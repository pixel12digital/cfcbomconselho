# FASE 2 – Saneamento Financeiro & Pagamentos

**Data de início:** 2025-01-28  
**Objetivo:** Corrigir inconsistências críticas no módulo financeiro (faturas e pagamentos) sem alterar layout ou comportamento visual. Foco em consistência de dados e correção estrutural.

---

## Objetivo da Fase 2

Corrigir os problemas P0/P1 identificados no Raio-X Técnico relacionados ao módulo financeiro, garantindo que:
1. API de pagamentos use corretamente a tabela `financeiro_faturas` ao invés de `faturas` (inexistente)
2. API de faturas use consistentemente o campo `data_vencimento` ao invés de `vencimento`
3. Tabela `financeiro_configuracoes` seja criada ou seu uso seja neutralizado com fallback seguro
4. APIs "clean" não tenham credenciais duplicadas hardcoded

**Foco:** Consistência de dados e integridade estrutural, sem mudanças visuais.

---

## Problemas P0/P1 Relacionados a Financeiro/Pagamentos

### P0 - Crítico (Do Raio-X)

| Problema | Arquivo | Linha | Descrição |
|----------|---------|-------|-----------|
| **API pagamentos usa tabela inexistente** | `admin/api/pagamentos.php` | 82, 126, 200, 228 | JOIN com `faturas` que não existe mais - sistema usa `financeiro_faturas` |
| **API financeiro-faturas usa campo errado** | `admin/api/financeiro-faturas.php` | 113, 118, 139, 165, 189, 230, 297, 344 | Usa `vencimento` ao invés de `data_vencimento` (campo oficial) |
| **Tabela financeiro_configuracoes não existe** | `admin/api/financeiro-faturas.php` | 336 | Query em tabela inexistente quebra cálculo de inadimplência |
| **Tabela financeiro_configuracoes não existe** | `admin/api/financeiro-relatorios.php` | 134 | Mesma query problemática |

### P1 - Alto (Do Raio-X)

| Problema | Arquivo | Linha | Descrição |
|----------|---------|-------|-----------|
| **Credenciais hardcoded duplicadas** | `admin/api/salas-clean.php` | 6-9 | Define DB_HOST, DB_NAME, DB_USER, DB_PASS diretamente |
| **Credenciais hardcoded duplicadas** | `admin/api/disciplinas-curso.php` | 19-22 | Define credenciais duplicadas |
| **Credenciais hardcoded duplicadas** | `admin/api/disciplinas-automaticas.php` | 34-37 | Define credenciais duplicadas |

---

## Checklist da Fase 2

- [ ] 1. Corrigir `admin/api/pagamentos.php` para usar `financeiro_faturas`
- [ ] 2. Corrigir `admin/api/financeiro-faturas.php` para usar `data_vencimento`
- [ ] 3. Resolver `financeiro_configuracoes` (criar tabela ou neutralizar uso)
- [ ] 4. Remover credenciais duplicadas em APIs "clean"
- [ ] 5. Atualizar `install.php` se necessário (para `financeiro_configuracoes`)
- [ ] 6. Validar que todas as correções funcionam

---

## 1. Correção: admin/api/pagamentos.php

### Problema Identificado

A API de pagamentos está usando JOIN com a tabela `faturas` que não existe mais. O sistema usa `financeiro_faturas` como tabela oficial.

**Arquivo:** `admin/api/pagamentos.php`  
**Linhas problemáticas:**
- Linha 82: `JOIN faturas f ON p.fatura_id = f.id`
- Linha 93: `JOIN faturas f ON p.fatura_id = f.id`
- Linha 126: `SELECT * FROM faturas WHERE id = ?`
- Linha 200: `SELECT * FROM faturas WHERE id = ?`
- Linha 228: `UPDATE faturas SET status = ?`

### Alterações Realizadas

#### 1.1. Função handleGet - Buscar pagamentos por fatura

