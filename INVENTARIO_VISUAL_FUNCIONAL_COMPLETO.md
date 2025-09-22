# INVENTÁRIO VISUAL & FUNCIONAL COMPLETO (TEXTUAL)
## Sistema CFC - Bom Conselho

---

## 1) MAPA DO MENU (SIDEBAR/TOPBAR) — ORDEM E VISIBILIDADE

### Estrutura Completa em Árvore do Menu:

```
📊 Dashboard
   └── /admin/index.php (fa-chart-line)

📁 Cadastros (admin/secretaria)
   ├── 👥 Usuários (admin) → /admin/index.php?page=usuarios&action=list (fa-users)
   ├── 🏢 CFCs (admin) → /admin/index.php?page=cfcs&action=list (sem ícone)
   ├── 🎓 Alunos → /admin/pages/alunos.php (fa-graduation-cap) [badge: total_alunos]
   ├── 👨‍🏫 Instrutores → /admin/pages/instrutores.php (fa-chalkboard-teacher) [badge: total_instrutores]
   └── 🚗 Veículos → /admin/pages/veiculos.php (fa-car) [badge: total_veiculos]

📅 Operacional
   └── 📅 Agendamento → /admin/index.php?page=agendamento (fa-calendar-alt) [badge: total_aulas]

🎓 Gestão de Turmas
   └── /admin/pages/turmas.php (fa-graduation-cap)

💰 Financeiro (FINANCEIRO_ENABLED=true, admin/secretaria)
   ├── 📄 Faturas (Receitas) → /admin/pages/financeiro-faturas.php (fa-file-invoice)
   ├── 🧾 Despesas (Pagamentos) → /admin/pages/financeiro-despesas.php (fa-receipt)
   └── 📊 Relatórios → /admin/pages/financeiro-relatorios.php (fa-chart-line)

📊 Relatórios Gerais (admin/secretaria)
   ├── 📋 Relatório de Matrículas → /admin/pages/relatorio-matriculas.php (fa-graduation-cap)
   ├── ✅ Relatório de Frequência → /admin/pages/relatorio-frequencia.php (fa-calendar-check)
   ├── 👤 Relatório de Presenças → /admin/pages/relatorio-presencas.php (fa-user-check)
   └── 📄 Relatório de ATA → /admin/pages/relatorio-ata.php (fa-file-alt)

⚙️ Configurações (admin)
   ├── 🏷️ Categorias de Habilitação → /admin/index.php?page=configuracoes-categorias (fa-layer-group)
   ├── 🎛️ Configurações Gerais → /admin/index.php?page=configuracoes&action=geral (fa-sliders-h)
   ├── 📋 Logs do Sistema → /admin/index.php?page=logs&action=list (fa-file-alt)
   └── 💾 Backup → /admin/index.php?page=backup (fa-download)

🔧 Ferramentas (admin)
   ├── 🧪 Teste Regras → /admin/test-novas-regras-agendamento.php (fa-flask) [badge: 🧪]
   ├── 📋 Teste Simulado → /admin/teste-producao-completo.php (fa-vial) [badge: 📋]
   └── 🚀 Teste Real → /admin/teste-producao-real.php (fa-rocket) [badge: 🚀]

🚪 Sair
   └── /admin/logout.php (fa-sign-out-alt)
```

### Topbar (Ordem da Esquerda para Direita):

```
🔔 Notificações (fa-bell) [badge: notification-badge]
👤 Perfil do Usuário
   ├── 👤 Meu Perfil → /admin/index.php?page=profile (fa-user)
   ├── 🔑 Trocar senha → /admin/index.php?page=change-password (fa-key)
   └── 🚪 Sair → /admin/logout.php (fa-sign-out-alt)
```

### Itens Ocultos + Razão:

- **Turmas Teóricas (legado):** `turma-dashboard.php`, `turma-calendario.php`, `turma-matriculas.php`, `turma-configuracoes.php`, `turma-templates.php`, `turma-grade-generator.php` - Substituídos por `turmas.php`
- **Financeiro:** Oculto se `FINANCEIRO_ENABLED=false` ou usuário não é admin/secretaria
- **Ferramentas:** Oculto se usuário não é admin
- **Configurações:** Oculto se usuário não é admin
- **Usuários/CFCs:** Oculto se usuário não é admin

