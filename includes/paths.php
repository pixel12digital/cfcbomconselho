<?php
/**
 * Gerenciador de Caminhos do Sistema
 * Funciona tanto em desenvolvimento local quanto em produção
 */

// Função para obter o caminho base do projeto
function getProjectBasePath() {
    // Tentar diferentes estratégias para encontrar o caminho base
    
    // Estratégia 1: Usar __DIR__ do arquivo atual
    $currentDir = __DIR__;
    
    // Estratégia 2: Usar $_SERVER['DOCUMENT_ROOT'] se disponível
    if (isset($_SERVER['DOCUMENT_ROOT'])) {
        $docRoot = $_SERVER['DOCUMENT_ROOT'];
        
        // Se estamos em uma subpasta, ajustar o caminho
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        
        if ($scriptDir !== '/' && $scriptDir !== '') {
            $projectPath = $docRoot . $scriptDir;
            // Remover a parte 'admin/api' se estivermos em uma API
            if (strpos($projectPath, '/admin/api') !== false) {
                $projectPath = dirname(dirname(dirname($projectPath)));
            } elseif (strpos($projectPath, '/admin') !== false) {
                $projectPath = dirname($projectPath);
            }
            
            if (is_dir($projectPath) && file_exists($projectPath . '/includes/config.php')) {
                return $projectPath;
            }
        }
    }
    
    // Estratégia 3: Navegar a partir do diretório atual
    $searchPaths = [
        $currentDir,                    // includes/
        dirname($currentDir),           // raiz do projeto
        dirname(dirname($currentDir)),  // pai da raiz
        dirname(dirname(dirname($currentDir))), // avô da raiz
    ];
    
    foreach ($searchPaths as $path) {
        if (is_dir($path) && file_exists($path . '/includes/config.php')) {
            return $path;
        }
    }
    
    // Estratégia 4: Fallback para o diretório atual
    return $currentDir;
}

// Função para incluir arquivos de forma segura
function safeInclude($relativePath) {
    $basePath = getProjectBasePath();
    $fullPath = $basePath . '/' . ltrim($relativePath, '/');
    
    if (!file_exists($fullPath)) {
        throw new Exception("Arquivo não encontrado: {$fullPath}");
    }
    
    return require_once $fullPath;
}

// Função para obter caminho absoluto
function getAbsolutePath($relativePath) {
    $basePath = getProjectBasePath();
    return $basePath . '/' . ltrim($relativePath, '/');
}

// Função para obter URL base
function getBaseUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    
    // Remover 'admin/api' se estivermos em uma API
    if (strpos($scriptName, '/admin/api') !== false) {
        $scriptName = dirname(dirname(dirname($scriptName)));
    } elseif (strpos($scriptName, '/admin') !== false) {
        $scriptName = dirname($scriptName);
    }
    
    return $protocol . '://' . $host . $scriptName;
}

// Definir constantes globais de caminho
if (!defined('PROJECT_BASE_PATH')) {
    define('PROJECT_BASE_PATH', getProjectBasePath());
}

if (!defined('INCLUDES_PATH')) {
    define('INCLUDES_PATH', PROJECT_BASE_PATH . '/includes');
}

if (!defined('ADMIN_PATH')) {
    define('ADMIN_PATH', PROJECT_BASE_PATH . '/admin');
}

if (!defined('ASSETS_PATH')) {
    define('ASSETS_PATH', PROJECT_BASE_PATH . '/assets');
}

if (!defined('UPLOADS_PATH')) {
    define('UPLOADS_PATH', PROJECT_BASE_PATH . '/uploads');
}

if (!defined('LOGS_PATH')) {
    define('LOGS_PATH', PROJECT_BASE_PATH . '/logs');
}

if (!defined('BACKUPS_PATH')) {
    define('BACKUPS_PATH', PROJECT_BASE_PATH . '/backups');
}

// Log de debug para desenvolvimento
if (defined('LOG_ENABLED') && LOG_ENABLED && defined('LOG_LEVEL') && LOG_LEVEL === 'DEBUG') {
    error_log('[PATHS] Caminho base do projeto: ' . PROJECT_BASE_PATH);
    error_log('[PATHS] Caminho dos includes: ' . INCLUDES_PATH);
    error_log('[PATHS] Caminho do admin: ' . ADMIN_PATH);
}
?>
