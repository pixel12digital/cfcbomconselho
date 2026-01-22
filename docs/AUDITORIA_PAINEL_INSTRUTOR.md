# Auditoria: Painel do Instrutor - Estrutura Atual

## Data: 2024
## Status: ✅ CONCLUÍDA

---

## Resumo Executivo

Esta auditoria mapeia a estrutura atual do painel do instrutor para implementar melhorias pontuais sem grandes refatorações.

---

## 1. Header/Layout Usado nas Páginas do Instrutor

### ✅ Estrutura Atual

**Arquivo Principal:** `instrutor/dashboard.php`

**Características:**
- **NÃO usa header/layout compartilhado** - HTML inline
- **CSS:** `../assets/css/mobile-first.css`
- **Font Awesome:** CDN (`https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css`)

**Estrutura do Header:**
```html
<div class="header">
    <h1>Olá, <?php echo htmlspecialchars($instrutor['nome']); ?>!</h1>
    <div class="subtitle">Gerencie suas aulas e turmas</div>
</div>
```

**Observações:**
- Header simples, sem dropdown de usuário
- Sem menu de navegação superior
- Layout mobile-first

**Outros Arquivos do Instrutor:**
- `instrutor/dashboard-mobile.php` - Versão alternativa (usa layout mobile-first compartilhado)

---

## 2. Lógica de Autenticação/Sessão do Instrutor

### ✅ Localização

**Arquivo:** `instrutor/dashboard.php` (linhas 13-18)

**Código:**
```php
// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    header('Location: /login.php');
    exit();
}
```

**Funções Utilizadas:**
- `getCurrentUser()` - Função global de `includes/auth.php`
- Verifica tipo `'instrutor'` explicitamente
- Redireciona para `/login.php` se não autenticado

**Fluxo de Login:**
- `login.php` → `Auth::login()` → `redirectAfterLogin()` → `/instrutor/dashboard.php`
- Função `redirectAfterLogin()` já implementada em `includes/auth.php`

**Observações:**
- ✅ Autenticação funcional
- ❌ **NÃO verifica `precisa_trocar_senha`** no login
- ❌ **NÃO força troca de senha** após login

---

## 3. Implementação de Troca de Senha e `precisa_trocar_senha`

### ✅ Localização

**API de Redefinição de Senha (Admin):**
- **Arquivo:** `admin/api/usuarios.php`
- **Endpoint:** `POST` com `action=reset_password`
- **Linhas:** 96-330

**Funcionalidades:**
- ✅ Modo automático (gera senha temporária)
- ✅ Modo manual (admin define senha)
- ✅ Validação de senha (mínimo 8 caracteres)
- ✅ Hash bcrypt (`password_hash()`)
- ✅ Flag `precisa_trocar_senha = 1` após reset
- ✅ Log de auditoria

**Verificação de Coluna:**
```php
// Verifica se coluna existe dinamicamente
$hasPrecisaTrocarSenha = false;
try {
    $checkColumn = $db->fetch("SHOW COLUMNS FROM usuarios LIKE 'precisa_trocar_senha'");
    if ($checkColumn) {
        $hasPrecisaTrocarSenha = true;
    }
} catch (Exception $e) {
    // Coluna não existe
}
```

**Frontend (Admin):**
- **Arquivo:** `admin/pages/usuarios.php`
- **Modal:** `#resetPasswordModal`
- **Funções JS:** `showResetPasswordModal()`, `confirmResetPassword()`, etc.

**Observações:**
- ✅ Sistema de reset de senha funcional para admin
- ❌ **NÃO existe troca de senha pelo próprio instrutor**
- ❌ **NÃO existe verificação de `precisa_trocar_senha` no login**

---

## 4. Estrutura de Dados

### Tabela `usuarios`

**Colunas Relevantes:**
- `id` - ID do usuário
- `nome` - Nome completo
- `email` - Email (único)
- `senha` - Hash bcrypt
- `tipo` - ENUM('admin', 'instrutor', 'secretaria', 'aluno')
- `ativo` - TINYINT(1)
- `status` - VARCHAR(20) - 'ativo'
- `cpf` - VARCHAR(14) - opcional
- `telefone` - VARCHAR(20) - opcional
- `precisa_trocar_senha` - TINYINT(1) - flag para forçar troca
- `criado_em` / `created_at` - Timestamp

