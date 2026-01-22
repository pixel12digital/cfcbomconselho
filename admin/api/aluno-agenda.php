<?php
/**
 * API - Agenda consolidada do aluno (práticas + teóricas)
 *
 * Retorna resumo de progresso das aulas práticas, próxima aula
 * e linha do tempo unificada apenas para leitura.
 *
 * GET: ?aluno_id=123
 */

header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../includes/config.php';
    require_once __DIR__ . '/../../includes/database.php';
    require_once __DIR__ . '/../../includes/auth.php';
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro de configuração: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Método não permitido'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$alunoId = isset($_GET['aluno_id']) ? (int) $_GET['aluno_id'] : 0;

if (!$alunoId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetro aluno_id é obrigatório'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $db = db();

    $aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'message' => 'Aluno não encontrado'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // --- Aulas práticas ----------------------------------------------------
    $praticas = $db->fetchAll("
        SELECT 
            a.*,
            COALESCE(u.nome, i.nome) AS instrutor_nome,
            v.placa,
            v.modelo,
            v.marca
        FROM aulas a
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.aluno_id = ?
          AND a.status != 'cancelada'
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
    ", [$alunoId]);

    $totalPraticas = 0;
    $totalPraticasConcluidas = 0;
    $proximaPratica = null;
    $agora = new DateTimeImmutable();

    foreach ($praticas as $aula) {
        if (strtolower($aula['tipo_aula'] ?? '') === 'pratica') {
            $totalPraticas++;
            if (in_array(strtolower($aula['status']), ['concluida', 'realizada'])) {
                $totalPraticasConcluidas++;
            }

            if (!$proximaPratica) {
                $dataHora = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $aula['data_aula'] . ' ' . $aula['hora_inicio']);
                if ($dataHora && $dataHora >= $agora) {
                    $proximaPratica = $aula;
                }
            }
        }
    }

    $progressoPratica = $totalPraticas > 0
        ? round(($totalPraticasConcluidas / $totalPraticas) * 100, 1)
        : 0.0;

    // --- Aulas teóricas ----------------------------------------------------
    $teoricas = [];
    try {
        // Algumas instalações utilizam turma_matriculas, outras turma_alunos – cobrir ambos cenários.
        $tabelasTurmaAluno = [];
        $tabelasDisponiveis = $db->fetchAll("SHOW TABLES LIKE 'turma%alunos%'");
        foreach ($tabelasDisponiveis as $tabela) {
            $nomeTabela = array_values($tabela)[0] ?? null;
            if ($nomeTabela) {
                $tabelasTurmaAluno[] = $nomeTabela;
            }
        }

        $teoricas = [];

        foreach ($tabelasTurmaAluno as $tabelaAlunos) {
            $query = "
                SELECT 
                    taa.*,
                    tt.nome AS turma_nome,
                    tt.id AS turma_id,
                    '{$tabelaAlunos}' AS origem_relacionamento
                FROM {$tabelaAlunos} ta
                INNER JOIN turma_aulas_agendadas taa ON ta.turma_id = taa.turma_id
                LEFT JOIN turmas_teoricas tt ON tt.id = taa.turma_id
                WHERE ta.aluno_id = ?
                  AND taa.status != 'cancelada'
            ";
            $teoricas = array_merge($teoricas, $db->fetchAll($query, [$alunoId]));
        }
    } catch (Exception $e) {
        // Se a estrutura não existir ainda, apenas manter teoricas vazio
        if (function_exists('error_log')) {
            error_log('[API aluno-agenda] Falha ao buscar aulas teóricas: ' . $e->getMessage());
        }
        $teoricas = [];
    }

    // --- Montar linha do tempo unificada ----------------------------------
    $timeline = [];

    foreach ($praticas as $aula) {
        $dataHora = $aula['data_aula'] . ' ' . ($aula['hora_inicio'] ?? '00:00:00');
        $timeline[] = [
            'tipo' => 'pratica',
            'titulo' => 'Aula Prática',
            'descricao' => trim(($aula['instrutor_nome'] ?? '') . ' • ' . ($aula['modelo'] ?? '')),
            'status' => strtolower($aula['status'] ?? 'agendada'),
            'data' => $aula['data_aula'],
            'hora_inicio' => $aula['hora_inicio'],
            'hora_fim' => $aula['hora_fim'],
            'data_hora' => $dataHora,
            'id' => (int) ($aula['id'] ?? 0),
            'detalhes' => [
                'instrutor' => $aula['instrutor_nome'] ?? null,
                'veiculo' => trim(($aula['marca'] ?? '') . ' ' . ($aula['modelo'] ?? '') . ' ' . ($aula['placa'] ?? '')),
                'observacoes' => $aula['observacoes'] ?? null
            ]
        ];
    }

    foreach ($teoricas as $aula) {
        $dataHora = $aula['data_aula'] . ' ' . ($aula['hora_inicio'] ?? '00:00:00');
        $timeline[] = [
            'tipo' => 'teorica',
            'titulo' => $aula['nome_aula'] ?? 'Aula Teórica',
            'descricao' => trim(($aula['disciplina'] ?? '') . ' • ' . ($aula['turma_nome'] ?? 'Turma')),
            'status' => strtolower($aula['status'] ?? 'agendada'),
            'data' => $aula['data_aula'],
            'hora_inicio' => $aula['hora_inicio'],
            'hora_fim' => $aula['hora_fim'],
            'data_hora' => $dataHora,
            'id' => (int) ($aula['id'] ?? 0),
            'detalhes' => [
                'disciplina' => $aula['disciplina'] ?? null,
                'turma' => $aula['turma_nome'] ?? null,
                'instrutor' => $aula['instrutor_id'] ?? null,
                'sala' => $aula['sala_id'] ?? null
            ]
        ];
    }

    usort($timeline, function ($a, $b) {
        return strtotime($a['data_hora']) <=> strtotime($b['data_hora']);
    });

    $resumo = [
        'praticas' => [
            'total' => $totalPraticas,
            'concluidas' => $totalPraticasConcluidas,
            'progresso_percentual' => $progressoPratica,
            'proxima' => $proximaPratica ? [
                'id' => (int) ($proximaPratica['id'] ?? 0),
                'data' => $proximaPratica['data_aula'],
                'hora_inicio' => $proximaPratica['hora_inicio'],
                'instrutor' => $proximaPratica['instrutor_nome'] ?? null,
                'veiculo' => trim(($proximaPratica['marca'] ?? '') . ' ' . ($proximaPratica['modelo'] ?? '') . ' ' . ($proximaPratica['placa'] ?? ''))
            ] : null
        ],
        'teoricas' => [
            'total' => count($teoricas)
        ]
    ];

    echo json_encode([
        'success' => true,
        'aluno' => [
            'id' => $aluno['id'],
            'nome' => $aluno['nome'],
            'categoria_cnh' => $aluno['categoria_cnh'] ?? null
        ],
        'resumo' => $resumo,
        'timeline' => $timeline
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao carregar agenda do aluno',
        'error' => DEBUG_MODE ? $e->getMessage() : null
    ], JSON_UNESCAPED_UNICODE);
}

