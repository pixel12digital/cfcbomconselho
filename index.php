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
    header('Location: admin/dashboard.php');
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
        $result = $auth->login($email, $senha, $remember);
        
        if ($result['success']) {
            $success = $result['message'];
            // Redirecionar após 1 segundo
            header('Refresh: 1; URL=admin/dashboard.php');
        } else {
            $error = $result['message'];
        }
    }
}

// Obter IP do cliente para controle de tentativas
$clientIP = $_SERVER['HTTP_CLIENT_IP'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// Verificar se deve mostrar captcha
$showCaptcha = false;
if (AUDIT_ENABLED) {
    $sql = "SELECT COUNT(*) as tentativas FROM logs WHERE ip_address = :ip AND acao = 'login_failed' AND criado_em > DATE_SUB(NOW(), INTERVAL :timeout SECOND)";
    $result = db()->fetch($sql, ['ip' => $clientIP, 'timeout' => LOGIN_TIMEOUT]);
    $showCaptcha = $result['tentativas'] >= MAX_LOGIN_ATTEMPTS;
}
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
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
                    <link href="assets/css/variables.css" rel="stylesheet">
                <link href="assets/css/login.css" rel="stylesheet">
                <link href="assets/css/responsive-utilities.css" rel="stylesheet">
                <link href="assets/css/fix-visibility.css" rel="stylesheet">

    <link href="assets/css/components/login-form.css" rel="stylesheet">
    <link href="assets/css/components/desktop-layout.css" rel="stylesheet">
    <link href="assets/css/components/no-scroll-optimization.css" rel="stylesheet">
    
    <!-- Recaptcha -->
    <?php if (RECAPTCHA_SITE_KEY && $showCaptcha): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
</head>
<body class="login-page">
    <!-- Skip to main content link for screen readers -->
    <a href="#main-content" class="sr-only sr-only-focusable">Pular para o conteúdo principal</a>
    
    <div class="login-page-container">
        <!-- Coluna da esquerda - Informações do sistema (Desktop) -->
        <div class="login-info-column" role="banner" aria-label="Informações do sistema">
            <div class="login-info-content">
                <div class="login-logo-container">
                    <i class="fas fa-car login-logo-icon" aria-hidden="true"></i>
                    <h1 class="login-logo-title"><?php echo APP_NAME; ?></h1>
                    <p class="login-logo-subtitle">Sistema completo para gestão de Centros de Formação de Condutores</p>
                </div>
                <nav class="login-features-list" role="navigation" aria-label="Recursos do sistema">
                    <div class="login-feature-item">
                        <i class="fas fa-users login-feature-icon" aria-hidden="true"></i>
                        <span class="login-feature-text">Gestão de Alunos e Instrutores</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-calendar-alt login-feature-icon" aria-hidden="true"></i>
                        <span class="login-feature-text">Agendamento de Aulas</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-chart-line login-feature-icon" aria-hidden="true"></i>
                        <span class="login-feature-text">Relatórios e Estatísticas</span>
                    </div>
                    <div class="login-feature-item">
                        <i class="fas fa-shield-alt login-feature-icon" aria-hidden="true"></i>
                        <span class="login-feature-text">Controle de Documentação</span>
                    </div>
                </nav>
            </div>
        </div>
            
        <!-- Coluna da direita - Formulário de login -->
        <div class="login-form-column" role="main" id="main-content">
            <div class="login-form-wrapper">
                <!-- Logo para mobile - Sempre visível -->
                <div class="login-mobile-logo d-lg-none">
                    <i class="fas fa-car" aria-hidden="true"></i>
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
                            <div class="alert alert-danger alert-dismissible fade show" role="alert" aria-live="polite" aria-atomic="true">
                                <i class="fas fa-exclamation-triangle me-2" aria-hidden="true"></i>
                                <span id="error-message"><?php echo htmlspecialchars($error); ?></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar alerta"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert" aria-live="polite" aria-atomic="true">
                                <i class="fas fa-check-circle me-2" aria-hidden="true"></i>
                                <span id="success-message"><?php echo htmlspecialchars($success); ?></span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar alerta"></button>
                            </div>
                            <?php endif; ?>
                            
                            <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
                            <div class="alert alert-info alert-dismissible fade show" role="alert" aria-live="polite" aria-atomic="true">
                                <i class="fas fa-sign-out-alt me-2" aria-hidden="true"></i>
                                <span>Logout realizado com sucesso! Você foi desconectado do sistema.</span>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar alerta"></button>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Formulário de login -->
                            <form method="POST" action="" id="loginForm" novalidate role="form" aria-labelledby="login-title">
                        <!-- Campo Email -->
                        <div class="form-field-group">
                            <label for="email" class="form-field-label">
                                <i class="fas fa-envelope" aria-hidden="true"></i>E-mail
                            </label>
                            <div class="form-input-group">
                                <span class="input-group-text" id="email-addon">
                                    <i class="fas fa-at" aria-hidden="true"></i>
                                </span>
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
                                <i class="fas fa-lock" aria-hidden="true"></i>Senha
                            </label>
                            <div class="form-input-group">
                                <span class="input-group-text" id="senha-addon">
                                    <i class="fas fa-key" aria-hidden="true"></i>
                                </span>
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
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                </button>
                            </div>
                            <div class="form-help-text" id="senha-help">
                                Digite sua senha de acesso ao sistema.
                            </div>
                            <div class="form-validation-message" id="senha-error" role="alert" aria-live="polite"></div>
                        </div>
                                
                        <!-- Captcha (se necessário) -->
                        <?php if ($showCaptcha && RECAPTCHA_SITE_KEY): ?>
                        <div class="form-field-group">
                            <div class="g-recaptcha" 
                                 data-sitekey="<?php echo RECAPTCHA_SITE_KEY; ?>"
                                 data-callback="onRecaptchaSuccess"
                                 data-expired-callback="onRecaptchaExpired"
                                 aria-label="Verificação de segurança reCAPTCHA">
                            </div>
                            <div class="form-help-text text-danger">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                Muitas tentativas de login. Complete o captcha para continuar.
                            </div>
                        </div>
                        <?php endif; ?>
                        
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
                                    <?php echo $showCaptcha ? 'disabled' : ''; ?>
                                    aria-describedby="login-help">
                                <div class="btn-content">
                                    <i class="fas fa-sign-in-alt" aria-hidden="true"></i>
                                    <span>Entrar no Sistema</span>
                                </div>
                                <div class="spinner">
                                    <i class="fas fa-spinner" aria-hidden="true"></i>
                                </div>
                            </button>
                            <div class="form-help-text text-center mt-2" id="login-help">
                                Clique para acessar o sistema com suas credenciais.
                            </div>
                        </div>
                        
                        <!-- Informações de suporte -->
                        <div class="text-center">
                            <small class="form-help-text">
                                <i class="fas fa-info-circle" aria-hidden="true"></i>
                                Problemas para acessar? Entre em contato com o suporte.
                            </small>
                        </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Rodapé -->
                    <footer class="text-center mt-3" role="contentinfo">
                        <p class="text-muted mb-1">
                            <small>
                                <i class="fas fa-clock me-1" aria-hidden="true"></i>
                                Suporte: <?php echo SUPPORT_HOURS; ?>
                            </small>
                        </p>
                        <p class="text-muted mb-0">
                            <small>
                                <i class="fas fa-envelope me-1" aria-hidden="true"></i>
                                <a href="mailto:<?php echo SUPPORT_EMAIL; ?>" aria-label="Enviar e-mail para suporte">
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
    </div>
    
    <!-- Loading Modal -->
    <div class="modal fade" id="loadingModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" role="dialog" aria-labelledby="loading-title">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body text-center p-4">
                    <div class="spinner-border text-primary mb-3" role="status" aria-hidden="true">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <h5 class="mb-2" id="loading-title">Autenticando...</h5>
                    <p class="text-muted mb-0">Por favor, aguarde enquanto verificamos suas credenciais.</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="assets/js/login.js"></script>
    <script src="assets/js/login-form.js"></script>
    <script src="assets/js/fix-visibility.js"></script>
    
    <script>
        // Variáveis globais para o captcha
        let recaptchaCompleted = <?php echo $showCaptcha ? 'false' : 'true'; ?>;
        
        // Função chamada quando o captcha é completado
        function onRecaptchaSuccess() {
            recaptchaCompleted = true;
            const btnLogin = document.getElementById('btnLogin');
            if (btnLogin) {
                btnLogin.disabled = false;
                btnLogin.classList.remove('btn-secondary');
                btnLogin.classList.add('btn-primary');
                btnLogin.setAttribute('aria-disabled', 'false');
            }
        }
        
        // Função chamada quando o captcha expira
        function onRecaptchaExpired() {
            recaptchaCompleted = false;
            const btnLogin = document.getElementById('btnLogin');
            if (btnLogin) {
                btnLogin.disabled = true;
                btnLogin.classList.remove('btn-primary');
                btnLogin.classList.add('btn-secondary');
                btnLogin.setAttribute('aria-disabled', 'true');
            }
        }
        
        // Validação do formulário
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            if (!this.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            
            // Verificar captcha se necessário
            if (<?php echo $showCaptcha ? 'true' : 'false'; ?> && !recaptchaCompleted) {
                e.preventDefault();
                alert('Por favor, complete o captcha para continuar.');
                return false;
            }
            
            // Mostrar loading
            if (this.checkValidity()) {
                const loadingModal = new bootstrap.Modal(document.getElementById('loadingModal'));
                loadingModal.show();
            }
            
            this.classList.add('was-validated');
        });
        
        // Toggle de visibilidade da senha
        document.getElementById('togglePassword').addEventListener('click', function() {
            const senhaInput = document.getElementById('senha');
            const icon = this.querySelector('i');
            const isVisible = senhaInput.type === 'text';
            
            if (isVisible) {
                senhaInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
                this.setAttribute('aria-label', 'Mostrar senha');
                this.setAttribute('aria-pressed', 'false');
            } else {
                senhaInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
                this.setAttribute('aria-label', 'Ocultar senha');
                this.setAttribute('aria-pressed', 'true');
            }
            
            // Focar no campo de senha
            senhaInput.focus();
        });
        
        // Auto-focus no campo de email
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            if (emailInput) {
                emailInput.focus();
            }
        });
        
        // Validação em tempo real
        document.getElementById('email').addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.classList.add('is-invalid');
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                this.setAttribute('aria-invalid', 'false');
            }
        });
        
        document.getElementById('senha').addEventListener('blur', function() {
            if (this.value && !this.checkValidity()) {
                this.classList.add('is-invalid');
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
                this.setAttribute('aria-invalid', 'false');
            }
        });
        
        // Limpar validações ao digitar
        document.getElementById('email').addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
            this.setAttribute('aria-invalid', 'false');
        });
        
        document.getElementById('senha').addEventListener('input', function() {
            this.classList.remove('is-invalid', 'is-valid');
            this.setAttribute('aria-invalid', 'false');
        });
        
        // Prevenção de múltiplos submits
        let formSubmitted = false;
        document.getElementById('loginForm').addEventListener('submit', function() {
            if (formSubmitted) {
                return false;
            }
            formSubmitted = true;
            const btnLogin = document.getElementById('btnLogin');
            if (btnLogin) {
                btnLogin.disabled = true;
                btnLogin.setAttribute('aria-disabled', 'true');
                btnLogin.innerHTML = '<i class="fas fa-spinner fa-spin me-2" aria-hidden="true"></i><span>Entrando...</span>';
            }
        });
        
        // Detectar mudanças na conexão
        window.addEventListener('online', function() {
            console.log('Conexão restaurada');
        });
        
        window.addEventListener('offline', function() {
            console.log('Conexão perdida');
            alert('Conexão com a internet perdida. Verifique sua conexão e tente novamente.');
        });
        
        // Prevenção de ataques de força bruta
        let loginAttempts = 0;
        const maxAttempts = 5;
        const lockoutTime = 300000; // 5 minutos em ms
        
        function checkLoginAttempts() {
            if (loginAttempts >= maxAttempts) {
                const btnLogin = document.getElementById('btnLogin');
                if (btnLogin) {
                    btnLogin.disabled = true;
                    btnLogin.setAttribute('aria-disabled', 'true');
                    btnLogin.innerHTML = '<i class="fas fa-lock me-2" aria-hidden="true"></i><span>Bloqueado temporariamente</span>';
                }
                
                setTimeout(() => {
                    loginAttempts = 0;
                    if (btnLogin) {
                        btnLogin.disabled = false;
                        btnLogin.setAttribute('aria-disabled', 'false');
                        btnLogin.innerHTML = '<i class="fas fa-sign-in-alt me-2" aria-hidden="true"></i><span>Entrar no Sistema</span>';
                    }
                }, lockoutTime);
            }
        }
        
        // Interceptar erros de login
        <?php if ($error): ?>
        loginAttempts++;
        checkLoginAttempts();
        <?php endif; ?>
        
        // Melhorar acessibilidade para navegação por teclado
        document.addEventListener('keydown', function(e) {
            // Escape para limpar formulário
            if (e.key === 'Escape') {
                const form = document.getElementById('loginForm');
                if (form) {
                    form.reset();
                    // Limpar validações
                    const inputs = form.querySelectorAll('.form-control');
                    inputs.forEach(input => {
                        input.classList.remove('is-invalid', 'is-valid');
                        input.setAttribute('aria-invalid', 'false');
                    });
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
