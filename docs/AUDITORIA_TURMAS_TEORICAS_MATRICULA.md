# Auditoria: Critério de Seleção de Alunos Apto para Matrícula em Turma Teórica

**Data da Auditoria:** 12/12/2025  
**Caso Real Analisado:** Aluno Charles Dietrich (ID: 167) não aparece no modal "Matricular Alunos na Turma"

---

## 1. Resumo do Problema

### Sintoma Observado

Na tela **Turmas Teóricas → Detalhes → Matricular Alunos**, aparece:

- Modal: "Critério de Seleção: Apenas alunos com exames médico e psicotécnico aprovados serão exibidos."
- Lista vazia: "Nenhum aluno encontrado com exames médico e psicotécnico aprovados."
- Debug Info mostra:
  - CFC da Turma: 1
  - CFC da Sessão: 0 (admin_global)
  - CFCs coincidem: N/A (Admin Global)
  - Total candidatos: 0
  - Total aptos: 0

### Situação do Aluno Charles (ID: 167)

- ✅ Exames **Médico** e **Psicotécnico** concluídos e "APTO"
- ✅ Tela de histórico mostra: "Exames OK – Aluno apto para prosseguir com aulas teóricas"
- ✅ "Liberado para Aulas Teóricas – Tudo em ordem, situação financeira regularizada"
- ❌ **NÃO aparece na lista de alunos aptos para matrícula**

---

## 2. Arquivos Envolvidos

### 2.1. Modal e Frontend

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`

- **Linhas 9334-9354:** HTML do modal "Matricular Alunos na Turma"
- **Linhas 13016-13110:** JavaScript que chama a API e renderiza os resultados
- **Linhas 13080-13108:** Renderização do debug info quando não há alunos

### 2.2. API de Busca de Alunos Aptos

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`

- **Linhas 95-124:** Query SQL que busca alunos candidatos
- **Linhas 193-270:** Loop que filtra alunos usando funções centralizadas
- **Linha 219:** Condição de elegibilidade

### 2.3. Funções de Validação

**Arquivo:** `admin/includes/guards_exames.php`

- **Linhas 57-128:** `GuardsExames::alunoComExamesOkParaTeoricas($alunoId)`
  - Verifica se exames médico e psicotécnico estão OK
  - Usa a mesma lógica do histórico do aluno

**Arquivo:** `admin/includes/FinanceiroAlunoHelper.php`

- **Linhas 77-240:** `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)`
  - Verifica se aluno tem pelo menos uma fatura paga
  - Verifica se não há faturas vencidas

---

## 3. Critério Atual de Seleção (Baseado no Código)

### 3.1. Query SQL Inicial (Busca Candidatos Brutos)

**Localização:** `admin/api/alunos-aptos-turma-simples.php`, linhas 95-124

```sql
SELECT 
    a.id,
    a.nome,
    a.cpf,
    a.categoria_cnh,
    a.status as status_aluno,
    c.nome as cfc_nome,
    c.id as cfc_id,
    -- Incluir categoria da matrícula ativa (prioridade 1)
    m_ativa.categoria_cnh as categoria_cnh_matricula,
    m_ativa.tipo_servico as tipo_servico_matricula,
    CASE 
        WHEN tm.id IS NOT NULL THEN 'matriculado'
        ELSE 'disponivel'
    END as status_matricula
FROM alunos a
JOIN cfcs c ON a.cfc_id = c.id
LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
    AND tm.turma_id = ? 
    AND tm.status IN ('matriculado', 'cursando')
LEFT JOIN (
    SELECT aluno_id, categoria_cnh, tipo_servico
    FROM matriculas
    WHERE status = 'ativa'
) m_ativa ON a.id = m_ativa.aluno_id
WHERE a.status = 'ativo'
    AND a.cfc_id = ?
ORDER BY a.nome
```

**Parâmetros:**
- `$turmaId` (primeiro `?`) - usado no LEFT JOIN de `turma_matriculas`
- `$cfcIdTurma` (segundo `?`) - usado no WHERE para filtrar por CFC

**Condições da Query:**
1. ✅ `a.status = 'ativo'` - aluno deve estar ativo
2. ✅ `a.cfc_id = $cfcIdTurma` - aluno deve pertencer ao mesmo CFC da turma
3. ✅ LEFT JOIN com `turma_matriculas` para determinar se já está matriculado nesta turma
   - Se existe registro com `turma_id = $turmaId` e `status IN ('matriculado', 'cursando')` → `status_matricula = 'matriculado'`
   - Caso contrário → `status_matricula = 'disponivel'`

### 3.2. Filtros Aplicados Após a Query (Loop de Validação)

