# 📘 ONBOARDING - DESENVOLVEDOR CFC BOM CONSELHO

**Última atualização:** Janeiro 2025  
**Versão do Sistema:** 1.0.0

---

## 📋 Índice

1. [Visão Geral do CFC Bom Conselho](#1-visão-geral-do-cfc-bom-conselho)
2. [Arquitetura Técnica](#2-arquitetura-técnica)
3. [Módulos de Negócio](#3-módulos-de-negócio)
4. [Modelo de Dados (Banco)](#4-modelo-de-dados-banco)
5. [Autenticação, Perfis e Permissões](#5-autenticação-perfis-e-permissões)
6. [Integrações Externas](#6-integrações-externas)
7. [Aplicativos (PWA) de Aluno e Instrutor](#7-aplicativos-pwa-de-aluno-e-instrutor)
8. [Padrões de Layout, UI e UX](#8-padrões-de-layout-ui-e-ux)
9. [Fluxos Críticos (Passo a Passo)](#9-fluxos-críticos-passo-a-passo)
10. [Pendências, Bugs Conhecidos e Áreas Sensíveis](#10-pendências-bugs-conhecidos-e-áreas-sensíveis)
11. [Como Rodar o Projeto Localmente](#11-como-rodar-o-projeto-localmente)
12. [Checklist para Novo Desenvolvedor](#12-checklist-para-novo-desenvolvedor)

---

## 1. Visão Geral do CFC Bom Conselho

### O que é o Sistema

O **CFC Bom Conselho** é um sistema web completo para gestão de Centros de Formação de Condutores (autoescolas). O sistema permite gerenciar todo o ciclo de vida do processo de formação de condutores, desde a matrícula até a conclusão, incluindo controle de aulas teóricas e práticas, provas, presenças, financeiro e relatórios.

### Principais Tipos de Usuário

1. **Administração / Gestor do CFC**
   - Visão global do sistema
   - Configurações gerais
   - Gestão de usuários e permissões
   - Relatórios macro
   - Pode ser **Admin Master** (multi-CFC, `cfc_id = 0`) ou **Admin Secretaria** (operacional, `cfc_id > 0`)

2. **Instrutor**
   - Visualização de aulas agendadas
   - Registro de presença teórica (via PWA)
   - Início/encerramento de aulas práticas (com registro de km)
   - Acesso via PWA ou painel web

3. **Aluno**
   - Acompanhamento do processo de formação
   - Visualização de agenda (teórica e prática)
   - Consulta de financeiro
   - Histórico de aulas e provas
   - Acesso via PWA ou painel web

4. **Secretaria** (tipo de usuário)
   - Operação diária do CFC
   - Cadastro de alunos e matrículas
   - Gestão de turmas teóricas
   - Controle financeiro básico

### Principais Módulos

- **Alunos & Matrículas**: Cadastro completo de alunos, dados pessoais, documentos, histórico completo
- **Aulas Teóricas**: Criação de turmas, agendamento de aulas, controle de presença, frequência
- **Aulas Práticas**: Agendamento, controle de km, horários, veículos, instrutores
- **Controle de Presença**: Registro de presença teórica e prática, cálculo de frequência
- **Provas / Avaliações**: Registro de exames médico, psicotécnico, teórico e prático
- **Financeiro**: Faturas, pagamentos, controle de inadimplência, bloqueios automáticos
- **Relatórios**: Dashboards, relatórios de alunos, estatísticas de aulas, financeiro
- **Aplicações PWA**: Interfaces mobile-friendly para aluno e instrutor (parcialmente implementadas)

**Referências principais:**
- Documentação de planejamento: `admin/pages/_PLANO-SISTEMA-CFC.md`
- Raio-X completo: `docs/RAIO-X-PROJETO-CFC-COMPLETO.md`

---

## 2. Arquitetura Técnica

### Stack Principal

**Backend:**
- **PHP 8.0+** - Linguagem principal
- **MySQL 5.7+** - Banco de dados relacional
- **PDO** - Camada de abstração para banco de dados
- **Sessions PHP** - Gerenciamento de sessões

**Frontend:**
- **HTML5** - Estrutura semântica
- **CSS3** - Estilos responsivos
- **JavaScript (ES6+)** - Funcionalidades interativas (vanilla JS, sem frameworks)
- **Bootstrap 5** - Framework CSS para layout responsivo
- **Font Awesome** - Biblioteca de ícones

**Arquitetura:**
- **Padrão MVC simplificado** (sem framework)
- **Roteamento via query string** (`?page=nome&action=acao`)
- **APIs REST** via arquivos PHP individuais em `admin/api/`

### Organização de Pastas

```
cfc-bom-conselho/
├── admin/                    # Área administrativa
│   ├── api/                 # APIs REST (78 arquivos)
│   ├── pages/               # Páginas do admin (64 arquivos)
│   ├── assets/              # CSS, JS, imagens do admin
│   ├── migrations/          # Scripts de migração SQL
│   ├── tools/               # Scripts de diagnóstico/ferramentas
│   ├── includes/            # Helpers e services específicos do admin
│   └── index.php            # Router principal do admin
├── aluno/                    # Área do aluno (PWA/web)
│   ├── dashboard.php
│   ├── aulas.php
│   └── ...
├── instrutor/                # Área do instrutor (PWA/web)
│   ├── dashboard.php
│   ├── aulas.php
│   └── ...
├── includes/                 # Arquivos compartilhados
│   ├── config.php           # Configurações globais
│   ├── database.php         # Classe Database (PDO wrapper)
│   ├── auth.php             # Sistema de autenticação
│   ├── guards/              # Validações de negócio (exames, financeiro)
│   └── services/            # Services compartilhados
├── pwa/                      # Assets PWA (manifest, service worker)
├── assets/                   # Assets globais (CSS, JS, imagens)
├── docs/                     # Documentação do projeto
├── logs/                     # Logs do sistema
├── backups/                  # Backups do banco
└── index.php                 # Página de login inicial
```

### Ambientes

O sistema detecta automaticamente o ambiente através de `includes/config.php`:

- **Local**: Detectado quando `HTTP_HOST` contém `localhost` ou `127.0.0.1`
- **Produção**: Detectado quando `HTTP_HOST` contém `hostinger` ou `hstgr.io`

**Configurações por ambiente:**
- **Local**: Debug ativo, logs detalhados, timeout maior, sem cache
- **Produção**: Debug desativado, logs INFO, timeout menor, cache ativo

**Arquivos de configuração:**
- `includes/config.php` - Configurações principais (banco, URLs, segurança)
- `config_local.php` (opcional) - Sobrescreve configurações em ambiente local

### Multi-tenant

**O sistema é multi-tenant** através do campo `cfc_id`:

- **Tabelas multi-tenant**: `alunos`, `turmas_teoricas`, `instrutores`, `veiculos`, `salas`, etc.
- **Campo tenant**: `cfc_id` (INT) - Referência para `cfcs.id`
- **CFC Bom Conselho**: ID canônico é **36** (ID 1 é legado e deve ser migrado)

**Regras de acesso:**

1. **Admin Global** (`cfc_id = 0`):
   - Pode acessar dados de qualquer CFC
   - Não há bloqueio por CFC diferente
   - Alunos retornados sempre são do CFC da turma (não do CFC da sessão)

2. **Usuário de CFC específico** (`cfc_id > 0`):
   - Só pode acessar dados do seu próprio CFC
   - Bloqueio automático se tentar acessar turma/aluno de outro CFC

**Arquivos críticos para multi-tenant:**
- `admin/api/alunos-aptos-turma-simples.php` - Lógica de filtro por CFC
- `includes/auth.php` - Sessão armazena `cfc_id` do usuário
- Queries SQL devem sempre filtrar por `cfc_id` quando aplicável

**Documentação:** `docs/IMPLEMENTACAO_CFC_ALUNOS_TURMAS.md`, `docs/CORRECAO_CFC_ADMIN_GLOBAL.md`

---

## 3. Módulos de Negócio

### 3.1. Alunos & Matrículas

**O que faz:**
- Cadastro completo de alunos (dados pessoais, documentos, contatos)
- Gestão de matrículas (categoria CNH, tipo de serviço, status)
- Histórico completo do aluno (aulas, provas, financeiro, presenças)
- Modal avançado com múltiplas abas (detalhes, matrícula, histórico, financeiro, etc.)

**Principais rotas/telas:**
- `admin/index.php?page=alunos` - Listagem e cadastro de alunos
- `admin/api/alunos.php` - API CRUD de alunos
- `admin/api/matriculas.php` - API CRUD de matrículas
- `admin/api/historico_aluno.php` - Histórico completo do aluno

**Código principal:**
- Controller: `admin/pages/alunos.php` (~11.000 linhas - modal complexo)
- API: `admin/api/alunos.php`
- Service: `admin/includes/FinanceiroAlunoHelper.php` - Resumo financeiro

**Relacionamentos:**
- `alunos` 1:N `matriculas`
- `alunos` 1:N `financeiro_faturas`
- `alunos` 1:N `exames`
- `alunos` 1:N `aulas`
- `alunos` 1:N `turma_matriculas`

### 3.2. Aulas Teóricas

**O que faz:**
- Criação de turmas teóricas (wizard completo)
- Configuração de disciplinas por tipo de curso
- Agendamento de aulas teóricas em lote
- Controle de presença teórica
- Cálculo automático de frequência

**Principais rotas/telas:**
- `admin/index.php?page=turmas-teoricas` - Listagem de turmas
- `admin/index.php?page=turma-chamada&turma_id=X` - Chamada de presença
- `admin/api/turmas-teoricas.php` - API CRUD de turmas
- `admin/api/turma-presencas.php` - API de presenças

**Código principal:**
- Controller: `admin/pages/turmas-teoricas-detalhes-inline.php`
- Manager: `admin/includes/TurmaTeoricaManager.php` - Lógica de turmas
- Manager: `admin/includes/turma_manager.php` - Helpers de turmas

**Tabelas principais:**
- `turmas_teoricas` - Turmas
- `turma_aulas_agendadas` - Aulas agendadas da turma
- `turma_matriculas` - Alunos matriculados na turma
- `turma_presencas` - Presenças dos alunos

**Documentação:** `admin/migrations/001-create-turmas-teoricas-structure.sql`

### 3.3. Aulas Práticas

**O que faz:**
- Agendamento de aulas práticas (data, horário, veículo, instrutor, aluno)
- Validação de conflitos (mesmo instrutor/veículo no mesmo horário)
- Registro de km inicial/final (via PWA instrutor)
- Controle de status (agendada, em_andamento, concluida, cancelada)

**Principais rotas/telas:**
- `admin/index.php?page=agendamento` - Agenda global (calendário visual)
- `admin/api/agendamento.php` - API de agendamento
- `instrutor/dashboard.php` - Dashboard do instrutor (lista de aulas)

**Código principal:**
- Controller: `admin/pages/agendamento.php`
- API: `admin/api/agendamento.php`
- Validações: `admin/includes/controle_limite_aulas.php`

**Regras de agendamento:**
- Duração fixa: **50 minutos** por aula
- Máximo: **3 aulas por dia** por instrutor
- Padrão: 2 aulas consecutivas + intervalo 30min + 1 aula final
- Alternativa: 1 aula + intervalo 30min + 2 aulas consecutivas

**Tabela principal:**
- `aulas` - Aulas práticas e teóricas (campo `tipo_aula` diferencia)

### 3.4. Presenças

**O que faz:**
- Registro de presença teórica (via chamada na turma ou PWA instrutor)
- Registro de presença prática (automático ao iniciar/encerrar aula)
- Cálculo automático de frequência percentual
- Log de alterações de presença

**Onde é gravado:**
- **Teórica**: `turma_presencas` (relacionada a `turma_aulas_agendadas`)
- **Prática**: Implícita no status da aula (`aulas.status`)

**Regras de negócio:**
- Frequência calculada automaticamente via `TurmaTeoricaManager::recalcularFrequenciaAluno()`
- Bloqueio automático se frequência < 75% (configurável)
- Log de alterações em `turma_presencas_log`

**Código principal:**
- API: `admin/api/turma-presencas.php`
- Manager: `admin/includes/TurmaTeoricaManager.php`
- Log: `admin/migrations/20251124_create_turma_presencas_log.sql`

**Documentação:** `docs/RAIO_X_PRESENCA_TEORICA.md`, `docs/IMPLEMENTACAO_PRESENCA_TEORICA_COMPLETA.md`

### 3.5. Provas / Avaliações

**O que faz:**
- Registro de exames médico, psicotécnico, teórico e prático
- Validação de elegibilidade para turmas teóricas (exames OK)
- Bloqueio automático se exames não estiverem OK
- Histórico completo de exames do aluno

**Tipos de exames:**
- `medico` - Exame médico
- `psicotecnico` - Exame psicotécnico
- `teorico` - Prova teórica
- `pratico` - Prova prática

**Status de resultados:**
- `apto` / `inapto` - Para médico e psicotécnico
- `aprovado` / `reprovado` - Para teórico e prático

**Código principal:**
- API: `admin/api/exames.php`
- Guards: `admin/includes/guards_exames.php` - Validações
- Service: `admin/includes/ExamesRulesService.php` - Regras de negócio

**Tabela principal:**
- `exames` - Todos os exames/provas

**Documentação:** `docs/ANALISE_RELACAO_TIPO_EXAME_ID_RESULTADO.md`

### 3.6. Financeiro

**O que faz:**
- Gestão de faturas (receitas)
- Registro de pagamentos
- Controle de inadimplência
- Bloqueio automático por inadimplência
- Resumo financeiro por aluno

**Diferença importante:**
- **Financeiro da Matrícula** (`matriculas.valor_total`, `matriculas.forma_pagamento`): Campos informativos do contrato
- **Financeiro Real** (`financeiro_faturas`, `pagamentos`): Controle efetivo de cobranças e pagamentos

**Principais rotas/telas:**
- `admin/index.php?page=financeiro-faturas` - Listagem de faturas
- `admin/index.php?page=financeiro-pagamentos` - Pagamentos
- `admin/api/financeiro-faturas.php` - API CRUD de faturas
- `admin/api/financeiro-pagamentos.php` - API de pagamentos

**Código principal:**
- Controller: `admin/pages/financeiro-faturas.php`
- API: `admin/api/financeiro-faturas.php`
- Service: `admin/includes/FinanceiroService.php` - Lógica financeira
- Helper: `admin/includes/FinanceiroAlunoHelper.php` - Resumo por aluno
- Guards: `admin/includes/guards/FinanceiroRulesService.php` - Validações

**Tabelas principais:**
- `financeiro_faturas` - Faturas (receitas)
- `pagamentos` / `financeiro_pagamentos` - Pagamentos registrados
- `financeiro_configuracoes` - Configurações do módulo financeiro

**Bloqueios automáticos:**
- Aluno com faturas vencidas não pode ser matriculado em turma teórica
- Aluno inadimplente não pode agendar aulas práticas
- Validação via `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`

**Documentação:** `docs/FASE3_FINANCEIRO_ALUNO.md`, `admin/migrations/005-create-financeiro-faturas-structure.sql`

### 3.7. Relatórios e Painéis

**O que faz:**
- Dashboard administrativo com KPIs
- Relatórios de alunos por status
- Estatísticas de aulas (teóricas e práticas)
- Relatórios financeiros (inadimplência, receitas)

**Principais rotas/telas:**
- `admin/index.php?page=dashboard` - Dashboard principal
- `admin/index.php?page=relatorios` - Relatórios (parcialmente implementado)

**Código principal:**
- Controller: `admin/pages/dashboard.php`
- Queries agregadas no próprio controller

**Pendências:**
- Relatórios avançados ainda não implementados completamente
- Exportação de dados (mencionada em TODO, não implementada)

### 3.8. Aplicativos / PWA

**Status:** ⚠️ **Parcialmente implementados**

#### Painel do Aluno

**O que existe:**
- `aluno/dashboard.php` - Dashboard básico
- `aluno/aulas.php` - Listagem de aulas
- `aluno/financeiro.php` - Consulta financeira básica
- `aluno/presencas-teoricas.php` - Presenças teóricas

**O que falta:**
- Dashboard com cards de resumo (processo, teórico, prático, provas, financeiro)
- API específica para PWA (`aluno/api/dashboard.php`)
- Service worker completo
- Notificações push

**Código principal:**
- Controllers: `aluno/*.php`
- PWA assets: `pwa/manifest.json`, `pwa/sw.js`

#### Painel do Instrutor

**O que existe:**
- `instrutor/dashboard.php` - Dashboard básico
- `instrutor/aulas.php` - Listagem de aulas do dia
- `instrutor/dashboard-mobile.php` - Versão mobile

**O que falta:**
- Funcionalidade "Iniciar aula" (registro de km inicial)
- Funcionalidade "Encerrar aula" (km final, carga horária)
- Registro de presença teórica via app
- API específica para PWA (`instrutor/api/aulas.php`)
- Validações de bloqueio no app (financeiro, faltas)

**Código principal:**
- Controllers: `instrutor/*.php`
- Usa APIs compartilhadas: `admin/api/agendamento.php` (com filtro por instrutor)

**Documentação:** `admin/pages/_FASE-4-ARQUITETURA-GERAL.md` (planejamento PWA)

---

## 4. Modelo de Dados (Banco)

### Tabelas Principais

| Tabela | Descrição | Campos-chave | Multi-tenant |
|--------|-----------|--------------|-------------|
| `alunos` | Cadastro de alunos | `id`, `cpf`, `cfc_id` | ✅ `cfc_id` |
| `matriculas` | Matrículas dos alunos | `id`, `aluno_id`, `categoria_cnh`, `tipo_servico` | ❌ |
| `turmas_teoricas` | Turmas teóricas | `id`, `cfc_id`, `curso_tipo`, `status` | ✅ `cfc_id` |
| `turma_aulas_agendadas` | Aulas agendadas da turma | `id`, `turma_id`, `disciplina`, `data_aula` | ❌ |
| `turma_matriculas` | Alunos matriculados em turmas | `id`, `turma_id`, `aluno_id` | ❌ |
| `turma_presencas` | Presenças teóricas | `id`, `aula_id`, `aluno_id` | ❌ |
| `aulas` | Aulas práticas/teóricas | `id`, `aluno_id`, `instrutor_id`, `tipo_aula` | ✅ `cfc_id` |
| `exames` | Exames e provas | `id`, `aluno_id`, `tipo`, `resultado` | ❌ |
| `financeiro_faturas` | Faturas (receitas) | `id`, `aluno_id`, `matricula_id`, `data_vencimento` | ❌ |
| `pagamentos` | Pagamentos registrados | `id`, `fatura_id`, `data_pagamento` | ❌ |
| `instrutores` | Instrutores | `id`, `usuario_id`, `cfc_id`, `credencial` | ✅ `cfc_id` |
| `veiculos` | Veículos do CFC | `id`, `cfc_id`, `placa` | ✅ `cfc_id` |
| `salas` | Salas de aula | `id`, `cfc_id`, `nome` | ✅ `cfc_id` |
| `usuarios` | Usuários do sistema | `id`, `email`, `tipo`, `cfc_id` | ✅ `cfc_id` |
| `cfcs` | CFCs cadastrados | `id`, `cnpj`, `nome` | ❌ |

### Diagrama Textual de Relações Principais

```
cfcs (1) ──< (N) alunos
cfcs (1) ──< (N) instrutores
cfcs (1) ──< (N) veiculos
cfcs (1) ──< (N) salas
cfcs (1) ──< (N) turmas_teoricas

alunos (1) ──< (N) matriculas
alunos (1) ──< (N) turma_matriculas
alunos (1) ──< (N) turma_presencas
alunos (1) ──< (N) aulas
alunos (1) ──< (N) exames
alunos (1) ──< (N) financeiro_faturas

matriculas (1) ──< (N) financeiro_faturas

turmas_teoricas (1) ──< (N) turma_aulas_agendadas
turmas_teoricas (1) ──< (N) turma_matriculas

turma_aulas_agendadas (1) ──< (N) turma_presencas

instrutores (1) ──< (N) aulas
instrutores (1) ──< (N) turma_aulas_agendadas

veiculos (1) ──< (N) aulas

financeiro_faturas (1) ──< (N) pagamentos
```

### Migrations

As migrations estão em `admin/migrations/`:

- `001-create-turmas-teoricas-structure.sql` - Estrutura completa de turmas teóricas
- `004-create-matriculas-structure.sql` - Tabela de matrículas
- `005-create-financeiro-faturas-structure.sql` - Estrutura financeira
- `006-create-pagamentos-structure.sql` - Pagamentos
- `007-create-financeiro-pagamentos-structure.sql` - Pagamentos (alternativa)
- `008-create-financeiro-configuracoes-structure.sql` - Configurações financeiras

**Importante:** O `install.php` na raiz cria as tabelas básicas, mas não todas. Use as migrations para estruturas mais complexas.

---

## 5. Autenticação, Perfis e Permissões

### Como o Usuário Faz Login

**Telas de login:**
- `index.php` - Login principal (redireciona conforme tipo de usuário)
- `admin/login.php` - Login específico do admin (se necessário)
- `instrutor/login.php` - Login do instrutor (se necessário)
- `aluno/login.php` - Login do aluno (se necessário)

**Gerenciamento de sessão:**
- Sessão PHP padrão
- Timeout: 1 hora (produção) / 2 horas (local)
- Cookie: `CFC_SESSION`
- Dados armazenados: `user_id`, `user_type`, `cfc_id`, `nome`, `email`

**Código principal:**
- `includes/auth.php` - Classe `Auth` com métodos `login()`, `logout()`, `isLoggedIn()`
- Funções globais: `isLoggedIn()`, `getCurrentUser()`, `hasPermission()`

### Perfis Existentes

| Perfil | Tipo (`usuarios.tipo`) | `cfc_id` | Descrição |
|--------|------------------------|----------|-----------|
| **Admin Master** | `admin` | `0` | Acesso global, pode gerenciar qualquer CFC |
| **Admin Secretaria** | `admin` | `> 0` | Acesso restrito ao seu CFC |
| **Secretaria** | `secretaria` | `> 0` | Operação diária, sem configurações avançadas |
| **Instrutor** | `instrutor` | `> 0` | Acesso ao painel do instrutor |
| **Aluno** | `aluno` | `> 0` | Acesso ao painel do aluno (se implementado) |

**Nota:** Atualmente, alunos não têm usuário no sistema. O acesso do aluno é feito via CPF/senha própria (se implementado) ou apenas visualização via PWA.

### Como as Permissões São Definidas

**Sistema atual:**
- **Baseado em tipo de usuário** (`usuarios.tipo`)
- **Baseado em CFC** (`usuarios.cfc_id`)

**Métodos de verificação:**
- `isLoggedIn()` - Verifica se está autenticado
- `hasPermission($permission)` - Verifica permissão específica (parcialmente implementado)
- Verificação manual: `$user['tipo'] === 'admin'`

**Middlewares/Guards:**
- Não há middleware formal
- Verificação manual no início de cada página/API:
  ```php
  if (!isLoggedIn()) {
      header('Location: ../index.php');
      exit;
  }
  ```

**Arquivos-chave:**
- `includes/auth.php` - Sistema de autenticação
- `admin/index.php` - Verificação de permissão no router
- Cada API verifica permissão individualmente

**Pendências:**
- Sistema de permissões granular não está completamente implementado
- Falta separação clara entre Admin Master e Admin Secretaria
- Documentação: `docs/ANALISE_SISTEMA_USUARIOS_PERMISSOES.md`

---

## 6. Integrações Externas

### Integrações Existentes

#### 1. ViaCEP (Consulta de CEP)

**Objetivo:** Preenchimento automático de endereço via CEP

**Onde está:**
- Configuração: `includes/config.php` - `VIA_CEP_API`
- Uso: Frontend JavaScript (busca direta na API do ViaCEP)

**Status:** ✅ Funcional

#### 2. IBGE (Municípios)

**Objetivo:** Lista de municípios brasileiros

**Onde está:**
- Configuração: `includes/config.php` - `IBGE_API`
- Dados: `admin/data/municipios_br.php` - Dados estáticos

**Status:** ✅ Funcional (usa dados estáticos)

#### 3. DETRAN

**Objetivo:** Consulta de processos DETRAN (futuro)

**Onde está:**
- Configuração: `includes/config.php` - `DETRAN_API` (vazio)
- Logs de erro: `admin/logs/exames_api_errors.log`

**Status:** ❌ Não implementado (planejado)

### Integrações Planejadas (Não Implementadas)

#### 1. Gateways de Pagamento

**Planejado:**
- Asaas (mencionado na documentação)
- Outros gateways não especificados

**Status:** ❌ Não implementado

**Onde implementar:**
- Criar service em `includes/services/PaymentService.php`
- Integrar com `financeiro_faturas` e `pagamentos`

#### 2. E-mail / SMS / WhatsApp

**Planejado:**
- Notificações por e-mail
- Notificações por SMS
- Notificações por WhatsApp

**Status:** ❌ Não implementado

**Configurações existentes (não usadas):**
- `includes/config.php` - `SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`
- `includes/config.php` - `SMS_NOTIFICATIONS`, `INTEGRATION_WHATSAPP`

**Onde implementar:**
- Criar services em `includes/services/`
- Integrar com sistema de notificações (não existe ainda)

#### 3. Google reCAPTCHA

**Configuração:**
- `includes/config.php` - `RECAPTCHA_SITE_KEY`, `RECAPTCHA_SECRET_KEY`

**Status:** ⚠️ Configurado mas uso não verificado

---

## 7. Aplicativos (PWA) de Aluno e Instrutor

### Status Geral

**PWA Instrutor:** ⚠️ **Parcialmente implementado**  
**PWA Aluno:** ⚠️ **Parcialmente implementado**

### Onde Está o Código

**PWA Assets:**
- `pwa/manifest.json` - Manifest do PWA
- `pwa/sw.js` - Service Worker
- `pwa/pwa-register.js` - Registro do service worker
- `pwa/icons/` - Ícones do PWA

**Painel Instrutor:**
- `instrutor/dashboard.php` - Dashboard principal
- `instrutor/dashboard-mobile.php` - Versão mobile
- `instrutor/aulas.php` - Listagem de aulas

**Painel Aluno:**
- `aluno/dashboard.php` - Dashboard principal
- `aluno/dashboard-mobile.php` - Versão mobile
- `aluno/aulas.php` - Listagem de aulas
- `aluno/financeiro.php` - Consulta financeira

### Fluxo do Instrutor (Atual)

1. **Login no app:**
   - `instrutor/login.php` ou `index.php` (redireciona se tipo = instrutor)

2. **Lista de aulas:**
   - `instrutor/dashboard.php` - Lista aulas do dia
   - Usa API: `admin/api/agendamento.php` (com filtro por `instrutor_id`)

3. **O que falta:**
   - ❌ Botão "Iniciar aula" (registro de km inicial, horário)
   - ❌ Botão "Encerrar aula" (km final, carga horária)
   - ❌ Registro de presença teórica via app
   - ❌ Validações de bloqueio (financeiro, faltas) antes de iniciar
   - ❌ API específica para PWA (`instrutor/api/aulas.php`)

**APIs que o app consome:**
- `admin/api/agendamento.php` - Lista de aulas (GET com filtro)
- APIs compartilhadas do admin (não ideal)

### Fluxo do Aluno (Atual)

1. **Login no app:**
   - `aluno/login.php` ou `index.php` (se implementado)

2. **Dashboard básico:**
   - `aluno/dashboard.php` - Visualização básica

3. **O que falta:**
   - ❌ Cards de resumo (processo, teórico, prático, provas, financeiro)
   - ❌ API específica para PWA (`aluno/api/dashboard.php`)
   - ❌ Agenda unificada (teórico + prático)
   - ❌ LADV digital (após aprovação teórica)
   - ❌ Declarações para trabalho/escola

**APIs que o app consome:**
- APIs compartilhadas do admin (não ideal)
- Falta API específica para aluno

### Planejamento PWA

**Documentação:** `admin/pages/_FASE-4-ARQUITETURA-GERAL.md`

**Próximos passos:**
1. Criar APIs específicas para PWA (`instrutor/api/`, `aluno/api/`)
2. Implementar funcionalidades de início/encerramento de aula
3. Implementar notificações push
4. Melhorar service worker para modo offline

---

## 8. Padrões de Layout, UI e UX

### Paleta de Cores / Identidade Visual

**Não há paleta oficial definida.** O sistema usa cores padrão do Bootstrap 5:

- **Primary:** Azul (`#0d6efd`)
- **Success:** Verde (`#198754`)
- **Danger:** Vermelho (`#dc3545`)
- **Warning:** Amarelo (`#ffc107`)
- **Info:** Ciano (`#17a2b8`)

**Observação:** Existem inconsistências conhecidas (telas com cores diferentes, ícones diferentes). Isso está no backlog para padronização.

### Componentes Visuais Padrão

**Botões:**
- Bootstrap 5 padrão
- Classes: `btn btn-primary`, `btn btn-success`, etc.

**Cards:**
- Bootstrap 5 padrão
- Classe: `card`, `card-body`, `card-header`

**Modais:**
- Bootstrap 5 Modal
- Uso extensivo no modal de aluno (`admin/pages/alunos.php`)

**Tabelas:**
- Bootstrap 5 Table
- Classes: `table table-striped table-hover`

**Páginas de listagem:**
- Filtros no topo
- Tabela com paginação (quando implementada)
- Botões de ação (editar, excluir, etc.)

**Formulários:**
- Bootstrap 5 Form
- Validação HTML5 + JavaScript customizado

### Assets de Front-end

**CSS Global:**
- `assets/css/` - CSS global
- `admin/assets/css/` - CSS específico do admin
- Bootstrap 5 via CDN

**Layout Base:**
- `admin/index.php` - Layout principal do admin (inclui header, sidebar, footer)
- `includes/layout/mobile-first.php` - Layout mobile-first (se usado)

**Componentes Compartilhados:**
- Não há sistema de componentes formal
- Código repetido entre páginas (modal de aluno é o maior exemplo)

**JavaScript:**
- `assets/js/` - JS global
- `admin/assets/js/` - JS específico do admin
- Vanilla JavaScript (sem frameworks)

**Observação:** Há falta de padronização conhecida. Muitas páginas têm código JavaScript inline, sem organização em arquivos separados.

---

## 9. Fluxos Críticos (Passo a Passo)

### 9.1. Fluxo de Nova Matrícula

**Do ponto de vista do usuário (secretaria/admin):**

1. Acessa `admin/index.php?page=alunos`
2. Clica em "Novo Aluno" ou busca aluno existente
3. Preenche dados pessoais do aluno (aba "Detalhes")
4. Preenche dados da matrícula (aba "Matrícula"):
   - Categoria CNH
   - Tipo de serviço (1ª habilitação, adição, etc.)
   - Data de matrícula
   - Valor do curso
   - Forma de pagamento
5. Salva aluno (POST para `admin/api/alunos.php`)

**O que acontece no banco:**

1. **Criação/atualização de `alunos`:**
   - Dados pessoais salvos
   - Campo `operacoes` (JSON) salvo com categoria/tipo_servico

2. **Criação de `matriculas` (se implementado):**
   - Registro de matrícula criado
   - Status: `ativa`

3. **Financeiro da matrícula:**
   - Campos `valor_total`, `forma_pagamento` salvos em `matriculas` (informativo)
   - **Não cria faturas automaticamente** (deve ser feito manualmente)

**Arquivos envolvidos:**
- `admin/pages/alunos.php` - Interface
- `admin/api/alunos.php` - API de salvamento
- `admin/api/matriculas.php` - API de matrículas (se integrado)

**Pendência conhecida:**
- Campo `operacoes` em `alunos` não está sincronizado com `matriculas`
- TODO linha 2582 em `admin/pages/alunos.php`: "integrar campos de matrícula no backend"

### 9.2. Fluxo de Aula Teórica

**Como se abre uma turma:**

1. Acessa `admin/index.php?page=turmas-teoricas`
2. Clica em "Nova Turma"
3. Preenche dados da turma:
   - Nome, sala, tipo de curso, datas
4. Configura disciplinas (wizard):
   - Seleciona disciplinas por tipo de curso
   - Define quantidade de aulas por disciplina
5. Agenda aulas em lote:
   - Sistema gera aulas automaticamente baseado nas disciplinas
   - Define instrutor, sala, data, horário para cada aula
6. Ativa turma (status: `ativa`)

**Como os alunos são associados:**

1. Na tela de detalhes da turma, clica em "Matricular Alunos"
2. Modal lista alunos elegíveis:
   - Alunos do mesmo CFC da turma
   - Status = `ativo`
   - Exames médico e psicotécnico OK
   - Financeiro OK (sem faturas vencidas)
3. Seleciona alunos e confirma matrícula
4. Sistema cria registros em `turma_matriculas`

**Como a presença é marcada:**

1. Acessa `admin/index.php?page=turma-chamada&turma_id=X`
2. Seleciona a aula (`turma_aulas_agendadas`)
3. Marca presença de cada aluno:
   - Checkbox "Presente"
   - Opcional: Justificativa se faltou
4. Salva presenças (POST para `admin/api/turma-presencas.php`)
5. Sistema atualiza `turma_presencas` e recalcula `frequencia_percentual` em `turma_matriculas`

**Arquivos envolvidos:**
- `admin/pages/turmas-teoricas-detalhes-inline.php` - Interface
- `admin/includes/TurmaTeoricaManager.php` - Lógica de turmas
- `admin/api/turma-presencas.php` - API de presenças

### 9.3. Fluxo de Aula Prática

**Agendamento:**

1. Acessa `admin/index.php?page=agendamento`
2. Seleciona data, horário, aluno, instrutor, veículo
3. Sistema valida:
   - Conflito de instrutor (já tem aula no mesmo horário)
   - Conflito de veículo (já está agendado)
   - Limite diário (máximo 3 aulas por instrutor)
   - Intervalos mínimos (30min entre blocos)
4. Cria registro em `aulas` com status `agendada`

**Confirmação / Realização da Aula:**

**Via Admin (atual):**
- Edita aula e marca como `concluida`

**Via PWA Instrutor (planejado, não implementado):**
1. Instrutor acessa `instrutor/dashboard.php`
2. Vê lista de aulas do dia
3. Clica em "Iniciar Aula":
   - Registra km inicial
   - Registra horário de início
   - Status muda para `em_andamento`
4. Ao finalizar, clica em "Encerrar Aula":
   - Registra km final
   - Calcula carga horária
   - Status muda para `concluida`

**Registro de KM:**

- Campos em `aulas`: `km_inicial`, `km_final` (se existirem)
- Ou tabela separada (não verificado)

**Presença:**

- Implícita: Se aula foi `concluida`, aluno esteve presente
- Não há tabela separada de presença prática

**Arquivos envolvidos:**
- `admin/pages/agendamento.php` - Interface de agendamento
- `admin/api/agendamento.php` - API de agendamento
- `admin/includes/controle_limite_aulas.php` - Validações

### 9.4. Fluxo de Provas

**Agendamento / Registro:**

1. Acessa modal do aluno (`admin/pages/alunos.php`)
2. Aba "Histórico" → Seção "Provas"
3. Clica em "Agendar Exame" ou "Registrar Resultado"
4. Preenche dados:
   - Tipo de exame (médico, psicotécnico, teórico, prático)
   - Data
   - Resultado (apto/inapto ou aprovado/reprovado)
5. Salva (POST para `admin/api/exames.php`)

**Atualização de Status do Aluno:**

- Sistema valida elegibilidade para turmas teóricas:
  - `GuardsExames::alunoComExamesOkParaTeoricas()` verifica se médico e psicotécnico estão OK
- Bloqueio automático se exames não estiverem OK

**Arquivos envolvidos:**
- `admin/api/exames.php` - API de exames
- `admin/includes/guards_exames.php` - Validações
- `admin/includes/ExamesRulesService.php` - Regras de negócio

### 9.5. Fluxo Financeiro

**Diferença entre "Financeiro da Matrícula" e Financeiro Real:**

1. **Financeiro da Matrícula** (`matriculas`):
   - Campos: `valor_total`, `forma_pagamento`, `status_pagamento`
   - **Informativo apenas** - não controla cobranças reais
   - Usado para exibição no modal do aluno

2. **Financeiro Real** (`financeiro_faturas`, `pagamentos`):
   - **Controle efetivo** de cobranças e pagamentos
   - Faturas criadas manualmente ou via contrato
   - Pagamentos registrados manualmente
   - Bloqueios automáticos baseados em faturas vencidas

**Como as Informações Percorrem o Sistema:**

1. **Criação de Fatura:**
   - Secretaria acessa `admin/index.php?page=financeiro-faturas`
   - Cria fatura vinculada a aluno (e opcionalmente a matrícula)
   - Define valor, vencimento, descrição
   - Status: `aberta`

2. **Registro de Pagamento:**
   - Quando aluno paga, secretaria registra pagamento
   - Cria registro em `pagamentos` vinculado à fatura
   - Atualiza status da fatura: `paga` ou `parcial`

3. **Job de Faturas Vencidas:**
   - `admin/jobs/marcar_faturas_vencidas.php` (executar via cron)
   - Marca faturas com `data_vencimento < hoje` como `vencida`

4. **Bloqueios Automáticos:**
   - `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()` verifica se há faturas vencidas
   - Aluno com faturas vencidas não pode ser matriculado em turma teórica
   - Aluno inadimplente não pode agendar aulas práticas

5. **Resumo Financeiro do Aluno:**
   - API: `admin/api/financeiro-resumo.php` (se existir)
   - Ou calculado via `FinanceiroAlunoHelper::getResumoFinanceiroAluno()`
   - Exibido no modal do aluno (aba "Financeiro")

**Arquivos envolvidos:**
- `admin/pages/financeiro-faturas.php` - Interface de faturas
- `admin/api/financeiro-faturas.php` - API de faturas
- `admin/api/financeiro-pagamentos.php` - API de pagamentos
- `admin/includes/FinanceiroService.php` - Lógica financeira
- `admin/includes/FinanceiroAlunoHelper.php` - Helpers financeiros
- `admin/jobs/marcar_faturas_vencidas.php` - Job de faturas vencidas

---

## 10. Pendências, Bugs Conhecidos e Áreas Sensíveis

### Pendências Identificadas (TODOs/FIXMEs)

| Descrição | Onde está | Impacto | Risco |
|-----------|-----------|---------|-------|
| Integrar campos de matrícula no backend | `admin/pages/alunos.php:2582` | Médio | Médio - Campo `operacoes` não sincronizado com `matriculas` |
| Implementar exportação de dados | `admin/pages/alunos.php:6206` | Baixo | Baixo - Funcionalidade não crítica |
| Adicionar eventos de aulas/provas na timeline | `admin/pages/alunos.php:10900` | Baixo | Baixo - Melhoria de UX |
| Validar combinações tipo + resultado de exames | `admin/api/exames.php:447` | Médio | Médio - Validação de negócio faltando |
| Integrar com fonte oficial de aulas contratadas | `admin/api/progresso_pratico.php:14` | Médio | Médio - Dados podem estar incorretos |
| Criar páginas faltantes (relatórios, configurações) | `admin/index.php` (vários TODOs) | Baixo | Baixo - Funcionalidades planejadas |

### Bugs Conhecidos

**Documentação de bugs:** `docs/BUG-*.md`, `docs/CORRECAO-*.md`

**Principais bugs corrigidos (documentados):**
- Duplicação de usuários (`docs/CORRECAO_DUPLICACAO_USUARIOS.md`)
- Erro 500 na criação de faturas (`docs/AUDITORIA_FATURAS_CREATE_500.md`)
- Modal travado (`docs/CORRECAO_LOOP_INFINITO_MODAIS.md`)
- Persistência de status no modal (`docs/CORRECAO_STATUS_ALUNO_IMPLEMENTADA.md`)

**Áreas com bugs conhecidos (verificar antes de mexer):**
- Modal de aluno (`admin/pages/alunos.php`) - Arquivo muito grande, histórico de bugs
- Sistema de presença teórica - Lógica complexa, já teve problemas
- Financeiro - Inconsistência entre `vencimento` e `data_vencimento` na API

### Áreas Sensíveis

**⚠️ NÃO MEXER SEM ENTENDER COMPLETAMENTE:**

1. **Sistema Multi-tenant (`cfc_id`):**
   - **Risco:** Quebrar isolamento de dados entre CFCs
   - **Onde:** Todas as queries que envolvem `alunos`, `turmas_teoricas`, `instrutores`
   - **Arquivo crítico:** `admin/api/alunos-aptos-turma-simples.php` - Lógica de filtro por CFC

2. **Cálculo de Frequência Teórica:**
   - **Risco:** Calcular frequência incorretamente
   - **Onde:** `admin/includes/TurmaTeoricaManager.php::recalcularFrequenciaAluno()`
   - **Impacto:** Alunos podem ser bloqueados incorretamente ou aprovados sem frequência mínima

3. **Validações de Bloqueio (Financeiro/Exames):**
   - **Risco:** Permitir ações não permitidas (matricular aluno inadimplente, etc.)
   - **Onde:** 
     - `admin/includes/guards/FinanceiroRulesService.php`
     - `admin/includes/guards_exames.php`
   - **Impacto:** Violação de regras de negócio críticas

4. **Job de Faturas Vencidas:**
   - **Risco:** Marcar faturas incorretamente como vencidas
   - **Onde:** `admin/jobs/marcar_faturas_vencidas.php`
   - **Impacto:** Bloqueios financeiros incorretos

5. **Modal de Aluno (`admin/pages/alunos.php`):**
   - **Risco:** Quebrar funcionalidades críticas do sistema
   - **Tamanho:** ~11.000 linhas
   - **Histórico:** Múltiplos bugs corrigidos (modal travado, persistência de status, etc.)
   - **Recomendação:** Refatorar em componentes menores antes de fazer mudanças grandes

### Módulos em Produção Críticos

**Não modificar sem plano de teste:**

1. **Sistema de Autenticação** (`includes/auth.php`)
2. **Conexão com Banco** (`includes/database.php`)
3. **APIs de Alunos** (`admin/api/alunos.php`)
4. **APIs de Turmas Teóricas** (`admin/api/turmas-teoricas.php`)
5. **Sistema Financeiro** (`admin/api/financeiro-*.php`)

---

## 11. Como Rodar o Projeto Localmente

### Pré-requisitos

- **PHP:** 8.0 ou superior
- **MySQL:** 5.7+ ou MariaDB 10.2+
- **Servidor Web:** Apache 2.4+ (XAMPP recomendado) ou Nginx
- **Extensões PHP obrigatórias:**
  - PDO
  - PDO_MySQL
  - JSON
  - cURL
  - OpenSSL
  - Session
  - mbstring

### Passo a Passo

#### 1. Clonar o Projeto

```bash
# Via Git (se disponível)
git clone <url-do-repositorio> cfc-bom-conselho
cd cfc-bom-conselho

# Ou baixar e extrair arquivos para o diretório do servidor web
# Exemplo XAMPP: C:\xampp\htdocs\cfc-bom-conselho
```

#### 2. Configurar Banco de Dados

**Criar banco de dados:**
```sql
CREATE DATABASE cfc_bom_conselho CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Configurar credenciais em `includes/config.php`:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'cfc_bom_conselho');
define('DB_USER', 'root');  // Ajustar conforme seu MySQL
define('DB_PASS', '');      // Ajustar conforme seu MySQL
```

**Ou criar arquivo `config_local.php` na raiz (recomendado para não commitar):**
```php
<?php
// Sobrescreve configurações em ambiente local
define('DB_HOST', 'localhost');
define('DB_NAME', 'cfc_bom_conselho');
define('DB_USER', 'root');
define('DB_PASS', '');
```

#### 3. Executar Instalação

**Opção 1: Via `install.php` (cria tabelas básicas):**
```
Acessar: http://localhost/cfc-bom-conselho/install.php
```

**Opção 2: Executar migrations manualmente:**
```bash
# Conectar ao MySQL
mysql -u root -p cfc_bom_conselho

# Executar migrations em ordem:
source admin/migrations/001-create-turmas-teoricas-structure.sql
source admin/migrations/004-create-matriculas-structure.sql
source admin/migrations/005-create-financeiro-faturas-structure.sql
source admin/migrations/006-create-pagamentos-structure.sql
source admin/migrations/007-create-financeiro-pagamentos-structure.sql
source admin/migrations/008-create-financeiro-configuracoes-structure.sql
```

#### 4. Popular Dados Mínimos (Seeds)

**Criar CFC padrão:**
```sql
INSERT INTO cfcs (id, nome, cnpj, ativo) VALUES 
(36, 'CFC Bom Conselho', '00.000.000/0001-00', 1);
```

**Criar usuário admin:**
```sql
INSERT INTO usuarios (nome, email, senha, tipo, cfc_id, ativo) VALUES 
('Admin', 'admin@cfc.com', '$2y$10$...', 'admin', 0, 1);
-- Senha: usar password_hash() do PHP para gerar hash
```

**Ou usar script de criação:**
- `admin/gerar-hash-senha.php` - Gera hash de senha
- `admin/criar-instrutor-carlos.php` - Exemplo de criação de usuário

#### 5. Configurar Permissões de Pastas

```bash
# Windows (PowerShell como Admin)
icacls logs /grant Users:F
icacls backups /grant Users:F
icacls uploads /grant Users:F

# Linux/Mac
chmod 755 logs/
chmod 755 backups/
chmod 755 uploads/
```

#### 6. Acessar o Sistema

**URLs principais:**

- **Login:** `http://localhost/cfc-bom-conselho/index.php`
- **Admin:** `http://localhost/cfc-bom-conselho/admin/index.php`
- **Instrutor:** `http://localhost/cfc-bom-conselho/instrutor/dashboard.php`
- **Aluno:** `http://localhost/cfc-bom-conselho/aluno/dashboard.php`

**Credenciais padrão (após criar usuário):**
- Email: `admin@cfc.com`
- Senha: (definida no seed)

### Troubleshooting

**Erro de conexão com banco:**
- Verificar se MySQL está rodando
- Verificar credenciais em `includes/config.php`
- Verificar se banco foi criado

**Erro "headers already sent":**
- Verificar se há espaços em branco antes de `<?php` em arquivos PHP
- Verificar se há `echo` ou `print` antes de `header()`

**Página em branco:**
- Ativar debug em `includes/config.php`: `define('DEBUG_MODE', true);`
- Verificar logs em `logs/php_errors.log`

**Problemas de sessão:**
- Verificar permissões da pasta de sessão do PHP
- Limpar cookies do navegador

---

## 12. Checklist para Novo Desenvolvedor

Antes de começar a implementar novas funcionalidades, complete este checklist:

### Leitura e Compreensão

- [ ] Li completamente este documento `ONBOARDING_DEV_CFC.md`
- [ ] Li o `README.md` na raiz do projeto
- [ ] Li a documentação de planejamento: `admin/pages/_PLANO-SISTEMA-CFC.md`
- [ ] Li o raio-X do projeto: `docs/RAIO-X-PROJETO-CFC-COMPLETO.md`
- [ ] Entendi a arquitetura multi-tenant (`cfc_id`)
- [ ] Entendi a diferença entre "Financeiro da Matrícula" e "Financeiro Real"

### Ambiente Local

- [ ] Subi o projeto localmente com sucesso
- [ ] Configurei o banco de dados
- [ ] Executei as migrations necessárias
- [ ] Criei dados de teste (CFC, usuário admin, aluno, instrutor)
- [ ] Acessei o sistema sem erros

### Navegação como Usuários

- [ ] Naveguei como **Admin**:
  - [ ] Acessei o dashboard
  - [ ] Visualizei lista de alunos
  - [ ] Abri modal de aluno e naveguei pelas abas
  - [ ] Acessei turmas teóricas
  - [ ] Acessei agenda de aulas práticas
  - [ ] Acessei financeiro (faturas)

- [ ] Naveguei como **Instrutor**:
  - [ ] Fiz login no painel do instrutor
  - [ ] Visualizei lista de aulas do dia
  - [ ] Entendi o que falta implementar (iniciar/encerrar aula)

- [ ] Naveguei como **Aluno** (se possível):
  - [ ] Fiz login no painel do aluno
  - [ ] Visualizei dashboard básico
  - [ ] Entendi o que falta implementar

### Teste de Fluxos Críticos

- [ ] Testei o fluxo completo de **Nova Matrícula**:
  - [ ] Criei um aluno novo
  - [ ] Preenchi dados da matrícula
  - [ ] Verifiquei o que foi salvo no banco

- [ ] Testei o fluxo de **Aulas Teóricas**:
  - [ ] Criei uma turma teórica
  - [ ] Agendei aulas
  - [ ] Matriculei um aluno na turma
  - [ ] Marquei presença em uma aula
  - [ ] Verifiquei cálculo de frequência

- [ ] Testei o fluxo de **Aulas Práticas**:
  - [ ] Agendei uma aula prática
  - [ ] Verifiquei validações de conflito
  - [ ] Entendi como funciona o registro de km (se implementado)

- [ ] Testei o fluxo de **Provas**:
  - [ ] Registrei um exame médico
  - [ ] Registrei um exame psicotécnico
  - [ ] Verifiquei bloqueio de matrícula se exames não estiverem OK

- [ ] Testei o fluxo **Financeiro**:
  - [ ] Criei uma fatura
  - [ ] Registrei um pagamento
  - [ ] Verifiquei bloqueio por inadimplência

### Identificação de Impacto

- [ ] Identifiquei se minha alteração afeta algum **fluxo crítico** listado na seção 9
- [ ] Verifiquei se há **TODOs/FIXMEs** relacionados ao módulo que vou alterar
- [ ] Verifiquei se o módulo está na lista de **áreas sensíveis** (seção 10)
- [ ] Entendi o impacto da alteração no **multi-tenant** (`cfc_id`)

### Preparação para Desenvolvimento

- [ ] Criei uma branch no Git (se usando controle de versão)
- [ ] Li o código do módulo que vou alterar completamente
- [ ] Identifiquei dependências (outros módulos, APIs, tabelas)
- [ ] Verifiquei se há testes existentes (se houver sistema de testes)
- [ ] Documentei minha alteração antes de começar (se necessário)

### Após Implementação

- [ ] Testei localmente todos os fluxos afetados
- [ ] Verifiquei logs de erro (`logs/php_errors.log`)
- [ ] Testei em diferentes perfis de usuário (admin, instrutor, aluno)
- [ ] Verifiquei se não quebrei funcionalidades existentes
- [ ] Atualizei documentação se necessário
- [ ] Commitei com mensagem descritiva

---

## 📚 Referências Adicionais

### Documentação Importante

- **Planejamento:** `admin/pages/_PLANO-SISTEMA-CFC.md`
- **Raio-X Completo:** `docs/RAIO-X-PROJETO-CFC-COMPLETO.md`
- **Raio-X Técnico:** `admin/pages/_RAIO-X-TECNICO-COMPLETO.md`
- **Checklist de Testes:** `docs/CHECKLIST-TESTES-FUNCIONAIS-CFC.md`

### Documentação por Módulo

- **Turmas Teóricas:** `docs/RAIO_X_PRESENCA_TEORICA.md`
- **Presença Teórica:** `docs/IMPLEMENTACAO_PRESENCA_TEORICA_COMPLETA.md`
- **Financeiro:** `docs/FASE3_FINANCEIRO_ALUNO.md`
- **Multi-tenant:** `docs/IMPLEMENTACAO_CFC_ALUNOS_TURMAS.md`
- **Exames:** `docs/ANALISE_RELACAO_TIPO_EXAME_ID_RESULTADO.md`

### Arquivos de Configuração

- `includes/config.php` - Configurações globais
- `includes/database.php` - Classe Database
- `includes/auth.php` - Sistema de autenticação

### Migrations

- `admin/migrations/` - Todas as migrations do sistema

---

**Desenvolvido para a equipe do CFC Bom Conselho**  
**Última atualização:** Janeiro 2025


