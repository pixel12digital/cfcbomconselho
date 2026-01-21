# Auditoria Técnica + UX: Módulo Financeiro no Painel do Aluno + App

**Data:** 2024  
**Objetivo:** Mapear o estado atual do módulo financeiro como o aluno enxerga no sistema CFC (painel do aluno e app/PWA), identificar o que já existe, quais dados estão disponíveis, e propor a melhor estrutura de acesso ao financeiro sem criar duplicidades e sem quebrar fluxos.

---

## A) Como o aluno vê hoje

### A.1) Menu de Navegação

**Localização no menu:** O aluno tem acesso ao item "Financeiro" no menu lateral.

**Arquivo:** `app/Views/layouts/shell.php` (linhas 248-252)

```php
'ALUNO' => [
    ['path' => '/dashboard', 'label' => 'Meu Progresso', ...],
    ['path' => '/agenda', 'label' => 'Minha Agenda', ...],
    ['path' => '/financeiro', 'label' => 'Financeiro', ...],
],
```

**Rota:** `GET /financeiro` → `FinanceiroController::index()`

### A.2) Telas Existentes no Painel do Aluno

#### 1. Dashboard do Aluno (`/dashboard`)
**Arquivo:** `app/Views/dashboard/aluno.php`

**Informações financeiras exibidas:**
- **Status Geral:** "Em andamento", "Sem matrícula", "Pendência financeira", "Concluído"
- **Situação Financeira (card):**
  - Se `hasPending = true`: Exibe "⚠️ Pendência: R$ X em aberto" e mensagem "Entre em contato com a secretaria para regularizar"
  - Se `hasPending = false`: Exibe "✅ Sem pendências"
  - Botão: "Ver detalhes financeiros" (link para `/financeiro`)

**Dados calculados:**
- `totalDebt`: Soma de `(final_price - entry_amount)` para todas as matrículas não canceladas
- `totalPaid`: Soma de `entry_amount` de todas as matrículas
- `hasPending`: `true` se `totalDebt > 0`

**Fonte dos dados:** 
- Controller: `DashboardController::dashboardAluno()` (linhas 45-211)
- Model: `Enrollment::findByStudent()`
- Cálculo: Baseado em `enrollments.final_price` e `enrollments.entry_amount`

#### 2. Página Financeiro (`/financeiro`)
**Arquivo:** `app/Views/financeiro/index.php`

**Comportamento para aluno:**
- Quando `$currentRole === Constants::ROLE_ALUNO`, o controller carrega automaticamente os dados do próprio aluno
- Não exibe busca (apenas para admin/secretaria)
- Exibe diretamente os detalhes financeiros do aluno logado

**Informações exibidas:**

**a) Card de Resumo:**
- Nome do aluno
- CPF
- **Total Pago:** R$ X (verde)
- **Saldo Devedor:** R$ X (vermelho se > 0, verde se = 0)
- **Status Geral:**
  - "⚠️ BLOQUEADO" (vermelho) se alguma matrícula tem `financial_status = 'bloqueado'`
  - "⚠️ PENDENTE" (amarelo) se `totalDebt > 0`
  - "✅ EM DIA" (verde) se `totalDebt = 0`

**b) Tabela de Matrículas:**
- Colunas:
  - Serviço (nome do serviço)
  - Valor Total (`final_price`)
  - Status Financeiro (`financial_status`: em_dia/pendente/bloqueado)
  - Status (`status`: ativa/concluida/cancelada)
- **Ações disponíveis para aluno:** Nenhuma (coluna de ações só aparece para admin/secretaria)

**Fonte dos dados:**
- Controller: `FinanceiroController::index()` (linhas 45-63)
- Model: `Enrollment::findByStudent()`
- Cálculo: Mesmo do dashboard (`final_price - entry_amount`)

### A.3) Informações Financeiras Disponíveis (de onde vêm)

**Dados exibidos atualmente:**
1. **Total Pago:** `SUM(enrollments.entry_amount)` - Valor da entrada recebida
2. **Saldo Devedor:** `SUM(enrollments.final_price - enrollments.entry_amount)` - Diferença entre preço final e entrada
3. **Status Financeiro:** `enrollments.financial_status` (em_dia/pendente/bloqueado)
4. **Valor Total da Matrícula:** `enrollments.final_price`
5. **Nome do Serviço:** `services.name` (via JOIN)

**Dados NÃO exibidos para o aluno (mas existem no banco):**
- `enrollments.installments` - Número de parcelas
- `enrollments.down_payment_amount` - Valor da entrada (quando entrada_parcelas)
- `enrollments.down_payment_due_date` - Vencimento da entrada
- `enrollments.first_due_date` - Vencimento da primeira parcela
- `enrollments.gateway_charge_id` - ID da cobrança no gateway EFI
- `enrollments.gateway_last_status` - Último status do gateway (waiting, paid, settled, etc)
- `enrollments.gateway_payment_url` - Link do boleto/PIX/carnê
- `enrollments.billing_status` - Status da geração de cobrança (draft/ready/generated/error)
- `enrollments.gateway_last_event_at` - Data/hora do último evento do gateway

### A.4) O que está faltando para o aluno ter visão completa

**Lacunas identificadas:**

1. **Parcelas individuais:**
   - Não há visualização de parcelas (1/12, 2/12, etc.)
   - Não há vencimento por parcela
   - Não há status por parcela (a vencer, vencida, paga)

2. **Cobranças geradas:**
   - Aluno não vê se existe cobrança gerada no gateway
   - Aluno não vê link para pagamento (boleto/PIX)
   - Aluno não vê código PIX ou linha digitável
   - Aluno não pode baixar boleto PDF

3. **Status de pagamento:**
   - Aluno não vê status detalhado do gateway (waiting, paid, settled, etc.)
   - Aluno não sabe se pagamento foi confirmado ou está pendente
   - Não há histórico de eventos/pagamentos

