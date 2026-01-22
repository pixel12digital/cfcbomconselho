# Auditoria: SMTP Salvar Retornando HTML em vez de JSON

**Data da Auditoria:** 2025-12-17  
**Objetivo:** Identificar causa raiz do erro onde "Salvar SMTP" retorna HTML/erro em vez de JSON  
**Status:** 🔍 INVESTIGAÇÃO COMPLETA (sem alterações de código)

---

## 1. MAPEAMENTO DO FLUXO COMPLETO

### 1.1. Arquivo Frontend (Página)

**Arquivo:** `admin/pages/configuracoes-smtp.php`

**Linha do JavaScript que dispara "Salvar":**
- **Linha 521-524:** Event listener do formulário
- **Linha 559-627:** Função `saveConfig()`
- **Linha 585:** URL chamada: `fetch('api/smtp-config.php', ...)`
- **Linha 590:** Payload: `JSON.stringify(data)` com `Content-Type: application/json`

**Estrutura do Payload:**
```javascript
{
    action: 'save',
    host: 'smtp.hostinger.com',
    port: 587,
    user: 'email@dominio.com',
    pass: 'senha123',  // ou omitido se vazio
    encryption_mode: 'tls',
    from_name: 'Nome Remetente',
    from_email: 'noreply@dominio.com'
}
```

**Método HTTP:** POST  
**Headers enviados:**
- `Content-Type: application/json`
- Body: JSON stringificado

---

### 1.2. Arquivo Backend (API)

**Arquivo:** `admin/api/smtp-config.php`

**Linha de entrada:** Linha 1  
**Estrutura de proteção JSON:**
- **Linha 10-19:** Função `sendJsonError()` para garantir JSON em erros
- **Linha 22-27:** `register_shutdown_function()` para capturar erros fatais
- **Linha 30:** `ob_start()` - Inicia output buffering
- **Linha 33:** `ini_set('display_errors', 0)` - Desabilita exibição de erros
- **Linha 37:** `header('Content-Type: application/json')` - Define header JSON
- **Linha 43:** `ob_clean()` - Limpa buffer após includes

**Fluxo de processamento POST:**
- **Linha 111-171:** Switch case para POST
- **Linha 113:** Lê `php://input` (JSON)
- **Linha 114:** `json_decode($rawInput, true)`
- **Linha 117-119:** Fallback para `$_POST` se JSON vazio
- **Linha 127:** Extrai `action` do `$data` ou `$_GET`
- **Linha 129:** Se `action === 'save'` → chama `SMTPConfigService::saveConfig()`
- **Linha 145:** Salva configuração
- **Linha 146-148:** Retorna JSON com `ob_end_clean()` + `json_encode()`

**Ações disponíveis:**
- `save` - Salvar configurações (linha 129)
- `test` - Testar envio (linha 150)
- `GET` - Obter configurações (linha 88)

---

### 1.3. Serviço de Configuração

**Arquivo:** `includes/SMTPConfigService.php`

**Método chamado:** `saveConfig($data, $userId)` (linha 53)

**Fluxo interno:**
- **Linha 55:** Obtém instância do banco `db()`
- **Linha 58:** Valida dados via `validateConfig()`
- **Linha 68-83:** Criptografa senha ou mantém atual
- **Linha 87:** Desabilita outras configurações: `$db->update('smtp_settings', ['enabled' => 0], '1=1')`
- **Linha 90-101:** Prepara dados para inserção
- **Linha 103:** `$db->insert('smtp_settings', $configData)`
- **Linha 114-117:** Retorna array `['success' => true, 'message' => '...']`

**Ponto crítico:** Se `$db->insert()` ou `$db->update()` lançar exceção, ela é capturada no `try-catch` da linha 119 e retorna array de erro (não JSON direto).

---

## 2. PONTOS DE FALHA IDENTIFICADOS

### 2.1. Possível Saída HTML Antes do JSON

**Cenário 1: Include emite output**
- Se `config.php`, `database.php`, `auth.php` ou `SMTPConfigService.php` emitirem qualquer output (echo, print, whitespace antes de `<?php`, warnings/notices), o buffer pode não capturar tudo.
- **Proteção:** `ob_clean()` na linha 43 e 52 do `smtp-config.php` tenta limpar, mas pode não ser suficiente se o output vier antes do `ob_start()`.

**Cenário 2: Redirect de autenticação**
- Se `auth.php` ou verificação de login fizer `header('Location: ...')` e `exit`, o JSON nunca será enviado.
- **Proteção:** Linha 66-78 do `smtp-config.php` verifica autenticação e retorna JSON de erro (401/403) via `sendJsonError()`, não redirect.

