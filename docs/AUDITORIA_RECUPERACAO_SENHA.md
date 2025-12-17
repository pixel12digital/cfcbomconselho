# Auditoria: Sistema de Recuperação de Senha

**Data:** 2025-01-XX  
**Objetivo:** Verificar se existe fluxo de "Esqueci minha senha / Recuperar senha" implementado para todos os tipos de acesso (Admin, Secretaria, Instrutor, Aluno) e mapear se está completo e funcional.

---

## Resumo Executivo

❌ **CONCLUSÃO PRINCIPAL:** **NÃO existe sistema de recuperação de senha público/autônomo para usuários finais.**

**Estado Atual:**
- ✅ Existe link "Esqueci minha senha" na tela de login (admin/secretaria/instrutor)
- ❌ Link aponta para `href="#"` (não funciona, apenas UI)
- ✅ Existe redefinição de senha pelo **Administrador** no painel admin (não é recuperação pública)
- ❌ Não existe fluxo público de recuperação (token, email, reset)
- ❌ Não existe tabela de tokens de reset no banco
- ❌ Não existe envio real de email (apenas simulação/log)

**Funcionalidade Existente:**
- **Apenas** redefinição de senha pelo admin no painel administrativo (`admin/pages/usuarios.php`)
- Requer que admin esteja logado e tenha permissão para gerenciar usuários
- Gera senha temporária ou permite admin definir senha manualmente
- **Não é recuperação de senha** - é ferramenta administrativa

---

## Parte 1: Auditoria Visual (O que o usuário vê)

### Matriz de Verificação por Tipo de Login

| Tipo de Login | URL | Link "Esqueci" existe? | Link aponta para | Observações |
|--------------|-----|----------------------|------------------|-------------|
| **Admin** | `login.php?type=admin` | ✅ SIM | `href="#"` | Link existe mas não funciona (apenas visual) |
| **Secretaria** | `login.php?type=secretaria` | ✅ SIM | `href="#"` | Link existe mas não funciona (apenas visual) |
| **Instrutor** | `login.php?type=instrutor` | ✅ SIM | `href="#"` | Link existe mas não funciona (apenas visual) |
| **Aluno** | `login.php?type=aluno` | ❌ NÃO | N/A | Aluno não tem link "Esqueci minha senha" |

**Localização do link:**
- **Arquivo:** `login.php` linha 838
- **Código:** `<a href="#" class="forgot-password">Esqueci minha senha</a>`
- **Aparece apenas quando:** `$userType !== 'aluno'` (linha 832)
- **Resultado:** Link não funcional (apenas UI)

### Detalhamento Visual

#### Para Admin/Secretaria/Instrutor:

**Tela de Login:**
- ✅ Exibe link "Esqueci minha senha" abaixo do checkbox "Lembrar de mim"
- ⚠️ Link não clicável (aponta para `#`)
- ❌ Não há tela de recuperação
- ❌ Não há campo para inserir email
- ❌ Não há mensagem de confirmação
- ❌ Não há proteção anti-enumeração

#### Para Aluno:

**Tela de Login:**
- ❌ Não exibe link "Esqueci minha senha"
- ❌ Login usa CPF (não email), então recuperação seria mais complexa
- ❌ Não há tela de recuperação específica para aluno

---

## Parte 2: Auditoria Técnica (Como funciona por trás)

### 2.1. Busca de Arquivos Relacionados

**Arquivos Encontrados:**
- ❌ Nenhum arquivo `forgot_password.php`
- ❌ Nenhum arquivo `reset_password.php`
- ❌ Nenhum arquivo `recover.php` ou similar
- ❌ Nenhum arquivo público de recuperação de senha

**Arquivos Relacionados (mas não são recuperação pública):**
- ✅ `admin/pages/usuarios.php` - Modal de redefinição de senha pelo admin
- ✅ `admin/api/usuarios.php` - API de redefinição de senha (requer autenticação admin)
- ✅ `includes/CredentialManager.php` - Gerencia senhas temporárias (uso interno)

### 2.2. Endpoint de Solicitar Recuperação

**Status:** ❌ **NÃO EXISTE**

