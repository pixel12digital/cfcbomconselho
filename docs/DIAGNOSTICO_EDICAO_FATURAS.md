# DIAGNÓSTICO COMPLETO - Funcionalidades de Edição de Faturas

**Data:** 2025-11-19  
**Página:** `admin/index.php?page=financeiro-faturas`  
**Card:** "Lista de Faturas"

---

## 1. RAI-X DAS AÇÕES ATUAIS

### 1.1 Ícone de Visualizar (👁️)

**Função JS:**
- Nome: `visualizarFatura(id)`
- Localização: `admin/pages/financeiro-faturas.php` (linha 2226)
- **Status:** ⚠️ **NÃO IMPLEMENTADA** (apenas placeholder)

```javascript
function visualizarFatura(id) {
    // Implementar visualização da fatura
    alert('Visualização da fatura ' + id + ' será implementada em breve.');
}
```

**Endpoint/URL:**
- ❌ **Nenhum endpoint específico chamado**
- A função apenas exibe um `alert()` temporário

**Campos alterados:**
- ❌ Nenhum (função não implementada)

---

### 1.2 Ícone de Confirmar/Baixar (✅)

**Função JS:**
- Nome: `marcarComoPaga(id)`
- Localização: `admin/pages/financeiro-faturas.php` (linha 2231)
- **Status:** ⚠️ **NÃO IMPLEMENTADA** (apenas placeholder)

```javascript
function marcarComoPaga(id) {
    if (confirm('Deseja marcar esta fatura como paga?')) {
        // Implementar marcação como paga
        alert('Marcação como paga será implementada em breve.');
    }
}
```

**Endpoint/URL:**
- ❌ **Nenhum endpoint específico chamado**
- A função apenas exibe um `alert()` temporário

**Campos que deveriam ser alterados (quando implementado):**
- `status`: de `'aberta'` para `'paga'`
- Possivelmente `data_pagamento` (se existir na tabela)

**Nota:** Existe uma API alternativa em `admin/api/financeiro-faturas.php` com método `PUT` que permite atualizar `status`, mas não está sendo usada pela função JS atual.

---

### 1.3 Ícone de Cancelar (❌)

**Função JS:**
- Nome: `cancelarFatura(id)`
- Localização: `admin/pages/financeiro-faturas.php` (linha 2238)
- **Status:** ⚠️ **NÃO IMPLEMENTADA** (apenas placeholder)

```javascript
function cancelarFatura(id) {
    if (confirm('Deseja cancelar esta fatura?')) {
        // Implementar cancelamento
        alert('Cancelamento será implementado em breve.');
    }
}
```

**Endpoint/URL:**
- ❌ **Nenhum endpoint específico chamado**
- A função apenas exibe um `alert()` temporário

**Campos que deveriam ser alterados (quando implementado):**
- `status`: de `'aberta'` para `'cancelada'`

**Nota:** Existe uma API em `admin/api/financeiro-faturas.php` com método `DELETE` que:
- Verifica se a fatura existe
- Verifica se o status é `'aberta'` (apenas faturas abertas podem ser excluídas)
- Exclui a fatura do banco
- Atualiza status de inadimplência do aluno

---

## 2. FUNCIONALIDADES DE EDIÇÃO EXISTENTES

### 2.1 API de Atualização (PUT) - Backend Existe, Frontend Não Usa

**Endpoint:**
- Arquivo: `admin/api/financeiro-faturas.php`
- Método: `PUT`
- Rota: `admin/api/financeiro-faturas.php?id={fatura_id}`

**Campos permitidos para atualização:**
```php
$allowedFields = [
    'titulo', 
    'valor_total', 
    'status', 
    'data_vencimento', 
    'vencimento',  // Campo alternativo (compatibilidade)
    'forma_pagamento', 
    'observacoes'
];
```

**Lógica de atualização:**
- Aceita `data_vencimento` e mantém `vencimento` em sync (compatibilidade)
- Atualiza `atualizado_em` automaticamente
- Atualiza status de inadimplência do aluno após a mudança

**Status:**
- ✅ **Backend implementado e funcional**
- ❌ **Frontend não possui interface para usar esta API**

---

### 2.2 Busca de Fatura Específica (GET) - Existe

