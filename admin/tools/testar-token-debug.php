<?php
/**
 * Script de Debug - Testar Token de Reset
 * Versão com captura de erros detalhada
 */

// Ativar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>Debug - Testar Token</h1>";

// Testar includes um por um
echo "<h2>1. Testando Includes</h2>";

try {
    $configPath = __DIR__ . '/../includes/config.php';
    if (!file_exists($configPath)) {
        die("ERRO: config.php não encontrado em: $configPath");
    }
    require_once $configPath;
    echo "<p>✅ config.php carregado</p>";
} catch (Throwable $e) {
    die("ERRO ao carregar config.php: " . $e->getMessage() . " em " . $e->getFile() . ":" . $e->getLine());
}

try {
    require_once __DIR__ . '/../includes/database.php';
    echo "<p>✅ database.php carregado</p>";
} catch (Throwable $e) {
    die("ERRO ao carregar database.php: " . $e->getMessage());
}

try {
    require_once __DIR__ . '/../includes/auth.php';
    echo "<p>✅ auth.php carregado</p>";
} catch (Throwable $e) {
    die("ERRO ao carregar auth.php: " . $e->getMessage());
}

try {
    require_once __DIR__ . '/../includes/PasswordReset.php';
    echo "<p>✅ PasswordReset.php carregado</p>";
} catch (Throwable $e) {
    die("ERRO ao carregar PasswordReset.php: " . $e->getMessage());
}

// Testar autenticação
echo "<h2>2. Testando Autenticação</h2>";
try {
    if (!function_exists('getCurrentUser')) {
        die("ERRO: Função getCurrentUser() não existe");
    }
    $user = getCurrentUser();
    if (!$user) {
        die("ERRO: Usuário não autenticado. Faça login primeiro.");
    }
    if ($user['tipo'] !== 'admin') {
        die("ERRO: Acesso negado. Apenas administradores podem executar este script.");
    }
    echo "<p>✅ Usuário autenticado: " . htmlspecialchars($user['email'] ?? 'N/A') . " (tipo: " . htmlspecialchars($user['tipo'] ?? 'N/A') . ")</p>";
} catch (Throwable $e) {
    die("ERRO ao verificar usuário: " . $e->getMessage());
}

// Testar banco de dados
echo "<h2>3. Testando Banco de Dados</h2>";
try {
    if (!function_exists('db')) {
        die("ERRO: Função db() não existe");
    }
    $db = db();
    if (!$db) {
        die("ERRO: Não foi possível conectar ao banco de dados");
    }
    echo "<p>✅ Conexão com banco estabelecida</p>";
} catch (Throwable $e) {
    die("ERRO ao conectar ao banco: " . $e->getMessage());
}

// Agora processar o token
$token = $_GET['token'] ?? '';

if (empty($token)) {
    echo "<h2>4. Formulário</h2>";
    echo '<form method="GET">';
    echo '<label>Token: <input type="text" name="token" style="width: 600px;" placeholder="Cole o token aqui..."></label>';
    echo '<button type="submit">Testar</button>';
    echo '</form>';
    exit;
}

echo "<h2>4. Processando Token</h2>";
echo "<p>Token recebido: " . htmlspecialchars(substr($token, 0, 20)) . "... (tamanho: " . strlen($token) . " caracteres)</p>";

try {
    // Informações básicas do token
    $tokenLength = strlen($token);
    $tokenHash = hash('sha256', $token);
    
    echo "<h3>Informações do Token</h3>";
    echo "<p>Comprimento: <strong>$tokenLength</strong> caracteres</p>";
    echo "<p>Hash SHA256: " . substr($tokenHash, 0, 32) . "...</p>";
    
    // Buscar no banco
    $reset = $db->fetch(
        "SELECT * FROM password_resets WHERE token_hash = :hash LIMIT 1",
        ['hash' => $tokenHash]
    );
    
    if ($reset) {
        echo "<h3>✅ Token encontrado no banco</h3>";
        echo "<pre>";
        print_r($reset);
        echo "</pre>";
        
        // Validar token
        $validation = PasswordReset::validateToken($token);
        echo "<h3>Validação</h3>";
        echo "<pre>";
        print_r($validation);
        echo "</pre>";
        
    } else {
        echo "<h3>❌ Token NÃO encontrado no banco</h3>";
    }
    
} catch (Throwable $e) {
    echo "<h3>ERRO ao processar token:</h3>";
    echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
    echo "<p>Arquivo: " . htmlspecialchars($e->getFile()) . ":" . $e->getLine() . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}
