# 📋 MAPEAMENTO: Foto, Telefone e E-mail do Instrutor

**Data:** 2025-01-27  
**Objetivo:** Mapear implementação existente no admin para reaproveitar no painel do instrutor

---

## 🎯 ETAPA 1: MAPEAMENTO DO QUE JÁ EXISTE

### 1.1. FOTO DO INSTRUTOR

#### **Arquivos Envolvidos:**

| Arquivo | Função |
|---------|--------|
| `admin/api/instrutores.php` | **Endpoint principal** - Processa upload e salva foto |
| `admin/pages/instrutores.php` | **Tela/Modal** - Interface de upload (linha ~285-302) |
| `admin/assets/js/instrutores-page.js` | **JavaScript** - Preview e validação (linhas ~11-114) |

#### **Função de Upload:**
```php
// admin/api/instrutores.php (linha 27)
function processarUploadFoto($arquivo, $instrutorId = null)
```

**Validações:**
- Tipos permitidos: `image/jpeg`, `image/jpg`, `image/png`, `image/gif`, `image/webp`
- Tamanho máximo: **2MB**
- Nome do arquivo: `instrutor_{id}_{timestamp}.{ext}`
- Diretório: `../../assets/uploads/instrutores/`
- Retorna: Caminho relativo `assets/uploads/instrutores/instrutor_123_1234567890.jpg`

#### **Tabela e Campo:**
- **Tabela:** `instrutores`
- **Campo:** `foto` (VARCHAR 255)
- **Valor:** Caminho relativo: `assets/uploads/instrutores/instrutor_123_1234567890.jpg`

#### **URL de Exibição:**
- **Base:** `assets/uploads/instrutores/{nome_arquivo}`
- **Exemplo completo:** `http://localhost/cfc-bom-conselho/assets/uploads/instrutores/instrutor_123_1234567890.jpg`

#### **Endpoint API:**
- **PUT** `/admin/api/instrutores.php?id={instrutor_id}`
- **Content-Type:** `multipart/form-data`
- **Campo:** `foto` (arquivo)
- **Processamento:** Linha 724-738

---

### 1.2. TELEFONE E E-MAIL

#### **Estrutura de Dados:**

**Tabela `usuarios`:**
- Campo `telefone` (VARCHAR 20) ✅
- Campo `email` (VARCHAR 100) ✅

**Tabela `instrutores`:**
- Campo `telefone` (VARCHAR 20) ✅
- Campo `email` (VARCHAR 100) ✅

#### **Mapeamento de Uso:**

| Tela | Origem do Dado | Campo no Banco |
|------|----------------|----------------|
| **Admin > Usuários** (editar usuário) | `usuarios.telefone`<br>`usuarios.email` | `usuarios.telefone`<br>`usuarios.email` |
| **Admin > Instrutores** (editar instrutor) | `instrutores.telefone`<br>`instrutores.email`<br>Fallback: `usuarios.email` | `instrutores.telefone`<br>`instrutores.email` |
| **Header do Instrutor** (dashboard) | `usuarios.email` (via sessão) | `usuarios.email` |
| **Instrutor > Perfil** (atual) | `usuarios.telefone`<br>`usuarios.email` | `usuarios.telefone`<br>`usuarios.email` |

#### **Lógica Atual no Admin:**

**admin/api/instrutores.php (linhas 707-708, 669-671):**
```php
// Atualiza AMBAS as tabelas quando edita instrutor
if (isset($data['email'])) $updateUserData['email'] = $data['email'];
if (isset($data['telefone'])) $updateUserData['telefone'] = $data['telefone'];
// ... depois atualiza usuarios ...
if (isset($data['email'])) $updateInstrutorData['email'] = $data['email'];
if (isset($data['telefone'])) $updateInstrutorData['telefone'] = $data['telefone'];
// ... depois atualiza instrutores ...
```

**Observação:** O admin já sincroniza ambos os campos ao editar instrutor!

---

### 1.3. EXIBIÇÃO NO HEADER DO INSTRUTOR

**Arquivo:** `instrutor/dashboard.php` (linha ~540-545)

**Código Atual:**
```php
<div class="instrutor-profile-avatar">
    <?php
    $iniciais = strtoupper(substr($instrutor['nome'], 0, 1));
    // ... gera iniciais ...
    echo $iniciais;
    ?>
</div>
```

**Status:** Atualmente exibe apenas **iniciais**, não foto.

---

## 📊 RESUMO DO MAPEAMENTO

### Foto
- ✅ **Upload:** `admin/api/instrutores.php::processarUploadFoto()`
- ✅ **Storage:** `assets/uploads/instrutores/`
- ✅ **Banco:** `instrutores.foto`
- ✅ **Validação:** JPG, PNG, GIF, WebP até 2MB
- ✅ **Endpoint:** PUT `/admin/api/instrutores.php?id={id}`

### Telefone
- ✅ **Tabela usuarios:** `usuarios.telefone`
- ✅ **Tabela instrutores:** `instrutores.telefone`
- ✅ **Admin sincroniza ambas** ao editar

### E-mail
- ✅ **Tabela usuarios:** `usuarios.email`
- ✅ **Tabela instrutores:** `instrutores.email`
- ✅ **Admin sincroniza ambas** ao editar
- ✅ **Fallback:** Se `instrutores.email` vazio, usa `usuarios.email`

---

## 🎯 PRÓXIMAS ETAPAS

1. **ETAPA 2:** Criar/ajustar tela "Meu Perfil" no painel do instrutor
2. **ETAPA 3:** Implementar sincronização automática (reaproveitar lógica do admin)
