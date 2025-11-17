# DIFF - REORGANIZAÇÃO DO MENU ADMINISTRATIVO

**Data:** 2025-01-28  
**Baseado em:** `_FASE-4-ARQUITETURA-GERAL.md` - Seção 2.1 MENU PRINCIPAL  
**Arquivo alvo:** `admin/index.php` (linhas 1286-1514 para desktop, 1517-1712 para mobile)  
**Arquivo JS:** `admin/assets/js/menu-flyout.js` (linhas 13-81 para flyoutConfig)

---

## 📋 RESUMO DAS MUDANÇAS

### Estrutura ATUAL vs. Estrutura ALVO

| Ordem | ATUAL | ALVO |
|-------|-------|------|
| 1 | Dashboard | Dashboard ✅ (mantém) |
| 2 | Cadastros (submenu) | **Alunos** (submenu opcional) |
| 3 | Operacional (submenu) | **Acadêmico** (submenu) |
| 4 | Gestão de Turmas (direto) | **Provas & Exames** (submenu) |
| 5 | Financeiro (submenu) | Financeiro (submenu - ajustar itens) |
| 6 | Relatórios Gerais (submenu) | **Configurações** (submenu - reorganizar) |
| 7 | Configurações (submenu) | **Relatórios** (submenu - reorganizar) |
| 8 | Ferramentas (vazio) | **Sistema / Ajuda** (novo) |
| 9 | Sair | Sair ✅ (mantém) |

---

## 🔄 MUDANÇAS DETALHADAS

### 1. **DASHBOARD** (MANTÉM)
- ✅ Nenhuma mudança necessária
- **Arquivo:** `admin/index.php` linha ~1295-1302 (desktop), ~1537-1542 (mobile)

---

### 2. **CADASTROS → ALUNOS** (REORGANIZAÇÃO MAJOR)

#### ❌ REMOVER:
- Menu "Cadastros" completo (com submenu)
- Itens dentro de Cadastros:
  - Usuários (mover para Sistema/Ajuda ou manter apenas para Admin Master)
  - CFCs (mover para Sistema/Ajuda ou manter apenas para Admin Master)
  - **Alunos** (extrair como menu principal)
  - Instrutores (mover para Acadêmico)
  - Veículos (mover para Acadêmico)

#### ✅ CRIAR:
- Novo menu principal **"Alunos"** com submenu opcional:
  - Alunos (link principal - lista completa)
  - Alunos Ativos (filtro: `status = 'em_formacao'`)
  - Alunos em Exame (filtro: `status = 'em_exame'`)
  - Alunos Concluídos (filtro: `status = 'concluido'`)

**Arquivo:** `admin/index.php`  
**Linhas a substituir:** ~1304-1344 (desktop), ~1544-1580 (mobile)

**ANTES:**
```php
<!-- Cadastros -->
<?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="cadastros" title="Cadastros">
        ...
    </div>
    <div class="nav-submenu" id="cadastros">
        <!-- Usuários, CFCs, Alunos, Instrutores, Veículos -->
    </div>
</div>
<?php endif; ?>
```

**DEPOIS:**
```php
<!-- Alunos -->
<?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="alunos" title="Alunos">
        <div class="nav-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <div class="nav-text">Alunos</div>
        <div class="nav-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <div class="nav-submenu" id="alunos">
        <a href="index.php?page=alunos" class="nav-sublink <?php echo $page === 'alunos' ? 'active' : ''; ?>">
            <i class="fas fa-list"></i>
            <span>Todos os Alunos</span>
            <div class="nav-badge"><?php echo $stats['total_alunos']; ?></div>
        </a>
        <a href="index.php?page=alunos&status=em_formacao" class="nav-sublink <?php echo ($page === 'alunos' && ($_GET['status'] ?? '') === 'em_formacao') ? 'active' : ''; ?>">
            <i class="fas fa-user-check"></i>
            <span>Alunos Ativos</span>
        </a>
        <a href="index.php?page=alunos&status=em_exame" class="nav-sublink <?php echo ($page === 'alunos' && ($_GET['status'] ?? '') === 'em_exame') ? 'active' : ''; ?>">
            <i class="fas fa-clipboard-check"></i>
            <span>Alunos em Exame</span>
        </a>
        <a href="index.php?page=alunos&status=concluido" class="nav-sublink <?php echo ($page === 'alunos' && ($_GET['status'] ?? '') === 'concluido') ? 'active' : ''; ?>">
            <i class="fas fa-check-circle"></i>
            <span>Alunos Concluídos</span>
        </a>
    </div>
</div>
<?php endif; ?>
```

