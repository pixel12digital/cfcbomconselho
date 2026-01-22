<?php
/**
 * Script para executar a Migration 027: Adicionar step CURSO_TEORICO
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 027: Adicionar step CURSO_TEORICO...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/027_add_curso_teorico_step.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 027 executada com sucesso!\n";
    echo "\nStep criado:\n";
    echo "  - CURSO_TEORICO (order 4, antes de PROVA_TEORICA)\n";
    
    // Verificar se foi criado
    $stmt = $db->query("SELECT * FROM steps WHERE code = 'CURSO_TEORICO'");
    $step = $stmt->fetch();
    if ($step) {
        echo "\n✓ Step CURSO_TEORICO confirmado:\n";
        echo "  ID: {$step['id']}\n";
        echo "  Nome: {$step['name']}\n";
        echo "  Order: {$step['order']}\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
