<?php
// =====================================================
// API DE VERIFICAÇÃO DE DISPONIBILIDADE - SISTEMA CFC
// =====================================================

// Limpar qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Usar caminho relativo que sabemos que funciona
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
    exit();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit();
}

try {
    $db = db();
    
    // Receber parâmetros
    $data_aula = $_REQUEST['data_aula'] ?? null;
    $hora_inicio = $_REQUEST['hora_inicio'] ?? null;
    $duracao = $_REQUEST['duracao'] ?? 50; // Padrão 50 minutos
    $instrutor_id = $_REQUEST['instrutor_id'] ?? null;
    $veiculo_id = $_REQUEST['veiculo_id'] ?? null;
    $tipo_aula = $_REQUEST['tipo_aula'] ?? null;
    $aluno_id = $_REQUEST['aluno_id'] ?? null; // Adicionado parâmetro aluno_id
    $tipo_agendamento = $_REQUEST['tipo_agendamento'] ?? 'unica';
    $posicao_intervalo = $_REQUEST['posicao_intervalo'] ?? 'depois';
    
    if (!$data_aula || !$hora_inicio) {
        throw new Exception('Data e hora de início são obrigatórios');
    }
    
         // Calcular horários baseados no tipo de agendamento
     $horarios_aulas = calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo);
    
    $resultado = [
        'sucesso' => true,
        'disponivel' => true,
        'mensagem' => 'Horário disponível',
        'detalhes' => []
    ];
    
    // 1. Verificar disponibilidade do instrutor para todas as aulas do bloco
    if ($instrutor_id) {
        $disponibilidade_instrutor = ['disponivel' => true, 'mensagem' => 'Instrutor disponível'];
        
        foreach ($horarios_aulas as $aula) {
            $disponibilidade = verificarDisponibilidadeInstrutor($db, $instrutor_id, $data_aula, $aula['hora_inicio'], $aula['hora_fim']);
            if (!$disponibilidade['disponivel']) {
                $disponibilidade_instrutor = $disponibilidade;
                break;
            }
        }
        
        $resultado['detalhes']['instrutor'] = $disponibilidade_instrutor;
        
        if (!$disponibilidade_instrutor['disponivel']) {
            $resultado['disponivel'] = false;
            $resultado['mensagem'] = 'Instrutor não disponível neste horário';
        }
    }
    
    // 2. Verificar disponibilidade do veículo para todas as aulas do bloco
    if ($veiculo_id && $tipo_aula !== 'teorica') {
        $disponibilidade_veiculo = ['disponivel' => true, 'mensagem' => 'Veículo disponível'];
        
        foreach ($horarios_aulas as $aula) {
            $disponibilidade = verificarDisponibilidadeVeiculo($db, $veiculo_id, $data_aula, $aula['hora_inicio'], $aula['hora_fim']);
            if (!$disponibilidade['disponivel']) {
                $disponibilidade_veiculo = $disponibilidade;
                break;
            }
        }
        
        $resultado['detalhes']['veiculo'] = $disponibilidade_veiculo;
        
        if (!$disponibilidade_veiculo['disponivel']) {
            $resultado['disponivel'] = false;
            $resultado['mensagem'] = 'Veículo não disponível neste horário';
        }
    }
    
    // 3. Verificar padrões de aulas para todas as aulas do bloco
    if ($instrutor_id) {
        $padrao_valido = ['valido' => true, 'mensagem' => 'Padrão de aulas respeitado'];
        
        foreach ($horarios_aulas as $aula) {
            $padrao = verificarPadraoAulas($db, $instrutor_id, $data_aula, $aula['hora_inicio'], $aula['hora_fim']);
            if (!$padrao['valido']) {
                $padrao_valido = $padrao;
                break;
            }
        }
        
        $resultado['detalhes']['padrao'] = $padrao_valido;
        
        if (!$padrao_valido['valido']) {
            $resultado['disponivel'] = false;
            $resultado['mensagem'] = $padrao_valido['mensagem'];
        }
    }
    
    // 3. Verificar limite diário do ALUNO (máximo 3 aulas práticas por dia)
    if ($aluno_id && $tipo_aula === 'pratica') {
        $limite_aluno = verificarLimiteDiarioAluno($db, $aluno_id, $data_aula, count($horarios_aulas));
        $resultado['detalhes']['limite_aluno'] = $limite_aluno;
        
        if (!$limite_aluno['disponivel']) {
            $resultado['disponivel'] = false;
            $resultado['mensagem'] = $limite_aluno['mensagem'];
        }
    }
    
    // 4. Verificar limite diário do instrutor baseado no horário de trabalho
    if ($instrutor_id) {
        $limite_diario = verificarLimiteDiario($db, $instrutor_id, $data_aula, count($horarios_aulas));
        $resultado['detalhes']['limite_diario'] = $limite_diario;
        
        if (!$limite_diario['disponivel']) {
            $resultado['disponivel'] = false;
            $resultado['mensagem'] = $limite_diario['mensagem'];
        }
    }
    
    // 5. Sugerir horários alternativos se não disponível
    if (!$resultado['disponivel']) {
        $horarios_alternativos = sugerirHorariosAlternativos($db, $instrutor_id, $data_aula, $duracao, $tipo_agendamento);
        $resultado['horarios_alternativos'] = $horarios_alternativos;
    }
    
    echo json_encode($resultado);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'erro' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
}