**O que existe (não é recuperação pública):**
- `admin/api/usuarios.php` endpoint `POST` com `action=reset_password`
- Requer autenticação como admin/secretaria
- Requer `user_id` do usuário a ser resetado
- Não é recuperação - é ferramenta administrativa

**O que não existe:**
- ❌ Endpoint público para solicitar recuperação
- ❌ Endpoint que aceita email/CPF para gerar token
- ❌ Validação de usuário existente sem revelar
- ❌ Geração de token de reset
- ❌ Armazenamento de token no banco

### 2.3. Estrutura de Banco de Dados

**Tabelas Verificadas:**

#### Tabela `usuarios`:
- ✅ Campo `senha` (VARCHAR(255)) - Hash bcrypt
- ✅ Campo `email` (VARCHAR(100)) - Para admin/secretaria/instrutor
- ✅ Campo `cpf` (VARCHAR(14)) - Para aluno
- ❌ **NÃO existe** campo `reset_token`
- ❌ **NÃO existe** campo `reset_token_expires_at`
- ✅ Campo `precisa_trocar_senha` (TINYINT(1)) - Flag de troca obrigatória (usado após reset pelo admin)

#### Tabela `sessoes`:
- ✅ Usada apenas para tokens de "lembrar-me" (30 dias)
- ❌ **NÃO é usada** para tokens de reset de senha

#### Tabelas de Reset:
- ❌ **NÃO existe** tabela `password_resets`
- ❌ **NÃO existe** tabela `reset_tokens`
- ❌ **NÃO existe** tabela `senha_resets`

**Conclusão:** Não há estrutura no banco para armazenar tokens de recuperação.

### 2.4. Geração e Armazenamento de Token

**Status:** ❌ **NÃO EXISTE**

**O que seria necessário:**
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
);
```

**Status Atual:** Tabela não existe, token não é gerado, não há fluxo de recuperação.

### 2.5. Envio de Email

**Status:** ⚠️ **SIMULADO (não envia email real)**

**Arquivo:** `includes/CredentialManager.php` linha 230-244

**Método:** `sendCredentials($email, $senha, $tipo)`

**Código Atual:**
```php
public static function sendCredentials($email, $senha, $tipo) {
    // Aqui você implementaria o envio real de email
    // Por enquanto, vamos apenas logar as credenciais
    
    $message = "=== CREDENCIAIS DE ACESSO ===\n";
    $message .= "Tipo: " . ucfirst($tipo) . "\n";
    $message .= "Email: " . $email . "\n";
    $message .= "Senha temporária: " . $senha . "\n";
    $message .= "IMPORTANTE: Altere sua senha no primeiro acesso!\n";
    $message .= "========================\n";
    
    error_log($message);
    
    return true;
}
```

**Observações:**
- ❌ Não envia email real (apenas log)
- ❌ Não usa SMTP configurado
- ❌ Não usa PHPMailer ou mail()
- ⚠️ Configuração SMTP existe em `includes/config.php` mas não é usada:
  ```php
  define('SMTP_HOST', 'smtp.hostinger.com');
  define('SMTP_PORT', 587);
  define('SMTP_USER', 'seu_email@seudominio.com'); // Placeholder
  define('SMTP_PASS', 'sua_senha_smtp'); // Placeholder
  ```

### 2.6. Página de Redefinição

**Status:** ❌ **NÃO EXISTE**

**O que existe (não é recuperação pública):**
- Modal no painel admin (`admin/pages/usuarios.php`) - Requer autenticação
- API `admin/api/usuarios.php` - Requer autenticação

**O que não existe:**
- ❌ Página pública `reset-password.php?token=XXX`
- ❌ Validação de token expirado
- ❌ Validação de token inválido
- ❌ Invalidação de token após uso
- ❌ Formulário para nova senha (público)

### 2.7. Fluxo de Redefinição pelo Admin (Funcionalidade Existente)

**Localização:** `admin/api/usuarios.php` linha 96-330

**Endpoint:** `POST /admin/api/usuarios.php`

**Requisição:**
```json
{
    "action": "reset_password",
    "user_id": 123,
    "mode": "auto" | "manual",
    "nova_senha": "...", // Apenas se mode=manual
    "nova_senha_confirmacao": "..." // Apenas se mode=manual
}
```

**Fluxo:**
1. Valida autenticação (admin/secretaria)
2. Valida `user_id` existe
3. Se `mode=auto`: gera senha temporária
4. Se `mode=manual`: valida senha (mínimo 8 caracteres, confirmação)
5. Faz hash da senha com `password_hash($senha, PASSWORD_DEFAULT)`
6. Atualiza `usuarios.senha`
7. Marca `precisa_trocar_senha = 1` (se coluna existir)
8. Se for aluno, sincroniza também na tabela `alunos.senha`
9. Log de auditoria: `[PASSWORD_RESET] admin_id=X, user_id=Y, mode=auto|manual, ...`
10. Chama `CredentialManager::sendCredentials()` (apenas log, não envia email)
11. Retorna senha temporária na resposta (apenas modo auto)

**Observações:**
- ✅ Funcional para admin resetar senha de qualquer usuário
- ❌ **NÃO é recuperação pública** - requer autenticação
- ❌ Usuário não pode solicitar própria recuperação

### 2.8. Logs e Segurança

#### Logs de Auditoria

**Existente:**
- ✅ Log de redefinição pelo admin: `[PASSWORD_RESET] admin_id=X, user_id=Y, mode=auto|manual, timestamp=Z, ip=W`
- **Localização:** `admin/api/usuarios.php` linha 283-293

**Não Existente (para recuperação pública):**
- ❌ Log de solicitação de recuperação
- ❌ Log de token gerado
- ❌ Log de tentativa de reset com token inválido
- ❌ Log de tentativa de reset com token expirado

#### Proteções de Segurança

**Não Implementadas:**
- ❌ Proteção anti-enumeração (mensagem neutra)
- ❌ Rate limiting / cooldown (evitar spam)
- ❌ Token com hash (tokens em texto puro são risco)
- ❌ Expiração de token (30-60 min)
- ❌ Uso único de token (one-time)
- ❌ Validação de força de nova senha

**Configurações Existentes (não usadas para recuperação):**
- ✅ `MAX_LOGIN_ATTEMPTS` em `includes/config.php` (linha 76)
- ✅ `LOGIN_TIMEOUT` em `includes/config.php` (linha 77)
- ⚠️ Apenas para tentativas de login, não para recuperação

---

## Parte 3: Teste Funcional (Análise Lógica)

### Teste A: Admin solicitando recuperação própria

**Fluxo:**
1. Admin acessa `login.php?type=admin`
2. Clica em "Esqueci minha senha"
3. ❌ Link não funciona (aponta para `#`)
4. ❌ Não há tela de recuperação
5. ❌ Não há como solicitar recuperação

