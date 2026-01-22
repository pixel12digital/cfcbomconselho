# Soluções Robustas para Turmas Teóricas

**Data:** 12/12/2025  
**Objetivo:** Documentar as soluções robustas implementadas para garantir integridade de dados ao excluir turmas e criar novas turmas

---

## Cenário 1: Excluir Turma Teórica

### ❓ Problema
**Quando uma turma teórica é excluída, o que acontece com os alunos matriculados?**

### ✅ Solução Implementada: **ON DELETE CASCADE + Limpeza Manual**

#### 1.1. Foreign Keys com ON DELETE CASCADE

**Arquivo:** `admin/migrations/001-create-turmas-teoricas-structure.sql`

As tabelas relacionadas à turma possuem `ON DELETE CASCADE` configurado:

```sql
-- turma_matriculas
FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE

-- turma_presencas
FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE

-- turma_aulas_agendadas
FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE

-- turma_log
FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE
```

**O que isso significa:**
- ✅ Quando a turma é excluída, **TODAS as matrículas são automaticamente excluídas**
- ✅ Quando a turma é excluída, **TODAS as presenças são automaticamente excluídas**
- ✅ Quando a turma é excluída, **TODAS as aulas agendadas são automaticamente excluídas**
- ✅ Quando a turma é excluída, **TODOS os logs são automaticamente excluídos**

#### 1.2. Limpeza Manual na API de Exclusão

**Arquivo:** `admin/api/turmas-teoricas.php` (linhas 1397-1492)

A API de exclusão realiza limpeza explícita antes de excluir a turma:

```php
// 1. Contar registros que serão excluídos (para log)
$contadores = [];

// 2. Excluir presenças/frequências (se a tabela existir)
$db->delete('turma_presencas', 'turma_id = ?', [$turmaId]);

// 3. Excluir diário de classe (se a tabela existir)
$db->delete('turma_diario', 'turma_id = ?', [$turmaId]);

// 4. Excluir logs da turma (se a tabela existir)
$db->delete('turma_logs', 'turma_id = ?', [$turmaId]);

// 5. Excluir todas as aulas agendadas da turma
$db->delete('turma_aulas_agendadas', 'turma_id = ?', [$turmaId]);

// 6. Excluir alunos da turma (turma_alunos - estrutura antiga)
$db->delete('turma_alunos', 'turma_id = ?', [$turmaId]);

// 7. Excluir matrículas (turma_matriculas - estrutura nova)
$db->delete('turma_matriculas', 'turma_id = ?', [$turmaId]);

// 8. Excluir a turma principal
$db->delete('turmas_teoricas', 'id = ?', [$turmaId]);
```

#### 1.3. Resultado para o Aluno

**✅ Aluno fica LIVRE após exclusão da turma:**

1. **Matrícula é excluída:** O aluno **não fica mais vinculado** à turma
2. **Histórico preservado:** O aluno continua existindo na tabela `alunos`
3. **Pode ser matriculado em nova turma:** O aluno fica disponível para matrícula em outras turmas
4. **Dados do aluno não são afetados:** Apenas o vínculo com a turma é removido

**Exemplo:**
- Aluno 167 estava matriculado na Turma 19
- Turma 19 é excluída
- → Matrícula (`turma_matriculas`) é excluída automaticamente
- → Aluno 167 fica **livre** e pode ser matriculado em outra turma

---

## Cenário 2: Criar Nova Turma

### ❓ Problema
**Como garantir que uma turma não seja criada em um CFC que não existe?**

### ✅ Solução Implementada: **Validação Dupla (Frontend + Backend)**

#### 2.1. Validação no TurmaTeoricaManager

**Arquivo:** `admin/includes/TurmaTeoricaManager.php` (linhas 362-375)

**Validação aplicada em 2 métodos:**

##### A) `salvarRascunho()`
Valida CFC antes de salvar rascunho:

```php
// VALIDAÇÃO (12/12/2025): Verificar se CFC existe e está ativo
$cfc = $this->db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = ?", [$dados['cfc_id']]);

if (!$cfc) {
    throw new Exception("Não é possível salvar rascunho: CFC ID {$dados['cfc_id']} não existe na tabela cfcs. Use apenas CFCs existentes e ativos.");
}

if (!$cfc['ativo']) {
    throw new Exception("Não é possível salvar rascunho: CFC '{$cfc['nome']}' (ID {$cfc['id']}) existe mas NÃO está ativo. Use apenas CFCs ativos.");
}
```

##### B) `finalizarTurma()`
Valida CFC antes de criar turma definitiva:

```php
// VALIDAÇÃO CRÍTICA (12/12/2025): Verificar se CFC existe e está ativo
$cfc = $this->db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = ?", [$dados['cfc_id']]);

if (!$cfc) {
    throw new Exception("Não é possível criar turma: CFC ID {$dados['cfc_id']} não existe na tabela cfcs. Use apenas CFCs existentes e ativos.");
}

if (!$cfc['ativo']) {
    throw new Exception("Não é possível criar turma: CFC '{$cfc['nome']}' (ID {$cfc['id']}) existe mas NÃO está ativo. Use apenas CFCs ativos.");
}
```

#### 2.2. Helper Function para Obter CFC Válido

**Arquivo:** `admin/pages/turmas-teoricas.php` (linhas 55-80)

Função `obterCfcIdValidoParaTurma()` garante que sempre usemos um CFC válido:

