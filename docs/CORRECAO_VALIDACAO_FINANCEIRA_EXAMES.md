# Correção: Validação Financeira para Exames

## Problema Identificado

A validação financeira estava bloqueando incorretamente quando havia faturas ABERTAS com vencimento futuro, mesmo quando já havia pelo menos uma fatura PAGA.

### Comportamento Incorreto (Antes)

- Aluno com Entrada PAGA e parcelas ABERTAS (vencimento futuro) → **BLOQUEADO** ❌
- Mensagem: "Não é possível avançar: existem faturas pendentes de pagamento para este aluno."

### Comportamento Correto (Agora)

- Aluno com Entrada PAGA e parcelas ABERTAS (vencimento futuro) → **LIBERADO** ✅
- Aluno sem nenhuma fatura lançada → **BLOQUEADO** ✅
- Aluno com faturas em atraso → **BLOQUEADO** ✅

## Regra de Negócio Corrigida

### Para EXAMES (agendamento ou qualquer avanço de processo atrelado a exame):

**Bloquear se:**
1. Não houver nenhuma fatura lançada para o aluno
2. Existir qualquer fatura em atraso (vencida)

**Permitir se:**
1. Houver pelo menos uma fatura PAGA (ex.: Entrada)
2. As demais estiverem ABERTAS mas com vencimento futuro (sem atraso)

**Em outras palavras:**
- ABERTA e ainda não vencida **NÃO deve bloquear**
- Só bloqueia por financeiro se tiver atraso ou nenhum lançamento

## Alterações Realizadas

### Arquivo: `admin/includes/FinanceiroAlunoHelper.php`

**Função:** `verificarPermissaoFinanceiraAluno($alunoId)`

**Mudanças:**

1. **Verificação de Fatura Paga:**
   - Antes: Liberava apenas se status fosse 'em_dia' (todas as faturas pagas)
   - Agora: Verifica se existe pelo menos uma fatura PAGA
   - Verifica tanto pelo status 'paga' quanto pelo total pago >= valor total da fatura

2. **Lógica de Liberação:**
   ```php
   // ANTES (linha 150):
   $liberado = ($statusFinanceiro === 'em_dia');
   
   // AGORA (linha 150+):
   $liberado = $temFaturaPaga && ($qtdFaturasVencidas == 0);
   ```

3. **Mensagens de Motivo:**
   - Ajustadas para refletir a nova regra
   - Diferencia entre "sem fatura paga" e "faturas em atraso"

## Fluxo de Validação

### 1. Verificar Matrícula Ativa
- Se não houver → BLOQUEIA com motivo "aluno não possui matrícula ativa"

### 2. Buscar Faturas
- Se não houver nenhuma → BLOQUEIA com motivo "ainda não existem faturas lançadas"

### 3. Verificar Faturas em Atraso
- Se houver faturas vencidas → BLOQUEIA com motivo "existem faturas em atraso"

### 4. Verificar se há Fatura Paga
- Verifica status 'paga' ou se total pago >= valor total da fatura
- Se não houver nenhuma paga → BLOQUEIA com motivo "ainda não existem faturas pagas"

### 5. Liberar se:
- ✅ Tem pelo menos uma fatura paga
- ✅ Não há faturas em atraso

## Logs de Debug

Adicionado log detalhado:
```php
error_log('[VALIDACAO FINANCEIRA EXAMES] Aluno ' . $alunoId . 
         ' - Liberado: ' . ($liberado ? 'SIM' : 'NÃO') . 
         ' - Tem fatura paga: ' . ($temFaturaPaga ? 'SIM' : 'NÃO') . 
         ' - Faturas vencidas: ' . $qtdFaturasVencidas . 
         ' - Status: ' . $statusMapeado);
```

## Testes Esperados

### Cenário A – Deve Bloquear

**Situação:** Aluno com fatura em atraso

**Resultado Esperado:**
- Tela de exames: Bloqueia com mensagem "existem faturas em atraso"
- Histórico do aluno: Botão bloqueado, alerta ao clicar

### Cenário B – Deve Liberar

**Situação:** 
- Entrada PAGA
- Parcelas ABERTAS com vencimento futuro

**Resultado Esperado:**
- Tela de exames: Permite agendar normalmente
- Histórico do aluno: Botão liberado, redireciona normalmente

### Cenário C – Deve Bloquear

**Situação:** Aluno sem nenhuma fatura lançada

**Resultado Esperado:**
- Tela de exames: Bloqueia com mensagem "ainda não existem faturas lançadas"
- Histórico do aluno: Botão bloqueado, alerta ao clicar

## Arquivos Afetados

1. **`admin/includes/FinanceiroAlunoHelper.php`**
   - Função central de validação financeira para exames
   - Usada tanto na tela de exames quanto no histórico

2. **`admin/api/exames_simple.php`**
   - Usa `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`
   - Não precisa de alteração (já usa a função centralizada)

3. **`admin/pages/historico-aluno.php`**
   - Usa `FinanceiroAlunoHelper::verificarPermissaoFinanceiraAluno()`
   - Não precisa de alteração (já usa a função centralizada)

## Garantias

✅ **Função Centralizada:** Ambas as telas (exames e histórico) usam a mesma função
✅ **Validação em Tempo Real:** Sempre consulta o banco de dados atual
✅ **Não quebra funcionalidades existentes:** Mantém comportamento correto na tela de exames
✅ **Logs detalhados:** Facilita debug e validação

