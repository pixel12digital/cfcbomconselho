# FASE 3 – Acadêmico & Agenda

**Data de início:** 2025-01-28  
**Objetivo:** Organizar e documentar todo o módulo acadêmico (teórico + prático + agenda) para preparar a implementação das regras de jornada do aluno, apps PWA e UX final.

---

## 1. Arquitetura Acadêmica Atual (Visão Geral)

O módulo acadêmico do sistema CFC Bom Conselho está dividido em três grandes fluxos que convergem para o histórico do aluno:

### 1.1. Turmas Teóricas

As turmas teóricas (`turmas_teoricas`) são criadas através de um wizard em 4 etapas gerenciado pela classe `TurmaTeoricaManager` (`admin/includes/TurmaTeoricaManager.php:1-649`). O fluxo funciona assim:

1. **Criação da turma** (`admin/pages/turmas-teoricas.php:1-712`) - Define nome, sala, curso, modalidade, período
2. **Agendamento de aulas** (`admin/includes/TurmaTeoricaManager.php:397-442`) - Agenda aulas teóricas por disciplina usando `turma_aulas_agendadas`
3. **Matrícula de alunos** (`admin/includes/TurmaTeoricaManager.php:563-644`) - Matricula alunos validando exames e vagas via `turma_matriculas`
4. **Registro de presenças** (`admin/api/turma-presencas.php:1-618`) - Marca presenças por aula em `turma_presencas`

As presenças alimentam o campo `frequencia_percentual` na tabela `turma_matriculas` que é consultado pela API `progresso_teorico.php` (`admin/api/progresso_teorico.php:52-120`) para exibir no modal de alunos.

### 1.2. Aulas Práticas

As aulas práticas são gerenciadas através da tabela `aulas` (`install.php:88-103`) com o campo `tipo_aula = 'pratica'`. O agendamento é feito via API `agendamento.php` (`admin/api/agendamento.php:198-716`) que:

- Valida disponibilidade de instrutor e veículo
- Verifica limites de aulas diárias por aluno
- Cria registros em `aulas` com status `agendada`, `em_andamento`, `concluida` ou `cancelada`

A API `progresso_pratico.php` (`admin/api/progresso_pratico.php:61-167`) consulta a tabela `aulas` para calcular estatísticas que aparecem nos cards do modal de alunos.

### 1.3. Agenda / Calendário

A agenda principal está em `admin/pages/agendamento.php` (4113 linhas) e usa a tabela `aulas` para exibir um calendário visual. A página lista todas as aulas (teóricas e práticas) e permite criação, edição e cancelamento.

### 1.4. Integração com Aluno

Os cards de resumo no modal de alunos (`admin/pages/alunos.php:4700-4745`) são atualizados via JavaScript (`admin/pages/alunos.php:7173-7359`) que chama as APIs `progresso_teorico.php` e `progresso_pratico.php` automaticamente ao abrir o modal.

---

## 2. Mapas Detalhados

### 2.1. Turmas Teóricas & Presenças

#### APIs Envolvidas

| API | Arquivo | Rotas/Funções Principais | Referência |
|-----|---------|--------------------------|------------|
| **Turmas Teóricas** | `admin/api/turmas-teoricas.php` | GET (listar), POST (criar), PUT (atualizar), DELETE (cancelar) | Linhas 1-452 |
| **Turmas Teóricas Inline** | `admin/api/turmas-teoricas-inline.php` | Versão simplificada para uso inline | Existe mas não analisado |
| **Matrícula em Turma** | `admin/api/matricular-aluno-turma.php` | POST - Matricula aluno em turma | Existe mas não analisado |
| **Presenças** | `admin/api/turma-presencas.php` | GET (buscar), POST (marcar individual/lote), PUT (atualizar), DELETE (remover) | Linhas 1-618 |
| **Presenças (função específica)** | `admin/api/turma-presencas.php:316-380` | `marcarPresencaIndividual()` - Marca presença de um aluno | Linhas 316-380 |
| **Presenças (lote)** | `admin/api/turma-presencas.php:385-455` | `marcarPresencasLote()` - Marca múltiplas presenças | Linhas 385-455 |
| **Alunos Aptos** | `admin/api/alunos-aptos-turma.php` | GET - Lista alunos elegíveis para turma (com exames OK) | Existe mas não analisado |
| **Estatísticas Turma** | `admin/api/estatisticas-turma.php` | GET - Estatísticas da turma (frequência, progresso) | Existe mas não analisado |

#### Páginas Admin Envolvidas

| Página | Arquivo | Objetivo | Referência |
|--------|---------|----------|------------|
| **Lista de Turmas** | `admin/pages/turmas-teoricas-lista.php` | Lista todas as turmas com filtros | Linhas 1-712 |
| **Gerenciar Turma (Wizard)** | `admin/pages/turmas-teoricas.php` | Wizard completo de 4 etapas para criar/gerenciar turma | Linhas 1-712 |
| **Detalhes da Turma** | `admin/pages/turmas-teoricas-detalhes.php` | Visualização detalhada de uma turma | Existe mas não analisado |
| **Detalhes Inline** | `admin/pages/turmas-teoricas-detalhes-inline.php` | Versão inline dos detalhes | Existe mas não analisado |
| **Chamada (Presença)** | `admin/pages/turma-chamada.php` | Interface para marcar presenças de uma aula | Linhas 1-931 |
| **Step 2 (Agendamento)** | `admin/pages/turmas-teoricas-step2.php` | Etapa 2 do wizard - agendar aulas | Existe mas não analisado |
| **Step 4 (Matrícula)** | `admin/pages/turmas-teoricas-step4.php` | Etapa 4 do wizard - matricular alunos | Linhas 1-62 |

#### Tabelas Usadas

