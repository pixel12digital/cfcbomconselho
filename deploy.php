<?php
/**
 * Webhook para deploy automático da Hostinger
 * Este arquivo será chamado automaticamente quando houver push no GitHub
 */

// Verificar se é uma requisição POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    die('Método não permitido');
}

// Log do webhook
$logFile = 'logs/deploy.log';
$timestamp = date('Y-m-d H:i:s');

// Criar diretório de logs se não existir
if (!file_exists('logs')) {
    mkdir('logs', 0755, true);
}

// Log da requisição
file_put_contents($logFile, "[$timestamp] Deploy iniciado\n", FILE_APPEND);

try {
    // Obter payload do GitHub (se disponível)
    $input = file_get_contents('php://input');
    $payload = json_decode($input, true);
    
    // Log do payload
    file_put_contents($logFile, "[$timestamp] Payload recebido: " . substr($input, 0, 200) . "...\n", FILE_APPEND);
    
    // Executar git pull
    $output = [];
    $exitCode = 0;
    
    // Comando para atualizar o repositório
    $command = 'cd ' . __DIR__ . ' && git pull origin master 2>&1';
    exec($command, $output, $exitCode);
    
    // Log do resultado
    $result = implode("\n", $output);
    file_put_contents($logFile, "[$timestamp] Git pull resultado ($exitCode): $result\n", FILE_APPEND);
    
    if ($exitCode === 0) {
        file_put_contents($logFile, "[$timestamp] Deploy CONCLUÍDO com sucesso\n", FILE_APPEND);
        
        // Limpar cache se necessário
        if (function_exists('opcache_reset')) {
            opcache_reset();
        }
        
        http_response_code(200);
        echo json_encode([
            'status' => 'success',
            'message' => 'Deploy realizado com sucesso',
            'timestamp' => $timestamp,
            'branch' => 'master'
        ]);
    } else {
        file_put_contents($logFile, "[$timestamp] Deploy FALHOU: $result\n", FILE_APPEND);
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'message' => 'Erro no deploy: ' . $result,
            'timestamp' => $timestamp
        ]);
    }
    
} catch (Exception $e) {
    file_put_contents($logFile, "[$timestamp] Erro no deploy: " . $e->getMessage() . "\n", FILE_APPEND);
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Erro interno: ' . $e->getMessage(),
        'timestamp' => $timestamp
    ]);
}
?>
