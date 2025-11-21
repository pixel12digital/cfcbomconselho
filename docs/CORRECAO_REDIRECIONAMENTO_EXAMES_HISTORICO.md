# Correção: Redirecionamento após Salvar Exame vindo do Histórico

## Problema

Quando o usuário acessava a página de exames vindo do histórico do aluno (`origem=historico` + `aluno_id`), após salvar o exame:

1. ✅ O exame era salvo corretamente
2. ✅ O alert de sucesso era exibido
3. ❌ `location.reload()` recarregava a mesma URL com `origem=historico`
4. ❌ O listener `DOMContentLoaded` detectava `origem=historico` e abria o modal automaticamente novamente
5. ❌ O usuário via um modal vazio como se fosse agendar outro exame

## Solução

**Arquivo:** `admin/pages/exames.php`

**Função:** `agendarExame()` (linha ~2377)

**Alteração:** Substituir `location.reload()` por lógica condicional que verifica a origem do acesso.

### Código Antes

```javascript
.then(data => {
    if (data.success === true) {
        const mensagem = data.message || 'Exame agendado com sucesso!';
        alert('✅ ' + mensagem);
        location.reload();  // ⚠️ Sempre recarrega a mesma página
    }
})
```

### Código Depois

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

## Comportamento Após Correção

### Cenário 1: Acesso via Menu

**URL:** `index.php?page=exames&tipo=medico`

**Fluxo:**
1. Usuário clica em "Agendar Exame" (abre modal)
2. Preenche e salva
3. ✅ Exame salvo
4. ✅ Alert de sucesso exibido
5. ✅ `location.reload()` recarrega `page=exames&tipo=medico`
6. ✅ Modal **não** abre automaticamente (não há `origem=historico` na URL)

### Cenário 2: Acesso via Histórico do Aluno

**URL:** `index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`

**Fluxo:**
1. Modal abre automaticamente (devido ao `DOMContentLoaded`)
2. Aluno 167 já está pré-selecionado
3. Usuário preenche e salva
4. ✅ Exame salvo
5. ✅ Alert de sucesso exibido
6. ✅ Redireciona para `index.php?page=historico-aluno&id=167`
7. ✅ Modal **não** abre novamente (usuário está no histórico, não na página de exames)

## Arquivos Modificados

1. **`admin/pages/exames.php`**
   - Linha ~2377-2395: Função `agendarExame()` - Lógica condicional de redirecionamento

## Regras Mantidas

✅ **Não alterado:** Listener `DOMContentLoaded` que abre modal automaticamente (linha ~2129)
- Continua funcionando para o primeiro acesso vindo do histórico
- Não interfere após o redirecionamento (usuário já está no histórico)

✅ **Não alterado:** HTML dos links do menu e do histórico
- Links continuam apontando para as mesmas URLs

✅ **Não alterado:** API `admin/api/exames_simple.php`
- Processamento de salvamento permanece inalterado

## Testes de Validação

### Teste 1: Via Menu

1. Abrir `index.php?page=exames&tipo=medico`
2. Clicar em "Agendar Exame"
3. Preencher formulário e salvar
4. **Verificar:**
   - ✅ Exame foi salvo no banco
   - ✅ Alert de sucesso aparece
   - ✅ Página recarrega em `page=exames&tipo=medico`
   - ✅ Modal **não** abre sozinho após reload

### Teste 2: Via Histórico do Aluno

1. Abrir `index.php?page=historico-aluno&id=167`
2. Clicar em "Agendar Exame" (Médico ou Psicotécnico)
3. **Verificar URL:** `index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`
4. **Verificar:** Modal abre automaticamente e aluno 167 está pré-selecionado
5. Preencher dados e salvar
6. **Verificar:**
   - ✅ Exame foi salvo no banco
   - ✅ Alert de sucesso aparece
   - ✅ Redireciona para `index.php?page=historico-aluno&id=167`
   - ✅ Modal **não** abre novamente em branco

## Benefícios

1. ✅ **Experiência melhorada:** Usuário volta para o histórico após agendar, vendo o exame recém-criado
2. ✅ **Sem modais indesejados:** Modal não abre automaticamente após salvar
3. ✅ **Fluxo intuitivo:** Comportamento diferente para acesso via menu vs histórico
4. ✅ **Mínimo impacto:** Apenas uma função foi alterada, mantendo toda a estrutura existente

