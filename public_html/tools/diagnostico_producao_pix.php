<?php
/**
 * Script de Diagnóstico - API Pix EFI em Produção
 * 
 * Execute via SSH:
 * php public_html/tools/diagnostico_producao_pix.php
 * 
 * Ou acesse via browser (se permitido):
 * https://painel.cfcbomconselho.com.br/tools/diagnostico_producao_pix.php
 */

// Carregar configurações
require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Services/EfiPaymentService.php';

use App\Config\Env;
use App\Services\EfiPaymentService;

// Carregar ENV
Env::load();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico API Pix - Produção</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico API Pix EFI - Produção</h1>
    <p><strong>Data/Hora:</strong> <?= date('Y-m-d H:i:s') ?></p>

<?php

// 1. Verificar código atualizado
echo '<div class="section">';
echo '<h2>1. Verificação de Código</h2>';
$gitStatus = shell_exec('cd ' . dirname(__DIR__, 2) . ' && git log -1 --oneline 2>&1');
$gitBranch = shell_exec('cd ' . dirname(__DIR__, 2) . ' && git branch --show-current 2>&1');
echo '<pre>';
echo "Branch: " . trim($gitBranch) . "\n";
echo "Último commit: " . trim($gitStatus) . "\n";
echo '</pre>';

// Verificar se o arquivo tem as URLs corretas
$serviceFile = dirname(__DIR__, 2) . '/app/Services/EfiPaymentService.php';
$fileContent = file_get_contents($serviceFile);
$hasPixUrl = strpos($fileContent, 'pix.api.efipay.com.br') !== false;
$hasBaseUrlPix = strpos($fileContent, 'baseUrlPix') !== false;

if ($hasPixUrl && $hasBaseUrlPix) {
    echo '<p class="success">✅ Código atualizado com URLs Pix corretas</p>';
} else {
    echo '<p class="error">❌ Código NÃO tem URLs Pix corretas. Execute: git pull origin master</p>';
}
echo '</div>';

// 2. Verificar configuração ENV
echo '<div class="section">';
echo '<h2>2. Configuração ENV</h2>';
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;
$pixKey = $_ENV['EFI_PIX_KEY'] ?? null;

echo '<pre>';
echo "EFI_CLIENT_ID: " . ($clientId ? substr($clientId, 0, 20) . '...' : '❌ NÃO CONFIGURADO') . "\n";
echo "EFI_CLIENT_SECRET: " . ($clientSecret ? '✅ Configurado (' . strlen($clientSecret) . ' chars)' : '❌ NÃO CONFIGURADO') . "\n";
echo "EFI_SANDBOX: " . ($sandbox ? '⚠️ true (SANDBOX)' : '✅ false (PRODUÇÃO)') . "\n";
echo "EFI_CERT_PATH: " . ($certPath ?? '❌ NÃO CONFIGURADO') . "\n";
echo "EFI_CERT_EXISTS: " . ($certPath && file_exists($certPath) ? '✅ Sim' : '❌ Não') . "\n";
echo "EFI_PIX_KEY: " . ($pixKey ? '✅ Configurado' : '❌ NÃO CONFIGURADO') . "\n";
echo '</pre>';
echo '</div>';

// 3. Verificar URLs configuradas no código
echo '<div class="section">';
echo '<h2>3. URLs Configuradas no Código</h2>';

