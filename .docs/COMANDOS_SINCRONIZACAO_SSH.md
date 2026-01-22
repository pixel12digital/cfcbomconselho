# 🔄 Comandos para Sincronização via SSH

## ✅ Status Atual

**Código local e produção estão sincronizados!**
- Commit local: `451c8d1`
- Commit produção: `451c8d1`
- Status: ✅ **IGUAIS**

## 📋 Comandos para Executar no Servidor (SSH)

### 1. Conectar ao Servidor
```bash
ssh usuario@servidor.com
# ou
ssh -p PORTA usuario@servidor.com
```

### 2. Navegar até o Diretório do Projeto
```bash
cd /home/usuario/public_html/painel
# ou o caminho onde está o projeto
```

### 3. Verificar Status Atual
```bash
git status
```

### 4. Fazer Fetch do Repositório
```bash
git fetch production
```

### 5. Verificar Diferenças
```bash
# Ver commits diferentes
git log HEAD..production/master --oneline

# Ver arquivos diferentes
git diff --name-status HEAD production/master

# Ver último commit em produção
git log production/master -1 --oneline
```

### 6. Fazer Pull (Atualizar Código)
```bash
git pull production master
```

### 7. Verificar Arquivos Específicos Alterados
```bash
# Verificar AuthController
git show production/master:app/Controllers/AuthController.php | grep -A 15 "showLogin"

# Verificar index.php
git show production/master:public_html/index.php | head -30
```

### 8. Se Houver Conflitos
```bash
# Ver arquivos em conflito
git status

# Resolver manualmente e depois:
git add arquivo_resolvido.php
git commit -m "Merge: resolve conflitos"
```

## 🚀 Comando Rápido (Tudo em Um)

```bash
cd /home/usuario/public_html/painel && \
git fetch production && \
git pull production master && \
git status
```

## 🔍 Verificar se Está Sincronizado

```bash
# Comparar commits
LOCAL=$(git rev-parse HEAD)
REMOTE=$(git rev-parse production/master)

if [ "$LOCAL" = "$REMOTE" ]; then
    echo "✅ Sincronizado!"
else
    echo "⚠️  Diferente - fazer pull"
fi
```

## 📝 Verificar Arquivos Específicos

```bash
# Ver se AuthController está igual
git diff production/master HEAD -- app/Controllers/AuthController.php

# Ver se index.php está igual
git diff production/master HEAD -- public_html/index.php

# Se não houver saída, os arquivos estão iguais
```

## ⚠️ Se o Git Pull Não Funcionar

### Opção 1: Verificar Permissões
```bash
# Verificar permissões do diretório .git
ls -la .git

# Se necessário, corrigir
chmod -R 755 .git
```

### Opção 2: Verificar Configuração do Git
```bash
# Ver remotes configurados
git remote -v

# Verificar se production está configurado
git remote show production
```

### Opção 3: Forçar Atualização (CUIDADO)
```bash
# Fazer backup primeiro
cp -r . ../backup-$(date +%Y%m%d)

# Resetar para produção
git fetch production
git reset --hard production/master
```

## 📊 Comparar Arquivos Específicos

### AuthController.php
```bash
# Ver versão em produção
git show production/master:app/Controllers/AuthController.php | grep -A 20 "showLogin"

# Ver versão local (no servidor)
grep -A 20 "showLogin" app/Controllers/AuthController.php
```

### public_html/index.php
```bash
# Ver versão em produção
git show production/master:public_html/index.php | head -30

# Ver versão local (no servidor)
head -30 public_html/index.php
```

## ✅ Checklist de Verificação

Execute estes comandos no servidor para garantir sincronização:

```bash
# 1. Status
git status

# 2. Fetch
git fetch production

# 3. Comparar commits
echo "Local:  $(git rev-parse HEAD)"
echo "Remoto: $(git rev-parse production/master)"

# 4. Ver diferenças
git diff --name-status HEAD production/master

# 5. Se houver diferenças, fazer pull
git pull production master

# 6. Verificar arquivos específicos
git diff production/master HEAD -- app/Controllers/AuthController.php
git diff production/master HEAD -- public_html/index.php
```

## 🎯 Resultado Esperado

Após executar os comandos, você deve ver:
- ✅ `git status` mostra "Your branch is up to date with 'production/master'"
- ✅ `git diff` não mostra diferenças
- ✅ Os commits são idênticos
