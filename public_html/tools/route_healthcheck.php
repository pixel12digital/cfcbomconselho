<?php
/**
 * Script de Healthcheck de Rotas
 * 
 * Valida que todas as rotas respondem corretamente e não dependem
 * de paths hardcoded que quebram entre local e produção.
 * 
 * USO:
 * - Local: http://localhost/cfc-v.1/public_html/tools/route_healthcheck.php
 * - Produção: https://dominio.com/tools/route_healthcheck.php
 * 
 * SEGURANÇA: Este script é somente leitura, não altera banco de dados.
 */

// Definir constantes básicas
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', __DIR__ . '/..');

// Autoload
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar .env
use App\Config\Env;
Env::load();

// Bootstrap (para ter acesso aos helpers)
require_once APP_PATH . '/Bootstrap.php';

// Detectar ambiente
$appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'local';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$isLocalhost = in_array($host, ['localhost', '127.0.0.1', '::1']) || 
              strpos($host, 'localhost') !== false;
$isProduction = ($appEnv === 'production') && !$isLocalhost;

// Para exibição no relatório: se host for localhost, mostrar como "local"
$displayEnv = $isLocalhost ? 'local' : $appEnv;
$displayIsProduction = !$isLocalhost && ($appEnv === 'production');

// Base URL para testes
$baseUrl = base_url('');
$basePath = base_path('');

// Função para normalizar Location header
function normalizeLocation($location, $basePath) {
    if (empty($location)) {
        return null;
    }
    
    // Se Location for absoluto (http://... ou https://...), extrair apenas o path
    if (preg_match('/^https?:\/\//', $location)) {
        $location = parse_url($location, PHP_URL_PATH);
    }
    
    // Se Location for relativo mas começar com /, usar como está
    // Se não começar com /, assumir que é relativo ao basePath
    if (!empty($location) && $location[0] !== '/') {
        $location = '/' . $location;
    }
    
    // Remover base_path do location
    if (!empty($basePath) && $basePath !== '/') {
        $basePath = rtrim($basePath, '/');
        if (strpos($location, $basePath) === 0) {
            $location = substr($location, strlen($basePath));
        }
    }
    
    // Garantir que começa com /
    $location = '/' . ltrim($location, '/');
    
    return $location;
}

// Lista de rotas para testar
$routes = [
    // Rotas públicas (devem retornar 200 ou 302 esperado)
    'public' => [
        ['method' => 'GET', 'path' => '/', 'expected_status' => [200, 301, 302], 'description' => 'Raiz - deve redirecionar para login ou mostrar login'],
        ['method' => 'GET', 'path' => '/login', 'expected_status' => [200], 'description' => 'Página de login'],
        ['method' => 'GET', 'path' => '/login/cfc-logo', 'expected_status' => [200, 302, 404], 'description' => 'Logo do CFC (pode não existir)'],
        ['method' => 'GET', 'path' => '/forgot-password', 'expected_status' => [200], 'description' => 'Esqueci minha senha'],
        ['method' => 'GET', 'path' => '/reset-password', 'expected_status' => [200, 302], 'description' => 'Reset de senha (pode redirecionar sem token)'],
        ['method' => 'GET', 'path' => '/ativar-conta', 'expected_status' => [200, 302], 'description' => 'Ativação de conta (pode redirecionar sem token)'],
    ],
    
    // Rotas protegidas (devem retornar 302 para /login quando sem sessão)
    'protected' => [
        ['method' => 'GET', 'path' => '/dashboard', 'expected_status' => [302], 'expected_location' => '/login', 'description' => 'Dashboard - deve redirecionar para login'],
        ['method' => 'GET', 'path' => '/servicos', 'expected_status' => [302], 'expected_location' => '/login', 'description' => 'Serviços - deve redirecionar para login'],
        ['method' => 'GET', 'path' => '/alunos', 'expected_status' => [302], 'expected_location' => '/login', 'description' => 'Alunos - deve redirecionar para login'],
        ['method' => 'GET', 'path' => '/agenda', 'expected_status' => [302], 'expected_location' => '/login', 'description' => 'Agenda - deve redirecionar para login'],
        ['method' => 'GET', 'path' => '/configuracoes/cfc', 'expected_status' => [302], 'expected_location' => '/login', 'description' => 'Configurações CFC - deve redirecionar para login'],
    ],
    
    // Assets (devem retornar 200)
    'assets' => [
        ['method' => 'GET', 'path' => '/assets/ping.txt', 'expected_status' => [200], 'description' => 'Asset de teste (ping.txt)'],
    ],
];

