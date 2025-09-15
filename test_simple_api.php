<?php
// Teste simples para verificar se a API está funcionando
echo "Testando API de agendamento...\n";

// Simular uma requisição POST simples
$url = "http://localhost:8000/admin/api/agendamento.php";

$postData = http_build_query([
    'acao' => 'teste',
    'teste' => 'valor'
]);

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/x-www-form-urlencoded',
        'content' => $postData
    ]
]);

$result = file_get_contents($url, false, $context);

if ($result === FALSE) {
    echo "Erro ao acessar a API\n";
    if (isset($http_response_header)) {
        echo "Headers de resposta:\n";
        foreach ($http_response_header as $header) {
            echo "  " . $header . "\n";
        }
    }
} else {
    echo "Resposta da API:\n";
    echo $result . "\n";
}
?>
