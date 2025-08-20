<?php
/**
 * Script para limpar dados de teste da tabela instrutores
 * Permite que o TESTE #7 funcione corretamente
 */

echo "<h1>🧹 LIMPEZA DE DADOS DE TESTE - TABELA INSTRUTORES</h1>";
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
    
    // Verificar dados atuais na tabela instrutores
    echo "<h2>📊 Dados Atuais na Tabela 'instrutores'</h2>";
    $stmt = $pdo->query("SELECT * FROM instrutores ORDER BY id");
    $instrutores = $stmt->fetchAll();
    
    if (count($instrutores) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>CPF</th><th>CNH</th><th>Status</th><th>Criado em</th></tr>";
        
        foreach ($instrutores as $instrutor) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($instrutor['id']) . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['cpf'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['cnh'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['status'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['created_at'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<p><strong>Total de instrutores:</strong> " . count($instrutores) . "</p>";
    } else {
        echo "<p>Nenhum instrutor encontrado na tabela.</p>";
    }
    
    // Opção 1: Limpar todos os dados de teste
    echo "<h2>🧹 Opção 1: Limpar Todos os Dados de Teste</h2>";
    
    try {
        // Verificar se há dados de teste (com nomes que contêm "TESTE" ou "Exemplo")
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores WHERE nome LIKE '%TESTE%' OR nome LIKE '%Exemplo%' OR nome LIKE '%Padrão%'");
        $resultado = $stmt->fetch();
        $total_teste = $resultado['total'];
        
        if ($total_teste > 0) {
            echo "⚠️ <strong>Encontrados dados de teste</strong> - $total_teste registros<br>";
            
            // Excluir dados de teste
            $sql = "DELETE FROM instrutores WHERE nome LIKE '%TESTE%' OR nome LIKE '%Exemplo%' OR nome LIKE '%Padrão%'";
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
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
        $resultado = $stmt->fetch();
        $total_restante = $resultado['total'];
        
        if ($total_restante > 0) {
            echo "⚠️ <strong>Ainda existem dados</strong> - $total_restante registros<br>";
            
            // Perguntar se deve limpar tudo (simular com botão)
            echo "<div style='background: #fff3cd; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>⚠️ ATENÇÃO:</strong> Ainda existem $total_restante instrutores na tabela.</p>";
            echo "<p>Se quiser limpar todos os dados para um teste limpo, execute o comando SQL:</p>";
            echo "<code>DELETE FROM instrutores;</code>";
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
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
        $resultado = $stmt->fetch();
        $total_final = $resultado['total'];
        
        if ($total_final == 0) {
            // Resetar auto_increment
            $sql = "ALTER TABLE instrutores AUTO_INCREMENT = 1";
            $pdo->exec($sql);
            
            echo "✅ <strong>Auto increment resetado</strong> - Próximo ID será 1<br>";
        } else {
            echo "⚠️ <strong>Auto increment não resetado</strong> - Ainda existem dados na tabela<br>";
        }
        
    } catch (Exception $e) {
        echo "❌ <strong>Erro ao resetar auto increment</strong> - " . $e->getMessage() . "<br>";
    }
    
    // Verificar dados finais
    echo "<h2>📊 Dados Finais na Tabela 'instrutores'</h2>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM instrutores");
    $resultado = $stmt->fetch();
    $total_final = $resultado['total'];
    
    echo "✅ <strong>Total de Instrutores na tabela</strong> - $total_final registros<br>";
    
    if ($total_final > 0) {
        echo "<details style='margin: 10px 0;'>";
        echo "<summary style='cursor: pointer; color: #007bff;'>📋 Ver instrutores restantes</summary>";
        
        $stmt = $pdo->query("SELECT * FROM instrutores ORDER BY id");
        $instrutores_finais = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0; font-size: 12px;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>CPF</th><th>CNH</th><th>Status</th></tr>";
        
        foreach ($instrutores_finais as $instrutor) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($instrutor['id']) . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['nome'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['cpf'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['cnh'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($instrutor['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        echo "</details>";
    }
    
    echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "🎉 <strong>LIMPEZA DE DADOS DE TESTE CONCLUÍDA!</strong><br>";
    echo "A tabela instrutores agora está pronta para o TESTE #7.";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Limpeza de dados concluída!</strong></p>";
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
details { margin: 10px 0; }
summary { cursor: pointer; color: #007bff; }
code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; font-family: monospace; }
</style>
