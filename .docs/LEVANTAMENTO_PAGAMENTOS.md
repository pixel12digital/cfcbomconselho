# Levantamento Técnico: Sistema de Pagamentos/Cobrança

**Data:** 2024  
**Objetivo:** Auditoria completa do que já existe relacionado a pagamentos antes de implementar gateway via API

---

## 1. Estrutura Atual no Sistema

### 1.1 Módulos/Controllers Relacionados

#### ✅ **FinanceiroController** (`app/Controllers/FinanceiroController.php`)
- **Finalidade:** Consulta e visualização da situação financeira
- **Funcionalidades:**
  - Listagem de matrículas por aluno
  - Cálculo de total pago (`entry_amount`) e saldo devedor (`outstanding_amount`)
  - Busca de alunos em atraso (`financial_status` = 'bloqueado' ou 'pendente')
  - Busca de alunos com vencimentos próximos (7 dias)
  - Histórico de consultas recentes
- **Status:** ✅ Ativo em produção
- **Rotas:**
  - `GET /financeiro` - Página principal
  - `GET /api/financeiro/autocomplete` - Autocomplete para busca

#### ✅ **AlunosController** (`app/Controllers/AlunosController.php`)
- **Finalidade:** Gestão de matrículas (inclui campos financeiros)
- **Funcionalidades relacionadas:**
  - Criação de matrícula com campos de pagamento
  - Atualização de matrícula (incluindo plano de pagamento)
  - Validação de entrada e parcelamento
  - Controle de edição baseado em `billing_status`
- **Status:** ✅ Ativo em produção
- **Métodos relevantes:**
  - `criarMatricula()` - Processa entrada, parcelamento, saldo devedor
  - `atualizarMatricula()` - Atualiza plano de pagamento (se `billing_status` permitir)

### 1.2 Models Relacionados

#### ✅ **Enrollment** (`app/Models/Enrollment.php`)
- **Finalidade:** Model básico para matrículas
- **Métodos:**
  - `findByStudent()` - Busca matrículas por aluno
  - `findWithDetails()` - Busca com detalhes do serviço e aluno
  - `calculateFinalPrice()` - Calcula preço final (base - desconto + extra)
- **Status:** ✅ Ativo em produção

### 1.3 Services Relacionados

#### ✅ **EnrollmentPolicy** (`app/Services/EnrollmentPolicy.php`)
- **Finalidade:** Políticas de acesso baseadas em status financeiro
- **Métodos:**
  - `canSchedule()` - Verifica se pode agendar aula (bloqueia se `financial_status = 'bloqueado'`)
  - `canStartLesson()` - Verifica se pode iniciar aula (bloqueia se `financial_status = 'bloqueado'`)
- **Status:** ✅ Ativo em produção
- **Impacto:** Bloqueia agendamento e início de aulas para alunos bloqueados

#### ✅ **StudentHistoryService** (`app/Services/StudentHistoryService.php`)
- **Finalidade:** Registro de histórico do aluno
- **Funcionalidades relacionadas:**
  - `logFinancialEvent()` - Registra eventos financeiros (entrada, parcelamento)
  - `logFinancialStatusChanged()` - Registra mudanças de status financeiro
- **Status:** ✅ Ativo em produção

---

## 2. Banco de Dados

### 2.1 Tabela Principal: `enrollments`

**Estrutura completa relacionada a pagamentos:**

