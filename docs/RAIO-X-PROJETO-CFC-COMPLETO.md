# RAIO-X COMPLETO DO PROJETO CFC BOM CONSELHO

**Data:** 2025-01-19  
**Objetivo:** Mapear planejamento vs implementação, funcionalidades prontas para teste e pendências do sistema CFC Bom Conselho.

---

## 1. PLANEJAMENTO DO CFC ENCONTRADO

### 1.1. Arquivos de Planejamento Identificados

| Arquivo | Caminho | Status | Descrição |
|---------|---------|--------|-----------|
| **Plano Estratégico** | `admin/pages/_PLANO-SISTEMA-CFC.md` | ✅ **ATIVO** | Documento mestre com estrutura completa do sistema, perfis, jornadas, regras de negócio e roadmap de implementação em 7 fases |
| **Fase 1** | `admin/pages/_FASE-1-LIMPEZA-E-BASE.md` | ✅ **CONCLUÍDA** | Limpeza estrutural, correção do job financeiro, alinhamento de instalação (2025-01-27) |
| **Fase 2** | `admin/pages/_FASE-2-FINANCEIRO-E-PAGAMENTOS.md` | ✅ **CONCLUÍDA** | Saneamento financeiro, correção de APIs, criação de `financeiro_configuracoes` (2025-01-28) |
| **Fase 3** | `admin/pages/_FASE-3-ACADEMICO-E-AGENDA.md` | ✅ **CONCLUÍDA** | Mapeamento completo do módulo acadêmico (turmas teóricas, aulas práticas, agenda) (2025-01-28) |
| **Fase 4** | `admin/pages/_FASE-4-ARQUITETURA-GERAL.md` | ✅ **APROVADA** | Arquitetura final do sistema, especificação de PWAs, regras de bloqueio (2025-01-28) |

### 1.2. Resumo do Planejamento Principal

**Fonte:** `admin/pages/_PLANO-SISTEMA-CFC.md`

#### Perfis do Sistema
1. **Admin Master** (Dono da Plataforma / Multi-CFC) - Visão global, configuração
2. **Admin Secretaria** (Operacional do CFC) - Operação diária
3. **Instrutor** (PWA) - Execução de aulas práticas
4. **Aluno** (PWA) - Acompanhamento do processo

#### Módulos Principais Planejados
- **Alunos & Matrículas** - Cadastro, matrícula, histórico
- **Exames** - Médico, psicotécnico, teórico, prático
- **Turmas Teóricas** - Criação, matrícula, presenças
- **Aulas Práticas** - Agenda, controle, faltas
- **Financeiro** - Faturas, pagamentos, inadimplência
- **Documentos** - LADV, declarações
- **Notificações** - Push, e-mail, SMS
- **Relatórios** - Processos, aulas, financeiro

#### Roadmap de Implementação (7 Fases)
- **Fase 0** - Raio-X do Sistema Atual ✅
- **Fase 1** - Consolidação Alunos/Matrícula/Histórico ✅
- **Fase 2** - Jornada Teórica Completa (parcial)
- **Fase 3** - Jornada Prática Completa (parcial)
- **Fase 4** - Provas (Teórica e Prática) (parcial)
- **Fase 5** - PWA Instrutor (pendente)
- **Fase 6** - PWA Aluno (pendente)
- **Fase 7** - Refinos & Limpeza (pendente)

---

## 2. VISÃO GERAL DOS MÓDULOS – CFC BOM CONSELHO

