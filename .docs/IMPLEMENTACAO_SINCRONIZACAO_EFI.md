# Implementação: Sincronização Manual EFI + Persistência de Link de Pagamento

**Data:** 2024  
**Status:** ✅ Implementado para Produção

---

## Resumo Executivo

Implementação completa de sincronização manual de status de cobrança EFI e persistência do link de pagamento, pronta para produção.

### Funcionalidades Implementadas

1. ✅ **Persistência de Link de Pagamento** - `gateway_payment_url` salvo no banco
2. ✅ **Sincronização Manual** - Botão "Sincronizar Cobrança" consulta EFI e atualiza status
3. ✅ **Atualização Automática de `financial_status`** - Quando cobrança está paga, atualiza para 'em_dia'
4. ✅ **Idempotência Aprimorada** - Não gera cobrança duplicada, retorna existente
5. ✅ **Validações Robustas** - Verifica saldo devedor, cobrança existente, etc.

---

## 1. Status EFI Encontrados na API

### Status Retornados por `getChargeStatus()`

Com base na documentação da EFI e no código implementado, os seguintes status podem ser retornados:

| Status EFI | Descrição | Mapeamento `billing_status` | Mapeamento `financial_status` |
|------------|-----------|----------------------------|------------------------------|
| `paid` | Pagamento confirmado | `generated` | `em_dia` |
| `settled` | Pagamento liquidado | `generated` | `em_dia` |
| `approved` | Pagamento aprovado | `generated` | `em_dia` |
| `waiting` | Aguardando pagamento | `generated` | `pendente` |
| `unpaid` | Não pago | `error` | `pendente` |
| `pending` | Pendente | `ready` | `pendente` |
| `processing` | Processando | `ready` | `pendente` |
| `new` | Nova cobrança | `ready` | `pendente` |
| `canceled` | Cancelado | `error` | `pendente` |
| `expired` | Expirado | `error` | `pendente` |
| `refunded` | Reembolsado | `error` | `null` (não altera) |

**Observação:** A resposta real da API EFI pode variar. O sistema trata status desconhecidos como `ready` (billing_status) e não altera `financial_status` (retorna `null`).

---

## 2. Enums Reais do Sistema

### `financial_status` (tabela `enrollments`)

**Tipo:** `ENUM('em_dia','pendente','bloqueado')`  
**Default:** `'em_dia'`  
**Definido em:** Migration 002 (`database/migrations/002_create_phase1_tables.sql`)

| Valor | Significado |
|-------|-------------|
| `em_dia` | Aluno em dia com pagamentos |
| `pendente` | Aluno com pagamentos pendentes |
| `bloqueado` | Aluno bloqueado (não pode agendar aulas) |

### `billing_status` (tabela `enrollments`)

**Tipo:** `ENUM('draft','ready','generated','error')`  
**Default:** `'draft'`  
**Definido em:** Migration 009 (`database/migrations/009_add_payment_plan_to_enrollments.sql`)

| Valor | Significado |
|-------|-------------|
| `draft` | Rascunho (pronto para gerar cobrança) |
| `ready` | Pronto (status intermediário) |
| `generated` | Cobrança gerada no gateway |
| `error` | Erro na geração ou cobrança cancelada/expirada |

---

## 3. Tabela Exata: `enrollments`

**Tabela:** `enrollments`  
**Schema:** Definido em múltiplas migrations (002, 009, 010, 030, 031)

### Campos Relacionados ao Gateway

| Campo | Tipo | Null | Default | Descrição |
|-------|------|------|---------|-----------|
| `gateway_provider` | VARCHAR(50) | YES | NULL | Provedor do gateway ('efi', 'asaas', etc.) |
| `gateway_charge_id` | VARCHAR(255) | YES | NULL | ID da cobrança no gateway |
| `gateway_last_status` | VARCHAR(50) | YES | NULL | Último status recebido do gateway |
| `gateway_last_event_at` | DATETIME | YES | NULL | Data/hora do último evento recebido |
| `gateway_payment_url` | TEXT | YES | NULL | **NOVO** - URL de pagamento (PIX ou Boleto) |
| `billing_status` | ENUM(...) | NO | 'draft' | Status da geração de cobrança |
| `financial_status` | ENUM(...) | NO | 'em_dia' | Status financeiro do aluno |

### Índices

- `KEY gateway_provider (gateway_provider)`
- `KEY gateway_charge_id (gateway_charge_id)`
- `KEY gateway_last_event_at (gateway_last_event_at)`
- `KEY billing_status (billing_status)`

---

## 4. Resumo do Fluxo: "Gerar" e "Sincronizar"

### Fluxo: Gerar Cobrança (`POST /api/payments/generate`)

