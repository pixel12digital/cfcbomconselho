<?php
/**
 * Teste Direto do Certificado - Verifica se o certificado pode ser lido
 */

require_once __DIR__ . '/../../app/Config/Env.php';
use App\Config\Env;

Env::load();

$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? '';

header('Content-Type: text/plain; charset=utf-8');

if (!$certPath || !file_exists($certPath)) {
    die("❌ Certificado não encontrado: " . ($certPath ?? 'não configurado'));
}

echo "🔍 Teste Direto do Certificado\n";
echo str_repeat("=", 60) . "\n\n";

echo "Caminho: {$certPath}\n";
echo "Tamanho: " . filesize($certPath) . " bytes\n";
echo "Senha configurada: " . (!empty($certPassword) ? "SIM (" . strlen($certPassword) . " caracteres)" : "NÃO") . "\n\n";

// Tentar ler informações do certificado usando OpenSSL
echo "📋 Tentando ler informações do certificado...\n";
echo str_repeat("-", 60) . "\n";

// Teste 1: Sem senha
echo "\n1. Teste SEM senha:\n";
$output = [];
$returnVar = 0;
exec("openssl pkcs12 -info -in \"{$certPath}\" -noout -passin pass: 2>&1", $output, $returnVar);
if ($returnVar === 0) {
    echo "✅ Certificado pode ser lido SEM senha\n";
    foreach ($output as $line) {
        if (preg_match('/subject=|issuer=|friendlyName=/i', $line)) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "❌ Não foi possível ler sem senha\n";
    echo "   Erro: " . implode("\n   ", array_slice($output, 0, 3)) . "\n";
}

// Teste 2: Com senha (se configurada)
if (!empty($certPassword)) {
    echo "\n2. Teste COM senha configurada:\n";
    $output = [];
    $returnVar = 0;
    $escapedPassword = escapeshellarg($certPassword);
    exec("openssl pkcs12 -info -in \"{$certPath}\" -noout -passin pass:{$escapedPassword} 2>&1", $output, $returnVar);
    if ($returnVar === 0) {
        echo "✅ Certificado pode ser lido COM senha\n";
        foreach ($output as $line) {
            if (preg_match('/subject=|issuer=|friendlyName=/i', $line)) {
                echo "   " . trim($line) . "\n";
            }
        }
    } else {
        echo "❌ Senha configurada está INCORRETA\n";
        echo "   Erro: " . implode("\n   ", array_slice($output, 0, 3)) . "\n";
    }
}

// Teste 3: Verificar se é um P12 válido
echo "\n3. Verificando formato do arquivo:\n";
$fileContent = file_get_contents($certPath, false, null, 0, 4);
$hex = bin2hex($fileContent);
echo "   Primeiros bytes (hex): {$hex}\n";
if (substr($hex, 0, 4) === '3082' || substr($hex, 0, 4) === '30a0') {
    echo "   ✅ Parece ser um arquivo PKCS#12 válido\n";
} else {
    echo "   ⚠️  Formato pode estar incorreto\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "💡 CONCLUSÃO:\n";
if ($returnVar !== 0 && empty($certPassword)) {
    echo "   O certificado PROVAVELMENTE TEM SENHA.\n";
    echo "   Pergunte ao cliente qual é a senha e adicione:\n";
    echo "   EFI_CERT_PASSWORD=senha_aqui no .env\n";
} elseif ($returnVar === 0) {
    echo "   O certificado NÃO tem senha (ou a senha configurada está correta).\n";
    echo "   O problema pode ser:\n";
    echo "   1. Credenciais não correspondem ao certificado\n";
    echo "   2. Escopos não habilitados na aplicação EFI\n";
    echo "   3. Certificado e credenciais são de aplicações diferentes\n";
}
