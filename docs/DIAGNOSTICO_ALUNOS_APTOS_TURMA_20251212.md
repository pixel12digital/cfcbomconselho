# Diagnóstico Completo - API Alunos Aptos para Turma Teórica

**Data:** 12/12/2025  
**API:** `admin/api/alunos-aptos-turma-simples.php`  
**Problema:** Modal "Matricular Alunos na Turma" mostra "Nenhum aluno encontrado" mesmo com alunos aptos

---

## 1. Problema Identificado

**Sintoma:**
- Modal "Matricular Alunos na Turma" exibe: "Nenhum aluno encontrado com exames médico e psicotécnico aprovados"
- Debug mostra: `Total candidatos: 0`, `Total aptos: 0`
- Aluno 167 (Charles) tem exames OK e financeiro OK (confirmado no histórico)

**Contexto:**
- Turma: ID 19
- Aluno de teste: ID 167 (Charles Dietrich)
- CFC da Turma: 1
- CFC da Sessão: 0 (admin_global)

---

## 2. Auditoria Realizada

### 2.1. API Identificada

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`  
**Método:** POST  
**Chamado por:** `admin/pages/turmas-teoricas-detalhes-inline.php` (linha 13021)

### 2.2. Correção Planejada Já Aplicada

A API já estava atualizada conforme o plano (`docs/PLANO_IMPL_PRODUCAO_CFC.md`):

- ✅ Constante `STATUS_ALUNO_PERMITIDOS_TURMA_TEORICA = ['ativo', 'em_andamento']` existe
- ✅ Query usa `WHERE a.status IN (...)` ao invés de `= 'ativo'` hardcoded
- ✅ Usa funções centralizadas:
  - `GuardsExames::alunoComExamesOkParaTeoricas()`
  - `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`

### 2.3. Lógica de CFC

**Status:** ✅ **CORRETO**

- Admin Global (`cfc_id = 0`): filtra alunos por `cfcIdTurma` (CFC da turma)
- Admin CFC específico (`cfc_id > 0`): filtra alunos por `cfcIdTurma` (que deve = `cfcIdSessao`)
- Query sempre usa `cfcIdTurma` para filtrar alunos (linha 177)

---

## 3. Melhorias Implementadas

### 3.1. Debug Aprimorado

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`

**Adicionado diagnóstico automático quando `total_candidatos = 0`:**
- Conta total de alunos com status permitidos no CFC
- Conta total de alunos no CFC (qualquer status)
- Mostra distribuição de status dos alunos
- Verifica se o CFC existe (pode estar faltando e causar problema no JOIN)

**Logs detalhados:**
```php
error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: Total de alunos com status permitidos no CFC {$cfcIdTurma}: {$totalAlunosStatusOK}");
error_log("[TURMAS TEORICAS API] DIAGNÓSTICO: Total de alunos no CFC {$cfcIdTurma} (qualquer status): {$totalAlunosCfc}");
```

### 3.2. Debug Info Melhorado

**Adicionado ao `debug_info` retornado pela API:**
- `cfc_usado_na_query`: CFC efetivamente usado (sempre `cfcIdTurma`)
- `total_com_exames_ok`: Contador de candidatos com exames OK
- `total_com_financeiro_ok`: Contador de candidatos com financeiro OK
- `total_com_categoria_ok`: Contador de candidatos com categoria OK
- `total_disponivel`: Contador de candidatos disponíveis (não matriculados)

### 3.3. Script de Diagnóstico Criado

**Arquivo:** `admin/tools/diagnostico-alunos-aptos-api.php`

Script HTML interativo que:
- Verifica dados básicos do aluno
- Verifica dados da turma
- Testa query base (status + CFC)
- Testa query completa (com JOINs)
- Verifica exames, financeiro e matrícula
- Mostra resumo final com todos os critérios

**Uso:**
```
admin/tools/diagnostico-alunos-aptos-api.php?turma_id=19&aluno_id=167
```

### 3.4. Queries SQL de Diagnóstico

**Arquivo:** `admin/tools/auditoria-aluno-167-turma-19.sql`

Conjunto de queries SQL para diagnóstico manual:
- Dados básicos do aluno
- Dados da turma
- Teste incremental da query (status → CFC → JOINs)
- Verificação de exames
- Verificação financeiro
- Resumo final

