# Correção Dashboard do Instrutor - Aulas Teóricas

**Data:** 2025-11-24  
**Objetivo:** Corrigir o painel do instrutor para exibir corretamente suas aulas teóricas (hoje e próximos dias)

## Problema Identificado

O dashboard do instrutor (`instrutor/dashboard.php`) estava mostrando apenas aulas práticas da tabela `aulas`, ignorando completamente as aulas teóricas da tabela `turma_aulas_agendadas`.

### Sintomas
- Cards de "Hoje" mostravam 0 aulas mesmo quando havia aulas teóricas agendadas
- Seção "Aulas de Hoje" vazia
- Seção "Próximas Aulas (7 dias)" vazia
- Estatísticas do dia não incluíam aulas teóricas

## Correções Aplicadas

### 1. Query de "Aulas de Hoje" (`instrutor/dashboard.php` - linha ~77)

**ANTES:**
```php
// Buscava apenas aulas práticas
$aulasHoje = $db->fetchAll("
    SELECT a.*, ...
    FROM aulas a
    WHERE a.instrutor_id = ? AND a.data_aula = ?
", [$instrutorId, $hoje]);
```

**DEPOIS:**
```php
// FASE INSTRUTOR - AULAS TEORICAS - Buscar aulas práticas E teóricas
// 1. Buscar aulas práticas
$aulasPraticasHoje = $db->fetchAll("... FROM aulas ...", [$instrutorId, $hoje]);

// 2. Buscar aulas teóricas
$aulasTeoricasHoje = $db->fetchAll("
    SELECT taa.*, tt.nome as turma_nome, s.nome as sala_nome, 'teorica' as tipo_aula
    FROM turma_aulas_agendadas taa
    JOIN turmas_teoricas tt ON taa.turma_id = tt.id
    LEFT JOIN salas s ON taa.sala_id = s.id
    WHERE taa.instrutor_id = ? AND taa.data_aula = ?
", [$instrutorId, $hoje]);

// 3. Combinar e ordenar
$aulasHoje = array_merge($aulasPraticasHoje, $aulasTeoricasHoje);
```

### 2. Query de "Próximas Aulas (7 dias)" (`instrutor/dashboard.php` - linha ~95)

**ANTES:**
```php
// Buscava apenas aulas práticas
$proximasAulas = $db->fetchAll("
    SELECT a.*, ...
    FROM aulas a
    WHERE a.instrutor_id = ? 
      AND a.data_aula > ? 
      AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
", [$instrutorId, $hoje, $hoje]);
```

**DEPOIS:**
```php
// FASE INSTRUTOR - AULAS TEORICAS - Buscar práticas E teóricas dos próximos 7 dias
// Similar à correção acima, combinando ambas as fontes
```

### 3. Estatísticas do Dia (`instrutor/dashboard.php` - linha ~163)

**ANTES:**
```php
// Contava apenas aulas práticas
$statsHoje = $db->fetch("
    SELECT COUNT(*) as total_aulas, ...
    FROM aulas 
    WHERE instrutor_id = ? AND data_aula = ?
", [$instrutorId, $hoje]);
```

**DEPOIS:**
```php
// FASE INSTRUTOR - AULAS TEORICAS - Combinar estatísticas de práticas e teóricas
$statsPraticas = $db->fetch("SELECT ... FROM aulas ...", [$instrutorId, $hoje]);
$statsTeoricas = $db->fetch("SELECT ... FROM turma_aulas_agendadas ...", [$instrutorId, $hoje]);

$statsHoje = [
    'total_aulas' => $statsPraticas['total_aulas'] + $statsTeoricas['total_aulas'],
    'agendadas' => $statsPraticas['agendadas'] + $statsTeoricas['agendadas'],
    // ...
];
```

### 4. Exibição das Aulas (`instrutor/dashboard.php` - linha ~335)

**ANTES:**
```php
// Mostrava apenas dados de aula prática (aluno, veículo)
<div class="aula-detalhe">
    <i class="fas fa-user"></i>
    <?php echo htmlspecialchars($aula['aluno_nome']); ?>
</div>
```

**DEPOIS:**
```php
// FASE INSTRUTOR - AULAS TEORICAS - Exibir dados específicos por tipo
<?php if ($aula['tipo_aula'] === 'teorica'): ?>
    <!-- Mostrar: turma, disciplina, sala -->
    <div class="aula-detalhe">
        <i class="fas fa-users-class"></i>
        <?php echo htmlspecialchars($aula['turma_nome']); ?>
    </div>
    <div class="aula-detalhe">
        <i class="fas fa-book"></i>
        <?php echo htmlspecialchars($disciplinaNome); ?>
    </div>
<?php else: ?>
    <!-- Mostrar: aluno, veículo (aula prática) -->
<?php endif; ?>
```

### 5. Botões de Ação (`instrutor/dashboard.php` - linha ~362)

**ANTES:**
```php
// Botões genéricos para todas as aulas
<button class="btn btn-sm btn-warning solicitar-transferencia">Transferir</button>
<button class="btn btn-sm btn-danger cancelar-aula">Cancelar</button>
```