| Módulo / Área | Status | Observações rápidas | Arquivos Principais |
|---------------|--------|---------------------|---------------------|
| **Dashboard geral** | ✅ **FEITO** | Dashboard com KPIs, estatísticas, módulos por abas | `admin/pages/dashboard.php`, `admin/index.php` |
| **Alunos** | ✅ **FEITO** | Listagem, cadastro, edição, modal completo (Dados/Matrícula/Histórico), cards de resumo | `admin/pages/alunos.php`, `admin/api/alunos.php`, `admin/api/matriculas.php` |
| **Matrículas** | ✅ **FEITO** | Criação, edição, vinculação com turmas teóricas, resumo financeiro | `admin/api/matriculas.php`, `admin/includes/sistema_matricula.php` |
| **Agenda global (aulas práticas/teóricas)** | ✅ **FEITO** | Calendário visual, criação, edição, cancelamento de aulas | `admin/pages/agendamento.php`, `admin/api/agendamento.php` |
| **Agenda em ALUNOS (aba agendamentos do aluno)** | ⚠️ **PARCIAL** | Existe API `aluno-agenda.php`, mas integração no modal pode estar incompleta | `admin/api/aluno-agenda.php` |
| **Turmas teóricas** | ✅ **FEITO** | Wizard completo (4 etapas), criação, agendamento de aulas, matrícula de alunos | `admin/pages/turmas-teoricas.php`, `admin/includes/TurmaTeoricaManager.php`, `admin/api/turmas-teoricas.php` |
| **Presenças teóricas** | ✅ **FEITO** | Interface de chamada, marcação individual/lote, cálculo de frequência | `admin/pages/turma-chamada.php`, `admin/api/turma-presencas.php` |
| **Provas & Exames (teórico/prático)** | ✅ **FEITO** | Cadastro, agendamento, resultado (médico, psicotécnico, teórico, prático) | `admin/pages/exames.php`, `admin/api/exames.php` |
| **Financeiro – faturas** | ✅ **FEITO** | CRUD completo, visualização, edição, cancelamento, registro de pagamentos | `admin/pages/financeiro-faturas.php`, `admin/api/financeiro-faturas.php` |
| **Financeiro – pagamentos** | ✅ **FEITO** | Registro de pagamentos, cálculo de saldo, atualização de status | `admin/api/pagamentos.php` |
| **Financeiro – situação do aluno** | ✅ **FEITO** | Resumo financeiro calculado, exibição em cards (Detalhes, Matrícula, Histórico) | `admin/includes/FinanceiroService.php`, `admin/api/financeiro-resumo-aluno.php` |
| **Instrutores / Colaboradores** | ✅ **FEITO** | Cadastro, edição, categorias, credenciais | `admin/pages/instrutores.php`, `admin/api/instrutores.php` |
| **Veículos** | ✅ **FEITO** | Cadastro, edição, categorias CNH, disponibilidade | `admin/pages/veiculos.php`, `admin/api/veiculos.php` |
| **Salas** | ✅ **FEITO** | Cadastro, configuração, capacidade | `admin/pages/configuracoes-salas.php`, `admin/api/salas-real.php` |
| **Relatórios** | ⚠️ **PARCIAL** | Frequência teórica existe, outros relatórios planejados mas não implementados | `admin/pages/relatorio-frequencia.php`, `admin/pages/financeiro-relatorios.php` |
| **Configurações do CFC** | ⚠️ **PARCIAL** | Categorias, disciplinas, salas existem; configurações financeiras e regras de bloqueio pendentes | `admin/pages/configuracoes-categorias.php`, `admin/pages/configuracoes-disciplinas.php` |
| **Histórico do Aluno** | ✅ **FEITO** | Timeline completa, cards de resumo (teórico, prático, financeiro, provas) | `admin/pages/historico-aluno.php`, `admin/api/historico_aluno.php` |
| **Integrações (ex.: Asaas, WhatsApp, e-mail)** | ❌ **PENDENTE** | Não implementado | - |
| **Logs / auditoria (ações, backups, etc.)** | ⚠️ **PARCIAL** | Logs básicos existem, auditoria completa pendente | `admin/logs/`, `logs/php_errors.log` |
| **PWA Instrutor** | ❌ **PENDENTE** | Planejado mas não implementado | - |
| **PWA Aluno** | ❌ **PENDENTE** | Planejado mas não implementado | - |
| **Notificações Push** | ❌ **PENDENTE** | Planejado mas não implementado | - |
| **LADV Digital** | ❌ **PENDENTE** | Mencionado no planejamento, não implementado | - |
| **Bloqueios por Faltas/Inadimplência** | ⚠️ **PARCIAL** | Lógica existe mas não totalmente integrada ao agendamento | `admin/includes/guards/AgendamentoGuards.php`, `admin/includes/FinanceiroRulesService.php` |

