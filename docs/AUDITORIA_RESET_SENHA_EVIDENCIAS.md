# Auditoria Objetiva - Erro ao Redefinir Senha (Aluno)

## Status: Logging Cirúrgico Implementado ✅

### O que foi implementado:

1. **Script de Diagnóstico Melhorado** (`admin/tools/diagnostico-reset-senha.php`)
   - ✅ `token_length` (tamanho exato do token)
   - ✅ Comparação de timezone (PHP local, PHP UTC, MySQL NOW, MySQL UTC_TIMESTAMP)
   - ✅ Schema da coluna `senha` (tipo, null, key, default)
   - ✅ Status do token (expires_at, used_at, válido/expirado/usado)
   - ✅ Informações do usuário (user_found, user_id, user_tipo, user_ativo)
   - ✅ Hash da senha atual (primeiros 20 caracteres + tamanho)

2. **Logging Cirúrgico no PasswordReset.php**
   - ✅ `[PASSWORD_RESET_AUDIT] [1]` - Antes de validar token
   - ✅ `[PASSWORD_RESET_AUDIT] [2]` - Depois de buscar token no banco
   - ✅ `[PASSWORD_RESET_AUDIT] [3]` - Depois de buscar usuário
   - ✅ `[PASSWORD_RESET_AUDIT] [4]` - Antes do UPDATE (senha atual)
   - ✅ `[PASSWORD_RESET_AUDIT] [5]` - Depois do UPDATE (senha nova, se mudou)
   - ✅ `[PASSWORD_RESET_AUDIT] [6]` - Antes de marcar used_at
   - ✅ `[PASSWORD_RESET_AUDIT] [7]` - Depois de marcar used_at
   - ✅ `[PASSWORD_RESET_AUDIT] [8]` - Completo (resumo final)

3. **Verificação de Email Mascarado**
   - ✅ **CONFIRMADO**: Email mascarado é usado APENAS para exibição
   - ✅ Email real (`$emailTo`) é usado para envio via `Mailer::sendPasswordResetEmail()`
   - ✅ Linha 75 de `forgot-password.php`: `Mailer::sendPasswordResetEmail($emailTo, ...)`
   - ✅ `$emailTo` vem de `$result['user_email']` (email real, não mascarado)

## Como Reproduzir e Coletar Evidências

### Passo 0: Reproduzir o Erro

1. Acesse: `https://cfcbomconselho.com.br/forgot-password.php?type=aluno`
2. Digite o CPF de um aluno conhecido (com email válido)
3. Clique em "Enviar instruções"
4. Abra o email recebido
5. **Copie o token completo da URL** (ex: `reset-password.php?token=abc123...`)
6. **Anote o tamanho do token** (deve ser 64 caracteres se hex)
7. Acesse a URL completa: `https://cfcbomconselho.com.br/reset-password.php?token=SEU_TOKEN`
8. Preencha nova senha (mínimo 8 caracteres)
9. Clique em "Redefinir Senha"
10. **Confirme que aparece "Erro ao atualizar senha. Tente novamente."**

### Passo 1: Validar Token com Script

1. Acesse: `https://cfcbomconselho.com.br/admin/tools/diagnostico-reset-senha.php`
2. Vá na aba "Validar Token"
3. Cole o token completo do email
4. Clique em "Validar Token"
5. **Copie/Print da tela mostrando:**
   - `token_length`: X caracteres
   - `Token válido`: SIM/NÃO
   - `expires_at`: YYYY-MM-DD HH:MM:SS
   - `used_at`: NULL ou data
   - Comparação de timezone (PHP vs MySQL)
   - `user_found`: SIM/NÃO
   - `user_id`: número
   - `user_ativo`: SIM/NÃO
   - Schema da coluna `senha`

### Passo 2: Coletar Logs do Sistema

**Via SSH:**
```bash
# Últimas 50 linhas relacionadas a reset de senha
tail -n 50 logs/php_errors.log | grep -i "PASSWORD_RESET_AUDIT\|PASSWORD_RESET\|reset-password"

# Ou monitorar em tempo real (antes de tentar reset)
tail -f logs/php_errors.log | grep -i "PASSWORD_RESET_AUDIT"
```

**Via Script de Diagnóstico:**
1. Acesse: `admin/tools/diagnostico-reset-senha.php`
2. Vá na aba "Logs Recentes"
3. Procure por `[PASSWORD_RESET_AUDIT]`

**O que procurar nos logs:**
- `[PASSWORD_RESET_AUDIT] [1]` - Token recebido
- `[PASSWORD_RESET_AUDIT] [2]` - Token validado?
- `[PASSWORD_RESET_AUDIT] [3]` - Usuário encontrado?
- `[PASSWORD_RESET_AUDIT] [4]` - Senha ANTES do UPDATE
- `[PASSWORD_RESET_AUDIT] [5]` - Senha DEPOIS do UPDATE (mudou?)
- `[PASSWORD_RESET_AUDIT] [6]` - Antes de marcar used_at
- `[PASSWORD_RESET_AUDIT] [7]` - Depois de marcar used_at
- `[PASSWORD_RESET_AUDIT] [8]` - Resumo final

### Passo 3: Verificar UPDATE no Banco