**DEPOIS:**
```php
// FASE INSTRUTOR - AULAS TEORICAS - Botões diferenciados por tipo
<?php if ($aula['tipo_aula'] === 'teorica'): ?>
    <a href="../admin/index.php?page=turma-chamada&turma_id=...&aula_id=..." 
       class="btn btn-sm btn-primary">Chamada</a>
    <a href="../admin/index.php?page=turma-diario&turma_id=...&aula_id=..." 
       class="btn btn-sm btn-secondary">Diário</a>
<?php else: ?>
    <button class="btn btn-sm btn-warning solicitar-transferencia">Transferir</button>
    <button class="btn btn-sm btn-danger cancelar-aula">Cancelar</button>
<?php endif; ?>
```

### 6. Página "Ver Todas as Aulas" (`instrutor/aulas.php`)

**ANTES:**
- Buscava apenas aulas práticas
- Não tinha filtro por tipo
- Estatísticas não incluíam teóricas

**DEPOIS:**
- Busca aulas práticas E teóricas
- Filtro por tipo (Todas / Práticas / Teóricas)
- Estatísticas combinadas
- Exibição separada por tipo com informações específicas
- Botões de ação corretos para cada tipo

## Estrutura de Dados

### Aulas Práticas
- **Tabela:** `aulas`
- **Campo instrutor:** `aulas.instrutor_id`
- **Campos principais:** `aluno_id`, `veiculo_id`, `data_aula`, `hora_inicio`, `status`
- **Status possíveis:** `agendada`, `confirmada`, `em_andamento`, `concluida`, `cancelada`

### Aulas Teóricas
- **Tabela:** `turma_aulas_agendadas`
- **Campo instrutor:** `turma_aulas_agendadas.instrutor_id`
- **Campos principais:** `turma_id`, `disciplina`, `nome_aula`, `data_aula`, `hora_inicio`, `status`
- **Status possíveis:** `agendada`, `realizada`, `cancelada`
- **Relacionamentos:** 
  - `turma_id` → `turmas_teoricas.id`
  - `sala_id` → `salas.id`

## Arquivos Modificados

1. **`instrutor/dashboard.php`**
   - Queries de "Aulas de Hoje" (linha ~77)
   - Queries de "Próximas Aulas" (linha ~95)
   - Estatísticas do dia (linha ~163)
   - Exibição de detalhes das aulas (linha ~335)
   - Botões de ação (linha ~362)
   - Comentário de documentação no topo do arquivo

2. **`instrutor/aulas.php`**
   - Queries de listagem (linha ~81)
   - Filtro por tipo
   - Estatísticas combinadas
   - Exibição separada por tipo
   - Botões de ação corretos

## Validações de Segurança

- ✅ Todas as queries filtram por `instrutor_id` do instrutor logado
- ✅ Instrutor não consegue ver aulas de outros instrutores
- ✅ Validação de autenticação mantida (`$user['tipo'] === 'instrutor'`)
- ✅ Uso correto de `$instrutorId` da tabela `instrutores` (não `usuario_id` diretamente)

## Critérios de Aceite

### ✅ Implementado

1. **Cards de Hoje:**
   - Mostram valor > 0 quando há aulas teóricas no dia
   - Incluem contagem de práticas + teóricas

2. **Seção "Aulas de Hoje":**
   - Lista todas as aulas teóricas do instrutor no dia
   - Mostra informações corretas (turma, disciplina, sala)
   - Botões de ação corretos (Chamada/Diário)

3. **Seção "Próximas Aulas (7 dias)":**
   - Lista corretamente as próximas aulas teóricas
   - Ordenadas por data/hora
   - Informações completas

4. **Botão "Ver Todas as Aulas":**
   - Redireciona para `instrutor/aulas.php`
   - Página lista todas as aulas (práticas + teóricas)
   - Filtros funcionam (período, tipo, status)
   - Estatísticas corretas

5. **Sem Regressão:**
   - Admin continua vendo tudo normalmente
   - Aluno continua vendo suas aulas normalmente
   - Presença teórica não foi afetada

## Testes Recomendados

1. **Login como instrutor Carlos da Silva:**
   - Verificar cards de hoje com aulas teóricas
   - Verificar "Aulas de Hoje" com aulas teóricas
   - Verificar "Próximas Aulas" com aulas teóricas
   - Clicar em "Ver Todas as Aulas" e validar listagem completa

2. **Validar segurança:**
   - Tentar acessar aulas de outro instrutor (deve ser bloqueado)
   - Verificar que apenas aulas do instrutor logado aparecem

3. **Validar dados:**
   - Comparar aulas exibidas no dashboard com as vistas no admin
   - Verificar que contagens batem

## Notas Técnicas

- **Lógica de combinação:** Aulas práticas e teóricas são buscadas separadamente e depois combinadas em arrays PHP, ordenadas por data/hora
- **Status adaptados:** Aulas teóricas usam `realizada` enquanto práticas usam `concluida` - adaptação feita nas estatísticas
- **Campos unificados:** Para facilitar a exibição, campos são padronizados com `tipo_aula` para identificar a origem
- **Performance:** Queries separadas são mais eficientes que UNIONs complexos, e a combinação em PHP é rápida para volumes normais

## Próximos Passos (Opcional)

- [ ] Adicionar indicador visual mais claro entre práticas e teóricas
- [ ] Considerar cache de estatísticas para melhor performance
- [ ] Adicionar filtros rápidos no dashboard (ex: "Só teóricas", "Só práticas")
- [ ] Melhorar exibição de disciplinas com ícones específicos

