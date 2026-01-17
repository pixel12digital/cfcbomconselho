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
        // Detectar ambiente: produção ou desenvolvimento local
        $appEnv = $_ENV['APP_ENV'] ?? 'local';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Se está em produção OU se a URI não contém o path local
        if ($appEnv === 'production' || strpos($requestUri, '/cfc-v.1/public_html') === false) {
            // Produção: path base vazio ou relativo ao subdomínio/pasta
            $base = '';
        } else {
            // Desenvolvimento local: path completo
            $base = '/cfc-v.1/public_html';
        }
        
        // Se path vazio ou apenas /, retornar base com barra final
        if ($path === '' || $path === '/') {
            return ($base ? rtrim($base, '/') . '/' : '/');
        }
        // Caso contrário, concatenar normalmente
        return ($base ? rtrim($base, '/') . '/' : '') . ltrim($path, '/');
    }
}

if (!function_exists('base_url')) {
    function base_url($path = '') {
        // URL completa (para redirects)
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        
        // Detectar ambiente: produção ou desenvolvimento local
        $appEnv = $_ENV['APP_ENV'] ?? 'local';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        
        // Se está em produção OU se a URI não contém o path local
        if ($appEnv === 'production' || strpos($requestUri, '/cfc-v.1/public_html') === false) {
            // Produção: sem path adicional
            $base = $protocol . '://' . $host;
        } else {
            // Desenvolvimento local: com path completo
            $base = $protocol . '://' . $host . '/cfc-v.1/public_html';
        }
        
        return rtrim($base, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset_url')) {
    function asset_url($path, $versioned = true) {
        $url = base_path('assets/' . ltrim($path, '/'));
        
        // Versionamento automático via filemtime (evita cache quebrado)
        if ($versioned) {
            $filePath = ROOT_PATH . '/assets/' . ltrim($path, '/');
            if (file_exists($filePath)) {
                $url .= '?v=' . filemtime($filePath);
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
