<?php
// Teste simples da API de instrutores

echo "=== TESTE DA API DE INSTRUTORES ===\n";

// Simular ambiente
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/cfc-bom-conselho/admin/api/instrutores.php';

// Simular session
session_start();
$_SESSION['user_id'] = 18;
$_SESSION['user_email'] = 'admin@test.com';
$_SESSION['user_name'] = 'Administrador';
$_SESSION['user_type'] = 'admin';
$_SESSION['user_cfc_id'] = 1;

echo "Session simulada:\n";
print_r($_SESSION);

// Incluir a API
try {
    ob_start();
    include 'admin/api/instrutores.php';
    $output = ob_get_clean();
    
    echo "\n=== RESPOSTA DA API ===\n";
    echo $output;
    
} catch (Exception $e) {
    echo "\n=== ERRO ===\n";
    echo "Erro: " . $e->getMessage() . "\n";
}

echo "\n=== TESTE CONCLUÍDO ===\n";
?>
