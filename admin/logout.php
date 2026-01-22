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

// Obter informações do usuário ANTES de fazer logout (importante: depois a sessão será destruída)
$user_info = null;
$user_type = 'admin'; // Tipo padrão
if (isset($_SESSION['user_id'])) {
    $user_info = getCurrentUser();
    // Capturar o tipo do usuário antes de destruir a sessão
    if ($user_info && isset($user_info['tipo'])) {
        $user_type = strtolower($user_info['tipo']);
    }
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
global $auth;
if ($auth) {
    $auth->logout();
}

// Garantir que a sessão foi completamente destruída (dupla verificação)
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = array();
    @session_destroy();
}

// Limpar output buffer antes de enviar headers de redirecionamento
if (ob_get_level() > 0) {
    ob_end_clean();
}

// Remover cookies de "lembrar-me" se existirem (dupla verificação)
if (isset($_COOKIE['remember_token'])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    @setcookie('remember_token', '', time() - 3600, '/', '', $is_https, true);
    unset($_COOKIE['remember_token']);
}

// Remover cookie CFC_SESSION se existir (dupla verificação)
if (isset($_COOKIE['CFC_SESSION'])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $host = $_SERVER['HTTP_HOST'] ?? '';
    // Tentar remover com diferentes combinações de parâmetros
    @setcookie('CFC_SESSION', '', time() - 42000, '/', '', $is_https, true);
    if (strpos($host, 'hostingersite.com') !== false) {
        @setcookie('CFC_SESSION', '', time() - 42000, '/', '.hostingersite.com', $is_https, true);
        @setcookie('CFC_SESSION', '', time() - 42000, '/', $host, $is_https, true);
    }
    unset($_COOKIE['CFC_SESSION']);
}

// Remover outros cookies de sessão que possam existir
$sessionName = session_name();
if (isset($_COOKIE[$sessionName])) {
    $is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    $params = session_get_cookie_params();
    @setcookie($sessionName, '', time() - 42000, $params['path'], $params['domain'], $is_https, $params['httponly']);
    unset($_COOKIE[$sessionName]);
}

// Redirecionar para página de login usando BASE_PATH dinâmico
// CORREÇÃO: Redirecionar para login.php (raiz) com o tipo correto selecionado
// IMPORTANTE: Sempre usar caminho absoluto para garantir que vá para a raiz, não para admin/login.php
$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';

// Mapear tipo do usuário para o parâmetro type do login
// admin e secretaria -> type=admin (mostra opções de admin/secretaria)
// instrutor -> type=instrutor
// aluno -> type=aluno
$loginType = 'admin'; // Padrão
if ($user_type === 'instrutor') {
    $loginType = 'instrutor';
} elseif ($user_type === 'aluno') {
    $loginType = 'aluno';
} else {
    // admin ou secretaria -> type=admin (mostra ambos os tipos no login)
    $loginType = 'admin';
}

// CORREÇÃO CRÍTICA: Calcular o caminho base do projeto a partir do SCRIPT_NAME
// Isso garante que sempre aponte para a raiz, não para admin/
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
// Se estamos em admin/logout.php, remover /admin/logout.php para obter a raiz
$basePath = dirname(dirname($scriptName)); // Volta 2 níveis: de /admin/logout.php para /
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
} elseif ($basePath !== '' && $basePath[0] !== '/') {
    $basePath = '/' . $basePath;
}

// Redirecionar para login.php (raiz) com o tipo correto selecionado
// Usar caminho absoluto: /cfc-bom-conselho/login.php ou /login.php (dependendo do BASE_PATH)
$redirectUrl = $protocol . '://' . $host . $basePath . '/login.php?type=' . $loginType . '&message=logout_success';

// Limpar qualquer output antes de redirecionar
if (ob_get_level() > 0) {
    ob_end_clean();
}

header('Location: ' . $redirectUrl);
exit;
?>
