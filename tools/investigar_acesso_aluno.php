<?php
/**
 * Script para investigar acesso de aluno específico
 * Uso: php tools/investigar_acesso_aluno.php [CPF ou NOME]
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

// Carregar variáveis de ambiente
Env::load();

$searchTerm = $argv[1] ?? 'cliente teste 001';

echo "=== Investigação de Acesso do Aluno ===\n\n";
echo "Termo de busca: {$searchTerm}\n\n";

$db = Database::getInstance()->getConnection();

// Buscar aluno por CPF ou nome
$cpfClean = preg_replace('/[^0-9]/', '', $searchTerm);
$isCpf = strlen($cpfClean) === 11;

if ($isCpf) {
    $stmt = $db->prepare("
        SELECT id, name, full_name, cpf, email, user_id, cfc_id 
        FROM students 
        WHERE cpf = ?
    ");
    $stmt->execute([$cpfClean]);
} else {
    $stmt = $db->prepare("
        SELECT id, name, full_name, cpf, email, user_id, cfc_id 
        FROM students 
        WHERE (full_name LIKE ? OR name LIKE ?)
        LIMIT 10
    ");
    $searchPattern = "%{$searchTerm}%";
    $stmt->execute([$searchPattern, $searchPattern]);
}

$students = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (empty($students)) {
    echo "❌ Nenhum aluno encontrado com o termo: {$searchTerm}\n";
    exit(1);
}

if (count($students) > 1) {
    echo "⚠️  Múltiplos alunos encontrados. Mostrando todos:\n\n";
}

foreach ($students as $student) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📋 DADOS DO ALUNO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: {$student['id']}\n";
    echo "Nome: " . ($student['full_name'] ?: $student['name']) . "\n";
    echo "CPF: {$student['cpf']}\n";
    echo "Email: " . ($student['email'] ?: '(não informado)') . "\n";
    echo "CFC ID: {$student['cfc_id']}\n";
    echo "User ID vinculado: " . ($student['user_id'] ?: '(não vinculado)') . "\n\n";
    
    if (empty($student['user_id'])) {
        echo "⚠️  STATUS: Aluno NÃO possui acesso vinculado\n";
        echo "   Ação necessária: Criar acesso em /usuarios/novo\n\n";
        continue;
    }
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "👤 DADOS DO USUÁRIO VINCULADO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
    // Buscar dados do usuário
    $stmt = $db->prepare("
        SELECT u.id, u.nome, u.email, u.status, u.must_change_password, u.created_at
        FROM usuarios u
        WHERE u.id = ?
    ");
    $stmt->execute([$student['user_id']]);
    $user = $stmt->fetch(\PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo "❌ ERRO: User ID {$student['user_id']} não existe na tabela usuarios!\n";
        echo "   Isso indica uma referência inválida.\n";
        echo "   Ação necessária: Limpar referência e criar novo acesso\n\n";
        continue;
    }
    
    echo "User ID: {$user['id']}\n";
    echo "Nome: {$user['nome']}\n";
    echo "Email: {$user['email']}\n";
    echo "Status: {$user['status']}\n";
    echo "Deve trocar senha: " . ($user['must_change_password'] ? 'Sim' : 'Não') . "\n";
    echo "Criado em: " . date('d/m/Y H:i:s', strtotime($user['created_at'])) . "\n\n";
    
    // Buscar roles/perfis
    $stmt = $db->prepare("SELECT role FROM usuario_roles WHERE usuario_id = ?");
    $stmt->execute([$user['id']]);
    $roles = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    
    echo "Perfis/Roles: " . (empty($roles) ? '(nenhum)' : implode(', ', $roles)) . "\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "🔗 COMO ACESSAR/EDITAR O ACESSO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "1. Lista de Usuários:\n";
    echo "   URL: /usuarios\n";
    echo "   Procure por: \"{$user['nome']}\" ou \"{$user['email']}\"\n";
    echo "   Vínculo deve aparecer como: \"Aluno: " . ($student['full_name'] ?: $student['name']) . "\"\n\n";
    
    echo "2. Editar Usuário:\n";
    echo "   URL: /usuarios/{$user['id']}/editar\n";
    echo "   Ações disponíveis:\n";
    echo "   - Alterar status (ativo/inativo)\n";
    echo "   - Gerar senha temporária\n";
    echo "   - Gerar link de ativação\n";
    echo "   - Enviar link por email\n\n";
    
    echo "3. Ver Detalhes do Aluno:\n";
    echo "   URL: /alunos/{$student['id']}\n";
    echo "   (Nota: A página do aluno não mostra informações do acesso atualmente)\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 RESUMO\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✅ Aluno possui acesso vinculado\n";
    echo "   User ID: {$user['id']}\n";
    echo "   Email de acesso: {$user['email']}\n";
    echo "   Status: {$user['status']}\n";
    echo "   Para editar: /usuarios/{$user['id']}/editar\n\n";
}

echo "\n";
echo "💡 DICA: Se precisar resetar a senha, acesse:\n";
echo "   /usuarios/{$user['id']}/editar\n";
echo "   E use o botão 'Gerar Senha Temporária' ou 'Gerar Link de Ativação'\n\n";
