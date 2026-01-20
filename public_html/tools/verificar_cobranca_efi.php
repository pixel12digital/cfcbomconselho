<?php
/**
 * Script para verificar status da cobrança na API EFI (não no banco local)
 * 
 * Uso: php public_html/tools/verificar_cobranca_efi.php [charge_id]
 */

require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/Services/EfiPaymentService.php';

App\Config\Env::load();

echo "==========================================\n";
echo "VERIFICAÇÃO: Cobrança na API EFI\n";
echo "==========================================\n\n";

// Obter charge_id do argumento ou buscar da matrícula
$chargeId = isset($argv[1]) ? $argv[1] : null;

if (!$chargeId) {
    // Buscar charge_id da matrícula ID 1
    $db = App\Config\Database::getInstance()->getConnection();
    $stmt = $db->prepare("SELECT gateway_charge_id, payment_method, installments FROM enrollments WHERE id = 1");
    $stmt->execute();
    $enrollment = $stmt->fetch();
    
    if ($enrollment && !empty($enrollment['gateway_charge_id'])) {
        $chargeId = $enrollment['gateway_charge_id'];
        echo "Charge ID encontrado no banco: {$chargeId}\n";
        echo "Payment Method: " . ($enrollment['payment_method'] ?? 'NULL') . "\n";
        echo "Installments: " . ($enrollment['installments'] ?? 'NULL') . "\n\n";
    } else {
        echo "❌ ERRO: Charge ID não encontrado!\n";
        echo "Uso: php verificar_cobranca_efi.php [charge_id]\n";
        exit(1);
    }
}

echo "Consultando cobrança na API EFI...\n";
echo "Charge ID: {$chargeId}\n\n";

// Criar serviço EFI
$efiService = new App\Services\EfiPaymentService();

// Usar reflection para acessar método privado
$reflection = new ReflectionClass($efiService);
$getChargeStatus = $reflection->getMethod('getChargeStatus');
$getChargeStatus->setAccessible(true);

// Consultar cobrança (não é PIX, é Cobrança)
$chargeData = $getChargeStatus->invoke($efiService, $chargeId, false);

if (!$chargeData) {
    echo "❌ ERRO: Não foi possível consultar cobrança na API EFI.\n";
    echo "Possíveis causas:\n";
    echo "  - Charge ID inválido\n";
    echo "  - Problema de conexão com API EFI\n";
    echo "  - Credenciais inválidas\n";
    exit(1);
}

echo "✅ Cobrança encontrada na API EFI!\n\n";

echo "Dados da Cobrança:\n";
echo "==========================================\n";

// Status
$status = $chargeData['status'] ?? 'unknown';
echo "Status: {$status}\n";

// Charge ID
$chargeIdApi = $chargeData['charge_id'] ?? $chargeData['id'] ?? 'N/A';
echo "Charge ID: {$chargeIdApi}\n";

// Valor total
$total = $chargeData['total'] ?? ($chargeData['value'] ?? 'N/A');
echo "Valor Total: R$ " . (is_numeric($total) ? number_format($total / 100, 2, ',', '.') : $total) . "\n";

// Método de pagamento
if (isset($chargeData['payment'])) {
    echo "\nMétodo de Pagamento:\n";
    
    // Verificar se é boleto
    if (isset($chargeData['payment']['banking_billet'])) {
        echo "  Tipo: BOLETO (À Vista)\n";
        $billet = $chargeData['payment']['banking_billet'];
        echo "  Link: " . ($billet['link'] ?? 'N/A') . "\n";
        echo "  Vencimento: " . ($billet['expire_at'] ?? 'N/A') . "\n";
        echo "  Código de Barras: " . (isset($billet['barcode']) ? substr($billet['barcode'], 0, 50) . '...' : 'N/A') . "\n";
    }
    
    // Verificar se é cartão parcelado
    if (isset($chargeData['payment']['credit_card'])) {
        $card = $chargeData['payment']['credit_card'];
        echo "  Tipo: CARTÃO DE CRÉDITO (Parcelado)\n";
        echo "  Parcelas: " . ($card['installments'] ?? 'N/A') . "x\n";
        
        if (isset($card['charges'])) {
            foreach ($card['charges'] as $idx => $charge) {
                echo "  Parcela " . ($idx + 1) . ": R$ " . number_format($charge['total'] / 100, 2, ',', '.') . 
                     " - Vencimento: " . ($charge['due_at'] ?? 'N/A') . "\n";
            }
        }
    }
    
    // Verificar se é PIX
    if (isset($chargeData['payment']['pix'])) {
        echo "  Tipo: PIX\n";
        $pix = $chargeData['payment']['pix'];
        echo "  QR Code: " . (isset($pix['qr_code']) ? substr($pix['qr_code'], 0, 50) . '...' : 'N/A') . "\n";
    }
}

// Items
if (isset($chargeData['items'])) {
    echo "\nItems:\n";
    foreach ($chargeData['items'] as $item) {
        echo "  - " . ($item['name'] ?? 'N/A') . ": R$ " . 
             (is_numeric($item['value'] ?? 0) ? number_format($item['value'] / 100, 2, ',', '.') : 'N/A') . "\n";
    }
}

// Customer
if (isset($chargeData['customer'])) {
    echo "\nCliente:\n";
    $customer = $chargeData['customer'];
    echo "  Nome: " . ($customer['name'] ?? 'N/A') . "\n";
    echo "  CPF: " . ($customer['cpf'] ?? 'N/A') . "\n";
    echo "  Email: " . ($customer['email'] ?? 'N/A') . "\n";
}

// Datas
echo "\nDatas:\n";
echo "  Criado em: " . ($chargeData['created_at'] ?? 'N/A') . "\n";
echo "  Atualizado em: " . ($chargeData['updated_at'] ?? 'N/A') . "\n";

echo "\n==========================================\n";
echo "RESPOSTA COMPLETA DA API EFI (JSON):\n";
echo "==========================================\n";
echo json_encode($chargeData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
