# 📋 PLANO: NOVA TELA "AGENDAR AULA (POR ALUNO)" COM SLOTS VISUAIS

**Data:** 2025-01-28  
**Objetivo:** Especificação funcional da nova tela de agendamento por aluno usando slots visuais, baseada no Raio-X da Agenda/Agendamentos.

**Status:** 📝 Documentação / Especificação (sem implementação ainda)

---

## 📋 1. RESUMO DA TELA

### 🎯 **Onde é Acessada:**

- **Botão "Agendar Aula"** na ficha/modal do aluno em `admin/pages/alunos.php`
  - Função JavaScript: `agendarAula(alunoId)` (linha 4854)
  - Redirecionamento: `index.php?page=agendar-aula&aluno_id=X` (linha 8413)
  - URL atual: `admin/pages/agendar-aula.php?aluno_id=X`

### 🎯 **Objetivo Principal:**

Agendar aula prática para um aluno específico através de **slots visuais** que mostram apenas horários disponíveis, eliminando tentativas de agendamento em horários ocupados e melhorando a experiência do usuário.

**Diferencial da nova versão:**
- Mostra **apenas slots disponíveis** (não deixa o usuário escolher manualmente data/hora que pode estar ocupada)
- Interface guiada por calendário/dias com slots clicáveis
- Validação preventiva (não espera enviar o formulário para descobrir conflito)

### 📊 **Tabela e API de Escrita:**

- **Tabela:** `aulas` (conforme `install.php` linhas 88-103)
- **API de Criação:** `admin/api/agendamento.php` (método POST, função `criarAula()` linha 201)
- **API de Leitura (Slots):** `admin/api/disponibilidade.php` (método GET)

---

## 🔄 2. FLUXO DO USUÁRIO (PASSO A PASSO)

### **Passo 1: Abrir a Tela Já com o Aluno Selecionado**

**Ação:**
- Usuário clica em "Agendar Aula" na ficha/modal do aluno
- Redirecionamento: `index.php?page=agendar-aula&aluno_id=X`

**O que a Tela Mostra:**
- Header com informações do aluno (nome, CPF, CFC, status)
- Botão "Voltar para Alunos"
- Seção de seleção de tipo de agendamento

**Implementação:**
- Página PHP: `admin/pages/agendar-aula.php`
- Carrega dados do aluno via `admin/index.php` (switch/case para `$page === 'agendar-aula'`)
- Query: `SELECT * FROM alunos WHERE id = ?`

---

### **Passo 2: Escolher Tipo de Agendamento**

**Opções (reaproveitar lógica existente de `agendamento.php`):**

| Tipo | Valor | Duração Total | Descrição |
|------|-------|---------------|-----------|
| **1 Aula** | `unica` | 50 minutos | Uma aula simples |
| **2 Aulas** | `duas` | 1h 40min (100 minutos) | Duas aulas consecutivas |
| **3 Aulas** | `tres` | 2h 30min (180 minutos) | Três aulas com intervalo de 30min |

**Para 3 Aulas - Posição do Intervalo:**

| Posição | Valor | Descrição |
|---------|-------|-----------|
| **2 consecutivas + intervalo + 1 aula** | `depois` | Primeiro bloco de 2 aulas, depois intervalo, depois 1 aula |
| **1 aula + intervalo + 2 consecutivas** | `antes` | Primeira aula, depois intervalo, depois bloco de 2 aulas |

**Interface:**
- Radio buttons estilo customizado (já existente em `agendar-aula.php` linhas 165-225)
- Quando "3 Aulas" é selecionado, mostrar opções de posição do intervalo
- Reaproveitar CSS e lógica JavaScript existente

**Função JavaScript Reutilizada:**
- Lógica de cálculo de horários pode usar `calcularHorariosAulas()` (já existe em múltiplos lugares)

---

### **Passo 3: Chamar API de Disponibilidade**

**Ação:**
- Após selecionar tipo de agendamento, a tela automaticamente chama `admin/api/disponibilidade.php`

**Parâmetros Enviados:**

| Parâmetro | Tipo | Obrigatório | Descrição |
|-----------|------|-------------|-----------|
| `aluno_id` | INT | ✅ Sim | ID do aluno |
| `intervalo` | STRING | ✅ Sim | Tipo de agendamento: `'unica'`, `'duas'`, `'tres'` |
| `posicao` | STRING | ⚠️ Opcional | Posição do intervalo: `'antes'` ou `'depois'` (padrão: `'depois'`) |
| `categoria` | STRING | ⚠️ Opcional | Categoria CNH (usa categoria do aluno se não informada) |
| `dias` | INT | ⚠️ Opcional | Janela de dias para buscar (padrão: 14, máximo: 21) |
| `limite` | INT | ⚠️ Opcional | Limite de slots retornados (padrão: 30, máximo: 60) |

**Exemplo de Requisição:**
```
GET /admin/api/disponibilidade.php?aluno_id=10&intervalo=unica&dias=14&limite=30
```

**Status da API Atual:**
- ✅ Já implementada e funcional (`admin/api/disponibilidade.php` linha 1-323)
- ✅ Retorna slots disponíveis com instrutor e veículo já atribuídos
- ✅ Verifica conflitos de instrutor, veículo e aluno

---

### **Passo 4: Exibir Calendário/Lista de Dias com Slots**

**Interface Visual:**

**Opção A - Calendário Mensal:**
- Mostrar calendário com dias do mês atual + próximo mês
- Dias **com slots disponíveis**: destacados, clicáveis
- Dias **sem slots disponíveis**: cinza, desabilitados
- Badge com número de slots disponíveis em cada dia (ex: "3 slots")

**Opção B - Lista de Dias (Mais Simples):**
- Lista vertical de dias (hoje até +14 dias)
- Cada dia mostra: data formatada (ex: "28/01 - Segunda-feira")
- Card/dia **com slots**: fundo branco, borda verde, clicável
- Card/dia **sem slots**: fundo cinza claro, borda cinza, desabilitado
- Mostrar quantidade de slots disponíveis (ex: "5 slots disponíveis")

**Recomendação Inicial:**
- Começar com **Opção B (Lista de Dias)** por ser mais simples de implementar
- Se funcionar bem, evoluir para **Opção A (Calendário)** posteriormente

**Formato de Dados Retornados pela API:**

```json
{
  "success": true,
  "aluno": {
    "id": 10,
    "nome": "João Silva",
    "categoria_cnh": "B"
  },
  "slots": [
    {
      "data": "2025-01-30",
      "hora_inicio": "08:00",
      "hora_fim": "08:50",
      "tipo_agendamento": "unica",
      "total_aulas": 1,
      "instrutor": {
        "id": 5,
        "nome": "Carlos Instrutor"
      },
      "veiculo": {
        "id": 3,
        "modelo": "Fiat Uno",
        "placa": "ABC-1234"
      }
    },
    {
      "data": "2025-01-30",
      "hora_inicio": "08:50",
      "hora_fim": "09:40",
      "tipo_agendamento": "unica",
      "total_aulas": 1,
      "instrutor": {
        "id": 7,
        "nome": "Maria Instrutora"
      },
      "veiculo": {
        "id": 3,
        "modelo": "Fiat Uno",
        "placa": "ABC-1234"
      }
    }
  ],
  "meta": {
    "categoria": "B",
    "dias_analisados": 14,
    "limite_slots": 30
  }
}
```