---

## 2) TELAS/ROTAS — UMA SEÇÃO POR PÁGINA

### 2.1) DASHBOARD

**URL completa:** `/admin/index.php` (sem parâmetros)

**Perfis que acessam:** admin ✅, secretaria ✅, instrutor ✅, aluno ❌

**Breadcrumbs:** Não há

**Título da página:** "Dashboard"

**Descrição/subtítulo:** Não há

**Ícone do título:** `fa-chart-line`

**Seções/Cartões (ordem na tela):**

1. **Cards de Estatísticas (4 colunas):**
   - **Total de Alunos:** `stat-card primary` (fa-graduation-cap) - número formatado + "+5%"
   - **Total de Instrutores:** `stat-card success` (fa-chalkboard-teacher) - número formatado + "+8%"
   - **Total de Veículos:** `stat-card info` (fa-car) - número formatado + "+5%"
   - **Aulas Hoje:** `stat-card success` (fa-calendar-day) - número formatado + "+15%"
   - **Aulas Esta Semana:** `stat-card primary` (fa-calendar-week) - número formatado + "+22%"

2. **Módulo: Indicadores por Fases:**
   - **Título:** "Indicadores por Fases do Processo"
   - **Subtítulo:** "Acompanhamento do progresso dos alunos em cada etapa"
   - **Grid de 8 cards:** Cadastro, Confirmação, Exames Aptidão, Curso Teórico, Aulas Práticas, Prova Prática, CNH, CNH Retirada

3. **Módulo: Volume de Vendas:**
   - **Título:** "Volume de Vendas por Mês"
   - **Gráfico:** Chart.js com dados dos últimos 12 meses

4. **Módulo: Ocupação da Agenda:**
   - **Título:** "Ocupação da Agenda"
   - **Gráfico:** Chart.js com dados de ocupação

**Filtros/Buscar:** Não há

**Ações de topo:** Não há

**Tabela/Lista:** Não há

**Modais/Drawers:** Não há

**Toasts/Alerts:** Sistema de notificações global

**Estados especiais:**
- **Loading:** Skeleton cards com animação fadeIn
- **Empty state:** Não aplicável (sempre mostra dados)
- **Erro:** Toast de erro com ícone `fa-exclamation-triangle`

**Navegação contextual:** Links para módulos específicos nos cards

**APIs chamadas:** Não há APIs específicas (dados carregados no PHP)

**Responsividade:** 
- **xs/sm:** Cards empilham em 1 coluna
- **md:** Cards em 2 colunas
- **lg/xl:** Cards em 4 colunas

**Acessibilidade:** Cards com `aria-label`, foco visível, ordem TAB lógica

**Internacionalização:** Números formatados com separadores brasileiros, datas em DD/MM/YYYY

---

### 2.2) ALUNOS

**URL completa:** `/admin/pages/alunos.php` (sem parâmetros)

**Perfis que acessam:** admin ✅, secretaria ✅, instrutor ✅, aluno ❌

**Breadcrumbs:** Não há

**Título da página:** "Gestão de Alunos"

**Descrição/subtítulo:** Não há

**Ícone do título:** `fa-graduation-cap`

**Seções/Cartões (ordem na tela):**

1. **Cards de Estatísticas (4 colunas):**
   - **Total de Alunos:** `card border-left-primary` (fa-graduation-cap) - número formatado
   - **Alunos Ativos:** `card border-left-success` (fa-user-check) - número formatado
   - **Alunos Inativos:** `card border-left-warning` (fa-user-times) - número formatado
   - **Novos Este Mês:** `card border-left-info` (fa-user-plus) - número formatado

2. **Filtros/Buscar:**
   - **Campo de busca:** `form-control` com placeholder "Buscar por nome, CPF ou telefone..."
   - **Filtro por CFC:** `select` com opções dos CFCs
   - **Filtro por Status:** `select` com opções (Todos, Ativo, Inativo)
   - **Botão Limpar:** `btn btn-outline-secondary` (fa-times)

3. **Ações de topo:**
   - **Novo Aluno:** `btn btn-primary` (fa-plus) - abre modal

