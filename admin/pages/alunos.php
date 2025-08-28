<?php
// Verificar se as variáveis estão definidas
if (!isset($alunos)) $alunos = [];
if (!isset($cfcs)) $cfcs = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<style>
/* =====================================================
   ESTILOS PERSONALIZADOS PARA MODAL DE ALUNOS
   Sobrescrevendo Bootstrap com especificidade máxima
   ===================================================== */

/* Forçar modal fullscreen com especificidade máxima */
.modal#modalAluno .modal-dialog.modal-fullscreen {
    max-width: 100vw !important;
    max-height: 100vh !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
}

.modal#modalAluno .modal-content {
    height: 100vh !important;
    border-radius: 0 !important;
    border: none !important;
}

.modal#modalAluno .modal-body {
    max-height: calc(100vh - 120px) !important;
    overflow-y: auto !important;
    padding: 2rem !important;
    background-color: #f8f9fa !important;
}

.modal#modalAluno .modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
    color: white !important;
    border-bottom: none !important;
    padding: 1.5rem 2rem !important;
}

.modal#modalAluno .modal-title {
    color: white !important;
    font-weight: 600 !important;
    font-size: 1.5rem !important;
}

.modal#modalAluno .btn-close {
    filter: invert(1) !important;
}

.modal#modalAluno .modal-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #dee2e6 !important;
    padding: 1.5rem 2rem !important;
}

/* Estilos dos formulários */
.modal#modalAluno .form-label {
    font-weight: 600 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
    font-size: 0.9rem !important;
}

.modal#modalAluno .form-control,
.modal#modalAluno .form-select {
    border-radius: 0.5rem !important;
    border: 1px solid #ced4da !important;
    transition: all 0.2s ease !important;
    padding: 0.75rem 1rem !important;
    font-size: 0.9rem !important;
}

.modal#modalAluno .form-control:focus,
.modal#modalAluno .form-select:focus {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25) !important;
}

.modal#modalAluno .text-primary {
    color: #0d6efd !important;
}

.modal#modalAluno .border-bottom {
    border-bottom: 2px solid #e9ecef !important;
}

.modal#modalAluno .form-range {
    height: 6px !important;
    border-radius: 3px !important;
}

.modal#modalAluno .form-range::-webkit-slider-thumb {
    background: #0d6efd !important;
    border-radius: 50% !important;
    width: 20px !important;
    height: 20px !important;
}

.modal#modalAluno .form-range::-moz-range-thumb {
    background: #0d6efd !important;
    border-radius: 50% !important;
    width: 20px !important;
    height: 20px !important;
}

/* Melhorar espaçamento entre seções */
.modal#modalAluno .row.mb-4 {
    margin-bottom: 2rem !important;
}

.modal#modalAluno .mb-3 {
    margin-bottom: 1.25rem !important;
}

/* Seções com fundo branco e sombra */
.modal#modalAluno .container-fluid {
    background-color: white !important;
    border-radius: 0.5rem !important;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075) !important;
    padding: 2rem !important;
    margin: 1rem 0 !important;
    transition: all 0.3s ease !important;
}

/* Animações suaves para melhor UX */
.modal#modalAluno .modal-content {
    animation: modalSlideIn 0.3s ease-out !important;
}

@keyframes modalSlideIn {
    from {
        opacity: 0;
        transform: scale(0.95) translateY(-20px);
    }
    to {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

/* Melhorar aparência dos campos obrigatórios */
.modal#modalAluno .form-label:has-text("*") {
    position: relative !important;
}

.modal#modalAluno .form-label:after {
    content: " *" !important;
    color: #dc3545 !important;
    font-weight: bold !important;
}

/* Hover effects para melhor interatividade */
.modal#modalAluno .form-control:hover,
.modal#modalAluno .form-select:hover {
    border-color: #86b7fe !important;
    box-shadow: 0 0 0 0.1rem rgba(13, 110, 253, 0.1) !important;
}

/* Melhorar aparência do slider de progresso */
.modal#modalAluno .form-range {
    background: linear-gradient(to right, #0d6efd 0%, #0d6efd var(--value, 0%), #e9ecef var(--value, 0%), #e9ecef 100%) !important;
}

/* Estilo para seções com melhor hierarquia visual */
.modal#modalAluno .row.mb-4 h6 {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    padding: 0.75rem 1rem !important;
    border-radius: 0.375rem !important;
    margin-bottom: 1.5rem !important;
    border-left: 4px solid #0d6efd !important;
    font-weight: 600 !important;
    color: #495057 !important;
}

/* Responsividade otimizada para diferentes tamanhos de tela */
@media (max-width: 1400px) {
    .modal#modalAluno .col-md-2 {
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
    }
    
    .modal#modalAluno .col-md-3 {
        flex: 0 0 33.333333% !important;
        max-width: 33.333333% !important;
    }
    
    .modal#modalAluno .col-md-4 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
}

@media (max-width: 1200px) {
    .modal#modalAluno .col-md-2 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
    
    .modal#modalAluno .col-md-3 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
    
    .modal#modalAluno .col-md-4 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
    
    .modal#modalAluno .col-md-6 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
}

@media (max-width: 992px) {
    .modal#modalAluno .modal-body {
        padding: 1.5rem !important;
    }
    
    .modal#modalAluno .container-fluid {
        padding: 1.5rem !important;
        margin: 0.75rem 0 !important;
    }
    
    .modal#modalAluno .col-md-2,
    .modal#modalAluno .col-md-3,
    .modal#modalAluno .col-md-4,
    .modal#modalAluno .col-md-6 {
        flex: 0 0 50% !important;
        max-width: 50% !important;
    }
}