4. **Ações do aluno:**
   - Aluno não pode "Pagar agora"
   - Aluno não pode "Copiar código PIX"
   - Aluno não pode "Baixar boleto"
   - Aluno não pode "Ver detalhes da parcela"

5. **Comprovantes:**
   - Não há área para upload/visualização de comprovantes
   - Não há histórico de comprovantes enviados

6. **Carnê (quando aplicável):**
   - Se a matrícula tem carnê (boleto parcelado), aluno não vê as parcelas individuais
   - Não há link para baixar carnê completo em PDF

### A.5) App/PWA

**Arquivos encontrados:**
- `public_html/manifest.json` - Manifest do PWA
- `public_html/sw.js` - Service Worker

**Status:** Sistema tem estrutura PWA, mas não foi verificado se há diferenças específicas no módulo financeiro para mobile/PWA.

**Observação:** As mesmas views (`dashboard/aluno.php` e `financeiro/index.php`) são servidas tanto para desktop quanto para PWA (não há versão mobile específica).

---

## B) Inventário Técnico (o que existe no código)

### B.1) Rotas/Endpoints Relacionados ao Financeiro do Aluno

#### Rotas Web (para aluno)

**Arquivo:** `app/routes/web.php`

| Rota | Método | Controller | Método | Descrição |
|------|--------|------------|--------|-----------|
| `/financeiro` | GET | `FinanceiroController` | `index()` | Página principal do financeiro (comportamento diferente para aluno vs admin) |
| `/api/financeiro/autocomplete` | GET | `FinanceiroController` | `autocomplete()` | Autocomplete para busca (apenas admin/secretaria) |

#### Rotas API (não acessíveis diretamente pelo aluno)

| Rota | Método | Controller | Método | Permissão | Descrição |
|------|--------|------------|--------|-----------|-----------|
| `/api/payments/generate` | POST | `PaymentsController` | `generate()` | ADMIN/SECRETARIA | Gera cobrança na EFI |
| `/api/payments/status` | GET | `PaymentsController` | `status()` | ADMIN/SECRETARIA | Retorna status da cobrança |
| `/api/payments/sync` | POST | `PaymentsController` | `sync()` | ADMIN/SECRETARIA | Sincroniza status com EFI |
| `/api/payments/sync-pendings` | POST | `PaymentsController` | `syncPendings()` | ADMIN/SECRETARIA | Sincroniza pendentes em lote |
| `/api/payments/cancel` | POST | `PaymentsController` | `cancel()` | ADMIN/SECRETARIA | Cancela cobrança |
| `/api/payments/webhook/efi` | POST | `PaymentsController` | `webhookEfi()` | PÚBLICO | Recebe webhook da EFI |

**Observação importante:** Nenhuma rota API de pagamentos está acessível para o aluno. Todas exigem permissão de ADMIN ou SECRETARIA.

### B.2) Controllers e Métodos

#### FinanceiroController
**Arquivo:** `app/Controllers/FinanceiroController.php`

**Métodos:**
- `index()` - Página principal (comportamento diferente para aluno)
  - Para aluno: Carrega automaticamente dados do próprio aluno
  - Para admin: Permite busca e visualização de qualquer aluno
- `autocomplete()` - Autocomplete para busca (apenas admin)

**Lógica para aluno:**
```php
if ($currentRole === Constants::ROLE_ALUNO && $userId) {
    $user = $userModel->findWithLinks($userId);
    if ($user && !empty($user['student_id'])) {
        $studentId = $user['student_id'];
        $student = $studentModel->find($studentId);
        if ($student && $student['cfc_id'] == $this->cfcId) {
            $enrollments = $enrollmentModel->findByStudent($studentId);
            // Calcular totais
            foreach ($enrollments as $enr) {
                $totalPaid += $enr['entry_amount'] ?? 0;
                $totalDebt += max(0, $enr['final_price'] - ($enr['entry_amount'] ?? 0));
            }
        }
    }
}
```

#### PaymentsController
**Arquivo:** `app/Controllers/PaymentsController.php`

**Métodos (todos restritos a ADMIN/SECRETARIA):**
- `generate()` - Gera cobrança na EFI
- `webhookEfi()` - Recebe webhook da EFI (público, mas valida assinatura)
- `sync()` - Sincroniza status de uma cobrança
- `syncPendings()` - Sincroniza pendentes em lote
- `status()` - Retorna status e detalhes da cobrança
- `cancel()` - Cancela cobrança

**Validação de permissão:**
```php
if (!in_array($currentRole, [Constants::ROLE_ADMIN, Constants::ROLE_SECRETARIA])) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'Você não tem permissão...']);
    exit;
}
```

### B.3) Tabelas e Modelos Envolvidos

#### Tabela Principal: `enrollments`

**Arquivo de migrations:**
- `002_create_phase1_tables.sql` - Estrutura base
- `009_add_payment_plan_to_enrollments.sql` - Parcelamento
- `010_add_entry_fields_to_enrollments.sql` - Entrada e saldo devedor
- `030_add_gateway_fields_to_enrollments.sql` - Campos do gateway EFI

**Colunas relevantes para financeiro:**