**Agrupamento dos Slots:**
- Slots devem ser agrupados por **data**
- Cada dia pode ter múltiplos slots
- Ordenar slots dentro de cada dia por `hora_inicio` (crescente)

---

### **Passo 5: Ao Clicar em um Dia, Mostrar Slots Horários**

**Ação:**
- Usuário clica em um dia que possui slots disponíveis
- Expandir/seção mostrará os slots horários daquele dia

**Formato Visual dos Slots:**

**Cada Slot deve mostrar:**
- ⏰ **Horário:** `08:00 - 08:50` (formato: HH:mm)
- 👨‍🏫 **Instrutor:** Nome do instrutor (ex: "Carlos Instrutor")
- 🚗 **Veículo:** Modelo + Placa (ex: "Fiat Uno - ABC-1234")
- 📊 **Duração:** Total de aulas se for bloco (ex: "2 aulas - 1h 40min")

**Estilo dos Cards de Slot:**
- Card clicável (hover: destaque)
- Borda: verde quando disponível
- Ícones: Font Awesome (relógio, instrutor, veículo)
- Layout responsivo (grid ou flex)

**Exemplo de HTML do Slot:**
```html
<div class="slot-card" data-slot-id="0" onclick="selecionarSlot(0)">
    <div class="slot-header">
        <i class="fas fa-clock"></i>
        <span class="slot-horario">08:00 - 08:50</span>
    </div>
    <div class="slot-body">
        <div class="slot-info">
            <i class="fas fa-user-tie"></i>
            <span>Carlos Instrutor</span>
        </div>
        <div class="slot-info">
            <i class="fas fa-car"></i>
            <span>Fiat Uno - ABC-1234</span>
        </div>
        <div class="slot-badge">
            <i class="fas fa-check-circle"></i>
            Disponível
        </div>
    </div>
</div>
```

**Comportamento:**
- Slots são clicáveis
- Ao clicar, slot fica "selecionado" (destaque visual)
- Permite selecionar apenas 1 slot por vez
- Botão "Confirmar agendamento" aparece apenas após seleção

---

### **Passo 6: Exibir Resumo e Botão de Confirmação**

**Resumo (Modal ou Seção Fixa na Tela):**

**Informações Exibidas:**

| Campo | Fonte | Exemplo |
|-------|-------|---------|
| **Aluno** | Dados já carregados | "João Silva (CPF: 123.456.789-00)" |
| **Data** | Slot selecionado | "30/01/2025 - Segunda-feira" |
| **Horário** | Slot selecionado | "08:00 - 08:50" |
| **Instrutor** | Slot selecionado | "Carlos Instrutor" |
| **Veículo** | Slot selecionado | "Fiat Uno - ABC-1234" |
| **Tipo** | Tipo de agendamento selecionado | "1 Aula (50 minutos)" |
| **Total de Aulas** | Slot selecionado | "1 aula" ou "2 aulas" se for bloco |

**Se for Bloco (2 ou 3 aulas):**
- Mostrar detalhamento de cada aula:
  - 1ª Aula: 08:00 - 08:50
  - 2ª Aula: 08:50 - 09:40 (se for 2 aulas)
  - 3ª Aula: 10:10 - 11:00 (se for 3 aulas com intervalo)

**Botão "Confirmar Agendamento":**
- Estilo: Botão primário (azul)
- Ícone: `fas fa-check-circle`
- Estado disabled até slot ser selecionado
- Ao clicar: chama função JavaScript `confirmarAgendamento()`

**Botão "Cancelar":**
- Estilo: Botão secundário (cinza)
- Ícone: `fas fa-times`
- Ao clicar: volta para lista de alunos ou fecha modal

---

### **Passo 7: Confirmar e Chamar API de Criação**

**Ação:**
- Usuário clica em "Confirmar agendamento"
- JavaScript chama `admin/api/agendamento.php` (POST)

**Dados Enviados:**

| Campo | Fonte | Descrição |
|-------|-------|-----------|
| `aluno_id` | Parâmetro da URL | ID do aluno |
| `tipo_aula` | Fixo | `'pratica'` (sempre práticas nesta tela) |
| `instrutor_id` | Slot selecionado | ID do instrutor do slot |
| `veiculo_id` | Slot selecionado | ID do veículo do slot |
| `data_aula` | Slot selecionado | Data no formato `YYYY-MM-DD` |
| `hora_inicio` | Slot selecionado | Hora inicial no formato `HH:MM:SS` |
| `hora_fim` | Slot selecionado | Hora final calculada |
| `tipo_agendamento` | Tipo selecionado | `'unica'`, `'duas'` ou `'tres'` |
| `posicao_intervalo` | Opção selecionada | `'antes'` ou `'depois'` (apenas se `tipo_agendamento === 'tres'`) |
| `duracao` | Fixo | `50` (duração fixa) |
| `observacoes` | Campo opcional | Texto livre (opcional) |

**Formato do Request (JSON ou FormData):**

**Opção A - JSON:**
```json
{
  "aluno_id": 10,
  "tipo_aula": "pratica",
  "instrutor_id": 5,
  "veiculo_id": 3,
  "data_aula": "2025-01-30",
  "hora_inicio": "08:00:00",
  "duracao": 50,
  "tipo_agendamento": "unica",
  "observacoes": ""
}
```

**Opção B - FormData (atual):**
- Enviar via `FormData` (conforme implementação atual em `agendamento.php`)

**Resposta Esperada:**

```json
{
  "success": true,
  "mensagem": "Aula(s) agendada(s) com sucesso",
  "dados": {
    "aluno": "João Silva",
    "instrutor": "Carlos Instrutor",
    "data": "30/01/2025",
    "total_aulas": 1,
    "tipo": "Prática",
    "aulas_criadas": [
      {
        "id": 123,
        "hora_inicio": "08:00:00",
        "hora_fim": "08:50:00"
      }
    ]
  }
}
```

**Tratamento de Erros:**

| Código HTTP | Tipo de Erro | Exemplo de Mensagem |
|-------------|--------------|---------------------|
| 400 | Dados inválidos | "Todos os campos obrigatórios devem ser preenchidos" |
| 404 | Recurso não encontrado | "Aluno não encontrado" ou "Instrutor não encontrado ou inativo" |
| 409 | Conflito | "👨‍🏫 INSTRUTOR INDISPONÍVEL: O instrutor já possui aula agendada no horário..." |
| 500 | Erro do servidor | "Erro ao agendar aula. Tente novamente." |

**Ações Após Sucesso:**
- Mostrar mensagem de sucesso (modal ou alert)
- Exibir resumo do agendamento realizado
- Opção 1: Redirecionar para página do aluno após 3 segundos
- Opção 2: Manter na tela com opção de "Agendar outra aula" ou "Voltar"

---

## 🔌 3. INTEGRAÇÃO COM API DE DISPONIBILIDADE

### 📡 **Como `admin/api/disponibilidade.php` Funciona Hoje**

**Endpoint:** `GET /admin/api/disponibilidade.php`

**Parâmetros Aceitos (atual):**