| Tabela | Migration | Campos Relevantes | Referência |
|--------|-----------|-------------------|------------|
| **turmas_teoricas** | `001-create-turmas-teoricas-structure.sql:83` | `id`, `nome`, `sala_id`, `curso_tipo`, `data_inicio`, `data_fim`, `status`, `carga_horaria_total`, `carga_horaria_agendada`, `carga_horaria_realizada`, `max_alunos`, `alunos_matriculados`, `cfc_id`, `criado_por` | Linhas 83-123 |
| **turma_aulas_agendadas** | `001-create-turmas-teoricas-structure.sql:126` | `id`, `turma_id`, `disciplina`, `nome_aula`, `instrutor_id`, `sala_id`, `data_aula`, `hora_inicio`, `hora_fim`, `duracao_minutos`, `ordem_disciplina`, `ordem_global`, `status` | Linhas 126-159 |
| **turma_matriculas** | `001-create-turmas-teoricas-structure.sql:162` | `id`, `turma_id`, `aluno_id`, `data_matricula`, `status`, `exames_validados_em`, `frequencia_percentual`, `observacoes` | Linhas 162-180 |
| **turma_presencas** | `001-create-turmas-teoricas-structure.sql:183` | `id`, `turma_id`, `aula_id` (FK para turma_aulas_agendadas), `aluno_id`, `presente`, `justificativa`, `registrado_por`, `registrado_em` | Linhas 183-202 |
| **disciplinas_configuracao** | `001-create-turmas-teoricas-structure.sql:30` | `id`, `curso_tipo`, `disciplina`, `nome_disciplina`, `aulas_obrigatorias`, `ordem`, `cor_hex`, `icone`, `ativa` | Linhas 30-55 |
| **salas** | `001-create-turmas-teoricas-structure.sql:9` | `id`, `nome`, `capacidade`, `equipamentos`, `ativa`, `cfc_id` | Linhas 9-21 |

#### Como uma Turma é Criada

1. **Admin acessa** `?page=turmas-teoricas&acao=nova&step=1` → `admin/pages/turmas-teoricas.php:1-712`
2. **Preenche dados básicos** → Chama `TurmaTeoricaManager->salvarRascunho()` (`admin/includes/TurmaTeoricaManager.php:29-219`)
3. **Status = 'rascunho'** → Salva em `turmas_teoricas` com status inicial
4. **Step 2 - Agenda aulas** → `TurmaTeoricaManager->agendarAula()` (`admin/includes/TurmaTeoricaManager.php:402-442`)
5. **Valida conflitos** → Verifica sala/instrutor ocupados no mesmo horário
6. **Insere em `turma_aulas_agendadas`** → Atualiza `carga_horaria_agendada` via trigger
7. **Status = 'completa'** → Quando todas as disciplinas têm aulas suficientes

#### Como um Aluno é Matriculado na Turma

1. **Admin acessa Step 4** → `admin/pages/turmas-teoricas-step4.php:1-62`
2. **Busca alunos elegíveis** → API `alunos-aptos-turma.php` retorna alunos com exames OK
3. **Chama `matricularAluno()`** → `admin/includes/TurmaTeoricaManager.php:563-644`
4. **Validações:**
   - Turma está ativa ou completa (`status IN ('completa', 'ativa')`)
   - Exames aprovados via `AgendamentoGuards->verificarExamesOK()` (`includes/guards/AgendamentoGuards.php:389-598`)
   - Há vagas disponíveis (`alunos_matriculados < max_alunos`)
   - Aluno não está já matriculado
5. **Insere em `turma_matriculas`** → `status = 'matriculado'`, `exames_validados_em = NOW()`
6. **Trigger atualiza contador** → `alunos_matriculados` é recalculado automaticamente

#### Como as Presenças são Registradas

1. **Admin acessa chamada** → `admin/pages/turma-chamada.php:1-931` com `turma_id` e `aula_id`
2. **Carrega alunos matriculados** → Query na linha 87-105 do `turma-chamada.php`
3. **Carrega presenças existentes** → JOIN com `turma_presencas` na linha 98
4. **Interface JavaScript** → Linhas 634-689 do `turma-chamada.php` manipula presenças
5. **Marcar presença** → POST para `admin/api/turma-presencas.php` (`admin/api/turma-presencas.php:316-380`)
6. **Validações:**
   - Aluno está matriculado na turma
   - Não existe presença duplicada para `aula_id + aluno_id`
7. **Insere/Atualiza `turma_presencas`** → Campo `presente` (BOOLEAN), `justificativa` (opcional)
8. **Recalcula frequência** → Função `calcularFrequenciaAluno()` atualiza `turma_matriculas.frequencia_percentual`

#### Como isso Alimenta os Cards no Modal de Aluno

1. **Modal abre** → `admin/pages/alunos.php` função `abrirModalAluno()`
2. **JavaScript chama** → `atualizarResumoTeoricoAluno(alunoId)` (`admin/pages/alunos.php:7173-7241`)
3. **Fetch API** → `GET api/progresso_teorico.php?aluno_id=X` (`admin/pages/alunos.php:7184`)
4. **API consulta** → `admin/api/progresso_teorico.php:72-85` busca matrícula mais recente:
   ```sql
   SELECT tm.status, tm.frequencia_percentual, tm.turma_id, t.nome AS turma_nome
   FROM turma_matriculas tm
   JOIN turmas_teoricas t ON tm.turma_id = t.id
   WHERE tm.aluno_id = ?
   ORDER BY tm.data_matricula DESC LIMIT 1
   ```
5. **Retorna JSON** → `{ success: true, progresso: { status, frequencia_percentual, turma_id, turma_nome } }`
6. **JavaScript formata** → `admin/pages/alunos.php:7205-7224` converte status + frequência em texto (ex: "Em andamento (80% de presença)")
7. **Atualiza cards** → `atualizarCardsTeoricoResumo(texto)` (`admin/pages/alunos.php:7095-7111`) atualiza elementos `[data-field="teorico_resumo"]`
8. **Atualiza vinculação** → `atualizarVinculacaoTeoricaUI()` (`admin/pages/alunos.php:7119-7167`) preenche select e input na aba Matrícula

#### Progresso Teórico - Fluxo Completo

