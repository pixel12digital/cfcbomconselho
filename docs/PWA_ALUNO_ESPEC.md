# PWA Aluno – Especificação Funcional e Técnica

**Versão:** 1.0  
**Data:** Janeiro 2025  
**Status:** Especificação - Contrato para Implementação

---

## 1. Objetivo

O **PWA Aluno** é a interface mobile-first e web que permite aos alunos do CFC Bom Conselho acompanharem de forma autônoma e transparente sua jornada de formação. Ele serve como central de informações para que o aluno visualize suas aulas, acompanhe seu progresso, consulte seu financeiro e tenha visibilidade completa de todas as etapas do processo de habilitação.

**Principais objetivos:**
- Fornecer ao aluno uma visão clara da sua próxima aula (teórica ou prática) e fase atual do processo
- Permitir acompanhamento de aulas teóricas e práticas (histórico e futuras)
- Exibir presenças e frequência em turmas teóricas
- Mostrar status de exames (médico, psicotécnico, teórico, prático)
- Disponibilizar informações financeiras de forma clara e simples (faturas, parcelas, status)

---

## 2. Estado Atual (Como o Sistema Funciona Hoje)

### 2.1. Telas/Páginas Existentes

| Arquivo | URL | Descrição |
|---------|-----|-----------|
| `aluno/login.php` | `/aluno/login.php` | Tela de login do aluno (GET exibe formulário, POST processa autenticação) |
| `aluno/dashboard.php` | `/aluno/dashboard.php` | Dashboard principal com resumo geral, próximas aulas, notificações |
| `aluno/dashboard-mobile.php` | `/aluno/dashboard-mobile.php` | Versão alternativa mobile (pode ser redundante com dashboard.php) |
| `aluno/aulas.php` | `/aluno/aulas.php` | Listagem completa de aulas do aluno (teóricas e práticas) com filtros |
| `aluno/financeiro.php` | `/aluno/financeiro.php` | Visualização de faturas e resumo financeiro do aluno |
| `aluno/presencas-teoricas.php` | `/aluno/presencas-teoricas.php` | Visualização de presenças teóricas por turma e frequência |
| `aluno/historico.php` | `/aluno/historico.php` | Histórico completo da jornada do aluno |
| `aluno/notificacoes.php` | `/aluno/notificacoes.php` | Listagem de notificações do aluno |
| `aluno/contato.php` | `/aluno/contato.php` | Tela de contato/suporte |
| `aluno/logout.php` | `/aluno/logout.php` | Logout do aluno (destrói sessão e redireciona) |

### 2.2. Funcionalidades Atuais

#### Dashboard (`aluno/dashboard.php`)

**O que o aluno consegue ver hoje:**
- **Boas-vindas:** Card com nome do aluno e botão de logout
- **Notificações:** Últimas notificações não lidas (se houver)
- **Próximas aulas:** Lista de aulas do primeiro dia com aulas agendadas (até 14 dias no futuro):
  - Aulas práticas: horário, instrutor, veículo (modelo, placa)
  - Aulas teóricas: horário, turma, disciplina, sala, instrutor
  - Tipo de aula (badge: Teórica/Prática)
  - Status (agendada, em andamento, concluída, cancelada)
- **Resumo de exames:** Status dos exames (médico, psicotécnico) - apenas visualização básica
- **Resumo financeiro:** Card com indicadores básicos (em aberto, em atraso, pagas)
- **Progresso:** Indicadores visuais de progresso (se implementado)

**O que o aluno consegue fazer hoje:**
- Visualizar próximas aulas (práticas e teóricas)
- Visualizar resumo financeiro básico
- Visualizar status de exames
- Navegar para páginas detalhadas (aulas, financeiro, presenças)

#### Página de Aulas (`aluno/aulas.php`)

**O que o aluno consegue ver hoje:**
- Listagem completa de aulas (teóricas e práticas) com filtros:
  - Período (últimos 7 dias, últimos 30 dias, próximos 7 dias, próximos 30 dias, hoje, todas)
  - Tipo (todas, práticas, teóricas)
  - Status (agendada, em andamento, concluída, cancelada)
