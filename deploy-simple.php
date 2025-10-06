<?php
/**
 * Deploy Simples - Versão que funciona em qualquer servidor
 */

$logFile = 'logs/deploy.log';
$timestamp = date('Y-m-d H:i:s');

// Criar diretório de logs
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

function logMessage($message) {
    global $logFile, $timestamp;
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

logMessage("🚀 Deploy iniciado");

try {
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);
    
    if (isset($payload['ref']) && $payload['ref'] === 'refs/heads/master') {
        logMessage("✅ Push no master detectado");
        
        // Tentar git pull de forma simples
        $result = shell_exec('git pull origin master 2>&1');
        logMessage("Git pull resultado: " . trim($result ?: 'sem saída'));
        
        // Criar flag de sucesso
        file_put_contents('deploy-flag.txt', $timestamp . ' - Deploy executado');
        logMessage("🏁 Deploy concluído");
        
        http_response_code(200);
        echo json_encode(['status' => 'success', 'message' => 'Deploy OK']);
        
    } else {
        logMessage("⚠️ Branch diferente");
        http_response_code(200);
        echo json_encode(['status' => 'ignored']);
    }
    
} catch (Exception $e) {
    logMessage("❌ Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

logMessage("🏁 Finalizado");
?>
