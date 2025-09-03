<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "🔍 Verificando CFC específico...\n";

try {
    $db = Database::getInstance();
    
    // Verificar o CFC específico
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = 36");
    
    if ($cfc) {
        echo "✅ CFC encontrado:\n";
        echo "  ID: {$cfc['id']}\n";
        echo "  Nome: {$cfc['nome']}\n";
        echo "  Ativo: " . ($cfc['ativo'] ?? 'N/A') . "\n";
        echo "  Status: " . ($cfc['status'] ?? 'N/A') . "\n";
        
        // Testar diferentes consultas
        echo "\n🔍 Testando consultas:\n";
        
        $cfcs_ativos = $db->fetchAll("SELECT id, nome FROM cfcs WHERE ativo = 1 ORDER BY nome");
        echo "CFCs com ativo = 1: " . count($cfcs_ativos) . "\n";
        
        $cfcs_status = $db->fetchAll("SELECT id, nome FROM cfcs WHERE status = 'ativo' ORDER BY nome");
        echo "CFCs com status = 'ativo': " . count($cfcs_status) . "\n";
        
        $todos_cfcs = $db->fetchAll("SELECT id, nome FROM cfcs ORDER BY nome");
        echo "Todos os CFCs: " . count($todos_cfcs) . "\n";
        
        // Mostrar todos os CFCs
        echo "\n📋 Todos os CFCs:\n";
        foreach ($todos_cfcs as $c) {
            echo "  ID: {$c['id']}, Nome: {$c['nome']}\n";
        }
        
    } else {
        echo "❌ CFC não encontrado!\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
}
?>
