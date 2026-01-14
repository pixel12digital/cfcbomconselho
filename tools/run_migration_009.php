<?php
/**
 * Script de Execução da Migration 009 - Adicionar campos de plano de pagamento na tabela enrollments
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script executa a migration 009 de forma segura, verificando se as colunas já existem
 * antes de tentar adicioná-las.
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

echo "=== EXECUTANDO MIGRATION 009 - PLANO DE PAGAMENTO ===\n\n";

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
    
    // Função para verificar se um valor existe no enum
    $enumValueExists = function($table, $column, $value) use ($db) {
        $stmt = $db->prepare("
            SELECT COLUMN_TYPE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = ? 
            AND COLUMN_NAME = ?
        ");
        $stmt->execute([$table, $column]);
        $result = $stmt->fetch();
        if (!$result) return false;
        
        $columnType = $result['COLUMN_TYPE'];
        return strpos($columnType, $value) !== false;
    };
    
    // Atualizar enum de payment_method
    echo "3. Atualizando enum payment_method...\n";
    if ($columnExists('enrollments', 'payment_method')) {
        if ($enumValueExists('enrollments', 'payment_method', 'entrada_parcelas')) {
            echo "   ⏭️  Enum 'payment_method' já contém 'entrada_parcelas', pulando...\n";
        } else {
            try {
                $db->exec("ALTER TABLE `enrollments` MODIFY COLUMN `payment_method` enum('pix','boleto','cartao','entrada_parcelas') NOT NULL");
                echo "   ✅ Enum 'payment_method' atualizado com sucesso\n";
            } catch (\PDOException $e) {
                echo "   ❌ Erro ao atualizar enum 'payment_method': " . $e->getMessage() . "\n";
            }
        }
    } else {
        echo "   ⚠️  Coluna 'payment_method' não existe!\n";
    }
    echo "\n";
    
    // Lista de colunas a adicionar
    $columnsToAdd = [
        ['name' => 'installments', 'type' => 'INT(11)', 'after' => 'payment_method', 'default' => 'DEFAULT NULL COMMENT \'Número de parcelas (1-12)\''],
        ['name' => 'down_payment_amount', 'type' => 'DECIMAL(10,2)', 'after' => 'installments', 'default' => 'DEFAULT NULL COMMENT \'Valor da entrada (quando entrada_parcelas)\''],
        ['name' => 'down_payment_due_date', 'type' => 'DATE', 'after' => 'down_payment_amount', 'default' => 'DEFAULT NULL COMMENT \'Data de vencimento da entrada\''],
        ['name' => 'first_due_date', 'type' => 'DATE', 'after' => 'down_payment_due_date', 'default' => 'DEFAULT NULL COMMENT \'Data de vencimento da primeira parcela\''],
        ['name' => 'billing_status', 'type' => "ENUM('draft','ready','generated','error')", 'after' => 'first_due_date', 'default' => "NOT NULL DEFAULT 'draft' COMMENT 'Status da geração de cobrança Asaas'"],
    ];
    
    echo "4. Verificando e adicionando colunas de plano de pagamento...\n";
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
    echo "5. Verificando e adicionando índices...\n";
    
    $indexesToAdd = [
        ['name' => 'billing_status', 'column' => 'billing_status'],
        ['name' => 'first_due_date', 'column' => 'first_due_date'],
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
    echo "6. Verificação final...\n";
    $criticalColumns = ['installments', 'down_payment_amount', 'down_payment_due_date', 'first_due_date', 'billing_status'];
    $allOk = true;
    
    foreach ($criticalColumns as $col) {
        if ($columnExists('enrollments', $col)) {
            echo "   ✅ Coluna '{$col}' existe\n";
        } else {
            echo "   ❌ Coluna '{$col}' NÃO existe!\n";
            $allOk = false;
        }
    }
    
    // Verificar enum
    if ($enumValueExists('enrollments', 'payment_method', 'entrada_parcelas')) {
        echo "   ✅ Enum 'payment_method' contém 'entrada_parcelas'\n";
    } else {
        echo "   ❌ Enum 'payment_method' NÃO contém 'entrada_parcelas'!\n";
        $allOk = false;
    }
    
    echo "\n";
    
    if ($allOk) {
        echo "✅ MIGRATION 009 EXECUTADA COM SUCESSO!\n";
        echo "\nOs campos de plano de pagamento foram adicionados à tabela enrollments:\n";
        echo "- installments (INT) - Número de parcelas\n";
        echo "- down_payment_amount (DECIMAL) - Valor da entrada\n";
        echo "- down_payment_due_date (DATE) - Vencimento da entrada\n";
        echo "- first_due_date (DATE) - Vencimento da primeira parcela\n";
        echo "- billing_status (ENUM) - Status da geração de cobrança Asaas\n";
        echo "\nAgora você pode usar as condições de pagamento nas telas de matrícula.\n";
    } else {
        echo "⚠️  MIGRATION PARCIALMENTE EXECUTADA\n";
        echo "Algumas colunas críticas não foram criadas. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