4. **Tabela/Lista:**
   - **Colunas (ordem):** Avatar, Nome, CPF, Telefone, CFC, Status, Ações
   - **Avatar:** `avatar-sm` com inicial do nome
   - **Status:** `badge bg-success` (Ativo) ou `badge bg-secondary` (Inativo)
   - **Ações por linha:** 
     - **Editar:** `btn btn-sm btn-outline-primary` (fa-edit)
     - **Histórico:** `btn btn-sm btn-outline-info` (fa-history)
     - **Financeiro:** `btn btn-sm btn-outline-success` (fa-dollar-sign)
     - **Excluir:** `btn btn-sm btn-outline-danger` (fa-trash)

5. **Modais:**
   - **Modal Novo/Editar Aluno:**
     - **Gatilho:** Botão "Novo Aluno" ou "Editar"
     - **Título:** "Cadastrar Novo Aluno" ou "Editar Aluno"
     - **Campos (ordem):**
       - Nome (obrigatório, text)
       - CPF (obrigatório, máscara CPF)
       - Telefone (obrigatório, máscara telefone)
       - E-mail (opcional, validação e-mail)
       - Data de Nascimento (obrigatório, date)
       - CFC (obrigatório, select)
       - Endereço (opcional, textarea)
       - Observações (opcional, textarea)
     - **Botões:** "Salvar" (primário), "Cancelar" (secundário)

**Estados especiais:**
- **Loading:** Spinner no centro da tabela
- **Empty state:** Ícone `fa-inbox fa-3x`, texto "Nenhum aluno cadastrado ainda", botão "Cadastrar Primeiro Aluno"
- **Erro:** Toast de erro com mensagem específica

**Navegação contextual:**
- **Histórico:** `/admin/pages/historico-aluno.php?id={aluno_id}`
- **Financeiro:** `/admin/pages/financeiro-faturas.php?aluno_id={aluno_id}`

**APIs chamadas:**
- **GET** `/admin/api/alunos.php` - Listar alunos
- **POST** `/admin/api/alunos.php` - Criar aluno
- **PUT** `/admin/api/alunos.php?id={id}` - Editar aluno
- **DELETE** `/admin/api/alunos.php?id={id}` - Excluir aluno

**Responsividade:**
- **xs/sm:** Tabela com scroll horizontal, cards empilham
- **md/lg:** Layout normal

**Acessibilidade:** Tabela com `aria-label`, botões com `title`, foco visível

**Internacionalização:** CPF com máscara XXX.XXX.XXX-XX, telefone com máscara (XX) XXXXX-XXXX

---

### 2.3) FINANCEIRO - FATURAS

**URL completa:** `/admin/pages/financeiro-faturas.php` (sem parâmetros)

**Perfis que acessam:** admin ✅, secretaria ✅, instrutor ❌, aluno ❌

**Breadcrumbs:** Não há

**Título da página:** "Faturas (Receitas)"

**Descrição/subtítulo:** Não há

**Ícone do título:** `fa-file-invoice`

**Seções/Cartões (ordem na tela):**

1. **Cards de Estatísticas (4 colunas):**
   - **Total de Faturas:** `stats-card` (fa-file-invoice) - número formatado
   - **Faturas Abertas:** `stats-card warning` (fa-clock) - número formatado
   - **Faturas Pagas:** `stats-card success` (fa-check-circle) - número formatado
   - **Faturas Vencidas:** `stats-card info` (fa-exclamation-triangle) - número formatado

2. **Filtros/Buscar:**
   - **Período:** `input[type="date"]` (data início e fim)
   - **Status:** `select` com opções (Todos, Aberta, Paga, Vencida, Parcial, Cancelada)
   - **Aluno:** `input[type="text"]` com placeholder "Buscar por aluno..."
   - **Matrícula:** `input[type="text"]` com placeholder "Buscar por matrícula..."

3. **Ações de topo:**
   - **Nova Fatura:** `btn btn-primary` (fa-plus) - abre modal

