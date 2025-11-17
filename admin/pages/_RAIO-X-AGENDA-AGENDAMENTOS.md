# 📊 RAIX-X COMPLETO: AGENDA / AGENDAMENTOS DO SISTEMA CFC

**Data:** 2025-01-28  
**Objetivo:** Mapeamento completo do sistema de agenda/agendamento sem alterações, para entendimento da arquitetura atual antes de desenhar a nova.

---

## 📁 1. LISTA DE ARQUIVOS RELACIONADOS A AGENDA/AGENDAMENTO

### 🔹 Páginas PHP (Telas)

| Arquivo | Caminho Completo | Descrição |
|---------|-----------------|-----------|
| **Agendamento (Global)** | `admin/pages/agendamento.php` | Página principal de agendamento global (todos os alunos/instrutores/veículos). Mostra calendário com aulas agendadas. |
| **Agendamento Moderno** | `admin/pages/agendamento-moderno.php` | Versão moderna/alternativa da página de agendamento global. |
| **Agendar Aula (Por Aluno)** | `admin/pages/agendar-aula.php` | Formulário para agendar aula focada em um aluno específico. Acessado via `?page=agendar-aula&aluno_id=X`. |
| **Listar Aulas** | `admin/pages/listar-aulas.php` | Listagem de todas as aulas (global). Acessado via `?page=listar-aulas` ou `pages/listar-aulas.php`. |
| **Editar Aula** | `admin/pages/editar-aula.php` | Formulário para editar aula existente. |
| **Agendar Manutenção** | `admin/pages/agendar-manutencao.php` | Agendamento de manutenção de veículos (não é aula, mas usa estrutura de agenda). |

### 🔹 APIs PHP

| Arquivo | Caminho Completo | Descrição | Métodos |
|---------|-----------------|-----------|---------|
| **Agendamento (Principal)** | `admin/api/agendamento.php` | API principal para criar, atualizar, buscar e cancelar aulas. | GET, POST, PUT, DELETE |
| **Disponibilidade** | `admin/api/disponibilidade.php` | API para buscar slots de horários disponíveis para agendamento. | GET |
| **Aluno Agenda** | `admin/api/aluno-agenda.php` | API para buscar agenda consolidada de um aluno (práticas + teóricas). | GET |
| **Agendamento Detalhes** | `admin/api/agendamento-detalhes.php` | API para buscar detalhes de um agendamento específico. | GET |
| **Agendamento Detalhes Fallback** | `admin/api/agendamento-detalhes-fallback.php` | Versão alternativa/fallback da API de detalhes. | GET |
| **Agendamentos por IDs** | `admin/api/agendamentos-por-ids.php` | API para buscar múltiplos agendamentos por lista de IDs. | GET |
| **Turma Agendamento** | `admin/api/turma-agendamento.php` | API para agendamento de aulas teóricas em turmas. | GET, POST |
| **Listar Agendamentos Turma** | `admin/api/listar-agendamentos-turma.php` | API para listar agendamentos de uma turma teórica específica. | GET |
| **Disciplina Agendamentos** | `admin/api/disciplina-agendamentos.php` | API para buscar agendamentos de uma disciplina específica em uma turma. | GET |
| **Buscar Aula** | `admin/api/buscar-aula.php` | API para buscar uma aula específica por ID. | GET |
| **Atualizar Aula** | `admin/api/atualizar-aula.php` | API para atualizar dados de uma aula. | PUT, POST |
| **Cancelar Aula** | `admin/api/cancelar-aula.php` | API para cancelar uma aula. | POST, DELETE |
| **Verificar Aula Específica** | `admin/api/verificar-aula-especifica.php` | API para verificar se uma aula específica existe/está disponível. | GET |
| **Verificar Disponibilidade** | `admin/api/verificar-disponibilidade.php` | API para verificar disponibilidade de instrutor/veículo em um horário. | GET |
| **Exportar Agendamentos** | `admin/api/exportar-agendamentos.php` | API para exportar lista de agendamentos (CSV, Excel, etc.). | GET |

### 🔹 JavaScript (Frontend)