| Campo | Tipo | Descrição | Origem |
|-------|------|-----------|--------|
| `id` | INT(11) | PK | Migration 002 |
| `student_id` | INT(11) | FK → students | Migration 002 |
| `service_id` | INT(11) | FK → services | Migration 002 |
| `cfc_id` | INT(11) | FK → cfcs | Migration 002 |
| `base_price` | DECIMAL(10,2) | Preço base | Migration 002 |
| `discount_value` | DECIMAL(10,2) | Desconto | Migration 002 |
| `extra_value` | DECIMAL(10,2) | Valor extra | Migration 002 |
| `final_price` | DECIMAL(10,2) | Preço final | Migration 002 |
| `payment_method` | ENUM | pix/boleto/cartao/entrada_parcelas | Migration 002/009 |
| `financial_status` | ENUM | em_dia/pendente/bloqueado | Migration 002 |
| `status` | ENUM | ativa/concluida/cancelada | Migration 002 |
| `entry_amount` | DECIMAL(10,2) | Valor da entrada recebida | Migration 010 |
| `entry_payment_method` | ENUM | dinheiro/pix/cartao/boleto | Migration 010 |
| `entry_payment_date` | DATE | Data do pagamento da entrada | Migration 010 |
| `outstanding_amount` | DECIMAL(10,2) | Saldo devedor | Migration 010 |
| `installments` | INT(11) | Número de parcelas (1-12) | Migration 009 |
| `down_payment_amount` | DECIMAL(10,2) | Valor da entrada (quando entrada_parcelas) | Migration 009 |
| `down_payment_due_date` | DATE | Vencimento da entrada | Migration 009 |
| `first_due_date` | DATE | Vencimento da primeira parcela | Migration 009 |
| `billing_status` | ENUM | draft/ready/generated/error | Migration 009 |
| `gateway_provider` | VARCHAR(50) | Provedor (efi, asaas, etc) | Migration 030 |
| `gateway_charge_id` | VARCHAR(255) | ID da cobrança no gateway | Migration 030 |
| `gateway_last_status` | VARCHAR(50) | Último status do gateway | Migration 030 |
| `gateway_last_event_at` | DATETIME | Data/hora do último evento | Migration 030 |
| `gateway_payment_url` | TEXT | Link/JSON do pagamento | Migration 031 |

**Observação:** `gateway_payment_url` pode conter:
- String simples (link direto) para cobrança única
- JSON com estrutura de Carnê (quando `payment_method = 'boleto'` e `installments > 1`)

**Estrutura JSON do Carnê (exemplo):**
```json
{
  "type": "carne",
  "carnet_id": "123456",
  "status": "up_to_date",
  "cover": "https://...",
  "download_link": "https://...",
  "charges": [
    {
      "charge_id": "789",
      "expire_at": "2024-01-15",
      "status": "paid",
      "billet_link": "https://..."
    },
    ...
  ]
}
```

#### Model: Enrollment
**Arquivo:** `app/Models/Enrollment.php`

**Métodos:**
- `findByStudent($studentId, $cfcId = null)` - Busca matrículas por aluno
- `findWithDetails($id)` - Busca matrícula com detalhes completos (JOIN com services e students)
- `calculateFinalPrice($basePrice, $discountValue, $extraValue)` - Calcula preço final

**Limitação:** Não há método específico para buscar parcelas individuais ou cobranças.

### B.4) Integração EFI

#### Service: EfiPaymentService
**Arquivo:** `app/Services/EfiPaymentService.php`

**Métodos principais:**

1. **`createCharge($enrollment)`**
   - Cria cobrança na EFI (PIX, boleto, cartão ou carnê)
   - Suporta PIX (API Pix `/v2/cob`)
   - Suporta boleto único (API Cobranças `/v1/charge/one-step`)
   - Suporta carnê (API Cobranças `/v1/carnet`)
   - Suporta cartão de crédito (API Cobranças `/v1/charge/one-step`)
   - Atualiza `enrollments` com `gateway_charge_id`, `gateway_last_status`, `gateway_payment_url`
   - **Validação anti-duplicidade:** Verifica se já existe cobrança ativa antes de criar

2. **`createCarnet($enrollment, $student, $outstandingAmount, $installments)`**
   - Cria carnê (múltiplos boletos) na EFI
   - Divide `outstanding_amount` em `installments` parcelas
   - Calcula vencimentos baseado em `first_due_date`
   - Salva estrutura JSON completa em `gateway_payment_url`

3. **`syncCharge($enrollment)`**
   - Consulta status atual da cobrança na EFI
   - Atualiza `gateway_last_status`, `gateway_last_event_at`
   - Atualiza `financial_status` baseado no status do gateway
   - Para carnê, atualiza status de parcelas individuais no JSON

4. **`parseWebhook($requestPayload)`**
   - Processa webhook recebido da EFI
   - Valida assinatura (se `EFI_WEBHOOK_SECRET` configurado)
   - Atualiza status da cobrança/carnê no banco
   - Suporta webhook de cobrança única e carnê
   - Para carnê, atualiza status de parcelas individuais

5. **`cancelCarnet($enrollment)`**
   - Cancela carnê na EFI
   - Atualiza status local

**Configuração (variáveis de ambiente):**
- `EFI_CLIENT_ID` - Client ID da EFI
- `EFI_CLIENT_SECRET` - Client Secret da EFI
- `EFI_SANDBOX` - true/false (ambiente)
- `EFI_CERT_PATH` - Caminho do certificado P12 (produção)
- `EFI_CERT_PASSWORD` - Senha do certificado
- `EFI_PIX_KEY` - Chave PIX (para cobranças PIX)
- `EFI_WEBHOOK_SECRET` - Secret para validar assinatura do webhook

### B.5) Jobs/Cron/Webhook

#### Webhook EFI
**Rota:** `POST /api/payments/webhook/efi`  
**Controller:** `PaymentsController::webhookEfi()`  
**Arquivo:** `app/Controllers/PaymentsController.php` (linhas 156-188)

**Funcionamento:**
1. Recebe payload JSON da EFI
2. Chama `EfiPaymentService::parseWebhook($payload)`
3. Valida assinatura (se `EFI_WEBHOOK_SECRET` configurado)
4. Atualiza status no banco
5. Sempre retorna HTTP 200 (para evitar retry infinito)

**Validação de assinatura:**
```php
if ($this->webhookSecret) {
    $signature = $_SERVER['HTTP_X_GN_SIGNATURE'] ?? '';
    if (!$this->validateWebhookSignature($requestPayload, $signature)) {
        return ['ok' => false, 'message' => 'Assinatura inválida'];
    }
}
```

