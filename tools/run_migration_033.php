<?php
/**
 * Script para executar a migration 033 (Adicionar campos gateway_pix_code e gateway_barcode)
 * 
 * Execute via linha de comando: php tools/run_migration_033.php
 * Ou acesse via navegador: http://localhost/cfc-v.1/public_html/tools/run_migration_033.php
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

use App\Config\Database;

echo "=== EXECUTANDO MIGRATION 033 ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Ler arquivo SQL
    $sqlFile = ROOT_PATH . '/database/migrations/033_add_payment_tokens_to_enrollments.sql';
    
    if (!file_exists($sqlFile)) {
        die("❌ Arquivo de migration não encontrado: {$sqlFile}\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (empty($sql)) {
        die("❌ Arquivo de migration está vazio\n");
    }
    
    echo "1. Verificando se as colunas já existem...\n";
    
    // Verificar se as colunas já existem
    $stmt = $db->query("
        SELECT COLUMN_NAME 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'enrollments'
            AND COLUMN_NAME IN ('gateway_pix_code', 'gateway_barcode')
    ");
    $existingColumns = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    if (in_array('gateway_pix_code', $existingColumns) && in_array('gateway_barcode', $existingColumns)) {
        echo "   ⚠️  As colunas já existem. Nada a fazer.\n\n";
        echo "✅ Migration 033 já foi aplicada!\n";
        exit(0);
    }
    
    echo "2. Executando SQL...\n";
    
    // Executar SQL (pode conter múltiplas queries)
    $db->exec($sql);
    
    echo "   ✅ Migration executada com sucesso!\n\n";
    
    // Verificar se as colunas foram criadas
    echo "3. Verificando colunas...\n";
    $stmt = $db->query("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE, COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'enrollments'
            AND COLUMN_NAME IN ('gateway_pix_code', 'gateway_barcode')
        ORDER BY COLUMN_NAME
    ");
    $columns = $stmt->fetchAll();
    
    if (empty($columns)) {
        echo "   ⚠️  Colunas não encontradas após execução\n";
    } else {
        foreach ($columns as $column) {
            $length = $column['CHARACTER_MAXIMUM_LENGTH'] ? "({$column['CHARACTER_MAXIMUM_LENGTH']})" : '';
            $nullable = $column['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
            $comment = $column['COLUMN_COMMENT'] ? " - {$column['COLUMN_COMMENT']}" : '';
            echo "   ✅ {$column['COLUMN_NAME']}: {$column['DATA_TYPE']}{$length} {$nullable}{$comment}\n";
        }
    }
    
    echo "\n✅ Migration 033 concluída!\n";
    echo "\nPróximos passos:\n";
    echo "1. Execute: php tools/check_duplicates_before_unique.php\n";
    echo "2. Se houver duplicados, corrija-os\n";
    echo "3. Execute: php tools/apply_unique_constraint.php\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