| Parâmetro | Tipo | Obrigatório | Padrão | Descrição |
|-----------|------|-------------|--------|-----------|
| `aluno_id` | INT | ✅ Sim | - | ID do aluno |
| `categoria` | STRING | ⚠️ Não | Categoria do aluno | Categoria CNH |
| `intervalo` | STRING | ⚠️ Não | `'unica'` | Tipo: `'unica'`, `'duas'`, `'tres'` |
| `posicao` | STRING | ⚠️ Não | `'depois'` | Posição do intervalo: `'antes'` ou `'depois'` |
| `dias` | INT | ⚠️ Não | `14` | Janela de dias (1-21) |
| `limite` | INT | ⚠️ Não | `30` | Limite de slots (1-60) |

**Lógica Atual (linhas 89-137):**

1. **Carrega Aluno:**
   - Query: `SELECT * FROM alunos WHERE id = ?`
   - Valida se aluno existe e tem categoria CNH

2. **Carrega Instrutores e Veículos Elegíveis:**
   - `carregarInstrutoresElegiveis()` (linha 177)
   - `carregarVeiculosElegiveis()` (linha 205)
   - Filtra por categoria CNH do aluno

3. **Gera Slots Baseados em Horários Fixos:**
   - Horários base: `['08:00', '08:50', '09:40', '10:30', '11:20', '12:10', '14:00', '14:50', '15:40', '16:30', '17:20', '18:10', '19:00', '19:50', '20:40']`
   - Para cada horário base, calcula blocos de aulas usando `calcularHorariosAulas()`

4. **Verifica Disponibilidade de Cada Slot:**
   - `slotDisponivel()` (linha 218)
   - Verifica conflitos de:
     - Instrutor (via `possuiConflito()` linha 240)
     - Veículo (via `possuiConflito()` linha 240)
     - Aluno (via `possuiConflito()` linha 240)

5. **Retorna Apenas Slots Disponíveis:**
   - Cada slot já vem com instrutor e veículo atribuídos
   - Slots ordenados por data e horário

**Funções Auxiliares:**

- `normalizarTipoAgendamento()` (linha 161): Normaliza tipo para `'unica'`, `'duas'` ou `'tres'`
- `calcularHorariosAulas()` (linha 260): Calcula horários baseado no tipo de agendamento
- `possuiConflito()` (linha 240): Verifica sobreposição de horários

---

### 📤 **Parâmetros que a Nova Tela Vai Enviar**

**Parâmetros Obrigatórios:**
- ✅ `aluno_id` → Sempre presente na URL (`?aluno_id=X`)
- ✅ `intervalo` → Sempre presente (valor do radio button selecionado)

**Parâmetros Opcionais (com valores padrão):**
- ⚠️ `categoria` → Não enviar (API usa categoria do aluno automaticamente)
- ⚠️ `posicao` → Enviar apenas se `intervalo === 'tres'` (valor do radio button)
- ⚠️ `dias` → Fixo em `14` (pode deixar configurável no futuro)
- ⚠️ `limite` → Fixo em `30` (pode aumentar para 60 se necessário)

**Exemplo de Chamada:**
```javascript
// Quando "1 Aula" estiver selecionado
GET /admin/api/disponibilidade.php?aluno_id=10&intervalo=unica&dias=14&limite=30

// Quando "3 Aulas" estiver selecionado com intervalo "depois"
GET /admin/api/disponibilidade.php?aluno_id=10&intervalo=tres&posicao=depois&dias=14&limite=30
```

**Quando Chamar a API:**
- Ao carregar a tela (se tipo padrão já estiver selecionado)
- Ao mudar o tipo de agendamento (listener no radio button)
- Ao mudar a posição do intervalo (se for 3 aulas)

**Loading State:**
- Mostrar indicador de carregamento enquanto busca slots
- Mensagem: "Buscando horários disponíveis..."
- Se não houver slots: "Nenhum horário disponível nos próximos 14 dias. Tente outro tipo de agendamento."

---

### 📥 **Formato de Resposta Esperado para Montar o Calendário**

**Estrutura de Dados:**

```json
{
  "success": true,
  "aluno": {
    "id": 10,
    "nome": "João Silva",
    "categoria_cnh": "B"
  },
  "slots": [
    {
      "data": "2025-01-30",
      "hora_inicio": "08:00",
      "hora_fim": "08:50",
      "tipo_agendamento": "unica",
      "total_aulas": 1,
      "instrutor": {
        "id": 5,
        "nome": "Carlos Instrutor"
      },
      "veiculo": {
        "id": 3,
        "modelo": "Fiat Uno",
        "placa": "ABC-1234"
      }
    }
  ],
  "meta": {
    "categoria": "B",
    "dias_analisados": 14,
    "limite_slots": 30
  }
}
```

**Processamento no Frontend:**

1. **Agrupar Slots por Data:**
```javascript
const slotsPorData = {};
slots.forEach(slot => {
    const data = slot.data;
    if (!slotsPorData[data]) {
        slotsPorData[data] = [];
    }
    slotsPorData[data].push(slot);
});
```

2. **Ordenar Slots Dentro de Cada Data:**
```javascript
Object.keys(slotsPorData).forEach(data => {
    slotsPorData[data].sort((a, b) => {
        return a.hora_inicio.localeCompare(b.hora_inicio);
    });
});
```

3. **Renderizar Dias:**
- Para cada data com slots: criar card de dia clicável
- Para cada data sem slots: criar card de dia desabilitado

4. **Renderizar Slots ao Expandir Dia:**
- Ao clicar em um dia, mostrar os slots daquele dia
- Slots como cards clicáveis

---

### 🔮 **Onde Será Encaixada a Checagem de Limites/Intervalos/Bloqueios (Futuro)**

**Localização na API:** `admin/api/disponibilidade.php`

**Pontos de Inserção:**

1. **Limite de Aulas/Dia por Instrutor:**
   - **Local:** Após `carregarInstrutoresElegiveis()` (linha 73)
   - **Validação:** Para cada instrutor, contar aulas do dia: `SELECT COUNT(*) FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND status != 'cancelada'`
   - **Regra:** Se instrutor já tem 3 aulas no dia, não incluir nos slots daquele dia

2. **Limite de Aulas/Dia por Aluno:**
   - **Local:** Dentro de `slotDisponivel()` (linha 218), antes de verificar conflitos
   - **Validação:** `SELECT COUNT(*) FROM aulas WHERE aluno_id = ? AND data_aula = ? AND status != 'cancelada'`
   - **Regra:** Se aluno já tem N aulas no dia, não retornar slots para aquele dia

3. **Intervalo Mínimo entre Aulas:**
   - **Local:** Dentro de `possuiConflito()` (linha 240), ajustar lógica de sobreposição
   - **Validação:** Verificar se há aula terminando dentro dos últimos X minutos (ex: 30min) antes do início da nova aula
   - **Query adicional:** `SELECT 1 FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND hora_fim > DATE_SUB(?, INTERVAL 30 MINUTE) AND hora_fim <= ?`
   - **Regra:** Se instrutor tem aula terminando menos de 30min antes, não incluir slot

4. **Bloqueio por Inadimplência:**
   - **Local:** Antes de carregar slots (linha 52), adicionar validação
   - **Validação:** `AgendamentoGuards::verificarSituacaoFinanceira()` (`includes/guards/AgendamentoGuards.php` linha 140)
   - **Regra:** Se aluno tem faturas vencidas, retornar `slots: []` e mensagem: "Aluno bloqueado por inadimplência. Regularize a situação financeira para agendar aulas."