**Resultado:** ❌ **IMPOSSÍVEL** - Não há fluxo implementado

### Teste B: Secretaria solicitando recuperação própria

**Fluxo:** Mesmo que Teste A

**Resultado:** ❌ **IMPOSSÍVEL** - Não há fluxo implementado

### Teste C: Instrutor solicitando recuperação própria

**Fluxo:** Mesmo que Teste A

**Resultado:** ❌ **IMPOSSÍVEL** - Não há fluxo implementado

### Teste D: Aluno solicitando recuperação própria

**Fluxo:**
1. Aluno acessa `login.php?type=aluno`
2. ❌ Não há link "Esqueci minha senha" (não aparece para alunos)
3. ❌ Não há tela de recuperação

**Resultado:** ❌ **IMPOSSÍVEL** - Não há fluxo implementado

### Teste E: Admin resetando senha de outro usuário (funcionalidade existente)

**Fluxo:**
1. Admin loga no sistema
2. Acessa `admin/index.php?page=usuarios`
3. Clica no botão "Senha" de um usuário
4. Seleciona modo (auto/manual)
5. Confirma redefinição
6. ✅ Senha é resetada
7. ✅ Senha temporária é exibida (modo auto)
8. ⚠️ Email não é enviado (apenas log)

**Resultado:** ✅ **FUNCIONAL** - Mas não é recuperação pública

---

## Matriz Final por Perfil

