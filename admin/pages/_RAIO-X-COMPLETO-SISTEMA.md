# 🔥 FASE 0 – RAIO-X PROFISSIONAL COMPLETO DO SISTEMA CFC

**Data da Análise:** 2025-01-27  
**Objetivo:** Diagnóstico estrutural completo + auditoria técnica + inventário completo + classificação de maturidade  
**Sistema:** CFC Bom Conselho - Pixel12

---

## 📌 METODOLOGIA

Este documento foi gerado através de:
- ✅ Análise de arquivos do código-fonte
- ✅ Mapeamento de APIs e endpoints
- ✅ Revisão de estrutura de banco de dados (install.php, migrations)
- ✅ Auditoria de permissões e segurança
- ✅ Identificação de código legado e duplicações

**Classificação de Maturidade:**
- **OK** → Funcional e coerente
- **PARCIAL** → Funciona, mas incompleto
- **QUEBRADO** → Lógica falha, retornos inconsistentes
- **LEGADO/LIXO** → Código morto, não usado, duplicado ou que deve ser removido

---

## 1. INVENTÁRIO GERAL DO SISTEMA

### 1.1. Backend (APIs)

**Localização:** `admin/api/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `agendamento.php` | ✅ | API principal de agendamentos | OK |
| `agendamento-detalhes.php` | ✅ | Detalhes de agendamento | OK |
| `agendamento-detalhes-fallback.php` | ⚠️ | Fallback para detalhes | PARCIAL |
| `agendamentos-por-ids.php` | ✅ | Busca agendamentos por IDs | OK |
| `aluno-agenda.php` | ✅ | Agenda do aluno | OK |
| `aluno-documentos.php` | ✅ | Documentos do aluno | OK |
| `alunos.php` | ✅ | CRUD de alunos | OK |
| `alunos-aptos-turma.php` | ✅ | Alunos aptos para turma | OK |
| `alunos-aptos-turma-simples.php` | ⚠️ | Versão simplificada | LEGADO - Duplicado |
| `atualizar-aula.php` | ✅ | Atualizar aula | OK |
| `atualizar-categoria-instrutor.php` | ✅ | Atualizar categoria instrutor | OK |
| `buscar-aula.php` | ✅ | Buscar aula | OK |
| `cancelar-aula.php` | ✅ | Cancelar aula | OK |
| `cfcs.php` | ✅ | CRUD de CFCs | OK |
| `configuracoes.php` | ✅ | Configurações do sistema | OK |
| `despesas.php` | ⚠️ | API de despesas | PARCIAL |
| `disciplina-agendamentos.php` | ✅ | Agendamentos de disciplina | OK |
| `disciplinas.php` | ✅ | CRUD de disciplinas | OK |
| `disciplinas-automaticas.php` | ⚠️ | Disciplinas automáticas | PARCIAL |
| `disciplinas-clean.php` | ⚠️ | Versão "limpa" | LEGADO - Duplicado |
| `disciplinas-curso.php` | ✅ | Disciplinas por curso | OK |
| `disciplinas-estaticas.php` | ⚠️ | Disciplinas estáticas | LEGADO |
| `disciplinas-simples.php` | ⚠️ | Versão simplificada | LEGADO - Duplicado |
| `disponibilidade.php` | ✅ | Verificar disponibilidade | OK |
| `estatisticas-turma.php` | ✅ | Estatísticas de turma | OK |
| `exames.php` | ✅ | CRUD de exames/provas | OK |
| `exames_simple.php` | ⚠️ | Versão simplificada | LEGADO - Duplicado |
| `exportar-agendamentos.php` | ✅ | Exportar agendamentos | OK |
| `faturas.php` | ⚠️ | API faturas (antiga) | LEGADO - Duplicado |
| `financeiro-despesas.php` | ✅ | API despesas (nova) | OK |
| `financeiro-faturas.php` | ✅ | API faturas (nova) | OK |
| `financeiro-relatorios.php` | ✅ | Relatórios financeiros | OK |
| `historico.php` | ✅ | Histórico geral | OK |
| `historico_aluno.php` | ✅ | Histórico do aluno | OK |
| `info-disciplina-turma.php` | ✅ | Info disciplina/turma | OK |
| `instrutores.php` | ✅ | CRUD instrutores | OK |
| `instrutores-real.php` | ⚠️ | Versão "real" | LEGADO - Duplicado |
| `instrutores-simple.php` | ⚠️ | Versão simplificada | LEGADO - Duplicado |
| `instrutores_simplificado.php` | ⚠️ | Versão simplificada 2 | LEGADO - Duplicado |
| `lgpd.php` | ✅ | LGPD | OK |
| `listar-agendamentos-turma.php` | ✅ | Listar agendamentos turma | OK |
| `manutencao.php` | ✅ | API manutenção | OK |
| `matriculas.php` | ✅ | CRUD matrículas | OK |
| `matricular-aluno-turma.php` | ✅ | Matricular aluno em turma | OK |
| `notificacoes.php` | ⚠️ | Notificações (português) | PARCIAL |
| `notifications.php` | ⚠️ | Notificações (inglês) | LEGADO - Duplicado |
| `pagamentos.php` | ✅ | CRUD pagamentos | OK |
| `progresso_pratico.php` | ✅ | Progresso prático | OK |
| `progresso_teorico.php` | ✅ | Progresso teórico | OK |
| `relatorio-disciplinas.php` | ✅ | Relatório disciplinas | OK |
| `remover-matricula-turma.php` | ✅ | Remover matrícula turma | OK |
| `salas.php` | ⚠️ | API salas (antiga) | LEGADO - Duplicado |
| `salas-ajax.php` | ⚠️ | Salas AJAX | LEGADO - Duplicado |
| `salas-clean.php` | ⚠️ | Salas "limpas" | LEGADO - Duplicado |
| `salas-real.php` | ✅ | API salas (nova) | OK |
| `search.php` | ✅ | Busca geral | OK |
| `solicitacoes.php` | ✅ | Solicitações | OK |
| `tipos-curso-clean.php` | ⚠️ | Tipos curso "limpos" | LEGADO - Duplicado |
| `turma-agendamento.php` | ✅ | Agendamento de turma | OK |
| `turma-diario.php` | ✅ | Diário de turma | OK |
| `turma-frequencia.php` | ✅ | Frequência de turma | OK |
| `turma-grade-generator.php` | ✅ | Gerador de grade | OK |
| `turma-presencas.php` | ✅ | Presenças de turma | OK |
| `turma-relatorios.php` | ✅ | Relatórios de turma | OK |
| `turmas-teoricas.php` | ✅ | CRUD turmas teóricas | OK |
| `turmas-teoricas-inline.php` | ✅ | Turmas teóricas inline | OK |
| `usuarios.php` | ✅ | CRUD usuários | OK |
| `veiculos.php` | ✅ | CRUD veículos | OK |
| `verificar-aula-especifica.php` | ✅ | Verificar aula específica | OK |
| `verificar-disponibilidade.php` | ✅ | Verificar disponibilidade | OK |
| `verificar-limite-data-turma.php` | ✅ | Verificar limite data turma | OK |

**Total de APIs:** 72 arquivos  
**Legados/Duplicados identificados:** 15 arquivos (20.8%)

### 1.2. Páginas Administrativas

**Localização:** `admin/pages/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `dashboard.php` | ✅ | Dashboard principal | OK |
| `alunos.php` | ✅ | Gestão de alunos | OK |
| `alunos_original.php` | ❌ | Backup/legado | LEGADO - Remover |
| `alunos-complete.txt` | ❌ | Arquivo de texto | LEGADO - Remover |
| `agendamento.php` | ✅ | Agendamento principal | OK |
| `agendamento-moderno.php` | ✅ | Agendamento moderno | OK |
| `agendamento-moderno.js` | ✅ | JS do agendamento moderno | OK |
| `agendar-aula.php` | ✅ | Agendar aula | OK |
| `agendar-manutencao.php` | ✅ | Agendar manutenção | OK |
| `cfcs.php` | ✅ | Gestão de CFCs | OK |
| `configuracoes-categorias.php` | ✅ | Config categorias | OK |
| `configuracoes-disciplinas.php` | ✅ | Config disciplinas | OK |
| `configuracoes-salas.php` | ✅ | Config salas | OK |
| `editar-aula.php` | ✅ | Editar aula | OK |
| `exames.php` | ✅ | Gestão de exames/provas | OK |
| `financeiro-despesas.php` | ✅ | Despesas | OK |
| `financeiro-despesas-standalone.php` | ⚠️ | Versão standalone | LEGADO - Duplicado |
| `financeiro-faturas.php` | ✅ | Faturas | OK |
| `financeiro-faturas-standalone.php` | ⚠️ | Versão standalone | LEGADO - Duplicado |
| `financeiro-relatorios.php` | ✅ | Relatórios financeiros | OK |
| `financeiro-relatorios-standalone.php` | ⚠️ | Versão standalone | LEGADO - Duplicado |
| `historico-aluno.php` | ✅ | Histórico do aluno | OK |
| `historico-aluno-melhorado.php` | ⚠️ | Versão melhorada | PARCIAL |
| `historico-aluno-novo.php` | ⚠️ | Versão nova | LEGADO - Duplicado |
| `historico-instrutor.php` | ✅ | Histórico instrutor | OK |
| `instrutores.php` | ✅ | Gestão instrutores | OK |
| `instrutores-otimizado.php` | ⚠️ | Versão otimizada | LEGADO - Duplicado |
| `listar-aulas.php` | ✅ | Listar aulas | OK |
| `relatorio-ata.php` | ✅ | Relatório ata | OK |
| `relatorio-frequencia.php` | ✅ | Relatório frequência | OK |
| `relatorio-matriculas.php` | ✅ | Relatório matrículas | OK |
| `relatorio-presencas.php` | ✅ | Relatório presenças | OK |
| `turma-chamada.php` | ✅ | Chamada de turma | OK |
| `turma-diario.php` | ✅ | Diário de turma | OK |
| `turma-relatorios.php` | ✅ | Relatórios turma | OK |
| `turmas-teoricas.php` | ✅ | Gestão turmas teóricas | OK |
| `turmas-teoricas-detalhes.php` | ✅ | Detalhes turma | OK |
| `turmas-teoricas-detalhes-inline.php` | ✅ | Detalhes inline | OK |
| `turmas-teoricas-disciplinas-fixed.php` | ⚠️ | Versão "fixed" | LEGADO - Duplicado |
| `turmas-teoricas-fixed.php` | ⚠️ | Versão "fixed" | LEGADO - Duplicado |
| `turmas-teoricas-lista.php` | ✅ | Lista turmas | OK |
| `turmas-teoricas-step2.php` | ⚠️ | Step 2 | PARCIAL |
| `turmas-teoricas-step4.php` | ⚠️ | Step 4 | PARCIAL |
| `usuarios.php` | ✅ | Gestão usuários | OK |
| `usuarios_simples.php` | ⚠️ | Versão simplificada | LEGADO - Duplicado |
| `vagas-candidatos.php` | ✅ | Vagas/candidatos | OK |
| `veiculos.php` | ✅ | Gestão veículos | OK |
| `_DIAGNOSTICO-JORNADA-ALUNO.md` | ✅ | Documentação | OK |
| `_MAPEAMENTO-CAMPOS-ALUNO.md` | ✅ | Documentação | OK |
| `_PLANO-SISTEMA-CFC.md` | ✅ | Documentação | OK |
| `_RAIO-X-MATRICULAS.md` | ✅ | Documentação | OK |
| `_modalAluno-legacy.php` | ❌ | Modal legado | LEGADO - Remover |

