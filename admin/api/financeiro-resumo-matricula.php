<?php
/**
 * API para obter resumo financeiro da matrícula (detalhado para contrato)
 * Sistema CFC - Bom Conselho
 * 
 * Retorna informações detalhadas do financeiro do aluno para exibição na aba Matrícula
 * e uso na geração de contrato.
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
    
    // FECHAR SESSÃO APÓS AUTENTICAÇÃO para evitar bloqueio de sessão em requisições simultâneas
    // Isso permite que múltiplas requisições AJAX sejam processadas em paralelo
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }
    
    // Obter aluno_id
    $alunoId = $_GET['aluno_id'] ?? null;
    
    if (!$alunoId || !is_numeric($alunoId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'ID do aluno não fornecido ou inválido']);
        exit;
    }
    
    $db = Database::getInstance();
    
    // Buscar todas as faturas não canceladas do aluno (limitado para performance)
    $faturas = $db->fetchAll("
        SELECT 
            id,
            titulo,
            valor_total,
            data_vencimento,
            status,
            forma_pagamento,
            matricula_id
        FROM financeiro_faturas
        WHERE aluno_id = ?
        AND status != 'cancelada'
        ORDER BY data_vencimento ASC
        LIMIT 500
    ", [$alunoId]);
    
    // Se não houver faturas, retornar estado vazio
    if (empty($faturas)) {
        echo json_encode([
            'success' => true,
            'tem_financeiro' => false,
            'resumo' => null
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
    
    // Calcular informações detalhadas
    $valorTotal = 0.0;
    $valorEntrada = 0.0;
    $qtdParcelas = 0;
    $valorParcela = 0.0;
    $formasPagamento = [];
    $primeiroVencimento = null;
    
    foreach ($faturas as $fatura) {
        $valor = (float)($fatura['valor_total'] ?? 0);
        $valorTotal += $valor;
        
        // Identificar entrada (fatura com título contendo "Entrada" ou primeira fatura)
        $titulo = strtolower($fatura['titulo'] ?? '');
        if (stripos($titulo, 'entrada') !== false || $primeiroVencimento === null) {
            // Se for a primeira fatura ou contiver "entrada", considerar como entrada
            if ($primeiroVencimento === null) {
                $valorEntrada = $valor;
            }
        }
        
        // Contar parcelas (faturas que não são entrada)
        if (stripos($titulo, 'entrada') === false) {
            $qtdParcelas++;
            if ($valorParcela == 0 && $valor > 0) {
                $valorParcela = $valor; // Usar valor da primeira parcela como referência
            }
        }
        
        // Coletar formas de pagamento
        $formaPagamento = $fatura['forma_pagamento'] ?? null;
        if ($formaPagamento) {
            if (!isset($formasPagamento[$formaPagamento])) {
                $formasPagamento[$formaPagamento] = 0;
            }
            $formasPagamento[$formaPagamento]++;
        }
        
        // Primeiro vencimento (menor data_vencimento)
        $dataVencimento = $fatura['data_vencimento'] ?? null;
        if ($dataVencimento) {
            if ($primeiroVencimento === null || $dataVencimento < $primeiroVencimento) {
                $primeiroVencimento = $dataVencimento;
            }
        }
    }
    
    // Determinar forma de pagamento predominante
    $formaPagamentoPredominante = null;
    if (!empty($formasPagamento)) {
        arsort($formasPagamento);
        $formaPagamentoPredominante = array_key_first($formasPagamento);
    }
    
    // Mapear forma de pagamento para texto legível
    $mapFormaPagamento = [
        'avista' => 'À vista',
        'a_vista' => 'À vista',
        'cartao_credito' => 'Cartão de crédito',
        'boleto' => 'Boleto',
        'pix' => 'PIX',
        'carne_parcelado' => 'Carnê / Parcelado',
        'parcelado' => 'Parcelado',
        'outro' => 'Outro',
        'nao_informado' => 'Não informado'
    ];
    
    $formaPagamentoTexto = $mapFormaPagamento[$formaPagamentoPredominante] ?? $formaPagamentoPredominante ?? 'Não informado';
    
    // Obter resumo básico do FinanceiroService
    $resumoBasico = FinanceiroService::calcularResumoFinanceiroAluno((int)$alunoId);
    
    // Montar resposta
    $resumo = [
        'valor_total' => round($valorTotal, 2),
        'valor_entrada' => round($valorEntrada, 2),
        'tem_entrada' => $valorEntrada > 0,
        'qtd_parcelas' => $qtdParcelas,
        'valor_parcela' => round($valorParcela, 2),
        'forma_pagamento' => $formaPagamentoPredominante,
        'forma_pagamento_texto' => $formaPagamentoTexto,
        'primeiro_vencimento' => $primeiroVencimento,
        'qtd_faturas' => count($faturas),
        'status_financeiro' => $resumoBasico['status_financeiro'] ?? 'nao_lancado'
    ];
    
    echo json_encode([
        'success' => true,
        'tem_financeiro' => true,
        'resumo' => $resumo
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
    if (function_exists('error_log')) {
        error_log("Erro em financeiro-resumo-matricula.php: " . $e->getMessage());
    }
}

