# PWA Instrutor – Especificação Funcional e Técnica

**Versão:** 1.0  
**Data:** Janeiro 2025  
**Status:** Especificação - Contrato para Implementação

---

## 1. Objetivo

O **PWA Instrutor** é a interface mobile-first e web que permite aos instrutores do CFC Bom Conselho gerenciar suas atividades diárias de forma autônoma e eficiente. Ele serve como ferramenta principal para que o instrutor visualize sua agenda, execute aulas práticas e teóricas, e tenha acesso rápido a informações essenciais dos alunos e veículos durante as aulas.

**Principais objetivos:**
- Permitir que o instrutor veja sua agenda de aulas do dia em dispositivos móveis e desktop
- Facilitar o registro de execução de aulas práticas (início, fim, KM, presenças)
- Fornecer acesso rápido a dados essenciais do aluno e veículo durante a aula
- Suportar o registro de presenças teóricas quando o instrutor for responsável por turmas teóricas

---

## 2. Estado Atual (Como o Sistema Funciona Hoje)

### 2.1. Telas/Páginas Existentes

| Arquivo | URL | Descrição |
|---------|-----|-----------|
| `instrutor/login.php` | `/instrutor/login.php` | Tela de login do instrutor (GET exibe formulário, POST processa autenticação) |
| `instrutor/dashboard.php` | `/instrutor/dashboard.php` | Dashboard principal com resumo do dia (aulas práticas e teóricas, estatísticas) |
| `instrutor/dashboard-mobile.php` | `/instrutor/dashboard-mobile.php` | Versão alternativa mobile (pode ser redundante com dashboard.php) |
| `instrutor/aulas.php` | `/instrutor/aulas.php` | Listagem completa de aulas do instrutor com filtros (data, status, tipo) |
| `instrutor/perfil.php` | `/instrutor/perfil.php` | Visualização/edição de perfil do instrutor |
| `instrutor/trocar-senha.php` | `/instrutor/trocar-senha.php` | Tela para troca de senha (obrigatória se `precisa_trocar_senha = 1`) |
| `instrutor/notificacoes.php` | `/instrutor/notificacoes.php` | Listagem de notificações do instrutor |
| `instrutor/ocorrencias.php` | `/instrutor/ocorrencias.php` | Registro de ocorrências relacionadas às aulas |

### 2.2. Funcionalidades Atuais

#### Dashboard (`instrutor/dashboard.php`)

**O que o instrutor consegue ver hoje:**
- **Próxima aula do dia:** Primeira aula do dia (prática ou teórica) com horário, tipo, aluno/turma
- **Aulas de hoje:** Lista completa de aulas do dia atual (práticas e teóricas) com:
  - Horário (hora_inicio – hora_fim)
  - Tipo (badge: TEOR/PRAT)
  - Status (PENDENTE/CONCLUÍDA)
  - Disciplina/Turma (para teóricas) ou Aluno/Veículo (para práticas)
  - Botões de ação diferenciados (Chamada/Diário para teóricas, Transferir/Cancelar para práticas)
- **Resumo de hoje:** Estatísticas do dia (total de aulas, concluídas, pendentes)
- **Próximas aulas (7 dias):** Lista agrupada por data das próximas aulas
- **Notificações:** Últimas notificações não lidas

**O que o instrutor consegue fazer hoje:**
- Visualizar aulas práticas e teóricas do dia
- **Cancelar/transferir aulas práticas** via botões na lista (chamada AJAX para `admin/api/instrutor-aulas.php`)
- Visualizar detalhes básicos (nome do aluno, placa do veículo, disciplina/turma)

#### Página de Aulas (`instrutor/aulas.php`)

**O que o instrutor consegue ver hoje:**
- Listagem completa de aulas (práticas e teóricas) com filtros:
  - Período (data_inicio, data_fim)
  - Status (agendada, em_andamento, concluida, cancelada)
  - Tipo (pratica, teorica)
- Para cada aula:
  - Data, horário, tipo (prática/teórica)
  - Dados do aluno (nome, telefone) - apenas práticas
  - Dados do veículo (modelo, placa) - apenas práticas
  - Dados da turma/disciplina (nome, disciplina) - apenas teóricas
  - Status da aula