- Agrupamento por data (aulas futuras primeiro, depois passadas)
- Para cada aula:
  - Data, horário, tipo (prática/teórica)
  - Instrutor (nome)
  - Veículo (modelo, placa) - apenas práticas
  - Turma, disciplina, sala - apenas teóricas
  - Status da aula
- **Próxima aula em destaque:** Primeira aula futura exibida com destaque visual

**O que o aluno consegue fazer hoje:**
- Filtrar aulas por período, tipo e status
- Visualizar detalhes de cada aula
- Ver histórico de aulas realizadas
- Ver próximas aulas agendadas

#### Página de Financeiro (`aluno/financeiro.php`)

**O que o aluno consegue ver hoje:**
- Listagem de faturas com filtros:
  - Período (vencidas, últimos 30 dias, próximos 30 dias, últimos e próximos 90 dias)
  - Status (aberta, paga, vencida, parcial)
- Para cada fatura:
  - Título/descrição
  - Valor total
  - Valor pago (se houver)
  - Data de vencimento
  - Status (badge)
- **Resumo financeiro:** Cards com totais (em aberto, em atraso, pagas)

**O que o aluno consegue fazer hoje:**
- Visualizar faturas e status
- Filtrar por período e status
- Visualizar resumo financeiro

#### Página de Presenças Teóricas (`aluno/presencas-teoricas.php`)

**O que o aluno consegue ver hoje:**
- Listagem de turmas teóricas em que o aluno está matriculado
- Para cada turma:
  - Nome da turma
  - Frequência percentual (`turma_matriculas.frequencia_percentual`)
  - Total de aulas agendadas vs presenças registradas
- **Detalhamento por turma:** Ao selecionar uma turma, lista de aulas com:
  - Data e horário
  - Disciplina
  - Status de presença (Presente/Ausente/Não registrado)
  - Justificativa (se houver)

**O que o aluno consegue fazer hoje:**
- Visualizar frequência por turma
- Visualizar presenças detalhadas de cada aula teórica
- Filtrar por período (últimos 30 dias, últimos 90 dias, último mês, todas)

#### Página de Histórico (`aluno/historico.php`)

**O que o aluno consegue ver hoje:**
- Histórico completo da jornada (se implementado):
  - Eventos cronológicos (cadastro, matrícula, aulas, exames, faturas)

**Funcionalidade:** Parcialmente implementada (depende de `admin/api/historico_aluno.php`)

### 2.3. APIs Utilizadas Atualmente

| Endpoint | Método | Uso Atual | Tela/Contexto |
|----------|--------|-----------|---------------|
| `admin/api/aluno-agenda.php` | GET | Agenda consolidada do aluno (práticas + teóricas) | `dashboard.php`, `aulas.php` |
| `admin/api/financeiro-resumo-aluno.php` | GET | Resumo financeiro do aluno (totais, faturas) | `dashboard.php`, `financeiro.php` |
| `admin/api/turma-presencas.php` | GET | Consultar presenças teóricas do aluno (read-only) | `presencas-teoricas.php` |
| `admin/api/progresso_teorico.php` | GET | Progresso teórico do aluno (se disponível) | Dashboard (potencial uso) |
| `admin/api/progresso_pratico.php` | GET | Progresso prático do aluno (se disponível) | Dashboard (potencial uso) |
| `admin/api/historico_aluno.php` | GET | Histórico completo da jornada | `historico.php` |
| `admin/api/financeiro-faturas.php` | GET | Listar faturas do aluno (filtrado por `aluno_id`) | `financeiro.php` |

### 2.4. Fluxo Atual Típico

1. **Login:** Aluno acessa `aluno/login.php`, insere email/senha, sistema valida e cria sessão
2. **Dashboard:** Após login, redireciona para `aluno/dashboard.php`
   - Sistema busca próximas aulas (práticas e teóricas) via `admin/api/aluno-agenda.php` ou query direta
   - Sistema busca resumo financeiro via `admin/api/financeiro-resumo-aluno.php`
   - Sistema busca exames da tabela `exames` (WHERE `aluno_id = ?`)
   - Exibe cards com resumo
3. **Visualização de aulas:** Aluno navega para `aluno/aulas.php` para ver histórico/futuro completo
4. **Visualização financeira:** Aluno navega para `aluno/financeiro.php` para ver faturas detalhadas
5. **Visualização de presenças:** Aluno navega para `aluno/presencas-teoricas.php` para ver frequência

