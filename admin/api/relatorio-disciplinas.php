<?php
/**
 * API para Relatório Detalhado de Disciplinas
 * Retorna aulas agendadas e histórico completo por disciplina
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Incluir arquivos necessários
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Debug: Verificar autenticação
error_log("DEBUG API: Verificando autenticação...");
error_log("DEBUG API: SESSION: " . json_encode($_SESSION ?? []));
error_log("DEBUG API: COOKIES: " . json_encode($_COOKIE ?? []));
error_log("DEBUG API: isLoggedIn(): " . (isLoggedIn() ? 'true' : 'false'));

// Verificar autenticação
if (!isLoggedIn()) {
    error_log("DEBUG API: Usuário não autenticado");
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

error_log("DEBUG API: Usuário autenticado com sucesso");

// Verificar método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Obter parâmetros
    $turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 0;
    $disciplinaId = $_GET['disciplina_id'] ?? '';
    $acao = $_GET['acao'] ?? '';

    if (!$turmaId) {
        throw new Exception('ID da turma é obrigatório');
    }

    switch ($acao) {
        case 'aulas_disciplina':
            if (empty($disciplinaId)) {
                throw new Exception('ID da disciplina é obrigatório');
            }
            $resultado = obterAulasDisciplina($turmaId, $disciplinaId);
            break;
            
        case 'resumo_disciplinas':
            $resultado = obterResumoDisciplinas($turmaId);
            break;
            
        case 'historico_completo':
            $resultado = obterHistoricoCompleto($turmaId);
            break;
            
        default:
            throw new Exception('Ação não especificada');
    }

    echo json_encode($resultado);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * Obter aulas agendadas de uma disciplina específica
 */
function obterAulasDisciplina($turmaId, $disciplinaId) {
    global $db;
    
    try {
        // Se disciplinaId é string, buscar o ID numérico correspondente
        $disciplinaIdNumerico = $disciplinaId;
        if (!is_numeric($disciplinaId)) {
            // Buscar disciplina por nome ou slug
            $disciplinaEncontrada = $db->fetch(
                "SELECT id FROM disciplinas WHERE nome = ? OR slug = ? OR codigo = ?",
                [$disciplinaId, $disciplinaId, $disciplinaId]
            );
            
            if (!$disciplinaEncontrada) {
                throw new Exception("Disciplina '{$disciplinaId}' não encontrada");
            }
            
            $disciplinaIdNumerico = $disciplinaEncontrada['id'];
        }
        
        // Buscar aulas agendadas da disciplina
        $aulas = $db->fetchAll(
            "SELECT 
                taa.*,
                d.nome as disciplina_nome,
                s.nome as sala_nome,
                i.nome as instrutor_nome,
                i.cpf as instrutor_cpf,
                i.telefone as instrutor_telefone,
                i.email as instrutor_email
            FROM turma_aulas_agendadas taa
            LEFT JOIN disciplinas d ON taa.disciplina = d.id
            LEFT JOIN salas s ON taa.sala_id = s.id
            LEFT JOIN instrutores i ON taa.instrutor_id = i.id
            WHERE taa.turma_id = ? AND taa.disciplina = ?
            ORDER BY taa.data_aula, taa.hora_inicio",
            [$turmaId, $disciplinaIdNumerico]
        );

        // Buscar informações da disciplina
        $disciplina = $db->fetch(
            "SELECT * FROM disciplinas WHERE id = ?",
            [$disciplinaIdNumerico]
        );

        // Calcular estatísticas
        $totalAulas = count($aulas);
        $aulasRealizadas = array_filter($aulas, function($aula) {
            return $aula['status'] === 'realizada';
        });
        $aulasAgendadas = array_filter($aulas, function($aula) {
            return $aula['status'] === 'agendada';
        });
        $aulasCanceladas = array_filter($aulas, function($aula) {
            return $aula['status'] === 'cancelada';
        });

        // Calcular carga horária
        $totalMinutos = array_sum(array_column($aulas, 'duracao_minutos'));
        $totalHoras = round($totalMinutos / 60, 1);

        // Formatar dados das aulas
        $aulasFormatadas = array_map(function($aula) {
            return [
                'id' => $aula['id'],
                'data_aula' => $aula['data_aula'],
                'data_formatada' => date('d/m/Y', strtotime($aula['data_aula'])),
                'dia_semana' => obterDiaSemana($aula['data_aula']),
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim'],
                'duracao_minutos' => $aula['duracao_minutos'],
                'duracao_horas' => round($aula['duracao_minutos'] / 60, 1),
                'status' => $aula['status'],
                'status_formatado' => ucfirst($aula['status']),
                'sala_nome' => $aula['sala_nome'] ?? 'Não especificada',
                'instrutor_nome' => $aula['instrutor_nome'] ?? 'Não especificado',
                'instrutor_cpf' => $aula['instrutor_cpf'] ?? '',
                'instrutor_telefone' => $aula['instrutor_telefone'] ?? '',
                'instrutor_email' => $aula['instrutor_email'] ?? '',
                'observacoes' => $aula['observacoes'] ?? '',
                'data_criacao' => $aula['data_criacao'],
                'data_atualizacao' => $aula['data_atualizacao']
            ];
        }, $aulas);

        return [
            'success' => true,
            'disciplina' => $disciplina,
            'aulas' => $aulasFormatadas,
            'estatisticas' => [
                'total_aulas' => $totalAulas,
                'aulas_realizadas' => count($aulasRealizadas),
                'aulas_agendadas' => count($aulasAgendadas),
                'aulas_canceladas' => count($aulasCanceladas),
                'total_horas' => $totalHoras,
                'carga_obrigatoria' => $disciplina['carga_horaria_padrao'] ?? 0,
                'horas_faltantes' => max(0, ($disciplina['carga_horaria_padrao'] ?? 0) - $totalHoras)
            ]
        ];

    } catch (Exception $e) {
        throw new Exception('Erro ao buscar aulas da disciplina: ' . $e->getMessage());
    }
}

