# Resumo: Investigação de Candidatos Vazios na Seleção de Alunos

## Problema

Modal "Matricular Alunos na Turma" mostra `Total candidatos: 0` e `Total aptos: 0`, mesmo com aluno 167 (Charles) tendo exames e financeiro OK.

## Alterações Implementadas

### Arquivo: `admin/api/alunos-aptos-turma-simples.php`

#### 1. Logs Detalhados Antes da Query (linha ~84-85)

```php
error_log("[TURMAS TEORICAS API] Executando query - turma_id={$turmaId}, cfc_id_turma={$cfcIdTurma}");
```

#### 2. Tratamento de Erro na Query (linha ~87-114)

```php
try {
    $alunosCandidatos = $db->fetchAll("
        SELECT 
            a.id,
            a.nome,
            a.cpf,
            a.categoria_cnh,
            a.status as status_aluno,  // ← Adicionado para debug
            c.nome as cfc_nome,
            c.id as cfc_id,
            CASE 
                WHEN tm.id IS NOT NULL THEN 'matriculado'
                ELSE 'disponivel'
            END as status_matricula
        FROM alunos a
        JOIN cfcs c ON a.cfc_id = c.id
        LEFT JOIN turma_matriculas tm ON a.id = tm.aluno_id 
            AND tm.turma_id = ? 
            AND tm.status IN ('matriculado', 'cursando')
        WHERE a.status = 'ativo'
            AND a.cfc_id = ?  // ← Usa $cfcIdTurma (não $cfcIdSessao)
        ORDER BY a.nome
    ", [$turmaId, $cfcIdTurma]);
} catch (Exception $e) {
    error_log("[TURMAS TEORICAS API] ERRO na query de candidatos: " . $e->getMessage());
    error_log("[TURMAS TEORICAS API] Stack trace: " . $e->getTraceAsString());
    throw $e;
}
```

#### 3. Logs Detalhados Após a Query (linha ~116-136)

```php
// Logs detalhados após a query
error_log("[TURMAS TEORICAS API] Turma {$turmaId} - CFC Turma: {$cfcIdTurma}, CFC Sessao: {$cfcIdSessao} ({$sessionCfcLabel}), AdminGlobal=" . ($isAdminGlobal ? 'true' : 'false'));
error_log("[TURMAS TEORICAS API] Turma {$turmaId} - Total candidatos brutos (antes de qualquer filtro): " . count($alunosCandidatos));

// Log de cada candidato bruto encontrado
foreach ($alunosCandidatos as $c) {
    error_log("[TURMAS TEORICAS API] CANDIDATO BRUTO - aluno_id={$c['id']}, nome={$c['nome']}, cfc_id={$c['cfc_id']}, status_aluno=" . ($c['status_aluno'] ?? 'N/A') . ", status_matricula=" . ($c['status_matricula'] ?? 'N/A'));
}

// Verificar especificamente se o aluno 167 está nos candidatos
$aluno167Encontrado = false;
foreach ($alunosCandidatos as $c) {
    if ((int)$c['id'] === 167) {
        $aluno167Encontrado = true;
        error_log("[TURMAS TEORICAS API] ✅ ALUNO 167 ENCONTRADO NOS CANDIDATOS BRUTOS - nome={$c['nome']}, cfc_id={$c['cfc_id']}, status_aluno={$c['status_aluno']}, status_matricula={$c['status_matricula']}");
        break;
    }
}
if (!$aluno167Encontrado) {
    error_log("[TURMAS TEORICAS API] ❌ ALUNO 167 NÃO ENCONTRADO NOS CANDIDATOS BRUTOS - Verificar se aluno está ativo e no CFC {$cfcIdTurma}");
}
```

#### 4. Logs Mantidos no Loop de Filtragem (linha ~157-195)

- Log específico para aluno 167 (já existia, mantido)
- Log padronizado por candidato (já existia, mantido)

## Verificações da Query

### ✅ Query Usa CFC da Turma (Correto)

```sql
WHERE a.status = 'ativo'
    AND a.cfc_id = ?  -- Parâmetro: $cfcIdTurma
```

**Confirmado:** Query usa `$cfcIdTurma` (não `$cfcIdSessao`), então admin global não afeta a busca de alunos.

### ✅ Filtros Aplicados

1. **Status do aluno:** `a.status = 'ativo'`
2. **CFC do aluno:** `a.cfc_id = $cfcIdTurma`
3. **Matrícula na turma:** LEFT JOIN com `turma_matriculas` (não filtra, apenas marca como 'matriculado' ou 'disponivel')

