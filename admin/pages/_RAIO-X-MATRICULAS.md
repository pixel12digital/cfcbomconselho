# 🔍 Raio-X Completo do Sistema de Matrículas

**Data da Análise:** 2025-01-27  
**Objetivo:** Mapear toda a estrutura de matrículas antes de integrar a aba "Matrícula" do modal de aluno

---

## 📊 1. Estrutura da Tabela `matriculas`

### 1.1. Campos Identificados (via código PHP)

Com base nas queries encontradas em `admin/api/matriculas.php`, a tabela `matriculas` possui os seguintes campos:

| Campo | Tipo (inferido) | Descrição | Obrigatório |
|-------|----------------|-----------|-------------|
| `id` | INT AUTO_INCREMENT | Chave primária | ✅ |
| `aluno_id` | INT | FK para `alunos.id` | ✅ |
| `categoria_cnh` | VARCHAR/ENUM | Categoria da CNH (A, B, C, D, E, AB, etc.) | ✅ |
| `tipo_servico` | VARCHAR/ENUM | Tipo de serviço (primeira_habilitacao, reciclagem, etc.) | ✅ |
| `status` | ENUM | Status da matrícula (`ativa`, `concluida`, `cancelada`, etc.) | ✅ |
| `data_inicio` | DATE | Data de início da matrícula | ✅ |
| `data_fim` | DATE | Data de conclusão da matrícula | ❌ |
| `valor_total` | DECIMAL | Valor total do curso | ❌ |
| `forma_pagamento` | VARCHAR/ENUM | Forma de pagamento | ❌ |
| `observacoes` | TEXT | Observações sobre a matrícula | ❌ |
| `criado_em` | TIMESTAMP | Data de criação (inferido) | ❌ |
| `atualizado_em` | TIMESTAMP | Data de atualização (inferido) | ❌ |

### 1.2. Relacionamentos

- **FK `aluno_id`** → `alunos.id` (ON DELETE CASCADE provavelmente)
- **Índices esperados:**
  - `idx_aluno_id` (para buscas por aluno)
  - `idx_status` (para filtros por status)
  - `idx_categoria_tipo` (para validação de duplicatas)

### 1.3. Regras de Negócio Identificadas

1. **Validação de Duplicatas:**
   - Não pode existir mais de uma matrícula **ativa** com a mesma combinação `aluno_id + categoria_cnh + tipo_servico`
   - Código: `admin/api/matriculas.php:129-132`

2. **Exclusão Condicional:**
   - Não pode excluir matrícula se houver aulas vinculadas após a data de início
   - Código: `admin/api/matriculas.php:244-256`

---

## 📋 2. Outras Tabelas Relacionadas à Matrícula

### 2.1. `turma_matriculas` (Matrículas em Turmas Teóricas)

**Estrutura completa** (via `admin/migrations/001-create-turmas-teoricas-structure.sql`):

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT AUTO_INCREMENT | Chave primária |
| `turma_id` | INT | FK para `turmas_teoricas.id` |
| `aluno_id` | INT | FK para `alunos.id` |
| `data_matricula` | TIMESTAMP | Data da matrícula na turma |
| `status` | ENUM | `matriculado`, `cursando`, `concluido`, `evadido`, `transferido` |
| `exames_validados_em` | TIMESTAMP | Data de validação dos exames |
| `frequencia_percentual` | DECIMAL(5,2) | Percentual de frequência |
| `observacoes` | TEXT | Observações |
| `atualizado_em` | TIMESTAMP | Data de atualização |

**Relacionamentos:**
- FK `turma_id` → `turmas_teoricas.id` (ON DELETE CASCADE)
- FK `aluno_id` → `alunos.id` (ON DELETE CASCADE)
- **UNIQUE KEY:** `(turma_id, aluno_id)` - Um aluno só pode estar matriculado uma vez na mesma turma

**Triggers:**
- `after_turma_matricula_insert` - Atualiza contador `alunos_matriculados` em `turmas_teoricas`
- `after_turma_matricula_update` - Atualiza contador `alunos_matriculados` em `turmas_teoricas`
- `after_turma_matricula_delete` - Atualiza contador `alunos_matriculados` em `turmas_teoricas`

### 2.2. `aulas` (Aulas Práticas/Teóricas)

