# 🔍 RAIO-X COMPLETO: PRESENÇA TEÓRICA
## Sistema CFC Bom Conselho - Mapeamento Técnico Completo

**Data:** 2025-01-28  
**Objetivo:** Mapear toda a estrutura e fluxo de presença teórica antes de implementar melhorias

---

## 📋 ÍNDICE

1. [Estrutura do Banco de Dados](#1-estrutura-do-banco-de-dados)
2. [Painel Admin/Secretaria](#2-painel-adminsecretaria)
3. [Painel Instrutor](#3-painel-instrutor)
4. [Área do Aluno](#4-área-do-aluno)
5. [Validação para Prova Teórica](#5-validação-para-prova-teórica)
6. [Fluxo Completo de Ponta a Ponta](#6-fluxo-completo-de-ponta-a-ponta)
7. [Problemas e Gaps Identificados](#7-problemas-e-gaps-identificados)
8. [Arquivos Envolvidos](#8-arquivos-envolvidos)

---

## 1. ESTRUTURA DO BANCO DE DADOS

### 1.1. Tabelas Principais

#### 📊 **Tabela: `turmas_teoricas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:83-123`

**Campos principais:**
- `id` (INT, PK)
- `nome` (VARCHAR(200))
- `sala_id` (INT, FK → `salas.id`)
- `curso_tipo` (ENUM: 'reciclagem_infrator', 'formacao_45h', 'atualizacao', 'formacao_acc_20h')
- `modalidade` (ENUM: 'online', 'presencial')
- `data_inicio`, `data_fim` (DATE)
- `status` (ENUM: 'criando', 'agendando', 'completa', 'ativa', 'concluida', 'cancelada')
- `carga_horaria_total`, `carga_horaria_agendada`, `carga_horaria_realizada` (INT)
- `max_alunos`, `alunos_matriculados` (INT)
- `cfc_id` (INT, FK → `cfcs.id`)
- `criado_por` (INT, FK → `usuarios.id`)
- `criado_em`, `atualizado_em` (TIMESTAMP)

**Relacionamentos:**
- Uma turma tem várias aulas agendadas (`turma_aulas_agendadas`)
- Uma turma tem várias matrículas (`turma_matriculas`)
- Uma turma tem várias presenças (`turma_presencas`)

---

#### 📅 **Tabela: `turma_aulas_agendadas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:126-159`

**Campos principais:**
- `id` (INT, PK)
- `turma_id` (INT, FK → `turmas_teoricas.id`)
- `disciplina` (ENUM: 'legislacao_transito', 'primeiros_socorros', 'direcao_defensiva', 'meio_ambiente_cidadania', 'mecanica_basica')
- `nome_aula` (VARCHAR(200))
- `instrutor_id` (INT, FK → `instrutores.id`)
- `sala_id` (INT, FK → `salas.id`)
- `data_aula` (DATE)
- `hora_inicio`, `hora_fim` (TIME)
- `duracao_minutos` (INT, DEFAULT 50)
- `ordem_disciplina`, `ordem_global` (INT)
- `status` (ENUM: 'agendada', 'realizada', 'cancelada')
- `observacoes` (TEXT)
- `criado_em`, `atualizado_em` (TIMESTAMP)

**Relacionamentos:**
- Uma aula agendada pertence a uma turma (`turmas_teoricas`)
- Uma aula agendada tem várias presenças (`turma_presencas`)

**⚠️ DISCREPÂNCIA IDENTIFICADA:**
- A API `turma-presencas.php` referencia `turma_aulas` (linha 251), mas a tabela real é `turma_aulas_agendadas`
- A API `turma-frequencia.php` também referencia `turma_aulas` (linhas 123, 219), mas deveria ser `turma_aulas_agendadas`
- **Impacto:** Queries podem falhar ou retornar dados incorretos

---

#### 👥 **Tabela: `turma_matriculas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:162-180`

**Campos principais:**
- `id` (INT, PK)
- `turma_id` (INT, FK → `turmas_teoricas.id`)
- `aluno_id` (INT, FK → `alunos.id`)
- `data_matricula` (TIMESTAMP)
- `status` (ENUM: 'matriculado', 'cursando', 'concluido', 'evadido', 'transferido')
- `exames_validados_em` (TIMESTAMP, NULL)
- **`frequencia_percentual`** (DECIMAL(5,2), DEFAULT 0.00) ⭐ **CAMPO CRÍTICO**
- `observacoes` (TEXT)
- `atualizado_em` (TIMESTAMP)

**Relacionamentos:**
- Uma matrícula liga um aluno a uma turma
- UNIQUE KEY: `(turma_id, aluno_id)` - um aluno só pode estar matriculado uma vez por turma

**⚠️ GAP IDENTIFICADO:**
- O campo `frequencia_percentual` existe, mas **não há evidência de atualização automática** quando presenças são marcadas
- Não foi encontrada função `calcularFrequenciaAluno()` que atualiza este campo via `UPDATE`

---

#### ✅ **Tabela: `turma_presencas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:183-202`

**Campos principais:**
- `id` (INT, PK)
- `turma_id` (INT, FK → `turmas_teoricas.id`)
- **`aula_id`** (INT, FK → `turma_aulas_agendadas.id`) ⚠️ **NOME DO CAMPO**
- `aluno_id` (INT, FK → `alunos.id`)
- `presente` (BOOLEAN, DEFAULT FALSE)
- `justificativa` (TEXT, NULL) ⚠️ **NOME DO CAMPO**
- `registrado_por` (INT, FK → `usuarios.id`) ⭐ **AUDITORIA**
- `registrado_em` (TIMESTAMP)

**Relacionamentos:**
- Uma presença liga um aluno a uma aula agendada
- UNIQUE KEY: `(aula_id, aluno_id)` - um aluno só pode ter uma presença por aula

**⚠️ DISCREPÂNCIAS IDENTIFICADAS:**
1. **Nome do campo:** A tabela usa `aula_id`, mas a API `turma-presencas.php` usa `turma_aula_id` nas queries (linhas 215, 242, 267)
2. **Nome do campo:** A tabela usa `justificativa`, mas a API usa `observacao` (linhas 218, 245, 270)
3. **Impacto:** Inserções/atualizações podem falhar ou usar campos errados

---

### 1.2. Regras de Negócio Existentes

#### ✅ **Regra 1: Validação de Exames para Matrícula**
**Localização:** `admin/includes/TurmaTeoricaManager.php:556-601`

**Descrição:**
- Antes de matricular aluno em turma teórica, verifica se exames médico e psicotécnico estão aprovados
- Usa `AgendamentoGuards::verificarExamesOK($alunoId)`
- Se exames não estiverem OK, retorna erro: "Exames médico e psicotécnico não concluídos"

**Status:** ✅ **IMPLEMENTADO**

---

#### ❌ **Regra 2: Validação de Presença para Prova Teórica**
**Localização:** `admin/includes/ExamesRulesService.php:39-135`

**Descrição:**
- A função `podeAgendarProvaTeorica()` **NÃO verifica presença teórica**
- Apenas verifica exames médico e psicotécnico
- **GAP:** Não há validação de frequência mínima ou carga horária teórica cumprida

**Status:** ❌ **NÃO IMPLEMENTADO**

---

#### ⚠️ **Regra 3: Cálculo de Frequência Percentual**
**Localização:** `admin/api/turma-frequencia.php:92-197`

**Descrição:**
- Função `calcularFrequenciaAluno()` calcula percentual de frequência
- Fórmula: `(aulas_presentes / total_aulas_registradas) * 100`
- **PROBLEMA:** O cálculo é feito em tempo real, mas **não atualiza** `turma_matriculas.frequencia_percentual`
- O campo `frequencia_percentual` fica desatualizado até ser recalculado manualmente

**Status:** ⚠️ **CALCULADO MAS NÃO PERSISTIDO**

---

## 2. PAINEL ADMIN/SECRETARIA

### 2.1. Listagens e Telas de Detalhe

#### 📋 **Lista de Turmas Teóricas**
**Arquivo:** `admin/pages/turmas-teoricas-lista.php`
**Rota:** `index.php?page=turmas-teoricas`

**Funcionalidades:**
- Lista todas as turmas teóricas
- Exibe: nome, sala, datas, número de alunos, status
- **NÃO exibe presença/frequência** na listagem

---

#### 📊 **Detalhes da Turma**
**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`
**Rota:** `index.php?page=turmas-teoricas&acao=detalhes&turma_id={id}`

**Funcionalidades:**
- Exibe informações completas da turma
- **Aba "Alunos Matriculados":**
  - Lista alunos com: nome, CPF, categoria, telefone, email
  - **Exibe `frequencia_percentual`** (linha 277)
  - **PROBLEMA:** Este valor pode estar desatualizado se não foi recalculado

**Query de alunos matriculados:**
```sql
SELECT 
    tm.id AS matricula_id,
    tm.aluno_id,
    tm.status,
    tm.frequencia_percentual,  -- ⚠️ Pode estar desatualizado
    a.nome, a.cpf, a.categoria_cnh,
    ...
FROM turma_matriculas tm
JOIN alunos a ON tm.aluno_id = a.id
WHERE tm.turma_id = ?
```

---

### 2.2. Edição / Controle de Presença via Admin/Secretaria

#### ✅ **Interface de Chamada**
**Arquivo:** `admin/pages/turma-chamada.php`
**Rota:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`

**Funcionalidades:**
- Permite marcar presença/falta para todos os alunos de uma aula
- Exibe lista de alunos matriculados
- Botões "Presente" e "Ausente" para cada aluno
- Exibe frequência percentual de cada aluno (se disponível)

**Permissões:**
- `$canEdit = ($userType === 'admin' || $userType === 'instrutor')`
- Instrutor só pode editar se for o instrutor da turma (`turma.instrutor_id == userId`)

**Fluxo de marcação:**
1. Admin/Secretaria acessa `?page=turma-chamada&turma_id=X&aula_id=Y`
2. Seleciona aluno e clica em "Presente" ou "Ausente"
3. JavaScript chama `marcarPresenca(alunoId, presente)` (linha 647)
4. Função faz POST para `/admin/api/turma-presencas.php`
5. API valida e insere/atualiza em `turma_presencas`

**⚠️ PROBLEMA IDENTIFICADO:**
- A página `turma-chamada.php` busca dados de `turmas` (linha 48), mas deveria buscar de `turmas_teoricas`
- Query usa `FROM turmas t` mas a tabela correta é `turmas_teoricas`
- **Impacto:** Página pode não funcionar corretamente

---

### 2.3. Logs ou Marcação de Quem Alterou

#### ✅ **Auditoria Implementada**
**Arquivo:** `admin/api/turma-presencas.php:601-616`

**Campos de auditoria:**
- `registrado_por` (INT, FK → `usuarios.id`) - armazenado em `turma_presencas`
- `registrado_em` (TIMESTAMP) - armazenado em `turma_presencas`
- Log adicional em `logs` (tabela genérica) via `logAuditoria()`

**Status:** ✅ **IMPLEMENTADO**

---

## 3. PAINEL INSTRUTOR

### 3.1. Tela(s) do Instrutor Relacionadas a Aulas Teóricas

#### 📱 **Dashboard do Instrutor**
**Arquivo:** `instrutor/dashboard.php`
**Rota:** `instrutor/dashboard.php`

**Funcionalidades:**
- Exibe aulas práticas do dia
- **Busca turmas teóricas do dia** (linha 61), mas query está incompleta no código encontrado
- **NÃO exibe interface de chamada** diretamente no dashboard

---

#### ✅ **Interface de Chamada (Compartilhada)**
**Arquivo:** `admin/pages/turma-chamada.php`
**Rota:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`

**Permissões para Instrutor:**
- Instrutor pode acessar se `turma.instrutor_id == userId` (linha 73)
- Se não for o instrutor da turma, `$canEdit = false` (apenas visualização)

**Status:** ✅ **IMPLEMENTADO** (compartilhado com Admin/Secretaria)

---

### 3.2. Fluxo Atual de Marcação de Presença pelo Instrutor

#### 📝 **Passo a Passo:**
1. Instrutor acessa `?page=turma-chamada&turma_id=X&aula_id=Y`
2. Visualiza lista de alunos matriculados
3. Clica em "Presente" ou "Ausente" para cada aluno
4. JavaScript chama `marcarPresenca(alunoId, presente)` (linha 647)
5. Função verifica se `canEdit === true`
6. Se presença já existe, chama `atualizarPresenca(presencaId, presente)` (linha 699)
7. Se presença não existe, chama `criarPresenca(alunoId, presente)` (linha 667)
8. POST/PUT para `/admin/api/turma-presencas.php`
9. API valida e grava em `turma_presencas`

**Endpoint:** `POST /admin/api/turma-presencas.php` ou `PUT /admin/api/turma-presencas.php?id={presenca_id}`

**Payload (POST):**
```json
{
  "turma_id": 1,
  "turma_aula_id": 5,  // ⚠️ Nome do campo pode estar errado (deveria ser aula_id)
  "aluno_id": 167,
  "presente": true
}
```

**Payload (PUT):**
```json
{
  "presente": false
}
```

---

### 3.3. Regras e Limitações Atuais

#### ✅ **Regra 1: Instrutor só edita suas próprias turmas**
**Localização:** `admin/pages/turma-chamada.php:72-75`

**Descrição:**
- Se `userType === 'instrutor'` e `turma.instrutor_id != userId`, então `$canEdit = false`
- Instrutor não pode editar presenças de turmas que não são suas

**Status:** ✅ **IMPLEMENTADO**

---

#### ❌ **Regra 2: Bloqueio de edição de aulas passadas/futuras**
**Status:** ❌ **NÃO IMPLEMENTADO**

**GAP:** Não há validação que impede editar presenças de:
- Aulas com `data_aula < hoje` (passadas)
- Aulas com `data_aula > hoje` (futuras)
- Aulas com `status = 'cancelada'`

---

#### ❌ **Regra 3: Fechamento de turma**
**Status:** ❌ **NÃO IMPLEMENTADO**

**GAP:** Não há validação que impede editar presenças se:
- `turma.status = 'concluida'`
- `turma.status = 'cancelada'`

---

## 4. ÁREA DO ALUNO

### 4.1. Onde o Aluno Vê a Presença Teórica Hoje

#### 📊 **Histórico do Aluno**
**Arquivo:** `admin/pages/historico-aluno.php`
**Rota:** `index.php?page=historico-aluno&id={aluno_id}`

**Funcionalidades:**
- Exibe progresso teórico e prático
- **NÃO exibe presenças teóricas individuais**
- **NÃO exibe frequência percentual de turmas teóricas**
- Exibe apenas estatísticas gerais (aulas teóricas concluídas, aulas práticas concluídas)

**Query de progresso teórico:**
- Busca `aulas` onde `tipo_aula = 'teorica'`
- **PROBLEMA:** Não busca dados de `turma_presencas` ou `turma_matriculas`

**Status:** ❌ **PRESENÇA TEÓRICA NÃO É EXIBIDA PARA O ALUNO**

---

### 4.2. Dados Exibidos e Origem

#### 📈 **Estatísticas Exibidas:**
- Total de aulas teóricas concluídas (contagem de `aulas` com `status = 'concluida'`)
- Total de aulas práticas concluídas
- Progresso por categoria (A, B, AB, etc.)

**Origem dos dados:**
- Tabela `aulas` (aulas práticas)
- **NÃO usa `turma_presencas` ou `turma_matriculas`**

**Status:** ⚠️ **DADOS INCOMPLETOS** - Aluno não vê presenças teóricas

---

## 5. VALIDAÇÃO PARA PROVA TEÓRICA

### 5.1. Ponto em que é Feita a Validação

#### 🔍 **Service de Validação**
**Arquivo:** `admin/includes/ExamesRulesService.php`
**Método:** `podeAgendarProvaTeorica(int $alunoId): array`

**Localização no código:**
- Chamado por `includes/guards/AgendamentoGuards.php:34-44`
- Usado em validações de agendamento de prova teórica

---

### 5.2. Regra Aplicada Atualmente

#### ✅ **Validação de Exames:**
```php
// Verifica se exames médico e psicotécnico estão aprovados
$medicoOK = $medico && ($medico['resultado'] === 'apto' || $medico['resultado'] === 'aprovado');
$psicotecnicoOK = $psicotecnico && ($psicotecnico['resultado'] === 'apto' || $psicotecnico['resultado'] === 'aprovado');

if (!$medicoOK || !$psicotecnicoOK) {
    return ['ok' => false, 'codigo' => 'EXAMES_INICIAIS_PENDENTES', ...];
}
```

**Status:** ✅ **IMPLEMENTADO**

---

#### ❌ **Validação de Presença:**
**Status:** ❌ **NÃO IMPLEMENTADO**

**GAP CRÍTICO:**
- Não verifica se aluno tem frequência mínima (ex: 75%)
- Não verifica se aluno completou carga horária teórica obrigatória
- Não verifica se aluno está matriculado em turma teórica
- Não verifica se aluno concluiu turma teórica

**Impacto:** Aluno pode agendar prova teórica sem ter frequentado aulas teóricas

---

### 5.3. Gaps / Ausência de Validação

#### ❌ **Validação de Presença Teórica:**
**Status:** ❌ **AUSENTE**

**O que deveria existir:**
1. Verificar se aluno está matriculado em turma teórica (`turma_matriculas`)
2. Verificar se aluno tem frequência mínima (ex: `frequencia_percentual >= 75`)
3. Verificar se aluno completou carga horária teórica obrigatória
4. Bloquear agendamento se alguma condição não for atendida

**O que existe hoje:**
- Apenas validação de exames médico e psicotécnico

---

## 6. FLUXO COMPLETO DE PONTA A PONTA

### 6.1. Matrícula do Aluno na Turma Teórica

**Passo 1:** Admin/Secretaria acessa `index.php?page=turmas-teoricas&acao=detalhes&turma_id={id}`

**Passo 2:** Clica em "Matricular Alunos na Turma"

**Passo 3:** Sistema valida:
- ✅ Exames médico e psicotécnico aprovados (`TurmaTeoricaManager::matricularAluno()`)
- ✅ Turma está ativa ou completa
- ✅ Há vagas disponíveis
- ✅ Aluno não está já matriculado

**Passo 4:** Se validações OK, insere em `turma_matriculas`:
```sql
INSERT INTO turma_matriculas (turma_id, aluno_id, status, frequencia_percentual)
VALUES (?, ?, 'matriculado', 0.00)
```

**Arquivos envolvidos:**
- `admin/pages/turmas-teoricas-detalhes-inline.php`
- `admin/api/matricular-aluno-turma.php`
- `admin/includes/TurmaTeoricaManager.php`

---

### 6.2. Criação/Gerenciamento de Aulas

**Passo 1:** Admin/Secretaria acessa detalhes da turma

**Passo 2:** Agenda aulas teóricas (disciplina, data, hora, instrutor, sala)

**Passo 3:** Sistema insere em `turma_aulas_agendadas`:
```sql
INSERT INTO turma_aulas_agendadas (turma_id, disciplina, nome_aula, instrutor_id, sala_id, data_aula, hora_inicio, hora_fim, status)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'agendada')
```

**Arquivos envolvidos:**
- `admin/pages/turmas-teoricas-detalhes-inline.php`
- `admin/api/turmas-teoricas.php` (handleAgendarAula)
- `admin/includes/TurmaTeoricaManager.php`

---

### 6.3. Marcação de Presença pelo Instrutor/Admin

**Passo 1:** Instrutor/Admin acessa `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`

**Passo 2:** Visualiza lista de alunos matriculados

**Passo 3:** Clica em "Presente" ou "Ausente" para cada aluno

**Passo 4:** JavaScript faz POST/PUT para `/admin/api/turma-presencas.php`

**Passo 5:** API valida e insere/atualiza em `turma_presencas`:
```sql
INSERT INTO turma_presencas (turma_id, aula_id, aluno_id, presente, registrado_por)
VALUES (?, ?, ?, ?, ?)
-- ou
UPDATE turma_presencas SET presente = ? WHERE id = ?
```

**Passo 6:** ⚠️ **PROBLEMA:** `frequencia_percentual` em `turma_matriculas` **NÃO é atualizado automaticamente**

**Arquivos envolvidos:**
- `admin/pages/turma-chamada.php`
- `admin/api/turma-presencas.php`

---

### 6.4. Visualização por Admin/Secretaria

**Passo 1:** Admin/Secretaria acessa detalhes da turma

**Passo 2:** Visualiza lista de alunos matriculados com `frequencia_percentual`

**Passo 3:** ⚠️ **PROBLEMA:** Valor pode estar desatualizado se não foi recalculado manualmente

**Arquivos envolvidos:**
- `admin/pages/turmas-teoricas-detalhes-inline.php`

---

### 6.5. Visualização pelo Aluno

**Passo 1:** Aluno acessa `index.php?page=historico-aluno&id={aluno_id}`

**Passo 2:** ❌ **PROBLEMA:** Aluno **NÃO vê** presenças teóricas individuais
- Não vê lista de aulas teóricas frequentadas
- Não vê frequência percentual
- Não vê presenças/faltas por aula

**Arquivos envolvidos:**
- `admin/pages/historico-aluno.php`

---

### 6.6. Agendamento de Prova Teórica

**Passo 1:** Sistema chama `ExamesRulesService::podeAgendarProvaTeorica($alunoId)`

**Passo 2:** Valida apenas exames médico e psicotécnico

**Passo 3:** ❌ **PROBLEMA:** **NÃO valida presença teórica**
- Não verifica se aluno está matriculado em turma teórica
- Não verifica se aluno tem frequência mínima
- Não verifica se aluno completou carga horária teórica

**Passo 4:** Se exames OK, permite agendamento (mesmo sem presença teórica)

**Arquivos envolvidos:**
- `admin/includes/ExamesRulesService.php`
- `includes/guards/AgendamentoGuards.php`

---

## 7. PROBLEMAS E GAPS IDENTIFICADOS

### 🔴 **CRÍTICOS**

1. **Discrepância de nomes de tabelas/campos:**
   - API `turma-presencas.php` referencia `turma_aulas`, mas tabela real é `turma_aulas_agendadas`
   - API usa `turma_aula_id` e `observacao`, mas tabela usa `aula_id` e `justificativa`
   - **Impacto:** Queries podem falhar

2. **Frequência percentual não é atualizada automaticamente:**
   - Campo `turma_matriculas.frequencia_percentual` existe mas não é atualizado quando presenças são marcadas
   - Função `calcularFrequenciaAluno()` calcula em tempo real, mas não persiste
   - **Impacto:** Dados desatualizados na interface

3. **Validação de presença para prova teórica ausente:**
   - `ExamesRulesService::podeAgendarProvaTeorica()` não verifica presença teórica
   - Aluno pode agendar prova teórica sem frequentar aulas teóricas
   - **Impacto:** Violação de regra de negócio

4. **Aluno não vê presenças teóricas:**
   - Histórico do aluno não exibe presenças/faltas de aulas teóricas
   - Aluno não sabe sua frequência percentual
   - **Impacto:** Falta de transparência

---

### 🟡 **IMPORTANTES**

5. **Página `turma-chamada.php` usa tabela errada:**
   - Busca de `turmas` em vez de `turmas_teoricas`
   - **Impacto:** Página pode não funcionar corretamente

6. **Falta de validações de edição:**
   - Não bloqueia edição de presenças de aulas passadas/futuras
   - Não bloqueia edição se turma está concluída/cancelada
   - **Impacto:** Possibilidade de inconsistências

7. **Falta de regra de frequência mínima:**
   - Não há validação de percentual mínimo (ex: 75%) para aprovação
   - Campo `frequencia_minima` existe em `turmas`, mas não é usado em validações
   - **Impacto:** Regra de negócio não aplicada

---

### 🟢 **MELHORIAS**

8. **Interface de chamada poderia ser mais intuitiva:**
   - Falta botão "Marcar todos presentes" / "Marcar todos ausentes"
   - Falta exportação de lista de presença

9. **Falta de relatórios:**
   - Não há relatório de frequência por turma
   - Não há relatório de alunos com frequência abaixo do mínimo

---

## 8. ARQUIVOS ENVOLVIDOS

### 8.1. Backend (PHP)

#### **APIs:**
- `admin/api/turma-presencas.php` - CRUD de presenças
- `admin/api/turma-frequencia.php` - Cálculo de frequência (não persiste)
- `admin/api/turmas-teoricas.php` - Gerenciamento de turmas e aulas
- `admin/api/matricular-aluno-turma.php` - Matrícula de alunos
- `admin/api/progresso_teorico.php` - Progresso teórico (usa `frequencia_percentual`)

#### **Services/Managers:**
- `admin/includes/TurmaTeoricaManager.php` - Gerenciamento de turmas
- `admin/includes/ExamesRulesService.php` - Validações de exames (não valida presença)
- `includes/guards/AgendamentoGuards.php` - Guards de agendamento

#### **Páginas:**
- `admin/pages/turmas-teoricas-lista.php` - Lista de turmas
- `admin/pages/turmas-teoricas-detalhes-inline.php` - Detalhes da turma
- `admin/pages/turma-chamada.php` - Interface de chamada (Admin/Instrutor)
- `admin/pages/historico-aluno.php` - Histórico do aluno (não exibe presenças teóricas)

---

### 8.2. Frontend (JavaScript)

#### **Arquivos JS:**
- `admin/pages/turma-chamada.php` (JavaScript inline) - Funções de marcação de presença
  - `marcarPresenca(alunoId, presente)`
  - `criarPresenca(alunoId, presente)`
  - `atualizarPresenca(presencaId, presente)`

---

### 8.3. Banco de Dados

#### **Tabelas:**
- `turmas_teoricas` - Turmas teóricas
- `turma_aulas_agendadas` - Aulas agendadas
- `turma_matriculas` - Matrículas de alunos
- `turma_presencas` - Presenças dos alunos
- `usuarios` - Usuários (para auditoria)
- `alunos` - Alunos
- `instrutores` - Instrutores
- `salas` - Salas

#### **Migração:**
- `admin/migrations/001-create-turmas-teoricas-structure.sql` - Estrutura inicial

---

## 📝 RESUMO EXECUTIVO

### ✅ **O que está funcionando:**
1. Estrutura de banco de dados existe e está bem definida
2. Interface de chamada existe e permite marcar presença (Admin/Instrutor)
3. API de presenças existe e funciona (com algumas discrepâncias)
4. Validação de exames para matrícula funciona
5. Auditoria de quem marcou presença está implementada

### ❌ **O que está faltando:**
1. **Validação de presença para prova teórica** (CRÍTICO)
2. **Atualização automática de `frequencia_percentual`** (CRÍTICO)
3. **Exibição de presenças teóricas para o aluno** (IMPORTANTE)
4. **Correção de discrepâncias de nomes de tabelas/campos** (CRÍTICO)
5. **Validações de edição (aulas passadas, turmas concluídas)** (IMPORTANTE)

### ⚠️ **O que precisa ser corrigido:**
1. Discrepâncias entre nomes de campos na API e no banco
2. Query errada em `turma-chamada.php` (busca `turmas` em vez de `turmas_teoricas`)
3. Cálculo de frequência não persiste no banco

---

## 🎯 PRÓXIMOS PASSOS SUGERIDOS

1. **Corrigir discrepâncias de nomes** (tabelas e campos)
2. **Implementar atualização automática de `frequencia_percentual`**
3. **Adicionar validação de presença em `ExamesRulesService::podeAgendarProvaTeorica()`**
4. **Criar interface para aluno ver presenças teóricas**
5. **Adicionar validações de edição (aulas passadas, turmas concluídas)**
6. **Implementar regra de frequência mínima**

---

**Fim do Raio-X**