**O que o instrutor consegue fazer hoje:**
- Filtrar aulas por período e status
- **Cancelar/transferir aulas práticas** via botões na listagem
- Visualizar detalhes da aula

### 2.3. APIs Utilizadas Atualmente

| Endpoint | Método | Uso Atual | Tela/Contexto |
|----------|--------|-----------|---------------|
| `admin/api/agendamento.php` | GET | Listar aulas do instrutor (filtrado por `instrutor_id` na sessão) | `dashboard.php`, `aulas.php` |
| `admin/api/instrutor-aulas.php` | POST | Cancelar/transferir aula prática | `dashboard.php`, `aulas.php` (botões de ação) |
| `admin/api/notificacoes.php` | GET | Buscar notificações do instrutor | `dashboard.php`, `notificacoes.php` |
| `admin/api/ocorrencias-instrutor.php` | POST/GET | Registrar/buscar ocorrências | `ocorrencias.php` |
| `admin/api/turma-presencas.php` | GET/POST/PUT | Consultar/registrar presenças teóricas (quando instrutor tem permissão) | Dashboard (botão Chamada para aulas teóricas) |

### 2.4. Fluxo Atual Típico

1. **Login:** Instrutor acessa `instrutor/login.php`, insere email/senha, sistema valida e cria sessão
2. **Dashboard:** Após login, redireciona para `instrutor/dashboard.php`
   - Sistema busca aulas práticas do dia da tabela `aulas` (WHERE `instrutor_id = ?` AND `data_aula = hoje`)
   - Sistema busca aulas teóricas do dia da tabela `turma_aulas_agendadas` (WHERE `instrutor_id = ?` AND `data_aula = hoje`)
   - Combina e ordena por horário
   - Exibe próxima aula e lista completa do dia
3. **Visualização de aulas:** Instrutor pode navegar para `instrutor/aulas.php` para ver histórico/futuro
4. **Ações disponíveis:** 
   - **Cancelar aula prática:** Clica em botão "Cancelar" → chamada AJAX para `admin/api/instrutor-aulas.php` com `acao=cancelar`
   - **Transferir aula prática:** Clica em botão "Transferir" → modal/form → chamada AJAX para `admin/api/instrutor-aulas.php` com `acao=transferir`

### 2.5. Limitações Conhecidas (Estado Atual)

- ❌ **Não existe funcionalidade de "Iniciar Aula Prática"** - Instrutor não pode registrar início de aula
- ❌ **Não existe funcionalidade de "Finalizar Aula Prática"** - Instrutor não pode registrar fim de aula
- ❌ **Não existe registro de KM inicial/final** - Campos `km_inicial` e `km_final` não são preenchidos via PWA
- ❌ **Não existe marcação de falta do aluno** - Instrutor não pode marcar falta do aluno em aula prática
- ❌ **Dados do aluno são limitados** - Apenas nome e telefone, não há acesso a CPF, foto, categoria CNH via PWA
- ❌ **Dados do veículo são limitados** - Apenas modelo e placa, não há acesso a tipo de veículo detalhado
- ⚠️ **Presenças teóricas:** A funcionalidade existe parcialmente (API `turma-presencas.php` permite POST do instrutor), mas não há tela dedicada no PWA para chamada teórica

---

## 3. Funcionalidades Desejadas (Fase 2 do Plano)

As funcionalidades abaixo serão implementadas nas tarefas 2.2 a 2.6 do `docs/PLANO_IMPL_PRODUCAO_CFC.md`.

### 3.1. Início e Fim de Aula Prática (Tarefa 2.2)

**Problema que resolve:** Permitir que o instrutor registre o momento exato de início e fim de uma aula prática, essencial para controle de carga horária e faturamento.

**Onde aparece:** Dentro de `instrutor/dashboard.php` e `instrutor/aulas.php`, em aulas práticas com status `agendada`, aparecerão botões:
- **"Iniciar Aula"** (quando status = `agendada`)
- **"Finalizar Aula"** (quando status = `em_andamento`)

