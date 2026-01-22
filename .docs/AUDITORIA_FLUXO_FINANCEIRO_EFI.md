# AUDITORIA COMPLETA - FLUXO FINANCEIRO → GERAR COBRANÇA (EFI)

**Data da Auditoria:** 2024  
**Objetivo:** Mapear estado atual e identificar gaps no fluxo de geração de cobrança EFI

---

## A) COMO ESTÁ HOJE (Fluxo Real Encontrado)

### 1. MAPA DO FLUXO ATUAL

#### 1.1. Onde nasce o "financeiro" quando cria matrícula + serviço

**Arquivo:** `app/Controllers/AlunosController.php`  
**Função:** `criarMatricula()` (linha ~398)  
**Endpoint:** `POST /alunos/{id}/matricular` (rota linha 62 em `app/routes/web.php`)

**Fluxo:**
1. Usuário acessa `/alunos/{id}/matricular` (GET)
2. Preenche formulário com:
   - Serviço (service_id)
   - Preço base, desconto, extra → calcula `final_price`
   - Entrada (entry_amount) → calcula `outstanding_amount = final_price - entry_amount`
   - Parcelas (installments)
   - Datas de vencimento
3. Submete formulário (POST)
4. Controller valida e cria registro em `enrollments`:
   - `final_price` = base_price - discount_value + extra_value
   - `outstanding_amount` = final_price - entry_amount (se entry_amount > 0)
   - `billing_status` = 'draft' (Rascunho)
   - `financial_status` = 'em_dia' (padrão)
   - `installments`, `first_due_date`, `down_payment_amount`, etc.

**Cálculo do Saldo Devedor:**
- **Arquivo:** `app/Controllers/AlunosController.php` linha 434
- **Fórmula:** `$outstandingAmount = $entryAmount > 0 ? max(0, $finalPrice - $entryAmount) : $finalPrice;`
- **Armazenado em:** `enrollments.outstanding_amount` (DECIMAL 10,2)

#### 1.2. Onde o botão "Gerar Cobrança Efi" aponta

**Arquivo:** `app/Views/alunos/matricula_show.php`  
**Linha:** 410-412  
**Função JavaScript:** `gerarCobrancaEfi()` (linha 532)

**Rota/Endpoint:**
- **URL:** `POST /api/payments/generate`
- **Controller:** `app/Controllers/PaymentsController.php`
- **Método:** `generate()` (linha 25)
- **Rota definida em:** `app/routes/web.php` linha 187

**Fluxo do Botão:**
1. Botão aparece apenas se:
   - `installments` > 0 (tem parcelas definidas)
   - `billing_status` = 'draft', 'ready' ou 'error'
   - NÃO existe cobrança ativa (`gateway_charge_id` vazio OU status = 'canceled'/'expired'/'error')
2. Ao clicar, chama `gerarCobrancaEfi()` via AJAX
3. Envia `{enrollment_id: X}` para `/api/payments/generate`
4. Recebe resposta JSON e atualiza UI

#### 1.3. Onde calcula saldo devedor, parcelas e vencimento

**Saldo Devedor:**
- **Criação:** `app/Controllers/AlunosController.php:434`
- **Exibição/Edição:** `app/Views/alunos/matricula_show.php:442-465` (JavaScript `calculateOutstanding()`)
- **Uso na cobrança:** `app/Services/EfiPaymentService.php:54` → `$outstandingAmount = floatval($enrollment['outstanding_amount'] ?? $enrollment['final_price'] ?? 0);`

**Parcelas:**
- **Armazenado em:** `enrollments.installments` (INT, 1-12)
- **Definido em:** `app/Controllers/AlunosController.php:437-450`
- **Usado em:** `app/Services/EfiPaymentService.php:112` → `$installments = intval($enrollment['installments'] ?? 1);`

**Vencimento 1ª Parcela:**
- **Armazenado em:** `enrollments.first_due_date` (DATE)
- **Definido em:** `app/Controllers/AlunosController.php:454-458`
- **Exibido em:** `app/Views/alunos/matricula_show.php:250-260`

#### 1.4. Onde o status "Rascunho" é setado e outros status

**Status "Rascunho" (draft):**
- **Setado em:** `app/Controllers/AlunosController.php:531` → `'billing_status' => 'draft'`
- **Enum definido em:** Migration 009 → `enum('draft','ready','generated','error')`
- **Valor padrão:** `'draft'` (NOT NULL DEFAULT 'draft')

**Outros Status Existentes:**

