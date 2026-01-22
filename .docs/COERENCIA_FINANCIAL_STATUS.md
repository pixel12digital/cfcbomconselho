# Coerência de Financial Status Baseado em Outstanding Amount

**Data:** 2024  
**Status:** ✅ Implementado

---

## Regra de Coerência

O sistema garante que `financial_status` seja coerente com `outstanding_amount`:

### Regras

1. **Se `outstanding_amount > 0`:**
   - `financial_status` deve ser `'pendente'` (exceto se já está `'bloqueado'`)

2. **Se `outstanding_amount = 0`:**
   - `financial_status` deve ser `'em_dia'` (exceto se já está `'bloqueado'`)

3. **Se `financial_status = 'bloqueado'`:**
   - Mantém bloqueado (não altera automaticamente)
   - Requer ação manual para desbloquear

---

## Onde é Aplicado

### 1. Criação de Matrícula

**Arquivo:** `app/Controllers/AlunosController.php`  
**Método:** `criarMatricula()` (linha ~435)

```php
// Calcular saldo devedor
$outstandingAmount = $entryAmount > 0 ? max(0, $finalPrice - $entryAmount) : $finalPrice;

// Recalcular financial_status baseado em outstanding_amount (coerência)
$financialStatus = $outstandingAmount > 0 ? 'pendente' : 'em_dia';
```

**Comportamento:**
- Ao criar matrícula, `financial_status` é calculado automaticamente
- Não depende do valor manual informado

---

### 2. Atualização de Matrícula

**Arquivo:** `app/Controllers/AlunosController.php`  
**Método:** `atualizarMatricula()` (linha ~717)

```php
// Calcular saldo devedor
$outstandingAmount = $entryAmount > 0 ? max(0, $finalPrice - $entryAmount) : $finalPrice;

// Recalcular financial_status baseado em outstanding_amount (coerência)
if ($financialStatus !== 'bloqueado') {
    $financialStatus = $outstandingAmount > 0 ? 'pendente' : 'em_dia';
}
```

**Comportamento:**
- Ao atualizar matrícula, recalcula `financial_status`
- Respeita se já está bloqueado (não altera)

---

### 3. Sincronização de Cobrança

**Arquivo:** `app/Services/EfiPaymentService.php`  
**Método:** `syncCharge()` (linha ~536)

```php
// Atualizar financial_status se mapeado
if ($financialStatus !== null) {
    $updateData['financial_status'] = $financialStatus;
} else {
    // Se não foi mapeado, recalcular baseado em outstanding_amount
    $updateData['financial_status'] = $this->recalculateFinancialStatus($enrollment);
}
```

**Comportamento:**
- Se gateway mapeia status (ex: `paid` → `'em_dia'`), usa mapeamento
- Se não mapeia, recalcula baseado em `outstanding_amount`

---

### 4. Atualização de Status do Gateway

**Arquivo:** `app/Services/EfiPaymentService.php`  
**Método:** `updateEnrollmentStatus()` (linha ~615)

```php
// Recalcular financial_status baseado em outstanding_amount
// (exceto se já está bloqueado ou se foi mapeado pelo gateway)
$updateData['financial_status'] = $this->recalculateFinancialStatus($currentEnrollment);
```

**Comportamento:**
- Sempre recalcula ao atualizar status do gateway
- Garante coerência mesmo após atualizações

---

## Método Helper

**Arquivo:** `app/Services/EfiPaymentService.php`  
**Método:** `recalculateFinancialStatus()` (linha ~582)

```php
private function recalculateFinancialStatus($enrollment)
{
    // Se já está bloqueado, manter bloqueado
    if (($enrollment['financial_status'] ?? '') === 'bloqueado') {
        return 'bloqueado';
    }
    
    // Calcular saldo devedor
    $outstandingAmount = floatval($enrollment['outstanding_amount'] ?? 0);
    if ($outstandingAmount <= 0) {
        // Se não tem coluna outstanding_amount, calcular
        if (empty($enrollment['outstanding_amount'])) {
            $finalPrice = floatval($enrollment['final_price'] ?? 0);
            $entryAmount = floatval($enrollment['entry_amount'] ?? 0);
            $outstandingAmount = max(0, $finalPrice - $entryAmount);
        }
    }
    
    // Se tem saldo devedor, deve ser 'pendente'
    // Se não tem saldo, deve ser 'em_dia'
    return $outstandingAmount > 0 ? 'pendente' : 'em_dia';
}
```

**Lógica:**
1. Se bloqueado → mantém bloqueado
2. Se `outstanding_amount > 0` → retorna `'pendente'`
3. Se `outstanding_amount = 0` → retorna `'em_dia'`
4. Se coluna não existe → calcula: `final_price - entry_amount`

---

## Exemplos de Uso

### Exemplo 1: Criar Matrícula com Saldo

```php
// Dados de entrada
$finalPrice = 1000.00;
$entryAmount = 200.00;
$outstandingAmount = 800.00;

// Resultado
$financialStatus = 'pendente'; // Calculado automaticamente
```

### Exemplo 2: Atualizar Matrícula (Pagar Entrada)

```php
// Antes
$outstandingAmount = 800.00;
$financialStatus = 'pendente';

// Depois (paga entrada completa)
$entryAmount = 1000.00;
$outstandingAmount = 0.00;
$financialStatus = 'em_dia'; // Recalculado automaticamente
```

### Exemplo 3: Sincronizar Cobrança Paga

```php
// Antes
$outstandingAmount = 800.00;
$financialStatus = 'pendente';
$gatewayStatus = 'waiting';

// Depois (webhook informa pago)
$gatewayStatus = 'paid';
$financialStatus = 'em_dia'; // Mapeado pelo gateway (paid → em_dia)
// Se outstanding_amount ainda > 0, será recalculado para 'pendente'
```

### Exemplo 4: Matrícula Bloqueada

```php
// Dados
$outstandingAmount = 800.00;
$financialStatus = 'bloqueado'; // Manual

// Ao recalcular
$financialStatus = 'bloqueado'; // Mantém bloqueado (não altera)
```

---

## Validação SQL

Para validar coerência no banco:

```sql
-- Matrículas com incoerência (outstanding_amount > 0 mas financial_status = 'em_dia')
SELECT id, student_id, outstanding_amount, financial_status, final_price, entry_amount
FROM enrollments
WHERE outstanding_amount > 0
AND financial_status = 'em_dia'
AND financial_status != 'bloqueado'
AND status != 'cancelada';

-- Matrículas com incoerência (outstanding_amount = 0 mas financial_status = 'pendente')
SELECT id, student_id, outstanding_amount, financial_status, final_price, entry_amount
FROM enrollments
WHERE (outstanding_amount = 0 OR outstanding_amount IS NULL)
AND financial_status = 'pendente'
AND financial_status != 'bloqueado'
AND status != 'cancelada';
```

---

## Correção Manual (se necessário)

Se houver incoerências no banco, execute:

```sql
-- Corrigir: outstanding_amount > 0 mas financial_status = 'em_dia'
UPDATE enrollments
SET financial_status = 'pendente'
WHERE outstanding_amount > 0
AND financial_status = 'em_dia'
AND financial_status != 'bloqueado'
AND status != 'cancelada';

-- Corrigir: outstanding_amount = 0 mas financial_status = 'pendente'
UPDATE enrollments
SET financial_status = 'em_dia'
WHERE (outstanding_amount = 0 OR outstanding_amount IS NULL)
AND financial_status = 'pendente'
AND financial_status != 'bloqueado'
AND status != 'cancelada';
```

---

**Status:** ✅ **Coerência implementada e aplicada automaticamente**
