<?php
/**
 * Script para verificar a URL atual do sistema
 * Use para identificar qual URL usar no diagnóstico
 */

session_start();

echo "<h1>Informações da URL do Sistema</h1>";
echo "<table border='1' cellpadding='10' style='border-collapse:collapse;'>";
echo "<tr><th>Item</th><th>Valor</th></tr>";

// URL atual
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'N/A';
$path = $_SERVER['REQUEST_URI'] ?? 'N/A';
$currentUrl = $protocol . '://' . $host . dirname($path);

echo "<tr><td><strong>URL Atual (Protocolo + Host + Path)</strong></td><td>{$currentUrl}</td></tr>";
echo "<tr><td><strong>Host (HTTP_HOST)</strong></td><td>{$host}</td></tr>";
echo "<tr><td><strong>Protocolo</strong></td><td>{$protocol}</td></tr>";
echo "<tr><td><strong>Path (REQUEST_URI)</strong></td><td>{$path}</td></tr>";

// APP_URL definido
if (defined('APP_URL')) {
    echo "<tr><td><strong>APP_URL (config.php)</strong></td><td>" . APP_URL . "</td></tr>";
} else {
    echo "<tr><td><strong>APP_URL (config.php)</strong></td><td>Não definido ainda</td></tr>";
}

echo "</table>";

echo "<h2>URL para o Script de Diagnóstico:</h2>";
echo "<p><code>{$protocol}://{$host}" . dirname($path) . "/diagnostico-aluno-167-turma-teorica.php?turma_id=16</code></p>";
echo "<p>(Substitua <code>16</code> pelo ID real da turma)</p>";

echo "<h2>Ou, se preferir usar apenas o host:</h2>";
echo "<p><code>{$protocol}://{$host}/admin/tools/diagnostico-aluno-167-turma-teorica.php?turma_id=16</code></p>";
?>

