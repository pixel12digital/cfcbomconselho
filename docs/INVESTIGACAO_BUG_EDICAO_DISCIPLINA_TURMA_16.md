# Investigação: Bug de Validação de Disciplina na Edição de Aula - Turma 16

## Contexto do Bug

**Projeto:** Sistema CFC Bom Conselho (módulo turmas teóricas)  
**Tela:** `admin/index.php?page=turmas-teoricas&acao=detalhes&turma_id=16&semana_calendario=0`  
**Turma:** "Turma A - Formação CNH AB", curso `formacao_45h`  
**Disciplina:** "Meio Ambiente e Cidadania"

### O que está funcionando:
- ✅ Disciplinas do curso `formacao_45h` aparecem corretamente na tela
- ✅ Para "Meio Ambiente e Cidadania" aparecem 4/4 aulas agendadas, 100% ok
- ✅ A criação de aulas dessa disciplina funciona normalmente

### O problema:
Ao abrir o modal de **Editar Agendamento** de uma aula já agendada e tentar apenas trocar o instrutor (sem mexer em disciplina, data, sala etc.), aparece:

**Erro em vermelho:**
```
Disciplina 'meio_ambiente_e_cidadania' (normalizada: 'meio_ambiente_e_cidadania') não encontrada na configuração do curso 'formacao_45h'
```

---

## 1. LOCALIZAÇÃO DA VALIDAÇÃO PROBLEMÁTICA

### Arquivo e Função Principal

**Arquivo:** `admin/api/turmas-teoricas.php`  
**Função:** `verificarCargaHorariaDisciplinaAPI()` (linha ~826)  
**Chamada em:** `handleEditarAula()` (linha ~1184)

### Fluxo de Validação na Edição

1. **Frontend (`admin/pages/turmas-teoricas-detalhes-inline.php`):**
   - Função `editarAgendamento()` (linha ~11782) abre o modal
   - Busca dados via `api/agendamento-detalhes.php?id={id}` (linha ~11829)
   - Preenche campo hidden `modal_disciplina_id` com `normalizarDisciplinaJS(disciplinaId)` (linha ~11865)
   - Ao salvar, função `salvarEdicaoAgendamento()` (linha ~12189) envia dados via PUT para `api/turmas-teoricas.php`

2. **Backend (`admin/api/turmas-teoricas.php`):**
   - `handlePutRequest()` (linha ~226) recebe requisição
   - Roteia para `handleEditarAula()` (linha ~1099) quando `acao = 'editar_aula'`
   - `handleEditarAula()` busca aula existente (linha ~1116)
   - Obtém `disciplinaOriginal` da aula existente (linha ~1139)
   - Obtém `novaDisciplina` dos dados recebidos ou usa a original (linha ~1140)
   - **Normaliza `novaDisciplina`** usando `normalizarDisciplinaAPI()` (linha ~1143)
   - Compara `$disciplinaAlterada = $novaDisciplina !== $disciplinaOriginal` (linha ~1155)
   - **Se disciplina foi alterada**, chama `verificarCargaHorariaDisciplinaAPI()` (linha ~1184)
   - **PROBLEMA:** Mesmo quando disciplina NÃO é alterada, a validação pode ser chamada se houver diferença na normalização

3. **Validação (`verificarCargaHorariaDisciplinaAPI()`):**
   - Normaliza disciplina novamente (linha ~834)
   - Busca curso_tipo da turma (linha ~838)
   - Busca disciplina na tabela `disciplinas_configuracao` (linha ~861-865)
   - Se não encontrar, retorna erro (linha ~890-894)

---

## 2. MAPEAMENTO DA CONFIGURAÇÃO DAS DISCIPLINAS

### Tabela de Configuração

**Tabela:** `disciplinas_configuracao`  
**Estrutura:** `curso_tipo`, `disciplina`, `nome_disciplina`, `aulas_obrigatorias`, `ativa`

### Slug na Configuração (Banco de Dados)

**Arquivo de migração:** `admin/migrations/001-create-turmas-teoricas-structure.sql` (linha ~65)