```
1. Usuário clica "Gerar Cobrança Efí"
   ↓
2. Frontend valida: outstanding_amount > 0
   ↓
3. POST /api/payments/generate {enrollment_id: X}
   ↓
4. PaymentsController::generate()
   ├─ Valida autenticação e permissão
   ├─ Busca matrícula (Enrollment::findWithDetails)
   ├─ Valida outstanding_amount > 0
   └─ Verifica idempotência:
      ├─ Se gateway_charge_id existe E billing_status = 'generated' E status não é finalizado
      │  → Retorna cobrança existente (200 OK)
      └─ Caso contrário → Continua
   ↓
5. EfiPaymentService::createCharge()
   ├─ Valida configuração (client_id, client_secret)
   ├─ Obtém token OAuth (getAccessToken)
   ├─ Monta payload da cobrança
   ├─ POST /v1/charges (API EFI)
   ├─ Extrai: charge_id, status, payment_url
   └─ Atualiza banco (updateEnrollmentStatus):
      ├─ gateway_charge_id
      ├─ gateway_last_status
      ├─ gateway_payment_url ← NOVO
      ├─ billing_status = 'generated'
      └─ gateway_last_event_at
   ↓
6. Retorna JSON:
   {
     "ok": true,
     "charge_id": "123456",
     "status": "waiting",
     "payment_url": "https://..."
   }
   ↓
7. Frontend exibe sucesso e recarrega página
```

**Pontos de Falha e Mensagens:**

| Ponto | Condição | HTTP | Mensagem |
|-------|----------|------|----------|
| Autenticação | Sem sessão | 401 | "Não autenticado" |
| Permissão | Não é ADMIN/SECRETARIA | 403 | "Sem permissão" |
| Matrícula não encontrada | enrollment_id inválido | 404 | "Matrícula não encontrada" |
| Saldo zero | outstanding_amount <= 0 | 400 | "Não é possível gerar cobrança: saldo devedor deve ser maior que zero" |
| Cobrança já existe | Idempotência ativada | 200 | "Cobrança já existe" (com dados existentes) |
| Configuração | client_id/secret ausente | 400 | "Configuração do gateway não encontrada" |
| Autenticação EFI | Token não obtido | 400 | "Falha ao autenticar no gateway" |
| API EFI | Erro na criação | 400 | Mensagem de erro da EFI |
| Aluno não encontrado | student_id inválido | 400 | "Aluno não encontrado" |

---

### Fluxo: Sincronizar Cobrança (`POST /api/payments/sync`)

```
1. Usuário clica "Sincronizar Cobrança"
   ↓
2. POST /api/payments/sync {enrollment_id: X}
   ↓
3. PaymentsController::sync()
   ├─ Valida autenticação e permissão
   ├─ Busca matrícula (Enrollment::findWithDetails)
   └─ Valida gateway_charge_id existe:
      ├─ Se não existe → 400 "Nenhuma cobrança gerada..."
      └─ Caso contrário → Continua
   ↓
4. EfiPaymentService::syncCharge()
   ├─ Valida configuração
   ├─ GET /v1/charges/{charge_id} (API EFI)
   ├─ Extrai: status, payment_url
   ├─ Mapeia status:
      ├─ billing_status = mapGatewayStatusToBillingStatus(status)
      └─ financial_status = mapGatewayStatusToFinancialStatus(status)
   └─ Atualiza banco:
      ├─ gateway_last_status
      ├─ gateway_last_event_at
      ├─ billing_status
      ├─ financial_status (se mapeado)
      └─ gateway_payment_url (se não existir e API retornar)
   ↓
5. Retorna JSON:
   {
     "ok": true,
     "charge_id": "123456",
     "status": "paid",
     "billing_status": "generated",
     "financial_status": "em_dia",
     "payment_url": "https://..."
   }
   ↓
6. Frontend exibe sucesso e recarrega página
```

**Pontos de Falha e Mensagens:**

| Ponto | Condição | HTTP | Mensagem |
|-------|----------|------|----------|
| Autenticação | Sem sessão | 401 | "Não autenticado" |
| Permissão | Não é ADMIN/SECRETARIA | 403 | "Sem permissão" |
| Matrícula não encontrada | enrollment_id inválido | 404 | "Matrícula não encontrada" |
| Sem cobrança | gateway_charge_id vazio | 400 | "Nenhuma cobrança gerada para esta matrícula. Gere uma cobrança primeiro." |
| Configuração | client_id/secret ausente | 502 | "Configuração do gateway não encontrada" |
| API EFI | Erro na consulta | 502 | "Não foi possível consultar status da cobrança na EFI. Verifique se a cobrança existe ou se há problemas de conexão." |
| Exceção | Erro inesperado | 500 | "Erro ao sincronizar cobrança. Tente novamente mais tarde." |

---

## 5. Mapeamento de Status

### `mapGatewayStatusToBillingStatus()`

```php
paid, settled, waiting → 'generated'
unpaid, refunded, canceled, expired → 'error'
outros → 'ready'
```

### `mapGatewayStatusToFinancialStatus()`

