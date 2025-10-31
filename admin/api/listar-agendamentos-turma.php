<?php
/**
 * API para listar agendamentos de uma turma/disciplina
 * GET: ?turma_id=9&disciplina=legislacao_transito
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';

    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $disciplina = $_GET['disciplina'] ?? '';

    if (!$turmaId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'turma_id é obrigatório']);
        exit;
    }

    $db = db();
    
    // Verificar se a tabela existe primeiro
    try {
        $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turma_aulas_agendadas'");
        if (!$tabelaExiste) {
            echo json_encode([
                'success' => true,
                'turma_id' => $turmaId,
                'disciplina' => $disciplina,
                'total' => 0,
                'agendamentos' => [],
                'message' => 'Tabela turma_aulas_agendadas não existe ainda'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            exit;
        }
    } catch (Exception $e) {
        // Continuar mesmo se a verificação falhar
    }
    
    // Buscar agendamentos com JOINs seguros - sem depender de disciplinas_configuracao
    if ($disciplina) {
        // Buscar de uma disciplina específica
        $agendamentos = $db->fetchAll(
            "SELECT 
                taa.*,
                taa.disciplina as nome_disciplina,
                i.id as instrutor_id,
                COALESCE(u.nome, i.nome, 'Instrutor não encontrado') as instrutor_nome,
                COALESCE(s.nome, 'Não informada') as sala_nome,
                s.id as sala_id
             FROM turma_aulas_agendadas taa
             LEFT JOIN instrutores i ON taa.instrutor_id = i.id
             LEFT JOIN usuarios u ON i.usuario_id = u.id
             LEFT JOIN salas s ON taa.sala_id = s.id
             WHERE taa.turma_id = ? AND taa.disciplina = ? AND taa.status != 'cancelada'
             ORDER BY taa.ordem_disciplina ASC, taa.data_aula ASC, taa.hora_inicio ASC",
            [$turmaId, $disciplina]
        );
    } else {
        // Buscar todos da turma
        $agendamentos = $db->fetchAll(
            "SELECT 
                taa.*,
                taa.disciplina as nome_disciplina,
                i.id as instrutor_id,
                COALESCE(u.nome, i.nome, 'Instrutor não encontrado') as instrutor_nome,
                COALESCE(s.nome, 'Não informada') as sala_nome,
                s.id as sala_id
             FROM turma_aulas_agendadas taa
             LEFT JOIN instrutores i ON taa.instrutor_id = i.id
             LEFT JOIN usuarios u ON i.usuario_id = u.id
             LEFT JOIN salas s ON taa.sala_id = s.id
             WHERE taa.turma_id = ? AND taa.status != 'cancelada'
             ORDER BY taa.ordem_disciplina ASC, taa.data_aula ASC, taa.hora_inicio ASC",
            [$turmaId]
        );
    }

    echo json_encode([
        'success' => true,
        'turma_id' => $turmaId,
        'disciplina' => $disciplina,
        'total' => count($agendamentos),
        'agendamentos' => $agendamentos
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro em listar-agendamentos-turma.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar agendamentos',
        'error' => $e->getMessage(),
        'trace' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getTraceAsString() : null
    ], JSON_UNESCAPED_UNICODE);
}

