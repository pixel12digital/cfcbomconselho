<?php

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400, // 24 horas
        'cookie_httponly' => true,
        'cookie_secure' => isset($_SERVER['HTTPS']),
        'cookie_samesite' => 'Strict'
    ]);
}

// Timezone
date_default_timezone_set('America/Sao_Paulo');

// Helper functions
if (!function_exists('base_path')) {
    function base_path($path = '') {
        // Base calculada pelo caminho real do script (mesma lógica do asset_url e base_url)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        
        // Se estamos em /cfc-v.1/public_html/index.php -> $base = /cfc-v.1/public_html
        // Se estamos em /index.php -> $base = / (raiz)
        if ($base === '' || $base === '.') {
            $base = '/';
        } elseif (substr($base, 0, 1) !== '/') {
            $base = '/' . $base;
        }
        
        // Limpar o path
        $path = ltrim($path, '/');
        
        // Montar path: base + / + path (ou apenas base se path vazio)
        return $base . ($path ? '/' . $path : '');
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        // URL completa (para redirects)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Base calculada pelo caminho real do script (mesma lógica do asset_url)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        
        // Se estamos em /cfc-v.1/public_html/index.php -> $basePath = /cfc-v.1/public_html
        // Se estamos em /index.php -> $basePath = / (raiz)
        if ($basePath === '' || $basePath === '.') {
            $basePath = '/';
        } elseif (substr($basePath, 0, 1) !== '/') {
            $basePath = '/' . $basePath;
        }
        
        // Montar URL completa: protocolo + host + basePath + path
        $path = ltrim($path, '/');
        $url = $protocol . '://' . $host . $basePath . ($path ? '/' . $path : '');
        
        return $url;
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path, $versioned = true) {
        // Limpar o path
        $path = ltrim($path, '/'); // ex: css/tokens.css
        
        // Base calculada pelo caminho real do script (funciona em subpastas)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $base = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        
        // Se estamos em /cfc-v.1/public_html/index.php -> $base = /cfc-v.1/public_html
        // Se estamos em /index.php -> $base = / (raiz)
        // Garantir que $base sempre comece com / ou seja vazio (raiz)
        if ($base === '' || $base === '.') {
            $base = '/';
        } elseif (substr($base, 0, 1) !== '/') {
            $base = '/' . $base;
        }
        
        // Montar URL: base + /assets/ + path
        $url = $base . '/assets/' . $path;
        
        // Cache bust (versionamento)
        if ($versioned) {
            $fileOnDisk = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/public_html/assets/' . $path;
            if (!file_exists($fileOnDisk)) {
                // Tentar em assets/ na raiz também
                $fileOnDisk = (defined('ROOT_PATH') ? ROOT_PATH : dirname(__DIR__)) . '/assets/' . $path;
            }
            if (is_file($fileOnDisk)) {
                $url .= '?v=' . filemtime($fileOnDisk);
            }
        }
        
        return $url;
    }
}

if (!function_exists('redirect')) {
    function redirect($url) {
        header('Location: ' . $url);
        exit;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('csrf_verify')) {
    function csrf_verify($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
}
