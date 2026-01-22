<?php
/**
 * Script de Validação - Integração Gateway EFI
 * 
 * Uso: Acesse via browser: http://localhost/cfc-v.1/public_html/tools/validar_integracao_efi.php
 * 
 * Este script valida completamente a integração com o gateway EFI:
 * - Configuração (.env)
 * - Autenticação OAuth
 * - Criação de cobrança
 * - Consulta de status
 * - Sincronização
 * - Mapeamento de status
 */

require_once __DIR__ . '/../../app/Config/Env.php';
require_once __DIR__ . '/../../app/Config/Database.php';
require_once __DIR__ . '/../../app/autoload.php';

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
$certPath = $_ENV['EFI_CERT_PATH'] ?? null;
$certPassword = $_ENV['EFI_CERT_PASSWORD'] ?? null;
$webhookSecret = $_ENV['EFI_WEBHOOK_SECRET'] ?? null;

$baseUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br/v1'
    : 'https://apis.gerencianet.com.br/v1';
$oauthUrl = $sandbox 
    ? 'https://sandbox.gerencianet.com.br'
    : 'https://apis.gerencianet.com.br';

$results = [];
$hasError = false;
$testData = [];

// Processar ações
$action = $_GET['action'] ?? 'validate';
$enrollmentId = $_POST['enrollment_id'] ?? $_GET['enrollment_id'] ?? null;
$chargeId = $_POST['charge_id'] ?? $_GET['charge_id'] ?? null;

// ============================================
// 1. VALIDAÇÃO DE CONFIGURAÇÃO
// ============================================

$results[] = [
    'category' => 'Configuração',
    'test' => 'Arquivo .env existe',
    'status' => file_exists(dirname(__DIR__) . '/../.env') ? '✅ PASSOU' : '❌ FALHOU',
    'details' => file_exists(dirname(__DIR__) . '/../.env') 
        ? "Arquivo encontrado" 
        : "Arquivo não encontrado em: " . dirname(__DIR__) . '/../.env'
];

$results[] = [
    'category' => 'Configuração',
    'test' => 'EFI_CLIENT_ID configurado',
    'status' => !empty($clientId) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientId) 
        ? "CLIENT_ID encontrado (tamanho: " . strlen($clientId) . " caracteres, primeiros 10: " . substr($clientId, 0, 10) . "...)" 
        : "CLIENT_ID não encontrado no .env"
];

$results[] = [
    'category' => 'Configuração',
    'test' => 'EFI_CLIENT_SECRET configurado',
    'status' => !empty($clientSecret) ? '✅ PASSOU' : '❌ FALHOU',
    'details' => !empty($clientSecret) 
        ? "CLIENT_SECRET encontrado (tamanho: " . strlen($clientSecret) . " caracteres, primeiros 10: " . substr($clientSecret, 0, 10) . "...)" 
        : "CLIENT_SECRET não encontrado no .env"
];

$results[] = [
    'category' => 'Configuração',
    'test' => 'EFI_SANDBOX configurado',
    'status' => isset($_ENV['EFI_SANDBOX']) ? '✅ PASSOU' : '⚠️ AVISO',
    'details' => "EFI_SANDBOX = " . ($sandbox ? 'true (SANDBOX)' : 'false (PRODUÇÃO)') . " | URL Base: {$baseUrl}"
];

