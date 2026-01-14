# 🔍 AUDITORIA COMPLETA - SISTEMA CFC v.1

**Data da Auditoria:** 2024  
**Versão do Sistema:** v.1  
**Status Geral:** Em desenvolvimento - Fase 1 implementada

---

## 📋 SUMÁRIO EXECUTIVO

O sistema CFC está aproximadamente **65% pronto** para publicação por perfil. Os módulos principais (Alunos, Matrículas, Agenda, Instrutores, Veículos, Serviços, Financeiro) estão funcionais, mas faltam:

1. **PWA não implementado** (0% - bloqueador crítico)
2. **Telas específicas por perfil** (parcial - menu existe, mas telas são genéricas)
3. **Módulo de Relatórios** (não implementado)
4. **Módulo de Configurações** (não implementado)
5. **Validações de permissões inconsistentes** (alguns endpoints sem validação)
6. **Experiência mobile/app** (layout responsivo existe, mas não otimizado para app)

**Principais bloqueadores para publicação:**
- ❌ PWA não implementado (manifest, service worker, cache, offline)
- ❌ Telas finais por perfil não diferenciadas
- ❌ Sistema de notificações não implementado
- ⚠️ Validações de segurança em alguns endpoints

---

## 1. MAPA DO SISTEMA (VISÃO GERAL)

### 1.1 Arquitetura Técnica

**Backend:**
- **Linguagem:** PHP 8.0+
- **Padrão:** MVC (Model-View-Controller)
- **Banco de Dados:** MySQL (InnoDB)
- **Autenticação:** Session-based (PHP Sessions)
- **Estrutura:** Front Controller Pattern (`public_html/index.php`)

**Frontend:**
- **Tecnologia:** PHP Server-Side Rendering (Views PHP)
- **CSS:** Design System modular (tokens, components, layout, utilities)
- **JavaScript:** Vanilla JS (`assets/js/app.js`)
- **Responsividade:** Mobile-first

**PWA:**
- ❌ **NÃO IMPLEMENTADO**
- Sem `manifest.json`
- Sem `service-worker.js`
- Sem cache strategy
- Sem offline support

### 1.2 Estrutura de Pastas

```
cfc-v.1/
├── app/
│   ├── Config/          # Database, Constants, Env, Credentials
│   ├── Controllers/     # 11 controllers
│   ├── Core/           # Router
│   ├── Middlewares/    # AuthMiddleware, RoleMiddleware
│   ├── Models/         # 13 models
│   ├── Services/       # AuthService, PermissionService, AuditService, etc.
│   ├── Views/          # Templates PHP
│   ├── routes/         # web.php
│   └── Bootstrap.php
├── assets/
│   ├── css/            # Design System
│   └── js/             # app.js
├── database/
│   ├── migrations/     # 16 migrations
│   └── seeds/          # Dados iniciais
├── public_html/        # DocumentRoot
└── storage/            # Logs e uploads
```

### 1.3 Ambientes

**Desenvolvimento:**
- Servidor: XAMPP (Apache + MySQL)
- Path: `c:\xampp\htdocs\cfc-v.1`
- DocumentRoot: `public_html/`
- Base URL: `/cfc-v.1/public_html`

**Variáveis de Ambiente:**
- Configuração via `app/Config/Env.php`
- Database config em `app/Config/Database.php`
- Constants em `app/Config/Constants.php`

**Deploy:**
- Não documentado (provavelmente FTP/SSH para servidor PHP tradicional)

---

## 2. MÓDULOS EXISTENTES

### 2.1 Módulos Implementados

| Módulo | Status | Funcionalidades | Observações |
|--------|--------|-----------------|-------------|
| **Autenticação** | ✅ Completo | Login, Logout, Troca de papel | Session-based, sem refresh token |
| **Dashboard** | ⚠️ Parcial | Tela básica | Não diferenciada por perfil |
| **Alunos** | ✅ Completo | CRUD, Matrícula, Histórico, Etapas, Foto | Funcional, com validações |
| **Matrículas** | ✅ Completo | Criar, Editar, Etapas, DETRAN, Financeiro | Integrado com alunos |
| **Agenda** | ✅ Completo | Calendário, Agendar, Remarcar, Cancelar, Concluir | Valida conflitos, bloqueio financeiro |
| **Instrutores** | ✅ Completo | CRUD, Disponibilidade, Foto, Credencial | Valida credencial vencida |
| **Veículos** | ✅ Completo | CRUD básico | Sem histórico de uso |
| **Serviços** | ✅ Completo | CRUD, Categorias, Preços, Métodos de pagamento | JSON para métodos |
| **Financeiro** | ⚠️ Parcial | Consulta, Autocomplete, Cards | Sem geração de cobranças |
| **Relatórios** | ❌ Não existe | - | Não implementado |
| **Configurações** | ❌ Não existe | - | Não implementado |

### 2.2 Fluxos Principais por Módulo

