<?php
/**
 * Script para executar a migration 013 (Remover aulas teóricas)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar autoload
require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 013: Remover suporte a aulas teóricas...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/013_remove_theoretical_lessons.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 013 executada com sucesso!\n";
    echo "\nAlterações realizadas:\n";
    echo "  - ENUM 'type' alterado para apenas 'pratica'\n";
    echo "  - Campo vehicle_id agora é obrigatório\n";
    echo "  - Referências a aulas teóricas removidas\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
