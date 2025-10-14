<?php
// =====================================================
// SISTEMA DE EXAMES MÉDICO E PSICOTÉCNICO - SISTEMA CFC
// Gestão completa de exames com calendário e status
// =====================================================

// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    // Se acessado diretamente, redirecionar para o sistema de roteamento
    header('Location: ../index.php?page=exames');
    exit;
}

// Verificar se as variáveis estão definidas
if (!isset($alunos)) $alunos = [];
if (!isset($exames)) $exames = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';

// Verificar se usuário é admin usando a mesma função da API
$isAdmin = false;
$user = getCurrentUser();
if ($user && $user['tipo'] === 'admin') {
    $isAdmin = true;
}

// Verificação de admin concluída

// Obter dados necessários para a página
try {
    $db = db();
    
    // Buscar alunos ativos
    $alunos = $db->fetchAll("
        SELECT a.*, c.nome as cfc_nome
        FROM alunos a
        JOIN cfcs c ON a.cfc_id = c.id
        WHERE a.status = 'ativo' 
        ORDER BY a.nome
    ");
    
    // Buscar exames recentes (últimos 30 dias e próximos 30 dias)
    $exames = $db->fetchAll("
        SELECT e.*, a.nome as aluno_nome, a.cpf as aluno_cpf,
               c.nome as cfc_nome
        FROM exames e
        JOIN alunos a ON e.aluno_id = a.id
        JOIN cfcs c ON a.cfc_id = c.id
        WHERE e.data_agendada >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
           OR e.data_agendada <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
        ORDER BY e.data_agendada DESC, e.tipo
    ");
    
} catch (Exception $e) {
    error_log("Erro ao carregar dados dos exames: " . $e->getMessage());
    $alunos = [];
    $exames = [];
}
?>

<style>
/* =====================================================
   ESTILOS PARA SISTEMA DE EXAMES
   ===================================================== */

.exames-container {
    max-width: 1200px;
    margin: 0 auto;
    padding: 20px;
}

.exames-header {
    background: #023A8D;
    color: white;
    padding: 30px;
    border-radius: 15px;
    margin-bottom: 30px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.exames-header h1 {
    margin: 0;
    font-size: 2.5rem;
    font-weight: 700;
}

.exames-header p {
    margin: 10px 0 0 0;
    opacity: 0.9;
    font-size: 1.1rem;
}

/* Cards de Status */
.status-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.status-card {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    border-left: 5px solid #023A8D;
    transition: transform 0.3s ease;
}

.status-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
}

.status-card.success {
    border-left-color: #28a745;
}

.status-card.warning {
    border-left-color: #ffc107;
}

.status-card.danger {
    border-left-color: #dc3545;
}

.status-card.info {
    border-left-color: #17a2b8;
}

.status-icon {
    font-size: 2.5rem;
    margin-bottom: 15px;
    color: #023A8D;
}

.status-card.success .status-icon {
    color: #28a745;
}

.status-card.warning .status-icon {
    color: #ffc107;
}

.status-card.danger .status-icon {
    color: #dc3545;
}

.status-card.info .status-icon {
    color: #17a2b8;
}

.status-number {
    font-size: 2.5rem;
    font-weight: 700;
    margin-bottom: 5px;
    color: #2c3e50;
}

.status-label {
    font-size: 1rem;
    color: #6c757d;
    font-weight: 500;
}

/* Filtros */
.filtros-container {
    background: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 30px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.filtros-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    align-items: end;
}

/* Tabela de Exames */
.exames-table-container {
    background: white;
    border-radius: 15px;
    padding: 25px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.table {
    margin-bottom: 0;
}

.table th {
    border-top: none;
    border-bottom: 2px solid #023A8D;
    color: #023A8D;
    font-weight: 600;
    padding: 15px 12px;
}

.table td {
    padding: 15px 12px;
    vertical-align: middle;
    border-bottom: 1px solid #f1f3f4;
}

/* Badges de Status */
.badge-status {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-agendado {
    background-color: #F7931E;
    color: white;
    border: none;
}

.badge-concluido {
    background-color: #28a745;
    color: white;
    border: none;
}

.badge-cancelado {
    background-color: #dc3545;
    color: white;
    border: none;
}

/* Badges de Resultado */
.badge-resultado {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.85rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.badge-apto {
    background-color: #28a745;
    color: white;
    border: none;
}

.badge-inapto {
    background-color: #dc3545;
    color: white;
    border: none;
}

.badge-inapto-temporario {
    background-color: #F7931E;
    color: white;
    border: none;
}

.badge-pendente {
    background-color: #F7931E;
    color: white;
    border: none;
}

/* Botões de Ação */
.btn-exame {
    padding: 8px 16px;
    border-radius: 8px;
    font-weight: 500;
    font-size: 0.9rem;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
    margin: 2px;
}

.btn-agendar {
    background-color: #023A8D;
    color: white;
    border: none;
}

.btn-agendar:hover {
    background-color: #022a6b;
    transform: translateY(-2px);
}

.btn-resultado {
    background-color: #F7931E;
    color: white;
    border: none;
}

.btn-resultado:hover {
    background-color: #e6851a;
    transform: translateY(-2px);
}

.btn-cancelar {
    background-color: #dc3545;
    color: white;
    border: none;
}

.btn-cancelar:hover {
    background-color: #c82333;
    transform: translateY(-2px);
}

/* Modal de Exame */
.modal-exame .modal-content {
    border-radius: 15px;
    border: none;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
}

/* Modal de Exames - CSS ULTRA ESPECÍFICO */
html body #modalAgendarExame {
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    z-index: 1055 !important;
    display: none !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;
    margin: 0 !important;
    overflow: visible !important;
    max-width: none !important;
}

html body #modalAgendarExame.show {
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    padding: 0 !important;  /* REMOVER: padding não deve estar no overlay */
}

#modalAgendarExame .modal-content {
    height: auto !important;
    max-height: none !important;
}

#modalAgendarExame .modal-body {
    overflow: visible !important;
    max-height: none !important;
    height: auto !important;
    padding: 2rem !important;
}

/* Desktop - Layout amplo */
@media (min-width: 992px) {
    /* Garantir que o modal seja centralizado quando visível */
    html body #modalAgendarExame.show {
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        padding: 0 !important;  /* REMOVER: padding não deve estar no overlay */
    }
    
    /* Garantir que o modal-dialog seja centralizado verticalmente COM GUTTER */
    html body #modalAgendarExame .modal-dialog {
        margin: 4rem auto !important;  /* GUTTER DESKTOP: 4rem top/bottom, auto left/right */
        align-self: center !important;
        max-height: calc(100vh - 8rem) !important;  /* Respeitar gutter vertical */
    }
    
    /* Largura responsiva para desktop COM GUTTER LATERAL */
    html body #modalAgendarExame .modal-dialog,
    html body .modal#modalAgendarExame .modal-dialog,
    html body .modal.fade#modalAgendarExame .modal-dialog,
    html body .modal.show#modalAgendarExame .modal-dialog,
    html body .modal.modal-exame#modalAgendarExame .modal-dialog {
        margin: 4rem auto !important;  /* GUTTER DESKTOP: 4rem top/bottom, auto left/right */
        max-width: min(1400px, calc(100vw - 8rem)) !important;  /* Respeitar gutter lateral */
        width: min(1400px, calc(100vw - 8rem)) !important;  /* Respeitar gutter lateral */
        position: relative !important;
        left: auto !important;
        right: auto !important;
        top: auto !important;
        bottom: auto !important;
        transform: none !important;
        flex: none !important;
    }
    
    #modalAgendarExame .form-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr 1fr !important;
        gap: 1.25rem 2rem !important;  /* REDUZIDO: 1.25rem vertical, 2rem horizontal */
    }
    
    #modalAgendarExame .form-grid .field-full {
        grid-column: 1 / -1 !important;
    }
    
    #modalAgendarExame .form-floating {
        margin-bottom: 0.75rem !important;  /* REDUZIDO: de 1.75rem para 0.75rem */
    }
    
    #modalAgendarExame .form-floating > .form-control,
    #modalAgendarExame .form-floating > .form-select {
        height: calc(5rem + 2px) !important;  /* AUMENTADO: de 4.25rem para 5rem para evitar clipping */
        padding: 1.75rem 1rem 1.75rem 1rem !important;  /* AUMENTADO: bottom padding de 1.5rem para 1.75rem */
        font-size: 1.1rem !important;
        line-height: 1.5 !important;  /* FIX: line-height adequado para evitar clipping */
    }
    
    #modalAgendarExame .form-floating > label {
        padding: 1.75rem 1rem 0.5rem 1rem !important;  /* AUMENTADO: bottom padding de 0 para 0.5rem */
        font-size: 1.1rem !important;
        margin-bottom: 0.5rem !important;  /* REDUZIDO MOBILE: de 0.75rem para 0.5rem */
        transition: transform 0.2s ease-in-out !important;  /* Transição suave */
    }
    
    /* Estados filled/focus para desktop */
    #modalAgendarExame .form-floating > .form-control:not(:placeholder-shown) ~ label,
    #modalAgendarExame .form-floating > .form-select:not([value=""]) ~ label,
    #modalAgendarExame .form-floating > input[type="date"]:not(:placeholder-shown) ~ label,
    #modalAgendarExame .form-floating > .form-control:focus ~ label,
    #modalAgendarExame .form-floating > .form-select:focus ~ label,
    #modalAgendarExame .form-floating > input[type="date"]:focus ~ label,
    #modalAgendarExame .form-floating > .form-control.has-value ~ label,
    #modalAgendarExame .form-floating > .form-select.has-value ~ label,
    #modalAgendarExame .form-floating > input[type="date"].has-value ~ label,
    #modalAgendarExame .form-floating > .form-control.is-focused ~ label,
    #modalAgendarExame .form-floating > .form-select.is-focused ~ label,
    #modalAgendarExame .form-floating > input[type="date"].is-focused ~ label {
        transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;  /* GAP FORÇADO: -1.5rem */
        opacity: 0.65 !important;
    }
}

