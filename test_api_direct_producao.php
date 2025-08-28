<?php
// Teste direto das APIs em produção
header('Content-Type: text/html; charset=UTF-8');

// Configurações para produção
$baseUrl = 'https://linen-mantis-198436.hostingersite.com';
$apis = [
    'instrutores' => $baseUrl . '/admin/api/instrutores.php',
    'usuarios' => $baseUrl . '/admin/api/usuarios.php',
    'cfcs' => $baseUrl . '/admin/api/cfcs.php'
];

echo "<!DOCTYPE html>
<html lang='pt-BR'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste Direto das APIs - Produção</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background-color: #d4edda; border-color: #c3e6cb; }
        .error { background-color: #f8d7da; border-color: #f5c6cb; }
        .info { background-color: #d1ecf1; border-color: #bee5eb; }
        .url { font-family: monospace; background-color: #f8f9fa; padding: 5px; border-radius: 3px; }
        .response { font-family: monospace; background-color: #f8f9fa; padding: 10px; border-radius: 3px; margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🧪 Teste Direto das APIs - Produção</h1>
    
    <div class='test-section info'>
        <h3>📍 URLs das APIs para Teste</h3>";

foreach ($apis as $name => $url) {
    echo "<p><strong>" . ucfirst($name) . ":</strong> <span class='url'>$url</span></p>";
}

echo "</div>

    <div class='test-section'>
        <h3>🧪 Resultados dos Testes</h3>
        <div id='test-results'>";

// Testar cada API
foreach ($apis as $name => $url) {
    echo "<h4>🔍 Testando API: " . ucfirst($name) . "</h4>";
    echo "<p><strong>URL:</strong> <span class='url'>$url</span></p>";
    
    // Fazer requisição HTTP
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "<div class='test-section error'>";
        echo "<p><strong>❌ Erro de CURL:</strong> $error</p>";
        echo "</div>";
    } else {
        if ($httpCode === 200) {
            echo "<div class='test-section success'>";
            echo "<p><strong>✅ Sucesso:</strong> HTTP $httpCode</p>";
            
            // Tentar decodificar JSON
            $data = json_decode($response, true);
            if ($data !== null) {
                echo "<p><strong>📊 Resposta JSON válida:</strong></p>";
                echo "<div class='response'>";
                echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "</pre>";
                echo "</div>";
            } else {
                echo "<p><strong>⚠️ Resposta não é JSON válido:</strong></p>";
                echo "<div class='response'>";
                $preview = substr($response, 0, 500);
                echo htmlspecialchars($preview);
                if (strlen($response) > 500) echo "...";
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='test-section error'>";
            echo "<p><strong>❌ Erro HTTP:</strong> $httpCode</p>";
            echo "<div class='response'>";
            $preview = substr($response, 0, 500);
            echo htmlspecialchars($preview);
            if (strlen($response) > 500) echo "...";
            echo "</div>";
            echo "</div>";
        }
    }
    
    echo "<hr>";
}

echo "</div>

    <div class='test-section info'>
        <h3>🔧 Informações Adicionais</h3>
        <p><strong>Data/Hora:</strong> " . date('d/m/Y H:i:s') . "</p>
        <p><strong>Servidor:</strong> " . $_SERVER['SERVER_SOFTWARE'] ?? 'N/A' . "</p>
        <p><strong>PHP Version:</strong> " . PHP_VERSION . "</p>
    </div>

    <script>
        console.log('🧪 Teste das APIs em produção concluído!');
        console.log('📊 Verifique os resultados acima para identificar problemas.');
    </script>
</body>
</html>";
?>
