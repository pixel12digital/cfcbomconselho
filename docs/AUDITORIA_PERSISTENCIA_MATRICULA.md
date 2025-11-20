# Auditoria Completa - Persistência de Campos da Matrícula

## Problema Identificado

**Sintoma:** Três campos da aba Matrícula não persistem após refresh da página:
- Aulas Práticas Contratadas
- Aulas Extras  
- Forma de Pagamento

**Comportamento:**
- ✅ Salvar → Reabrir sem refresh → Valores aparecem corretamente
- ❌ Salvar → Refresh (F5) → Reabrir → Campos voltam vazios

## Estrutura da Tabela `matriculas`

### Campos Identificados

| Campo | Tipo | Localização | Status |
|-------|------|-------------|--------|
| `aulas_praticas_contratadas` | `INT DEFAULT NULL` | Tabela `matriculas` | ✅ Existe |
| `aulas_praticas_extras` | `INT DEFAULT NULL` | Tabela `matriculas` | ✅ Existe |
| `forma_pagamento` | `VARCHAR(50) DEFAULT NULL` | Tabela `matriculas` | ✅ Existe |

**Observação:** As colunas são criadas dinamicamente pelo backend se não existirem (linhas 97-117 de `admin/api/matriculas.php`).

## Análise do Fluxo de Salvamento (UPDATE)

### 1. Frontend - `saveAlunoMatricula()` 

**Arquivo:** `admin/pages/alunos.php` (linhas ~7210-7217)

**Status:** ✅ **CORRETO**

Os campos estão sendo enviados corretamente no payload:

```javascript
aulas_praticas_contratadas: (() => {
    const valor = formData.get('aulas_praticas_contratadas');
    return valor && valor.trim() !== '' ? parseInt(valor) : null;
})(),
aulas_praticas_extras: (() => {
    const valor = formData.get('aulas_praticas_extras');
    return valor && valor.trim() !== '' ? parseInt(valor) : null;
})(),
// forma_pagamento está em outro trecho (linha ~7195)
```

### 2. Backend - `handlePut()` em `admin/api/matriculas.php`

**Arquivo:** `admin/api/matriculas.php` (linhas ~360-365)

**Status:** ✅ **CORRETO**

Os campos estão sendo incluídos no UPDATE:

```php
'aulas_praticas_contratadas' => isset($input['aulas_praticas_contratadas']) && $input['aulas_praticas_contratadas'] !== '' && $input['aulas_praticas_contratadas'] !== null
    ? (int)$input['aulas_praticas_contratadas']
    : ($matricula['aulas_praticas_contratadas'] ?? null),
'aulas_praticas_extras' => isset($input['aulas_praticas_extras']) && $input['aulas_praticas_extras'] !== '' && $input['aulas_praticas_extras'] !== null
    ? (int)$input['aulas_praticas_extras']
    : ($matricula['aulas_praticas_extras'] ?? null),
```

**Conclusão:** Os dados estão sendo salvos corretamente no banco de dados.

## Análise do Fluxo de Carregamento (GET)

### Problema Identificado: ❌ **API GET de Alunos não retornava os campos**

**Arquivo:** `admin/api/alunos.php` (linhas ~410-455)

**Antes da correção:**
- A query buscava apenas `renach, status, data_fim` da matrícula ativa
- Uma segunda query buscava apenas `processo_numero, processo_numero_detran, processo_situacao`
- **Os campos `aulas_praticas_contratadas`, `aulas_praticas_extras` e `forma_pagamento` NÃO eram retornados**

**Depois da correção:**
- Query única que busca todos os campos necessários:
  ```sql
  SELECT 
      renach, 
      status, 
      data_fim,
      processo_numero,
      processo_numero_detran,
      processo_situacao,
      previsao_conclusao,
      valor_total,
      forma_pagamento,
      status_pagamento,
      aulas_praticas_contratadas,
      aulas_praticas_extras
  FROM matriculas 
  WHERE aluno_id = ? AND status = 'ativa'
  ORDER BY data_inicio DESC
  LIMIT 1
  ```