if ($certPath) {
    $certExists = file_exists($certPath);
    $certDetails = "Certificado encontrado: {$certPath}";
    
    if ($certExists) {
        $fileSize = filesize($certPath);
        $certDetails .= " | Tamanho: " . number_format($fileSize) . " bytes";
        
        // Verificar extensão
        $extension = strtolower(pathinfo($certPath, PATHINFO_EXTENSION));
        if (!in_array($extension, ['p12', 'pfx'])) {
            $certDetails .= " | ⚠️ Extensão: .{$extension} (esperado: .p12 ou .pfx)";
        }
        
        // Verificar se tem senha configurada
        if (empty($certPassword)) {
            $certDetails .= " | ⚠️ Senha não configurada (EFI_CERT_PASSWORD)";
        } else {
            $certDetails .= " | Senha configurada (" . strlen($certPassword) . " caracteres)";
        }
    }
    
    $results[] = [
        'category' => 'Configuração',
        'test' => 'EFI_CERT_PATH configurado',
        'status' => $certExists ? '✅ PASSOU' : '⚠️ AVISO',
        'details' => $certDetails
    ];
} else {
    $results[] = [
        'category' => 'Configuração',
        'test' => 'EFI_CERT_PATH configurado',
        'status' => !$sandbox ? '⚠️ AVISO' : '⏭️ PULADO',
        'details' => !$sandbox 
            ? "Certificado não configurado. Em produção, pode ser necessário." 
            : "Certificado não configurado (opcional em sandbox)"
    ];
}

if ($webhookSecret) {
    $results[] = [
        'category' => 'Configuração',
        'test' => 'EFI_WEBHOOK_SECRET configurado',
        'status' => '✅ PASSOU',
        'details' => "Webhook secret configurado (tamanho: " . strlen($webhookSecret) . " caracteres)"
    ];
} else {
    $results[] = [
        'category' => 'Configuração',
        'test' => 'EFI_WEBHOOK_SECRET configurado',
        'status' => '⚠️ AVISO',
        'details' => "Webhook secret não configurado (recomendado para validação de webhooks)"
    ];
}

// ============================================
// 2. TESTE DE AUTENTICAÇÃO OAUTH
// ============================================

