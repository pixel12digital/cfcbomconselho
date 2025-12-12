# Plano de Implementação para Produção – CFC Bom Conselho

**Versão:** 1.0  
**Data de criação:** Janeiro 2025  
**Última atualização:** Janeiro 2025

---

## Objetivo do Plano

Este documento é o **plano mestre de implementação** para levar o sistema **CFC Bom Conselho** até produção de forma segura e controlada.

### Objetivos Principais

1. **Finalizar o CFC Bom Conselho para uso em produção** com funcionalidades essenciais completas e estáveis.

2. **Garantir que NENHUMA funcionalidade existente seja quebrada** durante as próximas implementações.

3. **Organizar as entregas em fases controladas**, permitindo validação incremental antes de avançar.

4. **Estabelecer a ordem correta de trabalho:**
   - Planejamento detalhado da tarefa
   - Definição de escopo e impacto
   - Criação/ajuste de testes automatizados
   - Implementação da funcionalidade
   - Execução de testes automatizados
   - Execução de testes manuais guiados por checklist
   - Validação de que nenhum fluxo crítico foi afetado

### Documentação Base

Este plano complementa e deve ser lido em conjunto com:

- **`docs/ONBOARDING_DEV_CFC.md`** - Documento de onboarding que explica a arquitetura, módulos, fluxos críticos e estrutura técnica do sistema.

**Não reescreve o onboarding**, mas referencia seus conceitos para garantir que todas as implementações respeitem o que já está documentado.

---

## Princípios e Regras Gerais

### Regras Absolutas

1. **Nenhuma funcionalidade existente pode ser quebrada.**
   - Qualquer alteração deve manter 100% de compatibilidade com o comportamento atual, a menos que seja explicitamente definido como mudança intencional na tarefa.

2. **Nenhuma alteração "grande" sem tarefa específica.**
   - Cada tarefa deve ter um escopo bem definido.
   - Não se deve "aproveitar" uma tarefa para mexer em áreas não relacionadas.

3. **Nada de "otimizações mágicas" ou "refatorar geral" sem planejamento.**
   - Otimizações devem ser baseadas em dados concretos (logs, performance, feedback).
   - Refatorações grandes devem ser tarefas dedicadas, não "melhorias paralelas".

4. **Commits pequenos, focados em UMA tarefa por vez.**
   - Facilita revisão, rollback e identificação de problemas.

5. **Tudo que for alterado deve ter cobertura mínima de teste automatizado + checklist de teste manual.**
   - Testes automatizados garantem regressão.
   - Testes manuais garantem experiência do usuário.

6. **Toda mudança que possa impactar fluxo crítico deve, idealmente, ser feita atrás de um "flag" ou configuração**, quando for viável.
   - Permite rollback rápido sem deploy.

### Regra de Ouro para Implementações

**Toda tarefa de implementação deve seguir SEMPRE esta ordem:**

1. **Análise de impacto** no código já existente.
   - Identificar todos os arquivos, tabelas, APIs e fluxos que serão afetados.
   - Documentar dependências e riscos.

2. **Criação/ajuste de testes automatizados** para o escopo da tarefa.
   - Testes devem ser criados ANTES da implementação (TDD quando possível).
   - Garantir que os testes falhem inicialmente (comportamento atual) e passem após a implementação (comportamento novo).

3. **Somente depois disso, alteração de código.**
   - Implementar a funcionalidade seguindo o escopo definido.
   - Não expandir o escopo durante a implementação.

4. **Execução dos testes automatizados.**
   - Todos os testes devem passar.
   - Se algum teste não relacionado falhar, investigar antes de prosseguir.

5. **Execução de testes manuais guiados por checklist.**
   - Validar a experiência do usuário.
   - Validar que fluxos críticos não foram quebrados.

6. **Validação final de regressão.**
   - Executar smoke tests em todos os fluxos críticos listados abaixo.

**Nenhuma mudança deve ser feita "no feeling" sem testes.**

---

### Fluxos Críticos que NÃO podem ser quebrados

Baseado no `docs/ONBOARDING_DEV_CFC.md`, os seguintes fluxos são considerados **críticos** e devem ser preservados em todas as fases:

1. **Nova matrícula de aluno**
   - Cadastro de aluno via `admin/index.php?page=alunos`
   - Salvamento via `admin/api/alunos.php`
   - Relação com tabelas `alunos`, `matriculas`

2. **Cadastro e gestão de turmas teóricas**
   - Criação de turma via `admin/index.php?page=turmas-teoricas`
   - Agendamento de aulas via wizard
   - Tabelas: `turmas_teoricas`, `turma_aulas_agendadas`, `turma_matriculas`

3. **Agendamento e execução de aulas práticas**
   - Agendamento via `admin/index.php?page=agendamento`
   - Validações de conflito (instrutor, veículo, limite diário)
   - API: `admin/api/agendamento.php`
   - Tabela: `aulas`

4. **Registro de presenças teóricas e práticas**
   - Presença teórica via `admin/index.php?page=turma-chamada`
   - API: `admin/api/turma-presencas.php`
   - Cálculo automático de frequência via `TurmaTeoricaManager::recalcularFrequenciaAluno()`
   - Tabelas: `turma_presencas`, `turma_matriculas.frequencia_percentual`

5. **Fluxo de provas/exames**
   - Registro via modal de aluno (aba "Histórico")
   - API: `admin/api/exames.php`
   - Validações via `guards_exames.php`
   - Bloqueio de matrícula se exames não estiverem OK

6. **Fluxo financeiro (lançamento, cobrança, baixa)**
   - Criação de faturas via `admin/index.php?page=financeiro-faturas`
   - API: `admin/api/financeiro-faturas.php`
   - Registro de pagamentos via `admin/api/financeiro-pagamentos.php`
   - Job de faturas vencidas: `admin/jobs/marcar_faturas_vencidas.php`
   - Bloqueio automático por inadimplência

7. **Acesso e uso do painel do aluno (web/PWA)**
   - Login: `aluno/login.php` ou `index.php` (redirecionamento)
   - Dashboard: `aluno/dashboard.php`
   - Aulas: `aluno/aulas.php`
   - Financeiro: `aluno/financeiro.php`

8. **Acesso e uso do painel do instrutor (web/PWA)**
   - Login: `instrutor/login.php` ou `index.php` (redirecionamento)
   - Dashboard: `instrutor/dashboard.php`
   - Aulas: `instrutor/aulas.php`

9. **Acesso administrativo (login/admin, permissões)**
   - Login via `index.php`
   - Autenticação via `includes/auth.php`
   - Multi-tenant: filtro por `cfc_id` (Admin Global `cfc_id = 0` vs usuário específico `cfc_id > 0`)
   - Permissões baseadas em `usuarios.tipo`

**Todas as fases e tarefas deste plano devem garantir que nenhum desses fluxos seja impactado negativamente.**

---

## Estratégia de Testes (Automatizados e Manuais)

### Filosofia de Testes

A estratégia de testes para o CFC Bom Conselho será **incremental e pragmática**, focando em:

- **Prevenção de regressão** nos fluxos críticos.
- **Validação de comportamento esperado** em novas funcionalidades.
- **Validação da experiência do usuário** através de testes manuais guiados.

---

### 1. Testes Automatizados

#### Objetivo

Garantir que o comportamento crítico seja verificado automaticamente, permitindo detecção rápida de quebras durante desenvolvimento e antes de deploys.

#### Níveis de Teste

