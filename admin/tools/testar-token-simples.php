<?php
/**
 * Script Simples para Testar Token de Reset
 * Versão simplificada sem DateTime para evitar erros
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/PasswordReset.php';

// Verificar se é admin
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

header('Content-Type: text/html; charset=UTF-8');

$token = $_GET['token'] ?? '';

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Teste Simples - Token Reset</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 1200px; margin: 20px auto; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin: 15px 0; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #f0f0f0; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Teste Simples - Token de Reset</h1>
    
    <form method="GET">
        <label>Token: <input type="text" name="token" value="<?php echo htmlspecialchars($token); ?>" style="width: 600px;" placeholder="Cole o token aqui..."></label>
        <button type="submit">Testar</button>
    </form>
    
    <?php if (!empty($token)): ?>
        <h2>Resultado</h2>
        
        <?php
        $db = db();
        
        // 1. Informações do token
        $tokenLength = strlen($token);
        $tokenPreview = substr($token, 0, 6) . '...';
        $tokenHash = hash('sha256', $token);
        
        echo "<h3>1. Informações do Token</h3>";
        echo "<table>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        echo "<tr><td><strong>token_length</strong></td><td><strong>{$tokenLength}</strong> caracteres</td></tr>";
        echo "<tr><td>token_preview</td><td><code>{$tokenPreview}</code></td></tr>";
        echo "<tr><td>token_hash (SHA256)</td><td><code>" . substr($tokenHash, 0, 32) . "...</code></td></tr>";
        echo "</table>";
        
        // 2. Timezone
        $phpTime = date('Y-m-d H:i:s');
        $phpTimeUtc = gmdate('Y-m-d H:i:s');
        $mysqlNow = $db->fetch("SELECT NOW() as now, UTC_TIMESTAMP() as utc_now");
        $mysqlNowLocal = $mysqlNow['now'] ?? 'N/A';
        $mysqlNowUtc = $mysqlNow['utc_now'] ?? 'N/A';
        
        echo "<h3>2. Comparação de Timezone</h3>";
        echo "<table>";
        echo "<tr><th>Origem</th><th>Data/Hora</th></tr>";
        echo "<tr><td>PHP (local)</td><td>{$phpTime}</td></tr>";
        echo "<tr><td>PHP (UTC)</td><td>{$phpTimeUtc}</td></tr>";
        echo "<tr><td>MySQL NOW()</td><td>{$mysqlNowLocal}</td></tr>";
        echo "<tr><td>MySQL UTC_TIMESTAMP()</td><td>{$mysqlNowUtc}</td></tr>";
        echo "</table>";
        
        // 3. Buscar token no banco
        $reset = $db->fetch(
            "SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1",
            ['hash' => $tokenHash]
        );
        
        echo "<h3>3. Token no Banco</h3>";
        if ($reset) {
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td><strong>Token encontrado?</strong></td><td><span class='success'>✅ SIM</span></td></tr>";
            echo "<tr><td>ID</td><td>{$reset['id']}</td></tr>";
            echo "<tr><td>Login</td><td>" . htmlspecialchars($reset['login']) . "</td></tr>";
            echo "<tr><td>Tipo</td><td>{$reset['type']}</td></tr>";
            echo "<tr><td>Criado em</td><td>{$reset['created_at']}</td></tr>";
            echo "<tr><td><strong>Expira em</strong></td><td><strong>{$reset['expires_at']}</strong></td></tr>";
            echo "<tr><td><strong>Usado em</strong></td><td><strong>" . ($reset['used_at'] ?: 'NULL') . "</strong></td></tr>";
            
            // Verificar se está expirado (sem DateTime)
            $expiresTimestamp = strtotime($reset['expires_at']);
            $nowTimestamp = time();
            $isExpired = $expiresTimestamp < $nowTimestamp;
            $isUsed = !empty($reset['used_at']);
            
            echo "<tr><td><strong>Status</strong></td><td>";
            if ($isUsed) {
                echo "<span class='error'>❌ JÁ USADO</span>";
            } elseif ($isExpired) {
                $diffSeconds = $nowTimestamp - $expiresTimestamp;
                $diffMinutes = floor($diffSeconds / 60);
                echo "<span class='error'>❌ EXPIRADO há {$diffMinutes} minutos</span>";
            } else {
                echo "<span class='success'>✅ VÁLIDO</span>";
            }
            echo "</td></tr>";
            echo "</table>";
            
            // 4. Validar token
            $validation = PasswordReset::validateToken($token);
            
            echo "<h3>4. Validação do Token</h3>";
            echo "<table>";
            echo "<tr><th>Campo</th><th>Valor</th></tr>";
            echo "<tr><td><strong>Token válido</strong></td><td>" . ($validation['valid'] ? "<span class='success'>✅ SIM</span>" : "<span class='error'>❌ NÃO</span>") . "</td></tr>";
            echo "<tr><td>Reset ID</td><td>" . ($validation['reset_id'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Login</td><td>" . htmlspecialchars($validation['login'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Tipo</td><td>" . htmlspecialchars($validation['type'] ?? 'N/A') . "</td></tr>";
            echo "<tr><td>Motivo (se inválido)</td><td>" . htmlspecialchars($validation['reason'] ?? 'N/A') . "</td></tr>";
            echo "</table>";
            
            // 5. Buscar usuário
            if ($validation['valid']) {
                $loginBusca = $validation['login'];
                $typeBusca = $validation['type'];
                $usuario = null;
                
                if ($typeBusca === 'aluno') {
                    $cpfLimpo = preg_replace('/[^0-9]/', '', trim($loginBusca));
                    $isEmail = filter_var($loginBusca, FILTER_VALIDATE_EMAIL);
                    
                    if ($isEmail) {
                        $usuario = $db->fetch(
                            "SELECT id, email, cpf, tipo, ativo FROM usuarios WHERE email = :email AND tipo = 'aluno' AND ativo = 1 LIMIT 1",
                            ['email' => $loginBusca]
                        );
                    } elseif (!empty($cpfLimpo) && strlen($cpfLimpo) === 11) {
                        $usuario = $db->fetch(
                            "SELECT id, email, cpf, tipo, ativo FROM usuarios 
                             WHERE REPLACE(REPLACE(cpf, '.', ''), '-', '') = :cpf 
                             AND tipo = 'aluno' 
                             AND ativo = 1 
                             LIMIT 1",
                            ['cpf' => $cpfLimpo]
                        );
                    }
                } else {
                    $usuario = $db->fetch(
                        "SELECT id, email, tipo, ativo FROM usuarios WHERE email = :email AND tipo = :type AND ativo = 1 LIMIT 1",
                        ['email' => $loginBusca, 'type' => $typeBusca]
                    );
                }
                
                echo "<h3>5. Usuário</h3>";
                if ($usuario) {
                    echo "<table>";
                    echo "<tr><th>Campo</th><th>Valor</th></tr>";
                    echo "<tr><td><strong>user_found</strong></td><td><span class='success'>✅ SIM</span></td></tr>";
                    echo "<tr><td><strong>user_id</strong></td><td><strong>{$usuario['id']}</strong></td></tr>";
                    echo "<tr><td>user_tipo</td><td>{$usuario['tipo']}</td></tr>";
                    echo "<tr><td>user_email</td><td>" . htmlspecialchars($usuario['email'] ?? 'N/A') . "</td></tr>";
                    echo "<tr><td>user_cpf</td><td>" . htmlspecialchars($usuario['cpf'] ?? 'N/A') . "</td></tr>";
                    echo "<tr><td><strong>user_ativo</strong></td><td><strong>" . ($usuario['ativo'] ? 'SIM (1)' : 'NÃO (0)') . "</strong></td></tr>";
                    
                    // Schema da coluna senha
                    $senhaColumn = $db->fetch("SHOW COLUMNS FROM usuarios WHERE Field = 'senha'");
                    if ($senhaColumn) {
                        echo "<tr><td colspan='2'><strong>Schema da coluna senha:</strong></td></tr>";
                        echo "<tr><td>Field</td><td>{$senhaColumn['Field']}</td></tr>";
                        echo "<tr><td>Type</td><td>{$senhaColumn['Type']}</td></tr>";
                        echo "<tr><td>Null</td><td>{$senhaColumn['Null']}</td></tr>";
                    }
                    
                    // Senha atual (apenas preview)
                    $senhaAtual = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $usuario['id']]);
                    $senhaHashAtual = $senhaAtual['senha'] ?? null;
                    if ($senhaHashAtual) {
                        echo "<tr><td>senha_hash_atual (primeiros 20)</td><td><code>" . substr($senhaHashAtual, 0, 20) . "...</code> (len=" . strlen($senhaHashAtual) . ")</td></tr>";
                    }
                    
                    echo "</table>";
                } else {
                    echo "<p class='error'>❌ Usuário NÃO encontrado com login: " . htmlspecialchars($loginBusca) . ", tipo: " . htmlspecialchars($typeBusca) . "</p>";
                }
            }
        } else {
            echo "<p class='error'>❌ Token não encontrado no banco de dados.</p>";
        }
        ?>
        
        <h3>6. Logs Recentes</h3>
        <?php
        $logPath = __DIR__ . '/../logs/php_errors.log';
        if (file_exists($logPath)) {
            $lines = file($logPath);
            if ($lines) {
                $filteredLines = [];
                foreach ($lines as $line) {
                    if (stripos($line, 'PASSWORD_RESET_AUDIT') !== false || 
                        stripos($line, 'PASSWORD_RESET') !== false ||
                        stripos($line, 'RESET_PASSWORD') !== false) {
                        $filteredLines[] = $line;
                    }
                }
                
                $filteredLines = array_slice($filteredLines, -30);
                
                if (empty($filteredLines)) {
                    echo "<p>Nenhuma entrada encontrada nos logs.</p>";
                } else {
                    echo "<pre>";
                    foreach ($filteredLines as $line) {
                        echo htmlspecialchars($line);
                    }
                    echo "</pre>";
                }
            }
        } else {
            echo "<p class='error'>Arquivo de log não encontrado: " . htmlspecialchars($logPath) . "</p>";
        }
        ?>
        
    <?php endif; ?>
</body>
</html>
