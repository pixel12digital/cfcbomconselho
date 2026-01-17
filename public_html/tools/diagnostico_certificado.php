<?php
/**
 * Script de Diagnóstico - Certificado EFI
 * 
 * Uso: Acesse via browser: https://painel.cfcbomconselho.com.br/tools/diagnostico_certificado.php
 * 
 * Este script verifica detalhadamente a configuração do certificado.
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

// Carregar variáveis de ambiente
Env::load();

$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';

$results = [];
$hasError = false;

// 1. Verificar se EFI_CERT_PATH está configurado
$results[] = [
    'test' => 'EFI_CERT_PATH configurado',
    'status' => !empty($certPath) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($certPath) 
        ? "Caminho configurado: <code>{$certPath}</code>" 
        : "Variável EFI_CERT_PATH não encontrada no .env"
];

if (empty($certPath)) {
    $hasError = true;
}

// 2. Verificar se arquivo existe
if (!empty($certPath)) {
    $fileExists = file_exists($certPath);
    $results[] = [
        'test' => 'Arquivo certificado existe',
        'status' => $fileExists ? '✅ PASSOU' : '❌ FALHOU',
        'details' => $fileExists 
            ? "Arquivo encontrado: <code>{$certPath}</code>" 
            : "Arquivo NÃO encontrado em: <code>{$certPath}</code><br><br>Verifique se o caminho está correto e se o arquivo foi feito upload corretamente."
    ];
    
    if (!$fileExists) {
        $hasError = true;
    }
    
    // 3. Verificar permissões do arquivo
    if ($fileExists) {
        $isReadable = is_readable($certPath);
        $perms = substr(sprintf('%o', fileperms($certPath)), -4);
        $results[] = [
            'test' => 'Permissões do arquivo',
            'status' => $isReadable ? '✅ PASSOU' : '⚠️ AVISO',
            'details' => $isReadable 
                ? "Arquivo legível. Permissões: <code>{$perms}</code> (recomendado: 600 ou 644)" 
                : "Arquivo NÃO é legível pelo servidor web. Permissões atuais: <code>{$perms}</code><br><br>Execute no SSH: <code>chmod 600 {$certPath}</code>"
        ];
        
        if (!$isReadable) {
            $hasError = true;
        }
        
        // 4. Verificar tamanho do arquivo
        $fileSize = filesize($certPath);
        $results[] = [
            'test' => 'Tamanho do arquivo',
            'status' => $fileSize > 0 ? '✅ PASSOU' : '❌ FALHOU',
            'details' => $fileSize > 0 
                ? "Tamanho: " . number_format($fileSize) . " bytes (" . number_format($fileSize / 1024, 2) . " KB)" 
                : "Arquivo está vazio ou corrompido"
        ];
        
        if ($fileSize <= 0) {
            $hasError = true;
        }
        
        // 5. Verificar extensão
        $extension = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
        $results[] = [
            'test' => 'Extensão do arquivo',
            'status' => in_array($extension, ['p12', 'pfx']) ? '✅ PASSOU' : '⚠️ AVISO',
            'details' => in_array($extension, ['p12', 'pfx']) 
                ? "Extensão correta: <code>.{$extension}</code>" 
                : "Extensão: <code>.{$extension}</code> (esperado: .p12 ou .pfx)"
        ];
    }
}

// 6. Verificar credenciais
$results[] = [
    'test' => 'EFI_CLIENT_ID configurado',
    'status' => !empty($clientId) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientId) 
        ? "CLIENT_ID encontrado" 
        : "CLIENT_ID não configurado"
];

$results[] = [
    'test' => 'EFI_CLIENT_SECRET configurado',
    'status' => !empty($clientSecret) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientSecret) 
        ? "CLIENT_SECRET encontrado" 
        : "CLIENT_SECRET não configurado"
];

// 7. Verificar ambiente
$results[] = [
    'test' => 'Ambiente configurado',
    'status' => isset($_ENV['EFI_SANDBOX']) ? '✅ PASSOU' : '⚠️ AVISO',
    'details' => "EFI_SANDBOX = " . ($sandbox ? 'true (SANDBOX)' : 'false (PRODUÇÃO)') . 
        ($sandbox ? '' : '<br><br>⚠️ Em produção, certificado é OBRIGATÓRIO!')
];

// 8. Testar autenticação (se tudo estiver configurado)
if (!empty($clientId) && !empty($clientSecret) && !empty($certPath) && file_exists($certPath)) {
    $oauthUrl = $sandbox 
        ? 'https://sandbox.gerencianet.com.br'
        : 'https://apis.gerencianet.com.br';
    
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
    
    // Usar certificado
    curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
    curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    $curlInfo = curl_getinfo($ch);
    curl_close($ch);

    if ($curlError) {
        $errorDetails = "Erro de cURL: <code>{$curlError}</code>";
        
        if (strpos($curlError, 'SSL') !== false || strpos($curlError, 'certificate') !== false) {
            $errorDetails .= "<br><br>⚠️ <strong>Problema com certificado SSL:</strong><br>";
            $errorDetails .= "1. Verifique se o certificado está correto<br>";
            $errorDetails .= "2. O certificado pode ter senha (não suportado ainda)<br>";
            $errorDetails .= "3. Verifique se o certificado é do ambiente correto (produção vs sandbox)";
        } elseif (strpos($curlError, 'Connection was reset') !== false || strpos($curlError, 'Recv failure') !== false) {
            $errorDetails .= "<br><br>⚠️ <strong>Possíveis causas:</strong><br>";
            $errorDetails .= "1. Certificado pode estar incorreto ou corrompido<br>";
            $errorDetails .= "2. Firewall bloqueando conexão<br>";
            $errorDetails .= "3. A EFI pode exigir configurações adicionais";
        }
        
        $results[] = [
            'test' => 'Teste de autenticação com certificado',
            'status' => '❌ FALHOU',
            'details' => $errorDetails
        ];
        $hasError = true;
    } elseif ($httpCode !== 200) {
        $errorData = json_decode($response, true);
        $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido';
        
        $results[] = [
            'test' => 'Teste de autenticação com certificado',
            'status' => '❌ FALHOU',
            'details' => "HTTP {$httpCode}: <code>{$errorMessage}</code><br><br>Verifique se as credenciais estão corretas."
        ];
        $hasError = true;
    } else {
        $data = json_decode($response, true);
        if (isset($data['access_token'])) {
            $results[] = [
                'test' => 'Teste de autenticação com certificado',
                'status' => '✅ PASSOU',
                'details' => "✅ Autenticação bem-sucedida! Token obtido com sucesso."
            ];
        } else {
            $results[] = [
                'test' => 'Teste de autenticação com certificado',
                'status' => '❌ FALHOU',
                'details' => "Resposta não contém access_token. Resposta: " . htmlspecialchars(substr($response, 0, 200))
            ];
            $hasError = true;
        }
    }
} else {
    $results[] = [
        'test' => 'Teste de autenticação com certificado',
        'status' => '⏭️ PULADO',
        'details' => 'Configuração incompleta. Configure todas as variáveis necessárias primeiro.'
    ];
}

// Output HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico - Certificado EFI</title>
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
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Diagnóstico - Certificado EFI</h1>
        <p>Este script verifica detalhadamente a configuração do certificado para autenticação com a API EFI.</p>
        
        <?php foreach ($results as $result): ?>
            <div class="test-item <?= strtolower(str_replace(['✅ ', '❌ ', '⚠️ ', '⏭️ '], '', $result['status'])) ?>">
                <div class="test-name"><?= htmlspecialchars($result['test']) ?></div>
                <div class="test-status"><?= htmlspecialchars($result['status']) ?></div>
                <div class="test-details"><?= $result['details'] ?></div>
            </div>
        <?php endforeach; ?>
        
        <div class="summary">
            <h2>📋 Resumo e Próximos Passos</h2>
            
            <?php if ($hasError): ?>
                <p><strong>⚠️ Problemas encontrados. Verifique os itens acima.</strong></p>
                
                <h3>🔧 Soluções Comuns:</h3>
                <ul class="action-list">
                    <li><strong>Se o certificado não existe:</strong>
                        <ul>
                            <li>Verifique se o caminho no <code>.env</code> está correto</li>
                            <li>O caminho deve ser absoluto: <code>/home/u502697186/domains/cfcbomconselho.com.br/public_html/painel/certificados/certificado.p12</code></li>
                            <li>Faça upload do certificado via File Manager da Hostinger</li>
                        </ul>
                    </li>
                    <li><strong>Se o arquivo não é legível:</strong>
                        <ul>
                            <li>Conecte via SSH e execute: <code>chmod 600 certificados/certificado.p12</code></li>
                        </ul>
                    </li>
                    <li><strong>Se a autenticação falha:</strong>
                        <ul>
                            <li>Verifique se o certificado é do ambiente correto (produção vs sandbox)</li>
                            <li>Verifique se as credenciais <code>EFI_CLIENT_ID</code> e <code>EFI_CLIENT_SECRET</code> estão corretas</li>
                            <li>Verifique se <code>EFI_SANDBOX=false</code> para produção</li>
                        </ul>
                    </li>
                </ul>
            <?php else: ?>
                <p><strong>✅ Todos os testes passaram!</strong></p>
                <p>A configuração do certificado está correta e a autenticação com a EFI deve funcionar.</p>
            <?php endif; ?>
            
            <p style="margin-top: 20px;">
                <a href="/" style="color: #023A8D; text-decoration: none;">← Voltar</a>
            </p>
        </div>
    </div>
</body>
</html>
