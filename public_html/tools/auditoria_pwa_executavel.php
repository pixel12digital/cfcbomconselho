<?php
/**
 * Auditoria PWA Executável
 * 
 * Este script testa e valida todos os requisitos do PWA
 * Execute em produção via: https://seudominio.com/tools/auditoria_pwa_executavel.php
 * 
 * IMPORTANTE: Não altera nada, apenas diagnostica
 */

header('Content-Type: text/html; charset=utf-8');

$results = [];
$errors = [];
$warnings = [];

// Helper para adicionar resultado
function addResult($category, $check, $status, $message, $details = '') {
    global $results;
    $results[] = [
        'category' => $category,
        'check' => $check,
        'status' => $status, // 'ok', 'error', 'warning'
        'message' => $message,
        'details' => $details
    ];
}

// Helper para adicionar erro
function addError($check, $message, $details = '') {
    global $errors;
    $errors[] = [
        'check' => $check,
        'message' => $message,
        'details' => $details
    ];
    addResult('ERRO', $check, 'error', $message, $details);
}

// Helper para adicionar warning
function addWarning($check, $message, $details = '') {
    global $warnings;
    $warnings[] = [
        'check' => $check,
        'message' => $message,
        'details' => $details
    ];
    addResult('AVISO', $check, 'warning', $message, $details);
}

// ============================================
// 1. VERIFICAÇÃO HTTPS
// ============================================

$isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
           || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
           || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

if ($isHttps) {
    addResult('HTTPS', 'Protocolo HTTPS', 'ok', '✅ Site está sendo servido via HTTPS', 
        'Protocolo: ' . ($_SERVER['HTTPS'] ?? $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '443'));
} else {
    addError('Protocolo HTTPS', '❌ Site NÃO está em HTTPS', 
        'PWA requer HTTPS em produção (exceto localhost). Protocolo atual: ' . ($_SERVER['SERVER_PROTOCOL'] ?? 'HTTP'));
}

// Verificar se há redirect HTTP → HTTPS
$hasHttpsRedirect = false;
$htaccessPath = __DIR__ . '/../.htaccess';
if (file_exists($htaccessPath)) {
    $htaccessContent = file_get_contents($htaccessPath);
    if (preg_match('/RewriteCond.*HTTPS|RewriteRule.*https/i', $htaccessContent)) {
        $hasHttpsRedirect = true;
        addResult('HTTPS', 'Redirect HTTP→HTTPS', 'ok', '✅ .htaccess contém regras de redirect HTTPS');
    } else {
        addWarning('Redirect HTTP→HTTPS', '⚠️ .htaccess não contém regras explícitas de redirect HTTPS', 
            'Pode estar configurado no servidor (Apache/Nginx) ou via Cloudflare');
    }
} else {
    addWarning('Redirect HTTP→HTTPS', '⚠️ Arquivo .htaccess não encontrado', 
        'Redirect pode estar configurado no servidor');
}

// ============================================
// 2. VERIFICAÇÃO MANIFEST
// ============================================

$manifestPath = __DIR__ . '/../manifest.json';
if (file_exists($manifestPath)) {
    addResult('Manifest', 'Arquivo existe', 'ok', '✅ manifest.json encontrado', $manifestPath);
    
    $manifestContent = file_get_contents($manifestPath);
    $manifest = json_decode($manifestContent, true);
    
    if ($manifest === null) {
        addError('Manifest JSON válido', '❌ manifest.json contém JSON inválido', 
            'Erro: ' . json_last_error_msg());
    } else {
        addResult('Manifest', 'JSON válido', 'ok', '✅ manifest.json é JSON válido');
        
        // Verificar campos obrigatórios
        $requiredFields = ['name', 'short_name', 'start_url', 'display', 'icons'];
        foreach ($requiredFields as $field) {
            if (isset($manifest[$field])) {
                addResult('Manifest', "Campo: $field", 'ok', "✅ Campo '$field' existe", 
                    'Valor: ' . (is_array($manifest[$field]) ? json_encode($manifest[$field]) : $manifest[$field]));
            } else {
                addError("Campo: $field", "❌ Campo obrigatório '$field' não existe no manifest");
            }
        }
        
        // Verificar se é hardcoded
        if (isset($manifest['name']) && $manifest['name'] === 'CFC Sistema de Gestão') {
            addWarning('Manifest dinâmico', '⚠️ Manifest usa valores hardcoded', 
                'Nome: "' . $manifest['name'] . '" - Deve ser dinâmico por CFC');
        }
    }
} else {
    addError('Manifest existe', '❌ manifest.json NÃO encontrado', $manifestPath);
}