**ANTES (Linha 79-85):**
```php
$pagamentos = $db->fetchAll("
    SELECT p.*, f.numero as fatura_numero, f.descricao as fatura_descricao
    FROM pagamentos p
    JOIN faturas f ON p.fatura_id = f.id
    WHERE p.fatura_id = ?
    ORDER BY p.data_pagamento DESC
", [$faturaId]);
```

**DEPOIS:**
```php
$pagamentos = $db->fetchAll("
    SELECT p.*, f.titulo as fatura_titulo, f.valor_total as fatura_valor_total,
           f.data_vencimento as fatura_data_vencimento
    FROM pagamentos p
    JOIN financeiro_faturas f ON p.fatura_id = f.id
    WHERE p.fatura_id = ?
    ORDER BY p.data_pagamento DESC
", [$faturaId]);
```

**Mudanças:**
- ✅ `faturas` → `financeiro_faturas`
- ✅ `f.numero` → `f.titulo` (campo que existe em `financeiro_faturas`)
- ✅ `f.descricao` → removido (não existe em `financeiro_faturas`)
- ✅ Adicionado `f.data_vencimento` e `f.valor_total` para contexto

#### 1.2. Função handleGet - Listar todos os pagamentos

**ANTES (Linha 90-97):**
```php
$pagamentos = $db->fetchAll("
    SELECT p.*, f.numero as fatura_numero, f.descricao as fatura_descricao, a.nome as aluno_nome
    FROM pagamentos p
    JOIN faturas f ON p.fatura_id = f.id
    JOIN alunos a ON f.aluno_id = a.id
    ORDER BY p.data_pagamento DESC
    LIMIT 100
");
```

**DEPOIS:**
```php
$pagamentos = $db->fetchAll("
    SELECT p.*, f.titulo as fatura_titulo, f.valor_total as fatura_valor_total,
           f.data_vencimento as fatura_data_vencimento, a.nome as aluno_nome
    FROM pagamentos p
    JOIN financeiro_faturas f ON p.fatura_id = f.id
    JOIN alunos a ON f.aluno_id = a.id
    ORDER BY p.data_pagamento DESC
    LIMIT 100
");
```

**Mudanças:**
- ✅ `faturas` → `financeiro_faturas`
- ✅ Ajustados campos retornados para corresponder à estrutura real

#### 1.3. Função handlePost - Verificar se fatura existe

**ANTES (Linha 126):**
```php
$fatura = $db->fetch("SELECT * FROM faturas WHERE id = ?", [$input['fatura_id']]);
```

**DEPOIS:**
```php
$fatura = $db->fetch("SELECT * FROM financeiro_faturas WHERE id = ?", [$input['fatura_id']]);
```

#### 1.4. Função recalcularStatusFatura - Buscar fatura

**ANTES (Linha 200):**
```php
$fatura = $db->fetch("SELECT * FROM faturas WHERE id = ?", [$faturaId]);
```

**DEPOIS:**
```php
$fatura = $db->fetch("SELECT * FROM financeiro_faturas WHERE id = ?", [$faturaId]);
```

#### 1.5. Função recalcularStatusFatura - Calcular status e atualizar

**ANTES (Linhas 210-228):**
```php
$valorLiquido = (float)$fatura['valor_liquido'];
// ...
if ($fatura['vencimento'] < date('Y-m-d')) {
    $novoStatus = 'vencida';
}
// ...
$db->update('faturas', ['status' => $novoStatus], 'id = ?', [$faturaId]);
```

**DEPOIS:**
```php
$valorLiquido = (float)($fatura['valor_total'] ?? $fatura['valor'] ?? 0);
// ...
if (($fatura['data_vencimento'] ?? $fatura['vencimento'] ?? null) < date('Y-m-d')) {
    $novoStatus = 'vencida';
}
// ...
$db->update('financeiro_faturas', ['status' => $novoStatus], 'id = ?', [$faturaId]);
```

**Mudanças:**
- ✅ `faturas` → `financeiro_faturas`
- ✅ `valor_liquido` → `valor_total` (campo que existe)
- ✅ `vencimento` → `data_vencimento` (com fallback para compatibilidade)

