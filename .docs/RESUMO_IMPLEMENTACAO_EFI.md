# RESUMO FINAL - Implementação Sincronização EFI

## 1. Status EFI Encontrados na Resposta Real

### Status Retornados por `getChargeStatus()` (API EFI)

Com base na documentação da EFI e implementação:

| Status EFI | Descrição | Quando Ocorre |
|------------|-----------|---------------|
| `paid` | Pagamento confirmado | Pagamento realizado e confirmado |
| `settled` | Pagamento liquidado | Pagamento liquidado na conta |
| `approved` | Pagamento aprovado | Pagamento aprovado (cartão) |
| `waiting` | Aguardando pagamento | Cobrança gerada, aguardando pagamento |
| `unpaid` | Não pago | Cobrança não foi paga |
| `pending` | Pendente | Status intermediário |
| `processing` | Processando | Processamento em andamento |
| `new` | Nova cobrança | Cobrança recém-criada |
| `canceled` | Cancelado | Cobrança cancelada |
| `expired` | Expirado | Cobrança expirou |
| `refunded` | Reembolsado | Pagamento reembolsado |

**Observação:** A resposta real pode variar. O sistema trata status desconhecidos como `ready` (billing_status) e não altera `financial_status`.

---

## 2. Enums/Valores Reais do Sistema

### `financial_status` (tabela `enrollments`)

**Tipo:** `ENUM('em_dia','pendente','bloqueado')`  
**Default:** `'em_dia'`  
**Definido em:** `database/migrations/002_create_phase1_tables.sql`

| Valor | Significado | Quando é Setado |
|-------|-------------|-----------------|
| `em_dia` | Aluno em dia | Quando pagamento é confirmado (paid/settled/approved) |
| `pendente` | Aluno com pendências | Quando cobrança está aguardando ou cancelada |
| `bloqueado` | Aluno bloqueado | Manualmente ou por regra de negócio |

### `billing_status` (tabela `enrollments`)

**Tipo:** `ENUM('draft','ready','generated','error')`  
**Default:** `'draft'`  
**Definido em:** `database/migrations/009_add_payment_plan_to_enrollments.sql`

| Valor | Significado | Quando é Setado |
|-------|-------------|-----------------|
| `draft` | Rascunho | Criação inicial da matrícula |
| `ready` | Pronto | Status intermediário (processando, pending, etc.) |
| `generated` | Gerado | Cobrança criada com sucesso (paid/settled/waiting) |
| `error` | Erro | Erro na geração ou cancelada/expirada |

---

## 3. Tabela Exata: `enrollments`

**Tabela:** `enrollments`  
**Database:** Mesmo banco do sistema

### Campos do Gateway (Localização Exata)

| Campo | Tipo | Posição | Migration |
|-------|------|---------|-----------|
| `gateway_provider` | VARCHAR(50) | Após `billing_status` | 030 |
| `gateway_charge_id` | VARCHAR(255) | Após `gateway_provider` | 030 |
| `gateway_last_status` | VARCHAR(50) | Após `gateway_charge_id` | 030 |
| `gateway_last_event_at` | DATETIME | Após `gateway_last_status` | 030 |
| `gateway_payment_url` | TEXT | Após `gateway_last_event_at` | **031 (NOVO)** |
| `billing_status` | ENUM(...) | Antes de `gateway_provider` | 009 |
| `financial_status` | ENUM(...) | Antes de `billing_status` | 002 |

### Estrutura SQL (Campos Gateway)

```sql
-- Migration 030
ALTER TABLE `enrollments`
ADD COLUMN `gateway_provider` varchar(50) DEFAULT NULL,
ADD COLUMN `gateway_charge_id` varchar(255) DEFAULT NULL,
ADD COLUMN `gateway_last_status` varchar(50) DEFAULT NULL,
ADD COLUMN `gateway_last_event_at` datetime DEFAULT NULL;

-- Migration 031 (NOVO)
ALTER TABLE `enrollments`
ADD COLUMN `gateway_payment_url` TEXT DEFAULT NULL 
COMMENT 'URL de pagamento (PIX QR Code ou Boleto) retornada pelo gateway'
AFTER `gateway_last_event_at`;
```

**Confirmado:** Todos os campos estão na tabela `enrollments`, não em tabela separada.

---

## 4. Resumo do Fluxo: "Gerar" e "Sincronizar"

### Fluxo: Gerar Cobrança

