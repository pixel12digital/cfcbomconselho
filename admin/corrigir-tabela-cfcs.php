<?php
/**
 * Script para corrigir a estrutura da tabela cfcs
 * Adiciona as colunas 'responsavel' e 'status' que estão faltando
 */

echo "<h1>🔧 CORREÇÃO DA TABELA CFCS</h1>";
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
    
    // Verificar estrutura atual da tabela
    echo "<h2>📋 Estrutura Atual da Tabela 'cfcs'</h2>";
    $stmt = $pdo->query("DESCRIBE cfcs");
    $colunas = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar se as colunas necessárias já existem
    $colunas_existentes = array_column($colunas, 'Field');
    
    // Verificar se a coluna 'responsavel' existe
    if (in_array('responsavel', $colunas_existentes)) {
        echo "✅ <strong>Coluna 'responsavel'</strong> - JÁ EXISTE<br>";
    } else {
        echo "⚠️ <strong>Coluna 'responsavel'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
        
        // Adicionar coluna 'responsavel'
        $sql = "ALTER TABLE cfcs ADD COLUMN responsavel VARCHAR(100) AFTER email";
        $pdo->exec($sql);
        
        echo "✅ <strong>Coluna 'responsavel'</strong> - ADICIONADA COM SUCESSO<br>";
        
        // Atualizar registros existentes para terem responsável padrão
        $sql = "UPDATE cfcs SET responsavel = 'Responsável não informado' WHERE responsavel IS NULL";
        $pdo->exec($sql);
        
        echo "✅ <strong>Registros existentes</strong> - ATUALIZADOS COM RESPONSÁVEL PADRÃO<br>";
    }
    
    // Verificar se a coluna 'status' existe
    if (in_array('status', $colunas_existentes)) {
        echo "✅ <strong>Coluna 'status'</strong> - JÁ EXISTE<br>";
    } else {
        echo "⚠️ <strong>Coluna 'status'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
        
        // Adicionar coluna 'status'
        $sql = "ALTER TABLE cfcs ADD COLUMN status VARCHAR(20) DEFAULT 'ativo' AFTER responsavel";
        $pdo->exec($sql);
        
        echo "✅ <strong>Coluna 'status'</strong> - ADICIONADA COM SUCESSO<br>";
        
        // Atualizar registros existentes para terem status 'ativo'
        $sql = "UPDATE cfcs SET status = 'ativo' WHERE status IS NULL";
        $pdo->exec($sql);
        
        echo "✅ <strong>Registros existentes</strong> - ATUALIZADOS COM STATUS 'ativo'<br>";
    }
    
    // Verificar se a coluna 'created_at' existe
    if (in_array('created_at', $colunas_existentes)) {
        echo "✅ <strong>Coluna 'created_at'</strong> - JÁ EXISTE<br>";
    } else {
        echo "⚠️ <strong>Coluna 'created_at'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
        
        // Adicionar coluna 'created_at'
        $sql = "ALTER TABLE cfcs ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status";
        $pdo->exec($sql);
        
        echo "✅ <strong>Coluna 'created_at'</strong> - ADICIONADA COM SUCESSO<br>";
    }
    
    // Verificar se a coluna 'updated_at' existe
    if (in_array('updated_at', $colunas_existentes)) {
        echo "✅ <strong>Coluna 'updated_at'</strong> - JÁ EXISTE<br>";
    } else {
        echo "⚠️ <strong>Coluna 'updated_at'</strong> - NÃO EXISTE, ADICIONANDO...<br>";
        
        // Adicionar coluna 'updated_at'
        $sql = "ALTER TABLE cfcs ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at";
        $pdo->exec($sql);
        
        echo "✅ <strong>Coluna 'updated_at'</strong> - ADICIONADA COM SUCESSO<br>";
    }
    
    // Verificar estrutura final
    echo "<h2>📋 Estrutura Final da Tabela 'cfcs'</h2>";
    $stmt = $pdo->query("DESCRIBE cfcs");
    $colunas_finais = $stmt->fetchAll();
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
    echo "<tr style='background: #f8f9fa;'><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    
    foreach ($colunas_finais as $coluna) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($coluna['Field']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Type']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Null']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Key']) . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Default'] ?? 'NULL') . "</td>";
        echo "<td>" . htmlspecialchars($coluna['Extra']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar se todas as colunas necessárias estão presentes
    $colunas_necessarias = ['id', 'nome', 'cnpj', 'endereco', 'telefone', 'email', 'responsavel', 'status', 'created_at', 'updated_at'];
    $colunas_finais_nomes = array_column($colunas_finais, 'Field');
    
    $colunas_faltando = array_diff($colunas_necessarias, $colunas_finais_nomes);
    
    if (empty($colunas_faltando)) {
        echo "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "🎉 <strong>TABELA 'cfcs' CORRIGIDA COM SUCESSO!</strong><br>";
        echo "Todas as colunas necessárias estão presentes.";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "⚠️ <strong>ATENÇÃO:</strong> Ainda faltam colunas: " . implode(', ', $colunas_faltando);
        echo "</div>";
    }
    
    // Verificar dados existentes
    echo "<h2>📊 Dados Atuais na Tabela 'cfcs'</h2>";
    $stmt = $pdo->query("SELECT * FROM cfcs");
    $cfcs_existentes = $stmt->fetchAll();
    
    if (count($cfcs_existentes) > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
        echo "<tr style='background: #f8f9fa;'><th>ID</th><th>Nome</th><th>CNPJ</th><th>Responsável</th><th>Status</th></tr>";
        
        foreach ($cfcs_existentes as $cfc) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($cfc['id']) . "</td>";
            echo "<td>" . htmlspecialchars($cfc['nome']) . "</td>";
            echo "<td>" . htmlspecialchars($cfc['cnpj']) . "</td>";
            echo "<td>" . htmlspecialchars($cfc['responsavel'] ?? 'N/A') . "</td>";
            echo "<td>" . htmlspecialchars($cfc['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum CFC encontrado na tabela.</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage();
    echo "</div>";
}

echo "<hr>";
echo "<h2>🔄 PRÓXIMO PASSO</h2>";
echo "<p>✅ <strong>Correção da tabela cfcs concluída!</strong></p>";
echo "<p>🎯 <strong>Próximo:</strong> TESTE #5 - CRUD de CFCs (Executar novamente)</p>";
echo "<p>📝 <strong>Instrução:</strong> Agora execute o TESTE #5 novamente para verificar se as operações CRUD estão funcionando.</p>";
?>

<style>
body { font-family: Arial, sans-serif; margin: 20px; }
h1 { color: #2c3e50; }
h2 { color: #34495e; margin-top: 30px; }
table { font-size: 14px; }
th { padding: 8px; background: #f8f9fa; }
td { padding: 6px; text-align: center; }
</style>
