# 🔧 Comandos SSH - Verificar Estrutura e Criar Diretório Tools

## Problema Identificado
- Diretório `~/painel/public_html/tools/` não existe
- Arquivo retorna 404
- Servidor está respondendo (PHP 8.2.29, LiteSpeed)

## Comandos para Resolver

### 1. Verificar estrutura atual do projeto
```bash
cd ~/painel
pwd
ls -la
```

### 2. Verificar se public_html existe
```bash
ls -la ~/painel/public_html/
```

### 3. Verificar onde está o DocumentRoot (pode ser diferente)
```bash
# Verificar estrutura comum do Hostinger
ls -la ~/public_html/
ls -la ~/domains/painel.cfcbomconselho.com.br/public_html/
```

### 4. Fazer git pull para atualizar
```bash
cd ~/painel
git pull origin master
```

### 5. Verificar se arquivo existe no repositório local
```bash
cd ~/painel
find . -name "auditoria_pwa_executavel.php" 2>/dev/null
```

### 6. Criar diretório tools
```bash
# Criar em ~/painel/public_html/tools/
mkdir -p ~/painel/public_html/tools/
chmod 755 ~/painel/public_html/tools/

# OU criar em ~/public_html/tools/ (se DocumentRoot for diferente)
mkdir -p ~/public_html/tools/
chmod 755 ~/public_html/tools/
```

### 7. Verificar se arquivo foi commitado no git
```bash
cd ~/painel
git log --oneline --all | grep -i "auditoria\|pwa" | head -5
git show HEAD:public_html/tools/auditoria_pwa_executavel.php | head -10
```

### 8. Se arquivo não existe no git local, criar manualmente
```bash
# Criar arquivo diretamente (se git pull não trouxe)
cd ~/painel/public_html/tools/
# Ou copiar do repositório remoto
```

---

## 🎯 Comando Completo de Diagnóstico e Criação

```bash
cd ~/painel && echo "=== DIAGNÓSTICO ===" && echo "1. Estrutura atual:" && pwd && ls -la | head -10 && echo "" && echo "2. Verificando public_html:" && ls -la public_html/ 2>&1 | head -10 && echo "" && echo "3. Fazendo git pull:" && git pull origin master 2>&1 && echo "" && echo "4. Procurando arquivo:" && find . -name "auditoria_pwa_executavel.php" 2>/dev/null && echo "" && echo "5. Criando diretório tools:" && mkdir -p public_html/tools/ && chmod 755 public_html/tools/ && echo "6. Verificando se arquivo existe no git:" && git ls-files | grep auditoria_pwa_executavel.php && echo "" && echo "7. Verificando último commit:" && git log --oneline -1
```

---

## 📝 Solução Passo a Passo

### Passo 1: Verificar estrutura
```bash
cd ~/painel
pwd
ls -la
ls -la public_html/ 2>&1
```

### Passo 2: Atualizar repositório
```bash
cd ~/painel
git pull origin master
```

### Passo 3: Verificar se arquivo está no repositório
```bash
git ls-files | grep auditoria
git show HEAD:public_html/tools/auditoria_pwa_executavel.php > /dev/null 2>&1 && echo "Arquivo existe no git" || echo "Arquivo NÃO existe no git"
```

### Passo 4: Criar diretório tools
```bash
mkdir -p ~/painel/public_html/tools/
chmod 755 ~/painel/public_html/tools/
```

### Passo 5: Se arquivo não existe, verificar commit
```bash
git log --oneline --all | head -5
git show 9f7f679:public_html/tools/auditoria_pwa_executavel.php > ~/painel/public_html/tools/auditoria_pwa_executavel.php 2>&1
```

### Passo 6: Verificar permissões
```bash
ls -la ~/painel/public_html/tools/auditoria_pwa_executavel.php
chmod 644 ~/painel/public_html/tools/auditoria_pwa_executavel.php
```

### Passo 7: Testar acesso
```bash
curl -I https://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php
```

---

## 🔍 Verificar DocumentRoot Real

O DocumentRoot pode estar em outro lugar. Verificar:

```bash
# Hostinger geralmente usa:
ls -la ~/domains/painel.cfcbomconselho.com.br/public_html/

# Ou verificar configuração do servidor
# (pode variar conforme Hostinger)
```

---

## ⚡ Solução Rápida (Se arquivo está no git)

```bash
cd ~/painel && git pull origin master && mkdir -p public_html/tools/ && git show 9f7f679:public_html/tools/auditoria_pwa_executavel.php > public_html/tools/auditoria_pwa_executavel.php && chmod 644 public_html/tools/auditoria_pwa_executavel.php && ls -la public_html/tools/auditoria_pwa_executavel.php
```

Este comando:
1. Atualiza o repositório
2. Cria o diretório tools
3. Extrai o arquivo do commit específico (9f7f679)
4. Define permissões
5. Verifica se foi criado
