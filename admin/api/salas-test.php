<?php
// API de teste para salas
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

$salas = [
    [
        'id' => 1,
        'nome' => 'Sala 01 Teste',
        'capacidade' => 30
    ],
    [
        'id' => 2,
        'nome' => 'Sala 02',
        'capacidade' => 25
    ],
    [
        'id' => 3,
        'nome' => 'Sala 03',
        'capacidade' => 35
    ]
];

echo json_encode([
    'success' => true,
    'salas' => $salas,
    'total' => count($salas)
], JSON_UNESCAPED_UNICODE);
