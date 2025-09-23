<?php
// VERSÃO DEBUG DA API - Usa variável global em vez de php://input
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('html_errors', 0);

// Limpar qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

// Buffer de saída para capturar qualquer output inesperado
ob_start();

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Função para retornar erro JSON de forma segura
function returnJsonError($message, $code = 500) {
    if (ob_get_level()) {
        ob_clean();
    }
    
    http_response_code($code);
    
    $output = json_encode(['success' => false, 'mensagem' => $message], JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'success' => false, 
            'mensagem' => 'Erro ao codificar JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    echo $output;
    exit();
}

// Função para retornar sucesso JSON de forma segura
function returnJsonSuccess($message, $data = null) {
    if (ob_get_level()) {
        ob_clean();
    }
    
    $response = ['success' => true, 'mensagem' => $message];
    if ($data !== null) {
        $response['dados'] = $data;
    }
    
    $output = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'success' => false, 
            'mensagem' => 'Erro ao codificar JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    echo $output;
    exit();
}

/**
 * Buscar aulas para exibir no calendário
 */
function buscarAulas() {
    if (!isset($_SESSION['user_id'])) {
        session_start();
    }
    if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
        echo json_encode(['success' => false, 'mensagem' => 'Usuário não autenticado']);
    exit();
}

try {
    $db = db();
    
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
        'success' => true,
            'dados' => $aulas,
            'total' => count($aulas)
    ]);
    
} catch (Exception $e) {
        http_response_code(500);
    echo json_encode([
        'success' => false,
            'mensagem' => 'Erro ao buscar aulas: ' . $e->getMessage(),
        'erro' => DEBUG_MODE ? $e->getTraceAsString() : null
    ]);
    }
}

