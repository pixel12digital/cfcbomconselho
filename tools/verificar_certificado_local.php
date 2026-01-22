<?php
/**
 * Script para Verificar Certificado EFI Localmente
 * 
 * Uso: php tools/verificar_certificado_local.php caminho/para/certificado.p12
 * 
 * Este script verifica se o certificado tem senha e se está válido.
 */

if ($argc < 2) {
    echo "Uso: php verificar_certificado_local.php caminho/para/certificado.p12\n";
    echo "Exemplo: php verificar_certificado_local.php certificados/certificado.p12\n";
    exit(1);
}

$certPath = $argv[1];

if (!file_exists($certPath)) {
    echo "❌ ERRO: Arquivo não encontrado: {$certPath}\n";
    exit(1);
}

echo "🔍 Verificando certificado: {$certPath}\n";
echo str_repeat("=", 60) . "\n\n";

// 1. Verificar se arquivo existe e tamanho
$fileSize = filesize($certPath);
echo "✅ Arquivo encontrado\n";
echo "   Tamanho: " . number_format($fileSize) . " bytes (" . number_format($fileSize / 1024, 2) . " KB)\n\n";

// 2. Tentar abrir sem senha
echo "🔐 Testando se certificado tem senha...\n";

// Usar OpenSSL para verificar
$output = [];
$returnVar = 0;

// Tentar abrir sem senha
exec("openssl pkcs12 -info -in \"{$certPath}\" -noout -passin pass: 2>&1", $output, $returnVar);

if ($returnVar === 0) {
    echo "✅ Certificado NÃO tem senha (pode ser aberto sem senha)\n\n";
    $hasPassword = false;
} else {
    // Tentar com senha vazia também falha, então provavelmente tem senha
    echo "⚠️  Certificado provavelmente TEM senha\n";
    echo "   (Não foi possível abrir sem senha)\n\n";
    $hasPassword = true;
}

// 3. Tentar extrair informações básicas
echo "📋 Informações do certificado:\n";
echo str_repeat("-", 60) . "\n";

// Tentar com senha vazia primeiro
$output = [];
exec("openssl pkcs12 -info -in \"{$certPath}\" -noout -passin pass: 2>&1", $output, $returnVar);

if ($returnVar === 0) {
    // Sem senha - mostrar informações
    foreach ($output as $line) {
        if (preg_match('/subject=|issuer=|friendlyName=/i', $line)) {
            echo "   " . trim($line) . "\n";
        }
    }
} else {
    echo "   ⚠️  Não foi possível ler informações sem senha\n";
    echo "   Para ver informações completas, use:\n";
    echo "   openssl pkcs12 -info -in \"{$certPath}\" -passin pass:SUA_SENHA\n\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "📝 RESUMO:\n";
echo str_repeat("=", 60) . "\n";
echo "Caminho: {$certPath}\n";
echo "Tamanho: " . number_format($fileSize) . " bytes\n";
echo "Tem senha: " . ($hasPassword ? "SIM ⚠️" : "NÃO ✅") . "\n";

if ($hasPassword) {
    echo "\n⚠️  IMPORTANTE:\n";
    echo "   Este certificado TEM senha.\n";
    echo "   Você precisa adicionar no .env:\n";
    echo "   EFI_CERT_PASSWORD=senha_do_certificado\n";
    echo "\n   Pergunte ao cliente qual é a senha do certificado.\n";
} else {
    echo "\n✅ Este certificado NÃO precisa de senha.\n";
    echo "   Não é necessário configurar EFI_CERT_PASSWORD no .env.\n";
}

echo "\n";
