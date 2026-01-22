# RESUMO - Painel Financeiro com Sincronização em Lote

## Comportamento Atualizado

### Critério de Listagem

**ANTES:** Listava apenas matrículas com `financial_status = 'pendente'` E `gateway_charge_id` preenchido.

**AGORA:** Lista **todas as matrículas com saldo devedor** (`outstanding_amount > 0`), independente de:
- `financial_status` (pode ser 'em_dia', 'pendente', 'bloqueado')
- `gateway_charge_id` (pode estar vazio ou preenchido)

### Coerência de Status

O sistema agora garante coerência automática:
- Se `outstanding_amount > 0` → `financial_status = 'pendente'` (exceto se bloqueado)
- Se `outstanding_amount = 0` → `financial_status = 'em_dia'` (exceto se bloqueado)
- Se `financial_status = 'bloqueado'` → mantém bloqueado (não altera automaticamente)

**Onde é aplicado:**
1. Ao criar matrícula (`AlunosController::criarMatricula()`)
2. Ao atualizar matrícula (`AlunosController::atualizarMatricula()`)
3. Ao sincronizar cobrança (`EfiPaymentService::syncCharge()`)
4. Ao atualizar status do gateway (`EfiPaymentService::updateEnrollmentStatus()`)

## Query SQL Usada na Listagem

### Query Principal (com paginação e filtro)

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
-- Critério: saldo devedor > 0
AND (e.outstanding_amount > 0 OR (e.outstanding_amount IS NULL AND e.final_price > COALESCE(e.entry_amount, 0)))
-- Filtro opcional por busca (nome/CPF)
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?) -- Se search não vazio
ORDER BY 
    -- Vencidas primeiro
    CASE 
        WHEN COALESCE(
            NULLIF(e.first_due_date, '0000-00-00'),
            NULLIF(e.down_payment_due_date, '0000-00-00'),
            '9999-12-31'
        ) < CURDATE() THEN 0
        ELSE 1
    END ASC,
    -- Depois por vencimento mais próximo
    COALESCE(
        NULLIF(e.first_due_date, '0000-00-00'),
        NULLIF(e.down_payment_due_date, '0000-00-00'),
        DATE(e.created_at)
    ) ASC,
    e.id ASC