**Ações do usuário:**
1. Instrutor clica em "Iniciar Aula" → Modal/confirmação aparece (opcional: campo para registrar KM inicial)
2. Confirma → Sistema registra `status = 'em_andamento'`, `hora_inicio_real = NOW()`, `km_inicial` (se fornecido)
3. Botão muda para "Finalizar Aula"
4. Instrutor clica em "Finalizar Aula" → Modal/confirmação aparece (campo obrigatório: KM final)
5. Confirma → Sistema registra `status = 'concluida'`, `hora_fim_real = NOW()`, `km_final`

**Validações necessárias:**
- Instrutor só pode iniciar/finalizar suas próprias aulas (validar `aulas.instrutor_id = instrutor_logado`)
- Não pode finalizar aula que não foi iniciada
- Não pode iniciar aula que já foi iniciada/finalizada

### 3.2. Registro de KM Inicial e Final (Tarefa 2.3)

**Problema que resolve:** Controle preciso de uso de veículos para manutenção, gestão de frota e possível cobrança por quilometragem.

**Onde aparece:** Integrado no fluxo de início/fim de aula:
- **KM Inicial:** Campo opcional no modal de "Iniciar Aula"
- **KM Final:** Campo obrigatório no modal de "Finalizar Aula"

**Ações do usuário:**
1. Ao iniciar aula, instrutor pode (opcionalmente) informar KM inicial do veículo
2. Ao finalizar aula, instrutor deve informar KM final do veículo
3. Sistema valida: `km_final >= km_inicial`

**Validações necessárias:**
- KM deve ser número positivo
- `km_final >= km_inicial` (quando ambos estiverem preenchidos)
- KM inicial pode ser opcional (se não informado, não registra)
- KM final é obrigatório para finalizar aula

### 3.3. Marcação de Falta do Aluno (Tarefa 2.4)

**Problema que resolve:** Permitir que o instrutor registre quando o aluno não compareceu à aula prática agendada, essencial para controle de frequência e possível reagendamento.

**Onde aparece:** Dentro de `instrutor/dashboard.php` e `instrutor/aulas.php`, em aulas práticas com status `agendada`, aparecerá botão adicional:
- **"Marcar Falta"** (quando status = `agendada`)

**Ações do usuário:**
1. Instrutor clica em "Marcar Falta" → Modal de confirmação aparece
2. Confirma → Sistema registra `status = 'falta_aluno'` (ou campo `presente = false`, conforme estrutura do banco)
3. Aula aparece marcada como falta nos relatórios

**Validações necessárias:**
- Instrutor só pode marcar falta de suas próprias aulas
- Não pode marcar falta de aula já iniciada/finalizada
- Não pode marcar falta de aula cancelada

### 3.4. Visualização de Dados Essenciais do Aluno (Tarefa 2.5)

**Problema que resolve:** Fornecer ao instrutor informações rápidas do aluno durante a aula (nome completo, CPF, categoria CNH pretendida, foto) sem precisar acessar o painel admin.

**Onde aparece:** 
- **Opção 1:** Modal/popup ao clicar no nome do aluno na lista de aulas
- **Opção 2:** Tela/página dedicada `instrutor/detalhes-aluno.php?id=X`

**Informações exibidas:**
- Nome completo
- CPF
- Telefone
- Email
- Categoria CNH pretendida (da matrícula)
- Foto (se existir)
- Status da matrícula (ativo/inativo)

**Ações do usuário:**
1. Instrutor clica no nome do aluno (ou botão "Ver Detalhes")
2. Modal/tela exibe dados essenciais do aluno
3. Opcional: Botão para ligar (tel:...) ou enviar WhatsApp

**Validações necessárias:**
- Instrutor só pode ver alunos que têm aulas com ele (validar relação `aulas.instrutor_id = instrutor_logado` AND `aulas.aluno_id = aluno_solicitado`)

### 3.5. Visualização de Dados do Veículo (Tarefa 2.5 - Integrado)

**Problema que resolve:** Fornecer informações detalhadas do veículo durante a aula (placa, modelo, marca, tipo, ano).

