<?php
/**
 * API Gerador Automático de Grade - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data);
    exit();
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    $turma_id = $data['turma_id'] ?? null;
    $carga_horaria = $data['carga_horaria'] ?? 45;
    $duracao_aula = $data['duracao_aula'] ?? 50;
    $data_inicio = $data['data_inicio'] ?? null;
    $data_fim = $data['data_fim'] ?? null;
    $horario_inicio = $data['horario_inicio'] ?? '08:00';
    $horario_fim = $data['horario_fim'] ?? '18:00';
    $max_aulas_dia = $data['max_aulas_dia'] ?? 5;
    $dias_semana = $data['dias_semana'] ?? [1, 2, 3, 4, 5]; // Segunda a Sexta
    
    if (!$turma_id || !$data_inicio || !$data_fim) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Parâmetros obrigatórios: turma_id, data_inicio, data_fim'
        ], 400);
    }
    
    try {
        // Calcular total de aulas necessárias
        $total_aulas = ceil(($carga_horaria * 60) / $duracao_aula);
        
        // Calcular dias disponíveis
        $dias_disponiveis = calcularDiasDisponiveis($data_inicio, $data_fim, $dias_semana);
        
        // Distribuir aulas pelos dias
        $aulas_distribuidas = distribuirAulas($dias_disponiveis, $total_aulas, $max_aulas_dia, $horario_inicio, $duracao_aula);
        
        // Limpar aulas existentes da turma
        $db->query("DELETE FROM turma_aulas WHERE turma_id = ?", [$turma_id]);
        
        // Inserir novas aulas
        $aulas_criadas = [];
        foreach ($aulas_distribuidas as $index => $aula) {
            $aula_id = $db->insert('turma_aulas', [
                'turma_id' => $turma_id,
                'ordem' => $index + 1,
                'nome_aula' => "Aula " . ($index + 1),
                'duracao_minutos' => $duracao_aula,
                'data_aula' => $aula['data'],
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim'],
                'tipo_conteudo' => 'teorica',
                'status' => 'agendada'
            ]);
            
            $aulas_criadas[] = [
                'id' => $aula_id,
                'ordem' => $index + 1,
                'nome_aula' => "Aula " . ($index + 1),
                'data_aula' => $aula['data'],
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim']
            ];
        }
        
        sendJsonResponse([
            'status' => 'success',
            'message' => 'Grade gerada com sucesso!',
            'data' => [
                'total_aulas' => $total_aulas,
                'dias_utilizados' => count($dias_disponiveis),
                'aulas_criadas' => $aulas_criadas
            ]
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Erro ao gerar grade: ' . $e->getMessage()
        ], 500);
    }
    
} elseif ($method === 'GET') {
    $turma_id = $_GET['turma_id'] ?? null;
    
    if (!$turma_id) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'ID da turma é obrigatório'
        ], 400);
    }
    
    try {
        // Buscar aulas existentes da turma
        $aulas = $db->fetchAll("
            SELECT * FROM turma_aulas 
            WHERE turma_id = ? 
            ORDER BY ordem ASC
        ", [$turma_id]);
        
        // Buscar informações da turma
        $turma = $db->fetch("
            SELECT * FROM turmas 
            WHERE id = ?
        ", [$turma_id]);
        
        sendJsonResponse([
            'status' => 'success',
            'data' => [
                'turma' => $turma,
                'aulas' => $aulas,
                'total_aulas' => count($aulas)
            ]
        ]);
        
    } catch (Exception $e) {
        sendJsonResponse([
            'status' => 'error',
            'message' => 'Erro ao buscar grade: ' . $e->getMessage()
        ], 500);
    }
    
} else {
    sendJsonResponse([
        'status' => 'error',
        'message' => 'Método não permitido'
    ], 405);
}

/**
 * Calcular dias disponíveis entre duas datas, considerando apenas os dias da semana especificados
 */
function calcularDiasDisponiveis($data_inicio, $data_fim, $dias_semana) {
    $dias = [];
    $inicio = new DateTime($data_inicio);
    $fim = new DateTime($data_fim);
    
    while ($inicio <= $fim) {
        $dia_semana = (int)$inicio->format('N'); // 1 = Segunda, 7 = Domingo
        
        if (in_array($dia_semana, $dias_semana)) {
            $dias[] = $inicio->format('Y-m-d');
        }
        
        $inicio->add(new DateInterval('P1D'));
    }
    
    return $dias;
}

/**
 * Distribuir aulas pelos dias disponíveis
 */
function distribuirAulas($dias_disponiveis, $total_aulas, $max_aulas_dia, $horario_inicio, $duracao_aula) {
    $aulas_distribuidas = [];
    $aula_atual = 0;
    
    foreach ($dias_disponiveis as $dia) {
        $aulas_no_dia = min($max_aulas_dia, $total_aulas - $aula_atual);
        
        for ($i = 0; $i < $aulas_no_dia; $i++) {
            $hora_inicio = calcularHoraInicio($horario_inicio, $i, $duracao_aula);
            $hora_fim = calcularHoraFim($hora_inicio, $duracao_aula);
            
            $aulas_distribuidas[] = [
                'data' => $dia,
                'hora_inicio' => $hora_inicio,
                'hora_fim' => $hora_fim
            ];
            
            $aula_atual++;
            
            if ($aula_atual >= $total_aulas) {
                break 2; // Sair dos dois loops
            }
        }
    }
    
    return $aulas_distribuidas;
}

/**
 * Calcular hora de início da aula
 */
function calcularHoraInicio($horario_base, $indice_aula, $duracao_aula) {
    $hora_base = new DateTime($horario_base);
    $minutos_adicionais = $indice_aula * $duracao_aula;
    $hora_base->add(new DateInterval('PT' . $minutos_adicionais . 'M'));
    
    return $hora_base->format('H:i:s');
}

/**
 * Calcular hora de fim da aula
 */
function calcularHoraFim($hora_inicio, $duracao_aula) {
    $hora = new DateTime($hora_inicio);
    $hora->add(new DateInterval('PT' . $duracao_aula . 'M'));
    
    return $hora->format('H:i:s');
}
