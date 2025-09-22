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
if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
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
 */
function buscarPresencasAula($db, $turmaId, $aulaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.observacao,
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
 */
function buscarPresencasAluno($db, $alunoId, $turmaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.observacao,
            tp.registrado_em,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem
        FROM turma_presencas tp
        JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
        WHERE tp.aluno_id = ? AND tp.turma_id = ?
        ORDER BY ta.ordem ASC
    ";
    
    return $db->fetchAll($sql, [$alunoId, $turmaId]);
}

/**
 * Buscar todas as presenças de uma turma
 */
function buscarPresencasTurma($db, $turmaId) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.observacao,
            tp.registrado_em,
            a.nome as aluno_nome,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
        WHERE tp.turma_id = ?
        ORDER BY ta.ordem ASC, a.nome ASC
    ";
    
    return $db->fetchAll($sql, [$turmaId]);
}

/**
 * Listar presenças com filtros
 */
function listarPresencas($db) {
    $sql = "
        SELECT 
            tp.id,
            tp.turma_id,
            tp.turma_aula_id,
            tp.aluno_id,
            tp.presente,
            tp.observacao,
            tp.registrado_em,
            a.nome as aluno_nome,
            ta.nome_aula,
            t.nome as turma_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
        JOIN turmas t ON tp.turma_id = t.id
        ORDER BY tp.registrado_em DESC
        LIMIT 100
    ";
    
    return $db->fetchAll($sql);
}

/**
 * Marcar presença individual
 */
function marcarPresencaIndividual($db, $dados, $userId) {
    // Validar dados obrigatórios
    $validacao = validarDadosPresenca($dados);
    if (!$validacao['success']) {
        return $validacao;
    }
    
    // Verificar se aluno está matriculado na turma
    $matricula = $db->fetch(
        "SELECT id FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?",
        [$dados['turma_id'], $dados['aluno_id']]
    );
    
    if (!$matricula) {
        return [
            'success' => false,
            'message' => 'Aluno não está matriculado nesta turma'
        ];
    }
    
    // Verificar se já existe presença para esta aula
    $presencaExistente = $db->fetch(
        "SELECT id FROM turma_presencas WHERE turma_id = ? AND turma_aula_id = ? AND aluno_id = ?",
        [$dados['turma_id'], $dados['turma_aula_id'], $dados['aluno_id']]
    );
    
    if ($presencaExistente) {
        return [
            'success' => false,
            'message' => 'Presença já registrada para este aluno nesta aula'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Inserir presença
        $presencaId = $db->insert('turma_presencas', [
            'turma_id' => $dados['turma_id'],
            'turma_aula_id' => $dados['turma_aula_id'],
            'aluno_id' => $dados['aluno_id'],
            'presente' => $dados['presente'] ? 1 : 0,
            'observacao' => $dados['observacao'] ?? null,
            'registrado_por' => $userId
        ]);
        
        // Log de auditoria
        logAuditoria($db, $userId, 'presenca_criada', $presencaId, $dados);
        
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
    $turmaId = $dados['turma_id'];
    $turmaAulaId = $dados['turma_aula_id'];
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
        
        foreach ($presencas as $index => $presenca) {
            // Validar dados da presença
            $presenca['turma_id'] = $turmaId;
            $presenca['turma_aula_id'] = $turmaAulaId;
            
            $validacao = validarDadosPresenca($presenca);
            if (!$validacao['success']) {
                $erros[] = "Presença " . ($index + 1) . ": " . $validacao['message'];
                continue;
            }
            
            // Verificar se aluno está matriculado
            $matricula = $db->fetch(
                "SELECT id FROM turma_alunos WHERE turma_id = ? AND aluno_id = ?",
                [$turmaId, $presenca['aluno_id']]
            );
            
            if (!$matricula) {
                $erros[] = "Presença " . ($index + 1) . ": Aluno não matriculado";
                continue;
            }
            
            // Verificar duplicidade
            $presencaExistente = $db->fetch(
                "SELECT id FROM turma_presencas WHERE turma_id = ? AND turma_aula_id = ? AND aluno_id = ?",
                [$turmaId, $turmaAulaId, $presenca['aluno_id']]
            );
            
            if ($presencaExistente) {
                $erros[] = "Presença " . ($index + 1) . ": Já registrada";
                continue;
            }
            
            // Inserir presença
            $presencaId = $db->insert('turma_presencas', [
                'turma_id' => $turmaId,
                'turma_aula_id' => $turmaAulaId,
                'aluno_id' => $presenca['aluno_id'],
                'presente' => $presenca['presente'] ? 1 : 0,
                'observacao' => $presenca['observacao'] ?? null,
                'registrado_por' => $userId
            ]);
            
            $sucessos++;
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
    // Verificar se presença existe
    $presenca = $db->fetch("SELECT * FROM turma_presencas WHERE id = ?", [$presencaId]);
    if (!$presenca) {
        return [
            'success' => false,
            'message' => 'Presença não encontrada'
        ];
    }
    
    // Validar dados
    $validacao = validarDadosPresenca($dados, $presencaId);
    if (!$validacao['success']) {
        return $validacao;
    }
    
    try {
        $db->beginTransaction();
        
        // Atualizar presença
        $db->update('turma_presencas', [
            'presente' => $dados['presente'] ? 1 : 0,
            'observacao' => $dados['observacao'] ?? null
        ], 'id = ?', [$presencaId]);
        
        // Log de auditoria
        logAuditoria($db, $userId, 'presenca_atualizada', $presencaId, [
            'dados_anteriores' => $presenca,
            'dados_novos' => $dados
        ]);
        
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
    // Verificar se presença existe
    $presenca = $db->fetch("SELECT * FROM turma_presencas WHERE id = ?", [$presencaId]);
    if (!$presenca) {
        return [
            'success' => false,
            'message' => 'Presença não encontrada'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Excluir presença
        $db->delete('turma_presencas', 'id = ?', [$presencaId]);
        
        // Log de auditoria
        logAuditoria($db, $_SESSION['user_id'] ?? 1, 'presenca_excluida', $presencaId, $presenca);
        
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
 */
function validarDadosPresenca($dados, $presencaId = null) {
    $erros = [];
    
    // Campos obrigatórios
    if (empty($dados['turma_id'])) {
        $erros[] = 'ID da turma é obrigatório';
    }
    
    if (empty($dados['turma_aula_id'])) {
        $erros[] = 'ID da aula é obrigatório';
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