#### **Módulo: Alunos**
1. **Criar Aluno:** Form → Validação → Criação → Histórico
2. **Editar Aluno:** Form → Validação → Atualização → Histórico
3. **Matricular:** Selecionar serviço → Definir preço → Criar matrícula → Criar etapas
4. **Visualizar:** Dados pessoais → Matrículas → Progresso → Histórico
5. **Upload Foto:** Validação → Upload → Atualização BD → Auditoria

#### **Módulo: Agenda**
1. **Agendar Aula:** Selecionar aluno → Matrícula → Instrutor → Veículo → Data/Hora → Validações → Criação
2. **Remarcar:** Editar aula → Validações → Atualização
3. **Iniciar Aula:** Validar bloqueio financeiro → Atualizar status → Histórico
4. **Concluir Aula:** Atualizar status → Histórico
5. **Cancelar Aula:** Motivo → Atualizar status → Histórico

#### **Módulo: Financeiro**
1. **Consultar:** Buscar aluno → Exibir matrículas → Totais
2. **Autocomplete:** Busca AJAX → Retorna alunos

#### **Módulo: Instrutores**
1. **Criar:** Form → Validação → Criação → Disponibilidade → Foto (opcional)
2. **Editar:** Form → Validação → Atualização → Disponibilidade → Foto (opcional)
3. **Validar Credencial:** Verifica vencimento antes de agendar

---

## 3. MATRIZ DE PERFIS E PERMISSÕES (RBAC)

### 3.1 Perfis Existentes

| Perfil | Código | Descrição | Status |
|--------|--------|-----------|--------|
| **Administrador** | `ADMIN` | Acesso total ao sistema | ✅ Ativo |
| **Secretaria** | `SECRETARIA` | Gestão de alunos, matrículas, agenda e financeiro | ✅ Ativo |
| **Instrutor** | `INSTRUTOR` | Agenda, aulas práticas e comunicação com alunos | ✅ Ativo |
| **Aluno** | `ALUNO` | Acesso ao portal do aluno | ✅ Ativo |

### 3.2 Credenciais de Teste

**Admin:**
- Email: `admin@cfc.local`
- Senha: `admin123`
- **⚠️ ALTERAR APÓS PRIMEIRO LOGIN!**

**Outros perfis:**
- Não há seeds para outros perfis
- Necessário criar manualmente no banco

### 3.3 Matriz de Permissões

#### **Tabela: `permissoes`**
Módulos e ações cadastradas:
- `alunos`: listar, criar, editar, excluir, visualizar
- `matriculas`: listar, criar, editar, excluir
- `agenda`: listar, criar, editar, excluir
- `aulas`: listar, iniciar, finalizar, cancelar
- `financeiro`: listar, criar, editar, excluir
- `instrutores`: listar, criar, editar, excluir
- `veiculos`: listar, criar, editar, excluir
- `servicos`: listar, criar, editar, excluir

#### **Tabela: `role_permissoes`**
Associação de permissões por role:

| Role | Permissões |
|------|------------|
| **ADMIN** | Todas as permissões |
| **SECRETARIA** | alunos, matriculas, agenda, financeiro, servicos |
| **INSTRUTOR** | agenda (listar), aulas (listar, iniciar, finalizar) |
| **ALUNO** | Nenhuma permissão explícita (apenas visualização própria) |

### 3.4 Validação de Permissões

**Backend:**
- ✅ `AuthMiddleware`: Valida sessão (todas as rotas protegidas)
- ✅ `RoleMiddleware`: Valida role específica (não usado nas rotas atuais)
- ⚠️ `PermissionService::check()`: Usado em alguns controllers, mas **não em todos**
- ❌ Alguns endpoints não validam permissões específicas

**Frontend:**
- ✅ Menu diferenciado por perfil (`getMenuItems()` em `shell.php`)
- ⚠️ Links/buttons não ocultados por permissão (apenas por role)
- ❌ Sem validação de permissão antes de ações

**Pontos de Risco:**
1. **Endpoints sem validação de permissão:**
   - `/api/geo/cidades` - Apenas AuthMiddleware
   - `/api/geo/cep` - Apenas AuthMiddleware
   - `/api/students/{id}/enrollments` - Apenas AuthMiddleware
   - `/api/financeiro/autocomplete` - Apenas AuthMiddleware
   - `/api/agenda/calendario` - Apenas AuthMiddleware

2. **Controllers com validação parcial:**
   - `AlunosController`: Valida permissões em alguns métodos
   - `ServicosController`: Valida permissões
   - `AgendaController`: **NÃO valida permissões específicas**
   - `FinanceiroController`: **NÃO valida permissões específicas**
   - `InstrutoresController`: **NÃO valida permissões específicas**
   - `VeiculosController`: **NÃO valida permissões específicas**

3. **Rota de debug exposta:**
   - `/debug/database` - **SEM AUTENTICAÇÃO** (comentário diz "APENAS LOCAL")

---

## 4. INVENTÁRIO DE TELAS E STATUS

### 4.1 Tabela de Telas

