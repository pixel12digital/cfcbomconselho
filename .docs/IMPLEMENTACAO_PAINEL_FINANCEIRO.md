# Implementação: Painel Financeiro com Sincronização em Lote

**Data:** 2024  
**Status:** ✅ Implementado

---

## Resumo Executivo

Evolução da tela "Consulta Financeira" para um painel com listagem de matrículas pendentes, sincronização em lote e filtros de busca.

---

## 1. Query SQL da Listagem

### Query Principal (com paginação)

```sql
SELECT e.*, 
       s.name as student_name, 
       s.full_name as student_full_name, 
       s.cpf as student_cpf,
       sv.name as service_name
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
INNER JOIN services sv ON sv.id = e.service_id
WHERE e.cfc_id = ?
AND e.financial_status = 'pendente'
AND e.gateway_charge_id IS NOT NULL
AND e.gateway_charge_id != ''
AND e.status != 'cancelada'
-- Filtro opcional por busca (nome/CPF)
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?) -- Se search não vazio
ORDER BY 
    COALESCE(
        NULLIF(e.first_due_date, '0000-00-00'),
        NULLIF(e.down_payment_due_date, '0000-00-00'),
        DATE(e.created_at)
    ) ASC,
    e.id ASC
LIMIT ? OFFSET ?
```

### Query de Contagem (total)

```sql
SELECT COUNT(*) as total
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
WHERE e.cfc_id = ?
AND e.financial_status = 'pendente'
AND e.gateway_charge_id IS NOT NULL
AND e.gateway_charge_id != ''
AND e.status != 'cancelada'
-- Filtro opcional por busca (nome/CPF)
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?) -- Se search não vazio
```

### Critérios de Filtro

- **financial_status = 'pendente'** - Apenas matrículas pendentes
- **gateway_charge_id IS NOT NULL AND != ''** - Apenas com cobrança gerada
- **status != 'cancelada'** - Excluir canceladas
- **Filtro opcional:** Busca por nome/CPF do aluno

### Ordenação

1. **Vencimento mais próximo primeiro:**
   - `first_due_date` (se válido)
   - `down_payment_due_date` (se válido)
   - `created_at` (fallback)
2. **ID da matrícula** (desempate)

---

## 2. Endpoint de Sincronização em Lote

### Endpoint

**POST** `/api/payments/sync-pendings`

### Parâmetros

```json
{
  "page": 1,
  "per_page": 10,
  "search": "" // opcional
}
```

### Validações

- ✅ Autenticação obrigatória
- ✅ Permissão: ADMIN ou SECRETARIA
- ✅ `per_page` limitado a máximo 20 (para não sobrecarregar API)
- ✅ Mesma query da listagem (garante sincronizar apenas o que está visível)

### Comportamento

1. Busca matrículas pendentes usando a mesma query da listagem
2. Para cada matrícula:
   - Verifica se tem `gateway_charge_id`
   - Chama `EfiPaymentService::syncCharge()`
   - Continua mesmo se uma falhar
3. Retorna resultado agregado

### Resposta

```json
{
  "ok": true,
  "total": 25,
  "synced": 8,
  "errors": [
    {
      "enrollment_id": 123,
      "reason": "Nenhuma cobrança gerada"
    }
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

### Segurança

- ✅ Sem logs de token/secret
- ✅ Timeout configurado (30s por request via curl)
- ✅ Tratamento de exceções (não quebra se uma falhar)
- ✅ Limite de `per_page` (máximo 20)

---

## 3. Arquivos Modificados

### Controllers

1. **`app/Controllers/FinanceiroController.php`**
   - Método `index()` - Adicionada lógica de listagem de pendentes
   - Método `getPendingEnrollments()` - **NOVO** - Busca pendentes com paginação e filtro

2. **`app/Controllers/PaymentsController.php`**
   - Método `syncPendings()` - **NOVO** - Sincronização em lote

### Views

1. **`app/Views/financeiro/index.php`**
   - Seção de lista de pendentes (substitui cards quando há pendentes)
   - Botão "Sincronizar Pendentes desta Página"
   - Botão "Sincronizar" individual em cada linha
   - Link "Abrir Cobrança" (se `gateway_payment_url` existir)
   - Paginação
   - Funções JavaScript: `sincronizarPendentes()`, `sincronizarIndividual()`

### Rotas

1. **`app/routes/web.php`**
   - Adicionada: `POST /api/payments/sync-pendings`

---

## 4. Estrutura da UI

### Tela Padrão (sem busca)

```
[Busca de Aluno]
[Matrículas Pendentes]
  - Header: Título + Total + Botão "Sincronizar Pendentes desta Página"
  - Tabela:
    - Aluno | CPF | Serviço | Valor | Vencimento | Status Financeiro | Status Gateway | Último Evento | Ações
    - Ações: [Abrir Cobrança] [Sincronizar]
  - Paginação: [Anterior] [Próxima]
