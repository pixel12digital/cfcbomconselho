<?php
/**
 * Script para executar a Migration 026: Adicionar campos de curso teórico em enrollments
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 026: Adicionar campos de curso teórico em enrollments...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/026_add_theory_fields_to_enrollments.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 026 executada com sucesso!\n";
    echo "\nCampos adicionados à tabela enrollments:\n";
    echo "  - theory_course_id (int, nullable, FK)\n";
    echo "  - theory_class_id (int, nullable, FK)\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
