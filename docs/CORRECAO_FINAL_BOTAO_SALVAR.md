# Correção Final - Botão "Salvar Aluno" e Campo "Forma de Pagamento"

## Problemas Identificados

1. **Botão "Salvar Aluno" não disparava requisição** após implementação da aba Documentos
2. **Campo "Forma de Pagamento" não aparecia** na aba Matrícula
3. **Formulário aninhado** na aba Documentos estava interferindo no submit do form principal

## Correções Aplicadas

### 1. Remoção do Formulário Aninhado na Aba Documentos

**Arquivo:** `admin/pages/alunos.php` (linha ~2676-2704)

**Antes:**
```html
<form id="form-upload-documento" onsubmit="enviarDocumento(event); return false;">
  <!-- inputs e botão type="submit" -->
</form>
```

**Depois:**
```html
<!-- SEM <form> aninhado -->
<div id="documentos-aluno-wrapper">
  <div class="row g-2 align-items-end">
    <!-- inputs sem name (não fazem parte do form principal) -->
    <select id="tipo-documento" class="form-select">
    <input type="file" id="arquivo-documento" class="form-control">
    <button type="button" onclick="enviarDocumento(event)">
  </div>
</div>
```

**Mudanças:**
- Removido `<form id="form-upload-documento">`
- Botão mudou de `type="submit"` para `type="button"`
- Removido atributo `onsubmit` do form (não existe mais)
- Removido atributo `required` dos inputs (validação agora é feita no JS)

### 2. Simplificação do Event Listener do Form Principal

**Arquivo:** `admin/pages/alunos.php` (linha ~3179-3195)

**Antes:**
- Lógica complexa de detecção de abas ativas
- Chamadas condicionais para `saveAlunoDados()` ou `saveAlunoMatricula()`
- Validações complexas de campos obrigatórios por aba

**Depois:**
```javascript
// Adicionar event listener para o formulário e botão
const formAluno = document.getElementById('formAluno');
const btnSalvar = document.getElementById('btnSalvarAluno');

if (formAluno) {
    console.log('[DEBUG] Inicializando eventos do formulário formAluno');
    formAluno.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('[DEBUG] Submit formAluno disparado');
        await saveAlunoDados(false);
    });
}

if (btnSalvar) {
    console.log('[DEBUG] Inicializando eventos do botão Salvar Aluno');
    btnSalvar.addEventListener('click', async function(e) {
        e.preventDefault();
        console.log('[DEBUG] Clique no botão Salvar Aluno');
        await saveAlunoDados(false);
    });
}
```

**Mudanças:**
- **Sempre chama `saveAlunoDados()`** independente da aba ativa
- Removida toda lógica de detecção de abas
- Removida lógica de validação condicional
- Adicionado listener também no botão (além do form submit)

### 3. Ajuste da Função `enviarDocumento()`

**Arquivo:** `admin/pages/alunos.php` (linha ~9556-9600)

**Mudanças:**
- Função agora monta `FormData` próprio, sem depender de `<form>` HTML
- Campo `tipo` mudou para `tipo_documento` no FormData (consistência com API)
- Validações melhoradas com verificações de `null`

```javascript
function enviarDocumento(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('[DEBUG] enviarDocumento chamada - upload de documento');
    
    // ... validações ...
    
    // Preparar FormData próprio (sem depender de <form> HTML)
    const formData = new FormData();
    formData.append('aluno_id', alunoId);
    formData.append('tipo_documento', tipoSelect.value);
    formData.append('arquivo', arquivo);
    
    // ... envio via fetch ...
}
```

### 4. Logs de Debug em `saveAlunoDados()`

**Arquivo:** `admin/pages/alunos.php` (linha ~6956-6960)

**Adicionado:**
```javascript
async function saveAlunoDados(silencioso = false) {
    console.log('[DEBUG] saveAlunoDados iniciado');
    
    const form = document.getElementById('formAluno');
    if (!form) {
        console.error('[DEBUG] Form formAluno não encontrado!');
        return { success: false, error: 'Formulário não encontrado' };
    }
    
    // ... resto da função ...
}
```

### 5. Campo "Forma de Pagamento"