### 2.1. Migrations Principais por Módulo

| Módulo | Migrations | Arquivos |
|--------|------------|----------|
| **Turmas Teóricas** | `001-create-turmas-teoricas-structure.sql`, `002-create-turmas-disciplinas-table.sql` | `admin/migrations/001-*.sql`, `admin/migrations/002-*.sql` |
| **Matrículas** | `004-create-matriculas-structure.sql` | `admin/migrations/004-*.sql` |
| **Financeiro** | `005-create-financeiro-faturas-structure.sql`, `006-create-pagamentos-structure.sql`, `007-create-financeiro-pagamentos-structure.sql`, `008-create-financeiro-configuracoes-structure.sql`, `009-fix-pagamentos-foreign-key-to-financeiro-faturas.sql` | `admin/migrations/005-*.sql` até `009-*.sql` |
| **Exames** | `003-alter-exames-add-provas.sql` | `admin/migrations/003-*.sql` |

---

## 3. FUNCIONALIDADES PRONTAS PARA TESTE – CFC

### A. PRONTO PARA TESTE (CFC)

#### 1. Alunos

- **URL principal:** `/admin/index.php?page=alunos`
- **Arquivos principais:**
  - `admin/pages/alunos.php` (9256 linhas - página principal)
  - `admin/api/alunos.php` (API CRUD)
  - `admin/api/matriculas.php` (API de matrículas)
  - `admin/includes/FinanceiroService.php` (Cálculo de resumo financeiro)
  - `admin/api/financeiro-resumo-aluno.php` (API de resumo)
  - `admin/api/financeiro-resumo-aluno-html.php` (API HTML renderizado)
  - `admin/api/progresso_teorico.php` (Progresso teórico)
  - `admin/api/progresso_pratico.php` (Progresso prático)
  - `admin/api/historico_aluno.php` (Histórico completo)

- **O que já deve estar funcionando:**
  - ✅ Listagem de alunos com filtros (todos, em formação, em exame, concluídos)
  - ✅ Cadastro de novo aluno
  - ✅ Edição de aluno (modal completo)
  - ✅ Modal "Detalhes do Aluno" com:
    - Card "Situação Financeira" (resumo calculado)
    - Card "Progresso Teórico" (frequência, turma)
    - Card "Progresso Prático" (aulas realizadas/contratadas)
    - Card "Provas" (status de exames)
    - Timeline completa do aluno
  - ✅ Modal "Editar Aluno" com abas:
    - **Dados:** Informações pessoais, contato, documentos
    - **Matrícula:** Dados da matrícula, resumo financeiro (read-only), vinculação teórica/prática
    - **Histórico:** Cards de resumo (teórico, prático, financeiro, provas) + timeline
  - ✅ Busca de alunos
  - ✅ Filtros por status

- **Pontos de atenção conhecidos:**
  - Modal de aluno é muito grande (9256 linhas) - pode ter problemas de performance
  - Alguns cards podem demorar para carregar (chamadas AJAX múltiplas)
  - Resumo financeiro na aba Matrícula foi recentemente ajustado para renderizar em PHP

#### 2. Financeiro – Faturas

- **URL:** `/admin/index.php?page=financeiro-faturas`
- **Arquivos principais:**
  - `admin/pages/financeiro-faturas.php` (3906 linhas)
  - `admin/api/financeiro-faturas.php` (API CRUD)
  - `admin/api/pagamentos.php` (API de pagamentos)
  - `admin/jobs/marcar_faturas_vencidas.php` (Job para marcar vencidas)

