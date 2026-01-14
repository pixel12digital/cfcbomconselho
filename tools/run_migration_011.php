<?php
/**
 * Script de Execução da Migration 011 - Tabela de Histórico do Aluno
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

echo "=== EXECUTANDO MIGRATION 011 - TABELA DE HISTÓRICO DO ALUNO ===\n\n";

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
    
    // Verificar se a tabela já existe
    echo "3. Verificando se a tabela student_history já existe...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'student_history'");
    if ($stmt->rowCount() > 0) {
        echo "   ⚠️  Tabela 'student_history' já existe.\n";
        echo "   Deseja continuar mesmo assim? (isso pode causar erro se a tabela já existir)\n";
        echo "   Pressione Enter para continuar ou Ctrl+C para cancelar...\n";
        // Em CLI, podemos continuar
    } else {
        echo "   ✅ Tabela 'student_history' não existe, será criada\n\n";
    }
    
    $migrationFile = __DIR__ . '/../database/migrations/011_create_student_history.sql';
    
    if (!file_exists($migrationFile)) {
        die("   ❌ ERRO: Arquivo de migration não encontrado: {$migrationFile}\n");
    }
    
    echo "4. Executando migration...\n";
    $sql = file_get_contents($migrationFile);
    
    // Executar migration
    $db->exec($sql);
    
    echo "   ✅ Migration executada com sucesso!\n\n";
    
    // Verificação final
    echo "5. Verificação final...\n";
    $stmt = $db->query("SHOW TABLES LIKE 'student_history'");
    if ($stmt->rowCount() > 0) {
        echo "   ✅ Tabela 'student_history' criada com sucesso\n";
        
        // Verificar colunas
        $stmt = $db->query("SHOW COLUMNS FROM student_history");
        $columns = $stmt->fetchAll();
        echo "   ✅ Colunas criadas: " . count($columns) . "\n";
        
        foreach ($columns as $column) {
            echo "      - {$column['Field']} ({$column['Type']})\n";
        }
    } else {
        echo "   ❌ ERRO: Tabela 'student_history' não foi criada!\n";
        exit(1);
    }
    
    echo "\n✅ MIGRATION 011 EXECUTADA COM SUCESSO!\n";
    echo "\nA tabela 'student_history' foi criada e está pronta para uso.\n";
    echo "O sistema agora pode registrar eventos do histórico dos alunos automaticamente.\n";
    
} catch (\Exception $e) {
    echo "\n❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
