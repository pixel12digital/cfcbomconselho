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
$info = ''; // Mensagem informativa adicional
$maskedDestination = null;
$found = null; // null = não verificado, true = encontrado, false = não encontrado
$hasEmail = false;
$rateLimited = false;
$userType = $_GET['type'] ?? '';
$hasSpecificType = !empty($userType);

// Contatos da Secretaria (pode ser movido para config se necessário)
$secretariaContato = [
    'telefone' => '(87) 98145-0308',
    'whatsapp' => '(87) 98145-0308',
    'email' => 'contato@cfcbomconselho.com.br'
];

// Processar solicitação de recuperação
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $requestedType = $_POST['user_type'] ?? $userType;
    
    // Log para debug (remover em produção)
    if (LOG_ENABLED) {
        error_log("[FORGOT_PASSWORD] POST recebido - login: '$login', requestedType: '$requestedType', userType: '$userType'");
    }
    
    if (empty($login)) {
        $error = $requestedType === 'aluno' 
            ? 'Por favor, informe seu CPF.' 
            : 'Por favor, informe seu e-mail.';
    } else {
        try {
            // Obter IP do cliente
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
            }
            
            // Solicitar reset
            $result = PasswordReset::requestReset($login, $requestedType, $ip);
            
            // Capturar informações para feedback
            $rateLimited = $result['rate_limited'] ?? false;
            $found = $result['found'] ?? null;
            $hasEmail = $result['has_email'] ?? false;
            $maskedDestination = $result['masked_destination'] ?? null;
            
            if ($result['success'] && isset($result['token']) && $result['token']) {
                // Token gerado - enviar email
                $emailTo = $result['user_email'] ?? null;
                
                if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
                    // Tentar enviar email
                    $emailResult = Mailer::sendPasswordResetEmail($emailTo, $result['token'], $requestedType);
                    
                    // Verificar resultado do envio
                    if ($emailResult['success']) {
                        // Email enviado com sucesso
                        $success = $result['message'];
                        $maskedDestination = $result['masked_destination'];
                    } else {
                        // Email falhou - logar erro detalhado
                        if (LOG_ENABLED) {
                            error_log(sprintf(
                                '[FORGOT_PASSWORD] Falha ao enviar email de recuperação - Email: %s, Type: %s, Erro: %s',
                                $emailTo,
                                $requestedType,
                                $emailResult['message'] ?? 'Erro desconhecido'
                            ));
                        }
                        
                        // Mostrar mensagem específica se SMTP não configurado
                        if (isset($emailResult['smtp_configured']) && !$emailResult['smtp_configured']) {
                            $error = 'Erro ao enviar email: SMTP não configurado. Entre em contato com a Secretaria.';
                        } else {
                            // Manter mensagem neutra por segurança (não revelar se email existe)
                            // Mas logar erro detalhado para admin investigar
                            $success = $result['message'];
                            $maskedDestination = $result['masked_destination'];
                            
                            if (LOG_ENABLED) {
                                error_log(sprintf(
                                    '[FORGOT_PASSWORD] Email falhou silenciosamente - Token gerado mas não enviado. Email: %s, Type: %s, Erro: %s',
                                    $emailTo,
                                    $requestedType,
                                    $emailResult['message'] ?? 'Erro desconhecido'
                                ));
                            }
                        }
                    }
                }
            } elseif (isset($result['found']) && $result['found'] === false) {
                // Não encontrado
                $error = $result['message'];
            } elseif (isset($result['found']) && $result['found'] === true && !$result['has_email']) {
                // Encontrado mas sem e-mail
                $error = $result['message'];
            } elseif ($rateLimited) {
                // Rate limit
                $error = $result['message'];
            } else {
                // Erro genérico
                $error = $result['message'] ?? 'Erro ao processar solicitação. Tente novamente mais tarde.';
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

// Configurações por tipo
$userTypes = [
    'admin' => [
        'title' => 'Administrador', 
        'field_label' => 'E-mail', 
        'field_type' => 'email', 
        'placeholder' => 'admin@cfc.com',
        'help_text' => 'Digite seu endereço de e-mail cadastrado no sistema'
    ],
    'secretaria' => [
        'title' => 'Secretaria', 
        'field_label' => 'E-mail', 
        'field_type' => 'email', 
        'placeholder' => 'atendente@cfc.com',
        'help_text' => 'Digite seu endereço de e-mail cadastrado no sistema'
    ],
    'instrutor' => [
        'title' => 'Instrutor', 
        'field_label' => 'E-mail', 
        'field_type' => 'email', 
        'placeholder' => 'instrutor@cfc.com',
        'help_text' => 'Digite seu endereço de e-mail cadastrado no sistema'
    ],
    'aluno' => [
        'title' => 'Aluno', 
        'field_label' => 'CPF', 
        'field_type' => 'text', 
        'placeholder' => '000.000.000-00',
        'help_text' => 'Digite seu CPF cadastrado (apenas números ou com formatação)'
    ]
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
            
            // Máscara de CPF para campo de aluno
            <?php if ($displayType === 'aluno'): ?>
            const cpfInput = document.getElementById('login');
            if (cpfInput) {
                cpfInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, ''); // Remove tudo que não é dígito
                    
                    // Aplica máscara
                    if (value.length <= 11) {
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d)/, '$1.$2');
                        value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                        e.target.value = value;
                    }
                });
                
                // Permitir backspace e delete
                cpfInput.addEventListener('keydown', function(e) {
                    if (e.key === 'Backspace' || e.key === 'Delete') {
                        // Permite apagar normalmente
                    }
                });
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
                    <p style="margin-bottom: 10px;">
                        <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                    </p>
                </div>
                
                <!-- Contatos da Secretaria quando há erro -->
                <div class="alert-info">
                    <strong><i class="fas fa-phone"></i> Precisa de ajuda?</strong><br>
                    <p style="margin: 10px 0 0 0;">
                        Entre em contato com a Secretaria:<br>
                        📞 <strong><?php echo htmlspecialchars($secretariaContato['telefone']); ?></strong><br>
                        💬 WhatsApp: <strong><?php echo htmlspecialchars($secretariaContato['whatsapp']); ?></strong><br>
                        📧 <a href="mailto:<?php echo htmlspecialchars($secretariaContato['email']); ?>" style="color: #1A365D;"><?php echo htmlspecialchars($secretariaContato['email']); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <p style="margin-bottom: 10px;">
                        <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                    </p>
                    
                    <?php if ($maskedDestination): ?>
                        <p style="margin: 15px 0 10px 0; font-weight: 600; color: #155724; font-size: 15px;">
                            <i class="fas fa-envelope"></i> Enviamos para o e-mail cadastrado: <strong><?php echo htmlspecialchars($maskedDestination); ?></strong>
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="alert-info" style="margin-top: 20px;">
                    <strong><i class="fas fa-question-circle"></i> Não recebeu?</strong><br>
                    <ul style="margin: 10px 0 0 20px; padding: 0;">
                        <li>Verifique se digitou corretamente o <?php echo $displayType === 'aluno' ? 'CPF' : 'e-mail'; ?>.</li>
                        <li>Confira sua caixa de entrada, pasta de spam ou lixeira.</li>
                        <li>O e-mail pode levar alguns minutos para chegar.</li>
                        <?php if ($displayType === 'aluno'): ?>
                        <li>Se você não tiver e-mail cadastrado, entre em contato com a Secretaria para atualizar seu cadastro.</li>
                        <?php endif; ?>
                    </ul>
                    
                    <p style="margin: 15px 0 5px 0; padding-top: 10px; border-top: 1px solid rgba(26, 54, 93, 0.2);">
                        <strong>Contato da Secretaria:</strong><br>
                        📞 <?php echo htmlspecialchars($secretariaContato['telefone']); ?><br>
                        💬 WhatsApp: <?php echo htmlspecialchars($secretariaContato['whatsapp']); ?><br>
                        📧 <a href="mailto:<?php echo htmlspecialchars($secretariaContato['email']); ?>" style="color: #1A365D;"><?php echo htmlspecialchars($secretariaContato['email']); ?></a>
                    </p>
                </div>
            <?php endif; ?>
            
            <?php if (!$success): ?>
                <form method="POST">
                    <input type="hidden" name="user_type" value="<?php echo htmlspecialchars($displayType); ?>">
                    
                    <div class="form-group">
                        <label for="login" class="form-label">
                            <?php echo htmlspecialchars($currentConfig['field_label']); ?>
                            <span style="color: #d63031;">*</span>
                        </label>
                        <input 
                            type="<?php echo htmlspecialchars($currentConfig['field_type']); ?>" 
                            id="login" 
                            name="login" 
                            class="form-control" 
                            placeholder="<?php echo htmlspecialchars($currentConfig['placeholder']); ?>" 
                            required
                            autofocus
                            <?php if ($displayType === 'aluno'): ?>
                            pattern="[0-9]{3}\.[0-9]{3}\.[0-9]{3}-[0-9]{2}|[0-9]{11}"
                            title="Digite o CPF no formato 000.000.000-00 ou apenas números"
                            maxlength="14"
                            <?php endif; ?>
                        >
                        <div class="form-help">
                            <?php echo htmlspecialchars($currentConfig['help_text']); ?>
                        </div>
                    </div>
                    
                    <?php if ($displayType === 'aluno'): ?>
                        <div class="alert-info" style="font-size: 13px;">
                            <strong>ℹ️ Informação:</strong> As instruções de recuperação serão enviadas para o e-mail cadastrado em seu CPF. Se não tiver e-mail cadastrado, entre em contato com a Secretaria.
                        </div>
                    <?php endif; ?>
                    
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