**Cenário 3: Erro fatal antes do try-catch**
- Se houver erro fatal (ex: classe não encontrada, syntax error) antes do `try-catch` da linha 86, o `register_shutdown_function()` (linha 22) deveria capturar, mas pode não funcionar se o erro for muito cedo.

**Cenário 4: Tabela `smtp_settings` não existe**
- Se a tabela não existir, `SMTPConfigService::getConfig()` (linha 19) lança exceção ao fazer `SELECT FROM smtp_settings`.
- **Proteção:** `SMTPConfigService::getConfig()` tem `try-catch` (linha 38) que retorna `null`, mas `saveConfig()` pode lançar exceção na linha 74 ou 87 se a tabela não existir.

---

### 2.2. Erro no Banco de Dados

**Cenário: Tabela não existe ou estrutura incorreta**
- **Linha 74 de SMTPConfigService.php:** `$db->fetch("SELECT pass_encrypted FROM smtp_settings ...")` - Se tabela não existe, lança exceção.
- **Linha 87:** `$db->update('smtp_settings', ['enabled' => 0], '1=1')` - Se tabela não existe, lança exceção.
- **Linha 103:** `$db->insert('smtp_settings', $configData)` - Se tabela não existe, lança exceção.

**Comportamento esperado:**
- Exceção é capturada no `try-catch` da linha 119 de `SMTPConfigService.php`.
- Retorna array `['success' => false, 'message' => 'Erro ao salvar...']`.
- Esse array é retornado para `smtp-config.php` linha 145.
- `smtp-config.php` linha 147 faz `ob_end_clean()` + `json_encode($result)` + `exit`.

**Se funcionar corretamente:** Deveria retornar JSON mesmo com erro.

---

### 2.3. Output de Warnings/Notices do PHP

**Cenário: PHP emite warnings antes do JSON**
- Se `LOG_ENABLED` não estiver definido, `error_log()` pode emitir warning.
- Se algum `include` tiver whitespace antes de `<?php`, isso vai para o output.
- Se `ini_set()` falhar, pode emitir notice.

**Proteção atual:**
- `ini_set('display_errors', 0)` na linha 33 - Desabilita exibição, mas não impede que warnings sejam enviados ao cliente se `error_reporting` estiver ativo e não houver `ob_clean()` adequado.

---

## 3. ESTRUTURA DO BANCO DE DADOS

### 3.1. Tabela Esperada: `smtp_settings`

**Arquivo de migration:** `docs/scripts/migration-smtp-settings.sql`

**Estrutura esperada:**
```sql
CREATE TABLE IF NOT EXISTS smtp_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    host VARCHAR(255) NOT NULL,
    port INT NOT NULL DEFAULT 587,
    user VARCHAR(255) NOT NULL,
    pass_encrypted TEXT NOT NULL,
    encryption_mode ENUM('tls', 'ssl', 'none') DEFAULT 'tls',
    from_name VARCHAR(255) NULL,
    from_email VARCHAR(255) NULL,
    enabled BOOLEAN DEFAULT TRUE,
    last_test_at TIMESTAMP NULL DEFAULT NULL,
    last_test_status ENUM('ok', 'error') NULL,
    last_test_message VARCHAR(500) NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NULL,
    FOREIGN KEY (updated_by) REFERENCES usuarios(id) ON DELETE SET NULL,
    INDEX idx_enabled (enabled),
    INDEX idx_updated_at (updated_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Colunas críticas para `saveConfig()`:**
- `host` - Obrigatório
- `port` - Obrigatório
- `user` - Obrigatório
- `pass_encrypted` - Obrigatório (criptografado)
- `encryption_mode` - Default 'tls'
- `enabled` - Default TRUE
- `updated_by` - ID do usuário
- `updated_at` - Auto-atualizado

**Validação necessária:**
- ✅ Tabela existe?
- ✅ Todas as colunas existem?
- ✅ Foreign key `updated_by` → `usuarios(id)` existe?

---

## 4. LOGS E EVIDÊNCIAS

### 4.1. Logs do PHP (Local)

**Comando executado:**
```powershell
Get-Content "logs/php_errors.log" -Tail 200 | Select-String -Pattern "SMTP_CONFIG|smtp-config|smtp_settings"
```

**Resultado:** Nenhum log encontrado relacionado a SMTP nas últimas 200 linhas.

**Interpretação:**
- Pode indicar que o erro não está sendo logado (exceção silenciosa?).
- Ou o erro está ocorrendo antes de chegar ao código que faz `error_log()`.
- Ou os logs estão em outro arquivo/local.

---

### 4.2. Verificação de Estrutura do Banco

**Script de verificação:** `admin/tools/executar-migration-smtp-settings.php`

**Como verificar:**
1. Acessar: `http://localhost/cfc-bom-conselho/admin/tools/executar-migration-smtp-settings.php`
2. Verificar se a tabela existe e se a estrutura está correta.

