# 📚 PLANO DE REESTRUTURAÇÃO - SISTEMA DE TURMAS TEÓRICAS

## 📋 **ANÁLISE DO SISTEMA ATUAL**

### 🔍 **Estado Atual**
- Sistema básico de turmas com campos genéricos
- Falta estrutura para salas e disciplinas específicas
- Não há fluxo estruturado em etapas
- Agendamento de aulas individual (não por turma)
- Validação de exames já implementada ✅

### ❌ **Problemas Identificados**
1. **Campos inadequados**: Sistema atual tem campos genéricos demais
2. **Falta de controle de sala**: Não há gestão de conflitos de sala
3. **Ausência de disciplinas estruturadas**: Não há controle por disciplina
4. **Fluxo desorganizado**: Criação e gestão em uma única tela
5. **Sem controle de carga horária**: Não valida se completou todas as disciplinas

---

## 🎯 **PROPOSTA DE REESTRUTURAÇÃO**

### 📊 **Novo Fluxo em 4 Etapas**

#### **ETAPA 1: Criação da Turma Básica**
```
🏷️ Campos da Turma:
├── Nome da Turma
├── Sala (dropdown com salas disponíveis)
├── Curso (select predefinido):
│   ├── Curso de reciclagem para condutor infrator
│   ├── Curso de formação de condutores - Permissão 45h
│   ├── Curso de atualização
│   └── Curso de formação de condutores - ACC 20h
├── Modalidade: [Online] [Presencial]
├── Período: [Data Início] até [Data Fim]
└── Observações (textarea)
```

#### **ETAPA 2: Agendamento de Aulas**
```
📅 Configuração por Disciplina:
├── Instrutor (dropdown com instrutores ativos)
├── Disciplina (baseada no curso selecionado)
├── Data da aula
├── Horário (dropdown com slots disponíveis)
├── Quantidade de aulas no dia (máx 5)
└── Validações:
    ├── ❌ Instrutor já agendado no horário
    ├── ❌ Sala já ocupada no horário
    └── ✅ Slots disponíveis
```

#### **ETAPA 3: Validação de Carga Horária**
```
⏱️ Controle de Disciplinas:
├── Legislação de Trânsito: [12/12 aulas] ✅
├── Primeiros Socorros: [4/4 aulas] ✅  
├── Direção Defensiva: [8/12 aulas] ⚠️
├── Meio Ambiente: [2/4 aulas] ⚠️
└── Status: Incompleto (faltam 6 aulas)
```

#### **ETAPA 4: Inserção de Alunos**
```
👥 Matrícula de Alunos:
├── Validação de exames (já implementada) ✅
├── Verificação de vagas disponíveis
├── Lista de alunos elegíveis
└── Confirmação de matrícula
```

---

## 🗃️ **ESTRUTURA DO BANCO DE DADOS**

### 📋 **Tabelas Necessárias**

#### **1. `turmas_teoricas` (Nova estrutura)**
```sql
CREATE TABLE turmas_teoricas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(200) NOT NULL,
    sala_id INT NOT NULL,
    curso_tipo ENUM(
        'reciclagem_infrator',
        'formacao_45h', 
        'atualizacao',
        'formacao_acc_20h'
    ) NOT NULL,
    modalidade ENUM('online', 'presencial') NOT NULL,
    data_inicio DATE NOT NULL,
    data_fim DATE NOT NULL,
    observacoes TEXT,
    status ENUM('criando', 'agendando', 'completa', 'ativa', 'concluida') DEFAULT 'criando',
    carga_horaria_total INT DEFAULT 0,
    carga_horaria_agendada INT DEFAULT 0,
    max_alunos INT DEFAULT 30,
    alunos_matriculados INT DEFAULT 0,
    cfc_id INT NOT NULL,
    criado_por INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_sala_data (sala_id, data_inicio, data_fim),
    INDEX idx_curso_status (curso_tipo, status),
    FOREIGN KEY (sala_id) REFERENCES salas(id),
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
);
```

#### **2. `salas` (Nova tabela)**
```sql
CREATE TABLE salas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    capacidade INT NOT NULL DEFAULT 30,
    equipamentos JSON,
    ativa BOOLEAN DEFAULT TRUE,
    cfc_id INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cfc_id) REFERENCES cfcs(id)
);
```

