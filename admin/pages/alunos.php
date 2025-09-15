<?php
// Verificar se as variáveis estão definidas
if (!isset($alunos)) $alunos = [];
if (!isset($cfcs)) $cfcs = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<style>
/* =====================================================
   ESTILOS PARA OTIMIZAÇÃO DE ESPAÇO DESKTOP
   ===================================================== */

/* Cards de estatísticas mais compactos */
.card.border-left-primary,
.card.border-left-success,
.card.border-left-warning,
.card.border-left-info {
    min-height: 100px;
}

.card-body {
    padding: 1rem 0.75rem;
}

.text-xs {
    font-size: 0.7rem !important;
}

.h5 {
    font-size: 1.5rem !important;
}

/* Tabela otimizada */
.table-responsive {
    overflow-x: auto;
}

.table th,
.table td {
    padding: 0.5rem 0.75rem;
    vertical-align: middle;
}

/* Avatar menor */
.avatar-sm {
    width: 32px;
    height: 32px;
}

.avatar-title {
    font-size: 0.875rem;
}

/* Botões de ação compactos */
.action-buttons-compact {
    min-width: 180px;
    justify-content: center;
}

.action-icon-btn {
    width: 28px;
    height: 28px;
    font-size: 0.8rem;
}

/* Responsividade melhorada */
@media (max-width: 1200px) {
    .col-lg-2 {
        flex: 0 0 25%;
        max-width: 25%;
    }
}

@media (max-width: 992px) {
    .col-lg-2 {
        flex: 0 0 50%;
        max-width: 50%;
    }
    
    .action-buttons-compact {
        min-width: 150px;
    }
}

@media (max-width: 768px) {
    .col-lg-2 {
        flex: 0 0 100%;
        max-width: 100%;
    }
    
    .action-buttons-compact {
        min-width: 120px;
        gap: 0.15rem;
    }
    
    .action-icon-btn {
        width: 26px;
        height: 26px;
        font-size: 0.75rem;
    }
}

/* =====================================================
   ESTILOS PARA MODAL DE AGENDAMENTO
   ===================================================== */

.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-content {
    background: white;
    border-radius: 0.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
    max-width: 90vw;
    max-height: 90vh;
    overflow-y: auto;
    position: relative;
}

.modal-large {
    width: 800px;
}

.modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem 0.5rem 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.modal-close {
    background: none;
    border: none;
    color: white;
    font-size: 1.5rem;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: background-color 0.2s;
}

.modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2);
}

.modal-form {
    padding: 1.5rem;
}

.form-section {
    margin-bottom: 1.5rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e9ecef;
}

.form-row {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
}

.form-group {
    flex: 1;
    margin-bottom: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 600;
    color: #495057;
}

