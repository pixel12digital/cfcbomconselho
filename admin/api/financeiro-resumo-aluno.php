<?php
/**
 * API para obter resumo financeiro do aluno
 * Sistema CFC - Bom Conselho
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
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
require_once '../includes/FinanceiroService.php';

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
        echo json_encode(['success' => false, 'error' => 'Acesso negado']);
        exit;
    }
    
    // Obter aluno_id
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if (!$alunoId || !is_numeric($alunoId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do aluno não fornecido ou inválido']);
        exit;
    }
    
    // Calcular resumo financeiro
    $resumo = FinanceiroService::calcularResumoFinanceiroAluno((int)$alunoId);
    
    echo json_encode([
        'success' => true,
        'resumo' => $resumo
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ]);
    error_log("Erro em financeiro-resumo-aluno.php: " . $e->getMessage());
}

