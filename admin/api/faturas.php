<?php
/**
 * API para gerenciamento de Faturas (Receitas)
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
    $matriculaId = $_GET['matricula_id'] ?? null;
    $alunoId = $_GET['aluno_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $vencimentoDe = $_GET['vencimento_de'] ?? null;
    $vencimentoAte = $_GET['vencimento_ate'] ?? null;
    $page = $_GET['page'] ?? 1;
    $limit = $_GET['limit'] ?? 20;
    
    if ($id) {
        // Buscar fatura específica
        $fatura = $db->fetch("
            SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf, m.categoria_cnh, m.tipo_servico
            FROM faturas f
            JOIN alunos a ON f.aluno_id = a.id
            JOIN matriculas m ON f.matricula_id = m.id
            WHERE f.id = ?
        ", [$id]);
        
        if (!$fatura) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Fatura não encontrada']);
            return;
        }
        
        // Buscar pagamentos da fatura
        $pagamentos = $db->fetchAll("
            SELECT * FROM pagamentos WHERE fatura_id = ? ORDER BY data_pagamento DESC
        ", [$id]);
        
        $fatura['pagamentos'] = $pagamentos;
        
        echo json_encode(['success' => true, 'fatura' => $fatura]);
    } else {
        // Listar faturas com filtros
        $where = [];
        $params = [];
        
        if ($matriculaId) {
            $where[] = "f.matricula_id = ?";
            $params[] = $matriculaId;
        }
        
        if ($alunoId) {
            $where[] = "f.aluno_id = ?";
            $params[] = $alunoId;
        }
        
        if ($status) {
            $where[] = "f.status = ?";
            $params[] = $status;
        }
        
        if ($vencimentoDe) {
            $where[] = "f.vencimento >= ?";
            $params[] = $vencimentoDe;
        }
        
        if ($vencimentoAte) {
            $where[] = "f.vencimento <= ?";
            $params[] = $vencimentoAte;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $offset = ($page - 1) * $limit;
        
        $faturas = $db->fetchAll("
            SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf, m.categoria_cnh, m.tipo_servico
            FROM faturas f
            JOIN alunos a ON f.aluno_id = a.id
            JOIN matriculas m ON f.matricula_id = m.id
            $whereClause
            ORDER BY f.vencimento DESC, f.criado_em DESC
            LIMIT ? OFFSET ?
        ", array_merge($params, [$limit, $offset]));
        
        // Contar total para paginação
        $total = $db->fetchColumn("
            SELECT COUNT(*) FROM faturas f $whereClause
        ", $params);
        
        echo json_encode([
            'success' => true, 
            'faturas' => $faturas,
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
    
    // Verificar se é criação de parcelas
    if (isset($input['parcelas']) && $input['parcelas'] > 1) {
        criarParcelas($db, $input, $currentUser);
        return;
    }
    
    // Criar fatura única
    criarFaturaUnica($db, $input, $currentUser);
}

/**
 * Criar fatura única
 */
function criarFaturaUnica($db, $input, $currentUser) {
    // Validar dados obrigatórios
    $required = ['matricula_id', 'aluno_id', 'descricao', 'valor', 'vencimento'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Verificar se matrícula existe
    $matricula = $db->fetch("SELECT * FROM matriculas WHERE id = ?", [$input['matricula_id']]);
    if (!$matricula) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Matrícula não encontrada']);
        return;
    }
    
    // Calcular valor líquido
    $valor = (float)$input['valor'];
    $desconto = (float)($input['desconto'] ?? 0);
    $acrescimo = (float)($input['acrescimo'] ?? 0);
    $valorLiquido = max($valor - $desconto + $acrescimo, 0);
    
    // Gerar número da fatura
    $numero = 'FAT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    // Inserir fatura
    $faturaId = $db->insert('faturas', [
        'matricula_id' => $input['matricula_id'],
        'aluno_id' => $input['aluno_id'],
        'numero' => $numero,
        'descricao' => $input['descricao'],
        'valor' => $valor,
        'desconto' => $desconto,
        'acrescimo' => $acrescimo,
        'valor_liquido' => $valorLiquido,
        'vencimento' => $input['vencimento'],
        'meio' => $input['meio'] ?? 'pix',
        'criado_por' => $currentUser['id']
    ]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Fatura criada com sucesso',
        'fatura_id' => $faturaId,
        'numero' => $numero
    ]);
}

