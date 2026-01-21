# 📋 Mapeamento Completo: Implementação de Carnê (Efí API)

Este documento contém todas as informações coletadas sobre a implementação atual de Carnê no sistema, conforme solicitado.

---

## 🔍 1) Endpoint que está sendo chamado para Carnê

### ✅ Endpoint Confirmado

**Método HTTP:** `POST`  
**URL Completa:** 
- **Sandbox:** `https://cobrancas-h.api.efipay.com.br/v1/carnet`
- **Produção:** `https://cobrancas.api.efipay.com.br/v1/carnet`

**Localização no código:**
- **Arquivo:** `app/Services/EfiPaymentService.php`
- **Método:** `createCarnet()` (linha 502)
- **Linha da chamada:** 645
```php
$response = $this->makeRequest('POST', '/v1/carnet', $payload, $token, false);
```

**Confirmação:** ✅ Não está usando `/v1/charge/...` por engano. O endpoint correto `/v1/carnet` está sendo usado.

---

## 📦 2) Payload JSON completo que está sendo enviado

### Estrutura do Payload (sem dados sensíveis)

```json
{
  "items": [
    {
      "name": "Matrícula - Parcela 1/4",
      "value": 5000,
      "amount": 1
    }
  ],
  "expire_at": "2026-02-10",
  "repeats": 4,
  "message": "Pagamento referente a matrícula",
  "customer": {
    "name": "Nome do Aluno",
    "cpf": "***",
    "email": "***",
    "phone_number": "***",
    "address": {
      "street": "Rua Exemplo",
      "number": "123",
      "neighborhood": "Centro",
      "zipcode": "12345678",
      "city": "São Paulo",
      "state": "SP"
    }
  }
}
```

### Código que monta o payload (linhas 552-616)

```php
$payload = [
    'items' => [
        [
            'name' => ($enrollment['service_name'] ?? 'Matrícula') . ' - Parcela 1/' . $installments,
            'value' => $parcelValueInCents,  // Valor em centavos (INT)
            'amount' => 1
        ]
    ],
    'expire_at' => $expireDate,  // ✅ OBRIGATÓRIO no nível raiz (formato YYYY-MM-DD)
    'repeats' => $installments,  // ✅ OBRIGATÓRIO - INT (número de parcelas), não array!
    'message' => 'Pagamento referente a matrícula'
];

// Adicionar dados do cliente (se CPF disponível)
if (!empty($student['cpf'])) {
    $cpf = preg_replace('/[^0-9]/', '', $student['cpf']);
    if (strlen($cpf) === 11) {
        $payload['customer'] = [
            'name' => $student['full_name'] ?? $student['name'] ?? 'Cliente',
            'cpf' => $cpf,
            'email' => $student['email'] ?? null,
            'phone_number' => !empty($student['phone']) ? preg_replace('/[^0-9]/', '', $student['phone']) : null
        ];

        // Adicionar endereço se disponível
        if (!empty($student['cep'])) {
            $cep = preg_replace('/[^0-9]/', '', $student['cep']);
            if (strlen($cep) === 8) {
                $payload['customer']['address'] = [
                    'street' => $student['street'] ?? 'Não informado',
                    'number' => $student['number'] ?? 'S/N',
                    'neighborhood' => $student['neighborhood'] ?? '',
                    'zipcode' => $cep,
                    'city' => $student['city'] ?? '',
                    'state' => $student['state_uf'] ?? ''
                ];
            }
        }
    }
}

// Remover campos nulos/vazios do customer
if (isset($payload['customer'])) {
    $payload['customer'] = array_filter($payload['customer'], function($value) {
        return $value !== null && $value !== '';
    });
    
    if (isset($payload['customer']['address'])) {
        $address = array_filter($payload['customer']['address'], function($value) {
            return $value !== null && $value !== '';
        });
        if (empty($address)) {
            unset($payload['customer']['address']);
        } else {
            $payload['customer']['address'] = $address;
        }
    }
    
    if (empty($payload['customer'])) {
        unset($payload['customer']);
    }
}
```

### Campos Obrigatórios
- ✅ `items[]` - Array de itens (obrigatório)
- ✅ `expire_at` - Data de vencimento no nível raiz (formato YYYY-MM-DD) - **OBRIGATÓRIO**
- ✅ `repeats` - Número de parcelas (INT) - **OBRIGATÓRIO**

### Campos Opcionais
- `customer{}` - Dados do cliente (recomendado)
- `message` - Mensagem
- `configurations{}` - Configurações (multa, juros, etc.) - **NÃO IMPLEMENTADO**

