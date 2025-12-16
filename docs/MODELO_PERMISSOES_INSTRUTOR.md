# Modelo de Permissões - Instrutor

## Data: 2025-01-XX

## Visão Geral

Este documento descreve o modelo de permissões consolidado para o perfil de **Instrutor** no sistema CFC Bom Conselho. O instrutor possui acesso restrito a funcionalidades relacionadas às suas turmas e aulas, com foco em registro de presenças e visualização de dados básicos dos alunos.

---

## 1. Identidade e Autenticação

### 1.1. Relação Usuário → Instrutor

- **Tabela:** `usuarios` (user_id) → `instrutores` (instrutor_id)
- **Campo de ligação:** `instrutores.usuario_id = usuarios.id`
- **Função helper:** `getCurrentInstrutorId($userId)` em `includes/auth.php`

### 1.2. Detecção de Fluxo de Instrutor

O sistema detecta quando um instrutor está acessando telas administrativas através de:

- **Parâmetro URL:** `origem=instrutor`
- **Tipo de usuário:** `$_SESSION['user_type'] === 'instrutor'`

Quando detectado, o sistema:
1. Busca o `instrutor_id` real usando `getCurrentInstrutorId()`
2. Ajusta a identidade exibida na topbar
3. Aplica regras de permissão específicas

---

## 2. Rotas e Permissões

### 2.1. Rotas Permitidas

#### ✅ Painel do Instrutor
- **Rota:** `/instrutor/dashboard.php`
- **Permissão:** Acesso total ao próprio painel
- **Funcionalidades:**
  - Ver próximas aulas (teóricas e práticas)
  - Ver quantidade de aulas do dia, pendentes, concluídas
  - Ver pendências de chamadas teóricas
  - Abrir Chamada da aula → redireciona para `admin/index.php?page=turma-chamada&...&origem=instrutor`
  - Abrir Diário da turma → redireciona para `admin/index.php?page=turma-diario&...&origem=instrutor`

#### ✅ Chamada Teórica
- **Rota:** `admin/index.php?page=turma-chamada&turma_id=X&aula_id=Y&origem=instrutor`
- **Arquivo:** `admin/pages/turma-chamada.php`
- **Permissão:** 
  - **Edição:** Apenas se for o instrutor da aula específica
  - **Visualização:** Pode ver qualquer aula da turma (modo somente leitura)
- **Validação:**
  - Verifica se `instrutor_id` da aula corresponde ao instrutor logado
  - Se não for o instrutor, exibe alerta e desabilita botões de edição
- **Funcionalidades:**
  - Ver dados da turma, data/hora/descrição da aula
  - Ver lista de alunos matriculados
  - Marcar presença/ausência individual (se for instrutor da aula)
  - Marcar todos presentes/ausentes (se for instrutor da aula)
  - Limpar marcações (se for instrutor da aula)

#### ✅ Diário da Turma
- **Rota:** `admin/index.php?page=turma-diario&turma_id=X&origem=instrutor`
- **Arquivo:** `admin/pages/turma-diario.php`
- **Permissão:**
  - **Visualização:** Pode ver se tem aulas na turma
  - **Edição:** Não pode editar dados da turma
- **Funcionalidades:**
  - Ver dados da turma
  - Ver lista de alunos matriculados
  - Ver resumo de frequência teórica por aluno (dentro desta turma)
  - Visualizar detalhes básicos do aluno (via modal restrito)

#### ✅ Detalhes do Aluno (Restrito)
- **Endpoint:** `admin/api/aluno-detalhes-instrutor.php?aluno_id=X&turma_id=Y`
- **Permissão:** Apenas se o instrutor tiver aulas na turma e o aluno estiver matriculado
- **Dados retornados:**
  - Dados básicos: nome, CPF, email, telefone, data nascimento, categoria CNH
  - Dados de matrícula na turma atual: status, data matrícula
  - Resumo de frequência teórica: percentual, total presentes/ausentes, histórico (últimas 10 aulas)
- **Dados NÃO retornados:**
  - Dados financeiros
  - Documentos sigilosos
  - Histórico completo de todos os CFCS/turmas
  - Dados administrativos

### 2.2. Rotas Bloqueadas

#### ❌ Gestão Geral de Alunos
- **Rota:** `admin/index.php?page=alunos`
- **Ação:** Redireciona para `/instrutor/dashboard.php` com mensagem de permissão negada

#### ❌ Histórico Global do Aluno
- **Rota:** `admin/index.php?page=historico-aluno&id=X`
- **Ação:** Redireciona para `/instrutor/dashboard.php` com mensagem de permissão negada