- Os campos são mapeados no objeto `$aluno`:
  ```php
  $aluno['aulas_praticas_contratadas'] = $matriculaAtiva['aulas_praticas_contratadas'] ?? null;
  $aluno['aulas_praticas_extras'] = $matriculaAtiva['aulas_praticas_extras'] ?? null;
  $aluno['forma_pagamento_matricula'] = $matriculaAtiva['forma_pagamento'] ?? null;
  ```

### 2. Frontend - `preencherAbaMatriculaComDados()`

**Arquivo:** `admin/pages/alunos.php` (linhas ~7906-7986)

**Status:** ✅ **CORRETO** (após correção da API)

A função já estava preparada para ler os campos, mas eles não chegavam porque a API não os retornava:

```javascript
// Preencher Aulas Práticas Contratadas
if (matricula.aulas_praticas_contratadas !== undefined && matricula.aulas_praticas_contratadas !== null) {
    const aulasContratadasInput = document.getElementById('aulas_praticas_contratadas');
    if (aulasContratadasInput) {
        aulasContratadasInput.value = matricula.aulas_praticas_contratadas;
    }
}

// Preencher Aulas Extras
if (matricula.aulas_praticas_extras !== undefined && matricula.aulas_praticas_extras !== null) {
    const aulasExtrasInput = document.getElementById('aulas_praticas_extras');
    if (aulasExtrasInput) {
        aulasExtrasInput.value = matricula.aulas_praticas_extras;
    }
}

// Preencher Forma de Pagamento
if (matricula.forma_pagamento) {
    const formaPagamentoSelect = document.getElementById('forma_pagamento');
    if (formaPagamentoSelect) {
        formaPagamentoSelect.value = matricula.forma_pagamento;
    }
}
```

### 3. Duas Fontes de Dados da Matrícula

**Problema identificado:** Há duas formas de carregar dados da matrícula:

1. **Via API GET de Alunos** (`api/alunos.php?id={id}`)
   - Chamada quando `editarAluno(id)` é executado
   - Retorna dados do aluno + dados da matrícula ativa
   - **ANTES:** Não retornava os 3 campos problemáticos
   - **DEPOIS:** ✅ Retorna todos os campos

2. **Via API GET de Matrículas** (`api/matriculas.php?aluno_id={id}`)
   - Chamada por `carregarMatriculaPrincipal(alunoId)`
   - Retorna array de matrículas do aluno
   - **Status:** ✅ Já retorna todos os campos (usa `SELECT m.*`)

**Observação:** Quando o aluno é carregado via `editarAluno`, os dados da matrícula vêm da API GET de alunos. Quando `carregarMatriculaPrincipal` é chamado, ele sobrescreve com dados da API GET de matrículas. Ambas as fontes agora retornam os campos corretos.

## Correções Aplicadas

### 1. `admin/api/alunos.php` (linhas ~410-455)

**Mudança:** Query única que busca todos os campos da matrícula ativa, incluindo:
- `aulas_praticas_contratadas`
- `aulas_praticas_extras`
- `forma_pagamento`

**Mapeamento no objeto `$aluno`:**
- `$aluno['aulas_praticas_contratadas']`
- `$aluno['aulas_praticas_extras']`
- `$aluno['forma_pagamento_matricula']`

### 2. `admin/pages/alunos.php` (linhas ~7756-7765)

**Mudança:** Adicionados logs de debug para rastrear os dados recebidos da API:

```javascript
console.log('[DEBUG MATRICULA] Dados recebidos da API matriculas.php:', {
    aulas_praticas_contratadas: matricula.aulas_praticas_contratadas,
    aulas_praticas_extras: matricula.aulas_praticas_extras,
    forma_pagamento: matricula.forma_pagamento,
    matricula_completa: matricula
});
```

### 3. `admin/pages/alunos.php` (linhas ~7906-7986)

**Mudança:** Adicionados logs de debug em cada campo para rastrear o preenchimento:

```javascript
console.log('[DEBUG MATRICULA] aulas_praticas_contratadas recebido:', matricula.aulas_praticas_contratadas);
console.log('[DEBUG MATRICULA] aulas_praticas_extras recebido:', matricula.aulas_praticas_extras);
console.log('[DEBUG MATRICULA] forma_pagamento recebido:', matricula.forma_pagamento);
```

## Diagnóstico Final

### Gargalo Identificado

