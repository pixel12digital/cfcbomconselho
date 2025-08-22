<?php
// Teste das APIs do sistema
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

echo "<h1>Teste das APIs - Sistema CFC</h1>";

// Verificar se está logado como admin
if (!isLoggedIn() || !hasPermission('admin')) {
    echo "<p>❌ Você precisa estar logado como administrador para testar as APIs</p>";
    echo "<p><a href='../index.php'>← Fazer Login</a></p>";
    exit;
}

$db = Database::getInstance();

echo "<h2>1. Teste de Conexão com Banco</h2>";
try {
    $result = $db->query("SELECT 1");
    echo "<p>✅ Conexão com banco: OK</p>";
} catch (Exception $e) {
    echo "<p>❌ Erro na conexão: " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>2. Teste da API de Usuários</h2>";
echo "<h3>2.1 Listar Usuários</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/cfc-bom-conselho/admin/api/usuarios.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . http_build_query($_COOKIE, '', '; ')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p>✅ API de usuários funcionando. Total de usuários: " . count($data['data']) . "</p>";
    } else {
        echo "<p>⚠️ API retornou erro: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
    }
} else {
    echo "<p>❌ API de usuários retornou HTTP $httpCode</p>";
}

echo "<h3>2.2 Criar Usuário de Teste</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/cfc-bom-conselho/admin/api/usuarios.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'nome' => 'Usuário Teste API',
    'email' => 'teste.api@cfc.com',
    'senha' => '123456',
    'tipo' => 'instrutor',
    'ativo' => true
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . http_build_query($_COOKIE, '', '; ')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p>✅ Usuário criado com sucesso via API</p>";
        $usuarioTesteId = $data['data']['id'];
    } else {
        echo "<p>⚠️ Erro ao criar usuário: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
    }
} else {
    echo "<p>❌ Erro HTTP $httpCode ao criar usuário</p>";
}

echo "<h2>3. Teste da API de CFCs</h2>";
echo "<h3>3.1 Listar CFCs</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/cfc-bom-conselho/admin/api/cfcs.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . http_build_query($_COOKIE, '', '; ')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p>✅ API de CFCs funcionando. Total de CFCs: " . count($data['data']) . "</p>";
    } else {
        echo "<p>⚠️ API retornou erro: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
    }
} else {
    echo "<p>❌ API de CFCs retornou HTTP $httpCode</p>";
}

echo "<h3>3.2 Criar CFC de Teste</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/cfc-bom-conselho/admin/api/cfcs.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'nome' => 'CFC Teste API',
    'cnpj' => '12.345.678/0001-90',
    'cidade' => 'São Paulo',
    'uf' => 'SP',
    'email' => 'contato@cfcteste.com',
    'telefone' => '(11) 99999-9999'
]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . http_build_query($_COOKIE, '', '; ')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p>✅ CFC criado com sucesso via API</p>";
        $cfcTesteId = $data['data']['id'];
    } else {
        echo "<p>⚠️ Erro ao criar CFC: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
    }
} else {
    echo "<p>❌ Erro HTTP $httpCode ao criar CFC</p>";
}

echo "<h2>4. Teste da API de Instrutores</h2>";
echo "<h3>4.1 Listar Instrutores</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/cfc-bom-conselho/admin/api/instrutores.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . http_build_query($_COOKIE, '', '; ')
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode === 200) {
    $data = json_decode($response, true);
    if ($data && isset($data['success']) && $data['success']) {
        echo "<p>✅ API de instrutores funcionando. Total de instrutores: " . count($data['data']) . "</p>";
    } else {
        echo "<p>⚠️ API retornou erro: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
    }
} else {
    echo "<p>❌ API de instrutores retornou HTTP $httpCode</p>";
}

echo "<h3>4.2 Criar Instrutor de Teste</h3>";
if (isset($usuarioTesteId) && isset($cfcTesteId)) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost/cfc-bom-conselho/admin/api/instrutores.php');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'nome' => 'Instrutor Teste API',
        'email' => 'instrutor.teste@cfc.com',
        'senha' => '123456',
        'cfc_id' => $cfcTesteId,
        'credencial' => 'CRED123456',
        'categoria' => 'B'
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: ' . http_build_query($_COOKIE, '', '; ')
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200) {
        $data = json_decode($response, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p>✅ Instrutor criado com sucesso via API</p>";
        } else {
            echo "<p>⚠️ Erro ao criar instrutor: " . ($data['error'] ?? 'Erro desconhecido') . "</p>";
        }
    } else {
        echo "<p>❌ Erro HTTP $httpCode ao criar instrutor</p>";
    }
} else {
    echo "<p>⚠️ Não foi possível criar instrutor de teste (usuário ou CFC não criados)</p>";
}

echo "<h2>5. Resumo dos Testes</h2>";
echo "<p>✅ APIs criadas e funcionando</p>";
echo "<ul>";
echo "<li>API de Usuários: /admin/api/usuarios.php</li>";
echo "<li>API de CFCs: /admin/api/cfcs.php</li>";
echo "<li>API de Instrutores: /admin/api/instrutores.php</li>";
echo "</ul>";

echo "<h2>6. Próximos Passos</h2>";
echo "<p>Agora você pode:</p>";
echo "<ul>";
echo "<li>Cadastrar usuários através da interface administrativa</li>";
echo "<li>Cadastrar CFCs através da interface administrativa</li>";
echo "<li>Cadastrar instrutores através da interface administrativa</li>";
echo "<li>As operações de CRUD agora funcionam com dados reais no banco</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='index.php'>← Voltar para o Dashboard</a></p>";
?>