/**
 * Criar parcelas
 */
function criarParcelas($db, $input, $currentUser) {
    // Validar dados obrigatórios
    $required = ['matricula_id', 'aluno_id', 'descricao', 'valor_total', 'parcelas', 'primeiro_vencimento'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    $valorTotal = (float)$input['valor_total'];
    $parcelas = (int)$input['parcelas'];
    $valorParcela = $valorTotal / $parcelas;
    $intervaloDias = (int)($input['intervalo_dias'] ?? 30);
    
    $faturasCriadas = [];
    
    for ($i = 1; $i <= $parcelas; $i++) {
        $vencimento = date('Y-m-d', strtotime($input['primeiro_vencimento'] . " +" . (($i - 1) * $intervaloDias) . " days"));
        $numero = 'FAT-' . date('Y') . '-' . str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT) . "-$i";
        
        $faturaId = $db->insert('faturas', [
            'matricula_id' => $input['matricula_id'],
            'aluno_id' => $input['aluno_id'],
            'numero' => $numero,
            'descricao' => $input['descricao'] . " - Parcela $i/$parcelas",
            'valor' => $valorParcela,
            'desconto' => 0,
            'acrescimo' => 0,
            'valor_liquido' => $valorParcela,
            'vencimento' => $vencimento,
            'meio' => $input['meio'] ?? 'pix',
            'criado_por' => $currentUser['id']
        ]);
        
        $faturasCriadas[] = [
            'id' => $faturaId,
            'numero' => $numero,
            'valor' => $valorParcela,
            'vencimento' => $vencimento
        ];
    }
    
    echo json_encode([
        'success' => true, 
        'message' => "$parcelas parcelas criadas com sucesso",
        'faturas' => $faturasCriadas
    ]);
}

/**
 * Processar requisições PUT
 */
function handlePut($db, $currentUser) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID da fatura não fornecido']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
        return;
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch("SELECT * FROM faturas WHERE id = ?", [$id]);
    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Fatura não encontrada']);
        return;
    }
    
    // Campos editáveis
    $camposEditaveis = ['descricao', 'valor', 'desconto', 'acrescimo', 'vencimento', 'meio', 'status'];
    $dadosAtualizacao = [];
    
    foreach ($camposEditaveis as $campo) {
        if (isset($input[$campo])) {
            $dadosAtualizacao[$campo] = $input[$campo];
        }
    }
    
    // Recalcular valor líquido se necessário
    if (isset($dadosAtualizacao['valor']) || isset($dadosAtualizacao['desconto']) || isset($dadosAtualizacao['acrescimo'])) {
        $valor = $dadosAtualizacao['valor'] ?? $fatura['valor'];
        $desconto = $dadosAtualizacao['desconto'] ?? $fatura['desconto'];
        $acrescimo = $dadosAtualizacao['acrescimo'] ?? $fatura['acrescimo'];
        $dadosAtualizacao['valor_liquido'] = max($valor - $desconto + $acrescimo, 0);
    }
    
    if (!empty($dadosAtualizacao)) {
        $db->update('faturas', $dadosAtualizacao, 'id = ?', [$id]);
        
        echo json_encode(['success' => true, 'message' => 'Fatura atualizada com sucesso']);
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
        echo json_encode(['success' => false, 'error' => 'ID da fatura não fornecido']);
        return;
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch("SELECT * FROM faturas WHERE id = ?", [$id]);
    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Fatura não encontrada']);
        return;
    }
    
    // Verificar se tem pagamentos
    $pagamentos = $db->fetchAll("SELECT * FROM pagamentos WHERE fatura_id = ?", [$id]);
    if (!empty($pagamentos)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Não é possível excluir fatura com pagamentos registrados']);
        return;
    }
    
    // Cancelar fatura (marcar como cancelada)
    $db->update('faturas', ['status' => 'cancelada'], 'id = ?', [$id]);
    
    echo json_encode(['success' => true, 'message' => 'Fatura cancelada com sucesso']);
}