| Status | Significado | Onde é setado |
|--------|-------------|---------------|
| `draft` | Rascunho (pronto para gerar) | Criação de matrícula |
| `ready` | Pronto (intermediário) | Mapeamento de status do gateway |
| `generated` | Cobrança gerada | `EfiPaymentService::createCharge()` linha 200 |
| `error` | Erro na geração | `EfiPaymentService::createCharge()` linha 175 ou `mapGatewayStatusToBillingStatus()` linha 429 |

**Status do Gateway (`gateway_last_status`):**
- Armazenado em: `enrollments.gateway_last_status` (VARCHAR 50)
- Mapeamento: `EfiPaymentService::mapGatewayStatusToBillingStatus()` linha 417
- Status mapeados:
  - `paid`, `settled`, `waiting` → `billing_status = 'generated'`
  - `unpaid`, `refunded`, `canceled`, `expired` → `billing_status = 'error'`
  - Outros → `billing_status = 'ready'`

---

### 2. INVENTÁRIO DE INTEGRAÇÃO EFI

#### 2.1. Classes/Arquivos que cuidam da EFI

**Service Principal:**
- **Arquivo:** `app/Services/EfiPaymentService.php` (465 linhas)
- **Métodos públicos:**
  - `createCharge($enrollment)` - Cria cobrança na EFI
  - `parseWebhook($requestPayload)` - Processa webhook
  - `getChargeStatus($chargeId)` - Consulta status

**Controller:**
- **Arquivo:** `app/Controllers/PaymentsController.php` (126 linhas)
- **Métodos:**
  - `generate()` - Endpoint de geração
  - `webhookEfi()` - Endpoint de webhook

**Rotas:**
- **Arquivo:** `app/routes/web.php`
- **Linhas:** 187-188
  - `POST /api/payments/generate` (autenticado)
  - `POST /api/payments/webhook/efi` (público)

#### 2.2. Como é obtido o token

**Método:** `EfiPaymentService::getAccessToken()` (linha 312)

**Grant Type:** `client_credentials` (OAuth2)

**Endpoint:** 
- Sandbox: `https://sandbox.gerencianet.com.br/oauth/token`
- Produção: `https://apis.gerencianet.com.br/oauth/token` (sem `/v1` e com "apis" no plural)

**Autenticação:**
- Basic Auth: `base64_encode(client_id:client_secret)`
- Header: `Authorization: Basic {base64}`

**Cache do Token:**
- ❌ **NÃO IMPLEMENTADO** - Token é obtido a cada requisição
- **Problema:** Pode gerar múltiplas chamadas desnecessárias
- **Recomendação:** Implementar cache em memória/sessão com expiração

**Expiração:**
- Token retornado pela API tem `expires_in` (geralmente 3600s)
- Sistema não armazena nem valida expiração

#### 2.3. Endpoint/método da EFI usado para gerar cobrança

**Endpoint:** `POST /v1/charges`

**Método:** `EfiPaymentService::makeRequest()` (linha 353)

**Tipos de Cobrança Suportados:**

1. **PIX (à vista):**
   - Se `installments = 1` E `payment_method = 'pix'`
   - Payload: `{'payment': {'pix': []}}`
   - Retorna: `payment.pix.qr_code` (URL do QR Code)

2. **Boleto (à vista):**
   - Se `installments = 1` E `payment_method = 'boleto'`
   - Payload: `{'payment': {'banking_billet': []}}`
   - Retorna: `payment.banking_billet.link` (URL do boleto)

3. **Cartão Parcelado:**
   - Se `installments > 1`
   - Payload: `{'payment': {'credit_card': {'installments': N, 'billing_address': {...}}}}`
   - Requer endereço completo do aluno

**Código relevante:** `EfiPaymentService.php:143-166`

#### 2.4. Campos que a API exige e mapeamento de dados

**Campos Obrigatórios da API:**

1. **Items (produto/serviço):**
   - `items[0].name` → `$enrollment['service_name'] ?? 'Matrícula'`
   - `items[0].value` → `$outstandingAmount * 100` (centavos)
   - `items[0].amount` → `1`

2. **Customer (pagador):**
   - `customer.name` → `$student['full_name'] ?? $student['name']`
   - `customer.cpf` → `preg_replace('/[^0-9]/', '', $student['cpf'])` (11 dígitos)
   - `customer.email` → `$student['email']` (opcional)
   - `customer.phone_number` → `preg_replace('/[^0-9]/', '', $student['phone'])` (opcional)

