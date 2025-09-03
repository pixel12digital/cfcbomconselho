<?php
// Script final para testar a exclusão completa do usuário
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== TESTE FINAL DE EXCLUSÃO COMPLETA ===\n\n";

// Verificar se o usuário existe
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuario) {
    echo "❌ Usuário ID=1 não existe!\n";
    exit;
}

echo "✅ Usuário encontrado: " . $usuario['nome'] . "\n\n";

// Verificar referências antes
$sessoesAntes = $db->fetch("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id = 1");
$logsAntes = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = 1");

echo "=== REFERÊNCIAS ANTES ===\n";
echo "Sessões: " . $sessoesAntes['total'] . "\n";
echo "Logs: " . $logsAntes['total'] . "\n\n";

// Executar exclusão completa
echo "=== EXECUTANDO EXCLUSÃO COMPLETA ===\n";

try {
    // Começar transação
    $db->beginTransaction();
    
    // 1. Excluir logs primeiro
    echo "1. Excluindo logs...\n";
    $logsResult = $db->query("DELETE FROM logs WHERE usuario_id = 1");
    echo "   Logs removidos: " . $logsResult->rowCount() . "\n";
    
    // 2. Excluir sessões
    echo "2. Excluindo sessões...\n";
    $sessoesResult = $db->query("DELETE FROM sessoes WHERE usuario_id = 1");
    echo "   Sessões removidas: " . $sessoesResult->rowCount() . "\n";
    
    // 3. Verificar se não há mais referências
    echo "3. Verificando referências...\n";
    $sessoesDepois = $db->fetch("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id = 1");
    $logsDepois = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = 1");
    
    echo "   Sessões restantes: " . $sessoesDepois['total'] . "\n";
    echo "   Logs restantes: " . $logsDepois['total'] . "\n";
    
    if ($sessoesDepois['total'] > 0 || $logsDepois['total'] > 0) {
        throw new Exception("Ainda há referências restantes!");
    }
    
    // 4. Excluir usuário
    echo "4. Excluindo usuário...\n";
    $usuarioResult = $db->delete('usuarios', 'id = 1', [1]);
    
    if ($usuarioResult) {
        $db->commit();
        echo "✅ USUÁRIO EXCLUÍDO COM SUCESSO!\n";
        echo "📋 MENSAGEM DA API: Usuário excluído com sucesso\n";
    } else {
        $db->rollback();
        echo "❌ Falha ao excluir usuário\n";
        echo "📋 MENSAGEM DA API: Erro ao excluir usuário\n";
    }
    
} catch (Exception $e) {
    $db->rollback();
    echo "❌ Erro durante exclusão: " . $e->getMessage() . "\n";
    echo "📋 MENSAGEM DA API: Erro interno ao excluir usuário: " . $e->getMessage() . "\n";
}

// Verificar resultado final
echo "\n=== VERIFICAÇÃO FINAL ===\n";
$usuarioFinal = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuarioFinal) {
    echo "✅ Usuário ID=1 foi excluído com sucesso!\n";
} else {
    echo "❌ Usuário ID=1 ainda existe: " . $usuarioFinal['nome'] . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
