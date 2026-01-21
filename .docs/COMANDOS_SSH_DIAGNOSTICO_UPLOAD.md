# Comandos SSH para Diagnóstico do Upload de Logo

Execute estes comandos no servidor para diagnosticar o problema do upload:

## 1. Localizar estrutura do projeto e logs

```bash
# Verificar diretório atual
pwd

# Listar estrutura
ls -lah

# Verificar storage
ls -lah storage || true
ls -lah storage/logs || true

# Procurar logs de upload
find . -maxdepth 4 -type f -name "upload_logo.log" -o -name "*.log" | head -n 50
```

## 2. Criar diretório de logs se não existir

```bash
mkdir -p storage/logs
chmod 755 storage/logs
```

## 3. Verificar diretório de upload

```bash
# Ver arquivos no diretório de upload
ls -lah storage/uploads/cfcs | tail -n 20

# Ver arquivos criados nas últimas 24h
find storage/uploads/cfcs -type f -mtime -1 -ls | tail -n 20
```

## 4. Após tentar upload, verificar logs

```bash
# Ver log de upload (se existir)
tail -n 200 storage/logs/upload_logo.log 2>/dev/null || echo "Log não encontrado"

# Ver erros PHP (se existir)
tail -n 200 storage/logs/php_errors.log 2>/dev/null || echo "Log de erros não encontrado"

# Ver todos os logs recentes
find storage/logs -type f -mtime -1 -ls | tail -n 20
```

## 5. Verificar permissões

```bash
# Verificar permissões do diretório de upload
ls -ld storage/uploads/cfcs/

# Verificar permissões do diretório de logs
ls -ld storage/logs/
```

## 6. Testar acesso ao sw.js (após deploy)

```bash
# Testar se sw.js está acessível
curl -I https://painel.cfcbomconselho.com.br/public_html/sw.js

# Ver conteúdo (primeiras linhas)
curl -s https://painel.cfcbomconselho.com.br/public_html/sw.js | head -20
```
