# Fase 0 - Respostas Obrigatórias: Arquitetura Atual

## 1. Matrícula hoje cria o quê?

**Tabelas/Entidades criadas/atualizadas:**
- `enrollments`: Registro principal da matrícula
  - Campos: `student_id`, `service_id`, `base_price`, `discount_value`, `extra_value`, `final_price`, `payment_method`, `financial_status`, `status`
  - Relaciona: `students` → `services` → `cfcs`
  
- `student_steps`: Cria automaticamente todas as etapas do catálogo (`steps`) vinculadas à matrícula
  - Status inicial: `MATRICULA` = `concluida`, demais = `pendente`
  - Campos: `enrollment_id`, `step_id`, `status`, `source`, `validated_by_user_id`, `validated_at`

**Fluxo:**
1. Cria registro em `enrollments`
2. Busca todas as etapas ativas de `steps`
3. Cria registros em `student_steps` para cada etapa
4. Registra no histórico do aluno (`student_history`)
5. Registra auditoria (`auditoria`)

**Conclusão:** A matrícula cria `enrollment` + `student_steps` (instâncias de etapas). Não cria "curso" separado; o curso é representado pelo `service_id`.

---

## 2. Progresso do aluno: onde vive?

**Tabela principal:** `student_steps`
- Estrutura: `enrollment_id` + `step_id` + `status` (`pendente`/`concluida`)
- Campos: `source` (`cfc`/`aluno`), `validated_by_user_id`, `validated_at`, `notes`
- Relaciona: `enrollments` → `steps` (catálogo de etapas)

**Catálogo de etapas:** `steps`
- Campos: `code` (ex: `MATRICULA`, `PROVA_TEORICA`), `name`, `description`, `order`, `is_active`
- **IMPORTANTE:** Etapas são globais (não por CFC), mas podem ser ativadas/desativadas

**Renderização no painel do aluno:**
- View: `app/Views/dashboard/aluno.php`
- Controller: `DashboardController::dashboardAluno()`
- Busca: `student_steps` JOIN `steps` ordenado por `steps.order`
- Exibe: Timeline visual com status concluída/pendente

**Conclusão:** Progresso vive em `student_steps` (instância por matrícula) + `steps` (catálogo). **Não existe tabela separada de "progresso"**; é baseado em etapas.

---

## 3. Agenda: qual modelo atual?

**Tabela:** `lessons`
- Campos principais:
  - `student_id`, `enrollment_id`, `instructor_id`, `vehicle_id`
  - `type`: **ENUM('pratica')** - **ATENÇÃO: só aceita 'pratica' hoje!**
  - `status`: `agendada`, `em_andamento`, `concluida`, `cancelada`, `no_show`
  - `scheduled_date`, `scheduled_time`, `duration_minutes`
  - `started_at`, `completed_at`, `notes`
  - `created_by` (usuário que criou)

**Model:** `app/Models/Lesson.php`
- Métodos: `findByPeriod()`, `findByStudent()`, `hasInstructorConflict()`, `hasVehicleConflict()`

**Controller:** `app/Controllers/AgendaController.php`
- Views: `agenda/index.php` (calendário semanal/diário/lista)
- API: `apiCalendario()` para AJAX

**Conclusão:** Agenda usa `lessons` com `type='pratica'`. **Para integrar teoria, precisamos:**
- **Opção A:** Adicionar `'teoria'` ao ENUM `type` em `lessons` (mais simples, reutiliza estrutura)
- **Opção B:** Criar `theory_sessions` separada e fazer view unificada (mais complexo, mas separa responsabilidades)

**Recomendação:** **Opção A** (adicionar `'teoria'` ao ENUM) porque:
- Reutiliza toda a estrutura de agenda existente
- Mantém consistência de queries
- Evita duplicação de código
- `vehicle_id` pode ser NULL para aulas teóricas

---

## 4. RBAC: como restringe módulos?

**Sistema:**
- **Tabelas:** `roles`, `permissoes`, `role_permissoes`, `usuario_roles`
- **Service:** `app/Services/PermissionService.php`
  - Método: `PermissionService::check($module, $action)`
  - ADMIN tem todas as permissões automaticamente
  - Outros roles: consulta `role_permissoes` JOIN `permissoes`

**Middleware:**
- `AuthMiddleware`: Valida sessão (todas as rotas protegidas)
- `RoleMiddleware`: Valida role específica (não usado amplamente)

**Permissões cadastradas:**
- `alunos`: listar, criar, editar, excluir, visualizar
- `matriculas`: listar, criar, editar, excluir
- `agenda`: listar, criar, editar, excluir
- `aulas`: listar, iniciar, finalizar, cancelar
- `financeiro`: listar, criar, editar, excluir
- `instrutores`: listar, criar, editar, excluir
- `veiculos`: listar, criar, editar, excluir
- `servicos`: listar, criar, editar, excluir

**Uso nos Controllers:**
- Alguns controllers usam `PermissionService::check()` (ex: `AlunosController`, `AgendaController`)
- **Não é usado em todos os endpoints** (risco de segurança)

