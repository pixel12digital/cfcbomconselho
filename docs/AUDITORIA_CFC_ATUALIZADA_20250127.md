# AUDITORIA CFC BOM CONSELHO - ATUALIZADA

**Data da Auditoria:** 2025-01-27  
**Baseada em:** `docs/AUDITORIA_GERAL_CFC_BOM_CONSELHO.md`  
**Verificação:** Estado real do código

---

## 📊 Resumo Executivo

### Status dos Itens

- **CONCLUÍDO:** 0 itens
- **PARCIAL:** 3 itens
- **NÃO INICIADO:** 30 itens
- **Total verificado:** 33 itens

### 5 Itens Mais Críticos para Produção

1. **BUG-ALUNOS-STATUS-01** (P1) - Status não atualiza no modal - **NÃO INICIADO** - Bloqueia funcionalidade principal
2. **FUNC-FINANCEIRO-01** (P1) - CRUD de despesas incompleto - **NÃO INICIADO** - Funcionalidade parcialmente implementada
3. **TECH-ALUNOS-JS-01** (P1) - Arquivo alunos.php com 10.972 linhas - **NÃO INICIADO** - Dificulta manutenção
4. **PWA-ISSUE-04** (P1) - Logs excessivos de performance - **NÃO INICIADO** - Impacta experiência do usuário
5. **BUG-ALUNOS-MATRICULA-01** (P2) - Campos de matrícula não integrados - **NÃO INICIADO** - Funcionalidade incompleta

---

## 📋 Tabela Geral - Visão de Lista