**Localização:** `admin/api/alunos-aptos-turma-simples.php`, linhas 193-270

Para cada aluno retornado pela query, são aplicados os seguintes filtros:

#### Filtro 1: Verificação de CFC do Aluno vs CFC da Turma
```php
// Linhas 197-203
if ($alunoCfcId !== $cfcIdTurma) {
    continue; // Não considera este aluno
}
```

#### Filtro 2: Verificação de Exames
```php
// Linha 206
$examesOK = GuardsExames::alunoComExamesOkParaTeoricas($alunoId);
```

**Lógica da função `GuardsExames::alunoComExamesOkParaTeoricas()`:**

1. Busca exames na tabela `exames` filtrando por `aluno_id`
2. Pega o exame mais recente de cada tipo (`medico` e `psicotecnico`)
3. Verifica se ambos têm resultado lançado:
   - Campo `resultado` não está vazio/null e não é 'pendente'
   - E está em valores válidos: `['apto', 'inapto', 'inapto_temporario', 'aprovado', 'reprovado']`
   - OU existe `data_resultado` preenchida
4. Verifica se ambos são aptos:
   - `resultado` deve estar em `['apto', 'aprovado']`
5. Retorna `true` apenas se ambos têm resultado E ambos são aptos

#### Filtro 3: Verificação Financeira
```php
// Linhas 209-210
$verificacaoFinanceira = FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId);
$financeiroOK = $verificacaoFinanceira['liberado'];
```

**Lógica da função `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`:**

1. Verifica se existe matrícula ativa (status = 'ativa')
2. Busca todas as faturas não canceladas do aluno
3. Se não houver nenhuma fatura → **BLOQUEIA** (`liberado = false`)
4. Se houver faturas vencidas (status 'aberta'/'parcial' e `data_vencimento < hoje`) → **BLOQUEIA**
5. Se houver pelo menos uma fatura PAGA E não houver faturas vencidas → **LIBERA** (`liberado = true`)
6. Caso contrário → **BLOQUEIA**

#### Filtro 4: Verificação de Categoria
```php
// Linha 216
$categoriaOK = true; // TODO: Implementar filtro de categoria se necessário
```

**Status Atual:** Sempre retorna `true` (não há filtro por categoria implementado)

#### Filtro 5: Verificação de Status de Matrícula na Turma
```php
// Linha 219 - Condição de elegibilidade
$elegivel = ($examesOK && $financeiroOK && $categoriaOK && $aluno['status_matricula'] === 'disponivel');
```

**Importante:** O aluno só é elegível se `status_matricula === 'disponivel'`, ou seja, **não pode estar já matriculado nesta turma**.

### 3.3. Resumo do Critério de Elegibilidade

Um aluno é elegível para aparecer na lista se **TODAS** as condições abaixo forem verdadeiras:

1. ✅ `alunos.status = 'ativo'`
2. ✅ `alunos.cfc_id = turmas_teoricas.cfc_id`
3. ✅ `GuardsExames::alunoComExamesOkParaTeoricas($alunoId) === true`
   - Exame médico: tem resultado E resultado = 'apto' ou 'aprovado'
   - Exame psicotécnico: tem resultado E resultado = 'apto' ou 'aprovado'
4. ✅ `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)['liberado'] === true`
   - Tem matrícula ativa
   - Tem pelo menos uma fatura paga
   - Não tem faturas vencidas
5. ✅ `$aluno['status_matricula'] === 'disponivel'`
   - Não está matriculado nesta turma (sem registro em `turma_matriculas` com `status IN ('matriculado', 'cursando')`)

---

## 4. Papel do CFC da Sessão vs CFC da Turma

### 4.1. Como o CFC da Sessão é Definido

**Localização:** `admin/api/alunos-aptos-turma-simples.php`, linhas 64-70

```php
$user = getCurrentUser();
$cfcIdSessao = $user ? ((int)($user['cfc_id'] ?? 0)) : 0;
$isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null);
```

- **Admin Global:** `cfc_id = 0` ou `null` → pode gerenciar qualquer CFC
- **Admin de CFC específico:** `cfc_id > 0` → só pode gerenciar seu próprio CFC

### 4.2. Como o CFC da Turma é Definido

**Localização:** `admin/api/alunos-aptos-turma-simples.php`, linhas 52-62

```php
$turma = $db->fetch("
    SELECT cfc_id, curso_tipo 
    FROM turmas_teoricas 
    WHERE id = ?
", [$turmaId]);
$cfcIdTurma = (int)$turma['cfc_id'];
```

