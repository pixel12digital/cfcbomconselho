<?php
// =====================================================
// API DE AGENDAMENTO - SISTEMA CFC
// =====================================================

// Limpar qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

// Verificar método HTTP e rotear para função apropriada
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Buscar aulas para exibir no calendário
    buscarAulas();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Método não permitido']);
    exit();
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    session_start();
}

// Verificar se usuário está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
    exit();
}

$currentUser = getCurrentUser();
if (!$currentUser) {
    http_response_code(401);
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida']);
    exit();
}

try {
    $db = db();
    
    // Se for JSON, usar os dados do JSON, senão usar $_POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    // Debug: log da requisição
    error_log("Requisição recebida: " . json_encode($data));
    error_log("Usuário atual: " . $currentUser['email'] . " (Tipo: " . $currentUser['tipo'] . ")");
    
    if ($data && isset($data['acao'])) {
        $acao = $data['acao'];
        
        // Verificar permissões específicas por ação
        if ($acao === 'criar' && !canAddLessons()) {
            http_response_code(403);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Apenas administradores e atendentes podem adicionar aulas']);
            exit();
        }
        
        if (($acao === 'editar' || $acao === 'cancelar') && !canEditLessons()) {
            http_response_code(403);
            echo json_encode(['sucesso' => false, 'mensagem' => 'Você não tem permissão para editar aulas']);
            exit();
        }
        
        // Para ações específicas como cancelar
        if ($acao === 'cancelar' && isset($data['aula_id'])) {
            cancelarAula($data['aula_id']);
            exit();
        }
        
        // Para ação de editar
        if ($acao === 'editar' && isset($data['aula_id'])) {
            editarAula($data['aula_id'], $data);
            exit();
        }
        
        // Para criação de nova aula
        if ($acao === 'criar') {
            criarAula($data);
            exit();
        }
    }
        $observacoes = $data['observacoes'] ?? '';
        $tipo_agendamento = $data['tipo_agendamento'] ?? 'unica';
        $posicao_intervalo = $data['posicao_intervalo'] ?? 'depois';
    } else {
        // Receber dados do formulário (comportamento original)
        $acao = 'criar';
        $aluno_id = $_POST['aluno_id'] ?? null;
        $data_aula = $_POST['data_aula'] ?? null;
        $hora_inicio = $_POST['hora_inicio'] ?? null;
        $duracao = $_POST['duracao'] ?? null;
        $tipo_aula = $_POST['tipo_aula'] ?? null;
        $instrutor_id = $_POST['instrutor_id'] ?? null;
        $veiculo_id = $_POST['veiculo_id'] ?? null;
        $disciplina = $_POST['disciplina'] ?? null;
        $observacoes = $_POST['observacoes'] ?? '';
        $tipo_agendamento = $_POST['tipo_agendamento'] ?? 'unica';
        $posicao_intervalo = $_POST['posicao_intervalo'] ?? 'depois';
    }
    
    // Validar dados obrigatórios
    if (!$aluno_id || !$data_aula || !$hora_inicio || !$duracao || !$tipo_aula || !$instrutor_id) {
        throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
    }
    
    // Validar disciplina para aulas teóricas
    if ($tipo_aula === 'teorica' && !$disciplina) {
        throw new Exception('Disciplina é obrigatória para aulas teóricas');
    }
    
    // Validar veículo para aulas práticas
    if ($tipo_aula !== 'teorica' && !$veiculo_id) {
        throw new Exception('Veículo é obrigatório para aulas práticas');
    }
    
    // Validar duração fixa de 50 minutos
    if ($duracao != 50) {
        throw new Exception('A aula deve ter exatamente 50 minutos de duração');
    }
    
         // Calcular horários baseados no tipo de agendamento
     $horarios_aulas = calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo);
    
    // Buscar informações do aluno e CFC
    $aluno = $db->fetch("SELECT a.*, c.id as cfc_id FROM alunos a JOIN cfcs c ON a.cfc_id = c.id WHERE a.id = ?", [$aluno_id]);
    if (!$aluno) {
        throw new Exception('Aluno não encontrado');
    }
    
    // Verificar se instrutor existe e está ativo
    $instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ? AND ativo = 1", [$instrutor_id]);
    if (!$instrutor) {
        throw new Exception('Instrutor não encontrado ou inativo');
    }
    
    // Verificar se veículo é obrigatório para aulas práticas
    if ($tipo_aula !== 'teorica' && !$veiculo_id) {
        throw new Exception('Veículo é obrigatório para aulas práticas');
    }
    
    // Verificar se veículo existe e está disponível
    if ($veiculo_id) {
        $veiculo = $db->fetch("SELECT * FROM veiculos WHERE id = ? AND ativo = 1", [$veiculo_id]);
        if (!$veiculo) {
            throw new Exception('Veículo não encontrado ou inativo');
        }
    }
    
    // VALIDAÇÕES DE NEGÓCIO
    
    // 1. Verificar limite diário do instrutor (máximo 3 aulas)
    $aulas_hoje = $db->fetchAll("SELECT COUNT(*) as total FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'", [$instrutor_id, $data_aula]);
    $total_aulas_existentes = $aulas_hoje[0]['total'] ?? 0;
    $total_aulas_novas = count($horarios_aulas);
    
    // Debug: log dos valores
    error_log("Debug agendamento - Instrutor ID: $instrutor_id, Data: $data_aula");
    error_log("Debug agendamento - Total aulas existentes: $total_aulas_existentes");
    error_log("Debug agendamento - Total aulas novas: $total_aulas_novas");
    error_log("Debug agendamento - Horários calculados: " . json_encode($horarios_aulas));
    
    if (($total_aulas_existentes + $total_aulas_novas) > 3) {
        throw new Exception("Instrutor já possui {$total_aulas_existentes} aulas agendadas. Com {$total_aulas_novas} novas aulas, excederia o limite de 3 aulas por dia.");
    }
    
    // 2. Verificar conflitos de horário para instrutor (todas as aulas do bloco)
    foreach ($horarios_aulas as $aula) {
        $conflito_instrutor = $db->fetch("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' AND (
            (hora_inicio <= ? AND hora_fim > ?) OR
            (hora_inicio < ? AND hora_fim >= ?) OR
            (hora_inicio >= ? AND hora_fim <= ?)
        )", [$instrutor_id, $data_aula, $aula['hora_inicio'], $aula['hora_inicio'], $aula['hora_fim'], $aula['hora_fim'], $aula['hora_inicio'], $aula['hora_fim']]);
        
        if ($conflito_instrutor) {
            throw new Exception("Instrutor já possui aula agendada no horário {$aula['hora_inicio']} - {$aula['hora_fim']}");
        }
    }
    
    // 3. Verificar conflitos de horário para veículo (se aplicável)
    if ($veiculo_id) {
        foreach ($horarios_aulas as $aula) {
            $conflito_veiculo = $db->fetch("SELECT * FROM aulas WHERE veiculo_id = ? AND data_aula = ? AND status != 'cancelada' AND (
                (hora_inicio <= ? AND hora_fim > ?) OR
                (hora_inicio < ? AND hora_fim >= ?) OR
                (hora_inicio >= ? AND hora_fim <= ?)
            )", [$veiculo_id, $data_aula, $aula['hora_inicio'], $aula['hora_inicio'], $aula['hora_fim'], $aula['hora_fim'], $aula['hora_inicio'], $aula['hora_fim']]);
            
            if ($conflito_veiculo) {
                throw new Exception("Veículo já está em uso no horário {$aula['hora_inicio']} - {$aula['hora_fim']}");
            }
        }
    }
    
    // 4. Verificar padrão de aulas e intervalos para todas as aulas do bloco
    $aulas_existentes = $db->fetchAll("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' ORDER BY hora_inicio", [$instrutor_id, $data_aula]);
    
    if (count($aulas_existentes) > 0) {
        // Verificar se cada aula do bloco respeita os padrões implementados
        foreach ($horarios_aulas as $aula) {
            $padrao_valido = verificarPadraoAulas($aulas_existentes, $aula['hora_inicio'], $aula['hora_fim']);
            if (!$padrao_valido['valido']) {
                throw new Exception($padrao_valido['mensagem']);
            }
        }
    }
    
    // =====================================================
    // VALIDAÇÕES COMPLETAS ANTES DE CRIAR AULAS
    // =====================================================
    
    // Validar cada aula do bloco antes de inserir
    foreach ($horarios_aulas as $index => $aula) {
        $validacao = validarAgendamento($aluno_id, $instrutor_id, $veiculo_id, $data_aula, $aula['hora_inicio'], $aula['hora_fim'], $tipo_aula, $db);
        
        if (!$validacao['valido']) {
            throw new Exception($validacao['mensagem']);
        }
    }
    
    // INSERIR MÚLTIPLAS AULAS NO BANCO
    $aulas_criadas = [];
    $sql = "INSERT INTO aulas (aluno_id, instrutor_id, cfc_id, veiculo_id, tipo_aula, disciplina, data_aula, hora_inicio, hora_fim, status, observacoes, criado_em) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'agendada', ?, NOW())";
    
    foreach ($horarios_aulas as $index => $aula) {
        $params = [
            $aluno_id,
            $instrutor_id,
            $aluno['cfc_id'],
            $veiculo_id ?: null,
            $tipo_aula,
            $disciplina ?: null,
            $data_aula,
            $aula['hora_inicio'],
            $aula['hora_fim'],
            $observacoes . ($index > 0 ? " (Aula " . ($index + 1) . " do bloco)" : "")
        ];
        
        $result = $db->query($sql, $params);
        
        if ($result) {
            $aula_id = $db->lastInsertId();
            $aulas_criadas[] = [
                'id' => $aula_id,
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim']
            ];
            
            // Log de auditoria para cada aula
            $log_sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                        VALUES (?, 'INSERT', 'aulas', ?, NULL, ?, ?, NOW())";
            
            $dados_json = json_encode([
                'aluno_id' => $aluno_id,
                'instrutor_id' => $instrutor_id,
                'tipo_aula' => $tipo_aula,
                'data_aula' => $data_aula,
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim'],
                'veiculo_id' => $veiculo_id,
                'aula_bloco' => $index + 1
            ]);
            
            $db->query($log_sql, [$_SESSION['user_id'], $aula_id, $dados_json, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
        } else {
            throw new Exception('Erro ao salvar aula ' . ($index + 1) . ' no banco de dados');
        }
    }
    
    // Mensagem de sucesso baseada no tipo de agendamento
    $mensagem_sucesso = '';
    switch ($tipo_agendamento) {
        case 'unica':
            $mensagem_sucesso = 'Aula agendada com sucesso!';
            break;
        case 'duas':
            $mensagem_sucesso = '2 aulas agendadas com sucesso!';
            break;
        case 'tres':
            $mensagem_sucesso = '3 aulas agendadas com sucesso!';
            break;
    }
    
    echo json_encode([
        'sucesso' => true,
        'mensagem' => $mensagem_sucesso,
        'aulas_criadas' => $aulas_criadas,
        'tipo_agendamento' => $tipo_agendamento,
        'dados' => [
            'aluno' => $aluno['nome'],
            'instrutor' => $instrutor['credencial'],
            'data' => $data_aula,
            'total_aulas' => count($aulas_criadas),
            'tipo' => ucfirst($tipo_aula)
        ]
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage(),
        'erro' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
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
                     'hora_inicio' => date('H:i:s', strtotime($hora_inicio) + (160 * 60)),
                     'hora_fim' => date('H:i:s', strtotime($hora_inicio) + (210 * 60))
                 ];
             }
             break;
            
        default:
            throw new Exception('Tipo de agendamento inválido');
    }
    
    return $horarios;
}