LIMIT 10 OFFSET 0
```

**Parâmetros:**
- `cfc_id` (INT)
- `search_term` (STRING, opcional - 3x se search não vazio)
- `limit` (INT - 10)
- `offset` (INT - calculado: (page - 1) * per_page)

### Query de Contagem

```sql
SELECT COUNT(*) as total
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
WHERE e.cfc_id = ?
AND e.status != 'cancelada'
-- Critério: saldo devedor > 0
AND (e.outstanding_amount > 0 OR (e.outstanding_amount IS NULL AND e.final_price > COALESCE(e.entry_amount, 0)))
-- Filtro opcional por busca
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?) -- Se search não vazio
```

### Query de Contagem de Sincronizáveis

```sql
SELECT COUNT(*) as total
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
WHERE e.cfc_id = ?
AND e.status != 'cancelada'
AND (e.outstanding_amount > 0 OR (e.outstanding_amount IS NULL AND e.final_price > COALESCE(e.entry_amount, 0)))
AND e.gateway_charge_id IS NOT NULL
AND e.gateway_charge_id != ''
-- Filtro opcional por busca
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?) -- Se search não vazio
```

---

## Endpoint de Sincronização em Lote

### POST /api/payments/sync-pendings

**Input:**
```json
{
  "page": 1,
  "per_page": 10,
  "search": "" // opcional
}
```

**Comportamento:**
1. Usa a mesma query da listagem para buscar matrículas
2. Para cada matrícula:
   - Verifica `gateway_charge_id` existe
   - Chama `EfiPaymentService::syncCharge()`
   - Continua mesmo se uma falhar
3. Retorna resultado agregado

**Response:**
```json
{
  "ok": true,
  "total": 25,
  "synced": 8,
  "errors": [
    {"enrollment_id": 123, "reason": "Nenhuma cobrança gerada"}
  ],
  "items": [
    {
      "enrollment_id": 124,
      "charge_id": "abc123",
      "status": "paid",
      "billing_status": "generated",
      "financial_status": "em_dia"
    }
  ]
}
```

**Segurança:**
- ✅ Limite máximo: `per_page` = 20
- ✅ Timeout: 30s por request (via curl)
- ✅ Tratamento de exceções: continua mesmo se uma falhar
- ✅ Sem logs de secrets

---

## Arquivos Modificados

1. **`app/Controllers/FinanceiroController.php`**
   - `index()` - Adicionada lógica de listagem de pendentes
   - `getPendingEnrollments()` - **NOVO** método

2. **`app/Controllers/PaymentsController.php`**
   - `syncPendings()` - **NOVO** método

3. **`app/Views/financeiro/index.php`**
   - Seção de lista de pendentes
   - Botões de sincronização
   - Paginação

4. **`app/routes/web.php`**
   - Rota `POST /api/payments/sync-pendings`

---

## Checklist de Teste Manual

### 1. Abrir Tela `/financeiro`

- [ ] Verificar que lista de matrículas com saldo devedor aparece (padrão, sem busca)
- [ ] Verificar colunas: Aluno, CPF, Serviço, Saldo Devedor, Vencimento, Status Financeiro, Cobrança, Status Gateway, Último Evento, Ações
- [ ] Verificar que matrículas com `financial_status = 'em_dia'` mas `outstanding_amount > 0` aparecem na lista
- [ ] Verificar paginação (se total > 10)
- [ ] Verificar botão "Sincronizar Pendentes desta Página" sempre aparece (habilitado se houver cobranças, desabilitado se não houver)

### 2. Testar Busca

- [ ] Buscar por nome de aluno
- [ ] Verificar que filtra lista de pendentes (se não encontrar aluno)
- [ ] Buscar por CPF
- [ ] Verificar que mantém filtro na paginação

### 3. Testar Sincronização Individual

- [ ] Verificar que botão "Sincronizar" aparece apenas se `gateway_charge_id` existe
- [ ] Se não tem cobrança, verificar que aparece botão "Gerar Cobrança" (link para matrícula)
- [ ] Clicar "Sincronizar" em uma linha
- [ ] Verificar que botão fica desabilitado durante processamento
- [ ] Verificar que status atualiza após sincronização
- [ ] Verificar que `financial_status` atualiza se pago (e recalcula baseado em outstanding_amount)

### 4. Testar Sincronização em Lote

- [ ] Clicar "Sincronizar Pendentes desta Página"
- [ ] Verificar confirmação
- [ ] Verificar que processa todas as matrículas da página
- [ ] Verificar mensagem de resultado (total, sincronizadas, erros)
- [ ] Verificar que página recarrega e status atualiza

### 5. Testar Link de Pagamento

- [ ] Verificar que botão "Abrir Cobrança" aparece se `gateway_payment_url` existe
- [ ] Clicar "Abrir Cobrança"
- [ ] Verificar que abre em nova aba

### 6. Testar Coerência de Status

- [ ] Criar/editar matrícula com `outstanding_amount > 0`
- [ ] Verificar que `financial_status` é automaticamente 'pendente' (se não bloqueado)
- [ ] Editar matrícula para `outstanding_amount = 0`
- [ ] Verificar que `financial_status` é automaticamente 'em_dia'
- [ ] Sincronizar cobrança paga
- [ ] Verificar que `financial_status` atualiza para 'em_dia' (se outstanding_amount = 0 após pagamento)

### 7. Testar Paginação

- [ ] Navegar para próxima página
- [ ] Verificar que mantém filtro de busca (se houver)
- [ ] Verificar que lista atualiza corretamente

---

## Fluxo Completo: Gerar → Sincronizar

```
1. Criar matrícula com saldo devedor
   ↓
2. Gerar cobrança EFI (botão "Gerar Cobrança Efí")
   ↓
3. Cobrança aparece na lista de pendentes
   ↓
4. Sincronizar (individual ou em lote)
   ↓
5. Status atualiza:
   - gateway_last_status
   - billing_status
   - financial_status (se pago)
   ↓
6. Se pago: financial_status = 'em_dia'
   Se não: mantém 'pendente'
```

---

**Status:** ✅ **Implementação completa e pronta para teste**
