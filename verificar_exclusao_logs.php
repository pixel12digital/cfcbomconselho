<?php
// Script para verificar por que a exclusão dos logs não funcionou
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== VERIFICAÇÃO DA EXCLUSÃO DE LOGS ===\n\n";

// Verificar logs antes da exclusão
$logsAntes = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = 1");
echo "Logs antes da exclusão: " . $logsAntes['total'] . "\n";

// Tentar excluir logs
echo "\n=== EXCLUINDO LOGS ===\n";
try {
    $result = $db->query("DELETE FROM logs WHERE usuario_id = 1");
    echo "✅ Query de exclusão de logs executada\n";
    echo "Linhas afetadas: " . $result->rowCount() . "\n";
} catch (Exception $e) {
    echo "❌ Erro ao excluir logs: " . $e->getMessage() . "\n";
}

// Verificar logs depois da exclusão
$logsDepois = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = 1");
echo "Logs depois da exclusão: " . $logsDepois['total'] . "\n";

// Verificar se a transação foi commitada
echo "\n=== VERIFICAÇÃO DE TRANSAÇÃO ===\n";
echo "Em transação: " . ($db->inTransaction() ? 'Sim' : 'Não') . "\n";

// Se ainda há logs, verificar detalhes
if ($logsDepois['total'] > 0) {
    echo "\n=== DETALHES DOS LOGS RESTANTES ===\n";
    $logsRestantes = $db->fetchAll("SELECT * FROM logs WHERE usuario_id = 1 LIMIT 5");
    foreach ($logsRestantes as $i => $log) {
        echo "Log " . ($i + 1) . ": ID=" . $log['id'] . ", Ação=" . $log['acao'] . "\n";
    }
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
?>