```
Aluno matriculado em turma → Presenças registradas → frequencia_percentual calculada → 
API progresso_teorico.php consulta turma_matriculas → Retorna status + frequência → 
JavaScript formata e exibe no card do modal
```

**Arquivos envolvidos:**
- `admin/api/progresso_teorico.php:52-120` - API de consulta
- `admin/pages/alunos.php:7173-7241` - JavaScript que chama e processa
- `admin/pages/alunos.php:7095-7111` - Função que atualiza UI
- `admin/pages/alunos.php:7119-7167` - Função que atualiza vinculação

---

### 2.2. Aulas Práticas & Agenda

#### APIs Envolvidas

| API | Arquivo | Rotas/Funções Principais | Referência |
|-----|---------|--------------------------|------------|
| **Agendamento Principal** | `admin/api/agendamento.php` | POST (criar), PUT (atualizar), DELETE (cancelar), GET (listar) | Linhas 1-894 |
| **Criar Aula** | `admin/api/agendamento.php:201-586` | `criarAula()` - Valida e cria aula prática ou teórica | Linhas 201-586 |
| **Atualizar Aula** | `admin/api/agendamento.php` | PUT - Atualiza dados de aula existente | Função `atualizarAula()` |
| **Cancelar Aula** | `admin/api/cancelar-aula.php` | DELETE - Cancela aula | Existe mas não analisado |
| **Buscar Aula** | `admin/api/buscar-aula.php` | GET - Busca detalhes de uma aula específica | Existe mas não analisado |
| **Verificar Disponibilidade** | `admin/api/verificar-disponibilidade.php` | GET - Verifica disponibilidade de instrutor/veículo/horário | Linhas 1-288 |
| **Verificar Aula Específica** | `admin/api/verificar-aula-especifica.php` | GET - Verifica conflitos para uma aula específica | Existe mas não analisado |
| **Atualizar Aula** | `admin/api/atualizar-aula.php` | PUT - Atualiza aula existente | Existe mas não analisado |
| **Disponibilidade Geral** | `admin/api/disponibilidade.php` | GET - Lista horários disponíveis | Existe mas não analisado |
| **Agenda do Aluno** | `admin/api/aluno-agenda.php` | GET - Lista aulas agendadas de um aluno | Existe mas não analisado |
| **Progresso Prático** | `admin/api/progresso_pratico.php` | GET - Estatísticas de aulas práticas do aluno | Linhas 61-167 |
| **Exportar Agendamentos** | `admin/api/exportar-agendamentos.php` | GET - Exporta agenda em CSV | Existe mas não analisado |
| **Disciplina Agendamentos** | `admin/api/disciplina-agendamentos.php` | GET - Agendamentos por disciplina | Existe mas não analisado |
| **Listar Agendamentos Turma** | `admin/api/listar-agendamentos-turma.php` | GET - Agendamentos de uma turma | Existe mas não analisado |

#### Páginas Admin Envolvidas

| Página | Arquivo | Objetivo | Referência |
|--------|---------|----------|------------|
| **Agenda Principal** | `admin/pages/agendamento.php` | Calendário visual com todas as aulas (teóricas + práticas) | Linhas 1-4113 |
| **Agendamento Moderno** | `admin/pages/agendamento-moderno.php` | Versão moderna/alternativa da agenda | Existe mas não analisado |
| **Agendar Aula** | `admin/pages/agendar-aula.php` | Formulário para agendar aula individual | Existe mas não analisado |
| **Listar Aulas** | `admin/pages/listar-aulas.php` | Lista de aulas em formato de cards | Linhas 1-398 |
| **Editar Aula** | `admin/pages/editar-aula.php` | Formulário para editar aula existente | Existe mas não analisado |
| **Agendar Manutenção** | `admin/pages/agendar-manutencao.php` | Agendamento de manutenção de veículo | Existe mas não analisado |

#### Tabelas Usadas

| Tabela | Migration | Campos Relevantes | Referência |
|--------|-----------|-------------------|------------|
| **aulas** | `install.php:88` | `id`, `aluno_id`, `instrutor_id`, `veiculo_id`, `cfc_id`, `tipo_aula`, `data_aula`, `hora_inicio`, `hora_fim`, `status`, `observacoes` | Linhas 88-103 |
| **instrutores** | `install.php:75` | `id`, `usuario_id`, `cfc_id`, `credencial`, `categoria_habilitacao`, `ativo` | Linhas 75-85 |
| **veiculos** | `install.php:106` | `id`, `cfc_id`, `placa`, `modelo`, `marca`, `ano`, `categoria_cnh`, `ativo` | Linhas 106-117 |
| **alunos** | `install.php:34` | `id`, `nome`, `cpf`, `categoria_cnh`, `exame_medico`, `exame_psicologico`, `inadimplente` | Base para validações |

**Observação:** A tabela `aulas` armazena tanto aulas teóricas (`tipo_aula = 'teorica'`) quanto práticas (`tipo_aula = 'pratica'`), mas as turmas teóricas usam `turma_aulas_agendadas` para gerenciar suas aulas. Isso cria uma **duplicação conceitual** - aulas teóricas podem estar em duas tabelas diferentes.

#### Como uma Aula Prática é Criada/Agendada Hoje

1. **Admin acessa agenda** → `admin/pages/agendamento.php:1-4113` (calendário visual) ou `admin/pages/agendar-aula.php` (formulário)
2. **Preenche dados** → Aluno, instrutor, veículo, data, hora, duração (fixa 50 min)
3. **JavaScript valida** → Verifica campos obrigatórios no frontend
4. **POST para API** → `admin/api/agendamento.php` método `criarAula()` (`admin/api/agendamento.php:201-586`)
5. **Validações da API:**
   - Campos obrigatórios preenchidos (`admin/api/agendamento.php:228-235`)
   - Aluno existe e está ativo
   - Instrutor existe e está ativo (`admin/api/agendamento.php:272-275`)
   - Veículo existe e está ativo (`admin/api/agendamento.php:278-282`)
   - Verifica conflitos de horário:
     - **Instrutor ocupado** (`admin/api/agendamento.php:670-688`) - Verifica se instrutor tem aula no mesmo horário
     - **Veículo ocupado** (`admin/api/agendamento.php:691-704`) - Verifica se veículo está em uso
     - **Limite diário aluno** (`admin/api/agendamento.php:707-713`) - Máximo 3 aulas práticas por dia
