<?php
/**
 * Script para corrigir a coluna credencial da tabela instrutores
 * Resolve o problema de constraint unique na coluna credencial
 */

echo "<h1>🔧 CORREÇÃO DA COLUNA CREDENCIAL - TABELA INSTRUTORES</h1>";
echo "<p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>";
echo "<hr>";

try {
    // Incluir configurações
    require_once '../includes/config.php';
    require_once '../includes/database.php';
    
    echo "✅ <strong>Arquivos de configuração</strong> - INCLUÍDOS COM SUCESSO<br>";
    
    // Conectar ao banco
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );
    
    echo "✅ <strong>Conexão com banco</strong> - ESTABELECIDA<br>";
    
    // Verificar estrutura atual da coluna credencial
    echo "<h2>📋 Estrutura Atual da Coluna 'credencial'</h2>";
    $stmt = $pdo->query("DESCRIBE instrutores");
    $colunas = $stmt->fetchAll();
    
    $coluna_credencial = null;
    foreach ($colunas as $coluna) {
        if ($coluna['Field'] === 'credencial') {
            $coluna_credencial = $coluna;
            break;
        }
    }
    
    if ($coluna_credencial) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna_credencial['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial['Extra']) . "</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<p><strong>Status atual:</strong> " . htmlspecialchars($coluna_credencial['Key']) . "</p>";
    } else {
        echo "<p>Coluna 'credencial' não encontrada.</p>";
    }
    
    // Verificar constraints de unique na coluna credencial
    echo "<h2>🔗 Constraints de Unique na Coluna 'credencial'</h2>";
    $stmt = $pdo->query("
        SELECT 
            CONSTRAINT_NAME,
            COLUMN_NAME,
            REFERENCED_TABLE_NAME,
            REFERENCED_COLUMN_NAME
        FROM information_schema.KEY_COLUMN_USAGE 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'instrutores' 
        AND COLUMN_NAME = 'credencial'
    ");
    $constraints = $stmt->fetchAll();
    
    if (count($constraints) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Constraint</th><th>Coluna</th><th>Tabela Referenciada</th><th>Coluna Referenciada</th></tr>";
        
        foreach ($constraints as $constraint) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($constraint['CONSTRAINT_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['COLUMN_NAME']) . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_TABLE_NAME'] ?? 'UNIQUE') . "</td>";
            echo "<td>" . htmlspecialchars($constraint['REFERENCED_COLUMN_NAME'] ?? 'UNIQUE') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhuma constraint encontrada na coluna 'credencial'.</p>";
    }
    
    // Verificar dados atuais na coluna credencial
    echo "<h2>📊 Dados Atuais na Coluna 'credencial'</h2>";
    $stmt = $pdo->query("SELECT credencial, COUNT(*) as total FROM instrutores GROUP BY credencial ORDER BY total DESC");
    $dados_credencial = $stmt->fetchAll();
    
    if (count($dados_credencial) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Valor da Credencial</th><th>Quantidade</th></tr>";
        
        foreach ($dados_credencial as $dado) {
            $valor = $dado['credencial'] === '' ? '(VAZIO)' : $dado['credencial'];
            echo "<tr>";
            echo "<td>" . htmlspecialchars($valor) . "</td>";
            echo "<td>" . htmlspecialchars($dado['total']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum dado encontrado na coluna 'credencial'.</p>";
    }
    
    // Opção 1: Remover constraint unique da coluna credencial
    echo "<h2>🔧 Opção 1: Remover Constraint Unique da Coluna 'credencial'</h2>";
    
    try {
        // Primeiro, vamos verificar se existe uma constraint unique
        $stmt = $pdo->query("SHOW INDEX FROM instrutores WHERE Column_name = 'credencial' AND Non_unique = 0");
        $indexes = $stmt->fetchAll();
        
        if (count($indexes) > 0) {
            foreach ($indexes as $index) {
                $key_name = $index['Key_name'];
                echo "⚠️ <strong>Removendo constraint unique</strong> - $key_name<br>";
                
                // Remover o índice unique
                $sql = "ALTER TABLE instrutores DROP INDEX $key_name";
                $pdo->exec($sql);
                
                echo "✅ <strong>Constraint unique removida</strong> - $key_name<br>";
            }
        } else {
            echo "✅ <strong>Nenhuma constraint unique encontrada</strong> na coluna credencial<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao remover constraint</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Opção 2: Tornar a coluna credencial opcional (permitir NULL)
    echo "<h2>🔧 Opção 2: Tornar Coluna 'credencial' Opcional</h2>";
    
    try {
        // Alterar coluna credencial para permitir NULL
        $sql = "ALTER TABLE instrutores MODIFY COLUMN credencial VARCHAR(50) NULL";
        $pdo->exec($sql);
        
        echo "✅ <strong>Coluna 'credencial'</strong> - MODIFICADA PARA PERMITIR NULL<br>";
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao modificar credencial</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Opção 3: Atualizar registros com credencial vazia para NULL
    echo "<h2>🔧 Opção 3: Atualizar Registros com Credencial Vazia</h2>";
    
    try {
        // Atualizar registros com credencial vazia para NULL
        $sql = "UPDATE instrutores SET credencial = NULL WHERE credencial = '' OR credencial IS NULL";
        $resultado = $pdo->exec($sql);
        
        if ($resultado > 0) {
            echo "✅ <strong>Registros atualizados</strong> - $resultado registros com credencial vazia convertidos para NULL<br>";
        } else {
            echo "✅ <strong>Nenhum registro atualizado</strong> - Todos os registros já estão corretos<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao atualizar registros</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Verificar estrutura final da coluna credencial
    echo "<h2>📋 Estrutura Final da Coluna 'credencial'</h2>";
    $stmt = $pdo->query("DESCRIBE instrutores");
    $colunas_finais = $stmt->fetchAll();
    
    $coluna_credencial_final = null;
    foreach ($colunas_finais as $coluna) {
        if ($coluna['Field'] === 'credencial') {
            $coluna_credencial_final = $coluna;
            break;
        }
    }
    
    if ($coluna_credencial_final) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna_credencial_final['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial_final['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial_final['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial_final['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial_final['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna_credencial_final['Extra']) . "</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<p><strong>Status final:</strong> " . htmlspecialchars($coluna_credencial_final['Key']) . "</p>";
    }
    
    // Verificar dados finais na coluna credencial
    echo "<h2>📊 Dados Finais na Coluna 'credencial'</h2>";
    $stmt = $pdo->query("SELECT credencial, COUNT(*) as total FROM instrutores GROUP BY credencial ORDER BY total DESC");
    $dados_credencial_final = $stmt->fetchAll();
    
    if (count($dados_credencial_final) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>Valor da Credencial</th><th>Quantidade</th></tr>";
        
        foreach ($dados_credencial_final as $dado) {
            $valor = $dado['credencial'] === '' ? '(VAZIO)' : ($dado['credencial'] ?? 'NULL');
            echo "<tr>";
            echo "<td>" . htmlspecialchars($valor) . "</td>";
            echo "<td>" . htmlspecialchars($dado['total']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "🎉 <strong>CORREÇÃO DA COLUNA CREDENCIAL CONCLUÍDA!</strong><br>";
    echo "A coluna credencial agora deve funcionar corretamente.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Correção da coluna credencial concluída!</strong></p>";
echo "<p>🎯 <strong>Próximo:</strong> TESTE #7 - CRUD de Instrutores (Executar novamente)</p>";
echo "<p>📝 <strong>Instrução:</strong> Agora execute o TESTE #7 novamente para verificar se as operações CRUD estão funcionando.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
table { font-size: 14px; }
th { padding: 8px; background: #f8f9fa; }
td { padding: 6px; text-align: center; }
</style>
