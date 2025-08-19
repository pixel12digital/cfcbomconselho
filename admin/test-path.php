<?php
echo "<h1>Teste de Caminhos - Admin</h1>";

echo "<h2>Diretório Atual:</h2>";
echo "<p>getcwd(): " . getcwd() . "</p>";

echo "<h2>dirname(__DIR__):</h2>";
echo "<p>dirname(__DIR__): " . dirname(__DIR__) . "</p>";

echo "<h2>Caminho Base:</h2>";
$base_path = dirname(__DIR__);
echo "<p>base_path: " . $base_path . "</p>";

echo "<h2>Verificação de Arquivos:</h2>";
$config_file = $base_path . '/includes/config.php';
$database_file = $base_path . '/includes/database.php';
$auth_file = $base_path . '/includes/auth.php';

echo "<p>config.php: " . $config_file . " - " . (file_exists($config_file) ? "✅ Existe" : "❌ Não existe") . "</p>";
echo "<p>database.php: " . $database_file . " - " . (file_exists($database_file) ? "✅ Existe" : "❌ Não existe") . "</p>";
echo "<p>auth.php: " . $auth_file . " - " . (file_exists($auth_file) ? "✅ Existe" : "❌ Não existe") . "</p>";

echo "<h2>Listagem do Diretório:</h2>";
echo "<p>Conteúdo de " . $base_path . ":</p>";
$files = scandir($base_path);
echo "<ul>";
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo "<li>" . $file . "</li>";
    }
}
echo "</ul>";

echo "<h2>Listagem do Diretório includes:</h2>";
$includes_dir = $base_path . '/includes';
if (is_dir($includes_dir)) {
    $includes_files = scandir($includes_dir);
    echo "<ul>";
    foreach ($includes_files as $file) {
        if ($file != '.' && $file != '..') {
            echo "<li>" . $file . "</li>";
        }
    }
    echo "</ul>";
} else {
    echo "<p>❌ Diretório includes não existe em: " . $includes_dir . "</p>";
}
?>
