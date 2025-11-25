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

// Verificar autenticação (aceitar admin, secretaria e instrutor)
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Usuário não autenticado'
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// FASE 1 - PRESENCA TEORICA - Ajustar permissões para incluir aluno
// Arquivo: admin/api/turma-frequencia.php (linha ~38)
require_once __DIR__ . '/../../includes/auth.php';
$currentUser = getCurrentUser();
$isAdmin = ($currentUser['tipo'] ?? '') === 'admin';
$isSecretaria = ($currentUser['tipo'] ?? '') === 'secretaria';
$isInstrutor = ($currentUser['tipo'] ?? '') === 'instrutor';
$isAluno = ($currentUser['tipo'] ?? '') === 'aluno';

if (!$isAdmin && !$isSecretaria && !$isInstrutor && !$isAluno) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Permissão negada - Apenas administradores, secretaria, instrutores e alunos podem acessar frequências'
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
 * FASE 1 - PRESENCA TEORICA - Adicionar validação de segurança para aluno
 */
function handleGetRequest($db) {
    global $isAluno, $currentUser;
    
    // FASE 1 - PRESENCA TEORICA - Validação de segurança para aluno
    if ($isAluno) {
        $currentAlunoId = getCurrentAlunoId();
        if (!$currentAlunoId) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Aluno não encontrado ou não autenticado'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        // Aluno só pode ver sua própria frequência
        if (isset($_GET['aluno_id']) && (int)$_GET['aluno_id'] !== $currentAlunoId) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Permissão negada - Você só pode ver sua própria frequência'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        // Se não especificou aluno_id, usar o ID do aluno logado
        if (!isset($_GET['aluno_id']) && isset($_GET['turma_id'])) {
            $_GET['aluno_id'] = $currentAlunoId;
        }
    }
    
    if (isset($_GET['aluno_id']) && isset($_GET['turma_id'])) {
        // Calcular frequência de um aluno específico
        $frequencia = calcularFrequenciaAluno($db, $_GET['aluno_id'], $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $frequencia
        ], JSON_UNESCAPED_UNICODE);
        
    } elseif (isset($_GET['turma_id'])) {
        // Calcular frequência de todos os alunos da turma (apenas admin/secretaria/instrutor)
        if ($isAluno) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Permissão negada - Alunos não podem ver frequência de toda a turma'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        $frequencias = calcularFrequenciaTurma($db, $_GET['turma_id']);
        echo json_encode([
            'success' => true,
            'data' => $frequencias
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        // Listar frequências com filtros (apenas admin/secretaria/instrutor)
        if ($isAluno) {
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => 'Permissão negada - Alunos não podem listar todas as frequências'
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
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
    // CORRIGIDO: Usar turma_matriculas e turmas_teoricas (tabelas corretas)
    // Buscar dados do aluno
    $aluno = $db->fetch("
        SELECT a.*, tm.status as status_matricula
        FROM alunos a
        JOIN turma_matriculas tm ON a.id = tm.aluno_id
        WHERE a.id = ? AND tm.turma_id = ?
    ", [$alunoId, $turmaId]);
    
    if (!$aluno) {
        return [
            'success' => false,
            'message' => 'Aluno não encontrado ou não matriculado nesta turma'
        ];
    }
    
    // Buscar dados da turma (CORRIGIDO: usar turmas_teoricas)
    $turma = $db->fetch("
        SELECT * FROM turmas_teoricas WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        return [
            'success' => false,
            'message' => 'Turma não encontrada'
        ];
    }
    
    // Contar aulas programadas da turma (CORRIGIDO: usar turma_aulas_agendadas)
    $aulasProgramadas = $db->fetch("
        SELECT COUNT(*) as total
        FROM turma_aulas_agendadas 
        WHERE turma_id = ? AND status IN ('agendada', 'realizada')
    ", [$turmaId]);
    
    $totalAulas = $aulasProgramadas['total'];
    
    // Contar presenças do aluno (CORRIGIDO: considerar apenas aulas válidas)
    $presencas = $db->fetch("
        SELECT 
            COUNT(*) as total_registradas,
            COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as presentes,
            COUNT(CASE WHEN tp.presente = 0 THEN 1 END) as ausentes
        FROM turma_presencas tp
        INNER JOIN turma_aulas_agendadas taa ON tp.aula_id = taa.id
        WHERE tp.turma_id = ? 
        AND tp.aluno_id = ?
        AND taa.status IN ('agendada', 'realizada')
    ", [$turmaId, $alunoId]);
    
    $totalRegistradas = $presencas['total_registradas'];
    $aulasPresentes = $presencas['presentes'];
    $aulasAusentes = $presencas['ausentes'];
    
    // Calcular percentual de frequência (baseado em aulas válidas, não apenas registradas)
    $percentualFrequencia = 0;
    if ($totalAulas > 0) {
        $percentualFrequencia = round(($aulasPresentes / $totalAulas) * 100, 2);
    }
    
    // Determinar status de frequência
    // Frequência mínima padrão: 75% (se não houver campo na turma)
    $frequenciaMinima = isset($turma['frequencia_minima']) ? (float)$turma['frequencia_minima'] : 75.0;
    
    $statusFrequencia = 'PENDENTE';
    if ($totalAulas > 0) {
        if ($percentualFrequencia >= $frequenciaMinima) {
            $statusFrequencia = 'APROVADO';
        } else {
            $statusFrequencia = 'REPROVADO';
        }
    }
    
    // Buscar histórico de presenças (CORRIGIDO: usar turma_aulas_agendadas e aula_id)
    $historicoPresencas = $db->fetchAll("
        SELECT 
            tp.presente,
            tp.justificativa as observacao,
            tp.registrado_em,
            taa.nome_aula,
            taa.data_aula,
            taa.ordem_global as ordem
        FROM turma_presencas tp
        JOIN turma_aulas_agendadas taa ON tp.aula_id = taa.id
        WHERE tp.turma_id = ? AND tp.aluno_id = ?
        ORDER BY taa.ordem_global ASC
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
            'frequencia_minima' => isset($turma['frequencia_minima']) ? $turma['frequencia_minima'] : 75.0
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
    // CORRIGIDO: Usar turmas_teoricas e turma_matriculas (tabelas corretas)
    // Buscar dados da turma
    $turma = $db->fetch("
        SELECT * FROM turmas_teoricas WHERE id = ?
    ", [$turmaId]);
    
    if (!$turma) {
        return [
            'success' => false,
            'message' => 'Turma não encontrada'
        ];
    }
    
    // Contar aulas programadas da turma (CORRIGIDO: usar turma_aulas_agendadas)
    $aulasProgramadas = $db->fetch("
        SELECT COUNT(*) as total
        FROM turma_aulas_agendadas 
        WHERE turma_id = ? AND status IN ('agendada', 'realizada')
    ", [$turmaId]);
    
    $totalAulas = $aulasProgramadas['total'];
    
    // Buscar todos os alunos matriculados (CORRIGIDO: usar turma_matriculas)
    $alunos = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.cpf,
            tm.status as status_matricula,
            tm.data_matricula
        FROM alunos a
        JOIN turma_matriculas tm ON a.id = tm.aluno_id
        WHERE tm.turma_id = ? AND tm.status IN ('matriculado', 'cursando', 'concluido')
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
    
    // Frequência mínima padrão: 75% (se não houver campo na turma)
    $frequenciaMinima = isset($turma['frequencia_minima']) ? (float)$turma['frequencia_minima'] : 75.0;
    
    foreach ($alunos as $aluno) {
        // Contar presenças do aluno (CORRIGIDO: considerar apenas aulas válidas)
        $presencas = $db->fetch("
            SELECT 
                COUNT(*) as total_registradas,
                COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as presentes,
                COUNT(CASE WHEN tp.presente = 0 THEN 1 END) as ausentes
            FROM turma_presencas tp
            INNER JOIN turma_aulas_agendadas taa ON tp.aula_id = taa.id
            WHERE tp.turma_id = ? 
            AND tp.aluno_id = ?
            AND taa.status IN ('agendada', 'realizada')
        ", [$turmaId, $aluno['id']]);
        
        $totalRegistradas = $presencas['total_registradas'];
        $aulasPresentes = $presencas['presentes'];
        $aulasAusentes = $presencas['ausentes'];
        
        // Calcular percentual de frequência (baseado em aulas válidas, não apenas registradas)
        $percentualFrequencia = 0;
        if ($totalAulas > 0) {
            $percentualFrequencia = round(($aulasPresentes / $totalAulas) * 100, 2);
            $somaFrequencias += $percentualFrequencia;
            $alunosComFrequencia++;
        }
        
        // Determinar status de frequência
        $statusFrequencia = 'PENDENTE';
        if ($totalAulas > 0) {
            if ($percentualFrequencia >= $frequenciaMinima) {
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
    
    // Frequência mínima padrão: 75% (se não houver campo na turma)
    $frequenciaMinima = isset($turma['frequencia_minima']) ? (float)$turma['frequencia_minima'] : 75.0;
    
    return [
        'turma' => [
            'id' => $turma['id'],
            'nome' => $turma['nome'],
            'frequencia_minima' => $frequenciaMinima
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
    // CORRIGIDO: Usar turmas_teoricas, turma_matriculas, turma_aulas_agendadas (tabelas corretas)
    $sql = "
        SELECT 
            tt.id as turma_id,
            tt.nome as turma_nome,
            COALESCE(tt.frequencia_minima, 75.0) as frequencia_minima,
            COUNT(DISTINCT tm.aluno_id) as total_alunos,
            COUNT(DISTINCT taa.id) as total_aulas,
            COUNT(tp.id) as total_presencas_registradas,
            COUNT(CASE WHEN tp.presente = 1 THEN 1 END) as total_presentes,
            ROUND(
                CASE 
                    WHEN COUNT(tp.id) > 0 THEN 
                        (COUNT(CASE WHEN tp.presente = 1 THEN 1 END) / COUNT(tp.id)) * 100
                    ELSE 0 
                END, 2
            ) as frequencia_media
        FROM turmas_teoricas tt
        LEFT JOIN turma_matriculas tm ON tt.id = tm.turma_id
        LEFT JOIN turma_aulas_agendadas taa ON tt.id = taa.turma_id
        LEFT JOIN turma_presencas tp ON tt.id = tp.turma_id
        GROUP BY tt.id, tt.nome, tt.frequencia_minima
        ORDER BY tt.criado_em DESC
        LIMIT 50
    ";
    
    return $db->fetchAll($sql);
}

/**
 * Calcular frequência em tempo real (para uso em chamadas)
 */
function calcularFrequenciaTempoReal($db, $turmaId, $aulaId) {
    // CORRIGIDO: Usar aula_id e turma_matriculas (tabelas/campos corretos)
    // Buscar presenças da aula atual
    $presencasAula = $db->fetchAll("
        SELECT 
            tp.aluno_id,
            tp.presente,
            a.nome as aluno_nome
        FROM turma_presencas tp
        JOIN alunos a ON tp.aluno_id = a.id
        WHERE tp.turma_id = ? AND tp.aula_id = ?
        ORDER BY a.nome ASC
    ", [$turmaId, $aulaId]);
    
    // Buscar todos os alunos matriculados na turma (CORRIGIDO: usar turma_matriculas)
    $alunosTurma = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.cpf
        FROM alunos a
        JOIN turma_matriculas tm ON a.id = tm.aluno_id
        WHERE tm.turma_id = ? AND tm.status IN ('matriculado', 'cursando', 'concluido')
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
