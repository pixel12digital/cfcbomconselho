# PLANO DE INVESTIGAÇÃO - MODAL TRAVADO E DADOS NÃO VISÍVEIS

## PROBLEMA REPORTADO
1. Modal não fecha (botão FECHAR não executa)
2. Dados não aparecem nos campos (mas logs mostram que foram preenchidos)
3. Página trava completamente

## HIPÓTESES A INVESTIGAR

### HIPÓTESE 1: Valores estão sendo aplicados mas não são visíveis (CSS/Opacidade)
**Sintomas:** Logs mostram `✅ [DEBUG] editDisciplinaNome aplicado: Legislação de Trânsito`, mas campo aparece vazio

**Investigação:**
- [ ] Verificar se campos têm `opacity: 0` ou `visibility: hidden`
- [ ] Verificar se `color` está igual ao `background-color` (texto invisível)
- [ ] Verificar se campos têm `display: none`
- [ ] Verificar se há `-webkit-text-fill-color` sobrescrevendo `color`
- [ ] Verificar se campos readonly têm estilos que escondem texto
- [ ] Verificar z-index dos campos (podem estar atrás de outro elemento)

**Ações:**
1. Adicionar função `window.diagnosticarModal()` que verifica estilos computados
2. Forçar estilos inline com `!important` após preencher valores
3. Verificar se há CSS global sobrescrevendo estilos do modal

---

### HIPÓTESE 2: Valores são aplicados mas depois removidos (Timing/Race Condition)
**Sintomas:** Valores são definidos mas logo depois voltam a ficar vazios

**Investigação:**
- [ ] Verificar se há `setTimeout` ou eventos que resetam campos
- [ ] Verificar se há `MutationObserver` removendo valores
- [ ] Verificar se há funções de "limpar formulário" sendo chamadas
- [ ] Verificar se há eventos `change` ou `input` que resetam valores
- [ ] Verificar ordem de execução dos scripts

**Ações:**
1. Adicionar logs antes/depois de cada operação que modifica campos
2. Usar `Object.defineProperty` para interceptar mudanças em `value`
3. Adicionar verificações periódicas se valores foram mantidos

---

### HIPÓTESE 3: Campos readonly não estão recebendo valores (Propriedade readonly)
**Sintomas:** Campo `editDisciplinaNome` é readonly e pode não aceitar `value`

**Investigação:**
- [ ] Verificar se campos readonly podem ter `.value` modificado
- [ ] Verificar se precisa usar `setAttribute('value', ...)` além de `.value`
- [ ] Verificar se precisa remover readonly temporariamente

**Ações:**
1. Testar se `campo.readonly = false; campo.value = 'X'; campo.readonly = true` funciona
2. Usar `campo.setAttribute('value', valor)` e `campo.value = valor`
3. Disparar eventos `input` e `change` após definir valor

---

### HIPÓTESE 4: Event Listeners estão bloqueados (Overlay/Elementos sobrepostos)
**Sintomas:** Botões não respondem a cliques

**Investigação:**
- [ ] Verificar se há overlay invisível sobre botões
- [ ] Verificar `pointer-events: none` em elementos pais
- [ ] Verificar se botões têm `z-index` menor que overlay
- [ ] Verificar se eventos estão sendo `stopPropagation()` ou `preventDefault()`
- [ ] Verificar se há múltiplos event listeners conflitantes

**Ações:**
1. Adicionar listener na fase de captura (`addEventListener(..., true)`)
2. Verificar todos os overlays com `z-index` alto
3. Forçar `pointer-events: auto` nos botões
4. Adicionar botões com `onclick` inline (não depende de event listeners)

---

### HIPÓTESE 5: Loop infinito ou processo bloqueante (Thread bloqueada)
**Sintomas:** Página completamente travada, não responde a nada

**Investigação:**
- [ ] Verificar se há `while(true)` ou loops infinitos
- [ ] Verificar se há `setInterval` sem clear
- [ ] Verificar se há operações síncronas muito pesadas
- [ ] Verificar se há recursão infinita
- [ ] Verificar performance no DevTools (Performance tab)