/* Mobile - Tela cheia com scroll */
@media (max-width: 991px) {
    #modalAgendarExame {
        padding: 0 !important;  /* REMOVER: padding não deve estar no overlay */
    }
    
    #modalAgendarExame .modal-dialog {
        max-width: calc(100vw - 2rem) !important;  /* GUTTER MOBILE: 1rem de cada lado */
        width: calc(100vw - 2rem) !important;  /* GUTTER MOBILE: 1rem de cada lado */
        margin: 1rem auto !important;  /* GUTTER MOBILE: 1rem top/bottom, auto left/right */
        height: calc(100vh - 2rem) !important;  /* Respeitar gutter vertical */
        max-height: calc(100vh - 2rem) !important;  /* Respeitar gutter vertical */
    }
    
    #modalAgendarExame .modal-content {
        width: 100% !important;
        height: 100% !important;
        max-height: 90vh !important;
        display: flex !important;
        flex-direction: column !important;
    }
    
    #modalAgendarExame .modal-body {
        flex: 1 !important;
        padding: 1.5rem !important;  /* PADDING MOBILE: reduzido mas consistente */
        overflow-y: auto !important;
        max-height: calc(90vh - 120px) !important;
    }
    
    #modalAgendarExame .form-grid {
        width: 100% !important;
        display: grid !important;
        grid-template-columns: 1fr !important;
        gap: 0.75rem !important;  /* REDUZIDO: de 1rem para 0.75rem */
    }
}

#modalAgendarExame .modal-header,
#modalAgendarExame .modal-footer {
    flex-shrink: 0 !important;
}

/* REGRA CORRIGIDA PARA LARGURA RESPONSIVA E CENTRALIZAÇÃO */
@media (min-width: 992px) {
    /* Largura responsiva baseada no viewport COM GUTTER */
    html body .modal#modalAgendarExame .modal-dialog,
    html body .modal.fade#modalAgendarExame .modal-dialog,
    html body .modal.show#modalAgendarExame .modal-dialog,
    html body .modal.modal-exame#modalAgendarExame .modal-dialog,
    html body .modal-dialog-centered#modalAgendarExame .modal-dialog,
    html body #modalAgendarExame.modal .modal-dialog.modal-dialog-centered {
        max-width: min(1400px, calc(100vw - 8rem)) !important;  /* Respeitar gutter lateral */
        width: min(1400px, calc(100vw - 8rem)) !important;  /* Respeitar gutter lateral */
        min-width: min(1200px, calc(100vw - 8rem)) !important;  /* Respeitar gutter lateral */
        margin: 4rem auto !important;  /* GUTTER DESKTOP: 4rem top/bottom, auto left/right */
        position: relative !important;
        left: auto !important;
        right: auto !important;
        top: auto !important;
        bottom: auto !important;
        transform: none !important;
        flex: none !important;
    }
    
    /* Garantir que o conteúdo também seja largo */
    html body .modal#modalAgendarExame .modal-content,
    html body .modal.fade#modalAgendarExame .modal-content,
    html body .modal.show#modalAgendarExame .modal-content,
    html body .modal.modal-exame#modalAgendarExame .modal-content {
        width: 100% !important;
        max-width: 100% !important;
        min-width: 100% !important;
    }
    
    /* Garantir que o modal-body não tenha scroll no desktop */
    html body .modal#modalAgendarExame .modal-body,
    html body .modal.fade#modalAgendarExame .modal-body,
    html body .modal.show#modalAgendarExame .modal-body,
    html body .modal.modal-exame#modalAgendarExame .modal-body {
        overflow: visible !important;
        max-height: none !important;
        height: auto !important;
    }
}

.modal-exame .modal-header {
    background: #023A8D;
    color: white;
    border-radius: 15px 15px 0 0;
    border-bottom: none;
    padding: 1.5rem 2rem;  /* PADDING INTERNO: consistente com body e footer */
}

.modal-exame .modal-title {
    font-weight: 600;
    font-size: 1.3rem;
}

.modal-exame .modal-body {
    padding: 2rem;  /* PADDING INTERNO: espaçamento interno consistente */
}

.modal-exame .modal-footer {
    border-top: 1px solid #f1f3f4;
    padding: 1.5rem 2rem;  /* PADDING INTERNO: consistente com o body */
    background-color: #f8f9fa;
    border-radius: 0 0 15px 15px;
}

/* Layout responsivo do formulário */
#modalAgendarExame .form-grid {
    display: grid;
    gap: 1rem;  /* REDUZIDO: de 1.5rem para 1rem */
    width: 100%;
}

/* Desktop - 3 colunas */
@media (min-width: 992px) {
    #modalAgendarExame .form-grid {
        grid-template-columns: 1fr 1fr 1fr;
        grid-template-rows: auto auto auto;
    }
    
    #modalAgendarExame .form-grid .field-full {
        grid-column: 1 / -1;
    }
}

/* Tablet - 2 colunas */
@media (min-width: 768px) and (max-width: 991px) {
    #modalAgendarExame .form-grid {
        grid-template-columns: 1fr 1fr;
        grid-template-rows: auto auto auto auto;
    }
    
    #modalAgendarExame .form-grid .field-full {
        grid-column: 1 / -1;
    }
}

/* Mobile - 1 coluna */
@media (max-width: 767px) {
    #modalAgendarExame .form-grid {
        grid-template-columns: 1fr;
        grid-template-rows: auto;
    }
}

/* Mobile - Modal responsivo */
@media (max-width: 767px) {
    #modalAgendarExame .modal-dialog {
        max-width: calc(100vw - 1rem);  /* GUTTER MOBILE PEQUENO: 0.5rem de cada lado */
        width: calc(100vw - 1rem);  /* GUTTER MOBILE PEQUENO: 0.5rem de cada lado */
        margin: 0.5rem auto;  /* GUTTER MOBILE PEQUENO: 0.5rem top/bottom, auto left/right */
    }
    
    #modalAgendarExame .modal-body {
        padding: 1rem;
    }
    
    #modalAgendarExame .modal-header {
        padding: 1.25rem 1.5rem;  /* PADDING MOBILE: consistente */
    }
    
    #modalAgendarExame .modal-footer {
        padding: 1.25rem 1.5rem;  /* PADDING MOBILE: consistente */
        flex-direction: column;
        gap: 0.5rem;
    }
    
    #modalAgendarExame .btn {
        width: 100%;
        margin: 0;
    }
}

/* Estilos específicos para o modal de agendar exame */
.modal-exame .form-floating {
    margin-bottom: 1.25rem;
}

/* CORREÇÃO FORÇADA: Labels flutuantes com gap adequado */
.modal-exame .form-floating > .form-control:focus ~ label,
.modal-exame .form-floating > .form-select:focus ~ label,
.modal-exame .form-floating > input[type="date"]:focus ~ label {
    color: #023A8D !important;
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;  /* GAP FORÇADO: -1.5rem */
    opacity: 0.65 !important;
}

.modal-exame .form-floating > .form-control:not(:placeholder-shown) ~ label,
.modal-exame .form-floating > .form-select:not([value=""]) ~ label,
.modal-exame .form-floating > .form-control[value]:not([value=""]) ~ label,
.modal-exame .form-floating > .form-select[value]:not([value=""]) ~ label,
.modal-exame .form-floating > input[type="date"][value]:not([value=""]) ~ label,
.modal-exame .form-floating > input[type="date"]:not(:placeholder-shown) ~ label {
    opacity: 0.65 !important;
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;  /* GAP FORÇADO: -1.5rem */
}

.modal-exame .form-floating > label {
    padding: 1.25rem 0.75rem 0.5rem 0.75rem;  /* AUMENTADO: bottom padding de 0 para 0.5rem */
    margin-bottom: 0.5rem;
    transition: transform 0.2s ease-in-out;  /* Transição suave */
}