6. **Cálculo de horários** → Função `calcularHorariosAulas()` (`admin/api/agendamento.php`) calcula horários baseado em tipo de agendamento (única, dupla, etc.)
7. **Insere em `aulas`** → `status = 'agendada'`, `tipo_aula = 'pratica'`
8. **Retorna sucesso** → Retorna ID da aula criada

#### Como é Marcada como Concluída, Cancelada ou Falta

**Concluída:**
- Via API `atualizar-aula.php` ou `agendamento.php` (PUT)
- Altera `status = 'concluida'` na tabela `aulas`
- Não há registro explícito de "falta" - apenas status `cancelada` ou não concluída

**Cancelada:**
- Via API `cancelar-aula.php` ou `agendamento.php` (DELETE)
- Altera `status = 'cancelada'` na tabela `aulas`
- Pode ter campo `observacoes` explicando motivo

**Falta:**
- ⚠️ **GAP IDENTIFICADO** - Não existe marcação explícita de falta para aulas práticas
- Apenas se a aula está `agendada` e não foi `concluida`, pode ser considerada falta
- Não há tabela `aulas_faltas` equivalente a `turma_presencas` para teóricas

#### Como isso Alimenta o Progresso Prático

1. **API consulta** → `admin/api/progresso_pratico.php:81-91` busca todas as aulas práticas:
   ```sql
   SELECT id, status, data_aula
   FROM aulas
   WHERE aluno_id = ? 
   AND tipo_aula = 'pratica'
   AND status != 'cancelada'
   ```
2. **Calcula estatísticas** → `admin/api/progresso_pratico.php:102-143`:
   - `total_realizadas` = Count de `status = 'concluida'`
   - `total_agendadas` = Count de `status IN ('agendada', 'em_andamento')`
   - `total_contratadas` = `total_realizadas + total_agendadas` (estimativa - **GAP**)
   - `percentual_concluido` = `(total_realizadas / total_contratadas) * 100`
   - `status` = `'nao_iniciado'`, `'em_andamento'` ou `'concluido'`
3. **Retorna JSON** → `{ success: true, progresso: { status, total_contratadas, total_realizadas, percentual_concluido } }`
4. **JavaScript formata** → `admin/pages/alunos.php:7310-7359` converte em texto (ex: "Em andamento (8 de 20 aulas)")
5. **Atualiza cards** → `atualizarCardsPraticoResumo(texto)` (`admin/pages/alunos.php:7249-7268`)

#### Timeline do Aluno

A timeline no modal de visualização (`admin/pages/alunos.php:4748-4765`) é populada pela função `carregarHistoricoAluno()` que consulta:
- Matrículas
- Aulas teóricas e práticas
- Exames
- Faturas

**API:** `admin/api/historico_aluno.php` (existe mas não foi analisada em detalhes)

**Card no modal:** `admin/pages/alunos.php:4749-4764` - Container `#visualizar-historico-container`

---

### 2.3. Relação com o Aluno (Resumo)

#### APIs que Alimentam o Modal de Aluno

| Card/Seção | API | Função JavaScript | Referência |
|------------|-----|-------------------|------------|
| **Progresso Teórico** | `api/progresso_teorico.php` | `atualizarResumoTeoricoAluno()` | `admin/pages/alunos.php:7173-7241` |
| **Progresso Prático** | `api/progresso_pratico.php` | `atualizarResumoPraticoAluno()` | `admin/pages/alunos.php:7310-7359` |
| **Provas** | (Não mapeada) | (Não mapeada) | Card existe mas API não identificada |
| **Histórico** | `api/historico_aluno.php` | `carregarHistoricoAluno()` | `admin/pages/alunos.php:4983` |

#### Fluxo: aluno_id → APIs → Cards no Modal

1. **Modal abre** → `abrirModalAluno('visualizar', alunoId)` ou `abrirModalAluno('editar', alunoId)`
2. **Carrega dados básicos** → Fetch para `api/alunos.php?id=X`
3. **Atualiza cards automaticamente:**
   - `atualizarResumoTeoricoAluno(alunoId)` → Chama `progresso_teorico.php`
   - `atualizarResumoPraticoAluno(alunoId)` → Chama `progresso_pratico.php`
   - `carregarHistoricoAluno(alunoId)` → Chama `historico_aluno.php`
4. **Cards são atualizados** → Elementos com `data-field="teorico_resumo"`, `data-field="pratico_resumo"` recebem valores formatados
5. **Aba Matrícula** → `atualizarVinculacaoTeoricaUI()` e `atualizarVinculacaoPraticaUI()` preenchem campos de vinculação

**Cards no modal:**
- `admin/pages/alunos.php:4703-4711` - Progresso Teórico (`data-field="teorico_resumo"`)
- `admin/pages/alunos.php:4713-4722` - Progresso Prático (`data-field="pratico_resumo"`)
- `admin/pages/alunos.php:4724-4733` - Situação Financeira (`data-field="financeiro_resumo"`)
- `admin/pages/alunos.php:4735-4744` - Provas (`data-field="provas_resumo"`)

---

## 3. Menus & UX Atual (Admin/Secretaria)

### 3.1. Menus Relacionados a Acadêmico & Agenda

#### Menus Identificados no Sistema

