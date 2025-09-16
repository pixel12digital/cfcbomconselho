<?php
// =====================================================
// PÁGINA DE LOGIN PRINCIPAL - SISTEMA CFC
// VERSÃO 2.0 - RESPONSIVA E ACESSÍVEL
// =====================================================

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Se já estiver logado, redirecionar para dashboard
if (isLoggedIn()) {
    header('Location: admin/');
    exit;
}

$error = '';
$success = '';

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $remember = isset($_POST['remember']);
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos';
    } else {
        try {
            $result = $auth->login($email, $senha, $remember);
            
            if ($result['success']) {
                $success = $result['message'];
                // Redirecionar após 1 segundo
                header('Refresh: 1; URL=admin/');
                exit;
            } else {
                $error = $result['message'];
            }
        } catch (Exception $e) {
            $error = 'Erro interno do sistema. Tente novamente.';
            if (LOG_ENABLED) {
                error_log('Erro no login: ' . $e->getMessage());
            }
        }
    }
}

// Obter IP do cliente para controle de tentativas
$clientIP = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Verificar se deve mostrar captcha (simplificado)
$showCaptcha = false;
// Removida verificação da tabela logs que estava causando erro
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo APP_NAME; ?> - Login</title>
    
    <!-- Meta tags SEO -->
    <meta name="description" content="<?php echo META_DESCRIPTION; ?>">
    <meta name="keywords" content="<?php echo META_KEYWORDS; ?>">
    <meta name="author" content="<?php echo APP_NAME; ?>">
    <meta name="robots" content="noindex, nofollow">
    
    <!-- Meta tags para acessibilidade -->
    <meta name="theme-color" content="#0d6efd">
    <meta name="color-scheme" content="light dark">
    <meta name="supported-color-schemes" content="light dark">
    
    <!-- Meta tags para dispositivos móveis -->
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo APP_NAME; ?>">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="assets/img/favicon.ico">
    <link rel="apple-touch-icon" href="assets/img/apple-touch-icon.png">
    
    <!-- CSS LOCAL APENAS -->
    <link href="assets/css/simple-login.css" rel="stylesheet">
