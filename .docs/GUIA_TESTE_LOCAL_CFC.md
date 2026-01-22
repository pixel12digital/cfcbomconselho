# Guia de Teste Local - Configurações CFC

## Pré-requisitos

1. **XAMPP rodando** (Apache + MySQL)
2. **Banco de dados local configurado** com a migration `034_add_logo_path_to_cfcs.sql` executada
3. **Usuário ADMIN logado** no sistema local

## Passo 1: Verificar se está logado como ADMIN

1. Acesse: `http://localhost/cfc-v.1/public_html/`
2. Faça login com um usuário que tenha role `ADMIN`
3. Verifique no menu lateral se aparece o item **"CFC"** (deve estar após "Cursos Teóricos" e antes de "Configurações")

## Passo 2: Acessar a página de Configurações CFC

1. No menu lateral, clique em **"CFC"**
2. Ou acesse diretamente: `http://localhost/cfc-v.1/public_html/configuracoes/cfc`
3. Você deve ver a página com:
   - Título: "Configurações do CFC"
   - Seção: "Logo do CFC"
   - Seção: "Informações do CFC" (Nome e CNPJ editáveis)

## Passo 3: Testar Upload de Logo

1. **Selecione um arquivo de imagem** (JPG, PNG ou WEBP, máximo 5MB)
2. **Abra o Console do Navegador** (F12 → Console)
3. **Clique em "Fazer Upload"**
4. **Observe os logs no console:**
   ```
   [UPLOAD DEBUG] ========================================
   [UPLOAD DEBUG] Form submit iniciado
   [UPLOAD DEBUG] Action: ...
   [UPLOAD DEBUG] Arquivo selecionado: {...}
   [UPLOAD DEBUG] ✅ Validações passaram, enviando requisição...
   [UPLOAD DEBUG] Resposta recebida
   [UPLOAD DEBUG] Status: 302 (redirecionamento)
   [UPLOAD DEBUG] Headers de debug: {...}
   ```

## Passo 4: Verificar se o arquivo foi salvo

### 4.1. Verificar no disco

Execute no PowerShell (na raiz do projeto):
```powershell
# Verificar se o diretório existe
Test-Path "storage\uploads\cfcs"

# Listar arquivos recentes
Get-ChildItem "storage\uploads\cfcs" | Sort-Object LastWriteTime -Descending | Select-Object -First 5
```

### 4.2. Verificar no banco de dados

Execute no MySQL:
```sql
SELECT id, nome, logo_path FROM cfcs WHERE id = 1;
```

O campo `logo_path` deve conter algo como: `storage/uploads/cfcs/cfc_1_1234567890.png`

### 4.3. Verificar ícones PWA gerados

Execute no PowerShell:
```powershell
# Verificar se os ícones foram gerados
Test-Path "public_html\icons\1\icon-192x192.png"
Test-Path "public_html\icons\1\icon-512x512.png"

# Listar conteúdo do diretório
Get-ChildItem "public_html\icons\1" -ErrorAction SilentlyContinue
```

## Passo 5: Verificar logs do servidor

### 5.1. Log de upload (se existir)

Execute no PowerShell:
```powershell
# Verificar se o log existe
Test-Path "storage\logs\upload_logo.log"

# Ver últimas linhas do log
Get-Content "storage\logs\upload_logo.log" -Tail 50 -ErrorAction SilentlyContinue
```

### 5.2. Log de erros do PHP

Verifique o arquivo de log do PHP (geralmente em `C:\xampp\php\logs\php_error_log` ou configurado no `php.ini`)

## Passo 6: Testar edição de Nome e CNPJ

1. Na seção "Informações do CFC", edite o campo **Nome**
2. Edite o campo **CNPJ**
3. Clique em **"Salvar Informações"**
4. Verifique se a mensagem de sucesso aparece
5. Recarregue a página e confirme que os valores foram salvos

## Problemas Comuns

### ❌ Item "CFC" não aparece no menu

**Causa:** Usuário não está logado como ADMIN

**Solução:**
1. Verifique o role na sessão: `$_SESSION['current_role']` deve ser `'ADMIN'`
2. Faça logout e login novamente
3. Ou altere manualmente no banco: `UPDATE usuarios SET role = 'ADMIN' WHERE id = X;`

### ❌ Página retorna 404

**Causa:** Rota não configurada ou `.htaccess` não funcionando

**Solução:**
1. Verifique se `app/routes/web.php` tem a rota: `$router->get('/configuracoes/cfc', ...)`
2. Verifique se `public_html/.htaccess` está configurado corretamente
3. Teste acessar diretamente: `http://localhost/cfc-v.1/public_html/index.php?route=/configuracoes/cfc`

### ❌ Upload não salva arquivo

**Causa:** Permissões ou diretório não existe

**Solução:**
1. Execute o script de criação de diretório:
   ```powershell
   php public_html\tools\criar_diretorio_upload.php
   ```
2. Verifique permissões do diretório `storage/uploads/cfcs/` (deve ser gravável)
3. Verifique limites do PHP (`upload_max_filesize`, `post_max_size` no `php.ini`)

### ❌ Erro "Token CSRF inválido"

**Causa:** Token CSRF expirado ou não gerado

**Solução:**
1. Recarregue a página antes de fazer upload
2. Verifique se o campo `<input type="hidden" name="csrf_token">` está presente no formulário

## URLs de Teste Local

- **Login:** `http://localhost/cfc-v.1/public_html/`
- **Configurações CFC:** `http://localhost/cfc-v.1/public_html/configuracoes/cfc`
- **Manifest PWA:** `http://localhost/cfc-v.1/public_html/pwa-manifest.php`
- **Service Worker:** `http://localhost/cfc-v.1/public_html/sw.js`

## Próximos Passos

Após confirmar que tudo funciona localmente:
1. Fazer commit das alterações
2. Fazer push para o repositório
3. Testar em produção
