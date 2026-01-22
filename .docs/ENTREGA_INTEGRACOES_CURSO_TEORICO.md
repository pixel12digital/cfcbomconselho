# Entrega: Fase Integrações - Curso Teórico

## ✅ Status: Implementação Completa

---

## 📋 1. Integração na Matrícula

### Tabelas Afetadas
- ✅ **`enrollments`** - Adicionados campos opcionais `theory_course_id`, `theory_class_id`
- ✅ **`theory_enrollments`** - Criado registro (upsert idempotente via UNIQUE KEY)

### Implementação

**Migration 026:** `database/migrations/026_add_theory_fields_to_enrollments.sql`
- Adiciona 2 campos opcionais em `enrollments`
- FKs para `theory_courses` e `theory_classes`

**Controller:** `app/Controllers/AlunosController.php`
- Método `matricular()`: Busca cursos/turmas para o formulário
- Método `criarMatricula()`:
  - Recebe `theory_course_id` e/ou `theory_class_id`
  - Valida turma/curso antes de criar enrollment
  - Se `theory_class_id` informado → cria `theory_enrollment` (idempotente)
  - Transação: enrollment + theory_enrollment

**View:** `app/Views/alunos/matricular.php`
- Seção colapsável "Curso Teórico (Opcional)"
- Select para template de curso
- Select para turma (filtrado por curso selecionado)

**Idempotência:**
- ✅ UNIQUE KEY `class_student` em `theory_enrollments` previne duplicidade
- ✅ Verificação antes de criar: `isEnrolled()`
- ✅ Transação com rollback

---

## 📋 2. Integração no Progresso (steps/student_steps)

### Tabelas Afetadas
- ✅ **`steps`** - Adicionado 1 registro (INSERT): `CURSO_TEORICO` (order 4)
- ✅ **`student_steps`** - UPDATE status baseado em `theory_attendance`

### Implementação

**Migration 027:** `database/migrations/027_add_curso_teorico_step.sql`
- Insere step `CURSO_TEORICO` com order 4 (antes de PROVA_TEORICA)
- Atualiza order das etapas subsequentes

**Service:** `app/Services/TheoryProgressService.php`
- Método `updateTheoryStepStatus()`:
  - Busca todas sessões concluídas da turma (`status = 'done'`)
  - Verifica presenças do aluno (deve ser `present` ou `justified`)
  - Se TODAS as sessões têm presença válida → conclui step
  - UPDATE `student_steps` (ou cria se não existir)

**Integração Automática:**
- ✅ Step `CURSO_TEORICO` é criado automaticamente em `student_steps` pelo fluxo normal
- ✅ Atualização automática ao marcar presença (se sessão está `done`)
- ✅ Atualização ao cancelar sessão

**Controller:** `app/Controllers/TheoryAttendanceController.php`
- Ao salvar presença → chama `TheoryProgressService::updateTheoryStepStatus()`
- Gera notificação quando curso é concluído

**View:** `app/Views/dashboard/aluno.php`
- Seção "Curso Teórico" com:
  - Nome do curso/turma
  - Barra de progresso (% de sessões concluídas)
  - Contador de sessões
- Timeline mostra step "Curso Teórico" com % de progresso

---

## 📋 3. Notificações Internas

### Eventos Implementados

#### `theory_class_enrolled`
**Quando:** Aluno matriculado em turma (`TheoryEnrollmentsController::criar()`)
**Link:** `/turmas-teoricas/{classId}`

#### `theory_session_scheduled`
**Quando:** Sessão criada (`TheorySessionsController::criar()`)
**Link:** `/turmas-teoricas/{classId}/sessoes/{sessionId}/presenca`

#### `theory_session_canceled`
**Quando:** Sessão cancelada (`TheorySessionsController::cancelar()`)
**Link:** `/turmas-teoricas/{classId}`

#### `theory_attendance_marked`
**Quando:** Aluno marcado como ausente (`TheoryAttendanceController::salvar()`)
**Link:** `/turmas-teoricas/{classId}/sessoes/{sessionId}/presenca`

#### `theory_course_completed`
**Quando:** Curso teórico concluído (todas sessões com presença válida)
**Link:** `/dashboard`

**Implementação:**
- Usa `Notification::createNotification()` existente
- Busca `user_id` via `student_id` para enviar notificação

---

## 📋 4. RBAC (PermissionService::check)

### Permissões Criadas

**Seed 003:** `database/seeds/003_seed_theory_permissions.sql`

**Módulos:**
- `disciplinas`: view, create, update, delete
- `cursos_teoricos`: view, create, update, delete
- `turmas_teoricas`: view, create, update, delete
- `presenca_teorica`: view, create, update

**Roles:**
- **ADMIN:** Todas as permissões
- **SECRETARIA:** view, create, update (não delete)
- **INSTRUTOR:** view turmas, view/create/update presença

