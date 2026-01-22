# Auditoria do Banco de Dados - Antes da Limpeza

**Data:** 2026-01-22 13:04:54

## Resumo

- **KEEP:** 12 tabelas
- **DELETE:** 17 tabelas
- **REVIEW:** 3 tabelas

## Tabelas por Classificação

### KEEP

| Tabela | Registros | Observação |
|--------|-----------|------------|
| `cities` | 5570 | Estrutura: Cidades |
| `role_permissoes` | 118 | Relação roles-permissões (estrutura) |
| `permissoes` | 65 | Permissões do sistema (estrutura) |
| `states` | 27 | Estrutura: Estados |
| `steps` | 9 | Catálogo de etapas (estrutura) |
| `services` | 7 | Serviços (podem permanecer como exemplo) |
| `roles` | 4 | Papéis do sistema (estrutura) |
| `theory_course_disciplines` | 2 | Relação curso-disciplinas (configuração) |
| `theory_disciplines` | 2 | Disciplinas teóricas (exemplo) |
| `cfcs` | 1 | Configurações do CFC |
| `theory_courses` | 1 | Cursos teóricos (exemplo) |
| `smtp_settings` | 0 | Configurações SMTP |

### DELETE

| Tabela | Registros | Observação |
|--------|-----------|------------|
| `instructor_availability` | 6 | Disponibilidade de instrutores |
| `account_activation_tokens` | 4 | Tokens de ativação |
| `notifications` | 4 | Notificações/Comunicados |
| `instructors` | 1 | Instrutores |
| `theory_classes` | 1 | Aulas teóricas |
| `theory_sessions` | 1 | Sessões teóricas |
| `vehicles` | 1 | Veículos |
| `enrollments` | 0 | Matrículas |
| `lessons` | 0 | Aulas/Agendamentos |
| `password_reset_tokens` | 0 | Tokens de reset |
| `reschedule_requests` | 0 | Solicitações de remarcação |
| `student_history` | 0 | Histórico de alunos |
| `student_steps` | 0 | Etapas de alunos |
| `students` | 0 | Alunos |
| `theory_attendance` | 0 | Presenças teóricas |
| `theory_enrollments` | 0 | Matrículas |
| `user_recent_financial_queries` | 0 | Logs de consultas financeiras |

### REVIEW

| Tabela | Registros | Observação |
|--------|-----------|------------|
| `auditoria` | 98 | Logs de auditoria - verificar se deve manter ou deletar |
| `usuario_roles` | 4 | Manter apenas roles do ADMIN, deletar demais |
| `usuarios` | 4 | Manter apenas ADMIN, deletar demais |

## Dependências (Foreign Keys)

### account_activation_tokens

- `user_id` → `usuarios.id`
- `created_by` → `usuarios.id`

### instructor_availability

- `instructor_id` → `instructors.id`

### instructors

- `cfc_id` → `cfcs.id`
- `user_id` → `usuarios.id`
- `address_city_id` → `cities.id`
- `address_state_id` → `states.id`

### notifications

- `user_id` → `usuarios.id`

### theory_classes

- `cfc_id` → `cfcs.id`
- `course_id` → `theory_courses.id`
- `instructor_id` → `instructors.id`
- `created_by` → `usuarios.id`

### theory_sessions

- `class_id` → `theory_classes.id`
- `discipline_id` → `theory_disciplines.id`
- `lesson_id` → `lessons.id`
- `created_by` → `usuarios.id`

## Arquivos em storage/uploads

### cfcs/ (1 arquivos - MANTER)

- `cfc_1_1769079410.png`

### students/ (0 arquivos - DELETAR)


### instructors/ (1 arquivos - DELETAR)

- `instructor_1_1768339379.jpg`

### vehicles/ (0 arquivos - DELETAR)


## Ordem Sugerida de Exclusão

Baseado nas dependências, a ordem segura seria:

1. Tabelas filhas (que referenciam outras)
2. Tabelas pai (referenciadas)
3. Resetar AUTO_INCREMENT das tabelas limpas

