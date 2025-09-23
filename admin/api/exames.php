<?php
/**
 * API de Exames - Sistema CFC
 * Endpoints para gestão de exames médico e psicotécnico
 */

require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado', 'code' => 'UNAUTHORIZED']);
    exit;
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
    echo json_encode(['error' => 'Sem permissão para acessar exames', 'code' => 'FORBIDDEN']);
    exit;
}

$db = db();
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($method) {
        case 'GET':
            handleGet($db, $canWrite);
            break;
        case 'POST':
            if (!$canWrite) {
                http_response_code(403);
                echo json_encode(['error' => 'Sem permissão para criar exames', 'code' => 'FORBIDDEN']);
                exit;
            }
            handlePost($db, $user);
            break;
        case 'PUT':
            if (!$canWrite) {
                http_response_code(403);
                echo json_encode(['error' => 'Sem permissão para editar exames', 'code' => 'FORBIDDEN']);
                exit;
            }
            handlePut($db, $user);
            break;
        case 'DELETE':
            if (!$canWrite) {
                http_response_code(403);
                echo json_encode(['error' => 'Sem permissão para cancelar exames', 'code' => 'FORBIDDEN']);
                exit;
            }
            handleDelete($db, $user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Método não permitido', 'code' => 'METHOD_NOT_ALLOWED']);
    }
} catch (Exception $e) {
    error_log('[EXAMES API] Erro: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno do servidor', 'code' => 'INTERNAL_ERROR']);
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
        echo json_encode(['error' => 'ID do aluno é obrigatório', 'code' => 'MISSING_ALUNO_ID']);
        return;
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id, nome FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        http_response_code(404);
        echo json_encode(['error' => 'Aluno não encontrado', 'code' => 'ALUNO_NOT_FOUND']);
        return;
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
    
    echo json_encode($response);
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
            echo json_encode(['error' => "Campo '$field' é obrigatório", 'code' => 'MISSING_FIELD']);
            return;
        }
    }
    
    // Validar tipo
    if (!in_array($data['tipo'], ['medico', 'psicotecnico'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Tipo deve ser "medico" ou "psicotecnico"', 'code' => 'INVALID_TIPO']);
        return;
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$data['aluno_id']]);
    if (!$aluno) {
        http_response_code(404);
        echo json_encode(['error' => 'Aluno não encontrado', 'code' => 'ALUNO_NOT_FOUND']);
        return;
    }
    
    // Verificar se já existe exame ativo do mesmo tipo
    $existing = $db->fetch("
        SELECT id FROM exames 
        WHERE aluno_id = ? AND tipo = ? AND status IN ('agendado', 'concluido')
    ", [$data['aluno_id'], $data['tipo']]);
    
    if ($existing) {
        http_response_code(409);
        echo json_encode(['error' => "Já existe exame {$data['tipo']} ativo para este aluno", 'code' => 'EXAME_EXISTS']);
        return;
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
        echo json_encode([
            'success' => true,
            'message' => 'Exame agendado com sucesso',
            'exame' => $exame
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao criar exame', 'code' => 'CREATE_ERROR']);
    }
}

/**
 * PUT - Atualizar exame (lançar resultado)
 */
function handlePut($db, $user) {
    $exameId = $_GET['id'] ?? null;
    
    if (!$exameId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID do exame é obrigatório', 'code' => 'MISSING_ID']);
        return;
    }
    
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data) {
        $data = $_POST;
    }
    
    // Verificar se exame existe
    $exame = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
    if (!$exame) {
        http_response_code(404);
        echo json_encode(['error' => 'Exame não encontrado', 'code' => 'EXAME_NOT_FOUND']);
        return;
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
    if (isset($updateData['resultado']) && !in_array($updateData['resultado'], ['apto', 'inapto', 'pendente'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Resultado deve ser "apto", "inapto" ou "pendente"', 'code' => 'INVALID_RESULTADO']);
        return;
    }
    
    if (isset($updateData['status']) && !in_array($updateData['status'], ['agendado', 'concluido', 'cancelado'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Status deve ser "agendado", "concluido" ou "cancelado"', 'code' => 'INVALID_STATUS']);
        return;
    }
    
    // Se lançando resultado, marcar como concluído
    if (isset($updateData['resultado']) && in_array($updateData['resultado'], ['apto', 'inapto'])) {
        $updateData['status'] = 'concluido';
        if (empty($updateData['data_resultado'])) {
            $updateData['data_resultado'] = date('Y-m-d');
        }
    }
    
    // Atualizar exame
    $success = $db->update('exames', $updateData, ['id' => $exameId]);
    
    if ($success) {
        // Buscar exame atualizado
        $exameAtualizado = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
        
        // Log de auditoria
        error_log("[EXAMES API] Exame atualizado - ID: $exameId, Status: {$updateData['status']}, Resultado: {$updateData['resultado']}, Usuário: {$user['id']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Exame atualizado com sucesso',
            'exame' => $exameAtualizado
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao atualizar exame', 'code' => 'UPDATE_ERROR']);
    }
}

/**
 * DELETE - Cancelar exame
 */
function handleDelete($db, $user) {
    $exameId = $_GET['id'] ?? null;
    
    if (!$exameId) {
        http_response_code(400);
        echo json_encode(['error' => 'ID do exame é obrigatório', 'code' => 'MISSING_ID']);
        return;
    }
    
    // Verificar se exame existe
    $exame = $db->fetch("SELECT * FROM exames WHERE id = ?", [$exameId]);
    if (!$exame) {
        http_response_code(404);
        echo json_encode(['error' => 'Exame não encontrado', 'code' => 'EXAME_NOT_FOUND']);
        return;
    }
    
    // Marcar como cancelado (soft delete)
    $success = $db->update('exames', [
        'status' => 'cancelado',
        'atualizado_por' => $user['id']
    ], ['id' => $exameId]);
    
    if ($success) {
        // Log de auditoria
        error_log("[EXAMES API] Exame cancelado - ID: $exameId, Usuário: {$user['id']}");
        
        echo json_encode([
            'success' => true,
            'message' => 'Exame cancelado com sucesso'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erro ao cancelar exame', 'code' => 'DELETE_ERROR']);
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
    
    return ($medico && $medico['resultado'] === 'apto') && 
           ($psicotecnico && $psicotecnico['resultado'] === 'apto');
}
?>
