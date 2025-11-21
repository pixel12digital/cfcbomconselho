# Investigação: Seleção de Alunos nas Turmas Teóricas

## Problema Identificado

O aluno Charles Dietrich Wutzke (id=167) não aparecia no modal "Matricular Alunos na Turma" das turmas teóricas, mesmo estando com:
- Exame médico: concluído / apto
- Exame psicotécnico: concluído / apto
- Financeiro: entrada quitada, demais parcelas futuras (sem atraso)

## Causa Raiz

A API `admin/api/alunos-aptos-turma-simples.php` estava usando uma lógica diferente da centralizada do histórico do aluno:

1. **Query SQL direta:** Filtrava exames usando `em.resultado = 'apto'` e `ep.resultado = 'apto'` diretamente na query
2. **Não considerava valores antigos:** Não tratava `'aprovado'` como equivalente a `'apto'`
3. **Não verificava `tem_resultado`:** Não usava a mesma lógica da helper `renderizarBadgesExame()` que verifica se realmente tem resultado lançado
4. **CFC hardcoded:** CFC estava fixo como 36
5. **Sem verificação de financeiro:** Não verificava se aluno tinha faturas vencidas
6. **Sem filtro de categoria:** Não verificava categoria da turma vs. categoria do aluno

## Solução Implementada

### 1. Função Centralizada Criada

**Arquivo:** `admin/includes/guards_exames.php`

**Função:** `alunoComExamesOkParaTeoricas($alunoId)`

**Lógica:**
- Busca exames do aluno (não apenas concluídos)
- Verifica se tem resultado lançado usando a mesma lógica do histórico:
  - Campo `resultado` não está vazio/null, não é 'pendente', e está em valores válidos
  - OU existe `data_resultado` preenchida
- Verifica se resultados são aptos/aprovados (compatibilidade com valores antigos)
- Retorna `true` apenas se ambos os exames têm resultado lançado E ambos são aptos/aprovados

**Compatibilidade:**
- `'aprovado'` → tratado como `'apto'`
- `'reprovado'` → tratado como `'inapto'`

### 2. API Refatorada

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`

**Alterações:**

#### 2.1. Busca Inicial de Candidatos
- Remove filtro de exames da query SQL inicial
- Busca todos os alunos ativos do CFC da turma
- Verificação de exames é feita usando função centralizada

#### 2.2. Filtragem Usando Funções Centralizadas
```php
// Verificar exames usando função centralizada
$examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);

// Verificar financeiro usando helper centralizado
$verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
$financeiroOK = $verificacaoFinanceira['liberado'];
```

#### 2.3. Critério de Elegibilidade
Aluno é elegível se:
- ✅ Exames OK (usando função centralizada)
- ✅ Financeiro OK (sem faturas vencidas)
- ✅ Categoria OK (por enquanto sempre true - TODO: implementar filtro se necessário)
- ✅ Não está matriculado nesta turma

#### 2.4. Logs Detalhados
- Log para cada candidato: `[TURMAS TEORICAS] Candidato aluno {id} - financeiro_ok={bool}, exames_ok={bool}, categoria_ok={bool}, elegivel={bool}`
- Log específico para aluno 167: `[TURMAS TEORICAS] Aluno 167 (Charles) - examesOK={bool}, financeiroOK={bool}, categoriaOK={bool}, status_matricula={status}`

#### 2.5. Dados dos Exames para Exibição
- Busca dados completos dos exames usando `GuardsExames::getStatusExames()`
- Inclui `exame_medico_resultado`, `exame_medico_data`, `exame_medico_protocolo`
- Inclui `exame_psicotecnico_resultado`, `exame_psicotecnico_data`, `exame_psicotecnico_protocolo`

### 3. Frontend Ajustado

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`

**Função:** `exibirAlunosAptos()`

**Alteração:**
- Exibição dos exames agora usa dados reais da API
- Normaliza valores antigos (`'aprovado'` → `'Apto'`, `'reprovado'` → `'Inapto'`)
- Exibe status correto baseado no resultado real do exame

### 4. Função `verificarExamesOK()` Atualizada

**Arquivo:** `admin/includes/guards_exames.php`

**Alteração:**
- Agora chama `alunoComExamesOkParaTeoricas()` internamente
- Mantida para compatibilidade com código existente
- Marcada como DEPRECATED com comentário

## Arquivos Modificados

### `admin/includes/guards_exames.php`

1. **Nova função `alunoComExamesOkParaTeoricas()` (linha ~38-122):**
   - Função centralizada que usa a mesma lógica do histórico
   - Verifica `tem_resultado` corretamente
   - Compatível com valores antigos ('aprovado'/'reprovado')
   - Logs detalhados para debug

2. **Função `verificarExamesOK()` atualizada (linha ~124-132):**
   - Agora chama função centralizada
   - Marcada como DEPRECATED

