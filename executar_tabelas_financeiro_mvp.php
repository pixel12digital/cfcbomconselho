<?php
/**
 * Executor das Tabelas Financeiras MVP
 * Sistema CFC - Bom Conselho
 */

require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🚀 Criando tabelas financeiras MVP...\n\n";

$db = Database::getInstance();

try {
    // Ler e executar o SQL
    $sql = file_get_contents('criar_tabelas_financeiro_mvp.sql');
    
    // Dividir em comandos individuais
    $commands = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($commands as $command) {
        if (!empty($command) && !preg_match('/^--/', $command)) {
            echo "Executando: " . substr($command, 0, 50) . "...\n";
            $db->query($command);
        }
    }
    
    echo "\n✅ Tabelas financeiras criadas com sucesso!\n";
    
    // Verificar tabelas criadas
    $tables = ['financeiro_faturas', 'financeiro_faturas_itens', 'financeiro_pagamentos', 'financeiro_configuracoes'];
    
    foreach ($tables as $table) {
        $exists = $db->fetch("SHOW TABLES LIKE '$table'");
        echo "✅ Tabela $table: " . ($exists ? "Criada" : "Não encontrada") . "\n";
    }
    
    // Verificar configurações inseridas
    $configs = $db->fetchAll("SELECT chave, valor FROM financeiro_configuracoes");
    echo "\n📋 Configurações inseridas:\n";
    foreach ($configs as $config) {
        echo "  - {$config['chave']}: {$config['valor']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro ao criar tabelas: " . $e->getMessage() . "\n";
}
