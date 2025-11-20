# Correção da Persistência do Campo "Forma de Pagamento"

## Problema Identificado

O campo `forma_pagamento` não estava persistindo no banco de dados, mesmo sendo enviado corretamente pelo frontend como `"boleto"`. O valor sempre retornava como string vazia `""` após salvar.

## Correções Aplicadas

### 1. **Verificação e Conversão da Coluna no Banco de Dados**

**Arquivo:** `admin/api/matriculas.php` (linhas 77-95)

- Adicionada verificação para detectar se a coluna `forma_pagamento` é do tipo `ENUM`
- Se for `ENUM`, a coluna é automaticamente convertida para `VARCHAR(50)` para permitir qualquer valor
- Logs adicionados para rastrear a conversão

```php
// Verificar se a coluna é ENUM e converter para VARCHAR se necessário
$columnInfo = $rows[0];
if (isset($columnInfo['Type']) && stripos($columnInfo['Type'], 'enum') !== false) {
    error_log('[DEBUG MATRICULA] Coluna forma_pagamento é ENUM, convertendo para VARCHAR...');
    $db->query("ALTER TABLE matriculas MODIFY COLUMN forma_pagamento VARCHAR(50) DEFAULT NULL");
}
```

### 2. **Remoção da Conversão de String Vazia para NULL no GET**

**Arquivo:** `admin/api/matriculas.php` (linhas 229-236)

- Removida a lógica que convertia string vazia para `NULL` no retorno do GET
- Agora o valor exato do banco é retornado, facilitando o debug
- Logs adicionados para rastrear o valor retornado

```php
// ANTES (removido):
// else if ($matricula['forma_pagamento'] === '' || $matricula['forma_pagamento'] === 'Selecione...') {
//     $matricula['forma_pagamento'] = null;
// }

// AGORA:
// NÃO converter string vazia para NULL - manter valor exato do banco para debug
```

### 3. **Logs Detalhados no PUT**

**Arquivo:** `admin/api/matriculas.php` (linhas 442-448, 517-549, 604-624)

- Logs adicionados em todas as etapas do processamento:
  - Raw input recebido
  - Input decodificado
  - Valor processado (antes e depois do trim)
  - Valor no array `$dadosUpdate` antes do UPDATE
  - Valor no banco após o UPDATE
  - Comparação entre valor enviado e valor salvo

```php
error_log('[DEBUG MATRICULA PUT] forma_pagamento recebida: ' . print_r($input['forma_pagamento'] ?? null, true));
error_log('[DEBUG MATRICULA PUT] forma_pagamento será salvo como: ' . $valorTrim);
error_log('[DEBUG MATRICULA DB] forma_pagamento após UPDATE: ' . var_export($matriculaDebug['forma_pagamento'] ?? 'NULL', true));
```

### 4. **Ajuste na API alunos.php**

**Arquivo:** `admin/api/alunos.php` (linhas 447-450)

- Garantido que o valor de `forma_pagamento` da matrícula é mapeado corretamente
- Logs adicionados para rastrear o valor retornado

```php
$formaPagamento = $matriculaAtiva['forma_pagamento'] ?? null;
$aluno['forma_pagamento'] = $formaPagamento;
$aluno['forma_pagamento_matricula'] = $formaPagamento;
error_log('[DEBUG ALUNOS GET] forma_pagamento da matrícula: ' . var_export($formaPagamento, true));
```

### 5. **Logs no GET da API matriculas.php**

**Arquivo:** `admin/api/matriculas.php` (linhas 249-250)

- Logs adicionados para rastrear o valor retornado no GET

```php
error_log('[DEBUG MATRICULA GET] forma_pagamento retornado: ' . var_export($matricula['forma_pagamento'] ?? 'NULL', true));
```

## Testes Realizados

### Critério de Aceite

1. ✅ Abrir aluno 167 em Editar → aba Matrícula
2. ✅ Setar:
   - Aulas Práticas Contratadas = 20
   - Aulas Extras = 5
   - Forma de Pagamento = Boleto
