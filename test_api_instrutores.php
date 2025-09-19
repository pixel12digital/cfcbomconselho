<?php
// Teste da API de instrutores para debug

// Simular dados de FormData
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW';

// Simular dados POST como viriam do FormData
$_POST = [
    'id' => '36',
    'nome' => 'Alexsandra Rodrigues de Pontes Pontes',
    'email' => 'pontess_29@hotmail.com',
    'telefone' => '(87) 99921-6055',
    'credencial' => 'sandra',
    'cfc_id' => '36',
    'usuario_id' => '22',
    'ativo' => '1',
    'cpf' => '022.653.934-28',
    'cnh' => '03290614062',
    'data_nascimento' => '1976-06-29',
    'horario_inicio' => '08:00',
    'horario_fim' => '17:00',
    'endereco' => '',
    'cidade' => '',
    'uf' => '',
    'tipo_carga' => '',
    'validade_credencial' => '2035-03-20',
    'observacoes' => '',
    'categoria_habilitacao' => ['A', 'E'],
    'dias_semana' => ['segunda', 'terca', 'quarta', 'quinta', 'sexta', 'sabado']
];

// Simular arquivo de foto
$_FILES = [
    'foto' => [
        'name' => 'hero-bg-portrait.desktop-_1600-x-1200-px_.webp',
        'type' => 'image/webp',
        'tmp_name' => '/tmp/phpXXXXXX',
        'error' => UPLOAD_ERR_OK,
        'size' => 123456
    ]
];

echo "=== TESTE DA API DE INSTRUTORES ===\n";
echo "REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "CONTENT_TYPE: " . $_SERVER['CONTENT_TYPE'] . "\n";
echo "\n";

echo "=== DADOS POST ===\n";
print_r($_POST);
echo "\n";

echo "=== ARQUIVOS ===\n";
print_r($_FILES);
echo "\n";

echo "=== DETECÇÃO DE FORMDATA ===\n";
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
$isFormData = strpos($contentType, 'multipart/form-data') !== false;
echo "É FormData? " . ($isFormData ? 'SIM' : 'NÃO') . "\n";
echo "\n";

if ($isFormData) {
    echo "=== PROCESSANDO FORMDATA ===\n";
    $data = $_POST;
    echo "Dados processados:\n";
    print_r($data);
    echo "\n";
    
    echo "ID do instrutor: " . ($data['id'] ?? 'NÃO ENCONTRADO') . "\n";
    echo "Nome: " . ($data['nome'] ?? 'NÃO ENCONTRADO') . "\n";
    
    if (empty($data['id'])) {
        echo "❌ ERRO: ID do instrutor é obrigatório\n";
    } else {
        echo "✅ ID do instrutor encontrado: " . $data['id'] . "\n";
    }
} else {
    echo "=== PROCESSANDO JSON ===\n";
    echo "Dados via file_get_contents('php://input')\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