**Ações:**
1. Adicionar timeouts em todas as operações pesadas
2. Usar `requestAnimationFrame` para operações visuais
3. Verificar se há `MutationObserver` sem desconectar
4. Adicionar breakpoints e verificar call stack

---

### HIPÓTESE 6: Múltiplos modais sobrepostos (Múltiplas instâncias)
**Sintomas:** Pode haver vários modais criados, um bloqueando o outro

**Investigação:**
- [ ] Verificar quantos elementos com `id="modalEditarAgendamento"` existem
- [ ] Verificar se `criarModalEdicao` está sendo chamada múltiplas vezes
- [ ] Verificar se modais antigos não estão sendo removidos

**Ações:**
1. Remover todos os modais antes de criar novo
2. Verificar se modal já existe antes de criar
3. Adicionar ID único para cada modal criado

---

## PLANO DE AÇÃO IMEDIATO

### FASE 1: DIAGNÓSTICO (AGORA)
1. ✅ Criar função `window.diagnosticarModal()` para verificar estado completo
2. ✅ Adicionar logs detalhados em cada etapa de preenchimento
3. ✅ Verificar estilos computados de todos os campos
4. ✅ Verificar se há elementos bloqueando cliques

### FASE 2: CORREÇÕES PREVENTIVAS
1. Forçar remoção de todos os modais antes de criar novo
2. Adicionar verificações de timing em operações assíncronas
3. Garantir que estilos sejam aplicados após preencher valores
4. Adicionar fallbacks para todos os métodos de fechar modal

### FASE 3: CORREÇÕES DE VISIBILIDADE
1. Aplicar estilos inline forçados após preencher cada campo
2. Garantir que campos readonly recebam valores corretamente
3. Verificar e corrigir conflitos de CSS
4. Adicionar verificações periódicas de valores

### FASE 4: CORREÇÕES DE INTERATIVIDADE
1. Garantir que botões tenham `pointer-events: auto`
2. Adicionar múltiplos métodos de fechar (onclick inline, event listeners, teclado)
3. Verificar e remover overlays bloqueadores
4. Adicionar função de emergência que funciona mesmo com página travada

---

## FERRAMENTAS DE DIAGNÓSTICO

### Console do Navegador
```javascript
// Executar após abrir modal
window.diagnosticarModal()

// Verificar se valores estão definidos
console.log('Disciplina:', document.getElementById('editDisciplinaNome')?.value)
console.log('Data:', document.getElementById('editDataAula')?.value)

// Verificar estilos
const campo = document.getElementById('editDisciplinaNome')
const estilo = window.getComputedStyle(campo)
console.log('Estilos:', {
    color: estilo.color,
    opacity: estilo.opacity,
    visibility: estilo.visibility,
    display: estilo.display
})

// Verificar se botão está clicável
const btn = document.getElementById('btnFecharModalEdicao')
console.log('Botão:', {
    onclick: btn?.onclick,
    zIndex: window.getComputedStyle(btn)?.zIndex,
    pointerEvents: window.getComputedStyle(btn)?.pointerEvents
})
```

### DevTools
1. Abrir Performance tab e gravar durante abertura do modal
2. Verificar se há operações bloqueantes
3. Verificar call stack quando página trava
4. Verificar Network tab para requests que não completam

---

## CHECKLIST DE VALIDAÇÃO

Após implementar correções, verificar:
- [ ] Modal abre e dados aparecem imediatamente
- [ ] Todos os campos são visíveis e legíveis
- [ ] Botão FECHAR funciona (clique e teclado)
- [ ] Botões de emergência funcionam
- [ ] Página não trava
- [ ] Console não mostra erros
- [ ] Valores persistem após pequeno delay
- [ ] Modal fecha corretamente

---

## PRÓXIMOS PASSOS

1. **URGENTE:** Restaurar arquivo `turmas-teoricas-detalhes-inline.php` (foi sobrescrito)
2. Adicionar função de diagnóstico completa
3. Implementar correções de visibilidade
4. Implementar correções de interatividade
5. Testar cada correção isoladamente
6. Documentar soluções aplicadas

