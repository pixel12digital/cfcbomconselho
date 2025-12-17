# Diagnóstico: Email de Recuperação Não Envia (Teste SMTP OK)

**Data:** 2025-12-17  
**Cenário:** Teste de envio funciona, mas email de recuperação de senha não chega ao usuário  
**Status:** 🔍 CAUSA RAIZ IDENTIFICADA

---

## 📋 RESUMO DO PROBLEMA

### Sintomas Relatados:
- ✅ **Teste de envio de email:** Funciona corretamente
- ✅ **Dados do usuário (aluno):** Confirmados e válidos
- ✅ **Mensagem de recuperação:** Aparece corretamente (mostra email mascarado)
- ❌ **Email de redefinição:** Não chega ao usuário em produção
- ✅ **Email validado:** Mesmo email usado no teste funciona

---

## 🔍 ANÁLISE DO FLUXO

### 1. Fluxo "Testar SMTP" (Funciona)

**Arquivo:** `admin/pages/configuracoes-smtp.php` → `admin/api/smtp-config.php` → `SMTPConfigService::testConfig()`

**Código (SMTPConfigService.php, linha 138-184):**
```php
public static function testConfig($testEmail, $userId = null) {
    // ...
    $testToken = bin2hex(random_bytes(16)); // Token fake
    $result = Mailer::sendPasswordResetEmail($testEmail, $testToken, 'admin');
    // ...
    return $result; // Retorna sucesso/erro
}
```

**Resultado:** ✅ Email enviado com sucesso

---

### 2. Fluxo "Recuperação de Senha" (Não Envia)

**Arquivo:** `forgot-password.php` → `PasswordReset::requestReset()` → `Mailer::sendPasswordResetEmail()`

**Código (forgot-password.php, linha 67-78):**
```php
if ($result['success'] && isset($result['token']) && $result['token']) {
    // Token gerado - enviar email
    $emailTo = $result['user_email'] ?? null;
    
    if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        // Tentar enviar email
        $emailResult = Mailer::sendPasswordResetEmail($emailTo, $result['token'], $requestedType);
        
        // Sucesso: cadastro encontrado e email enviado
        $success = $result['message'];
        $maskedDestination = $result['masked_destination'];
    }
}
```

**Problema identificado:**
1. ✅ Email é chamado (linha 73)
2. ❌ **Resultado do envio (`$emailResult`) NÃO é verificado**
3. ❌ **Se `$emailResult['success'] === false`, o código continua como se tivesse enviado**
4. ❌ **Mensagem de sucesso é exibida mesmo se o email falhar**

---

## 🎯 CAUSA RAIZ

### Problema Principal: Erro Silencioso no Envio

**O que acontece:**
1. `PasswordReset::requestReset()` gera token e retorna `success: true`
2. `forgot-password.php` chama `Mailer::sendPasswordResetEmail()`
3. Se o envio falhar (ex: erro SMTP, timeout, etc.), `$emailResult['success']` será `false`
4. **MAS o código não verifica `$emailResult`**
5. A mensagem de sucesso é exibida mesmo com falha no envio

**Evidências:**
- Teste SMTP funciona (usa mesmo método `Mailer::sendPasswordResetEmail()`)
- Mensagem de sucesso aparece (indica que token foi gerado)
- Email não chega (indica que envio falhou silenciosamente)

---

## 🔬 POSSÍVEIS CAUSAS ESPECÍFICAS

### 1. Erro no Envio Não Está Sendo Logado

**Verificar logs:**
```bash
# Procurar por erros de envio
grep -i "MAILER\|sendPasswordResetEmail" logs/php_errors.log
```

**Se não houver logs:**
- Erro pode estar sendo silenciado
- `LOG_ENABLED` pode estar desabilitado
- Erro pode estar ocorrendo antes do `error_log()`

---

### 2. Diferença de Contexto Entre Teste e Recuperação

**Teste SMTP:**
- Executado via API (`admin/api/smtp-config.php`)
- Contexto: Admin logado
- Headers/ambiente: Completo

**Recuperação de Senha:**
- Executado via página pública (`forgot-password.php`)
- Contexto: Usuário não logado
- Headers/ambiente: Pode estar diferente

**Possíveis diferenças:**
- `APP_URL` pode estar diferente (afeta link do email)
- Timeout do servidor pode ser menor em requisições públicas
- Firewall/proxy pode bloquear requisições SMTP de páginas públicas

