<?php

/**
 * Script para verificar se as migrations da Etapa 2 foram aplicadas corretamente
 */

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Verificando Migrations da Etapa 2 ===\n\n";

$checks = [
    'students' => [
        'column' => 'user_id',
        'description' => 'Campo user_id em students'
    ],
    'password_reset_tokens' => [
        'table' => true,
        'description' => 'Tabela password_reset_tokens'
    ],
    'smtp_settings' => [
        'table' => true,
        'description' => 'Tabela smtp_settings'
    ],
    'permissoes' => [
        'check' => "SELECT COUNT(*) as count FROM permissoes WHERE modulo = 'usuarios'",
        'description' => 'Permissões do módulo usuarios'
    ],
    'role_permissoes' => [
        'check' => "SELECT COUNT(*) as count FROM role_permissoes rp INNER JOIN permissoes p ON rp.permissao_id = p.id WHERE p.modulo = 'usuarios' AND rp.role = 'ADMIN'",
        'description' => 'Permissões de usuarios associadas ao ADMIN'
    ],
];

$allOk = true;

// Verificar campo user_id em students
try {
    $stmt = $db->query("SHOW COLUMNS FROM students LIKE 'user_id'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ Campo user_id em students - OK\n";
    } else {
        echo "❌ Campo user_id em students - NÃO ENCONTRADO\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "❌ Erro ao verificar students: " . $e->getMessage() . "\n";
    $allOk = false;
}

// Verificar tabela password_reset_tokens
try {
    $stmt = $db->query("SHOW TABLES LIKE 'password_reset_tokens'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ Tabela password_reset_tokens - OK\n";
    } else {
        echo "❌ Tabela password_reset_tokens - NÃO ENCONTRADA\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "❌ Erro ao verificar password_reset_tokens: " . $e->getMessage() . "\n";
    $allOk = false;
}

// Verificar tabela smtp_settings
try {
    $stmt = $db->query("SHOW TABLES LIKE 'smtp_settings'");
    $result = $stmt->fetch();
    if ($result) {
        echo "✅ Tabela smtp_settings - OK\n";
    } else {
        echo "❌ Tabela smtp_settings - NÃO ENCONTRADA\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "❌ Erro ao verificar smtp_settings: " . $e->getMessage() . "\n";
    $allOk = false;
}

// Verificar permissões
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM permissoes WHERE modulo = 'usuarios'");
    $result = $stmt->fetch();
    if ($result && $result['count'] > 0) {
        echo "✅ Permissões do módulo usuarios - OK ({$result['count']} permissões)\n";
    } else {
        echo "❌ Permissões do módulo usuarios - NÃO ENCONTRADAS\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "❌ Erro ao verificar permissões: " . $e->getMessage() . "\n";
    $allOk = false;
}

// Verificar associação de permissões ao ADMIN
try {
    $stmt = $db->query("SELECT COUNT(*) as count FROM role_permissoes rp INNER JOIN permissoes p ON rp.permissao_id = p.id WHERE p.modulo = 'usuarios' AND rp.role = 'ADMIN'");
    $result = $stmt->fetch();
    if ($result && $result['count'] > 0) {
        echo "✅ Permissões de usuarios associadas ao ADMIN - OK ({$result['count']} permissões)\n";
    } else {
        echo "❌ Permissões de usuarios associadas ao ADMIN - NÃO ENCONTRADAS\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "❌ Erro ao verificar role_permissoes: " . $e->getMessage() . "\n";
    $allOk = false;
}

echo "\n";

if ($allOk) {
    echo "=== ✅ Todas as verificações passaram! ===\n";
    echo "\nSistema pronto para uso:\n";
    echo "- Acesse /usuarios como ADMIN para gerenciar usuários\n";
    echo "- Acesse /configuracoes/smtp como ADMIN para configurar e-mail\n";
    echo "- Teste os fluxos de alteração e recuperação de senha\n";
} else {
    echo "=== ⚠️  Algumas verificações falharam ===\n";
    echo "Revise os erros acima e execute novamente as migrations se necessário.\n";
}
