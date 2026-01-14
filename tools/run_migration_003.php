<?php
/**
 * Script de Execução da Migration 003 - Adicionar campos completos ao cadastro de alunos
 * 
 * ⚠️ APENAS PARA USO LOCAL/DEVELOPMENT
 * 
 * Este script executa a migration 003 de forma segura, verificando se as colunas já existem
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

echo "=== EXECUTANDO MIGRATION 003 ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar banco atual
    echo "1. Verificando banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n\n";
    
    // Verificar se a tabela students existe
    echo "2. Verificando tabela students...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'students'");
    if ($stmt->rowCount() === 0) {
        die("   ❌ ERRO: Tabela 'students' não existe! Execute primeiro a migration 002.\n");
    }
    echo "   ✅ Tabela 'students' existe\n\n";
    
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
    
    // Lista de colunas a adicionar
    $columnsToAdd = [
        // Dados pessoais
        ['name' => 'full_name', 'type' => 'varchar(255)', 'after' => 'name', 'default' => 'DEFAULT NULL'],
        ['name' => 'birth_date', 'type' => 'date', 'after' => 'full_name', 'default' => 'DEFAULT NULL'],
        ['name' => 'remunerated_activity', 'type' => 'tinyint(1)', 'after' => 'birth_date', 'default' => 'NOT NULL DEFAULT 0'],
        ['name' => 'marital_status', 'type' => 'varchar(50)', 'after' => 'remunerated_activity', 'default' => 'DEFAULT NULL'],
        ['name' => 'profession', 'type' => 'varchar(255)', 'after' => 'marital_status', 'default' => 'DEFAULT NULL'],
        ['name' => 'education_level', 'type' => 'varchar(100)', 'after' => 'profession', 'default' => 'DEFAULT NULL'],
        ['name' => 'nationality', 'type' => 'varchar(100)', 'after' => 'education_level', 'default' => 'DEFAULT NULL'],
        ['name' => 'birth_state_uf', 'type' => 'char(2)', 'after' => 'nationality', 'default' => 'DEFAULT NULL'],
        ['name' => 'birth_city', 'type' => 'varchar(255)', 'after' => 'birth_state_uf', 'default' => 'DEFAULT NULL'],
        
        // Documentos
        ['name' => 'rg_number', 'type' => 'varchar(20)', 'after' => 'cpf', 'default' => 'DEFAULT NULL'],
        ['name' => 'rg_issuer', 'type' => 'varchar(50)', 'after' => 'rg_number', 'default' => 'DEFAULT NULL'],
        ['name' => 'rg_uf', 'type' => 'char(2)', 'after' => 'rg_issuer', 'default' => 'DEFAULT NULL'],
        ['name' => 'rg_issue_date', 'type' => 'date', 'after' => 'rg_uf', 'default' => 'DEFAULT NULL'],
        
        // Contato
        ['name' => 'phone_primary', 'type' => 'varchar(20)', 'after' => 'phone', 'default' => 'DEFAULT NULL'],
        ['name' => 'phone_secondary', 'type' => 'varchar(20)', 'after' => 'phone_primary', 'default' => 'DEFAULT NULL'],
        
        // Emergência
        ['name' => 'emergency_contact_name', 'type' => 'varchar(255)', 'after' => 'email', 'default' => 'DEFAULT NULL'],
        ['name' => 'emergency_contact_phone', 'type' => 'varchar(20)', 'after' => 'emergency_contact_name', 'default' => 'DEFAULT NULL'],
        
        // Endereço
        ['name' => 'cep', 'type' => 'varchar(10)', 'after' => 'emergency_contact_phone', 'default' => 'DEFAULT NULL'],
        ['name' => 'street', 'type' => 'varchar(255)', 'after' => 'cep', 'default' => 'DEFAULT NULL'],
        ['name' => 'number', 'type' => 'varchar(20)', 'after' => 'street', 'default' => 'DEFAULT NULL'],
        ['name' => 'complement', 'type' => 'varchar(255)', 'after' => 'number', 'default' => 'DEFAULT NULL'],
        ['name' => 'neighborhood', 'type' => 'varchar(255)', 'after' => 'complement', 'default' => 'DEFAULT NULL'],
        ['name' => 'city', 'type' => 'varchar(255)', 'after' => 'neighborhood', 'default' => 'DEFAULT NULL'],
        ['name' => 'state_uf', 'type' => 'char(2)', 'after' => 'city', 'default' => 'DEFAULT NULL'],
        
        // Foto
        ['name' => 'photo_path', 'type' => 'varchar(500)', 'after' => 'notes', 'default' => 'DEFAULT NULL'],
    ];
    
    echo "3. Verificando e adicionando colunas...\n";
    $added = 0;
    $skipped = 0;
    
    foreach ($columnsToAdd as $column) {
        if ($columnExists('students', $column['name'])) {
            echo "   ⏭️  Coluna '{$column['name']}' já existe, pulando...\n";
            $skipped++;
        } else {
            try {
                $sql = "ALTER TABLE `students` ADD COLUMN `{$column['name']}` {$column['type']} {$column['default']} AFTER `{$column['after']}`";
                $db->exec($sql);
                echo "   ✅ Coluna '{$column['name']}' adicionada com sucesso\n";
                $added++;
            } catch (\PDOException $e) {
                echo "   ❌ Erro ao adicionar coluna '{$column['name']}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n   Resumo: {$added} colunas adicionadas, {$skipped} já existiam\n\n";
    
    // Migrar dados existentes
    echo "4. Migrando dados existentes...\n";
    
    // Migrar name para full_name
    $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE full_name IS NULL AND name IS NOT NULL");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        $db->exec("UPDATE students SET full_name = name WHERE full_name IS NULL AND name IS NOT NULL");
        echo "   ✅ Migrados {$result['count']} registros: name -> full_name\n";
    } else {
        echo "   ⏭️  Nenhum registro para migrar (name -> full_name)\n";
    }
    
    // Migrar phone para phone_primary
    $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE phone_primary IS NULL AND phone IS NOT NULL");
    $result = $stmt->fetch();
    if ($result['count'] > 0) {
        $db->exec("UPDATE students SET phone_primary = phone WHERE phone_primary IS NULL AND phone IS NOT NULL");
        echo "   ✅ Migrados {$result['count']} registros: phone -> phone_primary\n";
    } else {
        echo "   ⏭️  Nenhum registro para migrar (phone -> phone_primary)\n";
    }
    
    echo "\n";
    
    // Adicionar índices
    echo "5. Verificando e adicionando índices...\n";
    
    $indexesToAdd = [
        ['name' => 'idx_birth_date', 'column' => 'birth_date'],
        ['name' => 'idx_phone_primary', 'column' => 'phone_primary'],
        ['name' => 'idx_cep', 'column' => 'cep'],
        ['name' => 'idx_city', 'column' => 'city'],
        ['name' => 'idx_state_uf', 'column' => 'state_uf'],
    ];
    
    $indexAdded = 0;
    $indexSkipped = 0;
    
    foreach ($indexesToAdd as $index) {
        // Verificar se o índice já existe
        $stmt = $db->prepare("
            SELECT COUNT(*) as count 
            FROM INFORMATION_SCHEMA.STATISTICS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'students' 
            AND INDEX_NAME = ?
        ");
        $stmt->execute([$index['name']]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            echo "   ⏭️  Índice '{$index['name']}' já existe, pulando...\n";
            $indexSkipped++;
        } else {
            // Verificar se a coluna existe antes de criar o índice
            if ($columnExists('students', $index['column'])) {
                try {
                    $db->exec("ALTER TABLE `students` ADD INDEX `{$index['name']}` (`{$index['column']}`)");
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
    $criticalColumns = ['full_name', 'phone_primary'];
    $allOk = true;
    
    foreach ($criticalColumns as $col) {
        if ($columnExists('students', $col)) {
            echo "   ✅ Coluna '{$col}' existe\n";
        } else {
            echo "   ❌ Coluna '{$col}' NÃO existe!\n";
            $allOk = false;
        }
    }
    
    echo "\n";
    
    if ($allOk) {
        echo "✅ MIGRATION 003 EXECUTADA COM SUCESSO!\n";
        echo "\nAgora você pode acessar a página de alunos sem erros.\n";
    } else {
        echo "⚠️  MIGRATION PARCIALMENTE EXECUTADA\n";
        echo "Algumas colunas críticas não foram criadas. Verifique os erros acima.\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