**Onde aparece:** Integrado na mesma tela/modal de detalhes do aluno ou em tooltip/badge na listagem de aulas.

**Informações exibidas:**
- Placa
- Modelo
- Marca
- Ano
- Tipo/Categoria (se houver)

**Ações do usuário:**
1. Instrutor visualiza dados do veículo na listagem de aulas (já existe parcialmente)
2. Pode acessar detalhes completos via modal/tela

### 3.6. (Opcional) Chamada de Turma Teórica via PWA (Tarefa 2.6)

**Problema que resolve:** Permitir que o instrutor registre presenças teóricas diretamente pelo PWA, sem precisar acessar o painel admin.

**Onde aparece:** 
- **Opção 1:** Botão "Chamada" na lista de aulas teóricas do dashboard
- **Opção 2:** Tela/página dedicada `instrutor/chamada-teorica.php?turma_id=X&aula_id=Y`

**Ações do usuário:**
1. Instrutor clica em "Chamada" em uma aula teórica
2. Lista de alunos matriculados na turma aparece
3. Instrutor marca presença/falta de cada aluno
4. Confirma → Sistema registra presenças via `admin/api/turma-presencas.php`
5. Frequência é recalculada automaticamente

**Validações necessárias:**
- Instrutor só pode fazer chamada de aulas teóricas que ele ministra (validar `turma_aulas_agendadas.instrutor_id = instrutor_logado`)
- Chamada só pode ser feita no dia da aula ou após (não antes)

**Nota:** Esta funcionalidade é opcional e deve ser aprovada antes de implementação.

---

## 4. Mapeamento de Telas e Rotas

| Tela | Arquivo | URL | Descrição |
|------|---------|-----|-----------|
| Login Instrutor | `instrutor/login.php` | `/instrutor/login.php` | Tela de login do instrutor (GET: formulário, POST: processa autenticação) |
| Dashboard Instrutor | `instrutor/dashboard.php` | `/instrutor/dashboard.php` | Dashboard principal com resumo do dia, próximas aulas, ações rápidas |
| Aulas do Instrutor | `instrutor/aulas.php` | `/instrutor/aulas.php` | Listagem completa de aulas com filtros (período, status, tipo) |
| Perfil Instrutor | `instrutor/perfil.php` | `/instrutor/perfil.php` | Visualização/edição de dados do perfil do instrutor |
| Trocar Senha | `instrutor/trocar-senha.php` | `/instrutor/trocar-senha.php` | Tela obrigatória para troca de senha (se `precisa_trocar_senha = 1`) |
| Notificações | `instrutor/notificacoes.php` | `/instrutor/notificacoes.php` | Listagem de notificações do instrutor |
| Ocorrências | `instrutor/ocorrencias.php` | `/instrutor/ocorrencias.php` | Registro de ocorrências relacionadas às aulas |
| Logout | `instrutor/logout.php` | `/instrutor/logout.php` | Logout do instrutor (destrói sessão e redireciona) |

**Futuro (Fase 2):**
| Tela | Arquivo | URL | Descrição |
|------|---------|-----|-----------|
| Detalhes do Aluno | `instrutor/detalhes-aluno.php` | `/instrutor/detalhes-aluno.php?id=X` | (Tarefa 2.5) Tela/modal com dados essenciais do aluno |
| Chamada Teórica | `instrutor/chamada-teorica.php` | `/instrutor/chamada-teorica.php?turma_id=X&aula_id=Y` | (Tarefa 2.6 - Opcional) Tela de chamada/presença teórica |

---

## 5. Mapeamento de APIs Usadas pelo PWA Instrutor

