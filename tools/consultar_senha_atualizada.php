<?php
/**
 * Consulta detalhada da senha atualizada do admin no banco remoto
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load();

echo "========================================\n";
echo "CONSULTA - SENHA ATUALIZADA DO ADMIN\n";
echo "========================================\n\n";

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar todos os admins
    $stmt = $db->query("
        SELECT 
            u.id,
            u.email,
            u.password,
            u.status,
            u.created_at,
            u.updated_at,
            GROUP_CONCAT(r.nome SEPARATOR ', ') as roles
        FROM usuarios u
        LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
        LEFT JOIN roles r ON r.role = ur.role
        WHERE ur.role = 'admin' OR u.email LIKE '%admin%'
        GROUP BY u.id
        ORDER BY u.id
    ");
    $admins = $stmt->fetchAll();
    
    if (count($admins) === 0) {
        echo "❌ Nenhum usuário administrador encontrado.\n";
        exit(1);
    }
    
    foreach ($admins as $admin) {
        echo "════════════════════════════════════════\n";
        echo "USUÁRIO ADMINISTRADOR\n";
        echo "════════════════════════════════════════\n\n";
        
        echo "ID: " . $admin['id'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Status: " . $admin['status'] . "\n";
        echo "Roles: " . ($admin['roles'] ?? 'Nenhum') . "\n";
        echo "Criado em: " . ($admin['created_at'] ?? 'N/A') . "\n";
        echo "Atualizado em: " . ($admin['updated_at'] ?? 'N/A') . "\n";
        echo "\n";
        
        echo "HASH DA SENHA (ATUAL):\n";
        echo str_repeat('-', 50) . "\n";
        echo $admin['password'] . "\n";
        echo "\n";
        
        // Informações do hash
        $hashInfo = password_get_info($admin['password']);
        if ($hashInfo) {
            echo "INFORMAÇÕES DO HASH:\n";
            echo str_repeat('-', 50) . "\n";
            echo "Algoritmo: " . $hashInfo['algoName'] . "\n";
            echo "Custo: " . $hashInfo['options']['cost'] . "\n";
            echo "Tamanho do hash: " . strlen($admin['password']) . " caracteres\n";
            echo "\n";
        }
        
        // Análise da data de atualização
        if ($admin['updated_at'] && $admin['updated_at'] !== $admin['created_at']) {
            $created = new DateTime($admin['created_at']);
            $updated = new DateTime($admin['updated_at']);
            $diff = $created->diff($updated);
            
            echo "ANÁLISE TEMPORAL:\n";
            echo str_repeat('-', 50) . "\n";
            echo "Criado: " . $admin['created_at'] . "\n";
            echo "Última atualização: " . $admin['updated_at'] . "\n";
            echo "Tempo desde criação: " . $diff->format('%a dias, %h horas, %i minutos') . "\n";
            echo "⚠️  A senha foi atualizada após a criação do usuário.\n";
            echo "\n";
        }
        
        // Testar senhas comuns e variações
        echo "TESTE DE SENHAS:\n";
        echo str_repeat('-', 50) . "\n";
        
        // Extrair domínio do email para variações
        $emailParts = explode('@', $admin['email']);
        $domain = $emailParts[1] ?? '';
        $domainName = explode('.', $domain)[0] ?? '';
        
        $testPasswords = [
            // Senhas padrão
            'admin123',
            'admin',
            '123456',
            'password',
            'senha',
            'cfc123',
            
            // Baseado no domínio
            'bomconselho',
            'cfcbomconselho',
            'bomconselho123',
            'cfcbomconselho123',
            'admin@cfcbomconselho',
            
            // Variações comuns
            'Admin123',
            'ADMIN123',
            'Admin@123',
            'cfc@2024',
            'cfc@2025',
            'bomconselho@2024',
            'bomconselho@2025',
            
            // Outras possibilidades
            'cfc2024',
            'cfc2025',
            'bomconselho2024',
            'bomconselho2025',
        ];
        
        $found = false;
        $tested = 0;
        
        foreach ($testPasswords as $testPwd) {
            $tested++;
            if (password_verify($testPwd, $admin['password'])) {
                echo "✅ SENHA ENCONTRADA!\n";
                echo "   Testada: '$testPwd'\n";
                echo "   Tentativas: $tested\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "❌ Nenhuma das senhas testadas corresponde ao hash.\n";
            echo "   Total de senhas testadas: $tested\n";
            echo "\n";
            echo "OPÇÕES PARA RECUPERAR/REDEFINIR A SENHA:\n";
            echo str_repeat('-', 50) . "\n";
            echo "1. Usar funcionalidade de recuperação de senha do sistema\n";
            echo "2. Redefinir via banco de dados (gerar novo hash)\n";
            echo "3. Contatar o administrador que criou/alterou a senha\n";
            echo "\n";
        }
        
        echo "\n";
        
        // Mostrar credenciais se encontradas
        if ($found) {
            echo "════════════════════════════════════════\n";
            echo "CREDENCIAIS DE ACESSO ATUALIZADAS:\n";
            echo "════════════════════════════════════════\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Senha: [Encontrada nos testes acima]\n";
            echo "════════════════════════════════════════\n";
        } else {
            echo "════════════════════════════════════════\n";
            echo "CREDENCIAIS:\n";
            echo "════════════════════════════════════════\n";
            echo "Email: " . $admin['email'] . "\n";
            echo "Senha: [Não identificada - requer redefinição]\n";
            echo "════════════════════════════════════════\n";
        }
        
        echo "\n\n";
    }
    
    // Verificar se existe o usuário padrão também
    echo "════════════════════════════════════════\n";
    echo "VERIFICAÇÃO DO USUÁRIO PADRÃO\n";
    echo "════════════════════════════════════════\n\n";
    
    $stmt = $db->prepare("SELECT id, email, status FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute(['admin@cfc.local']);
    $defaultAdmin = $stmt->fetch();
    
    if ($defaultAdmin) {
        echo "✅ Usuário padrão (admin@cfc.local) existe:\n";
        echo "   ID: " . $defaultAdmin['id'] . "\n";
        echo "   Status: " . $defaultAdmin['status'] . "\n";
    } else {
        echo "❌ Usuário padrão (admin@cfc.local) NÃO existe no banco.\n";
        echo "   Apenas o usuário personalizado foi encontrado.\n";
    }
    
    echo "\n";
    echo "========================================\n";
    echo "CONSULTA CONCLUÍDA\n";
    echo "========================================\n";
    
} catch (\Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . "\n";
    echo "Linha: " . $e->getLine() . "\n";
    exit(1);
}