O CFC da turma vem diretamente do campo `turmas_teoricas.cfc_id`.

### 4.3. Como o Par (Sessão x Turma) é Usado na Seleção

**Localização:** `admin/api/alunos-aptos-turma-simples.php`, linhas 72-82

```php
$cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao);

// Bloquear acesso apenas se usuário de CFC específico tentar acessar turma de outro CFC
if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) {
    throw new Exception('Acesso negado: você não tem permissão para gerenciar turmas deste CFC');
}
```

**Comportamento:**
- **Admin Global:** Não há bloqueio de acesso, `$cfcIdsCoincidem = true` (sempre)
- **Admin de CFC específico:** Só pode acessar turmas do seu próprio CFC

**Importante:** Na query SQL (linha 122), **SEMPRE usa `$cfcIdTurma`** para filtrar alunos:

```php
WHERE a.status = 'ativo'
    AND a.cfc_id = ?  // ← Usa $cfcIdTurma, NÃO $cfcIdSessao
```

Isso significa que:
- ✅ Admin Global pode ver turmas de qualquer CFC, mas a lista de alunos sempre será filtrada pelo CFC da turma
- ✅ Admin de CFC específico só vê turmas do seu CFC, e a lista também é filtrada pelo CFC da turma (que coincide com o da sessão)

### 4.4. Resumo da Lógica de CFC

| Situação | CFC Sessão | CFC Turma | Acesso Permitido? | Alunos Retornados |
|----------|-----------|-----------|-------------------|-------------------|
| Admin Global | 0 | 1 | ✅ Sim | Alunos com `cfc_id = 1` |
| Admin Global | 0 | 36 | ✅ Sim | Alunos com `cfc_id = 36` |
| Admin CFC 1 | 1 | 1 | ✅ Sim | Alunos com `cfc_id = 1` |
| Admin CFC 1 | 1 | 36 | ❌ Não (bloqueado) | - |
| Admin CFC 36 | 36 | 36 | ✅ Sim | Alunos com `cfc_id = 36` |
| Admin CFC 36 | 36 | 1 | ❌ Não (bloqueado) | - |

---

## 5. Diagnóstico do Caso do Aluno Charles (ID: 167)

### 5.1. Informações Conhecidas

**Debug Info do Modal:**
- CFC da Turma: **1**
- CFC da Sessão: **0** (admin_global)
- Total candidatos: **0**
- Total aptos: **0**

**Dados do Aluno (da tela de histórico):**
- ✅ Exames médico e psicotécnico: Concluídos e APTO
- ✅ Financeiro: OK (situação regularizada)
- ✅ Status do aluno: Concluido (na tela de histórico)

### 5.2. Possíveis Causas da Exclusão

#### Causa 1: CFC do Aluno ≠ CFC da Turma

**Hipótese:** O aluno 167 tem `cfc_id = 36` (CFC canônico), mas a turma tem `cfc_id = 1` (legado).

**Evidência:**
- Documentos encontrados mencionam migração CFC 1 → 36 (`docs/IMPLEMENTACAO_CFC_ALUNOS_TURMAS.md`)
- A query filtra por `a.cfc_id = $cfcIdTurma` (linha 122)
- Se `aluno.cfc_id = 36` e `turma.cfc_id = 1`, o aluno não entra na query inicial

**Como Verificar:**
```sql
-- Verificar CFC do aluno 167
SELECT id, nome, cfc_id, status FROM alunos WHERE id = 167;

-- Verificar CFC da turma (assumindo turma_id = 16, mas pode ser outra)
SELECT id, nome, cfc_id FROM turmas_teoricas WHERE id = ?;
```

#### Causa 2: Status do Aluno ≠ 'ativo'

**Hipótese:** O aluno 167 tem `status = 'concluido'` ou outro valor diferente de 'ativo'.

**Evidência:**
- A tela de histórico mostra "Status: Concluido"
- A query filtra por `a.status = 'ativo'` (linha 121)
- Se o status for 'concluido', o aluno não entra na query

**Como Verificar:**
```sql
SELECT id, nome, status FROM alunos WHERE id = 167;
```

#### Causa 3: Aluno Já Está Matriculado na Turma OU Matrículas Órfãs

**Hipótese A:** O aluno 167 já está matriculado nesta turma teórica.

**Evidência:**
- A condição de elegibilidade exige `status_matricula === 'disponivel'` (linha 219)
- Se houver registro em `turma_matriculas` com `status IN ('matriculado', 'cursando')` para esta turma específica, o aluno é marcado como 'matriculado' e não aparece na lista

**Hipótese B (NOVA):** O aluno 167 possui matrículas órfãs (em turmas que foram excluídas).

