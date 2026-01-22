# ✅ IMPLEMENTAÇÃO: Foto de Perfil do Instrutor

**Data:** 2025-01-27  
**Status:** ✅ Concluído  
**Escopo:** Foto, telefone e e-mail do instrutor no painel do instrutor, com sincronização automática

---

## 📋 RESUMO

Implementação de foto de perfil, telefone e e-mail do instrutor no painel do instrutor, **reaproveitando** a lógica existente do admin. O instrutor pode editar apenas seu próprio perfil, e as alterações são sincronizadas automaticamente entre as tabelas `usuarios` e `instrutores`.

---

## 📁 ARQUIVOS CRIADOS/MODIFICADOS

### Novos Arquivos

| Arquivo | Descrição |
|---------|-----------|
| `instrutor/api/perfil.php` | **Endpoint API** para o instrutor atualizar seu próprio perfil (foto, telefone, e-mail) |
| `docs/MAPEAMENTO_FOTO_TELEFONE_EMAIL_INSTRUTOR.md` | Documento de mapeamento do que já existe no admin |

### Arquivos Modificados

| Arquivo | Alterações |
|---------|------------|
| `instrutor/perfil.php` | ✅ Adicionado campo de upload de foto<br>✅ Atualizado para usar API (AJAX)<br>✅ Prioriza dados da tabela `instrutores` |
| `instrutor/dashboard.php` | ✅ Header atualizado para exibir foto do instrutor (com fallback para iniciais) |

---

## 🔄 SINCRONIZAÇÃO DE DADOS

### Campos Sincronizados

Quando o instrutor salva foto/telefone/e-mail, **ambas as tabelas são atualizadas**:

| Campo | Tabela `usuarios` | Tabela `instrutores` |
|-------|-------------------|----------------------|
| **Foto** | ❌ Não armazenada | ✅ `instrutores.foto` |
| **Telefone** | ✅ `usuarios.telefone` | ✅ `instrutores.telefone` |
| **E-mail** | ✅ `usuarios.email` | ✅ `instrutores.email` |

### Lógica de Prioridade (Exibição)

**Telefone e E-mail:**
- **Fonte primária:** `instrutores.telefone` / `instrutores.email`
- **Fallback:** `usuarios.telefone` / `usuarios.email`

**Foto:**
- **Fonte:** `instrutores.foto` (única fonte)
- **Fallback:** Iniciais geradas do nome

---

## 🔒 SEGURANÇA

### Validações Implementadas

1. **Autenticação:**
   - ✅ Verifica sessão ativa
   - ✅ Verifica se é instrutor (`tipo === 'instrutor'`)

2. **Autorização:**
   - ✅ Instrutor **só pode editar o próprio perfil**
   - ✅ ID do instrutor obtido via `getCurrentInstrutorId()` (sessão)
   - ✅ **NUNCA aceita ID arbitrário via GET/POST**

3. **Validação de Dados:**
   - ✅ E-mail: Valida formato e verifica duplicidade
   - ✅ Foto: Valida tipo (JPG, PNG, GIF, WebP) e tamanho (2MB máximo)

---

## 📊 ESTRUTURA DO ENDPOINT

### `instrutor/api/perfil.php`

**GET:** Buscar dados do perfil
```json
{
  "success": true,
  "perfil": {
    "id": 123,
    "nome": "Carlos da Silva",
    "email": "carlos@email.com",
    "telefone": "87999999999",
    "foto": "assets/uploads/instrutores/instrutor_123_1234567890.jpg",
    "credencial": "INS001"
  }
}
```

**PUT:** Atualizar perfil
- **Content-Type:** `multipart/form-data` (para upload de foto)
- **Campos:** `email`, `telefone`, `foto` (arquivo)
- **Resposta:** Mesma estrutura do GET

---

## 🎨 INTERFACE

### Página de Perfil (`instrutor/perfil.php`)

**Campos:**
- ✅ **Foto:** Upload com preview circular (120x120px)
- ✅ **Nome:** Campo de texto (somente leitura - não editável pelo instrutor)
- ✅ **E-mail:** Campo de texto editável
- ✅ **Telefone:** Campo de texto editável
- ✅ **CPF, CFC, Tipo:** Campos somente leitura