5. **Bloqueio por Faltas:**
   - **Local:** Antes de carregar slots (linha 52), adicionar validação
   - **Validação:** Contar faltas recentes do aluno
   - **Query:** `SELECT COUNT(*) FROM aulas WHERE aluno_id = ? AND status = 'falta' AND data_aula >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)`
   - **Regra:** Se aluno tem 3+ faltas nos últimos 30 dias, retornar `slots: []` e mensagem: "Aluno bloqueado por excesso de faltas."

6. **Bloqueio por Falta de LADV:**
   - **Local:** Antes de carregar slots (linha 52), adicionar validação
   - **Validação:** Verificar se aluno tem LADV válida
   - **Query/Tabela:** A definir (ainda não mapeada no Raio-X)
   - **Regra:** Se aluno não tem LADV válida, retornar `slots: []` e mensagem: "LADV não encontrada. Aluno precisa ter LADV válida para agendar aulas práticas."

**Estrutura de Resposta com Bloqueios:**

```json
{
  "success": true,
  "aluno": {
    "id": 10,
    "nome": "João Silva",
    "categoria_cnh": "B"
  },
  "slots": [],
  "bloqueios": [
    {
      "tipo": "inadimplencia",
      "mensagem": "Aluno possui 2 fatura(s) vencida(s) no valor total de R$ 500,00",
      "acao_requerida": "Regularizar situação financeira"
    }
  ],
  "meta": {
    "categoria": "B",
    "dias_analisados": 0,
    "limite_slots": 30
  }
}
```

---

## 📐 4. REGRAS DE NEGÓCIO PARA ESTA TELA

### ✅ **Regras que Já Existem (Mantidas)**

#### **4.1. Conflitos de Horário**

**Conflito de Instrutor:**
- ✅ **Implementado:** `AgendamentoGuards::verificarConflitoInstrutor()` (`includes/guards/AgendamentoGuards.php` linha 271)
- **Regra:** Instrutor não pode ter duas aulas simultâneas
- **Query:** Verifica sobreposição de horários na tabela `aulas`
- **Aplicação:** Na API `disponibilidade.php`, função `possuiConflito()` (linha 240)

**Conflito de Veículo:**
- ✅ **Implementado:** `AgendamentoGuards::verificarConflitoVeiculo()` (`includes/guards/AgendamentoGuards.php` linha 305)
- **Regra:** Veículo não pode estar em uso em dois lugares ao mesmo tempo
- **Query:** Verifica sobreposição de horários na tabela `aulas`
- **Aplicação:** Na API `disponibilidade.php`, função `possuiConflito()` (linha 240)

**Conflito de Aluno:**
- ✅ **Implementado:** `AgendamentoGuards::verificarConflitoAluno()` (aproximadamente linha 240)
- **Regra:** Aluno não pode ter duas aulas simultâneas
- **Query:** Verifica sobreposição de horários na tabela `aulas`
- **Aplicação:** Na API `disponibilidade.php`, função `possuiConflito()` (linha 240)

#### **4.2. Validação de Veículo Obrigatório**

**Regra:**
- ✅ **Implementado:** Validação em `admin/api/agendamento.php` linha 246
- **Para aulas práticas:** `veiculo_id` é obrigatório
- **Para aulas teóricas:** `veiculo_id` pode ser NULL
- **Aplicação nesta tela:** Esta tela é **somente para aulas práticas**, então veículo sempre obrigatório

#### **4.3. Duração Fixa de 50 Minutos**

**Regra:**
- ✅ **Implementado:** Validação em `admin/api/agendamento.php` linha 251
- **Todas as aulas:** Devem ter exatamente 50 minutos
- **Aplicação:** Campo `duracao` sempre será `50` nesta tela

---

### 🆕 **Regras Novas que Vamos Adicionar (Documentadas, não implementadas ainda)**

#### **4.4. Máximo de Aulas por Dia por Aluno**

**Regra:**
- **Limite:** Máximo de N aulas práticas por dia por aluno (ex: N = 2 ou 3)
- **Validação:**
  - Antes de retornar slots, verificar quantas aulas o aluno já tem agendadas para cada dia
  - Query: `SELECT COUNT(*) FROM aulas WHERE aluno_id = ? AND data_aula = ? AND tipo_aula = 'pratica' AND status != 'cancelada'`
  - Se o aluno já tem N aulas no dia, não retornar slots para aquele dia
- **Local de Implementação:** `admin/api/disponibilidade.php`, dentro do loop de dias (linha 98), antes de processar horários
- **Mensagem ao Usuário:** "Este aluno já possui o limite máximo de aulas agendadas para este dia."

**Exceção:**
- Se o tipo de agendamento for "2 aulas" ou "3 aulas", contar como 1 bloco (não 2 ou 3 aulas separadas)
- Ou seja: se limite é 2 aulas/dia, aluno pode ter 1 bloco de 3 aulas no dia

---

#### **4.5. Máximo de Aulas por Dia por Instrutor**

**Regra:**
- **Limite:** Máximo de M aulas práticas por dia por instrutor (ex: M = 3)
- **Validação:**
  - Antes de incluir instrutor nos slots, verificar quantas aulas ele já tem agendadas para cada dia
  - Query: `SELECT COUNT(*) FROM aulas WHERE instrutor_id = ? AND data_aula = ? AND tipo_aula = 'pratica' AND status != 'cancelada'`
  - Se o instrutor já tem M aulas no dia, não incluí-lo nos slots daquele dia
- **Local de Implementação:** `admin/api/disponibilidade.php`, dentro do loop de instrutores (linha 112), antes de verificar disponibilidade
- **Mensagem ao Usuário:** Não precisa mostrar mensagem específica (instrutor simplesmente não aparece nos slots daquele dia)

**Observação:**
- Se o limite é 3 aulas/dia, mas o instrutor já tem 1 aula agendada no dia, ainda pode aparecer em slots de 2 aulas consecutivas (totalizando 3)

---

#### **4.6. Intervalo Mínimo entre Aulas**

**Regra:**
- **Intervalo:** Mínimo de X minutos entre aulas do mesmo instrutor ou do mesmo veículo (ex: X = 30 minutos)
- **Validação:**
  - Verificar se há aula do instrutor/veículo terminando dentro dos últimos X minutos antes do início da nova aula
  - Query adicional em `possuiConflito()`:
    ```sql
    SELECT 1 FROM aulas 
    WHERE (instrutor_id = ? OR veiculo_id = ?) 
      AND data_aula = ? 
      AND status != 'cancelada'
      AND hora_fim > DATE_SUB(?, INTERVAL 30 MINUTE)
      AND hora_fim <= ?
    ```
  - Se houver conflito de intervalo, não incluir slot
- **Local de Implementação:** `admin/api/disponibilidade.php`, função `possuiConflito()` (linha 240) ou nova função `possuiConflitoIntervalo()`
- **Mensagem ao Usuário:** Não precisa mostrar mensagem específica (slot simplesmente não aparece)

**Exemplo:**
- Instrutor tem aula: 08:00 - 08:50
- Slot candidato: 08:50 - 09:40
- **Resultado:** Slot não aparece (precisa de 30min de intervalo)

---

#### **4.7. Impedir Exibição de Slots se Aluno Estiver Bloqueado**

**Bloqueio por Inadimplência:**

