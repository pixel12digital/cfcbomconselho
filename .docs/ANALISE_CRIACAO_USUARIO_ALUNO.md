# Análise: Criação Automática de Usuário ao Criar Aluno

**Data:** 2026-01-21  
**Problema:** Ao criar um aluno, o usuário correspondente não está sendo criado automaticamente.

---

## 📋 Lógica Atual do Sistema

### 1. Fluxo de Criação de Aluno

**Arquivo:** `app/Controllers/AlunosController.php` - Método `criar()`

```
1. Validação de permissões
2. Verificação CSRF
3. Validação de dados (validateStudentData)
   └─ Email é OBRIGATÓRIO (linha 1138-1142)
4. Preparação de dados (prepareStudentData)
   └─ Email processado: trim($_POST['email']) ou null (linha 1257)
5. Verificação de CPF único
6. Criação do aluno no banco
7. Auditoria e histórico
8. ⚠️ TENTATIVA DE CRIAR USUÁRIO (linhas 112-137)
```

### 2. Lógica de Criação de Usuário

**Localização:** `app/Controllers/AlunosController.php` - Linhas 112-137

```php
// Criar usuário automaticamente se houver e-mail
$email = trim($_POST['email'] ?? '');
if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    try {
        $userService = new UserCreationService();
        $userData = $userService->createForStudent($id, $email, $fullName ?: null);
        
        // Tentar enviar e-mail com credenciais
        // ...
        
        $_SESSION['success'] = 'Aluno criado com sucesso! Acesso ao sistema criado automaticamente.';
    } catch (\Exception $e) {
        // ⚠️ ERRO SILENCIOSO: Apenas loga, não bloqueia
        error_log("Erro ao criar acesso para aluno: " . $e->getMessage());
        $_SESSION['success'] = 'Aluno criado com sucesso! (Aviso: não foi possível criar acesso automático - ' . $e->getMessage() . ')';
    }
} else {
    $_SESSION['success'] = 'Aluno criado com sucesso! (Acesso não criado: e-mail não informado ou inválido)';
}
```

### 3. Service de Criação de Usuário

**Arquivo:** `app/Services/UserCreationService.php` - Método `createForStudent()`

```php
public function createForStudent($studentId, $email, $fullName = null)
{
    // 1. Verificar se aluno já tem usuário
    // 2. Verificar se email já existe na tabela usuarios
    //    └─ Se existir, lança exceção: "E-mail já está em uso por outro usuário."
    // 3. Gerar senha temporária
    // 4. Criar usuário na tabela usuarios
    // 5. Vincular com aluno (UPDATE students SET user_id = ?)
    // 6. Associar role ALUNO (INSERT INTO usuario_roles)
    // 7. Retornar dados do usuário criado
}
```

---

## 🔍 Problemas Identificados

### ❌ Problema 1: Inconsistência na Validação

- **Validação exige email obrigatório** (linha 1138-1142)
- **Campo no formulário NÃO tem `required`** (`app/Views/alunos/form.php` linha 243-251)
- **Resultado:** Usuário pode submeter formulário sem email via JavaScript ou manipulação

### ❌ Problema 2: Erro Silencioso

- Se `UserCreationService` lançar exceção, ela é capturada e apenas logada
- Mensagem de sucesso ainda aparece, mas pode não mencionar claramente o problema
- Usuário pode não perceber que o acesso não foi criado

### ❌ Problema 3: Possíveis Causas de Falha

1. **Email não enviado no POST**
   - Campo pode estar vazio
   - JavaScript pode estar bloqueando/envio

2. **Email inválido**
   - `filter_var($email, FILTER_VALIDATE_EMAIL)` retorna false
   - Email com formato incorreto

3. **Email já existe**
   - `UserCreationService` verifica se email já está em uso
   - Lança exceção: "E-mail já está em uso por outro usuário."

4. **Erro de banco de dados**
   - Falha na transação
   - Constraint violation
   - Erro de conexão

5. **Problema na vinculação**
   - Falha ao atualizar `students.user_id`
   - Falha ao inserir em `usuario_roles`

