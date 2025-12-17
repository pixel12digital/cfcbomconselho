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
$maskedDestination = null;
$hasEmail = false;
$rateLimited = false;
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
            
            // Capturar informações para feedback melhorado
            $rateLimited = $result['rate_limited'] ?? false;
            $maskedDestination = $result['masked_destination'] ?? null;
            $hasEmail = $result['has_email'] ?? false;
            
            if ($result['success'] && isset($result['token']) && $result['token']) {
                // Token gerado - enviar email
                $emailTo = $result['user_email'] ?? null;
                
                if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
                    // Tentar enviar email
                    $emailResult = Mailer::sendPasswordResetEmail($emailTo, $result['token'], $requestedType);
                    
                    // Mesmo se email falhar, retornar mensagem neutra (anti-enumeração)
                    $success = $result['message'];
                } else {
                    // Para aluno sem email cadastrado - mensagem neutra
                    $success = $result['message'];
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
        
        .alert-info ul {
            list-style-type: disc;
        }
        
        .alert-info li {
            margin: 5px 0;
        }
        
        .btn-submit:disabled {
            background: #95a5a6;
            cursor: not-allowed;
            opacity: 0.6;
        }
        
        .cooldown-timer {
            margin-top: 10px;
            font-size: 13px;
            color: #7f8c8d;
            font-style: italic;
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
    <script>
        // Desabilitar botão após clique e mostrar cooldown
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const submitBtn = document.getElementById('submitBtn');
            const cooldownTimerEl = document.getElementById('cooldownTimer');
            
            if (form && submitBtn) {
                form.addEventListener('submit', function(e) {
                    // Desabilitar botão imediatamente
                    submitBtn.disabled = true;
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
                    
                    // Mostrar mensagem de processamento
                    if (cooldownTimerEl) {
                        cooldownTimerEl.style.display = 'block';
                        cooldownTimerEl.textContent = 'Processando solicitação...';
                    }
                    
                    // Permitir reenvio após 3 segundos (evitar múltiplos cliques acidentais)
                    // Nota: Se o formulário for enviado com sucesso, a página será recarregada
                    setTimeout(function() {
                        // Só reabilitar se ainda estiver na mesma página (não houve redirecionamento)
                        if (submitBtn && submitBtn.disabled) {
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Enviar Instruções';
                            
                            if (cooldownTimerEl) {
                                cooldownTimerEl.style.display = 'none';
                            }
                        }
                    }, 3000);
                });
            }
            
            // Se já submetido e há rate limit, mostrar timer de 5 minutos
            <?php if ($rateLimited): ?>
            let cooldownSeconds = 300; // 5 minutos
            if (cooldownTimerEl) {
                cooldownTimerEl.style.display = 'block';
                const interval = setInterval(function() {
                    const minutes = Math.floor(cooldownSeconds / 60);
                    const seconds = cooldownSeconds % 60;
                    cooldownTimerEl.textContent = 'Você pode solicitar novamente em ' + minutes + ':' + (seconds < 10 ? '0' : '') + seconds;
                    
                    if (cooldownSeconds <= 0) {
                        clearInterval(interval);
                        cooldownTimerEl.style.display = 'none';
                    }
                    cooldownSeconds--;
                }, 1000);
            }
            <?php endif; ?>
        });
    </script>
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
                    <p style="margin-bottom: 10px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </p>
                    
                    <?php if ($maskedDestination): ?>
                        <p style="margin: 10px 0; font-weight: 600; color: #155724;">
                            <i class="fas fa-envelope"></i> Instruções serão enviadas para: <strong><?php echo htmlspecialchars($maskedDestination); ?></strong>
                        </p>
                    <?php endif; ?>
                    
                    <?php if ($rateLimited): ?>
                        <p style="margin: 10px 0; font-size: 13px; color: #856404;">
                            <i class="fas fa-clock"></i> Você pode solicitar novamente em alguns minutos.
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="alert-info" style="margin-top: 20px;">
                    <strong><i class="fas fa-question-circle"></i> Não recebeu?</strong><br>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <li>Verifique se digitou corretamente o <?php echo $displayType === 'aluno' ? 'CPF ou e-mail' : 'e-mail'; ?>.</li>
                        <li>Confira sua caixa de entrada, spam ou lixeira.</li>
                        <?php if ($displayType === 'aluno'): ?>
                        <li>Se você não tiver e-mail cadastrado, entre em contato com a Secretaria para atualizar seu cadastro e redefinir a senha.</li>
                        <?php else: ?>
                        <li>Se não receber em alguns minutos, verifique o e-mail informado ou entre em contato com o suporte.</li>
                        <?php endif; ?>
                    </ul>
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
                    
                    <button type="submit" id="submitBtn" class="btn-submit">
                        <i class="fas fa-paper-plane"></i> Enviar Instruções
                    </button>
                    <div id="cooldownTimer" class="cooldown-timer" style="display: none;"></div>
                </form>
            <?php endif; ?>
            
            <a href="login.php<?php echo $hasSpecificType ? '?type=' . htmlspecialchars($userType) : ''; ?>" class="back-link">
                <i class="fas fa-arrow-left"></i> Voltar para o login
            </a>
        </div>
    </div>
</body>
</html>
