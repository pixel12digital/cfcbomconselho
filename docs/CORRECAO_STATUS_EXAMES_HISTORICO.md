# Correção: Lógica de Status dos Exames no Histórico do Aluno

## Problema Identificado

No histórico do aluno, os cards de Exame Médico e Exame Psicotécnico estavam exibindo badges conflitantes:
- Badge verde "CONCLUÍDO" (baseada no campo `status`)
- Badge amarela "PENDENTE" (baseada no campo `resultado`)

Isso acontecia quando o exame tinha `status = 'concluido'` mas o campo `resultado` ainda estava vazio, null ou 'pendente'.

## Causa Raiz

A lógica de exibição das badges estava verificando apenas:
1. **Badge de Status:** Campo `status` do banco (agendado/concluido/cancelado)
2. **Badge de Resultado:** Campo `resultado` do banco (apto/inapto/null)

O problema era que quando o resultado era lançado, a API atualiza o `status` para 'concluido', mas se o `resultado` não fosse atualizado corretamente ou ainda estivesse como 'pendente', apareciam ambas as badges.

## Solução Implementada

### 1. Função Helper Centralizada

Criada função `renderizarBadgesExame($exame)` que:
- Centraliza a lógica de exibição de badges
- Garante consistência entre histórico e tela de exames
- Verifica se há resultado lançado de forma robusta

**Lógica de Verificação de Resultado:**
```php
// Considera que tem resultado se:
// 1. O campo resultado não está vazio/null e não é 'pendente'
//    E está em ['apto', 'inapto', 'inapto_temporario', 'aprovado', 'reprovado']
// 2. OU existe data_resultado preenchida
```

### 2. Regras de Exibição

**Badge Principal (Status):**
- `agendado` → Badge laranja "Agendado"
- `concluido` → Badge verde "Concluído"
- `cancelado` → Badge vermelha "Cancelado"

**Badge Secundária (Resultado):**
- **Se tem resultado lançado:**
  - `apto` / `aprovado` → Badge verde "Apto"
  - `inapto` / `reprovado` → Badge vermelha "Inapto"
  - `inapto_temporario` → Badge amarela "Inapto Temporário"
- **Se não tem resultado lançado:**
  - Badge amarela "Pendente"

### 3. Bloco "Exames Pendentes"

Ajustada a lógica para verificar corretamente se o exame tem resultado lançado:
- Usa a mesma função helper `renderizarBadgesExame()`
- Só lista como pendente se:
  - Exame não existe OU
  - Exame existe mas não tem resultado lançado E não está cancelado

## Alterações Realizadas

### Arquivo: `admin/pages/historico-aluno.php`

**1. Função Helper (linha ~170-240):**
```php
function renderizarBadgesExame($exame) {
    // Verifica se tem resultado lançado
    // Retorna badges de status e resultado
    // Inclui logs de debug
}
```

**2. Cards de Exame Médico (linha ~1046-1073):**
- Substituída lógica inline por chamada à função helper
- Usa `$badgesMedico['status_badge']` e `$badgesMedico['resultado_badge']`

**3. Cards de Exame Psicotécnico (linha ~1135-1162):**
- Substituída lógica inline por chamada à função helper
- Usa `$badgesPsicotecnico['status_badge']` e `$badgesPsicotecnico['resultado_badge']`

**4. Bloco "Exames Pendentes" (linha ~1230-1250):**
- Usa função helper para verificar se tem resultado
- Logs detalhados para debug
- Lista apenas exames realmente pendentes

## Logs de Debug

Adicionados logs em pontos estratégicos:

```php
// Na função helper
error_log('[DEBUG EXAME] id={id}, status={status}, resultado={resultado}, data_resultado={data_resultado}');

// No bloco de exames pendentes
error_log('[EXAMES PENDENTES] Aluno {id} - Total pendentes: {n} - Lista: {lista}');
```

## Testes Esperados

### Cenário 1: Exame Agendado sem Resultado
- **Status:** `agendado`
- **Resultado:** `null` ou `pendente`
- **Esperado:**
  - Badge principal: "Agendado" (laranja)
  - Badge secundária: "Pendente" (amarela)
  - Listado em "Exames Pendentes"

### Cenário 2: Exame com Resultado APTO
- **Status:** `concluido`
- **Resultado:** `apto`
- **Data Resultado:** preenchida
- **Esperado:**
  - Badge principal: "Concluído" (verde)
  - Badge secundária: "Apto" (verde)
  - **NÃO** listado em "Exames Pendentes"

### Cenário 3: Exame com Resultado INAPTO
- **Status:** `concluido`
- **Resultado:** `inapto`
- **Data Resultado:** preenchida
- **Esperado:**
  - Badge principal: "Concluído" (verde)
  - Badge secundária: "Inapto" (vermelha)
  - **NÃO** listado em "Exames Pendentes"

### Cenário 4: Exame Cancelado
- **Status:** `cancelado`
- **Resultado:** qualquer
- **Esperado:**
  - Badge principal: "Cancelado" (vermelha)
  - Badge secundária: conforme resultado (ou "Pendente" se não tiver)
  - **NÃO** listado em "Exames Pendentes"

## Garantias

✅ **Função Centralizada:** Lógica única para renderização de badges
✅ **Verificação Robusta:** Verifica tanto campo `resultado` quanto `data_resultado`
✅ **Logs Detalhados:** Facilita debug e validação
✅ **Consistência:** Mesma lógica pode ser reutilizada na tela de exames
✅ **Não quebra funcionalidades:** Mantém bloqueio financeiro e fluxo de redirecionamento

## Próximos Passos (Opcional)

- Criar arquivo helper separado (`admin/includes/ExameHelper.php`) para reutilizar em outras páginas
- Aplicar mesma lógica na tela de exames (`admin/pages/exames.php`) para garantir consistência visual