### Observações de Compatibilidade

- ✅ `pagamentos.fatura_id` já referencia corretamente `financeiro_faturas.id` (FK existe ou será criada)
- ✅ Campos retornados ajustados: `fatura_numero`/`fatura_descricao` → `fatura_titulo`/`fatura_valor_total`
- ✅ Função `recalcularStatusFatura()` ajustada para usar `valor_total` (campo existente) ao invés de `valor_liquido`
- ✅ Campo `data_vencimento` usado com fallback para `vencimento` quando necessário (compatibilidade durante transição)
- ⚠️ **Impacto:** Páginas que usam `fatura_numero` ou `fatura_descricao` precisarão ajustar para `fatura_titulo` (mas isso não é desta fase)

---

## 2. Correção: admin/api/financeiro-faturas.php

### Problema Identificado

A API usa campo `vencimento` ao invés de `data_vencimento` (campo oficial usado em páginas e migrations).

**Arquivo:** `admin/api/financeiro-faturas.php`  
**Linhas problemáticas:**
- Linha 113: `f.vencimento >= ?`
- Linha 118: `f.vencimento <= ?`
- Linha 139: `ORDER BY f.vencimento DESC`
- Linha 165: `'vencimento'` no required
- Linha 189: `'vencimento' => $input['vencimento']`
- Linha 230: `'vencimento'` no allowedFields
- Linha 297: `ORDER BY f.vencimento DESC`
- Linha 344: `AND vencimento < DATE_SUB(...)`

### Alterações Realizadas

#### 2.1. Função handleGet - Filtros de data

**ANTES (Linhas 112-120):**
```php
if ($data_inicio) {
    $where[] = 'f.vencimento >= ?';
    $params[] = $data_inicio;
}

if ($data_fim) {
    $where[] = 'f.vencimento <= ?';
    $params[] = $data_fim;
}
```

**DEPOIS (Linhas 112-120):**
```php
if ($data_inicio) {
    $where[] = 'f.data_vencimento >= ?';  // ✅ Campo oficial
    $params[] = $data_inicio;
}

if ($data_fim) {
    $where[] = 'f.data_vencimento <= ?';  // ✅ Campo oficial
    $params[] = $data_fim;
}
```

#### 2.2. Função handleGet - ORDER BY

**ANTES (Linha 139):**
```php
ORDER BY f.vencimento DESC, f.criado_em DESC
```

**DEPOIS:**
```php
ORDER BY f.data_vencimento DESC, f.criado_em DESC
```

#### 2.3. Função handlePost - Validação e INSERT

**ANTES (Linhas 165, 189):**
```php
$required = ['aluno_id', 'titulo', 'valor_total', 'vencimento'];
// ...
'vencimento' => $input['vencimento'],
```

**DEPOIS:**
```php
$required = ['aluno_id', 'titulo', 'valor_total', 'data_vencimento'];
// ...
'data_vencimento' => $input['data_vencimento'] ?? $input['vencimento'] ?? null,
// Manter vencimento para compatibilidade se fornecido
'vencimento' => $input['data_vencimento'] ?? $input['vencimento'] ?? null,
```

**Nota:** Inserir ambos os campos para compatibilidade durante transição.

#### 2.4. Função handlePut - Campos editáveis

**ANTES (Linha 230):**
```php
$allowedFields = ['titulo', 'valor_total', 'status', 'vencimento', 'forma_pagamento', 'observacoes'];
```

**DEPOIS:**
```php
$allowedFields = ['titulo', 'valor_total', 'status', 'data_vencimento', 'vencimento', 'forma_pagamento', 'observacoes'];
```

**Nota:** Aceitar ambos os campos, mas priorizar `data_vencimento`.

#### 2.5. Função exportCSV - ORDER BY

**ANTES (Linha 297):**
```php
ORDER BY f.vencimento DESC
```

**DEPOIS:**
```php
ORDER BY f.data_vencimento DESC, f.vencimento DESC
```

#### 2.6. Função updateAlunoInadimplencia - WHERE

