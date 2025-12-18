<?php
/**
 * Testar UPDATE diretamente sem rate limit
 */

chdir('C:\xampp\htdocs\cfc-bom-conselho');

require_once 'includes/config.php';
require_once 'includes/database.php';

$db = db();

// Buscar aluno
$aluno = $db->fetch(
    "SELECT id, email, cpf FROM usuarios WHERE id = 19 LIMIT 1"
);

if (!$aluno) {
    die("Aluno não encontrado\n");
}

echo "=== TESTE UPDATE DIRETO ===\n\n";
echo "Aluno ID: " . $aluno['id'] . "\n\n";

// Senha antes
echo "1. Senha ANTES:\n";
$senhaAntes = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $aluno['id']]);
$senhaHashAntes = $senhaAntes['senha'] ?? null;
echo "   Hash: " . substr($senhaHashAntes, 0, 30) . "...\n";
echo "   Tamanho: " . strlen($senhaHashAntes) . "\n\n";

// Gerar novo hash
echo "2. Gerando novo hash...\n";
$novaSenha = 'Teste1234';
$passwordHash = password_hash($novaSenha, PASSWORD_DEFAULT);
echo "   Novo hash: " . substr($passwordHash, 0, 30) . "...\n";
echo "   Tamanho: " . strlen($passwordHash) . "\n\n";

// Executar UPDATE
echo "3. Executando UPDATE...\n";
try {
    $stmt = $db->update('usuarios', ['senha' => $passwordHash], 'id = :id', ['id' => $aluno['id']]);
    $rowsAffected = $stmt ? $stmt->rowCount() : 0;
    echo "   rowCount(): $rowsAffected\n\n";
    
    // Verificar se mudou
    echo "4. Verificando se mudou...\n";
    $senhaDepois = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $aluno['id']]);
    $senhaHashDepois = $senhaDepois['senha'] ?? null;
    
    echo "   Hash depois: " . substr($senhaHashDepois, 0, 30) . "...\n";
    echo "   Tamanho: " . strlen($senhaHashDepois) . "\n";
    
    $senhaMudou = ($senhaHashDepois !== $senhaHashAntes);
    echo "   Senha mudou: " . ($senhaMudou ? '✅ SIM' : '❌ NÃO') . "\n";
    
    if ($senhaMudou) {
        echo "\n✅ UPDATE FUNCIONOU!\n";
    } else {
        echo "\n❌ UPDATE NÃO FUNCIONOU!\n";
        echo "   Possíveis causas:\n";
        echo "   - UPDATE não foi executado\n";
        echo "   - Constraint ou trigger bloqueou\n";
        echo "   - Mesmo hash (improvável)\n";
    }
    
} catch (Throwable $e) {
    echo "❌ ERRO:\n";
    echo "   " . $e->getMessage() . "\n";
    echo "   " . $e->getFile() . ":" . $e->getLine() . "\n";
}
