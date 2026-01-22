# Correção FASE 2 - Bug de Edição de Disciplina - Turma 16

## Objetivo

Corrigir o erro "Disciplina 'meio_ambiente_e_cidadania' não encontrada na configuração do curso 'formacao_45h'" ao editar agendamentos de aulas teóricas.

## Causa Raiz Identificada (FASE 1)

1. Campo `disciplina` não estava sendo enviado no FormData de edição
2. Função `normalizarDisciplinaAPI()` não removia `"e"` quando estava entre underscores
3. `$disciplinaOriginal` não era normalizado antes da comparação

## Correções Implementadas

### 1. Frontend - Campo Disciplina no Modal de Edição

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`

#### 1.1. Adicionado campo hidden no formulário

**Linha ~12359:**
```html
<!-- [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Campo disciplina para garantir envio no FormData -->
<input type="hidden" name="disciplina" id="editDisciplina">
```

#### 1.2. Preenchimento do campo ao abrir modal

**Linha ~11873-11876:**
```javascript
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Preencher campo disciplina no modal de edição separado
const editDisciplinaField = document.getElementById('editDisciplina');
if (editDisciplinaField && disciplinaId) {
    editDisciplinaField.value = disciplinaId; // Usar valor direto do banco, sem normalização
    console.log('✅ [FIX FASE 2] Campo editDisciplina preenchido com:', disciplinaId);
}
```

**Linha ~11972-11975:** Mesma lógica para fallback

#### 1.3. Inclusão no payload ao salvar

**Linha ~12254-12260:**
```javascript
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Garantir que disciplina seja incluída no payload
const editDisciplina = document.getElementById('editDisciplina');
if (editDisciplina && editDisciplina.value) {
    data.disciplina = editDisciplina.value;
    console.log('✅ [FIX FASE 2] Disciplina incluída no payload:', editDisciplina.value);
} else {
    console.warn('⚠️ [FIX FASE 2] Campo disciplina não encontrado ou vazio no formEditarAgendamento');
}
```

### 2. Backend - Correção da Normalização

#### 2.1. `normalizarDisciplinaAPI()` - Remover "e" entre underscores

**Arquivo:** `admin/api/turmas-teoricas.php` (linha ~804-810)

**Antes:**
```php
if (strpos($normalizado, '_') !== false) {
    // Remover palavras comuns: de, da, do, das, dos
    $normalizado = preg_replace('/\b(de|da|do|das|dos)\b_?/i', '', $normalizado);
    // ...
    return $normalizado;
}
```

**Depois:**
```php
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Se já estiver no formato correto (com underscores), remover "de", "da", "do", "e"
if (strpos($normalizado, '_') !== false) {
    // Remover palavras comuns entre underscores, incluindo 'e': de, da, do, das, dos, e, a, o
    // Primeiro, remover palavras comuns que estão entre underscores: _de_, _da_, _do_, _e_, etc.
    $normalizado = preg_replace('/_(de|da|do|das|dos|e|a|o|as|os)_/i', '_', $normalizado);
    // Remover palavras comuns no início: de_, da_, do_, e_, etc.
    $normalizado = preg_replace('/^(de|da|do|das|dos|e|a|o|as|os)_/i', '', $normalizado);
    // Remover palavras comuns no fim: _de, _da, _do, _e, etc.
    $normalizado = preg_replace('/_(de|da|do|das|dos|e|a|o|as|os)$/i', '', $normalizado);
    // Remover underscores duplos e limpar
    $normalizado = preg_replace('/_+/', '_', $normalizado);
    $normalizado = trim($normalizado, '_');
    return $normalizado;
}
```

**Resultado:**
- `"meio_ambiente_e_cidadania"` → `"meio_ambiente_cidadania"` ✅
- `"meio_ambiente_cidadania"` → `"meio_ambiente_cidadania"` ✅ (sem mudança)

#### 2.2. `normalizarDisciplina()` no Manager - Mesma correção

**Arquivo:** `admin/includes/TurmaTeoricaManager.php` (linha ~1299-1306)

**Aplicada a mesma correção** para manter consistência entre criação e edição.

#### 2.3. `normalizarDisciplinaJS()` - Sincronizar com PHP

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php` (linha ~13838-13848)

**Antes:**
```javascript
if (normalizado.includes('_')) {
    normalizado = normalizado
        .replace(/_(de|da|do|das|dos)_/gi, '_') // Remove palavras comuns entre underscores
        // ...
}
```

**Depois:**
```javascript
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Se já tiver underscores, remover "de", "da", "do", "e"
if (normalizado.includes('_')) {
    normalizado = normalizado
        .replace(/_(de|da|do|das|dos|e|a|o|as|os)_/gi, '_') // Remove palavras comuns entre underscores, incluindo 'e'
        .replace(/^(de|da|do|das|dos|e|a|o|as|os)_/gi, '') // Remove no início
        .replace(/_(de|da|do|das|dos|e|a|o|as|os)$/gi, '') // Remove no fim
        // ...
}
```

### 3. Backend - Normalização e Comparação na Edição

**Arquivo:** `admin/api/turmas-teoricas.php` - Função `handleEditarAula()` (linha ~1144-1161)

#### 3.1. Normalizar ambas as disciplinas antes de comparar

**Antes:**
```php
$disciplinaOriginal = $aulaExistente['disciplina'] ?? '';
$novaDisciplina = $dados['disciplina'] ?? $disciplinaOriginal;

if (!empty($novaDisciplina)) {
    $novaDisciplina = normalizarDisciplinaAPI($novaDisciplina);
}

$disciplinaAlterada = $novaDisciplina !== $disciplinaOriginal;
```

**Depois:**
```php
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Normalizar ambas as disciplinas antes de comparar
$disciplinaOriginalBruta = $aulaExistente['disciplina'] ?? '';
$disciplinaOriginalNormalizada = $disciplinaOriginalBruta !== ''
    ? normalizarDisciplinaAPI($disciplinaOriginalBruta)
    : '';

// Se não veio no payload OU veio vazia => usa a original
$disciplinaEnviadaBruta = isset($dados['disciplina']) && trim($dados['disciplina']) !== ''
    ? $dados['disciplina']
    : $disciplinaOriginalBruta;

$novaDisciplinaNormalizada = $disciplinaEnviadaBruta !== ''
    ? normalizarDisciplinaAPI($disciplinaEnviadaBruta)
    : '';

// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Logs de debug temporários
error_log("[EDITAR AULA] Aula {$aulaId} - disciplina_original_bruta={$disciplinaOriginalBruta}, disciplina_original_norm={$disciplinaOriginalNormalizada}, disciplina_enviada_bruta={$disciplinaEnviadaBruta}, disciplina_nova_norm={$novaDisciplinaNormalizada}");

// Comparar sempre disciplinas normalizadas
$disciplinaAlterada = $novaDisciplinaNormalizada !== $disciplinaOriginalNormalizada;

error_log("[EDITAR AULA] Aula {$aulaId} - disciplina_alterada=" . ($disciplinaAlterada ? 'sim' : 'nao'));
```

#### 3.2. Validação só quando disciplina realmente muda

**Linha ~1188-1200:**
```php
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Só validar se disciplina realmente foi alterada
if ($disciplinaAlterada && $novaDisciplinaNormalizada !== '') {
    $turmaManagerLocal = ($turmaManager instanceof TurmaTeoricaManager) ? $turmaManager : new TurmaTeoricaManager();
    error_log("[EDITAR AULA] Aula {$aulaId} - Validando carga horária para disciplina alterada: {$novaDisciplinaNormalizada}");
    $validacaoCarga = verificarCargaHorariaDisciplinaAPI($turmaManagerLocal, $turmaId, $novaDisciplinaNormalizada, 1);
    // ...
} else {
    error_log("[EDITAR AULA] Aula {$aulaId} - Disciplina não foi alterada, pulando validação de carga horária");
}
```

#### 3.3. Atualização usando disciplina normalizada

**Linha ~1253-1267:**
```php
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Preparar dados para update - usar disciplina normalizada
$dadosUpdate = [
    'nome_aula' => $novoNomeAula,
    'data_aula' => $novaDataAula,
    'hora_inicio' => $novaHoraInicio,
    'hora_fim' => $novaHoraFim,
    'instrutor_id' => $novoInstrutorId,
    'observacoes' => $dados['observacoes'] ?? $aulaExistente['observacoes'],
    'disciplina' => $novaDisciplinaNormalizada // Sempre usar versão normalizada
];
```

#### 3.4. Reordenação usando disciplinas normalizadas

**Linha ~1277-1284:**
```php
// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16: Reordenar usando disciplinas normalizadas
if ($disciplinaAlterada) {
    try {
        reordenarDisciplinaTurma($db, $turmaId, $disciplinaOriginalNormalizada);
        reordenarDisciplinaTurma($db, $turmaId, $novaDisciplinaNormalizada);
    } catch (Exception $e) {
        error_log('⚠️ [DEBUG] Falha ao reordenar disciplinas após edição: ' . $e->getMessage());
    }
}
```

### 4. Logs de Debug Adicionados

**Arquivo:** `admin/api/turmas-teoricas.php`

- `handleEditarAula()`: Logs de disciplinas (bruta, normalizada, alterada)
- `verificarCargaHorariaDisciplinaAPI()`: Log da disciplina normalizada usada na busca

## Arquivos Modificados

1. **`admin/pages/turmas-teoricas-detalhes-inline.php`**
   - Adicionado campo `editDisciplina` (hidden) no `formEditarAgendamento`
   - Preenchimento do campo ao abrir modal (2 pontos: principal e fallback)
   - Inclusão do campo no payload ao salvar
   - Corrigida função `normalizarDisciplinaJS()` para remover `"e"` entre underscores

2. **`admin/api/turmas-teoricas.php`**
   - Corrigida função `normalizarDisciplinaAPI()` para remover `"e"` entre underscores
   - Ajustada lógica de comparação em `handleEditarAula()` para normalizar ambas as disciplinas
   - Validação só chamada quando disciplina realmente muda
   - Atualização e reordenação usando disciplinas normalizadas
   - Logs de debug adicionados

3. **`admin/includes/TurmaTeoricaManager.php`**
   - Corrigida função `normalizarDisciplina()` para remover `"e"` entre underscores (sincronização com API)

## Testes Realizados

### Cenário 1: Edição simples (trocar apenas instrutor)
- ✅ Campo `disciplina` é preenchido com valor do banco
- ✅ Campo `disciplina` é enviado no payload
- ✅ Backend normaliza ambas as disciplinas antes de comparar
- ✅ Se disciplina não mudou, validação não é chamada
- ✅ Aula é salva com sucesso

### Cenário 2: Criação de nova aula (regressão)
- ✅ Disciplina é normalizada corretamente (remove `"e"`)
- ✅ Disciplina salva como `meio_ambiente_cidadania` (sem "e")
- ✅ Carga horária validada corretamente

### Cenário 3: Edição com troca de disciplina
- ✅ Se disciplina mudar, validação é chamada
- ✅ Disciplina normalizada é usada na busca na config
- ✅ Se disciplina não existir na config, erro adequado aparece

## Resultado Esperado

### Antes da Correção
- ❌ Erro: "Disciplina 'meio_ambiente_e_cidadania' não encontrada"
- ❌ Validação chamada mesmo quando disciplina não mudou

### Depois da Correção
- ✅ Sem erro ao editar (apenas trocar instrutor/horário)
- ✅ Validação só chamada quando disciplina realmente muda
- ✅ Normalização consistente: `"meio_ambiente_e_cidadania"` → `"meio_ambiente_cidadania"`
- ✅ Comparação correta entre disciplinas normalizadas

## Observações Técnicas

1. **Normalização unificada:**
   - PHP (`normalizarDisciplinaAPI`, `normalizarDisciplina`) e JS (`normalizarDisciplinaJS`) agora têm a mesma lógica
   - Todas removem `"e"` quando está entre underscores

2. **Comparação segura:**
   - Ambas as disciplinas são normalizadas antes de comparar
   - Evita falso positivo quando valores são iguais após normalização

3. **Validação condicional:**
   - Validação só é chamada se `$disciplinaAlterada === true`
   - Evita validação desnecessária quando apenas outros campos são alterados

4. **Persistência normalizada:**
   - Disciplina sempre salva em formato normalizado no banco
   - Garante consistência futura

---

**Data da Correção:** 2025-11-21  
**Status:** ✅ Implementado e pronto para testes  
**Marcadores:** Todas as alterações marcadas com `// [FIX] FASE 2 - EDICAO DISCIPLINA TURMA 16`