**ANTES (Linha 344):**
```php
AND vencimento < DATE_SUB(NOW(), INTERVAL ? DAY)
```

**DEPOIS:**
```php
AND data_vencimento < DATE_SUB(NOW(), INTERVAL ? DAY)
```

### Lista de Trechos Alterados

| Função | Linha Original | Campo Antes | Campo Depois | Observação |
|--------|---------------|-------------|--------------|------------|
| `handleGet` - filtro inicio | 113 | `f.vencimento` | `f.data_vencimento` | Filtro de período |
| `handleGet` - filtro fim | 118 | `f.vencimento` | `f.data_vencimento` | Filtro de período |
| `handleGet` - ORDER BY | 139 | `f.vencimento` | `f.data_vencimento` | Ordenação |
| `handlePost` - required | 165 | `'vencimento'` | `'data_vencimento'` | Validação |
| `handlePost` - INSERT | 189 | `'vencimento'` | `'data_vencimento'` | Inserção |
| `handlePut` - allowedFields | 230 | `'vencimento'` | `'data_vencimento'` | Validação |
| `exportCSV` - ORDER BY | 297 | `f.vencimento` | `f.data_vencimento` | Ordenação |
| `updateAlunoInadimplencia` - WHERE | 344 | `vencimento` | `data_vencimento` | Filtro de vencimento |

### Como Ficou o Filtro de Datas Após Correção

**Filtro de período (GET):**
```php
if ($data_inicio) {
    $where[] = 'f.data_vencimento >= ?';  // ✅ Campo oficial
    $params[] = $data_inicio;
}

if ($data_fim) {
    $where[] = 'f.data_vencimento <= ?';  // ✅ Campo oficial
    $params[] = $data_fim;
}
```

**Filtro de vencidas (updateAlunoInadimplencia):**
```php
$faturasVencidas = $db->fetchColumn("
    SELECT COUNT(*) 
    FROM financeiro_faturas 
    WHERE aluno_id = ? 
    AND status IN ('aberta', 'vencida') 
    AND data_vencimento < DATE_SUB(NOW(), INTERVAL ? DAY)  // ✅ Campo oficial
", [$alunoId, $diasInadimplencia]);
```

---

## 3. Resolução: financeiro_configuracoes

### Análise do Uso

**Arquivos que usam:**
1. `admin/api/financeiro-faturas.php:336` - Busca configuração `dias_inadimplencia`
2. `admin/api/financeiro-relatorios.php:134` - Mesma busca

**Uso atual:**
```php
$config = $db->fetch("SELECT valor FROM financeiro_configuracoes WHERE chave = 'dias_inadimplencia'");
$diasInadimplencia = $config ? (int)$config['valor'] : 30;  // Fallback para 30 dias
```

**Decisão:** Criar tabela com estrutura mínima, pois:
- ✅ É usada para configuração importante (dias de inadimplência)
- ✅ Já existe fallback seguro (30 dias) se tabela não existir
- ✅ Estrutura simples (chave-valor)
- ✅ Pode ser expandida futuramente

### Migração Criada

**Arquivo:** `admin/migrations/008-create-financeiro-configuracoes-structure.sql`

```sql
-- =====================================================
-- MIGRAÇÃO: Estrutura da Tabela Financeiro Configurações
-- Versão: 1.0
-- Data: 2025-01-28 (Fase 2)
-- Autor: Sistema CFC Bom Conselho
-- 
-- NOTA: Tabela para armazenar configurações do módulo financeiro
-- Baseada em: admin/api/financeiro-faturas.php:336, admin/api/financeiro-relatorios.php:134
-- =====================================================

-- Tabela de Configurações Financeiras
CREATE TABLE IF NOT EXISTS financeiro_configuracoes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    chave VARCHAR(100) NOT NULL UNIQUE,
    valor VARCHAR(255) NOT NULL,
    descricao VARCHAR(255) DEFAULT NULL,
    tipo ENUM('texto', 'numero', 'booleano', 'data') DEFAULT 'texto',
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_chave (chave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inserir configuração padrão
INSERT IGNORE INTO financeiro_configuracoes (chave, valor, descricao, tipo) VALUES
('dias_inadimplencia', '30', 'Número de dias após vencimento para considerar inadimplente', 'numero');

-- Comentários
ALTER TABLE financeiro_configuracoes 
    MODIFY COLUMN chave VARCHAR(100) 
    COMMENT 'Chave única da configuração (ex: dias_inadimplencia)';
    
ALTER TABLE financeiro_configuracoes 
    MODIFY COLUMN valor VARCHAR(255) 
    COMMENT 'Valor da configuração (armazenado como string, converter conforme tipo)';
```

