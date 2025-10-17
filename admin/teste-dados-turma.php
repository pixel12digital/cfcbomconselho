<?php
/**
 * Teste para verificar os dados da turma no banco de dados
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

echo "<h1>🔍 Debug - Dados da Turma ID: $turmaId</h1>";

echo "<h2>1. Verificar se a turma existe:</h2>";
try {
    $turma = $db->fetch("SELECT * FROM turmas_teoricas WHERE id = ?", [$turmaId]);
    if ($turma) {
        echo "<p style='color: green;'>✅ Turma encontrada</p>";
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

echo "<h2>2. Testar função obterTurma do TurmaTeoricaManager:</h2>";
try {
    $resultadoTurma = $turmaManager->obterTurma($turmaId);
    if ($resultadoTurma['sucesso']) {
        echo "<p style='color: green;'>✅ Função obterTurma retornou sucesso</p>";
        echo "<h3>Dados retornados:</h3>";
        echo "<pre>" . print_r($resultadoTurma['dados'], true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Função obterTurma falhou: " . $resultadoTurma['mensagem'] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na função obterTurma: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificar disciplinas selecionadas:</h2>";
try {
    $disciplinasSelecionadas = $turmaManager->obterDisciplinasSelecionadas($turmaId);
    if ($disciplinasSelecionadas) {
        echo "<p style='color: green;'>✅ Encontradas " . count($disciplinasSelecionadas) . " disciplinas selecionadas</p>";
        echo "<h3>Disciplinas:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Disciplina ID</th><th>Nome</th><th>Carga Horária</th><th>Ordem</th></tr>";
        foreach ($disciplinasSelecionadas as $disciplina) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($disciplina['id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['disciplina_id'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['nome_original'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['carga_horaria_padrao'] ?? '') . "</td>";
            echo "<td>" . htmlspecialchars($disciplina['ordem'] ?? '') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma disciplina selecionada encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar disciplinas: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Verificar se há rascunho:</h2>";
try {
    $rascunho = $turmaManager->obterRascunho($turmaId);
    if ($rascunho['sucesso'] && $rascunho['dados']) {
        echo "<p style='color: green;'>✅ Rascunho encontrado</p>";
        echo "<h3>Dados do rascunho:</h3>";
        echo "<pre>" . print_r($rascunho['dados'], true) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhum rascunho encontrado</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar rascunho: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Verificar estrutura das tabelas:</h2>";
try {
    echo "<h3>Tabela turmas_teoricas:</h3>";
    $estruturaTurmas = $db->fetchAll("DESCRIBE turmas_teoricas");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    foreach ($estruturaTurmas as $campo) {
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
    
    echo "<h3>Tabela turmas_disciplinas:</h3>";
    $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turmas_disciplinas'");
    if ($tabelaExiste) {
        $estruturaDisciplinas = $db->fetchAll("DESCRIBE turmas_disciplinas");
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($estruturaDisciplinas as $campo) {
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
        echo "<p style='color: red;'>❌ Tabela turmas_disciplinas não existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar estrutura: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>📋 Resumo:</h2>";
echo "<p><strong>Se a turma existe mas as datas não aparecem:</strong> Problema no JavaScript ou no carregamento dos dados</p>";
echo "<p><strong>Se a turma não existe:</strong> Problema no banco de dados ou ID incorreto</p>";
echo "<p><strong>Se não há disciplinas:</strong> As disciplinas não foram salvas ou a tabela não existe</p>";
?>