**Regra:**
- Se aluno possui faturas vencidas (mais de X dias), não exibir slots
- **Validação:**
  - Usar `AgendamentoGuards::verificarSituacaoFinanceira()` (`includes/guards/AgendamentoGuards.php` linha 140)
  - Query atual verifica: `SELECT COUNT(*) FROM faturas WHERE aluno_id = ? AND status = 'pendente' AND data_vencimento < CURDATE()`
  - **⚠️ Observação:** Query atual usa tabela `faturas` (que pode não existir). Deve usar `financeiro_faturas` (conforme correção da Fase 2)
- **Local de Implementação:** `admin/api/disponibilidade.php`, logo após carregar aluno (linha 54), antes de buscar slots
- **Resposta:**
  ```json
  {
    "success": true,
    "aluno": {...},
    "slots": [],
    "bloqueios": [
      {
        "tipo": "inadimplencia",
        "mensagem": "Aluno possui 2 fatura(s) vencida(s) no valor total de R$ 500,00",
        "acao_requerida": "Regularizar situação financeira"
      }
    ]
  }
  ```
- **Mensagem na Tela:** Exibir banner/alert explicando o bloqueio e ação necessária

---

**Bloqueio por Faltas:**

**Regra:**
- Se aluno possui 3+ faltas nos últimos 30 dias, não exibir slots
- **Validação:**
  - Query: `SELECT COUNT(*) FROM aulas WHERE aluno_id = ? AND status = 'falta' AND data_aula >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)`
  - Se contagem >= 3, bloquear agendamento
- **Local de Implementação:** `admin/api/disponibilidade.php`, após validação de inadimplência
- **Resposta:** Similar ao bloqueio financeiro, com `tipo: "faltas"`
- **Mensagem:** "Aluno bloqueado por excesso de faltas. Entre em contato com a secretaria."

---

**Bloqueio por Falta de LADV:**

**Regra:**
- Se aluno não possui LADV válida, não exibir slots para aulas práticas
- **Validação:**
  - Query/Tabela: A definir (ainda não mapeada no Raio-X)
  - Possíveis campos: `alunos.ladv_numero`, `alunos.ladv_data_emissao`, `alunos.ladv_valida_ate`, ou tabela separada `documentos_aluno`
- **Local de Implementação:** `admin/api/disponibilidade.php`, após outras validações
- **Resposta:** Similar aos outros bloqueios, com `tipo: "ladv"`
- **Mensagem:** "LADV não encontrada ou vencida. Aluno precisa ter LADV válida para agendar aulas práticas."

---

## 🔄 5. IMPACTO NAS TELAS EXISTENTES

### 📋 **Esta Tela Não Substitui a Agenda Global**

**Agenda Global (`admin/pages/agendamento.php`):**
- ✅ **Mantém sua função:** Visualização de todas as aulas (todos os alunos, instrutores, veículos)
- ✅ **Mantém criação manual:** Permite criar aula escolhendo manualmente data/hora/instrutor/veículo
- ✅ **Uso:** Para visualização geral e agendamentos especiais (ex: quando precisa forçar um horário específico)

**Nova Tela "Agendar Aula (por Aluno)":**
- ✅ **Função específica:** Agendar aula focada em um aluno específico
- ✅ **Interface guiada:** Slots visuais que mostram apenas horários disponíveis
- ✅ **Uso:** Para agendamento rápido e seguro quando o contexto é um aluno específico

**Relação:**
- As duas telas são **complementares**, não substitutas
- Agenda Global: "Onde vejo todas as aulas?"
- Nova Tela: "Quero agendar aula para este aluno específico"

---

### 🔄 **Trechos Atuais que Serão Reaproveitados**

#### **5.1. Lógica de Cálculo de Horários**

**Função:** `calcularHorariosAulas($horaInicio, $tipoAgendamento, $posicaoIntervalo)`

**Localizações:**
- `admin/api/disponibilidade.php` linha 260
- `admin/api/agendamento.php` linha 116

**Reaproveitamento:**
- ✅ **Backend:** Função já existe e será usada pela API `disponibilidade.php`
- ✅ **Frontend:** Pode usar a mesma lógica JavaScript para calcular horários de blocos selecionados (ex: mostrar "3 aulas: 08:00-08:50, 08:50-09:40, 10:10-11:00")
- **Ação:** Reaproveitar sem alteração na API, apenas usar na nova tela

---

#### **5.2. API de Disponibilidade**

**Endpoint:** `admin/api/disponibilidade.php`

**Reaproveitamento:**
- ✅ **Totalmente funcional:** API já retorna slots disponíveis
- ⚠️ **Ajustes futuros necessários:** Adicionar validações de limites/intervalos/bloqueios (conforme Seção 4)
- **Ação:** Usar API atual como está, adicionar validações depois

---

#### **5.3. Validações de Conflito em AgendamentoGuards**

**Classe:** `includes/guards/AgendamentoGuards.php`

**Métodos Reaproveitados:**
- ✅ `verificarConflitos()` (linha 191): Verifica conflitos de aluno, instrutor e veículo
- ✅ `verificarConflitoInstrutor()` (linha 271): Validação específica de instrutor
- ✅ `verificarConflitoVeiculo()` (linha 305): Validação específica de veículo
- ✅ `verificarConflitoAluno()` (~linha 240): Validação específica de aluno
- ✅ `verificarSituacaoFinanceira()` (linha 140): Validação de inadimplência

**Reaproveitamento:**
- ✅ **API de Disponibilidade:** Já usa lógica similar em `possuiConflito()` (linha 240)
- ✅ **API de Agendamento:** Usa `AgendamentoGuards` para validação final antes de criar aula
- **Ação:** Manter validações atuais, adicionar novas regras (limites, intervalos, bloqueios) na API de disponibilidade

---

#### **5.4. API de Criação de Aula**

**Endpoint:** `admin/api/agendamento.php` (POST)

**Função:** `criarAula()` (linha 201)

**Reaproveitamento:**
- ✅ **Totalmente funcional:** API já cria aulas corretamente
- ✅ **Suporta múltiplas aulas:** Já cria blocos de 2 ou 3 aulas quando necessário
- ✅ **Validações:** Já usa `AgendamentoGuards` para validar antes de criar
- **Ação:** Usar API atual sem alterações na nova tela

---

#### **5.5. Integração com Modal do Aluno**

**Arquivo:** `admin/pages/alunos.php`

**Função JavaScript:** `agendarAula(id)` (linha 4854)

**Reaproveitamento:**
- ✅ **Função existente:** Já redireciona para `index.php?page=agendar-aula&aluno_id=X`
- **Ação:** Manter redirecionamento atual (não precisa alterar)

---

## ✅ 6. CHECKLIST PARA A PRÓXIMA ETAPA

### 📋 **Itens que Precisarão ser Implementados Após Aprovação do Plano**

#### **🔧 6.1. Ajustes na API de Disponibilidade**

- [ ] **Adicionar validação de limite de aulas/dia por aluno**
  - Local: `admin/api/disponibilidade.php`, dentro do loop de dias (linha 98)
  - Query: Contar aulas do aluno no dia
  - Se limite excedido, não incluir slots daquele dia

- [ ] **Adicionar validação de limite de aulas/dia por instrutor**
  - Local: `admin/api/disponibilidade.php`, dentro do loop de instrutores (linha 112)
  - Query: Contar aulas do instrutor no dia
  - Se limite excedido, não incluir instrutor nos slots daquele dia

