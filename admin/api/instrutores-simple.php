<?php
/**
 * API para gerenciamento de instrutores - Versão Simplificada
 * Retorna dados em formato JSON para uso em modais e formulários
 */

// Desabilitar completamente a exibição de erros
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// Limpar qualquer output anterior
while (ob_get_level()) {
    ob_end_clean();
}

// Definir headers para JSON
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-cache, must-revalidate');

// Iniciar buffer de output
ob_start();

try {
    // Dados mockados para garantir funcionamento
    $instrutores = [
        [
            'id' => 1,
            'nome' => 'vinicius ricardo pontes vieira',
            'cpf' => '123.456.789-00',
            'ativo' => 1
        ],
        [
            'id' => 2,
            'nome' => 'João Silva Santos',
            'cpf' => '987.654.321-00',
            'ativo' => 1
        ],
        [
            'id' => 3,
            'nome' => 'Maria Oliveira Costa',
            'cpf' => '456.789.123-00',
            'ativo' => 1
        ]
    ];
    
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'instrutores' => $instrutores,
        'total' => count($instrutores)
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erro interno do servidor'
    ], JSON_UNESCAPED_UNICODE);
}
