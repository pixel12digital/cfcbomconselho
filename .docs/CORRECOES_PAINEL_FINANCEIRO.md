# Correções: Painel Financeiro - Lista Útil e Coerência de Status

**Data:** 2024  
**Status:** ✅ Implementado

---

## Problema Identificado

A tela `/financeiro` mostrava "Nenhuma matrícula pendente encontrada" mesmo quando havia alunos com saldo devedor, porque:
1. Filtrava apenas `financial_status = 'pendente'`
2. Exigia `gateway_charge_id` preenchido
3. Não mostrava matrículas com `financial_status = 'em_dia'` mas `outstanding_amount > 0`

---

## Correções Implementadas

### A) Query de Listagem Atualizada

**ANTES:**
```sql
WHERE e.financial_status = 'pendente'
AND e.gateway_charge_id IS NOT NULL
AND e.gateway_charge_id != ''
```

**AGORA:**
```sql
WHERE e.status != 'cancelada'
AND (e.outstanding_amount > 0 OR (e.outstanding_amount IS NULL AND e.final_price > COALESCE(e.entry_amount, 0)))
```

**Mudanças:**
- ✅ Lista **todas** as matrículas com saldo devedor (`outstanding_amount > 0`)
- ✅ Não depende de `financial_status`
- ✅ Não exige `gateway_charge_id` (mostra mesmo sem cobrança gerada)
- ✅ Ordena vencidas primeiro, depois por vencimento mais próximo

---

### B) Coerência de Financial Status

**Regra:** `financial_status` deve ser coerente com `outstanding_amount`:

- `outstanding_amount > 0` → `financial_status = 'pendente'` (exceto se bloqueado)
- `outstanding_amount = 0` → `financial_status = 'em_dia'` (exceto se bloqueado)
- `financial_status = 'bloqueado'` → mantém bloqueado (não altera)

**Onde é aplicado:**

1. **Criação de Matrícula** (`AlunosController::criarMatricula()`)
   ```php
   $financialStatus = $outstandingAmount > 0 ? 'pendente' : 'em_dia';
   ```

2. **Atualização de Matrícula** (`AlunosController::atualizarMatricula()`)
   ```php
   if ($financialStatus !== 'bloqueado') {
       $financialStatus = $outstandingAmount > 0 ? 'pendente' : 'em_dia';
   }
   ```

3. **Sincronização de Cobrança** (`EfiPaymentService::syncCharge()`)
   - Se gateway mapeia status → usa mapeamento
   - Se não mapeia → recalcula baseado em `outstanding_amount`

4. **Atualização de Status Gateway** (`EfiPaymentService::updateEnrollmentStatus()`)
   - Sempre recalcula ao atualizar

**Método Helper:** `EfiPaymentService::recalculateFinancialStatus()`

---

### C) UI Atualizada

**Colunas da Tabela:**
- Aluno
- CPF
- Serviço (link para matrícula)
- **Saldo Devedor** (destacado em vermelho se > 0)
- Vencimento (destacado se vencido)
- Status Financeiro
- **Cobrança** (Gerada/Não gerada)
- Status Gateway (se tem cobrança)
- Último Evento (se tem cobrança)
- Ações

**Ações por Estado:**

1. **Sem cobrança gerada:**
   - Botão "Gerar Cobrança" (link para `/matriculas/{id}`)

2. **Com cobrança gerada:**
   - Link "Abrir Cobrança" (se `gateway_payment_url` existe)
   - Botão "Sincronizar"

**Botão de Sincronização em Lote:**
- ✅ **Sempre aparece** no header
- ✅ **Habilitado** se `pendingSyncableCount > 0`
- ✅ **Desabilitado** se não houver cobranças, com mensagem "Sem cobranças para sincronizar"

---

## Query SQL Final

### Listagem (com paginação)