| Rota | Arquivo | Perfis | Funcionalidade | Status | Pendências |
|------|---------|--------|----------------|--------|------------|
| `/` | `auth/login.php` | Público | Login | ✅ OK | - |
| `/login` | `auth/login.php` | Público | Login | ✅ OK | - |
| `/dashboard` | `dashboard.php` | Todos | Dashboard genérico | ⚠️ Parcial | Não diferenciado por perfil |
| `/alunos` | `alunos/index.php` | ADMIN, SECRETARIA | Lista de alunos | ✅ OK | - |
| `/alunos/novo` | `alunos/form.php` | ADMIN, SECRETARIA | Formulário novo aluno | ✅ OK | - |
| `/alunos/{id}` | `alunos/show.php` | ADMIN, SECRETARIA | Detalhes do aluno | ✅ OK | - |
| `/alunos/{id}/editar` | `alunos/form.php` | ADMIN, SECRETARIA | Formulário editar | ✅ OK | - |
| `/alunos/{id}/matricular` | `alunos/matricular.php` | ADMIN, SECRETARIA | Formulário matrícula | ✅ OK | - |
| `/matriculas/{id}` | `alunos/matricula_show.php` | ADMIN, SECRETARIA | Detalhes matrícula | ✅ OK | - |
| `/agenda` | `agenda/index.php` | ADMIN, SECRETARIA, INSTRUTOR | Calendário agenda | ✅ OK | Filtro por instrutor (INSTRUTOR) |
| `/agenda/novo` | `agenda/form.php` | ADMIN, SECRETARIA | Formulário nova aula | ✅ OK | - |
| `/agenda/{id}` | `agenda/show.php` | ADMIN, SECRETARIA, INSTRUTOR | Detalhes aula | ✅ OK | - |
| `/agenda/{id}/editar` | `agenda/form.php` | ADMIN, SECRETARIA | Formulário remarcar | ✅ OK | - |
| `/instrutores` | `instrutores/index.php` | ADMIN, SECRETARIA | Lista instrutores | ✅ OK | - |
| `/instrutores/novo` | `instrutores/form.php` | ADMIN, SECRETARIA | Formulário novo | ✅ OK | - |
| `/instrutores/{id}/editar` | `instrutores/form.php` | ADMIN, SECRETARIA | Formulário editar | ✅ OK | - |
| `/veiculos` | `veiculos/index.php` | ADMIN, SECRETARIA | Lista veículos | ✅ OK | - |
| `/veiculos/novo` | `veiculos/form.php` | ADMIN, SECRETARIA | Formulário novo | ✅ OK | - |
| `/veiculos/{id}/editar` | `veiculos/form.php` | ADMIN, SECRETARIA | Formulário editar | ✅ OK | - |
| `/servicos` | `servicos/index.php` | ADMIN, SECRETARIA | Lista serviços | ✅ OK | - |
| `/servicos/novo` | `servicos/form.php` | ADMIN, SECRETARIA | Formulário novo | ✅ OK | - |
| `/servicos/{id}/editar` | `servicos/form.php` | ADMIN, SECRETARIA | Formulário editar | ✅ OK | - |
| `/financeiro` | `financeiro/index.php` | ADMIN, SECRETARIA | Consulta financeira | ⚠️ Parcial | Sem geração de cobranças |
| **ALUNO** | | | | | |
| `/dashboard` (ALUNO) | `dashboard.php` | ALUNO | Dashboard genérico | ❌ Falta | Tela específica "Meu Progresso" |
| `/agenda` (ALUNO) | `agenda/index.php` | ALUNO | Agenda genérica | ❌ Falta | Tela específica "Minha Agenda" |
| `/financeiro` (ALUNO) | `financeiro/index.php` | ALUNO | Financeiro genérico | ❌ Falta | Tela específica "Meus Pagamentos" |
| **INSTRUTOR** | | | | | |
| `/dashboard` (INSTRUTOR) | `dashboard.php` | INSTRUTOR | Dashboard genérico | ❌ Falta | Tela específica "Minha Agenda Hoje" |
| `/agenda` (INSTRUTOR) | `agenda/index.php` | INSTRUTOR | Agenda completa | ⚠️ Parcial | Deveria filtrar apenas aulas do instrutor |

### 4.2 Telas Faltantes

| Tela | Perfil | Prioridade | Descrição |
|------|--------|------------|-----------|
| Dashboard Aluno | ALUNO | Alta | Progresso, próximas aulas, pendências |
| Dashboard Instrutor | INSTRUTOR | Alta | Aulas do dia, próximas, estatísticas |
| Minha Agenda (Aluno) | ALUNO | Alta | Apenas aulas do próprio aluno |
| Minha Agenda (Instrutor) | INSTRUTOR | Média | Filtrada por instrutor logado |
| Meus Pagamentos (Aluno) | ALUNO | Alta | Histórico e pendências financeiras |
| Relatórios | ADMIN, SECRETARIA | Média | Vários relatórios |
| Configurações | ADMIN | Baixa | Configurações do sistema |

---

## 5. INVENTÁRIO DE APIs

### 5.1 Tabela de Endpoints