/**
 * Calcular horários das aulas baseado no tipo de agendamento
 */
 function calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo = 'depois') {
    $horarios = [];
    
    // Garantir que a hora tenha formato HH:MM:SS
    if (strlen($hora_inicio) === 5) {
        $hora_inicio .= ':00';
    }
    
    error_log("Hora de início formatada: $hora_inicio");
    
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

/**
 * Criar uma nova aula
 */
function criarAula($data) {
    error_log("=== INÍCIO DA FUNÇÃO CRIAR AULA ===");
    error_log("Dados recebidos na função: " . json_encode($data));
    try {
        $db = db();
        
        // Verificar permissões
        $permissions = new AgendamentoPermissions();
        $permCriar = $permissions->podeCriarAgendamento();
        if (!$permCriar['permitido']) {
            returnJsonError($permCriar['motivo'], 403);
        }
        
        // Validar dados obrigatórios
        $aluno_id = $data['aluno_id'] ?? null;
        $data_aula = $data['data_aula'] ?? null;
        $hora_inicio = $data['hora_inicio'] ?? null;
        $duracao = $data['duracao'] ?? 50;
        $tipo_aula = $data['tipo_aula'] ?? null;
        $instrutor_id = $data['instrutor_id'] ?? null;
        $veiculo_id = $data['veiculo_id'] ?? null;
        $disciplina = $data['disciplina'] ?? null;
        $observacoes = $data['observacoes'] ?? '';
        $tipo_agendamento = $data['tipo_agendamento'] ?? 'unica';
        $posicao_intervalo = $data['posicao_intervalo'] ?? 'depois';
        
        // Validar dados obrigatórios
        if (!$aluno_id || !$data_aula || !$hora_inicio || !$tipo_aula || !$instrutor_id) {
            returnJsonError('Todos os campos obrigatórios devem ser preenchidos', 400);
        }
        
        // Validar disciplina para aulas teóricas
        if ($tipo_aula === 'teorica' && !$disciplina) {
            returnJsonError('Disciplina é obrigatória para aulas teóricas', 400);
        }
        
        // Validar veículo para aulas práticas
        if ($tipo_aula !== 'teorica' && !$veiculo_id) {
            returnJsonError('Veículo é obrigatório para aulas práticas', 400);
        }
        
        // Validar duração fixa de 50 minutos (se fornecida)
        if ($duracao && $duracao != 50) {
            returnJsonError('A aula deve ter exatamente 50 minutos de duração', 400);
        }
        
        // Se duração não foi fornecida, usar 50 minutos como padrão
        if (!$duracao) {
            $duracao = 50;
        }
        
        // Calcular horários baseados no tipo de agendamento
        error_log("Calculando horários para: $hora_inicio, $tipo_agendamento, $posicao_intervalo");
        $horarios_aulas = calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo);
        error_log("Horários calculados: " . json_encode($horarios_aulas));
        
        // Buscar informações do aluno e CFC
        $aluno = $db->fetch("SELECT a.*, c.id as cfc_id FROM alunos a JOIN cfcs c ON a.cfc_id = c.id WHERE a.id = ?", [$aluno_id]);
        if (!$aluno) {
            returnJsonError('Aluno não encontrado', 404);
        }
        
        // Verificar se instrutor existe e está ativo
        $instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ? AND ativo = 1", [$instrutor_id]);
        if (!$instrutor) {
            returnJsonError('Instrutor não encontrado ou inativo', 404);
        }
        
        // Verificar se veículo existe e está disponível (se aplicável)
        if ($veiculo_id) {
            $veiculo = $db->fetch("SELECT * FROM veiculos WHERE id = ? AND ativo = 1", [$veiculo_id]);
            if (!$veiculo) {
                returnJsonError('Veículo não encontrado ou inativo', 404);
            }
        }
        
        // Usar sistema de guardas para validação completa
        $guards = new AgendamentoGuards();
        
        // Verificar cada aula do bloco
        foreach ($horarios_aulas as $index => $aula) {
            $dadosAula = [
                'aluno_id' => $aluno_id,
                'instrutor_id' => $instrutor_id,
                'veiculo_id' => $veiculo_id,
                'tipo_aula' => $tipo_aula,
                'data_aula' => $data_aula,
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim'],
                'disciplina' => $disciplina,
                'observacoes' => $observacoes
            ];
            
            $validacao = $guards->validarAgendamentoCompleto($dadosAula);
            if (!$validacao['valido']) {
                returnJsonError($validacao['motivo'], 409);
            }
        }
        
        // Inserir múltiplas aulas no banco
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
                
            // Registrar auditoria usando o sistema de auditoria
            $auditoria = new AgendamentoAuditoria();
            $dadosAulaAuditoria = [
                'aluno_id' => $aluno_id,
                'instrutor_id' => $instrutor_id,
                'tipo_aula' => $tipo_aula,
                'data_aula' => $data_aula,
                'hora_inicio' => $aula['hora_inicio'],
                'hora_fim' => $aula['hora_fim'],
                'veiculo_id' => $veiculo_id,
                'disciplina' => $disciplina,
                'observacoes' => $observacoes . ($index > 0 ? " (Aula " . ($index + 1) . " do bloco)" : "")
            ];
            $auditoria->registrarCriacao($aula_id, $dadosAulaAuditoria);
            
            // Enviar notificações
            $notificacoes = new SistemaNotificacoes();
            $notificacoes->notificarAgendamentoCriado($aula_id, $dadosAulaAuditoria);
            } else {
                returnJsonError('Erro ao salvar aula ' . ($index + 1) . ' no banco de dados', 500);
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
        
        returnJsonSuccess($mensagem_sucesso, [
            'aulas_criadas' => $aulas_criadas,
            'tipo_agendamento' => $tipo_agendamento,
            'aluno' => $aluno['nome'],
            'instrutor' => $instrutor['credencial'],
            'data' => $data_aula,
            'total_aulas' => count($aulas_criadas),
            'tipo' => ucfirst($tipo_aula)
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao criar aula: " . $e->getMessage());
        returnJsonError('Erro interno do servidor: ' . $e->getMessage(), 500);
    }
}

try {
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/guards/AgendamentoGuards.php';
require_once __DIR__ . '/../../includes/guards/AgendamentoPermissions.php';
require_once __DIR__ . '/../../includes/guards/AgendamentoAuditoria.php';
require_once __DIR__ . '/../../includes/services/SistemaNotificacoes.php';

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        buscarAulas();
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        returnJsonError('Método não permitido', 405);
    }

    if (!isset($_SESSION['user_id'])) {
        session_start();
    }

    if (!isLoggedIn()) {
        returnJsonError('Usuário não autenticado', 401);
    }

    $currentUser = getCurrentUser();
    if (!$currentUser) {
        http_response_code(401);
        echo json_encode(['success' => false, 'mensagem' => 'Sessão inválida']);
        exit();
    }

    try {
        $db = db();
        
        // Limpar buffer antes de ler input
        if (ob_get_level()) {
            ob_clean();
        }
        
        // Verificar se é FormData ou JSON
        $data = null;
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'multipart/form-data') !== false) {
            // É FormData - usar $_POST
            error_log("Recebido FormData: " . json_encode($_POST));
            $data = $_POST;
        } else {
            // É JSON - usar php://input
            $input = file_get_contents('php://input');
            error_log("Input bruto: " . $input);
            $data = json_decode($input, true);
            error_log("Data decodificada: " . json_encode($data));
        }
        
        error_log("Requisição recebida: " . json_encode($data));
        error_log("Usuário atual: " . $currentUser['email'] . " (Tipo: " . $currentUser['tipo'] . ")");
        
        if ($data && isset($data['acao'])) {
            error_log("Ação detectada: " . $data['acao']);
            $acao = $data['acao'];
            error_log("Ação detectada: " . $acao);
            
            if ($acao === 'criar' && !canAddLessons()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'mensagem' => 'Apenas administradores e atendentes podem adicionar aulas']);
                exit();
            }
            
            if (($acao === 'editar' || $acao === 'cancelar') && !canEditLessons()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'mensagem' => 'Você não tem permissão para editar aulas']);
                exit();
            }
            
            if ($acao === 'cancelar' && isset($data['aula_id'])) {
                error_log("Executando cancelamento para aula_id: " . $data['aula_id']);
                cancelarAula($data['aula_id']);
                exit();
            }
            
            if ($acao === 'editar' && isset($data['aula_id'])) {
                editarAula($data['aula_id'], $data);
                exit();
            }
            
            if ($acao === 'criar') {
                criarAula($data);
                exit();
            }
            
            // Se chegou até aqui, é uma ação de criação via JSON
            $aluno_id = $data['aluno_id'] ?? null;
            $data_aula = $data['data_aula'] ?? null;
            $hora_inicio = $data['hora_inicio'] ?? null;
            $duracao = $data['duracao'] ?? null;
            $tipo_aula = $data['tipo_aula'] ?? null;
            $instrutor_id = $data['instrutor_id'] ?? null;
            $veiculo_id = $data['veiculo_id'] ?? null;
            $disciplina = $data['disciplina'] ?? null;
            $observacoes = $data['observacoes'] ?? '';
            $tipo_agendamento = $data['tipo_agendamento'] ?? 'unica';
            $posicao_intervalo = $data['posicao_intervalo'] ?? 'depois';
        } else {
            error_log("Nenhuma ação detectada, assumindo criação de aula");
            // Assumir que é uma criação de aula
            $acao = 'criar';
            
            error_log("Chamando criarAula com dados recebidos");
            criarAula($data);
        }
        
    } catch (Exception $e) {
        returnJsonError($e->getMessage(), 400);
    }

} catch (Exception $e) {
    returnJsonError('Erro interno do servidor: ' . $e->getMessage(), 500);
} catch (Error $e) {
    returnJsonError('Erro fatal do sistema: ' . $e->getMessage(), 500);
}

