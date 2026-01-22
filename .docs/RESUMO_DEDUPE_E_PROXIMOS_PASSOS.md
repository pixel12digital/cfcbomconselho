# Resumo: Dedupe Implementado + Próximos Passos

## ✅ Dedupe de Sessões Teóricas - CONCLUÍDO

### Problema Resolvido
- ✅ Agenda do instrutor não duplica mais sessões teóricas
- ✅ Agrupamento por `theory_session_id` implementado
- ✅ Views atualizadas para mostrar "📚 Sessão Teórica (X alunos)"
- ✅ Link direto para tela de presença
- ✅ Idempotência na criação de sessões (transação + validação)

### Arquivos Modificados
1. `app/Models/Lesson.php` - Novo método `findByInstructorWithTheoryDedupe()`
2. `app/Controllers/AgendaController.php` - Usa novo método
3. `app/Controllers/DashboardController.php` - Usa novo método
4. `app/Views/agenda/index.php` - Renderização de sessões teóricas agrupadas
5. `app/Controllers/TheorySessionsController.php` - Transação + idempotência

---

## 🚧 Próximos Passos: Fase Integrações

### 1. Integração na Matrícula
**Objetivo:** Matrícula passa a apontar para template/turma, sem criar disciplinas.

**Implementar:**
- [ ] Adicionar campos opcionais em `enrollments`:
  - `theory_course_id` (template)
  - `theory_class_id` (turma)
- [ ] Atualizar formulário de matrícula (`app/Views/alunos/matricular.php`)
- [ ] No `AlunosController::criarMatricula()`:
  - Se `theory_class_id` informado → criar `theory_enrollment`
  - Não criar sessions/lessons aqui (só vincular)
- [ ] Validação: verificar se turma existe e está ativa

**Arquivos a modificar:**
- `database/migrations/026_add_theory_fields_to_enrollments.sql`
- `app/Controllers/AlunosController.php`
- `app/Views/alunos/matricular.php`

---

### 2. Integração no Progresso (student_steps/steps)
**Objetivo:** Adicionar etapa "Curso Teórico" antes de "Prova Teórica".

**Implementar:**
- [ ] Criar step novo no catálogo:
  - `code = 'CURSO_TEORICO'`
  - `order` antes de `PROVA_TEORICA`
- [ ] Na criação da matrícula, garantir `student_steps` correspondente
- [ ] Atualização automática do status do step com base em `theory_attendance`
- [ ] Condição de conclusão (MVP):
  - "Conclui quando todas as sessões do curso/turma marcadas como `done` tiverem presença `present` ou `justified`"
  - Opcionalmente: respeitar `required` em `theory_course_disciplines`

**Arquivos a modificar:**
- `database/seeds/003_seed_curso_teorico_step.sql` (ou migration)
- `app/Controllers/AlunosController.php` (criação de matrícula)
- `app/Controllers/TheoryAttendanceController.php` (atualizar step ao marcar presença)
- `app/Views/dashboard/aluno.php` (exibir progresso do curso teórico)

---

### 3. Notificações Internas
**Objetivo:** Criar eventos para principais ações do módulo teórico.

**Eventos a criar:**
- [ ] `theory_class_enrolled` - Aluno matriculado em turma
- [ ] `theory_session_scheduled` - Sessão criada
- [ ] `theory_session_canceled` - Sessão cancelada
- [ ] `theory_attendance_marked` - Presença marcada (ausente)
- [ ] `theory_course_completed` - Curso teórico concluído

**Links sugeridos:**
- `/turmas-teoricas/{classId}` - Detalhes da turma
- `/turmas-teoricas/{classId}/sessoes/{sessionId}/presenca` - Presença
- `/dashboard` - Progresso do aluno

**Arquivos a modificar:**
- `app/Controllers/TheoryEnrollmentsController.php`
- `app/Controllers/TheorySessionsController.php`
- `app/Controllers/TheoryAttendanceController.php`
- Usar `Notification::createNotification()`

---

### 4. RBAC (PermissionService::check)
**Objetivo:** Adicionar permissões para módulo teórico.

**Permissões a criar:**
- [ ] `disciplinas`: listar, criar, editar, excluir
- [ ] `cursos_teoricos`: listar, criar, editar, excluir
- [ ] `turmas_teoricas`: listar, criar, editar, excluir
- [ ] `presenca_teorica`: listar, criar, editar
- [ ] `instrutor.presenca_teorica` (se aplicável)
- [ ] `aluno.curso_teorico_view` (se aplicável)

**Arquivos a modificar:**
- `database/seeds/004_seed_theory_permissions.sql`
- Controllers já têm `PermissionService::check()` mas precisam das permissões no banco

---

## 📝 Notas Técnicas

### Idempotência (Já Implementado)
- ✅ Verificação antes de criar sessão
- ✅ Transação na criação de sessão + lessons
- ✅ Rollback em caso de erro

### Validações Necessárias
- [ ] Verificar se disciplina está ativa antes de vincular ao curso
- [ ] Verificar se curso está ativo antes de criar turma
- [ ] Validar conflitos de horário do instrutor (já existe em `Lesson`)
- [ ] Validar se aluno já está matriculado na turma (já existe UNIQUE)

### Performance
- Query de dedupe usa `GROUP BY` + `UNION` - pode precisar de índices:
  - `lessons(theory_session_id, instructor_id, cfc_id, type)`
  - `theory_sessions(class_id, starts_at)`
