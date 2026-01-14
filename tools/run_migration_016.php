<?php
/**
 * Script para executar a migration 016 (Tabela de consultas financeiras recentes)
 * 
 * Execute via linha de comando: php tools/run_migration_016.php
 * Ou acesse via navegador: http://localhost/cfc-v.1/public_html/tools/run_migration_016.php
 */

// Inicialização
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app';

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

echo "=== EXECUTANDO MIGRATION 016 ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Ler arquivo SQL
    $sqlFile = ROOT_PATH . '/database/migrations/016_create_user_recent_financial_queries.sql';
    
    if (!file_exists($sqlFile)) {
        die("❌ Arquivo de migration não encontrado: {$sqlFile}\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    if (empty($sql)) {
        die("❌ Arquivo de migration está vazio\n");
    }
    
    echo "1. Executando SQL...\n";
    
    // Executar SQL (pode conter múltiplas queries)
    $db->exec($sql);
    
    echo "   ✅ Migration executada com sucesso!\n\n";
    
    // Verificar se a tabela foi criada
    echo "2. Verificando tabela...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'user_recent_financial_queries'");
    $tableExists = $stmt->fetch();
    
    if ($tableExists) {
        echo "   ✅ Tabela 'user_recent_financial_queries' criada com sucesso!\n\n";
        
        // Mostrar estrutura da tabela
        echo "3. Estrutura da tabela:\n";
        $stmt = $db->query("DESCRIBE user_recent_financial_queries");
        $columns = $stmt->fetchAll();
        
        foreach ($columns as $column) {
            echo "   - {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "   ⚠️  Tabela não encontrada após execução\n";
    }
    
    echo "\n✅ Migration 016 concluída!\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}