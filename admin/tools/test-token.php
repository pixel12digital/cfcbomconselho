<?php
chdir('C:\xampp\htdocs\cfc-bom-conselho');

require_once 'includes/config.php';
require_once 'includes/database.php';

$token = '676f711eef98d00d96218e326f147ac79a02a2c6dc4e2775538e7e5548bc9fdd';

echo "=== INVESTIGAÇÃO TOKEN ===\n\n";

$db = db();

// 1. Informações do token
echo "1. TOKEN\n";
echo "   Tamanho: " . strlen($token) . " caracteres\n";
$tokenHash = hash('sha256', $token);
echo "   Hash SHA256: " . substr($tokenHash, 0, 32) . "...\n\n";

// 2. Timezone
echo "2. TIMEZONE\n";
$phpTime = date('Y-m-d H:i:s');
$phpTimeUtc = gmdate('Y-m-d H:i:s');
$mysqlNow = $db->fetch("SELECT NOW() as now, UTC_TIMESTAMP() as utc_now");
echo "   PHP local: $phpTime\n";
echo "   PHP UTC: $phpTimeUtc\n";
echo "   MySQL NOW(): " . ($mysqlNow['now'] ?? 'N/A') . "\n";
echo "   MySQL UTC_TIMESTAMP(): " . ($mysqlNow['utc_now'] ?? 'N/A') . "\n\n";

// 3. Buscar token no banco
echo "3. TOKEN NO BANCO\n";
$reset = $db->fetch(
    "SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1",
    ['hash' => $tokenHash]
);

if ($reset) {
    echo "   ✅ Token encontrado!\n";
    echo "   ID: " . $reset['id'] . "\n";
    echo "   Login: " . $reset['login'] . "\n";
    echo "   Tipo: " . $reset['type'] . "\n";
    echo "   Criado em: " . $reset['created_at'] . "\n";
    echo "   Expira em: " . $reset['expires_at'] . "\n";
    echo "   Usado em: " . ($reset['used_at'] ?: 'NULL') . "\n";
    
    // Verificar se está expirado
    $expiresTimestamp = strtotime($reset['expires_at']);
    $nowTimestamp = time();
    $isExpired = $expiresTimestamp < $nowTimestamp;
    $isUsed = !empty($reset['used_at']);
    
    if ($isUsed) {
        echo "   Status: ❌ JÁ USADO\n";
    } elseif ($isExpired) {
        $diffMinutes = floor(($nowTimestamp - $expiresTimestamp) / 60);
        echo "   Status: ❌ EXPIRADO há $diffMinutes minutos\n";
    } else {
        $diffMinutes = floor(($expiresTimestamp - $nowTimestamp) / 60);
        echo "   Status: ✅ VÁLIDO (expira em $diffMinutes minutos)\n";
    }
    echo "\n";
    
    // 4. Validar token
    echo "4. VALIDAÇÃO\n";
    require_once 'includes/PasswordReset.php';
    $validation = PasswordReset::validateToken($token);
    echo "   Válido: " . ($validation['valid'] ? 'SIM' : 'NÃO') . "\n";
    echo "   Reset ID: " . ($validation['reset_id'] ?? 'N/A') . "\n";
    echo "   Login: " . ($validation['login'] ?? 'N/A') . "\n";
    echo "   Tipo: " . ($validation['type'] ?? 'N/A') . "\n";
    echo "   Motivo: " . ($validation['reason'] ?? 'N/A') . "\n\n";
    
    // 5. Buscar usuário
    if ($validation['valid']) {
        echo "5. USUÁRIO\n";
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
        
        if ($usuario) {
            echo "   ✅ Usuário encontrado!\n";
            echo "   ID: " . $usuario['id'] . "\n";
            echo "   Tipo: " . $usuario['tipo'] . "\n";
            echo "   Email: " . ($usuario['email'] ?? 'N/A') . "\n";
            echo "   CPF: " . ($usuario['cpf'] ?? 'N/A') . "\n";
            echo "   Ativo: " . ($usuario['ativo'] ? 'SIM' : 'NÃO') . "\n";
            
            // Schema da coluna senha
            $senhaColumn = $db->fetch("SHOW COLUMNS FROM usuarios WHERE Field = 'senha'");
            if ($senhaColumn) {
                echo "\n   Schema da coluna senha:\n";
                echo "   - Field: " . $senhaColumn['Field'] . "\n";
                echo "   - Type: " . $senhaColumn['Type'] . "\n";
                echo "   - Null: " . $senhaColumn['Null'] . "\n";
            }
            
            // Senha atual
            $senhaAtual = $db->fetch("SELECT senha FROM usuarios WHERE id = :id", ['id' => $usuario['id']]);
            $senhaHashAtual = $senhaAtual['senha'] ?? null;
            if ($senhaHashAtual) {
                echo "   - Senha hash atual (primeiros 20): " . substr($senhaHashAtual, 0, 20) . "...\n";
                echo "   - Tamanho do hash: " . strlen($senhaHashAtual) . " caracteres\n";
            }
        } else {
            echo "   ❌ Usuário NÃO encontrado\n";
            echo "   Login buscado: $loginBusca\n";
            echo "   Tipo buscado: $typeBusca\n";
        }
    }
    
} else {
    echo "   ❌ Token NÃO encontrado no banco\n\n";
    
    // Verificar últimos tokens
    echo "   Últimos tokens gerados:\n";
    $ultimosTokens = $db->fetchAll(
        "SELECT id, login, type, created_at, expires_at, used_at 
         FROM password_resets 
         ORDER BY created_at DESC 
         LIMIT 5"
    );
    foreach ($ultimosTokens as $t) {
        echo "   - ID: {$t['id']}, Login: {$t['login']}, Tipo: {$t['type']}, Criado: {$t['created_at']}\n";
    }
}

echo "\n=== FIM ===\n";
