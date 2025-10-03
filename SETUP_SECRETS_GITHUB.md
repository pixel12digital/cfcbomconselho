# 🔐 Configuração SSH Secrets no GitHub

## 📋 **Passo a Passo Completo:**

### **1️⃣ Obter Credenciais SSH da Hostinger**

#### **A. SSH Key (chave privada):**
1. **Hostinger:** SSH Management → SSH Keys
2. **Copie a chave PRIVADA** (não a pública!)
3. **Salvou em:** `~/.ssh/hostinger_key` (exemplo)

#### **B. Dados de Conexão:**
```
Host: cfcbomconselho.com.br (ou IP)
Username: u502697186 (usuário SSH)
Port: 22 (padrão)
```

### **2️⃣ Configurar Secrets no GitHub**

#### **A. Acesse GitHub:**
1. Vá em: https://github.com/pixel12digital/cfcbomconselho/settings/secrets/actions
2. Clique **"New repository secret"**

#### **B. Adicione os secrets:**

**Secret 1: `HOSTINGER_HOST`**
```
Valor: cfcbomconselho.com.br
```

**Secret 2: `HOSTINGER_USERNAME`**  
```
Valor: u502697186
```

**Secret 3: `HOSTINGER_PORT`**
```
Valor: 22
```

**Secret 4: `HOSTINGER_SSH_KEY`** (MAIS IMPORTANTE)
```
Valor: (chave SSH privada completa)
-----BEGIN OPENSSH PRIVATE KEY-----
b3BlbnNzaC1rZXktdjEAAAAABG5vbmUAAAAEbm9uZQAAAAAAAAABAAA...
-----END OPENSSH PRIVATE KEY-----
```

### **3️⃣ Testar Conexão SSH**

#### **No terminal da Hostinger:**
```bash
# Testar se SSH funciona
ssh -T git@github.com

# Verificar permissões
ls -la ~/.ssh/
```

### **4️⃣ Ativar GitHub Actions**

#### **No repositório GitHub:**
1. **Actions** → **Allow actions**
2. Procure por workflow "Deploy to Production"
3. **Enable workflow**

## ✅ **Após configuração:**

- ✅ Push no código → Deploy automático
- ✅ Backup antes de cada deploy
- ✅ Rollback em caso de erro
- ✅ Logs detalhados
- ✅ Notificações de status

## 🚨 **IMPORTANTE:**

**NUNCA compartilhe as chaves SSH privadas publicamente!**
- ✅ Use apenas GitHub Secrets
- ❌ Não commite no código
- ❌ Não envie por email/mensagem

## 🔍 **Verificar se funcionou:**

1. Fazer uma pequena alteração
2. `git add . && git commit -m "test deploy"`  
3. `git push origin master`
4. Vá em **Actions** → Ver deploy automático
