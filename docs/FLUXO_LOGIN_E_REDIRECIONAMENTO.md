# Fluxo de Login e Redirecionamento - Sistema CFC

## Data: 2024
## Status: ✅ DOCUMENTADO E CORRIGIDO

---

## Resumo Executivo

Este documento descreve o fluxo completo de autenticação e redirecionamento do sistema CFC, incluindo como cada tipo de usuário é direcionado após o login e como funciona a verificação de "já logado".

---

## Problema Identificado e Corrigido

### Problema Original

**Sintoma:**
- Instrutor fazia login e era redirecionado para `index.php` (site público) em vez do painel do instrutor
- Após login, ao tentar acessar `login.php`, era redirecionado novamente para `index.php`
- Admin funcionava corretamente

**Causa Raiz:**
- `login.php` não diferenciava entre tipos de usuário no redirecionamento pós-login
- Todos os funcionários (admin, secretaria, instrutor) eram redirecionados para `admin/`
- A verificação "já logado" também não diferenciava tipos

### Solução Implementada

1. **Função Centralizada `redirectAfterLogin()`**
   - Criada em `includes/auth.php` (método da classe `Auth`)
   - Função global `redirectAfterLogin()` também criada para facilitar uso
   - Centraliza toda a lógica de redirecionamento por tipo

2. **Correção em `login.php`**
   - Após login bem-sucedido: usa `redirectAfterLogin($user)`
   - Verificação "já logado": usa `redirectAfterLogin($user)`
   - Garante redirecionamento correto para cada tipo

---

## Arquitetura do Fluxo de Autenticação

### Arquivos Principais

1. **`login.php`**
   - Página de login principal
   - Processa formulário de login
   - Verifica se usuário já está logado
   - Redireciona após login bem-sucedido

2. **`includes/auth.php`**
   - Classe `Auth` com métodos de autenticação
   - Método `login()`: valida credenciais e cria sessão
   - Método `redirectAfterLogin()`: redireciona baseado no tipo
   - Funções globais: `isLoggedIn()`, `getCurrentUser()`, `redirectAfterLogin()`

3. **`admin/index.php`**
   - Painel administrativo (admin e secretaria)
   - Verifica permissões antes de permitir acesso

4. **`instrutor/dashboard.php`**
   - Painel do instrutor
   - Verifica se usuário é do tipo `instrutor`

5. **`aluno/dashboard.php`**
   - Painel do aluno
   - Verifica se usuário é do tipo `aluno`

---

## Fluxo Detalhado

### 1. Acesso à Tela de Login

**URL:** `login.php?type={tipo}`

**Parâmetros:**
- `type=admin` → Portal do CFC (admin/secretaria/instrutor)
- `type=aluno` → Portal do Aluno

**Comportamento:**
- Se `type=aluno`: mostra apenas opção "Aluno"
- Se `type=admin` ou não especificado: mostra opções "Administrador", "Atendente CFC", "Instrutor"

**Código Relevante:**
```php
// login.php linha 24
$userType = $_GET['type'] ?? 'admin';

// login.php linha 534-537
if ($userType === 'aluno' && $type !== 'aluno') continue;
if ($userType !== 'aluno' && $type === 'aluno') continue;
```

---

### 2. Verificação "Já Está Logado"

**Localização:** `login.php` linhas 12-16

**Lógica:**
```php
if (isLoggedIn()) {
    $user = getCurrentUser();
    redirectAfterLogin($user);
}
```

**Comportamento:**
- Se usuário já está logado, não mostra formulário
- Redireciona imediatamente para o painel apropriado
- Usa `redirectAfterLogin()` para garantir destino correto

---

### 3. Processamento do Formulário de Login

**Localização:** `login.php` linhas 27-115

**Fluxo:**

#### 3.1. Login de Aluno
```php
if ($selectedType === 'aluno') {
    // Busca na tabela alunos ou usuarios
    // Valida senha
    // Cria sessão manualmente
    // Redireciona para: aluno/dashboard.php
}
```

#### 3.2. Login de Funcionário (Admin/Secretaria/Instrutor)
```php
else {
    // Usa Auth::login()
    $result = $auth->login($email, $senha, $remember);
    
    if ($result['success']) {
        $user = getCurrentUser();
        redirectAfterLogin($user); // ← REDIRECIONAMENTO CORRETO
    }
}
```

**Método `Auth::login()` (`includes/auth.php` linhas 23-84):**
1. Valida entrada (email/senha não vazios)
2. Verifica bloqueio por tentativas
3. Busca usuário por email/CPF
4. Verifica senha com `password_verify()`
5. Verifica se usuário está ativo
6. Cria sessão com `createSession()`
7. Registra log de auditoria
8. Retorna sucesso com dados do usuário

---

### 4. Redirecionamento Pós-Login

**Função:** `redirectAfterLogin($user = null)`

