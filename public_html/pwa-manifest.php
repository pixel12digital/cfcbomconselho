<?php
/**
 * Manifest PWA Dinâmico - White-Label
 * 
 * Retorna manifest.json dinâmico baseado no CFC atual (tenant)
 * Fallback seguro para valores estáticos se não conseguir resolver CFC
 */

// Headers (definir antes de qualquer output)
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=300'); // 5 minutos - permite atualização rápida mas mantém performance

// Valores padrão (fallback)
$defaultManifest = [
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

// Tentar carregar dados dinâmicos do CFC (com fallback seguro)
try {
    // Incluir dependências mínimas
    $rootPath = dirname(__DIR__);
    
    // 1. Bootstrap (session e helpers)
    if (file_exists($rootPath . '/app/Bootstrap.php')) {
        require_once $rootPath . '/app/Bootstrap.php';
    }
    
    // 2. Constants (constantes do sistema)
    if (file_exists($rootPath . '/app/Config/Constants.php')) {
        require_once $rootPath . '/app/Config/Constants.php';
    }
    
    // 3. Env (configurações)
    if (file_exists($rootPath . '/app/Config/Env.php')) {
        require_once $rootPath . '/app/Config/Env.php';
        \App\Config\Env::load();
    }
    
    // 4. Database (conexão)
    if (file_exists($rootPath . '/app/Config/Database.php')) {
        require_once $rootPath . '/app/Config/Database.php';
    }
    
    // 5. Model base
    if (file_exists($rootPath . '/app/Models/Model.php')) {
        require_once $rootPath . '/app/Models/Model.php';
    }
    
    // 6. Model Cfc
    if (file_exists($rootPath . '/app/Models/Cfc.php')) {
        require_once $rootPath . '/app/Models/Cfc.php';
        
        // Buscar dados do CFC atual
        $cfcModel = new \App\Models\Cfc();
        $cfc = $cfcModel->getCurrent();
        
        if ($cfc && !empty($cfc['nome'])) {
            // Montar manifest dinâmico
            $cfcName = trim($cfc['nome']);
            $shortName = mb_substr($cfcName, 0, 15); // Limitar a 15 caracteres
            
            // Se o nome for muito longo, truncar e adicionar "..."
            if (mb_strlen($cfcName) > 15) {
                $shortName = mb_substr($cfcName, 0, 12) . '...';
            }
            
            // Verificar se há ícones PWA gerados para este CFC
            $icons = $defaultManifest['icons']; // Fallback para ícones padrão
            
            if (!empty($cfc['id'])) {
                $icon192 = './icons/' . $cfc['id'] . '/icon-192x192.png';
                $icon512 = './icons/' . $cfc['id'] . '/icon-512x512.png';
                
                // Verificar se os arquivos existem
                $rootPath = dirname(__DIR__);
                $icon192Path = $rootPath . '/public_html/icons/' . $cfc['id'] . '/icon-192x192.png';
                $icon512Path = $rootPath . '/public_html/icons/' . $cfc['id'] . '/icon-512x512.png';
                
                if (file_exists($icon192Path) && file_exists($icon512Path)) {
                    // Usar ícones dinâmicos do CFC
                    $icons = [
                        [
                            'src' => $icon192,
                            'sizes' => '192x192',
                            'type' => 'image/png',
                            'purpose' => 'any maskable'
                        ],
                        [
                            'src' => $icon512,
                            'sizes' => '512x512',
                            'type' => 'image/png',
                            'purpose' => 'any maskable'
                        ]
                    ];
                }
            }
            
            // Usar nome do CFC
            $manifest = [
                'name' => $cfcName,
                'short_name' => $shortName,
                'description' => 'Sistema de gestão para ' . $cfcName,
                'start_url' => './dashboard',
                'scope' => './',
                'display' => 'standalone',
                'orientation' => 'portrait-primary',
                'theme_color' => '#023A8D', // Pode ser dinâmico no futuro se houver campo theme_color
                'background_color' => '#ffffff',
                'icons' => $icons
            ];
            
        } else {
            // CFC não encontrado ou sem nome - usar fallback
            $manifest = $defaultManifest;
        }
    } else {
        // Model não existe - usar fallback
        $manifest = $defaultManifest;
    }
    
} catch (\Exception $e) {
    // Qualquer erro - usar fallback (nunca retornar 500)
    // Log do erro pode ser feito aqui se necessário, mas não expor ao cliente
    $manifest = $defaultManifest;
} catch (\Throwable $e) {
    // Capturar qualquer erro fatal também
    $manifest = $defaultManifest;
}

// Output JSON (sempre retorna 200 com manifest válido)
echo json_encode($manifest, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
