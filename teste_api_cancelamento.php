<?php
require_once 'includes/config.php';
require_once 'includes/database.php';
require_once 'includes/auth.php';

// Simular sessão completa para teste
session_start();
$_SESSION['user_id'] = 18; // ID do Administrador
$_SESSION['user_type'] = 'admin';
$_SESSION['last_activity'] = time();

echo "=== TESTE DA API DE CANCELAMENTO ===\n\n";

// Simular dados POST para cancelar aula
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST = json_encode(['acao' => 'cancelar', 'aula_id' => 12]);

// Capturar output da API
ob_start();

// Simular chamada da API
try {
    // Incluir a API
    include 'admin/api/agendamento.php';
    
    $output = ob_get_clean();
    
    echo "✅ Resposta da API:\n";
    echo $output . "\n\n";
    
    // Verificar se é JSON válido
    $json = json_decode($output, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "✅ JSON válido!\n";
        echo "   Sucesso: " . ($json['sucesso'] ? 'true' : 'false') . "\n";
        echo "   Mensagem: " . $json['mensagem'] . "\n";
    } else {
        echo "❌ JSON inválido!\n";
        echo "   Erro: " . json_last_error_msg() . "\n";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "❌ Erro no teste: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
