<?php
// Teste da API de instrutores com sessão ativa
echo "<h1>Teste da API de Instrutores com Sessão</h1>";

// 1. Iniciar sessão e verificar status
echo "<h2>1. Status da Sessão</h2>";
session_start();
echo "Session ID: " . (session_id() ?: 'Nenhuma') . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Nenhum') . "<br>";
echo "User Type: " . ($_SESSION['user_type'] ?? 'Nenhum') . "<br>";

// 2. Incluir arquivos necessários
echo "<h2>2. Inclusão de Arquivos</h2>";
try {
    require_once 'includes/config.php';
    echo "✅ config.php incluído<br>";
    
    require_once 'includes/database.php';
    echo "✅ database.php incluído<br>";
    
    require_once 'includes/auth.php';
    echo "✅ auth.php incluído<br>";
    
} catch (Exception $e) {
    echo "❌ Erro ao incluir arquivos: " . $e->getMessage() . "<br>";
    exit;
}

// 3. Verificar funções de autenticação
echo "<h2>3. Verificação de Funções</h2>";
if (function_exists('isLoggedIn')) {
    echo "✅ isLoggedIn() está disponível<br>";
    $loggedIn = isLoggedIn();
    echo "Resultado: " . ($loggedIn ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "❌ isLoggedIn() não está disponível<br>";
    exit;
}

if (function_exists('hasPermission')) {
    echo "✅ hasPermission() está disponível<br>";
    $hasAdmin = hasPermission('admin');
    echo "Resultado admin: " . ($hasAdmin ? 'TRUE' : 'FALSE') . "<br>";
} else {
    echo "❌ hasPermission() não está disponível<br>";
    exit;
}

// 4. Testar conexão com banco
echo "<h2>4. Teste de Conexão com Banco</h2>";
try {
    $db = Database::getInstance();
    echo "✅ Conexão com banco estabelecida<br>";
    
    // Verificar se há instrutores
    $instrutores = $db->fetchAll("SELECT COUNT(*) as total FROM instrutores");
    echo "Total de instrutores: " . ($instrutores[0]['total'] ?? 'Erro') . "<br>";
    
    if (($instrutores[0]['total'] ?? 0) > 0) {
        // Pegar o primeiro instrutor
        $primeiro = $db->fetch("SELECT * FROM instrutores LIMIT 1");
        echo "Primeiro instrutor - ID: " . ($primeiro['id'] ?? 'N/A') . "<br>";
        echo "Usuario ID: " . ($primeiro['usuario_id'] ?? 'N/A') . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Erro no banco: " . $e->getMessage() . "<br>";
    exit;
}

// 5. Testar API com sessão ativa
echo "<h2>5. Teste da API com Sessão</h2>";

if (!$loggedIn) {
    echo "❌ Usuário não está logado. Faça login primeiro!<br>";
    echo "<p><a href='admin/' target='_blank'>Clique aqui para fazer login</a></p>";
    exit;
}

if (!$hasAdmin) {
    echo "❌ Usuário não tem permissão de administrador<br>";
    exit;
}

echo "✅ Usuário logado e com permissão de admin<br>";

// 6. Simular requisição para a API
echo "<h2>6. Simulando Requisição para API</h2>";

// Criar contexto com cookies da sessão
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Cookie: ' . session_name() . '=' . session_id()
        ]
    ]
]);

$apiUrl = "http://localhost:8080/cfc-bom-conselho/admin/api/instrutores.php";
echo "URL da API: <code>$apiUrl</code><br>";

try {
    echo "Enviando requisição GET...<br>";
    
    $response = file_get_contents($apiUrl, false, $context);
    
    if ($response !== false) {
        $data = json_decode($response, true);
        echo "<pre>Resposta da API: ";
        print_r($data);
        echo "</pre>";
        
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>✅ API funcionando! Encontrados " . count($data['data']) . " instrutores.</p>";
            
            // Testar exclusão
            if (isset($primeiro) && $primeiro['id']) {
                echo "<h2>7. Teste de Exclusão</h2>";
                echo "<p>Para testar a exclusão do instrutor ID " . $primeiro['id'] . ", clique no link abaixo:</p>";
                echo "<p><a href='teste_exclusao_direta.php?id=" . $primeiro['id'] . "' target='_blank'>Testar Exclusão Direta</a></p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ Erro na API: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Não foi possível acessar a API</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Exceção: " . $e->getMessage() . "</p>";
}

// 7. Verificar logs de erro
echo "<h2>8. Verificando Logs</h2>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    echo "Arquivo de log: <code>$logFile</code><br>";
    
    // Ler as últimas linhas do log
    $logLines = file($logFile);
    $ultimasLinhas = array_slice($logLines, -10); // Últimas 10 linhas
    
    echo "<h3>Últimas linhas do log:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 200px; overflow-y: auto;'>";
    foreach ($ultimasLinhas as $linha) {
        echo htmlspecialchars($linha);
    }
    echo "</pre>";
} else {
    echo "Arquivo de log não encontrado ou não configurado.<br>";
}

echo "<hr>";
echo "<h3>Resumo:</h3>";
echo "<ul>";
echo "<li>✅ Sessão: " . ($loggedIn ? 'ATIVA' : 'INATIVA') . "</li>";
echo "<li>✅ Permissão Admin: " . ($hasAdmin ? 'SIM' : 'NÃO') . "</li>";
echo "<li>✅ Banco: Conectado</li>";
echo "<li>✅ Instrutores: " . ($instrutores[0]['total'] ?? '0') . " encontrados</li>";
echo "</ul>";

echo "<h3>Próximos Passos:</h3>";
echo "<ol>";
echo "<li>Se a API funcionar, teste a exclusão no sistema</li>";
echo "<li>Se não funcionar, verifique os logs de erro</li>";
echo "<li>Compare com outras APIs que funcionam</li>";
echo "</ol>";
?>