**Query SQL para verificar se senha mudou:**
```sql
-- Antes do reset (copiar hash atual)
SELECT id, email, tipo, LEFT(senha, 20) as senha_hash_preview, LENGTH(senha) as senha_len
FROM usuarios 
WHERE id = [USER_ID_AQUI];

-- Depois do reset (verificar se mudou)
SELECT id, email, tipo, LEFT(senha, 20) as senha_hash_preview, LENGTH(senha) as senha_len
FROM usuarios 
WHERE id = [USER_ID_AQUI];
```

**Comparar:**
- Se `senha_hash_preview` mudou → UPDATE funcionou
- Se `senha_hash_preview` igual → UPDATE não funcionou

## Hipóteses a Testar (SIM/NÃO + Evidência)

### H1: Token está expirado na hora do POST?
**Teste:**
- Comparar `expires_at` (do token) vs `NOW()` do MySQL
- Se `expires_at < NOW()` → **SIM, expirado**
- **Evidência:** Log `[PASSWORD_RESET_AUDIT] [2]` mostra `valid: false, reason: ...`

### H2: Token já está marcado como usado antes do submit?
**Teste:**
- Verificar `used_at` no banco antes de tentar reset
- Se `used_at IS NOT NULL` → **SIM, já usado**
- **Evidência:** Log `[PASSWORD_RESET_AUDIT] [2]` mostra `valid: false, reason: ...`

### H3: Token válido no GET, inválido no POST?
**Teste:**
- Validar token no GET (página carrega)
- Validar token no POST (formulário submetido)
- Se GET válido mas POST inválido → **SIM, problema de validação**
- **Evidência:** Comparar logs `[RESET_PASSWORD]` (GET) vs `[PASSWORD_RESET_AUDIT] [2]` (POST)

### H4: UPDATE está mirando coluna errada?
**Teste:**
- Verificar schema: `SHOW COLUMNS FROM usuarios WHERE Field = 'senha'`
- Verificar query executada: Log `[PASSWORD_RESET_AUDIT] [4]` mostra coluna
- Se coluna diferente de `senha` → **SIM, coluna errada**
- **Evidência:** Schema mostra coluna diferente

### H5: Usuário aluno está em outra tabela ou tem outro ID?
**Teste:**
- Verificar `user_id` encontrado vs `user_id` no token
- Verificar se usuário existe: `SELECT * FROM usuarios WHERE id = [ID]`
- Se ID diferente ou não existe → **SIM, mapeamento errado**
- **Evidência:** Log `[PASSWORD_RESET_AUDIT] [3]` mostra `user_id` diferente

### H6: Senha está sendo salva, mas falha ao marcar token como usado?
**Teste:**
- Verificar se senha mudou: Log `[PASSWORD_RESET_AUDIT] [5]` mostra `senha_mudou: SIM`
- Verificar se `used_at` foi marcado: Log `[PASSWORD_RESET_AUDIT] [7]` mostra `used_at: NULL`
- Se senha mudou mas `used_at` NULL → **SIM, falha ao marcar**
- **Evidência:** Logs `[5]` e `[7]` contraditórios

### H7: Regra de senha (min 8) está bloqueando?
**Teste:**
- Verificar tamanho da senha: Log `[PASSWORD_RESET_AUDIT] [1]` mostra `password_len`
- Se `password_len < 8` → **SIM, senha muito curta**
- **Evidência:** Log mostra `password_len: X` onde X < 8

## Formato de Resposta Esperado

Após coletar evidências, retornar:

```
=== EVIDÊNCIAS COLETADAS ===

1. TOKEN:
   - token_length: 64
   - token_preview: abc123...
   - token_existe_na_tabela: SIM/NÃO
   - expires_at: 2025-01-18 10:30:00
   - used_at: NULL ou 2025-01-18 10:25:00
   - valid: SIM/NÃO
   - reason: (se inválido)

2. TIMEZONE:
   - PHP local: 2025-01-18 10:30:00
   - PHP UTC: 2025-01-18 13:30:00
   - MySQL NOW(): 2025-01-18 10:30:00
   - MySQL UTC_TIMESTAMP(): 2025-01-18 13:30:00

3. USUÁRIO:
   - user_found: SIM/NÃO
   - user_id: 123
   - user_tipo: aluno
   - user_ativo: SIM/NÃO

4. SCHEMA:
   - coluna_senha: senha
   - tipo: VARCHAR(255)
   - null: YES/NO

5. LOGS (trecho relevante):
   [PASSWORD_RESET_AUDIT] [1] ...
   [PASSWORD_RESET_AUDIT] [2] ...
   [PASSWORD_RESET_AUDIT] [3] ...
   [PASSWORD_RESET_AUDIT] [4] ...
   [PASSWORD_RESET_AUDIT] [5] ...
   [PASSWORD_RESET_AUDIT] [6] ...
   [PASSWORD_RESET_AUDIT] [7] ...
   [PASSWORD_RESET_AUDIT] [8] ...

6. HIPÓTESES:
   - H1 (Token expirado): SIM/NÃO - Evidência: ...
   - H2 (Token usado): SIM/NÃO - Evidência: ...
   - H3 (GET válido, POST inválido): SIM/NÃO - Evidência: ...
   - H4 (Coluna errada): SIM/NÃO - Evidência: ...
   - H5 (ID errado): SIM/NÃO - Evidência: ...
   - H6 (Falha ao marcar used_at): SIM/NÃO - Evidência: ...
   - H7 (Senha muito curta): SIM/NÃO - Evidência: ...
```

## Próximo Passo

Com essas evidências, será possível identificar **exatamente** qual é a causa e aplicar a **correção mínima** necessária, sem tentativa e erro.
