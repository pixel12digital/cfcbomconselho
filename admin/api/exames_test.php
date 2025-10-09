<?php
/**
 * API de Teste para Exames - Debug
 */

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/exames_test_errors.log');

header('Content-Type: application/json; charset=utf-8');

try {
    // Testar diferentes caminhos
    $possiblePaths = [
        __DIR__ . '/../../includes/config.php',  // Produção
        __DIR__ . '/../includes/config.php',     // Desenvolvimento
        dirname(__DIR__, 2) . '/includes/config.php'  // Alternativo
    ];
    
    echo json_encode([
        'status' => 'testing',
        'possible_paths' => $possiblePaths,
        'current_dir' => __DIR__,
        'dirname_2' => dirname(__DIR__, 2),
        'file_exists' => []
    ]);
    
    foreach ($possiblePaths as $index => $path) {
        $exists = file_exists($path);
        echo "\n\nPath $index: $path - Exists: " . ($exists ? 'YES' : 'NO');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
?>