| Campo | Tipo | Descrição | Origem |
|-------|------|-----------|--------|
| `id` | INT(11) | PK | Migration 002 |
| `base_price` | DECIMAL(10,2) | Preço base do serviço | Migration 002 |
| `discount_value` | DECIMAL(10,2) | Valor do desconto | Migration 002 |
| `extra_value` | DECIMAL(10,2) | Valor extra | Migration 002 |
| `final_price` | DECIMAL(10,2) | Preço final calculado | Migration 002 |
| `payment_method` | ENUM | Método de pagamento | Migration 002 (expandido em 009) |
| `financial_status` | ENUM | Status financeiro | Migration 002 |
| `entry_amount` | DECIMAL(10,2) | Valor da entrada recebida | Migration 010 |
| `entry_payment_method` | ENUM | Forma de pagamento da entrada | Migration 010 |
| `entry_payment_date` | DATE | Data do pagamento da entrada | Migration 010 |
| `outstanding_amount` | DECIMAL(10,2) | Saldo devedor (final_price - entry_amount) | Migration 010 |
| `installments` | INT(11) | Número de parcelas (1-12) | Migration 009 |
| `down_payment_amount` | DECIMAL(10,2) | Valor da entrada (quando entrada_parcelas) | Migration 009 |
| `down_payment_due_date` | DATE | Vencimento da entrada | Migration 009 |
| `first_due_date` | DATE | Vencimento da primeira parcela | Migration 009 |
| `billing_status` | ENUM | Status da geração de cobrança Asaas | Migration 009 |

**Valores dos ENUMs:**

- `payment_method`: `'pix'`, `'boleto'`, `'cartao'`, `'entrada_parcelas'` (adicionado em 009)
- `financial_status`: `'em_dia'`, `'pendente'`, `'bloqueado'`
- `entry_payment_method`: `'dinheiro'`, `'pix'`, `'cartao'`, `'boleto'`
- `billing_status`: `'draft'`, `'ready'`, `'generated'`, `'error'`

**Relacionamentos:**
- `student_id` → `students.id` (FK)
- `service_id` → `services.id` (FK)
- `cfc_id` → `cfcs.id` (FK)

**Índices relevantes:**
- `financial_status`
- `billing_status`
- `first_due_date`
- `entry_payment_date`
- `outstanding_amount`

### 2.2 Tabelas Relacionadas

#### ❌ **Não existem tabelas específicas para:**
- `payments` (pagamentos individuais)
- `invoices` (faturas)
- `subscriptions` (assinaturas)
- `installments` (parcelas individuais)
- `transactions` (transações)

**Conclusão:** Toda a informação financeira está concentrada na tabela `enrollments`. Não há rastreamento de pagamentos individuais ou parcelas separadas.

### 2.3 Migrations Relacionadas

1. **Migration 002** (`002_create_phase1_tables.sql`)
   - Cria estrutura base de `enrollments` com campos financeiros básicos

2. **Migration 009** (`009_add_payment_plan_to_enrollments.sql`)
   - Adiciona campos de parcelamento
   - Adiciona `billing_status` (preparação para Asaas)
   - Expande `payment_method` para incluir `'entrada_parcelas'`

3. **Migration 010** (`010_add_entry_fields_to_enrollments.sql`)
   - Adiciona campos de entrada (`entry_amount`, `entry_payment_method`, `entry_payment_date`)
   - Adiciona `outstanding_amount` (saldo devedor)

---

## 3. Integrações Externas

### 3.1 Gateway de Pagamento

#### ⚠️ **Asaas - Preparado mas NÃO Implementado**

**Evidências de preparação:**
- Campo `billing_status` na tabela `enrollments` com comentário "Status da geração de cobrança Asaas"
- Botão "Gerar Cobrança Asaas" na view `matricula_show.php` (linha 364)
- Função JavaScript `gerarCobrancaAsaas()` preparada mas com TODO (linha 507)
- Comentários no código mencionando "Asaas" e uso de `outstanding_amount`

**Status atual:**
- ❌ Nenhum service de integração implementado
- ❌ Nenhuma rota de webhook configurada
- ❌ Nenhuma configuração de API key/token encontrada
- ❌ Nenhum arquivo `.env` encontrado no projeto
- ⚠️ Apenas estrutura preparada, funcionalidade não implementada

**Código relevante:**
```javascript
// app/Views/alunos/matricula_show.php:507
// TODO: Implementar chamada AJAX para endpoint de geração de cobrança
// IMPORTANTE: Usar outstanding_amount ao invés de final_price
```

