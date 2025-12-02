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

                    <div class="log-box" id="logBox" style="background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 600px; overflow-y: auto;">
                        <?php
                        try {
                            $db = Database::getInstance();
                            $pdo = $db->getConnection();
                            
                            echo '<div class="log-info" style="color: #569cd6;">🚀 Iniciando diagnóstico para aluno ID: ' . $alunoId . '</div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏰ ' . date('Y-m-d H:i:s') . '</div>';
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
                            
                            echo '<div class="log-success" style="color: #4ec9b0;"><strong>✅ Diagnóstico concluído!</strong></div>';
                            echo '<div class="log-info" style="color: #569cd6;">⏱️ Tempo total: ' . number_format($totalTime, 2) . ' ms</div>';
                            
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

