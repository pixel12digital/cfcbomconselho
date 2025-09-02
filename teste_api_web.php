<?php
// Teste para verificar dados do instrutor ID 23 via web
echo "<h2>Teste API Instrutor ID 23 via Web</h2>";

// Simular uma requisição web
$baseUrl = "http://localhost/cfc-bom-conselho/";
$apiUrl = $baseUrl . "admin/api/instrutores.php?id=23";

echo "<h3>Testando API via Web:</h3>";
echo "<p>URL: $apiUrl</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, "cookies.txt");
curl_setopt($ch, CURLOPT_COOKIEFILE, "cookies.txt");
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p>HTTP Code: $httpCode</p>";
if ($error) {
    echo "<p style='color: red;'>Erro cURL: $error</p>";
}
echo "<p>Resposta da API:</p>";
echo "<pre>";
print_r(json_decode($response, true));
echo "</pre>";

// Testar também a página de login para verificar se há problemas de autenticação
echo "<h3>Testando página de login:</h3>";
$loginUrl = $baseUrl . "admin/login.php";
echo "<p>URL: $loginUrl</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $loginUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code da página de login: $httpCode</p>";
echo "<p>Tamanho da resposta: " . strlen($response) . " bytes</p>";
?>