### 2.5. Limitações Conhecidas (Estado Atual)

- ⚠️ **Dashboard não mostra "Fase Atual"** - Não há indicador claro se aluno está em fase teórica, prática ou exames
- ⚠️ **Próxima aula pode ser melhorada** - Existe, mas não está em destaque claro no dashboard
- ⚠️ **Próxima parcela financeira** - Não há destaque claro para próxima fatura a vencer no dashboard
- ❌ **Tela de exames dedicada não existe** - Exames aparecem apenas resumidos no dashboard
- ⚠️ **Frequência detalhada** - Existe em `presencas-teoricas.php`, mas pode ser melhorada
- ⚠️ **Histórico completo** - Funcionalidade parcial, depende de `historico_aluno.php`

---

## 3. Funcionalidades Desejadas (Fase 3 do Plano)

As funcionalidades abaixo serão implementadas nas tarefas 3.2 a 3.6 do `docs/PLANO_IMPL_PRODUCAO_CFC.md`.

### 3.1. Dashboard com "Próxima Aula" e Fase Atual (Tarefa 3.2)

**Problema que resolve:** Dar ao aluno visibilidade clara de qual é a próxima etapa do seu processo e em que fase está (teórico/prático/exames).

**Onde aparece:** Card em destaque no topo do `aluno/dashboard.php`

**Informações exibidas:**
- **Próxima aula:** Primeira aula futura (teórica ou prática) com:
  - Data e horário
  - Tipo (Teórica/Prática)
  - Detalhes (turma/disciplina para teórica, instrutor/veículo para prática)
  - Status (agendada, em breve, etc.)
- **Fase atual:** Indicador visual da fase do processo:
  - "Formação Teórica" (quando aluno está matriculado em turma teórica e frequência < 75%)
  - "Formação Prática" (quando aluno completou teórico e está fazendo aulas práticas)
  - "Aguardando Exames" (quando exames médico/psicotécnico não estão aprovados)
  - "Exames" (quando está fazendo provas teóricas/práticas)
  - "Concluído" (quando processo completo)

**Ações do usuário:**
1. Aluno visualiza dashboard
2. Vê claramente qual é a próxima aula e em que fase está
3. Pode clicar na próxima aula para ver detalhes completos

### 3.2. Próxima Parcela Financeira no Dashboard (Tarefa 3.2 - Integrado)

**Problema que resolve:** Alertar o aluno sobre a próxima fatura a vencer, facilitando o planejamento financeiro.

**Onde aparece:** Card adicional no `aluno/dashboard.php`, abaixo ou ao lado do card de "Próxima Aula"

**Informações exibidas:**
- Próxima fatura a vencer (data de vencimento mais próxima, status = 'aberta' ou 'vencida')
- Valor da fatura
- Dias até o vencimento (ou dias em atraso se vencida)
- Link para ver detalhes completos em `aluno/financeiro.php`

### 3.3. Tela de Turmas Teóricas e Frequência Detalhada (Tarefa 3.3)

**Problema que resolve:** Melhorar a visualização de frequência teórica, tornando mais clara a relação entre presenças, faltas e frequência percentual necessária para aprovação.

**Onde aparece:** Melhorar `aluno/presencas-teoricas.php` ou criar `aluno/turmas-teoricas.php`

**Informações exibidas:**
- Lista de turmas teóricas com:
  - Nome da turma
  - Frequência percentual atual
  - Frequência mínima necessária (geralmente 75%)
  - Progresso visual (barra de progresso)
  - Total de aulas vs presenças vs faltas
- **Detalhamento por turma:** Ao selecionar uma turma:
  - Lista completa de aulas com data, disciplina, presença/falta
  - Gráfico ou tabela de frequência ao longo do tempo
  - Aviso se frequência está abaixo do mínimo

**Ações do usuário:**
1. Aluno acessa tela de turmas teóricas
2. Visualiza frequência de cada turma
3. Seleciona turma para ver detalhes completos

### 3.4. Tela de Aulas Práticas (Histórico e Futuras) Melhorada (Tarefa 3.4)

**Problema que resolve:** Separar claramente aulas práticas realizadas (histórico) de aulas futuras, facilitando o acompanhamento do progresso.

