<?php
// Teste cURL em PHP para verificar se a API está funcionando
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🧪 Teste cURL em PHP - API de Alunos</h1>";

// Verificar se cURL está disponível
if (!function_exists('curl_init')) {
    echo "<h2>❌ cURL não está disponível</h2>";
    echo "<p>Instale a extensão cURL do PHP</p>";
    exit;
}

echo "<h2>1. Testando GET (listar alunos)...</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/alunos.php');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($error) {
    echo "<p><strong>Erro cURL:</strong> {$error}</p>";
} else {
    echo "<p><strong>Resposta:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Tentar decodificar JSON
    $jsonData = json_decode($response, true);
    if ($jsonData) {
        echo "<p><strong>JSON decodificado:</strong></p>";
        echo "<pre>" . print_r($jsonData, true) . "</pre>";
        
        if (isset($jsonData['success']) && $jsonData['success'] && isset($jsonData['alunos'])) {
            echo "<p style='color: green;'>✅ GET funcionando! Alunos encontrados: " . count($jsonData['alunos']) . "</p>";
        } else {
            echo "<p style='color: red;'>❌ GET retornou erro: " . ($jsonData['error'] ?? 'Erro desconhecido') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Não foi possível decodificar JSON</p>";
    }
}

echo "<hr>";

echo "<h2>2. Testando DELETE (excluir aluno)...</h2>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost:8080/cfc-bom-conselho/admin/api/alunos.php');
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['id' => 106]));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> {$httpCode}</p>";

if ($error) {
    echo "<p><strong>Erro cURL:</strong> {$error}</p>";
} else {
    echo "<p><strong>Resposta:</strong></p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Tentar decodificar JSON
    $jsonData = json_decode($response, true);
    if ($jsonData) {
        echo "<p><strong>JSON decodificado:</strong></p>";
        echo "<pre>" . print_r($jsonData, true) . "</pre>";
        
        if (isset($jsonData['success']) && $jsonData['success']) {
            echo "<p style='color: green;'>✅ DELETE funcionando! Aluno excluído com sucesso!</p>";
        } else {
            echo "<p style='color: red;'>❌ DELETE retornou erro: " . ($jsonData['error'] ?? 'Erro desconhecido') . "</p>";
            
            // Mostrar debug se disponível
            if (isset($jsonData['debug'])) {
                echo "<p><strong>Debug:</strong></p>";
                echo "<pre>" . print_r($jsonData['debug'], true) . "</pre>";
            }
        }
    } else {
        echo "<p style='color: red;'>❌ Não foi possível decodificar JSON</p>";
    }
}

echo "<hr>";
echo "<p><strong>Teste concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
