<?php
/**
 * Script para redefinir senha do admin
 * Gera uma nova senha temporária e atualiza no banco
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load();

echo "========================================\n";
echo "REDEFINIR SENHA DO ADMIN\n";
echo "========================================\n\n";

$email = 'admin@cfcbomconselho.com.br';

try {
    $db = Database::getInstance()->getConnection();
    
    // Verificar se usuário existe
    $stmt = $db->prepare("SELECT id, email, status FROM usuarios WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        echo "❌ Usuário não encontrado!\n";
        exit(1);
    }
    
    echo "✅ Usuário encontrado:\n";
    echo "   ID: " . $user['id'] . "\n";
    echo "   Email: " . $user['email'] . "\n";
    echo "   Status: " . $user['status'] . "\n\n";
    
    // Gerar nova senha temporária
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $tempPassword = substr(str_shuffle(str_repeat($chars, ceil(12 / strlen($chars)))), 0, 12);
    $hashedPassword = password_hash($tempPassword, PASSWORD_BCRYPT);
    
    echo "Gerando nova senha temporária...\n";
    echo "Senha gerada: $tempPassword\n";
    echo "Hash gerado: " . substr($hashedPassword, 0, 30) . "...\n\n";
    
    // Atualizar no banco
    $stmt = $db->prepare("
        UPDATE usuarios 
        SET password = ?, must_change_password = 1 
        WHERE id = ?
    ");
    $stmt->execute([$hashedPassword, $user['id']]);
    
    // Verificar se atualizou
    $stmt = $db->prepare("SELECT password, must_change_password FROM usuarios WHERE id = ?");
    $stmt->execute([$user['id']]);
    $updated = $stmt->fetch();
    
    // Testar se a senha funciona
    $testVerify = password_verify($tempPassword, $updated['password']);
    
    echo "════════════════════════════════════════\n";
    if ($testVerify) {
        echo "✅ SENHA REDEFINIDA COM SUCESSO!\n";
        echo "════════════════════════════════════════\n\n";
        echo "CREDENCIAIS DE ACESSO:\n";
        echo "Email: $email\n";
        echo "Senha: $tempPassword\n\n";
        echo "⚠️  IMPORTANTE: Anote esta senha! Ela será exibida apenas uma vez.\n";
        echo "   Você será obrigado a alterar a senha no primeiro login.\n";
    } else {
        echo "❌ ERRO: A senha foi atualizada mas a verificação falhou!\n";
        echo "════════════════════════════════════════\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
