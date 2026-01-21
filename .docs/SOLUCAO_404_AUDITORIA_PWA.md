# 🔧 Solução: 404 Auditoria PWA - Arquivo não encontrado

## Problema Identificado
```
ls: cannot access '/home/u502697186/public_html/tools/auditoria_pwa_executavel.php': No such file or directory
```

## Comandos SSH para Resolver

### 1. Verificar onde está o projeto
```bash
# Você está em ~/painel, verificar estrutura
pwd
ls -la

# Verificar se há diretório public_html
ls -la ~/public_html/

# Verificar estrutura do projeto atual
ls -la ~/painel/
```

### 2. Verificar se arquivo existe no repositório local
```bash
# Se projeto está em ~/painel
cd ~/painel
ls -la public_html/tools/auditoria_pwa_executavel.php

# Ou verificar em outros locais possíveis
find ~ -name "auditoria_pwa_executavel.php" 2>/dev/null
```

### 3. Fazer git pull para atualizar
```bash
cd ~/painel
git pull origin master

# Verificar se arquivo foi baixado
ls -la public_html/tools/auditoria_pwa_executavel.php
```

### 4. Copiar arquivo para local correto (se necessário)
```bash
# Se arquivo existe no projeto mas não em public_html/tools/
# Opção A: Copiar
cp ~/painel/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/

# Opção B: Criar link simbólico
ln -s ~/painel/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/auditoria_pwa_executavel.php

# Verificar se copiou
ls -la ~/public_html/tools/auditoria_pwa_executavel.php
```

### 5. Criar diretório tools se não existir
```bash
# Criar diretório se não existir
mkdir -p ~/public_html/tools/
chmod 755 ~/public_html/tools/

# Depois copiar arquivo
cp ~/painel/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/
chmod 644 ~/public_html/tools/auditoria_pwa_executavel.php
```

### 6. Verificar estrutura completa
```bash
# Ver onde está o DocumentRoot do servidor
echo "DocumentRoot atual: $(pwd)"
echo ""
echo "Estrutura ~/painel:"
ls -la ~/painel/ | head -20
echo ""
echo "Estrutura ~/public_html:"
ls -la ~/public_html/ 2>&1 | head -20
```

---

## 🎯 Comando Completo (Copiar e Colar)

```bash
cd ~/painel && echo "=== DIAGNÓSTICO COMPLETO ===" && echo "" && echo "1. Verificando projeto atual:" && pwd && ls -la | head -10 && echo "" && echo "2. Verificando se arquivo existe no projeto:" && ls -la public_html/tools/auditoria_pwa_executavel.php 2>&1 && echo "" && echo "3. Verificando git status:" && git status 2>&1 | head -5 && echo "" && echo "4. Fazendo git pull:" && git pull origin master 2>&1 && echo "" && echo "5. Verificando novamente:" && ls -la public_html/tools/auditoria_pwa_executavel.php 2>&1 && echo "" && echo "6. Criando diretório tools se necessário:" && mkdir -p ~/public_html/tools/ && echo "7. Copiando arquivo:" && cp public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/ 2>&1 && echo "8. Verificando cópia:" && ls -la ~/public_html/tools/auditoria_pwa_executavel.php 2>&1
```

---

## 📝 Solução Passo a Passo

### Passo 1: Verificar estrutura
```bash
cd ~/painel
pwd
ls -la public_html/tools/
```

### Passo 2: Atualizar repositório
```bash
cd ~/painel
git pull origin master
```

### Passo 3: Verificar se arquivo existe
```bash
ls -la public_html/tools/auditoria_pwa_executavel.php
```

### Passo 4: Copiar para public_html (se necessário)
```bash
# Criar diretório se não existir
mkdir -p ~/public_html/tools/

# Copiar arquivo
cp ~/painel/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/

# Dar permissões corretas
chmod 644 ~/public_html/tools/auditoria_pwa_executavel.php
```

### Passo 5: Verificar acesso
```bash
# Testar se arquivo está acessível
curl -I https://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php
```

---

## ⚠️ Possíveis Estruturas do Servidor

O servidor pode ter diferentes estruturas:

### Estrutura A: Projeto em ~/painel, public_html separado
```bash
~/painel/public_html/tools/auditoria_pwa_executavel.php  # Arquivo aqui
~/public_html/tools/                                       # Copiar para cá
```

### Estrutura B: public_html é symlink
```bash
# Verificar se public_html é link
ls -la ~/public_html

# Se for link, arquivo pode estar em outro lugar
readlink ~/public_html
```

### Estrutura C: DocumentRoot aponta para ~/painel/public_html
```bash
# Neste caso, arquivo já deve estar acessível em:
# https://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php
# Mas pode precisar ajustar .htaccess
```

---

## 🔍 Verificar Configuração do Servidor

```bash
# Ver onde está o DocumentRoot configurado
# (pode variar conforme servidor)

# Ver se há .htaccess bloqueando
cat ~/public_html/.htaccess | grep -i "tools"

# Ver se há router bloqueando
cat ~/painel/public_html/index.php | grep -i "tools"
```

---

**Execute o comando completo acima primeiro para diagnóstico automático!**
