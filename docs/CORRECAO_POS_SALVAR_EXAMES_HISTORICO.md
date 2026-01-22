# Correção: Pós-Salvar dos Modais de Exames (Agendamento + Resultado)

## Problema

Quando o usuário acessava a página de exames vindo do histórico do aluno (`origem=historico` + `aluno_id` ou `exame_id`), após salvar:

1. ✅ O exame/resultado era salvo corretamente
2. ✅ O alert de sucesso era exibido
3. ❌ `location.reload()` recarregava a mesma URL com `origem=historico`
4. ❌ O listener `DOMContentLoaded` detectava `origem=historico` e abria o modal automaticamente novamente
5. ❌ O usuário via um modal vazio como se fosse agendar/lançar outro exame

## Solução

**Arquivo:** `admin/pages/exames.php`

**Alterações realizadas:**

### 1. Variáveis Globais para Parâmetros da URL

**Linha:** ~2128-2134

**Antes:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    const origem = urlParams.get('origem');
    const alunoId = urlParams.get('aluno_id');
    const exameId = urlParams.get('exame_id');
    // ... variáveis locais
});
```

**Depois:**
```javascript
// Variáveis globais para parâmetros da URL (usadas em agendarExame e salvarResultado)
let urlParamsOrigem = null;
let urlParamsAlunoId = null;
let urlParamsExameId = null;