// Função para fazer requisição HTTP
function makeRequest($url, $method = 'GET', $followRedirects = false) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $followRedirects);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_NOBODY, true); // HEAD request
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    
    // Não validar SSL em local (XAMPP)
    if (strpos($url, 'localhost') !== false || strpos($url, '127.0.0.1') !== false) {
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $headers = substr($response, 0, $headerSize);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Extrair Location header
    $location = null;
    if (preg_match('/Location:\s*(.+)/i', $headers, $matches)) {
        $location = trim($matches[1]);
    }
    
    return [
        'status' => $httpCode,
        'location' => $location,
        'headers' => $headers,
        'error' => $error,
        'time' => microtime(true)
    ];
}

// Função para verificar se path está correto (sem duplicação)
function checkPathConsistency($url, $baseUrl, $basePath) {
    $issues = [];
    
    // Verificar se há duplicação de /public_html
    if (substr_count($url, '/public_html/') > 1) {
        $issues[] = "Duplicação de /public_html/ detectada";
    }
    
    // Verificar se há duplicação de /cfc-v.1
    if (substr_count($url, '/cfc-v.1/') > 1) {
        $issues[] = "Duplicação de /cfc-v.1/ detectada";
    }
    
    // Verificar se base_url está sendo usado corretamente
    // Em produção, não deve ter /cfc-v.1/public_html
    $isProduction = (strpos($baseUrl, 'localhost') === false && strpos($baseUrl, '127.0.0.1') === false);
    if ($isProduction && (strpos($url, '/cfc-v.1/') !== false || strpos($url, '/public_html/') !== false)) {
        $issues[] = "Path de desenvolvimento detectado em produção";
    }
    
    return $issues;
}

// Iniciar testes
$results = [
    'environment' => [
        'app_env' => $appEnv,
        'display_env' => $displayEnv,
        'host' => $host,
        'is_production' => $isProduction,
        'display_is_production' => $displayIsProduction,
        'base_url' => $baseUrl,
        'base_path' => $basePath,
    ],
    'tests' => [],
    'summary' => [
        'total' => 0,
        'passed' => 0,
        'failed' => 0,
        'warnings' => 0,
    ]
];

// Testar rotas
foreach ($routes as $category => $categoryRoutes) {
    foreach ($categoryRoutes as $route) {
        $results['summary']['total']++;
        
        $fullUrl = base_url($route['path']);
        $startTime = microtime(true);
        $response = makeRequest($fullUrl, $route['method']);
        $duration = round((microtime(true) - $startTime) * 1000, 2);
        
        // Verificar status code
        $statusOk = in_array($response['status'], $route['expected_status']);
        
        // Verificar Location header se esperado
        $locationOk = true;
        if (isset($route['expected_location'])) {
            if ($response['location']) {
                // Normalizar Location usando função utilitária
                $expected = $route['expected_location'];
                $actual = normalizeLocation($response['location'], $basePath);
                
                // Comparar paths normalizados
                $locationOk = ($actual === $expected || strpos($actual, $expected) !== false);
            } else {
                $locationOk = false;
            }
        }
        
        // Verificar consistência de path
        $pathIssues = checkPathConsistency($fullUrl, $baseUrl, $basePath);
        $pathOk = empty($pathIssues);
        
        // Determinar resultado
        $passed = $statusOk && $locationOk && $pathOk;
        $hasWarnings = !empty($pathIssues) && $statusOk;
        
        if ($passed) {
            $results['summary']['passed']++;
        } elseif ($hasWarnings) {
            $results['summary']['warnings']++;
        } else {
            $results['summary']['failed']++;
        }
        
        $results['tests'][] = [
            'category' => $category,
            'method' => $route['method'],
            'path' => $route['path'],
            'full_url' => $fullUrl,
            'description' => $route['description'],
            'expected_status' => $route['expected_status'],
            'actual_status' => $response['status'],
            'expected_location' => $route['expected_location'] ?? null,
            'actual_location' => $response['location'],
            'normalized_location' => isset($route['expected_location']) && $response['location'] ? normalizeLocation($response['location'], $basePath) : null,
            'status_ok' => $statusOk,
            'location_ok' => $locationOk,
            'path_issues' => $pathIssues,
            'path_ok' => $pathOk,
            'duration_ms' => $duration,
            'passed' => $passed,
            'has_warnings' => $hasWarnings,
            'error' => $response['error'],
        ];
    }
}

