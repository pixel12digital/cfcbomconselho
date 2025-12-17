# Implementação: Sistema de Recuperação de Senha

**Data:** 2025-01-XX  
**Status:** ✅ IMPLEMENTADO  
**Versão:** 1.0

---

## Resumo Executivo

Implementado sistema completo de recuperação de senha pública para todos os perfis (Admin, Secretaria, Instrutor, Aluno) com segurança máxima e zero alterações na lógica de autenticação existente.

### Funcionalidades Implementadas

✅ Solicitação de recuperação via email/CPF  
✅ Geração de tokens seguros (SHA256)  
✅ Expiração de 30 minutos  
✅ Uso único (one-time tokens)  
✅ Rate limiting (5 minutos)  
✅ Proteção anti-enumeração  
✅ Envio de email via SMTP (com fallback seguro)  
✅ Redefinição de senha com validação  
✅ Sincronização de senha para alunos (tabela alunos)  
✅ Logs de auditoria completos

---

## Arquivos Criados/Modificados

### Novos Arquivos

1. **`docs/scripts/migration-password-resets.sql`**
   - Migration SQL para criar tabela `password_resets`

2. **`includes/PasswordReset.php`**
   - Classe principal para gerenciar recuperação de senha
   - Métodos: `requestReset()`, `validateToken()`, `consumeTokenAndSetPassword()`

3. **`includes/Mailer.php`**
   - Classe para envio de emails via SMTP
   - Método: `sendPasswordResetEmail()`
   - Verifica se SMTP está configurado antes de enviar

4. **`forgot-password.php`**
   - Página pública de solicitação de recuperação
   - Suporta todos os tipos: admin, secretaria, instrutor, aluno

5. **`reset-password.php`**
   - Página pública de redefinição de senha
   - Valida token, expiração e uso único

6. **`docs/RECUPERACAO_SENHA_IMPLEMENTACAO.md`** (este arquivo)
   - Documentação de implementação e testes

### Arquivos Modificados

1. **`login.php`**
   - Linha ~838: Atualizado link "Esqueci minha senha" para apontar para `forgot-password.php`
   - Adicionado link para alunos (linha ~840+)

---

## Estrutura do Banco de Dados

### Tabela `password_resets`

```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    login VARCHAR(100) NOT NULL,
    token_hash VARCHAR(64) NOT NULL,        -- SHA256 do token
    type ENUM('admin', 'secretaria', 'instrutor', 'aluno') NOT NULL,
    ip VARCHAR(45) NOT NULL,
    expires_at TIMESTAMP NOT NULL,           -- 30 minutos
    used_at TIMESTAMP NULL DEFAULT NULL,     -- NULL = não usado
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_token_hash (token_hash),
    INDEX idx_login (login),
    INDEX idx_expires_at (expires_at),
    INDEX idx_login_type (login, type),
    INDEX idx_login_ip_created (login, ip, created_at)
);
```

**Segurança:**
- ✅ Token nunca armazenado em texto puro (apenas hash SHA256)
- ✅ Expiração automática (30 minutos)
- ✅ Uso único (campo `used_at`)
- ✅ Índices para performance

---

## Fluxo Completo

### 1. Solicitação de Recuperação

**URL:** `forgot-password.php?type=admin|secretaria|instrutor|aluno`

**Processo:**
1. Usuário acessa página
2. Informa email (ou CPF para aluno)
3. Sistema valida rate limit (5 minutos)
4. Busca usuário (sem revelar se existe)
5. Gera token único (32 bytes, hex)
6. Hash SHA256 do token
7. Salva no banco com expiração de 30 min
8. Invalida tokens anteriores do mesmo login
9. Envia email com link (se SMTP configurado)
10. Retorna mensagem neutra (anti-enumeração)

**Arquivos:**
- `forgot-password.php` - Interface
- `includes/PasswordReset.php::requestReset()` - Lógica
- `includes/Mailer.php::sendPasswordResetEmail()` - Envio

### 2. Validação do Token

**URL:** `reset-password.php?token={token}`

**Processo:**
1. Sistema recebe token via GET
2. Hash SHA256 do token
3. Busca no banco por hash
4. Valida expiração (`expires_at > NOW()`)
5. Valida não usado (`used_at IS NULL`)
6. Se válido, exibe formulário
7. Se inválido, exibe erro

**Arquivos:**
- `reset-password.php` - Interface
- `includes/PasswordReset.php::validateToken()` - Validação

### 3. Redefinição de Senha

