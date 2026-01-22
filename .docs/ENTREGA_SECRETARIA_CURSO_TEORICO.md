# Entrega: Módulo Curso Teórico - Secretaria (MVP)

## ✅ Status: Implementação Completa

Implementação do MVP do módulo de Curso Teórico para Secretaria conforme planejamento.

---

## 📋 Rotas Criadas

### Configurações (ADMIN)
```
GET  /configuracoes/disciplinas
GET  /configuracoes/disciplinas/novo
POST /configuracoes/disciplinas/criar
GET  /configuracoes/disciplinas/{id}/editar
POST /configuracoes/disciplinas/{id}/atualizar

GET  /configuracoes/cursos
GET  /configuracoes/cursos/novo
POST /configuracoes/cursos/criar
GET  /configuracoes/cursos/{id}/editar
POST /configuracoes/cursos/{id}/atualizar
```

### Secretaria (Turmas, Sessões, Matrículas, Presença)
```
GET  /turmas-teoricas
GET  /turmas-teoricas/novo
POST /turmas-teoricas/criar
GET  /turmas-teoricas/{id}
GET  /turmas-teoricas/{id}/editar
POST /turmas-teoricas/{id}/atualizar

GET  /turmas-teoricas/{classId}/sessoes/novo
POST /turmas-teoricas/{classId}/sessoes/criar
POST /turmas-teoricas/{classId}/sessoes/{sessionId}/cancelar

GET  /turmas-teoricas/{classId}/matricular
POST /turmas-teoricas/{classId}/matriculas/criar
POST /turmas-teoricas/{classId}/matriculas/{enrollmentId}/remover

GET  /turmas-teoricas/{classId}/sessoes/{sessionId}/presenca
POST /turmas-teoricas/{classId}/sessoes/{sessionId}/presenca/salvar
```

---

## 🖥️ Telas Principais

### 1. Configurações - Disciplinas
**Arquivo:** `app/Views/configuracoes/disciplinas/index.php`
- Lista de disciplinas com ordem, carga horária padrão e status
- Botão "Nova Disciplina"
- Ações: Editar

**Arquivo:** `app/Views/configuracoes/disciplinas/form.php`
- Formulário para criar/editar disciplina
- Campos: Nome, Carga Horária Padrão (minutos), Ordem de Exibição, Status (ativo/inativo)

### 2. Configurações - Cursos
**Arquivo:** `app/Views/configuracoes/cursos/index.php`
- Lista de cursos teóricos
- Botão "Novo Curso"
- Ações: Editar

**Arquivo:** `app/Views/configuracoes/cursos/form.php`
- Formulário para criar/editar curso
- Campos: Nome, Status (ativo/inativo)
- **Vínculo de Disciplinas:** Interface dinâmica para adicionar/remover disciplinas
  - Cada disciplina: seleção, carga horária específica (opcional), obrigatória/opcional
  - Ordem configurável via `sort_order`

### 3. Secretaria - Turmas
**Arquivo:** `app/Views/theory_classes/index.php`
- Lista de turmas com curso, instrutor, data início, quantidade de alunos, status
- Filtro por status (opcional)
- Ações: Ver detalhes, Editar

**Arquivo:** `app/Views/theory_classes/form.php`
- Formulário para criar/editar turma
- Campos: Curso (select), Instrutor (select), Nome/Código (opcional), Data Início, Status

**Arquivo:** `app/Views/theory_classes/show.php`
- Detalhes da turma
- **Painel de Sessões:** Lista de sessões com disciplina, data/hora, local
  - Botão "Nova Sessão"
  - Botão "Marcar Presença" por sessão
- **Painel de Alunos:** Lista de alunos matriculados
  - Botão "Matricular"
  - Botão "Remover" por aluno

### 4. Secretaria - Sessões
**Arquivo:** `app/Views/theory_sessions/form.php`
- Formulário para criar sessão teórica
- Campos: Disciplina (select do curso), Data/Hora Início, Data/Hora Término, Local (opcional)
- **Ao criar:** Gera automaticamente `lessons` (type='teoria') para cada aluno matriculado

### 5. Secretaria - Matrículas na Turma
**Arquivo:** `app/Views/theory_enrollments/form.php`
- Formulário para matricular aluno na turma
- Campos: Aluno (select), Matrícula Principal (opcional, para rastreabilidade)
- Validação: Não permite matricular aluno já matriculado (UNIQUE `class_student`)

### 6. Secretaria - Presença (Mobile-First)
**Arquivo:** `app/Views/theory_attendance/sessao.php`
- Tela otimizada para mobile
- **Por aluno matriculado:**
  - Radio buttons: Presente, Ausente, Justificado, Reposição
  - Campo de observações (textarea)
  - Feedback visual por status (cores)
- **Submit rápido:** Um botão "Salvar Presença" salva todas as presenças em lote

---

## 🔄 Integração: theory_sessions ↔ lessons

### Estratégia Implementada

**Ao criar uma sessão teórica (`TheorySessionsController::criar()`):**

