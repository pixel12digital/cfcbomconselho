<?php
/**
 * Script para executar migração da tabela turmas_disciplinas
 * Execute este arquivo via navegador para criar a tabela no banco remoto
 */

// Incluir configuração do banco
require_once __DIR__ . '/../includes/config.php';

// Conectar ao banco
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    die('❌ ERRO: Não foi possível conectar ao banco de dados: ' . $e->getMessage());
}

echo "<h2>🔧 Executando Migração - Tabela turmas_disciplinas</h2>";
echo "<pre>";

try {
    // Ler o arquivo SQL
    $sqlFile = __DIR__ . '/migrations/002-create-turmas-disciplinas-table.sql';
    
    if (!file_exists($sqlFile)) {
        throw new Exception("❌ Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sqlContent = file_get_contents($sqlFile);
    
    if (empty($sqlContent)) {
        throw new Exception("❌ Arquivo SQL está vazio");
    }
    
    echo "📄 Conteúdo do arquivo SQL:\n";
    echo "================================\n";
    echo $sqlContent;
    echo "\n================================\n\n";
    
    // Executar o SQL
    echo "🔄 Executando SQL no banco de dados...\n";
    
    $result = $pdo->exec($sqlContent);
    
    if ($result !== false) {
        echo "✅ SUCESSO: Tabela 'turmas_disciplinas' criada com sucesso!\n";
        
        // Verificar se a tabela foi criada
        $stmt = $pdo->query("SHOW TABLES LIKE 'turmas_disciplinas'");
        if ($stmt->rowCount() > 0) {
            echo "✅ CONFIRMAÇÃO: Tabela existe no banco de dados\n";
            
            // Mostrar estrutura da tabela
            $stmt = $pdo->query("DESCRIBE turmas_disciplinas");
            $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "\n📋 Estrutura da tabela 'turmas_disciplinas':\n";
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
            echo "❌ ERRO: Tabela não foi encontrada após criação\n";
        }
        
    } else {
        $errorInfo = $pdo->errorInfo();
        throw new Exception("❌ Erro ao executar SQL: " . $errorInfo[2]);
    }
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    
    // Verificar se a tabela já existe
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'turmas_disciplinas'");
        if ($stmt->rowCount() > 0) {
            echo "ℹ️ INFO: Tabela 'turmas_disciplinas' já existe no banco\n";
        }
    } catch (Exception $e2) {
        echo "❌ Erro ao verificar tabela: " . $e2->getMessage() . "\n";
    }
}

echo "\n";
echo "🎯 PRÓXIMOS PASSOS:\n";
echo "1. Verificar se não há erros de sintaxe JavaScript\n";
echo "2. Testar criação de turma com disciplinas\n";
echo "3. Verificar se disciplinas aparecem na etapa 2\n";
echo "</pre>";

// Botão para voltar
echo '<br><a href="?page=turmas-teoricas&acao=nova&step=1" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Voltar para Turmas Teóricas</a>';
?>
