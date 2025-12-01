<?php
/**
 * API para obter resumo financeiro do aluno
 * Sistema CFC - Bom Conselho
 */

// Desabilitar exibição de erros na saída (para não quebrar JSON)
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Função para garantir resposta JSON mesmo em caso de erro fatal
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Erro fatal no servidor: ' . $error['message']
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
});

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

// Verificar se FinanceiroService existe antes de incluir
$financeiroServicePath = __DIR__ . '/../includes/FinanceiroService.php';
if (!file_exists($financeiroServicePath)) {
    // Tentar caminho alternativo (caso o arquivo esteja em outro local)
    $financeiroServicePathAlt = __DIR__ . '/../../admin/includes/FinanceiroService.php';
    if (file_exists($financeiroServicePathAlt)) {
        $financeiroServicePath = $financeiroServicePathAlt;
    }
}

if (file_exists($financeiroServicePath)) {
    require_once $financeiroServicePath;
} else {
    // Se não existir, retornar erro JSON em vez de causar erro PHP
    http_response_code(503);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Serviço financeiro não disponível (FinanceiroService.php não encontrado)'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = Database::getInstance();
    
    // Verificar se sistema financeiro está habilitado (opcional - não bloquear se não estiver definido)
    if (defined('FINANCEIRO_ENABLED') && !FINANCEIRO_ENABLED) {
        http_response_code(503);
        echo json_encode(['success' => false, 'error' => 'Sistema financeiro desabilitado'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
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
    
    // Verificar se a classe FinanceiroService existe
    if (!class_exists('FinanceiroService')) {
        http_response_code(503);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => 'Serviço financeiro não disponível (classe FinanceiroService não encontrada)'
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // Calcular resumo financeiro
    $resumo = FinanceiroService::calcularResumoFinanceiroAluno((int)$alunoId);
    
    echo json_encode([
        'success' => true,
        'resumo' => $resumo
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Garantir que sempre retorne JSON, mesmo em caso de erro
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (function_exists('error_log')) {
        error_log("Erro em financeiro-resumo-aluno.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
} catch (Error $e) {
    // Capturar erros fatais do PHP 7+ (ex: Call to undefined method)
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => false,
        'error' => 'Erro fatal: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (function_exists('error_log')) {
        error_log("Erro fatal em financeiro-resumo-aluno.php: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
    }
}

