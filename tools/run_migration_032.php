<?php
/**
 * Script de Execução da Migration 032 - Adicionar 'canceled' ao ENUM de billing_status
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script adiciona o valor 'canceled' ao ENUM do campo billing_status.
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

echo "=== EXECUTANDO MIGRATION 032 - ADICIONAR 'canceled' AO ENUM BILLING_STATUS ===\n\n";

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
        die("   ❌ ERRO: Tabela 'enrollments' não existe!\n");
    }
    echo "   ✅ Tabela 'enrollments' existe\n\n";
    
    // Verificar se a coluna billing_status existe e obter valores atuais do ENUM
    echo "3. Verificando coluna billing_status...\n";
    $stmt = $db->query("
        SELECT COLUMN_NAME, COLUMN_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'enrollments' 
        AND COLUMN_NAME = 'billing_status'
    ");
    $column = $stmt->fetch();
    
    if (!$column) {
        die("   ❌ ERRO: Coluna 'billing_status' não existe! Execute primeiro a migration 009.\n");
    }
    
    echo "   ✅ Coluna 'billing_status' existe\n";
    echo "   Tipo atual: " . ($column['COLUMN_TYPE'] ?? 'N/A') . "\n";
    echo "   Comentário: " . ($column['COLUMN_COMMENT'] ?? 'N/A') . "\n\n";
    
    // Verificar se 'canceled' já existe no ENUM
    $currentEnum = $column['COLUMN_TYPE'] ?? '';
    if (strpos($currentEnum, "'canceled'") !== false) {
        echo "   ℹ️  O valor 'canceled' já existe no ENUM\n";
        echo "   ✅ Migration já foi aplicada anteriormente\n\n";
        echo "✅ MIGRATION 032 JÁ ESTÁ APLICADA!\n";
        exit(0);
    }
    
    // Executar ALTER TABLE para adicionar 'canceled'
    echo "4. Adicionando 'canceled' ao ENUM de billing_status...\n";
    try {
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
        $db->exec("SET time_zone = '+00:00'");
        
        $db->exec("
            ALTER TABLE `enrollments` 
            MODIFY COLUMN `billing_status` enum('draft','ready','generated','error','canceled') 
            NOT NULL DEFAULT 'draft' 
            COMMENT 'Status da geração de cobrança no gateway de pagamento'
        ");
        
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        
        echo "   ✅ ENUM atualizado com sucesso\n\n";
    } catch (\PDOException $e) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        echo "   ❌ Erro ao atualizar ENUM: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    // Verificação final
    echo "5. Verificação final...\n";
    $stmt = $db->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'enrollments' 
        AND COLUMN_NAME = 'billing_status'
    ");
    $result = $stmt->fetch();
    
    if ($result && strpos($result['COLUMN_TYPE'], "'canceled'") !== false) {
        echo "   ✅ ENUM atualizado corretamente\n";
        echo "   Novo tipo: " . $result['COLUMN_TYPE'] . "\n\n";
        echo "✅ MIGRATION 032 EXECUTADA COM SUCESSO!\n";
        echo "\nO valor 'canceled' foi adicionado ao ENUM do campo billing_status.\n";
        echo "Valores disponíveis agora: draft, ready, generated, error, canceled\n";
    } else {
        echo "   ⚠️  ENUM pode não ter sido atualizado corretamente\n";
        echo "   Tipo encontrado: " . ($result['COLUMN_TYPE'] ?? 'N/A') . "\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
