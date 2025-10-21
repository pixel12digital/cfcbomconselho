<?php
// API de teste para instrutores
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$instrutores = [
    [
        'id' => 1,
        'nome' => 'vinicius ricardo pontes vieira',
        'cpf' => '123.456.789-00',
        'ativo' => 1
    ],
    [
        'id' => 2,
        'nome' => 'João Silva Santos',
        'cpf' => '987.654.321-00',
        'ativo' => 1
    ],
    [
        'id' => 3,
        'nome' => 'Maria Oliveira Costa',
        'cpf' => '456.789.123-00',
        'ativo' => 1
    ]
];

echo json_encode([
    'success' => true,
    'instrutores' => $instrutores,
    'total' => count($instrutores)
], JSON_UNESCAPED_UNICODE);
