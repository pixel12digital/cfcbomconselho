<?php
/**
 * Script de Diagnóstico - Upload de Logo CFC
 * Acesse via: /tools/diagnostico_upload_logo.php
 */

// Carregar configurações
require_once __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/Config/Env.php';
require_once __DIR__ . '/../app/Config/Database.php';

use App\Config\Env;
use App\Config\Database;

Env::load();

// Verificar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['current_role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    die("Acesso negado. Apenas administradores podem acessar este diagnóstico.");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Diagnóstico - Upload de Logo CFC</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        .success { border-left-color: #28a745; }
        .error { border-left-color: #dc3545; }
        .warning { border-left-color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico - Upload de Logo CFC</h1>
    <p><strong>Data/Hora:</strong> <?= date('Y-m-d H:i:s') ?></p>

<?php

// 1. Verificar estrutura de diretórios
echo '<div class="section">';
echo '<h2>1. Estrutura de Diretórios</h2>';
$rootPath = dirname(__DIR__);
echo '<pre>';
echo "ROOT_PATH: $rootPath\n";
echo "DIRNAME(__DIR__, 2): " . dirname(__DIR__, 2) . "\n";
echo "SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'N/A') . "\n";
echo "DOCUMENT_ROOT: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo '</pre>';

$uploadDir = dirname(__DIR__, 2) . '/storage/uploads/cfcs/';
echo '<pre>';
echo "Upload Dir (calculado): $uploadDir\n";
echo "Upload Dir existe: " . (is_dir($uploadDir) ? '✅ SIM' : '❌ NÃO') . "\n";
if (is_dir($uploadDir)) {
    echo "Upload Dir é gravável: " . (is_writable($uploadDir) ? '✅ SIM' : '❌ NÃO') . "\n";
    echo "Permissões: " . substr(sprintf('%o', fileperms($uploadDir)), -4) . "\n";
}
echo '</pre>';
echo '</div>';

// 2. Verificar configurações PHP
echo '<div class="section">';
echo '<h2>2. Configurações PHP (Upload)</h2>';
echo '<pre>';
echo "upload_max_filesize: " . ini_get('upload_max_filesize') . "\n";
echo "post_max_size: " . ini_get('post_max_size') . "\n";
echo "max_file_uploads: " . ini_get('max_file_uploads') . "\n";
echo "file_uploads: " . (ini_get('file_uploads') ? '✅ Habilitado' : '❌ Desabilitado') . "\n";
echo "upload_tmp_dir: " . (ini_get('upload_tmp_dir') ?: 'Padrão do sistema') . "\n";
echo '</pre>';
echo '</div>';

// 3. Verificar extensões necessárias
echo '<div class="section">';
echo '<h2>3. Extensões PHP</h2>';
echo '<pre>';
echo "GD: " . (extension_loaded('gd') ? '✅ Carregada' : '❌ NÃO carregada') . "\n";
echo "fileinfo: " . (extension_loaded('fileinfo') ? '✅ Carregada' : '❌ NÃO carregada') . "\n";
if (extension_loaded('gd')) {
    $gdInfo = gd_info();
    echo "GD Version: " . ($gdInfo['GD Version'] ?? 'N/A') . "\n";
    echo "PNG Support: " . (isset($gdInfo['PNG Support']) && $gdInfo['PNG Support'] ? '✅' : '❌') . "\n";
    echo "JPEG Support: " . (isset($gdInfo['JPEG Support']) && $gdInfo['JPEG Support'] ? '✅' : '❌') . "\n";
    echo "WebP Support: " . (isset($gdInfo['WebP Support']) && $gdInfo['WebP Support'] ? '✅' : '❌') . "\n";
}
echo '</pre>';
echo '</div>';

// 4. Verificar CFC atual
echo '<div class="section">';
echo '<h2>4. CFC Atual</h2>';
try {
    $cfcModel = new \App\Models\Cfc();
    $cfc = $cfcModel->getCurrent();
    if ($cfc) {
        echo '<pre>';
        echo "ID: {$cfc['id']}\n";
        echo "Nome: {$cfc['nome']}\n";
        echo "Logo Path: " . ($cfc['logo_path'] ?? 'NULL') . "\n";
        if (!empty($cfc['logo_path'])) {
            $logoFullPath = dirname(__DIR__, 2) . '/' . $cfc['logo_path'];
            echo "Logo Full Path: $logoFullPath\n";
            echo "Logo existe: " . (file_exists($logoFullPath) ? '✅ SIM' : '❌ NÃO') . "\n";
        }
        echo '</pre>';
    } else {
        echo '<pre>❌ CFC não encontrado</pre>';
    }
} catch (\Exception $e) {
    echo '<pre class="error">❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</pre>';
}
echo '</div>';

// 5. Teste de escrita
echo '<div class="section">';
echo '<h2>5. Teste de Escrita</h2>';
$testFile = $uploadDir . 'test_' . time() . '.txt';
if (!is_dir($uploadDir)) {
    echo '<pre class="error">❌ Diretório não existe. Tentando criar...</pre>';
    if (mkdir($uploadDir, 0755, true)) {
        echo '<pre class="success">✅ Diretório criado com sucesso!</pre>';
    } else {
        echo '<pre class="error">❌ Erro ao criar diretório</pre>';
    }
}

if (is_dir($uploadDir)) {
    if (file_put_contents($testFile, 'test')) {
        echo '<pre class="success">✅ Arquivo de teste criado com sucesso: ' . basename($testFile) . '</pre>';
        @unlink($testFile);
        echo '<pre>✅ Arquivo de teste removido</pre>';
    } else {
        echo '<pre class="error">❌ Erro ao criar arquivo de teste</pre>';
    }
}
echo '</div>';

// 6. Verificar base_path
echo '<div class="section">';
echo '<h2>6. Função base_path()</h2>';
echo '<pre>';
echo "base_path('pwa-manifest.php'): " . base_path('pwa-manifest.php') . "\n";
echo "base_path('/pwa-manifest.php'): " . base_path('/pwa-manifest.php') . "\n";
echo "base_path('assets/css/tokens.css'): " . base_path('assets/css/tokens.css') . "\n";
echo '</pre>';
echo '</div>';

// 7. Verificar se pwa-manifest.php existe
echo '<div class="section">';
echo '<h2>7. Arquivo pwa-manifest.php</h2>';
$manifestPath = __DIR__ . '/pwa-manifest.php';
echo '<pre>';
echo "Caminho esperado: $manifestPath\n";
echo "Arquivo existe: " . (file_exists($manifestPath) ? '✅ SIM' : '❌ NÃO') . "\n";
if (file_exists($manifestPath)) {
    echo "Tamanho: " . filesize($manifestPath) . " bytes\n";
    echo "Permissões: " . substr(sprintf('%o', fileperms($manifestPath)), -4) . "\n";
}
echo '</pre>';
echo '</div>';

?>

    <div class="section">
        <h2>8. Próximos Passos</h2>
        <ul>
            <li>Se o diretório não existe: criar manualmente via SSH ou File Manager</li>
            <li>Se não é gravável: ajustar permissões (chmod 755)</li>
            <li>Se upload_max_filesize < 5MB: ajustar no php.ini ou .htaccess</li>
            <li>Se pwa-manifest.php não existe: verificar se foi feito deploy</li>
        </ul>
    </div>

</body>
</html>
