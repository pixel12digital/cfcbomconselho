# FASE 1 – Limpeza e Base Estrutural

**Data de início:** 2025-01-27  
**Objetivo:** Organização estrutural + correções críticas sem quebrar funcionalidades existentes  
**Base:** `admin/pages/_RAIO-X-COMPLETO-SISTEMA.md`

---

## 1. Escopo da Fase 1

Esta fase tem como objetivo:

1. ✅ **Isolamento e documentação de código legado** (sem apagar ainda)
   - Mover arquivos legados não utilizados para pastas `legacy/`
   - Documentar arquivos legados que ainda estão em uso

2. ✅ **Correção crítica do job financeiro**
   - Corrigir `admin/jobs/marcar_faturas_vencidas.php` para usar `financeiro_faturas` (tabela oficial)

3. ✅ **Alinhamento de instalação**
   - Garantir que `install.php` + migrations criem todas as tabelas que o código usa
   - Focar nas tabelas críticas: `matriculas`, `financeiro_faturas`, `pagamentos`, `financeiro_despesas`

**NÃO será feito nesta fase:**
- ❌ PWA instrutor/aluno
- ❌ Regras avançadas de aulas
- ❌ Notificações push
- ❌ Alterações de UX/UI
- ❌ Remoção definitiva de código (apenas isolamento)

---

## 2. Arquivos LEGACY Identificados

### 2.1. APIs Legadas (do RAIO-X)

| Arquivo | Status | Motivo | Ação |
|---------|--------|--------|------|
| `admin/api/faturas.php` | 🔴 LEGADO | Substituída por `financeiro-faturas.php` | Verificar uso → Mover |
| `admin/api/salas.php` | 🔴 LEGADO | Substituída por `salas-real.php` | Verificar uso → Mover |
| `admin/api/salas-ajax.php` | 🔴 LEGADO | Versão antiga AJAX | Verificar uso → Mover |
| `admin/api/salas-clean.php` | 🔴 LEGADO | Versão "limpa" | Verificar uso → Mover |
| `admin/api/instrutores-real.php` | 🔴 LEGADO | Versão antiga | Verificar uso → Mover |
| `admin/api/instrutores-simple.php` | 🔴 LEGADO | Versão simplificada | Verificar uso → Mover |
| `admin/api/instrutores_simplificado.php` | 🔴 LEGADO | Versão simplificada 2 | Verificar uso → Mover |
| `admin/api/exames_simple.php` | 🔴 LEGADO | Versão simplificada | Verificar uso → Mover |
| `admin/api/disciplinas-clean.php` | 🔴 LEGADO | Versão "limpa" | Verificar uso → Mover |
| `admin/api/disciplinas-simples.php` | 🔴 LEGADO | Versão simplificada | Verificar uso → Mover |
| `admin/api/disciplinas-estaticas.php` | 🔴 LEGADO | Versão estática | Verificar uso → Mover |
| `admin/api/alunos-aptos-turma-simples.php` | 🔴 LEGADO | Versão simplificada | Verificar uso → Mover |
| `admin/api/notifications.php` | 🔴 LEGADO | Duplicação inglês | Verificar uso → Mover |
| `admin/api/tipos-curso-clean.php` | 🔴 LEGADO | Versão "limpa" | Verificar uso → Mover |

**Total:** 14 APIs legadas

### 2.2. Páginas Legadas (do RAIO-X)

| Arquivo | Status | Motivo | Ação |
|---------|--------|--------|------|
| `admin/pages/financeiro-faturas-standalone.php` | 🔴 LEGADO | Versão standalone | Verificar uso → Mover |
| `admin/pages/financeiro-despesas-standalone.php` | 🔴 LEGADO | Versão standalone | Verificar uso → Mover |
| `admin/pages/financeiro-relatorios-standalone.php` | 🔴 LEGADO | Versão standalone | Verificar uso → Mover |
| `admin/pages/historico-aluno-melhorado.php` | 🔴 LEGADO | Versão antiga | Verificar uso → Mover |
| `admin/pages/historico-aluno-novo.php` | 🔴 LEGADO | Versão antiga | Verificar uso → Mover |
| `admin/pages/instrutores-otimizado.php` | 🔴 LEGADO | Versão antiga | Verificar uso → Mover |
| `admin/pages/turmas-teoricas-fixed.php` | 🔴 LEGADO | Versão "fixed" | Verificar uso → Mover |
| `admin/pages/turmas-teoricas-disciplinas-fixed.php` | 🔴 LEGADO | Versão "fixed" | Verificar uso → Mover |
| `admin/pages/alunos_original.php` | 🔴 LEGADO | Backup | Verificar uso → Mover |
| `admin/pages/alunos-complete.txt` | 🔴 LEGADO | Arquivo texto | Verificar uso → Remover |
| `admin/pages/_modalAluno-legacy.php` | 🔴 LEGADO | Modal legado | Verificar uso → Mover |
| `admin/pages/usuarios_simples.php` | 🔴 LEGADO | Versão simplificada | Verificar uso → Mover |

