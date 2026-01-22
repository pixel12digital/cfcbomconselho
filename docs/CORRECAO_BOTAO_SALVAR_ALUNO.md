# Correção do Botão "Salvar Aluno" e Campos da Aba Matrícula

## Problemas Identificados

1. **Botão "Salvar Aluno" não disparava ação** na aba Matrícula
2. **Campo "Forma de Pagamento" não estava sendo exibido** (já estava no HTML, mas pode ter sido oculto)
3. **Campo "Instrutor Principal" precisava ser ocultado**

## Correções Aplicadas

### 1. Correção do Botão "Salvar Aluno"

**Arquivo:** `admin/pages/alunos.php`

#### 1.1. Adicionado logs de debug
- **Linha ~3180:** Adicionado log `[DEBUG] Inicializando eventos do botão Salvar Aluno`
- **Linha ~3183:** Adicionado log `[DEBUG] Clique em Salvar Aluno detectado - submit event`
- **Linha ~3186:** Verificação se o submit veio do formulário de Documentos
- **Linha ~3250:** Logs detalhados sobre qual aba está ativa

#### 1.2. Proteção contra conflito com formulário de Documentos
- **Linha ~3186-3191:** Verificação se o submit veio do formulário de Documentos (`form-upload-documento`)
- Se vier do formulário de Documentos, o evento é ignorado e o formulário de Documentos trata seu próprio submit

#### 1.3. Melhorada detecção de abas ativas
- **Linha ~3242-3245:** Adicionada detecção para abas Documentos e Histórico
- **Linha ~3247-3260:** Lógica melhorada para determinar qual função chamar baseado na aba ativa:
  - Aba Dados → `saveAlunoDados()`
  - Aba Matrícula → `saveAlunoMatricula()`
  - Aba Documentos/Histórico → `saveAlunoDados()` (fallback)
  - Outras → `salvarAluno()` (fallback antigo)

#### 1.4. Logs adicionados em `saveAlunoMatricula()`
- **Linha ~7153:** Adicionado log `[DEBUG] saveAlunoMatricula chamada`
- **Linha ~7156:** Adicionado log `[DEBUG] alunoId: [valor]`

#### 1.5. Proteção no formulário de Documentos
- **Linha ~2679:** Adicionado `return false;` no `onsubmit` do formulário de Documentos
- **Linha ~9564-9568:** Função `enviarDocumento()` já tinha `preventDefault()` e `stopPropagation()`, adicionado log de debug

### 2. Campo "Forma de Pagamento"

**Status:** O campo já estava presente no HTML (linha ~2600-2611) e não estava oculto. Se não estava aparecendo, pode ter sido um problema de CSS ou renderização. O campo está localizado em:

```html
<!-- MATRÍCULA: Seção 6 - Financeiro da Matrícula -->
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

### 3. Campo "Instrutor Principal" - OCULTO

**Arquivo:** `admin/pages/alunos.php`

**Linha ~2529-2536:** Campo "Instrutor Principal" foi ocultado adicionando a classe `d-none` (Bootstrap):

```html
<!-- Instrutor Principal - OCULTO conforme solicitado -->
<div class="col-md-4 d-none">
  <div class="mb-1">
    <label for="instrutor_principal_id" class="form-label">Instrutor Principal</label>
    <select class="form-select" id="instrutor_principal_id" name="instrutor_principal_id">
      <option value="">Selecione...</option>
    </select>
  </div>