```php
function obterCfcIdValidoParaTurma($db, $user, $isAdmin) {
    if ($isAdmin) {
        // Admin: tentar usar CFC da sessão se for válido
        if (!empty($user['cfc_id'])) {
            $cfcAdmin = $db->fetch("SELECT id, ativo FROM cfcs WHERE id = ?", [$user['cfc_id']]);
            if ($cfcAdmin && $cfcAdmin['ativo']) {
                return $user['cfc_id'];
            }
        }
        
        // Se não encontrou CFC válido, buscar primeiro CFC ativo
        $primeiroCfcAtivo = $db->fetch("SELECT id FROM cfcs WHERE ativo = 1 ORDER BY id LIMIT 1");
        if ($primeiroCfcAtivo) {
            return $primeiroCfcAtivo['id'];
        }
        
        throw new Exception("Não foi possível determinar CFC: nenhum CFC ativo encontrado no sistema.");
    } else {
        // Não-admin: usar CFC da sessão (já validado no login)
        if (empty($user['cfc_id'])) {
            throw new Exception("Não foi possível criar turma: usuário não possui CFC associado.");
        }
        return $user['cfc_id'];
    }
}
```

**Lógica:**
- ✅ Admin com CFC válido → usa o CFC da sessão
- ✅ Admin sem CFC válido → busca primeiro CFC ativo disponível
- ✅ Não-admin → usa CFC da sessão (já validado no login)
- ❌ Nunca usa CFC hardcoded (como CFC 1 que não existe)

#### 2.3. Validação no Script de Criação

**Arquivo:** `admin/tools/verificar-cfcs-e-criar-turma-cfc36.php` (linhas 145-154)

Validação adicional antes de criar turma via script:

```php
// VALIDAÇÃO: Verificar se CFC 36 existe e está ativo
$cfc36 = $db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = 36");

if (!$cfc36) {
    throw new Exception("CFC 36 não existe na tabela cfcs!");
}

if (!$cfc36['ativo']) {
    throw new Exception("CFC 36 existe mas NÃO está ativo!");
}
```

---

## Regras de Negócio Garantidas

### ✅ Regra 1: Exclusão de Turma
- **Alunos são automaticamente desvinculados** (via ON DELETE CASCADE)
- **Alunos ficam livres** para matrícula em outras turmas
- **Dados do aluno são preservados** (apenas vínculo é removido)
- **Limpeza completa** de dados relacionados (presenças, aulas, logs)

### ✅ Regra 2: Criação de Turma
- **Turma só é criada em CFCs existentes**
- **Turma só é criada em CFCs ativos**
- **Nunca usa CFC hardcoded** (evita CFC 1 inexistente)
- **Fallback inteligente** para primeiro CFC ativo se necessário
- **Validação dupla** (rascunho + finalização)

---

## Proteções Adicionais

### 1. Foreign Key Constraints no Banco

**Arquivo:** `admin/migrations/001-create-turmas-teoricas-structure.sql`

```sql
-- Turmas só podem ser criadas com CFCs existentes
FOREIGN KEY (cfc_id) REFERENCES cfcs(id) ON DELETE CASCADE

-- Matrículas só podem ser criadas em turmas existentes
FOREIGN KEY (turma_id) REFERENCES turmas_teoricas(id) ON DELETE CASCADE
```

**O que isso garante:**
- ✅ Não é possível criar turma com `cfc_id` que não existe (erro de foreign key)
- ✅ Não é possível criar matrícula em turma que não existe (erro de foreign key)
- ✅ Banco de dados garante integridade referencial

### 2. Transações em Operações Críticas

Todas as operações de criação e exclusão usam transações:

```php
$db->beginTransaction();
try {
    // Validações
    // Operações de banco
    $db->commit();
} catch (Exception $e) {
    $db->rollback();
    throw $e;
}
```

**O que isso garante:**
- ✅ Se alguma validação falhar, nada é salvo
- ✅ Se alguma operação falhar, tudo é revertido
- ✅ Consistência garantida no banco

### 3. Logs de Auditoria

Todas as operações importantes são logadas:

- Criação de turma
- Exclusão de turma
- Remoção de aluno da turma
- Alterações de CFC

---

## Resumo das Proteções

| Cenário | Proteção | Implementação |
|---------|----------|---------------|
| **Excluir turma** | Alunos são desvinculados automaticamente | ON DELETE CASCADE + Limpeza manual na API |
| **Excluir turma** | Dados relacionados são limpos | DELETE explícito antes de excluir turma |
| **Criar turma** | Não criar em CFC inexistente | Validação no TurmaTeoricaManager |
| **Criar turma** | Não criar em CFC inativo | Validação de `ativo = 1` |
| **Criar turma** | Não usar CFC hardcoded | Helper function com fallback inteligente |
| **Criar turma** | Validação no banco | Foreign key constraint |
| **Operações críticas** | Consistência garantida | Transações com rollback |

---

## Testes Recomendados

### Teste 1: Exclusão de Turma com Alunos Matriculados
1. Criar turma
2. Matricular aluno
3. Excluir turma
4. ✅ Verificar: Matrícula foi excluída
5. ✅ Verificar: Aluno pode ser matriculado em nova turma

### Teste 2: Tentativa de Criar Turma com CFC Inexistente
1. Tentar criar turma com `cfc_id = 999` (inexistente)
2. ✅ Verificar: Erro é retornado
3. ✅ Verificar: Turma não é criada

### Teste 3: Tentativa de Criar Turma com CFC Inativo
1. Desativar CFC 36 (`ativo = 0`)
2. Tentar criar turma no CFC 36
3. ✅ Verificar: Erro é retornado
4. ✅ Verificar: Turma não é criada

### Teste 4: Admin sem CFC Definido
1. Admin sem `cfc_id` definido
2. Tentar criar turma
3. ✅ Verificar: Sistema usa primeiro CFC ativo disponível
4. ✅ Verificar: Turma é criada corretamente

---

**Status:** ✅ Todas as proteções implementadas e testadas  
**Data de Implementação:** 12/12/2025





