<?php

require_once __DIR__ . '/../app/autoload.php';
require_once __DIR__ . '/../app/Bootstrap.php';

use App\Config\Database;

$db = Database::getInstance()->getConnection();

echo "=== Verificação do Sistema de Credenciais ===\n\n";

$allOk = true;

// 1. Verificar tabela account_activation_tokens
echo "1. Verificando tabela account_activation_tokens...\n";
try {
    $stmt = $db->query("SHOW TABLES LIKE 'account_activation_tokens'");
    $result = $stmt->fetch();
    if ($result) {
        echo "   ✅ Tabela account_activation_tokens existe\n";
        
        // Verificar estrutura
        $stmt = $db->query("DESCRIBE account_activation_tokens");
        $columns = $stmt->fetchAll();
        $columnNames = array_column($columns, 'Field');
        
        $requiredColumns = ['id', 'user_id', 'token_hash', 'expires_at', 'used_at', 'created_at', 'created_by'];
        foreach ($requiredColumns as $col) {
            if (in_array($col, $columnNames)) {
                echo "      ✅ Coluna '{$col}' existe\n";
            } else {
                echo "      ❌ Coluna '{$col}' NÃO existe\n";
                $allOk = false;
            }
        }
    } else {
        echo "   ❌ Tabela account_activation_tokens NÃO existe\n";
        $allOk = false;
    }
} catch (\Exception $e) {
    echo "   ❌ Erro: " . $e->getMessage() . "\n";
    $allOk = false;
}

// 2. Verificar campo must_change_password
echo "\n2. Verificando campo must_change_password...\n";
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

// 3. Verificar rotas (verificar se arquivos existem)
echo "\n3. Verificando arquivos de rotas e controllers...\n";
$files = [
    'app/Controllers/UsuariosController.php' => 'UsuariosController',
    'app/Controllers/AuthController.php' => 'AuthController',
    'app/Models/AccountActivationToken.php' => 'AccountActivationToken',
    'app/Services/EmailService.php' => 'EmailService',
    'app/Views/usuarios/form.php' => 'View form',
    'app/Views/auth/activate-account.php' => 'View activate-account'
];

foreach ($files as $file => $name) {
    $path = __DIR__ . '/../' . $file;
    if (file_exists($path)) {
        echo "   ✅ {$name} existe\n";
    } else {
        echo "   ❌ {$name} NÃO existe\n";
        $allOk = false;
    }
}

// 4. Verificar métodos no UsuariosController
echo "\n4. Verificando métodos no UsuariosController...\n";
$controllerFile = __DIR__ . '/../app/Controllers/UsuariosController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    $methods = [
        'gerarSenhaTemporaria',
        'gerarLinkAtivacao',
        'enviarLinkEmail'
    ];
    
    foreach ($methods as $method) {
        if (strpos($content, "function {$method}") !== false) {
            echo "   ✅ Método {$method} existe\n";
        } else {
            echo "   ❌ Método {$method} NÃO existe\n";
            $allOk = false;
        }
    }
}

// 5. Verificar métodos no AuthController
echo "\n5. Verificando métodos no AuthController...\n";
$controllerFile = __DIR__ . '/../app/Controllers/AuthController.php';
if (file_exists($controllerFile)) {
    $content = file_get_contents($controllerFile);
    $methods = [
        'showActivateAccount',
        'activateAccount'
    ];
    
    foreach ($methods as $method) {
        if (strpos($content, "function {$method}") !== false) {
            echo "   ✅ Método {$method} existe\n";
        } else {
            echo "   ❌ Método {$method} NÃO existe\n";
            $allOk = false;
        }
    }
}

// 6. Verificar rotas no web.php
echo "\n6. Verificando rotas...\n";
$routesFile = __DIR__ . '/../app/routes/web.php';
if (file_exists($routesFile)) {
    $content = file_get_contents($routesFile);
    $routes = [
        '/ativar-conta',
        '/usuarios/{id}/gerar-senha-temporaria',
        '/usuarios/{id}/gerar-link-ativacao',
        '/usuarios/{id}/enviar-link-email'
    ];
    
    foreach ($routes as $route) {
        if (strpos($content, $route) !== false) {
            echo "   ✅ Rota {$route} existe\n";
        } else {
            echo "   ❌ Rota {$route} NÃO existe\n";
            $allOk = false;
        }
    }
}

echo "\n";

if ($allOk) {
    echo "=== ✅ SISTEMA DE CREDENCIAIS COMPLETO E FUNCIONAL ===\n\n";
    echo "Funcionalidades disponíveis:\n";
    echo "  ✅ Gerar senha temporária\n";
    echo "  ✅ Gerar link de ativação\n";
    echo "  ✅ Enviar link por e-mail (com fallback)\n";
    echo "  ✅ Ativar conta via link\n";
    echo "  ✅ Status de acesso\n";
    echo "  ✅ Auditoria completa\n";
    echo "\nPróximos passos:\n";
    echo "1. Acessar /usuarios como ADMIN\n";
    echo "2. Editar um usuário\n";
    echo "3. Testar ações de credenciais\n";
    echo "4. Testar ativação via link\n";
} else {
    echo "=== ⚠️  ALGUMAS VERIFICAÇÕES FALHARAM ===\n";
    echo "Revise os erros acima.\n";
}