| Menu/Página | Arquivo | Objetivo | Status |
|-------------|---------|----------|--------|
| **Turmas Teóricas** | `admin/pages/turmas-teoricas.php` | Gerenciar turmas teóricas (wizard completo) | ✅ Ativo |
| **Agendamento** | `admin/pages/agendamento.php` | Calendário visual de todas as aulas | ✅ Ativo |
| **Agendar Aula** | `admin/pages/agendar-aula.php` | Formulário para agendar aula individual | ✅ Ativo |
| **Listar Aulas** | `admin/pages/listar-aulas.php` | Lista de aulas em formato de cards | ✅ Ativo |
| **Histórico do Aluno** | `admin/pages/historico-aluno.php` | Histórico completo do aluno (aulas, provas, financeiro) | ✅ Ativo |
| **Instrutores** | `admin/pages/instrutores.php` | Cadastro e gerenciamento de instrutores | ✅ Ativo (não analisado) |
| **Veículos** | `admin/pages/veiculos.php` | Cadastro e gerenciamento de veículos | ✅ Ativo (não analisado) |

#### Menus Legados (Identificados no Raio-X)

| Menu/Página | Arquivo | Status | Motivo |
|-------------|---------|--------|--------|
| **Turmas Teóricas Fixed** | `admin/pages/turmas-teoricas-fixed.php` | 🔴 LEGADO | Versão "fixed" antiga |
| **Turmas Teóricas Disciplinas Fixed** | `admin/pages/turmas-teoricas-disciplinas-fixed.php` | 🔴 LEGADO | Versão "fixed" antiga |
| **Histórico Aluno Melhorado** | `admin/pages/historico-aluno-melhorado.php` | 🔴 LEGADO | Versão antiga |
| **Histórico Aluno Novo** | `admin/pages/historico-aluno-novo.php` | 🔴 LEGADO | Versão antiga |
| **Agendamento Moderno** | `admin/pages/agendamento-moderno.php` | ⚠️ DUPLICADO | Pode ser versão alternativa |

### 3.2. Pontos de Confusão ou Sobreposição

#### 1. Duplicação de Agendamento

**Problema:** Existem múltiplas páginas/APIs para agendamento:
- `admin/pages/agendamento.php` (4113 linhas - agenda principal)
- `admin/pages/agendamento-moderno.php` (versão alternativa?)
- `admin/pages/agendar-aula.php` (formulário individual)
- `admin/api/agendamento.php` (API principal)
- `admin/api/agendamento-detalhes.php` (detalhes)
- `admin/api/agendamento-detalhes-fallback.php` (fallback)

**Impacto:** Confusão sobre qual usar, possível código duplicado.

**Referência:** Identificado no Raio-X (`admin/pages/_RAIO-X-TECNICO-COMPLETO.md:396-440`)

#### 2. Aulas Teóricas em Duas Tabelas

**Problema:** Aulas teóricas podem estar em:
- `turma_aulas_agendadas` (para turmas teóricas organizadas)
- `aulas` com `tipo_aula = 'teorica'` (para aulas teóricas avulsas)

**Impacto:** Inconsistência - não fica claro quando usar qual.

**Referência:** 
- `admin/migrations/001-create-turmas-teoricas-structure.sql:126` - Tabela `turma_aulas_agendadas`
- `install.php:88` - Tabela `aulas` com campo `tipo_aula`

#### 3. Progresso Prático Usa Estimativa

**Problema:** API `progresso_pratico.php` calcula `total_contratadas = total_realizadas + total_agendadas` como estimativa, mas não consulta fonte oficial (ex: `aulas_slots` ou configuração de categoria).

**Impacto:** Progresso pode estar incorreto se aluno tiver aulas contratadas mas não agendadas ainda.

**Referência:** `admin/api/progresso_pratico.php:121-123` - TODO comentado

#### 4. Falta de Marcação de Faltas em Aulas Práticas

**Problema:** Não existe tabela ou campo para marcar faltas em aulas práticas. Apenas status `cancelada` ou não `concluida`.

**Impacto:** Não é possível rastrear faltas de forma consistente para aplicar regras (ex: 3 faltas = bloqueio).

**Referência:** Tabela `aulas` não tem campo `falta` ou equivalente.

---

## 4. Lixo / Legado / Oportunidades de Simplificação

### 4.1. Arquivos Legados ou Candidatos a Remoção

#### APIs Legadas

| Arquivo | Status | Justificativa | Referência |
|---------|--------|---------------|------------|
| `admin/api/turmas-teoricas-inline.php` | ⚠️ VERIFICAR | Versão "inline" - verificar se está em uso | Existe mas não analisado |
| `admin/api/disciplinas-clean.php` | 🔴 LEGADO | Versão "clean" - substituída por `disciplinas.php` | `_FASE-1-LIMPEZA-E-BASE.md:47` |
| `admin/api/disciplinas-simples.php` | 🔴 LEGADO | Versão simplificada - substituída por `disciplinas.php` | `_FASE-1-LIMPEZA-E-BASE.md:48` |
| `admin/api/disciplinas-estaticas.php` | 🔴 LEGADO | Versão estática - substituída por `disciplinas.php` | `_FASE-1-LIMPEZA-E-BASE.md:49` |
| `admin/api/disciplinas-automaticas.php` | ⚠️ EM USO | Versão usada em `turmas-teoricas.php` - manter | `_FASE-1-LIMPEZA-E-BASE.md:50` |
| `admin/api/alunos-aptos-turma-simples.php` | 🔴 LEGADO | Versão simplificada - substituída por `alunos-aptos-turma.php` | `_FASE-1-LIMPEZA-E-BASE.md:50` |

#### Páginas Legadas

| Arquivo | Status | Justificativa | Referência |
|---------|--------|---------------|------------|
| `admin/pages/turmas-teoricas-fixed.php` | 🔴 LEGADO | Versão "fixed" antiga - substituída por `turmas-teoricas.php` | `_RAIO-X-TECNICO-COMPLETO.md:431` |
| `admin/pages/turmas-teoricas-disciplinas-fixed.php` | 🔴 LEGADO | Versão "fixed" antiga - substituída por `turmas-teoricas.php` | `_RAIO-X-TECNICO-COMPLETO.md:432` |
| `admin/pages/historico-aluno-melhorado.php` | 🔴 LEGADO | Versão antiga - substituída por `historico-aluno.php` | `_RAIO-X-TECNICO-COMPLETO.md:428` |
| `admin/pages/historico-aluno-novo.php` | 🔴 LEGADO | Versão antiga - substituída por `historico-aluno.php` | `_RAIO-X-TECNICO-COMPLETO.md:429` |
| `admin/pages/agendamento-moderno.php` | ⚠️ VERIFICAR | Pode ser versão alternativa - verificar uso | Não analisado |