```

### Tela com Busca (aluno encontrado)

```
[Busca de Aluno]
[Detalhes do Aluno]
  - Cards: Total Pago | Saldo Devedor | Status Geral
  - Tabela de Matrículas do Aluno
```

### Tela com Busca (sem resultado, mas filtra pendentes)

```
[Busca de Aluno]
[Matrículas Pendentes] (filtradas pela busca)
```

---

## 5. Fluxo de Sincronização em Lote

```
[UI] Botão "Sincronizar Pendentes desta Página"
  ↓
[API] POST /api/payments/sync-pendings
  ├─ PaymentsController::syncPendings()
  │  ├─ Valida: autenticação, permissão
  │  ├─ Busca matrículas pendentes (mesma query da listagem)
  │  └─ Para cada matrícula:
  │     ├─ Verifica gateway_charge_id existe
  │     ├─ Chama EfiPaymentService::syncCharge()
  │     ├─ Se sucesso: adiciona em items[]
  │     └─ Se erro: adiciona em errors[]
  ↓
[Response] JSON: {ok, total, synced, errors, items}
  ↓
[UI] Exibe resultado e recarrega página
```

**Pontos de Falha:**

| Ponto | Tratamento |
|-------|------------|
| Matrícula sem gateway_charge_id | Adiciona em errors[], continua |
| Erro na API EFI | Adiciona em errors[], continua |
| Timeout | Captura exceção, adiciona em errors[], continua |
| Exceção inesperada | Try/catch, adiciona em errors[], continua |

---

## 6. Teste Manual

### Checklist de Teste

1. **Abrir tela `/financeiro`**
   - [ ] Verificar que lista de pendentes aparece (se houver)
   - [ ] Verificar paginação (se total > 10)
   - [ ] Verificar colunas: Aluno, CPF, Serviço, Valor, Vencimento, Status, Ações

2. **Testar busca**
   - [ ] Buscar por nome de aluno
   - [ ] Buscar por CPF
   - [ ] Verificar que filtra lista de pendentes

3. **Testar sincronização individual**
   - [ ] Clicar "Sincronizar" em uma linha
   - [ ] Verificar que status atualiza após sincronização
   - [ ] Verificar que `financial_status` atualiza se pago

4. **Testar sincronização em lote**
   - [ ] Clicar "Sincronizar Pendentes desta Página"
   - [ ] Verificar que processa todas as matrículas da página
   - [ ] Verificar que mostra resultado (total, sincronizadas, erros)
   - [ ] Verificar que página recarrega e status atualiza

5. **Testar link de pagamento**
   - [ ] Clicar "Abrir Cobrança" (se existir)
   - [ ] Verificar que abre em nova aba

---

## 7. Query SQL Completa (para referência)

### Listagem com Filtro e Paginação

```sql
-- Buscar pendentes
SELECT e.*, 
       s.name as student_name, 
       s.full_name as student_full_name, 
       s.cpf as student_cpf,
       sv.name as service_name
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
INNER JOIN services sv ON sv.id = e.service_id
WHERE e.cfc_id = ?
AND e.financial_status = 'pendente'
AND e.gateway_charge_id IS NOT NULL
AND e.gateway_charge_id != ''
AND e.status != 'cancelada'
-- Filtro opcional
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?)
ORDER BY 
    COALESCE(
        NULLIF(e.first_due_date, '0000-00-00'),
        NULLIF(e.down_payment_due_date, '0000-00-00'),
        DATE(e.created_at)
    ) ASC,
    e.id ASC
LIMIT 10 OFFSET 0
```

### Contagem Total

```sql
SELECT COUNT(*) as total
FROM enrollments e
INNER JOIN students s ON s.id = e.student_id
WHERE e.cfc_id = ?
AND e.financial_status = 'pendente'
AND e.gateway_charge_id IS NOT NULL
AND e.gateway_charge_id != ''
AND e.status != 'cancelada'
-- Filtro opcional
AND (s.name LIKE ? OR s.full_name LIKE ? OR s.cpf LIKE ?)
```

---

## 8. Melhorias Futuras

1. **Cache de resultados** - Evitar múltiplas queries
2. **Sincronização assíncrona** - Processar em background
3. **Filtros avançados** - Por status gateway, data de vencimento, etc.
4. **Exportação** - CSV/PDF da lista de pendentes
5. **Notificações** - Alertar quando status muda após sincronização

---

**Status:** ✅ **Implementação completa e pronta para uso**