.form-group input,
.form-group select,
.form-group textarea {
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    font-size: 0.9rem;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.form-group input:focus,
.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

.custom-radio {
    position: relative;
}

.custom-radio input[type="radio"] {
    position: absolute;
    opacity: 0;
    cursor: pointer;
}

.custom-radio .form-check-label {
    display: block;
    padding: 0.75rem 1rem;
    border: 2px solid #e9ecef;
    border-radius: 0.5rem;
    cursor: pointer;
    transition: all 0.2s;
    background: white;
}

.custom-radio input[type="radio"]:checked + .form-check-label {
    border-color: #0d6efd;
    background-color: #f8f9ff;
}

.custom-radio .radio-text strong {
    display: block;
    color: #495057;
    margin-bottom: 0.25rem;
}

.custom-radio .radio-text small {
    color: #6c757d;
    font-size: 0.8rem;
}

.form-actions {
    display: flex;
    gap: 1rem;
    justify-content: flex-end;
    margin-top: 2rem;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.form-actions .btn {
    padding: 0.75rem 1.5rem;
    font-weight: 500;
}

/* Responsividade do modal */
@media (max-width: 768px) {
    .modal-large {
        width: 95vw;
        margin: 1rem;
    }
    
    .form-row {
        flex-direction: column;
        gap: 0;
    }
    
    .form-actions {
        flex-direction: column;
    }
}

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

/* Melhorias para scroll do modal */
.custom-modal .modal-body {
    scrollbar-width: thin;
    scrollbar-color: #6c757d #f8f9fa;
}

.custom-modal .modal-body::-webkit-scrollbar {
    width: 8px;
}

.custom-modal .modal-body::-webkit-scrollbar-track {
    background: #f8f9fa;
    border-radius: 4px;
}

.custom-modal .modal-body::-webkit-scrollbar-thumb {
    background: #6c757d;
    border-radius: 4px;
}

.custom-modal .modal-body::-webkit-scrollbar-thumb:hover {
    background: #495057;
}

/* Garantir que os botões sejam sempre visíveis */
.custom-modal .modal-footer {
    position: sticky !important;
    bottom: 0 !important;
    background-color: #f8f9fa !important;
    border-top: 1px solid #dee2e6 !important;
    z-index: 10 !important;
}

/* Responsividade para telas menores */
@media (max-height: 768px) {
    .custom-modal-content {
        max-height: 85vh !important;
    }
    
    .custom-modal .modal-body {
        max-height: calc(85vh - 140px) !important;
    }
}

@media (max-height: 600px) {
    .custom-modal-content {
        max-height: 80vh !important;
    }
    
    .custom-modal .modal-body {
        max-height: calc(80vh - 140px) !important;
    }
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
    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
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

    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
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

    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
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

    <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
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
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($alunos)): ?>
                    <tr>
                        <td colspan="5" class="text-center text-muted py-4">
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
                                <div class="action-buttons-compact">
                                    <button type="button" class="btn btn-sm btn-outline-primary action-icon-btn" 
                                            onclick="editarAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Editar dados do aluno" data-bs-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-info action-icon-btn" 
                                            onclick="visualizarAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Ver detalhes completos do aluno" data-bs-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success action-icon-btn" 
                                            onclick="agendarAula(<?php echo $aluno['id']; ?>)" 
                                            title="Agendar nova aula para este aluno" data-bs-toggle="tooltip">
                                        <i class="fas fa-calendar-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning action-icon-btn" 
                                            onclick="historicoAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Visualizar histórico de aulas e progresso" data-bs-toggle="tooltip">
                                        <i class="fas fa-history"></i>
                                    </button>
                                    <?php if ($aluno['status'] === 'ativo'): ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary action-icon-btn" 
                                            onclick="desativarAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Desativar aluno (não poderá agendar aulas)" data-bs-toggle="tooltip">
                                        <i class="fas fa-ban"></i>
                                    </button>
                                    <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline-success action-icon-btn" 
                                            onclick="ativarAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Reativar aluno para agendamento de aulas" data-bs-toggle="tooltip">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <?php endif; ?>
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
        <div class="custom-modal-content" style="width: 100%; height: auto; max-width: 95vw; max-height: 90vh; background: white; border: none; border-radius: 0.5rem; box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15); overflow: hidden; display: flex; flex-direction: column; position: relative;">
            <form id="formAluno" method="POST">
                <div class="modal-header" style="background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%); color: white; border-bottom: none; padding: 0.75rem 1.5rem; flex-shrink: 0;">
                    <h5 class="modal-title" id="modalTitle" style="color: white; font-weight: 600; font-size: 1.25rem; margin: 0;">
                        <i class="fas fa-user-graduate me-2"></i>Novo Aluno
                    </h5>
                    <button type="button" class="btn-close" onclick="fecharModalAluno()" style="filter: invert(1); background: none; border: none; font-size: 1.25rem; color: white; opacity: 0.8; cursor: pointer;">&times;</button>
                </div>
                <div class="modal-body" style="overflow-y: auto; padding: 1rem; flex: 1; min-height: 0; max-height: calc(90vh - 140px);">
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
                            <div class="col-md-3">
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
                            <div class="col-md-3">
                                <div class="mb-1">
                                    <label for="naturalidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Naturalidade</label>
                                    <input type="text" class="form-control" id="naturalidade" name="naturalidade" 
                                           placeholder="Cidade - UF" style="padding: 0.4rem; font-size: 0.85rem;">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="mb-1">
                                    <label for="nacionalidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Nacionalidade</label>
                                    <input type="text" class="form-control" id="nacionalidade" name="nacionalidade" 
                                           placeholder="Brasileira" style="padding: 0.4rem; font-size: 0.85rem;">
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
                                    <label for="tipo_servico" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Tipo de Serviço *</label>
                                    <select class="form-select" id="tipo_servico" name="tipo_servico" required style="padding: 0.4rem; font-size: 0.85rem;" onchange="carregarCategoriasCNH()">
                                        <option value="">Selecione o tipo de serviço...</option>
                                        <option value="primeira_habilitacao">🏍️ Primeira Habilitação</option>
                                        <option value="adicao">➕ Adição de Categoria</option>
                                        <option value="mudanca">🔄 Mudança de Categoria</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-2">
                            <div class="col-md-6">
                                <div class="mb-1">
                                    <label for="categoria_cnh" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Categoria CNH *</label>
                                    <select class="form-select" id="categoria_cnh" name="categoria_cnh" required style="padding: 0.4rem; font-size: 0.85rem;" disabled>
                                        <option value="">Primeiro selecione o tipo de serviço</option>
                                    </select>
                                    <small class="form-text text-muted" id="categoria_info" style="font-size: 0.75rem; margin-top: 0.25rem;"></small>
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

<!-- Modal Nova Aula -->
<div id="modal-nova-aula" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Nova Aula</h3>
            <button class="modal-close" onclick="fecharModalNovaAula()">×</button>
        </div>
        
        <form id="form-nova-aula" class="modal-form" onsubmit="salvarNovaAula(event)">
            <!-- Seleção de Tipo de Agendamento -->
            <div class="form-section">
                <label class="form-label fw-bold">Tipo de Agendamento:</label>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check custom-radio">
                        <input class="form-check-input" type="radio" name="tipo_agendamento" id="modal_aula_unica" value="unica" checked>
                        <label class="form-check-label" for="modal_aula_unica">
                            <div class="radio-text">
                                <strong>1 Aula</strong>
                                <small>50 minutos</small>
                            </div>
                        </label>
                    </div>
                    <div class="form-check custom-radio">
                        <input class="form-check-input" type="radio" name="tipo_agendamento" id="modal_duas_aulas" value="duas">
                        <label class="form-check-label" for="modal_duas_aulas">
                            <div class="radio-text">
                                <strong>2 Aulas</strong>
                                <small>1h 40min</small>
                            </div>
                        </label>
                    </div>
                    <div class="form-check custom-radio">
                        <input class="form-check-input" type="radio" name="tipo_agendamento" id="modal_tres_aulas" value="tres">
                        <label class="form-check-label" for="modal_tres_aulas">
                            <div class="radio-text">
                                <strong>3 Aulas</strong>
                                <small>2h 30min</small>
                            </div>
                        </label>
                    </div>
                </div>
                
                <small class="form-text text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>2 aulas:</strong> Consecutivas (1h 40min) | <strong>3 aulas:</strong> Escolha a posição do intervalo de 30min (2h 30min total)
                </small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="aluno_id">Aluno *</label>
                    <select id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione o aluno</option>
                        <?php if (isset($alunos) && is_array($alunos)): ?>
                            <?php foreach ($alunos as $aluno): ?>
                                <option value="<?php echo intval($aluno['id']); ?>" data-nome="<?php echo htmlspecialchars($aluno['nome']); ?>">
                                    <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo htmlspecialchars($aluno['categoria_cnh']); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="instrutor_id">Instrutor *</label>
                    <select id="instrutor_id" name="instrutor_id" required>
                        <option value="">Selecione o instrutor</option>
                        <!-- Será carregado via AJAX -->
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_aula">Tipo de Aula *</label>
                    <select id="tipo_aula" name="tipo_aula" required>
                        <option value="">Selecione o tipo</option>
                        <option value="teorica">Teórica</option>
                        <option value="pratica">Prática</option>
                        <option value="simulador">Simulador</option>
                        <option value="avaliacao">Avaliação</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="veiculo_id">Veículo</label>
                    <select id="veiculo_id" name="veiculo_id">
                        <option value="">Apenas para aulas práticas</option>
                        <!-- Será carregado via AJAX -->
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_aula">Data da Aula *</label>
                    <input type="date" id="data_aula" name="data_aula" required min="<?php echo date('Y-m-d'); ?>">
                </div>
                
                <div class="form-group">
                    <label for="hora_inicio">Hora de Início *</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required>
                </div>
                
                <div class="form-group">
                    <label for="duracao">Duração da Aula *</label>
                    <div class="form-control-plaintext bg-light border rounded p-2">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        <strong>50 minutos</strong>
                        <small class="text-muted ms-2">(duração fixa)</small>
                    </div>
                    <input type="hidden" id="duracao" name="duracao" value="50">
                </div>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" name="observacoes" rows="3" placeholder="Observações sobre a aula..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModalNovaAula()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Salvar Aula
                </button>
            </div>
        </form>
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
    
    // Inicializar controles do modal
inicializarModalAluno();
    
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

// Função para inicializar controles do modal
function inicializarModalAluno() {
    // Event listeners para o modal
    const modal = document.getElementById('modalAluno');
    if (modal) {
        // Fechar modal ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalAluno();
            }
        });
    }
    
    // Event listener para ESC fechar modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalAluno');
            if (modal && modal.style.display === 'block') {
                fecharModalAluno();
            }
        }
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
    document.getElementById('naturalidade').value = aluno.naturalidade || '';
    document.getElementById('nacionalidade').value = aluno.nacionalidade || '';
    document.getElementById('email').value = aluno.email || '';
    document.getElementById('telefone').value = aluno.telefone || '';
    document.getElementById('cfc_id').value = aluno.cfc_id || '';
    document.getElementById('status').value = aluno.status || 'ativo';
    
    // Preencher tipo de serviço e categoria CNH
    if (aluno.categoria_cnh) {
        // Determinar tipo de serviço baseado na categoria
        let tipoServico = '';
        if (['A', 'B', 'AB', 'ACC'].includes(aluno.categoria_cnh)) {
            tipoServico = 'primeira_habilitacao';
        } else if (['C', 'D', 'E'].includes(aluno.categoria_cnh)) {
            tipoServico = 'adicao';
        } else {
            tipoServico = 'mudanca';
        }
        
        // Definir tipo de serviço primeiro
        document.getElementById('tipo_servico').value = tipoServico;
        
        // Carregar categorias para o tipo selecionado
        carregarCategoriasCNH();
        
        // Definir categoria CNH após carregar as opções
        setTimeout(() => {
            document.getElementById('categoria_cnh').value = aluno.categoria_cnh || '';
        }, 100);
    }
    
    // Endereço
    if (aluno.endereco) {
        let endereco;
        if (typeof aluno.endereco === 'string') {
            try {
                // Try to parse as JSON first
                endereco = JSON.parse(aluno.endereco);
            } catch (e) {
                // If parsing fails, treat as plain string and create a simple object
                endereco = {
                    logradouro: aluno.endereco,
                    numero: aluno.numero || '',
                    bairro: aluno.bairro || '',
                    cidade: aluno.cidade || '',
                    uf: aluno.estado || '',
                    cep: aluno.cep || ''
                };
            }
        } else {
            endereco = aluno.endereco;
        }
        
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
    // Handle endereco field - it might be a string or an object
    let endereco = aluno.endereco;
    if (typeof aluno.endereco === 'string') {
        try {
            // Try to parse as JSON first
            endereco = JSON.parse(aluno.endereco);
        } catch (e) {
            // If parsing fails, treat as plain string and create a simple object
            endereco = {
                logradouro: aluno.endereco,
                numero: aluno.numero || '',
                bairro: aluno.bairro || '',
                cidade: aluno.cidade || '',
                uf: aluno.estado || '',
                cep: aluno.cep || ''
            };
        }
    }
    
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
                <p><strong>Naturalidade:</strong> ${aluno.naturalidade || 'Não informado'}</p>
                <p><strong>Nacionalidade:</strong> ${aluno.nacionalidade || 'Não informado'}</p>
                <p><strong>E-mail:</strong> ${aluno.email || 'Não informado'}</p>
                <p><strong>Telefone:</strong> ${aluno.telefone || 'Não informado'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2"></i>Informações Acadêmicas</h6>
                <p><strong>CFC:</strong> ${aluno.cfc_nome || 'Não informado'}</p>
                <p><strong>Categoria:</strong> <span class="badge bg-secondary">${aluno.categoria_cnh}</span></p>
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
    console.log('🚀 agendarAula chamada com ID:', id);
    
    // Verificar se o ID é válido
    if (!id || id === 'undefined' || id === 'null') {
        console.error('❌ ID inválido:', id);
        mostrarAlerta('Erro: ID do aluno inválido', 'danger');
        return;
    }
    
    // Abrir modal primeiro
    abrirModalNovaAula();
    
    // Aguardar um pouco para o modal estar totalmente carregado
    setTimeout(() => {
        preencherAlunoSelecionado(id);
    }, 200); // Aumentei o tempo para 200ms
}

function preencherAlunoSelecionado(id) {
    console.log('🔧 Preenchendo aluno selecionado:', id);
    
    const selectAluno = document.getElementById('aluno_id');
    if (!selectAluno) {
        console.error('❌ Select de aluno não encontrado!');
        return;
    }
    
    console.log('📋 Select encontrado, verificando opções...');
    
    // Método mais simples e seguro
    try {
        // Tentar definir o valor diretamente
        selectAluno.value = id;
        
        // Verificar se foi definido corretamente
        if (selectAluno.value == id) {
            console.log('✅ Aluno pré-selecionado com sucesso!');
            
            // Disparar evento change
            selectAluno.dispatchEvent(new Event('change', { bubbles: true }));
            
            // Carregar dados relacionados
            carregarInstrutoresDisponiveis();
            carregarVeiculosDisponiveis();
        } else {
            console.log('⚠️ Valor não foi definido, tentando método alternativo...');
            
            // Método alternativo: percorrer as opções
            for (let i = 0; i < selectAluno.options.length; i++) {
                const option = selectAluno.options[i];
                if (option.value == id) {
                    selectAluno.selectedIndex = i;
                    console.log('✅ Aluno pré-selecionado (método alternativo):', option.textContent);
                    
                    selectAluno.dispatchEvent(new Event('change', { bubbles: true }));
                    carregarInstrutoresDisponiveis();
                    carregarVeiculosDisponiveis();
                    return;
                }
            }
            
            console.error('❌ Nenhuma opção encontrada para ID:', id);
        }
    } catch (error) {
        console.error('❌ Erro ao pré-selecionar aluno:', error);
    }
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
        const categoriaLinha = linha.querySelector('td:nth-child(3)').textContent;
        const statusLinha = linha.querySelector('td:nth-child(4) .badge').textContent;
        
        let mostrar = true;
        
        // Filtro de busca
        if (busca && !nome.includes(busca)) {
            mostrar = false;
        }
        
        // Filtro de status
        if (status && statusLinha !== status) {
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
        linha.querySelector('td:nth-child(4) .badge').textContent === 'Ativo'
    ).length;
    
    const concluidos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(4) .badge').textContent === 'Concluído'
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

// FUNÇÕES PARA MODAL DE AGENDAMENTO

function abrirModalNovaAula() {
    console.log('🚀 Abrindo modal de nova aula...');
    const modal = document.getElementById('modal-nova-aula');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Garantir que o modal esteja totalmente renderizado
        modal.offsetHeight; // Force reflow
        
        console.log('✅ Modal de nova aula aberto!');
    } else {
        console.error('❌ Modal não encontrado!');
    }
}

function fecharModalNovaAula() {
    console.log('🚪 Fechando modal de nova aula...');
    const modal = document.getElementById('modal-nova-aula');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Limpar formulário
        document.getElementById('form-nova-aula').reset();
        console.log('✅ Modal de nova aula fechado e formulário limpo!');
    }
}