.modal-exame .form-floating > .form-control,
.modal-exame .form-floating > .form-select {
    height: calc(4.5rem + 2px);  /* AUMENTADO: de 3.75rem para 4.5rem para evitar clipping */
    padding: 1.5rem 0.75rem 1.25rem 0.75rem;  /* AUMENTADO: bottom padding de 1rem para 1.25rem */
    line-height: 1.5;  /* FIX: line-height adequado para evitar clipping */
}

.modal-exame .form-floating > .form-control:focus,
.modal-exame .form-floating > .form-select:focus {
    border-color: #023A8D;
    box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
}

/* Regras específicas para textarea */
.modal-exame .form-floating > textarea.form-control {
    padding-top: 1.5rem !important;
    overflow-y: hidden !important;
    resize: vertical !important;
    min-height: 80px !important;
}

.modal-exame .form-floating > textarea.form-control:focus {
    overflow-y: auto !important;
}

/* Regras específicas para input[type="date"] */
.modal-exame .form-floating > input[type="date"]:not(:placeholder-shown) ~ label,
.modal-exame .form-floating > input[type="date"]:focus ~ label,
.modal-exame .form-floating > input[type="date"][value]:not([value=""]) ~ label {
    opacity: 0.65 !important;
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;  /* GAP FORÇADO: -1.5rem */
}

/* Classes CSS para controle dinâmico via JavaScript */
.modal-exame .form-floating > .form-control.has-value ~ label,
.modal-exame .form-floating > .form-select.has-value ~ label,
.modal-exame .form-floating > input[type="date"].has-value ~ label {
    opacity: 0.65 !important;
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;  /* GAP FORÇADO: -1.5rem */
}

.modal-exame .form-floating > .form-control.is-focused ~ label,
.modal-exame .form-floating > .form-select.is-focused ~ label,
.modal-exame .form-floating > input[type="date"].is-focused ~ label {
    color: #023A8D !important;
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;  /* GAP FORÇADO: -1.5rem */
    opacity: 0.65 !important;
}

/* REGRA SUPER ESPECÍFICA: Sobrescrever Bootstrap completamente */
html body .modal-exame .form-floating > .form-control:not(:placeholder-shown) ~ label,
html body .modal-exame .form-floating > .form-select:not([value=""]) ~ label,
html body .modal-exame .form-floating > input[type="date"]:not(:placeholder-shown) ~ label,
html body .modal-exame .form-floating > .form-control:focus ~ label,
html body .modal-exame .form-floating > .form-select:focus ~ label,
html body .modal-exame .form-floating > input[type="date"]:focus ~ label {
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;
    opacity: 0.65 !important;
}

/* REGRA RADICAL: Forçar em TODOS os labels quando há valor */
html body .modal-exame .form-floating label {
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;
    opacity: 0.65 !important;
}

/* REGRA ULTRA ESPECÍFICA: Para o modal de exames especificamente */
html body #modalAgendarExame .form-floating label,
html body #modalAgendarExame .modal-exame .form-floating label {
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;
    opacity: 0.65 !important;
}

/* REGRA FINAL: Forçar em TODOS os estados possíveis */
html body #modalAgendarExame .form-floating > .form-control ~ label,
html body #modalAgendarExame .form-floating > .form-select ~ label,
html body #modalAgendarExame .form-floating > input[type="date"] ~ label,
html body #modalAgendarExame .form-floating > textarea ~ label {
    transform: scale(0.85) translateY(-1.5rem) translateX(0.15rem) !important;
    opacity: 0.65 !important;
}

/* Melhorias para seções */
.modal-exame h6 {
    font-weight: 600;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 0.5rem;
}

.modal-exame .text-primary {
    color: #023A8D !important;
}

/* Alert personalizado */
.modal-exame .alert-info {
    background-color: #e3f2fd;
    border-color: #023A8D;
    color: #023A8D;
    padding: 0.75rem 1rem;  /* COMPACTO: padding reduzido para consistência */
    margin-bottom: 0;  /* REMOVER: margin bottom para evitar espaços extras */
}

.modal-exame .alert-info i {
    color: #F7931E;
}

/* Botões melhorados */
.modal-exame .btn-primary {
    background-color: #023A8D;
    border-color: #023A8D;
    font-weight: 600;
    padding: 0.75rem 1.5rem;
}

.modal-exame .btn-primary:hover {
    background-color: #022a73;
    border-color: #022a73;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(2, 58, 141, 0.3);
}

.modal-exame .btn-outline-secondary {
    font-weight: 600;
    padding: 0.75rem 1.5rem;
}

.modal-exame .btn-outline-secondary:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
}

/* Formulário */
.form-group {
    margin-bottom: 20px;
}

.form-label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.form-control {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 12px 15px;
    font-size: 1rem;
    transition: border-color 0.3s ease, box-shadow 0.3s ease;
}

.form-control:focus {
    border-color: #023A8D;
    box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
}

