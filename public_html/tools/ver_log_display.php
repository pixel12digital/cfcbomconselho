<?php
// Define ROOT_PATH se não estiver definido
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__, 2));
}

$logFile = ROOT_PATH . '/storage/logs/display_logo.log';

header('Content-Type: text/plain; charset=utf-8');
header('X-Content-Type-Options: nosniff');

echo "=== LOG DE EXIBIÇÃO DE LOGO ===\n\n";
echo "Caminho do Log: " . $logFile . "\n\n";

if (file_exists($logFile)) {
    echo "Conteúdo do Log:\n";
    echo "--------------------------------------------------\n";
    readfile($logFile);
    echo "\n--------------------------------------------------\n";
} else {
    echo "O arquivo de log não foi encontrado em: " . $logFile . "\n";
    echo "Verifique se o diretório 'storage/logs/' existe e tem permissões de escrita.\n";
}

echo "\n=== FIM DO LOG ===\n";
?>
