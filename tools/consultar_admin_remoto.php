<?php
/**
 * Script para consultar credenciais do admin no banco remoto
 * 
 * Uso via navegador: http://localhost/cfc-v.1/tools/consultar_admin_remoto.php
 * Ou via CLI: C:\xampp\php\php.exe tools/consultar_admin_remoto.php
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

echo "========================================\n";
echo "CONSULTA - CREDENCIAIS ADMIN (BANCO REMOTO)\n";
echo "========================================\n\n";

// 1. Mostrar configuração
echo "📋 CONFIGURAÇÃO DE CONEXÃO:\n";
echo str_repeat('-', 40) . "\n";
$dbHost = $_ENV['DB_HOST'] ?? 'localhost';
$dbPort = $_ENV['DB_PORT'] ?? '3306';
$dbName = $_ENV['DB_NAME'] ?? 'cfc_db';
$dbUser = $_ENV['DB_USER'] ?? 'root';
$dbPass = $_ENV['DB_PASS'] ?? '';

$isRemote = !in_array(strtolower($dbHost), ['localhost', '127.0.0.1', '::1']);

echo "Host: " . $dbHost . ($isRemote ? " [REMOTO]" : " [LOCAL]") . "\n";
echo "Porta: " . $dbPort . "\n";
echo "Database: " . $dbName . "\n";
echo "Usuário: " . $dbUser . "\n";
echo "Senha: " . (!empty($dbPass) ? '*** (' . strlen($dbPass) . ' caracteres)' : 'vazio') . "\n";
echo "\n";

try {
    // 2. Conectar
    echo "🔌 Conectando ao banco...\n";
    $db = Database::getInstance()->getConnection();
    echo "✅ Conexão estabelecida!\n\n";
    
    // 3. Informações do servidor
    $stmt = $db->query("SELECT DATABASE() as current_db, VERSION() as mysql_version");
    $serverInfo = $stmt->fetch();
    
    echo "📊 INFORMAÇÕES DO SERVIDOR:\n";
    echo str_repeat('-', 40) . "\n";
    echo "Banco atual: " . ($serverInfo['current_db'] ?? 'N/A') . "\n";
    echo "Versão MySQL: " . ($serverInfo['mysql_version'] ?? 'N/A') . "\n";
    echo "\n";
    
    if ($serverInfo['current_db'] !== $dbName) {
        echo "⚠️  ATENÇÃO: Banco em uso (" . $serverInfo['current_db'] . ") diferente do configurado (" . $dbName . ")\n\n";
    }
    
    // 4. Consultar usuário admin
    echo "👤 CONSULTANDO USUÁRIO ADMIN:\n";
    echo str_repeat('-', 40) . "\n";
    
    $stmt = $db->prepare("
        SELECT 
            u.id,
            u.email,
            u.password,
            u.status,
            u.created_at,
            GROUP_CONCAT(r.nome SEPARATOR ', ') as roles
        FROM usuarios u
        LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
        LEFT JOIN roles r ON r.role = ur.role
        WHERE u.email = ?
        GROUP BY u.id
        LIMIT 1
    ");
    $stmt->execute(['admin@cfc.local']);
    $admin = $stmt->fetch();
    
    if ($admin) {
        echo "✅ Usuário encontrado!\n\n";
        echo "DADOS DO ADMINISTRADOR:\n";
        echo str_repeat('-', 40) . "\n";
        echo "ID: " . $admin['id'] . "\n";
        echo "Email: " . $admin['email'] . "\n";
        echo "Status: " . $admin['status'] . ($admin['status'] === 'ativo' ? " ✅" : " ❌") . "\n";
        echo "Roles/Papéis: " . ($admin['roles'] ?? 'Nenhum') . "\n";
        echo "Criado em: " . ($admin['created_at'] ?? 'N/A') . "\n";
        echo "\n";
        echo "Hash da senha:\n";
        echo $admin['password'] . "\n";
        echo "\n";
        
        // Verificar tipo de hash
        $hashInfo = password_get_info($admin['password']);
        if ($hashInfo) {
            echo "Tipo de hash: " . $hashInfo['algoName'] . " (custo: " . $hashInfo['options']['cost'] . ")\n";
        } else {
            echo "⚠️  Hash inválido ou não é bcrypt\n";
        }
        echo "\n";
        
        // Testar senha padrão
        $testPassword = 'admin123';
        $passwordValid = password_verify($testPassword, $admin['password']);
        
        echo "🔐 TESTE DE SENHA:\n";
        echo str_repeat('-', 40) . "\n";
        if ($passwordValid) {
            echo "✅ password_verify('admin123', hash) = TRUE\n";
            echo "\n";
            echo "════════════════════════════════════════\n";
            echo "CREDENCIAIS DE ACESSO:\n";
            echo "════════════════════════════════════════\n";
            echo "Email: admin@cfc.local\n";
            echo "Senha: admin123\n";
            echo "════════════════════════════════════════\n";
        } else {
            echo "❌ password_verify('admin123', hash) = FALSE\n";
            echo "\n";
            echo "⚠️  A senha padrão não corresponde ao hash.\n";
            echo "    A senha pode ter sido alterada no banco.\n";
        }
        echo "\n";
        
        if ($admin['status'] !== 'ativo') {
            echo "⚠️  ATENÇÃO: Usuário não está ativo! Isso pode impedir o login.\n\n";
        }
        
    } else {
        echo "❌ Usuário admin@cfc.local NÃO encontrado!\n\n";
        echo "💡 SOLUÇÃO: Execute o seed do banco:\n";
        echo "   database/seeds/001_seed_initial_data.sql\n\n";
    }
    
    // 5. Listar todos os admins
    echo "📋 TODOS OS USUÁRIOS ADMINISTRADORES:\n";
    echo str_repeat('-', 40) . "\n";
    
    $stmt = $db->query("
        SELECT 
            u.id,
            u.email,
            u.status,
            u.created_at,
            GROUP_CONCAT(r.nome SEPARATOR ', ') as roles
        FROM usuarios u
        LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id
        LEFT JOIN roles r ON r.role = ur.role
        WHERE r.role = 'admin' OR u.email LIKE '%admin%'
        GROUP BY u.id
        ORDER BY u.id
    ");
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        printf("%-5s %-30s %-10s %-30s %s\n", "ID", "Email", "Status", "Roles", "Criado em");
        echo str_repeat('-', 100) . "\n";
        foreach ($admins as $adm) {
            printf("%-5s %-30s %-10s %-30s %s\n", 
                $adm['id'], 
                $adm['email'], 
                $adm['status'],
                $adm['roles'] ?? 'Nenhum',
                $adm['created_at'] ?? 'N/A'
            );
        }
    } else {
        echo "Nenhum usuário administrador encontrado.\n";
    }
    echo "\n";
    
    echo "========================================\n";
    echo "CONSULTA CONCLUÍDA\n";
    echo "========================================\n";
    
} catch (\PDOException $e) {
    echo "❌ ERRO na conexão:\n";
    echo "   Código: " . $e->getCode() . "\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    exit(1);
} catch (\Exception $e) {
    echo "❌ ERRO:\n";
    echo "   Mensagem: " . $e->getMessage() . "\n";
    echo "   Arquivo: " . $e->getFile() . "\n";
    echo "   Linha: " . $e->getLine() . "\n";
    exit(1);
}
