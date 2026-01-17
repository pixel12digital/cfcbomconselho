<?php
/**
 * Script de Verificação - Certificado EFI
 * 
 * Verifica se o certificado .p12 está configurado corretamente
 */

define('ROOT_PATH', dirname(__DIR__));

// Carregar .env
require_once ROOT_PATH . '/app/Config/Env.php';
App\Config\Env::load();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Verificação de Certificado EFI</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
        h1 { color: #333; }
        .test { padding: 10px; margin: 10px 0; border-left: 4px solid #ccc; background: #f9f9f9; }
        .pass { border-left-color: #4CAF50; background: #e8f5e9; }
        .fail { border-left-color: #f44336; background: #ffebee; }
        .warn { border-left-color: #ff9800; background: #fff3e0; }
        .status { font-weight: bold; margin-bottom: 5px; }
        .details { color: #666; font-size: 0.9em; }
        code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Certificado EFI</h1>
        
        <?php
        $certPath = $_ENV['EFI_CERT_PATH'] ?? null;
        $sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';
        $isProduction = !$sandbox;
        
        $tests = [];
        
        // Teste 1: Verificar se EFI_CERT_PATH está configurado
        $tests[] = [
            'name' => 'EFI_CERT_PATH configurado no .env',
            'status' => !empty($certPath) ? 'pass' : 'fail',
            'message' => !empty($certPath) 
                ? "✅ Configurado: <code>{$certPath}</code>" 
                : "❌ Variável EFI_CERT_PATH não encontrada no .env"
        ];
        
        // Teste 2: Verificar se arquivo existe (se path configurado)
        if (!empty($certPath)) {
            $exists = file_exists($certPath);
            $tests[] = [
                'name' => 'Arquivo certificado existe',
                'status' => $exists ? 'pass' : 'fail',
                'message' => $exists 
                    ? "✅ Arquivo encontrado em: <code>{$certPath}</code>" 
                    : "❌ Arquivo NÃO encontrado em: <code>{$certPath}</code>"
            ];
            
            // Teste 3: Verificar extensão
            if ($exists) {
                $extension = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
                $tests[] = [
                    'name' => 'Formato do arquivo',
                    'status' => in_array($extension, ['p12', 'pfx']) ? 'pass' : 'warn',
                    'message' => in_array($extension, ['p12', 'pfx'])
                        ? "✅ Formato correto: <code>.{$extension}</code>"
                        : "⚠️ Formato esperado: <code>.p12</code> ou <code>.pfx</code> (encontrado: <code>.{$extension}</code>)"
                ];
                
                // Teste 4: Verificar permissões (se Linux)
                if (PHP_OS_FAMILY !== 'Windows') {
                    $perms = fileperms($certPath);
                    $permsOctal = substr(sprintf('%o', $perms), -4);
                    $readable = is_readable($certPath);
                    $tests[] = [
                        'name' => 'Permissões do arquivo (Linux)',
                        'status' => $readable ? 'pass' : 'fail',
                        'message' => $readable
                            ? "✅ Arquivo legível (permissões: <code>{$permsOctal}</code>)"
                            : "❌ Arquivo NÃO legível (permissões: <code>{$permsOctal}</code>)<br>Execute: <code>chmod 600 {$certPath}</code>"
                    ];
                }
                
                // Teste 5: Tamanho do arquivo
                $size = filesize($certPath);
                $sizeKB = round($size / 1024, 2);
                $tests[] = [
                    'name' => 'Tamanho do arquivo',
                    'status' => $size > 0 ? 'pass' : 'fail',
                    'message' => $size > 0
                        ? "✅ Tamanho: <code>{$sizeKB} KB</code>"
                        : "❌ Arquivo vazio"
                ];
            }
        }
        
        // Teste 6: Verificar se é obrigatório (produção)
        if ($isProduction) {
            $tests[] = [
                'name' => 'Certificado obrigatório em produção',
                'status' => !empty($certPath) && file_exists($certPath) ? 'pass' : 'fail',
                'message' => !empty($certPath) && file_exists($certPath)
                    ? "✅ Certificado configurado (obrigatório em produção)"
                    : "❌ Certificado é OBRIGATÓRIO em produção (EFI_SANDBOX=false)<br>Obtenha em: https://dev.gerencianet.com.br/ → API → Meus Certificados → Produção"
            ];
        } else {
            $tests[] = [
                'name' => 'Certificado em sandbox',
                'status' => 'warn',
                'message' => "⚠️ Ambiente SANDBOX: certificado geralmente não é necessário"
            ];
        }
        
        // Teste 7: Verificar diretório certificados/
        $certDir = ROOT_PATH . '/certificados';
        $certDirExists = is_dir($certDir);
        $certFiles = $certDirExists ? glob($certDir . '/*.{p12,pfx}', GLOB_BRACE) : [];
        
        $tests[] = [
            'name' => 'Diretório certificados/',
            'status' => $certDirExists ? 'pass' : 'warn',
            'message' => $certDirExists
                ? "✅ Diretório existe: <code>{$certDir}</code><br>" . 
                  (count($certFiles) > 0 
                    ? "Encontrados <code>" . count($certFiles) . "</code> arquivo(s) .p12/.pfx" 
                    : "Nenhum arquivo .p12/.pfx encontrado no diretório")
                : "⚠️ Diretório não existe: <code>{$certDir}</code>"
        ];
        
        // Mostrar resultados
        foreach ($tests as $test) {
            $class = $test['status'] === 'pass' ? 'pass' : ($test['status'] === 'fail' ? 'fail' : 'warn');
            echo "<div class='test {$class}'>";
            echo "<div class='status'>{$test['name']}</div>";
            echo "<div class='details'>{$test['message']}</div>";
            echo "</div>";
        }
        
        // Resumo
        $passed = count(array_filter($tests, fn($t) => $t['status'] === 'pass'));
        $failed = count(array_filter($tests, fn($t) => $t['status'] === 'fail'));
        $warnings = count(array_filter($tests, fn($t) => $t['status'] === 'warn'));
        
        echo "<hr style='margin: 20px 0;'>";
        echo "<h2>📊 Resumo</h2>";
        echo "<p><strong>✅ Passou:</strong> {$passed} | <strong>❌ Falhou:</strong> {$failed} | <strong>⚠️ Avisos:</strong> {$warnings}</p>";
        
        if ($failed === 0 && !$isProduction) {
            echo "<p style='color: #4CAF50;'><strong>✅ Configuração OK para SANDBOX</strong></p>";
        } elseif ($failed === 0 && $isProduction) {
            echo "<p style='color: #4CAF50;'><strong>✅ Certificado configurado corretamente para PRODUÇÃO</strong></p>";
        } else {
            echo "<p style='color: #f44336;'><strong>❌ Ajustes necessários</strong></p>";
        }
        ?>
        
        <hr style='margin: 20px 0;'>
        <h3>📝 Próximos Passos</h3>
        <ol>
            <li>Se o certificado não está configurado, adicione <code>EFI_CERT_PATH</code> no arquivo <code>.env</code></li>
            <li>Certifique-se de que o caminho é <strong>absoluto</strong> (não relativo)</li>
            <li>Em produção, o certificado é <strong>obrigatório</strong></li>
            <li>Obtenha o certificado em: <a href='https://dev.gerencianet.com.br/' target='_blank'>Dashboard EFI → API → Meus Certificados → Produção</a></li>
        </ol>
    </div>
</body>
</html>