| Método | Rota | Controller | Validação | Quem Acessa | Status |
|--------|------|------------|-----------|-------------|--------|
| GET | `/` | AuthController | Nenhuma | Público | ✅ |
| GET | `/login` | AuthController | Nenhuma | Público | ✅ |
| POST | `/login` | AuthController | CSRF | Público | ✅ |
| GET | `/logout` | AuthController | Nenhuma | Autenticado | ✅ |
| GET | `/dashboard` | DashboardController | AuthMiddleware | Todos | ✅ |
| GET | `/alunos` | AlunosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/alunos/novo` | AlunosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/alunos/criar` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/alunos/{id}` | AlunosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/alunos/{id}/editar` | AlunosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/alunos/{id}/atualizar` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/alunos/{id}/matricular` | AlunosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/alunos/{id}/matricular` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/alunos/{id}/foto/upload` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/alunos/{id}/foto/remover` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/alunos/{id}/foto` | AlunosController | AuthMiddleware | Autenticado | ✅ |
| POST | `/alunos/{id}/historico/observacao` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/matriculas/{id}` | AlunosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/matriculas/{id}/atualizar` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/student-steps/{id}/toggle` | AlunosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/agenda` | AgendaController | AuthMiddleware | ADMIN, SECRETARIA, INSTRUTOR | ⚠️ Sem validação de permissão |
| GET | `/agenda/novo` | AgendaController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/agenda/criar` | AgendaController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/agenda/{id}` | AgendaController | AuthMiddleware | ADMIN, SECRETARIA, INSTRUTOR | ⚠️ Sem validação de permissão |
| GET | `/agenda/{id}/editar` | AgendaController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/agenda/{id}/atualizar` | AgendaController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/agenda/{id}/cancelar` | AgendaController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/agenda/{id}/concluir` | AgendaController | AuthMiddleware + CSRF | ADMIN, SECRETARIA, INSTRUTOR | ⚠️ Sem validação de permissão |
| POST | `/agenda/{id}/iniciar` | AgendaController | AuthMiddleware + CSRF | ADMIN, SECRETARIA, INSTRUTOR | ⚠️ Sem validação de permissão |
| GET | `/api/agenda/calendario` | AgendaController | AuthMiddleware | Autenticado | ⚠️ Sem validação de permissão |
| GET | `/instrutores` | InstrutoresController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/instrutores/novo` | InstrutoresController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/instrutores/criar` | InstrutoresController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/instrutores/{id}/editar` | InstrutoresController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/instrutores/{id}/atualizar` | InstrutoresController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/instrutores/{id}/foto/upload` | InstrutoresController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/instrutores/{id}/foto/remover` | InstrutoresController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/instrutores/{id}/foto` | InstrutoresController | AuthMiddleware | Autenticado | ✅ |
| GET | `/veiculos` | VeiculosController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/veiculos/novo` | VeiculosController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/veiculos/criar` | VeiculosController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/veiculos/{id}/editar` | VeiculosController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| POST | `/veiculos/{id}/atualizar` | VeiculosController | AuthMiddleware + CSRF | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/servicos` | ServicosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/servicos/novo` | ServicosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/servicos/criar` | ServicosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/servicos/{id}/editar` | ServicosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/servicos/{id}/atualizar` | ServicosController | AuthMiddleware + CSRF + PermissionService | ADMIN, SECRETARIA | ✅ |
| POST | `/servicos/{id}/toggle` | ServicosController | AuthMiddleware + PermissionService | ADMIN, SECRETARIA | ✅ |
| GET | `/financeiro` | FinanceiroController | AuthMiddleware | ADMIN, SECRETARIA | ⚠️ Sem validação de permissão |
| GET | `/api/financeiro/autocomplete` | FinanceiroController | AuthMiddleware | Autenticado | ⚠️ Sem validação de permissão |
| POST | `/api/switch-role` | ApiController | AuthMiddleware | Autenticado | ✅ |
| GET | `/api/geo/cidades` | ApiController | AuthMiddleware | Autenticado | ⚠️ Sem validação de permissão |
| GET | `/api/geo/cep` | ApiController | AuthMiddleware | Autenticado | ⚠️ Sem validação de permissão |
| GET | `/api/students/{id}/enrollments` | ApiController | AuthMiddleware | Autenticado | ⚠️ Sem validação de permissão |
| GET | `/debug/database` | DebugController | **NENHUMA** | Público | ❌ **RISCO CRÍTICO** |

### 5.2 Validações e Regras de Negócio

**Validações Server-Side:**
- ✅ CSRF em todas as rotas POST
- ✅ AuthMiddleware em rotas protegidas
- ⚠️ PermissionService apenas em alguns controllers
- ✅ Validações de dados (CPF, CEP, datas, etc.)
- ✅ Validação de conflitos de agenda
- ✅ Validação de bloqueio financeiro
- ✅ Validação de credencial de instrutor vencida

**Regras de Negócio Implementadas:**
- ✅ Bloqueio de agendamento se aluno com situação financeira bloqueada
- ✅ Validação de conflito de horário (instrutor e veículo)
- ✅ Validação de disponibilidade do instrutor (se configurada)
- ✅ Validação de credencial de instrutor antes de agendar
- ✅ Criação automática de etapas ao matricular
- ✅ Histórico automático de ações do aluno
- ✅ Auditoria de ações (tabela `auditoria`)

