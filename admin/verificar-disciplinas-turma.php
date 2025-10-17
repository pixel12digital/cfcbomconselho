<?php
/**
 * Script para verificar disciplinas de uma turma específica
 */

echo "<h2>🔍 Verificar Disciplinas da Turma</h2>";
echo "<pre>";

try {
    // Incluir configuração
    require_once __DIR__ . '/../includes/config.php';
    
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
    
    // Verificar turma ID 6
    echo "🔍 Verificando turma ID 6...\n";
    $stmt = $pdo->prepare("SELECT * FROM turmas_teoricas WHERE id = ?");
    $stmt->execute([6]);
    $turma = $stmt->fetch();
    
    if ($turma) {
        echo "✅ Turma encontrada:\n";
        echo "  Nome: {$turma['nome']}\n";
        echo "  Curso: {$turma['curso_tipo']}\n";
        echo "  Status: {$turma['status']}\n";
        echo "  Criada em: {$turma['criado_em']}\n\n";
    } else {
        echo "❌ Turma ID 6 não encontrada!\n\n";
    }
    
    // Verificar disciplinas da turma 6
    echo "🔍 Verificando disciplinas da turma 6...\n";
    $stmt = $pdo->prepare("SELECT * FROM turmas_disciplinas WHERE turma_id = ? ORDER BY ordem");
    $stmt->execute([6]);
    $disciplinas = $stmt->fetchAll();
    
    if (empty($disciplinas)) {
        echo "❌ Nenhuma disciplina encontrada para turma 6!\n";
        
        // Verificar se há disciplinas para outras turmas
        echo "\n🔍 Verificando outras turmas com disciplinas...\n";
        $stmt = $pdo->query("SELECT turma_id, COUNT(*) as total FROM turmas_disciplinas GROUP BY turma_id");
        $outras = $stmt->fetchAll();
        
        if (empty($outras)) {
            echo "❌ Nenhuma disciplina encontrada em nenhuma turma!\n";
        } else {
            echo "✅ Disciplinas encontradas em outras turmas:\n";
            foreach ($outras as $outra) {
                echo "  Turma ID {$outra['turma_id']}: {$outra['total']} disciplinas\n";
            }
        }
    } else {
        echo "✅ Encontradas " . count($disciplinas) . " disciplinas:\n";
        foreach ($disciplinas as $disc) {
            echo "  ID: {$disc['id']}\n";
            echo "    Nome: {$disc['nome_disciplina']}\n";
            echo "    Horas: {$disc['carga_horaria_padrao']}h\n";
            echo "    Ordem: {$disc['ordem']}\n";
            echo "    Cor: {$disc['cor_hex']}\n";
            echo "    Criada: {$disc['criado_em']}\n";
            echo "  ---\n";
        }
    }
    
    // Verificar todas as turmas
    echo "\n🔍 Verificando todas as turmas...\n";
    $stmt = $pdo->query("SELECT id, nome, curso_tipo, status, criado_em FROM turmas_teoricas ORDER BY id DESC LIMIT 10");
    $turmas = $stmt->fetchAll();
    
    echo "✅ Últimas 10 turmas:\n";
    foreach ($turmas as $t) {
        echo "  ID: {$t['id']} - {$t['nome']} ({$t['curso_tipo']}) - Status: {$t['status']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Botões de teste
echo '<br>';
echo '<a href="?page=turmas-teoricas&acao=detalhes&turma_id=6" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🔍 Ver Detalhes Turma 6</a>';
echo '<a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=6" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🧪 Testar Etapa 2</a>';
?>