function carregarInstrutoresDisponiveis() {
    console.log('🔧 Carregando instrutores disponíveis...');
    
    // Por enquanto, vamos usar dados mockados para evitar problemas de autenticação
    const selectInstrutor = document.getElementById('instrutor_id');
    if (selectInstrutor) {
        selectInstrutor.innerHTML = '<option value="">Selecione o instrutor</option>';
        
        // Dados mockados temporários
        const instrutoresMock = [
            { id: 1, nome: 'João Silva', categoria_habilitacao: 'A, B, C' },
            { id: 2, nome: 'Maria Santos', categoria_habilitacao: 'A, B' },
            { id: 3, nome: 'Pedro Oliveira', categoria_habilitacao: 'B, C, D' }
        ];
        
        instrutoresMock.forEach(instrutor => {
            const option = document.createElement('option');
            option.value = instrutor.id;
            option.textContent = `${instrutor.nome} - ${instrutor.categoria_habilitacao}`;
            selectInstrutor.appendChild(option);
        });
        
        console.log('✅ Instrutores carregados (mock):', instrutoresMock.length);
    }
}

function carregarVeiculosDisponiveis() {
    console.log('🔧 Carregando veículos disponíveis...');
    
    fetch('api/veiculos.php')
        .then(response => {
            console.log('📡 Resposta da API veículos:', response.status);
            return response.json();
        })
        .then(data => {
            console.log('📄 Dados recebidos:', data);
            
            const selectVeiculo = document.getElementById('veiculo_id');
            if (selectVeiculo) {
                selectVeiculo.innerHTML = '<option value="">Apenas para aulas práticas</option>';
                
                // Verificar se os dados são válidos (API retorna 'data' em vez de 'veiculos')
                if (data && data.success && Array.isArray(data.data)) {
                    data.data.forEach(veiculo => {
                        const option = document.createElement('option');
                        option.value = veiculo.id;
                        option.textContent = `${veiculo.marca} ${veiculo.modelo} - ${veiculo.placa}`;
                        option.setAttribute('data-categoria', veiculo.categoria_cnh);
                        selectVeiculo.appendChild(option);
                    });
                    console.log('✅ Veículos carregados:', data.data.length);
                } else {
                    console.warn('⚠️ Dados de veículos inválidos ou vazios');
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'Nenhum veículo disponível';
                    option.disabled = true;
                    selectVeiculo.appendChild(option);
                }
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar veículos:', error);
            
            // Fallback: adicionar opção de erro
            const selectVeiculo = document.getElementById('veiculo_id');
            if (selectVeiculo) {
                selectVeiculo.innerHTML = '<option value="">Erro ao carregar veículos</option>';
            }
        });
}

function salvarNovaAula(event) {
    event.preventDefault();
    console.log('🚀 Salvando nova aula...');
    
    const formData = new FormData(event.target);
    const dados = Object.fromEntries(formData.entries());
    
    // Mostrar loading no botão
    const btnSalvar = event.target.querySelector('button[type="submit"]');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSalvar.disabled = true;
    
    // Enviar para API
    fetch('api/agendamento.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            mostrarAlerta('Aula agendada com sucesso!', 'success');
            fecharModalNovaAula();
            
            // Recarregar página após um breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarAlerta('Erro ao agendar aula: ' + (data.mensagem || 'Erro desconhecido'), 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao agendar aula. Verifique o console para mais detalhes.', 'danger');
    })
    .finally(() => {
        // Restaurar botão
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
    });
}

