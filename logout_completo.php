<?php
// =====================================================
// LOGOUT COMPLETO - SISTEMA CFC
// =====================================================

// Iniciar sessão
session_start();

// Limpar todas as variáveis de sessão
$_SESSION = array();

// Destruir a sessão
session_destroy();

// Regenerar ID da sessão
session_regenerate_id(true);

// Limpar cookies de sessão
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Limpar cookies de "lembrar-me"
if (isset($_COOKIE['remember_token'])) {
    setcookie('remember_token', '', time() - 3600, '/');
}

// Limpar outros cookies relacionados
$cookiesToRemove = ['user_id', 'user_tipo', 'user_email', 'last_activity'];
foreach ($cookiesToRemove as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 3600, '/');
    }
}

// Redirecionar para home
header('Location: index.php');
exit;
?>