| Arquivo | Caminho Completo | Descrição |
|---------|-----------------|-----------|
| **Agendamento** | `admin/assets/js/agendamento.js` | JavaScript principal para página de agendamento global. Gerencia calendário, eventos, criação/edição de aulas. |
| **Agendamento Moderno** | `admin/pages/agendamento-moderno.js` | JavaScript específico da versão moderna da página de agendamento. |

### 🔹 CSS (Estilos)

| Arquivo | Caminho Completo | Descrição |
|---------|-----------------|-----------|
| **Agendamento** | `admin/assets/css/agendamento.css` | Estilos da página de agendamento global. |
| **Agendamento Moderno** | `admin/assets/css/agendamento-moderno.css` | Estilos da versão moderna da página de agendamento. |

### 🔹 Controllers/Classes PHP

| Arquivo | Caminho Completo | Descrição |
|---------|-----------------|-----------|
| **AgendamentoController** | `includes/controllers/AgendamentoController.php` | Classe controladora para lógica de agendamento. Gerencia criação, validação, verificação de conflitos. |
| **AgendamentoGuards** | `includes/guards/AgendamentoGuards.php` | Classe para validações/guards de agendamento (conflitos, regras de negócio). |
| **AgendamentoPermissions** | `includes/guards/AgendamentoPermissions.php` | Classe para verificar permissões de agendamento. |
| **AgendamentoAuditoria** | `includes/guards/AgendamentoAuditoria.php` | Classe para auditoria de ações de agendamento. |
| **TurmaTeoricaManager** | `admin/includes/TurmaTeoricaManager.php` | Classe para gerenciar turmas teóricas, incluindo agendamento de aulas teóricas. |
| **Controle Limite Aulas** | `admin/includes/controle_limite_aulas.php` | Controle de limites de aulas por aluno/instrutor. |

### 🔹 Integrações no Sistema Principal

| Arquivo | Caminho Completo | Onde aparece a agenda |
|---------|-----------------|----------------------|
| **Index.php (Switch)** | `admin/index.php` | Rotas: `?page=agendamento`, `?page=agendar-aula`, `?page=listar-aulas` |
| **Modal Aluno** | `admin/pages/alunos.php` | Modal do aluno mostra agenda (via API `aluno-agenda.php`) |

---

## 🗄️ 2. ESTRUTURA DAS TABELAS ENVOLVIDAS

### 📊 **Tabela: `aulas`** (Aulas Práticas e Teóricas Individuais)

**Localização:** Criada em `install.php` (linhas 88-103)

**Campos Principais:**

| Campo | Tipo | Chave | Descrição |
|-------|------|-------|-----------|
| `id` | INT AUTO_INCREMENT | PK | Identificador único da aula |
| `aluno_id` | INT NOT NULL | FK → `alunos.id` | Referência ao aluno |
| `instrutor_id` | INT NOT NULL | FK → `instrutores.id` | Referência ao instrutor |
| `cfc_id` | INT NOT NULL | FK → `cfcs.id` | Referência ao CFC |
| `veiculo_id` | INT NULL | FK → `veiculos.id` | Referência ao veículo (obrigatório para práticas, NULL para teóricas) |
| `tipo_aula` | ENUM('teorica', 'pratica') | - | Tipo da aula |
| `data_aula` | DATE NOT NULL | - | Data da aula |
| `hora_inicio` | TIME NOT NULL | - | Hora de início |
| `hora_fim` | TIME NOT NULL | - | Hora de término |
| `status` | ENUM('agendada', 'em_andamento', 'concluida', 'cancelada') | - | Status da aula |
| `observacoes` | TEXT | - | Observações sobre a aula |
| `criado_em` | TIMESTAMP | - | Data/hora de criação |

**Relações Importantes:**
- `aulas.aluno_id` → `alunos.id` (Aluno da aula)
- `aulas.instrutor_id` → `instrutores.id` (Instrutor responsável)
- `aulas.veiculo_id` → `veiculos.id` (Veículo usado - apenas práticas)
- `aulas.cfc_id` → `cfcs.id` (CFC do agendamento)

