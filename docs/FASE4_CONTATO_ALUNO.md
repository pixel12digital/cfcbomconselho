# FASE 4: Contato do Aluno com o CFC - Documentação

**Data:** 2025-11-24  
**Status:** ✅ Concluída

## Resumo Executivo

Implementação completa do sistema de contato do aluno com o CFC, permitindo que o aluno envie mensagens para a secretaria e acompanhe o histórico de suas mensagens enviadas.

## Arquivos Criados

### 1. `aluno/contato.php`
- **Descrição:** Página principal de contato do aluno
- **Funcionalidades:**
  - Exibe informações de contato da secretaria (WhatsApp, E-mail, Telefone, Horário, Endereço)
  - Formulário para enviar mensagem para secretaria
  - Lista de mensagens enviadas pelo aluno
  - Status das mensagens (Recebido, Em Análise, Respondido, Arquivado)
  - Respostas da secretaria (quando disponível)
  - Validação de formulário (assunto mínimo 5 caracteres, mensagem mínimo 10 caracteres)
  - Select de aulas relacionadas (práticas e teóricas)
- **Segurança:**
  - Usa `getCurrentAlunoId()` para identificar o aluno
  - Não aceita `aluno_id` via GET/POST
  - Valida que aula relacionada pertence ao aluno
  - Filtra todas as queries por `aluno_id` do aluno logado
- **Resiliência:**
  - Verifica se tabela `contatos_aluno` existe
  - Exibe mensagem amigável se tabela não existir
  - Não quebra a página se tabela não existir

### 2. `docs/scripts/migration_contatos_aluno.sql`
- **Descrição:** Script SQL para criar tabela `contatos_aluno`
- **Estrutura:**
  - Baseada em `contatos_instrutor`
  - Campos: `id`, `aluno_id`, `usuario_id`, `tipo_assunto`, `assunto`, `mensagem`, `aula_id`, `turma_id`, `status`, `resposta`, `respondido_por`, `respondido_em`, `criado_em`, `atualizado_em`
  - Foreign Keys para `alunos`, `usuarios`, `aulas`, `turmas_teoricas`
  - Índices para performance

## Arquivos Modificados

### 1. `aluno/dashboard.php`
- **Alterações:**
  - Removido `alert()` temporário
  - Função `contatarCFC()` redireciona para `contato.php`
- **Comentários:** `// FASE 4 - CONTATO ALUNO - ...`

## Como Funciona o Fluxo de Contato

### 1. Envio de Mensagem

1. **Aluno acessa `aluno/contato.php`**
   - Sistema verifica se está logado como aluno
   - Obtém `aluno_id` via `getCurrentAlunoId()`

2. **Aluno preenche formulário:**
   - Tipo de assunto (opcional): Dúvida sobre aulas, Financeiro, Documentação, Exames, Outro
   - Assunto (obrigatório, mínimo 5 caracteres)
   - Aula relacionada (opcional): Select com aulas práticas e teóricas do aluno
   - Mensagem (obrigatória, mínimo 10 caracteres)

3. **Validações:**
   - Backend valida tamanho mínimo de assunto e mensagem
   - Se `aula_id` for fornecido, valida que a aula pertence ao aluno
   - Verifica se tabela `contatos_aluno` existe

4. **Inserção no banco:**
   - Insere registro em `contatos_aluno` com:
     - `aluno_id` = aluno logado
     - `usuario_id` = usuário logado
     - `status` = 'aberto'
     - Dados do formulário

5. **Feedback:**
   - Redireciona com `?success=1`
   - Exibe mensagem de sucesso

### 2. Listagem de Mensagens

1. **Busca mensagens do aluno:**
   - Query filtra por `aluno_id = aluno_id_logado`
   - Ordena por `criado_em DESC` (mais recentes primeiro)
   - Limite de 50 mensagens

