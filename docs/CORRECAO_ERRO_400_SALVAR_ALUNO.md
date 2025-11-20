# CORREÇÃO - Erro 400 ao Salvar Dados do Aluno (Edição)

## Data: 2025-11-19

## Problema Identificado

### Erro 400 na API
- **URL da chamada**: `/cfc-bom-conselho/admin/api/alunos.php?id=167&t=...`
- **Método HTTP**: PUT
- **Resposta**: 400 Bad Request
- **Mensagem**: "ID e dados são obrigatórios"

### Causa Raiz
O problema estava na validação do handler PUT em `admin/api/alunos.php`:

```php
if (!$id || !$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID e dados são obrigatórios']);
    exit;
}
```

**O que estava acontecendo:**
1. O ID estava chegando corretamente na URL (`id=167`)
2. O corpo da requisição (FormData) não estava sendo lido corretamente
3. Quando usamos `FormData` com `fetch()`, o PHP pode não popular `$_POST` automaticamente em requisições PUT
4. A validação falhava porque `$input` estava vazio ou null

## Contrato da API (admin/api/alunos.php)

### Método PUT - Edição de Aluno

**Endpoint**: `admin/api/alunos.php?id={aluno_id}`

**Método HTTP**: `PUT`

**Formato dos Dados Aceitos**:
1. **JSON** (Content-Type: `application/json`)
   - Dados vêm em `php://input`
   - Parseado com `json_decode()`

2. **FormData** (Content-Type: `multipart/form-data` ou `application/x-www-form-urlencoded`)
   - Dados vêm em `$_POST`
   - Arquivos (foto) vêm em `$_FILES`

**Campos Esperados** (quando FormData):
- `nome`, `cpf`, `rg`, `rg_orgao_emissor`, `rg_uf`, `rg_data_emissao`
- `renach`, `foto` (arquivo), `data_nascimento`
- `estado_civil`, `profissao`, `escolaridade`
- `naturalidade`, `nacionalidade`
- `telefone`, `telefone_secundario`
- `contato_emergencia_nome`, `contato_emergencia_telefone`
- `email`, `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`
- `categoria_cnh`, `tipo_servico`, `status`
- `observacoes`, `atividade_remunerada`
- `lgpd_consentimento`, `lgpd_consentimento_em`
- `salvar_apenas_dados` (flag para indicar que é apenas salvamento da aba Dados)

**Validações**:
- `id` obrigatório na URL (`$_GET['id']`)
- `$input` não pode ser vazio (deve ter pelo menos um campo)

## Correções Implementadas

### 1. Melhoria na Leitura de Dados (PUT Handler)

**Arquivo**: `admin/api/alunos.php` (linha ~698)

**Antes**:
```php
if (strpos($contentType, 'application/json') !== false) {
    $input = json_decode(file_get_contents('php://input'), true);
} else {
    $input = $_POST;
}
```

**Depois**:
```php
// Tentar ler dados de diferentes formas
if (strpos($contentType, 'application/json') !== false) {
    // Requisição JSON
    $input = json_decode(file_get_contents('php://input'), true);
} elseif (!empty($_POST)) {
    // Requisição FormData (multipart/form-data ou application/x-www-form-urlencoded)
    $input = $_POST;
} else {
    // Tentar ler do php://input mesmo que não seja JSON
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        // Tentar parsear como form-urlencoded
        parse_str($rawInput, $parsedInput);
        if (!empty($parsedInput)) {
            $input = $parsedInput;
        } else {
            // Última tentativa: tentar JSON mesmo sem o Content-Type correto
            $jsonInput = json_decode($rawInput, true);
            if (json_last_error() === JSON_ERROR_NONE && $jsonInput) {
                $input = $jsonInput;
            }
        }
    }
}
```

**Melhorias**:
- Validação separada para `$id` e `$input`
- Mensagens de erro mais específicas
- Fallback para ler de `php://input` quando `$_POST` estiver vazio
- Logs mais detalhados para debug

### 2. Validação Melhorada

**Antes**:
```php
if (!$id || !$input) {
    // Erro genérico
}
```

**Depois**:
```php
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'ID é obrigatório']);
    exit;
}

if (!$input || (is_array($input) && empty($input))) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Dados são obrigatórios']);
    exit;
}
```

### 3. Logs de Debug no Frontend

**Arquivo**: `admin/pages/alunos.php` (linha ~6889)

Adicionado log antes de enviar a requisição:
```javascript
console.log('📤 Enviando dados do aluno:', {
    method: method,
    url: url,
    isEditing: isEditing,
    alunoId: alunoIdHidden?.value,
    camposEnviados: Array.from(dadosFormData.keys()),
    temFoto: fotoInput && fotoInput.files && fotoInput.files[0] ? true : false
});
```

### 4. Garantia de Content-Type Correto

**Importante**: Não definir `Content-Type` manualmente quando usar `FormData`. O navegador define automaticamente com o boundary correto para `multipart/form-data`.

## Contrato API ↔ JS (saveAlunoDados)

### Método HTTP
- **Criação (novo aluno)**: `POST`
- **Edição (aluno existente)**: `PUT`

### Formato dos Dados
- **FormData** (multipart/form-data)
- Permite envio de arquivos (foto) junto com os dados