**Uso na Agenda:**
- **Aulas Práticas:** Todas as aulas práticas individuais são armazenadas aqui
- **Aulas Teóricas Individuais:** Algumas aulas teóricas podem ser agendadas individualmente (fora de turma)
- **Calendário Global:** Esta tabela é usada para popular o calendário global em `agendamento.php`

---

### 📊 **Tabela: `turma_aulas_agendadas`** (Aulas Teóricas em Turmas)

**Localização:** Criada em `admin/migrations/001-create-turmas-teoricas-structure.sql` (linhas 126-159)

**Campos Principais:**

| Campo | Tipo | Chave | Descrição |
|-------|------|-------|-----------|
| `id` | INT AUTO_INCREMENT | PK | Identificador único da aula agendada |
| `turma_id` | INT NOT NULL | FK → `turmas_teoricas.id` | Referência à turma teórica |
| `disciplina` | ENUM('legislacao_transito', 'primeiros_socorros', 'direcao_defensiva', 'meio_ambiente_cidadania', 'mecanica_basica') | - | Disciplina da aula |
| `nome_aula` | VARCHAR(200) | - | Nome/título da aula |
| `instrutor_id` | INT NOT NULL | FK → `instrutores.id` | Instrutor da aula |
| `sala_id` | INT NOT NULL | FK → `salas.id` | Sala onde ocorrerá a aula |
| `data_aula` | DATE NOT NULL | - | Data da aula |
| `hora_inicio` | TIME NOT NULL | - | Hora de início |
| `hora_fim` | TIME NOT NULL | - | Hora de término |
| `duracao_minutos` | INT DEFAULT 50 | - | Duração da aula em minutos |
| `ordem_disciplina` | INT DEFAULT 1 | - | Ordem da aula dentro da disciplina |
| `ordem_global` | INT DEFAULT 1 | - | Ordem global da aula na turma |
| `status` | ENUM('agendada', 'realizada', 'cancelada') | - | Status da aula |
| `observacoes` | TEXT | - | Observações |
| `criado_em` | TIMESTAMP | - | Data/hora de criação |
| `atualizado_em` | TIMESTAMP | - | Data/hora de atualização |

**Índices para Detecção de Conflitos:**
- `idx_instrutor_conflitos` → `(instrutor_id, data_aula, hora_inicio, hora_fim)` - Para verificar conflitos de instrutor
- `idx_sala_conflitos` → `(sala_id, data_aula, hora_inicio, hora_fim)` - Para verificar conflitos de sala
- `idx_turma_disciplina` → `(turma_id, disciplina, ordem_disciplina)` - Para ordenar aulas por disciplina
- `idx_cronologico` → `(turma_id, data_aula, hora_inicio)` - Para ordenar cronologicamente

**Relações Importantes:**
- `turma_aulas_agendadas.turma_id` → `turmas_teoricas.id` (Turma à qual a aula pertence)
- `turma_aulas_agendadas.instrutor_id` → `instrutores.id` (Instrutor responsável)
- `turma_aulas_agendadas.sala_id` → `salas.id` (Sala da aula)

**Uso na Agenda:**
- **Aulas Teóricas em Turma:** Todas as aulas teóricas agendadas para uma turma são armazenadas aqui
- **Agendamento por Turma:** Aulas são agendadas em lote quando uma turma é criada/configurada
- **Presenças:** Alunos da turma têm suas presenças registradas para estas aulas (via `turma_presencas`)

---

### 📊 **Tabela: `turma_matriculas`** (Matrículas de Alunos em Turmas)

**Localização:** Criada em `admin/migrations/001-create-turmas-teoricas-structure.sql`

**Campos Principais (relevantes para agenda):**

| Campo | Tipo | Chave | Descrição |
|-------|------|-------|-----------|
| `id` | INT AUTO_INCREMENT | PK | Identificador único da matrícula |
| `turma_id` | INT NOT NULL | FK → `turmas_teoricas.id` | Referência à turma |
| `aluno_id` | INT NOT NULL | FK → `alunos.id` | Referência ao aluno |
| `data_matricula` | DATE | - | Data da matrícula |

**Uso na Agenda:**
- **Agenda do Aluno:** Usada para determinar quais aulas teóricas (`turma_aulas_agendadas`) o aluno deve ter acesso
- **API `aluno-agenda.php`:** Busca todas as `turma_aulas_agendadas` das turmas onde o aluno está matriculado

