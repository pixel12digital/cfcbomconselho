# 🔍 RAIO-X COMPLETO: PRESENÇA TEÓRICA
## Sistema CFC Bom Conselho - Mapeamento Técnico Completo

**Data:** 24/11/2025  
**Objetivo:** Mapear toda a estrutura e fluxo de presença teórica para identificar o que existe e o que falta

---

## 📋 ÍNDICE

1. [Estrutura do Banco de Dados](#1-estrutura-do-banco-de-dados)
2. [APIs e Endpoints](#2-apis-e-endpoints)
3. [Páginas Frontend](#3-páginas-frontend)
4. [Regras de Negócio Implementadas](#4-regras-de-negócio-implementadas)
5. [Arquivos Envolvidos](#5-arquivos-envolvidos)
6. [Resumo Executivo](#6-resumo-executivo)

---

## 1. ESTRUTURA DO BANCO DE DADOS

### 1.1. Tabelas Principais

#### 📊 **Tabela: `turmas_teoricas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:83-123`

**Campos principais:**
- `id` (INT, PK)
- `nome` (VARCHAR(200))
- `sala_id` (INT, FK → `salas.id`)
- `instrutor_id` (INT, FK → `instrutores.id`) ⭐ **Campo crítico para permissões**
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
- Uma turma pertence a um instrutor (`instrutores`)

---

#### 📅 **Tabela: `turma_aulas_agendadas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:126-159`

**Campos principais:**
- `id` (INT, PK) ⭐ **Referenciado como `aula_id` em `turma_presencas`**
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
- **`frequencia_percentual`** (DECIMAL(5,2), DEFAULT 0.00) ⭐ **CAMPO CRÍTICO - Atualizado automaticamente**
- `observacoes` (TEXT)
- `atualizado_em` (TIMESTAMP)

**Relacionamentos:**
- Uma matrícula liga um aluno a uma turma
- UNIQUE KEY: `(turma_id, aluno_id)` - um aluno só pode estar matriculado uma vez por turma

**Status:** ✅ Campo `frequencia_percentual` é atualizado automaticamente via `TurmaTeoricaManager::recalcularFrequenciaAluno()`

---

#### ✅ **Tabela: `turma_presencas`**
**Arquivo de definição:** `admin/migrations/001-create-turmas-teoricas-structure.sql:183-202`

**Campos principais:**
- `id` (INT, PK)
- `turma_id` (INT, FK → `turmas_teoricas.id`)
- **`aula_id`** (INT, FK → `turma_aulas_agendadas.id`) ⭐ **Nome correto do campo**
- `aluno_id` (INT, FK → `alunos.id`)
- `presente` (BOOLEAN, DEFAULT FALSE)
- `justificativa` (TEXT, NULL) ⭐ **Nome correto do campo**
- `registrado_por` (INT, FK → `usuarios.id`) ⭐ **AUDITORIA**
- `registrado_em` (TIMESTAMP)

**Relacionamentos:**
- Uma presença liga um aluno a uma aula agendada
- UNIQUE KEY: `(aula_id, aluno_id)` - um aluno só pode ter uma presença por aula

**Status:** ✅ Estrutura correta. API aceita compatibilidade com nomes antigos (`turma_aula_id`, `observacao`)

---

### 1.2. Views e Stored Procedures

#### 📊 **View: `view_turmas_completas`**
**Arquivo:** `admin/migrations/001-create-turmas-teoricas-structure.sql:315-335`

**Descrição:** View com informações completas das turmas (sala, criador, CFC, curso, etc.)

**Status:** ✅ Implementada

---

#### 📊 **View: `view_turma_progresso_disciplinas`**
**Arquivo:** `admin/migrations/001-create-turmas-teoricas-structure.sql:338-357`

**Descrição:** View com progresso das disciplinas por turma (aulas agendadas vs obrigatórias)

**Status:** ✅ Implementada

---

### 1.3. Triggers

#### ⚙️ **Trigger: `after_turma_matricula_insert/update/delete`**
**Arquivo:** `admin/migrations/001-create-turmas-teoricas-structure.sql:230-264`

**Descrição:** Atualiza automaticamente `turmas_teoricas.alunos_matriculados` quando matrículas são criadas/atualizadas/excluídas

**Status:** ✅ Implementado

---

#### ⚙️ **Trigger: `after_aula_agendada_insert/update/delete`**
**Arquivo:** `admin/migrations/001-create-turmas-teoricas-structure.sql:267-306`

**Descrição:** Atualiza automaticamente `turmas_teoricas.carga_horaria_agendada` e `carga_horaria_realizada` quando aulas são criadas/atualizadas/excluídas

**Status:** ✅ Implementado

---

## 2. APIs E ENDPOINTS

### 2.1. API de Presenças

#### 📍 **Endpoint: `admin/api/turma-presencas.php`**
**Métodos suportados:** GET, POST, PUT, DELETE

**Permissões:**
- ✅ Admin: Acesso total
- ✅ Secretaria: Acesso total
- ✅ Instrutor: Apenas suas turmas (validação via `instrutor_id`)

**Funcionalidades:**

**GET:**
- `?turma_id={id}&aula_id={id}` - Buscar presenças de uma aula específica
- `?aluno_id={id}&turma_id={id}` - Buscar presenças de um aluno em uma turma
- `?turma_id={id}` - Buscar todas as presenças de uma turma
- Sem parâmetros - Listar presenças (últimas 100)

**POST:**
- Marcar presença individual ou em lote
- Payload individual:
  ```json
  {
    "turma_id": 1,
    "aula_id": 5,
    "aluno_id": 167,
    "presente": true,
    "justificativa": "Opcional"
  }
  ```
- Payload lote:
  ```json
  {
    "turma_id": 1,
    "aula_id": 5,
    "presencas": [
      {"aluno_id": 167, "presente": true},
      {"aluno_id": 168, "presente": false}
    ]
  }
  ```

**PUT:**
- Atualizar presença existente
- `?id={presenca_id}`
- Payload:
  ```json
  {
    "presente": false,
    "justificativa": "Falta justificada"
  }
  ```

**DELETE:**
- Excluir presença
- `?id={presenca_id}`

**Validações implementadas:**
- ✅ Aluno deve estar matriculado na turma (`turma_matriculas`)
- ✅ Não permite duplicar presença (UNIQUE KEY)
- ✅ Instrutor só pode editar suas próprias turmas
- ✅ Não permite editar presenças de turmas canceladas
- ✅ Instrutor não pode editar presenças de turmas concluídas
- ✅ Não permite editar presenças de aulas canceladas

**Recalculo automático:**
- ✅ Após criar/atualizar/excluir presença, chama `TurmaTeoricaManager::recalcularFrequenciaAluno()`
- ✅ Atualiza `turma_matriculas.frequencia_percentual` automaticamente

**Status:** ✅ **FUNCIONAL E COMPLETO**

---

### 2.2. API de Frequência

#### 📍 **Endpoint: `admin/api/turma-frequencia.php`**
**Métodos suportados:** GET

**Permissões:**
- ✅ Admin: Acesso total
- ✅ Secretaria: Acesso total
- ✅ Instrutor: Acesso total (pode ver frequência de qualquer turma)

**Funcionalidades:**

**GET:**
- `?aluno_id={id}&turma_id={id}` - Calcular frequência de um aluno específico
- `?turma_id={id}` - Calcular frequência de todos os alunos da turma
- Sem parâmetros - Listar frequências (últimas 50 turmas)

**Resposta (aluno específico):**
```json
{
  "success": true,
  "data": {
    "aluno": {...},
    "turma": {...},
    "estatisticas": {
      "total_aulas_programadas": 20,
      "total_aulas_registradas": 15,
      "aulas_presentes": 12,
      "aulas_ausentes": 3,
      "percentual_frequencia": 60.0,
      "status_frequencia": "REPROVADO"
    },
    "historico_presencas": [...]
  }
}
```

**Cálculo de frequência:**
- Fórmula: `(aulas_presentes / total_aulas_programadas) * 100`
- Considera apenas aulas com status `'agendada'` ou `'realizada'` (não conta canceladas)
- Frequência mínima padrão: 75% (se não configurada na turma)

**Status:** ✅ **FUNCIONAL**

---

### 2.3. API de Turmas Teóricas

#### 📍 **Endpoint: `admin/api/turmas-teoricas.php`**
**Métodos suportados:** GET, POST, PUT, DELETE

**Permissões:**
- ✅ Admin: Acesso total

**Funcionalidades relacionadas a presença:**
- Agendar aulas teóricas
- Listar turmas e aulas
- Gerenciar matrículas

**Status:** ✅ **FUNCIONAL**

---

## 3. PÁGINAS FRONTEND

### 3.1. Painel Admin/Secretaria

#### 📋 **Lista de Turmas Teóricas**
**Arquivo:** `admin/pages/turmas-teoricas-lista.php`  
**Rota:** `index.php?page=turmas-teoricas`

**Funcionalidades:**
- Lista todas as turmas teóricas
- Exibe: nome, sala, datas, número de alunos, status
- **NÃO exibe presença/frequência** na listagem

**Status:** ✅ Funcional

---

#### 📊 **Detalhes da Turma**
**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`  
**Rota:** `index.php?page=turmas-teoricas&acao=detalhes&turma_id={id}`

**Funcionalidades:**
- Exibe informações completas da turma
- **Aba "Alunos Matriculados":**
  - Lista alunos com: nome, CPF, categoria, telefone, email
  - **Exibe `frequencia_percentual`** (atualizado automaticamente)
  - Permite matricular novos alunos
- **Aba "Calendário de Aulas":**
  - Lista todas as aulas agendadas
  - Permite agendar novas aulas
  - Link para chamada de cada aula

**Status:** ✅ Funcional

---

#### ✅ **Interface de Chamada**
**Arquivo:** `admin/pages/turma-chamada.php`  
**Rota:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`

**Funcionalidades:**
- Permite marcar presença/falta para todos os alunos de uma aula
- Exibe lista de alunos matriculados
- Botões "Presente" e "Ausente" para cada aluno
- Exibe frequência percentual de cada aluno
- Exibe estatísticas da turma (total presentes, ausentes, sem registro)
- Permite adicionar justificativa/observação

**Permissões:**
- Admin/Secretaria: Pode editar qualquer turma (exceto canceladas)
- Instrutor: Pode editar apenas suas próprias turmas (validação via `instrutor_id`)

**Fluxo de marcação:**
1. JavaScript chama `marcarPresenca(alunoId, presente)`
2. Função faz POST/PUT para `/admin/api/turma-presencas.php`
3. API valida e insere/atualiza em `turma_presencas`
4. Frequência é recalculada automaticamente
5. Interface é atualizada via AJAX

**Status:** ✅ **FUNCIONAL E COMPLETO**

---

#### 📊 **Histórico do Aluno**
**Arquivo:** `admin/pages/historico-aluno.php`  
**Rota:** `index.php?page=historico-aluno&id={aluno_id}`

**Funcionalidades:**
- Exibe progresso teórico e prático
- **Bloco "Presença Teórica":**
  - Lista turmas teóricas do aluno
  - Exibe frequência percentual por turma
  - Tabela de aulas com status de presença (Presente/Ausente/Não registrado)
  - Exibe justificativas (se houver)

**Status:** ✅ **FUNCIONAL** (bloco de presença teórica implementado)

---

### 3.2. Painel Instrutor

#### 📱 **Dashboard do Instrutor**
**Arquivo:** `instrutor/dashboard.php`  
**Rota:** `instrutor/dashboard.php`

**Funcionalidades:**
- Exibe aulas práticas do dia
- **NÃO exibe turmas teóricas** diretamente no dashboard
- **NÃO exibe interface de chamada** diretamente

**Status:** ⚠️ **PARCIAL** - Não mostra turmas teóricas

---

#### 📋 **Lista de Aulas do Instrutor**
**Arquivo:** `instrutor/aulas.php`  
**Rota:** `instrutor/aulas.php`

**Funcionalidades:**
- Lista aulas práticas do instrutor
- Filtros por período e status
- **NÃO lista aulas teóricas**

**Status:** ⚠️ **PARCIAL** - Não mostra aulas teóricas

---

#### ✅ **Interface de Chamada (Compartilhada)**
**Arquivo:** `admin/pages/turma-chamada.php`  
**Rota:** `index.php?page=turma-chamada&turma_id={id}&aula_id={id}`

**Permissões para Instrutor:**
- Instrutor pode acessar se `turma.instrutor_id == userId`
- Se não for o instrutor da turma, `$canEdit = false` (apenas visualização)

**Status:** ✅ **FUNCIONAL** (compartilhado com Admin/Secretaria)

---

### 3.3. Área do Aluno

#### 📱 **Dashboard do Aluno**
**Arquivo:** `aluno/dashboard.php` / `aluno/dashboard-mobile.php`  
**Rota:** `aluno/dashboard.php`

**Funcionalidades:**
- Exibe progresso geral
- Timeline de etapas (exames, aulas teóricas, etc.)
- **NÃO exibe presenças teóricas individuais**
- **NÃO exibe frequência percentual**

**Status:** ❌ **NÃO IMPLEMENTADO** - Aluno não vê presenças teóricas

---

#### 📊 **Histórico do Aluno (via Admin)**
**Arquivo:** `admin/pages/historico-aluno.php`  
**Rota:** `index.php?page=historico-aluno&id={aluno_id}`

**Funcionalidades:**
- Exibe bloco completo de "Presença Teórica"
- Lista turmas teóricas do aluno
- Exibe frequência percentual por turma
- Tabela de aulas com status de presença

**Status:** ✅ **FUNCIONAL** (mas acessível apenas via admin, não diretamente pelo aluno)

---

## 4. REGRAS DE NEGÓCIO IMPLEMENTADAS

### 4.1. Validação de Exames para Matrícula

**Localização:** `admin/includes/TurmaTeoricaManager.php:563-588`

**Regra:**
- Antes de matricular aluno em turma teórica, verifica se exames médico e psicotécnico estão aprovados
- Usa `AgendamentoGuards::verificarExamesOK($alunoId)`
- Se exames não estiverem OK, retorna erro: "Exames médico e psicotécnico não concluídos"

**Status:** ✅ **IMPLEMENTADO**

---

### 4.2. Validação de Presença para Prova Teórica

**Localização:** `admin/includes/ExamesRulesService.php:39-135`

**Regra:**
- Função `podeAgendarProvaTeorica()` verifica:
  1. ✅ Exames médico e psicotécnico aprovados
  2. ✅ Aluno está matriculado em turma teórica válida
  3. ✅ Frequência percentual >= 75% (ou frequência mínima da turma)

**Códigos de retorno:**
- `SEM_TURMA_TEORICA` - Aluno não tem turma teórica válida
- `FREQUENCIA_INSUFICIENTE` - Frequência abaixo do mínimo
- `EXAMES_E_PRESENCA_OK` - Tudo OK

**Status:** ✅ **IMPLEMENTADO**

---

### 4.3. Cálculo de Frequência Percentual

**Localização:** `admin/includes/TurmaTeoricaManager.php::recalcularFrequenciaAluno()`

**Fórmula:**
```
frequencia_percentual = (total_presentes / total_aulas_validas) * 100
```

**Critérios:**
- **Aulas válidas:** Status `'agendada'` ou `'realizada'` (não conta canceladas)
- **Presenças:** Apenas onde `presente = 1` (presentes)
- **Atualização:** Automática após criar/atualizar/excluir presença

**Status:** ✅ **IMPLEMENTADO E AUTOMÁTICO**

---

### 4.4. Regras de Edição de Presença

**Localização:** `admin/api/turma-presencas.php::validarRegrasEdicaoPresenca()`

**Regras para Instrutor:**
- ✅ Pode editar presença apenas se é instrutor da turma (`turmas_teoricas.instrutor_id == userId`)
- ✅ Não pode editar se turma está com status `concluida` ou `cancelada`
- ✅ Não pode editar se aula está com status `cancelada`
- ✅ Pode editar aulas de qualquer data (passadas ou futuras) - sem limite temporal

**Regras para Admin/Secretaria:**
- ✅ Pode editar presença de qualquer turma/aula
- ✅ Não pode editar se turma está `cancelada`
- ✅ Pode editar turmas `concluidas` (diferente do instrutor)
- ✅ Pode editar aulas de qualquer data

**Status:** ✅ **IMPLEMENTADO**

---

### 4.5. Validação de Duplicidade

**Localização:** `admin/api/turma-presencas.php::marcarPresencaIndividual()`

**Regra:**
- Verifica se já existe presença para esta aula/aluno
- Usa UNIQUE KEY `(aula_id, aluno_id)` no banco
- Se existir, retorna erro: "Presença já registrada para este aluno nesta aula"

**Status:** ✅ **IMPLEMENTADO**

---

### 4.6. Validação de Matrícula

**Localização:** `admin/api/turma-presencas.php::marcarPresencaIndividual()`

**Regra:**
- Verifica se aluno está matriculado na turma (`turma_matriculas`)
- Se não estiver, retorna erro: "Aluno não está matriculado nesta turma"

**Status:** ✅ **IMPLEMENTADO**

---

## 5. ARQUIVOS ENVOLVIDOS

### 5.1. Backend (PHP)

#### **APIs:**
- ✅ `admin/api/turma-presencas.php` - CRUD de presenças (COMPLETO)
- ✅ `admin/api/turma-frequencia.php` - Cálculo de frequência (FUNCIONAL)
- ✅ `admin/api/turmas-teoricas.php` - Gerenciamento de turmas e aulas
- ✅ `admin/api/matricular-aluno-turma.php` - Matrícula de alunos

#### **Services/Managers:**
- ✅ `admin/includes/TurmaTeoricaManager.php` - Gerenciamento de turmas (inclui `recalcularFrequenciaAluno()`)
- ✅ `admin/includes/ExamesRulesService.php` - Validações de exames e presença
- ✅ `includes/guards/AgendamentoGuards.php` - Guards de agendamento

#### **Páginas:**
- ✅ `admin/pages/turmas-teoricas-lista.php` - Lista de turmas
- ✅ `admin/pages/turmas-teoricas-detalhes-inline.php` - Detalhes da turma
- ✅ `admin/pages/turma-chamada.php` - Interface de chamada (Admin/Instrutor)
- ✅ `admin/pages/historico-aluno.php` - Histórico do aluno (com bloco de presença teórica)
- ⚠️ `instrutor/dashboard.php` - Dashboard do instrutor (não mostra turmas teóricas)
- ⚠️ `instrutor/aulas.php` - Lista de aulas (não mostra aulas teóricas)
- ❌ `aluno/dashboard.php` - Dashboard do aluno (não mostra presenças teóricas)

---

### 5.2. Frontend (JavaScript)

#### **Arquivos JS:**
- ✅ `admin/pages/turma-chamada.php` (JavaScript inline) - Funções de marcação de presença
  - `marcarPresenca(alunoId, presente)`
  - `criarPresenca(alunoId, presente)`
  - `atualizarPresenca(presencaId, presente)`

---

### 5.3. Banco de Dados

#### **Tabelas:**
- ✅ `turmas_teoricas` - Turmas teóricas
- ✅ `turma_aulas_agendadas` - Aulas agendadas
- ✅ `turma_matriculas` - Matrículas de alunos (com `frequencia_percentual`)
- ✅ `turma_presencas` - Presenças dos alunos
- ✅ `usuarios` - Usuários (para auditoria)
- ✅ `alunos` - Alunos
- ✅ `instrutores` - Instrutores
- ✅ `salas` - Salas

#### **Views:**
- ✅ `view_turmas_completas` - Informações completas das turmas
- ✅ `view_turma_progresso_disciplinas` - Progresso das disciplinas

#### **Triggers:**
- ✅ Triggers para atualizar contadores automáticos

#### **Migração:**
- ✅ `admin/migrations/001-create-turmas-teoricas-structure.sql` - Estrutura inicial

---

## 6. RESUMO EXECUTIVO

### ✅ **O que está funcionando:**

1. **Estrutura de banco de dados:**
   - ✅ Tabelas bem definidas e relacionadas
   - ✅ Triggers para atualização automática de contadores
   - ✅ Views para consultas otimizadas

2. **API de presenças:**
   - ✅ CRUD completo (GET, POST, PUT, DELETE)
   - ✅ Validações de segurança implementadas
   - ✅ Recalculo automático de frequência
   - ✅ Regras de edição por perfil

3. **Interface de chamada:**
   - ✅ Funcional para Admin/Secretaria
   - ✅ Funcional para Instrutor (com restrições)
   - ✅ Exibe frequência percentual
   - ✅ Permite justificativas

4. **Validações de negócio:**
   - ✅ Validação de exames para matrícula
   - ✅ Validação de presença para prova teórica
   - ✅ Cálculo automático de frequência
   - ✅ Regras de edição por perfil

5. **Histórico do aluno:**
   - ✅ Bloco completo de presença teórica (via admin)
   - ✅ Exibe frequência percentual
   - ✅ Lista aulas com status de presença

---

### ❌ **O que está faltando:**

1. **Painel Instrutor:**
   - ❌ Dashboard não mostra turmas teóricas
   - ❌ Lista de aulas não mostra aulas teóricas
   - ❌ Falta link direto para chamada de turmas teóricas

2. **Área do Aluno:**
   - ❌ Dashboard não mostra presenças teóricas
   - ❌ Aluno não vê frequência percentual
   - ❌ Aluno não vê histórico de presenças/faltas
   - ❌ Histórico só acessível via admin (não diretamente pelo aluno)

3. **Relatórios:**
   - ❌ Não há relatório de frequência por turma (exportável)
   - ❌ Não há relatório de alunos com frequência abaixo do mínimo
   - ❌ Não há dashboard de frequência geral

4. **Melhorias de UX:**
   - ❌ Falta botão "Marcar todos presentes" / "Marcar todos ausentes" na chamada
   - ❌ Falta exportação de lista de presença (PDF/Excel)
   - ❌ Falta notificação quando aluno atinge frequência mínima

---

### ⚠️ **O que precisa ser melhorado:**

1. **Integração Instrutor:**
   - ⚠️ Adicionar seção de turmas teóricas no dashboard
   - ⚠️ Adicionar link para chamada de turmas teóricas
   - ⚠️ Adicionar lista de aulas teóricas em `instrutor/aulas.php`

2. **Área do Aluno:**
   - ⚠️ Criar página `aluno/presencas-teoricas.php` ou adicionar bloco no dashboard
   - ⚠️ Permitir que aluno acesse seu histórico diretamente

3. **Relatórios:**
   - ⚠️ Criar página de relatórios de frequência
   - ⚠️ Adicionar exportação PDF/Excel

---

### 🎯 **Onde o sistema está forte:**

1. **Backend robusto:**
   - APIs bem estruturadas
   - Validações completas
   - Recalculo automático de frequência
   - Auditoria implementada

2. **Regras de negócio:**
   - Validação de presença para prova teórica
   - Regras de edição por perfil
   - Cálculo correto de frequência

3. **Interface Admin/Secretaria:**
   - Chamada funcional e intuitiva
   - Histórico completo do aluno
   - Detalhes da turma bem organizados

---

### 🔴 **Onde estão os maiores buracos:**

1. **Área do Aluno:**
   - Aluno não tem acesso direto às suas presenças teóricas
   - Falta transparência para o aluno

2. **Painel Instrutor:**
   - Instrutor não vê suas turmas teóricas facilmente
   - Falta integração entre aulas práticas e teóricas

3. **Relatórios:**
   - Falta visão consolidada de frequência
   - Falta exportação de dados

---

### 📋 **Sugestão de ordem para implementar:**

**Fase 1 - Prioridade Alta (Obrigatório para produção):**
1. ✅ Adicionar seção de turmas teóricas no dashboard do instrutor
2. ✅ Criar página `aluno/presencas-teoricas.php` ou adicionar bloco no dashboard do aluno
3. ✅ Adicionar link para chamada de turmas teóricas no dashboard do instrutor

**Fase 2 - Prioridade Média (Importante para UX):**
4. ⚠️ Adicionar lista de aulas teóricas em `instrutor/aulas.php`
5. ⚠️ Criar página de relatórios de frequência (admin)
6. ⚠️ Adicionar botões "Marcar todos presentes/ausentes" na chamada

**Fase 3 - Prioridade Baixa (Melhorias futuras):**
7. ⚠️ Adicionar exportação PDF/Excel de lista de presença
8. ⚠️ Adicionar notificações quando aluno atinge frequência mínima
9. ⚠️ Criar dashboard de frequência geral (admin)

---

**Fim do Raio-X**

