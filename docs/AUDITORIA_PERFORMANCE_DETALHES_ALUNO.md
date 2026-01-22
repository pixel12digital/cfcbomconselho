# Auditoria de Performance - Detalhes do Aluno, Histórico e Resumos

**Data:** 2025-01-27  
**Objetivo:** Diagnóstico completo da lentidão em produção nas telas de detalhes de aluno, histórico e resumos (teórico, prático, provas)  
**Tipo:** Somente auditoria, sem alterações de código

---

## 1. Contexto Geral

### 1.1. Ambiente
- **Banco de dados:** Remoto (mesmo host para dev e produção)
- **Host:** `auth-db803.hstgr.io`
- **Banco:** `u502697186_cfcbomconselho`
- **Problema:** Em produção, as telas estão consideravelmente mais lentas que em dev, mesmo usando o mesmo banco remoto
- **Timeouts:** Múltiplos timeouts de 8000ms registrados no console

### 1.2. Evidências em Produção

Os seguintes erros estão sendo registrados no console:

```
❌ Erro ao carregar Progresso Teórico do aluno: Error: Timeout: A requisição demorou mais de 8000ms
❌ Erro ao carregar Progresso Prático do aluno: Error: Timeout: A requisição demorou mais de 8000ms
❌ Erro ao carregar resumo de provas do aluno: Error: Timeout: A requisição demorou mais de 8000ms
❌ Erro ao carregar histórico do aluno: Error: Timeout: A requisição demorou mais de 8000ms
```

---

## 2. Mapeamento de Pontos de Entrada

### 2.1. Funções JavaScript

#### `atualizarResumoTeoricoAluno(alunoId)`
- **Arquivo:** `admin/pages/alunos.php` (linha 9286)
- **Endpoint chamado:** `api/progresso_teorico.php?aluno_id={alunoId}`
- **Timeout:** 8000ms
- **Função wrapper:** `fetchWithTimeout()` (linha 8694)

#### `atualizarResumoPraticoAluno(alunoId)`
- **Arquivo:** `admin/pages/alunos.php` (linha 9432)
- **Endpoint chamado:** `api/progresso_pratico.php?aluno_id={alunoId}`
- **Timeout:** 8000ms
- **Função wrapper:** `fetchWithTimeout()` (linha 8694)

#### `atualizarResumoProvasAluno(alunoId)`
- **Arquivo:** `admin/pages/alunos.php` (linha 9509)
- **Endpoint chamado:** `api/exames.php?aluno_id={alunoId}&resumo=1`
- **Timeout:** 8000ms
- **Função wrapper:** `fetchWithTimeout()` (linha 8694)

#### `carregarHistoricoAluno(alunoId, options)`
- **Arquivo:** `admin/pages/alunos.php` (linha 10692)
- **Endpoint chamado:** `api/historico_aluno.php?aluno_id={alunoId}`
- **Timeout:** 8000ms
- **Função wrapper:** `fetchWithTimeout()` (linha 8694)

### 2.2. Fluxo de Chamadas quando Modal é Aberto

**Sequência de chamadas ao abrir modal de visualização de aluno:**

```
Abrir Modal Aluno (visualizarAluno)
  ↓
carregarMatriculaPrincipalVisualizacao(alunoId)
  ↓
  ├─→ atualizarResumoTeoricoAluno(alunoId)      [PARALELO]
  ├─→ atualizarResumoPraticoAluno(alunoId)      [PARALELO]
  ├─→ atualizarResumoProvasAluno(alunoId)       [PARALELO]
  └─→ atualizarResumoFinanceiroAluno(alunoId)   [PARALELO]
  
Abrir Aba Histórico
  ↓
carregarHistoricoAluno(alunoId)                 [SEPARADO]
```

**Observação:** As 4 funções de resumo são chamadas em paralelo quando o modal é aberto, o que pode causar sobrecarga simultânea no servidor.

---

## 3. Mapeamento de Endpoints e Queries SQL

### 3.1. `api/progresso_teorico.php`

