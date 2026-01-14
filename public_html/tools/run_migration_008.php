<?php
/**
 * Script de Execução da Migration 008 - Adicionar campos DETRAN na tabela enrollments
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Acesse via navegador: http://localhost/cfc-v.1/tools/run_migration_008.php
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
    <title>Migration 008 - Campos DETRAN</title>
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
        <h1>Migration 008 - Campos DETRAN</h1>
        <div class="output">
<?php

echo "=== EXECUTANDO MIGRATION 008 - CAMPOS DETRAN ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Verificando banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n\n";
    
    // Verificar se a tabela enrollments existe
    echo "2. Verificando tabela enrollments...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'enrollments'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'enrollments' não existe! Execute primeiro a migration 002.\n");
    }
    echo "   ✅ Tabela 'enrollments' existe\n\n";
    
    // Função para verificar se uma coluna existe
    $columnExists = function($table, $column) use ($db) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    };
    
    // Função para verificar se um índice existe
    $indexExists = function($table, $indexName) use ($db) {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND INDEX_NAME = ?
        ");
        $stmt->execute([$table, $indexName]);
        $result = $stmt->fetch();
        return $result['count'] > 0;
    };
    
    // Lista de colunas a adicionar
    $columnsToAdd = [
        ['name' => 'renach', 'type' => 'VARCHAR(20)', 'after' => 'status', 'default' => 'NULL DEFAULT NULL'],
        ['name' => 'detran_protocolo', 'type' => 'VARCHAR(50)', 'after' => 'renach', 'default' => 'NULL DEFAULT NULL'],
        ['name' => 'numero_processo', 'type' => 'VARCHAR(50)', 'after' => 'detran_protocolo', 'default' => 'NULL DEFAULT NULL'],
        ['name' => 'situacao_processo', 'type' => "ENUM('nao_iniciado','em_andamento','pendente','concluido','cancelado')", 'after' => 'numero_processo', 'default' => "NOT NULL DEFAULT 'nao_iniciado'"],
    ];
    
    echo "3. Verificando e adicionando colunas DETRAN...\n";
    $added = 0;
    $skipped = 0;
    
    foreach ($columnsToAdd as $column) {
        if ($columnExists('enrollments', $column['name'])) {
            echo "   ⏭️  Coluna '{$column['name']}' já existe, pulando...\n";
            $skipped++;
        } else {
            try {
                $sql = "ALTER TABLE `enrollments` ADD COLUMN `{$column['name']}` {$column['type']} {$column['default']} AFTER `{$column['after']}`";
                $db->exec($sql);
                echo "   ✅ Coluna '{$column['name']}' adicionada com sucesso\n";
                $added++;
            } catch (\PDOException $e) {
                echo "   ❌ Erro ao adicionar coluna '{$column['name']}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n   Resumo: {$added} colunas adicionadas, {$skipped} já existiam\n\n";
    
    // Adicionar índices
    echo "4. Verificando e adicionando índices...\n";
    
    $indexesToAdd = [
        ['name' => 'idx_renach', 'column' => 'renach'],
        ['name' => 'idx_detran_protocolo', 'column' => 'detran_protocolo'],
        ['name' => 'idx_numero_processo', 'column' => 'numero_processo'],
        ['name' => 'idx_situacao_processo', 'column' => 'situacao_processo'],
    ];
    
    $indexAdded = 0;
    $indexSkipped = 0;
    
    foreach ($indexesToAdd as $index) {
        if ($indexExists('enrollments', $index['name'])) {
            echo "   ⏭️  Índice '{$index['name']}' já existe, pulando...\n";
            $indexSkipped++;
        } else {
            // Verificar se a coluna existe antes de criar o índice
            if ($columnExists('enrollments', $index['column'])) {
                try {
                    $db->exec("ALTER TABLE `enrollments` ADD INDEX `{$index['name']}` (`{$index['column']}`)");
                    echo "   ✅ Índice '{$index['name']}' criado com sucesso\n";
                    $indexAdded++;
                } catch (\PDOException $e) {
                    echo "   ❌ Erro ao criar índice '{$index['name']}': " . $e->getMessage() . "\n";
                }
            } else {
                echo "   ⚠️  Coluna '{$index['column']}' não existe, pulando índice '{$index['name']}'\n";
            }
        }
    }
    
    echo "\n   Resumo índices: {$indexAdded} criados, {$indexSkipped} já existiam\n\n";
    
    // Verificação final
    echo "5. Verificação final...\n";
    $criticalColumns = ['renach', 'detran_protocolo', 'numero_processo', 'situacao_processo'];
    $allOk = true;
    
    foreach ($criticalColumns as $col) {
        if ($columnExists('enrollments', $col)) {
            echo "   ✅ Coluna '{$col}' existe\n";
        } else {
            echo "   ❌ Coluna '{$col}' NÃO existe!\n";
            $allOk = false;
        }
    }
    
    echo "\n";
    
    if ($allOk) {
        echo "✅ MIGRATION 008 EXECUTADA COM SUCESSO!\n";
        echo "\nOs campos DETRAN foram adicionados à tabela enrollments:\n";
        echo "- renach (VARCHAR 20)\n";
        echo "- detran_protocolo (VARCHAR 50)\n";
        echo "- numero_processo (VARCHAR 50)\n";
        echo "- situacao_processo (ENUM)\n";
        echo "\nAgora você pode usar a seção 'Processo DETRAN' nas telas de matrícula.\n";
    } else {
        echo "⚠️  MIGRATION PARCIALMENTE EXECUTADA\n";
        echo "Algumas colunas críticas não foram criadas. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

?>
        </div>
        <p><a href="javascript:location.reload()">🔄 Executar Novamente</a> | <a href="/">🏠 Voltar ao Sistema</a></p>
    </div>
</body>
</html>