/* Responsividade - Mobile First */
@media (max-width: 1200px) {
    .exames-container {
        padding: 15px;
    }
    
    .status-cards {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .filtros-row {
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
}

@media (max-width: 768px) {
    .exames-container {
        padding: 10px;
    }
    
    .exames-header {
        padding: 15px;
        text-align: center;
        margin-bottom: 20px;
    }
    
    .exames-header h1 {
        font-size: 1.8rem;
        margin-bottom: 10px;
    }
    
    .exames-header p {
        font-size: 1rem;
    }
    
    .status-cards {
        grid-template-columns: 1fr;
        gap: 15px;
        margin-bottom: 20px;
    }
    
    .status-card {
        padding: 20px;
    }
    
    .status-number {
        font-size: 2rem;
    }
    
    .filtros-container {
        padding: 20px;
        margin-bottom: 20px;
    }
    
    .filtros-row {
        grid-template-columns: 1fr;
        gap: 15px;
    }
    
    .exames-table-container {
        padding: 15px;
        overflow-x: auto;
    }
    
    .exames-table-container h3 {
        font-size: 1.3rem;
        margin-bottom: 15px;
    }
    
    /* Tabela responsiva - Cards em mobile */
    .table {
        display: none; /* Esconder tabela em mobile */
    }
    
    .mobile-exam-cards {
        display: block;
    }
    
    .exam-card {
        background: white;
        border: 1px solid #e9ecef;
        border-radius: 12px;
        padding: 15px;
        margin-bottom: 15px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .exam-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 12px;
        padding-bottom: 10px;
        border-bottom: 1px solid #f1f3f4;
    }
    
    .exam-student-info h4 {
        margin: 0;
        font-size: 1.1rem;
        color: #2c3e50;
        font-weight: 600;
    }
    
    .exam-student-info small {
        color: #6c757d;
        font-size: 0.85rem;
    }
    
    .exam-type-badge {
        padding: 4px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
    }
    
    .exam-type-medico {
        background-color: #e3f2fd;
        color: #1976d2;
    }
    
    .exam-type-psicotecnico {
        background-color: #f3e5f5;
        color: #7b1fa2;
    }
    
    .exam-details {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
    }
    
    .exam-details .exam-detail-item:nth-child(3) {
        grid-column: 1;
    }
    
    .exam-details .exam-detail-item:nth-child(4) {
        grid-column: 2;
    }
    
    /* Garantir ordem específica para status e data resultado */
    .exam-status-item {
        grid-column: 1;
        grid-row: 2;
    }
    
    .exam-result-date-item {
        grid-column: 2;
        grid-row: 2;
    }
    
    .exam-detail-item {
        display: flex;
        flex-direction: column;
    }
    
    .exam-detail-label {
        font-size: 0.8rem;
        color: #6c757d;
        font-weight: 500;
        margin-bottom: 2px;
    }
    
    .exam-detail-value {
        font-size: 0.9rem;
        color: #2c3e50;
        font-weight: 500;
    }
    
    /* Badges menores no mobile */
    .exam-detail-value .badge-status {
        font-size: 0.75rem;
        padding: 4px 8px;
        border-radius: 12px;
    }
    
    .exam-actions {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }
    
    .btn-exame {
        padding: 8px 12px;
        font-size: 0.85rem;
        border-radius: 6px;
        flex: 1;
        min-width: 80px;
        text-align: center;
    }
    
    /* Badges de ação menores no mobile */
    .exam-actions .badge {
        font-size: 0.8rem;
        padding: 6px 12px;
        border-radius: 8px;
    }
    
    .resultado-select {
        min-width: 100%;
        font-size: 0.85rem;
        padding: 8px 35px 8px 10px;
    }
    
    /* Modal responsivo */
    .modal-exame .modal-dialog {
        margin: 10px;
        max-width: calc(100% - 20px);
    }
    
    .modal-exame .modal-body {
        padding: 20px;
    }
    
    .modal-exame .modal-header {
        padding: 20px;
    }
    
    .modal-exame .modal-footer {
        padding: 15px 20px;
    }
    
    /* Ajustes específicos para mobile no modal de agendar exame */
    .modal-exame .modal-xl {
        max-width: 100%;
    }
    
    .modal-exame .row.g-3 {
        --bs-gutter-x: 1rem;
    }
    
    .modal-exame .form-floating > .form-control,
    .modal-exame .form-floating > .form-select {
        height: calc(4rem + 2px);  /* AUMENTADO: de 3.25rem para 4rem para evitar clipping */
        padding: 1.125rem 0.75rem 1rem 0.75rem;  /* AUMENTADO: bottom padding de 0.75rem para 1rem */
    }
    
    .modal-exame .form-floating > label {
        padding: 1rem 0.75rem 0.25rem 0.75rem;  /* AUMENTADO: bottom padding de 0 para 0.25rem */
        margin-bottom: 0.25rem;
    }
    
    .modal-exame h6 {
        font-size: 1rem;
        margin-bottom: 1rem;
    }
    
    .modal-exame .alert-info {
        padding: 0.75rem;
        font-size: 0.9rem;
    }
    
    .modal-exame .btn {
        padding: 0.5rem 1rem;
        font-size: 0.9rem;
    }
}

@media (max-width: 480px) {
    .exames-container {
        padding: 5px;
    }
    
    .exames-header {
        padding: 10px;
    }
    
    .exames-header h1 {
        font-size: 1.5rem;
    }
    
    .status-cards {
        gap: 10px;
    }
    
    .status-card {
        padding: 15px;
    }
    
    .status-number {
        font-size: 1.8rem;
    }
    
    .exam-details {
        grid-template-columns: 1fr;
        gap: 8px;
    }
    
    .exam-actions {
        flex-direction: column;
    }
    
    .btn-exame {
        width: 100%;
    }
    
    /* Badges ainda menores em telas muito pequenas */
    .exam-detail-value .badge-status {
        font-size: 0.7rem;
        padding: 3px 6px;
        border-radius: 10px;
    }
    
    .exam-actions .badge {
        font-size: 0.75rem;
        padding: 5px 10px;
        border-radius: 6px;
    }
    
    /* Modal para telas muito pequenas */
    .modal-exame .modal-dialog {
        margin: 5px;
        max-width: calc(100% - 10px);
    }
    
    .modal-exame .modal-body {
        padding: 1rem;  /* PADDING TELAS PEQUENAS: mínimo mas consistente */
    }
    
    .modal-exame .modal-header {
        padding: 1rem;  /* PADDING TELAS PEQUENAS: mínimo mas consistente */
    }
    
    .modal-exame .modal-footer {
        padding: 1rem;  /* PADDING TELAS PEQUENAS: mínimo mas consistente */
        flex-direction: column;
        gap: 10px;
    }
    
    .modal-exame .btn {
        width: 100%;
        margin: 0;
    }
    
    .modal-exame .row.g-3 {
        --bs-gutter-x: 0.5rem;
    }
    
    .modal-exame .form-floating {
        margin-bottom: 0.5rem;  /* REDUZIDO: de 0.75rem para 0.5rem */
    }
    
    .modal-exame .form-floating > .form-control,
    .modal-exame .form-floating > .form-select {
        height: calc(3.5rem + 2px);  /* AUMENTADO: de 3rem para 3.5rem para evitar clipping */
        padding: 1rem 0.75rem 0.625rem 0.75rem;  /* AUMENTADO: top padding de 0.875rem para 1rem */
        font-size: 0.9rem;
    }
    
    .modal-exame .form-floating > label {
        padding: 0.875rem 0.75rem 0.25rem 0.75rem;  /* RESPIRA: bottom padding para separar do valor */
        font-size: 0.9rem;
    }
    
    .modal-exame .alert-info {
        padding: 0.5rem;
        font-size: 0.85rem;
        text-align: center;
    }
    
    .modal-exame .alert-info i {
        display: none; /* Esconder ícone em telas muito pequenas */
    }
}

/* Desktop - Mostrar tabela normal */
@media (min-width: 769px) {
    .mobile-exam-cards {
        display: none;
    }
    
    .table {
        display: table;
    }
    
    /* Modal otimizado para desktop - sem scroll */
    .modal-exame .modal-dialog {
        max-height: 85vh;
        height: auto;
    }
    
    .modal-exame .modal-content {
        max-height: 85vh;
        display: flex;
        flex-direction: column;
        height: auto;
    }
    
    .modal-exame .modal-body {
        flex: 1;
        overflow-y: visible;
        padding: 30px;
    }
    
    .modal-exame .form-floating {
        margin-bottom: 1.5rem;
    }
    
    .modal-exame .row.g-4 {
        --bs-gutter-x: 1.5rem;
        --bs-gutter-y: 1.5rem;
    }
    
    .modal-exame .form-floating > .form-control,
    .modal-exame .form-floating > .form-select {
        height: calc(4.5rem + 2px);  /* AUMENTADO: de 3.75rem para 4.5rem para evitar clipping */
        padding: 1.25rem 0.75rem 1rem 0.75rem;  /* AUMENTADO: bottom padding de 0 para 1rem */
        font-size: 1rem;
    }
    
    .modal-exame .form-floating > label {
        padding: 1.25rem 0.75rem 0.5rem 0.75rem;  /* AUMENTADO: bottom padding de 0 para 0.5rem */
        font-size: 1rem;
    }
    
    .modal-exame .modal-header {
        padding: 25px 30px !important;
    }
    
    .modal-exame .modal-footer {
        padding: 20px 30px !important;
    }
    
    .modal-exame .modal-title {
        font-size: 1.3rem !important;
    }
    
    .modal-exame .alert-info {
        padding: 1rem 1.25rem;
        font-size: 0.95rem;
    }
    
    /* Layout otimizado para desktop */
    #modalAgendarExame .form-floating {
        margin-bottom: 1.5rem;
    }
}

/* Select de Resultado */
.resultado-select {
    min-width: 180px;
    width: 100%;
    max-width: 200px;
    font-size: 0.85rem;
    border: 2px solid #e9ecef;
    border-radius: 8px;
    padding: 6px 35px 6px 10px; /* Mais padding à direita para a seta */
    transition: all 0.3s ease;
    background-color: white;
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m1 6 6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 8px center;
    background-size: 16px 12px;
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
}

.resultado-select:focus {
    border-color: #023A8D;
    box-shadow: 0 0 0 0.2rem rgba(2, 58, 141, 0.25);
}

.resultado-select option[value="apto"] {
    background-color: #d4edda;
    color: #155724;
}

.resultado-select option[value="inapto"] {
    background-color: #f8d7da;
    color: #721c24;
}

.resultado-select option[value="inapto_temporario"] {
    background-color: #fff3cd;
    color: #856404;
}

.resultado-select option[value="pendente"] {
    background-color: #e2e3e5;
    color: #383d41;
}

/* Indicador de carregamento */
.resultado-select.loading {
    opacity: 0.6;
    pointer-events: none;
}

.resultado-select.loading::after {
    content: "⏳";
    margin-left: 5px;
}

/* Animações suaves apenas para hover */
.exames-container > * {
    transition: opacity 0.3s ease;
}
</style>

<div class="exames-container">
    <!-- Header -->
    <div class="exames-header">
        <h1><i class="fas fa-stethoscope me-3"></i>Exames Médicos e Psicotécnicos</h1>
        <p>Gestão completa de exames com calendário, status e validação para aulas teóricas</p>
        
    </div>

    <!-- Cards de Status -->
    <div class="status-cards">
        <div class="status-card success">
            <div class="status-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="status-number" id="total-aptos">0</div>
            <div class="status-label">Alunos Aptos</div>
        </div>
        
        <div class="status-card warning">
            <div class="status-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="status-number" id="total-pendentes">0</div>
            <div class="status-label">Aguardando Resultado</div>
        </div>
        
        <div class="status-card danger">
            <div class="status-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="status-number" id="total-inaptos">0</div>
            <div class="status-label">Inaptos</div>
        </div>
        
        <div class="status-card info">
            <div class="status-icon">
                <i class="fas fa-calendar-alt"></i>
            </div>
            <div class="status-number" id="total-agendados">0</div>
            <div class="status-label">Exames Agendados</div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="filtros-container">
        <div class="filtros-row">
            <div class="form-group">
                <label class="form-label">Filtrar por Aluno</label>
                <select class="form-control" id="filtro-aluno">
                    <option value="">Todos os alunos</option>
                    <?php foreach ($alunos as $aluno): ?>
                        <option value="<?php echo $aluno['id']; ?>">
                            <?php echo htmlspecialchars($aluno['nome'] . ' - ' . $aluno['cpf']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Filtrar por Tipo</label>
                <select class="form-control" id="filtro-tipo">
                    <option value="">Todos os tipos</option>
                    <option value="medico">Exame Médico</option>
                    <option value="psicotecnico">Exame Psicotécnico</option>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Filtrar por Status</label>
                <select class="form-control" id="filtro-status">
                    <option value="">Todos os status</option>
                    <option value="agendado">Agendado</option>
                    <option value="concluido">Concluído</option>
                    <option value="cancelado">Cancelado</option>
                </select>
            </div>
            
            <div class="form-group">
                <button type="button" class="btn btn-primary btn-lg" onclick="abrirModalAgendar()">
                    <i class="fas fa-plus me-2"></i>Agendar Exame
                </button>
            </div>
        </div>
    </div>

    <!-- Tabela de Exames -->
    <div class="exames-table-container">
        <h3 class="mb-4">
            <i class="fas fa-list me-2"></i>Lista de Exames
            <small class="text-muted">(Últimos 30 dias e próximos 30 dias)</small>
        </h3>
        
        <!-- Tabela Desktop -->
        <div class="table-responsive">
            <table class="table table-hover" id="tabela-exames">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Tipo</th>
                        <th>Data Agendada</th>
                        <th>Clínica</th>
                        <th>Status</th>
                        <th>Resultado</th>
                        <th>Data Resultado</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($exames)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4">
                                <i class="fas fa-info-circle me-2"></i>
                                Nenhum exame encontrado no período selecionado.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($exames as $exame): ?>
                            <tr data-exame-id="<?php echo $exame['id']; ?>"
                                data-aluno-id="<?php echo $exame['aluno_id']; ?>" 
                                data-tipo="<?php echo $exame['tipo']; ?>"
                                data-status="<?php echo $exame['status']; ?>">
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($exame['aluno_nome']); ?></strong>
                                        <br>
                                        <small class="text-muted"><?php echo htmlspecialchars($exame['aluno_cpf']); ?></small>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $exame['tipo'] === 'medico' ? 'primary' : 'info'; ?>">
                                        <i class="fas fa-<?php echo $exame['tipo'] === 'medico' ? 'user-md' : 'brain'; ?> me-1"></i>
                                        <?php echo $exame['tipo'] === 'medico' ? 'Médico' : 'Psicotécnico'; ?>
                                    </span>
                                </td>
                                <td>
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($exame['data_agendada'])); ?>
                                </td>
                                <td>
                                    <?php echo $exame['clinica_nome'] ? htmlspecialchars($exame['clinica_nome']) : '-'; ?>
                                </td>
                                <td>
                                    <span class="badge-status badge-<?php echo $exame['status']; ?>">
                                        <?php echo ucfirst($exame['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm resultado-select" 
                                            data-exame-id="<?php echo $exame['id']; ?>"
                                            data-current-status="<?php echo $exame['status']; ?>"
                                            onchange="alterarResultado(this)">
                                        <option value="pendente" <?php echo ($exame['resultado'] === 'pendente' || !$exame['resultado']) ? 'selected' : ''; ?>>
                                            ⏳ Aguardando
                                        </option>
                                        <option value="apto" <?php echo $exame['resultado'] === 'apto' ? 'selected' : ''; ?>>
                                            ✅ Apto
                                        </option>
                                        <option value="inapto" <?php echo $exame['resultado'] === 'inapto' ? 'selected' : ''; ?>>
                                            ❌ Inapto
                                        </option>
                                        <option value="inapto_temporario" <?php echo $exame['resultado'] === 'inapto_temporario' ? 'selected' : ''; ?>>
                                            ⚠️ Inapto Temp.
                                        </option>
                                    </select>
                                </td>
                                <td>
                                    <?php echo $exame['data_resultado'] ? date('d/m/Y', strtotime($exame['data_resultado'])) : '-'; ?>
                                </td>
                                <td>
                                    <div class="d-flex flex-wrap gap-2">
                                        <button class="btn-exame btn-primary" 
                                                onclick="editarExame(<?php echo $exame['id']; ?>)"
                                                title="Editar Exame">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <?php if ($exame['status'] !== 'cancelado'): ?>
                                            <button class="btn-exame btn-warning" 
                                                    onclick="cancelarExame(<?php echo $exame['id']; ?>)"
                                                    title="Cancelar Exame">
                                                <i class="fas fa-ban"></i>
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($isAdmin): ?>
                                            <button class="btn-exame btn-danger" 
                                                    onclick="excluirExame(<?php echo $exame['id']; ?>)"
                                                    title="Excluir Definitivamente">
                                                <i class="fas fa-trash"></i>
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

        <!-- Cards Mobile -->
        <div class="mobile-exam-cards">
            <?php if (empty($exames)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-info-circle me-2"></i>
                    Nenhum exame encontrado no período selecionado.
                </div>
            <?php else: ?>
                <?php foreach ($exames as $exame): ?>
                    <div class="exam-card" data-exame-id="<?php echo $exame['id']; ?>"
                         data-aluno-id="<?php echo $exame['aluno_id']; ?>" 
                         data-tipo="<?php echo $exame['tipo']; ?>"
                         data-status="<?php echo $exame['status']; ?>">
                        
                        <div class="exam-card-header">
                            <div class="exam-student-info">
                                <h4><?php echo htmlspecialchars($exame['aluno_nome']); ?></h4>
                                <small><?php echo htmlspecialchars($exame['aluno_cpf']); ?></small>
                            </div>
                            <div class="exam-type-badge exam-type-<?php echo $exame['tipo']; ?>">
                                <i class="fas fa-<?php echo $exame['tipo'] === 'medico' ? 'user-md' : 'brain'; ?> me-1"></i>
                                <?php echo $exame['tipo'] === 'medico' ? 'Médico' : 'Psicotécnico'; ?>
                            </div>
                        </div>

                        <div class="exam-details">
                            <div class="exam-detail-item">
                                <div class="exam-detail-label">Data Agendada</div>
                                <div class="exam-detail-value">
                                    <i class="fas fa-calendar me-1"></i>
                                    <?php echo date('d/m/Y', strtotime($exame['data_agendada'])); ?>
                                </div>
                            </div>
                            
                            <div class="exam-detail-item">
                                <div class="exam-detail-label">Clínica</div>
                                <div class="exam-detail-value">
                                    <?php echo $exame['clinica_nome'] ? htmlspecialchars($exame['clinica_nome']) : '-'; ?>
                                </div>
                            </div>
                            
                            <div class="exam-detail-item exam-status-item">
                                <div class="exam-detail-label">Status</div>
                                <div class="exam-detail-value">
                                    <span class="badge-status badge-<?php echo $exame['status']; ?>">
                                        <?php echo ucfirst($exame['status']); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <div class="exam-detail-item exam-result-date-item">
                                <div class="exam-detail-label">Data Resultado</div>
                                <div class="exam-detail-value">
                                    <?php echo $exame['data_resultado'] ? date('d/m/Y', strtotime($exame['data_resultado'])) : '-'; ?>
                                </div>
                            </div>
                        </div>

                        <div class="exam-detail-item" style="margin-bottom: 15px;">
                            <div class="exam-detail-label">Resultado</div>
                            <select class="form-select form-select-sm resultado-select" 
                                    data-exame-id="<?php echo $exame['id']; ?>"
                                    data-current-status="<?php echo $exame['status']; ?>"
                                    onchange="alterarResultado(this)">
                                <option value="pendente" <?php echo ($exame['resultado'] === 'pendente' || !$exame['resultado']) ? 'selected' : ''; ?>>
                                    ⏳ Aguardando
                                </option>
                                <option value="apto" <?php echo $exame['resultado'] === 'apto' ? 'selected' : ''; ?>>
                                    ✅ Apto
                                </option>
                                <option value="inapto" <?php echo $exame['resultado'] === 'inapto' ? 'selected' : ''; ?>>
                                    ❌ Inapto
                                </option>
                                <option value="inapto_temporario" <?php echo $exame['resultado'] === 'inapto_temporario' ? 'selected' : ''; ?>>
                                    ⚠️ Inapto Temp.
                                </option>
                            </select>
                        </div>

                        <div class="exam-actions">
                            <button class="btn-exame btn-primary" 
                                    onclick="editarExame(<?php echo $exame['id']; ?>)"
                                    title="Editar Exame">
                                <i class="fas fa-edit me-1"></i>Editar
                            </button>
                            <?php if ($exame['status'] !== 'cancelado'): ?>
                                <button class="btn-exame btn-warning" 
                                        onclick="cancelarExame(<?php echo $exame['id']; ?>)"
                                        title="Cancelar Exame">
                                    <i class="fas fa-ban me-1"></i>Cancelar
                                </button>
                            <?php endif; ?>
                            <?php if ($isAdmin): ?>
                                <button class="btn-exame btn-danger" 
                                        onclick="excluirExame(<?php echo $exame['id']; ?>)"
                                        title="Excluir Definitivamente">
                                    <i class="fas fa-trash me-1"></i>Excluir
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal Agendar Exame -->
<div class="modal fade modal-exame" id="modalAgendarExame" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-plus me-2"></i>Agendar Novo Exame
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAgendarExame">
                    <!-- Layout responsivo com CSS Grid -->
                    <div class="form-grid">
                        <!-- Primeira linha - 3 campos -->
                        <div class="form-floating">
                            <select class="form-select" name="aluno_id" id="aluno_id" required>
                                <option value="">Selecione um aluno</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome'] . ' - ' . $aluno['cpf']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <label for="aluno_id">
                                <i class="fas fa-user me-1"></i>Aluno *
                            </label>
                        </div>
                        
                        <div class="form-floating">
                            <select class="form-select" name="tipo" id="tipo_exame" required>
                                <option value="">Selecione o tipo</option>
                                <option value="medico">Exame Médico</option>
                                <option value="psicotecnico">Exame Psicotécnico</option>
                            </select>
                            <label for="tipo_exame">
                                <i class="fas fa-clipboard-list me-1"></i>Tipo de Exame *
                            </label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="date" class="form-control" name="data_agendada" id="data_agendada" required>
                            <label for="data_agendada">
                                <i class="fas fa-calendar me-1"></i>Data do Exame *
                            </label>
                        </div>
                        
                        <!-- Segunda linha - 2 campos -->
                        <div class="form-floating">
                            <input type="text" class="form-control" name="clinica_nome" id="clinica_nome" placeholder="Nome da clínica">
                            <label for="clinica_nome">
                                <i class="fas fa-hospital me-1"></i>Clínica
                            </label>
                        </div>
                        
                        <div class="form-floating">
                            <input type="text" class="form-control" name="protocolo" id="protocolo" placeholder="Número do protocolo">
                            <label for="protocolo">
                                <i class="fas fa-hashtag me-1"></i>Protocolo
                            </label>
                        </div>
                        
                        <!-- Terceira linha - 1 campo (observações) -->
                        <div class="form-floating field-full">
                            <textarea class="form-control" name="observacoes" id="observacoes" placeholder="Observações adicionais" style="height: 80px; overflow-y: hidden; padding-top: 1.5rem;"></textarea>
                            <label for="observacoes">
                                <i class="fas fa-sticky-note me-1"></i>Observações
                            </label>
                        </div>
                    </div>
                    
                    <!-- Dica informativa -->
                    <div class="mt-2">  <!-- REDUZIDO: de mt-3 para mt-2 -->
                        <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                            <i class="fas fa-lightbulb me-2"></i>
                            <div>
                                <strong>Dica:</strong> Todos os campos marcados com * são obrigatórios. 
                                Você pode agendar exames médico e psicotécnico para o mesmo aluno em datas diferentes.
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="agendarExame()">
                    <i class="fas fa-calendar-check me-2"></i>Agendar Exame
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Lançar Resultado -->
<div class="modal fade modal-exame" id="modalResultadoExame" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-clipboard-check me-2"></i>Lançar Resultado do Exame
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formResultadoExame">
                    <input type="hidden" name="exame_id" id="exame_id_resultado">
                    
                    <div class="form-group">
                        <label class="form-label">Resultado *</label>
                        <select class="form-control" name="resultado" required>
                            <option value="">Selecione o resultado</option>
                            <option value="apto">Apto</option>
                            <option value="inapto">Inapto</option>
                            <option value="inapto_temporario">Inapto Temporário</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Data do Resultado</label>
                        <input type="date" class="form-control" name="data_resultado" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3" placeholder="Observações sobre o resultado"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>Cancelar
                </button>
                <button type="button" class="btn btn-success" onclick="salvarResultado()">
                    <i class="fas fa-save me-2"></i>Salvar Resultado
                </button>
            </div>
        </div>
    </div>
</div>

<script>
// =====================================================
// JAVASCRIPT PARA SISTEMA DE EXAMES
// =====================================================

class SistemaExames {
    constructor() {
        this.init();
    }
    
    init() {
        this.carregarEstatisticas();
        this.configurarFiltros();
        this.configurarDataPicker();
    }
    
    carregarEstatisticas() {
        const exames = <?php echo json_encode($exames); ?>;
        
        let totalAptos = 0;
        let totalPendentes = 0;
        let totalInaptos = 0;
        let totalAgendados = 0;
        
        // Contar exames individuais baseado no status e resultado
        exames.forEach(exame => {
            // Contar exames agendados
            if (exame.status === 'agendado') {
                totalAgendados++;
                
                // Contar exames pendentes (agendados sem resultado ou com resultado pendente)
                if (!exame.resultado || exame.resultado === 'pendente') {
                    totalPendentes++;
                }
            }
            
            // Contar exames concluídos
            if (exame.status === 'concluido') {
                if (exame.resultado === 'apto') {
                    totalAptos++;
                } else if (exame.resultado === 'inapto' || exame.resultado === 'inapto_temporario') {
                    totalInaptos++;
                }
            }
        });
        
        // Atualizar cards
        document.getElementById('total-aptos').textContent = totalAptos;
        document.getElementById('total-pendentes').textContent = totalPendentes;
        document.getElementById('total-inaptos').textContent = totalInaptos;
        document.getElementById('total-agendados').textContent = totalAgendados;
    }
    
    configurarFiltros() {
        const filtroAluno = document.getElementById('filtro-aluno');
        const filtroTipo = document.getElementById('filtro-tipo');
        const filtroStatus = document.getElementById('filtro-status');
        
        [filtroAluno, filtroTipo, filtroStatus].forEach(filtro => {
            filtro.addEventListener('change', this.aplicarFiltros.bind(this));
        });
    }
    
    aplicarFiltros() {
        const filtroAluno = document.getElementById('filtro-aluno').value;
        const filtroTipo = document.getElementById('filtro-tipo').value;
        const filtroStatus = document.getElementById('filtro-status').value;
        
        const linhas = document.querySelectorAll('#tabela-exames tbody tr');
        
        linhas.forEach(linha => {
            if (linha.querySelector('td')) { // Ignorar linha de "nenhum exame encontrado"
                const alunoId = linha.getAttribute('data-aluno-id');
                const tipo = linha.getAttribute('data-tipo');
                const status = linha.getAttribute('data-status');
                
                let mostrar = true;
                
                if (filtroAluno && alunoId !== filtroAluno) mostrar = false;
                if (filtroTipo && tipo !== filtroTipo) mostrar = false;
                if (filtroStatus && status !== filtroStatus) mostrar = false;
                
                linha.style.display = mostrar ? '' : 'none';
            }
        });
    }
    
    configurarDataPicker() {
        // Definir data mínima como hoje
        const inputData = document.querySelector('input[name="data_agendada"]');
        if (inputData) {
            inputData.min = new Date().toISOString().split('T')[0];
        }
    }
}

// Funções globais
function abrirModalAgendar() {
    console.log('🔍 DEBUG: Iniciando abertura do modal');
    
    const modal = new bootstrap.Modal(document.getElementById('modalAgendarExame'));
    modal.show();
    
    // Limpar formulário
    document.getElementById('formAgendarExame').reset();
    
    // Restaurar título original
    document.querySelector('#modalAgendarExame .modal-title').innerHTML = 
        '<i class="fas fa-calendar-plus me-2"></i>Agendar Novo Exame';
    
    // Restaurar botão original
    const btnAgendar = document.querySelector('#modalAgendarExame .btn-primary');
    btnAgendar.innerHTML = '<i class="fas fa-calendar-check me-2"></i>Agendar Exame';
    btnAgendar.onclick = agendarExame;
    
    // Remover campo hidden de edição se existir
    const hiddenField = document.getElementById('exame_id_edicao');
    if (hiddenField) {
        hiddenField.remove();
    }
    
    // CORREÇÃO RESPONSIVA: Ajustar largura baseada no viewport
    setTimeout(() => {
        const modalElement = document.getElementById('modalAgendarExame');
        const modalDialog = modalElement.querySelector('.modal-dialog');
        
        if (modalElement && modalDialog) {
            console.log('🔍 DEBUG: Aplicando correção responsiva');
            
            // Calcular largura responsiva COM GUTTER
            const viewportWidth = window.innerWidth;
            const gutter = viewportWidth >= 992 ? 128 : 32; // 4rem desktop, 1rem mobile
            const maxWidth = Math.min(1400, viewportWidth - gutter);
            
            // Aplicar largura responsiva
            modalDialog.style.width = maxWidth + 'px';
            modalDialog.style.maxWidth = maxWidth + 'px';
            modalDialog.style.marginLeft = 'auto';
            modalDialog.style.marginRight = 'auto';
            
            console.log('🔍 DEBUG: Largura responsiva aplicada:', maxWidth + 'px');
        }
    }, 100);
}

function abrirModalResultado(exameId) {
    const modal = new bootstrap.Modal(document.getElementById('modalResultadoExame'));
    modal.show();
    
    // Definir ID do exame
    document.getElementById('exame_id_resultado').value = exameId;
    
    // Limpar formulário
    document.getElementById('formResultadoExame').reset();
    document.getElementById('exame_id_resultado').value = exameId;
    document.querySelector('input[name="data_resultado"]').value = new Date().toISOString().split('T')[0];
}

// Configurar modal responsivo - SIMPLES COM DEBUG
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('modalAgendarExame');
    if (modal) {
        modal.addEventListener('shown.bs.modal', function() {
            const modalDialog = modal.querySelector('.modal-dialog');
            const modalBody = modal.querySelector('.modal-body');
            
            if (modalDialog && modalBody) {
                // CORREÇÃO RESPONSIVA: Ajustar largura baseada no viewport COM GUTTER
                const viewportWidth = window.innerWidth;
                if (viewportWidth >= 992) {
                    // Desktop: usar largura responsiva COM GUTTER
                    const gutter = 128; // 4rem = 64px * 2 (lados)
                    const maxWidth = Math.min(1400, viewportWidth - gutter);
                    modalDialog.style.width = maxWidth + 'px';
                    modalDialog.style.maxWidth = maxWidth + 'px';
                    modalDialog.style.marginLeft = 'auto';
                    modalDialog.style.marginRight = 'auto';
                    modalBody.style.overflow = 'visible';
                    modalBody.style.maxHeight = 'none';
                } else {
                    // Mobile: usar viewport width COM GUTTER
                    const gutter = 32; // 1rem = 16px * 2 (lados)
                    const maxWidth = viewportWidth - gutter;
                    modalDialog.style.width = maxWidth + 'px';
                    modalDialog.style.maxWidth = maxWidth + 'px';
                    modalBody.style.overflowY = 'auto';
                    modalBody.style.maxHeight = 'calc(90vh - 120px)';
                }
            }
            
            // CORREÇÃO LABELS FLUTUANTES: Controle dinâmico dos estados
            const formControls = modal.querySelectorAll('.form-floating > .form-control, .form-floating > .form-select, .form-floating > input[type="date"]');
            
            // FUNÇÃO RADICAL: Forçar transform em todos os labels
            function forceAllLabels() {
                const allLabels = modal.querySelectorAll('.form-floating label');
                allLabels.forEach(function(label) {
                    label.style.setProperty('transform', 'scale(0.85) translateY(-1.5rem) translateX(0.15rem)', 'important');
                    label.style.setProperty('opacity', '0.65', 'important');
                });
            }
            
            // FUNÇÃO: Forçar altura correta dos inputs para evitar clipping
            function fixInputHeights() {
                const inputs = modal.querySelectorAll('.form-floating > .form-control, .form-floating > .form-select, .form-floating > input[type="date"]');
                inputs.forEach(function(input) {
                    // Forçar altura mínima para evitar clipping
                    if (window.innerWidth >= 992) {
                        input.style.setProperty('height', 'calc(5rem + 2px)', 'important');
                    } else if (window.innerWidth >= 768) {
                        input.style.setProperty('height', 'calc(4.5rem + 2px)', 'important');
                    } else {
                        input.style.setProperty('height', 'calc(4rem + 2px)', 'important');
                    }
                });
            }
            
            formControls.forEach(function(control) {
                // Função para atualizar estado
                function updateFloatingLabel() {
                    const hasValue = control.value && control.value.trim() !== '';
                    
                    if (hasValue) {
                        control.classList.add('has-value');
                        // Forçar aplicação do transform via style inline
                        const label = control.nextElementSibling;
                        if (label) {
                            label.style.setProperty('transform', 'scale(0.85) translateY(-1.5rem) translateX(0.15rem)', 'important');
                            label.style.setProperty('opacity', '0.65', 'important');
                        }
                    } else {
                        control.classList.remove('has-value');
                        const label = control.nextElementSibling;
                        if (label) {
                            label.style.setProperty('transform', 'scale(0.85) translateY(-1.5rem) translateX(0.15rem)', 'important');
                            label.style.setProperty('opacity', '0.65', 'important');
                        }
                    }
                }
                
                // Eventos para atualizar estado
                control.addEventListener('input', function() {
                    updateFloatingLabel();
                    forceAllLabels(); // Forçar em todos
                });
                control.addEventListener('change', function() {
                    updateFloatingLabel();
                    forceAllLabels(); // Forçar em todos
                });
                control.addEventListener('focus', function() {
                    control.classList.add('is-focused');
                    const label = control.nextElementSibling;
                    if (label) {
                        label.style.setProperty('transform', 'scale(0.85) translateY(-1.5rem) translateX(0.15rem)', 'important');
                        label.style.setProperty('opacity', '0.65', 'important');
                        label.style.setProperty('color', '#023A8D', 'important');
                    }
                    forceAllLabels(); // Forçar em todos
                });
                control.addEventListener('blur', function() {
                    control.classList.remove('is-focused');
                    const label = control.nextElementSibling;
                    if (label) {
                        label.style.setProperty('color', '', 'important');
                        updateFloatingLabel();
                    }
                    forceAllLabels(); // Forçar em todos
                });
                
                // Estado inicial
                updateFloatingLabel();
            });
            
            // FORÇAR TODOS OS LABELS E ALTURAS IMEDIATAMENTE
            setTimeout(function() {
                forceAllLabels();
                fixInputHeights();
            }, 100);
        });
        
    }
});


function agendarExame() {
    const form = document.getElementById('formAgendarExame');
    const formData = new FormData(form);
    
    // Validações
    if (!formData.get('aluno_id')) {
        alert('Selecione um aluno');
        return;
    }
    
    if (!formData.get('tipo')) {
        alert('Selecione o tipo de exame');
        return;
    }
    
    if (!formData.get('data_agendada')) {
        alert('Selecione a data do exame');
        return;
    }
    
    // Enviar dados
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Response status:', response.status);
        console.log('Response headers:', response.headers);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Parsed data:', data);
        if (data.success) {
            alert('✅ Exame agendado com sucesso!');
            location.reload();
        } else {
            // Mostrar mensagem amigável em vez de erro técnico
            const message = data.friendly_message || data.message || data.error || 'Erro desconhecido';
            alert(message);
        }
    })
    .catch(error => {
        console.error('Erro completo:', error);
        alert('❌ Erro ao agendar exame: ' + error.message);
    });
}

