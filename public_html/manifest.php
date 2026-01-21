<?php
/**
 * Manifest PWA Dinâmico - White-Label
 * 
 * Retorna manifest.json dinâmico baseado no CFC atual
 * Substitui o manifest.json estático para permitir white-label
 * 
 * Funciona mesmo sem sessão/banco (usa fallback)
 */

// Headers primeiro (antes de qualquer output)
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache por 1 hora

// Valores padrão (fallback)
$cfcName = 'CFC Sistema de Gestão';
$cfcShortName = 'CFC Sistema';

// Tentar buscar nome do CFC apenas se houver sessão ativa
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['cfc_id'])) {
    try {
        // Definir constantes básicas
        if (!defined('ROOT_PATH')) {
            define('ROOT_PATH', dirname(__DIR__));
        }
        if (!defined('APP_PATH')) {
            define('APP_PATH', ROOT_PATH . '/app');
        }
        
        // Autoload
        if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
            require_once ROOT_PATH . '/vendor/autoload.php';
        } elseif (file_exists(APP_PATH . '/autoload.php')) {
            require_once APP_PATH . '/autoload.php';
        }
        
        // Carregar classes necessárias
        if (file_exists(APP_PATH . '/Config/Database.php') &&
            file_exists(APP_PATH . '/Config/Constants.php') &&
            file_exists(APP_PATH . '/Models/Model.php') &&
            file_exists(APP_PATH . '/Models/Cfc.php')) {
            
            require_once APP_PATH . '/Config/Database.php';
            require_once APP_PATH . '/Config/Constants.php';
            require_once APP_PATH . '/Models/Model.php';
            require_once APP_PATH . '/Models/Cfc.php';
            
            // Tentar buscar nome do CFC
            $cfcModel = new \App\Models\Cfc();
            $cfcName = $cfcModel->getCurrentName();
            $cfcShortName = strlen($cfcName) > 20 ? substr($cfcName, 0, 17) . '...' : $cfcName;
        }
    } catch (\Exception $e) {
        // Se falhar, usar valores padrão (já definidos acima)
    } catch (\Error $e) {
        // Se falhar, usar valores padrão
    }
}

// Manifest dinâmico
$manifest = [
    'name' => $cfcName,
    'short_name' => $cfcShortName,
    'description' => "Sistema de gestão para {$cfcName}",
    'start_url' => './dashboard',
    'scope' => './',
    'display' => 'standalone',
    'orientation' => 'portrait-primary',
    'theme_color' => '#023A8D',
    'background_color' => '#ffffff',
    'icons' => [
        [
            'src' => './icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => './icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ]
];

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
