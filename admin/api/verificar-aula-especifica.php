<?php
/**
 * API para verificar se uma aula específica foi salva no banco
 * GET: ?turma_id=9&disciplina=legislacao_transito&nome_aula=Aula 18
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';

    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $disciplina = $_GET['disciplina'] ?? '';
    $nomeAula = $_GET['nome_aula'] ?? 'Aula 18';

    if (!$turmaId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'turma_id é obrigatório']);
        exit;
    }

    $db = db();
    
    // Buscar especificamente pela aula
    $aula = $db->fetch(
        "SELECT 
            taa.*,
            COALESCE(u.nome, i.nome, 'Instrutor não encontrado') as instrutor_nome,
            COALESCE(s.nome, 'Não informada') as sala_nome,
            s.id as sala_id
         FROM turma_aulas_agendadas taa
         LEFT JOIN instrutores i ON taa.instrutor_id = i.id
         LEFT JOIN usuarios u ON i.usuario_id = u.id
         LEFT JOIN salas s ON taa.sala_id = s.id
         WHERE taa.turma_id = ? 
         AND taa.disciplina = ?
         AND taa.nome_aula LIKE ?
         ORDER BY taa.id DESC
         LIMIT 1",
        [$turmaId, $disciplina, "%{$nomeAula}%"]
    );
    
    // Também buscar todas as aulas da disciplina para ver quantas existem
    $todasAulas = $db->fetchAll(
        "SELECT 
            taa.id,
            taa.nome_aula,
            taa.data_aula,
            taa.hora_inicio,
            taa.hora_fim,
            taa.status,
            taa.ordem_disciplina
         FROM turma_aulas_agendadas taa
         WHERE taa.turma_id = ? 
         AND taa.disciplina = ?
         ORDER BY taa.ordem_disciplina ASC",
        [$turmaId, $disciplina]
    );

    echo json_encode([
        'success' => true,
        'turma_id' => $turmaId,
        'disciplina' => $disciplina,
        'nome_aula_buscado' => $nomeAula,
        'aula_encontrada' => $aula ? true : false,
        'aula' => $aula,
        'total_aulas_disciplina' => count($todasAulas),
        'todas_aulas' => $todasAulas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro em verificar-aula-especifica.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar aula',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}