**Estrutura** (via `install.php`):

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT AUTO_INCREMENT | Chave primária |
| `aluno_id` | INT | FK para `alunos.id` |
| `instrutor_id` | INT | FK para `instrutores.id` |
| `cfc_id` | INT | FK para `cfcs.id` |
| `tipo_aula` | ENUM | `teorica`, `pratica` |
| `data_aula` | DATE | Data da aula |
| `hora_inicio` | TIME | Hora de início |
| `hora_fim` | TIME | Hora de fim |
| `status` | ENUM | `agendada`, `em_andamento`, `concluida`, `cancelada` |
| `observacoes` | TEXT | Observações |
| `criado_em` | TIMESTAMP | Data de criação |

**Relacionamento com Matrícula:**
- Não há FK direta para `matriculas.id`
- Relacionamento indireto via `aluno_id` e `data_aula >= data_inicio` (validação na exclusão)

### 2.3. `exames` (Exames Médicos/Psicotécnicos)

**Estrutura** (via `install.php`):

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT AUTO_INCREMENT | Chave primária |
| `aluno_id` | INT | FK para `alunos.id` |
| `tipo` | ENUM | `medico`, `psicotecnico` |
| `status` | ENUM | `agendado`, `concluido`, `cancelado` |
| `resultado` | ENUM | `apto`, `inapto`, `inapto_temporario`, `pendente` |
| `clinica_nome` | VARCHAR(200) | Nome da clínica |
| `protocolo` | VARCHAR(100) | Protocolo do exame |
| `data_agendada` | DATE | Data agendada |
| `data_resultado` | DATE | Data do resultado |
| `observacoes` | TEXT | Observações |
| `anexos` | TEXT | Anexos (JSON provavelmente) |
| `criado_por` | INT | FK para `usuarios.id` |
| `atualizado_por` | INT | FK para `usuarios.id` |

**Relacionamento com Matrícula:**
- Não há FK direta para `matriculas.id`
- Relacionamento indireto via `aluno_id`

### 2.4. `financeiro_faturas` (Faturas Financeiras)

**Estrutura inferida** (via código):

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT AUTO_INCREMENT | Chave primária |
| `matricula_id` | INT | FK para `matriculas.id` |
| `aluno_id` | INT | FK para `alunos.id` |
| `titulo` | VARCHAR | Título/descrição da fatura |
| `valor` | DECIMAL | Valor da fatura |
| `data_vencimento` | DATE | Data de vencimento |
| `status` | ENUM | `aberta`, `paga`, `vencida`, `cancelada` |
| `desconto` | DECIMAL | Desconto aplicado |
| `acrescimo` | DECIMAL | Acréscimo aplicado |
| `numero` | VARCHAR | Número da fatura (ex: FAT-2025-0001) |

**Relacionamento com Matrícula:**
- FK `matricula_id` → `matriculas.id` (direto)
- FK `aluno_id` → `alunos.id` (redundante, mas útil para queries)

**Arquivos que usam:**
- `admin/api/faturas.php` - CRUD de faturas
- `admin/pages/financeiro-faturas.php` - Listagem de faturas
- `admin/index.php` - Criação de faturas em lote

### 2.5. `turma_aulas_agendadas` (Aulas Agendadas de Turmas Teóricas)

**Estrutura** (via migration):

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT AUTO_INCREMENT | Chave primária |
| `turma_id` | INT | FK para `turmas_teoricas.id` |
| `disciplina` | ENUM | Disciplina da aula |
| `nome_aula` | VARCHAR(200) | Nome da aula |
| `instrutor_id` | INT | FK para `instrutores.id` |
| `sala_id` | INT | FK para `salas.id` |
| `data_aula` | DATE | Data da aula |
| `hora_inicio` | TIME | Hora de início |
| `hora_fim` | TIME | Hora de fim |
| `duracao_minutos` | INT | Duração em minutos |
| `status` | ENUM | `agendada`, `realizada`, `cancelada` |

**Relacionamento com Matrícula:**
- Indireto via `turma_id` → `turmas_teoricas` → `turma_matriculas` → `aluno_id`

### 2.6. `turma_presencas` (Presenças em Aulas Teóricas)

**Estrutura** (via migration):

| Campo | Tipo | Descrição |
|-------|------|-----------|
| `id` | INT AUTO_INCREMENT | Chave primária |
| `turma_id` | INT | FK para `turmas_teoricas.id` |
| `aula_id` | INT | FK para `turma_aulas_agendadas.id` |
| `aluno_id` | INT | FK para `alunos.id` |
| `presente` | BOOLEAN | Se o aluno estava presente |
| `justificativa` | TEXT | Justificativa da falta |
| `registrado_por` | INT | FK para `usuarios.id` |
| `registrado_em` | TIMESTAMP | Data do registro |

**Relacionamento com Matrícula:**
- Indireto via `aluno_id` e `turma_id` → `turma_matriculas`