| Endpoint | Método | Uso no PWA Instrutor | Tela/Fluxo | Cenários de Teste Automatizado (Referência) |
|----------|--------|---------------------|------------|---------------------------------------------|
| `admin/api/agendamento.php` | GET | Listar aulas do instrutor (filtrado por `instrutor_id`) | `dashboard.php`, `aulas.php` | `tests/api/test-agendamento-api.php` (GET autenticado, GET não autenticado) |
| `admin/api/instrutor-aulas.php` | POST | Cancelar/transferir aula prática | `dashboard.php`, `aulas.php` (botões de ação) | `tests/api/test-instrutor-aulas-api.php` (POST autenticado, POST sem permissão, validação) |
| `admin/api/notificacoes.php` | GET | Buscar notificações do instrutor | `dashboard.php`, `notificacoes.php` | (Não mapeado ainda - pode ser adicionado) |
| `admin/api/ocorrencias-instrutor.php` | POST/GET | Registrar/buscar ocorrências | `ocorrencias.php` | (Não mapeado ainda - pode ser adicionado) |
| `admin/api/turma-presencas.php` | GET/POST/PUT | Consultar/registrar presenças teóricas | Dashboard (botão Chamada), Tarefa 2.6 | `tests/api/test-turma-presencas-api.php` (GET autenticado, POST autenticado, validação) |

**Futuro (Fase 2):**
| Endpoint | Método | Uso no PWA Instrutor | Tela/Fluxo | Cenários de Teste Automatizado (Referência) |
|----------|--------|---------------------|------------|---------------------------------------------|
| `admin/api/instrutor-aulas.php` (expandido) | POST | Iniciar aula prática | `dashboard.php`, `aulas.php` (Tarefa 2.2) | `tests/api/test-instrutor-aulas-api.php` (novos cenários: iniciar aula, validação) |
| `admin/api/instrutor-aulas.php` (expandido) | POST | Finalizar aula prática (com KM) | `dashboard.php`, `aulas.php` (Tarefa 2.2, 2.3) | `tests/api/test-instrutor-aulas-api.php` (novos cenários: finalizar aula, validação KM) |
| `admin/api/instrutor-aulas.php` (expandido) | POST | Marcar falta do aluno | `dashboard.php`, `aulas.php` (Tarefa 2.4) | `tests/api/test-instrutor-aulas-api.php` (novos cenários: marcar falta, validação) |
| `admin/api/alunos.php` (ou nova API) | GET | Buscar dados essenciais do aluno | `detalhes-aluno.php` (Tarefa 2.5) | `tests/api/test-alunos-api.php` (GET autenticado, validação de permissão) |

---

## 6. Impacto em Fluxos Críticos e Testes

### Fluxos Críticos Impactados

| Fluxo Crítico | Impacto do PWA Instrutor | Checklist Manual | Testes Automatizados |
|---------------|--------------------------|------------------|---------------------|
| **3. Agendamento e execução de aulas práticas** | ✅ **ALTO** - Instrutor executa aulas via PWA (início, fim, KM, falta) | `TESTES_PWA_INSTRUTOR.md`<br>`TESTES_REGRESSAO_FLUXOS_CRITICOS.md` (cenário 3) | `tests/api/test-instrutor-aulas-api.php`<br>`tests/api/test-agendamento-api.php` |
| **4. Registro de presenças teóricas** | ⚠️ **MÉDIO** - Instrutor pode registrar presenças via PWA (Tarefa 2.6) | `TESTES_PWA_INSTRUTOR.md`<br>`TESTES_REGRESSAO_FLUXOS_CRITICOS.md` (cenário 4) | `tests/api/test-turma-presencas-api.php` |
| **8. Acesso e uso do painel do instrutor** | ✅ **ALTO** - PWA Instrutor é o próprio painel do instrutor | `TESTES_PWA_INSTRUTOR.md`<br>`TESTES_REGRESSAO_FLUXOS_CRITICOS.md` (cenário 8) | `tests/api/test-agendamento-api.php` (GET autenticado como instrutor) |
| **9. Acesso administrativo (login/permissões)** | ✅ **ALTO** - Login e autenticação do instrutor | `TESTES_REGRESSAO_LOGIN.md` | (Testes de autenticação nas APIs) |

### Checklist Manual Relacionado

**Arquivo:** `docs/testes/TESTES_PWA_INSTRUTOR.md`

