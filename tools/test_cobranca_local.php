<?php
/**
 * Script de Teste - Cobrança EFI Local
 * 
 * Uso: Acesse via browser: http://localhost/cfc-v.1/public_html/tools/test_cobranca_local.php
 * 
 * Este script testa a criação de cobrança real na EFI usando uma matrícula de teste.
 * ⚠️ ATENÇÃO: Este script cria cobranças REAIS na EFI (produção).
 */

require_once __DIR__ . '/../app/Config/Env.php';
require_once __DIR__ . '/../app/Config/Database.php';
require_once __DIR__ . '/../app/autoload.php';

use App\Config\Env;
use App\Config\Database;
use App\Models\Enrollment;
use App\Services\EfiPaymentService;

// Carregar variáveis de ambiente
Env::load();

// Obter credenciais
$clientId = $_ENV['EFI_CLIENT_ID'] ?? null;
$clientSecret = $_ENV['EFI_CLIENT_SECRET'] ?? null;
$sandbox = ($_ENV['EFI_SANDBOX'] ?? 'true') === 'true';

$results = [];
$hasError = false;
$testEnrollmentId = null;
$chargeResult = null;

// Processar ações
$action = $_GET['action'] ?? 'form';
$enrollmentId = $_POST['enrollment_id'] ?? $_GET['enrollment_id'] ?? null;
$createTest = isset($_POST['create_test']);

// 1. Verificar configuração básica
$results[] = [
    'test' => 'Arquivo .env existe',
    'status' => file_exists(dirname(__DIR__) . '/.env') ? '✅ PASSOU' : '❌ FALHOU',
    'details' => file_exists(dirname(__DIR__) . '/.env') 
        ? "Arquivo encontrado" 
        : "Arquivo não encontrado"
];

$results[] = [
    'test' => 'EFI_CLIENT_ID configurado',
    'status' => !empty($clientId) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientId) 
        ? "CLIENT_ID encontrado (primeiros 10 caracteres: " . substr($clientId, 0, 10) . "...)" 
        : "CLIENT_ID não encontrado no .env"
];

$results[] = [
    'test' => 'EFI_CLIENT_SECRET configurado',
    'status' => !empty($clientSecret) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientSecret) 
        ? "CLIENT_SECRET encontrado (primeiros 10 caracteres: " . substr($clientSecret, 0, 10) . "...)" 
        : "CLIENT_SECRET não encontrado no .env"
];

$results[] = [
    'test' => 'Ambiente configurado',
    'status' => isset($_ENV['EFI_SANDBOX']) ? '✅ PASSOU' : '⚠️ AVISO',
    'details' => "EFI_SANDBOX = " . ($sandbox ? 'true (SANDBOX)' : 'false (PRODUÇÃO)')
];

// 2. Testar autenticação (se credenciais existem)
if (!empty($clientId) && !empty($clientSecret)) {
    $efiService = new EfiPaymentService();
    
    // Testar autenticação via reflexão (método privado)
    $reflection = new ReflectionClass($efiService);
    $method = $reflection->getMethod('getAccessToken');
    $method->setAccessible(true);
    $token = $method->invoke($efiService);
    
    if ($token) {
        $results[] = [
            'test' => 'Autenticação EFI',
            'status' => '✅ PASSOU',
            'details' => "Token obtido com sucesso! (primeiros 20 caracteres: " . substr($token, 0, 20) . "...)"
        ];
    } else {
        $results[] = [
            'test' => 'Autenticação EFI',
            'status' => '❌ FALHOU',
            'details' => "Falha ao obter token. Verifique credenciais e certificado (se necessário)."
        ];
        $hasError = true;
    }
} else {
    $results[] = [
        'test' => 'Autenticação EFI',
        'status' => '⏭️ PULADO',
        'details' => 'Credenciais não configuradas. Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET primeiro.'
    ];
    $hasError = true;
}

