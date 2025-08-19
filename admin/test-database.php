<?php
// Teste robusto da estrutura do banco
require_once '../includes/config.php';
require_once '../includes/database.php';

echo "<h1>🧪 Teste da Estrutura do Banco</h1>";

try {
    // Teste de conexão
    echo "<h2>1. Teste de Conexão</h2>";
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Verificar tabelas existentes
    echo "<h2>2. Verificação de Tabelas</h2>";
    $tables = ['usuarios', 'cfcs', 'alunos', 'instrutores', 'aulas', 'veiculos', 'sessoes', 'logs'];
    
    foreach ($tables as $table) {
        try {
            $count = $db->count($table);
            echo "✅ Tabela {$table}: {$count} registros<br>";
        } catch (Exception $e) {
            echo "❌ Tabela {$table}: " . $e->getMessage() . "<br>";
        }
    }
    
    // Teste de estrutura da tabela aulas
    echo "<h2>3. Estrutura da Tabela Aulas</h2>";
    try {
        $columns = $db->fetchAll("DESCRIBE aulas");
        echo "Colunas da tabela aulas:<br>";
        foreach ($columns as $column) {
            echo "- {$column['Field']} ({$column['Type']})<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "<br>";
    }
    
    // Teste de contagem com filtros
    echo "<h2>4. Teste de Contagem com Filtros</h2>";
    try {
        $aulas_hoje = $db->count('aulas', 'data_aula = ?', [date('Y-m-d')]);
        echo "✅ Aulas hoje: {$aulas_hoje}<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao contar aulas hoje: " . $e->getMessage() . "<br>";
    }
    
    try {
        $aulas_semana = $db->count('aulas', 'data_aula >= ?', [date('Y-m-d', strtotime('monday this week'))]);
        echo "✅ Aulas da semana: {$aulas_semana}<br>";
    } catch (Exception $e) {
        echo "❌ Erro ao contar aulas da semana: " . $e->getMessage() . "<br>";
    }
    
    // Teste de query complexa
    echo "<h2>5. Teste de Query Complexa</h2>";
    try {
        $atividades = $db->fetchAll("
            SELECT 'aluno' as tipo, nome, 'cadastrado' as acao, criado_em as data
            FROM alunos 
            ORDER BY criado_em DESC 
            LIMIT 3
        ");
        echo "✅ Query de alunos executada: " . count($atividades) . " registros<br>";
    } catch (Exception $e) {
        echo "❌ Erro na query de alunos: " . $e->getMessage() . "<br>";
    }
    
    echo "<h2>✅ Teste Concluído!</h2>";
    echo "<p><a href='index.php'>Ir para o Painel Admin</a></p>";
    
} catch (Exception $e) {
    echo "<h2>❌ Erro Fatal</h2>";
    echo "<p>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>