| Perfil | Link "Esqueci" existe? | Fluxo existe no backend? | Envio existe? | Reset funciona? | Observações |
|--------|----------------------|-------------------------|---------------|----------------|-------------|
| **Admin** | ✅ SIM (não funciona) | ❌ NÃO | ❌ NÃO | ❌ NÃO | Link aponta para `#` |
| **Secretaria** | ✅ SIM (não funciona) | ❌ NÃO | ❌ NÃO | ❌ NÃO | Link aponta para `#` |
| **Instrutor** | ✅ SIM (não funciona) | ❌ NÃO | ❌ NÃO | ❌ NÃO | Link aponta para `#` |
| **Aluno** | ❌ NÃO | ❌ NÃO | ❌ NÃO | ❌ NÃO | Não tem link |

**Funcionalidade Administrativa:**
- ✅ Admin pode resetar senha de qualquer usuário (via painel admin)
- ⚠️ Não é recuperação pública - requer que admin esteja logado

---

## Arquivos e Pontos do Código

### Arquivos Envolvidos

#### 1. Interface (UI):
- **`login.php`** linha 838
  - Link "Esqueci minha senha" (não funcional)
  - Aparece apenas para admin/secretaria/instrutor
  - Não aparece para aluno

#### 2. Redefinição pelo Admin (ferramenta administrativa):
- **`admin/pages/usuarios.php`**
  - Modal de redefinição de senha (linha 545+)
  - Funções JavaScript: `showResetPasswordModal()`, `confirmResetPassword()`
  
- **`admin/api/usuarios.php`**
  - Endpoint `POST` com `action=reset_password` (linha 96-330)
  - Processa redefinição (auto/manual)
  - Log de auditoria
  - Chama `CredentialManager::sendCredentials()` (apenas log)

#### 3. Gerenciamento de Credenciais:
- **`includes/CredentialManager.php`**
  - `generateTemporaryPassword()` - Gera senha temporária
  - `sendCredentials()` - **Simula** envio de email (apenas log)

#### 4. Configuração:
- **`includes/config.php`**
  - Configurações SMTP (linha 84-87) - Placeholders, não usadas
  - Rate limiting (linha 76-77) - Apenas para login

### Responsabilidade de Cada Arquivo

| Arquivo | Responsabilidade | Status |
|---------|------------------|--------|
| `login.php` | Exibir link "Esqueci minha senha" | ✅ Exibe (não funciona) |
| `admin/pages/usuarios.php` | Modal de redefinição pelo admin | ✅ Funcional |
| `admin/api/usuarios.php` | API de redefinição pelo admin | ✅ Funcional |
| `includes/CredentialManager.php` | Geração de senha temporária | ✅ Funcional |
| `includes/CredentialManager.php` | Envio de email | ❌ Apenas log |

---

## Tabelas/Campos no Banco

### Estrutura Existente

**Tabela `usuarios`:**
- `id` - ID do usuário
- `email` - Email (admin/secretaria/instrutor)
- `cpf` - CPF (aluno)
- `senha` - Hash bcrypt (VARCHAR(255))
- `precisa_trocar_senha` - Flag de troca obrigatória (TINYINT(1), pode não existir)

**Tabela `sessoes`:**
- Usada apenas para tokens de "lembrar-me" (30 dias)
- Não é usada para tokens de reset

### Estrutura Necessária (não existe)

**Tabela `password_resets` (NECESSÁRIA mas não existe):**
```sql
CREATE TABLE password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    token VARCHAR(255) UNIQUE NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_email (email),
    INDEX idx_expires (expires_at)
);
```

**Observações:**
- Token deveria ser hasheado (SHA256) antes de armazenar
- Expiração recomendada: 30-60 minutos
- Campo `used` previne reuso do token

---

## Riscos e Recomendações

### Riscos Identificados

1. **Usuários não conseguem recuperar senha sozinhos**
   - Dependem de admin para resetar
   - Risco operacional alto

2. **Email não configurado/enviado**
   - Mesmo redefinição pelo admin não envia email real
   - Senha temporária apenas exibida no modal (admin precisa copiar)

3. **Aluno sem opção de recuperação**
   - Não há link "Esqueci minha senha" para alunos
   - Login por CPF complica recuperação (precisa identificar sem email obrigatório)

4. **Sem proteção anti-enumeração**
   - Se implementado, deve retornar mesma mensagem para email existente ou não

5. **Sem rate limiting para recuperação**
   - Risco de spam/abuso se implementado

### Recomendações (Baixo Risco)

#### 1. Implementação Mínima Segura

