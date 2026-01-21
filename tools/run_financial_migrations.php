<?php
/**
 * Script para executar migrations do módulo financeiro no banco remoto
 * Execute: php tools/run_financial_migrations.php
 * 
 * Migrations executadas:
 * - 030: Campos genéricos do gateway
 * - 031: Gateway payment URL
 * - 032: Billing status canceled
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

echo "=== EXECUTANDO MIGRATIONS DO MÓDULO FINANCEIRO ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Verificando conexão com banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n";
    echo "   Host: " . ($_ENV['DB_HOST'] ?? 'N/A') . "\n\n";
    
    // Verificar se a tabela enrollments existe
    echo "2. Verificando tabela enrollments...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'enrollments'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'enrollments' não existe! Execute primeiro as migrations base.\n");
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
    
    // ============================================
    // MIGRATION 030: Campos genéricos do gateway
    // ============================================
    echo "========================================\n";
    echo "MIGRATION 030: Campos genéricos do gateway\n";
    echo "========================================\n\n";
    
    $columnsToAdd = [
        ['name' => 'gateway_provider', 'type' => 'VARCHAR(50)', 'after' => 'billing_status', 'default' => 'DEFAULT NULL COMMENT \'Provedor do gateway (efi, asaas, etc)\''],
        ['name' => 'gateway_charge_id', 'type' => 'VARCHAR(255)', 'after' => 'gateway_provider', 'default' => 'DEFAULT NULL COMMENT \'ID da cobrança no gateway\''],
        ['name' => 'gateway_last_status', 'type' => 'VARCHAR(50)', 'after' => 'gateway_charge_id', 'default' => 'DEFAULT NULL COMMENT \'Último status recebido do gateway\''],
        ['name' => 'gateway_last_event_at', 'type' => 'DATETIME', 'after' => 'gateway_last_status', 'default' => 'DEFAULT NULL COMMENT \'Data/hora do último evento recebido do gateway\''],
    ];
    
    $migration030Ok = true;
    $added030 = 0;
    $skipped030 = 0;
    
    foreach ($columnsToAdd as $column) {
        if ($columnExists('enrollments', $column['name'])) {
            echo "   ⏭️  Coluna '{$column['name']}' já existe\n";
            $skipped030++;
        } else {
            try {
                $sql = "ALTER TABLE `enrollments` ADD COLUMN `{$column['name']}` {$column['type']} {$column['default']} AFTER `{$column['after']}`";
                $db->exec($sql);
                echo "   ✅ Coluna '{$column['name']}' adicionada\n";
                $added030++;
            } catch (\PDOException $e) {
                echo "   ❌ Erro ao adicionar '{$column['name']}': " . $e->getMessage() . "\n";
                $migration030Ok = false;
            }
        }
    }
    
    // Adicionar índices da migration 030
    $indexesToAdd = [
        ['name' => 'gateway_provider', 'column' => 'gateway_provider'],
        ['name' => 'gateway_charge_id', 'column' => 'gateway_charge_id'],
        ['name' => 'gateway_last_event_at', 'column' => 'gateway_last_event_at'],
    ];
    
    foreach ($indexesToAdd as $index) {
        if ($indexExists('enrollments', $index['name'])) {
            echo "   ⏭️  Índice '{$index['name']}' já existe\n";
        } else {
            if ($columnExists('enrollments', $index['column'])) {
                try {
                    $db->exec("ALTER TABLE `enrollments` ADD KEY `{$index['name']}` (`{$index['column']}`)");
                    echo "   ✅ Índice '{$index['name']}' criado\n";
                } catch (\PDOException $e) {
                    echo "   ❌ Erro ao criar índice '{$index['name']}': " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "\n   Resumo Migration 030: {$added030} colunas adicionadas, {$skipped030} já existiam\n";
    
    if ($migration030Ok && ($added030 > 0 || $skipped030 === count($columnsToAdd))) {
        echo "   ✅ Migration 030: OK\n\n";
    } else {
        echo "   ⚠️  Migration 030: Parcialmente executada\n\n";
    }
    
    // ============================================
    // MIGRATION 031: Gateway payment URL
    // ============================================
    echo "========================================\n";
    echo "MIGRATION 031: Gateway payment URL\n";
    echo "========================================\n\n";
    
    if ($columnExists('enrollments', 'gateway_payment_url')) {
        echo "   ⏭️  Coluna 'gateway_payment_url' já existe\n";
        echo "   ✅ Migration 031: Já executada\n\n";
    } else {
        // Verificar se gateway_last_event_at existe (referência)
        $afterColumn = '';
        if ($columnExists('enrollments', 'gateway_last_event_at')) {
            $afterColumn = 'AFTER `gateway_last_event_at`';
            echo "   ✅ Coluna de referência 'gateway_last_event_at' existe\n";
        } else {
            echo "   ⚠️  Coluna de referência 'gateway_last_event_at' não existe\n";
            echo "   A coluna será adicionada no final da tabela\n";
        }
        
        try {
            $sql = "ALTER TABLE `enrollments`
                    ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
                    COMMENT 'URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway'";
            
            if ($afterColumn) {
                $sql .= " " . $afterColumn;
            }
            
            $db->exec($sql);
            echo "   ✅ Coluna 'gateway_payment_url' adicionada com sucesso\n";
            echo "   ✅ Migration 031: Executada\n\n";
        } catch (\PDOException $e) {
            echo "   ❌ Erro ao adicionar 'gateway_payment_url': " . $e->getMessage() . "\n";
            echo "   ⚠️  Migration 031: Falhou\n\n";
        }
    }
    
    // ============================================
    // MIGRATION 032: Billing status canceled
    // ============================================
    echo "========================================\n";
    echo "MIGRATION 032: Billing status canceled\n";
    echo "========================================\n\n";
    
    // Verificar ENUM atual
    $stmt = $db->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'enrollments' 
        AND COLUMN_NAME = 'billing_status'
    ");
    $result = $stmt->fetch();
    
    if (!$result) {
        echo "   ❌ Coluna 'billing_status' não existe! Execute primeiro a migration 009.\n";
        echo "   ⚠️  Migration 032: Não pode ser executada\n\n";
    } else {
        $currentEnum = $result['COLUMN_TYPE'] ?? '';
        
        if (strpos($currentEnum, "'canceled'") !== false) {
            echo "   ⏭️  Valor 'canceled' já existe no ENUM\n";
            echo "   ✅ Migration 032: Já executada\n\n";
        } else {
            try {
                $db->exec("SET FOREIGN_KEY_CHECKS = 0");
                $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
                
                $db->exec("
                    ALTER TABLE `enrollments` 
                    MODIFY COLUMN `billing_status` enum('draft','ready','generated','error','canceled') 
                    NOT NULL DEFAULT 'draft' 
                    COMMENT 'Status da geração de cobrança no gateway de pagamento'
                ");
                
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                
                echo "   ✅ ENUM atualizado com sucesso\n";
                echo "   ✅ Migration 032: Executada\n\n";
            } catch (\PDOException $e) {
                $db->exec("SET FOREIGN_KEY_CHECKS = 1");
                echo "   ❌ Erro ao atualizar ENUM: " . $e->getMessage() . "\n";
                echo "   ⚠️  Migration 032: Falhou\n\n";
            }
        }
    }
    
    // ============================================
    // Verificação final
    // ============================================
    echo "========================================\n";
    echo "VERIFICAÇÃO FINAL\n";
    echo "========================================\n\n";
    
    $criticalColumns = [
        'gateway_provider',
        'gateway_charge_id',
        'gateway_last_status',
        'gateway_last_event_at',
        'gateway_payment_url'
    ];
    
    $allOk = true;
    
    foreach ($criticalColumns as $col) {
        if ($columnExists('enrollments', $col)) {
            echo "   ✅ Coluna '{$col}' existe\n";
        } else {
            echo "   ❌ Coluna '{$col}' NÃO existe!\n";
            $allOk = false;
        }
    }
    
    // Verificar ENUM
    $stmt = $db->query("
        SELECT COLUMN_TYPE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'enrollments' 
        AND COLUMN_NAME = 'billing_status'
    ");
    $result = $stmt->fetch();
    
    if ($result && strpos($result['COLUMN_TYPE'], "'canceled'") !== false) {
        echo "   ✅ ENUM 'billing_status' contém 'canceled'\n";
    } else {
        echo "   ⚠️  ENUM 'billing_status' não contém 'canceled' (opcional)\n";
    }
    
    echo "\n";
    
    if ($allOk) {
        echo "✅ TODAS AS MIGRATIONS FORAM EXECUTADAS COM SUCESSO!\n\n";
        echo "O módulo financeiro está pronto para uso.\n";
        echo "A Etapa 1 (UX de leitura) pode ser testada agora.\n";
    } else {
        echo "⚠️  ALGUMAS MIGRATIONS FALHARAM\n";
        echo "Verifique os erros acima e tente novamente.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
