<?php
/**
 * Script de Reset - Senha do Admin
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script:
 * 1. Busca o usuário admin@cfc.local
 * 2. Gera um novo hash para a senha 'admin123' usando password_hash
 * 3. Atualiza a senha no banco de dados
 * 
 * Acesse via: http://localhost/cfc-v.1/tools/reset_admin_password.php
 */

// Inicialização
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
use App\Config\Env;
Env::load();

// Verificar se está em ambiente local (segurança)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if (!$isLocal) {
    die('⚠️ Este script só pode ser executado em ambiente local!');
}

$newPassword = 'admin123';
$adminEmail = 'admin@cfc.local';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset - Senha do Admin</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #28a745;
            padding-bottom: 10px;
        }
        .success {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
        }
        .error {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            word-break: break-all;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <h1>🔐 Reset - Senha do Admin</h1>

    <?php
    try {
        use App\Config\Database;
        $db = Database::getInstance()->getConnection();

        // Verificar se o usuário existe
        $stmt = $db->prepare("SELECT id, email, password FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$adminEmail]);
        $admin = $stmt->fetch();

        if (!$admin) {
            echo '<div class="card">';
            echo '<div class="error">';
            echo '❌ <strong>Erro:</strong> Usuário ' . htmlspecialchars($adminEmail) . ' não encontrado!';
            echo '<br><br>';
            echo 'Execute primeiro o seed: <code>database/seeds/001_seed_initial_data.sql</code>';
            echo '</div>';
            echo '</div>';
            exit;
        }

        // Gerar novo hash
        $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        echo '<div class="card">';
        echo '<div class="info">';
        echo '<strong>Gerando novo hash para a senha:</strong> ' . htmlspecialchars($newPassword);
        echo '<br><br>';
        echo '<strong>Novo hash gerado:</strong>';
        echo '<pre>' . htmlspecialchars($newHash) . '</pre>';
        echo '</div>';
        echo '</div>';

        // Verificar se deve executar (via GET parameter)
        $execute = isset($_GET['execute']) && $_GET['execute'] === '1';

        if ($execute) {
            // Atualizar senha no banco
            $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
            $stmt->execute([$newHash, $adminEmail]);

            echo '<div class="card">';
            echo '<div class="success">';
            echo '✅ <strong>Senha atualizada com sucesso!</strong>';
            echo '<br><br>';
            echo 'O usuário <strong>' . htmlspecialchars($adminEmail) . '</strong> agora pode fazer login com a senha <strong>' . htmlspecialchars($newPassword) . '</strong>';
            echo '</div>';
            echo '</div>';

            // Verificar se funcionou
            $stmt = $db->prepare("SELECT password FROM usuarios WHERE email = ? LIMIT 1");
            $stmt->execute([$adminEmail]);
            $updated = $stmt->fetch();
            
            $testVerify = password_verify($newPassword, $updated['password']);
            
            echo '<div class="card">';
            echo '<h2>Verificação</h2>';
            if ($testVerify) {
                echo '<div class="success">';
                echo '✅ <strong>Teste de verificação:</strong> password_verify("' . htmlspecialchars($newPassword) . '", hash) = TRUE';
                echo '<br><br>';
                echo 'A senha foi atualizada corretamente e está funcionando!';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '❌ <strong>Erro:</strong> A verificação da senha falhou após a atualização!';
                echo '</div>';
            }
            echo '</div>';

            echo '<div class="card">';
            echo '<h2>Próximo Passo</h2>';
            echo '<p>Agora você pode fazer login com:</p>';
            echo '<ul>';
            echo '<li><strong>Email:</strong> ' . htmlspecialchars($adminEmail) . '</li>';
            echo '<li><strong>Senha:</strong> ' . htmlspecialchars($newPassword) . '</li>';
            echo '</ul>';
            echo '<a href="' . htmlspecialchars(base_url('/login')) . '" class="btn">Ir para Login</a>';
            echo '</div>';

        } else {
            echo '<div class="card">';
            echo '<div class="info">';
            echo '<strong>Usuário encontrado:</strong> ' . htmlspecialchars($adminEmail) . ' (ID: ' . htmlspecialchars($admin['id']) . ')';
            echo '<br><br>';
            echo '<strong>Hash atual:</strong>';
            echo '<pre>' . htmlspecialchars($admin['password']) . '</pre>';
            echo '</div>';
            echo '</div>';

            echo '<div class="card">';
            echo '<h2>Confirmação</h2>';
            echo '<p>Clique no botão abaixo para atualizar a senha do admin:</p>';
            echo '<a href="?execute=1" class="btn" style="background: #28a745;">🔐 Atualizar Senha do Admin</a>';
            echo '</div>';
        }

    } catch (\Exception $e) {
        echo '<div class="card">';
        echo '<div class="error">';
        echo '❌ <strong>Erro:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        echo '</div>';
    }
    ?>

</body>
</html>
