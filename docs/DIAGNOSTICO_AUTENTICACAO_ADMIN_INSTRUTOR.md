# Diagnóstico de Autenticação Admin vs Instrutor

## Data: 2025-01-XX

## Contexto
Este documento descreve como funciona a autenticação e exibição de identidade no sistema CFC Bom Conselho, especialmente no contexto de acesso cruzado entre painel ADMIN e painel INSTRUTOR.

## Variáveis de Sessão

### Autenticação Admin
Quando um usuário faz login como admin/secretaria:
- `$_SESSION['user_id']` - ID do usuário na tabela `usuarios`
- `$_SESSION['user_name']` - Nome do usuário (definido em `includes/auth.php` linha 382)
- `$_SESSION['user_type']` - Tipo do usuário: `'admin'` ou `'secretaria'`
- `$_SESSION['user_email']` - Email do usuário
- `$_SESSION['user_cfc_id']` - ID do CFC associado (se houver)

**Arquivo:** `includes/auth.php` - método `createSession()` (linha 379-397)

### Autenticação Instrutor
Quando um instrutor faz login:
- `$_SESSION['user_id']` - ID do usuário na tabela `usuarios` (mesma tabela)
- `$_SESSION['user_name']` - Nome do usuário (definido em `includes/auth.php` linha 382)
- `$_SESSION['user_type']` - Tipo do usuário: `'instrutor'`
- `$_SESSION['user_email']` - Email do usuário
- `$_SESSION['user_cfc_id']` - ID do CFC associado (se houver)

**Observação:** O instrutor NÃO tem uma sessão separada. Ele usa a mesma estrutura de sessão que o admin, diferenciado apenas pelo `user_type`.

### Relação Usuário → Instrutor
Para obter o ID do instrutor a partir do `user_id`:
- Função: `getCurrentInstrutorId($userId)` em `includes/auth.php` (linha 810)
- Query: `SELECT id FROM instrutores WHERE usuario_id = ? AND ativo = 1`
- A tabela `instrutores` tem um campo `usuario_id` que referencia `usuarios.id`

## Exibição de Identidade no Topo

### Localização
O topbar do admin está em `admin/index.php` (linhas 1467-1472):
```php
<div class="profile-name" id="profile-name"><?php echo htmlspecialchars($user['nome']); ?></div>
<div class="profile-role" id="profile-role">Administrador</div>
```

**Problema:** O role está hardcoded como "Administrador", mesmo quando o acesso é via `origem=instrutor`.

### JavaScript do Topbar
O arquivo `admin/assets/js/topbar-unified.js` (linha 714-728) também define valores hardcoded:
```javascript
const userData = {
    name: 'Administrador Sistema',
    role: 'Administrador',
    ...
};
```

**Problema:** Esses valores são hardcoded e não respeitam o contexto de origem.

## Fluxo de Acesso Cruzado

### Quando instrutor acessa chamada/diário via dashboard
1. Instrutor está logado no painel `/instrutor/dashboard.php`
2. Clica em "Chamada" ou "Diário"
3. URL gerada: `admin/index.php?page=turma-chamada&turma_id=X&aula_id=Y&origem=instrutor`
4. O `admin/index.php` carrega a página `admin/pages/turma-chamada.php`
5. **Problema:** O topbar mostra "Administrador" em vez de "Instrutor"

### Parâmetro `origem=instrutor`
- Indica que o acesso veio do painel do instrutor
- Deve ser usado para:
  - Ajustar a identidade exibida no topo
  - Ajustar a lógica de permissão (usar instrutor_id da aula, não user_id direto)
  - Ajustar mensagens de alerta

## Lógica de Permissão Atual

### Em `admin/pages/turma-chamada.php` (linha 98-128)
```php
if ($userType === 'instrutor') {
    if ($aulaId) {
        $aulaInstrutor = $db->fetch(...);
        if (!$aulaInstrutor || $aulaInstrutor['instrutor_id'] != $userId) {
            $canEdit = false;
        }
    }
}
```

**Problema:** Compara `instrutor_id` da aula com `$userId` (que é o ID do usuário, não do instrutor).

**Correção necessária:**
- Quando `origem=instrutor`, usar `getCurrentInstrutorId($userId)` para obter o `instrutor_id` real
- Comparar `$aulaInstrutor['instrutor_id']` com o `instrutor_id` obtido, não com `$userId`