**Conclusão:** RBAC via `PermissionService::check($module, $action)`. Para curso teórico, precisaremos adicionar permissões:
- `cursos_teoricos`: listar, criar, editar, excluir
- `turmas_teoricas`: listar, criar, editar, excluir
- `disciplinas`: listar, criar, editar, excluir
- `presenca_teorica`: listar, criar, editar

---

## 5. Instrutor: agenda em lista hoje puxa de qual query/tabela?

**Query atual (DashboardController::dashboardInstrutor):**
```sql
SELECT l.*, s.name as student_name, v.plate as vehicle_plate
FROM lessons l
INNER JOIN students s ON l.student_id = s.id
LEFT JOIN vehicles v ON l.vehicle_id = v.id
WHERE l.instructor_id = ?
  AND l.cfc_id = ?
  AND l.status = 'agendada'
  AND (l.scheduled_date > ? OR (l.scheduled_date = ? AND l.scheduled_time >= ?))
ORDER BY l.scheduled_date ASC, l.scheduled_time ASC
```

**AgendaController::index() para instrutor:**
- Mesma tabela `lessons`
- Filtra por `instructor_id` e `cfc_id`
- Abas: "Próximas", "Histórico", "Todas"

**Conclusão:** Agenda do instrutor puxa de `lessons` filtrado por `instructor_id`. **Se integrarmos teoria em `lessons` com `type='teoria'`, funcionará automaticamente.**

---

## 6. Notificações: modelo já tem link e type?

**Tabela:** `notifications`
- Campos:
  - `user_id` (destinatário)
  - `type`: VARCHAR(50) - **JÁ EXISTE!** (ex: `lesson_scheduled`, `lesson_rescheduled`, `lesson_canceled`, `step_updated`, `financial_pending`)
  - `title`, `body`
  - `link`: VARCHAR(255) - **JÁ EXISTE!** (ex: `/agenda/123`, `/financeiro`)
  - `is_read`, `read_at`
  - `created_at`

**Model:** `app/Models/Notification.php`
- Método: `createNotification($userId, $type, $title, $body = null, $link = null)`

**Conclusão:** **SIM!** Notificações já têm `type` e `link`. Podemos usar tipos como:
- `theory_class_enrolled` (aluno matriculado em turma)
- `theory_session_scheduled` (sessão criada)
- `theory_session_canceled` (sessão cancelada)
- `theory_attendance_marked` (presença marcada)
- `theory_course_completed` (curso teórico concluído)

---

## 📋 DECISÕES DE ARQUITETURA

### 1. Integração na Agenda
**DECISÃO:** Adicionar `'teoria'` ao ENUM `type` em `lessons`
- ✅ Reutiliza toda estrutura existente
- ✅ `vehicle_id` pode ser NULL para teóricas
- ✅ Queries de agenda funcionam automaticamente
- ✅ Instrutor vê teóricas e práticas na mesma agenda

**Migration necessária:**
```sql
ALTER TABLE lessons MODIFY COLUMN type ENUM('pratica','teoria') NOT NULL DEFAULT 'pratica';
```

### 2. Integração no Progresso
**DECISÃO:** Adicionar etapa "Curso Teórico" no catálogo `steps`
- Criar step com `code='CURSO_TEORICO'` e `order` apropriado (antes de `PROVA_TEORICA`)
- Conclusão automática quando todas as disciplinas do curso forem concluídas

### 3. Estrutura de Dados
**DECISÃO:** Criar tabelas separadas para configuração e operação:
- **Configuração:** `theory_disciplines`, `theory_courses`, `theory_course_disciplines`
- **Operação:** `theory_classes`, `theory_sessions`, `theory_enrollments`, `theory_attendance`
- **Integração:** `theory_sessions` cria registros em `lessons` com `type='teoria'` (ou usar `lessons` diretamente com campos adicionais)

**DECISÃO FINAL:** Usar `lessons` diretamente para sessões teóricas, mas criar tabelas auxiliares:
- `theory_classes`: Turma teórica (vincula a `course_id`)
- `theory_sessions`: Sessão teórica (pode ser apenas metadados ou criar registro em `lessons`)
- `theory_enrollments`: Matrícula do aluno na turma
- `theory_attendance`: Presença por sessão

**Estratégia híbrida:**
- `theory_sessions` armazena metadados (disciplina, turma)
- Ao criar sessão, também cria registro em `lessons` com `type='teoria'`
- `lessons.id` é referenciado em `theory_attendance.session_id` (ou criar campo `theory_session_id` em `lessons`)

---

## ✅ PRÓXIMOS PASSOS

1. Criar migrations para tabelas de teoria
2. Adicionar `'teoria'` ao ENUM `type` em `lessons`
3. Criar Models para teoria
4. Criar Controllers e Views
5. Integrar na matrícula
6. Integrar no progresso
7. Adicionar permissões RBAC
8. Integrar notificações
