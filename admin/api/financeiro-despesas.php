<?php
/**
 * API Financeiro - Despesas (Pagamentos)
 * Sistema CFC - Bom Conselho MVP
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

// Verificar autenticação e permissão
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$currentUser = getCurrentUser();
if (!in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Sem permissão']);
    exit;
}

$db = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

try {
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
            echo json_encode(['error' => 'Método não permitido']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}

function handleGet($db, $user) {
    $id = $_GET['id'] ?? null;
    $categoria = $_GET['categoria'] ?? null;
    $status = $_GET['status'] ?? null;
    $data_inicio = $_GET['data_inicio'] ?? null;
    $data_fim = $_GET['data_fim'] ?? null;
    $page = (int)($_GET['page'] ?? 1);
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = ($page - 1) * $limit;
    
    // Export CSV
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        exportCSV($db, $user);
        return;
    }
    
    if ($id) {
        // Buscar despesa específica
        $despesa = $db->fetch("
            SELECT p.*, u.nome as criado_por_nome
            FROM financeiro_pagamentos p
            JOIN usuarios u ON p.criado_por = u.id
            WHERE p.id = ?
        ", [$id]);
        
        if (!$despesa) {
            http_response_code(404);
            echo json_encode(['error' => 'Despesa não encontrada']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $despesa]);
        return;
    }
    
    // Listar despesas com filtros
    $where = ['1=1'];
    $params = [];
    
    if ($categoria) {
        $where[] = 'p.categoria = ?';
        $params[] = $categoria;
    }
    
    if ($status) {
        $where[] = 'p.status = ?';
        $params[] = $status;
    }
    
    if ($data_inicio) {
        $where[] = 'p.vencimento >= ?';
        $params[] = $data_inicio;
    }
    
    if ($data_fim) {
        $where[] = 'p.vencimento <= ?';
        $params[] = $data_fim;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Contar total
    $total = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM financeiro_pagamentos p
        WHERE $whereClause
    ", $params);
    
    // Buscar despesas
    $despesas = $db->fetchAll("
        SELECT p.*, u.nome as criado_por_nome
        FROM financeiro_pagamentos p
        JOIN usuarios u ON p.criado_por = u.id
        WHERE $whereClause
        ORDER BY p.vencimento DESC, p.criado_em DESC
        LIMIT $limit OFFSET $offset
    ", $params);
    
    echo json_encode([
        'success' => true,
        'data' => $despesas,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handlePost($db, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }
    
    // Validações obrigatórias
    $required = ['fornecedor', 'valor', 'vencimento'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Criar despesa
    $despesaId = $db->insert('financeiro_pagamentos', [
        'fornecedor' => $input['fornecedor'],
        'descricao' => $input['descricao'] ?? null,
        'categoria' => $input['categoria'] ?? 'outros',
        'valor' => $input['valor'],
        'status' => $input['status'] ?? 'pendente',
        'vencimento' => $input['vencimento'],
        'forma_pagamento' => $input['forma_pagamento'] ?? 'pix',
        'data_pagamento' => $input['data_pagamento'] ?? null,
        'observacoes' => $input['observacoes'] ?? null,
        'comprovante_url' => $input['comprovante_url'] ?? null,
        'criado_por' => $user['id']
    ]);
    
    echo json_encode([
        'success' => true,
        'despesa_id' => $despesaId,
        'message' => 'Despesa criada com sucesso'
    ]);
}

function handlePut($db, $user) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID da despesa obrigatório']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }
    
    // Verificar se despesa existe
    $despesa = $db->fetch('SELECT * FROM financeiro_pagamentos WHERE id = ?', [$id]);
    if (!$despesa) {
        http_response_code(404);
        echo json_encode(['error' => 'Despesa não encontrada']);
        return;
    }
    
    // Campos permitidos para atualização
    $allowedFields = ['fornecedor', 'descricao', 'categoria', 'valor', 'status', 'vencimento', 'forma_pagamento', 'data_pagamento', 'observacoes', 'comprovante_url'];
    $updateData = [];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $updateData[$field] = $input[$field];
        }
    }
    
    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nenhum campo para atualizar']);
        return;
    }
    
    $updateData['atualizado_em'] = date('Y-m-d H:i:s');
    
    $db->update('financeiro_pagamentos', $updateData, 'id = ?', [$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Despesa atualizada com sucesso'
    ]);
}

function handleDelete($db, $user) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID da despesa obrigatório']);
        return;
    }
    
    // Verificar se despesa existe
    $despesa = $db->fetch('SELECT * FROM financeiro_pagamentos WHERE id = ?', [$id]);
    if (!$despesa) {
        http_response_code(404);
        echo json_encode(['error' => 'Despesa não encontrada']);
        return;
    }
    
    // Verificar se pode ser excluída (apenas se pendente)
    if ($despesa['status'] !== 'pendente') {
        http_response_code(400);
        echo json_encode(['error' => 'Apenas despesas pendentes podem ser excluídas']);
        return;
    }
    
    $db->delete('financeiro_pagamentos', 'id = ?', [$id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Despesa excluída com sucesso'
    ]);
}

function exportCSV($db, $user) {
    $despesas = $db->fetchAll("
        SELECT p.*, u.nome as criado_por_nome
        FROM financeiro_pagamentos p
        JOIN usuarios u ON p.criado_por = u.id
        ORDER BY p.vencimento DESC
    ");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=despesas_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho
    fputcsv($output, [
        'ID', 'Fornecedor', 'Descrição', 'Categoria', 'Valor', 'Status', 
        'Vencimento', 'Forma Pagamento', 'Data Pagamento', 'Observações', 'Criado por', 'Criado em'
    ]);
    
    // Dados
    foreach ($despesas as $despesa) {
        fputcsv($output, [
            $despesa['id'],
            $despesa['fornecedor'],
            $despesa['descricao'],
            $despesa['categoria'],
            number_format($despesa['valor'], 2, ',', '.'),
            $despesa['status'],
            date('d/m/Y', strtotime($despesa['vencimento'])),
            $despesa['forma_pagamento'],
            $despesa['data_pagamento'] ? date('d/m/Y', strtotime($despesa['data_pagamento'])) : '',
            $despesa['observacoes'],
            $despesa['criado_por_nome'],
            date('d/m/Y H:i', strtotime($despesa['criado_em']))
        ]);
    }
    
    fclose($output);
}
