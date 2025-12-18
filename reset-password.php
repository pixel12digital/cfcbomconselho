<?php
/**
 * Página de Redefinição de Senha
 * Sistema CFC Bom Conselho
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/PasswordReset.php';

// Log inicial para rastreamento
if (LOG_ENABLED) {
    error_log(sprintf(
        '[RESET_PASSWORD] Página carregada - Method: %s, GET_token: %s, POST_token: %s, POST_new_password: %s',
        $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        !empty($_GET['token']) ? substr($_GET['token'], 0, 16) . '...' : 'vazio',
        !empty($_POST['token']) ? substr($_POST['token'], 0, 16) . '...' : 'vazio',
        !empty($_POST['new_password']) ? 'preenchido' : 'vazio'
    ));
}

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    $user = getCurrentUser();
    redirectAfterLogin($user);
}

$error = '';
$success = '';
$token = $_GET['token'] ?? '';
$tokenValid = false;
$tokenData = null;

// Validar token se fornecido
if (!empty($token)) {
    if (LOG_ENABLED) {
        error_log(sprintf(
            '[RESET_PASSWORD] Validando token GET - token: %s',
            substr($token, 0, 16) . '...'
        ));
    }
    
    $validation = PasswordReset::validateToken($token);
    $tokenValid = $validation['valid'];
    $tokenData = $validation;
    
    if (LOG_ENABLED) {
        error_log(sprintf(
            '[RESET_PASSWORD] Resultado validação token - valid: %s, reason: %s',
            $tokenValid ? 'true' : 'false',
            $validation['reason'] ?? 'N/A'
        ));
    }
    
    if (!$tokenValid) {
        $error = 'Link inválido ou expirado. Solicite uma nova recuperação de senha.';
    }
} else {
    if (LOG_ENABLED) {
        error_log('[RESET_PASSWORD] Token GET vazio');
    }
}

// Processar redefinição de senha
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (LOG_ENABLED) {
        error_log(sprintf(
            '[RESET_PASSWORD] POST recebido - tokenValid: %s, token: %s, has_new_password: %s, has_confirm: %s',
            $tokenValid ? 'true' : 'false',
            !empty($_POST['token']) ? substr($_POST['token'], 0, 16) . '...' : 'vazio',
            !empty($_POST['new_password']) ? 'sim' : 'não',
            !empty($_POST['confirm_password']) ? 'sim' : 'não'
        ));
    }
    
    if (!$tokenValid) {
        if (LOG_ENABLED) {
            error_log('[RESET_PASSWORD] Token inválido no POST - bloqueando processamento');
        }
        $error = 'Link inválido ou expirado. Solicite uma nova recuperação de senha.';
    } else {
        $token = $_POST['token'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($newPassword) || empty($confirmPassword)) {
            $error = 'Por favor, preencha todos os campos.';
        } elseif (strlen($newPassword) < 8) {
            $error = 'A senha deve ter no mínimo 8 caracteres.';
        } elseif ($newPassword !== $confirmPassword) {
            $error = 'As senhas não coincidem.';
        } else {
            // Consumir token e definir nova senha
            if (LOG_ENABLED) {
                error_log('[RESET_PASSWORD] Chamando consumeTokenAndSetPassword');
            }
            
            $result = PasswordReset::consumeTokenAndSetPassword($token, $newPassword);
            
            if (LOG_ENABLED) {
                error_log(sprintf(
                    '[RESET_PASSWORD] Resultado do consumeTokenAndSetPassword - success: %s, message: %s',
                    $result['success'] ? 'true' : 'false',
                    $result['message'] ?? 'N/A'
                ));
            }
            
            if ($result['success']) {
                $success = $result['message'];
                $tokenValid = false; // Marcar como usado para não mostrar formulário
            } else {
                $error = $result['message'];
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha | Sistema CFC</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #F6F8FC;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            overflow: hidden;
            width: 100%;
            max-width: 600px;
            min-height: 500px;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .logo-image {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            border-radius: 50%;
            object-fit: contain;
        }
        
        .login-title {
            font-size: 28px;
            color: #1A365D;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .login-subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            color: #1A365D;
            font-weight: 500;
            font-size: 14px;
        }
        
        .form-control {
            width: 100%;
            padding: 15px;
            border: 2px solid #e1e5e9;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #1A365D;
            background: white;
            box-shadow: 0 0 0 3px rgba(26, 54, 93, 0.1);
        }
        
        .form-help {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .password-strength {
            margin-top: 5px;
            font-size: 12px;
        }
        
        .password-strength.weak {
            color: #d63031;
        }
        
        .password-strength.medium {
            color: #f39c12;
        }
        
        .password-strength.strong {
            color: #2e7d32;
        }
        
        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        
        .alert-error {
            background: #ffe6e6;
            color: #d63031;
            border-left: 4px solid #d63031;
        }
        
        .alert-success {
            background: #e8f5e9;
            color: #2e7d32;
            border-left: 4px solid #2e7d32;
        }
        
        .alert-info {
            background: #e8f4f8;
            color: #2c3e50;
            border-left: 4px solid #1A365D;
        }
        
        .btn-submit {
            width: 100%;
            padding: 15px;
            background: #1A365D;
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }
        
        .btn-submit:hover {
            background: #2d4a6b;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(26, 54, 93, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(0);
        }
        
        .btn-submit:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            transform: none;
        }
        
        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #1A365D;
            text-decoration: none;
            font-size: 14px;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #7f8c8d;
        }
        
        .password-toggle-icon:hover {
            color: #1A365D;
        }
    </style>
    <script>
        function togglePasswordVisibility(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }
        
        function validatePassword() {
            const password = document.getElementById('new_password').value;
            const confirm = document.getElementById('confirm_password').value;
            const submitBtn = document.getElementById('submitBtn');
            const strength = document.getElementById('passwordStrength');
            const matchMsg = document.getElementById('passwordMatch');
            
            // Validar força
            let strengthText = '';
            let strengthClass = '';
            
            if (password.length === 0) {
                strengthText = '';
            } else if (password.length < 8) {
                strengthText = 'Senha muito curta (mínimo 8 caracteres)';
                strengthClass = 'weak';
            } else if (password.length < 12) {
                strengthText = 'Senha média';
                strengthClass = 'medium';
            } else {
                strengthText = 'Senha forte';
                strengthClass = 'strong';
            }
            
            strength.textContent = strengthText;
            strength.className = 'password-strength ' + strengthClass;
            
            // Validar confirmação
            if (confirm.length > 0) {
                if (password !== confirm) {
                    matchMsg.textContent = 'As senhas não coincidem';
                    matchMsg.className = 'form-help';
                    matchMsg.style.color = '#d63031';
                } else {
                    matchMsg.textContent = 'As senhas coincidem';
                    matchMsg.className = 'form-help';
                    matchMsg.style.color = '#2e7d32';
                }
            } else {
                matchMsg.textContent = '';
            }
            
            // Habilitar/desabilitar botão
            const isValid = password.length >= 8 && password === confirm && confirm.length > 0;
            submitBtn.disabled = !isValid;
        }
    </script>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <img src="assets/logo.png" alt="Logo CFC" class="logo-image">
            <h2 class="login-title">Redefinir Senha</h2>
            <p class="login-subtitle">Crie uma nova senha para sua conta</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o login
            </a>
        <?php elseif (empty($token)): ?>
            <div class="alert alert-error">
                Token não fornecido. Verifique o link enviado por email.
            </div>
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o login
            </a>
        <?php elseif (!$tokenValid): ?>
            <div class="alert alert-error">
                Link inválido ou expirado. Solicite uma nova recuperação de senha.
            </div>
            <a href="forgot-password.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Solicitar nova recuperação
            </a>
        <?php else: ?>
            <form method="POST" id="resetForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> Sua nova senha deve ter no mínimo 8 caracteres.
                </div>
                
                <div class="form-group">
                    <label for="new_password" class="form-label">
                        Nova Senha <span style="color: #d63031;">*</span>
                    </label>
                    <div class="password-toggle">
                        <input 
                            type="password" 
                            id="new_password" 
                            name="new_password" 
                            class="form-control" 
                            placeholder="Digite sua nova senha" 
                            required
                            minlength="8"
                            onkeyup="validatePassword()"
                            autofocus
                        >
                        <i class="fas fa-eye password-toggle-icon" id="toggleNewPassword" onclick="togglePasswordVisibility('new_password', 'toggleNewPassword')"></i>
                    </div>
                    <div id="passwordStrength" class="password-strength"></div>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password" class="form-label">
                        Confirmar Nova Senha <span style="color: #d63031;">*</span>
                    </label>
                    <div class="password-toggle">
                        <input 
                            type="password" 
                            id="confirm_password" 
                            name="confirm_password" 
                            class="form-control" 
                            placeholder="Digite a senha novamente" 
                            required
                            minlength="8"
                            onkeyup="validatePassword()"
                        >
                        <i class="fas fa-eye password-toggle-icon" id="toggleConfirmPassword" onclick="togglePasswordVisibility('confirm_password', 'toggleConfirmPassword')"></i>
                    </div>
                    <div id="passwordMatch" class="form-help"></div>
                </div>
                
                <button type="submit" id="submitBtn" class="btn-submit" disabled>
                    <i class="fas fa-key"></i> Redefinir Senha
                </button>
            </form>
            
            <a href="login.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o login
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