**Próximo passo:** Executar este script e verificar se a tabela existe no banco de produção.

---

## 5. COMPARAÇÃO: "Testar SMTP" vs "Salvar SMTP"

### 5.1. Fluxo "Testar SMTP"

**Frontend (configuracoes-smtp.php):**
- **Linha 629-690:** Função `testSMTP()`
- **Linha 649:** `fetch('api/smtp-config.php', ...)`
- **Linha 654:** Payload: `{ action: 'test', test_email: '...' }`

**Backend (smtp-config.php):**
- **Linha 150:** Se `action === 'test'`
- **Linha 152:** Obtém `test_email` do payload ou `$currentUser['email']`
- **Linha 163:** Chama `SMTPConfigService::testConfig($testEmail, $userId)`
- **Linha 164-166:** Retorna JSON

**Serviço (SMTPConfigService.php):**
- **Linha 138:** `testConfig($testEmail, $userId)`
- **Linha 140:** Chama `self::getConfig()` - **LÊ do banco**
- **Linha 158:** Chama `Mailer::sendPasswordResetEmail()` - **USA configurações do banco**
- **Linha 164-172:** Atualiza `last_test_*` no banco
- **Linha 174-184:** Retorna array de sucesso/erro

**Diferença crítica:**
- "Testar" **LÊ** configurações do banco (via `getConfig()`).
- "Salvar" **ESCREVE** no banco (via `insert()`).

**Se "Testar" funciona mas "Salvar" não:**
- Indica que a tabela **EXISTE** e pode ser **LIDA**, mas pode haver problema na **ESCRITA** (INSERT/UPDATE).

---

### 5.2. Fluxo "Salvar SMTP"

**Diferenças:**
- "Salvar" faz `INSERT` (linha 103 de SMTPConfigService.php).
- "Salvar" faz `UPDATE` para desabilitar outras (linha 87).
- "Salvar" valida dados antes (linha 58).

**Possíveis falhas:**
1. **Foreign key constraint:** Se `updated_by` não existir em `usuarios`, o INSERT falha.
2. **Campo obrigatório NULL:** Se algum campo NOT NULL não for fornecido, o INSERT falha.
3. **Tipo de dado incorreto:** Se `port` não for inteiro, pode falhar.
4. **Exceção não capturada:** Se a exceção do banco não for capturada corretamente, pode vazar HTML.

---

## 6. CAUSA RAIZ PROVÁVEL

### Hipótese Principal: Tabela Não Existe ou Estrutura Incorreta

**Evidências:**
1. ✅ Código tem proteções robustas para retornar JSON (`ob_start()`, `ob_clean()`, `sendJsonError()`, `register_shutdown_function()`).
2. ✅ "Testar SMTP" funciona (lê do banco) - indica que tabela pode existir.
3. ❓ "Salvar SMTP" falha (escreve no banco) - pode ser problema de INSERT/UPDATE.
4. ❓ Logs não mostram erros SMTP - pode indicar erro antes do `error_log()` ou erro silencioso.

**Cenários possíveis:**

**A) Tabela não existe:**
- `SMTPConfigService::saveConfig()` linha 74 ou 87 ou 103 lança exceção.
- Exceção é capturada (linha 119), retorna array de erro.
- Array é convertido para JSON em `smtp-config.php` linha 147.
- **MAS:** Se a exceção for lançada **ANTES** do `ob_clean()` ou se houver output antes, pode vazar HTML.

**B) Foreign key constraint:**
- Se `updated_by` (linha 99) não existir em `usuarios`, o INSERT falha com erro de constraint.
- Exceção é capturada, mas pode não estar sendo logada corretamente.

**C) Output antes do JSON:**
- Se algum `include` (linha 46-49) emitir output (whitespace, warning, notice), mesmo com `ob_clean()`, pode não limpar tudo se o output vier **antes** do `ob_start()`.

---

## 7. PLANO DE CORREÇÃO MÍNIMO

### 7.1. Verificações Imediatas (Sem Alterar Código)

1. **Verificar se tabela existe:**
   - Executar: `admin/tools/executar-migration-smtp-settings.php`
   - Verificar estrutura no phpMyAdmin.

2. **Testar endpoint diretamente:**
   - Acessar: `http://localhost/cfc-bom-conselho/admin/api/smtp-config.php` (GET)
   - Deve retornar JSON: `{"success":true,"config":...,"status":...}` ou erro JSON.