---

### 📊 **Tabelas Relacionadas (Suporte à Agenda)**

| Tabela | Campos Relevantes | Uso na Agenda |
|--------|------------------|---------------|
| **`alunos`** | `id`, `nome`, `cpf`, `categoria_cnh`, `status` | Identificação do aluno nos agendamentos |
| **`instrutores`** | `id`, `nome`, `categoria_habilitacao`, `ativo` | Identificação do instrutor nos agendamentos |
| **`veiculos`** | `id`, `placa`, `modelo`, `marca`, `categoria_cnh`, `ativo` | Identificação do veículo em aulas práticas |
| **`salas`** | `id`, `nome`, `capacidade` | Identificação da sala em aulas teóricas |
| **`turmas_teoricas`** | `id`, `nome`, `status`, `data_inicio`, `data_fim` | Contexto das aulas teóricas agendadas |

---

## 🔄 3. FLUXO ATUAL DE AGENDAMENTO

### 📚 **3.1. AULAS TEÓRICAS**

#### **Como é Agendado:**

1. **Tela:** `admin/pages/turmas-teoricas-detalhes.php` ou `admin/pages/turmas-teoricas-step4.php`
2. **Processo:**
   - O usuário cria/configura uma turma teórica
   - Durante a configuração da turma, as aulas são agendadas em lote
   - Usa `TurmaTeoricaManager::agendarAula()` (`admin/includes/TurmaTeoricaManager.php` linha 402)

#### **O que é Salvo no Banco:**

- **Tabela:** `turma_aulas_agendadas`
- **Campos principais salvos:**
  - `turma_id` → ID da turma
  - `disciplina` → Disciplina da aula (enum: legislacao_transito, primeiros_socorros, etc.)
  - `instrutor_id` → Instrutor responsável
  - `sala_id` → Sala onde ocorrerá
  - `data_aula`, `hora_inicio`, `hora_fim` → Horário da aula
  - `ordem_disciplina` → Ordem da aula dentro da disciplina
  - `ordem_global` → Ordem cronológica global na turma

#### **Vínculos:**

- ✅ **Turma:** Sim - aulas são vinculadas a uma turma específica (`turma_id`)
- ✅ **Sala:** Sim - cada aula teórica deve ter uma sala (`sala_id`)
- ✅ **Instrutor:** Sim - cada aula teórica deve ter um instrutor (`instrutor_id`)
- ✅ **Aluno:** Indireto - alunos são vinculados via `turma_matriculas`, não diretamente na aula

#### **Verificação de Conflitos:**

- **Instrutor:** Verifica se o instrutor já tem aula agendada no mesmo horário (`idx_instrutor_conflitos`)
- **Sala:** Verifica se a sala já está ocupada no mesmo horário (`idx_sala_conflitos`)
- **Implementação:** `TurmaTeoricaManager::verificarConflitosHorario()` (linha 425)

---

### 🚗 **3.2. AULAS PRÁTICAS**

#### **Como é Agendado:**

1. **Tela Principal (Global):** `admin/pages/agendamento.php`
   - Calendário global mostrando todas as aulas
   - Formulário para criar nova aula
   - Filtros por aluno, instrutor, veículo, data

2. **Tela Por Aluno:** `admin/pages/agendar-aula.php`
   - Acessado via `?page=agendar-aula&aluno_id=X`
   - Formulário focado no aluno específico
   - Carrega instrutores e veículos elegíveis para a categoria do aluno

#### **O que é Salvo no Banco:**

- **Tabela:** `aulas`
- **Campos principais salvos:**
  - `aluno_id` → Aluno da aula
  - `instrutor_id` → Instrutor responsável
  - `veiculo_id` → Veículo usado (obrigatório para práticas)
  - `tipo_aula` → 'pratica'
  - `data_aula`, `hora_inicio`, `hora_fim` → Horário da aula
  - `status` → 'agendada', 'em_andamento', 'concluida', 'cancelada'

#### **Como o Sistema Garante que Não Há Conflito:**

