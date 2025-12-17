<?php
/**
 * Página de Solicitação de Recuperação de Senha
 * Sistema CFC Bom Conselho
 */

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';
require_once 'includes/PasswordReset.php';
require_once 'includes/Mailer.php';

// Se já estiver logado, redirecionar
if (isLoggedIn()) {
    $user = getCurrentUser();
    redirectAfterLogin($user);
}

$error = '';
$success = '';
$userType = $_GET['type'] ?? '';
$hasSpecificType = !empty($userType);

// Processar solicitação de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $requestedType = $_POST['user_type'] ?? $userType;
    
    if (empty($login)) {
        $error = 'Por favor, informe seu email ou CPF.';
    } else {
        try {
            // Obter IP do cliente
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            }
            
            // Solicitar reset
            $result = PasswordReset::requestReset($login, $requestedType, $ip);
            
            if ($result['success'] && isset($result['token']) && $result['token']) {
                // Token gerado - enviar email
                $emailTo = $result['user_email'] ?? null;
                
                if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
                    // Tentar enviar email
                    $emailResult = Mailer::sendPasswordResetEmail($emailTo, $result['token'], $requestedType);
                    
                    // Mesmo se email falhar, retornar mensagem neutra (anti-enumeração)
                    $success = $result['message'];
                } else {
                    // Para aluno sem email cadastrado
                    if ($requestedType === 'aluno') {
                        $success = 'Se você não possui email cadastrado, entre em contato com a secretaria para recuperar sua senha.';
                    } else {
                        $success = $result['message'];
                    }
                }
            } else {
                // Sem token (rate limit ou usuário não encontrado) - mensagem neutra
                $success = $result['message'];
            }
            
        } catch (Exception $e) {
            if (LOG_ENABLED) {
                error_log('[FORGOT_PASSWORD] Erro: ' . $e->getMessage());
            }
            $error = 'Erro ao processar solicitação. Tente novamente mais tarde.';
        }
    }
}

// Determinar tipo para exibição
$displayType = $hasSpecificType ? $userType : 'admin';

// Configurações por tipo (reutilizar do login.php)
$userTypes = [
    'admin' => ['title' => 'Administrador', 'field_label' => 'E-mail', 'field_type' => 'email', 'placeholder' => 'admin@cfc.com'],
    'secretaria' => ['title' => 'Secretaria', 'field_label' => 'E-mail', 'field_type' => 'email', 'placeholder' => 'atendente@cfc.com'],
    'instrutor' => ['title' => 'Instrutor', 'field_label' => 'E-mail', 'field_type' => 'email', 'placeholder' => 'instrutor@cfc.com'],
    'aluno' => ['title' => 'Aluno', 'field_label' => 'CPF', 'field_type' => 'text', 'placeholder' => '000.000.000-00']
];

$currentConfig = $userTypes[$displayType] ?? $userTypes['admin'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha | Sistema CFC</title>
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
            max-width: 900px;
            min-height: 500px;
            display: flex;
        }
        
        .left-panel {
            background: #1A365D;
            color: white;
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .logo-image {
            width: 120px;
            height: 120px;
            margin-bottom: 20px;
            border-radius: 50%;
            object-fit: contain;
        }
        
        .system-subtitle {
            font-size: 16px;
            opacity: 0.9;
            line-height: 1.6;
            text-align: center;
        }
        
        .right-panel {
            flex: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 30px;
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
        }
        
        .btn-submit:hover {
            background: #2d4a6b;
            transform: translateY(-1px);
            box-shadow: 0 5px 15px rgba(26, 54, 93, 0.3);
        }
        
        .btn-submit:active {
            transform: translateY(0);
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
        
        .alert-info {
            background: #e8f4f8;
            color: #2c3e50;
            border-left: 4px solid #1A365D;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 100%;
            }
            
            .left-panel {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="left-panel">
            <div class="logo-section">
                <img src="assets/logo.png" alt="Logo CFC" class="logo-image">
                <p class="system-subtitle">Sistema completo para gestão de Centros de Formação de Condutores</p>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="login-header">
                <h2 class="login-title">Recuperar Senha</h2>
                <p class="login-subtitle">Informe seus dados para receber instruções de recuperação</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="POST">
                    <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($displayType); ?>">
                    
                    <?php if ($displayType === 'aluno'): ?>
                        <div class="alert-info">
                            <strong>⚠️ Para Alunos:</strong> Se você não possui email cadastrado no sistema, entre em contato com a secretaria para recuperar sua senha.
                        </div>
                    <?php endif; ?>
                    
                    <div class="form-group">
                        <label for="login" class="form-label">
                            <?php echo $displayType === 'aluno' ? 'CPF ou E-mail' : 'E-mail'; ?>
                            <span style="color: #d63031;">*</span>
                        </label>
                        <input 
                            type="<?php echo $displayType === 'aluno' ? 'text' : 'email'; ?>" 
                            id="login" 
                            name="login" 
                            class="form-control" 
                            placeholder="<?php echo htmlspecialchars($currentConfig['placeholder']); ?>" 
                            required
                            autofocus
                        >
                        <div class="form-help">
                            <?php if ($displayType === 'aluno'): ?>
                                Digite seu CPF cadastrado ou e-mail (se tiver cadastrado)
                            <?php else: ?>
                                Digite seu endereço de e-mail cadastrado no sistema
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Enviar Instruções
                    </button>
                </form>
            <?php endif; ?>
            
            <a href="login.php<?php echo $hasSpecificType ? '?type=' . htmlspecialchars($userType) : ''; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o login
            </a>
        </div>
    </div>
</body>
</html>