function salvarResultado() {
    const form = document.getElementById('formResultadoExame');
    const formData = new FormData(form);
    
    // Validações
    if (!formData.get('resultado')) {
        alert('Selecione o resultado do exame');
        return;
    }
    
    const exameId = formData.get('exame_id');
    
    // Adicionar ação ao FormData
    formData.append('action', 'update');
    formData.append('exame_id', exameId);
    
    // Enviar dados
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('Resultado salvo com sucesso!');
            location.reload();
        } else {
            alert('Erro ao salvar resultado: ' + (data.error || data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar resultado. Tente novamente.');
    });
}

function cancelarExame(exameId) {
    if (!confirm('Tem certeza que deseja cancelar este exame?')) {
        return;
    }
    
    // Criar FormData para DELETE
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('exame_id', exameId);
    
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Cancel response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Cancel response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Cancel parsed data:', data);
        if (data.success) {
            alert('Exame cancelado com sucesso!');
            location.reload();
        } else {
            alert('Erro ao cancelar exame: ' + (data.error || data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro completo no cancelamento:', error);
        alert('Erro ao cancelar exame: ' + error.message);
    });
}

function alterarResultado(selectElement) {
    const exameId = selectElement.getAttribute('data-exame-id');
    const currentStatus = selectElement.getAttribute('data-current-status');
    const novoResultado = selectElement.value;
    
    // Se o exame já está cancelado, não permitir alteração
    if (currentStatus === 'cancelado') {
        alert('Não é possível alterar o resultado de um exame cancelado.');
        // Reverter para o valor anterior
        location.reload();
        return;
    }
    
    // Adicionar indicador de carregamento
    selectElement.classList.add('loading');
    
    // Criar FormData para UPDATE
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('exame_id', exameId);
    formData.append('resultado', novoResultado);
    
    // Se o resultado não for "pendente", definir data do resultado
    if (novoResultado !== 'pendente') {
        formData.append('data_resultado', new Date().toISOString().split('T')[0]);
    }
    
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Update response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Update response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Update parsed data:', data);
        if (data.success) {
            // Atualizar o status na interface se necessário
            atualizarStatusNaInterface(exameId, novoResultado);
            
            // Mostrar mensagem de sucesso
            const textosResultado = {
                'pendente': 'Aguardando',
                'apto': 'Apto',
                'inapto': 'Inapto',
                'inapto_temporario': 'Inapto Temporário'
            };
            
            const mensagem = `Resultado alterado para "${textosResultado[novoResultado] || novoResultado}"`;
            
            // Usar toast ou notificação mais elegante
            mostrarNotificacao(mensagem, 'success');
            
        } else {
            alert('Erro ao alterar resultado: ' + (data.error || data.mensagem || 'Erro desconhecido'));
            // Reverter para o valor anterior
            location.reload();
        }
    })
    .catch(error => {
        console.error('Erro completo na alteração:', error);
        alert('Erro ao alterar resultado: ' + error.message);
        // Reverter para o valor anterior
        location.reload();
    })
    .finally(() => {
        // Remover indicador de carregamento
        selectElement.classList.remove('loading');
    });
}

