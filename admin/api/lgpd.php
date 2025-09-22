<?php
/**
 * API para gerenciamento de LGPD (Consentimento)
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
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if ($alunoId) {
        // Buscar status LGPD de um aluno específico
        $aluno = $db->fetch("
            SELECT id, nome, cpf, lgpd_consentido, lgpd_consentido_em 
            FROM alunos 
            WHERE id = ?
        ", [$alunoId]);
        
        if (!$aluno) {
            http_response_code(404);
            echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
            return;
        }
        
        echo json_encode(['success' => true, 'aluno' => $aluno]);
    } else {
        // Listar alunos com status LGPD
        $alunos = $db->fetchAll("
            SELECT id, nome, cpf, lgpd_consentido, lgpd_consentido_em 
            FROM alunos 
            ORDER BY nome ASC
        ");
        
        echo json_encode(['success' => true, 'alunos' => $alunos]);
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
    $required = ['aluno_id', 'consentido'];
    foreach ($required as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "Campo obrigatório: $field"]);
            return;
        }
    }
    
    $alunoId = $input['aluno_id'];
    $consentido = (bool)$input['consentido'];
    
    // Verificar se aluno existe
    $aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$alunoId]);
    if (!$aluno) {
        http_response_code(404);
        echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
        return;
    }
    
    // Atualizar consentimento LGPD
    $dadosAtualizacao = [
        'lgpd_consentido' => $consentido ? 1 : 0
    ];
    
    if ($consentido) {
        $dadosAtualizacao['lgpd_consentido_em'] = date('Y-m-d H:i:s');
    } else {
        $dadosAtualizacao['lgpd_consentido_em'] = null;
    }
    
    $db->update('alunos', $dadosAtualizacao, 'id = ?', [$alunoId]);
    
    $mensagem = $consentido ? 'Consentimento LGPD registrado com sucesso' : 'Consentimento LGPD removido com sucesso';
    
    echo json_encode([
        'success' => true, 
        'message' => $mensagem,
        'consentido' => $consentido,
        'data_consentimento' => $consentido ? $dadosAtualizacao['lgpd_consentido_em'] : null
    ]);
}