/**
 * Verifica disponibilidade do instrutor
 */
function verificarDisponibilidadeInstrutor($db, $instrutor_id, $data_aula, $hora_inicio, $hora_fim) {
    // Verificar conflitos de horário
    $conflito = $db->fetch("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' AND ((hora_inicio <= ? AND hora_fim > ?) OR (hora_inicio < ? AND hora_fim >= ?) OR (hora_inicio >= ? AND hora_fim <= ?))", [$instrutor_id, $data_aula, $hora_inicio, $hora_inicio, $hora_fim, $hora_fim, $hora_inicio, $hora_fim]);
    
    if ($conflito) {
        return [
            'disponivel' => false,
            'conflito' => [
                'hora_inicio' => $conflito['hora_inicio'],
                'hora_fim' => $conflito['hora_fim'],
                'aluno' => $conflito['aluno_id']
            ],
            'mensagem' => 'Instrutor já possui aula agendada neste horário'
        ];
    }
    
    return ['disponivel' => true, 'mensagem' => 'Instrutor disponível'];
}

/**
 * Verifica disponibilidade do veículo
 */
function verificarDisponibilidadeVeiculo($db, $veiculo_id, $data_aula, $hora_inicio, $hora_fim) {
    // Verificar conflitos de horário
    $conflito = $db->fetch("SELECT * FROM aulas WHERE veiculo_id = ? AND data_aula = ? AND status != 'cancelada' AND ((hora_inicio <= ? AND hora_fim > ?) OR (hora_inicio < ? AND hora_fim >= ?) OR (hora_inicio >= ? AND hora_fim <= ?))", [$veiculo_id, $data_aula, $hora_inicio, $hora_inicio, $hora_fim, $hora_fim, $hora_inicio, $hora_fim]);
    
    if ($conflito) {
        return [
            'disponivel' => false,
            'conflito' => [
                'hora_inicio' => $conflito['hora_inicio'],
                'hora_fim' => $conflito['hora_fim'],
                'aluno' => $conflito['aluno_id']
            ],
            'mensagem' => 'Veículo já está em uso neste horário'
        ];
    }
    
    return ['disponivel' => true, 'mensagem' => 'Veículo disponível'];
}

/**
 * Verifica se a nova aula respeita os padrões implementados
 */
function verificarPadraoAulas($db, $instrutor_id, $data_aula, $nova_hora_inicio, $nova_hora_fim) {
    $aulas_existentes = $db->fetchAll("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' ORDER BY hora_inicio", [$instrutor_id, $data_aula]);
    
    if (empty($aulas_existentes)) {
        return ['valido' => true, 'mensagem' => 'Primeira aula do dia'];
    }
    
    // Converter horários para minutos
    $nova_inicio_min = horaParaMinutos($nova_hora_inicio);
    $nova_fim_min = horaParaMinutos($nova_hora_fim);
    
    // Verificar sobreposições
    foreach ($aulas_existentes as $aula) {
        $aula_inicio_min = horaParaMinutos($aula['hora_inicio']);
        $aula_fim_min = horaParaMinutos($aula['hora_fim']);
        
        if (($nova_inicio_min < $aula_fim_min) && ($nova_fim_min > $aula_inicio_min)) {
            return ['valido' => false, 'mensagem' => 'A nova aula sobrepõe horário de aula existente'];
        }
    }
    
    // Verificar padrões de intervalo
    $aulas_ordenadas = array_merge($aulas_existentes, [
        ['hora_inicio' => $nova_hora_inicio, 'hora_fim' => $nova_hora_fim]
    ]);
    
    usort($aulas_ordenadas, function($a, $b) {
        return strtotime($a['hora_inicio']) - strtotime($b['hora_inicio']);
    });
    
    // Verificar intervalos
    for ($i = 0; $i < count($aulas_ordenadas) - 1; $i++) {
        $aula_atual = $aulas_ordenadas[$i];
        $proxima_aula = $aulas_ordenadas[$i + 1];
        
        $fim_atual = horaParaMinutos($aula_atual['hora_fim']);
        $inicio_proxima = horaParaMinutos($proxima_aula['hora_inicio']);
        
        $intervalo = $inicio_proxima - $fim_atual;
        
        if ($intervalo > 0 && $intervalo < 30) {
            return ['valido' => false, 'mensagem' => 'Intervalo entre aulas deve ser de 30 minutos ou aulas consecutivas'];
        }
    }
    
    return ['valido' => true, 'mensagem' => 'Padrão de aulas respeitado'];
}