4. **Tabela/Lista:**
   - **Colunas (ordem):** Número, Aluno, Matrícula, Vencimento, Valor, Status, Ações
   - **Status:** `status-badge` com classes:
     - `status-aberta` (Aberta)
     - `status-paga` (Paga)
     - `status-vencida` (Vencida)
     - `status-parcial` (Parcial)
     - `status-cancelada` (Cancelada)
   - **Ações por linha:**
     - **Registrar Pagamento:** `btn btn-sm btn-success` (fa-money-bill-wave)
     - **Editar:** `btn btn-sm btn-outline-primary` (fa-edit)
     - **Cancelar:** `btn btn-sm btn-outline-danger` (fa-times)
     - **Histórico:** `btn btn-sm btn-outline-info` (fa-history)

5. **Modais:**
   - **Modal Nova Fatura:**
     - **Gatilho:** Botão "Nova Fatura"
     - **Título:** "Nova Fatura"
     - **Campos (ordem):**
       - Matrícula (obrigatório, select)
       - Aluno (automático, readonly)
       - Descrição (obrigatório, text)
       - Valor (obrigatório, number, máscara moeda)
       - Desconto (opcional, number, máscara moeda)
       - Acréscimo (opcional, number, máscara moeda)
       - Vencimento (obrigatório, date)
       - Meio de Pagamento (obrigatório, select)
     - **Botões:** "Salvar" (primário), "Cancelar" (secundário)

   - **Modal Registrar Pagamento:**
     - **Gatilho:** Botão "Registrar Pagamento"
     - **Título:** "Registrar Pagamento"
     - **Campos (ordem):**
       - Data do Pagamento (obrigatório, date)
       - Valor Pago (obrigatório, number, máscara moeda)
       - Método (obrigatório, select)
       - Comprovante (opcional, file)
       - Observações (opcional, textarea)
     - **Botões:** "Registrar" (primário), "Cancelar" (secundário)

**Estados especiais:**
- **Loading:** Spinner no centro da tabela
- **Empty state:** Ícone `fa-file-invoice fa-3x`, texto "Nenhuma fatura encontrada", botão "Nova Fatura"
- **Erro:** Toast de erro com mensagem específica

**Navegação contextual:**
- **Aluno:** `/admin/pages/historico-aluno.php?id={aluno_id}`
- **Matrícula:** Contexto preservado

**APIs chamadas:**
- **GET** `/admin/api/faturas.php` - Listar faturas
- **POST** `/admin/api/faturas.php` - Criar fatura
- **PUT** `/admin/api/faturas.php?id={id}` - Editar fatura
- **DELETE** `/admin/api/faturas.php?id={id}` - Cancelar fatura
- **POST** `/admin/api/pagamentos.php` - Registrar pagamento

**Responsividade:**
- **xs/sm:** Tabela com scroll horizontal, cards empilham
- **md/lg:** Layout normal

**Acessibilidade:** Tabela com `aria-label`, botões com `title`, foco visível

**Internacionalização:** Valores em R$ 1.234,56, datas em DD/MM/YYYY

---

### 2.4) FINANCEIRO - DESPESAS

**URL completa:** `/admin/pages/financeiro-despesas.php` (sem parâmetros)

**Perfis que acessam:** admin ✅, secretaria ✅, instrutor ❌, aluno ❌

**Breadcrumbs:** Não há

**Título da página:** "Despesas (Contas a Pagar)"

**Descrição/subtítulo:** Não há

**Ícone do título:** `fa-receipt`

**Seções/Cartões (ordem na tela):**

1. **Cards de Estatísticas (4 colunas):**
   - **Total de Despesas:** `stats-card` (fa-receipt) - número formatado
   - **Despesas Pagas:** `stats-card success` (fa-check-circle) - número formatado
   - **Despesas Pendentes:** `stats-card warning` (fa-clock) - número formatado
   - **Despesas Vencidas:** `stats-card info` (fa-exclamation-triangle) - número formatado

2. **Filtros/Buscar:**
   - **Período:** `input[type="date"]` (data início e fim)
   - **Categoria:** `select` com opções (Todas, Combustível, Manutenção, Aluguel, Taxas, Salários, Outros)
   - **Status:** `select` com opções (Todos, Pago, Pendente, Vencido)
   - **Fornecedor:** `input[type="text"]` com placeholder "Buscar por fornecedor..."

3. **Ações de topo:**
   - **Nova Despesa:** `btn btn-primary` (fa-plus) - abre modal