**Evidência:**
- Se o aluno estava em uma turma que foi excluída, podem restar registros em `turma_matriculas` com status ativo
- Embora a query filtre por `turma_id` específico, matrículas órfãs podem indicar inconsistência de dados
- Pode haver problemas se a exclusão não foi feita corretamente

**Como Verificar:**
```sql
-- Verificar se aluno 167 está matriculado nesta turma específica
SELECT tm.*, tt.nome as turma_nome, tt.cfc_id as turma_cfc_id
FROM turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
AND tm.turma_id = ?  -- Substitua ? pelo turma_id
AND tm.status IN ('matriculado', 'cursando');

-- Verificar matrículas órfãs (em turmas excluídas)
SELECT tm.*
FROM turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
AND tt.id IS NULL  -- Turma não existe mais
AND tm.status IN ('matriculado', 'cursando');

-- Verificar TODAS as matrículas do aluno (histórico completo)
SELECT tm.*, tt.id as turma_existe, tt.nome as turma_nome
FROM turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
ORDER BY tm.data_matricula DESC;
```

#### Causa 4: Exames Não Passam na Validação

**Hipótese:** Os exames do aluno 167 não atendem aos critérios específicos da função `GuardsExames::alunoComExamesOkParaTeoricas()`.

**Evidência:**
- A função verifica:
  - Se o exame mais recente tem resultado lançado
  - Se o resultado está em `['apto', 'aprovado']`
- Se houver exames duplicados ou com status diferente, pode não passar

**Como Verificar:**
```sql
-- Verificar exames do aluno 167
SELECT id, tipo, status, resultado, data_resultado, data_agendada
FROM exames
WHERE aluno_id = 167
AND tipo IN ('medico', 'psicotecnico')
ORDER BY tipo, data_agendada DESC;
```

#### Causa 5: Financeiro Não Passa na Validação

**Hipótese:** O aluno 167 não tem pelo menos uma fatura paga, ou tem faturas vencidas.

**Evidência:**
- A função `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()` exige:
  - Pelo menos uma fatura paga
  - Nenhuma fatura vencida
- Se a validação financeira falhar, o aluno não aparece

**Como Verificar:**
```sql
-- Verificar faturas do aluno 167
SELECT id, valor_total, data_vencimento, status
FROM financeiro_faturas
WHERE aluno_id = 167
AND status != 'cancelada'
ORDER BY data_vencimento ASC;

-- Verificar pagamentos
SELECT p.*, f.data_vencimento, f.status as fatura_status
FROM pagamentos p
JOIN financeiro_faturas f ON p.fatura_id = f.id
WHERE f.aluno_id = 167;
```

### 5.3. Resultado do Diagnóstico Executado

**Data:** 12/12/2025  
**Turma testada:** 16 (não encontrada - foi excluída)

**Problemas identificados:**

1. **Status do aluno = 'concluido' (BLOQUEADOR CRÍTICO)**
   - Aluno 167 tem `status = 'concluido'`
   - Query exige `a.status = 'ativo'` (linha 121)
   - **IMPACTO:** Aluno não passa no filtro inicial, independente de outros critérios

2. **Turma 16 foi excluída**
   - Turma não existe no banco de dados
   - Confirma que foi excluída

**Causa raiz confirmada:** Status do aluno 'concluido' bloqueia a seleção.

Ver detalhes completos em: `docs/DIAGNOSTICO_ALUNO_167_RESULTADO.md`

---

### 5.4. Diagnóstico Mais Provável (Teórico - Antes do Teste)

Baseado nos documentos encontrados (`docs/IMPLEMENTACAO_CFC_ALUNOS_TURMAS.md`) e na informação de que **o aluno estava em uma turma que foi excluída**, há duas causas possíveis:

**CAUSA 1: Matrículas Órfãs em Turmas Excluídas (NOVO)**

- ⚠️ **Aluno 167 estava matriculado em uma turma que foi excluída**
- ⚠️ A exclusão da turma pode ter deixado registros órfãos em `turma_matriculas`
- ⚠️ Se as matrículas órfãs estão com status `'matriculado'` ou `'cursando'`, podem causar problemas
- ❌ **IMPORTANTE:** A query da API usa `LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id AND tm.turma_id = ?`, então matrículas em outras turmas (mesmo excluídas) não deveriam afetar diretamente, MAS pode haver inconsistências nos dados

**CAUSA 2: Divergência de CFC entre aluno e turma**

- ✅ Aluno 167 tem `cfc_id = 36` (CFC canônico)
- ✅ Turma tem `cfc_id = 1` (legado, precisa migrar para 36)
- ❌ Query filtra por `a.cfc_id = $cfcIdTurma` (1), então o aluno (36) não entra

