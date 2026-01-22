<?php
/**
 * FASE 6: Script de diagnóstico - 3 provas obrigatórias
 * 
 * Verifica:
 * (A) Arquivo existe fisicamente em storage/uploads/cfcs/
 * (B) DB foi atualizado com logo_path
 * (C) URL do logo retorna 200 ou 404
 */

// Carregar configurações (seguindo padrão do index.php)
// __DIR__ = public_html/tools/, então precisamos subir 2 níveis para chegar à raiz
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');

// Autoload (necessário antes de usar classes)
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente PRIMEIRO
require_once APP_PATH . '/Config/Env.php';
use App\Config\Env;
Env::load();

// Bootstrap
require_once APP_PATH . '/Bootstrap.php';

// Database
require_once APP_PATH . '/Config/Database.php';

use App\Config\Database;
use App\Models\Cfc;

// Verificar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['current_role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    die("Acesso negado. Apenas administradores podem acessar este diagnóstico.");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico Logo CFC</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .success { color: #28a745; }
        .error { color: #dc3545; }
        .warning { color: #ffc107; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        h2 { margin-top: 0; }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico Logo CFC - 3 Provas</h1>
    
    <?php
    $cfcModel = new Cfc();
    $cfc = $cfcModel->getCurrent();
    $cfcId = $cfc['id'] ?? null;
    
    if (!$cfcId) {
        echo '<div class="section error"><h2>❌ Erro</h2><p>CFC não encontrado na sessão.</p></div>';
        exit;
    }
    
    echo '<div class="section"><h2>CFC Atual</h2>';
    echo '<pre>ID: ' . htmlspecialchars($cfcId) . "\n";
    echo 'Nome: ' . htmlspecialchars($cfc['nome'] ?? 'N/A') . "\n";
    echo 'Logo Path (DB): ' . htmlspecialchars($cfc['logo_path'] ?? 'NULL') . '</pre></div>';
    
    // PROVA 1: Arquivo existe fisicamente
    echo '<div class="section"><h2>📁 Prova 1: Arquivo Existe no Disco</h2>';
    $uploadDir = ROOT_PATH . '/storage/uploads/cfcs/';
    $files = [];
    
    if (is_dir($uploadDir)) {
        $files = glob($uploadDir . 'cfc_' . $cfcId . '_*');
        if ($files) {
            echo '<p class="success">✅ <strong>Arquivo(s) encontrado(s):</strong></p>';
            echo '<pre>';
            foreach ($files as $file) {
                $size = filesize($file);
                $modified = date('Y-m-d H:i:s', filemtime($file));
                echo basename($file) . ' (' . number_format($size / 1024, 2) . ' KB) - Modificado: ' . $modified . "\n";
            }
            echo '</pre>';
        } else {
            echo '<p class="error">❌ <strong>Nenhum arquivo encontrado</strong> em: ' . htmlspecialchars($uploadDir) . '</p>';
        }
    } else {
        echo '<p class="error">❌ <strong>Diretório não existe:</strong> ' . htmlspecialchars($uploadDir) . '</p>';
    }
    
    if ($cfc['logo_path']) {
        $expectedPath = ROOT_PATH . '/' . $cfc['logo_path'];
        if (file_exists($expectedPath)) {
            echo '<p class="success">✅ <strong>Arquivo do DB existe:</strong> ' . htmlspecialchars($cfc['logo_path']) . '</p>';
            echo '<pre>Tamanho: ' . number_format(filesize($expectedPath) / 1024, 2) . ' KB' . "\n";
            echo 'Modificado: ' . date('Y-m-d H:i:s', filemtime($expectedPath)) . '</pre>';
        } else {
            echo '<p class="error">❌ <strong>Arquivo do DB NÃO existe:</strong> ' . htmlspecialchars($expectedPath) . '</p>';
        }
    }
    echo '</div>';
    
    // PROVA 2: DB foi atualizado
    echo '<div class="section"><h2>💾 Prova 2: Banco de Dados</h2>';
    try {
        $db = \App\Config\Database::getInstance()->getConnection();
        $stmt = $db->prepare("SELECT id, logo_path, updated_at FROM cfcs WHERE id = ?");
        $stmt->execute([$cfcId]);
        $dbCfc = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($dbCfc) {
            echo '<pre>';
            echo 'ID: ' . htmlspecialchars($dbCfc['id']) . "\n";
            echo 'Logo Path: ' . ($dbCfc['logo_path'] ? htmlspecialchars($dbCfc['logo_path']) : 'NULL') . "\n";
            echo 'Updated At: ' . htmlspecialchars($dbCfc['updated_at'] ?? 'N/A') . "\n";
            echo '</pre>';
            
            if ($dbCfc['logo_path']) {
                echo '<p class="success">✅ <strong>DB tem logo_path preenchido</strong></p>';
            } else {
                echo '<p class="error">❌ <strong>DB tem logo_path NULL</strong></p>';
            }
        } else {
            echo '<p class="error">❌ CFC não encontrado no banco</p>';
        }
    } catch (\Exception $e) {
        echo '<p class="error">❌ Erro ao consultar banco: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';
    
    // PROVA 3: URL retorna 200 ou 404
    echo '<div class="section"><h2>🌐 Prova 3: URL do Logo</h2>';
    if ($cfc['logo_path']) {
        $logoUrl = base_path('configuracoes/cfc/logo') . '?v=' . time();
        $fullUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . 
                   '://' . $_SERVER['HTTP_HOST'] . $logoUrl;
        
        echo '<p><strong>URL:</strong> <a href="' . htmlspecialchars($logoUrl) . '" target="_blank">' . htmlspecialchars($logoUrl) . '</a></p>';
        echo '<p><strong>URL Completa:</strong> ' . htmlspecialchars($fullUrl) . '</p>';
        
        // Testar via cURL
        $ch = curl_init($fullUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        curl_close($ch);
        
        echo '<pre>';
        echo 'HTTP Code: ' . $httpCode . "\n";
        echo 'Content-Type: ' . ($contentType ?: 'N/A') . "\n";
        echo '</pre>';
        
        if ($httpCode === 200) {
            echo '<p class="success">✅ <strong>URL retorna 200 OK</strong></p>';
        } elseif ($httpCode === 404) {
            echo '<p class="error">❌ <strong>URL retorna 404 Not Found</strong></p>';
            echo '<p class="warning">⚠️ Problema: Rota não encontrada ou arquivo não existe</p>';
        } else {
            echo '<p class="warning">⚠️ <strong>URL retorna ' . $httpCode . '</strong></p>';
        }
    } else {
        echo '<p class="warning">⚠️ Não há logo_path no DB para testar URL</p>';
    }
    echo '</div>';
    
    // Resumo
    echo '<div class="section"><h2>📊 Resumo</h2>';
    $prova1 = !empty($files) || ($cfc['logo_path'] && file_exists(ROOT_PATH . '/' . $cfc['logo_path']));
    $prova2 = !empty($cfc['logo_path']);
    $prova3 = false;
    if (isset($httpCode)) {
        $prova3 = ($httpCode === 200);
    }
    
    echo '<pre>';
    echo 'Prova 1 (Arquivo existe): ' . ($prova1 ? '✅' : '❌') . "\n";
    echo 'Prova 2 (DB atualizado): ' . ($prova2 ? '✅' : '❌') . "\n";
    echo 'Prova 3 (URL funciona): ' . ($prova3 ? '✅' : '❌') . "\n";
    echo '</pre>';
    
    if ($prova1 && $prova2 && $prova3) {
        echo '<p class="success"><strong>✅ Todas as provas passaram! O logo deve estar funcionando.</strong></p>';
    } elseif ($prova1 && $prova2 && !$prova3) {
        echo '<p class="error"><strong>❌ Problema: Arquivo e DB OK, mas URL não funciona (404).</strong></p>';
        echo '<p>Correção: Verificar rota /configuracoes/cfc/logo</p>';
    } elseif ($prova1 && !$prova2) {
        echo '<p class="error"><strong>❌ Problema: Arquivo existe, mas DB não foi atualizado.</strong></p>';
        echo '<p>Correção: Verificar método uploadLogo() - update no banco</p>';
    } elseif (!$prova1) {
        echo '<p class="error"><strong>❌ Problema: Arquivo não foi salvo no disco.</strong></p>';
        echo '<p>Correção: Verificar permissões e método uploadLogo() - move_uploaded_file</p>';
    }
    echo '</div>';
    ?>
    
    <div class="section">
        <h2>📝 Logs</h2>
        <p><a href="<?= base_path('tools/ver_log_upload.php') ?>" target="_blank">Ver log de upload</a></p>
        <p><a href="<?= base_path('tools/ver_log_display.php') ?>" target="_blank">Ver log de exibição</a></p>
    </div>
</body>
</html>
