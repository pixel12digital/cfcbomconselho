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

/* =====================================================
   ESTILOS PARA TOASTS MELHORADOS
   ===================================================== */

.toast-container {
    z-index: 9999 !important;
}

.toast {
    min-width: 350px;
    max-width: 450px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    border-radius: 8px;
    border: none;
}

.toast-body {
    padding: 1rem;
}

.toast .btn-close {
    filter: invert(1);
}

.toast.bg-danger {
    background: linear-gradient(135deg, #dc3545, #c82333) !important;
}

.toast.bg-success {
    background: linear-gradient(135deg, #198754, #157347) !important;
}

.toast.bg-warning {
    background: linear-gradient(135deg, #ffc107, #e0a800) !important;
}

.toast.bg-info {
    background: linear-gradient(135deg, #0dcaf0, #0aa2c0) !important;
}

/* Responsividade para toasts */
@media (max-width: 768px) {
    .toast {
        min-width: 300px;
        max-width: 350px;
    }
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
        <h5 class="mb-0 text-secondary"><i class="fas fa-list me-2"></i>Lista de Alunos</h5>
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
                                <?php 
                                // Mostrar operações dinâmicas em vez de categoria única
                                if (!empty($aluno['operacoes']) && is_array($aluno['operacoes'])) {
                                    foreach ($aluno['operacoes'] as $index => $operacao) {
                                        $badgeClass = '';
                                        $tipoText = '';
                                        
                                        $tipo = $operacao['tipo'] ?? 'desconhecido';
                                        $categoria = $operacao['categoria'] ?? $operacao['categoria_cnh'] ?? 'N/A';
                                        
                                        switch ($tipo) {
                                            case 'primeira_habilitacao':
                                                $badgeClass = 'bg-primary';
                                                $tipoText = '🏍️';
                                                break;
                                            case 'adicao':
                                                $badgeClass = 'bg-success';
                                                $tipoText = '➕';
                                                break;
                                            case 'mudanca':
                                                $badgeClass = 'bg-warning';
                                                $tipoText = '🔄';
                                                break;
                                            case 'aula_avulsa':
                                                $badgeClass = 'bg-info';
                                                $tipoText = '📚';
                                                break;
                                            default:
                                                $badgeClass = 'bg-secondary';
                                                $tipoText = '📋';
                                        }
                                        
                                        if ($index > 0) echo '<br>';
                                        echo '<span class="badge ' . $badgeClass . ' me-1" title="' . ucfirst(str_replace('_', ' ', $tipo)) . '">' . 
                                             $tipoText . ' ' . htmlspecialchars($categoria) . '</span>';
                                    }
                                } else {
                                    // Fallback para categoria antiga se não houver operações
                                    echo '<span class="badge bg-secondary">' . htmlspecialchars($aluno['categoria_cnh'] ?? 'N/A') . '</span>';
                                }
                                ?>
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
                    <input type="hidden" name="aluno_id" id="aluno_id_hidden" value="">
                    
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
                            <div class="col-md-3">
                                <div class="mb-1">
                                    <label for="atividade_remunerada" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Atividade Remunerada</label>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="atividade_remunerada" name="atividade_remunerada" value="1" style="font-size: 0.9rem;">
                                        <label class="form-check-label" for="atividade_remunerada" style="font-size: 0.85rem;">
                                            <i class="fas fa-briefcase me-1"></i>CNH com atividade remunerada
                                        </label>
                                    </div>
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
                        
                        <!-- Seção 2: CFC -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-graduation-cap me-1"></i>CFC
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
                        </div>
                        
                        <!-- Seção 3: Tipo de Serviço -->
                        <div class="row mb-2">
                            <div class="col-12">
                                <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                                    <i class="fas fa-tasks me-1"></i>Tipo de Serviço
                                </h6>
                            </div>
                            <div class="col-12">
                                <div class="mb-2">
                                    <div id="operacoes-container">
                                        <!-- Operações existentes serão carregadas aqui -->
                                    </div>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="adicionarOperacao()" style="font-size: 0.8rem;">
                                        <i class="fas fa-plus me-1"></i>Adicionar Tipo de Serviço
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Seção 4: Endereço -->
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
                
                <!-- Opções para 3 aulas -->
                <div id="modal_opcoesTresAulas" class="mb-3" style="display: none;">
                    <label class="form-label fw-bold">Posição do Intervalo:</label>
                    <div class="d-flex gap-3">
                        <div class="form-check custom-radio">
                            <input class="form-check-input" type="radio" name="posicao_intervalo" id="modal_intervalo_depois" value="depois" checked>
                            <label class="form-check-label" for="modal_intervalo_depois">
                                <div class="radio-text">
                                    <strong>2 consecutivas + intervalo + 1 aula</strong>
                                    <small>Primeiro bloco, depois intervalo</small>
                                </div>
                            </label>
                        </div>
                        <div class="form-check custom-radio">
                            <input class="form-check-input" type="radio" name="posicao_intervalo" id="modal_intervalo_antes" value="antes">
                            <label class="form-check-label" for="modal_intervalo_antes">
                                <div class="radio-text">
                                    <strong>1 aula + intervalo + 2 consecutivas</strong>
                                    <small>Primeira aula, depois intervalo</small>
                                </div>
                            </label>
                        </div>
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
                                    <?php echo htmlspecialchars($aluno['nome']); ?> - <?php 
                                    if (!empty($aluno['operacoes']) && is_array($aluno['operacoes'])) {
                                        $categorias = array_map(function($op) { 
                                            return $op['categoria'] ?? $op['categoria_cnh'] ?? 'N/A'; 
                                        }, $aluno['operacoes']);
                                        echo implode(', ', $categorias);
                                    } else {
                                        echo htmlspecialchars($aluno['categoria_cnh'] ?? 'N/A');
                                    }
                                    ?>
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
// Definir categorias por tipo de serviço (GLOBAL)
const categoriasPorTipo = {
    'primeira_habilitacao': [
        { value: 'A', text: 'A - Motocicletas', desc: 'Primeira habilitação para motocicletas, ciclomotores e triciclos' },
        { value: 'B', text: 'B - Automóveis', desc: 'Primeira habilitação para automóveis, caminhonetes e utilitários' },
        { value: 'AB', text: 'AB - A + B', desc: 'Primeira habilitação completa (motocicletas + automóveis)' }
    ],
    'adicao': [
        { value: 'A', text: 'A - Motocicletas', desc: 'Adicionar categoria A (motocicletas) à habilitação existente' },
        { value: 'B', text: 'B - Automóveis', desc: 'Adicionar categoria B (automóveis) à habilitação existente' }
    ],
    'mudanca': [
        { value: 'C', text: 'C - Veículos de Carga', desc: 'Mudança de B para C (veículos de carga acima de 3.500kg)' },
        { value: 'D', text: 'D - Veículos de Passageiros', desc: 'Mudança de B para D (veículos de transporte de passageiros)' },
        { value: 'E', text: 'E - Combinação de Veículos', desc: 'Mudança de B para E (combinação de veículos - carreta, bitrem)' }
    ],
    'aula_avulsa': [
        { value: 'A', text: 'A - Motocicletas', desc: 'Aula avulsa para categoria A (motocicletas, ciclomotores e triciclos)' },
        { value: 'B', text: 'B - Automóveis', desc: 'Aula avulsa para categoria B (automóveis, caminhonetes e utilitários)' },
        { value: 'C', text: 'C - Veículos de Carga', desc: 'Aula avulsa para categoria C (veículos de carga acima de 3.500kg)' },
        { value: 'D', text: 'D - Veículos de Passageiros', desc: 'Aula avulsa para categoria D (veículos de transporte de passageiros)' },
        { value: 'E', text: 'E - Combinação de Veículos', desc: 'Aula avulsa para categoria E (combinação de veículos - carreta, bitrem)' }
    ]
};

document.addEventListener('DOMContentLoaded', function() {
        // CORREÇÃO DE DUPLICAÇÃO - Temporariamente desabilitada para teste
        /*
        setTimeout(function() {
            const tabela = document.getElementById('tabelaAlunos');
            if (tabela) {
                const linhas = tabela.querySelectorAll('tbody tr');
                const idsEncontrados = [];
                const linhasParaRemover = [];
                
                linhas.forEach((linha, index) => {
                    const id = linha.querySelector('td:first-child')?.textContent?.trim();
                    if (id) {
                        if (idsEncontrados.includes(id)) {
                            console.log('🔧 Removendo linha duplicada para ID:', id);
                            linhasParaRemover.push(linha);
                        } else {
                            idsEncontrados.push(id);
                        }
                    }
                });
                
                // Remover linhas duplicadas
                linhasParaRemover.forEach(linha => {
                    linha.remove();
                });
                
                if (linhasParaRemover.length > 0) {
                    console.log('✅ Duplicatas removidas:', linhasParaRemover.length);
                }
            }
        }, 100);
        */
    
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

function abrirModalEdicao() {
    console.log('🚀 Abrindo modal para edição...');
    const modal = document.getElementById('modalAluno');
    if (modal) {
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden'; // Prevenir scroll do body
        
        // Configurar para edição
        const acaoAluno = document.getElementById('acaoAluno');
        if (acaoAluno) {
            acaoAluno.value = 'editar';
            console.log('✅ Campo acaoAluno definido como: editar');
        }
        
        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Aluno';
        }
        
        console.log('🔍 Modal aberto - Editando? true');
        console.log('📝 Formulário mantido para edição');
    }
}

function editarAluno(id) {
    console.log('🚀 editarAluno chamada com ID:', id);
    
    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalAluno');
    const modalTitle = document.getElementById('modalTitle');
    const acaoAluno = document.getElementById('acaoAluno');
    const alunoId = document.getElementById('aluno_id_hidden');
    
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
    console.log(`📡 URL completa: ${window.location.origin}/cfc-bom-conselho/admin/api/alunos.php?id=${id}`);
    
    // Buscar dados do aluno (usando nova API funcional)
    const timestamp = new Date().getTime();
    fetch(`api/alunos.php?id=${id}&t=${timestamp}`)
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            console.log(`📨 URL da resposta: ${response.url}`);
            console.log(`📨 Headers da resposta:`, response.headers);
            return response;
        })
        .then(response => {
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
                console.log('✅ Success = true, configurando modal...');
                
                // Configurar modal PRIMEIRO
                if (modalTitle) modalTitle.textContent = 'Editar Aluno';
                if (acaoAluno) {
                    acaoAluno.value = 'editar';
                    console.log('✅ Campo acaoAluno definido como: editar');
                }
                if (alunoId) {
                    alunoId.value = id;
                    console.log('✅ Campo aluno_id definido como:', id);
                }
                
                // Abrir modal customizado para edição
                abrirModalEdicao();
                console.log('🪟 Modal de edição aberto!');
                
                // Preencher formulário DEPOIS com delay para garantir que o modal esteja renderizado
                setTimeout(() => {
                    console.log('🔄 Chamando preencherFormularioAluno com dados:', data.aluno);
                    console.log('🔄 Timestamp:', new Date().toISOString());
                    preencherFormularioAluno(data.aluno);
                    console.log('✅ Formulário preenchido - função executada');
                }, 200); // Aumentar delay para 200ms
                
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
    console.log('📝 Preenchendo formulário para aluno:', aluno);
    console.log('📝 Dados específicos do aluno:');
    console.log('  - ID:', aluno.id);
    console.log('  - Nome:', aluno.nome);
    console.log('  - CPF:', aluno.cpf);
    console.log('  - Email:', aluno.email);
    console.log('  - Telefone:', aluno.telefone);
    console.log('  - CFC ID:', aluno.cfc_id);
    
    // Verificar se o modal está aberto
    const modal = document.getElementById('modalAluno');
    console.log('🔍 Modal status:', modal ? (modal.style.display === 'block' ? '✅ Aberto' : '❌ Fechado') : '❌ Não encontrado');
    
    // Definir ID do aluno para edição
    const alunoIdField = document.getElementById('aluno_id_hidden');
    if (alunoIdField) alunoIdField.value = aluno.id || '';
    
    // Preencher campos básicos com verificações de segurança
    const campos = {
        'nome': aluno.nome || '',
        'cpf': aluno.cpf || '',
        'rg': aluno.rg || '',
        'data_nascimento': aluno.data_nascimento || '',
        'naturalidade': aluno.naturalidade || '',
        'nacionalidade': aluno.nacionalidade || '',
        'email': aluno.email || '',
        'telefone': aluno.telefone || '',
        'cfc_id': aluno.cfc_id || '',
        'status': aluno.status || 'ativo',
        'atividade_remunerada': aluno.atividade_remunerada || 0
    };
    
    console.log('📝 Campos a serem preenchidos:', campos);
    
    // Preencher cada campo se ele existir
    console.log('🔍 Verificando elementos do formulário...');
    console.log('🔍 Modal visível?', document.getElementById('modalAluno')?.style.display);
    console.log('🔍 Formulário existe?', document.getElementById('formAluno') ? 'Sim' : 'Não');
    
    Object.keys(campos).forEach(campoId => {
        const elemento = document.getElementById(campoId);
        console.log(`🔍 Campo ${campoId}:`, elemento ? '✅ Existe' : '❌ Não existe');
        if (elemento) {
            const valorAnterior = elemento.value;
            elemento.value = campos[campoId];
            console.log(`✅ Campo ${campoId}:`);
            console.log(`  - Valor anterior: "${valorAnterior}"`);
            console.log(`  - Valor novo: "${campos[campoId]}"`);
            console.log(`  - Valor atual: "${elemento.value}"`);
            
            // Verificar se o valor foi realmente definido (comparação mais robusta)
            if (String(elemento.value).trim() !== String(campos[campoId]).trim()) {
                console.error(`❌ ERRO: Campo ${campoId} não foi preenchido corretamente!`);
                console.error(`  - Esperado: "${campos[campoId]}"`);
                console.error(`  - Atual: "${elemento.value}"`);
            } else {
                console.log(`✅ Campo ${campoId} preenchido corretamente`);
            }
        } else {
            console.warn(`⚠️ Campo ${campoId} não encontrado no DOM`);
        }
    });
    
    // Tratamento especial para checkbox de atividade remunerada
    const checkboxAtividade = document.getElementById('atividade_remunerada');
    if (checkboxAtividade) {
        const valorAtividade = campos['atividade_remunerada'] == 1 || campos['atividade_remunerada'] === '1' || campos['atividade_remunerada'] === true;
        checkboxAtividade.checked = valorAtividade;
        console.log(`✅ Checkbox atividade_remunerada:`, valorAtividade ? 'Marcado' : 'Desmarcado');
    } else {
        console.warn(`⚠️ Checkbox atividade_remunerada não encontrado no DOM`);
    }
    
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
        
        // Removido: tipo_servico e categoria_cnh - agora usamos apenas operacoes
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
        
        // Preencher campos de endereço com verificações de segurança
        const camposEndereco = {
            'cep': endereco.cep || '',
            'logradouro': endereco.logradouro || '',
            'numero': endereco.numero || '',
            'bairro': endereco.bairro || '',
            'cidade': endereco.cidade || '',
            'uf': endereco.uf || ''
        };
        
        Object.keys(camposEndereco).forEach(campoId => {
            const elemento = document.getElementById(campoId);
            if (elemento) {
                elemento.value = camposEndereco[campoId];
                console.log(`✅ Campo endereço ${campoId} preenchido:`, camposEndereco[campoId]);
            } else {
                console.warn(`⚠️ Campo endereço ${campoId} não encontrado no DOM`);
            }
        });
    }
    
    // Carregar operações existentes
    console.log('🔍 Dados do aluno recebidos:', aluno);
    console.log('🔍 Operações do aluno:', aluno.operacoes);
    console.log('🔍 Tipo de operacoes:', typeof aluno.operacoes);
    console.log('🔍 Operacoes é array?', Array.isArray(aluno.operacoes));
    console.log('🔍 Quantidade de operações:', aluno.operacoes ? aluno.operacoes.length : 'undefined');
    carregarOperacoesExistentes(aluno.operacoes || []);
    
    // Preencher campo de observações
    const observacoesField = document.getElementById('observacoes');
    if (observacoesField) {
        observacoesField.value = aluno.observacoes || '';
        console.log('✅ Campo observacoes preenchido:', aluno.observacoes);
    } else {
        console.warn('⚠️ Campo observacoes não encontrado no DOM');
    }
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
    const timestamp = new Date().getTime();
    fetch(`api/alunos.php?id=${id}&t=${timestamp}`)
        .then(response => {
            console.log(`📨 Resposta recebida - Status: ${response.status}, OK: ${response.ok}`);
            console.log(`📨 URL da resposta: ${response.url}`);
            console.log(`📨 Headers da resposta:`, response.headers);
            return response;
        })
        .then(response => {
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
                <p><strong>Atividade Remunerada:</strong> ${aluno.atividade_remunerada == 1 ? '<span class="badge bg-success"><i class="fas fa-briefcase me-1"></i>Sim</span>' : '<span class="badge bg-secondary"><i class="fas fa-user me-1"></i>Não</span>'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2"></i>CFC</h6>
                <p><strong>CFC:</strong> ${aluno.cfc_nome || 'Não informado'}</p>
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
    
    // Aguardar mais tempo para garantir que o modal esteja totalmente carregado
    setTimeout(() => {
        preencherAlunoSelecionado(id);
    }, 500); // Aumentei para 500ms para dar mais tempo
}

function preencherAlunoSelecionado(id) {
    console.log('🔧 Preenchendo aluno selecionado:', id);
    
    // Tentar encontrar o elemento com retry
    let selectAluno = document.getElementById('aluno_id');
    
    if (!selectAluno) {
        console.warn('⚠️ Select de aluno não encontrado, tentando novamente...');
        // Aguardar um pouco e tentar novamente
        setTimeout(() => {
            selectAluno = document.getElementById('aluno_id');
            if (selectAluno) {
                console.log('✅ Select de aluno encontrado na segunda tentativa');
                preencherAlunoSelecionado(id);
            } else {
                console.error('❌ Select de aluno não encontrado após retry');
            }
        }, 100);
        return;
    }
    
    // Verificar se é um elemento select válido
    if (selectAluno.tagName !== 'SELECT') {
        console.error('❌ Elemento encontrado não é um SELECT:', selectAluno.tagName);
        return;
    }
    
    // Verificar se options existe e é válido
    if (!selectAluno.options) {
        console.error('❌ Select de aluno não tem propriedade options!');
        console.log('🔍 Elemento encontrado:', selectAluno);
        console.log('🔍 Tipo do elemento:', typeof selectAluno);
        console.log('🔍 Propriedades disponíveis:', Object.keys(selectAluno));
        return;
    }
    
    console.log('📋 Select encontrado, verificando opções...');
    console.log('📋 Total de opções:', selectAluno.options ? selectAluno.options.length : 'undefined');
    
    // Listar todas as opções para debug (com verificação de segurança)
    if (selectAluno.options && selectAluno.options.length > 0) {
        for (let i = 0; i < selectAluno.options.length; i++) {
            const option = selectAluno.options[i];
            console.log(`📋 Opção ${i}: value="${option.value}", text="${option.textContent}"`);
        }
    } else {
        console.warn('⚠️ Nenhuma opção encontrada no select de aluno');
    }
    
    // Método mais simples e seguro
    try {
        // Tentar definir o valor diretamente
        selectAluno.value = id;
        console.log('🔧 Valor definido:', selectAluno.value);
        
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
            if (selectAluno.options && selectAluno.options.length > 0) {
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
            }
            
            console.error('❌ Nenhuma opção encontrada para ID:', id);
            console.log('🔍 Tentando com string...');
            
            // Última tentativa: converter para string
            const idString = String(id);
            if (selectAluno.options && selectAluno.options.length > 0) {
                for (let i = 0; i < selectAluno.options.length; i++) {
                    const option = selectAluno.options[i];
                    if (option.value === idString) {
                        selectAluno.selectedIndex = i;
                        console.log('✅ Aluno pré-selecionado (string):', option.textContent);
                        
                        selectAluno.dispatchEvent(new Event('change', { bubbles: true }));
                        carregarInstrutoresDisponiveis();
                        carregarVeiculosDisponiveis();
                        return;
                    }
                }
            }
            
            console.error('❌ Nenhuma opção encontrada mesmo com string!');
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
        
        const timestamp = new Date().getTime();
        fetch(`api/alunos.php?t=${timestamp}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text().then(text => {
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
    // Criar um toast moderno e elegante
    const toastContainer = document.getElementById('toast-container') || criarToastContainer();
    
    const toastId = 'toast-' + Date.now();
    const toastDiv = document.createElement('div');
    
    // Configurar classes e ícones baseados no tipo
    let iconClass, bgClass, textClass;
    switch(tipo) {
        case 'success':
            iconClass = 'fas fa-check-circle';
            bgClass = 'bg-success';
            textClass = 'text-white';
            break;
        case 'danger':
        case 'error':
            iconClass = 'fas fa-exclamation-triangle';
            bgClass = 'bg-danger';
            textClass = 'text-white';
            break;
        case 'warning':
            iconClass = 'fas fa-exclamation-circle';
            bgClass = 'bg-warning';
            textClass = 'text-dark';
            break;
        case 'info':
            iconClass = 'fas fa-info-circle';
            bgClass = 'bg-info';
            textClass = 'text-white';
            break;
        default:
            iconClass = 'fas fa-bell';
            bgClass = 'bg-primary';
            textClass = 'text-white';
    }
    
    toastDiv.id = toastId;
    toastDiv.className = `toast align-items-center ${bgClass} ${textClass} border-0`;
    toastDiv.setAttribute('role', 'alert');
    toastDiv.setAttribute('aria-live', 'assertive');
    toastDiv.setAttribute('aria-atomic', 'true');
    
    toastDiv.innerHTML = `
        <div class="d-flex">
            <div class="toast-body d-flex align-items-center">
                <i class="${iconClass} me-3 fs-5"></i>
                <div>
                    <strong>${tipo === 'success' ? 'Sucesso!' : tipo === 'danger' || tipo === 'error' ? 'Erro!' : tipo === 'warning' ? 'Atenção!' : 'Informação!'}</strong>
                    <div class="small mt-1">${tipo === 'danger' || tipo === 'error' ? formatarMensagemErro(mensagem) : mensagem}</div>
                </div>
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    `;
    
    toastContainer.appendChild(toastDiv);
    
    // Inicializar o toast
    const toast = new bootstrap.Toast(toastDiv, {
        autohide: true,
        delay: tipo === 'danger' || tipo === 'error' ? 8000 : 5000
    });
    
    toast.show();
    
    // Remover o elemento após ser escondido
    toastDiv.addEventListener('hidden.bs.toast', () => {
        toastDiv.remove();
    });
}

function criarToastContainer() {
    const container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container position-fixed top-0 end-0 p-3';
    container.style.zIndex = '9999';
    document.body.appendChild(container);
    return container;
}

// Função para formatar mensagens de erro específicas
function formatarMensagemErro(mensagem) {
    // Mapear mensagens de erro para versões mais amigáveis
    const mapeamentoErros = {
        'ALUNO JÁ AGENDADO': '⚠️ Conflito de Horário',
        'INSTRUTOR JÁ AGENDADO': '⚠️ Instrutor Indisponível',
        'VEÍCULO JÁ AGENDADO': '⚠️ Veículo Indisponível',
        'excederia o limite': '⚠️ Limite de Aulas Excedido',
        'não encontrado': '❌ Registro Não Encontrado',
        'não está logado': '🔐 Sessão Expirada',
        'Permissão negada': '🚫 Acesso Negado'
    };
    
    // Procurar por padrões conhecidos e substituir
    let mensagemFormatada = mensagem;
    for (const [padrao, substituto] of Object.entries(mapeamentoErros)) {
        if (mensagem.includes(padrao)) {
            mensagemFormatada = mensagem.replace(padrao, substituto);
            break;
        }
    }
    
    return mensagemFormatada;
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

function resetarFormularioAgendamento() {
    console.log('🔄 Resetando formulário de agendamento...');
    
    // Resetar todos os selects para o primeiro item (placeholder)
    const selectInstrutor = document.getElementById('instrutor_id');
    if (selectInstrutor) {
        selectInstrutor.selectedIndex = 0;
        console.log('✅ Select instrutor resetado para:', selectInstrutor.value);
    }
    
    const selectVeiculo = document.getElementById('veiculo_id');
    if (selectVeiculo) {
        selectVeiculo.selectedIndex = 0;
        console.log('✅ Select veículo resetado para:', selectVeiculo.value);
    }
    
    // Resetar outros campos
    const tipoAulaSelect = document.getElementById('tipo_aula');
    if (tipoAulaSelect) {
        tipoAulaSelect.selectedIndex = 0;
    }
    
    // Resetar campos de data e hora
    const dataAula = document.getElementById('data_aula');
    if (dataAula) {
        dataAula.value = '';
    }
    
    const horaInicio = document.getElementById('hora_inicio');
    if (horaInicio) {
        horaInicio.value = '';
    }
    
    // Resetar observações
    const observacoes = document.getElementById('observacoes');
    if (observacoes) {
        observacoes.value = '';
    }
    
    console.log('✅ Formulário de agendamento resetado');
}

function abrirModalNovaAula() {
    console.log('🚀 Abrindo modal de nova aula...');
    const modal = document.getElementById('modal-nova-aula');
    if (modal) {
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        
        // Garantir que o modal esteja totalmente renderizado
        modal.offsetHeight; // Force reflow
        
        // Aguardar um pouco mais para garantir que todos os elementos estejam prontos
        setTimeout(() => {
            // Resetar formulário primeiro
            resetarFormularioAgendamento();
            
            // Inicializar eventos dos radio buttons
            inicializarEventosAgendamento();
            
            // Verificar se os selects existem
            const selectAluno = document.getElementById('aluno_id');
            const selectInstrutor = document.getElementById('instrutor_id');
            const selectVeiculo = document.getElementById('veiculo_id');
            
            console.log('🔍 Verificando elementos do modal:');
            console.log('📋 Select aluno:', selectAluno ? 'encontrado' : 'não encontrado');
            console.log('📋 Select instrutor:', selectInstrutor ? 'encontrado' : 'não encontrado');
            console.log('📋 Select veículo:', selectVeiculo ? 'encontrado' : 'não encontrado');
            
            if (selectAluno && selectAluno.options) {
                console.log('📋 Opções do aluno:', selectAluno.options.length);
            }
        }, 100);
        
        console.log('✅ Modal de nova aula aberto!');
    } else {
        console.error('❌ Modal não encontrado!');
    }
}

function inicializarEventosAgendamento() {
    console.log('🔧 Inicializando eventos de agendamento...');
    
    // Event listeners para os radio buttons de tipo de agendamento
    const radioButtons = document.querySelectorAll('input[name="tipo_agendamento"]');
    radioButtons.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('📻 Tipo de agendamento alterado:', this.value);
            atualizarOpcoesAgendamento(this.value);
        });
    });
    
    // Event listeners para os radio buttons de posição do intervalo
    const intervalos = document.querySelectorAll('input[name="posicao_intervalo"]');
    intervalos.forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('📻 Posição do intervalo alterada:', this.value);
            atualizarHorariosCalculados();
        });
    });
    
    // Event listener para hora de início
    const horaInicio = document.getElementById('hora_inicio');
    if (horaInicio) {
        horaInicio.addEventListener('input', function() {
            console.log('🕐 Hora de início alterada:', this.value);
            atualizarHorariosCalculados();
        });
    }
}

function atualizarOpcoesAgendamento(tipo) {
    console.log('🔧 Atualizando opções para tipo:', tipo);
    
    const opcoesTresAulas = document.getElementById('modal_opcoesTresAulas');
    
    if (tipo === 'tres') {
        // Mostrar opções de intervalo para 3 aulas
        if (opcoesTresAulas) {
            opcoesTresAulas.style.display = 'block';
            console.log('✅ Opções de intervalo exibidas');
        }
    } else {
        // Ocultar opções de intervalo para 1 ou 2 aulas
        if (opcoesTresAulas) {
            opcoesTresAulas.style.display = 'none';
            console.log('✅ Opções de intervalo ocultadas');
        }
    }
    
    // Atualizar horários calculados se existir o elemento
    atualizarHorariosCalculados();
}

function atualizarHorariosCalculados() {
    const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked');
    const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked');
    const horaInicio = document.getElementById('hora_inicio');
    
    if (!tipoAgendamento || !horaInicio || !horaInicio.value) {
        return;
    }
    
    const horaInicioValue = horaInicio.value;
    const tipo = tipoAgendamento.value;
    const posicao = posicaoIntervalo ? posicaoIntervalo.value : 'depois';
    
    console.log('🕐 Calculando horários:', { tipo, posicao, horaInicio: horaInicioValue });
    
    // Calcular horários baseado no tipo
    let horarios = [];
    
    switch (tipo) {
        case 'unica':
            horarios = [{
                inicio: horaInicioValue,
                fim: adicionarMinutos(horaInicioValue, 50),
                duracao: '50 min'
            }];
            break;
            
        case 'duas':
            horarios = [
                {
                    inicio: horaInicioValue,
                    fim: adicionarMinutos(horaInicioValue, 50),
                    duracao: '50 min'
                },
                {
                    inicio: adicionarMinutos(horaInicioValue, 50),
                    fim: adicionarMinutos(horaInicioValue, 100),
                    duracao: '50 min'
                }
            ];
            break;
            
        case 'tres':
            if (posicao === 'depois') {
                // 2 consecutivas + 30min intervalo + 1 aula
                horarios = [
                    {
                        inicio: horaInicioValue,
                        fim: adicionarMinutos(horaInicioValue, 50),
                        duracao: '50 min'
                    },
                    {
                        inicio: adicionarMinutos(horaInicioValue, 50),
                        fim: adicionarMinutos(horaInicioValue, 100),
                        duracao: '50 min'
                    },
                    {
                        inicio: adicionarMinutos(horaInicioValue, 130),
                        fim: adicionarMinutos(horaInicioValue, 180),
                        duracao: '50 min'
                    }
                ];
            } else {
                // 1 aula + 30min intervalo + 2 consecutivas
                horarios = [
                    {
                        inicio: horaInicioValue,
                        fim: adicionarMinutos(horaInicioValue, 50),
                        duracao: '50 min'
                    },
                    {
                        inicio: adicionarMinutos(horaInicioValue, 80),
                        fim: adicionarMinutos(horaInicioValue, 130),
                        duracao: '50 min'
                    },
                    {
                        inicio: adicionarMinutos(horaInicioValue, 160),
                        fim: adicionarMinutos(horaInicioValue, 210),
                        duracao: '50 min'
                    }
                ];
            }
            break;
    }
    
    console.log('🕐 Horários calculados:', horarios);
    
    // Atualizar elementos HTML se existirem
    const containerHorarios = document.getElementById('horarios-calculados');
    if (containerHorarios) {
        containerHorarios.innerHTML = '';
        
        horarios.forEach((horario, index) => {
            const card = document.createElement('div');
            card.className = 'card mb-2';
            card.innerHTML = `
                <div class="card-body p-2">
                    <h6 class="card-title mb-1">${index + 1}ª Aula</h6>
                    <p class="card-text mb-0">
                        <strong>${horario.inicio}</strong> - <strong>${horario.fim}</strong>
                        <small class="text-muted">(${horario.duracao})</small>
                    </p>
                </div>
            `;
            containerHorarios.appendChild(card);
        });
        
        // Adicionar banner de intervalo se for 3 aulas
        if (tipo === 'tres' && horarios.length === 3) {
            const bannerIntervalo = document.createElement('div');
            bannerIntervalo.className = 'alert alert-info text-center py-2 mb-2';
            bannerIntervalo.innerHTML = '<strong>INTERVALO DE 30 MINUTOS ENTRE BLOCOS DE AULAS</strong>';
            containerHorarios.insertBefore(bannerIntervalo, containerHorarios.children[1]);
        }
    }
}

function adicionarMinutos(hora, minutos) {
    const [h, m] = hora.split(':').map(Number);
    const totalMinutos = h * 60 + m + minutos;
    const novaHora = Math.floor(totalMinutos / 60);
    const novoMinuto = totalMinutos % 60;
    return `${novaHora.toString().padStart(2, '0')}:${novoMinuto.toString().padStart(2, '0')}`;
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
    
    const selectInstrutor = document.getElementById('instrutor_id');
    if (!selectInstrutor) {
        console.error('❌ Select de instrutor não encontrado!');
        return;
    }
    
    // Limpar opções existentes
    selectInstrutor.innerHTML = '<option value="">Selecione o instrutor</option>';
    
    // Fazer chamada real para a API
    fetch('api/instrutores.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include' // Incluir cookies de sessão
    })
        .then(response => {
            console.log('📡 Resposta da API instrutores:', response.status);
            console.log('📡 Headers da resposta:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('📄 Dados recebidos da API instrutores:', data);
            
            // Verificar se os dados são válidos
            if (data && data.success && Array.isArray(data.data)) {
                data.data.forEach(instrutor => {
                    const option = document.createElement('option');
                    option.value = instrutor.id;
                    
                    // Construir texto com nome e categorias
                    let texto = instrutor.nome || 'Nome não informado';
                    if (instrutor.categorias_json) {
                        try {
                            const categorias = JSON.parse(instrutor.categorias_json);
                            if (Array.isArray(categorias) && categorias.length > 0) {
                                texto += ` - ${categorias.join(', ')}`;
                            }
                        } catch (e) {
                            console.warn('⚠️ Erro ao parsear categorias:', e);
                        }
                    }
                    
                    option.textContent = texto;
                    selectInstrutor.appendChild(option);
                });
                console.log('✅ Instrutores carregados:', data.data.length);
                
                // Garantir que nenhum item seja selecionado automaticamente
                selectInstrutor.selectedIndex = 0; // Sempre selecionar o primeiro item (placeholder)
            } else {
                console.warn('⚠️ Dados de instrutores inválidos ou vazios');
                
                // Fallback: adicionar opção de erro
                const option = document.createElement('option');
                option.value = '';
                option.textContent = 'Nenhum instrutor disponível';
                option.disabled = true;
                selectInstrutor.appendChild(option);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar instrutores:', error);
            
            // Fallback: adicionar opção de erro
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'Erro ao carregar instrutores';
            option.disabled = true;
            selectInstrutor.appendChild(option);
        });
}

function carregarVeiculosDisponiveis() {
    console.log('🔧 Carregando veículos disponíveis...');
    
    fetch('api/veiculos.php', {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
        },
        credentials: 'include' // Incluir cookies de sessão
    })
        .then(response => {
            console.log('📡 Resposta da API veículos:', response.status);
            console.log('📡 Headers da resposta:', response.headers);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
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
                    
                    // Garantir que nenhum item seja selecionado automaticamente
                    selectVeiculo.selectedIndex = 0; // Sempre selecionar o primeiro item (placeholder)
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
    
    // Debug: mostrar dados que serão enviados
    console.log('📋 Dados do formulário:', dados);
    
    // Verificar se tipo_agendamento está sendo enviado
    const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked');
    if (tipoAgendamento) {
        dados.tipo_agendamento = tipoAgendamento.value;
        console.log('📋 Tipo de agendamento:', tipoAgendamento.value);
    } else {
        console.warn('⚠️ Nenhum tipo de agendamento selecionado!');
    }
    
    // Verificar posição do intervalo para 3 aulas
    const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked');
    if (posicaoIntervalo) {
        dados.posicao_intervalo = posicaoIntervalo.value;
        console.log('📋 Posição do intervalo:', posicaoIntervalo.value);
    }
    
    console.log('📋 Dados finais para envio:', dados);
    
    // Validar se IDs são válidos antes de enviar
    const instrutorId = dados.instrutor_id;
    const veiculoId = dados.veiculo_id;
    
    if (!instrutorId || instrutorId === '' || instrutorId === '0') {
        alert('Por favor, selecione um instrutor válido.');
        return;
    }
    
    if (dados.tipo_aula !== 'teorica' && (!veiculoId || veiculoId === '' || veiculoId === '0')) {
        alert('Por favor, selecione um veículo válido para aulas práticas.');
        return;
    }
    
    // Verificar se não está enviando IDs inexistentes (como 1)
    const selectInstrutor = document.getElementById('instrutor_id');
    const instrutorOption = selectInstrutor.querySelector(`option[value="${instrutorId}"]`);
    if (!instrutorOption || instrutorOption.disabled) {
        alert('O instrutor selecionado não é válido. Por favor, selecione outro instrutor.');
        return;
    }
    
    if (dados.tipo_aula !== 'teorica') {
        const selectVeiculo = document.getElementById('veiculo_id');
        const veiculoOption = selectVeiculo.querySelector(`option[value="${veiculoId}"]`);
        if (!veiculoOption || veiculoOption.disabled) {
            alert('O veículo selecionado não é válido. Por favor, selecione outro veículo.');
            return;
        }
    }
    
    console.log('✅ Validação de IDs passou - instrutor:', instrutorId, 'veículo:', veiculoId);
    
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
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.text().then(text => {
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
        if (data.success) {
            mostrarAlerta('Aula agendada com sucesso!', 'success');
            fecharModalNovaAula();
            
            // Recarregar página após um breve delay
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            const mensagemErro = data.mensagem || 'Erro desconhecido';
            mostrarAlerta(mensagemErro, 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro de conexão. Verifique sua internet e tente novamente.', 'danger');
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
        
        // SEMPRE definir como criar novo aluno quando esta função é chamada
        const acaoAluno = document.getElementById('acaoAluno');
        if (acaoAluno) {
            acaoAluno.value = 'criar';
            console.log('✅ Campo acaoAluno definido como: criar');
        }
        
        console.log('🔍 Modal aberto - Editando? false (sempre criar novo)');
        
        // SEMPRE limpar formulário para novo aluno
        const formAluno = document.getElementById('formAluno');
        if (formAluno) {
            formAluno.reset();
            console.log('🧹 Formulário limpo para novo aluno');
        }
        
        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-user-graduate me-2"></i>Novo Aluno';
        }
        
        // Limpar seção de operações para novo aluno
        const operacoesContainer = document.getElementById('operacoes-container');
        if (operacoesContainer) {
            operacoesContainer.innerHTML = '';
            contadorOperacoes = 0;
            console.log('🧹 Seção de operações limpa');
        }
        
        const alunoIdField = document.getElementById('aluno_id_hidden');
        if (alunoIdField) alunoIdField.value = ''; // Limpar ID
        
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
// Removido: função carregarCategoriasCNH() - não é mais necessária

// Função para salvar aluno via AJAX
function salvarAluno() {
    const form = document.getElementById('formAluno');
    const formData = new FormData(form);
    
    // Mostrar loading no botão
    const btnSalvar = document.getElementById('btnSalvarAluno');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSalvar.disabled = true;
    
    // Coletar operações de habilitação
    const operacoes = coletarDadosOperacoes();
    
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
        // Removido: tipo_servico e categoria_cnh - agora usamos apenas operacoes
        operacoes: operacoes, // Adicionar operações
        atividade_remunerada: formData.get('atividade_remunerada') ? 1 : 0,
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
    console.log('📤 Enviando dados para API:', dados);
    console.log('📤 Operações coletadas:', operacoes);
    console.log('📤 Ação:', acao);
    console.log('📤 Aluno ID:', alunoId);
    
    const timestamp = new Date().getTime();
    fetch(`api/alunos.php?t=${timestamp}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(response => {
        console.log('Resposta da API:', response);
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

// Sistema de Operações de Habilitação
let contadorOperacoes = 0;

function adicionarOperacao() {
    contadorOperacoes++;
    const container = document.getElementById('operacoes-container');
    
    if (!container) {
        console.error('❌ Container de operações não encontrado!');
        alert('ERRO: Container de operações não encontrado!');
        return;
    }
    
    const operacaoHtml = `
        <div class="operacao-item border rounded p-2 mb-2" data-operacao-id="${contadorOperacoes}">
            <div class="row align-items-center">
                <div class="col-md-3">
                    <select class="form-select form-select-sm" name="operacao_tipo_${contadorOperacoes}" onchange="carregarCategoriasOperacao(${contadorOperacoes})">
                        <option value="">Tipo de Operação</option>
                        <option value="primeira_habilitacao">🏍️ Primeira Habilitação</option>
                        <option value="adicao">➕ Adição de Categoria</option>
                        <option value="mudanca">🔄 Mudança de Categoria</option>
                        <option value="aula_avulsa">📚 Aula Avulsa</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm" name="operacao_categoria_${contadorOperacoes}" disabled>
                        <option value="">Selecione o tipo primeiro</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerOperacao(${contadorOperacoes})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    `;
    
    container.insertAdjacentHTML('beforeend', operacaoHtml);
}

function carregarCategoriasOperacao(operacaoId, categoriaSelecionada = '', tipoServicoParam = '') {
    console.log('🔄 Carregando categorias para operação:', operacaoId);
    
    const tipoSelect = document.querySelector(`select[name="operacao_tipo_${operacaoId}"]`);
    const categoriaSelect = document.querySelector(`select[name="operacao_categoria_${operacaoId}"]`);
    
    if (!tipoSelect) {
        console.warn(`⚠️ Select de tipo não encontrado para operação ${operacaoId}`);
        return;
    }
    
    if (!categoriaSelect) {
        console.warn(`⚠️ Select de categoria não encontrado para operação ${operacaoId}`);
        return;
    }
    
    // Usar o tipo passado como parâmetro ou o valor do select
    console.log(`🔍 tipoServicoParam recebido:`, tipoServicoParam);
    console.log(`🔍 tipoSelect.value:`, tipoSelect ? tipoSelect.value : 'não existe');
    const tipoServico = tipoServicoParam || (tipoSelect ? tipoSelect.value : '');
    console.log(`🔍 tipoServico final:`, tipoServico);
    
    // Limpar opções anteriores
    categoriaSelect.innerHTML = '<option value="">Selecione a categoria...</option>';
    
    if (!tipoServico) {
        categoriaSelect.disabled = true;
        return;
    }
    
    // Usar a definição global de categoriasPorTipo
    console.log(`⚙️ Tipo de serviço: ${tipoServico}`);
    console.log(`⚙️ Categorias disponíveis:`, categoriasPorTipo[tipoServico]);
    
    const categorias = categoriasPorTipo[tipoServico] || [];
    
    // Adicionar opções ao select
    categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.value;
        option.textContent = cat.text;
        if (cat.value === categoriaSelecionada) {
            option.selected = true;
            console.log(`✅ Categoria selecionada: ${cat.value} - ${cat.text}`);
        }
        categoriaSelect.appendChild(option);
    });
    
    // Habilitar select
    categoriaSelect.disabled = false;
    console.log(`⚙️ Select habilitado para operação ${operacaoId}`);
}

function removerOperacao(operacaoId) {
    const operacaoItem = document.querySelector(`[data-operacao-id="${operacaoId}"]`);
    if (operacaoItem) {
        operacaoItem.remove();
    }
}

// Função para coletar dados das operações ao salvar
function coletarDadosOperacoes() {
    const operacoes = [];
    const operacaoItems = document.querySelectorAll('.operacao-item');
    
    console.log('📋 Coletando operações - Total de itens encontrados:', operacaoItems.length);
    
    operacaoItems.forEach((item, index) => {
        const operacaoId = item.getAttribute('data-operacao-id');
        const tipo = document.querySelector(`select[name="operacao_tipo_${operacaoId}"]`)?.value;
        const categoria = document.querySelector(`select[name="operacao_categoria_${operacaoId}"]`)?.value;
        
        console.log(`📋 Operação ${index + 1} (ID: ${operacaoId}):`, { tipo, categoria });
        
        if (tipo && categoria) {
            operacoes.push({
                tipo: tipo,
                categoria: categoria
            });
            console.log('✅ Operação adicionada:', { tipo, categoria });
        } else {
            console.log('⚠️ Operação ignorada - campos vazios:', { tipo, categoria });
        }
    });
    
    console.log('📋 Total de operações coletadas:', operacoes.length);
    console.log('📋 Operações finais:', operacoes);
    
    return operacoes;
}

// Função para carregar operações existentes ao editar aluno
function carregarOperacoesExistentes(operacoes) {
    console.log('🔄 Carregando operações existentes:', operacoes);
    console.log('🔄 Tipo de operacoes:', typeof operacoes);
    console.log('🔄 Array?', Array.isArray(operacoes));
    console.log('🔄 Quantidade:', operacoes ? operacoes.length : 'undefined');
    
    // Limpar operações atuais com verificação de segurança
    const operacoesContainer = document.getElementById('operacoes-container');
    if (operacoesContainer) {
        operacoesContainer.innerHTML = '';
        contadorOperacoes = 0;
        console.log('✅ Container de operações limpo');
    } else {
        console.warn('⚠️ Container de operações não encontrado');
        return;
    }
    
    // Verificar se operacoes é um array válido
    if (!Array.isArray(operacoes) || operacoes.length === 0) {
        console.log('⚠️ Nenhuma operação para carregar ou operacoes não é array');
        return;
    }
    
    // Adicionar cada operação existente
    console.log(`🔄 Iniciando processamento de ${operacoes.length} operações`);
    console.log(`🔄 Contador inicial: ${contadorOperacoes}`);
    
    operacoes.forEach((operacao, index) => {
        console.log(`🔄 Processando operação ${index}:`, operacao);
        console.log(`🔄 Operação ${index} - tipo:`, operacao.tipo);
        console.log(`🔄 Operação ${index} - categoria:`, operacao.categoria);
        contadorOperacoes++;
        console.log(`🔄 Contador de operações agora é: ${contadorOperacoes}`);
        const container = document.getElementById('operacoes-container');
        console.log(`🔄 Container encontrado:`, container ? '✅' : '❌');
        
        const operacaoHtml = `
            <div class="operacao-item border rounded p-2 mb-2" data-operacao-id="${contadorOperacoes}">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="operacao_tipo_${contadorOperacoes}" onchange="carregarCategoriasOperacao(${contadorOperacoes})">
                            <option value="">Tipo de Operação</option>
                            <option value="primeira_habilitacao" ${operacao.tipo === 'primeira_habilitacao' ? 'selected' : ''}>🏍️ Primeira Habilitação</option>
                            <option value="adicao" ${operacao.tipo === 'adicao' ? 'selected' : ''}>➕ Adição de Categoria</option>
                            <option value="mudanca" ${operacao.tipo === 'mudanca' ? 'selected' : ''}>🔄 Mudança de Categoria</option>
                            <option value="aula_avulsa" ${operacao.tipo === 'aula_avulsa' ? 'selected' : ''}>📚 Aula Avulsa</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select form-select-sm" name="operacao_categoria_${contadorOperacoes}" disabled>
                            <option value="">Selecione o tipo primeiro</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerOperacao(${contadorOperacoes})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', operacaoHtml);
        console.log(`✅ HTML inserido para operação ${contadorOperacoes}`);
        
        // Elemento inserido com sucesso
        
        // Carregar categorias para esta operação
        // Capturar o valor atual do contador para evitar closure
        const operacaoIdAtual = contadorOperacoes;
        setTimeout(() => {
            console.log(`⚙️ Carregando categorias para operação ${operacaoIdAtual} com categoria: ${operacao.categoria}`);
            
            // Verificar se o select existe antes de acessar .value
            const tipoSelect = document.querySelector(`select[name="operacao_tipo_${operacaoIdAtual}"]`);
            if (tipoSelect) {
                console.log(`⚙️ Valor do select tipo:`, tipoSelect.value);
            } else {
                console.warn(`⚠️ Select de tipo não encontrado para operação ${operacaoIdAtual}`);
            }
            
            carregarCategoriasOperacao(operacaoIdAtual, operacao.categoria, operacao.tipo);
        }, 100);
    });
}
</script>