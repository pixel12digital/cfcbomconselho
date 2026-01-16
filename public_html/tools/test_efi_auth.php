<?php
/**
 * Script de Teste - Autenticação EFI
 * 
 * Uso: Acesse via browser: http://localhost/cfc-v.1/public_html/tools/test_efi_auth.php
 * 
 * Este script testa a configuração e autenticação com a API EFI sem gerar cobranças.
 */

require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/Config/Database.php';

use App\Config\Env;
use App\Config\Database;

// Carregar variáveis de ambiente
Env::load();

// Obter credenciais
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
// OAuth endpoint usa URL diferente (sem /v1)
$oauthUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br'
    : 'https://apis.gerencianet.com.br';
$baseUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br/v1'
    : 'https://apis.gerencianet.com.br/v1';

$results = [];
$hasError = false;

// 1. Verificar se .env existe
$envPath = dirname(__DIR__, 2) . '/.env';
$results[] = [
    'test' => 'Arquivo .env existe',
    'status' => file_exists($envPath) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => file_exists($envPath) ? "Arquivo encontrado: {$envPath}" : "Arquivo não encontrado em: {$envPath}"
];

// 2. Verificar CLIENT_ID
$results[] = [
    'test' => 'EFI_CLIENT_ID configurado',
    'status' => !empty($clientId) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientId) 
        ? "CLIENT_ID encontrado (primeiros 10 caracteres: " . substr($clientId, 0, 10) . "...)" 
        : "CLIENT_ID não encontrado no .env"
];

// 3. Verificar CLIENT_SECRET
$results[] = [
    'test' => 'EFI_CLIENT_SECRET configurado',
    'status' => !empty($clientSecret) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientSecret) 
        ? "CLIENT_SECRET encontrado (primeiros 10 caracteres: " . substr($clientSecret, 0, 10) . "...)" 
        : "CLIENT_SECRET não encontrado no .env"
];

// 4. Verificar ambiente
$results[] = [
    'test' => 'Ambiente configurado',
    'status' => isset($_ENV['EFI_SANDBOX']) ? '✅ PASSOU' : '⚠️ AVISO',
    'details' => "EFI_SANDBOX = " . ($sandbox ? 'true (SANDBOX)' : 'false (PRODUÇÃO)') . " | OAuth URL: {$oauthUrl}/oauth/token | API URL: {$baseUrl}"
];

// 4.5. Verificar certificado (se produção)
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
if (!$sandbox) {
    $results[] = [
        'test' => 'Certificado cliente (produção)',
        'status' => !empty($certPath) && file_exists($certPath) ? '✅ PASSOU' : '⚠️ AVISO',
        'details' => !empty($certPath) && file_exists($certPath) 
            ? "Certificado encontrado: {$certPath}" 
            : "Certificado não configurado. A EFI pode exigir certificado cliente (.p12) em produção. Configure EFI_CERT_PATH no .env"
    ];
}

