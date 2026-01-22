# Implementação do Módulo Curso Teórico - Progresso

## ✅ Fase 0 - Concluída

Respostas documentadas em `.docs/FASE0_RESPOSTAS_CURSO_TEORICO.md`

**Decisões arquiteturais:**
- ✅ Integração na agenda: Adicionar `'teoria'` ao ENUM `type` em `lessons`
- ✅ Integração no progresso: Adicionar etapa "Curso Teórico" no catálogo `steps`
- ✅ Estrutura: Tabelas separadas para configuração e operação, integração via `lessons`

---

## ✅ Fase 1 - Concluída

### 1.1 Migration 025
**Arquivo:** `database/migrations/025_create_theory_course_tables.sql`

**Tabelas criadas:**
- ✅ `theory_disciplines` - Disciplinas configuráveis por CFC
- ✅ `theory_courses` - Cursos (templates curriculares)
- ✅ `theory_course_disciplines` - Relação curso-disciplinas
- ✅ `theory_classes` - Turmas teóricas
- ✅ `theory_sessions` - Sessões (encontros/aulas)
- ✅ `theory_enrollments` - Matrículas na turma
- ✅ `theory_attendance` - Presença por sessão

**Alterações em `lessons`:**
- ✅ Adicionado `'teoria'` ao ENUM `type`
- ✅ `vehicle_id` agora é opcional (NULL para teóricas)
- ✅ Adicionado campo `theory_session_id` para rastreabilidade

**Script de execução:** `tools/run_migration_025.php`

### 1.2 Models Criados
- ✅ `TheoryDiscipline.php` - CRUD de disciplinas
- ✅ `TheoryCourse.php` - CRUD de cursos
- ✅ `TheoryCourseDiscipline.php` - Relação curso-disciplinas
- ✅ `TheoryClass.php` - CRUD de turmas
- ✅ `TheorySession.php` - CRUD de sessões
- ✅ `TheoryEnrollment.php` - Matrículas na turma
- ✅ `TheoryAttendance.php` - Presença

### 1.3 Controllers - Configurações
**Arquivo:** `app/Controllers/ConfiguracoesController.php`

**Métodos adicionados:**
- ✅ `disciplinas()` - Lista disciplinas
- ✅ `disciplinaNovo()` - Formulário nova disciplina
- ✅ `disciplinaCriar()` - Criar disciplina
- ✅ `disciplinaEditar($id)` - Formulário editar
- ✅ `disciplinaAtualizar($id)` - Atualizar disciplina
- ✅ `cursos()` - Lista cursos
- ✅ `cursoNovo()` - Formulário novo curso
- ✅ `cursoCriar()` - Criar curso com disciplinas
- ✅ `cursoEditar($id)` - Formulário editar
- ✅ `cursoAtualizar($id)` - Atualizar curso

---

## 🚧 Próximas Etapas

### Fase 2 - Views de Configurações
- [ ] `app/Views/configuracoes/disciplinas/index.php` - Lista de disciplinas
- [ ] `app/Views/configuracoes/disciplinas/form.php` - Formulário disciplina
- [ ] `app/Views/configuracoes/cursos/index.php` - Lista de cursos
- [ ] `app/Views/configuracoes/cursos/form.php` - Formulário curso (com vínculo de disciplinas)

### Fase 3 - Controllers de Secretaria
- [ ] `TheoryClassesController.php` - CRUD de turmas
- [ ] `TheorySessionsController.php` - CRUD de sessões
- [ ] `TheoryEnrollmentsController.php` - Matrículas na turma
- [ ] `TheoryAttendanceController.php` - Marcar presença

### Fase 4 - Integrações
- [ ] Integrar na Agenda (instrutor vê sessões teóricas)
- [ ] Integrar na Matrícula (opção de vincular curso teórico)
- [ ] Integrar no Progresso do Aluno (módulo Curso Teórico)
- [ ] Adicionar permissões RBAC
- [ ] Integrar notificações

### Fase 5 - Rotas
- [ ] Adicionar rotas em `app/routes/web.php`

---

## 📝 Notas de Implementação

### Estrutura de Dados
- **Configuração:** Disciplinas e Cursos são por CFC (`cfc_id`)
- **Operação:** Turmas e Sessões são instâncias operacionais
- **Integração:** Sessões criam registros em `lessons` com `type='teoria'`

### Validações Necessárias
- Verificar se disciplina está ativa antes de vincular ao curso
- Verificar se curso está ativo antes de criar turma
- Validar conflitos de horário do instrutor (já existe em `Lesson`)
- Validar se aluno já está matriculado na turma

### Permissões RBAC
Adicionar em `permissoes`:
- `disciplinas`: listar, criar, editar, excluir
- `cursos_teoricos`: listar, criar, editar, excluir
- `turmas_teoricas`: listar, criar, editar, excluir
- `presenca_teorica`: listar, criar, editar

### Notificações
Tipos a adicionar:
- `theory_class_enrolled` - Aluno matriculado em turma
- `theory_session_scheduled` - Sessão criada
- `theory_session_canceled` - Sessão cancelada
- `theory_attendance_marked` - Presença marcada
- `theory_course_completed` - Curso teórico concluído