**Processo:**
1. Usuário preenche nova senha + confirmação
2. Valida força (mínimo 8 caracteres)
3. Valida confirmação (deve coincidir)
4. Re-valida token (pode ter expirado entre validação e POST)
5. Busca usuário no banco
6. Hash da nova senha (`password_hash()`)
7. Atualiza `usuarios.senha`
8. Se aluno, sincroniza `alunos.senha`
9. Marca token como usado (`used_at = NOW()`)
10. Invalida outros tokens não usados do mesmo login
11. Log de auditoria
12. Redireciona para login

**Arquivos:**
- `reset-password.php` - Interface
- `includes/PasswordReset.php::consumeTokenAndSetPassword()` - Lógica

---

## Segurança Implementada

### ✅ Proteções Ativas

1. **Tokens com Hash**
   - Token em texto puro apenas no email
   - Armazenado como SHA256 no banco
   - Impossível recuperar token original do banco

2. **Expiração Curta**
   - Tokens expiram em 30 minutos
   - Query automática: `expires_at > NOW()`

3. **Uso Único (One-Time)**
   - Campo `used_at` marca quando foi usado
   - Token não pode ser reutilizado
   - Outros tokens do mesmo login são invalidados após uso

4. **Rate Limiting**
   - 1 solicitação a cada 5 minutos por login+ip
   - Previne spam/abuso
   - Usa própria tabela `password_resets` para verificar

5. **Proteção Anti-Enumeração**
   - Mensagem neutra sempre retornada
   - Não revela se usuário existe ou não
   - Mesma mensagem para todos os casos:
     - Usuário não encontrado
     - Rate limit atingido
     - Sucesso (com email enviado)

6. **Validação de Senha**
   - Mínimo 8 caracteres
   - Validação no frontend e backend
   - Confirmação obrigatória

7. **Logs de Auditoria**
   - Todas as ações são logadas
   - Formato: `[PASSWORD_RESET_*] login=X, type=Y, ip=Z, timestamp=W`
   - Sem dados sensíveis (nunca loga senhas ou tokens)

---

## Configuração de Email

### Estado Atual

