<?php
// Teste simples da API
$url = "http://localhost:8000/admin/api/agendamento.php";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Erro ao acessar a API\n";
    echo "URL: " . $url . "\n";
    if (isset($http_response_header)) {
        echo "Erro HTTP: " . $http_response_header[0] . "\n";
    }
} else {
    echo "Resposta da API:\n";
    echo $result . "\n";
}
?>
