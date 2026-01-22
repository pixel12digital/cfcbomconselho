# Auditoria da API de Alunos Aptos para Turma Teórica

**Data:** 12/12/2025  
**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`  
**Objetivo:** Documentar a regra atual e identificar pontos de fragilidade para correção robusta

---

## 1. Query SQL Atual

**Localização:** Linhas 107-136

```sql
SELECT 
    a.id, a.nome, a.cpf, a.categoria_cnh, a.status as status_aluno,
    c.nome as cfc_nome, c.id as cfc_id,
    m_ativa.categoria_cnh, m_ativa.tipo_servico,
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
WHERE a.status = 'ativo'  -- ← PROBLEMA: Hardcoded, não permite outros status
    AND a.cfc_id = ?      -- ← Usa cfcIdTurma (correto)
ORDER BY a.nome
```

**Parâmetros:**
- `$turmaId` (primeiro `?`) - usado no LEFT JOIN de `turma_matriculas`
- `$cfcIdTurma` (segundo `?`) - usado no WHERE para filtrar por CFC

---

## 2. Regras Atuais Identificadas

### 2.1. CFC

**Lógica atual (linhas 64-82):**
- Admin Global (`cfc_id = 0`): pode acessar qualquer turma, mas alunos são filtrados por `cfcIdTurma`
- Admin de CFC específico (`cfc_id > 0`): só pode acessar turmas do próprio CFC
- **Query sempre usa `cfcIdTurma`** para filtrar alunos (linha 134)

**Status:** ✅ **CORRETO** - A lógica está adequada

### 2.2. Status do Aluno

**Lógica atual (linha 133):**
- `WHERE a.status = 'ativo'` - **HARDCODED**
- Alunos com status diferente de 'ativo' são excluídos

**Status:** ⚠️ **FRÁGIL** - Não permite configuração de status permitidos

**Problemas:**
- Não permite outros status que podem ser válidos (ex: 'em_andamento')
- Não há constante/configuração centralizada
- Difícil de manter e evoluir

### 2.3. Exames

**Lógica atual (linha 236):**
- Usa `GuardsExames::alunoComExamesOkParaTeoricas($alunoId)`
- Função centralizada - ✅ **BOM**

**Status:** ✅ **CORRETO** - Usa função centralizada

### 2.4. Financeiro

**Lógica atual (linhas 240-241):**
- Usa `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)`
- Função centralizada - ✅ **BOM**

**Status:** ✅ **CORRETO** - Usa função centralizada

### 2.5. Status de Matrícula na Turma

**Lógica atual (linha 251):**
- Exige `status_matricula === 'disponivel'`
- Alunos já matriculados nesta turma não aparecem

**Status:** ✅ **CORRETO** - Faz sentido do ponto de vista de negócio

---

## 3. Comparação com Histórico do Aluno

**Arquivo:** `admin/pages/historico-aluno.php`

**Funções usadas no histórico:**
1. **Exames:** `GuardsExames::verificarBloqueioTeorica($alunoId)` (linha 286)
   - Internamente usa `GuardsExames::alunoComExamesOkParaTeoricas()` ✅
2. **Financeiro:** `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno($alunoId)` (linha 303) ✅

**Card "Liberado para Aulas Teóricas" (linhas 1464-1479):**
- Aparece quando: `$bloqueioTeorica['pode_prosseguir'] === true`
- `verificarBloqueioTeorica()` verifica:
  - Exames OK (via `alunoComExamesOkParaTeoricas()`)
  - Inadimplência OK (via `verificarInadimplencia()`)

**Conclusão:**
- ✅ API já usa as mesmas funções centralizadas para exames e financeiro
- ⚠️ **DIVERGÊNCIA:** Histórico usa `verificarBloqueioTeorica()` que também verifica inadimplência, mas a API usa apenas `verificarPermissaoFinanceiraAluno()`
- ⚠️ **DIVERGÊNCIA:** Histórico não verifica status do aluno (mostra para qualquer status), mas API filtra por `status = 'ativo'`

---

## 4. Pontos de Fragilidade Identificados

### 4.1. Status do Aluno Hardcoded

**Problema:**
- `WHERE a.status = 'ativo'` está hardcoded na query
- Não permite outros status válidos (ex: 'em_andamento')
- Difícil de manter e evoluir

**Solução proposta:**
- Criar constante/array de status permitidos
- Usar `IN (...)` na query
- Exemplo: `$statusPermitidos = ['ativo', 'em_andamento'];`

### 4.2. Inconsistência com Histórico

**Problema:**
- Histórico mostra "Liberado" para qualquer status de aluno
- API filtra apenas alunos 'ativo'
- Pode causar confusão: histórico diz "liberado" mas aluno não aparece na lista

**Solução proposta:**
- Manter filtro de status na API (faz sentido do ponto de vista de negócio)
- Mas tornar configurável e bem documentado

### 4.3. Verificação de Inadimplência

**Problema:**
- Histórico usa `verificarBloqueioTeorica()` que verifica inadimplência
- API usa apenas `verificarPermissaoFinanceiraAluno()`
- Pode haver diferença na lógica

**Solução proposta:**
- Verificar se `verificarPermissaoFinanceiraAluno()` já cobre inadimplência
- Se não, considerar usar `verificarBloqueioTeorica()` na API também

---

## 5. Regra de Negócio Esperada (Nova)

### 5.1. CFC

**Manter como está:**
- Admin Global: filtra alunos por `cfcIdTurma`
- Admin CFC: filtra alunos por `cfcIdTurma` (que deve coincidir com `cfcIdSessao`)

### 5.2. Status do Aluno

**Nova regra:**
- Permitir status configuráveis: `['ativo', 'em_andamento']`
- Excluir: `['concluido', 'cancelado']`
- Criar constante: `STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA`

### 5.3. Elegibilidade

**Manter como está:**
- Exames: `GuardsExames::alunoComExamesOkParaTeoricas()`
- Financeiro: `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`
- Status matrícula: `'disponivel'`

**Considerar:**
- Usar `verificarBloqueioTeorica()` para garantir consistência total com histórico

---

## 6. Próximos Passos

1. ✅ Implementar constante de status permitidos
2. ✅ Ajustar query para usar `IN (...)` ao invés de `= 'ativo'`
3. ✅ Verificar se `verificarPermissaoFinanceiraAluno()` cobre tudo que `verificarBloqueioTeorica()` cobre
4. ✅ Criar testes automatizados
5. ✅ Validar manualmente no modal

