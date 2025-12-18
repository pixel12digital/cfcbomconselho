<?php
/**
 * Script para verificar logs relacionados a reset de senha
 * Ajuda a encontrar o arquivo de log correto no servidor
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar se é admin
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar Logs - Reset de Senha</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            border-bottom: 3px solid #3498db;
            padding-bottom: 10px;
        }
        .log-path {
            background: #ecf0f1;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            margin: 10px 0;
        }
        .success {
            color: #27ae60;
            font-weight: bold;
        }
        .error {
            color: #e74c3c;
            font-weight: bold;
        }
        .info {
            color: #3498db;
        }
        pre {
            background: #2c3e50;
            color: #ecf0f1;
            padding: 15px;
            border-radius: 4px;
            overflow-x: auto;
            max-height: 600px;
            overflow-y: auto;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #3498db;
            padding-left: 10px;
        }
        .log-entry.error {
            border-left-color: #e74c3c;
        }
        .log-entry.success {
            border-left-color: #27ae60;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Verificação de Logs - Reset de Senha</h1>
        
        <?php
        // Caminho configurado no config.php
        $logPath = __DIR__ . '/../logs/php_errors.log';
        $logPathRelative = 'logs/php_errors.log';
        
        echo "<h2>📁 Caminho do Log Configurado</h2>";
        echo "<div class='log-path'>";
        echo "<strong>Caminho absoluto:</strong><br>";
        echo htmlspecialchars($logPath) . "<br><br>";
        echo "<strong>Caminho relativo (do diretório raiz):</strong><br>";
        echo htmlspecialchars($logPathRelative) . "<br><br>";
        echo "<strong>No servidor SSH (a partir de cfcbomconselho.com.br):</strong><br>";
        echo "<code>logs/php_errors.log</code> ou <code>./logs/php_errors.log</code>";
        echo "</div>";
        
        // Verificar se o arquivo existe
        echo "<h2>📋 Status do Arquivo</h2>";
        if (file_exists($logPath)) {
            $fileSize = filesize($logPath);
            $fileSizeMB = round($fileSize / 1024 / 1024, 2);
            $lastModified = date('Y-m-d H:i:s', filemtime($logPath));
            
            echo "<p class='success'>✅ Arquivo encontrado!</p>";
            echo "<ul>";
            echo "<li><strong>Tamanho:</strong> " . number_format($fileSize) . " bytes (" . $fileSizeMB . " MB)</li>";
            echo "<li><strong>Última modificação:</strong> " . $lastModified . "</li>";
            echo "<li><strong>Legível:</strong> " . (is_readable($logPath) ? "✅ Sim" : "❌ Não") . "</li>";
            echo "</ul>";
            
            // Ler últimas linhas relacionadas a PASSWORD_RESET
            echo "<h2>📝 Últimas Entradas com 'PASSWORD_RESET' ou 'RESET_PASSWORD'</h2>";
            
            // Ler arquivo (últimas 1000 linhas para não sobrecarregar)
            $lines = file($logPath);
            if ($lines === false) {
                echo "<p class='error'>❌ Erro ao ler arquivo de log.</p>";
            } else {
                // Filtrar linhas relacionadas a password reset
                $filteredLines = [];
                foreach ($lines as $lineNum => $line) {
                    if (stripos($line, 'PASSWORD_RESET') !== false || 
                        stripos($line, 'RESET_PASSWORD') !== false) {
                        $filteredLines[] = [
                            'line' => $lineNum + 1,
                            'content' => $line
                        ];
                    }
                }
                
                // Mostrar últimas 50 entradas
                $filteredLines = array_slice($filteredLines, -50);
                
                if (empty($filteredLines)) {
                    echo "<p class='info'>ℹ️ Nenhuma entrada encontrada com 'PASSWORD_RESET' ou 'RESET_PASSWORD'.</p>";
                    echo "<p>Isso pode significar que:</p>";
                    echo "<ul>";
                    echo "<li>Nenhum reset de senha foi tentado ainda</li>";
                    echo "<li>Os logs estão em outro arquivo</li>";
                    echo "<li>LOG_ENABLED está desabilitado</li>";
                    echo "</ul>";
                } else {
                    echo "<p class='success'>✅ Encontradas " . count($filteredLines) . " entradas relacionadas.</p>";
                    echo "<p><strong>Mostrando últimas 50 entradas:</strong></p>";
                    echo "<pre>";
                    foreach ($filteredLines as $entry) {
                        $isError = stripos($entry['content'], '❌') !== false || 
                                  stripos($entry['content'], 'erro') !== false ||
                                  stripos($entry['content'], 'error') !== false;
                        $isSuccess = stripos($entry['content'], '✅') !== false || 
                                    stripos($entry['content'], 'sucesso') !== false ||
                                    stripos($entry['content'], 'success') !== false;
                        
                        $class = $isError ? 'error' : ($isSuccess ? 'success' : '');
                        echo "<div class='log-entry $class'>";
                        echo "Linha " . $entry['line'] . ": " . htmlspecialchars($entry['content']);
                        echo "</div>";
                    }
                    echo "</pre>";
                }
            }
            
        } else {
            echo "<p class='error'>❌ Arquivo não encontrado no caminho configurado.</p>";
            echo "<h3>🔍 Tentando encontrar logs em outros locais:</h3>";
            
            // Tentar outros caminhos comuns
            $possiblePaths = [
                __DIR__ . '/../logs/php_errors.log',
                __DIR__ . '/../error.log',
                ini_get('error_log'),
                '/var/log/php_errors.log',
                '/var/log/apache2/error.log',
                '/var/log/httpd/error_log',
            ];
            
            echo "<ul>";
            foreach ($possiblePaths as $path) {
                if ($path && file_exists($path)) {
                    echo "<li class='success'>✅ Encontrado: " . htmlspecialchars($path) . "</li>";
                } else {
                    echo "<li class='error'>❌ Não encontrado: " . htmlspecialchars($path ?: 'N/A') . "</li>";
                }
            }
            echo "</ul>";
            
            echo "<h3>💡 Comandos SSH para encontrar logs:</h3>";
            echo "<pre>";
            echo "# A partir do diretório cfcbomconselho.com.br:\n";
            echo "find . -name '*.log' -type f 2>/dev/null\n";
            echo "find . -name '*error*' -type f 2>/dev/null\n\n";
            echo "# Verificar configuração do PHP:\n";
            echo "php -i | grep error_log\n\n";
            echo "# Verificar logs do sistema:\n";
            echo "ls -la logs/ 2>/dev/null\n";
            echo "tail -f logs/php_errors.log | grep PASSWORD_RESET\n";
            echo "</pre>";
        }
        
        // Verificar configuração
        echo "<h2>⚙️ Configuração Atual</h2>";
        echo "<ul>";
        echo "<li><strong>LOG_ENABLED:</strong> " . (LOG_ENABLED ? "✅ true" : "❌ false") . "</li>";
        echo "<li><strong>error_log (PHP ini):</strong> " . htmlspecialchars(ini_get('error_log') ?: 'Não configurado') . "</li>";
        echo "<li><strong>log_errors (PHP ini):</strong> " . (ini_get('log_errors') ? "✅ Ativado" : "❌ Desativado") . "</li>";
        echo "</ul>";
        ?>
        
        <h2>📖 Como usar no SSH</h2>
        <p>Se você estiver no diretório <code>cfcbomconselho.com.br</code>, use:</p>
        <pre>
# Ver últimas 50 linhas do log
tail -n 50 logs/php_errors.log

# Filtrar apenas logs de reset de senha
tail -n 100 logs/php_errors.log | grep -i "PASSWORD_RESET\|RESET_PASSWORD"

# Monitorar em tempo real
tail -f logs/php_errors.log | grep -i "PASSWORD_RESET\|RESET_PASSWORD"

# Ver todas as linhas com reset de senha
grep -i "PASSWORD_RESET\|RESET_PASSWORD" logs/php_errors.log | tail -n 50
        </pre>
    </div>
</body>
</html>
