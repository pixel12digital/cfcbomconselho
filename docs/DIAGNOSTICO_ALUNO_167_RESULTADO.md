# Resultado do Diagnóstico - Aluno 167 (Charles) não aparece na lista

**Data:** 12/12/2025  
**Turma investigada:** 16 (não encontrada - foi excluída)

---

## 🚨 PROBLEMAS CRÍTICOS IDENTIFICADOS

### Problema 1: Status do Aluno = 'concluido' (BLOQUEADOR CRÍTICO)

**Situação:**
- Aluno 167 tem `status = 'concluido'`
- A query de candidatos exige `a.status = 'ativo'` (linha 121 de `admin/api/alunos-aptos-turma-simples.php`)

**Impacto:**
- ⚠️ **BLOQUEADOR DIRETO**: O aluno **NÃO PASSARÁ** no filtro inicial da query
- Mesmo que exames e financeiro estejam OK, o aluno não aparecerá na lista
- Isso acontece ANTES de qualquer outra validação

**Query que bloqueia:**
```sql
WHERE a.status = 'ativo'  -- ← Este filtro exclui o aluno
    AND a.cfc_id = ?
```

**Solução:**
- Verificar regra de negócio: alunos 'concluidos' podem ser rematriculados em novas turmas?
- Se SIM, atualizar status:
  ```sql
  UPDATE alunos SET status = 'ativo' WHERE id = 167;
  ```
- Se NÃO, a regra de negócio está correta e alunos concluídos não devem aparecer

---

### Problema 2: Turma 16 foi Excluída

**Situação:**
- A turma 16 não existe no banco de dados
- Foi excluída (confirmado pela ausência na tabela `turmas_teoricas`)

**Impacto:**
- Não é possível verificar compatibilidade de CFC
- Não é possível verificar matrícula específica nesta turma
- Mas podemos verificar matrículas órfãs (se houver)

---

## 🔍 INVESTIGAÇÃO ADICIONAL NECESSÁRIA

### 1. Verificar Matrículas Órfãs

Se o aluno estava na turma 16 quando ela foi excluída, pode haver matrícula órfã:

```sql
-- Verificar matrículas órfãs (em turmas excluídas)
SELECT tm.*
FROM turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
AND tt.id IS NULL  -- Turma não existe mais
AND tm.status IN ('matriculado', 'cursando');
```

**Ação se encontrar matrículas órfãs:**
- Atualizar para status 'cancelada':
  ```sql
  UPDATE turma_matriculas 
  SET status = 'cancelada', atualizado_em = NOW() 
  WHERE aluno_id = 167 
  AND turma_id IN (
      SELECT turma_id FROM turma_matriculas tm
      LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
      WHERE tm.aluno_id = 167 AND tt.id IS NULL
  );
  ```

### 2. Verificar CFC do Aluno

- Aluno tem `cfc_id = 36`
- Verificar qual CFC tem as turmas que você está tentando matricular
- Se for CFC diferente, o aluno não aparecerá (filtro adicional)

---

## ✅ CONCLUSÃO

**Causa Raiz Principal:**
O aluno 167 não aparece na lista porque:
1. **Status = 'concluido'** → bloqueia no filtro `a.status = 'ativo'` (BLOQUEADOR CRÍTICO)
2. **Turma 16 foi excluída** → não pode verificar matrícula nesta turma específica

**Análise da Regra de Negócio:**
- ✅ **A regra atual está CORRETA**: Alunos 'concluidos' não devem aparecer automaticamente em novas turmas
- ✅ **Faz sentido do ponto de vista de negócio**: "Concluído" normalmente é quem já terminou o curso/processo
- ✅ **Para um novo curso, o correto seria**: Abrir novo processo e esse novo aluno/processo viria como "ativo"

**O que está fora do padrão:**
- Usar um aluno concluído como aluno de teste para simular matrícula em turma

---

## 🔧 SOLUÇÃO PARA HOMOLOG (Ambiente de Teste)

Como estamos em ambiente de teste e queremos usar o Charles como "aluno em andamento" para validar todo o fluxo, o caminho mais simples e seguro é:

### 1. Reabrir o aluno apenas em HOMOLOG

```sql
-- Reabrir o aluno de teste (somente em HOMOLOG)
UPDATE alunos 
SET status = 'ativo' 
WHERE id = 167;
```

### 2. (Opcional) Limpar matrículas órfãs

Se quiser deixar tudo bem limpinho, cancelar matrículas órfãs em turmas que já não existem:

```sql
-- Cancelar matrículas órfãs em turmas que já não existem
UPDATE turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
SET tm.status = 'cancelada', tm.atualizado_em = NOW()
WHERE tm.aluno_id = 167
  AND tt.id IS NULL
  AND tm.status IN ('matriculado', 'cursando');
```

**⚠️ IMPORTANTE:** Execute essas queries **APENAS em HOMOLOG**, não em produção!

---

## ✅ O QUE DEVE ACONTECER DEPOIS

### Na ficha do aluno:
- Status deixar de ser "CONCLUÍDO" e passar a "ATIVO" (ou equivalente na interface)

### No modal "Matricular Alunos na Turma":
- Ao abrir de novo para a turma de teste, o Charles deve passar a aparecer na lista de candidatos aptos

### A partir daí, conseguimos:
- ✅ Matricular esse aluno na turma teórica
- ✅ Seguir pros testes de frequência/presença
- ✅ Só depois avançar para a parte prática

---

## 📋 SOBRE A REGRA DEFINITIVA (PRODUÇÃO)

**Para produção, o ideal é manter assim:**
- ✅ Alunos concluídos **não entram** na lista de candidatos

**Se o CFC precisar rematricular alguém, o fluxo "certo" é:**
- Reabrir o processo do aluno (com uma ação explícita, tipo "Reabrir Processo / Nova Turma")
- OU criar um novo cadastro/processo para ele

**Futuro:**
- Se necessário, implementar fluxo de "Reabrir aluno concluído" com botão no admin, em vez de depender de SQL manual

---

## 🎯 PRÓXIMO PASSO AGORA

1. ✅ Rodar o UPDATE no aluno 167 em homolog
2. ✅ Abrir de novo o modal "Matricular Alunos na Turma"
3. ✅ Verificar se o Charles apareceu na lista

---

## 📋 SQL de Correção Sugerido

```sql
-- 1. Atualizar status do aluno (se apropriado)
UPDATE alunos SET status = 'ativo' WHERE id = 167;

-- 2. Limpar matrículas órfãs (se houver)
UPDATE turma_matriculas tm
LEFT JOIN turmas_teoricas tt ON tm.turma_id = tt.id
SET tm.status = 'cancelada', tm.atualizado_em = NOW()
WHERE tm.aluno_id = 167
AND tt.id IS NULL
AND tm.status IN ('matriculado', 'cursando');
```

**⚠️ IMPORTANTE:** Execute essas queries apenas após validar a regra de negócio!

---

## 📄 Arquivo SQL de Correção

Para facilitar, criei o arquivo `docs/CORRECAO_ALUNO_167_HOMOLOG.sql` com todas as queries necessárias, incluindo validações.

**Uso:**
1. Abrir o arquivo SQL no phpMyAdmin (ou cliente MySQL)
2. Selecionar o banco de homolog
3. Executar as queries na ordem
4. Verificar os resultados das queries de validação

**⚠️ IMPORTANTE:** Execute essas queries **APENAS em HOMOLOG**, não em produção!

