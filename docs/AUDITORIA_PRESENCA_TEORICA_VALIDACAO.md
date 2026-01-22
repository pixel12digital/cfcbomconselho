# 🔍 AUDITORIA COMPLETA: PRESENÇA TEÓRICA
## Sistema CFC Bom Conselho - Validação e Diagnóstico Completo

**Data:** 2025-11-25  
**Objetivo:** Auditoria completa de tudo relacionado à presença teórica, com foco em ADMIN, mapeando o que existe para instrutor e aluno, sem implementar correções ainda.

---

## 📋 ÍNDICE

1. [Resumo Executivo](#1-resumo-executivo)
2. [Mapa Técnico](#2-mapa-técnico)
3. [Admin - Situação Atual](#3-admin---situação-atual)
4. [Instrutor - Situação Atual](#4-instrutor---situação-atual)
5. [Aluno - Situação Atual](#5-aluno---situação-atual)
6. [Checklist de Ajustes](#6-checklist-de-ajustes)
7. [Próximos Passos Sugeridos](#7-próximos-passos-sugeridos)

---

## 1. RESUMO EXECUTIVO

### 1.1. Status Geral da Presença Teórica

**Admin/Secretaria:** ✅ **FUNCIONAL E COMPLETO**
- Interface de chamada operacional
- Registro de presenças funcionando
- Cálculo automático de frequência
- Histórico do aluno completo
- APIs robustas e seguras

**Instrutor:** ⚠️ **PARCIALMENTE IMPLEMENTADO**
- Pode acessar chamada de suas turmas
- Não tem acesso fácil às turmas no dashboard
- Não vê aulas teóricas na lista de aulas

**Aluno:** ✅ **IMPLEMENTADO (Fase 1)**
- Pode ver suas presenças teóricas
- Pode ver frequência percentual
- Pode ver histórico completo
- APIs seguras (só vê seus próprios dados)

### 1.2. Pontos Fortes

1. **Backend Robusto:**
   - APIs bem estruturadas com validações completas
   - Recalculo automático de frequência após cada alteração
   - Segurança implementada (aluno só vê seus dados)
   - Triggers no banco para atualização automática de contadores

2. **Regras de Negócio:**
   - Validação de presença para prova teórica (75% mínimo)
   - Validação de exames para matrícula
   - Regras de edição por perfil (instrutor não edita turmas concluídas)
   - Cálculo correto de frequência (apenas aulas válidas)

3. **Interface Admin:**
   - Chamada funcional e intuitiva
   - Histórico completo do aluno
   - Detalhes da turma bem organizados
   - Estatísticas em tempo real

### 1.3. Riscos Principais

1. **Segurança (Baixo Risco):**
   - ✅ APIs já validam permissões corretamente
   - ✅ Aluno não consegue acessar dados de outros alunos
   - ⚠️ Instrutor pode acessar chamada de qualquer turma se souber a URL (mas validação de `instrutor_id` bloqueia edição)

2. **Consistência de Dados:**
   - ✅ Frequência sempre atualizada automaticamente
   - ✅ Mesma lógica usada em todas as telas
   - ⚠️ Não há histórico de alterações (auditoria de quem alterou)

3. **UX/Fluxo:**
   - ⚠️ Instrutor não tem acesso fácil às turmas teóricas
   - ⚠️ Falta botões de ação em lote na chamada (marcar todos presentes/ausentes)
   - ⚠️ Não há relatórios exportáveis

---

## 2. MAPA TÉCNICO

### 2.1. Tabelas Envolvidas

#### **`turmas_teoricas`**
**Campos principais:**
- `id` (PK)
- `nome`, `instrutor_id`, `cfc_id`
- `data_inicio`, `data_fim`
- `status` (criando, agendando, completa, ativa, concluida, cancelada)
- `carga_horaria_total`, `carga_horaria_agendada`, `carga_horaria_realizada`
- `max_alunos`, `alunos_matriculados`

**Relacionamentos:**
- Uma turma tem várias aulas (`turma_aulas_agendadas`)
- Uma turma tem várias matrículas (`turma_matriculas`)
- Uma turma tem várias presenças (`turma_presencas`)

#### **`turma_aulas_agendadas`**
**Campos principais:**
- `id` (PK) - Referenciado como `aula_id` em `turma_presencas`
- `turma_id` (FK)
- `disciplina` (ENUM)
- `nome_aula`, `data_aula`, `hora_inicio`, `hora_fim`
- `status` (agendada, realizada, cancelada)
- `ordem_global`, `ordem_disciplina`

**Relacionamentos:**
- Uma aula pertence a uma turma
- Uma aula tem várias presenças (`turma_presencas`)

#### **`turma_matriculas`**
**Campos principais:**
- `id` (PK)
- `turma_id` (FK), `aluno_id` (FK)
- `status` (matriculado, cursando, concluido, evadido, transferido)
- **`frequencia_percentual`** (DECIMAL(5,2)) ⭐ **CAMPO CRÍTICO**
- `data_matricula`, `exames_validados_em`

**Relacionamentos:**
- UNIQUE KEY: `(turma_id, aluno_id)` - um aluno só pode estar matriculado uma vez por turma
- Campo `frequencia_percentual` atualizado automaticamente via `TurmaTeoricaManager::recalcularFrequenciaAluno()`

#### **`turma_presencas`**
**Campos principais:**
- `id` (PK)
- `turma_id` (FK), `aula_id` (FK → `turma_aulas_agendadas.id`), `aluno_id` (FK)
- `presente` (BOOLEAN)
- `justificativa` (TEXT, NULL)
- `registrado_por` (FK → `usuarios.id`)
- `registrado_em` (TIMESTAMP)

**Relacionamentos:**
- UNIQUE KEY: `(aula_id, aluno_id)` - um aluno só pode ter uma presença por aula

### 2.2. APIs Envolvidas

#### **`admin/api/turma-presencas.php`**
**Métodos:** GET, POST, PUT, DELETE

**Funcionalidades:**
- **GET:** Buscar presenças (aula específica, aluno específico, turma completa)
- **POST:** Marcar presença individual ou em lote
- **PUT:** Atualizar presença existente
- **DELETE:** Excluir presença

**Permissões:**
- Admin/Secretaria: Acesso total
- Instrutor: Apenas suas turmas (validação via `instrutor_id`)
- Aluno: Apenas leitura (GET) de suas próprias presenças

**Validações:**
- ✅ Aluno deve estar matriculado na turma
- ✅ Não permite duplicar presença (UNIQUE KEY)
- ✅ Instrutor só pode editar suas próprias turmas
- ✅ Não permite editar presenças de turmas canceladas
- ✅ Instrutor não pode editar presenças de turmas concluídas
- ✅ Não permite editar presenças de aulas canceladas

**Recalculo Automático:**
- ✅ Após criar/atualizar/excluir presença, chama `TurmaTeoricaManager::recalcularFrequenciaAluno()`
- ✅ Atualiza `turma_matriculas.frequencia_percentual` automaticamente

**Status:** ✅ **FUNCIONAL E COMPLETO**

#### **`admin/api/turma-frequencia.php`**
**Métodos:** GET apenas

**Funcionalidades:**
- **GET:** Calcular frequência de um aluno específico ou de toda a turma

**Permissões:**
- Admin/Secretaria: Acesso total
- Instrutor: Acesso total (pode ver frequência de qualquer turma)
- Aluno: Apenas sua própria frequência

**Cálculo:**
- Fórmula: `(aulas_presentes / total_aulas_programadas) * 100`
- Considera apenas aulas com status `'agendada'` ou `'realizada'` (não conta canceladas)
- Frequência mínima padrão: 75% (se não configurada na turma)

**Status:** ✅ **FUNCIONAL**

### 2.3. Telas por Perfil

#### **Admin/Secretaria**

**1. Lista de Turmas Teóricas**
- **Arquivo:** `admin/pages/turmas-teoricas-lista.php`
- **Rota:** `index.php?page=turmas-teoricas`
- **Funcionalidades:** Lista todas as turmas teóricas
- **Status:** ✅ Funcional

**2. Detalhes da Turma**
- **Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`
- **Rota:** `index.php?page=turmas-teoricas&acao=detalhes&turma_id={id}`
- **Funcionalidades:**
  - Aba "Alunos Matriculados" com frequência percentual
  - Aba "Calendário de Aulas" com link para chamada
- **Status:** ✅ Funcional

**3. Interface de Chamada**
- **Arquivo:** `admin/pages/turma-chamada.php`
- **Rota:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`
- **Funcionalidades:**
  - Lista alunos matriculados
  - Botões "Presente" e "Ausente" para cada aluno
  - Exibe frequência percentual de cada aluno
  - Estatísticas da turma (total presentes, ausentes, sem registro)
  - Permite adicionar justificativa/observação
  - **Ações em lote:** Botões "Marcar todos presentes" e "Marcar todos ausentes" (linhas 533-534)
- **Status:** ✅ **FUNCIONAL E COMPLETO**

**4. Histórico do Aluno**
- **Arquivo:** `admin/pages/historico-aluno.php`
- **Rota:** `index.php?page=historico-aluno&id={aluno_id}`
- **Funcionalidades:**
  - Bloco "Presença Teórica" completo
  - Lista turmas teóricas do aluno
  - Exibe frequência percentual por turma
  - Tabela de aulas com status de presença (Presente/Ausente/Não registrado)
  - Exibe justificativas (se houver)
- **Status:** ✅ **FUNCIONAL**

#### **Instrutor**

**1. Dashboard do Instrutor**
- **Arquivo:** `instrutor/dashboard.php`
- **Funcionalidades:** Exibe apenas aulas práticas do dia
- **Status:** ⚠️ **NÃO mostra turmas teóricas**

**2. Lista de Aulas**
- **Arquivo:** `instrutor/aulas.php`
- **Funcionalidades:** Lista apenas aulas práticas
- **Status:** ⚠️ **NÃO lista aulas teóricas**

**3. Interface de Chamada (Compartilhada)**
- **Arquivo:** `admin/pages/turma-chamada.php`
- **Rota:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`
- **Permissões:**
  - Instrutor pode acessar se `turma.instrutor_id == userId`
  - Se não for o instrutor da turma, `$canEdit = false` (apenas visualização)
- **Status:** ✅ **FUNCIONAL** (compartilhado com Admin/Secretaria)

#### **Aluno**

**1. Dashboard do Aluno**
- **Arquivo:** `aluno/dashboard.php`
- **Funcionalidades:** Exibe progresso geral, timeline de etapas
- **Status:** ✅ Funcional (não exibe presenças teóricas diretamente, mas tem link)

**2. Presenças Teóricas**
- **Arquivo:** `aluno/presencas-teoricas.php`
- **Funcionalidades:**
  - Lista turmas teóricas do aluno
  - Exibe frequência percentual por turma
  - Tabela de aulas com status de presença
  - Exibe justificativas (se houver)
- **Status:** ✅ **FUNCIONAL** (implementado na Fase 1)

**3. Histórico do Aluno**
- **Arquivo:** `aluno/historico.php`
- **Funcionalidades:**
  - Bloco "Presença Teórica" completo (reaproveitado de `historico-aluno.php`)
  - Lista turmas teóricas do aluno
  - Exibe frequência percentual por turma
  - Tabela de aulas com status de presença
- **Status:** ✅ **FUNCIONAL** (implementado na Fase 1)

---

## 3. ADMIN - SITUAÇÃO ATUAL

### 3.1. O que já funciona bem

#### **Interface de Chamada (`admin/pages/turma-chamada.php`)**

✅ **Funcionalidades Completas:**
- Lista todos os alunos matriculados na turma
- Exibe status de presença de cada aluno (Presente/Ausente/Sem registro)
- Botões "Presente" e "Ausente" funcionam corretamente
- Exibe frequência percentual de cada aluno em tempo real
- Estatísticas da turma atualizadas automaticamente
- Permite adicionar justificativa/observação
- **Ações em lote:** Botões "Marcar todos presentes" e "Marcar todos ausentes" (linhas 533-534)
- Seleção de aula via dropdown funciona
- Interface responsiva (mobile-friendly)

✅ **Fluxo de Marcação:**
1. JavaScript chama `marcarPresenca(alunoId, presente)` (linha 634)
2. Função faz POST/PUT para `/admin/api/turma-presencas.php`
3. API valida e insere/atualiza em `turma_presencas`
4. Frequência é recalculada automaticamente
5. Interface é atualizada via AJAX

✅ **Validações:**
- Não permite marcar presença de aluno não matriculado
- Não permite duplicar presença (UNIQUE KEY)
- Bloqueia edição de turmas canceladas
- Bloqueia edição de aulas canceladas

#### **Detalhes da Turma (`admin/pages/turmas-teoricas-detalhes-inline.php`)**

✅ **Aba "Alunos Matriculados":**
- Lista alunos com: nome, CPF, categoria, telefone, email
- **Exibe `frequencia_percentual`** (atualizado automaticamente)
- Permite matricular novos alunos
- Permite remover alunos da turma

✅ **Aba "Calendário de Aulas":**
- Lista todas as aulas agendadas
- Permite agendar novas aulas
- Link para chamada de cada aula funciona corretamente

#### **Histórico do Aluno (`admin/pages/historico-aluno.php`)**

✅ **Bloco "Presença Teórica":**
- Lista todas as turmas teóricas do aluno
- Exibe frequência percentual por turma
- Tabela de aulas com status de presença (Presente/Ausente/Não registrado)
- Exibe justificativas (se houver)
- Dados 100% sincronizados com a interface de chamada

### 3.2. O que está inconsistente

#### **1. Falta de Histórico de Alterações**

**Problema:**
- Não há registro de quem alterou uma presença, quando e o que mudou
- Campo `registrado_por` existe, mas não há histórico de alterações

**Impacto:** Médio
- Dificulta auditoria de alterações
- Não é possível rastrear quem fez correções

**Onde corrigir:**
- Criar tabela `turma_presencas_log` ou usar tabela `logs` existente
- Registrar alterações em `admin/api/turma-presencas.php` (PUT)

#### **2. Falta de Validação de Limite Temporal**

**Problema:**
- Não há limite temporal para edição de presenças
- Admin pode editar presenças de qualquer data (passadas ou futuras)

**Impacto:** Baixo
- Pode ser intencional (admin precisa corrigir presenças antigas)
- Mas pode ser um risco se não houver controle

**Onde corrigir:**
- Adicionar validação opcional em `admin/api/turma-presencas.php`
- Permitir configurar limite de dias para edição

#### **3. Falta de Relatórios Exportáveis**

**Problema:**
- Não há exportação PDF/Excel de lista de presença
- Não há relatório consolidado de frequência por período
- Não há relatório de alunos com frequência abaixo do mínimo

**Impacto:** Médio
- Dificulta análise e impressão de relatórios
- Secretaria precisa copiar dados manualmente

**Onde corrigir:**
- Criar `admin/api/exportar-presencas.php`
- Criar `admin/api/exportar-frequencia.php`
- Adicionar botões de exportação nas páginas

### 3.3. Gaps em relação às regras de negócio

#### **1. Frequência Mínima Configurável**

**Status:** ✅ **IMPLEMENTADO**
- Campo `frequencia_minima` existe em `turmas_teoricas` (pode ser NULL)
- Padrão de 75% usado quando não configurado
- Validação de presença para prova teórica usa este campo

#### **2. Validação de Presença para Prova Teórica**

**Status:** ✅ **IMPLEMENTADO**
- `ExamesRulesService::podeAgendarProvaTeorica()` verifica frequência >= 75%
- Bloqueia agendamento de prova se frequência insuficiente

#### **3. Validação de Exames para Matrícula**

**Status:** ✅ **IMPLEMENTADO**
- `TurmaTeoricaManager::matricularAluno()` valida exames antes de matricular
- Usa `AgendamentoGuards::verificarExamesOK($alunoId)`

---

## 4. INSTRUTOR - SITUAÇÃO ATUAL

### 4.1. O que já está pronto

#### **Interface de Chamada (Compartilhada)**

✅ **Acesso:**
- Instrutor pode acessar `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`
- Validação: `turma.instrutor_id == userId`
- Se não for o instrutor da turma, `$canEdit = false` (apenas visualização)

✅ **Funcionalidades:**
- Pode marcar presença/falta para todos os alunos de uma aula
- Pode adicionar justificativa/observação
- Pode ver frequência percentual de cada aluno
- Pode ver estatísticas da turma
- **Ações em lote:** Botões "Marcar todos presentes" e "Marcar todos ausentes"

✅ **Restrições:**
- Não pode editar presenças de turmas concluídas
- Não pode editar presenças de turmas canceladas
- Não pode editar presenças de aulas canceladas

### 4.2. O que depende de ajuste

#### **1. Acesso Fácil às Turmas Teóricas**

**Problema:**
- Dashboard do instrutor (`instrutor/dashboard.php`) não mostra turmas teóricas
- Instrutor precisa saber a URL exata para acessar a chamada

**Impacto:** Alto
- Instrutor não tem visibilidade de suas turmas teóricas
- Dificulta o trabalho diário do instrutor

**Onde corrigir:**
- Adicionar seção "Minhas Turmas Teóricas" em `instrutor/dashboard.php`
- Listar turmas teóricas do instrutor (status: ativa, completa, cursando)
- Adicionar link direto para chamada de cada turma
- Adicionar link para próxima aula teórica do dia

#### **2. Lista de Aulas Teóricas**

**Problema:**
- `instrutor/aulas.php` não lista aulas teóricas
- Instrutor não vê suas aulas teóricas na lista de aulas

**Impacto:** Médio
- Instrutor precisa acessar chamada via URL direta
- Não tem visão unificada de aulas (práticas + teóricas)

**Onde corrigir:**
- Adicionar seção "Aulas Teóricas" em `instrutor/aulas.php`
- Listar aulas teóricas do instrutor (futuras e passadas)
- Adicionar link para chamada de cada aula
- Adicionar filtros (período, status, turma)

### 4.3. Lacunas

#### **1. Notificações**

**Problema:**
- Não há notificação quando há aula teórica agendada para hoje
- Não há notificação quando há presenças pendentes

**Impacto:** Baixo
- Instrutor pode esquecer de fazer chamada
- Mas não é crítico (pode ser feito depois)

**Onde corrigir:**
- Adicionar notificações em `includes/services/SistemaNotificacoes.php`
- Criar lógica de notificação em `admin/includes/TurmaTeoricaManager.php`

---

## 5. ALUNO - SITUAÇÃO ATUAL

### 5.1. O que ele já enxerga

#### **Dashboard do Aluno (`aluno/dashboard.php`)**

✅ **Funcionalidades:**
- Exibe progresso geral (exames, aulas teóricas, aulas práticas)
- Timeline de etapas
- Link para "Minhas Presenças Teóricas" (botão destacado)

#### **Presenças Teóricas (`aluno/presencas-teoricas.php`)**

✅ **Funcionalidades:**
- Lista turmas teóricas do aluno
- Exibe frequência percentual por turma
- Tabela de aulas com status de presença (Presente/Ausente/Não registrado)
- Exibe justificativas (se houver)
- Filtro por período (último mês, último trimestre, etc.)

✅ **Segurança:**
- Usa `getCurrentAlunoId()` para identificar o aluno
- Não aceita `aluno_id` via GET/POST
- Valida que turma selecionada pertence ao aluno

#### **Histórico do Aluno (`aluno/historico.php`)**

✅ **Funcionalidades:**
- Bloco "Presença Teórica" completo
- Lista turmas teóricas do aluno
- Exibe frequência percentual por turma
- Tabela de aulas com status de presença
- Dados 100% sincronizados com a visão do admin

### 5.2. Coerência dos dados com o admin

#### **Comparação de Lógica**

✅ **Queries Idênticas:**
- `aluno/presencas-teoricas.php` usa as mesmas queries de `admin/pages/historico-aluno.php`
- `aluno/historico.php` reaproveita a mesma lógica
- Todas usam `frequencia_percentual` de `turma_matriculas` diretamente

✅ **Sincronização:**
- Campo `frequencia_percentual` é atualizado automaticamente após cada alteração
- Não há divergência entre cálculo dinâmico e campo persistido
- Aluno vê exatamente os mesmos dados que o admin vê

### 5.3. Problemas encontrados

#### **Nenhum problema crítico identificado**

✅ **Status:** Sistema funcional e seguro
- Aluno consegue ver suas presenças teóricas
- Dados sincronizados com admin
- Segurança validada (aluno só vê seus próprios dados)

---

## 6. CHECKLIST DE AJUSTES

### FASE 1 — Ajustes ADMIN (Prioridade Máxima)

#### **1. Histórico de Alterações de Presença**
- **Título:** Registrar histórico de alterações
- **Descrição:** Criar tabela `turma_presencas_log` ou usar tabela `logs` existente para registrar todas as alterações de presença (quem alterou, quando, o que mudou)
- **Onde corrigir:**
  - Criar migration: `admin/migrations/XXX-create-turma-presencas-log.sql`
  - Modificar: `admin/api/turma-presencas.php` (PUT e DELETE)
- **Tipo:** Regra de negócio / Auditoria
- **Impacto:** Médio

#### **2. Relatórios Exportáveis**
- **Título:** Exportação PDF/Excel de lista de presença
- **Descrição:** Criar endpoints para exportar lista de presença e relatório de frequência em PDF/Excel
- **Onde corrigir:**
  - Criar: `admin/api/exportar-presencas.php`
  - Criar: `admin/api/exportar-frequencia.php`
  - Adicionar botões de exportação em `admin/pages/turma-chamada.php`
  - Adicionar botões de exportação em `admin/pages/turmas-teoricas-detalhes-inline.php`
- **Tipo:** Melhoria de UX
- **Impacto:** Médio

#### **3. Relatório de Alunos em Risco**
- **Título:** Relatório de alunos com frequência abaixo do mínimo
- **Descrição:** Criar página de relatório mostrando alunos com frequência abaixo do mínimo (75% ou configurado na turma)
- **Onde corrigir:**
  - Criar: `admin/pages/relatorio-frequencia.php`
  - Adicionar item no menu do admin
- **Tipo:** Relatório
- **Impacto:** Médio

#### **4. Filtro de Alunos em Risco na Lista**
- **Título:** Filtro "Frequência abaixo do mínimo" na lista de alunos da turma
- **Descrição:** Adicionar filtro na aba "Alunos Matriculados" dos detalhes da turma para mostrar apenas alunos com frequência abaixo do mínimo
- **Onde corrigir:**
  - Modificar: `admin/pages/turmas-teoricas-detalhes-inline.php`
- **Tipo:** Melhoria de UX
- **Impacto:** Baixo

### FASE 2 — Ajustes INSTRUTOR

#### **5. Seção "Minhas Turmas Teóricas" no Dashboard**
- **Título:** Adicionar seção de turmas teóricas no dashboard do instrutor
- **Descrição:** Adicionar seção no `instrutor/dashboard.php` listando turmas teóricas do instrutor com link direto para chamada
- **Onde corrigir:**
  - Modificar: `instrutor/dashboard.php`
- **Tipo:** Melhoria de UX
- **Impacto:** Alto

#### **6. Lista de Aulas Teóricas em `instrutor/aulas.php`**
- **Título:** Adicionar seção de aulas teóricas na lista de aulas
- **Descrição:** Adicionar seção "Aulas Teóricas" em `instrutor/aulas.php` listando aulas teóricas do instrutor com link para chamada
- **Onde corrigir:**
  - Modificar: `instrutor/aulas.php`
- **Tipo:** Melhoria de UX
- **Impacto:** Médio

### FASE 3 — Ajustes ALUNO

#### **7. Nenhum ajuste necessário**
- **Status:** ✅ Sistema funcional e completo
- **Observação:** Aluno já tem acesso completo às suas presenças teóricas, frequência e histórico. Nenhum ajuste crítico identificado.

---

## 7. PRÓXIMOS PASSOS SUGERIDOS

### Foco: ADMIN (Prioridade Máxima)

#### **Passo 1: Histórico de Alterações**
1. Criar tabela `turma_presencas_log` com campos:
   - `id`, `presenca_id`, `turma_id`, `aula_id`, `aluno_id`
   - `presente_antes`, `presente_depois`
   - `justificativa_antes`, `justificativa_depois`
   - `alterado_por` (FK → `usuarios.id`)
   - `alterado_em` (TIMESTAMP)
2. Modificar `admin/api/turma-presencas.php`:
   - Na função `handlePutRequest()`, registrar alteração antes de atualizar
   - Na função `handleDeleteRequest()`, registrar exclusão
3. Criar página de visualização do histórico (opcional):
   - `admin/pages/historico-presencas.php` ou adicionar modal na chamada

#### **Passo 2: Relatórios Exportáveis**
1. Criar `admin/api/exportar-presencas.php`:
   - Exportar lista de presença de uma aula em PDF/Excel
   - Usar biblioteca como `PhpSpreadsheet` ou `FPDF`
2. Criar `admin/api/exportar-frequencia.php`:
   - Exportar relatório de frequência de uma turma em PDF/Excel
3. Adicionar botões de exportação:
   - Em `admin/pages/turma-chamada.php` (exportar lista de presença)
   - Em `admin/pages/turmas-teoricas-detalhes-inline.php` (exportar frequência da turma)

#### **Passo 3: Relatório de Alunos em Risco**
1. Criar `admin/pages/relatorio-frequencia.php`:
   - Listar todas as turmas teóricas
   - Para cada turma, listar alunos com frequência abaixo do mínimo
   - Adicionar filtros (turma, período, status)
   - Exibir estatísticas gerais (total de alunos em risco, frequência média)
2. Adicionar item no menu do admin:
   - "Relatórios" → "Frequência Teórica"

#### **Passo 4: Filtro de Alunos em Risco**
1. Modificar `admin/pages/turmas-teoricas-detalhes-inline.php`:
   - Adicionar filtro "Frequência abaixo do mínimo" na aba "Alunos Matriculados"
   - Adicionar badge visual para alunos com frequência abaixo do mínimo
   - Adicionar ordenação por frequência (maior/menor)

### Ordem de Implementação Sugerida

1. **Primeiro:** Histórico de Alterações (auditoria e rastreabilidade)
2. **Segundo:** Relatórios Exportáveis (necessidade imediata da secretaria)
3. **Terceiro:** Relatório de Alunos em Risco (análise e acompanhamento)
4. **Quarto:** Filtro de Alunos em Risco (melhoria de UX)

---

**Fim da Auditoria**