function atualizarStatusNaInterface(exameId, resultado) {
    // Encontrar tanto a linha da tabela quanto o card mobile
    const linha = document.querySelector(`tr[data-exame-id="${exameId}"]`);
    const card = document.querySelector(`.exam-card[data-exame-id="${exameId}"]`);
    
    // Atualizar o status baseado no resultado
    let novoStatus = 'agendado';
    let novaClasse = 'badge-agendado';
    
    if (resultado === 'apto' || resultado === 'inapto') {
        novoStatus = 'concluido';
        novaClasse = 'badge-concluido';
    } else if (resultado === 'inapto_temporario') {
        novoStatus = 'pendente';
        novaClasse = 'badge-pendente';
    } else if (resultado === 'pendente') {
        novoStatus = 'agendado';
        novaClasse = 'badge-agendado';
    }
    
    // Atualizar na tabela (desktop)
    if (linha) {
        const statusCell = linha.querySelector('.badge-status');
        if (statusCell) {
            statusCell.textContent = novoStatus.charAt(0).toUpperCase() + novoStatus.slice(1);
            statusCell.className = `badge-status ${novaClasse}`;
        }
        
        // Atualizar a data do resultado se não for pendente
        if (resultado !== 'pendente') {
            const dataResultadoCell = linha.querySelector('td:nth-child(7)'); // Coluna da data do resultado
            if (dataResultadoCell) {
                dataResultadoCell.textContent = new Date().toLocaleDateString('pt-BR');
            }
        } else {
            const dataResultadoCell = linha.querySelector('td:nth-child(7)'); // Coluna da data do resultado
            if (dataResultadoCell) {
                dataResultadoCell.textContent = '-';
            }
        }
    }
    
    // Atualizar no card (mobile)
    if (card) {
        const statusCell = card.querySelector('.badge-status');
        if (statusCell) {
            statusCell.textContent = novoStatus.charAt(0).toUpperCase() + novoStatus.slice(1);
            statusCell.className = `badge-status ${novaClasse}`;
        }
        
        // Atualizar a data do resultado se não for pendente
        if (resultado !== 'pendente') {
            const dataResultadoValue = card.querySelector('.exam-result-date-item .exam-detail-value');
            if (dataResultadoValue) {
                dataResultadoValue.textContent = new Date().toLocaleDateString('pt-BR');
            }
        } else {
            const dataResultadoValue = card.querySelector('.exam-result-date-item .exam-detail-value');
            if (dataResultadoValue) {
                dataResultadoValue.textContent = '-';
            }
        }
    }
}

