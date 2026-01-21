<?php
/**
 * Script para visualizar logs de upload de logo
 * Acesse via: /tools/ver_log_upload.php
 */

// Carregar configurações
require_once __DIR__ . '/../app/Bootstrap.php';
require_once __DIR__ . '/../app/Config/Env.php';
require_once __DIR__ . '/../app/Config/Database.php';

use App\Config\Env;
use App\Config\Database;

Env::load();

// Verificar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id']) || ($_SESSION['current_role'] ?? '') !== 'ADMIN') {
    http_response_code(403);
    die("Acesso negado. Apenas administradores podem acessar este log.");
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Logs de Upload de Logo</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f5f5f5; }
        .section { background: white; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; white-space: pre-wrap; word-wrap: break-word; }
        h2 { margin-top: 0; }
        .empty { color: #999; font-style: italic; }
        .refresh-btn { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>📋 Logs de Upload de Logo</h1>
    <p><strong>Data/Hora:</strong> <?= date('Y-m-d H:i:s') ?></p>
    
    <div class="refresh-btn">
        <button onclick="location.reload()">🔄 Atualizar</button>
    </div>

<?php

$logFile = dirname(__DIR__, 2) . '/storage/logs/upload_logo.log';

echo '<div class="section">';
echo '<h2>📄 Arquivo de Log</h2>';
echo '<pre>';
echo "Caminho: $logFile\n";
echo "Existe: " . (file_exists($logFile) ? '✅ SIM' : '❌ NÃO') . "\n";
if (file_exists($logFile)) {
    echo "Tamanho: " . filesize($logFile) . " bytes\n";
    echo "Última modificação: " . date('Y-m-d H:i:s', filemtime($logFile)) . "\n";
    echo "Permissões: " . substr(sprintf('%o', fileperms($logFile)), -4) . "\n";
}
echo '</pre>';
echo '</div>';

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    
    if (empty(trim($content))) {
        echo '<div class="section">';
        echo '<h2>⚠️ Log Vazio</h2>';
        echo '<p class="empty">O arquivo de log existe mas está vazio. Isso significa que o método uploadLogo() não está sendo executado.</p>';
        echo '<p><strong>Possíveis causas:</strong></p>';
        echo '<ul>';
        echo '<li>A rota não está sendo encontrada</li>';
        echo '<li>O middleware está bloqueando antes de chegar no método</li>';
        echo '<li>Há um erro fatal antes do método ser executado</li>';
        echo '<li>O form não está sendo submetido corretamente</li>';
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="section">';
        echo '<h2>📝 Conteúdo do Log</h2>';
        echo '<pre>' . htmlspecialchars($content) . '</pre>';
        echo '</div>';
        
        // Tentar parsear JSON se possível
        $lines = explode("\n", $content);
        $jsonEntries = [];
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '===') === 0) continue;
            
            if (($jsonStart = strpos($line, '{')) !== false) {
                $jsonStr = substr($line, $jsonStart);
                $json = json_decode($jsonStr, true);
                if ($json) {
                    $jsonEntries[] = $json;
                }
            }
        }
        
        if (!empty($jsonEntries)) {
            echo '<div class="section">';
            echo '<h2>📊 Análise do Log</h2>';
            echo '<pre>' . json_encode($jsonEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
            echo '</div>';
        }
    }
} else {
    echo '<div class="section">';
    echo '<h2>❌ Arquivo de Log Não Existe</h2>';
    echo '<p class="empty">O arquivo de log não foi criado. Isso confirma que o método uploadLogo() não está sendo executado.</p>';
    echo '<p><strong>Próximos passos:</strong></p>';
    echo '<ul>';
    echo '<li>Verificar se o form está sendo submetido (console do navegador)</li>';
    echo '<li>Verificar se a rota está correta</li>';
    echo '<li>Verificar se há erros no php_errors.log</li>';
    echo '</ul>';
    echo '</div>';
}

// Verificar erros PHP relacionados
$phpErrorsLog = dirname(__DIR__, 2) . '/storage/logs/php_errors.log';
if (file_exists($phpErrorsLog)) {
    $phpErrors = file_get_contents($phpErrorsLog);
    $uploadRelated = [];
    $lines = explode("\n", $phpErrors);
    foreach ($lines as $line) {
        if (stripos($line, 'upload') !== false || 
            stripos($line, 'logo') !== false || 
            stripos($line, 'configuracoes') !== false ||
            stripos($line, 'uploadLogo') !== false) {
            $uploadRelated[] = $line;
        }
    }
    
    if (!empty($uploadRelated)) {
        echo '<div class="section">';
        echo '<h2>⚠️ Erros PHP Relacionados</h2>';
        echo '<pre>' . htmlspecialchars(implode("\n", array_slice($uploadRelated, -20))) . '</pre>';
        echo '</div>';
    }
}

?>

    <div class="section">
        <h2>🔍 Verificações Adicionais</h2>
        <ul>
            <li><strong>Console do navegador:</strong> Verifique se há logs de "[UPLOAD DEBUG] Form submit iniciado"</li>
            <li><strong>Network tab:</strong> Verifique se a requisição POST para /configuracoes/cfc/logo/upload está sendo feita</li>
            <li><strong>Status da resposta:</strong> Verifique o status code (200, 302, 404, 500)</li>
            <li><strong>Headers de resposta:</strong> Verifique se há headers X-Upload-Debug-*</li>
        </ul>
    </div>

</body>
</html>