**Atualização no banco:**
- Atualiza `gateway_last_status`
- Atualiza `gateway_last_event_at`
- Atualiza `gateway_payment_url` (para carnê, atualiza JSON com status das parcelas)
- Atualiza `billing_status` (se aplicável)
- Atualiza `financial_status` (mapeado do status do gateway)

#### Rotina de Reconciliação
**Não existe job/cron automático.**

**Reconciliação manual disponível:**
- `POST /api/payments/sync` - Sincroniza uma matrícula específica
- `POST /api/payments/sync-pendings` - Sincroniza pendentes em lote (página atual)

**Observação:** Não há rotina automática de polling/consulta periódica para evitar divergências.

### B.6) Regras Anti-Duplicidade Já Existentes

#### 1. Verificação antes de criar cobrança
**Arquivo:** `app/Controllers/PaymentsController.php` (linhas 91-105)

```php
// Verificar idempotência: se já existe cobrança ativa, retornar dados existentes
if (!empty($enrollment['gateway_charge_id']) && 
    $enrollment['billing_status'] === 'generated' &&
    !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error'])) {
    
    http_response_code(200);
    echo json_encode([
        'ok' => true,
        'charge_id' => $enrollment['gateway_charge_id'],
        'status' => $enrollment['gateway_last_status'],
        'payment_url' => $enrollment['gateway_payment_url'] ?? null,
        'message' => 'Esta cobrança já foi gerada anteriormente'
    ]);
    exit;
}
```

**Chave de unicidade:** `enrollments.gateway_charge_id` (único por matrícula)

**Condições para considerar "cobrança ativa":**
- `gateway_charge_id` não está vazio
- `billing_status = 'generated'`
- `gateway_last_status` não é 'canceled', 'expired' ou 'error'

#### 2. Verificação no Service
**Arquivo:** `app/Services/EfiPaymentService.php` (linhas 87-97)

```php
// Verificar se já existe cobrança ativa (idempotência)
if (!empty($enrollment['gateway_charge_id']) && 
    $enrollment['billing_status'] === 'generated' &&
    !in_array($enrollment['gateway_last_status'] ?? '', ['canceled', 'expired', 'error'])) {
    return [
        'ok' => false,
        'message' => 'Cobrança já existe',
        'charge_id' => $enrollment['gateway_charge_id'],
        'status' => $enrollment['gateway_last_status']
    ];
}
```

**Observação:** Não há constraint UNIQUE no banco para `gateway_charge_id`. A verificação é feita apenas em código.

#### 3. Para Carnê (parcelas)
**Arquivo:** `app/Services/EfiPaymentService.php` (método `parseWebhook`)

Para carnê, cada parcela tem seu próprio `charge_id` dentro do JSON `gateway_payment_url`. O webhook atualiza apenas a parcela específica quando recebe evento.

**Estrutura de identificação:**
- Carnê: `gateway_charge_id` = `carnet_id` (ID do carnê completo)
- Parcelas: `charges[].charge_id` (dentro do JSON)

**Não há verificação de duplicidade de parcelas individuais** - assume-se que o carnê é criado uma única vez e as parcelas são gerenciadas pela EFI.

---

## C) Verdadeiro "Source of Truth" do Status Financeiro

### C.1) O Status Exibido para o Aluno Vem de Onde?

**Resposta:** **Banco local (tabela `enrollments`)**

**Evidências:**
1. **Dashboard do Aluno:**
   - `DashboardController::dashboardAluno()` busca matrículas via `Enrollment::findByStudent()`
   - Calcula `totalDebt` e `totalPaid` diretamente do banco
   - Não faz consulta à EFI em tempo real

2. **Página Financeiro:**
   - `FinanceiroController::index()` busca matrículas via `Enrollment::findByStudent()`
   - Exibe dados diretamente do banco
   - Não faz consulta à EFI em tempo real

**Não há:**
- Consulta em tempo real à EFI ao exibir dados para o aluno
- Cache/espelhamento via webhook (webhook existe, mas atualiza o banco; não há cache separado)
- Mistura dos dois (não consulta EFI e depois exibe)

### C.2) Estados Existentes e Mapeamento

#### Estados no Banco Local

**Campo `financial_status` (ENUM):**
- `'em_dia'` - Aluno em dia com pagamentos
- `'pendente'` - Aluno com pendências
- `'bloqueado'` - Aluno bloqueado (não pode agendar/iniciar aulas)

**Campo `gateway_last_status` (VARCHAR):**
- Estados possíveis (conforme EFI):
  - `'waiting'` - Aguardando pagamento
  - `'up_to_date'` - Em dia (carnê sem parcelas vencidas)
  - `'paid'` - Pago
  - `'paid_partial'` - Parcialmente pago
  - `'settled'` - Liquidado
  - `'canceled'` - Cancelado
  - `'expired'` - Expirado
  - `'error'` - Erro
  - `'unpaid'` - Não pago
  - `'pending'` - Pendente
  - `'processing'` - Processando
  - `'new'` - Nova cobrança

**Campo `billing_status` (ENUM):**
- `'draft'` - Rascunho (cobrança não gerada)
- `'ready'` - Pronto para gerar
- `'generated'` - Cobrança gerada no gateway
- `'error'` - Erro ao gerar

#### Mapeamento Gateway → Financial Status

**Arquivo:** `app/Services/EfiPaymentService.php` (método `syncCharge`)

**Lógica de mapeamento:**
```php
// Mapear status do gateway para financial_status
$financialStatus = null;
if (in_array($status, ['paid', 'settled'])) {
    $financialStatus = 'em_dia';
} elseif (in_array($status, ['waiting', 'pending', 'processing', 'new', 'up_to_date'])) {
    $financialStatus = 'pendente';
} elseif (in_array($status, ['unpaid', 'expired'])) {
    $financialStatus = 'bloqueado';
}
```

