<?php
/**
 * Script de Teste com Debug Completo - Autenticação EFI
 * 
 * Uso: Acesse via browser: https://painel.cfcbomconselho.com.br/public_html/tools/test_efi_auth_debug.php
 * 
 * Este script mostra debug completo da autenticação EFI.
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

$clientId = trim($_ENV['EFI_CLIENT_ID'] ?? '');
$clientSecret = trim($_ENV['EFI_CLIENT_SECRET'] ?? '');
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? '';
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';

$oauthUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br'
    : 'https://apis.gerencianet.com.br';

$url = $oauthUrl . '/oauth/token';

// Função helper para não expor segredos completos
function tailHex($s, $n = 6) {
    if (strlen($s) <= $n) return '***';
    $t = substr($s, -$n);
    return bin2hex($t);
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debug Completo - Autenticação EFI</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            padding: 20px;
            background: #1e1e1e;
            color: #d4d4d4;
        }
        .container {
            background: #252526;
            padding: 20px;
            border-radius: 4px;
        }
        h1 { color: #4ec9b0; }
        .section {
            margin: 20px 0;
            padding: 15px;
            background: #2d2d30;
            border-left: 3px solid #007acc;
        }
        .label { color: #569cd6; }
        .value { color: #ce9178; }
        .success { color: #4ec9b0; }
        .error { color: #f48771; }
        .warning { color: #dcdcaa; }
        pre {
            background: #1e1e1e;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Debug Completo - Autenticação EFI</h1>
        
        <div class="section">
            <h2>📋 Configuração</h2>
            <p><span class="label">CLIENT_ID:</span> <span class="value"><?= strlen($clientId) ?> caracteres | Tail: <?= tailHex($clientId) ?></span></p>
            <p><span class="label">CLIENT_SECRET:</span> <span class="value"><?= strlen($clientSecret) ?> caracteres | Tail: <?= tailHex($clientSecret) ?></span></p>
            <p><span class="label">CERT_PATH:</span> <span class="value"><?= $certPath ?? 'não configurado' ?></span></p>
            <p><span class="label">CERT_EXISTS:</span> <span class="value"><?= $certPath && file_exists($certPath) ? '✅ SIM' : '❌ NÃO' ?></span></p>
            <p><span class="label">CERT_PASSWORD:</span> <span class="value"><?= !empty($certPassword) ? '✅ Configurada (' . strlen($certPassword) . ' caracteres)' : '❌ Não configurada' ?></span></p>
            <p><span class="label">SANDBOX:</span> <span class="value"><?= $sandbox ? 'true (SANDBOX)' : 'false (PRODUÇÃO)' ?></span></p>
            <p><span class="label">OAUTH_URL:</span> <span class="value"><?= $url ?></span></p>
        </div>

        <?php
        if (empty($clientId) || empty($clientSecret)) {
            echo '<div class="section"><p class="error">❌ Credenciais não configuradas!</p></div>';
            exit;
        }

        $ch = curl_init($url);
        
        $postFields = http_build_query(['grant_type' => 'client_credentials']);
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $postFields,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded',
                'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        // Configurar certificado se existir
        if ($certPath && file_exists($certPath)) {
            // Configurar certificado cliente para mutual TLS (mTLS)
            curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
            // Para P12, também pode precisar especificar a chave (mesmo arquivo)
            curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
            if (!empty($certPassword)) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
            } else {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
            }
        }

        // Capturar verbose
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verbose);

        $startTime = microtime(true);
        $response = curl_exec($ch);
        $endTime = microtime(true);
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errNo = curl_errno($ch);
        $errStr = curl_error($ch);
        $curlInfo = curl_getinfo($ch);
        
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        
        curl_close($ch);
        ?>

        <div class="section">
            <h2>📤 Requisição</h2>
            <p><span class="label">URL:</span> <span class="value"><?= htmlspecialchars($url) ?></span></p>
            <p><span class="label">Method:</span> <span class="value">POST</span></p>
            <p><span class="label">Payload:</span> <span class="value">grant_type=client_credentials</span></p>
            <p><span class="label">Auth Header:</span> <span class="value">Basic <?= base64_encode(substr($clientId, 0, 10) . ':***') ?></span></p>
            <?php if ($certPath && file_exists($certPath)): ?>
                <p><span class="label">Certificado:</span> <span class="value">✅ Enviado (<?= basename($certPath) ?>)</span></p>
                <p><span class="label">Senha do Certificado:</span> <span class="value"><?= !empty($certPassword) ? '✅ Configurada' : '❌ Vazia (sem senha)' ?></span></p>
            <?php else: ?>
                <p><span class="label">Certificado:</span> <span class="error">❌ NÃO enviado</span></p>
            <?php endif; ?>
        </div>

        <div class="section">
            <h2>📥 Resposta</h2>
            <p><span class="label">HTTP Code:</span> <span class="value <?= $httpCode === 200 ? 'success' : 'error' ?>"><?= $httpCode ?></span></p>
            <p><span class="label">cURL ErrNo:</span> <span class="value <?= $errNo === 0 ? 'success' : 'error' ?>"><?= $errNo ?></span></p>
            <p><span class="label">cURL Error:</span> <span class="value <?= empty($errStr) ? 'success' : 'error' ?>"><?= empty($errStr) ? 'Nenhum erro' : htmlspecialchars($errStr) ?></span></p>
            <p><span class="label">Tempo de Resposta:</span> <span class="value"><?= number_format(($endTime - $startTime) * 1000, 2) ?> ms</span></p>
            <p><span class="label">Response Body:</span></p>
            <pre><?= htmlspecialchars($response) ?></pre>
        </div>

        <?php if (!empty($verboseLog)): ?>
        <div class="section">
            <h2>🔍 cURL Verbose (Detalhes Técnicos)</h2>
            <pre><?= htmlspecialchars($verboseLog) ?></pre>
        </div>
        <?php endif; ?>

        <div class="section">
            <h2>📊 Informações do cURL</h2>
            <pre><?= print_r($curlInfo, true) ?></pre>
        </div>

        <div class="section">
            <h2>✅ Resultado</h2>
            <?php if ($errNo !== 0): ?>
                <p class="error">❌ Erro de cURL: <?= htmlspecialchars($errStr) ?></p>
            <?php elseif ($httpCode === 200): ?>
                <?php
                $data = json_decode($response, true);
                if (isset($data['access_token'])) {
                    echo '<p class="success">✅ Autenticação bem-sucedida!</p>';
                    echo '<p><span class="label">Access Token:</span> <span class="value">' . substr($data['access_token'], 0, 50) . '...</span></p>';
                } else {
                    echo '<p class="error">❌ Resposta não contém access_token</p>';
                }
                ?>
            <?php else: ?>
                <p class="error">❌ HTTP <?= $httpCode ?>: <?= htmlspecialchars($response) ?></p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
