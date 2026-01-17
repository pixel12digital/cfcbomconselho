<?php
/**
 * Teste Final - Verifica se certificado está sendo enviado no handshake TLS
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

Env::load();

$clientId = trim($_ENV['EFI_CLIENT_ID'] ?? '');
$clientSecret = trim($_ENV['EFI_CLIENT_SECRET'] ?? '');
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? '';

header('Content-Type: text/plain; charset=utf-8');

echo "🔍 TESTE FINAL - Verificação Completa\n";
echo str_repeat("=", 70) . "\n\n";

if (empty($clientId) || empty($clientSecret)) {
    die("❌ Credenciais não configuradas\n");
}

if (!$certPath || !file_exists($certPath)) {
    die("❌ Certificado não encontrado\n");
}

$url = "https://apis.gerencianet.com.br/oauth/token";

echo "📋 Configuração:\n";
echo "   CLIENT_ID: " . strlen($clientId) . " caracteres\n";
echo "   CLIENT_SECRET: " . strlen($clientSecret) . " caracteres\n";
echo "   CERT_PATH: {$certPath}\n";
echo "   CERT_EXISTS: " . (file_exists($certPath) ? "SIM" : "NÃO") . "\n";
echo "   CERT_PASSWORD: " . (!empty($certPassword) ? "SIM" : "NÃO") . "\n\n";

echo "🔧 Testando diferentes configurações de certificado...\n";
echo str_repeat("-", 70) . "\n\n";

// Teste 1: Configuração atual (P12 com CURLOPT_SSLKEY)
echo "TESTE 1: P12 com CURLOPT_SSLCERT + CURLOPT_SSLKEY\n";
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
    CURLOPT_SSLCERTPASSWD => $certPassword ?: '',
    CURLOPT_SSLKEYPASSWD => $certPassword ?: '',
]);

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

echo "   HTTP Code: {$httpCode}\n";
echo "   cURL Error: " . ($curlError ?: "Nenhum") . "\n";
echo "   Response: " . substr($response, 0, 200) . "\n";

// Verificar se certificado cliente aparece no verbose
if (strpos($verboseLog, 'client certificate') !== false || 
    strpos($verboseLog, 'Client Certificate') !== false ||
    strpos($verboseLog, 'SSL client certificate') !== false) {
    echo "   ✅ Certificado cliente APARECE no verbose\n";
} else {
    echo "   ⚠️  Certificado cliente NÃO aparece no verbose\n";
    echo "   (Isso pode ser normal - nem sempre aparece no verbose)\n";
}

if ($httpCode === 200) {
    echo "   ✅ SUCESSO!\n";
    $data = json_decode($response, true);
    if (isset($data['access_token'])) {
        echo "   ✅ Access token obtido!\n";
    }
} else {
    echo "   ❌ FALHOU\n";
}

echo "\n";

// Teste 2: Apenas CURLOPT_SSLCERT (sem CURLOPT_SSLKEY)
echo "TESTE 2: Apenas CURLOPT_SSLCERT (sem CURLOPT_SSLKEY)\n";
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
    CURLOPT_SSLCERTPASSWD => $certPassword ?: '',
]);

$response2 = curl_exec($ch);
$httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError2 = curl_error($ch);
curl_close($ch);

echo "   HTTP Code: {$httpCode2}\n";
echo "   cURL Error: " . ($curlError2 ?: "Nenhum") . "\n";
echo "   Response: " . substr($response2, 0, 200) . "\n";

if ($httpCode2 === 200) {
    echo "   ✅ SUCESSO!\n";
} else {
    echo "   ❌ FALHOU\n";
}

echo "\n" . str_repeat("=", 70) . "\n";
echo "💡 CONCLUSÃO:\n";

if ($httpCode === 200 || $httpCode2 === 200) {
    echo "✅ Autenticação funcionou! O problema estava na configuração.\n";
} else {
    echo "❌ Ambos os testes falharam com HTTP {$httpCode}.\n";
    echo "\nO problema NÃO é a configuração do certificado no código.\n";
    echo "O problema é:\n";
    echo "1. Credenciais não correspondem ao certificado (aplicações diferentes)\n";
    echo "2. Ou há algum problema na validação do lado da EFI\n";
    echo "\nPeça ao cliente para:\n";
    echo "- Verificar se certificado e credenciais são da MESMA aplicação\n";
    echo "- Gerar NOVAS credenciais se necessário\n";
    echo "- Verificar se não há restrições de IP ou outras configurações\n";
}
