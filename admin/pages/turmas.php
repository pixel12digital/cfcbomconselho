<?php
/**
 * Página de Gestão de Turmas - Template
 * Sistema CFC - Bom Conselho
 */

// Verificar permissões (as variáveis já estão disponíveis do admin/index.php)
if (!$isAdmin && !$isInstrutor) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Incluir dependências específicas
require_once __DIR__ . '/../includes/turma_manager.php';

// Instanciar o gerenciador de turmas
$turmaManager = new TurmaManager();

// Buscar instrutores para o dropdown
try {
    $instrutores = $db->fetchAll("
        SELECT id, nome, email 
        FROM instrutores 
        WHERE ativo = 1 
        ORDER BY nome ASC
    ");
} catch (Exception $e) {
    $instrutores = [];
}

// Buscar estatísticas
try {
    $cfcIdParaStats = $isAdmin ? null : ($user['cfc_id'] ?? 1); // Admin vê estatísticas de todos os CFCs
    $stats = $turmaManager->obterEstatisticas($cfcIdParaStats);
    $estatisticas = $stats['sucesso'] ? $stats['dados'] : [];
} catch (Exception $e) {
    $estatisticas = [];
}

// Processar filtros
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'status' => $_GET['status'] ?? '',
    'instrutor_id' => $_GET['instrutor_id'] ?? '',
    'cfc_id' => $isAdmin ? null : ($user['cfc_id'] ?? 1) // Admin vê todas as turmas
];

// Buscar turmas com filtros
try {
    $resultado = $turmaManager->listarTurmas($filtros);
    $turmas = (is_array($resultado) && isset($resultado['sucesso']) && $resultado['sucesso']) 
              ? $resultado['dados'] 
              : [];
} catch (Exception $e) {
    $turmas = [];
}

// Processar ações
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$turma_id = $_GET['turma_id'] ?? null;

if ($acao === 'excluir' && $turma_id) {
    if ($turmaManager->excluirTurma($turma_id)) {
        echo '<div class="alert alert-success">Turma excluída com sucesso!</div>';
    } else {
        echo '<div class="alert alert-danger">Erro ao excluir turma.</div>';
    }
}

if ($acao === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosTurma = [
        'nome' => $_POST['nome'] ?? '',
        'instrutor_id' => $_POST['instrutor_id'] ?? '',
        'tipo_aula' => $_POST['tipo_aula'] ?? '',
        'categoria_cnh' => $_POST['categoria_cnh'] ?? null,
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
        'status' => $_POST['status'] ?? 'agendado',
        'observacoes' => $_POST['observacoes'] ?? null,
        'cfc_id' => $isAdmin ? 36 : ($user['cfc_id'] ?? 1) // Admin usa CFC 36 por padrão
    ];
    
    $resultado = $turmaManager->criarTurma($dadosTurma);
    
    if ($resultado['sucesso']) {
        echo '<div class="alert alert-success">Turma criada com sucesso!</div>';
        // Redirecionar para evitar reenvio do formulário
        header('Location: ?page=turmas&sucesso=1');
        exit;
    } else {
        echo '<div class="alert alert-danger">Erro ao criar turma: ' . ($resultado['mensagem'] ?? 'Erro desconhecido') . '</div>';
    }
}

// Verificar se houve sucesso na criação
if (isset($_GET['sucesso']) && $_GET['sucesso'] == '1') {
    echo '<div class="alert alert-success">Turma criada com sucesso!</div>';
}
?>

