# 🚀 Solução Rápida - Deployment Git

## Problema
```
fatal: could not read Username for 'https://github.com': No such device or address
```

## ✅ Solução Mais Rápida: Personal Access Token

### Passo 1: Criar Token no GitHub (2 minutos)

1. Acesse: https://github.com/settings/tokens
2. Clique em **"Generate new token"** → **"Generate new token (classic)"**
3. Nome: `deploy-producao-hostinger`
4. Marque: **`repo`** (acesso completo)
5. Clique em **"Generate token"**
6. **COPIE O TOKEN** (você não verá novamente!)

### Passo 2: Configurar no Servidor (via SSH ou File Manager)

**Se você tem acesso SSH:**

```bash
# Conectar ao servidor
ssh usuario@servidor

# Ir para o diretório do projeto
cd /caminho/do/projeto

# Configurar credential helper
git config --global credential.helper store

# Fazer um pull manual (vai pedir credenciais)
git pull origin master
# Quando pedir:
#   Username: pixel12digital
#   Password: COLE_SEU_TOKEN_AQUI

# Agora está configurado! Teste o deployment novamente.
```

**Se você NÃO tem acesso SSH:**

1. Acesse o **painel da Hostinger**
2. Vá em **"Deployment"** ou **"Git"**
3. Procure por **"Credenciais"** ou **"Authentication"**
4. Configure:
   - **Username:** `pixel12digital`
   - **Password/Token:** `SEU_TOKEN_AQUI`
5. Salve e tente deployment novamente

---

## 🔄 Alternativa: Mudar para SSH (Mais Seguro)

Se você tem acesso SSH e quer uma solução permanente:

```bash
# 1. Gerar chave SSH (se não tiver)
ssh-keygen -t ed25519 -C "deploy@hostinger"
# Pressionar Enter 2x (sem senha)

# 2. Ver chave pública
cat ~/.ssh/id_ed25519.pub
# COPIAR TODO O CONTEÚDO

# 3. Adicionar no GitHub:
#    https://github.com/settings/keys → "New SSH key" → Colar e salvar

# 4. Mudar remote no servidor
cd /caminho/do/projeto
git remote set-url origin git@github.com:pixel12digital/cfv-v1.git

# 5. Testar
git fetch origin
```

---

## ✅ Após Configurar

Teste o deployment novamente. Deve funcionar!

Se ainda der erro, verifique:
- Token tem permissão `repo`
- Nome do repositório está correto
- Credenciais foram salvas corretamente

---

## 📝 Nota

O arquivo `pwa-manifest.php` será baixado automaticamente quando o deployment funcionar, pois já está commitado no repositório.