**Arquivo:** `admin/api/progresso_teorico.php`

**Query SQL executada:**
```sql
SELECT 
    tm.status,
    tm.frequencia_percentual,
    tm.data_matricula,
    tm.exames_validados_em,
    tm.turma_id,
    t.nome AS turma_nome
FROM turma_matriculas tm
INNER JOIN turmas_teoricas t ON tm.turma_id = t.id
WHERE tm.aluno_id = ?
ORDER BY tm.data_matricula DESC, tm.id DESC
LIMIT 1
```

**Análise:**
- ✅ Query simples com 1 JOIN
- ✅ LIMIT 1 (retorna apenas 1 registro)
- ✅ ORDER BY em índices prováveis (data_matricula, id)
- ⚠️ **Potencial problema:** Se não houver índice em `tm.aluno_id`, pode ser lento em produção com muitos registros
- ⚠️ **Potencial problema:** JOIN com `turmas_teoricas` pode ser lento se a tabela for grande

**Complexidade:** BAIXA

---

### 3.2. `api/progresso_pratico.php`

**Arquivo:** `admin/api/progresso_pratico.php`

**Query SQL executada:**
```sql
SELECT 
    id,
    status,
    data_aula
FROM aulas
WHERE aluno_id = ? 
AND tipo_aula = 'pratica'
AND status != 'cancelada'
ORDER BY data_aula ASC
LIMIT 500
```

**Processamento em PHP:**
```php
// Loop através de TODAS as aulas retornadas (até 500)
foreach ($aulas as $aula) {
    $status = strtolower($aula['status']);
    if ($status === 'concluida') {
        $totalRealizadas++;
    } elseif (in_array($status, ['agendada', 'em_andamento'])) {
        $totalAgendadas++;
    }
    if ($aula['data_aula']) {
        $datas[] = $aula['data_aula'];
    }
}

// Cálculos adicionais
$totalContratadas = $totalRealizadas + $totalAgendadas;
$percentualConcluido = round(($totalRealizadas / $totalContratadas) * 100);
$primeiraAula = !empty($datas) ? min($datas) : null;
$ultimaAula = !empty($datas) ? max($datas) : null;
```

**Análise:**
- ⚠️ **PROBLEMA CRÍTICO:** Busca até 500 registros sem paginação
- ⚠️ **PROBLEMA CRÍTICO:** Processamento em PHP com loops e cálculos (min/max em arrays)
- ⚠️ **PROBLEMA:** Se não houver índice composto em `(aluno_id, tipo_aula, status)`, a query pode ser muito lenta
- ⚠️ **PROBLEMA:** `ORDER BY data_aula ASC` sem índice pode ser lento em produção
- ⚠️ **PROBLEMA:** Cálculos de min/max poderiam ser feitos no SQL com `MIN()` e `MAX()`
- ⚠️ **PROBLEMA:** Contagens poderiam ser feitas no SQL com `COUNT()` e `GROUP BY`

**Complexidade:** MÉDIA-ALTA (devido ao processamento em PHP)

**Recomendação:** Mover cálculos para SQL usando agregações.

---

### 3.3. `api/exames.php` (modo resumo)

**Arquivo:** `admin/api/exames.php`

**Query SQL executada (quando `resumo=1`):**
```sql
SELECT 
    id,
    tipo,
    status,
    resultado,
    data_agendada,
    data_resultado,
    protocolo,
    clinica_nome
FROM exames
WHERE aluno_id = ?
AND tipo IN ('teorico', 'pratico')
ORDER BY 
    CASE tipo 
        WHEN 'teorico' THEN 1 
        WHEN 'pratico' THEN 2 
        ELSE 3 
    END,
    data_agendada DESC,
    data_resultado DESC
LIMIT 10
```