**Evidência Adicional:**
- O documento menciona este caso: "O modal não mostrava o aluno 167 (Charles) mesmo com exames e financeiro OK."
- Causa raiz identificada: "Turma 16 → `turmas_teoricas.cfc_id = 1` (legado, deve ser 36)"
- **NOVA INFORMAÇÃO:** Aluno estava em turma que foi excluída

**Prioridade de Investigação:**
1. **PRIMEIRO:** Verificar se há matrículas órfãs (em turmas excluídas) com status ativo
2. **SEGUNDO:** Verificar divergência de CFC entre aluno e turma

---

## 6. Hipóteses de Correção (SEM Implementar)

### 6.1. Hipótese 1: Migração de CFC da Turma

**Abordagem:** Migrar a turma de `cfc_id = 1` para `cfc_id = 36` (CFC canônico).

**Quando Aplicar:** Se a causa for confirmada como divergência de CFC.

**Considerações:**
- ✅ Alinha turma com o CFC canônico (36)
- ✅ Permite que alunos do CFC 36 apareçam na lista
- ⚠️ Pode afetar outras funcionalidades que dependem do CFC da turma
- ⚠️ Precisa verificar se há outras turmas com CFC 1 que também precisam migrar

### 6.2. Hipótese 2: Ajustar Query para Usar CFC da Turma Corretamente

**Abordagem:** Garantir que a query sempre use o CFC correto, mesmo quando há valores legados.

**Quando Aplicar:** Se houver necessidade de manter compatibilidade com CFCs legados.

**Considerações:**
- ⚠️ Complexidade adicional (mapeamento de CFCs legados)
- ⚠️ Pode mascarar problemas de migração
- ✅ Permite funcionamento temporário enquanto migrações são feitas

### 6.3. Hipótese 3: Permitir Alunos Concluídos na Lista

**Abordagem:** Remover ou ajustar o filtro `a.status = 'ativo'` para incluir alunos com status 'concluido'.

**Quando Aplicar:** Se a causa for status do aluno.

**Considerações:**
- ⚠️ Pode não ser desejável permitir matricular alunos já concluídos
- ⚠️ Precisa validar regra de negócio: alunos concluídos podem ser rematriculados?
- ✅ Pode ser necessário apenas para casos específicos

### 6.4. Hipótese 4: Ajustar Validação de Exames

**Abordagem:** Revisar a lógica de `GuardsExames::alunoComExamesOkParaTeoricas()` para garantir compatibilidade com todos os formatos de dados.

**Quando Aplicar:** Se exames estão OK no histórico mas não passam na validação.

**Considerações:**
- ✅ Garante consistência entre histórico e modal
- ⚠️ Pode mascarar problemas de dados inconsistentes
- ✅ Já existe função centralizada, só precisa ajustar a lógica

---

## 7. Próximos Passos Recomendados

1. **Executar Script de Diagnóstico:**
   - Criar script temporário que verifica todos os critérios para o aluno 167
   - Executar queries de verificação listadas na seção 5.2
   - Coletar logs de `error_log` do servidor após tentar abrir o modal

2. **Confirmar Causa Raiz:**
   - Verificar `cfc_id` do aluno 167 vs `cfc_id` da turma
   - Verificar `status` do aluno 167
   - Verificar se há matrícula ativa na turma

3. **Decidir Correção:**
   - Se for CFC: Executar migração conforme `docs/MIGRACAO_CFC_1_PARA_36.md`
   - Se for status: Validar regra de negócio e ajustar query/filtros
   - Se for exames/financeiro: Ajustar funções de validação

4. **Implementar Correção (Próxima Tarefa):**
   - Seguir regra de ouro: testes → código → validação
   - Criar testes automatizados para o cenário do aluno 167
   - Executar testes manuais após correção

---

## 8. Arquivos de Referência

- `admin/api/alunos-aptos-turma-simples.php` - API principal de busca de alunos
- `admin/includes/guards_exames.php` - Validação de exames
- `admin/includes/FinanceiroAlunoHelper.php` - Validação financeira
- `admin/pages/turmas-teoricas-detalhes-inline.php` - Modal e frontend
- `docs/IMPLEMENTACAO_CFC_ALUNOS_TURMAS.md` - Documentação sobre migração CFC
- `docs/MIGRACAO_CFC_1_PARA_36.md` - Scripts de migração (se existir)

---

**Próxima Etapa:** Executar diagnóstico específico do aluno 167 para confirmar causa raiz antes de implementar correção.