/**
 * Obter resumo de todas as disciplinas da turma
 */
function obterResumoDisciplinas($turmaId) {
    global $db;
    
    try {
        // Buscar disciplinas da turma
        $disciplinas = $db->fetchAll(
            "SELECT DISTINCT 
                d.id,
                d.nome,
                d.carga_horaria_padrao,
                COUNT(taa.id) as total_aulas,
                SUM(CASE WHEN taa.status = 'realizada' THEN 1 ELSE 0 END) as aulas_realizadas,
                SUM(CASE WHEN taa.status = 'agendada' THEN 1 ELSE 0 END) as aulas_agendadas,
                SUM(CASE WHEN taa.status = 'cancelada' THEN 1 ELSE 0 END) as aulas_canceladas,
                SUM(taa.duracao_minutos) as total_minutos
            FROM disciplinas d
            LEFT JOIN turma_aulas_agendadas taa ON d.id = taa.disciplina AND taa.turma_id = ?
            WHERE d.id IN (
                SELECT DISTINCT disciplina FROM turma_aulas_agendadas WHERE turma_id = ?
            )
            GROUP BY d.id, d.nome, d.carga_horaria_padrao
            ORDER BY d.nome",
            [$turmaId, $turmaId]
        );

        // Formatar dados
        $disciplinasFormatadas = array_map(function($disciplina) {
            $totalHoras = round($disciplina['total_minutos'] / 60, 1);
            $cargaObrigatoria = $disciplina['carga_horaria_padrao'] ?? 0;
            $horasFaltantes = max(0, $cargaObrigatoria - $totalHoras);
            
            return [
                'id' => $disciplina['id'],
                'nome' => $disciplina['nome'],
                'carga_obrigatoria' => $cargaObrigatoria,
                'total_aulas' => (int)$disciplina['total_aulas'],
                'aulas_realizadas' => (int)$disciplina['aulas_realizadas'],
                'aulas_agendadas' => (int)$disciplina['aulas_agendadas'],
                'aulas_canceladas' => (int)$disciplina['aulas_canceladas'],
                'total_horas' => $totalHoras,
                'horas_faltantes' => $horasFaltantes,
                'percentual_concluido' => $cargaObrigatoria > 0 ? round(($totalHoras / $cargaObrigatoria) * 100, 1) : 0,
                'status' => obterStatusDisciplina($disciplina['aulas_realizadas'], $disciplina['total_aulas'], $cargaObrigatoria)
            ];
        }, $disciplinas);

        return [
            'success' => true,
            'disciplinas' => $disciplinasFormatadas,
            'total_disciplinas' => count($disciplinasFormatadas)
        ];

    } catch (Exception $e) {
        throw new Exception('Erro ao buscar resumo das disciplinas: ' . $e->getMessage());
    }
}