---

## 🔧 Como Diagnosticar

### 1. Verificar Logs de Erro

```bash
# Verificar logs do PHP/Apache
tail -f /var/log/apache2/error.log
# ou
tail -f C:\xampp\apache\logs\error.log
```

Procurar por:
- `"Erro ao criar acesso para aluno:"`
- `"E-mail já está em uso por outro usuário."`

### 2. Verificar no Banco de Dados

```sql
-- Verificar se aluno tem user_id
SELECT id, full_name, email, user_id 
FROM students 
WHERE email = 'email@exemplo.com';

-- Verificar se usuário foi criado
SELECT id, nome, email, status 
FROM usuarios 
WHERE email = 'email@exemplo.com';

-- Verificar role
SELECT ur.*, u.email 
FROM usuario_roles ur
JOIN usuarios u ON u.id = ur.usuario_id
WHERE u.email = 'email@exemplo.com';
```

### 3. Testar Criação Manual

Usar a funcionalidade de "Criar Acesso" manual em:
- `app/Controllers/UsuariosController.php` - Método `criarAcessoAluno()`

---

## ✅ Soluções Recomendadas

### Solução 1: Melhorar Tratamento de Erros

```php
// Em AlunosController::criar()
try {
    $userService = new UserCreationService();
    $userData = $userService->createForStudent($id, $email, $fullName ?: null);
    
    $_SESSION['success'] = 'Aluno criado com sucesso! Acesso ao sistema criado automaticamente.';
} catch (\Exception $e) {
    // Log detalhado
    error_log("Erro ao criar acesso para aluno ID {$id}: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // Mensagem mais clara para o usuário
    $_SESSION['warning'] = 'Aluno criado, mas não foi possível criar acesso automático: ' . $e->getMessage();
    $_SESSION['success'] = 'Aluno criado com sucesso!';
}
```

### Solução 2: Adicionar Campo Required no Formulário

```php
// Em app/Views/alunos/form.php
<input 
    type="email" 
    id="email" 
    name="email" 
    class="form-input" 
    value="<?= htmlspecialchars($student['email'] ?? '') ?>"
    required  <!-- ADICIONAR -->
>
```

### Solução 3: Melhorar Validação

```php
// Verificar email ANTES de criar aluno
$email = trim($_POST['email'] ?? '');
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = 'E-mail é obrigatório e deve ser válido para criar acesso ao sistema.';
    redirect(base_url('alunos/novo'));
}
```

### Solução 4: Adicionar Log Detalhado

```php
// Adicionar log antes de tentar criar
error_log("Tentando criar usuário para aluno ID {$id}, email: {$email}");

try {
    $userService = new UserCreationService();
    $userData = $userService->createForStudent($id, $email, $fullName ?: null);
    error_log("Usuário criado com sucesso: ID {$userData['user_id']}");
} catch (\Exception $e) {
    error_log("FALHA ao criar usuário: " . $e->getMessage());
    error_log("Aluno ID: {$id}, Email: {$email}");
}
```

---

## 📊 Checklist de Verificação

- [ ] Email está sendo enviado no POST?
- [ ] Email passa na validação `filter_var`?
- [ ] Email não está em uso por outro usuário?
- [ ] Transação de banco está funcionando?
- [ ] `students.user_id` está sendo atualizado?
- [ ] `usuario_roles` está sendo populado?
- [ ] Logs de erro estão sendo gerados?
- [ ] Mensagem de sucesso está clara?

---

## 🎯 Próximos Passos

1. **Verificar logs** para identificar o erro específico
2. **Testar criação** com email válido e novo
3. **Verificar banco** para confirmar se usuário foi criado
4. **Implementar melhorias** sugeridas acima
5. **Adicionar testes** para garantir funcionamento

---

## 📝 Notas Técnicas

- O sistema usa **transações** para garantir atomicidade
- Erros são **silenciosos** para não bloquear criação do aluno
- Email é **obrigatório** na validação, mas não no formulário HTML
- Existe funcionalidade de **criação manual** de acesso em `UsuariosController`