// Instanciar service para verificar URLs
try {
    $efiService = new EfiPaymentService();
    
    // Usar reflection para acessar propriedades privadas
    $reflection = new ReflectionClass($efiService);
    
    $baseUrlCharges = $reflection->getProperty('baseUrlCharges');
    $baseUrlCharges->setAccessible(true);
    $baseUrlChargesValue = $baseUrlCharges->getValue($efiService);
    
    $oauthUrlCharges = $reflection->getProperty('oauthUrlCharges');
    $oauthUrlCharges->setAccessible(true);
    $oauthUrlChargesValue = $oauthUrlCharges->getValue($efiService);
    
    $baseUrlPix = $reflection->getProperty('baseUrlPix');
    $baseUrlPix->setAccessible(true);
    $baseUrlPixValue = $baseUrlPix->getValue($efiService);
    
    $oauthUrlPix = $reflection->getProperty('oauthUrlPix');
    $oauthUrlPix->setAccessible(true);
    $oauthUrlPixValue = $oauthUrlPix->getValue($efiService);
    
    echo '<pre>';
    echo "API Cobranças:\n";
    echo "  OAuth: {$oauthUrlChargesValue}/oauth/token\n";
    echo "  Base: {$baseUrlChargesValue}\n\n";
    
    echo "API Pix:\n";
    echo "  OAuth: {$oauthUrlPixValue}/oauth/token\n";
    echo "  Base: {$baseUrlPixValue}\n";
    
    // Verificar se Pix NÃO usa apis.gerencianet.com.br
    if (strpos($baseUrlPixValue, 'apis.gerencianet.com.br') !== false) {
        echo "\n❌ ERRO: API Pix está usando apis.gerencianet.com.br (INCORRETO!)\n";
    } else {
        echo "\n✅ API Pix usa pix.api.efipay.com.br (CORRETO)\n";
    }
    echo '</pre>';
} catch (Exception $e) {
    echo '<p class="error">❌ Erro ao verificar URLs: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// 4. Testar OAuth Pix
echo '<div class="section">';
echo '<h2>4. Teste OAuth Pix</h2>';

if (!$clientId || !$clientSecret) {
    echo '<p class="error">❌ Credenciais não configuradas. Não é possível testar OAuth.</p>';
} else {
    $oauthUrlPix = $sandbox ? 'https://pix-h.api.efipay.com.br' : 'https://pix.api.efipay.com.br';
    $url = $oauthUrlPix . '/oauth/token';
    
    echo '<pre>';
    echo "URL: {$url}\n";
    echo "Método: POST\n";
    echo "Content-Type: application/x-www-form-urlencoded\n";
    echo "Authorization: Basic " . base64_encode($clientId . ':' . $clientSecret) . "\n\n";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['grant_type' => 'client_credentials']));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Adicionar certificado se configurado
    if ($certPath && file_exists($certPath)) {
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
        curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
        if ($certPassword) {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
        }
        echo "✅ Certificado configurado\n";
    } else {
        echo "⚠️ Certificado não configurado\n";
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        echo "❌ Erro cURL: {$curlError}\n";
    } else {
        echo "HTTP Code: {$httpCode}\n";
        $data = json_decode($response, true);
        
        if ($httpCode === 200 && isset($data['access_token'])) {
            $token = $data['access_token'];
            echo "✅ OAuth Pix bem-sucedido!\n";
            echo "Token (preview): " . substr($token, 0, 20) . '...' . substr($token, -10) . "\n";
            echo "Token length: " . strlen($token) . " caracteres\n";
            echo "Token type: " . ($data['token_type'] ?? 'N/A') . "\n";
            echo "Expires in: " . ($data['expires_in'] ?? 'N/A') . " segundos\n";
            
            // Verificar formato do token
            if (preg_match('/[^\x20-\x7E]/', $token)) {
                echo "⚠️ Token contém caracteres não-ASCII\n";
            } else {
                echo "✅ Token contém apenas caracteres ASCII válidos\n";
            }
            
            // Testar header Authorization
            echo "\n--- Teste Header Authorization ---\n";
            $authHeader = 'Authorization: Bearer ' . $token;
            echo "Header completo: {$authHeader}\n";
            echo "Header length: " . strlen($authHeader) . " caracteres\n";
            
            // Verificar se há problemas no header
            if (strpos($authHeader, '=') !== false) {
                echo "⚠️ AVISO: Header contém '=' (pode causar erro)\n";
            }
            if (preg_match('/[^\x20-\x7E]/', $authHeader)) {
                echo "⚠️ AVISO: Header contém caracteres não-ASCII\n";
            }
            
        } else {
            echo "❌ OAuth Pix falhou\n";
            echo "Resposta: " . substr($response, 0, 500) . "\n";
        }
    }
    echo '</pre>';
}
echo '</div>';

