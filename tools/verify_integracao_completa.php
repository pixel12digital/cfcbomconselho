<?php

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Verificação Completa da Integração Identidade/Acesso ===\n\n";

$allOk = true;

// 1. Verificar campo must_change_password
echo "1. Verificando campo must_change_password...\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM usuarios LIKE 'must_change_password'");
    $result = $stmt->fetch();
    if ($result) {
        echo "   ✅ Campo must_change_password existe\n";
    } else {
        echo "   ❌ Campo must_change_password NÃO existe\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    $allOk = false;
}

// 2. Verificar campo user_id em students
echo "\n2. Verificando campo user_id em students...\n";
try {
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'user_id'");
    $result = $stmt->fetch();
    if ($result) {
        echo "   ✅ Campo user_id existe\n";
    } else {
        echo "   ❌ Campo user_id NÃO existe\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    $allOk = false;
}

// 3. Verificar usuário SECRETARIA
echo "\n3. Verificando usuário SECRETARIA...\n";
try {
    $stmt = $db->prepare("SELECT u.*, GROUP_CONCAT(ur.role) as roles FROM usuarios u LEFT JOIN usuario_roles ur ON ur.usuario_id = u.id WHERE u.email = 'secretaria@cfc.local' GROUP BY u.id");
    $stmt->execute();
    $user = $stmt->fetch();
    if ($user) {
        echo "   ✅ Usuário SECRETARIA existe\n";
        echo "      - Email: {$user['email']}\n";
        echo "      - Perfil: " . ($user['roles'] ?? 'N/A') . "\n";
        echo "      - Status: {$user['status']}\n";
    } else {
        echo "   ❌ Usuário SECRETARIA NÃO existe\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    $allOk = false;
}

// 4. Verificar permissões
echo "\n4. Verificando permissões do módulo usuarios...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM permissoes WHERE modulo = 'usuarios'");
    $result = $stmt->fetch();
    if ($result && $result['count'] >= 5) {
        echo "   ✅ {$result['count']} permissões encontradas\n";
    } else {
        echo "   ❌ Permissões insuficientes\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    $allOk = false;
}

// 5. Verificar alunos sem acesso (exemplo)
echo "\n5. Verificando alunos sem acesso...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM students WHERE (user_id IS NULL OR user_id = 0) AND email IS NOT NULL AND email != ''");
    $result = $stmt->fetch();
    echo "   ℹ️  {$result['count']} aluno(s) sem acesso (com e-mail válido)\n";
} catch (\Exception $e) {
    echo "   ⚠️  Erro ao verificar: " . $e->getMessage() . "\n";
}

// 6. Verificar instrutores sem acesso (exemplo)
echo "\n6. Verificando instrutores sem acesso...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM instructors WHERE (user_id IS NULL OR user_id = 0) AND email IS NOT NULL AND email != ''");
    $result = $stmt->fetch();
    echo "   ℹ️  {$result['count']} instrutor(es) sem acesso (com e-mail válido)\n";
} catch (\Exception $e) {
    echo "   ⚠️  Erro ao verificar: " . $e->getMessage() . "\n";
}

// 7. Verificar usuários com vínculos
echo "\n7. Verificando usuários com vínculos...\n";
try {
    $stmt = $db->query("
        SELECT 
            COUNT(DISTINCT u.id) as total_usuarios,
            COUNT(DISTINCT s.id) as usuarios_com_aluno,
            COUNT(DISTINCT i.id) as usuarios_com_instrutor
        FROM usuarios u
        LEFT JOIN students s ON s.user_id = u.id
        LEFT JOIN instructors i ON i.user_id = u.id
    ");
    $result = $stmt->fetch();
    echo "   ℹ️  Total de usuários: {$result['total_usuarios']}\n";
    echo "   ℹ️  Usuários vinculados a alunos: {$result['usuarios_com_aluno']}\n";
    echo "   ℹ️  Usuários vinculados a instrutores: {$result['usuarios_com_instrutor']}\n";
} catch (\Exception $e) {
    echo "   ⚠️  Erro ao verificar: " . $e->getMessage() . "\n";
}

echo "\n";

if ($allOk) {
    echo "=== ✅ INTEGRAÇÃO COMPLETA E FUNCIONAL ===\n\n";
    echo "Credenciais de teste:\n";
    echo "  ADMIN: admin@cfc.local / admin123\n";
    echo "  SECRETARIA: secretaria@cfc.local / secretaria123\n";
    echo "\nPróximos passos:\n";
    echo "1. Criar um aluno com e-mail → verificar acesso automático\n";
    echo "2. Criar um instrutor com e-mail → verificar acesso automático\n";
    echo "3. Testar login com cada perfil\n";
    echo "4. Testar troca obrigatória de senha\n";
    echo "5. Configurar SMTP em /configuracoes/smtp\n";
} else {
    echo "=== ⚠️  ALGUMAS VERIFICAÇÕES FALHARAM ===\n";
    echo "Revise os erros acima.\n";
}