4. **Tabela/Lista:**
   - **Colunas (ordem):** Título, Fornecedor, Categoria, Vencimento, Valor, Status, Ações
   - **Status:** `status-badge` com classes:
     - `status-pago` (Pago)
     - `status-pendente` (Pendente)
     - `status-vencido` (Vencido)
   - **Ações por linha:**
     - **Marcar Pago:** `btn btn-sm btn-success` (fa-check)
     - **Editar:** `btn btn-sm btn-outline-primary` (fa-edit)
     - **Excluir:** `btn btn-sm btn-outline-danger` (fa-trash)
     - **Anexo:** `btn btn-sm btn-outline-info` (fa-paperclip)

5. **Modais:**
   - **Modal Nova Despesa:**
     - **Gatilho:** Botão "Nova Despesa"
     - **Título:** "Nova Despesa"
     - **Campos (ordem):**
       - Título (obrigatório, text)
       - Fornecedor (opcional, text)
       - Categoria (obrigatório, select)
       - Valor (obrigatório, number, máscara moeda)
       - Vencimento (obrigatório, date)
       - Método de Pagamento (obrigatório, select)
       - Anexo (opcional, file)
       - Observações (opcional, textarea)
     - **Botões:** "Salvar" (primário), "Cancelar" (secundário)

**Estados especiais:**
- **Loading:** Spinner no centro da tabela
- **Empty state:** Ícone `fa-receipt fa-3x`, texto "Nenhuma despesa encontrada", botão "Nova Despesa"
- **Erro:** Toast de erro com mensagem específica

**Navegação contextual:** Não há

**APIs chamadas:**
- **GET** `/admin/api/despesas.php` - Listar despesas
- **POST** `/admin/api/despesas.php` - Criar despesa
- **PUT** `/admin/api/despesas.php?id={id}` - Editar despesa
- **DELETE** `/admin/api/despesas.php?id={id}` - Excluir despesa

**Responsividade:**
- **xs/sm:** Tabela com scroll horizontal, cards empilham
- **md/lg:** Layout normal

**Acessibilidade:** Tabela com `aria-label`, botões com `title`, foco visível

**Internacionalização:** Valores em R$ 1.234,56, datas em DD/MM/YYYY

---

### 2.5) FINANCEIRO - RELATÓRIOS

**URL completa:** `/admin/pages/financeiro-relatorios.php` (parâmetros: data_inicio, data_fim)

**Perfis que acessam:** admin ✅, secretaria ✅, instrutor ❌, aluno ❌

**Breadcrumbs:** Não há

**Título da página:** "Relatórios Financeiros"

**Descrição/subtítulo:** Não há

**Ícone do título:** `fa-chart-line`

**Seções/Cartões (ordem na tela):**

1. **Ações de topo:**
   - **Exportar CSV:** `btn btn-outline-success` (fa-file-csv)
   - **Imprimir:** `btn btn-outline-primary` (fa-print)

2. **Filtros/Buscar:**
   - **Período:** `input[type="date"]` (data início e fim)
   - **Aplicar Filtros:** `btn btn-primary` (fa-filter)

3. **Navegação por Abas:**
   - **Receitas:** `nav-link active` (fa-chart-line)
   - **Despesas:** `nav-link` (fa-receipt)
   - **Fluxo de Caixa:** `nav-link` (fa-exchange-alt)
   - **Inadimplência:** `nav-link` (fa-exclamation-triangle)

4. **Conteúdo das Abas:**
   - **Aba Receitas:**
     - **Gráfico:** Chart.js com dados de receitas por período
     - **Tabela:** Receitas pagas vs em aberto
   - **Aba Despesas:**
     - **Gráfico:** Chart.js com dados de despesas por categoria
     - **Tabela:** Despesas pagas vs pendentes
   - **Aba Fluxo de Caixa:**
     - **Gráfico:** Chart.js com fluxo de caixa
     - **Tabela:** Entradas vs saídas
   - **Aba Inadimplência:**
     - **Gráfico:** Chart.js com inadimplência por período
     - **Tabela:** Faturas vencidas por aluno

