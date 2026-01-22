# Comandos SSH para Ver Logs de Upload

Como o script web não está acessível, use estes comandos SSH para verificar os logs:

## 1. Verificar estrutura e localizar arquivo

```bash
# Verificar diretório atual
pwd

# Verificar se arquivo existe
ls -lah public_html/tools/ver_log_upload.php

# Procurar arquivo em todo o projeto
find . -name "ver_log_upload.php" 2>/dev/null

# Ver estrutura do tools
ls -lah public_html/tools/
```

## 2. Ver logs diretamente (MÉTODO PRINCIPAL)

```bash
# Ver log completo
cat storage/logs/upload_logo.log

# Ver últimas 50 linhas
tail -n 50 storage/logs/upload_logo.log

# Ver se arquivo existe
ls -lah storage/logs/upload_logo.log

# Se não existir, verificar diretório
ls -lah storage/logs/
```

## 3. Verificar se método está sendo chamado

```bash
# Ver erros PHP relacionados
tail -n 200 storage/logs/php_errors.log | grep -i "upload\|logo\|configuracoes\|uploadLogo" || echo "Nenhum erro relacionado"

# Ver todos os erros recentes
tail -n 50 storage/logs/php_errors.log
```

## 4. Testar se arquivo foi criado após upload

```bash
# Ver arquivos no diretório de upload
ls -lah storage/uploads/cfcs/

# Ver arquivos criados nas últimas 24h
find storage/uploads/cfcs -type f -mtime -1 -ls

# Ver permissões
stat -c "%a %U %G" storage/uploads/cfcs storage/logs
```

## 5. Verificar se git pull trouxe o arquivo

```bash
# Fazer pull
git pull origin master

# Verificar se arquivo existe no git
git ls-files | grep ver_log_upload.php

# Ver se arquivo foi commitado
git log --oneline --all | grep ver_log_upload
```
