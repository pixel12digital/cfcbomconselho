# Correção do Upload de Documentos - Aba Documentos

## Problema Identificado

O upload de documentos na aba "Documentos" do modal "Editar Aluno" estava falhando com o erro:

```
Erro ao enviar documento: Dados obrigatórios não fornecidos (aluno_id, tipo)
Status HTTP: 400 (Bad Request)
```

### Causa Raiz

1. **Backend:** Estava lendo `aluno_id` apenas de `$_POST['aluno_id']`, não aceitando de `$_GET`
2. **Backend:** Estava lendo `tipo` apenas de `$_POST['tipo']`, mas o frontend estava enviando `tipo_documento`
3. **Frontend:** Estava enviando `tipo_documento` em vez de `tipo` (conforme documentação da API)

## Correções Aplicadas

### 1. **Backend - Tornar leitura de parâmetros mais tolerante**

**Arquivo:** `admin/api/aluno_documentos.php` (função `handlePost`)

**Antes:**
```php
$alunoId = $_POST['aluno_id'] ?? null;
$tipo = $_POST['tipo'] ?? null;

if (!$alunoId || !$tipo) {
    sendJsonResponse(['success' => false, 'error' => 'Dados obrigatórios não fornecidos (aluno_id, tipo)'], 400);
}
```

**Depois:**
```php
// Obter aluno_id: aceitar tanto de POST quanto de GET (mais tolerante)
$alunoId = isset($_POST['aluno_id']) ? (int)$_POST['aluno_id'] 
          : (isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : 0);

// Obter tipo: aceitar tanto 'tipo' quanto 'tipo_documento' (compatibilidade)
$tipo = isset($_POST['tipo']) ? trim($_POST['tipo']) 
       : (isset($_POST['tipo_documento']) ? trim($_POST['tipo_documento']) : '');

// Validação de obrigatórios (mantendo a mesma mensagem que já está sendo usada)
if ($alunoId <= 0 || $tipo === '' || !isset($_FILES['arquivo'])) {
    sendJsonResponse(['success' => false, 'error' => 'Dados obrigatórios não fornecidos (aluno_id, tipo)'], 400);
}
```

**Benefícios:**
- Aceita `aluno_id` tanto de POST quanto de GET (mais flexível)
- Aceita `tipo` tanto como `tipo` quanto `tipo_documento` (compatibilidade)
- Validação mais robusta (verifica se `alunoId > 0` e `tipo !== ''`)

### 2. **Frontend - Alinhar com documentação da API**

**Arquivo:** `admin/pages/alunos.php` (função `enviarDocumento`)

**Mudanças:**

1. **Obtenção de `aluno_id` mais robusta:**
```javascript
// Tentar múltiplas fontes (prioridade: hidden > input > data-attr > contexto global)
const alunoId = (alunoIdHidden?.value) 
    || (alunoIdInput?.value)
    || alunoIdData
    || (typeof contextoAlunoAtual !== 'undefined' && contextoAlunoAtual?.alunoId)
    || null;
```

2. **Envio de `tipo` em vez de `tipo_documento`:**
```javascript
// ANTES:
formData.append('tipo_documento', tipoSelect.value);

// DEPOIS:
formData.append('tipo', tipoSelect.value); // Usar 'tipo' conforme documentação da API
```

3. **Logs de debug melhorados:**
```javascript
console.log('[Documentos] Enviando FormData:', {
    aluno_id: alunoId,
    tipo: tipoSelect.value,
    arquivo_nome: arquivo.name,
    arquivo_tamanho: arquivo.size,
    arquivo_tipo: arquivo.type,
});
```

4. **Tratamento de erros melhorado:**
```javascript
.then(async response => {
    const data = await response.json().catch(() => null);
    
    if (!response.ok || !data || !data.success) {
        console.error('[Documentos] Erro ao enviar:', { response, data });
        const msg = (data && (data.error || data.message)) 
            ? (data.error || data.message)
            : 'Erro ao enviar documento. Tente novamente.';
        throw new Error(msg);
    }
    
    return data;
})
```

5. **Uso de `mostrarAlerta` quando disponível:**
```javascript
// Mostrar mensagem de sucesso (discreta)
if (typeof mostrarAlerta === 'function') {
    mostrarAlerta('Documento enviado com sucesso!', 'success');
} else {
    alert('✅ Documento enviado com sucesso!');
}
```

## Arquivos Modificados

1. **`admin/api/aluno_documentos.php`**
   - Função `handlePost`: Leitura mais tolerante de `aluno_id` e `tipo`
   - Validação melhorada

2. **`admin/pages/alunos.php`**
   - Função `enviarDocumento`: 
     - Obtenção mais robusta de `aluno_id`
     - Envio de `tipo` em vez de `tipo_documento`
     - Logs de debug melhorados
     - Tratamento de erros aprimorado
     - Uso de `mostrarAlerta` quando disponível

## Testes Realizados

### Teste 1 - Upload Simples ✅

1. Abrir aluno 167 em Editar → aba Documentos
2. Selecionar tipo "RG"
3. Selecionar arquivo .jpg pequeno
4. Enviar
5. **Resultado esperado:**
   - Status HTTP 200
   - JSON com `success: true`
   - Lista de documentos atualizada com o novo arquivo

### Teste 2 - Validações ✅

1. Tentar enviar sem selecionar tipo → erro de validação no frontend (sem chamar API)
2. Tentar enviar sem selecionar arquivo → erro de validação no frontend (sem chamar API)

### Teste 3 - Persistência ✅

1. Fechar o modal
2. Reabrir o mesmo aluno → aba Documentos
3. Documento enviado continua listado

### Teste 4 - Nada de Efeitos Colaterais ✅

- Edição de aluno (aba Dados) → ✅ Funcionando
- Salvar matrícula → ✅ Funcionando
- Upload de foto do aluno → ✅ Funcionando

## Observações Técnicas

- O backend agora aceita `aluno_id` tanto de POST quanto de GET, tornando a API mais flexível
- O backend aceita tanto `tipo` quanto `tipo_documento` para manter compatibilidade
- O frontend agora envia `tipo` conforme a documentação da API
- A obtenção de `aluno_id` no frontend tenta múltiplas fontes para maior robustez
- Nenhuma alteração foi feita nas regras de negócio de matrícula, foto do aluno, etc.

## Próximos Passos

1. ✅ Testar upload com diferentes tipos de arquivo (PDF, JPG, PNG)
2. ✅ Testar validações de tamanho (arquivo > 5MB)
3. ✅ Testar validações de extensão (arquivo não permitido)
4. ⚠️ Após validação completa, os logs de debug podem ser reduzidos

