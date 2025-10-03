# 🔄 Alternativas para Git Pull na Hospedagem

## 🚀 **Metodos para Automatizar Deploy:**

### **1️⃣ SSH Terminal Direto**
```bash
# Conectar via SSH na Hostinger
ssh u502697186@cfcbomconselho.com.br
cd /public_html
git pull origin master
```

**Vantagens:** ✅ Direto e rápido
**Desvantagens:** ❌ Manual, precisa fazer sempre

### **2️⃣ GitHub Actions (CI/CD)**
```yaml
# Automatizar via GitHub Actions
# Arquivo: .github/workflows/deploy.yml (já criado!)
```

**Configuração necessária:**
- SSH Key no GitHub Secrets
- Workflow configurado
- **Deploy automático a cada push!**

### **3️⃣ Webhook Personalizado (JA CRIADO!)**
```php
// deploy.php - webhook automático
// Git hook no servidor -> pull automático
```

**Como funciona:**
1. Push no GitHub
2. Webhook chama `deploy.php`
3. `deploy.php` executa `git pull`
4. Site atualizado automaticamente

### **4️⃣ FTP/Git Sync Tools**
```bash
# Ferramentas como rsync/cygdrive
rsync -avz --delete ./ usuario@hostinger:/public_html/
```

### **5️⃣ Composer/Homestead**
```bash
# Usando Homestead para deploy
php artisan deploy:hostinger
```

## 🎯 **Comparativo:**

| Método | Automático | Complexidade | Recomendado |
|--------|------------|--------------|-------------|
| SSH Direto | ❌ | ⭐ | Para testes |
| GitHub Actions | ✅ | ⭐⭐⭐ | Para produção |
| Webhook PHP | ✅ | ⭐⭐ | **MAIS FÁCIL** |
| FTP Sync | ⚡ Semi | ⭐⭐⭐ | Para grandes projetos |
| Homestead | ✅ | ⭐⭐⭐⭐ | Para Laravel |

## 🚀 **Minha Recomendação:**

### **Para SIMPLICIDADE:** Webhook PHP ✅
- ✅ Arquivo `deploy.php` já criado
- ✅ Configuração simples
- ✅ Sem SSH keys complexas
- ✅ Deploy automático funcionando

### **Para PROFISSIONAL:** GitHub Actions ⭐
- ✅ Controle total do processo
- ✅ Logs detalhados
- ✅ Rollback automático
- ✅ Múltiplos ambientes

## 🔧 **Setup Rápido (Webhook):**

### No GitHub:
1. Settings → Webhooks → Add webhook
2. URL: `https://cfcbomconselho.com.br/deploy.php`
3. Content type: `application/json`
4. Events: `Just the push event`

### No Código:
- ✅ `deploy.php` já está pronto
- ✅ Clona/pull automático
- ✅ Logs em `/logs/deploy.log`