---

## 💻 3. Arquivos PHP que Manipulam Matrículas

### 3.1. API de Matrículas (`admin/api/matriculas.php`)

**Funções principais:**

1. **`handleGet($db)`** - Listar matrículas
   - GET `/api/matriculas.php?aluno_id=X` - Matrículas de um aluno
   - GET `/api/matriculas.php` - Todas as matrículas (limit 100)
   - Retorna: `{ success: true, matriculas: [...] }`

2. **`handlePost($db)`** - Criar matrícula
   - POST `/api/matriculas.php`
   - Campos obrigatórios: `aluno_id`, `categoria_cnh`, `tipo_servico`, `data_inicio`
   - Validação: Não permite duplicatas ativas
   - Retorna: `{ success: true, matricula_id: X }`

3. **`handlePut($db)`** - Atualizar matrícula
   - PUT `/api/matriculas.php?id=X`
   - Atualiza: `categoria_cnh`, `tipo_servico`, `status`, `data_inicio`, `data_fim`, `valor_total`, `forma_pagamento`, `observacoes`
   - Retorna: `{ success: true, message: '...' }`

4. **`handleDelete($db)`** - Excluir matrícula
   - DELETE `/api/matriculas.php?id=X`
   - Validação: Não permite exclusão se houver aulas vinculadas
   - Retorna: `{ success: true, message: '...' }`

**Permissões:**
- Requer autenticação (`isLoggedIn()`)
- Requer permissão `admin` ou `secretaria`

### 3.2. Sistema de Matrícula (`admin/includes/sistema_matricula.php`)

**Classe:** `SistemaMatricula`

**Métodos principais:**

1. **`processarMatricula($dadosAluno)`**
   - Processa matrícula de novo aluno
   - Valida dados do aluno
   - Obtém configuração da categoria
   - Insere aluno na tabela `alunos`
   - Cria slots de aulas baseados na configuração
   - Cria credenciais automáticas

2. **`getInfoMatricula($alunoId)`**
   - Retorna informações da matrícula de um aluno
   - Inclui: dados do aluno, slots de aulas, aulas agendadas/concluídas
   - **Nota:** Não usa a tabela `matriculas`, apenas `alunos` e `aulas_slots`

3. **`criarSlotsAulas($alunoId, $configuracao)`**
   - Cria slots de aulas baseados na configuração da categoria
   - Usa tabela `aulas_slots` (não mapeada neste relatório)

**Observação importante:**
- Este arquivo **não usa a tabela `matriculas`**
- Trabalha diretamente com `alunos` e `aulas_slots`
- Pode ser código legado ou sistema paralelo

### 3.3. API de Alunos (`admin/api/alunos.php`)

**Relacionamento com Matrícula:**

- **POST/PUT** - Salva campo `operacoes` (JSON) na tabela `alunos`
- Campo `operacoes` contém array de operações com `categoria_cnh` e `tipo_servico`
- **Não cria registro em `matriculas`** - apenas salva JSON em `alunos.operacoes`

**Código relevante:**
```php
'operacoes' => isset($data['operacoes']) ? json_encode($data['operacoes']) : null,
```

### 3.4. API de Faturas (`admin/api/faturas.php`)

**Relacionamento com Matrícula:**

- **POST** - Cria fatura vinculada a `matricula_id`
- Valida se matrícula existe antes de criar fatura
- Código: `admin/api/faturas.php:210`

### 3.5. Página de Alunos (`admin/pages/alunos.php`)

**Funções JavaScript relacionadas:**

1. **`carregarMatriculas(alunoId)`** (linha 6653)
   - Chama: `GET /api/matriculas.php?aluno_id=X`
   - Preenche: `#matriculas-list` na aba Matrícula
   - Exibe: Tabela com categoria, tipo serviço, status, data início

2. **`carregarDadosAba(abaId, alunoId)`** (linha 6787)
   - Chama `carregarMatriculas()` quando `abaId === 'matricula'`

**Observação:**
- A função `carregarMatriculas()` já existe e está funcional
- A aba Matrícula já tem estrutura HTML preparada
- Falta apenas integrar o salvamento dos dados do formulário

---

## 🔗 4. Como a Matrícula é Vinculada ao Aluno

### 4.1. Via Tabela `matriculas`

- **Campo:** `aluno_id` (FK para `alunos.id`)
- **Relacionamento:** 1 aluno pode ter N matrículas
- **Validação:** Não pode ter 2 matrículas ativas com mesma categoria + tipo_servico

### 4.2. Via Tabela `alunos` (Campo `operacoes`)

