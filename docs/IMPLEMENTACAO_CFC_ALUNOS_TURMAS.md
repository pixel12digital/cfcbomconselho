# Implementação: Alinhamento de CFC entre Alunos e Turmas

## Problema Identificado

O modal "Matricular Alunos na Turma" não mostrava o aluno 167 (Charles) mesmo com exames e financeiro OK.

**Causa raiz:** Divergência de CFC entre aluno e turma:
- Turma 16 → `turmas_teoricas.cfc_id = 1` (legado, deve ser 36)
- Aluno 167 → `alunos.cfc_id = 36` (correto)

**Nota:** Neste ambiente, o CFC Bom Conselho é representado pelo CFC ID 36. O ID 1 é legado e deve ser migrado para 36.

A query da API `admin/api/alunos-aptos-turma-simples.php` filtra por `a.status = 'ativo' AND a.cfc_id = cfcIdTurma`, então o aluno não entra.

## Regra de Negócio Definida

### CFC Canônico
- Cada turma teórica pertence a um CFC (`turmas_teoricas.cfc_id`)
- Cada aluno pertence a um CFC (`alunos.cfc_id`)
- Um aluno só pode ser candidato a uma turma teórica se:
  1. `alunos.cfc_id == turmas_teoricas.cfc_id`
  2. `alunos.status = 'ativo'`
  3. Não esteja já "matriculado/cursando" na mesma turma
  4. Exames médico e psicotécnico OK
  5. Financeiro OK

### Admin Global
- O usuário Admin Global (`cfc_id = 0`) pode enxergar qualquer turma
- Mas a seleção de alunos continua filtrando pelo CFC da turma, não pelo CFC da sessão

## Implementações Realizadas

### 1. Scripts de Diagnóstico

#### `admin/tools/diagnostico-cfc-turma-16.php`
- Confirma o CFC canônico da turma 16
- Mostra dados da turma e do CFC
- Lista todos os CFCs cadastrados

**Uso:** Acesse via navegador: `admin/tools/diagnostico-cfc-turma-16.php`

#### `admin/tools/diagnostico-cfc-alunos.php`
- Lista todos os alunos ordenados por CFC
- Identifica alunos com CFC diferente do canônico
- Gera SQL de migração sugerido (não executa automaticamente)

**Uso:** Acesse via navegador: `admin/tools/diagnostico-cfc-alunos.php?cfc_canonico=36`

**Parâmetro opcional:** `cfc_canonico` (padrão: 36 - CFC canônico do CFC Bom Conselho)

### 2. Blindagem Extra na API

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php`

Adicionada verificação adicional antes de considerar o aluno elegível:

```php
// BLINDAGEM EXTRA: Verificar se CFC do aluno corresponde ao CFC da turma
if ($alunoCfcId !== $cfcIdTurma) {
    error_log("[TURMAS TEORICAS API] WARNING: Aluno {$alunoId} com cfc_id={$alunoCfcId} diferente do cfc da turma {$cfcIdTurma} - IGNORANDO");
    continue; // Não considera este aluno
}
```

**Benefício:** Mesmo que a query já filtre por CFC, esta verificação garante que nenhum aluno de outra origem (ex: importação, migração) seja considerado incorretamente.

### 3. Ajuste no Cadastro/Edição de Alunos

**Arquivo:** `admin/api/alunos.php`

Implementada lógica para garantir `cfc_id` correto:

- **Usuário de CFC específico (não admin global):**
  - `cfc_id` deve ser sempre o `cfc_id` da sessão
  - Não permite escolher outro CFC
  - Se `cfc_id` diferente for enviado, é automaticamente ajustado

- **Admin Global (`cfc_id = 0`):**
  - `cfc_id` deve ser fornecido explicitamente
  - Campo obrigatório
  - Retorna erro se não fornecido

**Código implementado:**
```php
// Obter usuário atual e CFC da sessão
$user = getCurrentUser();
$userCfcId = isset($user['cfc_id']) ? (int)$user['cfc_id'] : 0;
$isAdminGlobal = ($userCfcId === 0 || $userCfcId === null);

// REGRA DE NEGÓCIO: Garantir cfc_id correto
if (!$isAdminGlobal) {
    // Usuário de CFC específico: usar sempre o CFC da sessão
    if (empty($data['cfc_id']) || (int)$data['cfc_id'] !== $userCfcId) {
        $data['cfc_id'] = $userCfcId;
    }
} else {
    // Admin Global: cfc_id deve ser fornecido explicitamente
    if (empty($data['cfc_id'])) {
        sendJsonResponse(['success' => false, 'error' => 'CFC é obrigatório para Admin Global'], 400);
    }
}
```

## Próximos Passos (Manual)

### 1. Confirmar CFC Canônico
Execute: `admin/tools/diagnostico-cfc-turma-16.php`
- O CFC canônico do CFC Bom Conselho é **36** (não mais 1)
- O script mostrará se há turmas com CFC divergente

### 2. Diagnosticar Alunos
Execute: `admin/tools/diagnostico-cfc-alunos.php?cfc_canonico=36`
- O padrão já é 36, mas pode ser especificado explicitamente
- Revise a lista de alunos com CFC diferente de 36

### 3. Revisar SQL de Migração
Revise o arquivo: `docs/MIGRACAO_CFC_1_PARA_36.md`
- Contém queries de diagnóstico e migração
- **REVISE ANTES DE EXECUTAR**

### 4. Executar Migração Manualmente
Após revisar o SQL em `docs/MIGRACAO_CFC_1_PARA_36.md`:
1. Faça backup do banco de dados
2. Execute as queries de diagnóstico primeiro
3. Execute os UPDATEs manualmente no phpMyAdmin
4. Verifique com as queries de verificação pós-migração
5. Confirme que não restam registros com `cfc_id = 1`

### 5. Testar
1. Abra o modal "Matricular Alunos na Turma" para a turma 16
2. O aluno 167 (Charles) deve aparecer como candidato apto (se exames e financeiro estiverem OK)

## Arquivos Modificados

1. `admin/api/alunos-aptos-turma-simples.php`
   - Adicionada blindagem extra para verificar CFC do aluno

2. `admin/api/alunos.php`
   - Adicionada lógica para garantir `cfc_id` correto no cadastro/edição

3. `admin/tools/diagnostico-cfc-turma-16.php` (NOVO)
   - Script para confirmar CFC canônico

4. `admin/tools/diagnostico-cfc-alunos.php` (NOVO)
   - Script para diagnosticar divergências de CFC

## Garantias Implementadas

✅ **Backend:** API garante que apenas alunos do CFC correto sejam considerados
✅ **Cadastro:** Novos alunos são criados sempre com o CFC correto
✅ **Edição:** Alunos não podem ter CFC alterado para um diferente do CFC da sessão (exceto Admin Global)
✅ **Diagnóstico:** Scripts permitem identificar e corrigir divergências existentes
✅ **Logs:** Logs detalhados ajudam a auditar problemas futuros

## Notas Importantes

- **Não altere outras regras de negócio** que já estão funcionando (exames, financeiro, etc.)
- **Foque apenas** em alinhar CFC de alunos e turmas
- **Evite** que novos alunos sejam criados com CFC incoerente
- **Execute migração manualmente** após revisar o SQL gerado