#### ❌ Ações Administrativas de Aluno
- **Rotas bloqueadas:**
  - `admin/index.php?page=alunos&action=view&id=X`
  - `admin/index.php?page=alunos&action=edit&id=X`
  - `admin/index.php?page=alunos&action=create`
- **Ação:** Redireciona para `/instrutor/dashboard.php` com mensagem de permissão negada

---

## 3. Registro de Presenças

### 3.1. Estrutura de Dados

#### Tabela: `turma_presencas`
- `id` (PK)
- `turma_id` (FK para `turmas_teoricas.id`)
- `turma_aula_id` (FK para `turma_aulas_agendadas.id`) ⚠️ **Nome correto do campo**
- `aluno_id` (FK para `alunos.id`)
- `presente` (TINYINT: 0 = ausente, 1 = presente)
- `registrado_por` (FK para `usuarios.id`)
- `registrado_em` (TIMESTAMP)

#### Tabela: `turma_matriculas`
- `frequencia_percentual` (DECIMAL): Calculado automaticamente após cada registro de presença

### 3.2. API de Presenças

#### Endpoint: `admin/api/turma-presencas.php`

**Validações para Instrutor:**
1. Verifica se é instrutor da aula específica (`turma_aulas_agendadas.instrutor_id`)
2. Verifica se turma não está cancelada
3. Verifica se turma não está concluída (instrutor não pode editar presenças de turmas concluídas)
4. Verifica se aula não está cancelada

**Métodos:**
- **POST:** Criar presença individual ou em lote
- **PUT:** Atualizar presença existente
- **DELETE:** Excluir presença
- **GET:** Listar presenças (com filtros)

### 3.3. Cálculo de Frequência

#### Função: `recalcularFrequenciaAluno()`
**Arquivo:** `admin/includes/TurmaTeoricaManager.php`

**Lógica:**
1. Conta total de aulas válidas da turma (`turma_aulas_agendadas` com status 'agendada' ou 'realizada')
2. Conta presenças do aluno onde `presente = 1` e aula está válida
3. Calcula: `(total_presentes / total_aulas_validas) * 100`
4. Atualiza `turma_matriculas.frequencia_percentual`

**Chamada automática:**
- Após criar presença (POST)
- Após atualizar presença (PUT)
- Após excluir presença (DELETE)

---

## 4. Exibição de Frequência

### 4.1. Histórico do Aluno

**Arquivo:** `admin/pages/historico-aluno.php`

**Query corrigida:**
```sql
SELECT 
    tp.turma_aula_id as aula_id,  -- CORRIGIDO: usar turma_aula_id
    tp.presente,
    tp.justificativa,
    tp.registrado_em
FROM turma_presencas tp
WHERE tp.turma_id = ? AND tp.aluno_id = ?
```

**Exibição:**
- Lista de turmas teóricas do aluno
- Para cada turma: frequência percentual, status de presença por aula
- Badge colorido: verde (≥75%), amarelo (60-74%), vermelho (<60%)

### 4.2. Diário da Turma

**Arquivo:** `admin/pages/turma-diario.php`

**Dados exibidos:**
- Frequência percentual vem de `turma_matriculas.frequencia_percentual` (já calculado)
- Cards de resumo: Total de alunos, Presentes, Ausentes, Frequência média
- Lista de alunos com status de presença

### 4.3. API de Frequência

**Arquivo:** `admin/api/turma-frequencia.php`

**Queries corrigidas:**
- Todas as queries agora usam `tp.turma_aula_id` em vez de `tp.aula_id`
- JOIN correto: `INNER JOIN turma_aulas_agendadas taa ON tp.turma_aula_id = taa.id`

---

## 5. Topbar e Identidade

### 5.1. Lógica de Exibição

**Arquivo:** `admin/index.php` (linhas ~38-64)

**Quando `origem=instrutor` ou `user_type=instrutor`:**
1. Busca `instrutor_id` usando `getCurrentInstrutorId($userId)`
2. Busca nome do instrutor em `instrutores.nome`
3. Exibe: "Nome do Instrutor – Instrutor"

**JavaScript:**
- `admin/assets/js/topbar-unified.js` não sobrescreve valores do PHP quando já preenchidos
- Respeita a lógica PHP de identidade do instrutor

### 5.2. Validação

**Em todas as telas acessadas pelo instrutor:**
- Topbar deve mostrar nome do instrutor correto
- Não deve exibir "Administrador" por engano
- JavaScript não deve sobrescrever dados do PHP

---

## 6. Fluxo de Acesso às Telas de Aluno

### 6.1. Modal de Detalhes (Modo Instrutor)

**Arquivo:** `admin/pages/turma-diario.php`

