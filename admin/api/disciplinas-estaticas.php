<?php
/**
 * API de Disciplinas - Versão Estática
 * Sistema CFC Bom Conselho - Sem dependência de banco
 */

// Configurações básicas
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Verificar se é requisição OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Log de debug
error_log('[API Disciplinas Estática] Requisição: ' . $_SERVER['REQUEST_METHOD'] . ' - ' . ($_GET['action'] ?? 'sem ação'));

try {
    // Obter método da requisição
    $method = $_SERVER['REQUEST_METHOD'];
    $action = $_GET['action'] ?? '';
    
    if ($method === 'GET' && $action === 'listar') {
        error_log('[API Disciplinas Estática] Listando disciplinas estáticas...');
        
        // Disciplinas fixas (sem banco de dados)
        $disciplinas = [
            [
                'id' => 1,
                'nome' => 'Legislação de Trânsito',
                'carga_horaria' => 18,
                'descricao' => 'Normas e regulamentações do trânsito brasileiro',
                'ativa' => true,
                'cfc_id' => 1
            ],
            [
                'id' => 2,
                'nome' => 'Direção Defensiva',
                'carga_horaria' => 16,
                'descricao' => 'Técnicas de direção segura e preventiva',
                'ativa' => true,
                'cfc_id' => 1
            ],
            [
                'id' => 3,
                'nome' => 'Primeiros Socorros',
                'carga_horaria' => 4,
                'descricao' => 'Noções básicas de primeiros socorros',
                'ativa' => true,
                'cfc_id' => 1
            ],
            [
                'id' => 4,
                'nome' => 'Meio Ambiente e Cidadania',
                'carga_horaria' => 4,
                'descricao' => 'Consciência ambiental e cidadania no trânsito',
                'ativa' => true,
                'cfc_id' => 1
            ],
            [
                'id' => 5,
                'nome' => 'Mecânica Básica',
                'carga_horaria' => 3,
                'descricao' => 'Conhecimentos básicos sobre funcionamento do veículo',
                'ativa' => true,
                'cfc_id' => 1
            ]
        ];
        
        error_log('[API Disciplinas Estática] Retornando ' . count($disciplinas) . ' disciplinas');
        
        // Retornar resposta JSON
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'disciplinas' => $disciplinas
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        error_log('[API Disciplinas Estática] Ação não suportada: ' . $action);
        
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Ação não suportada'
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    error_log('[API Disciplinas Estática] Erro: ' . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
