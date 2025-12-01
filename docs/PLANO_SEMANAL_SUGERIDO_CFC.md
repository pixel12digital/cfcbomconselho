# PLANO SEMANAL SUGERIDO - CFC BOM CONSELHO

**Data:** 2025-01-27  
**Baseado em:** `docs/AUDITORIA_CFC_ATUALIZADA_20250127.md`

---

## 🎯 Bloco "Estabilização Crítica" (P1)

### Itens P1 Pendentes (5 itens)

| Ordem | ID | Título | Esforço | Quick Win? | Justificativa |
|---|---|---|---|---|---|
| 1 | BUG-ALUNOS-STATUS-01 | Corrigir status no modal | Alto | ❌ | Bloqueia funcionalidade principal - usuários não conseguem alterar status |
| 2 | FUNC-FINANCEIRO-01 | CRUD de despesas | Médio | ✅ | API já existe, falta apenas frontend - impacto rápido |
| 3 | PWA-ISSUE-04 | Remover logs de performance | Baixo | ✅ | Rápido de fazer, melhora experiência imediata |
| 4 | PWA-ISSUE-01 | Revisar cache | Médio | ❌ | Impacta performance, mas requer testes |
| 5 | TECH-ALUNOS-JS-01 | Refatorar alunos.php | Alto | ❌ | Facilita manutenção futura, mas é trabalho extenso |

---

## 📅 Sugestão de Fatiamento por Blocos de Trabalho

### Semana 1 - Foco: Bugs Críticos

#### Bloco 1 (2h) - BUG-ALUNOS-STATUS-01 - Investigação
- Verificar se há múltiplos elementos com `id="status"` no DOM
- Adicionar MutationObserver para monitorar mudanças
- Identificar causa raiz do problema
- **Resultado esperado:** Causa identificada, solução definida

#### Bloco 2 (2h) - BUG-ALUNOS-STATUS-01 - Correção
- Implementar seletor mais específico (`formAluno.querySelector`)
- Corrigir leitura do status
- Remover logs de debug temporários
- **Resultado esperado:** Status atualiza corretamente no modal

#### Bloco 3 (1h) - PWA-ISSUE-04 - Quick Win
- Condicionar logs de `performance-metrics.js` a ambiente
- Remover logs excessivos de CLS
- Testar em desenvolvimento e produção
- **Resultado esperado:** Logs limpos em produção

#### Bloco 4 (2h) - FUNC-FINANCEIRO-01 - Modal Nova Despesa
- Criar modal de nova despesa (similar ao de faturas)
- Implementar chamada à API POST
- Atualizar listagem após criação
- **Resultado esperado:** Usuário consegue criar nova despesa

---

### Semana 2 - Foco: Completar Funcionalidades Críticas

#### Bloco 5 (2h) - FUNC-FINANCEIRO-01 - Visualização e Ações
- Criar modal de visualização de despesa
- Implementar botão "Marcar como Paga" (PUT)
- Implementar botão "Cancelar" (DELETE)
- **Resultado esperado:** CRUD completo de despesas funcional

#### Bloco 6 (1h) - BUG-ALUNOS-STATUS-01 - Validação
- Testar correção em diferentes cenários
- Validar em produção
- Documentar solução
- **Resultado esperado:** Bug confirmado como resolvido

#### Bloco 7 (2h) - PWA-ISSUE-01 - Revisão de Cache
- Mudar estratégia para Network First em páginas dinâmicas
- Manter Cache First para recursos estáticos
- Testar CLS e performance
- **Resultado esperado:** CLS reduzido, performance mantida

---

### Semana 3-4 - Foco: Refatoração Crítica

#### Bloco 8 (4h) - TECH-ALUNOS-JS-01 - Extrair JavaScript
- Extrair funções principais para `alunos-modal.js`
- Extrair funções de listagem para `alunos-listagem.js`
- Atualizar referências no HTML
- Testar funcionalidades
- **Resultado esperado:** JavaScript separado, arquivo alunos.php reduzido

#### Bloco 9 (2h) - TECH-ALUNOS-JS-01 - Separar HTML
- Extrair HTML do modal para template/componente
- Manter apenas lógica PHP no arquivo principal
- Testar renderização
- **Resultado esperado:** HTML separado, código mais limpo

