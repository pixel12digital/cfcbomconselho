<?php
/**
 * Script de Execução da Migration 016 - Tabela de consultas financeiras recentes
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Acesse via navegador: http://localhost/cfc-v.1/public_html/tools/run_migration_016.php
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
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
           (php_sapi_name() === 'cli');

if (!$isLocal && php_sapi_name() !== 'cli') {
    die('⚠️ Este script só pode ser executado em ambiente local!');
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Migration 016 - Consultas Financeiras Recentes</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .output {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            white-space: pre-wrap;
            max-height: 600px;
            overflow-y: auto;
            margin: 20px 0;
        }
        .success { color: #28a745; font-weight: bold; }
        .error { color: #dc3545; font-weight: bold; }
        .warning { color: #ffc107; }
        .info { color: #17a2b8; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Migration 016 - Consultas Financeiras Recentes</h1>
        <div class="output">
<?php

echo "=== EXECUTANDO MIGRATION 016 ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Verificando banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n\n";
    
    // Verificar se a tabela já existe
    echo "2. Verificando se a tabela já existe...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'user_recent_financial_queries'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ Tabela 'user_recent_financial_queries' já existe!\n\n";
        echo "   Estrutura atual:\n";
        $stmt = $db->query("DESCRIBE user_recent_financial_queries");
        $columns = $stmt->fetchAll();
        foreach ($columns as $column) {
            echo "   - {$column['Field']} ({$column['Type']})\n";
        }
        echo "\n✅ Tabela já existe. Nada a fazer.\n";
    } else {
        echo "   ⏭️  Tabela não existe. Criando...\n\n";
        
        // Ler arquivo SQL
        $sqlFile = ROOT_PATH . '/database/migrations/016_create_user_recent_financial_queries.sql';
        
        if (!file_exists($sqlFile)) {
            die("   ❌ Arquivo de migration não encontrado: {$sqlFile}\n");
        }
        
        $sql = file_get_contents($sqlFile);
        
        if (empty($sql)) {
            die("   ❌ Arquivo de migration está vazio\n");
        }
        
        echo "3. Executando SQL...\n";
        
        // Executar SQL
        $db->exec($sql);
        
        echo "   ✅ SQL executado com sucesso!\n\n";
        
        // Verificar se a tabela foi criada
        echo "4. Verificando tabela...\n";
        $stmt = $db->query("SHOW TABLES LIKE 'user_recent_financial_queries'");
        $tableExists = $stmt->fetch();
        
        if ($tableExists) {
            echo "   ✅ Tabela 'user_recent_financial_queries' criada com sucesso!\n\n";
            
            // Mostrar estrutura da tabela
            echo "5. Estrutura da tabela:\n";
            $stmt = $db->query("DESCRIBE user_recent_financial_queries");
            $columns = $stmt->fetchAll();
            
            foreach ($columns as $column) {
                echo "   - {$column['Field']} ({$column['Type']})";
                if ($column['Null'] === 'NO') {
                    echo " NOT NULL";
                }
                if ($column['Key'] === 'PRI') {
                    echo " PRIMARY KEY";
                }
                if ($column['Key'] === 'UNI') {
                    echo " UNIQUE";
                }
                if ($column['Extra']) {
                    echo " {$column['Extra']}";
                }
                echo "\n";
            }
            
            echo "\n✅ Migration 016 concluída com sucesso!\n";
            echo "\nA tabela 'user_recent_financial_queries' foi criada.\n";
            echo "Agora o card 'Recentes' na tela de Consulta Financeira funcionará corretamente.\n";
        } else {
            echo "   ⚠️  Tabela não encontrada após execução\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "\n✅ A tabela já existe no banco de dados.\n";
    } else {
        echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    }
}

?>
        </div>
        <p><a href="javascript:location.reload()">🔄 Executar Novamente</a> | <a href="/">🏠 Voltar ao Sistema</a></p>
    </div>
</body>
</html>