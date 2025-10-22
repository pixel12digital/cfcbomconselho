<?php
/**
 * Arquivo de teste para verificar se a API de salas está funcionando
 */

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Desabilitar output de erro
ini_set('display_errors', 0);
error_reporting(0);

echo json_encode([
    'sucesso' => true,
    'mensagem' => 'API de teste funcionando!',
    'timestamp' => date('Y-m-d H:i:s'),
    'servidor' => $_SERVER['HTTP_HOST'] ?? 'desconhecido',
    'path' => $_SERVER['REQUEST_URI'] ?? 'desconhecido',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'desconhecido',
    'params' => [
        'get' => $_GET,
        'post' => $_POST
    ]
], JSON_UNESCAPED_UNICODE);
?>
