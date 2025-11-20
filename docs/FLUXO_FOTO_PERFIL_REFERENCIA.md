# Fluxo de Upload de Foto de Perfil - Referência para Documentos

## 1. Frontend - HTML (Aba Dados)

**Localização:** `admin/pages/alunos.php` (linha ~1895-1915)

```html
<input type="file" class="form-control" id="foto" name="foto" accept="image/*" 
       onchange="previewFotoAluno(this)">
<div id="preview-container-aluno">
  <img id="foto-preview-aluno" src="" alt="Preview da foto">
  <button onclick="removerFotoAluno()">Remover</button>
</div>
```

## 2. Frontend - JavaScript

**Localização:** `admin/pages/alunos.php` (linha ~6992-6996)

### 2.1. Inclusão no FormData

```javascript
const dadosFormData = new FormData();
// ... outros campos ...
const fotoInput = document.getElementById('foto');
if (fotoInput && fotoInput.files && fotoInput.files[0]) {
    dadosFormData.append('foto', fotoInput.files[0]);
}
```

### 2.2. Envio para API

**Endpoint:** `admin/api/alunos.php` (POST/PUT)
- Método: POST (criar) ou PUT (editar)
- Content-Type: `multipart/form-data` (automático com FormData)
- Body: FormData contendo todos os campos do aluno + arquivo foto

## 3. Backend - Processamento

**Localização:** `admin/api/alunos.php` (linha ~280-360)

### 3.1. Verificação de Upload

```php
$temFotoNova = isset($_FILES['foto']) && 
               $_FILES['foto']['error'] === UPLOAD_ERR_OK && 
               $_FILES['foto']['size'] > 0;
```

### 3.2. Validações

- **Extensões permitidas:** `jpg, jpeg, png, gif, webp`
- **Tamanho máximo:** 2MB
- **Diretório:** `admin/uploads/alunos/`

### 3.3. Salvamento

```php
$uploadDir = __DIR__ . '/../uploads/alunos/';
$nomeArquivo = 'aluno_' . time() . '_' . uniqid() . '.' . $extension;
$caminhoCompleto = $uploadDir . $nomeArquivo;
move_uploaded_file($_FILES['foto']['tmp_name'], $caminhoCompleto);
$caminhoFoto = 'admin/uploads/alunos/' . $nomeArquivo; // Caminho relativo para BD
```

### 3.4. Persistência no Banco

**Tabela:** `alunos`
**Coluna:** `foto` (VARCHAR)
**Valor:** Caminho relativo: `admin/uploads/alunos/aluno_1234567890_abc123.jpg`

### 3.5. Remoção de Foto Antiga

```php
// Se há foto antiga e nova foto foi enviada, remover a antiga
if ($alunoExistente && $alunoExistente['foto'] && $temFotoNova) {
    $caminhoAntigo = __DIR__ . '/../' . $alunoExistente['foto'];
    if (file_exists($caminhoAntigo)) {
        unlink($caminhoAntigo);
    }
}
```

## 4. Exibição em Detalhes

**Localização:** `admin/pages/alunos.php` (função `preencherModalVisualizacao`)

```javascript
const fotoUrl = aluno.foto 
    ? `${window.location.origin}${projectPath}/${aluno.foto}` 
    : '';
```

## Resumo do Fluxo

1. **Usuário seleciona arquivo** → `onchange="previewFotoAluno(this)"` mostra preview
2. **Usuário clica em Salvar** → `saveAlunoDados()` monta FormData incluindo `foto`
3. **FormData enviado** → POST/PUT para `admin/api/alunos.php`
4. **Backend valida** → Extensão, tamanho, permissões
5. **Backend salva arquivo** → `admin/uploads/alunos/aluno_TIMESTAMP_UNIQID.ext`
6. **Backend salva caminho** → Coluna `foto` na tabela `alunos`
7. **Frontend atualiza** → Preview e modal de detalhes mostram a foto

## Padrão para Documentos

- **Diretório:** `admin/uploads/alunos_documentos/{aluno_id}/`
- **Nome arquivo:** `{tipo}_{timestamp}_{uniqid}.{ext}`
- **Tabela:** `alunos_documentos`
- **API:** `admin/api/aluno_documentos.php`
- **FormData:** Apenas `arquivo` + `tipo` (não junto com dados do aluno)

