# 🔧 Correção: Subdomínio Painel não mostra Login

## 🎯 Problema

O subdomínio `painel.cfcbomconselho.com.br` estava abrindo o dashboard/index ao invés da página de login.

## ✅ Soluções Implementadas

### 1. Validação Robusta de Sessão no `AuthController::showLogin()`

**Arquivo:** `app/Controllers/AuthController.php`

**Alteração:** Agora o método `showLogin()` verifica se o usuário realmente existe no banco de dados e está ativo antes de redirecionar para o dashboard. Se o usuário não existir ou estiver inativo, a sessão é limpa e o login é exibido.

```php
// Antes: apenas verificava se $_SESSION['user_id'] existia
if (!empty($_SESSION['user_id'])) {
    redirect(base_url('/dashboard'));
}

// Depois: verifica se o usuário existe e está ativo
if (!empty($_SESSION['user_id'])) {
    $userModel = new User();
    $user = $userModel->find($_SESSION['user_id']);
    
    if ($user && $user['status'] === 'ativo') {
        redirect(base_url('/dashboard'));
    } else {
        // Limpar sessão inválida
        session_destroy();
        session_start();
    }
}
```

### 2. Detecção do Subdomínio Painel

**Arquivo:** `public_html/index.php`

**Alteração:** Adicionada detecção do subdomínio `painel` no início do arquivo para garantir que sempre mostre o login quando não houver sessão válida.

```php
// Verificar se está sendo acessado pelo subdomínio painel
$host = $_SERVER['HTTP_HOST'] ?? '';
$isPainelSubdomain = strpos($host, 'painel.') === 0 || $host === 'painel.cfcbomconselho.com.br';

if ($isPainelSubdomain) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!empty($_SESSION['user_id'])) {
        // Validação será feita no AuthController
    } else {
        // Limpar sessão inválida
        $_SESSION = [];
    }
}
```

## 🔍 Verificações Adicionais Necessárias

### 1. Configuração do Subdomínio no Servidor

**IMPORTANTE:** Verifique se o subdomínio `painel` está apontando para o local correto:

**No painel da Hostinger:**
1. Acesse: **Domínios** → **Subdomínios**
2. Verifique onde `painel` está apontando:
   - ✅ **Correto:** Deve apontar para `public_html/painel/public_html/` OU para a pasta onde está o `public_html/index.php` do sistema
   - ❌ **Errado:** Se estiver apontando para a raiz do domínio principal (`public_html/`), ele pode estar carregando o `index.php` da landing page

**Estrutura Esperada:**
```
/home/usuario/public_html/painel/
├── app/
├── public_html/  ← O DocumentRoot do subdomínio deve apontar AQUI
│   └── index.php  ← Sistema de login (Router)
├── assets/
├── .env
└── certificados/
```

### 2. Verificar Sessões Ativas

Se o problema persistir, pode haver sessões "fantasma" no servidor. Para limpar:

1. Limpar cookies do navegador para `painel.cfcbomconselho.com.br`
2. Verificar se há sessões antigas no banco de dados (se aplicável)
3. Limpar o diretório de sessões do PHP no servidor

### 3. Verificar .htaccess

Certifique-se de que o `.htaccess` em `public_html/.htaccess` está correto e redirecionando todas as requisições para `index.php`:

```apache
# Front Controller Pattern
RewriteEngine On

# Se o arquivo/pasta existe fisicamente, NÃO reescreve
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Redirecionar tudo para index.php
RewriteRule ^ index.php [L]
```

## 📝 Testes

Após as correções, teste:

1. ✅ Acessar `painel.cfcbomconselho.com.br` sem estar logado → deve mostrar login
2. ✅ Acessar `painel.cfcbomconselho.com.br` com sessão válida → deve mostrar dashboard
3. ✅ Acessar `painel.cfcbomconselho.com.br` com sessão inválida → deve limpar sessão e mostrar login
4. ✅ Fazer logout → deve redirecionar para login

## 🚀 Deploy

As alterações foram feitas nos seguintes arquivos:
- `app/Controllers/AuthController.php`
- `public_html/index.php`

Fazer commit e push para produção:
```bash
git add app/Controllers/AuthController.php public_html/index.php
git commit -m "fix: corrige subdomínio painel para sempre mostrar login quando não houver sessão válida"
git push production master
```