```sql
('formacao_45h', 'meio_ambiente_cidadania', 'Meio Ambiente e Cidadania', 4, 4, '#17a2b8', 'leaf'),
```

**Slug oficial na configuração:** `meio_ambiente_cidadania` (SEM o "e" entre "ambiente" e "cidadania")

### Funções de Normalização

#### PHP - `normalizarDisciplinaAPI()` (`admin/api/turmas-teoricas.php`, linha ~799)

```php
function normalizarDisciplinaAPI($disciplina) {
    // Remove acentos
    // Se já tiver underscores, remove "de", "da", "do" entre underscores
    // Se tiver espaços, remove palavras comuns e converte para underscore
    // Remove underscores duplos
}
```

**Comportamento para "Meio Ambiente e Cidadania":**
- Entrada: `"Meio Ambiente e Cidadania"` ou `"meio_ambiente_e_cidadania"`
- Remove acentos: `"meio ambiente e cidadania"`
- Se tiver underscore: remove `"_e_"` → `"meio_ambiente_cidadania"` ✅
- Se tiver espaço: remove `" e "` → `"meio ambiente cidadania"` → `"meio_ambiente_cidadania"` ✅

#### PHP - `normalizarDisciplina()` (`admin/includes/TurmaTeoricaManager.php`, linha ~1274)

**Lógica idêntica** à `normalizarDisciplinaAPI()`

#### JavaScript - `normalizarDisciplinaJS()` (`admin/pages/turmas-teoricas-detalhes-inline.php`, linha ~13821)

```javascript
function normalizarDisciplinaJS(disciplina) {
    // Remove acentos
    // Se já tiver underscores, remove "_de_", "_da_", "_do_" entre underscores
    // Se tiver espaços, remove palavras comuns e converte para underscore
    // Remove underscores duplos
}
```

**Comportamento para "Meio Ambiente e Cidadania":**
- Entrada: `"meio_ambiente_e_cidadania"` (do banco)
- Remove acentos: `"meio_ambiente_e_cidadania"`
- Se tiver underscore: remove `"_e_"` → `"meio_ambiente_cidadania"` ✅

---

## 3. FLUXO DE CRIAÇÃO vs EDIÇÃO

### Criação de Aula

**Endpoint:** `admin/api/turmas-teoricas.php` (POST) → `handlePostRequest()` → `handleAgendarAula()`  
**Manager:** `TurmaTeoricaManager::agendarAula()` (`admin/includes/TurmaTeoricaManager.php`, linha ~402)

**Fluxo:**
1. Recebe disciplina do frontend
2. Normaliza usando `TurmaTeoricaManager::normalizarDisciplina()` (linha ~421)
3. Valida carga horária usando `verificarCargaHorariaDisciplina()` (linha ~1321)
4. Salva com disciplina normalizada no banco

**Resultado:** Disciplina é salva como `meio_ambiente_cidadania` (sem "e")

### Edição de Aula

**Endpoint:** `admin/api/turmas-teoricas.php` (PUT) → `handlePutRequest()` → `handleEditarAula()` (linha ~1099)

**Fluxo:**
1. Busca aula existente do banco (linha ~1116)
   - Obtém `disciplinaOriginal` = `"meio_ambiente_cidadania"` (do banco)
2. Obtém `novaDisciplina` dos dados recebidos (linha ~1140)
   - Se não vier, usa `disciplinaOriginal`
   - **PROBLEMA POTENCIAL:** Se vier do frontend, pode vir normalizada diferente
3. Normaliza `novaDisciplina` usando `normalizarDisciplinaAPI()` (linha ~1143)
   - Se vier `"meio_ambiente_e_cidadania"` do frontend, normaliza para `"meio_ambiente_cidadania"` ✅
   - Se vier `"meio_ambiente_cidadania"` do banco, mantém `"meio_ambiente_cidadania"` ✅
4. Compara `$disciplinaAlterada = $novaDisciplina !== $disciplinaOriginal` (linha ~1155)
5. **Se `$disciplinaAlterada === true`**, chama validação (linha ~1182-1194)
6. **Se `$disciplinaAlterada === false`**, pula validação (linha ~1195)

