<?php
/**
 * Manifest PWA Dinâmico - White-Label
 * 
 * Retorna manifest.json dinâmico baseado no CFC atual
 * Substitui o manifest.json estático para permitir white-label
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache por 1 hora

// Valores padrão (fallback)
$cfcName = 'CFC Sistema de Gestão';
$cfcShortName = 'CFC Sistema';

// Tentar carregar dados do CFC (com tratamento de erros)
try {
    // Carregar configurações
    require_once __DIR__ . '/../app/Bootstrap.php';
    require_once __DIR__ . '/../app/Config/Database.php';
    require_once __DIR__ . '/../app/Config/Env.php';
    require_once __DIR__ . '/../app/Models/Cfc.php';
    
    use App\Config\Env;
    use App\Models\Cfc;
    
    // Carregar .env
    Env::load();
    
    // Iniciar sessão se não estiver iniciada (Bootstrap já faz isso, mas garantir)
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Buscar dados do CFC atual (com fallback se falhar)
    try {
        $cfcModel = new Cfc();
        $cfcName = $cfcModel->getCurrentName();
        $cfcShortName = strlen($cfcName) > 20 ? substr($cfcName, 0, 17) . '...' : $cfcName;
    } catch (\Exception $e) {
        // Se falhar, usar valores padrão (já definidos acima)
        error_log("Erro ao buscar nome do CFC no manifest: " . $e->getMessage());
    }
} catch (\Exception $e) {
    // Se houver erro ao carregar classes/config, usar valores padrão
    error_log("Erro ao carregar manifest dinâmico: " . $e->getMessage());
}

// Base path para assets (relativo ao manifest)
$basePath = './';

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

// Se CFC tem logo, usar logo do CFC (futuro)
// $cfcLogo = $cfcModel->getCurrentLogo();
// if ($cfcLogo) {
//     // Gerar ícones do logo e usar aqui
// }

echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
