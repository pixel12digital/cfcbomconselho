<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();
$cfcs = $db->fetchAll('SELECT id, nome FROM cfcs LIMIT 5');

echo "CFCs disponíveis:\n";
foreach($cfcs as $cfc) {
    echo "CFC ID: " . $cfc['id'] . " - " . $cfc['nome'] . "\n";
}

if (empty($cfcs)) {
    echo "Nenhum CFC encontrado. Criando um CFC de teste...\n";
    $cfcId = $db->insert('cfcs', [
        'nome' => 'CFC Teste',
        'cnpj' => '12345678000199',
        'endereco' => 'Endereço Teste',
        'telefone' => '11999999999',
        'email' => 'teste@cfc.com',
        'status' => 'ativo'
    ]);
    echo "CFC criado com ID: $cfcId\n";
}

