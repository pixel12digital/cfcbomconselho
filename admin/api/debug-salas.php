<?php
/**
 * Debug para verificar problemas com a API de salas
 */

// Headers
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Informações de debug
$debug = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? 'desconhecido',
    'request_uri' => $_SERVER['REQUEST_URI'] ?? 'desconhecido',
    'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'desconhecido',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'desconhecido',
    'method' => $_SERVER['REQUEST_METHOD'] ?? 'desconhecido',
    'get_params' => $_GET,
    'post_params' => $_POST,
    'file_exists' => file_exists(__FILE__),
    'file_readable' => is_readable(__FILE__),
    'directory_exists' => is_dir(__DIR__),
    'directory_readable' => is_readable(__DIR__),
    'php_version' => PHP_VERSION,
    'extensions' => [
        'pdo' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'json' => extension_loaded('json')
    ]
];

// Testar conexão com banco
try {
    $dsn = "mysql:host=auth-db803.hstgr.io;dbname=u502697186_cfcbomconselho;charset=utf8mb4";
    $pdo = new PDO($dsn, 'u502697186_cfcbomconselho', 'Los@ngo#081081', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
    $debug['database_connection'] = 'success';
    
    // Testar query simples
    $stmt = $pdo->query("SELECT 1 as test");
    $result = $stmt->fetch();
    $debug['database_query'] = 'success';
    
} catch (Exception $e) {
    $debug['database_connection'] = 'failed';
    $debug['database_error'] = $e->getMessage();
}

echo json_encode([
    'sucesso' => true,
    'mensagem' => 'Debug informações',
    'debug' => $debug
], JSON_UNESCAPED_UNICODE);
?>
