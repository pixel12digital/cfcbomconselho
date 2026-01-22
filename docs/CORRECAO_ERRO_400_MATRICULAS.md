# Correção - Erro 400 ao Salvar Matrícula

## Problema Identificado

**Erro:**
```
Failed to load resource: the server responded with a status of 400 (Bad Request)
Erro ao salvar matrícula: Error: Dados inválidos
```

**Causa Raiz:**
A função `saveAlunoMatricula()` estava enviando `FormData` para a API `matriculas.php`, mas a API espera receber dados em formato **JSON**.

A API na linha 102 faz:
```php
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    return;
}
```

Quando `FormData` é enviado, `php://input` não contém JSON válido, então `json_decode` retorna `null`, resultando no erro "Dados inválidos".

---

## Correção Aplicada

### 1. Conversão de FormData para JSON

**Antes:**
```javascript
const dadosFormData = new FormData();
dadosFormData.append('id', alunoIdHidden.value);
dadosFormData.append('renach', renach);
// ... mais campos

const response = await fetch(`api/matriculas.php?t=${timestamp}`, {
    method: 'POST',
    body: dadosFormData  // ❌ FormData
});
```

**Depois:**
```javascript
const dadosMatricula = {
    aluno_id: parseInt(alunoIdHidden.value),
    categoria_cnh: categoriaCnh,
    tipo_servico: tipoServico,
    data_inicio: dataMatricula,
    // ... mais campos
};

const response = await fetch(`api/matriculas.php?t=${timestamp}`, {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'  // ✅ Header correto
    },
    body: JSON.stringify(dadosMatricula)  // ✅ JSON
});
```

### 2. Extração de Campos Obrigatórios

A API exige os seguintes campos obrigatórios:
- `aluno_id`
- `categoria_cnh`
- `tipo_servico`
- `data_inicio`

**Correção:**
- Extrair `categoria_cnh` e `tipo_servico` da primeira operação coletada
- Mapear o tipo da operação para o formato esperado pela API
- Usar `data_matricula` do formulário como `data_inicio`
- Validar todos os campos obrigatórios antes de enviar

**Código:**
```javascript
// Extrair categoria_cnh e tipo_servico da primeira operação
let categoriaCnh = '';
let tipoServico = '';
if (operacoes && operacoes.length > 0) {
    const primeiraOperacao = operacoes[0];
    categoriaCnh = primeiraOperacao.categoria || formData.get('categoria_cnh') || '';
    // Mapear tipo da operação para tipo_servico da API
    const tipoOperacao = primeiraOperacao.tipo || '';
    if (tipoOperacao === 'primeira_habilitacao' || tipoOperacao === 'primeira') {
        tipoServico = 'primeira_habilitacao';
    } else if (tipoOperacao === 'adicao' || tipoOperacao === 'adicao_categoria') {
        tipoServico = 'adicao';
    } else if (tipoOperacao === 'mudanca' || tipoOperacao === 'mudanca_categoria') {
        tipoServico = 'mudanca';
    } else {
        tipoServico = tipoOperacao || formData.get('tipo_servico') || '';
    }
} else {
    // Fallback: tentar pegar do formulário
    categoriaCnh = formData.get('categoria_cnh') || '';
    tipoServico = formData.get('tipo_servico') || '';
}
```

### 3. Validação de Campos Obrigatórios

**Adicionada validação antes do envio:**
```javascript
const dataMatricula = formData.get('data_matricula') || '';
if (!categoriaCnh || !tipoServico || !dataMatricula) {
    const camposFaltando = [];
    if (!categoriaCnh) camposFaltando.push('Categoria CNH');
    if (!tipoServico) camposFaltando.push('Tipo de Serviço');
    if (!dataMatricula) camposFaltando.push('Data da Matrícula');
    
    alert('⚠️ Campos obrigatórios da matrícula não preenchidos:\n\n' +
          camposFaltando.map(c => `- ${c}`).join('\n') +
          '\n\nPor favor, preencha todos os campos obrigatórios.');
    btnSalvar.innerHTML = textoOriginal;
    btnSalvar.disabled = false;
    return { success: false, error: 'Campos obrigatórios não preenchidos' };
}
```

### 4. Estrutura de Dados Enviada

**Objeto JSON enviado:**
```javascript
{
    aluno_id: 123,                    // ✅ Obrigatório
    categoria_cnh: "B",               // ✅ Obrigatório
    tipo_servico: "primeira_habilitacao", // ✅ Obrigatório
    data_inicio: "2024-01-15",       // ✅ Obrigatório
    data_fim: "2024-06-15",          // Opcional
    status: "ativa",                  // Opcional (padrão: "ativa")
    valor_total: 1500.00,            // Opcional
    forma_pagamento: "dinheiro",      // Opcional
    observacoes: "Observações..."     // Opcional
}
```

---

## Arquivos Modificados

**`admin/pages/alunos.php`**
- Linha 7120-7143: Função `saveAlunoMatricula()` corrigida
  - Conversão de FormData para JSON
  - Extração de campos obrigatórios das operações
  - Validação antes do envio
  - Header `Content-Type: application/json` adicionado

---

## Testes Recomendados

### Teste 1: Salvar Matrícula com Todos os Campos
1. Preencher aba "Dados" do aluno
2. Preencher aba "Matrícula":
   - Adicionar operação (Categoria + Tipo)
   - Preencher Data da Matrícula
   - Preencher outros campos opcionais
3. Clicar em "Salvar Aluno"
4. **Resultado esperado**: Matrícula salva com sucesso

### Teste 2: Validação de Campos Obrigatórios
1. Preencher aba "Dados" do aluno
2. Preencher aba "Matrícula" **sem**:
   - Operação (Categoria/Tipo)
   - Data da Matrícula
3. Clicar em "Salvar Aluno"
4. **Resultado esperado**: Alerta informando campos faltando

### Teste 3: Verificar no Banco de Dados
1. Após salvar matrícula com sucesso
2. Verificar tabela `matriculas` no banco
3. **Resultado esperado**: Registro criado com todos os campos corretos

---

## Conclusão

O erro foi corrigido convertendo o envio de `FormData` para `JSON` e garantindo que todos os campos obrigatórios sejam extraídos corretamente das operações e validados antes do envio.

A API agora recebe os dados no formato esperado e pode processar a matrícula corretamente.