**Regras Faltantes:**
- ❌ Limite de aulas por dia/aluno
- ❌ Validação de idade mínima para matrícula
- ❌ Validação de documentos obrigatórios
- ❌ Regras de cancelamento (prazo, multa, etc.)

---

## 6. BANCO DE DADOS E REGRAS

### 6.1 Estrutura do Banco

**Tabelas Principais:**

| Tabela | Finalidade | Relações |
|--------|------------|----------|
| `cfcs` | CFCs (multi-tenant preparado) | - |
| `usuarios` | Usuários do sistema | → `cfcs` |
| `roles` | Catálogo de papéis | - |
| `usuario_roles` | Usuário pode ter múltiplos papéis | → `usuarios`, → `roles` |
| `permissoes` | Catálogo de permissões | - |
| `role_permissoes` | Permissões por papel | → `roles`, → `permissoes` |
| `auditoria` | Log de ações | → `usuarios`, → `cfcs` |
| `services` | Serviços oferecidos | → `cfcs` |
| `students` | Alunos | → `cfcs`, → `cities` (endereço), → `cities` (nascimento) |
| `enrollments` | Matrículas | → `students`, → `services`, → `usuarios` |
| `steps` | Catálogo de etapas | - |
| `student_steps` | Etapas do aluno por matrícula | → `enrollments`, → `steps`, → `usuarios` |
| `student_history` | Histórico do aluno | → `students`, → `usuarios` |
| `instructors` | Instrutores | → `cfcs`, → `usuarios` (opcional), → `cities` |
| `instructor_availability` | Disponibilidade do instrutor | → `instructors` |
| `vehicles` | Veículos | → `cfcs` |
| `lessons` | Aulas agendadas | → `students`, → `enrollments`, → `instructors`, → `vehicles`, → `usuarios` |
| `states` | Estados (IBGE) | - |
| `cities` | Cidades (IBGE) | → `states` |
| `user_recent_financial_queries` | Consultas recentes financeiro | → `usuarios`, → `students` |

### 6.2 Relações Principais

```
CFC (1) ──→ (N) Usuários
CFC (1) ──→ (N) Alunos
CFC (1) ──→ (N) Instrutores
CFC (1) ──→ (N) Veículos
CFC (1) ──→ (N) Serviços

Aluno (1) ──→ (N) Matrículas
Matrícula (1) ──→ (N) Etapas do Aluno
Matrícula (1) ──→ (1) Serviço

Aluno (1) ──→ (N) Aulas
Aula (1) ──→ (1) Instrutor
Aula (1) ──→ (1) Veículo
Aula (1) ──→ (1) Matrícula

Instrutor (1) ──→ (N) Disponibilidades
Instrutor (1) ──→ (N) Aulas

Usuário (N) ──→ (N) Roles (RBAC)
Role (N) ──→ (N) Permissões
```

### 6.3 Campos Críticos

**Status:**
- `students.status`: lead, matriculado, em_andamento, concluido, cancelado
- `enrollments.status`: ativa, concluida, cancelada
- `enrollments.financial_status`: em_dia, pendente, bloqueado
- `lessons.status`: agendada, em_andamento, concluida, cancelada, no_show
- `instructors.is_active`: 0 ou 1
- `vehicles.is_active`: 0 ou 1
- `services.is_active`: 0 ou 1

**Datas:**
- `enrollments.first_due_date`: Primeira parcela
- `enrollments.down_payment_due_date`: Entrada
- `lessons.scheduled_date`: Data agendada
- `lessons.scheduled_time`: Hora agendada
- `instructors.credential_expiry_date`: Vencimento credencial

**Chaves:**
- `students.cfc_id + students.cpf`: Único (aluno por CFC)
- `vehicles.cfc_id + vehicles.plate`: Único (veículo por CFC)
- `usuario_roles.usuario_id + usuario_roles.role`: Único

### 6.4 Migrações

**Total:** 16 migrations

| Migration | Descrição | Status |
|-----------|-----------|--------|
| 001 | Tabelas base (CFCs, Usuários, Roles, Permissões, Auditoria) | ✅ |
| 002 | Serviços, Alunos, Matrículas, Etapas | ✅ |
| 003 | Campos adicionais alunos (Fase 1.1) | ✅ |
| 004 | Estados e cidades (IBGE) | ✅ |
| 005 | city_id em students | ✅ |
| 006 | birth_city_id em students | ✅ |
| 007 | Remover colunas deprecated (city, birth_city) | ✅ |
| 008 | Campos DETRAN em enrollments | ✅ |
| 009 | Plano de pagamento em enrollments | ✅ |
| 010 | Campos de entrada em enrollments | ✅ |
| 011 | Histórico do aluno | ✅ |
| 012 | Instrutores, Veículos, Aulas | ✅ |
| 013 | Remover aulas teóricas | ✅ |
| 014 | Campos completos instrutores | ✅ |
| 015 | Campos cancelamento aulas | ✅ |
| 016 | Consultas recentes financeiro | ✅ |

