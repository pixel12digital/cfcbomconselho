<?php
// Teste direto da API de CFCs
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Teste Direto da API de CFCs</h1>";

// Simular uma requisição DELETE para a API
echo "<h2>1. Simulando Requisição DELETE</h2>";

// Definir variáveis globais necessárias
$_SERVER['REQUEST_METHOD'] = 'DELETE';
$_GET['id'] = '26';

// Iniciar sessão se necessário
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Simular usuário logado
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';
$_SESSION['last_activity'] = time();

echo "<p>✅ Método HTTP: {$_SERVER['REQUEST_METHOD']}</p>";
echo "<p>✅ ID do CFC: {$_GET['id']}</p>";
echo "<p>✅ Sessão iniciada</p>";

// Testar a API
echo "<h2>2. Executando API</h2>";

try {
    // Capturar saída da API
    ob_start();
    
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
                echo "<p>🎉 <strong>SUCESSO!</strong> CFC excluído!</p>";
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
    echo "<p>❌ Erro ao executar API: " . $e->getMessage() . "</p>";
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
    
    // Verificar se o CFC ainda existe
    $cfc = $db->fetch("SELECT * FROM cfcs WHERE id = 26");
    if ($cfc) {
        echo "<p>⚠️ CFC ID 26 ainda existe: {$cfc['nome']}</p>";
        echo "<p>💡 A exclusão pode não ter funcionado</p>";
    } else {
        echo "<p>✅ CFC ID 26 foi excluído com sucesso!</p>";
    }
    
    // Listar CFCs restantes
    $cfcs = $db->fetchAll("SELECT id, nome, cnpj FROM cfcs ORDER BY id");
    echo "<p>📋 CFCs restantes no banco:</p>";
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
echo "<p>1. Se a API funcionou, teste o botão de exclusão na página</p>";
echo "<p>2. Se não funcionou, verifique os logs de erro</p>";
echo "<p>3. Confirme se o usuário tem permissão de admin</p>";
?>
