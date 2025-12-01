# INVESTIGAÇÃO: Persistência do Problema de Status no Modal de Edição

**Data:** 28/11/2025  
**Problema:** Modal "Editar Aluno" não atualiza o status do aluno, mesmo após múltiplas correções  
**Status Atual:** ❌ PROBLEMA PERSISTE

---

## 📋 RESUMO EXECUTIVO

O problema de atualização de status do aluno no modal "Editar Aluno" persiste mesmo após implementação de múltiplas correções. Os logs mostram que:

1. ✅ O campo `status` é **preenchido corretamente** com "inativo" quando o modal abre
2. ✅ A API retorna o status correto (`"status":"inativo"`)
3. ❌ Quando o usuário clica em "Salvar Aluno", o código lê **"ativo"** do select, não "inativo"
4. ❌ O botão fica travado em "Salvando Dados..." (problema secundário já resolvido parcialmente)

---

## 🔍 HISTÓRICO DE TENTATIVAS DE CORREÇÃO

### **Tentativa 1: Alinhar Fluxo Rápido com API**
**Data:** Inicial  
**Arquivo:** `admin/pages/alunos.php`  
**Mudança:** Refatorar `alterarStatusAluno()` para usar `fetchAPIAlunos` com `PUT` para `admin/api/alunos.php?id={id}`  
**Resultado:** ✅ **SUCESSO** - Botão de ação rápida funciona perfeitamente  
**Status:** Implementado e funcionando

---

### **Tentativa 2: Validar e Instrumentar Fluxo do Modal**
**Data:** Segunda rodada  
**Arquivos:** 
- `admin/assets/js/alunos.js`
- `admin/api/alunos.php`

**Mudanças:**
- Adicionar `console.log` para debug de status
- Confirmar que `status` está em `$camposPermitidos`
- Adicionar `error_log` temporário na API

**Resultado:** ⚠️ **PARCIAL** - Logs mostraram que o status não estava sendo enviado corretamente  
**Status:** Implementado, mas problema persistiu

---

### **Tentativa 3: Unificar `saveAlunoDados` com API**
**Data:** Terceira rodada  
**Arquivo:** `admin/pages/alunos.php`  
**Mudanças:**
- Refatorar `saveAlunoDados()` para usar `fetchAPIAlunos`
- Usar `PUT` com JSON quando sem foto
- Usar `POST` com FormData + `_method='PUT'` quando com foto
- Garantir que `status` é lido diretamente do `<select>` e incluído no payload

**Resultado:** ⚠️ **PARCIAL** - Fluxo unificado, mas status ainda não atualiza  
**Status:** Implementado, mas problema persistiu

---

### **Tentativa 4: Corrigir Erro de Sintaxe PHP**
**Data:** Quarta rodada  
**Arquivo:** `admin/api/alunos.php`  
**Problema:** `Parse error: syntax error, unexpected token "case"` na linha 1250  
**Mudanças:**
- Reorganizar router por método HTTP (um único `switch ($method)`)
- Consolidar lógica de `POST` e `PUT`
- Garantir que todas as respostas sejam JSON

**Resultado:** ✅ **SUCESSO** - Erro de sintaxe corrigido  
**Status:** Implementado e funcionando

---

### **Tentativa 5: Tratar `rg_data_emissao` e CPF Duplicado**
**Data:** Quinta rodada  
**Arquivos:**
- `admin/pages/alunos.php` (JS)
- `admin/api/alunos.php` (PHP)

**Mudanças:**
- Tratar `rg_data_emissao` com valor "0000-00-00" no JS
- Tratar CPF duplicado no PHP (preservar CPF original se conflito)

**Resultado:** ✅ **SUCESSO** - Problemas secundários resolvidos  
**Status:** Implementado e funcionando

---

### **Tentativa 6: Corrigir `SyntaxError` de Variável Duplicada**
**Data:** Sexta rodada  
**Arquivo:** `admin/pages/alunos.php`  
**Problema:** `SyntaxError: Identifier 'campoRgDataEmissao' has already been declared`  
**Mudanças:**
- Remover declaração duplicada de `const campoRgDataEmissao`
- Mudar `const rgDataEmissaoValor` para `let`

**Resultado:** ✅ **SUCESSO** - Erro de sintaxe corrigido  
**Status:** Implementado e funcionando

