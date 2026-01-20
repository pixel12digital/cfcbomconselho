<?php
/**
 * Script de Teste - Tokens Separados EFI (Cobranças vs Pix)
 * 
 * Execute via SSH:
 * php public_html/tools/test_efi_split_tokens.php
 * 
 * Ou acesse via browser:
 * https://painel.cfcbomconselho.com.br/tools/test_efi_split_tokens.php
 */

require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/Services/EfiPaymentService.php';

use App\Config\Env;
use App\Services\EfiPaymentService;

// Habilitar exibição de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Carregar ENV
try {
    Env::load();
} catch (Exception $e) {
    echo "ERRO ao carregar ENV: " . $e->getMessage() . "\n";
    exit(1);
}

// Não usar header() em CLI
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

echo "==========================================\n";
echo "TESTE: Tokens Separados EFI (Cobranças vs Pix)\n";
echo "==========================================\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

try {
    echo "Criando instância de EfiPaymentService...\n";
    $service = new EfiPaymentService();
    echo "✅ Instância criada\n\n";
    $reflection = new ReflectionClass($service);
    
    // Obter URLs
    $oauthUrlCharges = $reflection->getProperty('oauthUrlCharges');
    $oauthUrlCharges->setAccessible(true);
    $oauthUrlChargesValue = $oauthUrlCharges->getValue($service);
    
    $baseUrlCharges = $reflection->getProperty('baseUrlCharges');
    $baseUrlCharges->setAccessible(true);
    $baseUrlChargesValue = $baseUrlCharges->getValue($service);
    
    $oauthUrlPix = $reflection->getProperty('oauthUrlPix');
    $oauthUrlPix->setAccessible(true);
    $oauthUrlPixValue = $oauthUrlPix->getValue($service);
    
    $baseUrlPix = $reflection->getProperty('baseUrlPix');
    $baseUrlPix->setAccessible(true);
    $baseUrlPixValue = $baseUrlPix->getValue($service);
    
    echo "1. URLs Configuradas:\n";
    echo "   OAuth Cobranças: {$oauthUrlChargesValue}/oauth/token\n";
    echo "   Base Cobranças:  {$baseUrlChargesValue}\n";
    echo "   OAuth Pix:       {$oauthUrlPixValue}/oauth/token\n";
    echo "   Base Pix:        {$baseUrlPixValue}\n\n";
    
    // Obter tokens
    $getToken = $reflection->getMethod('getAccessToken');
    $getToken->setAccessible(true);
    
    echo "2. Obtendo Token de Cobranças (getAccessToken(false))...\n";
    $tokenCharges = $getToken->invoke($service, false);
    
    if ($tokenCharges) {
        $tokenChargesLen = strlen($tokenCharges);
        $tokenChargesPrefix = substr($tokenCharges, 0, 10);
        $isJwtCharges = (substr($tokenCharges, 0, 3) === 'eyJ');
        
        echo "   ✅ Token obtido\n";
        echo "   Length: {$tokenChargesLen} caracteres\n";
        echo "   Prefix: {$tokenChargesPrefix}\n";
        echo "   É JWT (eyJ): " . ($isJwtCharges ? 'SIM ⚠️' : 'NÃO ✅') . "\n";
        
        if ($isJwtCharges) {
            echo "   ⚠️ AVISO: Token de Cobranças é JWT (típico de Pix)!\n";
        }
    } else {
        echo "   ❌ Token NÃO obtido\n";
        exit(1);
    }
    
    echo "\n3. Obtendo Token Pix (getAccessToken(true))...\n";
    $tokenPix = $getToken->invoke($service, true);
    
    if ($tokenPix) {
        $tokenPixLen = strlen($tokenPix);
        $tokenPixPrefix = substr($tokenPix, 0, 10);
        $isJwtPix = (substr($tokenPix, 0, 3) === 'eyJ');
        
        echo "   ✅ Token obtido\n";
        echo "   Length: {$tokenPixLen} caracteres\n";
        echo "   Prefix: {$tokenPixPrefix}\n";
        echo "   É JWT (eyJ): " . ($isJwtPix ? 'SIM ✅' : 'NÃO ⚠️') . "\n";
    } else {
        echo "   ❌ Token NÃO obtido\n";
        exit(1);
    }
    
    echo "\n4. Testando Requisições:\n";
    
    // Teste 1: Cobranças
    // makeRequest já adiciona /v1/ automaticamente, então usar apenas /charges
    echo "\n   4.1. GET {$baseUrlChargesValue}/v1/charges?limit=1\n";
    $makeRequest = $reflection->getMethod('makeRequest');
    $makeRequest->setAccessible(true);
    
    $resultCharges = $makeRequest->invoke($service, 'GET', '/charges?limit=1', null, $tokenCharges, false);
    
    // makeRequest agora sempre retorna array com http_code
    $httpCodeCharges = $resultCharges['http_code'] ?? 0;
    $responseCharges = $resultCharges['response'] ?? $resultCharges;
    
    echo "   HTTP Code: {$httpCodeCharges}\n";
    
    if ($httpCodeCharges === 200 || $httpCodeCharges === 201) {
        echo "   ✅ Requisição bem-sucedida\n";
    } elseif ($httpCodeCharges === 403) {
        echo "   ❌ HTTP 403 - Acesso negado\n";
        $errorMsg = $responseCharges['message'] ?? $responseCharges['error'] ?? 'Erro desconhecido';
        echo "   Erro: " . substr($errorMsg, 0, 200) . "\n";
        if (strpos($errorMsg, 'Invalid key=value pair') !== false) {
            echo "   ⚠️ Erro AWS detectado - verifique se está usando cobrancas.api.efipay.com.br\n";
        }
    } else {
        echo "   ⚠️ HTTP {$httpCodeCharges}\n";
    }
    
    $responsePreview = json_encode($responseCharges, JSON_UNESCAPED_UNICODE);
    echo "   Response (primeiros 200 chars): " . substr($responsePreview, 0, 200) . "\n";
    
    // Teste 2: Pix
    // API Pix requer formato date-time ISO 8601
    $inicio = date('Y-m-d\T00:00:00\Z', strtotime('-1 day'));
    $fim = date('Y-m-d\T23:59:59\Z');
    echo "\n   4.2. GET {$baseUrlPixValue}/v2/cob?inicio={$inicio}&fim={$fim}\n";
    
    $endpointPix = '/v2/cob?inicio=' . urlencode($inicio) . '&fim=' . urlencode($fim);
    $resultPix = $makeRequest->invoke($service, 'GET', $endpointPix, null, $tokenPix, true);
    
    // makeRequest agora sempre retorna array com http_code
    $httpCodePix = $resultPix['http_code'] ?? 0;
    $responsePix = $resultPix['response'] ?? $resultPix;
    
    echo "   HTTP Code: {$httpCodePix}\n";
    
    if ($httpCodePix === 200 || $httpCodePix === 201) {
        echo "   ✅ Requisição bem-sucedida\n";
    } elseif ($httpCodePix === 403) {
        echo "   ❌ HTTP 403 - Acesso negado\n";
        $errorMsg = $responsePix['message'] ?? $responsePix['error'] ?? 'Erro desconhecido';
        echo "   Erro: " . substr($errorMsg, 0, 200) . "\n";
    } else {
        echo "   ⚠️ HTTP {$httpCodePix}\n";
    }
    
    $responsePreview = json_encode($responsePix, JSON_UNESCAPED_UNICODE);
    echo "   Response (primeiros 200 chars): " . substr($responsePreview, 0, 200) . "\n";
    
    echo "\n==========================================\n";
    echo "RESUMO:\n";
    echo "==========================================\n";
    
    $chargesOk = !$isJwtCharges && (isset($httpCodeCharges) && ($httpCodeCharges === 200 || $httpCodeCharges === 201));
    $pixOk = $isJwtPix && (isset($httpCodePix) && ($httpCodePix === 200 || $httpCodePix === 201));
    
    echo "Token Cobranças: " . ($chargesOk ? "✅ OK" : "❌ PROBLEMA") . "\n";
    echo "Token Pix:       " . ($pixOk ? "✅ OK" : "❌ PROBLEMA") . "\n";
    
    if (!$chargesOk) {
        echo "\n⚠️ PROBLEMA: Token de Cobranças não está funcionando corretamente.\n";
        if ($isJwtCharges) {
            echo "   - Token é JWT (típico de Pix), pode estar usando OAuth errado.\n";
        }
        if (isset($httpCodeCharges) && $httpCodeCharges === 403) {
            echo "   - HTTP 403 indica que o token não é aceito pela API de Cobranças.\n";
        }
    }
    
    if (!$pixOk && isset($httpCodePix) && $httpCodePix === 403) {
        echo "\n⚠️ PROBLEMA: Token Pix retornou HTTP 403.\n";
    }
    
    echo "\n";
    
} catch (Error $e) {
    echo "❌ ERRO FATAL: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
