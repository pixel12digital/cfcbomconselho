<?php
// Teste simples do painel admin
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

echo "<h1>🧪 Teste do Painel Admin</h1>";

try {
    // Teste de conexão
    echo "<h2>1. Teste de Conexão</h2>";
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Teste de contagem
    echo "<h2>2. Teste de Contagem</h2>";
    $total_alunos = $db->count('alunos');
    $total_instrutores = $db->count('instrutores');
    $total_aulas = $db->count('aulas');
    $total_veiculos = $db->count('veiculos');
    
    echo "Total de alunos: {$total_alunos}<br>";
    echo "Total de instrutores: {$total_instrutores}<br>";
    echo "Total de aulas: {$total_aulas}<br>";
    echo "Total de veículos: {$total_veiculos}<br>";
    
    // Teste de estatísticas
    echo "<h2>3. Teste de Estatísticas</h2>";
    $aulas_hoje = $db->count('aulas', 'data_aula = ?', [date('Y-m-d')]);
    $aulas_semana = $db->count('aulas', 'data_aula >= ?', [date('Y-m-d', strtotime('monday this week'))]);
    
    echo "Aulas hoje: {$aulas_hoje}<br>";
    echo "Aulas da semana: {$aulas_semana}<br>";
    
    // Teste de configurações
    echo "<h2>4. Teste de Configurações</h2>";
    echo "Nome da aplicação: " . APP_NAME . "<br>";
    echo "Versão: " . APP_VERSION . "<br>";
    echo "Log habilitado: " . (LOG_ENABLED ? 'Sim' : 'Não') . "<br>";
    
    echo "<h2>✅ Todos os testes passaram!</h2>";
    echo "<p><a href='index.php'>Ir para o Painel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro nos testes</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>
