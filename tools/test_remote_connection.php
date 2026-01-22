<?php
/**
 * Script de Teste - Conexão com Banco Remoto
 * 
 * Testa a conexão com o banco de dados remoto configurado no .env
 * 
 * Acesse via: http://localhost/cfc-v.1/tools/test_remote_connection.php
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
use App\Config\Database;
Env::load();

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Conexão - Banco Remoto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .content {
            padding: 30px;
        }
        .card {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            border-left: 4px solid #667eea;
        }
        .card h2 {
            color: #333;
            margin-bottom: 15px;
            font-size: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #dee2e6;
        }
        table th {
            background: #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        .success {
            background: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #28a745;
            margin: 15px 0;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #dc3545;
            margin: 15px 0;
        }
        .info {
            background: #d1ecf1;
            color: #0c5460;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #17a2b8;
            margin: 15px 0;
        }
        .warning {
            background: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #ffc107;
            margin: 15px 0;
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
        .badge-error {
            background: #dc3545;
            color: white;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔌 Teste de Conexão - Banco Remoto</h1>
            <p>Verificação da conexão com o banco de dados remoto</p>
        </div>
        <div class="content">
            <?php
            try {
                // 1. Verificar configuração do .env
                echo '<div class="card">';
                echo '<h2>1. Configuração do Banco de Dados</h2>';
                
                $dbHost = $_ENV['DB_HOST'] ?? 'NÃO CONFIGURADO';
                $dbPort = $_ENV['DB_PORT'] ?? '3306';
                $dbName = $_ENV['DB_NAME'] ?? 'NÃO CONFIGURADO';
                $dbUser = $_ENV['DB_USER'] ?? 'NÃO CONFIGURADO';
                $dbPass = isset($_ENV['DB_PASS']) ? '••••••••' : 'NÃO CONFIGURADO';
                
                echo '<table>';
                echo '<tr><th>Configuração</th><th>Valor</th></tr>';
                echo '<tr><td><strong>DB_HOST</strong></td><td>' . htmlspecialchars($dbHost) . '</td></tr>';
                echo '<tr><td><strong>DB_PORT</strong></td><td>' . htmlspecialchars($dbPort) . '</td></tr>';
                echo '<tr><td><strong>DB_NAME</strong></td><td>' . htmlspecialchars($dbName) . '</td></tr>';
                echo '<tr><td><strong>DB_USER</strong></td><td>' . htmlspecialchars($dbUser) . '</td></tr>';
                echo '<tr><td><strong>DB_PASS</strong></td><td>' . htmlspecialchars($dbPass) . '</td></tr>';
                echo '</table>';
                echo '</div>';

                // 2. Testar conexão
                echo '<div class="card">';
                echo '<h2>2. Teste de Conexão</h2>';
                
                $startTime = microtime(true);
                $db = Database::getInstance()->getConnection();
                $endTime = microtime(true);
                $connectionTime = round(($endTime - $startTime) * 1000, 2);
                
                echo '<div class="success">';
                echo '✅ <strong>Conexão estabelecida com sucesso!</strong><br>';
                echo 'Tempo de conexão: ' . $connectionTime . ' ms';
                echo '</div>';
                
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
                } else {
                    echo '<div class="success">';
                    echo '✅ Banco de dados corresponde à configuração!';
                    echo '</div>';
                }
                
                // 4. Verificar versão do MySQL
                $stmt = $db->query("SELECT VERSION() as version");
                $version = $stmt->fetch();
                
                echo '<div class="info">';
                echo '<strong>Versão do MySQL:</strong> ' . htmlspecialchars($version['version'] ?? 'N/A');
                echo '</div>';
                
                // 5. Listar algumas tabelas
                $stmt = $db->query("SHOW TABLES");
                $tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
                
                echo '<div class="info">';
                echo '<strong>Total de tabelas encontradas:</strong> ' . count($tables);
                if (count($tables) > 0) {
                    echo '<br><strong>Primeiras 10 tabelas:</strong> ';
                    echo implode(', ', array_slice($tables, 0, 10));
                    if (count($tables) > 10) {
                        echo ' <em>(e mais ' . (count($tables) - 10) . ')</em>';
                    }
                }
                echo '</div>';
                
                echo '</div>';

                // 6. Teste de query simples
                echo '<div class="card">';
                echo '<h2>3. Teste de Query</h2>';
                
                try {
                    // Tentar contar registros em algumas tabelas principais
                    $testTables = ['cfcs', 'usuarios', 'students', 'enrollments', 'services'];
                    $results = [];
                    
                    foreach ($testTables as $table) {
                        if (in_array($table, $tables)) {
                            try {
                                $stmt = $db->query("SELECT COUNT(*) as count FROM `{$table}`");
                                $result = $stmt->fetch();
                                $results[$table] = $result['count'];
                            } catch (\Exception $e) {
                                $results[$table] = 'Erro: ' . $e->getMessage();
                            }
                        }
                    }
                    
                    if (!empty($results)) {
                        echo '<table>';
                        echo '<tr><th>Tabela</th><th>Registros</th></tr>';
                        foreach ($results as $table => $count) {
                            echo '<tr><td><strong>' . htmlspecialchars($table) . '</strong></td><td>' . htmlspecialchars($count) . '</td></tr>';
                        }
                        echo '</table>';
                    } else {
                        echo '<div class="warning">Nenhuma tabela principal encontrada para teste.</div>';
                    }
                } catch (\Exception $e) {
                    echo '<div class="warning">Erro ao executar queries de teste: ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
                
                echo '</div>';

                // 7. Resumo final
                echo '<div class="card">';
                echo '<h2>4. Resumo</h2>';
                echo '<div class="success">';
                echo '<strong>✅ CONEXÃO REMOTA FUNCIONANDO!</strong><br><br>';
                echo 'O projeto está conectado ao banco remoto <strong>' . htmlspecialchars($dbName) . '</strong>.<br>';
                echo 'Host: <strong>' . htmlspecialchars($dbHost) . '</strong><br>';
                echo 'Total de tabelas: <strong>' . count($tables) . '</strong>';
                echo '</div>';
                echo '</div>';

            } catch (\PDOException $e) {
                echo '<div class="error">';
                echo '❌ <strong>ERRO NA CONEXÃO</strong><br><br>';
                echo '<strong>Mensagem:</strong> ' . htmlspecialchars($e->getMessage()) . '<br><br>';
                echo '<strong>Possíveis causas:</strong><br>';
                echo '• Credenciais incorretas<br>';
                echo '• Host/porta inacessível<br>';
                echo '• Firewall bloqueando a conexão<br>';
                echo '• IP não autorizado no servidor remoto<br>';
                echo '• Servidor MySQL remoto offline';
                echo '</div>';
            } catch (\Exception $e) {
                echo '<div class="error">';
                echo '❌ <strong>ERRO INESPERADO</strong><br><br>';
                echo htmlspecialchars($e->getMessage());
                echo '</div>';
            }
            ?>
        </div>
    </div>
</body>
</html>
