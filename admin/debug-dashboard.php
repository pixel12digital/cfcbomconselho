<?php
// Debug do Dashboard
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🐛 Debug do Dashboard</h1>";

try {
    echo "<h2>1. Verificando includes...</h2>";
    require_once '../includes/config.php';
    echo "✅ config.php carregado<br>";
    
    require_once '../includes/database.php';
    echo "✅ database.php carregado<br>";
    
    require_once '../includes/auth.php';
    echo "✅ auth.php carregado<br>";
    
    echo "<h2>2. Verificando variáveis...</h2>";
    echo "APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NÃO DEFINIDO') . "<br>";
    echo "APP_VERSION: " . (defined('APP_VERSION') ? APP_VERSION : 'NÃO DEFINIDO') . "<br>";
    
    echo "<h2>3. Testando banco de dados...</h2>";
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    echo "<h2>4. Testando estatísticas...</h2>";
    $stats = [
        'total_alunos' => $db->count('alunos'),
        'total_instrutores' => $db->count('instrutores'),
        'total_aulas' => $db->count('aulas'),
        'total_veiculos' => $db->count('veiculos'),
        'aulas_hoje' => $db->count('aulas', 'data_aula = ?', [date('Y-m-d')]),
        'aulas_semana' => $db->count('aulas', 'data_aula >= ?', [date('Y-m-d', strtotime('monday this week'))])
    ];
    
    foreach ($stats as $key => $value) {
        echo "{$key}: {$value}<br>";
    }
    
    echo "<h2>5. Testando dashboard-simple.php...</h2>";
    ob_start();
    include 'pages/dashboard-simple.php';
    $content = ob_get_clean();
    echo "✅ dashboard-simple.php carregou: " . strlen($content) . " caracteres<br>";
    
    echo "<h2>6. Testando dashboard-test.php...</h2>";
    ob_start();
    include 'pages/dashboard-test.php';
    $content = ob_get_clean();
    echo "✅ dashboard-test.php carregou: " . strlen($content) . " caracteres<br>";
    
    echo "<h2>✅ Todos os testes passaram!</h2>";
    echo "<p><a href='index.php'>← Voltar para o Painel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro encontrado:</h2>";
    echo "<p><strong>Erro:</strong> " . $e->getMessage() . "</p>";
    echo "<p><strong>Arquivo:</strong> " . $e->getFile() . "</p>";
    echo "<p><strong>Linha:</strong> " . $e->getLine() . "</p>";
    echo "<p><strong>Stack trace:</strong></p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
