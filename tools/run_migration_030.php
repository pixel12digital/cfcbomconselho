<?php
/**
 * Script de Execução da Migration 030 - Adicionar campos genéricos do gateway
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script adiciona campos genéricos para rastreamento do gateway de pagamento.
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

echo "=== EXECUTANDO MIGRATION 030 - CAMPOS GENÉRICOS DO GATEWAY ===\n\n";

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
        ['name' => 'gateway_provider', 'type' => 'VARCHAR(50)', 'after' => 'billing_status', 'default' => 'DEFAULT NULL COMMENT \'Provedor do gateway (efi, asaas, etc)\''],
        ['name' => 'gateway_charge_id', 'type' => 'VARCHAR(255)', 'after' => 'gateway_provider', 'default' => 'DEFAULT NULL COMMENT \'ID da cobrança no gateway\''],
        ['name' => 'gateway_last_status', 'type' => 'VARCHAR(50)', 'after' => 'gateway_charge_id', 'default' => 'DEFAULT NULL COMMENT \'Último status recebido do gateway\''],
        ['name' => 'gateway_last_event_at', 'type' => 'DATETIME', 'after' => 'gateway_last_status', 'default' => 'DEFAULT NULL COMMENT \'Data/hora do último evento recebido do gateway\''],
    ];
    
    echo "3. Verificando e adicionando colunas do gateway...\n";
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
        ['name' => 'gateway_provider', 'column' => 'gateway_provider'],
        ['name' => 'gateway_charge_id', 'column' => 'gateway_charge_id'],
        ['name' => 'gateway_last_event_at', 'column' => 'gateway_last_event_at'],
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
                    $db->exec("ALTER TABLE `enrollments` ADD KEY `{$index['name']}` (`{$index['column']}`)");
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
    $criticalColumns = ['gateway_provider', 'gateway_charge_id', 'gateway_last_status', 'gateway_last_event_at'];
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
        echo "✅ MIGRATION 030 EXECUTADA COM SUCESSO!\n";
        echo "\nOs campos genéricos do gateway foram adicionados à tabela enrollments:\n";
        echo "- gateway_provider (VARCHAR) - Provedor do gateway\n";
        echo "- gateway_charge_id (VARCHAR) - ID da cobrança no gateway\n";
        echo "- gateway_last_status (VARCHAR) - Último status recebido\n";
        echo "- gateway_last_event_at (DATETIME) - Data/hora do último evento\n";
        echo "\nAgora você pode rastrear informações do gateway de pagamento.\n";
    } else {
        echo "⚠️  MIGRATION PARCIALMENTE EXECUTADA\n";
        echo "Algumas colunas críticas não foram criadas. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
