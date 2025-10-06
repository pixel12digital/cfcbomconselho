<?php
/**
 * Runner de Deploy seguro (executar via CLI/cron)
 * - Verifica deploy-flag.txt
 * - Executa git pull no diretório do projeto
 * - Loga saída e limpa a flag
 */

$timestamp = date('Y-m-d H:i:s');
$rootDir = __DIR__;
$logFile = $rootDir . '/logs/deploy.log';
$flagFile = $rootDir . '/deploy-flag.txt';

function logLine($msg) {
    global $logFile, $timestamp;
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    file_put_contents($logFile, "[$timestamp] $msg\n", FILE_APPEND);
}

logLine('🏃 Iniciando deploy-run.php');

if (!file_exists($flagFile)) {
    logLine('ℹ️ Nenhuma flag de deploy encontrada. Encerrando.');
    exit(0);
}

logLine('🟡 Flag encontrada. Executando git pull...');

// Comandos de atualização
$cmds = [
    'git fetch --all',
    'git reset --hard origin/master',
    'git clean -fd',
    'git pull --rebase --autostash'
];

foreach ($cmds as $cmd) {
    $descriptor = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
    $proc = proc_open($cmd, $descriptor, $pipes, $rootDir);
    if (is_resource($proc)) {
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        $exit = proc_close($proc);
        logLine("$cmd => exit:$exit");
        if ($stdout) logLine("STDOUT: " . trim($stdout));
        if ($stderr) logLine("STDERR: " . trim($stderr));
        if ($exit !== 0) {
            logLine('❌ Erro ao executar comando. Abortando deploy.');
            exit(1);
        }
    } else {
        logLine('❌ Falha ao iniciar processo do git.');
        exit(1);
    }
}

// Limpar flag
@unlink($flagFile);
logLine('✅ Deploy concluído e flag removida.');
echo "OK";
?>