**1.1. Testes de Unidade (quando fizer sentido)**
- **Onde:** Services isolados, helpers, classes utilitárias.
- **Exemplos:**
  - `admin/includes/FinanceiroAlunoHelper.php` - Métodos de cálculo de resumo financeiro
  - `admin/includes/TurmaTeoricaManager.php` - Métodos de cálculo de frequência
  - `admin/includes/guards_exames.php` - Validações de elegibilidade

**1.2. Testes de Integração/API**
- **Onde:** Endpoints REST usados pelo frontend (admin, aluno, instrutor).
- **Foco:** Endpoints críticos dos fluxos listados na seção anterior.
- **Exemplos:**
  - `admin/api/alunos.php` - CRUD de alunos
  - `admin/api/agendamento.php` - Agendamento de aulas práticas
  - `admin/api/turma-presencas.php` - Registro de presenças teóricas
  - `admin/api/financeiro-faturas.php` - Gestão de faturas

#### Ferramentas

**Abordagem incremental e simples:**

1. **Estrutura inicial:** Pasta `tests/` na raiz do projeto.
2. **Testes de API:** Scripts PHP que fazem chamadas HTTP e validam respostas JSON.
   - Exemplo: `tests/api/test-alunos-api.php`
   - Usa `curl` ou `file_get_contents()` com contexto HTTP
   - Valida status code, estrutura JSON, valores esperados
3. **Testes de Unidade:** PHPUnit ou scripts PHP simples.
   - PHPUnit se já estiver configurado ou se houver tempo para setup inicial.
   - Scripts PHP simples como alternativa pragmática inicial.

**Importante:** Para este plano, apenas descrevemos a estratégia. A implementação real dos testes será feita tarefa por tarefa nas fases seguintes.

#### Estrutura de Testes (Planejada)

```
tests/
├── api/                    # Testes de endpoints REST
│   ├── test-alunos-api.php
│   ├── test-agendamento-api.php
│   ├── test-turma-presencas-api.php
│   ├── test-financeiro-faturas-api.php
│   └── helpers/            # Helpers para autenticação, setup, etc.
│       ├── auth-helper.php
│       └── database-helper.php
├── unit/                   # Testes de unidade
│   ├── FinanceiroAlunoHelperTest.php
│   ├── TurmaTeoricaManagerTest.php
│   └── GuardsExamesTest.php
└── integration/            # Testes de integração (se necessário)
```

#### Para Cada Tarefa Futura

Cada tarefa nas fases seguintes terá uma subseção definindo:

- **Quais testes automatizados devem ser escritos/ajustados:**
  - Nome do arquivo de teste.
  - Endpoints/tabelas envolvidos.
  - Cenários a serem cobertos (sucesso, erro, validações).

- **Resultados esperados:**
  - Status codes esperados.
  - Estrutura de resposta esperada.
  - Valores mínimos a serem validados.

---

### 2. Testes Manuais

#### Objetivo

Validar a experiência real do usuário para cada fluxo impactado, garantindo que funcionalidades funcionem como esperado do ponto de vista do usuário final.

#### Estrutura

**2.1. Checklists Específicos por Módulo**

Checklists detalhados para validar funcionalidades específicas:

- **`docs/testes/TESTES_PWA_ALUNO.md`** - Testes do painel do aluno
- **`docs/testes/TESTES_PWA_INSTRUTOR.md`** - Testes do painel do instrutor
- **`docs/testes/TESTES_ADMIN_ALUNOS.md`** - Testes do módulo de alunos (admin)
- **`docs/testes/TESTES_ADMIN_TURMAS.md`** - Testes de turmas teóricas (admin)
- **`docs/testes/TESTES_ADMIN_FINANCEIRO.md`** - Testes do módulo financeiro (admin)

**2.2. Checklists Genéricos de Regressão**

Checklists para validar que fluxos críticos não foram quebrados:

- **`docs/testes/TESTES_REGRESSAO_LOGIN.md`** - Testes de autenticação e permissões
- **`docs/testes/TESTES_REGRESSAO_FLUXOS_CRITICOS.md`** - Testes dos 9 fluxos críticos

**Observação:** Os checklists serão criados durante as fases de implementação, não neste momento de planejamento.

#### Formato dos Checklists

Cada checklist conterá:

- **Cenário:** Descrição do que será testado.
- **Pré-condições:** Estado necessário antes do teste (ex.: aluno cadastrado, turma criada).
- **Passos:** Sequência de ações a serem executadas.
- **Resultado esperado:** Comportamento esperado após os passos.
- **Status:** [ ] Pendente / [x] Aprovado / [ ] Falhou

---

### 3. Ordem Obrigatória para Cada Tarefa

Para cada tarefa de implementação, a ordem a seguir é **obrigatória**:

1. **Definir escopo da tarefa e impacto.**
   - Documentar arquivos, tabelas, APIs envolvidas.
   - Listar fluxos críticos que podem ser impactados.
   - Identificar dependências.

2. **Planejar testes automatizados.**
   - Definir quais endpoints/métodos serão testados.
   - Documentar cenários (sucesso, erro, edge cases).
   - Criar estrutura inicial dos arquivos de teste.

3. **Implementar testes.**
   - Escrever testes automatizados.
   - Garantir que testes falhem inicialmente (se for mudança de comportamento).
   - Executar testes e documentar resultados iniciais.

4. **Implementar código.**
   - Implementar funcionalidade seguindo o escopo definido.
   - Não expandir escopo durante implementação.

5. **Rodar testes automatizados.**
   - Executar todos os testes relacionados à tarefa.
   - Executar testes de regressão (se existirem).
   - Corrigir problemas encontrados até todos passarem.

6. **Executar checklist manual.**
   - Seguir checklist específico da tarefa (se existir).
   - Seguir checklist de regressão dos fluxos críticos.
   - Documentar resultados (aprovado/falhou com detalhes).

7. **Só depois disso, considerar a tarefa "pronta".**
   - Revisar código (se necessário).
   - Documentar mudanças (se necessário).
   - Marcar tarefa como concluída.

---

## Fase 0 – Congelamento, Baseline e Homologação

### Objetivo

Estabelecer um **ponto de referência estável** antes de qualquer alteração, garantindo que possamos voltar a este estado se necessário.

### Tarefas

| Tarefa | Responsável | Saída Esperada |
|--------|-------------|----------------|
| **0.1. Criar tag/branch de baseline** | Dev responsável | Tag `baseline-pre-producao` criada no repositório, representando o estado atual "aceitável" |
| **0.2. Validar documentação de onboarding** | Dev responsável / Líder técnico | `docs/ONBOARDING_DEV_CFC.md` revisado e confirmado como atualizado e coerente com o código atual |
| **0.3. Configurar ambiente de homologação** | Dev responsável / DevOps | Ambiente de homologação funcionando: banco dedicado, configurações separadas de produção, URL de acesso definida |
| **0.4. Validar fluxos críticos em homologação** | Dev responsável / QA | Todos os 9 fluxos críticos listados na seção "Fluxos Críticos" funcionando corretamente em homologação |
| **0.5. Criar rascunho de checklists manuais** | Dev responsável / QA | Rascunho inicial de checklists para os 9 fluxos críticos (estrutura base, a ser detalhado nas próximas fases) |

### Detalhamento das Tarefas

#### Tarefa 0.1. Criar tag/branch de baseline

**Objetivo:** Ter um ponto de referência seguro para rollback.

**Ações:**
- Criar tag Git: `baseline-pre-producao` (ou branch se não usar tags)
- Documentar commit hash correspondente
- Registrar data/hora da baseline

**Critérios de aceite:**
- Tag/branch criada e documentada
- Estado do código neste ponto é "aceitável" (sistema funcional, mesmo com pendências conhecidas)

#### Tarefa 0.2. Validar documentação de onboarding

