# Análise do Sistema de Gerenciamento de Usuários e Permissões

## 📍 Localização do Gerenciamento de Usuários

### Caminho/Página no Admin
**URL:** `index.php?page=usuarios`

**Acesso direto:**
- URL completa: `admin/index.php?page=usuarios`
- A página aceita o parâmetro `action` (ex: `index.php?page=usuarios&action=create`)

### ⚠️ **PROBLEMA IDENTIFICADO:**
**A página de usuários NÃO está visível no menu lateral do admin!** 

O menu de navegação (`admin/index.php`, linhas 1488-1800) não possui um item específico para "Usuários" ou "Gerenciar Usuários". A página existe e funciona, mas não é acessível pelo menu.

---

## 📁 Arquivos Principais

### 1. Página de Gerenciamento
**Arquivo:** `admin/pages/usuarios.php`
- **Linhas:** 1-2314
- **Função:** Interface HTML completa para listar, criar, editar e excluir usuários
- **Recursos:**
  - Listagem de usuários em tabela responsiva
  - Modal para criar/editar usuários
  - Modal para redefinir senhas
  - Modal para exibir credenciais geradas
  - Exportação de dados (CSV)
  - JavaScript inline para todas as operações

### 2. API de Usuários
**Arquivo:** `admin/api/usuarios.php`
- **Linhas:** 1-440
- **Função:** Endpoint REST para operações CRUD de usuários
- **Métodos HTTP suportados:**
  - `GET` - Listar todos ou buscar por ID
  - `POST` - Criar novo usuário ou redefinir senha
  - `PUT` - Atualizar usuário existente
  - `DELETE` - Excluir usuário

**Validações de Permissão:**
```php
// Linha 22-27: Verifica se está logado
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autorizado', 'code' => 'NOT_LOGGED_IN']);
    exit;
}

// Linha 42-47: Verifica se pode gerenciar usuários
if (!canManageUsers()) {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso negado - Apenas administradores e atendentes podem gerenciar usuários', 'code' => 'NOT_AUTHORIZED']);
    exit;
}
```

### 3. Sistema de Autenticação e Permissões
**Arquivo:** `includes/auth.php`
- **Linhas:** 1-689
- **Classe principal:** `Auth`
- **Funções globais disponíveis:**
  - `isLoggedIn()` - Verifica se usuário está logado
  - `getCurrentUser()` - Retorna dados do usuário atual
  - `hasPermission($permission)` - Verifica permissão específica
  - `isAdmin()`, `isInstructor()`, `isSecretary()`, `isStudent()` - Verificam tipo de usuário
  - `canManageUsers()` - Verifica se pode gerenciar usuários
  - `canAddLessons()`, `canEditLessons()`, `canCancelLessons()` - Permissões de aulas
  - `canAccessConfigurations()` - Acesso a configurações
  - `requireLogin()`, `requirePermission()`, `requireAdmin()` - Middlewares de proteção

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `usuarios`

