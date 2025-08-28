<?php
// Teste direto do modal de instrutores
require_once 'includes/config.php';

echo "<h2>🧪 Teste Direto do Modal de Instrutores</h2>";

// Verificar se está logado
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>✅ Usuário logado: ID {$_SESSION['user_id']}</p>";
    echo "<p style='color: green;'>✅ Tipo: {$_SESSION['user_type']}</p>";
    
    // Testar carregamento de usuários
    echo "<hr><h3>👥 Testando Carregamento de Usuários</h3>";
    
    try {
        $db = Database::getInstance();
        $usuarios = $db->fetchAll("SELECT id, nome, email, tipo, ativo FROM usuarios ORDER BY nome");
        
        if ($usuarios) {
            echo "<p style='color: green;'>✅ Usuários carregados do banco: " . count($usuarios) . "</p>";
            echo "<ul>";
            foreach ($usuarios as $usuario) {
                echo "<li><strong>{$usuario['nome']}</strong> ({$usuario['email']}) - {$usuario['tipo']}</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhum usuário encontrado no banco</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao carregar usuários: " . $e->getMessage() . "</p>";
    }
    
    // Testar carregamento de CFCs
    echo "<hr><h3>🏢 Testando Carregamento de CFCs</h3>";
    
    try {
        $cfcs = $db->fetchAll("SELECT id, nome, cnpj FROM cfcs ORDER BY nome");
        
        if ($cfcs) {
            echo "<p style='color: green;'>✅ CFCs carregados do banco: " . count($cfcs) . "</p>";
            echo "<ul>";
            foreach ($cfcs as $cfc) {
                echo "<li><strong>{$cfc['nome']}</strong> (CNPJ: {$cfc['cnpj']})</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color: orange;'>⚠️ Nenhum CFC encontrado no banco</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Erro ao carregar CFCs: " . $e->getMessage() . "</p>";
    }
    
    // Testar API via cURL
    echo "<hr><h3>🌐 Testando API via cURL</h3>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/usuarios.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIE, 'CFC_SESSION=' . session_id());
    curl_setopt($ch, CURLOPT_HEADER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    echo "<p><strong>Status HTTP da API:</strong> {$httpCode}</p>";
    
    if ($httpCode === 200) {
        echo "<p style='color: green;'>✅ API funcionando via cURL!</p>";
        
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $body = substr($response, $headerSize);
        
        $data = json_decode($body, true);
        if ($data && isset($data['success'])) {
            echo "<p style='color: green;'>✅ JSON válido da API: " . count($data['data']) . " usuários</p>";
        } else {
            echo "<p style='color: red;'>❌ JSON inválido da API</p>";
            echo "<pre>" . htmlspecialchars($body) . "</pre>";
        }
    } else {
        echo "<p style='color: red;'>❌ Erro HTTP da API: {$httpCode}</p>";
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
echo "<p><strong>🔍 Debug - Cookies e Sessão:</strong></p>";
echo "<p>Session Name: " . session_name() . "</p>";
echo "<p>CFC_SESSION Cookie: " . ($_COOKIE['CFC_SESSION'] ?? 'NÃO DEFINIDO') . "</p>";
echo "<p>Todos os Cookies:</p>";
echo "<pre>" . print_r($_COOKIE, true) . "</pre>";
?>
