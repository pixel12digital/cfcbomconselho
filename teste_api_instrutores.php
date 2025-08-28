<?php
// Script de teste para a API de instrutores
// Execute este arquivo para testar se a API está funcionando

echo "<h1>Teste da API de Instrutores</h1>";

// Testar GET - Listar instrutores
echo "<h2>1. Testando GET - Listar instrutores</h2>";
$url = 'http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

try {
    $response = file_get_contents($url, false, $context);
    if ($response !== false) {
        $data = json_decode($response, true);
        echo "<pre>Resposta da API: ";
        print_r($data);
        echo "</pre>";
        
        if ($data['success']) {
            echo "<p style='color: green;'>✅ API funcionando! Encontrados " . count($data['data']) . " instrutores.</p>";
            
            // Verificar se os dados estão corretos
            foreach ($data['data'] as $instrutor) {
                echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px;'>";
                echo "<strong>ID:</strong> " . $instrutor['id'] . "<br>";
                echo "<strong>Nome:</strong> " . ($instrutor['nome'] ?? 'N/A') . "<br>";
                echo "<strong>Nome Usuário:</strong> " . ($instrutor['nome_usuario'] ?? 'N/A') . "<br>";
                echo "<strong>Email:</strong> " . ($instrutor['email'] ?? 'N/A') . "<br>";
                echo "<strong>CFC ID:</strong> " . ($instrutor['cfc_id'] ?? 'N/A') . "<br>";
                echo "<strong>CFC Nome:</strong> " . ($instrutor['cfc_nome'] ?? 'N/A') . "<br>";
                echo "<strong>Credencial:</strong> " . ($instrutor['credencial'] ?? 'N/A') . "<br>";
                echo "<strong>Status:</strong> " . ($instrutor['ativo'] ? 'Ativo' : 'Inativo') . "<br>";
                echo "</div>";
            }
        } else {
            echo "<p style='color: red;'>❌ Erro na API: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Erro ao acessar a API</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exceção: " . $e->getMessage() . "</p>";
}

// Testar GET - Buscar instrutor específico (se existir)
echo "<h2>2. Testando GET - Buscar instrutor específico</h2>";
if (isset($data['data']) && count($data['data']) > 0) {
    $primeiroInstrutor = $data['data'][0];
    $id = $primeiroInstrutor['id'];
    
    $urlEspecifico = $url . "?id=" . $id;
    try {
        $responseEspecifico = file_get_contents($urlEspecifico, false, $context);
        if ($responseEspecifico !== false) {
            $dataEspecifico = json_decode($responseEspecifico, true);
            echo "<pre>Resposta da API para ID $id: ";
            print_r($dataEspecifico);
            echo "</pre>";
            
            if ($dataEspecifico['success']) {
                echo "<p style='color: green;'>✅ Busca por ID funcionando!</p>";
            } else {
                echo "<p style='color: red;'>❌ Erro na busca por ID: " . ($dataEspecifico['error'] ?? 'Erro desconhecido') . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exceção na busca por ID: " . $e->getMessage() . "</p>";
    }
}

// Testar estrutura da tabela
echo "<h2>3. Verificando estrutura da tabela</h2>";
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar estrutura da tabela instrutores
    $colunas = $db->fetchAll("DESCRIBE instrutores");
    echo "<h3>Estrutura da tabela instrutores:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th><th>Extra</th></tr>";
    foreach ($colunas as $coluna) {
        echo "<tr>";
        echo "<td>" . $coluna['Field'] . "</td>";
        echo "<td>" . $coluna['Type'] . "</td>";
        echo "<td>" . $coluna['Null'] . "</td>";
        echo "<td>" . $coluna['Key'] . "</td>";
        echo "<td>" . $coluna['Default'] . "</td>";
        echo "<td>" . $coluna['Extra'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verificar dados da tabela
    echo "<h3>Dados da tabela instrutores:</h3>";
    $dados = $db->fetchAll("SELECT * FROM instrutores LIMIT 5");
    if ($dados) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr>";
        foreach (array_keys($dados[0]) as $coluna) {
            echo "<th>$coluna</th>";
        }
        echo "</tr>";
        foreach ($dados as $linha) {
            echo "<tr>";
            foreach ($linha as $valor) {
                echo "<td>" . ($valor ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>Nenhum dado encontrado na tabela instrutores.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro ao verificar estrutura: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>Instruções para corrigir o problema:</strong></p>";
echo "<ol>";
echo "<li>Execute o script SQL 'adicionar_colunas_instrutores.sql' no phpMyAdmin</li>";
echo "<li>Recarregue a página de instrutores no sistema</li>";
echo "<li>Teste criar um novo instrutor</li>";
echo "<li>Verifique se os dados estão sendo salvos e exibidos corretamente</li>";
echo "</ol>";
?>
