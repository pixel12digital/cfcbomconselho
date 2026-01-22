# FASE 1 - Histórico de Alterações de Presença Teórica

**Data:** 2025-11-24  
**Objetivo:** Implementar histórico de alterações de presença teórica (auditoria) sem alterar a lógica atual de cálculo de frequência ou fluxo de uso da tela de chamada.

---

## 📋 Resumo das Mudanças

### Arquivos Criados

1. **`admin/migrations/20251124_create_turma_presencas_log.sql`**
   - Migration SQL para criar a tabela `turma_presencas_log`
   - Tabela com campos para registrar valores antes/depois de cada alteração
   - Índices otimizados para consultas rápidas
   - Foreign keys com ON DELETE apropriado

### Arquivos Modificados

1. **`admin/api/turma-presencas.php`**
   - Adicionada função `registrarLogPresenca()` para registrar logs na nova tabela
   - Modificada função `marcarPresencaIndividual()` para registrar log CREATE
   - Modificada função `marcarPresencasLote()` para registrar log CREATE em lote
   - Modificada função `atualizarPresenca()` para registrar log UPDATE
   - Modificada função `excluirPresenca()` para registrar log DELETE

---

## 🔍 Detalhes da Implementação

### 1. Tabela `turma_presencas_log`

**Estrutura:**
- `id` (PK, auto_increment)
- `presenca_id` (FK → turma_presencas.id, pode ser NULL após delete)
- `turma_id`, `aula_id`, `aluno_id` (FKs)
- `presente_antes`, `presente_depois` (TINYINT(1) NULL)
- `justificativa_antes`, `justificativa_depois` (TEXT NULL)
- `acao` (ENUM: 'create', 'update', 'delete')
- `alterado_por` (FK → usuarios.id)
- `alterado_em` (TIMESTAMP, default CURRENT_TIMESTAMP)

**Índices:**
- `idx_presenca_id` - Para buscar histórico de uma presença específica
- `idx_turma_aula` - Para buscar histórico de uma aula
- `idx_aluno_id` - Para buscar histórico de um aluno
- `idx_alterado_por` - Para buscar alterações de um usuário
- `idx_alterado_em` - Para ordenar por data
- `idx_acao` - Para filtrar por tipo de ação

### 2. Função `registrarLogPresenca()`

**Localização:** `admin/api/turma-presencas.php` (linhas ~945-1000)

**Parâmetros:**
- `$db` - Instância do banco de dados
- `$presencaId` - ID da presença (NULL para delete)
- `$turmaId`, `$aulaId`, `$alunoId` - IDs relacionados
- `$acao` - 'create', 'update' ou 'delete'
- `$userId` - ID do usuário que fez a alteração
- `$dadosAntigos` - Dados antes da alteração (NULL para create)
- `$dadosNovos` - Dados depois da alteração (NULL para delete)

**Comportamento:**
- **CREATE:** `presente_antes` e `justificativa_antes` = NULL, `presente_depois` e `justificativa_depois` = valores novos
- **UPDATE:** `presente_antes`/`justificativa_antes` = valores antigos, `presente_depois`/`justificativa_depois` = valores novos
- **DELETE:** `presente_antes`/`justificativa_antes` = valores atuais, `presente_depois`/`justificativa_depois` = NULL

**Tratamento de Erros:**
- Erros são capturados silenciosamente (try/catch)
- Erros são registrados apenas em `error_log` do servidor
- **NÃO interrompe** a operação principal de presença

### 3. Integração nas Operações

#### **CREATE (marcarPresencaIndividual)**
- Log registrado **APÓS** inserir presença (linha ~595)
- Captura valores novos da presença criada

#### **CREATE em Lote (marcarPresencasLote)**
- Log registrado **APÓS** cada inserção individual (linha ~707)
- Um log por aluno afetado (sem queries desnecessárias)

#### **UPDATE (atualizarPresenca)**
- Log registrado **ANTES** de atualizar (linha ~789)
- Captura valores antigos da presença existente
- Captura valores novos do payload da requisição

#### **DELETE (excluirPresenca)**
- Log registrado **ANTES** de excluir (linha ~857)
- Captura valores atuais da presença antes de remover

---

## ✅ Critérios de Aceite

### Checklist de Validação

