<?php
// Teste específico para ação de editar
$url = "http://localhost:8000/admin/api/agendamento.php";

$data = [
    'acao' => 'editar',
    'aula_id' => 1,
    'edit_aluno_id' => 1,
    'edit_data_aula' => '2025-09-15',
    'edit_hora_inicio' => '10:00',
    'edit_hora_fim' => '10:50',
    'edit_tipo_aula' => 'pratica',
    'edit_instrutor_id' => 1,
    'edit_veiculo_id' => 1,
    'edit_observacoes' => 'Teste de edição'
];

$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => 'Content-Type: application/json',
        'content' => json_encode($data)
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
