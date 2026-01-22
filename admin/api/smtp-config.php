<?php
/**
 * API para Configurações SMTP
 * Permite salvar e testar configurações SMTP do painel admin
 * 
 * IMPORTANTE: Este endpoint SEMPRE retorna JSON, mesmo em caso de erro
 */

// Função para garantir resposta JSON em caso de erro fatal
function sendJsonError($message, $code = 500) {
    ob_end_clean();
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
}

// Registrar handler de erros para converter qualquer saída em JSON
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        sendJsonError('Erro interno do servidor: ' . $error['message'], 500);
    }
});

// Iniciar output buffering o mais cedo possível
ob_start();

// Desabilitar exibição de erros (mas manter logging)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Definir headers JSON ANTES de qualquer outra coisa
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Limpar qualquer saída capturada
ob_clean();

try {
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    require_once '../../includes/auth.php';
    require_once '../../includes/SMTPConfigService.php';
    
    // Limpar buffer novamente após includes (caso algum include tenha emitido output)
    ob_clean();
    
} catch (Exception $e) {
    // Se houver erro ao carregar includes, limpar buffer e retornar JSON de erro
    ob_end_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar dependências: ' . $e->getMessage()
    ]);
    exit;
}

// Verificar autenticação (SEM redirect - retornar JSON)
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    sendJsonError('Não autorizado. Faça login novamente.', 401);
}

// Verificar se é admin (somente admin pode configurar SMTP)
if (!function_exists('getCurrentUser')) {
    sendJsonError('Erro ao verificar permissões.', 500);
}

$currentUser = getCurrentUser();
if (!$currentUser || ($currentUser['tipo'] ?? '') !== 'admin') {
    sendJsonError('Acesso negado - Apenas administradores podem configurar SMTP', 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$userId = $currentUser['id'];

// Garantir que não há saída antes do JSON
ob_clean();

try {
    switch ($method) {
        case 'GET':
            // Obter configurações atuais (sem senha)
            try {
                $config = SMTPConfigService::getConfig();
                $status = SMTPConfigService::getStatus();
                
                if ($config) {
                    // Não expor senha
                    unset($config['pass']);
                }
                
                ob_end_clean();
                echo json_encode([
                    'success' => true,
                    'config' => $config,
                    'status' => $status
                ]);
                exit;
            } catch (Exception $e) {
                sendJsonError('Erro ao obter configurações: ' . $e->getMessage(), 500);
            }
            break;
            
        case 'POST':
            // Tentar ler JSON primeiro (padrão do frontend)
            $rawInput = file_get_contents('php://input');
            $data = json_decode($rawInput, true);
            
            // Se não houver JSON, tentar $_POST (fallback)
            if (empty($data) && !empty($_POST)) {
                $data = $_POST;
            }
            
            // Validar que temos dados
            if (empty($data)) {
                sendJsonError('Nenhum dado recebido', 400);
            }
            
            // Extrair ação do JSON ou $_POST com fallback seguro
            $action = ($data['action'] ?? '') ?: ($_GET['action'] ?? '');
            
            if ($action === 'save') {
                // Validar dados básicos antes de salvar (com fallback para evitar notices)
                $host = trim($data['host'] ?? '');
                $port = isset($data['port']) ? (int)$data['port'] : 0;
                $user = trim($data['user'] ?? '');
                
                if (empty($host) || empty($port) || empty($user)) {
                    ob_end_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'Campos obrigatórios: Host, Porta e E-mail/Usuário'
                    ]);
                    exit;
                }
                
                // Salvar configurações
                $result = SMTPConfigService::saveConfig($data, $userId);
                ob_end_clean();
                echo json_encode($result);
                exit;
                
            } elseif ($action === 'test') {
                // Testar configurações
                $testEmail = ($data['test_email'] ?? '') ?: ($currentUser['email'] ?? '');
                
                if (empty($testEmail)) {
                    ob_end_clean();
                    echo json_encode([
                        'success' => false,
                        'message' => 'E-mail de teste não fornecido'
                    ]);
                    exit;
                }
                
                $result = SMTPConfigService::testConfig($testEmail, $userId);
                ob_end_clean();
                echo json_encode($result);
                exit;
                
            } else {
                sendJsonError('Ação não reconhecida: ' . ($action ?: '(vazia)'), 400);
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido']);
            break;
    }
    
    // Se chegou aqui, algo deu errado (não deveria)
    sendJsonError('Erro inesperado no processamento da requisição', 500);
    
} catch (Throwable $e) {
    // Captura qualquer erro, incluindo Error (PHP 7+)
    ob_end_clean();
    
    // Log detalhado do erro
    $errorMessage = $e->getMessage();
    $errorFile = $e->getFile();
    $errorLine = $e->getLine();
    $errorTrace = $e->getTraceAsString();
    
    if (defined('LOG_ENABLED') && LOG_ENABLED) {
        error_log(sprintf(
            '[SMTP_CONFIG_API] Erro: %s | Arquivo: %s:%d | Trace: %s',
            $errorMessage,
            $errorFile,
            $errorLine,
            $errorTrace
        ));
    }
    
    // SEMPRE retornar JSON, mesmo em erro
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao processar requisição: ' . $errorMessage
    ]);
    exit;
}
