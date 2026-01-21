# Correção: Erro ao Criar Carnê - expire_at obrigatório

## 🐛 Problema Identificado

Ao tentar criar um Carnê (boleto parcelado) para um aluno, o sistema retornava o erro:

```
Erro 400: Erro ao criar Carnê: A propriedade [expire_at] é obrigatória.
```

## 🔍 Causa Raiz

A API da Efí exige que o campo `expire_at` (data de vencimento) esteja presente no objeto `banking_billet` dentro de `payment`, além de estar em cada item do array `repeats`.

O código original estava definindo `expire_at` apenas nos `repeats`, mas não no `banking_billet`.

## ✅ Correções Aplicadas

### 1. Adicionado `expire_at` no `banking_billet`

**Arquivo:** `app/Services/EfiPaymentService.php`  
**Método:** `createCarnet()`

**Antes:**
```php
$payload['payment'] = [
    'banking_billet' => [
        'message' => 'Pagamento referente a matrícula'
    ]
];
```

**Depois:**
```php
$payload['payment'] = [
    'banking_billet' => [
        'expire_at' => $firstDueDate, // Data de vencimento obrigatória
        'message' => 'Pagamento referente a matrícula'
    ]
];
```

### 2. Validação de Datas

Adicionada validação para garantir que as datas de vencimento não estejam no passado:

```php
// Validar que a data está no futuro
if (strtotime($dueDate) < time()) {
    $this->efiLog('WARNING', 'createCarnet: Data de vencimento no passado, ajustando', [
        'enrollment_id' => $enrollment['id'],
        'parcela' => $i + 1,
        'data_original' => $dueDate
    ]);
    // Se a data estiver no passado, usar pelo menos 3 dias a partir de hoje
    $dueDate = date('Y-m-d', strtotime('+3 days'));
}
```

### 3. Logs Melhorados

Adicionados logs detalhados para facilitar o debug:

- Log do payload preparado (sem dados sensíveis)
- Log de erros com detalhes específicos
- Validação de estrutura do payload antes do envio

### 4. Tratamento de Erros Aprimorado

Melhorado o tratamento de erros para extrair e exibir informações mais detalhadas da resposta da API:

```php
// Extrair detalhes específicos de validação
if (isset($responseData['errors']) && is_array($responseData['errors'])) {
    $errorDetails = $responseData['errors'];
}
```

## 🧪 Script de Teste Local

Criado script de teste para validar a correção localmente:

**Arquivo:** `tools/test_carne_local.php`

**Uso:**
```bash
php tools/test_carne_local.php [enrollment_id]
```

**Exemplo:**
```bash
php tools/test_carne_local.php 2
```

O script:
- Carrega a matrícula especificada
- Valida os dados necessários
- Tenta criar o Carnê
- Exibe o resultado detalhado

## 📋 Estrutura do Payload Corrigido

O payload agora está na estrutura correta esperada pela API Efí:

```json
{
  "items": [
    {
      "name": "Matrícula - Parcela 1/4",
      "value": 250000,
      "amount": 1
    }
  ],
  "repeats": [
    {
      "value": 250000,
      "expire_at": "2026-02-10"
    },
    {
      "value": 250000,
      "expire_at": "2026-03-10"
    },
    // ... mais parcelas
  ],
  "payment": {
    "banking_billet": {
      "expire_at": "2026-02-10",  // ✅ OBRIGATÓRIO - Adicionado na correção
      "message": "Pagamento referente a matrícula"
    }
  },
  "customer": {
    "name": "Nome do Aluno",
    "cpf": "12345678901",
    // ... outros dados
  }
}
```

## ✅ Validações Adicionais

1. **Data de vencimento no futuro:** Valida se a data não está no passado
2. **Formato de data:** Garante formato `YYYY-MM-DD`
3. **Valor em centavos:** Converte corretamente para inteiro
4. **Número de parcelas:** Valida que é maior que 1

## 🚀 Próximos Passos

1. **Testar em produção:**
   - Acessar uma matrícula com parcelas > 1
   - Tentar gerar cobrança como Carnê
   - Verificar se o erro foi resolvido

2. **Monitorar logs:**
   - Verificar `storage/logs/php_errors.log` para logs detalhados
   - Confirmar que não há mais erros de `expire_at`

3. **Validar resposta:**
   - Confirmar que o Carnê é criado com sucesso
   - Verificar que todas as parcelas têm datas corretas
   - Testar o link de pagamento

## 📝 Notas Técnicas

- A API Efí exige `expire_at` tanto nos `repeats` quanto no `banking_billet`
- O `expire_at` no `banking_billet` deve ser a data da primeira parcela
- Cada item em `repeats` deve ter seu próprio `expire_at` calculado mensalmente
- O formato de data deve ser `YYYY-MM-DD` (ISO 8601)

## 🔗 Referências

- Documentação Efí API Carnê: https://dev.efipay.com.br/docs/api-cobrancas/carne
- Endpoint: `POST /v1/carnet`
- Requer autenticação OAuth2

---

**Data da Correção:** 2024  
**Arquivos Modificados:**
- `app/Services/EfiPaymentService.php`
- `tools/test_carne_local.php` (novo)