**Observação:** Se não mapear, recalcula baseado em `outstanding_amount`:
```php
if ($financialStatus === null) {
    $financialStatus = $this->recalculateFinancialStatus($enrollment);
}
```

**Método `recalculateFinancialStatus`:**
```php
private function recalculateFinancialStatus($enrollment)
{
    $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? 
                                  ($enrollment['final_price'] - ($enrollment['entry_amount'] ?? 0)));
    
    if ($outstandingAmount <= 0) {
        return 'em_dia';
    } elseif ($outstandingAmount > 0 && $enrollment['financial_status'] !== 'bloqueado') {
        return 'pendente';
    } else {
        return $enrollment['financial_status']; // Preserva bloqueado
    }
}
```

### C.3) Atualização do Status

**Quando o status é atualizado:**

1. **Webhook da EFI:**
   - `PaymentsController::webhookEfi()` → `EfiPaymentService::parseWebhook()`
   - Atualiza `gateway_last_status`, `gateway_last_event_at`
   - Atualiza `financial_status` (mapeado do status do gateway)
   - Atualiza JSON do carnê (se aplicável)

2. **Sincronização manual:**
   - `PaymentsController::sync()` → `EfiPaymentService::syncCharge()`
   - Consulta status atual na EFI
   - Atualiza campos no banco

3. **Criação de cobrança:**
   - `PaymentsController::generate()` → `EfiPaymentService::createCharge()`
   - Atualiza `gateway_charge_id`, `gateway_last_status`, `billing_status`

**Não há:**
- Atualização automática periódica (cron/job)
- Atualização em tempo real ao exibir dados para o aluno

---

## D) Proposta de Melhor UX para Aluno (sem implementar ainda)

### D.1) Sugestão Mínima (Recomendada)

#### 1. Resumo Financeiro no Dashboard do Aluno

**Melhorias no card "Situação Financeira":**

**Estado atual:**
```
⚠️ Pendência: R$ 1.500,00 em aberto
Entre em contato com a secretaria para regularizar.
[Ver detalhes financeiros]
```

**Proposta:**
```
┌─────────────────────────────────────────┐
│ Situação Financeira                     │
├─────────────────────────────────────────┤
│ Em aberto: R$ 1.500,00                  │
│ Próximo vencimento: 15/01/2024          │
│ Pagamentos em atraso: 2                 │
│                                         │
│ [Ver Financeiro] [Pagar Agora]         │
└─────────────────────────────────────────┘
```

**Dados necessários:**
- `totalDebt` - Já existe
- `nextDueDate` - Precisa calcular: `MIN(first_due_date, down_payment_due_date)` onde `outstanding_amount > 0`
- `overdueCount` - Precisa calcular: Contar parcelas com `due_date < CURDATE()` e status não pago

**Fonte:** Dados já existem no banco, apenas precisa calcular.

#### 2. Tela "Financeiro" (melhorada)

**Estrutura proposta:**

**a) Card de Resumo (mantém, mas adiciona):**
- Total Pago: R$ X
- Saldo Devedor: R$ X
- Próximo Vencimento: DD/MM/AAAA
- Parcelas em Atraso: N

**b) Lista de Parcelas/Cobranças:**

**Estado atual:** Lista apenas matrículas (não parcelas)

**Proposta:** Lista de parcelas/cobranças com:

| Competência/Parcela | Vencimento | Valor | Status | Ações |
|---------------------|------------|-------|--------|-------|
| Entrada | 01/01/2024 | R$ 500,00 | ✅ Paga | Ver detalhes |
| 1/12 | 15/01/2024 | R$ 200,00 | ⚠️ A vencer | [Pagar agora] [Copiar PIX] |
| 2/12 | 15/02/2024 | R$ 200,00 | ⚠️ A vencer | [Pagar agora] |
| 3/12 | 15/03/2024 | R$ 200,00 | ❌ Vencida | [Pagar agora] [Baixar boleto] |

**Status possíveis:**
- ✅ **Paga** - Parcela paga (status = paid/settled)
- ⚠️ **A vencer** - Parcela ainda não venceu
- ❌ **Vencida** - Parcela vencida e não paga
- ⏳ **Aguardando** - Cobrança gerada, aguardando pagamento

**Ações disponíveis:**
- **"Pagar agora"** - Abre modal/fluxo de pagamento
  - Se PIX: Mostra QR Code e código copia-e-cola
  - Se boleto: Abre link do boleto
  - Se carnê: Abre link do carnê ou parcela específica
- **"Copiar código"** - Copia código PIX ou linha digitável
- **"Baixar boleto PDF"** - Baixa PDF do boleto (se existir)
- **"Ver detalhes"** - Abre tela de detalhes da parcela

**Dados necessários:**

**Para matrícula sem cobrança gerada:**
- Calcular parcelas baseado em:
  - `installments` - Número de parcelas
  - `outstanding_amount` - Valor total a dividir
  - `first_due_date` - Data da primeira parcela
  - `down_payment_due_date` - Data da entrada (se houver)

**Para matrícula com cobrança gerada:**
- Se cobrança única: Usar `gateway_payment_url` (link direto)
- Se carnê: Decodificar JSON `gateway_payment_url` e listar `charges[]`
- Status de cada parcela vem de `charges[].status`

**Fonte:** Dados já existem no banco, mas precisam ser processados/formatados.

#### 3. Tela de Detalhes da Parcela

**Estrutura proposta:**

```
┌─────────────────────────────────────────┐
│ Parcela 1/12 - Matrícula CNH           │
├─────────────────────────────────────────┤
│ Status: ⚠️ A vencer                     │
│ Valor: R$ 200,00                       │
│ Vencimento: 15/01/2024                 │
│                                         │
│ Histórico:                              │
│ • 10/01/2024 14:30 - Cobrança gerada   │
│ • 10/01/2024 14:30 - Boleto emitido    │
│                                         │
│ Dados do Pagamento:                     │
│ • Código de barras: 34191...            │
│ • Linha digitável: 34191.09008...       │
│ • Link: [Abrir boleto]                  │
│                                         │
│ [Pagar agora] [Copiar código]          │
│ [Baixar boleto PDF]                     │
│ [Atualizar status]                      │
└─────────────────────────────────────────┘
```