- **Campo:** `operacoes` (TEXT/JSON)
- **Conteúdo:** Array JSON com operações do aluno
- **Estrutura esperada:**
  ```json
  [
    {
      "categoria_cnh": "B",
      "tipo_servico": "primeira_habilitacao",
      "categoria": "B"
    }
  ]
  ```
- **Uso atual:** Salvo junto com o cadastro/edição do aluno
- **Problema:** Não há sincronização com a tabela `matriculas`

### 4.3. Via Tabela `turma_matriculas`

- **Campo:** `aluno_id` (FK para `alunos.id`)
- **Relacionamento:** 1 aluno pode estar em N turmas teóricas
- **Validação:** UNIQUE `(turma_id, aluno_id)`

### 4.4. Via Tabela `aulas`

- **Campo:** `aluno_id` (FK para `alunos.id`)
- **Relacionamento:** 1 aluno pode ter N aulas
- **Validação na exclusão:** Verifica se há aulas após `data_inicio` da matrícula

---

## 🖥️ 5. Telas de Cadastro de Matrícula

### 5.1. Tela de Cadastro de Aluno (`admin/pages/alunos.php`)

**Status:** ✅ Existe

**Funcionalidade:**
- Cadastro/edição de aluno
- Campo `operacoes` (JSON) é salvo na tabela `alunos`
- **Não cria registro em `matriculas`**

**Aba Matrícula do Modal:**
- Estrutura HTML preparada
- Campos: `operacoes-container`, `data_matricula`, `previsao_conclusao`, `data_conclusao`, `status_matricula`, `renach`, `processo_numero`, `processo_numero_detran`, `processo_situacao`, `turma_teorica_atual_id`, `situacao_teorica`, `aulas_praticas_contratadas`, `aulas_praticas_extras`, `instrutor_principal_id`, `situacao_pratica`, `valor_curso`, `forma_pagamento`, `status_pagamento`
- **Não salva ainda** - apenas estrutura HTML

### 5.2. Tela de Turmas Teóricas

**Status:** ✅ Existe

**Funcionalidade:**
- Criação/edição de turmas teóricas
- Matrícula de alunos em turmas (via `turma_matriculas`)
- **Não cria registro em `matriculas`**

### 5.3. Tela de Faturas (`admin/pages/financeiro-faturas.php`)

**Status:** ✅ Existe

**Funcionalidade:**
- Listagem de faturas
- Criação de faturas vinculadas a `matricula_id`
- **Requer que a matrícula já exista em `matriculas`**

---

## ⚠️ 6. Conflitos e Código Legado Identificados

### 6.1. Duplicação de Conceito de Matrícula

**Problema:** Existem **dois sistemas paralelos** de matrícula:

1. **Sistema via `matriculas` (tabela dedicada):**
   - API completa em `admin/api/matriculas.php`
   - Estrutura normalizada
   - Validações de duplicatas
   - Relacionamento com faturas

2. **Sistema via `alunos.operacoes` (JSON):**
   - Salvo junto com cadastro de aluno
   - Não tem validações
   - Não tem relacionamento com faturas
   - Usado pelo `SistemaMatricula` (legado?)

**Impacto:**
- Dados podem estar desincronizados
- A aba Matrícula do modal precisa decidir qual sistema usar

### 6.2. Campo `operacoes` vs Tabela `matriculas`

**Situação atual:**
- O formulário de aluno salva `operacoes` (JSON) em `alunos.operacoes`
- A API de matrículas espera dados em `matriculas`
- **Não há sincronização automática**

**Solução necessária:**
- Decidir se `operacoes` é fonte de verdade ou se `matriculas` é
- Criar sincronização ou migrar dados

### 6.3. Validação de Duplicatas

**Sistema `matriculas`:**
- Valida: Não pode ter 2 matrículas ativas com mesma `categoria_cnh + tipo_servico`
- Código: `admin/api/matriculas.php:129-132`

**Sistema `alunos.operacoes`:**
- Não valida duplicatas
- Pode ter múltiplas operações com mesma categoria

---

## 📝 7. Sugestão de Fluxo de Integração

### 7.1. Opção A: Manter 1 Matrícula Ativa por Aluno (Recomendada)

**Vantagens:**
- ✅ Mais simples de implementar
- ✅ Alinha com validação existente (não permite duplicatas ativas)
- ✅ Menos conflitos com código legado
- ✅ Facilita sincronização com `alunos.operacoes`

**Implementação:**
1. Ao salvar a aba Matrícula:
   - Se não existe matrícula ativa → Criar nova em `matriculas`
   - Se existe matrícula ativa → Atualizar a existente
   - Sincronizar `alunos.operacoes` com a matrícula ativa

