# 🔄 Como Funciona Deploy GitHub → Hostinger

## ❌ **O que NÃO faz deploy automaticamente:**

### GitHub sozinho:
- ❌ GitHub **não sabe** onde seu código deve ir
- ❌ GitHub **não sabe** qual servidor usar
- ❌ GitHub **não tem acesso** ao seu servidor Hostinger

## ✅ **O que é necessário para deploy:**

### 1️⃣ **Hostinger precisa do GitHub**
- ✅ Chave SSH no GitHub (FEITO!)
- ✅ Hostinger configurar qual repositório clonar
- ✅ Hostinger fazer git clone/pull do seu código

### 2️⃣ **Processo completo:**
```
Seu código local → (git push) → GitHub
GitHub → (clone/autorização) → Hostinger → Seu site
```

## 🚀 **O que precisa acontecer:**

### Na Hostinger, você precisa configurar:
1. **URL do repositório:** `git@github.com:pixel12digital/cfcbomconselho.git`
2. **Diretório de destino:** `/public_html/`
3. **Branch:** `master`

### Então Hostinger vai:
- Conectar no GitHub usando a chave SSH
- Fazer `git clone` do seu repositório
- Copiar todo código para `/public_html/`
- Futuras atualizações: `git pull` automático

## 💡 **Alternativa: Webhook Manual**

Se não conseguir configurar na Hostinger:

### Configurar webhook GitHub → Seu site:
```
git push → GitHub → Webhook → https://cfcbomconselho.com.br/deploy.php
deploy.php → executa git pull → Atualiza site
```

**Mas isso requer que Hostinger já tenha acesso SSH/Git configurado.**
