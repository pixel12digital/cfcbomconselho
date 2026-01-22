<?php
/**
 * Script para consultar informações do banco de dados remoto
 * Inclui verificação de credenciais do admin
 * 
 * Acesse via: http://localhost/cfc-v.1/tools/consultar_banco_remoto.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Env;
use App\Config\Database;

// Carregar variáveis de ambiente
Env::load();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Consulta - Banco Remoto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 25px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            color: #667eea;
            margin-bottom: 20px;
            font-size: 20px;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #495057;
        }
        table tr:hover {
            background: #f8f9fa;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #28a745;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #dc3545;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #17a2b8;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 5px;
            margin: 10px 0;
            border-left: 4px solid #ffc107;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            overflow-x: auto;
            font-size: 12px;
            word-break: break-all;
        }
        .badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-success {
            background: #28a745;
            color: white;
        }
        .badge-danger {
            background: #dc3545;
            color: white;
        }
        .badge-warning {
            background: #ffc107;
            color: #333;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Consulta ao Banco de Dados Remoto</h1>
            <p>Verificação de configurações e credenciais do administrador</p>
        </div>

        <?php
        try {
            // 1. Informações de Conexão
            echo '<div class="card">';
            echo '<h2>1. Configuração de Conexão</h2>';
            
            $dbHost = $_ENV['DB_HOST'] ?? 'localhost';
            $dbPort = $_ENV['DB_PORT'] ?? '3306';
            $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
            $dbUser = $_ENV['DB_USER'] ?? 'root';
            $dbPass = $_ENV['DB_PASS'] ?? '';
            
            $isRemote = !in_array(strtolower($dbHost), ['localhost', '127.0.0.1', '::1']);
            
            echo '<table>';
            echo '<tr><th>Configuração</th><th>Valor</th></tr>';
            echo '<tr><td><strong>DB_HOST</strong></td><td>' . htmlspecialchars($dbHost) . ' ' . ($isRemote ? '<span class="badge badge-warning">REMOTO</span>' : '<span class="badge badge-success">LOCAL</span>') . '</td></tr>';
            echo '<tr><td><strong>DB_PORT</strong></td><td>' . htmlspecialchars($dbPort) . '</td></tr>';
            echo '<tr><td><strong>DB_NAME</strong></td><td>' . htmlspecialchars($dbName) . '</td></tr>';
            echo '<tr><td><strong>DB_USER</strong></td><td>' . htmlspecialchars($dbUser) . '</td></tr>';
            echo '<tr><td><strong>DB_PASS</strong></td><td>' . (!empty($dbPass) ? '•••••••• (' . strlen($dbPass) . ' caracteres)' : '<em>vazio</em>') . '</td></tr>';
            echo '</table>';
            echo '</div>';

            // 2. Testar Conexão
            echo '<div class="card">';
            echo '<h2>2. Status da Conexão</h2>';
            
            $startTime = microtime(true);
            $db = Database::getInstance()->getConnection();
            $endTime = microtime(true);
            $connectionTime = round(($endTime - $startTime) * 1000, 2);
            
            echo '<div class="success">';
            echo '✅ <strong>Conexão estabelecida com sucesso!</strong><br>';
            echo 'Tempo de conexão: ' . $connectionTime . ' ms';
            echo '</div>';
            
            // Informações do servidor
            $stmt = $db->query("SELECT 
                DATABASE() as current_db,
                VERSION() as mysql_version,
                USER() as current_user,
                @@hostname as server_hostname,
                NOW() as server_time
            ");
            $serverInfo = $stmt->fetch();
            
            echo '<table>';
            echo '<tr><th>Informação</th><th>Valor</th></tr>';
            echo '<tr><td>Banco de dados atual</td><td><strong>' . htmlspecialchars($serverInfo['current_db'] ?? 'N/A') . '</strong></td></tr>';
            echo '<tr><td>Versão MySQL</td><td>' . htmlspecialchars($serverInfo['mysql_version'] ?? 'N/A') . '</td></tr>';
            echo '<tr><td>Usuário conectado</td><td>' . htmlspecialchars($serverInfo['current_user'] ?? 'N/A') . '</td></tr>';
            echo '<tr><td>Hostname do servidor</td><td>' . htmlspecialchars($serverInfo['server_hostname'] ?? 'N/A') . '</td></tr>';
            echo '<tr><td>Data/Hora do servidor</td><td>' . htmlspecialchars($serverInfo['server_time'] ?? 'N/A') . '</td></tr>';
            echo '</table>';
            
            if ($serverInfo['current_db'] !== $dbName) {
                echo '<div class="warning">';
                echo '⚠️ <strong>ATENÇÃO:</strong> O banco em uso (' . htmlspecialchars($serverInfo['current_db']) . ') é diferente do configurado (' . htmlspecialchars($dbName) . ')!';
                echo '</div>';
            }
            
            echo '</div>';

            // 3. Verificar Usuário Admin
            echo '<div class="card">';
            echo '<h2>3. Credenciais do Administrador</h2>';
            
            $stmt = $db->prepare("
                SELECT 
                    u.id,
                    u.email,
                    u.password,
                    u.status,
                    u.created_at,
                    GROUP_CONCAT(r.nome SEPARATOR ', ') as roles
                FROM usuarios u
                LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
                LEFT JOIN roles r ON r.role = ur.role
                WHERE u.email = ?
                GROUP BY u.id
                LIMIT 1
            ");
            $stmt->execute(['admin@cfc.local']);
            $admin = $stmt->fetch();
            
            if ($admin) {
                echo '<div class="success">✅ Usuário administrador encontrado!</div>';
                
                echo '<table>';
                echo '<tr><th>Campo</th><th>Valor</th></tr>';
                echo '<tr><td>ID</td><td>' . htmlspecialchars($admin['id']) . '</td></tr>';
                echo '<tr><td>Email</td><td><strong>' . htmlspecialchars($admin['email']) . '</strong></td></tr>';
                echo '<tr><td>Status</td><td><span class="badge ' . ($admin['status'] === 'ativo' ? 'badge-success' : 'badge-danger') . '">' . htmlspecialchars($admin['status']) . '</span></td></tr>';
                echo '<tr><td>Roles/Papéis</td><td>' . htmlspecialchars($admin['roles'] ?? 'Nenhum') . '</td></tr>';
                echo '<tr><td>Data de criação</td><td>' . htmlspecialchars($admin['created_at'] ?? 'N/A') . '</td></tr>';
                echo '<tr><td>Hash da senha</td><td><pre>' . htmlspecialchars($admin['password']) . '</pre></td></tr>';
                
                // Verificar tipo de hash
                $hashInfo = password_get_info($admin['password']);
                if ($hashInfo) {
                    echo '<tr><td>Tipo de hash</td><td><span class="badge badge-success">' . htmlspecialchars($hashInfo['algoName']) . '</span> (custo: ' . $hashInfo['options']['cost'] . ')</td></tr>';
                } else {
                    echo '<tr><td>Tipo de hash</td><td><span class="badge badge-danger">Inválido ou não bcrypt</span></td></tr>';
                }
                
                echo '</table>';
                
                // Testar senha padrão
                $testPassword = 'admin123';
                $passwordValid = password_verify($testPassword, $admin['password']);
                
                echo '<div class="' . ($passwordValid ? 'success' : 'error') . '">';
                echo ($passwordValid ? '✅' : '❌') . ' <strong>Teste de senha padrão:</strong> ';
                echo 'password_verify("admin123", hash) = <strong>' . ($passwordValid ? 'TRUE' : 'FALSE') . '</strong>';
                if ($passwordValid) {
                    echo '<br><small>As credenciais padrão funcionam: <strong>admin@cfc.local</strong> / <strong>admin123</strong></small>';
                } else {
                    echo '<br><small>A senha padrão não corresponde ao hash armazenado. A senha pode ter sido alterada.</small>';
                }
                echo '</div>';
                
                if ($admin['status'] !== 'ativo') {
                    echo '<div class="warning">';
                    echo '⚠️ <strong>ATENÇÃO:</strong> O usuário não está ativo. Isso pode impedir o login.';
                    echo '</div>';
                }
                
            } else {
                echo '<div class="error">';
                echo '❌ <strong>Usuário administrador não encontrado!</strong><br>';
                echo 'O email <strong>admin@cfc.local</strong> não existe no banco de dados.';
                echo '</div>';
                echo '<div class="info">';
                echo '💡 <strong>Solução:</strong> Execute o seed do banco de dados:<br>';
                echo '<code>database/seeds/001_seed_initial_data.sql</code>';
                echo '</div>';
            }
            
            echo '</div>';

            // 4. Listar todos os usuários admin
            echo '<div class="card">';
            echo '<h2>4. Todos os Usuários Administradores</h2>';
            
            $stmt = $db->query("
                SELECT 
                    u.id,
                    u.email,
                    u.status,
                    u.created_at,
                    GROUP_CONCAT(r.nome SEPARATOR ', ') as roles
                FROM usuarios u
                LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
                LEFT JOIN roles r ON r.role = ur.role
                WHERE r.role = 'admin' OR u.email LIKE '%admin%'
                GROUP BY u.id
                ORDER BY u.id
            ");
            $admins = $stmt->fetchAll();
            
            if (count($admins) > 0) {
                echo '<table>';
                echo '<tr><th>ID</th><th>Email</th><th>Status</th><th>Roles</th><th>Criado em</th></tr>';
                foreach ($admins as $adm) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($adm['id']) . '</td>';
                    echo '<td><strong>' . htmlspecialchars($adm['email']) . '</strong></td>';
                    echo '<td><span class="badge ' . ($adm['status'] === 'ativo' ? 'badge-success' : 'badge-danger') . '">' . htmlspecialchars($adm['status']) . '</span></td>';
                    echo '<td>' . htmlspecialchars($adm['roles'] ?? 'Nenhum') . '</td>';
                    echo '<td>' . htmlspecialchars($adm['created_at'] ?? 'N/A') . '</td>';
                    echo '</tr>';
                }
                echo '</table>';
            } else {
                echo '<div class="info">Nenhum usuário administrador encontrado.</div>';
            }
            
            echo '</div>';

            // 5. Estatísticas do banco
            echo '<div class="card">';
            echo '<h2>5. Estatísticas do Banco de Dados</h2>';
            
            $tables = ['usuarios', 'cfcs', 'students', 'enrollments', 'services', 'instructors'];
            echo '<table>';
            echo '<tr><th>Tabela</th><th>Registros</th></tr>';
            
            foreach ($tables as $table) {
                try {
                    $stmt = $db->query("SELECT COUNT(*) as count FROM `{$table}`");
                    $result = $stmt->fetch();
                    $count = $result['count'] ?? 0;
                    echo '<tr><td>' . htmlspecialchars($table) . '</td><td><strong>' . number_format($count, 0, ',', '.') . '</strong></td></tr>';
                } catch (\Exception $e) {
                    echo '<tr><td>' . htmlspecialchars($table) . '</td><td><span style="color: #dc3545;">Tabela não existe</span></td></tr>';
                }
            }
            
            echo '</table>';
            echo '</div>';

        } catch (\Exception $e) {
            echo '<div class="card">';
            echo '<div class="error">';
            echo '❌ <strong>Erro ao conectar ao banco de dados:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
            echo '</div>';
        }
        ?>

        <div class="card">
            <div class="info">
                <strong>ℹ️ Informações:</strong><br>
                • Este script consulta o banco de dados remoto configurado no arquivo <code>.env</code><br>
                • As credenciais do banco estão em: <code>app/Config/Database.php</code><br>
                • As credenciais do admin estão em: <code>app/Config/Credentials.php</code>
            </div>
        </div>

    </div>
</body>
</html>