### Atualização do install.php

Adicionada criação da tabela em `install.php` seguindo o padrão das demais tabelas.

### Abordagem Adotada

✅ **Criar tabela** com estrutura simples (chave-valor)  
✅ **Inserir valor padrão** (`dias_inadimplencia = 30`)  
✅ **Manter fallback** no código para segurança (se tabela não existir, usa 30)

### Trechos de Código que Usam

1. **`admin/api/financeiro-faturas.php:336`**
   ```php
   $config = $db->fetch("SELECT valor FROM financeiro_configuracoes WHERE chave = 'dias_inadimplencia'");
   $diasInadimplencia = $config ? (int)$config['valor'] : 30;
   ```

2. **`admin/api/financeiro-relatorios.php:134`**
   ```php
   $config = $db->fetch("SELECT valor FROM financeiro_configuracoes WHERE chave = 'dias_inadimplencia'");
   $diasInadimplencia = $config ? (int)$config['valor'] : 30;
   ```

**Status:** ✅ Tabela criada e código funciona com fallback seguro

---

## 4. Remoção de Credenciais Duplicadas

### Arquivos Identificados

| Arquivo | Linhas | Problema |
|---------|--------|----------|
| `admin/api/salas-clean.php` | 6-9 | Define `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS` diretamente |
| `admin/api/disciplinas-curso.php` | 19-22 | Define credenciais duplicadas |
| `admin/api/disciplinas-automaticas.php` | 34-37 | Define credenciais duplicadas |
| `admin/api/disciplinas-clean.php` | Usa `DB_HOST` mas não define | Usa constantes do `config.php` (OK) |
| `admin/api/tipos-curso-clean.php` | Usa `DB_HOST` mas não define | Usa constantes do `config.php` (OK) |

### Alterações Realizadas

#### 4.1. admin/api/salas-clean.php

**ANTES (Linhas 6-10):**
```php
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');
```

**DEPOIS:**
```php
// Credenciais removidas - usar includes/config.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
```

**E alterar função conectarBanco:**
```php
function conectarBanco() {
    // Usar Database::getInstance() ao invés de criar PDO diretamente
    return Database::getInstance()->getConnection();
}
```

#### 4.2. admin/api/disciplinas-curso.php

**ANTES (Linhas 19-22):**
```php
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');
```

**DEPOIS:**
```php
// Credenciais removidas - usar includes/config.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
```

#### 4.3. admin/api/disciplinas-automaticas.php

**ANTES (Linhas 34-37):**
```php
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');
```

**DEPOIS:**
```php
// Credenciais removidas - usar includes/config.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
```

### Lista de Arquivos Corrigidos

| Arquivo | Status | Observação |
|---------|--------|------------|
| `admin/api/salas-clean.php` | ✅ Corrigido | Credenciais removidas (linhas 6-9), usa `includes/config.php` e `Database::getInstance()` |
| `admin/api/disciplinas-curso.php` | ✅ Corrigido | Credenciais removidas (linhas 19-22), usa `includes/config.php` e `Database::getInstance()` |
| `admin/api/disciplinas-automaticas.php` | ✅ Corrigido | Credenciais removidas (linhas 34-37), usa `includes/config.php` e `Database::getInstance()` |
| `admin/api/disciplinas-clean.php` | ✅ OK | Já usa `includes/config.php` (não tinha credenciais duplicadas) |
| `admin/api/tipos-curso-clean.php` | ✅ OK | Já usa `includes/config.php` (não tinha credenciais duplicadas) |

