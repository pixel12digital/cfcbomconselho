<?php
/**
 * Script de Teste - Validação do Hash da Senha
 * 
 * Testa se o hash do seed está correto para a senha 'admin123'
 */

$seedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
$password = 'admin123';

echo "Testando hash do seed...\n\n";
echo "Hash do seed: " . $seedHash . "\n";
echo "Senha: " . $password . "\n\n";

$isValid = password_verify($password, $seedHash);

if ($isValid) {
    echo "✅ SUCESSO: O hash do seed está CORRETO!\n";
    echo "password_verify('admin123', hash) = TRUE\n";
} else {
    echo "❌ ERRO: O hash do seed está INCORRETO!\n";
    echo "password_verify('admin123', hash) = FALSE\n\n";
    echo "Gerando novo hash...\n";
    $newHash = password_hash($password, PASSWORD_DEFAULT);
    echo "Novo hash: " . $newHash . "\n";
    echo "\nExecute o script reset_admin_password.php para atualizar.\n";
}

echo "\n---\n\n";
echo "Informações do hash:\n";
$info = password_get_info($seedHash);
print_r($info);
