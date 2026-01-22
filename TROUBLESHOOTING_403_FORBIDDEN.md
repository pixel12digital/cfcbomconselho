# 🔴 Troubleshooting: Erro 403 Forbidden em Produção

## 🎯 Problema
Ao acessar `painel.cfcbomconselho.com.br`, retorna erro **403 Forbidden**.

---

## ✅ SOLUÇÕES (tente nesta ordem)

### 1. 🔒 **Verificar Permissões de Arquivos e Pastas**

**Na Hostinger (File Manager):**

#### A. Verificar permissões do `index.php`
- Caminho: `public_html/painel/public_html/index.php`
- Permissões: **644** ou **755**

#### B. Verificar permissões do diretório `public_html/`
- Caminho: `public_html/painel/public_html/`
- Permissões: **755**

#### C. Verificar permissões da raiz do projeto
- Caminho: `public_html/painel/`
- Permissões: **755**

**Como verificar/alterar:**
1. No File Manager da Hostinger
2. Clique com botão direito no arquivo/pasta
3. Selecione "Change Permissions" ou "Permissões"
4. Para arquivos: `644` ou `755`
5. Para diretórios: `755`

---

### 2. 📁 **Verificar Estrutura de Pastas**

O subdomínio `painel` deve apontar para a pasta correta:

**Estrutura Esperada:**
```
/home/usuario/public_html/painel/
├── app/
├── public_html/  ← O subdomínio deve apontar AQUI (ou para painel/)
│   └── index.php
├── assets/
├── .env
└── certificados/
```

**Verificar no painel da Hostinger:**
1. Vá em **Domínios** → **Subdomínios**
2. Verifique onde `painel` está apontando:
   - ✅ **Correto:** Aponta para `public_html/painel/` OU `public_html/painel/public_html/`
   - ❌ **Errado:** Aponta para outra pasta

**Se o subdomínio apontar para `public_html/painel/public_html/`:**
- O `index.php` deve estar em `public_html/painel/public_html/index.php`
- O `.env` deve estar em `public_html/painel/.env`

---

### 3. 🔧 **Verificar Configuração do .htaccess**

**Verifique se o `.htaccess` existe:**
- Caminho: `public_html/painel/public_html/.htaccess`
- Deve conter as regras de rewrite

**Se o `.htaccess` não existe ou está vazio:**

Crie um arquivo `.htaccess` em `public_html/painel/public_html/` com este conteúdo:

```apache
# Front Controller Pattern
RewriteEngine On

# Permitir acesso direto a arquivos estáticos
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
```

---

### 4. 🌐 **Verificar DocumentRoot**

O DocumentRoot do subdomínio deve estar correto.

**No painel da Hostinger:**
1. Vá em **Domínios** → **Gerenciar** → `painel.cfcbomconselho.com.br`
2. Verifique o **DocumentRoot**
3. Deve ser: `/home/usuario/public_html/painel/public_html/` (ou similar)

**Se estiver incorreto:**
- Altere para o caminho correto onde está o `index.php`
- Salve e aguarde alguns minutos para propagar

---

### 5. 🚫 **Verificar se Arquivo Index Existe**

**Verificar se `index.php` existe:**
- Caminho: `public_html/painel/public_html/index.php`
- Deve existir e ter permissões corretas (644 ou 755)

**Se não existir:**
- Faça upload do `index.php` para o local correto
- Garanta permissões 644 ou 755

---

### 6. 📝 **Verificar Arquivo .env**

**Importante:** O `.env` deve estar na **raiz do projeto**, não dentro de `public_html/`:

**Localização correta:**
```
public_html/painel/.env  ← CORRETO (mesmo nível de app/)
```

**Localização ERRADA:**
```
public_html/painel/public_html/.env  ← ERRADO
```

---

### 7. 🔍 **Verificar Logs de Erro**

**Na Hostinger:**
1. Vá em **Avancado** → **Error Log** ou **Logs**
2. Procure por erros recentes
3. Verifique mensagens de "Permission denied" ou "403"

**Logs do PHP (se configurado):**
- Caminho: `storage/logs/php_errors.log`
- Verifique se há erros de permissão ou PHP

---

## 🧪 **Teste Rápido**

### 1. Testar se PHP está funcionando

Crie um arquivo `test.php` em `public_html/painel/public_html/`:

```php
<?php
phpinfo();
?>
```

Acesse: `https://painel.cfcbomconselho.com.br/test.php`

- ✅ **Se funcionar:** PHP está OK, problema é de permissões/configuração
- ❌ **Se der 403:** Problema é de permissões ou DocumentRoot

**⚠️ IMPORTANTE:** Delete o `test.php` após testar!

---

### 2. Testar acesso direto ao index.php

Acesse: `https://painel.cfcbomconselho.com.br/index.php`

- ✅ **Se funcionar:** Problema é com `.htaccess` ou rewrite
- ❌ **Se der 403:** Problema é com permissões do arquivo

---

## ✅ **Checklist de Verificação**

Marque cada item ao verificar:

- [ ] Permissões do `index.php`: 644 ou 755
- [ ] Permissões do diretório `public_html/`: 755
- [ ] Permissões do diretório raiz `painel/`: 755
- [ ] Arquivo `.htaccess` existe em `public_html/painel/public_html/`
- [ ] Arquivo `index.php` existe em `public_html/painel/public_html/`
- [ ] Subdomínio `painel` aponta para pasta correta
- [ ] DocumentRoot está configurado corretamente
- [ ] Arquivo `.env` está na raiz do projeto (não em `public_html/`)

---

## 🆘 **Se Nada Funcionar**

### Contatar Suporte Hostinger:

1. **Suporte Técnico:** Atendimento da Hostinger
2. **Informações necessárias:**
   - URL: `painel.cfcbomconselho.com.br`
   - Erro: 403 Forbidden
   - Estrutura de pastas
   - Permissões configuradas

---

## 📋 **Resumo Rápido**

**Mais comum:** Permissões incorretas
- **Solução:** Configure permissões 755 para diretórios e 644/755 para arquivos

**Segundo mais comum:** Subdomínio apontando para pasta errada
- **Solução:** Verifique e corrija o DocumentRoot do subdomínio

**Terceiro:** `.htaccess` ausente ou incorreto
- **Solução:** Crie/verifique o `.htaccess` em `public_html/painel/public_html/`