// 3. Criar matrícula de teste se solicitado
if ($createTest && !$hasError) {
    try {
        $db = Database::getInstance()->getConnection();
        
        // Buscar primeiro serviço disponível
        $serviceStmt = $db->query("SELECT id, name FROM services LIMIT 1");
        $service = $serviceStmt->fetch();
        
        if (!$service) {
            $results[] = [
                'test' => 'Criar matrícula de teste',
                'status' => '❌ FALHOU',
                'details' => 'Nenhum serviço encontrado no banco. Crie um serviço primeiro.'
            ];
            $hasError = true;
        } else {
            // Buscar primeiro aluno disponível
            $studentStmt = $db->query("SELECT id, name, cpf, full_name, email, phone FROM students LIMIT 1");
            $student = $studentStmt->fetch();
            
            if (!$student) {
                $results[] = [
                    'test' => 'Criar matrícula de teste',
                    'status' => '❌ FALHOU',
                    'details' => 'Nenhum aluno encontrado no banco. Crie um aluno primeiro.'
                ];
                $hasError = true;
            } else {
                // Criar matrícula de teste
                $finalPrice = 100.00; // R$ 100,00
                $entryAmount = 0.00; // Sem entrada
                $outstandingAmount = $finalPrice - $entryAmount;
                
                $insertStmt = $db->prepare("
                    INSERT INTO enrollments 
                    (student_id, service_id, cfc_id, final_price, entry_amount, outstanding_amount, 
                     billing_status, financial_status, installments, status, created_at)
                    VALUES (?, ?, 1, ?, ?, ?, 'draft', 'pendente', 1, 'ativa', NOW())
                ");
                
                $insertStmt->execute([
                    $student['id'],
                    $service['id'],
                    $finalPrice,
                    $entryAmount,
                    $outstandingAmount
                ]);
                
                $testEnrollmentId = $db->lastInsertId();
                
                $results[] = [
                    'test' => 'Criar matrícula de teste',
                    'status' => '✅ PASSOU',
                    'details' => "Matrícula criada: ID {$testEnrollmentId} | Aluno: {$student['name']} | Serviço: {$service['name']} | Valor: R$ " . number_format($outstandingAmount, 2, ',', '.')
                ];
                
                $enrollmentId = $testEnrollmentId; // Usar a matrícula criada
            }
        }
    } catch (Exception $e) {
        $results[] = [
            'test' => 'Criar matrícula de teste',
            'status' => '❌ FALHOU',
            'details' => "Erro: " . $e->getMessage()
        ];
        $hasError = true;
    }
}

// 4. Gerar cobrança se enrollment_id fornecido
if ($enrollmentId && !$hasError && $action === 'generate') {
    try {
        $enrollmentModel = new Enrollment();
        $enrollment = $enrollmentModel->findWithDetails($enrollmentId);
        
        if (!$enrollment) {
            $results[] = [
                'test' => 'Buscar matrícula',
                'status' => '❌ FALHOU',
                'details' => "Matrícula ID {$enrollmentId} não encontrada"
            ];
            $hasError = true;
        } else {
            $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?? 0);
            
            $results[] = [
                'test' => 'Buscar matrícula',
                'status' => '✅ PASSOU',
                'details' => "Matrícula encontrada: ID {$enrollmentId} | Aluno: {$enrollment['student_name']} | Saldo devedor: R$ " . number_format($outstandingAmount, 2, ',', '.')
            ];
            
            if ($outstandingAmount <= 0) {
                $results[] = [
                    'test' => 'Validar saldo devedor',
                    'status' => '❌ FALHOU',
                    'details' => "Saldo devedor deve ser maior que zero. Valor atual: R$ " . number_format($outstandingAmount, 2, ',', '.')
                ];
                $hasError = true;
            } else {
                $results[] = [
                    'test' => 'Validar saldo devedor',
                    'status' => '✅ PASSOU',
                    'details' => "Saldo devedor válido: R$ " . number_format($outstandingAmount, 2, ',', '.')
                ];
                
                // Verificar se já existe cobrança
                if (!empty($enrollment['gateway_charge_id']) && 
                    $enrollment['billing_status'] === 'generated' &&
                    !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error'])) {
                    
                    $results[] = [
                        'test' => 'Gerar cobrança EFI',
                        'status' => '⚠️ AVISO',
                        'details' => "Cobrança já existe: Charge ID = {$enrollment['gateway_charge_id']} | Status = {$enrollment['gateway_last_status']}"
                    ];
                    
                    $chargeResult = [
                        'ok' => true,
                        'charge_id' => $enrollment['gateway_charge_id'],
                        'status' => $enrollment['gateway_last_status'],
                        'payment_url' => $enrollment['gateway_payment_url'] ?? null,
                        'message' => 'Cobrança já existe'
                    ];
                } else {
                    // Gerar cobrança
                    $efiService = new EfiPaymentService();
                    $chargeResult = $efiService->createCharge($enrollment);
                    
                    if ($chargeResult['ok']) {
                        $results[] = [
                            'test' => 'Gerar cobrança EFI',
                            'status' => '✅ PASSOU',
                            'details' => "Cobrança criada com sucesso! Charge ID: {$chargeResult['charge_id']} | Status: {$chargeResult['status']}"
                        ];
                    } else {
                        $results[] = [
                            'test' => 'Gerar cobrança EFI',
                            'status' => '❌ FALHOU',
                            'details' => "Erro: " . ($chargeResult['message'] ?? 'Erro desconhecido')
                        ];
                        $hasError = true;
                    }
                }
            }
        }
    } catch (Exception $e) {
        $results[] = [
            'test' => 'Gerar cobrança EFI',
            'status' => '❌ FALHOU',
            'details' => "Exceção: " . $e->getMessage()
        ];
        $hasError = true;
    }
}

