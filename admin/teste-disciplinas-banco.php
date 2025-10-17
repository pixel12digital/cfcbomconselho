<?php
/**
 * Teste específico para verificar disciplinas na tabela turmas_disciplinas
 */

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';

// Instanciar o gerenciador
$turmaManager = new TurmaTeoricaManager();
$db = Database::getInstance();

// ID da turma para teste
$turmaId = $_GET['turma_id'] ?? 6;

echo "<h1>🔍 Debug - Disciplinas da Turma ID: $turmaId</h1>";

echo "<h2>1. Verificar se a tabela turmas_disciplinas existe:</h2>";
try {
    $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turmas_disciplinas'");
    if ($tabelaExiste) {
        echo "<p style='color: green;'>✅ Tabela turmas_disciplinas existe</p>";
        
        // Verificar estrutura da tabela
        echo "<h3>Estrutura da tabela:</h3>";
        $estrutura = $db->fetchAll("DESCRIBE turmas_disciplinas");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($estrutura as $campo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($campo['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($campo['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($campo['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($campo['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($campo['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($campo['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Tabela turmas_disciplinas NÃO existe</p>";
        echo "<p><strong>Solução:</strong> A tabela precisa ser criada ou as disciplinas não foram salvas.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificar disciplinas na tabela turmas_disciplinas:</h2>";
try {
    $disciplinas = $db->fetchAll("SELECT * FROM turmas_disciplinas WHERE turma_id = ?", [$turmaId]);
    if ($disciplinas) {
        echo "<p style='color: green;'>✅ Encontradas " . count($disciplinas) . " disciplinas para a turma</p>";
        echo "<h3>Disciplinas encontradas:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Turma ID</th><th>Disciplina ID</th><th>Carga Horária</th><th>Ordem</th><th>Criado em</th></tr>";
        foreach ($disciplinas as $disciplina) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($disciplina['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['turma_id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['disciplina_id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['carga_horaria'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['ordem'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['created_at'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma disciplina encontrada para a turma $turmaId</p>";
        echo "<p><strong>Possível causa:</strong> As disciplinas não foram salvas quando a turma foi criada.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar disciplinas: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificar disciplinas disponíveis:</h2>";
try {
    $disciplinasDisponiveis = $db->fetchAll("SELECT * FROM disciplinas ORDER BY nome");
    if ($disciplinasDisponiveis) {
        echo "<p style='color: green;'>✅ Encontradas " . count($disciplinasDisponiveis) . " disciplinas disponíveis</p>";
        echo "<h3>Disciplinas disponíveis:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Carga Horária Padrão</th><th>Descrição</th></tr>";
        foreach ($disciplinasDisponiveis as $disciplina) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($disciplina['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['nome'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['carga_horaria_padrao'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['descricao'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Nenhuma disciplina disponível no sistema</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar disciplinas disponíveis: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testar função obterDisciplinasSelecionadas:</h2>";
try {
    $disciplinasSelecionadas = $turmaManager->obterDisciplinasSelecionadas($turmaId);
    if ($disciplinasSelecionadas) {
        echo "<p style='color: green;'>✅ Função retornou " . count($disciplinasSelecionadas) . " disciplinas</p>";
        echo "<h3>Dados retornados pela função:</h3>";
        echo "<pre>" . print_r($disciplinasSelecionadas, true) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Função retornou array vazio</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na função obterDisciplinasSelecionadas: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Verificar se há dados na turma:</h2>";
try {
    $turma = $db->fetch("SELECT * FROM turmas_teoricas WHERE id = ?", [$turmaId]);
    if ($turma) {
        echo "<p style='color: green;'>✅ Turma encontrada: " . htmlspecialchars($turma['nome']) . "</p>";
        echo "<h3>Dados da turma:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Valor</th></tr>";
        foreach ($turma as $campo => $valor) {
            echo "<tr><td><strong>$campo</strong></td><td>" . htmlspecialchars($valor ?? 'NULL') . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Turma com ID $turmaId não encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar turma: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Conclusão:</h2>";
echo "<p><strong>Se não há disciplinas na tabela turmas_disciplinas:</strong> As disciplinas não foram salvas quando a turma foi criada. É necessário salvar as disciplinas primeiro.</p>";
echo "<p><strong>Se há disciplinas mas não aparecem na edição:</strong> Problema na função de carregamento JavaScript.</p>";
echo "<p><strong>Se não há disciplinas disponíveis:</strong> É necessário cadastrar disciplinas no sistema primeiro.</p>";
?>