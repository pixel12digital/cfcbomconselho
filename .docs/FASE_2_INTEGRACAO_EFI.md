# Fase 2 - Integração EFÍ (Gerencianet) - Relatório de Implementação

**Data:** 2024  
**Status:** ✅ Implementado

---

## Objetivo

Implementar integração com gateway de pagamento EFÍ (Gerencianet) para gerar cobranças a partir de matrículas, com suporte a webhooks para atualização automática de status.

---

## Arquivos Criados

### 1. Service Layer
- ✅ **app/Services/EfiPaymentService.php**
  - `createCharge($enrollment)` - Cria cobrança na Efí
  - `parseWebhook($requestPayload)` - Processa webhook da Efí
  - `getChargeStatus($chargeId)` - Consulta status de cobrança
  - Métodos privados para autenticação e requisições HTTP

### 2. Controller
- ✅ **app/Controllers/PaymentsController.php**
  - `generate()` - Endpoint POST /api/payments/generate
  - `webhookEfi()` - Endpoint POST /api/payments/webhook/efi

### 3. Rotas
- ✅ **app/routes/web.php** (atualizado)
  - `POST /api/payments/generate` - Geração de cobrança (autenticado)
  - `POST /api/payments/webhook/efi` - Webhook (público, com validação)

### 4. Views
- ✅ **app/Views/alunos/matricula_show.php** (atualizado)
  - Função JavaScript `gerarCobrancaEfi()` implementada com AJAX
  - Exibição de informações da cobrança (charge_id, status, último evento)
  - Lógica para ocultar botão quando cobrança já existe

### 5. Documentação
- ✅ **.docs/EFI_STATUS_MAPPING.md**
  - Mapeamento completo de status do gateway
  - Fluxo de estados e transições
  - Exemplos de payload do webhook

---

## Funcionalidades Implementadas

### 1. Geração de Cobrança

**Endpoint:** `POST /api/payments/generate`

**Validações:**
- ✅ Autenticação obrigatória
- ✅ Permissão: ADMIN ou SECRETARIA
- ✅ Verificação de saldo devedor > 0
- ✅ Idempotência: não permite gerar 2 cobranças ativas

**Payload:**
```json
{
  "enrollment_id": 123
}
```

**Response (Sucesso):**
```json
{
  "ok": true,
  "charge_id": "123456",
  "status": "waiting",
  "payment_url": "https://..."
}
```

**Response (Erro):**
```json
{
  "ok": false,
  "message": "Cobrança já existe"
}
```

### 2. Webhook da Efí

**Endpoint:** `POST /api/payments/webhook/efi`

**Características:**
- ✅ Público (sem autenticação de sessão)
- ✅ Validação de assinatura (se `EFI_WEBHOOK_SECRET` configurado)
- ✅ Processamento idempotente
- ✅ Sempre retorna 200 (evita retry infinito)

**Payload Normalizado:**
```json
{
  "charge_id": "123456",
  "status": "paid",
  "occurred_at": "2024-01-15T10:30:00Z"
}
```

**Response:**
```json
{
  "ok": true,
  "processed": true,
  "charge_id": "123456",
  "status": "paid",
  "billing_status": "generated"
}
```

### 3. Atualização de Status

**Campos atualizados no `enrollments`:**
- `gateway_provider` = 'efi'
- `gateway_charge_id` = ID da cobrança
- `gateway_last_status` = Status do gateway
- `gateway_last_event_at` = Data/hora do evento
- `billing_status` = Status mapeado (draft/ready/generated/error)

---

## Configuração (.env)

Variáveis necessárias:

```env
# EFÍ (Gerencianet) - Gateway de Pagamento
EFI_CLIENT_ID=seu_client_id_aqui
EFI_CLIENT_SECRET=seu_client_secret_aqui
EFI_SANDBOX=true
EFI_CERT_PATH=/caminho/para/certificado.p12
EFI_WEBHOOK_SECRET=seu_webhook_secret_aqui
```

**Observações:**
- `EFI_SANDBOX`: `true` (sandbox) ou `false` (produção)
- `EFI_CERT_PATH`: Opcional, apenas se certificado for exigido
- `EFI_WEBHOOK_SECRET`: Opcional, mas recomendado para validar assinatura

---

## Regras de Negócio Implementadas

### 1. Valor da Cobrança
- ✅ **Sempre usa `outstanding_amount`** (saldo devedor)
- ✅ Converte para centavos (multiplica por 100)
- ✅ Valida que saldo > 0 antes de gerar

### 2. Parcelamento
- ✅ Se `installments > 1`, cria cobrança parcelada no gateway
- ✅ Controle de parcelas fica no gateway (MVP)
- ✅ Se `installments = 1`, cria cobrança à vista (PIX ou Boleto conforme `payment_method`)

