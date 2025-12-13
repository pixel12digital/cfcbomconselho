<?php
/**
 * API de Presenças de Turma
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.2: API de Presença
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

/**
 * Função helper para responder erros sempre em JSON
 * Garante que este endpoint NUNCA devolva HTML, apenas JSON
 */
function responderJsonErro($mensagem, $statusCode = 400, array $extra = []) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    $payload = array_merge([
        'success' => false,
        'message' => $mensagem,
    ], $extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// Guard explícito de sessão/autenticação para este endpoint
$userId    = $_SESSION['user_id']   ?? null;
$userType  = $_SESSION['user_type'] ?? null;
$origem    = $_GET['origem']       ?? ($_POST['origem'] ?? '');

if (!$userId || !$userType) {
    responderJsonErro('Sessão expirada. Faça login novamente.', 401, [
        'code' => 'AUTH_NO_SESSION',
    ]);
}

// Verificar autenticação (compatibilidade com código existente)
if (!isLoggedIn()) {
    responderJsonErro('Usuário não autenticado', 401, [
        'code' => 'AUTH_NOT_LOGGED_IN',
    ]);
}

// FASE 1 - PRESENCA TEORICA - Ajustar permissões para incluir aluno (apenas leitura)
// Arquivo: admin/api/turma-presencas.php (linha ~38)
$currentUser = getCurrentUser();
$isAdmin = ($currentUser['tipo'] ?? '') === 'admin';
$isSecretaria = ($currentUser['tipo'] ?? '') === 'secretaria';
$isInstrutor = ($currentUser['tipo'] ?? '') === 'instrutor';
$isAluno = ($currentUser['tipo'] ?? '') === 'aluno';

// Aluno pode apenas ler suas próprias presenças (GET), não pode criar/editar/excluir
if (!$isAdmin && !$isSecretaria && !$isInstrutor && !$isAluno) {
    responderJsonErro('Permissão negada - Apenas administradores, secretaria, instrutores e alunos podem acessar presenças', 403, [
        'code' => 'PERMISSAO_NEGADA',
    ]);
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($db);
            break;
            
        case 'POST':
            handlePostRequest($db, $userId);
            break;
            
        case 'PUT':
            handlePutRequest($db, $userId);
            break;
            
        case 'DELETE':
            handleDeleteRequest($db);
            break;
            
        default:
            responderJsonErro('Método não permitido', 405, [
                'code' => 'METHOD_NOT_ALLOWED',
            ]);
            break;
    }
    
    } catch (Exception $e) {
        responderJsonErro('Erro interno do servidor: ' . $e->getMessage(), 500, [
            'code' => 'INTERNAL_ERROR',
        ]);
    }

/**
 * Manipular requisições GET
 * FASE 1 - PRESENCA TEORICA - Adicionar validação de segurança para aluno
 */
