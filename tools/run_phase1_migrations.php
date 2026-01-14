<?php
/**
 * Script para executar migrations e seeds da Fase 1
 * Execute via linha de comando: php tools/run_phase1_migrations.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Config/Database.php';

use App\Config\Database;

echo "=== FASE 1 - Executando Migrations e Seeds ===\n\n";

$db = Database::getInstance()->getConnection();

try {
    // Ler e executar migration
    echo "1. Executando migration 002...\n";
    $migrationFile = ROOT_PATH . '/database/migrations/002_create_phase1_tables.sql';
    
    if (!file_exists($migrationFile)) {
        die("ERRO: Arquivo de migration não encontrado: {$migrationFile}\n");
    }
    
    $migrationSQL = file_get_contents($migrationFile);
    
    // Dividir em comandos individuais (remover comentários e linhas vazias)
    $statements = array_filter(
        array_map('trim', explode(';', $migrationSQL)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt) && !preg_match('/^SET\s+/i', $stmt);
        }
    );
    
    // Executar SET statements primeiro
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $db->exec("SET time_zone = '+00:00'");
    
    // Executar CREATE TABLE statements
    foreach ($statements as $statement) {
        if (preg_match('/^CREATE\s+TABLE/i', $statement)) {
            $db->exec($statement);
            preg_match('/`(\w+)`/', $statement, $matches);
            if (!empty($matches[1])) {
                echo "   ✓ Tabela '{$matches[1]}' criada\n";
            }
        }
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "   ✓ Migration executada com sucesso!\n\n";
    
    // Ler e executar seed
    echo "2. Executando seed 002...\n";
    $seedFile = ROOT_PATH . '/database/seeds/002_seed_phase1_data.sql';
    
    if (!file_exists($seedFile)) {
        die("ERRO: Arquivo de seed não encontrado: {$seedFile}\n");
    }
    
    $seedSQL = file_get_contents($seedFile);
    
    // Dividir em comandos individuais
    $statements = array_filter(
        array_map('trim', explode(';', $seedSQL)),
        function($stmt) {
            return !empty($stmt) && !preg_match('/^--/', $stmt);
        }
    );
    
    $inserted = 0;
    foreach ($statements as $statement) {
        if (preg_match('/^INSERT\s+INTO/i', $statement)) {
            try {
                $db->exec($statement);
                $inserted++;
                preg_match('/INSERT\s+INTO\s+`?(\w+)`?/i', $statement, $matches);
                if (!empty($matches[1])) {
                    echo "   ✓ Dados inseridos em '{$matches[1]}'\n";
                }
            } catch (PDOException $e) {
                // Ignorar erros de duplicação (ON DUPLICATE KEY UPDATE)
                if (strpos($e->getMessage(), 'Duplicate') === false) {
                    echo "   ⚠ Aviso: " . $e->getMessage() . "\n";
                }
            }
        }
    }
    
    echo "   ✓ Seed executado com sucesso! ({$inserted} inserções)\n\n";
    
    // Verificar tabelas criadas
    echo "3. Verificando tabelas criadas...\n";
    $tables = ['services', 'students', 'enrollments', 'steps', 'student_steps'];
    $allOk = true;
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                echo "   ✓ Tabela '{$table}' existe\n";
            } else {
                echo "   ✗ Tabela '{$table}' NÃO existe\n";
                $allOk = false;
            }
        } catch (PDOException $e) {
            echo "   ✗ Erro ao verificar '{$table}': " . $e->getMessage() . "\n";
            $allOk = false;
        }
    }
    
    if ($allOk) {
        echo "\n✅ FASE 1 CONFIGURADA COM SUCESSO!\n";
        echo "\nPróximos passos:\n";
        echo "1. Acesse o sistema e faça login\n";
        echo "2. Teste criar um serviço em /servicos\n";
        echo "3. Teste criar um aluno em /alunos\n";
        echo "4. Teste criar uma matrícula\n";
    } else {
        echo "\n⚠️ Algumas tabelas não foram criadas. Verifique os erros acima.\n";
    }
    
} catch (PDOException $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
    exit(1);
}