---

### 3. Erro na Construção da URL do Reset

**Código (Mailer.php, linha 91-100):**
```php
$baseUrl = defined('APP_URL') ? APP_URL : '';
if (empty($baseUrl)) {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    $baseUrl = $protocol . '://' . $host . ($scriptDir !== '/' ? $scriptDir : '');
}

$resetUrl = rtrim($baseUrl, '/') . '/reset-password.php?token=' . urlencode($token);
```

**Problema potencial:**
- Se `APP_URL` não estiver definido corretamente em produção, a URL pode estar errada
- Se a URL estiver errada, o email pode ser rejeitado pelo servidor SMTP como spam
- Ou o email pode ser enviado mas com link inválido

---

### 4. Exceção Não Capturada

**Código (Mailer.php, linha 120-129):**
```php
} catch (Exception $e) {
    if (LOG_ENABLED) {
        error_log('[MAILER] Erro ao enviar email: ' . $e->getMessage());
    }
    
    return [
        'success' => false,
        'message' => 'Erro ao enviar email: ' . $e->getMessage()
    ];
}
```

**Se houver erro fatal (não Exception):**
- `Error` (PHP 7+) não é capturado por `catch (Exception $e)`
- Deveria ser `catch (Throwable $e)` para capturar todos os erros

---

## ✅ CORREÇÕES NECESSÁRIAS

### Correção 1: Verificar Resultado do Envio

**Arquivo:** `forgot-password.php` (linha 67-78)

**Código atual (PROBLEMÁTICO):**
```php
if ($result['success'] && isset($result['token']) && $result['token']) {
    $emailTo = $result['user_email'] ?? null;
    
    if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        $emailResult = Mailer::sendPasswordResetEmail($emailTo, $result['token'], $requestedType);
        
        // ❌ PROBLEMA: Não verifica $emailResult
        $success = $result['message'];
        $maskedDestination = $result['masked_destination'];
    }
}
```

**Código corrigido:**
```php
if ($result['success'] && isset($result['token']) && $result['token']) {
    $emailTo = $result['user_email'] ?? null;
    
    if ($emailTo && filter_var($emailTo, FILTER_VALIDATE_EMAIL)) {
        $emailResult = Mailer::sendPasswordResetEmail($emailTo, $result['token'], $requestedType);
        
        // ✅ CORREÇÃO: Verificar resultado do envio
        if ($emailResult['success']) {
            // Email enviado com sucesso
            $success = $result['message'];
            $maskedDestination = $result['masked_destination'];
        } else {
            // Email falhou - logar e informar usuário
            if (LOG_ENABLED) {
                error_log('[FORGOT_PASSWORD] Falha ao enviar email: ' . ($emailResult['message'] ?? 'Erro desconhecido'));
            }
            
            // Mostrar mensagem específica se SMTP não configurado
            if (isset($emailResult['smtp_configured']) && !$emailResult['smtp_configured']) {
                $error = 'Erro ao enviar email: SMTP não configurado. Entre em contato com a Secretaria.';
            } else {
                // Manter mensagem neutra mas logar erro
                $success = $result['message']; // Mostrar como se tivesse enviado (segurança)
                $maskedDestination = $result['masked_destination'];
                // Log detalhado para admin investigar
                if (LOG_ENABLED) {
                    error_log('[FORGOT_PASSWORD] Email falhou silenciosamente - Token gerado mas não enviado. Email: ' . $emailTo);
                }
            }
        }
    }
}
```

---

### Correção 2: Melhorar Captura de Erros no Mailer

**Arquivo:** `includes/Mailer.php` (linha 120)

**Código atual:**
```php
} catch (Exception $e) {
```

**Código corrigido:**
```php
} catch (Throwable $e) { // Captura Exception e Error (PHP 7+)
```

---

### Correção 3: Adicionar Log Detalhado

**Arquivo:** `includes/Mailer.php` (adicionar após linha 110)

**Código a adicionar:**
```php
// Enviar via SMTP
$result = self::sendSMTP($to, $subject, $htmlBody, $textBody);

// ✅ CORREÇÃO: Log detalhado do resultado
if (LOG_ENABLED) {
    if ($result['success']) {
        error_log(sprintf(
            '[MAILER] Email de recuperação enviado - To: %s, Type: %s, Success: true',
            $to,
            $type
        ));
    } else {
        error_log(sprintf(
            '[MAILER] Email de recuperação FALHOU - To: %s, Type: %s, Error: %s',
            $to,
            $type,
            $result['message'] ?? 'Erro desconhecido'
        ));
    }
}
```