**Onde aparece:** Melhorar `aluno/aulas.php` com abas ou seções distintas

**Informações exibidas:**
- **Aba/Seção "Próximas":**
  - Aulas práticas agendadas (status = 'agendada', 'em_andamento')
  - Ordenadas por data/hora
  - Detalhes: data, horário, instrutor, veículo
- **Aba/Seção "Realizadas":**
  - Aulas práticas concluídas (status = 'concluida')
  - Ordenadas por data/hora (mais recentes primeiro)
  - Detalhes: data, horário, instrutor, veículo, KM rodado (se disponível)
- **Estatísticas:** Total de aulas realizadas, total agendadas, progresso (ex.: 20/45 aulas)

### 3.5. Tela de Exames (Tarefa 3.5)

**Problema que resolve:** Centralizar informações sobre exames em uma tela dedicada, mostrando status claro de cada exame necessário.

**Onde aparece:** Criar `aluno/exames.php` (nova página)

**Informações exibidas:**
- Lista de exames com cards/seções por tipo:
  - **Exame Médico:** Status (agendado, realizado, aprovado, reprovado), data
  - **Exame Psicotécnico:** Status, data
  - **Prova Teórica:** Status (se aplicável), data
  - **Prova Prática:** Status (se aplicável), data
- Para cada exame:
  - Tipo e nome
  - Status (badge colorido)
  - Data agendada (se agendado)
  - Data realizado (se realizado)
  - Resultado (aprovado/reprovado, se disponível)
  - Observações (se houver)

**Ações do usuário:**
1. Aluno acessa tela de exames
2. Visualiza status de todos os exames
3. Identifica quais exames estão pendentes

### 3.6. Tela de Financeiro Simplificada (Tarefa 3.6)

**Problema que resolve:** Melhorar a visualização financeira para o aluno, tornando mais clara a situação de parcelas, vencimentos e pagamentos.

**Onde aparece:** Melhorar `aluno/financeiro.php`

**Informações exibidas:**
- **Resumo visual:** Cards com indicadores (em aberto, em atraso, pagas, total)
- **Lista de faturas:** Organizada por status e data de vencimento
- **Próximas parcelas:** Destaque para faturas que vencerão nos próximos 7 dias
- **Faturas em atraso:** Destaque visual (vermelho) para faturas vencidas
- Para cada fatura:
  - Descrição/título
  - Valor total e valor pago
  - Data de vencimento (destaque se vencida)
  - Status (badge)
  - Link para boleto/pagamento (se disponível)

**Ações do usuário:**
1. Aluno visualiza resumo financeiro
2. Identifica faturas em atraso e próximas a vencer
3. Acessa detalhes de cada fatura
4. (Futuro) Faz download de boleto ou acessa link de pagamento

---

## 4. Mapeamento de Telas e Rotas

| Tela | Arquivo | URL | Descrição |
|------|---------|-----|-----------|
| Login Aluno | `aluno/login.php` | `/aluno/login.php` | Tela de login do aluno (GET: formulário, POST: processa autenticação) |
| Dashboard Aluno | `aluno/dashboard.php` | `/aluno/dashboard.php` | Dashboard principal com próxima aula, fase atual, resumo financeiro |
| Aulas do Aluno | `aluno/aulas.php` | `/aluno/aulas.php` | Listagem completa de aulas (teóricas e práticas) com filtros |
| Financeiro Aluno | `aluno/financeiro.php` | `/aluno/financeiro.php` | Visualização de faturas, parcelas e resumo financeiro |
| Presenças Teóricas | `aluno/presencas-teoricas.php` | `/aluno/presencas-teoricas.php` | Visualização de presenças teóricas por turma e frequência |
| Histórico Aluno | `aluno/historico.php` | `/aluno/historico.php` | Histórico completo da jornada do aluno |
| Notificações Aluno | `aluno/notificacoes.php` | `/aluno/notificacoes.php` | Listagem de notificações do aluno |
| Contato | `aluno/contato.php` | `/aluno/contato.php` | Tela de contato/suporte |
| Logout | `aluno/logout.php` | `/aluno/logout.php` | Logout do aluno |

