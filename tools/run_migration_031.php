<?php
/**
 * Script de Execução da Migration 031 - Adicionar campo gateway_payment_url
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script adiciona o campo gateway_payment_url na tabela enrollments.
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

// Verificar se está em ambiente local (segurança)
$isLocal = in_array($_SERVER['HTTP_HOST'] ?? '', ['localhost', '127.0.0.1']) || 
           strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ||
           (php_sapi_name() === 'cli');

if (!$isLocal && php_sapi_name() !== 'cli') {
    die('⚠️ Este script só pode ser executado em ambiente local!');
}

echo "=== EXECUTANDO MIGRATION 031 - ADICIONAR GATEWAY_PAYMENT_URL ===\n\n";

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
        die("   ❌ ERRO: Tabela 'enrollments' não existe! Execute as migrations anteriores primeiro.\n");
    }
    echo "   ✅ Tabela 'enrollments' existe\n\n";
    
    // Verificar se a coluna já existe
    echo "3. Verificando se coluna gateway_payment_url já existe...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'enrollments'
        AND COLUMN_NAME = 'gateway_payment_url'
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] > 0) {
        echo "   ⚠️ Coluna 'gateway_payment_url' já existe. Pulando migration.\n\n";
        echo "✅ Migration 031 já foi executada anteriormente.\n";
        exit(0);
    }
    
    echo "   ✅ Coluna não existe, prosseguindo...\n\n";
    
    // Verificar se gateway_last_event_at existe (coluna anterior)
    echo "4. Verificando coluna gateway_last_event_at (referência)...\n";
    $stmt = $db->query("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'enrollments'
        AND COLUMN_NAME = 'gateway_last_event_at'
    ");
    $result = $stmt->fetch();
    
    if ($result['count'] === 0) {
        echo "   ⚠️ AVISO: Coluna 'gateway_last_event_at' não existe.\n";
        echo "   A coluna será adicionada após a última coluna da tabela.\n\n";
        $afterColumn = '';
    } else {
        echo "   ✅ Coluna 'gateway_last_event_at' existe\n\n";
        $afterColumn = 'AFTER `gateway_last_event_at`';
    }
    
    // Executar migration
    echo "5. Adicionando coluna gateway_payment_url...\n";
    
    $sql = "ALTER TABLE `enrollments`
            ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
            COMMENT 'URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway'";
    
    if ($afterColumn) {
        $sql .= " " . $afterColumn;
    }
    
    $db->exec($sql);
    echo "   ✅ Coluna 'gateway_payment_url' adicionada com sucesso\n\n";
    
    // Verificar se foi criada corretamente
    echo "6. Verificando criação da coluna...\n";
    $stmt = $db->query("
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'enrollments'
        AND COLUMN_NAME = 'gateway_payment_url'
    ");
    $column = $stmt->fetch();
    
    if ($column) {
        echo "   ✅ Coluna criada corretamente:\n";
        echo "      - Nome: {$column['COLUMN_NAME']}\n";
        echo "      - Tipo: {$column['DATA_TYPE']}\n";
        echo "      - Null: {$column['IS_NULLABLE']}\n";
        echo "      - Default: " . ($column['COLUMN_DEFAULT'] ?? 'NULL') . "\n";
        echo "      - Comentário: {$column['COLUMN_COMMENT']}\n\n";
    } else {
        die("   ❌ ERRO: Coluna não foi criada corretamente!\n");
    }
    
    echo "✅ Migration 031 executada com sucesso!\n\n";
    echo "A coluna 'gateway_payment_url' foi adicionada à tabela 'enrollments'.\n";
    
} catch (Exception $e) {
    echo "\n❌ ERRO ao executar migration:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    exit(1);
}