---

## 🧪 TESTES DE VALIDAÇÃO

### Teste 1: Verificar Logs Após Tentativa de Recuperação

**Passos:**
1. Solicitar recuperação de senha para um aluno com email válido
2. Verificar `logs/php_errors.log` imediatamente após
3. Procurar por:
   - `[MAILER] Email de recuperação enviado` (sucesso)
   - `[MAILER] Email de recuperação FALHOU` (falha)
   - `[MAILER] Erro ao enviar email` (exceção)

**Resultado esperado:**
- Se email foi enviado: log de sucesso
- Se email falhou: log de falha com motivo

---

### Teste 2: Comparar Teste SMTP vs Recuperação

**Passos:**
1. Fazer teste SMTP no painel admin (deve funcionar)
2. Solicitar recuperação de senha (não funciona)
3. Comparar logs de ambos

**Diferenças a verificar:**
- Headers HTTP diferentes?
- `APP_URL` diferente?
- Timeout diferente?
- Erro específico na recuperação?

---

### Teste 3: Verificar URL Gerada

**Passos:**
1. Adicionar log temporário em `Mailer.php` linha 100:
   ```php
   error_log('[MAILER] URL de reset gerada: ' . $resetUrl);
   ```
2. Solicitar recuperação
3. Verificar se URL está correta

**Resultado esperado:**
- URL deve ser completa e válida
- Exemplo: `https://seu-dominio.com/reset-password.php?token=...`

---

## 📊 CHECKLIST DE DIAGNÓSTICO

### ✅ O que já foi verificado:

- [x] Teste SMTP funciona (prova que SMTP está configurado)
- [x] Dados do usuário estão corretos (email válido)
- [x] Token é gerado (mensagem de sucesso aparece)
- [x] Código chama `Mailer::sendPasswordResetEmail()`

### ❓ O que precisa ser verificado:

- [ ] Logs mostram erro específico ao enviar email de recuperação?
- [ ] `$emailResult['success']` é `false` quando recuperação falha?
- [ ] `APP_URL` está definido corretamente em produção?
- [ ] URL de reset está sendo gerada corretamente?
- [ ] Há diferença entre contexto de teste e recuperação?
- [ ] Exceção está sendo lançada mas não capturada?

---

## 🎯 PLANO DE AÇÃO IMEDIATO

### Passo 1: Adicionar Verificação de Resultado (CRÍTICO)

**Arquivo:** `forgot-password.php` linha 73

**Ação:** Verificar `$emailResult['success']` antes de exibir mensagem de sucesso.

**Impacto:** Alto - Resolve o problema de feedback incorreto ao usuário.

---

### Passo 2: Adicionar Logs Detalhados

**Arquivo:** `includes/Mailer.php`

**Ação:** Adicionar logs antes e depois do envio, incluindo resultado.

**Impacto:** Médio - Facilita diagnóstico futuro.

---

### Passo 3: Melhorar Tratamento de Erros

**Arquivo:** `includes/Mailer.php` linha 120

**Ação:** Mudar `catch (Exception $e)` para `catch (Throwable $e)`.

**Impacto:** Médio - Captura mais tipos de erro.

---

### Passo 4: Validar URL de Reset

**Arquivo:** `includes/Mailer.php` linha 100

**Ação:** Adicionar validação e log da URL gerada.

**Impacto:** Baixo - Garante que link está correto.

---

## 📝 CONCLUSÃO

**Causa raiz mais provável:**
- O email está sendo tentado enviar, mas está falhando silenciosamente.
- O código não verifica o resultado do envio (`$emailResult['success']`).
- Mensagem de sucesso é exibida mesmo quando o email falha.

**Evidências:**
- Teste SMTP funciona (mesmo método, contexto diferente)
- Token é gerado (prova que fluxo chega até o envio)
- Email não chega (prova que envio falha)
- Mensagem de sucesso aparece (prova que erro não é tratado)

**Próxima ação:**
1. Implementar verificação de `$emailResult['success']` em `forgot-password.php`
2. Adicionar logs detalhados no `Mailer.php`
3. Verificar logs após implementação para identificar erro específico
4. Corrigir erro específico identificado nos logs

---

**Documento gerado em:** 2025-12-17  
**Próxima revisão:** Após implementação das correções
