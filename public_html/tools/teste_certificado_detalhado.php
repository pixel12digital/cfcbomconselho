<?php
/**
 * Teste Detalhado - Certificado EFI
 * 
 * Este script testa diferentes formas de usar o certificado
 * e verifica se o certificado tem senha.
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
    <title>Teste Detalhado - Certificado EFI</title>
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
        .info-item {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 4px;
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
        <h1>🔍 Teste Detalhado - Certificado EFI</h1>
        <p>Teste avançado do certificado com diferentes configurações.</p>
    </div>
    
    <?php
    // Verificar configuração detalhadamente
    $missing = [];
    if (empty($clientId)) {
        $missing[] = 'EFI_CLIENT_ID';
    }
    if (empty($clientSecret)) {
        $missing[] = 'EFI_CLIENT_SECRET';
    }
    if (empty($certPath)) {
        $missing[] = 'EFI_CERT_PATH';
    } elseif (!file_exists($certPath)) {
        $missing[] = 'Certificado não encontrado em: ' . htmlspecialchars($certPath);
    }
    
    if (!empty($missing)) {
        echo '<div class="container">';
        echo '<div class="test-result error">';
        echo '<strong>❌ Configuração incompleta</strong><br><br>';
        echo '<strong>Itens faltando ou incorretos:</strong><br>';
        echo '<ul>';
        foreach ($missing as $item) {
            echo '<li>' . htmlspecialchars($item) . '</li>';
        }
        echo '</ul>';
        echo '<br><strong>Verifique o arquivo .env:</strong><br>';
        echo '<code>';
        if (empty($clientId)) {
            echo 'EFI_CLIENT_ID=seu_client_id_aqui<br>';
        }
        if (empty($clientSecret)) {
            echo 'EFI_CLIENT_SECRET=seu_client_secret_aqui<br>';
        }
        if (empty($certPath)) {
            echo 'EFI_CERT_PATH=C:\\xampp\\htdocs\\cfc-v.1\\certificados\\certifica.p12<br>';
        } elseif (!file_exists($certPath)) {
            echo 'EFI_CERT_PATH=' . htmlspecialchars($certPath) . '<br>';
            echo '<br>⚠️ O arquivo não existe neste caminho. Verifique se o caminho está correto.';
        }
        echo '</code>';
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
    
    $url = 'https://apis.gerencianet.com.br/oauth/token';
    
    // Verificar se certificado tem senha usando OpenSSL (se disponível)
    echo '<div class="container">';
    echo '<h2>🔐 Verificação de Senha do Certificado</h2>';
    
    $hasPassword = null;
    $opensslAvailable = false;
    
    // Tentar verificar com OpenSSL
    if (function_exists('exec')) {
        $output = [];
        $returnVar = 0;
        
        // Tentar abrir sem senha
        $command = 'openssl pkcs12 -info -in "' . escapeshellarg($certPath) . '" -noout -passin pass: 2>&1';
        @exec($command, $output, $returnVar);
        
        if ($returnVar === 0) {
            $hasPassword = false;
            $opensslAvailable = true;
            echo '<div class="test-result success">';
            echo '<strong>✅ Certificado NÃO tem senha</strong><br>';
            echo 'O certificado pode ser aberto sem senha.';
            echo '</div>';
        } else {
            // Tentar com senha vazia também falha, então provavelmente tem senha
            $hasPassword = true;
            $opensslAvailable = true;
            echo '<div class="test-result warning">';
            echo '<strong>⚠️ Certificado provavelmente TEM senha</strong><br>';
            echo 'O certificado não pode ser aberto sem senha.';
            if (empty($certPassword)) {
                echo '<br><br><strong>⚠️ PROBLEMA ENCONTRADO:</strong> EFI_CERT_PASSWORD não está configurado!<br>';
                echo 'Configure a senha do certificado no arquivo .env:<br>';
                echo '<code>EFI_CERT_PASSWORD=sua_senha_aqui</code>';
            }
            echo '</div>';
        }
    } else {
        echo '<div class="test-result warning">';
        echo '<strong>⚠️ OpenSSL não disponível</strong><br>';
        echo 'Não foi possível verificar se o certificado tem senha automaticamente.';
        echo '</div>';
    }
    echo '</div>';
    
    // Função para testar autenticação com diferentes configurações
    function testAuthWithCert($url, $clientId, $clientSecret, $certPath, $certPassword = null, $description = '') {
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
        
        // Configurar certificado
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
        curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
        
        if ($certPassword !== null) {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
        } else {
            curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
            curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
        }
        
        // Habilitar verbose
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        
        curl_close($ch);
        
        return [
            'description' => $description,
            'http_code' => $httpCode,
            'curl_error' => $curlError,
            'curl_errno' => $curlErrNo,
            'response' => $response,
            'verbose' => $verboseLog,
            'success' => $httpCode === 200 && !$curlError
        ];
    }
    
    // Testar diferentes configurações
    echo '<div class="container">';
    echo '<h2>🧪 Testes com Diferentes Configurações</h2>';
    
    $tests = [];
    
    // Teste 1: Sem senha (string vazia)
    $tests[] = testAuthWithCert($url, $clientIdClean, $clientSecretClean, $certPath, '', 'Teste 1: Certificado sem senha (string vazia)');
    
    // Teste 2: Com senha do .env (se configurada)
    if (!empty($certPassword)) {
        $tests[] = testAuthWithCert($url, $clientIdClean, $clientSecretClean, $certPath, $certPassword, 'Teste 2: Certificado com senha do .env');
    }
    
    // Teste 3: Sem especificar senha (null)
    $tests[] = testAuthWithCert($url, $clientIdClean, $clientSecretClean, $certPath, null, 'Teste 3: Certificado sem especificar senha (null)');
    
    // Exibir resultados
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
            $summary = 'Erro cURL: ' . htmlspecialchars($test['curl_error']);
            if (strpos($test['curl_error'], 'bad decrypt') !== false || strpos($test['curl_error'], 'password') !== false) {
                $summary .= ' → Certificado provavelmente tem senha incorreta';
            }
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
        echo '<p><strong>Resultado:</strong> ' . $summary . '</p>';
        
        if ($test['http_code']) {
            echo '<p><strong>HTTP Code:</strong> ' . $test['http_code'] . '</p>';
        }
        
        if ($test['curl_error']) {
            echo '<p><strong>Erro cURL:</strong> <code>' . htmlspecialchars($test['curl_error']) . '</code></p>';
            if ($test['curl_errno']) {
                echo '<p><strong>CURL Error Number:</strong> ' . $test['curl_errno'] . '</p>';
            }
        }
        
        if ($test['response'] && !$test['success']) {
            echo '<p><strong>Resposta da API:</strong></p>';
            echo '<code>' . htmlspecialchars(substr($test['response'], 0, 500)) . '</code>';
        }
        
        // Mostrar parte do verbose se houver erro
        if ($test['curl_error'] && $test['verbose']) {
            echo '<p><strong>Detalhes técnicos (cURL verbose):</strong></p>';
            echo '<code>' . htmlspecialchars(substr($test['verbose'], 0, 1000)) . '</code>';
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    
    // Análise final
    echo '<div class="container">';
    echo '<h2>📊 Análise Final</h2>';
    
    $successfulTest = null;
    foreach ($tests as $test) {
        if ($test['success']) {
            $successfulTest = $test;
            break;
        }
    }
    
    if ($successfulTest) {
        echo '<div class="test-result success">';
        echo '<h3>✅ Solução Encontrada!</h3>';
        echo '<p>O teste <strong>' . htmlspecialchars($successfulTest['description']) . '</strong> funcionou!</p>';
        echo '<p><strong>Configuração recomendada no .env:</strong></p>';
        if (strpos($successfulTest['description'], 'sem senha') !== false) {
            echo '<code>EFI_CERT_PASSWORD=</code> (vazio ou não configurar)';
        } else {
            echo '<code>EFI_CERT_PASSWORD=sua_senha_aqui</code>';
        }
        echo '</div>';
    } else {
        echo '<div class="test-result error">';
        echo '<h3>❌ Nenhum teste funcionou</h3>';
        echo '<p><strong>Possíveis causas:</strong></p>';
        echo '<ol>';
        
        // Verificar se todos falharam com erro de senha
        $allPasswordErrors = true;
        foreach ($tests as $test) {
            if ($test['curl_error'] && strpos($test['curl_error'], 'bad decrypt') === false && strpos($test['curl_error'], 'password') === false) {
                $allPasswordErrors = false;
                break;
            }
        }
        
        if ($allPasswordErrors && $hasPassword) {
            echo '<li><strong>Certificado tem senha incorreta:</strong><br>';
            echo 'Todos os testes falharam com erro relacionado a senha.<br>';
            echo 'O certificado tem senha, mas a senha configurada está incorreta ou não está configurada.<br>';
            echo '<strong>Solução:</strong> Verifique a senha do certificado na dashboard EFI ou baixe um novo certificado.</li>';
        } else {
            echo '<li><strong>Credenciais podem estar incorretas:</strong><br>';
            echo 'Mesmo com certificado, está retornando erro 401.<br>';
            echo 'Verifique se CLIENT_ID e CLIENT_SECRET estão corretos na dashboard EFI.</li>';
            
            echo '<li><strong>Certificado pode estar incorreto:</strong><br>';
            echo 'O certificado pode ser de outro ambiente ou estar corrompido.<br>';
            echo '<strong>Solução:</strong> Baixe um novo certificado de PRODUÇÃO na dashboard EFI.</li>';
            
            echo '<li><strong>Certificado pode ter senha:</strong><br>';
            if (empty($certPassword)) {
                echo 'EFI_CERT_PASSWORD não está configurado.<br>';
                echo 'Se o certificado tiver senha, configure esta variável no .env.</li>';
            } else {
                echo 'A senha configurada pode estar incorreta.<br>';
                echo 'Verifique a senha do certificado na dashboard EFI.</li>';
            }
        }
        
        echo '</ol>';
        echo '</div>';
        
        echo '<div class="info-item">';
        echo '<strong>Próximos passos:</strong><br><br>';
        echo '<ol>';
        echo '<li>Acesse a dashboard EFI: <a href="https://dev.gerencianet.com.br/" target="_blank">https://dev.gerencianet.com.br/</a></li>';
        echo '<li>Vá em: <strong>API → Meus Certificados</strong></li>';
        echo '<li>Selecione ambiente: <strong>PRODUÇÃO</strong></li>';
        echo '<li>Baixe o certificado novamente</li>';
        echo '<li>Se o certificado tiver senha, anote a senha</li>';
        echo '<li>Atualize o arquivo .env:</li>';
        echo '<ul>';
        echo '<li><code>EFI_CERT_PATH=C:\\xampp\\htdocs\\cfc-v.1\\certificados\\certifica.p12</code></li>';
        if ($hasPassword) {
            echo '<li><code>EFI_CERT_PASSWORD=senha_do_certificado</code></li>';
        }
        echo '</ul>';
        echo '<li>Reinicie o Apache/XAMPP</li>';
        echo '<li>Execute os testes novamente</li>';
        echo '</ol>';
        echo '</div>';
    }
    
    echo '</div>';
    ?>
    
    <div class="container">
        <p>
            <a href="teste_certificado_producao.php" class="btn">← Teste Básico do Certificado</a>
            <a href="validar_integracao_efi.php" class="btn">← Validação Completa</a>
        </p>
    </div>
</body>
</html>
