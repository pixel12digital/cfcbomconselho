<?php
/**
 * Script para verificar se as migrations necessárias para o módulo financeiro foram executadas
 * Execute: php tools/check_financial_migrations.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';
require_once APP_PATH . '/Bootstrap.php';

use App\Config\Database;

echo "=== VERIFICAÇÃO DE MIGRATIONS DO MÓDULO FINANCEIRO ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "Banco de dados: " . ($currentDb['current_db'] ?? $dbName) . "\n\n";
    
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
    
    // Verificar se a tabela enrollments existe
    echo "1. Verificando tabela 'enrollments'...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'enrollments'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'enrollments' não existe! Execute primeiro a migration 002.\n");
    }
    echo "   ✅ Tabela 'enrollments' existe\n\n";
    
    // Lista de migrations necessárias para o módulo financeiro
    $requiredMigrations = [
        '009' => [
            'name' => 'Payment Plan (Parcelamento)',
            'columns' => ['installments', 'down_payment_amount', 'down_payment_due_date', 'first_due_date', 'billing_status']
        ],
        '010' => [
            'name' => 'Entry Fields (Entrada e Saldo Devedor)',
            'columns' => ['entry_amount', 'entry_payment_method', 'entry_payment_date', 'outstanding_amount']
        ],
        '030' => [
            'name' => 'Gateway Fields (Campos do Gateway)',
            'columns' => ['gateway_provider', 'gateway_charge_id', 'gateway_last_status', 'gateway_last_event_at']
        ],
        '031' => [
            'name' => 'Gateway Payment URL (URL de Pagamento)',
            'columns' => ['gateway_payment_url']
        ],
        '032' => [
            'name' => 'Billing Status Canceled (Status Cancelado)',
            'columns' => [] // Verificar ENUM
        ]
    ];
    
    $allOk = true;
    $missingMigrations = [];
    
    echo "2. Verificando migrations necessárias...\n\n";
    
    foreach ($requiredMigrations as $migrationNum => $migration) {
        echo "   Migration {$migrationNum}: {$migration['name']}\n";
        
        $migrationOk = true;
        
        // Verificar colunas
        foreach ($migration['columns'] as $column) {
            $exists = $columnExists('enrollments', $column);
            if ($exists) {
                echo "      ✅ Coluna '{$column}' existe\n";
            } else {
                echo "      ❌ Coluna '{$column}' NÃO existe\n";
                $migrationOk = false;
                $allOk = false;
            }
        }
        
        // Verificação especial para migration 032 (ENUM)
        if ($migrationNum === '032') {
            $stmt = $db->query("
                SELECT COLUMN_TYPE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'enrollments' 
                AND COLUMN_NAME = 'billing_status'
            ");
            $result = $stmt->fetch();
            if ($result && strpos($result['COLUMN_TYPE'], 'canceled') !== false) {
                echo "      ✅ ENUM 'billing_status' contém 'canceled'\n";
            } else {
                echo "      ❌ ENUM 'billing_status' NÃO contém 'canceled'\n";
                $migrationOk = false;
                $allOk = false;
            }
        }
        
        if ($migrationOk) {
            echo "      ✅ Migration {$migrationNum} executada corretamente\n";
        } else {
            echo "      ❌ Migration {$migrationNum} PENDENTE ou INCOMPLETA\n";
            $missingMigrations[] = $migrationNum;
        }
        
        echo "\n";
    }
    
    // Resumo
    echo "========================================\n";
    echo "RESUMO:\n";
    echo "========================================\n";
    
    if ($allOk) {
        echo "✅ Todas as migrations necessárias foram executadas!\n";
        echo "✅ O módulo financeiro está pronto para uso.\n";
        echo "\n";
        echo "A Etapa 1 (UX de leitura) pode ser testada agora.\n";
    } else {
        echo "❌ Algumas migrations estão pendentes:\n";
        foreach ($missingMigrations as $migrationNum) {
            echo "   - Migration {$migrationNum}: {$requiredMigrations[$migrationNum]['name']}\n";
        }
        echo "\n";
        echo "Para executar as migrations pendentes, use:\n";
        foreach ($missingMigrations as $migrationNum) {
            $migrationFile = "database/migrations/{$migrationNum}_*.sql";
            echo "   - php tools/run_migration_{$migrationNum}.php\n";
            echo "     OU execute diretamente: {$migrationFile}\n";
        }
    }
    
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