**Nota:** Para Admin Master (SaaS), pode manter menu "CFCs" e "Usuários" separado ou em "Sistema/Ajuda".

---

### 3. **OPERACIONAL + GESTÃO DE TURMAS → ACADÊMICO** (REORGANIZAÇÃO MAJOR)

#### ❌ REMOVER:
- Menu "Operacional" completo
- Menu "Gestão de Turmas" (link direto)

#### ✅ CRIAR:
- Novo menu principal **"Acadêmico"** com submenu:
  - **Turmas Teóricas** (`?page=turmas-teoricas`)
  - **Presenças Teóricas** (`?page=turma-chamada` ou criar nova página)
  - **Aulas Práticas** (`?page=listar-aulas` ou `?page=aulas-praticas`)
  - **Agenda Geral** (`?page=agendamento` ou `?page=agenda`)
  - **Instrutores** (`?page=instrutores`) ← movido de Cadastros
  - **Veículos** (`?page=veiculos`) ← movido de Cadastros
  - **Salas** (`?page=configuracoes-salas`) ← movido de Configurações

**Arquivo:** `admin/index.php`  
**Linhas a substituir:** ~1346-1378 (desktop), ~1582-1608 (mobile)

**ANTES:**
```php
<!-- Operacional -->
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="operacional" title="Operacional">
        ...
    </div>
    <div class="nav-submenu" id="operacional">
        <a href="index.php?page=agendamento" ...>Agendamento</a>
        <a href="index.php?page=exames" ...>Exames Médicos</a>
    </div>
</div>

<!-- Gestão de Turmas -->
<div class="nav-item">
    <a href="?page=turmas-teoricas" class="nav-link ...">
        ...
    </a>
</div>
```

**DEPOIS:**
```php
<!-- Acadêmico -->
<?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="academico" title="Acadêmico">
        <div class="nav-icon">
            <i class="fas fa-book-reader"></i>
        </div>
        <div class="nav-text">Acadêmico</div>
        <div class="nav-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <div class="nav-submenu" id="academico">
        <a href="?page=turmas-teoricas" class="nav-sublink <?php echo $page === 'turmas-teoricas' ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Turmas Teóricas</span>
        </a>
        <a href="?page=presencas-teoricas" class="nav-sublink <?php echo $page === 'presencas-teoricas' ? 'active' : ''; ?>">
            <i class="fas fa-check-square"></i>
            <span>Presenças Teóricas</span>
        </a>
        <a href="?page=aulas-praticas" class="nav-sublink <?php echo $page === 'aulas-praticas' ? 'active' : ''; ?>">
            <i class="fas fa-car-side"></i>
            <span>Aulas Práticas</span>
        </a>
        <a href="?page=agendamento" class="nav-sublink <?php echo $page === 'agendamento' ? 'active' : ''; ?>">
            <i class="fas fa-calendar-alt"></i>
            <span>Agenda Geral</span>
            <div class="nav-badge"><?php echo $stats['total_aulas']; ?></div>
        </a>
        <a href="index.php?page=instrutores" class="nav-sublink <?php echo $page === 'instrutores' ? 'active' : ''; ?>">
            <i class="fas fa-chalkboard-teacher"></i>
            <span>Instrutores</span>
            <div class="nav-badge"><?php echo $stats['total_instrutores']; ?></div>
        </a>
        <a href="index.php?page=veiculos" class="nav-sublink <?php echo $page === 'veiculos' ? 'active' : ''; ?>">
            <i class="fas fa-car"></i>
            <span>Veículos</span>
            <div class="nav-badge"><?php echo $stats['total_veiculos']; ?></div>
        </a>
        <a href="index.php?page=configuracoes-salas" class="nav-sublink <?php echo $page === 'configuracoes-salas' ? 'active' : ''; ?>">
            <i class="fas fa-door-open"></i>
            <span>Salas</span>
        </a>
    </div>
</div>
<?php endif; ?>
```

