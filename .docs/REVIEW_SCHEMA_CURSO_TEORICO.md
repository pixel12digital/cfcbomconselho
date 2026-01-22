# Review: Schema e Estratégia Híbrida - Curso Teórico

## ✅ Schema - Índices e Uniques

### Tabelas Criadas

#### `theory_disciplines`
- **PRIMARY KEY:** `id`
- **Índices:** `cfc_id`, `active`, `sort_order`
- **Unique:** Nenhum (permite disciplinas com mesmo nome em CFCs diferentes)

#### `theory_courses`
- **PRIMARY KEY:** `id`
- **Índices:** `cfc_id`, `active`
- **Unique:** Nenhum

#### `theory_course_disciplines`
- **PRIMARY KEY:** `id`
- **UNIQUE KEY:** `course_discipline` (`course_id`, `discipline_id`) ✅ **Evita duplicidade**
- **Índices:** `course_id`, `discipline_id`, `sort_order`

#### `theory_classes`
- **PRIMARY KEY:** `id`
- **Índices:** `cfc_id`, `course_id`, `instructor_id`, `status`, `start_date`
- **Unique:** Nenhum

#### `theory_sessions`
- **PRIMARY KEY:** `id`
- **Índices:** `class_id`, `discipline_id`, `lesson_id`, `starts_at`, `status`
- **Unique:** Nenhum (permite múltiplas sessões da mesma disciplina na mesma turma)
- **Campo `lesson_id`:** FK opcional para `lessons.id` (integração com agenda)

#### `theory_enrollments`
- **PRIMARY KEY:** `id`
- **UNIQUE KEY:** `class_student` (`class_id`, `student_id`) ✅ **Evita matrícula duplicada**
- **Índices:** `class_id`, `student_id`, `enrollment_id`, `status`

#### `theory_attendance`
- **PRIMARY KEY:** `id`
- **UNIQUE KEY:** `session_student` (`session_id`, `student_id`) ✅ **Evita presença duplicada**
- **Índices:** `session_id`, `student_id`, `status`, `marked_by`

### Modificações em `lessons`

#### Campo `type`
```sql
ALTER TABLE lessons 
MODIFY COLUMN type ENUM('pratica','teoria') NOT NULL DEFAULT 'pratica';
```
✅ Agora aceita `'teoria'`

#### Campo `vehicle_id`
```sql
ALTER TABLE lessons 
MODIFY COLUMN vehicle_id int(11) DEFAULT NULL;
```
✅ Agora é opcional (NULL para aulas teóricas)

#### Novo campo `theory_session_id`
```sql
ALTER TABLE lessons
ADD COLUMN theory_session_id int(11) DEFAULT NULL,
ADD KEY theory_session_id (theory_session_id),
ADD CONSTRAINT lessons_ibfk_theory_session 
  FOREIGN KEY (theory_session_id) REFERENCES theory_sessions (id) ON DELETE SET NULL;
```
✅ Vincula `lessons` → `theory_sessions` (rastreabilidade reversa)

---

## 🔄 Estratégia Híbrida: theory_sessions ↔ lessons

### Fluxo de Criação de Sessão Teórica

1. **Criar `theory_sessions`** (metadados):
   - `class_id`, `discipline_id`, `starts_at`, `ends_at`, `location`, `status`
   - `lesson_id` = NULL inicialmente

2. **Criar `lessons`** (para agenda):
   - `type` = `'teoria'`
   - `student_id` = **PROBLEMA:** Sessão teórica não tem aluno específico (é turma)
   - `enrollment_id` = **PROBLEMA:** Sessão teórica não tem matrícula específica
   - `instructor_id` = da turma (`theory_classes.instructor_id`)
   - `vehicle_id` = NULL (aula teórica)
   - `scheduled_date` = `DATE(starts_at)`
   - `scheduled_time` = `TIME(starts_at)`
   - `duration_minutes` = `TIMESTAMPDIFF(MINUTE, starts_at, ends_at)`
   - `theory_session_id` = `theory_sessions.id` (vinculação reversa)

