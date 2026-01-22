# 🔍 Diagnóstico Avançado - Erro 403 Forbidden

## ✅ Já Verificado e Corrigido

- ✅ Permissões do diretório `public_html/`: 755
- ✅ Permissões do `index.php`: 644
- ✅ `.htaccess` da raiz: corrigido (sem regras problemáticas)
- ✅ `.htaccess` em `public_html/painel/public_html/`: existe e está correto

---

## 🔴 POSSÍVEIS CAUSAS (ainda não verificadas)

### 1. 📍 **DocumentRoot do Subdomínio Incorreto**

**⚠️ MAIS PROVÁVEL:** O subdomínio `painel` pode não estar apontando para a pasta correta.

**Verificar no painel da Hostinger:**
1. Acesse: **Domínios** → **Gerenciar** → `painel.cfcbomconselho.com.br`
2. Verifique o **DocumentRoot** ou **Raiz do Site**
3. **Deve ser:** `/home/usuario/public_html/painel/public_html/` (ou similar)

**Se estiver incorreto:**
- Altere para: `public_html/painel/public_html/` (caminho relativo)
- Ou: `/home/usuario/public_html/painel/public_html/` (caminho absoluto)
- Salve e aguarde alguns minutos para propagar

---

### 2. 🔒 **Permissões do Diretório Pai**

Verifique permissões do diretório **pai** (`painel/`):

**Na Hostinger:**
- Caminho: `public_html/painel/`
- Permissões: **755** (rwxr-xr-x)

---

### 3. 📄 **Index.php Não Acessível**

Verifique se o `index.php` está realmente no local correto:

**Caminho esperado:**
```
public_html/painel/public_html/index.php
```

**Verificar:**
- O arquivo existe neste caminho?
- As permissões estão corretas (644 ou 755)?

---

### 4. 🚫 **Bloqueio no .htaccess**

Pode haver algum conflito entre os dois `.htaccess`:

**Verificar:**
- `.htaccess` em `public_html/painel/.htaccess` (raiz) - já corrigido ✅
- `.htaccess` em `public_html/painel/public_html/.htaccess` - verificar conteúdo

---

### 5. 🌐 **Configuração do Subdomínio**

**Verificar no painel da Hostinger:**
1. **Domínios** → **Subdomínios**
2. Verifique se `painel` está **ativo** e **apontando para a pasta correta**
3. Se não estiver, edite e configure:
   - **Caminho:** `public_html/painel/public_html/`
   - **Status:** Ativo

---

### 6. 🔐 **Permissões do Usuário/Apache**

O servidor pode não ter permissão para acessar os arquivos.

**Verificar:**
- O proprietário dos arquivos deve ser o usuário do cPanel/Hostinger
- Geralmente o servidor web (Apache) usa o mesmo usuário

---

## 🧪 TESTES PARA DIAGNOSTICAR

### Teste 1: Acessar index.php diretamente

Acesse: `https://painel.cfcbomconselho.com.br/index.php`

- ✅ **Se funcionar:** O problema é com `.htaccess` ou rewrite
- ❌ **Se der 403:** O problema é com permissões ou DocumentRoot

---

### Teste 2: Criar arquivo test.php

Crie um arquivo `test.php` em `public_html/painel/public_html/`:

```php
<?php
echo "PHP funciona!";
phpinfo();
?>
```

Acesse: `https://painel.cfcbomconselho.com.br/test.php`

- ✅ **Se funcionar:** PHP está OK, problema é com `index.php` ou rotas
- ❌ **Se der 403:** Problema é com permissões ou DocumentRoot

**⚠️ IMPORTANTE:** Delete o `test.php` após testar!

---

### Teste 3: Verificar se arquivos estáticos funcionam

Acesse: `https://painel.cfcbomconselho.com.br/assets/css/layout.css`

(ou qualquer arquivo CSS/JS dentro de `public_html/painel/public_html/assets/`)

- ✅ **Se funcionar:** Arquivos estáticos acessíveis, problema é com `index.php`
- ❌ **Se der 403:** Problema geral de permissões ou DocumentRoot

---

## 📋 CHECKLIST COMPLETO

Marque cada item ao verificar:

### Permissões
- [x] Diretório `public_html/`: 755 ✅
- [x] Arquivo `index.php`: 644 ✅
- [ ] Diretório pai `painel/`: 755
- [ ] Proprietário dos arquivos: usuário correto

### Configuração
- [ ] DocumentRoot do subdomínio: `public_html/painel/public_html/`
- [ ] Subdomínio `painel` está ativo
- [ ] `.htaccess` em `public_html/painel/public_html/` existe

### Arquivos
- [ ] `index.php` existe em `public_html/painel/public_html/`
- [ ] `.htaccess` correto em `public_html/painel/public_html/`

---

## 🆘 SE NADA FUNCIONAR

### Contatar Suporte Hostinger

1. **Informações para o suporte:**
   - URL: `painel.cfcbomconselho.com.br`
   - Erro: 403 Forbidden
   - Estrutura de pastas: `public_html/painel/public_html/`
   - Permissões configuradas: 755 (diretórios), 644 (arquivos)
   - Testes realizados: [listar os que você fez]

2. **Solicitar:**
   - Verificar configuração do DocumentRoot do subdomínio
   - Verificar se há bloqueios no servidor
   - Verificar permissões do usuário/Apache

---

## 🎯 PRÓXIMOS PASSOS (ordem de prioridade)

1. **Verificar DocumentRoot do subdomínio** (mais provável)
2. **Fazer Teste 2** (criar test.php)
3. **Verificar permissões do diretório pai** (`painel/`)
4. **Verificar se subdomínio está ativo**
