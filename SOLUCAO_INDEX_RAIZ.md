# 🔧 Solução: Index.php na Raiz

## 🎯 Problema

O subdomínio `painel` aponta para:
```
/home/u502697186/domains/cfcbomconselho.com.br/public_html/painel
```

Mas o `index.php` está em:
```
/home/u502697186/domains/cfcbomconselho.com.br/public_html/painel/public_html/
```

**Solução:** Criar um `index.php` na raiz que inclua o `public_html/index.php`.

---

## ✅ SOLUÇÃO

### PASSO 1: Criar `index.php` na raiz

**Localização:** `public_html/painel/index.php`

**Conteúdo:**

```php
<?php
/**
 * Front controller na raiz
 * Redireciona para public_html/index.php
 */

// Definir caminho absoluto para public_html
$publicHtmlPath = __DIR__ . '/public_html/index.php';

// Verificar se o arquivo existe
if (!file_exists($publicHtmlPath)) {
    http_response_code(500);
    die('Arquivo index.php não encontrado em public_html/');
}

// Incluir o index.php real
require_once $publicHtmlPath;
```

---

### PASSO 2: Criar/Atualizar `.htaccess` na raiz

**Localização:** `public_html/painel/.htaccess`

**Conteúdo (substituir o atual):**

```apache
# Proteger diretórios sensíveis
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Front Controller - Redirecionar para index.php na raiz
RewriteEngine On

# Permitir acesso direto a arquivos estáticos
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Permitir acesso direto a public_html/assets
RewriteCond %{REQUEST_URI} ^/assets/
RewriteRule ^ - [L]

# Redirecionar tudo para index.php na raiz
RewriteRule ^(.*)$ index.php [QSA,L]

# Proteger storage e certificados
RewriteRule ^storage/ - [F,L]
RewriteRule ^certificados/.*\.(p12|pfx|pem)$ - [F,L]
```

---

## 📋 ESTRUTURA FINAL

```
public_html/painel/
├── index.php  ← NOVO (redireciona para public_html/index.php)
├── .htaccess  ← ATUALIZAR (rewrite para index.php na raiz)
├── app/
├── public_html/  ← index.php REAL está aqui
│   ├── index.php  ← mantém como está
│   ├── .htaccess  ← mantém como está
│   └── assets/
├── certificados/
└── .env
```

---

## ✅ Como Funciona

1. Usuário acessa: `https://painel.cfcbomconselho.com.br/`
2. Apache lê `.htaccess` na raiz (`public_html/painel/`)
3. `.htaccess` redireciona tudo para `index.php` na raiz
4. `index.php` na raiz inclui `public_html/index.php`
5. O sistema funciona normalmente

---

## 🧪 TESTES

Após criar os arquivos:

1. **Teste:** `https://painel.cfcbomconselho.com.br/`
2. **Deve:** Carregar normalmente
3. **Verificar:** Login e navegação funcionam

---

## ⚠️ IMPORTANTE

- Mantenha o `index.php` e `.htaccess` em `public_html/painel/public_html/` (não delete)
- O novo `index.php` na raiz apenas redireciona para o real
- Isso permite que o código continue funcionando normalmente