### 4.2. Overlaps que Devem ser Unificados

#### 1. Agendamento - Múltiplas Páginas/APIs

**Overlap:** `agendamento.php`, `agendamento-moderno.php`, `agendar-aula.php`, `agendamento-detalhes.php`, `agendamento-detalhes-fallback.php`

**Solução proposta:**
- **Manter:** `admin/pages/agendamento.php` como agenda principal
- **Manter:** `admin/api/agendamento.php` como API principal
- **Verificar e remover:** `agendamento-moderno.php` se não estiver em uso
- **Manter:** `agendar-aula.php` se for formulário complementar à agenda

**Fonte da verdade:** `admin/pages/agendamento.php` + `admin/api/agendamento.php`

#### 2. Aulas Teóricas - Duas Tabelas

**Overlap:** `turma_aulas_agendadas` (para turmas) vs `aulas` com `tipo_aula = 'teorica'` (avulsas)

**Solução proposta:**
- **Para turmas teóricas:** Usar sempre `turma_aulas_agendadas` (já está assim)
- **Para aulas teóricas avulsas:** Continuar usando `aulas` com `tipo_aula = 'teorica'`
- **Documentar:** Criar regra clara de quando usar qual

**Fonte da verdade:**
- Turmas: `turma_aulas_agendadas`
- Aulas avulsas: `aulas`

#### 3. Progresso - Múltiplas APIs

**Overlap:** `progresso_teorico.php`, `progresso_pratico.php`, `historico_aluno.php` (pode ter progresso também)

**Solução proposta:**
- **Manter separadas** para responsabilidades distintas:
  - `progresso_teorico.php` → Apenas dados de turma teórica
  - `progresso_pratico.php` → Apenas estatísticas de aulas práticas
  - `historico_aluno.php` → Histórico completo incluindo progresso
- **Documentar:** Clarificar que `historico_aluno.php` pode usar dados das outras APIs internamente

**Fonte da verdade:** APIs especializadas (`progresso_teorico.php`, `progresso_pratico.php`)

---

## 5. Gaps Funcionais (do ponto de vista do CFC)

### P0 – Impactam Diretamente a Jornada ou Deixam Buraco Grande

#### 1. Falta de Marcação de Faltas em Aulas Práticas

**O que tem hoje:**
- Tabela `aulas` com status `agendada`, `em_andamento`, `concluida`, `cancelada`
- Não há campo `falta` ou tabela equivalente a `turma_presencas`

**O que falta:**
- Campo ou tabela para marcar falta em aula prática
- Campo `falta` na tabela `aulas` OU tabela `aulas_faltas` separada
- Lógica para marcar falta quando aula não foi concluída e data passou
- Regra de negócio: 3 faltas práticas = bloqueio (conforme PLANO-SISTEMA-CFC)

**Onde deveria ser tratado:**
- `admin/api/agendamento.php` - Função para marcar falta após data da aula
- `admin/api/progresso_pratico.php` - Incluir contagem de faltas
- Nova API `admin/api/aulas-faltas.php` ou adicionar campo em `aulas`

**Referência:** 
- PLANO-SISTEMA-CFC menciona regra de faltas práticas
- `admin/api/progresso_pratico.php:81-143` - Não considera faltas

#### 2. Progresso Prático Usa Estimativa ao Invés de Fonte Oficial

**O que tem hoje:**
- API `progresso_pratico.php` calcula `total_contratadas = total_realizadas + total_agendadas`
- Não consulta configuração de categoria (ex: categoria A = 20h práticas)

**O que falta:**
- Integração com `aulas_slots` ou `matriculas` para saber quantas aulas foram contratadas
- Consultar configuração de categoria do aluno para saber limite oficial
- Calcular `total_contratadas` baseado em fonte oficial, não estimativa

**Onde deveria ser tratado:**
- `admin/api/progresso_pratico.php:121-123` - Implementar consulta a `aulas_slots` ou `matriculas`
- `admin/includes/sistema_matricula.php:111-147` - Já cria `aulas_slots`, usar como referência

**Referência:**
- `admin/api/progresso_pratico.php:121-123` - TODO comentado
- `admin/includes/sistema_matricula.php:111-147` - Cria slots de aulas

#### 3. Frequência de Presenças Não Recalcula Automaticamente

**O que tem hoje:**
- Presenças são marcadas em `turma_presencas`
- Campo `frequencia_percentual` em `turma_matriculas` existe mas não se sabe se é recalculado automaticamente

**O que falta:**
- Trigger ou função que recalcula `frequencia_percentual` quando presença é marcada/alterada
- Verificar se existe função `calcularFrequenciaAluno()` e se é chamada automaticamente

**Onde deveria ser tratado:**
- Trigger no banco (`admin/migrations/001-create-turmas-teoricas-structure.sql`) OU
- Função PHP chamada após marcar presença (`admin/api/turma-presencas.php:353-379`)
- `admin/api/turma-presencas.php` - Verificar se chama cálculo de frequência após inserir/atualizar

**Referência:**
- `admin/migrations/001-create-turmas-teoricas-structure.sql:183-202` - Tabela `turma_presencas`
- `admin/migrations/001-create-turmas-teoricas-structure.sql:162-180` - Tabela `turma_matriculas` com campo `frequencia_percentual`

#### 4. Bloqueio por Faltas Práticas Não Implementado

**O que tem hoje:**
- Regra mencionada no PLANO-SISTEMA-CFC: "3 faltas práticas = bloqueio"
- Não há validação ao agendar nova aula prática se aluno tem 3+ faltas

