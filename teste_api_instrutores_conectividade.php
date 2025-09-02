<?php
// Teste simples para verificar se a API de instrutores está funcionando
echo "<h2>Teste da API de Instrutores</h2>";

// Testar conexão direta
$apiUrl = "http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php";

echo "<h3>1. Teste de Conectividade</h3>";
echo "<p>URL: $apiUrl</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
if ($error) {
    echo "<p style='color: red;'><strong>Erro cURL:</strong> $error</p>";
}

echo "<p><strong>Resposta:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Testar com autenticação simulada
echo "<h3>2. Teste com Autenticação</h3>";

// Primeiro fazer login
$loginUrl = "http://localhost:8080/cfc-bom-conselho/admin/login.php";
echo "<p>Fazendo login em: $loginUrl</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, "email=admin@cfc.com&senha=admin123&action=login");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>Login HTTP Code:</strong> $httpCode</p>";

// Agora testar a API com cookies
echo "<p>Testando API com cookies de sessão...</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>API HTTP Code:</strong> $httpCode</p>";
echo "<p><strong>Resposta da API:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Verificar se o arquivo de cookies foi criado
if (file_exists("cookies.txt")) {
    echo "<p><strong>Cookies salvos:</strong></p>";
    echo "<pre>" . htmlspecialchars(file_get_contents("cookies.txt")) . "</pre>";
} else {
    echo "<p style='color: red;'>Arquivo de cookies não foi criado!</p>";
}

echo "<h3>3. Diagnóstico</h3>";
echo "<p>Se o HTTP Code for 401 ou 403, o problema é de autenticação.</p>";
echo "<p>Se o HTTP Code for 500, há um erro no servidor.</p>";
echo "<p>Se o HTTP Code for 0 ou houver erro cURL, há problema de conectividade.</p>";
?>
