<?php
/**
 * Script para executar o Seed 003: Permissões de Curso Teórico
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando seed 003: Permissões de Curso Teórico...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/seeds/003_seed_theory_permissions.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Seed 003 executado com sucesso!\n";
    echo "\nPermissões criadas:\n";
    echo "  - disciplinas: view, create, update, delete\n";
    echo "  - cursos_teoricos: view, create, update, delete\n";
    echo "  - turmas_teoricas: view, create, update, delete\n";
    echo "  - presenca_teorica: view, create, update\n";
    
    // Verificar permissões
    $stmt = $db->query("SELECT COUNT(*) as count FROM permissoes WHERE modulo IN ('disciplinas', 'cursos_teoricos', 'turmas_teoricas', 'presenca_teorica')");
    $result = $stmt->fetch();
    echo "\n✓ Total de permissões criadas: {$result['count']}\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
