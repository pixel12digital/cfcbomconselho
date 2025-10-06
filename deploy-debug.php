<?php
/**
 * Debug público do deploy
 */

$logFile = 'logs/deploy.log';
$timestamp = date('Y-m-d H:i:s');

if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

function logMessage($message) {
    global $logFile, $timestamp;
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    file_put_contents('deploy-debug.txt', $logEntry, FILE_APPEND);
}

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
        
        $result = shell_exec('git pull origin master 2>&1');
        logMessage("Git pull: " . trim($result ?: 'sem saída'));
        
        file_put_contents('deploy-flag.txt', $timestamp . ' - Deploy OK');
        logMessage("🏁 Deploy concluído");
        
        // Resposta mais simples para evitar problemas
        http_response_code(200);
        header('Content-Type: application/json');
        echo '{"status":"success"}';
        
    } else {
        logMessage("⚠️ Branch diferente");
        http_response_code(200);
        header('Content-Type: application/json');
        echo '{"status":"ignored"}';
    }
    
} catch (Exception $e) {
    logMessage("❌ Erro: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo '{"status":"error"}';
}

logMessage("🏁 Finalizado");
?>
