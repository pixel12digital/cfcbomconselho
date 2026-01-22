# 🔄 Sincronização de Código com Produção

## 🎯 Problema

O código no servidor de produção pode não estar sincronizado com o repositório remoto após fazer `git push`. É necessário fazer `git pull` no servidor para atualizar o código.

## ✅ Soluções

### 1. Script de Sincronização Automática

Foram criados scripts para facilitar a sincronização:

**Linux/Mac:**
```bash
chmod +x tools/sync-producao.sh
./tools/sync-producao.sh
```

**Windows (PowerShell):**
```powershell
.\tools\sync-producao.ps1
```

### 2. Sincronização Manual via SSH

**Passo a passo:**

1. **Conectar ao servidor via SSH:**
   ```bash
   ssh usuario@servidor.com
   ```

2. **Navegar até o diretório do projeto:**
   ```bash
   cd /caminho/para/o/projeto
   # Exemplo: cd /home/usuario/public_html/painel
   ```

3. **Verificar status atual:**
   ```bash
   git status
   ```

4. **Fazer fetch do repositório:**
   ```bash
   git fetch production
   ```

5. **Verificar diferenças:**
   ```bash
   git log HEAD..production/master --oneline
   git diff --name-status HEAD production/master
   ```

6. **Fazer pull (se houver atualizações):**
   ```bash
   git pull production master
   ```

7. **Verificar se há conflitos:**
   ```bash
   git status
   ```

### 3. Verificar Sincronização

**Comparar commits:**
```bash
# Ver último commit local
git log -1 --oneline

# Ver último commit em produção
git log production/master -1 --oneline

# Comparar
git log HEAD..production/master --oneline  # Commits em produção que não estão local
git log production/master..HEAD --oneline   # Commits locais que não estão em produção
```

**Comparar arquivos específicos:**
```bash
# Ver diferenças em um arquivo
git diff production/master HEAD -- app/Controllers/AuthController.php

# Ver status de todos os arquivos
git diff --name-status HEAD production/master
```

### 4. Resolver Conflitos (se houver)

Se houver conflitos ao fazer pull:

1. **Ver arquivos em conflito:**
   ```bash
   git status
   ```

2. **Abrir arquivos com conflito e resolver manualmente**

3. **Adicionar arquivos resolvidos:**
   ```bash
   git add arquivo_resolvido.php
   ```

4. **Finalizar merge:**
   ```bash
   git commit -m "Merge: resolve conflitos com produção"
   ```

### 5. Garantir que Código Local = Produção

**Forçar sincronização (CUIDADO: isso descarta mudanças locais):**
```bash
# 1. Fazer backup das mudanças locais (se houver)
git stash

# 2. Fazer reset para o estado de produção
git fetch production
git reset --hard production/master

# 3. Verificar
git status
```

**Ou fazer merge mantendo mudanças locais:**
```bash
# 1. Fazer pull com merge
git pull production master

# 2. Resolver conflitos se houver
# 3. Commit
git commit -m "Merge: sincroniza com produção"
```

## 🔍 Verificação de Arquivos Específicos

**Verificar se arquivo específico está igual:**
```bash
# Comparar conteúdo
git diff production/master HEAD -- caminho/do/arquivo.php

# Ver conteúdo em produção
git show production/master:caminho/do/arquivo.php

# Ver conteúdo local
cat caminho/do/arquivo.php
```

## 📋 Checklist de Sincronização

- [ ] Conectar ao servidor via SSH
- [ ] Navegar até o diretório do projeto
- [ ] Verificar status: `git status`
- [ ] Fazer fetch: `git fetch production`
- [ ] Verificar diferenças: `git log HEAD..production/master`
- [ ] Fazer pull: `git pull production master`
- [ ] Verificar se há conflitos: `git status`
- [ ] Resolver conflitos (se houver)
- [ ] Verificar arquivos específicos alterados
- [ ] Testar aplicação após sincronização

## ⚠️ Importante

1. **Sempre fazer backup antes de forçar sincronização**
2. **Verificar diferenças antes de fazer pull**
3. **Testar aplicação após sincronização**
4. **Manter logs de alterações para facilitar troubleshooting**

## 🚀 Comandos Rápidos

```bash
# Sincronização rápida (assumindo que não há conflitos)
git fetch production && git pull production master

# Verificar se está sincronizado
git rev-parse HEAD == $(git rev-parse production/master) && echo "Sincronizado" || echo "Diferente"

# Ver diferenças em arquivos específicos
git diff production/master HEAD -- app/Controllers/AuthController.php public_html/index.php
```