/**
 * Verifica se a nova aula respeita os padrões implementados
 */
function verificarPadraoAulas($aulas_existentes, $nova_hora_inicio, $nova_hora_fim) {
    // Converter horários para minutos para facilitar cálculos
    $nova_inicio_min = horaParaMinutos($nova_hora_inicio);
    $nova_fim_min = horaParaMinutos($nova_hora_fim);
    
    // Se não há aulas existentes, qualquer horário é válido
    if (empty($aulas_existentes)) {
        return ['valido' => true, 'mensagem' => ''];
    }
    
    // Verificar se a nova aula se encaixa nos padrões implementados
    foreach ($aulas_existentes as $aula) {
        $aula_inicio_min = horaParaMinutos($aula['hora_inicio']);
        $aula_fim_min = horaParaMinutos($aula['hora_fim']);
        
        // Verificar se há sobreposição
        if (($nova_inicio_min < $aula_fim_min) && ($nova_fim_min > $aula_inicio_min)) {
            return ['valido' => false, 'mensagem' => 'A nova aula sobrepõe horário de aula existente'];
        }
    }
    
    // Verificar padrões de intervalo (30 minutos entre blocos)
    $aulas_ordenadas = array_merge($aulas_existentes, [
        ['hora_inicio' => $nova_hora_inicio, 'hora_fim' => $nova_hora_fim]
    ]);
    
    // Ordenar por hora de início
    usort($aulas_ordenadas, function($a, $b) {
        return strtotime($a['hora_inicio']) - strtotime($b['hora_inicio']);
    });
    
    // Verificar se os intervalos estão corretos
    for ($i = 0; $i < count($aulas_ordenadas) - 1; $i++) {
        $aula_atual = $aulas_ordenadas[$i];
        $proxima_aula = $aulas_ordenadas[$i + 1];
        
        $fim_atual = horaParaMinutos($aula_atual['hora_fim']);
        $inicio_proxima = horaParaMinutos($proxima_aula['hora_inicio']);
        
        $intervalo = $inicio_proxima - $fim_atual;
        
        // Intervalo deve ser 0 (aulas consecutivas) ou 30 minutos
        if ($intervalo > 0 && $intervalo < 30) {
            return ['valido' => false, 'mensagem' => 'Intervalo entre aulas deve ser de 30 minutos ou aulas consecutivas'];
        }
    }
    
    return ['valido' => true, 'mensagem' => ''];
}

