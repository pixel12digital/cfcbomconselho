<?php
/**
 * API para Configurações SMTP
 * Permite salvar e testar configurações SMTP do painel admin
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';
require_once '../../includes/SMTPConfigService.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado', 'code' => 'NOT_LOGGED_IN']);
    exit;
}

// Verificar se é admin (somente admin pode configurar SMTP)
$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['tipo'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado - Apenas administradores podem configurar SMTP', 'code' => 'NOT_AUTHORIZED']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $currentUser['id'];

try {
    switch ($method) {
        case 'GET':
            // Obter configurações atuais (sem senha)
            $config = SMTPConfigService::getConfig();
            $status = SMTPConfigService::getStatus();
            
            if ($config) {
                // Não expor senha
                unset($config['pass']);
                
                echo json_encode([
                    'success' => true,
                    'config' => $config,
                    'status' => $status
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'config' => null,
                    'status' => $status
                ]);
            }
            break;
            
        case 'POST':
            $action = $_POST['action'] ?? $_GET['action'] ?? '';
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (empty($data) && !empty($_POST)) {
                $data = $_POST;
            }
            
            if ($action === 'save') {
                // Salvar configurações
                $result = SMTPConfigService::saveConfig($data, $userId);
                echo json_encode($result);
                
            } elseif ($action === 'test') {
                // Testar configurações
                $testEmail = $data['test_email'] ?? $currentUser['email'];
                
                if (empty($testEmail)) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'E-mail de teste não fornecido'
                    ]);
                    break;
                }
                
                $result = SMTPConfigService::testConfig($testEmail, $userId);
                echo json_encode($result);
                
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Ação não reconhecida'
                ]);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    if (LOG_ENABLED) {
        error_log('[SMTP_CONFIG_API] Erro: ' . $e->getMessage());
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno: ' . $e->getMessage()
    ]);
}
