# Dedupe de Sessões Teóricas na Agenda do Instrutor

## ✅ Problema Resolvido

**Problema:** Agenda do instrutor mostrava a mesma sessão teórica repetida N vezes (uma por aluno), gerando ruído visual.

**Solução:** Implementado agrupamento por `theory_session_id` para lessons teóricas.

---

## 🔧 Implementação

### 1. Novo Método no Model `Lesson`

**Arquivo:** `app/Models/Lesson.php`

**Método:** `findByInstructorWithTheoryDedupe()`

**Estratégia:**
- **Aulas Práticas:** Query normal (sem agrupamento)
- **Aulas Teóricas:** Query com `GROUP BY theory_session_id` + `COUNT(DISTINCT student_id)`
- **UNION:** Combina práticas e teóricas agrupadas

**Campos retornados para teóricas:**
- `theory_session_id` - ID da sessão teórica
- `class_id` - ID da turma (para link de presença)
- `student_count` - Quantidade de alunos na sessão
- `student_names` - Nomes dos alunos (GROUP_CONCAT)
- `lesson_type` - 'teoria' ou 'pratica'

### 2. Atualização dos Controllers

**AgendaController::index()** (linha ~120)
- Substituído query direta por `$lessonModel->findByInstructorWithTheoryDedupe()`

**DashboardController::dashboardInstrutor()** (linha ~167)
- Substituído query direta por `$lessonModel->findByInstructorWithTheoryDedupe()`

### 3. Atualização das Views

**app/Views/agenda/index.php**

**Lista (view=list):**
- Detecta `lesson_type === 'teoria'` ou `theory_session_id` não vazio
- Mostra "📚 Sessão Teórica (X alunos)" ao invés de nome do aluno
- Link aponta para tela de presença: `/turmas-teoricas/{classId}/sessoes/{sessionId}/presenca`
- Tipo de aula: "Aula Teórica" vs "Aula Prática"

**Calendário (view=week/day):**
- Mesma lógica de detecção
- Card mostra "📚 Sessão Teórica" + contagem de alunos
- Link para presença

---

## 📊 Query SQL

```sql
-- Aulas Práticas (normais)
SELECT l.*, s.name as student_name, v.plate as vehicle_plate, ...
FROM lessons l
WHERE l.type = 'pratica' AND l.instructor_id = ?

-- Aulas Teóricas (agrupadas)
SELECT MIN(l.id) as id, 
       l.theory_session_id,
       ts.class_id,
       COUNT(DISTINCT l.student_id) as student_count,
       'teoria' as lesson_type,
       GROUP_CONCAT(DISTINCT s.name ORDER BY s.name SEPARATOR ', ') as student_names,
       ...
FROM lessons l
INNER JOIN theory_sessions ts ON l.theory_session_id = ts.id
WHERE l.type = 'teoria' AND l.instructor_id = ?
GROUP BY l.theory_session_id, l.scheduled_date, l.scheduled_time

-- UNION
(SELECT ... FROM pratica) UNION (SELECT ... FROM teoria) ORDER BY ...
```

---

## ✅ Resultado

**Antes:**
- Instrutor via: "Aula - João", "Aula - Maria", "Aula - Pedro" (3 cards para mesma sessão)

**Depois:**
- Instrutor vê: "📚 Sessão Teórica (3 alunos)" (1 card único)
- Ao clicar: vai direto para tela de presença

**Aluno:**
- Continua vendo normalmente (filtro por `student_id` já evita duplicação)

---

## 🔒 Idempotência

**Problema:** Duplo clique em "criar sessão" poderia duplicar lessons.

**Solução implementada:**
1. Verificação antes de criar: `SELECT id FROM theory_sessions WHERE class_id = ? AND discipline_id = ? AND starts_at = ?`
2. Transação: `beginTransaction()` → criar session + lessons → `commit()`
3. Rollback em caso de erro

**Arquivo:** `app/Controllers/TheorySessionsController::criar()`
