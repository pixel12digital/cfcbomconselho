<?php
/**
 * Script de Teste - Caminho do Manifest
 * Acesse via: /test-manifest-path.php
 */

// Carregar Bootstrap
require_once __DIR__ . '/../app/Bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== DIAGNÓSTICO DE CAMINHOS ===\n\n";

echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "REQUEST_URI: " . ($_SERVER['REQUEST_URI'] ?? 'N/A') . "\n";
echo "PHP_SELF: " . ($_SERVER['PHP_SELF'] ?? 'N/A') . "\n\n";

echo "=== BASE_PATH ===\n";
echo "base_path('pwa-manifest.php'): " . base_path('pwa-manifest.php') . "\n";
echo "base_path('/pwa-manifest.php'): " . base_path('/pwa-manifest.php') . "\n";
echo "base_path('public_html/pwa-manifest.php'): " . base_path('public_html/pwa-manifest.php') . "\n\n";

echo "=== ARQUIVOS ===\n";
$manifestPath1 = __DIR__ . '/pwa-manifest.php';
$manifestPath2 = dirname(__DIR__) . '/public_html/pwa-manifest.php';
echo "pwa-manifest.php (__DIR__): $manifestPath1\n";
echo "  Existe: " . (file_exists($manifestPath1) ? 'SIM' : 'NÃO') . "\n";
echo "pwa-manifest.php (dirname): $manifestPath2\n";
echo "  Existe: " . (file_exists($manifestPath2) ? 'SIM' : 'NÃO') . "\n\n";

echo "=== TESTE DE ACESSO ===\n";
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
$testPath1 = $baseUrl . base_path('pwa-manifest.php');
$testPath2 = $baseUrl . '/pwa-manifest.php';
$testPath3 = $baseUrl . '/public_html/pwa-manifest.php';
echo "URL 1 (base_path): $testPath1\n";
echo "URL 2 (direto): $testPath2\n";
echo "URL 3 (com public_html): $testPath3\n";