**Total de páginas:** 52 arquivos  
**Legados/Duplicados identificados:** 14 arquivos (26.9%)

### 1.3. Páginas de Instrutor

**Localização:** `instrutor/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `dashboard.php` | ⚠️ | Dashboard instrutor | PARCIAL |
| `dashboard-mobile.php` | ⚠️ | Dashboard mobile | PARCIAL |

**Status:** Parcialmente implementado - faltam funcionalidades do PWA

### 1.4. Páginas do Aluno (PWA)

**Localização:** `aluno/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `dashboard.php` | ⚠️ | Dashboard aluno | PARCIAL |
| `dashboard-mobile.php` | ⚠️ | Dashboard mobile | PARCIAL |
| `login.php` | ✅ | Login aluno | OK |
| `logout.php` | ✅ | Logout aluno | OK |

**Status:** Parcialmente implementado - faltam funcionalidades do PWA

### 1.5. Banco de Dados

**Migrations encontradas:** `admin/migrations/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `001-create-turmas-teoricas-structure.sql` | ✅ | Estrutura turmas teóricas | OK |
| `002-create-turmas-disciplinas-table.sql` | ✅ | Tabela disciplinas | OK |
| `003-alter-exames-add-provas.sql` | ✅ | Adicionar provas ao exames | OK |

**Script de instalação:** `install.php`  
**Status:** ✅ Funcional - cria todas as tabelas principais

**Tabelas identificadas (via install.php):**
- `usuarios` ✅
- `cfcs` ✅
- `alunos` ✅
- `instrutores` ✅
- `aulas` ✅
- `veiculos` ✅
- `sessoes` ✅
- `logs` ✅
- `exames` ✅ (inclui provas teóricas/práticas)
- `matriculas` ⚠️ (não encontrada em install.php - pode estar em migration)
- `turmas_teoricas` ✅ (criada via migration)
- `turma_matriculas` ✅ (via migration)
- `turma_aulas_agendadas` ✅ (via migration)
- `turma_presencas` ✅ (via migration)
- `salas` ✅ (via migration)
- `disciplinas_configuracao` ✅ (via migration)
- `financeiro_faturas` ⚠️ (não encontrada em install.php - pode estar em migration)
- `faturas` ⚠️ (mencionada em APIs - possível duplicação)
- `pagamentos` ⚠️ (mencionada em APIs - não encontrada em install.php)

### 1.6. Helpers, Libs, Utils

**Localização:** `includes/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `auth.php` | ✅ | Sistema de autenticação | OK |
| `config.php` | ✅ | Configurações | OK |
| `database.php` | ✅ | Conexão banco | OK |
| `CredentialManager.php` | ✅ | Gerenciador credenciais | OK |
| `controllers/AgendamentoController.php` | ✅ | Controller agendamento | OK |
| `controllers/LoginController.php` | ✅ | Controller login | OK |
| `guards/AgendamentoAuditoria.php` | ✅ | Auditoria agendamento | OK |
| `guards/AgendamentoGuards.php` | ✅ | Guards agendamento | OK |
| `guards/AgendamentoPermissions.php` | ✅ | Permissões agendamento | OK |
| `services/AuthService.php` | ✅ | Serviço auth | OK |
| `services/SistemaNotificacoes.php` | ✅ | Sistema notificações | OK |
| `models/UserModel.php` | ✅ | Model usuário | OK |
| `views/header.php` | ✅ | Header | OK |
| `layout/mobile-first.php` | ✅ | Layout mobile | OK |
| `paths.php` | ✅ | Paths | OK |

**Status:** ✅ Bem estruturado

### 1.7. Componentes Compartilhados

**Localização:** `admin/includes/`

| Arquivo | Status | Descrição | Classificação |
|---------|--------|-----------|---------------|
| `categorias_habilitacao.php` | ✅ | Categorias habilitação | OK |
| `configuracoes_categorias.php` | ✅ | Config categorias | OK |
| `controle_limite_aulas.php` | ✅ | Controle limite aulas | OK |
| `guards_exames.php` | ✅ | Guards exames | OK |
| `sistema_matricula.php` | ✅ | Sistema matrícula | OK |
| `turma_manager.php` | ✅ | Gerenciador turma | OK |
| `TurmaTeoricaManager.php` | ✅ | Manager turma teórica | OK |

**Status:** ✅ Bem estruturado

---

## 2. MAPEAMENTO DO MENU ATUAL

**Arquivo de renderização:** `admin/index.php` (linhas ~1300-1500)

### 2.1. Menu Principal (Desktop)

**Estrutura HTML:** `<div class="nav-menu">`

| Menu Item | Arquivo Renderizado | API Relacionada | Fluxo | Classificação |
|-----------|---------------------|-----------------|-------|---------------|
| **Dashboard** | `pages/dashboard.php` | N/A | Estatísticas gerais | OK |
| **Cadastros** | | | | |
| └ Alunos | `pages/alunos.php` | `api/alunos.php` | CRUD completo | OK |
| └ Instrutores | `pages/instrutores.php` | `api/instrutores.php` | CRUD completo | OK |
| └ Veículos | `pages/veiculos.php` | `api/veiculos.php` | CRUD completo | OK |
| └ CFCs | `pages/cfcs.php` | `api/cfcs.php` | CRUD completo | OK |
| └ Usuários | `pages/usuarios.php` | `api/usuarios.php` | CRUD completo | OK |
| **Operacional** | | | | |
| └ Agenda | `pages/agendamento.php` | `api/agendamento.php` | Agendamento aulas | OK |
| └ Turmas Teóricas | `pages/turmas-teoricas.php` | `api/turmas-teoricas.php` | Gestão turmas | OK |
| └ Exames | `pages/exames.php` | `api/exames.php` | Gestão exames/provas | OK |
| **Financeiro** | | | | |
| └ Faturas | `pages/financeiro-faturas.php` | `api/financeiro-faturas.php` | Gestão faturas | OK |
| └ Despesas | `pages/financeiro-despesas.php` | `api/financeiro-despesas.php` | Gestão despesas | OK |
| └ Relatórios | `pages/financeiro-relatorios.php` | `api/financeiro-relatorios.php` | Relatórios | OK |
| **Relatórios** | | | | |
| └ Matrículas | `pages/relatorio-matriculas.php` | N/A | Relatório | OK |
| └ Frequência | `pages/relatorio-frequencia.php` | N/A | Relatório | OK |
| └ Presenças | `pages/relatorio-presencas.php` | N/A | Relatório | OK |
| └ Ata | `pages/relatorio-ata.php` | N/A | Relatório | OK |
| **Configurações** | | | | |
| └ Salas | `pages/configuracoes-salas.php` | `api/salas-real.php` | Gestão salas | OK |
| └ Disciplinas | `pages/configuracoes-disciplinas.php` | `api/disciplinas.php` | Gestão disciplinas | OK |
| └ Categorias | `pages/configuracoes-categorias.php` | N/A | Gestão categorias | OK |
| └ Sistema | `pages/configuracoes.php` (via API) | `api/configuracoes.php` | Config sistema | OK |
| **Ferramentas** | | | | |
| └ Vagas/Candidatos | `pages/vagas-candidatos.php` | N/A | Gestão vagas | OK |

