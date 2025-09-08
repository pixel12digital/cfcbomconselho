<?php
/**
 * Script para adicionar campos de naturalidade e nacionalidade à tabela alunos
 * Execute este arquivo uma vez para atualizar a estrutura do banco de dados
 */

// Configurações do banco de dados
$host = 'localhost';
$dbname = 'cfc_sistema';
$username = 'root';
$password = '';

try {
    // Conectar ao banco de dados
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Atualizando estrutura da tabela alunos...</h2>\n";
    
    // Verificar se as colunas já existem
    $stmt = $pdo->query("SHOW COLUMNS FROM alunos LIKE 'naturalidade'");
    $naturalidadeExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW COLUMNS FROM alunos LIKE 'nacionalidade'");
    $nacionalidadeExists = $stmt->rowCount() > 0;
    
    // Adicionar coluna naturalidade se não existir
    if (!$naturalidadeExists) {
        $pdo->exec("ALTER TABLE alunos ADD COLUMN naturalidade VARCHAR(100) NULL COMMENT 'Cidade e UF de nascimento do aluno'");
        echo "✅ Coluna 'naturalidade' adicionada com sucesso!<br>\n";
    } else {
        echo "ℹ️ Coluna 'naturalidade' já existe.<br>\n";
    }
    
    // Adicionar coluna nacionalidade se não existir
    if (!$nacionalidadeExists) {
        $pdo->exec("ALTER TABLE alunos ADD COLUMN nacionalidade VARCHAR(50) NULL DEFAULT 'Brasileira' COMMENT 'Nacionalidade do aluno'");
        echo "✅ Coluna 'nacionalidade' adicionada com sucesso!<br>\n";
    } else {
        echo "ℹ️ Coluna 'nacionalidade' já existe.<br>\n";
    }
    
    // Adicionar índices para melhorar performance
    try {
        $pdo->exec("CREATE INDEX idx_alunos_naturalidade ON alunos(naturalidade)");
        echo "✅ Índice para 'naturalidade' criado com sucesso!<br>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️ Índice para 'naturalidade' já existe.<br>\n";
        } else {
            echo "⚠️ Erro ao criar índice para 'naturalidade': " . $e->getMessage() . "<br>\n";
        }
    }
    
    try {
        $pdo->exec("CREATE INDEX idx_alunos_nacionalidade ON alunos(nacionalidade)");
        echo "✅ Índice para 'nacionalidade' criado com sucesso!<br>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
            echo "ℹ️ Índice para 'nacionalidade' já existe.<br>\n";
        } else {
            echo "⚠️ Erro ao criar índice para 'nacionalidade': " . $e->getMessage() . "<br>\n";
        }
    }
    
    // Mostrar estrutura atualizada da tabela
    echo "<h3>Estrutura atual da tabela alunos:</h3>\n";
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>\n";
    
    $stmt = $pdo->query("DESCRIBE alunos");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
        echo "</tr>\n";
    }
    echo "</table>\n";
    
    echo "<h3>✅ Atualização concluída com sucesso!</h3>\n";
    echo "<p>Os campos de <strong>naturalidade</strong> e <strong>nacionalidade</strong> foram adicionados à tabela alunos.</p>\n";
    echo "<p>Agora você pode:</p>\n";
    echo "<ul>\n";
    echo "<li>Cadastrar novos alunos com os campos de naturalidade e nacionalidade</li>\n";
    echo "<li>Editar alunos existentes para incluir essas informações</li>\n";
    echo "<li>Visualizar essas informações na listagem de alunos</li>\n";
    echo "</ul>\n";
    
} catch (PDOException $e) {
    echo "<h3>❌ Erro ao conectar com o banco de dados:</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Verifique se:</p>\n";
    echo "<ul>\n";
    echo "<li>O MySQL está rodando</li>\n";
    echo "<li>As credenciais do banco estão corretas</li>\n";
    echo "<li>O banco de dados 'cfc_sistema' existe</li>\n";
    echo "</ul>\n";
} catch (Exception $e) {
    echo "<h3>❌ Erro inesperado:</h3>\n";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>\n";
}
?>
