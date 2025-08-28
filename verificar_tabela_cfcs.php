<?php
// Verificar estrutura da tabela cfcs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔍 Verificação da Tabela CFCs</h1>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    echo "<p>✅ Conexão com banco estabelecida</p>";
    
    // Verificar se a tabela existe
    echo "<h2>1. Verificação da Tabela</h2>";
    
    $tables = $db->fetchAll("SHOW TABLES LIKE 'cfcs'");
    if (empty($tables)) {
        echo "<p>❌ Tabela 'cfcs' NÃO EXISTE!</p>";
        exit;
    } else {
        echo "<p>✅ Tabela 'cfcs' existe</p>";
    }
    
    // Verificar estrutura da tabela
    echo "<h2>2. Estrutura da Tabela</h2>";
    
    $columns = $db->fetchAll("DESCRIBE cfcs");
    if (empty($columns)) {
        echo "<p>❌ Não foi possível obter a estrutura da tabela</p>";
        exit;
    }
    
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
    
    // Verificar se as colunas obrigatórias existem
    echo "<h2>3. Verificação de Colunas Obrigatórias</h2>";
    
    $requiredColumns = ['id', 'nome', 'cnpj', 'cidade', 'uf', 'criado_em'];
    $existingColumns = array_column($columns, 'Field');
    
    foreach ($requiredColumns as $required) {
        if (in_array($required, $existingColumns)) {
            echo "<p>✅ Coluna '{$required}' existe</p>";
        } else {
            echo "<p>❌ Coluna '{$required}' NÃO EXISTE</p>";
        }
    }
    
    // Verificar se há dados na tabela
    echo "<h2>4. Dados na Tabela</h2>";
    
    $count = $db->count('cfcs');
    echo "<p>📊 Total de CFCs na tabela: {$count}</p>";
    
    if ($count > 0) {
        $cfcs = $db->fetchAll("SELECT id, nome, cnpj, cidade, uf FROM cfcs ORDER BY id DESC LIMIT 5");
        echo "<p>📋 Últimos CFCs:</p>";
        echo "<ul>";
        foreach ($cfcs as $cfc) {
            echo "<li>ID {$cfc['id']}: {$cfc['nome']} - {$cfc['cnpj']} - {$cfc['cidade']}/{$cfc['uf']}</li>";
        }
        echo "</ul>";
    }
    
    // Testar inserção
    echo "<h2>5. Teste de Inserção</h2>";
    
    try {
        $testData = [
            'nome' => 'CFC Teste Estrutura',
            'cnpj' => '11.111.111/0001-11',
            'razao_social' => 'CFC Teste Estrutura Ltda',
            'endereco' => 'Rua Teste, 123',
            'bairro' => 'Centro',
            'cidade' => 'Teste',
            'uf' => 'TS',
            'cep' => '00000-000',
            'telefone' => '(00) 00000-0000',
            'email' => 'teste@estrutura.com',
            'responsavel_id' => null,
            'ativo' => 1,
            'observacoes' => 'Teste de estrutura da tabela',
            'criado_em' => date('Y-m-d H:i:s')
        ];
        
        $result = $db->insert('cfcs', $testData);
        
        if ($result) {
            echo "<p>✅ Teste de inserção bem-sucedido! ID: {$result}</p>";
            
            // Remover o registro de teste
            $db->delete('cfcs', 'id = ?', [$result]);
            echo "<p>🗑️ Registro de teste removido</p>";
        } else {
            echo "<p>❌ Teste de inserção falhou</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Erro no teste de inserção: " . $e->getMessage() . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Erro geral: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h2>🧪 Próximos Passos</h2>";
echo "<p>1. Se a tabela não existir, execute o script de criação</p>";
echo "<p>2. Se a estrutura estiver incorreta, verifique o SQL de criação</p>";
echo "<p>3. Se tudo estiver OK, o problema pode estar na API</p>";
?>