**Cenários obrigatórios (baseado no estado atual + futuro):**
- Login do instrutor
- Visualização da agenda de aulas do dia
- Visualização de aulas (práticas e teóricas)
- Cancelar/transferir aula prática (já existe)
- (Futuro - Tarefa 2.2) Iniciar aula prática
- (Futuro - Tarefa 2.2) Finalizar aula prática
- (Futuro - Tarefa 2.3) Registrar KM inicial e final
- (Futuro - Tarefa 2.4) Marcar falta do aluno
- (Futuro - Tarefa 2.5) Visualizar dados do aluno
- (Futuro - Tarefa 2.6 - Opcional) Registrar presença teórica

---

## 7. Roadmap de Implementação (Apenas PWA Instrutor)

### Tarefa 2.2 – Fluxo de Início/Fim de Aula Prática

**2.2.1 – Criar testes automatizados para iniciar/finalizar aula**
- Criar/expandir `tests/api/test-instrutor-aulas-api.php`
- Cenários: iniciar aula (autenticado, não autenticado, permissão), finalizar aula (autenticado, validação)

**2.2.2 – Implementar backend de "iniciar aula"**
- Expandir `admin/api/instrutor-aulas.php` com ação `iniciar`
- Validar `aulas.instrutor_id = instrutor_logado`
- Atualizar `aulas.status = 'em_andamento'`, `aulas.hora_inicio_real = NOW()`

**2.2.3 – Implementar backend de "finalizar aula"**
- Expandir `admin/api/instrutor-aulas.php` com ação `finalizar`
- Validar que aula foi iniciada
- Atualizar `aulas.status = 'concluida'`, `aulas.hora_fim_real = NOW()`

**2.2.4 – Ajustar frontend (`instrutor/dashboard.php`, `instrutor/aulas.php`)**
- Adicionar botão "Iniciar Aula" em aulas com `status = 'agendada'`
- Adicionar botão "Finalizar Aula" em aulas com `status = 'em_andamento'`
- Implementar chamadas AJAX para as APIs

**2.2.5 – Rodar testes e validar**
- Executar testes automatizados (`tests/api/test-instrutor-aulas-api.php`)
- Executar checklist manual (`TESTES_PWA_INSTRUTOR.md` - cenários de início/fim)
- Validar regressão (fluxos críticos 3 e 8)

### Tarefa 2.3 – Registro de KM Inicial/Final

**2.3.1 – Verificar/criar campos no banco**
- Verificar se `aulas.km_inicial` e `aulas.km_final` existem
- Criar migration se necessário

**2.3.2 – Criar testes automatizados para KM**
- Adicionar cenários em `tests/api/test-instrutor-aulas-api.php`
- Validar: KM positivo, `km_final >= km_inicial`, KM inicial opcional

**2.3.3 – Implementar backend de registro de KM**
- Expandir `admin/api/instrutor-aulas.php` para aceitar `km_inicial` (opcional) e `km_final` (obrigatório)
- Adicionar validações

**2.3.4 – Ajustar frontend**
- Adicionar campo "KM Inicial" no modal de "Iniciar Aula" (opcional)
- Adicionar campo "KM Final" no modal de "Finalizar Aula" (obrigatório)
- Implementar validação client-side (opcional, mas recomendado)

**2.3.5 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão

### Tarefa 2.4 – Marcação de Falta do Aluno

**2.4.1 – Definir estratégia de status**
- Decidir se usar `aulas.status = 'falta_aluno'` ou campo `aulas.presente = false`
- Criar migration se necessário

**2.4.2 – Criar testes automatizados**
- Adicionar cenários em `tests/api/test-instrutor-aulas-api.php`
- Validar: permissão, não pode marcar falta de aula já finalizada

**2.4.3 – Implementar backend**
- Expandir `admin/api/instrutor-aulas.php` com ação `marcar_falta`
- Validar permissões e status

**2.4.4 – Ajustar frontend**
- Adicionar botão "Marcar Falta" em aulas com `status = 'agendada'`
- Implementar chamada AJAX

**2.4.5 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão

### Tarefa 2.5 – Tela de Detalhes do Aluno (Dados Essenciais)