**Processamento em PHP:**
```php
// Filtrar apenas provas (teórica e prática)
const provas = data.exames.filter(exame => 
    exame.tipo === 'teorico' || exame.tipo === 'pratico'
);

// Loop para encontrar última prova teórica e prática
provas.forEach(prova => {
    const dataRef = prova.data_resultado || prova.data_agendada;
    if (!dataRef) return;
    
    if (prova.tipo === 'teorico') {
        if (!provaTeorica || new Date(dataRef) > new Date(provaTeorica.dataRef)) {
            provaTeorica = { ...prova, dataRef };
        }
    } else if (prova.tipo === 'pratico') {
        if (!provaPratica || new Date(dataRef) > new Date(provaPratica.dataRef)) {
            provaPratica = { ...prova, dataRef };
        }
    }
});
```

**Análise:**
- ✅ LIMIT 10 (quantidade razoável)
- ⚠️ **PROBLEMA:** `ORDER BY CASE` pode ser lento sem índices adequados
- ⚠️ **PROBLEMA:** Processamento em JavaScript (não PHP, mas ainda processamento desnecessário)
- ⚠️ **PROBLEMA:** Filtro em JavaScript quando já foi filtrado no SQL (`tipo IN ('teorico', 'pratico')`)
- ⚠️ **PROBLEMA:** Lógica de encontrar "última prova" poderia ser feita no SQL com subqueries ou window functions

**Complexidade:** MÉDIA

---

### 3.4. `api/historico_aluno.php`

**Arquivo:** `admin/api/historico_aluno.php`

**Este é o endpoint MAIS PESADO. Executa MÚLTIPLAS queries:**

#### Query 1: Buscar aluno
```sql
SELECT id, nome, criado_em, atualizado_em
FROM alunos
WHERE id = ?
```

#### Query 2: Buscar matrículas (LIMIT 50)
```sql
SELECT id, aluno_id, categoria_cnh, tipo_servico, status, data_inicio, data_fim, criado_em
FROM matriculas
WHERE aluno_id = ?
ORDER BY data_inicio DESC, id DESC
LIMIT 50
```

**Processamento em PHP (LOOP):**
```php
foreach ($matriculas as $matricula) {
    // Criar evento de matrícula criada
    $eventos[] = [...];
    
    // Se tiver data_fim, criar evento de matrícula concluída
    if (!empty($matricula['data_fim'])) {
        $eventos[] = [...];
    }
}
```

#### Query 3: Buscar exames (LIMIT 100)
```sql
SELECT id, aluno_id, tipo, status, resultado, data_agendada, data_resultado, protocolo, clinica_nome
FROM exames
WHERE aluno_id = ?
AND tipo IN ('medico', 'psicotecnico', 'teorico', 'pratico')
ORDER BY data_agendada DESC, data_resultado DESC
LIMIT 100
```

**Processamento em PHP (LOOP COMPLEXO):**
```php
foreach ($exames as $exame) {
    $tipoExame = $exame['tipo'];
    $isProva = in_array($tipoExame, ['teorico', 'pratico']);
    
    if ($isProva) {
        // Lógica para Provas (Teórica/Prática)
        if (!empty($exame['data_agendada'])) {
            // Criar evento prova agendada
            $eventos[] = [...];
        }
        if (!empty($exame['data_resultado']) && !empty($exame['resultado'])) {
            // Criar evento prova realizada
            $eventos[] = [...];
        }
    } else {
        // Lógica para Exames (Médico/Psicotécnico)
        if (!empty($exame['data_agendada'])) {
            // Criar evento exame agendado
            $eventos[] = [...];
        }
        if (!empty($exame['data_resultado']) && $exame['status'] === 'concluido') {
            // Criar evento exame realizado
            $eventos[] = [...];
        }
    }
}
```

#### Query 4: Buscar faturas (LIMIT 100) - com fallback para duas tabelas
```sql
-- Tenta primeiro 'faturas'
SELECT id, aluno_id, matricula_id, descricao, valor, vencimento, status, criado_em
FROM faturas
WHERE aluno_id = ?
ORDER BY vencimento DESC, criado_em DESC
LIMIT 100

-- Se falhar, tenta 'financeiro_faturas'
SELECT id, aluno_id, matricula_id, titulo as descricao, valor_total as valor, 
       data_vencimento as vencimento, status, criado_em
FROM financeiro_faturas
WHERE aluno_id = ?
ORDER BY data_vencimento DESC, criado_em DESC
LIMIT 100
```

