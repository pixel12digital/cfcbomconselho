<?php
// Script para testar as APIs de usuários e CFCs na produção
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🧪 Teste das APIs - Produção</h2>";

// Testar API de CFCs
echo "<h3>📡 Testando API de CFCs</h3>";
$cfcsUrl = 'https://linen-mantis-198436.hostingersite.com/admin/api/cfcs.php';
echo "<p>URL: {$cfcsUrl}</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $cfcsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    echo "<p style='color: green;'>Resposta recebida</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Testar API de Usuários
echo "<h3>📡 Testando API de Usuários</h3>";
$usuariosUrl = 'https://linen-mantis-198436.hostingersite.com/admin/api/usuarios.php';
echo "<p>URL: {$usuariosUrl}</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $usuariosUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    echo "<p style='color: green;'>Resposta recebida</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Testar com sessão simulada
echo "<h3>🔐 Testando com Sessão Simulada</h3>";

// Primeiro fazer login
$loginUrl = 'https://linen-mantis-198436.hostingersite.com/admin/login.php';
echo "<p>Fazendo login em: {$loginUrl}</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>Login HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL no login: {$error}</p>";
} else {
    echo "<p style='color: green;'>Página de login carregada</p>";
}

// Agora testar as APIs com cookies
echo "<h3>🍪 Testando APIs com Cookies de Sessão</h3>";

// Testar CFCs com cookies
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $cfcsUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>CFCs com cookies - HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    echo "<p style='color: green;'>Resposta recebida</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Testar Usuários com cookies
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $usuariosUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>Usuários com cookies - HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    echo "<p style='color: green;'>Resposta recebida</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Limpar arquivo de cookies
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}

echo "<h3>✅ Teste Concluído</h3>";
?>