- ✅ **Instrutor:** Verifica se já existe aula com mesmo instrutor no mesmo horário
  - Implementação: `AgendamentoGuards::verificarConflitoInstrutor()` (`includes/guards/AgendamentoGuards.php` linha 271)
  - Query verifica sobreposição de horários: `(hora_inicio <= ? AND hora_fim > ?) OR (hora_inicio < ? AND hora_fim >= ?) OR (hora_inicio >= ? AND hora_fim <= ?)`

- ✅ **Veículo:** Verifica se já existe aula com mesmo veículo no mesmo horário
  - Implementação: `AgendamentoGuards::verificarConflitoVeiculo()` (`includes/guards/AgendamentoGuards.php` linha 305)
  - Mesma lógica de sobreposição de horários

- ✅ **Aluno:** Verifica se o aluno já tem aula no mesmo horário (opcional, mas implementado)
  - Implementação: `AgendamentoGuards::verificarConflitoAluno()` (linha ~240)
  - Previne que aluno tenha duas aulas simultâneas

- ⚠️ **Limitação Observada:** 
  - Não há verificação de limite de aulas por dia (ex: máximo 3 aulas/dia por instrutor)
  - Não há verificação de intervalo mínimo entre aulas (ex: 30 minutos entre aulas do mesmo instrutor)

#### **API de Disponibilidade:**

- **Endpoint:** `admin/api/disponibilidade.php`
- **Funcionalidade:** Busca slots de horários disponíveis para um aluno específico
- **Parâmetros:**
  - `aluno_id` → ID do aluno
  - `categoria` → Categoria CNH (opcional, usa categoria do aluno se não informada)
  - `intervalo` → Tipo de agendamento: 'unica', 'duas', 'tres' (1, 2 ou 3 aulas consecutivas)
  - `dias` → Janela de dias para buscar (padrão: 14 dias, máximo: 21)
- **Lógica:**
  - Carrega instrutores e veículos elegíveis para a categoria
  - Gera slots baseados em horários fixos: 08:00, 08:50, 09:40, etc.
  - Verifica conflitos para cada slot (instrutor e veículo)
  - Retorna apenas slots disponíveis

#### **Cálculo de Horários:**

- **Função:** `calcularHorariosAulas()` em `admin/api/agendamento.php` (linha 116)
- **Tipos de agendamento:**
  - `unica` → 1 aula de 50 minutos
  - `duas` → 2 aulas consecutivas (50 + 50 = 100 minutos)
  - `tres` → 3 aulas consecutivas (50 + 50 + 50 = 150 minutos)
- **Posição do intervalo:** 'antes' ou 'depois' (quando há intervalos entre aulas)

---

### 📅 **3.3. AGENDA GLOBAL**

#### **Arquivo da Tela:**

- **Principal:** `admin/pages/agendamento.php`
- **Alternativa:** `admin/pages/agendamento-moderno.php`

#### **Como os Eventos são Carregados:**

1. **Backend (PHP):**
   - Linha 59-72: Query SQL que busca aulas dos últimos 6 meses até próximos 6 meses
   - JOINs com `alunos`, `instrutores`, `usuarios`, `veiculos`
   - Ordenação por `data_aula`, `hora_inicio`

2. **Frontend (JavaScript):**
   - `admin/assets/js/agendamento.js` gerencia o calendário
   - Eventos podem ser carregados via AJAX usando `admin/api/agendamento.php` (método GET)

#### **Tipos de Evento que Aparecem:**

- ✅ **Aulas Práticas:** Todas as aulas da tabela `aulas` com `tipo_aula = 'pratica'`
- ✅ **Aulas Teóricas Individuais:** Aulas da tabela `aulas` com `tipo_aula = 'teorica'` (se existirem)
- ⚠️ **Aulas Teóricas em Turma:** **NÃO aparecem diretamente na agenda global** (só via turma específica)
- ⚠️ **Exames/Provas:** Não aparecem na agenda de aulas (são tratados separadamente em `exames.php`)

#### **Funcionalidades:**

- Visualização em calendário (semana, mês)
- Criação de nova aula prática
- Edição de aula existente
- Cancelamento de aula
- Filtros por aluno, instrutor, veículo, data

---

