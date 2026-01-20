<?php
/**
 * Script para verificar qual banco está conectado e dados da matrícula
 */

require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Config/Env.php';

App\Config\Env::load();

echo "==========================================\n";
echo "VERIFICAÇÃO: Banco de Dados\n";
echo "==========================================\n\n";

// Mostrar configuração de conexão
echo "Configuração de Conexão:\n";
echo "  DB_HOST: " . ($_ENV['DB_HOST'] ?? 'localhost (padrão)') . "\n";
echo "  DB_NAME: " . ($_ENV['DB_NAME'] ?? 'cfc_db (padrão)') . "\n";
echo "  DB_USER: " . ($_ENV['DB_USER'] ?? 'root (padrão)') . "\n";
echo "  DB_PORT: " . ($_ENV['DB_PORT'] ?? '3306 (padrão)') . "\n";

// Conectar e verificar host real
$db = App\Config\Database::getInstance()->getConnection();
$stmt = $db->query("SELECT CONNECTION_ID(), DATABASE(), @@hostname as server_hostname");
$connInfo = $stmt->fetch();

echo "\nConexão Atual:\n";
echo "  Connection ID: " . ($connInfo['CONNECTION_ID()'] ?? 'N/A') . "\n";
echo "  Database: " . ($connInfo['DATABASE()'] ?? 'N/A') . "\n";
echo "  Server Hostname: " . ($connInfo['server_hostname'] ?? 'N/A') . "\n";

// Verificar se é local ou remoto
$host = $_ENV['DB_HOST'] ?? 'localhost';
$isRemote = !in_array(strtolower($host), ['localhost', '127.0.0.1', '::1']);

echo "\nTipo de Conexão: " . ($isRemote ? "🌐 REMOTO (Produção)" : "💻 LOCAL (XAMPP)") . "\n";

echo "\n" . str_repeat('=', 60) . "\n\n";

// Buscar dados da matrícula
echo "Dados da Matrícula ID 1:\n";
echo str_repeat('=', 60) . "\n";

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
        billing_status
    FROM enrollments 
    WHERE id = 1
");

$stmt->execute();
$enrollment = $stmt->fetch();

if (!$enrollment) {
    echo "❌ Matrícula não encontrada!\n";
    exit(1);
}

echo "Payment Method: " . ($enrollment['payment_method'] ?? 'NULL') . "\n";
echo "Installments: " . ($enrollment['installments'] ?? 'NULL') . "\n";
echo "Final Price: R$ " . number_format($enrollment['final_price'], 2, ',', '.') . "\n";
echo "Entry Amount: R$ " . number_format($enrollment['entry_amount'] ?? 0, 2, ',', '.') . "\n";
echo "Outstanding Amount: R$ " . number_format($enrollment['outstanding_amount'] ?? 0, 2, ',', '.') . "\n";
echo "Charge ID: " . ($enrollment['gateway_charge_id'] ?? 'NULL') . "\n";
echo "Gateway Status: " . ($enrollment['gateway_last_status'] ?? 'NULL') . "\n";
echo "Billing Status: " . ($enrollment['billing_status'] ?? 'NULL') . "\n";

echo "\n" . str_repeat('=', 60) . "\n";
echo "RESUMO:\n";
echo str_repeat('=', 60) . "\n";

if ($enrollment['payment_method'] === 'cartao' || $enrollment['payment_method'] === 'credit_card') {
    echo "✅ Payment Method: CARTÃO\n";
} elseif ($enrollment['payment_method'] === 'boleto') {
    echo "📄 Payment Method: BOLETO\n";
} elseif ($enrollment['payment_method'] === 'pix') {
    echo "💰 Payment Method: PIX\n";
} else {
    echo "❓ Payment Method: " . ($enrollment['payment_method'] ?? 'NULL') . "\n";
}

$installments = intval($enrollment['installments'] ?? 1);
echo "📊 Installments: {$installments}\n";

if ($installments > 1) {
    echo "⚠️  ATENÇÃO: Tem {$installments} parcelas configuradas!\n";
    
    if ($enrollment['payment_method'] === 'boleto') {
        echo "❌ PROBLEMA: Payment Method é BOLETO mas tem parcelas!\n";
        echo "   Boletos são sempre à vista (1 parcela).\n";
        echo "   Deveria ser CARTÃO para parcelar.\n";
    } elseif ($enrollment['payment_method'] === 'cartao' || $enrollment['payment_method'] === 'credit_card') {
        echo "✅ OK: Payment Method é CARTÃO com parcelas - correto!\n";
    }
} else {
    echo "✅ OK: Pagamento à vista (1 parcela)\n";
}
