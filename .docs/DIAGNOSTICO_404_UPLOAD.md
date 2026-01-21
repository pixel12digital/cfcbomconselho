# 🔍 Diagnóstico: 404 pwa-manifest.php e Upload não salva

**Data:** 2026-01-21  
**Status:** Em investigação

## Problemas Reportados

1. ❌ `pwa-manifest.php` retorna **404** em produção
2. ❌ Upload de logo **não está salvando** a imagem

---

## 1️⃣ Problema: pwa-manifest.php retorna 404

### Análise

O erro indica que o arquivo não está sendo encontrado ou o caminho está incorreto.

### Possíveis Causas

1. **Arquivo não foi deployado** - O arquivo `public_html/pwa-manifest.php` não existe no servidor
2. **Caminho incorreto** - O `base_path()` está gerando um caminho errado
3. **`.htaccess` bloqueando** - Regras de rewrite podem estar impedindo o acesso

### Soluções Implementadas

#### ✅ A. Adicionada regra no `.htaccess`

```apache
# Permitir acesso direto ao pwa-manifest.php (PWA white-label)
RewriteRule ^pwa-manifest\.php$ - [L]
```

Isso garante que o arquivo seja servido diretamente, sem passar pelo front controller.

#### ✅ B. Script de diagnóstico criado

Arquivo: `public_html/test-manifest-path.php`

Acesse via: `https://painel.cfcbomconselho.com.br/test-manifest-path.php`

Este script mostra:
- Caminhos calculados pelo `base_path()`
- Se o arquivo existe fisicamente
- URLs de teste

### Próximos Passos

1. **Verificar se o arquivo existe no servidor:**
   ```bash
   # Via SSH ou File Manager
   ls -la public_html/pwa-manifest.php
   ```

2. **Testar o script de diagnóstico:**
   - Acesse: `https://painel.cfcbomconselho.com.br/test-manifest-path.php`
   - Verifique os caminhos retornados

3. **Se o arquivo não existir:**
   - Fazer deploy do arquivo `public_html/pwa-manifest.php`
   - Verificar se está na pasta correta

4. **Se o caminho estiver errado:**
   - Ajustar `base_path()` ou usar caminho absoluto no `shell.php`

---

## 2️⃣ Problema: Upload de logo não salva

### Análise

O upload não está salvando a imagem, mesmo sem erros aparentes.

### Possíveis Causas

1. **Permissões do diretório** - `storage/uploads/cfcs/` não é gravável
2. **Espaço em disco** - Servidor sem espaço
3. **Erro silencioso** - `move_uploaded_file()` falhando sem mensagem
4. **Configuração PHP** - `upload_max_filesize` ou `post_max_size` muito baixos

### Soluções Implementadas

#### ✅ A. Logs detalhados adicionados

O método `uploadLogo()` agora grava logs em:
```
storage/logs/upload_logo.log
```

O log inclui:
- Caminho do diretório de upload
- Se o diretório existe e é gravável
- Caminho completo do arquivo
- Tamanho do arquivo
- Código de erro do upload
- Resultado da operação

#### ✅ B. Validações melhoradas

- Verificação de espaço em disco
- Verificação de permissões antes do upload
- Mensagens de erro mais detalhadas

#### ✅ C. Script de diagnóstico criado

Arquivo: `public_html/tools/diagnostico_upload_logo.php`

Acesse via: `https://painel.cfcbomconselho.com.br/tools/diagnostico_upload_logo.php`

Este script mostra:
- Estrutura de diretórios
- Configurações PHP (upload_max_filesize, etc.)
- Extensões necessárias (GD, fileinfo)
- CFC atual e logo existente
- Teste de escrita
- Permissões

### Próximos Passos

1. **Acessar o script de diagnóstico:**
   - URL: `https://painel.cfcbomconselho.com.br/tools/diagnostico_upload_logo.php`
   - Verificar todas as seções, especialmente:
     - Seção 1: Estrutura de Diretórios
     - Seção 5: Teste de Escrita

2. **Verificar o log de upload:**
   - Via SSH ou File Manager, abra: `storage/logs/upload_logo.log`
   - Procure por erros ou informações sobre o último upload

3. **Verificar permissões:**
   ```bash
   # Via SSH
   chmod 755 storage/uploads/cfcs/
   chmod 644 storage/logs/upload_logo.log
   ```

4. **Se o diretório não existir:**
   ```bash
   # Via SSH
   mkdir -p storage/uploads/cfcs/
   chmod 755 storage/uploads/cfcs/
   ```

5. **Testar upload novamente:**
   - Após corrigir permissões, tentar fazer upload novamente
   - Verificar o log após o upload

---

## 📋 Checklist de Verificação

### Para pwa-manifest.php (404)

- [ ] Arquivo `public_html/pwa-manifest.php` existe no servidor
- [ ] Permissões do arquivo: `644` ou `755`
- [ ] Regra no `.htaccess` está ativa
- [ ] Script de diagnóstico mostra caminho correto
- [ ] `base_path('pwa-manifest.php')` retorna caminho válido

### Para Upload de Logo

- [ ] Diretório `storage/uploads/cfcs/` existe
- [ ] Permissões do diretório: `755`
- [ ] Diretório é gravável (teste de escrita passou)
- [ ] `upload_max_filesize >= 5M` no PHP
- [ ] `post_max_size >= 5M` no PHP
- [ ] Extensão GD está habilitada
- [ ] Extensão fileinfo está habilitada
- [ ] Espaço em disco disponível
- [ ] Log de upload mostra sucesso ou erro específico

---

## 🔧 Comandos Úteis (SSH)

```bash
# Verificar se arquivo existe
ls -la public_html/pwa-manifest.php

# Verificar permissões
ls -la storage/uploads/cfcs/

# Criar diretório se não existir
mkdir -p storage/uploads/cfcs/
chmod 755 storage/uploads/cfcs/

# Verificar espaço em disco
df -h

# Verificar configurações PHP
php -i | grep upload_max_filesize
php -i | grep post_max_size

# Ver log de upload
tail -f storage/logs/upload_logo.log
```

---

## 📝 Notas

- Os scripts de diagnóstico (`test-manifest-path.php` e `diagnostico_upload_logo.php`) devem ser removidos após a resolução dos problemas por questões de segurança.
- O log de upload (`storage/logs/upload_logo.log`) pode ser limpo periodicamente para economizar espaço.
