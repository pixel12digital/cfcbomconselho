<?php
/**
 * Página de Diagnóstico de Queries Lentas
 * Sistema CFC - Bom Conselho
 * 
 * Acessível via: index.php?page=diagnostico-queries
 */

// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    require_once '../../includes/auth.php';
    
    // Verificar se usuário está logado
    if (!isLoggedIn()) {
        header('Location: ../../index.php');
        exit;
    }
}

// Verificar se é administrador
$currentUser = getCurrentUser();
if (!$currentUser || ($currentUser['tipo'] ?? '') !== 'admin') {
    echo '<div class="alert alert-danger">Acesso negado. Apenas administradores podem executar este script.</div>';
    return;
}

$alunoId = $_GET['aluno_id'] ?? 170; // ID padrão para teste
$alunoId = (int)$alunoId;

?>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-danger text-white">
                    <h3 class="mb-0">🔍 Diagnóstico de Queries Lentas</h3>
                </div>
                <div class="card-body">
                    <form method="GET" class="mb-4">
                        <input type="hidden" name="page" value="diagnostico-queries">
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label"><strong>ID do Aluno:</strong></label>
                                <input type="number" name="aluno_id" class="form-control" value="<?php echo $alunoId; ?>" required>
                            </div>
                            <div class="col-md-6 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">🔍 Analisar</button>
                            </div>
                        </div>
                    </form>

                    <div class="alert alert-info">
                        <strong>💡 Diagnóstico Completo:</strong> Este script testa tanto as queries SQL isoladas quanto as chamadas HTTP reais dos endpoints para identificar onde está o gargalo.
                    </div>

                    <div class="log-box" id="logBox" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 600px; overflow-y: auto;">
                        <?php
                        try {
                            $db = Database::getInstance();
                            $pdo = $db->getConnection();
                            
                            echo '<div class="log-info" style="color: #569cd6;">🚀 Iniciando diagnóstico para aluno ID: ' . $alunoId . '</div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏰ ' . date('Y-m-d H:i:s') . '</div>';
                            echo '<hr style="border-color: #555;">';
                            
                            echo '<div class="log-info" style="color: #569cd6;"><strong>📊 FASE 1: Testando Queries SQL Isoladas</strong></div>';
                            echo '<hr style="border-color: #555;">';
                            
                            // Testar cada endpoint problemático
                            $endpoints = [
                                'progresso_pratico' => "
                                    SELECT 
                                        COUNT(CASE WHEN status = 'concluida' THEN 1 END) as total_realizadas,
                                        COUNT(CASE WHEN status IN ('agendada', 'em_andamento') THEN 1 END) as total_agendadas,
                                        COUNT(CASE WHEN status != 'cancelada' THEN 1 END) as total_nao_canceladas,
                                        MIN(CASE WHEN status != 'cancelada' THEN data_aula END) as primeira_aula,
                                        MAX(CASE WHEN status != 'cancelada' THEN data_aula END) as ultima_aula
                                    FROM aulas
                                    WHERE aluno_id = ? 
                                    AND tipo_aula = 'pratica'
                                ",
                                'progresso_teorico' => "
                                    SELECT 
                                        tm.status,
                                        tm.frequencia_percentual,
                                        tm.data_matricula,
                                        tm.exames_validados_em,
                                        tm.turma_id,
                                        t.nome AS turma_nome
                                    FROM turma_matriculas tm
                                    INNER JOIN turmas_teoricas t ON tm.turma_id = t.id
                                    WHERE tm.aluno_id = ?
                                    ORDER BY tm.data_matricula DESC, tm.id DESC
                                    LIMIT 1
                                ",
                                'exames_resumo' => "
                                    SELECT 
                                        id,
                                        tipo,
                                        status,
                                        resultado,
                                        data_agendada,
                                        data_resultado,
                                        protocolo,
                                        clinica_nome
                                    FROM exames
                                    WHERE aluno_id = ?
                                    AND tipo IN ('teorico', 'pratico')
                                    ORDER BY tipo ASC, data_agendada DESC, data_resultado DESC
                                    LIMIT 10
                                ",
                                'historico_aluno' => "
                                    SELECT id, nome, criado_em, atualizado_em
                                    FROM alunos
                                    WHERE id = ?
                                ",
                                'historico_matriculas' => "
                                    SELECT id, aluno_id, categoria_cnh, tipo_servico, status, data_inicio, data_fim, criado_em
                                    FROM matriculas
                                    WHERE aluno_id = ?
                                    ORDER BY data_inicio DESC, id DESC
                                    LIMIT 50
                                ",
                                'historico_exames' => "
                                    SELECT id, aluno_id, tipo, status, resultado, data_agendada, data_resultado, protocolo, clinica_nome
                                    FROM exames
                                    WHERE aluno_id = ?
                                    ORDER BY data_agendada DESC, data_resultado DESC
                                    LIMIT 100
                                ",
                                'historico_faturas' => "
                                    SELECT 
                                        id,
                                        aluno_id,
                                        matricula_id,
                                        descricao,
                                        valor,
                                        vencimento,
                                        status,
                                        criado_em
                                    FROM faturas
                                    WHERE aluno_id = ?
                                    ORDER BY vencimento DESC, criado_em DESC
                                    LIMIT 100
                                ",
                                'progresso_pratico_matriculas' => "
                                    SELECT aulas_praticas_contratadas
                                    FROM matriculas
                                    WHERE aluno_id = ? 
                                    AND status = 'ativa'
                                    ORDER BY data_inicio DESC
                                    LIMIT 1
                                "
                            ];
                            
                            $totalTime = 0;
                            $slowQueries = [];
                            
                            foreach ($endpoints as $nome => $sql) {
                                echo '<div class="log-info" style="color: #569cd6;">📋 Testando: ' . $nome . '</div>';
                                
                                $startTime = microtime(true);
                                
                                try {
                                    // Executar EXPLAIN primeiro
                                    $explainSql = "EXPLAIN " . $sql;
                                    $explainStmt = $pdo->prepare($explainSql);
                                    $explainStmt->execute([$alunoId]);
                                    $explain = $explainStmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    // Executar query real
                                    $stmt = $pdo->prepare($sql);
                                    $stmt->execute([$alunoId]);
                                    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    $endTime = microtime(true);
                                    $queryTime = ($endTime - $startTime) * 1000; // em ms
                                    $totalTime += $queryTime;
                                    
                                    $timeClass = $queryTime > 1000 ? 'log-error' : ($queryTime > 500 ? 'log-warning' : 'log-success');
                                    echo '<div class="' . $timeClass . '" style="color: ' . ($queryTime > 1000 ? '#f48771' : ($queryTime > 500 ? '#dcdcaa' : '#4ec9b0')) . ';">   ⏱️ Tempo: ' . number_format($queryTime, 2) . ' ms</div>';
                                    echo '<div class="log-info" style="color: #569cd6;">   📊 Registros retornados: ' . count($result) . '</div>';
                                    
                                    // Verificar uso de índices
                                    $usesIndex = false;
                                    foreach ($explain as $row) {
                                        if (!empty($row['key']) && $row['key'] !== 'NULL') {
                                            $usesIndex = true;
                                            echo '<div class="log-success" style="color: #4ec9b0;">   ✅ Usa índice: ' . $row['key'] . '</div>';
                                        }
                                    }
                                    
                                    if (!$usesIndex) {
                                        echo '<div class="log-warning" style="color: #dcdcaa;">   ⚠️ Não usa índice (Full Table Scan)</div>';
                                    }
                                    
                                    // Mostrar EXPLAIN resumido
                                    if (count($explain) > 0) {
                                        echo '<div class="log-info" style="margin-left: 20px; font-size: 10px; color: #569cd6;">';
                                        echo '   EXPLAIN: ';
                                        $explainInfo = [];
                                        foreach ($explain as $row) {
                                            $info = [];
                                            if (!empty($row['key'])) $info[] = 'key=' . $row['key'];
                                            if (!empty($row['type'])) $info[] = 'type=' . $row['type'];
                                            if (!empty($row['rows'])) $info[] = 'rows=' . $row['rows'];
                                            $explainInfo[] = implode(', ', $info);
                                        }
                                        echo implode(' | ', $explainInfo);
                                        echo '</div>';
                                    }
                                    
                                    if ($queryTime > 1000) {
                                        $slowQueries[] = [
                                            'nome' => $nome,
                                            'tempo' => $queryTime,
                                            'sql' => $sql
                                        ];
                                    }
                                    
                                } catch (Exception $e) {
                                    $endTime = microtime(true);
                                    $queryTime = ($endTime - $startTime) * 1000;
                                    echo '<div class="log-error" style="color: #f48771;">   ❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    echo '<div class="log-error" style="color: #f48771;">   ⏱️ Tempo até erro: ' . number_format($queryTime, 2) . ' ms</div>';
                                }
                                
                                echo '<hr style="border-color: #555;">';
                                
                                // Pequeno delay entre queries
                                usleep(100000);
                                if (ob_get_level() > 0) ob_flush();
                                flush();
                            }
                            
                            echo '<div class="log-success" style="color: #4ec9b0;"><strong>✅ FASE 1 concluída!</strong></div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏱️ Tempo total SQL: ' . number_format($totalTime, 2) . ' ms</div>';
                            
                            if (!empty($slowQueries)) {
                                echo '<div class="log-error" style="color: #f48771;"><strong>⚠️ Queries lentas encontradas:</strong></div>';
                                foreach ($slowQueries as $slow) {
                                    echo '<div style="background: #3a1f1f; padding: 5px; margin: 5px 0;">';
                                    echo '<div class="log-error" style="color: #f48771;">   ' . $slow['nome'] . ': ' . number_format($slow['tempo'], 2) . ' ms</div>';
                                    echo '<div class="log-info" style="font-size: 10px; color: #569cd6;">   SQL: ' . htmlspecialchars(substr($slow['sql'], 0, 200)) . '...</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="log-success" style="color: #4ec9b0;">✅ Nenhuma query muito lenta encontrada (todas < 1000ms)</div>';
                            }
                            
                            echo '<hr style="border-color: #555;">';
                            echo '<div class="log-info" style="color: #569cd6;"><strong>📡 FASE 2: Testando Chamadas HTTP Reais</strong></div>';
                            echo '<div class="log-warning" style="color: #dcdcaa;">⚠️ Isso simula o que o frontend faz - inclui latência de rede HTTP</div>';
                            echo '<hr style="border-color: #555;">';
                            
                            // Testar chamadas HTTP reais
                            // Construir caminho correto para admin/api
                            // Como a página é acessada via index.php?page=diagnostico-queries,
                            // o SCRIPT_NAME aponta para /admin/index.php
                            $scriptPath = $_SERVER['SCRIPT_NAME'] ?? $_SERVER['PHP_SELF'] ?? '';
                            
                            // Extrair o caminho base do admin a partir do SCRIPT_NAME
                            // Ex: /admin/index.php -> /admin
                            if (strpos($scriptPath, '/admin/') !== false) {
                                // Se contém /admin/, extrair até /admin
                                $adminPath = '/admin';
                            } else {
                                // Fallback: tentar calcular a partir do diretório atual
                                $requestUri = $_SERVER['REQUEST_URI'] ?? '';
                                if (preg_match('#(/admin/)#', $requestUri, $matches)) {
                                    $adminPath = $matches[1];
                                    $adminPath = rtrim($adminPath, '/');
                                } else {
                                    // Último fallback: usar caminho fixo
                                    $adminPath = '/admin';
                                }
                            }
                            
                            $httpEndpoints = [
                                'progresso_pratico' => $adminPath . '/api/progresso_pratico.php?aluno_id=' . $alunoId,
                                'progresso_teorico' => $adminPath . '/api/progresso_teorico.php?aluno_id=' . $alunoId,
                                'exames_resumo' => $adminPath . '/api/exames.php?aluno_id=' . $alunoId . '&resumo=1',
                                'historico_aluno' => $adminPath . '/api/historico_aluno.php?aluno_id=' . $alunoId
                            ];
                            
                            // Debug: mostrar caminho calculado
                            echo '<div class="log-info" style="color: #569cd6; font-size: 10px;">   🔍 Debug: scriptPath=' . htmlspecialchars($scriptPath) . ', adminPath=' . htmlspecialchars($adminPath) . '</div>';
                            
                            $totalHttpTime = 0;
                            $slowHttpRequests = [];
                            
                            // IMPORTANTE: Fechar sessão antes de fazer requisições cURL
                            // Isso evita bloqueio de sessão (session locking) que causa deadlock
                            if (session_status() === PHP_SESSION_ACTIVE) {
                                session_write_close();
                            }
                            
                            foreach ($httpEndpoints as $nome => $url) {
                                echo '<div class="log-info" style="color: #569cd6;">📡 Testando HTTP: ' . $nome . '</div>';
                                echo '<div class="log-info" style="color: #569cd6; font-size: 10px;">   URL: ' . htmlspecialchars($url) . '</div>';
                                
                                $startTime = microtime(true);
                                
                                try {
                                    // Construir URL completa
                                    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http');
                                    $host = $_SERVER['HTTP_HOST'];
                                    $fullUrl = $protocol . '://' . $host . $url;
                                    
                                    // Obter cookie de sessão ANTES de fechar a sessão
                                    $sessionCookie = session_name() . '=' . session_id();
                                    
                                    $ch = curl_init($fullUrl);
                                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                                    curl_setopt($ch, CURLOPT_TIMEOUT, 15); // Aumentar timeout para 15s
                                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                                    curl_setopt($ch, CURLOPT_HTTPHEADER, [
                                        'Cookie: ' . $sessionCookie
                                    ]);
                                    
                                    $response = curl_exec($ch);
                                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                                    $curlError = curl_error($ch);
                                    curl_close($ch);
                                    
                                    $endTime = microtime(true);
                                    $httpTime = ($endTime - $startTime) * 1000; // em ms
                                    $totalHttpTime += $httpTime;
                                    
                                    $timeClass = $httpTime > 1000 ? 'log-error' : ($httpTime > 500 ? 'log-warning' : 'log-success');
                                    echo '<div class="' . $timeClass . '" style="color: ' . ($httpTime > 1000 ? '#f48771' : ($httpTime > 500 ? '#dcdcaa' : '#4ec9b0')) . ';">   ⏱️ Tempo HTTP: ' . number_format($httpTime, 2) . ' ms</div>';
                                    echo '<div class="log-info" style="color: #569cd6;">   📊 Status HTTP: ' . $httpCode . '</div>';
                                    
                                    if ($curlError) {
                                        echo '<div class="log-error" style="color: #f48771;">   ❌ Erro cURL: ' . htmlspecialchars($curlError) . '</div>';
                                    }
                                    
                                    if ($httpTime > 1000) {
                                        $slowHttpRequests[] = [
                                            'nome' => $nome,
                                            'tempo' => $httpTime,
                                            'url' => $url
                                        ];
                                    }
                                    
                                } catch (Exception $e) {
                                    $endTime = microtime(true);
                                    $httpTime = ($endTime - $startTime) * 1000;
                                    echo '<div class="log-error" style="color: #f48771;">   ❌ Erro: ' . htmlspecialchars($e->getMessage()) . '</div>';
                                    echo '<div class="log-error" style="color: #f48771;">   ⏱️ Tempo até erro: ' . number_format($httpTime, 2) . ' ms</div>';
                                }
                                
                                echo '<hr style="border-color: #555;">';
                                
                                usleep(200000); // Delay maior entre requisições HTTP
                                if (ob_get_level() > 0) ob_flush();
                                flush();
                            }
                            
                            echo '<div class="log-success" style="color: #4ec9b0;"><strong>✅ FASE 2 concluída!</strong></div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏱️ Tempo total HTTP: ' . number_format($totalHttpTime, 2) . ' ms</div>';
                            
                            if (!empty($slowHttpRequests)) {
                                echo '<div class="log-error" style="color: #f48771;"><strong>⚠️ Requisições HTTP lentas encontradas:</strong></div>';
                                foreach ($slowHttpRequests as $slow) {
                                    echo '<div style="background: #3a1f1f; padding: 5px; margin: 5px 0;">';
                                    echo '<div class="log-error" style="color: #f48771;">   ' . $slow['nome'] . ': ' . number_format($slow['tempo'], 2) . ' ms</div>';
                                    echo '<div class="log-info" style="font-size: 10px; color: #569cd6;">   URL: ' . htmlspecialchars($slow['url']) . '</div>';
                                    echo '</div>';
                                }
                            } else {
                                echo '<div class="log-success" style="color: #4ec9b0;">✅ Nenhuma requisição HTTP muito lenta encontrada (todas < 1000ms)</div>';
                            }
                            
                            echo '<hr style="border-color: #555;">';
                            echo '<div class="log-success" style="color: #4ec9b0;"><strong>✅ Diagnóstico completo concluído!</strong></div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏱️ Tempo total SQL: ' . number_format($totalTime, 2) . ' ms</div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏱️ Tempo total HTTP: ' . number_format($totalHttpTime, 2) . ' ms</div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏱️ Diferença (latência de rede + overhead): ' . number_format($totalHttpTime - $totalTime, 2) . ' ms</div>';
                            
                            if (($totalHttpTime - $totalTime) > 2000) {
                                echo '<div class="log-warning" style="color: #dcdcaa;"><strong>⚠️ ATENÇÃO: Grande diferença entre tempo SQL e HTTP!</strong></div>';
                                echo '<div class="log-warning" style="color: #dcdcaa;">   Isso indica que o problema pode ser:</div>';
                                echo '<div class="log-warning" style="color: #dcdcaa;">   - Latência de rede alta (banco remoto)</div>';
                                echo '<div class="log-warning" style="color: #dcdcaa;">   - Processamento PHP adicional após queries</div>';
                                echo '<div class="log-warning" style="color: #dcdcaa;">   - Overhead de requisições HTTP</div>';
                            }
                            
                        } catch (Exception $e) {
                            echo '<div class="log-error" style="color: #f48771;">❌ ERRO: ' . htmlspecialchars($e->getMessage()) . '</div>';
                        }
                        ?>
                    </div>

                    <div class="mt-4">
                        <a href="index.php?page=diagnostico-queries" class="btn btn-primary">🔄 Testar Outro Aluno</a>
                        <a href="index.php" class="btn btn-secondary">🏠 Voltar ao Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const logBox = document.getElementById('logBox');
    if (logBox) {
        logBox.scrollTop = logBox.scrollHeight;
    }
</script>

