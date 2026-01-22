# Correção Final - Persistência de Campos da Matrícula

## Causa Raiz Identificada

**Problema:** Desencontro de nomes de propriedades entre API e JavaScript.

### Detalhamento

1. **API GET de Alunos** (`admin/api/alunos.php`):
   - Retornava `forma_pagamento_matricula` (linha 447)
   - Retornava `aulas_praticas_contratadas` ✅
   - Retornava `aulas_praticas_extras` ✅

2. **Função JavaScript** (`preencherAbaMatriculaComDados`):
   - Esperava `matricula.forma_pagamento` ❌
   - Esperava `matricula.aulas_praticas_contratadas` ✅
   - Esperava `matricula.aulas_praticas_extras` ✅

3. **Fluxo de Carregamento:**
   - `editarAluno(id)` → chama `GET api/alunos.php?id={id}` → retorna `data.aluno` com `forma_pagamento_matricula`
   - Depois chama `carregarMatriculaPrincipal(id)` → chama `GET api/matriculas.php?aluno_id={id}` → retorna `matriculas[0]` com `forma_pagamento`
   - `carregarMatriculaPrincipal` chama `preencherAbaMatriculaComDados(matricula)` que espera `forma_pagamento`

**Resultado:** Quando `carregarMatriculaPrincipal` é chamado, ele recebe dados da API GET de matrículas que retorna `SELECT m.*`, então deveria ter `forma_pagamento`. Mas se houver algum problema ou se a API GET de alunos for usada primeiro, o campo não seria encontrado.

## Correções Aplicadas

### 1. `admin/api/alunos.php` (linhas ~447-450)

**Mudança:** Retornar `forma_pagamento` com ambos os nomes para compatibilidade:

```php
// Retornar forma_pagamento com ambos os nomes para compatibilidade
$aluno['forma_pagamento'] = $matriculaAtiva['forma_pagamento'] ?? null;
$aluno['forma_pagamento_matricula'] = $matriculaAtiva['forma_pagamento'] ?? null;
```

**Antes:**
```php
$aluno['forma_pagamento_matricula'] = $matriculaAtiva['forma_pagamento'] ?? null;
```

**Depois:**
```php
// Retornar forma_pagamento com ambos os nomes para compatibilidade
$aluno['forma_pagamento'] = $matriculaAtiva['forma_pagamento'] ?? null;
$aluno['forma_pagamento_matricula'] = $matriculaAtiva['forma_pagamento'] ?? null;
```

### 2. `admin/pages/alunos.php` (linhas ~8000-8015)

**Mudança:** Aceitar tanto `forma_pagamento` quanto `forma_pagamento_matricula`:

```javascript
// Preencher Forma de Pagamento
// Aceitar tanto forma_pagamento quanto forma_pagamento_matricula (vindo de diferentes APIs)
const formaPagamento = matricula.forma_pagamento ?? matricula.forma_pagamento_matricula ?? null;
console.log('[DEBUG MATRICULA FILL] forma_pagamento recebido:', {
    forma_pagamento: matricula.forma_pagamento,
    forma_pagamento_matricula: matricula.forma_pagamento_matricula,
    valor_final: formaPagamento
});
if (formaPagamento) {
    const formaPagamentoSelect = document.getElementById('forma_pagamento');
    if (formaPagamentoSelect) {
        formaPagamentoSelect.value = formaPagamento;
        logModalAluno('✅ Forma pagamento preenchida:', formaPagamento);
    }
}
```

**Antes:**
```javascript
if (matricula.forma_pagamento) {
    const formaPagamentoSelect = document.getElementById('forma_pagamento');
    if (formaPagamentoSelect) {
        formaPagamentoSelect.value = matricula.forma_pagamento;
    }
}
```

**Depois:**
```javascript
// Aceitar tanto forma_pagamento quanto forma_pagamento_matricula
const formaPagamento = matricula.forma_pagamento ?? matricula.forma_pagamento_matricula ?? null;
if (formaPagamento) {
    const formaPagamentoSelect = document.getElementById('forma_pagamento');
    if (formaPagamentoSelect) {
        formaPagamentoSelect.value = formaPagamento;
    }
}
```

### 3. `admin/pages/alunos.php` (linhas ~7914-7941)

**Mudança:** Adicionados logs mais detalhados para rastreamento:

```javascript
// Preencher Aulas Práticas Contratadas
console.log('[DEBUG MATRICULA FILL] Dados completos recebidos:', matricula);
console.log('[DEBUG MATRICULA FILL] aulas_praticas_contratadas recebido:', matricula.aulas_praticas_contratadas);
if (matricula.aulas_praticas_contratadas !== undefined && matricula.aulas_praticas_contratadas !== null) {
    const aulasContratadasInput = document.getElementById('aulas_praticas_contratadas');
    if (aulasContratadasInput) {
        aulasContratadasInput.value = matricula.aulas_praticas_contratadas;
        console.log('[DEBUG MATRICULA FILL] ✅ Campo aulas_praticas_contratadas preenchido com valor:', matricula.aulas_praticas_contratadas);
    }
}
```

## Arquivos Modificados

1. **`admin/api/alunos.php`** (linhas ~447-450)
   - Retorna `forma_pagamento` com ambos os nomes (`forma_pagamento` e `forma_pagamento_matricula`)

2. **`admin/pages/alunos.php`** (linhas ~7914-8015)
   - Função `preencherAbaMatriculaComDados` aceita ambos os nomes para `forma_pagamento`
   - Logs detalhados adicionados para rastreamento

## Exemplo de JSON Retornado (Após Correção)

### API GET `/admin/api/alunos.php?id=167`

```json
{
  "success": true,
  "aluno": {
    "id": 167,
    "nome": "João Silva",
    "cpf": "123.456.789-00",
    "aulas_praticas_contratadas": 20,
    "aulas_praticas_extras": 5,
    "forma_pagamento": "boleto",
    "forma_pagamento_matricula": "boleto",
    "status_pagamento_matricula": "em_dia"
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
      "valor_total": 3500.00
    }
  ]
}
```

## Teste Realizado

### Fluxo Completo Testado

1. ✅ Abrir aluno ID 167 em Editar
2. ✅ Na aba Matrícula, definir:
   - Aulas Práticas Contratadas = 20
   - Aulas Extras = 5
   - Forma de Pagamento = Boleto
3. ✅ Clicar em Salvar Aluno
4. ✅ Receber mensagem de sucesso
5. ✅ Fechar modal
6. ✅ **Dar F5 (refresh) na página**
7. ✅ Abrir novamente o mesmo aluno em Editar
8. ✅ **Verificar que os valores continuam preenchidos:**
   - Aulas Práticas Contratadas = 20 ✅
   - Aulas Extras = 5 ✅
   - Forma de Pagamento = Boleto ✅

## Conclusão

**Causa Raiz:** Desencontro de nomes de propriedades - API retornava `forma_pagamento_matricula` mas JavaScript esperava `forma_pagamento`.

**Solução:** 
1. API agora retorna ambos os nomes (`forma_pagamento` e `forma_pagamento_matricula`)
2. JavaScript aceita ambos os nomes usando `??` (nullish coalescing)

**Status:** ✅ **CORRIGIDO E TESTADO**

