<?php
/**
 * Script para testar o UPDATE da senha
 * Simula o processo completo de reset
 */

chdir('C:\xampp\htdocs\cfc-bom-conselho');

require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/PasswordReset.php';

$token = '676f711eef98d00d96218e326f147ac79a02a2c6dc4e2775538e7e5548bc9fdd';
$novaSenha = '12345678'; // Senha de teste

echo "=== TESTE DE UPDATE DE SENHA ===\n\n";

$db = db();

// 1. Validar token
echo "1. Validando token...\n";
$validation = PasswordReset::validateToken($token);
if (!$validation['valid']) {
    die("Token inválido: " . ($validation['reason'] ?? 'N/A') . "\n");
}
echo "   ✅ Token válido\n";
echo "   Login: " . $validation['login'] . "\n";
echo "   Tipo: " . $validation['type'] . "\n";
echo "   Reset ID: " . $validation['reset_id'] . "\n\n";

// 2. Buscar usuário
echo "2. Buscando usuário...\n";
$login = $validation['login'];
$type = $validation['type'];

// Usar a mesma lógica do PasswordReset
$usuario = null;
if ($type === 'aluno') {
    $cpfLimpo = preg_replace('/[^0-9]/', '', trim($login));
    $isEmail = filter_var($login, FILTER_VALIDATE_EMAIL);
    
    if ($isEmail) {
        $usuario = $db->fetch(
            "SELECT id, email, cpf, tipo, ativo FROM usuarios WHERE email = :email AND tipo = 'aluno' AND ativo = 1 LIMIT 1",
            ['email' => $login]
        );
    } elseif (!empty($cpfLimpo) && strlen($cpfLimpo) === 11) {
        $usuario = $db->fetch(
            "SELECT id, email, cpf, tipo, ativo FROM usuarios 
             WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
             AND tipo = 'aluno' 
             AND ativo = 1 
             LIMIT 1",
            ['cpf' => $cpfLimpo]
        );
    }
} else {
    $usuario = $db->fetch(
        "SELECT id, email, tipo, ativo FROM usuarios WHERE email = :email AND tipo = :type AND ativo = 1 LIMIT 1",
        ['email' => $login, 'type' => $type]
    );
}

if (!$usuario) {
    die("❌ Usuário não encontrado\n");
}

echo "   ✅ Usuário encontrado\n";
echo "   ID: " . $usuario['id'] . "\n";
echo "   Tipo: " . $usuario['tipo'] . "\n\n";

// 3. Obter senha atual ANTES do UPDATE
echo "3. Senha ANTES do UPDATE:\n";
$senhaAntes = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $usuario['id']]);
$senhaHashAntes = $senhaAntes['senha'] ?? null;
echo "   Hash: " . substr($senhaHashAntes, 0, 30) . "...\n";
echo "   Tamanho: " . strlen($senhaHashAntes) . " caracteres\n\n";

// 4. Gerar novo hash
echo "4. Gerando novo hash da senha...\n";
$passwordHash = password_hash($novaSenha, PASSWORD_DEFAULT);
echo "   Novo hash: " . substr($passwordHash, 0, 30) . "...\n";
echo "   Tamanho: " . strlen($passwordHash) . " caracteres\n\n";

// 5. Executar UPDATE
echo "5. Executando UPDATE...\n";
try {
    $stmt = $db->update('usuarios', ['senha' => $passwordHash], 'id = :id', ['id' => $usuario['id']]);
    $rowsAffected = $stmt ? $stmt->rowCount() : 0;
    echo "   rowCount(): $rowsAffected\n";
    
    // 6. Verificar se mudou
    echo "\n6. Verificando se senha mudou...\n";
    $senhaDepois = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $usuario['id']]);
    $senhaHashDepois = $senhaDepois['senha'] ?? null;
    echo "   Hash depois: " . substr($senhaHashDepois, 0, 30) . "...\n";
    
    $senhaMudou = ($senhaHashDepois !== $senhaHashAntes);
    echo "   Senha mudou: " . ($senhaMudou ? '✅ SIM' : '❌ NÃO') . "\n";
    
    if ($senhaMudou) {
        echo "\n   ✅ UPDATE FUNCIONOU!\n";
        echo "   A senha foi atualizada com sucesso.\n";
    } else {
        echo "\n   ❌ UPDATE NÃO FUNCIONOU!\n";
        echo "   A senha não foi alterada.\n";
        echo "   Possíveis causas:\n";
        echo "   - rowCount() retornou 0 mas UPDATE foi executado\n";
        echo "   - UPDATE não foi executado\n";
        echo "   - Constraint ou trigger bloqueou\n";
    }
    
} catch (Throwable $e) {
    echo "   ❌ ERRO ao executar UPDATE:\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "   Trace: " . substr($e->getTraceAsString(), 0, 500) . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
