<?php
/**
 * Teste Específico - Certificado em Produção
 * 
 * Este script testa especificamente o certificado em produção,
 * já que as credenciais estão corretas mas a autenticação falha.
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
    <title>Teste Certificado - Produção EFI</title>
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
        <h1>🔐 Teste Certificado - Produção EFI</h1>
        <p>Teste específico do certificado em produção, já que as credenciais estão corretas.</p>
    </div>
    
    <?php
    // Verificar configuração
    if (empty($clientId) || empty($clientSecret)) {
        echo '<div class="container">';
        echo '<div class="test-result error">';
        echo '<strong>❌ Credenciais não configuradas</strong>';
        echo '</div>';
        echo '</div>';
        exit;
    }
    
    if (empty($certPath)) {
        echo '<div class="container">';
        echo '<div class="test-result error">';
        echo '<strong>❌ Certificado não configurado</strong><br>';
        echo 'EFI_CERT_PATH não está configurado no arquivo .env';
        echo '</div>';
        echo '</div>';
        exit;
    }
    
    if (!file_exists($certPath)) {
        echo '<div class="container">';
        echo '<div class="test-result error">';
        echo '<strong>❌ Certificado não encontrado</strong><br>';
        echo 'Arquivo não existe: <code>' . htmlspecialchars($certPath) . '</code>';
        echo '</div>';
        echo '</div>';
        exit;
    }
    
    // Informações do certificado
    echo '<div class="container">';
    echo '<h2>📋 Informações do Certificado</h2>';
    
    $fileSize = filesize($certPath);
    $extension = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
    $isP12 = in_array($extension, ['p12', 'pfx']);
    
    echo '<div class="info-item">';
    echo '<strong>Caminho:</strong> <code>' . htmlspecialchars($certPath) . '</code><br>';
    echo '<strong>Tamanho:</strong> ' . number_format($fileSize) . ' bytes (' . number_format($fileSize / 1024, 2) . ' KB)<br>';
    echo '<strong>Extensão:</strong> .' . $extension . ' ' . ($isP12 ? '✅' : '⚠️') . '<br>';
    echo '<strong>Senha configurada:</strong> ' . (!empty($certPassword) ? 'SIM (' . strlen($certPassword) . ' caracteres)' : 'NÃO') . '<br>';
    echo '</div>';
    echo '</div>';
    
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
    
    // Teste 1: Sem certificado (deve falhar em produção)
    echo '<div class="container">';
    echo '<h2>🧪 Teste 1: Sem Certificado</h2>';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode($clientIdClean . ':' . $clientSecretClean)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode === 200) {
        echo '<div class="test-result success">';
        echo '<strong>✅ Funcionou sem certificado</strong><br>';
        echo 'Isso é incomum em produção. O certificado pode não ser obrigatório.';
        echo '</div>';
    } else {
        echo '<div class="test-result warning">';
        echo '<strong>⚠️ Falhou sem certificado (esperado em produção)</strong><br>';
        echo 'HTTP Code: ' . $httpCode . '<br>';
        if ($curlError) {
            echo 'Erro cURL: ' . htmlspecialchars($curlError) . '<br>';
        }
        if ($response) {
            $errorData = json_decode($response, true);
            echo 'Resposta: ' . htmlspecialchars($errorData['error_description'] ?? $errorData['error'] ?? 'Erro desconhecido');
        }
        echo '</div>';
    }
    echo '</div>';
    
    // Teste 2: Com certificado (sem senha)
    echo '<div class="container">';
    echo '<h2>🧪 Teste 2: Com Certificado (sem senha)</h2>';
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded',
        'Authorization: Basic ' . base64_encode($clientIdClean . ':' . $clientSecretClean)
    ]);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    // Configurar certificado SEM senha
    curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
    curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
    curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
    curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
    curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
    
    // Habilitar verbose para debug
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
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            echo '<div class="test-result success">';
            echo '<strong>✅ Funcionou com certificado (sem senha)</strong><br>';
            echo 'Token obtido com sucesso! O certificado não tem senha ou a senha está vazia.';
            echo '</div>';
        } else {
            echo '<div class="test-result error">';
            echo '<strong>❌ HTTP 200 mas sem access_token</strong><br>';
            echo 'Resposta: <code>' . htmlspecialchars(substr($response, 0, 200)) . '</code>';
            echo '</div>';
        }
    } else {
        echo '<div class="test-result error">';
        echo '<strong>❌ Falhou com certificado (sem senha)</strong><br>';
        echo 'HTTP Code: ' . $httpCode . '<br>';
        if ($curlError) {
            echo 'Erro cURL: <code>' . htmlspecialchars($curlError) . '</code><br>';
        }
        if ($response) {
            $errorData = json_decode($response, true);
            echo 'Resposta: <code>' . htmlspecialchars($errorData['error_description'] ?? $errorData['error'] ?? 'Erro desconhecido') . '</code><br>';
        }
        if ($curlError && (strpos($curlError, 'bad decrypt') !== false || strpos($curlError, 'password') !== false)) {
            echo '<br><strong>💡 DICA:</strong> O certificado provavelmente tem senha. Configure EFI_CERT_PASSWORD no .env';
        }
        echo '</div>';
    }
    echo '</div>';
    
    // Teste 3: Com certificado (com senha, se configurada)
    if (!empty($certPassword)) {
        echo '<div class="container">';
        echo '<h2>🧪 Teste 3: Com Certificado (com senha configurada)</h2>';
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($clientIdClean . ':' . $clientSecretClean)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // Configurar certificado COM senha
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
        curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
        curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
        curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
        curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
        
        // Habilitar verbose
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
        
        if ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                echo '<div class="test-result success">';
                echo '<strong>✅ Funcionou com certificado (com senha)</strong><br>';
                echo 'Token obtido com sucesso! A senha do certificado está correta.';
                echo '</div>';
            } else {
                echo '<div class="test-result error">';
                echo '<strong>❌ HTTP 200 mas sem access_token</strong><br>';
                echo 'Resposta: <code>' . htmlspecialchars(substr($response, 0, 200)) . '</code>';
                echo '</div>';
            }
        } else {
            echo '<div class="test-result error">';
            echo '<strong>❌ Falhou com certificado (com senha)</strong><br>';
            echo 'HTTP Code: ' . $httpCode . '<br>';
            if ($curlError) {
                echo 'Erro cURL: <code>' . htmlspecialchars($curlError) . '</code><br>';
                if (strpos($curlError, 'bad decrypt') !== false || strpos($curlError, 'password') !== false) {
                    echo '<br><strong>⚠️ PROBLEMA:</strong> A senha do certificado pode estar incorreta!<br>';
                    echo 'Verifique se EFI_CERT_PASSWORD está correto no arquivo .env';
                }
            }
            if ($response) {
                $errorData = json_decode($response, true);
                echo 'Resposta: <code>' . htmlspecialchars($errorData['error_description'] ?? $errorData['error'] ?? 'Erro desconhecido') . '</code><br>';
            }
            echo '</div>';
        }
        echo '</div>';
    } else {
        echo '<div class="container">';
        echo '<h2>🧪 Teste 3: Com Certificado (com senha)</h2>';
        echo '<div class="test-result warning">';
        echo '<strong>⏭️ Teste pulado</strong><br>';
        echo 'EFI_CERT_PASSWORD não está configurado. Se o certificado tiver senha, configure esta variável no .env';
        echo '</div>';
        echo '</div>';
    }
    
    // Análise final
    echo '<div class="container">';
    echo '<h2>📊 Análise e Recomendações</h2>';
    
    echo '<div class="info-item">';
    echo '<strong>Possíveis problemas e soluções:</strong><br><br>';
    
    echo '<strong>1. Certificado com senha incorreta:</strong><br>';
    echo 'Se o teste 2 falhou mas o certificado tem senha, verifique:<br>';
    echo '- A senha configurada em EFI_CERT_PASSWORD está correta?<br>';
    echo '- A senha foi definida quando você baixou o certificado?<br>';
    echo '- Tente baixar o certificado novamente da dashboard EFI<br><br>';
    
    echo '<strong>2. Certificado do ambiente errado:</strong><br>';
    echo 'Certifique-se de que o certificado é de PRODUÇÃO, não de HOMOLOGAÇÃO/SANDBOX<br><br>';
    
    echo '<strong>3. Certificado corrompido:</strong><br>';
    echo 'Se todos os testes falharem, o certificado pode estar corrompido:<br>';
    echo '- Baixe o certificado novamente da dashboard EFI<br>';
    echo '- Certifique-se de que o download foi completo<br><br>';
    
    echo '<strong>4. Formato do certificado:</strong><br>';
    echo 'O certificado deve estar no formato .p12 ou .pfx<br>';
    echo 'Extensão atual: .' . $extension . ($isP12 ? ' ✅' : ' ⚠️') . '<br><br>';
    
    echo '<strong>5. Como obter o certificado correto:</strong><br>';
    echo '<ol>';
    echo '<li>Acesse: <a href="https://dev.gerencianet.com.br/" target="_blank">https://dev.gerencianet.com.br/</a></li>';
    echo '<li>Faça login</li>';
    echo '<li>Vá em: <strong>API → Meus Certificados</strong></li>';
    echo '<li>Selecione ambiente: <strong>PRODUÇÃO</strong></li>';
    echo '<li>Baixe o certificado (.p12)</li>';
    echo '<li>Salve em local seguro (ex: C:\\xampp\\htdocs\\cfc-v.1\\certificados\\)</li>';
    echo '<li>Configure EFI_CERT_PATH no .env com o caminho completo</li>';
    echo '<li>Se o certificado tiver senha, configure EFI_CERT_PASSWORD</li>';
    echo '</ol>';
    
    echo '</div>';
    echo '</div>';
    ?>
    
    <div class="container">
        <h2>🔬 Teste Detalhado (Recomendado)</h2>
        <p>Para uma análise mais profunda, execute o teste detalhado que verifica se o certificado tem senha e testa diferentes configurações:</p>
        <p>
            <a href="teste_certificado_detalhado.php" class="btn">🔍 Executar Teste Detalhado</a>
        </p>
    </div>
    
    <div class="container">
        <p>
            <a href="diagnostico_certificado.php" class="btn">← Diagnóstico Completo do Certificado</a>
            <a href="validar_integracao_efi.php" class="btn">← Validação Completa</a>
        </p>
    </div>
</body>
</html>
