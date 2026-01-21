<?php
/**
 * Script para verificar se cobrança existe na Efí e limpar se necessário
 * 
 * Uso: php tools/verificar_e_limpar_cobranca.php [enrollment_id] [--force]
 * 
 * Se --force for passado, limpa mesmo se existir na Efí
 */

require_once __DIR__ . '/../app/autoload.php';
use App\Config\Database;
use App\Config\Env;
use App\Services\EfiPaymentService;

Env::load();

$enrollmentId = $argv[1] ?? null;
$force = in_array('--force', $argv);

if (!$enrollmentId) {
    die("Uso: php tools/verificar_e_limpar_cobranca.php [enrollment_id] [--force]\n");
}

echo "=== VERIFICAR E LIMPAR COBRANÇA ===\n\n";

try {
    $db = Database::getInstance()->getConnection();
    $efiService = new EfiPaymentService();
    
    // Buscar matrícula
    $stmt = $db->prepare("
        SELECT e.*, s.name as student_name, s.cpf as student_cpf, sv.name as service_name
        FROM enrollments e
        LEFT JOIN students s ON s.id = e.student_id
        LEFT JOIN services sv ON sv.id = e.service_id
        WHERE e.id = ?
    ");
    $stmt->execute([$enrollmentId]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment) {
        die("❌ ERRO: Matrícula #{$enrollmentId} não encontrada.\n");
    }
    
    echo "Matrícula encontrada:\n";
    echo "  - ID: {$enrollment['id']}\n";
    echo "  - Aluno: {$enrollment['student_name']} ({$enrollment['student_cpf']})\n";
    echo "  - Serviço: {$enrollment['service_name']}\n";
    echo "  - Gateway Charge ID: " . ($enrollment['gateway_charge_id'] ?? 'NULL') . "\n";
    echo "  - Billing Status: {$enrollment['billing_status']}\n";
    echo "  - Gateway Last Status: " . ($enrollment['gateway_last_status'] ?? 'NULL') . "\n";
    echo "  - Gateway Payment URL: " . (empty($enrollment['gateway_payment_url']) ? 'NULL' : 'EXISTS') . "\n\n";
    
    if (empty($enrollment['gateway_charge_id'])) {
        echo "ℹ️  Esta matrícula não possui cobrança gerada.\n";
        exit(0);
    }
    
    $chargeId = $enrollment['gateway_charge_id'];
    $paymentData = null;
    if (!empty($enrollment['gateway_payment_url'])) {
        $paymentData = json_decode($enrollment['gateway_payment_url'], true);
    }
    $isCarnet = $paymentData && isset($paymentData['type']) && $paymentData['type'] === 'carne';
    
    echo "Tipo de cobrança: " . ($isCarnet ? 'Carnê' : 'Cobrança única') . "\n";
    echo "Charge/Carnet ID: {$chargeId}\n\n";
    
    // Verificar se existe na Efí
    echo "1. Verificando se existe na Efí...\n";
    
    if ($isCarnet) {
        // Verificar carnê
        $response = $efiService->syncCarnet($enrollment);
        if ($response['ok']) {
            echo "   ✅ Carnê EXISTE na Efí\n";
            echo "   Status na Efí: " . ($response['status'] ?? 'N/A') . "\n";
            
            if (!$force) {
                echo "\n⚠️  Carnê existe na Efí. Use --force para limpar mesmo assim.\n";
                exit(0);
            } else {
                echo "   ⚠️  Modo --force ativado. Limpando mesmo assim...\n";
            }
        } else {
            echo "   ❌ Carnê NÃO existe na Efí (ou erro ao consultar)\n";
            echo "   Erro: " . ($response['message'] ?? 'Desconhecido') . "\n";
            echo "   ✅ Pode limpar com segurança\n";
        }
    } else {
        // Verificar cobrança única
        $chargeData = $efiService->getChargeStatus($chargeId, false);
        if ($chargeData) {
            echo "   ✅ Cobrança EXISTE na Efí\n";
            echo "   Status na Efí: " . ($chargeData['status'] ?? 'N/A') . "\n";
            
            if (!$force) {
                echo "\n⚠️  Cobrança existe na Efí. Use --force para limpar mesmo assim.\n";
                exit(0);
            } else {
                echo "   ⚠️  Modo --force ativado. Limpando mesmo assim...\n";
            }
        } else {
            echo "   ❌ Cobrança NÃO existe na Efí (ou erro ao consultar)\n";
            echo "   ✅ Pode limpar com segurança\n";
        }
    }
    
    // Limpar cobrança
    echo "\n2. Limpando cobrança no banco local...\n";
    
    $updateStmt = $db->prepare("
        UPDATE enrollments 
        SET gateway_charge_id = NULL, 
            gateway_payment_url = NULL, 
            gateway_last_status = NULL, 
            gateway_last_event_at = NULL,
            billing_status = 'draft'
        WHERE id = ?
    ");
    $updateStmt->execute([$enrollmentId]);
    
    echo "   ✅ Cobrança limpa com sucesso!\n";
    echo "   - gateway_charge_id: NULL\n";
    echo "   - gateway_payment_url: NULL\n";
    echo "   - gateway_last_status: NULL\n";
    echo "   - gateway_last_event_at: NULL\n";
    echo "   - billing_status: draft\n\n";
    
    echo "✅ PRONTO! Agora você pode gerar uma nova cobrança.\n";
    
} catch (\Throwable $e) {
    echo "❌ EXCEÇÃO:\n";
    echo "  - Mensagem: " . $e->getMessage() . "\n";
    echo "  - Arquivo: " . $e->getFile() . "\n";
    echo "  - Linha: " . $e->getLine() . "\n";
    exit(1);
}
