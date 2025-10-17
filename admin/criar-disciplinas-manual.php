<?php
/**
 * Script para criar disciplinas manualmente para a turma 6
 */

echo "<h2>➕ Criar Disciplinas Manualmente - Turma 6</h2>";
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
    
    // Verificar se turma 6 existe
    $stmt = $pdo->prepare("SELECT * FROM turmas_teoricas WHERE id = ?");
    $stmt->execute([6]);
    $turma = $stmt->fetch();
    
    if (!$turma) {
        echo "❌ Turma 6 não encontrada!\n";
        exit;
    }
    
    echo "✅ Turma 6 encontrada: {$turma['nome']}\n\n";
    
    // Verificar se já há disciplinas
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM turmas_disciplinas WHERE turma_id = ?");
    $stmt->execute([6]);
    $count = $stmt->fetch();
    
    if ($count['total'] > 0) {
        echo "ℹ️ Turma já possui {$count['total']} disciplinas.\n";
        echo "❓ Deseja adicionar mais disciplinas? (Sim/Não)\n";
    } else {
        echo "➕ Adicionando 3 disciplinas para turma 6...\n\n";
        
        // Disciplinas para curso formacao_45h
        $disciplinas = [
            ['Legislação de Trânsito', 15, '#007bff'],
            ['Direção Defensiva', 10, '#28a745'],
            ['Primeiros Socorros', 10, '#dc3545']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO turmas_disciplinas (turma_id, disciplina_id, nome_disciplina, carga_horaria_padrao, cor_hex, ordem) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($disciplinas as $index => $disciplina) {
            $stmt->execute([
                6, // turma_id
                $index + 1, // disciplina_id (fictício)
                $disciplina[0], // nome
                $disciplina[1], // carga horária
                $disciplina[2], // cor
                $index + 1 // ordem
            ]);
            
            echo "✅ {$disciplina[0]} ({$disciplina[1]}h) - Adicionada\n";
        }
        
        echo "\n🎉 Disciplinas adicionadas com sucesso!\n";
    }
    
    // Mostrar disciplinas da turma
    echo "\n📋 Disciplinas da turma 6:\n";
    echo "==========================================\n";
    $stmt = $pdo->prepare("SELECT * FROM turmas_disciplinas WHERE turma_id = ? ORDER BY ordem");
    $stmt->execute([6]);
    $disciplinas = $stmt->fetchAll();
    
    foreach ($disciplinas as $disc) {
        echo sprintf("%-30s %-10s %-10s\n", 
            $disc['nome_disciplina'], 
            $disc['carga_horaria_padrao'] . 'h',
            $disc['cor_hex']
        );
    }
    
} catch (PDOException $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Botões de teste
echo '<br>';
echo '<a href="?page=turmas-teoricas&acao=detalhes&turma_id=6" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🔍 Ver Detalhes</a>';
echo '<a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=6" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🧪 Testar Etapa 2</a>';
echo '<a href="verificar-disciplinas-turma.php" style="background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔍 Verificar Novamente</a>';
?>