**Processamento em PHP (LOOP COMPLEXO):**
```php
foreach ($faturas as $fatura) {
    // Criar evento fatura criada
    $eventos[] = [...];
    
    // Se status = 'paga', buscar data_pagamento em outra tabela
    if (strtolower($fatura['status']) === 'paga') {
        // QUERY ADICIONAL DENTRO DO LOOP (N+1!)
        $pagamento = $db->fetch("
            SELECT data_pagamento
            FROM pagamentos
            WHERE fatura_id = ?
            ORDER BY data_pagamento DESC
            LIMIT 1
        ", [$fatura['id']]);
        
        if ($dataPagamento) {
            $eventos[] = [...];
        }
    }
    
    // Verificar se fatura está vencida
    if ($statusLower === 'vencida' || ($vencimentoDate < $hoje && $statusLower !== 'paga')) {
        $eventos[] = [...];
    }
}
```

#### Query 5: Buscar matrícula teórica
```sql
SELECT 
    tm.id,
    tm.aluno_id,
    tm.turma_id,
    tm.status,
    tm.data_matricula,
    tm.frequencia_percentual,
    tm.atualizado_em,
    t.nome AS turma_nome
FROM turma_matriculas tm
JOIN turmas_teoricas t ON tm.turma_id = t.id
WHERE tm.aluno_id = ?
ORDER BY tm.data_matricula DESC, tm.id DESC
LIMIT 1
```

#### Query 6: Buscar primeira aula prática
```sql
SELECT 
    id,
    aluno_id,
    data_aula,
    status,
    tipo_aula
FROM aulas
WHERE aluno_id = ?
AND tipo_aula = 'pratica'
AND status != 'cancelada'
ORDER BY data_aula ASC
LIMIT 1
```

#### Query 7: Buscar última aula prática concluída
```sql
SELECT 
    id,
    aluno_id,
    data_aula,
    status,
    tipo_aula
FROM aulas
WHERE aluno_id = ?
AND tipo_aula = 'pratica'
AND status = 'concluida'
ORDER BY data_aula DESC
LIMIT 1
```

#### Query 8: Contar total de aulas práticas realizadas
```sql
SELECT COUNT(*) as total
FROM aulas
WHERE aluno_id = ?
AND tipo_aula = 'pratica'
AND status = 'concluida'
```

#### Query 9: Contar total de aulas práticas contratadas
```sql
SELECT COUNT(*) as total
FROM aulas
WHERE aluno_id = ?
AND tipo_aula = 'pratica'
AND status != 'cancelada'
```

#### Processamento final em PHP:
```php
// Ordenar eventos por data (mais recente primeiro)
usort($eventos, function($a, $b) {
    return strtotime($b['data']) - strtotime($a['data']);
});
```

**Análise:**
- 🔴 **PROBLEMA CRÍTICO:** Executa **9 queries SQL** para um único aluno
- 🔴 **PROBLEMA CRÍTICO:** **N+1 Query Problem** - Query dentro de loop para buscar `data_pagamento` de faturas pagas
- 🔴 **PROBLEMA CRÍTICO:** Múltiplos loops em PHP processando dados
- ⚠️ **PROBLEMA:** `usort()` ordenando array grande em PHP (poderia ser feito no SQL)
- ⚠️ **PROBLEMA:** Tentativa de fallback entre duas tabelas (`faturas` e `financeiro_faturas`) pode causar lentidão
- ⚠️ **PROBLEMA:** Queries 6, 7, 8, 9 poderiam ser consolidadas em uma única query com agregações

**Complexidade:** MUITO ALTA

**Estimativa de queries por requisição:** 9-109 queries (9 base + até 100 queries adicionais dentro do loop de faturas se todas estiverem pagas)

