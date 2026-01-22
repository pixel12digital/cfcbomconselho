<?php
/**
 * Script para executar a Migration 028: Campos de aulas em disciplinas
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 028: Adicionar campos de aulas em disciplinas...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/028_add_lessons_fields_to_disciplines.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 028 executada com sucesso!\n";
    echo "\nCampos adicionados:\n";
    echo "  - theory_disciplines.default_lessons_count\n";
    echo "  - theory_disciplines.default_lesson_minutes\n";
    echo "  - theory_course_disciplines.lessons_count\n";
    echo "  - theory_course_disciplines.lesson_minutes\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
