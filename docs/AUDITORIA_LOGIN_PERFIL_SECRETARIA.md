# Auditoria de Login - Portal da Secretaria

**Data:** 2025-01-XX  
**Objetivo:** Verificar comportamento de login via `login.php?type=secretaria` e validar se permissões são respeitadas corretamente.

---

## Resumo Executivo

✅ **CONCLUSÃO PRINCIPAL:** O parâmetro `type` na URL é **APENAS para UI** (layout/título). Ele **NÃO** interfere em:
- Autenticação (validação de credenciais)
- Tipo de usuário na sessão (vem do banco de dados)
- Permissões (baseadas no `tipo` do banco, não no `type` da URL)
- Redirecionamento pós-login (baseado no `tipo` do banco)

**Comportamento Atual:**
- ✅ Admin pode logar via `type=secretaria` → Mantém todas as permissões de admin
- ✅ Secretaria pode logar via `type=secretaria` → Mantém permissões limitadas de secretaria
- ⚠️ Instrutor pode logar via `type=secretaria` → É redirecionado para dashboard do instrutor (devido ao tipo no banco)

---

## Parte 1: Mapeamento de Comportamento (Teste Lógico via Código)

### Cenário A: Admin logando via `type=secretaria`

**Fluxo:**
1. Usuário acessa `login.php?type=secretaria`
2. UI mostra apenas botão "Secretaria" (por causa do `$hasSpecificType`)
3. Admin digita credenciais de admin e submete formulário
4. Sistema processa via `Auth::login($email, $senha)`
5. Busca usuário no banco por email → encontra registro com `tipo = 'admin'`
6. Cria sessão com `$_SESSION['user_type'] = 'admin'` (valor vem do banco)
7. `redirectAfterLogin()` verifica `$user['tipo']` (é 'admin') → redireciona para `admin/index.php`
8. Admin acessa painel administrativo com **todas as permissões de admin intactas**

**Resultado:**
- ✅ Login aceito
- ✅ Redireciona para `admin/index.php`
- ✅ Mantém todas as permissões de admin (sem downgrade)
- ⚠️ UI da tela de login mostra "Secretaria", mas isso é apenas visual

**Arquivos Relevantes:**
- `login.php` linha 28: `$selectedType = $_POST['user_type']` (usado apenas para distinguir aluno vs funcionário)
- `login.php` linha 112: `$auth->login($email, $senha)` (não recebe tipo como parâmetro)
- `includes/auth.php` linha 36: `getUserByLogin($login)` (busca por email no banco)
- `includes/auth.php` linha 383: `$_SESSION['user_type'] = $usuario['tipo']` (vem do banco)
- `includes/auth.php` linha 337-343: `redirectAfterLogin()` usa `$user['tipo']` do banco

---

### Cenário B: Secretaria logando via `type=secretaria`

**Fluxo:**
1. Usuário acessa `login.php?type=secretaria`
2. UI mostra apenas botão "Secretaria"
3. Secretaria digita credenciais e submete
4. Sistema processa via `Auth::login()`
5. Busca no banco → encontra registro com `tipo = 'secretaria'`
6. Cria sessão com `$_SESSION['user_type'] = 'secretaria'`
7. `redirectAfterLogin()` verifica tipo → redireciona para `admin/index.php`
8. Secretaria acessa painel com **permissões limitadas de secretaria**

**Resultado:**
- ✅ Login aceito
- ✅ Redireciona para `admin/index.php` (mesmo destino que admin, mas permissões diferentes)
- ✅ Permissões corretas aplicadas (não pode acessar configurações, backup, logs)
- ✅ Sistema identifica como secretaria nas verificações de permissão

**Validação de Permissões:**
- `hasPermission('configuracoes')` → ❌ Retorna `false` (secretaria não tem)
- `hasPermission('usuarios')` → ✅ Retorna `true` (secretaria tem)
- `canAccessConfigurations()` → ❌ Retorna `false`
- `canManageUsers()` → ✅ Retorna `true` (admin e secretaria podem)

