# 🔧 Comando SSH para Resolver 404 - Auditoria PWA

## ⚡ Comando Único (Copiar e Colar)

```bash
cd ~ && pwd && git pull origin master 2>&1 | tail -5 && echo "" && echo "=== Verificando arquivo ===" && ls -la painel/public_html/tools/auditoria_pwa_executavel.php 2>&1 && echo "" && echo "=== Extraindo do git se necessário ===" && (test -f painel/public_html/tools/auditoria_pwa_executavel.php || (mkdir -p painel/public_html/tools/ && git show HEAD:public_html/tools/auditoria_pwa_executavel.php > painel/public_html/tools/auditoria_pwa_executavel.php && chmod 644 painel/public_html/tools/auditoria_pwa_executavel.php && echo "✅ Arquivo criado")) && echo "" && echo "=== Verificando acesso ===" && ls -la painel/public_html/tools/auditoria_pwa_executavel.php
```

## 📝 Passo a Passo (Se o comando único não funcionar)

### 1. Verificar onde você está
```bash
pwd
ls -la
```

### 2. Atualizar do git
```bash
git pull origin master
```

### 3. Verificar se arquivo existe no repositório
```bash
ls -la painel/public_html/tools/auditoria_pwa_executavel.php
```

### 4. Se não existir, extrair do commit
```bash
mkdir -p painel/public_html/tools/
git show HEAD:public_html/tools/auditoria_pwa_executavel.php > painel/public_html/tools/auditoria_pwa_executavel.php
chmod 644 painel/public_html/tools/auditoria_pwa_executavel.php
```

### 5. Verificar se foi criado
```bash
ls -la painel/public_html/tools/auditoria_pwa_executavel.php
```

### 6. Testar acesso
```bash
curl -I https://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php
```

## 🔍 Verificar DocumentRoot Real

O servidor pode estar apontando para outro diretório. Verificar:

```bash
# Verificar estrutura Hostinger comum
ls -la ~/public_html/
ls -la ~/domains/painel.cfcbomconselho.com.br/public_html/

# Se o DocumentRoot for diferente, copiar arquivo para lá também
cp painel/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/ 2>&1
# OU
cp painel/public_html/tools/auditoria_pwa_executavel.php ~/domains/painel.cfcbomconselho.com.br/public_html/tools/ 2>&1
```

## ⚠️ Se Ainda Der 404

Verificar se .htaccess está bloqueando:

```bash
# Ver regras do .htaccess
grep -i "tools\|RewriteRule" painel/public_html/.htaccess

# Verificar se router está bloqueando
grep -i "tools" painel/public_html/index.php
```

## ✅ Solução Definitiva

Se o DocumentRoot aponta para `~/painel/public_html/`, o arquivo já deve estar acessível após o git pull. Se apontar para outro lugar, copie o arquivo:

```bash
# Descobrir DocumentRoot real
# (pode variar conforme configuração Hostinger)

# Opção 1: Se for ~/public_html/
mkdir -p ~/public_html/tools/
cp painel/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/

# Opção 2: Se for ~/domains/painel.cfcbomconselho.com.br/public_html/
mkdir -p ~/domains/painel.cfcbomconselho.com.br/public_html/tools/
cp painel/public_html/tools/auditoria_pwa_executavel.php ~/domains/painel.cfcbomconselho.com.br/public_html/tools/
```
