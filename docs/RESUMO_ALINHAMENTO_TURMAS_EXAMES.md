# Resumo: Alinhamento de Lógica de Exames entre Histórico e Turmas Teóricas

## Problema Resolvido

O aluno Charles (id=167) não aparecia no modal "Matricular Alunos na Turma" das turmas teóricas, mesmo estando com exames e financeiro OK.

**Nota:** Neste ambiente, o CFC Bom Conselho é representado pelo CFC ID 36. O ID 1 é legado e deve ser migrado para 36.

## Causa Identificada

A API `alunos-aptos-turma-simples.php` usava lógica diferente da centralizada do histórico:
- Query SQL direta com `em.resultado = 'apto'` (não considerava 'aprovado')
- Não verificava `tem_resultado` corretamente
- CFC obtido dinamicamente da turma (não hardcoded)
- Sem verificação de financeiro

## Solução Implementada

### 1. Função Centralizada Criada

**Arquivo:** `admin/includes/guards_exames.php`

**Função:** `alunoComExamesOkParaTeoricas($alunoId)`

**Características:**
- Usa a mesma lógica do histórico (`renderizarBadgesExame()`)
- Verifica `tem_resultado` corretamente
- Compatível com valores antigos ('aprovado' = 'apto')
- Logs detalhados para debug

### 2. API Refatorada

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`

**Mudanças:**
- Busca inicial sem filtro de exames
- Filtragem usando `GuardsExames::alunoComExamesOkParaTeoricas()`
- Verificação financeira usando `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`
- CFC obtido dinamicamente da turma
- Logs detalhados para cada candidato
- Log específico para aluno 167

### 3. Frontend Ajustado

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php`

**Função:** `exibirAlunosAptos()`

**Mudança:**
- Exibição dos exames usa dados reais da API
- Normaliza valores antigos na exibição

## Arquivos Modificados

### `admin/includes/guards_exames.php`
- **Linha ~38-122:** Nova função `alunoComExamesOkParaTeoricas()`
- **Linha ~124-132:** `verificarExamesOK()` atualizada para usar função centralizada
- **Linha ~193:** `getStatusExames()` atualizada para usar função centralizada

### `admin/api/alunos-aptos-turma-simples.php`
- **Refatoração completa:** Busca, filtragem e logs

### `admin/pages/turmas-teoricas-detalhes-inline.php`
- **Linha ~13027-13036:** Exibição dos exames usando dados reais

## Critério de Elegibilidade

Aluno aparece no modal se:
1. ✅ Exames OK (usando função centralizada)
2. ✅ Financeiro OK (sem faturas vencidas)
3. ✅ Categoria OK (por enquanto sempre true)
4. ✅ Não está matriculado nesta turma

## Regra de CFC (Controle de Acesso)

**Implementação:** `admin/api/alunos-aptos-turma-simples.php` (linha ~55-70)

### Admin Global (cfc_id = 0)

**Comportamento:**
- Pode gerenciar turmas de qualquer CFC
- **NÃO há bloqueio** por CFC diferente
- Alunos retornados sempre são do CFC da turma (filtro na query SQL)

**Lógica:**
```php
$isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null);
$cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao);
```

**Debug Info:**
- `session_cfc_id: 0`
- `session_cfc_label: "admin_global"`
- `is_admin_global: true`
- `cfc_ids_match: true` (sempre true para admin global)

### Usuário de CFC Específico (cfc_id > 0)

**Comportamento:**
- Só pode gerenciar turmas do seu próprio CFC
- Se `session_cfc_id !== turma_cfc_id`, acesso é bloqueado (Exception)

**Lógica:**
```php
if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) {
    throw new Exception('Acesso negado: você não tem permissão para gerenciar turmas deste CFC');
}
```

**Debug Info:**
- `session_cfc_id: <id>`
- `session_cfc_label: "cfc_especifico"`
- `is_admin_global: false`
- `cfc_ids_match: true/false` (baseado na comparação)

### Seleção de Alunos

**Independente do CFC da sessão:**
- Alunos retornados **SEMPRE** são do CFC da turma
- Filtro na query SQL: `WHERE a.cfc_id = ?` com `$cfcIdTurma`
- Critérios de elegibilidade:
  1. ✅ Exames OK (usando função centralizada)
  2. ✅ Financeiro OK (sem faturas vencidas)
  3. ✅ Categoria OK (por enquanto sempre true)
  4. ✅ Não está matriculado nesta turma

## Questão da Categoria

**Status Atual:**
- `$categoriaOK = true` (aceita qualquer categoria)
- Tabela `turmas_teoricas` não tem campo `categoria_cnh` direto
- **TODO:** Implementar filtro se necessário (definir regra de negócio: aluno AB pode ser matriculado em turma B?)

## Logs de Debug

### Para cada candidato:
```
[TURMAS TEORICAS] Candidato aluno {id} - financeiro_ok={bool}, exames_ok={bool}, categoria_ok={bool}, elegivel={bool}
```

### Específico para aluno 167 (DETALHADO):
```
[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== 
[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id={id}, session_cfc_id={id} ({label})
[TURMAS TEORICAS API] Aluno 167 - exames_ok={bool}, financeiro_ok={bool}, categoria_ok={bool}, status_matricula={status}, elegivel={bool}
[TURMAS TEORICAS API] ================================= 
```

### Por candidato (formato padronizado):
```
[TURMAS TEORICAS API] Candidato aluno {id} ({nome}) - turma_cfc_id={id}, session_cfc_id={id}, financeiro_ok={bool}, exames_ok={bool}, categoria_ok={bool}, status_matricula={status}, elegivel={bool}
```

### Função centralizada:
```
[GUARDS EXAMES] Aluno {id} - medico_tem_resultado={bool}, medico_apto={bool}, psicotecnico_tem_resultado={bool}, psicotecnico_apto={bool}, exames_ok={bool}
```

## Testes Esperados

### ✅ Aluno 167 (Charles)
- Aparece no modal se exames e financeiro OK

### ✅ Aluno com pendência
- Não aparece no modal

### ✅ Aluno com atraso financeiro
- Não aparece no modal

### ✅ Aluno com valores antigos ('aprovado')
- Aparece no modal (normalizado para 'Apto')

## Garantias

✅ **Função Centralizada:** Lógica única
✅ **Consistência:** Mesma lógica do histórico
✅ **Compatibilidade:** Valores antigos funcionam
✅ **Verificação Financeira:** Centralizada
✅ **Logs Detalhados:** Facilita debug