- **O que deve estar ok:**
  - ✅ Listagem de faturas com filtros (aluno, status, período)
  - ✅ Criação de nova fatura (com sugestão de descrição)
  - ✅ Visualização de fatura (modal read-only com histórico de pagamentos)
  - ✅ Edição de fatura (modal com resumo de pagamentos)
  - ✅ Cancelamento de fatura (com validações de negócio)
  - ✅ Registro de pagamento (modal com cálculo automático de saldo)
  - ✅ Cards de resumo (Total de Faturas, Faturas Pagas, Faturas Vencidas, Valor em Aberto)
  - ✅ Status visual "EM ATRASO" para faturas vencidas
  - ✅ Descrição curta inteligente (Entrada, Xª parcela, ou título completo)
  - ✅ Atualização de status automática via `recalcularStatusFatura()`

- **Pontos de atenção:**
  - Job `marcar_faturas_vencidas.php` precisa ser executado periodicamente (cron)
  - Status "EM ATRASO" é calculado em tempo real (não persiste no banco)
  - Cancelamento não permite se houver pagamentos registrados

#### 3. Turmas Teóricas

- **URL:** `/admin/index.php?page=turmas-teoricas`
- **Arquivos principais:**
  - `admin/pages/turmas-teoricas.php` (712 linhas - wizard)
  - `admin/includes/TurmaTeoricaManager.php` (649 linhas - lógica)
  - `admin/api/turmas-teoricas.php` (API CRUD)
  - `admin/api/turma-presencas.php` (API de presenças)
  - `admin/pages/turma-chamada.php` (931 linhas - interface de presenças)

- **O que deve estar ok:**
  - ✅ Criação de turma teórica (wizard em 4 etapas)
  - ✅ Agendamento de aulas por disciplina
  - ✅ Matrícula de alunos em turma (com validação de exames)
  - ✅ Marcação de presenças (individual e em lote)
  - ✅ Cálculo de frequência percentual
  - ✅ Listagem de turmas
  - ✅ Detalhes da turma

- **Pontos de atenção:**
  - Wizard pode ser complexo para novos usuários
  - Frequência pode não recalcular automaticamente (verificar triggers)

#### 4. Agenda / Agendamento

- **URL:** `/admin/index.php?page=agendamento`
- **Arquivos principais:**
  - `admin/pages/agendamento.php` (4113 linhas - calendário visual)
  - `admin/api/agendamento.php` (894 linhas - API principal)
  - `admin/api/verificar-disponibilidade.php` (288 linhas - validações)
  - `admin/pages/listar-aulas.php` (398 linhas - lista de aulas)

- **O que deve estar ok:**
  - ✅ Calendário visual de aulas (teóricas + práticas)
  - ✅ Criação de aula prática (com validações de conflito)
  - ✅ Edição de aula
  - ✅ Cancelamento de aula
  - ✅ Validação de disponibilidade (instrutor, veículo, limite diário)
  - ✅ Listagem de aulas em formato de cards

- **Pontos de atenção:**
  - Arquivo `agendamento.php` muito grande (4113 linhas) - difícil manutenção
  - Validações de bloqueio por inadimplência/faltas podem não estar totalmente integradas
  - Marcação de faltas em aulas práticas não está implementada (apenas status cancelada/concluída)

#### 5. Provas & Exames

- **URL:** `/admin/index.php?page=exames&tipo={medico|psicotecnico|teorico|pratico}`
- **Arquivos principais:**
  - `admin/pages/exames.php` (página principal)
  - `admin/api/exames.php` (API CRUD)
  - `admin/includes/ExamesRulesService.php` (Regras de validação)
  - `admin/includes/guards/AgendamentoGuards.php` (Validações de pré-requisitos)

