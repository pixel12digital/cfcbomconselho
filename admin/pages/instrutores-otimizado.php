<?php
// Página de gerenciamento de instrutores - VERSÃO OTIMIZADA
if (!function_exists('isLoggedIn') || !function_exists('hasPermission')) {
    die('Funções de autenticação não disponíveis');
}

$pageTitle = 'Gestão de Instrutores';
?>

<!-- CSS otimizado -->
<link rel="stylesheet" href="assets/css/instrutores-otimizado.css">

<div class="container-fluid">
    <!-- Header compacto e alinhado -->
    <div class="instructors-header">
        <h1 class="instructors-title">Gestão de Instrutores</h1>
        <button class="btn btn-primary new-instructor-btn" onclick="abrirModalInstrutor()" aria-label="Adicionar novo instrutor">
            <i class="fas fa-plus"></i> Novo Instrutor
        </button>
    </div>

    <!-- Filtros compactos e responsivos -->
    <div class="filters-container">
        <div class="filters-grid">
            <div class="form-group">
                <label for="filtroStatus" class="form-label">Status</label>
                <select id="filtroStatus" class="form-select" onchange="aplicarFiltros()">
                    <option value="">Todos</option>
                    <option value="1">Ativo</option>
                    <option value="0">Inativo</option>
                    <option value="vacation">Em férias</option>
                </select>
            </div>
            <div class="form-group">
                <label for="filtroCFC" class="form-label">CFC</label>
                <select id="filtroCFC" class="form-select" onchange="aplicarFiltros()">
                    <option value="">Todos</option>
                    <!-- Preencher via JavaScript -->
                </select>
            </div>
            <div class="form-group">
                <label for="filtroCategoria" class="form-label">Categoria</label>
                <select id="filtroCategoria" class="form-select" onchange="aplicarFiltros()">
                    <option value="">Todas</option>
                    <option value="A">A</option>
                    <option value="B">B</option>
                    <option value="C">C</option>
                    <option value="D">D</option>
                    <option value="E">E</option>
                </select>
            </div>
            <div class="form-group">
                <label for="buscaInstrutor" class="form-label">Buscar</label>
                <input type="text" id="buscaInstrutor" class="form-control" 
                       placeholder="Nome, credencial ou CPF..." 
                       oninput="buscarComDebounce()"
                       onkeydown="handleSearchKeydown(event)">
            </div>
        </div>
        
        <!-- Chips de filtros aplicados -->
        <div class="filter-chips" id="filterChips" style="display: none;">
            <!-- Preenchido via JavaScript -->
        </div>
        
        <!-- Contagem de resultados e tempo -->
        <div class="results-info">
            <div class="results-count" id="resultsCount">Carregando...</div>
            <div class="last-updated" id="lastUpdated"></div>
            <button class="btn btn-outline-secondary btn-sm" onclick="limparFiltros()">
                <i class="fas fa-times"></i> Limpar Filtros
            </button>
        </div>
    </div>

    <!-- KPI Cards padronizados -->
    <div class="kpi-grid">
        <div class="kpi-card total">
            <div class="kpi-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="totalInstrutores">0</div>
                <div class="kpi-label">Total de Instrutores</div>
            </div>
        </div>
        <div class="kpi-card active">
            <div class="kpi-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="instrutoresAtivos">0</div>
                <div class="kpi-label">Instrutores Ativos</div>
            </div>
        </div>
        <div class="kpi-card inactive">
            <div class="kpi-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="instrutoresInativos">0</div>
                <div class="kpi-label">Instrutores Inativos</div>
            </div>
        </div>
        <div class="kpi-card vacation">
            <div class="kpi-icon">
                <i class="fas fa-calendar-times"></i>
            </div>
            <div class="kpi-content">
                <div class="kpi-value" id="instrutoresFerias">0</div>
                <div class="kpi-label">Em Férias</div>
            </div>
        </div>
    </div>

    <!-- Tabela otimizada -->
    <div class="table-container">
        <!-- Header da tabela -->
        <div class="table-header">
            <h5 class="table-title">Lista de Instrutores</h5>
            <div class="table-actions">
                <button class="btn btn-outline-success btn-sm" onclick="exportarInstrutores()">
                    <i class="fas fa-download"></i> Exportar
                </button>
                <button class="btn btn-outline-info btn-sm" onclick="imprimirInstrutores()">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Ações em massa -->
        <div class="bulk-actions" id="bulkActions" style="display: none;">
            <div class="d-flex align-items-center">
                <input type="checkbox" class="form-check-input bulk-checkbox" id="selectAll" onchange="toggleSelectAll()">
                <label for="selectAll" class="form-check-label ms-2">
                    <span id="selectedCount">0</span> selecionados
                </label>
            </div>
            <div class="bulk-buttons">
                <button class="btn btn-success btn-sm" onclick="bulkAction('activate')">
                    <i class="fas fa-check"></i> Ativar
                </button>
                <button class="btn btn-warning btn-sm" onclick="bulkAction('deactivate')">
                    <i class="fas fa-times"></i> Desativar
                </button>
                <button class="btn btn-info btn-sm" onclick="bulkAction('message')">
                    <i class="fas fa-envelope"></i> Enviar Mensagem
                </button>
            </div>
        </div>

        <!-- Tabela responsiva -->
        <div class="table-responsive">
            <table id="tabelaInstrutores" class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th width="40">
                            <input type="checkbox" class="form-check-input" id="selectAllHeader" onchange="toggleSelectAll()">
                        </th>
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>CFC</th>
                        <th>Status</th>
                        <th>Aulas Hoje</th>
                        <th>Ocupação</th>
                        <th>Última Atividade</th>
                        <th width="80">Ações</th>
                    </tr>
                </thead>
                <tbody id="tabelaInstrutoresBody">
                    <!-- Preenchido via JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Estados da tabela -->
        <div id="tableEmpty" class="table-empty" style="display: none;">
            <i class="fas fa-users fa-3x mb-3 text-muted"></i>
            <h5 class="text-muted">Nenhum instrutor encontrado</h5>
            <p class="text-muted">Tente ajustar os filtros ou adicionar um novo instrutor.</p>
        </div>

        <div id="tableLoading" class="table-loading" style="display: none;">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p>Carregando instrutores...</p>
        </div>

        <div id="tableError" class="table-error" style="display: none;">
            <i class="fas fa-exclamation-triangle fa-2x mb-3"></i>
            <h5>Erro ao carregar dados</h5>
            <button class="btn btn-outline-primary btn-sm" onclick="carregarInstrutores()">
                <i class="fas fa-redo"></i> Tentar Novamente
            </button>
        </div>

        <!-- Paginação -->
        <div class="pagination-container">
            <div class="pagination-info">
                Mostrando <span id="paginationStart">0</span> a <span id="paginationEnd">0</span> 
                de <span id="paginationTotal">0</span> instrutores
            </div>
            <div class="pagination-controls">
                <select class="form-select form-select-sm" id="itemsPerPage" onchange="changeItemsPerPage()">
                    <option value="10">10 por página</option>
                    <option value="25" selected>25 por página</option>
                    <option value="50">50 por página</option>
                    <option value="100">100 por página</option>
                </select>
                <nav aria-label="Paginação">
                    <ul class="pagination pagination-sm mb-0" id="paginationNav">
                        <!-- Preenchido via JavaScript -->
                    </ul>
                </nav>
            </div>
        </div>
    </div>