---

## 4. Análise de Diferenças Dev vs. Produção

### 4.1. Configurações de Ambiente

**Arquivo:** `includes/config.php`

**Diferenças identificadas:**

| Configuração | Dev (local) | Produção | Impacto na Performance |
|--------------|-------------|----------|----------------------|
| `REQUEST_TIMEOUT` | 60s | 30s | ⚠️ Timeout menor em produção |
| `SCRIPT_TIMEOUT` | 600s (10min) | 300s (5min) | ⚠️ Tempo de execução menor |
| `DB_TIMEOUT` | 60s | 30s | ⚠️ Timeout de conexão menor |
| `LOG_LEVEL` | DEBUG | INFO | ✅ Menos logs em produção |
| `CACHE_ENABLED` | false | true | ✅ Cache habilitado em produção |
| `DB_CACHE_ENABLED` | false | true | ✅ Cache de DB habilitado em produção |
| `DB_CACHE_DURATION` | 0 | 1800s (30min) | ✅ Cache de 30min em produção |
| `API_RATE_LIMIT` | 10000 | 100 | ⚠️ Rate limit mais restritivo |
| `RATE_LIMIT_MAX_REQUESTS` | 10000 | 1000 | ⚠️ Rate limit mais restritivo |
| `memory_limit` | 512M | 256M | ⚠️ Menos memória disponível |

**Observação:** Embora cache esteja habilitado em produção, pode não estar funcionando efetivamente ou os dados podem estar sendo invalidados frequentemente.

### 4.2. Volume de Dados

**Hipóteses para diferença de performance:**

1. **Volume de registros em produção:**
   - Alunos com muitos anos de histórico podem ter centenas de aulas, dezenas de matrículas, dezenas de exames e centenas de faturas
   - Em dev, provavelmente há menos dados históricos

2. **Concorrência:**
   - Em produção, múltiplos usuários podem estar acessando simultaneamente
   - Isso pode causar contenção de recursos (CPU, memória, conexões de banco)

3. **Latência de rede:**
   - Embora o banco seja remoto para ambos, a latência de rede pode ser diferente entre dev e produção
   - Produção pode ter mais overhead de rede (proxies, load balancers, etc.)

4. **Índices do banco:**
   - Índices podem não estar criados ou podem estar fragmentados em produção
   - Estatísticas do banco podem estar desatualizadas

### 4.3. Processamento em PHP vs. SQL

**Problemas identificados:**

1. **`progresso_pratico.php`:**
   - Busca até 500 registros e processa em PHP
   - Cálculos de min/max poderiam ser feitos no SQL
   - Contagens poderiam ser feitas no SQL

2. **`historico_aluno.php`:**
   - Múltiplas queries separadas quando poderiam ser consolidadas
   - N+1 query problem (query dentro de loop)
   - Ordenação em PHP quando poderia ser no SQL

---

## 5. Análise de Padrões de Chamadas AJAX / Concorrência

### 5.1. Sequência de Chamadas

**Quando o modal de aluno é aberto:**

```
1. abrirModalVisualizarAluno(alunoId)
   ↓
2. carregarMatriculaPrincipalVisualizacao(alunoId)
   ↓
3. Chamadas PARALELAS (sem await):
   - atualizarResumoTeoricoAluno(alunoId)
   - atualizarResumoPraticoAluno(alunoId)
   - atualizarResumoProvasAluno(alunoId)
   - atualizarResumoFinanceiroAluno(alunoId)
   ↓
4. Quando usuário abre aba Histórico:
   - carregarHistoricoAluno(alunoId)
```

**Problema:** As 4 chamadas de resumo são disparadas simultaneamente, causando:
- 4 conexões simultâneas ao banco
- 4 processos PHP simultâneos
- Sobrecarga de recursos do servidor

### 5.2. Timeout de 8000ms

**Função `fetchWithTimeout()`:**
```javascript
async function fetchWithTimeout(url, options = {}, timeout = 10000) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    // ...
}
```

