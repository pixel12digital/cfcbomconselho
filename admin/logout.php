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
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Remover cookies de "lembrar-me" se existirem
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Remover cookie CFC_SESSION se existir
if (isset($_COOKIE['CFC_SESSION'])) {
    setcookie('CFC_SESSION', '', time() - 42000, '/');
}

// Redirecionar para página de login com URL absoluta para evitar problemas
// Corrigir APP_URL que pode estar incorreto quando executado de dentro de admin/
$base_url = 'http://localhost/cfc-bom-conselho';
header('Location: ' . $base_url . '/index.php?message=logout_success');
exit;
?>