3. **Billing Address (para cartão):**
   - `street`, `number`, `neighborhood`, `zipcode`, `city`, `state`
   - Mapeado de `students` via `Enrollment::findWithDetails()`

4. **Metadata:**
   - `metadata.enrollment_id`
   - `metadata.cfc_id`
   - `metadata.student_id`

**Código relevante:** `EfiPaymentService.php:111-141`

---

### 3. BANCO DE DADOS / MODELOS

#### 3.1. Tabelas que guardam dados financeiros

**Tabela Principal: `enrollments`**

**Campos de Matrícula:**
- `id` (PK)
- `student_id` (FK → students)
- `service_id` (FK → services)
- `cfc_id` (FK → cfcs)

**Campos Financeiros (Valores):**
- `base_price` (DECIMAL 10,2) - Preço base do serviço
- `discount_value` (DECIMAL 10,2) - Desconto aplicado
- `extra_value` (DECIMAL 10,2) - Valor extra
- `final_price` (DECIMAL 10,2) - Preço final (base - desconto + extra)
- `entry_amount` (DECIMAL 10,2) - Valor da entrada recebida
- `outstanding_amount` (DECIMAL 10,2) - **Saldo devedor** (final_price - entry_amount)

**Campos de Parcelamento:**
- `installments` (INT) - Número de parcelas (1-12)
- `down_payment_amount` (DECIMAL 10,2) - Valor da entrada (quando entrada_parcelas)
- `down_payment_due_date` (DATE) - Vencimento da entrada
- `first_due_date` (DATE) - Vencimento da 1ª parcela

**Campos de Status Financeiro:**
- `financial_status` (ENUM: 'em_dia','pendente','bloqueado') - Status financeiro interno
- `payment_method` (ENUM: 'pix','boleto','cartao','entrada_parcelas') - Forma de pagamento

**Campos de Gateway (Cobrança):**
- `gateway_provider` (VARCHAR 50) - Provedor ('efi', 'asaas', etc.)
- `gateway_charge_id` (VARCHAR 255) - **ID da cobrança no gateway**
- `gateway_last_status` (VARCHAR 50) - **Último status do gateway**
- `gateway_last_event_at` (DATETIME) - **Data/hora do último evento**
- `billing_status` (ENUM: 'draft','ready','generated','error') - **Status da geração de cobrança**

**Migrations Relacionadas:**
- `009_add_payment_plan_to_enrollments.sql` - Parcelamento + billing_status
- `010_add_entry_fields_to_enrollments.sql` - Entrada + outstanding_amount
- `030_add_gateway_fields_to_enrollments.sql` - Campos do gateway

#### 3.2. Verificação de colunas e tipos

**✅ COLUNAS EXISTENTES E ADEQUADAS:**

| Campo | Tipo | Status | Observação |
|-------|------|--------|------------|
| `gateway_provider` | VARCHAR(50) | ✅ OK | Suficiente para 'efi', 'asaas', etc. |
| `gateway_charge_id` | VARCHAR(255) | ✅ OK | Suficiente para IDs da EFI |
| `gateway_last_status` | VARCHAR(50) | ✅ OK | Suficiente para status |
| `gateway_last_event_at` | DATETIME | ✅ OK | Armazena timestamp |
| `billing_status` | ENUM | ✅ OK | Estados bem definidos |

**❌ COLUNAS FALTANDO (para funcionalidade completa):**

| Campo | Tipo Sugerido | Necessidade | Justificativa |
|-------|---------------|-------------|---------------|
| `gateway_payment_url` | TEXT | ⚠️ MÉDIA | Link de pagamento (PIX/Boleto) - atualmente não é salvo |
| `gateway_barcode` | VARCHAR(255) | ⚠️ BAIXA | Linha digitável do boleto (opcional) |
| `gateway_pix_qrcode` | TEXT | ⚠️ MÉDIA | QR Code PIX completo (opcional) |
| `gateway_pix_copy_paste` | TEXT | ⚠️ BAIXA | Código PIX copia-e-cola (opcional) |

**Observação:** O sistema atualmente retorna `payment_url` na resposta JSON, mas **não salva no banco**. Isso impede acesso posterior ao link sem consultar a API da EFI.

#### 3.3. Migrations pendentes ou inconsistências

**Migrations Identificadas:**
- ✅ `009_add_payment_plan_to_enrollments.sql` - Criada
- ✅ `010_add_entry_fields_to_enrollments.sql` - Criada
- ✅ `030_add_gateway_fields_to_enrollments.sql` - Criada