**Arquivos a criar:**
- `forgot-password.php` - Tela para solicitar recuperação
- `reset-password.php` - Tela para redefinir senha (com token)
- `includes/PasswordReset.php` - Classe para gerenciar reset

**Fluxo Recomendado:**

**a) Solicitação (`forgot-password.php`):**
- Recebe email/CPF
- Valida se existe (sem revelar)
- Gera token único (32 bytes, hex)
- Hash do token (SHA256) antes de salvar
- Salva em `password_resets` com expiração (30 min)
- Envia email com link: `reset-password.php?token={token_original}`
- Mensagem neutra: "Se o email existir, enviaremos instruções"
- Rate limit: 1 solicitação por email a cada 5 minutos

**b) Redefinição (`reset-password.php`):**
- Recebe token via GET
- Valida token (hash, expiração, não usado)
- Exibe formulário (nova senha + confirmação)
- Valida força da senha (mínimo 8 caracteres)
- Atualiza hash da senha
- Marca token como usado
- Invalida todos outros tokens do mesmo email
- Redireciona para login com mensagem de sucesso

**c) Segurança:**
- Token em texto puro apenas no email
- Token armazenado com hash no banco
- Expiração: 30 minutos
- Uso único (one-time)
- Rate limiting por IP e email
- Logs de auditoria

#### 2. Para Aluno (CPF)

**Opcional - Duas abordagens:**

**Opção A (Recomendada):**
- Usar email do aluno (se cadastrado)
- Se não tiver email, exibir instrução para contatar CFC

**Opção B (Mais Complexa):**
- Validar CPF + Data de Nascimento
- Enviar email ou SMS (se cadastrado)
- Mais complexo e menos seguro

#### 3. Configuração de Email

**Necessário:**
- Configurar SMTP real em `includes/config.php`
- Implementar classe/envio real de email
- Template HTML para email de recuperação
- Assunto: "Recuperação de Senha - CFC Bom Conselho"

#### 4. Padronização de Mensagens

**Mensagens recomendadas:**
- Solicitação: "Se o email informado existir em nossa base, você receberá instruções para redefinir sua senha."
- Email enviado: "Clique no link abaixo para redefinir sua senha (válido por 30 minutos):"
- Token inválido: "Link inválido ou expirado. Solicite uma nova recuperação."
- Senha alterada: "Senha alterada com sucesso. Você pode fazer login agora."

---

## Conclusão Final

### Status Geral: ❌ **NÃO IMPLEMENTADO**

**Cenário Identificado:** **(C) Não existe e precisa desenhar implementação mínima mais segura**

**Resumo:**
- ✅ Existe UI (link "Esqueci minha senha") mas não funciona
- ✅ Existe redefinição pelo admin (ferramenta administrativa)
- ❌ **NÃO existe** recuperação pública/autônoma
- ❌ **NÃO existe** geração de tokens
- ❌ **NÃO existe** envio real de email
- ❌ **NÃO existe** estrutura no banco para tokens
- ❌ **NÃO existe** página pública de reset

**Próximos Passos Sugeridos:**
1. Criar tabela `password_resets`
2. Implementar `forgot-password.php` (solicitação)
3. Implementar `reset-password.php` (redefinição)
4. Implementar classe `PasswordReset` para gerenciar tokens
5. Configurar envio real de email (SMTP)
6. Implementar rate limiting
7. Adicionar logs de auditoria
8. Testar fluxo completo para cada perfil

**Prioridade:**
- 🔴 Alta - Usuários não conseguem recuperar senha sozinhos
- 🟡 Média - Aluno sem opção de recuperação
- 🟢 Baixa - Melhorar mensagens e UX

---

**Arquivos Inspecionados (Sem Alterações):**
1. `login.php` - Tela de login e link "Esqueci minha senha"
2. `admin/pages/usuarios.php` - Modal de redefinição pelo admin
3. `admin/api/usuarios.php` - API de redefinição pelo admin
4. `includes/CredentialManager.php` - Gerenciamento de credenciais
5. `includes/config.php` - Configurações SMTP
6. `install.php` - Estrutura do banco de dados
7. Busca por arquivos: `forgot*.php`, `reset*.php`, `recover*.php` (nenhum encontrado)
