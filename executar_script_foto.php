<?php
// Script para adicionar campo foto na tabela instrutores

require_once 'includes/config.php';
require_once 'includes/database.php';

try {
    $db = db();
    
    // Adicionar campo foto na tabela instrutores
    $sql = "ALTER TABLE instrutores ADD COLUMN IF NOT EXISTS foto VARCHAR(255) NULL COMMENT 'Caminho da foto do instrutor' AFTER observacoes";
    $result = $db->query($sql);
    
    if ($result) {
        echo "✅ Campo 'foto' adicionado com sucesso na tabela instrutores!\n";
    } else {
        echo "❌ Erro ao adicionar campo: " . $db->getLastError() . "\n";
    }
    
    // Verificar se o campo foi adicionado
    $columns = $db->fetchAll("SHOW COLUMNS FROM instrutores LIKE 'foto'");
    if (count($columns) > 0) {
        echo "✅ Campo 'foto' confirmado na tabela instrutores\n";
        echo "📋 Detalhes: " . json_encode($columns[0], JSON_PRETTY_PRINT) . "\n";
    } else {
        echo "⚠️ Campo 'foto' não encontrado na tabela\n";
    }
    
    // Mostrar estrutura atualizada da tabela
    echo "\n📋 Estrutura atual da tabela instrutores:\n";
    $structure = $db->fetchAll("DESCRIBE instrutores");
    foreach ($structure as $column) {
        echo "- {$column['Field']}: {$column['Type']} ({$column['Null']}, {$column['Key']})\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
