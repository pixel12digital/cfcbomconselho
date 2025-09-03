<?php
// Script para testar exclusão direta
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== TESTE DE EXCLUSÃO DIRETA ===\n\n";

// Verificar se o usuário existe
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuario) {
    echo "❌ Usuário ID=1 não existe!\n";
    exit;
}

echo "✅ Usuário encontrado: " . $usuario['nome'] . "\n\n";

// Verificar se não há referências
$sessoes = $db->fetch("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id = 1");
$logs = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = 1");

echo "=== VERIFICAÇÃO DE REFERÊNCIAS ===\n";
echo "Sessões: " . $sessoes['total'] . "\n";
echo "Logs: " . $logs['total'] . "\n\n";

if ($sessoes['total'] > 0 || $logs['total'] > 0) {
    echo "❌ Ainda há referências! Removendo...\n";
    
    // Remover referências
    $db->query("DELETE FROM logs WHERE usuario_id = 1");
    $db->query("DELETE FROM sessoes WHERE usuario_id = 1");
    
    echo "✅ Referências removidas\n\n";
}

// Testar exclusão direta
echo "=== TESTE DE EXCLUSÃO DIRETA ===\n";

try {
    // Usar PDO diretamente
    $pdo = $db->getConnection();
    
    // Preparar e executar
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = 1");
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ Usuário excluído com sucesso!\n";
        echo "Linhas afetadas: " . $stmt->rowCount() . "\n";
    } else {
        echo "❌ Falha na execução\n";
        $errorInfo = $stmt->errorInfo();
        echo "Código: " . $errorInfo[1] . "\n";
        echo "Mensagem: " . $errorInfo[2] . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro PDO: " . $e->getMessage() . "\n";
    echo "Código: " . $e->getCode() . "\n";
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
}

// Verificar resultado
echo "\n=== VERIFICAÇÃO FINAL ===\n";
$usuarioFinal = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuarioFinal) {
    echo "✅ Usuário ID=1 foi excluído com sucesso!\n";
} else {
    echo "❌ Usuário ID=1 ainda existe: " . $usuarioFinal['nome'] . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
