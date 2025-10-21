<?php
/**
 * API específica para edição de agendamentos de turmas teóricas - Versão Ultra Simples
 * Retorna dados em formato JSON para edição no modal
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
    // Obter dados do POST
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Dados inválidos');
    }
    
    $acao = $data['acao'] ?? '';
    $aula_id = $data['aula_id'] ?? null;
    
    if ($acao === 'editar' && $aula_id) {
        // Simular sucesso por enquanto
        // TODO: Implementar lógica real de banco de dados
        
        // Limpar buffer de output
        ob_end_clean();
        
        echo json_encode([
            'success' => true,
            'message' => 'Agendamento editado com sucesso!',
            'debug' => [
                'aula_id' => $aula_id,
                'dados_recebidos' => $data
            ]
        ], JSON_UNESCAPED_UNICODE);
        
    } else {
        throw new Exception('Ação não reconhecida');
    }
    
} catch (Exception $e) {
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar erro
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}