2. **Exibe informações:**
   - Status com badge colorido
   - Tipo de assunto (se informado)
   - Assunto e mensagem
   - Data/hora de envio
   - Aula relacionada (se houver)
   - Resposta da secretaria (se houver)

## Segurança Implementada

### Back-end (`aluno/contato.php`)
- ✅ Verifica se usuário está logado e é do tipo 'aluno'
- ✅ Usa `getCurrentAlunoId()` para obter `aluno_id`
- ✅ Todas as queries filtram por `aluno_id` do aluno logado
- ✅ Não aceita `aluno_id` via GET/POST
- ✅ Valida que aula relacionada pertence ao aluno (se fornecida)
- ✅ Valida tamanho mínimo de assunto (5 caracteres) e mensagem (10 caracteres)

### Banco de Dados
- ✅ Foreign Keys garantem integridade referencial
- ✅ Índices em `aluno_id` para performance
- ✅ `ON DELETE CASCADE` para limpeza automática

## Estrutura da Tabela `contatos_aluno`

```sql
CREATE TABLE contatos_aluno (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aluno_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo_assunto VARCHAR(100) NULL,
    assunto VARCHAR(255) NOT NULL,
    mensagem TEXT NOT NULL,
    aula_id INT NULL,
    turma_id INT NULL,
    status ENUM('aberto', 'em_atendimento', 'respondido', 'fechado') DEFAULT 'aberto',
    resposta TEXT NULL,
    respondido_por INT NULL,
    respondido_em DATETIME NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    ...
)
```

## Testes de Segurança

### Cenário 1: Aluno tenta vincular aula de outro aluno
- **Teste:** Aluno logado tenta enviar mensagem com `aula_id` de outro aluno
- **Resultado esperado:** Erro "Aula não encontrada ou não pertence a você"
- **Status:** ✅ Implementado

### Cenário 2: Aluno tenta ver mensagens de outro aluno
- **Teste:** Aluno logado tenta acessar mensagens via SQL injection ou manipulação de URL
- **Resultado esperado:** Query sempre filtra por `aluno_id` do aluno logado
- **Status:** ✅ Implementado

### Cenário 3: Tabela não existe
- **Teste:** Acessar `aluno/contato.php` quando tabela `contatos_aluno` não existe
- **Resultado esperado:** Exibe mensagem amigável, não quebra a página
- **Status:** ✅ Implementado

## Compatibilidade

### Reaproveitamento
- ✅ Estrutura baseada em `instrutor/contato.php`
- ✅ Mesma lógica de validação
- ✅ Mesmo layout visual (adaptado para aluno)
- ✅ Mesmas informações de contato da secretaria

### Diferenças do Instrutor
- **Tabela:** `contatos_aluno` ao invés de `contatos_instrutor`
- **Campo:** `aluno_id` ao invés de `instrutor_id`
- **Aulas:** Busca aulas práticas E teóricas do aluno (instrutor só práticas)
- **Select de aulas:** Inclui turmas teóricas do aluno

## Próximos Passos (Futuro - Admin)

1. **Painel de Gerenciamento de Contatos:**
   - Criar página admin para visualizar todos os contatos
   - Permitir responder mensagens
   - Atualizar status (em_atendimento, respondido, fechado)
   - Filtrar por aluno, status, período

2. **Notificações:**
   - Notificar secretaria quando aluno envia mensagem
   - Notificar aluno quando secretaria responde

3. **Estatísticas:**
   - Dashboard com métricas de contatos
   - Tempo médio de resposta
   - Contatos por tipo de assunto

## Notas Técnicas

- A página é resiliente: se a tabela não existir, exibe mensagem amigável
- O formulário é desabilitado se a tabela não existir
- As aulas são buscadas das últimas 30 dias (práticas e teóricas)
- O select de aulas combina práticas e teóricas em uma única lista ordenada
- A validação de aula relacionada garante que o aluno não pode vincular aula de outro aluno

---

**FASE 4 concluída com sucesso! ✅**

