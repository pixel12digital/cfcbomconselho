# Resumo das Correções: Permissões e Identidade do Instrutor

## Data: 2025-01-XX

## Problemas Identificados

Durante testes em aba anônima com instrutor Carlos, foram identificados três problemas:

1. **Não conseguia confirmar presença/ausência dos alunos matriculados** na tela de chamada
2. **Aparecia como "Administrador" em vez de "Instrutor Carlos"** no topbar
3. **Erro de permissão ao clicar em "Voltar"** da chamada para detalhes da turma: "Você não tem permissão para acessar esta página"

## Causas Raiz Identificadas

### Problema 1: Verificação de Permissão Incorreta em `turmas-teoricas.php`

**Causa:**
- A verificação usava `hasPermission('instrutor')`, que retorna `false` para usuários do tipo 'instrutor'
- Isso ocorre porque 'instrutor' não está na lista de permissões do tipo 'instrutor' em `includes/auth.php`
- A lista de permissões do tipo 'instrutor' contém apenas: `'dashboard', 'alunos', 'aulas_visualizar', 'aulas_editar', 'aulas_cancelar', 'veiculos', 'relatorios'`

**Solução:**
- Substituída verificação `hasPermission('instrutor')` por verificação de tipo: `($userType === 'instrutor') || isInstructor()`
- Aplicada tanto na verificação de acesso quanto na definição de `$isInstrutor`

### Problema 2: Identidade no Topbar

**Causa:**
- A lógica de detecção de fluxo instrutor depende de `$origem === 'instrutor'` OU `$userType === 'instrutor'`
- Se `$_SESSION['user_type']` não estiver definido corretamente ou se o link não passar `origem=instrutor`, a identidade não é detectada

**Solução:**
- Melhorada a lógica de fallback: mesmo quando `getCurrentInstrutorId()` retorna `null`, se `$userType === 'instrutor'`, ainda exibe "Instrutor" como role
- Adicionados logs de debug para diagnóstico quando `getCurrentInstrutorId()` retorna `null`

### Problema 3: Mensagens de Alerta Genéricas

**Causa:**
- Mensagens de alerta não eram específicas o suficiente para identificar o problema real
- Não diferenciava entre "instrutor não vinculado", "aula sem instrutor" e "instrutor diferente"

**Solução:**
- Implementadas mensagens específicas baseadas no problema identificado:
  - Se `getCurrentInstrutorId()` retorna `null`: "Não foi possível identificar seu vínculo como instrutor. Entre em contato com o administrador."
  - Se aula não tem `instrutor_id`: "Esta aula não possui instrutor atribuído. Entre em contato com o administrador."
  - Se instrutor não corresponde: "Você não é o instrutor desta aula. Apenas visualização."

## Correções Implementadas

### 1. `admin/pages/turmas-teoricas.php`

**Linhas ~24-36:**
- ✅ Corrigida verificação de permissão para usar `$userType === 'instrutor'` em vez de `hasPermission('instrutor')`
- ✅ `$isInstrutor` agora usa `($userType === 'instrutor') || isInstructor()`

**Código antes:**
```php
if (!isLoggedIn() || (!hasPermission('admin') && !hasPermission('instrutor'))) {
    // ...
}
$isInstrutor = hasPermission('instrutor');
```

**Código depois:**
```php
if (!isLoggedIn()) {
    // ...
}
$isInstrutor = ($userType === 'instrutor') || isInstructor();
if (!$isAdmin && !$isInstrutor) {
    // ...
}
```

### 2. `admin/index.php`

**Linhas ~38-64:**
- ✅ Melhorada lógica de fallback para identidade do instrutor
- ✅ Mesmo quando `getCurrentInstrutorId()` retorna `null`, se `$userType === 'instrutor'`, ainda exibe "Instrutor"
- ✅ Adicionados logs de debug para diagnóstico

**Melhorias:**
- Se `getCurrentInstrutorId()` retorna `null` mas `$userType === 'instrutor'`, usa nome do usuário e role "Instrutor"
- Logs de debug ajudam a identificar quando há problema de vínculo no banco

### 3. `admin/pages/turma-chamada.php`

**Linhas ~30-33:**
- ✅ Inicializada variável `$mensagemAlertaInstrutor` no início do arquivo

**Linhas ~104-175:**
- ✅ Adicionados logs de debug para diagnóstico
- ✅ Implementadas mensagens específicas baseadas no problema:
  - Problema de vínculo instrutor-usuário
  - Aula sem instrutor atribuído
  - Instrutor não corresponde à aula
- ✅ Melhorada mensagem quando não há `aula_id` mas instrutor não tem aulas na turma

**Linhas ~658-662:**
- ✅ Mensagem de alerta agora usa `$mensagemAlertaInstrutor` dinâmica

## Logs de Debug Adicionados

### Em `admin/index.php`:
```php
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("[TOPBAR] Fluxo instrutor detectado - user_id={$userId}, user_type={$userType}, origem={$origem}, instrutor_id=" . ($instrutorId ?? 'null'));
    error_log("[TOPBAR WARN] Usuário tipo 'instrutor' (user_id={$userId}) mas getCurrentInstrutorId() retornou null. Verificar vínculo em instrutores.usuario_id");
}
```