## 🔍 4. DIFERENÇA: GLOBAL vs POR ALUNO

### 🌐 **Agenda Global**

**Telas:**
- `admin/pages/agendamento.php` → Calendário global
- `admin/pages/listar-aulas.php` → Listagem de todas as aulas
- `admin/pages/agendamento-moderno.php` → Versão alternativa

**O que Mostra:**
- ✅ Todas as aulas práticas de todos os alunos
- ✅ Todas as aulas teóricas individuais (se existirem)
- ✅ Todas as aulas de todos os instrutores
- ✅ Todas as aulas de todos os veículos
- ❌ Não mostra aulas teóricas em turma (precisa acessar a turma específica)

**Fonte de Dados:**
- Tabela `aulas` (todas as linhas, filtradas por data)
- Query em `agendamento.php` linha 59-72

---

### 👤 **Agenda Por Aluno**

**Telas:**
- `admin/pages/agendar-aula.php?aluno_id=X` → Formulário para agendar aula focada no aluno
- **Modal do Aluno** em `admin/pages/alunos.php` → Mostra resumo da agenda do aluno

**API:**
- `admin/api/aluno-agenda.php?aluno_id=X` → Retorna agenda consolidada do aluno

**O que Mostra:**

1. **Aulas Práticas:**
   - Todas as aulas práticas do aluno (tabela `aulas` com `aluno_id = X`)
   - Filtradas por `status != 'cancelada'`
   - Ordenadas por `data_aula ASC, hora_inicio ASC`

2. **Aulas Teóricas:**
   - Busca via `turma_matriculas` → encontra todas as turmas do aluno
   - Para cada turma, busca `turma_aulas_agendadas` vinculadas
   - Retorna linha do tempo unificada de práticas + teóricas

3. **Resumo:**
   - Total de aulas práticas
   - Total de aulas práticas concluídas
   - Progresso percentual
   - Próxima aula prática

**Linha do Tempo:**
- `aluno-agenda.php` retorna `timeline` unificada (linhas 139-185)
- Combina aulas práticas e teóricas em um único array
- Ordena por `data_hora` (data + hora_inicio)

---

### 👨‍🏫 **Agenda Por Instrutor**

**Status:** ❌ **NÃO IMPLEMENTADA COMO TELA DEDICADA**

- Instrutor pode ser usado como filtro na agenda global
- Não há tela específica mostrando agenda do instrutor
- Query SQL permite filtrar por `instrutor_id`, mas não há UI dedicada

---

### 🚗 **Agenda Por Veículo**

**Status:** ❌ **NÃO IMPLEMENTADA COMO TELA DEDICADA**

- Veículo pode ser usado como filtro na agenda global
- Não há tela específica mostrando agenda do veículo
- Query SQL permite filtrar por `veiculo_id`, mas não há UI dedicada

---

## ⚠️ 5. LIMITAÇÕES E OBSERVAÇÕES

### ❌ **Limitações Críticas**

1. **Verificação de Limites de Aulas por Dia:**
   - ⚠️ Não há verificação de máximo de aulas/dia por instrutor (ex: máximo 3 aulas/dia)
   - ⚠️ Não há verificação de máximo de aulas/dia por veículo
   - ⚠️ Não há verificação de máximo de aulas/dia por aluno

2. **Verificação de Intervalo Mínimo:**
   - ⚠️ Não há verificação de intervalo mínimo entre aulas do mesmo instrutor (ex: 30 minutos)
   - ⚠️ Não há verificação de intervalo mínimo entre aulas do mesmo veículo
   - ⚠️ Sistema permite agendar aulas consecutivas sem intervalo

3. **Integração entre Aulas Práticas e Teóricas:**
   - ⚠️ Aulas teóricas em turma (`turma_aulas_agendadas`) não aparecem na agenda global
   - ⚠️ Só aparecem quando acessadas via turma específica ou via agenda do aluno
   - ⚠️ Não há visão unificada de todas as aulas (práticas + teóricas) na agenda global

4. **Verificação de Bloqueios:**
   - ⚠️ Não há verificação automática de bloqueio por inadimplência ao agendar
   - ⚠️ Não há verificação automática de bloqueio por faltas ao agendar
   - ⚠️ Não há verificação de LADV antes de agendar aula prática

