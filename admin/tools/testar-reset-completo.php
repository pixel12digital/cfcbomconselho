<?php
/**
 * Script para testar o processo completo de reset
 * Gera um novo token e testa o UPDATE
 */

chdir('C:\xampp\htdocs\cfc-bom-conselho');

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/PasswordReset.php';

echo "=== TESTE COMPLETO DE RESET ===\n\n";

$db = db();

// Buscar um aluno para testar
$aluno = $db->fetch(
    "SELECT id, email, cpf, tipo FROM usuarios WHERE tipo = 'aluno' AND ativo = 1 AND email IS NOT NULL LIMIT 1"
);

if (!$aluno) {
    die("Nenhum aluno encontrado para teste\n");
}

echo "1. Aluno selecionado para teste:\n";
echo "   ID: " . $aluno['id'] . "\n";
echo "   Email: " . $aluno['email'] . "\n";
echo "   CPF: " . ($aluno['cpf'] ?? 'N/A') . "\n\n";

// Gerar novo token
echo "2. Gerando novo token...\n";
$login = $aluno['cpf'] ?? $aluno['email'];
$ip = '127.0.0.1';
$result = PasswordReset::requestReset($login, 'aluno', $ip);

if (!$result['success'] || empty($result['token'])) {
    die("Erro ao gerar token: " . ($result['message'] ?? 'N/A') . "\n");
}

$token = $result['token'];
echo "   ✅ Token gerado: " . substr($token, 0, 20) . "...\n";
echo "   Tamanho: " . strlen($token) . " caracteres\n\n";

// Validar token imediatamente
echo "3. Validando token...\n";
$validation = PasswordReset::validateToken($token);
if (!$validation['valid']) {
    die("Token inválido: " . ($validation['reason'] ?? 'N/A') . "\n");
}
echo "   ✅ Token válido\n\n";

// Obter senha atual
echo "4. Senha ANTES do UPDATE:\n";
$senhaAntes = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $aluno['id']]);
$senhaHashAntes = $senhaAntes['senha'] ?? null;
echo "   Hash: " . substr($senhaHashAntes, 0, 30) . "...\n\n";

// Testar UPDATE
echo "5. Testando UPDATE com consumeTokenAndSetPassword...\n";
$novaSenha = 'Teste1234';
$resultUpdate = PasswordReset::consumeTokenAndSetPassword($token, $novaSenha);

if ($resultUpdate['success']) {
    echo "   ✅ UPDATE bem-sucedido!\n";
    echo "   Mensagem: " . $resultUpdate['message'] . "\n\n";
    
    // Verificar se senha mudou
    echo "6. Verificando se senha mudou...\n";
    $senhaDepois = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $aluno['id']]);
    $senhaHashDepois = $senhaDepois['senha'] ?? null;
    
    if ($senhaHashDepois !== $senhaHashAntes) {
        echo "   ✅ Senha foi alterada!\n";
        echo "   Hash antes: " . substr($senhaHashAntes, 0, 30) . "...\n";
        echo "   Hash depois: " . substr($senhaHashDepois, 0, 30) . "...\n";
    } else {
        echo "   ❌ Senha NÃO foi alterada!\n";
        echo "   PROBLEMA: UPDATE retornou sucesso mas senha não mudou\n";
    }
} else {
    echo "   ❌ UPDATE falhou!\n";
    echo "   Mensagem: " . $resultUpdate['message'] . "\n";
    echo "\n   PROBLEMA IDENTIFICADO: " . $resultUpdate['message'] . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