## Alerta "Você não é o instrutor desta aula"

### Localização
`admin/pages/turma-chamada.php` (linha 555-560):
```php
<?php elseif ($userType === 'instrutor' && !$canEdit): ?>
<div class="alert alert-info mb-3" role="alert">
    <strong>Sem permissão:</strong> Você não é o instrutor desta aula. Apenas visualização.
</div>
```

**Problema:** 
- Aparece mesmo quando o acesso é de admin (não deveria)
- Aparece mesmo quando o instrutor É o instrutor da aula (quando a lógica de permissão está errada)

**Correção necessária:**
- Só mostrar quando `origem=instrutor` E o instrutor logado NÃO for o instrutor da aula
- Não mostrar quando o acesso é de admin

## Decisões de Design

### Variáveis de Controle
Introduzir duas variáveis claras:
- `$modoSomenteLeitura` - Controla se a interface permite edição
- `$mostrarAlertaInstrutor` - Controla se deve mostrar o alerta específico de instrutor

### Regras de Permissão

**Quando `origem=instrutor`:**
1. Obter `$instrutorAtualId = getCurrentInstrutorId($userId)`
2. Obter `$instrutorDaAulaId` da tabela `turma_aulas_agendadas`
3. Se `$instrutorAtualId === $instrutorDaAulaId`:
   - `$modoSomenteLeitura = false`
   - `$mostrarAlertaInstrutor = false`
4. Se forem diferentes:
   - `$modoSomenteLeitura = true`
   - `$mostrarAlertaInstrutor = true`

**Quando `origem` não for 'instrutor' (fluxo admin):**
1. `$modoSomenteLeitura = false` (admin sempre pode editar)
2. `$mostrarAlertaInstrutor = false` (admin não precisa ver alerta de instrutor)

### Exibição de Identidade

**Quando `origem=instrutor` OU `user_type === 'instrutor'`:**
1. Buscar nome do instrutor na tabela `instrutores` (ou usar `$user['nome']` se não houver)
2. Exibir: `[Nome do Instrutor] - Instrutor`
3. Usar `getCurrentInstrutorId()` para obter dados do instrutor

**Quando acesso é admin normal:**
1. Exibir: `[Nome do Admin] - Administrador`
2. Usar dados de `$user` diretamente

## Arquivos Afetados

1. `admin/index.php` - Topbar profile (linha 1467-1472) ✅ CORRIGIDO
2. `admin/pages/turma-chamada.php` - Lógica de permissão e alerta (linha 98-128, 555-560) ✅ CORRIGIDO
3. `admin/pages/turma-diario.php` - Lógica similar ✅ CORRIGIDO
4. `admin/assets/js/topbar-unified.js` - JavaScript do topbar (linha 714-728) - não precisa ser alterado, PHP já controla

## Implementação Realizada

### 1. Lógica de Permissão Refinada
- Introduzidas variáveis `$modoSomenteLeitura` e `$mostrarAlertaInstrutor`
- Quando `origem=instrutor`, usa `getCurrentInstrutorId()` para obter o instrutor_id real
- Compara `instrutor_id` da aula com o `instrutor_id` do usuário logado (não mais com `user_id`)

### 2. Alerta Condicional
- Alerta "Você não é o instrutor desta aula" só aparece quando:
  - `$mostrarAlertaInstrutor === true`
  - E `$modoSomenteLeitura === true`
- Não aparece mais quando o acesso é de admin

### 3. Identidade no Topbar
- Quando `origem=instrutor` OU `user_type === 'instrutor'`:
  - Busca nome do instrutor na tabela `instrutores`
  - Exibe: `[Nome do Instrutor] - Instrutor`
- Quando acesso é admin normal:
  - Exibe: `[Nome do Admin] - Administrador` ou `[Nome] - Secretaria`

### 4. Aplicação em Múltiplas Páginas
- `turma-chamada.php`: Lógica completa implementada
- `turma-diario.php`: Lógica similar implementada
- `admin/index.php`: Topbar ajustado para detectar fluxo instrutor

## Funções Auxiliares Disponíveis

- `getCurrentInstrutorId($userId)` - `includes/auth.php` linha 810
- `getCurrentUser()` - `includes/auth.php` linha 662
- `isInstructor()` - `includes/auth.php` linha 686
