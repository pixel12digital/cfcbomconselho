<?php
/**
 * Análise Detalhada de Logs de Conexões
 * Script para diagnóstico de excesso de conexões
 * 
 * USO: Acesse via navegador ou execute via CLI: php admin/tools/analisar_logs_conexoes.php
 */

// Se executado via CLI, não requerer auth
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/auth.php';
    
    $user = getCurrentUser();
    if (!$user || !in_array($user['tipo'], ['admin'])) {
        http_response_code(403);
        die('Acesso negado. Apenas administradores podem acessar este relatório.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

$logDir = __DIR__ . '/../../storage/logs';
$logFile = $logDir . '/db_connections.jsonl';

if (!file_exists($logFile)) {
    echo "❌ Arquivo de log não encontrado: $logFile\n";
    echo "📝 Os logs serão gerados automaticamente quando o sistema for usado.\n";
    echo "💡 Acesse o admin e navegue por algumas páginas para gerar logs.\n";
    exit(1);
}

// Ler todas as linhas do arquivo
$lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if (empty($lines)) {
    echo "⚠️ Arquivo de log está vazio.\n";
    exit(1);
}

echo "📊 ANÁLISE DE LOGS DE CONEXÕES\n";
echo str_repeat("=", 80) . "\n\n";

// Parsear todas as entradas
$entries = [];
$firstTimestamp = null;
$lastTimestamp = null;

foreach ($lines as $line) {
    $entry = @json_decode(trim($line), true);
    if ($entry && isset($entry['timestamp'])) {
        $entries[] = $entry;
        $ts = strtotime($entry['timestamp']);
        if ($firstTimestamp === null || $ts < $firstTimestamp) {
            $firstTimestamp = $ts;
        }
        if ($lastTimestamp === null || $ts > $lastTimestamp) {
            $lastTimestamp = $ts;
        }
    }
}

$totalEntries = count($entries);
$periodMinutes = $firstTimestamp && $lastTimestamp ? round(($lastTimestamp - $firstTimestamp) / 60, 1) : 0;

echo "📅 PERÍODO COBERTO:\n";
echo "   Início: " . date('Y-m-d H:i:s', $firstTimestamp) . "\n";
echo "   Fim:    " . date('Y-m-d H:i:s', $lastTimestamp) . "\n";
echo "   Duração: {$periodMinutes} minutos\n";
echo "   Total de entradas: {$totalEntries}\n\n";

// Agregações
$byUri = [];
$byIp = [];
$byUserAgent = [];
$byMinute = [];
$byEvent = [];
$byRequestId = [];
$reconnects = [];

foreach ($entries as $entry) {
    $uri = $entry['request_uri'] ?? 'unknown';
    $ip = $entry['remote_ip'] ?? 'unknown';
    $ua = $entry['user_agent'] ?? 'unknown';
    $event = $entry['event'] ?? 'unknown';
    $requestId = $entry['request_id'] ?? 'unknown';
    $minute = substr($entry['timestamp'], 0, 16);
    
    $byUri[$uri] = ($byUri[$uri] ?? 0) + 1;
    $byIp[$ip] = ($byIp[$ip] ?? 0) + 1;
    $byUserAgent[$ua] = ($byUserAgent[$ua] ?? 0) + 1;
    $byMinute[$minute] = ($byMinute[$minute] ?? 0) + 1;
    $byEvent[$event] = ($byEvent[$event] ?? 0) + 1;
    
    if (!isset($byRequestId[$requestId])) {
        $byRequestId[$requestId] = [];
    }
    $byRequestId[$requestId][] = $entry;
    
    if ($event === 'reconnect' || $event === 'reconnect_error') {
        $reconnects[] = $entry;
    }
}

// Ordenar
arsort($byUri);
arsort($byIp);
arsort($byUserAgent);
arsort($byMinute);
arsort($byEvent);

// 1. TOP 20 URIs
echo "🔝 TOP 20 REQUEST_URI POR CONEXÕES:\n";
echo str_repeat("-", 80) . "\n";
$i = 1;
foreach (array_slice($byUri, 0, 20, true) as $uri => $count) {
    $percent = ($count / $totalEntries) * 100;
    $perMinute = $periodMinutes > 0 ? round($count / $periodMinutes, 2) : 0;
    echo sprintf("%2d. %-50s | %6d conexões (%5.1f%%) | ~%5.2f/min\n", 
        $i++, 
        substr($uri, 0, 50), 
        $count, 
        $percent,
        $perMinute
    );
}
echo "\n";

// 2. TOP IPs
echo "🌐 TOP 10 IPs:\n";
echo str_repeat("-", 80) . "\n";
$i = 1;
foreach (array_slice($byIp, 0, 10, true) as $ip => $count) {
    $percent = ($count / $totalEntries) * 100;
    echo sprintf("%2d. %-20s | %6d conexões (%5.1f%%)\n", $i++, $ip, $count, $percent);
}
echo "\n";

// 3. TOP User-Agents
echo "📱 TOP 10 User-Agents:\n";
echo str_repeat("-", 80) . "\n";
$i = 1;
foreach (array_slice($byUserAgent, 0, 10, true) as $ua => $count) {
    $uaShort = strlen($ua) > 60 ? substr($ua, 0, 60) . '...' : $ua;
    $percent = ($count / $totalEntries) * 100;
    echo sprintf("%2d. %-60s | %6d conexões (%5.1f%%)\n", $i++, $uaShort, $count, $percent);
}
echo "\n";

// 4. Timeline (picos)
echo "📈 TIMELINE - CONEXÕES POR MINUTO (últimos 30 minutos):\n";
echo str_repeat("-", 80) . "\n";
$recentMinutes = array_slice($byMinute, -30, 30, true);
$maxCount = max($recentMinutes);
foreach ($recentMinutes as $minute => $count) {
    $barLength = $maxCount > 0 ? round(($count / $maxCount) * 50) : 0;
    $bar = str_repeat('█', $barLength);
    echo sprintf("%s | %4d conexões | %s\n", $minute, $count, $bar);
}
echo "\n";

// 5. Reconexões
if (!empty($reconnects)) {
    echo "⚠️ RECONEXÕES DETECTADAS: " . count($reconnects) . " eventos\n";
    echo str_repeat("-", 80) . "\n";
    
    $reconnectsByUri = [];
    foreach ($reconnects as $entry) {
        $uri = $entry['request_uri'] ?? 'unknown';
        $reconnectsByUri[$uri] = ($reconnectsByUri[$uri] ?? 0) + 1;
    }
    arsort($reconnectsByUri);
    
    foreach (array_slice($reconnectsByUri, 0, 10, true) as $uri => $count) {
        echo sprintf("   %-50s | %4d reconexões\n", substr($uri, 0, 50), $count);
    }
    echo "\n";
}

// 6. Duplas conexões (mesmo request_id com múltiplos connects)
echo "🔄 DUPLAS CONEXÕES (mesmo request_id com múltiplos connects):\n";
echo str_repeat("-", 80) . "\n";
$duplas = [];
foreach ($byRequestId as $requestId => $requestEntries) {
    $connects = array_filter($requestEntries, function($e) {
        return ($e['event'] ?? '') === 'connect';
    });
    if (count($connects) > 1) {
        $uri = $requestEntries[0]['request_uri'] ?? 'unknown';
        $duplas[$uri] = ($duplas[$uri] ?? 0) + 1;
    }
}
if (!empty($duplas)) {
    arsort($duplas);
    foreach (array_slice($duplas, 0, 10, true) as $uri => $count) {
        echo sprintf("   %-50s | %4d requests com múltiplas conexões\n", substr($uri, 0, 50), $count);
    }
} else {
    echo "   ✅ Nenhuma dupla conexão detectada\n";
}
echo "\n";

// 7. Padrões de frequência
echo "🔍 ANÁLISE DE PADRÕES:\n";
echo str_repeat("-", 80) . "\n";

// Detectar polling (requisições em intervalos regulares)
$pollingCandidates = [];
foreach ($byUri as $uri => $count) {
    if ($count > 10 && $periodMinutes > 0) {
        $perMinute = $count / $periodMinutes;
        // Se mais de 1 requisição por minuto, pode ser polling
        if ($perMinute > 1) {
            $interval = round(60 / $perMinute);
            $pollingCandidates[$uri] = [
                'count' => $count,
                'per_minute' => round($perMinute, 2),
                'estimated_interval' => $interval
            ];
        }
    }
}

if (!empty($pollingCandidates)) {
    echo "🔄 POSSÍVEL POLLING (frequência > 1/min):\n";
    foreach ($pollingCandidates as $uri => $data) {
        echo sprintf("   %-50s | %4d conexões | ~%5.2f/min | intervalo estimado: ~%ds\n",
            substr($uri, 0, 50),
            $data['count'],
            $data['per_minute'],
            $data['estimated_interval']
        );
    }
} else {
    echo "   ✅ Nenhum padrão de polling detectado\n";
}
echo "\n";

// 8. Explosões (picos em minutos específicos)
$maxConnectionsPerMinute = max($byMinute);
$avgConnectionsPerMinute = $totalEntries / max($periodMinutes, 1);
$threshold = $avgConnectionsPerMinute * 2; // 2x a média

$explosions = [];
foreach ($byMinute as $minute => $count) {
    if ($count > $threshold) {
        $explosions[$minute] = $count;
    }
}

if (!empty($explosions)) {
    echo "💥 EXPLOSÕES DETECTADAS (picos > 2x a média):\n";
    arsort($explosions);
    foreach (array_slice($explosions, 0, 10, true) as $minute => $count) {
        echo sprintf("   %s | %4d conexões (média: %.1f/min)\n", $minute, $count, $avgConnectionsPerMinute);
    }
} else {
    echo "   ✅ Nenhuma explosão detectada\n";
}
echo "\n";

// 9. CONCLUSÃO - Top 3 culpados
echo "🎯 CONCLUSÃO - TOP 3 CULPADOS PROVÁVEIS:\n";
echo str_repeat("=", 80) . "\n";
$top3 = array_slice($byUri, 0, 3, true);
$i = 1;
foreach ($top3 as $uri => $count) {
    // Encontrar IP e UA mais comum para este URI
    $uriEntries = array_filter($entries, function($e) use ($uri) {
        return ($e['request_uri'] ?? '') === $uri;
    });
    
    $uriIps = [];
    $uriUas = [];
    foreach ($uriEntries as $e) {
        $ip = $e['remote_ip'] ?? 'unknown';
        $ua = $e['user_agent'] ?? 'unknown';
        $uriIps[$ip] = ($uriIps[$ip] ?? 0) + 1;
        $uriUas[$ua] = ($uriUas[$ua] ?? 0) + 1;
    }
    arsort($uriIps);
    arsort($uriUas);
    
    $topIp = array_key_first($uriIps);
    $topUa = array_key_first($uriUas);
    $uaShort = strlen($topUa) > 40 ? substr($topUa, 0, 40) . '...' : $topUa;
    $perMinute = $periodMinutes > 0 ? round($count / $periodMinutes, 2) : 0;
    
    echo sprintf("\n%d. %s\n", $i++, $uri);
    echo sprintf("   📊 %d conexões em %.1f minutos (~%.2f conexões/minuto)\n", $count, $periodMinutes, $perMinute);
    echo sprintf("   🌐 IP mais comum: %s (%d conexões)\n", $topIp, $uriIps[$topIp] ?? 0);
    echo sprintf("   📱 UA mais comum: %s\n", $uaShort);
    
    // Verificar se é polling
    if (isset($pollingCandidates[$uri])) {
        echo sprintf("   ⚠️ PADRÃO: Polling detectado (~%ds de intervalo)\n", $pollingCandidates[$uri]['estimated_interval']);
    }
    
    // Verificar reconexões
    $reconnectsCount = isset($reconnectsByUri[$uri]) ? $reconnectsByUri[$uri] : 0;
    if ($reconnectsCount > 0) {
        echo sprintf("   ⚠️ INSTABILIDADE: %d reconexões detectadas\n", $reconnectsCount);
    }
}

echo "\n" . str_repeat("=", 80) . "\n";
echo "✅ Análise concluída\n";