3. **Atualizar `theory_sessions.lesson_id`**:
   - Após criar `lessons`, atualizar `theory_sessions.lesson_id = lessons.id`

### ⚠️ Problema Identificado

**Sessão teórica é coletiva (turma), mas `lessons` exige `student_id` e `enrollment_id`.**

**Soluções possíveis:**

#### Opção A: Criar lesson "fantasma" por sessão (recomendada)
- Criar 1 `lesson` por sessão com `student_id` = NULL ou dummy
- Usar `theory_session_id` para identificar que é sessão teórica
- Agenda mostra a sessão, mas não vincula a aluno específico
- **Problema:** `lessons.student_id` é NOT NULL

#### Opção B: Criar lesson por aluno matriculado (mais complexa)
- Ao criar sessão, criar 1 `lesson` para cada aluno matriculado na turma
- Cada aluno vê sua própria "aula teórica" na agenda
- **Vantagem:** Agenda individualizada
- **Desvantagem:** Muitos registros (1 sessão = N lessons)

#### Opção C: Tornar `student_id` e `enrollment_id` opcionais em `lessons`
- Modificar schema para permitir NULL
- Criar 1 `lesson` por sessão sem aluno específico
- **Vantagem:** Simples
- **Desvantagem:** Quebra validações existentes

### ✅ Decisão: Opção B (Criar lesson por aluno)

**Justificativa:**
- Mantém integridade do schema (`student_id` NOT NULL)
- Cada aluno vê sua própria aula teórica na agenda
- Permite marcar presença individualmente
- Compatível com sistema de notificações existente

**Implementação:**
```php
// Ao criar theory_sessions:
1. Criar registro em theory_sessions (metadados)
2. Buscar todos os alunos matriculados na turma (theory_enrollments WHERE class_id = X AND status = 'active')
3. Para cada aluno:
   - Criar lesson com:
     - student_id = aluno.id
     - enrollment_id = theory_enrollment.enrollment_id (se houver)
     - instructor_id = theory_class.instructor_id
     - vehicle_id = NULL
     - type = 'teoria'
     - scheduled_date = DATE(starts_at)
     - scheduled_time = TIME(starts_at)
     - duration_minutes = TIMESTAMPDIFF(MINUTE, starts_at, ends_at)
     - theory_session_id = theory_sessions.id
   - Atualizar theory_sessions.lesson_id = primeiro lesson criado (para referência)
```

---

## 📊 Relacionamentos

```
theory_courses (template)
    ↓ (1:N)
theory_course_disciplines
    ↓ (N:1)
theory_disciplines

theory_courses
    ↓ (1:N)
theory_classes (turma)
    ↓ (1:N)
theory_sessions (sessão)
    ↓ (1:N via theory_enrollments)
theory_enrollments (matrícula aluno na turma)
    ↓ (1:N)
theory_attendance (presença)

theory_sessions
    ↓ (1:N)
lessons (type='teoria', theory_session_id)
    ↓ (N:1)
students
```

---

## ✅ Checklist de Integridade

- [x] UNIQUE em `theory_course_disciplines` (evita disciplina duplicada no curso)
- [x] UNIQUE em `theory_enrollments` (evita aluno duplicado na turma)
- [x] UNIQUE em `theory_attendance` (evita presença duplicada)
- [x] FK `theory_sessions.lesson_id` → `lessons.id` (opcional, para referência)
- [x] FK `lessons.theory_session_id` → `theory_sessions.id` (opcional, rastreabilidade)
- [x] `lessons.type` aceita `'teoria'`
- [x] `lessons.vehicle_id` pode ser NULL
- [ ] **PENDENTE:** Decidir estratégia de criação de `lessons` para sessões teóricas