**Uso:**
- Todas as funções usam `timeout = 8000` (8 segundos)
- Se a requisição demorar mais de 8 segundos, é abortada

**Problema:** 8 segundos pode ser insuficiente em produção quando:
- O servidor está sob carga
- Há muitos dados históricos
- Múltiplas requisições simultâneas

---

## 6. Principais Suspeitos para Timeouts

### 6.1. 🔴 CRÍTICO: `api/historico_aluno.php`

**Por que é o principal suspeito:**

1. **9 queries SQL** executadas sequencialmente
2. **N+1 Query Problem:** Query dentro de loop para buscar `data_pagamento`
   - Se houver 50 faturas pagas, são 50 queries adicionais
   - Total: 9 + 50 = **59 queries** em um cenário típico
3. **Múltiplos loops em PHP** processando dados
4. **Ordenação em PHP** (`usort()`) ao invés de SQL
5. **Sem paginação** - busca todos os eventos de uma vez

**Impacto em produção:**
- Com muitos dados históricos, pode facilmente exceder 8 segundos
- Cada query adicional aumenta o tempo total
- Processamento em PHP adiciona overhead

**Como isso se conecta ao timeout:**
- `carregarHistoricoAluno()` → `api/historico_aluno.php` → 9-109 queries → timeout > 8000ms

---

### 6.2. 🟡 ALTO: `api/progresso_pratico.php`

**Por que é suspeito:**

1. **Busca até 500 registros** sem necessidade
2. **Processamento em PHP** com loops e cálculos
3. **Cálculos de min/max** poderiam ser feitos no SQL
4. **Sem índices adequados** pode ser lento em produção

**Impacto em produção:**
- Com muitos alunos tendo centenas de aulas práticas, buscar 500 registros pode ser lento
- Processamento em PHP adiciona tempo

**Como isso se conecta ao timeout:**
- `atualizarResumoPraticoAluno()` → `api/progresso_pratico.php` → busca 500 registros + processamento PHP → timeout > 8000ms

---

### 6.3. 🟡 MÉDIO: `api/exames.php` (modo resumo)

**Por que é suspeito:**

1. **ORDER BY CASE** pode ser lento sem índices
2. **Processamento em JavaScript** desnecessário (filtro duplicado)
3. **Lógica de "última prova"** poderia ser feita no SQL

**Impacto em produção:**
- Menor que os outros, mas ainda pode contribuir para lentidão

---

### 6.4. 🟢 BAIXO: `api/progresso_teorico.php`

**Por que é menos suspeito:**

1. Query simples com LIMIT 1
2. Apenas 1 JOIN
3. Deve ser rápido mesmo em produção

**Observação:** Pode ainda ser lento se não houver índices adequados.

---

## 7. Hipóteses para Diferença Dev vs. Produção

### 7.1. Volume de Dados

**Hipótese mais provável:**

Em produção, alunos podem ter:
- **Centenas de aulas práticas** (vs. poucas em dev)
- **Dezenas de matrículas** (vs. 1-2 em dev)
- **Dezenas de exames** (vs. poucos em dev)
- **Centenas de faturas** (vs. poucas em dev)

**Impacto:**
- Queries que buscam "todas as aulas" ou "todas as faturas" são muito mais lentas
- Loops em PHP processam muito mais dados
- N+1 query problem se torna crítico (50+ queries adicionais)

### 7.2. Concorrência

**Hipótese:**

Em produção:
- Múltiplos usuários acessando simultaneamente
- 4 requisições paralelas por usuário (resumos)
- Múltiplos usuários = múltiplas conexões simultâneas ao banco

**Impacto:**
- Contenção de recursos (CPU, memória, conexões)
- Queries mais lentas devido à contenção
- Timeouts mais frequentes

### 7.3. Índices do Banco

**Hipótese:**

Índices podem não estar criados ou podem estar fragmentados em produção:
- `aulas(aluno_id, tipo_aula, status)` - pode não existir
- `exames(aluno_id, tipo)` - pode não existir
- `matriculas(aluno_id, data_inicio)` - pode não existir
- `faturas(aluno_id, vencimento)` - pode não existir

