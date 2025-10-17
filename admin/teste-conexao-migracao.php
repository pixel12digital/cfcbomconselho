<?php
/**
 * Script simples para testar conexão e executar migração
 */

echo "<h2>🔧 Teste de Conexão e Migração</h2>";
echo "<pre>";

try {
    // Incluir configuração do banco remoto
    require_once __DIR__ . '/../includes/config.php';
    
    // Tentar conectar usando configurações do config.php
    echo "🔄 Testando conexão com o banco remoto...\n";
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
    
    echo "✅ Conexão estabelecida com sucesso!\n\n";
    
    // Verificar se a tabela já existe
    echo "🔍 Verificando se a tabela 'turmas_disciplinas' já existe...\n";
    $stmt = $pdo->query("SHOW TABLES LIKE 'turmas_disciplinas'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Tabela 'turmas_disciplinas' já existe!\n";
        
        // Mostrar estrutura
        $stmt = $pdo->query("DESCRIBE turmas_disciplinas");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "\n📋 Estrutura da tabela:\n";
        echo "==========================================\n";
        foreach ($columns as $column) {
            echo sprintf("%-20s %-20s %-10s %-10s\n", 
                $column['Field'], 
                $column['Type'], 
                $column['Null'], 
                $column['Key']
            );
        }
        
    } else {
        echo "❌ Tabela 'turmas_disciplinas' NÃO existe. Criando...\n";
        
        // SQL para criar a tabela
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
        echo "✅ Tabela 'turmas_disciplinas' criada com sucesso!\n";
    }
    
    // Verificar se há dados na tabela
    echo "\n🔍 Verificando dados na tabela 'turmas_disciplinas'...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM turmas_disciplinas");
    $count = $stmt->fetch();
    echo "📊 Total de registros: " . $count['total'] . "\n";
    
    if ($count['total'] > 0) {
        echo "\n📋 Últimos registros:\n";
        $stmt = $pdo->query("SELECT * FROM turmas_disciplinas ORDER BY id DESC LIMIT 5");
        $registros = $stmt->fetchAll();
        
        foreach ($registros as $reg) {
            echo "  ID: {$reg['id']}, Turma: {$reg['turma_id']}, Disciplina: {$reg['nome_disciplina']}\n";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ ERRO na conexão: " . $e->getMessage() . "\n";
    echo "\n🔧 Verifique:\n";
    echo "1. Se o MySQL está rodando\n";
    echo "2. Se o banco 'cfc_bom_conselho' existe\n";
    echo "3. Se o usuário 'root' tem permissão\n";
    echo "4. Se a senha está correta (vazia no XAMPP)\n";
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";

// Botão para testar a etapa 2
echo '<br><a href="?page=turmas-teoricas&acao=agendar&step=2&turma_id=5" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">🧪 Testar Etapa 2</a>';
echo '<br><br><a href="?page=turmas-teoricas&acao=nova&step=1" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">➕ Criar Nova Turma</a>';
?>
