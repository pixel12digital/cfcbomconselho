# 🔧 Comandos SSH para Diagnóstico PWA

## 1. Verificar se o arquivo existe no servidor

```bash
# Verificar se o arquivo de auditoria existe
ls -la /caminho/para/public_html/tools/auditoria_pwa_executavel.php

# Ou se estiver na raiz do projeto
ls -la ~/public_html/tools/auditoria_pwa_executavel.php

# Verificar estrutura de diretórios
ls -la ~/public_html/tools/
```

## 2. Verificar permissões do arquivo

```bash
# Ver permissões atuais
ls -l ~/public_html/tools/auditoria_pwa_executavel.php

# Dar permissão de leitura (se necessário)
chmod 644 ~/public_html/tools/auditoria_pwa_executavel.php

# Ou permissão de execução (se necessário)
chmod 755 ~/public_html/tools/auditoria_pwa_executavel.php
```

## 3. Verificar se o diretório tools existe

```bash
# Verificar se diretório existe
ls -la ~/public_html/tools/

# Criar diretório se não existir
mkdir -p ~/public_html/tools/
chmod 755 ~/public_html/tools/
```

## 4. Verificar .htaccess (pode estar bloqueando)

```bash
# Ver conteúdo do .htaccess
cat ~/public_html/.htaccess

# Verificar se há regras bloqueando /tools/
grep -i "tools" ~/public_html/.htaccess
```

## 5. Testar acesso direto via PHP CLI

```bash
# Executar script diretamente via CLI (para testar se funciona)
php ~/public_html/tools/auditoria_pwa_executavel.php

# Ou se estiver no diretório
cd ~/public_html/tools/
php auditoria_pwa_executavel.php
```

## 6. Verificar logs de erro do Apache/Nginx

```bash
# Apache - últimos erros
tail -n 50 /var/log/apache2/error.log

# Ou se for outro caminho
tail -n 50 /var/log/httpd/error_log

# Nginx - últimos erros
tail -n 50 /var/log/nginx/error.log

# Filtrar por 404
grep "404" /var/log/apache2/error.log | tail -n 20
```

## 7. Verificar se arquivo foi enviado (após git pull)

```bash
# Ir para diretório do projeto
cd ~/caminho/do/projeto

# Verificar se arquivo existe localmente
ls -la public_html/tools/auditoria_pwa_executavel.php

# Se não existir, fazer git pull
git pull origin master

# Verificar novamente
ls -la public_html/tools/auditoria_pwa_executavel.php
```

## 8. Verificar configuração do servidor web

```bash
# Ver configuração do Apache (se aplicável)
apache2ctl -S

# Ver se mod_rewrite está habilitado
apache2ctl -M | grep rewrite

# Ver configuração do PHP
php -v
php -m | grep gd
```

## 9. Testar acesso via curl (simular navegador)

```bash
# Testar acesso HTTP
curl -I http://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php

# Testar acesso HTTPS
curl -I https://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php

# Ver resposta completa
curl -v https://painel.cfcbomconselho.com.br/tools/auditoria_pwa_executavel.php
```

## 10. Comando completo de diagnóstico (copiar e colar)

```bash
#!/bin/bash
echo "=== DIAGNÓSTICO AUDITORIA PWA ==="
echo ""
echo "1. Verificando arquivo..."
ls -la ~/public_html/tools/auditoria_pwa_executavel.php 2>&1
echo ""
echo "2. Verificando diretório tools..."
ls -la ~/public_html/tools/ 2>&1
echo ""
echo "3. Verificando permissões..."
ls -l ~/public_html/tools/auditoria_pwa_executavel.php 2>&1
echo ""
echo "4. Verificando .htaccess..."
grep -i "tools" ~/public_html/.htaccess 2>&1
echo ""
echo "5. Testando PHP CLI..."
php ~/public_html/tools/auditoria_pwa_executavel.php 2>&1 | head -n 20
echo ""
echo "=== FIM DIAGNÓSTICO ==="
```

## 11. Solução rápida (se arquivo não existe)

```bash
# Se arquivo não existe, criar manualmente
# Primeiro, verificar se está no repositório local
cd ~/caminho/do/projeto
git pull origin master

# Copiar arquivo se necessário
cp public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/

# Ou criar link simbólico (se projeto está em outro lugar)
ln -s ~/caminho/do/projeto/public_html/tools/auditoria_pwa_executavel.php ~/public_html/tools/
```

## 12. Verificar estrutura de URLs (pode ser problema de roteamento)

```bash
# Ver se há router bloqueando /tools/
cat ~/public_html/index.php | grep -i "tools"

# Ver rotas definidas
cat ~/app/routes/web.php | grep -i "tools"
```

---

## 🎯 Comando Recomendado para Começar

Execute este comando primeiro para diagnóstico completo:

```bash
cd ~ && echo "=== DIAGNÓSTICO ===" && echo "Arquivo existe?" && ls -la public_html/tools/auditoria_pwa_executavel.php 2>&1 && echo "" && echo "Diretório tools:" && ls -la public_html/tools/ 2>&1 && echo "" && echo "Permissões:" && ls -l public_html/tools/auditoria_pwa_executavel.php 2>&1
```

---

**Nota:** Ajuste os caminhos (`~/public_html/` ou `/var/www/` ou outro) conforme a estrutura do seu servidor.