---

## 🎯 Bloco "Próximos Passos Importantes" (P2)

### Top 5 Itens P2 para Atacar Após P1

1. **BUG-ALUNOS-MATRICULA-01 / FUNC-ALUNOS-MATRICULA-01** (Médio)
   - Verificar integração de matrícula no backend
   - Completar funcionalidade já iniciada

2. **FUNC-AGENDA-01** (Médio)
   - Criar página aulas-praticas.php
   - Remover marcação temporário do menu

3. **TECH-REPETICAO-MODAIS-01** (Alto)
   - Criar classe ModalManager
   - Refatorar modais existentes
   - Reduz duplicação de código

4. **TECH-REPETICAO-API-01** (Médio)
   - Criar classe APIClient
   - Centralizar lógica de requisições
   - Facilita manutenção futura

5. **PWA-ISSUE-02** (Médio)
   - Testar e corrigir fluxo de atualização do Service Worker
   - Melhora experiência PWA

---

## 📊 Resumo de Esforço por Semana

### Semana 1 (7h)
- BUG-ALUNOS-STATUS-01: 4h (investigação + correção)
- PWA-ISSUE-04: 1h (quick win)
- FUNC-FINANCEIRO-01: 2h (início)

### Semana 2 (5h)
- FUNC-FINANCEIRO-01: 2h (completar)
- BUG-ALUNOS-STATUS-01: 1h (validação)
- PWA-ISSUE-01: 2h (revisão cache)

### Semana 3-4 (6h)
- TECH-ALUNOS-JS-01: 6h (refatoração)

**Total P1:** ~18 horas

---

## 🎯 Sugestão de Distribuição na Agenda Semanal

### Cenário: 3 blocos por semana (FUTURO / CLIENTES / COMERCIAL)

**Semana 1:**
- **FUTURO (2h):** Bloco 1 - BUG-ALUNOS-STATUS-01 (investigação)
- **CLIENTES (2h):** Bloco 2 - BUG-ALUNOS-STATUS-01 (correção)
- **COMERCIAL (1h):** Bloco 3 - PWA-ISSUE-04 (quick win)

**Semana 2:**
- **FUTURO (2h):** Bloco 4 - FUNC-FINANCEIRO-01 (modal nova despesa)
- **CLIENTES (2h):** Bloco 5 - FUNC-FINANCEIRO-01 (visualização e ações)
- **COMERCIAL (1h):** Bloco 6 - BUG-ALUNOS-STATUS-01 (validação)

**Semana 3:**
- **FUTURO (2h):** Bloco 7 - PWA-ISSUE-01 (revisão cache)
- **CLIENTES (2h):** Bloco 8 - TECH-ALUNOS-JS-01 (extrair JS - parte 1)
- **COMERCIAL (2h):** Bloco 8 - TECH-ALUNOS-JS-01 (extrair JS - parte 2)

**Semana 4:**
- **FUTURO (2h):** Bloco 9 - TECH-ALUNOS-JS-01 (separar HTML - parte 1)
- **CLIENTES (2h):** Bloco 9 - TECH-ALUNOS-JS-01 (separar HTML - parte 2)
- **COMERCIAL (2h):** Iniciar P2 (BUG-ALUNOS-MATRICULA-01 ou FUNC-AGENDA-01)

---

## ✅ Quick Wins (Baixo Esforço, Alto Impacto)

1. **PWA-ISSUE-04** (1h) - Remover logs de performance
2. **FUNC-FINANCEIRO-01** (4h total) - API já existe, falta frontend
3. **BUG-DEBUG-LOGS-01** (1h) - Remover logs temporários (P3, mas rápido)

---

## 📝 Notas Importantes

- **BUG-ALUNOS-STATUS-01** deve ser prioridade máxima - bloqueia funcionalidade principal
- **FUNC-FINANCEIRO-01** é quick win - API pronta, falta apenas frontend
- **TECH-ALUNOS-JS-01** pode ser feito em paralelo com outras tarefas menores
- Após concluir P1, focar nos top 5 P2 listados acima

---

**Última atualização:** 2025-01-27

