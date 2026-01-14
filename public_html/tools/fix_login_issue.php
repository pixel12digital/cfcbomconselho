<?php
/**
 * Script de Correção Automática - Problema de Login
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script:
 * 1. Verifica a configuração do banco
 * 2. Verifica qual banco está em uso
 * 3. Verifica se o admin existe
 * 4. Verifica se o hash está correto
 * 5. CORRIGE automaticamente se necessário
 */

// Inicialização
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
use App\Config\Env;
use App\Config\Database;
Env::load();

// Verificar se está em ambiente local (segurança)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false;

if (!$isLocal) {
    die('⚠️ Este script só pode ser executado em ambiente local!');
}

$adminEmail = 'admin@cfc.local';
$adminPassword = 'admin123';
$fixIssues = isset($_GET['fix']) && $_GET['fix'] === '1';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Correção Automática - Login</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1000px;
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
        h2 {
            color: #555;
            margin-top: 0;
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
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
            font-weight: 600;
        }
        .btn:hover {
            background: #218838;
        }
        .status-ok { color: #28a745; font-weight: 600; }
        .status-error { color: #dc3545; font-weight: 600; }
        .status-warning { color: #ffc107; font-weight: 600; }
    </style>
</head>
<body>
    <h1>🔧 Correção Automática - Problema de Login</h1>

    <?php
    try {
        $db = Database::getInstance()->getConnection();

        // 1. Verificar configuração
        echo '<div class="card">';
        echo '<h2>1. Configuração do Banco de Dados</h2>';
        
        $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
        $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
        $dbUser = $_ENV['DB_USER'] ?? 'root';
        
        echo '<table>';
        echo '<tr><th>Configuração</th><th>Valor</th></tr>';
        echo '<tr><td>DB_HOST</td><td>' . htmlspecialchars($dbHost) . '</td></tr>';
        echo '<tr><td>DB_NAME</td><td>' . htmlspecialchars($dbName) . '</td></tr>';
        echo '<tr><td>DB_USER</td><td>' . htmlspecialchars($dbUser) . '</td></tr>';
        echo '</table>';
        echo '</div>';

        // 2. Verificar banco atual
        echo '<div class="card">';
        echo '<h2>2. Banco de Dados em Uso</h2>';
        
        $stmt = $db->query("SELECT DATABASE() as current_db");
        $currentDb = $stmt->fetch();
        $currentDbName = $currentDb['current_db'] ?? null;
        
        if ($currentDbName === $dbName) {
            echo '<div class="success">';
            echo '✅ <strong>Banco correto:</strong> ' . htmlspecialchars($currentDbName);
            echo '</div>';
        } else {
            echo '<div class="error">';
            echo '❌ <strong>PROBLEMA:</strong> Banco em uso (' . htmlspecialchars($currentDbName ?? 'N/A') . ') é diferente do configurado (' . htmlspecialchars($dbName) . ')';
            echo '</div>';
        }
        echo '</div>';

        // 3. Verificar usuário admin
        echo '<div class="card">';
        echo '<h2>3. Verificação do Usuário Admin</h2>';
        
        $stmt = $db->prepare("SELECT id, email, password, status, created_at FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$adminEmail]);
        $admin = $stmt->fetch();
        
        $issues = [];
        $fixes = [];
        
        if (!$admin) {
            echo '<div class="error">';
            echo '❌ <strong>PROBLEMA:</strong> Usuário ' . htmlspecialchars($adminEmail) . ' não encontrado!';
            echo '</div>';
            $issues[] = 'admin_not_exists';
            
            if ($fixIssues) {
                // Verificar se CFC existe
                $stmt = $db->query("SELECT id FROM cfcs WHERE id = 1 LIMIT 1");
                $cfc = $stmt->fetch();
                if (!$cfc) {
                    $db->exec("INSERT INTO cfcs (id, nome, status) VALUES (1, 'CFC Principal', 'ativo') ON DUPLICATE KEY UPDATE nome = VALUES(nome)");
                }
                
                // Criar admin
                $newHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                $stmt = $db->prepare("
                    INSERT INTO usuarios (cfc_id, nome, email, password, status) 
                    VALUES (1, 'Administrador', ?, ?, 'ativo')
                ");
                $stmt->execute([$adminEmail, $newHash]);
                
                // Associar ao role ADMIN
                $adminId = $db->lastInsertId();
                $stmt = $db->prepare("INSERT IGNORE INTO usuario_roles (usuario_id, role) VALUES (?, 'ADMIN')");
                $stmt->execute([$adminId]);
                
                echo '<div class="success">';
                echo '✅ <strong>CORRIGIDO:</strong> Usuário admin criado com sucesso!';
                echo '</div>';
                $fixes[] = 'admin_created';
                
                // Buscar novamente
                $stmt = $db->prepare("SELECT id, email, password, status FROM usuarios WHERE email = ? LIMIT 1");
                $stmt->execute([$adminEmail]);
                $admin = $stmt->fetch();
            }
        } else {
            echo '<div class="success">';
            echo '✅ <strong>Usuário encontrado:</strong> ' . htmlspecialchars($adminEmail) . ' (ID: ' . htmlspecialchars($admin['id']) . ')';
            echo '</div>';
            
            // Verificar status
            if ($admin['status'] !== 'ativo') {
                echo '<div class="warning">';
                echo '⚠️ <strong>PROBLEMA:</strong> Status do usuário é "' . htmlspecialchars($admin['status']) . '" (deveria ser "ativo")';
                echo '</div>';
                $issues[] = 'status_inactive';
                
                if ($fixIssues) {
                    $stmt = $db->prepare("UPDATE usuarios SET status = 'ativo' WHERE email = ?");
                    $stmt->execute([$adminEmail]);
                    echo '<div class="success">';
                    echo '✅ <strong>CORRIGIDO:</strong> Status atualizado para "ativo"';
                    echo '</div>';
                    $fixes[] = 'status_fixed';
                    $admin['status'] = 'ativo';
                }
            } else {
                echo '<div class="success">';
                echo '✅ <strong>Status:</strong> ativo';
                echo '</div>';
            }
            
            // Verificar hash
            $passwordValid = password_verify($adminPassword, $admin['password']);
            
            if (!$passwordValid) {
                echo '<div class="error">';
                echo '❌ <strong>PROBLEMA:</strong> Hash da senha está incorreto!';
                echo '<br><br>';
                echo '<strong>Hash atual:</strong>';
                echo '<pre>' . htmlspecialchars($admin['password']) . '</pre>';
                echo '</div>';
                $issues[] = 'hash_invalid';
                
                if ($fixIssues) {
                    $newHash = password_hash($adminPassword, PASSWORD_DEFAULT);
                    $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
                    $stmt->execute([$newHash, $adminEmail]);
                    
                    echo '<div class="success">';
                    echo '✅ <strong>CORRIGIDO:</strong> Hash da senha atualizado!';
                    echo '<br><br>';
                    echo '<strong>Novo hash:</strong>';
                    echo '<pre>' . htmlspecialchars($newHash) . '</pre>';
                    echo '</div>';
                    $fixes[] = 'hash_fixed';
                    
                    // Verificar novamente
                    $passwordValid = password_verify($adminPassword, $newHash);
                }
            } else {
                echo '<div class="success">';
                echo '✅ <strong>Hash da senha:</strong> Válido!';
                echo '</div>';
            }
        }
        
        echo '</div>';

        // 4. Resumo e ações
        echo '<div class="card">';
        echo '<h2>4. Resumo</h2>';
        
        if (empty($issues)) {
            echo '<div class="success">';
            echo '✅ <strong>Tudo OK!</strong> Nenhum problema encontrado.';
            echo '<br><br>';
            echo 'O login deve funcionar corretamente com:';
            echo '<ul>';
            echo '<li><strong>Email:</strong> ' . htmlspecialchars($adminEmail) . '</li>';
            echo '<li><strong>Senha:</strong> ' . htmlspecialchars($adminPassword) . '</li>';
            echo '</ul>';
            echo '</div>';
        } else {
            if ($fixIssues && !empty($fixes)) {
                echo '<div class="success">';
                echo '✅ <strong>Correções aplicadas:</strong>';
                echo '<ul>';
                foreach ($fixes as $fix) {
                    $fixMessages = [
                        'admin_created' => 'Usuário admin criado',
                        'status_fixed' => 'Status atualizado para ativo',
                        'hash_fixed' => 'Hash da senha atualizado'
                    ];
                    echo '<li>' . ($fixMessages[$fix] ?? $fix) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                // Verificar novamente após correções
                $stmt = $db->prepare("SELECT id, email, password, status FROM usuarios WHERE email = ? LIMIT 1");
                $stmt->execute([$adminEmail]);
                $admin = $stmt->fetch();
                
                if ($admin) {
                    $finalCheck = password_verify($adminPassword, $admin['password']) && $admin['status'] === 'ativo';
                    
                    if ($finalCheck) {
                        echo '<div class="success">';
                        echo '✅ <strong>Validação final:</strong> Tudo corrigido e funcionando!';
                        echo '<br><br>';
                        echo 'Agora você pode fazer login com:';
                        echo '<ul>';
                        echo '<li><strong>Email:</strong> ' . htmlspecialchars($adminEmail) . '</li>';
                        echo '<li><strong>Senha:</strong> ' . htmlspecialchars($adminPassword) . '</li>';
                        echo '</ul>';
                        echo '</div>';
                    }
                }
            } else {
                echo '<div class="warning">';
                echo '⚠️ <strong>Problemas encontrados:</strong>';
                echo '<ul>';
                foreach ($issues as $issue) {
                    $issueMessages = [
                        'admin_not_exists' => 'Usuário admin não existe',
                        'status_inactive' => 'Status do usuário não é "ativo"',
                        'hash_invalid' => 'Hash da senha está incorreto'
                    ];
                    echo '<li>' . ($issueMessages[$issue] ?? $issue) . '</li>';
                }
                echo '</ul>';
                echo '</div>';
                
                echo '<div class="info">';
                echo '<strong>Para corrigir automaticamente, clique no botão abaixo:</strong>';
                echo '<br><br>';
                echo '<a href="?fix=1" class="btn">🔧 Corrigir Todos os Problemas</a>';
                echo '</div>';
            }
        }
        
        echo '</div>';

        // 5. Informações adicionais
        if ($admin) {
            echo '<div class="card">';
            echo '<h2>5. Informações do Admin</h2>';
            echo '<table>';
            echo '<tr><th>Campo</th><th>Valor</th></tr>';
            echo '<tr><td>ID</td><td>' . htmlspecialchars($admin['id']) . '</td></tr>';
            echo '<tr><td>Email</td><td>' . htmlspecialchars($admin['email']) . '</td></tr>';
            echo '<tr><td>Status</td><td><span class="status-' . ($admin['status'] === 'ativo' ? 'ok' : 'error') . '">' . htmlspecialchars($admin['status']) . '</span></td></tr>';
            echo '<tr><td>Hash Válido</td><td><span class="status-' . (password_verify($adminPassword, $admin['password']) ? 'ok' : 'error') . '">' . (password_verify($adminPassword, $admin['password']) ? 'Sim' : 'Não') . '</span></td></tr>';
            echo '</table>';
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

    <div class="card">
        <h2>📝 Próximos Passos</h2>
        <ol>
            <li>Se todas as verificações passaram, tente fazer login</li>
            <li>Se ainda houver problemas, verifique os logs do servidor</li>
            <li>Após resolver, remova este script ou proteja-o adequadamente</li>
        </ol>
        <p><a href="/cfc-v.1/public_html/login">→ Ir para Login</a></p>
    </div>

</body>
</html>
