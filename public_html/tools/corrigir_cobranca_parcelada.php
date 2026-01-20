<?php
/**
 * Script para corrigir cobrança gerada incorretamente (boleto à vista quando deveria ser cartão parcelado)
 * 
 * Este script:
 * 1. Marca a cobrança atual como 'canceled' no banco (para permitir gerar nova)
 * 2. Limpa os dados da cobrança antiga
 * 3. Permite gerar nova cobrança corretamente (cartão parcelado)
 * 
 * Uso: php public_html/tools/corrigir_cobranca_parcelada.php [enrollment_id]
 */

require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Config/Env.php';

App\Config\Env::load();
$db = App\Config\Database::getInstance()->getConnection();

echo "==========================================\n";
echo "CORREÇÃO: Cobrança Parcelada\n";
echo "==========================================\n\n";

// Obter enrollment_id do argumento ou usar ID 1 como padrão
$enrollmentId = isset($argv[1]) ? intval($argv[1]) : 1;

echo "Matrícula ID: {$enrollmentId}\n\n";

// Buscar matrícula
$stmt = $db->prepare("
    SELECT 
        id,
        payment_method,
        installments,
        final_price,
        entry_amount,
        outstanding_amount,
        gateway_charge_id,
        gateway_last_status,
        billing_status,
        gateway_payment_url
    FROM enrollments 
    WHERE id = ?
");

$stmt->execute([$enrollmentId]);
$enrollment = $stmt->fetch();

if (!$enrollment) {
    echo "❌ ERRO: Matrícula não encontrada!\n";
    exit(1);
}

echo "Dados Atuais:\n";
echo "  Payment Method: " . ($enrollment['payment_method'] ?? 'NULL') . "\n";
echo "  Installments: " . ($enrollment['installments'] ?? 'NULL') . "\n";
echo "  Outstanding Amount: R$ " . number_format($enrollment['outstanding_amount'] ?? 0, 2, ',', '.') . "\n";
echo "  Charge ID: " . ($enrollment['gateway_charge_id'] ?? 'NULL') . "\n";
echo "  Gateway Status: " . ($enrollment['gateway_last_status'] ?? 'NULL') . "\n";
echo "  Billing Status: " . ($enrollment['billing_status'] ?? 'NULL') . "\n\n";

// Verificar se está incorreto (tem installments > 1 mas foi gerado como boleto)
$installments = intval($enrollment['installments'] ?? 1);
$hasCharge = !empty($enrollment['gateway_charge_id']);

if ($installments > 1 && $hasCharge) {
    echo "⚠️  PROBLEMA DETECTADO:\n";
    echo "  - Installments = {$installments} (parcelado)\n";
    echo "  - Mas cobrança já foi gerada (provavelmente como boleto à vista)\n\n";
    
    echo "Correção:\n";
    echo "  1. Marcar cobrança atual como 'canceled' no banco\n";
    echo "  2. Limpar dados da cobrança (permitir gerar nova)\n";
    echo "  3. Com a correção aplicada no código, ao gerar nova será cartão parcelado\n\n";
    
    if (!isset($argv[2]) || $argv[2] !== '--confirm') {
        echo "⚠️  ATENÇÃO: Isso marcará a cobrança atual como cancelada no banco.\n";
        echo "   A cobrança na EFI continuará existindo (status 'waiting'), mas o sistema permitirá gerar nova.\n\n";
        echo "Para executar a correção, execute:\n";
        echo "  php public_html/tools/corrigir_cobranca_parcelada.php {$enrollmentId} --confirm\n";
        exit(0);
    }
    
    // Executar correção
    echo "Executando correção...\n";
    
    $db->beginTransaction();
    
    try {
        // Atualizar matrícula: marcar como cancelada para permitir gerar nova
        $updateStmt = $db->prepare("
            UPDATE enrollments 
            SET 
                gateway_last_status = 'canceled',
                billing_status = 'error',
                gateway_payment_url = NULL
            WHERE id = ?
        ");
        
        $updateStmt->execute([$enrollmentId]);
        
        // Manter gateway_charge_id para referência (não limpar)
        // O sistema vai verificar que status é 'canceled' e permitir gerar nova
        
        $db->commit();
        
        echo "✅ Correção aplicada com sucesso!\n\n";
        echo "Próximos passos:\n";
        echo "  1. Acesse a página da matrícula: /matriculas/{$enrollmentId}\n";
        echo "  2. Clique em 'Gerar Cobrança Efí'\n";
        echo "  3. A nova cobrança será gerada como CARTÃO PARCELADO (3x)\n\n";
        echo "NOTA: A cobrança antiga na EFI (ID: {$enrollment['gateway_charge_id']}) continuará existindo.\n";
        echo "      Se necessário, cancele-a manualmente no painel da EFI.\n";
        
    } catch (\Exception $e) {
        $db->rollBack();
        echo "❌ ERRO ao aplicar correção:\n";
        echo "   " . $e->getMessage() . "\n";
        exit(1);
    }
    
} else {
    echo "✅ Status OK: Não precisa de correção.\n";
    
    if ($installments <= 1) {
        echo "  - Installments = {$installments} (não parcelado)\n";
    }
    
    if (!$hasCharge) {
        echo "  - Nenhuma cobrança gerada ainda\n";
    }
    
    echo "\nVocê pode gerar a cobrança normalmente.\n";
}