if (!empty($clientId) && !empty($clientSecret)) {
    try {
        // Testar autenticação diretamente com cURL para capturar detalhes
        $url = $oauthUrl . '/oauth/token';
        $payload = ['grant_type' => 'client_credentials'];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
            'Authorization: Basic ' . base64_encode($clientId . ':' . $clientSecret)
        ]);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        
        // Habilitar verbose para debug
        $verbose = fopen('php://temp', 'w+');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        curl_setopt($ch, CURLOPT_STDERR, $verbose);
        
        // Configurar certificado se disponível
        if ($certPath && file_exists($certPath)) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certPath);
            curl_setopt($ch, CURLOPT_SSLCERTTYPE, 'P12');
            curl_setopt($ch, CURLOPT_SSLKEY, $certPath);
            curl_setopt($ch, CURLOPT_SSLKEYTYPE, 'P12');
            if ($certPassword) {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $certPassword);
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, $certPassword);
            } else {
                curl_setopt($ch, CURLOPT_SSLCERTPASSWD, '');
                curl_setopt($ch, CURLOPT_SSLKEYPASSWD, '');
            }
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrNo = curl_errno($ch);
        
        // Capturar verbose log
        rewind($verbose);
        $verboseLog = stream_get_contents($verbose);
        fclose($verbose);
        
        curl_close($ch);
        
        // Analisar resultado
        if ($curlError) {
            $errorDetails = "cURL Error: {$curlError} (errno: {$curlErrNo})";
            
            // Adicionar informações específicas baseadas no erro
            if (strpos($curlError, 'Connection was reset') !== false || strpos($curlError, 'Recv failure') !== false) {
                $errorDetails .= "\n\nPossíveis causas:\n";
                $errorDetails .= "1. Certificado cliente necessário em produção (mutual TLS)\n";
                $errorDetails .= "2. Certificado pode estar corrompido ou com senha incorreta\n";
                $errorDetails .= "3. Firewall ou proxy bloqueando conexão\n";
                $errorDetails .= "4. Problema de rede/conectividade";
            } elseif (strpos($curlError, 'SSL') !== false || strpos($curlError, 'certificate') !== false) {
                $errorDetails .= "\n\nProblema com certificado SSL:\n";
                $errorDetails .= "1. Verifique se o certificado está no formato P12\n";
                $errorDetails .= "2. Verifique se a senha do certificado está correta (EFI_CERT_PASSWORD)\n";
                $errorDetails .= "3. Tente abrir o certificado com OpenSSL para validar";
            } elseif (strpos($curlError, 'timeout') !== false) {
                $errorDetails .= "\n\nTimeout na conexão:\n";
                $errorDetails .= "1. Verifique conectividade com a internet\n";
                $errorDetails .= "2. Verifique se não há firewall bloqueando\n";
                $errorDetails .= "3. Tente aumentar timeout";
            }
            
            if ($verboseLog) {
                $errorDetails .= "\n\nDetalhes técnicos (cURL verbose):\n" . substr($verboseLog, 0, 1000);
            }
            
            $results[] = [
                'category' => 'Autenticação',
                'test' => 'OAuth - Obter Access Token',
                'status' => '❌ FALHOU',
                'details' => $errorDetails
            ];
            $hasError = true;
        } elseif ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $errorMessage = $errorData['error_description'] ?? $errorData['error'] ?? $errorData['message'] ?? 'Erro desconhecido';
            
            $errorDetails = "HTTP {$httpCode}: {$errorMessage}";
            
            if ($httpCode === 401) {
                $errorDetails .= "\n\nErro 401 (Não autorizado):\n";
                $errorDetails .= "1. Verifique se CLIENT_ID e CLIENT_SECRET estão corretos\n";
                $errorDetails .= "2. Verifique se as credenciais correspondem ao ambiente (sandbox/produção)\n";
                $errorDetails .= "3. Verifique se não há espaços extras nas credenciais no .env";
            } elseif ($httpCode === 403) {
                $errorDetails .= "\n\nErro 403 (Proibido):\n";
                $errorDetails .= "1. Certificado cliente pode ser obrigatório em produção\n";
                $errorDetails .= "2. Verifique se o certificado está configurado corretamente";
            } elseif ($httpCode === 404) {
                $errorDetails .= "\n\nErro 404 (Não encontrado):\n";
                $errorDetails .= "1. Verifique se a URL do OAuth está correta\n";
                $errorDetails .= "2. URL esperada: {$url}";
            }
            
            if ($response) {
                $errorDetails .= "\n\nResposta da API:\n" . substr($response, 0, 500);
            }
            
            $results[] = [
                'category' => 'Autenticação',
                'test' => 'OAuth - Obter Access Token',
                'status' => '❌ FALHOU',
                'details' => $errorDetails
            ];
            $hasError = true;
        } elseif (!$response) {
            $results[] = [
                'category' => 'Autenticação',
                'test' => 'OAuth - Obter Access Token',
                'status' => '❌ FALHOU',
                'details' => "Resposta vazia da API. Verifique conectividade."
            ];
            $hasError = true;
        } else {
            $data = json_decode($response, true);
            if (isset($data['access_token'])) {
                $results[] = [
                    'category' => 'Autenticação',
                    'test' => 'OAuth - Obter Access Token',
                    'status' => '✅ PASSOU',
                    'details' => "Token obtido com sucesso! (tamanho: " . strlen($data['access_token']) . " caracteres, primeiros 30: " . substr($data['access_token'], 0, 30) . "...)"
                ];
                $testData['access_token'] = $data['access_token'];
            } else {
                $results[] = [
                    'category' => 'Autenticação',
                    'test' => 'OAuth - Obter Access Token',
                    'status' => '❌ FALHOU',
                    'details' => "Resposta não contém access_token. Resposta: " . substr($response, 0, 500)
                ];
                $hasError = true;
            }
        }
    } catch (Exception $e) {
        $results[] = [
            'category' => 'Autenticação',
            'test' => 'OAuth - Obter Access Token',
            'status' => '❌ FALHOU',
            'details' => "Exceção: " . $e->getMessage() . "\nTrace: " . $e->getTraceAsString()
        ];
        $hasError = true;
    }
} else {
    $results[] = [
        'category' => 'Autenticação',
        'test' => 'OAuth - Obter Access Token',
        'status' => '⏭️ PULADO',
        'details' => 'Credenciais não configuradas. Configure EFI_CLIENT_ID e EFI_CLIENT_SECRET primeiro.'
    ];
    $hasError = true;
}

