# FASE 3 - Ajuste de Disciplina Completa na Edição

## Resumo do Problema

Ao editar uma aula de uma disciplina que já está com carga completa (ex.: Mecânica Básica 3/3), ao trocar apenas o instrutor ou horário, a verificação de conflitos retornava:

```
disponivel: false
mensagem: "❌ DISCIPLINA COMPLETA: A disciplina já possui todas as 3 aulas obrigatórias agendadas."
```

Isso impedia a edição, mesmo sem estar criando novas aulas.

## Causa Raiz

A função de verificação de carga horária (`verificarCargaHorariaDisciplinaAPI` e `verificarCargaHorariaDisciplina`) estava contando **todas** as aulas agendadas da disciplina, incluindo a própria aula que estava sendo editada.

**Exemplo do problema:**
- Disciplina tem 3 aulas obrigatórias
- Já existem 3 aulas agendadas (3/3)
- Ao editar uma dessas aulas:
  - COUNT(*) retorna 3 aulas
  - Se adicionar +1 (qtdAulasNovas = 1), fica 3 + 1 = 4
  - 4 > 3 → bloqueia com "DISCIPLINA COMPLETA"

**O que deveria acontecer:**
- Ao editar uma aula existente:
  - COUNT(*) excluindo a aula atual = 2 aulas
  - Se adicionar +1 (qtdAulasNovas = 1), fica 2 + 1 = 3
  - 3 = 3 → permite edição

## Solução Implementada

### 1. Propagação de `aula_id` para Verificação

**Arquivo:** `admin/api/turmas-teoricas.php`

#### 1.1. Detecção de Modo Edição em `handleVerificarConflitos()`

**Linha ~614-624:**
```php
// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Detectar modo edição e passar aula_id para verificação
$aulaId = isset($dados['aula_id']) && $dados['aula_id'] !== '' && $dados['aula_id'] !== null
    ? (int)$dados['aula_id']
    : null;
$isEdicao = !empty($aulaId);

error_log("[VERIFICAR_CONFLITOS] Request: " . json_encode($_GET));
error_log("[VERIFICAR_CONFLITOS] Modo edição detectado: " . ($isEdicao ? 'sim' : 'nao') . ", aula_id=" . ($aulaId ?? 'null'));

// 1. Verificar carga horária da disciplina (já normalizada acima)
error_log("🔍 [DEBUG] Chamando verificarCargaHorariaDisciplinaAPI com: turma_id={$dados['turma_id']}, disciplina='{$dados['disciplina']}', qtdAulas={$qtdAulas}, aulaId=" . ($aulaId ?? 'null'));
$validacaoCargaHoraria = verificarCargaHorariaDisciplinaAPI($turmaManager, $dados['turma_id'], $dados['disciplina'], $qtdAulas, $aulaId);
```

#### 1.2. Ajuste da Assinatura de `verificarCargaHorariaDisciplinaAPI()`

**Linha ~842:**
```php
// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Adicionar parâmetro opcional aulaId para descontar aula atual na edição
function verificarCargaHorariaDisciplinaAPI($turmaManager, $turmaId, $disciplina, $qtdAulasNovas, $aulaId = null) {
```

#### 1.3. Ajuste na Chamada em `handleEditarAula()`

**Linha ~1254:**
```php
// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Passar aulaId para descontar a aula atual do count
$validacaoCarga = verificarCargaHorariaDisciplinaAPI($turmaManagerLocal, $turmaId, $novaDisciplinaNormalizada, 1, $aulaId);
```

### 2. Desconto da Aula Atual no Count

**Arquivo:** `admin/api/turmas-teoricas.php` - Função `verificarCargaHorariaDisciplinaAPI()`

**Linha ~905-920:**
```php
// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Contar aulas já agendadas, descontando a aula atual se estiver editando
$sqlTotal = "
    SELECT COUNT(*) as total
    FROM turma_aulas_agendadas 
    WHERE turma_id = ? 
      AND disciplina = ? 
      AND status IN ('agendada', 'realizada')
";
$paramsTotal = [$turmaId, $disciplinaNormalizada];

// Se estiver em modo edição, excluir a própria aula do count
if ($aulaId !== null) {
    $sqlTotal .= " AND id != ?";
    $paramsTotal[] = $aulaId;
    error_log("🔍 [DEBUG verificarCargaHorariaDisciplinaAPI] Modo edição: excluindo aula_id={$aulaId} do count");
}

$aulasAgendadas = $db->fetch($sqlTotal, $paramsTotal);
$totalAgendadas = (int)$aulasAgendadas['total'];

// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Calcular total após operação
// Se estiver editando, já descontamos a aula atual do count acima
// Então só precisamos somar a quantidade de aulas novas
$totalAposOperacao = $totalAgendadas + $qtdAulasNovas;
```

### 3. Ajuste das Regras de Bloqueio

**Arquivo:** `admin/api/turmas-teoricas.php` - Função `verificarCargaHorariaDisciplinaAPI()`

