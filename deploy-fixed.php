<?php
/**
 * 🚀 Deploy Automático via Webhook - CFC Bom Conselho
 * Versão corrigida e simplificada
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

// Verificar se é uma requisição POST (webhook do GitHub)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido. Este endpoint só aceita POST requests do GitHub webhook.');
}

logMessage("🚀 Deploy iniciado via webhook");

try {
    // Obter payload do GitHub
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);
    
    logMessage("📦 Payload recebido do GitHub");
    
    // Verificar se é um push no branch master
    if (isset($payload['ref']) && $payload['ref'] === 'refs/heads/master') {
        logMessage("✅ Push detectado no branch master");
        
        // Executar git pull
        $output = [];
        $exitCode = 0;
        
        // Comando para atualizar o repositório
        $command = 'cd ' . __DIR__ . ' && git pull origin master 2>&1';
        exec($command, $output, $exitCode);
        
        // Log do resultado
        $result = implode("\n", $output);
        logMessage("📥 Git pull resultado ($exitCode): $result");
        
        if ($exitCode === 0) {
            logMessage("✅ Deploy CONCLUÍDO com sucesso");
            
            // Limpar cache se necessário
            if (function_exists('opcache_reset')) {
                opcache_reset();
                logMessage("🧹 Cache limpo");
            }
            
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Deploy realizado com sucesso',
                'timestamp' => $timestamp,
                'branch' => 'master'
            ]);
        } else {
            logMessage("❌ Deploy FALHOU: $result");
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Erro no deploy: ' . $result,
                'timestamp' => $timestamp
            ]);
        }
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
