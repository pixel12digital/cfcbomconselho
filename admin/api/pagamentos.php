<?php
/**
 * API para gerenciamento de Pagamentos (Baixas)
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
    $faturaId = $_GET['fatura_id'] ?? null;
    
    if ($faturaId) {
        // Buscar pagamentos de uma fatura específica
        $pagamentos = $db->fetchAll("
            SELECT p.*, f.titulo as fatura_titulo, f.valor_total as fatura_valor_total,
                   f.data_vencimento as fatura_data_vencimento
            FROM pagamentos p
            JOIN financeiro_faturas f ON p.fatura_id = f.id
            WHERE p.fatura_id = ?
            ORDER BY p.data_pagamento DESC
        ", [$faturaId]);
        
        echo json_encode(['success' => true, 'pagamentos' => $pagamentos]);
    } else {
        // Listar todos os pagamentos
        $pagamentos = $db->fetchAll("
            SELECT p.*, f.titulo as fatura_titulo, f.valor_total as fatura_valor_total,
                   f.data_vencimento as fatura_data_vencimento, a.nome as aluno_nome
            FROM pagamentos p
            JOIN financeiro_faturas f ON p.fatura_id = f.id
            JOIN alunos a ON f.aluno_id = a.id
            ORDER BY p.data_pagamento DESC
            LIMIT 100
        ");
        
        echo json_encode(['success' => true, 'pagamentos' => $pagamentos]);
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
    $required = ['fatura_id', 'data_pagamento', 'valor_pago'];
    foreach ($required as $field) {
        if (empty($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch("SELECT * FROM financeiro_faturas WHERE id = ?", [$input['fatura_id']]);
    if (!$fatura) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Fatura não encontrada']);
        return;
    }
    
    // Verificar se fatura não está cancelada
    if ($fatura['status'] === 'cancelada') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'Não é possível registrar pagamento em fatura cancelada']);
        return;
    }
    
    // Inserir pagamento
    $pagamentoId = $db->insert('pagamentos', [
        'fatura_id' => $input['fatura_id'],
        'data_pagamento' => $input['data_pagamento'],
        'valor_pago' => $input['valor_pago'],
        'metodo' => $input['metodo'] ?? 'pix',
        'comprovante_url' => $input['comprovante_url'] ?? null,
        'obs' => $input['obs'] ?? null,
        'criado_por' => $currentUser['id']
    ]);
    
    // Recalcular status da fatura
    $novoStatus = recalcularStatusFatura($db, $input['fatura_id']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pagamento registrado com sucesso',
        'pagamento_id' => $pagamentoId,
        'novo_status_fatura' => $novoStatus
    ]);
}

/**
 * Processar requisições DELETE (estorno)
 */
function handleDelete($db, $currentUser) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do pagamento não fornecido']);
        return;
    }
    
    // Verificar se pagamento existe
    $pagamento = $db->fetch("SELECT * FROM pagamentos WHERE id = ?", [$id]);
    if (!$pagamento) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Pagamento não encontrado']);
        return;
    }
    
    // Excluir pagamento
    $db->delete('pagamentos', 'id = ?', [$id]);
    
    // Recalcular status da fatura
    $novoStatus = recalcularStatusFatura($db, $pagamento['fatura_id']);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Pagamento estornado com sucesso',
        'novo_status_fatura' => $novoStatus
    ]);
}

/**
 * Recalcular status da fatura baseado nos pagamentos
 */
function recalcularStatusFatura($db, $faturaId) {
    // Buscar fatura
    $fatura = $db->fetch("SELECT * FROM financeiro_faturas WHERE id = ?", [$faturaId]);
    if (!$fatura) {
        return null;
    }
    
    // Calcular total pago
    $totalPago = $db->fetchColumn("
        SELECT COALESCE(SUM(valor_pago), 0) FROM pagamentos WHERE fatura_id = ?
    ", [$faturaId]);
    
    $valorTotal = (float)($fatura['valor_total'] ?? $fatura['valor'] ?? 0);
    $totalPago = (float)$totalPago;
    
    // Determinar novo status
    if ($totalPago >= $valorTotal) {
        $novoStatus = 'paga';
    } elseif ($totalPago > 0) {
        $novoStatus = 'parcial';
    } else {
        // Verificar se está vencida (usar data_vencimento com fallback para compatibilidade)
        $dataVencimento = $fatura['data_vencimento'] ?? $fatura['vencimento'] ?? null;
        if ($dataVencimento && $dataVencimento < date('Y-m-d')) {
            $novoStatus = 'vencida';
        } else {
            $novoStatus = 'aberta';
        }
    }
    
    // Atualizar status da fatura
    $db->update('financeiro_faturas', ['status' => $novoStatus], 'id = ?', [$faturaId]);
    
    return $novoStatus;
}