// Output
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Healthcheck de Rotas - CFC</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            color: #333;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 30px;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            padding: 20px;
            border-radius: 6px;
            text-align: center;
        }
        .summary-card.total { background: #ecf0f1; }
        .summary-card.passed { background: #d4edda; color: #155724; }
        .summary-card.failed { background: #f8d7da; color: #721c24; }
        .summary-card.warnings { background: #fff3cd; color: #856404; }
        .summary-card h3 {
            font-size: 2em;
            margin-bottom: 5px;
        }
        .env-info {
            background: #e8f4f8;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            font-family: monospace;
            font-size: 0.9em;
        }
        .test-result {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            margin: 10px 0;
        }
        .test-result.passed {
            border-left: 4px solid #28a745;
            background: #f8fff9;
        }
        .test-result.failed {
            border-left: 4px solid #dc3545;
            background: #fff8f8;
        }
        .test-result.warning {
            border-left: 4px solid #ffc107;
            background: #fffef8;
        }
        .test-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .test-method {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
            background: #6c757d;
            color: white;
        }
        .test-method.GET { background: #28a745; }
        .test-method.POST { background: #007bff; }
        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            font-weight: bold;
        }
        .status-badge.ok { background: #28a745; color: white; }
        .status-badge.fail { background: #dc3545; color: white; }
        .status-badge.warn { background: #ffc107; color: #333; }
        .details {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid #eee;
            font-size: 0.9em;
        }
        .details strong { color: #555; }
        .path-issue {
            color: #856404;
            background: #fff3cd;
            padding: 5px 10px;
            border-radius: 4px;
            margin: 5px 0;
            display: inline-block;
        }
        .category-header {
            margin: 30px 0 15px 0;
            padding: 10px;
            background: #f8f9fa;
            border-left: 4px solid #3498db;
            font-size: 1.2em;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Healthcheck de Rotas</h1>
        
        <div class="env-info">
            <strong>Ambiente:</strong> <?= htmlspecialchars($results['environment']['display_env']) ?><br>
            <strong>Host:</strong> <?= htmlspecialchars($results['environment']['host']) ?><br>
            <strong>Produção:</strong> <?= $results['environment']['display_is_production'] ? 'Sim' : 'Não' ?><br>
            <strong>Base URL:</strong> <?= htmlspecialchars($results['environment']['base_url']) ?><br>
            <strong>Base Path:</strong> <?= htmlspecialchars($results['environment']['base_path']) ?>
        </div>
        
        <div class="summary">
            <div class="summary-card total">
                <h3><?= $results['summary']['total'] ?></h3>
                <p>Total de Testes</p>
            </div>
            <div class="summary-card passed">
                <h3><?= $results['summary']['passed'] ?></h3>
                <p>✅ Passou</p>
            </div>
            <div class="summary-card failed">
                <h3><?= $results['summary']['failed'] ?></h3>
                <p>❌ Falhou</p>
            </div>
            <div class="summary-card warnings">
                <h3><?= $results['summary']['warnings'] ?></h3>
                <p>⚠️ Avisos</p>
            </div>
        </div>
        
        <?php
        $currentCategory = null;
        foreach ($results['tests'] as $test) {
            if ($currentCategory !== $test['category']) {
                $currentCategory = $test['category'];
                echo '<div class="category-header">' . ucfirst($currentCategory) . '</div>';
            }
            
            $resultClass = $test['passed'] ? 'passed' : ($test['has_warnings'] ? 'warning' : 'failed');
            ?>
            <div class="test-result <?= $resultClass ?>">
                <div class="test-header">
                    <div>
                        <span class="test-method <?= $test['method'] ?>"><?= $test['method'] ?></span>
                        <strong><?= htmlspecialchars($test['path']) ?></strong>
                        <span style="color: #666; font-size: 0.9em;"><?= htmlspecialchars($test['description']) ?></span>
                    </div>
                    <div>
                        <?php if ($test['passed']): ?>
                            <span class="status-badge ok">✅ OK</span>
                        <?php elseif ($test['has_warnings']): ?>
                            <span class="status-badge warn">⚠️ AVISO</span>
                        <?php else: ?>
                            <span class="status-badge fail">❌ FALHOU</span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="details">
                    <strong>URL completa:</strong> <code><?= htmlspecialchars($test['full_url']) ?></code><br>
                    <strong>Status esperado:</strong> <?= implode(' ou ', $test['expected_status']) ?> | 
                    <strong>Status real:</strong> <code><?= $test['actual_status'] ?></code>
                    <?php if (!$test['status_ok']): ?>
                        <span class="status-badge fail">Status incorreto</span>
                    <?php endif; ?><br>
                    
                    <?php if ($test['expected_location']): ?>
                        <strong>Location esperado:</strong> <?= htmlspecialchars($test['expected_location']) ?> | 
                        <strong>Location real:</strong> <code><?= htmlspecialchars($test['actual_location'] ?? 'N/A') ?></code>
                        <?php if ($test['normalized_location']): ?>
                            | <strong>Location normalizado:</strong> <code><?= htmlspecialchars($test['normalized_location']) ?></code>
                        <?php endif; ?>
                        <?php if (!$test['location_ok']): ?>
                            <span class="status-badge fail">Location incorreto</span>
                        <?php endif; ?><br>
                    <?php endif; ?>
                    
                    <strong>Tempo de resposta:</strong> <?= $test['duration_ms'] ?>ms<br>
                    
                    <?php if (!empty($test['path_issues'])): ?>
                        <div style="margin-top: 10px;">
                            <strong>⚠️ Problemas de path detectados:</strong><br>
                            <?php foreach ($test['path_issues'] as $issue): ?>
                                <div class="path-issue"><?= htmlspecialchars($issue) ?></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($test['error']): ?>
                        <div style="margin-top: 10px; color: #dc3545;">
                            <strong>Erro cURL:</strong> <?= htmlspecialchars($test['error']) ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php } ?>
        
        <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 6px;">
            <h3>📋 Resumo</h3>
            <p><strong>Total:</strong> <?= $results['summary']['total'] ?> testes executados</p>
            <p><strong>✅ Passou:</strong> <?= $results['summary']['passed'] ?> (<?= round($results['summary']['passed'] / $results['summary']['total'] * 100, 1) ?>%)</p>
            <p><strong>❌ Falhou:</strong> <?= $results['summary']['failed'] ?> (<?= round($results['summary']['failed'] / $results['summary']['total'] * 100, 1) ?>%)</p>
            <p><strong>⚠️ Avisos:</strong> <?= $results['summary']['warnings'] ?> (<?= round($results['summary']['warnings'] / $results['summary']['total'] * 100, 1) ?>%)</p>
            
            <?php if ($results['summary']['failed'] === 0 && $results['summary']['warnings'] === 0): ?>
                <p style="margin-top: 15px; padding: 15px; background: #d4edda; color: #155724; border-radius: 4px;">
                    <strong>✅ Todos os testes passaram!</strong> O sistema está pronto para deploy.
                </p>
            <?php elseif ($results['summary']['failed'] === 0): ?>
                <p style="margin-top: 15px; padding: 15px; background: #fff3cd; color: #856404; border-radius: 4px;">
                    <strong>⚠️ Todos os testes passaram, mas há avisos.</strong> Revise os problemas de path antes do deploy.
                </p>
            <?php else: ?>
                <p style="margin-top: 15px; padding: 15px; background: #f8d7da; color: #721c24; border-radius: 4px;">
                    <strong>❌ Alguns testes falharam.</strong> Corrija os problemas antes do deploy.
                </p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