function mostrarNotificacao(mensagem, tipo = 'info') {
    // Criar elemento de notificação
    const notificacao = document.createElement('div');
    notificacao.className = `alert alert-${tipo === 'success' ? 'success' : 'info'} alert-dismissible fade show position-fixed`;
    notificacao.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notificacao.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Adicionar ao body
    document.body.appendChild(notificacao);
    
    // Remover automaticamente após 3 segundos
    setTimeout(() => {
        if (notificacao.parentNode) {
            notificacao.remove();
        }
    }, 3000);
}

// Funções para editar e excluir exames
function editarExame(exameId) {
    // Buscar dados do exame
    fetch(`api/exames_simple.php?id=${exameId}&t=${Date.now()}`, {
        cache: 'no-cache'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.exame) {
            const exame = data.exame;
            
            // Abrir modal de agendamento
            const modal = new bootstrap.Modal(document.getElementById('modalAgendarExame'));
            modal.show();
            
            // Preencher formulário com dados do exame
            document.getElementById('aluno_id').value = exame.aluno_id;
            document.getElementById('tipo_exame').value = exame.tipo;
            document.getElementById('data_agendada').value = exame.data_agendada;
            document.getElementById('clinica_nome').value = exame.clinica_nome || '';
            document.getElementById('protocolo').value = exame.protocolo || '';
            document.getElementById('observacoes').value = exame.observacoes || '';
            
            // Alterar título do modal
            document.querySelector('#modalAgendarExame .modal-title').innerHTML = 
                '<i class="fas fa-edit me-2"></i>Editar Exame';
            
            // Alterar botão de ação
            const btnAgendar = document.querySelector('#modalAgendarExame .btn-primary');
            btnAgendar.innerHTML = '<i class="fas fa-save me-2"></i>Salvar Alterações';
            btnAgendar.onclick = () => salvarEdicaoExame(exameId);
            
            // Adicionar campo hidden para indicar que é edição
            let hiddenField = document.getElementById('exame_id_edicao');
            if (!hiddenField) {
                hiddenField = document.createElement('input');
                hiddenField.type = 'hidden';
                hiddenField.id = 'exame_id_edicao';
                hiddenField.name = 'exame_id';
                document.getElementById('formAgendarExame').appendChild(hiddenField);
            }
            hiddenField.value = exameId;
            
            // Forçar atualização dos labels flutuantes
            setTimeout(() => {
                const formControls = document.querySelectorAll('#modalAgendarExame .form-floating > .form-control, #modalAgendarExame .form-floating > .form-select, #modalAgendarExame .form-floating > input[type="date"]');
                formControls.forEach(control => {
                    if (control.value) {
                        control.classList.add('has-value');
                        const label = control.nextElementSibling;
                        if (label) {
                            label.style.setProperty('transform', 'scale(0.85) translateY(-1.5rem) translateX(0.15rem)', 'important');
                            label.style.setProperty('opacity', '0.65', 'important');
                        }
                    }
                });
            }, 100);
            
        } else {
            alert('Erro ao carregar dados do exame: ' + (data.error || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao carregar exame:', error);
        alert('Erro ao carregar dados do exame. Tente novamente.');
    });
}

function salvarEdicaoExame(exameId) {
    const form = document.getElementById('formAgendarExame');
    const formData = new FormData(form);
    
    // Validações
    if (!formData.get('aluno_id')) {
        alert('Selecione um aluno');
        return;
    }
    
    if (!formData.get('tipo')) {
        alert('Selecione o tipo de exame');
        return;
    }
    
    if (!formData.get('data_agendada')) {
        alert('Selecione a data do exame');
        return;
    }
    
    // Adicionar ação de update
    formData.append('action', 'update');
    formData.append('exame_id', exameId);
    
    // Enviar dados
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Edit response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Edit response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Edit parsed data:', data);
        if (data.success) {
            alert('✅ Exame atualizado com sucesso!');
            location.reload();
        } else {
            const message = data.friendly_message || data.message || data.error || 'Erro desconhecido';
            alert(message);
        }
    })
    .catch(error => {
        console.error('Erro completo na edição:', error);
        alert('❌ Erro ao atualizar exame: ' + error.message);
    });
}

