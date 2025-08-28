<?php
// Teste simples de exclusão de instrutores
echo "<h1>Teste Simples de Exclusão</h1>";

// 1. Verificar se a sessão está funcionando
echo "<h2>1. Verificação de Sessão</h2>";
session_start();
echo "Session ID: " . (session_id() ?: 'Nenhuma') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Nenhum') . "<br>";
echo "User Type: " . ($_SESSION['user_type'] ?? 'Nenhum') . "<br>";

// 2. Verificar se as funções estão disponíveis
echo "<h2>2. Verificação de Funções</h2>";
if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn() está disponível<br>";
    echo "Resultado: " . (isLoggedIn() ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "❌ isLoggedIn() não está disponível<br>";
}

if (function_exists('hasPermission')) {
    echo "✅ hasPermission() está disponível<br>";
    echo "Resultado admin: " . (hasPermission('admin') ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "❌ hasPermission() não está disponível<br>";
}

// 3. Testar conexão com banco
echo "<h2>3. Teste de Conexão com Banco</h2>";
try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Verificar se há instrutores
    $instrutores = $db->fetchAll("SELECT COUNT(*) as total FROM instrutores");
    echo "Total de instrutores: " . ($instrutores[0]['total'] ?? 'Erro') . "<br>";
    
    if (($instrutores[0]['total'] ?? 0) > 0) {
        // Pegar o primeiro instrutor
        $primeiro = $db->fetch("SELECT * FROM instrutores LIMIT 1");
        echo "Primeiro instrutor - ID: " . ($primeiro['id'] ?? 'N/A') . "<br>";
        
        // Testar exclusão direta no banco
        echo "<h2>4. Teste de Exclusão Direta</h2>";
        echo "<p><strong>ATENÇÃO:</strong> Este teste irá EXCLUIR um instrutor real!</p>";
        echo "<p>ID do instrutor: " . ($primeiro['id'] ?? 'N/A') . "</p>";
        
        if (isset($_GET['test_delete']) && $_GET['test_delete'] === 'true') {
            echo "<p style='color: red;'>EXCLUINDO INSTRUTOR...</p>";
            
            try {
                $db->beginTransaction();
                
                // Excluir instrutor
                $result = $db->delete('instrutores', 'id = ?', [$primeiro['id']]);
                if ($result) {
                    echo "✅ Instrutor excluído<br>";
                    
                    // Excluir usuário
                    $result = $db->delete('usuarios', 'id = ?', [$primeiro['usuario_id']]);
                    if ($result) {
                        echo "✅ Usuário excluído<br>";
                        $db->commit();
                        echo "✅ Transação confirmada<br>";
                    } else {
                        throw new Exception('Erro ao excluir usuário');
                    }
                } else {
                    throw new Exception('Erro ao excluir instrutor');
                }
                
            } catch (Exception $e) {
                $db->rollback();
                echo "❌ Erro na transação: " . $e->getMessage() . "<br>";
            }
        } else {
            echo "<p>Para testar a exclusão, adicione <code>?test_delete=true</code> na URL</p>";
        }
        
    } else {
        echo "⚠️ Nenhum instrutor encontrado para testar<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "<br>";
}

// 4. Verificar se a API está acessível
echo "<h2>5. Teste de Acesso à API</h2>";
$apiUrl = "http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php";
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
echo "<li>Se a sessão não estiver funcionando, o usuário não está logado</li>";
echo "<li>Se as funções não estiverem disponíveis, há problema nos includes</li>";
echo "<li>Se o banco não conectar, há problema na configuração</li>";
echo "<li>Se a API não acessar, há problema de permissão ou roteamento</li>";
echo "</ul>";

echo "<h3>Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Verifique se está logado no sistema</li>";
echo "<li>Verifique se tem permissão de administrador</li>";
echo "<li>Execute o teste de exclusão direta se necessário</li>";
echo "<li>Verifique os logs de erro do servidor</li>";
echo "</ol>";
?>