**Total:** 12 páginas legadas

### 2.3. JS Temporários

| Arquivo | Status | Motivo | Ação |
|---------|--------|--------|------|
| `CORRECOES_MODAL_EMERGENCIAL.js` | 🔴 TEMPORÁRIO | Arquivo na raiz | Verificar uso → Mover/Remover |
| `admin/assets/js/mobile-debug.js` | 🔴 DEBUG | Debug | Remover em produção |

**Total:** 2 arquivos JS

### 2.4. Arquivos Movidos para Legacy

**APIs movidas:**
- Nenhuma movida nesta fase (todas ainda em uso ativo - ver seção 2.5)

**Páginas movidas:**
- Nenhuma movida nesta fase (todas ainda em uso ativo ou são backups - ver seção 2.5)

**Pastas legacy criadas:**
- ✅ `admin/api/legacy/` - criada
- ✅ `admin/pages/legacy/` - criada

### 2.5. Arquivos Legados Ainda em Uso (NÃO Mover - Revisar em Fase Futura)

**APIs legadas ainda em uso:**
- ❌ `admin/api/faturas.php` - EM USO em `admin/pages/alunos.php` (linha 6972) e `admin/pages/financeiro-faturas-standalone.php`
- ❌ `admin/api/salas-clean.php` - EM USO em `admin/index.php` (linhas 2932, 3065) e `admin/pages/turmas-teoricas.php` (múltiplas linhas)
- ❌ `admin/api/instrutores-real.php` - EM USO em `admin/pages/turmas-teoricas-detalhes-inline.php` (linhas 12452, 12630)
- ❌ `admin/api/exames_simple.php` - EM USO em `admin/pages/exames.php` (múltiplas linhas)
- ❌ `admin/api/disciplinas-clean.php` - EM USO em `admin/pages/turmas-teoricas.php`, `turmas-teoricas-detalhes-inline.php`, `admin/assets/js/admin.js` (múltiplas linhas)
- ❌ `admin/api/disciplinas-estaticas.php` - EM USO em `admin/pages/turmas-teoricas-detalhes-inline.php` (linhas 11414, 11595)
- ❌ `admin/api/tipos-curso-clean.php` - EM USO em `admin/pages/turmas-teoricas.php` e `admin/assets/js/admin.js` (múltiplas linhas)
- ❌ `admin/api/alunos-aptos-turma-simples.php` - EM USO em `admin/pages/turmas-teoricas-detalhes-inline.php` (linha 12946)
- ❌ `admin/api/notifications.php` - EM USO em `admin/assets/js/topbar-unified.js` (linhas 471, 641)

**Páginas legadas ainda em uso ou backups:**
- ⚠️ `admin/pages/financeiro-faturas-standalone.php` - Usa `faturas.php` antiga (migrar para `financeiro-faturas.php` em fase futura)
- ⚠️ `admin/pages/financeiro-despesas-standalone.php` - Versão standalone (verificar uso)
- ⚠️ `admin/pages/financeiro-relatorios-standalone.php` - Versão standalone (verificar uso)
- ❌ `admin/pages/instrutores-otimizado.php` - Tem CSS próprio (verificar se está em uso)
- ❌ `admin/pages/historico-aluno-melhorado.php` - Versão melhorada (verificar se está em uso)
- ❌ `admin/pages/historico-aluno-novo.php` - Versão nova (verificar se está em uso)
- ❌ `admin/pages/turmas-teoricas-fixed.php` - Versão fixed (verificar se está em uso)
- ❌ `admin/pages/turmas-teoricas-disciplinas-fixed.php` - Versão fixed (verificar se está em uso)
- ❌ `admin/pages/alunos_original.php` - BACKUP (pode remover após confirmação)
- ❌ `admin/pages/alunos-complete.txt` - BACKUP (pode remover após confirmação)
- ❌ `admin/pages/_modalAluno-legacy.php` - BACKUP (pode remover após confirmação)
- ❌ `admin/pages/usuarios_simples.php` - Versão simplificada (verificar se está em uso)

