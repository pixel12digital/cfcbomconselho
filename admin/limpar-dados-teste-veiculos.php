<?php
/**
 * Script para limpar dados de teste da tabela veiculos
 * Permite que o TESTE #8 funcione corretamente
 */

echo "<h1>🧹 LIMPEZA DE DADOS DE TESTE - TABELA VEÍCULOS</h1>";
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
    
    // Verificar dados atuais na tabela veiculos
    echo "<h2>📊 Dados Atuais na Tabela 'veiculos'</h2>";
    $stmt = $pdo->query("SELECT * FROM veiculos ORDER BY id");
    $veiculos = $stmt->fetchAll();
    
    if (count($veiculos) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Ano</th><th>CFC ID</th><th>Status</th></tr>";
        
        foreach ($veiculos as $veiculo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($veiculo['id']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['placa'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['marca'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['modelo'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['ano'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['cfc_id'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Total de veículos:</strong> " . count($veiculos) . "</p>";
    } else {
        echo "<p>Nenhum veículo encontrado na tabela.</p>";
    }
    
    // Opção 1: Limpar todos os dados de teste
    echo "<h2>🧹 Opção 1: Limpar Todos os Dados de Teste</h2>";
    
    try {
        // Verificar se há dados de teste (com placas que podem ser de teste)
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos WHERE placa IN ('ABC-1234', 'XYZ-5678') OR marca LIKE '%TESTE%' OR modelo LIKE '%TESTE%'");
        $resultado = $stmt->fetch();
        $total_teste = $resultado['total'];
        
        if ($total_teste > 0) {
            echo "⚠️ <strong>Encontrados dados de teste</strong> - $total_teste registros<br>";
            
            // Excluir dados de teste
            $sql = "DELETE FROM veiculos WHERE placa IN ('ABC-1234', 'XYZ-5678') OR marca LIKE '%TESTE%' OR modelo LIKE '%TESTE%'";
            $resultado = $pdo->exec($sql);
            
            echo "✅ <strong>Dados de teste removidos</strong> - $resultado registros excluídos<br>";
        } else {
            echo "✅ <strong>Nenhum dado de teste encontrado</strong><br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao limpar dados de teste</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Opção 2: Limpar todos os dados (se necessário)
    echo "<h2>🧹 Opção 2: Limpar Todos os Dados (Se Necessário)</h2>";
    
    try {
        // Verificar se ainda há dados
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
        $resultado = $stmt->fetch();
        $total_restante = $resultado['total'];
        
        if ($total_restante > 0) {
            echo "⚠️ <strong>Ainda existem dados</strong> - $total_restante registros<br>";
            
            // Perguntar se deve limpar tudo (simular com botão)
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>⚠️ ATENÇÃO:</strong> Ainda existem $total_restante veículos na tabela.</p>";
            echo "<p>Se quiser limpar todos os dados para um teste limpo, execute o comando SQL:</p>";
            echo "<code>DELETE FROM veiculos;</code>";
            echo "</div>";
        } else {
            echo "✅ <strong>Tabela limpa</strong> - Nenhum registro restante<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao verificar dados restantes</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Opção 3: Resetar auto_increment
    echo "<h2>🔧 Opção 3: Resetar Auto Increment</h2>";
    
    try {
        // Verificar se a tabela está vazia
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
        $resultado = $stmt->fetch();
        $total_final = $resultado['total'];
        
        if ($total_final == 0) {
            // Resetar auto_increment
            $sql = "ALTER TABLE veiculos AUTO_INCREMENT = 1";
            $pdo->exec($sql);
            
            echo "✅ <strong>Auto increment resetado</strong> - Próximo ID será 1<br>";
        } else {
            echo "⚠️ <strong>Auto increment não resetado</strong> - Ainda existem dados na tabela<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao resetar auto increment</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Verificar dados finais
    echo "<h2>📊 Dados Finais na Tabela 'veiculos'</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM veiculos");
    $resultado = $stmt->fetch();
    $total_final = $resultado['total'];
    
    echo "✅ <strong>Total de Veículos na tabela</strong> - $total_final registros<br>";
    
    if ($total_final > 0) {
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver veículos restantes</summary>";
        
        $stmt = $pdo->query("SELECT * FROM veiculos ORDER BY id");
        $veiculos_finais = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Placa</th><th>Marca</th><th>Modelo</th><th>Ano</th><th>Status</th></tr>";
        
        foreach ($veiculos_finais as $veiculo) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($veiculo['id']) . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['placa'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['marca'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['modelo'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['ano'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($veiculo['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "🎉 <strong>LIMPEZA DE DADOS DE TESTE CONCLUÍDA!</strong><br>";
    echo "A tabela veiculos agora está pronta para o TESTE #8.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Limpeza de dados concluída!</strong></p>";
echo "<p>🎯 <strong>Próximo:</strong> TESTE #8 - CRUD de Veículos (Executar novamente)</p>";
echo "<p>📝 <strong>Instrução:</strong> Agora execute o TESTE #8 novamente para verificar se as operações CRUD estão funcionando.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
table { font-size: 14px; }
th { padding: 8px; background: #f8f9fa; }
td { padding: 6px; text-align: center; }
details { margin: 10px 0; }
summary { cursor: pointer; color: #007bff; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>