| ID | Módulo | Tipo | Prioridade | Status Atual | O que falta |
|---|---|---|---|---|---|
| BUG-ALUNOS-STATUS-01 | Alunos | BUG | P1 | NÃO INICIADO | Corrigir leitura do select #status no modal - valor sempre "ativo" ao salvar |
| BUG-ALUNOS-FOTO-01 | Alunos | BUG | P2 | PARCIAL | Verificar se ainda há literais `${fotoUrl}` - código parece corrigido mas precisa validação |
| BUG-ALUNOS-MODAL-01 | Alunos | BUG | P2 | PARCIAL | Validação em produção - correções aplicadas mas não testadas |
| BUG-ALUNOS-MATRICULA-01 | Alunos | BUG | P2 | NÃO INICIADO | Integrar campos de matrícula no backend (TODO linha 2606) |
| BUG-FINANCEIRO-DESPESAS-01 | Financeiro | BUG | P1 | NÃO INICIADO | Implementar funções: novaDespesa(), visualizarDespesa(), marcarComoPaga(), cancelarDespesa() |
| BUG-PWA-UPDATE-01 | PWA | BUG | P2 | NÃO INICIADO | Testar e corrigir fluxo de atualização do Service Worker |
| BUG-PERFORMANCE-LOGS-01 | Performance | BUG | P3 | NÃO INICIADO | Remover/reduzir logs de CLS em performance-metrics.js (linha 129) |
| BUG-DEBUG-LOGS-01 | Geral | BUG | P3 | NÃO INICIADO | Remover logs temporários: alunos.php (linhas 56-57, 7402-7406), alunos.js (múltiplos) |
| FUNC-AGENDA-01 | Acadêmico | FUNC | P2 | NÃO INICIADO | Criar página aulas-praticas.php (menu ainda marca como temporário) |
| FUNC-FINANCEIRO-01 | Financeiro | FUNC | P1 | NÃO INICIADO | Implementar CRUD completo de despesas (API existe, falta frontend) |
| FUNC-FINANCEIRO-02 | Financeiro | FUNC | P3 | NÃO INICIADO | Criar página financeiro-configuracoes.php |
| FUNC-RELATORIOS-01 | Relatórios | FUNC | P2 | NÃO INICIADO | Criar relatorio-conclusao-pratica.php |
| FUNC-RELATORIOS-02 | Relatórios | FUNC | P2 | NÃO INICIADO | Criar relatorio-provas.php |
| FUNC-CONFIG-01 | Configurações | FUNC | P2 | NÃO INICIADO | Criar configuracoes-horarios.php |
| FUNC-CONFIG-02 | Configurações | FUNC | P2 | NÃO INICIADO | Criar configuracoes-bloqueios.php |
| FUNC-CONFIG-03 | Configurações | FUNC | P3 | NÃO INICIADO | Criar configuracoes-documentos.php |
| FUNC-CONFIG-04 | Configurações | FUNC | P3 | NÃO INICIADO | Verificar/criar configurações gerais |
| FUNC-SISTEMA-01 | Sistema | FUNC | P3 | NÃO INICIADO | Criar faq.php |
| FUNC-SISTEMA-02 | Sistema | FUNC | P3 | NÃO INICIADO | Criar sistema de tickets/suporte |
| FUNC-SISTEMA-03 | Sistema | FUNC | P3 | NÃO INICIADO | Criar backup.php |
| FUNC-ALUNOS-MATRICULA-01 | Alunos | FUNC | P2 | NÃO INICIADO | Integrar campos de matrícula no backend (mesmo que BUG-ALUNOS-MATRICULA-01) |
| TECH-ALUNOS-JS-01 | Alunos | TECH | P1 | NÃO INICIADO | Refatorar alunos.php (10.972 linhas) - extrair JS e HTML |
| TECH-INSTRUTORES-DUPLICACAO-01 | Instrutores | TECH | P3 | NÃO INICIADO | Consolidar instrutores.php e instrutores-otimizado.php |
| TECH-USUARIOS-DUPLICACAO-01 | Usuários | TECH | P3 | NÃO INICIADO | Consolidar usuarios.php e usuarios_simples.php |
| TECH-FINANCEIRO-STANDALONE-01 | Financeiro | TECH | P3 | NÃO INICIADO | Verificar/remover arquivos *-standalone.php |
| TECH-API-DUPLICACAO-01 | Geral | TECH | P2 | NÃO INICIADO | Consolidar APIs duplicadas (instrutores, salas, disciplinas) |
| TECH-REPETICAO-MODAIS-01 | Geral | TECH | P2 | NÃO INICIADO | Criar classe ModalManager reutilizável |
| TECH-REPETICAO-VALIDACAO-01 | Geral | TECH | P2 | NÃO INICIADO | Criar módulo validators.js reutilizável |
| TECH-REPETICAO-API-01 | Geral | TECH | P2 | NÃO INICIADO | Criar classe APIClient reutilizável |
| TECH-DEBUG-LOGS-01 | Geral | TECH | P3 | NÃO INICIADO | Criar sistema de logging condicional |
| TECH-ESTRUTURA-DOCS-01 | Docs | TECH | P3 | NÃO INICIADO | Organizar estrutura de documentação |
| PWA-ISSUE-01 | PWA | PWA | P1 | NÃO INICIADO | Revisar estratégia de cache (Network First para páginas dinâmicas) |
| PWA-ISSUE-02 | PWA | PWA | P2 | NÃO INICIADO | Garantir fluxo de atualização do Service Worker |
| PWA-ISSUE-03 | PWA | PWA | P2 | NÃO INICIADO | Implementar geração automática de versão do SW |
| PWA-ISSUE-04 | PWA | PWA | P1 | NÃO INICIADO | Remover logs excessivos de performance em produção |
| PWA-ISSUE-05 | PWA | PWA | P2 | NÃO INICIADO | Adicionar headers de segurança (CSP, HSTS, etc.) |

---

## 🔴 P1 – Itens Críticos

### BUG-ALUNOS-STATUS-01
**Título:** Status do aluno não atualiza no modal de edição

