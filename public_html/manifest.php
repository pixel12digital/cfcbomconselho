<?php
/**
 * Manifest PWA Dinâmico - White-Label
 * 
 * Retorna manifest.json dinâmico baseado no CFC atual
 * Versão simplificada que funciona mesmo sem sessão/banco
 */

// Headers primeiro
@header('Content-Type: application/manifest+json; charset=utf-8');
@header('Cache-Control: public, max-age=3600');

// Valores padrão (fallback)
$cfcName = 'CFC Sistema de Gestão';
$cfcShortName = 'CFC Sistema';

// Tentar buscar nome do CFC apenas se houver sessão ativa e arquivos existirem
if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['cfc_id'])) {
    $rootPath = dirname(__DIR__);
    $appPath = $rootPath . '/app';
    
    // Verificar se arquivos existem antes de tentar carregar
    if (file_exists($appPath . '/Models/Cfc.php') &&
        file_exists($appPath . '/Config/Database.php')) {
        
        try {
            // Autoload básico
            if (file_exists($rootPath . '/vendor/autoload.php')) {
                @require_once $rootPath . '/vendor/autoload.php';
            } elseif (file_exists($appPath . '/autoload.php')) {
                @require_once $appPath . '/autoload.php';
            }
            
            // Carregar classes
            @require_once $appPath . '/Config/Database.php';
            @require_once $appPath . '/Config/Constants.php';
            @require_once $appPath . '/Models/Model.php';
            @require_once $appPath . '/Models/Cfc.php';
            
            // Tentar buscar nome do CFC
            if (class_exists('\App\Models\Cfc')) {
                $cfcModel = new \App\Models\Cfc();
                $nome = $cfcModel->getCurrentName();
                if (!empty($nome) && $nome !== 'CFC Sistema') {
                    $cfcName = $nome;
                    $cfcShortName = strlen($cfcName) > 20 ? substr($cfcName, 0, 17) . '...' : $cfcName;
                }
            }
        } catch (\Exception $e) {
            // Silenciar erro, usar fallback
        } catch (\Error $e) {
            // Silenciar erro, usar fallback
        }
    }
}

// Manifest
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

// Output JSON
echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
