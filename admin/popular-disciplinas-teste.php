<?php
/**
 * Script para popular a tabela turmas_disciplinas com dados de teste
 */

echo "<h2>🧪 Popular Disciplinas de Teste</h2>";
echo "<pre>";

try {
    // Incluir configuração do banco remoto
    require_once __DIR__ . '/../includes/config.php';
    
    // Conectar ao banco remoto
    echo "🔄 Conectando ao banco remoto...\n";
    echo "📡 Host: " . DB_HOST . "\n";
    echo "📡 Database: " . DB_NAME . "\n\n";
    
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
    
    // Verificar se a tabela existe
    $stmt = $pdo->query("SHOW TABLES LIKE 'turmas_disciplinas'");
    if ($stmt->rowCount() == 0) {
        echo "❌ Tabela 'turmas_disciplinas' não existe. Execute primeiro a migração!\n";
        exit;
    }
    
    // Buscar turma com ID 5 (que está sendo testada)
    echo "🔍 Buscando turma ID 5...\n";
    $stmt = $pdo->prepare("SELECT * FROM turmas_teoricas WHERE id = ?");
    $stmt->execute([5]);
    $turma = $stmt->fetch();
    
    if (!$turma) {
        echo "❌ Turma ID 5 não encontrada. Criando turma de teste...\n";
        
        // Criar turma de teste
        $stmt = $pdo->prepare("
            INSERT INTO turmas_teoricas (nome, curso_tipo, data_inicio, data_fim, status, criado_em) 
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            'Turma Teste Disciplinas 2024',
            'formacao_45h',
            '2024-11-01',
            '2024-12-31',
            'agendando'
        ]);
        
        $turmaId = $pdo->lastInsertId();
        echo "✅ Turma de teste criada com ID: $turmaId\n";
    } else {
        $turmaId = $turma['id'];
        echo "✅ Turma encontrada: {$turma['nome']} (ID: $turmaId)\n";
    }
    
    // Verificar se já há disciplinas para esta turma
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM turmas_disciplinas WHERE turma_id = ?");
    $stmt->execute([$turmaId]);
    $count = $stmt->fetch();
    
    if ($count['total'] > 0) {
        echo "ℹ️ Já existem {$count['total']} disciplinas para esta turma.\n";
    } else {
        echo "➕ Adicionando disciplinas de teste...\n";
        
        // Dados de disciplinas para o curso formação 45h
        $disciplinas = [
            ['Legislação de Trânsito', 15, '#007bff'],
            ['Direção Defensiva', 10, '#28a745'],
            ['Primeiros Socorros', 10, '#dc3545'],
            ['Meio Ambiente e Cidadania', 5, '#ffc107'],
            ['Mecânica Básica', 5, '#6f42c1']
        ];
        
        $stmt = $pdo->prepare("
            INSERT INTO turmas_disciplinas (turma_id, disciplina_id, nome_disciplina, carga_horaria_padrao, cor_hex, ordem) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($disciplinas as $index => $disciplina) {
            $stmt->execute([
                $turmaId,
                $index + 1, // ID fictício da disciplina
                $disciplina[0], // Nome
                $disciplina[1], // Carga horária
                $disciplina[2], // Cor
                $index + 1 // Ordem
            ]);
            
            echo "  ✅ {$disciplina[0]} ({$disciplina[1]}h)\n";
        }
        
        echo "\n🎉 Disciplinas adicionadas com sucesso!\n";
    }
    
    // Mostrar disciplinas da turma
    echo "\n📋 Disciplinas da turma:\n";
    echo "==========================================\n";
    $stmt = $pdo->prepare("SELECT * FROM turmas_disciplinas WHERE turma_id = ? ORDER BY ordem");
    $stmt->execute([$turmaId]);
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
echo '<a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=' . ($turmaId ?? 5) . '" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">🧪 Testar Etapa 2</a>';
echo '<a href="admin/teste-conexao-migracao.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🔧 Verificar Migração</a>';
?>