**Dados necessários:**
- Status da parcela - Já existe (no JSON do carnê ou `gateway_last_status`)
- Valor - Já existe (calculado ou no JSON)
- Vencimento - Já existe (`first_due_date` ou `charges[].expire_at`)
- Histórico - **Não existe** (precisa criar tabela `payment_events` ou usar logs)
- Código de barras/linha digitável - Precisa consultar EFI ou salvar no banco
- Link do boleto - Já existe (`gateway_payment_url` ou `charges[].billet_link`)

**Botão "Atualizar status":**
- Chama `POST /api/payments/sync` (precisa permitir para aluno)
- Ou cria endpoint específico para aluno: `POST /api/student/payments/sync`

### D.2) O que Dá para Mostrar "Já" com os Dados Atuais

**✅ Pode exibir imediatamente (sem novas implementações):**

1. **Resumo financeiro básico:**
   - Total pago (`entry_amount`)
   - Saldo devedor (`outstanding_amount` ou `final_price - entry_amount`)
   - Status financeiro (`financial_status`)

2. **Lista de matrículas:**
   - Nome do serviço
   - Valor total
   - Status financeiro
   - Status da matrícula

3. **Informações de parcelamento (se existir):**
   - Número de parcelas (`installments`)
   - Valor da entrada (`down_payment_amount`)
   - Vencimento da entrada (`down_payment_due_date`)
   - Vencimento da primeira parcela (`first_due_date`)

4. **Se cobrança foi gerada:**
   - Status da cobrança (`gateway_last_status`)
   - Link do pagamento (`gateway_payment_url` - se for string simples)
   - Data do último evento (`gateway_last_event_at`)

5. **Se for carnê:**
   - Status geral do carnê (decodificar JSON)
   - Link para baixar carnê completo (`download_link` no JSON)
   - Link para visualizar (`cover` no JSON)

**❌ Não pode exibir (precisa implementar):**

1. **Parcelas individuais:**
   - Se matrícula não tem cobrança gerada, não há como listar parcelas individuais (só há `installments`, mas não há tabela de parcelas)
   - Se matrícula tem carnê, precisa decodificar JSON e processar `charges[]`

2. **Status por parcela:**
   - Para carnê, status está no JSON `gateway_payment_url.charges[].status`
   - Para cobrança única, não há "parcelas" - é um pagamento único

3. **Histórico de eventos:**
   - Não há tabela de histórico
   - Webhook atualiza status, mas não registra histórico

4. **Código PIX/linha digitável:**
   - Para PIX, código está na resposta da API, mas não é persistido no banco
   - Para boleto, linha digitável precisa ser consultada na EFI ou salva no banco

5. **Comprovantes:**
   - Não há tabela de comprovantes
   - Não há upload de comprovantes

### D.3) O que Depende de Implementar

#### 1. Reconciliação/Webhook/Mapeamento Melhor

**Para exibir status confiável:**
- ✅ Webhook já existe e atualiza status
- ❌ Não há rotina de reconciliação automática (cron)
- ❌ Aluno não pode "atualizar status" manualmente (endpoint restrito)

**Ação necessária:**
- Criar endpoint para aluno: `POST /api/student/payments/{enrollment_id}/sync`
- Ou permitir aluno acessar `POST /api/payments/sync` (com validação de ownership)

#### 2. Persistência de Dados de Pagamento

**Para exibir código PIX/linha digitável:**
- PIX: Código está na resposta da API, mas não é persistido
- Boleto: Linha digitável precisa ser consultada ou persistida

**Ação necessária:**
- Adicionar coluna `gateway_pix_code` (TEXT) para código PIX
- Adicionar coluna `gateway_barcode` (VARCHAR) para linha digitável
- Ou consultar EFI em tempo real (não recomendado - lento)

#### 3. Histórico de Eventos

**Para exibir histórico:**
- Não há tabela de histórico

**Ação necessária:**
- Criar tabela `payment_events`:
  ```sql
  CREATE TABLE payment_events (
    id INT PRIMARY KEY AUTO_INCREMENT,
    enrollment_id INT NOT NULL,
    event_type VARCHAR(50), -- 'created', 'paid', 'expired', etc
    event_data JSON,
    occurred_at DATETIME,
    FOREIGN KEY (enrollment_id) REFERENCES enrollments(id)
  );
  ```
- Registrar eventos no webhook e na criação de cobrança

#### 4. Processamento de Parcelas

**Para exibir parcelas individuais:**
- Se matrícula não tem cobrança gerada, calcular parcelas baseado em `installments` e `outstanding_amount`
- Se matrícula tem carnê, decodificar JSON e processar `charges[]`

**Ação necessária:**
- Criar método `Enrollment::getInstallments($enrollmentId)` que:
  - Se tem carnê: Decodifica JSON e retorna `charges[]`
  - Se não tem cobrança: Calcula parcelas baseado em `installments` e `first_due_date`
  - Retorna array padronizado: `[{number, due_date, amount, status, charge_id, payment_url}]`

---

## E) Plano Seguro (se houver lacunas)

### E.1) Lacunas Identificadas

1. **Não existe webhook confiável:**
   - ✅ Webhook existe e funciona
   - ⚠️ Validação de assinatura é opcional (depende de `EFI_WEBHOOK_SECRET`)
   - ❌ Não há rotina de reconciliação automática

2. **Status não é totalmente confiável:**
   - Status vem do banco local (não consulta EFI em tempo real)
   - Webhook atualiza, mas pode haver divergências se webhook falhar
   - Aluno não pode "forçar" atualização