2. Ao carregar a aba Matrícula:
   - Buscar matrícula ativa em `matriculas`
   - Se não existir, criar a partir de `alunos.operacoes` (migração automática)
   - Preencher formulário com dados da matrícula

3. Campos a sincronizar:
   - `operacoes` (JSON) ↔ `categoria_cnh` + `tipo_servico` da matrícula
   - `data_matricula` ↔ `data_inicio`
   - `status_matricula` ↔ `status`
   - `renach` → Manter em `alunos.renach` (já existe)
   - Campos de processo DETRAN → Adicionar em `matriculas` se necessário

**Desvantagens:**
- ❌ Não permite múltiplas matrículas simultâneas (ex: categoria B e C ao mesmo tempo)
- ❌ Requer migração de dados existentes

### 7.2. Opção B: Permitir Múltiplas Matrículas por Aluno

**Vantagens:**
- ✅ Mais flexível
- ✅ Permite múltiplas categorias simultâneas
- ✅ Alinha com estrutura da tabela `matriculas` (já suporta N matrículas)

**Implementação:**
1. Ao salvar a aba Matrícula:
   - Identificar qual matrícula está sendo editada (via `matricula_id` hidden)
   - Se `matricula_id` existe → Atualizar
   - Se não existe → Criar nova
   - Permitir múltiplas matrículas ativas (remover validação de duplicatas ou ajustar)

2. Ao carregar a aba Matrícula:
   - Listar todas as matrículas do aluno
   - Permitir seleção de qual matrícula editar
   - Ou criar nova matrícula

3. Sincronização com `alunos.operacoes`:
   - `operacoes` seria um "resumo" das matrículas ativas
   - Ou remover `operacoes` e usar apenas `matriculas`

**Desvantagens:**
- ❌ Mais complexo de implementar
- ❌ Requer ajustes na validação de duplicatas
- ❌ Pode conflitar com código que espera 1 matrícula ativa
- ❌ UI mais complexa (seleção de qual matrícula editar)

---

## 🎯 8. Recomendação Final

### **Opção A é mais adequada para começar:**

1. **Simplicidade:** Menos mudanças no código existente
2. **Validação existente:** Já previne duplicatas ativas
3. **Migração gradual:** Pode migrar dados de `operacoes` para `matriculas` aos poucos
4. **UI mais simples:** Não precisa de seleção de matrícula

### **Passos sugeridos para integração:**

1. **Fase 1 - Migração de Dados:**
   - Criar script para migrar `alunos.operacoes` → `matriculas`
   - Para cada aluno com `operacoes` não vazio, criar 1 matrícula ativa

2. **Fase 2 - Sincronização Bidirecional:**
   - Ao salvar aba Matrícula → Criar/atualizar `matriculas` + atualizar `alunos.operacoes`
   - Ao carregar aba Matrícula → Buscar `matriculas` ativa ou criar a partir de `operacoes`

3. **Fase 3 - Deprecação de `operacoes`:**
   - Manter `operacoes` como campo calculado (derivado de `matriculas`)
   - Ou remover completamente após migração

4. **Fase 4 - Campos Adicionais:**
   - Adicionar campos de processo DETRAN em `matriculas` se necessário
   - Adicionar campos de vinculação teórica/prática se necessário

---

## 📌 9. Checklist de Integração

- [ ] Decidir entre Opção A ou B
- [ ] Criar/verificar estrutura da tabela `matriculas` no banco
- [ ] Criar script de migração `operacoes` → `matriculas`
- [ ] Implementar salvamento da aba Matrícula → `matriculas`
- [ ] Implementar carregamento da aba Matrícula ← `matriculas`
- [ ] Sincronizar `operacoes` com `matriculas` (bidirecional)
- [ ] Testar validação de duplicatas
- [ ] Testar relacionamento com faturas
- [ ] Testar relacionamento com turmas teóricas (via `turma_matriculas`)
- [ ] Documentar mudanças

---

## 📚 10. Referências de Arquivos

- `admin/api/matriculas.php` - API CRUD de matrículas
- `admin/pages/alunos.php` - Página e modal de alunos (linha 6653+)
- `admin/includes/sistema_matricula.php` - Sistema legado (não usa `matriculas`)
- `admin/api/alunos.php` - API de alunos (salva `operacoes`)
- `admin/api/faturas.php` - API de faturas (usa `matricula_id`)
- `admin/migrations/001-create-turmas-teoricas-structure.sql` - Estrutura de `turma_matriculas`

---

**Fim do Relatório**

