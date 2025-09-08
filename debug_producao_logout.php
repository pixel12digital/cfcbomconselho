<?php
/**
 * Debug de produção - Verificar logout em ambiente de produção
 */

// Headers para evitar cache
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

session_start();

echo "<h1>🔍 DEBUG PRODUÇÃO - LOGOUT</h1>";
echo "<style>
    .debug-section { border: 2px solid #333; margin: 10px 0; padding: 10px; background: #f9f9f9; }
    .debug-step { border: 1px solid #666; margin: 5px 0; padding: 5px; background: #fff; }
    .error { color: red; font-weight: bold; }
    .success { color: green; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
</style>";

echo "<div class='debug-section'>";
echo "<h2>🌐 INFORMAÇÕES DO AMBIENTE</h2>";
echo "<div class='debug-step'>";
echo "<strong>HTTP_HOST:</strong> " . ($_SERVER['HTTP_HOST'] ?? 'não definido') . "<br>";
echo "<strong>HTTPS:</strong> " . (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'SIM' : 'NÃO') . "<br>";
echo "<strong>SERVER_NAME:</strong> " . ($_SERVER['SERVER_NAME'] ?? 'não definido') . "<br>";
echo "<strong>REQUEST_SCHEME:</strong> " . ($_SERVER['REQUEST_SCHEME'] ?? 'não definido') . "<br>";
echo "<strong>Ambiente detectado:</strong> " . (strpos($_SERVER['HTTP_HOST'] ?? '', 'localhost') !== false ? 'LOCAL' : 'PRODUÇÃO') . "<br>";
echo "</div>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>📊 ESTADO INICIAL DA SESSÃO</h2>";
echo "<div class='debug-step'>";
echo "<strong>Session ID:</strong> " . session_id() . "<br>";
echo "<strong>Session Status:</strong> " . session_status() . "<br>";
echo "<strong>Session Name:</strong> " . session_name() . "<br>";
echo "<strong>Session Data:</strong><br>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";
echo "</div>";

echo "<div class='debug-step'>";
echo "<strong>Cookies:</strong><br>";
echo "<pre>";
var_dump($_COOKIE);
echo "</pre>";
echo "</div>";
echo "</div>";

// Incluir arquivos necessários
require_once 'includes/config.php';
require_once 'includes/auth.php';

echo "<div class='debug-section'>";
echo "<h2>🔧 VERIFICAÇÃO INICIAL</h2>";
echo "<div class='debug-step'>";
echo "<strong>isLoggedIn():</strong> " . (isLoggedIn() ? '<span class="success">TRUE</span>' : '<span class="error">FALSE</span>') . "<br>";
echo "<strong>getCurrentUser():</strong><br>";
$user = getCurrentUser();
if ($user) {
    echo "<span class='warning'>Usuário encontrado:</span><br>";
    echo "<pre>";
    var_dump($user);
    echo "</pre>";
} else {
    echo "<span class='success'>Nenhum usuário logado</span><br>";
}
echo "</div>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>🚪 TESTE DE LOGOUT</h2>";
echo "<div class='debug-step'>";
global $auth;
echo "<strong>Executando logout...</strong><br>";
$result = $auth->logout();
echo "<strong>Resultado do logout:</strong><br>";
echo "<pre>";
var_dump($result);
echo "</pre>";
echo "</div>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>🍪 TESTE DE REMOÇÃO DE COOKIES</h2>";
echo "<div class='debug-step'>";
echo "<strong>Cookies antes da remoção:</strong><br>";
echo "<pre>";
var_dump($_COOKIE);
echo "</pre>";

// Testar remoção de cookies com parâmetros corretos para produção
$is_https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
echo "<strong>HTTPS detectado:</strong> " . ($is_https ? 'SIM' : 'NÃO') . "<br>";

if (isset($_COOKIE['CFC_SESSION'])) {
    echo "<strong>Removendo CFC_SESSION...</strong><br>";
    setcookie('CFC_SESSION', '', time() - 42000, '/', '', $is_https, true);
}

if (isset($_COOKIE['remember_token'])) {
    echo "<strong>Removendo remember_token...</strong><br>";
    setcookie('remember_token', '', time() - 3600, '/', '', $is_https, true);
}

echo "<strong>Cookies após remoção:</strong><br>";
echo "<pre>";
var_dump($_COOKIE);
echo "</pre>";
echo "</div>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>🌐 TESTE DE REDIRECIONAMENTO</h2>";
echo "<div class='debug-step'>";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
if (strpos($host, 'localhost') !== false || strpos($host, '127.0.0.1') !== false) {
    $base_url = 'http://localhost/cfc-bom-conselho';
} else {
    $base_url = 'https://linen-mantis-198436.hostingersite.com';
}
echo "<strong>Host detectado:</strong> " . $host . "<br>";
echo "<strong>URL base calculada:</strong> " . $base_url . "<br>";
echo "<strong>URL de redirecionamento:</strong> " . $base_url . '/index.php?message=logout_success<br>';
echo "</div>";

echo "<div class='debug-step'>";
echo "<strong>Links de teste:</strong><br>";
echo '<a href="' . $base_url . '/index.php?message=logout_success" target="_blank">🔗 Testar redirecionamento</a><br>';
echo '<a href="admin/index.php" target="_blank">🔗 Testar acesso ao admin</a><br>';
echo "</div>";
echo "</div>";

echo "<div class='debug-section'>";
echo "<h2>📋 RESUMO</h2>";
echo "<div class='debug-step'>";
if (isLoggedIn()) {
    echo "<span class='error'>❌ PROBLEMA: Usuário ainda está logado!</span><br>";
} else {
    echo "<span class='success'>✅ SUCESSO: Usuário não está mais logado!</span><br>";
}
echo "</div>";
echo "</div>";
?>
