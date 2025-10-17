<?php
/**
 * Teste da API de Exclusão de Turmas
 * Para debug e verificação
 */

// Simular dados de teste
$turmaId = 1; // ID de teste

echo "<h2>🧪 Teste da API de Exclusão de Turmas</h2>";

// Teste 1: Verificar se a API responde
echo "<h3>Teste 1: Verificar resposta da API</h3>";

$url = 'http://localhost/cfc-bom-conselho/admin/api/turmas-teoricas.php';
$data = [
    'acao' => 'excluir',
    'turma_id' => $turmaId
];

$options = [
    'http' => [
        'header' => "Content-type: application/x-www-form-urlencoded\r\n",
        'method' => 'POST',
        'content' => http_build_query($data)
    ]
];

$context = stream_context_create($options);
$result = file_get_contents($url, false, $context);

echo "<pre>";
echo "URL: $url\n";
echo "Dados enviados: " . print_r($data, true) . "\n";
echo "Resposta da API:\n";
echo htmlspecialchars($result);
echo "</pre>";

// Teste 2: Verificar se é JSON válido
echo "<h3>Teste 2: Verificar se é JSON válido</h3>";

$json = json_decode($result, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "✅ JSON válido!<br>";
    echo "<pre>" . print_r($json, true) . "</pre>";
} else {
    echo "❌ JSON inválido!<br>";
    echo "Erro: " . json_last_error_msg() . "<br>";
    echo "Resposta original: " . htmlspecialchars($result);
}

// Teste 3: Verificar headers HTTP
echo "<h3>Teste 3: Verificar headers HTTP</h3>";

$headers = get_headers($url . '?' . http_build_query($data));
echo "<pre>";
foreach ($headers as $header) {
    echo htmlspecialchars($header) . "\n";
}
echo "</pre>";
?>
