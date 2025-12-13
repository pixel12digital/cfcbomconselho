# Queries SQL de Diagnóstico - Aluno 167 (Charles) para Turma Teórica

Este documento contém as queries SQL necessárias para diagnosticar por que o aluno 167 não aparece na lista de alunos aptos para matrícula em turma teórica.

**⚠️ IMPORTANTE:** Substitua `?` pelo `turma_id` da turma que você está tentando matricular (ex: 16).

---

## 1. Dados Básicos do Aluno 167

```sql
SELECT id, nome, cpf, status, cfc_id
FROM alunos
WHERE id = 167;
```

**Verificar:**
- ✅ `status = 'ativo'` (se for diferente, aluno não passará no filtro da query)
- ✅ `cfc_id` (deve ser igual ao `cfc_id` da turma)

---

## 2. Dados da Turma

```sql
-- Substitua ? pelo turma_id (ex: 16)
SELECT id, nome, cfc_id, curso_tipo, status
FROM turmas_teoricas
WHERE id = ?;
```

**Verificar:**
- `cfc_id` da turma (deve ser igual ao `cfc_id` do aluno 167)

---

## 3. Compatibilidade de CFC

**Comparar os resultados das queries 1 e 2:**
- Se `alunos.cfc_id ≠ turmas_teoricas.cfc_id`, o aluno não entrará na query inicial

**Query para verificar diretamente:**
```sql
-- Substitua ? pelo turma_id
SELECT 
    a.id as aluno_id,
    a.nome as aluno_nome,
    a.cfc_id as aluno_cfc_id,
    t.id as turma_id,
    t.nome as turma_nome,
    t.cfc_id as turma_cfc_id,
    CASE 
        WHEN a.cfc_id = t.cfc_id THEN 'COMPATÍVEL'
        ELSE 'INCOMPATÍVEL - Aluno não passará no filtro'
    END as compatibilidade_cfc
FROM alunos a
CROSS JOIN turmas_teoricas t
WHERE a.id = 167
AND t.id = ?;
```

---

## 4. Verificação de Matrícula na Turma

```sql
-- Substitua ? pelo turma_id
SELECT 
    tm.*,
    tt.nome as turma_nome,
    tt.cfc_id as turma_cfc_id
FROM turma_matriculas tm
JOIN turmas_teoricas tt ON tm.turma_id = tt.id
WHERE tm.aluno_id = 167
AND tm.turma_id = ?
AND tm.status IN ('matriculado', 'cursando');
```

**Interpretação:**
- ✅ Se retornar **vazio**: Aluno não está matriculado → `status_matricula = 'disponivel'` → **OK para aparecer na lista**
- ❌ Se retornar **algum registro**: Aluno já está matriculado → `status_matricula = 'matriculado'` → **NÃO aparecerá na lista**

---

## 5. Verificação de Exames

```sql
SELECT 
    id,
    tipo,
    status,
    resultado,
    data_resultado,
    data_agendada
FROM exames
WHERE aluno_id = 167
AND tipo IN ('medico', 'psicotecnico')
ORDER BY tipo, data_agendada DESC;
```

**Interpretação:**
Para cada tipo (`medico` e `psicotecnico`), verificar:

1. **Existe exame mais recente?**
   - Deve existir pelo menos um registro de cada tipo

2. **Tem resultado lançado?**
   - Campo `resultado` não deve ser NULL, vazio ou 'pendente'
   - OU campo `data_resultado` deve estar preenchido

3. **Resultado é apto/aprovado?**
   - Campo `resultado` deve estar em `['apto', 'aprovado']`

**Se algum dos exames não atender aos critérios acima, o aluno não passará na validação de exames.**

---

## 6. Verificação Financeira

### 6.1. Matrícula Ativa

```sql
SELECT id, aluno_id, status, data_inicio
FROM matriculas
WHERE aluno_id = 167
AND status = 'ativa'
ORDER BY data_inicio DESC
LIMIT 1;
```

**Verificar:**
- ✅ Deve existir pelo menos uma matrícula com `status = 'ativa'`

### 6.2. Faturas do Aluno

```sql
SELECT 
    id,
    valor_total,
    data_vencimento,
    status,
    CASE 
        WHEN status IN ('aberta', 'parcial') AND data_vencimento < CURDATE() THEN 'VENCIDA'
        WHEN status = 'paga' THEN 'PAGA'
        WHEN status IN ('aberta', 'parcial') AND data_vencimento >= CURDATE() THEN 'ABERTA (não vencida)'
        ELSE 'OUTRO'
    END as situacao
FROM financeiro_faturas
WHERE aluno_id = 167
AND status != 'cancelada'
ORDER BY data_vencimento ASC;
```