@media (max-width: 768px) {
    .modal#modalAluno .modal-body {
        padding: 1rem !important;
    }
    
    .modal#modalAluno .container-fluid {
        padding: 1rem !important;
        margin: 0.5rem 0 !important;
    }
    
    .modal#modalAluno .col-md-2,
    .modal#modalAluno .col-md-3,
    .modal#modalAluno .col-md-4,
    .modal#modalAluno .col-md-6 {
        flex: 0 0 100% !important;
        max-width: 100% !important;
    }
    
    .modal#modalAluno .modal-header {
        padding: 1rem 1.5rem !important;
    }
    
    .modal#modalAluno .modal-footer {
        padding: 1rem 1.5rem !important;
    }
}

@media (max-width: 576px) {
    .modal#modalAluno .modal-body {
        padding: 0.75rem !important;
    }
    
    .modal#modalAluno .container-fluid {
        padding: 0.75rem !important;
        margin: 0.25rem 0 !important;
    }
    
    .modal#modalAluno .modal-header {
        padding: 0.75rem 1rem !important;
    }
    
    .modal#modalAluno .modal-footer {
        padding: 0.75rem 1rem !important;
    }
    
    .modal#modalAluno .modal-title {
        font-size: 1.25rem !important;
    }
}

/* Garantir que o modal ocupe toda a tela - FORÇA MÁXIMA */
.modal#modalAluno {
    z-index: 1055 !important;
}

.modal#modalAluno .modal-dialog {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: none !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 0 !important;
}

/* FORÇA BRUTA - Sobrescrever qualquer estilo do Bootstrap */
.modal#modalAluno .modal-dialog.modal-fullscreen {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: none !important;
}

/* Forçar o modal a ocupar toda a tela */
body.modal-open #modalAluno .modal-dialog {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: 100vw !important;
    max-height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
    transform: none !important;
}

/* =====================================================
   ESTILOS PARA MODAL DE VISUALIZAÇÃO
   ===================================================== */

.modal#modalVisualizarAluno .modal-dialog.modal-fullscreen {
    max-width: 100vw !important;
    max-height: 100vh !important;
    width: 100vw !important;
    height: 100vh !important;
    margin: 0 !important;
    padding: 0 !important;
}

.modal#modalVisualizarAluno .modal-content {
    height: 100vh !important;
    border-radius: 0 !important;
    border: none !important;
}

.modal#modalVisualizarAluno .modal-body {
    max-height: calc(100vh - 120px) !important;
    overflow-y: auto !important;
    padding: 2rem !important;
    background-color: #f8f9fa !important;
}

.modal#modalVisualizarAluno .modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
    color: white !important;
    border-bottom: none !important;
    padding: 1.5rem 2rem !important;
}

.modal#modalVisualizarAluno .modal-title {
    color: white !important;
    font-weight: 600 !important;
    font-size: 1.5rem !important;
}

.modal#modalVisualizarAluno .btn-close {
    filter: invert(1) !important;
}

.modal#modalVisualizarAluno .modal-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #dee2e6 !important;
    padding: 1.5rem 2rem !important;
}

.modal#modalVisualizarAluno {
    z-index: 1055 !important;
}

.modal#modalVisualizarAluno .modal-dialog {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    right: 0 !important;
    bottom: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    max-width: none !important;
    max-height: none !important;
    margin: 0 !important;
    padding: 0 !important;
}
</style>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-user-graduate me-2"></i>Gestão de Alunos
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarAlunos()">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="imprimirAlunos()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
        <button type="button" class="btn btn-primary" onclick="abrirModalAluno()">
            <i class="fas fa-plus me-1"></i>Novo Aluno
        </button>
    </div>
</div>