```php
paid, settled, approved → 'em_dia'
canceled, expired → 'pendente'
waiting, unpaid, pending, processing, new → 'pendente'
outros → null (não altera)
```

**Observação:** `financial_status` só é atualizado quando há mapeamento explícito. Status desconhecidos não alteram o status financeiro.

---

## 6. Arquivos Modificados

### Novos Arquivos

1. `database/migrations/031_add_gateway_payment_url_to_enrollments.sql`
2. `database/migrations/031_rollback_add_gateway_payment_url.sql`
3. `.docs/IMPLEMENTACAO_SINCRONIZACAO_EFI.md` (este arquivo)

### Arquivos Modificados

1. `app/Services/EfiPaymentService.php`
   - `updateEnrollmentStatus()` - Aceita `payment_url`
   - `mapGatewayStatusToFinancialStatus()` - **NOVO** método
   - `syncCharge()` - **NOVO** método

2. `app/Controllers/PaymentsController.php`
   - `generate()` - Validação de saldo e idempotência melhorada
   - `sync()` - **NOVO** método

3. `app/routes/web.php`
   - Adicionada rota `POST /api/payments/sync`

4. `app/Views/alunos/matricula_show.php`
   - Exibição de `gateway_payment_url` (link clicável)
   - Botão "Sincronizar Cobrança"
   - Validação de `outstanding_amount` no frontend
   - Função JavaScript `sincronizarCobrancaEfi()`

---

## 7. Configuração (.env)

### Variáveis Necessárias

```env
# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=seu_client_id_producao
EFI_CLIENT_SECRET=seu_client_secret_producao
EFI_SANDBOX=false
EFI_WEBHOOK_SECRET=seu_webhook_secret (opcional)
```

**Importante:**
- `EFI_SANDBOX=false` para produção
- Não usar sandbox (conforme solicitado)
- Secrets nunca são logados

---

## 8. Testes Locais

### Checklist de Teste

1. **Executar Migration 031**
   ```sql
   SOURCE database/migrations/031_add_gateway_payment_url_to_enrollments.sql;
   ```

2. **Criar Matrícula com Saldo Devedor**
   - Criar matrícula com `outstanding_amount > 0`
   - Definir `installments` (ex: 1 ou mais)
   - Verificar `billing_status = 'draft'`

3. **Gerar Cobrança EFI**
   - Clicar "Gerar Cobrança Efí"
   - Verificar no banco:
     ```sql
     SELECT gateway_charge_id, gateway_payment_url, billing_status, gateway_last_status 
     FROM enrollments 
     WHERE id = {enrollment_id};
     ```
   - Confirmar: `gateway_charge_id` preenchido, `gateway_payment_url` salvo

4. **Sincronizar Cobrança (sem pagar)**
   - Clicar "Sincronizar Cobrança"
   - Verificar atualização de `gateway_last_status` e `gateway_last_event_at`

5. **Simular Pagamento (se possível)**
   - Pagar cobrança na EFI (valor mínimo ou simulação)
   - Sincronizar novamente
   - Verificar: `financial_status = 'em_dia'` (se status = 'paid')

6. **Testar Idempotência**
   - Tentar gerar cobrança novamente
   - Verificar: retorna cobrança existente (não cria duplicada)

7. **Testar Validações**
   - Matrícula com `outstanding_amount = 0` → botão não aparece
   - Matrícula sem `gateway_charge_id` → botão sincronizar não aparece

---

## 9. Segurança

### Logs

- ✅ Nunca loga `client_id`, `client_secret`, `token`
- ✅ Loga apenas: `enrollment_id`, `charge_id`, `status`, `billing_status`, `financial_status`
- ✅ Erros técnicos logados sem dados sensíveis

### Tratamento de Erros

- ✅ Timeouts configurados (30s)
- ✅ Erros da API EFI não quebram a tela
- ✅ Mensagens amigáveis para o usuário
- ✅ HTTP codes apropriados (400, 401, 403, 404, 500, 502)

### Idempotência

- ✅ Não gera cobrança duplicada
- ✅ Retorna cobrança existente se já gerada
- ✅ Permite regerar apenas se status = 'canceled'/'expired'/'error'

---

## 10. Próximos Passos (Futuro)

1. **Cache de Token OAuth** - Melhorar performance
2. **Webhook Automático** - Atualizar status sem botão manual
3. **Notificações** - Email quando pagamento confirmado
4. **Dashboard Financeiro** - Exibir cobranças pendentes
5. **Relatórios** - Histórico de pagamentos via EFI

---

## 11. Rollback

Se necessário reverter a migration 031:

```sql
SOURCE database/migrations/031_rollback_add_gateway_payment_url.sql;
```

**Atenção:** Isso remove a coluna `gateway_payment_url` e todos os dados armazenados.

---

**Status:** ✅ **Implementação completa e pronta para produção**
