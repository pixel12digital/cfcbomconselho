<?php
/**
 * API de Exames - Sistema CFC
 * Endpoints para gestão de exames médico e psicotécnico
 */

// Configurar relatório de erros
error_reporting(E_ALL);
ini_set('display_errors', 0); // Não exibir erros na tela para não quebrar JSON
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/exames_api_errors.log');

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Limpar qualquer saída anterior
if (ob_get_level()) {
    ob_clean();
}

// Iniciar buffer de saída para capturar qualquer output inesperado
ob_start();

// Definir headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Função para retornar JSON de forma segura
function returnJsonResponse($data) {
    error_log("[EXAMES API] returnJsonResponse chamada com: " . print_r($data, true));
    
    // Limpar qualquer output anterior e parar qualquer buffer
    while (ob_get_level()) {
        ob_end_clean();
    }
    
    // Limpar qualquer saída anterior
    if (headers_sent()) {
        error_log("[EXAMES API] Headers já enviados, não é possível enviar JSON");
        return;
    }
    
    // Definir headers novamente para garantir
    header('Content-Type: application/json; charset=utf-8');
    
    $json = json_encode($data, JSON_UNESCAPED_UNICODE);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("[EXAMES API] Erro ao codificar JSON: " . json_last_error_msg());
        $json = json_encode([
            'success' => false,
            'error' => 'Erro ao codificar JSON: ' . json_last_error_msg(),
            'code' => 'JSON_ERROR'
        ], JSON_UNESCAPED_UNICODE);
    }
    
    error_log("[EXAMES API] JSON a ser enviado: " . $json);
    echo $json;
    exit;
}

// Verificar autenticação
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_log("[EXAMES API] Session ID: " . session_id());
error_log("[EXAMES API] Session data: " . print_r($_SESSION, true));
error_log("[EXAMES API] POST data: " . print_r($_POST, true));

if (!isset($_SESSION['user_id']) || !isLoggedIn()) {
    error_log("[EXAMES API] Usuário não autenticado");
    http_response_code(401);
    returnJsonResponse(['error' => 'Não autenticado', 'code' => 'UNAUTHORIZED']);
}

// Obter dados do usuário logado
$user = getCurrentUser();
$isAdmin = $user['tipo'] === 'admin';
$isSecretaria = $user['tipo'] === 'secretaria';
$isInstrutor = $user['tipo'] === 'instrutor';

// Verificar permissões
$canRead = $isAdmin || $isSecretaria || $isInstrutor;
$canWrite = $isAdmin || $isSecretaria;

if (!$canRead) {
    http_response_code(403);
    returnJsonResponse(['error' => 'Sem permissão para acessar exames', 'code' => 'FORBIDDEN']);
}

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

// Tratar requisições OPTIONS (preflight)
if ($method === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    error_log("[EXAMES API] Processando requisição - Método: $method");
    switch ($method) {
        case 'GET':
            error_log("[EXAMES API] Chamando handleGet");
            handleGet($db, $canWrite);
            break;
        case 'POST':
            if (!$canWrite) {
                http_response_code(403);
                returnJsonResponse(['error' => 'Sem permissão para criar exames', 'code' => 'FORBIDDEN']);
            }
            
            // Verificar ação específica
            $action = $_POST['action'] ?? 'create';
            
            switch ($action) {
                case 'create':
                    handlePost($db, $user);
                    break;
                case 'update':
                    handlePut($db, $user);
                    break;
                case 'delete':
                    handleDelete($db, $user);
                    break;
                default:
                    handlePost($db, $user);
                    break;
            }
            break;
        case 'PUT':
            if (!$canWrite) {
                http_response_code(403);
                returnJsonResponse(['error' => 'Sem permissão para editar exames', 'code' => 'FORBIDDEN']);
            }
            handlePut($db, $user);
            break;
        case 'DELETE':
            if (!$canWrite) {
                http_response_code(403);
                returnJsonResponse(['error' => 'Sem permissão para cancelar exames', 'code' => 'FORBIDDEN']);
            }
            handleDelete($db, $user);
            break;
        default:
            http_response_code(405);
            returnJsonResponse(['error' => 'Método não permitido', 'code' => 'METHOD_NOT_ALLOWED']);
    }
} catch (Exception $e) {
    error_log('[EXAMES API] Erro: ' . $e->getMessage());
    http_response_code(500);
    returnJsonResponse([
        'error' => 'Erro interno do servidor', 
        'code' => 'INTERNAL_ERROR',
        'message' => $e->getMessage()
    ]);
}

/**
 * GET - Listar exames
 */