- **O que deve estar ok:**
  - ✅ Cadastro de exames (médico, psicotécnico, teórico, prático)
  - ✅ Agendamento de provas
  - ✅ Registro de resultados
  - ✅ Filtros por tipo
  - ✅ Validação de pré-requisitos (ex: não agendar teórica sem médico+psico)

- **Pontos de atenção:**
  - Integração com LADV não implementada
  - Validações podem estar incompletas

#### 6. Instrutores

- **URL:** `/admin/index.php?page=instrutores`
- **Arquivos principais:**
  - `admin/pages/instrutores.php`
  - `admin/api/instrutores.php`

- **O que deve estar ok:**
  - ✅ Cadastro de instrutor
  - ✅ Edição de instrutor
  - ✅ Categorias de habilitação
  - ✅ Credenciais

#### 7. Veículos

- **URL:** `/admin/index.php?page=veiculos`
- **Arquivos principais:**
  - `admin/pages/veiculos.php`
  - `admin/api/veiculos.php`

- **O que deve estar ok:**
  - ✅ Cadastro de veículo
  - ✅ Edição de veículo
  - ✅ Categorias CNH compatíveis
  - ✅ Controle de disponibilidade

#### 8. Salas

- **URL:** `/admin/index.php?page=configuracoes-salas`
- **Arquivos principais:**
  - `admin/pages/configuracoes-salas.php`
  - `admin/api/salas-real.php`

- **O que deve estar ok:**
  - ✅ Cadastro de sala
  - ✅ Edição de sala
  - ✅ Capacidade
  - ✅ Equipamentos

#### 9. Dashboard

- **URL:** `/admin/index.php?page=dashboard`
- **Arquivos principais:**
  - `admin/pages/dashboard.php`
  - `admin/index.php` (estatísticas)

- **O que deve estar ok:**
  - ✅ KPIs gerais (alunos, instrutores, aulas, veículos)
  - ✅ Módulos por abas (Visão Geral, Fases, Volume, Financeiro, Agenda, Exames, Prazos)
  - ✅ Atalhos rápidos (Novo Aluno, Nova Fatura, etc.)

### B. PARCIAL / PRECISA DE AJUSTES

#### 1. Agenda em ALUNOS (aba agendamentos)

- **Status:** ⚠️ API existe mas integração pode estar incompleta
- **Arquivos:** `admin/api/aluno-agenda.php`
- **O que falta:**
  - Verificar se está sendo chamada no modal de aluno
  - Verificar se exibe corretamente na aba de agendamentos

#### 2. Relatórios

- **Status:** ⚠️ Frequência teórica existe, outros pendentes
- **Arquivos existentes:**
  - `admin/pages/relatorio-frequencia.php` ✅
  - `admin/pages/financeiro-relatorios.php` ✅ (inadimplência)
- **O que falta:**
  - Relatório de Conclusão Prática
  - Relatório de Provas (Taxa de Aprovação)
  - Relatórios avançados de aulas práticas

#### 3. Configurações do CFC

- **Status:** ⚠️ Categorias, disciplinas, salas existem; outras pendentes
- **Arquivos existentes:**
  - `admin/pages/configuracoes-categorias.php` ✅
  - `admin/pages/configuracoes-disciplinas.php` ✅
  - `admin/pages/configuracoes-salas.php` ✅
- **O que falta:**
  - Configurações Financeiras (regras de inadimplência, dias de bloqueio)
  - Regras de Bloqueio (faltas, inadimplência) - UI de configuração
  - Modelos de Documentos (LADV, declarações)

#### 4. Bloqueios por Faltas/Inadimplência

- **Status:** ⚠️ Lógica existe mas não totalmente integrada
- **Arquivos:**
  - `admin/includes/guards/AgendamentoGuards.php` (validações)
  - `admin/includes/FinanceiroRulesService.php` (regras financeiras)