**⚠️ VERIFICAÇÃO NECESSÁRIA:**
- Executar `DESCRIBE enrollments` no MySQL para confirmar se todas as colunas existem
- Verificar se índices foram criados corretamente

**Script de Verificação:** `tools/check_enrollments_structure.php` (criado)

---

### 4. UI/UX NECESSÁRIA PARA O TESTE

#### 4.1. Escolha de forma de pagamento e parcelas

**Estado Atual:**

**✅ Forma de Pagamento:**
- **Onde:** `app/Views/alunos/matricular.php` (formulário de criação)
- **Campo:** `payment_method` (SELECT)
- **Opções:** 'pix', 'boleto', 'cartao', 'entrada_parcelas'
- **Status:** ✅ Já existe na criação de matrícula

**⚠️ PROBLEMA:** Na tela de **edição** (`matricula_show.php`), não há campo para alterar `payment_method` antes de gerar cobrança.

**✅ Parcelas:**
- **Onde:** `app/Views/alunos/matricular.php` (formulário de criação)
- **Campo:** `installments` (1-12)
- **Status:** ✅ Já existe na criação de matrícula

**⚠️ PROBLEMA:** Na tela de **edição**, não há campo para alterar `installments` antes de gerar cobrança.

#### 4.2. Solução mínima para teste

**Opção 1: Usar dados já salvos (RECOMENDADO PARA TESTE)**
- ✅ Sistema já usa `enrollment['payment_method']` e `enrollment['installments']`
- ✅ Se não existir, fallback: `payment_method = 'pix'`, `installments = 1`
- **Código:** `EfiPaymentService.php:160` → `$paymentMethod = $enrollment['payment_method'] ?? 'pix';`

**Opção 2: Adicionar campos na tela de edição (FUTURO)**
- Adicionar SELECT de `payment_method` em `matricula_show.php`
- Adicionar INPUT de `installments` em `matricula_show.php`
- Permitir alteração antes de gerar cobrança

**✅ FALLBACK ATUAL:**
- Se `installments > 1` → Cartão parcelado
- Se `installments = 1` → PIX (se `payment_method = 'pix'`) ou Boleto (se `payment_method = 'boleto'`)
- Se `payment_method` não definido → PIX (padrão)

#### 4.3. Bloqueio do botão "Gerar Cobrança Efi"

**Lógica Atual:** `app/Views/alunos/matricula_show.php:404-417`

**Condições para BLOQUEAR botão:**

1. ✅ **Sem saldo devedor:**
   - Verificação: `outstanding_amount <= 0` (no service, linha 55)
   - **PROBLEMA:** Botão não é desabilitado na UI se `outstanding_amount = 0`
   - **Solução:** Adicionar verificação JavaScript antes de mostrar botão

2. ✅ **Cobrança ativa existe:**
   - Verificação: `gateway_charge_id` não vazio E `billing_status = 'generated'` E `gateway_last_status` não é 'canceled'/'expired'/'error'
   - **Status:** ✅ Implementado corretamente

**Código atual:**
```php
$hasActiveCharge = !empty($enrollment['gateway_charge_id']) && 
                   $enrollment['billing_status'] === 'generated' &&
                   !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error']);

if (!empty($enrollment['installments']) && !$hasActiveCharge && ...) {
    // Mostra botão
}
```

**⚠️ GAPS IDENTIFICADOS:**
1. Não verifica se `outstanding_amount > 0` na UI
2. Não verifica se `installments` está definido (pode ser NULL)
3. Botão aparece mesmo se `outstanding_amount = 0` (validação só no backend)

---

### 5. IDEMPOTÊNCIA E REGRAS DE NEGÓCIO

#### 5.1. Chave idempotente

**Chave Atual:**
- `enrollment_id` + `gateway_charge_id` (se existir)

**Lógica:** `EfiPaymentService::createCharge()` linha 63-72

```php
if (!empty($enrollment['gateway_charge_id']) && 
    $enrollment['billing_status'] === 'generated' &&
    !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error'])) {
    return ['ok' => false, 'message' => 'Cobrança já existe'];
}
```

**✅ FUNCIONA CORRETAMENTE:**
- Se existe `gateway_charge_id` E status é 'generated' E status não é finalizado → bloqueia
- Se status é 'canceled'/'expired'/'error' → permite regerar

#### 5.2. Regras de negócio implementadas

**✅ Regras Implementadas:**

