<?php
/**
 * Relatório de Conexões ao Banco de Dados
 * Ferramenta interna para análise de conexões e diagnóstico de max_connections_per_hour
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar se usuário é admin/master
$user = getCurrentUser();
if (!$user || !in_array($user['tipo'], ['admin'])) {
    http_response_code(403);
    die('Acesso negado. Apenas administradores podem acessar este relatório.');
}

$logDir = __DIR__ . '/../../storage/logs';
$logFile = $logDir . '/db_connections.jsonl';

// Parâmetros
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 2000;
$minutes = isset($_GET['minutes']) ? (int)$_GET['minutes'] : 60;
$download = isset($_GET['download']) && $_GET['download'] === '1';

// Função para ler últimas N linhas de arquivo grande
function readLastLines($file, $lines) {
    $handle = @fopen($file, 'r');
    if (!$handle) return [];
    
    $lineArray = [];
    $pos = -1;
    $currentLine = '';
    $eof = false;
    
    // Ir para o final do arquivo
    fseek($handle, -1, SEEK_END);
    
    // Ler linha por linha do final
    while ($lines > 0 && !$eof) {
        $char = fgetc($handle);
        if ($char === "\n") {
            if (strlen($currentLine) > 0) {
                array_unshift($lineArray, $currentLine);
                $currentLine = '';
                $lines--;
            }
        } else {
            $currentLine = $char . $currentLine;
        }
        
        if (ftell($handle) <= 1) {
            $eof = true;
            if (strlen($currentLine) > 0) {
                array_unshift($lineArray, $currentLine);
            }
        } else {
            fseek($handle, -2, SEEK_CUR);
        }
    }
    
    fclose($handle);
    return $lineArray;
}

// Ler logs
$logEntries = [];
if (file_exists($logFile)) {
    $rawLines = readLastLines($logFile, $lines);
    foreach ($rawLines as $line) {
        $entry = @json_decode(trim($line), true);
        if ($entry) {
            // Filtrar por minutos se especificado
            if ($minutes > 0) {
                $entryTime = strtotime($entry['timestamp']);
                $cutoffTime = time() - ($minutes * 60);
                if ($entryTime >= $cutoffTime) {
                    $logEntries[] = $entry;
                }
            } else {
                $logEntries[] = $entry;
            }
        }
    }
}

// Download JSON se solicitado
if ($download) {
    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename="db_connections_' . date('Ymd_His') . '.json"');
    echo json_encode($logEntries, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    exit;
}

// Agregações
$byUri = [];
$byIp = [];
$byUserAgent = [];
$byMinute = [];
$byEvent = [];

foreach ($logEntries as $entry) {
    // Por URI
    $uri = $entry['request_uri'] ?? 'unknown';
    $byUri[$uri] = ($byUri[$uri] ?? 0) + 1;
    
    // Por IP
    $ip = $entry['remote_ip'] ?? 'unknown';
    $byIp[$ip] = ($byIp[$ip] ?? 0) + 1;
    
    // Por User-Agent
    $ua = $entry['user_agent'] ?? 'unknown';
    $byUserAgent[$ua] = ($byUserAgent[$ua] ?? 0) + 1;
    
    // Por minuto
    $minute = substr($entry['timestamp'], 0, 16); // YYYY-MM-DDTHH:MM
    $byMinute[$minute] = ($byMinute[$minute] ?? 0) + 1;
    
    // Por evento
    $event = $entry['event'] ?? 'unknown';
    $byEvent[$event] = ($byEvent[$event] ?? 0) + 1;
}

// Ordenar
arsort($byUri);
arsort($byIp);
arsort($byUserAgent);
arsort($byMinute);
arsort($byEvent);

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Conexões ao Banco</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1A365D;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .filters {
            background: #f9f9f9;
            padding: 20px;
            border-radius: 6px;
            margin-bottom: 30px;
        }
        .filters form {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: end;
        }
        .filters label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            color: #333;
        }
        .filters input, .filters select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .filters button {
            padding: 8px 20px;
            background: #1A365D;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
        }
        .filters button:hover {
            background: #0f2a47;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: linear-gradient(135deg, #1A365D 0%, #2c5a8a 100%);
            color: white;
            padding: 20px;
            border-radius: 6px;
        }
        .stat-card h3 {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
        }
        .stat-card .value {
            font-size: 32px;
            font-weight: bold;
        }
        .section {
            margin-bottom: 40px;
        }
        .section h2 {
            color: #1A365D;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #1A365D;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f9f9f9;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #f9f9f9;
        }
        .uri-cell {
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .count {
            font-weight: bold;
            color: #1A365D;
        }
        .timeline {
            background: #f9f9f9;
            padding: 15px;
            border-radius: 6px;
            max-height: 400px;
            overflow-y: auto;
        }
        .timeline-item {
            display: flex;
            justify-content: space-between;
            padding: 8px;
            border-bottom: 1px solid #eee;
        }
        .timeline-item:last-child {
            border-bottom: none;
        }
        .bar {
            background: #1A365D;
            height: 20px;
            border-radius: 3px;
            margin-top: 5px;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #999;
        }
        .alert {
            background: #fff3cd;
            border: 1px solid #ffc107;
            color: #856404;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Relatório de Conexões ao Banco de Dados</h1>
        <p class="subtitle">Análise de conexões para diagnóstico de max_connections_per_hour</p>
        
        <?php if (!file_exists($logFile)): ?>
            <div class="alert">
                ⚠️ Arquivo de log não encontrado: <code><?php echo htmlspecialchars($logFile); ?></code>
            </div>
        <?php else: ?>
            <div class="filters">
                <form method="GET">
                    <div>
                        <label>Últimas linhas:</label>
                        <input type="number" name="lines" value="<?php echo $lines; ?>" min="100" max="10000" step="100">
                    </div>
                    <div>
                        <label>Últimos minutos:</label>
                        <input type="number" name="minutes" value="<?php echo $minutes; ?>" min="0" max="1440">
                        <small style="display: block; color: #666; margin-top: 5px;">0 = todas</small>
                    </div>
                    <div>
                        <button type="submit">Atualizar</button>
                    </div>
                    <div>
                        <a href="?<?php echo http_build_query(array_merge($_GET, ['download' => '1'])); ?>" style="padding: 8px 20px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; display: inline-block;">📥 Download JSON</a>
                    </div>
                </form>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>Total de Entradas</h3>
                    <div class="value"><?php echo number_format(count($logEntries)); ?></div>
                </div>
                <div class="stat-card">
                    <h3>URIs Únicas</h3>
                    <div class="value"><?php echo number_format(count($byUri)); ?></div>
                </div>
                <div class="stat-card">
                    <h3>IPs Únicos</h3>
                    <div class="value"><?php echo number_format(count($byIp)); ?></div>
                </div>
                <div class="stat-card">
                    <h3>Período</h3>
                    <div class="value"><?php echo $minutes > 0 ? $minutes . ' min' : 'Todas'; ?></div>
                </div>
            </div>
            
            <?php if (empty($logEntries)): ?>
                <div class="no-data">
                    <p>Nenhuma entrada encontrada no período especificado.</p>
                </div>
            <?php else: ?>
                <div class="section">
                    <h2>📊 Top 20 URIs por Conexões</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>URI</th>
                                <th>Conexões</th>
                                <th>%</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $top20 = array_slice($byUri, 0, 20, true);
                            $maxCount = max($top20);
                            $i = 1;
                            foreach ($top20 as $uri => $count): 
                            ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td class="uri-cell" title="<?php echo htmlspecialchars($uri); ?>">
                                        <?php echo htmlspecialchars($uri); ?>
                                    </td>
                                    <td class="count"><?php echo number_format($count); ?></td>
                                    <td>
                                        <div style="width: 200px;">
                                            <div class="bar" style="width: <?php echo ($count / $maxCount) * 100; ?>%;"></div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="section">
                    <h2>📈 Conexões por Minuto (Timeline)</h2>
                    <div class="timeline">
                        <?php 
                        $maxMinute = max($byMinute);
                        foreach (array_slice($byMinute, -30, 30, true) as $minute => $count): 
                        ?>
                            <div class="timeline-item">
                                <span><?php echo $minute; ?></span>
                                <span class="count"><?php echo number_format($count); ?></span>
                            </div>
                            <div class="bar" style="width: <?php echo ($count / $maxMinute) * 100; ?>%;"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="section">
                    <h2>🌐 Top 10 IPs</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>IP</th>
                                <th>Conexões</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($byIp, 0, 10, true) as $ip => $count): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($ip); ?></td>
                                    <td class="count"><?php echo number_format($count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="section">
                    <h2>📱 Top 10 User-Agents</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>User-Agent</th>
                                <th>Conexões</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach (array_slice($byUserAgent, 0, 10, true) as $ua => $count): ?>
                                <tr>
                                    <td style="max-width: 500px; overflow: hidden; text-overflow: ellipsis;" title="<?php echo htmlspecialchars($ua); ?>">
                                        <?php echo htmlspecialchars($ua); ?>
                                    </td>
                                    <td class="count"><?php echo number_format($count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="section">
                    <h2>⚡ Eventos por Tipo</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Evento</th>
                                <th>Quantidade</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($byEvent as $event => $count): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($event); ?></strong></td>
                                    <td class="count"><?php echo number_format($count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</body>
</html>
