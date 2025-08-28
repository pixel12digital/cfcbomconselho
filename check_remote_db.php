<?php
// Verificação específica para banco remoto
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🌐 Verificação de Banco Remoto</h2>";

try {
    require_once 'includes/config.php';
    
    echo "<h3>1. Configurações de Conexão:</h3>";
    echo "Host: " . DB_HOST . "<br>";
    echo "Database: " . DB_NAME . "<br>";
    echo "User: " . DB_USER . "<br>";
    echo "Charset: " . DB_CHARSET . "<br>";
    echo "Timeout: " . DB_TIMEOUT . "<br>";
    
    // Testar conexão direta com PDO
    echo "<h3>2. Teste de Conexão Direta:</h3>";
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET,
        PDO::ATTR_TIMEOUT => DB_TIMEOUT
    ];
    
    $startTime = microtime(true);
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    $endTime = microtime(true);
    $connectionTime = ($endTime - $startTime) * 1000;
    
    echo "✅ Conexão estabelecida em " . number_format($connectionTime, 2) . "ms<br>";
    
    // Verificar versão do MySQL
    $version = $pdo->query('SELECT VERSION()')->fetchColumn();
    echo "Versão MySQL: $version<br>";
    
    // Verificar charset
    $charset = $pdo->query('SELECT @@character_set_database')->fetchColumn();
    echo "Charset do banco: $charset<br>";
    
    // Verificar se as tabelas existem
    echo "<h3>3. Verificação de Tabelas:</h3>";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tabelas encontradas: " . implode(', ', $tables) . "<br>";
    
    // Verificar estrutura específica da tabela cfcs
    if (in_array('cfcs', $tables)) {
        echo "<h4>Estrutura da tabela cfcs:</h4>";
        $structure = $pdo->query("DESCRIBE cfcs")->fetchAll();
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        foreach ($structure as $field) {
            echo "<tr>";
            echo "<td>" . $field['Field'] . "</td>";
            echo "<td>" . $field['Type'] . "</td>";
            echo "<td>" . $field['Null'] . "</td>";
            echo "<td>" . $field['Key'] . "</td>";
            echo "<td>" . $field['Default'] . "</td>";
            echo "<td>" . $field['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Verificar foreign keys
        echo "<h4>Foreign Keys da tabela cfcs:</h4>";
        $fks = $pdo->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '" . DB_NAME . "' 
            AND TABLE_NAME = 'cfcs' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ")->fetchAll();
        
        if (empty($fks)) {
            echo "Nenhuma foreign key encontrada<br>";
        } else {
            foreach ($fks as $fk) {
                echo "• {$fk['COLUMN_NAME']} → {$fk['REFERENCED_TABLE_NAME']}.{$fk['REFERENCED_COLUMN_NAME']}<br>";
            }
        }
    } else {
        echo "❌ Tabela 'cfcs' não existe!<br>";
    }
    
    // Testar operações específicas
    echo "<h3>4. Teste de Operações:</h3>";
    
    // Testar SELECT
    $cfcs = $pdo->query("SELECT COUNT(*) as total FROM cfcs")->fetch();
    echo "Total de CFCs: " . $cfcs['total'] . "<br>";
    
    if ($cfcs['total'] > 0) {
        $cfc = $pdo->query("SELECT * FROM cfcs LIMIT 1")->fetch();
        $cfcId = $cfc['id'];
        echo "CFC de teste: ID $cfcId - " . htmlspecialchars($cfc['nome']) . "<br>";
        
        // Testar contagem de dependências
        $tables = ['alunos', 'instrutores', 'veiculos', 'aulas'];
        foreach ($tables as $table) {
            if (in_array($table, $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN))) {
                $count = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE cfc_id = ?");
                $count->execute([$cfcId]);
                $total = $count->fetchColumn();
                echo "• $table: $total registros<br>";
            } else {
                echo "• $table: tabela não existe<br>";
            }
        }
        
        // Testar preparação de DELETE (sem executar)
        echo "<h4>Teste de preparação DELETE:</h4>";
        $deleteStmt = $pdo->prepare("DELETE FROM cfcs WHERE id = ?");
        if ($deleteStmt) {
            echo "✅ Statement DELETE pode ser preparado<br>";
            
            // Verificar se pode ser executado (com transação para rollback)
            $pdo->beginTransaction();
            try {
                $result = $deleteStmt->execute([$cfcId]);
                $pdo->rollback();
                echo "✅ Statement DELETE pode ser executado<br>";
                echo "Linhas afetadas: " . $deleteStmt->rowCount() . "<br>";
            } catch (Exception $e) {
                $pdo->rollback();
                echo "❌ Erro ao executar DELETE: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "❌ Erro ao preparar statement DELETE<br>";
        }
    }
    
    // Verificar permissões
    echo "<h3>5. Verificação de Permissões:</h3>";
    $permissions = $pdo->query("SHOW GRANTS FOR CURRENT_USER()")->fetchAll(PDO::FETCH_COLUMN);
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ddd;'>";
    foreach ($permissions as $permission) {
        echo htmlspecialchars($permission) . "\n";
    }
    echo "</pre>";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}

echo "<h3>✅ Verificação Concluída</h3>";
?>
