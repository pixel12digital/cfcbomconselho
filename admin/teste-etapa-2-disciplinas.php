<?php
/**
 * Script para testar se disciplinas aparecem na etapa 2
 */

echo "<h2>🧪 Teste - Disciplinas na Etapa 2</h2>";
echo "<pre>";

try {
    // Incluir configuração
    require_once __DIR__ . '/../includes/config.php';
    require_once __DIR__ . '/includes/TurmaTeoricaManager.php';
    
    // Conectar ao banco
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_TIMEOUT => 30
        ]
    );
    
    echo "✅ Conectado ao banco!\n\n";
    
    // Criar instância do TurmaTeoricaManager
    $turmaManager = new TurmaTeoricaManager($pdo);
    
    // Testar método obterDisciplinasParaAgendamento
    echo "🔍 Testando obterDisciplinasParaAgendamento para turma 6...\n";
    $disciplinas = $turmaManager->obterDisciplinasParaAgendamento(6);
    
    if (empty($disciplinas)) {
        echo "❌ Nenhuma disciplina retornada!\n";
    } else {
        echo "✅ Retornadas " . count($disciplinas) . " disciplinas:\n";
        foreach ($disciplinas as $disc) {
            $horas = $disc['aulas_obrigatorias'] ?? $disc['carga_horaria_padrao'] ?? 'N/A';
            echo "  - {$disc['nome_disciplina']} ({$horas}h)\n";
        }
    }
    
    echo "\n🔍 Testando obterProgressoDisciplinas para turma 6...\n";
    $progresso = $turmaManager->obterProgressoDisciplinas(6);
    
    if (empty($progresso)) {
        echo "❌ Nenhum progresso retornado!\n";
    } else {
        echo "✅ Retornado progresso para " . count($progresso) . " disciplinas:\n";
        foreach ($progresso as $disc) {
            echo "  - {$disc['nome_disciplina']}: {$disc['aulas_agendadas']}/{$disc['aulas_obrigatorias']} aulas (Status: {$disc['status_disciplina']})\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Botões de teste
echo '<br>';
echo '<a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=6" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🧪 Testar Etapa 2</a>';
echo '<a href="?page=turmas-teoricas&acao=detalhes&turma_id=6" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🔍 Ver Detalhes</a>';
echo '<a href="verificar-disciplinas-turma.php" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;">📊 Verificar Banco</a>';
?>
