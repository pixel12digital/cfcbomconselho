<?php
/**
 * API para Gerenciamento de Turmas
 * Baseada na análise do sistema eCondutor
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
require_once __DIR__ . '/../includes/turma_manager.php';

// Verificar autenticação
if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Usuário não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$turmaManager = new TurmaManager();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGetRequest($turmaManager);
            break;
            
        case 'POST':
            handlePostRequest($turmaManager);
            break;
            
        case 'PUT':
            handlePutRequest($turmaManager);
            break;
            
        case 'DELETE':
            handleDeleteRequest($turmaManager);
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'sucesso' => false,
                'mensagem' => 'Método não permitido'
            ], JSON_UNESCAPED_UNICODE);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições GET
 */
function handleGetRequest($turmaManager) {
    if (isset($_GET['id'])) {
        // Buscar turma específica
        $resultado = $turmaManager->buscarTurma($_GET['id']);
        
        if ($resultado['sucesso']) {
            echo json_encode([
                'sucesso' => true,
                'dados' => $resultado['dados']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            http_response_code(404);
            echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        }
        
    } elseif (isset($_GET['estatisticas'])) {
        // Obter estatísticas
        $cfcId = $_GET['cfc_id'] ?? null;
        $resultado = $turmaManager->obterEstatisticas($cfcId);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
        
    } else {
        // Listar turmas com filtros
        $filtros = [
            'busca' => $_GET['busca'] ?? '',
            'data_inicio' => $_GET['data_inicio'] ?? '',
            'data_fim' => $_GET['data_fim'] ?? '',
            'status' => $_GET['status'] ?? '',
            'tipo_aula' => $_GET['tipo_aula'] ?? '',
            'cfc_id' => $_GET['cfc_id'] ?? $_SESSION['cfc_id'] ?? null,
            'limite' => (int)($_GET['limite'] ?? 10),
            'pagina' => (int)($_GET['pagina'] ?? 0)
        ];
        
        $resultado = $turmaManager->listarTurmas($filtros);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Manipular requisições POST
 */
function handlePostRequest($turmaManager) {
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'JSON inválido: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    // Adicionar CFC do usuário logado
    $dados['cfc_id'] = $_SESSION['cfc_id'] ?? 1;
    
    $resultado = $turmaManager->criarTurma($dados);
    
    if ($resultado['sucesso']) {
        http_response_code(201);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Manipular requisições PUT
 */
function handlePutRequest($turmaManager) {
    $turmaId = $_GET['id'] ?? null;
    
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $dados = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'JSON inválido: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $resultado = $turmaManager->atualizarTurma($turmaId, $dados);
    
    if ($resultado['sucesso']) {
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Manipular requisições DELETE
 */
function handleDeleteRequest($turmaManager) {
    $turmaId = $_GET['id'] ?? null;
    
    if (!$turmaId) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'ID da turma é obrigatório'
        ], JSON_UNESCAPED_UNICODE);
        return;
    }
    
    $resultado = $turmaManager->excluirTurma($turmaId);
    
    if ($resultado['sucesso']) {
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    } else {
        http_response_code(400);
        echo json_encode($resultado, JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Função auxiliar para retornar resposta JSON segura
 */
function returnJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Função auxiliar para validar dados de entrada
 */
function validateInput($data, $requiredFields = []) {
    $errors = [];
    
    foreach ($requiredFields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            $errors[] = "Campo '{$field}' é obrigatório";
        }
    }
    
    return $errors;
}

/**
 * Função auxiliar para sanitizar dados
 */
function sanitizeData($data) {
    $sanitized = [];
    
    foreach ($data as $key => $value) {
        if (is_string($value)) {
            $sanitized[$key] = htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        } else {
            $sanitized[$key] = $value;
        }
    }
    
    return $sanitized;
}

/**
 * Função auxiliar para log de operações
 */
function logOperation($operation, $turmaId, $userId, $details = []) {
    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'operation' => $operation,
        'turma_id' => $turmaId,
        'user_id' => $userId,
        'details' => $details
    ];
    
    error_log('[Turma API] ' . json_encode($logData));
}

/**
 * Função auxiliar para verificar permissões
 */
function checkPermission($action, $userId, $cfcId) {
    // Implementar lógica de permissões baseada no sistema atual
    // Por enquanto, permitir todas as operações para usuários autenticados
    
    $allowedActions = ['create', 'read', 'update', 'delete'];
    
    if (!in_array($action, $allowedActions)) {
        return false;
    }
    
    // Verificar se usuário pertence ao CFC
    if ($cfcId && $_SESSION['cfc_id'] !== $cfcId) {
        return false;
    }
    
    return true;
}
?>
