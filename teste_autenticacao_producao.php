<?php
// Script para testar especificamente a autenticação na produção
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔐 Teste de Autenticação - Produção</h2>";

// Testar login direto na API
echo "<h3>📡 Testando Login Direto na API</h3>";

$loginData = [
    'email' => 'admin@cfc.com',
    'senha' => 'admin123'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://linen-mantis-198436.hostingersite.com/admin/login.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($loginData));
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
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    echo "<p style='color: green;'>Resposta do login:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Verificar cookies gerados
if (file_exists('cookies.txt')) {
    echo "<h3>🍪 Cookies Gerados:</h3>";
    $cookies = file_get_contents('cookies.txt');
    echo "<pre>" . htmlspecialchars($cookies) . "</pre>";
}

// Testar API de usuários com cookies
echo "<h3>👥 Testando API de Usuários com Cookies</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://linen-mantis-198436.hostingersite.com/admin/api/usuarios.php');
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

echo "<p>Usuários HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    echo "<p style='color: green;'>Resposta da API de usuários:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
}

// Testar se a sessão está sendo mantida
echo "<h3>🔄 Testando Persistência da Sessão</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'https://linen-mantis-198436.hostingersite.com/admin/index.php');
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

echo "<p>Dashboard HTTP Code: {$httpCode}</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: {$error}</p>";
} else {
    if (strpos($response, 'Dashboard') !== false) {
        echo "<p style='color: green;'>✅ Dashboard carregado com sucesso</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Dashboard não carregou corretamente</p>";
        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>";
    }
}

// Limpar arquivo de cookies
if (file_exists('cookies.txt')) {
    unlink('cookies.txt');
}

echo "<h3>✅ Teste de Autenticação Concluído</h3>";
?>
