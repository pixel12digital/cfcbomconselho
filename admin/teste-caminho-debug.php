<?php
/**
 * Arquivo de debug para verificar caminhos
 */

echo "<h2>Debug de Caminhos</h2>";

echo "<h3>Informações do Sistema:</h3>";
echo "Current working directory: " . getcwd() . "<br>";
echo "__FILE__: " . __FILE__ . "<br>";
echo "__DIR__: " . __DIR__ . "<br>";
echo "dirname(__DIR__): " . dirname(__DIR__) . "<br>";
echo "dirname(dirname(__DIR__)): " . dirname(dirname(__DIR__)) . "<br>";

echo "<h3>Testando Caminhos:</h3>";

$paths_to_test = [
    'includes/config.php',
    '../includes/config.php',
    '../../includes/config.php',
    dirname(__DIR__) . '/includes/config.php',
    dirname(dirname(__DIR__)) . '/includes/config.php',
    __DIR__ . '/../../includes/config.php',
    realpath(__DIR__ . '/../../includes/config.php')
];

foreach ($paths_to_test as $path) {
    echo "Testando: <code>$path</code> - ";
    if (file_exists($path)) {
        echo "<span style='color: green;'>✅ EXISTE</span><br>";
    } else {
        echo "<span style='color: red;'>❌ NÃO EXISTE</span><br>";
    }
}

echo "<h3>Caminho Recomendado:</h3>";
$recommended_path = __DIR__ . '/../../includes/config.php';
echo "Caminho recomendado: <code>$recommended_path</code><br>";
echo "Arquivo existe: " . (file_exists($recommended_path) ? '✅ SIM' : '❌ NÃO') . "<br>";

if (file_exists($recommended_path)) {
    echo "<p style='color: green; font-weight: bold;'>✅ SUCESSO! Use este caminho: __DIR__ . '/../../includes/config.php'</p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>❌ PROBLEMA: Nenhum caminho funcionou!</p>";
}
?>
