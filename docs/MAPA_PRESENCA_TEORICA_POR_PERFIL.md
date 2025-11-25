# 🗺️ MAPA DE PRESENÇA TEÓRICA POR PERFIL
## Sistema CFC Bom Conselho - Visão por Perfil de Usuário

**Data:** 24/11/2025  
**Objetivo:** Mapear o que cada perfil vê e pode fazer hoje, e o que precisa ser implementado

---

## 📋 ÍNDICE

1. [Admin](#1-admin)
2. [Secretaria / Atendente CFC](#2-secretaria--atendente-cfc)
3. [Instrutor](#3-instrutor)
4. [Aluno](#4-aluno)

---

## 1. ADMIN

### 1.1. O que ele consegue VER hoje

#### ✅ **Turmas Teóricas:**
- **Lista de turmas:** `index.php?page=turmas-teoricas`
  - Nome, sala, datas, número de alunos, status
  - Filtros por status, curso, período
- **Detalhes da turma:** `index.php?page=turmas-teoricas&acao=detalhes&turma_id={id}`
  - Informações completas da turma
  - Aba "Alunos Matriculados" com frequência percentual
  - Aba "Calendário de Aulas" com todas as aulas agendadas
  - Link para chamada de cada aula

#### ✅ **Presença dos Alunos:**
- **Interface de chamada:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`
  - Lista de alunos matriculados
  - Status de presença de cada aluno (Presente/Ausente/Sem registro)
  - Frequência percentual de cada aluno
  - Estatísticas da turma (total presentes, ausentes, sem registro)
- **Histórico do aluno:** `index.php?page=historico-aluno&id={aluno_id}`
  - Bloco completo "Presença Teórica"
  - Lista de turmas teóricas do aluno
  - Frequência percentual por turma
  - Tabela de aulas com status de presença

#### ✅ **Agenda de Aulas:**
- **Calendário de aulas:** Aba "Calendário de Aulas" nos detalhes da turma
  - Lista todas as aulas agendadas
  - Data, horário, disciplina, instrutor, sala
  - Status da aula (agendada, realizada, cancelada)
  - Link para chamada de cada aula

---

### 1.2. O que ele consegue FAZER hoje

#### ✅ **Registrar / Editar / Excluir Presença:**
- **Marcar presença individual:**
  - Acessa interface de chamada
  - Clica em "Presente" ou "Ausente" para cada aluno
  - Pode adicionar justificativa/observação
- **Marcar presença em lote:**
  - Via API `POST /admin/api/turma-presencas.php` com array de presenças
- **Editar presença:**
  - Pode alterar status (Presente ↔ Ausente)
  - Pode alterar justificativa
  - Pode editar presenças de turmas concluídas (diferente do instrutor)
- **Excluir presença:**
  - Via API `DELETE /admin/api/turma-presencas.php?id={presenca_id}`
  - Frequência é recalculada automaticamente

#### ✅ **Ver Relatórios Consolidados:**
- **Frequência por turma:**
  - Via API `GET /admin/api/turma-frequencia.php?turma_id={id}`
  - Retorna frequência de todos os alunos da turma
  - Estatísticas gerais (aprovados, reprovados, frequência média)
- **Frequência por aluno:**
  - Via API `GET /admin/api/turma-frequencia.php?aluno_id={id}&turma_id={id}`
  - Retorna frequência específica do aluno
  - Histórico completo de presenças
- **Histórico do aluno:**
  - Página `historico-aluno.php` com bloco completo de presença teórica

#### ✅ **Gerenciar Turmas e Aulas:**
- Criar/editar/excluir turmas teóricas
- Agendar aulas teóricas
- Matricular alunos em turmas
- Ativar/concluir/cancelar turmas

---

### 1.3. O que claramente ainda NÃO existe e precisa ser criado

#### ❌ **Relatórios Exportáveis:**
- Não há exportação PDF/Excel de lista de presença
- Não há relatório consolidado de frequência por período
- Não há relatório de alunos com frequência abaixo do mínimo

#### ❌ **Dashboard de Frequência:**
- Não há dashboard consolidado mostrando:
  - Frequência média geral
  - Alunos com frequência abaixo do mínimo
  - Turmas com maior/menor frequência

#### ❌ **Notificações:**
- Não há notificação quando aluno atinge frequência mínima
- Não há notificação quando aluno está abaixo do mínimo

#### ⚠️ **Melhorias de UX:**
- Falta botão "Marcar todos presentes" / "Marcar todos ausentes" na chamada
- Falta filtro por frequência na lista de alunos da turma

---

## 2. SECRETARIA / ATENDENTE CFC

### 2.1. O que ela consegue VER hoje

#### ✅ **Turmas Teóricas:**
- **Lista de turmas:** `index.php?page=turmas-teoricas`
  - Mesma visão do admin
  - Nome, sala, datas, número de alunos, status
- **Detalhes da turma:** `index.php?page=turmas-teoricas&acao=detalhes&turma_id={id}`
  - Mesma visão do admin
  - Aba "Alunos Matriculados" com frequência percentual
  - Aba "Calendário de Aulas"

#### ✅ **Presença dos Alunos:**
- **Interface de chamada:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`
  - Mesma visão do admin
  - Lista de alunos com status de presença
  - Frequência percentual de cada aluno
- **Histórico do aluno:** `index.php?page=historico-aluno&id={aluno_id}`
  - Mesma visão do admin
  - Bloco completo "Presença Teórica"

---

### 2.2. O que ela consegue FAZER hoje

#### ✅ **Registrar / Editar / Excluir Presença:**
- **Mesmas permissões do admin:**
  - Pode marcar presença individual ou em lote
  - Pode editar presença (exceto turmas canceladas)
  - Pode excluir presença
  - Pode adicionar justificativa/observação

#### ✅ **Cadastrar/Editar Turmas e Aulas:**
- Pode criar/editar turmas teóricas
- Pode agendar aulas teóricas
- Pode matricular alunos em turmas
- Pode ativar/concluir turmas

#### ✅ **Ver Relatórios:**
- Pode acessar frequência por turma ou aluno
- Pode ver histórico completo do aluno

---

### 2.3. Lacunas de fluxo identificadas

#### ❌ **Correção de Presença:**
- Não há interface específica para "corrigir presença" (já existe via edição, mas poderia ser mais intuitivo)
- Não há histórico de alterações de presença (quem alterou, quando, o que mudou)

#### ❌ **Histórico Filtrado por Aluno:**
- Não há filtro rápido de "alunos com frequência abaixo do mínimo" na lista de alunos da turma
- Não há busca rápida de aluno por nome/CPF na interface de chamada

#### ❌ **Relatórios Específicos:**
- Não há relatório de "alunos em risco" (frequência abaixo do mínimo)
- Não há relatório de "presenças pendentes" (aulas sem presença registrada)

#### ⚠️ **Melhorias de UX:**
- Falta botão "Marcar todos presentes" / "Marcar todos ausentes"
- Falta exportação de lista de presença

---

## 3. INSTRUTOR

### 3.1. O que o instrutor já consegue VER no painel dele

#### ⚠️ **Aulas Teóricas Futuras:**
- **Dashboard:** `instrutor/dashboard.php`
  - **NÃO exibe turmas teóricas** diretamente
  - Exibe apenas aulas práticas do dia
- **Lista de aulas:** `instrutor/aulas.php`
  - **NÃO lista aulas teóricas**
  - Lista apenas aulas práticas

#### ✅ **Interface de Chamada (Compartilhada):**
- **Chamada de turma:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`
  - Pode acessar se for o instrutor da turma (`turma.instrutor_id == userId`)
  - Lista de alunos matriculados
  - Status de presença de cada aluno
  - Frequência percentual de cada aluno
  - Estatísticas da turma

#### ❌ **Turmas Teóricas:**
- **NÃO há lista de turmas teóricas** no dashboard do instrutor
- **NÃO há link direto** para acessar suas turmas teóricas

---

### 3.2. O que o instrutor já consegue FAZER

#### ✅ **Registrar Presença / Fazer Chamada:**
- **Marcar presença individual:**
  - Acessa interface de chamada
  - Clica em "Presente" ou "Ausente" para cada aluno
  - Pode adicionar justificativa/observação
- **Editar presença:**
  - Pode alterar status (Presente ↔ Ausente)
  - Pode alterar justificativa
  - **Restrição:** Não pode editar presenças de turmas concluídas
- **Excluir presença:**
  - Via API (mesma funcionalidade do admin)

#### ✅ **Ver Frequência:**
- Pode ver frequência percentual de cada aluno na interface de chamada
- Pode ver estatísticas da turma (total presentes, ausentes, sem registro)

---

### 3.3. O que está faltando para ele ter uma "chamada" funcional

#### ❌ **Acesso Fácil às Turmas Teóricas:**
- Falta seção "Minhas Turmas Teóricas" no dashboard
- Falta lista de turmas teóricas com link direto para chamada
- Falta lista de aulas teóricas futuras

#### ❌ **Notificações:**
- Não há notificação quando há aula teórica agendada para hoje
- Não há notificação quando há presenças pendentes

#### ❌ **Integração com Aulas Práticas:**
- Dashboard mostra apenas aulas práticas
- Falta visão unificada (aulas práticas + teóricas)

#### ⚠️ **Melhorias de UX:**
- Falta botão "Marcar todos presentes" / "Marcar todos ausentes"
- Falta acesso rápido à chamada da próxima aula teórica

---

### 3.4. Endpoint pronto para registrar presença

#### ✅ **API de Presenças:**
- **Endpoint:** `POST /admin/api/turma-presencas.php`
- **Permissões:** Instrutor pode usar se for instrutor da turma
- **Validações:** Implementadas (instrutor só suas turmas, turmas não canceladas, etc.)
- **Status:** ✅ **PRONTO E FUNCIONAL**

---

## 4. ALUNO

### 4.1. O que o aluno consegue VER hoje

#### ❌ **Aulas Teóricas Agendadas:**
- **Dashboard:** `aluno/dashboard.php`
  - **NÃO exibe aulas teóricas agendadas**
  - Exibe apenas timeline de etapas (exames, aulas teóricas, etc.)
  - Não mostra detalhes de turmas ou aulas

#### ❌ **Histórico de Presença/Faltas:**
- **Dashboard:** `aluno/dashboard.php`
  - **NÃO exibe presenças teóricas**
  - **NÃO exibe frequência percentual**
  - **NÃO exibe histórico de presenças/faltas**

#### ⚠️ **Histórico via Admin:**
- **Histórico do aluno:** `index.php?page=historico-aluno&id={aluno_id}`
  - Bloco completo "Presença Teórica" existe
  - **PROBLEMA:** Acessível apenas via admin, não diretamente pelo aluno
  - Aluno não tem acesso direto ao seu próprio histórico

---

### 4.2. O que o aluno consegue FAZER hoje

#### ❌ **Visualizar Presenças:**
- **NÃO pode visualizar** suas presenças teóricas diretamente
- **NÃO pode ver** frequência percentual
- **NÃO pode ver** histórico de presenças/faltas

#### ❌ **Acessar Histórico:**
- **NÃO tem acesso** ao seu próprio histórico de presença teórica
- Precisa pedir para admin/secretaria mostrar

---

### 4.3. O que falta para ele ver

#### ❌ **Aulas Teóricas Agendadas da Turma:**
- Falta seção "Minhas Turmas Teóricas" no dashboard
- Falta lista de aulas teóricas agendadas
- Falta informações da turma (nome, período, sala, instrutor)

#### ❌ **Histórico de Presença/Faltas por Período:**
- Falta página `aluno/presencas-teoricas.php` ou bloco no dashboard
- Falta lista de aulas com status de presença (Presente/Ausente/Não registrado)
- Falta frequência percentual por turma
- Falta filtro por período (último mês, último trimestre, etc.)

#### ❌ **Acesso Direto ao Histórico:**
- Falta permitir que aluno acesse `historico-aluno.php` diretamente (com validação de que é o próprio aluno)
- Ou criar página específica para aluno ver seu histórico

---

### 4.4. API que já expõe esse histórico

#### ✅ **API de Frequência:**
- **Endpoint:** `GET /admin/api/turma-frequencia.php?aluno_id={id}&turma_id={id}`
- **Permissões:** Atualmente apenas admin/secretaria/instrutor
- **Funcionalidade:** Retorna frequência específica do aluno e histórico completo de presenças
- **Status:** ✅ **EXISTE, mas não está acessível para o aluno**

#### ✅ **API de Presenças:**
- **Endpoint:** `GET /admin/api/turma-presencas.php?aluno_id={id}&turma_id={id}`
- **Permissões:** Atualmente apenas admin/secretaria/instrutor
- **Funcionalidade:** Retorna todas as presenças do aluno em uma turma
- **Status:** ✅ **EXISTE, mas não está acessível para o aluno**

---

## 📊 RESUMO COMPARATIVO

| Funcionalidade | Admin | Secretaria | Instrutor | Aluno |
|---------------|-------|-----------|-----------|-------|
| **Ver lista de turmas teóricas** | ✅ | ✅ | ❌ | ❌ |
| **Ver detalhes da turma** | ✅ | ✅ | ⚠️ (se for dele) | ❌ |
| **Ver presenças dos alunos** | ✅ | ✅ | ✅ (suas turmas) | ❌ |
| **Marcar presença** | ✅ | ✅ | ✅ (suas turmas) | ❌ |
| **Editar presença** | ✅ | ✅ | ✅ (suas turmas) | ❌ |
| **Ver frequência percentual** | ✅ | ✅ | ✅ (suas turmas) | ❌ |
| **Ver histórico de presenças** | ✅ | ✅ | ⚠️ (limitado) | ❌ |
| **Ver suas próprias presenças** | ✅ | ✅ | N/A | ❌ |
| **Acessar interface de chamada** | ✅ | ✅ | ✅ (suas turmas) | ❌ |
| **Ver relatórios consolidados** | ⚠️ (via API) | ⚠️ (via API) | ❌ | ❌ |
| **Exportar relatórios** | ❌ | ❌ | ❌ | ❌ |

**Legenda:**
- ✅ = Implementado e funcional
- ⚠️ = Parcialmente implementado ou com limitações
- ❌ = Não implementado

---

## 🎯 PRIORIDADES POR PERFIL

### **Admin:**
- **Alta:** Relatórios exportáveis (PDF/Excel)
- **Média:** Dashboard de frequência consolidado
- **Baixa:** Notificações automáticas

### **Secretaria:**
- **Alta:** Filtro de alunos com frequência abaixo do mínimo
- **Média:** Histórico de alterações de presença
- **Baixa:** Exportação de lista de presença

### **Instrutor:**
- **Alta:** Seção "Minhas Turmas Teóricas" no dashboard
- **Alta:** Link direto para chamada de turmas teóricas
- **Média:** Lista de aulas teóricas em `instrutor/aulas.php`
- **Baixa:** Notificações de aulas teóricas agendadas

### **Aluno:**
- **Alta:** Página `aluno/presencas-teoricas.php` ou bloco no dashboard
- **Alta:** Acesso direto ao histórico de presenças
- **Média:** Lista de aulas teóricas agendadas
- **Baixa:** Notificações de frequência

---

**Fim do Mapa por Perfil**