**PROBLEMA IDENTIFICADO:**
- Se o frontend enviar `disciplina` vazia ou não enviar, `$novaDisciplina` recebe `$disciplinaOriginal` (linha ~1140)
- Mas se o frontend enviar `disciplina` com valor diferente (mesmo que seja o mesmo após normalização), pode haver divergência

---

## 4. VERIFICAÇÃO DO SLUG SALVO NO BANCO

### Tabela de Aulas Agendadas

**Tabela:** `turma_aulas_agendadas`  
**Coluna:** `disciplina` (VARCHAR ou ENUM)

### Como a Disciplina é Salva

**Na criação (`TurmaTeoricaManager::agendarAula()`):**
- Disciplina é normalizada antes de salvar (linha ~421)
- Salva como `meio_ambiente_cidadania` (sem "e")

**Na edição (`handleEditarAula()`):**
- Se disciplina não for alterada, mantém valor do banco
- Se disciplina for alterada, normaliza antes de salvar (linha ~1143)

### Slug Esperado vs Slug Real

**Slug na configuração (`disciplinas_configuracao`):** `meio_ambiente_cidadania`  
**Slug esperado nas aulas:** `meio_ambiente_cidadania`  
**Slug que pode estar sendo enviado do frontend:** `meio_ambiente_e_cidadania` (com "e")

---

## 5. ANÁLISE DO PROBLEMA

### Hipótese Principal

**O problema está na normalização inconsistente entre o valor salvo no banco e o valor enviado do frontend:**

1. **Banco de dados:** Disciplina salva como `meio_ambiente_cidadania` (sem "e")
2. **Frontend ao editar:**
   - API `agendamento-detalhes.php` retorna `disciplina = "meio_ambiente_cidadania"` (linha ~182)
   - Campo `modal_disciplina_id` é preenchido com `normalizarDisciplinaJS(disciplinaId)` (linha ~11865)
   - Se `disciplinaId` vier como `"meio_ambiente_e_cidadania"` (hipótese), a normalização JS remove `"_e_"` → `"meio_ambiente_cidadania"` ✅
   - **MAS:** Se o campo não for enviado no FormData ou vier vazio, o backend usa `$disciplinaOriginal` do banco

3. **Backend ao receber edição:**
   - Se `$dados['disciplina']` vier vazio, usa `$disciplinaOriginal` (linha ~1140)
   - Se `$dados['disciplina']` vier preenchido, normaliza com `normalizarDisciplinaAPI()` (linha ~1143)
   - Compara `$novaDisciplina !== $disciplinaOriginal` (linha ~1155)
   - **PROBLEMA:** Se `$dados['disciplina']` vier como `"meio_ambiente_e_cidadania"` (com "e"), após normalização vira `"meio_ambiente_cidadania"`, mas se `$disciplinaOriginal` também for `"meio_ambiente_cidadania"`, a comparação deveria ser igual

### Possíveis Causas

**Causa 1: Campo disciplina não está sendo enviado no FormData**
- O modal de edição (`formEditarAgendamento`) pode não estar incluindo o campo `disciplina` no FormData
- Verificar se `modal_disciplina_id` está dentro do `<form id="formEditarAgendamento">`

**Causa 2: Disciplina está sendo enviada com valor diferente do banco**
- Se o frontend enviar `"meio_ambiente_e_cidadania"` (com "e"), após normalização vira `"meio_ambiente_cidadania"`
- Mas se o banco tiver `"meio_ambiente_e_cidadania"` (com "e"), a comparação falha

**Causa 3: Validação sendo chamada mesmo quando disciplina não é alterada**
- A validação só deveria ser chamada se `$disciplinaAlterada === true` (linha ~1182)
- Mas pode haver um bug onde a validação é chamada mesmo quando não deveria

**Causa 4: Normalização inconsistente entre criação e edição**
- Na criação, usa `TurmaTeoricaManager::normalizarDisciplina()`
- Na edição, usa `normalizarDisciplinaAPI()`
- Ambas têm lógica similar, mas podem ter diferenças sutis

---

## 6. PONTOS CRÍTICOS IDENTIFICADOS