**Nota:** Verificar se existem páginas para "Presenças Teóricas" e "Aulas Práticas". Se não existirem, criar ou usar páginas existentes:
- Presenças: pode usar `turma-chamada.php` ou criar `presencas-teoricas.php`
- Aulas Práticas: pode usar `listar-aulas.php` ou criar `aulas-praticas.php`

---

### 4. **EXAMES MÉDICOS → PROVAS & EXAMES** (EXPANDIR)

#### ❌ REMOVER:
- Item "Exames Médicos" do submenu "Operacional"

#### ✅ CRIAR:
- Novo menu principal **"Provas & Exames"** com submenu:
  - **Exame Médico** (`?page=exames&tipo=medico`)
  - **Exame Psicotécnico** (`?page=exames&tipo=psicotecnico`)
  - **Prova Teórica** (`?page=exames&tipo=teorico`)
  - **Prova Prática** (`?page=exames&tipo=pratico`)

**Arquivo:** `admin/index.php`  
**Posição:** Após "Acadêmico", antes de "Financeiro"

**DEPOIS:**
```php
<!-- Provas & Exames -->
<?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="provas-exames" title="Provas & Exames">
        <div class="nav-icon">
            <i class="fas fa-clipboard-check"></i>
        </div>
        <div class="nav-text">Provas & Exames</div>
        <div class="nav-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <div class="nav-submenu" id="provas-exames">
        <a href="?page=exames&tipo=medico" class="nav-sublink <?php echo ($page === 'exames' && ($_GET['tipo'] ?? '') === 'medico') ? 'active' : ''; ?>">
            <i class="fas fa-stethoscope"></i>
            <span>Exame Médico</span>
        </a>
        <a href="?page=exames&tipo=psicotecnico" class="nav-sublink <?php echo ($page === 'exames' && ($_GET['tipo'] ?? '') === 'psicotecnico') ? 'active' : ''; ?>">
            <i class="fas fa-brain"></i>
            <span>Exame Psicotécnico</span>
        </a>
        <a href="?page=exames&tipo=teorico" class="nav-sublink <?php echo ($page === 'exames' && ($_GET['tipo'] ?? '') === 'teorico') ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Prova Teórica</span>
        </a>
        <a href="?page=exames&tipo=pratico" class="nav-sublink <?php echo ($page === 'exames' && ($_GET['tipo'] ?? '') === 'pratico') ? 'active' : ''; ?>">
            <i class="fas fa-car"></i>
            <span>Prova Prática</span>
        </a>
    </div>
</div>
<?php endif; ?>
```

**Nota:** Verificar se a página `exames.php` suporta filtro por `tipo`. Se não, precisará ser implementado.

---

### 5. **FINANCEIRO** (AJUSTAR ITENS)

#### ❌ REMOVER/MODIFICAR:
- "Despesas (Pagamentos)" → renomear para **"Pagamentos"**
- Manter "Faturas" e "Relatórios"

#### ✅ ADICIONAR:
- **Configurações Financeiras** (`?page=financeiro-configuracoes`)

**Arquivo:** `admin/index.php`  
**Linhas a modificar:** ~1380-1407 (desktop), ~1610-1633 (mobile)

**ANTES:**
```php
<div class="nav-submenu" id="financeiro">
    <a href="?page=financeiro-faturas" ...>Faturas (Receitas)</a>
    <a href="?page=financeiro-despesas" ...>Despesas (Pagamentos)</a>
    <a href="?page=financeiro-relatorios" ...>Relatórios</a>
</div>
```

