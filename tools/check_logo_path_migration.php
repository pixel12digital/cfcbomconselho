<?php
/**
 * Script para verificar se a migration 034 foi executada
 * Verifica se o campo logo_path existe na tabela cfcs
 * Execute: php tools/check_logo_path_migration.php
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

echo "=== VERIFICANDO MIGRATION 034: logo_path na tabela cfcs ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Informações da conexão:\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n";
    echo "   Host: " . ($_ENV['DB_HOST'] ?? 'N/A') . "\n";
    echo "   Porta: " . ($_ENV['DB_PORT'] ?? '3306') . "\n\n";
    
    // Verificar se a tabela cfcs existe
    echo "2. Verificando tabela cfcs...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'cfcs'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'cfcs' não existe!\n");
    }
    echo "   ✅ Tabela 'cfcs' existe\n\n";
    
    // Verificar estrutura da tabela cfcs
    echo "3. Verificando estrutura da tabela cfcs...\n";
    $stmt = $db->query("DESCRIBE cfcs");
    $columns = $stmt->fetchAll();
    
    $columnNames = array_column($columns, 'Field');
    echo "   Colunas encontradas (" . count($columnNames) . "):\n";
    foreach ($columnNames as $col) {
        echo "     - {$col}\n";
    }
    echo "\n";
    
    // Verificar especificamente o campo logo_path
    echo "4. Verificando campo logo_path...\n";
    $logoPathExists = in_array('logo_path', $columnNames);
    
    if ($logoPathExists) {
        echo "   ✅ Campo 'logo_path' EXISTE na tabela cfcs\n\n";
        
        // Buscar detalhes do campo
        $stmt = $db->query("SHOW COLUMNS FROM cfcs WHERE Field = 'logo_path'");
        $columnInfo = $stmt->fetch();
        
        if ($columnInfo) {
            echo "   Detalhes do campo logo_path:\n";
            echo "     - Tipo: " . ($columnInfo['Type'] ?? 'N/A') . "\n";
            echo "     - Null: " . ($columnInfo['Null'] ?? 'N/A') . "\n";
            echo "     - Default: " . ($columnInfo['Default'] ?? 'NULL') . "\n";
            echo "     - Extra: " . ($columnInfo['Extra'] ?? 'N/A') . "\n\n";
        }
        
        // Verificar se há dados no campo
        echo "5. Verificando dados no campo logo_path...\n";
        $stmt = $db->query("SELECT COUNT(*) as total, COUNT(logo_path) as com_logo FROM cfcs");
        $stats = $stmt->fetch();
        echo "   Total de registros na tabela cfcs: " . ($stats['total'] ?? 0) . "\n";
        echo "   Registros com logo_path preenchido: " . ($stats['com_logo'] ?? 0) . "\n";
        
        if ($stats['com_logo'] > 0) {
            echo "\n   Exemplos de logo_path cadastrados:\n";
            $stmt = $db->query("SELECT id, nome, logo_path FROM cfcs WHERE logo_path IS NOT NULL LIMIT 5");
            $examples = $stmt->fetchAll();
            foreach ($examples as $example) {
                echo "     - CFC ID {$example['id']} ({$example['nome']}): {$example['logo_path']}\n";
            }
        }
        
        echo "\n✅ MIGRATION 034 FOI EXECUTADA COM SUCESSO!\n";
        echo "   O campo logo_path está presente na tabela cfcs.\n";
        
    } else {
        echo "   ❌ Campo 'logo_path' NÃO EXISTE na tabela cfcs\n\n";
        echo "   ⚠️  A migration 034 NÃO FOI EXECUTADA!\n";
        echo "   Execute a migration: database/migrations/034_add_logo_path_to_cfcs.sql\n\n";
        
        // Mostrar SQL da migration
        $migrationFile = ROOT_PATH . '/database/migrations/034_add_logo_path_to_cfcs.sql';
        if (file_exists($migrationFile)) {
            echo "   SQL da migration:\n";
            echo "   " . str_repeat("-", 60) . "\n";
            $migrationSQL = file_get_contents($migrationFile);
            $lines = explode("\n", $migrationSQL);
            foreach ($lines as $line) {
                if (trim($line) && !preg_match('/^--/', $line)) {
                    echo "   " . trim($line) . "\n";
                }
            }
            echo "   " . str_repeat("-", 60) . "\n";
        }
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "   Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== VERIFICAÇÃO CONCLUÍDA ===\n";