**Futuro (Fase 3):**
| Tela | Arquivo | URL | Descrição |
|------|---------|-----|-----------|
| Turmas Teóricas | `aluno/turmas-teoricas.php` | `/aluno/turmas-teoricas.php` | (Tarefa 3.3 - Opcional) Tela melhorada de turmas teóricas e frequência |
| Exames Aluno | `aluno/exames.php` | `/aluno/exames.php` | (Tarefa 3.5) Tela dedicada de exames com status detalhado |

---

## 5. Mapeamento de APIs Usadas pelo PWA Aluno

| Endpoint | Método | Uso no PWA Aluno | Tela/Fluxo | Cenários de Teste Automatizado (Referência) |
|----------|--------|-----------------|------------|---------------------------------------------|
| `admin/api/aluno-agenda.php` | GET | Agenda consolidada (práticas + teóricas) | `dashboard.php`, `aulas.php` | `tests/api/test-aluno-agenda-api.php` (GET autenticado, GET não autenticado, validação) |
| `admin/api/financeiro-resumo-aluno.php` | GET | Resumo financeiro (totais, faturas) | `dashboard.php`, `financeiro.php` | `tests/api/test-financeiro-resumo-aluno-api.php` (GET autenticado, GET não autenticado, validação) |
| `admin/api/financeiro-faturas.php` | GET | Listar faturas do aluno (filtrado por `aluno_id`) | `financeiro.php` | `tests/api/test-financeiro-faturas-api.php` (GET autenticado como aluno, validação) |
| `admin/api/turma-presencas.php` | GET | Consultar presenças teóricas do aluno (read-only) | `presencas-teoricas.php` | `tests/api/test-turma-presencas-api.php` (GET autenticado como aluno, validação) |
| `admin/api/progresso_teorico.php` | GET | Progresso teórico do aluno | Dashboard (potencial uso futuro) | (Não mapeado ainda - pode ser adicionado) |
| `admin/api/progresso_pratico.php` | GET | Progresso prático do aluno | Dashboard (potencial uso futuro) | (Não mapeado ainda - pode ser adicionado) |
| `admin/api/historico_aluno.php` | GET | Histórico completo da jornada | `historico.php` | (Não mapeado ainda - pode ser adicionado) |

**Futuro (Fase 3):**
| Endpoint | Método | Uso no PWA Aluno | Tela/Fluxo | Cenários de Teste Automatizado (Referência) |
|----------|--------|-----------------|------------|---------------------------------------------|
| `admin/api/aluno-agenda.php` (expandido) | GET | Buscar próxima aula e fase atual | `dashboard.php` (Tarefa 3.2) | `tests/api/test-aluno-agenda-api.php` (novos cenários) |
| `admin/api/exames.php` (adaptado) | GET | Listar exames do aluno (read-only) | `exames.php` (Tarefa 3.5) | `tests/api/test-exames-api.php` (GET autenticado como aluno, validação) |

---

## 6. Impacto em Fluxos Críticos e Testes

### Fluxos Críticos Impactados

| Fluxo Crítico | Impacto do PWA Aluno | Checklist Manual | Testes Automatizados |
|---------------|---------------------|------------------|---------------------|
| **7. Acesso e uso do painel do aluno** | ✅ **ALTO** - PWA Aluno é o próprio painel do aluno | `TESTES_PWA_ALUNO.md`<br>`TESTES_REGRESSAO_FLUXOS_CRITICOS.md` (cenário 7) | `tests/api/test-aluno-agenda-api.php`<br>`tests/api/test-financeiro-resumo-aluno-api.php`<br>`tests/api/test-financeiro-faturas-api.php` |
| **4. Registro de presenças teóricas** | ⚠️ **MÉDIO** - Aluno visualiza presenças (read-only) | `TESTES_PWA_ALUNO.md` | `tests/api/test-turma-presencas-api.php` (GET como aluno) |
| **6. Fluxo financeiro** | ⚠️ **MÉDIO** - Aluno visualiza financeiro (read-only) | `TESTES_PWA_ALUNO.md`<br>`TESTES_ADMIN_FINANCEIRO.md` | `tests/api/test-financeiro-faturas-api.php`<br>`tests/api/test-financeiro-resumo-aluno-api.php` |
| **9. Acesso administrativo (login/permissões)** | ✅ **ALTO** - Login e autenticação do aluno | `TESTES_REGRESSAO_LOGIN.md` | (Testes de autenticação nas APIs) |