**DEPOIS:**
```php
<div class="nav-submenu" id="financeiro">
    <a href="?page=financeiro-faturas" class="nav-sublink <?php echo $page === 'financeiro-faturas' ? 'active' : ''; ?>">
        <i class="fas fa-file-invoice"></i>
        <span>Faturas</span>
    </a>
    <a href="?page=financeiro-pagamentos" class="nav-sublink <?php echo $page === 'financeiro-pagamentos' ? 'active' : ''; ?>">
        <i class="fas fa-receipt"></i>
        <span>Pagamentos</span>
    </a>
    <a href="?page=financeiro-relatorios" class="nav-sublink <?php echo $page === 'financeiro-relatorios' ? 'active' : ''; ?>">
        <i class="fas fa-chart-line"></i>
        <span>Relatórios Financeiros</span>
    </a>
    <a href="?page=financeiro-configuracoes" class="nav-sublink <?php echo $page === 'financeiro-configuracoes' ? 'active' : ''; ?>">
        <i class="fas fa-cog"></i>
        <span>Configurações Financeiras</span>
    </a>
</div>
```

**Nota:** Verificar se existe página `financeiro-pagamentos.php` ou se deve usar `financeiro-despesas.php` renomeado. Criar `financeiro-configuracoes.php` se não existir.

---

### 6. **CONFIGURAÇÕES** (REORGANIZAR)

#### ❌ REMOVER/MOVER:
- **Salas** → mover para Acadêmico
- **Disciplinas** → pode manter ou mover para Acadêmico
- **Logs** → mover para Sistema/Ajuda
- **Backup** → mover para Sistema/Ajuda

#### ✅ MANTER/ADICIONAR:
- **Dados do CFC** (`?page=configuracoes&action=dados-cfc`)
- **Cursos / Categorias** (`?page=configuracoes-categorias`)
- **Tabela de Horários** (criar `?page=configuracoes-horarios`)
- **Regras de Bloqueio** (criar `?page=configuracoes-bloqueios`)
- **Modelos de Documentos** (criar `?page=configuracoes-documentos`)
- **Configurações Gerais** (manter)

**Arquivo:** `admin/index.php`  
**Linhas a modificar:** ~1446-1485 (desktop), ~1658-1695 (mobile)

**ANTES:**
```php
<div class="nav-submenu" id="configuracoes">
    <a href="index.php?page=configuracoes-categorias" ...>Categorias de Habilitação</a>
    <a href="index.php?page=configuracoes-salas" ...>Salas de Aula</a>
    <a href="index.php?page=configuracoes-disciplinas" ...>Disciplinas</a>
    <a href="index.php?page=configuracoes&action=geral" ...>Configurações Gerais</a>
    <a href="index.php?page=logs&action=list" ...>Logs do Sistema</a>
    <a href="index.php?page=backup" ...>Backup</a>
</div>
```

**DEPOIS:**
```php
<div class="nav-submenu" id="configuracoes">
    <a href="index.php?page=configuracoes&action=dados-cfc" class="nav-sublink <?php echo ($page === 'configuracoes' && ($_GET['action'] ?? '') === 'dados-cfc') ? 'active' : ''; ?>">
        <i class="fas fa-building"></i>
        <span>Dados do CFC</span>
    </a>
    <a href="index.php?page=configuracoes-categorias" class="nav-sublink <?php echo $page === 'configuracoes-categorias' ? 'active' : ''; ?>">
        <i class="fas fa-layer-group"></i>
        <span>Cursos / Categorias</span>
    </a>
    <a href="index.php?page=configuracoes-horarios" class="nav-sublink <?php echo $page === 'configuracoes-horarios' ? 'active' : ''; ?>">
        <i class="fas fa-clock"></i>
        <span>Tabela de Horários</span>
    </a>
    <a href="index.php?page=configuracoes-bloqueios" class="nav-sublink <?php echo $page === 'configuracoes-bloqueios' ? 'active' : ''; ?>">
        <i class="fas fa-ban"></i>
        <span>Regras de Bloqueio</span>
    </a>
    <a href="index.php?page=configuracoes-documentos" class="nav-sublink <?php echo $page === 'configuracoes-documentos' ? 'active' : ''; ?>">
        <i class="fas fa-file-pdf"></i>
        <span>Modelos de Documentos</span>
    </a>
    <a href="index.php?page=configuracoes-disciplinas" class="nav-sublink <?php echo $page === 'configuracoes-disciplinas' ? 'active' : ''; ?>">
        <i class="fas fa-book"></i>
        <span>Disciplinas</span>
    </a>
    <a href="index.php?page=configuracoes&action=geral" class="nav-sublink <?php echo ($page === 'configuracoes' && ($_GET['action'] ?? '') === 'geral') ? 'active' : ''; ?>">
        <i class="fas fa-sliders-h"></i>
        <span>Configurações Gerais</span>
    </a>
</div>
```