**Estados especiais:**
- **Loading:** Spinner no centro dos gráficos
- **Empty state:** Mensagem "Nenhum dado encontrado para o período"
- **Erro:** Toast de erro com mensagem específica

**Navegação contextual:** Não há

**APIs chamadas:**
- **GET** `/admin/api/faturas.php` - Dados de receitas
- **GET** `/admin/api/despesas.php` - Dados de despesas

**Responsividade:**
- **xs/sm:** Gráficos responsivos, tabelas com scroll
- **md/lg:** Layout normal

**Acessibilidade:** Gráficos com `aria-label`, tabelas com `aria-label`

**Internacionalização:** Valores em R$ 1.234,56, datas em DD/MM/YYYY

---

## 3) COMPONENTES GLOBAIS (DESIGN SYSTEM "DE FATO")

### Frameworks:
- **Bootstrap:** 5.3.0
- **Font Awesome:** 6.0.0
- **Chart.js:** Para gráficos
- **jQuery:** Para interações

### Tokens de Cores:
```css
--primary-color: #1e3a8a (Azul Marinho)
--primary-light: #3b82f6 (Azul Claro)
--primary-dark: #1e40af (Azul Escuro)
--secondary-color: #64748b (Cinza Azulado)
--accent-color: #0ea5e9 (Azul Ciano)
--success-color: #10b981 (Verde)
--warning-color: #f59e0b (Amarelo)
--danger-color: #ef4444 (Vermelho)
--info-color: #3b82f6 (Azul Info)
```

### Classes Utilitárias Mais Usadas:
- `bg-success`, `bg-warning`, `bg-danger`, `bg-info`
- `text-muted`, `text-primary`, `text-success`
- `shadow-sm`, `shadow-md`, `shadow-lg`
- `border-left-primary`, `border-left-success`
- `btn-primary`, `btn-secondary`, `btn-success`, `btn-warning`, `btn-danger`

### Componentes Recorrentes:

**Cards Padrão:**
```html
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-icon me-2"></i>Título</h5>
    </div>
    <div class="card-body">
        <!-- Conteúdo -->
    </div>
</div>
```

**Tabelas Padrão:**
```html
<div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>Coluna</th>
            </tr>
        </thead>
        <tbody>
            <!-- Dados -->
        </tbody>
    </table>
</div>
```

**Botões Padrão:**
- **Primário:** `btn btn-primary` (fa-plus, fa-save, fa-check)
- **Secundário:** `btn btn-secondary` (fa-times, fa-cancel)
- **Sucesso:** `btn btn-success` (fa-check-circle, fa-money-bill-wave)
- **Perigo:** `btn btn-danger` (fa-trash, fa-times)
- **Info:** `btn btn-info` (fa-eye, fa-history)

**Badges por Status:**
- **Ativo/Sucesso:** `badge bg-success`
- **Inativo/Neutro:** `badge bg-secondary`
- **Atenção:** `badge bg-warning`
- **Erro/Perigo:** `badge bg-danger`
- **Info:** `badge bg-info`

### Padrões de Ícones:
- **Editar:** `fa-edit`, `fa-pen`
- **Excluir:** `fa-trash`, `fa-times`
- **Visualizar:** `fa-eye`, `fa-search`
- **Adicionar:** `fa-plus`, `fa-user-plus`
- **Salvar:** `fa-save`, `fa-check`
- **Cancelar:** `fa-times`, `fa-arrow-left`
- **Exportar:** `fa-file-csv`, `fa-file-pdf`
- **Imprimir:** `fa-print`
- **Histórico:** `fa-history`, `fa-clock`
- **Financeiro:** `fa-dollar-sign`, `fa-money-bill-wave`

### Toasts/Alerts Padrão:
```html
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast" role="alert">
        <div class="toast-header">
            <i class="fas fa-icon me-2"></i>
            <strong class="me-auto">Título</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Mensagem
        </div>
    </div>
</div>
```

### Form Controls Padrão:
- **CPF:** Máscara XXX.XXX.XXX-XX
- **Telefone:** Máscara (XX) XXXXX-XXXX
- **E-mail:** Validação regex
- **CEP:** Máscara XXXXX-XXX
- **Moeda:** R$ 1.234,56