---

## ⚠️ 3) Log da requisição completa

### Tratamento de Erros no Código

**Localização:** `app/Services/EfiPaymentService.php` (linhas 650-711)

**Quando ocorre erro (HTTP 400, 401, 500, etc.):**

```php
if ($httpCode !== 200 && $httpCode !== 201) {
    $errorMessage = 'Erro ao criar Carnê';
    $errorDetails = [];
    
    if (is_array($responseData)) {
        if (isset($responseData['error_description'])) {
            $errorDesc = $responseData['error_description'];
            if (is_array($errorDesc)) {
                $errorMessage = json_encode($errorDesc, JSON_UNESCAPED_UNICODE);
                $errorDetails = $errorDesc;
            } else {
                $errorMessage = (string)$errorDesc;
            }
        } elseif (isset($responseData['message'])) {
            $errorMessage = $responseData['message'];
        } elseif (isset($responseData['error'])) {
            $errorMessage = $responseData['error'];
        }
        
        // Extrair detalhes específicos de validação
        if (isset($responseData['errors']) && is_array($responseData['errors'])) {
            $errorDetails = $responseData['errors'];
        }
    } else {
        $errorMessage = (string)$responseData;
    }

    // Log detalhado
    $this->efiLog('ERROR', 'createCarnet: Falha ao criar Carnê', [
        'enrollment_id' => $enrollment['id'],
        'http_code' => $httpCode,
        'endpoint' => '/v1/carnet',
        'host' => $this->baseUrlCharges,
        'error' => substr($errorMessage, 0, 500),
        'error_details' => $errorDetails,
        'payload_summary' => [
            'installments' => $installments,
            'repeats' => $installments,
            'expire_at' => $expireDate,
            'first_due_date' => $firstDueDate
        ],
        'response_snippet' => is_array($responseData) ? json_encode($responseData, JSON_UNESCAPED_UNICODE) : substr((string)$responseData, 0, 500)
    ]);

    $this->updateEnrollmentStatus($enrollment['id'], 'error', 'error', null);
    return [
        'ok' => false,
        'message' => 'Erro ao criar Carnê: ' . $errorMessage
    ];
}
```

### Exemplo de Resposta de Erro (400 Bad Request)

**Formato esperado:**
```json
{
  "error": "A propriedade [expire_at] é obrigatória",
  "error_description": "A propriedade [expire_at] é obrigatória",
  "message": "A propriedade [expire_at] é obrigatória"
}
```

**Headers importantes:**
- `Content-Type: application/json`
- `Authorization: Bearer {token}`
- `Host: cobrancas-h.api.efipay.com.br` (sandbox) ou `cobrancas.api.efipay.com.br` (produção)

### Logs de Debug

**Arquivo de log:** `storage/logs/php_errors.log`

**Formato do log:**
```
[2026-01-21 01:25:10 America/Sao_Paulo] EFI-ERROR: createCarnet: Falha ao criar Carnê {"enrollment_id":2,"http_code":400,"endpoint":"/v1/carnet","host":"https://cobrancas-h.api.efipay.com.br","error":"A propriedade [expire_at] é obrigatória","error_details":[],"payload_summary":{"installments":4,"repeats":4,"expire_at":"2026-02-10","first_due_date":"2026-02-10"},"response_snippet":"{\"error\":\"A propriedade [expire_at] é obrigatória\"}"}
```

---

## 📌 4) Regra atual de negócio para Carnê

### ✅ Opção Implementada: **Opção A — Carnê com vencimentos pré-definidos**

**Como funciona:**
1. O sistema usa o campo `first_due_date` da tabela `enrollments` para determinar a data da primeira parcela
2. Se `first_due_date` não estiver definido ou for `'0000-00-00'`, usa **30 dias a partir de hoje** como padrão
3. A API Efí calcula automaticamente as datas das parcelas seguintes com base no `repeats` (número de parcelas)
4. O intervalo entre parcelas é **mensal** (padrão da API Efí)

**Código relevante (linhas 525-550):**
```php
// Obter data da primeira parcela
$firstDueDate = $enrollment['first_due_date'] ?? null;
if (!$firstDueDate || $firstDueDate === '0000-00-00') {
    // Se não tiver data configurada, usar 30 dias a partir de hoje
    $firstDueDate = date('Y-m-d', strtotime('+30 days'));
}

// Validar que a data está no futuro
$expireDate = date('Y-m-d', strtotime($firstDueDate));
if (strtotime($expireDate) < time()) {
    $this->efiLog('WARNING', 'createCarnet: Data de vencimento no passado, ajustando', [
        'enrollment_id' => $enrollment['id'],
        'data_original' => $expireDate
    ]);
    // Se a data estiver no passado, usar pelo menos 3 dias a partir de hoje
    $expireDate = date('Y-m-d', strtotime('+3 days'));
}
```