**Objetivo:** Garantir que a documentação está alinhada com o código atual.

**Ações:**
- Revisar `docs/ONBOARDING_DEV_CFC.md`
- Validar que caminhos de arquivos mencionados existem
- Validar que APIs mencionadas existem
- Validar que fluxos descritos estão corretos
- Corrigir inconsistências encontradas (se houver)

**Critérios de aceite:**
- Documentação validada e corrigida (se necessário)
- Nenhuma inconsistência grave entre documentação e código

#### Tarefa 0.3. Configurar ambiente de homologação

**Objetivo:** Ter ambiente separado para validação antes de produção.

**Ações:**
- Configurar banco de dados dedicado para homologação
- Configurar arquivo de configuração separado (ex.: `config_homolog.php` ou variáveis de ambiente)
- Garantir que URLs e paths estejam corretos para homologação
- Popular dados de teste mínimos (CFC, usuário admin, alguns alunos, etc.)

**Critérios de aceite:**
- Ambiente de homologação acessível e funcional
- Login admin funcionando
- Dados de teste básicos populados

#### Tarefa 0.4. Validar fluxos críticos em homologação

**Objetivo:** Confirmar que todos os fluxos críticos funcionam no ambiente de homologação.

**Ações:**
- Executar manualmente cada um dos 9 fluxos críticos listados
- Documentar resultados (funcionou/não funcionou/com observações)
- Corrigir problemas encontrados (se houver)

**Critérios de aceite:**
- Todos os 9 fluxos críticos funcionando corretamente em homologação
- Problemas documentados e corrigidos (se houver)

#### Tarefa 0.5. Criar rascunho de checklists manuais

**Objetivo:** Ter estrutura base para checklists que serão usados nas próximas fases.

**Ações:**
- Criar arquivos de checklist em `docs/testes/` (estrutura inicial)
- Para cada fluxo crítico, criar checklist básico com:
  - Nome do fluxo
  - Cenários principais (ex.: "Cadastrar novo aluno", "Matricular aluno em turma")
  - Estrutura de passos (a ser detalhado depois)

**Critérios de aceite:**
- Estrutura base de checklists criada
- Cada fluxo crítico tem pelo menos um checklist básico

---

## Fase 1 – Camada Mínima de Testes de Regressão

### Objetivo

Criar uma **camada mínima de testes** que garanta que funcionalidades críticas não sejam quebradas durante as próximas implementações.

**Importante:** Nesta fase, apenas **planejamos** os testes. A implementação real será feita tarefa por tarefa nas próximas fases, quando necessário.

### Tarefas

| Tarefa | Responsável | Saída Esperada |
|--------|-------------|----------------|
| **1.1. Mapear endpoints e rotas críticas** | Dev responsável | Documento listando todos os endpoints/rotas críticas com método HTTP, uso principal e autenticação necessária |
| **1.2. Planejar testes automatizados mínimos** | Dev responsável | Documento definindo quais endpoints terão testes automatizados e quais cenários serão cobertos |
| **1.3. Planejar checklists manuais de regressão** | Dev responsável / QA | Estrutura de checklists manuais para validação de regressão antes de cada deploy |

---

### Tarefa 1.1. Mapear Endpoints e Rotas Críticas

#### Objetivo

Ter uma visão clara de todos os endpoints e rotas que são críticos para o funcionamento do sistema.

#### Escopo

Mapear endpoints das seguintes áreas:

**Painel Admin:**
- Rotas principais via `admin/index.php?page=...`
- APIs em `admin/api/`

**Painel Aluno:**
- Páginas em `aluno/`
- APIs usadas pelo painel do aluno

**Painel Instrutor:**
- Páginas em `instrutor/`
- APIs usadas pelo painel do instrutor

#### Saída Esperada

Tabela com:

| Rota/Endpoint | Método | Uso Principal | Autenticação | Fluxo Crítico Relacionado |
|---------------|--------|---------------|--------------|---------------------------|
| `admin/index.php?page=alunos` | GET | Listagem de alunos | Admin/Secretaria | Nova matrícula |
| `admin/api/alunos.php` | POST | Criar aluno | Admin/Secretaria | Nova matrícula |
| `admin/api/agendamento.php` | POST | Criar agendamento | Admin/Secretaria | Agendamento aulas práticas |
| `admin/api/turma-presencas.php` | POST | Registrar presença | Admin/Secretaria | Presenças teóricas |
| `instrutor/dashboard.php` | GET | Dashboard instrutor | Instrutor | Acesso painel instrutor |
| `aluno/dashboard.php` | GET | Dashboard aluno | Aluno | Acesso painel aluno |
| ... | ... | ... | ... | ... |

**Endpoints críticos identificados (baseado no código existente):**

**Admin:**
- `admin/api/alunos.php` (GET, POST, PUT, DELETE)
- `admin/api/matriculas.php` (GET, POST, PUT, DELETE)
- `admin/api/turmas-teoricas.php` (GET, POST, PUT)
- `admin/api/turma-presencas.php` (POST, PUT)
- `admin/api/agendamento.php` (GET, POST, PUT, DELETE)
- `admin/api/financeiro-faturas.php` (GET, POST, PUT, DELETE)
- `admin/api/financeiro-pagamentos.php` (GET, POST)
- `admin/api/exames.php` (GET, POST, PUT, DELETE)
- `admin/api/matricular-aluno-turma.php` (POST)

**Aluno:**
- `aluno/dashboard.php` (GET)
- `aluno/aulas.php` (GET)
- `aluno/financeiro.php` (GET)
- APIs usadas: `admin/api/aluno-agenda.php`, `admin/api/financeiro-resumo-aluno.php`

**Instrutor:**
- `instrutor/dashboard.php` (GET)
- `instrutor/aulas.php` (GET)
- APIs usadas: `admin/api/agendamento.php` (com filtro por instrutor)

---

### Tarefa 1.2. Planejar Testes Automatizados Mínimos

#### Objetivo

Definir quais endpoints terão testes automatizados e quais cenários serão cobertos.

#### Abordagem

Para cada endpoint crítico, planejar pelo menos:

1. **Teste de autenticação:**
   - Resposta esperada quando não autenticado (401, redirect, etc.)

2. **Teste de resposta bem-sucedida:**
   - Status 200 (ou apropriado)
   - Estrutura JSON esperada (se aplicável)
   - Valores mínimos a serem validados

3. **Teste de validação (quando aplicável):**
   - Resposta esperada quando dados inválidos são enviados

#### Saída Esperada

Tabela de planejamento:

