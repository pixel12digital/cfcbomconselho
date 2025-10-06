<?php
/**
 * Teste simples de webhook
 */

$logFile = 'logs/deploy.log';
$timestamp = date('Y-m-d H:i:s');

// Criar diretório de logs se não existir
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

function logMessage($message) {
    global $logFile, $timestamp;
    $logEntry = "[$timestamp] $message\n";
    file_put_contents($logFile, $logEntry, FILE_APPEND);
    echo $logEntry;
}

logMessage("🧪 TESTE: Deploy iniciado");

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

logMessage("✅ POST recebido");

try {
    // Obter payload
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);
    
    logMessage("📦 Payload recebido");
    
    // Verificar se é push no master
    if (isset($payload['ref']) && $payload['ref'] === 'refs/heads/master') {
        logMessage("✅ Push no master detectado");
        
        // Testar comandos básicos
        logMessage("🔄 Testando shell_exec...");
        $test1 = shell_exec('echo "teste"');
        logMessage("echo teste: " . trim($test1 ?: 'falhou'));
        
        logMessage("🔄 Testando git...");
        $gitVersion = shell_exec('git --version 2>&1');
        logMessage("git --version: " . trim($gitVersion ?: 'git não encontrado'));
        
        logMessage("🔄 Testando diretório...");
        $pwd = shell_exec('pwd 2>&1');
        logMessage("pwd: " . trim($pwd ?: 'erro'));
        
        // Criar flag
        $flagFile = 'deploy-flag.txt';
        file_put_contents($flagFile, $timestamp . ' - Teste webhook OK');
        logMessage("🏁 Flag criada: $flagFile");
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Teste concluído',
            'timestamp' => $timestamp
        ]);
        
    } else {
        logMessage("⚠️ Push em branch diferente");
        http_response_code(200);
        echo json_encode([
            'status' => 'ignored',
            'message' => 'Branch diferente',
            'timestamp' => $timestamp
        ]);
    }
    
} catch (Exception $e) {
    logMessage("❌ Erro: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => $timestamp
    ]);
}

logMessage("🏁 Teste finalizado");
?>
