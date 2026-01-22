<?php
/**
 * API para gerenciamento de Despesas (Contas a Pagar)
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
    
    // Verificar se sistema financeiro está habilitado
    if (!defined('FINANCEIRO_ENABLED') || !FINANCEIRO_ENABLED) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'Sistema financeiro desabilitado']);
        exit;
    }
    
    // Verificar autenticação
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Usuário não autenticado']);
        exit;
    }
    
    // Verificar permissão (apenas admin e secretaria)
    $currentUser = getCurrentUser();
    if (!$currentUser || !in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Acesso negado - Apenas administradores e atendentes podem acessar o sistema financeiro']);
        exit;
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            handleGet($db, $currentUser);
            break;
        case 'POST':
            handlePost($db, $currentUser);
            break;
        case 'PUT':
            handlePut($db, $currentUser);
            break;
        case 'DELETE':
            handleDelete($db, $currentUser);
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
function handleGet($db, $currentUser) {
    $id = $_GET['id'] ?? null;
    $categoria = $_GET['categoria'] ?? null;
    $pago = $_GET['pago'] ?? null;
    $vencimentoDe = $_GET['vencimento_de'] ?? null;
    $vencimentoAte = $_GET['vencimento_ate'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    
    if ($id) {
        // Buscar despesa específica
        $despesa = $db->fetch("SELECT * FROM despesas WHERE id = ?", [$id]);
        
        if (!$despesa) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Despesa não encontrada']);
            return;
        }
        
        echo json_encode(['success' => true, 'despesa' => $despesa]);
    } else {
        // Listar despesas com filtros
        $where = [];
        $params = [];
        
        if ($categoria) {
            $where[] = "categoria = ?";
            $params[] = $categoria;
        }
        
        if ($pago !== null) {
            $where[] = "pago = ?";
            $params[] = $pago;
        }
        
        if ($vencimentoDe) {
            $where[] = "vencimento >= ?";
            $params[] = $vencimentoDe;
        }
        
        if ($vencimentoAte) {
            $where[] = "vencimento <= ?";
            $params[] = $vencimentoAte;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $offset = ($page - 1) * $limit;
        
        $despesas = $db->fetchAll("
            SELECT * FROM despesas 
            $whereClause
            ORDER BY vencimento ASC, criado_em DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));
        
        // Contar total para paginação
        $total = $db->fetchColumn("
            SELECT COUNT(*) FROM despesas $whereClause
        ", $params);
        
        echo json_encode([
            'success' => true, 
            'despesas' => $despesas,
            'pagination' => [
                'page' => (int)$page,
                'limit' => (int)$limit,
                'total' => (int)$total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
}

/**
 * Processar requisições POST
 */
function handlePost($db, $currentUser) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Validar dados obrigatórios
    $required = ['titulo', 'valor', 'vencimento'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Inserir despesa
    $despesaId = $db->insert('despesas', [
        'titulo' => $input['titulo'],
        'fornecedor' => $input['fornecedor'] ?? null,
        'categoria' => $input['categoria'] ?? 'outros',
        'valor' => $input['valor'],
        'vencimento' => $input['vencimento'],
        'pago' => 0,
        'data_pagamento' => null,
        'metodo' => $input['metodo'] ?? 'pix',
        'anexo_url' => $input['anexo_url'] ?? null,
        'obs' => $input['obs'] ?? null,
        'criado_por' => $currentUser['id']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Despesa criada com sucesso',
        'despesa_id' => $despesaId
    ]);
}

/**
 * Processar requisições PUT
 */
function handlePut($db, $currentUser) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da despesa não fornecido']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Verificar se despesa existe
    $despesa = $db->fetch("SELECT * FROM despesas WHERE id = ?", [$id]);
    if (!$despesa) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Despesa não encontrada']);
        return;
    }
    
    // Campos editáveis
    $camposEditaveis = ['titulo', 'fornecedor', 'categoria', 'valor', 'vencimento', 'metodo', 'anexo_url', 'obs'];
    $dadosAtualizacao = [];
    
    foreach ($camposEditaveis as $campo) {
        if (isset($input[$campo])) {
            $dadosAtualizacao[$campo] = $input[$campo];
        }
    }
    
    // Se marcar como pago, definir data de pagamento
    if (isset($input['pago']) && $input['pago'] == 1) {
        $dadosAtualizacao['pago'] = 1;
        $dadosAtualizacao['data_pagamento'] = $input['data_pagamento'] ?? date('Y-m-d');
        $dadosAtualizacao['metodo'] = $input['metodo'] ?? $despesa['metodo'];
    } elseif (isset($input['pago']) && $input['pago'] == 0) {
        $dadosAtualizacao['pago'] = 0;
        $dadosAtualizacao['data_pagamento'] = null;
    }
    
    if (!empty($dadosAtualizacao)) {
        $db->update('despesas', $dadosAtualizacao, 'id = ?', [$id]);
        
        echo json_encode(['success' => true, 'message' => 'Despesa atualizada com sucesso']);
    } else {
        echo json_encode(['success' => true, 'message' => 'Nenhuma alteração realizada']);
    }
}

/**
 * Processar requisições DELETE
 */
function handleDelete($db, $currentUser) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da despesa não fornecido']);
        return;
    }
    
    // Verificar se despesa existe
    $despesa = $db->fetch("SELECT * FROM despesas WHERE id = ?", [$id]);
    if (!$despesa) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Despesa não encontrada']);
        return;
    }
    
    // Verificar se já foi paga
    if ($despesa['pago'] == 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Não é possível excluir despesa já paga']);
        return;
    }
    
    // Excluir despesa
    $db->delete('despesas', 'id = ?', [$id]);
    
    echo json_encode(['success' => true, 'message' => 'Despesa excluída com sucesso']);
}