- **O que falta:**
  - Marcação de faltas em aulas práticas (campo/tabela)
  - Bloqueio automático após 3 faltas práticas
  - Bloqueio automático por inadimplência no agendamento
  - UI para configurar regras de bloqueio

#### 5. Progresso Prático

- **Status:** ⚠️ Funciona mas usa estimativa
- **Arquivo:** `admin/api/progresso_pratico.php`
- **O que falta:**
  - Integração com `aulas_slots` ou `matriculas` para `total_contratadas` oficial
  - Consulta de configuração de categoria para limite oficial

### C. AINDA NÃO IMPLEMENTADO

#### 1. PWA Instrutor

- **Status:** ❌ Planejado mas não implementado
- **Referência:** `admin/pages/_PLANO-SISTEMA-CFC.md` (Seção 1.3, 3.3, 5.1)
- **O que falta:**
  - Tela de agenda do dia
  - Tela de detalhe da aula (iniciar/encerrar)
  - Registro de KM inicial/final
  - Botões WhatsApp/Ligar
  - Exibição de LADV
  - Validações de bloqueio no app
  - Notificações push

#### 2. PWA Aluno

- **Status:** ❌ Planejado mas não implementado
- **Referência:** `admin/pages/_PLANO-SISTEMA-CFC.md` (Seção 1.4, 3.4, 5.2)
- **O que falta:**
  - Dashboard com cards de resumo
  - Agenda (teórico + prático)
  - Financeiro básico
  - LADV e declarações
  - Notificações (alterações, lembretes)

#### 3. LADV Digital

- **Status:** ❌ Mencionado no planejamento, não implementado
- **Referência:** `admin/pages/_PLANO-SISTEMA-CFC.md` (Seção 4.5)
- **O que falta:**
  - Tabela/modelo de LADV
  - Geração de LADV após aprovação teórica
  - Exibição em PWA Instrutor/Aluno
  - Upload/gerador de LADV

#### 4. Integrações Externas

- **Status:** ❌ Não implementado
- **O que falta:**
  - Integração com Asaas (pagamentos)
  - Integração com WhatsApp (notificações)
  - Integração com e-mail (notificações)
  - Integração com DETRAN (futuro)

#### 5. Notificações Push

- **Status:** ❌ Não implementado
- **O que falta:**
  - Sistema de notificações push
  - Notificações para aluno (aula em 10 min, alterações, bloqueios)
  - Notificações para instrutor (alterações na agenda)
  - Notificações para secretaria (faltas, problemas)

#### 6. Declarações

- **Status:** ❌ Não implementado
- **O que falta:**
  - Modelo de declaração para escola/trabalho
  - Gerador de declarações
  - Histórico de declarações emitidas

---

## 4. PENDÊNCIAS / PRÓXIMOS PASSOS – CFC BOM CONSELHO

### Alunos

- [ ] Otimizar modal de aluno (quebrar em componentes menores)
- [ ] Melhorar performance de carregamento dos cards (unificar APIs)
- [ ] Verificar se aba "Agendamentos" no modal está funcionando
- [ ] Adicionar validação de CPF duplicado no cadastro

### Agenda (global e por aluno)

- [ ] Refatorar `agendamento.php` (quebrar em componentes)
- [ ] Implementar marcação de faltas em aulas práticas (campo `falta` ou tabela `aulas_faltas`)
- [ ] Integrar bloqueio por inadimplência no agendamento (chamar `FinanceiroRulesService`)
- [ ] Integrar bloqueio por faltas práticas (3 faltas = bloqueio)
- [ ] Melhorar validação de conflitos (intervalos, limites diários)
- [ ] Adicionar campo `tipo_veiculo` em `aulas` para alinhar com `aulas_slots`
- [ ] Integrar validação de LADV antes de agendar aula prática

### Turmas Teóricas

- [ ] Verificar se frequência recalcula automaticamente (criar trigger se necessário)
- [ ] Melhorar UX da interface de chamada (filtros, busca, indicadores de frequência)
- [ ] Adicionar validação de frequência mínima (ex: 75%)
- [ ] Implementar conclusão automática quando carga horária for cumprida

