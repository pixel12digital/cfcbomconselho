<?php
/**
 * Script de Execução da Migration 034 - Adicionar campo logo_path na tabela cfcs
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
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

echo "=== EXECUTANDO MIGRATION 034 - ADICIONAR LOGO_PATH NA TABELA CFCS ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Verificando banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n\n";
    
    // Verificar se a tabela cfcs existe
    echo "2. Verificando tabela cfcs...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'cfcs'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'cfcs' não existe!\n");
    }
    echo "   ✅ Tabela 'cfcs' existe\n\n";
    
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
    
    // Verificar se coluna logo_path já existe
    echo "3. Verificando coluna logo_path...\n";
    if ($columnExists('cfcs', 'logo_path')) {
        echo "   ⏭️  Coluna 'logo_path' já existe, pulando...\n\n";
    } else {
        echo "   Adicionando coluna 'logo_path'...\n";
        try {
            $sql = "ALTER TABLE `cfcs` 
                    ADD COLUMN `logo_path` VARCHAR(255) DEFAULT NULL 
                    COMMENT 'Caminho do arquivo de logo do CFC (para ícones PWA)' 
                    AFTER `email`";
            $db->exec($sql);
            echo "   ✅ Coluna 'logo_path' adicionada com sucesso\n\n";
        } catch (\PDOException $e) {
            echo "   ❌ Erro ao adicionar coluna 'logo_path': " . $e->getMessage() . "\n\n";
            exit(1);
        }
    }
    
    // Verificação final
    echo "4. Verificação final...\n";
    if ($columnExists('cfcs', 'logo_path')) {
        echo "   ✅ Coluna 'logo_path' existe\n\n";
        echo "✅ MIGRATION 034 EXECUTADA COM SUCESSO!\n";
        echo "\nO campo logo_path foi adicionado à tabela cfcs.\n";
        echo "Agora você pode fazer upload de logos por CFC para personalizar os ícones PWA.\n";
    } else {
        echo "   ❌ Coluna 'logo_path' NÃO existe!\n\n";
        echo "⚠️  MIGRATION FALHOU\n";
        exit(1);
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
