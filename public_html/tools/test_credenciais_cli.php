<?php
/**
 * Script CLI - Teste de Credenciais EFI
 * 
 * Uso: php public_html/tools/test_credenciais_cli.php
 * 
 * Este script testa as credenciais EFI via linha de comando
 */

require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

// Obter credenciais
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;

$oauthUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br'
    : 'https://apis.gerencianet.com.br';

echo "========================================\n";
echo "TESTE DE CREDENCIAIS EFI (CLI)\n";
echo "========================================\n\n";

// 1. Verificar configuração
echo "1. Verificando configuração...\n";
echo "   EFI_CLIENT_ID: " . (empty($clientId) ? "❌ NÃO CONFIGURADO" : "✅ Configurado (" . substr($clientId, 0, 20) . "...)") . "\n";
echo "   EFI_CLIENT_SECRET: " . (empty($clientSecret) ? "❌ NÃO CONFIGURADO" : "✅ Configurado (" . substr($clientSecret, 0, 20) . "...)") . "\n";
echo "   EFI_SANDBOX: " . ($sandbox ? "✅ true (SANDBOX)" : "✅ false (PRODUÇÃO)") . "\n";
echo "   EFI_CERT_PATH: " . (empty($certPath) ? "⚠️  Não configurado" : (file_exists($certPath) ? "✅ Existe: $certPath" : "❌ Arquivo não encontrado: $certPath")) . "\n";
echo "   EFI_CERT_PASSWORD: " . (empty($certPassword) ? "⚠️  Não configurado" : "✅ Configurado") . "\n";
echo "   Ambiente: " . ($sandbox ? "SANDBOX" : "PRODUÇÃO") . "\n";
echo "   URL OAuth: $oauthUrl/oauth/token\n\n";

if (empty($clientId) || empty($clientSecret)) {
    echo "❌ ERRO: Credenciais não configuradas!\n";
    echo "   Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET no arquivo .env\n";
    exit(1);
}

// 2. Testar autenticação
echo "2. Testando autenticação OAuth...\n";

$url = $oauthUrl . '/oauth/token';
$payload = ['grant_type' => 'client_credentials'];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/x-www-form-urlencoded',
    'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
]);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

// Configurar certificado se necessário
if ($certPath && file_exists($certPath)) {
    curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
    curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
    curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
    if ($certPassword) {
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
    }
    echo "   Certificado configurado: $certPath\n";
} elseif (!$sandbox) {
    echo "   ⚠️  AVISO: Produção sem certificado configurado\n";
}

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
$curlErrNo = curl_errno($ch);
curl_close($ch);

if ($curlError) {
    echo "   ❌ ERRO cURL: $curlError (errno: $curlErrNo)\n";
    if (strpos($curlError, 'Connection was reset') !== false || strpos($curlError, 'Recv failure') !== false) {
        echo "   💡 Possível causa: Certificado cliente necessário em produção\n";
    } elseif (strpos($curlError, 'SSL') !== false || strpos($curlError, 'certificate') !== false) {
        echo "   💡 Possível causa: Problema com certificado SSL\n";
    }
    exit(1);
}

echo "   HTTP Code: $httpCode\n";

if ($httpCode !== 200) {
    $errorData = json_decode($response, true);
    $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido';
    echo "   ❌ FALHA: HTTP $httpCode - $errorMessage\n";
    
    if ($httpCode === 401) {
        echo "   💡 Credenciais inválidas ou expiradas\n";
        echo "   💡 Verifique se CLIENT_ID e CLIENT_SECRET estão corretos\n";
        echo "   💡 Verifique se está usando credenciais do ambiente correto (sandbox/produção)\n";
    } elseif ($httpCode === 403) {
        echo "   💡 Acesso negado - pode ser necessário certificado em produção\n";
    }
    
    if ($response) {
        echo "   Resposta: " . substr($response, 0, 200) . "\n";
    }
    exit(1);
}

$data = json_decode($response, true);
if (!isset($data['access_token'])) {
    echo "   ❌ ERRO: access_token não encontrado na resposta\n";
    if ($response) {
        echo "   Resposta: " . substr($response, 0, 200) . "\n";
    }
    exit(1);
}

$token = $data['access_token'];
$tokenType = $data['token_type'] ?? 'Bearer';
$expiresIn = $data['expires_in'] ?? 'N/A';

echo "   ✅ SUCESSO!\n";
echo "   Token Type: $tokenType\n";
echo "   Expires In: $expiresIn segundos\n";
echo "   Access Token: " . substr($token, 0, 30) . "...\n\n";

// 3. Resumo final
echo "========================================\n";
echo "RESUMO\n";
echo "========================================\n";
echo "✅ Credenciais válidas!\n";
echo "✅ Autenticação OAuth funcionando\n";
echo "✅ Ambiente: " . ($sandbox ? "SANDBOX" : "PRODUÇÃO") . "\n";
if ($certPath && file_exists($certPath)) {
    echo "✅ Certificado configurado\n";
} elseif (!$sandbox) {
    echo "⚠️  Certificado não configurado (pode ser necessário em produção)\n";
}
echo "\n";
echo "🎉 Integração EFI está configurada corretamente!\n";
echo "========================================\n";

exit(0);