**Padronização:**
- ✅ Todas usam `SET FOREIGN_KEY_CHECKS = 0/1`
- ✅ Todas usam `SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO"`
- ✅ Timestamps padronizados
- ✅ Charset utf8mb4_unicode_ci

---

## 7. PWA / APP

### 7.1 Status Real do PWA

| Item | Status | Observações |
|------|--------|-------------|
| **Manifest.json** | ❌ Não existe | Não implementado |
| **Service Worker** | ❌ Não existe | Não implementado |
| **Cache Strategy** | ❌ Não existe | Não implementado |
| **Offline Support** | ❌ Não | Não funciona offline |
| **Install Prompt** | ❌ Não | Não pode instalar como app |
| **Push Notifications** | ❌ Não | Não implementado |
| **Atualização** | ❌ Não | Sem controle de versão |
| **Fallback** | ❌ Não | Sem página offline |

### 7.2 Fluxo de Login no PWA

**Atual (Session-based):**
- ✅ Login cria sessão PHP
- ✅ Sessão persiste por 24 horas (cookie_lifetime)
- ✅ Cookie httponly, secure (se HTTPS), samesite=Strict
- ⚠️ Sem refresh token
- ⚠️ Sem persistência em localStorage/IndexedDB
- ❌ Se fechar app, precisa logar novamente (se cookie expirar)

**Necessário para PWA:**
- ❌ Persistência de sessão em IndexedDB
- ❌ Refresh token automático
- ❌ Validação de sessão no service worker
- ❌ Redirecionamento para login se expirado

### 7.3 Mobile First

**Layout Responsivo:**
- ✅ CSS mobile-first
- ✅ Sidebar colapsável no mobile
- ✅ Topbar adaptável
- ✅ Formulários responsivos

**Otimizações Faltantes:**
- ❌ Touch gestures
- ❌ Pull-to-refresh
- ❌ Swipe actions
- ❌ Otimização de imagens (lazy loading)
- ❌ Compressão de assets

### 7.4 O que Falta para "App por Perfil"

**Home Específica:**
- ❌ Dashboard diferenciado por perfil
- ❌ Cards/estatísticas específicas
- ❌ Ações rápidas por perfil

**Navegação Específica:**
- ✅ Menu diferenciado (já existe)
- ❌ Bottom navigation (mobile)
- ❌ Atalhos por perfil

**Permissões:**
- ⚠️ Validação parcial (ver seção 3.4)

**Rotas Protegidas:**
- ✅ AuthMiddleware em todas
- ⚠️ Validação de permissão inconsistente

**PWA Features:**
- ❌ Tudo (manifest, service worker, cache, offline, push)

---

## 8. LISTA DO QUE FALTA IMPLEMENTAR (BACKLOG)

### 8.1 Funcionalidades (Alta Prioridade)

| Item | Descrição | Dependências | Risco | Esforço | Impacto |
|------|-----------|--------------|-------|---------|---------|
| **PWA - Manifest** | Criar manifest.json com ícones, nome, cores | - | Baixo | P | Alto |
| **PWA - Service Worker** | Implementar SW com cache strategy | Manifest | Médio | G | Alto |
| **PWA - Offline** | Página offline, cache de assets | Service Worker | Médio | M | Alto |
| **Dashboard Aluno** | Tela específica com progresso, próximas aulas | - | Baixo | M | Alto |
| **Dashboard Instrutor** | Tela específica com aulas do dia | - | Baixo | M | Alto |
| **Minha Agenda (Aluno)** | Filtrar apenas aulas do aluno logado | - | Baixo | P | Alto |
| **Minha Agenda (Instrutor)** | Filtrar apenas aulas do instrutor | - | Baixo | P | Alto |
| **Meus Pagamentos (Aluno)** | Histórico financeiro do próprio aluno | - | Baixo | M | Alto |
| **Validação de Permissões** | Adicionar PermissionService em todos os controllers | - | Médio | M | Alto |
| **Remover Debug Route** | Remover ou proteger `/debug/database` | - | Baixo | P | Alto |

### 8.2 Funcionalidades (Média Prioridade)

| Item | Descrição | Dependências | Risco | Esforço | Impacto |
|------|-----------|--------------|-------|---------|---------|
| **Geração de Cobranças** | Integração com gateway de pagamento | Financeiro | Alto | G | Médio |
| **Relatórios** | Módulo completo de relatórios | - | Médio | G | Médio |
| **Configurações** | Módulo de configurações do sistema | - | Baixo | M | Médio |
| **Notificações** | Sistema de notificações in-app | - | Médio | M | Médio |
| **Push Notifications** | Notificações push (PWA) | Service Worker | Alto | G | Médio |
| **Filtro Agenda Instrutor** | Auto-filtrar por instrutor logado | - | Baixo | P | Médio |
| **Histórico de Veículos** | Histórico de uso de veículos | - | Baixo | M | Baixo |
| **Validações Adicionais** | Idade mínima, documentos, limites | - | Baixo | M | Médio |

### 8.3 Melhorias UX/UI (Média/Baixa Prioridade)

