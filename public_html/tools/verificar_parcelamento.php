<?php
/**
 * Script para verificar dados de parcelamento de matrículas
 */

require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Config/Env.php';

App\Config\Env::load();
$db = App\Config\Database::getInstance()->getConnection();

echo "==========================================\n";
echo "VERIFICAÇÃO DE PARCELAMENTO - MATRÍCULAS\n";
echo "==========================================\n\n";

// Buscar matrículas com cobrança gerada
$stmt = $db->query("
    SELECT 
        id,
        payment_method,
        installments,
        final_price,
        entry_amount,
        outstanding_amount,
        gateway_charge_id,
        gateway_last_status,
        down_payment_amount,
        first_due_date
    FROM enrollments 
    WHERE gateway_charge_id IS NOT NULL 
    AND gateway_charge_id != ''
    ORDER BY id DESC
    LIMIT 10
");

$enrollments = $stmt->fetchAll();

if (empty($enrollments)) {
    echo "Nenhuma matrícula com cobrança gerada encontrada.\n";
    exit(0);
}

echo "Matrículas com cobrança gerada:\n\n";

foreach ($enrollments as $enr) {
    echo "ID: {$enr['id']}\n";
    echo "  Payment Method: " . ($enr['payment_method'] ?? 'NULL') . "\n";
    echo "  Installments: " . ($enr['installments'] ?? 'NULL') . "\n";
    echo "  Final Price: R$ " . number_format($enr['final_price'], 2, ',', '.') . "\n";
    echo "  Entry Amount: R$ " . number_format($enr['entry_amount'] ?? 0, 2, ',', '.') . "\n";
    echo "  Outstanding Amount: R$ " . number_format($enr['outstanding_amount'] ?? 0, 2, ',', '.') . "\n";
    echo "  Down Payment Amount: " . ($enr['down_payment_amount'] ? 'R$ ' . number_format($enr['down_payment_amount'], 2, ',', '.') : 'NULL') . "\n";
    echo "  First Due Date: " . ($enr['first_due_date'] ?? 'NULL') . "\n";
    echo "  Charge ID: " . ($enr['gateway_charge_id'] ?? 'NULL') . "\n";
    echo "  Gateway Status: " . ($enr['gateway_last_status'] ?? 'NULL') . "\n";
    
    // Verificar lógica de parcelamento
    $paymentMethod = $enr['payment_method'] ?? 'pix';
    $installments = intval($enr['installments'] ?? 1);
    $isPix = ($paymentMethod === 'pix' && $installments === 1);
    $isCreditCard = ($paymentMethod === 'cartao' || $paymentMethod === 'credit_card') && $installments > 1;
    $isBoleto = !$isPix && !$isCreditCard;
    
    echo "\n  ⚡ Lógica de determinação:\n";
    echo "    - Is PIX: " . ($isPix ? 'SIM' : 'NÃO') . "\n";
    echo "    - Is Credit Card (parcelado): " . ($isCreditCard ? 'SIM' : 'NÃO') . "\n";
    echo "    - Is Boleto: " . ($isBoleto ? 'SIM' : 'NÃO') . "\n";
    
    if ($installments > 1 && !$isCreditCard) {
        echo "\n  ⚠️ ATENÇÃO: Installments = {$installments} mas NÃO está sendo tratado como cartão!\n";
        echo "    Isso significa que será gerado como BOLETO em vez de CARTÃO PARCELADO.\n";
    }
    
    echo "\n" . str_repeat('-', 60) . "\n\n";
}
