<?php
/**
 * Manifest PWA Dinâmico - White-Label
 * 
 * Versão mínima que sempre funciona
 * Retorna manifest.json com paths relativos
 * 
 * NOTA: manifest.php bloqueado por regra do servidor, usar pwa-manifest.php
 */

// Headers
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

// Manifest (valores padrão - white-label será implementado depois quando resolver erro 500)
$manifest = [
    'name' => 'CFC Sistema de Gestão',
    'short_name' => 'CFC Sistema',
    'description' => 'Sistema de gestão para Centros de Formação de Condutores',
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
