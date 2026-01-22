# Correção: Tratamento de Admin Global (CFC = 0) na Seleção de Alunos

## Problema Identificado

Quando logado como **Administrador Sistema** (usuário global, `cfc_id = 0`), o modal "Matricular Alunos na Turma" não exibia alunos elegíveis, mesmo quando:
- Alunos tinham exames e financeiro OK
- Alunos eram do mesmo CFC da turma
- O debug mostrava `CFCs coincidem: Não` (incorreto para admin global)

## Causa Raiz

A API não tratava o caso especial de **Admin Global** (`cfc_id = 0`):
- Calculava `cfc_ids_match = false` quando `session_cfc_id = 0` e `turma_cfc_id = 1`
- Não havia lógica para permitir admin global acessar turmas de qualquer CFC
- O frontend poderia interpretar `cfc_ids_match = false` como bloqueio

## Solução Implementada

### 1. API Ajustada (`admin/api/alunos-aptos-turma-simples.php`)

#### 1.1. Detecção de Admin Global (linha ~55-70)

```php
// Determinar se é admin global (cfc_id = 0 ou null)
$isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null);
$sessionCfcLabel = $isAdminGlobal ? 'admin_global' : 'cfc_especifico';

// REGRA DE CFC:
// - Admin Global (cfc_id = 0): pode gerenciar qualquer CFC, não bloqueia
// - Usuário de CFC específico (cfc_id > 0): só pode gerenciar seu próprio CFC
// - Alunos retornados SEMPRE devem ser do CFC da turma (independente do CFC da sessão)
$cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao);
```

#### 1.2. Bloqueio Apenas para Usuários de CFC Específico (linha ~70-75)

```php
// Bloquear acesso apenas se usuário de CFC específico tentar acessar turma de outro CFC
if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) {
    error_log("[TURMAS TEORICAS API] BLOQUEIO: Usuário CFC {$cfcIdSessao} tentando acessar turma CFC {$cfcIdTurma}");
    throw new Exception('Acesso negado: você não tem permissão para gerenciar turmas deste CFC');
}
```

#### 1.3. Debug Info Melhorado (linha ~162-172)

```php
$debugInfoCompleto = [
    'turma_cfc_id' => $cfcIdTurma,
    'session_cfc_id' => $cfcIdSessao,
    'session_cfc_label' => $sessionCfcLabel,  // 'admin_global' ou 'cfc_especifico'
    'is_admin_global' => $isAdminGlobal,      // true/false
    'cfc_ids_match' => $cfcIdsCoincidem,
    'turma_id' => $turmaId,
    'total_candidatos' => count($alunosCandidatos),
    'total_aptos' => count($alunosAptos),
    'alunos_detalhados' => $debugInfo
];
```

#### 1.4. Logs Detalhados para Aluno 167 (linha ~116-125)

```php
// Log específico para aluno 167 (Charles) - DETALHADO
if ($alunoId === 167) {
    error_log("[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== ");
    error_log("[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id={$cfcIdTurma}, session_cfc_id={$cfcIdSessao} ({$sessionCfcLabel})");
    error_log("[TURMAS TEORICAS API] Aluno 167 - exames_ok={bool}, financeiro_ok={bool}, categoria_ok={bool}, status_matricula={status}, elegivel={bool}");
    error_log("[TURMAS TEORICAS API] ================================= ");
}
```

#### 1.5. Logs Padronizados (linha ~145-150)

```php
error_log("[TURMAS TEORICAS API] Candidato aluno {$alunoId} ({$aluno['nome']}) - turma_cfc_id={$cfcIdTurma}, session_cfc_id={$cfcIdSessao}, financeiro_ok={bool}, exames_ok={bool}, categoria_ok={bool}, status_matricula={status}, elegivel={bool}");
```

### 2. Frontend Ajustado (`admin/pages/turmas-teoricas-detalhes-inline.php`)

#### 2.1. Exibição Melhorada do Debug Info (linha ~13008-13020)

```javascript
const sessionLabel = debugInfo.session_cfc_label || (debugInfo.session_cfc_id === 0 ? 'admin_global' : 'cfc_especifico');
const isAdminGlobal = debugInfo.is_admin_global || (debugInfo.session_cfc_id === 0);
const cfcMatchText = isAdminGlobal ? 'N/A (Admin Global)' : (debugInfo.cfc_ids_match ? 'Sim' : 'Não');
```

**Resultado no painel amarelo:**
- CFC da Turma: `<id>`
- CFC da Sessão: `0 (admin_global)` ou `<id> (cfc_especifico)`
- CFCs coincidem: `N/A (Admin Global)` ou `Sim/Não`

## Regras Finais Aplicadas

### Admin Global (cfc_id = 0)

**Comportamento:**
- ✅ Pode acessar turmas de qualquer CFC
- ✅ Não há bloqueio por CFC diferente
- ✅ Alunos retornados sempre são do CFC da turma (filtro na query SQL)
- ✅ `cfc_ids_match = true` (sempre, para não confundir)

**Lógica:**
```php
if ($cfcIdSessao === 0 || $cfcIdSessao === null) {
    // Admin Global - não bloqueia
    $cfcIdsCoincidem = true;
}
```

### Usuário de CFC Específico (cfc_id > 0)

**Comportamento:**
- ✅ Só pode acessar turmas do seu próprio CFC
- ✅ Se `session_cfc_id !== turma_cfc_id`, acesso é bloqueado (Exception)
- ✅ Alunos retornados sempre são do CFC da turma

**Lógica:**
```php
if ($cfcIdSessao > 0 && $cfcIdSessao !== $cfcIdTurma) {
    throw new Exception('Acesso negado');
}
```

