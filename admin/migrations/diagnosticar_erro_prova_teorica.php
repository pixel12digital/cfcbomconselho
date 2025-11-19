<?php
/**
 * Diagnóstico: Erro ao verificar prova teórica
 * 
 * Este script diagnostica por que está aparecendo "Erro ao verificar prova teórica"
 * quando tenta agendar aula prática.
 */

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/ExamesRulesService.php';

$alunoId = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 112; // ID do aluno JEFFERSON
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diagnóstico: Erro ao Verificar Prova Teórica</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .section h2 {
            margin-top: 0;
            color: #333;
            border-bottom: 2px solid #007bff;
            padding-bottom: 10px;
        }
        .error {
            color: #dc3545;
            background: #f8d7da;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #dc3545;
        }
        .success {
            color: #155724;
            background: #d4edda;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #28a745;
        }
        .warning {
            color: #856404;
            background: #fff3cd;
            padding: 10px;
            border-radius: 4px;
            border-left: 4px solid #ffc107;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
            font-weight: bold;
        }
        pre {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <h1>🔍 Diagnóstico: Erro ao Verificar Prova Teórica</h1>
    <p><strong>Aluno ID:</strong> <?php echo $alunoId; ?></p>

    <?php
    try {
        $db = db();
        
        // 1. Verificar se o aluno existe
        echo '<div class="section">';
        echo '<h2>1. Verificar Aluno</h2>';
        $aluno = $db->fetch("SELECT id, nome, cpf FROM alunos WHERE id = ?", [$alunoId]);
        if ($aluno) {
            echo '<div class="success">✅ Aluno encontrado: ' . htmlspecialchars($aluno['nome']) . ' (CPF: ' . htmlspecialchars($aluno['cpf']) . ')</div>';
        } else {
            echo '<div class="error">❌ Aluno não encontrado!</div>';
            exit;
        }
        echo '</div>';

        // 2. Verificar estrutura da tabela exames
        echo '<div class="section">';
        echo '<h2>2. Estrutura da Tabela exames</h2>';
        $colunas = $db->fetchAll("SHOW COLUMNS FROM exames");
        echo '<table>';
        echo '<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Chave</th><th>Padrão</th></tr>';
        foreach ($colunas as $col) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($col['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';

        // 3. Verificar exames do aluno
        echo '<div class="section">';
        echo '<h2>3. Exames do Aluno (ID: ' . $alunoId . ')</h2>';
        $exames = $db->fetchAll("
            SELECT id, tipo, status, resultado, data_agendada, data_resultado,
                   LENGTH(COALESCE(tipo, '')) as tipo_length,
                   HEX(COALESCE(tipo, '')) as tipo_hex
            FROM exames
            WHERE aluno_id = ?
            ORDER BY id DESC
        ", [$alunoId]);
        
        if (empty($exames)) {
            echo '<div class="warning">⚠️ Nenhum exame encontrado para este aluno.</div>';
        } else {
            echo '<table>';
            echo '<tr><th>ID</th><th>Tipo</th><th>Status</th><th>Resultado</th><th>Data Agendada</th><th>Data Resultado</th><th>Tipo (Length)</th><th>Tipo (HEX)</th></tr>';
            foreach ($exames as $exame) {
                $tipoExibicao = $exame['tipo'] ? htmlspecialchars($exame['tipo']) : '<span style="color:red;">VAZIO</span>';
                echo '<tr>';
                echo '<td>' . $exame['id'] . '</td>';
                echo '<td>' . $tipoExibicao . '</td>';
                echo '<td>' . htmlspecialchars($exame['status']) . '</td>';
                echo '<td>' . htmlspecialchars($exame['resultado'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($exame['data_agendada'] ?? 'NULL') . '</td>';
                echo '<td>' . htmlspecialchars($exame['data_resultado'] ?? 'NULL') . '</td>';
                echo '<td>' . $exame['tipo_length'] . '</td>';
                echo '<td>' . $exame['tipo_hex'] . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';

        // 4. Tentar buscar prova teórica diretamente
        echo '<div class="section">';
        echo '<h2>4. Buscar Prova Teórica (Query Específica)</h2>';
        try {
            $provaTeorica = $db->fetch("
                SELECT tipo, status, resultado, data_resultado
                FROM exames 
                WHERE aluno_id = ? 
                AND tipo = 'teorico' 
                AND status = 'concluido'
                ORDER BY data_resultado DESC
                LIMIT 1
            ", [$alunoId]);
            
            if ($provaTeorica) {
                echo '<div class="success">✅ Prova teórica encontrada!</div>';
                echo '<pre>' . print_r($provaTeorica, true) . '</pre>';
                
                $provaAprovada = $provaTeorica['resultado'] === 'aprovado' || $provaTeorica['resultado'] === 'apto';
                if ($provaAprovada) {
                    echo '<div class="success">✅ Prova teórica APROVADA - pode agendar aula prática!</div>';
                } else {
                    echo '<div class="warning">⚠️ Prova teórica encontrada, mas resultado é: "' . htmlspecialchars($provaTeorica['resultado']) . '" (esperado: "aprovado" ou "apto")</div>';
                }
            } else {
                echo '<div class="warning">⚠️ Nenhuma prova teórica CONCLUÍDA encontrada para este aluno.</div>';
                
                // Verificar se há prova teórica agendada
                $provaAgendada = $db->fetch("
                    SELECT tipo, status, resultado, data_agendada
                    FROM exames 
                    WHERE aluno_id = ? 
                    AND tipo = 'teorico'
                    ORDER BY data_agendada DESC
                    LIMIT 1
                ", [$alunoId]);
                
                if ($provaAgendada) {
                    echo '<div class="warning">ℹ️ Existe prova teórica AGENDADA (status: ' . htmlspecialchars($provaAgendada['status']) . '), mas não está concluída.</div>';
                } else {
                    echo '<div class="error">❌ Nenhuma prova teórica encontrada (nem agendada nem concluída).</div>';
                }
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ ERRO ao executar query: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        echo '</div>';

        // 5. Testar ExamesRulesService
        echo '<div class="section">';
        echo '<h2>5. Testar ExamesRulesService::podeAgendarAulaPratica()</h2>';
        try {
            $service = new ExamesRulesService();
            $resultado = $service->podeAgendarAulaPratica($alunoId);
            
            echo '<div class="success">✅ Service executado sem exceção!</div>';
            echo '<pre>' . print_r($resultado, true) . '</pre>';
            
            if ($resultado['ok']) {
                echo '<div class="success">✅ RESULTADO: ' . htmlspecialchars($resultado['mensagem']) . '</div>';
            } else {
                echo '<div class="error">❌ RESULTADO: ' . htmlspecialchars($resultado['mensagem']) . '</div>';
                echo '<div class="warning">Código do erro: ' . htmlspecialchars($resultado['codigo']) . '</div>';
            }
        } catch (Exception $e) {
            echo '<div class="error">❌ EXCEÇÃO capturada ao executar service:</div>';
            echo '<div class="error">Mensagem: ' . htmlspecialchars($e->getMessage()) . '</div>';
            echo '<div class="error">Arquivo: ' . htmlspecialchars($e->getFile()) . ':' . $e->getLine() . '</div>';
            echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        }
        echo '</div>';

        // 6. Verificar logs de erro
        echo '<div class="section">';
        echo '<h2>6. Últimos Logs de Erro</h2>';
        $logFile = __DIR__ . '/../logs/exames_simple_errors.log';
        if (file_exists($logFile)) {
            $logs = file($logFile);
            $ultimosLogs = array_slice($logs, -20);
            echo '<pre>' . htmlspecialchars(implode('', $ultimosLogs)) . '</pre>';
        } else {
            echo '<div class="warning">⚠️ Arquivo de log não encontrado: ' . htmlspecialchars($logFile) . '</div>';
        }
        echo '</div>';

    } catch (Exception $e) {
        echo '<div class="section">';
        echo '<h2>❌ Erro Geral</h2>';
        echo '<div class="error">' . htmlspecialchars($e->getMessage()) . '</div>';
        echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
        echo '</div>';
    }
    ?>

    <div class="section">
        <h2>📝 Ações Recomendadas</h2>
        <ol>
            <li>Verificar se o aluno possui prova teórica agendada/concluída na tabela <code>exames</code></li>
            <li>Se a prova teórica não estiver com status='concluido', finalizar o exame primeiro</li>
            <li>Se a prova teórica não estiver com resultado='aprovado' ou 'apto', atualizar o resultado</li>
            <li>Verificar se o campo <code>tipo</code> na tabela <code>exames</code> está correto (deve ser 'teorico')</li>
            <li>Verificar logs de erro para mais detalhes</li>
        </ol>
    </div>
</body>
</html>

