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
    // oauthUrlCharges já inclui /v1/authorize (não adicionar /oauth/token)
    echo "   OAuth Cobranças: {$oauthUrlChargesValue}\n";
    echo "   Base Cobranças:  {$baseUrlChargesValue}\n";
    // oauthUrlPix não inclui /oauth/token, então mostrar separado
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
        echo "   Formato: " . ($isJwtCharges ? 'JWT' : 'Outro') . " (ambos podem ser JWT)\n";
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
        echo "   Formato: " . ($isJwtPix ? 'JWT' : 'Outro') . "\n";
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
    
    // Interpretar corretamente: 401/403 = erro de autenticação, 400 validation_error = auth OK
    if ($httpCodeCharges === 200 || $httpCodeCharges === 201) {
        echo "   ✅ Requisição bem-sucedida\n";
        $chargesAuthOk = true;
    } elseif ($httpCodeCharges === 401 || $httpCodeCharges === 403) {
        echo "   ❌ HTTP {$httpCodeCharges} - Erro de AUTENTICAÇÃO\n";
        $errorMsg = $responseCharges['message'] ?? $responseCharges['error'] ?? $responseCharges['error_description'] ?? 'Erro desconhecido';
        echo "   Erro: " . substr($errorMsg, 0, 200) . "\n";
        if (strpos($errorMsg, 'Invalid key=value pair') !== false) {
            echo "   ⚠️ Erro AWS detectado - token pode estar sendo usado no host errado\n";
        }
        $chargesAuthOk = false;
    } elseif ($httpCodeCharges === 400) {
        // HTTP 400 pode ser validação (auth OK) ou erro de request
        $errorType = $responseCharges['error'] ?? '';
        $errorDesc = $responseCharges['error_description'] ?? '';
        
        if (strpos($errorDesc, 'validation_error') !== false || strpos($errorDesc, 'charge_type') !== false || strpos($errorDesc, 'obrigatória') !== false) {
            echo "   ✅ AUTH OK - Token válido, request chegou na API\n";
            echo "   ⚠️ HTTP 400 - Erro de VALIDAÇÃO (endpoint exige parâmetros obrigatórios)\n";
            echo "   Erro: " . substr($errorDesc, 0, 200) . "\n";
            $chargesAuthOk = true;
        } else {
            echo "   ⚠️ HTTP 400 - Erro na requisição\n";
            echo "   Erro: " . substr($errorDesc ?: json_encode($responseCharges, JSON_UNESCAPED_UNICODE), 0, 200) . "\n";
            $chargesAuthOk = false;
        }
    } else {
        echo "   ⚠️ HTTP {$httpCodeCharges}\n";
        $chargesAuthOk = false;
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
    
    // Critério correto: token obtido E não retornou 401/403 (erro de autenticação)
    $chargesOk = $tokenCharges && (isset($chargesAuthOk) && $chargesAuthOk);
    $pixOk = $tokenPix && (isset($httpCodePix) && ($httpCodePix === 200 || $httpCodePix === 201));
    
    echo "Token Cobranças: " . ($chargesOk ? "✅ OK" : "❌ PROBLEMA") . "\n";
    if ($chargesOk && isset($httpCodeCharges) && $httpCodeCharges === 400) {
        echo "   (Auth OK, mas endpoint exige parâmetros obrigatórios)\n";
    }
    
    echo "Token Pix:       " . ($pixOk ? "✅ OK" : "❌ PROBLEMA") . "\n";
    
    if (!$chargesOk) {
        echo "\n⚠️ PROBLEMA: Token de Cobranças não está funcionando corretamente.\n";
        if (!isset($httpCodeCharges) || $httpCodeCharges === 0) {
            echo "   - Não foi possível fazer requisição.\n";
        } elseif ($httpCodeCharges === 401 || $httpCodeCharges === 403) {
            echo "   - HTTP {$httpCodeCharges} indica erro de AUTENTICAÇÃO.\n";
            echo "   - Verifique se o token foi obtido do OAuth correto (cobrancas.api.efipay.com.br/v1/authorize).\n";
        }
    }
    
    if (!$pixOk) {
        echo "\n⚠️ PROBLEMA: Token Pix não está funcionando corretamente.\n";
        if (isset($httpCodePix) && ($httpCodePix === 401 || $httpCodePix === 403)) {
            echo "   - HTTP {$httpCodePix} indica erro de AUTENTICAÇÃO.\n";
        }
    }
    
    echo "\n";
    
    // Mostrar últimas linhas do log EFI
    echo "==========================================\n";
    echo "ÚLTIMAS LINHAS DO LOG EFI:\n";
    echo "==========================================\n";
    
    $logFile = __DIR__ . '/../../storage/logs/php_errors.log';
    if (file_exists($logFile)) {
        $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $efiLines = array_filter($lines, function($line) {
            return stripos($line, 'EFI-') !== false;
        });
        $efiLines = array_slice($efiLines, -30);
        
        if (!empty($efiLines)) {
            foreach ($efiLines as $line) {
                echo $line . "\n";
            }
        } else {
            echo "Nenhuma linha EFI encontrada no log.\n";
        }
    } else {
        echo "Arquivo de log não encontrado: {$logFile}\n";
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