### Seleção de Alunos (Independente do CFC da Sessão)

**Sempre aplicado:**
- Alunos retornados são do CFC da turma: `WHERE a.cfc_id = ?` com `$cfcIdTurma`
- Critérios de elegibilidade:
  1. ✅ Exames OK (`GuardsExames::alunoComExamesOkParaTeoricas()`)
  2. ✅ Financeiro OK (`FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`)
  3. ✅ Categoria OK (por enquanto sempre true)
  4. ✅ Não está matriculado nesta turma

## Arquivos Modificados

### `admin/api/alunos-aptos-turma-simples.php`

1. **Linha ~55-70:** Detecção de admin global e regra de CFC
2. **Linha ~70-75:** Bloqueio apenas para usuários de CFC específico
3. **Linha ~116-125:** Logs detalhados para aluno 167
4. **Linha ~127:** Variável `$elegivel` calculada antes do if
5. **Linha ~145-150:** Logs padronizados por candidato
6. **Linha ~162-172:** Debug info com `session_cfc_label` e `is_admin_global`
7. **Linha ~183-186:** Debug response com novos campos

### `admin/pages/turmas-teoricas-detalhes-inline.php`

1. **Linha ~13008-13020:** Exibição melhorada do debug info com tratamento de admin global

## Logs Esperados

### Admin Global Acessando Turma CFC 1

```
[TURMAS TEORICAS API] Requisição recebida - turma_id: 16, input: {...}
[TURMAS TEORICAS API] CFC da Turma: 1, CFC da Sessão: 0 (admin_global), Admin Global: Sim
[TURMAS TEORICAS API] Turma 16 - CFC Turma: 1, CFC Sessão: 0, Total candidatos: {n}
[TURMAS TEORICAS API] ===== ALUNO 167 (CHARLES) ===== 
[TURMAS TEORICAS API] Aluno 167 - turma_cfc_id=1, session_cfc_id=0 (admin_global)
[TURMAS TEORICAS API] Aluno 167 - exames_ok=true, financeiro_ok=true, categoria_ok=true, status_matricula=disponivel, elegivel=true
[TURMAS TEORICAS API] ================================= 
[TURMAS TEORICAS API] Candidato aluno 167 (Charles Dietrich Wutzke) - turma_cfc_id=1, session_cfc_id=0, financeiro_ok=true, exames_ok=true, categoria_ok=true, status_matricula=disponivel, elegivel=true
[TURMAS TEORICAS API] Resposta - Total aptos: {n}, CFC Turma: 1, CFC Sessão: 0, Coincidem: Sim
```

### Usuário CFC 1 Acessando Turma CFC 1

```
[TURMAS TEORICAS API] CFC da Turma: 1, CFC da Sessão: 1 (cfc_especifico), Admin Global: Não
[TURMAS TEORICAS API] Turma 16 - CFC Turma: 1, CFC Sessão: 1, Total candidatos: {n}
[TURMAS TEORICAS API] Resposta - Total aptos: {n}, CFC Turma: 1, CFC Sessão: 1, Coincidem: Sim
```

### Usuário CFC 2 Tentando Acessar Turma CFC 1

```
[TURMAS TEORICAS API] CFC da Turma: 1, CFC da Sessão: 2 (cfc_especifico), Admin Global: Não
[TURMAS TEORICAS API] BLOQUEIO: Usuário CFC 2 tentando acessar turma CFC 1
Exception: Acesso negado: você não tem permissão para gerenciar turmas deste CFC
```

## Testes Esperados

### ✅ Cenário A: Admin Global (cfc_id = 0)

**Estado:**
- Turma do CFC 36 (CFC canônico do CFC Bom Conselho)
- Aluno 167 (CFC 36) com exames e financeiro OK
- Usuário logado: Administrador Sistema (cfc_id = 0)

**Nota:** Neste ambiente, o CFC Bom Conselho é representado pelo CFC ID 36. O ID 1 é legado e deve ser migrado para 36.

**Resultado Esperado:**
- ✅ Charles aparece no modal como elegível
- ✅ Debug Info mostra:
  - CFC da Turma: 1
  - CFC da Sessão: 0 (admin_global)
  - CFCs coincidem: N/A (Admin Global)
- ✅ Logs mostram `elegivel=true` para aluno 167

### ✅ Cenário B: Usuário CFC 1 Acessando Turma CFC 1

**Estado:**
- Turma do CFC 36 (CFC canônico)
- Aluno 167 (CFC 36) com exames e financeiro OK
- Usuário logado: CFC 36 (cfc_id = 36)

**Resultado Esperado:**
- ✅ Mesmo comportamento do Cenário A
- ✅ Sem quebra de segurança
- ✅ Debug Info mostra:
  - CFC da Turma: 1
  - CFC da Sessão: 1 (cfc_especifico)
  - CFCs coincidem: Sim

### ✅ Cenário C: Usuário CFC 2 Tentando Acessar Turma CFC 1

**Estado:**
- Turma do CFC 36
- Usuário logado: CFC 2 (cfc_id = 2)

**Resultado Esperado:**
- ❌ Acesso bloqueado (Exception)
- ❌ Nenhum aluno retornado
- ✅ Log mostra bloqueio

## Garantias

✅ **Admin Global:** Pode acessar turmas de qualquer CFC
✅ **Segurança:** Usuários de CFC específico só acessam seu próprio CFC
✅ **Alunos:** Sempre filtrados pelo CFC da turma (não do CFC da sessão)
✅ **Logs Detalhados:** Facilita debug e validação
✅ **Debug Info:** Indica claramente se é admin global ou CFC específico