- [ ] **Adicionar validação de intervalo mínimo entre aulas**
  - Local: `admin/api/disponibilidade.php`, função `possuiConflito()` (linha 240)
  - Query adicional: Verificar aulas terminando dentro dos últimos X minutos
  - Se houver conflito de intervalo, não incluir slot

- [ ] **Adicionar verificação de bloqueio por inadimplência**
  - Local: `admin/api/disponibilidade.php`, após carregar aluno (linha 54)
  - Usar: `AgendamentoGuards::verificarSituacaoFinanceira()` ou query direta em `financeiro_faturas`
  - Se bloqueado, retornar `slots: []` e `bloqueios: [...]`

- [ ] **Adicionar verificação de bloqueio por faltas**
  - Local: `admin/api/disponibilidade.php`, após verificação de inadimplência
  - Query: Contar faltas do aluno nos últimos 30 dias
  - Se 3+ faltas, retornar `slots: []` e `bloqueios: [...]`

- [ ] **Adicionar verificação de bloqueio por falta de LADV**
  - Local: `admin/api/disponibilidade.php`, após outras validações
  - Query: Verificar LADV válida do aluno (tabela/campos a definir)
  - Se sem LADV, retornar `slots: []` e `bloqueios: [...]`

- [ ] **Estrutura de resposta com bloqueios**
  - Adicionar campo `bloqueios` na resposta JSON quando houver bloqueios
  - Formato: `[{tipo, mensagem, acao_requerida}]`

---

#### **🎨 6.2. Criação/Ajuste da Página**

**Arquivo:** `admin/pages/agendar-aula.php`

**Tarefas:**

- [ ] **Redesenhar layout da página:**
  - Manter header com informações do aluno (já existe)
  - Adicionar seção de seleção de tipo de agendamento (já existe, manter)
  - **NOVO:** Adicionar seção de calendário/lista de dias com slots

- [ ] **Implementar seção de dias:**
  - Lista vertical de dias (hoje até +14 dias)
  - Cards de dias com/sem slots disponíveis
  - Badge com quantidade de slots por dia

- [ ] **Implementar expansão de dias:**
  - Ao clicar em um dia, expandir e mostrar slots horários
  - Cards de slots com informações (horário, instrutor, veículo)
  - Estados: normal, hover, selecionado

- [ ] **Implementar seção de resumo:**
  - Exibir resumo do slot selecionado
  - Informações: aluno, data, horário, instrutor, veículo, tipo
  - Botão "Confirmar agendamento"

- [ ] **Implementar JavaScript:**
  - Função para chamar API `disponibilidade.php`
  - Função para processar resposta e renderizar dias/slots
  - Função para selecionar slot
  - Função para confirmar agendamento (chamar `agendamento.php` POST)

- [ ] **Implementar tratamento de erros:**
  - Exibir mensagens de erro de forma amigável
  - Tratar bloqueios (exibir banner explicativo)
  - Loading states durante requisições

- [ ] **Implementar CSS:**
  - Estilos para cards de dias
  - Estilos para cards de slots
  - Estados visuais (hover, selecionado, desabilitado)
  - Responsividade mobile

---

#### **🔗 6.3. Integração do Botão "Agendar Aula"**

**Arquivo:** `admin/pages/alunos.php`

**Tarefas:**

- [ ] **Verificar função atual:**
  - Função `agendarAula(id)` (linha 4854) já redireciona corretamente
  - Não precisa alteração, apenas garantir que funciona

- [ ] **Testar integração:**
  - Clicar em "Agendar Aula" no modal do aluno
  - Verificar se redireciona para `index.php?page=agendar-aula&aluno_id=X`
  - Verificar se dados do aluno são carregados corretamente

---

#### **📊 6.4. Configurações de Limites/Intervalos**

**Arquivo:** `admin/pages/configuracoes-bloqueios.php` (a criar) ou `admin/migrations/` (tabela de configurações)

**Tarefas:**

- [ ] **Definir tabela de configurações de bloqueios:**
  - Tabela: `configuracoes_bloqueios` ou usar `financeiro_configuracoes`
  - Campos:
    - `max_aulas_dia_aluno` → INT (ex: 2 ou 3)
    - `max_aulas_dia_instrutor` → INT (ex: 3)
    - `intervalo_minimo_minutos` → INT (ex: 30)
    - `dias_inadimplencia` → INT (ex: 15) - quantos dias vencido bloqueia
    - `max_faltas_30_dias` → INT (ex: 3)

- [ ] **Criar página de configurações:**
  - Permitir admin configurar esses valores
  - Valores padrão se não configurados

- [ ] **Usar configurações na API:**
  - Carregar valores da tabela de configurações
  - Usar valores ao validar limites/intervalos/bloqueios

---

#### **🧪 6.5. Testes**

- [ ] **Testar fluxo completo:**
  - Abrir tela com aluno específico
  - Selecionar tipo de agendamento
  - Verificar se slots aparecem
  - Selecionar slot
  - Confirmar agendamento
  - Verificar se aula foi criada corretamente

- [ ] **Testar validações:**
  - Tentar agendar em horário ocupado (não deve aparecer no slot)
  - Tentar agendar quando aluno está bloqueado (deve mostrar mensagem)
  - Tentar agendar quando instrutor já tem 3 aulas no dia (não deve aparecer nos slots)

- [ ] **Testar blocos:**
  - Agendar 2 aulas consecutivas
  - Agendar 3 aulas com intervalo
  - Verificar se todas as aulas são criadas corretamente

---

## 📝 7. ESTRUTURA DE ARQUIVOS E FUNÇÕES

### 📁 **Arquivos Envolvidos**

| Arquivo | Função | Status |
|---------|--------|--------|
| `admin/pages/agendar-aula.php` | Tela principal (será redesenhada) | ⚠️ Ajustar |
| `admin/api/disponibilidade.php` | API de slots disponíveis | ⚠️ Adicionar validações |
| `admin/api/agendamento.php` | API de criação de aula | ✅ Reaproveitar |
| `admin/pages/alunos.php` | Botão "Agendar Aula" | ✅ Manter |
| `includes/guards/AgendamentoGuards.php` | Validações de negócio | ✅ Reaproveitar |
| `admin/assets/js/agendar-aula.js` | JavaScript da tela (a criar) | 🆕 Criar |
| `admin/assets/css/agendar-aula.css` | Estilos da tela (a criar) | 🆕 Criar |

---

### 🔧 **Funções JavaScript Principais**

**Arquivo:** `admin/assets/js/agendar-aula.js` (a criar)

| Função | Descrição | Parâmetros | Retorno |
|--------|-----------|------------|---------|
| `carregarSlotsDisponiveis()` | Chama API de disponibilidade | `alunoId`, `intervalo`, `posicao` | Promise com slots |
| `renderizarDiasComSlots(slots)` | Renderiza cards de dias | Array de slots | HTML |
| `expandirDia(data)` | Expande dia e mostra slots | `data` (YYYY-MM-DD) | void |
| `renderizarSlotsDoDia(slots, data)` | Renderiza cards de slots | Array de slots, data | HTML |
| `selecionarSlot(slotId)` | Marca slot como selecionado | `slotId` (índice) | void |
| `exibirResumo(slot)` | Exibe resumo do slot selecionado | Objeto slot | HTML |
| `confirmarAgendamento()` | Chama API de criação | - | Promise |
| `calcularHorariosBloco(horaInicio, tipo, posicao)` | Calcula horários do bloco | Hora, tipo, posição | Array de horários |