**Interpretação:**
- ❌ Se não houver nenhuma fatura: Aluno bloqueado (`NAO_LANCADO`)
- ❌ Se houver faturas `VENCIDA`: Aluno bloqueado (`EM_ATRASO`)
- ✅ Se houver pelo menos uma fatura `PAGA` E nenhuma `VENCIDA`: Financeiro OK

### 6.3. Pagamentos (para verificar se há fatura paga)

```sql
SELECT 
    p.*,
    f.data_vencimento,
    f.status as fatura_status,
    f.valor_total,
    CASE 
        WHEN COALESCE(p.valor_pago, 0) >= f.valor_total AND f.valor_total > 0 THEN 'TOTALMENTE_PAGA'
        WHEN COALESCE(p.valor_pago, 0) > 0 THEN 'PARCIALMENTE_PAGA'
        ELSE 'NAO_PAGA'
    END as situacao_pagamento
FROM financeiro_faturas f
LEFT JOIN (
    SELECT fatura_id, SUM(valor_pago) as valor_pago
    FROM pagamentos
    GROUP BY fatura_id
) p ON f.id = p.fatura_id
WHERE f.aluno_id = 167
AND f.status != 'cancelada'
ORDER BY f.data_vencimento ASC;
```

**Interpretação:**
- ✅ Se houver pelo menos uma fatura `TOTALMENTE_PAGA`: Financeiro OK
- ❌ Se não houver nenhuma fatura paga: Aluno bloqueado

---

## 7. Simulação da Query de Candidatos (Query Real Usada pela API)

```sql
-- Substitua ? pelo turma_id
SELECT 
    a.id,
    a.nome,
    a.status as status_aluno,
    a.cfc_id as aluno_cfc_id,
    c.nome as cfc_nome,
    CASE 
        WHEN tm.id IS NOT NULL THEN 'matriculado'
        ELSE 'disponivel'
    END as status_matricula
FROM alunos a
JOIN cfcs c ON a.cfc_id = c.id
LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
    AND tm.turma_id = ? 
    AND tm.status IN ('matriculado', 'cursando')
WHERE a.id = 167
    AND a.status = 'ativo'
    AND a.cfc_id = (
        SELECT cfc_id FROM turmas_teoricas WHERE id = ?
    )
ORDER BY a.nome;
```

**Nota:** Na segunda query, substitua o segundo `?` pelo mesmo `turma_id`.

**Interpretação:**
- ✅ Se retornar **1 linha**: Aluno passou na query inicial
- ❌ Se retornar **vazio**: Aluno não passou na query inicial (verificar `status` ou `cfc_id`)

---

## 8. Checklist de Diagnóstico Completo

Execute as queries acima e verifique cada critério:

- [ ] **Query 1**: Aluno 167 existe e tem `status = 'ativo'`?
- [ ] **Query 2**: Turma existe e tem `cfc_id`?
- [ ] **Query 3**: `aluno.cfc_id = turma.cfc_id`?
- [ ] **Query 4**: Aluno NÃO está matriculado nesta turma?
- [ ] **Query 5**: Exames médico e psicotécnico têm resultado 'apto' ou 'aprovado'?
- [ ] **Query 6.1**: Aluno tem matrícula ativa?
- [ ] **Query 6.2**: Não há faturas vencidas?
- [ ] **Query 6.3**: Existe pelo menos uma fatura paga?
- [ ] **Query 7**: Aluno é retornado pela query de candidatos?

---

## 9. Causa Mais Provável (Baseado na Documentação)

Segundo `docs/IMPLEMENTACAO_CFC_ALUNOS_TURMAS.md`, a causa mais provável é:

**Divergência de CFC:**
- Aluno 167 tem `cfc_id = 36` (CFC canônico)
- Turma tem `cfc_id = 1` (legado, precisa migrar para 36)

**Solução:** Migrar a turma de CFC 1 para CFC 36 (ver `docs/MIGRACAO_CFC_1_PARA_36.md`)

---

## 10. Como Executar

### Opção 1: Via Cliente MySQL (Recomendado para diagnóstico rápido)

```bash
mysql -h auth-db803.hstgr.io -u u502697186_cfcbomconselho -p u502697186_cfcbomconselho
```

Depois cole e execute as queries uma por uma, substituindo `?` pelo `turma_id`.

### Opção 2: Via phpMyAdmin ou Interface Web

1. Acesse o painel de controle da Hostinger
2. Abra phpMyAdmin
3. Selecione o banco `u502697186_cfcbomconselho`
4. Execute as queries acima no SQL

### Opção 3: Via Script PHP no Navegador

Acesse: `admin/tools/diagnostico-aluno-167-turma-teorica.php?turma_id=16`

(Substitua 16 pelo ID da turma)

---

**Próximos Passos:** Após identificar a causa, seguir para implementação da correção conforme `docs/AUDITORIA_TURMAS_TEORICAS_MATRICULA.md`.

