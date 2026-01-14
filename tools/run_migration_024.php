<?php
/**
 * Script para executar a migration 024 (Campos de quilometragem e observação do instrutor)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar autoload
require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 024: Campos de quilometragem e observação do instrutor...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/024_add_km_fields_to_lessons.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 024 executada com sucesso!\n";
    echo "\nCampos adicionados à tabela lessons:\n";
    echo "  - km_start (int, nullable)\n";
    echo "  - km_end (int, nullable)\n";
    echo "  - instructor_notes (text, nullable)\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