3. **Verificar logs após tentativa de salvar:**
   - Tentar salvar SMTP.
   - Verificar `logs/php_errors.log` imediatamente após.
   - Verificar Network tab do DevTools (status, Content-Type, body).

4. **Verificar se há output antes do JSON:**
   - Adicionar `error_log()` no início de `smtp-config.php` para confirmar que o arquivo está sendo executado.
   - Verificar se há whitespace antes de `<?php` nos includes.

---

### 7.2. Correções Propostas (Para Implementar Após Confirmação)

**Arquivo: `admin/api/smtp-config.php`**

1. **Mover `ob_start()` para o início absoluto:**
   - Colocar `<?php ob_start();` como primeira linha (antes de qualquer coisa).

2. **Adicionar verificação de tabela antes de processar:**
   ```php
   // Verificar se tabela existe antes de processar
   try {
       $db->query("SELECT 1 FROM smtp_settings LIMIT 1");
   } catch (Exception $e) {
       sendJsonError('Tabela smtp_settings não existe. Execute a migration primeiro.', 500);
   }
   ```

3. **Melhorar tratamento de exceções do banco:**
   - Capturar exceções específicas (PDOException, Exception) e garantir JSON sempre.

4. **Adicionar log detalhado antes de retornar:**
   ```php
   if (LOG_ENABLED) {
       error_log('[SMTP_CONFIG_API] Retornando JSON: ' . json_encode($result));
   }
   ```

**Arquivo: `includes/SMTPConfigService.php`**

1. **Verificar tabela antes de operações:**
   - Adicionar método `tableExists()` e verificar antes de `saveConfig()`.

2. **Melhorar mensagens de erro:**
   - Incluir código de erro SQL na mensagem (se disponível) para debug.

---

## 8. CHECKLIST DE VALIDAÇÃO

### ✅ O que já foi verificado:

- [x] Estrutura do código frontend (JavaScript)
- [x] Estrutura do código backend (API)
- [x] Estrutura do serviço (SMTPConfigService)
- [x] Proteções de JSON (ob_start, ob_clean, sendJsonError)
- [x] Estrutura esperada do banco (migration SQL)
- [x] Diferença entre "Testar" e "Salvar"

### ❓ O que precisa ser verificado (com evidências):

- [ ] Tabela `smtp_settings` existe no banco de produção?
- [ ] Estrutura da tabela está correta (todas as colunas)?
- [ ] Foreign key `updated_by` → `usuarios(id)` está funcionando?
- [ ] Logs do PHP mostram erro específico ao salvar?
- [ ] Network tab mostra status code, Content-Type e body da resposta?
- [ ] Há output (whitespace/warnings) antes do JSON nos includes?

---

## 9. PRÓXIMOS PASSOS

1. **Executar verificação de tabela:**
   - Acessar `admin/tools/executar-migration-smtp-settings.php` em produção.
   - Confirmar se tabela existe e estrutura está correta.

2. **Testar endpoint diretamente:**
   - Fazer requisição GET para `admin/api/smtp-config.php` (logado como admin).
   - Verificar se retorna JSON ou HTML.

3. **Capturar evidência do erro:**
   - Abrir DevTools → Network.
   - Tentar salvar SMTP.
   - Capturar: Status code, Response Headers (Content-Type), Response Body (primeiras 30 linhas).

4. **Verificar logs:**
   - Após tentativa de salvar, verificar logs do PHP/servidor.
   - Procurar por erros relacionados a `smtp_settings`, `SMTPConfigService`, `smtp-config.php`.

5. **Comparar local vs produção:**
   - Se funcionar local mas não em produção, comparar:
     - Estrutura do banco
     - Versão do PHP
     - Configurações de erro do PHP (`display_errors`, `error_reporting`)
     - Output buffering do servidor

---

## 10. CONCLUSÃO

**Causa raiz mais provável:**
- Tabela `smtp_settings` não existe no banco de produção, ou estrutura está incorreta, causando exceção no `INSERT`/`UPDATE` que não está sendo capturada corretamente, resultando em HTML de erro do PHP em vez de JSON.

**Evidências que suportam:**
- Código tem proteções robustas para JSON.
- "Testar" funciona (lê banco), "Salvar" falha (escreve banco).
- Logs não mostram erros (pode indicar erro antes do logging).

**Próxima ação:**
- Verificar existência e estrutura da tabela `smtp_settings` no banco de produção.
- Capturar resposta HTTP completa (status, headers, body) do Network tab ao tentar salvar.
- Verificar logs do servidor após tentativa de salvar.

---

**Documento gerado em:** 2025-12-17  
**Próxima revisão:** Após coleta de evidências de produção