1. **Valor da cobrança:**
   - ✅ Sempre usa `outstanding_amount` (não `final_price`)
   - ✅ Converte para centavos (multiplica por 100)
   - ✅ Valida saldo > 0

2. **Parcelamento:**
   - ✅ Se `installments > 1` → cria cobrança parcelada
   - ✅ Se `installments = 1` → cria cobrança à vista (PIX ou Boleto)

3. **Idempotência:**
   - ✅ Verifica cobrança ativa antes de criar
   - ✅ Permite regerar se status = 'canceled'/'expired'/'error'

4. **Dados do pagador:**
   - ✅ Valida CPF (11 dígitos)
   - ✅ Limpa caracteres especiais
   - ✅ Inclui endereço para cartão

**⚠️ REGRAS FALTANDO:**

1. **Atualização de `financial_status`:**
   - ❌ Quando pagamento é confirmado (webhook 'paid'), `financial_status` não muda automaticamente
   - **Recomendação:** Implementar mapeamento: `paid` → `financial_status = 'em_dia'`

2. **Validação de CPF:**
   - ⚠️ Valida apenas formato (11 dígitos), não valida dígitos verificadores
   - **Risco:** CPF inválido pode gerar erro na API da EFI

3. **Validação de email:**
   - ⚠️ Não valida formato de email antes de enviar
   - **Risco:** Email inválido pode gerar erro na API

---

### 6. WEBHOOK / SINCRONIZAÇÃO DE STATUS

#### 6.1. Endpoint de webhook existente

**✅ Endpoint Implementado:**
- **URL:** `POST /api/payments/webhook/efi`
- **Controller:** `PaymentsController::webhookEfi()` (linha 92)
- **Rota:** `app/routes/web.php:188` (público, sem autenticação)

**Características:**
- ✅ Público (sem autenticação de sessão)
- ✅ Aceita JSON no body
- ✅ Fallback para `$_POST` se JSON não vier
- ✅ Sempre retorna HTTP 200 (evita retry infinito)

#### 6.2. Validação de segurança

**Assinatura HMAC-SHA256:**
- **Método:** `EfiPaymentService::validateWebhookSignature()` (linha 402)
- **Header esperado:** `X-GN-Signature`
- **Secret:** `EFI_WEBHOOK_SECRET` (variável de ambiente)
- **Status:** ✅ Implementado, mas **opcional** (só valida se `EFI_WEBHOOK_SECRET` configurado)

**⚠️ GAPS DE SEGURANÇA:**

1. **IP Allowlist:**
   - ❌ Não verifica IP de origem
   - **Risco:** Webhook pode ser chamado de qualquer IP
   - **Recomendação:** Adicionar verificação de IP (se EFI fornecer range)

2. **Rate Limiting:**
   - ❌ Não implementado
   - **Risco:** Ataque de força bruta
   - **Recomendação:** Implementar limite de requisições por IP

3. **Logging:**
   - ⚠️ Loga apenas erros (`error_log()`)
   - **Recomendação:** Logar todos os webhooks recebidos (para auditoria)

#### 6.3. Processamento do webhook

**Método:** `EfiPaymentService::parseWebhook()` (linha 219)

**Payload Esperado:**
```json
{
  "identifiers": {"charge_id": "123456"},
  "current": {"status": "paid"},
  "occurred_at": "2024-01-15T10:30:00Z"
}
```

**Fluxo:**
1. Valida assinatura (se configurado)
2. Extrai `charge_id` e `status`
3. Busca matrícula por `gateway_charge_id`
4. Mapeia status do gateway para `billing_status`
5. Atualiza `enrollments` com novos dados

**✅ IDEMPOTÊNCIA:**
- Se matrícula não encontrada, retorna sucesso mas não processa (evita erro 500)

#### 6.4. Mapeamento de estados

**Mapeamento Atual:** `EfiPaymentService::mapGatewayStatusToBillingStatus()` (linha 417)

| Status Gateway | billing_status | Observação |
|----------------|----------------|------------|
| `paid` | `generated` | ✅ Pagamento confirmado |
| `settled` | `generated` | ✅ Pagamento liquidado |
| `waiting` | `generated` | ✅ Aguardando pagamento |
| `unpaid` | `error` | ⚠️ Não pago (deveria ser diferente?) |
| `refunded` | `error` | ✅ Reembolsado |
| `canceled` | `error` | ✅ Cancelado |
| `expired` | `error` | ✅ Expirado |
| Outros | `ready` | ⚠️ Status intermediário |