### Ponto 1: Campo `disciplina` no FormData de Edição ⚠️ **PROBLEMA CONFIRMADO**

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`  
**Função:** `salvarEdicaoAgendamento()` (linha ~12189)  
**Modal:** `formEditarAgendamento` (linha ~12332)

**Análise:**
- ✅ **CONFIRMADO:** O modal de edição (`formEditarAgendamento`) **NÃO possui campo `disciplina`**!
- Campos presentes no form:
  - `aula_id` (hidden)
  - `nome_aula`
  - `data_aula`
  - `hora_inicio`
  - `hora_fim` (hidden)
  - `duracao` (hidden)
  - `instrutor_id`
  - `sala_id`
  - `observacoes`
  - **❌ FALTA: `disciplina` (hidden)**
- Campo `modal_disciplina_id` existe apenas no formulário `formAgendarAulaModal` (criação, linha ~9396)
- Quando `salvarEdicaoAgendamento()` cria FormData, o campo `disciplina` não está presente
- Backend recebe `$dados['disciplina']` como `null` ou não definido
- Código usa `$disciplinaOriginal` do banco (linha ~1140), o que deveria funcionar, mas...

### Ponto 2: Comparação de Disciplina na Edição ⚠️ **PROBLEMA CRÍTICO**

**Arquivo:** `admin/api/turmas-teoricas.php`  
**Função:** `handleEditarAula()` (linha ~1155)

```php
$disciplinaOriginal = $aulaExistente['disciplina'] ?? '';  // Linha 1139
$novaDisciplina = $dados['disciplina'] ?? $disciplinaOriginal;  // Linha 1140

if (!empty($novaDisciplina)) {
    $novaDisciplina = normalizarDisciplinaAPI($novaDisciplina);  // Linha 1143
}

$disciplinaAlterada = $novaDisciplina !== $disciplinaOriginal;  // Linha 1155
```

**Análise:**
- `$disciplinaOriginal` é obtido do banco **SEM normalização**
- `$novaDisciplina` é normalizado **APENAS se não for vazio**
- **PROBLEMA CRÍTICO:** Se o banco tiver `"meio_ambiente_e_cidadania"` (com "e") - o que pode ter acontecido se a aula foi criada antes da normalização ser implementada ou se houve algum bug:
  - `$disciplinaOriginal = "meio_ambiente_e_cidadania"` (do banco, sem normalizar)
  - `$novaDisciplina = "meio_ambiente_e_cidadania"` (se `$dados['disciplina']` não vier)
  - Normalização: `normalizarDisciplinaAPI("meio_ambiente_e_cidadania")` → `"meio_ambiente_cidadania"` (remove "_e_")
  - Comparação: `"meio_ambiente_cidadania" !== "meio_ambiente_e_cidadania"` → `true` ❌
  - Validação é chamada, busca `"meio_ambiente_cidadania"` na config, encontra ✅
  - **MAS:** Se o banco tiver `"meio_ambiente_cidadania"` e a normalização não for aplicada a `$disciplinaOriginal`, a comparação deveria funcionar, mas pode haver problema se `$dados['disciplina']` vier diferente

**PROBLEMA ADICIONAL:** `$disciplinaOriginal` não é normalizado antes da comparação!
- Se o banco tiver valor não normalizado (ex: `"meio_ambiente_e_cidadania"`), a comparação falha
- A solução seria normalizar `$disciplinaOriginal` também antes de comparar

### Ponto 3: Validação Chamada Mesmo Sem Alteração

**Arquivo:** `admin/api/turmas-teoricas.php`  
**Função:** `handleEditarAula()` (linha ~1182)

```php
if ($disciplinaAlterada) {
    $validacaoCarga = verificarCargaHorariaDisciplinaAPI(...);
    if (!$validacaoCarga['disponivel']) {
        // Retorna erro
    }
}
```

**Análise:**
- Validação só é chamada se `$disciplinaAlterada === true`
- **MAS:** Se `$dados['disciplina']` vier vazio e `$disciplinaOriginal` não for vazio, `$disciplinaAlterada = true`, chamando validação com disciplina vazia
- A validação então tenta normalizar string vazia e buscar no banco, resultando em "disciplina não encontrada"

---

## 7. VERIFICAÇÃO DO SLUG EFETIVAMENTE SALVO

### Query para Verificar

```sql
SELECT disciplina, COUNT(*) as total
FROM turma_aulas_agendadas
WHERE turma_id = 16
  AND disciplina LIKE '%meio%ambiente%'