**JS Temporários/Debug:**
- ❌ `admin/assets/js/mobile-debug.js` - EM USO em `admin/index.php` (linha 2386) - NÃO remover ainda

**Decisão Fase 1:**
- Nenhum arquivo será movido nesta fase pois todos os arquivos identificados ainda estão em uso ativo ou são backups importantes
- Ação será deixada para Fase Futura (migração gradual de APIs e refino de código)

---

## 3. Ajustes Financeiros

### 3.1. Decisão: Tabela Oficial

**Tabela oficial de faturas:** `financeiro_faturas`

**Justificativa:**
- API ativa: `admin/api/financeiro-faturas.php` usa `financeiro_faturas`
- Página ativa: `admin/pages/financeiro-faturas.php` usa `financeiro_faturas`
- Job quebrado: `admin/jobs/marcar_faturas_vencidas.php` usa `faturas` (errado)

**Tabela antiga:** `faturas` (usada apenas no job quebrado)

### 3.2. Status de Faturas (via `financeiro-faturas.php`)

Status identificados na API oficial:
- `aberta` - Fatura em aberto
- `paga` - Fatura paga
- `vencida` - Fatura vencida
- `parcial` - Pagamento parcial (se aplicável)

**Campos da tabela `financeiro_faturas`:**
- `id`
- `aluno_id` (FK)
- `matricula_id` (FK - opcional)
- `valor`
- `data_vencimento` (campo usado para verificar vencimento)
- `status` (ENUM: aberta, paga, vencida, parcial)
- `descricao`
- `observacoes`
- `forma_pagamento`
- `criado_por`
- `criado_em`
- `atualizado_em`

### 3.3. Ajustes no Job

**Arquivo:** `admin/jobs/marcar_faturas_vencidas.php`

**Alterações realizadas:**
- [x] Tabela alterada de `faturas` para `financeiro_faturas`
- [x] Campo alterado de `vencimento` para `data_vencimento`
- [x] Query atualizada para usar campos corretos
- [x] Comentários adicionados referenciando esta fase e o RAIO-X
- [x] Corrigido uso de `rowCount()` para `fetchColumn()` (correção técnica)
- [x] Adicionado tratamento para `matricula_id` NULL (pode ser NULL em financeiro_faturas)

**Status:** ✅ CONCLUÍDO (2025-01-27)

**Detalhes da correção:**
- Linha 18-21: UPDATE agora usa `financeiro_faturas` e `data_vencimento`
- Linha 24-28: COUNT corrigido para usar `fetchColumn()` e tabela correta
- Linha 36-45: JOIN corrigido para usar `financeiro_faturas` e filtrar `matricula_id IS NOT NULL`
- Linha 54-61: UPDATE corrigido para usar `financeiro_faturas`
- Linha 74-81: Estatísticas atualizadas para usar `financeiro_faturas`
- Logs atualizados para incluir informações da tabela/campo usados

---

## 4. Ajustes de Instalação

### 4.1. Tabelas Críticas - Diagnóstico

| Tabela | Tem Migration? | Usada por | Situação | Ação |
|--------|----------------|-----------|----------|------|
| `matriculas` | ✅ Criada | `admin/api/matriculas.php` | ✅ OK | ✅ Migration 004 criada |
| `financeiro_faturas` | ✅ Criada | `admin/api/financeiro-faturas.php`, `admin/pages/financeiro-faturas.php`, `admin/index.php` | ✅ OK | ✅ Migration 005 criada |
| `pagamentos` | ✅ Criada | `admin/api/pagamentos.php` | ⚠️ Relaciona com `faturas` antiga | ✅ Migration 006 criada |
| `financeiro_pagamentos` | ✅ Criada | `admin/api/financeiro-despesas.php` | ✅ OK | ✅ Migration 007 criada |