### Checklist Manual Relacionado

**Arquivo:** `docs/testes/TESTES_PWA_ALUNO.md`

**Cenários obrigatórios (baseado no estado atual + futuro):**
- Login do aluno
- Visualização do dashboard
- Visualização de próximas aulas (teóricas e práticas)
- Visualização de aulas (histórico e futuras)
- Visualização de presenças teóricas e frequência
- Visualização de financeiro (faturas, parcelas, status)
- (Futuro - Tarefa 3.2) Visualização de fase atual no dashboard
- (Futuro - Tarefa 3.2) Visualização de próxima parcela no dashboard
- (Futuro - Tarefa 3.3) Visualização melhorada de frequência teórica
- (Futuro - Tarefa 3.4) Visualização separada de aulas práticas (histórico vs futuras)
- (Futuro - Tarefa 3.5) Visualização de exames em tela dedicada
- (Futuro - Tarefa 3.6) Visualização melhorada de financeiro

---

## 7. Roadmap de Implementação (Apenas PWA Aluno)

### Tarefa 3.2 – Dashboard com "Próxima Aula" e Fase Atual

**3.2.1 – Criar/ajustar API para dados do dashboard**
- Expandir `admin/api/aluno-agenda.php` ou criar `admin/api/aluno-dashboard.php`
- Retornar: `proxima_aula`, `fase_atual`, `proxima_fatura`
- Validar que dados são apenas do aluno logado

**3.2.2 – Criar testes automatizados**
- Criar/expandir `tests/api/test-aluno-agenda-api.php` ou `test-aluno-dashboard-api.php`
- Cenários: GET autenticado, GET não autenticado, validação de permissão

**3.2.3 – Implementar lógica de "Fase Atual"**
- Criar função/helper para determinar fase atual baseado em:
  - Status de exames (médico, psicotécnico)
  - Frequência teórica (se >= 75%)
  - Progresso prático (se tem aulas práticas)
  - Status da matrícula

**3.2.4 – Ajustar frontend (`aluno/dashboard.php`)**
- Adicionar card "Próxima Aula" em destaque
- Adicionar card "Fase Atual" com indicador visual
- Adicionar card "Próxima Parcela" (integração com financeiro)
- Implementar chamadas AJAX para API

**3.2.5 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual (`TESTES_PWA_ALUNO.md` - cenários de dashboard)
- Validar regressão (fluxos críticos 7 e 9)

### Tarefa 3.3 – Tela de Turmas Teóricas e Frequência

**3.3.1 – Melhorar `aluno/presencas-teoricas.php` ou criar `aluno/turmas-teoricas.php`**
- Reutilizar API `admin/api/turma-presencas.php` (GET) ou criar endpoint específico
- Adicionar cálculo de frequência mínima necessária (75%)
- Adicionar progresso visual (barra de progresso)

**3.3.2 – Criar testes automatizados**
- Expandir `tests/api/test-turma-presencas-api.php`
- Validar: GET autenticado como aluno, apenas dados do aluno logado

**3.3.3 – Ajustar frontend**
- Melhorar layout de frequência (gráficos/barras de progresso)
- Adicionar aviso visual se frequência abaixo do mínimo
- Melhorar visualização de presenças detalhadas

**3.3.4 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão

### Tarefa 3.4 – Tela de Aulas Práticas (Histórico e Futuras)

**3.4.1 – Melhorar `aluno/aulas.php`**
- Reutilizar API `admin/api/aluno-agenda.php` ou criar endpoint específico
- Adicionar separação clara: abas ou seções "Próximas" vs "Realizadas"
- Adicionar estatísticas (total realizado, total agendado, progresso)

**3.4.2 – Criar testes automatizados**
- Expandir `tests/api/test-aluno-agenda-api.php`
- Validar: filtros por status, apenas dados do aluno logado

**3.4.3 – Ajustar frontend**
- Implementar abas ou seções para separar futuras vs realizadas
- Adicionar cards de estatísticas
- Melhorar visualização de detalhes (KM, horários reais, etc.)

**3.4.4 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão

