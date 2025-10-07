<?php
/**
 * Teste com visualização de logs de erro
 */

echo "<h2>Teste da API de Exames com Logs</h2>";

// Simular login
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';

echo "<h3>1. Testando GET com aluno_id=1:</h3>";

// Criar diretório de logs se não existir
if (!file_exists('../logs')) {
    mkdir('../logs', 0777, true);
}

// Limpar arquivo de log anterior
$logFile = '../logs/exames_api_errors.log';
if (file_exists($logFile)) {
    file_put_contents($logFile, '');
}

// Fazer requisição
$url = 'http://localhost/cfc-bom-conselho/admin/api/exames.php?aluno_id=1';

echo "<p>URL: $url</p>";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h4>Resposta HTTP:</h4>";
echo "<strong>Status Code:</strong> $httpCode<br>";

// Separar headers e body
list($headers, $body) = explode("\r\n\r\n", $response, 2);

echo "<h4>Headers:</h4>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";

echo "<h4>Body:</h4>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

// Verificar JSON
$json = json_decode($body, true);
if ($json !== null) {
    echo "<p style='color: green;'>✅ JSON válido</p>";
    echo "<pre>" . print_r($json, true) . "</pre>";
} else {
    echo "<p style='color: red;'>❌ JSON inválido: " . json_last_error_msg() . "</p>";
}

echo "<hr>";

echo "<h3>2. Logs de Erro:</h3>";
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    if (!empty($logs)) {
        echo "<pre>" . htmlspecialchars($logs) . "</pre>";
    } else {
        echo "<p>Nenhum log registrado.</p>";
    }
} else {
    echo "<p>Arquivo de log não encontrado.</p>";
}

echo "<hr>";

echo "<h3>3. Erro do PHP (se houver):</h3>";
$phpErrorLog = ini_get('error_log');
echo "<p>Arquivo de erro do PHP: $phpErrorLog</p>";

if ($phpErrorLog && file_exists($phpErrorLog)) {
    $phpErrors = file_get_contents($phpErrorLog);
    $lastErrors = substr($phpErrors, -2000); // Últimos 2000 caracteres
    echo "<pre>" . htmlspecialchars($lastErrors) . "</pre>";
} else {
    echo "<p>Arquivo de erro do PHP não encontrado ou não configurado.</p>";
}
?>
