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
    
    $output = json_encode(['sucesso' => false, 'mensagem' => $message], JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'sucesso' => false, 
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
    
    $response = ['sucesso' => true, 'mensagem' => $message];
    if ($data !== null) {
        $response['dados'] = $data;
    }
    
    $output = json_encode($response, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'sucesso' => false, 
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
        echo json_encode(['sucesso' => false, 'mensagem' => 'Usuário não autenticado']);
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

/**
 * Calcular horários das aulas baseado no tipo de agendamento
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

/**
 * Criar uma nova aula
 */
function criarAula($data) {
    try {
        $db = db();
        
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
        
        // Validar duração fixa de 50 minutos
        if ($duracao != 50) {
            returnJsonError('A aula deve ter exatamente 50 minutos de duração', 400);
        }
        
        // Calcular horários baseados no tipo de agendamento
        $horarios_aulas = calcularHorariosAulas($hora_inicio, $tipo_agendamento, $posicao_intervalo);
        
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
        
        // Verificar conflitos de horário para todas as aulas do bloco
        foreach ($horarios_aulas as $aula) {
            $conflito_instrutor = $db->fetch("SELECT * FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada' AND (
                (hora_inicio <= ? AND hora_fim > ?) OR
                (hora_inicio < ? AND hora_fim >= ?) OR
                (hora_inicio >= ? AND hora_fim <= ?)
            )", [$instrutor_id, $data_aula, $aula['hora_inicio'], $aula['hora_inicio'], $aula['hora_fim'], $aula['hora_fim'], $aula['hora_inicio'], $aula['hora_fim']]);
            
            if ($conflito_instrutor) {
                returnJsonError("Instrutor já possui aula agendada no horário {$aula['hora_inicio']} - {$aula['hora_fim']}", 409);
            }
            
            // Verificar conflitos de veículo (se aplicável)
            if ($veiculo_id) {
                $conflito_veiculo = $db->fetch("SELECT * FROM aulas WHERE veiculo_id = ? AND data_aula = ? AND status != 'cancelada' AND (
                    (hora_inicio <= ? AND hora_fim > ?) OR
                    (hora_inicio < ? AND hora_fim >= ?) OR
                    (hora_inicio >= ? AND hora_fim <= ?)
                )", [$veiculo_id, $data_aula, $aula['hora_inicio'], $aula['hora_inicio'], $aula['hora_fim'], $aula['hora_fim'], $aula['hora_inicio'], $aula['hora_fim']]);
                
                if ($conflito_veiculo) {
                    returnJsonError("Veículo já está em uso no horário {$aula['hora_inicio']} - {$aula['hora_fim']}", 409);
                }
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

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        buscarAula();
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
        echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão inválida']);
        exit();
    }

    try {
        $db = db();
        
        // MODIFICAÇÃO: Usar php://input normalmente
        $input = file_get_contents('php://input');
        error_log("Input bruto: " . $input);
        $data = json_decode($input, true);
        error_log("Data decodificada: " . json_encode($data));
        
        error_log("Requisição recebida: " . json_encode($data));
        error_log("Usuário atual: " . $currentUser['email'] . " (Tipo: " . $currentUser['tipo'] . ")");
        
        if ($data && isset($data['acao'])) {
            $acao = $data['acao'];
            error_log("Ação detectada: " . $acao);
            
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
            
            criarAula([
                'aluno_id' => $aluno_id,
                'data_aula' => $data_aula,
                'hora_inicio' => $hora_inicio,
                'duracao' => $duracao,
                'tipo_aula' => $tipo_aula,
                'instrutor_id' => $instrutor_id,
                'veiculo_id' => $veiculo_id,
                'disciplina' => $disciplina,
                'observacoes' => $observacoes,
                'tipo_agendamento' => $tipo_agendamento,
                'posicao_intervalo' => $posicao_intervalo
            ]);
        }
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'sucesso' => false,
            'mensagem' => $e->getMessage(),
            'erro' => DEBUG_MODE ? $e->getTraceAsString() : null
        ]);
    }

} catch (Exception $e) {
    returnJsonError('Erro interno do servidor: ' . $e->getMessage(), 500);
} catch (Error $e) {
    returnJsonError('Erro fatal do sistema: ' . $e->getMessage(), 500);
}

/**
 * Cancelar uma aula
 */
function cancelarAula($aula_id) {
    if (!isset($_SESSION['user_id'])) {
        returnJsonError('Sessão não encontrada. Faça login novamente.', 401);
    }
    
    if (!isLoggedIn()) {
        returnJsonError('Usuário não autenticado. Faça login novamente.', 401);
    }
    
    if (!canCancelLessons()) {
        returnJsonError('Você não tem permissão para cancelar aulas', 403);
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
    
    returnJsonSuccess('Aula cancelada com sucesso');
}

/**
 * Editar uma aula
 */
function editarAula($aula_id, $data) {
    returnJsonError('Função de edição não implementada nesta versão', 501);
}

?>