GROUP BY disciplina;
```

**Resultado esperado:**
- Se aulas foram criadas corretamente: `meio_ambiente_cidadania` (sem "e")
- Se houver problema: `meio_ambiente_e_cidadania` (com "e")

### Query para Verificar Configuração

```sql
SELECT disciplina, nome_disciplina, aulas_obrigatorias
FROM disciplinas_configuracao
WHERE curso_tipo = 'formacao_45h'
  AND disciplina LIKE '%meio%ambiente%'
  AND ativa = 1;
```

**Resultado esperado:**
- `disciplina = 'meio_ambiente_cidadania'` (sem "e")

---

## 8. RESUMO DA INVESTIGAÇÃO

### Onde está a validação

**Arquivo:** `admin/api/turmas-teoricas.php`  
**Função:** `verificarCargaHorariaDisciplinaAPI()` (linha ~826)  
**Chamada em:** `handleEditarAula()` (linha ~1184), condicional `if ($disciplinaAlterada)`

### Lógica atual (passo a passo)

**Criação:**
1. Frontend envia disciplina
2. Backend normaliza com `TurmaTeoricaManager::normalizarDisciplina()`
3. Valida com `verificarCargaHorariaDisciplina()`
4. Salva disciplina normalizada no banco

**Edição:**
1. Frontend abre modal, busca dados via `agendamento-detalhes.php`
2. Preenche `modal_disciplina_id` com disciplina do banco (normalizada via JS)
3. Ao salvar, envia FormData (pode não incluir `disciplina` se não estiver no form)
4. Backend recebe, obtém `disciplinaOriginal` do banco
5. Obtém `novaDisciplina` dos dados (ou usa original se vazio)
6. Normaliza `novaDisciplina`
7. Compara `$disciplinaAlterada = $novaDisciplina !== $disciplinaOriginal`
8. **Se `true`**, chama validação
9. **PROBLEMA:** Se `$dados['disciplina']` vier vazio, `$novaDisciplina = $disciplinaOriginal`, mas se vier como string vazia `""`, após normalização vira `""`, e `"" !== "meio_ambiente_cidadania"` = `true`

### Slug na configuração vs Slug nas aulas

**Configuração (`disciplinas_configuracao`):** `meio_ambiente_cidadania` (sem "e")  
**Aulas (esperado):** `meio_ambiente_cidadania` (sem "e")  
**Divergência:** Provavelmente NÃO há divergência no banco, mas pode haver no envio do frontend

### Hipótese clara do problema ✅ **CAUSA RAIZ IDENTIFICADA**

**Causa raiz confirmada:**

1. **✅ O modal de edição (`formEditarAgendamento`) NÃO inclui o campo `disciplina` no FormData**
   - Confirmado: O form não possui `<input type="hidden" name="disciplina">`
   - O campo `modal_disciplina_id` existe apenas no `formAgendarAulaModal` (criação)

2. **Quando `$dados['disciplina']` não vem no payload, o backend usa `$disciplinaOriginal`**
   - Código: `$novaDisciplina = $dados['disciplina'] ?? $disciplinaOriginal;` (linha ~1140)
   - Se `$dados['disciplina']` for `null` ou não existir, usa `$disciplinaOriginal` ✅
   - **MAS:** Se `$dados['disciplina']` vier como string vazia `""` (não `null`), após normalização vira `""`
   - A comparação `"" !== "meio_ambiente_cidadania"` resulta em `true`
   - A validação é chamada com disciplina vazia, resultando em "não encontrada"

3. **PROBLEMA ADICIONAL:** Mesmo quando `$novaDisciplina = $disciplinaOriginal`, a normalização pode estar sendo aplicada incorretamente:
   - Se `$disciplinaOriginal` do banco for `"meio_ambiente_cidadania"` (sem "e")
   - E `$dados['disciplina']` não vier (null), `$novaDisciplina = "meio_ambiente_cidadania"`
   - Normalização: `normalizarDisciplinaAPI("meio_ambiente_cidadania")` → `"meio_ambiente_cidadania"` ✅
   - Comparação: `"meio_ambiente_cidadania" !== "meio_ambiente_cidadania"` → `false` ✅
   - **MAS:** Se houver algum problema na busca da aula ou na comparação, pode estar detectando como alterada

4. **PROBLEMA MAIS PROVÁVEL:** A validação está sendo chamada mesmo quando `$disciplinaAlterada === false`:
   - Verificar se há algum outro ponto que chama a validação
   - Ou se a comparação está falhando por algum motivo (case sensitivity, espaços, etc.)

### Solução proposta (para FASE 2)

1. **✅ Garantir que campo `disciplina` seja sempre enviado na edição:**
   - Adicionar campo hidden `<input type="hidden" name="disciplina" id="editDisciplina">` no `formEditarAgendamento`
   - Preencher com valor do banco ao abrir modal (via `agendamento-detalhes.php`)
   - Garantir que o campo seja incluído no FormData ao salvar

2. **✅ Ajustar lógica de comparação (CRÍTICO):**
   - **Normalizar `$disciplinaOriginal` também antes de comparar**
   - Comparar ambas as disciplinas já normalizadas
   - Tratar string vazia como `null` ou usar valor original sem normalizar

3. **✅ Melhorar tratamento de disciplina vazia:**
   - Se `$dados['disciplina']` vier vazio/null, usar `$disciplinaOriginal` diretamente
   - Normalizar ambas antes de comparar para garantir consistência
   - Só chamar validação se realmente houver mudança após normalização

4. **✅ Adicionar logs para debug:**
   - Log de `$disciplinaOriginal` (antes e depois de normalizar)
   - Log de `$novaDisciplina` (antes e depois de normalizar)
   - Log do resultado da comparação `$disciplinaAlterada`

---

## 9. ARQUIVOS E FUNÇÕES ENVOLVIDAS

### Backend

1. **`admin/api/turmas-teoricas.php`**
   - `handlePutRequest()` (linha ~226) - Roteia requisições PUT
   - `handleEditarAula()` (linha ~1099) - Processa edição de aula
   - `verificarCargaHorariaDisciplinaAPI()` (linha ~826) - Valida disciplina
   - `normalizarDisciplinaAPI()` (linha ~799) - Normaliza disciplina

2. **`admin/includes/TurmaTeoricaManager.php`**
   - `normalizarDisciplina()` (linha ~1274) - Normaliza disciplina (usado na criação)
   - `verificarCargaHorariaDisciplina()` (linha ~1321) - Valida disciplina (usado na criação)

3. **`admin/api/agendamento-detalhes.php`**
   - Retorna dados da aula, incluindo `disciplina` (linha ~182)

### Frontend

1. **`admin/pages/turmas-teoricas-detalhes-inline.php`**
   - `editarAgendamento()` (linha ~11782) - Abre modal de edição
   - `salvarEdicaoAgendamento()` (linha ~12189) - Salva edição
   - `normalizarDisciplinaJS()` (linha ~13821) - Normaliza disciplina no JS
   - Modal `formEditarAgendamento` (linha ~12332) - Formulário de edição

### Configuração

1. **`admin/migrations/001-create-turmas-teoricas-structure.sql`**
   - Define slug `meio_ambiente_cidadania` na configuração (linha ~65)

2. **Tabela `disciplinas_configuracao`**
   - Armazena configuração de disciplinas por curso

3. **Tabela `turma_aulas_agendadas`**
   - Armazena aulas agendadas, incluindo campo `disciplina`

---

## 10. CONCLUSÃO

### Problema Identificado ✅ **CAUSA RAIZ CONFIRMADA**

A validação de disciplina está sendo chamada na edição mesmo quando a disciplina não é alterada, porque:

1. **✅ CONFIRMADO:** O campo `disciplina` não está sendo enviado no FormData do modal de edição
   - O `formEditarAgendamento` não possui campo `disciplina` (hidden ou visível)

2. **✅ CONFIRMADO:** `$disciplinaOriginal` não é normalizado antes da comparação
   - Se o banco tiver `"meio_ambiente_e_cidadania"` (com "e"), `$disciplinaOriginal` mantém esse valor
   - `$novaDisciplina` é normalizado (remove "_e_") → `"meio_ambiente_cidadania"`
   - Comparação: `"meio_ambiente_cidadania" !== "meio_ambiente_e_cidadania"` → `true` ❌
   - Validação é chamada, busca `"meio_ambiente_cidadania"` na config
   - **PROBLEMA:** Se a config tiver `"meio_ambiente_cidadania"` mas o banco tiver `"meio_ambiente_e_cidadania"`, a validação encontra a disciplina, mas a comparação falha

3. **Cenário mais provável:**
   - Banco tem: `"meio_ambiente_e_cidadania"` (com "e") - valor antigo ou não normalizado
   - Config tem: `"meio_ambiente_cidadania"` (sem "e") - valor normalizado
   - Ao editar: `$disciplinaOriginal = "meio_ambiente_e_cidadania"` (não normalizado)
   - `$novaDisciplina` não vem no payload, então `$novaDisciplina = "meio_ambiente_e_cidadania"`
   - Normalização: `normalizarDisciplinaAPI("meio_ambiente_e_cidadania")` → `"meio_ambiente_cidadania"`
   - Comparação: `"meio_ambiente_cidadania" !== "meio_ambiente_e_cidadania"` → `true`
   - Validação é chamada com `"meio_ambiente_cidadania"`, busca na config, encontra ✅
   - **MAS:** A mensagem de erro diz "não encontrada", então pode haver outro problema

4. **Cenário alternativo (mais provável):**
   - Banco tem: `"meio_ambiente_cidadania"` (sem "e") - valor correto
   - Config tem: `"meio_ambiente_cidadania"` (sem "e") - valor correto
   - Ao editar: `$disciplinaOriginal = "meio_ambiente_cidadania"`
   - `$dados['disciplina']` não vem (null), então `$novaDisciplina = "meio_ambiente_cidadania"`
   - Normalização: `normalizarDisciplinaAPI("meio_ambiente_cidadania")` → `"meio_ambiente_cidadania"` (sem mudança)
   - Comparação: `"meio_ambiente_cidadania" !== "meio_ambiente_cidadania"` → `false` ✅
   - Validação NÃO deveria ser chamada
   - **MAS:** Se houver algum problema na busca da aula ou na comparação, pode estar detectando como alterada

5. **Cenário do erro reportado:**
   - Mensagem: `"Disciplina 'meio_ambiente_e_cidadania' (normalizada: 'meio_ambiente_e_cidadania') não encontrada"`
   - Isso indica que:
     - A disciplina original é `"meio_ambiente_e_cidadania"` (com "e")
     - A normalização foi aplicada, mas **não removeu o "_e_"**
     - **PROBLEMA:** A função `normalizarDisciplinaAPI()` remove `"_e_"` apenas se estiver entre underscores (`"_e_"`), mas se o valor for `"meio_ambiente_e_cidadania"`, a regex `/\b(de|da|do|das|dos)\b_?/i` não captura `"_e_"` porque "e" não está na lista de palavras comuns!
     - A busca na config falha porque procura `"meio_ambiente_e_cidadania"` mas a config tem `"meio_ambiente_cidadania"`

**CAUSA RAIZ FINAL ✅ CONFIRMADA:**

A função `normalizarDisciplinaAPI()` tem **duas lógicas diferentes**:

1. **Se já tiver underscores** (linha ~805):
   ```php
   if (strpos($normalizado, '_') !== false) {
       // Remover palavras comuns: de, da, do, das, dos
       $normalizado = preg_replace('/\b(de|da|do|das|dos)\b_?/i', '', $normalizado);
       // ...
       return $normalizado; // RETORNA AQUI, NÃO CONTINUA!
   }
   ```
   - Remove apenas: `de`, `da`, `do`, `das`, `dos`
   - **NÃO remove `"e"` quando está entre underscores!**
   - **Retorna imediatamente**, não executa o bloco que remove `"e"`

2. **Se tiver espaços** (linha ~813):
   ```php
   // Remover palavras comuns: de, da, do, das, dos, e, a, o
   $normalizado = preg_replace('/\b(de|da|do|das|dos|e|a|o|as|os)\b/i', '', $normalizado);
   ```
   - Remove: `de`, `da`, `do`, `das`, `dos`, **`e`**, `a`, `o`, `as`, `os`
   - **Remove `"e"`!**

**PROBLEMA:**
- Se o banco tiver `"meio_ambiente_e_cidadania"` (com "e" e underscores), a função entra no primeiro bloco
- Remove apenas `de`, `da`, `do`, mas **NÃO remove `"_e_"`**
- Retorna `"meio_ambiente_e_cidadania"` (sem mudança)
- A busca na config procura `"meio_ambiente_e_cidadania"`, mas a config tem `"meio_ambiente_cidadania"` (sem "e")
- Resultado: **"disciplina não encontrada"** ❌

**SOLUÇÃO:**
- Adicionar `"e"` na lista de palavras comuns do primeiro bloco (linha ~807)
- Ou ajustar a regex para capturar `"_e_"` especificamente

### Próximos Passos (FASE 2)

1. **✅ Adicionar campo `disciplina` no `formEditarAgendamento`:**
   - Adicionar `<input type="hidden" name="disciplina" id="editDisciplina">`
   - Preencher com valor do banco ao abrir modal

2. **✅ Corrigir função `normalizarDisciplinaAPI()`:**
   - Adicionar `"e"` na lista de palavras comuns do primeiro bloco (quando já tem underscores)
   - Ou ajustar regex para capturar `"_e_"` especificamente: `preg_replace('/_(de|da|do|das|dos|e)_/i', '_', $normalizado)`

3. **✅ Normalizar `$disciplinaOriginal` antes de comparar:**
   - Aplicar normalização em ambas as disciplinas antes da comparação
   - Garantir consistência

4. **✅ Adicionar logs para debug:**
   - Log de `$disciplinaOriginal` (antes e depois de normalizar)
   - Log de `$novaDisciplina` (antes e depois de normalizar)
   - Log do resultado da comparação `$disciplinaAlterada`
   - Log do valor buscado na config

---

---

## 11. RESUMO EXECUTIVO

### Problema
Ao editar uma aula teórica (apenas trocando instrutor), aparece erro: "Disciplina 'meio_ambiente_e_cidadania' não encontrada na configuração do curso 'formacao_45h'"

### Causa Raiz Identificada

1. **Campo `disciplina` não está no FormData de edição:**
   - Modal `formEditarAgendamento` não possui campo `disciplina` (hidden ou visível)
   - Backend usa `$disciplinaOriginal` do banco quando campo não vem

2. **Função de normalização não remove `"e"` quando está entre underscores:**
   - `normalizarDisciplinaAPI()` tem dois blocos: um para valores com underscores, outro para espaços
   - O bloco de underscores remove apenas `de`, `da`, `do`, `das`, `dos` - **NÃO remove `"e"`**
   - Se o banco tiver `"meio_ambiente_e_cidadania"`, a normalização não remove `"_e_"`, mantendo o valor
   - A busca na config procura `"meio_ambiente_e_cidadania"`, mas a config tem `"meio_ambiente_cidadania"` (sem "e")
   - Resultado: "disciplina não encontrada"

3. **`$disciplinaOriginal` não é normalizado antes da comparação:**
   - Pode causar comparação incorreta se o banco tiver valor não normalizado

### Solução Proposta

1. Adicionar campo `disciplina` (hidden) no `formEditarAgendamento`
2. Corrigir `normalizarDisciplinaAPI()` para remover `"e"` quando está entre underscores
3. Normalizar `$disciplinaOriginal` antes de comparar
4. Adicionar logs para debug

---

**Data da Investigação:** 2025-11-21  
**Status:** ✅ Investigação completa - Pronto para FASE 2 (correção)  
**Causa Raiz:** ✅ Identificada e documentada