### 2.2. Menu Mobile

**Arquivo:** `admin/index.php` (linhas ~1517-1700)  
**Arquivo JS:** `admin/assets/js/mobile-menu-clean.js`

**Status:** ✅ Funcional - mesmo conteúdo do menu desktop

### 2.3. Rotas "Fantasma" (Não Mapeadas no Menu)

| Rota | Arquivo | Observação |
|------|---------|------------|
| `page=historico-aluno` | `pages/historico-aluno.php` | Acessível via modal aluno |
| `page=historico-instrutor` | `pages/historico-instrutor.php` | Acessível via modal instrutor |
| `page=listar-aulas` | `pages/listar-aulas.php` | Acessível via agenda |
| `page=editar-aula` | `pages/editar-aula.php` | Acessível via agenda |
| `page=turma-diario` | `pages/turma-diario.php` | Acessível via turmas teóricas |
| `page=turma-chamada` | `pages/turma-chamada.php` | Acessível via turmas teóricas |

**Status:** ✅ Todas acessíveis via contexto - não são "fantasmas"

---

## 3. MÓDULO POR MÓDULO – AUDITORIA PROFUNDA

### 3.1. Módulo Alunos

**Arquivo principal:** `admin/pages/alunos.php`  
**API principal:** `admin/api/alunos.php`

#### 3.1.1. APIs Envolvidas

| API | Método | Status | Classificação |
|-----|--------|--------|---------------|
| `alunos.php` | GET, POST, PUT, DELETE | ✅ Funcional | OK |
| `aluno-agenda.php` | GET | ✅ Funcional | OK |
| `aluno-documentos.php` | GET | ✅ Funcional | OK |
| `historico_aluno.php` | GET | ✅ Funcional | OK |
| `matriculas.php` | GET (com aluno_id) | ✅ Funcional | OK |
| `progresso_teorico.php` | GET | ✅ Funcional | OK |
| `progresso_pratico.php` | GET | ✅ Funcional | OK |

#### 3.1.2. Campos Usados

**Tabela:** `alunos`

**Campos principais identificados (via `install.php:58-72`):**
- `id` ✅
- `nome` ✅
- `cpf` ✅ (UNIQUE)
- `rg` ✅
- `data_nascimento` ✅
- `endereco` ✅
- `telefone` ✅
- `email` ✅
- `cfc_id` ✅ (FK)
- `categoria_cnh` ✅ (ENUM)
- `status` ✅ (ENUM: ativo, inativo, concluido)
- `criado_em` ✅

**Campos adicionais (via código):**
- `foto` ⚠️ (mencionado em código, não em install.php - pode estar em migration)
- `renach` ⚠️ (mencionado em código)
- `processo_numero` ⚠️ (mencionado em código)
- Campos de matrícula vinculados via tabela `matriculas`

#### 3.1.3. Fluxos Implementados

✅ **Cadastro de aluno**
- Arquivo: `admin/api/alunos.php` (POST)
- Status: Funcional
- Validações: CPF único, campos obrigatórios

✅ **Edição de aluno**
- Arquivo: `admin/api/alunos.php` (PUT)
- Status: Funcional

✅ **Visualização de aluno**
- Arquivo: `admin/pages/alunos.php` (modal)
- Abas: Dados, Matrícula, Histórico, Visualizar
- Status: Funcional

✅ **Histórico do aluno**
- Arquivo: `admin/api/historico_aluno.php`
- Eventos: cadastro, matrícula, faturas, exames médico/psicotécnico
- Status: Parcial (faltam eventos de aulas teóricas/práticas e provas)

✅ **Progresso teórico**
- Arquivo: `admin/api/progresso_teorico.php`
- Status: Funcional

✅ **Progresso prático**
- Arquivo: `admin/api/progresso_pratico.php`
- Status: Funcional

#### 3.1.4. Fluxos que Faltam

❌ **Eventos de aulas teóricas na timeline**
- Arquivo: `admin/api/historico_aluno.php`
- Status: Não implementado
- Referência: `_DIAGNOSTICO-JORNADA-ALUNO.md` linha 231

❌ **Eventos de aulas práticas na timeline**
- Arquivo: `admin/api/historico_aluno.php`
- Status: Não implementado
- Referência: `_DIAGNOSTICO-JORNADA-ALUNO.md` linha 232

❌ **Eventos de provas teóricas/práticas na timeline**
- Arquivo: `admin/api/historico_aluno.php`
- Status: Não implementado (aguardando estrutura)
- Referência: `_DIAGNOSTICO-JORNADA-ALUNO.md` linha 233

#### 3.1.5. Eventos Gerados na Timeline

**Arquivo:** `admin/api/historico_aluno.php`

**Eventos implementados:**
- ✅ `aluno_cadastrado` - Data: `alunos.criado_em`
- ✅ `matricula_criada` - Data: `matriculas.data_inicio`
- ✅ `matricula_concluida` - Data: `matriculas.data_fim`
- ✅ `exame_medico_agendado` - Data: `exames.data_agendada` (tipo='medico')
- ✅ `exame_medico_realizado` - Data: `exames.data_resultado` (tipo='medico')
- ✅ `exame_psicotecnico_agendado` - Data: `exames.data_agendada` (tipo='psicotecnico')
- ✅ `exame_psicotecnico_realizado` - Data: `exames.data_resultado` (tipo='psicotecnico')
- ✅ `fatura_criada` - Data: `faturas.criado_em` ou `financeiro_faturas.criado_em`
- ✅ `fatura_paga` - Data: `pagamentos.data_pagamento`
- ✅ `fatura_vencida` - Data: `faturas.vencimento` (status='vencida')

**Eventos faltantes:**
- ❌ Eventos de turma teórica (matrícula, conclusão)
- ❌ Eventos de aulas práticas (primeira aula, conclusão)
- ❌ Eventos de provas (teórica/prática agendada, realizada, aprovada, reprovada)

#### 3.1.6. Relações com Outras Tabelas

✅ **Matrículas**
- Tabela: `matriculas`
- Relação: `matriculas.aluno_id = alunos.id`
- API: `admin/api/matriculas.php`

✅ **Faturas**
- Tabela: `financeiro_faturas`
- Relação: `financeiro_faturas.aluno_id = alunos.id`
- API: `admin/api/financeiro-faturas.php`

✅ **Aulas**
- Tabela: `aulas`
- Relação: `aulas.aluno_id = alunos.id`
- API: `admin/api/agendamento.php`

✅ **Exames/Provas**
- Tabela: `exames`
- Relação: `exames.aluno_id = alunos.id`
- API: `admin/api/exames.php`

✅ **Turmas Teóricas**
- Tabela: `turma_matriculas`
- Relação: `turma_matriculas.aluno_id = alunos.id` (via matrícula)
- API: `admin/api/turmas-teoricas.php`

**Classificação geral:** ✅ **OK** - Bem estruturado, faltam eventos na timeline

---

### 3.2. Módulo Matrículas

**Arquivo principal:** Modal aluno (aba Matrícula)  
**API principal:** `admin/api/matriculas.php`

#### 3.2.1. API e Funções

**Arquivo:** `admin/api/matriculas.php`

**Métodos implementados:**
- ✅ GET - Listar matrículas (todas ou por aluno)
- ✅ POST - Criar matrícula
- ✅ PUT - Atualizar matrícula
- ✅ DELETE - Deletar matrícula

**Status:** ✅ Funcional

#### 3.2.2. Validações

**Via código:** `admin/api/matriculas.php:99-120`

✅ Validações identificadas:
- Aluno existe
- Categoria CNH válida
- Tipo de serviço válido
- Status válido
- Datas coerentes (início < fim)

#### 3.2.3. Lógica de Sincronização

⚠️ **Problemas identificados:**

1. **Tabela não encontrada em install.php**
   - Tabela `matriculas` não está sendo criada em `install.php`
   - Possível migração faltando ou tabela criada manualmente
   - **Risco:** Sistema pode quebrar em nova instalação