### 3. Idempotência
- ✅ Verifica se já existe `gateway_charge_id` e `billing_status = 'generated'`
- ✅ Bloqueia geração se status não for `canceled`, `expired` ou `error`
- ✅ Permite regerar apenas se cobrança anterior foi cancelada/expirada/erro

### 4. Dados do Pagador
- ✅ Usa dados do aluno (CPF, nome, email, telefone)
- ✅ Limpa CPF (remove caracteres não numéricos)
- ✅ Valida CPF (11 dígitos)
- ✅ Inclui endereço para cobrança parcelada (cartão)

### 5. Metadata
- ✅ Inclui `enrollment_id`, `cfc_id`, `student_id` no metadata da cobrança
- ✅ Facilita rastreamento e busca no webhook

---

## Segurança

### 1. Autenticação
- ✅ Endpoint de geração requer autenticação
- ✅ Verifica permissão (ADMIN ou SECRETARIA)
- ✅ Valida que matrícula pertence ao CFC do usuário

### 2. Webhook
- ✅ Valida assinatura HMAC-SHA256 (se `EFI_WEBHOOK_SECRET` configurado)
- ✅ Sempre retorna 200 para evitar retry infinito
- ✅ Loga eventos mesmo quando ignora (para auditoria)

### 3. Logs
- ✅ Nunca loga `client_secret` ou `cert_path`
- ✅ Loga apenas erros técnicos (sem dados sensíveis)
- ✅ Usa `error_log()` do PHP para logs internos

---

## Mapeamento de Status

Ver documentação completa em `.docs/EFI_STATUS_MAPPING.md`.

**Resumo:**
- `paid`, `settled`, `waiting` → `billing_status = 'generated'`
- `unpaid`, `refunded`, `canceled`, `expired` → `billing_status = 'error'`
- Outros → `billing_status = 'ready'`

---

## Compatibilidade

### ✅ Fluxo Financeiro Manual
- **Status:** Não quebrado
- **Funcionalidade:** Continua funcionando normalmente
- **Observação:** `financial_status` não é alterado automaticamente (conforme solicitado)

### ✅ Bloqueio via `financial_status`
- **Status:** Não quebrado
- **Funcionalidade:** Continua usando apenas `financial_status`
- **Observação:** Gateway não interfere no bloqueio de agendamento

---

## Exemplos de Uso

### 1. Gerar Cobrança (JavaScript)

```javascript
fetch('/api/payments/generate', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        enrollment_id: 123
    })
})
.then(response => response.json())
.then(data => {
    if (data.ok) {
        console.log('Cobrança gerada:', data.charge_id);
        console.log('Link:', data.payment_url);
    }
});
```

### 2. Webhook (Exemplo de Payload)

```json
{
  "identifiers": {
    "charge_id": "123456"
  },
  "current": {
    "status": "paid"
  },
  "previous": {
    "status": "waiting"
  },
  "occurred_at": "2024-01-15T10:30:00Z"
}
```

---

## Testes de Idempotência

### Cenário 1: Tentar gerar cobrança duplicada
1. Gerar cobrança para enrollment_id = 123
2. Tentar gerar novamente para o mesmo enrollment_id
3. **Resultado esperado:** Retorna erro "Cobrança já existe"

### Cenário 2: Regenerar após cancelamento
1. Gerar cobrança
2. Webhook informa status `canceled`
3. Tentar gerar novamente
4. **Resultado esperado:** Permite gerar nova cobrança

---

## Próximos Passos (Futuro)

1. **Atualização automática de `financial_status`**
   - Implementar após confirmação explícita
   - Mapear status de pagamento para `financial_status`

2. **Notificações**
   - Enviar email quando cobrança for gerada
   - Notificar quando pagamento for confirmado

3. **Dashboard Financeiro**
   - Exibir cobranças pendentes
   - Relatórios de pagamentos

4. **Recorrência/Mensalidade**
   - Implementar se necessário no futuro
   - Usar `gateway_subscription_id` (já preparado no schema)

---

## Resumo Executivo

| Item | Status | Observações |
|------|--------|-------------|
| **Service Layer** | ✅ Implementado | EfiPaymentService completo |
| **Controller** | ✅ Implementado | PaymentsController com 2 endpoints |
| **Rotas** | ✅ Configuradas | Rotas neutras e seguras |
| **UI/JavaScript** | ✅ Implementado | AJAX funcional com feedback |
| **Webhook** | ✅ Implementado | Validação e processamento idempotente |
| **Idempotência** | ✅ Garantida | Não permite duplicação |
| **Segurança** | ✅ Implementada | Autenticação e validação de assinatura |
| **Documentação** | ✅ Criada | Mapeamento de status documentado |

**Total de arquivos criados:** 2  
**Total de arquivos alterados:** 3  
**Total de endpoints criados:** 2

---

✅ **Fase 2 concluída com sucesso!** Sistema pronto para gerar cobranças na Efí e processar webhooks.
