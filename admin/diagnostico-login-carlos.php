<?php
/**
 * Script de diagnóstico completo para login do Carlos da Silva
 * 
 * Executar via navegador: http://localhost/cfc-bom-conselho/admin/diagnostico-login-carlos.php
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
    <title>Diagnóstico de Login - Carlos da Silva</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #007bff; padding-bottom: 5px; }
        .success { color: #28a745; background: #d4edda; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #28a745; }
        .error { color: #dc3545; background: #f8d7da; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #dc3545; }
        .info { color: #0c5460; background: #d1ecf1; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #0c5460; }
        .warning { color: #856404; background: #fff3cd; padding: 10px; border-radius: 4px; margin: 10px 0; border-left: 4px solid #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; border: 1px solid #dee2e6; }
        table { width: 100%; border-collapse: collapse; margin: 10px 0; }
        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #f8f9fa; font-weight: bold; }
        .test-section { margin: 20px 0; padding: 15px; background: #f8f9fa; border-radius: 4px; }
        .code { background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔍 Diagnóstico Completo de Login - Carlos da Silva</h1>";

try {
    // 1. Buscar usuário completo
    echo "<h2>1. Informações do Usuário</h2>";
    $usuario = $db->fetch("
        SELECT 
            id, nome, email, tipo, ativo, senha, 
            LENGTH(senha) as senha_length,
            criado_em, atualizado_em,
            precisa_trocar_senha
        FROM usuarios 
        WHERE email = ?
    ", [$email]);
    
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
    echo "<tr><td>Tipo</td><td><strong>{$usuario['tipo']}</strong></td></tr>";
    echo "<tr><td>Ativo</td><td>" . ($usuario['ativo'] ? '<span style="color: green;">✅ Sim</span>' : '<span style="color: red;">❌ Não</span>') . "</td></tr>";
    echo "<tr><td>Comprimento do hash</td><td>{$usuario['senha_length']} caracteres</td></tr>";
    echo "<tr><td>Criado em</td><td>{$usuario['criado_em']}</td></tr>";
    echo "<tr><td>Atualizado em</td><td>{$usuario['atualizado_em']}</td></tr>";
    if (isset($usuario['precisa_trocar_senha'])) {
        echo "<tr><td>precisa_trocar_senha</td><td>" . ($usuario['precisa_trocar_senha'] == 1 ? '<span style="color: orange;">⚠️ Sim (1)</span>' : '<span style="color: green;">✅ Não (0)</span>') . "</td></tr>";
    } else {
        echo "<tr><td>precisa_trocar_senha</td><td><span style='color: red;'>❌ Coluna não existe</span></td></tr>";
    }
    echo "</table>";
    
    // 2. Verificar formato do hash
    echo "<h2>2. Análise do Hash da Senha</h2>";
    $senhaHash = $usuario['senha'];
    
    if (empty($senhaHash)) {
        echo "<div class='error'>❌ Senha está vazia no banco de dados!</div>";
    } else {
        // Verificar se é um hash bcrypt válido
        $isBcrypt = preg_match('/^\$2[ayb]\$.{56}$/', $senhaHash);
        if ($isBcrypt) {
            echo "<div class='success'>✅ Hash é um bcrypt válido (formato correto)</div>";
            echo "<div class='info'>Primeiros 30 caracteres: <code>" . htmlspecialchars(substr($senhaHash, 0, 30)) . "...</code></div>";
            echo "<div class='info'>Últimos 10 caracteres: <code>..." . htmlspecialchars(substr($senhaHash, -10)) . "</code></div>";
        } else {
            echo "<div class='error'>❌ Hash não parece ser bcrypt. Formato inválido!</div>";
            echo "<div class='info'>Primeiros 50 caracteres: <code>" . htmlspecialchars(substr($senhaHash, 0, 50)) . "...</code></div>";
        }
    }
    
    // 3. Testar autenticação usando a classe Auth
    echo "<h2>3. Teste de Autenticação (usando Auth::login)</h2>";
    echo "<div class='test-section'>";
    
    // Simular tentativas de login com senhas comuns
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
        'teste',
        'TempPass123',
        'Temp1234',
        'temp123',
        'senha2024',
        'cfc2024'
    ];
    
    echo "<table>";
    echo "<tr><th>Senha Testada</th><th>password_verify()</th><th>Auth::login()</th></tr>";
    
    $senhaEncontrada = false;
    foreach ($senhasParaTestar as $senhaTeste) {
        $valida = password_verify($senhaTeste, $senhaHash);
        
        // Testar também com Auth::login
        $auth = new Auth();
        $resultAuth = $auth->login($email, $senhaTeste, false);
        $authValida = $resultAuth['success'] ?? false;
        
        $statusVerify = $valida ? '✅ VÁLIDA' : '❌ Inválida';
        $statusAuth = $authValida ? '✅ VÁLIDA' : '❌ Inválida';
        
        if ($valida || $authValida) {
            $senhaEncontrada = true;
            echo "<tr style='background: #d4edda;'>";
            echo "<td><strong>" . htmlspecialchars($senhaTeste) . "</strong></td>";
            echo "<td><strong>{$statusVerify}</strong></td>";
            echo "<td><strong>{$statusAuth}</strong></td>";
            echo "</tr>";
        } else {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($senhaTeste) . "</td>";
            echo "<td>{$statusVerify}</td>";
            echo "<td>{$statusAuth}</td>";
            echo "</tr>";
        }
    }
    echo "</table>";
    
    if (!$senhaEncontrada) {
        echo "<div class='warning'>⚠️ Nenhuma das senhas comuns funcionou.</div>";
    }
    echo "</div>";
    
    // 4. Verificar getUserByLogin
    echo "<h2>4. Teste de getUserByLogin()</h2>";
    $auth = new Auth();
    $reflection = new ReflectionClass($auth);
    $method = $reflection->getMethod('getUserByLogin');
    $method->setAccessible(true);
    
    try {
        $usuarioLogin = $method->invoke($auth, $email);
        if ($usuarioLogin) {
            echo "<div class='success'>✅ getUserByLogin() encontrou o usuário:</div>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td>ID</td><td>{$usuarioLogin['id']}</td></tr>";
            echo "<tr><td>Email</td><td>" . htmlspecialchars($usuarioLogin['email'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Tipo</td><td>{$usuarioLogin['tipo']}</td></tr>";
            echo "<tr><td>Ativo</td><td>" . ($usuarioLogin['ativo'] ? 'Sim' : 'Não') . "</td></tr>";
            echo "<tr><td>Hash da senha (primeiros 20)</td><td><code>" . htmlspecialchars(substr($usuarioLogin['senha'] ?? '', 0, 20)) . "...</code></td></tr>";
            echo "</table>";
        } else {
            echo "<div class='error'>❌ getUserByLogin() não encontrou o usuário!</div>";
        }
    } catch (Exception $e) {
        echo "<div class='error'>❌ Erro ao chamar getUserByLogin(): " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 5. Verificar se usuário está bloqueado
    echo "<h2>5. Verificação de Bloqueio</h2>";
    try {
        $reflection = new ReflectionClass($auth);
        $method = $reflection->getMethod('isLocked');
        $method->setAccessible(true);
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $isLocked = $method->invoke($auth, $ip);
        
        if ($isLocked) {
            echo "<div class='error'>❌ IP está bloqueado por muitas tentativas de login!</div>";
        } else {
            echo "<div class='success'>✅ IP não está bloqueado.</div>";
        }
    } catch (Exception $e) {
        echo "<div class='warning'>⚠️ Não foi possível verificar bloqueio: " . htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    // 6. Verificar logs recentes
    echo "<h2>6. Logs Recentes (últimas 20 linhas)</h2>";
    $logFile = __DIR__ . '/../logs/error.log';
    if (file_exists($logFile)) {
        $lines = file($logFile);
        $recentLines = array_slice($lines, -20);
        echo "<div class='info'>Últimas 20 linhas do log:</div>";
        echo "<pre style='max-height: 300px; overflow-y: auto;'>";
        foreach ($recentLines as $line) {
            if (stripos($line, 'carlos') !== false || stripos($line, 'login') !== false || stripos($line, 'senha') !== false) {
                echo htmlspecialchars($line);
            }
        }
        echo "</pre>";
    } else {
        echo "<div class='warning'>⚠️ Arquivo de log não encontrado: {$logFile}</div>";
    }
    
    // 7. Solução recomendada
    echo "<h2>7. Solução Recomendada</h2>";
    echo "<div class='test-section'>";
    echo "<ol>";
    echo "<li><strong>Se nenhuma senha funcionou:</strong> Use o botão 'Senha' no painel admin para gerar uma nova senha temporária.</li>";
    echo "<li><strong>Verifique o tipo de usuário:</strong> Certifique-se de selecionar 'Instrutor' no login (não 'Administrador').</li>";
    echo "<li><strong>Verifique se o usuário está ativo:</strong> O campo 'Ativo' deve ser 'Sim' (✅).</li>";
    echo "<li><strong>Limpe o cache do navegador:</strong> Pressione Ctrl+Shift+Delete e limpe cookies e cache.</li>";
    echo "<li><strong>Teste em modo anônimo:</strong> Abra uma janela anônima (Ctrl+Shift+N) e tente fazer login.</li>";
    echo "</ol>";
    echo "</div>";
    
    // 8. Gerar nova senha temporária (opcional)
    echo "<h2>8. Gerar Nova Senha Temporária (Teste)</h2>";
    echo "<div class='test-section'>";
    echo "<p>Você pode usar este script para gerar uma nova senha temporária diretamente:</p>";
    echo "<form method='POST' style='margin-top: 10px;'>";
    echo "<input type='hidden' name='action' value='generate_password'>";
    echo "<button type='submit' style='padding: 10px 20px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer;'>Gerar Nova Senha Temporária</button>";
    echo "</form>";
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_password') {
        // Gerar senha temporária
        $novaSenha = 'Temp' . rand(1000, 9999);
        $senhaHash = password_hash($novaSenha, PASSWORD_DEFAULT);
        
        // Atualizar no banco
        $updateFields = ['senha = ?'];
        $updateValues = [$senhaHash];
        
        // Adicionar flag precisa_trocar_senha se existir
        $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
        if ($checkColumn) {
            $updateFields[] = 'precisa_trocar_senha = 1';
        }
        
        $updateQuery = "UPDATE usuarios SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $updateValues[] = $usuario['id'];
        
        try {
            $result = $db->query($updateQuery, $updateValues);
            if ($result) {
                echo "<div class='success' style='margin-top: 15px;'>";
                echo "<strong>✅ Nova senha gerada com sucesso!</strong><br>";
                echo "<strong>Email:</strong> {$email}<br>";
                echo "<strong>Senha temporária:</strong> <code style='font-size: 18px; padding: 5px 10px; background: #fff; border: 2px solid #28a745; border-radius: 4px;'>{$novaSenha}</code><br>";
                echo "<strong>⚠️ COPIE ESTA SENHA AGORA! Ela não será exibida novamente.</strong>";
                echo "</div>";
            } else {
                echo "<div class='error' style='margin-top: 15px;'>❌ Erro ao atualizar senha no banco de dados.</div>";
            }
        } catch (Exception $e) {
            echo "<div class='error' style='margin-top: 15px;'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
        }
    }
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
    error_log("Erro em admin/diagnostico-login-carlos.php: " . $e->getMessage());
}

echo "</div></body></html>";
?>

