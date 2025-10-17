<?php
/**
 * Teste para verificar se obterDisciplinasSelecionadas está funcionando
 */

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../includes/TurmaTeoricaManager.php';

// Instanciar o gerenciador
$turmaManager = new TurmaTeoricaManager();
$db = Database::getInstance();

// ID da turma para teste
$turmaId = 6;

echo "<h1>🔧 Teste - obterDisciplinasSelecionadas</h1>";

echo "<h2>1. Verificar se a tabela turmas_disciplinas existe:</h2>";
try {
    $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turmas_disciplinas'");
    if ($tabelaExiste) {
        echo "<p style='color: green;'>✅ Tabela turmas_disciplinas existe</p>";
    } else {
        echo "<p style='color: red;'>❌ Tabela turmas_disciplinas NÃO existe</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar tabela: " . $e->getMessage() . "</p>";
}

echo "<h2>2. Verificar dados da turma:</h2>";
try {
    $turma = $db->fetch("SELECT * FROM turmas_teoricas WHERE id = ?", [$turmaId]);
    if ($turma) {
        echo "<p style='color: green;'>✅ Turma encontrada: " . htmlspecialchars($turma['nome']) . "</p>";
        echo "<pre>" . print_r($turma, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Turma com ID $turmaId não encontrada</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar turma: " . $e->getMessage() . "</p>";
}

echo "<h2>3. Verificar disciplinas na tabela turmas_disciplinas:</h2>";
try {
    $disciplinas = $db->fetchAll("SELECT * FROM turmas_disciplinas WHERE turma_id = ?", [$turmaId]);
    if ($disciplinas) {
        echo "<p style='color: green;'>✅ Encontradas " . count($disciplinas) . " disciplinas para a turma</p>";
        echo "<pre>" . print_r($disciplinas, true) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Nenhuma disciplina encontrada para a turma $turmaId</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar disciplinas: " . $e->getMessage() . "</p>";
}

echo "<h2>4. Testar função obterDisciplinasSelecionadas:</h2>";
try {
    $disciplinasSelecionadas = $turmaManager->obterDisciplinasSelecionadas($turmaId);
    if ($disciplinasSelecionadas) {
        echo "<p style='color: green;'>✅ Função retornou " . count($disciplinasSelecionadas) . " disciplinas</p>";
        echo "<pre>" . print_r($disciplinasSelecionadas, true) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Função retornou array vazio</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro na função obterDisciplinasSelecionadas: " . $e->getMessage() . "</p>";
}

echo "<h2>5. Verificar disciplinas disponíveis:</h2>";
try {
    $disciplinasDisponiveis = $db->fetchAll("SELECT * FROM disciplinas ORDER BY nome");
    if ($disciplinasDisponiveis) {
        echo "<p style='color: green;'>✅ Encontradas " . count($disciplinasDisponiveis) . " disciplinas disponíveis</p>";
        echo "<pre>" . print_r($disciplinasDisponiveis, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Nenhuma disciplina disponível no sistema</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao buscar disciplinas disponíveis: " . $e->getMessage() . "</p>";
}

echo "<h2>6. Verificar estrutura da tabela turmas_disciplinas:</h2>";
try {
    $estrutura = $db->fetchAll("DESCRIBE turmas_disciplinas");
    if ($estrutura) {
        echo "<p style='color: green;'>✅ Estrutura da tabela:</p>";
        echo "<pre>" . print_r($estrutura, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ Não foi possível obter estrutura da tabela</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao obter estrutura: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Conclusão:</strong> Se não há disciplinas na tabela turmas_disciplinas, isso explica por que elas não aparecem na edição. As disciplinas precisam ser salvas primeiro.</p>";
?>