</div>

<!-- Toast Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- Modal Customizado (mantido da versão original) -->
<div id="modalInstrutor" class="custom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 9999; overflow: auto;">
    <div class="custom-modal-dialog" style="position: relative; width: 95%; max-width: 1200px; margin: 20px auto; background: white; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); overflow: hidden; display: block;">
        <form id="formInstrutor" onsubmit="return false;">
            <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-bottom: none; padding: 0.75rem 1.5rem; flex-shrink: 0;">
                <h5 class="modal-title" id="modalTitle" style="color: white; font-weight: 600; font-size: 1.25rem; margin: 0;">
                    <i class="fas fa-user-tie me-2"></i>Novo Instrutor
                </h5>
                <button type="button" class="btn-close" onclick="fecharModalInstrutor()" style="filter: invert(1); background: none; border: none; font-size: 1.25rem; color: white; opacity: 0.8; cursor: pointer;">&times;</button>
            </div>
            <div class="modal-body" style="overflow-y: auto; padding: 1rem; max-height: 70vh;">
                <!-- Conteúdo do modal mantido da versão original -->
                <input type="hidden" name="acao" id="acaoInstrutor" value="novo">
                <input type="hidden" name="instrutor_id" id="instrutor_id" value="">
                
                <div class="container-fluid" style="padding: 0;">
                    <!-- Seção 1: Informações Básicas -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                <i class="fas fa-user-tie me-1"></i>Informações Básicas
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="nome" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Nome Completo *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required 
                                       placeholder="Nome completo" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="cpf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CPF *</label>
                                <input type="text" class="form-control" id="cpf" name="cpf" required 
                                       placeholder="000.000.000-00" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="cnh" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CNH *</label>
                                <input type="text" class="form-control" id="cnh" name="cnh" required 
                                       placeholder="Número da CNH" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Resto do formulário mantido da versão original -->
                    <div class="row mb-2">
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="data_nascimento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data Nascimento *</label>
                                <input type="text" class="form-control" id="data_nascimento" name="data_nascimento" required 
                                       placeholder="dd/mm/aaaa" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="telefone" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone *</label>
                                <input type="text" class="form-control" id="telefone" name="telefone" required 
                                       placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="email" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required 
                                       placeholder="email@exemplo.com" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção 2: Informações Profissionais -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                <i class="fas fa-briefcase me-1"></i>Informações Profissionais
                            </h6>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="credencial" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Credencial *</label>
                                <input type="text" class="form-control" id="credencial" name="credencial" required 
                                       placeholder="Número da credencial" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="cfc_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CFC *</label>
                                <select class="form-select" id="cfc_id" name="cfc_id" required style="padding: 0.4rem; font-size: 0.85rem;">
                                    <option value="">Selecione o CFC</option>
                                    <!-- Preenchido via JavaScript -->
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-1">
                                <label for="categorias" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Categorias *</label>
                                <div class="form-check-group" style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="A" id="catA">
                                        <label class="form-check-label" for="catA" style="font-size: 0.8rem;">A</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="B" id="catB">
                                        <label class="form-check-label" for="catB" style="font-size: 0.8rem;">B</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="C" id="catC">
                                        <label class="form-check-label" for="catC" style="font-size: 0.8rem;">C</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="D" id="catD">
                                        <label class="form-check-label" for="catD" style="font-size: 0.8rem;">D</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="categorias[]" value="E" id="catE">
                                        <label class="form-check-label" for="catE" style="font-size: 0.8rem;">E</label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Seção 3: Endereço -->
                    <div class="row mb-2">
                        <div class="col-12">
                            <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                <i class="fas fa-map-marker-alt me-1"></i>Endereço
                            </h6>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-1">
                                <label for="cep" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CEP *</label>
                                <input type="text" class="form-control" id="cep" name="cep" required 
                                       placeholder="00000-000" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-1">
                                <label for="endereco" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Endereço *</label>
                                <input type="text" class="form-control" id="endereco" name="endereco" required 
                                       placeholder="Rua, número, bairro" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="mb-1">
                                <label for="cidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Cidade *</label>
                                <input type="text" class="form-control" id="cidade" name="cidade" required 
                                       placeholder="Cidade" style="padding: 0.4rem; font-size: 0.85rem;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer" style="background: var(--gray-50); border-top: 1px solid var(--gray-200); padding: 0.75rem 1.5rem; display: flex; justify-content: flex-end; gap: 0.5rem;">
                <button type="button" class="btn btn-secondary" onclick="fecharModalInstrutor()">Cancelar</button>
                <button type="submit" class="btn btn-primary" onclick="salvarInstrutor()">
                    <i class="fas fa-save"></i> Salvar Instrutor
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// JavaScript otimizado para a página de instrutores
let searchTimeout;
let currentFilters = {};
let selectedInstructors = new Set();
let currentPage = 1;
let itemsPerPage = 25;
let totalInstructors = 0;