**⚠️ PROBLEMA IDENTIFICADO:**
- `unpaid` é mapeado como `error`, mas pode ser apenas "aguardando pagamento"
- **Recomendação:** Criar status `pending` ou manter `waiting` para `unpaid`

#### 6.5. Atualização de `financial_status`

**❌ NÃO IMPLEMENTADO:**
- Quando webhook recebe `paid`, `financial_status` **não é atualizado**
- Sistema mantém `financial_status` manual

**Código atual:** `EfiPaymentService::updateEnrollmentStatus()` (linha 439)
- Atualiza apenas: `billing_status`, `gateway_last_status`, `gateway_last_event_at`
- **NÃO atualiza:** `financial_status`

**Recomendação:**
- Adicionar lógica para atualizar `financial_status` quando `paid`:
  - `paid` → `financial_status = 'em_dia'`
  - `expired` → `financial_status = 'pendente'` (se vencido)
  - `canceled` → manter atual (não alterar)

---

## B) O QUE ESTÁ FALTANDO / QUEBRADO

### ❌ PROBLEMAS CRÍTICOS

1. **Token não é cacheado**
   - Token OAuth é obtido a cada requisição
   - **Impacto:** Performance e rate limiting da API
   - **Solução:** Implementar cache com expiração

2. **Link de pagamento não é salvo no banco**
   - `payment_url` retornado pela API não é persistido
   - **Impacto:** Não é possível acessar link depois sem consultar API
   - **Solução:** Adicionar coluna `gateway_payment_url` (TEXT)

3. **`financial_status` não atualiza automaticamente**
   - Quando pagamento é confirmado, `financial_status` continua manual
   - **Impacto:** Status financeiro não reflete pagamento real
   - **Solução:** Implementar atualização automática no webhook

4. **Botão não verifica saldo devedor na UI**
   - Botão aparece mesmo se `outstanding_amount = 0`
   - **Impacto:** UX confusa (erro só aparece ao clicar)
   - **Solução:** Adicionar verificação JavaScript

### ⚠️ PROBLEMAS MÉDIOS

5. **Validação de CPF/Email fraca**
   - Não valida dígitos verificadores do CPF
   - Não valida formato de email
   - **Impacto:** Pode gerar erro na API da EFI
   - **Solução:** Adicionar validações

6. **Webhook sem IP allowlist**
   - Qualquer IP pode chamar webhook
   - **Impacto:** Risco de segurança (mitigado por assinatura)
   - **Solução:** Adicionar verificação de IP (se EFI fornecer)

7. **Mapeamento de status `unpaid`**
   - `unpaid` é tratado como `error`, mas pode ser apenas "aguardando"
   - **Impacto:** Confusão de status
   - **Solução:** Revisar mapeamento

### ℹ️ MELHORIAS RECOMENDADAS

8. **Logging de webhooks**
   - Apenas erros são logados
   - **Solução:** Logar todos os webhooks recebidos

9. **Campos de forma de pagamento na edição**
   - Não é possível alterar `payment_method` antes de gerar cobrança
   - **Solução:** Adicionar campos na tela de edição

10. **Rate limiting no webhook**
    - Não há proteção contra spam
    - **Solução:** Implementar rate limiting

---

## C) PLANO MÍNIMO PARA FICAR FUNCIONAL

### Passo 1: Verificar estrutura do banco
- [ ] Executar `DESCRIBE enrollments` no MySQL
- [ ] Confirmar que todas as colunas existem (gateway_*, billing_status, outstanding_amount)
- [ ] Se faltar, executar migrations pendentes

### Passo 2: Configurar variáveis de ambiente
- [ ] Verificar `.env` tem:
  - `EFI_CLIENT_ID`
  - `EFI_CLIENT_SECRET`
  - `EFI_SANDBOX=true` (para teste)
  - `EFI_WEBHOOK_SECRET` (opcional, mas recomendado)

### Passo 3: Corrigir validação do botão na UI
- [ ] Adicionar verificação JavaScript: `outstanding_amount > 0`
- [ ] Adicionar verificação: `installments > 0`
- [ ] Desabilitar botão se condições não atendidas

### Passo 4: Implementar cache de token (OPCIONAL, mas recomendado)
- [ ] Criar método `getCachedToken()` em `EfiPaymentService`
- [ ] Armazenar token em variável estática/sessão
- [ ] Validar expiração antes de usar