**Nota:** Algumas páginas podem não existir ainda (horários, bloqueios, documentos). Isso pode ser implementado posteriormente ou usar placeholders temporários.

---

### 7. **RELATÓRIOS GERAIS → RELATÓRIOS** (REORGANIZAR)

#### ❌ REMOVER/MOVER:
- "Relatório de Matrículas" → pode manter ou remover
- "Relatório de Frequência" → manter como "Frequência teórica"
- "Relatório de Presenças" → pode integrar em "Frequência teórica"
- "Relatório de ATA" → manter ou mover para Acadêmico
- "Vagas e Candidatos" → pode remover ou mover para Acadêmico

#### ✅ REORGANIZAR COMO:
- **Frequência teórica** (`?page=relatorio-frequencia`)
- **Conclusão prática** (`?page=relatorio-conclusao-pratica` - criar se não existir)
- **Provas (taxa de aprovação)** (`?page=relatorio-provas` - criar se não existir)
- **Inadimplência** (`?page=financeiro-relatorios&tipo=inadimplencia` ou criar página específica)

**Arquivo:** `admin/index.php`  
**Linhas a modificar:** ~1409-1444 (desktop), ~1635-1656 (mobile)

**ANTES:**
```php
<div class="nav-submenu" id="relatorios">
    <a href="pages/relatorio-matriculas.php" ...>Relatório de Matrículas</a>
    <a href="pages/relatorio-frequencia.php" ...>Relatório de Frequência</a>
    <a href="pages/relatorio-presencas.php" ...>Relatório de Presenças</a>
    <a href="pages/relatorio-ata.php" ...>Relatório de ATA</a>
    <a href="pages/vagas-candidatos.php" ...>Vagas e Candidatos</a>
</div>
```

**DEPOIS:**
```php
<div class="nav-submenu" id="relatorios">
    <a href="pages/relatorio-frequencia.php" class="nav-sublink <?php echo ($page === 'relatorio-frequencia' || $page === 'relatorio-presencas') ? 'active' : ''; ?>">
        <i class="fas fa-chart-bar"></i>
        <span>Frequência Teórica</span>
    </a>
    <a href="pages/relatorio-conclusao-pratica.php" class="nav-sublink <?php echo $page === 'relatorio-conclusao-pratica' ? 'active' : ''; ?>">
        <i class="fas fa-check-circle"></i>
        <span>Conclusão Prática</span>
    </a>
    <a href="pages/relatorio-provas.php" class="nav-sublink <?php echo $page === 'relatorio-provas' ? 'active' : ''; ?>">
        <i class="fas fa-clipboard-check"></i>
        <span>Provas (Taxa de Aprovação)</span>
    </a>
    <a href="index.php?page=financeiro-relatorios&tipo=inadimplencia" class="nav-sublink <?php echo ($page === 'financeiro-relatorios' && ($_GET['tipo'] ?? '') === 'inadimplencia') ? 'active' : ''; ?>">
        <i class="fas fa-exclamation-triangle"></i>
        <span>Inadimplência</span>
    </a>
</div>
```

---

### 8. **FERRAMENTAS → SISTEMA / AJUDA** (NOVO)

#### ❌ REMOVER:
- Menu "Ferramentas" (estava vazio)

