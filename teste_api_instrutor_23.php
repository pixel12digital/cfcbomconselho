<?php
// Teste para verificar dados do instrutor ID 23
require_once 'includes/config.php';
require_once 'includes/database.php';

echo "<h2>Teste API Instrutor ID 23</h2>";

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Buscar dados do instrutor ID 23
    $stmt = $pdo->prepare("SELECT * FROM instrutores WHERE id = ?");
    $stmt->execute([23]);
    $instrutor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h3>Dados do Instrutor ID 23:</h3>";
    echo "<pre>";
    print_r($instrutor);
    echo "</pre>";
    
    // Verificar se o usuário existe
    if ($instrutor && $instrutor['usuario_id']) {
        $stmt = $pdo->prepare("SELECT id, nome, email FROM usuarios WHERE id = ?");
        $stmt->execute([$instrutor['usuario_id']]);
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Dados do Usuário ID " . $instrutor['usuario_id'] . ":</h3>";
        echo "<pre>";
        print_r($usuario);
        echo "</pre>";
    }
    
    // Verificar se o CFC existe
    if ($instrutor && $instrutor['cfc_id']) {
        $stmt = $pdo->prepare("SELECT id, nome FROM cfcs WHERE id = ?");
        $stmt->execute([$instrutor['cfc_id']]);
        $cfc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>Dados do CFC ID " . $instrutor['cfc_id'] . ":</h3>";
        echo "<pre>";
        print_r($cfc);
        echo "</pre>";
    }
    
    // Testar a API diretamente
    echo "<h3>Teste da API:</h3>";
    $apiUrl = "admin/api/instrutores.php?id=23";
    echo "<p>URL: $apiUrl</p>";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p>HTTP Code: $httpCode</p>";
    echo "<p>Resposta da API:</p>";
    echo "<pre>";
    print_r(json_decode($response, true));
    echo "</pre>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Erro: " . $e->getMessage() . "</p>";
}
?>