### Financeiro

- [ ] Criar página de Configurações Financeiras (`financeiro-configuracoes.php`)
- [ ] Adicionar UI para configurar regras de inadimplência (dias, bloqueios)
- [ ] Implementar bloqueio automático por inadimplência no agendamento
- [ ] Melhorar relatórios financeiros (gráficos, exportação)
- [ ] Adicionar campo "reteste" em pagamentos (para reteste prático)

### Provas & Exames

- [ ] Integrar LADV com provas (liberar após aprovação teórica)
- [ ] Melhorar validações de pré-requisitos (sequência lógica)
- [ ] Adicionar card "Provas" no modal de aluno (já existe estrutura, verificar se funciona)
- [ ] Criar relatório de Taxa de Aprovação

### Progresso Prático

- [ ] Corrigir cálculo de `total_contratadas` (usar `aulas_slots` ou `matriculas`)
- [ ] Consultar configuração de categoria para limite oficial
- [ ] Adicionar contagem de faltas práticas no progresso
- [ ] Melhorar exibição de progresso (gráficos, percentuais)

### Progresso Teórico

- [ ] Melhorar para mostrar histórico completo (não apenas última matrícula)
- [ ] Somar progresso de múltiplas turmas se aluno mudou de turma
- [ ] Adicionar gráfico de frequência ao longo do tempo

### Relatórios

- [ ] Criar relatório de Conclusão Prática
- [ ] Criar relatório de Provas (Taxa de Aprovação)
- [ ] Melhorar relatório de Inadimplência (gráficos, filtros)
- [ ] Adicionar exportação em PDF/Excel para todos os relatórios

### Configurações do CFC

- [ ] Criar página de Configurações Financeiras
- [ ] Criar UI para Regras de Bloqueio (faltas, inadimplência)
- [ ] Criar gerenciador de Modelos de Documentos (LADV, declarações)
- [ ] Adicionar configurações de notificações (templates, variáveis)

### PWA Instrutor

- [ ] Definir arquitetura técnica (rotas, APIs, autenticação)
- [ ] Criar tela de agenda do dia
- [ ] Criar tela de detalhe da aula (iniciar/encerrar)
- [ ] Implementar registro de KM inicial/final
- [ ] Adicionar botões WhatsApp/Ligar
- [ ] Implementar exibição de LADV
- [ ] Integrar validações de bloqueio no app
- [ ] Implementar notificações push

### PWA Aluno

- [ ] Definir arquitetura técnica (rotas, APIs, autenticação)
- [ ] Criar dashboard com cards de resumo
- [ ] Criar tela de agenda (teórico + prático)
- [ ] Criar tela de financeiro básico
- [ ] Implementar exibição de LADV e declarações
- [ ] Implementar notificações (alterações, lembretes)

### LADV Digital

- [ ] Definir estrutura de dados (tabela `ladv` ou campo em `exames`)
- [ ] Criar gerador de LADV (template PDF/imagem)
- [ ] Implementar liberação automática após aprovação teórica
- [ ] Adicionar upload manual de LADV
- [ ] Integrar exibição em PWA Instrutor/Aluno

### Integrações

- [ ] Integração com Asaas (pagamentos online)
- [ ] Integração com WhatsApp (notificações)
- [ ] Integração com e-mail (notificações)
- [ ] Integração com DETRAN (futuro - consulta de processos)

### Notificações

- [ ] Sistema de notificações push (PWA)
- [ ] Notificações para aluno (aula em 10 min, alterações, bloqueios)
- [ ] Notificações para instrutor (alterações na agenda)
- [ ] Notificações para secretaria (faltas, problemas)
- [ ] Templates de notificações configuráveis

### Limpeza de Código