// Debounce para busca
function buscarComDebounce() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        aplicarFiltros();
    }, 300);
}

// Handler para teclas na busca
function handleSearchKeydown(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        aplicarFiltros();
    } else if (event.key === 'Escape') {
        event.target.value = '';
        aplicarFiltros();
    }
}

// Aplicar filtros
function aplicarFiltros() {
    currentFilters = {
        status: document.getElementById('filtroStatus').value,
        cfc: document.getElementById('filtroCFC').value,
        categoria: document.getElementById('filtroCategoria').value,
        busca: document.getElementById('buscaInstrutor').value
    };
    
    atualizarChipsFiltros();
    carregarInstrutores();
}

// Atualizar chips de filtros
function atualizarChipsFiltros() {
    const chipsContainer = document.getElementById('filterChips');
    chipsContainer.innerHTML = '';
    
    let hasFilters = false;
    
    Object.entries(currentFilters).forEach(([key, value]) => {
        if (value) {
            hasFilters = true;
            const chip = document.createElement('div');
            chip.className = 'filter-chip';
            
            let label = '';
            switch(key) {
                case 'status':
                    label = value === '1' ? 'Ativo' : value === '0' ? 'Inativo' : 'Em férias';
                    break;
                case 'cfc':
                    label = `CFC: ${value}`;
                    break;
                case 'categoria':
                    label = `Categoria: ${value}`;
                    break;
                case 'busca':
                    label = `Busca: ${value}`;
                    break;
            }
            
            chip.innerHTML = `
                ${label}
                <button class="remove-chip" onclick="removerFiltro('${key}')">
                    <i class="fas fa-times"></i>
                </button>
            `;
            chipsContainer.appendChild(chip);
        }
    });
    
    chipsContainer.style.display = hasFilters ? 'flex' : 'none';
}