// Output HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teste de Cobrança EFI - Local</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1000px;
            margin: 0 auto;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        h1 {
            color: #023A8D;
            margin-top: 0;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .warning-box strong {
            color: #856404;
        }
        .test-item {
            padding: 15px;
            margin: 10px 0;
            border-radius: 6px;
            border-left: 4px solid #ddd;
        }
        .test-item.passed {
            background: #d4edda;
            border-color: #28a745;
        }
        .test-item.failed {
            background: #f8d7da;
            border-color: #dc3545;
        }
        .test-item.warning {
            background: #fff3cd;
            border-color: #ffc107;
        }
        .test-item.skipped {
            background: #e2e3e5;
            border-color: #6c757d;
        }
        .test-name {
            font-weight: 600;
            margin-bottom: 5px;
        }
        .test-status {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 4px;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .test-details {
            color: #666;
            font-size: 0.9em;
            margin-top: 5px;
        }
        .form-group {
            margin: 20px 0;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #333;
        }
        .form-group input {
            width: 100%;
            max-width: 400px;
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #023A8D;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            border: none;
            cursor: pointer;
            font-size: 14px;
            margin-right: 10px;
            margin-top: 10px;
        }
        .btn:hover {
            background: #022a6d;
        }
        .btn-secondary {
            background: #6c757d;
        }
        .btn-secondary:hover {
            background: #5a6268;
        }
        .btn-danger {
            background: #dc3545;
        }
        .btn-danger:hover {
            background: #c82333;
        }
        .charge-result {
            background: #e7f3ff;
            border-left: 4px solid #023A8D;
            padding: 20px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .charge-result h3 {
            margin-top: 0;
            color: #023A8D;
        }
        .charge-result code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .charge-result a {
            color: #023A8D;
            text-decoration: none;
            font-weight: 600;
        }
        .charge-result a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>💳 Teste de Cobrança EFI - Local</h1>
        <p>Este script testa a criação de cobrança real na EFI usando uma matrícula.</p>
        
        <div class="warning-box">
            <strong>⚠️ ATENÇÃO:</strong> Este script cria cobranças <strong>REAIS</strong> na EFI (produção). 
            Certifique-se de que está usando credenciais de teste ou que deseja criar cobranças reais.
        </div>
        
        <?php foreach ($results as $result): ?>
            <div class="test-item <?= strtolower(str_replace(['✅ ', '❌ ', '⚠️ ', '⏭️ '], '', $result['status'])) ?>">
                <div class="test-name"><?= htmlspecialchars($result['test']) ?></div>
                <div class="test-status"><?= htmlspecialchars($result['status']) ?></div>
                <div class="test-details"><?= htmlspecialchars($result['details']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (!$hasError): ?>
        <div class="container">
            <h2>📝 Ações</h2>
            
            <form method="POST" action="?action=create_test">
                <div class="form-group">
                    <label>Criar Matrícula de Teste</label>
                    <p style="color: #666; font-size: 0.9em;">
                        Cria uma matrícula de teste com saldo devedor de R$ 100,00 para testar a cobrança.
                    </p>
                    <button type="submit" name="create_test" value="1" class="btn">Criar Matrícula de Teste</button>
                </div>
            </form>
            
            <form method="GET" action="?action=generate">
                <div class="form-group">
                    <label>Ou usar matrícula existente (ID)</label>
                    <input type="number" name="enrollment_id" placeholder="Digite o ID da matrícula" 
                           value="<?= htmlspecialchars($enrollmentId ?? '') ?>" required>
                    <button type="submit" class="btn">Gerar Cobrança</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if ($chargeResult): ?>
        <div class="container charge-result">
            <h3>📊 Resultado da Cobrança</h3>
            
            <?php if ($chargeResult['ok']): ?>
                <p><strong>✅ Cobrança gerada com sucesso!</strong></p>
                <p><strong>Charge ID:</strong> <code><?= htmlspecialchars($chargeResult['charge_id'] ?? 'N/A') ?></code></p>
                <p><strong>Status:</strong> <code><?= htmlspecialchars($chargeResult['status'] ?? 'N/A') ?></code></p>
                
                <?php if (!empty($chargeResult['payment_url'])): ?>
                    <p><strong>URL de Pagamento:</strong></p>
                    <code><?= htmlspecialchars($chargeResult['payment_url']) ?></code>
                    <p>
                        <a href="<?= htmlspecialchars($chargeResult['payment_url']) ?>" target="_blank">
                            🔗 Abrir link de pagamento
                        </a>
                    </p>
                <?php else: ?>
                    <p><em>URL de pagamento não disponível ainda. A cobrança pode estar sendo processada.</em></p>
                <?php endif; ?>
                
                <?php if (isset($chargeResult['message'])): ?>
                    <p><em><?= htmlspecialchars($chargeResult['message']) ?></em></p>
                <?php endif; ?>
            <?php else: ?>
                <p><strong>❌ Erro ao gerar cobrança</strong></p>
                <p><strong>Mensagem:</strong> <code><?= htmlspecialchars($chargeResult['message'] ?? 'Erro desconhecido') ?></code></p>
            <?php endif; ?>
            
            <p style="margin-top: 20px;">
                <a href="?" class="btn btn-secondary">← Voltar</a>
            </p>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <p style="margin-top: 20px;">
            <a href="/" style="color: #023A8D; text-decoration: none;">← Voltar ao sistema</a>
        </p>
    </div>
</body>
</html>
