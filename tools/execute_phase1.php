<?php
/**
 * Script para executar migrations e seeds da Fase 1
 * Execute: php tools/execute_phase1.php
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/Config/Database.php';

use App\Config\Database;

echo "========================================\n";
echo "  FASE 1 - Setup Automático\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    echo "✓ Conexão com banco de dados estabelecida\n\n";
    
    // 1. Executar Migration
    echo "1. Executando migration 002...\n";
    $migrationFile = ROOT_PATH . '/database/migrations/002_create_phase1_tables.sql';
    
    if (!file_exists($migrationFile)) {
        throw new Exception("Arquivo não encontrado: {$migrationFile}");
    }
    
    $migrationSQL = file_get_contents($migrationFile);
    
    // Executar SET statements primeiro
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("SET SQL_MODE = 'NO_AUTO_VALUE_ON_ZERO'");
    $db->exec("SET time_zone = '+00:00'");
    
    // Remover comentários e linhas vazias
    $migrationSQL = preg_replace('/--.*$/m', '', $migrationSQL);
    $migrationSQL = preg_replace('/^\s*$/m', '', $migrationSQL);
    
    // Dividir por CREATE TABLE (mais confiável)
    $createTablePattern = '/CREATE\s+TABLE\s+IF\s+NOT\s+EXISTS\s+`?(\w+)`?[^;]+;/is';
    preg_match_all($createTablePattern, $migrationSQL, $matches, PREG_SET_ORDER);
    
    $tablesCreated = [];
    
    foreach ($matches as $match) {
        $fullStatement = trim($match[0]);
        $tableName = $match[1];
        
        try {
            $db->exec($fullStatement);
            $tablesCreated[] = $tableName;
            echo "   ✓ Tabela '{$tableName}' criada\n";
        } catch (PDOException $e) {
            // Se já existe, ignorar
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate') !== false) {
                echo "   ℹ Tabela '{$tableName}' já existe\n";
                $tablesCreated[] = $tableName;
            } else {
                echo "   ✗ Erro ao criar '{$tableName}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "   ✓ Migration executada! (" . count($tablesCreated) . " tabelas processadas)\n\n";
    
    // 2. Executar Seed
    echo "2. Executando seed 002...\n";
    $seedFile = ROOT_PATH . '/database/seeds/002_seed_phase1_data.sql';
    
    if (!file_exists($seedFile)) {
        throw new Exception("Arquivo não encontrado: {$seedFile}");
    }
    
    $seedSQL = file_get_contents($seedFile);
    
    // Remover comentários
    $seedSQL = preg_replace('/--.*$/m', '', $seedSQL);
    
    // Dividir por INSERT INTO
    $insertPattern = '/INSERT\s+INTO\s+`?(\w+)`?[^;]+;/is';
    preg_match_all($insertPattern, $seedSQL, $insertMatches, PREG_SET_ORDER);
    
    $inserted = 0;
    $errors = 0;
    
    foreach ($insertMatches as $match) {
        $fullStatement = trim($match[0]);
        $tableName = $match[1];
        
        try {
            $db->exec($fullStatement);
            $inserted++;
            echo "   ✓ Dados inseridos em '{$tableName}'\n";
        } catch (PDOException $e) {
            // Ignorar erros de duplicação (ON DUPLICATE KEY UPDATE)
            if (strpos($e->getMessage(), 'Duplicate') !== false || 
                strpos($e->getMessage(), 'already exists') !== false) {
                // Silencioso - é esperado com ON DUPLICATE KEY UPDATE
            } else {
                $errors++;
                echo "   ⚠ Aviso em '{$tableName}': " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "   ✓ Seed executado! ({$inserted} inserções";
    if ($errors > 0) {
        echo ", {$errors} avisos";
    }
    echo ")\n\n";
    
    // 3. Verificar tabelas criadas
    echo "3. Verificando tabelas criadas...\n";
    $requiredTables = ['services', 'students', 'enrollments', 'steps', 'student_steps'];
    $allOk = true;
    
    foreach ($requiredTables as $table) {
        try {
            $stmt = $db->query("SHOW TABLES LIKE '{$table}'");
            if ($stmt->rowCount() > 0) {
                // Contar registros
                $countStmt = $db->query("SELECT COUNT(*) as total FROM `{$table}`");
                $count = $countStmt->fetch()['total'];
                echo "   ✓ Tabela '{$table}' existe ({$count} registros)\n";
            } else {
                echo "   ✗ Tabela '{$table}' NÃO existe\n";
                $allOk = false;
            }
        } catch (PDOException $e) {
            echo "   ✗ Erro ao verificar '{$table}': " . $e->getMessage() . "\n";
            $allOk = false;
        }
    }
    
    echo "\n";
    
    if ($allOk) {
        echo "========================================\n";
        echo "  ✅ FASE 1 CONFIGURADA COM SUCESSO!\n";
        echo "========================================\n\n";
        echo "Próximos passos:\n";
        echo "1. Acesse o sistema: http://localhost/cfc-v.1/public_html/\n";
        echo "2. Faça login com: admin@cfc.local / admin123\n";
        echo "3. Teste criar um serviço em /servicos\n";
        echo "4. Teste criar um aluno em /alunos\n";
        echo "5. Teste criar uma matrícula\n\n";
    } else {
        echo "========================================\n";
        echo "  ⚠️ ALGUMAS TABELAS NÃO FORAM CRIADAS\n";
        echo "========================================\n\n";
        echo "Verifique os erros acima e tente novamente.\n\n";
        exit(1);
    }
    
} catch (Exception $e) {
    echo "\n";
    echo "========================================\n";
    echo "  ❌ ERRO\n";
    echo "========================================\n\n";
    echo "Mensagem: " . $e->getMessage() . "\n";
    if ($e instanceof PDOException) {
        echo "Código: " . $e->getCode() . "\n";
    }
    echo "\n";
    exit(1);
}
