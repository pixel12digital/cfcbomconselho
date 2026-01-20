<?php
/**
 * Script de teste para criação de cobrança via API de Cobranças EFI
 * 
 * Uso: php public_html/tools/test_efi_create_charge_cobrancas.php
 */

require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/Services/EfiPaymentService.php';

// Carregar variáveis de ambiente
App\Config\Env::load();

echo "==========================================\n";
echo "TESTE: Criação de Cobrança EFI (Cobranças)\n";
echo "==========================================\n";
echo "Data/Hora: " . date('Y-m-d H:i:s') . "\n\n";

// Criar instância do serviço
echo "Criando instância de EfiPaymentService...\n";
$service = new App\Services\EfiPaymentService();
echo "✅ Instância criada\n\n";

// Obter token de Cobranças
echo "1. Obtendo Token de Cobranças...\n";
$reflection = new ReflectionClass($service);
$getToken = $reflection->getMethod('getAccessToken');
$getToken->setAccessible(true);

$token = $getToken->invoke($service, false);
if (!$token) {
    echo "❌ Falha ao obter token\n";
    exit(1);
}

$tokenLen = strlen($token);
$tokenPrefix = substr($token, 0, 10);
echo "   ✅ Token obtido\n";
echo "   Length: {$tokenLen} caracteres\n";
echo "   Prefix: {$tokenPrefix}\n\n";

// Montar payload mínimo válido para boleto
echo "2. Montando payload para boleto...\n";
$payload = [
    'items' => [
        [
            'name' => 'Teste de Cobrança',
            'value' => 10000, // R$ 100,00 em centavos
            'amount' => 1
        ]
    ],
    'customer' => [
        'name' => 'Cliente Teste',
        'cpf' => '12345678901',
        'email' => 'teste@example.com'
    ],
    'payment' => [
        'banking_billet' => [
            'customer' => [
                'name' => 'Cliente Teste',
                'cpf' => '12345678909',
                'phone_number' => '11999999999',
                'email' => 'cliente.teste@example.com',
                'address' => [
                    'street' => 'Rua Teste',
                    'number' => '100',
                    'neighborhood' => 'Centro',
                    'zipcode' => '01001000',
                    'city' => 'Sao Paulo',
                    'state' => 'SP'
                ]
            ],
            'expire_at' => date('Y-m-d', strtotime('+3 days')),
            'message' => 'Pagamento referente a matrícula'
        ]
    ],
    'metadata' => [
        'test' => 'true',
        'created_at' => date('Y-m-d H:i:s')
    ]
];
echo "   ✅ Payload montado\n";
echo "   Total: R$ " . number_format($payload['items'][0]['value'] / 100, 2, ',', '.') . "\n\n";

// Fazer requisição
echo "3. Criando cobrança via POST /v1/charge/one-step...\n";
$makeRequest = $reflection->getMethod('makeRequest');
$makeRequest->setAccessible(true);

$response = $makeRequest->invoke($service, 'POST', '/charge/one-step', $payload, $token, false);

$httpCode = $response['http_code'] ?? 0;
$responseData = $response['response'] ?? $response;
$rawResponse = $response['raw_response'] ?? '';

echo "   HTTP Code: {$httpCode}\n";

if ($httpCode >= 200 && $httpCode < 300) {
    echo "   ✅ Requisição bem-sucedida\n";
    
    // Extrair charge_id
    $chargeData = $responseData['data'] ?? $responseData;
    $chargeId = $chargeData['charge_id'] ?? $chargeData['id'] ?? null;
    $status = $chargeData['status'] ?? 'unknown';
    
    echo "\n4. Dados da Cobrança:\n";
    echo "   Charge ID: " . ($chargeId ?? 'N/A') . "\n";
    echo "   Status: {$status}\n";
    
    // Verificar URL de pagamento
    $paymentUrl = null;
    if (isset($chargeData['payment']['banking_billet']['link'])) {
        $paymentUrl = $chargeData['payment']['banking_billet']['link'];
        echo "   Link do Boleto: {$paymentUrl}\n";
    }
    
    echo "\n5. Response (primeiros 300 chars):\n";
    echo "   " . substr(json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT), 0, 300) . "...\n";
    
} else {
    echo "   ❌ Requisição falhou\n";
    $errorMessage = $responseData['error_description'] ?? $responseData['message'] ?? $responseData['error'] ?? 'Erro desconhecido';
    echo "   Erro: {$errorMessage}\n";
    echo "\n   Response (primeiros 300 chars):\n";
    echo "   " . substr($rawResponse, 0, 300) . "...\n";
}

echo "\n==========================================\n";
echo "RESUMO:\n";
echo "==========================================\n";
echo "HTTP Code: {$httpCode}\n";
if ($httpCode >= 200 && $httpCode < 300) {
    echo "Status: ✅ SUCESSO\n";
    if (isset($chargeId)) {
        echo "Charge ID obtido: ✅ SIM\n";
    } else {
        echo "Charge ID obtido: ⚠️ NÃO (verificar estrutura da resposta)\n";
    }
} else {
    echo "Status: ❌ FALHA\n";
}
echo "\n";

// Mostrar últimas linhas do log EFI
echo "==========================================\n";
echo "ÚLTIMAS LINHAS DO LOG EFI:\n";
echo "==========================================\n";

$logFile = __DIR__ . '/../../storage/logs/php_errors.log';
if (file_exists($logFile)) {
    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $efiLines = array_filter($lines, function($line) {
        return stripos($line, 'EFI-') !== false;
    });
    $efiLines = array_slice($efiLines, -20);
    
    if (!empty($efiLines)) {
        foreach ($efiLines as $line) {
            echo $line . "\n";
        }
    } else {
        echo "Nenhuma linha EFI encontrada no log.\n";
    }
} else {
    echo "Arquivo de log não encontrado: {$logFile}\n";
}

echo "\n";