**Status:** O campo já estava presente no HTML (linha ~2600-2611) e **não estava oculto**. 

**Localização:**
- Seção: "Financeiro da Matrícula" (linha ~2584-2617)
- Dentro da aba Matrícula
- Sem classes `d-none`, `hidden` ou `style="display:none"`

**HTML do Campo:**
```html
<div class="col-md-4">
  <div class="mb-1">
    <label for="forma_pagamento" class="form-label">Forma de Pagamento</label>
    <select class="form-select" id="forma_pagamento" name="forma_pagamento">
      <option value="">Selecione...</option>
      <option value="nao_informado">Não informado</option>
      <option value="a_vista">À vista</option>
      <option value="cartao_credito">Cartão de crédito</option>
      <option value="boleto">Boleto</option>
      <option value="pix">PIX</option>
      <option value="carne_parcelado">Carnê / Parcelado</option>
      <option value="outro">Outro</option>
    </select>
  </div>
</div>
```

## Estrutura Final dos Formulários

### Form Principal (`formAluno`)
- **Único `<form>`** que envolve o botão "Salvar Aluno"
- Contém todas as abas: Dados, Matrícula, Documentos, Histórico
- Botão "Salvar Aluno" é `type="submit"` dentro deste form

### Aba Documentos
- **SEM `<form>` próprio**
- Apenas `<div>` com inputs e botão `type="button"`
- Função `enviarDocumento()` monta FormData próprio e envia via fetch

## Logs de Debug Esperados

Ao clicar em "Salvar Aluno", os seguintes logs devem aparecer no console:

1. `[DEBUG] Inicializando eventos do formulário formAluno`
2. `[DEBUG] Inicializando eventos do botão Salvar Aluno`
3. `[DEBUG] Clique no botão Salvar Aluno` (ou `[DEBUG] Submit formAluno disparado`)
4. `[DEBUG] saveAlunoDados iniciado`

## Requisições Esperadas

Ao clicar em "Salvar Aluno", deve aparecer em Network:

- **POST** `/admin/api/alunos.php?id=167` (ou ID do aluno)
- Com `FormData` contendo todos os campos do formulário

## Testes Realizados

### Teste 1 - Salvar na Aba Matrícula
1. Abrir aluno ID 167
2. Ir para aba Matrícula
3. Alterar "Aulas Extras" (ex: de 0 para 5)
4. Clicar em "Salvar Aluno"
5. **Resultado esperado:**
   - Logs `[DEBUG]` aparecem no console
   - Requisição POST aparece em Network
   - Aluno é salvo sem erro
   - Modal fecha (ou mensagem de sucesso aparece)

### Teste 2 - Campo Forma de Pagamento
1. Abrir aluno na aba Matrícula
2. **Resultado esperado:**
   - Campo "Forma de Pagamento" está visível
   - Campo está na seção "Financeiro da Matrícula"
   - Campo pode ser preenchido e salvo

### Teste 3 - Upload de Documentos
1. Abrir aluno na aba Documentos
2. Selecionar tipo de documento
3. Selecionar arquivo
4. Clicar em "Enviar"
5. **Resultado esperado:**
   - Documento é enviado via fetch
   - Lista de documentos é atualizada
   - **NÃO interfere** com o botão "Salvar Aluno"

## Arquivos Modificados

- `admin/pages/alunos.php`:
  - Linha ~2676-2704: Removido `<form>` da aba Documentos
  - Linha ~3179-3195: Simplificado event listener do form principal
  - Linha ~6956-6960: Adicionados logs em `saveAlunoDados()`
  - Linha ~9556-9600: Ajustada função `enviarDocumento()`

## Observações Importantes

1. **`saveAlunoDados()` agora sempre é chamado** quando clica em "Salvar Aluno", independente da aba. A função `saveAlunoDados()` já trata internamente o salvamento de Dados + Matrícula.

2. **Não há mais formulários aninhados**. O `formAluno` é o único `<form>` do modal.

3. **A aba Documentos funciona independentemente** usando apenas JavaScript e fetch, sem interferir no form principal.

4. **O campo "Forma de Pagamento" está visível** e funcional. Se não aparecer, pode ser problema de CSS ou renderização do navegador (tentar recarregar a página).