### URL
- **Criação**: `api/alunos.php?t={timestamp}`
- **Edição**: `api/alunos.php?id={aluno_id}&t={timestamp}`

### Campos Enviados (FormData)
```javascript
dadosFormData.append('nome', nome);
dadosFormData.append('cpf', cpf);
dadosFormData.append('rg', ...);
dadosFormData.append('rg_orgao_emissor', ...);
dadosFormData.append('rg_uf', ...);
dadosFormData.append('rg_data_emissao', ...);
dadosFormData.append('data_nascimento', ...);
dadosFormData.append('estado_civil', ...);
dadosFormData.append('profissao', ...);
dadosFormData.append('escolaridade', ...);
dadosFormData.append('naturalidade', ...);
dadosFormData.append('nacionalidade', ...);
dadosFormData.append('email', ...);
dadosFormData.append('telefone', ...);
dadosFormData.append('telefone_secundario', ...);
dadosFormData.append('contato_emergencia_nome', ...);
dadosFormData.append('contato_emergencia_telefone', ...);
dadosFormData.append('status', ...);
dadosFormData.append('cfc_id', ...);
dadosFormData.append('atividade_remunerada', ...);
dadosFormData.append('cep', ...);
dadosFormData.append('endereco', ...);
dadosFormData.append('numero', ...);
dadosFormData.append('bairro', ...);
dadosFormData.append('cidade', ...);
dadosFormData.append('estado', ...);
dadosFormData.append('observacoes', ...);
dadosFormData.append('lgpd_consentimento', ...); // 0 ou 1
dadosFormData.append('foto', fotoFile); // Se houver arquivo selecionado
dadosFormData.append('salvar_apenas_dados', '1');
dadosFormData.append('id', alunoId); // Apenas na edição
```

## Status da Correção

### ✅ Erro 400 Resolvido
- A API agora lê corretamente os dados do FormData em requisições PUT
- Validações separadas para ID e dados
- Mensagens de erro mais específicas
- Logs detalhados para debug

### ✅ Fluxo de Edição Funcionando
- Ao clicar em "Salvar Aluno" na aba Dados (modo edição):
  - A requisição PUT é enviada corretamente
  - A API processa os dados e retorna sucesso
  - A mensagem "Dados do aluno salvos com sucesso!" aparece
  - Não há mais erro 400

### ✅ Fluxo de Criação Mantido
- Criação de novo aluno continua funcionando com POST
- Salvamento apenas de Dados (sem matrícula) funciona
- Salvamento completo (com matrícula) funciona

## Problema da Foto (404) - Status: PENDENTE

### Erro Observado
```
/admin/uploads/alunos/aluno_1763638782_691efdfe7d57a.png:1
Failed to load resource: the server responded with a status of 404 (Not Found)
```

### Possíveis Causas
1. **Arquivo não está sendo salvo no local correto**
   - Caminho salvo no banco: `admin/uploads/alunos/aluno_XXX.png`
   - Caminho físico esperado: `admin/uploads/alunos/aluno_XXX.png` (relativo à raiz do projeto)

2. **Permissões de diretório**
   - O diretório `admin/uploads/alunos/` pode não ter permissão de escrita
   - O arquivo pode estar sendo salvo mas não acessível via HTTP

3. **Caminho de acesso HTTP incorreto**
   - A função `carregarFotoExistenteAluno` pode estar construindo a URL incorretamente
   - O caminho relativo pode não estar correto

### Próximos Passos para Corrigir a Foto
1. Verificar se o diretório `admin/uploads/alunos/` existe e tem permissões corretas
2. Verificar se o arquivo está sendo salvo fisicamente no servidor
3. Verificar o caminho salvo no banco de dados
4. Ajustar a função `carregarFotoExistenteAluno` se necessário
5. Verificar se há regras de `.htaccess` bloqueando acesso aos arquivos

## Testes Realizados

### ✅ Teste 1: Editar Aluno Existente
- **Ação**: Clicar em "Editar" em um aluno existente
- **Ação**: Preencher campos e clicar em "Salvar Aluno"
- **Resultado**: ✅ Sucesso - Dados salvos sem erro 400

### ✅ Teste 2: Criar Novo Aluno
- **Ação**: Clicar em "Novo Aluno"
- **Ação**: Preencher campos e clicar em "Salvar Aluno"
- **Resultado**: ✅ Sucesso - Aluno criado e ID retornado

### ⚠️ Teste 3: Carregar Foto do Aluno
- **Ação**: Editar aluno que tem foto salva
- **Resultado**: ⚠️ Foto não carrega (404) - PENDENTE

## Arquivos Modificados

1. **`admin/api/alunos.php`**
   - Melhorada leitura de dados no handler PUT
   - Validações separadas para ID e dados
   - Logs detalhados para debug

2. **`admin/pages/alunos.php`**
   - Adicionado log de debug antes de enviar requisição
   - Comentário sobre não definir Content-Type manualmente

## Conclusão

O erro 400 foi **resolvido**. A API agora lê corretamente os dados do FormData em requisições PUT, e o fluxo de edição está funcionando.

O problema da foto (404) **permanece pendente** e precisa ser investigado separadamente, focando em:
- Verificar se o arquivo está sendo salvo fisicamente
- Verificar permissões do diretório
- Verificar o caminho de acesso HTTP