---

### **Tentativa 7: Corrigir Travamento do Modal**
**Data:** Sétima rodada  
**Arquivo:** `admin/pages/alunos.php`  
**Mudanças:**
- Tornar atualizações de resumo "fire and forget" (sem `await`)
- Garantir que modal fecha imediatamente após save bem-sucedido
- Adicionar `try...catch...finally` para sempre restaurar botão
- Corrigir URL de foto (evitar `${fotoUrl}` literal)

**Resultado:** ⚠️ **PARCIAL** - Modal não trava mais, mas status ainda não atualiza  
**Status:** Implementado, mas problema principal persiste

---

## 🔬 ANÁLISE DOS LOGS ATUAIS

### **Logs de Carregamento do Modal:**
```
✅ Campo status preenchido corretamente
  - Valor anterior: "ativo"
  - Valor novo: "inativo"
  - Valor atual: "inativo"
```

**Conclusão:** O campo `status` é preenchido corretamente com "inativo" quando o modal abre.

---

### **Logs ao Clicar em "Salvar Aluno":**
```
[DEBUG STATUS MODAL] Status no FormData: ativo
[DEBUG STATUS MODAL] Status lido do select (direto): ativo
[DEBUG STATUS MODAL] isEditing: true
[DEBUG STATUS MODAL] alunoId: 168
[DEBUG STATUS MODAL] Modo: EDIÇÃO sem FOTO - usando JSON
[SAVE ALUNO] Enviando payload para API (EDIÇÃO): Object
```

**Conclusão:** Quando o usuário clica em "Salvar Aluno", o código lê **"ativo"** do select, mesmo que o modal tenha sido preenchido com "inativo".

---

## 🎯 POSSÍVEIS CAUSAS DO PROBLEMA

### **Causa 1: Select sendo Resetado Após Preenchimento** ⚠️ **MAIS PROVÁVEL**

**Hipótese:** Algum código está resetando o valor do select `#status` após `preencherFormularioAluno()` ser executado.

**Evidências:**
- Logs mostram que o campo é preenchido corretamente
- Mas quando `saveAlunoDados()` é chamado, o valor lido é "ativo" (valor padrão)

**Onde investigar:**
- Funções que manipulam o formulário após o modal abrir
- Event listeners que podem estar resetando campos
- Código que limpa/reseta o formulário
- Código relacionado a abas do modal (Dados, Matrícula, Documentos, Histórico)

**Arquivos para verificar:**
- `admin/pages/alunos.php` - Funções relacionadas a resetar formulário
- `admin/pages/alunos.php` - Event listeners do modal
- `admin/pages/alunos.php` - Código de troca de abas

---

### **Causa 2: Múltiplos Elementos com `id="status"`** ⚠️ **PROVÁVEL**

**Hipótese:** Existem múltiplos elementos com `id="status"` no DOM, e `document.getElementById('status')` está retornando o elemento errado.

**Evidências:**
- O modal tem múltiplas abas (Dados, Matrícula, Documentos, Histórico)
- Pode haver um select `#status` em cada aba ou em diferentes contextos

**Onde investigar:**
- HTML do modal - verificar se há múltiplos `id="status"`
- Seletor usado em `saveAlunoDados()` - pode estar pegando o elemento errado

**Solução proposta:**
- Usar seletor mais específico: `formAluno.querySelector('#status')` ou `formAluno.querySelector('select[name="status"]')`
- Verificar se há conflito de IDs no HTML

---

### **Causa 3: Problema de Timing / Race Condition** ⚠️ **POSSÍVEL**

**Hipótese:** O valor do select está sendo lido antes de ser definido, ou há uma condição de corrida entre o preenchimento e a leitura.

**Evidências:**
- O preenchimento é assíncrono (carrega dados da API)
- Pode haver código que executa após o preenchimento e sobrescreve o valor

**Onde investigar:**
- Ordem de execução das funções ao abrir o modal
- Código que executa após `preencherFormularioAluno()`
- Event listeners que podem estar alterando o valor

---

### **Causa 4: Valor Padrão do Select** ⚠️ **POSSÍVEL**

**Hipótese:** O select `#status` tem um valor padrão "ativo" definido no HTML, e esse valor não está sendo sobrescrito corretamente.

