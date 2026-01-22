<?php
/**
 * Script para verificar logs relacionados a getCurrentAlunoId()
 * FASE 1 - AREA ALUNO PENDENCIAS - Verificação de logs
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

// Verificar se é admin
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'admin') {
    die('Acesso negado. Apenas administradores podem executar este script.');
}

echo "<h1>Verificação de Logs - getCurrentAlunoId()</h1>";
echo "<pre>";

// Tentar encontrar o arquivo de log do PHP
$possiveisLogs = [
    'C:\xampp\php\logs\php_error_log',
    'C:\xampp\apache\logs\error.log',
    ini_get('error_log'),
    __DIR__ . '/../logs/error.log',
    __DIR__ . '/../error.log'
];

echo "=== ARQUIVOS DE LOG VERIFICADOS ===\n\n";

$logsEncontrados = [];
foreach ($possiveisLogs as $logPath) {
    if ($logPath && file_exists($logPath)) {
        $logsEncontrados[] = $logPath;
        echo "✓ Encontrado: $logPath\n";
    } else {
        echo "✗ Não encontrado: " . ($logPath ?: 'N/A') . "\n";
    }
}

if (empty($logsEncontrados)) {
    echo "\n⚠️ Nenhum arquivo de log encontrado nos locais padrão.\n";
    echo "Verifique manualmente o arquivo de log do PHP configurado no php.ini.\n";
} else {
    echo "\n=== ÚLTIMAS 50 LINHAS COM 'getCurrentAlunoId' ===\n\n";
    
    foreach ($logsEncontrados as $logPath) {
        echo "\n--- Arquivo: $logPath ---\n";
        
        if (filesize($logPath) > 10 * 1024 * 1024) { // Se maior que 10MB, ler só as últimas linhas
            $lines = file($logPath);
            $relevantLines = array_filter($lines, function($line) {
                return stripos($line, 'getCurrentAlunoId') !== false;
            });
            $relevantLines = array_slice($relevantLines, -50);
        } else {
            $content = file_get_contents($logPath);
            $lines = explode("\n", $content);
            $relevantLines = array_filter($lines, function($line) {
                return stripos($line, 'getCurrentAlunoId') !== false;
            });
            $relevantLines = array_slice($relevantLines, -50);
        }
        
        if (empty($relevantLines)) {
            echo "Nenhuma linha encontrada com 'getCurrentAlunoId' neste arquivo.\n";
        } else {
            foreach ($relevantLines as $line) {
                echo trim($line) . "\n";
            }
        }
    }
}

echo "\n=== TESTE DIRETO DA FUNÇÃO ===\n\n";
echo "Testando getCurrentAlunoId() para usuario_id: 19\n";
$alunoId = getCurrentAlunoId(19);
echo "Resultado: " . ($alunoId ? "✓ Aluno encontrado! ID: $alunoId" : "✗ NULL (não encontrado)") . "\n";

echo "\n</pre>";

