<?php
/**
 * Teste Direto do Certificado - Verifica se o certificado pode ser lido
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

Env::load();

$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? '';

header('Content-Type: text/plain; charset=utf-8');

if (!$certPath || !file_exists($certPath)) {
    die("❌ Certificado não encontrado: " . ($certPath ?? 'não configurado'));
}

echo "🔍 Teste Direto do Certificado\n";
echo str_repeat("=", 60) . "\n\n";

echo "Caminho: {$certPath}\n";
echo "Tamanho: " . filesize($certPath) . " bytes\n";
echo "Senha configurada: " . (!empty($certPassword) ? "SIM (" . strlen($certPassword) . " caracteres)" : "NÃO") . "\n\n";

// Verificar formato do arquivo (sem usar OpenSSL para não travar)
echo "📋 Verificando formato do arquivo...\n";
echo str_repeat("-", 60) . "\n";

// Verificar se é um P12 válido
echo "\n1. Verificando formato do arquivo:\n";
$fileContent = file_get_contents($certPath, false, null, 0, 4);
$hex = bin2hex($fileContent);
echo "   Primeiros bytes (hex): {$hex}\n";
if (substr($hex, 0, 4) === '3082' || substr($hex, 0, 4) === '30a0') {
    echo "   ✅ Parece ser um arquivo PKCS#12 válido\n";
} else {
    echo "   ⚠️  Formato pode estar incorreto\n";
}

// Teste 2: Tentar usar o certificado com cURL (teste real)
echo "\n2. Teste REAL: Tentando usar certificado em requisição cURL:\n";
$testUrl = "https://apis.gerencianet.com.br/oauth/token";
$ch = curl_init($testUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Authorization: Basic ' . base64_encode('test:test')
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);

// Tentar com certificado
curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');

if (!empty($certPassword)) {
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
    curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
    echo "   Usando senha configurada...\n";
} else {
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
    curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
    echo "   Tentando SEM senha...\n";
}

$verbose = fopen('php://temp', 'w+');
curl_setopt($ch, CURLOPT_VERBOSE, true);
curl_setopt($ch, CURLOPT_STDERR, $verbose);

$response = curl_exec($ch);
$curlError = curl_error($ch);
$curlErrNo = curl_errno($ch);
curl_close($ch);

rewind($verbose);
$verboseLog = stream_get_contents($verbose);
fclose($verbose);

if ($curlErrNo !== 0) {
    echo "   ❌ Erro ao usar certificado: {$curlError}\n";
    if (strpos($curlError, 'bad password') !== false || strpos($curlError, 'Mac verify failure') !== false) {
        echo "   ⚠️  O certificado PROVAVELMENTE TEM SENHA!\n";
        echo "   A mensagem de erro indica problema com senha do certificado.\n";
    }
} else {
    echo "   ✅ Certificado foi aceito pelo cURL (sem erros de senha)\n";
    if (empty($certPassword)) {
        echo "   ✅ Certificado NÃO precisa de senha\n";
    } else {
        echo "   ✅ Senha configurada está CORRETA\n";
    }
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "💡 CONCLUSÃO:\n";
if ($curlErrNo === 0 && (strpos($curlError, 'bad password') === false && strpos($curlError, 'Mac verify failure') === false)) {
    echo "   ✅ O certificado NÃO tem senha e está funcionando corretamente.\n\n";
    echo "   ⚠️  O problema do erro 401 NÃO é a senha do certificado.\n";
    echo "   O problema pode ser:\n";
    echo "   1. ❌ Credenciais não correspondem ao certificado\n";
    echo "      → Certificado e credenciais devem ser da MESMA aplicação\n";
    echo "   2. ❌ Escopos não habilitados na aplicação EFI\n";
    echo "      → Verifique na dashboard: API → Minhas Aplicações → Escopos\n";
    echo "   3. ❌ Aplicação inativa ou credenciais revogadas\n";
    echo "      → Verifique se a aplicação está ATIVA na dashboard\n";
    echo "   4. ❌ Certificado e credenciais são de ambientes diferentes\n";
    echo "      → Ambos devem ser de PRODUÇÃO (não misturar com Homologação)\n\n";
    echo "   📋 AÇÃO NECESSÁRIA:\n";
    echo "   Peça ao cliente para verificar na dashboard da EFI:\n";
    echo "   - Aplicação está ATIVA?\n";
    echo "   - Escopos estão HABILITADOS? (Cobranças, PIX, etc.)\n";
    echo "   - Certificado e credenciais são da MESMA aplicação?\n";
} else {
    echo "   ⚠️  O certificado pode ter senha ou há problema ao usá-lo.\n";
    echo "   Erro: {$curlError}\n";
}