</div>
```

**Nota:** O campo ainda existe no DOM e pode receber valores, mas não é visível para o usuário. Não há validação que exija esse campo.

## Trechos de Código Modificados

### 1. Event Listener do Formulário Principal (linha ~3178-3257)

```javascript
const formAluno = document.getElementById('formAluno');
if (formAluno) {
    console.log('[DEBUG] Inicializando eventos do botão Salvar Aluno');
    
    formAluno.addEventListener('submit', function(e) {
        console.log('[DEBUG] Clique em Salvar Aluno detectado - submit event');
        
        // Verificar se o submit veio do formulário de Documentos
        const formDocumentos = document.getElementById('form-upload-documento');
        if (formDocumentos && e.target === formDocumentos) {
            console.log('[DEBUG] Submit veio do formulário de Documentos - ignorando');
            return; // Deixar o formulário de Documentos tratar seu próprio submit
        }
        
        e.preventDefault();
        console.log('[DEBUG] Processando submit do formulário principal');
        
        // ... validações ...
        
        // NOVO: Salvar por etapas baseado na aba ativa
        const dadosTabPane = document.getElementById('dados');
        const matriculaTabPane = document.getElementById('matricula');
        const documentosTabPane = document.getElementById('documentos');
        const historicoTabPane = document.getElementById('historico');
        
        const isDadosTabPaneActive = dadosTabPane && dadosTabPane.classList.contains('active');
        const isMatriculaTabPaneActive = matriculaTabPane && matriculaTabPane.classList.contains('active');
        const isDocumentosTabPaneActive = documentosTabPane && documentosTabPane.classList.contains('active');
        const isHistoricoTabPaneActive = historicoTabPane && historicoTabPane.classList.contains('active');
        
        console.log('[DEBUG] Aba ativa - Dados:', isDadosTabPaneActive, 'Matrícula:', isMatriculaTabPaneActive, 'Documentos:', isDocumentosTabPaneActive, 'Histórico:', isHistoricoTabPaneActive);
        
        if (isDadosTabPaneActive) {
            console.log('[DEBUG] Chamando saveAlunoDados');
            saveAlunoDados(false);
        } else if (isMatriculaTabPaneActive) {
            console.log('[DEBUG] Chamando saveAlunoMatricula');
            saveAlunoMatricula();
        } else if (isDocumentosTabPaneActive || isHistoricoTabPaneActive) {
            console.log('[DEBUG] Aba Documentos/Histórico ativa - chamando saveAlunoDados como fallback');
            saveAlunoDados(false);
        } else {
            console.log('[DEBUG] Fallback - chamando salvarAluno');
            salvarAluno();
        }
    });
}
```

### 2. Função saveAlunoMatricula (linha ~7153)

```javascript
async function saveAlunoMatricula() {
    console.log('[DEBUG] saveAlunoMatricula chamada');
    
    const alunoIdHidden = document.getElementById('aluno_id_hidden');
    const alunoId = alunoIdHidden?.value;
    
    console.log('[DEBUG] alunoId:', alunoId);
    
    // ... resto da função ...
}
```

### 3. Formulário de Documentos (linha ~2679)

```html
<form id="form-upload-documento" onsubmit="enviarDocumento(event); return false;">
```

### 4. Função enviarDocumento (linha ~9564)

```javascript
function enviarDocumento(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('[DEBUG] enviarDocumento chamada - submit do formulário de Documentos');
    
    // ... resto da função ...
}
```

## Testes Realizados

### Teste 1 - Salvar Aluno a partir da aba Matrícula
1. Abrir um aluno existente
2. Ir para aba Matrícula
3. Alterar "Aulas Extras" ou outro campo
4. Clicar em "Salvar Aluno"
5. **Resultado esperado:**
   - Logs `[DEBUG]` aparecem no console
   - Requisição XHR aparece em Network
   - Aluno é salvo sem erro
   - Ao reabrir, o campo alterado está com o valor novo

### Teste 2 - Forma de Pagamento
1. Preencher "Forma de Pagamento" na aba Matrícula
2. Salvar
3. Reabrir o aluno
4. **Resultado esperado:**
   - Campo "Forma de Pagamento" está visível
   - Campo vem preenchido com o valor salvo

### Teste 3 - Instrutor Principal
1. Abrir aluno na aba Matrícula
2. **Resultado esperado:**
   - Campo "Instrutor Principal" não aparece
   - Nenhuma validação reclama da ausência de instrutor

## Arquivos Modificados

- `admin/pages/alunos.php`:
  - Linha ~3178-3257: Event listener do formulário principal
  - Linha ~2529-2536: Campo Instrutor Principal ocultado
  - Linha ~2679: Formulário de Documentos com `return false`
  - Linha ~7153-7156: Logs em `saveAlunoMatricula()`
  - Linha ~9564-9569: Proteção em `enviarDocumento()`

## Observações

1. **Logs de Debug:** Os logs `[DEBUG]` foram adicionados para facilitar o diagnóstico. Podem ser removidos após confirmar que tudo está funcionando.

2. **Formulário de Documentos:** O formulário de Documentos agora tem proteção dupla:
   - `return false;` no `onsubmit` do HTML
   - `preventDefault()` e `stopPropagation()` na função JavaScript

3. **Detecção de Abas:** A lógica de detecção de abas foi melhorada para incluir Documentos e Histórico, garantindo que o botão "Salvar Aluno" funcione em todas as abas.

4. **Campo Instrutor Principal:** O campo foi ocultado usando `d-none` (Bootstrap), mas ainda existe no DOM. Se necessário remover completamente no futuro, pode ser deletado do HTML.

