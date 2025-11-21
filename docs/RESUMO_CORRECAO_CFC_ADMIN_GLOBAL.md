# Resumo: Correção de CFC Admin Global na Seleção de Alunos

## Problema Resolvido

Quando logado como **Administrador Sistema** (usuário global, `cfc_id = 0`), o modal "Matricular Alunos na Turma" não exibia alunos elegíveis, mesmo quando:
- Alunos tinham exames e financeiro OK
- Alunos eram do mesmo CFC da turma
- O debug mostrava `CFCs coincidem: Não` (incorreto para admin global)

## Solução Implementada

### Regra Final Aplicada

#### Admin Global (cfc_id = 0)

**Comportamento:**
- ✅ Pode acessar turmas de qualquer CFC
- ✅ **NÃO há bloqueio** por CFC diferente
- ✅ Alunos retornados sempre são do CFC da turma (filtro na query SQL)
- ✅ `cfc_ids_match = true` (sempre, para não confundir)

**Lógica:**
```php
$isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null);
$cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao);
```

#### Usuário de CFC Específico (cfc_id > 0)

**Comportamento:**
- ✅ Só pode acessar turmas do seu próprio CFC
- ✅ Se `session_cfc_id !== turma_cfc_id`, acesso é bloqueado (Exception)
- ✅ Alunos retornados sempre são do CFC da turma

**Lógica:**
```php
if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) {
    throw new Exception('Acesso negado: você não tem permissão para gerenciar turmas deste CFC');
}
```

## Arquivos Modificados

### `admin/api/alunos-aptos-turma-simples.php`

1. **Linha ~55-70:** Detecção de admin global e regra de CFC
   - `$isAdminGlobal = ($cfcIdSessao === 0 || $cfcIdSessao === null)`
   - `$sessionCfcLabel = $isAdminGlobal ? 'admin_global' : 'cfc_especifico'`
   - `$cfcIdsCoincidem = $isAdminGlobal ? true : ($cfcIdTurma === $cfcIdSessao)`

2. **Linha ~70-75:** Bloqueio apenas para usuários de CFC específico
   - `if (!$isAdminGlobal && $cfcIdSessao !== $cfcIdTurma) { throw Exception; }`

3. **Linha ~130-131:** Variável `$elegivel` calculada antes do if
   - `$elegivel = ($examesOK && $financeiroOK && $categoriaOK && $aluno['status_matricula'] === 'disponivel')`

4. **Linha ~133-142:** Logs detalhados para aluno 167
   - Bloco separado com `===== ALUNO 167 (CHARLES) =====`
   - Inclui `turma_cfc_id`, `session_cfc_id`, `session_cfc_label`
   - Inclui `elegivel` explicitamente

5. **Linha ~145-150:** Logs padronizados por candidato
   - Formato: `Candidato aluno {id} ({nome}) - turma_cfc_id={id}, session_cfc_id={id}, ...`

6. **Linha ~162-172:** Debug info com novos campos
   - `session_cfc_label`: 'admin_global' ou 'cfc_especifico'
   - `is_admin_global`: true/false

7. **Linha ~183-186:** Debug response com novos campos

### `admin/pages/turmas-teoricas-detalhes-inline.php`

1. **Linha ~13008-13020:** Exibição melhorada do debug info
   - Detecta admin global: `const isAdminGlobal = debugInfo.is_admin_global || (debugInfo.session_cfc_id === 0)`
   - Exibe label: `CFC da Sessão: 0 (admin_global)` ou `<id> (cfc_especifico)`
   - Exibe match: `N/A (Admin Global)` ou `Sim/Não`

## Logs Esperados para Aluno 167

### Admin Global (cfc_id = 0) Acessando Turma CFC 1

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