### Tarefa 3.5 – Tela de Exames

**3.5.1 – Criar `aluno/exames.php`**
- Adaptar `admin/api/exames.php` para uso do aluno (read-only, filtrado por `aluno_id`)
- Ou criar `admin/api/exames-aluno.php` específico

**3.5.2 – Criar testes automatizados**
- Criar/expandir `tests/api/test-exames-api.php`
- Validar: GET autenticado como aluno, apenas exames do aluno logado

**3.5.3 – Implementar frontend**
- Criar `aluno/exames.php` com cards por tipo de exame
- Exibir status com badges coloridos
- Adicionar datas e resultados (quando disponíveis)

**3.5.4 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão

### Tarefa 3.6 – Tela de Financeiro Simplificada

**3.6.1 – Melhorar `aluno/financeiro.php`**
- Reutilizar APIs existentes: `admin/api/financeiro-resumo-aluno.php` e `admin/api/financeiro-faturas.php`
- Adicionar destaque para faturas em atraso e próximas a vencer
- Melhorar cards de resumo

**3.6.2 – Criar testes automatizados**
- Expandir `tests/api/test-financeiro-resumo-aluno-api.php` e `test-financeiro-faturas-api.php`
- Validar: GET autenticado como aluno, apenas faturas do aluno logado

**3.6.3 – Ajustar frontend**
- Melhorar layout de cards de resumo
- Adicionar destaque visual para faturas em atraso (vermelho)
- Adicionar destaque para próximas faturas a vencer (amarelo)
- Melhorar listagem de faturas (ordenar por data de vencimento)

**3.6.4 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão (fluxo crítico 6)

---

## 8. Regras de Segurança e Não-Regressão

### Regras Absolutas

1. **Nenhuma mudança futura no PWA Aluno pode alterar comportamento atual sem estar documentado nesta especificação.**

2. **Toda implementação deve seguir a ordem obrigatória (Regra de Ouro):**
   - Planejar impacto
   - Criar/ajustar testes automatizados
   - **Só depois** alterar código
   - Rodar testes automatizados
   - Rodar checklists manuais de regressão

3. **Validações de permissão obrigatórias:**
   - Aluno só pode acessar seus próprios dados (validar `aluno_id` em todas as APIs)
   - Aluno tem acesso read-only a todas as funcionalidades (não pode criar/editar/excluir)
   - Aluno não pode acessar dados de outros alunos

4. **Multi-tenant:**
   - Todos os endpoints devem respeitar `cfc_id` da sessão
   - Aluno só vê dados do CFC ao qual pertence

5. **Não-regressão:**
   - Funcionalidades existentes (visualização de aulas, financeiro, presenças) não podem ser quebradas
   - Dashboard deve continuar funcionando normalmente
   - Login e autenticação não podem ser afetados

6. **Testes obrigatórios antes de cada deploy:**
   - Executar `tests/api/test-aluno-agenda-api.php`
   - Executar `tests/api/test-financeiro-resumo-aluno-api.php`
   - Executar `tests/api/test-financeiro-faturas-api.php`
   - Executar `tests/api/test-turma-presencas-api.php` (GET como aluno)
   - Executar checklist manual `TESTES_PWA_ALUNO.md`
   - Executar smoke test de fluxos críticos (4, 6, 7, 9)

### Áreas Sensíveis

- **Tabela `aulas`:** Visualização de aulas práticas - garantir que aluno só vê suas próprias aulas
- **Tabela `turma_presencas`:** Visualização de presenças - garantir que aluno só vê suas próprias presenças
- **Tabela `financeiro_faturas`:** Visualização financeira - garantir que aluno só vê suas próprias faturas
- **Autenticação:** Alterações podem quebrar acesso de todos os alunos

---

**Referências:**
- `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Fase 3, Tarefas 3.1 a 3.6
- `docs/TEST_PLAN_ENDPOINTS_CFC.md` - Mapeamento de endpoints
- `docs/TEST_PLAN_API_AUTOMATIZADOS.md` - Planejamento de testes automatizados
- `docs/TEST_PLAN_REGRESSAO_MANUAL.md` - Planejamento de checklists manuais
- `docs/testes/TESTES_PWA_ALUNO.md` - Checklist manual do PWA Aluno