3. **Falta persistir dados de pagamento:**
   - Código PIX não é persistido
   - Linha digitável do boleto não é persistida
   - Precisa consultar EFI para obter (lento)

4. **Falta vínculo parcela ↔ cobrança:**
   - Para matrículas sem cobrança gerada, não há "parcelas" no banco
   - Parcelas são calculadas dinamicamente
   - Para carnê, parcelas estão no JSON (não normalizado)

5. **Falta histórico de eventos:**
   - Não há rastreamento de quando cobrança foi criada, paga, etc.
   - Webhook atualiza status, mas não registra histórico

6. **Aluno não tem endpoints para ações:**
   - Aluno não pode "pagar agora" (não há endpoint)
   - Aluno não pode "sincronizar status" (endpoint restrito)
   - Aluno não pode "ver detalhes da cobrança" (endpoint restrito)

### E.2) Plano em Etapas

#### Etapa 1 (Sem Risco): Somente Leitura / Exibição / Telas Consumindo Dados Já Consistentes

**Objetivo:** Melhorar UX do aluno sem alterar lógica de negócio.

**Tarefas:**
1. **Melhorar dashboard do aluno:**
   - Adicionar "Próximo vencimento" e "Parcelas em atraso" no card de situação financeira
   - Calcular baseado em dados existentes (`first_due_date`, `outstanding_amount`)

2. **Melhorar tela financeiro:**
   - Adicionar card de resumo com próximos vencimentos
   - Listar parcelas (calcular dinamicamente se não tem cobrança, ou decodificar JSON se tem carnê)
   - Exibir status por parcela (baseado em `gateway_last_status` ou cálculo)

3. **Criar tela de detalhes da parcela:**
   - Exibir dados da parcela (valor, vencimento, status)
   - Exibir link do pagamento (se existir `gateway_payment_url`)
   - Botão "Abrir boleto/PIX" (abre link externo)

**Arquivos a modificar:**
- `app/Views/dashboard/aluno.php` - Adicionar cálculos no card
- `app/Views/financeiro/index.php` - Melhorar layout e adicionar lista de parcelas
- `app/Controllers/FinanceiroController.php` - Adicionar método para calcular parcelas
- Criar `app/Views/financeiro/parcela.php` - Tela de detalhes

**Riscos:** Baixo - Apenas leitura e exibição.

**Tempo estimado:** 2-3 dias

---

#### Etapa 2: Ajustes de Persistência/Normalização (com Constraints Anti-Duplicidade)

**Objetivo:** Melhorar persistência de dados e garantir unicidade.

**Tarefas:**
1. **Adicionar colunas para dados de pagamento:**
   ```sql
   ALTER TABLE enrollments
   ADD COLUMN gateway_pix_code TEXT NULL COMMENT 'Código PIX (copia-e-cola)',
   ADD COLUMN gateway_barcode VARCHAR(255) NULL COMMENT 'Linha digitável do boleto';
   ```

2. **Persistir código PIX ao criar cobrança:**
   - Modificar `EfiPaymentService::createCharge()` para salvar `pixCopiaECola` em `gateway_pix_code`

3. **Persistir linha digitável ao criar boleto:**
   - Modificar `EfiPaymentService::createCharge()` para salvar linha digitável em `gateway_barcode`

4. **Adicionar constraint UNIQUE para gateway_charge_id:**
   ```sql
   ALTER TABLE enrollments
   ADD UNIQUE KEY unique_gateway_charge (gateway_charge_id, gateway_provider);
   ```
   - **Cuidado:** Pode falhar se já existirem duplicatas. Verificar antes.

5. **Criar índice para melhor performance:**
   ```sql
   ALTER TABLE enrollments
   ADD KEY idx_student_financial (student_id, financial_status, outstanding_amount);
   ```

**Arquivos a modificar:**
- `database/migrations/032_add_payment_data_fields.sql` - Nova migration
- `app/Services/EfiPaymentService.php` - Persistir dados ao criar cobrança

**Riscos:** Médio - Pode quebrar se houver dados duplicados. Fazer backup antes.

**Tempo estimado:** 1-2 dias

---

#### Etapa 3: Webhook + Reconciliação

**Objetivo:** Garantir que status está sempre sincronizado.

**Tarefas:**
1. **Melhorar validação de webhook:**
   - Garantir que `EFI_WEBHOOK_SECRET` está configurado em produção
   - Adicionar log de webhooks rejeitados

2. **Criar rotina de reconciliação (cron):**
   - Script PHP: `tools/reconcile_payments.php`
   - Consulta matrículas com `billing_status = 'generated'` e `gateway_last_event_at < NOW() - INTERVAL 1 DAY`
   - Chama `EfiPaymentService::syncCharge()` para cada uma
   - Executar diariamente via cron

3. **Criar endpoint para aluno sincronizar:**
   - `POST /api/student/payments/{enrollment_id}/sync`
   - Valida que `enrollment.student_id` corresponde ao aluno logado
   - Chama `EfiPaymentService::syncCharge()`
   - Retorna status atualizado

4. **Adicionar botão "Atualizar status" na tela do aluno:**
   - Na tela de detalhes da parcela
   - Chama endpoint acima
   - Mostra loading e atualiza dados

**Arquivos a criar/modificar:**
- `tools/reconcile_payments.php` - Script de reconciliação
- `app/Controllers/PaymentsController.php` - Adicionar método `studentSync()`
- `app/routes/web.php` - Adicionar rota
- `app/Views/financeiro/parcela.php` - Adicionar botão

**Riscos:** Médio - Pode sobrecarregar API da EFI se executar muito frequentemente.

**Tempo estimado:** 2-3 dias

---

#### Etapa 4: Melhorias de UX (Pagamento 1 Clique, etc.)

**Objetivo:** Permitir que aluno realize ações de pagamento.

**Tarefas:**
1. **Criar endpoint para aluno obter dados de pagamento:**
   - `GET /api/student/payments/{enrollment_id}/payment-data`
   - Retorna código PIX, link do boleto, etc.
   - Valida ownership

