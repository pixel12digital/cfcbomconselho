<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();
$tables = $db->fetchAll('SHOW TABLES');

echo "Tabelas existentes no banco:\n";
foreach($tables as $table) {
    $tableName = $table[array_keys($table)[0]];
    echo "- $tableName\n";
}
?>
