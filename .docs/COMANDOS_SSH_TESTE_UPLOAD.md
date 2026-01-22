# Comandos SSH para Teste do Upload de Logo

Execute estes comandos **ANTES e DEPOIS** de tentar fazer upload:

## 1. Verificar permissões (ANTES do teste)

```bash
# Ver permissões dos diretórios
ls -ld storage/uploads storage/uploads/cfcs storage/logs

# Ver permissões detalhadas (formato: permissões usuário grupo arquivo)
stat -c "%a %U %G %n" storage/uploads/cfcs storage/logs

# Se necessário, ajustar permissões (TESTE TEMPORÁRIO)
chmod 775 storage/uploads storage/uploads/cfcs storage/logs
```

## 2. Limpar log anterior (opcional, para facilitar leitura)

```bash
# Limpar log anterior (se existir)
> storage/logs/upload_logo.log 2>/dev/null || true
```

## 3. Verificar estado ANTES do upload

```bash
# Ver se diretório de upload está vazio
ls -lah storage/uploads/cfcs

# Ver se há arquivos recentes
find storage/uploads/cfcs -type f -mtime -1 -ls
```

## 4. Fazer upload no navegador

1. Acesse: `https://painel.cfcbomconselho.com.br/configuracoes/cfc`
2. Selecione um arquivo de logo
3. Clique em "Fazer Upload"

## 5. Verificar estado DEPOIS do upload

```bash
# Ver se arquivo foi criado
ls -lah storage/uploads/cfcs

# Ver arquivos criados nas últimas 24h
find storage/uploads/cfcs -type f -mtime -1 -ls

# Ver log de upload (DEVE existir agora)
cat storage/logs/upload_logo.log

# Ver últimas linhas do log
tail -n 50 storage/logs/upload_logo.log

# Ver erros PHP (se houver)
tail -n 50 storage/logs/php_errors.log | grep -i upload || echo "Nenhum erro de upload encontrado"
```

## 6. Verificar permissões do usuário PHP

```bash
# Ver qual usuário está rodando PHP (via arquivo temporário)
echo '<?php echo get_current_user() . "\n"; echo posix_getuid() . "\n"; echo posix_geteuid() . "\n";' > public_html/test_php_user.php
php public_html/test_php_user.php
rm public_html/test_php_user.php

# Ver owner dos diretórios
stat -c "%U %G" storage/uploads/cfcs storage/logs
```

## 7. Teste de escrita direta (se upload falhar)

```bash
# Tentar criar arquivo diretamente (simular upload)
echo "test" > storage/uploads/cfcs/test_$(date +%s).txt
ls -lah storage/uploads/cfcs/test_*.txt 2>/dev/null || echo "Erro ao criar arquivo de teste"

# Remover arquivo de teste
rm -f storage/uploads/cfcs/test_*.txt
```

---

## Interpretação dos Resultados

### Se o log NÃO for criado:
- O método `uploadLogo()` não está sendo chamado
- Verificar rota e action do form

### Se o log mostrar `FILES_count: 0`:
- O form não está enviando multipart corretamente
- Verificar `enctype="multipart/form-data"` no form

### Se o log mostrar `has_logo_in_FILES: false`:
- O name do input não corresponde ao esperado
- Verificar se input tem `name="logo"`

### Se o arquivo não aparecer em `storage/uploads/cfcs`:
- Verificar permissões (deve ser 775 ou 777 para teste)
- Verificar se usuário PHP tem permissão de escrita
- Verificar logs de erro do PHP