---

## 4. Possíveis Causas do Problema

Com base na auditoria, as causas mais prováveis são:

### 4.1. Status do Aluno ❓

**Hipótese:** Aluno 167 não está com status `'ativo'` ou `'em_andamento'`

**Como verificar:**
```sql
SELECT id, nome, status FROM alunos WHERE id = 167;
```

**Solução:** Atualizar status do aluno:
```sql
UPDATE alunos SET status = 'ativo' WHERE id = 167;
```

### 4.2. CFC Incompatível ❓

**Hipótese:** CFC do aluno é diferente do CFC da turma

**Como verificar:**
```sql
SELECT 
    (SELECT cfc_id FROM alunos WHERE id = 167) as aluno_cfc,
    (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) as turma_cfc;
```

**Solução:** Se for diferente, corrigir CFC do aluno ou usar turma do mesmo CFC

### 4.3. CFC Não Existe ❓

**Hipótese:** CFC da turma não existe na tabela `cfcs`, causando exclusão no JOIN

**Como verificar:**
```sql
SELECT id, nome FROM cfcs WHERE id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19);
```

**Solução:** Criar o CFC se não existir

---

## 5. Próximos Passos

1. **Executar script de diagnóstico:**
   - Acessar: `admin/tools/diagnostico-alunos-aptos-api.php?turma_id=19&aluno_id=167`
   - Verificar qual critério está falhando

2. **Verificar logs do servidor:**
   - Procurar por `[TURMAS TEORICAS API] DIAGNÓSTICO:`
   - Verificar contadores e distribuição de status

3. **Executar queries SQL de diagnóstico:**
   - Executar `admin/tools/auditoria-aluno-167-turma-19.sql`
   - Identificar em qual passo o aluno é excluído

4. **Corrigir problema identificado:**
   - Status do aluno → atualizar para 'ativo' ou 'em_andamento'
   - CFC incompatível → ajustar CFC do aluno ou usar turma correta
   - CFC não existe → criar CFC faltante

5. **Validar correção:**
   - Abrir modal "Matricular Alunos na Turma"
   - Verificar se aluno 167 aparece na lista
   - Tentar matricular e confirmar sucesso

---

## 6. Arquivos Modificados/Criados

### Modificados:
- `admin/api/alunos-aptos-turma-simples.php`
  - Adicionado diagnóstico automático quando não há candidatos
  - Melhorado `debug_info` com contadores intermediários

### Criados:
- `admin/tools/diagnostico-alunos-aptos-api.php` - Script de diagnóstico interativo
- `admin/tools/auditoria-aluno-167-turma-19.sql` - Queries SQL de diagnóstico
- `docs/DIAGNOSTICO_ALUNOS_APTOS_TURMA_20251212.md` - Este documento

---

## 7. Resultado Esperado Após Correção

- Modal "Matricular Alunos na Turma" mostra lista de alunos aptos
- Debug mostra `total_candidatos > 0` e `total_aptos >= 1`
- Aluno 167 aparece na lista (se atender todos os critérios)
- É possível matricular o aluno na turma sem erros

---

## 8. Testes Recomendados

1. **Teste manual:**
   - Abrir modal para turma 19
   - Verificar se aluno 167 aparece
   - Matricular e confirmar sucesso

2. **Teste automatizado:**
   - Executar `tests/api/test-alunos-aptos-turma-api.php`
   - Validar todos os cenários

---

**Status:** ✅ Auditoria completa realizada  
**Causa raiz identificada:** Ver `docs/DIAGNOSTICO_ALUNOS_APTOS_TURMA_RESULTADO.md`

---

## 9. Resultado do Diagnóstico Executado

✅ **Diagnóstico executado com sucesso!**

**Problemas identificados:**

1. ❌ **Status do aluno:** `'concluido'` (deveria ser `'ativo'` ou `'em_andamento'`)
2. ❌ **CFC incompatível:** Aluno no CFC 36, turma no CFC 1

**Solução:** Ver arquivo `admin/tools/correcao-aluno-167-homolog.sql`

**Detalhes completos:** Ver `docs/DIAGNOSTICO_ALUNOS_APTOS_TURMA_RESULTADO.md`

