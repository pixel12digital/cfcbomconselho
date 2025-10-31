<?php
/**
 * API para obter estatísticas atualizadas de uma turma
 * GET: ?turma_id=9
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';

    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;

    if (!$turmaId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'turma_id é obrigatório']);
        exit;
    }

    $turmaManager = new TurmaTeoricaManager();
    $db = db();
    
    // Obter turma
    $resultadoTurma = $turmaManager->obterTurma($turmaId);
    if (!$resultadoTurma['sucesso']) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Turma não encontrada']);
        exit;
    }
    
    $turma = $resultadoTurma['dados'];
    
    // Obter disciplinas selecionadas
    $disciplinasSelecionadas = $turmaManager->obterDisciplinasSelecionadas($turmaId);
    
    $estatisticasDisciplinas = [];
    $totalAulasObrigatorias = 0;
    $totalAulasAgendadas = 0;
    $totalAulasRealizadas = 0;
    
    foreach ($disciplinasSelecionadas as $disciplina) {
        $disciplinaId = $disciplina['disciplina_id'];
        
        // Buscar aulas agendadas (excluindo canceladas)
        $aulasAgendadas = $db->fetch(
            "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ? AND status != 'cancelada'",
            [$turmaId, $disciplinaId]
        );
        
        // Buscar aulas realizadas
        $aulasRealizadas = $db->fetch(
            "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND disciplina = ? AND status = 'realizada'",
            [$turmaId, $disciplinaId]
        );
        
        $totalAgendadas = $aulasAgendadas['total'] ?? 0;
        $totalRealizadas = $aulasRealizadas['total'] ?? 0;
        $totalObrigatorias = $disciplina['carga_horaria_padrao'] ?? 0;
        $totalFaltantes = max(0, $totalObrigatorias - $totalAgendadas);
        
        $estatisticasDisciplinas[$disciplinaId] = [
            'agendadas' => $totalAgendadas,
            'realizadas' => $totalRealizadas,
            'faltantes' => $totalFaltantes,
            'obrigatorias' => $totalObrigatorias,
            'nome_disciplina' => $disciplina['nome_disciplina'] ?? $disciplina['nome_original'] ?? 'Disciplina',
            'percentual' => $totalObrigatorias > 0 ? round(($totalAgendadas / $totalObrigatorias) * 100, 1) : 0
        ];
        
        $totalAulasObrigatorias += $totalObrigatorias;
        $totalAulasAgendadas += $totalAgendadas;
        $totalAulasRealizadas += $totalRealizadas;
    }
    
    $percentualGeral = $totalAulasObrigatorias > 0 ? round(($totalAulasAgendadas / $totalAulasObrigatorias) * 100, 1) : 0;

    echo json_encode([
        'success' => true,
        'turma_id' => $turmaId,
        'estatisticas_gerais' => [
            'total_aulas_obrigatorias' => $totalAulasObrigatorias,
            'total_aulas_agendadas' => $totalAulasAgendadas,
            'total_aulas_realizadas' => $totalAulasRealizadas,
            'total_faltantes' => $totalAulasObrigatorias - $totalAulasAgendadas,
            'percentual_geral' => $percentualGeral
        ],
        'disciplinas' => $estatisticasDisciplinas
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    http_response_code(500);
    error_log("Erro em estatisticas-turma.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao buscar estatísticas',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