function cancelarExame(exameId) {
    if (!confirm('Tem certeza que deseja cancelar este exame? O exame ficará marcado como cancelado e será possível agendar um novo exame para o aluno.')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'cancel');
    formData.append('exame_id', exameId);
    
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Cancel response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Cancel response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Cancel parsed data:', data);
        if (data.success) {
            alert('Exame cancelado com sucesso!');
            location.reload();
        } else {
            alert('Erro ao cancelar exame: ' + (data.error || data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro completo no cancelamento:', error);
        alert('Erro ao cancelar exame: ' + error.message);
    });
}

function excluirExame(exameId) {
    if (!confirm('⚠️ ATENÇÃO: Esta ação irá EXCLUIR DEFINITIVAMENTE o exame do sistema. Esta ação não pode ser desfeita e todos os dados relacionados serão perdidos.\n\nTem certeza que deseja continuar?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('exame_id', exameId);
    
    fetch('api/exames_simple.php?t=' + Date.now(), {
        method: 'POST',
        body: formData,
        cache: 'no-cache'
    })
    .then(response => {
        console.log('Delete response status:', response.status);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }
        
        return response.text().then(text => {
            console.log('Delete response text:', text);
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('JSON parse error:', e);
                console.error('Response text was:', text);
                throw new Error('Invalid JSON response');
            }
        });
    })
    .then(data => {
        console.log('Delete parsed data:', data);
        if (data.success) {
            alert('Exame excluído definitivamente!');
            location.reload();
        } else {
            alert('Erro ao excluir exame: ' + (data.error || data.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro completo na exclusão:', error);
        alert('Erro ao excluir exame: ' + error.message);
    });
}

// Inicializar sistema quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    new SistemaExames();
});
</script>