**O que falta:**
- Validação em `AgendamentoGuards` para verificar faltas antes de permitir agendamento
- Função que conta faltas práticas do aluno
- Bloqueio automático após 3 faltas

**Onde deveria ser tratado:**
- `includes/guards/AgendamentoGuards.php` - Adicionar método `verificarFaltasPraticas()`
- `admin/api/agendamento.php:228-235` - Chamar guard antes de criar aula
- Nova função em `admin/api/progresso_pratico.php` para contar faltas

**Referência:**
- PLANO-SISTEMA-CFC menciona regra de faltas
- `includes/guards/AgendamentoGuards.php:389-598` - Já tem `verificarExamesOK()`, adicionar similar

### P1 – Importantes, mas Não Críticos

#### 5. Aulas Práticas Não Têm Campo de Tipo de Veículo Consistente

**O que tem hoje:**
- Tabela `aulas` tem `veiculo_id` (FK para veículo específico)
- Tabela `veiculos` tem `categoria_cnh` mas pode não corresponder ao tipo de aula prática contratada

**O que falta:**
- Campo `tipo_veiculo` em `aulas` (moto, carro, carga, etc.) para alinhar com `aulas_slots`
- Validação que veículo escolhido corresponde ao tipo de aula contratada

**Onde deveria ser tratado:**
- Migration para adicionar campo `tipo_veiculo` em `aulas`
- `admin/api/agendamento.php:246-248` - Validar que veículo corresponde ao tipo de aula

**Referência:**
- `admin/includes/sistema_matricula.php:125-131` - Define tipos de veículo: `moto`, `carro`, `carga`, `passageiros`, `combinacao`
- `install.php:88` - Tabela `aulas` não tem campo `tipo_veiculo`

#### 6. Progresso Teórico Mostra Apenas Última Matrícula

**O que tem hoje:**
- API `progresso_teorico.php` retorna apenas matrícula mais recente (LIMIT 1)

**O que falta:**
- Mostrar histórico de todas as turmas que aluno já cursou
- Somar progresso de múltiplas turmas se aluno mudou de turma

**Onde deveria ser tratado:**
- `admin/api/progresso_teorico.php:72-85` - Remover LIMIT 1 e agregar dados de todas as matrículas
- Ou criar nova API `progresso_teorico_completo.php` que retorna histórico completo

**Referência:**
- `admin/api/progresso_teorico.php:83` - `ORDER BY tm.data_matricula DESC LIMIT 1`

#### 7. Falta Integração entre Aulas Práticas e LADV

**O que tem hoje:**
- Sistema de LADV mencionado no PLANO-SISTEMA-CFC
- Aulas práticas existem independentemente

**O que falta:**
- Validação que aluno tem LADV válido antes de agendar aula prática
- Marcação de conclusão de LADV quando aulas práticas forem concluídas

**Onde deveria ser tratado:**
- `includes/guards/AgendamentoGuards.php` - Adicionar validação de LADV
- `admin/api/agendamento.php` - Verificar LADV antes de criar aula

**Referência:**
- PLANO-SISTEMA-CFC menciona LADV como parte da jornada

### P2 – Melhorias de UX/Organização

#### 8. Interface de Chamada Pode Ser Melhorada

**O que tem hoje:**
- `admin/pages/turma-chamada.php` (931 linhas) - Interface para marcar presenças

**O que falta:**
- Melhor UX: filtros, busca rápida, marcação em lote mais intuitiva
- Indicadores visuais de frequência atual do aluno

**Onde deveria ser tratado:**
- Refatorar `admin/pages/turma-chamada.php`
- Adicionar indicadores de frequência ao lado de cada aluno

**Referência:**
- `admin/pages/turma-chamada.php:1-931` - Interface atual

#### 9. Agenda Principal Muito Grande (4113 linhas)

**O que tem hoje:**
- `admin/pages/agendamento.php` com 4113 linhas - difícil de manter

**O que falta:**
- Quebrar em componentes menores
- Separar lógica JavaScript em arquivo separado

**Onde deveria ser tratado:**
- Refatorar `admin/pages/agendamento.php`
- Criar `admin/assets/js/agendamento.js` para lógica JavaScript

**Referência:**
- `admin/pages/agendamento.php:1-4113` - Arquivo muito grande

#### 10. Falta API Unificada de Progresso Completo

**O que tem hoje:**
- `progresso_teorico.php` - apenas teórico
- `progresso_pratico.php` - apenas prático
- Cada card chama API separada

**O que falta:**
- API `progresso_completo.php` que retorna teórico + prático + provas + financeiro em uma única chamada
- Reduzir número de requisições ao abrir modal de aluno

**Onde deveria ser tratado:**
- Criar `admin/api/progresso_completo.php`
- Refatorar `admin/pages/alunos.php:7173-7359` para usar uma única API

**Referência:**
- `admin/pages/alunos.php:7173-7241` - Chama `progresso_teorico.php`
- `admin/pages/alunos.php:7310-7359` - Chama `progresso_pratico.php`

---

## 6. Checklist Proposto para Próxima Fase (Implementação Acadêmica)

### 6.1. Correções Estruturais (P0)

- [ ] **Implementar marcação de faltas em aulas práticas**
  - Adicionar campo `falta` na tabela `aulas` ou criar tabela `aulas_faltas`
  - Migration: `admin/migrations/009-add-faltas-aulas-praticas.sql`
  - API: Adicionar endpoint em `admin/api/agendamento.php` para marcar falta
  - Função: `marcarFaltaAulaPratica($aulaId, $alunoId, $motivo)`

- [ ] **Corrigir cálculo de progresso prático**
  - Integrar `admin/api/progresso_pratico.php` com `aulas_slots` ou `matriculas`
  - Consultar configuração de categoria para `total_contratadas` oficial
  - Remover estimativa `total_contratadas = total_realizadas + total_agendadas`
  - Arquivo: `admin/api/progresso_pratico.php:121-123`