document.addEventListener('DOMContentLoaded', function() {
    const urlParams = new URLSearchParams(window.location.search);
    urlParamsOrigem = urlParams.get('origem');
    urlParamsAlunoId = urlParams.get('aluno_id');
    urlParamsExameId = urlParams.get('exame_id');
    // ... usa variáveis globais
});
```

**Justificativa:** As variáveis precisam ser acessíveis nas funções `agendarExame()` e `salvarResultado()` que são chamadas após o `DOMContentLoaded`.

### 2. Função `agendarExame()` - Fechar Modal e Remover Parâmetros

**Linha:** ~2377-2395

**Antes:**
```javascript
.then(data => {
    if (data.success === true) {
        const mensagem = data.message || 'Exame agendado com sucesso!';
        alert('✅ ' + mensagem);
        
        // Verificar se veio do histórico do aluno
        const urlParams = new URLSearchParams(window.location.search);
        const origem = urlParams.get('origem');
        const alunoId = urlParams.get('aluno_id');
        
        if (origem === 'historico' && alunoId) {
            // Redirecionar de volta para o histórico do aluno
            window.location.href = `index.php?page=historico-aluno&id=${alunoId}`;
        } else {
            // Acesso normal via menu: recarregar a página de exames
            location.reload();
        }
    }
})
```

**Depois:**
```javascript
.then(data => {
    if (data.success === true) {
        const mensagem = data.message || 'Exame agendado com sucesso!';
        alert('✅ ' + mensagem);
        
        // Fechar o modal de agendamento
        const modalAgendar = document.getElementById('modalAgendarExame');
        if (modalAgendar) {
            const bsModal = bootstrap.Modal.getInstance(modalAgendar);
            if (bsModal) {
                bsModal.hide();
            }
        }
        
        // Verificar se veio do histórico
        // Usar variáveis globais já lidas no DOMContentLoaded
        if (urlParamsOrigem === 'historico') {
            // Remover parâmetros que causam reabertura automática do modal
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('origem');
            currentUrl.searchParams.delete('aluno_id');
            currentUrl.searchParams.delete('exame_id');
            window.location.href = currentUrl.toString();
        } else {
            // Fluxo normal, quando vim pelo menu lateral de Exames
            location.reload();
        }
    }
})
```

**Mudanças:**
- ✅ Fecha o modal explicitamente antes de recarregar
- ✅ Remove parâmetros `origem`, `aluno_id`, `exame_id` da URL quando vem do histórico
- ✅ Mantém `page=exames&tipo=...` na URL (não altera a página base)
- ✅ Usa variáveis globais já lidas no `DOMContentLoaded`

### 3. Função `salvarResultado()` - Mesma Lógica

**Linha:** ~2442-2455

**Antes:**
```javascript
.then(data => {
    if (data.success) {
        alert('Resultado salvo com sucesso!');
        location.reload();
    }
})
```

**Depois:**
```javascript
.then(data => {
    if (data.success) {
        alert('Resultado salvo com sucesso!');
        
        // Fechar o modal de resultado
        const modalResultado = document.getElementById('modalResultadoExame');
        if (modalResultado) {
            const bsModal = bootstrap.Modal.getInstance(modalResultado);
            if (bsModal) {
                bsModal.hide();
            }
        }
        
        // Verificar se veio do histórico
        // Usar variáveis globais já lidas no DOMContentLoaded
        if (urlParamsOrigem === 'historico') {
            // Remover parâmetros que causam reabertura automática do modal
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.delete('origem');
            currentUrl.searchParams.delete('aluno_id');
            currentUrl.searchParams.delete('exame_id');
            window.location.href = currentUrl.toString();
        } else {
            // Fluxo normal, quando vim pelo menu lateral de Exames
            location.reload();
        }
    }
})
```

**Mudanças:**
- ✅ Fecha o modal explicitamente antes de recarregar
- ✅ Remove parâmetros `origem`, `aluno_id`, `exame_id` da URL quando vem do histórico
- ✅ Mantém `page=exames&tipo=...` na URL
- ✅ Usa variáveis globais já lidas no `DOMContentLoaded`

## Comportamento Após Correção

### Cenário 1: Acesso via Menu (sem `origem=historico`)

**URL:** `index.php?page=exames&tipo=medico`

**Fluxo:**
1. Usuário clica em "Agendar Exame" (abre modal)
2. Preenche e salva
3. ✅ Exame salvo
4. ✅ Alert de sucesso
5. ✅ Modal fecha
6. ✅ `location.reload()` recarrega `page=exames&tipo=medico`
7. ✅ Modal **não** abre automaticamente (não há `origem=historico` na URL)

### Cenário 2: Acesso via Histórico - Agendar Exame

**URL:** `index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`

**Fluxo:**
1. Modal abre automaticamente (devido ao `DOMContentLoaded`)
2. Aluno 167 já está pré-selecionado
3. Usuário preenche e salva
4. ✅ Exame salvo
5. ✅ Alert de sucesso
6. ✅ Modal fecha
7. ✅ URL atualizada para `index.php?page=exames&tipo=medico` (parâmetros removidos)
8. ✅ Página recarrega sem `origem=historico`
9. ✅ Modal **não** abre novamente

### Cenário 3: Acesso via Histórico - Lançar Resultado

**URL:** `index.php?page=exames&tipo=medico&exame_id=123&origem=historico`

**Fluxo:**
1. Modal de resultado abre automaticamente (devido ao `DOMContentLoaded`)
2. Exame 123 já está carregado
3. Usuário preenche resultado e salva
4. ✅ Resultado salvo
5. ✅ Alert de sucesso
6. ✅ Modal fecha
7. ✅ URL atualizada para `index.php?page=exames&tipo=medico` (parâmetros removidos)
8. ✅ Página recarrega sem `origem=historico`
9. ✅ Modal **não** abre novamente

## Arquivos Modificados

1. **`admin/pages/exames.php`**
   - Linha ~2128-2134: Variáveis globais para parâmetros da URL
   - Linha ~2377-2395: Função `agendarExame()` - Fechar modal e remover parâmetros
   - Linha ~2442-2455: Função `salvarResultado()` - Fechar modal e remover parâmetros

## Regras Mantidas

✅ **Não alterado:** Listener `DOMContentLoaded` que abre modal automaticamente (linha ~2129)
- Continua funcionando para o primeiro acesso vindo do histórico
- Não interfere após salvar (parâmetros são removidos da URL)

✅ **Não alterado:** HTML dos links do menu e do histórico
- Links continuam apontando para as mesmas URLs

✅ **Não alterado:** API `admin/api/exames_simple.php`
- Processamento de salvamento permanece inalterado

✅ **Não alterado:** Listagem, filtros e contadores
- Toda a lógica de exibição permanece intacta

## Testes de Validação

### Teste 1: Via Menu - Agendar Exame

1. Abrir `index.php?page=exames&tipo=medico`
2. Clicar em "Agendar Exame"
3. Preencher formulário e salvar
4. **Verificar:**
   - ✅ Exame foi salvo no banco
   - ✅ Alert de sucesso aparece
   - ✅ Modal fecha
   - ✅ Página recarrega em `page=exames&tipo=medico`
   - ✅ Modal **não** abre sozinho após reload

### Teste 2: Via Menu - Lançar Resultado

1. Abrir `index.php?page=exames&tipo=medico`
2. Clicar em "Lançar Resultado" em um exame da lista
3. Preencher resultado e salvar
4. **Verificar:**
   - ✅ Resultado foi salvo no banco
   - ✅ Alert de sucesso aparece
   - ✅ Modal fecha
   - ✅ Página recarrega em `page=exames&tipo=medico`
   - ✅ Modal **não** abre sozinho após reload

### Teste 3: Via Histórico - Agendar Exame

1. Abrir `index.php?page=historico-aluno&id=167`
2. Clicar em "Agendar Exame" (Médico ou Psicotécnico)
3. **Verificar URL:** `index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`
4. **Verificar:** Modal abre automaticamente e aluno 167 está pré-selecionado
5. Preencher dados e salvar
6. **Verificar:**
   - ✅ Exame foi salvo no banco
   - ✅ Alert de sucesso aparece
   - ✅ Modal fecha
   - ✅ URL atualizada para `index.php?page=exames&tipo=medico` (sem parâmetros)
   - ✅ Página recarrega
   - ✅ Modal **não** abre novamente em branco

### Teste 4: Via Histórico - Lançar Resultado

1. Abrir `index.php?page=historico-aluno&id=167`
2. Clicar em "Lançar Resultado" (quando há exame agendado)
3. **Verificar URL:** `index.php?page=exames&tipo=medico&exame_id=123&origem=historico`
4. **Verificar:** Modal de resultado abre automaticamente
5. Preencher resultado e salvar
6. **Verificar:**
   - ✅ Resultado foi salvo no banco
   - ✅ Alert de sucesso aparece
   - ✅ Modal fecha
   - ✅ URL atualizada para `index.php?page=exames&tipo=medico` (sem parâmetros)
   - ✅ Página recarrega
   - ✅ Modal **não** abre novamente em branco

## Benefícios

1. ✅ **Experiência melhorada:** Modal não abre automaticamente após salvar
2. ✅ **Fluxo intuitivo:** Usuário permanece na página de exames após salvar (não volta ao histórico)
3. ✅ **Sem modais indesejados:** Modal não reabre em branco após salvar
4. ✅ **Mínimo impacto:** Apenas três trechos foram alterados, mantendo toda a estrutura existente
5. ✅ **Consistência:** Mesma lógica aplicada para agendamento e resultado