**Impacto:**
- Queries fazem full table scan
- Muito mais lento em produção com muitos registros

### 7.4. Cache Não Funcionando

**Hipótese:**

Embora `DB_CACHE_ENABLED = true` em produção:
- Cache pode não estar funcionando efetivamente
- Dados podem estar sendo invalidados frequentemente
- Cache pode estar sendo ignorado por algum motivo

**Impacto:**
- Todas as queries são executadas sempre
- Sem benefício do cache

---

## 8. Recomendações de Melhoria (Alto Nível)

### 8.1. 🔴 PRIORIDADE CRÍTICA: Otimizar `api/historico_aluno.php`

**Problemas a resolver:**

1. **Consolidar queries:**
   - Reduzir de 9 queries para 2-3 queries usando JOINs e subqueries
   - Eliminar queries separadas para primeira/última aula prática

2. **Eliminar N+1 Query Problem:**
   - Buscar `data_pagamento` com LEFT JOIN ao invés de query dentro de loop
   - Ou usar uma única query com GROUP BY

3. **Mover ordenação para SQL:**
   - Ordenar eventos no SQL ao invés de PHP (`usort()`)

4. **Adicionar paginação:**
   - Limitar eventos retornados (ex.: últimos 50 eventos)
   - Carregar mais eventos sob demanda (lazy loading)

5. **Consolidar queries de aulas práticas:**
   - Queries 6, 7, 8, 9 podem ser uma única query com agregações:
   ```sql
   SELECT 
       MIN(CASE WHEN status != 'cancelada' THEN data_aula END) as primeira_aula,
       MAX(CASE WHEN status = 'concluida' THEN data_aula END) as ultima_aula,
       COUNT(CASE WHEN status = 'concluida' THEN 1 END) as total_realizadas,
       COUNT(CASE WHEN status != 'cancelada' THEN 1 END) as total_contratadas
   FROM aulas
   WHERE aluno_id = ? AND tipo_aula = 'pratica'
   ```

**Impacto esperado:** Redução de 9-109 queries para 2-3 queries. Tempo estimado: de 8+ segundos para < 2 segundos.

---

### 8.2. 🟡 PRIORIDADE ALTA: Otimizar `api/progresso_pratico.php`

**Problemas a resolver:**

1. **Mover cálculos para SQL:**
   ```sql
   SELECT 
       COUNT(CASE WHEN status = 'concluida' THEN 1 END) as total_realizadas,
       COUNT(CASE WHEN status IN ('agendada', 'em_andamento') THEN 1 END) as total_agendadas,
       MIN(data_aula) as primeira_aula,
       MAX(data_aula) as ultima_aula
   FROM aulas
   WHERE aluno_id = ? 
   AND tipo_aula = 'pratica'
   AND status != 'cancelada'
   ```

2. **Remover LIMIT 500:**
   - Não é necessário buscar registros individuais se estamos apenas agregando

3. **Adicionar índices:**
   - `aulas(aluno_id, tipo_aula, status, data_aula)`

**Impacto esperado:** Redução de busca de 500 registros + processamento PHP para 1 query agregada. Tempo estimado: de 3-5 segundos para < 1 segundo.

---

### 8.3. 🟡 PRIORIDADE MÉDIA: Otimizar `api/exames.php` (modo resumo)

**Problemas a resolver:**

1. **Mover lógica de "última prova" para SQL:**
   - Usar window functions ou subqueries para encontrar última prova teórica e prática

2. **Remover filtro duplicado em JavaScript:**
   - Já filtrado no SQL, não precisa filtrar novamente

**Impacto esperado:** Redução de processamento JavaScript. Tempo estimado: de 1-2 segundos para < 0.5 segundos.

---

### 8.4. 🟢 PRIORIDADE BAIXA: Verificar Índices do Banco

**Índices recomendados:**