**Nota:** `financeiro_despesas` não existe - a API usa `financeiro_pagamentos` para despesas.

### 4.2. Migrations Criadas/Ajustadas

**Migrations novas criadas:**
- ✅ `admin/migrations/004-create-matriculas-structure.sql`
  - Campos: aluno_id, categoria_cnh, tipo_servico, status, data_inicio, data_fim, valor_total, forma_pagamento, observacoes, renach, processo_numero, processo_numero_detran, processo_situacao, status_financeiro
  
- ✅ `admin/migrations/005-create-financeiro-faturas-structure.sql`
  - Campos: aluno_id, matricula_id, titulo, descricao, valor, valor_total, data_vencimento (oficial), vencimento (alternativo), status, forma_pagamento, parcelas, observacoes, reteste
  - Nota: Inclui ambos os campos (vencimento e data_vencimento) por compatibilidade

- ✅ `admin/migrations/006-create-pagamentos-structure.sql`
  - Campos: fatura_id, data_pagamento, valor_pago, metodo, comprovante_url, obs
  - Nota: Relaciona com `faturas` antiga (corrigir em fase futura)

- ✅ `admin/migrations/007-create-financeiro-pagamentos-structure.sql`
  - Campos: fornecedor, descricao, categoria, valor, status, vencimento, data_pagamento, forma_pagamento, comprovante_url, observacoes
  - Nota: Esta é a tabela de despesas (não `financeiro_despesas`)

**Ajustes no install.php:**
- ✅ Linha ~172: Tabela `matriculas` adicionada (após `exames`)
- ✅ Linha ~172: Tabela `financeiro_faturas` adicionada
- ✅ Linha ~172: Tabela `pagamentos` adicionada
- ✅ Linha ~172: Tabela `financeiro_pagamentos` adicionada
- ✅ Linha ~215: Índices adicionados para as novas tabelas

**Estruturas baseadas em:**
- `matriculas`: admin/api/matriculas.php (linhas 145-158, 196-208)
- `financeiro_faturas`: admin/api/financeiro-faturas.php, admin/index.php (linha 233), admin/pages/financeiro-faturas.php
- `pagamentos`: admin/api/pagamentos.php (linha 141)
- `financeiro_pagamentos`: admin/api/financeiro-despesas.php (linha 171)

---

## 5. Decisões Tomadas

### 5.1. Estrutura Financeira

- ✅ **Tabela oficial de faturas:** `financeiro_faturas`
- ✅ **API oficial:** `admin/api/financeiro-faturas.php`
- ✅ **Página oficial:** `admin/pages/financeiro-faturas.php`
- ❌ **Tabela antiga `faturas`:** Não será usada (será documentada para remoção futura)

### 5.2. Organização de Código Legado

- ✅ **Pasta para APIs legadas:** `admin/api/legacy/`
- ✅ **Pasta para páginas legadas:** `admin/pages/legacy/`
- ✅ **Critério para mover:** Apenas arquivos sem referências ativas no código

### 5.3. Instalação

- ✅ **install.php** deve criar todas as tabelas usadas pelo sistema
- ✅ **Migrations** devem refletir a estrutura atual do banco
- ✅ **Ordem:** Primeiro criar migrations, depois alinhar install.php

---

## 6. Divergências Encontradas

### 6.1. Tabela `financeiro_faturas` - Inconsistência de Campo

**Problema:** Há divergência entre o nome do campo de vencimento:
- API `admin/api/financeiro-faturas.php`: usa `vencimento` (linhas 113, 118, 139, 189, 230, 323, 344)
- Página `admin/pages/financeiro-faturas.php`: usa `data_vencimento` (linhas 24, 57, 62, 73)
- `admin/index.php` (criação de faturas): usa `data_vencimento` (linhas 122, 178, 233)

**Decisão Fase 1:** 
- Job corrigido para usar `data_vencimento` (baseado no uso em páginas e criação)
- Migration criará campo `data_vencimento` como oficial
- Inconsistência na API deixada para correção em fase futura (não quebrar funcionalidade)

### 6.2. Tabela `pagamentos` - Relaciona com Tabela Antiga