- [ ] Remover arquivos legados identificados (após verificar uso)
- [ ] Mover APIs legadas para `admin/api/legacy/`
- [ ] Mover páginas legadas para `admin/pages/legacy/`
- [ ] Remover arquivos temporários/debug
- [ ] Padronizar nomenclatura de tabelas/campos
- [ ] Documentar quando usar `turma_aulas_agendadas` vs `aulas` com `tipo_aula = 'teorica'`

### Outros módulos relevantes do CFC

- [ ] Sistema de CFCs (multi-tenant) - se aplicável
- [ ] Gestão de Usuários e Permissões (refinar)
- [ ] Sistema de Logs/Auditoria completo
- [ ] Backup automático configurável
- [ ] Sistema de manutenção de veículos (existe `agendar-manutencao.php` mas não analisado)

---

## 5. RESUMO EXECUTIVO

### Estado Atual

O sistema CFC Bom Conselho está **parcialmente implementado**, com os módulos core (Alunos, Financeiro, Turmas Teóricas, Agenda, Provas) funcionais e prontos para teste. Os módulos de PWA (Instrutor e Aluno) e integrações externas ainda não foram implementados.

### Principais Pontos Mapeados

- ✅ **Módulos Core Implementados:**
  - Alunos (CRUD completo, modal avançado, histórico)
  - Financeiro (faturas, pagamentos, resumo por aluno)
  - Turmas Teóricas (wizard completo, presenças, frequência)
  - Agenda (calendário visual, validações de conflito)
  - Provas & Exames (CRUD, validações básicas)
  - Instrutores, Veículos, Salas (CRUD básico)

- ⚠️ **Módulos Parciais:**
  - Bloqueios por faltas/inadimplência (lógica existe, integração incompleta)
  - Relatórios (alguns existem, outros pendentes)
  - Configurações (básicas existem, avançadas pendentes)
  - Progresso Prático (funciona mas usa estimativa)

- ❌ **Módulos Pendentes:**
  - PWA Instrutor
  - PWA Aluno
  - LADV Digital
  - Notificações Push
  - Integrações externas (Asaas, WhatsApp, e-mail)

### Riscos e Gaps

- **Gaps Funcionais:**
  - Marcação de faltas em aulas práticas não implementada
  - Bloqueios automáticos não totalmente integrados
  - LADV não implementado (mencionado no planejamento)
  - Progresso prático usa estimativa ao invés de fonte oficial

- **Gaps Técnicos:**
  - Arquivos muito grandes (ex: `alunos.php` 9256 linhas, `agendamento.php` 4113 linhas)
  - Código legado ainda em uso (APIs "clean", páginas "fixed")
  - Duplicação conceitual (aulas teóricas em duas tabelas)
  - Inconsistências de nomenclatura (vencimento vs data_vencimento - já corrigido parcialmente)

- **Riscos:**
  - Performance pode degradar com muitos alunos (modais grandes)
  - Manutenção difícil devido a arquivos grandes
  - Falta de testes automatizados
  - Documentação técnica incompleta

### Sugestão de Caminho (Alto Nível)

1. **Curto Prazo (Próximas 2-4 semanas):**
   - Finalizar integração de bloqueios (faltas/inadimplência)
   - Implementar marcação de faltas em aulas práticas
   - Corrigir cálculo de progresso prático
   - Criar relatórios pendentes
   - Limpeza de código legado

2. **Médio Prazo (1-2 meses):**
   - Implementar LADV Digital
   - Refatorar arquivos grandes (quebrar em componentes)
   - Melhorar sistema de notificações (e-mail básico)
   - Criar PWA Instrutor (MVP)

3. **Longo Prazo (3-6 meses):**
   - Criar PWA Aluno completo
   - Implementar notificações push
   - Integrações externas (Asaas, WhatsApp)
   - Sistema multi-CFC (se aplicável)

---

**Última atualização:** 2025-01-19  
**Próxima revisão:** Após testes funcionais

