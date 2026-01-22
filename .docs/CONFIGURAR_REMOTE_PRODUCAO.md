# 🔧 Configurar Remote "production" no Servidor

## 🎯 Problema

No servidor de produção, o remote `production` não está configurado. O servidor só tem `origin/master`, mas precisamos do remote `production` para sincronizar.

## ✅ Solução

### Opção 1: Adicionar Remote "production" no Servidor

Execute no servidor via SSH:

```bash
# 1. Verificar remotes atuais
git remote -v

# 2. Adicionar remote "production"
git remote add production https://github.com/pixel12digital/cfcbomconselho.git

# 3. Verificar se foi adicionado
git remote -v

# 4. Fazer fetch do production
git fetch production

# 5. Verificar branches remotos
git branch -r

# 6. Fazer pull
git pull production master
```

### Opção 2: Usar "origin" (se já estiver configurado)

Se o servidor já tem `origin` apontando para o repositório correto:

```bash
# 1. Verificar qual repositório o origin aponta
git remote show origin

# 2. Se estiver correto, usar origin ao invés de production
git fetch origin
git pull origin master
```

### Opção 3: Atualizar Remote Existente

Se o `origin` já existe mas aponta para lugar errado:

```bash
# 1. Ver remotes
git remote -v

# 2. Atualizar origin para apontar para produção
git remote set-url origin https://github.com/pixel12digital/cfcbomconselho.git

# 3. Verificar
git remote -v

# 4. Fazer fetch e pull
git fetch origin
git pull origin master
```

## 📋 Comandos Completos para Executar no Servidor

```bash
# 1. Verificar remotes atuais
git remote -v

# 2. Se não tiver "production", adicionar:
git remote add production https://github.com/pixel12digital/cfcbomconselho.git

# 3. Fazer fetch
git fetch production

# 4. Verificar branches
git branch -r

# 5. Verificar último commit em produção
git log production/master -1 --oneline

# 6. Verificar último commit local
git log HEAD -1 --oneline

# 7. Ver diferenças
git diff --name-status HEAD production/master

# 8. Fazer pull
git pull production master

# 9. Verificar status final
git status
```

## 🔍 Verificar se Está Configurado Corretamente

```bash
# Ver todos os remotes
git remote -v

# Deve mostrar algo como:
# origin    https://github.com/pixel12digital/cfv-v1.git (fetch)
# origin    https://github.com/pixel12digital/cfv-v1.git (push)
# production    https://github.com/pixel12digital/cfcbomconselho.git (fetch)
# production    https://github.com/pixel12digital/cfcbomconselho.git (push)
```

## ⚠️ Se Der Erro de Permissão

Se der erro de autenticação, pode ser necessário usar SSH:

```bash
# Adicionar remote com SSH
git remote add production git@github.com:pixel12digital/cfcbomconselho.git

# Ou atualizar URL existente
git remote set-url production git@github.com:pixel12digital/cfcbomconselho.git
```

## 🚀 Script Completo

Execute este script no servidor:

```bash
#!/bin/bash

echo "🔧 Configurando remote production..."

# Verificar se production já existe
if git remote | grep -q "^production$"; then
    echo "✅ Remote 'production' já existe"
    git remote -v | grep production
else
    echo "➕ Adicionando remote 'production'..."
    git remote add production https://github.com/pixel12digital/cfcbomconselho.git
    echo "✅ Remote 'production' adicionado"
fi

echo "📥 Fazendo fetch..."
git fetch production

echo "📊 Verificando status..."
echo "Local:  $(git rev-parse HEAD)"
echo "Remoto: $(git rev-parse production/master 2>/dev/null || echo 'N/A')"

echo "🔄 Fazendo pull..."
git pull production master

echo "✅ Concluído!"
```