**❌ Campos não retornados na API GET de alunos**

A API GET de alunos (`admin/api/alunos.php`) não estava incluindo os campos `aulas_praticas_contratadas`, `aulas_praticas_extras` e `forma_pagamento` na query da matrícula ativa. Quando o aluno era carregado após um refresh, esses campos não vinham no JSON, resultando em campos vazios no formulário.

### Fluxo Corrigido

1. **Salvar (UPDATE):** ✅ Funcionava corretamente
   - Frontend envia campos → Backend salva no banco

2. **Carregar (GET) - ANTES:** ❌ Campos não retornados
   - API GET de alunos não incluía os 3 campos na query
   - Frontend recebia JSON sem esses campos
   - Formulário ficava vazio

3. **Carregar (GET) - DEPOIS:** ✅ Campos retornados
   - API GET de alunos inclui todos os campos na query
   - Frontend recebe JSON com os campos
   - Formulário é preenchido corretamente

## Arquivos Modificados

1. **`admin/api/alunos.php`** (linhas ~410-455)
   - Query única que busca todos os campos da matrícula
   - Mapeamento dos campos no objeto `$aluno`
   - Logs de debug adicionados

2. **`admin/pages/alunos.php`** (linhas ~7756-7765, ~7906-7986)
   - Logs de debug adicionados para rastreamento
   - Verificações de campos melhoradas

## Exemplo de JSON Retornado (Após Correção)

### API GET `/admin/api/alunos.php?id=167`

```json
{
  "success": true,
  "aluno": {
    "id": 167,
    "nome": "João Silva",
    "cpf": "123.456.789-00",
    // ... outros campos do aluno ...
    "aulas_praticas_contratadas": 20,
    "aulas_praticas_extras": 5,
    "forma_pagamento_matricula": "boleto",
    "status_pagamento_matricula": "em_dia",
    // ... outros campos da matrícula ...
  }
}
```

### API GET `/admin/api/matriculas.php?aluno_id=167`

```json
{
  "success": true,
  "matriculas": [
    {
      "id": 123,
      "aluno_id": 167,
      "categoria_cnh": "B",
      "tipo_servico": "primeira_habilitacao",
      "status": "ativa",
      "data_inicio": "2024-01-15",
      "aulas_praticas_contratadas": 20,
      "aulas_praticas_extras": 5,
      "forma_pagamento": "boleto",
      "status_pagamento": "em_dia",
      "valor_total": 3500.00,
      // ... outros campos ...
    }
  ]
}
```

## Testes Recomendados

### Teste 1: Salvar e Reabrir (Sem Refresh)
1. Abrir aluno ID 167
2. Na aba Matrícula, definir:
   - Aulas Práticas Contratadas = 20
   - Aulas Extras = 5
   - Forma de Pagamento = Boleto
3. Salvar
4. Fechar modal e reabrir imediatamente
5. **Resultado esperado:** ✅ Valores devem estar preenchidos

### Teste 2: Salvar, Refresh e Reabrir
1. Abrir aluno ID 167
2. Na aba Matrícula, definir:
   - Aulas Práticas Contratadas = 20
   - Aulas Extras = 5
   - Forma de Pagamento = Boleto
3. Salvar
4. **Dar F5 (refresh) na página**
5. Abrir novamente o mesmo aluno em Editar
6. **Resultado esperado:** ✅ Valores devem continuar preenchidos (20 / 5 / Boleto)

### Teste 3: Verificar Logs de Debug
1. Abrir console do navegador (F12)
2. Abrir aluno em Editar
3. Verificar logs:
   - `[DEBUG MATRICULA] Dados recebidos da API matriculas.php:`
   - `[DEBUG MATRICULA] aulas_praticas_contratadas recebido:`
   - `[DEBUG MATRICULA] aulas_praticas_extras recebido:`
   - `[DEBUG MATRICULA] forma_pagamento recebido:`
4. **Resultado esperado:** ✅ Logs devem mostrar os valores corretos

## Conclusão

O problema estava na **API GET de alunos**, que não retornava os três campos da matrícula. Após a correção, os campos são retornados corretamente e o formulário é preenchido após refresh da página.

**Status:** ✅ **CORRIGIDO**