#### ❌ **Outros Gateways**
- **Mercado Pago:** Não encontrado
- **Pagar.me:** Não encontrado
- **Stripe:** Não encontrado

### 3.2 Configurações

#### ❌ **Arquivo .env**
- Não encontrado no projeto
- Não há referências a variáveis de ambiente para gateways

#### ❌ **Services de Integração**
- Não existe `PaymentService`, `AsaasService`, ou similar
- Não há classes para comunicação com APIs externas de pagamento

#### ❌ **Webhooks/Rotas de Callback**
- Nenhuma rota configurada para receber notificações de pagamento
- Nenhum endpoint para processar callbacks de gateway

---

## 4. Fluxo Atual de Cobrança

### 4.1 Processo Atual

**O sistema atualmente:**

1. ✅ **Registra pagamento manual** (entrada)
   - Campos: `entry_amount`, `entry_payment_method`, `entry_payment_date`
   - Registrado no momento da matrícula ou edição

2. ✅ **Controla status financeiro** (`financial_status`)
   - Valores: `'em_dia'`, `'pendente'`, `'bloqueado'`
   - Alterado manualmente por admin/secretaria

3. ✅ **Calcula saldo devedor** (`outstanding_amount`)
   - Fórmula: `final_price - entry_amount`
   - Atualizado automaticamente

4. ✅ **Prepara parcelamento** (campos preparados)
   - `installments` (número de parcelas)
   - `down_payment_amount`, `down_payment_due_date` (entrada)
   - `first_due_date` (vencimento primeira parcela)

5. ❌ **NÃO gera cobranças automaticamente**
   - Botão "Gerar Cobrança Asaas" existe mas não funciona
   - Não há integração com gateway

6. ❌ **NÃO rastreia pagamentos individuais**
   - Não há tabela de pagamentos
   - Não há histórico de parcelas pagas

### 4.2 Impacto do Status Financeiro

**Onde `financial_status` impacta o sistema:**

1. ✅ **Agendamento de Aulas** (`EnrollmentPolicy::canSchedule()`)
   - Bloqueia se `financial_status = 'bloqueado'`
   - Local: `app/Controllers/AgendaController.php`

2. ✅ **Início de Aulas** (`EnrollmentPolicy::canStartLesson()`)
   - Bloqueia se `financial_status = 'bloqueado'`
   - Local: `app/Controllers/AgendaController.php`

3. ✅ **Visualização no Dashboard**
   - Alunos bloqueados aparecem em alertas
   - Local: `app/Views/dashboard/*.php`

4. ✅ **Consulta Financeira**
   - Filtros por status (em dia, pendente, bloqueado)
   - Local: `app/Controllers/FinanceiroController.php`

**Onde NÃO impacta:**
- ❌ Matrícula pode ser criada mesmo sem pagamento
- ❌ Não há bloqueio automático por vencimento
- ❌ Não há atualização automática de status baseado em pagamentos

---

## 5. Pontos de Acoplamento

### 5.1 Onde o Gateway Vai se Conectar

#### ✅ **1. Criação de Matrícula** (`AlunosController::criarMatricula()`)
- **Local:** `app/Controllers/AlunosController.php:398-591`
- **Momento:** Após salvar matrícula, antes de criar etapas
- **Dados disponíveis:**
  - `outstanding_amount` (saldo devedor)
  - `installments` (número de parcelas)
  - `payment_method` (método escolhido)
  - `first_due_date` ou `down_payment_due_date` (vencimentos)
- **Hook natural:** Após `$enrollmentId = $enrollmentModel->create($enrollmentData);`
- **Status atual:** Campo `billing_status` é criado como `'draft'`

#### ✅ **2. Edição de Matrícula** (`AlunosController::atualizarMatricula()`)
- **Local:** `app/Controllers/AlunosController.php:655-843`
- **Momento:** Quando plano de pagamento é alterado
- **Restrição:** Só permite editar se `billing_status IN ('draft', 'ready', 'error')`
- **Hook natural:** Após validação, antes de atualizar banco