// ============================================
// 3. TESTE DE CRIAÇÃO DE COBRANÇA
// ============================================

if (!$hasError && $action === 'test_charge' && $enrollmentId) {
    try {
        $enrollmentModel = new Enrollment();
        $enrollment = $enrollmentModel->findWithDetails($enrollmentId);
        
        if (!$enrollment) {
            $results[] = [
                'category' => 'Cobrança',
                'test' => 'Buscar matrícula',
                'status' => '❌ FALHOU',
                'details' => "Matrícula ID {$enrollmentId} não encontrada"
            ];
            $hasError = true;
        } else {
            $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?? 0);
            
            $results[] = [
                'category' => 'Cobrança',
                'test' => 'Buscar matrícula',
                'status' => '✅ PASSOU',
                'details' => "Matrícula encontrada: ID {$enrollmentId} | Aluno: {$enrollment['student_name']} | Saldo devedor: R$ " . number_format($outstandingAmount, 2, ',', '.')
            ];
            
            if ($outstandingAmount <= 0) {
                $results[] = [
                    'category' => 'Cobrança',
                    'test' => 'Validar saldo devedor',
                    'status' => '❌ FALHOU',
                    'details' => "Saldo devedor deve ser maior que zero. Valor atual: R$ " . number_format($outstandingAmount, 2, ',', '.')
                ];
                $hasError = true;
            } else {
                $results[] = [
                    'category' => 'Cobrança',
                    'test' => 'Validar saldo devedor',
                    'status' => '✅ PASSOU',
                    'details' => "Saldo devedor válido: R$ " . number_format($outstandingAmount, 2, ',', '.')
                ];
                
                // Verificar se já existe cobrança
                if (!empty($enrollment['gateway_charge_id']) && 
                    $enrollment['billing_status'] === 'generated' &&
                    !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error'])) {
                    
                    $results[] = [
                        'category' => 'Cobrança',
                        'test' => 'Criar cobrança EFI',
                        'status' => '⚠️ AVISO',
                        'details' => "Cobrança já existe: Charge ID = {$enrollment['gateway_charge_id']} | Status = {$enrollment['gateway_last_status']}"
                    ];
                    
                    $testData['charge'] = [
                        'charge_id' => $enrollment['gateway_charge_id'],
                        'status' => $enrollment['gateway_last_status'],
                        'payment_url' => $enrollment['gateway_payment_url'] ?? null
                    ];
                } else {
                    // Gerar cobrança
                    $efiService = new EfiPaymentService();
                    $chargeResult = $efiService->createCharge($enrollment);
                    
                    if ($chargeResult['ok']) {
                        $results[] = [
                            'category' => 'Cobrança',
                            'test' => 'Criar cobrança EFI',
                            'status' => '✅ PASSOU',
                            'details' => "Cobrança criada com sucesso! Charge ID: {$chargeResult['charge_id']} | Status: {$chargeResult['status']}"
                        ];
                        $testData['charge'] = $chargeResult;
                    } else {
                        $results[] = [
                            'category' => 'Cobrança',
                            'test' => 'Criar cobrança EFI',
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
            'category' => 'Cobrança',
            'test' => 'Criar cobrança EFI',
            'status' => '❌ FALHOU',
            'details' => "Exceção: " . $e->getMessage()
        ];
        $hasError = true;
    }
}

// ============================================
// 4. TESTE DE CONSULTA DE STATUS
// ============================================

if (!$hasError && $action === 'test_status' && $chargeId) {
    try {
        $efiService = new EfiPaymentService();
        $chargeData = $efiService->getChargeStatus($chargeId);
        
        if ($chargeData) {
            $status = $chargeData['status'] ?? 'unknown';
            $paymentUrl = null;
            
            if (isset($chargeData['payment'])) {
                if (isset($chargeData['payment']['pix']['qr_code'])) {
                    $paymentUrl = $chargeData['payment']['pix']['qr_code'];
                } elseif (isset($chargeData['payment']['banking_billet']['link'])) {
                    $paymentUrl = $chargeData['payment']['banking_billet']['link'];
                }
            }
            
            $results[] = [
                'category' => 'Consulta',
                'test' => 'Consultar status da cobrança',
                'status' => '✅ PASSOU',
                'details' => "Status: {$status} | Payment URL: " . ($paymentUrl ? 'Disponível' : 'Não disponível')
            ];
            
            $testData['charge_status'] = [
                'charge_id' => $chargeId,
                'status' => $status,
                'payment_url' => $paymentUrl,
                'full_data' => $chargeData
            ];
        } else {
            $results[] = [
                'category' => 'Consulta',
                'test' => 'Consultar status da cobrança',
                'status' => '❌ FALHOU',
                'details' => "Não foi possível consultar status da cobrança {$chargeId}. Verifique se a cobrança existe."
            ];
            $hasError = true;
        }
    } catch (Exception $e) {
        $results[] = [
            'category' => 'Consulta',
            'test' => 'Consultar status da cobrança',
            'status' => '❌ FALHOU',
            'details' => "Exceção: " . $e->getMessage()
        ];
        $hasError = true;
    }
}

// ============================================
// 5. TESTE DE SINCRONIZAÇÃO
// ============================================

if (!$hasError && $action === 'test_sync' && $enrollmentId) {
    try {
        $enrollmentModel = new Enrollment();
        $enrollment = $enrollmentModel->findWithDetails($enrollmentId);
        
        if (!$enrollment) {
            $results[] = [
                'category' => 'Sincronização',
                'test' => 'Buscar matrícula para sincronização',
                'status' => '❌ FALHOU',
                'details' => "Matrícula ID {$enrollmentId} não encontrada"
            ];
            $hasError = true;
        } elseif (empty($enrollment['gateway_charge_id'])) {
            $results[] = [
                'category' => 'Sincronização',
                'test' => 'Validar cobrança existente',
                'status' => '❌ FALHOU',
                'details' => "Matrícula não possui cobrança gerada (gateway_charge_id vazio)"
            ];
            $hasError = true;
        } else {
            $results[] = [
                'category' => 'Sincronização',
                'test' => 'Validar cobrança existente',
                'status' => '✅ PASSOU',
                'details' => "Cobrança encontrada: Charge ID = {$enrollment['gateway_charge_id']}"
            ];
            
            $efiService = new EfiPaymentService();
            $syncResult = $efiService->syncCharge($enrollment);
            
            if ($syncResult['ok']) {
                $results[] = [
                    'category' => 'Sincronização',
                    'test' => 'Sincronizar cobrança',
                    'status' => '✅ PASSOU',
                    'details' => "Sincronização realizada! Status: {$syncResult['status']} | Billing Status: {$syncResult['billing_status']} | Financial Status: " . ($syncResult['financial_status'] ?? 'não alterado')
                ];
                $testData['sync'] = $syncResult;
            } else {
                $results[] = [
                    'category' => 'Sincronização',
                    'test' => 'Sincronizar cobrança',
                    'status' => '❌ FALHOU',
                    'details' => "Erro: " . ($syncResult['message'] ?? 'Erro desconhecido')
                ];
                $hasError = true;
            }
        }
    } catch (Exception $e) {
        $results[] = [
            'category' => 'Sincronização',
            'test' => 'Sincronizar cobrança',
            'status' => '❌ FALHOU',
            'details' => "Exceção: " . $e->getMessage()
        ];
        $hasError = true;
    }
}

// ============================================
// 6. RESUMO FINAL
// ============================================

$totalTests = count($results);
$passedTests = count(array_filter($results, fn($r) => strpos($r['status'], '✅') !== false));
$failedTests = count(array_filter($results, fn($r) => strpos($r['status'], '❌') !== false));
$warningTests = count(array_filter($results, fn($r) => strpos($r['status'], '⚠️') !== false));

// Output HTML
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validação Integração Gateway EFI</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            padding: 20px;
            background: #f5f5f5;
            max-width: 1200px;
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
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .summary-card {
            padding: 15px;
            border-radius: 6px;
            text-align: center;
        }
        .summary-card.total { background: #e7f3ff; color: #023A8D; }
        .summary-card.passed { background: #d4edda; color: #155724; }
        .summary-card.failed { background: #f8d7da; color: #721c24; }
        .summary-card.warning { background: #fff3cd; color: #856404; }
        .summary-card h3 {
            margin: 0 0 5px 0;
            font-size: 2em;
        }
        .summary-card p {
            margin: 0;
            font-weight: 600;
        }
        .category-header {
            background: #023A8D;
            color: white;
            padding: 10px 15px;
            margin: 20px 0 10px 0;
            border-radius: 4px;
            font-weight: 600;
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
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .test-item.failed .test-details {
            color: #721c24;
            font-weight: 500;
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
        .test-data {
            background: #f8f9fa;
            border-left: 4px solid #023A8D;
            padding: 15px;
            margin: 20px 0;
            border-radius: 4px;
        }
        .test-data code {
            background: #e9ecef;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
            display: block;
            margin: 10px 0;
            white-space: pre-wrap;
            word-break: break-all;
            font-size: 0.9em;
        }
        .test-data a {
            color: #023A8D;
            text-decoration: none;
            font-weight: 600;
        }
        .test-data a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Validação Integração Gateway EFI</h1>
        <p>Este script valida completamente a integração com o gateway EFI (Gerencianet).</p>
        
        <div class="summary">
            <div class="summary-card total">
                <h3><?= $totalTests ?></h3>
                <p>Total de Testes</p>
            </div>
            <div class="summary-card passed">
                <h3><?= $passedTests ?></h3>
                <p>✅ Passou</p>
            </div>
            <div class="summary-card failed">
                <h3><?= $failedTests ?></h3>
                <p>❌ Falhou</p>
            </div>
            <div class="summary-card warning">
                <h3><?= $warningTests ?></h3>
                <p>⚠️ Aviso</p>
            </div>
        </div>
    </div>
    
    <div class="container">
        <?php
        $currentCategory = '';
        foreach ($results as $result):
            if ($currentCategory !== $result['category']):
                $currentCategory = $result['category'];
        ?>
            <div class="category-header"><?= htmlspecialchars($currentCategory) ?></div>
        <?php endif; ?>
            <div class="test-item <?= strtolower(str_replace(['✅ ', '❌ ', '⚠️ ', '⏭️ '], '', $result['status'])) ?>">
                <div class="test-name"><?= htmlspecialchars($result['test']) ?></div>
                <div class="test-status"><?= htmlspecialchars($result['status']) ?></div>
                <div class="test-details"><?= htmlspecialchars($result['details']) ?></div>
            </div>
        <?php endforeach; ?>
    </div>
    
    <?php if (!empty($testData)): ?>
        <div class="container test-data">
            <h3>📊 Dados dos Testes</h3>
            
            <?php if (isset($testData['access_token'])): ?>
                <p><strong>Access Token:</strong></p>
                <code><?= htmlspecialchars(substr($testData['access_token'], 0, 50)) ?>...</code>
            <?php endif; ?>
            
            <?php if (isset($testData['charge'])): ?>
                <p><strong>Cobrança Criada:</strong></p>
                <code>Charge ID: <?= htmlspecialchars($testData['charge']['charge_id'] ?? 'N/A') ?>
Status: <?= htmlspecialchars($testData['charge']['status'] ?? 'N/A') ?>
<?php if (!empty($testData['charge']['payment_url'])): ?>
Payment URL: <?= htmlspecialchars($testData['charge']['payment_url']) ?>
<a href="<?= htmlspecialchars($testData['charge']['payment_url']) ?>" target="_blank">🔗 Abrir link</a>
<?php endif; ?></code>
            <?php endif; ?>
            
            <?php if (isset($testData['charge_status'])): ?>
                <p><strong>Status da Cobrança:</strong></p>
                <code><?= htmlspecialchars(json_encode($testData['charge_status'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code>
            <?php endif; ?>
            
            <?php if (isset($testData['sync'])): ?>
                <p><strong>Resultado da Sincronização:</strong></p>
                <code><?= htmlspecialchars(json_encode($testData['sync'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></code>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!$hasError): ?>
        <div class="container">
            <h2>🧪 Testes Adicionais</h2>
            
            <form method="GET" action="?action=test_charge">
                <div class="form-group">
                    <label>Testar Criação de Cobrança</label>
                    <p style="color: #666; font-size: 0.9em;">
                        Digite o ID de uma matrícula com saldo devedor para testar a criação de cobrança.
                    </p>
                    <input type="number" name="enrollment_id" placeholder="ID da matrícula" required>
                    <button type="submit" class="btn">Testar Criação de Cobrança</button>
                </div>
            </form>
            
            <form method="GET" action="?action=test_status">
                <div class="form-group">
                    <label>Testar Consulta de Status</label>
                    <p style="color: #666; font-size: 0.9em;">
                        Digite o Charge ID de uma cobrança existente para consultar seu status.
                    </p>
                    <input type="text" name="charge_id" placeholder="Charge ID" required>
                    <button type="submit" class="btn">Consultar Status</button>
                </div>
            </form>
            
            <form method="GET" action="?action=test_sync">
                <div class="form-group">
                    <label>Testar Sincronização</label>
                    <p style="color: #666; font-size: 0.9em;">
                        Digite o ID de uma matrícula com cobrança gerada para testar a sincronização.
                    </p>
                    <input type="number" name="enrollment_id" placeholder="ID da matrícula" required>
                    <button type="submit" class="btn">Testar Sincronização</button>
                </div>
            </form>
        </div>
    <?php endif; ?>
    
    <?php if ($hasError): ?>
        <div class="container">
            <h2>🔧 Diagnóstico Adicional</h2>
            <?php
            // Verificar se o erro é de autenticação (401)
            $isAuthError = false;
            foreach ($results as $result) {
                if ($result['category'] === 'Autenticação' && strpos($result['status'], '❌') !== false) {
                    if (strpos($result['details'], '401') !== false || strpos($result['details'], 'Invalid or inactive credentials') !== false) {
                        $isAuthError = true;
                        break;
                    }
                }
            }
            ?>
            
            <?php if ($isAuthError): ?>
                <p><strong>Erro 401 detectado:</strong> Problema com credenciais (CLIENT_ID ou CLIENT_SECRET).</p>
                <p>
                    <a href="diagnostico_credenciais_efi.php" class="btn" target="_blank">🔍 Diagnosticar Credenciais</a>
                </p>
            <?php endif; ?>
            
            <?php if ($certPath): ?>
                <p>Se o problema persistir, use o script de diagnóstico do certificado:</p>
                <p>
                    <a href="diagnostico_certificado.php" class="btn" target="_blank">🔍 Diagnosticar Certificado</a>
                </p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div class="container">
        <p style="margin-top: 20px;">
            <a href="?" class="btn btn-secondary">🔄 Executar Validação Novamente</a>
            <a href="/" style="color: #023A8D; text-decoration: none; margin-left: 10px;">← Voltar ao sistema</a>
        </p>
    </div>
</body>
</html>