#### ✅ CRIAR:
- Novo menu **"Sistema / Ajuda"** com submenu:
  - **Logs** (`?page=logs&action=list`) ← movido de Configurações
  - **FAQ** (`?page=faq` - criar se não existir)
  - **Suporte** (`?page=suporte` - criar se não existir)
  - **Backup** (`?page=backup`) ← movido de Configurações

**Arquivo:** `admin/index.php`  
**Posição:** Após "Relatórios", antes de "Sair"  
**Linhas a substituir:** ~1487-1502 (desktop), ~1697-1712 (mobile)

**ANTES:**
```php
<!-- Ferramentas de Desenvolvimento -->
<?php if ($isAdmin): ?>
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="ferramentas" title="Ferramentas">
        ...
    </div>
    <div class="nav-submenu" id="ferramentas">
    </div>
</div>
<?php endif; ?>
```

**DEPOIS:**
```php
<!-- Sistema / Ajuda -->
<?php if ($isAdmin): ?>
<div class="nav-item nav-group">
    <div class="nav-link nav-toggle" data-group="sistema-ajuda" title="Sistema / Ajuda">
        <div class="nav-icon">
            <i class="fas fa-question-circle"></i>
        </div>
        <div class="nav-text">Sistema / Ajuda</div>
        <div class="nav-arrow">
            <i class="fas fa-chevron-down"></i>
        </div>
    </div>
    <div class="nav-submenu" id="sistema-ajuda">
        <a href="index.php?page=logs&action=list" class="nav-sublink <?php echo $page === 'logs' ? 'active' : ''; ?>">
            <i class="fas fa-file-alt"></i>
            <span>Logs</span>
        </a>
        <a href="index.php?page=faq" class="nav-sublink <?php echo $page === 'faq' ? 'active' : ''; ?>">
            <i class="fas fa-question"></i>
            <span>FAQ</span>
        </a>
        <a href="index.php?page=suporte" class="nav-sublink <?php echo $page === 'suporte' ? 'active' : ''; ?>">
            <i class="fas fa-headset"></i>
            <span>Suporte</span>
        </a>
        <a href="index.php?page=backup" class="nav-sublink <?php echo $page === 'backup' ? 'active' : ''; ?>">
            <i class="fas fa-download"></i>
            <span>Backup</span>
        </a>
    </div>
</div>
<?php endif; ?>
```

---

### 9. **ATUALIZAR menu-flyout.js**

**Arquivo:** `admin/assets/js/menu-flyout.js`  
**Linhas a modificar:** 13-81 (flyoutConfig)

#### Mudanças necessárias:

1. **Remover:**
   - `'cadastros'` (substituir por `'alunos'`)
   - `'operacional'` (substituir por `'academico'`)
   - `'turmas'` (integrar em `'academico'`)
   - `'ferramentas'` (substituir por `'sistema-ajuda'`)

2. **Adicionar:**
   - `'alunos'`
   - `'academico'`
   - `'provas-exames'`
   - `'sistema-ajuda'`

3. **Manter:**
   - `'financeiro'` (ajustar itens)
   - `'relatorios'` (ajustar itens)
   - `'configuracoes'` (ajustar itens)

**ANTES:**
```javascript
const flyoutConfig = {
    'cadastros': { ... },
    'operacional': { ... },
    'turmas': { ... },
    'financeiro': { ... },
    'relatorios': { ... },
    'configuracoes': { ... },
    'ferramentas': { ... }
};
```

