# Validação de CFC para Criação de Turmas Teóricas

**Data:** 12/12/2025  
**Objetivo:** Garantir que turmas teóricas só sejam criadas com CFCs existentes e ativos

---

## Problema Identificado

Durante o diagnóstico do aluno 167, foi identificado que:

1. A turma 19 estava associada ao **CFC 1**, que **não existe** na tabela `cfcs`
2. O código estava usando **CFC 1 como fallback** quando admin não tinha `cfc_id` definido
3. Isso causava erro de **foreign key constraint** ao tentar atualizar dados

**Contexto do sistema:**
- O sistema possui **apenas o CFC 36** ativo
- CFC 1 não existe (nem deveria ser usado como fallback)
- Todas as turmas devem ser criadas no **CFC 36**

---

## Regra de Negócio Implementada

**CRITÉRIO OBRIGATÓRIO:**
- ✅ Turmas **SÓ podem ser criadas** em CFCs que **existem** na tabela `cfcs`
- ✅ Turmas **SÓ podem ser criadas** em CFCs que estão **ativos** (`ativo = 1`)

---

## Correções Implementadas

### 1. Validação no TurmaTeoricaManager

**Arquivo:** `admin/includes/TurmaTeoricaManager.php`

**Métodos atualizados:**
- `salvarRascunho()` - Valida CFC antes de salvar rascunho
- `finalizarTurma()` - Valida CFC antes de criar turma

**Validações adicionadas:**
```php
// Verificar se CFC existe e está ativo
$cfc = $this->db->fetch("SELECT id, nome, ativo FROM cfcs WHERE id = ?", [$dados['cfc_id']]);

if (!$cfc) {
    throw new Exception("Não é possível criar turma: CFC ID {$dados['cfc_id']} não existe na tabela cfcs. Use apenas CFCs existentes e ativos.");
}

if (!$cfc['ativo']) {
    throw new Exception("Não é possível criar turma: CFC '{$cfc['nome']}' (ID {$cfc['id']}) existe mas NÃO está ativo. Use apenas CFCs ativos.");
}
```

### 2. Helper Function em turmas-teoricas.php

**Arquivo:** `admin/pages/turmas-teoricas.php`

**Função criada:** `obterCfcIdValidoParaTurma()`

**Lógica:**
- **Admin com CFC válido:** Usa o CFC da sessão (se existe e está ativo)
- **Admin sem CFC válido:** Busca o primeiro CFC ativo disponível
- **Não-admin:** Usa CFC da sessão (já validado no login)

**Antes (❌ ERRADO):**
```php
'cfc_id' => $isAdmin ? ($user['cfc_id'] ?? 1) : $user['cfc_id'],
```

**Depois (✅ CORRETO):**
```php
'cfc_id' => $cfcIdValido, // Obtido via obterCfcIdValidoParaTurma()
```

### 3. Script de Criação de Turma no CFC 36

**Arquivo:** `admin/tools/verificar-cfcs-e-criar-turma-cfc36.php`

**Validação adicionada:**
- Verifica se CFC 36 existe
- Verifica se CFC 36 está ativo
- Só cria turma se passar nas validações

---

## Impacto

### Antes
- ❌ Sistema podia criar turmas com CFCs inexistentes (ex: CFC 1)
- ❌ Erro de foreign key constraint em operações subsequentes
- ❌ Dados inconsistentes no banco

### Depois
- ✅ Sistema valida CFC antes de criar turma
- ✅ Mensagens de erro claras se CFC não existir ou não estiver ativo
- ✅ Fallback inteligente: busca primeiro CFC ativo se admin não tiver CFC válido
- ✅ Consistência garantida no banco de dados

---

## Validações Aplicadas

### Ao Salvar Rascunho
1. Verifica se `cfc_id` foi fornecido
2. Verifica se CFC existe na tabela `cfcs`
3. Verifica se CFC está ativo
4. Lança exceção clara se alguma validação falhar

### Ao Finalizar Turma
1. Mesmas validações do rascunho
2. Aplicadas antes de fazer o INSERT na tabela `turmas_teoricas`
3. Transação garante rollback se validação falhar

### Ao Obter CFC para Turma
1. Admin: valida CFC da sessão ou busca primeiro ativo
2. Não-admin: usa CFC da sessão (já validado no login)

---

## Arquivos Modificados

1. **`admin/includes/TurmaTeoricaManager.php`**
   - Método `salvarRascunho()` - Validação de CFC
   - Método `finalizarTurma()` - Validação de CFC

2. **`admin/pages/turmas-teoricas.php`**
   - Função `obterCfcIdValidoParaTurma()` - Helper para obter CFC válido
   - Substituição de `($user['cfc_id'] ?? 1)` por `$cfcIdValido`

3. **`admin/tools/verificar-cfcs-e-criar-turma-cfc36.php`**
   - Validação antes de criar turma de teste

---

## Exemplo de Uso

**Antes (causava erro):**
```php
// Admin sem cfc_id → usava CFC 1 (inexistente)
'cfc_id' => $isAdmin ? ($user['cfc_id'] ?? 1) : $user['cfc_id']
```

**Depois (validado e seguro):**
```php
// Helper valida e retorna CFC válido
$cfcIdValido = obterCfcIdValidoParaTurma($db, $user, $isAdmin);
'cfc_id' => $cfcIdValido // Sempre será um CFC existente e ativo
```

---

## Testes Recomendados

1. **Admin sem CFC definido:**
   - Deve usar primeiro CFC ativo disponível
   - Não deve usar CFC 1 hardcoded

2. **Admin com CFC inativo:**
   - Deve usar primeiro CFC ativo disponível
   - Não deve usar o CFC inativo

3. **Tentativa de criar turma com CFC inexistente:**
   - Deve retornar erro claro
   - Não deve criar turma

4. **Tentativa de criar turma com CFC inativo:**
   - Deve retornar erro claro
   - Não deve criar turma

---

**Status:** ✅ Validações implementadas  
**Regra aplicada:** Todas as criações de turmas agora validam CFC existente e ativo

---

## Documentação Relacionada

- **Soluções Robustas Completas:** `docs/SOLUCOES_ROBUSTAS_TURMAS_TEORICAS.md`
  - Exclusão de turmas (ON DELETE CASCADE)
  - Criação de turmas (validações duplas)
  - Proteções adicionais
  - Testes recomendados