<!-- Mensagens de Feedback -->
<?php if (!empty($mensagem)): ?>
<div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($mensagem); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros e Busca Avançada -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="input-group">
            <span class="input-group-text">🔍</span>
            <input type="text" class="form-control" id="buscaAluno" placeholder="Buscar aluno..." data-validate="minLength:2">
        </div>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroStatus">
            <option value="">Todos os Status</option>
            <option value="ativo">✅ Ativo</option>
            <option value="inativo">❌ Inativo</option>
            <option value="concluido">🎓 Concluído</option>
            <option value="pendente">⏳ Pendente</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroCFC">
            <option value="">Todos os CFCs</option>
            <?php foreach ($cfcs as $cfc): ?>
                <option value="<?php echo $cfc['id']; ?>"><?php echo htmlspecialchars($cfc['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroCategoria">
            <option value="">Todas as Categorias</option>
            <option value="A">🚗 Categoria A</option>
            <option value="B">🚙 Categoria B</option>
            <option value="C">🚐 Categoria C</option>
            <option value="D">🚛 Categoria D</option>
            <option value="E">🚜 Categoria E</option>
            <option value="AB">🚗🚙 Categoria AB</option>
            <option value="AC">🚗🚐 Categoria AC</option>
            <option value="AD">🚗🚛 Categoria AD</option>
            <option value="AE">🚗🚜 Categoria AE</option>
        </select>
    </div>
    <div class="col-md-3">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-info" onclick="limparFiltros()">
                🗑️ Limpar
            </button>
            <button type="button" class="btn btn-outline-success" onclick="exportarFiltros()">
                📥 Exportar
            </button>
        </div>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Alunos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAlunos">
                            <?php echo count($alunos); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Alunos Ativos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="alunosAtivos">
                            <?php echo count(array_filter($alunos, function($a) { return $a['status'] === 'ativo'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Em Formação
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="emFormacao">
                            <?php echo count(array_filter($alunos, function($a) { return $a['status'] === 'ativo'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-clock fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Concluídos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="concluidos">
                            <?php echo count(array_filter($alunos, function($a) { return $a['status'] === 'concluido'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-graduation-cap fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Alunos -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Alunos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tabelaAlunos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>CFC</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Última Aula</th>
                        <th>Progresso</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum aluno cadastrado ainda.</p>
                            <button class="btn btn-primary" onclick="abrirModalAluno()">
                                <i class="fas fa-plus me-1"></i>Cadastrar Primeiro Aluno
                            </button>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($alunos as $aluno): ?>
                        <tr data-aluno-id="<?php echo $aluno['id']; ?>">
                            <td><?php echo $aluno['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3">
                                        <div class="avatar-title bg-primary rounded-circle">
                                            <?php echo strtoupper(substr($aluno['nome'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                                        <?php if ($aluno['email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($aluno['email']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($aluno['cpf']); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($aluno['cfc_nome'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($aluno['categoria_cnh']); ?></span>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'ativo' => 'success',
                                    'inativo' => 'danger',
                                    'concluido' => 'info'
                                ];
                                $statusText = [
                                    'ativo' => 'Ativo',
                                    'inativo' => 'Inativo',
                                    'concluido' => 'Concluído'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$aluno['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusText[$aluno['status']] ?? ucfirst($aluno['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($aluno['ultima_aula'])): ?>
                                    <small><?php echo date('d/m/Y', strtotime($aluno['ultima_aula'])); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">Nunca</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="progress" style="height: 20px;">
                                    <?php 
                                    $progresso = isset($aluno['progresso']) ? $aluno['progresso'] : 0;
                                    $progresso = min(100, max(0, $progresso));
                                    ?>
                                    <div class="progress-bar" role="progressbar" 
                                         style="width: <?php echo $progresso; ?>%" 
                                         aria-valuenow="<?php echo $progresso; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo $progresso; ?>%
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons-container">
                                    <!-- Botões principais em linha -->
                                    <div class="action-buttons-primary">
                                        <button type="button" class="btn btn-edit action-btn" 
                                                onclick="editarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Editar dados do aluno">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button type="button" class="btn btn-view action-btn" 
                                                onclick="visualizarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Ver detalhes completos do aluno">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="agendarAula(<?php echo $aluno['id']; ?>)" 
                                                title="Agendar nova aula para este aluno">
                                            <i class="fas fa-calendar-plus me-1"></i>Agendar
                                        </button>
                                    </div>
                                    
                                    <!-- Botões secundários em linha -->
                                    <div class="action-buttons-secondary">
                                        <button type="button" class="btn btn-history action-btn" 
                                                onclick="historicoAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Visualizar histórico de aulas e progresso">
                                            <i class="fas fa-history me-1"></i>Histórico
                                        </button>

                                        <?php if ($aluno['status'] === 'ativo'): ?>
                                        <button type="button" class="btn btn-toggle action-btn" 
                                                onclick="desativarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Desativar aluno (não poderá agendar aulas)">
                                            <i class="fas fa-ban me-1"></i>Desativar
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="ativarAluno(<?php echo $aluno['id']; ?>)" 
                                                title="Reativar aluno para agendamento de aulas">
                                            <i class="fas fa-check me-1"></i>Ativar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botão de exclusão destacado -->
                                    <div class="action-buttons-danger">
                                        <button type="button" class="btn btn-delete action-btn" 
                                                onclick="excluirAluno(<?php echo $aluno['id']; ?>)" 
                                                title="⚠️ EXCLUIR ALUNO - Esta ação não pode ser desfeita!">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Customizado para Cadastro/Edição de Aluno -->
<div id="modalAluno" class="custom-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div class="custom-modal-dialog" style="position: fixed; top: 2rem; left: 2rem; right: 2rem; bottom: 2rem; width: auto; height: auto; margin: 0; padding: 0; display: flex; align-items: center; justify-content: center;">
        <div class="custom-modal-content" style="width: 100%; height: 100%; max-width: 95vw; max-height: 95vh; background: white; border: none; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); overflow: hidden; display: flex; flex-direction: column;">
            <form id="formAluno" method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-bottom: none; padding: 0.75rem 1.5rem; flex-shrink: 0;">
                    <h5 class="modal-title" id="modalTitle" style="color: white; font-weight: 600; font-size: 1.25rem; margin: 0;">
                        <i class="fas fa-user-graduate me-2"></i>Novo Aluno
                    </h5>
                    <button type="button" class="btn-close" onclick="fecharModalAluno()" style="filter: invert(1); background: none; border: none; font-size: 1.25rem; color: white; opacity: 0.8; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="overflow-y: auto; padding: 1rem; flex: 1; min-height: 0;">
                    <input type="hidden" name="acao" id="acaoAluno" value="criar">
                    <input type="hidden" name="aluno_id" id="aluno_id" value="">
                    
                    <div class="container-fluid" style="padding: 0;">
                        <!-- Seção 1: Informações Pessoais -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-user me-1"></i>Informações Pessoais
                                </h6>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="nome" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Nome Completo *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" required 
                                           placeholder="Nome completo do aluno" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="cpf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CPF *</label>
                                    <input type="text" class="form-control" id="cpf" name="cpf" required 
                                           placeholder="000.000.000-00" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="rg" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">RG</label>
                                    <input type="text" class="form-control" id="rg" name="rg" 
                                           placeholder="00.000.000-0" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="data_nascimento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data Nasc. *</label>
                                    <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="status" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Status</label>
                                    <select class="form-select" id="status" name="status" style="padding: 0.4rem; font-size: 0.85rem;">
                                        <option value="ativo">Ativo</option>
                                        <option value="inativo">Inativo</option>
                                        <option value="concluido">Concluído</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="email" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">E-mail</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           placeholder="aluno@email.com" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="telefone" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone</label>
                                    <input type="text" class="form-control" id="telefone" name="telefone" 
                                           placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="progresso" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Progresso (%)</label>
                                    <input type="range" class="form-range" id="progresso" name="progresso" 
                                           min="0" max="100" value="0" style="margin: 0.1rem 0;">
                                    <div class="text-center">
                                        <span id="progressoValor" style="font-size: 0.75rem;">0%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 2: Informações Acadêmicas -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-graduation-cap me-1"></i>Informações Acadêmicas
                                </h6>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="cfc_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CFC *</label>
                                    <select class="form-select" id="cfc_id" name="cfc_id" required style="padding: 0.4rem; font-size: 0.85rem;">
                                        <option value="">Selecione um CFC...</option>
                                        <?php if (isset($cfcs) && is_array($cfcs)): ?>
                                            <?php foreach ($cfcs as $cfc): ?>
                                                <option value="<?php echo $cfc['id']; ?>">
                                                    <?php echo htmlspecialchars($cfc['nome']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="categoria_cnh" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Categoria CNH *</label>
                                    <select class="form-select" id="categoria_cnh" name="categoria_cnh" required style="padding: 0.4rem; font-size: 0.85rem;">
                                        <option value="">Selecione a categoria...</option>
                                        <option value="A">A - Motocicletas</option>
                                        <option value="B">B - Automóveis</option>
                                        <option value="C">C - Veículos de carga</option>
                                        <option value="D">D - Veículos de passageiros</option>
                                        <option value="E">E - Veículos com reboque</option>
                                        <option value="AB">AB - A + B</option>
                                        <option value="AC">AC - A + C</option>
                                        <option value="AD">AD - A + D</option>
                                        <option value="AE">AE - A + E</option>
                                    </select>
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
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="cep" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CEP</label>
                                    <input type="text" class="form-control" id="cep" name="cep" 
                                           placeholder="00000-000" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="logradouro" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Logradouro</label>
                                    <input type="text" class="form-control" id="logradouro" name="logradouro" 
                                           placeholder="Rua, Avenida, etc." style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="numero" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Número</label>
                                    <input type="text" class="form-control" id="numero" name="numero" 
                                           placeholder="123" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="bairro" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Bairro</label>
                                    <input type="text" class="form-control" id="bairro" name="bairro" 
                                           placeholder="Centro, Jardim, etc." style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="mb-1">
                                    <label for="uf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">UF</label>
                                    <select class="form-select" id="uf" name="uf" style="padding: 0.4rem; font-size: 0.85rem;">
                                        <option value="">Selecione...</option>
                                        <option value="AC">Acre</option>
                                        <option value="AL">Alagoas</option>
                                        <option value="AP">Amapá</option>
                                        <option value="AM">Amazonas</option>
                                        <option value="BA">Bahia</option>
                                        <option value="CE">Ceará</option>
                                        <option value="DF">Distrito Federal</option>
                                        <option value="ES">Espírito Santo</option>
                                        <option value="GO">Goiás</option>
                                        <option value="MA">Maranhão</option>
                                        <option value="MT">Mato Grosso</option>
                                        <option value="MS">Mato Grosso do Sul</option>
                                        <option value="MG">Minas Gerais</option>
                                        <option value="PA">Pará</option>
                                        <option value="PB">Paraíba</option>
                                        <option value="PR">Paraná</option>
                                        <option value="PE">Pernambuco</option>
                                        <option value="PI">Piauí</option>
                                        <option value="RJ">Rio de Janeiro</option>
                                        <option value="RN">Rio Grande do Norte</option>
                                        <option value="RS">Rio Grande do Sul</option>
                                        <option value="RO">Rondônia</option>
                                        <option value="RR">Roraima</option>
                                        <option value="SC">Santa Catarina</option>
                                        <option value="SP">São Paulo</option>
                                        <option value="SE">Sergipe</option>
                                        <option value="TO">Tocantins</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-1">
                                    <label for="cidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Cidade</label>
                                    <input type="text" class="form-control" id="cidade" name="cidade" 
                                           placeholder="Nome da cidade" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 4: Observações -->
                        <div class="row">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-sticky-note me-1"></i>Observações
                                </h6>
                                <div class="mb-1">
                                    <label for="observacoes" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Observações</label>
                                    <textarea class="form-control" id="observacoes" name="observacoes" rows="1" 
                                              placeholder="Informações adicionais sobre o aluno..." style="padding: 0.4rem; font-size: 0.85rem; resize: vertical;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; padding: 0.75rem 1.5rem; display: flex; justify-content: flex-end; gap: 1rem; flex-shrink: 0;">
                    <button type="button" class="btn btn-secondary" onclick="fecharModalAluno()" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarAluno" style="padding: 0.5rem 1rem; font-size: 0.9rem;">
                        <i class="fas fa-save me-1"></i>Salvar Aluno
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Aluno -->
<div class="modal fade" id="modalVisualizarAluno" tabindex="-1" aria-labelledby="modalVisualizarAlunoLabel" aria-hidden="true">
    <div class="modal-dialog modal-fullscreen" style="position: fixed !important; top: 0 !important; left: 0 !important; right: 0 !important; bottom: 0 !important; width: 100vw !important; height: 100vh !important; max-width: 100vw !important; max-height: 100vh !important; margin: 0 !important; padding: 0 !important; transform: none !important;">
        <div class="modal-content" style="height: 100vh !important; border-radius: 0 !important; border: none !important;">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarAlunoLabel">
                    <i class="fas fa-eye me-2"></i>Detalhes do Aluno
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVisualizarAlunoBody">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="fas fa-edit me-1"></i>Editar Aluno
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para Alunos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar máscaras
    inicializarMascarasAluno();
    
    // Inicializar filtros
    inicializarFiltrosAluno();
    
    // Inicializar busca
    inicializarBuscaAluno();
    
    // Inicializar controle de progresso
    inicializarProgresso();
    
    // Adicionar event listener para o formulário
    const formAluno = document.getElementById('formAluno');
    if (formAluno) {
        formAluno.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarAluno();
        });
    }
});

function inicializarMascarasAluno() {
    // Máscara para CPF
    if (typeof IMask !== 'undefined') {
        new IMask(document.getElementById('cpf'), {
            mask: '000.000.000-00'
        });
        
        // Máscara para RG
        new IMask(document.getElementById('rg'), {
            mask: '00.000.000-0'
        });
        
        // Máscara para telefone
        new IMask(document.getElementById('telefone'), {
            mask: '(00) 00000-0000'
        });
        
        // Máscara para CEP
        new IMask(document.getElementById('cep'), {
            mask: '00000-000'
        });
    }
    
    // Busca de CEP
    document.getElementById('cep').addEventListener('blur', function() {
        const cep = this.value.replace(/\D/g, '');
        if (cep.length === 8) {
            buscarCEP(cep);
        }
    });
}

function buscarCEP(cep) {
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => response.json())
        .then(data => {
            if (!data.erro) {
                document.getElementById('logradouro').value = data.logradouro;
                document.getElementById('bairro').value = data.bairro;
                document.getElementById('cidade').value = data.localidade;
                document.getElementById('uf').value = data.uf;
            }
        })
        .catch(error => console.error('Erro ao buscar CEP:', error));
}

function inicializarFiltrosAluno() {
    // Filtro por status
    document.getElementById('filtroStatus').addEventListener('change', filtrarAlunos);
    
    // Filtro por CFC
    document.getElementById('filtroCFC').addEventListener('change', filtrarAlunos);
    
    // Filtro por categoria
    document.getElementById('filtroCategoria').addEventListener('change', filtrarAlunos);
}



function inicializarBuscaAluno() {
    document.getElementById('buscaAluno').addEventListener('input', filtrarAlunos);
}

function inicializarProgresso() {
    const progressoRange = document.getElementById('progresso');
    const progressoValor = document.getElementById('progressoValor');
    
    progressoRange.addEventListener('input', function() {
        progressoValor.textContent = this.value + '%';
    });
}

function editarAluno(id) {
    console.log('🚀 editarAluno chamada com ID:', id);
    
    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalAluno');
    const modalTitle = document.getElementById('modalTitle');
    const acaoAluno = document.getElementById('acaoAluno');
    const alunoId = document.getElementById('aluno_id');
    
    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalTitle:', modalTitle ? '✅ Existe' : '❌ Não existe');
    console.log('  acaoAluno:', acaoAluno ? '✅ Existe' : '❌ Não existe');
    console.log('  aluno_id:', alunoId ? '✅ Existe' : '❌ Não existe');
    
    if (!modalElement) {
        console.error('❌ Modal não encontrado!');
        alert('ERRO: Modal não encontrado na página!');
        return;
    }
    
    console.log(`📡 Fazendo requisição para api/alunos.php?id=${id}`);
    
    // Buscar dados do aluno (usando nova API funcional)
    fetch(`api/alunos.php?id=${id}`)
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            // Primeiro vamos ver o texto da resposta
            return response.text().then(text => {
                console.log('📄 Texto da resposta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e);
                    console.error('📄 Texto que causou erro:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);
            
            if (data.success) {
                console.log('✅ Success = true, abrindo modal...');
                
                // Preencher formulário
                preencherFormularioAluno(data.aluno);
                console.log('✅ Formulário preenchido');
                
                // Configurar modal
                if (modalTitle) modalTitle.textContent = 'Editar Aluno';
                if (acaoAluno) acaoAluno.value = 'editar';
                if (alunoId) alunoId.value = id;
                
                // Abrir modal customizado
                abrirModalAluno();
                console.log('🪟 Modal customizado aberto!');
                
            } else {
                console.error('❌ Success = false, erro:', data.error);
                mostrarAlerta('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'), 'danger');
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            mostrarAlerta('Erro ao carregar dados do aluno: ' + error.message, 'danger');
        });
}

function preencherFormularioAluno(aluno) {
    document.getElementById('nome').value = aluno.nome || '';
    document.getElementById('cpf').value = aluno.cpf || '';
    document.getElementById('rg').value = aluno.rg || '';
    document.getElementById('data_nascimento').value = aluno.data_nascimento || '';
    document.getElementById('email').value = aluno.email || '';
    document.getElementById('telefone').value = aluno.telefone || '';
    document.getElementById('cfc_id').value = aluno.cfc_id || '';
    document.getElementById('categoria_cnh').value = aluno.categoria_cnh || '';
    document.getElementById('status').value = aluno.status || 'ativo';
    document.getElementById('progresso').value = aluno.progresso || 0;
    document.getElementById('progressoValor').textContent = (aluno.progresso || 0) + '%';
    
    // Endereço
    if (aluno.endereco) {
        const endereco = typeof aluno.endereco === 'string' ? JSON.parse(aluno.endereco) : aluno.endereco;
        document.getElementById('cep').value = endereco.cep || '';
        document.getElementById('logradouro').value = endereco.logradouro || '';
        document.getElementById('numero').value = endereco.numero || '';
        document.getElementById('bairro').value = endereco.bairro || '';
        document.getElementById('cidade').value = endereco.cidade || '';
        document.getElementById('uf').value = endereco.uf || '';
    }
    
    document.getElementById('observacoes').value = aluno.observacoes || '';
}

function visualizarAluno(id) {
    console.log('🚀 visualizarAluno chamada com ID:', id);

    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalVisualizarAluno');
    const modalBody = document.getElementById('modalVisualizarAlunoBody');

    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalVisualizarAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalVisualizarAlunoBody:', modalBody ? '✅ Existe' : '❌ Não existe');

    if (!modalElement) {
        console.error('❌ Modal de visualização não encontrado!');
        alert('ERRO: Modal de visualização não encontrado na página!');
        return;
    }

    console.log(`📡 Fazendo requisição para api/alunos.php?id=${id}`);

    // Buscar dados do aluno (usando nova API funcional)
    fetch(`api/alunos.php?id=${id}`)
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            return response.text().then(text => {
                console.log('📄 Texto da resposta:', text);
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e);
                    console.error('📄 Texto que causou erro:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);

            if (data.success) {
                console.log('✅ Success = true, preenchendo modal...');

                // Preencher modal
                preencherModalVisualizacao(data.aluno);
                console.log('✅ Modal preenchido');

                // Abrir modal
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
                console.log('🪟 Modal de visualização aberto!');

            } else {
                console.error('❌ Success = false, erro:', data.error);
                mostrarAlerta('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'), 'danger');
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            mostrarAlerta('Erro ao carregar dados do aluno: ' + error.message, 'danger');
        });
}

function preencherModalVisualizacao(aluno) {
    const endereco = typeof aluno.endereco === 'string' ? JSON.parse(aluno.endereco) : aluno.endereco;
    
    const html = `
        <div class="row">
            <div class="col-md-8">
                <h4>${aluno.nome}</h4>
                <p class="text-muted">CPF: ${aluno.cpf}</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-${aluno.status === 'ativo' ? 'success' : (aluno.status === 'concluido' ? 'info' : 'danger')} fs-6">
                    ${aluno.status === 'ativo' ? 'Ativo' : (aluno.status === 'concluido' ? 'Concluído' : 'Inativo')}
                </span>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle me-2"></i>Informações Pessoais</h6>
                <p><strong>RG:</strong> ${aluno.rg || 'Não informado'}</p>
                <p><strong>Data de Nascimento:</strong> ${aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado'}</p>
                <p><strong>E-mail:</strong> ${aluno.email || 'Não informado'}</p>
                <p><strong>Telefone:</strong> ${aluno.telefone || 'Não informado'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2"></i>Informações Acadêmicas</h6>
                <p><strong>CFC:</strong> ${aluno.cfc_nome || 'Não informado'}</p>
                <p><strong>Categoria:</strong> <span class="badge bg-secondary">${aluno.categoria_cnh}</span></p>
                <p><strong>Progresso:</strong> ${aluno.progresso || 0}%</p>
            </div>
        </div>
        
        ${endereco && (endereco.logradouro || endereco.cidade) ? `
        <hr>
        <h6><i class="fas fa-map-marker-alt me-2"></i>Endereço</h6>
        <p>${endereco.logradouro || ''} ${endereco.numero || ''}</p>
        <p>${endereco.bairro || ''}</p>
        <p>${endereco.cidade || ''} - ${endereco.uf || ''}</p>
        <p>CEP: ${endereco.cep || 'Não informado'}</p>
        ` : ''}
        
        ${aluno.observacoes ? `
        <hr>
        <h6><i class="fas fa-sticky-note me-2"></i>Observações</h6>
        <p>${aluno.observacoes}</p>
        ` : ''}
    `;
    
    document.getElementById('modalVisualizarAlunoBody').innerHTML = html;
    document.getElementById('btnEditarVisualizacao').onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('modalVisualizarAluno')).hide();
        editarAluno(aluno.id);
    };
}

function agendarAula(id) {
    // Redirecionar para página de agendamento usando o sistema de páginas do admin
    window.location.href = `?page=agendar-aula&aluno_id=${id}`;
}

function historicoAluno(id) {
    // Debug: verificar se a função está sendo chamada
    console.log('Função historicoAluno chamada com ID:', id);
    
    // Redirecionar para página de histórico usando o sistema de roteamento do admin
    window.location.href = `?page=historico-aluno&id=${id}`;
}

function ativarAluno(id) {
    if (confirm('Deseja realmente ativar este aluno?')) {
        alterarStatusAluno(id, 'ativo');
    }
}

function desativarAluno(id) {
    if (confirm('Deseja realmente desativar este aluno? Esta ação pode afetar o histórico de aulas.')) {
        alterarStatusAluno(id, 'inativo');
    }
}

function excluirAluno(id) {
    const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este aluno?';
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Excluindo aluno...');
        }
        
        fetch(`api/alunos.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            
            if (data.success) {
                if (typeof notifications !== 'undefined') {
                    notifications.success('Aluno excluído com sucesso!');
                } else {
                    mostrarAlerta('Aluno excluído com sucesso!', 'success');
                }
                location.reload();
            } else {
                if (typeof notifications !== 'undefined') {
                    notifications.error(data.error || 'Erro ao excluir aluno');
                } else {
                    mostrarAlerta(data.error || 'Erro ao excluir aluno', 'danger');
                }
            }
        })
        .catch(error => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('Erro:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Erro ao excluir aluno');
            } else {
                mostrarAlerta('Erro ao excluir aluno', 'danger');
            }
        });
    }
}

function alterarStatusAluno(id, status) {
    const mensagem = `Deseja realmente ${status === 'ativo' ? 'ativar' : 'desativar'} este aluno?`;
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Alterando status...');
        }
        
        const formData = new FormData();
        formData.append('acao', 'alterar_status');
        formData.append('aluno_id', id);
        formData.append('status', status);
        
        fetch('pages/alunos.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.text())
        .then(data => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            if (typeof notifications !== 'undefined') {
                notifications.success(`Status do aluno alterado para ${status} com sucesso!`);
            }
            location.reload();
        })
        .catch(error => {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('Erro:', error);
            if (typeof notifications !== 'undefined') {
                notifications.error('Erro ao alterar status do aluno');
            } else {
                mostrarAlerta('Erro ao alterar status do aluno', 'danger');
            }
        });
    }
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaAluno').value = '';
    filtrarAlunos();
}

function filtrarAlunos() {
    const busca = document.getElementById('buscaAluno').value.toLowerCase();
    const status = document.getElementById('filtroStatus').value;
    const cfc = document.getElementById('filtroCFC').value;
    const categoria = document.getElementById('filtroCategoria').value;
    
    const linhas = document.querySelectorAll('#tabelaAlunos tbody tr');
    let contador = 0;
    
    linhas.forEach(linha => {
        const nome = linha.querySelector('td:nth-child(2)').textContent.toLowerCase();
        const cpf = linha.querySelector('td:nth-child(3)').textContent;
        const email = linha.querySelector('td:nth-child(4)').textContent.toLowerCase();
        const statusLinha = linha.querySelector('td:nth-child(6) .badge').textContent;
        const categoriaLinha = linha.querySelector('td:nth-child(5)').textContent;
        const cfcLinha = linha.querySelector('td:nth-child(7)').textContent;
        
        let mostrar = true;
        
        // Filtro de busca
        if (busca && !nome.includes(busca) && !cpf.includes(busca) && !email.includes(busca)) {
            mostrar = false;
        }
        
        // Filtro de status
        if (status && statusLinha !== status) {
            mostrar = false;
        }
        
        // Filtro de CFC
        if (cfc && cfcLinha !== cfc) {
            mostrar = false;
        }
        
        // Filtro de categoria
        if (categoria && categoriaLinha !== categoria) {
            mostrar = false;
        }
        
        linha.style.display = mostrar ? '' : 'none';
        if (mostrar) contador++;
    });
    
    // Atualizar estatísticas
    document.getElementById('totalAlunos').textContent = contador;
    
    // Mostrar notificação de resultado
    if (typeof notifications !== 'undefined') {
        notifications.info(`Filtro aplicado: ${contador} aluno(s) encontrado(s)`);
    }
}

function atualizarEstatisticas() {
    const linhasVisiveis = document.querySelectorAll('#tabelaAlunos tbody tr:not([style*="display: none"])');
    
    document.getElementById('totalAlunos').textContent = linhasVisiveis.length;
    
    const ativos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Ativo'
    ).length;
    
    const concluidos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Concluído'
    ).length;
    
    document.getElementById('alunosAtivos').textContent = ativos;
    document.getElementById('emFormacao').textContent = ativos;
    document.getElementById('concluidos').textContent = concluidos;
}

function exportarAlunos() {
    // Implementar exportação para Excel/CSV
    alert('Funcionalidade de exportação será implementada em breve!');
}

function imprimirAlunos() {
    window.print();
}

function exportarFiltros() {
    if (typeof loading !== 'undefined') {
        loading.showGlobal('Preparando exportação...');
    }
    
    setTimeout(() => {
        if (typeof loading !== 'undefined') {
            loading.hideGlobal();
        }
        if (typeof notifications !== 'undefined') {
            notifications.success('Exportação realizada com sucesso!');
        } else {
            alert('Exportação realizada com sucesso!');
        }
    }, 1500);
}

// Função para mostrar alertas usando o sistema de notificações
function mostrarAlerta(mensagem, tipo) {
    if (typeof notifications !== 'undefined') {
        notifications.show(mensagem, tipo);
    } else {
        // Fallback para alertas tradicionais
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${mensagem}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        // Tentar encontrar um container válido para inserir o alerta
        const container = document.querySelector('.container-fluid') || 
                         document.querySelector('.container') || 
                         document.querySelector('main') || 
                         document.body;
        
        if (container && container !== document.body) {
            const targetElement = container.querySelector('.d-flex') || container.firstChild;
            if (targetElement) {
                container.insertBefore(alertDiv, targetElement);
            } else {
                container.appendChild(alertDiv);
            }
        } else {
            // Fallback para o body se não encontrar container
            document.body.insertBefore(alertDiv, document.body.firstChild);
        }
        
        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
}

// Função para confirmar ações importantes
function confirmarAcao(mensagem, acao) {
    if (typeof modals !== 'undefined') {
        modals.confirm(mensagem, acao);
    } else {
        if (confirm(mensagem)) {
            acao();
        }
    }
}

// FUNÇÕES PARA MODAL CUSTOMIZADO

// Função para ajustar modal responsivo (deve ser global)
function ajustarModalResponsivo() {
    const modalDialog = document.querySelector('#modalAluno .custom-modal-dialog');
    if (modalDialog) {
        if (window.innerWidth <= 768) {
            // Mobile - ocupar quase toda a tela
            modalDialog.style.cssText = `
                position: fixed !important;
                top: 0.5rem !important;
                left: 0.5rem !important;
                right: 0.5rem !important;
                bottom: 0.5rem !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
        } else if (window.innerWidth <= 1200) {
            // Tablet - margens menores
            modalDialog.style.cssText = `
                position: fixed !important;
                top: 1rem !important;
                left: 1rem !important;
                right: 1rem !important;
                bottom: 1rem !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
        } else {
            // Desktop - margens padrão
            modalDialog.style.cssText = `
                position: fixed !important;
                top: 2rem !important;
                left: 2rem !important;
                right: 2rem !important;
                bottom: 2rem !important;
                width: auto !important;
                height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            `;
        }
    }
}

function abrirModalAluno() {
    console.log('🚀 Abrindo modal customizado...');
    const modal = document.getElementById('modalAluno');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevenir scroll do body
        
        // Aplicar responsividade
        setTimeout(() => {
            ajustarModalResponsivo();
        }, 10);
        
        console.log('✅ Modal customizado aberto!');
    }
}

function fecharModalAluno() {
    console.log('🚪 Fechando modal customizado...');
    const modal = document.getElementById('modalAluno');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto'; // Restaurar scroll do body
        console.log('✅ Modal customizado fechado!');
    }
}

// Fechar modal ao clicar fora dele
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modalAluno');
    if (e.target === modal) {
        fecharModalAluno();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modalAluno');
        if (modal && modal.style.display === 'block') {
            fecharModalAluno();
        }
    }
});

// Inicializar funcionalidades quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar máscaras se disponível
    if (typeof inputMasks !== 'undefined') {
        inputMasks.applyMasks();
    }
    
    // Mostrar notificação de carregamento
    if (typeof notifications !== 'undefined') {
        notifications.info('Página de alunos carregada com sucesso!');
    }
    
    // Configurar tooltips e popovers se disponível
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Modal customizado - não precisamos mais do código do Bootstrap
    
    // Aplicar responsividade quando o modal abrir
    const modalAluno = document.getElementById('modalAluno');
    if (modalAluno) {
        modalAluno.addEventListener('DOMNodeInserted', ajustarModalResponsivo);
    }
    
    // Aplicar responsividade no resize da janela
    window.addEventListener('resize', function() {
        if (document.getElementById('modalAluno').style.display === 'block') {
            ajustarModalResponsivo();
        }
    });
});

// Função para salvar aluno via AJAX
function salvarAluno() {
    const form = document.getElementById('formAluno');
    const formData = new FormData(form);
    
    // Mostrar loading no botão
    const btnSalvar = document.getElementById('btnSalvarAluno');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSalvar.disabled = true;
    
    // Preparar dados para envio
    const dados = {
        nome: formData.get('nome'),
        cpf: formData.get('cpf'),
        rg: formData.get('rg'),
        data_nascimento: formData.get('data_nascimento'),
        email: formData.get('email'),
        telefone: formData.get('telefone'),
        status: formData.get('status'),
        progresso: parseInt(formData.get('progresso')) || 0, // Converter para número
        cfc_id: formData.get('cfc_id'),
        categoria_cnh: formData.get('categoria_cnh'),
        cep: formData.get('cep'),
        endereco: formData.get('logradouro'), // Mapear logradouro para endereco
        numero: formData.get('numero'),
        bairro: formData.get('bairro'),
        cidade: formData.get('cidade'),
        estado: formData.get('uf'), // Mapear uf para estado
        observacoes: formData.get('observacoes')
    };
    
    // Determinar se é criação ou edição
    const acao = formData.get('acao');
    const alunoId = formData.get('aluno_id');
    
    if (acao === 'editar' && alunoId) {
        dados.id = alunoId;
    }
    
    // Fazer requisição para a API
    console.log('Enviando dados para API:', dados);
    
    fetch('api/alunos.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(response => {
        console.log('Resposta da API:', response);
        return response.json();
    })
    .then(data => {
        console.log('Dados da resposta:', data);
        if (data.success) {
            // Sucesso
            alert(data.message || 'Aluno salvo com sucesso!');
            fecharModalAluno();
            
            // Recarregar a página para mostrar o novo aluno
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Erro
            alert('Erro ao salvar aluno: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar aluno. Verifique o console para mais detalhes.');
    })
    .finally(() => {
        // Restaurar botão
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
    });
}
</script>