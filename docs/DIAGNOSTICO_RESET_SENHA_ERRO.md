# Diagnóstico - Erro ao Resetar e Criar Nova Senha

## Problema Reportado
Erro ao resetar e criar nova senha do aluno pelo link enviado no email.

## Logs do Hostinger
Os logs do Hostinger mostram apenas erros 404 (robots.txt, etc), que são normais e não relacionados ao problema.

## Possíveis Causas

### 1. Token Truncado na URL do Email ⚠️ **MAIS PROVÁVEL**
**Problema:** Tokens têm 64 caracteres hex. Alguns clientes de email (Gmail, Outlook) podem quebrar URLs longas, truncando o token.

**Sintomas:**
- Link do email parece completo, mas token está incompleto
- Validação falha com "Token não encontrado no banco"
- Token no banco tem 64 caracteres, mas o recebido tem menos

**Solução:**
- Verificar se o token completo está sendo enviado no email
- Adicionar verificação de comprimento do token antes de validar
- Considerar usar tokens mais curtos (32 caracteres) ou dividir em duas partes

### 2. Problema com Timezone
**Problema:** Código usa UTC, mas pode haver inconsistência entre PHP e MySQL.

**Sintomas:**
- Token válido mas aparece como expirado
- Diferença de alguns minutos/horas na validação

**Verificação:**
- Usar script de diagnóstico: `admin/tools/diagnostico-reset-senha.php`
- Verificar se timezone PHP e MySQL estão sincronizados

### 3. Problema na Atualização da Senha
**Problema:** `rowCount()` pode retornar 0 mesmo quando UPDATE é executado.

**Sintomas:**
- Token válido, usuário encontrado, mas senha não é atualizada
- Logs mostram "Nenhuma linha foi atualizada"

**Verificação:**
- Verificar logs: `[PASSWORD_RESET] Resultado do UPDATE`
- Verificar se ID do usuário existe na tabela

### 4. Token Já Foi Usado
**Problema:** Token foi usado anteriormente ou link foi clicado duas vezes.

**Sintomas:**
- Validação inicial passa, mas ao submeter formulário falha
- Mensagem "Link inválido ou expirado"

**Verificação:**
- Verificar campo `used_at` na tabela `password_resets`
- Verificar se há múltiplas tentativas com mesmo token

## Ferramentas de Diagnóstico

### 1. Script de Diagnóstico Completo
**Arquivo:** `admin/tools/diagnostico-reset-senha.php`

**Funcionalidades:**
- Status do sistema (tabela, configuração, timezone)
- Validar token específico
- Buscar usuário por login
- Ver logs recentes

**Como usar:**
1. Acesse: `https://cfcbomconselho.com.br/admin/tools/diagnostico-reset-senha.php`
2. Use a aba "Validar Token" para testar um token específico
3. Use a aba "Buscar Usuário" para verificar se usuário existe
4. Use a aba "Logs Recentes" para ver erros recentes

### 2. Verificar Logs do PHP
**Arquivo:** `logs/php_errors.log`

**Comandos SSH:**
```bash
# Últimas 50 linhas relacionadas a reset de senha
tail -n 50 logs/php_errors.log | grep -i "PASSWORD_RESET\|RESET_PASSWORD"

# Monitorar em tempo real
tail -f logs/php_errors.log | grep -i "PASSWORD_RESET\|RESET_PASSWORD"

# Todas as ocorrências de hoje
grep "$(date +%Y-%m-%d)" logs/php_errors.log | grep -i "PASSWORD_RESET"
```

**O que procurar:**
- `[RESET_PASSWORD]` - Logs da página reset-password.php
- `[PASSWORD_RESET]` - Logs da classe PasswordReset
- `[MAILER]` - Logs do envio de email
- Mensagens de erro com `❌` ou `error`

### 3. Verificar Token no Banco
**Query SQL:**
```sql
-- Ver todos os tokens recentes
SELECT id, login, type, created_at, expires_at, used_at,
       TIMESTAMPDIFF(MINUTE, NOW(), expires_at) as minutos_restantes
FROM password_resets
ORDER BY created_at DESC
LIMIT 10;

-- Verificar token específico (precisa do hash)
SELECT * FROM password_resets 
WHERE token_hash = SHA2('TOKEN_AQUI', 256)
LIMIT 1;
```

## Passos para Resolver

### Passo 1: Coletar Informações
1. **Obter token do email:**
   - Copiar link completo do email
   - Extrair token da URL
   - Verificar comprimento (deve ter 64 caracteres hex)

2. **Usar script de diagnóstico:**
   - Acessar `admin/tools/diagnostico-reset-senha.php`
   - Colar token na aba "Validar Token"
   - Verificar resultado

3. **Verificar logs:**
   - Acessar logs recentes no script
   - Procurar por erros específicos

### Passo 2: Identificar Causa Específica

**Se token não encontrado:**
- Verificar se token está completo (64 caracteres)
- Verificar se token foi gerado recentemente
- Verificar se há problema com URL encoding

**Se token expirado:**
- Verificar timezone PHP vs MySQL
- Verificar se expiração está correta (30 minutos)
- Verificar se token foi usado antes do tempo

**Se usuário não encontrado:**
- Verificar se login está correto
- Verificar se tipo está correto (aluno/admin/secretaria/instrutor)
- Verificar se usuário está ativo

**Se senha não atualiza:**
- Verificar se ID do usuário existe
- Verificar se há erro na query UPDATE
- Verificar logs de exceção

### Passo 3: Aplicar Correção

**Se token truncado:**
- Considerar encurtar token ou usar método alternativo
- Adicionar verificação de comprimento
- Melhorar template de email para evitar quebra de linha

**Se problema de timezone:**
- Garantir que PHP e MySQL usam UTC
- Verificar configuração do servidor

**Se problema na atualização:**
- Verificar se método `update()` está funcionando
- Adicionar verificação adicional após UPDATE
- Verificar se há constraints ou triggers no banco

## Checklist de Verificação

- [ ] Token tem 64 caracteres (completo)
- [ ] Token existe no banco de dados
- [ ] Token não está expirado (verificar `expires_at`)
- [ ] Token não foi usado (verificar `used_at IS NULL`)
- [ ] Usuário existe na tabela `usuarios`
- [ ] Usuário está ativo (`ativo = 1`)
- [ ] Login corresponde ao token (email ou CPF)
- [ ] Tipo do usuário corresponde ao token
- [ ] Timezone PHP e MySQL estão sincronizados
- [ ] Logs não mostram erros de exceção
- [ ] UPDATE está sendo executado (verificar logs)
- [ ] `rowCount()` retorna > 0 após UPDATE

## Próximos Passos

1. **Solicitar token de teste:**
   - Acessar `forgot-password.php?type=aluno`
   - Solicitar reset para um aluno de teste
   - Copiar token completo do email

2. **Usar script de diagnóstico:**
   - Validar token
   - Verificar todos os passos do fluxo
   - Identificar onde está falhando

3. **Verificar logs em tempo real:**
   - Abrir logs em uma aba
   - Tentar reset em outra aba
   - Verificar mensagens de erro

4. **Aplicar correção específica:**
   - Baseado no diagnóstico
   - Testar novamente
   - Verificar se problema foi resolvido

## Contato para Suporte

Se o problema persistir após seguir todos os passos:
1. Coletar informações do script de diagnóstico
2. Coletar logs relevantes
3. Coletar token que está falhando (primeiros e últimos 10 caracteres apenas)
4. Descrever exatamente o que acontece (passo a passo)