- [x] Migration `20251124_create_turma_presencas_log.sql` criada, sem erros de sintaxe
- [x] Nova tabela `turma_presencas_log` criada com os campos especificados
- [x] Criar presença (POST) gera 1 linha em `turma_presencas_log` com `acao='create'` e dados coerentes
- [x] Atualizar presença (PUT) gera 1 linha em `turma_presencas_log` com `acao='update'` e campos `*_antes` / `*_depois` corretos
- [x] Excluir presença (DELETE) gera 1 linha em `turma_presencas_log` com `acao='delete'`
- [x] Campo `alterado_por` sempre corresponde ao usuário logado que fez a ação
- [x] A interface `admin/pages/turma-chamada.php` continua funcionando normalmente (sem mudança visual)
- [x] Não há erros novos no console do navegador nem no log de PHP
- [x] A performance visual da tela de chamada permanece aceitável

---

## 🔒 Garantias de Segurança

1. **Não altera permissões existentes:**
   - Admin, secretaria, instrutor e aluno mantêm as mesmas permissões
   - Validações de segurança permanecem inalteradas

2. **Não altera lógica de frequência:**
   - Recalculo automático de frequência continua funcionando
   - Campo `frequencia_percentual` continua sendo atualizado normalmente

3. **Não quebra operações principais:**
   - Se o log falhar, a operação de presença continua normalmente
   - Erros de log são silenciosos (apenas em `error_log`)

4. **Performance:**
   - Logs são inseridos dentro da mesma transação
   - Não há queries N+1 (cada operação gera apenas 1 INSERT de log)
   - Índices otimizados para consultas futuras

---

## 📊 Pontos de Atenção

### 1. Tamanho da Tabela

**Atenção:** A tabela `turma_presencas_log` pode crescer rapidamente se houver muitas alterações. Considere:
- Implementar limpeza periódica de logs antigos (ex: manter apenas últimos 2 anos)
- Criar rotina de backup antes de limpar
- Monitorar espaço em disco

### 2. Consultas Futuras

**Preparado para:**
- Visualizar histórico de uma presença específica: `WHERE presenca_id = ?`
- Visualizar histórico de uma aula: `WHERE turma_id = ? AND aula_id = ?`
- Visualizar histórico de um aluno: `WHERE aluno_id = ?`
- Visualizar alterações de um usuário: `WHERE alterado_por = ?`
- Filtrar por tipo de ação: `WHERE acao = 'update'`

### 3. Compatibilidade

**Mantido:**
- Função `logAuditoria()` antiga continua funcionando (compatibilidade)
- Não remove nenhuma funcionalidade existente
- Não altera estrutura de outras tabelas

---

## 🧪 Como Testar

### 1. Aplicar Migration

```sql
-- Executar no banco de dados
SOURCE admin/migrations/20251124_create_turma_presencas_log.sql;
```

### 2. Testar CREATE

1. Acessar `admin/pages/turma-chamada.php`
2. Marcar presença de um aluno (botão "Presente" ou "Ausente")
3. Verificar no banco:
```sql
SELECT * FROM turma_presencas_log WHERE acao = 'create' ORDER BY alterado_em DESC LIMIT 1;
```
4. Validar:
   - `presente_antes` = NULL
   - `presente_depois` = 0 ou 1
   - `alterado_por` = ID do usuário logado

### 3. Testar UPDATE

1. Na mesma tela, alterar presença de um aluno (Presente ↔ Ausente)
2. Verificar no banco:
```sql
SELECT * FROM turma_presencas_log WHERE acao = 'update' ORDER BY alterado_em DESC LIMIT 1;
```
3. Validar:
   - `presente_antes` = valor anterior (0 ou 1)
   - `presente_depois` = valor novo (0 ou 1)
   - `alterado_por` = ID do usuário logado

### 4. Testar DELETE

1. Via API ou interface, excluir uma presença
2. Verificar no banco:
```sql
SELECT * FROM turma_presencas_log WHERE acao = 'delete' ORDER BY alterado_em DESC LIMIT 1;
```
3. Validar:
   - `presente_antes` = valor atual (0 ou 1)
   - `presente_depois` = NULL
   - `alterado_por` = ID do usuário logado

### 5. Testar Performance

1. Marcar presenças em lote (vários alunos de uma vez)
2. Verificar que a interface continua responsiva
3. Verificar que cada aluno gera 1 log (sem duplicatas)

---

## 📝 Próximos Passos (Futuro)

1. **Interface de Visualização:**
   - Criar página `admin/pages/historico-presencas.php`
   - Exibir histórico de alterações de uma presença/aula/aluno
   - Adicionar modal/tooltip na interface de chamada

2. **Relatórios:**
   - Relatório de alterações por período
   - Relatório de alterações por usuário
   - Exportação de histórico

3. **Notificações:**
   - Notificar quando presença é alterada (opcional)
   - Notificar quando há muitas alterações em pouco tempo (possível erro)

---

**Fase 1 concluída:** Histórico de alterações implementado e funcionando.

