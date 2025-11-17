<?php
/**
 * API Financeiro - Faturas (Receitas)
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
$path = $_SERVER['REQUEST_URI'];

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
    $aluno_id = $_GET['aluno_id'] ?? null;
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
        // Buscar fatura específica
        $fatura = $db->fetch("
            SELECT f.*, a.nome as aluno_nome, a.cpf, m.categoria_cnh, m.tipo_servico
            FROM financeiro_faturas f
            JOIN alunos a ON f.aluno_id = a.id
            LEFT JOIN matriculas m ON f.matricula_id = m.id
            WHERE f.id = ?
        ", [$id]);
        
        if (!$fatura) {
            http_response_code(404);
            echo json_encode(['error' => 'Fatura não encontrada']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $fatura]);
        return;
    }
    
    // Listar faturas com filtros
    $where = ['1=1'];
    $params = [];
    
    if ($aluno_id) {
        $where[] = 'f.aluno_id = ?';
        $params[] = $aluno_id;
    }
    
    if ($status) {
        $where[] = 'f.status = ?';
        $params[] = $status;
    }
    
    if ($data_inicio) {
        $where[] = 'f.data_vencimento >= ?';
        $params[] = $data_inicio;
    }
    
    if ($data_fim) {
        $where[] = 'f.data_vencimento <= ?';
        $params[] = $data_fim;
    }
    
    $whereClause = implode(' AND ', $where);
    
    // Contar total
    $total = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM financeiro_faturas f
        JOIN alunos a ON f.aluno_id = a.id
        WHERE $whereClause
    ", $params);
    
    // Buscar faturas
    $faturas = $db->fetchAll("
        SELECT f.*, a.nome as aluno_nome, a.cpf, m.categoria_cnh, m.tipo_servico
        FROM financeiro_faturas f
        JOIN alunos a ON f.aluno_id = a.id
        LEFT JOIN matriculas m ON f.matricula_id = m.id
        WHERE $whereClause
        ORDER BY f.data_vencimento DESC, f.criado_em DESC
        LIMIT $limit OFFSET $offset
    ", $params);
    
    echo json_encode([
        'success' => true,
        'data' => $faturas,
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
    $required = ['aluno_id', 'titulo', 'valor_total', 'data_vencimento'];
    foreach ($required as $field) {
        // Aceitar tanto data_vencimento quanto vencimento (compatibilidade)
        if ($field === 'data_vencimento' && empty($input['data_vencimento']) && empty($input['vencimento'])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo obrigatório: data_vencimento ou vencimento"]);
            return;
        } elseif ($field !== 'data_vencimento' && empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Validar aluno existe
    $aluno = $db->fetch('SELECT id, nome FROM alunos WHERE id = ?', [$input['aluno_id']]);
    if (!$aluno) {
        http_response_code(400);
        echo json_encode(['error' => 'Aluno não encontrado']);
        return;
    }
    
    // Criar fatura (usar data_vencimento como oficial, manter vencimento para compatibilidade)
    $dataVencimento = $input['data_vencimento'] ?? $input['vencimento'] ?? null;
    
    $faturaId = $db->insert('financeiro_faturas', [
        'aluno_id' => $input['aluno_id'],
        'matricula_id' => $input['matricula_id'] ?? null,
        'titulo' => $input['titulo'],
        'valor_total' => $input['valor_total'],
        'status' => $input['status'] ?? 'aberta',
        'data_vencimento' => $dataVencimento,
        'vencimento' => $dataVencimento, // Manter para compatibilidade
        'forma_pagamento' => $input['forma_pagamento'] ?? 'avista',
        'parcelas' => $input['parcelas'] ?? 1,
        'observacoes' => $input['observacoes'] ?? null,
        'criado_por' => $user['id']
    ]);
    
    // Atualizar status de inadimplência do aluno
    updateAlunoInadimplencia($db, $input['aluno_id']);
    
    echo json_encode([
        'success' => true,
        'fatura_id' => $faturaId,
        'message' => 'Fatura criada com sucesso'
    ]);
}

function handlePut($db, $user) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID da fatura obrigatório']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        http_response_code(400);
        echo json_encode(['error' => 'Dados inválidos']);
        return;
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch('SELECT * FROM financeiro_faturas WHERE id = ?', [$id]);
    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['error' => 'Fatura não encontrada']);
        return;
    }
    
    // Campos permitidos para atualização (aceitar data_vencimento e vencimento para compatibilidade)
    $allowedFields = ['titulo', 'valor_total', 'status', 'data_vencimento', 'vencimento', 'forma_pagamento', 'observacoes'];
    $updateData = [];
    
    foreach ($allowedFields as $field) {
        if ($field === 'data_vencimento' && isset($input['data_vencimento'])) {
            $updateData['data_vencimento'] = $input['data_vencimento'];
            // Manter vencimento em sync para compatibilidade
            $updateData['vencimento'] = $input['data_vencimento'];
        } elseif ($field === 'vencimento' && isset($input['vencimento']) && !isset($input['data_vencimento'])) {
            // Se apenas vencimento for fornecido (sem data_vencimento), usar para ambos
            $updateData['data_vencimento'] = $input['vencimento'];
            $updateData['vencimento'] = $input['vencimento'];
        } elseif ($field !== 'data_vencimento' && $field !== 'vencimento' && isset($input[$field])) {
            $updateData[$field] = $input[$field];
        }
    }
    
    if (empty($updateData)) {
        http_response_code(400);
        echo json_encode(['error' => 'Nenhum campo para atualizar']);
        return;
    }
    
    $updateData['atualizado_em'] = date('Y-m-d H:i:s');
    
    $db->update('financeiro_faturas', $updateData, 'id = ?', [$id]);
    
    // Atualizar status de inadimplência do aluno
    updateAlunoInadimplencia($db, $fatura['aluno_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Fatura atualizada com sucesso'
    ]);
}

function handleDelete($db, $user) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode(['error' => 'ID da fatura obrigatório']);
        return;
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch('SELECT * FROM financeiro_faturas WHERE id = ?', [$id]);
    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['error' => 'Fatura não encontrada']);
        return;
    }
    
    // Verificar se pode ser excluída (apenas se aberta)
    if ($fatura['status'] !== 'aberta') {
        http_response_code(400);
        echo json_encode(['error' => 'Apenas faturas abertas podem ser excluídas']);
        return;
    }
    
    $db->delete('financeiro_faturas', 'id = ?', [$id]);
    
    // Atualizar status de inadimplência do aluno
    updateAlunoInadimplencia($db, $fatura['aluno_id']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Fatura excluída com sucesso'
    ]);
}

function exportCSV($db, $user) {
    $faturas = $db->fetchAll("
        SELECT f.*, a.nome as aluno_nome, a.cpf
        FROM financeiro_faturas f
        JOIN alunos a ON f.aluno_id = a.id
        ORDER BY f.data_vencimento DESC, f.vencimento DESC
    ");
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=faturas_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    
    // BOM para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho
    fputcsv($output, [
        'ID', 'Aluno', 'CPF', 'Título', 'Valor Total', 'Status', 
        'Vencimento', 'Forma Pagamento', 'Parcelas', 'Observações', 'Criado em'
    ]);
    
    // Dados
    foreach ($faturas as $fatura) {
        fputcsv($output, [
            $fatura['id'],
            $fatura['aluno_nome'],
            $fatura['cpf'],
            $fatura['titulo'],
            number_format($fatura['valor_total'], 2, ',', '.'),
            $fatura['status'],
            date('d/m/Y', strtotime($fatura['data_vencimento'] ?? $fatura['vencimento'] ?? '')),
            $fatura['forma_pagamento'],
            $fatura['parcelas'],
            $fatura['observacoes'],
            date('d/m/Y H:i', strtotime($fatura['criado_em']))
        ]);
    }
    
    fclose($output);
}

function updateAlunoInadimplencia($db, $alunoId) {
    // Verificar se há faturas vencidas
    // Usar fallback seguro: se tabela não existir, usar 30 dias padrão
    try {
        $config = $db->fetch("SELECT valor FROM financeiro_configuracoes WHERE chave = 'dias_inadimplencia'");
        $diasInadimplencia = $config ? (int)$config['valor'] : 30;
    } catch (Exception $e) {
        // Se tabela não existir, usar valor padrão
        $diasInadimplencia = 30;
    }
    
    $faturasVencidas = $db->fetchColumn("
        SELECT COUNT(*) 
        FROM financeiro_faturas 
        WHERE aluno_id = ? 
        AND status IN ('aberta', 'vencida') 
        AND data_vencimento < DATE_SUB(NOW(), INTERVAL ? DAY)
    ", [$alunoId, $diasInadimplencia]);
    
    $inadimplente = $faturasVencidas > 0;
    
    $db->update('alunos', [
        'inadimplente' => $inadimplente ? 1 : 0,
        'inadimplente_desde' => $inadimplente ? date('Y-m-d') : null
    ], 'id = ?', [$alunoId]);
}
