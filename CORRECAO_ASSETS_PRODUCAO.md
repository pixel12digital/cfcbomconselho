# 🔧 Correção de Assets (CSS/JS) em Produção

## 🎯 Problema

As páginas estão sem estilo (CSS) em produção porque os assets não estão sendo carregados corretamente.

---

## ✅ SOLUÇÃO APLICADA

### 1. Ajustado `asset_url()` no `app/Bootstrap.php`

A função `asset_url()` agora detecta o ambiente e usa o path correto:

**Produção:**
- Assets apontam para `/public_html/assets/` (onde realmente estão)

**Desenvolvimento:**
- Assets apontam para `/assets/` (como antes)

### 2. Atualizado `.htaccess` na raiz

Adicionadas regras para permitir acesso aos assets:
- `/public_html/assets/` → permite acesso direto
- `/assets/` → permite acesso direto (se existir symlink)

---

## 📋 VERIFICAÇÃO NECESSÁRIA NA HOSTINGER

### ⚠️ IMPORTANTE: Verificar Estrutura de Assets

Os assets devem estar acessíveis em:
```
public_html/painel/public_html/assets/
├── css/
│   ├── tokens.css
│   ├── components.css
│   ├── layout.css
│   └── utilities.css
└── js/
    └── app.js
```

**Verificar no File Manager:**
1. Navegue até: `public_html/painel/public_html/`
2. Confirme que existe a pasta `assets/`
3. Confirme que contém `css/` e `js/` com os arquivos

---

## 🧪 TESTE RÁPIDO

Após fazer deploy das alterações:

1. **Acesse:** `https://painel.cfcbomconselho.com.br/`
2. **Abra DevTools (F12)** → Aba **Network**
3. **Recarregue a página** (Ctrl+F5 para limpar cache)
4. **Verifique os requests de CSS:**
   - Procure por arquivos `.css` na lista
   - Status deve ser **200** (não 404)
   - URL deve ser algo como: `/public_html/assets/css/tokens.css`

---

## ✅ Se os Assets Não Estão Acessíveis

### Opção 1: Copiar Assets para Raiz (mais simples)

Se os assets não estão acessíveis, copie para a raiz do DocumentRoot:

**Na Hostinger:**
1. Copie a pasta `assets/` de `public_html/painel/public_html/assets/`
2. Para: `public_html/painel/assets/`
3. Agora os assets estarão em ambos os locais

**Depois ajuste o `asset_url()` para apontar apenas para `/assets/` em produção.**

### Opção 2: Criar Symlink (recomendado)

Se possível criar symlink no servidor:

```bash
ln -s public_html/assets public_html/painel/assets
```

Mas isso pode não ser possível via File Manager da Hostinger.

---

## 🔍 DIAGNÓSTICO

### Verificar no Browser DevTools:

1. **Abra DevTools (F12)**
2. **Aba Network**
3. **Recarregue a página**
4. **Procure por arquivos `.css`**

**Status 404:**
- ❌ Assets não encontrados
- Verificar se pasta `assets/` existe no local correto

**Status 200:**
- ✅ Assets carregando
- Mas ainda sem estilo → problema pode ser cache do browser

---

## ✅ CHECKLIST

- [ ] `app/Bootstrap.php` atualizado (já feito)
- [ ] `.htaccess` da raiz atualizado (já feito)
- [ ] Pasta `assets/` existe em `public_html/painel/public_html/assets/`
- [ ] Arquivos CSS/JS estão dentro de `assets/`
- [ ] Fazer deploy das alterações
- [ ] Testar acesso aos assets via DevTools

---

## 📝 PRÓXIMOS PASSOS

1. **Fazer deploy** dos arquivos atualizados (`app/Bootstrap.php` e `.htaccess`)
2. **Verificar** se `assets/` existe em `public_html/painel/public_html/assets/`
3. **Testar** o acesso aos assets
4. **Se não funcionar:** Copiar `assets/` para `public_html/painel/assets/`
