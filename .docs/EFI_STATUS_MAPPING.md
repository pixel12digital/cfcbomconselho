# Mapeamento de Status EFÍ (Gerencianet)

**Data:** 2024  
**Gateway:** EFÍ (Gerencianet)

---

## Objetivo

Documentar o mapeamento entre os status recebidos do webhook da Efí e os status internos do sistema (`billing_status` e `gateway_last_status`).

---

## Status do Gateway (gateway_last_status)

Os status abaixo são armazenados diretamente em `gateway_last_status` conforme recebidos do webhook da Efí:

### Status de Sucesso/Pagamento
- `paid` - Pagamento confirmado
- `settled` - Pagamento liquidado
- `waiting` - Aguardando pagamento

### Status de Erro/Falha
- `unpaid` - Não pago
- `refunded` - Reembolsado
- `canceled` - Cancelado
- `expired` - Expirado

### Status Intermediários
- `new` - Nova cobrança
- `processing` - Processando
- `pending` - Pendente

---

## Mapeamento para billing_status

O campo `billing_status` é um ENUM interno com 4 valores: `'draft'`, `'ready'`, `'generated'`, `'error'`.

### Regras de Mapeamento

| Status Gateway | billing_status | Observação |
|----------------|----------------|------------|
| `paid`, `settled`, `waiting` | `generated` | Cobrança gerada e aguardando/confirmada |
| `unpaid`, `refunded`, `canceled`, `expired` | `error` | Cobrança com problema ou cancelada |
| `new`, `processing`, `pending` | `ready` | Status intermediário, pronto para processar |
| Qualquer outro | `ready` | Fallback para status desconhecidos |

---

## Fluxo de Estados

```
[draft] → [ready] → [generated] → [error]
   ↑         ↑           ↓            ↓
   └─────────┴───────────┴────────────┘
```

### Transições

1. **draft → ready**
   - Quando matrícula é preparada com parcelamento
   - Estado inicial antes de gerar cobrança

2. **ready → generated**
   - Quando cobrança é criada com sucesso na Efí
   - Status do gateway: `new`, `waiting`, `paid`, etc.

3. **generated → error**
   - Quando webhook informa: `unpaid`, `canceled`, `expired`, `refunded`
   - Quando criação da cobrança falha

4. **error → ready**
   - Permite tentar gerar novamente após erro
   - Botão "Gerar Cobrança" fica disponível novamente

---

## Exemplo de Payload do Webhook

### Estrutura Normalizada

```json
{
  "charge_id": "123456",
  "status": "paid",
  "occurred_at": "2024-01-15T10:30:00Z"
}
```

### Payload Real da Efí (exemplo)

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

## Observações Importantes

1. **Idempotência**: O webhook pode ser chamado múltiplas vezes. O sistema sempre atualiza `gateway_last_event_at` para rastrear o último evento.

2. **Status não encontrado**: Se o status recebido não estiver na lista conhecida, mapeia para `ready` (status intermediário seguro).

3. **financial_status**: Por enquanto, **NÃO** alteramos `financial_status` automaticamente baseado no webhook. Isso será implementado em fase futura após confirmação explícita.

4. **Logs**: Todos os eventos do webhook são logados (sem dados sensíveis) para auditoria.

---

## Implementação

O mapeamento é implementado em `app/Services/EfiPaymentService.php` no método `mapGatewayStatusToBillingStatus()`.

```php
private function mapGatewayStatusToBillingStatus($gatewayStatus)
{
    $successStatuses = ['paid', 'settled', 'waiting'];
    $errorStatuses = ['unpaid', 'refunded', 'canceled', 'expired'];
    
    if (in_array(strtolower($gatewayStatus), $successStatuses)) {
        return 'generated';
    }
    
    if (in_array(strtolower($gatewayStatus), $errorStatuses)) {
        return 'error';
    }
    
    return 'ready';
}
```

---

## Testes

### Status de Teste (Sandbox)

Na sandbox da Efí, os seguintes status podem ser simulados:
- `waiting` - Simula aguardando pagamento
- `paid` - Simula pagamento confirmado
- `unpaid` - Simula não pago
- `canceled` - Simula cancelado

---

## Referências

- [Documentação API Efí](https://dev.gerencianet.com.br/docs/api-pix)
- [Webhooks Efí](https://dev.gerencianet.com.br/docs/webhooks)