### Alterações Realizadas

**ANTES (salas-clean.php linhas 6-9):**
```php
define('DB_HOST', 'auth-db803.hstgr.io');
define('DB_NAME', 'u502697186_cfcbomconselho');
define('DB_USER', 'u502697186_cfcbomconselho');
define('DB_PASS', 'Los@ngo#081081');
```

**DEPOIS:**
```php
// Credenciais removidas - usar includes/config.php
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
```

**E função conectarBanco() atualizada:**
```php
function conectarBanco() {
    try {
        $db = Database::getInstance();
        return $db->getConnection();
    } catch (Exception $e) {
        return null;
    }
}
```

**Mesma correção aplicada em:**
- `admin/api/disciplinas-curso.php` (linhas 18-30)
- `admin/api/disciplinas-automaticas.php` (linhas 33-45)

### Confirmação

✅ **Todas as APIs "clean" agora usam `includes/config.php`**  
✅ **Nenhuma API tem credenciais hardcoded duplicadas**  
✅ **Função `conectarBanco()` padronizada para usar `Database::getInstance()`**  
⚠️ **Nota:** `includes/config.php` ainda tem credenciais hardcoded (P0 para fase futura - não é desta fase)

---

## 5. Checklist Final e Validação

### Checklist de Correções

- [x] **admin/api/pagamentos.php usando financeiro_faturas**
  - [x] JOIN corrigido em `handleGet` - buscar por fatura (linha 82-83)
  - [x] JOIN corrigido em `handleGet` - listar todos (linha 95)
  - [x] SELECT corrigido em `handlePost` - verificar fatura (linha 128)
  - [x] SELECT corrigido em `recalcularStatusFatura` - buscar fatura (linha 202)
  - [x] UPDATE corrigido em `recalcularStatusFatura` - atualizar status (linha 231)
  - [x] Campos retornados ajustados: `fatura_numero`/`fatura_descricao` → `fatura_titulo`/`fatura_valor_total`/`fatura_data_vencimento`

- [x] **JOIN e selects ajustados**
  - [x] Campos retornados ajustados para estrutura de `financeiro_faturas`
  - [x] `fatura_numero`/`fatura_descricao` → `fatura_titulo`/`fatura_valor_total`

- [x] **admin/api/financeiro-faturas.php usando apenas data_vencimento**
  - [x] Filtros de data corrigidos - `handleGet` (linhas 113, 118)
  - [x] ORDER BY corrigido - `handleGet` (linha 139)
  - [x] Validação POST corrigida - `handlePost` (linha 165-177, aceita ambos campos para compatibilidade)
  - [x] INSERT POST corrigido - `handlePost` (linhas 187-202, insere ambos campos para compatibilidade)
  - [x] Campos editáveis PUT corrigidos - `handlePut` (linhas 238-253, sincroniza ambos campos)
  - [x] Export CSV corrigido - `exportCSV` (linha 313 ORDER BY, linha 339 exibição)
  - [x] updateAlunoInadimplencia corrigido - WHERE clause (linha 366, usa `data_vencimento`)
  - [x] Fallback seguro adicionado - `updateAlunoInadimplencia` (linhas 353-359, try-catch para tabela não existente)

- [x] **Situação de financeiro_configuracoes resolvida**
  - [x] Migração criada: `admin/migrations/008-create-financeiro-configuracoes-structure.sql`
  - [x] Tabela criada com estrutura chave-valor (tipo ENUM para extensibilidade)
  - [x] Valor padrão inserido via INSERT IGNORE (`dias_inadimplencia = 30`)
  - [x] `install.php` atualizado para criar tabela (linhas 276-288)
  - [x] `install.php` atualizado para inserir valor padrão (linhas 331-345)
  - [x] Fallback mantido no código com try-catch:
    - `admin/api/financeiro-faturas.php:353-359` - `updateAlunoInadimplencia()`
    - `admin/api/financeiro-relatorios.php:135-141` - `getInadimplencia()`

