# Correção - Widget "Próximas Aulas" do Dashboard do Aluno

**Data:** 2025-11-25  
**Objetivo:** Alinhar o widget "Próximas Aulas" do dashboard com a lógica usada em `aluno/aulas.php`, incluindo aulas teóricas e práticas.

---

## Problema Identificado

O widget "Próximas Aulas" no dashboard do aluno (`aluno/dashboard.php`) estava mostrando apenas aulas práticas, ignorando as aulas teóricas. Mesmo havendo aulas teóricas futuras (ex.: 03/12/2025), o dashboard mostrava:

> "Você não possui aulas agendadas para os próximos 14 dias."

### Causa Raiz

A query no dashboard buscava apenas da tabela `aulas` (aulas práticas), não incluindo as aulas teóricas da tabela `turma_aulas_agendadas` via `turma_matriculas`.

---

## Solução Implementada

### 1. Criação de Função Reutilizável

**Arquivo:** `aluno/dashboard.php`

**Função:** `obterProximasAulasAluno($db, $alunoId, $dias = 14)`

Esta função:
- Busca aulas práticas da tabela `aulas`
- Busca aulas teóricas via `turma_matriculas` → `turma_aulas_agendadas`
- Combina e ordena todas as aulas por data/hora
- Filtra apenas aulas futuras (hoje com hora >= agora, ou datas futuras)
- Retorna array unificado com campo `tipo` ('pratica' ou 'teorica')

**Lógica reutilizada de:** `aluno/aulas.php` (linhas 110-239)

### 2. Atualização da Busca de Aulas

**Antes:**
```php
$proximasAulas = $db->fetchAll("
    SELECT a.*, 
           i.nome as instrutor_nome,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ?
      AND a.data_aula >= CURDATE() 
      AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
      AND a.status != 'cancelada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 10
", [$alunoId]);
```

**Depois:**
```php
$proximasAulas = obterProximasAulasAluno($db, $alunoId, 14);
```

### 3. Atualização da Renderização

A renderização foi ajustada para suportar tanto aulas práticas quanto teóricas:

- **Tipo de aula:** Usa `$aula['tipo']` e `$aula['tipo_label']` em vez de `$aula['tipo_aula']`
- **Turma/Disciplina:** Exibe `turma_nome` e `disciplina` para aulas teóricas
- **Sala:** Exibe `sala_nome` para aulas teóricas (se disponível)
- **Status:** Adapta label (ex.: 'realizada' → 'Realizada')
- **Ações:** Botões "Reagendar" e "Cancelar" apenas para aulas práticas

### 4. Uso de `getCurrentAlunoId()`

A busca do `aluno_id` foi atualizada para usar `getCurrentAlunoId()` em vez de buscar por CPF, garantindo consistência com o restante do sistema.

---

## Arquivos Modificados

### Páginas PHP
1. **`aluno/dashboard.php`**
   - Adicionada função `obterProximasAulasAluno()` (linhas 38-144)
   - Atualizada busca de `aluno_id` para usar `getCurrentAlunoId()` (linha 36)
   - Atualizada chamada para usar a nova função (linha 147)
   - Atualizada renderização para suportar aulas teóricas e práticas (linhas 316-374)

---

## Detalhes Técnicos

### Query de Aulas Práticas
```sql
SELECT a.*, 
       i.nome as instrutor_nome,
       v.modelo as veiculo_modelo, 
       v.placa as veiculo_placa,
       'pratica' as tipo,
       'Prática' as tipo_label
FROM aulas a
JOIN instrutores i ON a.instrutor_id = i.id
LEFT JOIN veiculos v ON a.veiculo_id = v.id
WHERE a.aluno_id = ?
  AND a.data_aula >= ?
  AND a.data_aula <= ?
  AND a.status != 'cancelada'
ORDER BY a.data_aula ASC, a.hora_inicio ASC
```

### Query de Aulas Teóricas
```sql
SELECT 
    taa.id,
    taa.turma_id,
    taa.disciplina,
    taa.nome_aula,
    taa.data_aula,
    taa.hora_inicio,
    taa.hora_fim,
    taa.status,
    taa.observacoes,
    tt.nome as turma_nome,
    i.nome as instrutor_nome,
    s.nome as sala_nome,
    'teorica' as tipo,
    'Teórica' as tipo_label
FROM turma_aulas_agendadas taa
JOIN turmas_teoricas tt ON taa.turma_id = tt.id
LEFT JOIN instrutores i ON taa.instrutor_id = i.id
LEFT JOIN salas s ON taa.sala_id = s.id
WHERE taa.turma_id IN (...)
  AND taa.data_aula >= ?
  AND taa.data_aula <= ?
  AND taa.status IN ('agendada', 'realizada')
ORDER BY taa.data_aula ASC, taa.hora_inicio ASC
```

### Filtros Aplicados

- **Período:** Próximos 14 dias (configurável via parâmetro `$dias`)
- **Status práticas:** `status != 'cancelada'`
- **Status teóricas:** `status IN ('agendada', 'realizada')`
- **Matrículas:** Apenas turmas com status `'matriculado'`, `'cursando'` ou `'concluido'`
- **Futuras:** Apenas aulas com data > hoje OU (data = hoje E hora >= agora)

---

## Critérios de Aceite

✅ **Aulas teóricas aparecem no dashboard:**
- Aluno com aulas teóricas futuras vê essas aulas no widget
- Aulas teóricas mostram turma e disciplina

✅ **Aulas práticas continuam funcionando:**
- Aulas práticas aparecem normalmente
- Botões de ação (Reagendar/Cancelar) funcionam

✅ **Consistência com `aluno/aulas.php`:**
- Mesma lógica de busca
- Mesmos filtros e critérios
- Mesma ordenação

✅ **Estado vazio correto:**
- Se não houver aulas nos próximos 14 dias, mostra mensagem de vazio
- Mensagem só aparece quando realmente não há aulas

---

## Testes Realizados

1. ✅ Dashboard mostra aulas teóricas futuras
2. ✅ Dashboard mostra aulas práticas futuras
3. ✅ Aulas ordenadas corretamente (mais próxima primeiro)
4. ✅ Estado vazio aparece quando não há aulas
5. ✅ Renderização suporta ambos os tipos de aula
6. ✅ Botões de ação apenas para aulas práticas

---

## Notas Técnicas

- A função `obterProximasAulasAluno()` pode ser reutilizada em outras partes do sistema
- A lógica está alinhada com `aluno/aulas.php` para garantir consistência
- O intervalo de 14 dias pode ser ajustado via parâmetro da função
- A função filtra automaticamente aulas passadas (mesmo que estejam no intervalo de datas)

---

**Correção concluída:** O widget "Próximas Aulas" agora mostra tanto aulas teóricas quanto práticas, alinhado com a lógica de `aluno/aulas.php`.