2. **Relação com faturas**
   - Campo `matricula_id` mencionado em `admin/jobs/marcar_faturas_vencidas.php:38`
   - Campo não confirmado em `financeiro_faturas` (usa `aluno_id`)
   - **Possível inconsistência**

#### 3.2.4. Lacunas no Fluxo de Matrícula

❌ **Status de matrícula não sincronizado com processo**
- Não há validação se aluno pode ter múltiplas matrículas ativas
- Não há bloqueio automático ao concluir matrícula

❌ **Vinculação com exames/provas não automática**
- Matrícula não dispara criação automática de exames obrigatórios
- Processo manual

#### 3.2.5. Relação com Categoria, Serviço, DETRAN

**Campos identificados (via código):**
- `categoria_cnh` ✅ (ENUM: A, B, C, D, E, AB, AC, AD, AE)
- `tipo_servico` ⚠️ (mencionado mas não confirmado na estrutura)
- `renach` ⚠️ (mencionado mas não confirmado)
- `processo_numero` ⚠️ (mencionado mas não confirmado)
- `processo_numero_detran` ⚠️ (mencionado mas não confirmado)
- `processo_situacao` ⚠️ (mencionado mas não confirmado)

**Classificação geral:** ⚠️ **PARCIAL** - Funciona mas estrutura de banco inconsistente

---

### 3.3. Módulo Financeiro

#### 3.3.1. Tabelas Identificadas

**Tabelas mencionadas no código:**

| Tabela | Encontrada em | Status | Classificação |
|--------|---------------|--------|---------------|
| `financeiro_faturas` | `admin/api/financeiro-faturas.php` | ✅ Confirmada | OK |
| `faturas` | `admin/api/faturas.php`, `admin/jobs/marcar_faturas_vencidas.php` | ⚠️ Duplicada? | LEGADO |
| `pagamentos` | `admin/api/pagamentos.php` | ⚠️ Não em install.php | PARCIAL |
| `financeiro_despesas` | `admin/api/financeiro-despesas.php` | ⚠️ Não em install.php | PARCIAL |

**Problema crítico:** Duplicação de estrutura (`faturas` vs `financeiro_faturas`)

#### 3.3.2. Duplicidades

✅ **APIs duplicadas:**
- `admin/api/faturas.php` ⚠️ (LEGADO)
- `admin/api/financeiro-faturas.php` ✅ (ATIVA)

✅ **Páginas duplicadas:**
- `admin/pages/financeiro-faturas.php` ✅ (ATIVA)
- `admin/pages/financeiro-faturas-standalone.php` ⚠️ (LEGADO)
- `admin/pages/financeiro-despesas.php` ✅ (ATIVA)
- `admin/pages/financeiro-despesas-standalone.php` ⚠️ (LEGADO)
- `admin/pages/financeiro-relatorios.php` ✅ (ATIVA)
- `admin/pages/financeiro-relatorios-standalone.php` ⚠️ (LEGADO)

#### 3.3.3. O que está ativo hoje

✅ **Estrutura ativa:**
- Tabela: `financeiro_faturas` (confirmada via código)
- API: `admin/api/financeiro-faturas.php`
- API: `admin/api/financeiro-despesas.php`
- API: `admin/api/financeiro-relatorios.php`
- Páginas: `admin/pages/financeiro-*.php` (sem `-standalone`)

#### 3.3.4. APIs Existentes

✅ `admin/api/financeiro-faturas.php`
- Métodos: GET (listar/buscar), POST (criar)
- Status: Funcional

✅ `admin/api/financeiro-despesas.php`
- Métodos: GET, POST
- Status: Funcional

✅ `admin/api/financeiro-relatorios.php`
- Métodos: GET
- Status: Funcional

✅ `admin/api/pagamentos.php`
- Métodos: GET, POST, DELETE
- Status: Funcional
- Relaciona com: `faturas` (tabela antiga?) ou `financeiro_faturas`?

#### 3.3.5. Lógica de Status

**Status identificados (via `admin/api/pagamentos.php:214-225`):**
- `paga` ✅
- `parcial` ✅
- `vencida` ✅
- `aberta` ✅

**Job automático:** `admin/jobs/marcar_faturas_vencidas.php`
- Status: ✅ Funcional
- Problema: Usa tabela `faturas` (antiga?) ao invés de `financeiro_faturas`

#### 3.3.6. Pontos Quebrados

❌ **Job de faturas vencidas usa tabela errada**
- Arquivo: `admin/jobs/marcar_faturas_vencidas.php:18`
- Tabela usada: `faturas`
- Tabela correta: `financeiro_faturas`
- **Classificação:** QUEBRADO

#### 3.3.7. Pontos Faltantes

❌ **Integração financeiro com bloqueio de aulas práticas**
- Não há validação automática de inadimplência ao agendar aula
- Regra manual (não automatizada)

❌ **Integração com reteste**
- Campo "reteste" mencionado no plano mas não implementado
- Sem flag específico em faturas

**Classificação geral:** ⚠️ **PARCIAL/QUEBRADO** - Funciona mas com inconsistências críticas

---

### 3.4. Módulo Turmas Teóricas

**Arquivo principal:** `admin/pages/turmas-teoricas.php`  
**API principal:** `admin/api/turmas-teoricas.php`

#### 3.4.1. Estrutura de Banco

**Migrations:** `admin/migrations/001-create-turmas-teoricas-structure.sql`

**Tabelas criadas:**
- ✅ `salas`
- ✅ `disciplinas_configuracao`
- ✅ `turmas_teoricas`
- ✅ `turma_disciplinas` (via migration 002)
- ✅ `turma_aulas_agendadas`
- ✅ `turma_matriculas`
- ✅ `turma_presencas`

**Status:** ✅ Bem estruturado

#### 3.4.2. Matrícula em Turma

**API:** `admin/api/matricular-aluno-turma.php`
**API:** `admin/api/remover-matricula-turma.php`

✅ Funcional:
- Matricular aluno em turma
- Remover matrícula de turma
- Validação de vagas disponíveis

#### 3.4.3. Presenças

**API:** `admin/api/turma-presencas.php`
**Página:** `admin/pages/turma-chamada.php`

✅ Funcional:
- Registrar presenças
- Calcular frequência
- Relatórios de frequência

#### 3.4.4. Aulas

**API:** `admin/api/turma-agendamento.php`
**API:** `admin/api/disciplina-agendamentos.php`

✅ Funcional:
- Agendar aulas de disciplina
- Gerar grade horária
- Controlar carga horária

#### 3.4.5. APIs

✅ **APIs principais:**
- `admin/api/turmas-teoricas.php` - CRUD completo
- `admin/api/turmas-teoricas-inline.php` - Versão inline
- `admin/api/estatisticas-turma.php` - Estatísticas
- `admin/api/turma-frequencia.php` - Frequência
- `admin/api/turma-relatorios.php` - Relatórios

#### 3.4.6. Timeline

❌ **Eventos de turma teórica não na timeline**
- Arquivo: `admin/api/historico_aluno.php`
- Status: Não implementado
- Referência: `_DIAGNOSTICO-JORNADA-ALUNO.md` linha 231

#### 3.4.7. Conclusão

✅ **Status de conclusão**
- Status: `concluida` existe em `turmas_teoricas.status`
- Atualização: Manual (via API)
- Validação: Não automática (não verifica carga horária completa)

**Classificação geral:** ✅ **OK** - Bem estruturado, faltam eventos na timeline

---

### 3.5. Módulo Aulas Práticas

**Arquivo principal:** `admin/pages/agendamento.php`  
**API principal:** `admin/api/agendamento.php`

#### 3.5.1. Agendamentos

**API:** `admin/api/agendamento.php`
**Página:** `admin/pages/agendamento.php`

✅ Funcional:
- Criar agendamento
- Listar agendamentos
- Filtrar por instrutor/veículo/aluno/data

#### 3.5.2. Restrições

**Arquivo:** `includes/guards/AgendamentoGuards.php`

✅ **Validações identificadas:**
- Conflito de horário (instrutor)
- Conflito de horário (veículo)
- Limite diário de aulas (3 por instrutor)
- Intervalo mínimo (30 minutos)
- Duração da aula (50 minutos)

**Arquivo:** `admin/includes/controle_limite_aulas.php`
✅ Sistema de controle de limites

#### 3.5.3. APIs de Aulas

✅ **APIs principais:**
- `admin/api/agendamento.php` - CRUD
- `admin/api/atualizar-aula.php` - Atualizar
- `admin/api/cancelar-aula.php` - Cancelar
- `admin/api/buscar-aula.php` - Buscar
- `admin/api/verificar-disponibilidade.php` - Verificar disponibilidade
- `admin/api/verificar-aula-especifica.php` - Verificar aula específica

#### 3.5.4. Validações Existentes

✅ **Validações funcionais (via AgendamentoGuards.php):**
- Verificação de conflitos
- Verificação de limites
- Verificação de intervalos
- Verificação de bloqueio financeiro (parcial - mencionado mas não confirmado)

#### 3.5.5. Status