function handleGet($db, $canWrite) {
    // Parâmetros de filtro
    $alunoId = $_GET['aluno_id'] ?? null;
    $tipo = $_GET['tipo'] ?? null;
    $status = $_GET['status'] ?? null;
    
    if (!$alunoId) {
        http_response_code(400);
        returnJsonResponse(['error' => 'ID do aluno é obrigatório', 'code' => 'MISSING_ALUNO_ID']);
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id, nome FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        http_response_code(404);
        returnJsonResponse(['error' => 'Aluno não encontrado', 'code' => 'ALUNO_NOT_FOUND']);
    }
    
    // Construir query
    $sql = "SELECT * FROM exames WHERE aluno_id = ?";
    $params = [$alunoId];
    
    if ($tipo) {
        $sql .= " AND tipo = ?";
        $params[] = $tipo;
    }
    
    if ($status) {
        $sql .= " AND status = ?";
        $params[] = $status;
    }
    
    $sql .= " ORDER BY tipo, data_agendada DESC";
    
    $exames = $db->fetchAll($sql, $params);
    
    // Adicionar informações do aluno
    $response = [
        'aluno' => $aluno,
        'exames' => $exames,
        'can_write' => $canWrite,
        'exames_ok' => calcularExamesOK($exames)
    ];
    
    returnJsonResponse($response);
}

/**
 * POST - Criar/agendar exame
 */
function handlePost($db, $user) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    // Validações obrigatórias
    $required = ['aluno_id', 'tipo', 'data_agendada'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            returnJsonResponse(['error' => "Campo '$field' é obrigatório", 'code' => 'MISSING_FIELD']);
        }
    }
    
    // Validar tipo
    if (!in_array($data['tipo'], ['medico', 'psicotecnico'])) {
        http_response_code(400);
        returnJsonResponse(['error' => 'Tipo deve ser "medico" ou "psicotecnico"', 'code' => 'INVALID_TIPO']);
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$data['aluno_id']]);
    if (!$aluno) {
        http_response_code(404);
        returnJsonResponse(['error' => 'Aluno não encontrado', 'code' => 'ALUNO_NOT_FOUND']);
    }
    
    // Verificar se já existe exame ativo do mesmo tipo
    $existing = $db->fetch("
        SELECT id, data_agendada, status, resultado FROM exames 
        WHERE aluno_id = ? AND tipo = ? AND status IN ('agendado', 'concluido')
    ", [$data['aluno_id'], $data['tipo']]);
    
    if ($existing) {
        $dataFormatada = date('d/m/Y', strtotime($existing['data_agendada']));
        $statusTexto = $existing['status'] === 'agendado' ? 'agendado' : 'concluído';
        $resultadoTexto = $existing['resultado'] ? " (Resultado: {$existing['resultado']})" : '';
        
        // Retornar mensagem amigável em vez de erro 409
        $tipoExameTexto = $data['tipo'] === 'medico' ? 'médico' : 'psicotécnico';
        
        returnJsonResponse([
            'success' => false,
            'message' => "⚠️ Já existe um exame {$tipoExameTexto} {$statusTexto} para este aluno na data {$dataFormatada}{$resultadoTexto}",
            'friendly_message' => "Não é possível agendar um novo exame {$tipoExameTexto} para este aluno, pois já existe um {$statusTexto}. Para agendar um novo exame, primeiro cancele o exame existente na lista de exames.",
            'code' => 'EXAME_EXISTS',
            'existing_exam' => [
                'id' => $existing['id'],
                'data_agendada' => $existing['data_agendada'],
                'status' => $existing['status'],
                'resultado' => $existing['resultado']
            ]
        ]);
    }
    
    // Preparar dados para inserção
    $exameData = [
        'aluno_id' => $data['aluno_id'],
        'tipo' => $data['tipo'],
        'status' => 'agendado',
        'resultado' => 'pendente',
        'clinica_nome' => $data['clinica_nome'] ?? null,
        'protocolo' => $data['protocolo'] ?? null,
        'data_agendada' => $data['data_agendada'],
        'observacoes' => $data['observacoes'] ?? null,
        'criado_por' => $user['id']
    ];
    
    $exameId = $db->insert('exames', $exameData);
    
    if ($exameId) {
        // Buscar exame criado
        $exame = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
        
        // Log de auditoria
        error_log("[EXAMES API] Exame criado - ID: $exameId, Tipo: {$data['tipo']}, Aluno: {$data['aluno_id']}, Usuário: {$user['id']}");
        
        http_response_code(201);
        returnJsonResponse([
            'success' => true,
            'message' => 'Exame agendado com sucesso',
            'exame' => $exame
        ]);
    } else {
        http_response_code(500);
        returnJsonResponse(['error' => 'Erro ao criar exame', 'code' => 'CREATE_ERROR']);
    }
}

/**
 * PUT - Atualizar exame (lançar resultado)
 */