**2.5.1 – Criar testes automatizados**
- Criar `tests/api/test-detalhes-aluno-instrutor-api.php` (ou usar `test-alunos-api.php` com contexto instrutor)
- Validar: permissão (instrutor só vê alunos de suas aulas)

**2.5.2 – Implementar backend**
- Criar `admin/api/detalhes-aluno-instrutor.php` (ou expandir `admin/api/alunos.php` com endpoint específico)
- Validar relação instrutor-aluno via `aulas`

**2.5.3 – Implementar frontend**
- Criar modal ou página `instrutor/detalhes-aluno.php`
- Exibir dados essenciais (nome, CPF, telefone, email, categoria CNH, foto)
- Integrar na listagem de aulas (link/botão "Ver Detalhes")

**2.5.4 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão

### Tarefa 2.6 – (Opcional) Chamada de Turma Teórica via PWA

**2.6.1 – Aprovação da funcionalidade**
- Validar com stakeholders se faz sentido no fluxo do CFC

**2.6.2 – Criar testes automatizados**
- Expandir `tests/api/test-turma-presencas-api.php`
- Validar: permissão (instrutor só faz chamada de suas aulas teóricas)

**2.6.3 – Implementar backend**
- Reutilizar `admin/api/turma-presencas.php` (já suporta POST do instrutor)
- Validar `turma_aulas_agendadas.instrutor_id`

**2.6.4 – Implementar frontend**
- Criar `instrutor/chamada-teorica.php` ou modal
- Listar alunos da turma
- Permitir marcar presença/falta
- Integrar no dashboard (botão "Chamada" em aulas teóricas)

**2.6.5 – Rodar testes e validar**
- Executar testes automatizados
- Executar checklist manual
- Validar regressão (fluxo crítico 4)

---

## 8. Regras de Segurança e Não-Regressão

### Regras Absolutas

1. **Nenhuma mudança futura no PWA Instrutor pode alterar comportamento atual sem estar documentado nesta especificação.**

2. **Toda implementação deve seguir a ordem obrigatória (Regra de Ouro):**
   - Planejar impacto
   - Criar/ajustar testes automatizados
   - **Só depois** alterar código
   - Rodar testes automatizados
   - Rodar checklists manuais de regressão

3. **Validações de permissão obrigatórias:**
   - Instrutor só pode acessar/alterar suas próprias aulas (validar `instrutor_id` em todas as APIs)
   - Instrutor só pode ver alunos que têm aulas com ele
   - Instrutor só pode fazer chamada teórica de aulas que ele ministra

4. **Multi-tenant:**
   - Todos os endpoints devem respeitar `cfc_id` da sessão
   - Instrutor só vê dados do CFC ao qual pertence

5. **Não-regressão:**
   - Funcionalidades existentes (cancelar/transferir aula) não podem ser quebradas
   - Dashboard e listagem de aulas devem continuar funcionando normalmente
   - Login e autenticação não podem ser afetados

6. **Testes obrigatórios antes de cada deploy:**
   - Executar `tests/api/test-instrutor-aulas-api.php` (quando implementado)
   - Executar `tests/api/test-agendamento-api.php` (GET como instrutor)
   - Executar checklist manual `TESTES_PWA_INSTRUTOR.md`
   - Executar smoke test de fluxos críticos (3, 4, 8, 9)

### Áreas Sensíveis

- **Tabela `aulas`:** Alterações em status, horários e KM afetam relatórios e faturamento
- **Tabela `turma_presencas`:** Alterações afetam cálculo de frequência e aprovação
- **Autenticação:** Alterações podem quebrar acesso de todos os instrutores

---

**Referências:**
- `docs/PLANO_IMPL_PRODUCAO_CFC.md` - Fase 2, Tarefas 2.1 a 2.6
- `docs/TEST_PLAN_ENDPOINTS_CFC.md` - Mapeamento de endpoints
- `docs/TEST_PLAN_API_AUTOMATIZADOS.md` - Planejamento de testes automatizados
- `docs/TEST_PLAN_REGRESSAO_MANUAL.md` - Planejamento de checklists manuais
- `docs/testes/TESTES_PWA_INSTRUTOR.md` - Checklist manual do PWA Instrutor