**Definição (install.php, linhas 22-36):**
```sql
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nome VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    senha VARCHAR(255) NOT NULL,
    tipo ENUM('admin', 'instrutor', 'secretaria') NOT NULL DEFAULT 'secretaria',
    cpf VARCHAR(14) UNIQUE,
    telefone VARCHAR(20),
    ativo BOOLEAN DEFAULT TRUE,
    ultimo_login DATETIME,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

**⚠️ OBSERVAÇÃO:** O ENUM na definição original só inclui `'admin', 'instrutor', 'secretaria'`, mas o código também usa `'aluno'`. Isso pode causar problemas se o banco não foi atualizado.

**Campos que controlam acesso:**
- `tipo` - Define o papel/perfil do usuário (campo principal)
- `ativo` - Controla se o usuário pode fazer login
- `email` - Usado para login (único)
- `cpf` - Usado para login de alunos (único)

---

## 👥 Níveis de Acesso e Perfis

### Definição dos Perfis

Os perfis são definidos no método `getUserPermissions()` da classe `Auth` (includes/auth.php, linhas 418-441):

```php
private function getUserPermissions($userType) {
    $permissions = [
        'admin' => [
            'dashboard', 'usuarios', 'cfcs', 'alunos', 'instrutores', 'aulas', 
            'veiculos', 'relatorios', 'configuracoes', 'backup', 'logs'
        ],
        'instrutor' => [
            'dashboard', 'alunos', 'aulas_visualizar', 'aulas_editar', 'aulas_cancelar',
            'veiculos', 'relatorios'
        ],
        'secretaria' => [
            'dashboard', 'usuarios', 'cfcs', 'alunos', 'instrutores', 'aulas', 
            'veiculos', 'relatorios'
        ],
        'aluno' => [
            'dashboard', 'aulas_visualizar', 'relatorios_visualizar'
        ]
    ];
    
    return $permissions[$userType] ?? [];
}
```

### Resumo dos Perfis

| Perfil | Descrição | Permissões Principais |
|--------|-----------|----------------------|
| **admin** | Administrador | Acesso total ao sistema, incluindo configurações, backup, logs e gerenciamento de usuários |
| **secretaria** | Atendente CFC | Pode fazer tudo menos configurações, backup e logs. Pode gerenciar usuários. |
| **instrutor** | Instrutor | Pode visualizar/editar/cancelar aulas, visualizar alunos e veículos. Não pode adicionar aulas ou gerenciar usuários. |
| **aluno** | Aluno | Apenas visualização: dashboard, suas aulas e relatórios pessoais |

### Regras Especiais

1. **Admin tem todas as permissões:**
   ```php
   // includes/auth.php, linha 191-193
   if ($user['tipo'] === 'admin') {
       return true; // Sempre retorna true para qualquer permissão
   }
   ```

2. **Gerenciamento de Usuários:**
   ```php
   // includes/auth.php, linha 257-262
   public function canManageUsers() {
       $user = $this->getCurrentUser();
       if (!$user) return false;
       return in_array($user['tipo'], ['admin', 'secretaria']);
   }
   ```

3. **Permissões de Aulas:**
   - **Adicionar:** Apenas `admin` e `secretaria` (linha 225-230)
   - **Editar:** `admin`, `secretaria` e `instrutor` (linha 233-238)
   - **Cancelar:** `admin`, `secretaria` e `instrutor` (linha 241-246)

---

## 🔒 Validação de Permissões nas Páginas

### Como Funciona

1. **No início de cada página admin:**
   ```php
   // admin/index.php, linha 18-21
   if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('instrutor'))) {
       header('Location: ../index.php');
       exit;
   }
   ```

2. **Proteção de rotas específicas:**
   ```php
   // Exemplo: Proteger página de configurações
   if (!canAccessConfigurations()) {
       header('HTTP/1.1 403 Forbidden');
       die('Acesso negado');
   }
   ```

3. **Proteção de APIs:**
   ```php
   // includes/auth.php, linhas 660-684
   function apiRequireAuth() {
       if (!isLoggedIn()) {
           http_response_code(401);
           echo json_encode(['error' => 'Não autorizado']);
           exit;
       }
   }
   
   function apiRequirePermission($permission) {
       apiRequireAuth();
       if (!hasPermission($permission)) {
           http_response_code(403);
           echo json_encode(['error' => 'Acesso negado']);
           exit;
       }
   }
   ```

4. **Proteção no menu (ocultação de itens):**
   ```php
   // admin/index.php, exemplo linha 1506
   <?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
       <!-- Item do menu só aparece para admin e secretaria -->
   <?php endif; ?>
   ```

### Middlewares Disponíveis

- `requireLogin()` - Força login, redireciona se não logado
- `requirePermission($permission)` - Força permissão específica
- `requireAdmin()` - Força ser administrador
- `apiRequireAuth()` - Para APIs, retorna JSON 401
- `apiRequirePermission($permission)` - Para APIs, retorna JSON 403
- `apiRequireAdmin()` - Para APIs, retorna JSON 403 se não for admin

---

## 🔗 Conexão com Outras Áreas do Sistema

### Painel do Instrutor

O painel do instrutor usa o mesmo sistema de permissões. A verificação é feita em:
- `admin/index.php` (linha 18-21) verifica se é `admin` ou `instrutor`
- O menu mostra/oculta itens baseado em `$isAdmin` e `$user['tipo']`

### Sistema de Agendamentos

Existe um sistema específico de permissões para agendamentos:
- **Arquivo:** `includes/guards/AgendamentoPermissions.php`
- **Classe:** `AgendamentoPermissions`
- **Funções:**
  - `podeCriarAgendamento()` - Apenas admin e secretaria
  - `podeEditarAgendamento()` - Admin, secretaria e instrutor (suas próprias aulas)
  - `podeCancelarAgendamento()` - Admin, secretaria e instrutor (suas próprias aulas)
  - `podeTransferirAula()` - Apenas instrutor (suas próprias aulas)

Este sistema complementa o sistema principal de permissões com regras específicas de negócio.

---

## 📋 Planejamento e Recomendações

### Problemas Identificados

1. **❌ Menu não tem link para Usuários**
   - A página existe e funciona, mas não está acessível pelo menu
   - **Solução:** Adicionar item no menu "Configurações" ou criar seção "Sistema"

2. **⚠️ Inconsistência no ENUM do banco**
   - A definição SQL não inclui `'aluno'` no ENUM
   - O código usa `'aluno'` em vários lugares
   - **Solução:** Verificar se o banco foi atualizado ou fazer migration

3. **⚠️ Permissões hardcoded**
   - As permissões estão definidas em código PHP
   - Não há tabela de permissões no banco
   - **Solução:** Considerar migrar para sistema baseado em banco de dados

### Sugestões de Melhorias

1. **Adicionar item no menu:**
   ```php
   // Em admin/index.php, dentro do submenu "Configurações" (linha ~1746)
   <a href="index.php?page=usuarios" class="nav-sublink <?php echo $page === 'usuarios' ? 'active' : ''; ?>">
       <i class="fas fa-users-cog"></i>
       <span>Gerenciar Usuários</span>
   </a>
   ```

2. **Centralizar gerenciamento:**
   - Criar página única "Gerenciar Usuários / Permissões"
   - Incluir visualização de permissões por perfil
   - Permitir edição de permissões (se necessário)

3. **Melhorar estrutura:**
   - Considerar criar tabelas `perfis` e `permissoes` no banco
   - Permitir permissões customizadas por usuário
   - Adicionar auditoria de mudanças de permissões

---

## 📝 Resumo dos Arquivos

| Arquivo | Tipo | Função |
|---------|------|--------|
| `admin/pages/usuarios.php` | Página | Interface de gerenciamento de usuários |
| `admin/api/usuarios.php` | API | Endpoint REST para operações CRUD |
| `includes/auth.php` | Core | Sistema de autenticação e permissões |
| `includes/guards/AgendamentoPermissions.php` | Guard | Permissões específicas de agendamentos |
| `includes/CredentialManager.php` | Service | Gerenciamento de credenciais (senhas temporárias) |
| `admin/index.php` | Router | Roteamento e menu de navegação |

---

## 🎯 Conclusão

O sistema de gerenciamento de usuários **existe e está funcional**, mas:

1. **Não está acessível pelo menu** - precisa ser adicionado
2. **Usa sistema de permissões baseado em código** - funciona bem, mas não é flexível
3. **Tem validações consistentes** - tanto no frontend quanto no backend
4. **Integra bem com outras áreas** - painel do instrutor e agendamentos usam o mesmo sistema

**Próximos passos recomendados:**
1. Adicionar link no menu para a página de usuários
2. Verificar/atualizar estrutura do banco para incluir tipo 'aluno'
3. Considerar migração para sistema de permissões baseado em banco de dados (opcional, para maior flexibilidade)

