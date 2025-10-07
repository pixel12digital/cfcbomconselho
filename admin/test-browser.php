<?php
/**
 * Teste da API via navegador
 */

session_start();

// Simular login de admin
$_SESSION['user_id'] = 1;
$_SESSION['user_type'] = 'admin';

echo "<h2>Teste da API via Navegador</h2>";

echo "<h3>1. Status da Sessão:</h3>";
echo "Session ID: " . session_id() . "<br>";
echo "User ID: " . ($_SESSION['user_id'] ?? 'não definido') . "<br>";
echo "User Type: " . ($_SESSION['user_type'] ?? 'não definido') . "<br>";

echo "<h3>2. Teste da API com aluno_id=1:</h3>";

// Configurar para chamada GET
$_GET['aluno_id'] = '1';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capturar saída
ob_start();
include 'api/exames.php';
$output = ob_get_clean();

echo "<strong>Resposta da API:</strong><br>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

// Verificar se é JSON válido
$json = json_decode($output, true);
if ($json !== null) {
    echo "✅ JSON válido<br>";
    if (isset($json['aluno'])) {
        echo "✅ Retornou dados do aluno: " . $json['aluno']['nome'] . "<br>";
    }
    if (isset($json['exames'])) {
        echo "✅ Retornou " . count($json['exames']) . " exames<br>";
    }
    if (isset($json['exames_ok'])) {
        echo "✅ Exames OK: " . ($json['exames_ok'] ? 'Sim' : 'Não') . "<br>";
    }
} else {
    echo "❌ JSON inválido: " . json_last_error_msg() . "<br>";
}

echo "<hr>";

echo "<h3>3. Teste JavaScript Simulado:</h3>";
echo '<script>
fetch("api/exames.php?aluno_id=1")
    .then(response => {
        console.log("Status:", response.status);
        return response.text();
    })
    .then(text => {
        console.log("Resposta bruta:", text);
        try {
            const json = JSON.parse(text);
            console.log("JSON válido:", json);
        } catch (e) {
            console.error("Erro ao parsear JSON:", e);
        }
    })
    .catch(error => {
        console.error("Erro na requisição:", error);
    });
</script>';

echo "<p>Verifique o console do navegador (F12) para ver os resultados do JavaScript.</p>";
?>