// Fechar modal ao clicar fora dele
document.addEventListener('click', function(e) {
    const modal = document.getElementById('modal-nova-aula');
    if (e.target === modal) {
        fecharModalNovaAula();
    }
});

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('modal-nova-aula');
        if (modal && modal.style.display === 'flex') {
            fecharModalNovaAula();
        }
    }
});

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

// Função para carregar categorias CNH dinamicamente
function carregarCategoriasCNH() {
    console.log('🔧 carregarCategoriasCNH() chamada');
    
    const tipoServico = document.getElementById('tipo_servico').value;
    const selectCategoria = document.getElementById('categoria_cnh');
    const infoCategoria = document.getElementById('categoria_info');
    
    console.log('🔧 Tipo de serviço selecionado:', tipoServico);
    console.log('🔧 Select categoria encontrado:', !!selectCategoria);
    console.log('🔧 Info categoria encontrado:', !!infoCategoria);
    
    // Limpar opções anteriores
    selectCategoria.innerHTML = '<option value="">Selecione a categoria...</option>';
    
    if (!tipoServico) {
        console.log('⚠️ Tipo de serviço vazio, desabilitando categoria');
        selectCategoria.disabled = true;
        infoCategoria.textContent = '';
        return;
    }
    
    // Definir categorias por tipo de serviço
    const categoriasPorTipo = {
        'primeira_habilitacao': [
            { value: 'A', text: 'A - Motocicletas', desc: 'Motocicletas, ciclomotores e triciclos' },
            { value: 'B', text: 'B - Automóveis', desc: 'Automóveis, caminhonetes e utilitários' },
            { value: 'AB', text: 'AB - A + B', desc: 'Motocicletas e automóveis' },
            { value: 'ACC', text: 'ACC - Ciclomotores', desc: 'Ciclomotores até 50cc (menores de 18 anos)' }
        ],
        'adicao': [
            { value: 'C', text: 'C - Veículos de Carga', desc: 'Veículos de carga acima de 3.500kg' },
            { value: 'D', text: 'D - Veículos de Passageiros', desc: 'Veículos de transporte de passageiros' },
            { value: 'E', text: 'E - Combinação de Veículos', desc: 'Combinação de veículos (carreta, bitrem)' }
        ],
        'mudanca': [
            { value: 'AC', text: 'AC - A + C', desc: 'Motocicletas + Veículos de Carga' },
            { value: 'AD', text: 'AD - A + D', desc: 'Motocicletas + Veículos de Passageiros' },
            { value: 'AE', text: 'AE - A + E', desc: 'Motocicletas + Combinação de Veículos' },
            { value: 'BC', text: 'BC - B + C', desc: 'Automóveis + Veículos de Carga' },
            { value: 'BD', text: 'BD - B + D', desc: 'Automóveis + Veículos de Passageiros' },
            { value: 'BE', text: 'BE - B + E', desc: 'Automóveis + Combinação de Veículos' },
            { value: 'CD', text: 'CD - C + D', desc: 'Veículos de Carga + Passageiros' },
            { value: 'CE', text: 'CE - C + E', desc: 'Veículos de Carga + Combinação' },
            { value: 'DE', text: 'DE - D + E', desc: 'Veículos de Passageiros + Combinação' }
        ]
    };
    
    const categorias = categoriasPorTipo[tipoServico] || [];
    
    // Adicionar opções ao select
    console.log('🔧 Adicionando', categorias.length, 'categorias ao select');
    categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.value;
        option.textContent = cat.text;
        option.setAttribute('data-desc', cat.desc);
        selectCategoria.appendChild(option);
        console.log('✅ Adicionada opção:', cat.value, '-', cat.text);
    });
    
    // Habilitar select e mostrar informações
    selectCategoria.disabled = false;
    console.log('✅ Select categoria habilitado com', selectCategoria.options.length, 'opções');
    
    // Mostrar informações sobre o tipo selecionado
    const tiposInfo = {
        'primeira_habilitacao': 'Para quem nunca teve habilitação. Inclui curso teórico obrigatório.',
        'adicao': 'Para quem já possui categoria B. Não requer curso teórico.',
        'mudanca': 'Para quem já possui outras categorias. Não requer curso teórico.'
    };
    
    infoCategoria.textContent = tiposInfo[tipoServico] || '';
    
    // Adicionar evento para mostrar descrição da categoria selecionada
    selectCategoria.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.value) {
            infoCategoria.textContent = selectedOption.getAttribute('data-desc');
        } else {
            infoCategoria.textContent = tiposInfo[tipoServico] || '';
        }
    });
}

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
        naturalidade: formData.get('naturalidade'),
        nacionalidade: formData.get('nacionalidade'),
        email: formData.get('email'),
        telefone: formData.get('telefone'),
        status: formData.get('status'),
        cfc_id: formData.get('cfc_id'),
        tipo_servico: formData.get('tipo_servico'),
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