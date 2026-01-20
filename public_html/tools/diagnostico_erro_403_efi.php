<?php
/**
 * Diagnóstico - Erro 403 EFI "Invalid key=value pair"
 * 
 * Este script diagnostica o problema específico do erro 403 relacionado ao header Authorization
 */

require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/autoload.php';

use App\Config\Env;
use App\Services\EfiPaymentService;

Env::load();

$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;

?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico - Erro 403 EFI</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 900px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 2px solid #4CAF50; padding-bottom: 10px; }
        .section { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4CAF50; }
        .error { border-left-color: #f44336; background: #ffebee; }
        .warning { border-left-color: #ff9800; background: #fff3e0; }
        .success { border-left-color: #4CAF50; background: #e8f5e9; }
        .info { border-left-color: #2196F3; background: #e3f2fd; }
        code { background: #f5f5f5; padding: 2px 6px; border-radius: 3px; font-family: 'Courier New', monospace; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { display: inline-block; padding: 10px 20px; background: #4CAF50; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
        .btn:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico - Erro 403 EFI</h1>
        <p>Erro: "Invalid key=value pair (missing equal-sign) in Authorization header"</p>
        
        <?php
        // 1. Verificar configuração
        echo '<div class="section">';
        echo '<h2>1. Configuração Atual</h2>';
        echo '<p><strong>EFI_CLIENT_ID:</strong> ' . (empty($clientId) ? '❌ Não configurado' : '✅ Configurado (' . substr($clientId, 0, 20) . '...)') . '</p>';
        echo '<p><strong>EFI_CLIENT_SECRET:</strong> ' . (empty($clientSecret) ? '❌ Não configurado' : '✅ Configurado') . '</p>';
        echo '<p><strong>EFI_SANDBOX:</strong> ' . ($sandbox ? '✅ true (SANDBOX)' : '✅ false (PRODUÇÃO)') . '</p>';
        echo '<p><strong>EFI_CERT_PATH:</strong> ' . (empty($certPath) ? '❌ Não configurado' : (file_exists($certPath) ? '✅ Existe: ' . $certPath : '❌ Arquivo não encontrado: ' . $certPath)) . '</p>';
        echo '<p><strong>EFI_CERT_PASSWORD:</strong> ' . (empty($certPassword) ? '⚠️ Não configurado' : '✅ Configurado') . '</p>';
        echo '</div>';
        
        // 2. Testar autenticação OAuth
        echo '<div class="section">';
        echo '<h2>2. Teste de Autenticação OAuth</h2>';
        
        if (empty($clientId) || empty($clientSecret)) {
            echo '<p class="error">❌ Credenciais não configuradas. Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET primeiro.</p>';
        } else {
            $oauthUrl = $sandbox 
                ? 'https://sandbox.gerencianet.com.br/oauth/token'
                : 'https://apis.gerencianet.com.br/oauth/token';
            
            $ch = curl_init($oauthUrl);
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
            
            // Configurar certificado se existir
            if ($certPath && file_exists($certPath)) {
                curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
                curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
                curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
                curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
                if ($certPassword) {
                    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
                    curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
                }
                echo '<p>✅ Certificado configurado para OAuth</p>';
            } elseif (!$sandbox) {
                echo '<p class="warning">⚠️ Produção sem certificado - pode causar problemas</p>';
            }
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($curlError) {
                echo '<p class="error">❌ Erro cURL: ' . htmlspecialchars($curlError) . '</p>';
            } elseif ($httpCode === 200) {
                $data = json_decode($response, true);
                if (isset($data['access_token'])) {
                    $token = $data['access_token'];
                    echo '<p class="success">✅ Autenticação OAuth bem-sucedida!</p>';
                    echo '<p><strong>Token:</strong> ' . substr($token, 0, 30) . '... (tamanho: ' . strlen($token) . ' caracteres)</p>';
                    echo '<p><strong>Token Type:</strong> ' . ($data['token_type'] ?? 'N/A') . '</p>';
                    echo '<p><strong>Expires In:</strong> ' . ($data['expires_in'] ?? 'N/A') . ' segundos</p>';
                    
                    // 3. Testar requisição com token
                    echo '</div>';
                    echo '<div class="section">';
                    echo '<h2>3. Teste de Requisição API com Token</h2>';
                    
                    $baseUrl = $sandbox 
                        ? 'https://sandbox.gerencianet.com.br/v1'
                        : 'https://apis.gerencianet.com.br/v1';
                    
                    // Fazer uma requisição simples para testar
                    $testUrl = $baseUrl . '/charges?limit=1';
                    $ch2 = curl_init($testUrl);
                    curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch2, CURLOPT_HTTPHEADER, [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $token
                    ]);
                    curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch2, CURLOPT_SSL_VERIFYHOST, 2);
                    curl_setopt($ch2, CURLOPT_TIMEOUT, 30);
                    
                    // IMPORTANTE: Certificado também deve ser usado nas requisições da API
                    if ($certPath && file_exists($certPath)) {
                        curl_setopt($ch2, CURLOPT_SSLCERT, $certPath);
                        curl_setopt($ch2, CURLOPT_SSLCERTTYPE, 'P12');
                        curl_setopt($ch2, CURLOPT_SSLKEY, $certPath);
                        curl_setopt($ch2, CURLOPT_SSLKEYTYPE, 'P12');
                        if ($certPassword) {
                            curl_setopt($ch2, CURLOPT_SSLCERTPASSWD, $certPassword);
                            curl_setopt($ch2, CURLOPT_SSLKEYPASSWD, $certPassword);
                        }
                        echo '<p>✅ Certificado configurado para requisição API</p>';
                    } elseif (!$sandbox) {
                        echo '<p class="error">❌ PRODUÇÃO SEM CERTIFICADO - Este é provavelmente o problema!</p>';
                        echo '<p>A EFI exige certificado cliente em produção para TODAS as requisições, não apenas OAuth.</p>';
                    }
                    
                    $response2 = curl_exec($ch2);
                    $httpCode2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                    $curlError2 = curl_error($ch2);
                    curl_close($ch2);
                    
                    if ($curlError2) {
                        echo '<p class="error">❌ Erro cURL na requisição API: ' . htmlspecialchars($curlError2) . '</p>';
                    } else {
                        echo '<p><strong>HTTP Code:</strong> ' . $httpCode2 . '</p>';
                        if ($httpCode2 === 200) {
                            echo '<p class="success">✅ Requisição API bem-sucedida!</p>';
                        } elseif ($httpCode2 === 403) {
                            echo '<p class="error">❌ HTTP 403 - Acesso negado</p>';
                            $errorData = json_decode($response2, true);
                            if ($errorData) {
                                echo '<pre>' . htmlspecialchars(json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) . '</pre>';
                            } else {
                                echo '<pre>' . htmlspecialchars(substr($response2, 0, 500)) . '</pre>';
                            }
                            
                            if (!$sandbox && (!$certPath || !file_exists($certPath))) {
                                echo '<div class="section error">';
                                echo '<h3>🔴 PROBLEMA IDENTIFICADO</h3>';
                                echo '<p>Você está em <strong>PRODUÇÃO</strong> mas o certificado não está configurado ou não existe.</p>';
                                echo '<p>A EFI exige certificado cliente (.p12) em produção para TODAS as requisições da API.</p>';
                                echo '<p><strong>Solução:</strong></p>';
                                echo '<ol>';
                                echo '<li>Obtenha o certificado em: <a href="https://dev.gerencianet.com.br/" target="_blank">https://dev.gerencianet.com.br/</a> → API → Meus Certificados → Produção</li>';
                                echo '<li>Salve o certificado em um local seguro</li>';
                                echo '<li>Configure <code>EFI_CERT_PATH</code> no arquivo <code>.env</code> com o caminho absoluto</li>';
                                echo '<li>Se o certificado tiver senha, configure <code>EFI_CERT_PASSWORD</code></li>';
                                echo '<li>Reinicie o servidor web</li>';
                                echo '</ol>';
                                echo '</div>';
                            }
                        } else {
                            echo '<p class="warning">⚠️ HTTP ' . $httpCode2 . '</p>';
                            echo '<pre>' . htmlspecialchars(substr($response2, 0, 500)) . '</pre>';
                        }
                    }
                } else {
                    echo '<p class="error">❌ access_token não encontrado na resposta</p>';
                    echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
                }
            } else {
                echo '<p class="error">❌ HTTP ' . $httpCode . '</p>';
                echo '<pre>' . htmlspecialchars(substr($response, 0, 500)) . '</pre>';
            }
        }
        echo '</div>';
        
        // 4. Recomendações
        echo '<div class="section info">';
        echo '<h2>4. Recomendações</h2>';
        echo '<ul>';
        echo '<li><strong>Produção:</strong> Certificado cliente (.p12) é OBRIGATÓRIO para todas as requisições</li>';
        echo '<li><strong>Sandbox:</strong> Certificado geralmente não é necessário</li>';
        echo '<li><strong>Certificado:</strong> Deve ser o mesmo usado no OAuth e nas requisições da API</li>';
        echo '<li><strong>URLs:</strong> Verifique se está usando as URLs corretas (apis.gerencianet.com.br para produção)</li>';
        echo '<li><strong>Credenciais:</strong> Use credenciais de PRODUÇÃO quando EFI_SANDBOX=false</li>';
        echo '</ul>';
        echo '</div>';
        ?>
        
        <div style="margin-top: 20px;">
            <a href="validar_integracao_efi.php" class="btn">← Voltar para Validação Completa</a>
        </div>
    </div>
</body>
</html>
