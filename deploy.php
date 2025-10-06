<?php
/**
 * 🚀 Deploy Simples via Webhook - CFC Bom Conselho
 * Versão que funciona em qualquer hospedagem
 */

// Log do webhook
$logFile = 'logs/deploy.log';
$timestamp = date('Y-m-d H:i:s');

// Criar diretório de logs se não existir
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// Função de log
function logMessage($message) {
    global $logFile, $timestamp;
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

// Carregar token de teste (opcional) de config/deploy-token.php
$testToken = null;
if (file_exists(__DIR__ . '/config/deploy-token.php')) {
    $testToken = trim(@file_get_contents(__DIR__ . '/config/deploy-token.php')) ?: null;
}

// Verificar método: permitir GET somente em modo de teste com token
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $isTest = isset($_GET['test']) && isset($_GET['token']) && $testToken && hash_equals($testToken, (string)$_GET['token']);
    if (!$isTest) {
        http_response_code(405);
        die('Método não permitido. Este endpoint só aceita POST requests do GitHub webhook.');
    }
    logMessage('🧪 Modo teste via GET autorizado por token');
}

logMessage("🚀 Deploy iniciado via webhook");

try {
    // Obter payload do GitHub (ou simular em teste)
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $payload = ['ref' => 'refs/heads/master', 'test' => true];
    } else {
        logMessage("📦 Payload recebido do GitHub");
    }

    // Verificar se é um push no branch master
    if (isset($payload['ref']) && $payload['ref'] === 'refs/heads/master') {
        logMessage("✅ Push detectado no branch master");
        
        // Criar arquivo de flag para indicar que precisa de deploy
        $flagFile = 'deploy-flag.txt';
        file_put_contents($flagFile, $timestamp . ' - Deploy necessário');
        
        logMessage("🏁 Flag de deploy criada: $flagFile");
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Flag de deploy criada - Execute deploy manual',
            'timestamp' => $timestamp,
            'branch' => 'master',
            'action' => 'flag_created',
            'test_mode' => ($_SERVER['REQUEST_METHOD'] === 'GET')
        ]);
        
    } else {
        logMessage("⚠️ Push em branch diferente, ignorando");
        http_response_code(200);
        echo json_encode([
            'status' => 'ignored',
            'message' => 'Push em branch diferente de master',
            'timestamp' => $timestamp
        ]);
    }
    
} catch (Exception $e) {
    logMessage("❌ Erro no deploy: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno: ' . $e->getMessage(),
        'timestamp' => $timestamp
    ]);
}

logMessage("🏁 Deploy finalizado");
?>