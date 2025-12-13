# Resumo das Correções: Autenticação e Permissões Instrutor vs Admin

## Data: 2025-01-XX

## Problema Identificado

Quando um instrutor acessava a tela de chamada/diário através do painel `/instrutor/dashboard.php`:
1. O topo da tela mostrava "Administrador Sistema – Administrador" em vez do nome do instrutor
2. Ao clicar em "Presente", aparecia erro de conexão ("tente novamente") e no console havia:
   - `Erro: SyntaxError: Unexpected token '<', "<!DOCTYPE "... is not valid JSON`
   - Ou seja, a chamada AJAX esperava JSON mas a API estava devolvendo HTML (provavelmente tela de login ou "sem permissão")

## Correções Aplicadas

### 1. Correção da API `admin/api/turma-presencas.php`

**Problema:** A função `validarRegrasEdicaoPresenca()` comparava `$aula['instrutor_id']` com `$userId` (ID do usuário), quando deveria comparar com o `instrutor_id` real obtido através de `getCurrentInstrutorId()`.

**Correção:**
- Adicionada chamada a `getCurrentInstrutorId($userId)` para obter o `instrutor_id` real
- Comparação corrigida: `$aula['instrutor_id'] != $instrutorAtualId` (em vez de `!= $userId`)
- Aplicada a mesma correção para verificação de aulas na turma (quando não há `aula_id`)

**Arquivo:** `admin/api/turma-presencas.php` (linhas ~476-503)

### 2. Melhoria no Tratamento de Erros JSON no JavaScript

**Problema:** O JavaScript não tratava adequadamente respostas HTML quando a API retornava erro de autenticação/permissão.

**Correção:**
- Implementado tratamento robusto de erros em todas as funções `fetch()`:
  - `criarPresenca()`
  - `atualizarPresenca()`
  - `marcarTodos()` (presenças em lote)
  - `limparTodos()` (exclusão em lote)
- Agora o código:
  1. Lê a resposta como texto primeiro
  2. Tenta fazer parse JSON
  3. Se falhar, mostra mensagem amigável ao usuário
  4. Se for JSON mas `success === false`, mostra a mensagem de erro da API

**Arquivo:** `admin/pages/turma-chamada.php` (linhas ~974-1003, ~1006-1036, ~1133-1159, ~1188-1201)

### 3. Prevenção de Sobrescrita do Topbar pelo JavaScript

**Problema:** O JavaScript `topbar-unified.js` estava sobrescrevendo os valores do PHP com valores hardcoded ("Administrador Sistema", "Administrador").

**Correção:**
- Modificada a função `loadUserProfile()` para verificar se os valores já foram preenchidos pelo PHP
- Se os valores já existirem, não sobrescrever (respeitando a lógica PHP de `origem=instrutor`)
- Modificada a função `displayUserProfile()` para não sobrescrever valores existentes

**Arquivo:** `admin/assets/js/topbar-unified.js` (linhas ~714-738)

### 4. Adição de Alerta de Permissão no Diário

**Correção:**
- Adicionado alerta visual em `turma-diario.php` quando `$mostrarAlertaInstrutor === true`
- Mantém consistência com a página de chamada
- Alerta aparece antes da seção de detalhes da turma

**Arquivo:** `admin/pages/turma-diario.php` (linhas ~223-238)

## Validações Realizadas

### ✅ `admin/index.php`
- Lógica do topbar já estava correta:
  - Detecta `origem=instrutor` ou `user_type === 'instrutor'`
  - Busca nome do instrutor usando `getCurrentInstrutorId()`
  - Exibe "Nome do Instrutor - Instrutor" quando aplicável

### ✅ `admin/pages/turma-chamada.php`
- Lógica de permissão já estava correta:
  - Usa `getCurrentInstrutorId()` para obter `instrutor_id` real
  - Compara com `instrutor_id` da aula
  - Define `$modoSomenteLeitura` e `$mostrarAlertaInstrutor` corretamente
  - Alerta exibido apenas quando `$mostrarAlertaInstrutor === true`

### ✅ `admin/pages/turma-diario.php`
- Lógica de permissão já estava correta:
  - Usa `getCurrentInstrutorId()` para obter `instrutor_id` real
  - Verifica se instrutor tem aulas na turma
  - Define `$modoSomenteLeitura` e `$mostrarAlertaInstrutor` corretamente
  - Alerta adicionado para manter consistência

## Arquivos Modificados

1. ✅ `admin/api/turma-presencas.php` - Correção da validação de permissão
2. ✅ `admin/pages/turma-chamada.php` - Melhoria no tratamento de erros JSON
3. ✅ `admin/assets/js/topbar-unified.js` - Prevenção de sobrescrita do topbar
4. ✅ `admin/pages/turma-diario.php` - Adição de alerta de permissão

## Testes Recomendados

### Cenário 1 – Instrutor Real (Fluxo Correto)
1. Logar como instrutor Carlos no painel `/instrutor/dashboard.php`
2. Clicar em "Chamada" para uma aula que é dele (turma 19, aula 227)
3. **Esperado:**
   - ✅ No topo: "Carlos da Silva – Instrutor" (ou equivalente)
   - ✅ Não aparece alerta "Você não é o instrutor desta aula"
   - ✅ Botões de presença funcionam normalmente (sem erro de JSON)
   - ✅ Botão "Voltar" volta para `/instrutor/dashboard.php`

### Cenário 2 – Instrutor Acessando Aula de Outro Instrutor
1. Logar como instrutor Carlos
2. Acessar chamada de uma aula que é de outro instrutor (via URL direta)
3. **Esperado:**
   - ✅ No topo: "Carlos da Silva – Instrutor"
   - ✅ Aparece alerta "Você não é o instrutor desta aula. Apenas visualização."
   - ✅ Botões de presença desabilitados
   - ✅ Interface em modo somente leitura

### Cenário 3 – Admin
1. Logar como admin no `/admin`
2. Acessar a mesma tela de chamada direto pelo menu/admin
3. **Esperado:**
   - ✅ No topo: nome do admin + "Administrador"
   - ✅ Não mostra alerta "Você não é o instrutor desta aula"
   - ✅ Permissões de edição como admin (pode editar tudo, exceto turmas canceladas)

## Observações Importantes

1. **Não quebrou o fluxo admin:** Todas as mudanças são condicionais baseadas em `$origem` ou `$userType`
2. **Não alterou rotas:** Apenas ajustou lógica de permissão e identidade
3. **Mantém segurança:** Verificações de permissão foram refinadas, não removidas
4. **Compatível com código existente:** Usa funções auxiliares já existentes no sistema (`getCurrentInstrutorId()`)

## Próximos Passos (Opcional)

- [ ] Adicionar testes automatizados para validar a lógica de permissão
- [ ] Verificar se há outras APIs que precisam da mesma correção
- [ ] Considerar criar uma função helper centralizada para obter dados de exibição do usuário