| Item | Descrição | Dependências | Risco | Esforço | Impacto |
|------|-----------|--------------|-------|---------|---------|
| **Bottom Navigation (Mobile)** | Navegação inferior no mobile | - | Baixo | M | Médio |
| **Touch Gestures** | Swipe, pull-to-refresh | - | Baixo | M | Baixo |
| **Lazy Loading Imagens** | Carregamento sob demanda | - | Baixo | P | Baixo |
| **Compressão Assets** | Minificar CSS/JS | - | Baixo | P | Baixo |
| **Loading States** | Estados de carregamento | - | Baixo | P | Baixo |
| **Error Boundaries** | Tratamento de erros frontend | - | Baixo | M | Médio |

### 8.4 Dívida Técnica

| Item | Descrição | Dependências | Risco | Esforço | Impacto |
|------|-----------|--------------|-------|---------|---------|
| **Testes Automatizados** | Unit tests, integration tests | - | Médio | G | Médio |
| **Documentação API** | Documentar todos os endpoints | - | Baixo | M | Baixo |
| **Logs Estruturados** | Melhorar sistema de logs | - | Baixo | M | Baixo |
| **Tratamento de Erros** | Try-catch consistente, mensagens | - | Baixo | M | Médio |
| **Validação Frontend** | Validações JavaScript antes de submit | - | Baixo | M | Baixo |
| **Otimização Queries** | Indexes, queries otimizadas | - | Médio | M | Médio |

**Legenda:**
- **Esforço:** P (Pequeno - 1-2 dias), M (Médio - 3-5 dias), G (Grande - 1+ semanas)
- **Impacto:** Alto (bloqueador), Médio (importante), Baixo (nice-to-have)

---

## 9. RISCOS E PONTOS DE ATENÇÃO

### 9.1 Segurança

**Riscos Identificados:**

1. **🔴 CRÍTICO: Rota de Debug Exposta**
   - `/debug/database` sem autenticação
   - **Ação:** Remover ou proteger com AuthMiddleware + role ADMIN

2. **🟡 MÉDIO: Endpoints sem Validação de Permissão**
   - Vários endpoints apenas com AuthMiddleware
   - Qualquer usuário autenticado pode acessar
   - **Ação:** Adicionar PermissionService em todos os controllers

3. **🟡 MÉDIO: Validação de CFC**
   - Alguns controllers validam `cfc_id`, outros não
   - Risco de acesso cross-CFC (se multi-tenant)
   - **Ação:** Padronizar validação de `cfc_id` em todos os controllers

4. **🟢 BAIXO: CSRF**
   - ✅ Implementado em todas as rotas POST
   - ✅ Token gerado e validado

5. **🟢 BAIXO: SQL Injection**
   - ✅ Uso de prepared statements (PDO)
   - ✅ Sem queries dinâmicas sem escape

6. **🟡 MÉDIO: XSS**
   - ✅ `htmlspecialchars()` em outputs
   - ⚠️ Verificar todos os pontos de output

7. **🟡 MÉDIO: Upload de Arquivos**
   - ✅ Validação de tipo MIME
   - ✅ Validação de tamanho
   - ⚠️ Verificar se arquivos são servidos com headers corretos

### 9.2 Integridade de Dados

**Riscos:**

1. **🟡 MÉDIO: Concorrência**
   - Sem locks em atualizações críticas
   - Risco de race condition em agendamentos
   - **Ação:** Implementar transações ou locks

2. **🟢 BAIXO: Duplicidade**
   - ✅ Constraints UNIQUE no banco (CPF, placa)
   - ✅ Validação antes de criar

3. **🟡 MÉDIO: Validações**
   - ✅ Validações server-side
   - ❌ Sem validações frontend (JavaScript)
   - **Ação:** Adicionar validações JavaScript

### 9.3 Performance

**Riscos:**

1. **🟡 MÉDIO: Queries N+1**
   - Possível em listagens com relacionamentos
   - **Ação:** Revisar queries, usar JOINs quando necessário

2. **🟡 MÉDIO: Carregamento de Dados**
   - Sem paginação em algumas listagens
   - **Ação:** Implementar paginação

3. **🟢 BAIXO: Cache**
   - Sem cache de queries frequentes
   - **Ação:** Implementar cache (Redis/Memcached) se necessário

4. **🟡 MÉDIO: Assets**
   - Sem minificação/compressão
   - **Ação:** Minificar CSS/JS em produção

### 9.4 Confiabilidade

**Riscos:**

1. **🟡 MÉDIO: Logs**
   - ✅ Tabela `auditoria` para ações
   - ⚠️ Sem logs de erros estruturados
   - **Ação:** Implementar sistema de logs (Monolog)

2. **🟡 MÉDIO: Tratamento de Erros**
   - ⚠️ Try-catch inconsistente
   - ⚠️ Mensagens de erro genéricas
   - **Ação:** Padronizar tratamento de erros

3. **🟡 MÉDIO: Fallback**
   - Sem página de erro customizada
   - Sem fallback para APIs externas (ViaCEP)
   - **Ação:** Implementar fallbacks

---

## 10. PLANO DE "FECHAMENTO" PARA PUBLICAR

