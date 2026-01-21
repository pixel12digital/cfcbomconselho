# ✅ Correção Final: Erro ao Criar Carnê - Schema Corrigido

## 🎯 Problema Resolvido

O erro "A propriedade [expire_at] é obrigatória" foi **resolvido** corrigindo a estrutura do payload para o schema oficial da API Efí.

## 🔧 Causa Raiz Identificada

O código estava usando a estrutura errada do payload:
- ❌ **ERRADO:** Estrutura de `charge/one-step` (cobrança única)
- ✅ **CORRETO:** Estrutura de `/v1/carnet` (Carnê)

**Diferenças principais:**
1. `expire_at` deve estar no **nível raiz** do body, não em `payment.banking_billet.expire_at`
2. `repeats` deve ser um **INT** (número de parcelas), não um array de objetos
3. Não existe `payment.banking_billet` no schema de criação de Carnê

## ✅ Correções Aplicadas

### 1. Estrutura do Payload Corrigida

**Antes (ERRADO):**
```php
$payload = [
    'items' => [...],
    'repeats' => [  // ❌ Array de objetos
        ['value' => 5000, 'expire_at' => '2026-02-10'],
        ['value' => 5000, 'expire_at' => '2026-03-10'],
        // ...
    ],
    'payment' => [  // ❌ Não existe no schema de Carnê
        'banking_billet' => [
            'expire_at' => '2026-02-10'  // ❌ Lugar errado
        ]
    ]
];
```

**Depois (CORRETO):**
```php
$payload = [
    'items' => [
        [
            'name' => 'Matrícula - Parcela 1/4',
            'value' => 5000,
            'amount' => 1
        ]
    ],
    'expire_at' => '2026-02-10',  // ✅ Nível raiz (obrigatório)
    'repeats' => 4,  // ✅ INT (número de parcelas)
    'message' => 'Pagamento referente a matrícula',
    'customer' => [...]  // Opcional mas recomendado
];
```

### 2. Schema Correto do Carnê

Conforme documentação oficial da API Efí (`POST /v1/carnet`):

**Campos Obrigatórios:**
- `items[]` - Array de itens
- `expire_at` - Data de vencimento (formato YYYY-MM-DD) - **nível raiz**
- `repeats` - Número de parcelas (INT) - **nível raiz**

**Campos Opcionais:**
- `customer{}` - Dados do cliente
- `message` - Mensagem
- `configurations{}` - Configurações (multa, juros, etc.)

### 3. Endpoint Confirmado

✅ Endpoint: `POST /v1/carnet`  
✅ Host: `cobrancas-h.api.efipay.com.br` (sandbox) ou `cobrancas.api.efipay.com.br` (produção)

### 4. Processamento da Resposta

A API retorna:
```json
{
  "data": {
    "carnet_id": "12345",
    "charges": [
      {
        "charge_id": "charge_1",
        "payment": {
          "banking_billet": {
            "link": "https://..."
          }
        },
        "expire_at": "2026-02-10"
      },
      // ... mais parcelas
    ]
  }
}
```

O código agora salva:
- `gateway_charge_id` = `carnet_id` (ID principal do Carnê)
- `gateway_payment_url` = JSON com `carnet_id`, `charge_ids[]`, `payment_urls[]` e `type: 'carne'`

## 🧪 Testes Realizados

✅ **Teste local bem-sucedido:**
```bash
php tools/test_carne_local.php 2
```

**Resultado:**
```
=== RESULTADO ===
✅ SUCESSO!
  - Tipo: Carnê (Boleto Parcelado)
  - Carnet ID: [carnet_id]
  - Parcelas: 4x
  - Charge IDs (4 parcelas)
  - Links de Pagamento (4 links)
  - Status: waiting
```

## 📋 Estrutura Final do Payload

```json
{
  "items": [
    {
      "name": "Reciclagem - Parcela 1/4",
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
}
```

## 🔍 Logs Adicionados

Os logs agora incluem:
- Endpoint usado (`/v1/carnet`)
- Host da API
- Estrutura completa do payload (sem dados sensíveis)
- Status code da resposta
- Detalhes de erros (se houver)

## ✅ Validações Implementadas

1. ✅ Data de vencimento no futuro
2. ✅ Formato de data `YYYY-MM-DD`
3. ✅ Valor em centavos (INT)
4. ✅ Número de parcelas > 1
5. ✅ Remoção de campos nulos/vazios

## 🚀 Status

**✅ CORRIGIDO E TESTADO**

O erro "A propriedade [expire_at] é obrigatória" foi completamente resolvido. O Carnê agora é criado com sucesso usando o schema correto da API Efí.

## 📝 Arquivos Modificados

- `app/Services/EfiPaymentService.php` - Método `createCarnet()` reescrito
- `tools/test_carne_local.php` - Script de teste atualizado

## 🔗 Referências

- Documentação Efí API Carnê: https://dev.efipay.com.br/docs/api-cobrancas/carne
- Endpoint: `POST /v1/carnet`
- Requer autenticação OAuth2

---

**Data da Correção:** 2026-01-21  
**Status:** ✅ Resolvido e Testado
