<?php
/**
 * API para gerenciamento de Matrículas
 * Sistema CFC - Bom Conselho
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a requisições OPTIONS (CORS preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir arquivos necessários
require_once '../../includes/config.php';
require_once '../../includes/database.php';
require_once '../../includes/auth.php';

try {
    $db = Database::getInstance();
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }
    
    // Verificar permissão
    $currentUser = getCurrentUser();
    if (!$currentUser || !in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($db);
            break;
        case 'POST':
            handlePost($db);
            break;
        case 'PUT':
            handlePut($db);
            break;
        case 'DELETE':
            handleDelete($db);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Erro interno: ' . $e->getMessage()]);
}

/**
 * Processar requisições GET
 */
function handleGet($db) {
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if ($alunoId) {
        // Buscar matrículas de um aluno específico
        $matriculas = $db->fetchAll("
            SELECT m.*, a.nome as aluno_nome, a.cpf as aluno_cpf
            FROM matriculas m
            JOIN alunos a ON m.aluno_id = a.id
            WHERE m.aluno_id = ?
            ORDER BY m.data_inicio DESC
        ", [$alunoId]);
        
        echo json_encode(['success' => true, 'matriculas' => $matriculas]);
    } else {
        // Listar todas as matrículas
        $matriculas = $db->fetchAll("
            SELECT m.*, a.nome as aluno_nome, a.cpf as aluno_cpf
            FROM matriculas m
            JOIN alunos a ON m.aluno_id = a.id
            ORDER BY m.data_inicio DESC
            LIMIT 100
        ");
        
        echo json_encode(['success' => true, 'matriculas' => $matriculas]);
    }
}

/**
 * Processar requisições POST
 */
function handlePost($db) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Validar dados obrigatórios
    $required = ['aluno_id', 'categoria_cnh', 'tipo_servico', 'data_inicio'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT id FROM alunos WHERE id = ?", [$input['aluno_id']]);
    if (!$aluno) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
        return;
    }
    
    // Verificar se já existe matrícula ativa da mesma categoria + tipo_servico
    $matriculaExistente = $db->fetch("
        SELECT id FROM matriculas 
        WHERE aluno_id = ? AND categoria_cnh = ? AND tipo_servico = ? AND status = 'ativa'
    ", [$input['aluno_id'], $input['categoria_cnh'], $input['tipo_servico']]);
    
    if ($matriculaExistente) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Já existe uma matrícula ativa para esta categoria e tipo de serviço'
        ]);
        return;
    }
    
    // Inserir nova matrícula
    $matriculaId = $db->execute("
        INSERT INTO matriculas (
            aluno_id, categoria_cnh, tipo_servico, status, data_inicio, data_fim,
            valor_total, forma_pagamento, observacoes
        ) VALUES (?, ?, ?, 'ativa', ?, ?, ?, ?, ?)
    ", [
        $input['aluno_id'],
        $input['categoria_cnh'],
        $input['tipo_servico'],
        $input['data_inicio'],
        $input['data_fim'] ?? null,
        $input['valor_total'] ?? null,
        $input['forma_pagamento'] ?? null,
        $input['observacoes'] ?? null
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Matrícula criada com sucesso',
        'matricula_id' => $matriculaId
    ]);
}

/**
 * Processar requisições PUT
 */
function handlePut($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da matrícula não fornecido']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Verificar se matrícula existe
    $matricula = $db->fetch("SELECT * FROM matriculas WHERE id = ?", [$id]);
    if (!$matricula) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Matrícula não encontrada']);
        return;
    }
    
    // Atualizar matrícula
    $db->execute("
        UPDATE matriculas SET
            categoria_cnh = ?,
            tipo_servico = ?,
            status = ?,
            data_inicio = ?,
            data_fim = ?,
            valor_total = ?,
            forma_pagamento = ?,
            observacoes = ?,
            atualizado_em = NOW()
        WHERE id = ?
    ", [
        $input['categoria_cnh'] ?? $matricula['categoria_cnh'],
        $input['tipo_servico'] ?? $matricula['tipo_servico'],
        $input['status'] ?? $matricula['status'],
        $input['data_inicio'] ?? $matricula['data_inicio'],
        $input['data_fim'] ?? $matricula['data_fim'],
        $input['valor_total'] ?? $matricula['valor_total'],
        $input['forma_pagamento'] ?? $matricula['forma_pagamento'],
        $input['observacoes'] ?? $matricula['observacoes'],
        $id
    ]);
    
    echo json_encode(['success' => true, 'message' => 'Matrícula atualizada com sucesso']);
}

/**
 * Processar requisições DELETE
 */
function handleDelete($db) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da matrícula não fornecido']);
        return;
    }
    
    // Verificar se matrícula existe
    $matricula = $db->fetch("SELECT * FROM matriculas WHERE id = ?", [$id]);
    if (!$matricula) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Matrícula não encontrada']);
        return;
    }
    
    // Verificar se pode ser excluída (apenas se não há aulas vinculadas)
    $aulasVinculadas = $db->fetch("
        SELECT COUNT(*) as total FROM aulas 
        WHERE aluno_id = ? AND data_aula >= ?
    ", [$matricula['aluno_id'], $matricula['data_inicio']]);
    
    if ($aulasVinculadas['total'] > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false, 
            'error' => 'Não é possível excluir matrícula com aulas vinculadas'
        ]);
        return;
    }
    
    // Excluir matrícula
    $db->execute("DELETE FROM matriculas WHERE id = ?", [$id]);
    
    echo json_encode(['success' => true, 'message' => 'Matrícula excluída com sucesso']);
}
