<?php
/**
 * Script de Execução Direta - Correção de Login
 * 
 * Executa todas as verificações e correções diretamente no banco
 * Pode ser executado via linha de comando ou navegador
 */

// Inicialização
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
use App\Config\Env;
Env::load();

use App\Config\Database;

$adminEmail = 'admin@cfc.local';
$adminPassword = 'admin123';

echo "=== DIAGNÓSTICO E CORREÇÃO DE LOGIN ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // 1. Verificar banco atual
    echo "1. Verificando banco de dados...\n";
    $stmt = $db->query("SELECT DATABASE() as current_db");
    $currentDb = $stmt->fetch();
    $dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
    
    echo "   Banco configurado: {$dbName}\n";
    echo "   Banco em uso: " . ($currentDb['current_db'] ?? 'N/A') . "\n";
    
    if (($currentDb['current_db'] ?? null) !== $dbName) {
        echo "   ⚠️  AVISO: Banco diferente do configurado!\n";
    } else {
        echo "   ✅ Banco correto\n";
    }
    echo "\n";
    
    // 2. Verificar/Criar admin
    echo "2. Verificando usuário admin...\n";
    $stmt = $db->prepare("SELECT id, email, password, status FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$adminEmail]);
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo "   ❌ Admin não encontrado. Criando...\n";
        
        // Verificar se CFC existe
        $stmt = $db->query("SELECT id FROM cfcs WHERE id = 1 LIMIT 1");
        $cfc = $stmt->fetch();
        
        if (!$cfc) {
            echo "   Criando CFC padrão...\n";
            $db->exec("INSERT INTO cfcs (id, nome, status) VALUES (1, 'CFC Principal', 'ativo') ON DUPLICATE KEY UPDATE nome = VALUES(nome)");
        }
        
        // Criar admin
        $newHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("INSERT INTO usuarios (cfc_id, nome, email, password, status) VALUES (1, 'Administrador', ?, ?, 'ativo')");
        $stmt->execute([$adminEmail, $newHash]);
        $adminId = $db->lastInsertId();
        
        // Associar role ADMIN
        $stmt = $db->prepare("INSERT IGNORE INTO usuario_roles (usuario_id, role) VALUES (?, 'ADMIN')");
        $stmt->execute([$adminId]);
        
        echo "   ✅ Admin criado (ID: {$adminId})\n";
        
        // Buscar novamente
        $stmt = $db->prepare("SELECT id, email, password, status FROM usuarios WHERE email = ? LIMIT 1");
        $stmt->execute([$adminEmail]);
        $admin = $stmt->fetch();
    } else {
        echo "   ✅ Admin encontrado (ID: {$admin['id']})\n";
    }
    echo "\n";
    
    // 3. Verificar status
    echo "3. Verificando status...\n";
    if ($admin['status'] !== 'ativo') {
        echo "   ⚠️  Status: {$admin['status']}. Corrigindo...\n";
        $stmt = $db->prepare("UPDATE usuarios SET status = 'ativo' WHERE email = ?");
        $stmt->execute([$adminEmail]);
        $admin['status'] = 'ativo';
        echo "   ✅ Status corrigido para 'ativo'\n";
    } else {
        echo "   ✅ Status: ativo\n";
    }
    echo "\n";
    
    // 4. Verificar hash
    echo "4. Verificando hash da senha...\n";
    $passwordValid = password_verify($adminPassword, $admin['password']);
    
    if (!$passwordValid) {
        echo "   ❌ Hash inválido. Corrigindo...\n";
        $newHash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE usuarios SET password = ? WHERE email = ?");
        $stmt->execute([$newHash, $adminEmail]);
        echo "   ✅ Hash atualizado\n";
        echo "   Novo hash: {$newHash}\n";
        
        // Verificar novamente
        $passwordValid = password_verify($adminPassword, $newHash);
    } else {
        echo "   ✅ Hash válido\n";
    }
    echo "\n";
    
    // 5. Verificar role
    echo "5. Verificando role ADMIN...\n";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM usuario_roles WHERE usuario_id = ? AND role = 'ADMIN'");
    $stmt->execute([$admin['id']]);
    $roleCount = $stmt->fetch();
    
    if ($roleCount['count'] == 0) {
        echo "   ⚠️  Role ADMIN não encontrado. Adicionando...\n";
        $stmt = $db->prepare("INSERT IGNORE INTO usuario_roles (usuario_id, role) VALUES (?, 'ADMIN')");
        $stmt->execute([$admin['id']]);
        echo "   ✅ Role ADMIN adicionado\n";
    } else {
        echo "   ✅ Role ADMIN configurado\n";
    }
    echo "\n";
    
    // 6. Validação final
    echo "=== VALIDAÇÃO FINAL ===\n";
    $stmt = $db->prepare("SELECT id, email, password, status FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$adminEmail]);
    $finalAdmin = $stmt->fetch();
    
    $finalCheck = password_verify($adminPassword, $finalAdmin['password']) && $finalAdmin['status'] === 'ativo';
    
    if ($finalCheck) {
        echo "✅ TUDO OK! Login deve funcionar agora.\n\n";
        echo "Credenciais:\n";
        echo "  Email: {$adminEmail}\n";
        echo "  Senha: {$adminPassword}\n\n";
    } else {
        echo "❌ Ainda há problemas. Verifique manualmente.\n\n";
    }
    
    // Mostrar informações finais
    echo "=== INFORMAÇÕES DO ADMIN ===\n";
    echo "ID: {$finalAdmin['id']}\n";
    echo "Email: {$finalAdmin['email']}\n";
    echo "Status: {$finalAdmin['status']}\n";
    echo "Hash válido: " . (password_verify($adminPassword, $finalAdmin['password']) ? 'Sim' : 'Não') . "\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}

echo "\n=== CONCLUÍDO ===\n";