### Passo 5: Salvar link de pagamento no banco (OPCIONAL, mas recomendado)
- [ ] Criar migration: `031_add_gateway_payment_url_to_enrollments.sql`
- [ ] Adicionar coluna `gateway_payment_url` (TEXT)
- [ ] Atualizar `EfiPaymentService::createCharge()` para salvar URL
- [ ] Exibir link na UI (`matricula_show.php`)

### Passo 6: Testar geração de cobrança
- [ ] Criar matrícula com saldo devedor > 0
- [ ] Clicar em "Gerar Cobrança Efi"
- [ ] Verificar resposta JSON (charge_id, status, payment_url)
- [ ] Verificar banco (gateway_charge_id, billing_status = 'generated')

### Passo 7: Testar webhook (simulação)
- [ ] Criar script de teste: `tools/test_webhook_efi.php`
- [ ] Simular payload de webhook
- [ ] Verificar atualização no banco

### Passo 8: Configurar webhook na EFI (produção)
- [ ] Acessar painel da EFI
- [ ] Configurar URL: `https://seudominio.com/api/payments/webhook/efi`
- [ ] Configurar secret (se aplicável)
- [ ] Testar com webhook real

---

## D) MUDANÇAS RECOMENDADAS NO BANCO

### Migration 031: Adicionar campo para URL de pagamento

**Arquivo:** `database/migrations/031_add_gateway_payment_url_to_enrollments.sql`

```sql
-- Migration 031: Adicionar campo para armazenar URL de pagamento do gateway

ALTER TABLE `enrollments`
ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
COMMENT 'URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway';
```

**Justificativa:** Permitir acesso ao link de pagamento sem consultar API da EFI.

### Migration 032: Adicionar índice composto (OPCIONAL)

**Arquivo:** `database/migrations/032_add_gateway_index_to_enrollments.sql`

```sql
-- Migration 032: Adicionar índice composto para busca de cobranças

ALTER TABLE `enrollments`
ADD INDEX `idx_gateway_lookup` (`gateway_provider`, `gateway_charge_id`, `billing_status`);
```

**Justificativa:** Melhorar performance de buscas por cobrança.

---

## E) PONTOS DE SEGURANÇA

### 1. Armazenamento de Secret

**✅ BOAS PRÁTICAS ATUAIS:**
- Secrets armazenados em `.env` (não versionado)
- `Env::load()` carrega variáveis de ambiente
- Secrets nunca são logados

**⚠️ RECOMENDAÇÕES:**
- Garantir que `.env` está no `.gitignore`
- Nunca commitar `.env` no repositório
- Usar variáveis de ambiente do servidor em produção (não arquivo `.env`)

### 2. Logs

**Estado Atual:**
- `error_log()` usado para erros técnicos
- Não loga dados sensíveis (CPF, secrets)
- Webhooks não são logados (apenas erros)

**Recomendações:**
- Criar tabela `webhook_logs` para auditoria (opcional)
- Logar todos os webhooks recebidos (sem dados sensíveis)
- Implementar rotação de logs

### 3. Webhook

**Validação Atual:**
- ✅ Assinatura HMAC-SHA256 (se configurado)
- ❌ IP allowlist (não implementado)
- ❌ Rate limiting (não implementado)

**Recomendações:**
- Adicionar verificação de IP (se EFI fornecer range)
- Implementar rate limiting (ex: 10 requisições/minuto por IP)
- Sempre retornar HTTP 200 (já implementado ✅)

### 4. Autenticação de API

**Estado Atual:**
- ✅ Basic Auth com client_id:client_secret
- ✅ HTTPS obrigatório (curl SSL verification)
- ❌ Token não é cacheado (gera nova requisição a cada chamada)

**Recomendações:**
- Implementar cache de token
- Validar expiração do token
- Implementar retry com backoff exponencial

---

## F) TESTE FINAL (Passo a Passo)

### Pré-requisitos
1. ✅ Banco de dados com migrations executadas
2. ✅ `.env` configurado com credenciais EFI (sandbox)
3. ✅ Matrícula criada com:
   - `outstanding_amount > 0`
   - `installments` definido
   - `billing_status = 'draft'`

### Teste 1: Geração de Cobrança

**Passos:**
1. Acessar `/matriculas/{id}` (tela de edição)
2. Verificar que botão "Gerar Cobrança Efi" aparece
3. Clicar no botão
4. Confirmar diálogo (valores, parcelas)
5. Verificar resposta JSON:
   ```json
   {
     "ok": true,
     "charge_id": "123456",
     "status": "waiting",
     "payment_url": "https://..."
   }
   ```