### Layouts Responsivos Padrão:
- **Grid:** Bootstrap 5 grid system
- **Gutters:** `g-3`, `g-4`
- **Containers:** `container-fluid` para páginas completas
- **Breakpoints:** xs (<576px), sm (≥576px), md (≥768px), lg (≥992px), xl (≥1200px)

---

## 4) REGRAS DE PERMISSÃO (UI) — MATRIZ VISÍVEL

### Módulo Dashboard:
- **admin:** ✅ Total acesso
- **secretaria:** ✅ Total acesso
- **instrutor:** ✅ Acesso limitado (sem ferramentas)
- **aluno:** ❌ Sem acesso

### Módulo Cadastros:
- **admin:** ✅ Total acesso (incluindo Usuários e CFCs)
- **secretaria:** ✅ Acesso a Alunos, Instrutores, Veículos
- **instrutor:** ✅ Acesso a Alunos, Instrutores, Veículos
- **aluno:** ❌ Sem acesso

### Módulo Operacional:
- **admin:** ✅ Total acesso
- **secretaria:** ✅ Total acesso
- **instrutor:** ✅ Total acesso
- **aluno:** ❌ Sem acesso

### Módulo Gestão de Turmas:
- **admin:** ✅ Total acesso
- **secretaria:** ✅ Total acesso
- **instrutor:** ✅ Total acesso
- **aluno:** ❌ Sem acesso

### Módulo Financeiro:
- **admin:** ✅ Total acesso
- **secretaria:** ✅ Total acesso
- **instrutor:** ❌ Sem acesso (menu oculto)
- **aluno:** ❌ Sem acesso (menu oculto)

### Módulo Relatórios Gerais:
- **admin:** ✅ Total acesso
- **secretaria:** ✅ Total acesso
- **instrutor:** ❌ Sem acesso
- **aluno:** ❌ Sem acesso

### Módulo Configurações:
- **admin:** ✅ Total acesso
- **secretaria:** ❌ Sem acesso (menu oculto)
- **instrutor:** ❌ Sem acesso (menu oculto)
- **aluno:** ❌ Sem acesso (menu oculto)

### Módulo Ferramentas:
- **admin:** ✅ Total acesso
- **secretaria:** ❌ Sem acesso (menu oculto)
- **instrutor:** ❌ Sem acesso (menu oculto)
- **aluno:** ❌ Sem acesso (menu oculto)

### Mensagens de Acesso Negado:
- **Texto exato:** "Você não tem permissão para acessar esta funcionalidade"
- **Tipo:** Toast de erro com ícone `fa-exclamation-triangle`
- **Duração:** 5 segundos

---

## 5) FEATURE FLAGS / CONFIGS QUE AFETAM UI

### Flags Ativas:
- **FINANCEIRO_ENABLED:** `true`
  - **Quando ON:** Menu Financeiro visível para admin/secretaria
  - **Quando OFF:** Menu Financeiro oculto completamente

### Dependências de Ambiente:
- **Local:** Debug habilitado, logs detalhados
- **Produção:** Debug desabilitado, logs mínimos

### Configurações que Alteram UI:
- **LOG_ENABLED:** Afeta exibição de logs no sistema
- **DEBUG_MODE:** Afeta exibição de informações de debug

---

## 6) ITENS LEGADOS/DUPLICADOS/OCULTOS (PARA SANEAMENTO)

### Páginas Antigas Ainda Presentes:
- **turma-dashboard.php:** Substituído por `turmas.php`
- **turma-calendario.php:** Substituído por `turmas.php`
- **turma-matriculas.php:** Substituído por `turmas.php`
- **turma-configuracoes.php:** Substituído por `turmas.php`
- **turma-templates.php:** Substituído por `turmas.php`
- **turma-grade-generator.php:** Substituído por `turmas.php`

### Itens Duplicados no Menu:
- **Nenhum item duplicado identificado**

### Áreas "Preparado" / "Placeholder":
- **Exportação CSV:** Botões presentes mas funcionalidade em desenvolvimento
- **Integração Asaas:** Campos presentes mas integração não implementada
- **Relatórios PDF:** Botões presentes mas funcionalidade em desenvolvimento

### Console Warnings Visuais:
- **Nenhum warning identificado**

---

## 7) EXEMPLOS CONCRETOS (OBRIGATÓRIOS)