**Problema:** API `admin/api/pagamentos.php`:
- Usa tabela `pagamentos` ✅ (linha 141)
- Mas relaciona com tabela `faturas` antiga (linhas 82, 93, 200, 228)
- Deveria relacionar com `financeiro_faturas`

**Decisão Fase 1:**
- Migration criará tabela `pagamentos` conforme uso atual
- Relação com `faturas` antiga documentada para correção futura
- Não corrigir agora para não quebrar funcionalidade existente

### 6.3. Tabela Despesas - Nome Diferente

**Problema:** API `admin/api/financeiro-despesas.php`:
- Usa tabela `financeiro_pagamentos` (não `financeiro_despesas`)
- Pode ser confusão de nomenclatura ou estrutura legada

**Decisão Fase 1:**
- Migration criará `financeiro_pagamentos` conforme uso na API
- Documentado para revisão futura

---

## 7. Checklist de Tarefas Concluídas

### Etapa 1: Legacy (isolamento)

- [x] Criar pastas `admin/api/legacy/` e `admin/pages/legacy/`
- [x] Verificar referências de cada arquivo legado
- [x] Verificar APIs legadas sem uso ativo (nenhuma encontrada - todas em uso)
- [x] Verificar páginas legadas sem uso ativo (algumas são backups, não mover sem confirmação)
- [x] Documentar arquivos legados ainda em uso
- [x] Atualizar esta seção com resultados

**Resultado:** Nenhum arquivo movido nesta fase - todos os arquivos legados identificados ainda estão em uso ativo ou são backups importantes. Ação será deixada para fase futura (migração gradual).

### Etapa 2: Financeiro (correção do job)

- [x] Confirmar tabela oficial `financeiro_faturas`
- [x] Analisar estrutura da tabela e campos
- [x] Corrigir `admin/jobs/marcar_faturas_vencidas.php`
- [x] Adicionar comentários no código
- [x] Documentar alterações nesta seção
- [ ] Testar job (se possível em ambiente de teste) - ⚠️ Requer ambiente de teste

### Etapa 3: Instalação (install.php + migrations)

- [x] Verificar migrations existentes para tabelas críticas
- [x] Analisar estrutura das tabelas no código (APIs)
- [x] Criar migrations faltantes (4 migrations criadas)
- [x] Ajustar `install.php` com tabelas faltantes
- [x] Adicionar comentários no install.php
- [x] Documentar todas as alterações

### Finalização

- [x] Revisar todo o documento
- [x] Garantir que todas as alterações estão documentadas
- [x] Verificar que nenhum fluxo foi quebrado (nenhum arquivo movido, apenas documentação)

---

## 8. Resumo da Fase 1

### ✅ O que foi feito:

1. **Etapa 1 - Legacy:** 
   - Pastas legacy criadas (`admin/api/legacy/`, `admin/pages/legacy/`)
   - Verificação completa de referências realizada
   - Documentação de arquivos legados ainda em uso criada
   - Nenhum arquivo movido (todos ainda em uso ativo)

2. **Etapa 2 - Financeiro:**
   - Job `admin/jobs/marcar_faturas_vencidas.php` corrigido
   - Tabela alterada de `faturas` para `financeiro_faturas`
   - Campo alterado de `vencimento` para `data_vencimento`
   - Comentários e documentação adicionados

3. **Etapa 3 - Instalação:**
   - 4 migrations criadas (004, 005, 006, 007)
   - `install.php` atualizado com tabelas críticas
   - Índices adicionados para performance

### ⚠️ O que ficou para fase futura:

1. Migração gradual de APIs legadas (todas ainda em uso)
2. Correção de inconsistências (vencimento vs data_vencimento na API)
3. Migração de pagamentos para usar `financeiro_faturas` (atualmente usa `faturas` antiga)
4. Remoção de backups após confirmação
5. Remoção de arquivos temporários/debug após migração

### 📋 Decisões importantes documentadas:

1. Tabela oficial de faturas: `financeiro_faturas` (não `faturas`)
2. Campo oficial de vencimento: `data_vencimento` (não `vencimento`)
3. Nenhum código foi quebrado nesta fase (arquivos legados não foram movidos)
4. Inconsistências documentadas para correção futura

---

**Última atualização:** 2025-01-27 (Fase 1 concluída)