1. **Cria `theory_sessions`** (metadados):
   ```php
   $sessionData = [
       'class_id' => $classId,
       'discipline_id' => $disciplineId,
       'starts_at' => $startDateTime->format('Y-m-d H:i:s'),
       'ends_at' => $endDateTime->format('Y-m-d H:i:s'),
       'location' => $location,
       'status' => 'scheduled',
       'created_by' => $_SESSION['user_id']
   ];
   $sessionId = $sessionModel->create($sessionData);
   ```

2. **Busca alunos matriculados na turma:**
   ```php
   $enrollments = $enrollmentModel->findByClass($classId);
   ```

3. **Para cada aluno ativo, cria `lesson` (type='teoria'):**
   ```php
   $lessonData = [
       'cfc_id' => $this->cfcId,
       'student_id' => $enrollment['student_id'],
       'enrollment_id' => $enrollment['enrollment_id'] ?? 0,
       'instructor_id' => $class['instructor_id'],
       'vehicle_id' => null, // NULL para teóricas
       'type' => 'teoria', // ✅ Integração com agenda
       'status' => 'agendada',
       'scheduled_date' => $startDateTime->format('Y-m-d'),
       'scheduled_time' => $startDateTime->format('H:i:s'),
       'duration_minutes' => $durationMinutes,
       'theory_session_id' => $sessionId, // ✅ Rastreabilidade reversa
       'notes' => "Sessão teórica: {$class['course_name']}",
       'created_by' => $_SESSION['user_id']
   ];
   $lessonId = $lessonModel->create($lessonData);
   ```

4. **Atualiza `theory_sessions.lesson_id`** com o primeiro `lesson` criado (para referência):
   ```php
   $sessionModel->update($sessionId, ['lesson_id' => $firstLessonId]);
   ```

### Resultado

- ✅ **Agenda funciona nativamente:** Sessões teóricas aparecem na agenda do instrutor e do aluno
- ✅ **1 sessão = N lessons:** Cada aluno vê sua própria "aula teórica" na agenda
- ✅ **Rastreabilidade:** `lessons.theory_session_id` → `theory_sessions.id`
- ✅ **Referência reversa:** `theory_sessions.lesson_id` → primeiro `lessons.id` criado

### Schema de Relacionamento

```
theory_sessions (1)
    ↓ (1:N via theory_enrollments)
theory_enrollments (N alunos)
    ↓ (1:1)
lessons (N, type='teoria')
    ↓ (N:1)
students
```

**Campos de integração:**
- `theory_sessions.lesson_id` → `lessons.id` (FK opcional, referência)
- `lessons.theory_session_id` → `theory_sessions.id` (FK opcional, rastreabilidade)

---

## 📊 Controllers Criados

1. **ConfiguracoesController** (extendido)
   - `disciplinas()`, `disciplinaNovo()`, `disciplinaCriar()`, `disciplinaEditar()`, `disciplinaAtualizar()`
   - `cursos()`, `cursoNovo()`, `cursoCriar()`, `cursoEditar()`, `cursoAtualizar()`

2. **TheoryClassesController**
   - `index()`, `novo()`, `criar()`, `show()`, `editar()`, `atualizar()`

3. **TheorySessionsController**
   - `novo()`, `criar()` - **Cria lessons automaticamente**
   - `cancelar()` - Cancela sessão e lessons relacionadas

4. **TheoryEnrollmentsController**
   - `novo()`, `criar()`, `remover()`

5. **TheoryAttendanceController**
   - `sessao()` - Tela de presença (mobile-first)
   - `salvar()` - Salva presença em lote

---

## ✅ Checklist MVP Secretaria

- [x] **Turmas:** CRUD + escolher curso + instrutor
- [x] **Sessões:** Criar (manual) + gerar lessons automaticamente
- [x] **Matrículas na turma:** Adicionar/remover alunos
- [x] **Presença:** Tela por sessão (mobile-first) com submit rápido
- [x] **Integração:** theory_sessions cria lessons (type='teoria') para agenda funcionar nativa

---

## 🚧 Próximas Etapas (Fase Integrações)

1. **Integração na Matrícula:** Opção de vincular curso teórico ao criar matrícula
2. **Integração no Progresso:** Módulo "Curso Teórico" no painel do aluno
3. **Notificações:** Eventos de teoria (matrícula, sessão, presença, conclusão)
4. **RBAC:** Adicionar permissões para módulo teórico
5. **Etapa no Steps:** Adicionar "Curso Teórico" no catálogo de etapas

---

## 📝 Notas Técnicas

### Validações Implementadas
- ✅ Curso deve estar ativo para criar turma
- ✅ Instrutor deve estar ativo para criar turma
- ✅ Aluno não pode ser matriculado duas vezes na mesma turma (UNIQUE)
- ✅ Data/hora de término deve ser posterior à de início
- ✅ Presença não pode ser duplicada (UNIQUE `session_student`)

### Auditoria
- ✅ Todas as ações criam registros em `auditoria`
- ✅ Logs de criação, atualização e remoção

### Mobile-First
- ✅ Tela de presença otimizada para mobile
- ✅ Radio buttons grandes e fáceis de tocar
- ✅ Feedback visual imediato ao selecionar status
- ✅ Submit único para salvar todas as presenças
