# Solução para Erro de Deployment Git

**Erro:** `fatal: could not read Username for 'https://github.com': No such device or address`

**Causa:** O servidor está tentando fazer `git pull` via HTTPS mas não tem credenciais configuradas.

## Soluções Possíveis

### ✅ Solução 1: Mudar Remote para SSH (Recomendado)

Esta é a melhor solução se você tem acesso SSH ao servidor.

#### Passo 1: Gerar chave SSH no servidor (se ainda não tiver)

```bash
# Conectar ao servidor via SSH
ssh usuario@servidor

# Gerar chave SSH (se ainda não tiver)
ssh-keygen -t ed25519 -C "deploy@servidor"
# Pressionar Enter para aceitar local padrão
# Pressionar Enter para senha vazia (ou definir uma)

# Ver chave pública
cat ~/.ssh/id_ed25519.pub
```

#### Passo 2: Adicionar chave SSH no GitHub

1. Copiar o conteúdo de `~/.ssh/id_ed25519.pub`
2. Acessar: https://github.com/settings/keys
3. Clicar em "New SSH key"
4. Colar a chave e salvar

#### Passo 3: Mudar remote no servidor

```bash
# No servidor, navegar até o diretório do projeto
cd /caminho/do/projeto

# Verificar remote atual
git remote -v

# Mudar de HTTPS para SSH
git remote set-url origin git@github.com:pixel12digital/cfv-v1.git

# Verificar se mudou
git remote -v
# Deve mostrar: origin  git@github.com:pixel12digital/cfv-v1.git (fetch)
#              origin  git@github.com:pixel12digital/cfv-v1.git (push)

# Testar conexão
git fetch origin
```

#### Passo 4: Testar deployment

Agora o deployment deve funcionar, pois o SSH não precisa de credenciais interativas.

---

### ✅ Solução 2: Usar Personal Access Token (PAT)

Se não puder usar SSH, use um token de acesso pessoal do GitHub.

#### Passo 1: Criar Personal Access Token no GitHub

1. Acessar: https://github.com/settings/tokens
2. Clicar em "Generate new token" → "Generate new token (classic)"
3. Dar um nome: `deploy-producao`
4. Selecionar escopos: `repo` (acesso completo ao repositório)
5. Clicar em "Generate token"
6. **Copiar o token imediatamente** (não será mostrado novamente)

#### Passo 2: Configurar no servidor

**Opção A: Via URL com token (temporário)**

```bash
# No servidor
cd /caminho/do/projeto

# Substituir remote com token na URL
git remote set-url origin https://SEU_TOKEN@github.com/pixel12digital/cfv-v1.git

# Testar
git fetch origin
```

**Opção B: Via git credential helper (permanente)**

```bash
# No servidor
cd /caminho/do/projeto

# Configurar credential helper
git config --global credential.helper store

# Fazer um pull manual (vai pedir credenciais)
git pull origin master
# Username: pixel12digital
# Password: SEU_TOKEN_AQUI

# Agora o token está salvo e não precisará mais digitar
```

**Opção C: Via arquivo .git-credentials**

```bash
# No servidor
echo "https://SEU_TOKEN@github.com" > ~/.git-credentials
chmod 600 ~/.git-credentials

# Configurar git para usar
git config --global credential.helper store
```

---

### ✅ Solução 3: Usar Deploy Key (Alternativa)

Deploy keys são chaves SSH específicas para um único repositório.

#### Passo 1: Gerar chave SSH no servidor

```bash
# No servidor
ssh-keygen -t ed25519 -C "deploy-key-cfv-v1" -f ~/.ssh/deploy_key_cfv_v1
```

#### Passo 2: Adicionar como Deploy Key no GitHub

1. Copiar conteúdo de `~/.ssh/deploy_key_cfv_v1.pub`
2. Acessar: https://github.com/pixel12digital/cfv-v1/settings/keys
3. Clicar em "Add deploy key"
4. Colar a chave e marcar "Allow write access" (se necessário)
5. Salvar

#### Passo 3: Configurar SSH no servidor

```bash
# Criar/editar ~/.ssh/config
cat >> ~/.ssh/config << EOF
Host github-deploy
    HostName github.com
    User git
    IdentityFile ~/.ssh/deploy_key_cfv_v1
    IdentitiesOnly yes
EOF

chmod 600 ~/.ssh/config

# Mudar remote
cd /caminho/do/projeto
git remote set-url origin git@github-deploy:pixel12digital/cfv-v1.git

# Testar
git fetch origin
```

---

### ✅ Solução 4: Configurar no Painel de Deployment (Hostinger)

Se você está usando o painel de deployment da Hostinger:

1. Acessar o painel da Hostinger
2. Ir em "Deployment" ou "Git"
3. Verificar se há opção para configurar credenciais
4. Adicionar:
   - **Username:** `pixel12digital`
   - **Password/Token:** Seu Personal Access Token do GitHub
5. Salvar e tentar deployment novamente

---

## Verificação

Após configurar, teste:

```bash
# No servidor
cd /caminho/do/projeto

# Verificar remote
git remote -v

# Testar conexão
git fetch origin

# Se funcionar, fazer pull
git pull origin master
```

---

## Recomendação

**✅ Use a Solução 1 (SSH)** se possível, pois:
- Mais seguro
- Não expõe tokens
- Funciona automaticamente após configurado
- Padrão da indústria

**⚠️ Se não puder usar SSH**, use a Solução 2 (PAT) com credential helper.

---

## Troubleshooting

### Erro: "Permission denied (publickey)"
- Verificar se a chave SSH está adicionada no GitHub
- Verificar permissões: `chmod 600 ~/.ssh/id_ed25519`

### Erro: "Host key verification failed"
```bash
ssh-keyscan github.com >> ~/.ssh/known_hosts
```

### Erro: "Repository not found"
- Verificar se o token/chave tem acesso ao repositório
- Verificar se o nome do repositório está correto