```sql
SELECT e.*, 
       s.name as student_name, 
       s.full_name as student_full_name, 
       s.cpf as student_cpf,
       sv.name as service_name,
       CASE 
           WHEN e.outstanding_amount > 0 
           THEN e.outstanding_amount
           ELSE (e.final_price - COALESCE(e.entry_amount, 0))
       END as calculated_outstanding
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
INNER JOIN services sv ON sv.id = e.service_id
WHERE e.cfc_id = ?
AND e.status != 'cancelada'
AND (e.outstanding_amount > 0 OR (e.outstanding_amount IS NULL AND e.final_price > COALESCE(e.entry_amount, 0)))
-- Filtro opcional
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?)
ORDER BY 
    CASE 
        WHEN COALESCE(
            NULLIF(e.first_due_date, '0000-00-00'),
            NULLIF(e.down_payment_due_date, '0000-00-00'),
            '9999-12-31'
        ) < CURDATE() THEN 0
        ELSE 1
    END ASC,
    COALESCE(
        NULLIF(e.first_due_date, '0000-00-00'),
        NULLIF(e.down_payment_due_date, '0000-00-00'),
        DATE(e.created_at)
    ) ASC,
    e.id ASC
LIMIT 10 OFFSET 0
```

---

## Arquivos Modificados

1. **`app/Controllers/FinanceiroController.php`**
   - `getPendingEnrollments()` - Query atualizada para usar `outstanding_amount > 0`
   - Retorna `syncable_count` (quantas têm cobrança gerada)

2. **`app/Controllers/PaymentsController.php`**
   - `syncPendings()` - Query atualizada (mesma da listagem)

3. **`app/Controllers/AlunosController.php`**
   - `criarMatricula()` - Recalcula `financial_status` baseado em `outstanding_amount`
   - `atualizarMatricula()` - Recalcula `financial_status` baseado em `outstanding_amount`

4. **`app/Services/EfiPaymentService.php`**
   - `recalculateFinancialStatus()` - **NOVO** método helper
   - `syncCharge()` - Usa recálculo se gateway não mapeia
   - `updateEnrollmentStatus()` - Sempre recalcula

5. **`app/Views/financeiro/index.php`**
   - Lista sempre aparece (mesmo vazia)
   - Botão sincronização sempre aparece (habilitado/desabilitado)
   - Coluna "Cobrança" indica se gerada ou não
   - Botão "Gerar Cobrança" se não tem cobrança

---

## Validação SQL

Para verificar se há matrículas com saldo devedor:

```sql
SELECT id, student_id, outstanding_amount, financial_status, gateway_charge_id
FROM enrollments
WHERE outstanding_amount > 0
AND status != 'cancelada'
ORDER BY 
    COALESCE(
        NULLIF(first_due_date, '0000-00-00'),
        NULLIF(down_payment_due_date, '0000-00-00'),
        DATE(created_at)
    ) ASC;
```

---

## Teste de Validação

1. **Rodar SQL para aluno com saldo:**
   ```sql
   SELECT id, outstanding_amount, financial_status, gateway_charge_id, gateway_last_status, gateway_payment_url
   FROM enrollments 
   WHERE student_id = ?;
   ```

2. **Abrir `/financeiro`:**
   - [ ] Deve listar essa matrícula automaticamente
   - [ ] Deve aparecer na lista mesmo se `financial_status = 'em_dia'`

3. **Se `gateway_charge_id` existe:**
   - [ ] Botão "Sincronizar pendentes desta página" habilitado
   - [ ] Botão "Sincronizar" individual aparece
   - [ ] Link "Abrir Cobrança" aparece (se `gateway_payment_url` existe)

4. **Se `gateway_charge_id` não existe:**
   - [ ] Coluna "Cobrança" mostra "Não gerada"
   - [ ] Botão "Gerar Cobrança" aparece
   - [ ] Botão geral desabilitado com mensagem "Sem cobranças para sincronizar"

---

**Status:** ✅ **Correções implementadas e prontas para teste**
