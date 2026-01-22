<?php
/**
 * API de Diário de Classe
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.4: Diário de Classe
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
        // Buscar diário de uma aula específica
        $diario = buscarDiarioAula($db, $_GET['turma_id'], $_GET['aula_id']);
        echo json_encode([
            'success' => true,
            'data' => $diario
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif (isset($_GET['turma_id'])) {
        // Buscar todos os diários de uma turma
        $diarios = buscarDiariosTurma($db, $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $diarios
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Listar diários com filtros
        $diarios = listarDiarios($db);
        echo json_encode([
            'success' => true,
            'data' => $diarios
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
    
    $resultado = criarDiario($db, $input, $userId);
    
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
    $diarioId = $_GET['id'] ?? null;
    
    if (!$diarioId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do diário é obrigatório'
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
    
    $resultado = atualizarDiario($db, $diarioId, $input, $userId);
    
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
    $diarioId = $_GET['id'] ?? null;
    
    if (!$diarioId) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'ID do diário é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $resultado = excluirDiario($db, $diarioId);
    
    if ($resultado['success']) {
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Buscar diário de uma aula específica
 */
function buscarDiarioAula($db, $turmaId, $aulaId) {
    $diario = $db->fetch("
        SELECT 
            td.*,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem,
            ta.turma_id,
            u.nome as criado_por_nome,
            u2.nome as atualizado_por_nome
        FROM turma_diario td
        JOIN turma_aulas ta ON td.turma_aula_id = ta.id
        LEFT JOIN usuarios u ON td.criado_por = u.id
        LEFT JOIN usuarios u2 ON td.atualizado_por = u2.id
        WHERE ta.turma_id = ? AND td.turma_aula_id = ?
    ", [$turmaId, $aulaId]);
    
    if ($diario) {
        // Decodificar anexos se existirem
        if ($diario['anexos']) {
            $diario['anexos'] = json_decode($diario['anexos'], true);
        } else {
            $diario['anexos'] = [];
        }
    }
    
    return $diario;
}

/**
 * Buscar todos os diários de uma turma
 */
function buscarDiariosTurma($db, $turmaId) {
    $diarios = $db->fetchAll("
        SELECT 
            td.*,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem,
            ta.turma_id,
            u.nome as criado_por_nome,
            u2.nome as atualizado_por_nome
        FROM turma_diario td
        JOIN turma_aulas ta ON td.turma_aula_id = ta.id
        LEFT JOIN usuarios u ON td.criado_por = u.id
        LEFT JOIN usuarios u2 ON td.atualizado_por = u2.id
        WHERE ta.turma_id = ?
        ORDER BY ta.ordem ASC
    ", [$turmaId]);
    
    // Decodificar anexos para cada diário
    foreach ($diarios as &$diario) {
        if ($diario['anexos']) {
            $diario['anexos'] = json_decode($diario['anexos'], true);
        } else {
            $diario['anexos'] = [];
        }
    }
    
    return $diarios;
}

/**
 * Listar diários com filtros
 */
function listarDiarios($db) {
    $sql = "
        SELECT 
            td.*,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem,
            ta.turma_id,
            t.nome as turma_nome,
            u.nome as criado_por_nome
        FROM turma_diario td
        JOIN turma_aulas ta ON td.turma_aula_id = ta.id
        JOIN turmas t ON ta.turma_id = t.id
        LEFT JOIN usuarios u ON td.criado_por = u.id
        ORDER BY td.criado_em DESC
        LIMIT 50
    ";
    
    $diarios = $db->fetchAll($sql);
    
    // Decodificar anexos para cada diário
    foreach ($diarios as &$diario) {
        if ($diario['anexos']) {
            $diario['anexos'] = json_decode($diario['anexos'], true);
        } else {
            $diario['anexos'] = [];
        }
    }
    
    return $diarios;
}

/**
 * Criar novo diário
 */
function criarDiario($db, $dados, $userId) {
    // Validar dados obrigatórios
    $validacao = validarDadosDiario($dados);
    if (!$validacao['success']) {
        return $validacao;
    }
    
    // Verificar se já existe diário para esta aula
    $diarioExistente = $db->fetch(
        "SELECT id FROM turma_diario WHERE turma_aula_id = ?",
        [$dados['turma_aula_id']]
    );
    
    if ($diarioExistente) {
        return [
            'success' => false,
            'message' => 'Diário já existe para esta aula'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Preparar anexos
        $anexos = [];
        if (isset($dados['anexos']) && is_array($dados['anexos'])) {
            $anexos = $dados['anexos'];
        }
        
        // Inserir diário
        $diarioId = $db->insert('turma_diario', [
            'turma_aula_id' => $dados['turma_aula_id'],
            'conteudo_ministrado' => $dados['conteudo_ministrado'],
            'anexos' => json_encode($anexos),
            'observacoes' => $dados['observacoes'] ?? null,
            'criado_por' => $userId
        ]);
        
        // Log de auditoria
        logAuditoria($db, $userId, 'diario_criado', $diarioId, $dados);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Diário criado com sucesso',
            'diario_id' => $diarioId
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao criar diário: ' . $e->getMessage()
        ];
    }
}

/**
 * Atualizar diário existente
 */
function atualizarDiario($db, $diarioId, $dados, $userId) {
    // Verificar se diário existe
    $diario = $db->fetch("SELECT * FROM turma_diario WHERE id = ?", [$diarioId]);
    if (!$diario) {
        return [
            'success' => false,
            'message' => 'Diário não encontrado'
        ];
    }
    
    // Validar dados
    $validacao = validarDadosDiario($dados, $diarioId);
    if (!$validacao['success']) {
        return $validacao;
    }
    
    try {
        $db->beginTransaction();
        
        // Preparar anexos
        $anexos = $diario['anexos']; // Manter anexos existentes se não especificado
        if (isset($dados['anexos']) && is_array($dados['anexos'])) {
            $anexos = $dados['anexos'];
        }
        
        // Atualizar diário
        $db->update('turma_diario', [
            'conteudo_ministrado' => $dados['conteudo_ministrado'],
            'anexos' => json_encode($anexos),
            'observacoes' => $dados['observacoes'] ?? null,
            'atualizado_por' => $userId,
            'atualizado_em' => date('Y-m-d H:i:s')
        ], 'id = ?', [$diarioId]);
        
        // Log de auditoria
        logAuditoria($db, $userId, 'diario_atualizado', $diarioId, [
            'dados_anteriores' => $diario,
            'dados_novos' => $dados
        ]);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Diário atualizado com sucesso'
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao atualizar diário: ' . $e->getMessage()
        ];
    }
}

/**
 * Excluir diário
 */
function excluirDiario($db, $diarioId) {
    // Verificar se diário existe
    $diario = $db->fetch("SELECT * FROM turma_diario WHERE id = ?", [$diarioId]);
    if (!$diario) {
        return [
            'success' => false,
            'message' => 'Diário não encontrado'
        ];
    }
    
    try {
        $db->beginTransaction();
        
        // Excluir diário
        $db->delete('turma_diario', 'id = ?', [$diarioId]);
        
        // Log de auditoria
        logAuditoria($db, $_SESSION['user_id'] ?? 1, 'diario_excluido', $diarioId, $diario);
        
        $db->commit();
        
        return [
            'success' => true,
            'message' => 'Diário excluído com sucesso'
        ];
        
    } catch (Exception $e) {
        $db->rollback();
        return [
            'success' => false,
            'message' => 'Erro ao excluir diário: ' . $e->getMessage()
        ];
    }
}

/**
 * Validar dados do diário
 */
function validarDadosDiario($dados, $diarioId = null) {
    $erros = [];
    
    // Campos obrigatórios
    if (empty($dados['turma_aula_id'])) {
        $erros[] = 'ID da aula é obrigatório';
    }
    
    if (empty($dados['conteudo_ministrado'])) {
        $erros[] = 'Conteúdo ministrado é obrigatório';
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
function logAuditoria($db, $userId, $acao, $diarioId, $dados) {
    try {
        $db->insert('logs', [
            'usuario_id' => $userId,
            'acao' => $acao,
            'tabela_afetada' => 'turma_diario',
            'registro_id' => $diarioId,
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
