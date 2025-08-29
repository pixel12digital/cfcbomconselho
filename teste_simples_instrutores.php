<?php
// =====================================================
// TESTE SIMPLES DA API DE INSTRUTORES
// =====================================================

// Habilitar exibição de erros para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h1>🧪 Teste Simples da API de Instrutores</h1>";

// Verificar se a API existe
$apiPath = './admin/api/instrutores.php';
if (!file_exists($apiPath)) {
    echo "<p style='color: red;'>❌ <strong>ERRO:</strong> API não encontrada em: $apiPath</p>";
    exit;
}

echo "<p style='color: green;'>✅ <strong>SUCESSO:</strong> API encontrada em: $apiPath</p>";

// Verificar se as dependências existem
$deps = [
    './includes/config.php' => 'Configuração',
    './includes/database.php' => 'Database',
    './includes/auth.php' => 'Autenticação'
];

foreach ($deps as $dep => $nome) {
    if (file_exists($dep)) {
        echo "<p style='color: green;'>✅ $nome encontrado</p>";
    } else {
        echo "<p style='color: red;'>❌ $nome NÃO encontrado em: $dep</p>";
    }
}

// Verificar estrutura da tabela
echo "<h2>🔍 Verificando Estrutura da Tabela:</h2>";

try {
    require_once './includes/config.php';
    require_once './includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar se a tabela instrutores existe
    $tables = $db->fetchAll("SHOW TABLES LIKE 'instrutores'");
    if (empty($tables)) {
        echo "<p style='color: red;'>❌ Tabela 'instrutores' não encontrada!</p>";
    } else {
        echo "<p style='color: green;'>✅ Tabela 'instrutores' encontrada</p>";
        
        // Verificar estrutura
        $columns = $db->fetchAll("DESCRIBE instrutores");
        echo "<h3>📋 Estrutura da Tabela (${count($columns)} colunas):</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
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
        
        // Verificar se há dados
        $count = $db->count('instrutores');
        echo "<p><strong>Total de instrutores na tabela:</strong> $count</p>";
        
        if ($count > 0) {
            $instrutores = $db->fetchAll("SELECT * FROM instrutores ORDER BY id DESC LIMIT 3");
            echo "<h3>📊 Últimos 3 Instrutores:</h3>";
            echo "<pre>" . print_r($instrutores, true) . "</pre>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>Erro ao conectar com banco:</strong> " . $e->getMessage() . "</p>";
}

// Testar se conseguimos incluir a API (sem executar)
echo "<h2>🧪 Testando Inclusão da API:</h2>";

try {
    // Simular variáveis necessárias
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_GET = [];
    
    // Tentar incluir a API (apenas para verificar sintaxe)
    ob_start();
    include $apiPath;
    $output = ob_get_clean();
    
    echo "<p style='color: green;'>✅ <strong>SUCESSO:</strong> API pode ser incluída sem erros de sintaxe</p>";
    
    // Verificar se a saída é JSON válido
    if (json_decode($output)) {
        echo "<p style='color: green;'>✅ <strong>SUCESSO:</strong> API retorna JSON válido</p>";
        echo "<h3>📊 Resposta da API (GET):</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ <strong>AVISO:</strong> API não retorna JSON válido</p>";
        echo "<h3>📊 Saída da API:</h3>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ <strong>ERRO ao incluir API:</strong> " . $e->getMessage() . "</p>";
} catch (ParseError $e) {
    echo "<p style='color: red;'>❌ <strong>ERRO de sintaxe na API:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong> Verifique os resultados acima.</p>";
echo "<p><strong>Próximo passo:</strong> Testar a criação de um instrutor através do formulário web.</p>";
?>
