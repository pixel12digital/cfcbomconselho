<?php
/**
 * Script para buscar matrícula de R$ 200,00 (Reciclagem)
 */

require_once __DIR__ . '/../app/autoload.php';
use App\Config\Database;
use App\Config\Env;

Env::load();

try {
    $db = Database::getInstance()->getConnection();
    
    // Buscar matrícula de Reciclagem com valor próximo a R$ 200,00
    $stmt = $db->prepare("
        SELECT e.id, e.final_price, e.outstanding_amount, 
               s.name as student_name, s.cpf as student_cpf,
               sv.name as service_name,
               e.gateway_charge_id, e.billing_status, e.gateway_last_status
        FROM enrollments e
        LEFT JOIN students s ON s.id = e.student_id
        LEFT JOIN services sv ON sv.id = e.service_id
        WHERE sv.name LIKE '%Reciclagem%'
        AND (e.final_price = 200.00 OR e.outstanding_amount = 200.00)
        ORDER BY e.id DESC
        LIMIT 5
    ");
    $stmt->execute();
    $enrollments = $stmt->fetchAll();
    
    if (empty($enrollments)) {
        echo "Nenhuma matrícula encontrada com valor de R$ 200,00 (Reciclagem)\n";
        echo "Buscando todas as matrículas de Reciclagem...\n\n";
        
        $stmt2 = $db->prepare("
            SELECT e.id, e.final_price, e.outstanding_amount, 
                   s.name as student_name, s.cpf as student_cpf,
                   sv.name as service_name,
                   e.gateway_charge_id, e.billing_status, e.gateway_last_status
            FROM enrollments e
            LEFT JOIN students s ON s.id = e.student_id
            LEFT JOIN services sv ON sv.id = e.service_id
            WHERE sv.name LIKE '%Reciclagem%'
            ORDER BY e.id DESC
            LIMIT 10
        ");
        $stmt2->execute();
        $enrollments = $stmt2->fetchAll();
    }
    
    if (empty($enrollments)) {
        die("Nenhuma matrícula de Reciclagem encontrada.\n");
    }
    
    echo "=== MATRÍCULAS ENCONTRADAS ===\n\n";
    foreach ($enrollments as $enr) {
        echo "ID: {$enr['id']}\n";
        echo "  Aluno: {$enr['student_name']} ({$enr['student_cpf']})\n";
        echo "  Serviço: {$enr['service_name']}\n";
        echo "  Valor Final: R$ " . number_format($enr['final_price'], 2, ',', '.') . "\n";
        echo "  Saldo Devedor: R$ " . number_format($enr['outstanding_amount'], 2, ',', '.') . "\n";
        echo "  Gateway Charge ID: " . ($enr['gateway_charge_id'] ?? 'NULL') . "\n";
        echo "  Billing Status: {$enr['billing_status']}\n";
        echo "  Gateway Last Status: " . ($enr['gateway_last_status'] ?? 'NULL') . "\n";
        echo "\n";
    }
    
    // Se encontrou exatamente uma com R$ 200,00, mostrar
    $exactMatch = null;
    foreach ($enrollments as $enr) {
        if (($enr['final_price'] == 200.00 || $enr['outstanding_amount'] == 200.00) && 
            strpos($enr['service_name'], 'Reciclagem') !== false) {
            $exactMatch = $enr;
            break;
        }
    }
    
    if ($exactMatch) {
        echo "\n✅ MATCH EXATO ENCONTRADO:\n";
        echo "   Enrollment ID: {$exactMatch['id']}\n";
        echo "   Execute: php tools/verificar_e_limpar_cobranca.php {$exactMatch['id']}\n";
    }
    
} catch (\Throwable $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