- [x] **APIs "clean" sem credenciais duplicadas**
  - [x] `admin/api/salas-clean.php` corrigido
  - [x] `admin/api/disciplinas-curso.php` corrigido
  - [x] `admin/api/disciplinas-automaticas.php` corrigido
  - [x] Outras APIs "clean" já estavam OK

### Testes Manuais Básicos

#### Teste 1: Criar Fatura
1. Acessar página de faturas
2. Criar nova fatura
3. **Validar:** Fatura criada com `data_vencimento` preenchido
4. **Validar:** Verificar no banco que campo `data_vencimento` foi preenchido

#### Teste 2: Marcar como Paga (via API/página)
1. Registrar pagamento para uma fatura
2. **Validar:** Status da fatura atualizado corretamente
3. **Validar:** Pagamento registrado com `fatura_id` correto
4. **Validar:** Relação entre `pagamentos.fatura_id` e `financeiro_faturas.id` funciona

#### Teste 3: Listar Pagamentos
1. Acessar API: `GET /admin/api/pagamentos.php?fatura_id=X`
2. **Validar:** Retorna pagamentos da fatura correta
3. **Validar:** Campos retornados incluem `fatura_titulo`, `fatura_valor_total`
4. **Validar:** Não há erro de JOIN (tabela `faturas` não existe mais)

#### Teste 4: Filtros de Faturas por Vencimento
1. Acessar API: `GET /admin/api/financeiro-faturas.php?data_inicio=2025-01-01&data_fim=2025-12-31`
2. **Validar:** Retorna faturas no período correto
3. **Validar:** Usa campo `data_vencimento` para filtro

#### Teste 5: Job de Vencidas Funcionando
1. Executar `admin/jobs/marcar_faturas_vencidas.php`
2. **Validar:** Faturas vencidas marcadas corretamente
3. **Validar:** Usa tabela `financeiro_faturas` e campo `data_vencimento`
4. **Validar:** Configuração `dias_inadimplencia` é lida de `financeiro_configuracoes` (ou usa 30 como fallback)

### Observações Finais

✅ **Nenhuma alteração visual foi feita** - Foco em correções estruturais  
✅ **Compatibilidade mantida** - Campos antigos (`vencimento`) ainda são aceitos e sincronizados com `data_vencimento`  
✅ **Fallbacks seguros implementados** - Tabela `financeiro_configuracoes` não quebra o sistema se não existir  
✅ **Credenciais duplicadas removidas** - 3 APIs "clean" agora usam `includes/config.php`  

⚠️ **Próximos passos recomendados (Fases Futuras):**
- Remover campo `vencimento` da tabela após confirmar que tudo usa `data_vencimento` (fase futura)
- Migrar credenciais de `includes/config.php` para variáveis de ambiente (fase futura)
- Verificar se páginas que usam `fatura_numero`/`fatura_descricao` precisam ajustar para `fatura_titulo`

### Arquivos Modificados nesta Fase

1. ✅ `admin/api/pagamentos.php` - Correção de JOINs e queries
2. ✅ `admin/api/financeiro-faturas.php` - Correção de campos de vencimento
3. ✅ `admin/api/financeiro-relatorios.php` - Fallback seguro para `financeiro_configuracoes`
4. ✅ `admin/api/salas-clean.php` - Remoção de credenciais duplicadas
5. ✅ `admin/api/disciplinas-curso.php` - Remoção de credenciais duplicadas
6. ✅ `admin/api/disciplinas-automaticas.php` - Remoção de credenciais duplicadas
7. ✅ `admin/migrations/008-create-financeiro-configuracoes-structure.sql` - Criação de migration
8. ✅ `install.php` - Adição de tabela `financeiro_configuracoes` e inserção de valor padrão

### Arquivos Criados nesta Fase

1. ✅ `admin/pages/_FASE-2-FINANCEIRO-E-PAGAMENTOS.md` - Documentação completa da fase

---

**Fim da Fase 2**

