<?php
// Script para testar requisição DELETE
$url = 'http://localhost:8080/cfc-bom-conselho/admin/api/test_delete.php';
$data = json_encode(['id' => 1]);

$context = stream_context_create([
    'http' => [
        'method' => 'DELETE',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data)
        ],
        'content' => $data
    ]
]);

echo "Testando requisição DELETE para: $url\n";
echo "Dados enviados: $data\n\n";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Erro ao fazer requisição\n";
    $error = error_get_last();
    echo "Erro: " . $error['message'] . "\n";
} else {
    echo "✅ Resposta recebida:\n";
    echo $response . "\n";
}

// Testar também o arquivo veiculos.php
echo "\n" . str_repeat("=", 50) . "\n";
echo "Testando arquivo veiculos.php\n";
echo str_repeat("=", 50) . "\n";

$url2 = 'http://localhost:8080/cfc-bom-conselho/admin/api/veiculos.php';
$data2 = json_encode(['id' => 1]);

$context2 = stream_context_create([
    'http' => [
        'method' => 'DELETE',
        'header' => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data2)
        ],
        'content' => $data2
    ]
]);

echo "Testando requisição DELETE para: $url2\n";
echo "Dados enviados: $data2\n\n";

$response2 = file_get_contents($url2, false, $context2);

if ($response2 === false) {
    echo "❌ Erro ao fazer requisição\n";
    $error2 = error_get_last();
    echo "Erro: " . $error2['message'] . "\n";
} else {
    echo "✅ Resposta recebida:\n";
    echo $response2 . "\n";
}
?>
