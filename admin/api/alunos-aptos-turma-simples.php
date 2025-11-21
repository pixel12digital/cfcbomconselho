<?php
/**
 * API simplificada para buscar alunos aptos para matrícula em turmas teóricas
 * 
 * USO: Função centralizada GuardsExames::alunoComExamesOkParaTeoricas()
 * para garantir consistência com histórico do aluno
 * 
 * NOTA SOBRE CFC:
 * - CFC canônico do CFC Bom Conselho é ID 36 (não mais 1)
 * - Esta API usa SEMPRE o cfc_id real da turma/aluno vindo do banco
 * - NÃO assume valores fixos de CFC
 * - Migração CFC 1 → 36 é SEMPRE manual, via script documentado em docs/MIGRACAO_CFC_1_PARA_36.md
 * - Nenhuma rotina automática deve disparar UPDATEs de CFC
 */

header('Content-Type: application/json; charset=utf-8');

// Incluir dependências
$rootPath = dirname(__DIR__, 2);
require_once $rootPath . '/includes/config.php';
require_once $rootPath . '/includes/database.php';
require_once __DIR__ . '/../includes/guards_exames.php';
require_once __DIR__ . '/../includes/FinanceiroAlunoHelper.php';

// Função helper para obter usuário atual (se não existir globalmente)
if (!function_exists('getCurrentUser')) {
    function getCurrentUser() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user'] ?? null;
    }
}

