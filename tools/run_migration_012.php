<?php
/**
 * Script para executar a migration 012 (Instrutores, Veículos e Aulas)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar autoload
require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 012: Instrutores, Veículos e Aulas...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/012_create_instructors_vehicles_lessons.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 012 executada com sucesso!\n";
    echo "\nTabelas criadas:\n";
    echo "  - instructors\n";
    echo "  - vehicles\n";
    echo "  - lessons\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
