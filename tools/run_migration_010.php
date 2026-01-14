<?php
/**
 * Script de Execução da Migration 010 - Adicionar campos de entrada e saldo devedor na tabela enrollments
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script executa a migration 010 de forma segura, verificando se as colunas já existem
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

echo "=== EXECUTANDO MIGRATION 010 - CAMPOS DE ENTRADA E SALDO DEVEDOR ===\n\n";

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
        ['name' => 'entry_amount', 'type' => 'DECIMAL(10,2)', 'after' => 'final_price', 'default' => 'DEFAULT NULL COMMENT \'Valor da entrada recebida\''],
        ['name' => 'entry_payment_method', 'type' => "ENUM('dinheiro','pix','cartao','boleto')", 'after' => 'entry_amount', 'default' => 'DEFAULT NULL COMMENT \'Forma de pagamento da entrada\''],
        ['name' => 'entry_payment_date', 'type' => 'DATE', 'after' => 'entry_payment_method', 'default' => 'DEFAULT NULL COMMENT \'Data do pagamento da entrada\''],
        ['name' => 'outstanding_amount', 'type' => 'DECIMAL(10,2)', 'after' => 'entry_payment_date', 'default' => 'DEFAULT NULL COMMENT \'Saldo devedor (valor_final - entry_amount)\''],
    ];
    
    echo "3. Verificando e adicionando colunas de entrada e saldo devedor...\n";
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
        ['name' => 'entry_payment_date', 'column' => 'entry_payment_date'],
        ['name' => 'outstanding_amount', 'column' => 'outstanding_amount'],
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
    
    // Atualizar outstanding_amount para matrículas existentes que não têm entrada
    echo "5. Atualizando saldo devedor para matrículas existentes...\n";
    try {
        $stmt = $db->exec("
            UPDATE `enrollments` 
            SET `outstanding_amount` = `final_price` 
            WHERE `outstanding_amount` IS NULL
        ");
        $updated = $stmt;
        echo "   ✅ {$updated} matrícula(s) atualizada(s) com saldo devedor = valor final\n";
    } catch (\PDOException $e) {
        echo "   ⚠️  Aviso ao atualizar matrículas existentes: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
    
    // Verificação final
    echo "6. Verificação final...\n";
    $criticalColumns = ['entry_amount', 'entry_payment_method', 'entry_payment_date', 'outstanding_amount'];
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
        echo "✅ MIGRATION 010 EXECUTADA COM SUCESSO!\n";
        echo "\nOs campos de entrada e saldo devedor foram adicionados à tabela enrollments:\n";
        echo "- entry_amount (DECIMAL) - Valor da entrada recebida\n";
        echo "- entry_payment_method (ENUM) - Forma de pagamento da entrada\n";
        echo "- entry_payment_date (DATE) - Data do pagamento da entrada\n";
        echo "- outstanding_amount (DECIMAL) - Saldo devedor (valor_final - entry_amount)\n";
        echo "\nAgora você pode usar os campos de entrada nas telas de matrícula.\n";
        echo "O Asaas deve usar outstanding_amount ao invés de final_price para gerar cobranças.\n";
    } else {
        echo "⚠️  MIGRATION PARCIALMENTE EXECUTADA\n";
        echo "Algumas colunas críticas não foram criadas. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
