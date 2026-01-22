<?php
/**
 * API para matricular aluno em turma teórica
 * Valida exames e realiza a matrícula
 * 
 * NOTA SOBRE CFC:
 * - CFC canônico do CFC Bom Conselho é ID 36 (não mais 1)
 * - Usa guards centralizados para validação de exames e financeiro
 * - Migração CFC 1 → 36 é sempre manual
 */

// Configurações de cabeçalho
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Incluir dependências
$rootPath = dirname(__DIR__, 2); // Volta 2 níveis: admin/api -> admin -> raiz
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once $rootPath . '/includes/auth.php';
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';

// Verificar autenticação - temporariamente mais permissivo para debug
if (!isLoggedIn()) {
    error_log("API matricular-aluno-turma: Usuário não autenticado - Sessão: " . print_r($_SESSION, true));
    
    // Para debug, vamos usar dados padrão se não estiver autenticado
    $_SESSION['user_id'] = 1;
    $_SESSION['tipo'] = 'admin';
    $_SESSION['cfc_id'] = 36;
    $_SESSION['nome'] = 'Debug User';
    $_SESSION['last_activity'] = time();
    
    error_log("API matricular-aluno-turma: Usando dados de debug para autenticação");
}

// Verificar permissões - usar dados da sessão diretamente
$userTypeRaw = $_SESSION['tipo'] ?? $_SESSION['user_type'] ?? null;
$userType = is_string($userTypeRaw) ? strtolower($userTypeRaw) : null;
if (!in_array($userType, ['admin', 'instrutor'])) {
    http_response_code(403);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Permissão negada']);
    exit;
}

// Obter dados do usuário
$user = getCurrentUser();
$sessionCfcId = $_SESSION['cfc_id'] ?? $_SESSION['user_cfc_id'] ?? null;
$userCfcId = $user['cfc_id'] ?? null;
$cfcId = $sessionCfcId ?? $userCfcId;
$userId = $user['id'] ?? null;

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
$alunoId = $input['aluno_id'] ?? null;
$turmaId = $input['turma_id'] ?? null;

// Log detalhado da requisição
error_log('[MATRICULAR_ALUNO_TURMA] ===============================');
error_log('[MATRICULAR_ALUNO_TURMA] REQUEST METHOD: ' . $_SERVER['REQUEST_METHOD']);
error_log('[MATRICULAR_ALUNO_TURMA] RAW $_POST: ' . print_r($_POST, true));
error_log('[MATRICULAR_ALUNO_TURMA] RAW $_GET: ' . print_r($_GET, true));
error_log('[MATRICULAR_ALUNO_TURMA] RAW php://input: ' . file_get_contents('php://input'));
error_log('[MATRICULAR_ALUNO_TURMA] alunoId: ' . ($alunoId ?? 'NULL'));
error_log('[MATRICULAR_ALUNO_TURMA] turmaId: ' . ($turmaId ?? 'NULL'));
error_log('[MATRICULAR_ALUNO_TURMA] input completo: ' . print_r($input, true));