**Arquivos de Verificação:**
- `includes/auth.php` linha 209-223: `hasPermission()` verifica `$user['tipo']` do banco
- `includes/auth.php` linha 532-549: `getUserPermissions()` define permissões por tipo
- `admin/index.php` linha 26: Verifica `$user['tipo'] !== 'admin' && ... !== 'secretaria'`

---

### Cenário C: Instrutor logando via `type=secretaria`

**Fluxo:**
1. Usuário acessa `login.php?type=secretaria`
2. UI mostra apenas botão "Secretaria"
3. Instrutor digita credenciais e submete
4. Sistema processa via `Auth::login()`
5. Busca no banco → encontra registro com `tipo = 'instrutor'`
6. Cria sessão com `$_SESSION['user_type'] = 'instrutor'`
7. `redirectAfterLogin()` verifica tipo → redireciona para `instrutor/dashboard.php`

**Resultado:**
- ✅ Login aceito (sistema não bloqueia por causa do `type` da URL)
- ✅ Redireciona para `instrutor/dashboard.php` (devido ao tipo no banco)
- ⚠️ UX confusa: usuário acessa via portal "Secretaria" mas é redirecionado para área de instrutor

**Arquivos Relevantes:**
- `includes/auth.php` linha 337-343: `redirectAfterLogin()` switch case para 'instrutor'

---

## Parte 2: Análise do Código (Origem do Comportamento)

### 2.1. Onde `$_GET['type']` é lido

**Localização:** `login.php` linha 20
```php
$userType = $_GET['type'] ?? ''; // Tipo de usuário selecionado (vazio = tela de seleção)
$hasSpecificType = !empty($userType);
```

**Uso:**
- ✅ Apenas para controlar UI (quais botões mostrar no painel esquerdo)
- ✅ Define `$displayType` para exibir título correto no formulário
- ❌ **NÃO** usado na validação de autenticação
- ❌ **NÃO** usado para definir permissões
- ❌ **NÃO** usado para filtrar usuários do banco

### 2.2. Onde `$_POST['user_type']` é lido

**Localização:** `login.php` linha 28
```php
$selectedType = $_POST['user_type'] ?? 'admin';
```

**Uso:**
- ✅ Usado apenas para distinguir se é login de **aluno** vs **funcionário** (linha 35)
- ✅ Se `$selectedType === 'aluno'`: usa fluxo específico de aluno
- ✅ Se não: usa `Auth::login()` genérico (que não recebe tipo como parâmetro)
- ❌ **NÃO** usado para validar se usuário pode logar
- ❌ **NÃO** usado para definir tipo na sessão

### 2.3. Onde a autenticação acontece

**Localização:** `includes/auth.php` linha 23-84

**Fluxo:**
```php
public function login($login, $senha, $remember = false) {
    // 1. Busca usuário por email (não verifica tipo)
    $usuario = $this->getUserByLogin($login);
    
    // 2. Verifica senha
    if (!password_verify($senha, $usuario['senha'])) { ... }
    
    // 3. Verifica se está ativo
    if (!$usuario['ativo']) { ... }
    
    // 4. Cria sessão com tipo DO BANCO
    $this->createSession($usuario, $remember);
}
```

**Método `createSession()` (linha 379-386):**
```php
private function createSession($usuario, $remember = false) {
    $_SESSION['user_id'] = $usuario['id'];
    $_SESSION['user_email'] = $usuario['email'];
    $_SESSION['user_name'] = $usuario['nome'];
    $_SESSION['user_type'] = $usuario['tipo']; // ← VEM DO BANCO, NÃO DA URL
    // ...
}
```

**Conclusão:** O tipo do usuário sempre vem do banco de dados (`$usuario['tipo']`), nunca do parâmetro `type` da URL.

### 2.4. Onde o redirecionamento acontece

**Localização:** `includes/auth.php` linha 281-343

**Método `redirectAfterLogin()`:**
```php
public function redirectAfterLogin($user = null) {
    // ...
    $tipo = strtolower($user['tipo'] ?? ''); // ← USA TIPO DO BANCO
    
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
    }
}
```