<style>
/* Estilos específicos para turmas */
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}
.stats-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}
.stats-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
.stats-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-ativa { background-color: #d4edda; color: #155724; }
.status-inativa { background-color: #f8d7da; color: #721c24; }
.status-pendente { background-color: #fff3cd; color: #856404; }
.status-indefinido { background-color: #e2e3e5; color: #383d41; }

/* Correção para overlay do modal */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

/* Garantir que o body não mantenha classes de modal */
body.modal-open {
    overflow: hidden;
}

/* Limpar backdrop quando modal é fechado */
body:not(.modal-open) .modal-backdrop {
    display: none !important;
}
</style>

<!-- Header da página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-chalkboard-teacher me-2"></i>Gestão de Turmas</h2>
        <p class="text-muted mb-0">Gerencie as turmas teóricas do sistema</p>
    </div>
    <button class="btn btn-primary" onclick="novaTurma()">
        <i class="fas fa-plus me-1"></i>Nova Turma
    </button>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Total de Turmas</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['total_turmas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Turmas Ativas</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['turmas_ativas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Alunos Matriculados</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['total_matriculas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card info">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Próximas Aulas</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['proximas_aulas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-calendar-alt fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="turmas">
            <div class="col-md-3">
                <label for="busca" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busca" name="busca" 
                       placeholder="Nome da turma..." value="<?php echo htmlspecialchars($filtros['busca']); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="ativa" <?php echo $filtros['status'] === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                    <option value="inativa" <?php echo $filtros['status'] === 'inativa' ? 'selected' : ''; ?>>Inativa</option>
                    <option value="pendente" <?php echo $filtros['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="instrutor_id" class="form-label">Instrutor</label>
                <select class="form-select" id="instrutor_id" name="instrutor_id">
                    <option value="">Todos</option>
                    <?php foreach ($instrutores as $instrutor): ?>
                    <option value="<?php echo $instrutor['id']; ?>" <?php echo $filtros['instrutor_id'] == $instrutor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($instrutor['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $filtros['data_inicio']; ?>">
            </div>
            <div class="col-md-2">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $filtros['data_fim']; ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="?page=turmas" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Turmas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Turmas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Instrutor</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Alunos</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turmas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhuma turma encontrada</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($turmas as $turma): ?>
                    <?php if (is_array($turma)): ?>
                    <tr>
                        <td><?php echo $turma['id'] ?? 'N/A'; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($turma['nome'] ?? 'Nome não definido'); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($turma['descricao'] ?? ''); ?></small>
                        </td>
                        <td>
                            <?php if (!empty($turma['instrutor_nome'])): ?>
                            <strong><?php echo htmlspecialchars($turma['instrutor_nome']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($turma['instrutor_email'] ?? ''); ?></small>
                            <?php else: ?>
                            <span class="text-muted">Não definido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($turma['data_inicio'])): ?>
                                <?php echo date('d/m/Y', strtotime($turma['data_inicio'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Não definida</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($turma['data_fim'])): ?>
                                <?php echo date('d/m/Y', strtotime($turma['data_fim'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Não definida</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $turma['total_alunos'] ?? 0; ?> alunos
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $turma['status'] ?? 'indefinido'; ?>">
                                <?php echo ucfirst($turma['status'] ?? 'Indefinido'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="visualizarTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="editarTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="gerenciarAlunos(<?php echo $turma['id'] ?? 0; ?>)" title="Gerenciar Alunos">
                                    <i class="fas fa-users"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="calendarioTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Calendário">
                                    <i class="fas fa-calendar"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="excluirTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Nova Turma -->
<div class="modal fade" id="modalNovaTurma" tabindex="-1" aria-labelledby="modalNovaTurmaLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaTurmaLabel">
                    <i class="fas fa-plus me-2"></i>Nova Turma
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovaTurma" method="POST" action="?page=turmas">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome da Turma *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="instrutor_id" class="form-label">Instrutor *</label>
                                <select class="form-select" id="instrutor_id" name="instrutor_id" required>
                                    <option value="">Selecione um instrutor</option>
                                    <?php foreach ($instrutores as $instrutor): ?>
                                    <option value="<?php echo $instrutor['id']; ?>">
                                        <?php echo htmlspecialchars($instrutor['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tipo_aula" class="form-label">Tipo de Aula *</label>
                                <select class="form-select" id="tipo_aula" name="tipo_aula" required onchange="atualizarPreviewDisciplinas()">
                                    <option value="">Selecione o tipo</option>
                                    <option value="teorica">Teórica</option>
                                    <option value="pratica">Prática</option>
                                    <option value="mista">Mista</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categoria_cnh" class="form-label">Categoria CNH</label>
                                <select class="form-select" id="categoria_cnh" name="categoria_cnh" onchange="atualizarPreviewDisciplinas()">
                                    <option value="">Selecione a categoria</option>
                                    <option value="A">A - Motocicleta</option>
                                    <option value="B">B - Automóvel</option>
                                    <option value="C">C - Caminhão</option>
                                    <option value="D">D - Ônibus</option>
                                    <option value="E">E - Carreta</option>
                                    <option value="AB">AB - A + B</option>
                                    <option value="AC">AC - A + C</option>
                                    <option value="AD">AD - A + D</option>
                                    <option value="AE">AE - A + E</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="agendado">Agendado</option>
                                    <option value="ativa">Ativa</option>
                                    <option value="concluida">Concluída</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview das Disciplinas (apenas para aulas teóricas) -->
                    <div id="preview_disciplinas" class="mb-3" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    Preview das Disciplinas Teóricas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="disciplinas_preview_content">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        Carregando disciplinas...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_inicio" class="form-label">Data de Início *</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_fim" class="form-label">Data de Fim *</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Observações sobre a turma..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Turma
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function novaTurma() {
    // Abrir modal para nova turma
    const modalElement = document.getElementById('modalNovaTurma');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // Limpar formulário ao abrir
    document.getElementById('formNovaTurma').reset();
    
    // Mostrar modal
    modal.show();
    
    // Garantir que o backdrop seja removido quando o modal for fechado
    modalElement.addEventListener('hidden.bs.modal', function () {
        // Remover qualquer classe de backdrop que possa ter ficado
        document.body.classList.remove('modal-open');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
    });
}

// Validação do formulário
document.getElementById('formNovaTurma').addEventListener('submit', function(e) {
    const dataInicio = document.getElementById('data_inicio').value;
    const dataFim = document.getElementById('data_fim').value;
    
    if (dataInicio && dataFim && new Date(dataInicio) >= new Date(dataFim)) {
        e.preventDefault();
        alert('A data de fim deve ser posterior à data de início.');
        return false;
    }
    
    // Mostrar loading no botão
    const btnSubmit = this.querySelector('button[type="submit"]');
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSubmit.disabled = true;
});

// Função global para limpar backdrop
function limparBackdrop() {
    document.body.classList.remove('modal-open');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Forçar reflow para garantir limpeza
    document.body.style.overflow = '';
}

// Adicionar event listeners para todos os botões de fechar
document.addEventListener('DOMContentLoaded', function() {
    // Botões de fechar do modal
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', limparBackdrop);
    });
    
    // Clicar fora do modal também deve limpar
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            limparBackdrop();
        }
    });
    
    // Tecla ESC também deve limpar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            limparBackdrop();
        }
    });
});

function visualizarTurma(id) {
    // Implementar visualização da turma
    alert('Visualização da turma ' + id + ' será implementada em breve.');
}

function editarTurma(id) {
    // Implementar edição da turma
    alert('Edição da turma ' + id + ' será implementada em breve.');
}

function gerenciarAlunos(id) {
    // Implementar gerenciamento de alunos
    alert('Gerenciamento de alunos da turma ' + id + ' será implementado em breve.');
}

function calendarioTurma(id) {
    // Implementar calendário da turma
    alert('Calendário da turma ' + id + ' será implementado em breve.');
}

function excluirTurma(id) {
    if (confirm('Deseja realmente excluir esta turma?')) {
        if (confirm('Esta ação não pode ser desfeita. Continuar?')) {
            window.location.href = `?page=turmas&acao=excluir&turma_id=${id}`;
        }
    }
}

// Função para atualizar preview das disciplinas
function atualizarPreviewDisciplinas() {
    const tipoAula = document.getElementById('tipo_aula').value;
    const categoriaCNH = document.getElementById('categoria_cnh').value;
    const previewDiv = document.getElementById('preview_disciplinas');
    const contentDiv = document.getElementById('disciplinas_preview_content');
    
    if (tipoAula === 'teorica' && categoriaCNH) {
        previewDiv.style.display = 'block';
        contentDiv.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-spinner fa-spin me-2"></i>
                Carregando disciplinas para categoria ${categoriaCNH}...
            </div>
        `;
        
        // Buscar preview das disciplinas
        fetch(`api/turma-grade-generator.php?turma_id=0&action=preview&categoria=${categoriaCNH}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderizarPreviewDisciplinas(data.data.disciplinas);
                } else {
                    contentDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erro ao carregar disciplinas: ${error.message}
                    </div>
                `;
            });
    } else {
        previewDiv.style.display = 'none';
    }
}

// Função para renderizar o preview das disciplinas
function renderizarPreviewDisciplinas(disciplinas) {
    const contentDiv = document.getElementById('disciplinas_preview_content');
    
    if (disciplinas.length === 0) {
        contentDiv.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Nenhuma disciplina configurada para esta categoria.
            </div>
        `;
        return;
    }
    
    const totalAulas = disciplinas.reduce((total, disc) => total + disc.aulas, 0);
    
    let html = `
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Total de aulas:</strong> ${totalAulas} aulas teóricas
                </div>
            </div>
        </div>
        <div class="row">
    `;
    
    disciplinas.forEach(disciplina => {
        const corClasses = {
            'primary': 'bg-primary',
            'danger': 'bg-danger', 
            'success': 'bg-success',
            'warning': 'bg-warning',
            'info': 'bg-info'
        };
        
        html += `
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="card border-${disciplina.cor} h-100">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <i class="${disciplina.icone} text-${disciplina.cor} me-2" style="font-size: 1.2em;"></i>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-0" style="font-size: 0.9em;">${disciplina.nome}</h6>
                            </div>
                            <span class="badge ${corClasses[disciplina.cor]} text-white" style="font-size: 0.8em;">
                                ${disciplina.aulas} aulas
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
        </div>
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-lightbulb me-1"></i>
                A grade será gerada automaticamente com base nesta configuração.
            </small>
        </div>
    `;
    
    contentDiv.innerHTML = html;
}
</script>
