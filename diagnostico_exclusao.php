<?php
// Script para diagnosticar o problema na exclusão do usuário
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "=== DIAGNÓSTICO DO PROBLEMA DE EXCLUSÃO ===\n\n";

// Verificar se o usuário existe
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = 1");
if (!$usuario) {
    echo "❌ Usuário ID=1 não encontrado!\n";
    exit;
}

echo "✅ Usuário encontrado: " . $usuario['nome'] . " (ID: " . $usuario['id'] . ")\n\n";

// Testar a query DELETE diretamente
echo "=== TESTE DA QUERY DELETE ===\n";

try {
    // Testar com query direta
    $sql = "DELETE FROM usuarios WHERE id = 1";
    echo "Executando: $sql\n";
    
    $result = $db->query($sql);
    echo "✅ Query executada com sucesso!\n";
    echo "Linhas afetadas: " . $result->rowCount() . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro na query: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
}

echo "\n=== TESTE DO MÉTODO DELETE DA CLASSE ===\n";

try {
    // Testar com o método delete da classe
    $result = $db->delete('usuarios', 'id = ?', [1]);
    echo "✅ Método delete executado com sucesso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro no método delete: " . $e->getMessage() . "\n";
    echo "Código do erro: " . $e->getCode() . "\n";
}

echo "\n=== VERIFICAÇÃO DE ESTRUTURA DA TABELA ===\n";

// Verificar estrutura da tabela
try {
    $estrutura = $db->fetchAll("DESCRIBE usuarios");
    echo "✅ Estrutura da tabela usuarios:\n";
    foreach ($estrutura as $coluna) {
        echo "   - {$coluna['Field']}: {$coluna['Type']} {$coluna['Null']} {$coluna['Key']}\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "\n";
}

echo "\n=== VERIFICAÇÃO DE PERMISSÕES ===\n";

// Verificar permissões do usuário do banco
try {
    $permissoes = $db->fetchAll("SHOW GRANTS FOR CURRENT_USER()");
    echo "✅ Permissões do usuário atual:\n";
    foreach ($permissoes as $permissao) {
        echo "   - " . array_values($permissao)[0] . "\n";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar permissões: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE DE CONEXÃO ===\n";

try {
    $conexao = $db->getConnection();
    echo "✅ Conexão ativa: " . ($conexao ? 'Sim' : 'Não') . "\n";
    
    // Testar uma query simples
    $teste = $db->fetch("SELECT 1 as teste");
    echo "✅ Query de teste executada: " . $teste['teste'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Erro na conexão: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO DIAGNÓSTICO ===\n";
?>
