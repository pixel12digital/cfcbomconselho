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

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// Verificar se é admin, secretaria ou instrutor
require_once __DIR__ . '/../../includes/auth.php';
$currentUser = getCurrentUser();
$isAdmin = ($currentUser['tipo'] ?? '') === 'admin';
$isSecretaria = ($currentUser['tipo'] ?? '') === 'secretaria';
$isInstrutor = ($currentUser['tipo'] ?? '') === 'instrutor';

if (!$isAdmin && !$isSecretaria && !$isInstrutor) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Permissão negada - Apenas administradores, secretaria e instrutores podem gerenciar presenças'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$userId = $_SESSION['user_id'] ?? 1;

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
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'message' => 'Método não permitido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições GET
 */
function handleGetRequest($db) {
    if (isset($_GET['turma_id']) && isset($_GET['aula_id'])) {
        // Buscar presenças de uma aula específica
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
        // Buscar todas as presenças de uma turma
        $presencas = buscarPresencasTurma($db, $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Listar presenças com filtros
        $presencas = listarPresencas($db);
        echo json_encode([
            'success' => true,
            'data' => $presencas
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Manipular requisições POST
 */
function handlePostRequest($db, $userId) {
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
 */
function handlePutRequest($db, $userId) {
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
 */
function handleDeleteRequest($db) {
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
 * CORRIGIDO: Usa aula_id (nome correto do campo) e justificativa (nome correto do campo)
 */
function buscarPresencasAula($db, $turmaId, $aulaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.aula_id,
            tp.aluno_id,
            tp.presente,
            tp.justificativa,
            tp.registrado_por,
            tp.registrado_em,
            a.nome as aluno_nome,
            a.cpf as aluno_cpf,
            u.nome as registrado_por_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        LEFT JOIN usuarios u ON tp.registrado_por = u.id
        WHERE tp.turma_id = ? AND tp.aula_id = ?
        ORDER BY a.nome ASC
    ";
    
    return $db->fetchAll($sql, [$turmaId, $aulaId]);
}

/**
 * Buscar presenças de um aluno em uma turma
 * CORRIGIDO: Usa aula_id e turma_aulas_agendadas (tabela correta)
 */
function buscarPresencasAluno($db, $alunoId, $turmaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.aula_id,
            tp.aluno_id,
            tp.presente,
            tp.justificativa,
            tp.registrado_em,
            taa.nome_aula,
            taa.data_aula,
            taa.ordem_global as ordem
        FROM turma_presencas tp
        JOIN turma_aulas_agendadas taa ON tp.aula_id = taa.id
        WHERE tp.aluno_id = ? AND tp.turma_id = ?
        ORDER BY taa.ordem_global ASC
    ";
    
    return $db->fetchAll($sql, [$alunoId, $turmaId]);
}

/**
 * Buscar todas as presenças de uma turma
 * CORRIGIDO: Usa aula_id, justificativa e turma_aulas_agendadas (tabela correta)
 */
function buscarPresencasTurma($db, $turmaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.aula_id,
            tp.aluno_id,
            tp.presente,
            tp.justificativa,
            tp.registrado_em,
            a.nome as aluno_nome,
            taa.nome_aula,
            taa.data_aula,
            taa.ordem_global as ordem
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas_agendadas taa ON tp.aula_id = taa.id
        WHERE tp.turma_id = ?
        ORDER BY taa.ordem_global ASC, a.nome ASC
    ";
    
    return $db->fetchAll($sql, [$turmaId]);
}

/**
 * Listar presenças com filtros
 * CORRIGIDO: Usa aula_id, justificativa, turma_aulas_agendadas e turmas_teoricas (tabelas corretas)
 */
function listarPresencas($db) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.aula_id,
            tp.aluno_id,
            tp.presente,
            tp.justificativa,
            tp.registrado_em,
            a.nome as aluno_nome,
            taa.nome_aula,
            tt.nome as turma_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas_agendadas taa ON tp.aula_id = taa.id
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
    // Buscar dados da turma
    $turma = $db->fetch(
        "SELECT status, instrutor_id FROM turmas_teoricas WHERE id = ?",
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
    
    // Regra 2: Instrutor só pode editar se for instrutor da turma
    if ($isInstrutor && !$isAdmin && !$isSecretaria) {
        if ($turma['instrutor_id'] != $userId) {
            return [
                'permitido' => false,
                'motivo' => 'Você não é o instrutor desta turma'
            ];
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
    $aulaId = $dados['aula_id'] ?? $dados['turma_aula_id'] ?? null;
    if (!$aulaId) {
        return [
            'success' => false,
            'message' => 'ID da aula é obrigatório (aula_id ou turma_aula_id)'
        ];
    }
    
    // Validar regras de edição
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $dados['turma_id'], $aulaId, $userId, $isAdmin, $isSecretaria, $isInstrutor);
    if (!$validacaoEdicao['permitido']) {
        return [
            'success' => false,
            'message' => $validacaoEdicao['motivo']
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
    
    // Verificar se já existe presença para esta aula (usando aula_id, nome correto do campo)
    $presencaExistente = $db->fetch(
        "SELECT id FROM turma_presencas WHERE turma_id = ? AND aula_id = ? AND aluno_id = ?",
        [$dados['turma_id'], $aulaId, $dados['aluno_id']]
    );
    
    if ($presencaExistente) {
        return [
            'success' => false,
            'message' => 'Presença já registrada para este aluno nesta aula'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Normalizar nome do campo: aceitar tanto aula_id quanto turma_aula_id (compatibilidade)
        $aulaId = $dados['aula_id'] ?? $dados['turma_aula_id'] ?? null;
        if (!$aulaId) {
            throw new Exception('ID da aula é obrigatório');
        }
        
        // Inserir presença (usando aula_id e justificativa, nomes corretos dos campos)
        $presencaId = $db->insert('turma_presencas', [
            'turma_id' => $dados['turma_id'],
            'aula_id' => $aulaId,
            'aluno_id' => $dados['aluno_id'],
            'presente' => $dados['presente'] ? 1 : 0,
            'justificativa' => $dados['justificativa'] ?? $dados['observacao'] ?? null, // Compatibilidade: aceita ambos
            'registrado_por' => $userId
        ]);
        
        // Log de auditoria
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
    $aulaId = $dados['aula_id'] ?? $dados['turma_aula_id'] ?? null;
    if (!$aulaId) {
        return [
            'success' => false,
            'message' => 'ID da aula é obrigatório (aula_id ou turma_aula_id)'
        ];
    }
    
    // Validar regras de edição uma vez para o lote
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $turmaId, $aulaId, $userId, $isAdmin, $isSecretaria, $isInstrutor);
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
            $presenca['aula_id'] = $aulaId; // Normalizar para aula_id
            
            $validacao = validarDadosPresenca($presenca);
            if (!$validacao['success']) {
                $erros[] = "Presença " . ($index + 1) . ": " . $validacao['message'];
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
            
            // Verificar duplicidade (usando aula_id, nome correto do campo)
            $presencaExistente = $db->fetch(
                "SELECT id FROM turma_presencas WHERE turma_id = ? AND aula_id = ? AND aluno_id = ?",
                [$turmaId, $aulaId, $presenca['aluno_id']]
            );
            
            if ($presencaExistente) {
                $erros[] = "Presença " . ($index + 1) . ": Já registrada";
                continue;
            }
            
            // Inserir presença (usando aula_id e justificativa, nomes corretos)
            $presencaId = $db->insert('turma_presencas', [
                'turma_id' => $turmaId,
                'aula_id' => $aulaId,
                'aluno_id' => $presenca['aluno_id'],
                'presente' => $presenca['presente'] ? 1 : 0,
                'justificativa' => $presenca['justificativa'] ?? $presenca['observacao'] ?? null, // Compatibilidade
                'registrado_por' => $userId
            ]);
            
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
            'aula_id' => $aulaId,
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
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $presenca['turma_id'], $presenca['aula_id'], $userId, $isAdmin, $isSecretaria, $isInstrutor);
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
        
        // Atualizar presença (usando justificativa, nome correto do campo)
        $db->update('turma_presencas', [
            'presente' => $dados['presente'] ? 1 : 0,
            'justificativa' => $dados['justificativa'] ?? $dados['observacao'] ?? null // Compatibilidade
        ], 'id = ?', [$presencaId]);
        
        // Log de auditoria
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
    $validacaoEdicao = validarRegrasEdicaoPresenca($db, $presenca['turma_id'], $presenca['aula_id'], $userId, $isAdmin, $isSecretaria, $isInstrutor);
    if (!$validacaoEdicao['permitido']) {
        return [
            'success' => false,
            'message' => $validacaoEdicao['motivo']
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Excluir presença
        $db->delete('turma_presencas', 'id = ?', [$presencaId]);
        
        // Log de auditoria
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
 */
function validarDadosPresenca($dados, $presencaId = null) {
    $erros = [];
    
    // Campos obrigatórios
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
 * Log de auditoria
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
?>