**Arquivos envolvidos:**
- `admin/pages/alunos.php` (linhas 7398-7406: leitura do status, logs de debug)
- `admin/pages/alunos.php` (linha 2252: HTML do select #status)
- `admin/api/alunos.php` (API de atualização)

**Situação real hoje:**
- Código lê status diretamente do select: `const statusSelect = document.getElementById('status'); const status = statusSelect ? statusSelect.value : ...`
- Há logs de debug ainda presentes (linhas 7402-7406)
- Problema documentado em `docs/INVESTIGACAO_PERSISTENCIA_STATUS_MODAL.md`
- Possíveis causas: select sendo resetado após preenchimento, múltiplos elementos com id="status", problema de timing

**Passos objetivos para concluir:**
- Verificar se há múltiplos elementos com `id="status"` no DOM
- Usar seletor mais específico: `formAluno.querySelector('select[name="status"]')`
- Adicionar MutationObserver para monitorar mudanças no select
- Remover logs de debug temporários após correção
- Testar em produção

---

### FUNC-FINANCEIRO-01
**Título:** Implementar CRUD completo de despesas

**Arquivos envolvidos:**
- `admin/pages/financeiro-despesas.php` (linhas 296-314: funções com alert)
- `admin/api/despesas.php` (API funcional com GET, POST, PUT, DELETE)

**Situação real hoje:**
- API `despesas.php` está completa e funcional (suporta GET, POST, PUT, DELETE)
- Frontend tem apenas placeholders: `novaDespesa()`, `visualizarDespesa(id)`, `marcarComoPaga(id)`, `cancelarDespesa(id)` retornam apenas `alert()`
- Botões na interface chamam essas funções mas não fazem nada útil

**Passos objetivos para concluir:**
- Criar modal de nova despesa (similar ao de faturas)
- Criar modal de visualização de despesa
- Implementar chamadas à API `despesas.php` para criar/atualizar
- Implementar botões de ação (marcar como paga via PUT, cancelar via DELETE)
- Atualizar listagem após ações (recarregar dados)

---

### TECH-ALUNOS-JS-01
**Título:** Refatorar alunos.php (10.972 linhas)

**Arquivos envolvidos:**
- `admin/pages/alunos.php` (10.972 linhas - PHP + HTML + JS inline misturados)

**Situação real hoje:**
- Arquivo extremamente grande com código misturado
- JavaScript inline extenso (funções como `saveAlunoDados`, `preencherFormularioAluno`, `saveAlunoMatricula`)
- HTML do modal inline
- Dificulta manutenção, debugging e performance

**Passos objetivos para concluir:**
- Extrair JavaScript para `admin/assets/js/alunos-modal.js` e `admin/assets/js/alunos-listagem.js`
- Separar HTML do modal em componente/template ou arquivo PHP separado
- Dividir lógica PHP em funções/classes em `admin/includes/` se necessário
- Testar funcionalidades após refatoração

---

### PWA-ISSUE-01
**Título:** Revisar estratégia de cache para evitar CLS alto

**Arquivos envolvidos:**
- `pwa/sw.js` (linhas 128-143: estratégias de cache)

**Situação real hoje:**
- Service Worker usa Cache First para App Shell e recursos estáticos
- Network First para APIs e páginas dinâmicas
- Pode estar causando CLS alto em telas dinâmicas (alunos, agenda)

**Passos objetivos para concluir:**
- Mudar estratégia de Cache First para Network First em páginas dinâmicas (alunos, agenda, exames)
- Manter Cache First apenas para recursos estáticos (CSS, JS, imagens)
- Testar CLS após mudança
- Validar performance

---

### PWA-ISSUE-04
**Título:** Remover logs excessivos de performance em produção

**Arquivos envolvidos:**
- `pwa/performance-metrics.js` (linha 129: log de CLS a cada 10 entradas)

**Situação real hoje:**
- Logs de CLS sendo exibidos a cada 10 entradas ou quando valor > 0.1
- Logs de performance em geral sendo exibidos no console
- Não há condicional baseado em ambiente

**Passos objetivos para concluir:**
- Condicionar logs de `performance-metrics.js` a variável de ambiente (desenvolvimento vs produção)
- Remover ou reduzir frequência de logs de CLS
- Manter apenas logs críticos em produção

---

## 🟡 P2 – Itens Importantes

### BUG-ALUNOS-FOTO-01
**Arquivos:** `admin/pages/alunos.php` (linhas 5016-5025)  
**Situação:** Código parece corrigido (usa template literal correto), mas precisa validação. Há validação na linha 11330 que verifica se não há `${fotoUrl}` literal.  
**Falta:** Testar em produção e verificar se não há outros locais com problema.

---

### BUG-ALUNOS-MATRICULA-01 / FUNC-ALUNOS-MATRICULA-01
**Arquivos:** `admin/pages/alunos.php` (linha 2606: TODO, linha 7654: função saveAlunoMatricula existe)  
**Situação:** Função `saveAlunoMatricula()` existe e está implementada, mas comentário na linha 2606 indica que campos de matrícula não estão integrados no backend.  
**Falta:** Verificar se API `alunos.php` aceita campos de matrícula, atualizar se necessário, testar persistência.

---

### FUNC-AGENDA-01
**Arquivos:** `admin/index.php` (linha 1572: TODO), `admin/pages/listar-aulas.php`  
**Situação:** Menu marca como "(Temporário)" e redireciona para `listar-aulas.php`.  
**Falta:** Criar `admin/pages/aulas-praticas.php`, atualizar menu, remover marcação temporário.

---

### FUNC-RELATORIOS-01 e FUNC-RELATORIOS-02
**Situação:** Menu com `onclick="alert('Relatório em desenvolvimento')"`.  
**Falta:** Criar páginas `relatorio-conclusao-pratica.php` e `relatorio-provas.php`, implementar queries e interfaces.

---

### FUNC-CONFIG-01 e FUNC-CONFIG-02
**Situação:** Menu com `onclick="alert('Página em desenvolvimento')"`.  
**Falta:** Criar `configuracoes-horarios.php` e `configuracoes-bloqueios.php`, implementar CRUD e interfaces.

---

### TECH-REPETICAO-MODAIS-01, TECH-REPETICAO-VALIDACAO-01, TECH-REPETICAO-API-01
**Situação:** Lógica repetida em vários arquivos.  
**Falta:** Criar módulos reutilizáveis (`ModalManager`, `validators.js`, `APIClient`), refatorar código existente.

---

### TECH-API-DUPLICACAO-01
**Situação:** Múltiplas APIs com nomes similares (instrutores, salas, disciplinas).  
**Falta:** Identificar qual API está sendo usada, consolidar, remover não utilizadas.

---

### PWA-ISSUE-02, PWA-ISSUE-03, PWA-ISSUE-05
**Situação:** Fluxo de atualização não testado, versão hardcoded, headers de segurança ausentes.  
**Falta:** Testar atualização, implementar geração automática de versão, adicionar headers.

---

## 🟢 P3 – Melhorias / Estética

### BUG-DEBUG-LOGS-01 e BUG-PERFORMANCE-LOGS-01
**Falta:** Remover logs temporários, condicionar logs de performance.

---

### FUNC-FINANCEIRO-02, FUNC-CONFIG-03, FUNC-CONFIG-04, FUNC-SISTEMA-01, FUNC-SISTEMA-02, FUNC-SISTEMA-03
**Falta:** Criar páginas conforme necessidade do negócio.

---

### TECH-INSTRUTORES-DUPLICACAO-01, TECH-USUARIOS-DUPLICACAO-01, TECH-FINANCEIRO-STANDALONE-01
**Falta:** Verificar qual versão está ativa, consolidar, remover não utilizadas.

---

### TECH-DEBUG-LOGS-01, TECH-ESTRUTURA-DOCS-01
**Falta:** Criar sistema de logging condicional, organizar documentação em pastas.

---

**Última atualização:** 2025-01-27  
**Próxima revisão:** Após conclusão dos itens P1