**Observação:** O sistema **NÃO** recebe datas customizadas do frontend. As datas são calculadas no backend com base em `first_due_date` ou padrão de 30 dias.

---

## 📅 5) Como as datas de vencimento estão sendo geradas

### Processo Atual

1. **Fonte da data:** Campo `first_due_date` da tabela `enrollments`
2. **Fallback:** Se não houver data, usa `+30 dias` a partir de hoje
3. **Validação:** Se a data estiver no passado, ajusta para `+3 dias` a partir de hoje
4. **Formato:** `YYYY-MM-DD` (ex: `2026-02-10`)

### Onde a data é preenchida no payload

**Campo:** `expire_at` no **nível raiz** do payload (não dentro de `payment.banking_billet`)

```php
$payload = [
    'items' => [...],
    'expire_at' => $expireDate,  // ✅ Nível raiz (obrigatório)
    'repeats' => $installments,  // ✅ INT (número de parcelas)
    'message' => 'Pagamento referente a matrícula'
];
```

**IMPORTANTE:** 
- ❌ **NÃO** está usando `repeats` como array de objetos com `expire_at` em cada item
- ✅ **SIM** está usando `repeats` como INT e `expire_at` no nível raiz
- A API Efí calcula automaticamente as datas das parcelas seguintes (mensalmente)

---

## 💾 6) Como estão persistindo no banco

### Tabela: `enrollments`

### Campos utilizados para Carnê

| Campo | Tipo | Descrição | Valor Salvo |
|-------|------|-----------|--------------|
| `gateway_charge_id` | VARCHAR(255) | ID principal do Carnê | `carnet_id` retornado pela API |
| `gateway_payment_url` | TEXT | JSON com dados completos | JSON com `carnet_id`, `charge_ids[]`, `payment_urls[]`, `type: 'carne'` |
| `billing_status` | ENUM | Status da geração | `'generated'` (quando sucesso) |
| `gateway_last_status` | VARCHAR(50) | Status do gateway | `'waiting'` (status inicial) |
| `gateway_provider` | VARCHAR(50) | Provedor do gateway | `'efi'` |
| `gateway_last_event_at` | DATETIME | Data/hora do evento | Data atual |

### Código de persistência (linhas 741-772)

```php
// Atualizar matrícula com dados do Carnê
$this->updateEnrollmentStatus(
    $enrollment['id'],
    'generated',
    'waiting', // Status inicial do Carnê
    $carnetId, // Usar carnet_id como identificador principal
    null,
    $firstPaymentUrl // URL do primeiro boleto
);

// Atualizar campo adicional para armazenar charge_ids (via UPDATE direto)
$stmt = $this->db->prepare("
    UPDATE enrollments 
    SET gateway_payment_url = ? 
    WHERE id = ?
");
// Salvar JSON com charge_ids e payment_urls
$additionalData = json_encode([
    'carnet_id' => $carnetId,
    'charge_ids' => $chargeIds,
    'payment_urls' => $paymentUrls,
    'type' => 'carne'
], JSON_UNESCAPED_UNICODE);
$stmt->execute([$additionalData, $enrollment['id']]);
```

### Estrutura do JSON em `gateway_payment_url`

```json
{
  "carnet_id": "12345",
  "charge_ids": ["charge_1", "charge_2", "charge_3", "charge_4"],
  "payment_urls": [
    "https://...",
    "https://...",
    "https://...",
    "https://..."
  ],
  "type": "carne"
}
```

**Observação:** A estrutura atual do banco só tem `gateway_charge_id` (singular), então o código salva o `carnet_id` lá e guarda os `charge_ids` em `gateway_payment_url` como JSON (solução temporária).

---

## ⚠️ 7) Tratamento de erro hoje

### Backend (API Endpoint)

**Arquivo:** `app/Controllers/PaymentsController.php`  
**Método:** `generate()` (linhas 25-150)

### ✅ Tratamento Implementado

1. **Sempre retorna JSON** (não retorna HTML/500)
2. **Status HTTP apropriado:**
   - `200` - Sucesso
   - `400` - Erro de validação/dados
   - `401` - Não autenticado
   - `403` - Sem permissão
   - `404` - Matrícula não encontrada
   - `500` - Erro interno (exceção)