---

### 🔧 **Funções PHP Principais**

**Arquivo:** `admin/api/disponibilidade.php`

| Função | Descrição | Status |
|--------|-----------|--------|
| `carregarInstrutoresElegiveis()` | Filtra instrutores por categoria | ✅ Existente |
| `carregarVeiculosElegiveis()` | Filtra veículos por categoria | ✅ Existente |
| `calcularHorariosAulas()` | Calcula horários baseado no tipo | ✅ Existente |
| `slotDisponivel()` | Verifica se slot está disponível | ✅ Existente |
| `possuiConflito()` | Verifica conflito de horário | ✅ Existente |
| `verificarLimiteAulasDiaAluno()` | Valida limite do aluno | 🆕 Criar |
| `verificarLimiteAulasDiaInstrutor()` | Valida limite do instrutor | 🆕 Criar |
| `verificarIntervaloMinimo()` | Valida intervalo entre aulas | 🆕 Criar |
| `verificarBloqueios()` | Verifica todos os bloqueios | 🆕 Criar |

---

## 📊 8. MOCKUP/DESCRIÇÃO DA INTERFACE

### 📐 **Layout Proposto**

```
┌─────────────────────────────────────────────────────────────┐
│  [← Voltar]  Agendar Aula - João Silva                     │
├─────────────────────────────────────────────────────────────┤
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  👤 João Silva                                       │  │
│  │  CPF: 123.456.789-00  |  CFC: Bom Conselho  |  Ativo│  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Tipo de Agendamento:                                │  │
│  │  ( ) 1 Aula (50 min)                                 │  │
│  │  ( ) 2 Aulas (1h 40min)                              │  │
│  │  (•) 3 Aulas (2h 30min)                              │  │
│  │                                                       │  │
│  │  Posição do Intervalo (para 3 aulas):                │  │
│  │  (•) 2 consecutivas + intervalo + 1 aula            │  │
│  │  ( ) 1 aula + intervalo + 2 consecutivas            │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  📅 Selecione um dia disponível:                     │  │
│  │                                                       │  │
│  │  [28/01 - Segunda]  [Sem slots]                     │  │
│  │  [29/01 - Terça]    [3 slots] ✅                     │  │
│  │  [30/01 - Quarta]   [5 slots] ✅  ← Expandido       │  │
│  │    ┌───────────────────────────────────────────┐    │  │
│  │    │  ⏰ 08:00 - 08:50                         │    │  │
│  │    │  👨‍🏫 Carlos Instrutor                      │    │  │
│  │    │  🚗 Fiat Uno - ABC-1234                   │    │  │
│  │    │  [✅ Selecionado]                         │    │  │
│  │    └───────────────────────────────────────────┘    │  │
│  │    ┌───────────────────────────────────────────┐    │  │
│  │    │  ⏰ 08:50 - 09:40                         │    │  │
│  │    │  👨‍🏫 Maria Instrutora                     │    │  │
│  │    │  🚗 Fiat Uno - ABC-1234                   │    │  │
│  │    └───────────────────────────────────────────┘    │  │
│  │  [31/01 - Quinta]   [2 slots] ✅                    │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  📋 Resumo do Agendamento:                           │  │
│  │                                                       │  │
│  │  Aluno: João Silva                                   │  │
│  │  Data: 30/01/2025 - Quarta-feira                    │  │
│  │  Horário: 08:00 - 08:50                             │  │
│  │  Instrutor: Carlos Instrutor                        │  │
│  │  Veículo: Fiat Uno - ABC-1234                       │  │
│  │  Tipo: 1 Aula (50 minutos)                          │  │
│  │                                                       │  │
│  │  [✅ Confirmar Agendamento]  [❌ Cancelar]          │  │
│  └──────────────────────────────────────────────────────┘  │
│                                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🎨 9. ESTADOS VISUAIS

### 📊 **Estados dos Dias**

| Estado | Visual | Ação |
|--------|--------|------|
| **Com Slots** | Card branco, borda verde, badge "N slots", cursor pointer | Clicável |
| **Sem Slots** | Card cinza claro, borda cinza, texto "Sem slots", cursor not-allowed | Não clicável |
| **Expandido** | Card branco, borda azul, seta para baixo, slots visíveis | Clicável (para recolher) |
| **Loading** | Card branco, spinner, texto "Carregando..." | Não clicável |

---

### 📊 **Estados dos Slots**

| Estado | Visual | Ação |
|--------|--------|------|
| **Disponível** | Card branco, borda verde clara, ícones coloridos, hover: borda verde escura | Clicável |
| **Selecionado** | Card azul claro, borda azul escura, checkmark verde, ícones destacados | Clicável (para deselecionar) |
| **Loading** | Card branco, spinner, texto "Verificando..." | Não clicável |

---

### 📊 **Estados de Bloqueio**

| Tipo de Bloqueio | Visual | Ação |
|------------------|--------|------|
| **Inadimplência** | Banner vermelho, ícone de alerta, mensagem explicativa | Não mostra slots |
| **Faltas** | Banner laranja, ícone de alerta, mensagem explicativa | Não mostra slots |
| **Sem LADV** | Banner amarelo, ícone de alerta, mensagem explicativa | Não mostra slots |

---

## 📋 10. EXEMPLOS DE USO

### 🎯 **Exemplo 1: Agendamento Simples (1 Aula)**

1. Usuário clica em "Agendar Aula" no modal do aluno
2. Tela carrega com aluno "João Silva" selecionado
3. Tipo padrão "1 Aula" já está selecionado
4. Tela automaticamente chama API: `GET /admin/api/disponibilidade.php?aluno_id=10&intervalo=unica&dias=14&limite=30`
5. API retorna 15 slots disponíveis nos próximos 14 dias
6. Tela renderiza 8 dias com slots (outros 6 dias não têm slots)
7. Usuário clica no dia "30/01 - Quarta-feira" (tem 5 slots)
8. Tela expande e mostra os 5 slots:
   - 08:00 - 08:50 | Carlos Instrutor | Fiat Uno - ABC-1234
   - 08:50 - 09:40 | Maria Instrutora | Fiat Uno - ABC-1234
   - 14:00 - 14:50 | Carlos Instrutor | Fiat Uno - ABC-1234
   - 15:40 - 16:30 | João Instrutor | Fiat Palio - XYZ-5678
   - 17:20 - 18:10 | Maria Instrutora | Fiat Uno - ABC-1234
9. Usuário clica no primeiro slot (08:00 - 08:50)
10. Slot fica selecionado (destaque visual)
11. Resumo aparece mostrando: João Silva, 30/01/2025, 08:00-08:50, Carlos Instrutor, Fiat Uno - ABC-1234
12. Usuário clica em "Confirmar agendamento"
13. JavaScript chama: `POST /admin/api/agendamento.php` com dados do slot
14. API cria aula e retorna sucesso
15. Tela exibe mensagem: "Aula agendada com sucesso!"
16. Após 3 segundos, redireciona para página de alunos

---

### 🎯 **Exemplo 2: Agendamento Bloco (2 Aulas Consecutivas)**

1. Usuário seleciona "2 Aulas" no tipo de agendamento
2. Tela automaticamente chama API: `GET /admin/api/disponibilidade.php?aluno_id=10&intervalo=duas&dias=14&limite=30`
3. API retorna slots de 2 aulas consecutivas (blocos de 100 minutos)
4. Slots mostram: "08:00 - 09:40 (2 aulas - 1h 40min)"
5. Usuário seleciona slot e confirma
6. API cria 2 aulas:
   - Aula 1: 08:00 - 08:50
   - Aula 2: 08:50 - 09:40
7. Ambas com mesmo instrutor e veículo

---

### 🎯 **Exemplo 3: Aluno Bloqueado por Inadimplência**

1. Usuário tenta agendar aula para aluno com faturas vencidas
2. Tela chama API de disponibilidade
3. API verifica bloqueio financeiro e retorna:
   ```json
   {
     "success": true,
     "aluno": {...},
     "slots": [],
     "bloqueios": [
       {
         "tipo": "inadimplencia",
         "mensagem": "Aluno possui 2 fatura(s) vencida(s) no valor total de R$ 500,00",
         "acao_requerida": "Regularizar situação financeira"
       }
     ]
   }
   ```
4. Tela exibe banner vermelho:
   ```
   ⚠️ BLOQUEIO POR INADIMPLÊNCIA
   Aluno possui 2 fatura(s) vencida(s) no valor total de R$ 500,00
   Ação necessária: Regularizar situação financeira
   ```
5. Não exibe slots (área de dias fica vazia ou mostra mensagem)

---

## ⚠️ 11. LIMITAÇÕES E CONSIDERAÇÕES

### ⚠️ **Limitações Técnicas Atuais**

1. **Horários Fixos:**
   - API `disponibilidade.php` usa horários base fixos: `['08:00', '08:50', '09:40', ...]`
   - Não permite horários customizados (ex: 08:15, 09:25)
   - **Ação futura:** Considerar permitir horários customizados se necessário

2. **Categoria CNH:**
   - Slots são filtrados pela categoria CNH do aluno
   - Se aluno não tem categoria, não aparecem slots
   - **Ação:** Validar categoria antes de mostrar tela

3. **Janela de Dias:**
   - Atualmente busca apenas 14 dias à frente
   - Máximo configurável: 21 dias
   - **Ação futura:** Permitir usuário escolher janela maior se necessário

4. **Limite de Slots:**
   - Atualmente retorna no máximo 30 slots
   - Máximo configurável: 60 slots
   - **Ação futura:** Paginação se necessário

---

### ⚠️ **Considerações de UX**

1. **Feedback Visual:**
   - Slots devem ter feedback claro de disponibilidade/seleção
   - Loading states devem ser claros
   - Mensagens de erro devem ser amigáveis

2. **Responsividade:**
   - Tela deve funcionar bem em mobile
   - Cards de dias/slots devem empilhar verticalmente em telas pequenas
   - Botões devem ter tamanho adequado para toque

3. **Performance:**
   - API pode demorar para retornar se muitos slots disponíveis
   - Considerar paginação ou lazy loading de dias
   - Cache de slots pode não ser viável (mudam muito rápido)

---

## 📊 12. DIAGRAMA DE FLUXO

```
┌─────────────────┐
│ Usuário clica   │
│ "Agendar Aula"  │
└────────┬────────┘
         │
         ▼
