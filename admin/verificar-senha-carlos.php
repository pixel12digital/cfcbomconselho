<?php
/**
 * Script temporário para verificar a senha do usuário Carlos da Silva
 * 
 * Executar via navegador: http://localhost/cfc-bom-conselho/admin/verificar-senha-carlos.php
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || !canManageUsers()) {
    die('Acesso negado. Apenas administradores e secretárias podem executar este script.');
}

header('Content-Type: text/html; charset=utf-8');

$db = db();
$email = 'carlosteste@teste.com.br';

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>Verificar Senha - Carlos da Silva</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Verificação de Senha - Carlos da Silva</h1>";

try {
    // 1. Buscar usuário
    echo "<div class='info'>1. Buscando usuário com email: {$email}...</div>";
    $usuario = $db->fetch("SELECT id, nome, email, tipo, ativo, senha, LENGTH(senha) as senha_length FROM usuarios WHERE email = ?", [$email]);
    
    if (!$usuario) {
        echo "<div class='error'>❌ Usuário não encontrado com email: {$email}</div>";
        echo "</div></body></html>";
        exit;
    }
    
    echo "<div class='success'>✅ Usuário encontrado:</div>";
    echo "<table>";
    echo "<tr><th>Campo</th><th>Valor</th></tr>";
    echo "<tr><td>ID</td><td>{$usuario['id']}</td></tr>";
    echo "<tr><td>Nome</td><td>" . htmlspecialchars($usuario['nome']) . "</td></tr>";
    echo "<tr><td>Email</td><td>" . htmlspecialchars($usuario['email']) . "</td></tr>";
    echo "<tr><td>Tipo</td><td>{$usuario['tipo']}</td></tr>";
    echo "<tr><td>Ativo</td><td>" . ($usuario['ativo'] ? 'Sim' : 'Não') . "</td></tr>";
    echo "<tr><td>Comprimento da senha (hash)</td><td>{$usuario['senha_length']} caracteres</td></tr>";
    echo "</table>";
    
    // 2. Verificar formato do hash
    echo "<div class='info'>2. Verificando formato do hash da senha...</div>";
    $senhaHash = $usuario['senha'];
    
    if (empty($senhaHash)) {
        echo "<div class='error'>❌ Senha está vazia no banco de dados!</div>";
    } else {
        // Verificar se é um hash bcrypt válido
        $isBcrypt = preg_match('/^\$2[ayb]\$.{56}$/', $senhaHash);
        if ($isBcrypt) {
            echo "<div class='success'>✅ Hash parece ser um bcrypt válido (formato correto)</div>";
            echo "<div class='info'>Primeiros 20 caracteres do hash: " . htmlspecialchars(substr($senhaHash, 0, 20)) . "...</div>";
        } else {
            echo "<div class='warning'>⚠️ Hash não parece ser bcrypt. Formato: " . htmlspecialchars(substr($senhaHash, 0, 50)) . "...</div>";
        }
    }
    
    // 3. Testar senhas comuns
    echo "<div class='test-section'>";
    echo "<h3>3. Testando senhas comuns:</h3>";
    
    $senhasParaTestar = [
        '123456',
        'admin123',
        'carlos123',
        'teste123',
        'senha123',
        'password',
        '12345678',
        'admin',
        'carlos',
        'teste'
    ];
    
    echo "<table>";
    echo "<tr><th>Senha Testada</th><th>Resultado</th></tr>";
    
    $senhaEncontrada = false;
    foreach ($senhasParaTestar as $senhaTeste) {
        $valida = password_verify($senhaTeste, $senhaHash);
        $status = $valida ? '✅ VÁLIDA' : '❌ Inválida';
        $class = $valida ? 'success' : '';
        
        if ($valida) {
            $senhaEncontrada = true;
            echo "<tr class='{$class}'><td><strong>" . htmlspecialchars($senhaTeste) . "</strong></td><td><strong>{$status}</strong></td></tr>";
        } else {
            echo "<tr><td>" . htmlspecialchars($senhaTeste) . "</td><td>{$status}</td></tr>";
        }
    }
    echo "</table>";
    
    if (!$senhaEncontrada) {
        echo "<div class='warning'>⚠️ Nenhuma das senhas comuns funcionou. A senha pode ter sido definida manualmente.</div>";
    }
    echo "</div>";
    
    // 4. Verificar se precisa trocar senha
    echo "<div class='info'>4. Verificando flag precisa_trocar_senha...</div>";
    $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
    if ($checkColumn) {
        $usuarioCompleto = $db->fetch("SELECT precisa_trocar_senha FROM usuarios WHERE id = ?", [$usuario['id']]);
        if ($usuarioCompleto && isset($usuarioCompleto['precisa_trocar_senha'])) {
            $precisaTrocar = $usuarioCompleto['precisa_trocar_senha'];
            if ($precisaTrocar == 1) {
                echo "<div class='warning'>⚠️ Flag precisa_trocar_senha está ATIVADA (1). O usuário será forçado a trocar a senha no primeiro login.</div>";
            } else {
                echo "<div class='success'>✅ Flag precisa_trocar_senha está DESATIVADA (0). Login normal permitido.</div>";
            }
        }
    } else {
        echo "<div class='info'>ℹ️ Coluna precisa_trocar_senha não existe na tabela.</div>";
    }
    
    // 5. Instruções
    echo "<div class='test-section'>";
    echo "<h3>📋 Próximos Passos:</h3>";
    echo "<ol>";
    echo "<li>Se nenhuma senha comum funcionou, use o botão 'Senha' no painel admin para redefinir a senha do usuário.</li>";
    echo "<li>Escolha o modo 'Gerar senha temporária automática' para receber uma senha nova.</li>";
    echo "<li>Ou use o modo 'Definir nova senha manualmente' para definir uma senha específica.</li>";
    echo "<li>Após redefinir, tente fazer login novamente.</li>";
    echo "</ol>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
    error_log("Erro em admin/verificar-senha-carlos.php: " . $e->getMessage());
}

echo "</div></body></html>";
?>

