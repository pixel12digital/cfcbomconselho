<?php
/**
 * Script de Diagnóstico PWA
 * Verifica todos os requisitos para instalação PWA
 * 
 * Acesse: https://cfcbomconselho.com.br/debug_pwa.php
 */

// Garantir que o output não seja bufferizado
if (ob_get_level()) {
    ob_end_clean();
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico PWA - CFC Bom Conselho</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        h1 { color: #2c3e50; }
        h2 { color: #3498db; border-bottom: 2px solid #3498db; padding-bottom: 5px; }
        h3 { color: #7f8c8d; }
        table { border-collapse: collapse; width: 100%; margin: 20px 0; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background: #3498db; color: white; }
        .pass { color: #27ae60; font-weight: bold; }
        .fail { color: #e74c3c; font-weight: bold; }
        .note { background: #e8f4f8; border-left: 3px solid #3498db; padding: 12px; margin: 10px 0; }
        .warning { background: #fff3cd; border-left: 3px solid #ffc107; padding: 12px; margin: 10px 0; }
        .error { background: #f8d7da; border-left: 3px solid #dc3545; padding: 12px; margin: 10px 0; }
        ul { list-style: none; padding: 0; }
        li { padding: 8px 0; border-bottom: 1px solid #e1e5e9; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico PWA - CFC Bom Conselho</h1>
<?php
// Detectar base URL corretamente
$protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') 
            ? 'https' : 'http';
$baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
if ($basePath === '/' || $basePath === '\\') {
    $basePath = '';
}
?>
    <div class="note">
        <strong>Data:</strong> <?php echo date('Y-m-d H:i:s'); ?><br>
        <strong>URL Base:</strong> <?php echo htmlspecialchars($baseUrl); ?><br>
        <strong>Script Path:</strong> <?php echo htmlspecialchars($_SERVER['SCRIPT_NAME']); ?>
    </div>
<?php

// URLs para testar
$testUrls = [
    'instrutor' => $baseUrl . '/login.php?type=instrutor',
    'aluno' => $baseUrl . '/login.php?type=aluno'
];

$results = [];

function checkUrl($url, $description) {
    $result = [
        'url' => $url,
        'description' => $description,
        'status' => null,
        'content_type' => null,
        'first_line' => null,
        'error' => null,
        'pass' => false
    ];
    
    try {
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: PWA-Diagnostic/1.0',
                'timeout' => 10,
                'ignore_errors' => true
            ]
        ]);
        
        $response = @file_get_contents($url, false, $context);
        
        if ($response === false) {
            $result['error'] = 'Falha ao conectar';
            return $result;
        }
        
        // Extrair status code dos headers
        if (isset($http_response_header)) {
            $statusLine = $http_response_header[0];
            preg_match('/HTTP\/\d\.\d\s+(\d+)/', $statusLine, $matches);
            $result['status'] = isset($matches[1]) ? (int)$matches[1] : null;
            
            // Extrair Content-Type
            foreach ($http_response_header as $header) {
                if (stripos($header, 'Content-Type:') === 0) {
                    $result['content_type'] = trim(substr($header, 13));
                    break;
                }
            }
        }
        
        // Primeira linha do body
        $lines = explode("\n", $response);
        $result['first_line'] = trim($lines[0]);
        
        // Verificar se passou
        $result['pass'] = ($result['status'] === 200);
        
    } catch (Exception $e) {
        $result['error'] = $e->getMessage();
    }
    
    return $result;
}

function extractManifestFromHtml($html) {
    // Procurar por <link rel="manifest" href="...">
    if (preg_match('/<link[^>]*rel=["\']manifest["\'][^>]*href=["\']([^"\']+)["\']/', $html, $matches)) {
        return $matches[1];
    }
    return null;
}

function parseJson($json) {
    $decoded = json_decode($json, true);
    return [
        'valid' => (json_last_error() === JSON_ERROR_NONE),
        'error' => json_last_error() !== JSON_ERROR_NONE ? json_last_error_msg() : null,
        'data' => $decoded
    ];
}

// Testar cada URL
foreach ($testUrls as $type => $url) {
    $results[$type] = [
        'login_page' => null,
        'manifest' => null,
        'manifest_json' => null,
        'start_url' => null,
        'icons' => [],
        'service_worker' => null
    ];
    
    echo "<h2>Testando: {$type}</h2>\n";
    
    // 1. Testar página de login
    echo "<h3>1. Página de Login</h3>\n";
    $loginResult = checkUrl($url, 'Página de login');
    $results[$type]['login_page'] = $loginResult;
    
    if ($loginResult['pass']) {
        echo "✅ Status: {$loginResult['status']}<br>\n";
        echo "Content-Type: {$loginResult['content_type']}<br>\n";
        
        // Extrair manifest do HTML
        $html = file_get_contents($url);
        $manifestHref = extractManifestFromHtml($html);
        
        if ($manifestHref) {
            // Converter para URL absoluta se necessário
            if (strpos($manifestHref, 'http') !== 0) {
                if ($manifestHref[0] === '/') {
                    $manifestHref = $baseUrl . $manifestHref;
                } else {
                    $manifestHref = $baseUrl . '/' . $manifestHref;
                }
            }
            
            echo "✅ Manifest encontrado: {$manifestHref}<br>\n";
            
            // 2. Testar manifest
            echo "<h3>2. Manifest JSON</h3>\n";
            $manifestResult = checkUrl($manifestHref, 'Manifest JSON');
            $results[$type]['manifest'] = $manifestResult;
            
            if ($manifestResult['pass']) {
                echo "✅ Status: {$manifestResult['status']}<br>\n";
                echo "Content-Type: {$manifestResult['content_type']}<br>\n";
                
                // Verificar se é JSON
                if (stripos($manifestResult['content_type'], 'application/json') !== false || 
                    stripos($manifestResult['content_type'], 'text/json') !== false) {
                    echo "✅ Content-Type correto (JSON)<br>\n";
                    
                    // Parsear JSON
                    $manifestJson = file_get_contents($manifestHref);
                    $parsed = parseJson($manifestJson);
                    
                    if ($parsed['valid']) {
                        echo "✅ JSON válido<br>\n";
                        $results[$type]['manifest_json'] = ['valid' => true, 'data' => $parsed['data']];
                        
                        $manifestData = $parsed['data'];
                        
                        // 3. Testar start_url
                        if (isset($manifestData['start_url'])) {
                            echo "<h3>3. Start URL</h3>\n";
                            $startUrl = $manifestData['start_url'];
                            if ($startUrl[0] === '/') {
                                $startUrl = $baseUrl . $startUrl;
                            }
                            
                            $startUrlResult = checkUrl($startUrl, 'Start URL');
                            $results[$type]['start_url'] = $startUrlResult;
                            
                            if ($startUrlResult['pass']) {
                                echo "✅ Status: {$startUrlResult['status']}<br>\n";
                            } else {
                                echo "❌ Status: {$startUrlResult['status']} - {$startUrlResult['error']}<br>\n";
                            }
                        }
                        
                        // 4. Testar ícones
                        if (isset($manifestData['icons']) && is_array($manifestData['icons'])) {
                            echo "<h3>4. Ícones</h3>\n";
                            foreach ($manifestData['icons'] as $icon) {
                                if (isset($icon['src'])) {
                                    $iconUrl = $icon['src'];
                                    if ($iconUrl[0] === '/') {
                                        $iconUrl = $baseUrl . $iconUrl;
                                    }
                                    
                                    $iconResult = checkUrl($iconUrl, "Ícone: {$icon['src']}");
                                    $results[$type]['icons'][] = $iconResult;
                                    
                                    if ($iconResult['pass']) {
                                        echo "✅ {$icon['src']}: Status {$iconResult['status']}, Type: {$iconResult['content_type']}<br>\n";
                                    } else {
                                        echo "❌ {$icon['src']}: Status {$iconResult['status']}<br>\n";
                                    }
                                }
                            }
                        }
                        
                        // 5. Verificar scope
                        echo "<h3>5. Scope</h3>\n";
                        if (isset($manifestData['scope'])) {
                            echo "Scope definido: {$manifestData['scope']}<br>\n";
                            if ($manifestData['scope'] === '/' || strpos($manifestData['start_url'], $manifestData['scope']) === 0) {
                                echo "✅ Scope compatível com start_url<br>\n";
                            } else {
                                echo "⚠️ Scope pode não cobrir start_url<br>\n";
                            }
                        } else {
                            echo "⚠️ Scope não definido (padrão: diretório do manifest)<br>\n";
                        }
                        
                    } else {
                        echo "❌ JSON inválido: {$parsed['error']}<br>\n";
                        $results[$type]['manifest_json'] = ['valid' => false, 'error' => $parsed['error']];
                    }
                } else {
                    echo "❌ Content-Type incorreto: {$manifestResult['content_type']} (esperado: application/json)<br>\n";
                }
            } else {
                echo "❌ Status: {$manifestResult['status']} - {$manifestResult['error']}<br>\n";
            }
        } else {
            echo "❌ Manifest não encontrado no HTML<br>\n";
        }
    } else {
        echo "❌ Status: {$loginResult['status']} - {$loginResult['error']}<br>\n";
    }
    
    // 6. Testar Service Worker
    echo "<h3>6. Service Worker</h3>\n";
    $swUrl = $baseUrl . '/sw.js';
    $swResult = checkUrl($swUrl, 'Service Worker (root)');
    
    if (!$swResult['pass']) {
        // Tentar /pwa/sw.js
        $swUrl = $baseUrl . '/pwa/sw.js';
        $swResult = checkUrl($swUrl, 'Service Worker (/pwa/sw.js)');
    }
    
    $results[$type]['service_worker'] = $swResult;
    
    if ($swResult['pass']) {
        echo "✅ Status: {$swResult['status']}<br>\n";
        echo "URL: {$swUrl}<br>\n";
    } else {
        echo "❌ Status: {$swResult['status']} - {$swResult['error']}<br>\n";
    }
    
    echo "<hr>\n";
}

// Resumo final
echo "<h2>Resumo Final</h2>\n";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>\n";
echo "<tr><th>Teste</th><th>Instrutor</th><th>Aluno</th></tr>\n";

$tests = [
    'Página de Login' => ['login_page', 'status'],
    'Manifest HTTP' => ['manifest', 'status'],
    'Manifest JSON' => ['manifest_json', 'valid'],
    'Start URL' => ['start_url', 'status'],
    'Service Worker' => ['service_worker', 'status']
];

foreach ($tests as $testName => $config) {
    list($key, $field) = $config;
    echo "<tr><td><strong>{$testName}</strong></td>\n";
    
    foreach (['instrutor', 'aluno'] as $type) {
        $result = $results[$type][$key] ?? null;
        if ($result === null) {
            echo "<td>N/A</td>\n";
        } elseif (is_array($result) && isset($result[$field])) {
            $value = $result[$field];
            if ($field === 'status') {
                $pass = ($value === 200);
            } else {
                $pass = ($value === true);
            }
            echo "<td>" . ($pass ? "✅ PASS" : "❌ FAIL") . "</td>\n";
        } else {
            echo "<td>❓</td>\n";
        }
    }
    echo "</tr>\n";
}

echo "</table>\n";

// Verificar ícones
echo "<h3>Ícones</h3>\n";
foreach (['instrutor', 'aluno'] as $type) {
    $icons = $results[$type]['icons'] ?? [];
    $passed = 0;
    $total = count($icons);
    foreach ($icons as $icon) {
        if ($icon['status'] === 200) $passed++;
    }
    echo "<strong>{$type}:</strong> {$passed}/{$total} ícones OK<br>\n";
}

?>
    <hr>
    <div class="note">
        <strong>Nota:</strong> Este script verifica os requisitos técnicos para instalação PWA.
        Se algum teste falhar, verifique os detalhes acima e corrija os problemas identificados.
    </div>
</body>
</html>