// 5. Testar autenticação (se credenciais existem)
if (!empty($clientId) && !empty($clientSecret)) {
    $url = $oauthUrl . '/oauth/token';
    
    $payload = [
        'grant_type' => 'client_credentials'
    ];

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
    
    // Verificar se certificado está configurado
    $certPath = $_ENV['EFI_CERT_PATH'] ?? null;
    if ($certPath && file_exists($certPath)) {
        curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
        curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $errorDetails = "Erro de cURL: {$curlError}";
        
        // Mensagens mais específicas
        if (strpos($curlError, 'Connection was reset') !== false || strpos($curlError, 'Recv failure') !== false) {
            $errorDetails .= "\n\n⚠️ Possíveis causas:\n";
            $errorDetails .= "1. Certificado cliente necessário em produção (configure EFI_CERT_PATH no .env)\n";
            $errorDetails .= "2. Firewall bloqueando conexão HTTPS\n";
            $errorDetails .= "3. Problema de rede/conectividade\n";
            $errorDetails .= "4. A EFI pode exigir IP whitelist ou configurações específicas";
            
            if (!$sandbox && empty($certPath)) {
                $errorDetails .= "\n💡 Dica: Em produção, a EFI geralmente exige certificado cliente (.p12).";
            }
        } elseif (strpos($curlError, 'SSL') !== false || strpos($curlError, 'certificate') !== false) {
            $errorDetails .= "\n\n⚠️ Problema com certificado SSL. Verifique EFI_CERT_PATH no .env";
        } elseif (strpos($curlError, 'timeout') !== false) {
            $errorDetails .= "\n\n⚠️ Timeout na conexão. Verifique conectividade com a internet";
        }
        
        $results[] = [
            'test' => 'Teste de autenticação',
            'status' => '❌ FALHOU',
            'details' => $errorDetails
        ];
        $hasError = true;
    } elseif ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido';
        
        $results[] = [
            'test' => 'Teste de autenticação',
            'status' => '❌ FALHOU',
            'details' => "HTTP {$httpCode}: {$errorMessage}"
        ];
        $hasError = true;
    } else {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $results[] = [
                'test' => 'Teste de autenticação',
                'status' => '✅ PASSOU',
                'details' => "Token obtido com sucesso! (primeiros 20 caracteres: " . substr($data['access_token'], 0, 20) . "...)"
            ];
        } else {
            $results[] = [
                'test' => 'Teste de autenticação',
                'status' => '❌ FALHOU',
                'details' => "Resposta não contém access_token. Resposta: " . substr($response, 0, 200)
            ];
            $hasError = true;
        }
    }
} else {
    $results[] = [
        'test' => 'Teste de autenticação',
        'status' => '⏭️ PULADO',
        'details' => 'Credenciais não configuradas. Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET primeiro.'
    ];
    $hasError = true;
}

// Output HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Autenticação EFI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 900px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #023A8D;
            margin-top: 0;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .test-item.passed {
            background: #d4edda;
            border-color: #28a745;
        }
        .test-item.failed {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .test-item.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .test-item.skipped {
            background: #e2e3e5;
            border-color: #6c757d;
        }
        .test-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .test-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .test-details {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            background: #e7f3ff;
            border-left: 4px solid #023A8D;
            border-radius: 4px;
        }
        .summary h2 {
            margin-top: 0;
            color: #023A8D;
        }
        .action-list {
            margin-top: 15px;
        }
        .action-list li {
            margin: 8px 0;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Teste de Autenticação EFI</h1>
        <p>Este script verifica a configuração e testa a autenticação com a API EFI.</p>
        
        <?php foreach ($results as $result): ?>
            <div class="test-item <?= strtolower(str_replace(['✅ ', '❌ ', '⚠️ ', '⏭️ '], '', $result['status'])) ?>">
                <div class="test-name"><?= htmlspecialchars($result['test']) ?></div>
                <div class="test-status"><?= htmlspecialchars($result['status']) ?></div>
                <div class="test-details"><?= htmlspecialchars($result['details']) ?></div>
            </div>
        <?php endforeach; ?>
        
        <div class="summary">
            <h2>📋 Resumo e Próximos Passos</h2>
            
            <?php if ($hasError): ?>
                <p><strong>⚠️ Problemas encontrados:</strong></p>
                <ul class="action-list">
                    <?php if (empty($clientId) || empty($clientSecret)): ?>
                        <li><strong>Credenciais não configuradas:</strong>
                            <ul>
                                <li>Verifique se o arquivo <code>.env</code> existe na raiz do projeto</li>
                                <li>Adicione as variáveis:
                                    <pre>EFI_CLIENT_ID=seu_client_id_aqui
EFI_CLIENT_SECRET=seu_client_secret_aqui
EFI_SANDBOX=false</pre>
                                </li>
                                <li>Reinicie o servidor web após alterar o <code>.env</code></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($clientId) && !empty($clientSecret) && $hasError): ?>
                        <li><strong>Erro na autenticação:</strong>
                            <ul>
                                <li>Verifique se as credenciais estão corretas</li>
                                <li>Verifique se o ambiente (sandbox/produção) corresponde às credenciais</li>
                                <li>Verifique se há problemas de conexão com a internet</li>
                                <li>Consulte os logs do servidor para mais detalhes</li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            <?php else: ?>
                <p><strong>✅ Todos os testes passaram!</strong></p>
                <p>A configuração está correta e a autenticação com a EFI está funcionando.</p>
            <?php endif; ?>
            
            <p style="margin-top: 20px;">
                <a href="/" style="color: #023A8D; text-decoration: none;">← Voltar</a>
            </p>
        </div>
    </div>
</body>
</html>