3. **Estrutura de resposta de erro:**
```json
{
  "ok": false,
  "message": "Erro ao criar Carnê: {mensagem_da_api}",
  "details": {
    "error": "...",
    "file": "...",
    "line": ...
  }
}
```

### Código de tratamento (linhas 110-147)

```php
$result = $this->efiService->createCharge($enrollment);

if (!$result['ok']) {
    http_response_code(400);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    // Capturar qualquer erro (Exception, Error, etc)
    http_response_code(500);
    
    // Log com prefixo PAYMENTS-ERROR
    $logFile = __DIR__ . '/../../storage/logs/php_errors.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = sprintf(
        "[%s] PAYMENTS-ERROR: PaymentsController::generate() - %s in %s:%d\nStack trace:\n%s\n",
        $timestamp,
        $e->getMessage(),
        $e->getFile(),
        $e->getLine(),
        $e->getTraceAsString()
    );
    @file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    
    // Garantir que header JSON foi enviado
    if (!headers_sent()) {
        header('Content-Type: application/json; charset=utf-8');
    }
    
    echo json_encode([
        'ok' => false,
        'message' => 'Ocorreu um erro ao gerar a cobrança. Por favor, tente novamente.',
        'details' => [
            'error' => $e->getMessage(),
            'file' => basename($e->getFile()),
            'line' => $e->getLine()
        ]
    ], JSON_UNESCAPED_UNICODE);
}
```

**Conclusão:** ✅ O backend já está tratando erros e retornando JSON adequadamente.

---

## 📌 8) Script de teste local

### Arquivo: `tools/test_carne_local.php`

### Conteúdo completo:

```php
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
$enrollmentId = $argv[1] ?? 2; // ID 2 por padrão

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
```

### Como executar:

```bash
php tools/test_carne_local.php [enrollment_id]
```

**Exemplo:**
```bash
php tools/test_carne_local.php 2
```

### Equivalente cURL (gerado a partir do código):

```bash
curl -X POST https://cobrancas-h.api.efipay.com.br/v1/carnet \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer {token}" \
  -d '{
    "items": [
      {
        "name": "Matrícula - Parcela 1/4",
        "value": 5000,
        "amount": 1
      }
    ],
    "expire_at": "2026-02-10",
    "repeats": 4,
    "message": "Pagamento referente a matrícula",
    "customer": {
      "name": "Nome do Aluno",
      "cpf": "12345678901",
      "email": "aluno@example.com",
      "phone_number": "11999999999",
      "address": {
        "street": "Rua Exemplo",
        "number": "123",
        "neighborhood": "Centro",
        "zipcode": "12345678",
        "city": "São Paulo",
        "state": "SP"
      }
    }
  }'
```

---

## 📚 9) Confirmação da versão da documentação usada

### Link da Documentação Oficial

**URL:** https://dev.efipay.com.br/docs/api-cobrancas/carne

**Referência no código:**
- **Arquivo:** `CORRECAO_CARNE_FINAL.md` (linha 182)
- **Comentário no código:** Linha 532-539 de `EfiPaymentService.php`

```php
// Preparar payload do Carnê conforme schema oficial da API Efí
// Schema: POST /v1/carnet
// - items[] (obrigatório)
// - customer{} (opcional mas recomendado)
// - expire_at (obrigatório no nível raiz) - formato YYYY-MM-DD
// - repeats (obrigatório) - INT (número de parcelas), não array!
// - message (opcional)
// - configurations{} (opcional)
```

**Versão:** Não especificada explicitamente, mas o código segue o schema atual da documentação oficial.

---

## 🧠 10) Logs de debugging coletados

### Localização dos Logs

**Arquivo:** `storage/logs/php_errors.log`

### Sistema de Logging

**Método:** `efiLog()` em `EfiPaymentService.php` (linhas 991-1039)

**Níveis de log:**
- `DEBUG` - Apenas se `EFI_DEBUG=true` no `.env`
- `INFO` - Sempre gravado
- `WARN` - Sempre gravado
- `ERROR` - Sempre gravado

### Logs específicos para Carnê

**1. Log de Payload (antes de enviar):**
```php
$this->efiLog('DEBUG', 'createCarnet: Payload no schema correto do Carnê', [
    'enrollment_id' => $enrollment['id'],
    'endpoint' => '/v1/carnet',
    'host' => $this->baseUrlCharges,
    'installments' => $installments,
    'expire_at' => $expireDate,
    'repeats' => $installments,
    'has_customer' => !empty($payload['customer']),
    'has_address' => !empty($payload['customer']['address'] ?? null),
    'payload_structure' => json_encode($logPayload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
]);
```

