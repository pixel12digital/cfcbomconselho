<?php
/**
 * Logout - Encerrar sessão do usuário
 */

// Headers para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Iniciar sessão
session_start();

// Incluir arquivos necessários
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Obter informações do usuário antes de fazer logout
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $user_info = getCurrentUser();
}

// Fazer logout usando a instância global do Auth
global $auth;
$result = $auth->logout();

// Registrar o logout no log do sistema
if ($user_info) {
    try {
        $db = Database::getInstance();
        $db->dbLog('auth', 'logout', [
            'user_id' => $user_info['id'],
            'user_email' => $user_info['email'],
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Ignorar erros de log por enquanto
        if (LOG_ENABLED) {
            error_log('Erro ao registrar log de logout: ' . $e->getMessage());
        }
    }
}

// Limpar qualquer output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Garantir que não há mais sessão ativa - destruir completamente
session_unset();
session_destroy();

// Remover todos os cookies de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    // Ajustar parâmetros para produção (HTTPS)
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $is_https, $params["httponly"]
    );
}

// Remover cookies de "lembrar-me" se existirem
if (isset($_COOKIE['remember_token'])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie('remember_token', '', time() - 3600, '/', '', $is_https, true);
}

// Remover cookie CFC_SESSION se existir
if (isset($_COOKIE['CFC_SESSION'])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    setcookie('CFC_SESSION', '', time() - 42000, '/', '', $is_https, true);
}

// Redirecionar para página de login com URL absoluta para evitar problemas
// Detectar ambiente e usar URL apropriada
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    // Ambiente local
    $base_url = 'http://localhost/cfc-bom-conselho';
} else {
    // Ambiente de produção
    $base_url = 'https://linen-mantis-198436.hostingersite.com';
}
header('Location: ' . $base_url . '/index.php?message=logout_success');
exit;
?>