**Funcionalidades:**
- ✅ Preview da foto ao selecionar arquivo
- ✅ Validação de tipo e tamanho no frontend
- ✅ Salvamento via AJAX (sem recarregar página)
- ✅ Mensagens de sucesso/erro

### Header do Dashboard (`instrutor/dashboard.php`)

**Avatar:**
- ✅ Exibe foto se existir (`instrutores.foto`)
- ✅ Fallback para iniciais se não houver foto
- ✅ Tamanho: 36x36px, circular

---

## 🔧 REAPROVEITAMENTO DO ADMIN

### Funções Reutilizadas

| Função | Arquivo | Uso |
|--------|---------|-----|
| `processarUploadFoto()` | `admin/api/instrutores.php` | Upload e validação de foto |
| `removerFotoAntiga()` | `admin/api/instrutores.php` | Remoção de foto antiga ao atualizar |

### Estrutura de Armazenamento

- **Diretório:** `assets/uploads/instrutores/`
- **Padrão de nome:** `instrutor_{id}_{timestamp}.{ext}`
- **Caminho no BD:** `assets/uploads/instrutores/instrutor_123_1234567890.jpg`

---

## ✅ CHECKLIST DE TESTE MANUAL

### Cenário 1: Admin cadastra foto/telefone/e-mail

- [ ] Admin acessa **Admin > Instrutores > Editar Instrutor**
- [ ] Admin faz upload de foto
- [ ] Admin preenche telefone e e-mail
- [ ] Admin salva
- [ ] **Verificar:** Instrutor vê foto/telefone/e-mail no app (`instrutor/perfil.php`)
- [ ] **Verificar:** Header do dashboard exibe foto

### Cenário 2: Instrutor altera foto/telefone/e-mail

- [ ] Instrutor acessa **Meu Perfil** (`instrutor/perfil.php`)
- [ ] Instrutor faz upload de nova foto
- [ ] Instrutor altera telefone e e-mail
- [ ] Instrutor salva
- [ ] **Verificar:** Admin vê atualizado em **Admin > Usuários** (editar usuário)
- [ ] **Verificar:** Admin vê atualizado em **Admin > Instrutores** (editar instrutor)
- [ ] **Verificar:** Header do instrutor atualiza após salvar (sem recarregar página manualmente)

### Cenário 3: Validações

- [ ] Tentar upload de arquivo não-imagem → Erro exibido
- [ ] Tentar upload de arquivo > 2MB → Erro exibido
- [ ] Tentar usar e-mail já existente → Erro exibido
- [ ] Tentar editar perfil de outro instrutor → Erro 403

### Cenário 4: Fallbacks

- [ ] Instrutor sem foto → Exibe iniciais no header
- [ ] Foto quebrada (404) → Fallback para iniciais
- [ ] Telefone/e-mail vazios em `instrutores` → Usa dados de `usuarios`

### Cenário 5: Sincronização

- [ ] Instrutor altera telefone → Atualiza `usuarios.telefone` E `instrutores.telefone`
- [ ] Instrutor altera e-mail → Atualiza `usuarios.email` E `instrutores.email`
- [ ] Admin altera telefone/e-mail → Instrutor vê atualizado no app

---

## 📝 NOTAS IMPORTANTES

1. **Canonicidade:** Foto, telefone e e-mail editados pelo instrutor são a **fonte de verdade operacional**
2. **Não duplicação:** Reaproveita funções e estrutura do admin, não cria sistema novo
3. **Segurança:** Instrutor só pode editar próprio perfil (validação via sessão)
4. **Sincronização:** Atualiza ambas as tabelas automaticamente para evitar divergência

---

## 🚀 PRÓXIMOS PASSOS (Opcional)

1. **Testes manuais** - Validar todos os cenários do checklist
2. **Ajustes de UX** - Se necessário após feedback
3. **Cache de foto** - Se necessário para performance

---

**Implementação concluída em:** 2025-01-27  
**Versão:** 1.0