**Conclusão:** Redirecionamento é baseado no `tipo` do banco, não no `type` da URL.

### 2.5. Onde as permissões são aplicadas

**Localização:** `includes/auth.php` linha 209-223

**Método `hasPermission()`:**
```php
public function hasPermission($permission) {
    $user = $this->getCurrentUser(); // Busca da sessão (tipo vem do banco)
    
    // Admin tem todas as permissões
    if ($user['tipo'] === 'admin') {
        return true;
    }
    
    // Verifica permissões específicas por tipo (do banco)
    $permissions = $this->getUserPermissions($user['tipo']);
    return in_array($permission, $permissions);
}
```

**Verificações de permissão (exemplos):**
- `admin/index.php` linha 26: Verifica `$user['tipo']` do banco
- `admin/pages/usuarios.php`: Usa `canManageUsers()` que verifica `$user['tipo']`
- APIs: Usam `apiRequirePermission()` que verifica `$user['tipo']`

**Conclusão:** Todas as verificações de permissão usam o `tipo` do banco de dados (via sessão), nunca o `type` da URL.

---

## Tabela Resumo: Login Page X vs Perfil Y

| Login Page (`type=`) | Perfil Real (Banco) | Login Aceito? | Destino | Permissões Aplicadas |
|---------------------|---------------------|---------------|---------|---------------------|
| `secretaria` | `admin` | ✅ SIM | `admin/index.php` | Admin (todas) |
| `secretaria` | `secretaria` | ✅ SIM | `admin/index.php` | Secretaria (limitadas) |
| `secretaria` | `instrutor` | ✅ SIM | `instrutor/dashboard.php` | Instrutor |
| `instrutor` | `admin` | ✅ SIM | `admin/index.php` | Admin (todas) |
| `instrutor` | `secretaria` | ✅ SIM | `admin/index.php` | Secretaria (limitadas) |
| `instrutor` | `instrutor` | ✅ SIM | `instrutor/dashboard.php` | Instrutor |
| `admin` | `admin` | ✅ SIM | `admin/index.php` | Admin (todas) |
| `admin` | `secretaria` | ✅ SIM | `admin/index.php` | Secretaria (limitadas) |
| `admin` | `instrutor` | ✅ SIM | `instrutor/dashboard.php` | Instrutor |

---

## Respostas aos Critérios de Aceite

### 1. Posso logar como Admin dentro de `type=secretaria`? Se sim, ele mantém Admin ou fica "secretaria"?

**Resposta:** ✅ **SIM, pode logar. Ele mantém Admin.**  
- O login é aceito porque `Auth::login()` valida apenas email/senha
- O tipo na sessão vem do banco (`tipo = 'admin'`)
- Permissões são baseadas no tipo do banco, então admin mantém todas as permissões
- O `type=secretaria` na URL é apenas para UI (mostrar botão "Secretaria")

### 2. Posso logar como Secretaria dentro de `type=secretaria`? E ela fica corretamente limitada?

**Resposta:** ✅ **SIM, pode logar. Ela fica corretamente limitada.**  
- Login aceito normalmente
- Tipo na sessão vem do banco (`tipo = 'secretaria'`)
- Permissões são verificadas pelo `$user['tipo']`, então secretaria tem apenas:
  - ✅ Pode: dashboard, usuarios, cfcs, alunos, instrutores, aulas, veiculos, relatorios
  - ❌ Não pode: configuracoes, backup, logs

### 3. O `type` está só no front ou interfere em autenticação/redirect?

**Resposta:** ✅ **O `type` está APENAS no front (UI).**  
- Não interfere em autenticação (validação de credenciais)
- Não interfere em permissões (baseadas no tipo do banco)
- Não interfere em redirecionamento (baseado no tipo do banco)
- **Apenas** controla qual botão mostrar no painel esquerdo e qual título exibir no formulário

### 4. Quais arquivos/trechos são responsáveis por:

#### a) Validar credencial + role:
- **Arquivo:** `includes/auth.php`
- **Método:** `login()` (linha 23-84)
- **Fluxo:**
  1. Valida email/senha não vazios
  2. Busca usuário no banco por email: `getUserByLogin($login)` (linha 36)
  3. Verifica senha com `password_verify()` (linha 43)
  4. Verifica se usuário está ativo (linha 49)
  5. Cria sessão: `createSession($usuario)` (linha 54)
  6. **O tipo (`tipo`) vem do banco de dados, não é validado contra o `type` da URL**

#### b) Setar a sessão do usuário:
- **Arquivo:** `includes/auth.php`
- **Método:** `createSession()` (linha 379-386)
- **Código relevante:**
```php
$_SESSION['user_id'] = $usuario['id'];
$_SESSION['user_email'] = $usuario['email'];
$_SESSION['user_name'] = $usuario['nome'];
$_SESSION['user_type'] = $usuario['tipo']; // ← VEM DO BANCO
$_SESSION['user_cfc_id'] = $usuario['cfc_id'] ?? null;
$_SESSION['last_activity'] = time();
```

#### c) Redirect pós-login:
- **Arquivo:** `includes/auth.php`
- **Método:** `redirectAfterLogin()` (linha 281-343)
- **Uso:** Chamado em `login.php` linha 126 após login bem-sucedido
- **Lógica:** Usa `$user['tipo']` (do banco) para decidir destino:
  - `admin` ou `secretaria` → `admin/index.php`
  - `instrutor` → `instrutor/dashboard.php`
  - `aluno` → `aluno/dashboard.php`

---

## Observações Importantes

### ✅ Comportamento Correto (Atual):
1. **Permissões são baseadas no tipo do banco** → Segurança garantida
2. **Admin não perde permissões** ao logar via portal "Secretaria"
3. **Secretaria mantém limitações** independente de qual portal usa

### ⚠️ Potenciais Problemas de UX:
1. **Instrutor logando via `type=secretaria`** → É redirecionado para área de instrutor (confuso)
2. **Admin logando via `type=secretaria`** → UI mostra "Portal da Secretaria" mas ele mantém permissões de admin (pode confundir)
3. **Não há bloqueio** para impedir que perfis diferentes usem portais específicos

### 💡 Recomendações para Decisão de Produto:

#### Opção 1: Manter comportamento atual (flexível)
- ✅ Permite que qualquer funcionário use qualquer portal (flexibilidade)
- ✅ Admin pode usar portal "Secretaria" como "porta de entrada alternativa"
- ⚠️ UX pode ser confusa se usuário espera restrição

#### Opção 2: Bloquear perfis em portais específicos (restritivo)
- ✅ UX mais clara: cada portal aceita apenas seu perfil
- ✅ Evita confusão visual
- ❌ Perde flexibilidade (admin não pode usar portal secretaria)
- **Implementação:** Adicionar validação em `login.php` após autenticação bem-sucedida:
  ```php
  if ($hasSpecificType && $user['tipo'] !== $userType) {
      $error = 'Você não pode acessar este portal com seu perfil. Use o portal correto.';
      // Não criar sessão
      return;
  }
  ```

#### Opção 3: Permitir mas informar (híbrido)
- ✅ Mantém flexibilidade
- ✅ Informa usuário quando perfil difere do portal
- **Implementação:** Adicionar mensagem informativa quando `$user['tipo'] !== $userType` mas permitir login

---

## Arquivos Alterados para Auditoria

Nenhum arquivo foi alterado. Apenas inspeção de código e análise lógica.

**Arquivos Inspecionados:**
1. `login.php` - Fluxo de login e processamento de formulário
2. `includes/auth.php` - Autenticação, criação de sessão, redirecionamento
3. `admin/index.php` - Verificação de permissões no painel admin
4. `includes/auth.php` métodos de permissão - Verificação de permissões por tipo

---

**Próximos Passos:**
Aguardar decisão de produto sobre qual abordagem seguir (Opção 1, 2 ou 3).