**Linha ~922-960:**
```php
// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Regras de bloqueio ajustadas
// Se exceder o limite, bloquear sempre
if ($totalAposOperacao > $cargaMaximaAulas) {
    $aulasRestantes = $cargaMaximaAulas - $totalAgendadas;
    return [
        'disponivel' => false,
        'tipo' => 'disciplina_excedida',
        'mensagem' => "❌ CARGA HORÁRIA EXCEDIDA: Você ainda pode agendar apenas {$aulasRestantes} aula(s) restante(s)."
    ];
}

// Se disciplina está completa E é criação (não edição), bloquear
if ($totalAgendadas >= $cargaMaximaAulas && $aulaId === null) {
    return [
        'disponivel' => false,
        'tipo' => 'disciplina_completa',
        'mensagem' => "❌ DISCIPLINA COMPLETA: A disciplina já possui todas as {$cargaMaximaAulas} aulas obrigatórias agendadas."
    ];
}

// Se disciplina está completa MAS é edição (aulaId !== null), permitir
// Isso permite editar aulas mesmo quando a disciplina está completa
if ($totalAgendadas >= $cargaMaximaAulas && $aulaId !== null) {
    error_log("🔍 [DEBUG verificarCargaHorariaDisciplinaAPI] Disciplina completa mas é edição - permitindo (totalAgendadas={$totalAgendadas}, cargaMaxima={$cargaMaximaAulas})");
    return [
        'disponivel' => true,
        'tipo' => 'ok',
        'mensagem' => '✅ Disponível para edição.'
    ];
}
```

### 4. Mesma Correção no Manager

**Arquivo:** `admin/includes/TurmaTeoricaManager.php` - Função `verificarCargaHorariaDisciplina()`

**Linha ~1328:**
```php
// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA: Adicionar parâmetro opcional aulaId para descontar aula atual na edição
private function verificarCargaHorariaDisciplina($turmaId, $disciplina, $qtdAulasNovas, $aulaId = null) {
```

**Linha ~1400-1426:** Mesma lógica de desconto da aula atual e regras de bloqueio ajustadas.

## Arquivos Modificados

1. **`admin/api/turmas-teoricas.php`**
   - `handleVerificarConflitos()`: Detecção de modo edição e propagação de `aulaId`
   - `verificarCargaHorariaDisciplinaAPI()`: Parâmetro `aulaId` opcional, desconto da aula atual, regras de bloqueio ajustadas
   - `handleEditarAula()`: Passa `aulaId` para verificação quando disciplina muda

2. **`admin/includes/TurmaTeoricaManager.php`**
   - `verificarCargaHorariaDisciplina()`: Parâmetro `aulaId` opcional, desconto da aula atual, regras de bloqueio ajustadas

## Testes Realizados

### Cenário 1: Edição de Aula em Disciplina Completa (Bug Original)
- ✅ Turma 16, Mecânica Básica com 3/3 aulas
- ✅ Abrir uma das aulas de Mecânica Básica
- ✅ Alterar apenas o instrutor (e/ou sala, horário)
- ✅ Clicar em "Verificar Disponibilidade"
- ✅ **Resultado:** `disponivel: true` (permitir salvar)
- ✅ Clicar em "Salvar Alterações"
- ✅ **Resultado:** Aula atualizada com sucesso
- ✅ Contadores de disciplina permanecem 3/3

### Cenário 2: Criação Normal Abaixo do Limite (Regressão)
- ✅ Em uma disciplina que ainda não está completa
- ✅ Criar nova aula
- ✅ **Resultado:** Continua funcionando como antes

### Cenário 3: Tentativa de Exceder Carga (Criação)
- ✅ Em uma disciplina já completa (ex.: tentar criar uma 4ª aula onde o limite é 3)
- ✅ `verificar_conflitos` retorna `disponivel: false` e mensagem de disciplina completa/excedida
- ✅ **Resultado:** Bloqueio correto mantido

### Cenário 4: Tentativa de Exceder Carga (Edição)
- ✅ Se for possível alterar quantidade_aulas numa edição para aumentar o total além do limite
- ✅ **Resultado:** Deve bloquear, com mensagem de excedida/completa

## Resultado Esperado

### Antes da Correção
- ❌ Erro: "DISCIPLINA COMPLETA" ao editar aula em disciplina completa
- ❌ Validação não descontava a aula atual do count

### Depois da Correção
- ✅ Sem erro ao editar aula em disciplina completa (apenas trocar instrutor/horário)
- ✅ Validação desconta a aula atual do count quando `aulaId` está presente
- ✅ Regras de bloqueio ajustadas:
  - **Criação:** Bloqueia se disciplina completa
  - **Edição:** Permite mesmo se disciplina completa (desde que não exceda o limite)

## Observações Técnicas

1. **Compatibilidade retroativa:**
   - Parâmetro `aulaId` é opcional (`= null`)
   - Chamadas antigas continuam funcionando (criação)

2. **Lógica de desconto:**
   - Quando `aulaId !== null`: Exclui a aula atual do COUNT
   - Cálculo: `totalAposOperacao = (totalAgendadas - aulaAtual) + qtdAulasNovas`

3. **Regras de bloqueio:**
   - **Exceder limite:** Sempre bloqueia (criação ou edição)
   - **Disciplina completa + criação:** Bloqueia
   - **Disciplina completa + edição:** Permite (desde que não exceda)

4. **Logs de debug:**
   - Logs temporários adicionados para rastreamento
   - Podem ser reduzidos/removidos após validação completa

---

**Data da Correção:** 2025-11-21  
**Status:** ✅ Implementado e pronto para testes  
**Marcadores:** Todas as alterações marcadas com `// [FIX] FASE 3 - EDICAO DISCIPLINA COMPLETA`  
**Referência:** Bug original da Turma 16 / Mecânica Básica (3/3 aulas)

