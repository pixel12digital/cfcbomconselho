<?php

// Inicialização
define('ROOT_PATH', dirname(__DIR__));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__);

// Autoload (necessário antes de usar classes)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente PRIMEIRO (para detectar ambiente)
use App\Config\Env;
Env::load();

// Configurar exibição de erros baseado no ambiente
$appEnv = $_ENV['APP_ENV'] ?? 'local';
if ($appEnv === 'production') {
    // Produção: ocultar erros, apenas logar
    error_reporting(E_ALL);
    ini_set('display_errors', 0);
    ini_set('log_errors', 1);
    // Garantir que o diretório de logs existe
    $logDir = ROOT_PATH . '/storage/logs';
    if (!is_dir($logDir)) {
        @mkdir($logDir, 0755, true);
    }
    ini_set('error_log', $logDir . '/php_errors.log');
} else {
    // Desenvolvimento: mostrar erros
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Bootstrap
require_once APP_PATH . '/Bootstrap.php';

// Router
use App\Core\Router;

$router = new Router();
$router->dispatch();
