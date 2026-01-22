<?php
/**
 * Script Simples para Verificar Certificado EFI
 * Funciona no Windows sem precisar de OpenSSL instalado
 * 
 * Uso: Coloque este arquivo na mesma pasta do certificado e acesse via browser
 *      ou: php verificar_certificado_simples.php
 */

// Se executado via linha de comando
if (php_sapi_name() === 'cli') {
    if ($argc < 2) {
        echo "Uso: php verificar_certificado_simples.php caminho/para/certificado.p12\n";
        echo "Exemplo: php verificar_certificado_simples.php certificados/certificado.p12\n";
        exit(1);
    }
    $certPath = $argv[1];
} else {
    // Se acessado via browser
    $certPath = $_GET['cert'] ?? 'certificados/certificado.p12';
}

if (!file_exists($certPath)) {
    die("❌ ERRO: Arquivo não encontrado: {$certPath}");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificação de Certificado EFI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 800px;
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
        .info {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .info.success {
            background: #d4edda;
            border-color: #28a745;
        }
        .info.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .info.error {
            background: #f8d7da;
            border-color: #dc3545;
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
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Certificado EFI</h1>
        
        <?php
        $fileSize = filesize($certPath);
        $extension = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
        $isP12 = in_array($extension, ['p12', 'pfx']);
        
        // Tentar ler o arquivo como binário para verificar se está corrompido
        $fileContent = file_get_contents($certPath);
        $isValid = strlen($fileContent) > 0;
        
        // Verificar se parece ser um arquivo PKCS#12 (começa com bytes específicos)
        $isPKCS12 = false;
        if (strlen($fileContent) > 4) {
            // PKCS#12 geralmente começa com 0x30 0x82 ou tem estrutura específica
            $firstBytes = unpack('H*', substr($fileContent, 0, 4))[1];
            // Verificação básica - PKCS#12 geralmente começa com 30 82
            if (substr($firstBytes, 0, 4) === '3082' || substr($firstBytes, 0, 4) === '30a0') {
                $isPKCS12 = true;
            }
        }
        ?>
        
        <div class="info success">
            <strong>✅ Arquivo encontrado</strong><br>
            Caminho: <code><?= htmlspecialchars($certPath) ?></code><br>
            Tamanho: <?= number_format($fileSize) ?> bytes (<?= number_format($fileSize / 1024, 2) ?> KB)
        </div>
        
        <div class="info <?= $isP12 ? 'success' : 'warning' ?>">
            <strong><?= $isP12 ? '✅' : '⚠️' ?> Extensão do arquivo</strong><br>
            Extensão: <code>.<?= $extension ?></code><br>
            <?= $isP12 ? 'Extensão correta para certificado EFI (.p12 ou .pfx)' : 'Esperado: .p12 ou .pfx' ?>
        </div>
        
        <div class="info <?= $isValid ? 'success' : 'error' ?>">
            <strong><?= $isValid ? '✅' : '❌' ?> Arquivo válido</strong><br>
            <?= $isValid ? 'Arquivo pode ser lido e não está vazio' : 'Arquivo está vazio ou corrompido' ?>
        </div>
        
        <div class="info <?= $isPKCS12 ? 'success' : 'warning' ?>">
            <strong><?= $isPKCS12 ? '✅' : '⚠️' ?> Formato PKCS#12</strong><br>
            <?= $isPKCS12 
                ? 'Arquivo parece ser um certificado PKCS#12 válido' 
                : 'Não foi possível confirmar se é um certificado PKCS#12 válido. Pode ser necessário verificar com OpenSSL.' ?>
        </div>
        
        <div class="info warning">
            <strong>⚠️ Verificação de Senha</strong><br>
            <strong>Para verificar se o certificado tem senha, você precisa:</strong><br><br>
            
            <strong>Opção 1: Tentar abrir no Windows</strong><br>
            1. Clique duas vezes no arquivo <code><?= htmlspecialchars(basename($certPath)) ?></code><br>
            2. Se pedir senha → <strong>O certificado TEM senha</strong><br>
            3. Se abrir direto → <strong>O certificado NÃO tem senha</strong><br><br>
            
            <strong>Opção 2: Usar OpenSSL (se tiver instalado)</strong><br>
            Execute no terminal:<br>
            <code>openssl pkcs12 -info -in "<?= htmlspecialchars($certPath) ?>" -noout -passin pass:</code><br><br>
            
            Se der erro pedindo senha, o certificado TEM senha.<br>
            Se funcionar, o certificado NÃO tem senha.
        </div>
        
        <div class="summary">
            <h2>📝 Próximos Passos</h2>
            
            <p><strong>1. Verificar se tem senha:</strong></p>
            <ul>
                <li>Tente abrir o arquivo no Windows (duplo clique)</li>
                <li>Se pedir senha, pergunte ao cliente qual é</li>
            </ul>
            
            <p><strong>2. Se o certificado TEM senha:</strong></p>
            <ul>
                <li>Adicione no arquivo <code>.env</code> do servidor:</li>
                <li><code>EFI_CERT_PASSWORD=senha_que_o_cliente_passou</code></li>
            </ul>
            
            <p><strong>3. Se o certificado NÃO tem senha:</strong></p>
            <ul>
                <li>Não precisa adicionar <code>EFI_CERT_PASSWORD</code> no <code>.env</code></li>
                <li>Ou deixe vazio: <code>EFI_CERT_PASSWORD=</code></li>
            </ul>
            
            <p><strong>4. Fazer upload do certificado para o servidor:</strong></p>
            <ul>
                <li>Envie o arquivo <code><?= htmlspecialchars(basename($certPath)) ?></code> para o servidor</li>
                <li>Caminho no servidor: <code>certificados/certificado.p12</code></li>
            </ul>
        </div>
    </div>
</body>
</html>