try {
    $db = Database::getInstance();
    
    // Obter turma_id da requisição
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        $input = $_POST;
    }
    $turmaId = (int)($input['turma_id'] ?? 0);
    
    error_log("[TURMAS TEORICAS API] Requisição recebida - turma_id: {$turmaId}, input: " . json_encode($input));
    
    if (!$turmaId) {
        throw new Exception('turma_id é obrigatório');
    }
    
    // Buscar dados da turma para obter CFC e categoria (se houver)
    $turma = $db->fetch("
        SELECT cfc_id, curso_tipo 
        FROM turmas_teoricas 
        WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        throw new Exception('Turma não encontrada');
    }
    
    $cfcIdTurma = (int)$turma['cfc_id'];
    
    // Obter CFC da sessão (usuário logado)
    $user = getCurrentUser();
    $cfcIdSessao = $user ? ((int)($user['cfc_id'] ?? 0)) : 0;
    
    // Determinar se é admin global (cfc_id = 0 ou null)
    $isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null);
    $sessionCfcLabel = $isAdminGlobal ? 'admin_global' : 'cfc_especifico';
    
    // REGRA DE CFC:
    // - Admin Global (cfc_id = 0): pode gerenciar qualquer CFC, não bloqueia
    // - Usuário de CFC específico (cfc_id > 0): só pode gerenciar seu próprio CFC
    // - Alunos retornados SEMPRE devem ser do CFC da turma (independente do CFC da sessão)
    $cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao);
    
    // Bloquear acesso apenas se usuário de CFC específico tentar acessar turma de outro CFC
    if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) {
        error_log("[TURMAS TEORICAS API] BLOQUEIO: Usuário CFC {$cfcIdSessao} tentando acessar turma CFC {$cfcIdTurma}");
        throw new Exception('Acesso negado: você não tem permissão para gerenciar turmas deste CFC');
    }
    
    error_log("[TURMAS TEORICAS API] CFC da Turma: {$cfcIdTurma}, CFC da Sessão: {$cfcIdSessao} ({$sessionCfcLabel}), Admin Global: " . ($isAdminGlobal ? 'Sim' : 'Não'));
    
    // =====================================================
    // BUSCAR TODOS OS ALUNOS ATIVOS DO CFC
    // =====================================================
    // Não filtrar por exames na query inicial
    // A verificação será feita usando a função centralizada
    // IMPORTANTE: Usar $cfcIdTurma (não $cfcIdSessao) para filtrar alunos
    error_log("[TURMAS TEORICAS API] Executando query - turma_id={$turmaId}, cfc_id_turma={$cfcIdTurma}");
    
    try {
        $alunosCandidatos = $db->fetchAll("
            SELECT 
                a.id,
                a.nome,
                a.cpf,
                a.categoria_cnh,
                a.status as status_aluno,
                c.nome as cfc_nome,
                c.id as cfc_id,
                -- Incluir categoria da matrícula ativa (prioridade 1)
                m_ativa.categoria_cnh as categoria_cnh_matricula,
                m_ativa.tipo_servico as tipo_servico_matricula,
                CASE 
                    WHEN tm.id IS NOT NULL THEN 'matriculado'
                    ELSE 'disponivel'
                END as status_matricula
            FROM alunos a
            JOIN cfcs c ON a.cfc_id = c.id
            LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
                AND tm.turma_id = ? 
                AND tm.status IN ('matriculado', 'cursando')
            LEFT JOIN (
                SELECT aluno_id, categoria_cnh, tipo_servico
                FROM matriculas
                WHERE status = 'ativa'
            ) m_ativa ON a.id = m_ativa.aluno_id
            WHERE a.status = 'ativo'
                AND a.cfc_id = ?
            ORDER BY a.nome
        ", [$turmaId, $cfcIdTurma]);
    } catch (Exception $e) {
        error_log("[TURMAS TEORICAS API] ERRO na query de candidatos: " . $e->getMessage());
        error_log("[TURMAS TEORICAS API] Stack trace: " . $e->getTraceAsString());
        throw $e;
    }
    
    // Logs detalhados após a query
    error_log("[TURMAS TEORICAS API] Turma {$turmaId} - CFC Turma: {$cfcIdTurma}, CFC Sessao: {$cfcIdSessao} ({$sessionCfcLabel}), AdminGlobal=" . ($isAdminGlobal ? 'true' : 'false'));
    error_log("[TURMAS TEORICAS API] Turma {$turmaId} - Total candidatos brutos (antes de qualquer filtro): " . count($alunosCandidatos));
    
    // Log de cada candidato bruto encontrado
    foreach ($alunosCandidatos as $c) {
        error_log("[TURMAS TEORICAS API] CANDIDATO BRUTO - aluno_id={$c['id']}, nome={$c['nome']}, cfc_id={$c['cfc_id']}, status_aluno=" . ($c['status_aluno'] ?? 'N/A') . ", status_matricula=" . ($c['status_matricula'] ?? 'N/A'));
    }
    
    // Verificar especificamente se o aluno 167 está nos candidatos
    $aluno167Encontrado = false;
    foreach ($alunosCandidatos as $c) {
        if ((int)$c['id'] === 167) {
            $aluno167Encontrado = true;
            error_log("[TURMAS TEORICAS API] ✅ ALUNO 167 ENCONTRADO NOS CANDIDATOS BRUTOS - nome={$c['nome']}, cfc_id={$c['cfc_id']}, status_aluno={$c['status_aluno']}, status_matricula={$c['status_matricula']}");
            break;
        }
    }
    if (!$aluno167Encontrado) {
        error_log("[TURMAS TEORICAS API] ❌ ALUNO 167 NÃO ENCONTRADO NOS CANDIDATOS BRUTOS - Verificar se aluno está ativo e no CFC {$cfcIdTurma}");
        
        // Diagnóstico: buscar aluno 167 diretamente no banco
        try {
            $aluno167Diagnostico = $db->fetch("
                SELECT a.id, a.nome, a.status, a.cfc_id, c.id as cfc_id_join, c.nome as cfc_nome
                FROM alunos a
                LEFT JOIN cfcs c ON a.cfc_id = c.id
                WHERE a.id = 167
            ");
            
            if ($aluno167Diagnostico) {
                error_log("[TURMAS TEORICAS API] 🔍 DIAGNÓSTICO ALUNO 167:");
                error_log("[TURMAS TEORICAS API]   - ID: " . ($aluno167Diagnostico['id'] ?? 'N/A'));
                error_log("[TURMAS TEORICAS API]   - Nome: " . ($aluno167Diagnostico['nome'] ?? 'N/A'));
                error_log("[TURMAS TEORICAS API]   - Status: " . ($aluno167Diagnostico['status'] ?? 'N/A') . " (esperado: 'ativo')");
                error_log("[TURMAS TEORICAS API]   - CFC ID (alunos.cfc_id): " . ($aluno167Diagnostico['cfc_id'] ?? 'N/A') . " (esperado: {$cfcIdTurma})");
                error_log("[TURMAS TEORICAS API]   - CFC ID (join): " . ($aluno167Diagnostico['cfc_id_join'] ?? 'N/A'));
                error_log("[TURMAS TEORICAS API]   - CFC Nome: " . ($aluno167Diagnostico['cfc_nome'] ?? 'N/A'));
                
                // Verificar se status é diferente de 'ativo'
                if (($aluno167Diagnostico['status'] ?? '') !== 'ativo') {
                    error_log("[TURMAS TEORICAS API]   ⚠️ PROBLEMA: Status do aluno 167 não é 'ativo'!");
                }
                
                // Verificar se cfc_id é diferente do esperado
                if ((int)($aluno167Diagnostico['cfc_id'] ?? 0) !== $cfcIdTurma) {
                    error_log("[TURMAS TEORICAS API]   ⚠️ PROBLEMA: CFC do aluno 167 ({$aluno167Diagnostico['cfc_id']}) é diferente do CFC da turma ({$cfcIdTurma})!");
                }
            } else {
                error_log("[TURMAS TEORICAS API]   ❌ ERRO: Aluno 167 não existe no banco de dados!");
            }
        } catch (Exception $e) {
            error_log("[TURMAS TEORICAS API]   ❌ ERRO ao buscar diagnóstico do aluno 167: " . $e->getMessage());
        }
    }
    
    // =====================================================
    // FILTRAR ALUNOS USANDO FUNÇÃO CENTRALIZADA
    // =====================================================
    $alunosAptos = [];
    $debugInfo = [];
    
    foreach ($alunosCandidatos as $aluno) {
        $alunoId = (int)$aluno['id'];
        $alunoCfcId = (int)($aluno['cfc_id'] ?? 0);
        
        // BLINDAGEM EXTRA: Verificar se CFC do aluno corresponde ao CFC da turma
        // Mesmo que a query já filtre por CFC, esta verificação garante que nenhum aluno
        // de outra origem (ex: importação, migração) seja considerado incorretamente
        if ($alunoCfcId !== $cfcIdTurma) {
            error_log("[TURMAS TEORICAS API] WARNING: Aluno {$alunoId} ({$aluno['nome']}) com cfc_id={$alunoCfcId} diferente do cfc da turma {$cfcIdTurma} - IGNORANDO");
            continue; // Não considera este aluno
        }
        
        // Verificar exames usando função centralizada
        $examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);
        
        // Verificar financeiro usando helper centralizado
        $verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
        $financeiroOK = $verificacaoFinanceira['liberado'];
        
        // Verificar categoria (por enquanto, não filtrar por categoria)
        // NOTA: A turma não tem campo categoria_cnh direto.
        // Se houver necessidade de filtrar por categoria, verificar através da matrícula ativa do aluno.
        // Por enquanto, aceitar qualquer categoria.
        $categoriaOK = true; // TODO: Implementar filtro de categoria se necessário
        
        // Determinar elegibilidade
        $elegivel = ($examesOK && $financeiroOK && $categoriaOK && $aluno['status_matricula'] === 'disponivel');
        
        // Log específico para aluno 167 (Charles) - DETALHADO
        if ($alunoId === 167) {
            error_log("[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== ");
            error_log("[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id={$cfcIdTurma}, session_cfc_id={$cfcIdSessao} ({$sessionCfcLabel})");
            error_log("[TURMAS TEORICAS API] Aluno 167 - exames_ok=" . ($examesOK ? 'true' : 'false') . 
                     ", financeiro_ok=" . ($financeiroOK ? 'true' : 'false') . 
                     ", categoria_ok=" . ($categoriaOK ? 'true' : 'false') . 
                     ", status_matricula=" . $aluno['status_matricula'] .
                     ", elegivel=" . ($elegivel ? 'true' : 'false'));
            error_log("[TURMAS TEORICAS API] ================================= ");
        }
        
        // Aluno é elegível se:
        // 1. Exames OK (usando função centralizada)
        // 2. Financeiro OK (sem faturas vencidas)
        // 3. Categoria OK (por enquanto sempre true)
        // 4. Não está matriculado nesta turma
        // NOTA: Não há bloqueio por CFC aqui - alunos sempre são do CFC da turma (filtro na query)
        if ($elegivel) {
            // Buscar dados dos exames para exibição
            $exames = GuardsExames::getStatusExames($alunoId);
            
            $aluno['exame_medico_resultado'] = $exames['medico']['resultado'] ?? null;
            $aluno['exame_medico_data'] = $exames['medico']['data_resultado'] ?? null;
            $aluno['exame_medico_protocolo'] = $exames['medico']['protocolo'] ?? null;
            
            $aluno['exame_psicotecnico_resultado'] = $exames['psicotecnico']['resultado'] ?? null;
            $aluno['exame_psicotecnico_data'] = $exames['psicotecnico']['data_resultado'] ?? null;
            $aluno['exame_psicotecnico_protocolo'] = $exames['psicotecnico']['protocolo'] ?? null;
            
            $alunosAptos[] = $aluno;
        }
        
        // Log para debug (formato padronizado)
        error_log("[TURMAS TEORICAS API] Candidato aluno {$alunoId} ({$aluno['nome']}) - turma_cfc_id={$cfcIdTurma}, session_cfc_id={$cfcIdSessao}, financeiro_ok=" . ($financeiroOK ? 'true' : 'false') . 
                 ", exames_ok=" . ($examesOK ? 'true' : 'false') . 
                 ", categoria_ok=" . ($categoriaOK ? 'true' : 'false') . 
                 ", status_matricula=" . $aluno['status_matricula'] .
                 ", elegivel=" . ($elegivel ? 'true' : 'false'));
        
        $debugInfo[] = [
            'aluno_id' => $alunoId,
            'nome' => $aluno['nome'],
            'exames_ok' => $examesOK,
            'financeiro_ok' => $financeiroOK,
            'categoria_ok' => $categoriaOK,
            'status_matricula' => $aluno['status_matricula'],
            'elegivel' => $elegivel
        ];
    }
    
    // Montar debug_info com informações de CFC
    $debugInfoCompleto = [
        'turma_cfc_id' => $cfcIdTurma,
        'session_cfc_id' => $cfcIdSessao,
        'session_cfc_label' => $sessionCfcLabel,
        'is_admin_global' => $isAdminGlobal,
        'cfc_ids_match' => $cfcIdsCoincidem,
        'turma_id' => $turmaId,
        'total_candidatos' => count($alunosCandidatos),
        'total_aptos' => count($alunosAptos),
        'alunos_detalhados' => $debugInfo
    ];
    
    $response = [
        'sucesso' => true,
        'alunos' => array_values($alunosAptos),
        'estatisticas' => [
            'total_candidatos' => count($alunosCandidatos),
            'total_aptos' => count($alunosAptos),
            'total_matriculados' => count($alunosCandidatos) - count($alunosAptos)
        ],
        'debug_info' => $debugInfoCompleto,
        'debug' => [
            'turma_id' => $turmaId,
            'cfc_id_turma' => $cfcIdTurma,
            'cfc_id_sessao' => $cfcIdSessao,
            'session_cfc_label' => $sessionCfcLabel,
            'is_admin_global' => $isAdminGlobal,
            'cfc_ids_match' => $cfcIdsCoincidem,
            'alunos_encontrados' => count($alunosAptos),
            'total_candidatos' => count($alunosCandidatos)
        ]
    ];
    
    error_log("[TURMAS TEORICAS API] Resposta - Total aptos: " . count($alunosAptos) . ", CFC Turma: {$cfcIdTurma}, CFC Sessão: {$cfcIdSessao}, Coincidem: " . ($cfcIdsCoincidem ? 'Sim' : 'Não'));
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor',
        'debug' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
