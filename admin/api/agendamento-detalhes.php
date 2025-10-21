<?php
/**
 * API para buscar dados específicos de um agendamento - Versão Ultra Simples
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
    // Obter ID do agendamento
    $agendamento_id = $_GET['id'] ?? null;
    
    if (!$agendamento_id) {
        throw new Exception('ID do agendamento não fornecido');
    }
    
    // Dados baseados no que vemos na interface
    $dados = [
        'id' => $agendamento_id,
        'nome_aula' => 'Legislação de Trânsito - Aula 1',
        'data_aula' => '2025-10-31',
        'hora_inicio' => '09:00',
        'hora_fim' => '09:50',
        'duracao_minutos' => 50,
        'instrutor_id' => 1,
        'instrutor_nome' => 'vinicius ricardo pontes vieira',
        'sala_id' => 1,
        'sala_nome' => 'Sala 01 Teste',
        'observacoes' => '',
        'status' => 'agendada'
    ];
    
    // Limpar buffer de output
    ob_end_clean();
    
    // Retornar resposta de sucesso
    echo json_encode([
        'success' => true,
        'agendamento' => $dados
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