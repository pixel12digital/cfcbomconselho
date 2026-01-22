<?php
/**
 * Consulta detalhes do usuário admin encontrado no banco remoto
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load();

echo "========================================\n";
echo "DETALHES DO USUÁRIO ADMINISTRADOR\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar o admin encontrado
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.email,
            u.password,
            u.status,
            u.created_at,
            u.updated_at,
            GROUP_CONCAT(r.nome SEPARATOR ', ') as roles,
            GROUP_CONCAT(ur.role SEPARATOR ', ') as role_codes
        FROM usuarios u
        LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
        LEFT JOIN roles r ON r.role = ur.role
        WHERE ur.role = 'admin' OR u.email LIKE '%admin%'
        GROUP BY u.id
        ORDER BY u.id
        LIMIT 1
    ");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ USUÁRIO ADMINISTRADOR ENCONTRADO:\n";
        echo str_repeat('=', 50) . "\n\n";
        
        echo "ID: " . $admin['id'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        echo "Roles: " . ($admin['roles'] ?? 'Nenhum') . "\n";
        echo "Códigos de Roles: " . ($admin['role_codes'] ?? 'Nenhum') . "\n";
        echo "Criado em: " . ($admin['created_at'] ?? 'N/A') . "\n";
        echo "Atualizado em: " . ($admin['updated_at'] ?? 'N/A') . "\n";
        echo "\n";
        
        echo "HASH DA SENHA:\n";
        echo str_repeat('-', 50) . "\n";
        echo $admin['password'] . "\n";
        echo "\n";
        
        // Verificar hash
        $hashInfo = password_get_info($admin['password']);
        if ($hashInfo) {
            echo "Tipo: " . $hashInfo['algoName'] . "\n";
            echo "Custo: " . $hashInfo['options']['cost'] . "\n";
        }
        echo "\n";
        
        // Testar senhas comuns
        echo "TESTE DE SENHAS COMUNS:\n";
        echo str_repeat('-', 50) . "\n";
        
        $testPasswords = [
            'admin123',
            'admin',
            '123456',
            'password',
            'senha',
            'cfc123',
            'bomconselho',
            'cfcbomconselho'
        ];
        
        $found = false;
        foreach ($testPasswords as $testPwd) {
            if (password_verify($testPwd, $admin['password'])) {
                echo "✅ SENHA ENCONTRADA: '$testPwd'\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "❌ Nenhuma das senhas comuns corresponde ao hash.\n";
            echo "   A senha foi alterada ou é diferente das padrões testadas.\n";
        }
        
        echo "\n";
        echo "════════════════════════════════════════\n";
        if ($found) {
            echo "CREDENCIAIS DE ACESSO:\n";
            echo "════════════════════════════════════════\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Senha: [A senha foi encontrada nos testes acima]\n";
        } else {
            echo "CREDENCIAIS:\n";
            echo "════════════════════════════════════════\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Senha: [Não identificada - pode ter sido alterada]\n";
        }
        echo "════════════════════════════════════════\n";
        
    } else {
        echo "❌ Nenhum usuário administrador encontrado.\n";
    }
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
