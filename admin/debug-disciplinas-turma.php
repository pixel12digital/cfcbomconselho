<?php
/**
 * Debug - Verificar Disciplinas da Turma
 * Verifica se as disciplinas estão sendo salvas corretamente
 */

// Configuração do banco
require_once '../includes/Database.php';
require_once '../includes/TurmaTeoricaManager.php';

$db = new Database();
$turmaManager = new TurmaTeoricaManager($db);

$turmaId = isset($_GET['turma_id']) ? (int)$_GET['turma_id'] : 6;

echo "<h1>🔍 Debug - Disciplinas da Turma ID: $turmaId</h1>";

echo "<h2>1. Verificar se a tabela turmas_disciplinas existe:</h2>";
try {
    $result = $db->query("SHOW TABLES LIKE 'turmas_disciplinas'");
    if ($result->rowCount() > 0) {
        echo "✅ Tabela 'turmas_disciplinas' existe<br>";
    } else {
        echo "❌ Tabela 'turmas_disciplinas' NÃO existe<br>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao verificar tabela: " . $e->getMessage() . "<br>";
}

echo "<h2>2. Verificar estrutura da tabela turmas_disciplinas:</h2>";
try {
    $result = $db->query("DESCRIBE turmas_disciplinas");
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} catch (Exception $e) {
    echo "❌ Erro ao verificar estrutura: " . $e->getMessage() . "<br>";
}

echo "<h2>3. Verificar disciplinas na tabela turmas_disciplinas para turma $turmaId:</h2>";
try {
    $disciplinas = $db->fetchAll(
        "SELECT * FROM turmas_disciplinas WHERE turma_id = ?",
        [$turmaId]
    );
    
    if (empty($disciplinas)) {
        echo "❌ Nenhuma disciplina encontrada para turma $turmaId<br>";
    } else {
        echo "✅ Encontradas " . count($disciplinas) . " disciplinas:<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Turma ID</th><th>Disciplina ID</th><th>Nome Disciplina</th><th>Carga Horária</th><th>Ordem</th><th>Cor</th></tr>";
        foreach ($disciplinas as $disc) {
            echo "<tr>";
            echo "<td>" . $disc['id'] . "</td>";
            echo "<td>" . $disc['turma_id'] . "</td>";
            echo "<td>" . $disc['disciplina_id'] . "</td>";
            echo "<td>" . $disc['nome_disciplina'] . "</td>";
            echo "<td>" . $disc['carga_horaria_padrao'] . "</td>";
            echo "<td>" . $disc['ordem'] . "</td>";
            echo "<td>" . $disc['cor_hex'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar disciplinas: " . $e->getMessage() . "<br>";
}

echo "<h2>4. Testar função obterDisciplinasSelecionadas:</h2>";
try {
    $disciplinasSelecionadas = $turmaManager->obterDisciplinasSelecionadas($turmaId);
    
    if (empty($disciplinasSelecionadas)) {
        echo "❌ Função obterDisciplinasSelecionadas retornou array vazio<br>";
    } else {
        echo "✅ Função obterDisciplinasSelecionadas retornou " . count($disciplinasSelecionadas) . " disciplinas:<br>";
        echo "<pre>";
        print_r($disciplinasSelecionadas);
        echo "</pre>";
    }
} catch (Exception $e) {
    echo "❌ Erro na função obterDisciplinasSelecionadas: " . $e->getMessage() . "<br>";
}

echo "<h2>5. Verificar todas as turmas com disciplinas:</h2>";
try {
    $todasTurmas = $db->fetchAll(
        "SELECT DISTINCT turma_id, COUNT(*) as total_disciplinas 
         FROM turmas_disciplinas 
         GROUP BY turma_id 
         ORDER BY turma_id"
    );
    
    if (empty($todasTurmas)) {
        echo "❌ Nenhuma turma tem disciplinas cadastradas<br>";
    } else {
        echo "✅ Turmas com disciplinas:<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Turma ID</th><th>Total Disciplinas</th></tr>";
        foreach ($todasTurmas as $turma) {
            echo "<tr>";
            echo "<td>" . $turma['turma_id'] . "</td>";
            echo "<td>" . $turma['total_disciplinas'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar turmas: " . $e->getMessage() . "<br>";
}

echo "<h2>6. Verificar se há disciplinas na tabela disciplinas:</h2>";
try {
    $disciplinasDisponiveis = $db->fetchAll("SELECT * FROM disciplinas LIMIT 10");
    
    if (empty($disciplinasDisponiveis)) {
        echo "❌ Nenhuma disciplina disponível na tabela disciplinas<br>";
    } else {
        echo "✅ Disciplinas disponíveis (primeiras 10):<br>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Carga Horária</th><th>Ativa</th></tr>";
        foreach ($disciplinasDisponiveis as $disc) {
            echo "<tr>";
            echo "<td>" . $disc['id'] . "</td>";
            echo "<td>" . $disc['nome'] . "</td>";
            echo "<td>" . $disc['carga_horaria'] . "</td>";
            echo "<td>" . ($disc['ativa'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (Exception $e) {
    echo "❌ Erro ao buscar disciplinas disponíveis: " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>🔍 Conclusão:</strong> Este debug mostra exatamente onde está o problema com as disciplinas.</p>";
echo "<p><strong>📝 Próximos passos:</strong> Baseado nos resultados acima, vamos corrigir o problema.</p>";
?>