**Evidências:**
- O valor lido é sempre "ativo" (valor padrão)
- Mesmo que o log mostre que foi preenchido com "inativo"

**Onde investigar:**
- HTML do select `#status` - verificar se há `selected` ou `value="ativo"` definido
- Código que preenche o select - verificar se está realmente definindo o valor

---

### **Causa 5: FormData Capturando Valor Antigo** ⚠️ **MENOS PROVÁVEL**

**Hipótese:** O `FormData` está sendo criado antes do select ser atualizado, ou está capturando um valor em cache.

**Evidências:**
- O código já lê diretamente do select (`statusSelect.value`), não do FormData
- Mas o log mostra que FormData também tem "ativo"

**Onde investigar:**
- Ordem de criação do FormData vs. atualização do select
- Se o FormData está sendo criado muito cedo

---

## 💡 SOLUÇÕES PROPOSTAS (SEM APLICAÇÃO)

### **Solução 1: Verificar e Corrigir Reset do Select**

**Ação:**
1. Adicionar log imediatamente após `preencherFormularioAluno()` para verificar se o valor permanece
2. Adicionar log antes de `saveAlunoDados()` para verificar o valor atual do select
3. Procurar por código que chama `form.reset()`, `select.value = ''`, ou similar
4. Verificar event listeners que podem estar alterando o valor

**Código para adicionar:**
```javascript
// Após preencherFormularioAluno()
setTimeout(() => {
    const statusSelect = document.getElementById('status');
    console.log('[DEBUG] Status após preencherFormularioAluno (500ms depois):', statusSelect?.value);
}, 500);

// Antes de saveAlunoDados()
console.log('[DEBUG] Status ANTES de saveAlunoDados:', document.getElementById('status')?.value);
```

---

### **Solução 2: Usar Seletor Mais Específico**

**Ação:**
1. Verificar se há múltiplos elementos com `id="status"` no DOM
2. Usar seletor mais específico: `formAluno.querySelector('select[name="status"]')`
3. Adicionar validação para garantir que o elemento correto está sendo usado

**Código para modificar:**
```javascript
// Em saveAlunoDados(), trocar:
const statusSelect = document.getElementById('status');

// Por:
const formAluno = document.getElementById('formAluno');
const statusSelect = formAluno?.querySelector('select[name="status"]') || 
                     formAluno?.querySelector('#status');

if (!statusSelect) {
    console.error('[DEBUG STATUS MODAL] Select status não encontrado!');
    return;
}

console.log('[DEBUG STATUS MODAL] Select encontrado:', {
    id: statusSelect.id,
    name: statusSelect.name,
    value: statusSelect.value,
    options: Array.from(statusSelect.options).map(opt => opt.value)
});
```

---

### **Solução 3: Adicionar Observer para Monitorar Mudanças**

**Ação:**
1. Adicionar `MutationObserver` ou event listener para monitorar mudanças no select
2. Logar todas as alterações de valor do select
3. Identificar qual código está alterando o valor

**Código para adicionar:**
```javascript
// Após preencherFormularioAluno()
const statusSelect = document.getElementById('status');
if (statusSelect) {
    // Observer para mudanças de atributo
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'value') {
                console.log('[DEBUG] Select status value alterado via atributo:', statusSelect.value);
            }
        });
    });
    observer.observe(statusSelect, { attributes: true, attributeFilter: ['value'] });
    
    // Event listener para mudanças de valor
    statusSelect.addEventListener('change', (e) => {
        console.log('[DEBUG] Select status alterado via evento change:', e.target.value);
    });
    
    // Interceptar setter de value
    const originalValueSetter = Object.getOwnPropertyDescriptor(HTMLSelectElement.prototype, 'value').set;
    Object.defineProperty(statusSelect, 'value', {
        set: function(newValue) {
            console.log('[DEBUG] Select status value sendo definido:', {
                valorAnterior: this.value,
                valorNovo: newValue,
                stackTrace: new Error().stack
            });
            originalValueSetter.call(this, newValue);
        },
        get: function() {
            return originalValueSetter.get.call(this);
        }
    });
}
```

---

### **Solução 4: Forçar Valor do Select Antes de Salvar**

**Ação:**
1. Em `saveAlunoDados()`, antes de ler o valor, forçar a leitura do valor correto
2. Adicionar validação para garantir que o valor está correto
3. Se não estiver, tentar corrigir antes de enviar

