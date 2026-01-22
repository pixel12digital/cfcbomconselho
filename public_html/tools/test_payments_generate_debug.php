<?php
/**
 * Script de debug para testar endpoint /api/payments/generate localmente
 * 
 * Uso: php public_html/tools/test_payments_generate_debug.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Definir paths
define('ROOT_PATH', dirname(__DIR__, 2));
define('APP_PATH', ROOT_PATH . '/app');

// Carregar autoloader primeiro
if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require_once ROOT_PATH . '/vendor/autoload.php';
} else {
    require_once APP_PATH . '/autoload.php';
}

// Carregar variáveis de ambiente
App\Config\Env::load();

echo "==========================================\n";
echo "DEBUG: Teste de PaymentsController::generate()\n";
echo "==========================================\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// Simular sessão (para testes locais)
$_SESSION = [
    'user_id' => 1,
    'current_role' => 'admin',
    'cfc_id' => 1
];

// Testar 1: Verificar configuração EFI
echo "1. Verificando configuração EFI...\n";
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';

echo "   EFI_CLIENT_ID: " . ($clientId ? '✅ Configurado (' . substr($clientId, 0, 10) . '...)' : '❌ Não configurado') . "\n";
echo "   EFI_CLIENT_SECRET: " . ($clientSecret ? '✅ Configurado' : '❌ Não configurado') . "\n";
echo "   EFI_SANDBOX: " . ($sandbox ? 'true (Sandbox)' : 'false (Produção)') . "\n\n";

if (!$clientId || !$clientSecret) {
    echo "❌ ERRO: Credenciais EFI não configuradas!\n";
    echo "   Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET no arquivo .env\n";
    exit(1);
}

// Testar 2: Buscar uma matrícula de teste
echo "2. Buscando matrícula para teste...\n";
$enrollmentModel = new App\Models\Enrollment();
$db = App\Config\Database::getInstance()->getConnection();

// Buscar primeira matrícula com saldo devedor
$stmt = $db->prepare("
    SELECT e.*, 
           s.name as service_name,
           st.name as student_name, st.cpf as student_cpf,
           st.full_name, st.email, st.phone, st.phone_primary,
           st.street, st.number, st.neighborhood, st.cep, st.city, st.state_uf
    FROM enrollments e
    INNER JOIN services s ON e.service_id = s.id
    INNER JOIN students st ON e.student_id = st.id
    WHERE e.cfc_id = 1
    AND e.status != 'cancelada'
    AND (e.final_price > COALESCE(e.entry_amount, 0))
    ORDER BY e.id DESC
    LIMIT 1
");

$stmt->execute();
$enrollment = $stmt->fetch();

if (!$enrollment) {
    echo "❌ ERRO: Nenhuma matrícula encontrada para teste\n";
    echo "   Precisa de uma matrícula com saldo devedor\n";
    exit(1);
}

echo "   ✅ Matrícula encontrada:\n";
echo "      ID: {$enrollment['id']}\n";
echo "      Aluno: {$enrollment['student_name']}\n";
echo "      CPF: {$enrollment['student_cpf']}\n";
echo "      Valor: R$ " . number_format($enrollment['final_price'] ?? 0, 2, ',', '.') . "\n";
echo "      Entrada: R$ " . number_format($enrollment['entry_amount'] ?? 0, 2, ',', '.') . "\n";
$saldo = floatval($enrollment['final_price'] ?? 0) - floatval($enrollment['entry_amount'] ?? 0);
echo "      Saldo Devedor: R$ " . number_format($saldo, 2, ',', '.') . "\n\n";

// Testar 3: Verificar método findWithDetails
echo "3. Testando Enrollment::findWithDetails()...\n";
try {
    $enrollmentFull = $enrollmentModel->findWithDetails($enrollment['id']);
    if ($enrollmentFull) {
        echo "   ✅ Matrícula carregada com sucesso\n";
        echo "      Campos: " . implode(', ', array_keys($enrollmentFull)) . "\n";
    } else {
        echo "   ❌ ERRO: Matrícula não encontrada via findWithDetails\n";
        exit(1);
    }
} catch (\Throwable $e) {
    echo "   ❌ ERRO ao buscar matrícula:\n";
    echo "      " . $e->getMessage() . "\n";
    echo "      Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
echo "\n";

// Testar 4: Testar EfiPaymentService::createCharge()
echo "4. Testando EfiPaymentService::createCharge()...\n";
try {
    $efiService = new App\Services\EfiPaymentService();
    
    echo "   Tentando criar cobrança...\n";
    $result = $efiService->createCharge($enrollmentFull);
    
    echo "   Resultado:\n";
    echo "      ok: " . ($result['ok'] ? 'true' : 'false') . "\n";
    echo "      message: " . ($result['message'] ?? 'N/A') . "\n";
    
    if ($result['ok']) {
        echo "      ✅ Cobrança criada com sucesso!\n";
        echo "      charge_id: " . ($result['charge_id'] ?? 'N/A') . "\n";
        echo "      status: " . ($result['status'] ?? 'N/A') . "\n";
    } else {
        echo "      ❌ Erro ao criar cobrança: " . ($result['message'] ?? 'Erro desconhecido') . "\n";
    }
} catch (\Throwable $e) {
    echo "   ❌ EXCEÇÃO capturada:\n";
    echo "      Tipo: " . get_class($e) . "\n";
    echo "      Mensagem: " . $e->getMessage() . "\n";
    echo "      Arquivo: " . $e->getFile() . ":" . $e->getLine() . "\n";
    echo "      Stack trace:\n";
    $trace = $e->getTraceAsString();
    $lines = explode("\n", $trace);
    foreach (array_slice($lines, 0, 10) as $line) {
        echo "         " . $line . "\n";
    }
    exit(1);
}

echo "\n==========================================\n";
echo "RESUMO:\n";
echo "==========================================\n";
echo "✅ Todos os testes executados\n";
echo "\nSe algum teste falhou, corrija o problema antes de usar o endpoint real.\n";