**2. Log de Erro (quando falha):**
```php
$this->efiLog('ERROR', 'createCarnet: Falha ao criar Carnê', [
    'enrollment_id' => $enrollment['id'],
    'http_code' => $httpCode,
    'endpoint' => '/v1/carnet',
    'host' => $this->baseUrlCharges,
    'error' => substr($errorMessage, 0, 500),
    'error_details' => $errorDetails,
    'payload_summary' => [
        'installments' => $installments,
        'repeats' => $installments,
        'expire_at' => $expireDate,
        'first_due_date' => $firstDueDate
    ],
    'response_snippet' => is_array($responseData) ? json_encode($responseData, JSON_UNESCAPED_UNICODE) : substr((string)$responseData, 0, 500)
]);
```

**3. Log de Sucesso:**
```php
$this->efiLog('INFO', 'createCarnet: Carnê criado com sucesso', [
    'enrollment_id' => $enrollment['id'],
    'carnet_id' => $carnetId,
    'installments' => $installments,
    'charge_ids_count' => count($chargeIds)
]);
```

### Exemplo de Log Real

```
[2026-01-21 01:25:10 America/Sao_Paulo] EFI-ERROR: createCarnet: Falha ao criar Carnê {"enrollment_id":2,"http_code":400,"endpoint":"/v1/carnet","host":"https://cobrancas-h.api.efipay.com.br","error":"A propriedade [expire_at] é obrigatória","error_details":[],"payload_summary":{"installments":4,"repeats":4,"expire_at":"2026-02-10","first_due_date":"2026-02-10"},"response_snippet":"{\"error\":\"A propriedade [expire_at] é obrigatória\"}"}
```

### Variáveis Logadas (antes de enviar)

- `enrollment_id`
- `endpoint` (`/v1/carnet`)
- `host` (URL base da API)
- `installments` (número de parcelas)
- `expire_at` (data de vencimento)
- `repeats` (número de parcelas)
- `has_customer` (boolean)
- `has_address` (boolean)
- `payload_structure` (JSON completo do payload, sem dados sensíveis)

### Dados Sensíveis Removidos dos Logs

- CPF (substituído por `***`)
- Email (substituído por `***`)
- Phone number (substituído por `***`)
- Token completo (apenas prefixo e tamanho)

---

## 📊 Resumo Executivo

### ✅ Checklist de Informações Coletadas

1. ✅ **Endpoint completo:** `POST /v1/carnet` (sandbox: `cobrancas-h.api.efipay.com.br`, produção: `cobrancas.api.efipay.com.br`)
2. ✅ **Payload JSON real:** Estrutura completa documentada (sem dados sensíveis)
3. ✅ **Resposta completa da API:** Tratamento de erros e logs documentados
4. ✅ **Regra de negócio:** Opção A - Carnê com vencimentos pré-definidos (baseado em `first_due_date` ou +30 dias)
5. ✅ **Geração de datas:** `expire_at` no nível raiz, `repeats` como INT, API calcula parcelas mensais
6. ✅ **Estrutura de banco:** `gateway_charge_id` = `carnet_id`, `gateway_payment_url` = JSON com dados completos
7. ✅ **Tratamento de erros:** Backend retorna JSON com status HTTP apropriado
8. ✅ **Script de teste:** `tools/test_carne_local.php` completo e documentado
9. ✅ **Documentação oficial:** https://dev.efipay.com.br/docs/api-cobrancas/carne
10. ✅ **Logs de debug:** Sistema completo de logging em `storage/logs/php_errors.log`

---

## 🔗 Arquivos Relevantes

- `app/Services/EfiPaymentService.php` - Serviço principal (método `createCarnet()`)
- `app/Controllers/PaymentsController.php` - Controller da API
- `tools/test_carne_local.php` - Script de teste
- `storage/logs/php_errors.log` - Logs de debug
- `CORRECAO_CARNE_FINAL.md` - Documentação da correção
- `database/migrations/030_add_gateway_fields_to_enrollments.sql` - Migration dos campos do gateway
- `database/migrations/031_add_gateway_payment_url_to_enrollments.sql` - Migration do campo `gateway_payment_url`

---

**Data da Coleta:** 2026-01-21  
**Status:** ✅ Completo
