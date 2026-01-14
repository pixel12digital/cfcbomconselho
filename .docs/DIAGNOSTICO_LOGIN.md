# 🔍 Diagnóstico de Login - Guia Completo

## Problema
Login retorna "Credenciais inválidas" mesmo com credenciais corretas (admin@cfc.local / admin123).

## Scripts de Diagnóstico Criados

### 1. 🔍 Debug do Banco de Dados
**Arquivo:** `tools/debug_database.php`  
**Acesso:** `http://localhost/cfc-v.1/tools/debug_database.php`

Este script verifica:
- ✅ Configuração do banco (DB_HOST, DB_NAME, DB_USER)
- ✅ Banco de dados atual em uso (SELECT DATABASE())
- ✅ Existência do usuário admin@cfc.local
- ✅ Hash da senha armazenado
- ✅ Teste de verificação de senha (password_verify)

### 2. 🔐 Reset da Senha do Admin
**Arquivo:** `tools/reset_admin_password.php`  
**Acesso:** `http://localhost/cfc-v.1/tools/reset_admin_password.php`

Este script:
- Busca o usuário admin@cfc.local
- Gera um novo hash para 'admin123' usando `password_hash()`
- Atualiza a senha no banco de dados
- Testa se a senha funciona após atualização

### 3. 🔑 Gerar Hash de Senha
**Arquivo:** `tools/generate_password_hash.php`  
**Acesso:** `http://localhost/cfc-v.1/tools/generate_password_hash.php?password=admin123`

Gera um hash bcrypt para qualquer senha e fornece o SQL para atualizar.

### 4. 📡 Endpoint de Debug (JSON)
**Rota:** `/debug/database`  
**Acesso:** `http://localhost/cfc-v.1/public_html/debug/database`

Retorna JSON com informações de debug (apenas local).

---

## Checklist de Diagnóstico

Execute na seguinte ordem:

### ✅ Passo 1: Verificar Configuração do Banco
1. Acesse: `http://localhost/cfc-v.1/tools/debug_database.php`
2. Verifique:
   - **DB_HOST**: Deve ser o host correto (geralmente `localhost`)
   - **DB_NAME**: Deve ser o banco onde você rodou as migrations/seeds
   - **Banco atual em uso**: Deve corresponder ao DB_NAME configurado

**Se o banco atual for diferente do configurado:**
- Verifique o arquivo `.env` na raiz do projeto
- Ou edite `app/Config/Database.php` diretamente

### ✅ Passo 2: Verificar Existência do Admin
No script de debug, verifique se:
- ✅ Usuário `admin@cfc.local` existe
- ✅ Status está como `ativo`
- ✅ Hash da senha está presente

**Se o usuário não existir:**
```sql
-- Execute o seed completo
SOURCE database/seeds/001_seed_initial_data.sql;
```

Ou execute no phpMyAdmin/Workbench:
```sql
INSERT INTO `usuarios` (`id`, `cfc_id`, `nome`, `email`, `password`, `status`) VALUES
(1, 1, 'Administrador', 'admin@cfc.local', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'ativo')
ON DUPLICATE KEY UPDATE `nome` = VALUES(`nome`);
```

### ✅ Passo 3: Verificar Hash da Senha
No script de debug, verifique:
- ✅ **Teste de senha**: Deve retornar `TRUE`

**Se retornar `FALSE`:**
- O hash está incorreto ou foi gerado de forma diferente
- **Solução:** Execute `tools/reset_admin_password.php`

### ✅ Passo 4: Resetar Senha (se necessário)
1. Acesse: `http://localhost/cfc-v.1/tools/reset_admin_password.php`
2. Clique em "Atualizar Senha do Admin"
3. Verifique se o teste de verificação retorna `TRUE`
4. Tente fazer login novamente

---

## Verificação Manual via SQL

Execute no MySQL (phpMyAdmin ou Workbench):

### 1. Verificar se o admin existe:
```sql
SELECT id, email, password, status, created_at 
FROM usuarios 
WHERE email='admin@cfc.local' 
LIMIT 1;
```

### 2. Verificar banco atual:
```sql
SELECT DATABASE();
```

Compare com o `DB_NAME` configurado no `.env` ou `Database.php`.

### 3. Testar hash manualmente (via PHP):
Crie um arquivo `test_hash.php`:
```php
<?php
$hash = 'COLE_O_HASH_DO_BANCO_AQUI';
$password = 'admin123';
var_dump(password_verify($password, $hash));
?>
```

---

## Possíveis Causas e Soluções

### ❌ Causa 1: Banco de Dados Errado
**Sintoma:** O `SELECT DATABASE()` retorna um banco diferente do configurado.

**Solução:**
1. Verifique o arquivo `.env` na raiz do projeto
2. Ou edite `app/Config/Database.php` diretamente
3. Certifique-se de que o `DB_NAME` aponta para o banco correto

### ❌ Causa 2: Admin Não Existe
**Sintoma:** A query `SELECT ... FROM usuarios WHERE email='admin@cfc.local'` retorna vazio.

**Solução:**
Execute o seed: `database/seeds/001_seed_initial_data.sql`

### ❌ Causa 3: Hash Incorreto
**Sintoma:** `password_verify('admin123', $hashDoBanco)` retorna `FALSE`.

**Solução:**
1. Execute `tools/reset_admin_password.php`
2. Ou manualmente via SQL:
   ```sql
   -- Gerar hash no PHP:
   php -r "echo password_hash('admin123', PASSWORD_DEFAULT);"
   
   -- Depois atualizar:
   UPDATE usuarios 
   SET password = 'HASH_GERADO_AQUI'
   WHERE email = 'admin@cfc.local';
   ```

### ❌ Causa 4: Status Inativo
**Sintoma:** O usuário existe mas `status != 'ativo'`.

**Solução:**
```sql
UPDATE usuarios 
SET status = 'ativo' 
WHERE email = 'admin@cfc.local';
```

### ❌ Causa 5: Algoritmo de Hash Diferente
**Sintoma:** O seed usa um hash antigo (MD5, SHA1) ou hash direto.

**Solução:**
- O código de autenticação usa `password_verify()` que requer hash bcrypt
- Certifique-se de que o seed usa `password_hash('admin123', PASSWORD_DEFAULT)`
- Execute `tools/reset_admin_password.php` para corrigir

---

## Validação Final

Após seguir os passos acima, verifique:

1. ✅ Banco configurado = Banco em uso
2. ✅ Admin existe com email `admin@cfc.local`
3. ✅ Hash da senha é válido (password_verify retorna TRUE)
4. ✅ Status do usuário é `ativo`

**Se tudo estiver correto, o login deve funcionar!**

---

## Remover Scripts de Debug (Produção)

⚠️ **IMPORTANTE:** Antes de colocar em produção, remova:

1. A rota `/debug/database` de `app/routes/web.php`
2. O controller `app/Controllers/DebugController.php`
3. Os scripts em `tools/` (ou proteja com autenticação)

---

## Contato e Suporte

Se após seguir todos os passos o problema persistir, forneça:

1. Resultado de `SELECT DATABASE();`
2. Resultado de `SELECT id, email, password FROM usuarios WHERE email='admin@cfc.local';`
3. Valores de `DB_HOST`, `DB_NAME`, `DB_USER` do `.env` ou `Database.php`
4. Resultado do script `tools/debug_database.php`
