<?php
// Teste direto da API de CFCs - Método POST
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Teste Direto da API de CFCs - POST</h1>";

// Simular uma requisição POST para a API
echo "<h2>1. Simulando Requisição POST</h2>";

// Definir variáveis globais necessárias
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simular usuário logado
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';
$_SESSION['last_activity'] = time();

echo "<p>✅ Método HTTP: {$_SERVER['REQUEST_METHOD']}</p>";
echo "<p>✅ Content-Type: {$_SERVER['CONTENT_TYPE']}</p>";
echo "<p>✅ Sessão iniciada</p>";

// Simular dados JSON de entrada
$jsonData = [
    'nome' => 'CFC Teste API',
    'cnpj' => '98.765.432/0001-10',
    'razao_social' => 'CFC Teste API Ltda',
    'email' => 'teste@cfc.com',
    'telefone' => '(11) 99999-9999',
    'cep' => '01234-567',
    'endereco' => 'Rua Teste API, 123',
    'bairro' => 'Centro',
    'cidade' => 'São Paulo',
    'uf' => 'SP',
    'responsavel_id' => null,
    'ativo' => true,
    'observacoes' => 'CFC de teste via API'
];

// Simular php://input
$jsonString = json_encode($jsonData);
echo "<p>✅ Dados JSON simulados: " . htmlspecialchars($jsonString) . "</p>";

// Testar a API
echo "<h2>2. Executando API</h2>";

try {
    // Capturar saída da API
    ob_start();
    
    // Simular php://input
    $GLOBALS['php_input_simulation'] = $jsonString;
    
    // Incluir a API
    include 'admin/api/cfcs.php';
    
    $output = ob_get_clean();
    
    echo "<p>✅ API executada com sucesso</p>";
    
    // Tentar decodificar JSON
    $jsonStart = strpos($output, '{');
    if ($jsonStart !== false) {
        $jsonContent = substr($output, $jsonStart);
        $data = json_decode($jsonContent, true);
        
        if ($data) {
            echo "<p>✅ Resposta JSON válida:</p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            if ($data['success']) {
                echo "<p>🎉 <strong>SUCESSO!</strong> CFC criado!</p>";
            } else {
                echo "<p>❌ <strong>ERRO:</strong> {$data['error']}</p>";
            }
        } else {
            echo "<p>⚠️ Resposta não é JSON válido</p>";
            echo "<pre>" . htmlspecialchars($output) . "</pre>";
        }
    } else {
        echo "<p>⚠️ Nenhuma resposta JSON encontrada</p>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Exceção ao executar API: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
} catch (Error $e) {
    echo "<p>❌ Erro fatal: " . $e->getMessage() . "</p>";
    echo "<p>Arquivo: " . $e->getFile() . "</p>";
    echo "<p>Linha: " . $e->getLine() . "</p>";
}

echo "<hr>";
echo "<h2>3. Verificação do Banco</h2>";

try {
    require_once 'includes/config.php';
    require_once 'includes/database.php';
    
    $db = Database::getInstance();
    
    // Verificar se o CFC foi criado
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE cnpj = '98.765.432/0001-10'");
    if ($cfc) {
        echo "<p>✅ CFC criado com sucesso no banco!</p>";
        echo "<p>📋 Detalhes: ID {$cfc['id']} - {$cfc['nome']}</p>";
    } else {
        echo "<p>⚠️ CFC não foi criado no banco</p>";
    }
    
    // Listar CFCs existentes
    $cfcs = $db->fetchAll("SELECT id, nome, cnpj FROM cfcs ORDER BY id DESC LIMIT 5");
    echo "<p>📋 Últimos CFCs no banco:</p>";
    echo "<ul>";
    foreach ($cfcs as $cfc) {
        echo "<li>ID {$cfc['id']}: {$cfc['nome']} ({$cfc['cnpj']})</li>";
    }
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<p>❌ Erro ao verificar banco: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>🧪 Próximos Passos</h2>";
echo "<p>1. Se a API funcionou, o problema pode estar na autenticação</p>";
echo "<p>2. Se não funcionou, verifique os logs de erro do PHP</p>";
echo "<p>3. Confirme se a tabela cfcs existe e tem a estrutura correta</p>";
?>