function handleGetRequest($db) {
    global $isAluno, $currentUser;
    
    // FASE 1 - PRESENCA TEORICA - Validação de segurança para aluno
    if ($isAluno) {
        $currentAlunoId = getCurrentAlunoId();
        if (!$currentAlunoId) {
            responderJsonErro('Aluno não encontrado ou não autenticado', 403, [
                'code' => 'ALUNO_NOT_FOUND',
            ]);
        }
        
        // Aluno só pode ver suas próprias presenças
        if (isset($_GET['aluno_id']) && (int)$_GET['aluno_id'] !== $currentAlunoId) {
            responderJsonErro('Permissão negada - Você só pode ver suas próprias presenças', 403, [
                'code' => 'PERMISSAO_NEGADA_ALUNO',
            ]);
        }
        
        // Se não especificou aluno_id mas especificou turma_id, usar o ID do aluno logado
        if (!isset($_GET['aluno_id']) && isset($_GET['turma_id'])) {
            $_GET['aluno_id'] = $currentAlunoId;
        }
        
        // Aluno não pode ver presenças de uma aula específica (todos os alunos) ou de toda a turma
        if (isset($_GET['turma_id']) && isset($_GET['aula_id'])) {
            responderJsonErro('Permissão negada - Alunos não podem ver presenças de outros alunos', 403, [
                'code' => 'PERMISSAO_NEGADA_ALUNO',
            ]);
        }
        
        if (isset($_GET['turma_id']) && !isset($_GET['aluno_id'])) {
            responderJsonErro('Permissão negada - Alunos não podem ver presenças de toda a turma', 403, [
                'code' => 'PERMISSAO_NEGADA_ALUNO',
            ]);
        }
        
        // Aluno não pode listar todas as presenças
        if (!isset($_GET['turma_id']) && !isset($_GET['aluno_id'])) {
            responderJsonErro('Permissão negada - Alunos não podem listar todas as presenças', 403, [
                'code' => 'PERMISSAO_NEGADA_ALUNO',
            ]);
        }
    }
    
    if (isset($_GET['turma_id']) && isset($_GET['aula_id'])) {
        // Buscar presenças de uma aula específica (apenas admin/secretaria/instrutor)
        $presencas = buscarPresencasAula($db, $_GET['turma_id'], $_GET['aula_id']);
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif (isset($_GET['aluno_id']) && isset($_GET['turma_id'])) {
        // Buscar presenças de um aluno em uma turma
        $presencas = buscarPresencasAluno($db, $_GET['aluno_id'], $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif (isset($_GET['turma_id'])) {
        // Buscar todas as presenças de uma turma (apenas admin/secretaria/instrutor)
        $presencas = buscarPresencasTurma($db, $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Listar presenças com filtros (apenas admin/secretaria/instrutor)
        $presencas = listarPresencas($db);
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Manipular requisições POST
 * FASE 1 - PRESENCA TEORICA - Bloquear aluno de criar presenças
 */
function handlePostRequest($db, $userId) {
    global $isAluno;
    
    // FASE 1 - PRESENCA TEORICA - Aluno não pode criar presenças
    if ($isAluno) {
        responderJsonErro('Permissão negada - Alunos não podem criar presenças', 403, [
            'code' => 'PERMISSAO_NEGADA_ALUNO',
        ]);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Verificar se é marcação em lote
    if (isset($input['presencas']) && is_array($input['presencas'])) {
        $resultado = marcarPresencasLote($db, $input, $userId);
    } else {
        $resultado = marcarPresencaIndividual($db, $input, $userId);
    }
    
    if ($resultado['success']) {
        http_response_code(201);
    } else {
        http_response_code(400);
    }
    
    echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições PUT
 * FASE 1 - PRESENCA TEORICA - Bloquear aluno de editar presenças
 */
function handlePutRequest($db, $userId) {
    global $isAluno;
    
    // FASE 1 - PRESENCA TEORICA - Aluno não pode editar presenças
    if ($isAluno) {
        responderJsonErro('Permissão negada - Alunos não podem editar presenças', 403, [
            'code' => 'PERMISSAO_NEGADA_ALUNO',
        ]);
    }
    
    $presencaId = $_GET['id'] ?? null;
    
    if (!$presencaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da presença é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'JSON inválido: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $resultado = atualizarPresenca($db, $presencaId, $input, $userId);
    
    if ($resultado['success']) {
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Manipular requisições DELETE
 * FASE 1 - PRESENCA TEORICA - Bloquear aluno de excluir presenças
 */
function handleDeleteRequest($db) {
    global $isAluno;
    
    // FASE 1 - PRESENCA TEORICA - Aluno não pode excluir presenças
    if ($isAluno) {
        responderJsonErro('Permissão negada - Alunos não podem excluir presenças', 403, [
            'code' => 'PERMISSAO_NEGADA_ALUNO',
        ]);
    }
    
    $presencaId = $_GET['id'] ?? null;
    
    if (!$presencaId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID da presença é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $resultado = excluirPresenca($db, $presencaId);
    
    if ($resultado['success']) {
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Buscar presenças de uma aula específica
 * CORRIGIDO: Usa turma_aula_id (nome correto do campo)
 * NOTA: justificativa removida - coluna não existe na tabela turma_presencas
 */
function buscarPresencasAula($db, $turmaId, $aulaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.registrado_por,
            tp.registrado_em,
            a.nome as aluno_nome,
            a.cpf as aluno_cpf,
            u.nome as registrado_por_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        LEFT JOIN usuarios u ON tp.registrado_por = u.id
        WHERE tp.turma_id = ? AND tp.turma_aula_id = ?
        ORDER BY a.nome ASC
    ";
    
    return $db->fetchAll($sql, [$turmaId, $aulaId]);
}

/**
 * Buscar presenças de um aluno em uma turma
 * CORRIGIDO: Usa turma_aula_id e turma_aulas_agendadas (tabela correta)
 * NOTA: justificativa removida - coluna não existe na tabela turma_presencas
 */
function buscarPresencasAluno($db, $alunoId, $turmaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.registrado_em,
            taa.nome_aula,
            taa.data_aula,
            taa.ordem_global as ordem
        FROM turma_presencas tp
        JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        WHERE tp.aluno_id = ? AND tp.turma_id = ?
        ORDER BY taa.ordem_global ASC
    ";
    
    return $db->fetchAll($sql, [$alunoId, $turmaId]);
}

/**
 * Buscar todas as presenças de uma turma
 * CORRIGIDO: Usa turma_aula_id e turma_aulas_agendadas (tabela correta)
 * NOTA: justificativa removida - coluna não existe na tabela turma_presencas
 */
function buscarPresencasTurma($db, $turmaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.registrado_em,
            a.nome as aluno_nome,
            taa.nome_aula,
            taa.data_aula,
            taa.ordem_global as ordem
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        WHERE tp.turma_id = ?
        ORDER BY taa.ordem_global ASC, a.nome ASC
    ";
    
    return $db->fetchAll($sql, [$turmaId]);
}

/**
 * Listar presenças com filtros
 * CORRIGIDO: Usa turma_aula_id, turma_aulas_agendadas e turmas_teoricas (tabelas corretas)
 * NOTA: justificativa removida - coluna não existe na tabela turma_presencas
 */
function listarPresencas($db) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.registrado_em,
            a.nome as aluno_nome,
            taa.nome_aula,
            tt.nome as turma_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id
        JOIN turmas_teoricas tt ON tp.turma_id = tt.id
        ORDER BY tp.registrado_em DESC
        LIMIT 100
    ";
    
    return $db->fetchAll($sql);
}

/**
 * Validar regras de edição de presença
 * 
 * REGRAS:
 * - Instrutor: só pode editar se for instrutor da turma, turma não está concluída/cancelada, aula não está cancelada
 * - Admin/Secretaria: pode editar qualquer turma/aula, exceto turmas canceladas
 * 
 * @param object $db Instância do banco
 * @param int $turmaId ID da turma
 * @param int $aulaId ID da aula
 * @param int $userId ID do usuário
 * @param bool $isAdmin Se é admin
 * @param bool $isSecretaria Se é secretaria
 * @param bool $isInstrutor Se é instrutor
 * @return array ['permitido' => bool, 'motivo' => string]
 */
function validarRegrasEdicaoPresenca($db, $turmaId, $aulaId, $userId, $isAdmin, $isSecretaria, $isInstrutor) {
    global $origem, $userType;
    
    // Buscar dados da turma
    // CORREÇÃO: turmas_teoricas não tem instrutor_id - o instrutor está em turma_aulas_agendadas
    $turma = $db->fetch(
        "SELECT status FROM turmas_teoricas WHERE id = ?",
        [$turmaId]
    );
    
    if (!$turma) {
        return [
            'permitido' => false,
            'motivo' => 'Turma não encontrada'
        ];
    }
    
    // Regra 1: Turma cancelada bloqueia todos
    if ($turma['status'] === 'cancelada') {
        return [
            'permitido' => false,
            'motivo' => 'Não é possível editar presenças de turmas canceladas'
        ];
    }
    
    // Regra 2: Instrutor só pode editar se for instrutor da aula específica
    // CORREÇÃO: Verificar instrutor através da aula agendada, não da turma
    // CORREÇÃO: Usar getCurrentInstrutorId() para obter o instrutor_id real, não comparar com userId
    if ($isInstrutor && !$isAdmin && !$isSecretaria) {
        // Obter o instrutor_id real do usuário logado
        $instrutorAtualId = getCurrentInstrutorId($userId);
        
        // Logging de debug para facilitar diagnóstico
        error_log('[turma-presencas] debug perm: user_id=' . (int)$userId .
                  ' user_type=' . $userType .
                  ' origem=' . $origem .
                  ' instrutorAtualId=' . (int)$instrutorAtualId .
                  ' aulaId=' . (int)$aulaId .
                  ' turmaId=' . (int)$turmaId);
        
        if (!$instrutorAtualId) {
            return [
                'permitido' => false,
                'motivo' => 'Instrutor não encontrado ou não vinculado ao usuário'
            ];
        }
        
        if ($aulaId) {
            // Buscar o instrutor da aula específica
            $aula = $db->fetch(
                "SELECT instrutor_id FROM turma_aulas_agendadas WHERE id = ? AND turma_id = ?",
                [$aulaId, $turmaId]
            );
            
            if (!$aula || $aula['instrutor_id'] != $instrutorAtualId) {
                return [
                    'permitido' => false,
                    'motivo' => 'Você não é o instrutor desta aula'
                ];
            }
        } else {
            // Se não há aula_id, verificar se o instrutor tem alguma aula nesta turma
            $temAula = $db->fetch(
                "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND instrutor_id = ?",
                [$turmaId, $instrutorAtualId]
            );
            
            if (!$temAula || $temAula['total'] == 0) {
                return [
                    'permitido' => false,
                    'motivo' => 'Você não é instrutor de nenhuma aula desta turma'
                ];
            }
        }
        
        // Regra 3: Instrutor não pode editar se turma está concluída
        if ($turma['status'] === 'concluida') {
            return [
                'permitido' => false,
                'motivo' => 'Não é possível editar presenças de turmas concluídas'
            ];
        }
    }
    
    // Buscar dados da aula
    $aula = $db->fetch(
        "SELECT status FROM turma_aulas_agendadas WHERE id = ? AND turma_id = ?",
        [$aulaId, $turmaId]
    );
    
    if (!$aula) {
        return [
            'permitido' => false,
            'motivo' => 'Aula não encontrada'
        ];
    }
    
    // Regra 4: Aula cancelada bloqueia todos
    if ($aula['status'] === 'cancelada') {
        return [
            'permitido' => false,
            'motivo' => 'Não é possível editar presenças de aulas canceladas'
        ];
    }
    
    // Admin/Secretaria podem editar qualquer turma/aula (exceto canceladas, já validado acima)
    // Instrutor pode editar se passou nas validações acima
    return [
        'permitido' => true,
        'motivo' => ''
    ];
}

/**
 * Marcar presença individual
 */
function marcarPresencaIndividual($db, $dados, $userId) {
    global $isAdmin, $isSecretaria, $isInstrutor;
    
    // Validar dados obrigatórios
    $validacao = validarDadosPresenca($dados);
    if (!$validacao['success']) {
        return $validacao;
    }
    
    // Normalizar nome do campo: aceitar tanto aula_id quanto turma_aula_id (compatibilidade)
    $turmaAulaId = (int)($dados['turma_aula_id'] ?? $dados['aula_id'] ?? 0);
    if (!$turmaAulaId) {
        return [
            'success' => false,
            'message' => 'ID da aula é obrigatório (aula_id ou turma_aula_id)'
        ];
    }
    
    // Validar regras de edição
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $dados['turma_id'], $turmaAulaId, $userId, $isAdmin, $isSecretaria, $isInstrutor);
    if (!$validacaoEdicao['permitido']) {
        return [
            'success' => false,
            'message' => $validacaoEdicao['motivo']
        ];
    }
    
    // Verificar se a turma existe (tabela correta: turmas_teoricas)
    $turma = $db->fetch(
        "SELECT id FROM turmas_teoricas WHERE id = ?",
        [$dados['turma_id']]
    );
    
    if (!$turma) {
        return [
            'success' => false,
            'message' => 'Turma não encontrada. Verifique se a turma existe em turmas_teoricas.'
        ];
    }
    
    // Verificar se aluno está matriculado na turma (tabela correta: turma_matriculas)
    $matricula = $db->fetch(
        "SELECT id FROM turma_matriculas WHERE turma_id = ? AND aluno_id = ?",
        [$dados['turma_id'], $dados['aluno_id']]
    );
    
    if (!$matricula) {
        return [
            'success' => false,
            'message' => 'Aluno não está matriculado nesta turma'
        ];
    }
    
    // AJUSTE 2025-12 - Verificar se já existe presença para esta aula
    // Se existir, atualizar em vez de criar nova (permite admin corrigir presenças)
    $presencaExistente = $db->fetch(
        "SELECT id FROM turma_presencas WHERE turma_id = ? AND turma_aula_id = ? AND aluno_id = ?",
        [$dados['turma_id'], $turmaAulaId, $dados['aluno_id']]
    );
    
    if ($presencaExistente) {
        // Se já existe, atualizar em vez de criar nova
        return atualizarPresenca($db, $presencaExistente['id'], $dados, $userId);
    }
    
    try {
        $db->beginTransaction();
        
        // TODO: implementar coluna justificativa na tabela turma_presencas
        // Por enquanto, justificativa é lida do payload mas não é gravada no banco
        $justificativa = $dados['justificativa'] ?? $dados['observacao'] ?? null;
        
        // Inserir presença (usando turma_aula_id, nomes corretos dos campos)
        $presencaId = $db->insert('turma_presencas', [
            'turma_id' => $dados['turma_id'],
            'turma_aula_id' => $turmaAulaId,
            'aluno_id' => $dados['aluno_id'],
            'presente' => $dados['presente'] ? 1 : 0,
            'registrado_por' => $userId
        ]);
        
        // FASE 1 - LOG PRESENCA TEORICA - INICIO
        // Registrar log de criação de presença
        registrarLogPresenca(
            $db,
            $presencaId,
            $dados['turma_id'],
            $turmaAulaId,
            $dados['aluno_id'],
            'create',
            $userId,
            null, // dadosAntigos = NULL para create
            $dados // dadosNovos
        );
        // FASE 1 - LOG PRESENCA TEORICA - FIM
        
        // Log de auditoria (mantido para compatibilidade)
        logAuditoria($db, $userId, 'presenca_criada', $presencaId, $dados);
        
        // Recalcular frequência do aluno após inserir presença
        require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';
        try {
            $turmaManager = new TurmaTeoricaManager($db);
            $turmaManager->recalcularFrequenciaAluno($dados['turma_id'], $dados['aluno_id']);
        } catch (Exception $e) {
            // Log do erro mas não interrompe o fluxo principal
            error_log("Erro ao recalcular frequência após criar presença: " . $e->getMessage());
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Presença registrada com sucesso',
            'presenca_id' => $presencaId
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao registrar presença: ' . $e->getMessage()
        ];
    }
}

/**
 * Marcar presenças em lote
 */
function marcarPresencasLote($db, $dados, $userId) {
    global $isAdmin, $isSecretaria, $isInstrutor;
    
    $turmaId = $dados['turma_id'];
    // Normalizar nome do campo: aceitar tanto aula_id quanto turma_aula_id
    $turmaAulaId = (int)($dados['turma_aula_id'] ?? $dados['aula_id'] ?? 0);
    if (!$turmaAulaId) {
        return [
            'success' => false,
            'message' => 'ID da aula é obrigatório (aula_id ou turma_aula_id)'
        ];
    }
    
    // Validar regras de edição uma vez para o lote
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $turmaId, $turmaAulaId, $userId, $isAdmin, $isSecretaria, $isInstrutor);
    if (!$validacaoEdicao['permitido']) {
        return [
            'success' => false,
            'message' => $validacaoEdicao['motivo']
        ];
    }
    
    $presencas = $dados['presencas'];
    
    if (empty($presencas)) {
        return [
            'success' => false,
            'message' => 'Lista de presenças não pode estar vazia'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        $sucessos = 0;
        $erros = [];
        $alunosProcessados = []; // Para recalcular frequência apenas uma vez por aluno
        
        foreach ($presencas as $index => $presenca) {
            // Validar dados da presença
            $presenca['turma_id'] = $turmaId;
            $presenca['turma_aula_id'] = $turmaAulaId; // Normalizar para turma_aula_id
            
            $validacao = validarDadosPresenca($presenca);
            if (!$validacao['success']) {
                $erros[] = "Presença " . ($index + 1) . ": " . $validacao['message'];
                continue;
            }
            
            // Verificar se a turma existe (tabela correta: turmas_teoricas)
            $turma = $db->fetch(
                "SELECT id FROM turmas_teoricas WHERE id = ?",
                [$turmaId]
            );
            
            if (!$turma) {
                $erros[] = "Presença " . ($index + 1) . ": Turma não encontrada (ID: $turmaId)";
                continue;
            }
            
            // Verificar se aluno está matriculado (tabela correta: turma_matriculas)
            $matricula = $db->fetch(
                "SELECT id FROM turma_matriculas WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $presenca['aluno_id']]
            );
            
            if (!$matricula) {
                $erros[] = "Presença " . ($index + 1) . ": Aluno não matriculado";
                continue;
            }
            
            // Verificar duplicidade (usando turma_aula_id, nome correto do campo)
            $presencaExistente = $db->fetch(
                "SELECT id FROM turma_presencas WHERE turma_id = ? AND turma_aula_id = ? AND aluno_id = ?",
                [$turmaId, $turmaAulaId, $presenca['aluno_id']]
            );
            
            if ($presencaExistente) {
                $erros[] = "Presença " . ($index + 1) . ": Já registrada";
                continue;
            }
            
            // TODO: implementar coluna justificativa na tabela turma_presencas
            // Por enquanto, justificativa é lida do payload mas não é gravada no banco
            $justificativa = $presenca['justificativa'] ?? $presenca['observacao'] ?? null;
            
            // Inserir presença (usando turma_aula_id, nomes corretos)
            $presencaId = $db->insert('turma_presencas', [
                'turma_id' => $turmaId,
                'turma_aula_id' => $turmaAulaId,
                'aluno_id' => $presenca['aluno_id'],
                'presente' => $presenca['presente'] ? 1 : 0,
                'registrado_por' => $userId
            ]);
            
            // FASE 1 - LOG PRESENCA TEORICA - INICIO
            // Registrar log de criação de presença em lote
            registrarLogPresenca(
                $db,
                $presencaId,
                $turmaId,
                $turmaAulaId,
                $presenca['aluno_id'],
                'create',
                $userId,
                null, // dadosAntigos = NULL para create
                $presenca // dadosNovos
            );
            // FASE 1 - LOG PRESENCA TEORICA - FIM
            
            // Marcar aluno para recalcular frequência depois
            if (!in_array($presenca['aluno_id'], $alunosProcessados)) {
                $alunosProcessados[] = $presenca['aluno_id'];
            }
            
            $sucessos++;
        }
        
        // Recalcular frequência de todos os alunos processados
        if (!empty($alunosProcessados)) {
            require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';
            $turmaManager = new TurmaTeoricaManager($db);
            foreach ($alunosProcessados as $alunoId) {
                try {
                    $turmaManager->recalcularFrequenciaAluno($turmaId, $alunoId);
                } catch (Exception $e) {
                    error_log("Erro ao recalcular frequência do aluno {$alunoId}: " . $e->getMessage());
                }
            }
        }
        
        // Log de auditoria
        logAuditoria($db, $userId, 'presencas_lote', null, [
            'turma_id' => $turmaId,
            'turma_aula_id' => $turmaAulaId,
            'total_presencas' => count($presencas),
            'sucessos' => $sucessos,
            'erros' => count($erros)
        ]);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => "Presenças processadas: $sucessos sucessos, " . count($erros) . " erros",
            'sucessos' => $sucessos,
            'erros' => $erros
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao processar presenças em lote: ' . $e->getMessage()
        ];
    }
}

/**
 * Atualizar presença
 */
function atualizarPresenca($db, $presencaId, $dados, $userId) {
    global $isAdmin, $isSecretaria, $isInstrutor;
    
    // Verificar se presença existe
    $presenca = $db->fetch("SELECT * FROM turma_presencas WHERE id = ?", [$presencaId]);
    if (!$presenca) {
        return [
            'success' => false,
            'message' => 'Presença não encontrada'
        ];
    }
    
    // Validar regras de edição
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $presenca['turma_id'], $presenca['turma_aula_id'], $userId, $isAdmin, $isSecretaria, $isInstrutor);
    if (!$validacaoEdicao['permitido']) {
        return [
            'success' => false,
            'message' => $validacaoEdicao['motivo']
        ];
    }
    
    // Validar dados
    $validacao = validarDadosPresenca($dados, $presencaId);
    if (!$validacao['success']) {
        return $validacao;
    }
    
    try {
        $db->beginTransaction();
        
        // FASE 1 - LOG PRESENCA TEORICA - INICIO
        // Registrar log ANTES de atualizar (para capturar valores antigos)
        registrarLogPresenca(
            $db,
            $presencaId,
            $presenca['turma_id'],
            $presenca['turma_aula_id'],
            $presenca['aluno_id'],
            'update',
            $userId,
            $presenca, // dadosAntigos
            $dados // dadosNovos
        );
        // FASE 1 - LOG PRESENCA TEORICA - FIM
        
        // TODO: implementar coluna justificativa na tabela turma_presencas
        // Por enquanto, justificativa é lida do payload mas não é gravada no banco
        $justificativa = $dados['justificativa'] ?? $dados['observacao'] ?? null;
        
        // Atualizar presença
        $db->update('turma_presencas', [
            'presente' => $dados['presente'] ? 1 : 0
        ], 'id = ?', [$presencaId]);
        
        // Log de auditoria (mantido para compatibilidade)
        logAuditoria($db, $userId, 'presenca_atualizada', $presencaId, [
            'dados_anteriores' => $presenca,
            'dados_novos' => $dados
        ]);
        
        // Recalcular frequência do aluno após atualizar presença
        require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';
        try {
            $turmaManager = new TurmaTeoricaManager($db);
            $turmaManager->recalcularFrequenciaAluno($presenca['turma_id'], $presenca['aluno_id']);
        } catch (Exception $e) {
            // Log do erro mas não interrompe o fluxo principal
            error_log("Erro ao recalcular frequência após atualizar presença: " . $e->getMessage());
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Presença atualizada com sucesso'
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao atualizar presença: ' . $e->getMessage()
        ];
    }
}

/**
 * Excluir presença
 */
function excluirPresenca($db, $presencaId) {
    global $isAdmin, $isSecretaria, $isInstrutor;
    $userId = $_SESSION['user_id'] ?? 1;
    
    // Verificar se presença existe
    $presenca = $db->fetch("SELECT * FROM turma_presencas WHERE id = ?", [$presencaId]);
    if (!$presenca) {
        return [
            'success' => false,
            'message' => 'Presença não encontrada'
        ];
    }
    
    // Validar regras de edição
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $presenca['turma_id'], $presenca['turma_aula_id'], $userId, $isAdmin, $isSecretaria, $isInstrutor);
    if (!$validacaoEdicao['permitido']) {
        return [
            'success' => false,
            'message' => $validacaoEdicao['motivo']
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // FASE 1 - LOG PRESENCA TEORICA - INICIO
        // Registrar log ANTES de excluir (para capturar valores atuais)
        registrarLogPresenca(
            $db,
            $presencaId,
            $presenca['turma_id'],
            $presenca['turma_aula_id'],
            $presenca['aluno_id'],
            'delete',
            $userId,
            $presenca, // dadosAntigos
            null // dadosNovos = NULL para delete
        );
        // FASE 1 - LOG PRESENCA TEORICA - FIM
        
        // Excluir presença
        $db->delete('turma_presencas', 'id = ?', [$presencaId]);
        
        // Log de auditoria (mantido para compatibilidade)
        logAuditoria($db, $_SESSION['user_id'] ?? 1, 'presenca_excluida', $presencaId, $presenca);
        
        // Recalcular frequência do aluno após excluir presença
        require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';
        try {
            $turmaManager = new TurmaTeoricaManager($db);
            $turmaManager->recalcularFrequenciaAluno($presenca['turma_id'], $presenca['aluno_id']);
        } catch (Exception $e) {
            // Log do erro mas não interrompe o fluxo principal
            error_log("Erro ao recalcular frequência após excluir presença: " . $e->getMessage());
        }
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Presença excluída com sucesso'
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao excluir presença: ' . $e->getMessage()
        ];
    }
}

/**
 * Validar dados da presença
 * CORRIGIDO: Aceita tanto aula_id quanto turma_aula_id (compatibilidade)
 * AJUSTE 2025-12: Para atualização (quando presencaId existe), apenas presente é obrigatório
 */
function validarDadosPresenca($dados, $presencaId = null) {
    $erros = [];
    
    // Se é atualização (presencaId existe), apenas presente é obrigatório
    if ($presencaId !== null) {
        if (!isset($dados['presente'])) {
            $erros[] = 'Status de presença é obrigatório';
        }
        
        if (!empty($erros)) {
            return [
                'success' => false,
                'message' => 'Dados inválidos',
                'errors' => $erros
            ];
        }
        
        return ['success' => true];
    }
    
    // Se é criação, todos os campos são obrigatórios
    if (empty($dados['turma_id'])) {
        $erros[] = 'ID da turma é obrigatório';
    }
    
    // Aceitar tanto aula_id quanto turma_aula_id (compatibilidade)
    $aulaId = $dados['aula_id'] ?? $dados['turma_aula_id'] ?? null;
    if (empty($aulaId)) {
        $erros[] = 'ID da aula é obrigatório (aula_id ou turma_aula_id)';
    }
    
    if (empty($dados['aluno_id'])) {
        $erros[] = 'ID do aluno é obrigatório';
    }
    
    if (!isset($dados['presente'])) {
        $erros[] = 'Status de presença é obrigatório';
    }
    
    if (!empty($erros)) {
        return [
            'success' => false,
            'message' => 'Dados inválidos',
            'errors' => $erros
        ];
    }
    
    return ['success' => true];
}

/**
 * Log de auditoria (mantido para compatibilidade)
 */
function logAuditoria($db, $userId, $acao, $presencaId, $dados) {
    try {
        $db->insert('logs', [
            'usuario_id' => $userId,
            'acao' => $acao,
            'tabela_afetada' => 'turma_presencas',
            'registro_id' => $presencaId,
            'dados_novos' => json_encode($dados),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'criado_em' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Log de auditoria falhou, mas não deve interromper o processo principal
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
    }
}

/**
 * FASE 1 - LOG PRESENCA TEORICA - INICIO
 * Registrar log de alteração de presença na tabela turma_presencas_log
 * 
 * @param object $db Instância do banco de dados
 * @param int $presencaId ID da presença (NULL se for delete)
 * @param int $turmaId ID da turma
 * @param int $aulaId ID da aula
 * @param int $alunoId ID do aluno
 * @param string $acao Ação realizada: 'create', 'update', 'delete'
 * @param int $userId ID do usuário que fez a alteração
 * @param array|null $dadosAntigos Dados antes da alteração (para update/delete)
 * @param array|null $dadosNovos Dados depois da alteração (para create/update)
 */
function registrarLogPresenca($db, $presencaId, $turmaId, $aulaId, $alunoId, $acao, $userId, $dadosAntigos = null, $dadosNovos = null) {
    try {
        // Preparar valores antes e depois conforme a ação
        $presenteAntes = null;
        $justificativaAntes = null;
        $presenteDepois = null;
        $justificativaDepois = null;
        
        if ($acao === 'create') {
            // CREATE: antes é NULL, depois são os valores novos
            $presenteDepois = isset($dadosNovos['presente']) ? ($dadosNovos['presente'] ? 1 : 0) : null;
            $justificativaDepois = $dadosNovos['justificativa'] ?? $dadosNovos['observacao'] ?? null;
        } elseif ($acao === 'update') {
            // UPDATE: antes são os valores antigos, depois são os valores novos
            $presenteAntes = isset($dadosAntigos['presente']) ? ($dadosAntigos['presente'] ? 1 : 0) : null;
            $justificativaAntes = $dadosAntigos['justificativa'] ?? null;
            $presenteDepois = isset($dadosNovos['presente']) ? ($dadosNovos['presente'] ? 1 : 0) : null;
            $justificativaDepois = $dadosNovos['justificativa'] ?? $dadosNovos['observacao'] ?? null;
        } elseif ($acao === 'delete') {
            // DELETE: antes são os valores atuais, depois é NULL
            $presenteAntes = isset($dadosAntigos['presente']) ? ($dadosAntigos['presente'] ? 1 : 0) : null;
            $justificativaAntes = $dadosAntigos['justificativa'] ?? null;
            // presente_depois e justificativa_depois permanecem NULL
        }
        
        // Inserir log na tabela turma_presencas_log
        $db->insert('turma_presencas_log', [
            'presenca_id' => $presencaId,
            'turma_id' => $turmaId,
            'aula_id' => $aulaId,
            'aluno_id' => $alunoId,
            'presente_antes' => $presenteAntes,
            'justificativa_antes' => $justificativaAntes,
            'presente_depois' => $presenteDepois,
            'justificativa_depois' => $justificativaDepois,
            'acao' => $acao,
            'alterado_por' => $userId
        ]);
        
    } catch (Exception $e) {
        // Log de erro silencioso - não deve interromper o processo principal
        error_log("FASE 1 - LOG PRESENCA TEORICA - Erro ao registrar log de presença: " . $e->getMessage());
    }
}
// FASE 1 - LOG PRESENCA TEORICA - FIM
?>
