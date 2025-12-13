# Resumo da Correção: Identidade e Permissões Instrutor vs Admin

## Data: 2025-01-XX

## Problema Identificado

Quando um instrutor acessava a tela de chamada/diário através do painel `/instrutor/dashboard.php`:
1. O topo da tela mostrava "Administrador Sistema – Administrador" em vez do nome do instrutor
2. Aparecia o alerta "Você não é o instrutor desta aula. Apenas visualização." mesmo quando o instrutor era o instrutor correto da aula
3. A lógica de permissão comparava `user_id` com `instrutor_id` da aula (tipos diferentes)

## Solução Implementada

### 1. Lógica de Permissão Refinada

**Arquivos alterados:**
- `admin/pages/turma-chamada.php`
- `admin/pages/turma-diario.php`

**Mudanças:**
- Introduzidas variáveis de controle claras:
  - `$modoSomenteLeitura` - Controla se a interface permite edição
  - `$mostrarAlertaInstrutor` - Controla se deve mostrar o alerta específico de instrutor

- Quando `origem=instrutor` OU `user_type === 'instrutor'`:
  - Usa `getCurrentInstrutorId($userId)` para obter o `instrutor_id` real
  - Compara `instrutor_id` da aula com o `instrutor_id` do usuário logado (correção do bug)
  - Define `$modoSomenteLeitura` e `$mostrarAlertaInstrutor` baseado na comparação

- Quando acesso é admin normal:
  - `$modoSomenteLeitura = false` (admin sempre pode editar, exceto turmas canceladas)
  - `$mostrarAlertaInstrutor = false` (admin não precisa ver alerta de instrutor)

### 2. Alerta Condicional

**Arquivo:** `admin/pages/turma-chamada.php`

**Mudança:**
- Alerta "Você não é o instrutor desta aula" só aparece quando `$mostrarAlertaInstrutor === true`
- Não aparece mais quando o acesso é de admin
- Não aparece quando o instrutor É o instrutor correto da aula

### 3. Identidade no Topbar

**Arquivo:** `admin/index.php`

**Mudanças:**
- Detecta se estamos em fluxo de instrutor: `$isFluxoInstrutor = ($origem === 'instrutor') || ($userType === 'instrutor')`
- Quando `$isFluxoInstrutor === true`:
  - Busca nome do instrutor na tabela `instrutores` usando `getCurrentInstrutorId()`
  - Exibe: `[Nome do Instrutor] - Instrutor`
- Quando acesso é admin normal:
  - Exibe: `[Nome do Admin] - Administrador` ou `[Nome] - Secretaria`

### 4. Aplicação Consistente

**Mudanças em `turma-chamada.php`:**
- Todos os controles de edição usam `$modoSomenteLeitura` em vez de apenas `$canEdit`
- JavaScript recebe variável `modoSomenteLeitura` para validação no frontend
- Botões de ação em lote respeitam `$modoSomenteLeitura`

## Testes Manuais Recomendados

### Cenário 1 – Instrutor Real
1. Logar como instrutor Carlos no painel `/instrutor/dashboard.php`
2. Clicar em "Chamada" para uma aula que é dele
3. **Esperado:**
   - No topo: "Carlos da Silva – Instrutor" (ou equivalente)
   - Não aparece alerta "Você não é o instrutor desta aula"
   - Botões de presença funcionam normalmente
   - Botão "Voltar" volta para `/instrutor/dashboard.php`

### Cenário 2 – Instrutor Acessando Aula de Outro Instrutor
1. Logar como instrutor Carlos
2. Acessar chamada de uma aula que é de outro instrutor (via URL direta)
3. **Esperado:**
   - No topo: "Carlos da Silva – Instrutor"
   - Aparece alerta "Você não é o instrutor desta aula. Apenas visualização."
   - Botões de presença desabilitados
   - Interface em modo somente leitura

### Cenário 3 – Admin
1. Logar como admin no `/admin`
2. Acessar a mesma tela de chamada direto pelo menu/admin
3. **Esperado:**
   - No topo: nome do admin + "Administrador"
   - Não mostra alerta "Você não é o instrutor desta aula"
   - Permissões de edição como admin (pode editar tudo, exceto turmas canceladas)

## Arquivos Modificados

1. ✅ `admin/pages/turma-chamada.php` - Lógica de permissão e alerta
2. ✅ `admin/pages/turma-diario.php` - Lógica de permissão
3. ✅ `admin/index.php` - Topbar profile
4. ✅ `docs/DIAGNOSTICO_AUTENTICACAO_ADMIN_INSTRUTOR.md` - Documentação técnica

## Funções Utilizadas

- `getCurrentInstrutorId($userId)` - `includes/auth.php` linha 810
  - Obtém o `instrutor_id` a partir do `user_id`
  - Query: `SELECT id FROM instrutores WHERE usuario_id = ? AND ativo = 1`

## Observações Importantes

1. **Não quebrou o fluxo admin:** Todas as mudanças são condicionais baseadas em `$origem` ou `$userType`
2. **Não alterou rotas:** Apenas ajustou lógica de permissão e identidade
3. **Mantém segurança:** Verificações de permissão foram refinadas, não removidas
4. **Compatível com código existente:** Usa funções auxiliares já existentes no sistema

## Próximos Passos (Opcional)

- [ ] Adicionar testes automatizados para validar a lógica de permissão
- [ ] Verificar se há outras páginas que precisam da mesma correção
- [ ] Considerar criar uma função helper centralizada para obter dados de exibição do usuário
