<?php
/**
 * Script simples para executar migration via navegador
 * Acesse: http://localhost/cfc-bom-conselho/admin/migrations/executar_migration.php
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

header('Content-Type: text/plain; charset=utf-8');

try {
    $db = Database::getInstance();
    
    echo "=== Executando Migration: Adicionar hora_agendada ===\n\n";
    
    // Verificar se já existe
    $check = $db->fetch("
        SELECT COUNT(*) as count
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exames'
        AND COLUMN_NAME = 'hora_agendada'
    ");
    
    if ($check['count'] > 0) {
        echo "✅ Coluna hora_agendada já existe. Nenhuma alteração necessária.\n";
    } else {
        echo "➕ Adicionando coluna hora_agendada...\n";
        $db->query("ALTER TABLE exames ADD COLUMN hora_agendada TIME NULL AFTER data_agendada");
        echo "✅ Migration executada com sucesso!\n";
    }
    
    // Verificar resultado
    $cols = $db->fetchAll("
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'exames'
        AND COLUMN_NAME IN ('data_agendada', 'hora_agendada')
        ORDER BY ORDINAL_POSITION
    ");
    
    echo "\n=== Estrutura das colunas ===\n";
    foreach ($cols as $col) {
        echo sprintf("%-20s %-15s NULL=%s\n", 
            $col['COLUMN_NAME'], 
            $col['DATA_TYPE'], 
            $col['IS_NULLABLE']
        );
    }
    
    echo "\n✅ Concluído!\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}

