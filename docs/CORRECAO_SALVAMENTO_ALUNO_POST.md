# CORREÇÃO - Salvamento (UPDATE) do Aluno via POST

## Data: 2025-11-19

## Problema Identificado

### Erro 500 na API
- **URL da chamada**: `/admin/api/alunos.php?id=167`
- **Método HTTP**: PUT
- **Resposta**: 500 Internal Server Error
- **Erro SQL**: `UPDATE alunos SET  WHERE id = :where_0`

### Causa Raiz
O problema estava na montagem do SQL de UPDATE:
1. O array `$alunoData` estava vazio (nenhum campo para atualizar)
2. O SQL era gerado como `UPDATE alunos SET  WHERE id = :where_0` (sem campos no SET)
3. Isso acontecia porque PUT + FormData não preenche `$_POST` corretamente no PHP

## Solução Implementada

### Simplificação do Contrato: POST para Create e Update

**Decisão**: Abolir o uso de PUT e usar POST tanto para criar quanto para editar.

**Razão**: PUT + FormData é problemático no PHP (não preenche `$_POST` e o `php://input` vem multipart bruto).

### Novo Contrato

#### Criação de Aluno
- **Método**: `POST`
- **URL**: `admin/api/alunos.php?t={timestamp}`
- **Sem ID na URL**
- **Backend interpreta como CREATE**

#### Edição de Aluno
- **Método**: `POST` (mesmo método)
- **URL**: `admin/api/alunos.php?id={aluno_id}&t={timestamp}`
- **Com ID na query string**
- **Backend interpreta como UPDATE**

**Em ambos os casos**: Continuamos usando FormData (multipart/form-data) para permitir upload de foto.

## Alterações Implementadas

### 1. Frontend (saveAlunoDados)

**Arquivo**: `admin/pages/alunos.php` (linha ~6882)

**Antes**:
```javascript
const method = isEditing ? 'PUT' : 'POST';
const url = isEditing && alunoIdHidden && alunoIdHidden.value
    ? `api/alunos.php?id=${alunoIdHidden.value}&t=${timestamp}`
    : `api/alunos.php?t=${timestamp}`;
```

**Depois**:
```javascript
// Sempre usar POST (tanto para criar quanto para editar)
const method = 'POST';
const alunoId = alunoIdHidden?.value;
const url = alunoId
    ? `api/alunos.php?id=${alunoId}&t=${timestamp}`
    : `api/alunos.php?t=${timestamp}`;
```

### 2. Backend (admin/api/alunos.php)

**Arquivo**: `admin/api/alunos.php` (linha ~400)

**Estrutura do handler POST**:
```php
case 'POST':
    // Determinar se é CREATE ou UPDATE baseado na presença de ID na query string
    $id = isset($_GET['id']) ? (int)$_GET['id'] : null;
    $isUpdate = $id !== null && $id > 0;
    
    if ($isUpdate) {
        // ========== FLUXO DE UPDATE ==========
        // Ler dados do FormData (POST)
        $data = $_POST;
        
        // Validar que há campos para atualizar
        if (empty($alunoData)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
            exit;
        }
        
        // Executar UPDATE
        // ...
    } else {
        // ========== FLUXO DE CREATE ==========
        // Criar novo aluno
        // ...
    }
```

**Melhorias no UPDATE**:
1. **Leitura direta de `$_POST`**: Como agora é POST, `$_POST` é preenchido automaticamente
2. **Validação de campos vazios**: Antes de executar UPDATE, verifica se há campos para atualizar
3. **Mensagem de erro amigável**: Se não houver campos, retorna erro 400 com mensagem clara
4. **Processamento de foto**: Upload de foto funciona corretamente com POST + FormData

### 3. Remoção do Handler PUT

**Arquivo**: `admin/api/alunos.php`

- Removido completamente o `case 'PUT'`
- Toda a lógica de UPDATE foi movida para dentro do `case 'POST'`

## Contrato Final API ↔ JS

### Método HTTP
- **Criação**: `POST`
- **Edição**: `POST` (mesmo método)

### Formato dos Dados
- **FormData** (multipart/form-data)
- Permite envio de arquivos (foto) junto com os dados
- Não definir `Content-Type` manualmente (navegador define automaticamente)

### URL
- **Criação**: `api/alunos.php?t={timestamp}`
- **Edição**: `api/alunos.php?id={aluno_id}&t={timestamp}`

