<?php
/**
 * Script para executar migration: adicionar campos KM e timestamps na tabela aulas
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';

$db = Database::getInstance();

echo "=== Executando Migration: Adicionar campos KM e Timestamps ===\n\n";

$statements = [
    "ALTER TABLE aulas ADD COLUMN IF NOT EXISTS km_inicial INT NULL COMMENT 'Odômetro inicial da aula prática' AFTER observacoes",
    "ALTER TABLE aulas ADD COLUMN IF NOT EXISTS km_final INT NULL COMMENT 'Odômetro final da aula prática' AFTER km_inicial",
    "ALTER TABLE aulas ADD COLUMN IF NOT EXISTS inicio_at TIMESTAMP NULL COMMENT 'Timestamp real do início da aula' AFTER km_final",
    "ALTER TABLE aulas ADD COLUMN IF NOT EXISTS fim_at TIMESTAMP NULL COMMENT 'Timestamp real do fim da aula' AFTER inicio_at"
];

// MySQL não suporta IF NOT EXISTS em ALTER TABLE ADD COLUMN diretamente
// Vamos verificar antes de adicionar
$columns = $db->fetchAll("SHOW COLUMNS FROM aulas");
$existingColumns = array_map(function($col) {
    return strtolower($col['Field']);
}, $columns);

foreach ($statements as $index => $sql) {
    // Extrair nome da coluna do SQL
    if (preg_match('/ADD COLUMN.*?\s+(\w+)\s+/i', $sql, $matches)) {
        $columnName = strtolower($matches[1]);
        
        if (in_array($columnName, $existingColumns)) {
            echo "⏭ Coluna '{$columnName}' já existe. Pulando...\n";
            continue;
        }
    }
    
    // Remover IF NOT EXISTS (MySQL não suporta)
    $sql = preg_replace('/IF NOT EXISTS\s+/i', '', $sql);
    
    try {
        $db->query($sql);
        echo "✅ Coluna adicionada com sucesso!\n";
        echo "   SQL: " . substr($sql, 0, 80) . "...\n\n";
        
        // Atualizar lista de colunas existentes
        $columns = $db->fetchAll("SHOW COLUMNS FROM aulas");
        $existingColumns = array_map(function($col) {
            return strtolower($col['Field']);
        }, $columns);
        
    } catch (Exception $e) {
        $errorMsg = $e->getMessage();
        
        // Se já existe, OK
        if (strpos($errorMsg, 'Duplicate column') !== false || 
            strpos($errorMsg, 'already exists') !== false) {
            echo "⏭ Coluna já existe. OK.\n\n";
        } else {
            echo "❌ ERRO: {$errorMsg}\n\n";
        }
    }
}

echo "=== Verificando estrutura final ===\n\n";
$columns = $db->fetchAll("SHOW COLUMNS FROM aulas WHERE Field IN ('km_inicial', 'km_final', 'inicio_at', 'fim_at', 'observacoes')");

foreach ($columns as $col) {
    echo "✓ {$col['Field']}: {$col['Type']} (Null: {$col['Null']}, Default: " . ($col['Default'] ?? 'NULL') . ")\n";
}

echo "\n=== Migration concluída! ===\n";
