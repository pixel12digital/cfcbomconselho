<?php
session_start();

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/auth.php';

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

// Redirecionar para a página de login com mensagem de sucesso
header('Location: login.php?message=logout_success');
exit;
?>
