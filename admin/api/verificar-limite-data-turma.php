<?php
/**
 * API para verificar limite de data da turma e agendamentos
 * GET: ?turma_id=9
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';

    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

    if (!$turmaId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'turma_id é obrigatório']);
        exit;
    }

    $db = db();
    
    // Buscar dados da turma
    $turma = $db->fetch(
        "SELECT id, nome, data_inicio, data_fim, status FROM turmas_teoricas WHERE id = ?",
        [$turmaId]
    );
    
    if (!$turma) {
        echo json_encode([
            'success' => false,
            'message' => 'Turma não encontrada'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // Buscar agendamentos após a data_fim da turma
    $agendamentosAposDataFim = $db->fetchAll(
        "SELECT 
            taa.id,
            taa.nome_aula,
            taa.data_aula,
            taa.hora_inicio,
            taa.hora_fim,
            taa.status
         FROM turma_aulas_agendadas taa
         WHERE taa.turma_id = ? 
         AND taa.data_aula > ?
         ORDER BY taa.data_aula ASC",
        [$turmaId, $turma['data_fim']]
    );
    
    // Buscar último agendamento
    $ultimoAgendamento = $db->fetch(
        "SELECT 
            MAX(data_aula) as ultima_data,
            COUNT(*) as total_apos_data_fim
         FROM turma_aulas_agendadas 
         WHERE turma_id = ? AND data_aula > ?",
        [$turmaId, $turma['data_fim']]
    );

    echo json_encode([
        'success' => true,
        'turma' => [
            'id' => $turma['id'],
            'nome' => $turma['nome'],
            'data_inicio' => $turma['data_inicio'],
            'data_fim' => $turma['data_fim'],
            'status' => $turma['status']
        ],
        'limite_calendario' => [
            'data_inicio_permitida' => $turma['data_inicio'],
            'data_fim_permitida' => $turma['data_fim'],
            'observacao' => 'O calendário está limitado ao período da turma (data_inicio até data_fim)'
        ],
        'agendamentos_apos_limite' => [
            'existem' => count($agendamentosAposDataFim) > 0,
            'total' => count($agendamentosAposDataFim),
            'agendamentos' => $agendamentosAposDataFim,
            'ultima_data' => $ultimoAgendamento['ultima_data'] ?? null
        ],
        'analise' => [
            'problema_detectado' => count($agendamentosAposDataFim) > 0,
            'sugestao' => count($agendamentosAposDataFim) > 0 
                ? 'Existem agendamentos após a data_fim da turma. Considere estender o período da turma ou mover os agendamentos.'
                : 'O limite do calendário está correto. Apenas dias até ' . $turma['data_fim'] . ' estão disponíveis.'
        ]
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro em verificar-limite-data-turma.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao verificar limite',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


