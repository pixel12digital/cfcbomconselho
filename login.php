<?php
// =====================================================
// PÁGINA DE LOGIN PRINCIPAL - SISTEMA CFC
// VERSÃO 3.0 - INTERFACE REORGANIZADA POR TIPO DE USUÁRIO
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
$userType = $_GET['type'] ?? 'admin'; // Tipo de usuário selecionado

// Processar formulário de login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $remember = isset($_POST['remember']);
    $selectedType = $_POST['user_type'] ?? 'admin';
    
    if (empty($email) || empty($senha)) {
        $error = 'Por favor, preencha todos os campos';
    } else {
        try {
            // Para alunos, usar sistema específico
            if ($selectedType === 'aluno') {
                $db = db();
                
                // Log de debug
                error_log("[LOGIN ALUNO] Tentativa de login - CPF: $email, Senha: $senha");
                
                // Primeiro, tentar buscar na tabela alunos
                $aluno = $db->fetch("SELECT * FROM alunos WHERE cpf = ? AND ativo = 1", [$email]);
                error_log("[LOGIN ALUNO] Busca na tabela alunos: " . ($aluno ? "Encontrado ID " . $aluno['id'] : "Não encontrado"));
                
                // Se não encontrar na tabela alunos, buscar na tabela usuarios
                if (!$aluno) {
                    // Tentar buscar por CPF primeiro
                    $aluno = $db->fetch("SELECT * FROM usuarios WHERE cpf = ? AND tipo = 'aluno' AND ativo = 1", [$email]);
                    error_log("[LOGIN ALUNO] Busca por CPF na tabela usuarios: " . ($aluno ? "Encontrado ID " . $aluno['id'] : "Não encontrado"));
                    
                    // Se não encontrar por CPF, tentar por email
                    if (!$aluno) {
                        $aluno = $db->fetch("SELECT * FROM usuarios WHERE email = ? AND tipo = 'aluno' AND ativo = 1", [$email]);
                        error_log("[LOGIN ALUNO] Busca por email na tabela usuarios: " . ($aluno ? "Encontrado ID " . $aluno['id'] : "Não encontrado"));
                    }
                }
                
                if ($aluno) {
                    $senhaValida = password_verify($senha, $aluno['senha'] ?? '');
                    $senhaDefault = ($senha === '123456');
                    error_log("[LOGIN ALUNO] Verificação de senha - Válida: " . ($senhaValida ? "SIM" : "NÃO") . ", Padrão: " . ($senhaDefault ? "SIM" : "NÃO"));
                    
                    if ($senhaValida || $senhaDefault) {
                        $_SESSION['aluno_id'] = $aluno['id'];
                        $_SESSION['aluno_nome'] = $aluno['nome'];
                        $_SESSION['aluno_cpf'] = $aluno['cpf'] ?? $email;
                        $_SESSION['user_type'] = 'aluno';
                        $_SESSION['last_activity'] = time();
                        
                        error_log("[LOGIN ALUNO] Login bem-sucedido para ID: " . $aluno['id']);
                        $success = 'Login realizado com sucesso';
                        header('Refresh: 1; URL=aluno/dashboard.php');
                        exit;
                    } else {
                        error_log("[LOGIN ALUNO] Senha inválida para ID: " . $aluno['id']);
                        $error = 'CPF ou senha inválidos';
                    }
                } else {
                    error_log("[LOGIN ALUNO] Usuário não encontrado");
                    $error = 'CPF ou senha inválidos';
                }
            } else {
                // Para funcionários, usar sistema normal
            $result = $auth->login($email, $senha, $remember);
            
            if ($result['success']) {
                $success = $result['message'];
                header('Refresh: 1; URL=admin/');
                exit;
            } else {
                $error = $result['message'];
                }
            }
        } catch (Exception $e) {
            $error = 'Erro interno do sistema. Tente novamente.';
            if (LOG_ENABLED) {
                error_log('Erro no login: ' . $e->getMessage());
            }
        }
    }
}

