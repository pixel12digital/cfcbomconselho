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

// Carregar token de teste (opcional) de config/deploy-token.txt
$testToken = null;
if (file_exists(__DIR__ . '/config/deploy-token.txt')) {
    $testToken = trim(@file_get_contents(__DIR__ . '/config/deploy-token.txt')) ?: null;
}

// Verificar método: somente POST permitido
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
        
        // Executar deploy automático
        logMessage("🔄 Executando git pull...");
        
        // Verificar se git está disponível
        $gitCheck = shell_exec('which git 2>/dev/null || where git 2>/dev/null');
        logMessage("Git path: " . trim($gitCheck ?: 'não encontrado'));
        
        $commands = [
            'git --version',
            'git fetch --all',
            'git reset --hard origin/master',
            'git clean -fd',
            'git pull --rebase --autostash'
        ];
        
        $deploySuccess = true;
        $output = [];
        
        foreach ($commands as $cmd) {
            $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
            $proc = proc_open($cmd, $descriptor, $pipes, __DIR__);
            
            if (is_resource($proc)) {
                $stdout = stream_get_contents($pipes[1]);
                $stderr = stream_get_contents($pipes[2]);
                fclose($pipes[1]);
                fclose($pipes[2]);
                $exit = proc_close($proc);
                
                logMessage("$cmd => exit:$exit");
                if ($stdout) logMessage("STDOUT: " . trim($stdout));
                if ($stderr) logMessage("STDERR: " . trim($stderr));
                
                if ($exit !== 0) {
                    $deploySuccess = false;
                    logMessage("❌ Erro no comando: $cmd");
                    break;
                }
            } else {
                $deploySuccess = false;
                logMessage("❌ Falha ao executar: $cmd");
                break;
            }
        }
        
        if ($deploySuccess) {
            logMessage("✅ Deploy concluído com sucesso");
            http_response_code(200);
            echo json_encode([
                'status' => 'success',
                'message' => 'Deploy executado com sucesso',
                'timestamp' => $timestamp,
                'branch' => 'master',
                'action' => 'deploy_completed'
            ]);
        } else {
            logMessage("❌ Deploy falhou");
            http_response_code(500);
            echo json_encode([
                'status' => 'error',
                'message' => 'Deploy falhou - verifique logs',
                'timestamp' => $timestamp,
                'branch' => 'master',
                'action' => 'deploy_failed'
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