5. **Regras de Negócio:**
   - ⚠️ Não há validação de sequência lógica (ex: aluno só pode agendar prática após teórica concluída)
   - ⚠️ Não há validação de carga horária mínima cumprida antes de permitir prova

### ⚠️ **Observações Técnicas**

1. **Duplicação de Código:**
   - Existem múltiplas implementações de verificação de conflitos:
     - `AgendamentoGuards::verificarConflitos()` (`includes/guards/AgendamentoGuards.php`)
     - `AgendamentoController::verificarDisponibilidade()` (`includes/controllers/AgendamentoController.php`)
     - `verificarDisponibilidadeInstrutor()` em `admin/api/verificar-disponibilidade.php`
   - Lógica similar espalhada em diferentes arquivos

2. **Estrutura de Dados:**
   - Aulas práticas e teóricas individuais usam a mesma tabela (`aulas`)
   - Aulas teóricas em turma usam tabela separada (`turma_aulas_agendadas`)
   - Isso dificulta consultas unificadas e pode causar inconsistências

3. **Agenda do Aluno:**
   - A agenda do aluno (`aluno-agenda.php`) **apenas lista**, não permite agendar diretamente
   - Para agendar, é necessário acessar `agendar-aula.php` separadamente
   - Não há integração direta entre visualização e ação

4. **Status de Aulas:**
   - Aulas práticas usam: `'agendada', 'em_andamento', 'concluida', 'cancelada'`
   - Aulas teóricas em turma usam: `'agendada', 'realizada', 'cancelada'`
   - Inconsistência de nomenclatura (`concluida` vs `realizada`)

5. **Veículo Obrigatório:**
   - Para aulas práticas, `veiculo_id` é obrigatório
   - Para aulas teóricas, `veiculo_id` é NULL
   - Mas a validação ocorre apenas no backend, não há feedback claro no frontend antes do envio

---

## 📊 6. RESUMO EXECUTIVO

### ✅ **O que Funciona:**
- Agendamento de aulas práticas individuais funciona
- Agendamento de aulas teóricas em turma funciona
- Verificação de conflitos de horário (instrutor, veículo, aluno) funciona
- Agenda global mostra aulas práticas
- Agenda do aluno consolida práticas + teóricas

### ❌ **O que Falta ou Precisa Melhorar:**
- Verificação de limites de aulas/dia
- Verificação de intervalo mínimo entre aulas
- Integração unificada de práticas + teóricas na agenda global
- Verificação automática de bloqueios (inadimplência, faltas, LADV)
- Validação de regras de negócio (sequência lógica, carga horária)
- UI dedicada para agenda por instrutor
- UI dedicada para agenda por veículo

---

## 📝 7. ANEXOS

### 📄 **Queries SQL Principais**

**Buscar Aulas para Calendário Global:**
```sql
SELECT a.*, 
       al.nome as aluno_nome,
       COALESCE(u.nome, i.nome) as instrutor_nome,
       v.placa, v.modelo, v.marca
FROM aulas a
JOIN alunos al ON a.aluno_id = al.id
JOIN instrutores i ON a.instrutor_id = i.id
LEFT JOIN usuarios u ON i.usuario_id = u.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.data_aula >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
  AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
ORDER BY a.data_aula, a.hora_inicio
```

**Verificar Conflito de Instrutor:**
```sql
SELECT COUNT(*) as total FROM aulas 
WHERE instrutor_id = ? 
AND data_aula = ? 
AND status != 'cancelada'
AND ((hora_inicio <= ? AND hora_fim > ?) 
     OR (hora_inicio < ? AND hora_fim >= ?)
     OR (hora_inicio >= ? AND hora_fim <= ?))
```

**Buscar Agenda do Aluno (Práticas + Teóricas):**
- Práticas: `SELECT * FROM aulas WHERE aluno_id = ? AND status != 'cancelada'`
- Teóricas: `SELECT * FROM turma_aulas_agendadas taa JOIN turma_matriculas tm ON taa.turma_id = tm.turma_id WHERE tm.aluno_id = ?`

---

**Fim do Raio-X**

