<?php
// Script de teste para verificar exclusão do usuário ID=1
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "=== TESTE DE EXCLUSÃO DO USUÁRIO ID=1 ===\n\n";

// Simular requisição DELETE para a API
$url = 'http://localhost/cfc-bom-conselho/admin/api/usuarios.php?id=1';
$method = 'DELETE';

// Configurar contexto da requisição
$context = stream_context_create([
    'http' => [
        'method' => $method,
        'header' => [
            'Content-Type: application/json',
            'User-Agent: Test-Script'
        ]
    ]
]);

echo "Enviando requisição DELETE para: $url\n\n";

// Fazer a requisição
$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "❌ Erro ao fazer requisição HTTP\n";
    echo "Verifique se o servidor está rodando e acessível\n";
} else {
    echo "✅ Resposta recebida:\n";
    echo "----------------------------------------\n";
    echo $response . "\n";
    echo "----------------------------------------\n\n";
    
    // Decodificar JSON para melhor visualização
    $data = json_decode($response, true);
    if ($data) {
        echo "📋 Resposta decodificada:\n";
        echo "Status: " . (isset($data['success']) ? 'Sucesso' : 'Erro') . "\n";
        
        if (isset($data['error'])) {
            echo "Erro: " . $data['error'] . "\n";
            echo "Código: " . $data['code'] . "\n";
            
            if (isset($data['dependencias'])) {
                echo "\n🔍 Dependências encontradas:\n";
                foreach ($data['dependencias'] as $dep) {
                    echo "• {$dep['tipo']}: {$dep['quantidade']} registro(s)\n";
                    echo "  Instrução: {$dep['instrucao']}\n";
                }
            }
            
            if (isset($data['instrucoes'])) {
                echo "\n📝 Instruções para resolver:\n";
                foreach ($data['instrucoes'] as $i => $instrucao) {
                    echo ($i + 1) . ". $instrucao\n";
                }
            }
        } else {
            echo "Mensagem: " . $data['message'] . "\n";
        }
    } else {
        echo "❌ Erro ao decodificar JSON da resposta\n";
    }
}

echo "\n=== FIM DO TESTE ===\n";
?>