**DEPOIS:**
```javascript
const flyoutConfig = {
    'alunos': {
        title: 'Alunos',
        items: [
            { icon: 'fas fa-list', text: 'Todos os Alunos', href: '?page=alunos' },
            { icon: 'fas fa-user-check', text: 'Alunos Ativos', href: '?page=alunos&status=em_formacao' },
            { icon: 'fas fa-clipboard-check', text: 'Alunos em Exame', href: '?page=alunos&status=em_exame' },
            { icon: 'fas fa-check-circle', text: 'Alunos Concluídos', href: '?page=alunos&status=concluido' }
        ]
    },
    'academico': {
        title: 'Acadêmico',
        items: [
            { icon: 'fas fa-chalkboard-teacher', text: 'Turmas Teóricas', href: '?page=turmas-teoricas' },
            { icon: 'fas fa-check-square', text: 'Presenças Teóricas', href: '?page=presencas-teoricas' },
            { icon: 'fas fa-car-side', text: 'Aulas Práticas', href: '?page=aulas-praticas' },
            { icon: 'fas fa-calendar-alt', text: 'Agenda Geral', href: '?page=agendamento' },
            { icon: 'fas fa-chalkboard-teacher', text: 'Instrutores', href: '?page=instrutores' },
            { icon: 'fas fa-car', text: 'Veículos', href: '?page=veiculos' },
            { icon: 'fas fa-door-open', text: 'Salas', href: '?page=configuracoes-salas' }
        ]
    },
    'provas-exames': {
        title: 'Provas & Exames',
        items: [
            { icon: 'fas fa-stethoscope', text: 'Exame Médico', href: '?page=exames&tipo=medico' },
            { icon: 'fas fa-brain', text: 'Exame Psicotécnico', href: '?page=exames&tipo=psicotecnico' },
            { icon: 'fas fa-file-alt', text: 'Prova Teórica', href: '?page=exames&tipo=teorico' },
            { icon: 'fas fa-car', text: 'Prova Prática', href: '?page=exames&tipo=pratico' }
        ]
    },
    'financeiro': {
        title: 'Financeiro',
        items: [
            { icon: 'fas fa-file-invoice', text: 'Faturas', href: '?page=financeiro-faturas' },
            { icon: 'fas fa-receipt', text: 'Pagamentos', href: '?page=financeiro-pagamentos' },
            { icon: 'fas fa-chart-line', text: 'Relatórios Financeiros', href: '?page=financeiro-relatorios' },
            { icon: 'fas fa-cog', text: 'Configurações Financeiras', href: '?page=financeiro-configuracoes' }
        ]
    },
    'relatorios': {
        title: 'Relatórios',
        items: [
            { icon: 'fas fa-chart-bar', text: 'Frequência Teórica', href: 'pages/relatorio-frequencia.php' },
            { icon: 'fas fa-check-circle', text: 'Conclusão Prática', href: 'pages/relatorio-conclusao-pratica.php' },
            { icon: 'fas fa-clipboard-check', text: 'Provas (Taxa de Aprovação)', href: 'pages/relatorio-provas.php' },
            { icon: 'fas fa-exclamation-triangle', text: 'Inadimplência', href: '?page=financeiro-relatorios&tipo=inadimplencia' }
        ]
    },
    'configuracoes': {
        title: 'Configurações',
        items: [
            { icon: 'fas fa-building', text: 'Dados do CFC', href: '?page=configuracoes&action=dados-cfc' },
            { icon: 'fas fa-layer-group', text: 'Cursos / Categorias', href: '?page=configuracoes-categorias' },
            { icon: 'fas fa-clock', text: 'Tabela de Horários', href: '?page=configuracoes-horarios' },
            { icon: 'fas fa-ban', text: 'Regras de Bloqueio', href: '?page=configuracoes-bloqueios' },
            { icon: 'fas fa-file-pdf', text: 'Modelos de Documentos', href: '?page=configuracoes-documentos' },
            { icon: 'fas fa-book', text: 'Disciplinas', href: '?page=configuracoes-disciplinas' },
            { icon: 'fas fa-sliders-h', text: 'Configurações Gerais', href: '?page=configuracoes&action=geral' }
        ]
    },
    'sistema-ajuda': {
        title: 'Sistema / Ajuda',
        items: [
            { icon: 'fas fa-file-alt', text: 'Logs', href: '?page=logs&action=list' },
            { icon: 'fas fa-question', text: 'FAQ', href: '?page=faq' },
            { icon: 'fas fa-headset', text: 'Suporte', href: '?page=suporte' },
            { icon: 'fas fa-download', text: 'Backup', href: '?page=backup' }
        ]
    }
};
```

---

## ✅ CHECKLIST DE IMPLEMENTAÇÃO

