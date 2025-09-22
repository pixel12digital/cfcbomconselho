<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "Verificando estrutura da tabela turma_diario...\n";
$columns = $db->fetchAll('DESCRIBE turma_diario');

echo "Colunas da tabela turma_diario:\n";
foreach($columns as $column) {
    echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
}
?>
