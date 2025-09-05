<?php
// Teste para verificar se o método DELETE está funcionando
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

echo json_encode([
    'success' => true,
    'method' => $_SERVER['REQUEST_METHOD'],
    'message' => 'Método ' . $_SERVER['REQUEST_METHOD'] . ' aceito com sucesso!',
    'timestamp' => date('Y-m-d H:i:s')
]);
?>