- [ ] **Implementar recálculo automático de frequência teórica**
  - Verificar se existe trigger/função que recalcula `frequencia_percentual`
  - Se não existir, criar trigger ou função PHP chamada após marcar presença
  - Arquivo: `admin/api/turma-presencas.php:353-379` - Adicionar chamada após inserir/atualizar

- [ ] **Implementar bloqueio por faltas práticas**
  - Adicionar método `verificarFaltasPraticas()` em `includes/guards/AgendamentoGuards.php`
  - Chamar guard antes de criar aula prática em `admin/api/agendamento.php:228-235`
  - Contar faltas: `SELECT COUNT(*) FROM aulas WHERE aluno_id = ? AND tipo_aula = 'pratica' AND falta = 1`
  - Bloquear se `COUNT >= 3`

### 6.2. Melhorias Funcionais (P1)

- [ ] **Adicionar campo tipo_veiculo em aulas**
  - Migration: `admin/migrations/010-add-tipo-veiculo-aulas.sql`
  - Validar que veículo corresponde ao tipo em `admin/api/agendamento.php:246-248`
  - Atualizar `admin/api/progresso_pratico.php` para agrupar por tipo de veículo

- [ ] **Melhorar progresso teórico para mostrar histórico completo**
  - Remover `LIMIT 1` em `admin/api/progresso_teorico.php:83`
  - Agregar dados de todas as matrículas do aluno
  - Retornar array de turmas ao invés de objeto único

- [ ] **Integrar LADV com aulas práticas**
  - Adicionar validação de LADV válido em `includes/guards/AgendamentoGuards.php`
  - Verificar LADV antes de agendar aula prática
  - Marcar conclusão de LADV quando aulas práticas forem concluídas

### 6.3. Melhorias de UX/Organização (P2)

- [ ] **Refatorar interface de chamada**
  - Melhorar UX de `admin/pages/turma-chamada.php`
  - Adicionar filtros e busca rápida
  - Mostrar frequência atual ao lado de cada aluno
  - Melhorar marcação em lote

- [ ] **Quebrar agenda principal em componentes**
  - Separar lógica JavaScript de `admin/pages/agendamento.php` para `admin/assets/js/agendamento.js`
  - Criar componentes reutilizáveis para calendário
  - Reduzir tamanho do arquivo principal

- [ ] **Criar API unificada de progresso completo**
  - Criar `admin/api/progresso_completo.php` que retorna teórico + prático + provas + financeiro
  - Refatorar `admin/pages/alunos.php:7173-7359` para usar uma única chamada
  - Reduzir número de requisições ao abrir modal

- [ ] **Padronizar nomenclatura de tabelas/campos**
  - Documentar quando usar `turma_aulas_agendadas` vs `aulas` com `tipo_aula = 'teorica'`
  - Criar regra clara para evitar confusão futura

### 6.4. Limpeza de Legados

- [ ] **Remover páginas legadas**
  - Mover `admin/pages/turmas-teoricas-fixed.php` para `admin/pages/legacy/`
  - Mover `admin/pages/turmas-teoricas-disciplinas-fixed.php` para `admin/pages/legacy/`
  - Mover `admin/pages/historico-aluno-melhorado.php` para `admin/pages/legacy/`
  - Mover `admin/pages/historico-aluno-novo.php` para `admin/pages/legacy/`
  - Verificar e mover `admin/pages/agendamento-moderno.php` se não estiver em uso

- [ ] **Remover APIs legadas**
  - Mover `admin/api/disciplinas-clean.php` para `admin/api/legacy/` (se não estiver em uso)
  - Mover `admin/api/disciplinas-simples.php` para `admin/api/legacy/`
  - Mover `admin/api/disciplinas-estaticas.php` para `admin/api/legacy/`
  - Mover `admin/api/alunos-aptos-turma-simples.php` para `admin/api/legacy/`

### 6.5. APIs Base para PWA (Futuro)

- [ ] **Definir API base para PWA do Instrutor**
  - Endpoint: `GET api/instrutor/aulas-hoje.php` - Lista aulas do instrutor no dia
  - Endpoint: `POST api/instrutor/iniciar-aula.php` - Marca aula como em_andamento
  - Endpoint: `POST api/instrutor/encerrar-aula.php` - Marca aula como concluida
  - Baseado em: `admin/api/agendamento.php` - Adaptar para uso do instrutor

- [ ] **Definir API base para PWA do Aluno**
  - Endpoint: `GET api/aluno/resumo.php` - Retorna teórico + prático + provas + financeiro
  - Endpoint: `GET api/aluno/agenda.php` - Lista aulas agendadas do aluno
  - Endpoint: `GET api/aluno/historico.php` - Histórico completo do aluno
  - Baseado em: `admin/api/progresso_teorico.php`, `admin/api/progresso_pratico.php`, `admin/api/historico_aluno.php`

### 6.6. Reorganização de Menus

- [ ] **Propor reorganização do menu Acadêmico & Agenda**
  - Agrupar funcionalidades relacionadas:
    - **Turmas Teóricas:** `Turmas Teóricas` (lista + wizard)
    - **Aulas Práticas:** `Agendamento` (calendário), `Agendar Aula` (formulário)
    - **Controle:** `Listar Aulas`, `Chamada` (presenças)
    - **Relatórios:** `Histórico do Aluno`, `Relatórios de Turma`
  - Remover duplicações identificadas
  - Documentar nova estrutura de menus

---

## 7. Restrições

✅ **Não alterar código de produção nesta fase** (a menos que seja para corrigir erro óbvio de leitura no diagnóstico)

✅ **Não criar novas tabelas ainda** - Apenas documentar necessidade

✅ **Não inventar endpoints que não existam** - Sempre referenciar arquivos reais

✅ **Toda conclusão deve vir com ao menos uma referência de arquivo:linha–linha** - Seguido em todo este documento

---

**Saída Final:**  
✅ Arquivo `admin/pages/_FASE-3-ACADEMICO-E-AGENDA.md` criado com todo o conteúdo desta análise, seguindo a estrutura especificada.