// Remover filtro específico
function removerFiltro(key) {
    switch(key) {
        case 'status':
            document.getElementById('filtroStatus').value = '';
            break;
        case 'cfc':
            document.getElementById('filtroCFC').value = '';
            break;
        case 'categoria':
            document.getElementById('filtroCategoria').value = '';
            break;
        case 'busca':
            document.getElementById('buscaInstrutor').value = '';
            break;
    }
    aplicarFiltros();
}

// Limpar todos os filtros
function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaInstrutor').value = '';
    aplicarFiltros();
}

// Carregar instrutores
function carregarInstrutores() {
    mostrarEstado('loading');
    
    // Simular carregamento (substituir por chamada real à API)
    setTimeout(() => {
        const instrutores = gerarInstrutoresMock();
        renderizarInstrutores(instrutores);
        atualizarKPIs(instrutores);
        atualizarContagemResultados(instrutores.length);
        mostrarEstado('table');
    }, 500);
}

// Gerar dados mock para demonstração
function gerarInstrutoresMock() {
    const nomes = ['João Silva', 'Maria Santos', 'Pedro Oliveira', 'Ana Costa', 'Carlos Lima'];
    const cfcs = ['CFC Bom Conselho', 'CFC Centro', 'CFC Norte'];
    const categorias = ['A', 'B', 'C', 'D', 'E'];
    const status = ['ativo', 'inativo', 'ferias'];
    
    return Array.from({length: 25}, (_, i) => ({
        id: i + 1,
        nome: nomes[i % nomes.length] + ' ' + (i + 1),
        email: `instrutor${i + 1}@exemplo.com`,
        cfc: cfcs[i % cfcs.length],
        categoria: categorias[i % categorias.length],
        status: status[i % status.length],
        aulasHoje: Math.floor(Math.random() * 8),
        ocupacao: Math.floor(Math.random() * 100),
        ultimaAtividade: new Date(Date.now() - Math.random() * 7 * 24 * 60 * 60 * 1000).toLocaleDateString('pt-BR')
    }));
}

