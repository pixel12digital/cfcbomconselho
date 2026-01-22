# 🔧 Correção do .htaccess para Produção

## 🚨 Problema Identificado

O `.htaccess` na **raiz do projeto** (`public_html/painel/.htaccess`) contém regras do **ambiente local** que não funcionam em produção:

```apache
# ❌ INCORRETO para produção
RewriteCond %{REQUEST_URI} !^/cfc-v\.1/public_html/
```

Isso está causando conflito e pode resultar em erro 403.

---

## ✅ SOLUÇÃO

### Opção A: Remover o `.htaccess` da raiz (RECOMENDADO)

O `.htaccess` correto deve estar **apenas** em:
- `public_html/painel/public_html/.htaccess`

**Ação:** Delete ou renomeie o `.htaccess` da raiz (`public_html/painel/.htaccess`)

---

### Opção B: Corrigir o `.htaccess` da raiz (se necessário)

Se o `.htaccess` na raiz for necessário (ex: para proteger diretórios), remova as regras de rewrite incorretas:

**Arquivo:** `public_html/painel/.htaccess`

```apache
# Proteger diretórios sensíveis
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger storage e certificados
RewriteEngine On
RewriteRule ^storage/ - [F,L]
RewriteRule ^certificados/.*\.(p12|pfx|pem)$ - [F,L]
```

**⚠️ IMPORTANTE:** Remova todas as regras que mencionam `/cfc-v.1/public_html/`!

---

## ✅ `.htaccess` CORRETO (dentro de public_html/)

O `.htaccess` que deve ser usado está em:
- **Caminho:** `public_html/painel/public_html/.htaccess`

**Conteúdo correto:**

```apache
# Front Controller Pattern
RewriteEngine On

# Permitir acesso direto a arquivos estáticos (assets, imagens, etc)
RewriteCond %{REQUEST_FILENAME} -f [OR]
RewriteCond %{REQUEST_FILENAME} -d
RewriteRule ^ - [L]

# Redirecionar tudo para index.php
RewriteRule ^(.*)$ index.php [QSA,L]

# Segurança
<FilesMatch "^\.">
    Order allow,deny
    Deny from all
</FilesMatch>

# Proteger storage
RewriteRule ^storage/ - [F,L]

# Headers de segurança
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

## 📋 Checklist de Verificação

- [ ] **Remover ou corrigir** `.htaccess` da raiz (`public_html/painel/.htaccess`)
- [ ] **Verificar** se `.htaccess` correto existe em `public_html/painel/public_html/.htaccess`
- [ ] **Conteúdo correto:** Sem referências a `/cfc-v.1/public_html/`
- [ ] **Permissões:** `.htaccess` deve ter permissões 644

---

## 🔍 Como Verificar

1. **No File Manager da Hostinger:**
   - Verifique se existe `.htaccess` em `public_html/painel/` (raiz)
   - Se existir, abra e verifique se tem `cfc-v.1/public_html`
   - Se tiver, **delete ou corrija**

2. **Verifique se o `.htaccess` correto existe:**
   - Caminho: `public_html/painel/public_html/.htaccess`
   - Deve ter o conteúdo do Front Controller (sem `cfc-v.1`)

---

## ✅ Após Corrigir

1. **Teste o acesso:** `https://painel.cfcbomconselho.com.br`
2. **Se ainda der 403:** Verifique:
   - DocumentRoot do subdomínio
   - Permissões do `index.php` (já está correto: 644)
   - Permissões do diretório `public_html/` (já está correto: 755)

---

## 🎯 Resumo

**Problema:** `.htaccess` da raiz com regras do ambiente local  
**Solução:** Remover ou corrigir o `.htaccess` da raiz  
**Correto:** Usar apenas o `.htaccess` em `public_html/painel/public_html/`