**Arquivo:** `includes/config.php` (linhas 84-87)

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'seu_email@seudominio.com'); // ⚠️ Placeholder
define('SMTP_PASS', 'sua_senha_smtp'); // ⚠️ Placeholder
```

**Status:** ⚠️ Configurações são placeholders (não funcionais)

### Comportamento quando SMTP não configurado

1. `Mailer::isConfigured()` detecta placeholders
2. Retorna `false` (não configurado)
3. `sendPasswordResetEmail()` retorna erro mas não quebra fluxo
4. Mensagem neutra é exibida ao usuário (como se tivesse enviado)
5. Log registra que SMTP não está configurado
6. Sistema continua funcionando normalmente

**Recomendação:** Configurar SMTP real antes de produção.

---

## Testes Recomendados

### Teste 1: Solicitação de Recuperação (Admin)

**Passos:**
1. Acessar `forgot-password.php?type=admin`
2. Inserir email de admin válido
3. Clicar em "Enviar Instruções"
4. Verificar mensagem neutra exibida
5. Verificar log: token gerado e salvo
6. Verificar email (se SMTP configurado)
7. Verificar banco: registro em `password_resets`

**Resultado Esperado:**
- ✅ Mensagem neutra: "Se o dado informado existir em nossa base..."
- ✅ Token gerado no banco (hash SHA256)
- ✅ Expiração em 30 minutos
- ✅ Email enviado (se SMTP configurado)

### Teste 2: Validação de Token

**Passos:**
1. Copiar token do log (ou do banco antes do hash)
2. Acessar `reset-password.php?token={token}`
3. Verificar se formulário aparece
4. Verificar se token inválido não funciona

**Resultado Esperado:**
- ✅ Token válido exibe formulário
- ✅ Token inválido/expirado exibe erro
- ✅ Token usado não funciona novamente

### Teste 3: Redefinição de Senha

**Passos:**
1. Acessar `reset-password.php?token={token_válido}`
2. Preencher nova senha (mínimo 8 caracteres)
3. Confirmar senha
4. Submeter formulário
5. Tentar login com nova senha
6. Tentar usar token novamente (deve falhar)

**Resultado Esperado:**
- ✅ Senha atualizada com sucesso
- ✅ Login funciona com nova senha
- ✅ Token marcado como usado (`used_at` preenchido)
- ✅ Token não funciona mais (reuso bloqueado)

### Teste 4: Rate Limiting

**Passos:**
1. Solicitar recuperação para um email
2. Imediatamente solicitar novamente (mesmo email+ip)
3. Verificar comportamento

**Resultado Esperado:**
- ✅ Primeira solicitação: token gerado
- ✅ Segunda solicitação (< 5 min): mensagem neutra, mas token não gerado
- ✅ Log registra rate limit

### Teste 5: Proteção Anti-Enumeração

**Passos:**
1. Solicitar recuperação para email que **não existe**
2. Solicitar recuperação para email que **existe**
3. Comparar mensagens

**Resultado Esperado:**
- ✅ Mesma mensagem neutra em ambos os casos
- ✅ Não revela se usuário existe ou não
- ✅ Log diferencia (mas usuário não vê diferença)

### Teste 6: Expiração de Token

**Passos:**
1. Solicitar recuperação
2. Aguardar 31 minutos (ou alterar `expires_at` no banco para passado)
3. Tentar usar token

**Resultado Esperado:**
- ✅ Token expirado não funciona
- ✅ Mensagem: "Link inválido ou expirado"
- ✅ Necessário solicitar nova recuperação

### Teste 7: Aluno (CPF)

**Passos:**
1. Acessar `forgot-password.php?type=aluno`
2. Inserir CPF válido de aluno
3. Verificar se funciona

**Resultado Esperado:**
- ✅ Aceita CPF (com ou sem formatação)
- ✅ Busca aluno na tabela usuarios
- ✅ Funciona se aluno tiver email cadastrado
- ✅ Mensagem apropriada se não tiver email

### Teste 8: Validação de Integridade

**Passos:**
1. Verificar que login continua funcionando normalmente
2. Verificar que permissões não foram alteradas
3. Verificar que PWA não foi alterado

**Resultado Esperado:**
- ✅ Login funciona para todos os tipos
- ✅ Permissões baseadas no tipo do banco (sem alterações)
- ✅ Manifest/SW/PWA intocados

---

## Checklist de Validação

### Funcionalidades

- [ ] Solicitação de recuperação funciona para admin
- [ ] Solicitação de recuperação funciona para secretaria
- [ ] Solicitação de recuperação funciona para instrutor
- [ ] Solicitação de recuperação funciona para aluno (com email)
- [ ] Token é gerado e salvo corretamente
- [ ] Token expira após 30 minutos
- [ ] Token não pode ser reutilizado
- [ ] Rate limiting funciona (5 minutos)
- [ ] Proteção anti-enumeração funciona
- [ ] Email é enviado (se SMTP configurado)
- [ ] Redefinição de senha funciona
- [ ] Nova senha permite login
- [ ] Sincronização de senha para aluno funciona

### Segurança

- [ ] Token nunca logado em produção
- [ ] Token armazenado como hash (SHA256)
- [ ] Senha hasheada com `password_hash()` (bcrypt)
- [ ] Mensagens neutras (não revela se usuário existe)
- [ ] Rate limiting ativo
- [ ] Logs de auditoria funcionando
- [ ] Nenhum dado sensível em logs

### Compatibilidade

- [ ] Login continua funcionando normalmente
- [ ] Nenhuma alteração em `includes/auth.php`
- [ ] Nenhuma alteração em `redirectAfterLogin()`
- [ ] Permissões não foram alteradas
- [ ] PWA/manifest/SW intocados

---

## Migração do Banco de Dados

### Passo 1: Executar Migration

**Arquivo:** `docs/scripts/migration-password-resets.sql`

```sql
-- Copiar conteúdo do arquivo migration-password-resets.sql
-- Executar no banco de dados
```

**Método Recomendado:**
1. Acessar phpMyAdmin ou cliente MySQL
2. Selecionar banco de dados
3. Executar SQL do arquivo
4. Verificar tabela criada: `SHOW TABLES LIKE 'password_resets';`

### Passo 2: Verificar Estrutura

```sql
DESCRIBE password_resets;
```

**Deve mostrar:**
- `id`, `login`, `token_hash`, `type`, `ip`, `expires_at`, `used_at`, `created_at`
- Índices criados

---

## Configuração de SMTP (Produção)

### Passo 1: Obter Credenciais SMTP

Para Hostinger:
- **SMTP_HOST:** `smtp.hostinger.com`
- **SMTP_PORT:** `587`
- **SMTP_USER:** Email configurado no painel Hostinger
- **SMTP_PASS:** Senha do email

### Passo 2: Atualizar Configuração

**Arquivo:** `includes/config.php` (linhas 84-87)

```php
define('SMTP_HOST', 'smtp.hostinger.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'noreply@cfcbomconselho.com.br'); // ⬅️ Email real
define('SMTP_PASS', 'senha_real_aqui'); // ⬅️ Senha real
```

### Passo 3: Testar Envio

1. Solicitar recuperação de senha
2. Verificar se email é recebido
3. Verificar logs: `[MAILER] Email enviado com sucesso`

---

## Troubleshooting

### Problema: Email não é enviado

**Causas Possíveis:**
1. SMTP não configurado (placeholders)
2. Credenciais SMTP incorretas
3. Servidor bloqueia `mail()`
4. Firewall bloqueia porta 587

**Solução:**
1. Verificar `Mailer::isConfigured()` retorna `true`
2. Testar credenciais manualmente
3. Verificar logs: `[MAILER]` para detalhes
4. Considerar usar PHPMailer se `mail()` não funcionar

### Problema: Token não funciona

**Causas Possíveis:**
1. Token expirado (> 30 minutos)
2. Token já foi usado
3. Hash do token incorreto
4. Tabela `password_resets` não existe

**Solução:**
1. Verificar `expires_at` no banco
2. Verificar `used_at` no banco
3. Verificar se migration foi executada
4. Verificar logs para erros

### Problema: Rate limit muito restritivo

**Causa:** Limite de 5 minutos pode ser muito curto

**Solução:**
- Ajustar em `PasswordReset::checkRateLimit()` (linha ~290)
- Alterar `INTERVAL 5 MINUTE` para valor desejado

### Problema: Aluno sem email não consegue recuperar

**Comportamento Atual:**
- Aluno sem email recebe mensagem para contatar secretaria
- Sistema não bloqueia, apenas orienta

**Solução Futura (se necessário):**
- Implementar validação por CPF + Data de Nascimento
- Ou SMS (se serviço disponível)

---

## Validações de Segurança (Checklist Final)

### ✅ Implementado

- [x] Token com hash (SHA256)
- [x] Expiração (30 minutos)
- [x] Uso único
- [x] Rate limiting
- [x] Proteção anti-enumeração
- [x] Validação de força de senha
- [x] Logs de auditoria (sem dados sensíveis)
- [x] Mensagens neutras
- [x] Sincronização de senha (aluno)

### ⚠️ Requer Configuração

- [ ] SMTP configurado (produção)
- [ ] Testes manuais completos
- [ ] Monitoramento de logs em produção

---

## Próximos Passos Recomendados

1. **Configurar SMTP** (produção)
   - Atualizar `includes/config.php` com credenciais reais
   - Testar envio de email

2. **Testes Completos**
   - Executar todos os testes da seção "Testes Recomendados"
   - Validar cada fluxo

3. **Monitoramento**
   - Acompanhar logs de `[PASSWORD_RESET_*]`
   - Verificar rate limiting em produção
   - Monitorar tentativas de abuso

4. **Melhorias Futuras (Opcional)**
   - Template de email mais elaborado
   - Suporte a SMS (aluno sem email)
   - Dashboard de monitoramento de resets
   - Notificação para admin de resets suspeitos

---

## Notas Importantes

### Zero Alterações em Autenticação

✅ **Confirmado:** Nenhuma alteração em:
- `includes/auth.php` (método `login()`)
- `createSession()`
- `redirectAfterLogin()`
- Verificações de permissão
- Sistema de sessão

### PWA Intocado

✅ **Confirmado:** Nenhuma alteração em:
- Manifest files (`pwa/manifest*.json`)
- Service Worker (`sw.js`, `pwa/sw.js`)
- Beforeinstallprompt
- Instalação PWA
- Cache/Offline

### Compatibilidade

✅ **Confirmado:**
- Sistema funciona mesmo sem SMTP configurado
- Mensagem neutra sempre exibida
- Não quebra fluxo se email falhar
- Graceful degradation

---

## Comandos Úteis para Testes

### Verificar Tokens no Banco

```sql
SELECT id, login, type, expires_at, used_at, created_at 
FROM password_resets 
ORDER BY created_at DESC 
LIMIT 10;
```

### Verificar Rate Limiting

```sql
SELECT login, ip, created_at 
FROM password_resets 
WHERE created_at > DATE_SUB(NOW(), INTERVAL 10 MINUTE)
ORDER BY created_at DESC;
```

### Limpar Tokens Antigos (Manutenção)

```sql
-- Limpar tokens expirados (mais de 1 dia)
DELETE FROM password_resets 
WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY);
```

---

**Implementação concluída com sucesso! ✅**
