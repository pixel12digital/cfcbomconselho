<?php
// Script para verificar a estrutura da tabela instrutores
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/paths.php';
require_once INCLUDES_PATH . '/config.php';
require_once INCLUDES_PATH . '/database.php';

try {
    $db = Database::getInstance();
    
    echo "<h2>🔍 Verificação da Estrutura da Tabela Instrutores</h2>";
    
    // Verificar estrutura atual da tabela
    echo "<h3>📋 Estrutura Atual da Tabela 'instrutores':</h3>";
    $columns = $db->fetchAll("SHOW COLUMNS FROM instrutores");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar se há dados na tabela
    echo "<h3>📊 Dados na Tabela 'instrutores':</h3>";
    $instrutores = $db->fetchAll("SELECT * FROM instrutores LIMIT 5");
    
    if (!empty($instrutores)) {
        echo "<p>Total de instrutores: " . $db->count('instrutores') . "</p>";
        echo "<pre>" . print_r($instrutores, true) . "</pre>";
    } else {
        echo "<p>Nenhum instrutor encontrado</p>";
    }
    
    // Verificar se há foreign keys
    echo "<h3>🔗 Verificando Foreign Keys:</h3>";
    $foreignKeys = $db->fetchAll("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM 
            INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
        WHERE 
            TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'instrutores' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    if (!empty($foreignKeys)) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        
        foreach ($foreignKeys as $fk) {
            echo "<tr>";
            echo "<td>{$fk['CONSTRAINT_NAME']}</td>";
            echo "<td>{$fk['COLUMN_NAME']}</td>";
            echo "<td>{$fk['REFERENCED_TABLE_NAME']}</td>";
            echo "<td>{$fk['REFERENCED_COLUMN_NAME']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma foreign key encontrada</p>";
    }
    
    // Verificar se a tabela tem o campo cfc_id
    $hasCfcId = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'cfc_id') {
            $hasCfcId = true;
            break;
        }
    }
    
    if ($hasCfcId) {
        echo "<h3>✅ Campo 'cfc_id' encontrado</h3>";
        
        // Verificar se há instrutores vinculados ao CFC 30
        $instrutoresCFC30 = $db->fetchAll("SELECT * FROM instrutores WHERE cfc_id = 30");
        echo "<p>Instrutores vinculados ao CFC 30: " . count($instrutoresCFC30) . "</p>";
        
        if (!empty($instrutoresCFC30)) {
            echo "<h4>Detalhes dos instrutores vinculados:</h4>";
            foreach ($instrutoresCFC30 as $instrutor) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
                echo "<p><strong>ID:</strong> {$instrutor['id']}</p>";
                echo "<p><strong>Nome:</strong> " . ($instrutor['nome'] ?? 'N/A') . "</p>";
                echo "<p><strong>CPF:</strong> " . ($instrutor['cpf'] ?? 'N/A') . "</p>";
                echo "<p><strong>CFC ID:</strong> {$instrutor['cfc_id']}</p>";
                echo "<p><strong>Status:</strong> " . ($instrutor['status'] ?? 'N/A') . "</p>";
                echo "</div>";
            }
        }
    } else {
        echo "<h3>❌ Campo 'cfc_id' NÃO encontrado</h3>";
        echo "<p>Esta é provavelmente a causa do problema!</p>";
    }
    
    // Verificar se há problemas de integridade referencial
    echo "<h3>🔍 Verificando Integridade Referencial:</h3>";
    
    if ($hasCfcId) {
        // Verificar se há instrutores com cfc_id que não existe na tabela cfcs
        $invalidCFCs = $db->fetchAll("
            SELECT i.*, c.nome as cfc_nome 
            FROM instrutores i 
            LEFT JOIN cfcs c ON i.cfc_id = c.id 
            WHERE c.id IS NULL
        ");
        
        if (!empty($invalidCFCs)) {
            echo "<p style='color: red;'>⚠️ Instrutores com CFC inválido encontrados:</p>";
            echo "<pre>" . print_r($invalidCFCs, true) . "</pre>";
        } else {
            echo "<p style='color: green;'>✅ Todos os instrutores têm CFCs válidos</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}
?>
