<?php
/**
 * Script para executar a migration 014 (Completar tabela de instrutores)
 */

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar autoload
require_once APP_PATH . '/autoload.php';

use App\Config\Database;

try {
    $db = Database::getInstance()->getConnection();
    
    echo "Executando migration 014: Completar tabela de instrutores...\n\n";
    
    $sql = file_get_contents(ROOT_PATH . '/database/migrations/014_complete_instructors_table.sql');
    
    // Executar SQL
    $db->exec($sql);
    
    echo "✓ Migration 014 executada com sucesso!\n";
    echo "\nCampos adicionados:\n";
    echo "  - photo_path (foto do instrutor)\n";
    echo "  - birth_date (data de nascimento)\n";
    echo "  - credential_number (número da credencial)\n";
    echo "  - credential_expiry_date (validade da credencial)\n";
    echo "  - license_categories (categorias múltiplas)\n";
    echo "  - Campos de endereço completo\n";
    echo "\nTabela criada:\n";
    echo "  - instructor_availability (disponibilidade de horários)\n";
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
