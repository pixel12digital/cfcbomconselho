<?php
/**
 * Script de Diagnóstico - Credenciais EFI
 * 
 * Uso: Acesse via browser: http://localhost/cfc-v.1/public_html/tools/diagnostico_credenciais_efi.php
 * 
 * Este script diagnostica problemas com credenciais EFI.
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;

$oauthUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br/oauth/token'
    : 'https://apis.gerencianet.com.br/oauth/token';

$results = [];
$hasError = false;

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Credenciais EFI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1000px;
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
        .info-box {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .info-box.success {
            background: #d4edda;
            border-color: #28a745;
        }
        .info-box.error {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .info-box.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .info-box code {
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
        .check-item {
            padding: 10px;
            margin: 5px 0;
            background: #f8f9fa;
            border-radius: 4px;
        }
        .check-item strong {
            color: #023A8D;
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
        <h1>🔍 Diagnóstico - Credenciais EFI</h1>
        <p>Este script verifica problemas com as credenciais EFI (CLIENT_ID e CLIENT_SECRET).</p>
    </div>
    
    <div class="container">
        <h2>1. Verificação de Configuração</h2>
        
        <?php
        // Verificar CLIENT_ID
        if (empty($clientId)) {
            echo '<div class="info-box error">';
            echo '<strong>❌ EFI_CLIENT_ID não configurado</strong><br>';
            echo 'A variável EFI_CLIENT_ID não foi encontrada no arquivo .env';
            echo '</div>';
            $hasError = true;
        } else {
            $clientIdTrimmed = trim($clientId);
            $hasSpaces = $clientId !== $clientIdTrimmed;
            $hasQuotes = (substr($clientId, 0, 1) === '"' && substr($clientId, -1) === '"') || 
                         (substr($clientId, 0, 1) === "'" && substr($clientId, -1) === "'");
            
            echo '<div class="info-box ' . ($hasSpaces || $hasQuotes ? 'warning' : 'success') . '">';
            echo '<strong>' . ($hasSpaces || $hasQuotes ? '⚠️' : '✅') . ' EFI_CLIENT_ID configurado</strong><br>';
            echo 'Tamanho: ' . strlen($clientId) . ' caracteres<br>';
            echo 'Primeiros 15 caracteres: <code>' . htmlspecialchars(substr($clientId, 0, 15)) . '...</code><br>';
            echo 'Últimos 10 caracteres: <code>...' . htmlspecialchars(substr($clientId, -10)) . '</code><br>';
            
            if ($hasSpaces) {
                echo '<br><strong>⚠️ PROBLEMA ENCONTRADO:</strong> CLIENT_ID tem espaços no início ou fim!<br>';
                echo 'Remova espaços extras no arquivo .env';
            }
            if ($hasQuotes) {
                echo '<br><strong>⚠️ PROBLEMA ENCONTRADO:</strong> CLIENT_ID está entre aspas!<br>';
                echo 'Remova as aspas do valor no arquivo .env';
            }
            echo '</div>';
        }
        
        // Verificar CLIENT_SECRET
        if (empty($clientSecret)) {
            echo '<div class="info-box error">';
            echo '<strong>❌ EFI_CLIENT_SECRET não configurado</strong><br>';
            echo 'A variável EFI_CLIENT_SECRET não foi encontrada no arquivo .env';
            echo '</div>';
            $hasError = true;
        } else {
            $clientSecretTrimmed = trim($clientSecret);
            $hasSpaces = $clientSecret !== $clientSecretTrimmed;
            $hasQuotes = (substr($clientSecret, 0, 1) === '"' && substr($clientSecret, -1) === '"') || 
                         (substr($clientSecret, 0, 1) === "'" && substr($clientSecret, -1) === "'");
            
            echo '<div class="info-box ' . ($hasSpaces || $hasQuotes ? 'warning' : 'success') . '">';
            echo '<strong>' . ($hasSpaces || $hasQuotes ? '⚠️' : '✅') . ' EFI_CLIENT_SECRET configurado</strong><br>';
            echo 'Tamanho: ' . strlen($clientSecret) . ' caracteres<br>';
            echo 'Primeiros 15 caracteres: <code>' . htmlspecialchars(substr($clientSecret, 0, 15)) . '...</code><br>';
            echo 'Últimos 10 caracteres: <code>...' . htmlspecialchars(substr($clientSecret, -10)) . '</code><br>';
            
            if ($hasSpaces) {
                echo '<br><strong>⚠️ PROBLEMA ENCONTRADO:</strong> CLIENT_SECRET tem espaços no início ou fim!<br>';
                echo 'Remova espaços extras no arquivo .env';
            }
            if ($hasQuotes) {
                echo '<br><strong>⚠️ PROBLEMA ENCONTRADO:</strong> CLIENT_SECRET está entre aspas!<br>';
                echo 'Remova as aspas do valor no arquivo .env';
            }
            echo '</div>';
        }
        
        // Verificar ambiente
        echo '<div class="info-box ' . ($sandbox ? 'warning' : 'success') . '">';
        echo '<strong>' . ($sandbox ? '⚠️' : '✅') . ' Ambiente configurado</strong><br>';
        echo 'EFI_SANDBOX = ' . ($sandbox ? 'true (SANDBOX)' : 'false (PRODUÇÃO)') . '<br>';
        echo 'URL OAuth: <code>' . htmlspecialchars($oauthUrl) . '</code><br>';
        if ($sandbox) {
            echo '<br><strong>⚠️ ATENÇÃO:</strong> Você está usando SANDBOX. Certifique-se de que as credenciais são do ambiente SANDBOX.';
        } else {
            echo '<br><strong>✅ PRODUÇÃO:</strong> Certifique-se de que as credenciais são do ambiente PRODUÇÃO.';
        }
        echo '</div>';
        ?>
    </div>
    
    <?php if (!empty($clientId) && !empty($clientSecret)): ?>
    <div class="container">
        <h2>2. Teste de Autenticação</h2>
        
        <?php
        // Limpar espaços e aspas se existirem
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
        
        echo '<div class="check-item">';
        echo '<strong>Testando autenticação com credenciais limpas...</strong><br>';
        echo 'CLIENT_ID (limpo): ' . strlen($clientIdClean) . ' caracteres<br>';
        echo 'CLIENT_SECRET (limpo): ' . strlen($clientSecretClean) . ' caracteres<br>';
        echo '</div>';
        
        // Testar autenticação
        $ch = curl_init($oauthUrl);
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
        
        // Configurar certificado se disponível
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
        
        if ($curlError) {
            echo '<div class="info-box error">';
            echo '<strong>❌ Erro de conexão</strong><br>';
            echo 'Erro cURL: ' . htmlspecialchars($curlError) . '<br>';
            echo 'Isso pode indicar problema de rede ou certificado.';
            echo '</div>';
        } elseif ($httpCode === 200) {
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                echo '<div class="info-box success">';
                echo '<strong>✅ Autenticação bem-sucedida!</strong><br>';
                echo 'Token obtido com sucesso. As credenciais estão corretas.<br>';
                echo 'Token (primeiros 30 caracteres): <code>' . htmlspecialchars(substr($data['access_token'], 0, 30)) . '...</code>';
                echo '</div>';
            } else {
                echo '<div class="info-box error">';
                echo '<strong>❌ Resposta inesperada</strong><br>';
                echo 'HTTP 200 mas sem access_token. Resposta: <code>' . htmlspecialchars(substr($response, 0, 200)) . '</code>';
                echo '</div>';
            }
        } elseif ($httpCode === 401) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? 'Erro desconhecido';
            
            echo '<div class="info-box error">';
            echo '<strong>❌ Erro 401: Credenciais inválidas ou inativas</strong><br>';
            echo 'Mensagem: <code>' . htmlspecialchars($errorMessage) . '</code><br><br>';
            
            echo '<strong>Possíveis causas:</strong><br>';
            echo '<ol>';
            echo '<li><strong>Credenciais incorretas:</strong> CLIENT_ID ou CLIENT_SECRET estão errados</li>';
            echo '<li><strong>Credenciais do ambiente errado:</strong> Usando credenciais de SANDBOX em PRODUÇÃO ou vice-versa</li>';
            echo '<li><strong>Credenciais inativas:</strong> As credenciais foram desativadas na dashboard EFI</li>';
            echo '<li><strong>Credenciais expiradas:</strong> As credenciais podem ter expirado</li>';
            echo '</ol>';
            
            echo '<strong>Como resolver:</strong><br>';
            echo '<ol>';
            echo '<li>Acesse a dashboard EFI: <a href="https://dev.gerencianet.com.br/" target="_blank">https://dev.gerencianet.com.br/</a></li>';
            echo '<li>Vá em: <strong>API → Credenciais</strong></li>';
            if ($sandbox) {
                echo '<li>Selecione ambiente: <strong>SANDBOX</strong></li>';
            } else {
                echo '<li>Selecione ambiente: <strong>PRODUÇÃO</strong></li>';
            }
            echo '<li>Copie o <strong>Client_Id</strong> e <strong>Client_Secret</strong> corretos</li>';
            echo '<li>Atualize o arquivo <code>.env</code> com as credenciais corretas</li>';
            echo '<li>Certifique-se de que não há espaços extras ou aspas nos valores</li>';
            echo '<li>Reinicie o servidor web</li>';
            echo '</ol>';
            
            echo '<strong>Formato correto no .env:</strong><br>';
            echo '<code>';
            echo 'EFI_CLIENT_ID=Client_Id_xxxxxxxxxxxxxxxxxxxxx<br>';
            echo 'EFI_CLIENT_SECRET=Client_Secret_xxxxxxxxxxxxxxxxxxxxx<br>';
            echo 'EFI_SANDBOX=' . ($sandbox ? 'true' : 'false');
            echo '</code>';
            
            echo '</div>';
        } else {
            echo '<div class="info-box error">';
            echo '<strong>❌ Erro HTTP ' . $httpCode . '</strong><br>';
            echo 'Resposta: <code>' . htmlspecialchars(substr($response, 0, 500)) . '</code>';
            echo '</div>';
        }
        ?>
    </div>
    <?php endif; ?>
    
    <div class="container">
        <h2>3. Checklist de Verificação</h2>
        
        <div class="check-item">
            <strong>✓ Verificar Dashboard EFI</strong><br>
            <ol>
                <li>Acesse: <a href="https://dev.gerencianet.com.br/" target="_blank">https://dev.gerencianet.com.br/</a></li>
                <li>Faça login na sua conta</li>
                <li>Vá em: <strong>API → Credenciais</strong></li>
                <li>Verifique se as credenciais estão ativas</li>
                <li>Confirme se está usando o ambiente correto (SANDBOX ou PRODUÇÃO)</li>
            </ol>
        </div>
        
        <div class="check-item">
            <strong>✓ Verificar Arquivo .env</strong><br>
            <ol>
                <li>Abra o arquivo <code>.env</code> na raiz do projeto</li>
                <li>Verifique se as linhas estão corretas:
                    <code>EFI_CLIENT_ID=valor_sem_aspas_sem_espacos
EFI_CLIENT_SECRET=valor_sem_aspas_sem_espacos
EFI_SANDBOX=false</code>
                </li>
                <li>Certifique-se de que não há espaços antes ou depois do <code>=</code></li>
                <li>Certifique-se de que não há aspas nos valores</li>
                <li>Salve o arquivo</li>
                <li>Reinicie o servidor web (Apache/Nginx)</li>
            </ol>
        </div>
        
        <div class="check-item">
            <strong>✓ Verificar Ambiente</strong><br>
            <ul>
                <li>Se <code>EFI_SANDBOX=true</code> → Use credenciais de SANDBOX</li>
                <li>Se <code>EFI_SANDBOX=false</code> → Use credenciais de PRODUÇÃO</li>
                <li><strong>NÃO misture credenciais de ambientes diferentes!</strong></li>
            </ul>
        </div>
    </div>
    
    <div class="container">
        <h2>4. Teste Específico do Certificado</h2>
        <p>Se as credenciais estão corretas mas ainda falha, o problema pode ser o certificado:</p>
        <p>
            <a href="teste_certificado_producao.php" class="btn">🔐 Testar Certificado em Produção</a>
        </p>
        <p style="font-size: 0.9em; color: #666;">
            Este teste verifica se o certificado está sendo usado corretamente e se a senha está correta.
        </p>
    </div>
    
    <div class="container">
        <h2>5. Teste Avançado (Opcional)</h2>
        <p>Execute o teste avançado que testa diferentes combinações de ambiente e certificado:</p>
        <p>
            <a href="teste_avancado_credenciais.php" class="btn">🧪 Executar Teste Avançado</a>
        </p>
        <p style="font-size: 0.9em; color: #666;">
            O teste avançado verifica se as credenciais funcionam em SANDBOX ou PRODUÇÃO, 
            e se o certificado é necessário.
        </p>
    </div>
    
    <div class="container">
        <p>
            <a href="validar_integracao_efi.php" class="btn">← Voltar para Validação Completa</a>
            <a href="/" style="color: #023A8D; text-decoration: none; margin-left: 10px;">← Voltar ao sistema</a>
        </p>
    </div>
</body>
</html>
