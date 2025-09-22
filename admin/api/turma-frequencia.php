<?php
/**
 * API de Cálculo de Frequência
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.2: API de Presença
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn() || !hasPermission('admin')) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

$db = Database::getInstance();

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        handleGetRequest($db);
    } else {
        http_response_code(405);
        echo json_encode([
            'success' => false,
            'message' => 'Método não permitido'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Manipular requisições GET
 */
function handleGetRequest($db) {
    if (isset($_GET['aluno_id']) && isset($_GET['turma_id'])) {
        // Calcular frequência de um aluno específico
        $frequencia = calcularFrequenciaAluno($db, $_GET['aluno_id'], $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $frequencia
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif (isset($_GET['turma_id'])) {
        // Calcular frequência de todos os alunos da turma
        $frequencias = calcularFrequenciaTurma($db, $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $frequencias
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Listar frequências com filtros
        $frequencias = listarFrequencias($db);
        echo json_encode([
            'success' => true,
            'data' => $frequencias
        ], JSON_UNESCAPED_UNICODE);
    }
}

/**
 * Calcular frequência de um aluno específico
 */
function calcularFrequenciaAluno($db, $alunoId, $turmaId) {
    // Buscar dados do aluno
    $aluno = $db->fetch("
        SELECT a.*, ta.status as status_matricula
        FROM alunos a
        JOIN turma_alunos ta ON a.id = ta.aluno_id
        WHERE a.id = ? AND ta.turma_id = ?
    ", [$alunoId, $turmaId]);
    
    if (!$aluno) {
        return [
            'success' => false,
            'message' => 'Aluno não encontrado ou não matriculado nesta turma'
        ];
    }
    
    // Buscar dados da turma
    $turma = $db->fetch("
        SELECT * FROM turmas WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        return [
            'success' => false,
            'message' => 'Turma não encontrada'
        ];
    }
    
    // Contar aulas programadas da turma
    $aulasProgramadas = $db->fetch("
        SELECT COUNT(*) as total
        FROM turma_aulas 
        WHERE turma_id = ? AND status IN ('agendada', 'concluida')
    ", [$turmaId]);
    
    $totalAulas = $aulasProgramadas['total'];
    
    // Contar presenças do aluno
    $presencas = $db->fetch("
        SELECT 
            COUNT(*) as total_registradas,
            COUNT(CASE WHEN presente = 1 THEN 1 END) as presentes,
            COUNT(CASE WHEN presente = 0 THEN 1 END) as ausentes
        FROM turma_presencas 
        WHERE turma_id = ? AND aluno_id = ?
    ", [$turmaId, $alunoId]);
    
    $totalRegistradas = $presencas['total_registradas'];
    $aulasPresentes = $presencas['presentes'];
    $aulasAusentes = $presencas['ausentes'];
    
    // Calcular percentual de frequência
    $percentualFrequencia = 0;
    if ($totalRegistradas > 0) {
        $percentualFrequencia = round(($aulasPresentes / $totalRegistradas) * 100, 2);
    }
    
    // Determinar status de frequência
    $statusFrequencia = 'PENDENTE';
    if ($totalRegistradas > 0) {
        if ($percentualFrequencia >= $turma['frequencia_minima']) {
            $statusFrequencia = 'APROVADO';
        } else {
            $statusFrequencia = 'REPROVADO';
        }
    }
    
    // Buscar histórico de presenças
    $historicoPresencas = $db->fetchAll("
        SELECT 
            tp.presente,
            tp.observacao,
            tp.registrado_em,
            ta.nome_aula,
            ta.data_aula,
            ta.ordem
        FROM turma_presencas tp
        JOIN turma_aulas ta ON tp.turma_aula_id = ta.id
        WHERE tp.turma_id = ? AND tp.aluno_id = ?
        ORDER BY ta.ordem ASC
    ", [$turmaId, $alunoId]);
    
    return [
        'aluno' => [
            'id' => $aluno['id'],
            'nome' => $aluno['nome'],
            'cpf' => $aluno['cpf'],
            'status_matricula' => $aluno['status_matricula']
        ],
        'turma' => [
            'id' => $turma['id'],
            'nome' => $turma['nome'],
            'frequencia_minima' => $turma['frequencia_minima']
        ],
        'estatisticas' => [
            'total_aulas_programadas' => $totalAulas,
            'total_aulas_registradas' => $totalRegistradas,
            'aulas_presentes' => $aulasPresentes,
            'aulas_ausentes' => $aulasAusentes,
            'percentual_frequencia' => $percentualFrequencia,
            'status_frequencia' => $statusFrequencia
        ],
        'historico_presencas' => $historicoPresencas,
        'calculado_em' => date('Y-m-d H:i:s')
    ];
}

/**
 * Calcular frequência de todos os alunos da turma
 */
function calcularFrequenciaTurma($db, $turmaId) {
    // Buscar dados da turma
    $turma = $db->fetch("
        SELECT * FROM turmas WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        return [
            'success' => false,
            'message' => 'Turma não encontrada'
        ];
    }
    
    // Contar aulas programadas da turma
    $aulasProgramadas = $db->fetch("
        SELECT COUNT(*) as total
        FROM turma_aulas 
        WHERE turma_id = ? AND status IN ('agendada', 'concluida')
    ", [$turmaId]);
    
    $totalAulas = $aulasProgramadas['total'];
    
    // Buscar todos os alunos matriculados
    $alunos = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.cpf,
            ta.status as status_matricula,
            ta.data_matricula
        FROM alunos a
        JOIN turma_alunos ta ON a.id = ta.aluno_id
        WHERE ta.turma_id = ? AND ta.status IN ('matriculado', 'ativo')
        ORDER BY a.nome ASC
    ", [$turmaId]);
    
    $frequencias = [];
    $estatisticasGerais = [
        'total_alunos' => count($alunos),
        'total_aulas_programadas' => $totalAulas,
        'aprovados_frequencia' => 0,
        'reprovados_frequencia' => 0,
        'pendentes' => 0,
        'frequencia_media' => 0
    ];
    
    $somaFrequencias = 0;
    $alunosComFrequencia = 0;
    
    foreach ($alunos as $aluno) {
        // Contar presenças do aluno
        $presencas = $db->fetch("
            SELECT 
                COUNT(*) as total_registradas,
                COUNT(CASE WHEN presente = 1 THEN 1 END) as presentes,
                COUNT(CASE WHEN presente = 0 THEN 1 END) as ausentes
            FROM turma_presencas 
            WHERE turma_id = ? AND aluno_id = ?
        ", [$turmaId, $aluno['id']]);
        
        $totalRegistradas = $presencas['total_registradas'];
        $aulasPresentes = $presencas['presentes'];
        $aulasAusentes = $presencas['ausentes'];
        
        // Calcular percentual de frequência
        $percentualFrequencia = 0;
        if ($totalRegistradas > 0) {
            $percentualFrequencia = round(($aulasPresentes / $totalRegistradas) * 100, 2);
            $somaFrequencias += $percentualFrequencia;
            $alunosComFrequencia++;
        }
        
        // Determinar status de frequência
        $statusFrequencia = 'PENDENTE';
        if ($totalRegistradas > 0) {
            if ($percentualFrequencia >= $turma['frequencia_minima']) {
                $statusFrequencia = 'APROVADO';
                $estatisticasGerais['aprovados_frequencia']++;
            } else {
                $statusFrequencia = 'REPROVADO';
                $estatisticasGerais['reprovados_frequencia']++;
            }
        } else {
            $estatisticasGerais['pendentes']++;
        }
        
        $frequencias[] = [
            'aluno' => $aluno,
            'estatisticas' => [
                'total_aulas_registradas' => $totalRegistradas,
                'aulas_presentes' => $aulasPresentes,
                'aulas_ausentes' => $aulasAusentes,
                'percentual_frequencia' => $percentualFrequencia,
                'status_frequencia' => $statusFrequencia
            ]
        ];
    }
    
    // Calcular frequência média
    if ($alunosComFrequencia > 0) {
        $estatisticasGerais['frequencia_media'] = round($somaFrequencias / $alunosComFrequencia, 2);
    }
    
    return [
        'turma' => [
            'id' => $turma['id'],
            'nome' => $turma['nome'],
            'frequencia_minima' => $turma['frequencia_minima']
        ],
        'estatisticas_gerais' => $estatisticasGerais,
        'frequencias_alunos' => $frequencias,
        'calculado_em' => date('Y-m-d H:i:s')
    ];
}

/**
 * Listar frequências com filtros
 */
function listarFrequencias($db) {
    $sql = "
        SELECT 
            t.id as turma_id,
            t.nome as turma_nome,
            t.frequencia_minima,
            COUNT(DISTINCT ta_aluno.aluno_id) as total_alunos,
            COUNT(DISTINCT ta_aula.id) as total_aulas,
            COUNT(tp.id) as total_presencas_registradas,
            COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as total_presentes,
            ROUND(
                CASE 
                    WHEN COUNT(tp.id) > 0 THEN 
                        (COUNT(CASE WHEN tp.presente = 1 THEN 1 END) / COUNT(tp.id)) * 100
                    ELSE 0 
                END, 2
            ) as frequencia_media
        FROM turmas t
        LEFT JOIN turma_alunos ta_aluno ON t.id = ta_aluno.turma_id
        LEFT JOIN turma_aulas ta_aula ON t.id = ta_aula.turma_id
        LEFT JOIN turma_presencas tp ON t.id = tp.turma_id
        GROUP BY t.id, t.nome, t.frequencia_minima
        ORDER BY t.created_at DESC
        LIMIT 50
    ";
    
    return $db->fetchAll($sql);
}

/**
 * Calcular frequência em tempo real (para uso em chamadas)
 */
function calcularFrequenciaTempoReal($db, $turmaId, $aulaId) {
    // Buscar presenças da aula atual
    $presencasAula = $db->fetchAll("
        SELECT 
            tp.aluno_id,
            tp.presente,
            a.nome as aluno_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        WHERE tp.turma_id = ? AND tp.turma_aula_id = ?
        ORDER BY a.nome ASC
    ", [$turmaId, $aulaId]);
    
    // Buscar todos os alunos matriculados na turma
    $alunosTurma = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.cpf
        FROM alunos a
        JOIN turma_alunos ta ON a.id = ta.aluno_id
        WHERE ta.turma_id = ? AND ta.status IN ('matriculado', 'ativo')
        ORDER BY a.nome ASC
    ", [$turmaId]);
    
    $resultado = [];
    
    foreach ($alunosTurma as $aluno) {
        $presencaAula = null;
        foreach ($presencasAula as $presenca) {
            if ($presenca['aluno_id'] == $aluno['id']) {
                $presencaAula = $presenca;
                break;
            }
        }
        
        // Calcular frequência geral do aluno
        $frequenciaGeral = calcularFrequenciaAluno($db, $aluno['id'], $turmaId);
        
        $resultado[] = [
            'aluno' => $aluno,
            'presenca_aula_atual' => $presencaAula ? [
                'presente' => $presencaAula['presente'],
                'registrada' => true
            ] : [
                'presente' => null,
                'registrada' => false
            ],
            'frequencia_geral' => $frequenciaGeral['estatisticas'] ?? null
        ];
    }
    
    return $resultado;
}
?>