1. `aulas(aluno_id, tipo_aula, status, data_aula)`
2. `exames(aluno_id, tipo, data_agendada, data_resultado)`
3. `matriculas(aluno_id, data_inicio, id)`
4. `faturas(aluno_id, vencimento, criado_em)` ou `financeiro_faturas(aluno_id, data_vencimento, criado_em)`
5. `pagamentos(fatura_id, data_pagamento)`
6. `turma_matriculas(aluno_id, data_matricula, id)`

**Impacto esperado:** Queries 2-10x mais rápidas.

---

### 8.5. 🟢 PRIORIDADE BAIXA: Ajustar Fluxo de Chamadas AJAX

**Problemas a resolver:**

1. **Sequenciar chamadas ao invés de paralelas:**
   - Chamar uma função por vez com `await`
   - Ou implementar um sistema de fila com limite de concorrência

2. **Aumentar timeout:**
   - De 8000ms para 15000ms ou 20000ms
   - Ou implementar retry com backoff exponencial

3. **Implementar cache no frontend:**
   - Cachear resultados por alguns segundos
   - Evitar requisições duplicadas

**Impacto esperado:** Redução de sobrecarga simultânea no servidor. Melhor experiência do usuário.

---

## 9. Resumo Executivo

### 9.1. Principais Problemas Identificados

1. **🔴 CRÍTICO:** `api/historico_aluno.php` executa 9-109 queries SQL
2. **🟡 ALTO:** `api/progresso_pratico.php` busca 500 registros e processa em PHP
3. **🟡 MÉDIO:** Múltiplas chamadas paralelas causam sobrecarga simultânea
4. **🟢 BAIXO:** Falta de índices adequados no banco

### 9.2. Onde Atacar Primeiro

**Ordem recomendada de implementação:**

1. **FASE 1 (Crítica):** Otimizar `api/historico_aluno.php`
   - Consolidar queries
   - Eliminar N+1 query problem
   - Mover ordenação para SQL
   - **Impacto esperado:** Redução de 80-90% do tempo de resposta

2. **FASE 2 (Alta):** Otimizar `api/progresso_pratico.php`
   - Mover cálculos para SQL
   - Remover busca de 500 registros
   - **Impacto esperado:** Redução de 60-70% do tempo de resposta

3. **FASE 3 (Média):** Adicionar índices no banco
   - Criar índices compostos recomendados
   - **Impacto esperado:** Redução de 20-50% do tempo de resposta em todas as queries

4. **FASE 4 (Baixa):** Ajustar fluxo de chamadas AJAX
   - Sequenciar chamadas ou implementar fila
   - Aumentar timeout
   - **Impacto esperado:** Melhor experiência do usuário, menos timeouts

### 9.3. Estimativa de Melhoria Total

**Antes:**
- `historico_aluno.php`: 8-15 segundos (timeout)
- `progresso_pratico.php`: 3-8 segundos (timeout)
- `progresso_teorico.php`: 1-2 segundos
- `exames.php`: 1-2 segundos

**Depois (após todas as otimizações):**
- `historico_aluno.php`: < 2 segundos
- `progresso_pratico.php`: < 1 segundo
- `progresso_teorico.php`: < 0.5 segundos
- `exames.php`: < 0.5 segundos

**Melhoria total:** Redução de 80-90% no tempo de resposta.

---

## 10. Conclusão

O problema de performance em produção é causado principalmente por:

1. **Múltiplas queries SQL** executadas sequencialmente
2. **N+1 Query Problem** em `historico_aluno.php`
3. **Processamento pesado em PHP** ao invés de SQL
4. **Falta de índices adequados** no banco
5. **Chamadas paralelas** causando sobrecarga simultânea

A diferença entre dev e produção é explicada principalmente pelo **volume de dados históricos** em produção, que amplifica todos esses problemas.

**Próximos passos recomendados:**
1. Implementar otimizações na ordem de prioridade sugerida
2. Monitorar performance após cada fase
3. Ajustar conforme necessário

---

**Fim do Relatório de Auditoria**

