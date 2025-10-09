<?php
/**
 * API de Debug para Exames - Versão Simplificada
 */

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/exames_debug_errors.log');

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Log inicial
error_log("[EXAMES DEBUG] Iniciando...");

try {
    // Incluir arquivos necessários
    $includesPath = __DIR__ . '/../../includes/';
    error_log("[EXAMES DEBUG] Incluindo de: " . $includesPath);
    
    if (!file_exists($includesPath . 'config.php')) {
        throw new Exception("Config.php não encontrado em: " . $includesPath);
    }
    
    require_once $includesPath . 'config.php';
    require_once $includesPath . 'database.php';
    require_once $includesPath . 'auth.php';
    
    error_log("[EXAMES DEBUG] Includes carregados com sucesso");
    
    // Verificar autenticação
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    error_log("[EXAMES DEBUG] Session ID: " . session_id());
    error_log("[EXAMES DEBUG] Session data: " . print_r($_SESSION, true));
    
    if (!isset($_SESSION['user_id']) || !isLoggedIn()) {
        error_log("[EXAMES DEBUG] Usuário não autenticado");
        http_response_code(401);
        echo json_encode(['error' => 'Não autenticado', 'code' => 'UNAUTHORIZED']);
        exit;
    }
    
    $user = getCurrentUser();
    error_log("[EXAMES DEBUG] Usuário: " . print_r($user, true));
    
    $method = $_SERVER['REQUEST_METHOD'];
    error_log("[EXAMES DEBUG] Method: " . $method);
    
    if ($method === 'POST') {
        $action = $_POST['action'] ?? 'test';
        error_log("[EXAMES DEBUG] Action: " . $action);
        error_log("[EXAMES DEBUG] POST data: " . print_r($_POST, true));
        
        if ($action === 'test') {
            echo json_encode([
                'success' => true,
                'message' => 'API funcionando!',
                'user' => $user,
                'session_id' => session_id()
            ]);
        } else {
            echo json_encode([
                'success' => true,
                'message' => 'Action recebida: ' . $action,
                'data' => $_POST
            ]);
        }
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'GET request recebida',
            'method' => $method
        ]);
    }
    
} catch (Exception $e) {
    error_log('[EXAMES DEBUG] Erro: ' . $e->getMessage());
    error_log('[EXAMES DEBUG] File: ' . $e->getFile());
    error_log('[EXAMES DEBUG] Line: ' . $e->getLine());
    
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro interno: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