// Verificar se manifest está acessível via URL
$baseUrl = ($isHttps ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$manifestUrl = $baseUrl . '/manifest.json';
$manifestAccessible = @file_get_contents($manifestUrl);
if ($manifestAccessible !== false) {
    addResult('Manifest', 'Acessível via URL', 'ok', '✅ manifest.json acessível via HTTP', $manifestUrl);
} else {
    addError('Manifest acessível', '❌ manifest.json NÃO acessível via URL', 
        'URL testada: ' . $manifestUrl . ' - Verifique permissões e .htaccess');
}

// ============================================
// 3. VERIFICAÇÃO SERVICE WORKER
// ============================================

$swPath = __DIR__ . '/../sw.js';
if (file_exists($swPath)) {
    addResult('Service Worker', 'Arquivo existe', 'ok', '✅ sw.js encontrado', $swPath);
    
    // Verificar se está registrado no shell.php
    $shellPath = __DIR__ . '/../../app/Views/layouts/shell.php';
    if (file_exists($shellPath)) {
        $shellContent = file_get_contents($shellPath);
        if (strpos($shellContent, 'serviceWorker') !== false || strpos($shellContent, 'sw.js') !== false) {
            addResult('Service Worker', 'Registrado no HTML', 'ok', '✅ Service Worker está registrado no shell.php');
        } else {
            addError('Service Worker registrado', '❌ Service Worker NÃO está registrado no shell.php');
        }
    }
} else {
    addError('Service Worker existe', '❌ sw.js NÃO encontrado', $swPath);
}

// Verificar se sw.js está acessível via URL
$swUrl = $baseUrl . '/sw.js';
$swAccessible = @file_get_contents($swUrl);
if ($swAccessible !== false) {
    addResult('Service Worker', 'Acessível via URL', 'ok', '✅ sw.js acessível via HTTP', $swUrl);
} else {
    addError('Service Worker acessível', '❌ sw.js NÃO acessível via URL', 
        'URL testada: ' . $swUrl . ' - Verifique permissões e .htaccess');
}

// ============================================
// 4. VERIFICAÇÃO ÍCONES
// ============================================

$iconsDir = __DIR__ . '/../icons';
if (is_dir($iconsDir)) {
    addResult('Ícones', 'Diretório existe', 'ok', '✅ Diretório /icons/ existe', $iconsDir);
    
    $iconFiles = glob($iconsDir . '/*.png');
    if (empty($iconFiles)) {
        addError('Ícones existem', '❌ Diretório /icons/ está VAZIO', 
            'Necessário gerar ícones 192x192 e 512x512');
    } else {
        addResult('Ícones', 'Arquivos encontrados', 'ok', '✅ ' . count($iconFiles) . ' arquivo(s) de ícone encontrado(s)');
        
        // Verificar ícones específicos
        $requiredIcons = [
            'icon-192x192.png' => 192,
            'icon-512x512.png' => 512
        ];
        
        foreach ($requiredIcons as $filename => $size) {
            $iconPath = $iconsDir . '/' . $filename;
            if (file_exists($iconPath)) {
                $imageInfo = @getimagesize($iconPath);
                if ($imageInfo !== false) {
                    $actualSize = $imageInfo[0]; // width
                    if ($actualSize == $size) {
                        addResult('Ícones', $filename, 'ok', "✅ $filename existe e tem tamanho correto ({$size}x{$size})");
                    } else {
                        addWarning($filename, "⚠️ $filename existe mas tamanho incorreto", 
                            "Esperado: {$size}x{$size}, Encontrado: {$actualSize}x{$actualSize}");
                    }
                } else {
                    addError($filename, "❌ $filename existe mas não é uma imagem válida");
                }
            } else {
                addError($filename, "❌ $filename NÃO existe", "Necessário: $iconPath");
            }
        }
    }
} else {
    addError('Diretório ícones', '❌ Diretório /icons/ NÃO existe', $iconsDir);
}

// Verificar se ícones estão acessíveis via URL
if (is_dir($iconsDir) && !empty($iconFiles)) {
    foreach (['icon-192x192.png', 'icon-512x512.png'] as $iconFile) {
        $iconUrl = $baseUrl . '/icons/' . $iconFile;
        $iconAccessible = @get_headers($iconUrl, 1);
        if ($iconAccessible !== false && strpos($iconAccessible[0], '200') !== false) {
            addResult('Ícones', "Acessível: $iconFile", 'ok', "✅ $iconFile acessível via HTTP", $iconUrl);
        } else {
            addError("Ícone acessível: $iconFile", "❌ $iconFile NÃO acessível via URL", 
                'URL testada: ' . $iconUrl);
        }
    }
}

// ============================================
// 5. VERIFICAÇÃO SCRIPT GERADOR DE ÍCONES
// ============================================

$generateIconsPath = __DIR__ . '/../generate-icons.php';
if (file_exists($generateIconsPath)) {
    addResult('Script Gerador', 'Arquivo existe', 'ok', '✅ generate-icons.php encontrado', $generateIconsPath);
    
    // Verificar se GD está habilitado
    if (extension_loaded('gd')) {
        addResult('Script Gerador', 'Extensão GD', 'ok', '✅ Extensão GD está habilitada', 
            'Versão: ' . phpversion('gd'));
    } else {
        addError('Extensão GD', '❌ Extensão GD NÃO está habilitada', 
            'Necessário para gerar ícones. Execute: apt-get install php-gd (Linux) ou habilite no php.ini');
    }
} else {
    addWarning('Script Gerador', '⚠️ generate-icons.php não encontrado', 
        'Pode ter sido removido após gerar ícones');
}

// ============================================
// 6. VERIFICAÇÃO INSTALLABILITY
// ============================================

// Verificar se todos os requisitos básicos estão OK
$installabilityReqs = [
    'HTTPS' => $isHttps,
    'Manifest existe' => file_exists($manifestPath),
    'Manifest válido' => isset($manifest) && $manifest !== null,
    'SW existe' => file_exists($swPath),
    'SW acessível' => $swAccessible !== false,
    'Ícones existem' => !empty($iconFiles) && count($iconFiles) >= 2
];

$allReqsMet = true;
foreach ($installabilityReqs as $req => $met) {
    if (!$met) {
        $allReqsMet = false;
        break;
    }
}

if ($allReqsMet) {
    addResult('Installability', 'Requisitos básicos', 'ok', 
        '✅ Todos os requisitos básicos para installability estão OK', 
        'Teste no Chrome DevTools → Application → Manifest para confirmar');
} else {
    addWarning('Installability', '⚠️ Alguns requisitos para installability não estão OK', 
        'Verifique erros acima. PWA pode não ser installable ainda.');
}

// ============================================
// 7. OUTPUT HTML
// ============================================

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria PWA Executável</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #023A8D 0%, #0056b3 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 28px; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            padding: 30px;
            background: #f8f9fa;
        }
        .summary-card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .summary-card h3 { font-size: 36px; margin-bottom: 10px; }
        .summary-card.ok { color: #28a745; }
        .summary-card.warning { color: #ffc107; }
        .summary-card.error { color: #dc3545; }
        .content {
            padding: 30px;
        }
        .category {
            margin-bottom: 40px;
        }
        .category h2 {
            color: #023A8D;
            font-size: 22px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #023A8D;
        }
        .check-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 6px;
            border-left: 4px solid;
        }
        .check-item.ok {
            background: #d4edda;
            border-color: #28a745;
        }
        .check-item.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .check-item.error {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .check-item strong {
            display: block;
            margin-bottom: 5px;
            font-size: 16px;
        }
        .check-item .details {
            font-size: 14px;
            color: #666;
            margin-top: 5px;
            font-family: 'Courier New', monospace;
        }
        .instructions {
            background: #e7f3ff;
            border-left: 4px solid #023A8D;
            padding: 20px;
            margin: 30px 0;
            border-radius: 6px;
        }
        .instructions h3 {
            color: #023A8D;
            margin-bottom: 10px;
        }
        .instructions ol {
            margin-left: 20px;
        }
        .instructions li {
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Auditoria PWA Executável</h1>
            <p>Diagnóstico completo do estado atual do PWA</p>
        </div>
        
        <div class="summary">
            <div class="summary-card ok">
                <h3><?= count(array_filter($results, fn($r) => $r['status'] === 'ok')) ?></h3>
                <p>✅ OK</p>
            </div>
            <div class="summary-card warning">
                <h3><?= count($warnings) ?></h3>
                <p>⚠️ Avisos</p>
            </div>
            <div class="summary-card error">
                <h3><?= count($errors) ?></h3>
                <p>❌ Erros</p>
            </div>
        </div>
        
        <div class="content">
            <?php
            $currentCategory = '';
            foreach ($results as $result):
                if ($currentCategory !== $result['category']):
                    if ($currentCategory !== ''):
                        echo '</div>';
                    endif;
                    $currentCategory = $result['category'];
                    echo '<div class="category">';
                    echo '<h2>' . htmlspecialchars($result['category']) . '</h2>';
                endif;
            ?>
                <div class="check-item <?= $result['status'] ?>">
                    <strong><?= htmlspecialchars($result['check']) ?></strong>
                    <div><?= htmlspecialchars($result['message']) ?></div>
                    <?php if (!empty($result['details'])): ?>
                        <div class="details"><?= htmlspecialchars($result['details']) ?></div>
                    <?php endif; ?>
                </div>
            <?php
            endforeach;
            if ($currentCategory !== ''):
                echo '</div>';
            endif;
            ?>
            
            <div class="instructions">
                <h3>📋 Próximos Passos para Validação Manual</h3>
                <ol>
                    <li><strong>Chrome DevTools → Application → Manifest:</strong>
                        <ul>
                            <li>Abra o Chrome DevTools (F12)</li>
                            <li>Vá em Application → Manifest</li>
                            <li>Verifique se o manifest está sendo carregado</li>
                            <li>Anote qualquer erro ou warning</li>
                        </ul>
                    </li>
                    <li><strong>Lighthouse PWA Score:</strong>
                        <ul>
                            <li>Abra o Chrome DevTools (F12)</li>
                            <li>Vá em Lighthouse</li>
                            <li>Selecione "Progressive Web App"</li>
                            <li>Execute e anote o score</li>
                        </ul>
                    </li>
                    <li><strong>Installability Test:</strong>
                        <ul>
                            <li>Após gerar ícones e garantir HTTPS</li>
                            <li>Verifique se o Chrome mostra o botão de instalação nativo</li>
                            <li>Ou use: Chrome DevTools → Application → Manifest → "Add to homescreen"</li>
                        </ul>
                    </li>
                    <li><strong>Console/Network Errors:</strong>
                        <ul>
                            <li>Abra o Chrome DevTools → Console</li>
                            <li>Verifique erros relacionados a manifest, icons, ou service worker</li>
                            <li>Abra Chrome DevTools → Network</li>
                            <li>Recarregue a página e verifique se manifest.json, sw.js e ícones carregam sem erro</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</body>
</html>