| Endpoint | Tipo de Teste | Autenticado? | Resultado Esperado | Arquivo de Teste (Planejado) |
|----------|---------------|--------------|-------------------|------------------------------|
| `admin/api/alunos.php` (POST) | Autenticação | Não | 401 Unauthorized | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` (POST) | Sucesso | Sim (admin) | 200, JSON com `id` do aluno criado | `tests/api/test-alunos-api.php` |
| `admin/api/alunos.php` (POST) | Validação | Sim (admin) | 400, JSON com erro de validação | `tests/api/test-alunos-api.php` |
| `admin/api/agendamento.php` (POST) | Autenticação | Não | 401 Unauthorized | `tests/api/test-agendamento-api.php` |
| `admin/api/agendamento.php` (POST) | Sucesso | Sim (admin) | 200, JSON com `id` do agendamento | `tests/api/test-agendamento-api.php` |
| `admin/api/turma-presencas.php` (POST) | Sucesso | Sim (admin) | 200, presença registrada | `tests/api/test-turma-presencas-api.php` |
| ... | ... | ... | ... | ... |

**Importante:** Esta é apenas uma tabela de planejamento. Os testes serão implementados nas próximas fases, conforme necessário.

---

### Tarefa 1.3. Planejar Checklists Manuais de Regressão

#### Objetivo

Definir quais cenários serão sempre testados manualmente antes de cada deploy (homolog e produção).

#### Abordagem

Para cada fluxo crítico, definir cenários de regressão que devem ser sempre executados.

#### Saída Esperada

Tabela de planejamento:

| Fluxo | Cenário | Resultado Esperado | Checklist (Planejado) |
|-------|---------|-------------------|----------------------|
| Nova matrícula | Cadastrar novo aluno | Aluno criado, aparece na listagem | `docs/testes/TESTES_ADMIN_ALUNOS.md` |
| Nova matrícula | Editar aluno existente | Dados atualizados, histórico preservado | `docs/testes/TESTES_ADMIN_ALUNOS.md` |
| Turmas teóricas | Criar turma teórica | Turma criada, pode agendar aulas | `docs/testes/TESTES_ADMIN_TURMAS.md` |
| Turmas teóricas | Matricular aluno na turma | Aluno aparece na lista de matriculados | `docs/testes/TESTES_ADMIN_TURMAS.md` |
| Presenças teóricas | Marcar presença em aula | Presença registrada, frequência atualizada | `docs/testes/TESTES_ADMIN_TURMAS.md` |
| Aulas práticas | Agendar aula prática | Aula agendada, validações de conflito funcionam | `docs/testes/TESTES_ADMIN_AGENDA.md` |
| Provas/Exames | Registrar exame médico | Exame registrado, aluno pode ser matriculado | `docs/testes/TESTES_ADMIN_EXAMES.md` |
| Financeiro | Criar fatura | Fatura criada, aparece na listagem | `docs/testes/TESTES_ADMIN_FINANCEIRO.md` |
| Financeiro | Registrar pagamento | Fatura marcada como paga | `docs/testes/TESTES_ADMIN_FINANCEIRO.md` |
| Login Admin | Fazer login como admin | Acesso ao painel admin concedido | `docs/testes/TESTES_REGRESSAO_LOGIN.md` |
| Login Instrutor | Fazer login como instrutor | Acesso ao painel instrutor concedido | `docs/testes/TESTES_REGRESSAO_LOGIN.md` |
| Login Aluno | Fazer login como aluno | Acesso ao painel aluno concedido | `docs/testes/TESTES_REGRESSAO_LOGIN.md` |
| ... | ... | ... | ... |

**Importante:** Os checklists detalhados serão criados nas próximas fases. Esta é apenas a estrutura de planejamento.

---

## Fase 2 – PWA Instrutor (Funcionalidades Prioritárias)

### Objetivo

Consolidar o **PWA do Instrutor** em nível de produção, focando nas funcionalidades essenciais para que o instrutor possa executar suas atividades diárias de forma completa e confiável.

### Objetivos Principais da Fase

Permitir que o instrutor:

1. ✅ Veja a agenda de aulas do dia (já existe parcialmente).
2. ❌ **Inicie e finalize aulas práticas**, registrando horários e km.
3. ❌ **Marque faltas** dos alunos (quando aplicável).
4. ❌ Tenha acesso a **dados essenciais do aluno e do veículo** (nome, CPF, placa, tipo de veículo).
5. ❌ (Opcional) Tenha uma visão básica de turmas teóricas para chamada.

### Estrutura da Fase

A Fase 2 será dividida em **tarefas macro**, cada uma executada isoladamente seguindo a ordem obrigatória definida na seção "Estratégia de Testes".

---

### Tarefa 2.1 – Mapeamento e Documentação do Comportamento Atual do PWA Instrutor

#### Objetivo

Entender completamente o estado atual do PWA Instrutor antes de fazer alterações.

#### Escopo de Código (Alto Nível)

**Arquivos a serem analisados:**
- `instrutor/dashboard.php` - Dashboard principal
- `instrutor/dashboard-mobile.php` - Versão mobile
- `instrutor/aulas.php` - Listagem de aulas
- APIs usadas: `admin/api/agendamento.php` (com filtro por `instrutor_id`)

**Tabelas envolvidas:**
- `aulas` - Aulas práticas (campo `instrutor_id`, `status`, `data_aula`, `hora_inicio`, `hora_fim`)
- `alunos` - Dados do aluno (usado via JOIN nas queries)
- `veiculos` - Dados do veículo (usado via JOIN nas queries)

#### Testes Automatizados Mínimos para Esta Tarefa

- **Não aplicável** (tarefa de documentação/análise).

#### Testes Manuais Obrigatórios

**Cenários:**
1. Login como instrutor → Dashboard carrega sem erros.
2. Visualizar lista de aulas do dia → Aulas aparecem corretamente.
3. Navegar entre páginas do PWA → Navegação funciona.
4. Testar em dispositivo mobile → Layout responsivo funciona.

#### Critérios de Aceite

- Documento criado descrevendo:
  - Funcionalidades atuais do PWA Instrutor.
  - Limitações conhecidas.
  - APIs utilizadas.
  - Fluxos de dados.
- Nenhuma alteração de código nesta tarefa.

---

### Tarefa 2.2 – Fluxo de Início/Fim de Aula Prática

#### Objetivo

Implementar funcionalidade para que o instrutor possa iniciar e finalizar aulas práticas via PWA, registrando horários de início e fim.

#### Escopo de Código (Alto Nível)

**Arquivos a serem criados/modificados:**
- `instrutor/api/iniciar-aula.php` - API para iniciar aula (POST)
- `instrutor/api/finalizar-aula.php` - API para finalizar aula (POST)
- `instrutor/dashboard.php` ou `instrutor/aulas.php` - Adicionar botões "Iniciar Aula" e "Finalizar Aula"

**Tabelas envolvidas:**
- `aulas` - Campos: `status` (mudar para `em_andamento` ao iniciar, `concluida` ao finalizar), `hora_inicio_real` (se existir ou criar), `hora_fim_real` (se existir ou criar)

**APIs existentes a serem analisadas:**
- `admin/api/agendamento.php` - Verificar se já permite atualização de status
- `admin/api/atualizar-aula.php` - Verificar se existe e como funciona

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Iniciar aula (autenticado)**
   - Endpoint: `POST /instrutor/api/iniciar-aula.php`
   - Body: `{ "aula_id": X }`
   - Autenticação: Instrutor logado
   - Resultado esperado: 200, `status: "em_andamento"`, `hora_inicio_real` preenchido

2. **Teste: Iniciar aula (não autenticado)**
   - Resultado esperado: 401 Unauthorized

3. **Teste: Finalizar aula (autenticado)**
   - Endpoint: `POST /instrutor/api/finalizar-aula.php`
   - Body: `{ "aula_id": X }`
   - Autenticação: Instrutor logado
   - Resultado esperado: 200, `status: "concluida"`, `hora_fim_real` preenchido

4. **Teste: Validação - não pode iniciar aula de outro instrutor**
   - Resultado esperado: 403 Forbidden

#### Testes Manuais Obrigatórios

**Cenários:**
1. Instrutor logado clica em "Iniciar Aula" → Status muda para "em andamento", horário de início registrado.
2. Instrutor clica em "Finalizar Aula" → Status muda para "concluida", horário de fim registrado.
3. Tentar iniciar aula de outro instrutor → Erro de permissão.
4. Tentar finalizar aula que não foi iniciada → Erro de validação.
5. Validar que aulas em outros status não mostram botões incorretos.

#### Critérios de Aceite

- Instrutor consegue iniciar e finalizar aulas práticas via PWA.
- Horários de início e fim são registrados corretamente.
- Validações de permissão funcionam (instrutor só mexe em suas próprias aulas).
- Nenhuma mudança em fluxos não relacionados a aulas práticas do instrutor.
- Regressão zero nos fluxos já existentes do painel instrutor.

---

### Tarefa 2.3 – Registro de KM Inicial/Final

#### Objetivo

Permitir que o instrutor registre km inicial e final da aula prática, essencial para controle de uso de veículos.

#### Escopo de Código (Alto Nível)

**Arquivos a serem criados/modificados:**
- `instrutor/api/iniciar-aula.php` - Adicionar campo `km_inicial` no request
- `instrutor/api/finalizar-aula.php` - Adicionar campo `km_final` no request
- `instrutor/dashboard.php` ou `instrutor/aulas.php` - Adicionar campos de input para KM

**Tabelas envolvidas:**
- `aulas` - Campos: `km_inicial` (se existir ou criar), `km_final` (se existir ou criar)

**Validações necessárias:**
- `km_final` deve ser >= `km_inicial`
- KM deve ser número positivo

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Registrar km inicial**
   - Body: `{ "aula_id": X, "km_inicial": 1000 }`
   - Resultado esperado: 200, `km_inicial` salvo no banco

2. **Teste: Registrar km final válido**
   - Body: `{ "aula_id": X, "km_final": 1050 }`
   - Resultado esperado: 200, `km_final` salvo, validação de `km_final >= km_inicial` passa

3. **Teste: Registrar km final inválido (menor que inicial)**
   - Body: `{ "aula_id": X, "km_final": 900 }`
   - Resultado esperado: 400, erro de validação

#### Testes Manuais Obrigatórios

**Cenários:**
1. Iniciar aula registrando km inicial → KM inicial salvo corretamente.
2. Finalizar aula registrando km final válido → KM final salvo, validação passa.
3. Tentar finalizar com km final menor que inicial → Erro de validação exibido.
4. Visualizar histórico de aulas com KM → KM inicial e final aparecem corretamente.

#### Critérios de Aceite

- KM inicial e final podem ser registrados via PWA.
- Validações de KM funcionam corretamente.
- Dados de KM são salvos no banco e podem ser consultados.
- Nenhuma regressão em outras funcionalidades.

---

### Tarefa 2.4 – Marcação de Falta do Aluno

#### Objetivo

Permitir que o instrutor marque falta do aluno em aula prática (quando aluno não compareceu).

#### Escopo de Código (Alto Nível)

**Arquivos a serem criados/modificados:**
- `instrutor/api/marcar-falta.php` - API para marcar falta (POST)
- `instrutor/dashboard.php` ou `instrutor/aulas.php` - Adicionar botão/opção "Marcar Falta"

**Tabelas envolvidas:**
- `aulas` - Campo: `status` (mudar para `cancelada` ou novo status `falta_aluno` se necessário), ou campo `presente` (se existir)

**Validações necessárias:**
- Aula deve estar agendada para o instrutor logado
- Não pode marcar falta se aula já foi iniciada/finalizada

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Marcar falta (autenticado)**
   - Endpoint: `POST /instrutor/api/marcar-falta.php`
   - Body: `{ "aula_id": X }`
   - Resultado esperado: 200, status da aula atualizado para indicar falta

2. **Teste: Validação - não pode marcar falta de aula já finalizada**
   - Resultado esperado: 400, erro de validação

#### Testes Manuais Obrigatórios

**Cenários:**
1. Instrutor marca falta do aluno → Aula marcada como falta, aparece no relatório.
2. Tentar marcar falta de aula já finalizada → Erro de validação.
3. Visualizar relatório de faltas → Faltas aparecem corretamente.

#### Critérios de Aceite

- Instrutor consegue marcar falta do aluno via PWA.
- Faltas são registradas corretamente e aparecem em relatórios.
- Validações funcionam (não pode marcar falta de aula já realizada).
- Nenhuma regressão em outras funcionalidades.

---

### Tarefa 2.5 – Tela de Detalhes do Aluno (Dados Essenciais)

#### Objetivo

Permitir que o instrutor visualize dados essenciais do aluno durante a aula (nome, CPF, foto, contato).

#### Escopo de Código (Alto Nível)

**Arquivos a serem criados/modificados:**
- `instrutor/detalhes-aluno.php` - Nova página ou modal com dados do aluno
- `instrutor/api/detalhes-aluno.php` - API para buscar dados do aluno (GET)

**Tabelas envolvidas:**
- `alunos` - Campos: `nome`, `cpf`, `foto`, `telefone`, `email`

**APIs existentes a serem analisadas:**
- `admin/api/alunos.php` - Verificar se pode ser reutilizada ou se precisa de versão simplificada para instrutor

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Buscar detalhes do aluno (autenticado)**
   - Endpoint: `GET /instrutor/api/detalhes-aluno.php?aluno_id=X`
   - Autenticação: Instrutor logado
   - Resultado esperado: 200, JSON com dados essenciais do aluno

2. **Teste: Validação - instrutor só vê alunos de suas aulas**
   - Resultado esperado: 403 Forbidden se tentar acessar aluno que não tem aula com o instrutor

#### Testes Manuais Obrigatórios

**Cenários:**
1. Instrutor acessa detalhes do aluno → Dados essenciais aparecem corretamente.
2. Tentar acessar aluno que não tem aula com o instrutor → Erro de permissão.
3. Visualizar foto do aluno (se existir) → Foto carrega corretamente.

#### Critérios de Aceite

- Instrutor consegue visualizar dados essenciais do aluno via PWA.
- Permissões funcionam (só vê alunos de suas próprias aulas).
- Dados são exibidos de forma clara e organizada.
- Nenhuma regressão em outras funcionalidades.

---

### Tarefa 2.6 – (Opcional) Chamada de Turma Teórica via PWA Instrutor

#### Objetivo

Permitir que o instrutor registre presença teórica via PWA, se essa funcionalidade fizer sentido para o fluxo do CFC.

#### Escopo de Código (Alto Nível)

**Arquivos a serem analisados:**
- `admin/index.php?page=turma-chamada` - Verificar como funciona a chamada no admin
- `admin/api/turma-presencas.php` - API de presenças (verificar se pode ser reutilizada)

**Arquivos a serem criados/modificados:**
- `instrutor/chamada-teorica.php` - Nova página para chamada teórica
- `instrutor/api/turma-presencas.php` - Wrapper ou reutilização da API existente

**Tabelas envolvidas:**
- `turma_presencas` - Registro de presenças
- `turma_aulas_agendadas` - Aulas agendadas da turma
- `turma_matriculas` - Alunos matriculados na turma

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Registrar presença teórica (autenticado)**
   - Endpoint: `POST /instrutor/api/turma-presencas.php`
   - Body: `{ "aula_id": X, "aluno_id": Y, "presente": true }`
   - Resultado esperado: 200, presença registrada

2. **Teste: Validação - instrutor só pode registrar presença em suas próprias aulas teóricas**
   - Resultado esperado: 403 Forbidden

#### Testes Manuais Obrigatórios

**Cenários:**
1. Instrutor acessa chamada teórica → Lista de alunos da turma aparece.
2. Instrutor marca presença → Presença registrada, frequência atualizada.
3. Tentar registrar presença em aula de outro instrutor → Erro de permissão.

#### Critérios de Aceite

- Instrutor consegue registrar presença teórica via PWA (se funcionalidade for aprovada).
- Permissões funcionam corretamente.
- Frequência é atualizada automaticamente.
- Nenhuma regressão em outras funcionalidades.

**Nota:** Esta tarefa é opcional e deve ser aprovada antes de ser executada.

---

## Fase 3 – PWA Aluno (Jornada Completa)

### Objetivo

Fazer o **PWA do Aluno** refletir a jornada completa dele no CFC, com foco em turmas, aulas, exames e financeiro, garantindo que o aluno tenha visibilidade completa do seu processo de formação.

### Objetivos Principais da Fase

Dashboard do aluno com:

1. ❌ Próxima aula (teórica ou prática).
2. ❌ Próxima parcela financeira (se aplicável).
3. ❌ Fase atual (teórico/prático/exames).

Telas de:

1. ❌ Aulas teóricas (com presença e frequência).
2. ❌ Aulas práticas (realizadas e futuras).
3. ❌ Exames (agendados, realizados, faltosos).
4. ❌ Financeiro (parcelas, status, etc.).

### Estrutura da Fase

A Fase 3 será dividida em **tarefas macro**, cada uma executada isoladamente seguindo a ordem obrigatória definida na seção "Estratégia de Testes".

---

### Tarefa 3.1 – Revisão do Comportamento Atual do PWA Aluno

#### Objetivo

Entender completamente o estado atual do PWA Aluno antes de fazer alterações.

#### Escopo de Código (Alto Nível)

**Arquivos a serem analisados:**
- `aluno/dashboard.php` - Dashboard principal
- `aluno/dashboard-mobile.php` - Versão mobile
- `aluno/aulas.php` - Listagem de aulas
- `aluno/financeiro.php` - Consulta financeira
- `aluno/presencas-teoricas.php` - Presenças teóricas
- `aluno/historico.php` - Histórico

**APIs usadas:**
- `admin/api/aluno-agenda.php` - Agenda do aluno
- `admin/api/financeiro-resumo-aluno.php` - Resumo financeiro
- `admin/api/progresso_teorico.php` - Progresso teórico
- `admin/api/progresso_pratico.php` - Progresso prático

#### Testes Automatizados Mínimos para Esta Tarefa

- **Não aplicável** (tarefa de documentação/análise).

#### Testes Manuais Obrigatórios

**Cenários:**
1. Login como aluno → Dashboard carrega sem erros.
2. Visualizar lista de aulas → Aulas aparecem corretamente.
3. Visualizar financeiro → Dados financeiros aparecem.
4. Navegar entre páginas do PWA → Navegação funciona.
5. Testar em dispositivo mobile → Layout responsivo funciona.

#### Critérios de Aceite

- Documento criado descrevendo:
  - Funcionalidades atuais do PWA Aluno.
  - Limitações conhecidas.
  - APIs utilizadas.
  - Fluxos de dados.
- Nenhuma alteração de código nesta tarefa.

---

### Tarefa 3.2 – Dashboard com "Próxima Aula" e Fase Atual

#### Objetivo

Implementar dashboard do aluno mostrando próxima aula (teórica ou prática) e indicador da fase atual do processo (teórico/prático/exames).

#### Escopo de Código (Alto Nível)

**Arquivos a serem modificados:**
- `aluno/dashboard.php` - Adicionar cards/seções de "Próxima Aula" e "Fase Atual"

**APIs a serem criadas/modificadas:**
- `aluno/api/dashboard.php` - Nova API para dados do dashboard (GET)
  - Ou modificar `admin/api/aluno-agenda.php` para retornar próxima aula
  - Usar `admin/api/progresso_teorico.php` e `admin/api/progresso_pratico.php` para determinar fase atual

**Tabelas envolvidas:**
- `aulas` - Próxima aula prática
- `turma_aulas_agendadas` - Próxima aula teórica (via `turma_matriculas`)
- `exames` - Status dos exames para determinar fase

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Buscar dados do dashboard (autenticado)**
   - Endpoint: `GET /aluno/api/dashboard.php` ou endpoint existente modificado
   - Autenticação: Aluno logado
   - Resultado esperado: 200, JSON com `proxima_aula` e `fase_atual`

2. **Teste: Validação - aluno só vê seus próprios dados**
   - Resultado esperado: Dados retornados são apenas do aluno logado

#### Testes Manuais Obrigatórios

**Cenários:**
1. Aluno acessa dashboard → Próxima aula aparece corretamente (teórica ou prática).
2. Aluno acessa dashboard → Fase atual aparece corretamente (ex.: "Em formação teórica").
3. Aluno sem aulas agendadas → Dashboard mostra mensagem apropriada.
4. Aluno em diferentes fases → Fase atual reflete corretamente o estado.

#### Critérios de Aceite

- Dashboard mostra próxima aula corretamente.
- Dashboard mostra fase atual do processo.
- Dados são atualizados em tempo real.
- Nenhuma regressão em outras funcionalidades do PWA Aluno.

---

### Tarefa 3.3 – Tela de Turmas Teóricas e Frequência

#### Objetivo

Permitir que o aluno visualize suas turmas teóricas, aulas realizadas, presenças e frequência.

#### Escopo de Código (Alto Nível)

**Arquivos a serem criados/modificados:**
- `aluno/turmas-teoricas.php` - Nova página ou melhorar página existente
- `aluno/api/turmas-teoricas.php` - API para dados de turmas do aluno (GET)

**APIs existentes a serem analisadas:**
- `admin/api/progresso_teorico.php` - Verificar se pode ser reutilizada ou adaptada
- `admin/api/turma-presencas.php` - Verificar se pode ser consultada pelo aluno (read-only)

**Tabelas envolvidas:**
- `turma_matriculas` - Turmas do aluno (JOIN com `turmas_teoricas`)
- `turma_presencas` - Presenças do aluno
- `turma_aulas_agendadas` - Aulas agendadas da turma

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Buscar turmas teóricas do aluno (autenticado)**
   - Endpoint: `GET /aluno/api/turmas-teoricas.php`
   - Resultado esperado: 200, JSON com lista de turmas, presenças e frequência

2. **Teste: Validação - aluno só vê suas próprias turmas**
   - Resultado esperado: Dados retornados são apenas do aluno logado

#### Testes Manuais Obrigatórios

**Cenários:**
1. Aluno acessa tela de turmas teóricas → Lista de turmas aparece.
2. Aluno visualiza presenças → Presenças marcadas corretamente.
3. Aluno visualiza frequência → Frequência percentual aparece corretamente.
4. Aluno sem turmas teóricas → Mensagem apropriada é exibida.

#### Critérios de Aceite

- Aluno consegue visualizar suas turmas teóricas via PWA.
- Presenças e frequência são exibidas corretamente.
- Dados são apenas do aluno logado (segurança).
- Nenhuma regressão em outras funcionalidades.

---

### Tarefa 3.4 – Tela de Aulas Práticas (Histórico e Futuras)

#### Objetivo

Permitir que o aluno visualize aulas práticas realizadas (histórico) e futuras (agendadas).

#### Escopo de Código (Alto Nível)

**Arquivos a serem modificados:**
- `aluno/aulas.php` - Melhorar para mostrar histórico e futuras separadamente
- `aluno/api/aulas.php` - API para aulas do aluno (GET) ou usar API existente adaptada

**APIs existentes a serem analisadas:**
- `admin/api/aluno-agenda.php` - Verificar se retorna aulas práticas
- `admin/api/progresso_pratico.php` - Verificar se retorna histórico

**Tabelas envolvidas:**
- `aulas` - Aulas práticas do aluno (filtrar por `aluno_id` e `tipo_aula = 'pratica'`)

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Buscar aulas práticas do aluno (autenticado)**
   - Endpoint: `GET /aluno/api/aulas.php?tipo=pratica`
   - Resultado esperado: 200, JSON com aulas passadas e futuras

2. **Teste: Validação - aluno só vê suas próprias aulas**
   - Resultado esperado: Dados retornados são apenas do aluno logado

#### Testes Manuais Obrigatórios

**Cenários:**
1. Aluno acessa tela de aulas práticas → Lista de aulas aparece.
2. Aluno visualiza histórico → Aulas realizadas aparecem corretamente.
3. Aluno visualiza futuras → Aulas agendadas aparecem corretamente.
4. Aluno sem aulas práticas → Mensagem apropriada é exibida.

#### Critérios de Aceite

- Aluno consegue visualizar aulas práticas (histórico e futuras) via PWA.
- Separação clara entre realizadas e agendadas.
- Dados são apenas do aluno logado (segurança).
- Nenhuma regressão em outras funcionalidades.

---

### Tarefa 3.5 – Tela de Exames

#### Objetivo

Permitir que o aluno visualize seus exames (médico, psicotécnico, teórico, prático) com status (agendado, realizado, aprovado/reprovado).

#### Escopo de Código (Alto Nível)

**Arquivos a serem criados/modificados:**
- `aluno/exames.php` - Nova página ou melhorar página existente
- `aluno/api/exames.php` - API para exames do aluno (GET)

**APIs existentes a serem analisadas:**
- `admin/api/exames.php` - Verificar se pode ser adaptada para uso do aluno (read-only)

**Tabelas envolvidas:**
- `exames` - Exames do aluno (filtrar por `aluno_id`)

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Buscar exames do aluno (autenticado)**
   - Endpoint: `GET /aluno/api/exames.php`
   - Resultado esperado: 200, JSON com lista de exames e status

2. **Teste: Validação - aluno só vê seus próprios exames**
   - Resultado esperado: Dados retornados são apenas do aluno logado

#### Testes Manuais Obrigatórios

**Cenários:**
1. Aluno acessa tela de exames → Lista de exames aparece.
2. Aluno visualiza status dos exames → Status aparece corretamente (agendado, aprovado, etc.).
3. Aluno sem exames → Mensagem apropriada é exibida.

#### Critérios de Aceite

- Aluno consegue visualizar seus exames via PWA.
- Status dos exames é exibido corretamente.
- Dados são apenas do aluno logado (segurança).
- Nenhuma regressão em outras funcionalidades.

---

### Tarefa 3.6 – Tela de Financeiro Simplificada para o Aluno

#### Objetivo

Permitir que o aluno visualize suas faturas, parcelas e status financeiro de forma simples e clara.

#### Escopo de Código (Alto Nível)

**Arquivos a serem modificados:**
- `aluno/financeiro.php` - Melhorar para mostrar faturas, parcelas e status
- `aluno/api/financeiro.php` - API para financeiro do aluno (GET) ou usar API existente

**APIs existentes a serem analisadas:**
- `admin/api/financeiro-resumo-aluno.php` - Verificar se pode ser reutilizada
- `admin/api/financeiro-faturas.php` - Verificar se pode ser adaptada (read-only, filtro por aluno)

**Tabelas envolvidas:**
- `financeiro_faturas` - Faturas do aluno (filtrar por `aluno_id`)
- `pagamentos` - Pagamentos das faturas

#### Testes Automatizados Mínimos para Esta Tarefa

1. **Teste: Buscar dados financeiros do aluno (autenticado)**
   - Endpoint: `GET /aluno/api/financeiro.php`
   - Resultado esperado: 200, JSON com faturas, parcelas e status

2. **Teste: Validação - aluno só vê seus próprios dados financeiros**
   - Resultado esperado: Dados retornados são apenas do aluno logado

#### Testes Manuais Obrigatórios

**Cenários:**
1. Aluno acessa tela de financeiro → Faturas aparecem corretamente.
2. Aluno visualiza status das faturas → Status aparece (aberta, paga, vencida).
3. Aluno visualiza pagamentos → Pagamentos registrados aparecem.
4. Aluno sem faturas → Mensagem apropriada é exibida.

#### Critérios de Aceite

- Aluno consegue visualizar seus dados financeiros via PWA.
- Informações são exibidas de forma clara e organizada.
- Dados são apenas do aluno logado (segurança).
- Nenhuma regressão em outras funcionalidades.

---

## Fase 4 – Estabilização, Logs e Monitoramento

### Objetivo

Após as fases de PWA Instrutor e Aluno, realizar uma **fase de estabilização** focada em:

- Revisão e correção de erros encontrados em logs.
- Garantia de mensagens de erro amigáveis.
- Planejamento de monitoramento básico.

### Tarefas

| Tarefa | Responsável | Saída Esperada |
|--------|-------------|----------------|
| **4.1. Revisão de logs em homolog e correção de pontos críticos** | Dev responsável | Logs revisados, erros críticos corrigidos, documentação de erros não críticos |
| **4.2. Padronização mínima de mensagens de erro nas telas do aluno/instrutor** | Dev responsável | Mensagens de erro amigáveis e consistentes nos PWAs |
| **4.3. Script simples de health-check das principais rotas** | Dev responsável | Script criado para verificar se rotas principais estão respondendo |

---

### Tarefa 4.1 – Revisão de Logs em Homolog e Correção de Pontos Críticos

#### Objetivo

Identificar e corrigir erros críticos encontrados nos logs durante testes em homologação.

#### Escopo de Código (Alto Nível)

**Arquivos a serem analisados:**
- `logs/php_errors.log` - Logs de erros PHP
- Logs do servidor web (se disponíveis)

**Correções serão feitas conforme necessário:**
- Arquivos PHP com erros
- APIs que estão gerando erros
- Queries SQL que estão falhando

#### Testes Automatizados Mínimos para Esta Tarefa

- Testes de regressão dos fluxos críticos após correções.

#### Testes Manuais Obrigatórios

**Cenários:**
1. Executar fluxos críticos após correções → Nenhum erro aparece nos logs.
2. Validar que correções não introduziram novos problemas.

#### Critérios de Aceite

- Erros críticos corrigidos.
- Logs revisados e documentados.
- Nenhuma regressão introduzida pelas correções.

---

### Tarefa 4.2 – Padronização Mínima de Mensagens de Erro nas Telas do Aluno/Instrutor

#### Objetivo

Garantir que erros exibidos para o usuário (aluno/instrutor) sejam amigáveis e consistentes.

#### Escopo de Código (Alto Nível)

**Arquivos a serem modificados:**
- `aluno/*.php` - Adicionar tratamento de erros amigáveis
- `instrutor/*.php` - Adicionar tratamento de erros amigáveis
- APIs usadas pelos PWAs - Garantir que retornem mensagens de erro claras

#### Testes Automatizados Mínimos para Esta Tarefa

- Testes de APIs retornando erros → Validar que mensagens são claras e em português.

#### Testes Manuais Obrigatórios

**Cenários:**
1. Provocar erros intencionalmente nos PWAs → Mensagens amigáveis são exibidas.
2. Validar que mensagens são consistentes em diferentes telas.

#### Critérios de Aceite

- Mensagens de erro são amigáveis e em português.
- Mensagens são consistentes entre diferentes telas.
- Nenhuma mensagem técnica exposta ao usuário final.

---

### Tarefa 4.3 – Script Simples de Health-Check das Principais Rotas

#### Objetivo

Criar script básico para verificar se as rotas principais do sistema estão respondendo corretamente.

#### Escopo de Código (Alto Nível)

**Arquivo a ser criado:**
- `tools/health-check.php` - Script PHP que verifica rotas principais

**Rotas a serem verificadas:**
- Login (admin, instrutor, aluno)
- Dashboard de cada perfil
- APIs principais (alunos, agendamento, etc.)

#### Testes Automatizados Mínimos para Esta Tarefa

- Script deve ser executável e retornar status claro (OK/ERRO) para cada rota.

#### Testes Manuais Obrigatórios

**Cenários:**
1. Executar script → Status de cada rota é exibido corretamente.
2. Executar script com rota quebrada → Erro é detectado corretamente.

#### Critérios de Aceite

- Script criado e funcional.
- Script verifica rotas principais do sistema.
- Output é claro e fácil de interpretar.

---

## Fase 5 – Checklist de Go-Live (Produção)

### Objetivo

Garantir que todos os passos necessários sejam executados antes, durante e depois do deploy em produção.

---

### 1. Antes do Deploy

- [ ] **Backup completo de banco e arquivos**
  - Backup do banco de dados de produção
  - Backup dos arquivos do servidor (uploads, configurações, etc.)
  - Verificar que backups foram criados com sucesso

- [ ] **Aplicação dos testes automatizados em homolog**
  - Todos os testes automatizados passando em homologação
  - Nenhum teste falhando (investigar e corrigir se houver)

- [ ] **Execução completa dos checklists manuais nos fluxos críticos**
  - Executar checklists de todos os 9 fluxos críticos em homologação
  - Todos os cenários aprovados
  - Documentar resultados

- [ ] **Validação de ambiente de produção**
  - Verificar que banco de produção está acessível
  - Verificar que configurações de produção estão corretas
  - Verificar espaço em disco e recursos do servidor

- [ ] **Aprovação formal**
  - [ ] Aprovação do responsável técnico
  - [ ] Aprovação do responsável do CFC (cliente)
  - [ ] Aprovação documentada (e-mail, documento, etc.)

---

### 2. Durante o Deploy

- [ ] **Aplicar migrations (se houver)**
  - Verificar se há novas migrations desde a última atualização
  - Aplicar migrations em ordem
  - Verificar que migrations foram aplicadas com sucesso

- [ ] **Publicar código**
  - Fazer upload dos arquivos atualizados
  - Manter backup dos arquivos antigos (se possível)
  - Verificar permissões de arquivos

- [ ] **Limpar cache/configurações (se aplicável)**
  - Limpar cache do PHP (OPcache, se habilitado)
  - Limpar cache do navegador (se necessário)
  - Verificar que configurações estão corretas

- [ ] **Verificar que sistema está acessível**
  - Testar acesso à URL principal
  - Verificar que não há erros 500/404

---

### 3. Depois do Deploy

- [ ] **Smoke-test rápido em produção**

  **Login e Acesso:**
  - [ ] Login admin funciona
  - [ ] Login instrutor funciona
  - [ ] Login aluno funciona (se implementado)

  **Funcionalidades Básicas:**
  - [ ] Dashboard admin carrega sem erros
  - [ ] Dashboard instrutor carrega sem erros
  - [ ] Dashboard aluno carrega sem erros

  **Fluxos Críticos (Validação Rápida):**
  - [ ] Cadastro/consulta simples de aluno funciona
  - [ ] Visualização de aulas do instrutor funciona
  - [ ] Visualização de aulas e financeiro do aluno funciona

- [ ] **Monitoramento de logs nas primeiras horas/dias**
  - [ ] Verificar logs de erro nas primeiras 2 horas após deploy
  - [ ] Verificar logs de erro no primeiro dia após deploy
  - [ ] Investigar e corrigir erros críticos (se houver)

- [ ] **Validação com usuários reais**
  - [ ] Validar com secretaria/admin que funcionalidades estão funcionando
  - [ ] Validar com instrutores que PWA está funcionando
  - [ ] Validar com alunos que PWA está funcionando (se implementado)
  - [ ] Coletar feedback inicial

- [ ] **Documentação do deploy**
  - [ ] Documentar data/hora do deploy
  - [ ] Documentar versão/tag deployada
  - [ ] Documentar problemas encontrados (se houver)
  - [ ] Documentar próximos passos (se necessário)

---

## Regras para Execução das Próximas Tarefas

### Antes de Qualquer Alteração

- [ ] **Ler o `docs/ONBOARDING_DEV_CFC.md`**
  - Garantir entendimento completo da arquitetura, módulos e fluxos críticos.

- [ ] **Ler este `docs/PLANO_IMPL_PRODUCAO_CFC.md`**
  - Garantir entendimento do plano de implementação e das regras.

- [ ] **Entender em qual fase/tarefa a mudança se encaixa**
  - Identificar fase e tarefa específica.
  - Verificar dependências com outras tarefas.

---

### Para Cada Tarefa

- [ ] **Definir escopo exato (o que pode e o que NÃO pode ser alterado)**
  - Listar arquivos que serão modificados.
  - Listar tabelas que serão impactadas.
  - Listar APIs que serão criadas/modificadas.
  - Listar fluxos críticos que podem ser impactados.

- [ ] **Planejar e implementar testes automatizados mínimos**
  - Definir quais endpoints/métodos serão testados.
  - Escrever testes antes da implementação (quando possível).
  - Garantir que testes falham inicialmente (se for mudança de comportamento).

- [ ] **Implementar a funcionalidade**
  - Seguir o escopo definido.
  - Não expandir escopo durante implementação.
  - Commits pequenos e focados.

- [ ] **Rodar testes automatizados**
  - Executar todos os testes relacionados à tarefa.
  - Executar testes de regressão (se existirem).
  - Corrigir problemas até todos passarem.

- [ ] **Rodar checklist manual**
  - Seguir checklist específico da tarefa (se existir).
  - Seguir checklist de regressão dos fluxos críticos.
  - Documentar resultados (aprovado/falhou com detalhes).

- [ ] **Validar que nenhum fluxo crítico foi quebrado**
  - Executar smoke tests nos 9 fluxos críticos.
  - Validar que comportamento existente foi preservado.

---

### Proibido

- ❌ **"Aproveitar" uma tarefa para mexer em outras áreas não relacionadas**
  - Cada tarefa deve ter escopo bem definido.
  - Mudanças adicionais devem ser tarefas separadas.

- ❌ **Alterar múltiplos módulos sem checklist e testes**
  - Cada módulo alterado deve ter seus próprios testes.
  - Não fazer mudanças "grandes" de uma vez.

- ❌ **Apagar código antigo sem entender 100% o impacto**
  - Código legado só deve ser removido após análise completa.
  - Verificar se há dependências antes de remover.

- ❌ **Fazer deploy sem executar testes e checklists**
  - Deploy só acontece após validação completa.
  - Nenhuma exceção a esta regra.

- ❌ **Ignorar erros em logs "porque funciona na minha máquina"**
  - Todos os erros devem ser investigados.
  - Logs são fonte importante de informação.

---

## Conclusão

Este plano estabelece a **base para implementações seguras** do CFC Bom Conselho até produção.

**Princípios fundamentais:**

1. **Nenhuma funcionalidade existente pode ser quebrada.**
2. **Toda implementação segue: planejamento → testes → código → validação.**
3. **Fluxos críticos são sempre validados antes de cada deploy.**

**Próximos passos:**

1. Executar **Fase 0** para estabelecer baseline.
2. Executar **Fase 1** para planejar testes.
3. Executar **Fases 2 e 3** para implementar PWAs.
4. Executar **Fase 4** para estabilização.
5. Executar **Fase 5** para go-live.

**Documentação relacionada:**

- `docs/ONBOARDING_DEV_CFC.md` - Base de conhecimento do sistema
- `docs/testes/` - Checklists de testes (a serem criados)
- `tests/` - Testes automatizados (a serem criados)

---

**Desenvolvido para a equipe do CFC Bom Conselho**  
**Última atualização:** Janeiro 2025