/**
 * Cancelar uma aula
 */
function cancelarAula($aula_id, $motivo = '') {
    if (!isset($_SESSION['user_id'])) {
        returnJsonError('Sessão não encontrada. Faça login novamente.', 401);
    }
    
    if (!isLoggedIn()) {
        returnJsonError('Usuário não autenticado. Faça login novamente.', 401);
    }
    
    // Verificar permissões usando o sistema de permissões
    $permissions = new AgendamentoPermissions();
    $permCancelar = $permissions->podeCancelarAgendamento($aula_id);
    if (!$permCancelar['permitido']) {
        returnJsonError($permCancelar['motivo'], 403);
    }
    
    $db = db();
    
    $aula = $db->fetch("SELECT * FROM aulas WHERE id = ? AND status = 'agendada'", [$aula_id]);
    if (!$aula) {
        returnJsonError('Aula não encontrada ou já não está agendada', 404);
    }
    
    $result = $db->query("UPDATE aulas SET status = 'cancelada', atualizado_em = NOW() WHERE id = ?", [$aula_id]);
    if (!$result) {
        returnJsonError('Erro ao cancelar aula no banco de dados', 500);
    }
    
        // Registrar auditoria do cancelamento
        $auditoria = new AgendamentoAuditoria();
        $auditoria->registrarCancelamento($aula_id, $aula, $motivo);
        
        // Enviar notificações
        $notificacoes = new SistemaNotificacoes();
        $notificacoes->notificarAgendamentoCancelado($aula_id, $aula, $motivo);
    
    returnJsonSuccess('Aula cancelada com sucesso');
}

/**
 * Verifica limite diário do ALUNO (máximo 3 aulas práticas por dia)
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
 * Editar uma aula
 */
