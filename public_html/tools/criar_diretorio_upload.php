<?php
/**
 * Script para criar diretório de upload de logos CFC
 * Execute via SSH: php public_html/tools/criar_diretorio_upload.php
 */

$rootPath = dirname(__DIR__, 2);
$uploadDir = $rootPath . '/storage/uploads/cfcs/';

echo "=== CRIAR DIRETÓRIO DE UPLOAD ===\n\n";
echo "Root Path: $rootPath\n";
echo "Upload Dir: $uploadDir\n\n";

// Criar diretório
if (!is_dir($uploadDir)) {
    echo "Criando diretório...\n";
    if (mkdir($uploadDir, 0755, true)) {
        echo "✅ Diretório criado com sucesso!\n";
    } else {
        echo "❌ Erro ao criar diretório.\n";
        exit(1);
    }
} else {
    echo "✅ Diretório já existe.\n";
}

// Verificar permissões
echo "\nVerificando permissões...\n";
echo "É diretório: " . (is_dir($uploadDir) ? 'SIM' : 'NÃO') . "\n";
echo "É gravável: " . (is_writable($uploadDir) ? 'SIM' : 'NÃO') . "\n";

if (is_dir($uploadDir)) {
    $perms = substr(sprintf('%o', fileperms($uploadDir)), -4);
    echo "Permissões: $perms\n";
}

// Teste de escrita
echo "\nTestando escrita...\n";
$testFile = $uploadDir . 'test_' . time() . '.txt';
if (file_put_contents($testFile, 'test')) {
    echo "✅ Arquivo de teste criado: " . basename($testFile) . "\n";
    if (unlink($testFile)) {
        echo "✅ Arquivo de teste removido.\n";
    } else {
        echo "⚠️  Não foi possível remover arquivo de teste.\n";
    }
} else {
    echo "❌ Erro ao criar arquivo de teste.\n";
}

echo "\n=== CONCLUÍDO ===\n";