**Código para adicionar:**
```javascript
// Em saveAlunoDados(), antes de ler o status:
const statusSelect = document.getElementById('status');
if (statusSelect) {
    // Forçar atualização do valor (às vezes ajuda com problemas de sincronização)
    const currentValue = statusSelect.value;
    console.log('[DEBUG STATUS MODAL] Valor atual do select:', currentValue);
    
    // Se o valor estiver incorreto, tentar corrigir
    // (isso é um workaround, mas pode ajudar a identificar o problema)
    if (currentValue === 'ativo' && /* algum indicador de que deveria ser inativo */) {
        console.warn('[DEBUG STATUS MODAL] Valor do select parece incorreto, tentando corrigir...');
        // Não corrigir automaticamente, apenas logar
    }
}
```

---

### **Solução 5: Verificar HTML do Select**

**Ação:**
1. Verificar o HTML do select `#status` no modal
2. Verificar se há `selected` ou `value="ativo"` definido no HTML
3. Verificar se há JavaScript que define valor padrão

**Onde verificar:**
- `admin/pages/alunos.php` - HTML do modal, linha ~2252
- Verificar se há `selected` ou `value="ativo"` no `<option>`

---

### **Solução 6: Adicionar Logs Detalhados em Todos os Pontos**

**Ação:**
1. Adicionar logs em TODOS os pontos onde o select `#status` é acessado ou modificado
2. Adicionar stack trace para identificar qual código está alterando o valor
3. Criar um log completo do ciclo de vida do select

**Pontos para adicionar logs:**
- Quando o modal abre
- Quando `preencherFormularioAluno()` é chamado
- Quando o select é preenchido
- Quando qualquer código acessa o select
- Quando `saveAlunoDados()` é chamado
- Quando o valor é lido

---

## 📊 CHECKLIST DE INVESTIGAÇÃO

### **Fase 1: Verificação Básica**
- [ ] Verificar se há múltiplos elementos com `id="status"` no DOM
- [ ] Verificar HTML do select `#status` (valor padrão, `selected`, etc.)
- [ ] Verificar se há código que chama `form.reset()` após preencher
- [ ] Verificar event listeners que podem estar alterando o valor

### **Fase 2: Logs Detalhados**
- [ ] Adicionar log imediatamente após `preencherFormularioAluno()`
- [ ] Adicionar log antes de `saveAlunoDados()`
- [ ] Adicionar `MutationObserver` para monitorar mudanças
- [ ] Adicionar stack trace quando o valor é alterado

### **Fase 3: Correção**
- [ ] Usar seletor mais específico (`formAluno.querySelector()`)
- [ ] Adicionar validação antes de ler o valor
- [ ] Corrigir código que está resetando o select (se identificado)
- [ ] Testar em produção após correção

---

## 🎯 PRÓXIMOS PASSOS RECOMENDADOS

1. **Imediato:** Adicionar logs detalhados para identificar exatamente quando e onde o valor do select está sendo alterado
2. **Curto prazo:** Verificar se há múltiplos elementos com `id="status"` e corrigir se necessário
3. **Médio prazo:** Implementar `MutationObserver` para monitorar mudanças no select
4. **Longo prazo:** Refatorar código para usar seletor mais específico e adicionar validações

---

## 📝 NOTAS ADICIONAIS

- O problema **NÃO** está na API (botão rápido funciona perfeitamente)
- O problema **NÃO** está no backend (status é recebido e salvo corretamente quando enviado)
- O problema **ESTÁ** no frontend, especificamente na leitura do valor do select antes de enviar
- O modal fecha corretamente agora (problema secundário resolvido)
- Os resumos são atualizados em background (problema secundário resolvido)

---

## 🔗 ARQUIVOS RELACIONADOS

- `admin/pages/alunos.php` - Função `preencherFormularioAluno()` (linha ~4323)
- `admin/pages/alunos.php` - Função `saveAlunoDados()` (linha ~7380)
- `admin/pages/alunos.php` - HTML do select `#status` (linha ~2252)
- `admin/api/alunos.php` - API de atualização de alunos
- `admin/assets/js/alunos.js` - Função `fetchAPIAlunos()`

---

**Última atualização:** 28/11/2025  
**Próxima revisão:** Após implementação de logs detalhados



