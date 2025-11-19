<?php
/**
 * API Financeiro - Faturas (Receitas)
 * Sistema CFC - Bom Conselho MVP
 */

// Configuração para produção - evitar erros HTML antes do JSON
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('html_errors', 0);

// Limpar qualquer saída anterior
if (ob_get_level()) {
    ob_clean();
}

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Função para garantir resposta JSON válida
function sendJsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    
    // Limpar qualquer saída anterior
    if (ob_get_level()) {
        ob_clean();
    }
    
    // Garantir que não há saída antes do JSON
    $output = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        $output = json_encode([
            'success' => false, 
            'error' => 'Erro ao codificar JSON: ' . json_last_error_msg()
        ], JSON_UNESCAPED_UNICODE);
    }
    
    echo $output;
    exit;
}

// Incluir arquivos necessários usando caminho absoluto relativo
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticação e permissão
if (!isLoggedIn()) {
    sendJsonResponse(['success' => false, 'error' => 'Não autenticado'], 401);
}

$currentUser = getCurrentUser();
if (!in_array($currentUser['tipo'], ['admin', 'secretaria'])) {
    sendJsonResponse(['success' => false, 'error' => 'Sem permissão'], 403);
}

try {
    $db = Database::getInstance();
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];

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
            sendJsonResponse(['success' => false, 'error' => 'Método não permitido'], 405);
    }
} catch (Exception $e) {
    sendJsonResponse(['success' => false, 'error' => $e->getMessage()], 500);
} catch (Error $e) {
    sendJsonResponse(['success' => false, 'error' => 'Erro fatal: ' . $e->getMessage()], 500);
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
        $id = (int)$id;
        if ($id <= 0) {
            sendJsonResponse(['success' => false, 'error' => 'ID inválido'], 400);
        }
        
        try {
            $fatura = $db->fetch("
                SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf, m.categoria_cnh, m.tipo_servico
                FROM financeiro_faturas f
                JOIN alunos a ON f.aluno_id = a.id
                LEFT JOIN matriculas m ON f.matricula_id = m.id
                WHERE f.id = ?
            ", [$id]);
            
            if (!$fatura) {
                sendJsonResponse(['success' => false, 'error' => 'Fatura não encontrada'], 404);
            }
            
            // Formatar resposta conforme especificado
            // Incluir tanto aluno_cpf quanto cpf para compatibilidade com o JavaScript
            $alunoCpf = $fatura['aluno_cpf'] ?? $fatura['cpf'] ?? '';
            $response = [
                'success' => true,
                'data' => [
                    'id' => (int)$fatura['id'],
                    'aluno_id' => (int)$fatura['aluno_id'],
                    'aluno_nome' => $fatura['aluno_nome'] ?? '',
                    'aluno_cpf' => $alunoCpf,
                    'cpf' => $alunoCpf, // Compatibilidade com JavaScript
                    'titulo' => $fatura['titulo'] ?? '',
                    'valor' => isset($fatura['valor_total']) ? (float)$fatura['valor_total'] : 0.00,
                    'valor_total' => isset($fatura['valor_total']) ? (float)$fatura['valor_total'] : 0.00,
                    'data_vencimento' => $fatura['data_vencimento'] ?? $fatura['vencimento'] ?? null,
                    'vencimento' => $fatura['data_vencimento'] ?? $fatura['vencimento'] ?? null, // Compatibilidade
                    'status' => $fatura['status'] ?? 'aberta',
                    'forma_pagamento' => $fatura['forma_pagamento'] ?? 'avista',
                    'observacoes' => $fatura['observacoes'] ?? null,
                    'matricula_id' => isset($fatura['matricula_id']) ? (int)$fatura['matricula_id'] : null,
                    'parcelas' => isset($fatura['parcelas']) ? (int)$fatura['parcelas'] : 1
                ]
            ];
            
            sendJsonResponse($response, 200);
        } catch (Exception $e) {
            sendJsonResponse(['success' => false, 'error' => 'Erro ao buscar fatura: ' . $e->getMessage()], 500);
        }
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
    
    sendJsonResponse([
        'success' => true,
        'data' => $faturas,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ], 200);
}

function handlePost($db, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        sendJsonResponse(['success' => false, 'error' => 'Dados inválidos'], 400);
    }
    
    // Validações obrigatórias
    $required = ['aluno_id', 'titulo', 'valor_total', 'data_vencimento'];
    foreach ($required as $field) {
        // Aceitar tanto data_vencimento quanto vencimento (compatibilidade)
        if ($field === 'data_vencimento' && empty($input['data_vencimento']) && empty($input['vencimento'])) {
            sendJsonResponse(['success' => false, 'error' => "Campo obrigatório: data_vencimento ou vencimento"], 400);
        } elseif ($field !== 'data_vencimento' && empty($input[$field])) {
            sendJsonResponse(['success' => false, 'error' => "Campo obrigatório: $field"], 400);
        }
    }
    
    // Validar aluno existe
    $aluno = $db->fetch('SELECT id, nome FROM alunos WHERE id = ?', [$input['aluno_id']]);
    if (!$aluno) {
        sendJsonResponse(['success' => false, 'error' => 'Aluno não encontrado'], 400);
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
    
    sendJsonResponse([
        'success' => true,
        'fatura_id' => $faturaId,
        'message' => 'Fatura criada com sucesso'
    ], 201);
}

function handlePut($db, $user) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID da fatura obrigatório'], 400);
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        sendJsonResponse(['success' => false, 'error' => 'Dados inválidos'], 400);
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch('SELECT * FROM financeiro_faturas WHERE id = ?', [$id]);
    if (!$fatura) {
        sendJsonResponse(['success' => false, 'error' => 'Fatura não encontrada'], 404);
    }
    
    // Se estiver tentando cancelar a fatura, validar regras de negócio
    if (isset($input['status']) && $input['status'] === 'cancelada') {
        // Não pode cancelar se já estiver cancelada
        if ($fatura['status'] === 'cancelada') {
            sendJsonResponse(['success' => false, 'error' => 'Esta fatura já está cancelada'], 400);
        }
        
        // Não pode cancelar se estiver paga ou parcial
        if (in_array($fatura['status'], ['paga', 'parcial'])) {
            sendJsonResponse(['success' => false, 'error' => 'Não é possível cancelar uma fatura que já possui pagamentos'], 400);
        }
        
        // Verificar se há pagamentos registrados
        $pagamentos = $db->fetchAll('SELECT id FROM pagamentos WHERE fatura_id = ?', [$id]);
        if (!empty($pagamentos)) {
            sendJsonResponse(['success' => false, 'error' => 'Não é possível cancelar fatura que já possui pagamentos registrados'], 400);
        }
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
        sendJsonResponse(['success' => false, 'error' => 'Nenhum campo para atualizar'], 400);
    }
    
    $updateData['atualizado_em'] = date('Y-m-d H:i:s');
    
    $db->update('financeiro_faturas', $updateData, 'id = ?', [$id]);
    
    // Atualizar status de inadimplência do aluno
    // COMENTADO: Colunas inadimplente e inadimplente_desde ainda não existem na tabela alunos
    // TODO: Reativar após criar migration para essas colunas
    // updateAlunoInadimplencia($db, $fatura['aluno_id']);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Fatura atualizada com sucesso'
    ], 200);
}

function handleDelete($db, $user) {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        sendJsonResponse(['success' => false, 'error' => 'ID da fatura obrigatório'], 400);
    }
    
    // Verificar se fatura existe
    $fatura = $db->fetch('SELECT * FROM financeiro_faturas WHERE id = ?', [$id]);
    if (!$fatura) {
        sendJsonResponse(['success' => false, 'error' => 'Fatura não encontrada'], 404);
    }
    
    // Verificar se pode ser excluída (apenas se aberta)
    if ($fatura['status'] !== 'aberta') {
        sendJsonResponse(['success' => false, 'error' => 'Apenas faturas abertas podem ser excluídas'], 400);
    }
    
    $db->delete('financeiro_faturas', 'id = ?', [$id]);
    
    // Atualizar status de inadimplência do aluno
    updateAlunoInadimplencia($db, $fatura['aluno_id']);
    
    sendJsonResponse([
        'success' => true,
        'message' => 'Fatura excluída com sucesso'
    ], 200);
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