### Tabela `instrutores`

**Colunas:**
- `id` - ID do instrutor (PK)
- `usuario_id` - FK para `usuarios.id`
- `cfc_id` - FK para `cfcs.id`
- `credencial` - VARCHAR(50) - credencial do instrutor
- `categoria_habilitacao` - VARCHAR(100)
- `ativo` - BOOLEAN

**Relacionamento:**
- `instrutores.usuario_id` → `usuarios.id`
- Um usuário pode ter um registro em `instrutores`
- Se não tiver registro em `instrutores`, não terá aulas vinculadas

---

## 5. Menu/Navegação do Instrutor

### ✅ Estrutura Atual

**Arquivo:** `instrutor/dashboard.php`

**Ações Rápidas (Cards):**
- "Ver Todas as Aulas" - Link para `/instrutor/aulas.php`
- "Central de Avisos" - Link (não especificado)
- "Registrar Ocorrência" - Link (não especificado)
- "Contatar Secretária" - Link (não especificado)

**Observações:**
- Menu simples, sem sidebar
- Links para páginas que podem não existir
- Sem menu de navegação persistente

---

## 6. Endpoint de Logout

### ✅ Localização

**Arquivo:** `admin/logout.php`

**Funcionalidade:**
- Usa `$auth->logout()` de `includes/auth.php`
- Limpa sessão e cookies
- Redireciona para `index.php?message=logout_success`

**Observações:**
- ✅ Funcional para todos os tipos de usuário
- Pode ser reutilizado para instrutor

---

## 7. Comparação com Admin

### Header do Admin

**Estrutura:**
- Topbar com logo, busca, notificações, perfil
- Dropdown de perfil com:
  - Avatar com iniciais
  - Nome e role
  - Links: Meu Perfil, Trocar senha, Sair

**CSS:**
- `admin/assets/css/topbar-*.css`
- Classes: `profile-button`, `profile-dropdown`, `profile-avatar`, etc.

**JavaScript:**
- `admin/assets/js/topbar-*.js`
- Toggle do dropdown

---

## 8. Conclusões da Auditoria

### ✅ O que já existe:
1. Autenticação funcional para instrutor
2. Sistema de reset de senha (admin) com `precisa_trocar_senha`
3. Estrutura de dados completa
4. Layout mobile-first

### ❌ O que falta:
1. **Dropdown de usuário no header do instrutor**
2. **Página "Meu Perfil" do instrutor**
3. **Página "Trocar Senha" do instrutor**
4. **API para atualização de perfil do instrutor**
5. **API para troca de senha do instrutor**
6. **Verificação de `precisa_trocar_senha` no login**
7. **Forçar troca de senha quando flag = 1**

---

## 9. Arquivos que Serão Criados/Modificados

### Novos Arquivos:
1. `instrutor/perfil.php` - Página de perfil
2. `instrutor/trocar-senha.php` - Página de troca de senha
3. `instrutor/api/perfil.php` - API para atualizar perfil
4. `instrutor/api/trocar-senha.php` - API para trocar senha
5. `instrutor/includes/header.php` - Header compartilhado (opcional)

### Arquivos Modificados:
1. `instrutor/dashboard.php` - Adicionar dropdown de usuário
2. `login.php` - Adicionar verificação de `precisa_trocar_senha`
3. `includes/auth.php` - Ajustar `redirectAfterLogin()` se necessário

---

## 10. Próximos Passos

1. ✅ Auditoria concluída
2. ⏳ Implementar dropdown de usuário
3. ⏳ Criar página de perfil
4. ⏳ Criar página de troca de senha
5. ⏳ Criar APIs necessárias
6. ⏳ Implementar verificação de `precisa_trocar_senha`
7. ⏳ Revisar menu/navegação

---

## Notas Técnicas

### Reutilização de Código

**APIs Existentes:**
- `admin/api/usuarios.php` - Pode ser reutilizada parcialmente
- Validações de senha já implementadas
- Hash bcrypt já implementado

**CSS/JS:**
- Classes do admin podem ser reutilizadas
- Adaptar para mobile-first se necessário

### Segurança

- Instrutor só pode editar próprio perfil
- Validação de senha atual obrigatória
- Hash sempre usado para senhas
- Log de auditoria para mudanças

---

**Fim da Auditoria**

