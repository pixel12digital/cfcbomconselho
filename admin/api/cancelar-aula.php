<?php
// =====================================================
// API DE CANCELAMENTO DE AULAS - SISTEMA CFC
// =====================================================

// Configurar tratamento de erros para API
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na tela
ini_set('log_errors', 1); // Logar erros no arquivo de log
ini_set('html_errors', 0); // Desabilitar formatação HTML de erros

// Limpar qualquer output anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Função para garantir resposta JSON válida
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    
    // Limpar qualquer saída anterior
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Garantir que não há saída antes do JSON
    $output = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'success' => false, 
            'message' => 'Erro ao codificar JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    echo $output;
    exit;
}

// Usar caminho relativo que sabemos que funciona
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar método HTTP
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse(['success' => false, 'message' => 'Método não permitido'], 405);
}

// Verificar autenticação
if (!isset($_SESSION['user_id'])) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    sendJsonResponse(['success' => false, 'message' => 'Usuário não autenticado'], 401);
}

try {
    $db = db();
    
    // Debug: Log dos dados recebidos
    error_log("DEBUG CANCELAR: Dados POST recebidos: " . print_r($_POST, true));
    error_log("DEBUG CANCELAR: Método: " . $_SERVER['REQUEST_METHOD']);
    error_log("DEBUG CANCELAR: Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'não definido'));
    
    // Receber dados do formulário
    $aula_id = $_POST['aula_id'] ?? null;
    $motivo_cancelamento = $_POST['motivo_cancelamento'] ?? '';
    $observacoes = $_POST['observacoes'] ?? '';
    
    error_log("DEBUG CANCELAR: aula_id = " . ($aula_id ?? 'null'));
    error_log("DEBUG CANCELAR: motivo_cancelamento = " . $motivo_cancelamento);
    error_log("DEBUG CANCELAR: observacoes = " . $observacoes);
    
    // Validar dados obrigatórios
    if (!$aula_id) {
        error_log("ERRO CANCELAR: ID da aula não fornecido");
        throw new Exception('ID da aula é obrigatório');
    }
    
    // Buscar dados da aula
    $aula = $db->fetch("
        SELECT a.*, 
               al.nome as aluno_nome,
               i.nome as instrutor_nome,
               v.placa as veiculo_placa
        FROM aulas a
        LEFT JOIN alunos al ON a.aluno_id = al.id
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.id = ? AND a.status != 'cancelada'
    ", [$aula_id]);
    
    if (!$aula) {
        throw new Exception('Aula não encontrada ou já cancelada');
    }
    
    // Verificar se a aula pode ser cancelada
    $data_aula = strtotime($aula['data_aula']);
    $hora_aula = strtotime($aula['hora_inicio']);
    $agora = time();
    
    // Calcular tempo até a aula (em horas)
    $tempo_ate_aula = ($data_aula + $hora_aula) - $agora;
    $horas_ate_aula = $tempo_ate_aula / 3600;
    
    // Regras de cancelamento
    if ($horas_ate_aula < 2) {
        throw new Exception('Aula só pode ser cancelada com pelo menos 2 horas de antecedência');
    }
    
    if ($aula['status'] === 'concluida') {
        throw new Exception('Aula já foi concluída e não pode ser cancelada');
    }
    
    if ($aula['status'] === 'em_andamento') {
        throw new Exception('Aula em andamento não pode ser cancelada');
    }
    
    // Cancelar a aula
    $observacoes_cancelamento = $aula['observacoes'] . "\n\n[CANCELADA] Motivo: " . $motivo_cancelamento . " - Data: " . date('d/m/Y H:i:s');
    
    $result = $db->query("
        UPDATE aulas 
        SET status = 'cancelada', 
            observacoes = ?, 
            atualizado_em = NOW()
        WHERE id = ?
    ", [$observacoes_cancelamento, $aula_id]);
    
    if (!$result) {
        throw new Exception('Erro ao cancelar aula');
    }
    
    // Log da ação
    if (LOG_ENABLED) {
        error_log("Aula cancelada - ID: {$aula_id}, Aluno: {$aula['aluno_nome']}, Instrutor: {$aula['instrutor_nome']}, Motivo: {$motivo_cancelamento}");
    }
    
    // Resposta de sucesso
    sendJsonResponse([
        'success' => true,
        'message' => 'Aula cancelada com sucesso',
        'data' => [
            'aula_id' => $aula_id,
            'aluno' => $aula['aluno_nome'],
            'instrutor' => $aula['instrutor_nome'],
            'data' => date('d/m/Y', strtotime($aula['data_aula'])),
            'hora' => $aula['hora_inicio'],
            'tipo' => ucfirst($aula['tipo_aula']),
            'motivo' => $motivo_cancelamento
        ]
    ]);
    
} catch (Exception $e) {
    sendJsonResponse([
        'success' => false,
        'message' => $e->getMessage(),
        'error' => DEBUG_MODE ? $e->getTraceAsString() : null
    ], 400);
}
?>
