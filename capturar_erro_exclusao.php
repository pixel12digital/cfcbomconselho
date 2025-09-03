<?php
// Script para capturar erro detalhado na exclusão
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== CAPTURA DE ERRO DETALHADO ===\n\n";

// Verificar se o usuário ainda existe
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuario) {
    echo "❌ Usuário ID=1 não existe mais!\n";
    exit;
}

echo "✅ Usuário ainda existe: " . $usuario['nome'] . "\n\n";

// Verificar se ainda há referências
$sessoes = $db->fetch("SELECT COUNT(*) as total FROM sessoes WHERE usuario_id = 1");
$logs = $db->fetch("SELECT COUNT(*) as total FROM logs WHERE usuario_id = 1");

echo "=== VERIFICAÇÃO DE REFERÊNCIAS ===\n";
echo "Sessões: " . $sessoes['total'] . "\n";
echo "Logs: " . $logs['total'] . "\n\n";

// Tentar excluir com captura de erro detalhado
echo "=== TENTATIVA DE EXCLUSÃO ===\n";

try {
    // Usar PDO diretamente para capturar erro específico
    $pdo = $db->getConnection();
    
    // Preparar a query
    $stmt = $pdo->prepare("DELETE FROM usuarios WHERE id = ?");
    
    // Executar
    $result = $stmt->execute([1]);
    
    if ($result) {
        echo "✅ Usuário excluído com sucesso!\n";
        echo "Linhas afetadas: " . $stmt->rowCount() . "\n";
    } else {
        echo "❌ Falha na execução\n";
        $errorInfo = $stmt->errorInfo();
        echo "Código do erro: " . $errorInfo[1] . "\n";
        echo "Mensagem do erro: " . $errorInfo[2] . "\n";
    }
    
} catch (PDOException $e) {
    echo "❌ Erro PDO: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
    
    // Verificar se é erro de chave estrangeira
    if ($e->getCode() == 1451) {
        echo "🔍 Este é um erro de restrição de chave estrangeira!\n";
        echo "   Alguma tabela ainda tem registros que referenciam este usuário.\n";
    }
} catch (Exception $e) {
    echo "❌ Erro geral: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
