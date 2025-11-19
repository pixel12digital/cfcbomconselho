# AUDITORIA TÉCNICA COMPLETA - MÓDULO FINANCEIRO E INADIMPLÊNCIA

**Data da Auditoria:** 2025-01-XX  
**Sistema:** CFC Bom Conselho  
**Módulo:** Financeiro - Faturas (Receitas) e Inadimplência  
**Tipo:** Investigativa (sem alterações de código)

---

## ÍNDICE

1. [Tabelas e Modelo de Dados](#1-tabelas-e-modelo-de-dados)
2. [APIs de Financeiro e Pagamentos](#2-apis-de-financeiro-e-pagamentos)
3. [Telas e Fluxos Principais (Frontend)](#3-telas-e-fluxos-principais-frontend)
4. [Regras de Inadimplência](#4-regras-de-inadimplência)
5. [Integrações e Impactos Cruzados](#5-integrações-e-impactos-cruzados)
6. [Resumo Executivo](#6-resumo-executivo)

---

## 1. TABELAS E MODELO DE DADOS

### 1.1. Tabela `financeiro_faturas`

**Arquivo de Migração:** `admin/migrations/005-create-financeiro-faturas-structure.sql`

#### Estrutura Completa

| Coluna | Tipo | Null | Default | Comentário |
|--------|------|------|---------|------------|
| `id` | INT | NO | AUTO_INCREMENT | Chave primária |
| `aluno_id` | INT | NO | - | FK para `alunos.id` |
| `matricula_id` | INT | YES | NULL | FK para `matriculas.id` |
| `titulo` | VARCHAR(200) | NO | - | Descrição/título da fatura |
| `descricao` | TEXT | YES | NULL | Descrição adicional |
| `valor` | DECIMAL(10,2) | NO | 0.00 | Valor (campo adicional) |
| `valor_total` | DECIMAL(10,2) | NO | 0.00 | **Valor oficial da fatura** |
| `data_vencimento` | DATE | NO | - | **Campo oficial de vencimento** |
| `vencimento` | DATE | YES | NULL | Campo alternativo (DEPRECATED, mantido para compatibilidade) |
| `status` | ENUM | NO | 'aberta' | Valores: 'aberta', 'paga', 'vencida', 'parcial', 'cancelada' |
| `forma_pagamento` | ENUM | NO | 'avista' | Valores: 'avista', 'boleto', 'pix', 'cartao', 'transferencia', 'dinheiro' |
| `parcelas` | INT | NO | 1 | Número de parcelas |
| `observacoes` | TEXT | YES | NULL | Observações adicionais |
| `reteste` | BOOLEAN | NO | FALSE | Flag para reteste |
| `criado_por` | INT | YES | NULL | FK para `usuarios.id` |
| `criado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Data de criação |
| `atualizado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | Data de atualização |

#### Campos Críticos - Status

✅ **EXISTEM E SÃO USADOS:**
- `data_vencimento` - Campo oficial (usado em páginas e criação)
- `status` - Status da fatura (aberta, paga, vencida, parcial, cancelada)
- `valor_total` - Valor oficial da fatura
- `forma_pagamento` - Forma de pagamento configurada

❌ **NÃO EXISTEM:**
- `data_pagamento` - **NÃO existe na tabela `financeiro_faturas`**
- `valor_pago` - **NÃO existe na tabela `financeiro_faturas`**

⚠️ **OBSERVAÇÃO IMPORTANTE:**
- O campo `vencimento` existe mas é DEPRECATED. O código tenta manter sincronizado com `data_vencimento` para compatibilidade.
- A tabela `financeiro_faturas` **não armazena dados de pagamento diretamente**. Os pagamentos são armazenados na tabela `pagamentos` (ver seção 1.2).

#### Uso no Código

**Arquivos que usam `financeiro_faturas`:**
- `admin/api/financeiro-faturas.php` - CRUD completo (GET, POST, PUT, DELETE)
- `admin/pages/financeiro-faturas.php` - Listagem e criação via frontend
- `admin/api/financeiro-relatorios.php` - Relatórios e inadimplência
- `admin/jobs/marcar_faturas_vencidas.php` - Job diário para marcar vencidas
- `admin/includes/FinanceiroRulesService.php` - Validações de bloqueio
- `includes/guards/AgendamentoGuards.php` - Verificação financeira para agendamentos

---

### 1.2. Tabela `pagamentos`

**Arquivo de Migração:** `admin/migrations/006-create-pagamentos-structure.sql`

#### Estrutura Completa

| Coluna | Tipo | Null | Default | Comentário |
|--------|------|------|---------|------------|
| `id` | INT | NO | AUTO_INCREMENT | Chave primária |
| `fatura_id` | INT | NO | - | **FK para `financeiro_faturas.id`** ✅ |
| `data_pagamento` | DATE | NO | - | Data do pagamento |
| `valor_pago` | DECIMAL(10,2) | NO | - | Valor pago neste pagamento |
| `metodo` | ENUM | NO | 'pix' | Valores: 'pix', 'boleto', 'cartao', 'dinheiro', 'transferencia', 'outros' |
| `comprovante_url` | VARCHAR(500) | YES | NULL | URL do comprovante |
| `obs` | TEXT | YES | NULL | Observações |
| `criado_por` | INT | YES | NULL | FK para `usuarios.id` |
| `criado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Data de criação |
| `atualizado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | Data de atualização |

#### Status da Integração

✅ **CORRETO:**
- A tabela `pagamentos` está **corretamente relacionada** com `financeiro_faturas` via `fatura_id`
- A API `admin/api/pagamentos.php` usa `financeiro_faturas` (linha 83, 95, 128)

⚠️ **OBSERVAÇÃO:**
- A migration original (`006-create-pagamentos-structure.sql`) menciona que a relação era com tabela `faturas` antiga, mas o código atual (`admin/api/pagamentos.php`) já foi atualizado para usar `financeiro_faturas`.

#### Funcionalidade de Pagamento Parcial

✅ **IMPLEMENTADO:**
- A tabela `pagamentos` permite múltiplos registros para a mesma `fatura_id`
- A função `recalcularStatusFatura()` em `admin/api/pagamentos.php` (linhas 200-234) calcula:
  - Se `total_pago >= valor_total` → status = 'paga'
  - Se `total_pago > 0` → status = 'parcial'
  - Caso contrário → status = 'aberta' ou 'vencida'

#### Uso no Código

**Arquivos que usam `pagamentos`:**
- `admin/api/pagamentos.php` - CRUD de pagamentos (GET, POST, DELETE)
- `admin/api/financeiro-faturas.php` - Não usa diretamente (apenas `financeiro_faturas`)

---

### 1.3. Tabela `alunos`

**Arquivo de Referência:** `install.php` (linhas 57-73)

#### Estrutura Base (do install.php)

| Coluna | Tipo | Null | Default | Comentário |
|--------|------|------|---------|------------|
| `id` | INT | NO | AUTO_INCREMENT | Chave primária |
| `nome` | VARCHAR(100) | NO | - | Nome do aluno |
| `cpf` | VARCHAR(14) | NO | UNIQUE | CPF |
| `rg` | VARCHAR(20) | YES | NULL | RG |
| `data_nascimento` | DATE | YES | NULL | Data de nascimento |
| `endereco` | TEXT | YES | NULL | Endereço |
| `telefone` | VARCHAR(20) | YES | NULL | Telefone |
| `email` | VARCHAR(100) | YES | NULL | Email |
| `cfc_id` | INT | NO | - | FK para `cfcs.id` |
| `categoria_cnh` | ENUM | NO | - | Categoria CNH |
| `status` | ENUM | NO | 'ativo' | Valores: 'ativo', 'inativo', 'concluido' |
| `criado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Data de criação |

#### ❌ COLUNAS DE INADIMPLÊNCIA NÃO EXISTEM

**PROBLEMA CRÍTICO DETECTADO:**

As colunas `inadimplente` e `inadimplente_desde` **NÃO EXISTEM** na tabela `alunos` conforme definida em `install.php`.

**EVIDÊNCIAS:**
1. A função `updateAlunoInadimplencia()` em `admin/api/financeiro-faturas.php` (linhas 390-415) tenta fazer:
   ```php
   $db->update('alunos', [
       'inadimplente' => $inadimplente ? 1 : 0,
       'inadimplente_desde' => $inadimplente ? date('Y-m-d') : null
   ], 'id = ?', [$alunoId]);
   ```
   Mas esta chamada está **COMENTADA** na função `handlePut()` (linhas 310-312) com a nota:
   ```php
   // COMENTADO: Colunas inadimplente e inadimplente_desde ainda não existem na tabela alunos
   ```

2. A API `admin/api/financeiro-relatorios.php` (linhas 144-160) faz SELECT em:
   ```sql
   SELECT a.inadimplente, a.inadimplente_desde
   FROM alunos a
   WHERE a.inadimplente = 1
   ```
   **Este SELECT vai falhar** se as colunas não existirem.

3. O arquivo `admin/includes/guards_exames.php` (linha 85-88) tenta verificar inadimplência, mas usa uma query diferente (verifica faturas diretamente).

**IMPACTO:**
- ❌ A função `updateAlunoInadimplencia()` está **QUEBRADA** - não pode atualizar colunas que não existem
- ❌ O relatório de inadimplência (`admin/api/financeiro-relatorios.php`) vai **FALHAR** ao tentar ler `a.inadimplente`
- ⚠️ A lógica de inadimplência está **PARCIALMENTE IMPLEMENTADA** mas não funcional

---

### 1.4. Tabela `financeiro_configuracoes`

**Arquivo de Migração:** `admin/migrations/008-create-financeiro-configuracoes-structure.sql`

#### Estrutura

| Coluna | Tipo | Null | Default | Comentário |
|--------|------|------|---------|------------|
| `id` | INT | NO | AUTO_INCREMENT | Chave primária |
| `chave` | VARCHAR(100) | NO | UNIQUE | Chave da configuração |
| `valor` | VARCHAR(255) | NO | - | Valor (armazenado como string) |
| `descricao` | VARCHAR(255) | YES | NULL | Descrição |
| `tipo` | ENUM | NO | 'texto' | Valores: 'texto', 'numero', 'booleano', 'data' |
| `criado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Data de criação |
| `atualizado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | Data de atualização |

#### Configuração Padrão

A migration insere automaticamente:
```sql
INSERT IGNORE INTO financeiro_configuracoes (chave, valor, descricao, tipo) VALUES
('dias_inadimplencia', '30', 'Número de dias após vencimento para considerar inadimplente', 'numero');
```

#### Uso no Código

**Arquivos que usam `financeiro_configuracoes`:**
- `admin/api/financeiro-faturas.php` - Função `updateAlunoInadimplencia()` (linha 394)
- `admin/api/financeiro-relatorios.php` - Função `getInadimplencia()` (linha 136)
- Ambos usam fallback para 30 dias se a tabela não existir ou a configuração não for encontrada

---

### 1.5. Tabela `matriculas`

**Arquivo de Referência:** `install.php` (linha 194)

#### Campo Relacionado a Financeiro

| Coluna | Tipo | Null | Default | Comentário |
|--------|------|------|---------|------------|
| `status_financeiro` | ENUM | NO | 'regular' | Valores: 'regular', 'inadimplente', 'quitado' |

#### Uso no Código

**Arquivos que usam `matriculas.status_financeiro`:**
- `admin/jobs/marcar_faturas_vencidas.php` - Atualiza `status_financeiro` baseado em faturas vencidas (linhas 48-65)

⚠️ **OBSERVAÇÃO:**
- O campo `status_financeiro` em `matriculas` é atualizado pelo job, mas **não é usado** para bloqueios de agendamento.
- Os bloqueios de agendamento usam `FinanceiroRulesService` que verifica `financeiro_faturas` diretamente.

---

### 1.6. Tabela `financeiro_pagamentos` (Despesas)

**Arquivo de Migração:** `admin/migrations/007-create-financeiro-pagamentos-structure.sql`

⚠️ **ATENÇÃO:** Esta tabela é para **DESPESAS**, não para pagamentos de faturas.

| Coluna | Tipo | Null | Default | Comentário |
|--------|------|------|---------|------------|
| `id` | INT | NO | AUTO_INCREMENT | Chave primária |
| `fornecedor` | VARCHAR(200) | NO | - | Fornecedor |
| `descricao` | TEXT | YES | NULL | Descrição |
| `categoria` | ENUM | NO | 'outros' | Categoria da despesa |
| `valor` | DECIMAL(10,2) | NO | - | Valor da despesa |
| `status` | ENUM | NO | 'pendente' | Valores: 'pendente', 'pago', 'cancelado' |
| `vencimento` | DATE | NO | - | Data de vencimento |
| `data_pagamento` | DATE | YES | NULL | Data de pagamento |
| `forma_pagamento` | ENUM | NO | 'pix' | Forma de pagamento |
| `comprovante_url` | VARCHAR(500) | YES | NULL | URL do comprovante |
| `observacoes` | TEXT | YES | NULL | Observações |
| `criado_por` | INT | YES | NULL | FK para `usuarios.id` |
| `criado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP | Data de criação |
| `atualizado_em` | TIMESTAMP | NO | CURRENT_TIMESTAMP ON UPDATE | Data de atualização |

**NÃO CONFUNDIR:**
- `financeiro_pagamentos` = **DESPESAS** (saídas de dinheiro)
- `pagamentos` = **PAGAMENTOS DE FATURAS** (entradas de dinheiro, relacionado a `financeiro_faturas`)

---

## 2. APIs DE FINANCEIRO E PAGAMENTOS

### 2.1. API `admin/api/financeiro-faturas.php`

**Arquivo:** `admin/api/financeiro-faturas.php`  
**Métodos Suportados:** GET, POST, PUT, DELETE

#### 2.1.1. GET (Listagem)

**Endpoint:** `GET admin/api/financeiro-faturas.php`

**Parâmetros:**
- `id` (opcional) - Buscar fatura específica
- `aluno_id` (opcional) - Filtrar por aluno
- `status` (opcional) - Filtrar por status
- `data_inicio` (opcional) - Filtrar por data de vencimento inicial
- `data_fim` (opcional) - Filtrar por data de vencimento final
- `page` (opcional, default: 1) - Página para paginação
- `limit` (opcional, default: 20) - Itens por página
- `export=csv` (opcional) - Exportar para CSV

**Query SQL (Listagem):**
```sql
SELECT f.*, a.nome as aluno_nome, a.cpf, m.categoria_cnh, m.tipo_servico
FROM financeiro_faturas f
JOIN alunos a ON f.aluno_id = a.id
LEFT JOIN matriculas m ON f.matricula_id = m.id
WHERE [filtros]
ORDER BY f.data_vencimento DESC, f.criado_em DESC
LIMIT ? OFFSET ?
```

**Query SQL (Fatura Específica):**
```sql
SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf, m.categoria_cnh, m.tipo_servico
FROM financeiro_faturas f
JOIN alunos a ON f.aluno_id = a.id
LEFT JOIN matriculas m ON f.matricula_id = m.id
WHERE f.id = ?
```

**Resposta JSON:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "aluno_id": 112,
    "aluno_nome": "Nome do Aluno",
    "aluno_cpf": "123.456.789-00",
    "cpf": "123.456.789-00",
    "titulo": "1ª parcela",
    "valor": 1000.00,
    "valor_total": 1000.00,
    "data_vencimento": "2025-12-10",
    "vencimento": "2025-12-10",
    "status": "aberta",
    "forma_pagamento": "avista",
    "observacoes": null,
    "matricula_id": null,
    "parcelas": 1
  }
}
```

#### 2.1.2. POST (Criação)

**Endpoint:** `POST admin/api/financeiro-faturas.php`

**Payload JSON:**
```json
{
  "aluno_id": 112,
  "matricula_id": null,
  "titulo": "1ª parcela",
  "valor_total": 1000.00,
  "data_vencimento": "2025-12-10",
  "status": "aberta",
  "forma_pagamento": "avista",
  "parcelas": 1,
  "observacoes": null
}
```

**Validações:**
- Campos obrigatórios: `aluno_id`, `titulo`, `valor_total`, `data_vencimento` (ou `vencimento` para compatibilidade)
- Valida se aluno existe
- Aceita tanto `data_vencimento` quanto `vencimento` (mantém ambos sincronizados)

**INSERT SQL:**
```php
$db->insert('financeiro_faturas', [
    'aluno_id' => $input['aluno_id'],
    'matricula_id' => $input['matricula_id'] ?? null,
    'titulo' => $input['titulo'],
    'valor_total' => $input['valor_total'],
    'status' => $input['status'] ?? 'aberta',
    'data_vencimento' => $dataVencimento,
    'vencimento' => $dataVencimento, // Compatibilidade
    'forma_pagamento' => $input['forma_pagamento'] ?? 'avista',
    'parcelas' => $input['parcelas'] ?? 1,
    'observacoes' => $input['observacoes'] ?? null,
    'criado_por' => $user['id']
]);
```

**Após Criação:**
- Chama `updateAlunoInadimplencia($db, $input['aluno_id'])` (linha 257)
- ⚠️ **PROBLEMA:** Esta função tenta atualizar `alunos.inadimplente` e `alunos.inadimplente_desde` que **não existem**

#### 2.1.3. PUT (Edição)

**Endpoint:** `PUT admin/api/financeiro-faturas.php?id={id}`

**Payload JSON:**
```json
{
  "titulo": "1ª parcela - Atualizado",
  "valor_total": 1200.00,
  "data_vencimento": "2025-12-15",
  "status": "aberta",
  "forma_pagamento": "boleto",
  "observacoes": "Observação atualizada"
}
```

**Campos Permitidos:**
- `titulo`, `valor_total`, `status`, `data_vencimento`, `vencimento`, `forma_pagamento`, `observacoes`

**UPDATE SQL:**
```php
$db->update('financeiro_faturas', $updateData, 'id = ?', [$id]);
```

**Após Edição:**
- ❌ **COMENTADO:** A chamada `updateAlunoInadimplencia()` está comentada (linhas 310-312) porque as colunas não existem

#### 2.1.4. DELETE (Exclusão)

**Endpoint:** `DELETE admin/api/financeiro-faturas.php?id={id}`

**Validações:**
- Apenas faturas com `status = 'aberta'` podem ser excluídas

**DELETE SQL:**
```php
$db->delete('financeiro_faturas', 'id = ?', [$id]);
```

**Após Exclusão:**
- Chama `updateAlunoInadimplencia($db, $fatura['aluno_id'])` (linha 340)
- ⚠️ **PROBLEMA:** Esta função tenta atualizar colunas que não existem

#### 2.1.5. Função `updateAlunoInadimplencia()`

**Localização:** `admin/api/financeiro-faturas.php` (linhas 390-415)

**Algoritmo:**
1. Busca configuração `dias_inadimplencia` de `financeiro_configuracoes` (fallback: 30 dias)
2. Conta faturas vencidas do aluno:
   ```sql
   SELECT COUNT(*) 
   FROM financeiro_faturas 
   WHERE aluno_id = ? 
   AND status IN ('aberta', 'vencida') 
   AND data_vencimento < DATE_SUB(NOW(), INTERVAL ? DAY)
   ```
3. Se `faturasVencidas > 0` → `inadimplente = 1`, senão `inadimplente = 0`
4. Se inadimplente, define `inadimplente_desde = hoje`, senão `NULL`
5. ❌ **FALHA:** Tenta fazer UPDATE em `alunos.inadimplente` e `alunos.inadimplente_desde` que **não existem**

**Status:** ⚠️ **QUEBRADO** - A função existe mas não pode executar o UPDATE final

---

### 2.2. API `admin/api/pagamentos.php`

**Arquivo:** `admin/api/pagamentos.php`  
**Métodos Suportados:** GET, POST, DELETE

#### 2.2.1. GET (Listagem)

**Endpoint:** `GET admin/api/pagamentos.php`

**Parâmetros:**
- `fatura_id` (opcional) - Filtrar pagamentos de uma fatura específica

**Query SQL (Fatura Específica):**
```sql
SELECT p.*, f.titulo as fatura_titulo, f.valor_total as fatura_valor_total,
       f.data_vencimento as fatura_data_vencimento
FROM pagamentos p
JOIN financeiro_faturas f ON p.fatura_id = f.id
WHERE p.fatura_id = ?
ORDER BY p.data_pagamento DESC
```

**Query SQL (Todos os Pagamentos):**
```sql
SELECT p.*, f.titulo as fatura_titulo, f.valor_total as fatura_valor_total,
       f.data_vencimento as fatura_data_vencimento, a.nome as aluno_nome
FROM pagamentos p
JOIN financeiro_faturas f ON p.fatura_id = f.id
JOIN alunos a ON f.aluno_id = a.id
ORDER BY p.data_pagamento DESC
LIMIT 100
```

✅ **CORRETO:** A API usa `financeiro_faturas` (não a tabela antiga `faturas`)

#### 2.2.2. POST (Criação de Pagamento)

**Endpoint:** `POST admin/api/pagamentos.php`

**Payload JSON:**
```json
{
  "fatura_id": 1,
  "data_pagamento": "2025-01-15",
  "valor_pago": 500.00,
  "metodo": "pix",
  "comprovante_url": null,
  "obs": "Pagamento parcial"
}
```

**Validações:**
- Campos obrigatórios: `fatura_id`, `data_pagamento`, `valor_pago`
- Verifica se fatura existe
- Verifica se fatura não está cancelada

**INSERT SQL:**
```php
$db->insert('pagamentos', [
    'fatura_id' => $input['fatura_id'],
    'data_pagamento' => $input['data_pagamento'],
    'valor_pago' => $input['valor_pago'],
    'metodo' => $input['metodo'] ?? 'pix',
    'comprovante_url' => $input['comprovante_url'] ?? null,
    'obs' => $input['obs'] ?? null,
    'criado_por' => $currentUser['id']
]);
```

**Após Criação:**
- Chama `recalcularStatusFatura($db, $input['fatura_id'])` (linha 154)
- Esta função atualiza o `status` da fatura baseado no total pago

#### 2.2.3. DELETE (Estorno de Pagamento)

**Endpoint:** `DELETE admin/api/pagamentos.php?id={id}`

**Após Exclusão:**
- Chama `recalcularStatusFatura($db, $pagamento['fatura_id'])` (linha 188)
- Recalcula o status da fatura após remover o pagamento

#### 2.2.4. Função `recalcularStatusFatura()`

**Localização:** `admin/api/pagamentos.php` (linhas 200-234)

**Algoritmo:**
1. Busca fatura por ID
2. Calcula total pago:
   ```sql
   SELECT COALESCE(SUM(valor_pago), 0) FROM pagamentos WHERE fatura_id = ?
   ```
3. Compara `total_pago` com `valor_total`:
   - Se `total_pago >= valor_total` → status = 'paga'
   - Se `total_pago > 0` → status = 'parcial'
   - Caso contrário:
     - Se `data_vencimento < hoje` → status = 'vencida'
     - Senão → status = 'aberta'
4. Atualiza `financeiro_faturas.status`

✅ **FUNCIONAL:** Esta função está correta e funcional

---

### 2.3. Job `admin/jobs/marcar_faturas_vencidas.php`

**Arquivo:** `admin/jobs/marcar_faturas_vencidas.php`  
**Tipo:** Job diário (deve ser executado via cron)

#### Funcionalidades

**1. Marcar Faturas Vencidas:**
```sql
UPDATE financeiro_faturas 
SET status = 'vencida' 
WHERE status = 'aberta' AND data_vencimento < CURDATE()
```

**2. Atualizar Status Financeiro das Matrículas:**
```sql
-- Marcar como inadimplente
UPDATE matriculas m
JOIN (
    SELECT DISTINCT matricula_id, aluno_id
    FROM financeiro_faturas
    WHERE status = 'vencida' AND matricula_id IS NOT NULL
) f ON f.matricula_id = m.id
SET m.status_financeiro = 'inadimplente'
WHERE m.status_financeiro != 'inadimplente'

-- Marcar como regular
UPDATE matriculas
SET status_financeiro = 'regular'
WHERE id NOT IN (
    SELECT DISTINCT matricula_id 
    FROM financeiro_faturas 
    WHERE status = 'vencida' AND matricula_id IS NOT NULL
)
AND status_financeiro != 'regular'
```

**3. Estatísticas:**
- Total de faturas
- Faturas abertas, pagas, vencidas, parciais
- Matrículas regulares e inadimplentes

✅ **FUNCIONAL:** O job está correto e usa `financeiro_faturas` e `data_vencimento`

⚠️ **OBSERVAÇÃO:** O job atualiza `matriculas.status_financeiro`, mas este campo **não é usado** para bloqueios de agendamento (ver seção 5.2).

---

## 3. TELAS E FLUXOS PRINCIPAIS (FRONTEND)

### 3.1. Tela `admin/pages/financeiro-faturas.php`

**Arquivo:** `admin/pages/financeiro-faturas.php`

#### 3.1.1. Listagem de Faturas

**Query PHP (Backend):**
```php
$faturas = $db->fetchAll("
    SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf
    FROM financeiro_faturas f
    JOIN alunos a ON f.aluno_id = a.id
    WHERE [filtros]
    ORDER BY f.data_vencimento DESC, f.criado_em DESC
    LIMIT ? OFFSET ?
");
```

**Filtros Disponíveis:**
- `aluno_id` - Filtrar por aluno
- `status` - Filtrar por status (aberta, paga, vencida, parcial, cancelada)
- `data_inicio` - Filtrar por data de vencimento inicial
- `data_fim` - Filtrar por data de vencimento final

**Colunas Exibidas:**
- Aluno (nome + CPF)
- Descrição (título da fatura)
- Valor (valor_total)
- Vencimento (data_vencimento)
- Status (badge colorido)
- Ações (editar, visualizar, marcar como paga, cancelar)

#### 3.1.2. Criação de Fatura

**Modal:** `#modalNovaFatura`

**Campos do Formulário:**
- Aluno (select)
- Matrícula (select, opcional)
- Título/Descrição (input text)
- Valor Total (input number)
- Data de Vencimento (input date)
- Forma de Pagamento (select)
- Parcelas (input number, default: 1)
- Observações (textarea)

**Endpoint Chamado:**
- `POST admin/api/financeiro-faturas.php`

**Payload Enviado:**
```json
{
  "aluno_id": 112,
  "matricula_id": null,
  "titulo": "1ª parcela",
  "valor_total": 1000.00,
  "data_vencimento": "2025-12-10",
  "status": "aberta",
  "forma_pagamento": "avista",
  "parcelas": 1,
  "observacoes": null
}
```

#### 3.1.3. Edição de Fatura

**Modal:** `#modalEditarFatura`

**Função JavaScript:** `editarFatura(id)` (linha 2475)

**Fluxo:**
1. Faz GET para `admin/api/financeiro-faturas.php?id={id}`
2. Preenche formulário com dados retornados
3. Usuário edita campos (título, data_vencimento, forma_pagamento, observações)
4. Ao salvar, faz PUT para `admin/api/financeiro-faturas.php?id={id}`

**Campos Editáveis:**
- Título/Descrição
- Data de Vencimento
- Forma de Pagamento
- Observações

**Campos Somente Leitura:**
- Aluno (nome + CPF)
- Valor (não pode ser alterado)

#### 3.1.4. Visualização de Fatura

**Modal:** `#modalVisualizarFatura`

**Função JavaScript:** `visualizarFatura(id)` (linha 2639)

**Fluxo:**
1. Faz GET para `admin/api/financeiro-faturas.php?id={id}`
2. Preenche modal com dados em modo somente leitura

**Campos Exibidos:**
- Aluno (nome + CPF)
- Valor
- Descrição/Título
- Data de Vencimento
- Forma de Pagamento
- Status (badge)
- Observações (se houver)

✅ **IMPLEMENTADO:** A visualização está funcional

#### 3.1.5. Ações Rápidas

**Ícone de Editar (Lápis):**
- Chama `editarFatura(id)` → Abre modal de edição

**Ícone de Visualizar (Olho):**
- Chama `visualizarFatura(id)` → Abre modal de visualização

**Ícone de Marcar como Paga (Check):**
- Função: `marcarComoPaga(id)` (linha 2872)
- ⚠️ **PLACEHOLDER:** Ainda não implementado, apenas mostra `alert()`

**Ícone de Cancelar (X):**
- Função: `cancelarFatura(id)` (linha 2878)
- ⚠️ **PLACEHOLDER:** Ainda não implementado, apenas mostra `alert()`

#### 3.1.6. Exibição de Dados de Pagamento

❌ **NÃO EXIBE:**
- Data de pagamento
- Valor pago
- Pagamento parcial
- Histórico de pagamentos

⚠️ **GAP:** A tela não mostra informações de pagamentos registrados na tabela `pagamentos`

---

### 3.2. Tela `admin/pages/alunos.php`

**Arquivo:** `admin/pages/alunos.php`

#### 3.2.1. Exibição de Situação Financeira

**Função JavaScript:** `atualizarResumoFinanceiroAluno(alunoId, matricula)` (linha 7024)

**Algoritmo (JavaScript):**
1. Busca faturas do aluno via API `admin/api/financeiro-faturas.php?aluno_id={id}`
2. Calcula status financeiro com prioridade:
   - **Prioridade 1:** Se tem faturas vencidas → "Em atraso" (vermelho)
   - **Prioridade 2:** Se tem faturas abertas com vencimento >= hoje → "Em aberto" (azul)
   - **Prioridade 3:** Se tem faturas pagas → "Quitado" (verde)
   - **Default:** "Não lançado" (cinza)
3. Exibe no card "Situação Financeira" do modal de aluno

**Card Exibido:**
- Status (badge colorido)
- Valor em aberto (soma de faturas abertas)
- Valor vencido (soma de faturas vencidas)
- Total de faturas

✅ **FUNCIONAL:** A exibição de situação financeira está implementada

#### 3.2.2. Link para Financeiro do Aluno

**Função:** `abrirFinanceiroAluno(id)` (linha 5048)

**Ação:**
- Redireciona para `?page=financeiro-faturas&aluno_id={id}`
- Filtra a lista de faturas para mostrar apenas as do aluno

✅ **FUNCIONAL:** O link está implementado

---

### 3.3. Telas de Agendamento

#### 3.3.1. Validação Financeira em Agendamentos

**Arquivo:** `admin/includes/FinanceiroRulesService.php`

**Método:** `podeAgendar(int $alunoId): array`

**Algoritmo:**
1. Busca faturas do aluno (tenta `financeiro_faturas`, fallback para `faturas` antiga)
2. Se não encontrou faturas → retorna `NAO_LANCADO` (bloqueia)
3. Calcula status financeiro:
   - Se tem faturas vencidas ou abertas com vencimento < hoje → `em_atraso` → **BLOQUEIA**
   - Se tem faturas abertas com vencimento >= hoje → `em_aberto` → **PERMITE**
   - Se tem faturas pagas → `quitado` → **PERMITE**
   - Default → `nao_lancado` → **BLOQUEIA**

**Uso:**
- `includes/guards/AgendamentoGuards.php` - Usa `FinanceiroRulesService` para validar antes de agendar
- `admin/includes/guards_exames.php` - Verifica inadimplência para aulas teóricas (linha 70)

✅ **IMPLEMENTADO:** A validação financeira está funcional e bloqueia agendamentos quando necessário

#### 3.3.2. Validação em Aulas Práticas

**Arquivo:** `includes/guards/AgendamentoGuards.php`

**Método:** `verificarTodasValidacoes($alunoId, $tipoAula, ...)`

**Validações Incluídas:**
1. Exames OK (médico e psicotécnico)
2. **Situação Financeira** (via `FinanceiroRulesService`)
3. Conflitos de horário

✅ **IMPLEMENTADO:** Aulas práticas são bloqueadas se aluno estiver inadimplente

#### 3.3.3. Validação em Aulas Teóricas

**Arquivo:** `admin/includes/guards_exames.php`

**Método:** `verificarBloqueioTeorica($alunoId)`

**Validações:**
1. Exames OK (médico e psicotécnico)
2. **Inadimplência** (via `verificarInadimplencia()`)

**Método `verificarInadimplencia()`:**
```php
$faturasAbertas = $db->count('financeiro_faturas', 
    'aluno_id = ? AND status IN ("em_aberto", "vencida")', 
    [$alunoId]
);
```

⚠️ **PROBLEMA:** A query usa `status IN ("em_aberto", "vencida")` mas os valores corretos são `"aberta"` e `"vencida"`. O status `"em_aberto"` não existe no ENUM.

✅ **PARCIALMENTE FUNCIONAL:** A validação existe mas pode ter bugs na query

#### 3.3.4. Validação em Exames

**Arquivo:** `admin/includes/guards_exames.php`

**Método:** `verificarBloqueioTeorica($alunoId)` - Usado também para exames

✅ **IMPLEMENTADO:** Exames são bloqueados se aluno estiver inadimplente

---

## 4. REGRAS DE INADIMPLÊNCIA

### 4.1. Funções que Atualizam Inadimplência

#### 4.1.1. `updateAlunoInadimplencia()`

**Localização:** `admin/api/financeiro-faturas.php` (linhas 390-415)

**Descrição:**
Função que tenta atualizar `alunos.inadimplente` e `alunos.inadimplente_desde` baseado em faturas vencidas.

**Algoritmo:**
1. Busca configuração `dias_inadimplencia` de `financeiro_configuracoes` (fallback: 30 dias)
2. Conta faturas vencidas:
   ```sql
   SELECT COUNT(*) 
   FROM financeiro_faturas 
   WHERE aluno_id = ? 
   AND status IN ('aberta', 'vencida') 
   AND data_vencimento < DATE_SUB(NOW(), INTERVAL ? DAY)
   ```
3. Se `faturasVencidas > 0` → `inadimplente = 1`, senão `inadimplente = 0`
4. Se inadimplente, define `inadimplente_desde = hoje`, senão `NULL`
5. ❌ **FALHA:** Tenta fazer UPDATE em colunas que não existem

**Chamadas:**
- ✅ `handlePost()` (linha 257) - Após criar fatura
- ❌ `handlePut()` (linha 310) - **COMENTADO** (colunas não existem)
- ✅ `handleDelete()` (linha 340) - Após excluir fatura

**Status:** ⚠️ **QUEBRADO** - A função existe mas não pode executar o UPDATE final

#### 4.1.2. Job `marcar_faturas_vencidas.php`

**Localização:** `admin/jobs/marcar_faturas_vencidas.php`

**Descrição:**
Job diário que marca faturas vencidas e atualiza `matriculas.status_financeiro`.

**Algoritmo:**
1. Marca faturas vencidas:
   ```sql
   UPDATE financeiro_faturas 
   SET status = 'vencida' 
   WHERE status = 'aberta' AND data_vencimento < CURDATE()
   ```
2. Atualiza `matriculas.status_financeiro`:
   - Marca como `inadimplente` se tem faturas vencidas
   - Marca como `regular` se não tem faturas vencidas

**Status:** ✅ **FUNCIONAL** - O job está correto

⚠️ **OBSERVAÇÃO:** O job atualiza `matriculas.status_financeiro`, mas este campo **não é usado** para bloqueios de agendamento.

---

### 4.2. Funções que Verificam Inadimplência

#### 4.2.1. `FinanceiroRulesService::podeAgendar()`

**Localização:** `admin/includes/FinanceiroRulesService.php` (linhas 41-110)

**Descrição:**
Verifica se aluno pode agendar aula/exame baseado na situação financeira.

**Algoritmo:**
1. Busca faturas do aluno (tenta `financeiro_faturas`, fallback para `faturas`)
2. Se não encontrou faturas → retorna `NAO_LANCADO` (bloqueia)
3. Calcula status financeiro:
   - Se tem faturas vencidas ou abertas com vencimento < hoje → `em_atraso` → **BLOQUEIA**
   - Se tem faturas abertas com vencimento >= hoje → `em_aberto` → **PERMITE**
   - Se tem faturas pagas → `quitado` → **PERMITE**
   - Default → `nao_lancado` → **BLOQUEIA**

**Retorno:**
```php
[
    'ok' => bool,
    'codigo' => string, // 'INADIMPLENTE', 'FINANCEIRO_EM_DIA', 'NAO_LANCADO', etc.
    'mensagem' => string
]
```

**Status:** ✅ **FUNCIONAL** - A verificação está correta e funcional

#### 4.2.2. `GuardsExames::verificarInadimplencia()`

**Localização:** `admin/includes/guards_exames.php` (linhas 70-98)

**Descrição:**
Verifica inadimplência para aulas teóricas.

**Algoritmo:**
1. Verifica flag `bloquearTeoricaInadimplente` (hardcoded como `true`)
2. Conta faturas abertas/vencidas:
   ```php
   $faturasAbertas = $db->count('financeiro_faturas', 
       'aluno_id = ? AND status IN ("em_aberto", "vencida")', 
       [$alunoId]
   );
   ```
3. Se `faturasAbertas > 0` → bloqueia

⚠️ **PROBLEMA:** A query usa `status IN ("em_aberto", "vencida")` mas o valor correto é `"aberta"`, não `"em_aberto"`.

**Status:** ⚠️ **BUG** - A query está incorreta

---

### 4.3. Configuração de Inadimplência

**Tabela:** `financeiro_configuracoes`

**Chave:** `dias_inadimplencia`

**Valor Padrão:** 30 dias

**Uso:**
- `updateAlunoInadimplencia()` - Usa para determinar quantos dias após vencimento considerar inadimplente
- `getInadimplencia()` - Usa para filtrar relatório de inadimplência

**Fallback:**
- Se a tabela não existir ou a configuração não for encontrada, usa 30 dias como padrão

✅ **FUNCIONAL:** A configuração está implementada e funcional

---

## 5. INTEGRAÇÕES E IMPACTOS CRUZADOS

### 5.1. Quem Depende de Faturas

#### 5.1.1. Telas/APIs que Apenas Leem Faturas

**1. Tela de Listagem (`admin/pages/financeiro-faturas.php`)**
- **Origem:** `financeiro_faturas`
- **Ação:** Apenas exibe lista de faturas
- **Impacto:** Nenhum (somente leitura)

**2. Relatórios (`admin/api/financeiro-relatorios.php`)**
- **Origem:** `financeiro_faturas`
- **Ação:** Calcula estatísticas e gera relatórios
- **Impacto:** Se mudar estrutura de faturas, relatórios podem quebrar

**3. Resumo Financeiro do Aluno (`admin/pages/alunos.php`)**
- **Origem:** `financeiro_faturas` (via API GET)
- **Ação:** Exibe situação financeira no modal do aluno
- **Impacto:** Se mudar estrutura de faturas, o resumo pode não funcionar

#### 5.1.2. Telas/APIs que Alteram Status de Faturas

**1. API de Pagamentos (`admin/api/pagamentos.php`)**
- **Origem:** `financeiro_faturas` + `pagamentos`
- **Ação:** Atualiza `status` da fatura (paga, parcial) baseado em pagamentos
- **Impacto:** Se mudar lógica de status, pagamentos podem não funcionar corretamente

**2. Job de Faturas Vencidas (`admin/jobs/marcar_faturas_vencidas.php`)**
- **Origem:** `financeiro_faturas`
- **Ação:** Atualiza `status = 'vencida'` para faturas vencidas
- **Impacto:** Se mudar lógica de vencimento, job pode não funcionar

**3. API de Faturas (`admin/api/financeiro-faturas.php`)**
- **Origem:** `financeiro_faturas`
- **Ação:** Cria, edita e exclui faturas
- **Impacto:** Se mudar estrutura, CRUD pode quebrar

---

### 5.2. Quem Depende da Situação Financeira do Aluno

#### 5.2.1. Bloqueios de Agendamento

**1. Aulas Práticas (`includes/guards/AgendamentoGuards.php`)**
- **Origem:** `FinanceiroRulesService::podeAgendar()` → Verifica `financeiro_faturas` diretamente
- **Ação:** **BLOQUEIA** agendamento se aluno inadimplente
- **Impacto:** Se mudar cálculo de inadimplência, bloqueios podem não funcionar

**2. Aulas Teóricas (`admin/includes/guards_exames.php`)**
- **Origem:** `verificarInadimplencia()` → Verifica `financeiro_faturas` diretamente
- **Ação:** **BLOQUEIA** agendamento se aluno inadimplente
- **Impacto:** Se mudar cálculo de inadimplência, bloqueios podem não funcionar

**3. Exames (`admin/includes/guards_exames.php`)**
- **Origem:** `verificarBloqueioTeorica()` → Usa `verificarInadimplencia()`
- **Ação:** **BLOQUEIA** agendamento se aluno inadimplente
- **Impacto:** Se mudar cálculo de inadimplência, bloqueios podem não funcionar

#### 5.2.2. Exibição de Situação Financeira

**1. Modal de Aluno (`admin/pages/alunos.php`)**
- **Origem:** `financeiro_faturas` (via API GET)
- **Ação:** Exibe card "Situação Financeira" com status e valores
- **Impacto:** Se mudar estrutura de faturas, o card pode não funcionar

**2. Relatório de Inadimplência (`admin/api/financeiro-relatorios.php`)**
- **Origem:** `alunos.inadimplente` + `financeiro_faturas`
- **Ação:** Lista alunos inadimplentes
- **Impacto:** ⚠️ **QUEBRADO** - Tenta ler `alunos.inadimplente` que não existe

---

### 5.3. Impactos Prováveis de Mudanças

#### 5.3.1. Se Adicionar `data_pagamento` em `financeiro_faturas`

**Impactos:**
- ✅ **POSITIVO:** Simplifica queries (não precisa JOIN com `pagamentos`)
- ⚠️ **ATENÇÃO:** Precisa manter sincronizado com `pagamentos.data_pagamento`
- ⚠️ **ATENÇÃO:** Pode ter múltiplos pagamentos para uma fatura (parcial) → qual data usar?

#### 5.3.2. Se Adicionar `valor_pago` em `financeiro_faturas`

**Impactos:**
- ✅ **POSITIVO:** Simplifica queries (não precisa SUM de `pagamentos`)
- ⚠️ **ATENÇÃO:** Precisa manter sincronizado com `pagamentos.valor_pago`
- ⚠️ **ATENÇÃO:** Pode ter múltiplos pagamentos → precisa recalcular sempre

#### 5.3.3. Se Adicionar `alunos.inadimplente` e `alunos.inadimplente_desde`

**Impactos:**
- ✅ **POSITIVO:** Permite queries mais rápidas (não precisa calcular sempre)
- ⚠️ **ATENÇÃO:** Precisa manter sincronizado com `financeiro_faturas`
- ⚠️ **ATENÇÃO:** Precisa reativar `updateAlunoInadimplencia()` em todos os pontos
- ⚠️ **ATENÇÃO:** Relatório de inadimplência vai funcionar novamente

#### 5.3.4. Se Mudar Lógica de Cálculo de Inadimplência

**Impactos:**
- ⚠️ **CRÍTICO:** Todos os bloqueios de agendamento podem mudar de comportamento
- ⚠️ **CRÍTICO:** Relatórios podem mostrar dados diferentes
- ⚠️ **ATENÇÃO:** Precisa atualizar `FinanceiroRulesService`, `GuardsExames`, `updateAlunoInadimplencia()`, etc.

---

## 6. RESUMO EXECUTIVO

### 6.1. Estado Atual

**Faturas funcionam para cobrança básica, mas não há modelo completo de pagamento e inadimplência está parcialmente implementada e quebrada.**

### 6.2. Principais Pontos Mapeados

#### 6.2.1. Como Faturas São Salvas e Atualizadas

✅ **FUNCIONAL:**
- Criação via `POST admin/api/financeiro-faturas.php` → Insere em `financeiro_faturas`
- Edição via `PUT admin/api/financeiro-faturas.php?id={id}` → Atualiza `financeiro_faturas`
- Exclusão via `DELETE admin/api/financeiro-faturas.php?id={id}` → Deleta de `financeiro_faturas`
- Listagem via `GET admin/api/financeiro-faturas.php` → Lê de `financeiro_faturas`

⚠️ **PROBLEMA:**
- Após criar/editar/excluir, tenta atualizar `alunos.inadimplente` mas as colunas não existem

#### 6.2.2. Como Pagamentos São Tratados

✅ **FUNCIONAL:**
- Pagamentos são registrados em tabela separada `pagamentos`
- Cada pagamento tem `fatura_id` relacionado a `financeiro_faturas`
- Função `recalcularStatusFatura()` atualiza `status` da fatura baseado em total pago
- Suporta pagamento parcial (múltiplos pagamentos para uma fatura)

❌ **GAP:**
- A tela `financeiro-faturas.php` **não exibe** dados de pagamentos
- Não há visualização de histórico de pagamentos na tela de faturas

#### 6.2.3. Como Está a Lógica de Inadimplência Hoje

⚠️ **PARCIALMENTE QUEBRADA:**
- Função `updateAlunoInadimplencia()` existe mas **não pode executar** (colunas não existem)
- Relatório de inadimplência tenta ler `alunos.inadimplente` que **não existe**
- Validações de bloqueio funcionam mas verificam `financeiro_faturas` diretamente (não usam flag em `alunos`)
- Job `marcar_faturas_vencidas.php` atualiza `matriculas.status_financeiro` mas **não é usado** para bloqueios

✅ **FUNCIONAL:**
- `FinanceiroRulesService::podeAgendar()` verifica faturas diretamente e bloqueia corretamente
- Bloqueios de agendamento funcionam (aulas práticas, teóricas, exames)

#### 6.2.4. Onde Isso Impacta Aluno, Aulas, Exames

✅ **IMPLEMENTADO:**
- **Aulas Práticas:** Bloqueadas se aluno inadimplente (via `AgendamentoGuards`)
- **Aulas Teóricas:** Bloqueadas se aluno inadimplente (via `GuardsExames`)
- **Exames:** Bloqueados se aluno inadimplente (via `GuardsExames`)
- **Modal de Aluno:** Exibe situação financeira (via JavaScript)

⚠️ **BUG:**
- `GuardsExames::verificarInadimplencia()` usa query incorreta (`status IN ("em_aberto", "vencida")` ao invés de `("aberta", "vencida")`)

---

### 6.3. Riscos e Gaps

#### 6.3.1. Colunas/Tabelas Referenciadas mas Inexistentes

❌ **CRÍTICO:**
1. **`alunos.inadimplente`** - Referenciada em:
   - `admin/api/financeiro-faturas.php:412` (UPDATE)
   - `admin/api/financeiro-relatorios.php:155` (SELECT)
   - **IMPACTO:** Função `updateAlunoInadimplencia()` quebrada, relatório de inadimplência quebrado

2. **`alunos.inadimplente_desde`** - Referenciada em:
   - `admin/api/financeiro-faturas.php:413` (UPDATE)
   - `admin/api/financeiro-relatorios.php:149` (SELECT)
   - **IMPACTO:** Função `updateAlunoInadimplencia()` quebrada, relatório de inadimplência quebrado

#### 6.3.2. APIs que Ainda Falam com Estrutura Legada

✅ **CORRIGIDO:**
- `admin/api/pagamentos.php` usa `financeiro_faturas` (não mais `faturas` antiga)

⚠️ **FALLBACK:**
- `FinanceiroRulesService` tem fallback para tabela `faturas` antiga se `financeiro_faturas` não existir (linhas 120-132)

#### 6.3.3. Ausência de Validação Financeira em Módulos Críticos

✅ **IMPLEMENTADO:**
- Aulas práticas: Validação financeira implementada
- Aulas teóricas: Validação financeira implementada
- Exames: Validação financeira implementada

⚠️ **BUG:**
- `GuardsExames::verificarInadimplencia()` usa query incorreta

---

### 6.4. Sugestão de Caminho (Alto Nível)

#### 6.4.1. Padronizar Tudo em Torno de `financeiro_faturas` + `pagamentos`

✅ **JÁ ESTÁ ASSIM:**
- A maioria do código já usa `financeiro_faturas`
- Pagamentos já estão na tabela `pagamentos` relacionada a `financeiro_faturas`

**Ações Necessárias:**
1. Remover fallback para tabela `faturas` antiga (se não existir mais)
2. Garantir que todas as queries usem `data_vencimento` (não `vencimento`)

#### 6.4.2. Oficializar `alunos.inadimplente` e `alunos.inadimplente_desde` como Fonte de Verdade

**Ações Necessárias:**
1. Criar migration para adicionar colunas `inadimplente` e `inadimplente_desde` em `alunos`
2. Reativar `updateAlunoInadimplencia()` em todos os pontos (POST, PUT, DELETE)
3. Atualizar relatório de inadimplência para usar as colunas
4. Considerar usar as colunas para queries mais rápidas (opcional, não obrigatório)

**Vantagens:**
- Queries mais rápidas (não precisa calcular sempre)
- Relatórios mais simples
- Histórico de quando aluno ficou inadimplente

**Desvantagens:**
- Precisa manter sincronizado com `financeiro_faturas`
- Pode ter inconsistências se não atualizar corretamente

#### 6.4.3. Adicionar Campos de Pagamento em `financeiro_faturas` (Opcional)

**Considerações:**
- Se adicionar `data_pagamento` e `valor_pago`, precisa manter sincronizado com `pagamentos`
- Para pagamentos parciais, qual data usar? (primeira? última? todas?)
- Pode simplificar queries mas adiciona complexidade de sincronização

**Recomendação:**
- Manter `pagamentos` como tabela separada (permite múltiplos pagamentos)
- Adicionar campos calculados em `financeiro_faturas` apenas se necessário para performance

#### 6.4.4. Corrigir Bugs Identificados

**Ações Imediatas:**
1. Corrigir query em `GuardsExames::verificarInadimplencia()` (linha 86) - usar `"aberta"` ao invés de `"em_aberto"`
2. Decidir se vai criar colunas `inadimplente` ou manter verificação direta em `financeiro_faturas`

---

### 6.5. Conclusão

O sistema financeiro está **funcional para operações básicas** (criar, editar, listar faturas), mas tem **gaps importantes**:

1. ❌ **Inadimplência quebrada:** Colunas não existem, funções não podem executar
2. ⚠️ **Pagamentos funcionais mas não exibidos:** API funciona mas tela não mostra
3. ✅ **Bloqueios funcionais:** Agendamentos são bloqueados corretamente (mas com bug menor)
4. ⚠️ **Estrutura inconsistente:** Algumas queries usam `vencimento`, outras `data_vencimento`

**Prioridade de Correções:**
1. **ALTA:** Criar colunas `inadimplente` e `inadimplente_desde` ou remover referências
2. **MÉDIA:** Corrigir bug em `GuardsExames::verificarInadimplencia()`
3. **BAIXA:** Adicionar exibição de pagamentos na tela de faturas
4. **BAIXA:** Padronizar uso de `data_vencimento` (remover `vencimento`)

---

**Fim da Auditoria**

