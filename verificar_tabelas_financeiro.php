<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "Verificando tabelas financeiras...\n";

$tables = $db->fetchAll('SHOW TABLES LIKE "financeiro_%"');

if (empty($tables)) {
    echo "❌ Nenhuma tabela financeira encontrada\n";
} else {
    echo "✅ Tabelas encontradas:\n";
    foreach($tables as $table) {
        $tableName = array_values($table)[0];
        echo "  - $tableName\n";
        
        // Verificar estrutura
        $columns = $db->fetchAll("DESCRIBE $tableName");
        echo "    Colunas: " . count($columns) . "\n";
    }
}

// Verificar se tabela alunos tem campos de inadimplência
echo "\nVerificando campos de inadimplência em alunos...\n";
$columns = $db->fetchAll("SHOW COLUMNS FROM alunos LIKE '%inadimplente%'");
if (empty($columns)) {
    echo "❌ Campos de inadimplência não encontrados em alunos\n";
} else {
    echo "✅ Campos de inadimplência encontrados:\n";
    foreach($columns as $column) {
        echo "  - {$column['Field']} ({$column['Type']})\n";
    }
}
