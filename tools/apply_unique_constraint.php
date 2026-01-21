<?php
/**
 * Script para aplicar constraint UNIQUE após verificar que não há duplicados
 * 
 * IMPORTANTE: Execute tools/check_duplicates_before_unique.php primeiro
 * e corrija todos os duplicados antes de executar este script.
 */

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Config/Env.php';

App\Config\Env::load();
$db = App\Config\Database::getInstance()->getConnection();

echo "==========================================\n";
echo "APLICAR CONSTRAINT UNIQUE - GATEWAY\n";
echo "==========================================\n\n";

// Primeiro, verificar se há duplicados
$sql = "
    SELECT 
        gateway_provider, 
        gateway_charge_id, 
        COUNT(*) AS qty
    FROM enrollments
    WHERE gateway_charge_id IS NOT NULL 
        AND gateway_charge_id <> ''
    GROUP BY gateway_provider, gateway_charge_id
    HAVING COUNT(*) > 1
";

$stmt = $db->query($sql);
$duplicates = $stmt->fetchAll();

if (!empty($duplicates)) {
    echo "❌ ERRO: Ainda existem " . count($duplicates) . " grupo(s) de duplicados!\n";
    echo "\nExecute tools/check_duplicates_before_unique.php e corrija os duplicados primeiro.\n";
    exit(1);
}

echo "✓ Nenhum duplicado encontrado. Prosseguindo...\n\n";

// Verificar se a constraint já existe
$sql = "
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = 'enrollments'
        AND CONSTRAINT_NAME = 'uniq_gateway_provider_charge'
        AND CONSTRAINT_TYPE = 'UNIQUE'
";

$stmt = $db->query($sql);
$exists = $stmt->fetch();

if ($exists) {
    echo "⚠ A constraint UNIQUE já existe. Nada a fazer.\n";
    exit(0);
}

// Aplicar constraint UNIQUE
echo "Aplicando constraint UNIQUE...\n";

try {
    $db->exec("
        ALTER TABLE enrollments
        ADD UNIQUE KEY uniq_gateway_provider_charge (gateway_provider, gateway_charge_id)
    ");
    
    echo "✓ Constraint UNIQUE aplicada com sucesso!\n";
    echo "\nA constraint permite múltiplos NULLs (MySQL permite), então registros sem gateway_charge_id não serão afetados.\n";
    
} catch (\PDOException $e) {
    echo "❌ ERRO ao aplicar constraint: " . $e->getMessage() . "\n";
    exit(1);
}