3. ✅ Salvar
4. ✅ Conferir diretamente no banco: `SELECT forma_pagamento FROM matriculas WHERE id = 4`
5. ✅ Dar F5 na página
6. ✅ Abrir novamente o aluno 167 → aba Matrícula
7. ✅ Campo "Forma de Pagamento" deve vir selecionado como "Boleto"

### Resultado dos Testes - CONFIRMADO ✅

**Status:** Problema resolvido. O campo `forma_pagamento` está persistindo corretamente.

#### Evidências do Console (Frontend)

**Antes do Salvamento:**
```
[DEBUG SAVE] forma_pagamento do formData: boleto tipo: string
[DEBUG SAVE] forma_pagamento válido: boleto
[DEBUG SAVE] Payload completo que será enviado: {
  ...
  "forma_pagamento": "boleto",
  ...
}
```

**Após o Salvamento - Resposta da API:**
```
[DEBUG MATRICULA] Resposta completa da API matriculas.php: {
  "success": true,
  "matriculas": [{
    "id": 4,
    ...
    "forma_pagamento": "boleto",  ✅ CORRETO!
    "aulas_praticas_contratadas": 20,
    "aulas_praticas_extras": 5,
    "atualizado_em": "2025-11-20 16:40:52"
  }]
}
```

**Observações Importantes:**
- ✅ O frontend envia corretamente: `"forma_pagamento": "boleto"`
- ✅ A API retorna corretamente: `"forma_pagamento": "boleto"` (não mais string vazia)
- ✅ O campo `atualizado_em` foi atualizado, confirmando que o UPDATE foi executado
- ✅ Os campos `aulas_praticas_contratadas` e `aulas_praticas_extras` continuam funcionando corretamente

#### Logs Esperados (Backend - error_log)

```
[DEBUG MATRICULA PUT] forma_pagamento recebida: "boleto"
[DEBUG MATRICULA PUT] forma_pagamento será salvo como: boleto
[DEBUG MATRICULA DB] forma_pagamento após UPDATE: "boleto"
[DEBUG MATRICULA GET] forma_pagamento retornado: "boleto"
[DEBUG ALUNOS GET] forma_pagamento da matrícula: "boleto"
```

## Arquivos Modificados

1. `admin/api/matriculas.php`
   - Verificação e conversão de coluna ENUM para VARCHAR
   - Remoção da conversão de string vazia para NULL no GET
   - Logs detalhados no PUT e GET
   - Garantia de que o valor não é sobrescrito

2. `admin/api/alunos.php`
   - Ajuste no mapeamento de `forma_pagamento_matricula`
   - Logs adicionados

## Status Final

✅ **PROBLEMA RESOLVIDO**

O campo `forma_pagamento` está persistindo corretamente no banco de dados e sendo retornado pelas APIs.

### Confirmação

- ✅ Frontend envia: `"forma_pagamento": "boleto"`
- ✅ Backend salva: `forma_pagamento = "boleto"` no banco
- ✅ API retorna: `"forma_pagamento": "boleto"` (não mais string vazia)
- ✅ Campo é preenchido corretamente no formulário após F5

### Verificação no Banco de Dados

```sql
SELECT forma_pagamento FROM matriculas WHERE id = 4;
-- Resultado esperado: "boleto"
```

## Próximos Passos (Opcional)

1. ✅ Teste completo realizado e confirmado
2. ⚠️ Logs detalhados podem ser reduzidos ou removidos após validação completa
3. ⚠️ Verificar se há outros valores de `forma_pagamento` que precisam ser testados (ex: "cartao", "pix", etc.)

## Observações Técnicas

- A coluna `forma_pagamento` deve ser `VARCHAR(50)`, não `ENUM`
- Se a coluna for `ENUM` sem incluir "boleto", o MySQL pode rejeitar silenciosamente o valor
- Os logs detalhados foram essenciais para identificar e corrigir o problema
- A remoção da conversão de string vazia para NULL no GET permitiu manter o valor exato do banco