### ✅ Sem Filtros Escondidos

- Não há filtro por `$cfcIdSessao` na query
- Não há filtro por categoria na query inicial
- Não há filtro por exames na query inicial

## Logs Esperados

### Cenário 1: Aluno 167 está nos candidatos brutos

```
[TURMAS TEORICAS API] Executando query - turma_id=16, cfc_id_turma=1
[TURMAS TEORICAS API] Turma 16 - CFC Turma: 1, CFC Sessao: 0 (admin_global), AdminGlobal=true
[TURMAS TEORICAS API] Turma 16 - Total candidatos brutos (antes de qualquer filtro): 1
[TURMAS TEORICAS API] CANDIDATO BRUTO - aluno_id=167, nome=Charles Dietrich Wutzke, cfc_id=1, status_aluno=ativo, status_matricula=disponivel
[TURMAS TEORICAS API] ✅ ALUNO 167 ENCONTRADO NOS CANDIDATOS BRUTOS - nome=Charles Dietrich Wutzke, cfc_id=1, status_aluno=ativo, status_matricula=disponivel
[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== 
[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id=1, session_cfc_id=0 (admin_global)
[TURMAS TEORICAS API] Aluno 167 - exames_ok=true, financeiro_ok=true, categoria_ok=true, status_matricula=disponivel, elegivel=true
[TURMAS TEORICAS API] ================================= 
[TURMAS TEORICAS API] Candidato aluno 167 (Charles Dietrich Wutzke) - turma_cfc_id=1, session_cfc_id=0, financeiro_ok=true, exames_ok=true, categoria_ok=true, status_matricula=disponivel, elegivel=true
```

### Cenário 2: Aluno 167 NÃO está nos candidatos brutos

```
[TURMAS TEORICAS API] Executando query - turma_id=16, cfc_id_turma=1
[TURMAS TEORICAS API] Turma 16 - CFC Turma: 1, CFC Sessao: 0 (admin_global), AdminGlobal=true
[TURMAS TEORICAS API] Turma 16 - Total candidatos brutos (antes de qualquer filtro): 0
[TURMAS TEORICAS API] ❌ ALUNO 167 NÃO ENCONTRADO NOS CANDIDATOS BRUTOS - Verificar se aluno está ativo e no CFC 1
```

**Possíveis causas:**
- Aluno 167 não está com `status = 'ativo'`
- Aluno 167 não está no CFC 1
- Erro na query SQL

## Próximos Passos para Diagnóstico

1. **Abrir modal e verificar logs:**
   - Abrir "Matricular Alunos na Turma" na turma 16
   - Verificar logs do servidor (error_log)
   - Identificar qual cenário ocorreu

2. **Se Cenário 2 (aluno 167 não encontrado):**
   - Executar no banco: `SELECT id, nome, status, cfc_id FROM alunos WHERE id = 167`
   - Verificar se `status = 'ativo'` e `cfc_id = 1`
   - Executar query manualmente no phpMyAdmin:
     ```sql
     SELECT a.id, a.nome, a.status, a.cfc_id
     FROM alunos a
     WHERE a.status = 'ativo' AND a.cfc_id = 1
     ORDER BY a.nome
     ```

3. **Se Cenário 1 mas `elegivel=false`:**
   - Verificar logs de exames: `[GUARDS EXAMES] Aluno 167...`
   - Verificar logs de financeiro
   - Verificar `status_matricula` no log

## Garantias

✅ **Query correta:** Usa `$cfcIdTurma` no WHERE (não `$cfcIdSessao`)
✅ **Logs detalhados:** Cada etapa é logada
✅ **Tratamento de erro:** Erros SQL são capturados
✅ **Verificação específica:** Aluno 167 é verificado explicitamente
✅ **Sem filtros escondidos:** Query não filtra por CFC da sessão

## Arquivos Modificados

### `admin/api/alunos-aptos-turma-simples.php`

1. **Linha ~84-85:** Log antes da query
2. **Linha ~87-114:** Query com try-catch e `status_aluno` no SELECT
3. **Linha ~116-136:** Logs detalhados após a query
   - Total de candidatos brutos
   - Log de cada candidato bruto
   - Verificação específica do aluno 167

## Documentação

- `docs/INVESTIGACAO_CANDIDATOS_VAZIOS.md` - Análise técnica completa
- `docs/RESUMO_INVESTIGACAO_CANDIDATOS_VAZIOS.md` - Este resumo