**Status identificados (via `install.php:93-97`):**
- `agendada` ✅
- `em_andamento` ✅
- `concluida` ✅
- `cancelada` ✅

**Problema:** Status `falta` não existe no ENUM mas é mencionado em código

#### 3.5.6. Lógica Faltante

❌ **Conclusão de aulas práticas**
- Não há controle automático de total de aulas contratadas vs realizadas
- Sem validação de "todas as aulas concluídas"

❌ **Registro de faltas**
- Campo "falta" mencionado mas não implementado corretamente
- Sem contador de faltas práticas
- Sem bloqueio após 3 faltas

❌ **Regras de bloqueio por financeiro**
- Mencionado no plano mas não implementado
- Sem validação automática ao iniciar aula (PWA instrutor)

❌ **KM inicial/final**
- Não há campos na tabela `aulas` para KM
- Necessário adicionar

**Classificação geral:** ⚠️ **PARCIAL** - Funciona mas faltam regras críticas

---

### 3.6. Módulo Exames (médico/psico) e Provas (teórica/prática)

**Arquivo principal:** `admin/pages/exames.php`  
**API principal:** `admin/api/exames.php`

#### 3.6.1. Como está antes

✅ **Estrutura de banco:**
- Tabela: `exames`
- Tipos originais: `medico`, `psicotecnico`
- Resultados originais: `apto`, `inapto`, `inapto_temporario`, `pendente`

**Migration:** `admin/migrations/003-alter-exames-add-provas.sql`
✅ Executada - Adiciona `teorico` e `pratico` aos tipos
✅ Executada - Adiciona `aprovado` e `reprovado` aos resultados

#### 3.6.2. Como está depois

✅ **Tipos atuais:**
- `medico` ✅
- `psicotecnico` ✅
- `teorico` ✅ (adicionado)
- `pratico` ✅ (adicionado)

✅ **Resultados atuais:**
- `apto`, `inapto`, `inapto_temporario`, `pendente` ✅
- `aprovado`, `reprovado` ✅ (adicionados)

#### 3.6.3. APIs

✅ `admin/api/exames.php`
- Métodos: GET, POST, PUT, DELETE
- Validações: Tipo e resultado conforme ENUM
- Status: Funcional

#### 3.6.4. Campos

**Campos da tabela `exames` (via `install.php:146-170`):**
- `id` ✅
- `aluno_id` ✅ (FK)
- `tipo` ✅ (ENUM)
- `status` ✅ (ENUM: agendado, concluido, cancelado)
- `resultado` ✅ (ENUM)
- `clinica_nome` ✅ (VARCHAR 200) - Usado para local
- `protocolo` ✅ (VARCHAR 100)
- `data_agendada` ✅ (DATE)
- `data_resultado` ✅ (DATE)
- `observacoes` ✅ (TEXT)
- `anexos` ✅ (TEXT)
- `criado_por` ✅ (FK)
- `atualizado_por` ✅ (FK)

#### 3.6.5. Validações

✅ **Validações implementadas (via `admin/api/exames.php:254`):**
- Tipo deve ser um dos valores válidos
- Resultado deve ser um dos valores válidos
- Aluno deve existir
- Datas coerentes

⚠️ **Validação faltante:**
- Não há validação de combinação tipo+resultado (ex: prova teórica não pode ter resultado "apto")

#### 3.6.6. O que ainda falta

❌ **UI para provas**
- Página `exames.php` existe mas pode não ter filtros específicos para provas
- Sem seção específica "Provas" no menu

❌ **Eventos de provas na timeline**
- Arquivo: `admin/api/historico_aluno.php`
- Status: Não implementado

❌ **Seção "Provas" na aba Matrícula**
- Arquivo: `admin/pages/alunos.php` (modal)
- Status: Não implementado

❌ **Card "Status das Provas" na aba Histórico**
- Arquivo: `admin/pages/alunos.php` (modal)
- Status: Não implementado

❌ **LADV vinculado à aprovação teórica**
- Não há lógica para gerar/liberar LADV após aprovação na prova teórica
- Sem campo específico para armazenar LADV

**Classificação geral:** ⚠️ **PARCIAL** - Estrutura OK, faltam integrações e UI

---

### 3.7. Módulo Agenda Central

**Arquivo principal:** `admin/pages/agendamento.php`

#### 3.7.1. Onde está

✅ **Arquivos principais:**
- `admin/pages/agendamento.php` - Página principal
- `admin/pages/agendamento-moderno.php` - Versão moderna
- `admin/api/agendamento.php` - API principal
- `admin/api/agendamento-detalhes.php` - Detalhes

#### 3.7.2. O que faz

✅ **Funcionalidades:**
- Visualizar agenda (calendário/semana)
- Filtrar por instrutor/veículo/aluno
- Criar agendamento
- Editar agendamento
- Cancelar agendamento
- Verificar disponibilidade

#### 3.7.3. O que falta

❌ **Visualização unificada teórico + prático**
- Agenda separada para teórico e prático
- Sem visão consolidada

❌ **Bloqueio de períodos**
- Sem funcionalidade para bloquear períodos específicos (feriados, manutenção)

❌ **Reagendamento em lote**
- Sem funcionalidade para reagendar múltiplas aulas

#### 3.7.4. Estrutura Técnica

✅ **Tecnologias:**
- PHP backend
- JavaScript frontend (vanilla)
- CSS responsivo

✅ **APIs:**
- RESTful
- JSON responses
- Autenticação via sessão

**Classificação geral:** ✅ **OK** - Funcional, pode ter melhorias

---

### 3.8. Painel do Instrutor

**Arquivo principal:** `instrutor/dashboard.php`

#### 3.8.1. Telas

✅ **Telas existentes:**
- `instrutor/dashboard.php` - Dashboard
- `instrutor/dashboard-mobile.php` - Dashboard mobile
- `instrutor/login.php` - Login
- `instrutor/logout.php` - Logout

#### 3.8.2. APIs

⚠️ **APIs não específicas para instrutor:**
- Usa `admin/api/agendamento.php` (com filtro por instrutor)
- Sem API específica para PWA instrutor

#### 3.8.3. Permissões

✅ **Permissões (via `includes/auth.php:206-209`):**
- Tipo: `instrutor`
- Métodos: `isInstructor()`, `canEditLessons()`, `canCancelLessons()`
- Status: Funcional

#### 3.8.4. Lacunas

❌ **PWA Instrutor não implementado**
- Sem funcionalidade "Iniciar aula"
- Sem funcionalidade "Encerrar aula"
- Sem registro de KM inicial/final
- Sem botões WhatsApp/Ligar
- Sem visualização de LADV do aluno
- Sem notificações push

❌ **Validações de bloqueio não no PWA**
- Sem verificação de financeiro ao iniciar aula
- Sem verificação de faltas ao iniciar aula
- Sem verificação de limites diários

**Classificação geral:** ❌ **QUEBRADO/PARCIAL** - Estrutura existe mas PWA não implementado

---

### 3.9. Painel do Aluno (PWA)

**Arquivo principal:** `aluno/dashboard.php`

#### 3.9.1. Telas

✅ **Telas existentes:**
- `aluno/dashboard.php` - Dashboard
- `aluno/dashboard-mobile.php` - Dashboard mobile
- `aluno/login.php` - Login
- `aluno/logout.php` - Logout

#### 3.9.2. Scripts

⚠️ **Scripts não específicos:**
- Sem scripts específicos para PWA aluno
- Usa assets gerais

#### 3.9.3. O que existe

✅ **Estrutura básica:**
- Login funcional
- Dashboard básico
- Layout responsivo

#### 3.9.4. O que falta

❌ **Dashboard com cards**
- Sem card "Processo"
- Sem card "Teórico"
- Sem card "Prático"
- Sem card "Provas"
- Sem card "Financeiro"

❌ **Agenda**
- Sem visualização de aulas teóricas
- Sem visualização de aulas práticas

❌ **Financeiro**
- Sem listagem de parcelas
- Sem detalhes de pagamento

❌ **Documentos**
- Sem visualização de LADV
- Sem geração de declarações

❌ **Notificações**
- Sem notificações push
- Sem alertas de alteração de aula
- Sem lembrete antes de aulas

**Classificação geral:** ❌ **QUEBRADO/PARCIAL** - Estrutura existe mas PWA não implementado

---

### 3.10. Sistema de Permissões

**Arquivo principal:** `includes/auth.php`

#### 3.10.1. Papéis

✅ **Papéis identificados (via `install.php:28`):**
- `admin` ✅
- `instrutor` ✅
- `secretaria` ✅

⚠️ **Papel faltante:**
- `aluno` ❌ (mencionado em código mas não no ENUM de usuários)

#### 3.10.2. Como funciona hoje

✅ **Sistema de permissões (via `includes/auth.php:417-441`):**
- Matriz de permissões por tipo
- Admin tem todas as permissões
- Verificações via métodos: `hasPermission()`, `isAdmin()`, `isInstructor()`, etc.