#### **3. `turma_aulas_agendadas` (Nova estrutura)**
```sql
CREATE TABLE turma_aulas_agendadas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    disciplina ENUM(
        'legislacao_transito',
        'primeiros_socorros', 
        'direcao_defensiva',
        'meio_ambiente_cidadania',
        'mecanica_basica'
    ) NOT NULL,
    instrutor_id INT NOT NULL,
    sala_id INT NOT NULL,
    data_aula DATE NOT NULL,
    hora_inicio TIME NOT NULL,
    hora_fim TIME NOT NULL,
    ordem_disciplina INT NOT NULL,
    status ENUM('agendada', 'realizada', 'cancelada') DEFAULT 'agendada',
    observacoes TEXT,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_conflitos (instrutor_id, data_aula, hora_inicio, hora_fim),
    INDEX idx_sala_conflitos (sala_id, data_aula, hora_inicio, hora_fim),
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (instrutor_id) REFERENCES instrutores(id),
    FOREIGN KEY (sala_id) REFERENCES salas(id)
);
```

#### **4. `disciplinas_configuracao` (Nova tabela)**
```sql
CREATE TABLE disciplinas_configuracao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    curso_tipo ENUM(
        'reciclagem_infrator',
        'formacao_45h', 
        'atualizacao',
        'formacao_acc_20h'
    ) NOT NULL,
    disciplina ENUM(
        'legislacao_transito',
        'primeiros_socorros', 
        'direcao_defensiva',
        'meio_ambiente_cidadania',
        'mecanica_basica'
    ) NOT NULL,
    aulas_obrigatorias INT NOT NULL,
    ordem INT NOT NULL,
    ativa BOOLEAN DEFAULT TRUE,
    UNIQUE KEY unique_curso_disciplina (curso_tipo, disciplina)
);
```

#### **5. `turma_matriculas` (Melhorada)**
```sql
CREATE TABLE turma_matriculas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    turma_id INT NOT NULL,
    aluno_id INT NOT NULL,
    data_matricula TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('matriculado', 'cursando', 'concluido', 'evadido') DEFAULT 'matriculado',
    exames_aprovados_em TIMESTAMP,
    observacoes TEXT,
    UNIQUE KEY unique_turma_aluno (turma_id, aluno_id),
    FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE,
    FOREIGN KEY (aluno_id) REFERENCES alunos(id)
);
```

---

## 🖥️ **INTERFACE DE USUÁRIO**

### 📱 **Wireframe do Novo Fluxo**

#### **Tela 1: Lista de Turmas**
```
┌─────────────────────────────────────────────────────┐
│ 📚 GESTÃO DE TURMAS TEÓRICAS                       │
├─────────────────────────────────────────────────────┤
│ [+ Nova Turma Teórica]                    [🔍]     │
├─────────────────────────────────────────────────────┤
│ Turma A - Formação 45h    [👥 25/30] [📅 Ativa]   │
│ Sala 1 | 01/Nov - 30/Nov  [⚙️] [👁️] [✏️]        │
├─────────────────────────────────────────────────────┤
│ Turma B - Reciclagem      [👥 15/30] [⏳ Criando] │
│ Sala 2 | 15/Nov - 30/Nov  [⚙️] [👁️] [✏️]        │
└─────────────────────────────────────────────────────┘
```

#### **Tela 2: Wizard - Etapa 1**
```
┌─────────────────────────────────────────────────────┐
│ ✨ NOVA TURMA TEÓRICA - Etapa 1/4                  │
├─────────────────────────────────────────────────────┤
│ Nome da Turma: [________________]                   │
│ Sala: [Sala 1 - Cap. 30 ▼]                        │
│ Curso: [Formação 45h ▼]                            │
│ Modalidade: (•) Presencial  ( ) Online            │
│ Período: [01/11/2024] até [30/11/2024]            │
│ Observações: [_________________________]           │
│                                                     │
│          [Cancelar] [Próxima Etapa →]              │
└─────────────────────────────────────────────────────┘
```

