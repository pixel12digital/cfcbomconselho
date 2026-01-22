# Correções Aplicadas - Agenda Teórica

## Problemas Identificados e Resolvidos

### 1. Aulas teóricas não apareciam na agenda

**Causa Raiz:**
- A `theory_session` foi criada, mas as `lessons` teóricas não foram criadas automaticamente
- Provável causa: sessão criada antes da matrícula do aluno na turma, ou erro silencioso na criação

**Solução:**
- Script `tools/fix_missing_theory_lessons.php` criado para gerar lessons faltantes
- Lesson ID 8 criada para a sessão do dia 15/01/2026
- **Validação confirmada:** Query agora retorna 1 resultado para 15/01/2026

**Query Final (com filtros):**
```sql
SELECT MIN(l.id) as id,
       l.cfc_id,
       MIN(l.student_id) as student_id,
       ...
       l.status as lesson_status,
       ts.status as session_status,
       COALESCE(ts.status, MIN(l.status)) as status,
       ...
FROM lessons l
INNER JOIN theory_sessions ts ON l.theory_session_id = ts.id
WHERE l.cfc_id = 1
  AND l.scheduled_date BETWEEN '2026-01-15' AND '2026-01-15'
  AND l.type = 'teoria'
  AND l.theory_session_id IS NOT NULL
  AND l.status != 'cancelada' 
  AND ts.status != 'canceled'
GROUP BY l.theory_session_id, l.scheduled_date, l.scheduled_time, ...
```

### 2. View=list com data específica trazia range mensal

**Causa:**
- Lógica calculava 1 mês antes e depois da data selecionada

**Correção Aplicada:**
- `app/Controllers/AgendaController.php` linha 87-102
- Quando `view=list` e há data específica: usar EXATAMENTE esse dia (`startDate = $date`, `endDate = $date`)
- Range mensal apenas quando não há data específica selecionada

**Código:**
```php
if ($view === 'list') {
    if ($date && $date !== date('Y-m-d')) {
        // Data específica: usar EXATAMENTE esse dia
        $startDate = $date;
        $endDate = $date;
    } else {
        // Sem data: período amplo
        $startDate = null;
        $endDate = null;
    }
}
```

### 3. Badge "CONCLUÍDA" duplicado

**Causa:**
- `$isCompleted` era calculado duas vezes:
  1. Linha ~235: cálculo correto com `session_status` para teóricas
  2. Linha ~283: recálculo duplicado que sobrescrevia o valor

**Correção Aplicada:**
- Removido recálculo duplicado na linha ~283
- Badge agora usa apenas o valor calculado na linha ~235-241
- Fonte única de verdade:
  - **Prática:** `l.status = 'concluida'`
  - **Teórica:** `ts.status = 'done'` OU `l.status = 'concluida'`

**Código:**
```php
// Linha ~235-241: Cálculo único
if ($isTheory) {
    $isCompleted = ($lesson['session_status'] ?? '') === 'done' || ($lesson['status'] ?? '') === 'concluida';
} else {
    $isCompleted = ($lessonStatus === 'concluida');
}

// Linha ~279-291: Usa valor já calculado (sem recalcular)
if ($isCompleted):
    // Renderiza badge
endif;
```

### 4. Query retornando `session_status` para teóricas

**Correção:**
- Query de teóricas agora retorna:
  - `lesson_status`: `MIN(l.status)` (status da lesson)
  - `session_status`: `ts.status` (status da sessão)
  - `status`: `COALESCE(ts.status, MIN(l.status))` (status unificado)

- Query de práticas retorna:
  - `lesson_status`: `l.status`
  - `session_status`: `NULL`
  - `status`: `l.status`

## Validação SQL

**Resultado da validação (`tools/validate_theory_lessons.php`):**

```
A) Lessons teóricas no banco para 15/01/2026:
   Total encontrado: 1 ✅
   - Lesson ID: 8, Horário: 14:00:00, Status Lesson: agendada, Status Session: scheduled

B) Query da agenda (findByPeriodWithTheoryDedupe):
   Total retornado: 1 ✅
   - Data: 2026-01-15 14:00:00, Disciplina: Direção Defensiva, Alunos: 1

C) Theory_sessions para 15/01/2026:
   Total encontrado: 1 ✅
   - Session ID: 1, Início: 2026-01-15 14:00:00, Status: scheduled
```

## Arquivos Modificados

1. `app/Models/Lesson.php`
   - Query de teóricas: adicionado `session_status` e `lesson_status`
   - Query de práticas: adicionado `session_status` (NULL) para manter estrutura

2. `app/Controllers/AgendaController.php`
   - Corrigido cálculo de período para view=list com data específica

3. `app/Views/agenda/index.php`
   - Removido recálculo duplicado de `$isCompleted`
   - Badge usa `session_status` para teóricas

4. `tools/fix_missing_theory_lessons.php` (novo)
   - Script para criar lessons faltantes para sessions existentes

5. `tools/validate_theory_lessons.php` (novo)
   - Script de validação para diagnosticar problemas

## Próximos Passos Recomendados

1. **Prevenir criação de sessions sem lessons:**
   - Adicionar validação no `TheorySessionsController::criar()` para garantir que há alunos matriculados antes de criar a sessão
   - Ou criar lessons automaticamente quando aluno é matriculado em turma que já tem sessions

2. **Melhorar tratamento de erros:**
   - Logar erros silenciosos na criação de lessons
   - Mostrar mensagem ao usuário se nenhuma lesson foi criada

3. **Testar cenários:**
   - Criar sessão com 0 alunos matriculados
   - Criar sessão com alunos sem matrícula ativa
   - Criar sessão com alunos já tendo lessons para essa sessão (idempotência)