### Em `admin/pages/turma-chamada.php`:
```php
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_log("[TURMA-CHAMADA] Fluxo instrutor - user_id={$userId}, user_type={$userType}, origem={$origem}, instrutor_atual_id=" . ($instrutorAtualId ?? 'null') . ", aula_id={$aulaId}, turma_id={$turmaId}");
    error_log("[TURMA-CHAMADA] Aula {$aulaId} - instrutor_da_aula_id=" . ($instrutorDaAulaId ?? 'null') . ", instrutor_atual_id=" . ($instrutorAtualId ?? 'null') . ", match=" . ($match ? 'SIM' : 'NÃO'));
    error_log("[TURMA-CHAMADA WARN] getCurrentInstrutorId() retornou null para user_id={$userId}. Verificar vínculo em instrutores.usuario_id");
}
```

## Verificações Necessárias no Banco de Dados

Para diagnosticar problemas de vínculo, verificar:

1. **Vínculo instrutor-usuário:**
   ```sql
   SELECT i.id, i.nome, i.usuario_id, i.ativo, u.id as user_id, u.tipo
   FROM instrutores i
   LEFT JOIN usuarios u ON i.usuario_id = u.id
   WHERE u.id = [ID_DO_USUARIO_CARLOS];
   ```
   - Verificar se existe registro em `instrutores` com `usuario_id` correspondente
   - Verificar se `ativo = 1`
   - Verificar se `usuario.tipo = 'instrutor'`

2. **Instrutor da aula:**
   ```sql
   SELECT taa.id, taa.instrutor_id, i.nome as instrutor_nome
   FROM turma_aulas_agendadas taa
   LEFT JOIN instrutores i ON taa.instrutor_id = i.id
   WHERE taa.id = 228 AND taa.turma_id = 19;
   ```
   - Verificar se `instrutor_id` corresponde ao instrutor Carlos

3. **Sessão do usuário:**
   - Verificar se `$_SESSION['user_type']` está definido como 'instrutor' após login
   - Verificar se `$_SESSION['user_id']` corresponde ao usuário do Carlos

## Sobre Aba Anônima

A aba anônima pode interferir se:
1. **Cookies de sessão bloqueados** → Sessão não persiste entre requisições
2. **Cache de JavaScript** → Pode estar usando versão antiga do `topbar-unified.js`
3. **Sessão não inicializada corretamente** → `$_SESSION['user_type']` pode não estar definido

**Recomendação:** Testar também em aba normal para comparar comportamento.

## Testes Recomendados

### Cenário 1: Instrutor com Vínculo Correto
1. Logar como instrutor Carlos (aba normal)
2. Acessar chamada da aula 228 (turma 19)
3. **Esperado:**
   - ✅ Topbar mostra "Carlos - Instrutor" (ou nome do instrutor)
   - ✅ Pode marcar presença/ausência dos alunos
   - ✅ Não aparece alerta de permissão
   - ✅ Botão "Voltar" funciona corretamente

### Cenário 2: Instrutor sem Vínculo no Banco
1. Logar como usuário tipo 'instrutor' mas sem registro em `instrutores`
2. Acessar chamada
3. **Esperado:**
   - ✅ Topbar mostra "Instrutor" (mesmo sem vínculo)
   - ✅ Aparece alerta: "Não foi possível identificar seu vínculo como instrutor. Entre em contato com o administrador."
   - ✅ Modo somente leitura

### Cenário 3: Aula sem Instrutor Atribuído
1. Logar como instrutor
2. Acessar chamada de aula que não tem `instrutor_id`
3. **Esperado:**
   - ✅ Aparece alerta: "Esta aula não possui instrutor atribuído. Entre em contato com o administrador."
   - ✅ Modo somente leitura

### Cenário 4: Voltar da Chamada para Detalhes
1. Logar como instrutor
2. Acessar chamada via `origem=instrutor`
3. Clicar em "Voltar"
4. **Esperado:**
   - ✅ Não aparece erro de permissão
   - ✅ Acessa página de detalhes da turma corretamente

## Arquivos Modificados

1. ✅ `admin/pages/turmas-teoricas.php` - Correção de verificação de permissão
2. ✅ `admin/index.php` - Melhoria na lógica de identidade do topbar
3. ✅ `admin/pages/turma-chamada.php` - Mensagens específicas e logs de debug

## Observações Importantes

1. **Não quebrou o fluxo admin:** Todas as mudanças são condicionais baseadas em `$origem` ou `$userType`
2. **Mantém segurança:** Verificações de permissão foram refinadas, não removidas
3. **Compatível com código existente:** Usa funções auxiliares já existentes no sistema
4. **Logs de debug:** Ajudam a diagnosticar problemas de vínculo sem expor informações sensíveis

## Próximos Passos (se necessário)

- [ ] Verificar se há outros lugares usando `hasPermission('instrutor')` incorretamente
- [ ] Adicionar testes automatizados para validar a lógica de permissão
- [ ] Considerar criar função helper centralizada para verificar se usuário é instrutor
- [ ] Documentar processo de vínculo instrutor-usuário para administradores
