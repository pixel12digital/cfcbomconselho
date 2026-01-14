<?php
/**
 * Script de Debug - Verificação de Conexão e Banco de Dados
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script verifica:
 * 1. Configuração do banco de dados (DB_HOST, DB_NAME, DB_USER)
 * 2. Banco de dados atual em uso (SELECT DATABASE())
 * 3. Existência do usuário admin@cfc.local
 * 4. Hash da senha armazenado
 * 
 * Acesse via: http://localhost/cfc-v.1/tools/debug_database.php
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

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug - Conexão Banco de Dados</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 900px;
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
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        h2 {
            color: #555;
            margin-top: 0;
        }
        .info {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
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
        .warning {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 10px 0;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <h1>🔍 Debug - Conexão Banco de Dados</h1>

    <?php
    try {
        // 1. Verificar configuração do banco
        echo '<div class="card">';
        echo '<h2>1. Configuração do Banco de Dados</h2>';
        
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        $dbPass = $_ENV['DB_PASS'] ?? '';
        
        echo '<table>';
        echo '<tr><th>Configuração</th><th>Valor</th></tr>';
        echo '<tr><td>DB_HOST</td><td>' . htmlspecialchars($dbHost) . '</td></tr>';
        echo '<tr><td>DB_NAME</td><td>' . htmlspecialchars($dbName) . '</td></tr>';
        echo '<tr><td>DB_USER</td><td>' . htmlspecialchars($dbUser) . '</td></tr>';
        echo '<tr><td>DB_PASS</td><td>' . (empty($dbPass) ? '<em>(vazio)</em>' : '••••••••') . '</td></tr>';
        echo '</table>';
        echo '</div>';

        // 2. Testar conexão
        echo '<div class="card">';
        echo '<h2>2. Teste de Conexão</h2>';
        
        use App\Config\Database;
        $db = Database::getInstance()->getConnection();
        
        echo '<div class="success">✅ Conexão estabelecida com sucesso!</div>';
        
        // 3. Verificar banco atual
        $stmt = $db->query("SELECT DATABASE() as current_db");
        $currentDb = $stmt->fetch();
        
        echo '<div class="info">';
        echo '<strong>Banco de dados atual em uso:</strong> ' . htmlspecialchars($currentDb['current_db'] ?? 'N/A');
        echo '</div>';
        
        if ($currentDb['current_db'] !== $dbName) {
            echo '<div class="warning">';
            echo '⚠️ <strong>ATENÇÃO:</strong> O banco em uso (' . htmlspecialchars($currentDb['current_db']) . ') é diferente do configurado (' . htmlspecialchars($dbName) . ')!';
            echo '</div>';
        }
        
        echo '</div>';

        // 4. Verificar usuário admin
        echo '<div class="card">';
        echo '<h2>3. Verificação do Usuário Admin</h2>';
        
        $stmt = $db->prepare("SELECT id, email, password, status, created_at FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute(['admin@cfc.local']);
        $admin = $stmt->fetch();
        
        if ($admin) {
            echo '<div class="success">✅ Usuário admin encontrado!</div>';
            echo '<table>';
            echo '<tr><th>Campo</th><th>Valor</th></tr>';
            echo '<tr><td>ID</td><td>' . htmlspecialchars($admin['id']) . '</td></tr>';
            echo '<tr><td>Email</td><td>' . htmlspecialchars($admin['email']) . '</td></tr>';
            echo '<tr><td>Status</td><td>' . htmlspecialchars($admin['status']) . '</td></tr>';
            echo '<tr><td>Hash da Senha</td><td><pre style="word-break: break-all;">' . htmlspecialchars($admin['password']) . '</pre></td></tr>';
            echo '<tr><td>Hash válido?</td><td>' . (password_get_info($admin['password']) ? '✅ Sim (bcrypt)' : '❌ Não') . '</td></tr>';
            echo '</table>';
            
            // Testar verificação de senha
            $testPassword = 'admin123';
            $passwordValid = password_verify($testPassword, $admin['password']);
            
            echo '<div class="' . ($passwordValid ? 'success' : 'error') . '">';
            echo ($passwordValid ? '✅' : '❌') . ' <strong>Teste de senha:</strong> ';
            echo 'password_verify("admin123", hash) = ' . ($passwordValid ? 'TRUE' : 'FALSE');
            echo '</div>';
            
            if (!$passwordValid) {
                echo '<div class="error">';
                echo '❌ <strong>PROBLEMA ENCONTRADO:</strong> O hash da senha não corresponde à senha "admin123"!';
                echo '<br><br>';
                echo 'Solução: Execute o script <code>tools/reset_admin_password.php</code> para corrigir.';
                echo '</div>';
            }
            
        } else {
            echo '<div class="error">';
            echo '❌ Usuário admin@cfc.local NÃO encontrado no banco de dados!';
            echo '<br><br>';
            echo 'Solução: Execute o seed do banco de dados: <code>database/seeds/001_seed_initial_data.sql</code>';
            echo '</div>';
        }
        
        echo '</div>';

        // 5. Informações adicionais
        echo '<div class="card">';
        echo '<h2>4. Informações Adicionais</h2>';
        
        $stmt = $db->query("SELECT VERSION() as mysql_version");
        $version = $stmt->fetch();
        echo '<p><strong>Versão MySQL:</strong> ' . htmlspecialchars($version['mysql_version']) . '</p>';
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM usuarios");
        $total = $stmt->fetch();
        echo '<p><strong>Total de usuários:</strong> ' . htmlspecialchars($total['total']) . '</p>';
        
        echo '</div>';

    } catch (\Exception $e) {
        echo '<div class="card">';
        echo '<div class="error">';
        echo '❌ <strong>Erro:</strong> ' . htmlspecialchars($e->getMessage());
        echo '</div>';
        echo '</div>';
    }
    ?>

    <div class="card">
        <h2>📝 Próximos Passos</h2>
        <ol>
            <li>Se o banco atual for diferente do configurado, verifique o arquivo <code>.env</code> ou <code>app/Config/Database.php</code></li>
            <li>Se o usuário admin não existir, execute o seed: <code>database/seeds/001_seed_initial_data.sql</code></li>
            <li>Se o hash da senha estiver incorreto, execute: <code>tools/reset_admin_password.php</code></li>
            <li>Após corrigir, tente fazer login novamente</li>
        </ol>
    </div>

</body>
</html>
