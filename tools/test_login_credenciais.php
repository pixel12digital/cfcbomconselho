<?php
/**
 * Teste direto de login com as credenciais informadas
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;
use App\Models\User;

Env::load();

echo "========================================\n";
echo "TESTE DE LOGIN - CREDENCIAIS\n";
echo "========================================\n\n";

$email = 'admin@cfcbomconselho.com.br';
$password = 'bJKUfFrYZRMy';

echo "Email: $email\n";
echo "Senha: $password\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar usuário
    echo "1. Buscando usuário no banco...\n";
    $user = User::findByEmail($email);
    
    if (!$user) {
        echo "❌ Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Usuário encontrado:\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Status: " . $user['status'] . "\n";
    echo "   Must Change Password: " . ($user['must_change_password'] ?? 0) . "\n";
    echo "\n";
    
    // Verificar senha
    echo "2. Verificando senha...\n";
    $passwordValid = password_verify($password, $user['password']);
    
    echo "   Hash no banco: " . substr($user['password'], 0, 30) . "...\n";
    echo "   password_verify: " . ($passwordValid ? 'TRUE ✅' : 'FALSE ❌') . "\n";
    echo "\n";
    
    if (!$passwordValid) {
        echo "❌ A senha não corresponde ao hash no banco!\n\n";
        
        echo "3. Testando outras possibilidades...\n";
        
        // Testar variações
        $variations = [
            trim($password),
            ltrim($password),
            rtrim($password),
            strtolower($password),
            strtoupper($password),
        ];
        
        $found = false;
        foreach ($variations as $var) {
            if (password_verify($var, $user['password'])) {
                echo "   ✅ Senha encontrada na variação: '$var'\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "   ❌ Nenhuma variação funcionou\n";
        }
        
        echo "\n";
        echo "DIAGNÓSTICO:\n";
        echo "A senha informada não corresponde ao hash no banco.\n";
        echo "Isso pode significar:\n";
        echo "1. A senha foi alterada após a geração\n";
        echo "2. O hash foi gerado com uma senha diferente\n";
        echo "3. Há problema de encoding\n\n";
        
        echo "SOLUÇÃO:\n";
        echo "É necessário redefinir a senha do usuário.\n";
        echo "Posso criar um script para gerar uma nova senha temporária.\n";
        
    } else {
        echo "✅ SENHA VÁLIDA!\n";
        echo "O login deveria funcionar com essas credenciais.\n\n";
        
        // Verificar status
        if ($user['status'] !== 'ativo') {
            echo "⚠️  ATENÇÃO: Usuário não está ativo!\n";
            echo "   Status: " . $user['status'] . "\n";
            echo "   Isso pode impedir o login mesmo com senha correta.\n";
        }
    }
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
