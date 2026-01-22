<?php
/**
 * API - Disponibilidade de horários para agendamento de aula prática
 *
 * GET /admin/api/disponibilidade.php?aluno_id=10&categoria=B&intervalo=unica
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
$categoriaParam = isset($_GET['categoria']) ? trim($_GET['categoria']) : null;
$intervalo = $_GET['intervalo'] ?? 'unica';
$posicaoIntervalo = $_GET['posicao'] ?? 'depois';
$diasJanela = isset($_GET['dias']) ? max(1, min((int) $_GET['dias'], 21)) : 14;
$limiteSlots = isset($_GET['limite']) ? max(1, min((int) $_GET['limite'], 60)) : 30;

if (!$alunoId) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Parâmetro aluno_id é obrigatório'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$intervalo = normalizarTipoAgendamento($intervalo);
$posicaoIntervalo = in_array($posicaoIntervalo, ['antes', 'depois'], true) ? $posicaoIntervalo : 'depois';

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

    $categoriaUsada = $categoriaParam ?: ($aluno['categoria_cnh'] ?? null);
    if (!$categoriaUsada) {
        echo json_encode([
            'success' => false,
            'message' => 'Categoria do aluno não informada.'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $instrutores = carregarInstrutoresElegiveis($db, $categoriaUsada);
    $veiculos = carregarVeiculosElegiveis($db, $categoriaUsada);

    if (empty($instrutores) || empty($veiculos)) {
        echo json_encode([
            'success' => true,
            'slots' => [],
            'message' => 'Nenhum instrutor ou veículo disponível para a categoria informada.',
            'meta' => [
                'categoria' => $categoriaUsada,
                'dias_analisados' => 0
            ]
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $horariosBase = [
        '08:00', '08:50', '09:40', '10:30', '11:20', '12:10',
        '14:00', '14:50', '15:40', '16:30', '17:20', '18:10',
        '19:00', '19:50', '20:40'
    ];

    $slots = [];
    $hoje = new DateTimeImmutable('today');

    for ($dia = 0; $dia < $diasJanela && count($slots) < $limiteSlots; $dia++) {
        $dataAtual = $hoje->modify("+{$dia} day");
        $dataStr = $dataAtual->format('Y-m-d');

        foreach ($horariosBase as $horaInicio) {
            if (count($slots) >= $limiteSlots) {
                break 2;
            }

            $blocos = calcularHorariosAulas($horaInicio . ':00', $intervalo, $posicaoIntervalo);
            if (vazio($blocos)) {
                continue;
            }

            foreach ($instrutores as $instrutor) {
                foreach ($veiculos as $veiculo) {
                    if (slotDisponivel($db, $alunoId, $instrutor['id'], $veiculo['id'], $dataStr, $blocos)) {
                        $slots[] = [
                            'data' => $dataStr,
                            'hora_inicio' => $horaInicio,
                            'hora_fim' => end($blocos)['hora_fim'],
                            'tipo_agendamento' => $intervalo,
                            'total_aulas' => count($blocos),
                            'instrutor' => [
                                'id' => (int) $instrutor['id'],
                                'nome' => $instrutor['nome']
                            ],
                            'veiculo' => [
                                'id' => (int) $veiculo['id'],
                                'modelo' => $veiculo['modelo'],
                                'placa' => $veiculo['placa']
                            ]
                        ];

                        continue 3; // Próximo horário base
                    }
                }
            }
        }
    }

    echo json_encode([
        'success' => true,
        'aluno' => [
            'id' => $aluno['id'],
            'nome' => $aluno['nome'],
            'categoria_cnh' => $aluno['categoria_cnh']
        ],
        'slots' => $slots,
        'meta' => [
            'categoria' => $categoriaUsada,
            'dias_analisados' => min($diasJanela, count($slots) ? $diasJanela : 0),
            'limite_slots' => $limiteSlots
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro ao calcular disponibilidade: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function normalizarTipoAgendamento($valor)
{
    $map = [
        'unica' => 'unica',
        'uma' => 'unica',
        '1' => 'unica',
        'duas' => 'duas',
        '2' => 'duas',
        'tres' => 'tres',
        '3' => 'tres'
    ];

    $v = strtolower((string) $valor);
    return $map[$v] ?? 'unica';
}

function carregarInstrutoresElegiveis($db, $categoria)
{
    $dados = $db->fetchAll("
        SELECT i.id,
               COALESCE(u.nome, i.nome) AS nome,
               i.categorias_json,
               i.categoria_habilitacao
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
    ");

    return array_values(array_filter($dados, function ($instrutor) use ($categoria) {
        $categorias = [];

        if (!empty($instrutor['categorias_json'])) {
            $categorias = json_decode($instrutor['categorias_json'], true);
        }

        if (empty($categorias) && !empty($instrutor['categoria_habilitacao'])) {
            $categorias = array_map('trim', explode(',', $instrutor['categoria_habilitacao']));
        }

        $categorias = array_map('strtoupper', (array) $categorias);
        return in_array(strtoupper($categoria), $categorias, true);
    }));
}

function carregarVeiculosElegiveis($db, $categoria)
{
    $dados = $db->fetchAll("
        SELECT id, modelo, placa, categoria
        FROM veiculos
        WHERE ativo = 1
    ");

    return array_values(array_filter($dados, function ($veiculo) use ($categoria) {
        return strtoupper($veiculo['categoria'] ?? '') === strtoupper($categoria);
    }));
}

function slotDisponivel($db, $alunoId, $instrutorId, $veiculoId, $data, array $blocos)
{
    foreach ($blocos as $bloco) {
        $inicio = $bloco['hora_inicio'];
        $fim = $bloco['hora_fim'];

        if (possuiConflito($db, 'instrutor_id', $instrutorId, $data, $inicio, $fim)) {
            return false;
        }

        if (possuiConflito($db, 'veiculo_id', $veiculoId, $data, $inicio, $fim)) {
            return false;
        }

        if (possuiConflito($db, 'aluno_id', $alunoId, $data, $inicio, $fim)) {
            return false;
        }
    }

    return true;
}

function possuiConflito($db, $campo, $id, $data, $inicio, $fim)
{
    $sql = "
        SELECT 1
        FROM aulas
        WHERE {$campo} = ?
          AND data_aula = ?
          AND status != 'cancelada'
          AND (
            (hora_inicio < ? AND hora_fim > ?)
            OR (hora_inicio >= ? AND hora_inicio < ?)
            OR (hora_fim > ? AND hora_fim <= ?)
          )
        LIMIT 1
    ";

    $conflito = $db->fetch($sql, [$id, $data, $fim, $inicio, $inicio, $fim, $inicio, $fim]);
    return (bool) $conflito;
}

function calcularHorariosAulas($horaInicio, $tipoAgendamento, $posicaoIntervalo = 'depois')
{
    $horarios = [];
    $inicioTimestamp = strtotime($horaInicio);

    switch ($tipoAgendamento) {
        case 'unica':
            $horarios[] = [
                'hora_inicio' => date('H:i:s', $inicioTimestamp),
                'hora_fim' => date('H:i:s', $inicioTimestamp + 50 * 60)
            ];
            break;

        case 'duas':
            $horarios[] = [
                'hora_inicio' => date('H:i:s', $inicioTimestamp),
                'hora_fim' => date('H:i:s', $inicioTimestamp + 50 * 60)
            ];
            $horarios[] = [
                'hora_inicio' => date('H:i:s', $inicioTimestamp + 50 * 60),
                'hora_fim' => date('H:i:s', $inicioTimestamp + 100 * 60)
            ];
            break;

        case 'tres':
            if ($posicaoIntervalo === 'antes') {
                $horarios[] = [
                    'hora_inicio' => date('H:i:s', $inicioTimestamp),
                    'hora_fim' => date('H:i:s', $inicioTimestamp + 50 * 60)
                ];
                $horarios[] = [
                    'hora_inicio' => date('H:i:s', $inicioTimestamp + 80 * 60),
                    'hora_fim' => date('H:i:s', $inicioTimestamp + 130 * 60)
                ];
                $horarios[] = [
                    'hora_inicio' => date('H:i:s', $inicioTimestamp + 130 * 60),
                    'hora_fim' => date('H:i:s', $inicioTimestamp + 180 * 60)
                ];
            } else {
                $horarios[] = [
                    'hora_inicio' => date('H:i:s', $inicioTimestamp),
                    'hora_fim' => date('H:i:s', $inicioTimestamp + 50 * 60)
                ];
                $horarios[] = [
                    'hora_inicio' => date('H:i:s', $inicioTimestamp + 50 * 60),
                    'hora_fim' => date('H:i:s', $inicioTimestamp + 100 * 60)
                ];
                $horarios[] = [
                    'hora_inicio' => date('H:i:s', $inicioTimestamp + 130 * 60),
                    'hora_fim' => date('H:i:s', $inicioTimestamp + 180 * 60)
                ];
            }
            break;
    }

    return $horarios;
}

function vazio($valor)
{
    return empty($valor) && $valor !== 0 && $valor !== '0';
}

