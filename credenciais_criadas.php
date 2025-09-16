<?php
/**
 * Página de Credenciais Criadas - Sistema CFC
 * Exibe as credenciais geradas automaticamente para novos usuários
 */

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';
require_once '../includes/CredentialManager.php';

// Verificar se está logado
if (!isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

// Verificar permissão
if (!canManageUsers()) {
    header('Location: ../admin/');
    exit;
}

$credentials = null;
$message = '';

// Processar dados das credenciais se fornecidos
if (isset($_GET['credentials'])) {
    $credentials = json_decode(base64_decode($_GET['credentials']), true);
}

if (isset($_GET['message'])) {
    $message = urldecode($_GET['message']);
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Credenciais Criadas | Sistema CFC</title>
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
        
        .credentials-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 25px 50px rgba(0,0,0,0.15);
            padding: 40px;
            max-width: 600px;
            width: 100%;
            text-align: center;
        }
        
        .success-icon {
            font-size: 64px;
            color: #28a745;
            margin-bottom: 20px;
        }
        
        .title {
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
            font-weight: 600;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 30px;
        }
        
        .credentials-box {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
        }
        
        .credential-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #dee2e6;
        }
        
        .credential-item:last-child {
            border-bottom: none;
        }
        
        .credential-label {
            font-weight: 600;
            color: #495057;
        }
        
        .credential-value {
            font-family: 'Courier New', monospace;
            background: #e9ecef;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .warning-box {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 30px;
        }
        
        .warning-title {
            color: #856404;
            font-weight: 600;
            margin-bottom: 10px;
        }
        
        .warning-text {
            color: #856404;
            font-size: 14px;
            line-height: 1.5;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            color: white;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(52, 152, 219, 0.4);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .copy-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            cursor: pointer;
            margin-left: 10px;
        }
        
        .copy-btn:hover {
            background: #138496;
        }
        
        @media (max-width: 768px) {
            .credentials-container {
                padding: 20px;
            }
            
            .action-buttons {
                flex-direction: column;
            }
            
            .btn {
                width: 100%;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="credentials-container">
        <div class="success-icon">✅</div>
        <h1 class="title">Credenciais Criadas com Sucesso!</h1>
        <p class="subtitle">As credenciais de acesso foram geradas automaticamente</p>
        
        <?php if ($credentials): ?>
        <div class="credentials-box">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">📋 Credenciais de Acesso</h3>
            
            <?php if (isset($credentials['email'])): ?>
            <div class="credential-item">
                <span class="credential-label">📧 E-mail:</span>
                <span class="credential-value" id="email"><?php echo htmlspecialchars($credentials['email']); ?></span>
                <button class="copy-btn" onclick="copyToClipboard('email')">Copiar</button>
            </div>
            <?php endif; ?>
            
            <?php if (isset($credentials['cpf'])): ?>
            <div class="credential-item">
                <span class="credential-label">🆔 CPF:</span>
                <span class="credential-value" id="cpf"><?php echo htmlspecialchars($credentials['cpf']); ?></span>
                <button class="copy-btn" onclick="copyToClipboard('cpf')">Copiar</button>
            </div>
            <?php endif; ?>
            
            <div class="credential-item">
                <span class="credential-label">🔑 Senha Temporária:</span>
                <span class="credential-value" id="senha"><?php echo htmlspecialchars($credentials['senha_temporaria']); ?></span>
                <button class="copy-btn" onclick="copyToClipboard('senha')">Copiar</button>
            </div>
        </div>
        
        <div class="warning-box">
            <div class="warning-title">⚠️ Importante</div>
            <div class="warning-text">
                • Esta é uma senha temporária gerada automaticamente<br>
                • O usuário deve alterar a senha no primeiro acesso<br>
                • As credenciais foram enviadas por email (simulado)<br>
                • Guarde estas informações em local seguro
            </div>
        </div>
        <?php endif; ?>
        
        <?php if ($message): ?>
        <div class="warning-box">
            <div class="warning-text"><?php echo htmlspecialchars($message); ?></div>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <a href="admin/pages/usuarios.php" class="btn btn-primary">
                👥 Gerenciar Usuários
            </a>
            <a href="admin/pages/alunos.php" class="btn btn-success">
                🎓 Gerenciar Alunos
            </a>
            <a href="admin/" class="btn btn-secondary">
                🏠 Voltar ao Dashboard
            </a>
        </div>
    </div>
    
    <script>
        function copyToClipboard(elementId) {
            const element = document.getElementById(elementId);
            const text = element.textContent;
            
            navigator.clipboard.writeText(text).then(function() {
                // Feedback visual
                const btn = element.nextElementSibling;
                const originalText = btn.textContent;
                btn.textContent = 'Copiado!';
                btn.style.background = '#28a745';
                
                setTimeout(function() {
                    btn.textContent = originalText;
                    btn.style.background = '#17a2b8';
                }, 2000);
            }).catch(function(err) {
                console.error('Erro ao copiar: ', err);
                alert('Erro ao copiar para a área de transferência');
            });
        }
        
        // Auto-focus no primeiro campo
        document.addEventListener('DOMContentLoaded', function() {
            const firstValue = document.querySelector('.credential-value');
            if (firstValue) {
                firstValue.focus();
            }
        });
    </script>
</body>
</html>