### 10.1 Checklist Definition of Done - ADMIN

- [ ] Dashboard específico com estatísticas gerais
- [ ] Acesso a todos os módulos
- [ ] Validação de permissões em todos os endpoints
- [ ] Rota de debug removida ou protegida
- [ ] Testes manuais de todos os fluxos
- [ ] PWA funcional (se aplicável)

### 10.2 Checklist Definition of Done - SECRETARIA

- [ ] Dashboard específico com ações rápidas
- [ ] Acesso a: Alunos, Matrículas, Agenda, Financeiro, Serviços
- [ ] Sem acesso a: Configurações, Relatórios administrativos
- [ ] Validação de permissões
- [ ] Testes manuais de todos os fluxos
- [ ] PWA funcional (se aplicável)

### 10.3 Checklist Definition of Done - INSTRUTOR

- [ ] Dashboard específico com "Aulas de Hoje"
- [ ] Agenda filtrada apenas por instrutor logado
- [ ] Pode: Ver agenda, iniciar aula, concluir aula
- [ ] Não pode: Criar/editar/cancelar aulas, ver outros instrutores
- [ ] Validação de permissões
- [ ] Testes manuais de todos os fluxos
- [ ] PWA funcional (se aplicável)

### 10.4 Checklist Definition of Done - ALUNO

- [ ] Dashboard "Meu Progresso" com etapas, próximas aulas
- [ ] Agenda "Minha Agenda" apenas com aulas do aluno
- [ ] Financeiro "Meus Pagamentos" apenas do aluno
- [ ] Não pode: Ver outros alunos, criar/editar dados
- [ ] Validação de permissões
- [ ] Testes manuais de todos os fluxos
- [ ] PWA funcional (obrigatório para aluno)

### 10.5 Checklist PWA

- [ ] `manifest.json` criado com ícones, nome, cores
- [ ] `service-worker.js` implementado
- [ ] Cache strategy definida (Cache First para assets, Network First para API)
- [ ] Página offline (`offline.html`)
- [ ] Install prompt (se aplicável)
- [ ] Testes de instalação em Android/iOS
- [ ] Testes de funcionamento offline
- [ ] Atualização de versão (versionamento do SW)
- [ ] Push notifications (se aplicável)

### 10.6 Checklist de Testes

**Testes Manuais:**
- [ ] Login/logout em todos os perfis
- [ ] Troca de papel (se aplicável)
- [ ] CRUD de cada módulo por perfil
- [ ] Validações de permissão (tentar acessar sem permissão)
- [ ] Validações de dados (CPF inválido, etc.)
- [ ] Fluxos completos (matricular → agendar → concluir)
- [ ] Responsividade (mobile, tablet, desktop)
- [ ] PWA (instalar, offline, atualização)

**Smoke Tests:**
- [ ] Sistema inicia sem erros
- [ ] Login funciona
- [ ] Dashboard carrega
- [ ] Navegação funciona
- [ ] Formulários submetem
- [ ] APIs respondem

---

## 11. RESUMO EXECUTIVO

### 11.1 Status Geral

**O sistema está aproximadamente 65% pronto para publicação por perfil.**

**O que está funcionando:**
- ✅ Módulos principais (Alunos, Matrículas, Agenda, Instrutores, Veículos, Serviços)
- ✅ Autenticação e RBAC básico
- ✅ Banco de dados estruturado
- ✅ Layout responsivo
- ✅ Validações básicas

**O que bloqueia:**
- ❌ **PWA não implementado** (0%) - bloqueador crítico
- ❌ **Telas específicas por perfil** não diferenciadas (dashboard, agenda, financeiro)
- ❌ **Validações de permissão inconsistentes** (risco de segurança)
- ❌ **Rota de debug exposta** (risco crítico de segurança)

### 11.2 Próximos Passos Prioritários

1. **🔴 URGENTE:** Remover/proteger rota `/debug/database`
2. **🔴 URGENTE:** Adicionar validação de permissões em todos os controllers
3. **🔴 URGENTE:** Implementar PWA (manifest + service worker + offline)
4. **🟡 IMPORTANTE:** Criar telas específicas por perfil (dashboard, agenda, financeiro)
5. **🟡 IMPORTANTE:** Testes manuais completos
6. **🟢 DESEJÁVEL:** Melhorias UX/UI mobile

### 11.3 Estimativa para Publicação

**Com foco nas prioridades:**
- **PWA:** 1 semana (M)
- **Validações de segurança:** 2-3 dias (M)
- **Telas por perfil:** 1 semana (G)
- **Testes:** 3-5 dias (M)

**Total estimado:** 3-4 semanas de desenvolvimento focado

---

## 12. ANEXOS

### 12.1 Estrutura de Rotas Completa

Ver seção 5.1 (Inventário de APIs)

### 12.2 Estrutura de Banco Completa

Ver seção 6.1 (Banco de Dados)

### 12.3 Perfis e Permissões Detalhadas

Ver seção 3 (Matriz de Perfis e Permissões)

---

**Fim da Auditoria**

*Documento gerado em: 2024*  
*Versão do sistema auditado: v.1*
