# Resultado do Diagnóstico - API Alunos Aptos para Turma Teórica

**Data:** 12/12/2025  
**Aluno de Teste:** 167 (Charles Dietrich Wutzke)  
**Turma:** 19 (Turma A - Formação CNH AB)

---

## ✅ Diagnóstico Completo Realizado

O script de diagnóstico (`admin/tools/diagnostico-alunos-aptos-api.php`) foi executado e identificou **exatamente** as causas do problema.

---

## 🔴 Problemas Encontrados

### Problema 1: Status do Aluno ❌

**Situação Atual:**
- Status do aluno 167: `'concluido'`
- Status esperados: `['ativo', 'em_andamento']`

**Impacto:**
- Aluno é excluído na query base: `WHERE a.status IN ('ativo', 'em_andamento')`
- Não chega nem nos filtros de exames/financeiro

**Validação:**
- Exames: ✅ OK (ambos APTOS)
- Financeiro: ✅ OK (liberado)
- Status matrícula: ✅ OK (não matriculado na turma)

**Conclusão:** Exames e financeiro estão OK, mas o status impede o aluno de aparecer na lista.

---

### Problema 2: CFC Incompatível ❌

**Situação Atual:**
- CFC do aluno 167: `36`
- CFC da turma 19: `1`

**Impacto:**
- Aluno é excluído na query: `WHERE a.cfc_id = 1` (CFC da turma)
- Mesmo que o status fosse corrigido, ainda não apareceria

**Nota:** A API filtra sempre pelo CFC da turma (não do usuário logado), o que está correto do ponto de vista de negócio.

---

## 🔧 Soluções Necessárias

### Solução 1: Atualizar Status do Aluno

**Para ambiente de HOMOLOG/TESTE:**

```sql
-- Atualizar status do aluno 167 para 'ativo'
UPDATE alunos 
SET status = 'ativo' 
WHERE id = 167;
```

**⚠️ IMPORTANTE:** 
- Esta correção é apenas para ambiente de teste/homolog
- Em produção, alunos concluídos não devem aparecer automaticamente
- O correto seria ter um fluxo de "Reabrir processo" ou criar novo cadastro

---

### Solução 2: Corrigir CFC do Aluno OU Usar Turma do CFC Correto

**Opção A: Atualizar CFC do aluno (para teste)**

```sql
-- Atualizar CFC do aluno 167 para o CFC da turma 19
UPDATE alunos 
SET cfc_id = 1 
WHERE id = 167;
```

**Opção B: Usar turma do CFC 36 (mais correto)**

- Criar uma turma teórica com `cfc_id = 36`
- OU usar uma turma existente do CFC 36 para testes

**⚠️ IMPORTANTE:**
- Em produção, alunos devem estar sempre no CFC correto
- CFC 36 parece ser o CFC canônico do "CFC Bom Conselho" (conforme mencionado na documentação)
- CFC 1 pode ser um CFC antigo ou diferente

---

## 📋 Script SQL de Correção Completo

**Arquivo:** `admin/tools/correcao-aluno-167-homolog.sql`

```sql
-- =====================================================
-- CORREÇÃO PARA HOMOLOG - Aluno 167 + Turma 19
-- =====================================================
-- 
-- ⚠️ EXECUTAR APENAS EM HOMOLOG/TESTE
-- ⚠️ NÃO executar em produção sem validação
-- 
-- Problemas encontrados:
-- 1. Status do aluno = 'concluido' (deveria ser 'ativo')
-- 2. CFC do aluno = 36, mas turma é CFC 1
-- 
-- =====================================================

-- 1. Atualizar status do aluno para 'ativo'
UPDATE alunos 
SET status = 'ativo' 
WHERE id = 167;

-- Verificar se funcionou
SELECT id, nome, status, cfc_id 
FROM alunos 
WHERE id = 167;
-- Esperado: status = 'ativo'

-- 2. Atualizar CFC do aluno para o CFC da turma 19
-- ⚠️ ATENÇÃO: Isso muda o CFC do aluno. Se o CFC 36 for o correto,
-- considere criar uma turma no CFC 36 ao invés disso.
UPDATE alunos 
SET cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19)
WHERE id = 167;

-- Verificar se funcionou
SELECT 
    a.id, 
    a.nome, 
    a.status, 
    a.cfc_id as aluno_cfc_id,
    (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) as turma_cfc_id,
    CASE 
        WHEN a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) 
        THEN 'OK' 
        ELSE 'DIFERENTE' 
    END as status_compatibilidade
FROM alunos a
WHERE id = 167;
-- Esperado: aluno_cfc_id = turma_cfc_id

-- =====================================================
-- VALIDAÇÃO FINAL
-- =====================================================

-- Verificar se o aluno agora passa na query base
SELECT 
    a.id, 
    a.nome, 
    a.status, 
    a.cfc_id,
    CASE 
        WHEN a.status IN ('ativo', 'em_andamento') THEN 'Status OK'
        ELSE CONCAT('Status NÃO permitido: ', a.status)
    END as verif_status,
    CASE 
        WHEN a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19) THEN 'CFC OK'
        ELSE CONCAT('CFC diferente: aluno=', a.cfc_id, ', turma=', (SELECT cfc_id FROM turmas_teoricas WHERE id = 19))
    END as verif_cfc
FROM alunos a
WHERE id = 167
  AND a.status IN ('ativo', 'em_andamento')
  AND a.cfc_id = (SELECT cfc_id FROM turmas_teoricas WHERE id = 19);

-- Se retornar 1 linha, o aluno passou na query base ✅
-- Se retornar 0 linhas, ainda há problema ❌
```

---

## 🎯 Resultado Esperado Após Correção

Após executar as correções:

1. ✅ Status do aluno será `'ativo'`
2. ✅ CFC do aluno será compatível com a turma 19
3. ✅ Query base retornará o aluno
4. ✅ Query completa retornará o aluno
5. ✅ Exames OK (já estava OK)
6. ✅ Financeiro OK (já estava OK)
7. ✅ **Aluno aparecerá na lista do modal "Matricular Alunos na Turma"**

---

## ⚠️ Observações Importantes

### Sobre o Status 'concluido'

- A regra atual (excluir alunos concluídos) está **correta** do ponto de vista de negócio
- Alunos concluídos normalmente já terminaram o curso
- Para um novo curso, o correto seria:
  - Reabrir o processo do aluno (com ação explícita)
  - OU criar um novo cadastro/processo

### Sobre o CFC

- **CFC 36** parece ser o CFC canônico do "CFC Bom Conselho" (conforme documentação)
- **CFC 1** pode ser um CFC antigo ou diferente
- Em produção, alunos devem estar sempre no CFC correto
- A API filtra corretamente pelo CFC da turma (não do usuário logado)

### Recomendações para Produção

1. **Não atualizar status de alunos concluídos para 'ativo'** automaticamente
2. **Criar fluxo de "Reabrir Processo"** com botão no admin
3. **Validar CFC** ao criar/editar turmas e alunos
4. **Manter consistência** entre CFC do aluno e CFC das turmas

---

## 📝 Próximos Passos

1. ✅ Diagnóstico completo realizado
2. ⏳ Executar script de correção em HOMOLOG
3. ⏳ Validar no modal "Matricular Alunos na Turma"
4. ⏳ Confirmar que aluno aparece na lista
5. ⏳ Tentar matricular e confirmar sucesso

---

**Status:** ✅ Causa raiz identificada  
**Ação necessária:** Executar script de correção (apenas em homolog/teste)