// 5. Testar criação de cobrança Pix (simulado)
echo '<div class="section">';
echo '<h2>5. Teste Criação Cobrança Pix (Simulado)</h2>';

if (!$pixKey) {
    echo '<p class="error">❌ EFI_PIX_KEY não configurada. Não é possível testar criação de cobrança Pix.</p>';
} else {
    echo '<pre>';
    echo "Chave PIX: {$pixKey}\n\n";
    
    // Simular payload
    $payload = [
        'calendario' => ['expiracao' => 3600],
        'valor' => ['original' => '10.00'],
        'chave' => $pixKey,
        'solicitacaoPagador' => 'Teste Diagnóstico'
    ];
    
    echo "Payload (exemplo):\n";
    echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n";
    
    echo "Endpoint: /v2/cob\n";
    echo "Base URL: " . ($sandbox ? 'https://pix-h.api.efipay.com.br' : 'https://pix.api.efipay.com.br') . "\n";
    echo "URL completa: " . ($sandbox ? 'https://pix-h.api.efipay.com.br' : 'https://pix.api.efipay.com.br') . "/v2/cob\n";
    
    // Verificar se URL está correta
    $expectedUrl = $sandbox ? 'https://pix-h.api.efipay.com.br' : 'https://pix.api.efipay.com.br';
    if (strpos($expectedUrl, 'apis.gerencianet.com.br') === false) {
        echo "✅ URL correta (NÃO usa apis.gerencianet.com.br)\n";
    } else {
        echo "❌ URL INCORRETA (usa apis.gerencianet.com.br)\n";
    }
    echo '</pre>';
}
echo '</div>';

// 6. Verificar matrícula de teste
echo '<div class="section">';
echo '<h2>6. Verificar Matrícula de Teste</h2>';

try {
    $db = \App\Config\Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT id, payment_method, installments, gateway_charge_id, gateway_last_status, billing_status FROM enrollments ORDER BY id DESC LIMIT 5");
    $enrollments = $stmt->fetchAll();
    
    if (empty($enrollments)) {
        echo '<p>Nenhuma matrícula encontrada.</p>';
    } else {
        echo '<pre>';
        echo "Últimas 5 matrículas:\n\n";
        foreach ($enrollments as $enrollment) {
            echo "ID: {$enrollment['id']}\n";
            echo "  payment_method: " . ($enrollment['payment_method'] ?? 'NULL') . "\n";
            echo "  installments: " . ($enrollment['installments'] ?? 'NULL') . "\n";
            echo "  gateway_charge_id: " . ($enrollment['gateway_charge_id'] ?? 'NULL') . "\n";
            echo "  gateway_last_status: " . ($enrollment['gateway_last_status'] ?? 'NULL') . "\n";
            echo "  billing_status: " . ($enrollment['billing_status'] ?? 'NULL') . "\n";
            
            // Verificar se seria detectado como PIX
            $paymentMethod = $enrollment['payment_method'] ?? 'pix';
            $installments = intval($enrollment['installments'] ?? 1);
            $isPix = ($paymentMethod === 'pix' && $installments === 1);
            echo "  Seria detectado como PIX: " . ($isPix ? '✅ SIM' : '❌ NÃO') . "\n";
            echo "\n";
        }
        echo '</pre>';
    }
} catch (Exception $e) {
    echo '<p class="error">❌ Erro ao consultar matrículas: ' . $e->getMessage() . '</p>';
}
echo '</div>';

// 7. Recomendações
echo '<div class="section warning">';
echo '<h2>7. Recomendações</h2>';
echo '<ul>';
echo '<li>Se o código não está atualizado, execute: <code>git pull origin master</code></li>';
echo '<li>Se EFI_PIX_KEY não está configurada, adicione no .env</li>';
echo '<li>Verifique se o certificado está correto e acessível</li>';
echo '<li>Confirme que EFI_SANDBOX=false em produção</li>';
echo '<li>Verifique os logs do PHP para mais detalhes do erro</li>';
echo '</ul>';
echo '</div>';

?>

</body>
</html>