/**
 * Verifica limite diário do ALUNO (máximo 3 aulas por dia)
 */
function verificarLimiteDiarioAluno($db, $aluno_id, $data_aula, $aulas_novas = 1) {
    // Buscar informações do aluno
    $aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$aluno_id]);
    
    if (!$aluno) {
        return [
            'disponivel' => false,
            'mensagem' => 'Aluno não encontrado'
        ];
    }
    
    // Buscar aulas práticas já agendadas para o dia
    $aulas_hoje = $db->fetchAll("SELECT COUNT(*) as total FROM aulas WHERE aluno_id = ? AND data_aula = ? AND status != 'cancelada' AND tipo_aula = 'pratica'", [$aluno_id, $data_aula]);
    $total_aulas = $aulas_hoje[0]['total'];
    $total_com_novas = $total_aulas + $aulas_novas;
    
    // Limite fixo de 3 aulas práticas por dia para alunos
    $limite_aluno = 3;
    
    if ($total_com_novas > $limite_aluno) {
        return [
            'disponivel' => false,
            'total_aulas' => $total_aulas,
            'aulas_novas' => $aulas_novas,
            'limite' => $limite_aluno,
            'mensagem' => "🚫 LIMITE DE AULAS EXCEDIDO: O aluno já possui {$total_aulas} aulas práticas agendadas para este dia. Com {$aulas_novas} nova(s) aula(s) prática(s), excederia o limite máximo de {$limite_aluno} aulas práticas por dia."
        ];
    }
    
    return [
        'disponivel' => true,
        'total_aulas' => $total_aulas,
        'aulas_novas' => $aulas_novas,
        'limite' => $limite_aluno,
        'aulas_restantes' => $limite_aluno - $total_com_novas,
        'mensagem' => "Aluno pode agendar mais " . ($limite_aluno - $total_com_novas) . " aula(s) prática(s) (limite: {$limite_aluno} aulas práticas por dia)"
    ];
}

/**
 * Verifica disponibilidade do instrutor baseada no horário de trabalho
 */
function verificarLimiteDiario($db, $instrutor_id, $data_aula, $aulas_novas = 1) {
    // Buscar informações do instrutor incluindo horário de trabalho
    $instrutor = $db->fetch("SELECT i.*, u.nome FROM instrutores i LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE i.id = ?", [$instrutor_id]);
    
    if (!$instrutor) {
        return [
            'disponivel' => false,
            'mensagem' => 'Instrutor não encontrado'
        ];
    }
    
    // Verificar se o instrutor tem horário de trabalho configurado
    $horario_inicio = $instrutor['horario_inicio'] ?? '08:00';
    $horario_fim = $instrutor['horario_fim'] ?? '18:00';
    
    // Converter horários para minutos para facilitar cálculos
    $inicio_minutos = horaParaMinutos($horario_inicio);
    $fim_minutos = horaParaMinutos($horario_fim);
    $duracao_total_minutos = $fim_minutos - $inicio_minutos;
    
    // Calcular quantas aulas de 50 minutos cabem no horário de trabalho
    $max_aulas_possiveis = floor($duracao_total_minutos / 50);
    
    // Buscar aulas já agendadas para o dia
    $aulas_hoje = $db->fetchAll("SELECT COUNT(*) as total FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'", [$instrutor_id, $data_aula]);
    $total_aulas = $aulas_hoje[0]['total'];
    $total_com_novas = $total_aulas + $aulas_novas;
    
    // Verificar se excede o horário de trabalho
    if ($total_com_novas > $max_aulas_possiveis) {
        return [
            'disponivel' => false,
            'total_aulas' => $total_aulas,
            'aulas_novas' => $aulas_novas,
            'limite' => $max_aulas_possiveis,
            'horario_trabalho' => "{$horario_inicio} às {$horario_fim}",
            'mensagem' => "Instrutor já possui {$total_aulas} aulas agendadas. Com {$aulas_novas} novas aulas, excederia o horário de trabalho ({$horario_inicio} às {$horario_fim}). Máximo possível: {$max_aulas_possiveis} aulas."
        ];
    }
    
    return [
        'disponivel' => true,
        'total_aulas' => $total_aulas,
        'aulas_novas' => $aulas_novas,
        'limite' => $max_aulas_possiveis,
        'horario_trabalho' => "{$horario_inicio} às {$horario_fim}",
        'aulas_restantes' => $max_aulas_possiveis - $total_com_novas,
        'mensagem' => "Instrutor pode agendar mais " . ($max_aulas_possiveis - $total_com_novas) . " aula(s) dentro do horário de trabalho ({$horario_inicio} às {$horario_fim})"
    ];
}

