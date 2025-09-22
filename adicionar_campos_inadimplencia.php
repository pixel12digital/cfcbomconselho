<?php
require_once 'includes/config.php';
require_once 'includes/database.php';

$db = Database::getInstance();

echo "Adicionando campos de inadimplência...\n";

try {
    $db->query('ALTER TABLE alunos ADD COLUMN inadimplente TINYINT(1) DEFAULT 0 AFTER status_financeiro');
    echo "✅ Campo inadimplente adicionado\n";
} catch (Exception $e) {
    echo "❌ Erro inadimplente: " . $e->getMessage() . "\n";
}

try {
    $db->query('ALTER TABLE alunos ADD COLUMN inadimplente_desde DATE NULL AFTER inadimplente');
    echo "✅ Campo inadimplente_desde adicionado\n";
} catch (Exception $e) {
    echo "❌ Erro inadimplente_desde: " . $e->getMessage() . "\n";
}

echo "Campos adicionados com sucesso!\n";
