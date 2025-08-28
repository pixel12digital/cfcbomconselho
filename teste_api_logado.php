<?php
// Teste simples para verificar se as APIs funcionam
// Incluir o config.php para usar a mesma sessão do sistema
require_once 'includes/config.php';

echo "<h2>🔍 Teste de APIs - Status da Sessão</h2>";

// Verificar se está logado
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ Usuário logado: ID {$_SESSION['user_id']}</p>";
    echo "<p style='color: green;'>✅ Tipo: {$_SESSION['user_type']}</p>";
    
    // Testar API de usuários
    echo "<hr><h3>🧪 Testando API de Usuários</h3>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/usuarios.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'CFC_SESSION=' . session_id());
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p><strong>Status HTTP:</strong> {$httpCode}</p>";
    
    if ($httpCode === 200) {
        echo "<p style='color: green;'>✅ API funcionando!</p>";
        
        // Extrair apenas o corpo da resposta
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        
        $data = json_decode($body, true);
        if ($data && isset($data['success'])) {
            echo "<p style='color: green;'>✅ JSON válido recebido!</p>";
            echo "<p><strong>Total de usuários:</strong> " . count($data['data']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ JSON inválido ou erro na API</p>";
            echo "<pre>" . htmlspecialchars($body) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Erro HTTP: {$httpCode}</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
    }
    
    curl_close($ch);
    
} else {
    echo "<p style='color: red;'>❌ Usuário NÃO está logado!</p>";
    echo "<p><strong>Para testar:</strong></p>";
    echo "<ol>";
    echo "<li>Faça login em: <a href='admin/'>http://localhost:8080/cfc-bom-conselho/admin/</a></li>";
    echo "<li>Use as credenciais: admin@cfc.com / admin123</li>";
    echo "<li>Depois recarregue esta página</li>";
    echo "</ol>";
}

echo "<hr>";
echo "<p><strong>📋 Informações da sessão:</strong></p>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

echo "<hr>";
echo "<p><strong>🔍 Debug - Verificando includes:</strong></p>";
echo "<p>Config.php incluído: " . (defined('APP_NAME') ? '✅ SIM' : '❌ NÃO') . "</p>";
echo "<p>APP_NAME: " . (defined('APP_NAME') ? APP_NAME : 'NÃO DEFINIDO') . "</p>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";
echo "<p>Session Name: " . session_name() . "</p>";
echo "<p>CFC_SESSION Cookie: " . ($_COOKIE['CFC_SESSION'] ?? 'NÃO DEFINIDO') . "</p>";
?>