### Campos Enviados (FormData)
Todos os campos da aba Dados são enviados:
- Dados pessoais: `nome`, `cpf`, `rg`, `data_nascimento`, etc.
- Endereço: `endereco`, `numero`, `bairro`, `cidade`, `estado`, `cep`
- Contatos: `telefone`, `telefone_secundario`, `email`
- LGPD: `lgpd_consentimento` (0 ou 1)
- Outros: `observacoes`, `atividade_remunerada`, `foto` (arquivo), `salvar_apenas_dados`

## Validações Implementadas

### 1. Validação de Campos Vazios
```php
if (empty($alunoData)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Nenhum campo para atualizar']);
    exit;
}
```

**Garantia**: O SQL de UPDATE sempre terá pelo menos um campo no SET, ou retornará erro 400 amigável.

### 2. Validação de Aluno Existente
```php
$alunoExistente = $db->findWhere('alunos', 'id = ?', [$id], '*', null, 1);
if (!$alunoExistente) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Aluno não encontrado']);
    exit;
}
```

### 3. Validação de CPF Duplicado
```php
if (isset($alunoData['cpf'])) {
    $cpfExistente = $db->findWhere('alunos', 'cpf = ? AND id != ?', [$alunoData['cpf'], $id], '*', null, 1);
    if ($cpfExistente) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => 'CPF já cadastrado']);
        exit;
    }
}
```

## Status da Correção

### ✅ Erro 500 Resolvido
- O SQL de UPDATE agora sempre monta um SET válido
- Se não houver campos para atualizar, retorna erro 400 amigável
- Não há mais erro "UPDATE alunos SET  WHERE id = :where_0"

### ✅ Fluxo de Edição Funcionando
- Ao clicar em "Salvar Aluno" na aba Dados (modo edição):
  - A requisição POST é enviada corretamente
  - A API processa os dados e retorna sucesso
  - A mensagem "Dados do aluno salvos com sucesso!" aparece
  - Não há mais erro 500

### ✅ Fluxo de Criação Mantido
- Criação de novo aluno continua funcionando
- Salvamento apenas de Dados funciona
- Salvamento completo funciona

## Teste Realizado

### ✅ Teste: Editar Aluno Existente
1. **Ação**: Clicar em "Editar" em um aluno existente
2. **Ação**: Alterar campo "Observações"
3. **Ação**: Clicar em "Salvar Aluno"
4. **Resultado**: 
   - ✅ Resposta 200 OK da API
   - ✅ Mensagem "Dados do aluno salvos com sucesso!" exibida
   - ✅ Ao reabrir o aluno, o campo "Observações" está atualizado

## Problema da Foto (404) - Status: PENDENTE

### Erro Observado
```
/admin/uploads/alunos/aluno_1763638782_691efdfe7d57a.png:1
Failed to load resource: the server responded with a status of 404 (Not Found)
```

### Análise
O caminho salvo no banco é: `admin/uploads/alunos/aluno_XXX.png`

O arquivo físico está sendo salvo em: `../uploads/alunos/` (relativo ao arquivo PHP)

**Possíveis causas**:
1. O arquivo não está sendo salvo no local correto
2. Permissões do diretório `admin/uploads/alunos/`
3. Caminho de acesso HTTP incorreto na função `carregarFotoExistenteAluno`

### Próximos Passos para Corrigir a Foto
1. Verificar se o diretório `admin/uploads/alunos/` existe e tem permissões corretas
2. Verificar se o arquivo está sendo salvo fisicamente no servidor
3. Verificar o caminho salvo no banco de dados
4. Ajustar a função `carregarFotoExistenteAluno` se necessário
5. Verificar se há regras de `.htaccess` bloqueando acesso aos arquivos

## Arquivos Modificados

1. **`admin/pages/alunos.php`**
   - Função `saveAlunoDados` agora sempre usa POST
   - URL ajustada para incluir ID na query string quando editar

2. **`admin/api/alunos.php`**
   - Handler POST agora detecta CREATE vs UPDATE baseado em ID na query string
   - Lógica de UPDATE movida para dentro do case 'POST'
   - Validação de campos vazios antes de executar UPDATE
   - Removido completamente o case 'PUT'

## Conclusão

O erro 500 foi **resolvido**. O SQL de UPDATE agora sempre monta um SET válido, ou retorna erro 400 amigável se não houver campos para atualizar.

O fluxo de edição está funcionando corretamente:
- ✅ Resposta 200 OK da API
- ✅ Mensagem de sucesso exibida
- ✅ Dados atualizados corretamente no banco

O problema da foto (404) **permanece pendente** e precisa ser investigado separadamente.