### Arquivos a modificar:
- [ ] `admin/index.php` (menu desktop - linhas ~1286-1514)
- [ ] `admin/index.php` (menu mobile - linhas ~1517-1712)
- [ ] `admin/assets/js/menu-flyout.js` (flyoutConfig - linhas ~13-81)

### Páginas que podem precisar ser criadas/verificadas:
- [ ] `admin/pages/presencas-teoricas.php` (ou usar `turma-chamada.php`)
- [ ] `admin/pages/aulas-praticas.php` (ou usar `listar-aulas.php`)
- [ ] `admin/pages/financeiro-pagamentos.php` (ou renomear `financeiro-despesas.php`)
- [ ] `admin/pages/financeiro-configuracoes.php`
- [ ] `admin/pages/configuracoes-horarios.php`
- [ ] `admin/pages/configuracoes-bloqueios.php`
- [ ] `admin/pages/configuracoes-documentos.php`
- [ ] `admin/pages/relatorio-conclusao-pratica.php`
- [ ] `admin/pages/relatorio-provas.php`
- [ ] `admin/pages/faq.php`
- [ ] `admin/pages/suporte.php`

### Verificações necessárias:
- [ ] Página `exames.php` suporta filtro por `tipo`?
- [ ] Página `alunos.php` suporta filtro por `status`?
- [ ] Página `financeiro-relatorios.php` suporta filtro por `tipo=inadimplencia`?
- [ ] Atualizar roteamento em `admin/index.php` para novas páginas (seção de `switch($page)`)

---

## 📝 NOTAS IMPORTANTES

1. **Preservar permissões:** Manter todas as verificações de permissão (`$isAdmin`, `$user['tipo'] === 'secretaria'`, etc.)

2. **Admin Master (SaaS):** Para usuários do tipo "Admin Master", pode ser necessário manter menus adicionais como "CFCs" e "Usuários". Avaliar se deve criar um menu separado ou incluir em "Sistema/Ajuda".

3. **Compatibilidade:** Algumas páginas referenciadas podem não existir ainda. Nesses casos:
   - Criar páginas temporárias/com placeholder
   - Ou comentar o item do menu até a implementação
   - Ou redirecionar para página existente similar

4. **Menu Mobile:** Garantir que todas as mudanças sejam replicadas no menu mobile (drawer).

5. **Ícones:** Verificar se todos os ícones FontAwesome usados estão disponíveis. Substituir por ícones similares se necessário.

6. **Badges/Contadores:** Manter badges de contagem (ex: total de alunos, instrutores, veículos) onde faz sentido.

---

## 🎯 RESULTADO FINAL ESPERADO

O menu reorganizado seguirá esta estrutura:

```
1. Dashboard
2. Alunos
   ├─ Todos os Alunos
   ├─ Alunos Ativos
   ├─ Alunos em Exame
   └─ Alunos Concluídos
3. Acadêmico
   ├─ Turmas Teóricas
   ├─ Presenças Teóricas
   ├─ Aulas Práticas
   ├─ Agenda Geral
   ├─ Instrutores
   ├─ Veículos
   └─ Salas
4. Provas & Exames
   ├─ Exame Médico
   ├─ Exame Psicotécnico
   ├─ Prova Teórica
   └─ Prova Prática
5. Financeiro
   ├─ Faturas
   ├─ Pagamentos
   ├─ Relatórios Financeiros
   └─ Configurações Financeiras
6. Configurações
   ├─ Dados do CFC
   ├─ Cursos / Categorias
   ├─ Tabela de Horários
   ├─ Regras de Bloqueio
   ├─ Modelos de Documentos
   ├─ Disciplinas
   └─ Configurações Gerais
7. Relatórios
   ├─ Frequência Teórica
   ├─ Conclusão Prática
   ├─ Provas (Taxa de Aprovação)
   └─ Inadimplência
8. Sistema / Ajuda
   ├─ Logs
   ├─ FAQ
   ├─ Suporte
   └─ Backup
9. Sair
```

---

**Fim do documento de diff para reorganização do menu.**