### Um Card KPI:
```html
<div class="stat-card primary">
    <div class="stat-header">
        <div class="stat-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="stat-change positive">
            <i class="fas fa-arrow-up"></i>
            <span>+5%</span>
        </div>
    </div>
    <div class="stat-value">1,247</div>
    <div class="stat-label">Total de Alunos</div>
</div>
```

### Uma Linha de Tabela:
```html
<tr>
    <td>
        <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center">
            <span class="avatar-title">JS</span>
        </div>
    </td>
    <td>João Silva</td>
    <td>123.456.789-01</td>
    <td>(11) 99999-9999</td>
    <td>CFC Bom Conselho</td>
    <td><span class="badge bg-success">Ativo</span></td>
    <td>
        <div class="btn-group btn-group-sm">
            <button class="btn btn-outline-primary" title="Editar">
                <i class="fas fa-edit"></i>
            </button>
            <button class="btn btn-outline-info" title="Histórico">
                <i class="fas fa-history"></i>
            </button>
            <button class="btn btn-outline-success" title="Financeiro">
                <i class="fas fa-dollar-sign"></i>
            </button>
            <button class="btn btn-outline-danger" title="Excluir">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </td>
</tr>
```

### Um Modal:
```html
<div class="modal fade" id="modalNovoAluno" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus me-2"></i>Cadastrar Novo Aluno
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formNovoAluno">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Nome Completo *</label>
                            <input type="text" class="form-control" name="nome" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">CPF *</label>
                            <input type="text" class="form-control" name="cpf" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="salvarAluno()">
                    <i class="fas fa-save me-1"></i>Salvar
                </button>
            </div>
        </div>
    </div>
</div>
```

### Um Toast:
```html
<div class="toast-container position-fixed top-0 end-0 p-3">
    <div class="toast show" role="alert">
        <div class="toast-header">
            <i class="fas fa-check-circle text-success me-2"></i>
            <strong class="me-auto">Sucesso</strong>
            <small>agora</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            Aluno cadastrado com sucesso!
        </div>
    </div>
</div>
```

### Um Deep Link:
```html
<a href="pages/financeiro-faturas.php?aluno_id=123&matricula_id=456" class="btn btn-outline-success btn-sm">
    <i class="fas fa-dollar-sign me-1"></i>Ver Faturas
</a>
```

---

## 8) SUMÁRIO FINAL DE LACUNAS

### Lacunas Visuais:
1. **Página sem empty state:** Algumas páginas não têm estado vazio bem definido
2. **Botão sem ícone:** Alguns botões secundários não têm ícones
3. **Rótulos inconsistentes:** Alguns rótulos variam entre páginas
4. **Loading states:** Nem todas as operações têm estados de loading
5. **Validação visual:** Alguns campos não mostram validação em tempo real

### Inconsistências de Ícone/Classe:
1. **Ícones de editar:** `fa-edit` vs `fa-pen` em diferentes páginas
2. **Classes de botão:** `btn-outline-primary` vs `btn-primary` inconsistentes
3. **Badges de status:** Cores diferentes para mesmo status em páginas diferentes
4. **Tamanhos de ícone:** `fa-2x` vs `fa-3x` inconsistentes

### Redundâncias a Remover:
1. **Páginas de turma legadas:** 6 arquivos podem ser removidos
2. **CSS duplicado:** Estilos repetidos em múltiplos arquivos
3. **JavaScript duplicado:** Funções similares em diferentes arquivos

### Quick Wins de UX (2-5 itens objetivos):
1. **Padronizar ícones:** Usar sempre `fa-edit` para editar, `fa-trash` para excluir
2. **Implementar loading states:** Adicionar spinners em todas as operações assíncronas
3. **Melhorar empty states:** Adicionar ilustrações e ações claras
4. **Validação em tempo real:** Mostrar erros de validação enquanto o usuário digita
5. **Tooltips informativos:** Adicionar tooltips em botões e ícones

---

**✅ INVENTÁRIO COMPLETO FINALIZADO**

Este documento mapeia todos os componentes visuais e funcionais do sistema CFC após as implementações do módulo financeiro, fornecendo uma base sólida para a reestruturação final da UI.