#### ✅ **3. Botão "Gerar Cobrança Asaas"** (View)
- **Local:** `app/Views/alunos/matricula_show.php:364`
- **Status:** Preparado mas não implementado
- **Função:** `gerarCobrancaAsaas()` (linha 482)
- **Necessita:** Endpoint AJAX para processar geração

#### ⚠️ **4. Renovação de Matrícula**
- **Status:** Não identificado processo específico de renovação
- **Observação:** Pode ser necessário criar fluxo específico

#### ⚠️ **5. Mensalidade/Recorrente**
- **Status:** Não identificado processo de mensalidade
- **Observação:** Sistema atual parece ser baseado em matrícula única, não recorrente

#### ⚠️ **6. Serviços Adicionais**
- **Status:** Não identificado processo específico
- **Observação:** Pode usar mesma estrutura de matrícula

### 5.2 Hooks Naturais Existentes

✅ **Hooks já preparados:**
- Campo `billing_status` para controlar estado da cobrança
- Validação de edição baseada em `billing_status`
- Cálculo automático de `outstanding_amount`
- Estrutura de parcelamento já definida

❌ **Hooks que precisam ser criados:**
- Endpoint para gerar cobrança via API
- Webhook para receber notificações de pagamento
- Service para comunicação com gateway
- Atualização automática de `financial_status` baseado em pagamentos

---

## 6. Riscos e Cuidados

### 6.1 Pontos que NÃO Devem ser Impactados

#### ⚠️ **1. Matrícula Ativa Antes de Pagamento**
- **Situação atual:** Matrícula pode ser criada sem pagamento (`financial_status = 'em_dia'` por padrão)
- **Risco:** Se implementar bloqueio automático, pode quebrar fluxo atual
- **Recomendação:** Manter comportamento atual ou criar flag de configuração

#### ⚠️ **2. Status Financeiro Manual**
- **Situação atual:** Admin/secretaria altera `financial_status` manualmente
- **Risco:** Gateway pode tentar atualizar automaticamente, causando conflito
- **Recomendação:** Definir política clara (automático vs manual) ou criar flag de controle

#### ⚠️ **3. Entrada Manual**
- **Situação atual:** Entrada é registrada manualmente no sistema
- **Risco:** Gateway pode não saber sobre entrada já paga
- **Recomendação:** Gateway deve usar `outstanding_amount` (já implementado corretamente)

#### ⚠️ **4. Bloqueio de Aulas**
- **Situação atual:** `EnrollmentPolicy` bloqueia se `financial_status = 'bloqueado'`
- **Risco:** Gateway pode alterar status e impactar agendamentos existentes
- **Recomendação:** Considerar período de carência ou notificação antes de bloquear

### 6.2 Lógica Sensível

#### 🔴 **1. Cálculo de Saldo Devedor**
- **Local:** `AlunosController::criarMatricula()` linha 434
- **Fórmula:** `outstanding_amount = max(0, final_price - entry_amount)`
- **Cuidado:** Gateway deve usar `outstanding_amount`, não `final_price`
- **Status:** ✅ Já documentado no código (linha 509 de `matricula_show.php`)

#### 🔴 **2. Edição de Plano de Pagamento**
- **Local:** `AlunosController::atualizarMatricula()` linha 714-716
- **Regra:** Só permite editar se `billing_status IN ('draft', 'ready', 'error')`
- **Cuidado:** Se gateway gerar cobrança (`billing_status = 'generated'`), não pode mais editar
- **Status:** ✅ Já implementado corretamente

#### 🔴 **3. Bloqueio Automático**
- **Local:** `EnrollmentPolicy::canSchedule()` e `canStartLesson()`
- **Regra:** Bloqueia se `financial_status = 'bloqueado'`
- **Cuidado:** Se gateway atualizar status automaticamente, pode bloquear aluno em uso
- **Recomendação:** Implementar notificação ou período de carência