#### **Tela 3: Wizard - Etapa 2**
```
┌─────────────────────────────────────────────────────┐
│ 📅 AGENDAMENTO DE AULAS - Etapa 2/4                │
├─────────────────────────────────────────────────────┤
│ Turma: Turma A - Sala 1 | 01/Nov - 30/Nov         │
├─────────────────────────────────────────────────────┤
│ Disciplina: [Legislação de Trânsito ▼] (12 aulas) │
│ Instrutor: [João Silva ▼]                          │
│ Data: [01/11/2024]  Horário: [08:00 ▼]            │
│ Qtd Aulas: [2 ▼] (máx 5)                          │
│                                                     │
│ [Adicionar Agendamento]                             │
├─────────────────────────────────────────────────────┤
│ ✅ 01/11 - 08:00-09:40 - Legislação (2 aulas)     │
│ ✅ 02/11 - 08:00-09:40 - Legislação (2 aulas)     │
│ ❌ 03/11 - 08:00 - Conflito: Instrutor ocupado    │
└─────────────────────────────────────────────────────┘
```

---

## ⚙️ **IMPLEMENTAÇÃO TÉCNICA**

### 🔧 **Arquivos a Criar/Modificar**

#### **Backend**
1. **`admin/includes/TurmaTeoricaManager.php`** - Classe principal
2. **`admin/api/turmas-teoricas.php`** - API REST
3. **`admin/includes/SalaManager.php`** - Gestão de salas
4. **`admin/includes/DisciplinaManager.php`** - Controle de disciplinas

#### **Frontend**
1. **`admin/pages/turmas-teoricas.php`** - Lista de turmas
2. **`admin/pages/turma-wizard-step1.php`** - Criação básica
3. **`admin/pages/turma-wizard-step2.php`** - Agendamento
4. **`admin/pages/turma-wizard-step3.php`** - Controle carga horária
5. **`admin/pages/turma-wizard-step4.php`** - Inserção alunos

#### **JavaScript**
1. **`admin/assets/js/turma-wizard.js`** - Controle do wizard
2. **`admin/assets/js/agendamento-conflitos.js`** - Validação em tempo real

---

## 🚀 **CRONOGRAMA DE IMPLEMENTAÇÃO**

### **FASE 1: Estrutura (Dias 1-2)**
- [ ] Criar novas tabelas do banco
- [ ] Migrar dados existentes
- [ ] Criar classes PHP básicas

### **FASE 2: Backend (Dias 3-5)**
- [ ] Implementar TurmaTeoricaManager
- [ ] Criar API REST completa
- [ ] Sistema de validação de conflitos

### **FASE 3: Frontend (Dias 6-8)**
- [ ] Criar wizard multi-etapas
- [ ] Interface de agendamento
- [ ] Sistema de controle de carga horária

### **FASE 4: Testes e Ajustes (Dias 9-10)**
- [ ] Testes de conflito de horários
- [ ] Validação de carga horária
- [ ] Interface responsiva

---

## 🎯 **BENEFÍCIOS DA NOVA ESTRUTURA**

### ✅ **Vantagens**
1. **Fluxo Organizado**: Etapas claras e sequenciais
2. **Validação Robusta**: Evita conflitos de horário/sala
3. **Controle de Qualidade**: Garante carga horária completa
4. **Interface Intuitiva**: Wizard guiado passo a passo
5. **Escalabilidade**: Estrutura preparada para crescimento

### 📊 **Métricas de Sucesso**
- ⏱️ Redução de 70% no tempo de criação de turmas
- 🚫 Zero conflitos de agendamento não detectados
- 📈 100% das turmas com carga horária completa
- 👥 Interface 90% mais intuitiva (feedback usuários)

---

## 🔄 **MIGRAÇÃO DO SISTEMA ATUAL**

### 📋 **Plano de Migração**
1. **Backup completo** do sistema atual
2. **Criação das novas tabelas** em paralelo
3. **Script de migração** dos dados existentes
4. **Período de testes** com dados reais
5. **Ativação gradual** do novo sistema

### ⚠️ **Considerações**
- Sistema atual continua funcionando durante migração
- Dados históricos preservados
- Rollback disponível se necessário
- Treinamento da equipe incluído

---

## 📞 **PRÓXIMOS PASSOS**

1. **Aprovação do plano** pela equipe
2. **Início da implementação** conforme cronograma
3. **Testes incrementais** a cada etapa
4. **Feedback contínuo** durante desenvolvimento
5. **Go-live** com suporte completo

---

**💡 Este plano transforma o sistema atual em uma solução robusta, organizada e escalável para gestão de turmas teóricas, seguindo as melhores práticas de UX e desenvolvimento.**