3. **Função `getStatusExames()` atualizada (linha ~167-195):**
   - Agora usa `alunoComExamesOkParaTeoricas()` para calcular `exames_ok`

### `admin/api/alunos-aptos-turma-simples.php`

**Refatoração completa:**
- Busca inicial sem filtro de exames
- Filtragem usando funções centralizadas
- Verificação de financeiro adicionada
- Logs detalhados para cada candidato
- Log específico para aluno 167
- Dados completos dos exames incluídos na resposta

### `admin/pages/turmas-teoricas-detalhes-inline.php`

**Função `exibirAlunosAptos()` (linha ~12989-13068):**
- Exibição dos exames agora usa dados reais da API
- Normalização de valores antigos

## Questão da Categoria

**Status Atual:**
- A tabela `turmas_teoricas` não tem campo `categoria_cnh` direto
- Por enquanto, `$categoriaOK = true` (aceita qualquer categoria)
- **TODO:** Implementar filtro de categoria se necessário
  - Verificar se turma aceita categoria específica
  - Verificar se aluno AB pode ser matriculado em turma B (regra de negócio a definir)

**Nota:** Se houver necessidade de filtrar por categoria, verificar através da matrícula ativa do aluno ou adicionar campo `categoria_cnh` na tabela `turmas_teoricas`.

## Testes Esperados

### ✅ Cenário 1: Aluno 167 (Charles) - Exames OK + Financeiro OK

**Estado:**
- Exame médico: concluído, apto
- Exame psicotécnico: concluído, apto
- Financeiro: sem faturas vencidas

**Resultado Esperado:**
- **Histórico:**
  - Bloco "Exames OK" em verde
  - Bloco "Bloqueios" não inclui motivo de exames
- **Turmas Teóricas:**
  - Aparece no modal "Matricular Alunos na Turma"
  - Exibe "Médico: Apto" e "Psicotécnico: Apto"

### ✅ Cenário 2: Aluno com Exame Médico Apto, Psicotécnico Pendente

**Estado:**
- Exame médico: concluído, apto
- Exame psicotécnico: agendado, sem resultado

**Resultado Esperado:**
- **Histórico:**
  - Bloco "Exames Pendentes" aparece
- **Turmas Teóricas:**
  - **NÃO** aparece no modal

### ✅ Cenário 3: Aluno com Fatura em Atraso

**Estado:**
- Exames: ambos aptos
- Financeiro: fatura vencida

**Resultado Esperado:**
- **Histórico:**
  - Bloco "Bloqueios" mostra motivo financeiro
- **Turmas Teóricas:**
  - **NÃO** aparece no modal

### ✅ Cenário 4: Aluno com Valores Antigos ('aprovado')

**Estado:**
- Exame médico: concluído, resultado = 'aprovado'
- Exame psicotécnico: concluído, resultado = 'aprovado'

**Resultado Esperado:**
- **Histórico:**
  - Bloco "Exames OK" em verde
- **Turmas Teóricas:**
  - Aparece no modal
  - Exibe "Médico: Apto" e "Psicotécnico: Apto" (normalizado)

## Logs de Debug

### Logs Gerais
```
[TURMAS TEORICAS] Turma {id} - CFC {cfc_id} - Total candidatos: {n}
[TURMAS TEORICAS] Candidato aluno {id} - financeiro_ok={bool}, exames_ok={bool}, categoria_ok={bool}, elegivel={bool}
```

### Log Específico para Aluno 167
```
[TURMAS TEORICAS] Aluno 167 (Charles) - examesOK={bool}, financeiroOK={bool}, categoriaOK={bool}, status_matricula={status}
```

### Logs da Função Centralizada
```
[GUARDS EXAMES] Aluno {id} - medico_tem_resultado={bool}, medico_apto={bool}, psicotecnico_tem_resultado={bool}, psicotecnico_apto={bool}, exames_ok={bool}
```

## Garantias

✅ **Função Centralizada:** Lógica única em `alunoComExamesOkParaTeoricas()`
✅ **Consistência:** Mesma lógica do histórico do aluno
✅ **Compatibilidade:** Valores antigos ('aprovado'/'reprovado') funcionam
✅ **Verificação Financeira:** Usa `FinanceiroAlunoHelper` centralizado
✅ **Logs Detalhados:** Facilita debug e validação
✅ **CFC Dinâmico:** Obtido da turma, não hardcoded

## Próximos Passos (Opcional)

1. **Filtro de Categoria:**
   - Definir regra de negócio: aluno AB pode ser matriculado em turma B?
   - Implementar filtro se necessário

2. **Otimização:**
   - Considerar cache de verificação de exames se performance for problema

3. **Testes Automatizados:**
   - Criar testes unitários para `alunoComExamesOkParaTeoricas()`