</head>
<body class="login-page">
    <!-- Skip to main content link for screen readers -->
    <a href="#main-content" class="sr-only sr-only-focusable">Pular para o conteúdo principal</a>
    
    <div class="login-page-container">
        <!-- Coluna da esquerda - Informações do sistema (Desktop) -->
        <div class="login-info-column" role="banner" aria-label="Informações do sistema">
            <div class="login-logo-container">
                <img src="assets/logo.png" alt="Sistema CFC" class="login-logo-icon">
                <h1 class="login-logo-title"><?php echo APP_NAME; ?></h1>
                <p class="login-logo-subtitle">Sistema completo para gestão de Centros de Formação de Condutores</p>
            </div>
            <nav class="login-features-list" role="navigation" aria-label="Recursos do sistema">
                <div class="login-feature-item">
                    <span class="login-feature-icon">👥</span>
                    <span class="login-feature-text">Gestão de Alunos e Instrutores</span>
                </div>
                <div class="login-feature-item">
                    <span class="login-feature-icon">📅</span>
                    <span class="login-feature-text">Agendamento de Aulas</span>
                </div>
                <div class="login-feature-item">
                    <span class="login-feature-icon">📊</span>
                    <span class="login-feature-text">Relatórios e Estatísticas</span>
                </div>
                <div class="login-feature-item">
                    <span class="login-feature-icon">🛡️</span>
                    <span class="login-feature-text">Controle de Documentação</span>
                </div>
            </nav>
        </div>
            
        <!-- Coluna da direita - Formulário de login -->
        <div class="login-form-column" role="main" id="main-content">
            <div class="login-form-wrapper">
                <!-- Logo para mobile - Sempre visível -->
                <div class="login-mobile-logo d-lg-none">
                    <img src="assets/logo.png" alt="Sistema CFC" class="login-mobile-logo-icon">
                    <h2><?php echo APP_NAME; ?></h2>
                </div>
                    
                <!-- Card de login -->
                <div class="login-form-card" role="region" aria-labelledby="login-title">
                    <div class="login-form-header">
                        <h3 class="login-form-title" id="login-title">Acesso ao Sistema</h3>
                        <p class="login-form-subtitle">Entre com suas credenciais para acessar o sistema</p>
                    </div>
                    <div class="login-form-body">
                            
                            <!-- Mensagens de erro/sucesso -->
                            <?php if ($error): ?>
                            <div class="alert alert-danger" role="alert" aria-live="polite" aria-atomic="true">
                                <span id="error-message">⚠️ <?php echo htmlspecialchars($error); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                            <div class="alert alert-success" role="alert" aria-live="polite" aria-atomic="true">
                                <span id="success-message">✅ <?php echo htmlspecialchars($success); ?></span>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
                            <div class="alert alert-info" role="alert" aria-live="polite" aria-atomic="true">
                                <span>ℹ️ Logout realizado com sucesso! Você foi desconectado do sistema.</span>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Formulário de login -->
                            <form method="POST" action="" id="loginForm" novalidate role="form" aria-labelledby="login-title">
                        <!-- Campo Email -->
                        <div class="form-field-group">
                            <label for="email" class="form-field-label">
                                📧 E-mail
                            </label>
                            <div class="form-input-group">
                                <span class="input-group-text">@</span>
                                <input type="email" 
                                       class="form-field-input" 
                                       id="email" 
                                       name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                       placeholder="seu@email.com"
                                       required
                                       autocomplete="email"
                                       autofocus
                                       aria-describedby="email-help email-error"
                                       aria-required="true"
                                       aria-invalid="false">
                            </div>
                            <div class="form-help-text" id="email-help">
                                Digite seu endereço de e-mail cadastrado no sistema.
                            </div>
                            <div class="form-validation-message" id="email-error" role="alert" aria-live="polite">
                                Por favor, informe um e-mail válido.
                            </div>
                        </div>
                                
                        <!-- Campo Senha -->
                        <div class="form-field-group">
                            <label for="senha" class="form-field-label">
                                🔒 Senha
                            </label>
                            <div class="form-input-group">
                                <span class="input-group-text">🔑</span>
                                <input type="password" 
                                       class="form-field-input" 
                                       id="senha" 
                                       name="senha" 
                                       placeholder="Sua senha"
                                       required
                                       autocomplete="current-password"
                                       aria-describedby="senha-help senha-error"
                                       aria-required="true"
                                       aria-invalid="false">
                                <button class="password-toggle-btn" 
                                        type="button" 
                                        id="togglePassword"
                                        aria-label="Mostrar senha"
                                        aria-pressed="false">
                                    👁️
                                </button>
                            </div>
                            <div class="form-help-text" id="senha-help">
                                Digite sua senha de acesso ao sistema.
                            </div>
                            <div class="form-validation-message" id="senha-error" role="alert" aria-live="polite"></div>
                        </div>
                                
                        <!-- Opções adicionais -->
                        <div class="form-field-group">
                            <div class="form-checkbox-container">
                                <input class="form-checkbox-input" 
                                       type="checkbox" 
                                       id="remember" 
                                       name="remember"
                                       aria-describedby="remember-help">
                                <label class="form-checkbox-label" for="remember">
                                    Lembrar de mim
                                </label>
                            </div>
                            <div class="form-checkbox-help" id="remember-help">
                                Mantenha-me conectado por 30 dias.
                            </div>
                            <div class="text-end">
                                <a href="/recuperar-senha.php" class="form-link" aria-label="Esqueci minha senha - Abrir página de recuperação">
                                    Esqueci minha senha
                                </a>
                            </div>
                        </div>
                                
                        <!-- Botão de login -->
                        <div class="form-field-group">
                            <button type="submit" 
                                    class="form-submit-btn" 
                                    id="btnLogin"
                                    aria-describedby="login-help">
                                <div class="btn-content">
                                    🚀 Entrar no Sistema
                                </div>
                            </button>
                            <div class="form-help-text text-center mt-2" id="login-help">
                                Clique para acessar o sistema com suas credenciais.
                            </div>
                        </div>
                        
                        <!-- Informações de suporte -->
                        <div class="text-center">
                            <small class="form-help-text">
                                ℹ️ Problemas para acessar? Entre em contato com o suporte.
                            </small>
                        </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Rodapé -->
                    <footer class="text-center mt-3" role="contentinfo">
                        <p class="text-muted mb-1">
                            <small>
                                🕐 Suporte: <?php echo SUPPORT_HOURS; ?>
                            </small>
                        </p>
                        <p class="text-muted mb-0">
                            <small>
                                📧 <a href="mailto:<?php echo SUPPORT_EMAIL; ?>" aria-label="Enviar e-mail para suporte">
                                    <?php echo SUPPORT_EMAIL; ?>
                                </a>
                            </small>
                        </p>
                        <p class="text-muted mt-1">
                            <small>
                                &copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. 
                                Versão <?php echo APP_VERSION; ?>
                            </small>
                        </p>
                    </footer>
                </div>
            </div>
        </div>
        
        <!-- Link para acesso de alunos -->
        <div class="login-footer" style="text-align: center; margin-top: 20px;">
            <p style="color: #666; font-size: 14px;">
                É aluno? <a href="aluno/login.php" style="color: #667eea; text-decoration: none; font-weight: 500;">Acesse seu painel aqui</a>
            </p>
        </div>
    </div>
    
    <!-- JavaScript SIMPLIFICADO -->
    <script>
        // Sistema de login SIMPLIFICADO para evitar travamentos
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const btnLogin = document.getElementById('btnLogin');
            
            if (loginForm && btnLogin) {
                // Prevenção de múltiplos submits
                let formSubmitted = false;
                
                loginForm.addEventListener('submit', function(e) {
                    if (formSubmitted) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // Validar campos obrigatórios
                    const email = document.getElementById('email').value.trim();
                    const senha = document.getElementById('senha').value.trim();
                    
                    if (!email || !senha) {
                        e.preventDefault();
                        alert('Por favor, preencha todos os campos.');
                        return false;
                    }
                    
                    // Marcar como enviado
                    formSubmitted = true;
                    
                    // Mostrar loading no botão
                    btnLogin.innerHTML = '⏳ <span>Entrando...</span>';
                    btnLogin.disabled = true;
                    
                    // Permitir envio do formulário
                    return true;
                });
                
                // Toggle de visibilidade da senha
                const togglePassword = document.getElementById('togglePassword');
                const senhaInput = document.getElementById('senha');
                
                if (togglePassword && senhaInput) {
                    togglePassword.addEventListener('click', function() {
                        const type = senhaInput.type === 'password' ? 'text' : 'password';
                        senhaInput.type = type;
                        
                        // Atualizar emoji do botão
                        this.innerHTML = type === 'password' ? '👁️' : '🙈';
                    });
                }
                
                // Auto-focus no email
                const emailInput = document.getElementById('email');
                if (emailInput) {
                    emailInput.focus();
                }
            }
        });
    </script>
    
    <!-- Analytics (se habilitado) -->
    <?php if (ANALYTICS_ENABLED && ANALYTICS_PROVIDER === 'google' && ANALYTICS_TRACKING_ID): ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo ANALYTICS_TRACKING_ID; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo ANALYTICS_TRACKING_ID; ?>');
    </script>
    <?php endif; ?>
</body>
</html>