/**
 * Converte horário HH:MM para minutos desde 00:00
 */
function horaParaMinutos($hora) {
    $partes = explode(':', $hora);
    return ($partes[0] * 60) + $partes[1];
}

/**
 * Editar uma aula
 */
function editarAula($aula_id, $data) {
    try {
        $db = db();
        
        // Debug: log dos dados recebidos
        error_log("Dados recebidos para edição: " . json_encode($data));
        error_log("ID da aula: " . $aula_id);
        
        // Verificar se a aula existe e está agendada
        $aula_antiga = $db->fetch("SELECT * FROM aulas WHERE id = ? AND status = 'agendada'", [$aula_id]);
        if (!$aula_antiga) {
            throw new Exception('Aula não encontrada ou não pode ser editada. ID: ' . $aula_id);
        }
        
        // Validar dados obrigatórios
        $aluno_id = $data['edit_aluno_id'] ?? null;
        $data_aula = $data['edit_data_aula'] ?? null;
        $hora_inicio = $data['edit_hora_inicio'] ?? null;
        $hora_fim = $data['edit_hora_fim'] ?? null;
        $tipo_aula = $data['edit_tipo_aula'] ?? null;
        $instrutor_id = $data['edit_instrutor_id'] ?? null;
        $veiculo_id = $data['edit_veiculo_id'] ?? null;
        $observacoes = $data['edit_observacoes'] ?? '';
        
        if (!$aluno_id || !$data_aula || !$hora_inicio || !$hora_fim || !$tipo_aula || !$instrutor_id) {
            throw new Exception('Todos os campos obrigatórios devem ser preenchidos');
        }
        
        // Verificar se instrutor existe e está ativo
        $instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ? AND ativo = 1", [$instrutor_id]);
        if (!$instrutor) {
            throw new Exception('Instrutor não encontrado ou inativo');
        }
        
        // Verificar veículo se for aula prática
        if ($tipo_aula !== 'teorica') {
            if (!$veiculo_id) {
                throw new Exception('Veículo é obrigatório para aulas práticas');
            }
            $veiculo = $db->fetch("SELECT * FROM veiculos WHERE id = ? AND ativo = 1", [$veiculo_id]);
            if (!$veiculo) {
                throw new Exception('Veículo não encontrado ou inativo');
            }
        }
        
        // Atualizar aula
        $sql = "UPDATE aulas SET 
                aluno_id = ?, 
                instrutor_id = ?, 
                veiculo_id = ?, 
                data_aula = ?, 
                hora_inicio = ?, 
                hora_fim = ?, 
                tipo_aula = ?, 
                observacoes = ?, 
                atualizado_em = NOW() 
                WHERE id = ?";
        
        $db->query($sql, [
            $aluno_id,
            $instrutor_id,
            $veiculo_id ?: null,
            $data_aula,
            $hora_inicio,
            $hora_fim,
            $tipo_aula,
            $observacoes,
            $aula_id
        ]);
        
        // Log de auditoria
        $log_sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'UPDATE', 'aulas', ?, ?, ?, ?, NOW())";
        
        $dados_anteriores = json_encode([
            'aluno_id' => $aula_antiga['aluno_id'],
            'instrutor_id' => $aula_antiga['instrutor_id'],
            'veiculo_id' => $aula_antiga['veiculo_id'],
            'data_aula' => $aula_antiga['data_aula'],
            'hora_inicio' => $aula_antiga['hora_inicio'],
            'hora_fim' => $aula_antiga['hora_fim'],
            'tipo_aula' => $aula_antiga['tipo_aula'],
            'observacoes' => $aula_antiga['observacoes']
        ]);
        
        $dados_novos = json_encode([
            'aluno_id' => $aluno_id,
            'instrutor_id' => $instrutor_id,
            'veiculo_id' => $veiculo_id,
            'data_aula' => $data_aula,
            'hora_inicio' => $hora_inicio,
            'hora_fim' => $hora_fim,
            'tipo_aula' => $tipo_aula,
            'observacoes' => $observacoes
        ]);
        
        $db->query($log_sql, [
            $_SESSION['user_id'],
            $aula_id,
            $dados_anteriores,
            $dados_novos,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Aula atualizada com sucesso'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ]);
    }
}

/**
 * Cancelar uma aula
 */
function cancelarAula($aula_id) {
    try {
        $db = db();
        
        // Verificar se a aula existe e está agendada
        $aula = $db->fetch("SELECT * FROM aulas WHERE id = ? AND status = 'agendada'", [$aula_id]);
        if (!$aula) {
            throw new Exception('Aula não encontrada ou já não está agendada');
        }
        
        // Atualizar status para cancelada
        $db->query("UPDATE aulas SET status = 'cancelada', atualizado_em = NOW() WHERE id = ?", [$aula_id]);
        
        // Log de auditoria
        $log_sql = "INSERT INTO logs (usuario_id, acao, tabela, registro_id, dados_anteriores, dados_novos, ip_address, criado_em) 
                    VALUES (?, 'UPDATE', 'aulas', ?, ?, ?, ?, NOW())";
        
        $dados_anteriores = json_encode([
            'status' => 'agendada',
            'aluno_id' => $aula['aluno_id'],
            'instrutor_id' => $aula['instrutor_id'],
            'data_aula' => $aula['data_aula'],
            'hora_inicio' => $aula['hora_inicio']
        ]);
        
        $dados_novos = json_encode([
            'status' => 'cancelada',
            'aluno_id' => $aula['aluno_id'],
            'instrutor_id' => $aula['instrutor_id'],
            'data_aula' => $aula['data_aula'],
            'hora_inicio' => $aula['hora_inicio']
        ]);
        
        $db->query($log_sql, [
            $_SESSION['user_id'],
            $aula_id,
            $dados_anteriores,
            $dados_novos,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        echo json_encode([
            'sucesso' => true,
            'mensagem' => 'Aula cancelada com sucesso'
        ]);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage()
        ]);
    }
}

/**
 * Buscar aulas para exibir no calendário
 */
function buscarAulas() {
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
        
        // Buscar aulas com informações relacionadas (últimos 6 meses e próximos 6 meses)
        $aulas = $db->fetchAll("
            SELECT a.*, 
                   al.nome as aluno_nome,
                   COALESCE(u.nome, i.nome) as instrutor_nome,
                   v.placa, v.modelo, v.marca
            FROM aulas a
            JOIN alunos al ON a.aluno_id = al.id
            JOIN instrutores i ON a.instrutor_id = i.id
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            LEFT JOIN veiculos v ON a.veiculo_id = v.id
            WHERE a.data_aula >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
              AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
            ORDER BY a.data_aula, a.hora_inicio
        ");
        
        echo json_encode([
            'sucesso' => true,
            'dados' => $aulas,
            'total' => count($aulas)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => 'Erro ao buscar aulas: ' . $e->getMessage(),
            'erro' => DEBUG_MODE ? $e->getTraceAsString() : null
        ]);
    }
}

// =====================================================
// FUNÇÃO DE VALIDAÇÃO COMPLETA DE AGENDAMENTOS
// =====================================================

function validarAgendamento($aluno_id, $instrutor_id, $veiculo_id, $data_aula, $hora_inicio, $hora_fim, $tipo_aula, $db) {
    try {
        // 1. VALIDAR SE ALUNO JÁ TEM AGENDAMENTO NO MESMO HORÁRIO
        $aula_aluno = $db->fetch("
            SELECT a.*, al.nome as aluno_nome 
            FROM aulas a 
            JOIN alunos al ON a.aluno_id = al.id 
            WHERE a.aluno_id = ? 
            AND a.data_aula = ? 
            AND a.status != 'cancelada'
            AND (
                (a.hora_inicio < ? AND a.hora_fim > ?) OR
                (a.hora_inicio < ? AND a.hora_fim > ?) OR
                (a.hora_inicio >= ? AND a.hora_fim <= ?)
            )
        ", [$aluno_id, $data_aula, $hora_fim, $hora_inicio, $hora_inicio, $hora_fim, $hora_inicio, $hora_fim]);
        
        if ($aula_aluno) {
            return [
                'valido' => false,
                'mensagem' => "❌ ALUNO JÁ AGENDADO: O aluno {$aula_aluno['aluno_nome']} já possui uma aula agendada no horário {$aula_aluno['hora_inicio']} - {$aula_aluno['hora_fim']} neste mesmo dia."
            ];
        }
        
        // 2. VALIDAR DISPONIBILIDADE DO INSTRUTOR
        $aula_instrutor = $db->fetch("
            SELECT a.*, COALESCE(u.nome, i.nome) as instrutor_nome 
            FROM aulas a 
            JOIN instrutores i ON a.instrutor_id = i.id 
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            WHERE a.instrutor_id = ? 
            AND a.data_aula = ? 
            AND a.status != 'cancelada'
            AND (
                (a.hora_inicio < ? AND a.hora_fim > ?) OR
                (a.hora_inicio < ? AND a.hora_fim > ?) OR
                (a.hora_inicio >= ? AND a.hora_fim <= ?)
            )
        ", [$instrutor_id, $data_aula, $hora_fim, $hora_inicio, $hora_inicio, $hora_fim, $hora_inicio, $hora_fim]);
        
        if ($aula_instrutor) {
            return [
                'valido' => false,
                'mensagem' => "❌ INSTRUTOR OCUPADO: O instrutor {$aula_instrutor['instrutor_nome']} já possui uma aula agendada no horário {$aula_instrutor['hora_inicio']} - {$aula_instrutor['hora_fim']} neste mesmo dia."
            ];
        }
        
        // 3. VALIDAR HORÁRIO DE TRABALHO DO INSTRUTOR
        $instrutor = $db->fetch("
            SELECT i.*, COALESCE(u.nome, i.nome) as instrutor_nome,
                   TIME(?) as hora_inicio_time,
                   TIME(?) as hora_fim_time
            FROM instrutores i 
            LEFT JOIN usuarios u ON i.usuario_id = u.id
            WHERE i.id = ?
        ", [$hora_inicio, $hora_fim, $instrutor_id]);
        
        if (!$instrutor) {
            return [
                'valido' => false,
                'mensagem' => "❌ INSTRUTOR INVÁLIDO: Instrutor não encontrado no sistema."
            ];
        }
        
        // Verificar horário de trabalho (assumindo que instrutores trabalham das 8h às 18h)
        $hora_inicio_time = new DateTime($hora_inicio);
        $hora_fim_time = new DateTime($hora_fim);
        $hora_trabalho_inicio = new DateTime('08:00:00');
        $hora_trabalho_fim = new DateTime('18:00:00');
        
        if ($hora_inicio_time < $hora_trabalho_inicio || $hora_fim_time > $hora_trabalho_fim) {
            return [
                'valido' => false,
                'mensagem' => "❌ FORA DO HORÁRIO DE TRABALHO: O instrutor {$instrutor['instrutor_nome']} trabalha das 08:00 às 18:00. Horário solicitado: {$hora_inicio} - {$hora_fim}"
            ];
        }
        
        // 4. VALIDAR DIA DA SEMANA DO INSTRUTOR (assumindo que trabalha de segunda a sexta)
        $dia_semana = date('w', strtotime($data_aula)); // 0 = domingo, 6 = sábado
        if ($dia_semana == 0 || $dia_semana == 6) {
            return [
                'valido' => false,
                'mensagem' => "❌ DIA DA SEMANA INVÁLIDO: O instrutor {$instrutor['instrutor_nome']} trabalha apenas de segunda a sexta-feira. Data solicitada: " . date('d/m/Y (l)', strtotime($data_aula))
            ];
        }
        
        // 5. VALIDAR VEÍCULO OCUPADO (apenas para aulas práticas)
        if ($tipo_aula === 'pratica' && $veiculo_id) {
            $aula_veiculo = $db->fetch("
                SELECT a.*, v.placa, v.modelo, v.marca 
                FROM aulas a 
                JOIN veiculos v ON a.veiculo_id = v.id
                WHERE a.veiculo_id = ? 
                AND a.data_aula = ? 
                AND a.status != 'cancelada'
                AND (
                    (a.hora_inicio < ? AND a.hora_fim > ?) OR
                    (a.hora_inicio < ? AND a.hora_fim > ?) OR
                    (a.hora_inicio >= ? AND a.hora_fim <= ?)
                )
            ", [$veiculo_id, $data_aula, $hora_fim, $hora_inicio, $hora_inicio, $hora_fim, $hora_inicio, $hora_fim]);
            
            if ($aula_veiculo) {
                return [
                    'valido' => false,
                    'mensagem' => "❌ VEÍCULO OCUPADO: O veículo {$aula_veiculo['marca']} {$aula_veiculo['modelo']} (Placa: {$aula_veiculo['placa']}) já está em uso no horário {$aula_veiculo['hora_inicio']} - {$aula_veiculo['hora_fim']} neste mesmo dia."
                ];
            }
        }
        
        // 6. VALIDAR SE ALUNO JÁ CUMPRIU TODAS AS HORAS NECESSÁRIAS
        $aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$aluno_id]);
        if (!$aluno) {
            return [
                'valido' => false,
                'mensagem' => "❌ ALUNO INVÁLIDO: Aluno não encontrado no sistema."
            ];
        }
        
        // Calcular horas já cumpridas pelo aluno
        $horas_cumpridas = $db->fetch("
            SELECT 
                SUM(TIMESTAMPDIFF(MINUTE, hora_inicio, hora_fim)) as total_minutos
            FROM aulas 
            WHERE aluno_id = ? 
            AND status IN ('concluida', 'em_andamento')
        ", [$aluno_id]);
        
        $total_horas_cumpridas = ($horas_cumpridas['total_minutos'] ?? 0) / 60;
        
        // Calcular horas necessárias baseado no tipo de habilitação
        $horas_necessarias = 20; // Padrão de 20 horas para todas as habilitações
        
        // Se o campo tipo_habilitacao existir, usar valores específicos
        if (isset($aluno['tipo_habilitacao'])) {
            switch ($aluno['tipo_habilitacao']) {
                case 'A':
                    $horas_necessarias = 20; // 20 horas para moto
                    break;
                case 'B':
                    $horas_necessarias = 20; // 20 horas para carro
                    break;
                case 'AB':
                    $horas_necessarias = 40; // 40 horas para carro + moto
                    break;
                case 'C':
                    $horas_necessarias = 40; // 40 horas para caminhão
                    break;
                case 'D':
                    $horas_necessarias = 40; // 40 horas para ônibus
                    break;
                default:
                    $horas_necessarias = 20; // padrão
            }
        }
        
        if ($total_horas_cumpridas >= $horas_necessarias) {
            return [
                'valido' => false,
                'mensagem' => "❌ HORAS JÁ CUMPRIDAS: O aluno {$aluno['nome']} já cumpriu todas as {$horas_necessarias}h necessárias" . (isset($aluno['tipo_habilitacao']) ? " para a habilitação tipo {$aluno['tipo_habilitacao']}" : "") . ". Horas cumpridas: " . number_format($total_horas_cumpridas, 1) . "h"
            ];
        }
        
        // 7. VALIDAÇÃO ADICIONAL: Verificar se não está agendando no passado
        $data_atual = date('Y-m-d');
        $hora_atual = date('H:i:s');
        
        if ($data_aula < $data_atual) {
            return [
                'valido' => false,
                'mensagem' => "❌ DATA NO PASSADO: Não é possível agendar aulas para datas anteriores à data atual ({$data_atual})."
            ];
        }
        
        if ($data_aula == $data_atual && $hora_inicio < $hora_atual) {
            return [
                'valido' => false,
                'mensagem' => "❌ HORÁRIO NO PASSADO: Não é possível agendar aulas para horários anteriores ao horário atual ({$hora_atual})."
            ];
        }
        
        // Se chegou até aqui, todas as validações passaram
        return [
            'valido' => true,
            'mensagem' => "✅ Agendamento validado com sucesso!"
        ];
        
    } catch (Exception $e) {
        return [
            'valido' => false,
            'mensagem' => "❌ ERRO DE VALIDAÇÃO: " . $e->getMessage()
        ];
    }
}

?>
