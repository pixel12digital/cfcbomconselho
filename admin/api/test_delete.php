<?php
// Teste simples para verificar se DELETE funciona
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = null;

if ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
}

echo json_encode([
    'success' => true,
    'method' => $method,
    'input' => $input,
    'message' => 'Teste de método ' . $method . ' funcionando!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
        'REQUEST_URI' => $_SERVER['REQUEST_URI'],
        'HTTP_HOST' => $_SERVER['HTTP_HOST'],
        'SERVER_SOFTWARE' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'
    ]
]);
?>
