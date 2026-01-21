<?php
/**
 * Script para verificar e criar acesso para aluno específico
 * Uso: php tools/verificar_aluno_acesso.php [CPF]
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
    // Fallback: autoload manual
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $base_dir = APP_PATH . '/';
        
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        
        if (file_exists($file)) {
            require $file;
        }
    });
}

// Carregar configurações
require_once APP_PATH . '/Config/Env.php';
require_once APP_PATH . '/Config/Database.php';

use App\Config\Env;
use App\Config\Database;
use App\Services\UserCreationService;

// Carregar variáveis de ambiente do arquivo .env
Env::load();

$cpf = $argv[1] ?? '29561350076';

echo "=== Verificação de Acesso para Aluno ===\n\n";
echo "CPF informado: {$cpf}\n\n";

$db = Database::getInstance()->getConnection();

// Buscar aluno por CPF
$cpfClean = preg_replace('/[^0-9]/', '', $cpf);
$stmt = $db->prepare("
    SELECT id, name, full_name, cpf, email, user_id, cfc_id 
    FROM students 
    WHERE cpf = ?
");
$stmt->execute([$cpfClean]);
$student = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$student) {
    echo "❌ Aluno não encontrado com CPF: {$cpf}\n";
    exit(1);
}

echo "✅ Aluno encontrado:\n";
echo "   ID: {$student['id']}\n";
echo "   Nome: " . ($student['full_name'] ?: $student['name']) . "\n";
echo "   CPF: {$student['cpf']}\n";
echo "   Email: " . ($student['email'] ?: '(não informado)') . "\n";
echo "   CFC ID: {$student['cfc_id']}\n";
echo "   User ID: " . ($student['user_id'] ?: '(não vinculado)') . "\n\n";

// Verificar se tem email
if (empty($student['email'])) {
    echo "⚠️  ATENÇÃO: Aluno não possui email cadastrado!\n";
    echo "   É necessário adicionar um email válido antes de criar acesso.\n";
    echo "   Você pode editar o aluno em: /alunos/{$student['id']}/editar\n\n";
    exit(1);
}

// Verificar se email é válido
if (!filter_var($student['email'], FILTER_VALIDATE_EMAIL)) {
    echo "⚠️  ATENÇÃO: Email inválido: {$student['email']}\n";
    echo "   É necessário corrigir o email antes de criar acesso.\n\n";
    exit(1);
}

// Verificar se já tem user_id
if (!empty($student['user_id'])) {
    echo "Verificando se usuário existe...\n";
    
    $stmt = $db->prepare("SELECT id, nome, email, status FROM usuarios WHERE id = ?");
    $stmt->execute([$student['user_id']]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "✅ Aluno já possui acesso vinculado:\n";
        echo "   User ID: {$user['id']}\n";
        echo "   Nome: {$user['nome']}\n";
        echo "   Email: {$user['email']}\n";
        echo "   Status: {$user['status']}\n\n";
        
        // Verificar role
        $stmt = $db->prepare("SELECT role FROM usuario_roles WHERE usuario_id = ?");
        $stmt->execute([$user['id']]);
        $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
        
        if (!empty($roles)) {
            echo "   Perfis: " . implode(', ', $roles) . "\n";
        }
        
        exit(0);
    } else {
        echo "⚠️  Aluno tem user_id ({$student['user_id']}) mas usuário não existe!\n";
        echo "   Limpando referência inválida...\n";
        
        $stmt = $db->prepare("UPDATE students SET user_id = NULL WHERE id = ?");
        $stmt->execute([$student['id']]);
        
        echo "✅ Referência limpa. Pode criar acesso agora.\n\n";
    }
}

// Verificar se email já está em uso
$stmt = $db->prepare("SELECT id, nome, email FROM usuarios WHERE email = ?");
$stmt->execute([$student['email']]);
$existingUser = $stmt->fetch(\PDO::FETCH_ASSOC);

if ($existingUser) {
    echo "⚠️  ATENÇÃO: Email {$student['email']} já está em uso por outro usuário:\n";
    echo "   User ID: {$existingUser['id']}\n";
    echo "   Nome: {$existingUser['nome']}\n\n";
    
    // Perguntar se quer vincular
    echo "Deseja vincular este aluno ao usuário existente? (s/n): ";
    $handle = fopen("php://stdin", "r");
    $line = fgets($handle);
    fclose($handle);
    
    if (trim(strtolower($line)) === 's') {
        $stmt = $db->prepare("UPDATE students SET user_id = ? WHERE id = ?");
        $stmt->execute([$existingUser['id'], $student['id']]);
        
        // Verificar se tem role ALUNO
        $stmt = $db->prepare("SELECT role FROM usuario_roles WHERE usuario_id = ? AND role = 'ALUNO'");
        $stmt->execute([$existingUser['id']]);
        if (!$stmt->fetch()) {
            $stmt = $db->prepare("INSERT INTO usuario_roles (usuario_id, role) VALUES (?, 'ALUNO')");
            $stmt->execute([$existingUser['id']]);
        }
        
        echo "✅ Aluno vinculado ao usuário existente!\n";
        exit(0);
    } else {
        echo "Operação cancelada.\n";
        exit(1);
    }
}

// Criar acesso
echo "Criando acesso para o aluno...\n\n";

try {
    $_SESSION['cfc_id'] = $student['cfc_id']; // Necessário para o service
    
    $userService = new UserCreationService();
    $userData = $userService->createForStudent(
        $student['id'], 
        $student['email'], 
        $student['full_name'] ?: $student['name']
    );
    
    echo "✅ Acesso criado com sucesso!\n\n";
    echo "Detalhes do acesso:\n";
    echo "   User ID: {$userData['user_id']}\n";
    echo "   Email: {$userData['email']}\n";
    echo "   Senha temporária: {$userData['temp_password']}\n\n";
    
    echo "⚠️  IMPORTANTE: Anote a senha temporária acima!\n";
    echo "   Ela será necessária para o primeiro login do aluno.\n\n";
    
} catch (\Exception $e) {
    echo "❌ Erro ao criar acesso: {$e->getMessage()}\n";
    echo "   Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
