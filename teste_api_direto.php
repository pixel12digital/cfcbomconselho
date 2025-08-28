<?php
// Teste direto da API para verificar se está funcionando
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Teste Direto da API de Alunos</h1>";

// Simular uma requisição GET para listar alunos
echo "<h2>1. Testando GET (listar alunos)...</h2>";

// Capturar saída da API
ob_start();

// Simular variáveis de servidor
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['HTTP_ACCEPT'] = 'application/json';

// Incluir a API
include 'admin/api/alunos.php';

$apiOutput = ob_get_clean();

echo "<h3>Saída da API:</h3>";
echo "<pre>" . htmlspecialchars($apiOutput) . "</pre>";

// Verificar se há erros PHP
if (error_get_last()) {
    echo "<h3>❌ Erros PHP detectados:</h3>";
    echo "<pre>" . print_r(error_get_last(), true) . "</pre>";
}

// Testar método DELETE
echo "<h2>2. Testando DELETE (excluir aluno)...</h2>";

// Simular variáveis de servidor para DELETE
$_SERVER['REQUEST_METHOD'] = 'DELETE';
$_SERVER['HTTP_ACCEPT'] = 'application/json';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simular dados de entrada usando stream wrapper personalizado
class MockPhpInput {
    private $data;
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function __toString() {
        return $this->data;
    }
}

// Substituir php://input temporariamente
$originalPhpInput = null;
if (function_exists('stream_wrapper_register')) {
    // Tentar registrar um wrapper personalizado
    try {
        stream_wrapper_register('mock', 'MockPhpInput');
        $mockData = json_encode(['id' => 106]);
        $GLOBALS['mock_input_data'] = $mockData;
        
        // Capturar saída da API para DELETE
        ob_start();
        include 'admin/api/alunos.php';
        $apiOutputDelete = ob_get_clean();
        
        echo "<h3>Saída da API DELETE:</h3>";
        echo "<pre>" . htmlspecialchars($apiOutputDelete) . "</pre>";
        
    } catch (Exception $e) {
        echo "<h3>❌ Erro ao simular DELETE:</h3>";
        echo "<p>" . $e->getMessage() . "</p>";
    }
} else {
    echo "<h3>⚠️ Não foi possível simular php://input</h3>";
    echo "<p>Testando com cURL em vez disso...</p>";
    
    // Testar com cURL
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

    echo "<h3>Resposta cURL:</h3>";
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
        }
    }
}

// Verificar se há erros PHP no DELETE
if (error_get_last()) {
    echo "<h3>❌ Erros PHP detectados no DELETE:</h3>";
    echo "<pre>" . print_r(error_get_last(), true) . "</pre>";
}

echo "<hr>";
echo "<p><strong>Teste concluído em:</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