6. Verificar banco:
   ```sql
   SELECT gateway_charge_id, billing_status, gateway_last_status 
   FROM enrollments 
   WHERE id = {enrollment_id};
   ```
   - `gateway_charge_id` deve ter valor
   - `billing_status` deve ser 'generated'
   - `gateway_last_status` deve ter status da EFI

### Teste 2: Idempotência

**Passos:**
1. Tentar gerar cobrança novamente (mesmo enrollment_id)
2. Verificar resposta:
   ```json
   {
     "ok": false,
     "message": "Cobrança já existe",
     "charge_id": "123456"
   }
   ```
3. Verificar que botão desaparece ou fica desabilitado

### Teste 3: Webhook (Simulação)

**Passos:**
1. Criar script de teste: `tools/test_webhook_efi.php`
2. Simular payload:
   ```json
   {
     "identifiers": {"charge_id": "123456"},
     "current": {"status": "paid"},
     "occurred_at": "2024-01-15T10:30:00Z"
   }
   ```
3. Enviar POST para `/api/payments/webhook/efi`
4. Verificar resposta:
   ```json
   {
     "ok": true,
     "processed": true,
     "charge_id": "123456",
     "status": "paid",
     "billing_status": "generated"
   }
   ```
5. Verificar banco:
   ```sql
   SELECT gateway_last_status, gateway_last_event_at, billing_status 
   FROM enrollments 
   WHERE gateway_charge_id = '123456';
   ```
   - `gateway_last_status` deve ser 'paid'
   - `gateway_last_event_at` deve ter timestamp
   - `billing_status` deve ser 'generated'

### Teste 4: Validações

**Teste 4.1: Saldo devedor = 0**
1. Criar matrícula com `outstanding_amount = 0`
2. Verificar que botão não aparece OU está desabilitado
3. Tentar chamar API diretamente
4. Verificar erro: "Sem saldo devedor para gerar cobrança"

**Teste 4.2: Cobrança já existe**
1. Gerar cobrança
2. Tentar gerar novamente
3. Verificar erro: "Cobrança já existe"

**Teste 4.3: Cobrança cancelada**
1. Gerar cobrança
2. Simular webhook com status 'canceled'
3. Tentar gerar novamente
4. Verificar que permite gerar (idempotência)

### Teste 5: Diferentes formas de pagamento

**Teste 5.1: PIX (à vista)**
1. Matrícula com `installments = 1`, `payment_method = 'pix'`
2. Gerar cobrança
3. Verificar `payment_url` contém QR Code PIX

**Teste 5.2: Boleto (à vista)**
1. Matrícula com `installments = 1`, `payment_method = 'boleto'`
2. Gerar cobrança
3. Verificar `payment_url` contém link do boleto

**Teste 5.3: Cartão Parcelado**
1. Matrícula com `installments > 1`
2. Gerar cobrança
3. Verificar que payload contém `credit_card.installments`

---

## RESUMO EXECUTIVO

### ✅ O QUE ESTÁ FUNCIONANDO

1. **Fluxo básico de geração de cobrança** - Implementado e funcional
2. **Integração com API EFI** - Service completo com autenticação
3. **Webhook de atualização de status** - Endpoint implementado
4. **Idempotência** - Previne duplicação de cobranças
5. **Estrutura de banco** - Colunas necessárias existem

### ❌ O QUE PRECISA SER CORRIGIDO

1. **Token não cacheado** - Performance
2. **Link de pagamento não salvo** - Funcionalidade
3. **`financial_status` não atualiza** - Regra de negócio
4. **Validação de botão na UI** - UX

### ⚠️ MELHORIAS RECOMENDADAS

1. Cache de token OAuth
2. Salvar URL de pagamento no banco
3. Atualizar `financial_status` automaticamente
4. Validações de CPF/Email
5. Logging de webhooks
6. IP allowlist para webhook

### 📋 CHECKLIST DE TESTE

- [ ] Teste 1: Geração de cobrança
- [ ] Teste 2: Idempotência
- [ ] Teste 3: Webhook (simulação)
- [ ] Teste 4: Validações
- [ ] Teste 5: Diferentes formas de pagamento

---

**Status Geral:** 🟡 **FUNCIONAL COM MELHORIAS NECESSÁRIAS**

O sistema está **pronto para testes básicos**, mas precisa de ajustes para produção:
- Cache de token
- Persistência de URL de pagamento
- Atualização automática de `financial_status`
- Melhorias de segurança no webhook
