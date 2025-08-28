<?php
// Teste real da API de CFCs via HTTP
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Teste Real da API de CFCs</h1>";

// Dados para teste
$jsonData = [
    'nome' => 'CFC Teste Real',
    'cnpj' => '99.999.999/0001-99',
    'razao_social' => 'CFC Teste Real Ltda',
    'email' => 'teste@real.com',
    'telefone' => '(11) 88888-8888',
    'cep' => '99999-999',
    'endereco' => 'Rua Teste Real, 999',
    'bairro' => 'Centro',
    'cidade' => 'São Paulo',
    'uf' => 'SP',
    'responsavel_id' => null,
    'ativo' => true,
    'observacoes' => 'CFC de teste via API real'
];

echo "<h2>1. Dados para Teste</h2>";
echo "<pre>" . json_encode($jsonData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";

// Fazer requisição real para a API
echo "<h2>2. Fazendo Requisição HTTP Real</h2>";

$url = 'http://localhost:8080/cfc-bom-conselho/admin/api/cfcs.php';
$jsonString = json_encode($jsonData);

echo "<p>URL: {$url}</p>";
echo "<p>Método: POST</p>";
echo "<p>Content-Type: application/json</p>";

// Usar cURL para fazer a requisição
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonString);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($jsonString),
    'X-Requested-With: XMLHttpRequest'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 30);

echo "<p>🔄 Enviando requisição...</p>";

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<h2>3. Resposta da API</h2>";
echo "<p>HTTP Code: <strong>{$httpCode}</strong></p>";

if ($error) {
    echo "<p>❌ Erro cURL: {$error}</p>";
} else {
    echo "<p>✅ Requisição enviada com sucesso</p>";
    
    if ($httpCode === 200) {
        echo "<p>🎉 API retornou sucesso!</p>";
        
        // Tentar decodificar JSON
        $data = json_decode($response, true);
        if ($data) {
            echo "<p>📋 Resposta JSON:</p>";
            echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
            
            if ($data['success']) {
                echo "<p>🎉 <strong>SUCESSO TOTAL!</strong> CFC criado!</p>";
            } else {
                echo "<p>⚠️ API retornou erro: {$data['error']}</p>";
            }
        } else {
            echo "<p>⚠️ Resposta não é JSON válido</p>";
            echo "<pre>" . htmlspecialchars($response) . "</pre>";
        }
    } else {
        echo "<p>❌ API retornou erro HTTP {$httpCode}</p>";
        echo "<p>📋 Resposta completa:</p>";
        echo "<pre>" . htmlspecialchars($response) . "</pre>";
        
        // Verificar se é HTML
        if (strpos($response, '<!DOCTYPE') !== false || strpos($response, '<html') !== false) {
            echo "<p>⚠️ API está retornando HTML em vez de JSON</p>";
            echo "<p>🔍 Isso indica um erro PHP fatal na API</p>";
        }
    }
}

echo "<hr>";
echo "<h2>🧪 Análise</h2>";

if ($httpCode === 500) {
    echo "<p>❌ <strong>ERRO 500:</strong> Problema interno no servidor</p>";
    echo "<p>🔍 Possíveis causas:</p>";
    echo "<ul>";
    echo "<li>Erro de sintaxe PHP na API</li>";
    echo "<li>Include de arquivo inexistente</li>";
    echo "<li>Erro de conexão com banco</li>";
    echo "<li>Função não definida</li>";
    echo "</ul>";
} elseif ($httpCode === 200) {
    echo "<p>✅ <strong>SUCESSO:</strong> API funcionando perfeitamente</p>";
} else {
    echo "<p>⚠️ <strong>ERRO {$httpCode}:</strong> Outro tipo de problema</p>";
}

echo "<p>📋 <strong>Próximo passo:</strong> Se der erro 500, verificar logs do PHP</p>";
?>