┌─────────────────────────┐
│ Carrega tela com aluno  │
│ ?page=agendar-aula&     │
│ aluno_id=X              │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Seleciona tipo de       │
│ agendamento (unica/     │
│ duas/tres)              │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Chama API:              │
│ /api/disponibilidade.php│
│ ?aluno_id=X&intervalo=  │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ API verifica:           │
│ - Instrutor elegível?   │
│ - Veículo elegível?     │
│ - Conflitos horário?    │
│ - Bloqueios? (futuro)   │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Retorna slots           │
│ disponíveis             │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Tela renderiza:         │
│ - Dias com slots        │
│ - Dias sem slots        │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Usuário clica em dia    │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Tela expande e mostra   │
│ slots horários do dia   │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Usuário seleciona slot  │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Tela mostra resumo:     │
│ - Data                  │
│ - Horário               │
│ - Instrutor             │
│ - Veículo               │
│ - Tipo                  │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Usuário clica           │
│ "Confirmar"             │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Chama API:              │
│ POST /api/agendamento.php│
│ com dados do slot       │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ API valida e cria       │
│ aula(s) na tabela       │
│ `aulas`                 │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Retorna sucesso         │
└────────┬────────────────┘
         │
         ▼
┌─────────────────────────┐
│ Tela mostra mensagem    │
│ de sucesso e            │
│ redireciona             │
└─────────────────────────┘
```

---

## 📝 13. NOTAS DE IMPLEMENTAÇÃO FUTURA

### 🔮 **Melhorias Futuras (Fora do Escopo Inicial)**

1. **Agendamento Recorrente:**
   - Permitir agendar mesma aula toda semana por X semanas
   - Exemplo: "Agendar 2 aulas toda terça às 08:00 por 4 semanas"

2. **Filtros Adicionais:**
   - Filtrar por instrutor específico
   - Filtrar por veículo específico
   - Filtrar por período do dia (manhã, tarde, noite)

3. **Visualização de Agenda do Aluno:**
   - Mostrar aulas já agendadas do aluno na mesma tela
   - Prevenir agendamento em dias com muitas aulas

4. **Sugestões Inteligentes:**
   - Sugerir horários baseados em padrões do aluno
   - Sugerir instrutor baseado em histórico

5. **Integração com Notificações:**
   - Notificar aluno quando aula for agendada
   - Notificar instrutor quando nova aula for atribuída

---

## ✅ 14. VALIDAÇÃO DO PLANO

### 📋 **Checklist de Validação**

- [x] ✅ Plano baseado no Raio-X da Agenda
- [x] ✅ Usa APIs existentes como base
- [x] ✅ Define estrutura de dados clara
- [x] ✅ Descreve fluxo de usuário completo
- [x] ✅ Identifica regras de negócio atuais e futuras
- [x] ✅ Explica impacto nas telas existentes
- [x] ✅ Lista tarefas para próxima etapa
- [x] ✅ Não altera código nesta fase

---

## 📚 15. REFERÊNCIAS

### 📄 **Documentos Relacionados**

- `admin/pages/_RAIO-X-AGENDA-AGENDAMENTOS.md` → Base técnica do sistema atual
- `admin/pages/_FASE-3-ACADEMICO-E-AGENDA.md` → Contexto acadêmico e agenda
- `admin/pages/_FASE-4-ARQUITETURA-GERAL.md` → Arquitetura geral do sistema

### 📄 **Arquivos de Código Referenciados**

- `admin/api/disponibilidade.php` → API de slots disponíveis
- `admin/api/agendamento.php` → API de criação de aulas
- `admin/pages/agendar-aula.php` → Tela atual (será redesenhada)
- `admin/pages/agendamento.php` → Agenda global (mantida)
- `includes/guards/AgendamentoGuards.php` → Validações de negócio

---

**Fim do Plano de Especificação**

---

**Próximos Passos:**
1. Revisar e aprovar este plano
2. Implementar ajustes na API de disponibilidade (validações)
3. Redesenhar página `agendar-aula.php` com slots visuais
4. Testar fluxo completo
5. Integrar com sistema existente