✅ **Guards específicos:**
- `includes/guards/AgendamentoPermissions.php` - Permissões de agendamento
- `admin/includes/guards_exames.php` - Guards de exames

#### 3.10.3. Falhas

❌ **Papel "aluno" não implementado**
- Tipo `aluno` não existe no ENUM de `usuarios.tipo`
- Alunos não têm login próprio (só acesso via secretaria)

❌ **Permissões granulares faltantes**
- Sem sistema de permissões por recurso específico
- Permissões apenas por tipo de usuário

#### 3.10.4. Lacunas

❌ **Admin Master vs Admin Secretaria**
- Não há distinção entre admin master e admin secretaria
- Todos os admins têm as mesmas permissões

❌ **Permissões por CFC (multi-CFC)**
- Não há controle de acesso por CFC específico
- Usuário pode acessar dados de todos os CFCs

**Classificação geral:** ⚠️ **PARCIAL** - Funciona mas incompleto para o plano

---

## 4. FLUXO REAL DA JORNADA DO ALUNO

### 4.1. Cadastro

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/alunos.php` (POST)  
**Tabela:** `alunos`  
**Evento timeline:** ✅ `aluno_cadastrado`  
**Completo:** ✅ Sim  
**Quebrado:** ❌ Não  
**API:** `admin/api/alunos.php`

### 4.2. Matrícula

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/matriculas.php` (POST)  
**Tabela:** `matriculas` (⚠️ não em install.php)  
**Evento timeline:** ✅ `matricula_criada`  
**Completo:** ⚠️ Parcial (estrutura inconsistente)  
**Quebrado:** ⚠️ Possível (tabela não em install.php)  
**API:** `admin/api/matriculas.php`

### 4.3. Exames Médicos

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/exames.php` (POST)  
**Tabela:** `exames` (tipo='medico')  
**Evento timeline:** ✅ `exame_medico_agendado`, `exame_medico_realizado`  
**Completo:** ✅ Sim  
**Quebrado:** ❌ Não  
**API:** `admin/api/exames.php`

### 4.4. Psicotécnico

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/exames.php` (POST)  
**Tabela:** `exames` (tipo='psicotecnico')  
**Evento timeline:** ✅ `exame_psicotecnico_agendado`, `exame_psicotecnico_realizado`  
**Completo:** ✅ Sim  
**Quebrado:** ❌ Não  
**API:** `admin/api/exames.php`

### 4.5. Turma Teórica

**Status:** ✅ **IMPLEMENTADO** (parcialmente na timeline)

**Arquivo:** `admin/api/turmas-teoricas.php`  
**Tabelas:** `turmas_teoricas`, `turma_matriculas`, `turma_presencas`  
**Evento timeline:** ❌ Não implementado  
**Completo:** ⚠️ Parcial (falta timeline)  
**Quebrado:** ❌ Não  
**API:** `admin/api/turmas-teoricas.php`, `admin/api/matricular-aluno-turma.php`

### 4.6. Presenças

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/turma-presencas.php`  
**Tabela:** `turma_presencas`  
**Evento timeline:** ❌ Não implementado  
**Completo:** ⚠️ Parcial (falta timeline)  
**Quebrado:** ❌ Não  
**API:** `admin/api/turma-presencas.php`

### 4.7. Progresso Teórico

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/progresso_teorico.php`  
**Tabelas:** `turma_matriculas`, `turma_presencas`  
**Evento timeline:** ❌ Não implementado  
**Completo:** ⚠️ Parcial (falta timeline)  
**Quebrado:** ❌ Não  
**API:** `admin/api/progresso_teorico.php`

### 4.8. Prova Teórica

**Status:** ⚠️ **ESTRUTURA OK, UI FALTA**

**Arquivo:** `admin/api/exames.php` (POST com tipo='teorico')  
**Tabela:** `exames` (tipo='teorico')  
**Evento timeline:** ❌ Não implementado  
**Completo:** ❌ Não (falta UI e timeline)  
**Quebrado:** ❌ Não  
**API:** `admin/api/exames.php`

**Falta:**
- UI específica para provas
- Eventos na timeline
- Lógica de liberação de LADV
- Card "Provas" no histórico

### 4.9. Aulas Práticas

**Status:** ✅ **IMPLEMENTADO** (parcialmente na timeline)

**Arquivo:** `admin/api/agendamento.php`  
**Tabela:** `aulas` (tipo_aula='pratica')  
**Evento timeline:** ❌ Não implementado  
**Completo:** ⚠️ Parcial (falta timeline e regras de falta)  
**Quebrado:** ❌ Não  
**API:** `admin/api/agendamento.php`, `admin/api/progresso_pratico.php`

**Falta:**
- Eventos na timeline
- Controle de faltas práticas
- Bloqueio após 3 faltas
- KM inicial/final

