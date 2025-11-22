<?php
/**
 * Logout - Encerrar sessão do usuário
 */

// Iniciar output buffering para evitar problemas com headers
ob_start();

// Incluir arquivos necessários ANTES de qualquer output
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Verificar se a sessão está ativa antes de tentar acessá-la
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Headers para evitar cache (enviar antes de qualquer output)
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Obter informações do usuário antes de fazer logout
$user_info = null;
if (isset($_SESSION['user_id'])) {
    $user_info = getCurrentUser();
}

// Registrar o logout no log do sistema ANTES de fazer logout
// NOTA: O método $auth->logout() já registra o log, mas vamos registrar aqui também
// para ter informações adicionais (user_agent, etc.)
if ($user_info && defined('AUDIT_ENABLED') && AUDIT_ENABLED) {
    try {
        // Usar a função global dbLog() (não é método da classe Database)
        dbLog($user_info['id'], 'logout', 'usuarios', $user_info['id']);
    } catch (Exception $e) {
        // Ignorar erros de log por enquanto
        if (defined('LOG_ENABLED') && LOG_ENABLED) {
            error_log('Erro ao registrar log de logout: ' . $e->getMessage());
        }
    }
}

// Fazer logout usando a instância global do Auth
// NOTA: $auth->logout() já destrói a sessão e remove cookies, então não precisamos fazer isso novamente
global $auth;
if ($auth) {
    $auth->logout();
}

// Limpar output buffer antes de enviar headers de redirecionamento
ob_end_clean();

// Remover cookies de "lembrar-me" se existirem
if (isset($_COOKIE['remember_token'])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    @setcookie('remember_token', '', time() - 3600, '/', '', $is_https, true);
}

// Remover cookie CFC_SESSION se existir
if (isset($_COOKIE['CFC_SESSION'])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    // Tentar remover com diferentes combinações de parâmetros
    @setcookie('CFC_SESSION', '', time() - 42000, '/', '', $is_https, true);
    @setcookie('CFC_SESSION', '', time() - 42000, '/', '.hostingersite.com', $is_https, true);
    @setcookie('CFC_SESSION', '', time() - 42000, '/', 'linen-mantis-198436.hostingersite.com', $is_https, true);
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
