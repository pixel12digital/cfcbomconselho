<?php
// Teste da API de usuários
echo "<h1>Teste da API de Usuários</h1>";

try {
    // Incluir arquivos necessários
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    require_once 'includes/auth.php';
    
    echo "✅ Arquivos incluídos com sucesso<br>";
    
    // Conectar ao banco
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Verificar estrutura da tabela usuarios
    echo "<h2>Estrutura da Tabela Usuarios:</h2>";
    $colunas = $db->fetchAll("DESCRIBE usuarios");
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
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
    
    // Verificar dados atuais
    echo "<h2>Dados Atuais da Tabela:</h2>";
    $usuarios = $db->fetchAll("SELECT * FROM usuarios");
    
    if (empty($usuarios)) {
        echo "<p>Nenhum usuário encontrado na tabela.</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>Email</th><th>Tipo</th><th>Status</th><th>Criado em</th></tr>";
        
        foreach ($usuarios as $usuario) {
            echo "<tr>";
            echo "<td>" . $usuario['id'] . "</td>";
            echo "<td>" . $usuario['nome'] . "</td>";
            echo "<td>" . $usuario['email'] . "</td>";
            echo "<td>" . $usuario['tipo'] . "</td>";
            echo "<td>" . ($usuario['status'] ?? 'N/A') . "</td>";
            echo "<td>" . ($usuario['created_at'] ?? $usuario['criado_em'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Testar inserção direta
    echo "<h2>Teste de Inserção Direta:</h2>";
    
    $testData = [
        'nome' => 'Usuário Teste API',
        'email' => 'teste.api@teste.com',
        'senha' => password_hash('123456', PASSWORD_DEFAULT),
        'tipo' => 'instrutor',
        'ativo' => true,
        'criado_em' => date('Y-m-d H:i:s')
    ];
    
    echo "Tentando inserir usuário de teste...<br>";
    echo "Dados: " . json_encode($testData, JSON_PRETTY_PRINT) . "<br>";
    
    try {
        $result = $db->insert('usuarios', $testData);
        
        if ($result) {
            echo "✅ Usuário inserido com sucesso! ID: $result<br>";
            
            // Verificar se foi inserido
            $usuarioInserido = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$result]);
            if ($usuarioInserido) {
                echo "✅ Usuário encontrado no banco: " . json_encode($usuarioInserido, JSON_PRETTY_PRINT) . "<br>";
                
                // Remover usuário de teste
                $db->delete('usuarios', 'id = ?', [$result]);
                echo "✅ Usuário de teste removido<br>";
            } else {
                echo "❌ Usuário não foi encontrado após inserção<br>";
            }
        } else {
            echo "❌ Erro ao inserir usuário<br>";
        }
    } catch (Exception $e) {
        echo "❌ Exceção ao inserir: " . $e->getMessage() . "<br>";
    }
    
    // Verificar se as funções de autenticação estão funcionando
    echo "<h2>Teste de Autenticação:</h2>";
    
    if (function_exists('isLoggedIn')) {
        echo "✅ Função isLoggedIn está disponível<br>";
        $loggedIn = isLoggedIn();
        echo "Resultado: " . ($loggedIn ? 'TRUE' : 'FALSE') . "<br>";
    } else {
        echo "❌ Função isLoggedIn não está disponível<br>";
    }
    
    if (function_exists('hasPermission')) {
        echo "✅ Função hasPermission está disponível<br>";
        $hasAdmin = hasPermission('admin');
        echo "Resultado admin: " . ($hasAdmin ? 'TRUE' : 'FALSE') . "<br>";
    } else {
        echo "❌ Função hasPermission não está disponível<br>";
    }
    
    // Testar acesso à API
    echo "<h2>Teste de Acesso à API:</h2>";
    
    $apiUrl = "http://localhost:8080/cfc-bom-conselho/admin/api/usuarios.php";
    echo "URL da API: <code>$apiUrl</code><br>";
    
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => 'Content-Type: application/json'
        ]
    ]);
    
    try {
        $response = file_get_contents($apiUrl, false, $context);
        if ($response !== false) {
            $data = json_decode($response, true);
            if ($data && isset($data['error'])) {
                echo "❌ API retornou erro: " . $data['error'] . "<br>";
            } else {
                echo "✅ API acessível<br>";
            }
        } else {
            echo "❌ Não foi possível acessar a API<br>";
        }
    } catch (Exception $e) {
        echo "❌ Erro ao acessar API: " . $e->getMessage() . "<br>";
    }
    
    echo "<hr>";
    echo "<h3>Resumo dos Problemas:</h3>";
    echo "<ul>";
    echo "<li>Se a tabela não tiver os campos corretos, a inserção falha</li>";
    echo "<li>Se as funções de autenticação não funcionarem, a API retorna 401</li>";
    echo "<li>Se houver problema na conexão com o banco, a inserção falha</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Erro: " . $e->getMessage() . "</p>";
    echo "<p>Verifique se os arquivos de configuração estão corretos.</p>";
}
?>