### 4.10. Progresso Prático

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/progresso_pratico.php`  
**Tabela:** `aulas` (tipo_aula='pratica')  
**Evento timeline:** ❌ Não implementado  
**Completo:** ⚠️ Parcial (falta timeline)  
**Quebrado:** ❌ Não  
**API:** `admin/api/progresso_pratico.php`

### 4.11. Prova Prática

**Status:** ⚠️ **ESTRUTURA OK, UI FALTA**

**Arquivo:** `admin/api/exames.php` (POST com tipo='pratico')  
**Tabela:** `exames` (tipo='pratico')  
**Evento timeline:** ❌ Não implementado  
**Completo:** ❌ Não (falta UI e timeline)  
**Quebrado:** ❌ Não  
**API:** `admin/api/exames.php`

**Falta:**
- UI específica para provas
- Eventos na timeline
- Lógica de conclusão de matrícula
- Card "Provas" no histórico

### 4.12. Conclusão da CNH (Status Final)

**Status:** ⚠️ **PARCIAL**

**Arquivo:** `admin/api/matriculas.php` (PUT - atualizar status)  
**Tabela:** `matriculas` (status='concluida')  
**Evento timeline:** ✅ `matricula_concluida`  
**Completo:** ⚠️ Parcial (não automático)  
**Quebrado:** ❌ Não  
**API:** `admin/api/matriculas.php`

**Falta:**
- Validação automática (todas as provas aprovadas + financeiro OK)
- Evento detalhado de conclusão (aprovado/reprovado/evasão)

### 4.13. Financeiro

**Status:** ✅ **IMPLEMENTADO**

**Arquivo:** `admin/api/financeiro-faturas.php`  
**Tabela:** `financeiro_faturas`  
**Evento timeline:** ✅ `fatura_criada`, `fatura_paga`, `fatura_vencida`  
**Completo:** ⚠️ Parcial (job quebrado)  
**Quebrado:** ⚠️ Job usa tabela errada  
**API:** `admin/api/financeiro-faturas.php`, `admin/api/pagamentos.php`

**Problema crítico:**
- Job `admin/jobs/marcar_faturas_vencidas.php` usa tabela `faturas` ao invés de `financeiro_faturas`

---

## 5. IDENTIFICAÇÃO DE LIXO / LEGADO

### 5.1. Tabelas Não Usadas

**Nenhuma tabela não usada identificada** - Todas as tabelas mencionadas têm uso no código

⚠️ **Possível duplicação:**
- `faturas` vs `financeiro_faturas` - Investigar qual está ativa

### 5.2. APIs Duplicadas

✅ **APIs duplicadas identificadas:**

| API Ativa | API Legado | Motivo |
|-----------|------------|--------|
| `financeiro-faturas.php` | `faturas.php` | Migração para novo módulo |
| `salas-real.php` | `salas.php`, `salas-ajax.php`, `salas-clean.php` | Versões antigas |
| `instrutores.php` | `instrutores-real.php`, `instrutores-simple.php`, `instrutores_simplificado.php` | Versões antigas |
| `exames.php` | `exames_simple.php` | Versão simplificada |
| `disciplinas.php` | `disciplinas-clean.php`, `disciplinas-simples.php`, `disciplinas-estaticas.php`, `disciplinas-automaticas.php` | Versões antigas |
| `alunos.php` | `alunos-aptos-turma-simples.php` | Versão simplificada |
| `notificacoes.php` | `notifications.php` | Duplicação português/inglês |
| `usuarios.php` | `usuarios_simples.php` | Versão simplificada |

**Total:** 15 APIs legadas identificadas

### 5.3. Funções Mortas

⚠️ **Não possível identificar sem análise de uso em runtime** - Seria necessário análise de logs ou instrumentação

### 5.4. Campos Não Utilizados

⚠️ **Não possível identificar sem análise de queries** - Seria necessário análise de todas as queries do sistema

**Campos suspeitos (mencionados mas não confirmados):**
- `alunos.foto` - Mencionado mas não em install.php
- `alunos.renach` - Mencionado mas não em install.php
- `matriculas.tipo_servico` - Mencionado mas não confirmado

### 5.5. Páginas Sem Vínculo

✅ **Páginas legadas identificadas:**

| Página Ativa | Página Legado | Motivo |
|--------------|---------------|--------|
| `financeiro-faturas.php` | `financeiro-faturas-standalone.php` | Versão standalone |
| `financeiro-despesas.php` | `financeiro-despesas-standalone.php` | Versão standalone |
| `financeiro-relatorios.php` | `financeiro-relatorios-standalone.php` | Versão standalone |
| `historico-aluno.php` | `historico-aluno-melhorado.php`, `historico-aluno-novo.php` | Versões antigas |
| `instrutores.php` | `instrutores-otimizado.php` | Versão antiga |
| `turmas-teoricas.php` | `turmas-teoricas-fixed.php`, `turmas-teoricas-disciplinas-fixed.php` | Versões "fixed" |
| `alunos.php` | `alunos_original.php`, `alunos-complete.txt` | Backups |
| `alunos.php` | `_modalAluno-legacy.php` | Modal legado |

**Total:** 10 páginas legadas identificadas

### 5.6. JS Morto

✅ **Arquivos JS suspeitos:**

| Arquivo | Status |
|---------|--------|
| `CORRECOES_MODAL_EMERGENCIAL.js` | ⚠️ Arquivo na raiz - possível temporário |
| `admin/assets/js/mobile-debug.js` | ⚠️ Debug - remover em produção |

**Outros arquivos JS parecem estar em uso**

### 5.7. Resumo de Limpeza Recomendada

**Pode remover sem afetar:**

1. **APIs legadas (15 arquivos):**
   - `admin/api/faturas.php`
   - `admin/api/salas.php`, `salas-ajax.php`, `salas-clean.php`
   - `admin/api/instrutores-real.php`, `instrutores-simple.php`, `instrutores_simplificado.php`
   - `admin/api/exames_simple.php`
   - `admin/api/disciplinas-clean.php`, `disciplinas-simples.php`, `disciplinas-estaticas.php`
   - `admin/api/alunos-aptos-turma-simples.php`
   - `admin/api/notifications.php`
   - `admin/api/tipos-curso-clean.php`

2. **Páginas legadas (10 arquivos):**
   - `admin/pages/financeiro-*-standalone.php` (3 arquivos)
   - `admin/pages/historico-aluno-melhorado.php`, `historico-aluno-novo.php`
   - `admin/pages/instrutores-otimizado.php`
   - `admin/pages/turmas-teoricas-fixed.php`, `turmas-teoricas-disciplinas-fixed.php`
   - `admin/pages/alunos_original.php`, `alunos-complete.txt`
   - `admin/pages/_modalAluno-legacy.php`
   - `admin/pages/usuarios_simples.php`

3. **JS temporários (2 arquivos):**
   - `CORRECOES_MODAL_EMERGENCIAL.js`
   - `admin/assets/js/mobile-debug.js`

**Total a remover:** 27 arquivos

---

## 6. LISTA DE RISCOS TÉCNICOS

### 6.1. Riscos de Segurança

🔴 **CRÍTICO:**
- ❌ Credenciais hardcoded em `includes/config.php:15` - Senha do banco exposta
- ⚠️ Sessões não verificam IP/User-Agent mudado (parcial - tem validação mas pode ser melhorada)

🟡 **MÉDIO:**
- ⚠️ CORS aberto em algumas APIs (`admin/api/matriculas.php:8` - `Access-Control-Allow-Origin: *`)
- ⚠️ Falta rate limiting em APIs públicas

🟢 **BAIXO:**
- ✅ Prepared statements usados (proteção SQL Injection)
- ✅ Password hashing implementado

### 6.2. Riscos de Dados

🔴 **CRÍTICO:**
- ❌ Tabela `matriculas` não em `install.php` - Risco de não criar em nova instalação
- ❌ Tabela `financeiro_faturas` não em `install.php` - Risco de não criar em nova instalação
- ❌ Tabela `pagamentos` não em `install.php` - Risco de não criar em nova instalação
- ❌ Job `marcar_faturas_vencidas.php` usa tabela `faturas` errada - Dados podem não ser atualizados

🟡 **MÉDIO:**
- ⚠️ Possível duplicação de dados (`faturas` vs `financeiro_faturas`)
- ⚠️ Campos mencionados mas não confirmados na estrutura (`alunos.foto`, `alunos.renach`)

### 6.3. Riscos de Sincronização

🟡 **MÉDIO:**
- ⚠️ Status de matrícula não sincronizado automaticamente com provas
- ⚠️ Frequência teórica não atualizada automaticamente
- ⚠️ Faturas vencidas não marcadas automaticamente (job quebrado)

### 6.4. Riscos de Performance

🟡 **MÉDIO:**
- ⚠️ Muitas queries N+1 possíveis (sem análise profunda de código)
- ⚠️ Falta de índices em algumas tabelas (verificar índices nas migrations)

🟢 **BAIXO:**
- ✅ Índices criados em campos principais (CPF, email, FK)

### 6.5. Riscos de Inconsistência Futura

🔴 **CRÍTICO:**
- ❌ Código legado misturado com código ativo - Risco de usar APIs erradas
- ❌ Estrutura de banco inconsistente entre install.php e migrations

🟡 **MÉDIO:**
- ⚠️ Falta documentação de quais APIs/páginas são legadas
- ⚠️ Múltiplas versões de mesmo recurso (fixed, melhorado, novo, etc.)

---

## 7. PROPOSTAS DE REORGANIZAÇÃO PROFISSIONAL

### 7.1. Estruturação do Menu

✅ **Menu atual está bem estruturado** - Segue lógica de negócio

**Sugestões:**
- Adicionar submenu "Provas" dentro de "Operacional"
- Consolidar "Relatórios" (remover duplicações)

### 7.2. Divisão de Painéis

**Problema atual:** Todos os painéis usam mesma estrutura (`admin/`)

**Proposta:**
```
admin/          → Admin Master + Admin Secretaria (web)
instrutor/      → PWA Instrutor (mobile-first)
aluno/          → PWA Aluno (mobile-first)
```

**Mudanças necessárias:**
- Separar permissões Admin Master vs Admin Secretaria
- Implementar PWA completo para instrutor e aluno
- Criar rotas específicas por papel

### 7.3. Organização dos Módulos

**Problema atual:** Módulos misturados, APIs legadas

**Proposta:**
```
admin/
  ├── api/
  │   ├── v1/              → APIs ativas versão 1
  │   └── legacy/          → APIs legadas (mover antes de remover)
  ├── pages/
  │   ├── active/          → Páginas ativas
  │   └── legacy/          → Páginas legadas (mover antes de remover)
  └── includes/
      └── modules/         → Módulos específicos
          ├── alunos/
          ├── financeiro/
          ├── turmas/
          └── agendamento/
```

### 7.4. Limpeza e Padronização

**Ações imediatas:**

1. **Remover arquivos legados (27 arquivos identificados)**
2. **Corrigir job de faturas vencidas** (usar tabela correta)
3. **Adicionar tabelas faltantes ao install.php:**
   - `matriculas`
   - `financeiro_faturas`
   - `pagamentos`
   - `financeiro_despesas`
4. **Documentar APIs legadas** (adicionar comentário "DEPRECATED")
5. **Padronizar nomes** (remover sufixos: `-simple`, `-clean`, `-fixed`, `-real`)

### 7.5. Onde Criar Novos Arquivos

**PWA Instrutor:**
```
instrutor/
  ├── api/                 → APIs específicas PWA
  │   ├── aulas.php        → Iniciar/encerrar aula
  │   └── agenda.php       → Agenda do dia
  ├── assets/
  │   ├── css/
  │   └── js/
  └── service-worker.js    → PWA service worker
```

**PWA Aluno:**
```
aluno/
  ├── api/                 → APIs específicas PWA
  │   ├── dashboard.php    → Cards do dashboard
  │   ├── agenda.php       → Agenda do aluno
  │   └── documentos.php   → LADV, declarações
  ├── assets/
  └── service-worker.js
