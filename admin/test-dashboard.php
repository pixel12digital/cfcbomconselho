<?php
// Teste simples do dashboard
require_once '../includes/config.php';
require_once '../includes/database.php';

echo "<h1>🧪 Teste do Dashboard</h1>";

try {
    $db = Database::getInstance();
    
    // Simular estatísticas
    $stats = [
        'total_alunos' => $db->count('alunos'),
        'total_instrutores' => $db->count('instrutores'),
        'total_aulas' => $db->count('aulas'),
        'total_veiculos' => $db->count('veiculos'),
        'aulas_hoje' => $db->count('aulas', 'data_aula = ?', [date('Y-m-d')]),
        'aulas_semana' => $db->count('aulas', 'data_aula >= ?', [date('Y-m-d', strtotime('monday this week'))])
    ];
    
    echo "<h2>Estatísticas:</h2>";
    foreach ($stats as $key => $value) {
        echo "- {$key}: {$value}<br>";
    }
    
    // Testar dashboard
    echo "<h2>Testando Dashboard:</h2>";
    ob_start();
    include 'pages/dashboard-simple.php';
    $dashboard_content = ob_get_clean();
    
    if (!empty($dashboard_content)) {
        echo "✅ Dashboard carregou com sucesso!<br>";
        echo "Tamanho do conteúdo: " . strlen($dashboard_content) . " caracteres<br>";
    } else {
        echo "❌ Dashboard não carregou conteúdo<br>";
    }
    
    echo "<h2>✅ Teste Concluído!</h2>";
    echo "<p><a href='index.php'>Ir para o Painel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro no teste</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
}
?>
