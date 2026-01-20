<?php
/**
 * Verifica se o certificado foi atualizado e testa autenticação
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

Env::load();

$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Verificação de Certificado Atualizado</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        .ok { color: #4ec9b0; }
        .erro { color: #f48771; }
        .info { color: #569cd6; }
        pre { background: #252526; padding: 10px; border-radius: 4px; }
    </style>
</head>
<body>
    <h1>Verificação de Certificado Atualizado</h1>
    
    <?php
    if (!$certPath || !file_exists($certPath)) {
        echo "<p class='erro'>❌ Certificado não encontrado: " . htmlspecialchars($certPath ?? 'não configurado') . "</p>";
        exit;
    }
    
    $fileSize = filesize($certPath);
    $lastModified = filemtime($certPath);
    $dateModified = date('Y-m-d H:i:s', $lastModified);
    
    echo "<p class='info'>📁 Certificado encontrado:</p>";
    echo "<pre>";
    echo "Caminho: " . htmlspecialchars($certPath) . "\n";
    echo "Tamanho: " . number_format($fileSize) . " bytes\n";
    echo "Última modificação: {$dateModified}\n";
    echo "</pre>";
    
    if (!$clientId || !$clientSecret) {
        echo "<p class='erro'>❌ Credenciais não configuradas!</p>";
        exit;
    }
    
    echo "<p class='info'>🔧 Testando autenticação com novo certificado...</p>";
    
    $url = "https://apis.gerencianet.com.br/oauth/token";
    $ch = curl_init($url);
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => "grant_type=client_credentials",
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
        ],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_SSLCERT => $certPath,
        CURLOPT_SSLCERTTYPE => 'P12',
        CURLOPT_SSLKEY => $certPath,
        CURLOPT_SSLKEYTYPE => 'P12',
        CURLOPT_SSLCERTPASSWD => '',
        CURLOPT_SSLKEYPASSWD => '',
    ]);
    
    $verbose = fopen('php://temp', 'w+');
    curl_setopt($ch, CURLOPT_VERBOSE, true);
    curl_setopt($ch, CURLOPT_STDERR, $verbose);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    
    rewind($verbose);
    $verboseLog = stream_get_contents($verbose);
    fclose($verbose);
    curl_close($ch);
    
    echo "<pre>";
    echo "HTTP Code: {$httpCode}\n";
    echo "cURL Error: " . ($curlError ?: "Nenhum") . "\n";
    echo "Response: " . htmlspecialchars($response) . "\n";
    echo "</pre>";
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            echo "<p class='ok'>✅ SUCESSO! Autenticação funcionou com o novo certificado!</p>";
            echo "<p class='ok'>✅ Access token obtido: " . substr($data['access_token'], 0, 50) . "...</p>";
        } else {
            echo "<p class='erro'>❌ Resposta não contém access_token</p>";
        }
    } else {
        echo "<p class='erro'>❌ FALHOU - HTTP {$httpCode}</p>";
        echo "<p class='info'>Se ainda der erro 401, o problema pode ser:</p>";
        echo "<ul>";
        echo "<li>Certificado e credenciais ainda não correspondem</li>";
        echo "<li>Necessário gerar novas credenciais também</li>";
        echo "<li>Verificar se ambos são da mesma aplicação</li>";
        echo "</ul>";
    }
    
    if ($verboseLog) {
        echo "<details><summary>Verbose do cURL</summary><pre>" . htmlspecialchars(substr($verboseLog, 0, 2000)) . "</pre></details>";
    }
    ?>
</body>
</html>
