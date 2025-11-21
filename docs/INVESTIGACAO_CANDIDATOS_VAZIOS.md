# Investigação: Por que não há candidatos na seleção de alunos para turmas teóricas

## Problema Reportado

Modal "Matricular Alunos na Turma" mostra:
- Total candidatos: 0
- Total aptos: 0
- Mensagem: "Nenhum aluno encontrado com exames médico e psicotécnico aprovados."

Mesmo com aluno 167 (Charles) tendo:
- Exames médico e psicotécnico concluídos e aptos
- Financeiro OK (sem faturas vencidas)
- CFC correto (CFC 36, mesmo da turma - CFC canônico do CFC Bom Conselho)

**Nota:** Neste ambiente, o CFC Bom Conselho é representado pelo CFC ID 36. O ID 1 é legado e deve ser migrado para 36.

## Análise da Query

### Query Atual (linha ~87-108)

```sql
SELECT 
    a.id,
    a.nome,
    a.cpf,
    a.categoria_cnh,
    a.status as status_aluno,
    c.nome as cfc_nome,
    c.id as cfc_id,
    CASE 
        WHEN tm.id IS NOT NULL THEN 'matriculado'
        ELSE 'disponivel'
    END as status_matricula
FROM alunos a
JOIN cfcs c ON a.cfc_id = c.id
LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
    AND tm.turma_id = ? 
    AND tm.status IN ('matriculado', 'cursando')
WHERE a.status = 'ativo'
    AND a.cfc_id = ?
ORDER BY a.nome
```

**Parâmetros:**
- `$turmaId` (primeiro parâmetro) - usado no LEFT JOIN
- `$cfcIdTurma` (segundo parâmetro) - usado no WHERE

### Verificações Implementadas

1. **Logs antes da query:**
   - `[TURMAS TEORICAS API] Executando query - turma_id={$turmaId}, cfc_id_turma={$cfcIdTurma}`

2. **Logs após a query:**
   - `[TURMAS TEORICAS API] Turma {id} - Total candidatos brutos (antes de qualquer filtro): {n}`
   - Log de cada candidato bruto encontrado
   - Verificação específica se aluno 167 está nos candidatos

3. **Tratamento de erro:**
   - Try-catch na query para capturar erros SQL
   - Log de erro com stack trace

## Possíveis Causas

### 1. Aluno 167 não está com status 'ativo'

**Verificação:** Log mostrará `status_aluno` de cada candidato bruto.

**Solução:** Se aluno 167 estiver com status diferente de 'ativo', ajustar status ou remover filtro se necessário.

### 2. Aluno 167 não está no CFC 1

**Verificação:** Log mostrará `cfc_id` de cada candidato bruto.

**Solução:** Se aluno 167 estiver em outro CFC, ajustar CFC do aluno ou verificar se turma está no CFC correto.

### 3. Query retornando vazia por erro SQL

**Verificação:** Log de erro será capturado no try-catch.

**Solução:** Corrigir erro SQL ou estrutura da query.

### 4. Aluno 167 está marcado como matriculado na turma

**Verificação:** Log mostrará `status_matricula` de cada candidato.

**Solução:** Se aluno 167 estiver com `status_matricula = 'matriculado'`, ele não será elegível (correto).

## Logs Esperados

### Se aluno 167 estiver nos candidatos brutos:

```
[TURMAS TEORICAS API] Executando query - turma_id=16, cfc_id_turma=1
[TURMAS TEORICAS API] Turma 16 - Total candidatos brutos (antes de qualquer filtro): 1
[TURMAS TEORICAS API] CANDIDATO BRUTO - aluno_id=167, nome=Charles Dietrich Wutzke, cfc_id=1, status_aluno=ativo, status_matricula=disponivel
[TURMAS TEORICAS API] ✅ ALUNO 167 ENCONTRADO NOS CANDIDATOS BRUTOS - nome=Charles Dietrich Wutzke, cfc_id=1, status_aluno=ativo, status_matricula=disponivel
[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== 
[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id=1, session_cfc_id=0 (admin_global)
[TURMAS TEORICAS API] Aluno 167 - exames_ok=true, financeiro_ok=true, categoria_ok=true, status_matricula=disponivel, elegivel=true
[TURMAS TEORICAS API] ================================= 
```

### Se aluno 167 NÃO estiver nos candidatos brutos:

```
[TURMAS TEORICAS API] Executando query - turma_id=16, cfc_id_turma=1
[TURMAS TEORICAS API] Turma 16 - Total candidatos brutos (antes de qualquer filtro): 0
[TURMAS TEORICAS API] ❌ ALUNO 167 NÃO ENCONTRADO NOS CANDIDATOS BRUTOS - Verificar se aluno está ativo e no CFC 1
```

## Próximos Passos

1. **Executar a API e verificar logs:**
   - Abrir modal "Matricular Alunos na Turma"
   - Verificar logs do servidor (error_log)
   - Identificar se aluno 167 está nos candidatos brutos

2. **Se aluno 167 NÃO estiver nos candidatos brutos:**
   - Verificar status do aluno no banco: `SELECT id, nome, status, cfc_id FROM alunos WHERE id = 167`
   - Verificar CFC da turma: `SELECT id, nome, cfc_id FROM turmas_teoricas WHERE id = 16`
   - Executar query manualmente no phpMyAdmin para confirmar

3. **Se aluno 167 estiver nos candidatos brutos mas não elegível:**
   - Verificar logs de exames: `[GUARDS EXAMES] Aluno 167...`
   - Verificar logs de financeiro: `[FINANCEIRO] Aluno 167...`
   - Verificar `status_matricula` no log

4. **Corrigir conforme necessário:**
   - Ajustar status do aluno se necessário
   - Ajustar CFC se necessário
   - Corrigir lógica de exames/financeiro se necessário

## Arquivos Modificados

### `admin/api/alunos-aptos-turma-simples.php`

1. **Linha ~84-108:** Query com logs detalhados
   - Adicionado `a.status as status_aluno` no SELECT
   - Adicionado try-catch para capturar erros SQL
   - Log antes da query

2. **Linha ~110-130:** Logs após a query
   - Log de total de candidatos brutos
   - Log de cada candidato bruto encontrado
   - Verificação específica se aluno 167 está nos candidatos

## Garantias

✅ **Query usa CFC da turma:** `WHERE a.cfc_id = ?` com `$cfcIdTurma`
✅ **Logs detalhados:** Cada etapa da query e filtragem é logada
✅ **Tratamento de erro:** Erros SQL são capturados e logados
✅ **Verificação específica:** Aluno 167 é verificado explicitamente