**Localização:** 
- Método: `includes/auth.php` linha 255
- Função global: `includes/auth.php` linha 695

**Lógica:**
```php
function redirectAfterLogin($user = null) {
    if (!$user) {
        $user = getCurrentUser();
    }
    
    if (!$user) {
        header('Location: /cfc-bom-conselho/login.php');
        exit;
    }
    
    $tipo = strtolower($user['tipo'] ?? '');
    
    switch ($tipo) {
        case 'admin':
        case 'secretaria':
            header('Location: /cfc-bom-conselho/admin/index.php');
            break;
            
        case 'instrutor':
            header('Location: /cfc-bom-conselho/instrutor/dashboard.php');
            break;
            
        case 'aluno':
            header('Location: /cfc-bom-conselho/aluno/dashboard.php');
            break;
            
        default:
            header('Location: /cfc-bom-conselho/login.php');
    }
    
    exit;
}
```

**Mapeamento de Tipos:**
- `admin` → `/cfc-bom-conselho/admin/index.php`
- `secretaria` → `/cfc-bom-conselho/admin/index.php`
- `instrutor` → `/cfc-bom-conselho/instrutor/dashboard.php`
- `aluno` → `/cfc-bom-conselho/aluno/dashboard.php`
- `default` → `/cfc-bom-conselho/login.php`

---

## Proteções de Acesso

### 1. Painel Administrativo (`admin/index.php`)

**Localização:** `admin/index.php` linhas 17-21

**Verificação:**
```php
if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('instrutor'))) {
    header('Location: ../index.php');
    exit;
}
```

**Comportamento:**
- Permite acesso a: `admin`, `secretaria`, `instrutor`
- Bloqueia: `aluno` e usuários não logados
- Redireciona para site público se não autorizado

**Nota:** Instrutores TÊM acesso ao painel administrativo, mas após login são redirecionados para seu próprio painel (`instrutor/dashboard.php`).

### 2. Painel do Instrutor (`instrutor/dashboard.php`)

**Localização:** `instrutor/dashboard.php` linhas 13-18

**Verificação:**
```php
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    header('Location: /login.php');
    exit();
}
```

**Comportamento:**
- Permite acesso APENAS a usuários do tipo `instrutor`
- Bloqueia todos os outros tipos
- Redireciona para login se não autorizado

### 3. Painel do Aluno (`aluno/dashboard.php`)

**Verificação similar ao painel do instrutor, mas para tipo `aluno`**

---

## Links no Site Público

### Link "Portal do CFC"

**Localização:** `index.php` linha 4782

**Código:**
```html
<li><a href="login.php?type=admin" target="_blank">Portal do CFC</a></li>
```

**Comportamento:**
- Abre `login.php?type=admin`
- Mostra opções: Administrador, Atendente CFC, Instrutor
- Cada tipo pode fazer login e será redirecionado corretamente

### Link "Portal do Aluno"

**Localização:** `index.php` linha 4781

**Código:**
```html
<li><a href="login.php?type=aluno" target="_blank">Portal do Aluno</a></li>
```

**Comportamento:**
- Abre `login.php?type=aluno`
- Mostra apenas opção "Aluno"
- Após login, redireciona para `aluno/dashboard.php`

---

## Cenários de Teste

### ✅ Cenário 1: Login como Admin

1. Acessar `login.php?type=admin`
2. Selecionar "Administrador"
3. Informar credenciais de admin
4. Clicar em "Entrar"
5. **Resultado Esperado:** Redirecionado para `/cfc-bom-conselho/admin/index.php`

### ✅ Cenário 2: Login como Instrutor

1. Acessar `login.php?type=admin`
2. Selecionar "Instrutor"
3. Informar credenciais de instrutor
4. Clicar em "Entrar"
5. **Resultado Esperado:** Redirecionado para `/cfc-bom-conselho/instrutor/dashboard.php` ✅ **CORRIGIDO**

### ✅ Cenário 3: Já Logado como Instrutor

1. Estar logado como instrutor
2. Acessar `login.php` ou clicar em "Portal do CFC"
3. **Resultado Esperado:** Redirecionado automaticamente para `/cfc-bom-conselho/instrutor/dashboard.php` ✅ **CORRIGIDO**

### ✅ Cenário 4: Login com Senha Errada

1. Acessar tela de login
2. Informar credenciais incorretas
3. Clicar em "Entrar"
4. **Resultado Esperado:** Permanece na tela de login com mensagem de erro, sem redirecionar

### ✅ Cenário 5: Acesso Direto ao Painel sem Login

1. Tentar acessar `instrutor/dashboard.php` sem estar logado
2. **Resultado Esperado:** Redirecionado para `login.php`

---

## Funções e Métodos Principais

### Funções Globais (`includes/auth.php`)