**Endpoint:**
- Arquivo: `admin/api/financeiro-faturas.php`
- Método: `GET`
- Rota: `admin/api/financeiro-faturas.php?id={fatura_id}`

**Retorna:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "aluno_id": 112,
        "titulo": "CNH - Entrada",
        "valor": 500.00,
        "valor_total": 500.00,
        "data_vencimento": "2025-12-19",
        "status": "aberta",
        "forma_pagamento": "boleto",
        "observacoes": null,
        "aluno_nome": "Nome do Aluno",
        "cpf": "123.456.789-00",
        "categoria_cnh": "AB",
        "tipo_servico": "primeira_habilitacao"
    }
}
```

**Status:**
- ✅ **Backend implementado e funcional**
- ❌ **Frontend não usa para carregar dados em formulário de edição**

---

### 2.3 Formulários de Edição

**Busca realizada:**
- ❌ **Nenhum formulário encontrado** que carregue dados de fatura existente para edição
- ❌ **Nenhuma rota** como `action=edit`, `action=update`, `action=atualizar` em `admin/index.php`
- ❌ **Nenhum modal** de edição de fatura
- ❌ **Nenhum botão/ícone** de "editar" na coluna Ações

**Comparação com outros módulos:**
- ✅ **Alunos:** Possui modal de edição que carrega dados existentes
- ✅ **Instrutores:** Possui função `editarInstrutor(id)` que busca dados e preenche formulário
- ✅ **Aulas:** Possui página `editar-aula.php` que carrega dados para edição
- ❌ **Faturas:** Não possui nenhuma interface de edição

---

## 3. ESTRUTURA DE DADOS

### 3.1 Tabela `financeiro_faturas`

**Colunas relacionadas a vencimento e valor:**
```sql
- data_vencimento DATE NOT NULL          -- Campo oficial (usado em páginas)
- vencimento DATE DEFAULT NULL           -- Campo alternativo (API - DEPRECATED)
- valor DECIMAL(10, 2) NOT NULL          -- Valor da parcela individual
- valor_total DECIMAL(10, 2) NOT NULL    -- Valor total da fatura
- titulo VARCHAR(200) NOT NULL           -- Título/descrição da fatura
- descricao TEXT DEFAULT NULL            -- Descrição adicional (opcional)
```

**Colunas de controle:**
```sql
- status ENUM('aberta', 'paga', 'vencida', 'parcial', 'cancelada')
- forma_pagamento ENUM('avista', 'boleto', 'pix', 'cartao', 'transferencia', 'dinheiro')
- parcelas INT DEFAULT 1                 -- Número de parcelas (1 = fatura única)
- observacoes TEXT DEFAULT NULL
- atualizado_em TIMESTAMP                -- Atualizado automaticamente
```

**Estrutura completa:**
- Arquivo: `admin/migrations/005-create-financeiro-faturas-structure.sql`
- Total de colunas: ~15 campos principais

---

### 3.2 Representação de Parcelas

**Modelo atual:**
- ✅ **1 registro por parcela** na tabela `financeiro_faturas`
- Cada parcela (incluindo entrada) é um registro separado
- Campo `parcelas` indica quantas parcelas fazem parte do conjunto (mas não há link explícito entre elas)

**Exemplo de parcelamento:**
```
Fatura 1: titulo = "CNH - Entrada", valor = 500.00, data_vencimento = "2025-12-19", parcelas = 3
Fatura 2: titulo = "CNH - 1ª parcela", valor = 750.00, data_vencimento = "2026-01-19", parcelas = 3
Fatura 3: titulo = "CNH - 2ª parcela", valor = 750.00, data_vencimento = "2026-02-19", parcelas = 3
```

**Nota:** Não existe tabela auxiliar de parcelamento. O relacionamento entre parcelas é implícito (mesmo `aluno_id`, mesmo padrão de `titulo`, mesmo valor de `parcelas`).

---

### 3.3 Histórico de Alterações

**Busca realizada:**
- ❌ **Nenhuma tabela** de histórico de alterações de faturas
- ❌ **Nenhum campo** de log/auditoria específico para alterações de vencimento/valor
- ✅ **Campo `atualizado_em`** existe, mas apenas registra timestamp (não quem alterou ou o que foi alterado)

**Comparação com outros módulos:**
- ✅ **Turmas Teóricas:** Possui tabela `turma_log` com histórico completo de alterações
- ❌ **Faturas:** Não possui sistema de histórico

---

## 4. CONCLUSÃO OBJETIVA

### 4.1 Existe funcionalidade de edição?

**Resposta:** ❌ **NÃO EXISTE** interface de edição de fatura/parcela no frontend.

**O que existe:**
- ✅ Backend API (`PUT`) que permite atualizar campos via JSON
- ✅ Backend API (`GET`) que permite buscar dados de uma fatura
- ❌ Frontend não possui formulário/modal de edição
- ❌ Frontend não possui botão/ícone de "editar" na coluna Ações
- ❌ As funções JS (`visualizarFatura`, `marcarComoPaga`, `cancelarFatura`) são apenas placeholders

**O que o usuário pode fazer hoje:**
1. ✅ **Criar fatura** (modal "Nova Fatura" - funcional)
2. ❌ **Visualizar fatura** (função não implementada)
3. ❌ **Dar baixa/receber** (função não implementada)
4. ❌ **Cancelar** (função não implementada)
5. ❌ **Editar** (não existe)

---

### 4.2 Como acessar edição (se existisse)?

**Resposta:** Não existe acesso, pois a funcionalidade não foi implementada.

**O que seria necessário:**
- Botão/ícone de "editar" (lápis) na coluna Ações
- Modal ou formulário que carregue dados da fatura via `GET /api/financeiro-faturas.php?id={id}`
- Formulário que envie atualizações via `PUT /api/financeiro-faturas.php?id={id}`

---

### 4.3 Sugestão de implementação

**Caminho mais simples para editar vencimento de uma parcela:**

#### Opção 1: Modal de Edição Rápida (Recomendado)
1. **Adicionar ícone de edição** (lápis) na coluna Ações
2. **Criar modal simples** (similar ao modal "Nova Fatura", mas em modo edição)
3. **Campos editáveis:**
   - Data de Vencimento (`data_vencimento`)
   - Valor (`valor` e `valor_total`)
   - Descrição/Título (`titulo`)
   - Status (`status`) - apenas se ainda estiver aberta
   - Forma de Pagamento (`forma_pagamento`)
4. **Fluxo:**
   - Clique no ícone → Abre modal
   - Modal carrega dados via `GET /api/financeiro-faturas.php?id={id}`
   - Usuário edita campos
   - Salva via `PUT /api/financeiro-faturas.php?id={id}`

**Vantagens:**
- Reaproveita API existente (backend já está pronto)
- Reaproveita padrão visual do modal "Nova Fatura"
- Implementação rápida (apenas frontend)

#### Opção 2: Edição Inline na Tabela
1. **Duplo clique** na célula de vencimento ou valor
2. **Input inline** aparece
3. **Salva automaticamente** ao perder foco ou pressionar Enter

**Vantagens:**
- Mais rápido para edições simples
- Não precisa abrir modal

**Desvantagens:**
- Mais complexo de implementar
- Menos espaço para validações visuais

#### Opção 3: Expandir Modal de Visualização
1. **Implementar `visualizarFatura(id)`** primeiro
2. **Adicionar botão "Editar"** dentro do modal de visualização
3. **Modo edição** ativa campos editáveis

**Vantagens:**
- Usuário vê dados antes de editar
- Fluxo natural: visualizar → editar

**Desvantagens:**
- Requer implementar visualização primeiro

---

## 5. RESUMO EXECUTIVO

| Funcionalidade | Backend | Frontend | Status Geral |
|----------------|---------|----------|--------------|
| Criar Fatura | ✅ | ✅ | ✅ Funcional |
| Visualizar Fatura | ✅ | ❌ | ❌ Não implementado |
| Marcar como Paga | ⚠️ | ❌ | ❌ Não implementado |
| Cancelar Fatura | ✅ | ❌ | ❌ Não implementado |
| **Editar Fatura** | ✅ | ❌ | ❌ **Não implementado** |

**Recomendação:** Implementar modal de edição rápida (Opção 1) para permitir edição de vencimento, valor e descrição, aproveitando a API `PUT` já existente.