```
[UI] Botão "Gerar Cobrança Efí"
  ↓
[Frontend] Valida outstanding_amount > 0
  ↓
[API] POST /api/payments/generate
  ├─ PaymentsController::generate()
  │  ├─ Valida: autenticação, permissão, matrícula existe
  │  ├─ Valida: outstanding_amount > 0
  │  └─ Verifica idempotência:
  │     ├─ Se gateway_charge_id existe E billing_status='generated' E status não finalizado
  │     │  → Retorna cobrança existente (200 OK)
  │     └─ Caso contrário → Continua
  ↓
[Service] EfiPaymentService::createCharge()
  ├─ Obtém token OAuth (getAccessToken)
  ├─ POST /v1/charges (API EFI)
  ├─ Extrai: charge_id, status, payment_url
  └─ Atualiza banco (updateEnrollmentStatus):
     ├─ gateway_charge_id
     ├─ gateway_last_status
     ├─ gateway_payment_url ← NOVO (persistido)
     ├─ billing_status = 'generated'
     └─ gateway_last_event_at
  ↓
[Response] JSON: {ok: true, charge_id, status, payment_url}
  ↓
[UI] Exibe sucesso, recarrega página
```

**Pontos de Falha:**

| Ponto | Condição | HTTP | Mensagem Exibida |
|-------|----------|------|------------------|
| Saldo zero | outstanding_amount <= 0 | 400 | "Não é possível gerar cobrança: saldo devedor deve ser maior que zero" |
| Cobrança existe | Idempotência | 200 | "Cobrança já existe" (com dados) |
| Configuração | client_id/secret ausente | 400 | "Configuração do gateway não encontrada" |
| Autenticação EFI | Token não obtido | 400 | "Falha ao autenticar no gateway" |
| API EFI | Erro na criação | 400 | Mensagem de erro da EFI |

---

### Fluxo: Sincronizar Cobrança

```
[UI] Botão "Sincronizar Cobrança"
  ↓
[API] POST /api/payments/sync
  ├─ PaymentsController::sync()
  │  ├─ Valida: autenticação, permissão, matrícula existe
  │  └─ Valida: gateway_charge_id existe
  │     ├─ Se não existe → 400 "Nenhuma cobrança gerada..."
  │     └─ Caso contrário → Continua
  ↓
[Service] EfiPaymentService::syncCharge()
  ├─ GET /v1/charges/{charge_id} (API EFI)
  ├─ Extrai: status, payment_url
  ├─ Mapeia status:
  │  ├─ billing_status = mapGatewayStatusToBillingStatus(status)
  │  └─ financial_status = mapGatewayStatusToFinancialStatus(status)
  └─ Atualiza banco:
     ├─ gateway_last_status
     ├─ gateway_last_event_at
     ├─ billing_status
     ├─ financial_status (se mapeado)
     └─ gateway_payment_url (se não existir e API retornar)
  ↓
[Response] JSON: {ok: true, charge_id, status, billing_status, financial_status, payment_url}
  ↓
[UI] Exibe sucesso, recarrega página
```

**Pontos de Falha:**

| Ponto | Condição | HTTP | Mensagem Exibida |
|-------|----------|------|------------------|
| Sem cobrança | gateway_charge_id vazio | 400 | "Nenhuma cobrança gerada para esta matrícula. Gere uma cobrança primeiro." |
| API EFI | Erro na consulta | 502 | "Não foi possível consultar status da cobrança na EFI. Verifique se a cobrança existe ou se há problemas de conexão." |
| Exceção | Erro inesperado | 500 | "Erro ao sincronizar cobrança. Tente novamente mais tarde." |

---

## 5. Mapeamento de Status (Resumo)

### Status EFI → `billing_status`

```
paid, settled, waiting → 'generated'
unpaid, refunded, canceled, expired → 'error'
outros → 'ready'
```

### Status EFI → `financial_status`

```
paid, settled, approved → 'em_dia'
canceled, expired → 'pendente'
waiting, unpaid, pending, processing, new → 'pendente'
outros → null (não altera)
```

**Importante:** `financial_status` só é atualizado quando há mapeamento explícito. Status desconhecidos não alteram o status financeiro.

---

## 6. Checklist de Implementação

- [x] Migration 031 criada e testada
- [x] `gateway_payment_url` persistido em `createCharge()`
- [x] `updateEnrollmentStatus()` aceita `payment_url`
- [x] `mapGatewayStatusToFinancialStatus()` implementado
- [x] `syncCharge()` implementado
- [x] `PaymentsController::sync()` criado
- [x] Rota `POST /api/payments/sync` adicionada
- [x] UI atualizada (link de pagamento + botão sincronizar)
- [x] Validações robustas (saldo, idempotência)
- [x] Logs seguros (sem secrets)
- [x] Tratamento de erros completo

---

## 7. Arquivos Criados/Modificados

### Novos
- `database/migrations/031_add_gateway_payment_url_to_enrollments.sql`
- `database/migrations/031_rollback_add_gateway_payment_url.sql`
- `.docs/IMPLEMENTACAO_SINCRONIZACAO_EFI.md`
- `.docs/RESUMO_IMPLEMENTACAO_EFI.md` (este arquivo)

### Modificados
- `app/Services/EfiPaymentService.php`
- `app/Controllers/PaymentsController.php`
- `app/routes/web.php`
- `app/Views/alunos/matricula_show.php`

---

**Status:** ✅ **Implementação completa e pronta para produção**