**Quando `origem=instrutor`:**
- Botão "Visualizar" chama `visualizarAlunoInstrutor(alunoId, turmaId)`
- Faz requisição AJAX para `admin/api/aluno-detalhes-instrutor.php`
- Exibe modal somente leitura com dados básicos + frequência da turma atual

**Quando não é instrutor:**
- Botão "Visualizar" usa link normal: `?page=alunos&action=view&id=X`
- Carrega modal administrativo completo

### 6.2. Bloqueio de Rotas

**Arquivo:** `admin/index.php` (linhas ~110-130)

**Rotas bloqueadas para instrutor:**
- `page=alunos` (qualquer ação)
- `page=historico-aluno` (qualquer ação)

**Ação:** Redireciona para `/instrutor/dashboard.php` com mensagem de permissão negada

---

## 7. Testes Manuais

### Cenário A – Registro de Presença Correto
1. ✅ Logar como instrutor
2. ✅ Ir para `/instrutor/dashboard.php`
3. ✅ Abrir Chamada da turma
4. ✅ Marcar aluno como Presente
5. ✅ Validar toaster de sucesso
6. ✅ Validar registro em `turma_presencas`
7. ✅ Tentar marcar novamente → mensagem "Presença já registrada"

### Cenário B – Frequência Refletindo no Histórico
1. ✅ Registrar presença como instrutor
2. ✅ Acessar Histórico do Aluno (como admin ou instrutor)
3. ✅ Validar frequência > 0%
4. ✅ Validar aula aparece como "PRESENTE"

### Cenário C – Diário da Turma
1. ✅ Acessar Diário da turma como instrutor
2. ✅ Validar cards de presentes/ausentes/frequência média atualizados
3. ✅ Validar lista de alunos mostra status correto

### Cenário D – Acesso às Telas de Aluno
1. ✅ Em turma-diario.php (origem=instrutor), clicar em Visualizar aluno
2. ✅ Validar modal abre sem erro 403
3. ✅ Validar mostra apenas dados básicos + resumo de frequência
4. ✅ Validar não tem botões de "Desativar", "Editar", etc.
5. ✅ Forçar URL `admin/index.php?page=alunos` com instrutor logado
6. ✅ Validar redireciona para `/instrutor/dashboard.php` com mensagem

---

## 8. Arquivos Modificados

### 8.1. Novos Arquivos
- ✅ `admin/api/aluno-detalhes-instrutor.php` - Endpoint restrito para instrutor

### 8.2. Arquivos Modificados
- ✅ `admin/index.php` - Bloqueio de rotas administrativas
- ✅ `admin/pages/turma-diario.php` - Modal restrito para instrutor
- ✅ `admin/pages/historico-aluno.php` - Correção de query (turma_aula_id)
- ✅ `admin/api/turma-frequencia.php` - Correção de queries (turma_aula_id)
- ✅ `admin/includes/TurmaTeoricaManager.php` - Correção de query (turma_aula_id)

### 8.3. Arquivos Validados (sem mudanças)
- ✅ `admin/pages/turma-chamada.php` - Já estava correto
- ✅ `admin/api/turma-presencas.php` - Já estava correto
- ✅ `admin/assets/js/topbar-unified.js` - Já estava correto

---

## 9. Observações Importantes

### 9.1. Nome do Campo
⚠️ **IMPORTANTE:** O campo correto na tabela `turma_presencas` é `turma_aula_id`, não `aula_id`.

Todas as queries foram corrigidas para usar o nome correto:
- `tp.turma_aula_id` (correto)
- ~~`tp.aula_id`~~ (incorreto, foi usado em algumas queries antigas)

### 9.2. Compatibilidade
- Todas as mudanças são condicionais baseadas em `user_type` ou `origem`
- Não quebra o fluxo atual de admin/secretaria
- Preserva estrutura atual de arquivos

### 9.3. Segurança
- Verificações de permissão foram refinadas, não removidas
- Instrutor só pode ver/editar dados relacionados às suas próprias turmas/aulas
- Endpoint restrito valida se instrutor tem aulas na turma antes de retornar dados

---

## 10. Próximos Passos (Opcional)

- [ ] Adicionar testes automatizados para validar lógica de permissão
- [ ] Considerar criar função helper centralizada para obter dados de exibição do usuário
- [ ] Documentar fluxo completo de presenças em diagrama
- [ ] Adicionar logs de auditoria para acessos de instrutor a dados de aluno

---

## Referências

- `docs/DIAGNOSTICO_AUTENTICACAO_ADMIN_INSTRUTOR.md`
- `docs/RESUMO_CORRECOES_AUTENTICACAO_INSTRUTOR.md`
- `docs/RESUMO_CORRECOES_AUTENTICACAO_INSTRUTOR_2025.md`