/**
 * Sugere horários alternativos
 */
function sugerirHorariosAlternativos($db, $instrutor_id, $data_aula, $duracao, $tipo_agendamento) {
    $aulas_existentes = $db->fetchAll("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' ORDER BY hora_inicio", [$instrutor_id, $data_aula]);
    
    $horarios_sugeridos = [];
    $horarios_disponiveis = [
        '08:00', '08:50', '09:40', '10:30', '11:20', '12:10',
        '14:00', '14:50', '15:40', '16:30', '17:20', '18:10'
    ];
    
    foreach ($horarios_disponiveis as $horario) {
                 // Calcular horários para o tipo de agendamento
         $horarios_aulas = calcularHorariosAulas($horario, $tipo_agendamento, $posicao_intervalo);
        
        // Verificar se todos os horários do bloco estão disponíveis
        $disponivel = true;
        foreach ($horarios_aulas as $aula) {
            foreach ($aulas_existentes as $aula_existente) {
                if (($aula['hora_inicio'] < $aula_existente['hora_fim']) && ($aula['hora_fim'] > $aula_existente['hora_inicio'])) {
                    $disponivel = false;
                    break 2;
                }
            }
        }
        
        if ($disponivel) {
            $horarios_sugeridos[] = [
                'hora_inicio' => $horario,
                'tipo_agendamento' => $tipo_agendamento,
                'total_aulas' => count($horarios_aulas),
                'duracao_total' => count($horarios_aulas) * 50 + (count($horarios_aulas) > 1 ? (count($horarios_aulas) - 1) * 30 : 0)
            ];
        }
    }
    
    return array_slice($horarios_sugeridos, 0, 5); // Máximo 5 sugestões
}

 /**
  * Calcula os horários das aulas baseado no tipo de agendamento
  */
 function calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo = 'depois') {
     $horarios = [];
     
     // Converter hora de início para minutos
     $inicio_minutos = horaParaMinutos($hora_inicio);
     
     switch ($tipo_agendamento) {
         case 'unica':
             // 1 aula: 50 minutos
             $horarios[] = [
                 'hora_inicio' => $hora_inicio,
                 'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (50 * 60))
             ];
             break;
             
         case 'duas':
             // 2 aulas consecutivas: 50 + 50 = 100 minutos
             $horarios[] = [
                 'hora_inicio' => $hora_inicio,
                 'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (50 * 60))
             ];
             $horarios[] = [
                 'hora_inicio' => date('H:i:s', strtotime($hora_inicio) + (50 * 60)),
                 'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (100 * 60))
             ];
             break;
             
         case 'tres':
             // 3 aulas com intervalo de 30min = 180 minutos total
             if ($posicao_intervalo === 'depois') {
                 // 2 consecutivas + 30min intervalo + 1 aula
                 $horarios[] = [
                     'hora_inicio' => $hora_inicio,
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (50 * 60))
                 ];
                 $horarios[] = [
                     'hora_inicio' => date('H:i:s', strtotime($hora_inicio) + (50 * 60)),
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (100 * 60))
                 ];
                 $horarios[] = [
                     'hora_inicio' => date('H:i:s', strtotime($hora_inicio) + (130 * 60)),
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (180 * 60))
                 ];
             } else {
                 // 1 aula + 30min intervalo + 2 consecutivas
                 $horarios[] = [
                     'hora_inicio' => $hora_inicio,
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (50 * 60))
                 ];
                 $horarios[] = [
                     'hora_inicio' => date('H:i:s', strtotime($hora_inicio) + (80 * 60)),
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (130 * 60))
                 ];
                 $horarios[] = [
                     'hora_inicio' => date('H:i:s', strtotime($hora_inicio) + (130 * 60)),
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (180 * 60))
                 ];
             }
             break;
             
         default:
             throw new Exception('Tipo de agendamento inválido');
     }
     
     return $horarios;
 }

 /**
  * Converte horário HH:MM para minutos desde 00:00
  */
 function horaParaMinutos($hora) {
     $partes = explode(':', $hora);
     return ($partes[0] * 60) + $partes[1];
 }
 ?>