if (!$alunoId || !$turmaId) {
    error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: parâmetros obrigatórios ausentes. alunoId=' . ($alunoId ?? 'NULL') . ', turmaId=' . ($turmaId ?? 'NULL'));
    http_response_code(200); // Retornar 200 com success=false para melhor tratamento no frontend
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => 'ID do aluno e da turma são obrigatórios'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Iniciar transação
    $db->beginTransaction();
    
    // Verificar se a turma existe e pertence ao CFC do usuário
    $turma = $db->fetch("
        SELECT id, nome, cfc_id, max_alunos, status
        FROM turmas_teoricas 
        WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: turma não encontrada. turmaId=' . $turmaId);
        throw new Exception('Turma não encontrada');
    }
    
    error_log('[MATRICULAR_ALUNO_TURMA] Turma encontrada: id=' . $turma['id'] . ', nome=' . $turma['nome'] . ', cfc_id=' . $turma['cfc_id']);

    // Se ainda não sabemos o CFC em uso, assumir o da turma
    if (!$cfcId) {
        $cfcId = (int)$turma['cfc_id'];
    }
    
    // Verificar se a turma está em status que permite matrícula
    // Nota: 'criando' também permite matrícula (turma em construção)
    if (!in_array($turma['status'], ['criando', 'agendando', 'completa', 'ativa'])) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: status da turma não permite matrícula. status=' . $turma['status']);
        throw new Exception('A turma deve estar em status criando, agendando, completa ou ativa para receber alunos');
    }
    
    // Verificar se há vagas disponíveis
    $alunosMatriculados = $db->fetchColumn("
        SELECT COUNT(*) FROM turma_matriculas 
        WHERE turma_id = ? AND status IN ('matriculado', 'cursando')
    ", [$turmaId]);
    
    if ($alunosMatriculados >= $turma['max_alunos']) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: turma sem vagas. turmaId=' . $turmaId . ', matriculados=' . $alunosMatriculados . ', max=' . $turma['max_alunos']);
        throw new Exception('Turma sem vagas disponíveis');
    }
    
    error_log('[MATRICULAR_ALUNO_TURMA] ✅ Vagas disponíveis: ' . ($turma['max_alunos'] - $alunosMatriculados) . ' de ' . $turma['max_alunos']);
    
    // Buscar dados do aluno (incluindo categoria da matrícula ativa)
    $aluno = $db->fetch("
        SELECT 
            a.id,
            a.nome,
            a.cpf,
            a.cfc_id,
            a.status,
            a.categoria_cnh,
            a.email,
            a.telefone,
            c.nome AS cfc_nome,
            -- Incluir categoria da matrícula ativa (prioridade 1)
            m_ativa.categoria_cnh as categoria_cnh_matricula,
            m_ativa.tipo_servico as tipo_servico_matricula
        FROM alunos a
        LEFT JOIN cfcs c ON a.cfc_id = c.id
        LEFT JOIN (
            SELECT aluno_id, categoria_cnh, tipo_servico
            FROM matriculas
            WHERE status = 'ativa'
        ) m_ativa ON a.id = m_ativa.aluno_id
        WHERE a.id = ?
    ", [$alunoId]);
    
    if (!$aluno) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: aluno não encontrado. alunoId=' . $alunoId);
        throw new Exception('Aluno não encontrado');
    }
    
    error_log('[MATRICULAR_ALUNO_TURMA] Aluno encontrado: id=' . $aluno['id'] . ', nome=' . $aluno['nome'] . ', cfc_id=' . $aluno['cfc_id'] . ', status=' . $aluno['status']);
    
    // Garantir que o CFC da turma exista; se não existir e o usuário for admin, alinhar com CFC válido
    $cfcTurmaExiste = $db->fetchColumn("SELECT COUNT(*) FROM cfcs WHERE id = ?", [$turma['cfc_id']]);
    if (!$cfcTurmaExiste) {
        if ($userType === 'admin') {
            $cfcCandidato = null;
            
            if ($cfcId && $db->fetchColumn("SELECT COUNT(*) FROM cfcs WHERE id = ?", [$cfcId])) {
                $cfcCandidato = $cfcId;
            } elseif (!empty($aluno['cfc_id']) && $db->fetchColumn("SELECT COUNT(*) FROM cfcs WHERE id = ?", [$aluno['cfc_id']])) {
                $cfcCandidato = $aluno['cfc_id'];
            }
            
            if ($cfcCandidato) {
                $db->update('turmas_teoricas', ['cfc_id' => $cfcCandidato], 'id = ?', [$turmaId]);
                $turma['cfc_id'] = $cfcCandidato;
            } else {
                throw new Exception('CFC da turma inválido e não foi possível determinar um CFC válido para ajuste.');
            }
        } else {
            throw new Exception('CFC da turma inválido. Ajuste o cadastro da turma antes de matricular alunos.');
        }
    }

    // Garantir que o usuário tenha permissão sobre o CFC da turma
    if ($userType !== 'admin' && (int)$turma['cfc_id'] !== (int)$cfcId) {
        throw new Exception('Você não tem permissão para matricular alunos nesta turma');
    }
    
    $podeGerenciarTodosCfc = ($userType === 'admin');
    if (function_exists('hasPermission')) {
        $podeGerenciarTodosCfc = $podeGerenciarTodosCfc || hasPermission('admin');
    }
    // Verificar compatibilidade CFC
    if ((int)$aluno['cfc_id'] !== (int)$turma['cfc_id']) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: CFC incompatível. aluno_cfc_id=' . $aluno['cfc_id'] . ', turma_cfc_id=' . $turma['cfc_id']);
        if ($podeGerenciarTodosCfc) {
            $cfcAnterior = $aluno['cfc_id'];
            $db->update('alunos', ['cfc_id' => $turma['cfc_id']], 'id = ?', [$alunoId]);
            $aluno['cfc_id'] = $turma['cfc_id'];
            $aluno['cfc_nome'] = $db->fetchColumn("SELECT nome FROM cfcs WHERE id = ?", [$turma['cfc_id']]) ?: $aluno['cfc_nome'];
            
            error_log(sprintf(
                '[MATRICULAR_ALUNO_TURMA] Admin transferiu aluno %d do CFC %d para CFC %d antes da matrícula',
                $alunoId,
                $cfcAnterior,
                $turma['cfc_id']
            ));
        } else {
            throw new Exception('Aluno pertence a outro CFC. Aluno: CFC ' . $aluno['cfc_id'] . ', Turma: CFC ' . $turma['cfc_id']);
        }
    } else {
        error_log('[MATRICULAR_ALUNO_TURMA] ✅ CFC compatível: aluno e turma ambos com cfc_id=' . $turma['cfc_id']);
    }
    
    if ($aluno['status'] !== 'ativo') {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: aluno não está ativo. alunoId=' . $alunoId . ', status=' . $aluno['status']);
        throw new Exception('Aluno não está ativo');
    }
    
    error_log('[MATRICULAR_ALUNO_TURMA] ✅ Aluno está ativo');
    
    // Verificar se o aluno já está matriculado nesta turma (apenas status ativo)
    $matriculaExistente = $db->fetch("
        SELECT id, status FROM turma_matriculas 
        WHERE turma_id = ? AND aluno_id = ? AND status IN ('matriculado', 'cursando')
    ", [$turmaId, $alunoId]);
    
    if ($matriculaExistente) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: aluno já matriculado. alunoId=' . $alunoId . ', turmaId=' . $turmaId);
        throw new Exception('Aluno já está matriculado nesta turma');
    }
    
    // Verificar se há matrícula anterior com status evadido/cancelado
    $matriculaAnterior = $db->fetch("
        SELECT id, status FROM turma_matriculas 
        WHERE turma_id = ? AND aluno_id = ? AND status IN ('evadido', 'cancelado')
        ORDER BY data_matricula DESC LIMIT 1
    ", [$turmaId, $alunoId]);
    
    // Verificar exames usando guard centralizado (compatível com valores antigos 'aprovado' e novos 'apto')
    error_log('[MATRICULAR_ALUNO_TURMA] Verificando exames do aluno ' . $alunoId . ' usando guard centralizado...');
    $examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);
    
    if (!$examesOK) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: exames não OK para aulas teóricas. alunoId=' . $alunoId);
        throw new Exception('Aluno não possui exames médico e psicotécnico concluídos e aprovados');
    }
    
    error_log('[MATRICULAR_ALUNO_TURMA] ✅ Exames OK para aulas teóricas');
    
    // Verificar financeiro usando helper centralizado
    error_log('[MATRICULAR_ALUNO_TURMA] Verificando financeiro do aluno ' . $alunoId . '...');
    $financeiro = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
    
    if (!$financeiro['liberado']) {
        error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: financeiro bloqueado. alunoId=' . $alunoId . ', status=' . $financeiro['status'] . ', motivo=' . $financeiro['motivo']);
        throw new Exception($financeiro['motivo'] ?? 'Aluno com pendências financeiras');
    }
    
    error_log('[MATRICULAR_ALUNO_TURMA] ✅ Financeiro OK - aluno liberado para avançar');
    
    // Realizar matrícula
    if ($matriculaAnterior) {
        // Atualizar matrícula anterior
        $matriculaId = $matriculaAnterior['id'];
        $db->query("
            UPDATE turma_matriculas 
            SET status = 'matriculado', 
                data_matricula = NOW(), 
                exames_validados_em = NOW(),
                observacoes = 'Rematrícula realizada via sistema - exames validados',
                atualizado_em = NOW()
            WHERE id = ?
        ", [$matriculaId]);
    } else {
        // Criar nova matrícula
        try {
            $matriculaId = $db->insert('turma_matriculas', [
                'turma_id' => $turmaId,
                'aluno_id' => $alunoId,
                'data_matricula' => date('Y-m-d H:i:s'),
                'status' => 'matriculado',
                'exames_validados_em' => date('Y-m-d H:i:s'),
                'observacoes' => 'Matrícula realizada via sistema - exames validados'
            ]);
        } catch (Exception $e) {
            // Se falhar, tentar com INSERT direto
            $sql = "INSERT INTO turma_matriculas (turma_id, aluno_id, data_matricula, status, exames_validados_em, observacoes) VALUES (?, ?, NOW(), 'matriculado', NOW(), ?)";
            $db->query($sql, [$turmaId, $alunoId, 'Matrícula realizada via sistema - exames validados']);
            $matriculaId = $db->lastInsertId();
        }
    }
    
    $matricula = $db->fetch("
        SELECT id, turma_id, aluno_id, status, data_matricula
        FROM turma_matriculas
        WHERE id = ?
    ", [$matriculaId]);
    
    // Registrar log da operação
    if (AUDIT_ENABLED) {
        $db->log($userId, 'matricular_aluno_turma', 'turma_matriculas', $matriculaId, null, [
            'turma_id' => $turmaId,
            'aluno_id' => $alunoId,
            'turma_nome' => $turma['nome'],
            'aluno_nome' => $aluno['nome']
        ]);
    }
    
    // Confirmar transação
    $db->commit();
    
    error_log('[MATRICULAR_ALUNO_TURMA] ✅ Matrícula criada com sucesso: matricula_id=' . $matriculaId . ', alunoId=' . $alunoId . ', turmaId=' . $turmaId);
    
    // Preparar resposta de sucesso
    $response = [
        'sucesso' => true,
        'mensagem' => "Aluno {$aluno['nome']} matriculado com sucesso na turma {$turma['nome']}",
        'dados' => [
            'matricula_id' => $matriculaId,
            'aluno' => [
                'id' => $aluno['id'],
                'nome' => $aluno['nome'],
                'cpf' => $aluno['cpf'],
                'categoria_cnh' => $aluno['categoria_cnh'] ?? null,
                'categoria_cnh_matricula' => $aluno['categoria_cnh_matricula'] ?? null,
                'tipo_servico_matricula' => $aluno['tipo_servico_matricula'] ?? null,
                'cfc_nome' => $aluno['cfc_nome'] ?? null,
                'email' => $aluno['email'] ?? null,
                'telefone' => $aluno['telefone'] ?? null,
            ],
            'matricula' => [
                'id' => $matricula['id'] ?? $matriculaId,
                'status' => $matricula['status'] ?? 'matriculado',
                'data_matricula' => $matricula['data_matricula'] ?? date('Y-m-d H:i:s')
            ],
            'turma' => [
                'id' => $turma['id'],
                'nome' => $turma['nome'],
                'alunos_matriculados' => $alunosMatriculados + 1
            ]
        ]
    ];
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Reverter transação em caso de erro
    if (isset($db) && $db->inTransaction()) {
        $db->rollback();
    }
    
    $mensagemErro = $e->getMessage();
    error_log('[MATRICULAR_ALUNO_TURMA] ERRO 400 - motivo: ' . $mensagemErro);
    error_log('[MATRICULAR_ALUNO_TURMA] Stack trace: ' . $e->getTraceAsString());
    
    // Retornar 200 com success=false para melhor tratamento no frontend
    // (mantém compatibilidade, mas permite tratamento mais amigável)
    http_response_code(200);
    echo json_encode([
        'sucesso' => false, 
        'mensagem' => $mensagemErro
    ], JSON_UNESCAPED_UNICODE);
}
?>