function editarAula($aula_id, $data) {
    error_log("=== INÍCIO DA FUNÇÃO EDITAR AULA ===");
    error_log("Aula ID: {$aula_id}");
    error_log("Dados recebidos na função: " . json_encode($data));
    error_log("Dados POST: " . json_encode($_POST));
    error_log("Dados REQUEST: " . json_encode($_REQUEST));
    
    try {
        $db = db();
        
        // Verificar se a aula existe
        $aula_existente = $db->fetch("SELECT * FROM aulas WHERE id = ?", [$aula_id]);
        if (!$aula_existente) {
            error_log("Aula não encontrada: {$aula_id}");
            returnJsonError('Aula não encontrada', 404);
        }
        
        error_log("Aula existente: " . json_encode($aula_existente));
        
        // Verificar se a aula pode ser editada (apenas aulas agendadas)
        if ($aula_existente['status'] !== 'agendada') {
            error_log("Aula não pode ser editada - status: {$aula_existente['status']}");
            returnJsonError('Apenas aulas agendadas podem ser editadas', 400);
        }
        
        // Validar dados obrigatórios
        $aluno_id = $data['aluno_id'] ?? $aula_existente['aluno_id'];
        $data_aula = $data['data_aula'] ?? $aula_existente['data_aula'];
        $hora_inicio = $data['hora_inicio'] ?? $aula_existente['hora_inicio'];
        $duracao = $data['duracao'] ?? 50;
        $tipo_aula = $data['tipo_aula'] ?? $aula_existente['tipo_aula'];
        $instrutor_id = $data['instrutor_id'] ?? $aula_existente['instrutor_id'];
        $veiculo_id = $data['veiculo_id'] ?? $aula_existente['veiculo_id'];
        $disciplina = $data['disciplina'] ?? $aula_existente['disciplina'];
        $observacoes = $data['observacoes'] ?? $aula_existente['observacoes'];
        
        error_log("Dados processados - aluno_id: {$aluno_id}, data_aula: {$data_aula}, hora_inicio: {$hora_inicio}, instrutor_id: {$instrutor_id}, veiculo_id: {$veiculo_id}");
        
        // Validar dados obrigatórios
        if (!$aluno_id || !$data_aula || !$hora_inicio || !$tipo_aula || !$instrutor_id) {
            error_log("Dados obrigatórios faltando");
            returnJsonError('Todos os campos obrigatórios devem ser preenchidos', 400);
        }
        
        // Validar disciplina para aulas teóricas
        if ($tipo_aula === 'teorica' && !$disciplina) {
            returnJsonError('Disciplina é obrigatória para aulas teóricas', 400);
        }
        
        // Validar veículo para aulas práticas
        if ($tipo_aula !== 'teorica' && !$veiculo_id) {
            returnJsonError('Veículo é obrigatório para aulas práticas', 400);
        }
        
        // Calcular hora de fim
        $hora_fim = date('H:i:s', strtotime($hora_inicio . ' + ' . $duracao . ' minutes'));
        
        // Verificar se houve mudança de horário/instrutor/veículo
        $mudou_horario = ($hora_inicio !== $aula_existente['hora_inicio']) || 
                        ($hora_fim !== $aula_existente['hora_fim']) ||
                        ($data_aula !== $aula_existente['data_aula']);
        $mudou_instrutor = ($instrutor_id !== $aula_existente['instrutor_id']);
        $mudou_veiculo = ($veiculo_id !== $aula_existente['veiculo_id']);
        
        error_log("Mudanças detectadas - horário: " . ($mudou_horario ? 'SIM' : 'NÃO') . ", instrutor: " . ($mudou_instrutor ? 'SIM' : 'NÃO') . ", veículo: " . ($mudou_veiculo ? 'SIM' : 'NÃO'));
        
        // Se mudou horário, instrutor ou veículo, verificar conflitos
        if ($mudou_horario || $mudou_instrutor || $mudou_veiculo) {
            error_log("Verificando conflitos...");
            
            // Verificar conflitos de instrutor
            $conflito_instrutor = $db->fetch("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' AND id != ? AND (
                (hora_inicio <= ? AND hora_fim > ?) OR
                (hora_inicio < ? AND hora_fim >= ?) OR
                (hora_inicio >= ? AND hora_fim <= ?)
            )", [$instrutor_id, $data_aula, $aula_id, $hora_inicio, $hora_inicio, $hora_fim, $hora_fim, $hora_inicio, $hora_fim]);
            
            if ($conflito_instrutor) {
                error_log("Conflito de instrutor detectado: " . json_encode($conflito_instrutor));
                // Buscar nome do instrutor para mensagem mais específica
                $nome_instrutor = $db->fetchColumn("SELECT COALESCE(u.nome, i.nome) FROM instrutores i LEFT JOIN usuarios u ON i.usuario_id = u.id WHERE i.id = ?", [$instrutor_id]);
                returnJsonError("👨‍🏫 INSTRUTOR INDISPONÍVEL: O instrutor {$nome_instrutor} já possui aula agendada no horário {$hora_inicio} às {$hora_fim}. Escolha outro horário ou instrutor.", 409);
            }
            
            // Verificar conflitos de veículo (se aplicável)
            if ($veiculo_id) {
                $conflito_veiculo = $db->fetch("SELECT * FROM aulas WHERE veiculo_id = ? AND data_aula = ? AND status != 'cancelada' AND id != ? AND (
                    (hora_inicio <= ? AND hora_fim > ?) OR
                    (hora_inicio < ? AND hora_fim >= ?) OR
                    (hora_inicio >= ? AND hora_fim <= ?)
                )", [$veiculo_id, $data_aula, $aula_id, $hora_inicio, $hora_inicio, $hora_fim, $hora_fim, $hora_inicio, $hora_fim]);
                
                if ($conflito_veiculo) {
                    error_log("Conflito de veículo detectado: " . json_encode($conflito_veiculo));
                    // Buscar informações do veículo para mensagem mais específica
                    $info_veiculo = $db->fetch("SELECT placa, modelo, marca FROM veiculos WHERE id = ?", [$veiculo_id]);
                    $veiculo_info = "{$info_veiculo['marca']} {$info_veiculo['modelo']} - {$info_veiculo['placa']}";
                    returnJsonError("🚗 VEÍCULO INDISPONÍVEL: O veículo {$veiculo_info} já está em uso no horário {$hora_inicio} às {$hora_fim}. Escolha outro horário ou veículo.", 409);
                }
            }
            
            // Verificar limite de aulas práticas por dia para alunos (se mudou para aula prática)
            if ($tipo_aula === 'pratica' && $mudou_horario) {
                $limite_aluno = verificarLimiteDiarioAluno($db, $aluno_id, $data_aula, 1);
                if (!$limite_aluno['disponivel']) {
                    returnJsonError($limite_aluno['mensagem'], 409);
                }
            }
        }
        
        // Buscar informações do aluno e CFC
        $aluno = $db->fetch("SELECT a.*, c.id as cfc_id FROM alunos a JOIN cfcs c ON a.cfc_id = c.id WHERE a.id = ?", [$aluno_id]);
        if (!$aluno) {
            returnJsonError('Aluno não encontrado', 404);
        }
        
        // Verificar se instrutor existe e está ativo
        $instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ? AND ativo = 1", [$instrutor_id]);
        if (!$instrutor) {
            returnJsonError('Instrutor não encontrado ou inativo', 404);
        }
        
        // Verificar se veículo existe e está disponível (se aplicável)
        if ($veiculo_id) {
            $veiculo = $db->fetch("SELECT * FROM veiculos WHERE id = ? AND ativo = 1", [$veiculo_id]);
            if (!$veiculo) {
                returnJsonError('Veículo não encontrado ou inativo', 404);
            }
        }
        
        // Atualizar a aula
        $aula_data = [
            'aluno_id' => $aluno_id,
            'instrutor_id' => $instrutor_id,
            'cfc_id' => $aluno['cfc_id'],
            'veiculo_id' => $veiculo_id,
            'tipo_aula' => $tipo_aula,
            'disciplina' => $disciplina,
            'data_aula' => $data_aula,
            'hora_inicio' => $hora_inicio,
            'hora_fim' => $hora_fim,
            'observacoes' => $observacoes,
            'atualizado_em' => date('Y-m-d H:i:s')
        ];
        
        error_log("Dados para atualização: " . json_encode($aula_data));
        $resultado_update = $db->update('aulas', $aula_data, 'id = ?', [$aula_id]);
        error_log("Resultado do update: " . ($resultado_update ? 'SUCESSO' : 'FALHA'));
        
        error_log("Aula {$aula_id} atualizada com sucesso");
        
        returnJsonSuccess('Aula atualizada com sucesso!', [
            'aula_id' => $aula_id,
            'aluno_nome' => $aluno['nome'],
            'instrutor_nome' => $instrutor['nome'],
            'data_aula' => $data_aula,
            'hora_inicio' => $hora_inicio,
            'hora_fim' => $hora_fim,
            'tipo_aula' => $tipo_aula
        ]);
        
    } catch (Exception $e) {
        error_log("Erro ao editar aula: " . $e->getMessage());
        returnJsonError('Erro ao editar aula: ' . $e->getMessage(), 500);
    }
}

?>
