<?php
/**
 * Página de entrada do PWA para Alunos
 * Retorna 200 OK e redireciona via JavaScript (requisito PWA)
 * Redireciona para login se não estiver autenticado
 * Redireciona para dashboard se estiver autenticado
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Determinar destino do redirect
$redirectUrl = '../login.php?type=aluno';

if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user && $user['tipo'] === 'aluno') {
        // Usuário logado como aluno - ir para dashboard
        $redirectUrl = 'dashboard.php';
    } else {
        // Usuário logado mas não é aluno - fazer logout e ir para login
        if (function_exists('logout')) {
            logout();
        } else {
            session_destroy();
        }
        $redirectUrl = '../login.php?type=aluno';
    }
}

// Retornar 200 OK com redirect via JavaScript (requisito PWA)
http_response_code(200);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redirecionando...</title>
    <script>
        // Redirect imediato via JavaScript (mantém status 200)
        window.location.replace('<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>');
    </script>
    <meta http-equiv="refresh" content="0;url=<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">
</head>
<body>
    <p>Redirecionando...</p>
    <p>Se não for redirecionado automaticamente, <a href="<?php echo htmlspecialchars($redirectUrl, ENT_QUOTES, 'UTF-8'); ?>">clique aqui</a>.</p>
</body>
</html>
