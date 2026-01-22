<?php
/**
 * Teste Avançado - Credenciais EFI
 * 
 * Este script testa diferentes combinações de credenciais e ambientes
 * para identificar o problema específico.
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

Env::load();

$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste Avançado - Credenciais EFI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1200px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #023A8D;
            margin-top: 0;
        }
        .test-result {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .test-result.success {
            background: #d4edda;
            border-color: #28a745;
        }
        .test-result.error {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .test-result.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .test-result h3 {
            margin-top: 0;
        }
        .test-result code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            white-space: pre-wrap;
            word-break: break-all;
            font-size: 0.9em;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #023A8D;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #022a6d;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 Teste Avançado - Credenciais EFI</h1>
        <p>Este script testa diferentes combinações para identificar o problema específico.</p>
    </div>
    
    <?php
    if (empty($clientId) || empty($clientSecret)) {
        echo '<div class="container">';
        echo '<div class="test-result error">';
        echo '<strong>❌ Credenciais não configuradas</strong><br>';
        echo 'Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET no arquivo .env';
        echo '</div>';
        echo '</div>';
        exit;
    }
    
    // Limpar credenciais
    $clientIdClean = trim($clientId);
    $clientSecretClean = trim($clientSecret);
    
    // Remover aspas se existirem
    if ((substr($clientIdClean, 0, 1) === '"' && substr($clientIdClean, -1) === '"') || 
        (substr($clientIdClean, 0, 1) === "'" && substr($clientIdClean, -1) === "'")) {
        $clientIdClean = substr($clientIdClean, 1, -1);
    }
    if ((substr($clientSecretClean, 0, 1) === '"' && substr($clientSecretClean, -1) === '"') || 
        (substr($clientSecretClean, 0, 1) === "'" && substr($clientSecretClean, -1) === "'")) {
        $clientSecretClean = substr($clientSecretClean, 1, -1);
    }
    
    // Função para testar autenticação
    function testAuth($url, $clientId, $clientSecret, $certPath = null, $certPassword = null, $description = '') {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // Configurar certificado se fornecido
        if ($certPath && file_exists($certPath)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
            curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
            if ($certPassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
            } else {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        return [
            'description' => $description,
            'url' => $url,
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'response' => $response,
            'success' => $httpCode === 200 && !$curlError
        ];
    }
    
    $tests = [];
    
    // Teste 1: Produção SEM certificado
    $tests[] = testAuth(
        'https://apis.gerencianet.com.br/oauth/token',
        $clientIdClean,
        $clientSecretClean,
        null,
        null,
        'Teste 1: PRODUÇÃO sem certificado'
    );
    
    // Teste 2: Produção COM certificado
    if ($certPath && file_exists($certPath)) {
        $tests[] = testAuth(
            'https://apis.gerencianet.com.br/oauth/token',
            $clientIdClean,
            $clientSecretClean,
            $certPath,
            $certPassword,
            'Teste 2: PRODUÇÃO com certificado'
        );
    }
    
    // Teste 3: Sandbox SEM certificado
    $tests[] = testAuth(
        'https://sandbox.gerencianet.com.br/oauth/token',
        $clientIdClean,
        $clientSecretClean,
        null,
        null,
        'Teste 3: SANDBOX sem certificado'
    );
    
    // Teste 4: Sandbox COM certificado (se disponível)
    if ($certPath && file_exists($certPath)) {
        $tests[] = testAuth(
            'https://sandbox.gerencianet.com.br/oauth/token',
            $clientIdClean,
            $clientSecretClean,
            $certPath,
            $certPassword,
            'Teste 4: SANDBOX com certificado'
        );
    }
    
    // Exibir resultados
    echo '<div class="container">';
    echo '<h2>Resultados dos Testes</h2>';
    
    foreach ($tests as $test) {
        $class = 'error';
        $icon = '❌';
        $summary = '';
        
        if ($test['success']) {
            $class = 'success';
            $icon = '✅';
            $data = json_decode($test['response'], true);
            $summary = 'Token obtido com sucesso!';
        } elseif ($test['curl_error']) {
            $summary = 'Erro de conexão: ' . htmlspecialchars($test['curl_error']);
        } elseif ($test['http_code'] === 401) {
            $errorData = json_decode($test['response'], true);
            $errorMsg = $errorData['error_description'] ?? $errorData['error'] ?? 'Erro desconhecido';
            $summary = 'HTTP 401: ' . htmlspecialchars($errorMsg);
        } else {
            $summary = 'HTTP ' . $test['http_code'];
            if ($test['response']) {
                $errorData = json_decode($test['response'], true);
                if ($errorData) {
                    $summary .= ' - ' . htmlspecialchars($errorData['error_description'] ?? $errorData['error'] ?? 'Erro desconhecido');
                }
            }
        }
        
        echo '<div class="test-result ' . $class . '">';
        echo '<h3>' . $icon . ' ' . htmlspecialchars($test['description']) . '</h3>';
        echo '<p><strong>URL:</strong> <code>' . htmlspecialchars($test['url']) . '</code></p>';
        echo '<p><strong>Resultado:</strong> ' . $summary . '</p>';
        
        if ($test['http_code']) {
            echo '<p><strong>HTTP Code:</strong> ' . $test['http_code'] . '</p>';
        }
        
        if ($test['curl_error']) {
            echo '<p><strong>Erro cURL:</strong> <code>' . htmlspecialchars($test['curl_error']) . '</code></p>';
        }
        
        if ($test['response'] && !$test['success']) {
            echo '<p><strong>Resposta da API:</strong></p>';
            echo '<code>' . htmlspecialchars(substr($test['response'], 0, 500)) . '</code>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    
    // Análise dos resultados
    $productionSuccess = false;
    $sandboxSuccess = false;
    
    foreach ($tests as $test) {
        if ($test['success']) {
            if (strpos($test['url'], 'apis.gerencianet.com.br') !== false) {
                $productionSuccess = true;
            } elseif (strpos($test['url'], 'sandbox.gerencianet.com.br') !== false) {
                $sandboxSuccess = true;
            }
        }
    }
    
    echo '<div class="container">';
    echo '<h2>📊 Análise dos Resultados</h2>';
    
    if ($productionSuccess) {
        echo '<div class="test-result success">';
        echo '<h3>✅ Credenciais funcionam em PRODUÇÃO</h3>';
        echo '<p>Suas credenciais estão corretas para o ambiente de PRODUÇÃO.</p>';
        echo '<p><strong>Configuração recomendada:</strong></p>';
        echo '<code>EFI_SANDBOX=false</code>';
        echo '</div>';
    } elseif ($sandboxSuccess) {
        echo '<div class="test-result warning">';
        echo '<h3>⚠️ Credenciais funcionam apenas em SANDBOX</h3>';
        echo '<p>Suas credenciais são do ambiente SANDBOX, mas você está tentando usar em PRODUÇÃO.</p>';
        echo '<p><strong>Solução:</strong></p>';
        echo '<ol>';
        echo '<li>Obtenha credenciais de PRODUÇÃO na dashboard EFI</li>';
        echo '<li>OU altere <code>EFI_SANDBOX=true</code> no arquivo .env</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div class="test-result error">';
        echo '<h3>❌ Credenciais não funcionam em nenhum ambiente</h3>';
        echo '<p>As credenciais estão incorretas, inativas ou expiradas em ambos os ambientes.</p>';
        echo '<p><strong>Ações necessárias:</strong></p>';
        echo '<ol>';
        echo '<li>Acesse a dashboard EFI: <a href="https://dev.gerencianet.com.br/" target="_blank">https://dev.gerencianet.com.br/</a></li>';
        echo '<li>Vá em: <strong>API → Credenciais</strong></li>';
        echo '<li>Verifique se as credenciais estão ativas</li>';
        echo '<li>Se necessário, gere novas credenciais</li>';
        echo '<li>Copie as credenciais corretas e atualize o arquivo .env</li>';
        echo '</ol>';
        echo '</div>';
    }
    
    // Verificar se certificado é necessário
    $productionWithCert = false;
    $productionWithoutCert = false;
    
    foreach ($tests as $test) {
        if (strpos($test['url'], 'apis.gerencianet.com.br') !== false) {
            if ($test['success']) {
                if (strpos($test['description'], 'com certificado') !== false) {
                    $productionWithCert = true;
                } else {
                    $productionWithoutCert = true;
                }
            }
        }
    }
    
    if ($productionSuccess && !$productionWithoutCert && $productionWithCert) {
        echo '<div class="test-result warning">';
        echo '<h3>⚠️ Certificado é obrigatório em PRODUÇÃO</h3>';
        echo '<p>O teste mostra que o certificado é necessário para autenticação em PRODUÇÃO.</p>';
        echo '<p>Certifique-se de que:</p>';
        echo '<ul>';
        echo '<li><code>EFI_CERT_PATH</code> está configurado corretamente</li>';
        echo '<li>O certificado existe no caminho especificado</li>';
        echo '<li><code>EFI_CERT_PASSWORD</code> está correto (se o certificado tiver senha)</li>';
        echo '</ul>';
        echo '</div>';
    }
    
    echo '</div>';
    ?>
    
    <div class="container">
        <p>
            <a href="diagnostico_credenciais_efi.php" class="btn">← Voltar para Diagnóstico de Credenciais</a>
            <a href="validar_integracao_efi.php" class="btn">← Voltar para Validação Completa</a>
        </p>
    </div>
</body>
</html>