**Controllers:**
- ✅ `ConfiguracoesController` - Validação ADMIN (já existe)
- ✅ `TheoryClassesController` - `PermissionService::check('turmas_teoricas', ...)`
- ✅ `TheorySessionsController` - `PermissionService::check('turmas_teoricas', ...)`
- ✅ `TheoryEnrollmentsController` - `PermissionService::check('turmas_teoricas', ...)`
- ✅ `TheoryAttendanceController` - `PermissionService::check('presenca_teorica', ...)`

---

## 📋 5. Editar Sessão (Propagação para Lessons)

### Implementação

**Controller:** `TheorySessionsController::atualizar()`

**Funcionalidade:**
- Atualiza `theory_sessions` (horário/local)
- **Propagação:** Atualiza todas as `lessons` relacionadas via `theory_session_id`
- Query: `UPDATE lessons SET scheduled_date = ?, scheduled_time = ?, duration_minutes = ? WHERE theory_session_id = ?`
- Transação: session + lessons

**View:** `app/Views/theory_sessions/form.php`
- Suporta criação e edição
- Disciplina não pode ser alterada após criação (disabled)
- Campos pré-preenchidos na edição

**Rota:** `POST /turmas-teoricas/{classId}/sessoes/{sessionId}/atualizar`

---

## ✅ Checklist Final

### 1. Integração na Matrícula
- [x] Adicionar campos `theory_course_id` e `theory_class_id` em `enrollments`
- [x] Atualizar formulário de matrícula
- [x] Criar `theory_enrollment` se turma selecionada (idempotente)
- [x] Validação: turma existe e está ativa
- [x] Transação: enrollment + theory_enrollment

### 2. Integração no Progresso
- [x] Criar step `CURSO_TEORICO` (order 4)
- [x] Step criado automaticamente em `student_steps`
- [x] Atualização automática baseada em `theory_attendance`
- [x] Condição de conclusão: todas sessões `done` com presença `present` ou `justified`
- [x] Exibir progresso no dashboard do aluno

### 3. Notificações
- [x] `theory_class_enrolled` - Aluno matriculado
- [x] `theory_session_scheduled` - Sessão criada
- [x] `theory_session_canceled` - Sessão cancelada
- [x] `theory_attendance_marked` - Ausência marcada
- [x] `theory_course_completed` - Curso concluído

### 4. RBAC
- [x] Permissões criadas (seed 003)
- [x] Controllers validam permissões
- [x] Roles: ADMIN, SECRETARIA, INSTRUTOR

### 5. Editar Sessão
- [x] Método `editar()` e `atualizar()`
- [x] Propagação para lessons via `theory_session_id`
- [x] Transação: session + lessons

---

## 🔄 Fluxo Completo

### Criar Matrícula com Turma Teórica
```
1. Usuário seleciona turma no formulário
2. AlunosController::criarMatricula():
   - Valida turma
   - Cria enrollment (com theory_class_id)
   - Cria theory_enrollment (idempotente)
   - Cria student_steps (inclui CURSO_TEORICO)
3. Notificação: theory_class_enrolled
```

### Criar Sessão Teórica
```
1. Secretaria cria sessão
2. TheorySessionsController::criar():
   - Cria theory_sessions
   - Para cada aluno matriculado:
     - Cria lesson (type='teoria')
   - Atualiza theory_sessions.lesson_id
3. Notificação: theory_session_scheduled (para cada aluno)
```

### Marcar Presença
```
1. Instrutor marca presença
2. TheoryAttendanceController::salvar():
   - Salva presenças em lote
   - Se sessão está 'done':
     - TheoryProgressService::updateTheoryStepStatus()
     - Verifica se todas sessões têm presença válida
     - Atualiza student_steps (CURSO_TEORICO)
   - Notificação se ausente ou curso concluído
```

### Editar Sessão
```
1. Secretaria edita sessão
2. TheorySessionsController::atualizar():
   - Atualiza theory_sessions
   - UPDATE lessons WHERE theory_session_id = ?
   - Propaga horário/duração para todas lessons
```

---

## 📊 Validação de Arquitetura

### ✅ Não cria "segundo progresso"
- Progresso permanece em `student_steps`
- Step `CURSO_TEORICO` é apenas mais um step no catálogo
- Atualização segue mesmo padrão das outras etapas

### ✅ Reutiliza estrutura existente
- `steps` - catálogo existente
- `student_steps` - instâncias existentes
- `notifications` - modelo existente (type + link)
- `lessons` - agenda existente

### ✅ Transações e Idempotência
- Todas operações críticas em transação
- UNIQUE KEYs previnem duplicidade
- Verificações antes de inserir

---

## 🚀 Scripts de Execução

1. **Migration 025:** `php tools/run_migration_025.php`
2. **Migration 026:** `php tools/run_migration_026.php`
3. **Migration 027:** `php tools/run_migration_027.php`
4. **Seed 003:** `php tools/run_seed_003.php`

**Ordem recomendada:**
```bash
php tools/run_migration_025.php
php tools/run_migration_026.php
php tools/run_migration_027.php
php tools/run_seed_003.php
```