/**
 * Obter histórico completo da turma
 */
function obterHistoricoCompleto($turmaId) {
    global $db;
    
    try {
        // Buscar todas as aulas da turma
        $aulas = $db->fetchAll(
            "SELECT 
                taa.*,
                d.nome as disciplina_nome,
                s.nome as sala_nome,
                i.nome as instrutor_nome
            FROM turma_aulas_agendadas taa
            LEFT JOIN disciplinas d ON taa.disciplina = d.id
            LEFT JOIN salas s ON taa.sala_id = s.id
            LEFT JOIN instrutores i ON taa.instrutor_id = i.id
            WHERE taa.turma_id = ?
            ORDER BY taa.data_aula DESC, taa.hora_inicio DESC",
            [$turmaId]
        );

        // Agrupar por disciplina
        $historicoPorDisciplina = [];
        foreach ($aulas as $aula) {
            $disciplinaId = $aula['disciplina'];
            if (!isset($historicoPorDisciplina[$disciplinaId])) {
                $historicoPorDisciplina[$disciplinaId] = [
                    'disciplina_nome' => $aula['disciplina_nome'],
                    'aulas' => []
                ];
            }
            
            $historicoPorDisciplina[$disciplinaId]['aulas'][] = [
                'id' => $aula['id'],
                'data_aula' => $aula['data_aula'],
                'data_formatada' => date('d/m/Y', strtotime($aula['data_aula'])),
                'dia_semana' => obterDiaSemana($aula['data_aula']),
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim'],
                'duracao_horas' => round($aula['duracao_minutos'] / 60, 1),
                'status' => $aula['status'],
                'status_formatado' => ucfirst($aula['status']),
                'sala_nome' => $aula['sala_nome'] ?? 'Não especificada',
                'instrutor_nome' => $aula['instrutor_nome'] ?? 'Não especificado',
                'observacoes' => $aula['observacoes'] ?? ''
            ];
        }

        return [
            'success' => true,
            'historico' => $historicoPorDisciplina,
            'total_aulas' => count($aulas)
        ];

    } catch (Exception $e) {
        throw new Exception('Erro ao buscar histórico completo: ' . $e->getMessage());
    }
}

/**
 * Obter dia da semana em português
 */
function obterDiaSemana($data) {
    $dias = [
        'Sunday' => 'Domingo',
        'Monday' => 'Segunda-feira',
        'Tuesday' => 'Terça-feira',
        'Wednesday' => 'Quarta-feira',
        'Thursday' => 'Quinta-feira',
        'Friday' => 'Sexta-feira',
        'Saturday' => 'Sábado'
    ];
    
    $diaIngles = date('l', strtotime($data));
    return $dias[$diaIngles] ?? $diaIngles;
}

/**
 * Determinar status da disciplina baseado no progresso
 */
function obterStatusDisciplina($aulasRealizadas, $totalAulas, $cargaObrigatoria) {
    if ($totalAulas == 0) {
        return 'nao_iniciada';
    }
    
    if ($aulasRealizadas >= $cargaObrigatoria) {
        return 'concluida';
    }
    
    if ($aulasRealizadas > 0) {
        return 'em_andamento';
    }
    
    return 'agendada';
}
?>