```php
// Verifica se usuário está logado
function isLoggedIn(): bool

// Obtém dados do usuário logado
function getCurrentUser(): array|null

// Redireciona para painel apropriado
function redirectAfterLogin($user = null): void

// Verifica se usuário tem permissão específica
function hasPermission($permission): bool
```

### Métodos da Classe Auth

```php
// Realiza login
public function login($login, $senha, $remember = false): array

// Realiza logout
public function logout(): void

// Redireciona após login
public function redirectAfterLogin($user = null): void

// Verifica se é admin
public function isAdmin(): bool

// Verifica se é instrutor
public function isInstructor(): bool

// Verifica se é secretaria
public function isSecretary(): bool

// Verifica se é aluno
public function isStudent(): bool
```

---

## Estrutura de Diretórios

```
/cfc-bom-conselho/
├── login.php                    # Página de login principal
├── index.php                    # Site público (com links para login)
├── admin/
│   └── index.php                # Painel administrativo (admin/secretaria)
├── instrutor/
│   └── dashboard.php            # Painel do instrutor
├── aluno/
│   └── dashboard.php            # Painel do aluno
└── includes/
    └── auth.php                 # Sistema de autenticação
```

---

## Tabela de Usuários

**Tabela:** `usuarios`

**Campo de Tipo:** `tipo` (ENUM: `admin`, `secretaria`, `instrutor`, `aluno`)

**Campos Relevantes:**
- `id`: ID do usuário
- `email`: Email de login
- `senha`: Hash da senha (bcrypt)
- `tipo`: Tipo de usuário
- `ativo`: Status ativo/inativo

---

## Logs e Auditoria

### Log de Login

**Localização:** `includes/auth.php` linha 63

**Código:**
```php
if (AUDIT_ENABLED) {
    dbLog($usuario['id'], 'login', 'usuarios', $usuario['id']);
}
```

**Registra:**
- ID do usuário
- Ação: `login`
- Tabela afetada: `usuarios`
- Timestamp automático

### Log de Logout

**Localização:** `includes/auth.php` linha 90

**Código:**
```php
if (isset($_SESSION['user_id']) && AUDIT_ENABLED) {
    dbLog($_SESSION['user_id'], 'logout', 'usuarios', $_SESSION['user_id']);
}
```

---

## Considerações de Segurança

1. **Senhas:**
   - Armazenadas com `password_hash()` (bcrypt)
   - Validadas com `password_verify()`
   - Nunca exibidas em texto plano

2. **Sessões:**
   - Iniciadas automaticamente em `config.php`
   - Timeout configurável
   - Regeneração de ID após login

3. **Proteção CSRF:**
   - Tokens podem ser implementados no futuro
   - Atualmente não implementado

4. **Bloqueio por Tentativas:**
   - Implementado em `Auth::isLocked()`
   - Bloqueia após X tentativas falhas
   - Tempo de bloqueio configurável

---

## Manutenção Futura

### Adicionar Novo Tipo de Usuário

1. Adicionar tipo na tabela `usuarios.tipo` (ENUM)
2. Adicionar case em `redirectAfterLogin()`:
   ```php
   case 'novo_tipo':
       header('Location: /cfc-bom-conselho/novo-tipo/dashboard.php');
       break;
   ```
3. Criar diretório e painel: `/novo-tipo/dashboard.php`
4. Adicionar opção em `login.php` no array `$userTypes`

### Alterar URL de Redirecionamento

**Localização:** `includes/auth.php` método `redirectAfterLogin()`

**Exemplo:** Alterar painel do instrutor:
```php
case 'instrutor':
    header('Location: /cfc-bom-conselho/instrutor/novo-dashboard.php');
    break;
```

---

## Checklist de Validação

Após implementação, validar:

- [x] Admin faz login → vai para `admin/index.php`
- [x] Secretaria faz login → vai para `admin/index.php`
- [x] Instrutor faz login → vai para `instrutor/dashboard.php` ✅ **CORRIGIDO**
- [x] Aluno faz login → vai para `aluno/dashboard.php`
- [x] Admin já logado acessa `login.php` → redirecionado para `admin/index.php`
- [x] Instrutor já logado acessa `login.php` → redirecionado para `instrutor/dashboard.php` ✅ **CORRIGIDO**
- [x] Senha errada → permanece na tela de login com erro
- [x] Acesso direto sem login → redirecionado para `login.php`

---

## Conclusão

✅ **Fluxo de login e redirecionamento corrigido e documentado**

- Função centralizada `redirectAfterLogin()` implementada
- `login.php` corrigido para usar função centralizada
- Instrutor agora é redirecionado corretamente para seu painel
- Documentação completa para manutenção futura

**Arquivos Modificados:**
- `includes/auth.php` - Adicionada função `redirectAfterLogin()`
- `login.php` - Corrigido redirecionamento pós-login e verificação "já logado"

**Arquivos de Documentação:**
- `docs/FLUXO_LOGIN_E_REDIRECIONAMENTO.md` - Este documento

