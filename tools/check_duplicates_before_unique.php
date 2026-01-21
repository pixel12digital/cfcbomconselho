<?php
/**
 * Script para verificar duplicados antes de aplicar constraint UNIQUE
 * 
 * Este script verifica se existem duplicados na combinação (gateway_provider, gateway_charge_id)
 * antes de aplicar a constraint UNIQUE. Se houver duplicados, exibe um relatório detalhado.
 */

require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/Config/Env.php';

App\Config\Env::load();
$db = App\Config\Database::getInstance()->getConnection();

echo "==========================================\n";
echo "VERIFICAÇÃO DE DUPLICADOS - GATEWAY\n";
echo "==========================================\n\n";

// Query para encontrar duplicados
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

if (empty($duplicates)) {
    echo "✓ Nenhum duplicado encontrado!\n";
    echo "\nA constraint UNIQUE pode ser aplicada com segurança.\n";
    echo "\nSQL para aplicar:\n";
    echo "ALTER TABLE enrollments\n";
    echo "  ADD UNIQUE KEY uniq_gateway_provider_charge (gateway_provider, gateway_charge_id);\n";
    exit(0);
}

echo "⚠ ATENÇÃO: Foram encontrados " . count($duplicates) . " grupo(s) de duplicados!\n\n";

// Para cada grupo de duplicados, buscar os registros completos
foreach ($duplicates as $dup) {
    echo "----------------------------------------\n";
    echo "Duplicado encontrado:\n";
    echo "  Provider: {$dup['gateway_provider']}\n";
    echo "  Charge ID: {$dup['gateway_charge_id']}\n";
    echo "  Quantidade: {$dup['qty']}\n\n";
    
    // Buscar todos os registros com essa combinação
    $stmt = $db->prepare("
        SELECT 
            id,
            student_id,
            billing_status,
            gateway_last_status,
            gateway_last_event_at,
            created_at,
            updated_at
        FROM enrollments
        WHERE gateway_provider = ? 
            AND gateway_charge_id = ?
        ORDER BY 
            CASE billing_status 
                WHEN 'generated' THEN 1 
                WHEN 'ready' THEN 2 
                WHEN 'draft' THEN 3 
                ELSE 4 
            END,
            gateway_last_event_at DESC,
            id DESC
    ");
    $stmt->execute([$dup['gateway_provider'], $dup['gateway_charge_id']]);
    $enrollments = $stmt->fetchAll();
    
    echo "  Registros afetados:\n";
    foreach ($enrollments as $idx => $enr) {
        $marker = ($idx === 0) ? "  → MANTER" : "  ✗ LIMPAR";
        echo "  {$marker} - ID: {$enr['id']}, Student ID: {$enr['student_id']}, ";
        echo "Billing Status: {$enr['billing_status']}, ";
        echo "Gateway Status: {$enr['gateway_last_status']}, ";
        echo "Event At: {$enr['gateway_last_event_at']}\n";
    }
    echo "\n";
}

echo "==========================================\n";
echo "PLANO DE CORREÇÃO\n";
echo "==========================================\n\n";
echo "Para cada grupo de duplicados, mantenha o registro mais recente com billing_status='generated'\n";
echo "e limpe (NULL) o gateway_charge_id dos demais.\n\n";
echo "Exemplo de SQL para correção (ajuste os IDs conforme necessário):\n\n";

foreach ($duplicates as $dup) {
    $stmt = $db->prepare("
        SELECT id
        FROM enrollments
        WHERE gateway_provider = ? 
            AND gateway_charge_id = ?
        ORDER BY 
            CASE billing_status 
                WHEN 'generated' THEN 1 
                WHEN 'ready' THEN 2 
                WHEN 'draft' THEN 3 
                ELSE 4 
            END,
            gateway_last_event_at DESC,
            id DESC
    ");
    $stmt->execute([$dup['gateway_provider'], $dup['gateway_charge_id']]);
    $enrollments = $stmt->fetchAll();
    
    if (count($enrollments) > 1) {
        $keepId = $enrollments[0]['id'];
        $cleanIds = [];
        for ($i = 1; $i < count($enrollments); $i++) {
            $cleanIds[] = $enrollments[$i]['id'];
        }
        
        echo "-- Provider: {$dup['gateway_provider']}, Charge ID: {$dup['gateway_charge_id']}\n";
        echo "-- Manter: enrollment_id = {$keepId}\n";
        echo "-- Limpar: enrollment_id IN (" . implode(', ', $cleanIds) . ")\n";
        echo "UPDATE enrollments\n";
        echo "  SET gateway_charge_id = NULL,\n";
        echo "      gateway_provider = NULL,\n";
        echo "      gateway_last_status = NULL\n";
        echo "  WHERE id IN (" . implode(', ', $cleanIds) . ");\n\n";
    }
}

echo "\n⚠ NÃO APLIQUE A CONSTRAINT UNIQUE ENQUANTO HOUVER DUPLICADOS!\n";
echo "Execute as correções acima primeiro e rode este script novamente.\n";
