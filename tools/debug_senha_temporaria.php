<?php
/**
 * Script de debug para testar senha temporária
 * Testa se a senha temporária bJKUfFrYZRMy funciona com o hash no banco
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load();

echo "========================================\n";
echo "DEBUG - SENHA TEMPORÁRIA\n";
echo "========================================\n\n";

$tempPassword = 'bJKUfFrYZRMy';
$adminEmail = 'admin@cfcbomconselho.com.br';

echo "Senha temporária informada: $tempPassword\n";
echo "Email do admin: $adminEmail\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar usuário
    $stmt = $db->prepare("SELECT id, email, password, must_change_password FROM usuarios WHERE email = ?");
    $stmt->execute([$adminEmail]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Usuário encontrado:\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Must Change Password: " . ($user['must_change_password'] ?? 0) . "\n";
    echo "\n";
    
    echo "Hash armazenado no banco:\n";
    echo $user['password'] . "\n";
    echo "\n";
    
    // Testar senha temporária
    echo "TESTE DE VERIFICAÇÃO:\n";
    echo str_repeat('-', 50) . "\n";
    
    $isValid = password_verify($tempPassword, $user['password']);
    
    if ($isValid) {
        echo "✅ password_verify('$tempPassword', hash) = TRUE\n";
        echo "   A senha temporária está CORRETA!\n";
    } else {
        echo "❌ password_verify('$tempPassword', hash) = FALSE\n";
        echo "   A senha temporária NÃO corresponde ao hash!\n";
    }
    echo "\n";
    
    // Verificar se há espaços ou caracteres invisíveis
    echo "ANÁLISE DA SENHA:\n";
    echo str_repeat('-', 50) . "\n";
    echo "Tamanho da senha: " . strlen($tempPassword) . " caracteres\n";
    echo "Bytes da senha: " . bin2hex($tempPassword) . "\n";
    echo "Hash no banco (primeiros 20 chars): " . substr($user['password'], 0, 20) . "...\n";
    echo "\n";
    
    // Testar variações (com espaços, trim, etc)
    echo "TESTE DE VARIAÇÕES:\n";
    echo str_repeat('-', 50) . "\n";
    
    $variations = [
        'original' => $tempPassword,
        'trim' => trim($tempPassword),
        'ltrim' => ltrim($tempPassword),
        'rtrim' => rtrim($tempPassword),
    ];
    
    foreach ($variations as $name => $pwd) {
        $result = password_verify($pwd, $user['password']);
        echo ($result ? '✅' : '❌') . " $name: " . ($result ? 'VÁLIDA' : 'inválida') . "\n";
    }
    
    echo "\n";
    
    // Se não funcionou, verificar se o hash foi gerado corretamente
    if (!$isValid) {
        echo "DIAGNÓSTICO:\n";
        echo str_repeat('-', 50) . "\n";
        echo "A senha temporária não corresponde ao hash.\n";
        echo "Possíveis causas:\n";
        echo "1. A senha foi alterada após a geração\n";
        echo "2. O hash foi gerado com uma senha diferente\n";
        echo "3. Há problema de encoding/caracteres especiais\n";
        echo "\n";
        
        // Gerar novo hash com a senha temporária para comparar
        $newHash = password_hash($tempPassword, PASSWORD_BCRYPT);
        echo "Hash gerado AGORA com a senha temporária:\n";
        echo $newHash . "\n";
        echo "\n";
        
        echo "Teste com o novo hash:\n";
        $testNew = password_verify($tempPassword, $newHash);
        echo ($testNew ? '✅' : '❌') . " password_verify('$tempPassword', novo_hash) = " . ($testNew ? 'TRUE' : 'FALSE') . "\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
