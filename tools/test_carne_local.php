<?php
/**
 * Script de teste local para criação de Carnê
 * 
 * Uso: php tools/test_carne_local.php [enrollment_id]
 * 
 * Este script testa a criação de Carnê localmente para debug
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Services\EfiPaymentService;
use App\Models\Enrollment;
use App\Config\Env;

// Carregar variáveis de ambiente ANTES de qualquer uso do banco
Env::load();

// Obter enrollment_id da linha de comando ou usar padrão
$enrollmentId = $argv[1] ?? 2; // ID 2 por padrão (do erro mostrado)

echo "=== TESTE LOCAL: CRIAR CARNÊ ===\n\n";
echo "Enrollment ID: {$enrollmentId}\n\n";

try {
    // Carregar matrícula
    $enrollmentModel = new Enrollment();
    $enrollment = $enrollmentModel->findWithDetails($enrollmentId);
    
    if (!$enrollment) {
        die("ERRO: Matrícula #{$enrollmentId} não encontrada.\n");
    }
    
    echo "Matrícula encontrada:\n";
    echo "  - ID: {$enrollment['id']}\n";
    echo "  - Aluno: {$enrollment['student_name']}\n";
    echo "  - Serviço: {$enrollment['service_name']}\n";
    echo "  - Valor Final: R$ " . number_format($enrollment['final_price'], 2, ',', '.') . "\n";
    echo "  - Entrada: R$ " . number_format($enrollment['entry_amount'] ?? 0, 2, ',', '.') . "\n";
    echo "  - Saldo Devedor: R$ " . number_format($enrollment['outstanding_amount'] ?? $enrollment['final_price'], 2, ',', '.') . "\n";
    echo "  - Parcelas: " . ($enrollment['installments'] ?? 1) . "x\n";
    echo "  - Forma de Pagamento: {$enrollment['payment_method']}\n";
    echo "  - Data 1ª Parcela: " . ($enrollment['first_due_date'] ?? 'Não definida') . "\n";
    echo "  - Status Cobrança: {$enrollment['billing_status']}\n";
    echo "\n";
    
    // Validar se pode gerar cobrança
    $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?? 0);
    $installments = intval($enrollment['installments'] ?? 1);
    $paymentMethod = $enrollment['payment_method'] ?? '';
    
    if ($outstandingAmount <= 0) {
        die("ERRO: Saldo devedor deve ser maior que zero.\n");
    }
    
    if ($installments <= 1) {
        die("ERRO: Para criar Carnê, o número de parcelas deve ser maior que 1.\n");
    }
    
    if ($paymentMethod !== 'boleto') {
        echo "AVISO: Forma de pagamento é '{$paymentMethod}', mas será criado Carnê (boleto parcelado).\n\n";
    }
    
    // Criar serviço
    $efiService = new EfiPaymentService();
    
    echo "Iniciando criação do Carnê...\n";
    echo "  - Valor total: R$ " . number_format($outstandingAmount, 2, ',', '.') . "\n";
    echo "  - Parcelas: {$installments}x\n";
    echo "  - Valor por parcela: R$ " . number_format($outstandingAmount / $installments, 2, ',', '.') . "\n";
    echo "\n";
    
    echo "=== ATENÇÃO ===\n";
    echo "Verifique os logs em storage/logs/php_errors.log para:\n";
    echo "  - Payload FINAL enviado (antes de curl_exec)\n";
    echo "  - Status HTTP da resposta\n";
    echo "  - Response body completo\n";
    echo "\n";
    
    // Chamar método createCharge (que detecta Carnê e chama createCarnet)
    $result = $efiService->createCharge($enrollment);
    
    echo "=== RESULTADO ===\n";
        if ($result['ok']) {
            echo "✅ SUCESSO!\n";
            
            // Se for Carnê, mostrar informações específicas
            if (($result['type'] ?? '') === 'carne' || !empty($result['carnet_id'])) {
                echo "  - Tipo: Carnê (Boleto Parcelado)\n";
                echo "  - Carnet ID: " . ($result['carnet_id'] ?? 'N/A') . "\n";
                echo "  - Parcelas: " . ($result['installments'] ?? $installments) . "x\n";
                if (!empty($result['charge_ids']) && is_array($result['charge_ids'])) {
                    echo "  - Charge IDs (" . count($result['charge_ids']) . " parcelas):\n";
                    foreach ($result['charge_ids'] as $idx => $chargeId) {
                        echo "    * Parcela " . ($idx + 1) . ": {$chargeId}\n";
                    }
                }
                if (!empty($result['payment_urls']) && is_array($result['payment_urls'])) {
                    echo "  - Links de Pagamento (" . count($result['payment_urls']) . " links):\n";
                    foreach ($result['payment_urls'] as $idx => $url) {
                        echo "    * Parcela " . ($idx + 1) . ": {$url}\n";
                    }
                } elseif (!empty($result['payment_url'])) {
                    echo "  - Link Pagamento: " . $result['payment_url'] . "\n";
                }
            } else {
                echo "  - Charge ID: " . ($result['charge_id'] ?? 'N/A') . "\n";
                if (!empty($result['payment_url'])) {
                    echo "  - Link Pagamento: " . $result['payment_url'] . "\n";
                }
            }
            
            echo "  - Status: " . ($result['status'] ?? 'N/A') . "\n";
            if (!empty($result['type'])) {
                echo "  - Tipo: " . $result['type'] . "\n";
            }
        } else {
        echo "❌ ERRO!\n";
        echo "  - Mensagem: " . ($result['message'] ?? 'Erro desconhecido') . "\n";
        if (!empty($result['details'])) {
            echo "  - Detalhes: " . json_encode($result['details'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
        }
    }
    
    echo "\n";
    echo "=== FIM DO TESTE ===\n";
    
} catch (\Throwable $e) {
    echo "❌ EXCEÇÃO:\n";
    echo "  - Mensagem: " . $e->getMessage() . "\n";
    echo "  - Arquivo: " . $e->getFile() . "\n";
    echo "  - Linha: " . $e->getLine() . "\n";
    echo "  - Stack Trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