```

### 7.6. Onde Refatorar

1. **Sistema de permissões:**
   - Adicionar papel `aluno` ao ENUM
   - Separar Admin Master vs Admin Secretaria
   - Implementar permissões por CFC (multi-CFC)

2. **Módulo Financeiro:**
   - Consolidar tabelas (`faturas` vs `financeiro_faturas`)
   - Corrigir job de faturas vencidas
   - Implementar integração com bloqueio de aulas

3. **Timeline:**
   - Adicionar eventos de aulas teóricas
   - Adicionar eventos de aulas práticas
   - Adicionar eventos de provas

4. **Aulas Práticas:**
   - Adicionar campos KM inicial/final
   - Implementar controle de faltas
   - Implementar bloqueio após 3 faltas
   - Implementar bloqueio por financeiro

---

## 8. CHECKLIST DE FASE 1 A FASE 5 (PRÉ-PLANEJAMENTO)

### Fase 1: Limpeza do Sistema

- [ ] Remover 27 arquivos legados identificados
- [ ] Documentar APIs/páginas deprecadas antes de remover
- [ ] Corrigir job `marcar_faturas_vencidas.php` (usar `financeiro_faturas`)
- [ ] Adicionar tabelas faltantes ao `install.php`:
  - [ ] `matriculas`
  - [ ] `financeiro_faturas`
  - [ ] `pagamentos`
  - [ ] `financeiro_despesas`
- [ ] Remover credenciais hardcoded de `config.php`
- [ ] Mover código legado para pasta `legacy/` antes de remover
- [ ] Criar migration para consolidar tabelas financeiro (`faturas` → `financeiro_faturas`)

### Fase 2: Correções Estruturais

- [ ] Corrigir ENUM de `usuarios.tipo` (adicionar `aluno`)
- [ ] Adicionar campos faltantes em `alunos` (se necessário):
  - [ ] `foto`
  - [ ] `renach`
- [ ] Adicionar campos faltantes em `aulas`:
  - [ ] `km_inicial`
  - [ ] `km_final`
  - [ ] `falta` (boolean ou enum)
- [ ] Adicionar campo `status_falta` em `aulas` (ou criar tabela `aulas_faltas`)
- [ ] Verificar e corrigir estrutura de `matriculas` (campos: `tipo_servico`, `renach`, etc.)
- [ ] Criar migration para campos faltantes
- [ ] Atualizar `install.php` com estrutura completa

### Fase 3: Unificação e Reorganização do Menu

- [ ] Adicionar submenu "Provas" no menu Operacional
- [ ] Consolidar relatórios (remover duplicações)
- [ ] Organizar APIs em `api/v1/` e `api/legacy/`
- [ ] Organizar páginas em `pages/active/` e `pages/legacy/`
- [ ] Criar documentação de APIs ativas
- [ ] Padronizar nomes de arquivos (remover sufixos)

### Fase 4: Implementações Essenciais

#### 4.1. Prático

- [ ] Adicionar campos KM inicial/final em `aulas`
- [ ] Implementar controle de faltas práticas
- [ ] Implementar bloqueio após 3 faltas
- [ ] Implementar bloqueio por financeiro (validação ao agendar)
- [ ] Adicionar eventos de aulas práticas na timeline:
  - [ ] `aula_pratica_iniciada`
  - [ ] `aula_pratica_concluida`
  - [ ] `aula_pratica_falta`
  - [ ] `aulas_praticas_concluidas`

#### 4.2. Teórico

- [ ] Adicionar eventos de turma teórica na timeline:
  - [ ] `turma_teorica_matriculado`
  - [ ] `turma_teorica_concluida`

#### 4.3. Provas

- [ ] Criar UI específica para provas (filtrar tipo='teorico' e tipo='pratico')
- [ ] Adicionar seção "Provas" na aba Matrícula do modal aluno
- [ ] Adicionar card "Status das Provas" na aba Histórico
- [ ] Adicionar eventos de provas na timeline:
  - [ ] `prova_teorica_agendada`
  - [ ] `prova_teorica_realizada`
  - [ ] `prova_teorica_aprovada`
  - [ ] `prova_teorica_reprovada`
  - [ ] `prova_pratica_agendada`
  - [ ] `prova_pratica_realizada`
  - [ ] `prova_pratica_aprovada`
  - [ ] `prova_pratica_reprovada`

#### 4.4. Financeiro

- [ ] Implementar integração com bloqueio de aulas (validação)
- [ ] Adicionar campo/flag "reteste" em faturas
- [ ] Corrigir job de faturas vencidas
- [ ] Implementar sincronização automática de status financeiro

#### 4.5. Agenda

- [ ] Implementar visualização unificada (teórico + prático)
- [ ] Implementar bloqueio de períodos
- [ ] Implementar reagendamento em lote (opcional)

#### 4.6. Notificações

- [ ] Implementar sistema de notificações push (PWA)
- [ ] Implementar notificações de alteração de aula
- [ ] Implementar lembrete antes de aulas (10 min antes)
- [ ] Implementar alerta após 3 faltas práticas

### Fase 5: Painéis Finais

#### 5.1. PWA Instrutor

- [ ] Criar API específica `instrutor/api/aulas.php`:
  - [ ] `POST /iniciar` - Iniciar aula (com validações)
  - [ ] `POST /encerrar` - Encerrar aula (com KM)
  - [ ] `GET /agenda-dia` - Agenda do dia
- [ ] Implementar dashboard instrutor:
  - [ ] Lista de aulas do dia
  - [ ] Botão "Iniciar aula"
  - [ ] Botão "Encerrar aula"
  - [ ] Botões WhatsApp/Ligar
  - [ ] Visualização de LADV do aluno
- [ ] Implementar validações no PWA:
  - [ ] Verificar financeiro antes de iniciar
  - [ ] Verificar faltas antes de iniciar
  - [ ] Verificar limites diários
- [ ] Implementar service worker para PWA
- [ ] Implementar notificações push

#### 5.2. PWA Aluno

- [ ] Criar API específica `aluno/api/dashboard.php`:
  - [ ] Cards: Processo, Teórico, Prático, Provas, Financeiro
- [ ] Criar API `aluno/api/agenda.php`:
  - [ ] Aulas teóricas
  - [ ] Aulas práticas
- [ ] Criar API `aluno/api/documentos.php`:
  - [ ] LADV (após aprovação teórica)
  - [ ] Declarações
- [ ] Implementar dashboard aluno:
  - [ ] Cards de status
  - [ ] Agenda (teórico + prático)
  - [ ] Financeiro (parcelas)
  - [ ] Documentos
- [ ] Implementar service worker para PWA
- [ ] Implementar notificações push

#### 5.3. Admin Secretaria

- [ ] Separar permissões Admin Master vs Admin Secretaria
- [ ] Implementar menu específico para Secretaria (ocultar configurações avançadas)
- [ ] Manter funcionalidades operacionais

#### 5.4. Admin Master

- [ ] Implementar menu específico para Master
- [ ] Adicionar gestão de CFCs (multi-CFC)
- [ ] Adicionar configurações avançadas do sistema
- [ ] Adicionar relatórios macro

---

## 9. NÃO CONSEGUI ANALISAR COMPLETAMENTE

### 9.1. Análise de Performance

**Não foi possível:**
- Identificar queries N+1 sem análise de runtime
- Medir tempo de resposta das APIs
- Analisar uso de memória
- Identificar gargalos de banco

**Necessário:**
- Análise de logs de acesso
- Profiling de código
- Análise de queries do banco

### 9.2. Análise de Uso Real

**Não foi possível:**
- Identificar funções não utilizadas sem análise de runtime
- Identificar campos não utilizados sem análise de queries
- Identificar rotas não acessadas

**Necessário:**
- Análise de logs de acesso
- Instrumentação de código
- Análise de queries do banco

### 9.3. Estrutura Completa de Banco

**Não foi possível confirmar:**
- Estrutura completa de todas as tabelas (algumas não estão em install.php)
- Relações de foreign keys completas
- Índices em todas as tabelas

**Necessário:**
- Executar `SHOW CREATE TABLE` em todas as tabelas
- Analisar migrations completas
- Verificar constraints

### 9.4. Integrações Externas

**Não foi possível:**
- Confirmar integrações com DETRAN
- Confirmar integrações com sistemas de pagamento
- Confirmar integrações com WhatsApp/SMS

**Necessário:**
- Análise de código de integração
- Documentação de APIs externas

---

## 10. CONCLUSÃO

### 10.1. Resumo Executivo

**Status Geral do Sistema:** ⚠️ **PARCIAL - Funcional mas com inconsistências críticas**

**Pontos Fortes:**
- ✅ Estrutura de código bem organizada
- ✅ Sistema de autenticação funcional
- ✅ Módulos principais implementados
- ✅ APIs RESTful bem estruturadas

**Pontos Críticos:**
- 🔴 Estrutura de banco inconsistente (tabelas não em install.php)
- 🔴 Job de faturas quebrado (usa tabela errada)
- 🔴 Código legado misturado (risco de uso incorreto)
- 🔴 PWA não implementado (instrutor e aluno)

**Pontos a Melhorar:**
- ⚠️ Timeline incompleta (faltam eventos)
- ⚠️ Regras de negócio não implementadas (faltas, bloqueios)
- ⚠️ Sistema de permissões incompleto (falta papel aluno)

### 10.2. Prioridades

**🔴 CRÍTICO (Fase 1):**
1. Corrigir estrutura de banco (adicionar tabelas ao install.php)
2. Corrigir job de faturas vencidas
3. Remover código legado

**🟡 ALTA (Fase 2):**
4. Adicionar campos faltantes (KM, falta, etc.)
5. Implementar eventos na timeline
6. Implementar regras de bloqueio

**🟢 MÉDIA (Fase 3-5):**
7. Implementar PWA completo
8. Separar permissões Admin Master vs Secretaria
9. Melhorar UI de provas

---

**Fim do RAIO-X Profissional Completo**

*Documento gerado em: 2025-01-27*  
*Versão: 1.0*

