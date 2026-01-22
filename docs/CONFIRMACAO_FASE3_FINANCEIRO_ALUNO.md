# Confirmação FASE 3 - Financeiro do Aluno

**Data:** 2025-11-24  
**Objetivo:** Confirmar estrutura do módulo financeiro antes de implementar área do aluno

## 1. Estrutura de Dados

### Tabela `financeiro_faturas`
- **Campo principal:** `aluno_id` (relaciona fatura ↔ aluno)
- **Campos relevantes:**
  - `id` - ID da fatura
  - `titulo` - Descrição da fatura
  - `valor_total` - Valor da fatura
  - `data_vencimento` - Data de vencimento
  - `status` - Status: `aberta`, `paga`, `vencida`, `cancelada`, `parcial`
  - `forma_pagamento` - Forma de pagamento
  - `observacoes` - Observações
  - `criado_em` - Data de criação

### Relacionamentos
- `financeiro_faturas.aluno_id` → `alunos.id`
- `financeiro_faturas.matricula_id` → `matriculas.id` (opcional)

## 2. API Atual

### `admin/api/financeiro-faturas.php`
- **Método GET:** Lista faturas com filtros (aluno_id, status, data_inicio, data_fim)
- **Permissões atuais:** Apenas `admin` e `secretaria`
- **Estrutura de resposta:**
  ```json
  {
    "success": true,
    "data": [...],
    "pagination": {...}
  }
  ```

### Adaptação Necessária
- Adicionar suporte para `tipo_usuario = 'aluno'`
- Quando for aluno, forçar `aluno_id = getCurrentAlunoId()`
- Ignorar qualquer `aluno_id` vindo de GET/POST quando for aluno
- Manter compatibilidade com admin/secretaria

## 3. Página Admin de Referência

### `admin/pages/financeiro-faturas.php`
- Lista faturas em tabela
- Filtros: aluno, status, período
- Funções auxiliares:
  - `getDescricaoCurtaFatura()` - Formata título
  - `formatarStatusFatura()` - Formata status com cores

## 4. Decisão de Implementação

**Opção escolhida:** Adaptar `admin/api/financeiro-faturas.php` (Opção A)

**Motivos:**
- API já existe e funciona
- Estrutura de dados compatível
- Menos código duplicado
- Manutenção mais simples

**Garantias de segurança:**
- Aluno só vê suas próprias faturas (via `getCurrentAlunoId()`)
- Não aceita `aluno_id` externo quando for aluno
- Validação em todas as operações GET