function handlePut($db, $user) {
    $exameId = $_POST['exame_id'] ?? $_GET['id'] ?? null;
    
    if (!$exameId) {
        http_response_code(400);
        returnJsonResponse(['error' => 'ID do exame é obrigatório', 'code' => 'MISSING_ID']);
    }
    
    // Para ações via POST, usar $_POST diretamente
    $data = $_POST;
    
    // Se não há dados no POST, tentar json_decode
    if (empty($data)) {
        $data = json_decode(file_get_contents('php://input'), true);
    }
    
    // Verificar se exame existe
    $exame = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
    if (!$exame) {
        http_response_code(404);
        returnJsonResponse(['error' => 'Exame não encontrado', 'code' => 'EXAME_NOT_FOUND']);
    }
    
    // Preparar dados para atualização
    $updateData = [
        'atualizado_por' => $user['id']
    ];
    
    // Campos que podem ser atualizados
    $allowedFields = ['status', 'resultado', 'data_resultado', 'observacoes', 'anexos'];
    foreach ($allowedFields as $field) {
        if (isset($data[$field]) && $data[$field] !== '') {
            $updateData[$field] = $data[$field];
        }
    }
    
    // Validações
    if (isset($updateData['resultado']) && !in_array($updateData['resultado'], ['apto', 'inapto', 'inapto_temporario', 'pendente'])) {
        http_response_code(400);
        returnJsonResponse(['error' => 'Resultado deve ser "apto", "inapto", "inapto_temporario" ou "pendente"', 'code' => 'INVALID_RESULTADO']);
    }
    
    if (isset($updateData['status']) && !in_array($updateData['status'], ['agendado', 'concluido', 'cancelado'])) {
        http_response_code(400);
        returnJsonResponse(['error' => 'Status deve ser "agendado", "concluido" ou "cancelado"', 'code' => 'INVALID_STATUS']);
    }
    
    // Se lançando resultado, marcar como concluído
    if (isset($updateData['resultado']) && in_array($updateData['resultado'], ['apto', 'inapto', 'inapto_temporario'])) {
        $updateData['status'] = 'concluido';
        if (empty($updateData['data_resultado'])) {
            $updateData['data_resultado'] = date('Y-m-d');
        }
    }
    
    // Atualizar exame
    $success = $db->update('exames', $updateData, 'id = ?', [$exameId]);
    
    if ($success) {
        // Buscar exame atualizado
        $exameAtualizado = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
        
        // Log de auditoria
        error_log("[EXAMES API] Exame atualizado - ID: $exameId, Status: {$updateData['status']}, Resultado: {$updateData['resultado']}, Usuário: {$user['id']}");
        
        returnJsonResponse([
            'success' => true,
            'message' => 'Exame atualizado com sucesso',
            'exame' => $exameAtualizado
        ]);
    } else {
        http_response_code(500);
        returnJsonResponse(['error' => 'Erro ao atualizar exame', 'code' => 'UPDATE_ERROR']);
    }
}

/**
 * DELETE - Cancelar exame
 */
function handleDelete($db, $user) {
    $exameId = $_POST['exame_id'] ?? $_GET['id'] ?? null;
    
    if (!$exameId) {
        http_response_code(400);
        returnJsonResponse(['error' => 'ID do exame é obrigatório', 'code' => 'MISSING_ID']);
    }
    
    // Verificar se exame existe
    $exame = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
    if (!$exame) {
        http_response_code(404);
        returnJsonResponse(['error' => 'Exame não encontrado', 'code' => 'EXAME_NOT_FOUND']);
    }
    
    // Marcar como cancelado (soft delete)
    $success = $db->update('exames', [
        'status' => 'cancelado',
        'atualizado_por' => $user['id']
    ], 'id = ?', [$exameId]);
    
    if ($success) {
        // Log de auditoria
        error_log("[EXAMES API] Exame cancelado - ID: $exameId, Usuário: {$user['id']}");
        
        returnJsonResponse([
            'success' => true,
            'message' => 'Exame cancelado com sucesso'
        ]);
    } else {
        http_response_code(500);
        returnJsonResponse(['error' => 'Erro ao cancelar exame', 'code' => 'DELETE_ERROR']);
    }
}

/**
 * Calcular se exames estão OK (ambos aptos)
 */
function calcularExamesOK($exames) {
    $medico = null;
    $psicotecnico = null;
    
    foreach ($exames as $exame) {
        if ($exame['tipo'] === 'medico' && $exame['status'] === 'concluido') {
            $medico = $exame;
        }
        if ($exame['tipo'] === 'psicotecnico' && $exame['status'] === 'concluido') {
            $psicotecnico = $exame;
        }
    }
    
    // Exames OK = ambos aptos (inapto_temporario é considerado não apto para aulas teóricas)
    return ($medico && $medico['resultado'] === 'apto') && 
           ($psicotecnico && $psicotecnico['resultado'] === 'apto');
}
?>