#### 🟡 **4. Histórico de Pagamentos**
- **Situação atual:** Não há rastreamento de pagamentos individuais
- **Cuidado:** Gateway pode precisar registrar cada parcela paga
- **Recomendação:** Avaliar necessidade de tabela `payments` ou `installment_payments`

---

## 7. Conclusão Técnica

### 7.1 Estado Atual do Sistema

**O sistema está:**
- ✅ **Com base parcial** - Estrutura preparada, mas funcionalidade não implementada

**Detalhamento:**
- ✅ Estrutura de dados completa (tabela `enrollments` com todos os campos necessários)
- ✅ Interface preparada (botão e campos na view)
- ✅ Validações e regras de negócio implementadas
- ✅ Políticas de acesso baseadas em status financeiro
- ❌ Integração com gateway não implementada
- ❌ Webhooks não configurados
- ❌ Service layer para pagamentos não existe
- ❌ Rastreamento de pagamentos individuais não existe

### 7.2 Recomendações de Implementação

#### **Estratégia Recomendada: Service Layer + Módulo Isolado**

**1. Criar Service Layer para Pagamentos**
```
app/Services/PaymentService.php
app/Services/GatewayService.php (interface)
app/Services/AsaasService.php (implementação)
```

**2. Criar Model para Rastreamento (se necessário)**
```
app/Models/Payment.php (opcional - para rastrear pagamentos individuais)
app/Models/Installment.php (opcional - para rastrear parcelas)
```

**3. Criar Controller para Webhooks**
```
app/Controllers/PaymentWebhookController.php
```

**4. Adicionar Rotas**
```
POST /api/payments/generate (gerar cobrança)
POST /api/payments/webhook/asaas (receber notificações)
```

**5. Implementar de Forma Incremental**
- Fase 1: Service básico + geração de cobrança
- Fase 2: Webhook + atualização de status
- Fase 3: Rastreamento de pagamentos individuais (se necessário)
- Fase 4: Notificações e alertas automáticos

**6. Manter Compatibilidade**
- Não alterar estrutura existente de `enrollments`
- Usar `billing_status` para controlar estado
- Manter `financial_status` para controle de acesso
- Preservar fluxo manual existente (entrada manual)

### 7.3 Pontos de Atenção

1. ⚠️ **Usar `outstanding_amount` ao invés de `final_price`** (já documentado)
2. ⚠️ **Respeitar `billing_status` para edição** (já implementado)
3. ⚠️ **Considerar impacto de bloqueio automático** (avaliar período de carência)
4. ⚠️ **Definir política de atualização automática vs manual** (flag de configuração)
5. ⚠️ **Avaliar necessidade de tabela de pagamentos** (depende de requisitos de rastreamento)

---

## 8. Resumo Executivo

| Aspecto | Status | Observações |
|---------|--------|-------------|
| **Estrutura de Dados** | ✅ Completa | Tabela `enrollments` com todos os campos necessários |
| **Interface** | ✅ Preparada | Botão e campos existem, mas não funcionam |
| **Regras de Negócio** | ✅ Implementadas | Validações, cálculos e políticas existem |
| **Integração Gateway** | ❌ Não implementada | Apenas preparação (campo `billing_status`) |
| **Webhooks** | ❌ Não configurados | Nenhuma rota ou handler |
| **Service Layer** | ❌ Não existe | Precisa ser criado |
| **Rastreamento** | ❌ Não existe | Não há tabela de pagamentos individuais |

**Conclusão:** O sistema tem uma base sólida e bem preparada, mas a integração real com gateway de pagamento ainda não foi implementada. A estrutura permite implementação incremental sem quebrar funcionalidades existentes.

---

**Próximos Passos Sugeridos:**
1. Definir gateway escolhido (Asaas já está preparado)
2. Criar Service Layer para pagamentos
3. Implementar endpoint de geração de cobrança
4. Configurar webhooks
5. Implementar atualização automática de status (com cuidado)
6. Avaliar necessidade de rastreamento de pagamentos individuais
