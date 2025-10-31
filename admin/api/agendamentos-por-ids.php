<?php
/**
 * Retorna agendamentos (turma_aulas_agendadas) por IDs em JSON
 * GET ids=1,2,3
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';

    $idsParam = $_GET['ids'] ?? '';
    if (!$idsParam) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Parâmetro ids é obrigatório']);
        exit;
    }

    // Sanitizar IDs
    $ids = array_values(array_filter(array_map('intval', explode(',', $idsParam)), fn($v) => $v > 0));
    if (empty($ids)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Nenhum ID válido informado']);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $db = db();
    
    // Buscar agendamentos com JOINs seguros - sem depender de disciplinas_configuracao
    $rows = $db->fetchAll(
        "SELECT 
            taa.*,
            taa.disciplina as nome_disciplina,
            COALESCE(u.nome, i.nome, 'Instrutor não encontrado') as instrutor_nome,
            i.id as instrutor_id,
            COALESCE(s.nome, 'Não informada') as sala_nome,
            s.id as sala_id
         FROM turma_aulas_agendadas taa
         LEFT JOIN instrutores i ON taa.instrutor_id = i.id
         LEFT JOIN usuarios u ON i.usuario_id = u.id
         LEFT JOIN salas s ON taa.sala_id = s.id
         WHERE taa.id IN ($placeholders)
         ORDER BY taa.data_aula DESC, taa.hora_inicio DESC",
        $ids
    );

    echo json_encode([
        'success' => true, 
        'data' => $rows,
        'total' => count($rows)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Throwable $e) {
    http_response_code(500);
    error_log("Erro em agendamentos-por-ids.php: " . $e->getMessage() . " | Trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false, 
        'message' => 'Erro ao buscar agendamentos',
        'error' => $e->getMessage(),
        'trace' => defined('DEBUG_MODE') && DEBUG_MODE ? $e->getTraceAsString() : null
    ], JSON_UNESCAPED_UNICODE);
}


