# 🚀 Configuração do Repositório Git na Hostinger

## 📋 Passos para Configurar Deploy Automático

### 1️⃣ **Configurar SSH Key no GitHub**

1. **Copie a chave SSH da Hostinger:**
   - Na tela da Hostinger, clique no botão "Copiar"
   - A chave será algo como: `ssh-rsa AAAAB3NzaC1yc2EAAAADAQABAAABgQC/...`

2. **Adicione no GitHub:**
   - Acesse: https://github.com/settings/keys
   - Clique em "New SSH key"
   - Título: `Hostinger CFC Bom Conselho`
   - Cole a chave copiada
   - Salve

### 2️⃣ **Configurar Repositório na Hostinger**

1. **Na tela da Hostinger:**
   - Clique em "Criar um novo repositório"
   - **Repositório:** `git@github.com:pixel12digital/cfcbomconselho.git`
   - **Ramo:** `master`
   - **Diretório:** (deixe vazio)
   - Clique em "Criar"

### 3️⃣ **Configurar Webhook no GitHub (Opcional)**

Para deploy automático, configure webhook:

1. **No GitHub:**
   - Acesse: https://github.com/pixel12digital/cfcbomconselho/settings/hooks
   - Clique em "Add webhook"
   - **Payload URL:** `https://cfcbomconselho.com.br/deploy.php`
   - **Content type:** `application/json`
   - **Events:** `Just the push event`
   - Ative

### 4️⃣ **Teste o Deploy**

1. **Faça uma alteração no código local**
2. **Commit e push:**
   ```bash
   git add .
   git commit -m "Teste de deploy automático"
   git push origin master
   ```
3. **Verifique se foi sincronizado na Hostinger**

## ⚠️ **Importante:**

- ✅ Certifique-se de que o diretório esteja vazio antes da primeira configuração
- ✅ Backup automático será criado em `/backups/`
- ✅ Logs de deploy ficam em `/logs/deploy.log`
- ✅ Cache será limpo automaticamente após cada deploy

## 🔧 **Arquivos Criados:**

- `deploy.php` - Webhook para deploy automático
- `config_deploy.json` - Configurações do deploy
- `git_deploy_setup.md` - Este guia

## 🆘 **Resolução de Problemas:**

**Se o deploy não funcionar:**
1. Verifique os logs em `/logs/deploy.log`
2. Teste a conexão SSH manualmente
3. Verifique permissões de arquivos (644/755)
4. Confirme se o repositório GitHub está acessível

**Se precisar de ajuda:**
- Use o botão "💡 Pergunte ao Kodee" no painel da Hostinger
- Verifique a documentação: https://support.hostinger.com/git