// Renderizar instrutores na tabela
function renderizarInstrutores(instrutores) {
    const tbody = document.getElementById('tabelaInstrutoresBody');
    tbody.innerHTML = '';
    
    instrutores.forEach(instrutor => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <input type="checkbox" class="form-check-input instructor-checkbox" 
                       value="${instrutor.id}" onchange="toggleInstructorSelection(${instrutor.id})">
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-circle me-2">${instrutor.nome.charAt(0)}</div>
                    <div>
                        <div class="fw-semibold">${instrutor.nome}</div>
                        <small class="text-muted">${instrutor.email}</small>
                    </div>
                </div>
            </td>
            <td>
                <span class="badge bg-primary">${instrutor.categoria}</span>
            </td>
            <td>${instrutor.cfc}</td>
            <td>
                <span class="status-pill ${instrutor.status}" onclick="toggleStatus(${instrutor.id})">
                    ${instrutor.status === 'ativo' ? 'Ativo' : instrutor.status === 'inativo' ? 'Inativo' : 'Em férias'}
                </span>
            </td>
            <td>
                <span class="badge bg-info">${instrutor.aulasHoje}</span>
            </td>
            <td>
                <div class="d-flex align-items-center">
                    <div class="progress flex-grow-1 me-2" style="height: 6px;">
                        <div class="progress-bar" style="width: ${instrutor.ocupacao}%"></div>
                    </div>
                    <small class="text-muted">${instrutor.ocupacao}%</small>
                </div>
            </td>
            <td>
                <small class="text-muted">${instrutor.ultimaAtividade}</small>
            </td>
            <td>
                <div class="action-menu">
                    <button class="action-kebab" onclick="toggleActionMenu(${instrutor.id})">
                        <i class="fas fa-ellipsis-v"></i>
                    </button>
                    <div class="action-dropdown" id="actionMenu${instrutor.id}">
                        <a href="#" class="dropdown-item" onclick="verInstrutor(${instrutor.id})">
                            <i class="fas fa-eye"></i> Ver
                        </a>
                        <a href="#" class="dropdown-item" onclick="editarInstrutor(${instrutor.id})">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="#" class="dropdown-item" onclick="excluirInstrutor(${instrutor.id})">
                            <i class="fas fa-trash"></i> Excluir
                        </a>
                    </div>
                </div>
            </td>
        `;
        tbody.appendChild(row);
    });
}

// Atualizar KPIs
function atualizarKPIs(instrutores) {
    const total = instrutores.length;
    const ativos = instrutores.filter(i => i.status === 'ativo').length;
    const inativos = instrutores.filter(i => i.status === 'inativo').length;
    const ferias = instrutores.filter(i => i.status === 'ferias').length;
    
    document.getElementById('totalInstrutores').textContent = total;
    document.getElementById('instrutoresAtivos').textContent = ativos;
    document.getElementById('instrutoresInativos').textContent = inativos;
    document.getElementById('instrutoresFerias').textContent = ferias;
}

// Atualizar contagem de resultados
function atualizarContagemResultados(count) {
    document.getElementById('resultsCount').textContent = `${count} instrutor${count !== 1 ? 'es' : ''} encontrado${count !== 1 ? 's' : ''}`;
    document.getElementById('lastUpdated').textContent = `Atualizado em ${new Date().toLocaleTimeString('pt-BR')}`;
}

// Mostrar estado da tabela
function mostrarEstado(estado) {
    document.getElementById('tabelaInstrutores').style.display = estado === 'table' ? 'table' : 'none';
    document.getElementById('tableEmpty').style.display = estado === 'empty' ? 'block' : 'none';
    document.getElementById('tableLoading').style.display = estado === 'loading' ? 'block' : 'none';
    document.getElementById('tableError').style.display = estado === 'error' ? 'block' : 'none';
}

// Toggle seleção de instrutor
function toggleInstructorSelection(id) {
    if (selectedInstructors.has(id)) {
        selectedInstructors.delete(id);
    } else {
        selectedInstructors.add(id);
    }
    atualizarBulkActions();
}

// Toggle selecionar todos
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll').checked;
    const checkboxes = document.querySelectorAll('.instructor-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll;
        const id = parseInt(checkbox.value);
        if (selectAll) {
            selectedInstructors.add(id);
        } else {
            selectedInstructors.delete(id);
        }
    });
    
    atualizarBulkActions();
}

// Atualizar ações em massa
function atualizarBulkActions() {
    const bulkActions = document.getElementById('bulkActions');
    const selectedCount = document.getElementById('selectedCount');
    
    if (selectedInstructors.size > 0) {
        bulkActions.style.display = 'flex';
        selectedCount.textContent = selectedInstructors.size;
    } else {
        bulkActions.style.display = 'none';
    }
}

// Ação em massa
function bulkAction(action) {
    const ids = Array.from(selectedInstructors);
    
    switch(action) {
        case 'activate':
            mostrarToast('Instrutores ativados com sucesso!', 'success');
            break;
        case 'deactivate':
            mostrarToast('Instrutores desativados com sucesso!', 'success');
            break;
        case 'message':
            mostrarToast('Mensagem enviada para os instrutores!', 'success');
            break;
    }
    
    selectedInstructors.clear();
    atualizarBulkActions();
    carregarInstrutores();
}

// Toggle status do instrutor
function toggleStatus(id) {
    mostrarToast('Status atualizado com sucesso!', 'success');
    carregarInstrutores();
}

// Toggle menu de ações
function toggleActionMenu(id) {
    const menus = document.querySelectorAll('.action-dropdown');
    menus.forEach(menu => {
        if (menu.id !== `actionMenu${id}`) {
            menu.classList.remove('show');
        }
    });
    
    const menu = document.getElementById(`actionMenu${id}`);
    menu.classList.toggle('show');
}

// Fechar menus ao clicar fora
document.addEventListener('click', function(event) {
    if (!event.target.closest('.action-menu')) {
        document.querySelectorAll('.action-dropdown').forEach(menu => {
            menu.classList.remove('show');
        });
    }
});

// Mostrar toast
function mostrarToast(message, type = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'} me-2"></i>
            <span>${message}</span>
        </div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 3000);
}

// Funções do modal (mantidas da versão original)
function abrirModalInstrutor() {
    document.getElementById('modalInstrutor').style.display = 'block';
    document.getElementById('modalTitle').innerHTML = '<i class="fas fa-user-tie me-2"></i>Novo Instrutor';
    document.getElementById('acaoInstrutor').value = 'novo';
    document.getElementById('formInstrutor').reset();
}

function fecharModalInstrutor() {
    document.getElementById('modalInstrutor').style.display = 'none';
}

function salvarInstrutor() {
    mostrarToast('Instrutor salvo com sucesso!', 'success');
    fecharModalInstrutor();
    carregarInstrutores();
}

// Funções de exportação e impressão
function exportarInstrutores() {
    mostrarToast('Exportação iniciada!', 'info');
}

function imprimirInstrutores() {
    window.print();
}

// Inicializar página
document.addEventListener('DOMContentLoaded', function() {
    carregarInstrutores();
    
    // Atualizar tempo a cada minuto
    setInterval(() => {
        document.getElementById('lastUpdated').textContent = `Atualizado em ${new Date().toLocaleTimeString('pt-BR')}`;
    }, 60000);
});
</script>

<style>
/* Estilos adicionais específicos */
.avatar-circle {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: var(--primary-color);
    color: var(--white);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    font-size: 0.875rem;
}

.progress {
    background-color: var(--gray-200);
    border-radius: var(--border-radius);
}

.progress-bar {
    background: linear-gradient(90deg, var(--success-color), #10b981);
    border-radius: var(--border-radius);
}

/* Responsividade para mobile */
@media (max-width: 768px) {
    .table-responsive {
        display: none;
    }
    
    .mobile-cards {
        display: block;
    }
    
    .mobile-card {
        background: var(--white);
        border: 1px solid var(--gray-200);
        border-radius: var(--border-radius-lg);
        padding: 1rem;
        margin-bottom: 0.75rem;
        box-shadow: var(--shadow-sm);
    }
    
    .mobile-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 0.75rem;
    }
    
    .mobile-card-name {
        font-weight: 600;
        color: var(--gray-900);
        font-size: 1rem;
    }
    
    .mobile-card-status {
        margin-left: 0.5rem;
    }
    
    .mobile-card-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 0.5rem;
        margin-bottom: 0.75rem;
        font-size: 0.875rem;
        color: var(--gray-600);
    }
    
    .mobile-card-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.5rem;
    }
}
</style>
