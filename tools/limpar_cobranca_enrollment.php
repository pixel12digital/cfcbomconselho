<?php
/**
 * Script para limpar cobrança de uma matrícula (para testes)
 * 
 * Uso: php tools/limpar_cobranca_enrollment.php [enrollment_id]
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Config\Database;
use App\Config\Env;

Env::load();

$enrollmentId = $argv[1] ?? 2;

$db = Database::getInstance()->getConnection();

echo "=== LIMPAR COBRANÇA DA MATRÍCULA ===\n\n";
echo "Enrollment ID: {$enrollmentId}\n\n";

try {
    // Verificar se matrícula existe
    $stmt = $db->prepare("SELECT id, gateway_charge_id, billing_status FROM enrollments WHERE id = ?");
    $stmt->execute([$enrollmentId]);
    $enrollment = $stmt->fetch();
    
    if (!$enrollment) {
        die("ERRO: Matrícula #{$enrollmentId} não encontrada.\n");
    }
    
    echo "Matrícula encontrada:\n";
    echo "  - ID: {$enrollment['id']}\n";
    echo "  - Gateway Charge ID: " . ($enrollment['gateway_charge_id'] ?? 'NULL') . "\n";
    echo "  - Billing Status: {$enrollment['billing_status']}\n\n";
    
    // Limpar campos do gateway
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
    
    echo "✅ Cobrança limpa com sucesso!\n";
    echo "  - gateway_charge_id: NULL\n";
    echo "  - gateway_payment_url: NULL\n";
    echo "  - billing_status: draft\n\n";
    echo "Agora você pode executar: php tools/test_carne_local.php {$enrollmentId}\n";
    
} catch (\Throwable $e) {
    echo "❌ ERRO:\n";
    echo "  - Mensagem: " . $e->getMessage() . "\n";
    echo "  - Arquivo: " . $e->getFile() . "\n";
    echo "  - Linha: " . $e->getLine() . "\n";
    exit(1);
}