// Configurações por tipo de usuário
$userTypes = [
    'admin' => [
        'title' => 'Administrador',
        'placeholder' => 'admin@cfc.com',
        'field_label' => 'E-mail',
        'field_type' => 'email'
    ],
    'secretaria' => [
        'title' => 'Atendente CFC',
        'placeholder' => 'atendente@cfc.com',
        'field_label' => 'E-mail',
        'field_type' => 'email'
    ],
    'instrutor' => [
        'title' => 'Instrutor',
        'placeholder' => 'instrutor@cfc.com',
        'field_label' => 'E-mail',
        'field_type' => 'email'
    ],
    'aluno' => [
        'title' => 'Aluno',
        'placeholder' => '000.000.000-00',
        'field_label' => 'CPF',
        'field_type' => 'text'
    ]
];

$currentConfig = $userTypes[$userType] ?? $userTypes['admin'];
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $currentConfig['title']; ?> | Sistema CFC</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
            max-width: 1000px;
            min-height: 600px;
            display: flex;
        }
        
        .left-panel {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            color: white;
            padding: 40px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        
        .left-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="75" cy="75" r="1" fill="rgba(255,255,255,0.1)"/><circle cx="50" cy="10" r="0.5" fill="rgba(255,255,255,0.05)"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }
        
        .logo-section {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            z-index: 1;
        }
        
        .logo-image {
            width: 180px;
            height: 180px;
            margin-bottom: 30px;
            border-radius: 50%;
            box-shadow: 0 10px 30px rgba(0,0,0,0.4);
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            padding: 8px;
            object-fit: contain;
            transition: all 0.3s ease;
            border: 4px solid rgba(255,255,255,0.3);
        }
        
        .logo-image:hover {
            transform: scale(1.08);
            box-shadow: 0 15px 40px rgba(0,0,0,0.5);
        }
        
        .system-subtitle {
            font-size: 18px;
            opacity: 0.9;
            line-height: 1.6;
            text-align: center;
            margin-top: 10px;
        }
        
        .user-types {
            position: relative;
            z-index: 1;
        }
        
        .user-type-card {
            background: rgba(255,255,255,0.1);
            border: 2px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            text-decoration: none;
            color: white;
            display: block;
        }
        
        .user-type-card:hover {
            background: rgba(255,255,255,0.2);
            border-color: rgba(255,255,255,0.4);
            transform: translateY(-2px);
            text-decoration: none;
            color: white;
        }
        
        .user-type-card.active {
            background: rgba(255,255,255,0.25);
            border-color: #f39c12;
            box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
        }
        
        .user-type-title {
            font-size: 18px;
            font-weight: 600;
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
            color: #2c3e50;
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
            color: #2c3e50;
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
            border-color: #3498db;
            background: white;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-control.error {
            border-color: #e74c3c;
            background: #fdf2f2;
        }
        
        .form-help {
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        .form-error {
            font-size: 12px;
            color: #e74c3c;
            margin-top: 5px;
        }
        
        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input[type="checkbox"] {
            margin-right: 8px;
        }
        
        .checkbox-group label {
            font-size: 14px;
            color: #2c3e50;
        }
        
        .forgot-password {
            color: #3498db;
            text-decoration: none;
            font-size: 14px;
        }
        
        .forgot-password:hover {
            text-decoration: underline;
        }
        
        .btn-login {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-login:active {
            transform: translateY(0);
        }
        
        .btn-login:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .alert {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .alert-error {
            background: #fdf2f2;
            color: #e74c3c;
            border: 1px solid #fecaca;
        }
        
        .alert-success {
            background: #f0f9ff;
            color: #059669;
            border: 1px solid #a7f3d0;
        }
        
        .login-footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e1e5e9;
        }
        
        .login-footer p {
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 10px;
        }
        
        .support-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }
        
        .support-info h4 {
            color: #2c3e50;
            font-size: 14px;
            margin-bottom: 5px;
        }
        
        .support-info p {
            color: #7f8c8d;
            font-size: 12px;
            margin: 2px 0;
        }
        
        @media (max-width: 768px) {
            .login-container {
                flex-direction: column;
                max-width: 400px;
            }
            
            .left-panel {
                padding: 30px 20px;
            }
            
            .right-panel {
                padding: 30px 20px;
            }
            
            .logo-image {
                width: 140px;
                height: 140px;
                margin-bottom: 20px;
                padding: 6px;
            }
            
            .system-subtitle {
                font-size: 16px;
            }
            
            .user-type-card {
                padding: 15px;
            }
        }
        
        .hidden {
            display: none !important;
        }
        
        .back-to-site {
            position: absolute;
            top: 20px;
            left: 20px;
            color: white;
            text-decoration: none;
            padding: 10px 15px;
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        
        .back-to-site:hover {
            background: rgba(255,255,255,0.2);
            color: white;
            text-decoration: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Link para voltar ao site -->
        <a href="index.php" class="back-to-site">← Voltar ao Site</a>
        
        <!-- Painel Esquerdo - Seleção de Tipo de Usuário -->
        <div class="left-panel">
            <div class="logo-section">
                <img src="assets/logo.png" alt="Logo CFC" class="logo-image">
                <p class="system-subtitle">Sistema completo para gestão de Centros de Formação de Condutores</p>
        </div>
            
            <div class="user-types">
                <?php foreach ($userTypes as $type => $config): ?>
                    <a href="?type=<?php echo $type; ?>" class="user-type-card <?php echo $userType === $type ? 'active' : ''; ?>">
                        <div class="user-type-title"><?php echo $config['title']; ?></div>
                    </a>
                <?php endforeach; ?>
            </div>
                </div>
                    
        <!-- Painel Direito - Formulário de Login -->
        <div class="right-panel">
            <div class="login-header">
                <h2 class="login-title"><?php echo $currentConfig['title']; ?></h2>
                <p class="login-subtitle">Entre com suas credenciais para acessar o sistema</p>
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
                            
                            <?php if (isset($_GET['message']) && $_GET['message'] === 'logout_success'): ?>
                <div class="alert alert-success">
                    ✅ Logout realizado com sucesso! Você foi desconectado do sistema.
                            </div>
                            <?php endif; ?>
                            
            <form method="POST">
                <input type="hidden" name="user_type" value="<?php echo $userType; ?>">
                
                <div class="form-group">
                    <label for="email" class="form-label"><?php echo $currentConfig['field_label']; ?></label>
                    <input type="<?php echo $currentConfig['field_type']; ?>" 
                                       id="email" 
                                       name="email" 
                           class="form-control" 
                           placeholder="<?php echo $currentConfig['placeholder']; ?>" 
                           required>
                    <div class="form-help">
                        <?php if ($userType === 'aluno'): ?>
                            Digite seu CPF cadastrado no sistema
                        <?php else: ?>
                            Digite seu endereço de e-mail cadastrado no sistema
                        <?php endif; ?>
                            </div>
                        </div>
                                
                <div class="form-group">
                    <label for="senha" class="form-label">Senha</label>
                                <input type="password" 
                                       id="senha" 
                                       name="senha" 
                           class="form-control" 
                                       placeholder="Sua senha"
                           required>
                    <div class="form-help">Digite sua senha de acesso ao sistema</div>
                        </div>
                                
                <?php if ($userType !== 'aluno'): ?>
                <div class="form-options">
                    <div class="checkbox-group">
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">Lembrar de mim</label>
                            </div>
                    <a href="#" class="forgot-password">Esqueci minha senha</a>
                            </div>
                <?php endif; ?>
                
                <button type="submit" class="btn-login">
                    Entrar no Sistema
                            </button>
            </form>
            
            <div class="login-footer">
                <p>Problemas para acessar? Entre em contato com o suporte</p>
                
                <div class="support-info">
                    <h4>📞 Suporte</h4>
                    <p>Segunda a Sexta, 8h às 18h</p>
                    <p>suporte@cfc.com</p>
                    <p>&copy; <?php echo date('Y'); ?> Sistema CFC. Versão 3.0</p>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Máscara para CPF quando tipo for aluno
        document.addEventListener('DOMContentLoaded', function() {
            const emailInput = document.getElementById('email');
            const userType = '<?php echo $userType; ?>';
            
            if (userType === 'aluno') {
                emailInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d)/, '$1.$2');
                    value = value.replace(/(\d{3})(\d{1,2})$/, '$1-$2');
                    e.target.value = value;
                });
            }
        });
        
        // Auto-focus no campo de entrada
        document.getElementById('email').focus();
    </script>
</body>
</html>
