<?php
/**
 * Script para executar a migration 015 (Campos de cancelamento de aulas)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar autoload
require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 015: Adicionar campos de cancelamento...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/015_add_lesson_cancellation_fields.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 015 executada com sucesso!\n";
    echo "\nCampos adicionados:\n";
    echo "  - canceled_at (data/hora do cancelamento)\n";
    echo "  - canceled_by (usuário que cancelou)\n";
    echo "  - cancel_reason (motivo do cancelamento)\n";
    echo "\nÍndices e foreign keys criados.\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