2. **Criar modal "Pagar agora":**
   - Se PIX: Mostra QR Code e código copia-e-cola
   - Se boleto: Mostra linha digitável e botão "Abrir boleto"
   - Botão "Copiar código" para PIX/linha digitável

3. **Adicionar funcionalidade de comprovante (opcional):**
   - Upload de comprovante
   - Tabela `payment_receipts`
   - Visualização de comprovantes enviados

4. **Notificações (opcional):**
   - Notificar aluno quando parcela está próxima do vencimento
   - Notificar quando pagamento é confirmado

**Arquivos a criar/modificar:**
- `app/Controllers/PaymentsController.php` - Adicionar métodos para aluno
- `app/routes/web.php` - Adicionar rotas
- `app/Views/financeiro/parcela.php` - Adicionar modal
- Criar `app/Views/financeiro/payment_modal.php`

**Riscos:** Baixo - Apenas adiciona funcionalidades, não altera existentes.

**Tempo estimado:** 3-4 dias

---

## Resumo Executivo

### O que já está pronto para o aluno ver hoje

✅ **Dashboard do Aluno:**
- Resumo financeiro básico (total pago, saldo devedor)
- Status geral (em dia/pendente/bloqueado)
- Link para página financeiro

✅ **Página Financeiro:**
- Card de resumo (total pago, saldo devedor, status geral)
- Lista de matrículas com valores e status
- Dados básicos de parcelamento (se existir)

✅ **Dados disponíveis no banco:**
- Valores (final_price, entry_amount, outstanding_amount)
- Status financeiro (financial_status)
- Informações de parcelamento (installments, first_due_date)
- Status do gateway (gateway_last_status, gateway_payment_url)

### O que está faltando para o aluno ter visão financeira completa e confiável

❌ **Visualização de parcelas individuais:**
- Aluno não vê parcelas (1/12, 2/12, etc.)
- Aluno não vê vencimento por parcela
- Aluno não vê status por parcela

❌ **Acesso a dados de pagamento:**
- Aluno não vê código PIX
- Aluno não vê linha digitável do boleto
- Aluno não pode baixar boleto PDF
- Aluno não pode "pagar agora"

❌ **Status confiável:**
- Status vem do banco local (não consulta EFI em tempo real)
- Aluno não pode "forçar" atualização
- Não há rotina de reconciliação automática

❌ **Histórico e eventos:**
- Não há histórico de quando cobrança foi criada/paga
- Não há rastreamento de eventos

❌ **Endpoints para aluno:**
- Aluno não tem endpoints para ações (pagar, sincronizar, etc.)
- Todos os endpoints de pagamento são restritos a admin/secretaria

### Recomendação de UX (menu + telas)

**Menu:** ✅ Já existe item "Financeiro" no menu do aluno

**Telas propostas:**

1. **Dashboard (melhorado):**
   - Card de situação financeira com:
     - Em aberto: R$ X
     - Próximo vencimento: DD/MM/AAAA
     - Pagamentos em atraso: N
     - Botões: [Ver Financeiro] [Pagar Agora]

2. **Tela Financeiro (melhorada):**
   - Card de resumo (mantém atual + adiciona próximos vencimentos)
   - Lista de parcelas/cobranças:
     - Colunas: Competência, Vencimento, Valor, Status, Ações
     - Ações: [Pagar agora] [Copiar código] [Baixar boleto] [Ver detalhes]

3. **Tela Detalhes da Parcela (nova):**
   - Status, valor, vencimento
   - Histórico de eventos
   - Dados do pagamento (código PIX, linha digitável, link)
   - Ações: [Pagar agora] [Copiar código] [Baixar boleto] [Atualizar status]

### Riscos de Duplicidade Identificados e Como Mitigar

**Riscos identificados:**

1. **Cobrança duplicada para mesma parcela:**
   - ✅ **Mitigado:** Verificação antes de criar (`gateway_charge_id` não vazio + `billing_status = 'generated'`)
   - ⚠️ **Melhoria:** Adicionar constraint UNIQUE no banco (Etapa 2)

2. **Divergência entre status local vs EFI:**
   - ⚠️ **Risco:** Status vem do banco local, pode estar desatualizado
   - ✅ **Mitigado parcialmente:** Webhook atualiza status
   - 🔧 **Melhoria:** Rotina de reconciliação automática (Etapa 3)

3. **Pagamentos "confirmados" sem webhook/baixa real:**
   - ⚠️ **Risco:** Se webhook falhar, status pode não atualizar
   - 🔧 **Melhoria:** Rotina de reconciliação automática (Etapa 3)
   - 🔧 **Melhoria:** Permitir aluno "forçar" atualização (Etapa 3)

4. **Inconsistência entre painel admin e painel do aluno:**
   - ⚠️ **Risco:** Ambos usam mesmo banco, mas podem exibir dados diferentes se houver cache
   - ✅ **Mitigado:** Não há cache, ambos leem do mesmo banco
   - ⚠️ **Observação:** Admin pode ver mais detalhes (status do gateway, etc.), mas dados base são os mesmos

**Recomendações adicionais:**

1. **Adicionar constraint UNIQUE:**
   ```sql
   ALTER TABLE enrollments
   ADD UNIQUE KEY unique_gateway_charge (gateway_charge_id, gateway_provider);
   ```

2. **Criar rotina de reconciliação:**
   - Executar diariamente via cron
   - Sincronizar matrículas com cobrança gerada há mais de 1 dia

3. **Melhorar logs:**
   - Registrar todas as tentativas de criar cobrança
   - Registrar webhooks recebidos
   - Registrar sincronizações manuais

4. **Validação de ownership:**
   - Sempre validar que aluno só acessa seus próprios dados
   - Validar `enrollment.student_id = current_user.student_id` em todos os endpoints para aluno

---

**Fim do Relatório**
