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
 * Criar uma nova aula (versão simplificada)
 */
function criarAula($data) {
    returnJsonError('Função de criação de aulas não implementada nesta versão', 501);
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
        
        // MODIFICAÇÃO: Usar variável global em vez de php://input
        $input = $GLOBALS['php_input_data'] ?? '';
        $data = json_decode($input, true);
        
        error_log("Requisição recebida: " . json_encode($data));
        error_log("Usuário atual: " . $currentUser['email'] . " (Tipo: " . $currentUser['tipo'] . ")");
        
        if ($data && isset($data['acao'])) {
            $acao = $data['acao'];
            
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
