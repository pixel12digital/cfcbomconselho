<?php
/**
 * Script simples para executar migração no banco remoto
 */

echo "<h2>🌐 Migração no Banco Remoto</h2>";
echo "<pre>";

try {
    // Incluir configuração
    require_once __DIR__ . '/../includes/config.php';
    
    echo "🔄 Conectando ao banco remoto...\n";
    echo "📡 Host: " . DB_HOST . "\n";
    echo "📡 Database: " . DB_NAME . "\n";
    echo "📡 User: " . DB_USER . "\n\n";
    
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
    
    echo "✅ Conectado com sucesso!\n\n";
    
    // Verificar se tabela existe
    echo "🔍 Verificando tabela 'turmas_disciplinas'...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'turmas_disciplinas'");
    
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela já existe!\n";
        
        // Mostrar estrutura
        $stmt = $pdo->query("DESCRIBE turmas_disciplinas");
        $columns = $stmt->fetchAll();
        
        echo "\n📋 Estrutura:\n";
        echo "==========================================\n";
        foreach ($columns as $col) {
            echo sprintf("%-20s %-20s %-10s %-10s\n", 
                $col['Field'], $col['Type'], $col['Null'], $col['Key']);
        }
        
        // Verificar dados
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas_disciplinas");
        $count = $stmt->fetch();
        echo "\n📊 Total de registros: " . $count['total'] . "\n";
        
    } else {
        echo "❌ Tabela não existe. Criando...\n";
        
        // SQL da migração
        $sql = "CREATE TABLE IF NOT EXISTS turmas_disciplinas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            turma_id INT NOT NULL,
            disciplina_id INT NOT NULL,
            nome_disciplina VARCHAR(255) NOT NULL,
            carga_horaria_padrao INT NOT NULL,
            cor_hex VARCHAR(7) DEFAULT '#007bff',
            ordem INT NOT NULL DEFAULT 1,
            criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
            FOREIGN KEY (disciplina_id) REFERENCES disciplinas(id) ON DELETE RESTRICT,
            UNIQUE KEY unique_turma_disciplina (turma_id, disciplina_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $pdo->exec($sql);
        echo "✅ Tabela criada com sucesso!\n";
    }
    
    // Verificar se há turmas para testar
    echo "\n🔍 Verificando turmas existentes...\n";
    $stmt = $pdo->query("SELECT id, nome, curso_tipo FROM turmas_teoricas ORDER BY id DESC LIMIT 5");
    $turmas = $stmt->fetchAll();
    
    if (empty($turmas)) {
        echo "❌ Nenhuma turma encontrada.\n";
        echo "💡 Crie uma turma primeiro na etapa 1.\n";
    } else {
        echo "✅ Turmas encontradas:\n";
        foreach ($turmas as $turma) {
            echo "  ID: {$turma['id']} - {$turma['nome']} ({$turma['curso_tipo']})\n";
        }
        
        // Sugerir turma para teste
        $turmaTeste = $turmas[0];
        echo "\n🎯 Para testar, use: turma_id={$turmaTeste['id']}\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERRO de conexão: " . $e->getMessage() . "\n";
    echo "\n🔧 Possíveis causas:\n";
    echo "1. Problemas de conectividade\n";
    echo "2. Credenciais incorretas\n";
    echo "3. Firewall bloqueando conexão\n";
    echo "4. Banco de dados indisponível\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Botões de teste
echo '<br>';
echo '<a href="?page=turmas-teoricas&acao=nova&step=1" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-right: 10px;">➕ Criar Nova Turma</a>';

if (!empty($turmas)) {
    $turmaId = $turmas[0]['id'];
    echo '<a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=' . $turmaId . '" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🧪 Testar Etapa 2</a>';
}
?>
