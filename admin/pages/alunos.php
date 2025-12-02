<?php
// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    // Se acessado diretamente, redirecionar para o sistema de roteamento
    header('Location: ../index.php?page=alunos');
    exit;
}

// Verificar se as variáveis estão definidas
if (!isset($alunos)) $alunos = [];
if (!isset($cfcs)) $cfcs = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';

// Ler filtro de status atual
$statusFiltroAtual = isset($_GET['status']) ? trim($_GET['status']) : null;
$statusPermitidos = ['em_formacao', 'em_exame', 'concluido'];
if (!in_array($statusFiltroAtual, $statusPermitidos)) {
    $statusFiltroAtual = null;
}

// Estatísticas globais (se não vierem do index.php, calcular aqui)
if (!isset($stats['total_alunos'])) {
    try {
        require_once __DIR__ . '/../includes/database.php';
        $db = Database::getInstance();
        $resultTotal = $db->fetch("SELECT COUNT(*) as total FROM alunos");
        $stats['total_alunos'] = $resultTotal['total'] ?? count($alunos);
        
        $resultAtivos = $db->fetch("SELECT COUNT(*) as total FROM alunos WHERE status = 'ativo'");
        $stats['total_ativos'] = $resultAtivos['total'] ?? 0;
        
        $resultConcluidos = $db->fetch("SELECT COUNT(*) as total FROM alunos WHERE status = 'concluido'");
        $stats['total_concluidos'] = $resultConcluidos['total'] ?? 0;
        
        $resultEmExame = $db->fetch("
            SELECT COUNT(DISTINCT aluno_id) as total
            FROM exames
            WHERE status = 'agendado'
        ");
        $stats['total_em_exame'] = $resultEmExame['total'] ?? 0;
    } catch (Exception $e) {
        $stats['total_alunos'] = count($alunos);
        $stats['total_ativos'] = 0;
        $stats['total_concluidos'] = 0;
        $stats['total_em_exame'] = 0;
    }
}

// Headers para evitar cache em produção - removidos pois já há saída HTML
// header("Cache-Control: no-cache, no-store, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: 0");

// Debug: Verificar se os dados estão sendo carregados
error_log("DEBUG ALUNOS: Total de alunos carregados: " . count($alunos));
error_log("DEBUG ALUNOS: Primeiro aluno: " . json_encode($alunos[0] ?? 'nenhum'));

// Funções helper movidas para admin/includes/helpers_cnh.php
// Incluir o helper comum
require_once __DIR__ . '/../includes/helpers_cnh.php';
?>

<style>
/* =====================================================
   ESTILOS PARA OTIMIZAÇÃO DE ESPAÇO DESKTOP
   ===================================================== */

/* =====================================================
   Z-INDEX E STACKING CONTEXT DOS MODAIS
   ===================================================== */

/* Garantir que backdrop fique atrás do modal */
.modal-backdrop {
    z-index: 1050 !important;
}

/* Modal de visualização personalizado */
/* CSS ANTIGO DO MODAL DE VISUALIZAÇÃO - REMOVIDO
   Agora usa o mesmo padrão do #modalAluno (.custom-modal, .custom-modal-dialog, etc.)
   Regras específicas estão em admin/assets/css/modal-form.css
*/
/*
#modalVisualizarAluno {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(15, 23, 42, 0.65);
    z-index: 100000;
    padding: 2rem 1rem;
    overflow-y: auto;
    align-items: flex-start;
    justify-content: center;
    gap: 2rem;
    backdrop-filter: blur(1px);
}

#modalVisualizarAluno.is-open {
    display: flex;
}

#modalVisualizarAluno .visualizar-dialog {
    position: relative;
    width: min(1080px, 100%);
    max-width: 1080px;
    background: #ffffff;
    border-radius: 18px;
    box-shadow: 0 30px 80px rgba(15, 23, 42, 0.35);
    border: 1px solid rgba(148, 163, 184, 0.25);
    overflow: hidden;
    display: flex;
    flex-direction: column;
}

#modalVisualizarAluno .visualizar-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1.75rem 2rem;
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: #fff;
}

#modalVisualizarAluno .visualizar-title {
    font-size: 1.5rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.6rem;
}

#modalVisualizarAluno .visualizar-body {
    padding: 2rem;
    background: #f8fafc;
    max-height: calc(90vh - 180px);
    overflow-y: auto;
}

#modalVisualizarAluno .visualizar-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    min-height: 200px;
    color: #0d6efd;
    font-weight: 500;
}

#modalVisualizarAluno .visualizar-erro {
    padding: 1.5rem;
    border-radius: 12px;
    background: rgba(220, 53, 69, 0.1);
    color: #842029;
    font-weight: 500;
}

#modalVisualizarAluno .visualizar-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 1rem;
    padding: 1.5rem 2rem;
    background: #ffffff;
    border-top: 1px solid rgba(148, 163, 184, 0.2);
}

#modalVisualizarAluno .visualizar-close {
    border: none;
    background: rgba(255, 255, 255, 0.15);
    color: #ffffff;
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    transition: background 0.2s ease, transform 0.2s ease;
}

#modalVisualizarAluno .visualizar-close:hover {
    background: rgba(255, 255, 255, 0.28);
*/
    transform: scale(1.08);
}

body.visualizar-aluno-open {
    overflow: hidden !important;
}

/* NOTA: Controle de visibilidade do modal está em modal-form.css */
/* Regras removidas - usar apenas .custom-modal[data-opened="true"] */

/* Suavizar opacidade do backdrop */
.modal-backdrop.show {
    opacity: 0.35 !important;
}

/* Indicadores compactos */
.alunos-kpi-bar {
    display: flex;
    align-items: center;
    gap: 16px;
    padding: 8px 0;
    margin-bottom: 12px;
    flex-wrap: nowrap;
}

.alunos-kpi-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: #475569;
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 6px;
    transition: all 0.2s ease;
}

.alunos-kpi-item:hover {
    background: rgba(0, 123, 255, 0.1);
    transform: translateY(-1px);
}

.alunos-kpi-item.active {
    background: rgba(0, 123, 255, 0.15);
    font-weight: 600;
    border-bottom: 2px solid #007bff;
}

.alunos-kpi-icon {
    font-size: 16px;
    opacity: 0.85;
}

.alunos-kpi-icon.total {
    color: #2563eb;
}

.alunos-kpi-icon.ativos {
    color: #16a34a;
}

.alunos-kpi-icon.formacao {
    color: #f59e0b;
}

.alunos-kpi-icon.concluidos {
    color: #0f172a;
}

.alunos-kpi-label {
    font-size: 0.82rem;
    font-weight: 600;
    color: #4b5563;
}

.alunos-kpi-value {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
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
    /* CORREÇÃO: Garantir que os ícones fiquem SEMPRE atrás dos modais */
    z-index: 1 !important;
    position: relative !important;
}

.action-icon-btn {
    width: 28px;
    height: 28px;
    font-size: 0.8rem;
    display: inline-flex !important;
    visibility: visible !important;
    opacity: 1 !important;
    /* CORREÇÃO: z-index baixo para ficar atrás dos modais */
    position: relative !important;
    z-index: 10 !important;
}

/* CORREÇÃO: Estilo especial para quando modal estiver aberto */
body:has(#modalVisualizarAluno.is-open) .action-icon-btn,
body:has(#modalAluno[data-opened="true"]) .action-icon-btn {
    z-index: 1 !important;
}

body:has(#modalVisualizarAluno.is-open) .action-buttons-compact,
body:has(#modalAluno[data-opened="true"]) .action-buttons-compact {
    z-index: 1 !important;
}

/* Garantir que todos os botões de ação sejam visíveis */
.btn-history,
.btn-financial,
.btn-edit,
.btn-view,
.btn-add {
    display: inline-flex !important;
    visibility: visible !important;
    opacity: 1 !important;
}

.alunos-header {
    padding: 12px 0;
    margin-bottom: 12px;
    border-bottom: 1px solid #e2e8f0;
}

.alunos-header h1 {
    margin: 0;
    font-size: 1.875rem;
    font-weight: 700;
    color: #0f172a;
}

.alunos-header-actions {
    display: flex;
    align-items: center;
    gap: 12px;
    flex-wrap: wrap;
}

.alunos-actions-trigger {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 40px;
    padding: 0 14px;
    font-weight: 600;
    border-radius: 10px;
}

.alunos-actions-trigger i {
    font-size: 16px;
}

.alunos-novo-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    height: 40px;
    padding: 0 18px;
    border-radius: 10px;
}

.alunos-filtros-wrapper {
    padding: 8px 0;
    margin-bottom: 12px;
}

.alunos-filtros-toggle {
    display: none;
    width: 100%;
    align-items: center;
    justify-content: space-between;
    border: 1px solid #cbd5f5;
    background: #f8fafc;
    color: #475569;
    border-radius: 10px;
    height: 40px;
    padding: 0 12px;
    font-weight: 600;
    cursor: pointer;
}

.alunos-filtros-toggle i {
    transition: transform 0.2s ease;
}

.alunos-filtros-toggle.is-active i:last-child {
    transform: rotate(180deg);
}

.alunos-filtros-row {
    display: flex;
    align-items: center;
    gap: 16px;
    flex-wrap: nowrap;
}

.alunos-filtro {
    display: flex;
    align-items: center;
}

.alunos-filtro.busca {
    flex: 1 1 320px;
    min-width: 260px;
}

.alunos-input-search {
    position: relative;
    display: flex;
    align-items: center;
    width: 100%;
    height: 40px;
    border: 1px solid #cbd5f5;
    border-radius: 10px;
    background: #ffffff;
}

.alunos-input-search i {
    position: absolute;
    left: 12px;
    color: #64748b;
    font-size: 16px;
}

.alunos-input-search input {
    height: 100%;
    width: 100%;
    border: none;
    border-radius: 10px;
    padding-left: 36px;
    padding-right: 12px;
    font-size: 0.95rem;
    color: #1f2937;
    background: transparent;
}

.alunos-input-search input:focus {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
}

.alunos-input-search input::placeholder {
    color: #94a3b8;
}

.alunos-filtro.select .form-select {
    height: 40px;
    min-width: 220px;
    max-width: 240px;
    font-size: 0.95rem;
    border-radius: 10px;
    border: 1px solid #cbd5f5;
    padding: 0 12px;
    background-color: #ffffff;
    color: #1f2937;
}

.alunos-filtro.select .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.18);
}

.alunos-filtro.acao {
    margin-left: auto;
    flex: 0 0 auto;
}

.alunos-clear-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    height: 40px;
    padding: 0 12px;
    border: none;
    color: #475569;
    font-weight: 600;
    text-decoration: none;
    background: transparent;
    cursor: pointer;
}

.alunos-clear-btn:hover {
    color: #1f2937;
    text-decoration: none;
}

.alunos-actions-trigger:focus-visible,
.alunos-novo-btn:focus-visible,
.alunos-filtros-toggle:focus-visible,
.alunos-clear-btn:focus-visible {
    outline: none;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.35);
}

/* Responsividade melhorada */
@media (max-width: 1200px) {
    .col-lg-2 {
        flex: 0 0 25%;
        max-width: 25%;
    }
    
    .alunos-filtros-row {
        flex-wrap: wrap;
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
    
    .alunos-filtro.select .form-select {
        min-width: 200px;
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

    .alunos-kpi-bar {
        gap: 12px;
        justify-content: flex-start;
    }
    
    .alunos-filtros-row {
        gap: 12px;
    }
}
/* =====================================================
   CSS RESPONSIVO PARA MOBILE - ALUNOS
   ===================================================== */

/* Layout responsivo para tablets */
@media screen and (max-width: 768px), screen and (max-width: 900px) {
    .card .card-body .table-container {
        overflow-x: auto !important;
        -webkit-overflow-scrolling: touch !important;
        border-radius: 8px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
        max-width: 100% !important;
        width: 100% !important;
    }
    
    .card .card-body .table-container .table {
        min-width: 600px !important;
        width: 600px !important;
        font-size: 14px !important;
        table-layout: fixed !important;
    }
    
    .card .card-body .table-container .table th,
    .card .card-body .table-container .table td {
        padding: 8px 6px !important;
        white-space: nowrap !important;
        vertical-align: middle !important;
    }
    
    .action-buttons-compact {
        display: flex !important;
        flex-direction: row !important;
        gap: 5px !important;
        flex-wrap: nowrap !important;
    }

    .alunos-kpi-bar {
        flex-wrap: nowrap !important;
        overflow-x: auto !important;
        padding-bottom: 6px !important;
        gap: 16px !important;
    }

    .alunos-kpi-item {
        flex: 0 0 auto !important;
    }
}
/* Layout em cards para mobile */
@media screen and (max-width: 640px) {
    .alunos-filtros-toggle {
        display: flex;
        margin-bottom: 8px;
    }
    
    .alunos-filtros-row {
        display: none;
        flex-direction: column;
        align-items: stretch;
        gap: 12px;
    }
    
    .alunos-filtros-row.is-open {
        display: flex;
    }
    
    .alunos-filtro {
        width: 100%;
    }
    
    .alunos-filtro.select .form-select {
        width: 100%;
        min-width: 0;
    }
    
    .alunos-filtro.acao {
        margin-left: 0;
    }
    
    .alunos-clear-btn {
        justify-content: flex-start;
        padding: 0;
        height: auto;
    }
    
    .card .card-body .table-container {
        display: none !important;
        overflow: visible !important;
    }
    
    .card .card-body .table-container .table {
        display: none !important;
    }
    
    .card .card-body .mobile-aluno-cards {
        display: block !important;
        width: 100% !important;
    }
    
    .mobile-aluno-card {
        background: #fff !important;
        border: 1px solid #e3e6f0 !important;
        border-radius: 8px !important;
        margin-bottom: 15px !important;
        padding: 15px !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    .mobile-aluno-header {
        display: flex !important;
        align-items: center !important;
        margin-bottom: 12px !important;
    }
    
    /* Avatar mobile removido - CSS órfão removido */
    
    .mobile-aluno-info {
        flex: 1 !important;
    }
    
    .mobile-aluno-title {
        display: flex !important;
        align-items: center !important;
        margin-bottom: 4px !important;
    }
    
    .mobile-aluno-title strong {
        font-size: 16px !important;
        color: #2c3e50 !important;
        margin-right: 8px !important;
    }
    
    .mobile-aluno-id {
        font-size: 12px !important;
        color: #6c757d !important;
        background: #f8f9fa !important;
        padding: 2px 6px !important;
        border-radius: 4px !important;
    }
    
    .mobile-aluno-email {
        font-size: 13px !important;
        color: #6c757d !important;
    }
    
    .mobile-aluno-status {
        margin-left: auto !important;
    }
    
    .mobile-aluno-body {
        margin-bottom: 12px !important;
    }
    
    .mobile-aluno-field {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin-bottom: 8px !important;
    }
    
    .mobile-aluno-label {
        font-size: 12px !important;
        color: #6c757d !important;
        font-weight: 500 !important;
    }
    
    .mobile-aluno-value {
        font-size: 13px !important;
        color: #2c3e50 !important;
    }
    
    .mobile-aluno-actions {
        display: flex !important;
        gap: 8px !important;
        justify-content: center !important;
        flex-wrap: wrap !important;
    }
    
    .mobile-aluno-actions .btn {
        width: 35px !important;
        height: 35px !important;
        padding: 0 !important;
        display: flex !important;
        align-items: center !important;
        justify-content: center !important;
        font-size: 14px !important;
        border-radius: 6px !important;
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

.modal-overlay .modal-content {
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

.modal-overlay .modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%);
    color: white;
    padding: 1rem 1.5rem;
    border-radius: 0.5rem 0.5rem 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-overlay .modal-header h3 {
    margin: 0;
    font-size: 1.25rem;
    font-weight: 600;
}

.modal-overlay .modal-close {
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
.modal-overlay .modal-close:hover {
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
   ESTILOS DO MODAL DE ALUNOS
   =====================================================
   NOTA: As regras estruturais principais do modal foram
   movidas para admin/assets/css/modal-form.css
   
   Mantendo aqui apenas regras específicas que não são
   parte do padrão estrutural.
   ===================================================== */

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
    border-color: var(--cfc-primary, #0F1E4A) !important;
    box-shadow: 0 0 0 0.2rem rgba(15, 30, 74, 0.25) !important;
}

.modal#modalAluno .text-primary {
    color: var(--cfc-primary, #0F1E4A) !important;
}

.modal#modalAluno .border-bottom {
    border-bottom: 2px solid #e9ecef !important;
}

.modal#modalAluno .form-range {
    height: 6px !important;
    border-radius: 3px !important;
}

.modal#modalAluno .form-range::-webkit-slider-thumb {
    background: var(--cfc-primary, #0F1E4A) !important;
    border-radius: 50% !important;
    width: 20px !important;
    height: 20px !important;
}

.modal#modalAluno .form-range::-moz-range-thumb {
    background: var(--cfc-primary, #0F1E4A) !important;
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
    border-left: 4px solid var(--cfc-primary, #0F1E4A) !important;
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

/* =====================================================
   ESTILOS PARA TOASTS MELHORADOS
   ===================================================== */

.toast-container {
    z-index: 9999 !important;
}


/* =====================================================
   ESTILOS PARA VALIDAÇÃO DE CPF
   ===================================================== */

.cpf-validation-feedback {
    font-size: 0.75rem !important;
    margin-top: 0.25rem !important;
    padding: 0.25rem 0.5rem !important;
    border-radius: 0.25rem !important;
    display: none !important;
    transition: opacity 0.3s ease, visibility 0.3s ease;
    opacity: 0;
    visibility: hidden;
}

.cpf-validation-feedback.valid {
    background-color: #d1edff !important;
    color: #0c5460 !important;
    border: 1px solid #bee5eb !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.cpf-validation-feedback.invalid {
    background-color: #f8d7da !important;
    color: #721c24 !important;
    border: 1px solid #f5c6cb !important;
    display: block !important;
    opacity: 1 !important;
    visibility: visible !important;
}

.cpf-validation-feedback::before {
    content: "✓ ";
    font-weight: bold;
}

.cpf-validation-feedback.invalid::before {
    content: "✗ ";
    font-weight: bold;
}

/* Manter contorno verde visível mesmo sem mensagem */
input[type="text"].valid, 
input.form-control.valid {
    border-color: var(--success-color, #28a745) !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

input[type="text"].invalid, 
input.form-control.invalid {
    border-color: var(--danger-color, #dc3545) !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}

/* Estilos para campo CPF com validação */
#cpf.valid {
    border-color: #28a745 !important;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25) !important;
}

#cpf.invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
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

/* =====================================================
   NOVO LAYOUT DO MODAL DE CADASTRO/EDIÇÃO DE ALUNOS
   ===================================================== */

/* NOTA: Regras estruturais do modal estão em modal-form.css */

/* CSS ANTIGO DO MODAL DE VISUALIZAÇÃO - REMOVIDO
   Agora usa o mesmo padrão do #modalAluno (.custom-modal, .custom-modal-dialog, etc.)
   Regras específicas estão em admin/assets/css/modal-form.css
*/
/*
#modalVisualizarAluno.custom-modal,
#modalVisualizarAluno {
    position: fixed;
    inset: 0;
    width: 100vw;
    min-height: 100vh;
    background: rgba(15, 23, 42, 0.55);
    z-index: 10000;
    padding: clamp(16px, 4vw, 48px);
    display: flex;
    align-items: center;
    justify-content: center;
    box-sizing: border-box;
}

#modalVisualizarAluno.custom-modal[data-opened="true"],
#modalVisualizarAluno.custom-modal[style*="display: block"],
#modalVisualizarAluno.custom-modal[style*="display:block"],
#modalVisualizarAluno[data-opened="true"],
#modalVisualizarAluno[style*="display: block"],
#modalVisualizarAluno[style*="display:block"] {
    display: flex !important;
}

#modalVisualizarAluno .custom-modal-dialog {
    width: min(94vw, 1120px);
    max-width: 1120px;
    position: relative;
    left: 50%;
    transform: translateX(-50%);
    margin: 0;
}
*/

/* CSS ANTIGO DO MODAL DE VISUALIZAÇÃO - REMOVIDO
   Agora usa o mesmo padrão do #modalAluno (.custom-modal, .custom-modal-dialog, etc.)
   Regras específicas estão em admin/assets/css/modal-form.css
*/
/*
#modalVisualizarAluno .custom-modal-content {
    width: 100%;
    border-radius: 16px;
    overflow: hidden;
}
*/

/* NOTA: Regras estruturais do modal estão em modal-form.css */

/* =====================================================
   DEBUG VISUAL - DESATIVADO POR PADRÃO
   Para ativar, adicione a classe `layout-debug` em #modalAluno
   ===================================================== */
/*
#modalAluno.layout-debug,
#modalAluno.layout-debug-on {
    outline: 4px solid rgba(148, 163, 184, 0.65);
    box-shadow: 0 0 0 6px rgba(148, 163, 184, 0.25);
}

#modalAluno.layout-debug .custom-modal-content,
#modalAluno.layout-debug-on .custom-modal-content {
    outline: 4px solid rgba(148, 163, 184, 0.8);
    box-shadow: 0 0 0 4px rgba(148, 163, 184, 0.35) inset;
    background: rgba(255, 255, 255, 0.95);
}

#modalAluno.layout-debug .aluno-modal-header,
#modalAluno.layout-debug-on .aluno-modal-header {
    background: rgba(220, 38, 38, 0.35) !important;
    outline: 3px solid rgba(220, 38, 38, 0.75);
}

#modalAluno.layout-debug .aluno-modal-tabs,
#modalAluno.layout-debug-on .aluno-modal-tabs {
    background: rgba(34, 197, 94, 0.35) !important;
    outline: 3px solid rgba(34, 197, 94, 0.75);
}

#modalAluno.layout-debug .aluno-modal-body,
#modalAluno.layout-debug-on .aluno-modal-body {
    background: rgba(59, 130, 246, 0.25) !important;
    outline: 3px dashed rgba(59, 130, 246, 0.9);
}

#modalAluno.layout-debug .aluno-modal-footer,
#modalAluno.layout-debug-on .aluno-modal-footer {
    background: rgba(251, 191, 36, 0.4) !important;
    outline: 3px solid rgba(251, 191, 36, 0.9);
}
*/

/* NOTA: Regras de formulário estão em modal-form.css */

/* NOTA: Regras estruturais do footer e responsividade estão em modal-form.css */
</style>

<div class="alunos-header d-flex justify-content-between flex-wrap align-items-center">
    <h1>
        <i class="fas fa-user-graduate me-2"></i>Gestão de Alunos
    </h1>
    <div class="alunos-header-actions">
        <div class="dropdown">
            <button class="btn btn-outline-secondary alunos-actions-trigger" type="button" id="acoesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-ellipsis-h me-1"></i>Ações
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="acoesDropdown">
                <li><button class="dropdown-item" type="button" onclick="exportarAlunos('csv')"><i class="fas fa-file-csv me-2"></i>Exportar CSV</button></li>
                <li><button class="dropdown-item" type="button" onclick="exportarAlunos('xlsx')"><i class="fas fa-file-excel me-2"></i>Exportar XLSX</button></li>
                <li><button class="dropdown-item" type="button" onclick="imprimirAlunos()"><i class="fas fa-print me-2"></i>Imprimir</button></li>
            </ul>
        </div>
        <button type="button" class="btn btn-primary alunos-novo-btn" onclick="abrirModalAluno()">
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
<div class="alunos-filtros-wrapper">
    <button class="alunos-filtros-toggle" type="button" id="alunosFiltrosToggle" aria-controls="alunosFiltros" aria-expanded="false">
        <span><i class="fas fa-sliders-h me-2"></i>Filtros</span>
        <i class="fas fa-chevron-down"></i>
    </button>
    <div class="alunos-filtros-row" id="alunosFiltros">
        <div class="alunos-filtro busca">
            <label for="buscaAluno" class="visually-hidden">Buscar aluno</label>
            <div class="alunos-input-search">
                <i class="fas fa-search" aria-hidden="true"></i>
                <input type="text" class="form-control" id="buscaAluno" placeholder="Buscar por nome, e-mail ou ID" data-validate="minLength:2" autocomplete="off">
            </div>
        </div>
        <div class="alunos-filtro select">
            <label class="visually-hidden" for="filtroStatus">Status</label>
            <select class="form-select" id="filtroStatus">
                <option value="">Todos os Status</option>
                <option value="ativo">Ativo</option>
                <option value="inativo">Inativo</option>
                <option value="concluido">Concluído</option>
                <option value="pendente">Pendente</option>
            </select>
        </div>
        <div class="alunos-filtro select">
            <label class="visually-hidden" for="filtroCFC">CFC</label>
            <select class="form-select" id="filtroCFC">
                <option value="">Todos os CFCs</option>
                <?php foreach ($cfcs as $cfc): ?>
                    <option value="<?php echo $cfc['id']; ?>"><?php echo htmlspecialchars($cfc['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="alunos-filtro select">
            <label class="visually-hidden" for="filtroCategoria">Categoria</label>
            <select class="form-select" id="filtroCategoria">
                <option value="">Todas as Categorias</option>
                <option value="A">Categoria A</option>
                <option value="B">Categoria B</option>
                <option value="C">Categoria C</option>
                <option value="D">Categoria D</option>
                <option value="E">Categoria E</option>
                <option value="AB">Categoria AB</option>
                <option value="AC">Categoria AC</option>
                <option value="AD">Categoria AD</option>
                <option value="AE">Categoria AE</option>
            </select>
        </div>
        <div class="alunos-filtro acao">
            <button type="button" class="btn btn-link alunos-clear-btn" onclick="limparFiltros()">
                <i class="fas fa-eraser me-1"></i>Limpar
            </button>
        </div>
    </div>
</div>

<!-- Indicadores compactos (clicáveis) -->
<div class="alunos-kpi-bar" role="group" aria-label="Indicadores de alunos">
    <a href="index.php?page=alunos" class="alunos-kpi-item <?php echo $statusFiltroAtual === null ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
        <i class="fas fa-users alunos-kpi-icon total" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Total</span>
        <span class="alunos-kpi-value" id="totalAlunos">
            <?php echo $stats['total_alunos'] ?? count($alunos); ?>
        </span>
    </a>
    <a href="index.php?page=alunos&status=em_formacao" class="alunos-kpi-item <?php echo $statusFiltroAtual === 'em_formacao' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
        <i class="fas fa-check-circle alunos-kpi-icon ativos" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Ativos</span>
        <span class="alunos-kpi-value" id="alunosAtivos">
            <?php echo $stats['total_ativos'] ?? count(array_filter($alunos, function($a) { return ($a['status'] ?? '') === 'ativo'; })); ?>
        </span>
    </a>
    <a href="index.php?page=alunos&status=em_formacao" class="alunos-kpi-item <?php echo $statusFiltroAtual === 'em_formacao' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
        <i class="fas fa-clock alunos-kpi-icon formacao" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Em formação</span>
        <span class="alunos-kpi-value" id="emFormacao">
            <?php echo $stats['total_ativos'] ?? count(array_filter($alunos, function($a) { return ($a['status'] ?? '') === 'ativo'; })); ?>
        </span>
    </a>
    <a href="index.php?page=alunos&status=em_exame" class="alunos-kpi-item <?php echo $statusFiltroAtual === 'em_exame' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
        <i class="fas fa-clipboard-check alunos-kpi-icon" aria-hidden="true" style="color: #dc3545;"></i>
        <span class="alunos-kpi-label">Em exame</span>
        <span class="alunos-kpi-value" id="emExame">
            <?php echo $stats['total_em_exame'] ?? 0; ?>
        </span>
    </a>
    <a href="index.php?page=alunos&status=concluido" class="alunos-kpi-item <?php echo $statusFiltroAtual === 'concluido' ? 'active' : ''; ?>" style="text-decoration: none; color: inherit;">
        <i class="fas fa-graduation-cap alunos-kpi-icon concluidos" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Concluídos</span>
        <span class="alunos-kpi-value" id="concluidos">
            <?php echo $stats['total_concluidos'] ?? count(array_filter($alunos, function($a) { return ($a['status'] ?? '') === 'concluido'; })); ?>
        </span>
    </a>
</div>

<!-- Tabela de Alunos -->
<div class="card shadow">
    <div class="card-header bg-dark">
        <h5 class="mb-0" style="color: #6c757d !important;"><i class="fas fa-list me-2"></i>Lista de Alunos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive table-container">
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
                                // Obter categoria priorizando matrícula ativa
                                $categoriaExibicao = obterCategoriaExibicao($aluno);
                                
                                // Se houver matrícula ativa, usar badge primário; caso contrário, secundário
                                $badgeClass = !empty($aluno['categoria_cnh_matricula']) ? 'bg-primary' : 'bg-secondary';
                                
                                echo '<span class="badge ' . $badgeClass . '" title="Categoria CNH">' . 
                                     htmlspecialchars($categoriaExibicao) . '</span>';
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
                                    <?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
                                    <button type="button" class="btn btn-sm btn-secondary-action btn-edit action-icon-btn" 
                                            onclick="editarAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Editar dados do aluno" data-tooltip="Editar dados do aluno"
                                            style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">
                                        <i class="fas fa-edit" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;"></i>
                                    </button>
                                    <?php endif; ?>
                                    
                                    <button type="button" class="btn btn-sm btn-secondary-action btn-view action-icon-btn" 
                                            onclick="visualizarAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Ver detalhes completos do aluno" data-tooltip="Ver detalhes completos do aluno"
                                            style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">
                                        <i class="fas fa-eye" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;"></i>
                                    </button>
                                    
                                    <!-- Botão de agendamento removido conforme solicitado -->
                                    
                                    <button type="button" class="btn btn-sm btn-secondary-action btn-history action-icon-btn" 
                                            onclick="historicoAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Visualizar histórico de aulas e progresso" data-tooltip="Visualizar histórico de aulas e progresso"
                                            style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">
                                        <i class="fas fa-history" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;"></i>
                                    </button>
                                    
                                    <?php if (defined('FINANCEIRO_ENABLED') && FINANCEIRO_ENABLED && ($isAdmin || $user['tipo'] === 'secretaria')): ?>
                                    <button type="button" class="btn btn-sm btn-secondary-action btn-financial action-icon-btn" 
                                            onclick="abrirFinanceiroAluno(<?php echo $aluno['id']; ?>)" 
                                            title="Ver faturas e pagamentos do aluno" data-tooltip="Ver faturas e pagamentos do aluno"
                                            style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">
                                        <i class="fas fa-dollar-sign" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;"></i>
                                    </button>
                                    <?php endif; ?>
                                    <button type="button" 
                                            class="btn btn-sm btn-toggle-status action-icon-btn <?php echo $aluno['status'] === 'ativo' ? 'btn-outline-secondary' : 'btn-outline-success'; ?>" 
                                            data-aluno-id="<?php echo (int)$aluno['id']; ?>"
                                            data-status="<?php echo htmlspecialchars($aluno['status']); ?>"
                                            onclick="toggleStatusAluno(this)"
                                            title="<?php echo $aluno['status'] === 'ativo' ? 'Desativar aluno (não poderá agendar aulas)' : 'Reativar aluno para agendamento de aulas'; ?>" 
                                            data-bs-toggle="tooltip">
                                        <i class="fas <?php echo $aluno['status'] === 'ativo' ? 'fa-ban' : 'fa-check'; ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Layout em cards para mobile -->
        <div class="mobile-aluno-cards" style="display: none;">
            <?php if (!empty($alunos)): ?>
                <?php foreach ($alunos as $aluno): ?>
                <div class="mobile-aluno-card" data-aluno-id="<?php echo $aluno['id']; ?>">
                    <div class="mobile-aluno-header">
                        <div class="mobile-aluno-info">
                            <div class="mobile-aluno-title">
                                <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                                <span class="mobile-aluno-id">#<?php echo $aluno['id']; ?></span>
                            </div>
                            <?php if ($aluno['email']): ?>
                            <div class="mobile-aluno-email"><?php echo htmlspecialchars($aluno['email']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mobile-aluno-status">
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
                        </div>
                    </div>
                    
                    <div class="mobile-aluno-body">
                        <div class="mobile-aluno-field">
                            <span class="mobile-aluno-label">Categoria</span>
                            <span class="mobile-aluno-value">
                                <?php 
                                // REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
                                // Usar a mesma função helper da tabela desktop
                                $categoriaExibicao = obterCategoriaExibicao($aluno);
                                echo htmlspecialchars($categoriaExibicao);
                                ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="mobile-aluno-actions">
                        <button type="button" class="btn btn-sm btn-primary" onclick="visualizarAluno(<?php echo $aluno['id']; ?>)" title="Ver detalhes">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-warning" onclick="editarAluno(<?php echo $aluno['id']; ?>)" title="Editar aluno">
                            <i class="fas fa-edit"></i>
                        </button>
                        <!-- Botão de agendamento removido conforme solicitado -->
                        <button type="button" class="btn btn-sm btn-secondary" onclick="historicoAluno(<?php echo $aluno['id']; ?>)" title="Histórico de aulas">
                            <i class="fas fa-history"></i>
                        </button>
                        <button type="button" 
                                class="btn btn-sm btn-toggle-status <?php echo $aluno['status'] === 'ativo' ? 'btn-outline-secondary' : 'btn-outline-success'; ?>" 
                                data-aluno-id="<?php echo (int)$aluno['id']; ?>"
                                data-status="<?php echo htmlspecialchars($aluno['status']); ?>"
                                onclick="toggleStatusAluno(this)"
                                title="<?php echo $aluno['status'] === 'ativo' ? 'Desativar aluno (não poderá agendar aulas)' : 'Reativar aluno para agendamento de aulas'; ?>">
                            <i class="fas <?php echo $aluno['status'] === 'ativo' ? 'fa-ban' : 'fa-check'; ?>"></i>
                        </button>
                        <!-- Botão de excluir desativado por segurança -->
                        <!-- <button type="button" class="btn btn-sm btn-danger" onclick="excluirAluno(<?php echo $aluno['id']; ?>)">
                            <i class="fas fa-trash"></i>
                        </button> -->
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- Modal Customizado para Cadastro/Edição de Aluno - BASE LIMPA -->
<div id="modalAluno" class="custom-modal">
  <div class="custom-modal-dialog">
    <div class="custom-modal-content">
      <form id="formAluno" method="POST" class="modal-form aluno-modal-form">
        <input type="hidden" name="acao" id="acaoAluno" value="criar">
        <input type="hidden" name="aluno_id" id="aluno_id_hidden" value="">
        
        <div class="modal-form-header aluno-modal-header">
          <h2 class="aluno-modal-title" id="modalTitle">Editar Aluno</h2>
          <button type="button" class="btn-close aluno-modal-close" onclick="fecharModalAluno()"></button>
        </div>

        <div class="modal-form-tabs aluno-modal-tabs">
          <ul class="nav nav-tabs aluno-tabs" id="alunoTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab" aria-controls="dados" aria-selected="true">
                <i class="fas fa-user"></i>
                <span>Dados</span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="matricula-tab" data-bs-toggle="tab" data-bs-target="#matricula" type="button" role="tab" aria-controls="matricula" aria-selected="false">
                <i class="fas fa-graduation-cap"></i>
                <span>Matrícula</span>
              </button>
            </li>
            <!-- ABA FINANCEIRO (removida do modal, mantida apenas como histórico) -->
            <!--
            <li class="nav-item" role="presentation" id="financeiro-tab-container" style="display: none;">
              <button class="nav-link" id="financeiro-tab" data-bs-toggle="tab" data-bs-target="#financeiro" type="button" role="tab" aria-controls="financeiro" aria-selected="false">
                <i class="fas fa-dollar-sign"></i>
                <span>Financeiro</span>
              </button>
            </li>
            -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab" aria-controls="documentos" aria-selected="false">
                <i class="fas fa-file-alt"></i>
                <span>Documentos</span>
              </button>
            </li>
            <!-- ABA AGENDA (removida do modal, mantida apenas como histórico) -->
            <!--
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="agenda-tab" data-bs-toggle="tab" data-bs-target="#agenda" type="button" role="tab" aria-controls="agenda" aria-selected="false">
                <i class="fas fa-calendar-alt"></i>
                <span>Agenda</span>
              </button>
            </li>
            -->
            <!-- ABA TEÓRICO (removida do modal, mantida apenas como histórico) -->
            <!--
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="teorico-tab" data-bs-toggle="tab" data-bs-target="#teorico" type="button" role="tab" aria-controls="teorico" aria-selected="false">
                <i class="fas fa-chalkboard-teacher"></i>
                <span>Teórico</span>
              </button>
            </li>
            -->
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab" aria-controls="historico" aria-selected="false">
                <i class="fas fa-history"></i>
                <span>Histórico</span>
              </button>
            </li>
          </ul>
        </div>

        <div class="modal-form-body aluno-modal-body">
          <div class="modal-form-panel aluno-modal-panel">
            <div class="tab-content aluno-tab-content" id="alunoTabsContent">
              <!-- Aba Dados (real) -->
              <div class="tab-pane fade show active modal-tab-pane" id="dados" role="tabpanel" aria-labelledby="dados-tab">
                <div class="container-fluid" style="padding: 0;">
                  <!-- DADOS: Seção 1 - Informações Pessoais -->
                  <div class="row mb-2 mt-0">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-user me-1"></i>Informações Pessoais
                      </h6>
                    </div>
                    
                    <!-- Campo de Foto -->
                    <div class="col-12 mb-3">
                      <div class="row">
                        <div class="col-md-4">
                          <div class="mb-2">
                            <label for="foto" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Foto (Opcional)</label>
                            <input type="file" class="form-control" id="foto" name="foto" accept="image/*" 
                                   style="padding: 0.4rem; font-size: 0.85rem;" onchange="previewFotoAluno(this)">
                            <small class="text-muted" style="font-size: 0.75rem;">📷 JPG, PNG, GIF, WebP até 2MB</small>
                          </div>
                        </div>
                        <div class="col-md-8">
                          <div class="text-center">
                            <div id="preview-container-aluno" style="display: none;">
                              <img id="foto-preview-aluno" src="" alt="Preview da foto" 
                                   style="max-width: 150px; max-height: 150px; border-radius: 50%; object-fit: cover; border: 3px solid #dee2e6;">
                              <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removerFotoAluno()">
                                  <i class="fas fa-trash"></i> Remover
                                </button>
                              </div>
                            </div>
                            <div id="placeholder-foto-aluno" class="text-muted" style="font-size: 0.8rem;">
                              <i class="fas fa-user-circle fa-3x"></i><br>
                              Nenhuma foto selecionada
                            </div>
                          </div>
                        </div>
                      </div>
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
                               placeholder="000.000.000-00" style="padding: 0.4rem; font-size: 0.85rem;"
                               data-mask="cpf" data-validate="required|cpf" maxlength="14">
                        <div class="cpf-validation-feedback" style="font-size: 0.75rem; margin-top: 0.25rem; display: none;"></div>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-1">
                        <label for="rg" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">RG</label>
                        <input type="text" class="form-control" id="rg" name="rg" 
                               placeholder="Digite o RG (aceita letras)" maxlength="30" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-1">
                        <label for="data_nascimento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data Nasc. *</label>
                        <input type="date" class="form-control" id="data_nascimento" name="data_nascimento" required style="padding: 0.4rem; font-size: 0.85rem;">
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
                  
                  <!-- Campos complementares do RG -->
                  <div class="row mb-2">
                    <div class="col-md-2">
                      <div class="mb-1">
                        <label for="rg_orgao_emissor" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Órgão Emissor</label>
                        <input type="text" class="form-control" id="rg_orgao_emissor" name="rg_orgao_emissor" 
                               placeholder="Ex.: SSP" maxlength="10" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-1">
                        <label for="rg_uf" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">UF do RG</label>
                        <select class="form-select" id="rg_uf" name="rg_uf" style="padding: 0.4rem; font-size: 0.85rem;">
                          <option value="">Selecione...</option>
                          <option value="AC">AC</option>
                          <option value="AL">AL</option>
                          <option value="AP">AP</option>
                          <option value="AM">AM</option>
                          <option value="BA">BA</option>
                          <option value="CE">CE</option>
                          <option value="DF">DF</option>
                          <option value="ES">ES</option>
                          <option value="GO">GO</option>
                          <option value="MA">MA</option>
                          <option value="MT">MT</option>
                          <option value="MS">MS</option>
                          <option value="MG">MG</option>
                          <option value="PA">PA</option>
                          <option value="PB">PB</option>
                          <option value="PR">PR</option>
                          <option value="PE">PE</option>
                          <option value="PI">PI</option>
                          <option value="RJ">RJ</option>
                          <option value="RN">RN</option>
                          <option value="RS">RS</option>
                          <option value="RO">RO</option>
                          <option value="RR">RR</option>
                          <option value="SC">SC</option>
                          <option value="SP">SP</option>
                          <option value="SE">SE</option>
                          <option value="TO">TO</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-1">
                        <label for="rg_data_emissao" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data Emissão RG</label>
                        <input type="date" class="form-control" id="rg_data_emissao" name="rg_data_emissao" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-6"></div>
                  </div>
                  
                  <!-- Estado Civil, Profissão, Escolaridade -->
                  <div class="row mb-2">
                    <div class="col-md-3">
                      <div class="mb-1">
                        <label for="estado_civil" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Estado Civil</label>
                        <select class="form-select" id="estado_civil" name="estado_civil" style="padding: 0.4rem; font-size: 0.85rem;">
                          <option value="">Selecione...</option>
                          <option value="solteiro">Solteiro(a)</option>
                          <option value="casado">Casado(a)</option>
                          <option value="divorciado">Divorciado(a)</option>
                          <option value="viuvo">Viúvo(a)</option>
                          <option value="uniao_estavel">União estável</option>
                          <option value="outro">Outro</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-1">
                        <label for="profissao" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Profissão</label>
                        <input type="text" class="form-control" id="profissao" name="profissao" 
                               placeholder="Digite a profissão" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="mb-1">
                        <label for="escolaridade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Escolaridade</label>
                        <select class="form-select" id="escolaridade" name="escolaridade" style="padding: 0.4rem; font-size: 0.85rem;">
                          <option value="">Selecione...</option>
                          <option value="fundamental_incompleto">Fundamental incompleto</option>
                          <option value="fundamental_completo">Fundamental completo</option>
                          <option value="medio_incompleto">Médio incompleto</option>
                          <option value="medio_completo">Médio completo</option>
                          <option value="superior_incompleto">Superior incompleto</option>
                          <option value="superior_completo">Superior completo</option>
                          <option value="pos_graduacao">Pós-graduação</option>
                          <option value="outro">Outro</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <div class="row mb-2">
                    <div class="col-md-3">
                      <div class="mb-1">
                        <label for="naturalidade_estado" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Estado (Naturalidade)</label>
                        <select class="form-select" id="naturalidade_estado" name="naturalidade_estado" style="padding: 0.4rem; font-size: 0.85rem;">
                          <option value="">Selecione o estado...</option>
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
                        <label for="naturalidade_municipio" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Município (Naturalidade)</label>
                        <select class="form-select" id="naturalidade_municipio" name="naturalidade_municipio" style="padding: 0.4rem; font-size: 0.85rem;" disabled>
                          <option value="">Primeiro selecione o estado</option>
                        </select>
                      </div>
                    </div>
                    <div class="col-md-3">
                      <div class="mb-1">
                        <label for="nacionalidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Nacionalidade</label>
                        <input type="text" class="form-control" id="nacionalidade" name="nacionalidade" 
                               placeholder="Brasileira" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-2">
                      <div class="mb-1">
                        <label class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">&nbsp;</label>
                        <input type="hidden" id="naturalidade" name="naturalidade">
                        <button type="button" class="btn btn-outline-secondary w-100" id="btnLimparNaturalidade" 
                                style="padding: 0.4rem; font-size: 0.8rem;" title="Limpar seleção">
                          <i class="fas fa-times"></i>
                        </button>
                      </div>
                    </div>
                  </div>
                  
                  <!-- DADOS: Seção 2 - Contatos -->
                  <div class="row mb-2">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-phone me-1"></i>Contatos
                      </h6>
                    </div>
                    <!-- Linha 1: Telefone principal, Telefone secundário, E-mail -->
                    <div class="col-md-4">
                      <div class="mb-1">
                        <label for="telefone" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone</label>
                        <input type="text" class="form-control" id="telefone" name="telefone" 
                               placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-1">
                        <label for="telefone_secundario" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone Secundário</label>
                        <input type="text" class="form-control" id="telefone_secundario" name="telefone_secundario" 
                               placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-1">
                        <label for="email" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">E-mail</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               placeholder="aluno@email.com" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                  </div>
                  
                  <!-- Linha 2: Contato de Emergência -->
                  <div class="row mb-2">
                    <div class="col-md-5">
                      <div class="mb-1">
                        <label for="contato_emergencia_nome" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Contato de Emergência (Nome)</label>
                        <input type="text" class="form-control" id="contato_emergencia_nome" name="contato_emergencia_nome" 
                               placeholder="Nome do contato de emergência" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-4">
                      <div class="mb-1">
                        <label for="contato_emergencia_telefone" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Telefone de Emergência</label>
                        <input type="text" class="form-control" id="contato_emergencia_telefone" name="contato_emergencia_telefone" 
                               placeholder="(00) 00000-0000" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-3"></div>
                  </div>
                  
                  <!-- DADOS: Seção 3 - Endereço -->
                  <div class="row mb-2">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-map-marker-alt me-1"></i>Endereço
                      </h6>
                    </div>
                    
                    <!-- Primeira linha: CEP e Logradouro -->
                    <div class="col-md-3">
                      <div class="mb-1">
                        <label for="cep" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">CEP</label>
                        <div class="input-group">
                          <input type="text" class="form-control" id="cep" name="cep" 
                                 placeholder="00000-000" style="padding: 0.4rem; font-size: 0.85rem;"
                                 maxlength="9">
                          <button type="button" class="btn btn-outline-primary" id="btnBuscarCEP" 
                                  style="padding: 0.4rem 0.6rem; font-size: 0.8rem;"
                                  title="Buscar endereço pelo CEP">
                            <i class="fas fa-search"></i>
                          </button>
                          <a href="https://buscacepinter.correios.com.br/app/endereco/index.php" 
                             target="_blank" 
                             class="btn btn-outline-success" 
                             style="padding: 0.4rem 0.6rem; font-size: 0.8rem;"
                             title="Buscar CEP no site dos Correios">
                            <i class="fas fa-external-link-alt"></i>
                          </a>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
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
                    <div class="col-md-1">
                      <div class="mb-1">
                        <label for="complemento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Complemento</label>
                        <input type="text" class="form-control" id="complemento" name="complemento" 
                               placeholder="Apto, Bloco, etc." style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    
                    <!-- Segunda linha: Bairro, Cidade e UF -->
                    <div class="col-md-4">
                      <div class="mb-1">
                        <label for="bairro" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Bairro</label>
                        <input type="text" class="form-control" id="bairro" name="bairro" 
                               placeholder="Centro, Jardim, etc." style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-5">
                      <div class="mb-1">
                        <label for="cidade" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Cidade</label>
                        <input type="text" class="form-control" id="cidade" name="cidade" 
                               placeholder="Nome da cidade" style="padding: 0.4rem; font-size: 0.85rem;">
                      </div>
                    </div>
                    <div class="col-md-3">
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
                  </div>
                  
                  <!-- DADOS: Seção 4 - Configurações Gerais -->
                  <div class="row mb-2">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-cog me-1"></i>Configurações Gerais
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
                    <div class="col-md-3">
                      <div class="mb-1">
                        <label for="status" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Status do Aluno</label>
                        <select class="form-select" id="status" name="status" style="padding: 0.4rem; font-size: 0.85rem;">
                          <option value="ativo">Ativo</option>
                          <option value="inativo">Inativo</option>
                          <option value="concluido">Concluído</option>
                        </select>
                      </div>
                    </div>
                  </div>
                  
                  <!-- LGPD -->
                  <div class="row mb-2 aluno-lgpd-group">
                    <div class="col-12">
                      <div class="mb-2">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" id="lgpd_consentimento" name="lgpd_consentimento" value="1" style="font-size: 0.9rem;">
                          <label class="form-check-label" for="lgpd_consentimento" style="font-size: 0.85rem;">
                            Autorizo o CFC a utilizar meus dados para contato e registro do processo de habilitação, conforme LGPD.
                          </label>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-6">
                      <div class="mb-1">
                        <label for="lgpd_consentimento_em" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data/Hora do Consentimento</label>
                        <input type="text" class="form-control" id="lgpd_consentimento_em" name="lgpd_consentimento_em" 
                               readonly placeholder="Será preenchido automaticamente quando o fluxo de LGPD estiver ativo." 
                               style="padding: 0.4rem; font-size: 0.85rem; background-color: #f8f9fa;">
                      </div>
                    </div>
                  </div>
                  
                  <div class="row mb-3" id="observacoes-section">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-sticky-note me-1"></i>Observações Gerais
                      </h6>
                      <div class="mb-2">
                        <label for="observacoes" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                  placeholder="Informações adicionais sobre o aluno..." 
                                  style="padding: 0.4rem; font-size: 0.85rem; resize: vertical; min-height: 80px;"></textarea>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- Aba Matrícula -->
              <div class="tab-pane fade modal-tab-pane" id="matricula" role="tabpanel" aria-labelledby="matricula-tab">
                <div class="aluno-tab-pane-inner">
                  <div class="container-fluid" style="padding: 0;">
                    <!-- Cabeçalho da aba Matrícula -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2">
                          Matrícula do Aluno
                        </h6>
                        <p class="text-muted mb-0">
                          Resumo das turmas e do status de matrícula do aluno.
                        </p>
                      </div>
                    </div>

                    <!-- MATRÍCULA: Seção 1 - Curso e Serviços -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                          <i class="fas fa-clipboard-list me-1"></i>Curso e Serviços
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
                    
                    <!-- Linha 1 - Datas da matrícula -->
                    <div class="row mb-2">
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="data_matricula" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data da Matrícula</label>
                          <input type="date" class="form-control" id="data_matricula" name="data_matricula" style="padding: 0.4rem; font-size: 0.85rem;">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="previsao_conclusao" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Previsão de Conclusão</label>
                          <input type="date" class="form-control" id="previsao_conclusao" name="previsao_conclusao" style="padding: 0.4rem; font-size: 0.85rem;">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="data_conclusao" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Data de Conclusão</label>
                          <input type="date" class="form-control" id="data_conclusao" name="data_conclusao" 
                                 placeholder="Preenchida automaticamente quando a matrícula for concluída" 
                                 readonly style="padding: 0.4rem; font-size: 0.85rem; background-color: #f8f9fa; cursor: not-allowed;"
                                 title="Este campo é preenchido automaticamente quando o status da matrícula muda para 'Concluída'">
                        </div>
                      </div>
                    </div>
                    
                    <!-- Linha 2 - Status da matrícula -->
                    <div class="row mb-2">
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="status_matricula" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Status da Matrícula</label>
                          <select class="form-select" id="status_matricula" name="status_matricula" style="padding: 0.4rem; font-size: 0.85rem;">
                            <option value="">Selecione...</option>
                            <option value="em_analise">Em análise</option>
                            <option value="ativa">Ativa</option>
                            <option value="em_formacao">Em formação</option>
                            <option value="em_exame">Em exame</option>
                            <option value="concluida">Concluída</option>
                            <option value="trancada">Trancada</option>
                            <option value="cancelada">Cancelada</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    
                    <!-- MATRÍCULA: Seção 2 - Processo DETRAN -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                          <i class="fas fa-file-alt me-1"></i>Processo DETRAN
                        </h6>
                      </div>
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="renach" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">RENACH *</label>
                          <input type="text" class="form-control" id="renach" name="renach" 
                                 placeholder="PE000000000" maxlength="11" style="padding: 0.4rem; font-size: 0.85rem;"
                                 data-mask="renach" data-required-in-matricula="true">
                          <!-- NOTA: required removido do HTML para evitar erro "not focusable" ao salvar apenas aba Dados.
                               A validação de RENACH obrigatório será feita via JS apenas quando a aba Matrícula for utilizada. -->
                        </div>
                      </div>
                    </div>
                    
                    <!-- Linha 2 - Número do processo, DETRAN e situação -->
                    <div class="row mb-2">
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="processo_numero" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Número do Processo</label>
                          <input type="text" class="form-control" id="processo_numero" name="processo_numero" 
                                 placeholder="Digite o número do processo" style="padding: 0.4rem; font-size: 0.85rem;">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="processo_numero_detran" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Número DETRAN / Protocolo</label>
                          <input type="text" class="form-control" id="processo_numero_detran" name="processo_numero_detran" 
                                 placeholder="Ex.: protocolo DETRAN" style="padding: 0.4rem; font-size: 0.85rem;">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="processo_situacao" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Situação do Processo</label>
                          <select class="form-select" id="processo_situacao" name="processo_situacao" style="padding: 0.4rem; font-size: 0.85rem;">
                            <option value="">Selecione...</option>
                            <option value="nao_informado">Não informado</option>
                            <option value="em_analise">Em análise</option>
                            <option value="em_andamento">Em andamento</option>
                            <option value="aguardando_exame">Aguardando exame</option>
                            <option value="aprovado">Aprovado</option>
                            <option value="reprovado">Reprovado</option>
                            <option value="arquivado">Arquivado</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    
                    <!-- MATRÍCULA: Seção 3 - Vinculação Teórica -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                          <i class="fas fa-book me-1"></i>Vinculação Teórica
                        </h6>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-1">
                          <label for="turma_teorica_atual_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Turma Teórica Atual</label>
                          <select class="form-select" id="turma_teorica_atual_id" name="turma_teorica_atual_id" style="padding: 0.4rem; font-size: 0.85rem;">
                            <option value="">Selecione...</option>
                          </select>
                        </div>
                      </div>
                      <div class="col-md-6">
                        <div class="mb-1">
                          <label for="situacao_teorica" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Situação das Aulas Teóricas</label>
                          <input type="text" class="form-control" id="situacao_teorica" name="situacao_teorica" readonly
                                 placeholder="Será preenchido automaticamente (Não iniciada / Em andamento / Concluída)" 
                                 style="padding: 0.4rem; font-size: 0.85rem; background-color: #f8f9fa;">
                        </div>
                      </div>
                      <div class="col-12">
                        <small class="text-muted" style="font-size: 0.75rem;">
                          Essas informações serão atualizadas automaticamente pela tela de turmas teóricas no futuro.
                        </small>
                      </div>
                    </div>
                    
                    <!-- MATRÍCULA: Seção 4 - Vinculação Prática -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                          <i class="fas fa-car me-1"></i>Vinculação Prática
                        </h6>
                      </div>
                      <!-- Linha 1 - Quantidades e instrutor -->
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="aulas_praticas_contratadas" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Aulas Práticas Contratadas</label>
                          <input type="number" class="form-control" id="aulas_praticas_contratadas" name="aulas_praticas_contratadas" min="0" style="padding: 0.4rem; font-size: 0.85rem;">
                        </div>
                      </div>
                      <div class="col-md-4">
                        <div class="mb-1">
                          <label for="aulas_praticas_extras" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Aulas Extras</label>
                          <input type="number" class="form-control" id="aulas_praticas_extras" name="aulas_praticas_extras" min="0" style="padding: 0.4rem; font-size: 0.85rem;">
                        </div>
                      </div>
                      <!-- Instrutor Principal - OCULTO conforme solicitado -->
                      <div class="col-md-4 d-none">
                        <div class="mb-1">
                          <label for="instrutor_principal_id" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Instrutor Principal</label>
                          <select class="form-select" id="instrutor_principal_id" name="instrutor_principal_id" style="padding: 0.4rem; font-size: 0.85rem;">
                            <option value="">Selecione...</option>
                          </select>
                        </div>
                      </div>
                    </div>
                    
                    <!-- Linha 2 - Situação das práticas -->
                    <div class="row mb-2">
                      <div class="col-md-6">
                        <div class="mb-1">
                          <label for="situacao_pratica" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Situação das Aulas Práticas</label>
                          <input type="text" class="form-control" id="situacao_pratica" name="situacao_pratica" readonly
                                 placeholder="Será preenchido automaticamente (Não iniciada / Em andamento / Concluída)" 
                                 style="padding: 0.4rem; font-size: 0.85rem; background-color: #f8f9fa;">
                        </div>
                      </div>
                    </div>
                    
                    <!-- MATRÍCULA: Seção 5 - Provas -->
                    <hr class="my-3">
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-uppercase text-muted small mb-2">
                          <i class="fas fa-file-signature me-1"></i> Provas
                        </h6>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Prova Teórica</label>
                        <input type="text"
                               class="form-control"
                               name="prova_teorica_resumo"
                               id="prova_teorica_resumo"
                               placeholder="Sem informação"
                               readonly
                               style="padding: 0.4rem; font-size: 0.85rem; background-color: #f8f9fa;">
                        <small class="text-muted d-block mt-1" id="prova_teorica_detalhes"></small>
                      </div>
                      <div class="col-md-6 mb-3">
                        <label class="form-label">Prova Prática</label>
                        <input type="text"
                               class="form-control"
                               name="prova_pratica_resumo"
                               id="prova_pratica_resumo"
                               placeholder="Sem informação"
                               readonly
                               style="padding: 0.4rem; font-size: 0.85rem; background-color: #f8f9fa;">
                        <small class="text-muted d-block mt-1" id="prova_pratica_detalhes"></small>
                      </div>
                    </div>
                    
                    <!-- MATRÍCULA: Seção 6 - Resumo financeiro da matrícula -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                          <i class="fas fa-money-bill-wave me-1"></i>Resumo financeiro da matrícula
                        </h6>
                        <p class="text-muted small mb-2" style="font-size: 0.75rem;">
                          Essas informações vêm do Financeiro do Aluno e são usadas na emissão do contrato.<br>
                          Não é possível editar o financeiro por aqui.
                        </p>
                        
                        <!-- Campo oculto para armazenar aluno_id para uso no resumo -->
                        <input type="hidden" id="editar_aluno_id" value="">
                        
                        <!-- Card de resumo financeiro da matrícula -->
                        <div class="card border" style="background-color: #f8f9fa;">
                          <div class="card-body p-3">
                            <div id="resumo-financeiro-matricula-card">
                              <div class="text-center text-muted small">
                                <i class="fas fa-spinner fa-spin"></i> Carregando resumo financeiro...
                              </div>
                            </div>
                          </div>
                        </div>
                        
                        <!-- Texto informativo abaixo do card -->
                        <p class="text-muted small mt-2 mb-0" style="font-size: 0.75rem;">
                          <i class="fas fa-info-circle me-1"></i>
                          Lançamentos de cobrança, alterações de parcelas e situação de pagamento ficam apenas no Financeiro do Aluno.
                        </p>
                      </div>
                    </div>
                    
                    <!-- Resumo Financeiro do Aluno (somente leitura) - Mantido para compatibilidade -->
                    <div class="row mb-2">
                      <div class="col-12">
                        <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                          <i class="fas fa-calculator me-1"></i>Resumo Financeiro do Aluno
                        </h6>
                        <div id="resumo-financeiro-matricula" class="p-2" style="background-color: #f8f9fa; border-radius: 0.25rem; min-height: 80px;">
                          <div class="text-center text-muted small">
                            <i class="fas fa-spinner fa-spin"></i> Carregando resumo financeiro...
                          </div>
                        </div>
                      </div>
                    </div>
                    
                    <!-- TODO: integrar campos de matrícula no backend -->
                  </div>
                </div>
              </div>
              
              <!-- ABA FINANCEIRO DO ALUNO (desativada no modal; será reusada futuramente em outra tela/resumo) -->
              <!--
              <div class="tab-pane fade modal-tab-pane" id="financeiro" role="tabpanel" aria-labelledby="financeiro-tab">
                <p>Conteúdo da aba Financeiro será reintroduzido depois.</p>
              </div>
              -->
              
              <!-- Aba Documentos -->
              <div class="tab-pane fade modal-tab-pane" id="documentos" role="tabpanel" aria-labelledby="documentos-tab">
                <div class="aluno-tab-pane-inner">
                  <div class="container-fluid" id="documentos-container">
                    <h6 class="text-primary border-bottom pb-1 mb-2">Documentos do Aluno</h6>
                    <p class="text-muted mb-3" style="font-size: 0.85rem;">
                      Envie e gerencie os documentos do aluno. Formatos aceitos: PDF, JPG, PNG (máx. 5MB).
                    </p>

                    <!-- Formulário de Upload - SEM <form> aninhado -->
                    <div class="card mb-3" style="border: 1px solid #dee2e6;">
                      <div class="card-body p-3">
                        <div id="documentos-aluno-wrapper">
                          <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                              <label for="tipo-documento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Tipo de Documento</label>
                              <select class="form-select form-select-sm" id="tipo-documento" style="font-size: 0.85rem;">
                                <option value="">Selecione...</option>
                                <option value="rg">RG</option>
                                <option value="cpf">CPF</option>
                                <option value="comprovante_residencia">Comprovante de Residência</option>
                                <option value="foto_3x4">Foto 3x4</option>
                                <option value="outro">Outro</option>
                              </select>
                            </div>
                            <div class="col-md-5">
                              <label for="arquivo-documento" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.25rem;">Arquivo</label>
                              <input type="file" class="form-control form-control-sm" id="arquivo-documento" 
                                     accept=".pdf,.jpg,.jpeg,.png" style="font-size: 0.85rem; padding: 0.4rem;">
                            </div>
                            <div class="col-md-3">
                              <button type="button" class="btn btn-primary btn-sm w-100" id="btn-enviar-documento" onclick="enviarDocumento(event)" style="font-size: 0.85rem;">
                                <i class="fas fa-upload me-1"></i>Enviar
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Lista de Documentos -->
                    <div id="documentos-list">
                      <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                        <i class="fas fa-file-alt fa-2x mb-3"></i>
                        <p class="mb-0" style="font-size: 0.9rem;">Carregando documentos...</p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <!-- ABA AGENDA DO ALUNO (desativada no modal; será reusada futuramente em outra tela/resumo) -->
              <!--
              <div class="tab-pane fade modal-tab-pane" id="agenda" role="tabpanel" aria-labelledby="agenda-tab">
                <p>Conteúdo da aba Agenda será reintroduzido depois.</p>
              </div>
              -->
              
              <!-- ABA TEÓRICO DO ALUNO (desativada no modal; será reusada futuramente em outra tela/resumo) -->
              <!--
              <div class="tab-pane fade modal-tab-pane" id="teorico" role="tabpanel" aria-labelledby="teorico-tab">
                <p>Conteúdo da aba Teórico será reintroduzido depois.</p>
              </div>
              -->
              
              <!-- Aba Histórico -->
              <div class="tab-pane fade modal-tab-pane" id="historico" role="tabpanel" aria-labelledby="historico-tab">
                <div class="container-fluid" style="padding: 0;">
                  <!-- HISTÓRICO: Cabeçalho da Jornada -->
                  <div class="row mb-3">
                    <div class="col-12 border-bottom pb-2">
                      <h5 class="text-primary mb-0">
                        <i class="fas fa-history me-2"></i>Jornada do Aluno
                      </h5>
                      <p class="text-muted small mb-0">Visão completa da trajetória do aluno no CFC</p>
                    </div>
                  </div>
                  
                  <!-- HISTÓRICO: Seção Cards de Resumo -->
                  <div class="aluno-historico-cards row mb-4">
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                          <i class="fas fa-clipboard-check fa-2x text-primary mb-2"></i>
                          <h6 class="card-title mb-1">Situação do Processo</h6>
                          <div class="aluno-card-valor" data-field="processo_status_resumo">
                            <p class="card-text text-muted small mb-0">Em breve resumo do progresso</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                          <i class="fas fa-chalkboard-teacher fa-2x text-info mb-2"></i>
                          <h6 class="card-title mb-1">Progresso Teórico</h6>
                          <div class="aluno-card-valor" data-field="teorico_resumo">
                            <p class="card-text text-muted small mb-0">Em breve resumo do progresso</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                          <i class="fas fa-car fa-2x text-success mb-2"></i>
                          <h6 class="card-title mb-1">Progresso Prático</h6>
                          <div class="aluno-card-valor" data-field="pratico_resumo">
                            <p class="card-text text-muted small mb-0">Em breve resumo do progresso</p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                          <i class="fas fa-dollar-sign fa-2x text-warning mb-2"></i>
                          <h6 class="card-title mb-1">Situação Financeira</h6>
                          <div class="aluno-card-valor" data-field="financeiro_resumo" id="card-situacao-financeira-historico">
                            <p class="card-text text-muted small mb-0">
                              <span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>
                              Carregando situação financeira...
                            </p>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="col-md-3 col-sm-6 mb-3">
                      <div class="card border-0 shadow-sm h-100">
                        <div class="card-body text-center">
                          <i class="fas fa-file-signature fa-2x text-danger mb-2"></i>
                          <h6 class="card-title mb-1">Provas</h6>
                          <div class="aluno-card-valor" data-field="provas_resumo">
                            <p class="card-text text-muted small mb-0">Em breve resumo do progresso</p>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <!-- HISTÓRICO: Seção Linha do Tempo -->
                  <div class="row mb-4">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-3" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-clock me-1"></i>Linha do Tempo
                      </h6>
                      <div id="historico-container">
                        <ul class="aluno-timeline-list">
                          <!-- Placeholder enquanto não houver eventos -->
                          <li class="aluno-timeline-empty">
                            <div class="aluno-timeline-empty-icon">
                              <i class="fas fa-history fa-2x text-muted"></i>
                            </div>
                            <p class="text-muted mb-0">
                              Os eventos mais recentes do aluno aparecerão aqui.
                            </p>
                          </li>
                        </ul>
                      </div>
                    </div>
                  </div>
                  
                  <!-- HISTÓRICO: Seção Atalhos Rápidos -->
                  <div class="aluno-atalhos-rapidos row">
                    <div class="col-12">
                      <h6 class="text-primary border-bottom pb-1 mb-3" style="font-size: 0.9rem; margin-bottom: 0.5rem !important;">
                        <i class="fas fa-link me-1"></i>Atalhos Rápidos
                      </h6>
                      <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-link aluno-atalho" data-acao="abrir-agenda-aluno" disabled>
                          <i class="fas fa-calendar-alt me-1"></i>Abrir Agenda Completa
                        </button>
                        <button type="button" class="btn btn-link aluno-atalho" data-acao="ver-financeiro-aluno" disabled>
                          <i class="fas fa-dollar-sign me-1"></i>Ver Financeiro do Aluno
                        </button>
                        <button type="button" class="btn btn-link aluno-atalho" data-acao="ver-turma-teorica" disabled>
                          <i class="fas fa-chalkboard-teacher me-1"></i>Ver Turma Teórica
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="modal-form-footer aluno-modal-footer">
          <button type="button" class="btn btn-outline-secondary aluno-btn-cancelar" onclick="fecharModalAluno()">
            <i class="fas fa-times me-1"></i>Cancelar
          </button>
          <button type="submit" class="btn btn-primary aluno-btn-salvar" id="btnSalvarAluno">
            <i class="fas fa-save me-1"></i>Salvar Aluno
          </button>
        </div>
      </form>
    </div>
  </div>
</div>


<!-- Modal personalizado para Visualização de Aluno - Padronizado com #modalAluno -->
<div id="modalVisualizarAluno" class="custom-modal modal-visualizar-aluno">
  <div class="custom-modal-dialog">
    <div class="custom-modal-content">
      <div class="modal-form-header visualizar-modal-header">
        <h2 class="visualizar-modal-title">
          <i class="fas fa-eye me-2"></i>Detalhes do Aluno
        </h2>
        <button type="button" class="btn-close" onclick="fecharModalVisualizarAluno()" aria-label="Fechar modal"></button>
      </div>

      <div class="modal-form-body visualizar-modal-body" id="modalVisualizarAlunoBody">
        <!-- Conteúdo será carregado via JavaScript -->
      </div>

      <div class="modal-form-footer visualizar-modal-footer">
        <button type="button" class="btn btn-outline-secondary me-2" onclick="fecharModalVisualizarAluno()">
          <i class="fas fa-times me-1"></i>Fechar
        </button>
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
                                    $operacoes = $aluno['operacoes'];
                                    if (is_string($operacoes)) {
                                        $operacoes = json_decode($operacoes, true);
                                    }
                                    
                                    if (!empty($operacoes) && is_array($operacoes)) {
                                        $categorias = array_map(function($op) { 
                                            return $op['categoria'] ?? $op['categoria_cnh'] ?? 'N/A'; 
                                        }, $operacoes);
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
                <label for="observacoes_aula">Observações</label>
                <textarea id="observacoes_aula" name="observacoes_aula" rows="3" placeholder="Observações sobre a aula..."></textarea>
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
// =====================================================
// DECLARAÇÃO GLOBAL DE FUNÇÕES (ANTES DE TUDO)
// =====================================================
// Garantir que abrirModalAluno está disponível globalmente desde o início
// (será redefinida mais abaixo com a implementação completa)
if (typeof window.abrirModalAluno === 'undefined') {
    window.abrirModalAluno = function() {
        console.warn('abrirModalAluno chamada antes de ser totalmente inicializada');
    };
}

// =====================================================
// FLAG DE DEBUG PARA MODAL DE ALUNO
// =====================================================
// ROTAS DE ATALHOS
// Financeiro do aluno: index.php?page=financeiro-faturas&aluno_id={ID}
// Agenda do aluno: index.php?page=agendamento (TODO: ajustar rota da agenda se for criada página específica para agenda do aluno)
// Turma teórica: index.php?page=turmas-teoricas&acao=detalhes&turma_id={ID}

// Contexto global do aluno atual (para atalhos)
let contextoAlunoAtual = {
    alunoId: null,
    matriculaId: null,
    turmaTeoricaId: null
};

const DEBUG_MODAL_ALUNO = false;

function logModalAluno(...args) {
    if (!DEBUG_MODAL_ALUNO) return;
    console.log(...args);
}

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

const localDebounce = (func, delay = 300) => {
    let timeoutId;
    return (...args) => {
        clearTimeout(timeoutId);
        timeoutId = setTimeout(() => func.apply(this, args), delay);
    };
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
    inicializarFiltrosResponsivos();
    
    // Inicializar controles do modal
inicializarModalAluno();
    
    // Adicionar event listener para o formulário e botão
    const formAluno = document.getElementById('formAluno');
    const btnSalvar = document.getElementById('btnSalvarAluno');
    
    if (formAluno) {
        console.log('[DEBUG] Inicializando eventos do formulário formAluno');
        formAluno.addEventListener('submit', async function(e) {
            e.preventDefault();
            console.log('[DEBUG] Submit formAluno disparado');
            
            // Detectar qual aba está ativa (Bootstrap usa aria-selected ou classe active)
            const matriculaTab = document.getElementById('matricula-tab');
            const matriculaPane = document.getElementById('matricula');
            
            // Verificar de múltiplas formas (Bootstrap pode usar diferentes métodos)
            const isMatriculaActive = (matriculaTab && (
                matriculaTab.classList.contains('active') || 
                matriculaTab.getAttribute('aria-selected') === 'true'
            )) || (matriculaPane && (
                matriculaPane.classList.contains('active') || 
                matriculaPane.classList.contains('show')
            ));
            
            console.log('[DEBUG] Submit - Detecção de aba:', {
                isMatriculaActive: isMatriculaActive,
                tabHasActive: matriculaTab?.classList.contains('active'),
                tabAriaSelected: matriculaTab?.getAttribute('aria-selected'),
                paneHasActive: matriculaPane?.classList.contains('active'),
                paneHasShow: matriculaPane?.classList.contains('show')
            });
            
            if (isMatriculaActive) {
                // Se está na aba Matrícula, salvar Matrícula
                console.log('[DEBUG] Submit - ✅ Aba Matrícula detectada, chamando saveAlunoMatricula');
                await saveAlunoMatricula();
            } else {
                // Se está em outra aba, salvar apenas Dados
                console.log('[DEBUG] Submit - ⚠️ Aba Matrícula NÃO detectada, chamando saveAlunoDados');
                await saveAlunoDados(false);
            }
        });
    }
    
    if (btnSalvar) {
        console.log('[DEBUG] Inicializando eventos do botão Salvar Aluno');
        btnSalvar.addEventListener('click', async function(e) {
            e.preventDefault();
            console.log('[DEBUG] Clique no botão Salvar Aluno');
            
            // Detectar qual aba está ativa (Bootstrap usa aria-selected ou classe active)
            const matriculaTab = document.getElementById('matricula-tab');
            const matriculaPane = document.getElementById('matricula');
            
            // Verificar de múltiplas formas (Bootstrap pode usar diferentes métodos)
            const isMatriculaActive = (matriculaTab && (
                matriculaTab.classList.contains('active') || 
                matriculaTab.getAttribute('aria-selected') === 'true'
            )) || (matriculaPane && (
                matriculaPane.classList.contains('active') || 
                matriculaPane.classList.contains('show')
            ));
            
            console.log('[DEBUG] Detecção de aba:', {
                matriculaTab: matriculaTab ? 'existe' : 'não existe',
                matriculaPane: matriculaPane ? 'existe' : 'não existe',
                tabHasActive: matriculaTab?.classList.contains('active'),
                tabAriaSelected: matriculaTab?.getAttribute('aria-selected'),
                paneHasActive: matriculaPane?.classList.contains('active'),
                paneHasShow: matriculaPane?.classList.contains('show'),
                isMatriculaActive: isMatriculaActive
            });
            
            // Se está na aba Matrícula, salvar Matrícula
            if (isMatriculaActive) {
                console.log('[DEBUG] ✅ Aba Matrícula detectada como ativa, chamando saveAlunoMatricula');
                await saveAlunoMatricula();
            } else {
                // Se está em outra aba, salvar apenas Dados
                console.log('[DEBUG] ⚠️ Aba Matrícula NÃO detectada como ativa, chamando saveAlunoDados');
                await saveAlunoDados(false);
            }
        });
    }

    // Fechar modal de visualização com ESC
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('visualizar-aluno-open')) {
            fecharModalVisualizarAluno();
        }
    });

    const modalVisualizarOverlay = document.getElementById('modalVisualizarAluno');
    if (modalVisualizarOverlay) {
        modalVisualizarOverlay.addEventListener('click', (event) => {
            if (event.target === modalVisualizarOverlay) {
                fecharModalVisualizarAluno();
            }
        });
    }

    // Mostrar notificação de carregamento
    if (typeof notifications !== 'undefined') {
        notifications.info('Página de alunos carregada com sucesso!');
    }
});
function inicializarMascarasAluno() {
    // Máscara para CPF
    if (typeof IMask !== 'undefined') {
        new IMask(document.getElementById('cpf'), {
            mask: '000.000.000-00'
        });
        
        // RG sem máscara - aceita todos os formatos dos estados brasileiros
        // (alguns estados usam letras e formatos variados)
        
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
    
    // Busca de CEP ao pressionar Enter
    document.getElementById('cep').addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            const cep = this.value.replace(/\D/g, '');
            if (cep.length === 8) {
                buscarCEP(cep);
            } else {
                mostrarFeedbackCEP('warning', 'CEP deve ter 8 dígitos. Exemplo: 12345-678');
            }
        }
    });
    
    // Botão de busca manual
    document.getElementById('btnBuscarCEP').addEventListener('click', function() {
        const cepInput = document.getElementById('cep');
        const cep = cepInput.value.replace(/\D/g, '');
        if (cep.length === 8) {
            buscarCEP(cep);
        } else {
            mostrarFeedbackCEP('warning', 'CEP deve ter 8 dígitos. Exemplo: 12345-678');
            cepInput.focus();
        }
    });
    
    // Botão de busca por rua agora é um link direto para os Correios
    
    // Event listeners para naturalidade
    document.getElementById('naturalidade_estado').addEventListener('change', function() {
        const estado = this.value;
        const municipioSelect = document.getElementById('naturalidade_municipio');
        
        console.log('Estado selecionado:', estado); // Debug
        
        if (estado) {
            carregarMunicipios(estado);
            municipioSelect.disabled = false;
        } else {
            municipioSelect.innerHTML = '<option value="">Primeiro selecione o estado</option>';
            municipioSelect.disabled = true;
            atualizarNaturalidade();
        }
    });
    
    document.getElementById('naturalidade_municipio').addEventListener('change', function() {
        atualizarNaturalidade();
    });
    
    document.getElementById('btnLimparNaturalidade').addEventListener('click', function() {
        document.getElementById('naturalidade_estado').value = '';
        document.getElementById('naturalidade_municipio').innerHTML = '<option value="">Primeiro selecione o estado</option>';
        document.getElementById('naturalidade_municipio').disabled = true;
        document.getElementById('naturalidade').value = '';
    });
}

function buscarCEP(cep) {
    // Mostrar indicador de carregamento
    const cepInput = document.getElementById('cep');
    const originalPlaceholder = cepInput.placeholder;
    cepInput.placeholder = 'Buscando...';
    cepInput.style.backgroundColor = '#f8f9fa';
    
    // Adicionar ícone de carregamento
    const loadingIcon = document.createElement('span');
    loadingIcon.innerHTML = ' <i class="fas fa-spinner fa-spin text-primary"></i>';
    loadingIcon.id = 'cep-loading';
    cepInput.parentNode.appendChild(loadingIcon);
    
    fetch(`https://viacep.com.br/ws/${cep}/json/`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição');
            }
            return response.json();
        })
        .then(data => {
            // Remover indicador de carregamento
            cepInput.placeholder = originalPlaceholder;
            cepInput.style.backgroundColor = '';
            const loadingElement = document.getElementById('cep-loading');
            if (loadingElement) {
                loadingElement.remove();
            }
            
            if (!data.erro) {
                // Preencher campos automaticamente
                document.getElementById('logradouro').value = data.logradouro || '';
                document.getElementById('bairro').value = data.bairro || '';
                document.getElementById('cidade').value = data.localidade || '';
                document.getElementById('uf').value = data.uf || '';
                
                // Mostrar feedback de sucesso
                mostrarFeedbackCEP('success', 'Endereço encontrado e preenchido automaticamente!');
                
                // Destacar campos preenchidos
                ['logradouro', 'bairro', 'cidade', 'uf'].forEach(campo => {
                    const elemento = document.getElementById(campo);
                    if (elemento) {
                        elemento.style.backgroundColor = '#d4edda';
                        setTimeout(() => {
                            elemento.style.backgroundColor = '';
                        }, 2000);
                    }
                });
            } else {
                mostrarFeedbackCEP('warning', 'CEP não encontrado. Verifique o número digitado.');
                cepInput.style.borderColor = '#ffc107';
                setTimeout(() => {
                    cepInput.style.borderColor = '';
                }, 3000);
            }
        })
        .catch(error => {
            console.error('Erro ao buscar CEP:', error);
            
            // Remover indicador de carregamento
            cepInput.placeholder = originalPlaceholder;
            cepInput.style.backgroundColor = '';
            const loadingElement = document.getElementById('cep-loading');
            if (loadingElement) {
                loadingElement.remove();
            }
            
            mostrarFeedbackCEP('error', 'Erro ao buscar CEP. Verifique sua conexão e tente novamente.');
            cepInput.style.borderColor = '#dc3545';
            setTimeout(() => {
                cepInput.style.borderColor = '';
            }, 3000);
        });
}

function mostrarFeedbackCEP(tipo, mensagem) {
    // Remover feedback anterior se existir
    const feedbackAnterior = document.getElementById('cep-feedback');
    if (feedbackAnterior) {
        feedbackAnterior.remove();
    }
    
    // Criar elemento de feedback
    const feedback = document.createElement('div');
    feedback.id = 'cep-feedback';
    feedback.className = `alert alert-${tipo === 'success' ? 'success' : tipo === 'warning' ? 'warning' : 'danger'} alert-dismissible fade show`;
    feedback.style.cssText = 'position: fixed; top: 20px; right: 20px; z-index: 9999; min-width: 300px; font-size: 0.9rem;';
    feedback.innerHTML = `
        <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'warning' ? 'exclamation-triangle' : 'times-circle'} me-2"></i>
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Adicionar ao body
    document.body.appendChild(feedback);
    
    // Remover automaticamente após 5 segundos
    setTimeout(() => {
        if (feedback.parentNode) {
            feedback.remove();
        }
    }, 5000);
}


// Funções para naturalidade
// Globais para evitar carregamentos duplicados
let carregamentoMunicipios = {}; // Estado -> Promise em andamento

/**
 * Carrega municípios de um estado via API centralizada
 * Fonte: admin/api/municipios.php?uf={estado}
 * 
 * NOTA: Esta função foi atualizada para usar a fonte centralizada de municípios
 * em vez do array hardcoded, garantindo consistência e facilidade de manutenção.
 */
function carregarMunicipios(estado) {
    const municipioSelect = document.getElementById('naturalidade_municipio');
    
    if (!municipioSelect) {
        console.error('❌ Select de município não encontrado');
        return Promise.reject('Select de município não encontrado');
    }
    
    if (!estado || estado.trim() === '') {
        municipioSelect.innerHTML = '<option value="">Primeiro selecione o estado</option>';
        municipioSelect.disabled = true;
        return Promise.resolve();
    }
    
    console.log('🔄 Carregando municípios para estado:', estado, '(via API centralizada)');
    
    // Se já está no meio de um carregamento para este estado, retornar a promessa existente
    if (carregamentoMunicipios[estado]) {
        console.log('⏭️ Carregamento já em andamento para', estado, '- aguardando...');
        return carregamentoMunicipios[estado];
    }
    
    // Mostrar indicador de carregamento
    municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
    municipioSelect.disabled = true;
    
    // Buscar municípios da API centralizada
    // NOTA: Detectar caminho base automaticamente baseado na URL atual
    // A página é acessada via index.php?page=alunos (em admin/index.php)
    // Então o caminho relativo correto é ../api/ (subindo de pages/ para admin/, depois entrando em api/)
    // Mas se a URL já contém /admin/, usar caminho absoluto
    let apiUrl;
    const currentPath = window.location.pathname;
    
    // Detectar caminho base corretamente
    // A URL atual é algo como: /cfc-bom-conselho/admin/index.php
    // Precisamos construir: /cfc-bom-conselho/admin/api/municipios.php
    if (currentPath.includes('/admin/')) {
        // Se a URL contém /admin/, extrair o caminho base até /admin/
        const adminIndex = currentPath.indexOf('/admin/');
        const basePath = currentPath.substring(0, adminIndex + 7); // +7 para incluir '/admin/'
        apiUrl = `${basePath}api/municipios.php?uf=${encodeURIComponent(estado)}`;
    } else {
        // Caso contrário, tentar caminho relativo (assumindo que estamos em admin/pages/)
        apiUrl = `../api/municipios.php?uf=${encodeURIComponent(estado)}`;
    }
    
    console.log('🔍 Carregando municípios de:', apiUrl, '(path atual:', currentPath, ')');
    
    const promiseEmAndamento = fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Erro ao carregar municípios');
            }
            
            const municipios = data.municipios || [];
            console.log(`✅ ${municipios.length} municípios carregados para ${estado} (via API)`);
            
            // Limpar select e adicionar opção padrão
            municipioSelect.innerHTML = '<option value="">Selecione o município...</option>';
            
            // Adicionar municípios ao select (já vêm ordenados da API)
            municipios.forEach(municipio => {
                const option = document.createElement('option');
                option.value = municipio;
                option.textContent = municipio;
                municipioSelect.appendChild(option);
            });
            
            municipioSelect.disabled = false;
            
            // Disparar evento de mudança
            municipioSelect.dispatchEvent(new Event('change'));
            
            // Limpar a referência ao carregamento
            delete carregamentoMunicipios[estado];
            
            return municipios;
        })
        .catch(error => {
            console.error('❌ Erro ao carregar municípios da API:', error);
            municipioSelect.innerHTML = '<option value="">Erro ao carregar municípios</option>';
            municipioSelect.disabled = true;
            
            // Limpar a referência ao carregamento
            delete carregamentoMunicipios[estado];
            
            // Fallback: tentar usar lista estática se API falhar
            console.warn('⚠️ Tentando fallback para lista estática...');
            try {
                const municipiosFallback = getMunicipiosPorEstado(estado);
                if (municipiosFallback && municipiosFallback.length > 0) {
                    municipioSelect.innerHTML = '<option value="">Selecione o município...</option>';
                    municipiosFallback.sort((a, b) => a.localeCompare(b, 'pt-BR'));
                    municipiosFallback.forEach(municipio => {
                        const option = document.createElement('option');
                        option.value = municipio;
                        option.textContent = municipio;
                        municipioSelect.appendChild(option);
                    });
                    municipioSelect.disabled = false;
                    console.log('✅ Fallback: Municípios carregados da lista estática');
                }
            } catch (fallbackError) {
                console.error('❌ Erro no fallback:', fallbackError);
            }
            
            throw error;
        });
    
    // Armazenar a Promise em andamento
    carregamentoMunicipios[estado] = promiseEmAndamento;
    
    return promiseEmAndamento;
}

function atualizarNaturalidade() {
    const estado = document.getElementById('naturalidade_estado').value;
    const municipio = document.getElementById('naturalidade_municipio').value;
    const naturalidadeInput = document.getElementById('naturalidade');
    
    console.log('Atualizando naturalidade - Estado:', estado, 'Município:', municipio); // Debug
    
    if (estado && municipio) {
        // Converter sigla do estado para nome completo
        const nomeEstado = getNomeEstadoPorSigla(estado);
        naturalidadeInput.value = `${municipio} - ${nomeEstado}`;
        console.log('Naturalidade atualizada:', naturalidadeInput.value); // Debug
    } else {
        naturalidadeInput.value = '';
        console.log('Naturalidade limpa'); // Debug
    }
}

function extrairEstadoNaturalidade(naturalidade) {
    console.log('🔍 extrairEstadoNaturalidade - Input:', naturalidade);
    if (!naturalidade) return '';
    const partes = naturalidade.split(' - ');
    console.log('🔍 extrairEstadoNaturalidade - Partes:', partes);
    if (partes.length > 1) {
        const nomeEstado = partes[1];
        console.log('🔍 extrairEstadoNaturalidade - Nome do estado:', nomeEstado);
        // Converter nome do estado para sigla
        const sigla = getSiglaEstadoPorNome(nomeEstado);
        console.log('🔍 extrairEstadoNaturalidade - Sigla resultante:', sigla);
        return sigla;
    }
    return '';
}

function extrairMunicipioNaturalidade(naturalidade) {
    console.log('🔍 extrairMunicipioNaturalidade - Input:', naturalidade);
    if (!naturalidade) return '';
    const partes = naturalidade.split(' - ');
    console.log('🔍 extrairMunicipioNaturalidade - Partes:', partes);
    console.log('🔍 extrairMunicipioNaturalidade - Partes.length:', partes.length);
    const municipio = partes.length > 1 ? partes[0] : '';
    console.log('🔍 extrairMunicipioNaturalidade - Município resultante:', municipio);
    console.log('🔍 extrairMunicipioNaturalidade - Município trim:', municipio.trim());
    return municipio.trim();
}
// Lista estática de municípios por estado (principais municípios)
/**
 * FALLBACK: Lista hardcoded de municípios (PLANO B)
 * 
 * ⚠️ ATENÇÃO: Esta função é apenas um FALLBACK caso a API falhe.
 * A fonte oficial de municípios é a API: admin/api/municipios.php
 * que carrega dados de admin/data/municipios_br.php (base completa do IBGE).
 * 
 * Esta lista contém apenas municípios principais e deve ser usada
 * APENAS em caso de falha na requisição da API.
 * 
 * @param {string} estado - Sigla do estado (ex: 'PE', 'SP')
 * @returns {Array} Array de nomes de municípios
 */
function getMunicipiosPorEstado(estado) {
    console.log('⚠️ FALLBACK: Usando lista hardcoded para estado:', estado);
    console.warn('A API de municípios falhou. Esta é uma lista parcial apenas para emergência.');
    
    const municipios = {
        'PE': [
            'Recife', 'Olinda', 'Jaboatão dos Guararapes', 'Caruaru', 'Petrolina', 
            'Paulista', 'Cabo de Santo Agostinho', 'Camaragibe', 'Garanhuns', 'Vitória de Santo Antão',
            'Igarassu', 'São Lourenço da Mata', 'Abreu e Lima', 'Ipojuca', 'Santa Cruz do Capibaribe',
            'Carpina', 'Belo Jardim', 'Gravatá', 'Escada', 'Goiana', 'Serra Talhada', 'Araripina',
            'Bom Conselho', 'Palmares', 'Bezerros', 'Limoeiro', 'Surubim', 'Pesqueira', 'Salgueiro',
            'Timbaúba', 'Moreno', 'São Bento do Una', 'Barreiros', 'Custódia', 'Buíque', 'Lajedo',
            'Águas Belas', 'Canhotinho', 'Correntes', 'Tupanatinga', 'Caetés', 'Calçado', 'Jupi',
            'Jurema', 'Lagoa do Ouro', 'Palmeirina', 'Paranatama', 'Pedra', 'Quipapá', 'Salgadinho',
            'São João', 'Tacaimbó', 'Tacaratu', 'Terezinha', 'Venturosa'
        ],
        'SP': [
            'São Paulo', 'Guarulhos', 'Campinas', 'São Bernardo do Campo', 'Santo André',
            'Osasco', 'Ribeirão Preto', 'Sorocaba', 'Mauá', 'São José dos Campos',
            'Mogi das Cruzes', 'Diadema', 'Jundiaí', 'Carapicuíba', 'Piracicaba',
            'Bauru', 'Itaquaquecetuba', 'Franca', 'São Vicente', 'Praia Grande',
            'Guarujá', 'Taubaté', 'Limeira', 'Suzano', 'Sumaré', 'Barueri', 'Embu das Artes',
            'São José do Rio Preto', 'Mogi Guaçu', 'Americana', 'Araraquara', 'Jacareí',
            'Presidente Prudente', 'Marília', 'Itapevi', 'Cotia', 'Ferraz de Vasconcelos',
            'Indaiatuba', 'Hortolândia', 'Rio Claro', 'Cubatão', 'Itapecerica da Serra'
        ],
        'RJ': [
            'Rio de Janeiro', 'São Gonçalo', 'Duque de Caxias', 'Nova Iguaçu', 'Niterói',
            'Belford Roxo', 'São João de Meriti', 'Campos dos Goytacazes', 'Petrópolis', 'Volta Redonda',
            'Magé', 'Macaé', 'Itaboraí', 'Cabo Frio', 'Angra dos Reis', 'Nova Friburgo',
            'Barra Mansa', 'Teresópolis', 'Mesquita', 'Maricá', 'Nilópolis', 'Queimados',
            'São Pedro da Aldeia', 'Rio das Ostras', 'Itaguaí', 'Japeri', 'Cachoeiras de Macacu',
            'Resende', 'Barra do Piraí', 'Valença', 'Vassouras', 'Três Rios', 'Paraíba do Sul',
            'Sapucaia', 'Paty do Alferes', 'Miguel Pereira', 'Engenheiro Paulo de Frontin'
        ],
        'MG': [
            'Belo Horizonte', 'Uberlândia', 'Contagem', 'Juiz de Fora', 'Betim', 'Montes Claros',
            'Ribeirão das Neves', 'Uberaba', 'Governador Valadares', 'Ipatinga', 'Sete Lagoas',
            'Divinópolis', 'Santa Luzia', 'Ibirité', 'Poços de Caldas', 'Patos de Minas',
            'Pouso Alegre', 'Teófilo Otoni', 'Barbacena', 'Sabará', 'Vespasiano', 'Conselheiro Lafaiete',
            'Varginha', 'Araguari', 'Itabira', 'Passos', 'Lavras', 'Araxá', 'Coronel Fabriciano',
            'Ubá', 'Muriaé', 'Formiga', 'Caratinga', 'Ituiutaba', 'Nova Lima', 'João Monlevade',
            'Pará de Minas', 'Timóteo', 'Ouro Preto', 'Mariana', 'Diamantina', 'São João del Rei'
        ],
        'BA': [
            'Salvador', 'Feira de Santana', 'Vitória da Conquista', 'Camaçari', 'Juazeiro',
            'Lauro de Freitas', 'Ilhéus', 'Itabuna', 'Jequié', 'Teixeira de Freitas',
            'Barreiras', 'Alagoinhas', 'Porto Seguro', 'Simões Filho', 'Paulo Afonso',
            'Eunápolis', 'Guanambi', 'Jacobina', 'Serra do Ramalho', 'Senhor do Bonfim',
            'Dias d\'Ávila', 'Valença', 'Conceição do Coité', 'Bom Jesus da Lapa', 'Candeias',
            'Santo Antônio de Jesus', 'Euclides da Cunha', 'Santo Amaro', 'Casa Nova', 'Cruz das Almas',
            'Mata de São João', 'Serrinha', 'Sobradinho', 'Xique-Xique', 'Ribeira do Pombal',
            'Castro Alves', 'Mucuri', 'Correntina', 'Livramento de Nossa Senhora', 'Remanso'
        ],
        'PB': [
            'João Pessoa', 'Campina Grande', 'Santa Rita', 'Patos', 'Bayeux', 'Sousa',
            'Cajazeiras', 'Guarabira', 'Mamanguape', 'Cabedelo', 'Monteiro', 'Esperança',
            'Queimadas', 'Pombal', 'Itabaiana', 'Conde', 'Alagoa Grande', 'Bananeiras',
            'Areia', 'Solânea', 'Picuí', 'Princesa Isabel', 'Cuité', 'Sapé', 'Rio Tinto',
            'Alagoa Nova', 'Lagoa Seca', 'Massaranduba', 'Pilões', 'Serra Branca', 'Sumé',
            'Taperoá', 'Teixeira', 'Uiraúna', 'Vieirópolis', 'Cacimba de Dentro', 'Cacimba de Areia',
            'Cacimbas', 'Caiçara', 'Cajazeirinhas', 'Caldas Brandão', 'Camalaú', 'Capim',
            'Carrapateira', 'Catingueira', 'Catolé do Rocha', 'Caturité', 'Coremas', 'Coxixola',
            'Cuitegi', 'Curral de Cima', 'Curral Velho', 'Damião', 'Desterro', 'Diamante',
            'Dona Inês', 'Duas Estradas', 'Emas', 'Fagundes', 'Frei Martinho', 'Gado Bravo',
            'Gurinhém', 'Gurjão', 'Ibiara', 'Igaracy', 'Imaculada', 'Ingá', 'Itaporanga',
            'Itapororoca', 'Itatuba', 'Jacaraú', 'Jericó', 'Joca Claudino', 'Juarez Távora',
            'Juazeirinho', 'Junco do Seridó', 'Juripiranga', 'Juru', 'Lagoa', 'Lagoa de Dentro',
            'Lagoa do Mato', 'Lastro', 'Livramento', 'Logradouro', 'Lucena', 'Mãe d\'Água',
            'Malta', 'Manaíra', 'Marcação', 'Mari', 'Marizópolis', 'Maturéia', 'Mogeiro',
            'Montadas', 'Monte Horebe', 'Nazarezinho', 'Nova Floresta', 'Nova Olinda', 'Nova Palmeira',
            'Olho d\'Água', 'Olivedos', 'Ouro Velho', 'Parari', 'Passagem', 'Paulista',
            'Pedra Branca', 'Pedra Lavrada', 'Pedras de Fogo', 'Pedro Régis', 'Piancó', 'Pilar',
            'Pilõezinhos', 'Pirpirituba', 'Pitimbu', 'Pocinhos', 'Poço Dantas', 'Poço de José de Moura',
            'Pombal', 'Prata', 'Princesa Isabel', 'Puxinanã', 'Queimadas', 'Quixabá',
            'Remígio', 'Riachão', 'Riachão do Bacamarte', 'Riachão do Poço', 'Riacho de Santo Antônio',
            'Riacho dos Cavalos', 'Ribeira', 'Rio Tinto', 'Salgadinho', 'Salgado de São Félix',
            'Santa Cecília', 'Santa Cruz', 'Santa Helena', 'Santa Inês', 'Santa Luzia',
            'Santa Teresinha', 'Santana de Mangueira', 'Santana dos Garrotes', 'Santo André',
            'São Bentinho', 'São Bento', 'São Domingos', 'São Domingos do Cariri', 'São Francisco',
            'São João do Cariri', 'São João do Rio do Peixe', 'São João do Tigre', 'São José da Lagoa Tapada',
            'São José de Caiana', 'São José de Espinharas', 'São José de Piranhas', 'São José de Princesa',
            'São José do Bonfim', 'São José do Brejo do Cruz', 'São José do Sabugi', 'São José dos Cordeiros',
            'São José dos Ramos', 'São Mamede', 'São Miguel de Taipu', 'São Sebastião de Lagoa de Roça',
            'São Sebastião do Umbuzeiro', 'Sapé', 'Seridó', 'Serra Branca', 'Serra da Raiz',
            'Serra Grande', 'Serra Redonda', 'Serraria', 'Sertãozinho', 'Sobrado', 'Solânea',
            'Soledade', 'Sossêgo', 'Sousa', 'Sumé', 'Tacima', 'Taperoá', 'Tavares',
            'Teixeira', 'Tenório', 'Triunfo', 'Uiraúna', 'Umbuzeiro', 'Várzea',
            'Vieirópolis', 'Vista Serrana', 'Zabelê'
        ],
        'SC': [
            'Florianópolis', 'Joinville', 'Blumenau', 'São José', 'Criciúma', 'Chapecó',
            'Itajaí', 'Balneário Camboriú', 'Lages', 'Jaraguá do Sul', 'Palhoça', 'Caxias do Sul',
            'Tubarão', 'Navegantes', 'Americana', 'Camboriú', 'Gaspar', 'Brusque', 'Pomerode',
            'Corupá', 'Luiz Alves', 'Guaramirim', 'Massaranduba', 'Schroeder', 'São Bento do Sul',
            'Rio Negrinho', 'Mafra', 'Rio do Sul', 'São Miguel do Oeste', 'Araranguá', 'Sombrio',
            'Laguna', 'Urussanga', 'Cocal do Sul', 'Morro da Fumaça', 'Tubarão', 'Capivari de Baixo',
            'Gravatal', 'Armazém', 'Braço do Norte', 'São Ludgero', 'Orleans', 'Capivari de Baixo',
            'Lauro Muller', 'Meleiro', 'Pescaria Brava', 'Praia Grande', 'Timbó', 'Rio do Oeste',
            'Ponte Alta', 'Agrolândia', 'Atalanta', 'Chapadão do Lageado', 'Dona Emma', 'Ibirama',
            'Lontras', 'Witmarsum'
        ],
        'AL': [
            'Maceió', 'Arapiraca', 'Rio Largo', 'Marechal Deodoro', 'Palmeira dos Índios',
            'União dos Palmares', 'São Luís do Quitunde', 'Murici', 'Satuba', 'Messias',
            'Barra de Santo Antônio', 'Barra de São Miguel', 'Coruripe', 'Penedo', 'Porto de Pedras',
            'Porto Real do Colégio', 'São Miguel dos Campos', 'Viçosa', 'Limoeiro de Anadia',
            'Mata Grande', 'Canhotinho', 'Quebrangulo', 'Major Isidoro', 'Feliz Deserto',
            'Traipu', 'Santana do Ipanema', 'Palmeira dos Índios', 'São Miguel dos Milagres',
            'Igaci', 'Maragogi', 'São José da Tapera', 'Lagoa da Canoa', 'Carneiros',
            'Águas Belas', 'Boca da Mata', 'Pindoba', 'Passo de Camaragibe', 'Minador do Negrão',
            'Olho d\'Água do Casado', 'São Sebastião', 'Porto Calvo', 'Mata Grande', 'Pindorama'
        ],
        'AM': [
            'Manaus', 'Coari', 'Rio Preto da Eva', 'Itacoatiara', 'Manicoré', 'Tefé',
            'Tabatinga', 'Santo Antônio do Içá', 'Lábrea', 'Benjamin Constant', 'Barcelos',
            'Codajás', 'Iranduba', 'Careiro da Várzea', 'Anamã', 'Autazes', 'Beruri',
            'Boa Vista do Ramos', 'Caapiranga', 'Canutama', 'Capixaba', 'Envira',
            'Fonte Boa', 'Humaitá', 'Ipixuna', 'Itamarati', 'Japurá', 'Juruá',
            'Maués', 'Nhamundá', 'Nova Olinda do Norte', 'Parintins', 'Santa Isabel do Rio Negro',
            'São Gabriel da Cachoeira', 'Silves', 'Uruará', 'Tonantins', 'Tapauá',
            'Canafístula', 'Anori', 'Careiro', 'Itapiranga', 'São Paulo de Olivença'
        ],
        'RS': [
            'Porto Alegre', 'Caxias do Sul', 'Pelotas', 'Canoas', 'Gravataí', 'Viamão',
            'Novo Hamburgo', 'São Leopoldo', 'Rio Grande', 'Alvorada', 'Santa Maria',
            'Guaíba', 'Cachoeirinha', 'Bagé', 'Bento Gonçalves', 'Erechim', 'Passo Fundo',
            'Sapucaia do Sul', 'Uruguaiana', 'Santa Cruz do Sul', 'Venâncio Aires', 'Farroupilha',
            'Montenegro', 'Osório', 'Pantano Grande', 'São Borja', 'Torres', 'Pelotas',
            'Livramento', 'Quaraí', 'Sant\'Ana do Livramento', 'Alegrete', 'São Gabriel',
            'Não-Me-Toque', 'Espumoso', 'Soledade', 'Três Passos', 'Frederico Westphalen',
            'Carazinho', 'Nova Prata', 'Gravatal', 'Ijuí', 'Santa Rosa', 'Três de Maio'
        ],
        'GO': [
            'Goiânia', 'Aparecida de Goiânia', 'Anápolis', 'Rio Verde', 'Luziânia', 'Águas Lindas de Goiás',
            'Valparaíso de Goiás', 'Trindade', 'Formosa', 'Novo Gama', 'Planaltina', 'Senador Canedo',
            'Catalão', 'Caldas Novas', 'Itumbiara', 'Jataí', 'Mineiros', 'Morrinhos',
            'Goianésia', 'São Luís de Montes Belos', 'Quirinópolis', 'Cidade Ocidental', 'Montes Claros de Goiás',
            'Corumbaíba', 'Ceres', 'Uberlândia', 'Santo Antônio de Goiás', 'Inhumas',
            'Palmas', 'Paraúna', 'Pirenópolis', 'Pires do Rio', 'Piracanjuba', 'Posse',
            'Rubiataba', 'Silvânia', 'Sumário', 'Turvânia', 'Vianópolis', 'Vicentinópolis'
        ],
        'AC': [
            'Rio Branco', 'Cruzeiro do Sul', 'Sena Madureira', 'Tarauacá', 'Feijó', 'Brasiléia',
            'Xapuri', 'Mâncio Lima', 'Acrelândia', 'Bujari', 'Capixaba', 'Epitaciolândia',
            'Jordão', 'Manoel Urbano', 'Marechal Thaumaturgo', 'Plácido de Castro',
            'Porto Acre', 'Porto Walter', 'Rodrigues Alves', 'Santa Rosa do Purus',
            'Senador Guiomard', 'Tarauacá', 'Assis Brasil', 'Enéas Marques'
        ],
        'AP': [
            'Macapá', 'Santana', 'Laranjal do Jari', 'Oiapoque', 'Porto Grande', 'Mazagão',
            'Vitória do Jari', 'Amapá', 'Calçoene', 'Cutias', 'Ferreira Gomes', 'Itaubal',
            'Pedra Branca do Amapari', 'Pracuúba', 'Serra do Navio', 'Tartarugalzinho'
        ],
        'CE': [
            'Fortaleza', 'Caucaia', 'Juazeiro do Norte', 'Maracanaú', 'Sobral', 'Itapipoca',
            'Crato', 'Quixadá', 'Iguatu', 'Pacatuba', 'Aquiraz', 'Maranguape', 'Marco',
            'Mucambo', 'Pacoti', 'Pindoretama', 'Redenção', 'São Gonçalo do Amarante',
            'Senador Pompeu', 'Tabuleiro do Norte', 'Tarrafas', 'Tauá', 'Tejuçuoca',
            'Tianguá', 'Trairi', 'Tururu', 'Ubajara', 'Umari', 'Umirim', 'Uruburetama'
        ],
        'MA': [
            'São Luís', 'Imperatriz', 'São José de Ribamar', 'Timon', 'Caxias', 'Codó',
            'Paço do Lumiar', 'Açailândia', 'Bacabal', 'Balsas', 'Zé Doca', 'Santa Inês',
            'Barra do Corda', 'Santa Luzia', 'Chapadinha', 'Pinheiro', 'Araguanã', 'Arari',
            'Axixá', 'Bacurituba', 'Barro Branco', 'Barro Duro', 'Bom Jesus das Selvas',
            'Cajapió', 'Cajari', 'Cantanhede', 'Carutapera', 'Cedral', 'Centro do Guilherme',
            'Centro Novo do Maranhão', 'Colinas', 'Conchas', 'Coroatá', 'Cururupu',
            'Dom Pedro', 'Esperantinópolis', 'Estreito', 'Formosa da Serra Negra'
        ],
        'MT': [
            'Cuiabá', 'Várzea Grande', 'Rondonópolis', 'Sorriso', 'Cáceres', 'Sinop',
            'Tangará da Serra', 'Lucas do Rio Verde', 'Barra do Garidos', 'Campo Verde',
            'Colíder', 'Diamantino', 'Guarantã do Norte', 'Mirassol d\'Oeste', 'Nova Mutum',
            'Nova Xavantina', 'Poconé', 'Pontes e Lacerda', 'Primavera do Leste', 'Santo Antônio do Leverger',
            'São José do Xingu', 'Tapurah', 'Vila Rica', 'Alto Paraguai', 'Alto Taquari',
            'Apiacás', 'Araguaiana', 'Araguainha', 'Arenápolis', 'Aripuanã', 'Barão de Melgaço',
            'Barra do Bugres', 'Barra do Garidos', 'Bom Jesus do Araguaia', 'Brasnorte', 'Campinápolis'
        ],
        'MS': [
            'Campo Grande', 'Dourados', 'Três Lagoas', 'Corumbá', 'Ponta Porã', 'Naviraí',
            'Nova Andradina', 'Sidrolândia', 'Paranaíba', 'Aquidauana', 'Maracaju', 'Coxim',
            'Bonito', 'Jardim', 'Amambai', 'Pedro Gomes', 'Miranda', 'Anastácio',
            'Bandeirantes', 'Bela Vista', 'Caarapó', 'Cassilândia', 'Chapadão do Sul',
            'Eldorâdo', 'Guia Lopes da Laguna', 'Icaraíma', 'Inocência', 'Itaporã',
            'Ivinhema', 'Ladário', 'Lodo Pinto', 'Mundo Novo', 'Nioaque', 'Nova Alvorada do Sul',
            'Nova Esperança do Sul', 'Porto Murtinho', 'Ribas do Rio Pardo', 'Rio Negro'
        ],
        'PA': [
            'Belém', 'Ananindeua', 'Santarém', 'Marabá', 'Parauapebas', 'Castanhal', 'Abaetetuba',
            'Cametá', 'Marituba', 'Benevides', 'Bragança', 'Breves', 'Itaituba', 'Oriximiná',
            'Altamira', 'Barcarena', 'Conceição do Araguaia', 'Capanema', 'Tucuruí', 'Paragominas',
            'Redenção', 'Faro', 'Limoeiro do Ajuru', 'Oeiras do Pará', 'Terra Alta', 'Tomé-Açu',
            'Cachoeira do Piriá', 'Garrafão do Norte', 'Baião', 'Pacajá', 'Dom Eliseu', 'Rondon do Pará',
            'Jacareacanga', 'Eldorado dos Carajás', 'Itupiranga', 'Goianésia do Pará', 'São Miguel do Guamá',
            'Senador José Porfírio', 'Uruará', 'Vitória do Xingu', 'Xinguara', 'Tailândia', 'Ipixuna do Pará'
        ],
        'RO': [
            'Porto Velho', 'Ji-Paraná', 'Ariquemes', 'Vilhena', 'Cacoal', 'Rolim de Moura', 'Guajará-Mirim',
            'Jaru', 'Ouro Preto do Oeste', 'Buritis', 'Nova Mamoré', 'Alto Alegre dos Parecis', 'Alvorada D\'Oeste',
            'Cabixi', 'Campo Novo de Rondônia', 'Candeias do Jamari', 'Caxiuanã', 'Colorado do Oeste',
            'Costa Marques', 'Cujubim', 'Espigão D\'Oeste', 'Humaitá', 'Guajará-Mirim', 'Mirante da Serra',
            'Minister Andréazza', 'Monte Negro', 'Nova Brasilândia D\'Oeste', 'Novo Triunfo', 'Parecis',
            'Pimenta Bueno', 'Presidente Médici', 'Rio Crespo', 'Seringueiras', 'Theobroma',
            'Vale do Paraíso', 'Vista Alegre do Abunã'
        ],
        'RR': [
            'Boa Vista', 'Rorainópolis', 'Caracaraí', 'Alto Alegre', 'Bonfim', 'Cantá', 'Caroebe',
            'Iracema', 'Mucajaí', 'Normandia', 'Pacaraima', 'Santa Elisabeth do Rio Novo',
            'São João da Baliza', 'São Luiz', 'Uiramutã', 'Amajari', 'Crec¸ia', 'Pedra Branca do Amapari'
        ],
        'PI': [
            'Teresina', 'Parnaíba', 'Picos', 'Piripiri', 'Floriano', 'Campo Maior', 'Barras',
            'União', 'Altos', 'Caxias', 'Esperantina', 'São Raimundo Nonato', 'Corrente',
            'Valença do Piauí', 'Piripiri', 'São João do Piauí', 'Caxias do Sul do Piauí',
            'Pedro II', 'Cocal', 'São Miguel do Tapuio', 'Teresina', 'Timon', 'Gilbués',
            'José de Freitas', 'Nazaré do Piauí', 'Simplício Mendes', 'Simões', 'São João do Piauí'
        ],
        'ES': [
            'Vitória', 'Vila Velha', 'Cariacica', 'Serra', 'Linhares', 'São Mateus', 'Colatina',
            'Guarapari', 'Aracruz', 'Viana', 'Nova Venécia', 'Barra de São Francisco', 'São Gabriel da Palha',
            'Santa Teresa', 'Baixo Guandu', 'Montanha', 'Ecoporanga', 'Jaguaré', 'Iconha', 'Iúna',
            'Itapemirim', 'Laranja da Terra', 'Mantenópolis', 'Mimoso do Sul', 'Muqui',
            'Pinheiros', 'Rio Novo do Sul', 'São Domingos do Norte', 'Vargem Alta', 'Venda Nova do Imigrante'
        ],
        'DF': [
            'Brasília', 'Sobradinho', 'Taguatinga', 'Ceilândia', 'Planaltina', 'Santa Maria',
            'São Sebastião', 'Gama', 'Samambaia', 'Riacho Fundo', 'Arniqueira', 'Brazlândia',
            'Candangolândia', 'Cruzeiro', 'Estrutural', 'Fercal', 'Guará', 'Itapoã',
            'Jardim Botânico', 'Lago Norte', 'Lago Sul', 'Núcleo Bandeirante', 'Paranoá',
            'Pernambuco', 'Recanto das Emas', 'SCIA', 'SIA', 'Sudoeste/Octogonal',
            'Varjão', 'Vicente Pires'
        ],
        'PR': [
            'Curitiba', 'Londrina', 'Maringá', 'Ponta Grossa', 'Cascavel', 'São José dos Pinhais',
            'Foz do Iguaçu', 'Colombo', 'Guarapuava', 'Paranaguá', 'Araucária', 'Toledo',
            'Apucarana', 'Mafra', 'Pinhais', 'Santo Antônio da Platina', 'Medianeira', 'Umuarama',
            'Cambé', 'Francisco Beltrão', 'Irati', 'Piraquara', 'Arapongas', 'Telêmaco Borba',
            'Fazenda Rio Grande', 'Quatro Barras', 'Campo Mourão', 'Jaguariaiva', 'Campo Largo',
            'Laranjeiras do Sul', 'Sarandi', 'Nova Londrina', 'Reserva', 'Pitanga', 'Tupãssi'
        ],
        'RN': [
            'Natal', 'Mossoró', 'Parnamirim', 'São Gonçalo do Amarante', 'Macaíba', 'Ceará-Mirim',
            'Currais Novos', 'Caicó', 'Açu', 'Nova Cruz', 'João Câmara', 'São Paulo do Potengi',
            'Pau dos Ferros', 'Santa Cruz', 'Extremoz', 'Jucurutu', 'São Miguel', 'Baraúna',
            'Acari', 'Almino Afonso', 'Água Nova', 'Alexandria', 'Alto do Rodrigues',
            'Angicos', 'Antônio Martins', 'Apodi', 'Areia Branca', 'Arês', 'Augusto Severo',
            'Baía Formosa', 'Bangu', 'Bento Fernandes', 'Bodó', 'Bom Jesus', 'Brejinho'
        ]
    };
    
    const resultado = municipios[estado] || [];
    console.log('Resultado da busca para estado "' + estado + '":', resultado); // Debug
    console.log('🔍 Total de municípios encontrados:', resultado.length);
    
    if (estado === 'PE') {
        console.log('🔍 Verificando se Bom Conselho está na lista:', resultado.includes('Bom Conselho'));
        console.log('🔍 Lista completa de municípios PE:', resultado);
    }
    
    if (resultado.length === 0) {
        console.warn('⚠️ Nenhum município encontrado para estado:', estado);
        
        // Alertar usuário sobre estado não configurado
        setTimeout(() => {
            if (typeof mostrarAlerta === 'function') {
                mostrarAlerta(`Estado "${estado}" não possui municípios configurados. Entre em contato com o suporte.`, 'warning');
            } else {
                alert(`Estado "${estado}" não possui municípios configurados. Entre em contato com o suporte.`);
            }
        }, 1000);
    } else {
        console.log('✅ Municípios carregados para', estado, ':', resultado.slice(0, 5), resultado.length > 5 ? '...e mais ' + (resultado.length - 5) + ' municípios' : '');
    }
    
    return resultado;
}

// Funções auxiliares para conversão entre sigla e nome do estado
function getNomeEstadoPorSigla(sigla) {
    const estados = {
        'AC': 'Acre', 'AL': 'Alagoas', 'AP': 'Amapá', 'AM': 'Amazonas', 'BA': 'Bahia',
        'CE': 'Ceará', 'DF': 'Distrito Federal', 'ES': 'Espírito Santo', 'GO': 'Goiás',
        'MA': 'Maranhão', 'MT': 'Mato Grosso', 'MS': 'Mato Grosso do Sul', 'MG': 'Minas Gerais',
        'PA': 'Pará', 'PB': 'Paraíba', 'PR': 'Paraná', 'PE': 'Pernambuco', 'PI': 'Piauí',
        'RJ': 'Rio de Janeiro', 'RN': 'Rio Grande do Norte', 'RS': 'Rio Grande do Sul',
        'RO': 'Rondônia', 'RR': 'Roraima', 'SC': 'Santa Catarina', 'SP': 'São Paulo',
        'SE': 'Sergipe', 'TO': 'Tocantins'
    };
    return estados[sigla] || sigla;
}

function getSiglaEstadoPorNome(nome) {
    const estados = {
        'Acre': 'AC', 'Alagoas': 'AL', 'Amapá': 'AP', 'Amazonas': 'AM', 'Bahia': 'BA',
        'Ceará': 'CE', 'Distrito Federal': 'DF', 'Espírito Santo': 'ES', 'Goiás': 'GO',
        'Maranhão': 'MA', 'Mato Grosso': 'MT', 'Mato Grosso do Sul': 'MS', 'Minas Gerais': 'MG',
        'Pará': 'PA', 'Paraíba': 'PB', 'Paraná': 'PR', 'Pernambuco': 'PE', 'Piauí': 'PI',
        'Rio de Janeiro': 'RJ', 'Rio Grande do Norte': 'RN', 'Rio Grande do Sul': 'RS',
        'Rondônia': 'RO', 'Roraima': 'RR', 'Santa Catarina': 'SC', 'São Paulo': 'SP',
        'Sergipe': 'SE', 'Tocantins': 'TO'
    };
    return estados[nome] || nome;
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
    const inputBusca = document.getElementById('buscaAluno');
    if (!inputBusca) return;

    const aplicarBusca = (typeof debounce === 'function' ? debounce : localDebounce)(() => {
        filtrarAlunos();
    }, 300);

    inputBusca.addEventListener('input', aplicarBusca);

    inputBusca.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            filtrarAlunos();
        } else if (event.key === 'Escape' || event.key === 'Esc') {
            inputBusca.value = '';
            filtrarAlunos();
        }
    });
}

function inicializarFiltrosResponsivos() {
    const filtrosRow = document.getElementById('alunosFiltros');
    const toggleBtn = document.getElementById('alunosFiltrosToggle');
    if (!filtrosRow) return;

    const aplicarEstado = () => {
        const isMobile = window.innerWidth <= 640;

        if (isMobile) {
            if (toggleBtn) {
                const aberto = filtrosRow.classList.contains('is-open');
                toggleBtn.setAttribute('aria-expanded', aberto ? 'true' : 'false');
                toggleBtn.classList.toggle('is-active', aberto);
            }
        } else {
            filtrosRow.classList.add('is-open');
            if (toggleBtn) {
                toggleBtn.setAttribute('aria-expanded', 'true');
                toggleBtn.classList.remove('is-active');
            }
        }
    };

    if (window.innerWidth > 640) {
        filtrosRow.classList.add('is-open');
        if (toggleBtn) {
            toggleBtn.classList.remove('is-active');
        }
    }

    aplicarEstado();
    const aplicarEstadoDebounced = (typeof debounce === 'function' ? debounce : localDebounce)(aplicarEstado, 150);
    window.addEventListener('resize', aplicarEstadoDebounced);

    if (toggleBtn) {
        toggleBtn.addEventListener('click', () => {
            const isOpen = filtrosRow.classList.toggle('is-open');
            toggleBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
            toggleBtn.classList.toggle('is-active', isOpen);
        });
    }
}

function reforcarEstruturaModalAluno() {
    const modal = document.getElementById('modalAluno');
    if (!modal) return;

    const formAluno = modal.querySelector('#formAluno');
    if (!formAluno) return;

    const modalBody = formAluno.querySelector('.aluno-modal-body') || formAluno.querySelector('.modal-body');
    if (modalBody) {
        modalBody.classList.add('aluno-modal-body');
        modalBody.style.minHeight = '0';
    }

    let painel = formAluno.querySelector('.aluno-modal-panel');
    if (painel) {
        painel.classList.add('aluno-modal-panel');
    }

    const tabContent = formAluno.querySelector('#alunoTabsContent');

    if (tabContent) {
        if (!painel) {
            painel = document.createElement('div');
            painel.className = 'aluno-modal-panel';
            const parent = tabContent.parentNode;
            parent.insertBefore(painel, tabContent);
        }

        if (!painel.contains(tabContent)) {
            painel.appendChild(tabContent);
        }
    }

    normalizarEstruturaAbasModalAluno();
}

function normalizarEstruturaAbasModalAluno() {
    const modal = document.getElementById('modalAluno');
    if (!modal) return;

    const panes = modal.querySelectorAll('#alunoTabsContent .tab-pane');
    panes.forEach((pane) => {
        if (!pane) return;

        let inner = pane.querySelector('.aluno-tab-pane-inner');
        if (!inner) {
            inner = document.createElement('div');
            inner.className = 'aluno-tab-pane-inner';
            while (pane.firstChild) {
                inner.appendChild(pane.firstChild);
            }
            pane.appendChild(inner);
        }
    });
}

// Função para inicializar controles do modal
function inicializarModalAluno() {
    reforcarEstruturaModalAluno();

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
    logModalAluno('🚀 Abrindo modal para edição...');
    const modal = document.getElementById('modalAluno');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        return;
    }
    
    // Garantir que o modal está no body
    if (modal.parentNode !== document.body) {
        document.body.appendChild(modal);
        logModalAluno('📦 modalAluno realocado diretamente no body.');
    }

    // Limpar qualquer estado anterior que possa interferir
    modal.classList.add('custom-modal');
    
    // FORÇAR abertura do modal para edição - garantir que está visível
    modal.style.removeProperty('display');
    modal.style.removeProperty('visibility');
    modal.style.setProperty('display', 'flex', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('align-items', 'center', 'important');
    modal.style.setProperty('justify-content', 'center', 'important');
    
    // Marcar como aberto
    modal.setAttribute('data-opened', 'true');
    
    // Prevenir scroll do body
    document.body.style.overflow = 'hidden';
    
    // Reforçar estrutura do modal
    reforcarEstruturaModalAluno();

    const dialog = modal.querySelector('.custom-modal-dialog');
    if (dialog) {
        ['left', 'right', 'transform', 'margin', 'position', 'width', 'max-width', 'display', 'justify-content', 'align-items'].forEach(prop => dialog.style.removeProperty(prop));
        dialog.style.setProperty('width', 'min(95vw, 1080px)', 'important');
        dialog.style.setProperty('max-width', '1080px', 'important');
    }

    requestAnimationFrame(() => {
        const overlayRect = modal.getBoundingClientRect();
        const dialogRect = dialog ? dialog.getBoundingClientRect() : null;
        const gapLeft = dialogRect ? Math.round(dialogRect.left - overlayRect.left) : null;
        const gapRight = dialogRect ? Math.round(overlayRect.right - dialogRect.right) : null;
        logModalAluno('[modalAluno debug] overlay:', overlayRect);
        logModalAluno('[modalAluno debug] dialog :', dialogRect);
        logModalAluno('[modalAluno debug] gaps   -> esquerda:', gapLeft, 'direita:', gapRight);
    });
    
    // Configurar para edição
    const acaoAluno = document.getElementById('acaoAluno');
    if (acaoAluno) {
        acaoAluno.value = 'editar';
        logModalAluno('✅ Campo acaoAluno definido como: editar');
    }
    
    const modalTitle = document.getElementById('modalTitle');
    if (modalTitle) {
        modalTitle.innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Aluno';
    }
    
    logModalAluno('🔍 Modal aberto - Editando? true');
    logModalAluno('📝 Formulário mantido para edição');
}
window.editarAluno = function(id) {
    logModalAluno('🚀 editarAluno chamada com ID:', id);
    
    // Garantir que o modal anterior está completamente fechado antes de abrir novamente
    const modalAnterior = document.getElementById('modalAluno');
    if (modalAnterior) {
        const modalDisplay = window.getComputedStyle(modalAnterior).display;
        const dataOpened = modalAnterior.getAttribute('data-opened');
        
        // Se o modal ainda está aberto ou em estado inconsistente, forçar fechamento
        if (modalDisplay !== 'none' || dataOpened === 'true') {
            logModalAluno('⚠️ Modal ainda aberto ou em estado inconsistente, forçando fechamento...');
            fecharModalAluno();
            
            // Aguardar um pouco para garantir que o fechamento foi processado
            setTimeout(() => {
                executarEdicaoAluno(id);
            }, 100);
            return;
        }
    }
    
    executarEdicaoAluno(id);
}

function executarEdicaoAluno(id) {
    logModalAluno('🚀 executarEdicaoAluno chamada com ID:', id);
    
    // Preencher contexto do aluno atual
    contextoAlunoAtual.alunoId = id;
    contextoAlunoAtual.matriculaId = null;
    contextoAlunoAtual.turmaTeoricaId = null;
    
    // Verificar se os elementos necessários existem
    const modalElement = document.getElementById('modalAluno');
    const modalTitle = document.getElementById('modalTitle');
    const acaoAluno = document.getElementById('acaoAluno');
    const alunoId = document.getElementById('aluno_id_hidden');
    
    logModalAluno('🔍 Verificando elementos do DOM:');
    logModalAluno('  modalAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    logModalAluno('  modalTitle:', modalTitle ? '✅ Existe' : '❌ Não existe');
    logModalAluno('  acaoAluno:', acaoAluno ? '✅ Existe' : '❌ Não existe');
    logModalAluno('  aluno_id:', alunoId ? '✅ Existe' : '❌ Não existe');
    
    if (!modalElement) {
        console.error('❌ Modal não encontrado!');
        alert('ERRO: Modal não encontrado na página!');
        return;
    }
    
    logModalAluno(`📡 Fazendo requisição para api/alunos.php?id=${id}`);
    logModalAluno(`📡 URL completa: ${API_CONFIG.getRelativeApiUrl('ALUNOS')}?id=${id}`);
    
    // Buscar dados do aluno (usando nova API funcional)
    const timestamp = new Date().getTime();
    const url = API_CONFIG.getRelativeApiUrl('ALUNOS') + `?id=${id}&t=${timestamp}`;
    logModalAluno(`📡 URL final da requisição: ${url}`);
    
    fetch(url)
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
            logModalAluno('📄 Dados do aluno:', data.aluno);
            logModalAluno('📄 Naturalidade do aluno:', data.aluno?.naturalidade);
            logModalAluno('📄 Todos os campos do aluno:', Object.keys(data.aluno || {}));
            logModalAluno('📄 Estrutura completa do aluno:', JSON.stringify(data.aluno, null, 2));
            
            if (data.success) {
                logModalAluno('✅ Success = true, configurando modal...');
                
                // Configurar modal PRIMEIRO
                if (modalTitle) modalTitle.textContent = 'Editar Aluno';
                if (acaoAluno) {
                    acaoAluno.value = 'editar';
                    logModalAluno('✅ Campo acaoAluno definido como: editar');
                }
                if (alunoId) {
                    alunoId.value = id;
                    logModalAluno('✅ Campo aluno_id definido como:', id);
                }
                
                // Abrir modal customizado para edição
                abrirModalEdicao();
                logModalAluno('🪟 Modal de edição aberto!');
                
                // Função melhorada para garantir que o modal esteja totalmente carregado
                function esperarModalPronto() {
                    return new Promise((resolve) => {
                        const checkModal = () => {
                            const modal = document.getElementById('modalAluno');
                            const form = document.getElementById('formAluno');
                            const estadoSelect = document.getElementById('naturalidade_estado');
                            const observacoesField = document.getElementById('observacoes');
                            
                            const modalVisible = modal ? window.getComputedStyle(modal).display !== 'none' : false;
                            if (modal && modalVisible && 
                                form && estadoSelect && observacoesField) {
                                logModalAluno('✅ Modal totalmente carregado e pronto (incluindo campo observacoes)');
                                resolve();
                            } else {
                                logModalAluno('⏳ Aguardando modal carregar...', {
                                    modalVisible,
                                    formExists: !!form,
                                    observacoesExists: !!observacoesField,
                                    estadoExists: !!estadoSelect
                                });
                                setTimeout(checkModal, 50);
                            }
                        };
                        checkModal();
                    });
                }
                
                // Aguardar modal estar pronto, então preencher
                esperarModalPronto().then(() => {
                    logModalAluno('🔄 Callando preencherFormularioAluno com dados:', data.aluno);
                    logModalAluno('🔄 Naturalidade disponível:', data.aluno.naturalidade);
                    logModalAluno('🔄 Timestamp:', new Date().toISOString());
                    
                    // Log específico para LGPD e Observações antes de preencher
                    console.log('🔍 DEBUG - Aluno carregado para edição:', {
                        id: data.aluno.id,
                        nome: data.aluno.nome,
                        lgpd_consentimento: data.aluno.lgpd_consentimento,
                        lgpd_consentimento_em: data.aluno.lgpd_consentimento_em,
                        observacoes: data.aluno.observacoes,
                        observacoes_length: data.aluno.observacoes ? data.aluno.observacoes.length : 0
                    });
                    
                    preencherFormularioAluno(data.aluno);
                    logModalAluno('✅ Formulário preenchido - função executada');
                    
                // Carregar resumo financeiro do aluno (para todas as abas que precisam)
                atualizarResumoFinanceiroAluno(id, null);
                
                // Se a API GET de alunos retornou dados da matrícula, usar como fallback
                // antes de carregar da API GET de matrículas
                const dadosMatriculaDoAluno = {
                    aulas_praticas_contratadas: data.aluno.aulas_praticas_contratadas,
                    aulas_praticas_extras: data.aluno.aulas_praticas_extras,
                    // Campos financeiros removidos - forma_pagamento, status_pagamento, valor_total
                    // Agora os dados financeiros vêm apenas do módulo Financeiro do Aluno
                    previsao_conclusao: data.aluno.previsao_conclusao,
                    processo_numero: data.aluno.numero_processo,
                    processo_numero_detran: data.aluno.detran_numero,
                    processo_situacao: data.aluno.processo_situacao,
                    renach: data.aluno.renach_matricula ?? data.aluno.renach,
                    status: data.aluno.status_matricula,
                    data_inicio: data.aluno.data_matricula,
                    data_fim: data.aluno.data_conclusao,
                    categoria_cnh: data.aluno.categoria_cnh,
                    tipo_servico: data.aluno.tipo_servico
                };
                
                // Log dos dados da matrícula vindos da API GET de alunos
                console.log('[DEBUG MATRICULA] Dados da matrícula vindos da API GET de alunos:', dadosMatriculaDoAluno);
                
                // Carregar matrícula principal após preencher formulário
                setTimeout(() => {
                    carregarMatriculaPrincipal(id, dadosMatriculaDoAluno);
                    
                    // Carregar histórico do aluno
                    setTimeout(() => {
                        carregarHistoricoAluno(id);
                    }, 600);
                }, 300);
                    
                    // Aplicar validação automática após preenchimento
                    setTimeout(() => {
                        validarCampoCPFAutomaticamente();
                    }, 500);
                    
                    // Aplicar validação também após 1 segundo para garantir
                    setTimeout(() => {
                        aplicarValidacaoCPFFormulario();
                    }, 1000);
                });
                
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
    logModalAluno('📝 Preenchendo formulário para aluno:', aluno);
    logModalAluno('📝 Dados específicos do aluno:');
    logModalAluno('  - ID:', aluno.id);
    logModalAluno('  - Nome:', aluno.nome);
    logModalAluno('  - CPF:', aluno.cpf);
    logModalAluno('  - Email:', aluno.email);
    logModalAluno('  - Telefone:', aluno.telefone);
    logModalAluno('  - CFC ID:', aluno.cfc_id);
    logModalAluno('  - Naturalidade:', aluno.naturalidade);
    logModalAluno('  - Nacionalidade:', aluno.nacionalidade);
    
    
    // Verificar se o modal está aberto
    const modal = document.getElementById('modalAluno');
    const modalDisplay = modal ? window.getComputedStyle(modal).display : 'none';
    logModalAluno('🔍 Modal status:', modal ? (modalDisplay !== 'none' ? '✅ Aberto' : '❌ Fechado') : '❌ Não encontrado');
    
    // Definir ID do aluno para edição
    const alunoIdField = document.getElementById('aluno_id_hidden');
    if (alunoIdField) alunoIdField.value = aluno.id || '';
    
    // Preencher campos básicos com verificações de segurança
    const campos = {
        'nome': aluno.nome || '',
        'cpf': aluno.cpf || '',
        'rg': aluno.rg || '',
        'rg_orgao_emissor': aluno.rg_orgao_emissor || '',
        'rg_uf': aluno.rg_uf || '',
        'rg_data_emissao': (() => {
            // Tratar rg_data_emissao: converter "0000-00-00" para vazio
            const valorAPI = (aluno.rg_data_emissao || '').trim();
            if (valorAPI === '' || valorAPI === '0000-00-00' || valorAPI === '0000-00-00 00:00:00') {
                return ''; // Data inválida ou vazia
            }
            // Se já está em formato YYYY-MM-DD, retornar direto
            if (typeof valorAPI === 'string' && /^\d{4}-\d{2}-\d{2}/.test(valorAPI)) {
                return valorAPI.split(' ')[0]; // Pegar apenas a parte da data (ignorar hora se houver)
            }
            return valorAPI;
        })(),
        'renach': aluno.renach || '',
        'data_nascimento': (() => {
            // Tratar data de nascimento: converter para formato YYYY-MM-DD se válida
            const data = aluno.data_nascimento;
            if (!data || data === '0000-00-00' || data === '0000-00-00 00:00:00') {
                return ''; // Data inválida ou vazia
            }
            // Se já está em formato YYYY-MM-DD, retornar direto
            if (typeof data === 'string' && /^\d{4}-\d{2}-\d{2}/.test(data)) {
                return data.split(' ')[0]; // Pegar apenas a parte da data (ignorar hora se houver)
            }
            // Tentar converter de outros formatos
            try {
                const dataObj = new Date(data);
                if (!isNaN(dataObj.getTime())) {
                    const ano = dataObj.getFullYear();
                    const mes = String(dataObj.getMonth() + 1).padStart(2, '0');
                    const dia = String(dataObj.getDate()).padStart(2, '0');
                    return `${ano}-${mes}-${dia}`;
                }
            } catch (e) {
                console.warn('⚠️ Erro ao converter data_nascimento:', e);
            }
            return ''; // Fallback: retornar vazio
        })(),
        'estado_civil': aluno.estado_civil || '',
        'profissao': aluno.profissao || '',
        'escolaridade': aluno.escolaridade || '',
        'naturalidade': aluno.naturalidade || '',
        'naturalidade_estado': extrairEstadoNaturalidade(aluno.naturalidade),
        'naturalidade_municipio': extrairMunicipioNaturalidade(aluno.naturalidade),
        'nacionalidade': aluno.nacionalidade || '',
        'email': aluno.email || '',
        'telefone': aluno.telefone || '',
        'telefone_secundario': aluno.telefone_secundario || '',
        'contato_emergencia_nome': aluno.contato_emergencia_nome || '',
        'contato_emergencia_telefone': aluno.contato_emergencia_telefone || '',
        'cfc_id': aluno.cfc_id || '',
        'status': aluno.status || 'ativo',
        'atividade_remunerada': aluno.atividade_remunerada || 0,
        'observacoes': aluno.observacoes || ''
    };
    
    // Carregar foto existente se houver
    console.log('📷 Verificando foto do aluno:', aluno.foto);
    if (aluno.foto) {
        console.log('📷 Carregando foto existente:', aluno.foto);
        carregarFotoExistenteAluno(aluno.foto);
    } else {
        console.log('ℹ️ Nenhuma foto encontrada para o aluno');
        // Garantir que o placeholder está visível
        const container = document.getElementById('preview-container-aluno');
        const placeholder = document.getElementById('placeholder-foto-aluno');
        if (container) container.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
    }
    
    console.log('📝 Campos a serem preenchidos:', campos);
    
    // Preencher cada campo se ele existir (exceto naturalidade que será tratada separadamente)
    console.log('🔍 Verificando elementos do formulário...');
    logModalAluno('🔍 Modal visível?', document.getElementById('modalAluno')?.style.display);
    logModalAluno('🔍 Formulário existe?', document.getElementById('formAluno') ? 'Sim' : 'Não');
    
    Object.keys(campos).forEach(campoId => {
        // Pular campos de naturalidade que serão tratados separadamente
        if (campoId === 'naturalidade_estado' || campoId === 'naturalidade_municipio') {
            console.log(`⏭️ Pulando campo ${campoId} - será tratado separadamente`);
            return;
        }
        
        const elemento = document.getElementById(campoId);
        console.log(`🔍 Campo ${campoId}:`, elemento ? '✅ Existe' : '❌ Não existe');
        if (elemento) {
            const valorAnterior = elemento.value;
            
            // Tratamento especial para selects (estado_civil, escolaridade, rg_uf)
            if (elemento.tagName === 'SELECT') {
                elemento.value = campos[campoId];
                // Se o valor não foi definido (não existe nas opções), manter o primeiro (placeholder)
                if (elemento.value !== campos[campoId] && campos[campoId]) {
                    console.warn(`⚠️ Valor "${campos[campoId]}" não encontrado nas opções do select ${campoId}`);
                }
            } else {
                elemento.value = campos[campoId];
            }
            
            console.log(`✅ Campo ${campoId}:`);
            console.log(`  - Valor anterior: "${valorAnterior}"`);
            console.log(`  - Valor novo: "${campos[campoId]}"`);
            console.log(`  - Valor atual: "${elemento.value}"`);
            
            // Verificar se o valor foi realmente definido (comparação mais robusta)
            // Tratamento especial para data_nascimento: não bloquear outros campos se houver problema
            if (String(elemento.value).trim() !== String(campos[campoId]).trim()) {
                if (campoId === 'data_nascimento') {
                    // Para data_nascimento, apenas avisar mas não bloquear
                    const valorBruto = aluno.data_nascimento;
                    if (valorBruto && valorBruto !== '0000-00-00' && valorBruto !== '0000-00-00 00:00:00') {
                        console.warn(`⚠️ AVISO: Campo ${campoId} em formato inesperado`, {
                            esperado: campos[campoId],
                            atual: elemento.value,
                            valorBruto: valorBruto
                        });
                    } else {
                        console.log(`ℹ️ Campo ${campoId} vazio ou inválido no banco - deixando vazio`);
                    }
                } else {
                    console.error(`❌ ERRO: Campo ${campoId} não foi preenchido corretamente!`);
                    console.error(`  - Esperado: "${campos[campoId]}"`);
                    console.error(`  - Atual: "${elemento.value}"`);
                }
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
    
    // Tratamento especial para LGPD
    console.log('🔒 Verificando LGPD do aluno:', {
        lgpd_consentimento: aluno.lgpd_consentimento,
        lgpd_consentimento_em: aluno.lgpd_consentimento_em,
        tipo_lgpd_consentimento: typeof aluno.lgpd_consentimento,
        tipo_lgpd_consentimento_em: typeof aluno.lgpd_consentimento_em,
        lgpd_consentimento_undefined: aluno.lgpd_consentimento === undefined,
        lgpd_consentimento_null: aluno.lgpd_consentimento === null
    });
    
    // Checkbox LGPD
    const lgpdCheckbox = document.getElementById('lgpd_consentimento');
    if (lgpdCheckbox) {
        // Verificar se o valor existe e converter para boolean
        // Aceitar: 1, '1', true, ou qualquer valor truthy quando convertido
        const lgpdValue = aluno.lgpd_consentimento !== undefined && 
                         aluno.lgpd_consentimento !== null &&
                         (aluno.lgpd_consentimento == 1 || 
                          aluno.lgpd_consentimento === '1' || 
                          aluno.lgpd_consentimento === true || 
                          aluno.lgpd_consentimento === 1);
        lgpdCheckbox.checked = lgpdValue;
        console.log(`✅ Checkbox lgpd_consentimento:`, {
            valorBruto: aluno.lgpd_consentimento,
            valorConvertido: lgpdValue,
            checked: lgpdCheckbox.checked,
            elementoEncontrado: true
        });
    } else {
        console.error('❌ Checkbox lgpd_consentimento não encontrado no DOM');
    }
    
    // Data/Hora do Consentimento LGPD
    const lgpdConsentimentoEm = document.getElementById('lgpd_consentimento_em');
    if (lgpdConsentimentoEm) {
        // Verificar se há data válida (não vazia, não null, não data inválida do MySQL)
        if (aluno.lgpd_consentimento_em && 
            String(aluno.lgpd_consentimento_em).trim() !== '' && 
            aluno.lgpd_consentimento_em !== '0000-00-00 00:00:00' && 
            aluno.lgpd_consentimento_em !== '0000-00-00' &&
            aluno.lgpd_consentimento_em !== null) {
            // Formatar data/hora para exibição (dd/mm/aaaa hh:mm)
            try {
                const dataConsentimento = new Date(aluno.lgpd_consentimento_em);
                if (!isNaN(dataConsentimento.getTime())) {
                    const dia = String(dataConsentimento.getDate()).padStart(2, '0');
                    const mes = String(dataConsentimento.getMonth() + 1).padStart(2, '0');
                    const ano = dataConsentimento.getFullYear();
                    const hora = String(dataConsentimento.getHours()).padStart(2, '0');
                    const minuto = String(dataConsentimento.getMinutes()).padStart(2, '0');
                    lgpdConsentimentoEm.value = `${dia}/${mes}/${ano} ${hora}:${minuto}`;
                    console.log(`✅ Campo lgpd_consentimento_em preenchido:`, lgpdConsentimentoEm.value);
                } else {
                    console.warn(`⚠️ Data de consentimento LGPD inválida (não é uma data válida):`, aluno.lgpd_consentimento_em);
                    lgpdConsentimentoEm.value = '';
                }
            } catch (e) {
                console.warn(`⚠️ Erro ao formatar data de consentimento LGPD:`, e, aluno.lgpd_consentimento_em);
                lgpdConsentimentoEm.value = '';
            }
        } else {
            // Se não houver data, deixar vazio (placeholder será exibido)
            lgpdConsentimentoEm.value = '';
            console.log(`ℹ️ Campo lgpd_consentimento_em vazio - não há data de consentimento salva ou data inválida`, {
                valorBruto: aluno.lgpd_consentimento_em,
                isNull: aluno.lgpd_consentimento_em === null,
                isUndefined: aluno.lgpd_consentimento_em === undefined
            });
        }
    } else {
        console.error('❌ Campo lgpd_consentimento_em não encontrado no DOM');
    }
    
    // Preencher Observações
    const observacoesField = document.getElementById('observacoes');
    if (observacoesField) {
        const valorObservacoes = (aluno.observacoes !== undefined && aluno.observacoes !== null)
            ? String(aluno.observacoes)
            : '';
        
        observacoesField.value = valorObservacoes;
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
        
        // Preencher campos de naturalidade
        console.log('🔍 Dados de naturalidade recebidos:', {
            'aluno.naturalidade': aluno.naturalidade
        });
        
        const estadoNaturalidade = extrairEstadoNaturalidade(aluno.naturalidade || '');
        const municipioNaturalidade = extrairMunicipioNaturalidade(aluno.naturalidade || '');
        
        console.log('🔍 Dados extraídos:', {
            'estadoNaturalidade': estadoNaturalidade,
            'municipioNaturalidade': municipioNaturalidade,
            'naturalidade_completa': aluno.naturalidade || ''
        });
        console.log('🔍 Verificando se estado é PE:', estadoNaturalidade === 'PE');
        console.log('🔍 Verificando se município é Bom Conselho:', municipioNaturalidade === 'Bom Conselho');
        
        if (estadoNaturalidade) {
            console.log('🔄 Carregando naturalidade - Estado:', estadoNaturalidade, 'Município:', municipioNaturalidade);
            
            const estadoSelect = document.getElementById('naturalidade_estado');
            if (!estadoSelect) {
                console.error('❌ Campo naturalidade_estado não encontrado');
                return;
            }
            
            estadoSelect.value = estadoNaturalidade;
            
            // Usar Promise melhorada com tratamento de erro
            carregarMunicipios(estadoNaturalidade)
                .then(() => {
                console.log('✅ Municípios carregados, definindo valor:', municipioNaturalidade);
                
                // Sempre tentar definir o município se ele existir
                if (municipioNaturalidade) {
                    // Aguardar um pouco mais para garantir que o select foi populado
                    setTimeout(() => {
                        const municipioSelect = document.getElementById('naturalidade_municipio');
                        console.log('🔍 Tentando definir município:', municipioNaturalidade);
                        console.log('🔍 Opções disponíveis antes:', Array.from(municipioSelect.options).map(o => o.value));
                        console.log('🔍 Total de opções:', municipioSelect.options.length);
                        console.log('🔍 Primeiras 10 opções:', Array.from(municipioSelect.options).slice(0, 10).map(o => o.value));
                        
                        // Verificar se o município existe nas opções
                        const opcoes = Array.from(municipioSelect.options).map(o => o.value);
                        const municipioExiste = opcoes.includes(municipioNaturalidade);
                        console.log('🔍 Município existe nas opções?', municipioExiste);
                        console.log('🔍 Município procurado:', municipioNaturalidade);
                        console.log('🔍 Tipo do município procurado:', typeof municipioNaturalidade);
                        console.log('🔍 Tamanho do município procurado:', municipioNaturalidade.length);
                        
                        if (municipioExiste) {
                            console.log('🔍 Definindo valor do select...');
                            municipioSelect.value = municipioNaturalidade;
                            console.log('✅ Valor do município definido:', municipioSelect.value);
                            console.log('🔍 Verificando se foi definido corretamente...');
                            console.log('🔍 Valor atual do select:', municipioSelect.value);
                            console.log('🔍 Valor esperado:', municipioNaturalidade);
                            console.log('🔍 São iguais?', municipioSelect.value === municipioNaturalidade);
                        } else {
                            console.error('❌ Município não encontrado nas opções:', municipioNaturalidade);
                            console.log('🔍 Opções disponíveis:', opcoes);
                            console.log('🔍 Buscando correspondência exata...');
                            
                            // Tentar encontrar correspondência exata (case insensitive)
                            const municipioEncontrado = opcoes.find(opcao => 
                                opcao.toLowerCase() === municipioNaturalidade.toLowerCase()
                            );
                            
                            if (municipioEncontrado) {
                                console.log('✅ Município encontrado (case insensitive):', municipioEncontrado);
                                municipioSelect.value = municipioEncontrado;
                            } else {
                                console.log('🔍 Buscando correspondência parcial...');
                                const municipioParcial = opcoes.find(opcao => 
                                    opcao.toLowerCase().includes(municipioNaturalidade.toLowerCase()) ||
                                    municipioNaturalidade.toLowerCase().includes(opcao.toLowerCase())
                                );
                                
                                if (municipioParcial) {
                                    console.log('✅ Município encontrado (parcial):', municipioParcial);
                                    municipioSelect.value = municipioParcial;
                                } else {
                                    console.error('❌ Nenhuma correspondência encontrada para:', municipioNaturalidade);
                                }
                            }
                        }
                        
                        // Atualizar naturalidade após definir o município
                        console.log('🔍 Chamando atualizarNaturalidade...');
                        atualizarNaturalidade();
                        console.log('🔍 atualizarNaturalidade executada');
                        
                        // Validar se o campo foi preenchido corretamente
                        setTimeout(() => {
                            const valorAtual = municipioSelect.value;
                            if (String(valorAtual).trim() !== String(municipioNaturalidade).trim()) {
                                console.error(`❌ ERRO: Campo naturalidade_municipio não foi preenchido corretamente!`);
                                console.error(`  - Esperado: "${municipioNaturalidade}"`);
                                console.error(`  - Atual: "${valorAtual}"`);
                            } else {
                                console.log(`✅ Campo naturalidade_municipio preenchido corretamente`);
                            }
                        }, 100);
                    }, 50);
                } else {
                    // Não há município salvo, mas há estado selecionado
                    // Os municípios já foram carregados pelo carregarMunicipios()
                    // O dropdown está pronto para seleção manual
                    console.log('ℹ️ Não há município salvo, dropdown pronto para seleção manual');
                    console.log('ℹ️ Municípios disponíveis:', Array.from(document.getElementById('naturalidade_municipio').options).length);
                }
            })
                .catch(error => {
                    console.error('❌ Erro ao carregar municípios:', error);
                    console.warn('⚠️ Tentando novamente em 500ms...');
                    
                    // Tentar novamente após um delay maior
                    setTimeout(() => {
                        carregarMunicipios(estadoNaturalidade)
                            .then(() => {
                                if (municipioNaturalidade) {
                                    const municipioSelect = document.getElementById('naturalidade_municipio');
                                    municipioSelect.value = municipioNaturalidade;
                                    atualizarNaturalidade();
                                    console.log('✅ Município definido na segunda tentativa');
                                }
                            })
                            .catch(err => {
                                console.error('❌ Falha na segunda tentativa:', err);
                                mostrarAlerta('Erro ao carregar municípios. Recarregue a página.', 'warning');
                            });
                    }, 500);
                });
        } else {
            // Não há estado extraído da naturalidade
            console.log('ℹ️ Nenhum estado extraído da naturalidade');
            
            // Verificar se há estado definido no campo visualmente
            const estadoVisual = document.getElementById('naturalidade_estado')?.value || '';
            console.log('🔍 Estado visual no formulário:', estadoVisual);
            
            if (estadoVisual) {
                console.log('🔄 Estado encontrado visualmente, carregando municípios...');
                carregarMunicipios(estadoVisual).then(() => {
                    console.log('✅ Municípios carregados para estado visual:', estadoVisual);
                });
            }
        }
        
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
    
    // Observações já foi preenchido acima no tratamento especial após o loop de campos
}
window.visualizarAluno = function(id) {
    console.log('🚀 visualizandoAluno chamada com ID:', id);

    // Preencher contexto do aluno atual
    contextoAlunoAtual.alunoId = id;
    contextoAlunoAtual.matriculaId = null;
    contextoAlunoAtual.turmaTeoricaId = null;

    // GARANTIR que nenhum outro modal está aberto
    console.log('🔍 Verificando e fechando modais conflitantes...');
    const modalAlunoParaVisualizacao = document.getElementById('modalAluno');
    if (modalAlunoParaVisualizacao && modalAlunoParaVisualizacao.style.display !== 'none') {
        console.log('⚠️ Forçando fechamento do modalAluno conflitante...');
        modalAlunoParaVisualizacao.style.setProperty('display', 'none', 'important');
        modalAlunoParaVisualizacao.style.setProperty('visibility', 'hidden', 'important');
        modalAlunoParaVisualizacao.removeAttribute('data-opened');
    }

    // CORRIGIDO: Garantir que modal de visualização anterior está fechado
    const modalVisualizarAnterior = document.getElementById('modalVisualizarAluno');
    if (modalVisualizarAnterior) {
        modalVisualizarAnterior.classList.remove('is-open');
        modalVisualizarAnterior.dataset.opened = 'false';
        modalVisualizarAnterior.style.display = 'none';
    }

    const modalElement = document.getElementById('modalVisualizarAluno');
    const modalBody = document.getElementById('modalVisualizarAlunoBody');

    console.log('🔍 Verificando elementos do DOM:');
    logModalAluno('  modalVisualizarAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    logModalAluno('  modalVisualizarAlunoBody:', modalBody ? '✅ Existe' : '❌ Não existe');

    if (!modalElement || !modalBody) {
        console.error('❌ Modal de visualização não encontrado!');
        alert('ERRO: Modal de visualização não encontrado na página!');
        return;
    }

    modalBody.innerHTML = `
        <div class="visualizar-loading">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p>Carregando informações do aluno...</p>
        </div>
    `;

    // CORRIGIDO: Abrir modal ANTES de fazer a requisição para garantir que aparece imediatamente
    abrirModalVisualizarAluno(id);
    
    // Garantir que o modal está visível após um pequeno delay (para casos de timing)
    setTimeout(() => {
        if (modalElement && !modalElement.classList.contains('is-open')) {
            console.log('⚠️ Modal não abriu, forçando abertura...');
            abrirModalVisualizarAluno(id);
        }
    }, 50);
    aplicarCorrecaoZIconsAction('open');

    const fecharModalVisualizacao = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        fecharModalVisualizarAluno();
    };

    const btnFecharTopo = modalElement.querySelector('.btn-close');
    const btnFecharRodape = modalElement.querySelector('.visualizar-modal-footer .btn-outline-secondary');

    if (btnFecharTopo) {
        btnFecharTopo.onclick = fecharModalVisualizacao;
    }
    if (btnFecharRodape) {
        btnFecharRodape.onclick = fecharModalVisualizacao;
    }

    console.log(`📡 Fazendo requisição para api/alunos.php?id=${id}`);

    const timestamp = new Date().getTime();
    fetch(API_CONFIG.getRelativeApiUrl('ALUNOS') + `?id=${id}&t=${timestamp}`)
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

                preencherModalVisualizacao(data.aluno);
                console.log('✅ Modal preenchido');

                // Carregar contador de documentos
                carregarContadorDocumentos(id);

                // Carregar resumo da matrícula principal após preencher modal
                setTimeout(() => {
                    carregarResumoMatriculaParaVisualizacao(id);
                    
                    // Carregar histórico do aluno (modo visualização)
                    setTimeout(() => {
                        carregarHistoricoAluno(id, { modoVisualizacao: true });
                    }, 600);
                    
                    // Registrar eventos dos atalhos após carregar dados
                    setTimeout(() => {
                        registrarEventosAtalhosAluno();
                    }, 500);
                }, 300);

                const btnFecharTopoAtualizado = modalElement.querySelector('.btn-close');
                const btnFecharRodapeAtualizado = modalElement.querySelector('.visualizar-modal-footer .btn-outline-secondary');

                if (btnFecharTopoAtualizado) {
                    btnFecharTopoAtualizado.onclick = fecharModalVisualizacao;
                }
                if (btnFecharRodapeAtualizado) {
                    btnFecharRodapeAtualizado.onclick = fecharModalVisualizacao;
                }

                modalElement.addEventListener('click', (event) => {
                    if (event.target === modalElement) {
                        fecharModalVisualizacao(event);
                    }
                }, { once: true });

            } else {
                console.error('❌ Success = false, erro:', data.error);
                modalBody.innerHTML = `<div class="visualizar-erro">Erro ao carregar dados do aluno: ${data.error || 'Erro desconhecido'}</div>`;
            }
        })
        .catch(error => {
            console.error('💥 Erro na requisição:', error);
            modalBody.innerHTML = `<div class="visualizar-erro">Erro ao carregar dados do aluno: ${error.message}</div>`;
        });
}

function preencherModalVisualizacao(aluno) {
    // Funções auxiliares para formatação
    const formatarDataHora = (dataHora) => {
        if (!dataHora) return '—';
        try {
            const data = new Date(dataHora);
            if (isNaN(data.getTime())) return '—';
            return data.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        } catch (e) {
            return '—';
        }
    };
    
    const formatarBadgeStatus = (valor, tipo = 'status') => {
        if (!valor || valor === '') return '<span class="badge bg-secondary">Não informado</span>';
        
        const valorLower = String(valor).toLowerCase();
        let badgeClass = 'bg-secondary';
        let texto = valor;
        
        if (tipo === 'status_matricula') {
            const map = {
                'ativa': { class: 'bg-success', texto: 'Ativa' },
                'em_analise': { class: 'bg-warning', texto: 'Em Análise' },
                'concluida': { class: 'bg-info', texto: 'Concluída' },
                'trancada': { class: 'bg-secondary', texto: 'Trancada' },
                'cancelada': { class: 'bg-danger', texto: 'Cancelada' }
            };
            const mapped = map[valorLower] || { class: 'bg-secondary', texto: valor };
            badgeClass = mapped.class;
            texto = mapped.texto;
        } else if (tipo === 'processo_situacao') {
            const map = {
                'em_analise': { class: 'bg-warning', texto: 'Em Análise' },
                'aprovado': { class: 'bg-success', texto: 'Aprovado' },
                'indeferido': { class: 'bg-danger', texto: 'Indeferido' },
                'nao_informado': { class: 'bg-secondary', texto: 'Não Informado' }
            };
            const mapped = map[valorLower] || { class: 'bg-secondary', texto: valor };
            badgeClass = mapped.class;
            texto = mapped.texto;
        } else if (tipo === 'status_pagamento') {
            const map = {
                'em_dia': { class: 'bg-success', texto: 'EM DIA' },
                'em_aberto': { class: 'bg-warning', texto: 'EM ABERTO' },
                'em_atraso': { class: 'bg-danger', texto: 'EM ATRASO' },
                'pendente': { class: 'bg-secondary', texto: 'PENDENTE' }
            };
            const mapped = map[valorLower] || { class: 'bg-secondary', texto: valor.toUpperCase() };
            badgeClass = mapped.class;
            texto = mapped.texto;
        }
        
        return `<span class="badge ${badgeClass}">${texto}</span>`;
    };
    
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
    
    const fotoUrl = (() => {
        if (!aluno.foto || aluno.foto === 'Array') return '';
        if (typeof aluno.foto === 'string' && aluno.foto.match(/^https?:\/\//i)) {
            return aluno.foto;
        }
        const basePathParts = window.location.pathname.split('/');
        const projectPath = basePathParts.slice(0, -2).join('/');
        const normalizedFoto = aluno.foto.replace(/^\/+/, '');
        return `${window.location.origin}${projectPath}/${normalizedFoto}`;
    })();

    // Função helper para obter categoria priorizando matrícula ativa
    function obterCategoriaExibicao(aluno) {
        // Prioridade 1: Categoria da matrícula ativa
        if (aluno.categoria_cnh_matricula) {
            return aluno.categoria_cnh_matricula;
        }
        // Prioridade 2: Categoria do aluno (fallback)
        if (aluno.categoria_cnh) {
            return aluno.categoria_cnh;
        }
        // Prioridade 3: Tentar extrair de operações
        if (aluno.operacoes) {
            try {
                const operacoes = typeof aluno.operacoes === 'string' ? JSON.parse(aluno.operacoes) : aluno.operacoes;
                if (Array.isArray(operacoes) && operacoes.length > 0) {
                    const primeiraOp = operacoes[0];
                    return primeiraOp.categoria || primeiraOp.categoria_cnh || 'N/A';
                }
            } catch (e) {
                // Ignorar erro de parse
            }
        }
        return 'N/A';
    }
    
    // Função helper para obter tipo de serviço priorizando matrícula ativa
    function obterTipoServicoExibicao(aluno) {
        // Prioridade 1: Tipo de serviço da matrícula ativa
        if (aluno.tipo_servico_matricula) {
            return aluno.tipo_servico_matricula;
        }
        // Prioridade 2: Tipo de serviço do aluno (fallback)
        if (aluno.tipo_servico) {
            return aluno.tipo_servico;
        }
        // Prioridade 3: Tentar extrair de operações
        if (aluno.operacoes) {
            try {
                const operacoes = typeof aluno.operacoes === 'string' ? JSON.parse(aluno.operacoes) : aluno.operacoes;
                if (Array.isArray(operacoes) && operacoes.length > 0) {
                    const primeiraOp = operacoes[0];
                    return primeiraOp.tipo_servico || primeiraOp.tipo || 'Primeira Habilitação';
                }
            } catch (e) {
                // Ignorar erro de parse
            }
        }
        return 'Primeira Habilitação';
    }
    
    // Obter categoria e tipo de serviço usando as funções helper
    const categoriaExibicao = obterCategoriaExibicao(aluno);
    const tipoServicoExibicao = obterTipoServicoExibicao(aluno);
    
    // Formatar texto do tipo de serviço
    // Mapear tipo_servico para texto amigável
    const tipoServicoMap = {
        'primeira_habilitacao': 'Primeira Habilitação',
        'adicao': 'Adição de Categoria',
        'mudanca': 'Mudança de Categoria',
        'renovacao': 'Renovação',
        'reciclagem': 'Reciclagem'
    };
    
    const tipoServicoTextoFormatado = tipoServicoMap[tipoServicoExibicao] || tipoServicoExibicao;
    
    // Formatar categoria (ex: AB -> "A + B", B -> "B")
    let categoriaFormatada = categoriaExibicao;
    if (categoriaExibicao === 'AB') {
        categoriaFormatada = 'A + B';
    } else if (categoriaExibicao === 'AC') {
        categoriaFormatada = 'A + C';
    } else if (categoriaExibicao === 'AD') {
        categoriaFormatada = 'A + D';
    } else if (categoriaExibicao === 'AE') {
        categoriaFormatada = 'A + E';
    }
    
    // Montar texto final
    let tipoServicoTexto = `${tipoServicoTextoFormatado} ${categoriaFormatada}`;

    const html = `
        <!-- VISUALIZAR ALUNO: Header com dados essenciais -->
        <div class="row mb-4 pb-3 border-bottom">
            <div class="col-12">
                <div class="d-flex align-items-center">
                    ${fotoUrl && fotoUrl.trim() !== '' ? `
                        <img src="${fotoUrl.replace(/^\/+/, '')}" 
                             alt="Foto do aluno" class="rounded-circle me-3" 
                             style="width: 80px; height: 80px; object-fit: cover; border: 3px solid #dee2e6;">
                    ` : `
                        <div class="rounded-circle me-3 d-flex align-items-center justify-content-center bg-light" 
                             style="width: 80px; height: 80px; border: 3px solid #dee2e6;">
                            <i class="fas fa-user fa-2x text-muted"></i>
                        </div>
                    `}
                    <div class="flex-grow-1">
                        <h4 class="mb-1">${aluno.nome || 'Nome não informado'}</h4>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="text-muted">CPF: ${aluno.cpf || 'Não informado'}</span>
                            <span class="badge bg-${aluno.status === 'ativo' ? 'success' : (aluno.status === 'concluido' ? 'info' : 'secondary')}">
                                ${aluno.status === 'ativo' ? 'Ativo' : (aluno.status === 'concluido' ? 'Concluído' : 'Inativo')}
                            </span>
                        </div>
                        <small class="text-muted">${tipoServicoTexto}</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- VISUALIZAR ALUNO: Corpo dividido em 2 colunas -->
        <div class="row">
            <!-- VISUALIZAR ALUNO: Coluna Esquerda - Dados do Aluno -->
            <div class="col-md-6">
                <!-- Documento e Processo -->
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-id-card me-1"></i>Documento e Processo
                    </h6>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>RG:</strong> ${aluno.rg || 'Não informado'}${aluno.rg_orgao_emissor ? ` / ${aluno.rg_orgao_emissor}` : ''}${aluno.rg_uf ? ` ${aluno.rg_uf}` : ''}</p>
                    ${aluno.rg_data_emissao ? `<p class="mb-1" style="font-size: 0.85rem; color: #6c757d;">Data de Emissão: ${new Date(aluno.rg_data_emissao).toLocaleDateString('pt-BR')}</p>` : ''}
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>RENACH:</strong> ${(aluno.renach_matricula || aluno.renach) || 'Não informado'}</p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Número do Processo:</strong> ${aluno.numero_processo || 'Não informado'}</p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Número DETRAN / Protocolo:</strong> ${aluno.detran_numero || 'Não informado'}</p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Status da Matrícula:</strong> ${formatarBadgeStatus(aluno.status_matricula, 'status_matricula')}</p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Situação do Processo:</strong> ${formatarBadgeStatus(aluno.processo_situacao, 'processo_situacao')}</p>
                    <p class="mb-1" style="font-size: 0.9rem;">
                        <strong>Documentos anexados:</strong> 
                        <span id="contador-documentos-${aluno.id}" class="badge bg-info">
                            <i class="fas fa-spinner fa-spin me-1"></i>Carregando...
                        </span>
                    </p>
                </div>

                <!-- Dados Pessoais -->
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-user me-1"></i>Dados Pessoais
                    </h6>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Data de Nascimento:</strong> ${aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado'}</p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Naturalidade:</strong> ${aluno.naturalidade || 'Não informado'}</p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Nacionalidade:</strong> ${aluno.nacionalidade || 'Não informado'}</p>
                    ${aluno.estado_civil ? `<p class="mb-1" style="font-size: 0.9rem;"><strong>Estado Civil:</strong> ${aluno.estado_civil}</p>` : ''}
                    ${aluno.profissao ? `<p class="mb-1" style="font-size: 0.9rem;"><strong>Profissão:</strong> ${aluno.profissao}</p>` : ''}
                    ${aluno.escolaridade ? `<p class="mb-1" style="font-size: 0.9rem;"><strong>Escolaridade:</strong> ${aluno.escolaridade}</p>` : ''}
                    <p class="mb-1" style="font-size: 0.9rem;">
                        <strong>Atividade Remunerada:</strong> 
                        ${aluno.atividade_remunerada == 1 ? '<span class="badge bg-success"><i class="fas fa-briefcase me-1"></i>Sim</span>' : '<span class="badge bg-secondary"><i class="fas fa-user me-1"></i>Não</span>'}
                    </p>
                </div>

                <!-- LGPD -->
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-shield-alt me-1"></i>LGPD
                    </h6>
                    <p class="mb-1" style="font-size: 0.9rem;">
                        <strong>Consentimento LGPD:</strong> 
                        ${aluno.lgpd_consentimento == 1 ? '<span class="badge bg-success">Sim</span>' : '<span class="badge bg-secondary">Não</span>'}
                    </p>
                    <p class="mb-1" style="font-size: 0.9rem;">
                        <strong>Data/Hora do Consentimento:</strong> 
                        ${aluno.lgpd_consentimento_em ? formatarDataHora(aluno.lgpd_consentimento_em) : '—'}
                    </p>
                </div>

                <!-- Contatos -->
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-phone me-1"></i>Contatos
                    </h6>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>Telefone:</strong> ${aluno.telefone || 'Não informado'}</p>
                    ${aluno.telefone_secundario ? `<p class="mb-1" style="font-size: 0.9rem;"><strong>Telefone Secundário:</strong> ${aluno.telefone_secundario}</p>` : ''}
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>E-mail:</strong> ${aluno.email || 'Não informado'}</p>
                    ${aluno.contato_emergencia_nome ? `<p class="mb-1" style="font-size: 0.9rem;"><strong>Contato de Emergência:</strong> ${aluno.contato_emergencia_nome}${aluno.contato_emergencia_telefone ? ` - ${aluno.contato_emergencia_telefone}` : ''}</p>` : ''}
                </div>

                <!-- Endereço -->
                ${endereco && (endereco.logradouro || endereco.cidade) ? `
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-map-marker-alt me-1"></i>Endereço
                    </h6>
                    <p class="mb-1" style="font-size: 0.9rem;">
                        ${endereco.logradouro || ''}${endereco.numero ? `, ${endereco.numero}` : ''}${endereco.complemento ? `, ${endereco.complemento}` : ''}
                    </p>
                    <p class="mb-1" style="font-size: 0.9rem;">
                        ${endereco.bairro || ''}${endereco.cidade ? ` - ${endereco.cidade}` : ''}${endereco.uf ? `/${endereco.uf}` : ''}
                    </p>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>CEP:</strong> ${endereco.cep || 'Não informado'}</p>
                </div>
                ` : ''}

                <!-- CFC -->
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-graduation-cap me-1"></i>CFC
                    </h6>
                    <p class="mb-1" style="font-size: 0.9rem;"><strong>CFC:</strong> ${aluno.cfc_nome || 'Não informado'}</p>
                </div>

                <!-- Observações -->
                ${aluno.observacoes ? `
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-sticky-note me-1"></i>Observações do Aluno
                    </h6>
                    <p class="mb-0" style="font-size: 0.9rem; white-space: pre-wrap;">${aluno.observacoes}</p>
                </div>
                ` : ''}
            </div>

            <!-- VISUALIZAR ALUNO: Coluna Direita - Visão Rápida da Jornada -->
            <div class="col-md-6">
                <!-- Mini-cards de resumo -->
                <div class="aluno-historico-cards row mb-3">
                    <div class="col-6 mb-2">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-clipboard-check fa-lg text-primary mb-1"></i>
                                <h6 class="card-title mb-1" style="font-size: 0.85rem;">Situação do Processo</h6>
                                <div class="aluno-card-valor" data-field="processo_status_resumo" style="font-size: 0.8rem;">
                                    <span class="text-muted">Em breve</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-chalkboard-teacher fa-lg text-info mb-1"></i>
                                <h6 class="card-title mb-1" style="font-size: 0.85rem;">Progresso Teórico</h6>
                                <div class="aluno-card-valor" data-field="teorico_resumo" style="font-size: 0.8rem;">
                                    <span class="text-muted">0%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-car fa-lg text-success mb-1"></i>
                                <h6 class="card-title mb-1" style="font-size: 0.85rem;">Progresso Prático</h6>
                                <div class="aluno-card-valor" data-field="pratico_resumo" style="font-size: 0.8rem;">
                                    <span class="text-muted">Não iniciado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-dollar-sign fa-lg text-warning mb-1"></i>
                                <h6 class="card-title mb-1" style="font-size: 0.85rem;">Situação Financeira</h6>
                                <div class="aluno-card-valor" data-field="financeiro_resumo" style="font-size: 0.8rem;">
                                    <span class="text-muted">Em aberto</span>
                                </div>
                                <div class="mt-2" style="font-size: 0.75rem;">
                                    <strong>Status de Pagamento:</strong><br>
                                    ${formatarBadgeStatus(aluno.status_pagamento, 'status_pagamento')}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-6 mb-2">
                        <div class="card border-0 shadow-sm h-100">
                            <div class="card-body text-center p-2">
                                <i class="fas fa-file-signature fa-lg text-danger mb-1"></i>
                                <h6 class="card-title mb-1" style="font-size: 0.85rem;">Provas</h6>
                                <div class="aluno-card-valor" data-field="provas_resumo" style="font-size: 0.8rem;">
                                    <span class="text-muted">Não iniciado</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Timeline compacta -->
                <div class="mb-3">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-clock me-1"></i>Linha do Tempo
                    </h6>
                    <div id="visualizar-historico-container">
                        <ul class="aluno-timeline-list">
                            <li class="aluno-timeline-empty">
                                <div class="aluno-timeline-empty-icon">
                                    <i class="fas fa-history fa-lg text-muted"></i>
                                </div>
                                <p class="text-muted mb-0" style="font-size: 0.85rem;">
                                    Os eventos mais recentes do aluno aparecerão aqui.
                                </p>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Atalhos rápidos -->
                <div class="aluno-atalhos-rapidos">
                    <h6 class="text-primary border-bottom pb-1 mb-2" style="font-size: 0.9rem;">
                        <i class="fas fa-link me-1"></i>Atalhos Rápidos
                    </h6>
                    <div class="d-flex flex-column gap-1">
                        <button type="button" class="btn btn-link aluno-atalho text-start p-0" data-acao="abrir-historico-aluno" disabled style="font-size: 0.85rem;">
                            <i class="fas fa-history me-1"></i>Ver Histórico Completo
                        </button>
                        <button type="button" class="btn btn-link aluno-atalho text-start p-0" data-acao="abrir-agenda-aluno" disabled style="font-size: 0.85rem;">
                            <i class="fas fa-calendar-alt me-1"></i>Abrir Agenda Completa
                        </button>
                        <button type="button" class="btn btn-link aluno-atalho text-start p-0" data-acao="ver-financeiro-aluno" disabled style="font-size: 0.85rem;">
                            <i class="fas fa-dollar-sign me-1"></i>Ver Financeiro do Aluno
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('modalVisualizarAlunoBody').innerHTML = html;
    
    // Configurar botão "Editar Aluno" para usar o modal customizado
    const btnEditar = document.getElementById('btnEditarVisualizacao');
    if (btnEditar) {
        btnEditar.onclick = () => {
            logModalAluno('✏️ Botão Editar Aluno clicado, fechando modal de visualização...');
            fecharModalVisualizarAluno();
            setTimeout(() => {
                logModalAluno('🪟 Abrindo modal de edição...');
                abrirModalAluno('editar', aluno.id);
                // Carregar dados do aluno após abrir o modal
                setTimeout(() => {
                    editarAluno(aluno.id);
                }, 100);
            }, 200);
        };
    }
}

// =====================================================
// CONTROLE DE VISIBILIDADE DO MODAL DE VISUALIZAÇÃO - PADRÃO ÚNICO
// =====================================================

function abrirModalVisualizarAluno(alunoId) {
  const modal = document.getElementById('modalVisualizarAluno');
  if (!modal) {
    logModalAluno('[modalVisualizarAluno] Elemento #modalVisualizarAluno não encontrado.');
    return;
  }

  // CORRIGIDO: Adicionar classe is-open para exibir o modal (necessário para CSS)
  modal.classList.add('is-open');
  modal.dataset.opened = 'true';
  modal.style.display = 'flex'; // Forçar display para garantir visibilidade
  
  document.body.style.overflow = 'hidden';

  // garante que o conteúdo do modal começa no topo
  const bodyEl = modal.querySelector('.visualizar-modal-body');
  if (bodyEl) {
    bodyEl.scrollTop = 0;
  }

  logModalAluno('[modalVisualizarAluno] abrir', { alunoId });
}

// Controle global de requisições pendentes para cancelamento
let activeAbortControllers = [];

function fecharModalVisualizarAluno() {
  const modal = document.getElementById('modalVisualizarAluno');
  if (!modal) {
    logModalAluno('[modalVisualizarAluno] Elemento #modalVisualizarAluno não encontrado (fechar).');
    return;
  }

  // CANCELAR TODAS AS REQUISIÇÕES PENDENTES
  logModalAluno('🛑 Cancelando ' + activeAbortControllers.length + ' requisições pendentes...');
  activeAbortControllers.forEach(controller => {
    try {
      controller.abort();
    } catch (e) {
      // Ignorar erros ao cancelar
    }
  });
  activeAbortControllers = [];

  // CORRIGIDO: Remover classe is-open e ocultar modal corretamente
  modal.classList.remove('is-open');
  modal.dataset.opened = 'false';
  modal.style.display = 'none';
  document.body.style.overflow = '';
  
  // Zerar contexto do aluno atual
  contextoAlunoAtual = { alunoId: null, matriculaId: null, turmaTeoricaId: null };

  logModalAluno('[modalVisualizarAluno] fechado e requisições canceladas');
}

// expõe explicitamente no escopo global
window.abrirModalVisualizarAluno = abrirModalVisualizarAluno;
window.fecharModalVisualizarAluno = fecharModalVisualizarAluno;

logModalAluno('[modalVisualizarAluno] funções abrir/fechar registradas no window.');

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

window.historicoAluno = function(id) {
    // Debug: verificar se a função está sendo chamada
    console.log('Função historicoAluno chamada com ID:', id);
    
    // Redirecionar para página de histórico usando o sistema de roteamento do admin
    window.location.href = `?page=historico-aluno&id=${id}`;
}

function abrirFinanceiroAluno(id) {
    // Debug: verificar se a função está sendo chamada
    console.log('Função abrirFinanceiroAluno chamada com ID:', id);
    
    // Redirecionar para página de faturas com filtro por aluno usando roteamento
    window.location.href = `?page=financeiro-faturas&aluno_id=${id}`;
}

/**
 * Função para alternar status do aluno (botão rápido)
 * Lê o status atual do botão e alterna entre ativo/inativo
 * @param {HTMLElement} button - Botão clicado com data-aluno-id e data-status
 */
function toggleStatusAluno(button) {
    if (!button) {
        console.warn('[toggleStatusAluno] Botão não fornecido');
        return;
    }
    
    const alunoId = button.getAttribute('data-aluno-id');
    if (!alunoId) {
        console.warn('[toggleStatusAluno] ID do aluno não encontrado no botão');
        return;
    }
    
    const currentStatus = button.getAttribute('data-status') || 'ativo';
    const newStatus = currentStatus === 'ativo' ? 'inativo' : 'ativo';
    
    console.log('📡 Alterando status do aluno:', { id: alunoId, currentStatus, newStatus });
    
    // Chamar alterarStatusAluno com o novo status
    alterarStatusAluno(parseInt(alunoId, 10), newStatus);
}

/**
 * Função para ativar aluno (mantida para compatibilidade)
 * @deprecated Use toggleStatusAluno() ao invés desta função
 */
function ativarAluno(id) {
    if (confirm('Deseja realmente ativar este aluno?')) {
        alterarStatusAluno(id, 'ativo');
    }
}

/**
 * Função para desativar aluno (mantida para compatibilidade)
 * @deprecated Use toggleStatusAluno() ao invés desta função
 */
function desativarAluno(id) {
    alterarStatusAluno(id, 'inativo');
}
function excluirAluno(id) {
    const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este aluno?';
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Excluindo aluno...');
        }
        
        const timestamp = new Date().getTime();
        fetch(API_CONFIG.getRelativeApiUrl('ALUNOS') + `?t=${timestamp}`, {
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

/**
 * Função para alterar status do aluno usando a API unificada
 * CORREÇÃO: Agora usa admin/api/alunos.php em vez de pages/alunos.php
 * Alinhado com o fluxo do modal de edição
 */
/**
 * Helper para chamadas seguras de atualização de resumo
 * Torna as chamadas "best effort" - se falhar, não trava o fluxo
 * @param {string} nomeResumo - Nome do resumo para logs (ex: 'provas', 'financeiro')
 * @param {Function} fn - Função async a ser executada
 */
async function tentarAtualizarResumoAluno(nomeResumo, fn) {
    try {
        await fn();
    } catch (error) {
        console.error(`[RESUMO] Falha ao atualizar resumo de ${nomeResumo}:`, error);
        
        // Opcional: mostrar um aviso suave, sem bloquear o fluxo
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta(
                `Aluno atualizado, mas não foi possível atualizar o resumo de ${nomeResumo}.`,
                'warning'
            );
        } else if (typeof window.mostrarToast === 'function') {
            window.mostrarToast(
                'warning',
                `Aluno atualizado, mas não foi possível atualizar o resumo de ${nomeResumo}.`
            );
        }
    }
}

/**
 * Função para finalizar salvamento do aluno com sucesso
 * Garante que o fluxo sempre finalize, mesmo se algum resumo falhar
 * @param {Object} alunoAtualizado - Objeto aluno retornado pela API
 * @param {boolean} silencioso - Se true, não mostra mensagens nem fecha modal
 */
function finalizarSalvamentoAlunoComSucesso(alunoAtualizado, silencioso = false) {
    const btnSalvar = document.getElementById('btnSalvarAluno');
    const textoOriginal = btnSalvar ? btnSalvar.getAttribute('data-texto-original') : null;
    
    // Restaurar botão
    if (btnSalvar && textoOriginal) {
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
    }
    
    if (!silencioso) {
        // Mostrar notificação
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Aluno atualizado com sucesso!', 'success');
        }
        
        // Fechar modal
        if (typeof fecharModalAluno === 'function') {
            fecharModalAluno();
        }
    }
}

/**
 * Função para atualizar aluno na listagem após edição
 * @param {Object} alunoAtualizado - Objeto aluno retornado pela API
 */
function atualizarAlunoNaListagem(alunoAtualizado) {
    if (!alunoAtualizado || !alunoAtualizado.id) {
        console.warn('[atualizarAlunoNaListagem] Aluno inválido:', alunoAtualizado);
        return;
    }
    
    const alunoId = String(alunoAtualizado.id);
    const status = alunoAtualizado.status || 'ativo';
    
    console.log('[atualizarAlunoNaListagem] Atualizando aluno ID:', alunoId, 'Status:', status);
    
    // Mapeamentos de status (alinhados com o PHP)
    const statusClassMap = {
        'ativo': 'success',
        'inativo': 'danger',
        'concluido': 'info'
    };
    const statusTextMap = {
        'ativo': 'Ativo',
        'inativo': 'Inativo',
        'concluido': 'Concluído'
    };
    
    const statusClass = statusClassMap[status] || 'secondary';
    const statusText = statusTextMap[status] || status.charAt(0).toUpperCase() + status.slice(1);
    
    // 1) Atualizar linha desktop
    const row = document.querySelector(`tr[data-aluno-id="${alunoId}"]`);
    if (row) {
        // Atualizar data-status da linha (se existir)
        if (row.dataset) {
            row.dataset.status = status;
        }
        
        // Atualizar badge de status (4ª coluna)
        const statusCell = row.querySelector('td:nth-child(4)');
        if (statusCell) {
            const badge = statusCell.querySelector('.badge');
            if (badge) {
                badge.className = `badge bg-${statusClass}`;
                badge.textContent = statusText;
                console.log('[atualizarAlunoNaListagem] Badge atualizado:', { status, statusClass, statusText });
            }
        }
        
        // Atualizar botão de ação rápida na linha
        const btnToggle = row.querySelector('.btn-toggle-status');
        if (btnToggle) {
            btnToggle.dataset.status = status;
            
            // Atualizar classes e ícone do botão
            btnToggle.classList.remove('btn-outline-secondary', 'btn-outline-success');
            if (status === 'ativo') {
                btnToggle.classList.add('btn-outline-secondary');
                btnToggle.setAttribute('title', 'Desativar aluno (não poderá agendar aulas)');
                const icon = btnToggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-ban';
                }
            } else if (status === 'inativo') {
                btnToggle.classList.add('btn-outline-success');
                btnToggle.setAttribute('title', 'Reativar aluno para agendamento de aulas');
                const icon = btnToggle.querySelector('i');
                if (icon) {
                    icon.className = 'fas fa-check';
                }
            }
        }
    }
    
    // 2) Atualizar card mobile (se existir)
    const mobileCard = document.querySelector(`.mobile-aluno-card[data-aluno-id="${alunoId}"]`);
    if (mobileCard) {
        // Atualizar data-status do card
        if (mobileCard.dataset) {
            mobileCard.dataset.status = status;
        }
        
        const mobileBadge = mobileCard.querySelector('.badge');
        if (mobileBadge) {
            mobileBadge.className = `badge bg-${statusClass}`;
            mobileBadge.textContent = statusText;
        }
        
        // Atualizar botão no card mobile
        const btnToggleMobile = mobileCard.querySelector('.btn-toggle-status');
        if (btnToggleMobile) {
            btnToggleMobile.dataset.status = status;
            
            if (status === 'ativo') {
                btnToggleMobile.setAttribute('title', 'Desativar aluno (não poderá agendar aulas)');
            } else if (status === 'inativo') {
                btnToggleMobile.setAttribute('title', 'Reativar aluno para agendamento de aulas');
            }
        }
    }
    
    console.log('[atualizarAlunoNaListagem] Atualização completa');
}

async function alterarStatusAluno(id, status) {
    // Validar valores permitidos (alinhado com ENUM do banco)
    const statusValidos = ['ativo', 'inativo', 'concluido'];
    if (!statusValidos.includes(status)) {
        console.error('Status inválido:', status);
        if (typeof notifications !== 'undefined') {
            notifications.error('Status inválido. Use: ativo, inativo ou concluido');
        } else {
            alert('Status inválido. Use: ativo, inativo ou concluido');
        }
        return;
    }
    
    const mensagem = `Deseja realmente ${status === 'ativo' ? 'ativar' : 'desativar'} este aluno?`;
    
    if (confirm(mensagem)) {
        if (typeof loading !== 'undefined') {
            loading.showGlobal('Alterando status...');
        }
        
        try {
            // Detectar caminho da API (mesma lógica de alunos.js)
            const baseUrl = window.location.origin;
            const pathname = window.location.pathname;
            let apiUrl;
            
            if (pathname.includes('/admin/')) {
                const pathParts = pathname.split('/');
                const projectIndex = pathParts.findIndex(part => part === 'admin');
                if (projectIndex > 0) {
                    const basePath = pathParts.slice(0, projectIndex).join('/');
                    apiUrl = baseUrl + basePath + '/admin/api/alunos.php';
                } else {
                    apiUrl = baseUrl + '/admin/api/alunos.php';
                }
            } else {
                apiUrl = baseUrl + '/admin/api/alunos.php';
            }
            
            const url = `${apiUrl}?id=${id}`;
            
            console.log('📡 Alterando status do aluno:', { id, status, url });
            console.log('📡 Payload enviado:', JSON.stringify({ status: status }));
            
            // Fazer PUT para a API com apenas o campo status (mesmo padrão usado pelo salvarAluno em edição)
            const response = await fetch(url, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin',
                body: JSON.stringify({ status: status })
            });
            
            if (!response.ok) {
                const errorText = await response.text();
                throw new Error(`HTTP ${response.status}: ${errorText}`);
            }
            
            const data = await response.json();
            
            console.log('[alterarStatusAluno] Resposta da API:', data);
            
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            
            if (data.success) {
                // Se a API retornou o aluno atualizado, atualizar a listagem
                if (data.aluno) {
                    console.log('[alterarStatusAluno] Atualizando listagem com aluno:', data.aluno);
                    atualizarAlunoNaListagem(data.aluno);
                } else {
                    console.warn('[alterarStatusAluno] API não retornou aluno atualizado');
                }
                
                let mensagemSucesso;
                if (status === 'inativo') {
                    mensagemSucesso = 'Aluno desativado com sucesso!';
                } else if (status === 'ativo') {
                    mensagemSucesso = 'Aluno reativado com sucesso!';
                } else {
                    mensagemSucesso = `Status do aluno alterado para ${status} com sucesso!`;
                }
                
                if (typeof notifications !== 'undefined') {
                    notifications.success(mensagemSucesso);
                } else {
                    alert(mensagemSucesso);
                }
                
                // Não recarregar a página - a listagem já foi atualizada acima
                // location.reload(); // Removido para manter consistência com o modal
            } else {
                throw new Error(data.error || 'Erro ao alterar status do aluno');
            }
        } catch (error) {
            if (typeof loading !== 'undefined') {
                loading.hideGlobal();
            }
            console.error('❌ Erro ao alterar status:', error);
            
            const mensagemErro = error.message || 'Erro ao alterar status do aluno';
            if (typeof notifications !== 'undefined') {
                notifications.error(mensagemErro);
            } else {
                alert('Erro ao alterar status do aluno: ' + mensagemErro);
            }
        }
    }
}

function limparFiltros() {
    const status = document.getElementById('filtroStatus');
    const cfc = document.getElementById('filtroCFC');
    const categoria = document.getElementById('filtroCategoria');
    const busca = document.getElementById('buscaAluno');

    if (status) status.value = '';
    if (cfc) cfc.value = '';
    if (categoria) categoria.value = '';
    if (busca) {
        busca.value = '';
        if (window.innerWidth > 640) {
            busca.focus();
        }
    }

    filtrarAlunos();

    if (typeof atualizarEstatisticas === 'function') {
        atualizarEstatisticas();
    }
}

function filtrarAlunos({ silencioso = false } = {}) {
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
    
    // Mostrar notificação de resultado apenas se não for silencioso
    if (!silencioso && typeof notifications !== 'undefined') {
        notifications.info(`Filtro aplicado: ${contador} aluno(s) encontrado(s)`);
    }

    if (typeof atualizarEstatisticas === 'function') {
        atualizarEstatisticas();
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

function exportarAlunos(formato = 'csv') {
    const formatoUpper = (formato || 'csv').toUpperCase();

    if (typeof loading !== 'undefined') {
        loading.showGlobal(`Gerando arquivo ${formatoUpper}...`);
    }

    // TODO: implementar exportação real utilizando dados filtrados
    setTimeout(() => {
        if (typeof loading !== 'undefined') {
            loading.hideGlobal();
        }

        if (typeof notifications !== 'undefined') {
            notifications.success(`Arquivo ${formatoUpper} gerado com sucesso!`);
        } else {
            alert(`Arquivo ${formatoUpper} gerado com sucesso!`);
        }
    }, 1200);
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
    const observacoes = document.getElementById('observacoes_aula');
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
    fetch(API_CONFIG.getRelativeApiUrl('INSTRUTORES'), {
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
    
    fetch(API_CONFIG.getRelativeApiUrl('VEICULOS'), {
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
    fetch(API_CONFIG.getRelativeApiUrl('AGENDAMENTO'), {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(dados)
    })
    .then(response => {
        // Tratar resposta HTTP 409 (Conflict) especificamente
        if (response.status === 409) {
            return response.text().then(text => {
                console.log('Resposta de erro 409:', text);
                try {
                    const errorData = JSON.parse(text);
                    console.log('Dados de erro parseados:', errorData);
                    throw new Error(`CONFLITO: ${errorData.mensagem || 'Conflito de agendamento detectado'}`);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON de erro:', e);
                    console.error('Texto da resposta:', text);
                    // Se não conseguir fazer parse, extrair a mensagem do JSON manualmente
                    let mensagemErro = 'Veículo ou instrutor já possui aula agendada neste horário';
                    
                    // Tentar extrair a mensagem do JSON manualmente
                    const match = text.match(/"mensagem":"([^"]+)"/);
                    if (match && match[1]) {
                        mensagemErro = match[1];
                    } else if (text.includes('INSTRUTOR INDISPONÍVEL')) {
                        mensagemErro = text.replace(/.*INSTRUTOR INDISPONÍVEL: /, '👨‍🏫 INSTRUTOR INDISPONÍVEL: ').replace(/".*/, '');
                    } else if (text.includes('VEÍCULO INDISPONÍVEL')) {
                        mensagemErro = text.replace(/.*VEÍCULO INDISPONÍVEL: /, '🚗 VEÍCULO INDISPONÍVEL: ').replace(/".*/, '');
                    } else if (text.includes('LIMITE DE AULAS EXCEDIDO')) {
                        mensagemErro = text.replace(/.*LIMITE DE AULAS EXCEDIDO: /, '🚫 LIMITE DE AULAS EXCEDIDO: ').replace(/".*/, '');
                    }
                    
                    throw new Error(`CONFLITO: ${mensagemErro}`);
                }
            });
        }
        
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
        
        // Verificar se é erro de conflito específico
        if (error.message.startsWith('CONFLITO:')) {
            const mensagemConflito = error.message.replace('CONFLITO: ', '');
            mostrarAlerta(`⚠️ ${mensagemConflito}`, 'warning');
        } else {
            mostrarAlerta('Erro de conexão. Verifique sua internet e tente novamente.', 'danger');
        }
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
// Função removida - usando a versão mais completa abaixo

// Garantir que a função está disponível globalmente
window.abrirModalAluno = function abrirModalAluno() {
    logModalAluno('🚀 Abrindo modal customizado...');
    
    logModalAluno('🔒 Verificando conflitos com modal de visualização...');
    const modalVisualizar = document.getElementById('modalVisualizarAluno');
    if (modalVisualizar && modalVisualizar.classList.contains('is-open')) {
        logModalAluno('⚠️ Fechando modal de visualização antes de abrir modal de edição...');
        fecharModalVisualizarAluno();
    }
    
    const modal = document.getElementById('modalAluno');
    if (modal) {
        // FORÇAR abertura do modal
        modal.style.setProperty('display', 'block', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.setAttribute('data-opened', 'true'); // Marcar como aberto intencionalmente
        document.body.style.overflow = 'hidden'; // Prevenir scroll do body
        reforcarEstruturaModalAluno();
        
        // CORREÇÃO: Diminuir z-index dos ícones de ação quando modal de edição abrir
        aplicarCorrecaoZIconsAction('open');
        
        logModalAluno('✅ Modal de edição aberto com sucesso');
        
        // SEMPRE definir como criar novo aluno quando esta função é chamada
        const acaoAluno = document.getElementById('acaoAluno');
        if (acaoAluno) {
            acaoAluno.value = 'criar';
            logModalAluno('✅ Campo acaoAluno definido como: criar');
        }
        
        logModalAluno('🔍 Modal aberto - Editando? false (sempre criar novo)');
        
        // SEMPRE limpar formulário para novo aluno
        const formAluno = document.getElementById('formAluno');
        if (formAluno) {
            formAluno.reset();
            logModalAluno('🧹 Formulário limpo para novo aluno');
        }
        
        // Resetar campos específicos que não são tratados pelo reset padrão
        resetFormulario();
        
        const modalTitle = document.getElementById('modalTitle');
        if (modalTitle) {
            modalTitle.innerHTML = '<i class="fas fa-user-graduate me-2"></i>Novo Aluno';
        }
        
        // Limpar seção de operações para novo aluno
        const operacoesContainer = document.getElementById('operacoes-container');
        if (operacoesContainer) {
            operacoesContainer.innerHTML = '';
            contadorOperacoes = 0;
            logModalAluno('🧹 Seção de operações limpa');
            
            // Adicionar operação padrão automaticamente
            adicionarOperacao();
        }
        
        const alunoIdField = document.getElementById('aluno_id_hidden');
        if (alunoIdField) alunoIdField.value = ''; // Limpar ID
        
        // Aplicar responsividade
        setTimeout(() => {
            ajustarModalResponsivo();
        }, 10);
        
        logModalAluno('✅ Modal customizado aberto!');
    }
}

function fecharModalAluno() {
    logModalAluno('🚪 Fechando modal customizado...');
    const modal = document.getElementById('modalAluno');
    if (modal) {
        // FORÇAR fechamento do modal - garantir que está completamente oculto
        modal.style.setProperty('display', 'none', 'important');
        modal.style.setProperty('visibility', 'hidden', 'important');
        
        // Limpar outras propriedades que podem interferir
        const propsToClear = ['position', 'inset', 'width', 'min-height', 'background', 'z-index', 'padding', 'align-items', 'justify-content', 'box-sizing'];
        propsToClear.forEach(prop => modal.style.removeProperty(prop));
        
        // Remover atributos de estado
        modal.removeAttribute('data-opened'); // Remover marcação de aberto
        modal.removeAttribute('data-matricula-carregada'); // Resetar flag de matrícula carregada
        
        // Restaurar scroll do body
        document.body.style.overflow = 'auto';
        document.body.style.removeProperty('overflow');
        
        // Zerar contexto do aluno atual
        contextoAlunoAtual = { alunoId: null, matriculaId: null, turmaTeoricaId: null };

        // Limpar estilos do dialog
        const dialog = modal.querySelector('.custom-modal-dialog');
        if (dialog) {
            ['position', 'left', 'right', 'transform', 'margin', 'width', 'max-width'].forEach(prop => dialog.style.removeProperty(prop));
        }
        
        // CORREÇÃO: Restaurar z-index dos ícones de ação quando modal de edição fechar
        aplicarCorrecaoZIconsAction('close');
        
        // Resetar campos de naturalidade para evitar problemas
        resetFormulario();
        
        // Limpar ID do aluno do formulário para garantir estado limpo
        const alunoIdHidden = document.getElementById('aluno_id_hidden');
        if (alunoIdHidden) {
            alunoIdHidden.value = '';
        }
        
        const acaoAluno = document.getElementById('acaoAluno');
        if (acaoAluno) {
            acaoAluno.value = '';
        }
        
        logModalAluno('✅ Modal customizado fechado completamente!');
    }
}
// Função para resetar o formulário de alunos
function resetFormulario() {
    logModalAluno('🔄 Resetando formulário de alunos...');
    
    // Resetar campos de naturalidade
    const estadoSelect = document.getElementById('naturalidade_estado');
    const municipioSelect = document.getElementById('naturalidade_municipio');
    const naturalidadeInput = document.getElementById('naturalidade');
    
    if (estadoSelect) {
        estadoSelect.value = '';
    }
    
    if (municipioSelect) {
        municipioSelect.innerHTML = '<option value="">Primeiro selecione o estado</option>';
        municipioSelect.disabled = true;
        municipioSelect.value = '';
    }
    
    if (naturalidadeInput) {
        naturalidadeInput.value = '';
    }
    
    // Resetar outros campos principais
    const campos = [
        'nome', 'cpf', 'rg', 'renach', 'data_nascimento', 
        'nacionalidade', 'email', 'telefone', 'status', 'cfc_id'
    ];
    
    campos.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            campo.value = '';
        }
    });
    
    // Resetar campos de endereço
    const camposEndereco = [
        'cep', 'logradouro', 'numero', 'bairro', 'cidade', 'uf', 'complemento'
    ];
    
    camposEndereco.forEach(campuId => {
        const campo = document.getElementById(campuId);
        if (campo) {
            campo.value = '';
        }
    });
    
    // CORREÇÃO: Limpar completamente a foto do aluno
    const fotoInput = document.getElementById('foto');
    const fotoPreview = document.getElementById('foto-preview-aluno');
    const previewContainer = document.getElementById('preview-container-aluno');
    const placeholderFoto = document.getElementById('placeholder-foto-aluno');
    
    if (fotoInput) {
        fotoInput.value = ''; // Limpar o input file
    }
    
    if (fotoPreview) {
        fotoPreview.src = ''; // Limpar a URL da preview
    }
    
    if (previewContainer) {
        previewContainer.style.display = 'none'; // Ocultar container de preview
    }
    
    if (placeholderFoto) {
        placeholderFoto.style.display = 'block'; // Mostrar placeholder
    }
    
    // Limpar qualquer estado interno relacionado à foto
    if (fotoPreview && fotoPreview.classList) {
        fotoPreview.classList.remove('has-image');
    }
    
    logModalAluno('✅ Formulário resetado completamente (incluindo foto)');
    console.log('✅ Formulário resetado completamente');
}


// FUNÇÃO PARA CORRIGIR Z-INDEX DOS ÍCONES DE AÇÃO
function aplicarCorrecaoZIconsAction(acao) {
        logModalAluno(`🔧 Aplicando correção de z-index para ícones de ação: ${acao}`);
    const actionButtons = document.querySelectorAll('.action-icon-btn');
    const actionContainers = document.querySelectorAll('.action-buttons-compact');

    if (acao === 'open') {
        actionButtons.forEach(btn => {
            btn.style.setProperty('z-index', '1', 'important');
        });

        actionContainers.forEach(container => {
            container.style.setProperty('z-index', '1', 'important');
        });

        logModalAluno('🔽 z-index dos ícones diminuído para ficar atrás do modal');
    } else if (acao === 'close') {
        actionButtons.forEach(btn => {
            btn.style.removeProperty('z-index');
        });

        actionContainers.forEach(container => {
            container.style.removeProperty('z-index');
        });

        console.log('🔺 z-index dos ícones restaurado ao normal');
    }
}
// FUNÇÃO GLOBAL PARA LIMPEZA DE CONFLITOS ENTRE MODAIS
function forcarFecharModaisIniciais(origem = 'startup') {
    const modaisBootstrap = Array.from(document.querySelectorAll('.modal.show'));
    const modalAluno = document.getElementById('modalAluno');
    const modalVisualizarOverlay = document.getElementById('modalVisualizarAluno');
    const backdrops = Array.from(document.querySelectorAll('.modal-backdrop'));
    const haviaResiduos = modaisBootstrap.length > 0 || backdrops.length > 0 || document.body.classList.contains('modal-open');

    if (modalAluno && modalAluno.style.display !== 'none' && !modalAluno.hasAttribute('data-opened')) {
        logModalAluno(`🛑 ModalAluno estava visível sem permissão (${origem}) - forçando fechamento.`);
        modalAluno.style.setProperty('display', 'none', 'important');
        modalAluno.style.setProperty('visibility', 'hidden', 'important');
        modalAluno.setAttribute('aria-hidden', 'true');
        modalAluno.removeAttribute('aria-modal');
        modalAluno.removeAttribute('data-opened');
    }

    if (modalVisualizarOverlay && modalVisualizarOverlay.classList.contains('is-open') && !modalVisualizarOverlay.hasAttribute('data-opened')) {
        logModalAluno(`🛑 modalVisualizarAluno estava aberto indevidamente (${origem}) - fechando.`);
        modalVisualizarOverlay.classList.remove('is-open', 'modal-visualizar-fallback');
        modalVisualizarOverlay.setAttribute('aria-hidden', 'true');
        ['display', 'position', 'inset', 'width', 'height', 'overflow-y', 'background', 'visibility', 'opacity'].forEach(prop => {
            modalVisualizarOverlay.style.removeProperty(prop);
        });
        const dialogElement = modalVisualizarOverlay.querySelector('.custom-modal-dialog');
        if (dialogElement) {
            ['position', 'margin', 'width', 'max-width', 'pointer-events', 'display', 'opacity', 'transform'].forEach(prop => {
                dialogElement.style.removeProperty(prop);
            });
        }
    }

    modaisBootstrap.forEach(modal => {
        logModalAluno(`🛑 Fechando modal residual "${modal.id || modal.className}" (${origem}).`);
        modal.classList.remove('show');
        modal.style.removeProperty('display');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
    });

    backdrops.forEach(backdrop => {
        backdrop.remove();
    });

    if (haviaResiduos) {
        logModalAluno(`🧼 Resíduos de modal removidos (${origem}) -> modais:${modaisBootstrap.length} backdrops:${backdrops.length}`);
    }

    document.body.classList.remove('modal-open');
    document.body.classList.remove('visualizar-aluno-open');
    document.body.style.overflow = 'auto';
    document.body.style.removeProperty('paddingRight');
}

function limparTodosModais() {
    logModalAluno('🧹 Limpando todos os modais conflitantes...');
    
    // Aplicar correção aos ícones
    aplicarCorrecaoZIconsAction('close');
    
    // Limpar modal de visualização
    const modalVisualizar = document.getElementById('modalVisualizarAluno');
    if (modalVisualizar) {
        modalVisualizar.classList.remove('is-open', 'modal-visualizar-fallback');
        modalVisualizar.setAttribute('aria-hidden', 'true');
        ['display', 'position', 'inset', 'width', 'height', 'overflow-y', 'background', 'visibility', 'opacity'].forEach(prop => {
            modalVisualizar.style.removeProperty(prop);
        });
        const dialogElement = modalVisualizar.querySelector('.custom-modal-dialog');
        if (dialogElement) {
            ['position', 'margin', 'width', 'max-width', 'pointer-events', 'display', 'opacity', 'transform'].forEach(prop => {
                dialogElement.style.removeProperty(prop);
            });
        }
    }
    
    // Limpar modal de edição
    const modalAlunoParaLimpeza = document.getElementById('modalAluno');
    if (modalAlunoParaLimpeza) {
        modalAlunoParaLimpeza.style.setProperty('display', 'none', 'important');
        modalAlunoParaLimpeza.style.setProperty('visibility', 'hidden');
        modalAlunoParaLimpeza.removeAttribute('data-opened');
    }
    
    // Limpar todos os backdrops
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Restaurar estado do body
    document.body.style.overflow = 'auto';
    document.body.classList.remove('modal-open');
    document.body.classList.remove('visualizar-aluno-open');
    
    console.log('✅ Todos os modais limpos!');
    forcarFecharModaisIniciais('limparTodosModais');
}

// Event listeners para modal de alunos já estão definidos em inicializarModalAluno()

// Inicializar funcionalidades quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    forcarFecharModaisIniciais('domcontentloaded');
    setTimeout(() => forcarFecharModaisIniciais('domcontentloaded+50ms'), 50);
    setTimeout(() => forcarFecharModaisIniciais('domcontentloaded+200ms'), 200);
    setTimeout(() => forcarFecharModaisIniciais('domcontentloaded+500ms'), 500);
    setTimeout(() => forcarFecharModaisIniciais('domcontentloaded+1000ms'), 1000);
    
    // Limpar qualquer modal conflitante na inicialização
    limparTodosModais();
    
    // Observar quando modais são abertos para aplicar validação CPF
    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            if (mutation.type === 'attributes' && mutation.attributeName === 'style') {
                const target = mutation.target;
                if (target.id === 'modalAluno' && target.style.display === 'block') {
                    // Modal foi aberto, aplicar validação após um pequeno delay
                    setTimeout(() => {
                        aplicarValidacaoCPFFormulario();
                    }, 300);
                }
            }
        });
    });
    
    // Observar mudanças no modal de alunos
    const modalAlunoObserver = document.getElementById('modalAluno');
    if (modalAlunoObserver) {
        observer.observe(modalAlunoObserver, { attributes: true });
    }

    const modalVisualizarRoot = document.getElementById('modalVisualizarAluno');
    if (modalVisualizarRoot && modalVisualizarRoot.parentNode && modalVisualizarRoot.parentNode !== document.body) {
        document.body.appendChild(modalVisualizarRoot);
        logModalAluno('📦 modalVisualizarAluno realocado diretamente no body para garantir z-index correto.');
    }

    if (typeof inputMasks !== 'undefined') {
        inputMasks.applyMasks();
    }
    
    // Fechar modal de visualização com ESC
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && document.body.classList.contains('visualizar-aluno-open')) {
            fecharModalVisualizarAluno();
        }
    });
    
    const modalVisualizarOverlay = document.getElementById('modalVisualizarAluno');
    if (modalVisualizarOverlay) {
        modalVisualizarOverlay.addEventListener('click', (event) => {
            if (event.target === modalVisualizarOverlay) {
                fecharModalVisualizarAluno();
            }
        });
    }
    
    // Listener para carregar resumo financeiro e salvamento automático ao trocar para aba Matrícula
    const matriculaTab = document.getElementById('matricula-tab');
    const matriculaPane = document.getElementById('matricula');
    const dadosTab = document.getElementById('dados-tab');
    
    if (matriculaTab && matriculaPane) {
        // Interceptar clique na aba Matrícula para salvar Dados automaticamente
        matriculaTab.addEventListener('click', async function(e) {
            // Verificar se estamos na aba Dados e há dados preenchidos
            const isDadosTabActiveForAutoSave = dadosTab && dadosTab.classList.contains('active');
            const alunoId = document.getElementById('aluno_id_hidden')?.value || 
                           document.getElementById('editar_aluno_id')?.value;
            
            // Se estamos na aba Dados e não temos alunoId ainda (novo aluno), salvar Dados primeiro
            if (isDadosTabActiveForAutoSave && !alunoId) {
                e.preventDefault(); // Prevenir troca imediata de aba
                
                // Verificar se há dados preenchidos na aba Dados
                const nome = document.getElementById('nome')?.value.trim();
                const cpf = document.getElementById('cpf')?.value.trim();
                
                if (nome || cpf) {
                    // Tentar salvar Dados automaticamente
                    try {
                        const resultado = await saveAlunoDados(true); // true = salvamento silencioso (sem fechar modal)
                        
                        if (resultado.success) {
                            // Após salvar com sucesso, permitir troca de aba
                            const alunoIdNovo = resultado.aluno_id || resultado.id;
                            if (alunoIdNovo) {
                                // Atualizar ID do aluno no formulário
                                const alunoIdField = document.getElementById('aluno_id_hidden');
                                if (alunoIdField) {
                                    alunoIdField.value = alunoIdNovo;
                                }
                                
                                // Agora permitir a troca de aba
                                const matriculaTabBootstrap = new bootstrap.Tab(matriculaTab);
                                matriculaTabBootstrap.show();
                                
                                console.log('✅ Dados salvos automaticamente, trocando para aba Matrícula');
                            }
                        } else {
                            // Se falhou, mostrar erro e não trocar de aba
                            alert('⚠️ Não foi possível salvar os dados do aluno.\n\n' + (resultado.error || 'Verifique os campos obrigatórios na aba Dados.'));
                            return false;
                        }
                    } catch (error) {
                        console.error('Erro ao salvar dados automaticamente:', error);
                        alert('⚠️ Erro ao salvar dados do aluno. Verifique o console para mais detalhes.');
                        return false;
                    }
                } else {
                    // Se não há dados preenchidos, permitir troca normalmente
                    return true;
                }
            }
        });
        
        // Usar evento Bootstrap 5 para quando a aba é mostrada (após troca bem-sucedida)
        matriculaTab.addEventListener('shown.bs.tab', function() {
            // Quando a aba Matrícula é mostrada, verificar se precisa carregar o resumo
            const container = document.getElementById('resumo-financeiro-matricula');
            const alunoId = document.getElementById('aluno_id_hidden')?.value || 
                           document.getElementById('editar_aluno_id')?.value;
            
            // Se o container ainda mostra "Carregando..." e temos um alunoId, carregar
            if (container && alunoId) {
                const textoAtual = container.textContent || '';
                if (textoAtual.includes('Carregando') || textoAtual.trim() === '') {
                    logModalAluno('🔄 Aba Matrícula ativada - carregando resumo financeiro');
                    atualizarResumoFinanceiroMatricula(alunoId);
                }
            }
        });
        
        logModalAluno('✅ Listener da aba Matrícula configurado (com salvamento automático)');
    }
    
    // Mostrar notificação de carregamento
    if (typeof notifications !== 'undefined') {
        notifications.info('Página de alunos carregada com sucesso!');
    }
    
    // Configurar tooltips e popovers se disponível (evitar duplicação)
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]:not([data-bs-tooltip-initialized])'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            try {
                new bootstrap.Tooltip(tooltipTriggerEl);
                tooltipTriggerEl.setAttribute('data-bs-tooltip-initialized', 'true');
            } catch (error) {
                console.warn('Tooltip já inicializado para:', tooltipTriggerEl);
            }
        });
    }
    
    // Modal customizado - não precisamos mais do código do Bootstrap
    
    // Aplicar responsividade quando o modal abrir
    const modalAlunoResponsivity = document.getElementById('modalAluno');
    if (modalAlunoResponsivity) {
        modalAlunoResponsivity.addEventListener('DOMNodeInserted', ajustarModalResponsivo);
    }
    
    // Aplicar responsividade no resize da janela
    window.addEventListener('resize', function() {
        if (document.getElementById('modalAluno').style.display === 'block') {
            ajustarModalResponsivo();
        }
    });
});

window.addEventListener('load', () => {
    forcarFecharModaisIniciais('window-load');
    setTimeout(() => forcarFecharModaisIniciais('window-load+200ms'), 200);
    setTimeout(() => forcarFecharModaisIniciais('window-load+600ms'), 600);
    setTimeout(() => forcarFecharModaisIniciais('window-load+200ms'), 200);
});
// Função para carregar categorias CNH dinamicamente
// Removido: função carregarCategoriasCNH() - não é mais necessária

/**
 * Salva apenas os dados básicos do aluno (aba Dados)
 * Não exige matrícula preenchida
 * 
 * @param {boolean} silencioso - Se true, não mostra mensagens de sucesso nem fecha modal
 * @returns {Promise<{success: boolean, aluno_id?: number, error?: string}>}
 */
/**
 * Função para salvar dados do aluno (aba Dados)
 * 
 * FLUXO ANTIGO (CORRIGIDO): Usava POST com FormData para api/alunos.php (relativo)
 * FLUXO NOVO (UNIFICADO): Usa PUT com JSON para admin/api/alunos.php (mesmo padrão do botão rápido)
 * 
 * IMPORTANTE: Agora envia o campo 'status' corretamente e usa a mesma API que o botão rápido
 */
async function saveAlunoDados(silencioso = false) {
    console.log('[DEBUG] saveAlunoDados iniciado');
    
    const form = document.getElementById('formAluno');
    if (!form) {
        console.error('[DEBUG] Form formAluno não encontrado!');
        return { success: false, error: 'Formulário não encontrado' };
    }
    
    const formData = new FormData(form);
    
    // Validar apenas campos da aba Dados
    const nome = formData.get('nome')?.trim();
    const cpf = formData.get('cpf')?.trim();
    
    if (!nome) {
        alert('⚠️ O campo Nome é obrigatório.');
        document.getElementById('nome')?.focus();
        return { success: false, error: 'Nome é obrigatório' };
    }
    
    if (!cpf || cpf.length < 14) {
        alert('⚠️ O campo CPF é obrigatório e deve estar completo.');
        document.getElementById('cpf')?.focus();
        return { success: false, error: 'CPF é obrigatório' };
    }
    
    // Validar CPF
    const cpfLimpo = cpf.replace(/\D/g, '');
    if (!validarCPF(cpfLimpo)) {
        alert('Por favor, digite um CPF válido.');
        document.getElementById('cpf')?.focus();
        return { success: false, error: 'CPF inválido' };
    }
    
    // Mostrar loading no botão
    const btnSalvar = document.getElementById('btnSalvarAluno');
    const textoOriginal = btnSalvar ? btnSalvar.innerHTML : '';
    // Salvar texto original para usar na finalização
    if (btnSalvar) {
        btnSalvar.setAttribute('data-texto-original', textoOriginal);
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando Dados...';
        btnSalvar.disabled = true;
    }
    
    // Preparar dados apenas da aba Dados
    const dadosFormData = new FormData();
    dadosFormData.append('nome', nome);
    dadosFormData.append('cpf', cpf);
    dadosFormData.append('rg', formData.get('rg') || '');
    dadosFormData.append('rg_orgao_emissor', formData.get('rg_orgao_emissor') || '');
    dadosFormData.append('rg_uf', formData.get('rg_uf') || '');
    
    // Tratar rg_data_emissao: enviar null se vazio
    const campoRgDataEmissao = form.querySelector('input[name="rg_data_emissao"]');
    let rgDataEmissaoValor = campoRgDataEmissao ? campoRgDataEmissao.value.trim() : '';
    if (rgDataEmissaoValor === '' || rgDataEmissaoValor === '0000-00-00') {
        dadosFormData.append('rg_data_emissao', '');
    } else {
        dadosFormData.append('rg_data_emissao', rgDataEmissaoValor);
    }
    dadosFormData.append('data_nascimento', formData.get('data_nascimento') || '');
    dadosFormData.append('estado_civil', formData.get('estado_civil') || '');
    dadosFormData.append('profissao', formData.get('profissao') || '');
    dadosFormData.append('escolaridade', formData.get('escolaridade') || '');
    
    // Naturalidade
    const naturalidadeField = document.getElementById('naturalidade');
    const estadoField = document.getElementById('naturalidade_estado');
    const municipioField = document.getElementById('naturalidade_municipio');
    
    let naturalidadeValue = formData.get('naturalidade') || '';
    if (!naturalidadeValue && estadoField?.value && municipioField?.value) {
        const nomeEstado = getNomeEstadoPorSigla(estadoField.value);
        naturalidadeValue = `${municipioField.value} - ${nomeEstado}`;
        if (naturalidadeField) {
            naturalidadeField.value = naturalidadeValue;
        }
    }
    dadosFormData.append('naturalidade', naturalidadeValue);
    dadosFormData.append('naturalidade_estado', estadoField?.value || '');
    dadosFormData.append('naturalidade_municipio', municipioField?.value || '');
    
    dadosFormData.append('nacionalidade', formData.get('nacionalidade') || '');
    dadosFormData.append('email', formData.get('email') || '');
    dadosFormData.append('telefone', formData.get('telefone') || '');
    dadosFormData.append('telefone_secundario', formData.get('telefone_secundario') || '');
    dadosFormData.append('contato_emergencia_nome', formData.get('contato_emergencia_nome') || '');
    dadosFormData.append('contato_emergencia_telefone', formData.get('contato_emergencia_telefone') || '');
    // IMPORTANTE: Status do aluno - deve ser enviado e atualizado no banco
    const statusValue = formData.get('status') || 'ativo';
    dadosFormData.append('status', statusValue);
    
    // LOG TEMPORÁRIO: Status sendo enviado (remover após validação)
    console.log('[DEBUG STATUS MODAL] Status no FormData:', statusValue);
    dadosFormData.append('cfc_id', formData.get('cfc_id') || '');
    dadosFormData.append('atividade_remunerada', formData.get('atividade_remunerada') ? 1 : 0);
    dadosFormData.append('cep', formData.get('cep') || '');
    dadosFormData.append('endereco', formData.get('logradouro') || '');
    dadosFormData.append('numero', formData.get('numero') || '');
    dadosFormData.append('bairro', formData.get('bairro') || '');
    dadosFormData.append('cidade', formData.get('cidade') || '');
    dadosFormData.append('estado', formData.get('uf') || '');
    dadosFormData.append('observacoes', formData.get('observacoes') || '');
    
    // LGPD
    const lgpdCheckbox = document.getElementById('lgpd_consentimento');
    const lgpdConsentimento = lgpdCheckbox && lgpdCheckbox.checked ? 1 : 0;
    dadosFormData.append('lgpd_consentimento', lgpdConsentimento);
    
    // Se LGPD está marcado e não há data de consentimento salva, será definida no backend
    // Se já existe data, manter (não enviar lgpd_consentimento_em aqui, deixar backend decidir)
    
    // Detectar se estamos editando (usar aluno_id_hidden que é o campo real)
    const alunoIdHidden = document.getElementById('aluno_id_hidden');
    const alunoId = alunoIdHidden?.value;
    const isEditing = !!alunoId && alunoId.trim() !== '';
    
    // IMPORTANTE: Ler status DIRETAMENTE do select, não do FormData (pode estar desatualizado)
    const statusSelect = document.getElementById('status');
    const status = statusSelect ? statusSelect.value : (formData.get('status') || 'ativo');
    
    // LOG TEMPORÁRIO: Status sendo lido
    console.log('[DEBUG STATUS MODAL] Status no FormData:', formData.get('status'));
    console.log('[DEBUG STATUS MODAL] Status lido do select (direto):', status);
    console.log('[DEBUG STATUS MODAL] isEditing:', isEditing);
    console.log('[DEBUG STATUS MODAL] alunoId:', alunoId);
    
    // Adicionar foto se houver
    const fotoInput = document.getElementById('foto');
    const temFoto = fotoInput && fotoInput.files && fotoInput.files[0];
    
    // Marcar que é salvamento apenas de Dados (não incluir matrícula)
    dadosFormData.append('salvar_apenas_dados', '1');
    
    // GARANTIR que status está no FormData com o valor correto
    dadosFormData.set('status', status);
    
    try {
        // Detectar caminho da API (mesma lógica de alunos.js)
        const baseUrl = window.location.origin;
        const pathname = window.location.pathname;
        let apiBaseUrl;
        
        if (pathname.includes('/admin/')) {
            const pathParts = pathname.split('/');
            const projectIndex = pathParts.findIndex(part => part === 'admin');
            if (projectIndex > 0) {
                const basePath = pathParts.slice(0, projectIndex).join('/');
                apiBaseUrl = baseUrl + basePath + '/admin/api/alunos.php';
            } else {
                apiBaseUrl = baseUrl + '/admin/api/alunos.php';
            }
        } else {
            apiBaseUrl = baseUrl + '/admin/api/alunos.php';
        }
        
        let response;
        
        if (isEditing) {
            // EDIÇÃO: Usar PUT (mesmo padrão do botão rápido)
            const endpoint = `?id=${alunoId}`;
            const url = apiBaseUrl + endpoint;
            
            if (temFoto) {
                // Se houver foto, usar FormData com method override
                console.log('[DEBUG STATUS MODAL] Modo: EDIÇÃO com FOTO - usando FormData');
                console.log('[DEBUG STATUS MODAL] Status garantido no FormData:', dadosFormData.get('status'));
                
                // Adicionar method override para PUT via POST
                dadosFormData.append('_method', 'PUT');
                
                response = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: dadosFormData
                    // Não definir Content-Type - navegador define com boundary
                });
            } else {
                // Se não houver foto, usar JSON (mais eficiente e alinhado com botão rápido)
                console.log('[DEBUG STATUS MODAL] Modo: EDIÇÃO sem FOTO - usando JSON');
                
                // Converter FormData para objeto JSON
                const data = {};
                for (let [key, value] of dadosFormData.entries()) {
                    // Ignorar arquivo de foto se não houver
                    if (!(value instanceof File)) {
                        // Tratar rg_data_emissao: enviar null se vazio
                        if (key === 'rg_data_emissao' && (value === '' || value === '0000-00-00')) {
                            data[key] = null;
                        } else {
                            data[key] = value;
                        }
                    }
                }
                
                // GARANTIR que status está presente e correto
                data.status = status;
                
                // Log do payload enviado
                console.log('[SAVE ALUNO] Enviando payload para API (EDIÇÃO):', {
                    method: 'PUT',
                    url: url,
                    alunoId: alunoId,
                    status: data.status,
                    hasFoto: false
                });
                console.log('[SAVE ALUNO] Payload completo:', data);
                
                response = await fetch(url, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(data)
                });
            }
        } else {
            // CRIAÇÃO: Usar POST
            if (temFoto) {
                console.log('[DEBUG STATUS MODAL] Modo: CRIAÇÃO com FOTO - usando FormData');
                response = await fetch(apiBaseUrl, {
                    method: 'POST',
                    body: dadosFormData
                });
            } else {
                console.log('[DEBUG STATUS MODAL] Modo: CRIAÇÃO sem FOTO - usando JSON');
                
                // Converter FormData para objeto JSON
                const alunoData = {};
                for (let [key, value] of dadosFormData.entries()) {
                    if (key !== 'foto') {
                        alunoData[key] = value;
                    }
                }
                
                console.log('[SAVE ALUNO] Enviando payload para API (CRIAÇÃO):', {
                    method: 'POST',
                    url: apiBaseUrl,
                    hasFoto: false
                });
                console.log('[SAVE ALUNO] Payload completo:', alunoData);
                
                response = await fetch(apiBaseUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin',
                    body: JSON.stringify(alunoData)
                });
            }
        }
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
        }
        
        console.log('[SAVE ALUNO] Resposta recebida da API:', {
            success: data.success,
            status: response.status,
            alunoId: data.aluno_id || data.id || (data.aluno ? data.aluno.id : null),
            hasAluno: !!data.aluno
        });
        
        if (data.success) {
            const alunoId = data.aluno_id || data.id || (data.aluno ? data.aluno.id : null);
            
            console.log('[SAVE ALUNO] Salvamento bem-sucedido, alunoId:', alunoId);
            
            // Atualizar ID do aluno no formulário
            if (alunoId && alunoIdHidden) {
                alunoIdHidden.value = alunoId;
            }
            
            // Se a API retornou o aluno atualizado, atualizar a listagem
            if (data.aluno) {
                console.log('[SAVE ALUNO] Atualizando listagem com aluno:', data.aluno.id, 'status:', data.aluno.status);
                atualizarAlunoNaListagem(data.aluno);
            }
            
            // FECHAR MODAL E RESTAURAR BOTÃO IMEDIATAMENTE (não esperar resumos)
            console.log('[SAVE ALUNO] Finalizando fluxo principal (fechando modal, restaurando botão)');
            finalizarSalvamentoAlunoComSucesso(data.aluno, silencioso);
            
            // Disparar resumos em BACKGROUND (fire and forget - não bloqueiam o fluxo)
            if (alunoId) {
                console.log('[SAVE ALUNO] Disparando atualizações de resumo em background (não bloqueante)');
                
                // Resumos são "best effort" - executam em background sem travar a UI
                try {
                    // Financeiro
                    if (typeof atualizarResumoFinanceiroAluno === 'function') {
                        atualizarResumoFinanceiroAluno(alunoId, null).catch(err => {
                            console.error('[RESUMO FINANCEIRO] Erro ao atualizar resumo após salvar aluno:', err);
                        });
                    }
                    
                    // Provas
                    if (typeof atualizarResumoProvasAluno === 'function') {
                        atualizarResumoProvasAluno(alunoId).catch(err => {
                            console.error('[RESUMO PROVAS] Erro ao atualizar resumo após salvar aluno:', err);
                        });
                    }
                    
                    // Teórico
                    if (typeof atualizarResumoTeoricoAluno === 'function') {
                        atualizarResumoTeoricoAluno(alunoId).catch(err => {
                            console.error('[RESUMO TEÓRICO] Erro ao atualizar resumo após salvar aluno:', err);
                        });
                    }
                    
                    // Prático
                    if (typeof atualizarResumoPraticoAluno === 'function') {
                        atualizarResumoPraticoAluno(alunoId).catch(err => {
                            console.error('[RESUMO PRÁTICO] Erro ao atualizar resumo após salvar aluno:', err);
                        });
                    }
                } catch (err) {
                    console.warn('[SAVE ALUNO] Exceção inesperada ao disparar atualizações de resumo em background:', err);
                }
            }
            
            return { success: true, aluno_id: alunoId, aluno: data.aluno };
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('[SAVE ALUNO] Erro ao salvar dados do aluno:', error);
        
        // Sempre restaurar botão, mesmo em caso de erro
        if (btnSalvar) {
            const textoOriginalRestore = btnSalvar.getAttribute('data-texto-original') || textoOriginal;
            btnSalvar.innerHTML = textoOriginalRestore;
            btnSalvar.disabled = false;
        }
        
        // Mostrar erro amigável
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Erro ao salvar dados do aluno: ' + error.message, 'error');
        } else {
            alert('Erro ao salvar dados do aluno: ' + error.message);
        }
        
        return { success: false, error: error.message };
    } finally {
        // GARANTIR que o botão sempre seja restaurado, mesmo se houver exceção não capturada
        console.log('[SAVE ALUNO] Finalizando fluxo (finally)');
        const btnSalvarFinally = document.getElementById('btnSalvarAluno');
        if (btnSalvarFinally && btnSalvarFinally.disabled) {
            const textoOriginalFinally = btnSalvarFinally.getAttribute('data-texto-original') || textoOriginal;
            btnSalvarFinally.innerHTML = textoOriginalFinally;
            btnSalvarFinally.disabled = false;
        }
    }
}

/**
 * Salva a matrícula do aluno (aba Matrícula)
 * Exige que o aluno já exista (ID definido)
 * 
 * @returns {Promise<{success: boolean, error?: string}>}
 */
async function saveAlunoMatricula() {
    console.log('[DEBUG] saveAlunoMatricula chamada');
    
    const alunoIdHidden = document.getElementById('aluno_id_hidden');
    const alunoId = alunoIdHidden?.value;
    
    console.log('[DEBUG] alunoId:', alunoId);
    
    if (!alunoId) {
        // Se não tem alunoId, tentar salvar Dados primeiro
        const resultadoDados = await saveAlunoDados(true);
        if (!resultadoDados.success) {
            alert('⚠️ É necessário salvar os dados do aluno primeiro.\n\nPor favor, preencha e salve a aba Dados antes de salvar a Matrícula.');
            return { success: false, error: 'Aluno não existe ainda' };
        }
        
        // Atualizar alunoId após salvar Dados
        const novoAlunoId = resultadoDados.aluno_id;
        if (novoAlunoId && alunoIdHidden) {
            alunoIdHidden.value = novoAlunoId;
        }
    }
    
    const form = document.getElementById('formAluno');
    const formData = new FormData(form);
    
    // Validar campos obrigatórios da Matrícula
    // NOTA: RENACH não é mais obrigatório se estiver na aba Matrícula mas o campo está na aba Dados
    // A validação será feita apenas se o campo estiver visível e tiver o atributo required
    const renachField = document.getElementById('renach');
    const renach = renachField?.value.trim();
    
    // Verificar se RENACH é realmente obrigatório (está na aba Matrícula e tem required)
    const matriculaTabPane = document.getElementById('matricula');
    const renachTabPane = renachField?.closest('.tab-pane');
    const isRenachInMatriculaTab = renachTabPane && renachTabPane.id === 'matricula';
    
    // Se RENACH está na aba Matrícula e está vazio, mas não tem required, não bloquear
    if (!renach && isRenachInMatriculaTab && !renachField?.hasAttribute('required')) {
        console.log('⚠️ RENACH vazio, mas não é obrigatório na aba Matrícula. Continuando...');
    } else if (!renach && renachField?.hasAttribute('required')) {
        alert('⚠️ O campo RENACH é obrigatório na aba Matrícula.');
        renachField?.focus();
        renachField?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        return { success: false, error: 'RENACH é obrigatório' };
    }
    
    // Mostrar loading no botão
    const btnSalvar = document.getElementById('btnSalvarAluno');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando Matrícula...';
    btnSalvar.disabled = true;
    
    // Coletar operações de habilitação
    const operacoes = coletarDadosOperacoes();
    
    // Extrair categoria_cnh e tipo_servico da primeira operação
    let categoriaCnh = '';
    let tipoServico = '';
    if (operacoes && operacoes.length > 0) {
        const primeiraOperacao = operacoes[0];
        categoriaCnh = primeiraOperacao.categoria || formData.get('categoria_cnh') || '';
        // Mapear tipo da operação para tipo_servico da API
        const tipoOperacao = primeiraOperacao.tipo || '';
        if (tipoOperacao === 'primeira_habilitacao' || tipoOperacao === 'primeira') {
            tipoServico = 'primeira_habilitacao';
        } else if (tipoOperacao === 'adicao' || tipoOperacao === 'adicao_categoria') {
            tipoServico = 'adicao';
        } else if (tipoOperacao === 'mudanca' || tipoOperacao === 'mudanca_categoria') {
            tipoServico = 'mudanca';
        } else {
            tipoServico = tipoOperacao || formData.get('tipo_servico') || '';
        }
    } else {
        // Fallback: tentar pegar do formulário
        categoriaCnh = formData.get('categoria_cnh') || '';
        tipoServico = formData.get('tipo_servico') || '';
    }
    
    // Validar campos obrigatórios da API
    const dataMatricula = formData.get('data_matricula') || '';
    if (!categoriaCnh || !tipoServico || !dataMatricula) {
        const camposFaltando = [];
        if (!categoriaCnh) camposFaltando.push('Categoria CNH');
        if (!tipoServico) camposFaltando.push('Tipo de Serviço');
        if (!dataMatricula) camposFaltando.push('Data da Matrícula');
        
        alert('⚠️ Campos obrigatórios da matrícula não preenchidos:\n\n' +
              camposFaltando.map(c => `- ${c}`).join('\n') +
              '\n\nPor favor, preencha todos os campos obrigatórios.');
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
        return { success: false, error: 'Campos obrigatórios não preenchidos' };
    }
    
    // Verificar se é edição (já existe matrícula) ou criação
    const matriculaId = contextoAlunoAtual.matriculaId || null;
    const isEdicao = matriculaId !== null && matriculaId !== undefined;
    
    // Preparar dados da Matrícula em formato JSON
    const dadosMatricula = {
        aluno_id: parseInt(alunoIdHidden.value),
        categoria_cnh: categoriaCnh,
        tipo_servico: tipoServico,
        data_inicio: dataMatricula,
        data_fim: formData.get('data_conclusao') || null,
        previsao_conclusao: formData.get('previsao_conclusao') || null,
        status: formData.get('status_matricula') || 'ativa',
        // Campos financeiros removidos - valor_total, forma_pagamento e status_pagamento
        // Agora os dados financeiros vêm apenas do módulo Financeiro do Aluno (faturas/parcelas)
        observacoes: formData.get('observacoes') || null,
        renach: renach || null,
        processo_numero: formData.get('processo_numero') || null,
        processo_numero_detran: formData.get('processo_numero_detran') || null,
        processo_situacao: formData.get('processo_situacao') || null,
        aulas_praticas_contratadas: (() => {
            const valor = formData.get('aulas_praticas_contratadas');
            console.log('[DEBUG SAVE] aulas_praticas_contratadas do formData:', valor, 'tipo:', typeof valor);
            if (valor && valor.trim() !== '') {
                const parsed = parseInt(valor);
                console.log('[DEBUG SAVE] aulas_praticas_contratadas parseado:', parsed);
                return isNaN(parsed) ? null : parsed;
            }
            console.log('[DEBUG SAVE] aulas_praticas_contratadas vazio ou inválido, retornando null');
            return null;
        })(),
        aulas_praticas_extras: (() => {
            const valor = formData.get('aulas_praticas_extras');
            console.log('[DEBUG SAVE] aulas_praticas_extras do formData:', valor, 'tipo:', typeof valor);
            if (valor && valor.trim() !== '') {
                const parsed = parseInt(valor);
                console.log('[DEBUG SAVE] aulas_praticas_extras parseado:', parsed);
                return isNaN(parsed) ? null : parsed;
            }
            console.log('[DEBUG SAVE] aulas_praticas_extras vazio ou inválido, retornando null');
            return null;
        })()
    };
    
    // Se for edição, adicionar ID da matrícula
    if (isEdicao) {
        dadosMatricula.id = parseInt(matriculaId);
    }
    
    // Log completo do payload antes de enviar
    console.log('[DEBUG SAVE] Payload completo que será enviado:', JSON.stringify(dadosMatricula, null, 2));
    
    try {
        const timestamp = new Date().getTime();
        // Se for edição, usar PUT; se for criação, usar POST
        const url = isEdicao 
            ? `api/matriculas.php?id=${matriculaId}&t=${timestamp}`
            : `api/matriculas.php?t=${timestamp}`;
        const method = isEdicao ? 'PUT' : 'POST';
        
        const response = await fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(dadosMatricula)
        });
        
        const text = await response.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch (e) {
            console.error('Resposta não é JSON:', text);
            throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
        }
        
        if (data.success) {
            // Atualizar matriculaId no contexto se foi criação
            if (data.matricula_id && !isEdicao) {
                contextoAlunoAtual.matriculaId = data.matricula_id;
            }
            
            // Restaurar botão antes de fechar modal
            btnSalvar.innerHTML = textoOriginal;
            btnSalvar.disabled = false;
            
            // Mostrar notificação discreta (sem alert)
            mostrarAlerta(data.message || 'Matrícula salva com sucesso!', 'success');
            
            // Fechar modal automaticamente
            fecharModalAluno();
            
            // Atualizar lista de alunos silenciosamente (sem mostrar "Filtro aplicado...")
            if (typeof filtrarAlunos === 'function') {
                filtrarAlunos({ silencioso: true });
            }
            
            // Recarregar dados em background (sem mostrar mensagens ou recarregar página)
            const alunoId = parseInt(alunoIdHidden.value);
            if (alunoId) {
                // Recarregar matrícula principal silenciosamente após fechar modal
                setTimeout(() => {
                    carregarMatriculaPrincipal(alunoId).catch(() => {
                        // Ignorar erros silenciosamente
                    });
                }, 500);
            }
            
            return { success: true };
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    } catch (error) {
        console.error('Erro ao salvar matrícula:', error);
        alert('Erro ao salvar matrícula: ' + error.message);
        btnSalvar.innerHTML = textoOriginal;
        btnSalvar.disabled = false;
        return { success: false, error: error.message };
    }
}

// Função para salvar aluno via AJAX (mantida para compatibilidade, mas agora usa saveAlunoDados/saveAlunoMatricula)
function salvarAluno() {
    const form = document.getElementById('formAluno');
    const formData = new FormData(form);
    
    // CORREÇÃO: Validar RENACH apenas se estiver na aba Matrícula e for obrigatório
    // Nota: O campo renach está na aba Matrícula, então só validamos se o usuário estiver nessa aba
    const renachField = document.getElementById('renach');
    const matriculaTabPane = document.getElementById('matricula');
    const isMatriculaTabActive = matriculaTabPane && matriculaTabPane.classList.contains('active');
    
    // Se RENACH tem data-required-in-matricula e estamos na aba Matrícula, validar
    if (renachField && renachField.dataset.requiredInMatricula === 'true' && isMatriculaTabActive) {
        const renachValue = renachField.value.trim();
        if (!renachValue) {
            alert('⚠️ O campo RENACH é obrigatório na aba Matrícula.\n\nPor favor, preencha o RENACH antes de salvar.');
            renachField.focus();
            renachField.scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
    }
    
    // Se não estamos na aba Matrícula, não validar RENACH (permitir salvar apenas com dados básicos)
    
    // Validar CPF antes de prosseguir
    const cpfInput = document.getElementById('cpf');
    if (cpfInput && cpfInput.value.length === 14) {
        const cpfLimpo = cpfInput.value.replace(/\D/g, '');
        if (!validarCPF(cpfLimpo)) {
            alert('Por favor, digite um CPF válido.');
            cpfInput.focus();
            return;
        }
    } else if (cpfInput && cpfInput.value.length > 0) {
        alert('Por favor, digite um CPF completo.');
        cpfInput.focus();
        return;
    }
    
    
    // Mostrar loading no botão
    const btnSalvar = document.getElementById('btnSalvarAluno');
    const textoOriginal = btnSalvar.innerHTML;
    btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSalvar.disabled = true;
    
    // Coletar operações de habilitação
    const operacoes = coletarDadosOperacoes();
    
    // Preparar dados para envio usando FormData para suportar upload de arquivo
    const dadosFormData = new FormData();
    dadosFormData.append('nome', formData.get('nome'));
    dadosFormData.append('cpf', formData.get('cpf'));
    dadosFormData.append('rg', formData.get('rg') || '');
    dadosFormData.append('renach', formData.get('renach') || '');
    dadosFormData.append('data_nascimento', formData.get('data_nascimento') || '');
    // Debug dos campos de naturalidade
    const naturalidadeField = document.getElementById('naturalidade');
    const estadoField = document.getElementById('naturalidade_estado');
    const municipioField = document.getElementById('naturalidade_municipio');
    
    console.log('🔍 Debug naturalidade antes do envio:');
    console.log('  Campo naturalidade:', naturalidadeField?.value || 'não existe');
    console.log('  Campo naturalidade_estado:', estadoField?.value || 'não existe');
    console.log('  Campo naturalidade_municipio:', municipioField?.value || 'não existe');
    
    // Se campos individuais estão preenchidos mas o campo naturalidade está vazio, reconstruir
    let naturalidadeValue = formData.get('naturalidade') || '';
    if (!naturalidadeValue && estadoField?.value && municipioField?.value) {
        const nomeEstado = getNomeEstadoPorSigla(estadoField.value);
        naturalidadeValue = `${municipioField.value} - ${nomeEstado}`;
        console.log('🔄 Naturalidade reconstruída:', naturalidadeValue);
        
        // Atualizar o campo hidden para ser enviado corretamente
        if (naturalidadeField) {
            naturalidadeField.value = naturalidadeValue;
        }
    }
    
    dadosFormData.append('naturalidade', naturalidadeValue);
    // Adicionar campos de naturalidade separadamente (para campos disabled não são enviados automaticamente)
    dadosFormData.append('naturalidade_estado', estadoField?.value || '');
    dadosFormData.append('naturalidade_municipio', municipioField?.value || '');
    dadosFormData.append('nacionalidade', formData.get('nacionalidade') || '');
    dadosFormData.append('email', formData.get('email') || '');
    dadosFormData.append('telefone', formData.get('telefone') || '');
    dadosFormData.append('status', formData.get('status') || '');
    dadosFormData.append('cfc_id', formData.get('cfc_id') || '');
    dadosFormData.append('operacoes', JSON.stringify(operacoes));
    dadosFormData.append('atividade_remunerada', formData.get('atividade_remunerada') ? 1 : 0);
    dadosFormData.append('cep', formData.get('cep') || '');
    dadosFormData.append('endereco', formData.get('logradouro') || '');
    dadosFormData.append('numero', formData.get('numero') || '');
    dadosFormData.append('bairro', formData.get('bairro') || '');
    dadosFormData.append('cidade', formData.get('cidade') || '');
    dadosFormData.append('estado', formData.get('uf') || '');
    dadosFormData.append('observacoes', formData.get('observacoes') || '');
    
    // Adicionar ID do aluno se for edição
    const alunoIdHidden = document.getElementById('aluno_id_hidden');
    const acaoAluno = document.getElementById('acaoAluno');
    const isEditing = acaoAluno && acaoAluno.value === 'editar';
    
    if (isEditing && alunoIdHidden && alunoIdHidden.value) {
        dadosFormData.append('id', alunoIdHidden.value);
        logModalAluno('📝 Enviando ID do aluno para edição:', alunoIdHidden.value);
    } else {
        logModalAluno('📝 Criando novo aluno (sem ID)');
    }
    
    // Adicionar foto se houver
    const fotoInput = document.getElementById('foto');
    if (fotoInput && fotoInput.files && fotoInput.files[0]) {
        console.log('📷 Arquivo de foto encontrado:', fotoInput.files[0]);
        dadosFormData.append('foto', fotoInput.files[0]);
    } else {
        console.log('📷 Nenhum arquivo de foto selecionado');
    }
    
    // Determinar se é criação ou edição
    const acao = formData.get('acao');
    const alunoId = formData.get('aluno_id');
    
    if (acao === 'editar' && alunoId) {
        dadosFormData.append('id', alunoId);
    }
    
    // Fazer requisição para a API
    console.log('📤 Enviando dados para API via FormData');
    console.log('📤 Operações coletadas:', operacoes);
    console.log('📤 Ação:', acao);
    logModalAluno('📤 Aluno ID:', alunoId);
    
    // Debug FormData
    console.log('📤 FormData contents:');
    for (let [key, value] of dadosFormData.entries()) {
        console.log(`  ${key}:`, value);
    }
    
    const timestamp = new Date().getTime();
    fetch(`api/alunos.php?t=${timestamp}`, {
        method: 'POST',
        body: dadosFormData // Usar FormData em vez de JSON
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
            // Restaurar botão antes de fechar modal
            const btnSalvar = document.getElementById('btnSalvarAluno');
            if (btnSalvar) {
                const textoOriginal = btnSalvar.innerHTML;
                btnSalvar.innerHTML = textoOriginal;
                btnSalvar.disabled = false;
            }
            
            // Mostrar notificação discreta (sem alert)
            mostrarAlerta(data.message || 'Aluno salvo com sucesso!', 'success');
            
            // Obter aluno_id da resposta (pode ser data.aluno_id ou data.id)
            const alunoId = data.aluno_id || data.id || alunoIdHidden?.value;
            
            // Fechar modal automaticamente
            fecharModalAluno();
            
            // Atualizar lista de alunos silenciosamente (sem mostrar "Filtro aplicado...")
            if (typeof filtrarAlunos === 'function') {
                filtrarAlunos({ silencioso: true });
            }
            
            // Sincronizar matrícula principal após salvar aluno (em background)
            if (alunoId) {
                // Usar setTimeout para não bloquear o fluxo principal
                setTimeout(() => {
                    sincronizarMatriculaPrincipal(alunoId, dadosFormData);
                }, 100);
            } else {
                console.warn('⚠️ Aluno ID não encontrado na resposta, não será possível sincronizar matrícula');
            }
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

/**
 * Sincroniza a matrícula principal do aluno com a tabela matriculas
 * @param {number} alunoId - ID do aluno
 * @param {FormData} formData - Dados do formulário (FormData original)
 */
async function sincronizarMatriculaPrincipal(alunoId, formData) {
    try {
        logModalAluno('🔄 Iniciando sincronização de matrícula principal para aluno:', alunoId);
        
        // Extrair primeira operação do formulário
        const operacoes = coletarDadosOperacoes();
        
        if (!operacoes || operacoes.length === 0) {
            logModalAluno('⚠️ Nenhuma operação encontrada, não será possível sincronizar matrícula');
            return;
        }
        
        const primeiraOperacao = operacoes[0];
        const tipoServico = primeiraOperacao.tipo;
        const categoriaCnh = primeiraOperacao.categoria;
        
        if (!tipoServico || !categoriaCnh) {
            logModalAluno('⚠️ Primeira operação incompleta (tipo_servico ou categoria_cnh faltando), não será possível sincronizar matrícula');
            return;
        }
        
        // Extrair outros campos do formulário
        const dataMatricula = formData.get('data_matricula') || '';
        const dataConclusao = formData.get('data_conclusao') || null;
        const statusMatricula = formData.get('status_matricula') || 'ativa';
        // Campos financeiros removidos - valorCurso e formaPagamento
        // Agora os dados financeiros vêm apenas do módulo Financeiro do Aluno
        
        // Verificar se já existe matrícula para este aluno
        const responseGet = await fetch(`api/matriculas.php?aluno_id=${alunoId}`);
        const dataGet = await responseGet.json();
        
        if (!dataGet.success) {
            console.error('❌ Erro ao buscar matrículas existentes:', dataGet.error);
            return;
        }
        
        const matriculasExistentes = dataGet.matriculas || [];
        const matriculaExistente = matriculasExistentes.length > 0 ? matriculasExistentes[0] : null;
        
        // Preparar dados para envio
        const dadosMatricula = {
            aluno_id: alunoId,
            tipo_servico: tipoServico,
            categoria_cnh: categoriaCnh,
            data_inicio: dataMatricula || null,
            data_fim: dataConclusao || null,
            status: statusMatricula,
            // Campos financeiros removidos - valor_total e forma_pagamento
            // Agora os dados financeiros vêm apenas do módulo Financeiro do Aluno
            observacoes: 'Criado via formulário de aluno'
        };
        
        // Remover campos null/undefined
        Object.keys(dadosMatricula).forEach(key => {
            if (dadosMatricula[key] === null || dadosMatricula[key] === undefined || dadosMatricula[key] === '') {
                delete dadosMatricula[key];
            }
        });
        
        let response;
        let data;
        
        if (matriculaExistente) {
            // Atualizar matrícula existente
            logModalAluno('📝 Atualizando matrícula existente ID:', matriculaExistente.id);
            
            response = await fetch(`api/matriculas.php?id=${matriculaExistente.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dadosMatricula)
            });
            
            data = await response.json();
            
            if (data.success) {
                console.log('✅ Matrícula sincronizada (atualizada)', data);
            } else {
                console.error('❌ Erro ao atualizar matrícula:', data.error);
            }
        } else {
            // Criar nova matrícula
            logModalAluno('➕ Criando nova matrícula');
            
            response = await fetch('api/matriculas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(dadosMatricula)
            });
            
            data = await response.json();
            
            if (data.success) {
                console.log('✅ Matrícula sincronizada (criada)', data);
            } else {
                console.error('❌ Erro ao criar matrícula:', data.error);
            }
        }
        
        // TODO: futuramente exibir feedback visual da matrícula (cards da aba Histórico e Detalhes do Aluno)
        
    } catch (error) {
        console.error('❌ Erro ao sincronizar matrícula principal:', error);
        // Não bloquear o fluxo - apenas logar o erro
    }
}

/**
 * Carrega APENAS o resumo da matrícula principal para atualizar cards de visualização
 * Não mexe no formulário da aba Matrícula
 * @param {number} alunoId - ID do aluno
 */
async function carregarResumoMatriculaParaVisualizacao(alunoId) {
    try {
        logModalAluno('📥 Carregando resumo de matrícula para visualização do aluno:', alunoId);
        
        const response = await fetch(`api/matriculas.php?aluno_id=${alunoId}`);
        const data = await response.json();
        
        if (!data.success) {
            console.error('❌ Erro ao buscar matrículas (visualização):', data.error);
            contextoAlunoAtual.matriculaId = null;
            atualizarResumoProcessoHistorico(null);
            atualizarResumoFinanceiroAluno(alunoId, null);
            atualizarResumoTeoricoAluno(alunoId);
            atualizarResumoPraticoAluno(alunoId);
            
            // Registrar eventos dos atalhos mesmo com erro
            setTimeout(() => {
                registrarEventosAtalhosAluno();
            }, 500);
            return;
        }
        
        const matriculas = data.matriculas || [];
        
        if (matriculas.length === 0) {
            logModalAluno('⚠️ Nenhuma matrícula encontrada para visualização do aluno:', alunoId);
            contextoAlunoAtual.matriculaId = null;
            atualizarResumoProcessoHistorico(null);
            atualizarResumoFinanceiroAluno(alunoId, null);
            atualizarResumoTeoricoAluno(alunoId);
            atualizarResumoPraticoAluno(alunoId);
            
            // Registrar eventos dos atalhos mesmo sem matrícula
            setTimeout(() => {
                registrarEventosAtalhosAluno();
            }, 500);
            return;
        }
        
        // Usar sempre a primeira matrícula (matrícula principal)
        const matricula = matriculas[0];
        logModalAluno('✅ Matrícula principal (visualização) encontrada:', matricula);
        
        // Preencher contexto com matrícula
        contextoAlunoAtual.matriculaId = matricula.id || null;
        
        // Atualizar apenas o card de resumo (não mexe no formulário)
        atualizarResumoProcessoHistorico(matricula);
        
        // Atualizar resumo financeiro
        atualizarResumoFinanceiroAluno(alunoId, matricula);
        
        // Atualizar resumo teórico
        atualizarResumoTeoricoAluno(alunoId);
        
        // Atualizar resumo prático
        atualizarResumoPraticoAluno(alunoId);
        
        // Atualizar resumo de provas
        atualizarResumoProvasAluno(alunoId);
        
        // Registrar eventos dos atalhos após carregar dados
        setTimeout(() => {
            registrarEventosAtalhosAluno();
        }, 500);
        
    } catch (error) {
        console.error('❌ Erro ao carregar resumo de matrícula para visualização:', error);
        atualizarResumoProcessoHistorico(null);
        atualizarResumoFinanceiroAluno(alunoId, null);
        atualizarResumoTeoricoAluno(alunoId);
        atualizarResumoPraticoAluno(alunoId);
        atualizarResumoProvasAluno(alunoId);
        
        // Registrar eventos dos atalhos mesmo com erro
        setTimeout(() => {
            registrarEventosAtalhosAluno();
        }, 500);
    }
}

/**
 * Carrega a matrícula principal do aluno e preenche a aba Matrícula e atualiza o card de Histórico
 * @param {number} alunoId - ID do aluno
 */
async function carregarMatriculaPrincipal(alunoId, dadosFallback = null) {
    try {
        logModalAluno('📥 Carregando matrícula principal para aluno:', alunoId);
        
        const response = await fetch(`api/matriculas.php?aluno_id=${alunoId}`);
        
        // Verificar se a resposta é JSON válido
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            const text = await response.text();
            console.error('[DEBUG MATRICULA] Resposta não é JSON. Conteúdo:', text.substring(0, 200));
            throw new Error('A API retornou HTML em vez de JSON. Verifique se há erros no servidor.');
        }
        
        const data = await response.json();
        
        // Log completo da resposta
        console.log('[DEBUG MATRICULA] Resposta completa da API matriculas.php:', JSON.stringify(data, null, 2));
        
        if (!data.success) {
            console.error('❌ Erro ao buscar matrículas:', data.error);
            // Se não houver matrícula, limpar campos
            contextoAlunoAtual.matriculaId = null;
            limparAbaMatricula();
            atualizarResumoProcessoHistorico(null);
            // Atualizar resumos em background (não bloquear)
            setTimeout(() => {
                atualizarResumoTeoricoAluno(alunoId).catch(() => {});
                atualizarResumoPraticoAluno(alunoId).catch(() => {});
                atualizarResumoProvasAluno(alunoId).catch(() => {});
            }, 100);
            
            // Registrar eventos dos atalhos mesmo com erro
            setTimeout(() => {
                registrarEventosAtalhosAluno();
            }, 500);
            return;
        }
        
        const matriculas = data.matriculas || [];
        
        if (matriculas.length === 0) {
            logModalAluno('⚠️ Nenhuma matrícula encontrada para o aluno');
            contextoAlunoAtual.matriculaId = null;
            limparAbaMatricula();
            atualizarResumoProcessoHistorico(null);
            // Atualizar resumos em background (não bloquear)
            setTimeout(() => {
                atualizarResumoTeoricoAluno(alunoId).catch(() => {});
                atualizarResumoPraticoAluno(alunoId).catch(() => {});
                atualizarResumoProvasAluno(alunoId).catch(() => {});
            }, 100);
            
            // Registrar eventos dos atalhos mesmo sem matrícula
            setTimeout(() => {
                registrarEventosAtalhosAluno();
            }, 500);
            return;
        }
        
        // Usar sempre a primeira matrícula (matrícula principal)
        let matricula = matriculas[0];
        logModalAluno('✅ Matrícula principal encontrada:', matricula);
        
        // Se os campos problemáticos estiverem undefined/null e tivermos dados de fallback, usar fallback
        if (dadosFallback) {
            if ((matricula.aulas_praticas_contratadas === undefined || matricula.aulas_praticas_contratadas === null) && dadosFallback.aulas_praticas_contratadas !== undefined && dadosFallback.aulas_praticas_contratadas !== null) {
                matricula.aulas_praticas_contratadas = dadosFallback.aulas_praticas_contratadas;
                console.log('[DEBUG MATRICULA] Usando fallback para aulas_praticas_contratadas:', dadosFallback.aulas_praticas_contratadas);
            }
            if ((matricula.aulas_praticas_extras === undefined || matricula.aulas_praticas_extras === null) && dadosFallback.aulas_praticas_extras !== undefined && dadosFallback.aulas_praticas_extras !== null) {
                matricula.aulas_praticas_extras = dadosFallback.aulas_praticas_extras;
                console.log('[DEBUG MATRICULA] Usando fallback para aulas_praticas_extras:', dadosFallback.aulas_praticas_extras);
            }
            // Campos financeiros removidos - forma_pagamento não é mais usado
        }
        
        // Log completo para debug dos campos problemáticos
        console.log('[DEBUG MATRICULA] Dados recebidos da API matriculas.php (APÓS FALLBACK):', {
            aulas_praticas_contratadas: matricula.aulas_praticas_contratadas,
            aulas_praticas_extras: matricula.aulas_praticas_extras,
            // Campos financeiros removidos - forma_pagamento não é mais usado
            matricula_completa: matricula,
            todas_as_chaves: Object.keys(matricula)
        });
        
        // Log detalhado de cada campo
        console.log('[DEBUG MATRICULA] Verificação detalhada:', {
            'matricula.aulas_praticas_contratadas': matricula.aulas_praticas_contratadas,
            'typeof aulas_praticas_contratadas': typeof matricula.aulas_praticas_contratadas,
            'aulas_praticas_contratadas in matricula': 'aulas_praticas_contratadas' in matricula,
            'matricula.aulas_praticas_extras': matricula.aulas_praticas_extras,
            'typeof aulas_praticas_extras': typeof matricula.aulas_praticas_extras,
            'aulas_praticas_extras in matricula': 'aulas_praticas_extras' in matricula
            // Campos financeiros removidos - forma_pagamento não é mais usado
        });
        
        // Preencher contexto com matrícula
        contextoAlunoAtual.matriculaId = matricula.id || null;
        
        // Preencher aba Matrícula
        preencherAbaMatriculaComDados(matricula);
        
        // Atualizar card de Histórico
        atualizarResumoProcessoHistorico(matricula);
        
        // Atualizar resumo financeiro
        atualizarResumoFinanceiroAluno(alunoId, matricula);
        
        // Atualizar resumo teórico
        atualizarResumoTeoricoAluno(alunoId);
        
        // Atualizar resumo prático
        atualizarResumoPraticoAluno(alunoId);
        
        // Atualizar resumo de provas
        atualizarResumoProvasAluno(alunoId);
        
        // Registrar eventos dos atalhos após carregar dados
        setTimeout(() => {
            registrarEventosAtalhosAluno();
        }, 500);
        
    } catch (error) {
        console.error('❌ Erro ao carregar matrícula principal:', error);
        // Não bloquear o fluxo - apenas logar o erro
        limparAbaMatricula();
        atualizarResumoProcessoHistorico(null);
        // Atualizar resumos em background (não bloquear)
        setTimeout(() => {
            atualizarResumoFinanceiroAluno(alunoId, null).catch(() => {});
            atualizarResumoFinanceiroMatricula(alunoId).catch(() => {});
            atualizarResumoTeoricoAluno(alunoId).catch(() => {});
            atualizarResumoPraticoAluno(alunoId).catch(() => {});
            atualizarResumoProvasAluno(alunoId).catch(() => {});
        }, 100);
    }
}

/**
 * Preenche a aba Matrícula com os dados da matrícula
 * @param {Object} matricula - Objeto com os dados da matrícula
 */
function preencherAbaMatriculaComDados(matricula) {
    try {
        logModalAluno('📝 Preenchendo aba Matrícula com dados:', matricula);
        
        // 1) Preencher operação principal
        if (matricula.categoria_cnh && matricula.tipo_servico) {
            // Limpar operações existentes
            const operacoesContainer = document.getElementById('operacoes-container');
            if (operacoesContainer) {
                operacoesContainer.innerHTML = '';
                contadorOperacoes = 0;
                
                // Adicionar uma nova operação
                adicionarOperacao();
                
                // Aguardar um pouco para o DOM atualizar
                setTimeout(() => {
                    // Selecionar a primeira operação criada
                    const primeiraOperacao = document.querySelector('.operacao-item[data-operacao-id]');
                    if (primeiraOperacao) {
                        const operacaoId = primeiraOperacao.getAttribute('data-operacao-id');
                        const tipoSelect = document.querySelector(`select[name="operacao_tipo_${operacaoId}"]`);
                        const categoriaSelect = document.querySelector(`select[name="operacao_categoria_${operacaoId}"]`);
                        
                        if (tipoSelect && categoriaSelect) {
                            // Definir tipo de serviço
                            tipoSelect.value = matricula.tipo_servico;
                            
                            // Carregar categorias baseado no tipo e selecionar a categoria
                            carregarCategoriasOperacao(operacaoId, matricula.categoria_cnh, matricula.tipo_servico);
                            
                            logModalAluno('✅ Operação principal preenchida:', {
                                tipo: matricula.tipo_servico,
                                categoria: matricula.categoria_cnh
                            });
                        }
                    }
                }, 100);
            }
        }
        
        // 2) Preencher datas
        if (matricula.data_inicio) {
            const dataMatriculaInput = document.getElementById('data_matricula');
            if (dataMatriculaInput) {
                // Converter formato de data se necessário (YYYY-MM-DD já é compatível com input date)
                dataMatriculaInput.value = matricula.data_inicio;
                logModalAluno('✅ Data matrícula preenchida:', matricula.data_inicio);
            }
        }
        
        // Data de conclusão - somente leitura (preenchida automaticamente)
        const dataConclusaoInput = document.getElementById('data_conclusao');
        if (dataConclusaoInput) {
            if (matricula.data_fim) {
                dataConclusaoInput.value = matricula.data_fim;
                logModalAluno('✅ Data conclusão preenchida:', matricula.data_fim);
            }
            // Tornar campo readonly com placeholder explicativo
            dataConclusaoInput.readOnly = true;
            dataConclusaoInput.placeholder = 'Preenchida automaticamente quando a matrícula for concluída';
            dataConclusaoInput.title = 'Este campo é preenchido automaticamente quando o status da matrícula muda para "Concluída"';
        }
        
        // Preencher RENACH da matrícula
        if (matricula.renach) {
            const renachField = document.getElementById('renach');
            if (renachField) {
                renachField.value = matricula.renach;
                logModalAluno('✅ RENACH da matrícula preenchido:', matricula.renach);
            }
        }
        
        // Preencher campos do processo DETRAN
        if (matricula.processo_numero) {
            const processoNumeroInput = document.getElementById('processo_numero');
            if (processoNumeroInput) {
                processoNumeroInput.value = matricula.processo_numero;
            }
        }
        
        if (matricula.processo_numero_detran) {
            const processoNumeroDetranInput = document.getElementById('processo_numero_detran');
            if (processoNumeroDetranInput) {
                processoNumeroDetranInput.value = matricula.processo_numero_detran;
            }
        }
        
        if (matricula.processo_situacao) {
            const processoSituacaoSelect = document.getElementById('processo_situacao');
            if (processoSituacaoSelect) {
                processoSituacaoSelect.value = matricula.processo_situacao;
            }
        }
        
        // Preencher Previsão de Conclusão
        if (matricula.previsao_conclusao) {
            const previsaoConclusaoInput = document.getElementById('previsao_conclusao');
            if (previsaoConclusaoInput) {
                previsaoConclusaoInput.value = matricula.previsao_conclusao;
                logModalAluno('✅ Previsão de conclusão preenchida:', matricula.previsao_conclusao);
            }
        }
        
        // Preencher Aulas Práticas Contratadas
        // Log para debug completo
        console.log('[DEBUG MATRICULA FILL] Dados completos recebidos:', matricula);
        console.log('[DEBUG MATRICULA FILL] aulas_praticas_contratadas recebido:', matricula.aulas_praticas_contratadas);
        if (matricula.aulas_praticas_contratadas !== undefined && matricula.aulas_praticas_contratadas !== null) {
            const aulasContratadasInput = document.getElementById('aulas_praticas_contratadas');
            if (aulasContratadasInput) {
                aulasContratadasInput.value = matricula.aulas_praticas_contratadas;
                logModalAluno('✅ Aulas práticas contratadas preenchidas:', matricula.aulas_praticas_contratadas);
                console.log('[DEBUG MATRICULA FILL] ✅ Campo aulas_praticas_contratadas preenchido com valor:', matricula.aulas_praticas_contratadas);
            } else {
                console.warn('[DEBUG MATRICULA] Campo aulas_praticas_contratadas não encontrado no DOM');
            }
        } else {
            console.log('[DEBUG MATRICULA] aulas_praticas_contratadas está undefined ou null');
        }
        
        // Preencher Aulas Extras
        // Log para debug
        console.log('[DEBUG MATRICULA FILL] aulas_praticas_extras recebido:', matricula.aulas_praticas_extras);
        if (matricula.aulas_praticas_extras !== undefined && matricula.aulas_praticas_extras !== null) {
            const aulasExtrasInput = document.getElementById('aulas_praticas_extras');
            if (aulasExtrasInput) {
                aulasExtrasInput.value = matricula.aulas_praticas_extras;
                logModalAluno('✅ Aulas extras preenchidas:', matricula.aulas_praticas_extras);
                console.log('[DEBUG MATRICULA FILL] ✅ Campo aulas_praticas_extras preenchido com valor:', matricula.aulas_praticas_extras);
            } else {
                console.warn('[DEBUG MATRICULA] Campo aulas_praticas_extras não encontrado no DOM');
            }
        } else {
            console.log('[DEBUG MATRICULA] aulas_praticas_extras está undefined ou null');
        }
        
        // 3) Preencher status
        if (matricula.status) {
            const statusSelect = document.getElementById('status_matricula');
            if (statusSelect) {
                // Mapear valores da API para opções do select
                const statusMap = {
                    'ativa': 'ativa',
                    'em_formacao': 'em_formacao',
                    'em_andamento': 'em_formacao',
                    'concluida': 'concluida',
                    'cancelada': 'cancelada',
                    'trancada': 'trancada',
                    'em_analise': 'em_analise',
                    'em_exame': 'em_exame'
                };
                
                const statusMapeado = statusMap[matricula.status] || matricula.status;
                statusSelect.value = statusMapeado;
                logModalAluno('✅ Status matrícula preenchido:', statusMapeado);
            }
        }
        
        // 4) Campos financeiros removidos - agora o resumo é carregado via API financeiro-resumo-matricula.php
        // Os dados financeiros vêm diretamente do módulo Financeiro do Aluno (faturas/parcelas)
        
        // 5) Carregar resumo financeiro do aluno na aba Matrícula
        // Obter alunoId do campo hidden ou da matrícula
        const alunoIdField = document.getElementById('aluno_id_hidden');
        const alunoId = alunoIdField?.value || matricula?.aluno_id || document.getElementById('editar_aluno_id')?.value;
        
        // Atualizar campo oculto editar_aluno_id se necessário
        const editarAlunoIdField = document.getElementById('editar_aluno_id');
        if (editarAlunoIdField && alunoId) {
            editarAlunoIdField.value = alunoId;
        }
        
        // Carregar resumo financeiro (será executado quando a aba for mostrada também)
        if (alunoId) {
            logModalAluno('💰 Carregando resumo financeiro para aluno (aba Matrícula):', alunoId);
            atualizarResumoFinanceiroMatricula(alunoId);
        } else {
            logModalAluno('⚠️ AlunoId não encontrado para carregar resumo financeiro');
        }
        
        logModalAluno('✅ Aba Matrícula preenchida com sucesso');
        
    } catch (error) {
        console.error('❌ Erro ao preencher aba Matrícula:', error);
    }
}

/**
 * Limpa os campos da aba Matrícula
 */
function limparAbaMatricula() {
    try {
        logModalAluno('🧹 Limpando aba Matrícula');
        
        // Limpar operações
        const operacoesContainer = document.getElementById('operacoes-container');
        if (operacoesContainer) {
            operacoesContainer.innerHTML = '';
            contadorOperacoes = 0;
        }
        
        // Limpar campos de data
        const dataMatriculaInput = document.getElementById('data_matricula');
        if (dataMatriculaInput) dataMatriculaInput.value = '';
        
        const dataConclusaoInput = document.getElementById('data_conclusao');
        if (dataConclusaoInput) dataConclusaoInput.value = '';
        
        // Limpar status
        const statusSelect = document.getElementById('status_matricula');
        if (statusSelect) statusSelect.value = '';
        
        // Campos financeiros removidos - não há mais campos para limpar
        
        logModalAluno('✅ Aba Matrícula limpa');
        
    } catch (error) {
        console.error('❌ Erro ao limpar aba Matrícula:', error);
    }
}

/**
 * Helper para fazer fetch com timeout
 * @param {string} url - URL para fazer fetch
 * @param {Object} options - Opções do fetch (method, headers, body, etc)
 * @param {number} timeout - Timeout em milissegundos (padrão: 10000 = 10s)
 * @returns {Promise<Response>}
 */
async function fetchWithTimeout(url, options = {}, timeout = 10000) {
    const controller = new AbortController();
    const id = setTimeout(() => controller.abort(), timeout);
    
    // Registrar controller para possível cancelamento quando modal fechar
    activeAbortControllers.push(controller);
    
    try {
        const response = await fetch(url, {
            ...options,
            signal: controller.signal
        });
        clearTimeout(id);
        // Remover controller da lista quando requisição completar
        const index = activeAbortControllers.indexOf(controller);
        if (index > -1) {
            activeAbortControllers.splice(index, 1);
        }
        return response;
    } catch (error) {
        clearTimeout(id);
        // Remover controller da lista mesmo em caso de erro
        const index = activeAbortControllers.indexOf(controller);
        if (index > -1) {
            activeAbortControllers.splice(index, 1);
        }
        if (error.name === 'AbortError') {
            throw new Error(`Timeout: A requisição demorou mais de ${timeout}ms`);
        }
        throw error;
    }
}

/**
 * Atualiza o card "Situação Financeira" na aba Histórico e no modal de visualização
 * Atualiza TODOS os elementos com data-field="financeiro_resumo"
 * Usa a nova API financeiro-resumo-aluno.php que retorna resumo completo
 * @param {number} alunoId - ID do aluno
 * @param {Object|null} matricula - Objeto da matrícula principal (opcional, não usado mais mas mantido para compatibilidade)
 */
async function atualizarResumoFinanceiroAluno(alunoId, matricula = null) {
    try {
        logModalAluno('💰 Carregando resumo financeiro para aluno:', alunoId);
        
        // Usar nova API de resumo financeiro com timeout de 15 segundos
        const url = `api/financeiro-resumo-aluno.php?aluno_id=${alunoId}`;
        const response = await fetchWithTimeout(url, {}, 15000);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        // Verificar se a resposta é JSON antes de fazer parse
        const contentType = response.headers.get('content-type');
        if (!contentType || !contentType.includes('application/json')) {
            // Se não for JSON, ler como texto para ver o erro
            const text = await response.text();
            console.error('❌ Resposta não é JSON. Conteúdo recebido:', text.substring(0, 200));
            throw new Error('A API retornou HTML em vez de JSON. Verifique se há erros no servidor.');
        }
        
        const data = await response.json();
        
        if (!data.success) {
            console.error('❌ Erro ao buscar resumo financeiro:', data.error);
            // Em caso de erro, mostrar mensagem de erro nos cards
            const cardElements = document.querySelectorAll('[data-field="financeiro_resumo"]');
            cardElements.forEach((cardEl) => {
                const isCardHistorico = cardEl.id === 'card-situacao-financeira-historico';
                if (isCardHistorico) {
                    cardEl.innerHTML = `
                        <div class="text-center">
                            <p class="text-muted small mb-0" style="font-size: 0.7rem;">
                                Não foi possível carregar a situação financeira.
                            </p>
                        </div>
                    `;
                } else {
                    cardEl.innerHTML = `
                        <div class="text-center">
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                Não foi possível carregar a situação financeira.
                            </p>
                        </div>
                    `;
                }
            });
            return;
        }
        
        const resumo = data.resumo || {};
        logModalAluno('✅ Resumo financeiro carregado:', resumo);
        
        // Atualizar todos os cards com o resumo completo
        atualizarCardsFinanceiroResumo(resumo, alunoId);
        
    } catch (error) {
        // Log apenas se não for timeout (timeouts são esperados e não devem poluir o console)
        if (!error.message || !error.message.includes('Timeout')) {
            console.warn('⚠️ Erro ao carregar resumo financeiro (não bloqueante):', error.message || error);
        }
        
        // Log detalhado do erro para debug apenas se for erro de servidor
        if (error.message && error.message.includes('HTML')) {
            console.error('⚠️ A API retornou HTML em vez de JSON. Isso geralmente indica um erro PHP no servidor.');
        }
        
        // Em caso de erro, mostrar mensagem de erro nos cards (silenciosamente)
        try {
            const cardElements = document.querySelectorAll('[data-field="financeiro_resumo"]');
            cardElements.forEach((cardEl) => {
                const isCardHistorico = cardEl.id === 'card-situacao-financeira-historico';
                if (isCardHistorico) {
                    cardEl.innerHTML = `
                        <div class="text-center">
                            <p class="text-muted small mb-0" style="font-size: 0.7rem;">
                                Não foi possível carregar a situação financeira.
                            </p>
                        </div>
                    `;
                } else {
                    cardEl.innerHTML = `
                        <div class="text-center">
                            <p class="text-muted small mb-0" style="font-size: 0.75rem;">
                                Não foi possível carregar a situação financeira.
                            </p>
                        </div>
                    `;
                }
            });
        } catch (domError) {
            // Ignorar erros de DOM silenciosamente
        }
    }
}

/**
 * Atualiza o resumo financeiro na aba Matrícula
 * Usa endpoint PHP que retorna HTML renderizado diretamente
 * @param {number} alunoId - ID do aluno
 */
async function atualizarResumoFinanceiroMatricula(alunoId) {
    try {
        if (!alunoId) {
            return;
        }
        
        // Atualizar o card de resumo financeiro da matrícula (novo)
        const cardContainer = document.getElementById('resumo-financeiro-matricula-card');
        if (cardContainer) {
            // Mostrar loading
            cardContainer.innerHTML = '<div class="text-center text-muted small"><i class="fas fa-spinner fa-spin"></i> Carregando resumo financeiro...</div>';
            
            // Buscar dados detalhados do resumo financeiro da matrícula com timeout de 15 segundos
            const url = `api/financeiro-resumo-matricula.php?aluno_id=${alunoId}`;
            const response = await fetchWithTimeout(url, {}, 15000);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.error || 'Erro ao buscar resumo financeiro');
            }
            
            // Renderizar o card baseado no estado (com ou sem financeiro)
            if (!data.tem_financeiro || !data.resumo) {
                // Estado sem plano financeiro configurado
                cardContainer.innerHTML = `
                    <div class="text-center">
                        <div class="alert alert-warning mb-3" role="alert">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Nenhum plano financeiro foi configurado para esta matrícula ainda.</strong>
                        </div>
                        <p class="text-muted small mb-3">
                            Para gerar o contrato, é necessário configurar primeiro o financeiro do aluno.
                        </p>
                        <div class="d-flex gap-2 justify-content-center">
                            <button type="button" class="btn btn-primary btn-sm" onclick="abrirFinanceiroAluno(${alunoId})">
                                <i class="fas fa-cog me-1"></i>Configurar financeiro da matrícula
                            </button>
                            <a href="index.php?page=financeiro-faturas&aluno_id=${alunoId}" target="_blank" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-external-link-alt me-1"></i>Ver Financeiro do Aluno
                            </a>
                        </div>
                    </div>
                `;
            } else {
                // Estado com plano financeiro configurado
                const resumo = data.resumo;
                
                // Formatar valores monetários
                const formatarMoeda = (valor) => {
                    return new Intl.NumberFormat('pt-BR', {
                        style: 'currency',
                        currency: 'BRL'
                    }).format(valor || 0);
                };
                
                // Formatar data
                const formatarData = (data) => {
                    if (!data) return null;
                    const d = new Date(data);
                    return d.toLocaleDateString('pt-BR');
                };
                
                let html = '<div>';
                
                // Valor total do curso
                html += `<p class="mb-2"><strong>Valor total do curso:</strong> ${formatarMoeda(resumo.valor_total)}</p>`;
                
                // Entrada (se existir)
                if (resumo.tem_entrada && resumo.valor_entrada > 0) {
                    html += `<p class="mb-2"><strong>Entrada:</strong> ${formatarMoeda(resumo.valor_entrada)}</p>`;
                }
                
                // Parcelas (se existirem)
                if (resumo.qtd_parcelas > 0) {
                    html += `<p class="mb-2"><strong>Parcelas:</strong> ${resumo.qtd_parcelas}× de ${formatarMoeda(resumo.valor_parcela)}</p>`;
                }
                
                // Forma de pagamento predominante
                html += `<p class="mb-2"><strong>Forma de pagamento predominante:</strong> ${resumo.forma_pagamento_texto || 'Não informado'}</p>`;
                
                // Primeiro vencimento (se aplicável)
                if (resumo.primeiro_vencimento) {
                    html += `<p class="mb-2"><strong>Primeiro vencimento:</strong> ${formatarData(resumo.primeiro_vencimento)}</p>`;
                }
                
                html += '</div>';
                
                // Link para ver detalhes
                html += `
                    <div class="mt-3 pt-3 border-top">
                        <a href="index.php?page=financeiro-faturas&aluno_id=${alunoId}" target="_blank" class="btn btn-link btn-sm p-0" style="font-size: 0.85rem;">
                            <i class="fas fa-external-link-alt me-1"></i>Ver detalhes no Financeiro do Aluno
                        </a>
                    </div>
                `;
                
                cardContainer.innerHTML = html;
            }
        }
        
        // Atualizar também o resumo financeiro antigo (para compatibilidade)
        const container = document.getElementById('resumo-financeiro-matricula');
        if (container) {
            // Buscar HTML renderizado diretamente do PHP
            const url = `api/financeiro-resumo-aluno-html.php?aluno_id=${alunoId}`;
            const response = await fetch(url);
            
            if (response.ok) {
                const html = await response.text();
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-center text-danger small">Erro ao carregar resumo financeiro.</div>';
            }
        }
        
        logModalAluno('✅ Resumo financeiro carregado na aba Matrícula');
        
    } catch (error) {
        console.error('❌ Erro ao carregar resumo financeiro na aba Matrícula:', error);
        const cardContainer = document.getElementById('resumo-financeiro-matricula-card');
        if (cardContainer) {
            cardContainer.innerHTML = '<div class="text-center text-danger small">Erro ao carregar resumo financeiro.</div>';
        }
        const container = document.getElementById('resumo-financeiro-matricula');
        if (container) {
            container.innerHTML = '<div class="text-center text-danger small">Erro ao carregar resumo financeiro.</div>';
        }
    }
}

/**
 * Função para abrir a tela de financeiro do aluno
 * @param {number} alunoId - ID do aluno
 */
function abrirFinanceiroAluno(alunoId) {
    if (!alunoId) {
        mostrarAlerta('ID do aluno não encontrado', 'danger');
        return;
    }
    
    // Abrir em nova aba
    const url = `index.php?page=financeiro-faturas&aluno_id=${alunoId}`;
    window.open(url, '_blank');
    
    // Opcional: mostrar mensagem
    mostrarAlerta('Abrindo tela de Financeiro do Aluno em nova aba...', 'info');
}

/**
 * Verifica se o aluno tem financeiro configurado (pré-requisito para gerar contrato)
 * @param {number} alunoId - ID do aluno
 * @returns {Promise<{temFinanceiro: boolean, resumo: Object|null, mensagem: string}>}
 */
async function verificarFinanceiroParaContrato(alunoId) {
    try {
        if (!alunoId) {
            return {
                temFinanceiro: false,
                resumo: null,
                mensagem: 'ID do aluno não fornecido'
            };
        }
        
        const url = `api/financeiro-resumo-matricula.php?aluno_id=${alunoId}`;
        const response = await fetch(url);
        
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        const data = await response.json();
        
        if (!data.success) {
            return {
                temFinanceiro: false,
                resumo: null,
                mensagem: data.error || 'Erro ao verificar financeiro'
            };
        }
        
        if (!data.tem_financeiro || !data.resumo) {
            return {
                temFinanceiro: false,
                resumo: null,
                mensagem: 'Não foi possível gerar o contrato.\nConfigure primeiro o financeiro da matrícula no módulo Financeiro do Aluno.'
            };
        }
        
        return {
            temFinanceiro: true,
            resumo: data.resumo,
            mensagem: 'Financeiro configurado'
        };
        
    } catch (error) {
        console.error('❌ Erro ao verificar financeiro para contrato:', error);
        return {
            temFinanceiro: false,
            resumo: null,
            mensagem: 'Erro ao verificar financeiro do aluno'
        };
    }
}

/**
 * Função helper para gerar contrato (deve ser chamada quando o botão de gerar contrato for clicado)
 * Esta função verifica o pré-requisito de financeiro configurado antes de permitir a geração
 * @param {number} alunoId - ID do aluno
 * @param {Function} callbackGerarContrato - Função que será chamada se o financeiro estiver OK
 */
async function gerarContratoComValidacao(alunoId, callbackGerarContrato) {
    if (!alunoId) {
        mostrarAlerta('ID do aluno não encontrado', 'danger');
        return;
    }
    
    // Verificar se tem financeiro configurado
    const verificacao = await verificarFinanceiroParaContrato(alunoId);
    
    if (!verificacao.temFinanceiro) {
        // Mostrar mensagem de erro e opção para configurar financeiro
        mostrarAlerta(verificacao.mensagem, 'warning');
        
        // Opcional: perguntar se quer abrir a tela de financeiro
        if (confirm('Deseja abrir a tela de Financeiro do Aluno para configurar?')) {
            abrirFinanceiroAluno(alunoId);
        }
        
        return;
    }
    
    // Se chegou aqui, tem financeiro configurado - chamar callback
    if (typeof callbackGerarContrato === 'function') {
        callbackGerarContrato(alunoId, verificacao.resumo);
    } else {
        console.warn('⚠️ Função de gerar contrato não fornecida');
    }
}

/**
 * Atualiza todos os elementos com data-field="financeiro_resumo" com o resumo financeiro
 * @param {Object|null} resumo - Objeto de resumo financeiro ou null para valores padrão
 * @param {number} alunoId - ID do aluno (para link "Ver Financeiro")
 */
function atualizarCardsFinanceiroResumo(resumo, alunoId) {
    try {
        const cardElements = document.querySelectorAll('[data-field="financeiro_resumo"]');
        
        if (cardElements.length === 0) {
            logModalAluno('⚠️ Nenhum card financeiro_resumo encontrado');
            return;
        }
        
        // Se resumo for null, usar valores padrão
        if (!resumo) {
            resumo = {
                qtd_faturas: 0,
                status_financeiro: 'nao_lancado',
                total_contratado: 0,
                total_pago: 0,
                saldo_aberto: 0,
                qtd_faturas_vencidas: 0,
                proximo_vencimento: null
            };
        }
        
        // Mapear status para labels e classes
        const statusMap = {
            'nao_lancado': { label: 'Não lançado', class: 'secondary' },
            'em_dia': { label: 'Em dia', class: 'success' },
            'em_aberto': { label: 'Em aberto', class: 'warning' },
            'parcial': { label: 'Parcialmente pago', class: 'info' },
            'inadimplente': { label: 'Inadimplente', class: 'danger' }
        };
        
        const statusInfo = statusMap[resumo.status_financeiro] || statusMap['nao_lancado'];
        
        // Formatar valores monetários
        const formatarMoeda = (valor) => {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(valor || 0);
        };
        
        // Formatar data
        const formatarData = (data) => {
            if (!data) return null;
            const d = new Date(data);
            return d.toLocaleDateString('pt-BR');
        };
        
        // Montar HTML do resumo para cada card
        // Verificar se é o card da aba Histórico (mais compacto) ou outros cards (mais detalhado)
        cardElements.forEach((cardEl, index) => {
            const isCardHistorico = cardEl.id === 'card-situacao-financeira-historico';
            let html = '';
            
            if (resumo.qtd_faturas === 0) {
                // Nenhuma fatura lançada
                html = `
                    <div class="text-center">
                        <span class="badge bg-${statusInfo.class} mb-2">${statusInfo.label}</span>
                        <p class="text-muted small mb-0" style="font-size: 0.75rem;">Nenhuma fatura lançada para este aluno.</p>
                    </div>
                `;
            } else {
                // Há faturas - mostrar resumo
                if (isCardHistorico) {
                    // Card da aba Histórico - versão mais compacta
                    html = `
                        <div class="text-center">
                            <span class="badge bg-${statusInfo.class} mb-1" style="font-size: 0.7rem;">${statusInfo.label}</span>
                            <p class="text-muted small mb-0" style="font-size: 0.7rem; line-height: 1.3;">
                                ${resumo.saldo_aberto > 0 
                                    ? `Saldo: <strong>${formatarMoeda(resumo.saldo_aberto)}</strong>` 
                                    : 'Em dia'}
                                ${resumo.qtd_faturas_vencidas > 0 
                                    ? `<br><span class="text-danger" style="font-weight: 600;">${resumo.qtd_faturas_vencidas} vencida(s)</span>` 
                                    : ''}
                            </p>
                        </div>
                    `;
                } else {
                    // Outros cards - versão mais detalhada
                    html = `
                        <div class="text-center">
                            <span class="badge bg-${statusInfo.class} mb-2">${statusInfo.label}</span>
                            <p class="text-muted small mb-1" style="font-size: 0.75rem;">
                                Contratado: <strong>${formatarMoeda(resumo.total_contratado)}</strong><br>
                                Pago: <strong>${formatarMoeda(resumo.total_pago)}</strong><br>
                                Saldo: <strong>${formatarMoeda(resumo.saldo_aberto)}</strong>
                            </p>
                    `;
                    
                    if (resumo.proximo_vencimento) {
                        html += `<p class="text-muted small mb-1" style="font-size: 0.7rem;">Próximo vencimento: ${formatarData(resumo.proximo_vencimento)}</p>`;
                    }
                    
                    if (resumo.qtd_faturas_vencidas > 0) {
                        html += `<p class="text-danger small mb-0" style="font-size: 0.7rem; font-weight: 600;">Faturas vencidas: ${resumo.qtd_faturas_vencidas}</p>`;
                    }
                    
                    html += `</div>`;
                }
            }
            
            // Adicionar link "Ver Financeiro" apenas se não for o card da aba Histórico (para manter compacto)
            if (!isCardHistorico && alunoId) {
                html += `
                    <div class="text-center mt-2">
                        <a href="?page=financeiro-faturas&aluno_id=${alunoId}" 
                           class="btn btn-sm btn-outline-primary" 
                           style="font-size: 0.7rem; padding: 0.2rem 0.5rem;">
                            <i class="fas fa-dollar-sign me-1"></i>Ver Financeiro
                        </a>
                    </div>
                `;
            }
            
            // Atualizar este card específico
            cardEl.innerHTML = html;
            logModalAluno(`✅ Card financeiro_resumo [${index + 1}] atualizado com resumo completo`);
        });
        
        logModalAluno(`✅ Total de ${cardElements.length} card(s) financeiro(s) atualizado(s)`);
        
    } catch (error) {
        console.error('❌ Erro ao atualizar cards financeiro resumo:', error);
    }
}

/**
 * Atualiza todos os cards "Progresso Teórico"
 * (tanto na aba Histórico quanto no modal de visualização)
 * usando o atributo data-field="teorico_resumo"
 * @param {string} texto - Texto a ser exibido no card
 */
function atualizarCardsTeoricoResumo(texto) {
    try {
        const elementos = document.querySelectorAll('[data-field="teorico_resumo"]');
        
        if (!elementos.length) {
            logModalAluno('⚠️ Nenhum card teorico_resumo encontrado');
            return;
        }
        
        elementos.forEach((el, index) => {
            el.innerHTML = `<span class="text-muted">${texto}</span>`;
            logModalAluno(`✅ Card teorico_resumo [${index + 1}] atualizado:`, texto);
        });
        
        logModalAluno(`✅ Total de ${elementos.length} card(s) teóricos atualizado(s) com: ${texto}`);
        
    } catch (error) {
        console.error('❌ Erro ao atualizar cards de Progresso Teórico:', error);
    }
}

/**
 * Atualiza a seção "Vinculação Teórica" na aba Matrícula
 * usando os dados retornados pela API de progresso teórico.
 * @param {Object|null} progresso - Objeto retornado em data.progresso ou null
 * @param {string} textoResumo - Texto já formatado para o card (ex.: "Em andamento (80% de presença)")
 */
function atualizarVinculacaoTeoricaUI(progresso, textoResumo) {
    try {
        const selectTurma = document.querySelector('select[name="turma_teorica_atual_id"]');
        const inputSituacao = document.querySelector('input[name="situacao_teorica"]');
        
        if (!selectTurma || !inputSituacao) {
            logModalAluno('⚠️ Campos de vinculação teórica não encontrados no DOM');
            return;
        }
        
        // Limpar opções atuais do select
        while (selectTurma.firstChild) {
            selectTurma.removeChild(selectTurma.firstChild);
        }
        
        let option = document.createElement('option');
        
        if (!progresso || !progresso.turma_id) {
            // Sem turma vinculada
            option.value = '';
            option.textContent = 'Nenhuma turma teórica vinculada';
            selectTurma.appendChild(option);
            selectTurma.value = '';
            inputSituacao.value = 'Não iniciado';
        } else {
            // Turma vinculada
            option.value = progresso.turma_id;
            const nomeTurma = progresso.turma_nome || 'Turma teórica';
            option.textContent = nomeTurma;
            selectTurma.appendChild(option);
            selectTurma.value = progresso.turma_id;
            
            // Situação das aulas teóricas: usar o mesmo texto mostrado no card
            inputSituacao.value = textoResumo || '';
        }
        
        // Importante: deixar o select somente leitura (não editar por aqui)
        selectTurma.disabled = true;
        
        logModalAluno('✅ Vinculação Teórica atualizada a partir do progresso teórico:', {
            turma_id: progresso ? progresso.turma_id : null,
            turma_nome: progresso ? progresso.turma_nome : null,
            textoResumo
        });
        
    } catch (error) {
        console.error('❌ Erro ao atualizar UI de Vinculação Teórica:', error);
    }
}

/**
 * Busca o progresso teórico do aluno na API e atualiza os cards de resumo
 * @param {number} alunoId - ID do aluno
 */
async function atualizarResumoTeoricoAluno(alunoId) {
    try {
        logModalAluno('📘 Carregando Progresso Teórico do aluno:', alunoId);
        
        if (!alunoId) {
            atualizarCardsTeoricoResumo('Não iniciado');
            atualizarVinculacaoTeoricaUI(null, 'Não iniciado');
            contextoAlunoAtual.turmaTeoricaId = null;
            return;
        }
        
        const response = await fetchWithTimeout(`api/progresso_teorico.php?aluno_id=${encodeURIComponent(alunoId)}`, {}, 15000);
        
        if (!response.ok) {
            // Não bloquear se houver erro - apenas atualizar UI
            atualizarCardsTeoricoResumo('Não iniciado');
            atualizarVinculacaoTeoricaUI(null, 'Não iniciado');
            contextoAlunoAtual.turmaTeoricaId = null;
            return;
        }
        
        const data = await response.json();
        
        if (!data.success) {
            // Não logar erro se for esperado (não bloquear)
            atualizarCardsTeoricoResumo('Não iniciado');
            atualizarVinculacaoTeoricaUI(null, 'Não iniciado');
            contextoAlunoAtual.turmaTeoricaId = null;
            return;
        }
        
        const progresso = data.progresso;
        
        if (!progresso) {
            // Nenhuma matrícula teórica encontrada
            atualizarCardsTeoricoResumo('Não iniciado');
            atualizarVinculacaoTeoricaUI(null, 'Não iniciado');
            contextoAlunoAtual.turmaTeoricaId = null;
            return;
        }
        
        const status = (progresso.status || '').toLowerCase();
        const freq = typeof progresso.frequencia_percentual === 'number'
            ? progresso.frequencia_percentual
            : null;
        
        // Mapeamento de status brutos -> texto amigável
        const statusMap = {
            'matriculado': 'Matriculado',
            'cursando': 'Em andamento',
            'concluido': 'Concluído',
            'evadido': 'Evadido',
            'transferido': 'Transferido'
        };
        
        let texto = statusMap[status] || 'Não iniciado';
        
        if (freq !== null && !isNaN(freq)) {
            const freqFormatada = freq.toFixed(0); // 0 casas decimais basta pro card
            texto += ` (${freqFormatada}% de presença)`;
        }
        
        // Atualizar cards de resumo
        atualizarCardsTeoricoResumo(texto);
        
        // Atualizar seção "Vinculação Teórica" na aba Matrícula
        atualizarVinculacaoTeoricaUI(progresso, texto);
        
        // Preencher contexto com turma teórica
        contextoAlunoAtual.turmaTeoricaId = progresso && progresso.turma_id ? progresso.turma_id : null;
        
    } catch (error) {
        console.error('❌ Erro ao carregar Progresso Teórico do aluno:', error);
        atualizarCardsTeoricoResumo('Não iniciado');
        atualizarVinculacaoTeoricaUI(null, 'Não iniciado');
        contextoAlunoAtual.turmaTeoricaId = null;
    }
}

/**
 * Atualiza todos os cards "Progresso Prático"
 * (tanto na aba Histórico quanto no modal de visualização)
 * usando o atributo data-field="pratico_resumo"
 * @param {string} texto - Texto a ser exibido no card
 */
function atualizarCardsPraticoResumo(texto) {
    try {
        const elements = document.querySelectorAll('[data-field="pratico_resumo"]');
        
        if (!elements.length) {
            logModalAluno('⚠️ Nenhum card pratico_resumo encontrado');
            return;
        }
        
        elements.forEach((el, idx) => {
            el.innerHTML = `<span class="text-muted">${texto}</span>`;
            logModalAluno(`✅ Card pratico_resumo [${idx + 1}] atualizado:`, texto);
        });
        
        logModalAluno(`✅ Total de ${elements.length} card(s) de Progresso Prático atualizados`);
        
    } catch (error) {
        console.error('❌ Erro ao atualizar cards de Progresso Prático:', error);
    }
}

/**
 * Atualiza a seção "Vinculação Prática" na aba Matrícula
 * usando os dados retornados pela API de progresso prático.
 * Por enquanto, apenas preenche o campo de situação.
 * @param {Object|null} progresso - Objeto retornado em data.progresso ou null
 * @param {string} textoResumo - Texto já formatado para o card (ex.: "Em andamento (8 de 20 aulas)")
 */
function atualizarVinculacaoPraticaUI(progresso, textoResumo) {
    try {
        const situacaoInput = document.querySelector('input[name="situacao_pratica"]');
        
        if (!situacaoInput) {
            logModalAluno('⚠️ Campo situacao_pratica não encontrado no DOM');
            return;
        }
        
        if (!progresso) {
            // Sem progresso prático
            situacaoInput.value = 'Não iniciado';
        } else {
            // Com progresso prático
            situacaoInput.value = textoResumo || 'Não informado';
        }
        
        logModalAluno('✅ Vinculação Prática atualizada a partir do progresso prático:', {
            status: progresso ? progresso.status : null,
            total_realizadas: progresso ? progresso.total_realizadas : null,
            total_contratadas: progresso ? progresso.total_contratadas : null,
            textoResumo
        });
        
    } catch (error) {
        console.error('❌ Erro ao atualizar UI de Vinculação Prática:', error);
    }
}

/**
 * Busca o progresso prático do aluno na API e atualiza os cards de resumo
 * @param {number} alunoId - ID do aluno
 */
async function atualizarResumoPraticoAluno(alunoId) {
    try {
        logModalAluno('📗 Carregando Progresso Prático do aluno:', alunoId);
        
        if (!alunoId) {
            logModalAluno('⚠️ alunoId não fornecido para progresso prático');
            atualizarCardsPraticoResumo('Não iniciado');
            atualizarVinculacaoPraticaUI(null, 'Não iniciado');
            return;
        }
        
        const response = await fetchWithTimeout(`api/progresso_pratico.php?aluno_id=${encodeURIComponent(alunoId)}`, {}, 15000);
        
        if (!response.ok) {
            // Não bloquear se houver erro - apenas atualizar UI
            atualizarCardsPraticoResumo('Não iniciado');
            atualizarVinculacaoPraticaUI(null, 'Não iniciado');
            return;
        }
        
        const data = await response.json();
        
        if (!data.success) {
            // Não logar erro se for esperado (não bloquear)
            atualizarCardsPraticoResumo('Não iniciado');
            atualizarVinculacaoPraticaUI(null, 'Não iniciado');
            return;
        }
        
        const progresso = data.progresso;
        
        if (!progresso) {
            // Nenhuma aula prática encontrada
            atualizarCardsPraticoResumo('Não iniciado');
            atualizarVinculacaoPraticaUI(null, 'Não iniciado');
            return;
        }
        
        // Mapeamento de status brutos -> texto amigável
        const statusMap = {
            'nao_iniciado': 'Não iniciado',
            'em_andamento': 'Em andamento',
            'concluido': 'Concluído'
        };
        
        let texto = statusMap[progresso.status] || 'Não informado';
        
        // Complementar com informações de progresso
        if (progresso.total_realizadas !== undefined && progresso.total_contratadas !== undefined && progresso.total_contratadas > 0) {
            if (progresso.status === 'em_andamento') {
                texto += ` (${progresso.total_realizadas} de ${progresso.total_contratadas} aulas)`;
            } else if (progresso.status === 'concluido') {
                texto += ` (${progresso.total_realizadas} de ${progresso.total_contratadas} aulas)`;
            }
        } else if (progresso.percentual_concluido !== undefined && progresso.percentual_concluido > 0) {
            texto += ` (${progresso.percentual_concluido}% das aulas concluídas)`;
        }
        
        // Atualizar cards de resumo
        atualizarCardsPraticoResumo(texto);
        
        // Atualizar seção "Vinculação Prática" na aba Matrícula
        atualizarVinculacaoPraticaUI(progresso, texto);
        
    } catch (error) {
        console.error('❌ Erro ao carregar Progresso Prático do aluno:', error);
        atualizarCardsPraticoResumo('Não iniciado');
        atualizarVinculacaoPraticaUI(null, 'Não iniciado');
    }
}

/**
 * Atualiza o card "Provas" (data-field="provas_resumo") em todos os contextos
 * Usa a tabela EXAMES para buscar provas teóricas/práticas do aluno
 * Também preenche a seção "Provas" na aba Matrícula
 * @param {number} alunoId - ID do aluno
 */
async function atualizarResumoProvasAluno(alunoId) {
    try {
        if (!alunoId) {
            logModalAluno('⚠️ alunoId não fornecido para resumo de provas');
            atualizarCardsProvasResumo('Não iniciado');
            atualizarSecaoProvasMatricula(null, null);
            return;
        }
        
        console.log('[RESUMO PROVAS] Atualizando para aluno:', alunoId);
        
        // Detectar caminho da API
        const baseUrl = window.location.origin;
        const pathname = window.location.pathname;
        let apiUrl;
        
        if (pathname.includes('/admin/')) {
            const pathParts = pathname.split('/');
            const projectIndex = pathParts.findIndex(part => part === 'admin');
            if (projectIndex > 0) {
                const basePath = pathParts.slice(0, projectIndex).join('/');
                apiUrl = baseUrl + basePath + '/admin/api/exames.php';
            } else {
                apiUrl = baseUrl + '/admin/api/exames.php';
            }
        } else {
            apiUrl = baseUrl + '/admin/api/exames.php';
        }
        
        const url = `${apiUrl}?aluno_id=${encodeURIComponent(alunoId)}&resumo=1`;
        console.log('[RESUMO PROVAS] URL chamada:', url);
        
        logModalAluno('📝 Carregando resumo de provas do aluno:', alunoId);
        
        // Buscar exames do aluno (todos os tipos) com timeout de 15 segundos
        const response = await fetchWithTimeout(url, {}, 15000);
        
        console.log('[RESUMO PROVAS] Status HTTP:', response.status);
        
        if (!response.ok) {
            // Não logar erro se for timeout ou erro esperado (não bloquear)
            if (response.status !== 500 && response.status !== 503) {
                const text = await response.text().catch(() => '');
                console.warn('[RESUMO PROVAS] Erro HTTP ao buscar exames (não bloqueante):', response.status);
            }
            atualizarCardsProvasResumo('Não iniciado');
            atualizarSecaoProvasMatricula(null, null);
            return; // Retornar silenciosamente em vez de lançar erro
        }
        
        const data = await response.json();
        
        // A API de exames retorna { aluno, exames, can_write, exames_ok }
        if (!data || !data.exames) {
            console.error('❌ Erro ao buscar exames: resposta inválida');
            atualizarCardsProvasResumo('Não iniciado');
            atualizarSecaoProvasMatricula(null, null);
            return;
        }
        
        // Filtrar apenas provas (teórica e prática)
        const provas = data.exames.filter(exame => 
            exame.tipo === 'teorico' || exame.tipo === 'pratico'
        );
        
        if (provas.length === 0) {
            logModalAluno('⚠️ Nenhuma prova encontrada para o aluno');
            atualizarCardsProvasResumo('Não iniciado');
            atualizarSecaoProvasMatricula(null, null);
            return;
        }
        
        // Encontrar a última prova teórica e prática
        let provaTeorica = null;
        let provaPratica = null;
        
        provas.forEach(prova => {
            // Determinar data de referência (data_resultado > data_agendada > ignorar)
            const dataRef = prova.data_resultado || prova.data_agendada;
            if (!dataRef) return;
            
            if (prova.tipo === 'teorico') {
                if (!provaTeorica || new Date(dataRef) > new Date(provaTeorica.dataRef)) {
                    provaTeorica = { ...prova, dataRef };
                }
            } else if (prova.tipo === 'pratico') {
                if (!provaPratica || new Date(dataRef) > new Date(provaPratica.dataRef)) {
                    provaPratica = { ...prova, dataRef };
                }
            }
        });
        
        // Montar texto resumo para o card
        let resumoTexto = '';
        
        // Se não existir nenhuma prova
        if (!provaTeorica && !provaPratica) {
            resumoTexto = 'Não iniciado';
        }
        // Se teórica aprovada e prática aprovada
        else if (provaTeorica?.resultado === 'aprovado' && provaPratica?.resultado === 'aprovado') {
            resumoTexto = 'Teórica e prática aprovadas';
        }
        // Se teórica aprovada e prática pendente
        else if (provaTeorica?.resultado === 'aprovado' && (!provaPratica || provaPratica.status === 'agendado' || !provaPratica.resultado)) {
            resumoTexto = 'Teórica aprovada, prática pendente';
        }
        // Se provas agendadas mas nenhuma concluída
        else if (
            (provaTeorica?.status === 'agendado' || provaPratica?.status === 'agendado') &&
            (!provaTeorica?.resultado || provaTeorica.resultado === 'pendente') &&
            (!provaPratica?.resultado || provaPratica.resultado === 'pendente')
        ) {
            resumoTexto = 'Provas agendadas';
        }
        // Se houver reprovação recente
        else if (provaTeorica?.resultado === 'reprovado' || provaPratica?.resultado === 'reprovado') {
            // Determinar qual foi a última reprovação
            const teoricaReprovada = provaTeorica?.resultado === 'reprovado';
            const praticaReprovada = provaPratica?.resultado === 'reprovado';
            
            if (teoricaReprovada && praticaReprovada) {
                // Comparar datas para ver qual foi mais recente
                const dataTeorica = provaTeorica.data_resultado || provaTeorica.data_agendada;
                const dataPratica = provaPratica.data_resultado || provaPratica.data_agendada;
                if (new Date(dataTeorica) > new Date(dataPratica)) {
                    resumoTexto = 'Reprovação em prova teórica';
                } else {
                    resumoTexto = 'Reprovação em prova prática';
                }
            } else if (teoricaReprovada) {
                resumoTexto = 'Reprovação em prova teórica';
            } else {
                resumoTexto = 'Reprovação em prova prática';
            }
        }
        // Se tiver ao menos uma prova concluída mas não se encaixar nas regras acima
        else {
            resumoTexto = 'Provas em andamento';
        }
        
        // Atualizar cards
        atualizarCardsProvasResumo(resumoTexto);
        
        // Atualizar seção na aba Matrícula
        atualizarSecaoProvasMatricula(provaTeorica, provaPratica);
        
        logModalAluno('✅ Resumo de provas atualizado:', {
            resumoTexto,
            temTeorica: !!provaTeorica,
            temPratica: !!provaPratica
        });
        
    } catch (error) {
        console.error('❌ Erro ao carregar resumo de provas do aluno:', error);
        atualizarCardsProvasResumo('Não iniciado');
        atualizarSecaoProvasMatricula(null, null);
    }
}

/**
 * Atualiza todos os elementos com data-field="provas_resumo" com o texto fornecido
 * @param {string} texto - Texto a ser exibido no card
 */
function atualizarCardsProvasResumo(texto) {
    try {
        const elementos = document.querySelectorAll('[data-field="provas_resumo"]');
        
        if (!elementos.length) {
            logModalAluno('⚠️ Nenhum card provas_resumo encontrado');
            return;
        }
        
        elementos.forEach(el => {
            el.innerHTML = `<span class="text-muted">${texto}</span>`;
        });
        
    } catch (error) {
        console.error('❌ Erro ao atualizar cards de provas:', error);
    }
}

/**
 * Atualiza a seção "Provas" na aba Matrícula com os dados das provas
 * @param {Object|null} provaTeorica - Último exame de prova teórica ou null
 * @param {Object|null} provaPratica - Último exame de prova prática ou null
 */
function atualizarSecaoProvasMatricula(provaTeorica, provaPratica) {
    try {
        // Prova Teórica
        const inputTeorica = document.getElementById('prova_teorica_resumo');
        const detalhesTeorica = document.getElementById('prova_teorica_detalhes');
        
        if (inputTeorica) {
            if (!provaTeorica) {
                inputTeorica.value = '';
                if (detalhesTeorica) {
                    detalhesTeorica.textContent = 'Nenhuma prova registrada';
                }
            } else {
                // Determinar status principal
                let statusPrincipal = 'Não iniciado';
                if (provaTeorica.resultado === 'aprovado') {
                    statusPrincipal = 'Aprovado';
                } else if (provaTeorica.resultado === 'reprovado') {
                    statusPrincipal = 'Reprovado';
                } else if (provaTeorica.status === 'agendado') {
                    statusPrincipal = 'Agendado';
                } else if (provaTeorica.status === 'concluido') {
                    statusPrincipal = 'Concluído';
                }
                
                inputTeorica.value = statusPrincipal;
                
                // Montar detalhes
                if (detalhesTeorica) {
                    const detalhes = [];
                    const dataRef = provaTeorica.data_resultado || provaTeorica.data_agendada;
                    if (dataRef) {
                        const dataFormatada = new Date(dataRef).toLocaleDateString('pt-BR');
                        detalhes.push(`Data: ${dataFormatada}`);
                    }
                    if (provaTeorica.resultado) {
                        const resultadoTexto = provaTeorica.resultado === 'aprovado' ? 'Aprovado' : 
                                               provaTeorica.resultado === 'reprovado' ? 'Reprovado' : 
                                               provaTeorica.resultado;
                        detalhes.push(`Resultado: ${resultadoTexto}`);
                    }
                    if (provaTeorica.protocolo) {
                        detalhes.push(`protocolo ${provaTeorica.protocolo}`);
                    }
                    if (provaTeorica.clinica_nome) {
                        detalhes.push(`local ${provaTeorica.clinica_nome}`);
                    }
                    
                    detalhesTeorica.textContent = detalhes.length > 0 ? detalhes.join(' – ') : '';
                }
            }
        }
        
        // Prova Prática
        const inputPratica = document.getElementById('prova_pratica_resumo');
        const detalhesPratica = document.getElementById('prova_pratica_detalhes');
        
        if (inputPratica) {
            if (!provaPratica) {
                inputPratica.value = '';
                if (detalhesPratica) {
                    detalhesPratica.textContent = 'Nenhuma prova registrada';
                }
            } else {
                // Determinar status principal
                let statusPrincipal = 'Não iniciado';
                if (provaPratica.resultado === 'aprovado') {
                    statusPrincipal = 'Aprovado';
                } else if (provaPratica.resultado === 'reprovado') {
                    statusPrincipal = 'Reprovado';
                } else if (provaPratica.status === 'agendado') {
                    statusPrincipal = 'Agendado';
                } else if (provaPratica.status === 'concluido') {
                    statusPrincipal = 'Concluído';
                }
                
                inputPratica.value = statusPrincipal;
                
                // Montar detalhes
                if (detalhesPratica) {
                    const detalhes = [];
                    const dataRef = provaPratica.data_resultado || provaPratica.data_agendada;
                    if (dataRef) {
                        const dataFormatada = new Date(dataRef).toLocaleDateString('pt-BR');
                        detalhes.push(`Data: ${dataFormatada}`);
                    }
                    if (provaPratica.resultado) {
                        const resultadoTexto = provaPratica.resultado === 'aprovado' ? 'Aprovado' : 
                                               provaPratica.resultado === 'reprovado' ? 'Reprovado' : 
                                               provaPratica.resultado;
                        detalhes.push(`Resultado: ${resultadoTexto}`);
                    }
                    if (provaPratica.protocolo) {
                        detalhes.push(`protocolo ${provaPratica.protocolo}`);
                    }
                    if (provaPratica.clinica_nome) {
                        detalhes.push(`local ${provaPratica.clinica_nome}`);
                    }
                    
                    detalhesPratica.textContent = detalhes.length > 0 ? detalhes.join(' – ') : '';
                }
            }
        }
        
    } catch (error) {
        console.error('❌ Erro ao atualizar seção de provas na aba Matrícula:', error);
    }
}

/**
 * Registra eventos de clique nos atalhos rápidos do aluno
 * (tanto na aba Histórico quanto no modal de visualização)
 */
function registrarEventosAtalhosAluno() {
    try {
        const atalhos = document.querySelectorAll('.aluno-atalho[data-acao]');
        
        if (!atalhos.length) {
            logModalAluno('⚠️ Nenhum atalho de aluno encontrado para registrar eventos');
        } else {
            atalhos.forEach(atalho => {
                atalho.classList.remove('disabled');
                atalho.removeAttribute('disabled');
                
                // Remover listeners antigos para evitar duplicação
                const novoAtalho = atalho.cloneNode(true);
                atalho.parentNode.replaceChild(novoAtalho, atalho);
                
                novoAtalho.addEventListener('click', function (e) {
                    e.preventDefault();
                    const acao = this.getAttribute('data-acao');
                    abrirAtalhoAluno(acao);
                });
            });
            
            logModalAluno(`✅ Eventos registrados para ${atalhos.length} atalho(s) de aluno`);
        }
        
        // Habilitar link "Ver Financeiro do Aluno" na aba Matrícula
        const linkFinanceiro = document.querySelector('.aluno-btn-financeiro');
        if (linkFinanceiro) {
            // Obter alunoId do modal
            const alunoId = document.getElementById('editar_aluno_id')?.value;
            if (alunoId) {
                // Configurar link funcional
                linkFinanceiro.href = `?page=financeiro-faturas&aluno_id=${alunoId}`;
                linkFinanceiro.style.pointerEvents = 'auto';
                linkFinanceiro.style.opacity = '1';
                linkFinanceiro.style.cursor = 'pointer';
                linkFinanceiro.style.textDecoration = 'underline';
                
                logModalAluno('✅ Link "Ver Financeiro do Aluno" habilitado');
            }
        }
        
    } catch (error) {
        console.error('❌ Erro ao registrar eventos dos atalhos do aluno:', error);
    }
}

/**
 * Abre o atalho correspondente à ação solicitada
 * @param {string} acao - Ação do atalho (abrir-agenda-aluno, ver-financeiro-aluno, ver-turma-teorica)
 */
function abrirAtalhoAluno(acao) {
    try {
        const { alunoId, matriculaId, turmaTeoricaId } = contextoAlunoAtual;
        
        if (!alunoId) {
            logModalAluno('⚠️ Não há alunoId no contexto atual, atalho ignorado');
            return;
        }
        
        let url = null;
        
        switch (acao) {
            case 'abrir-agenda-aluno':
                // TODO: ajustar rota da agenda se for criada página específica para agenda do aluno
                url = `index.php?page=agendamento&aluno_id=${encodeURIComponent(alunoId)}`;
                break;
                
            case 'ver-financeiro-aluno':
                // A página de financeiro-faturas aceita apenas aluno_id como filtro
                url = `index.php?page=financeiro-faturas&aluno_id=${encodeURIComponent(alunoId)}`;
                break;
                
            case 'ver-turma-teorica':
                if (!turmaTeoricaId) {
                    logModalAluno('⚠️ Nenhuma turma teórica vinculada para este aluno');
                    return;
                }
                url = `index.php?page=turmas-teoricas&acao=detalhes&turma_id=${encodeURIComponent(turmaTeoricaId)}`;
                break;
                
            default:
                logModalAluno('⚠️ Ação de atalho não reconhecida:', acao);
                return;
        }
        
        if (url) {
            window.open(url, '_blank'); // abre em nova aba para não perder o modal
            logModalAluno('🔗 Atalho aberto:', url);
        }
        
    } catch (error) {
        console.error('❌ Erro ao abrir atalho do aluno:', error);
    }
}

/**
 * Atualiza o card "Situação do Processo" na aba Histórico e no modal de visualização
 * Atualiza TODOS os elementos com data-field="processo_status_resumo"
 * @param {Object|null} matricula - Objeto com os dados da matrícula ou null se não houver
 */
function atualizarResumoProcessoHistorico(matricula) {
    try {
        const cardElements = document.querySelectorAll('[data-field="processo_status_resumo"]');
        
        if (cardElements.length === 0) {
            logModalAluno('⚠️ Nenhum card processo_status_resumo encontrado');
            return;
        }
        
        // Determinar texto a ser exibido
        let statusTexto;
        
        if (!matricula || !matricula.status) {
            // Não há matrícula ou status não informado
            statusTexto = 'Não há matrícula cadastrada';
        } else {
            // Mapear status para texto amigável
            const statusMap = {
                'ativa': 'Em formação',
                'em_formacao': 'Em formação',
                'em_andamento': 'Em formação',
                'concluida': 'Concluído',
                'cancelada': 'Cancelado',
                'trancada': 'Trancado',
                'em_analise': 'Em análise',
                'em_exame': 'Em exame'
            };
            
            statusTexto = statusMap[matricula.status] || 'Não informado';
        }
        
        // Atualizar todos os elementos encontrados
        cardElements.forEach((cardElement, index) => {
            cardElement.innerHTML = `<span class="text-muted">${statusTexto}</span>`;
            logModalAluno(`✅ Card processo_status_resumo [${index + 1}] atualizado:`, statusTexto);
        });
        
        logModalAluno(`✅ Total de ${cardElements.length} card(s) atualizado(s) com: ${statusTexto}`);
        
    } catch (error) {
        console.error('❌ Erro ao atualizar resumo processo histórico:', error);
    }
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
                        <option value="primeira_habilitacao" selected>🏍️ Primeira Habilitação</option>
                        <option value="adicao">➕ Adição de Categoria</option>
                        <option value="mudanca">🔄 Mudança de Categoria</option>
                        <option value="aula_avulsa">📚 Aula Avulsa</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <select class="form-select form-select-sm" name="operacao_categoria_${contadorOperacoes}">
                        <option value="">Selecione a categoria</option>
                        <option value="A">A - Motocicleta</option>
                        <option value="B" selected>B - Automóvel</option>
                        <option value="AB">AB - Motocicleta + Automóvel</option>
                        <option value="ACC">ACC - Acionamento de Câmbio Automático</option>
                        <option value="C">C - Veículo de Carga</option>
                        <option value="D">D - Veículo de Passageiros</option>
                        <option value="E">E - Combinação de Veículos</option>
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
        console.log('⚠️ Nenhuma operação para carregar ou operacoes não é array - adicionando operação padrão');
        // Adicionar operação padrão se não houver operações
        adicionarOperacao();
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

// =====================================================
// FUNÇÕES PARA CONTROLE DAS ABAS DO MODAL DE ALUNO
// =====================================================
// Função para ajustar visibilidade das abas conforme perfil do usuário
function ajustarAbasPorPerfil() {
    // NOTA: As abas Financeiro, Agenda e Teórico foram removidas do modal
    // A aba Documentos agora está sempre visível (não precisa mais de controle por perfil)
    // Esta função foi simplificada pois não há mais abas condicionais
    
    logModalAluno('👤 Ajustando abas por perfil (simplificado - todas as abas visíveis)');
    
    // Aba Documentos sempre visível - não precisa mais de controle
    // As abas removidas (Financeiro, Agenda, Teórico) foram comentadas no HTML
    
    // Código antigo (comentado - não mais necessário):
    /*
    const currentUser = <?php echo json_encode($user ?? []); ?>;
    const userType = currentUser.tipo || 'instrutor';
    
    if (userType === 'instrutor') {
        document.getElementById('financeiro-tab-container').style.display = 'none';
        document.getElementById('documentos-tab-container').style.display = 'none';
    } else if (userType === 'secretaria') {
        document.getElementById('financeiro-tab-container').style.display = 'block';
        document.getElementById('documentos-tab-container').style.display = 'block';
    } else if (userType === 'admin') {
        document.getElementById('financeiro-tab-container').style.display = 'block';
        document.getElementById('documentos-tab-container').style.display = 'block';
    }
    */
}
// Função para carregar dados da aba Matrícula
function carregarMatriculas(alunoId) {
    if (!alunoId) return;
    
    // Verificar se a lista existe (aba deve ter estrutura correta)
    const list = document.querySelector('#modalAluno #matriculas-list');
    if (!list) {
        logModalAluno('[Matrículas] #matriculas-list não encontrado dentro do container. Verificar HTML da aba.');
        return;
    }
    
    fetch(`api/matriculas.php?aluno_id=${alunoId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.matriculas.length > 0) {
                list.innerHTML = `
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Categoria</th>
                                    <th>Tipo Serviço</th>
                                    <th>Status</th>
                                    <th>Data Início</th>
                                    <th>Ações</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${data.matriculas.map(matricula => `
                                    <tr>
                                        <td>${matricula.categoria_cnh}</td>
                                        <td>${matricula.tipo_servico}</td>
                                        <td><span class="badge bg-${matricula.status === 'ativa' ? 'success' : 'secondary'}">${matricula.status}</span></td>
                                        <td>${new Date(matricula.data_inicio).toLocaleDateString('pt-BR')}</td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" onclick="editarMatricula(${matricula.id})">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </td>
                                    </tr>
                                `).join('')}
                            </tbody>
                        </table>
                    </div>
                `;
            } else {
                list.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                        <i class="fas fa-graduation-cap fa-2x mb-3"></i>
                        <p class="mb-0">Nenhuma matrícula encontrada</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar matrículas:', error);
            if (list) {
                list.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar matrículas
                    </div>
                `;
            }
        })
        .finally(() => {
            reforcarEstruturaModalAluno();
        });
}
// Função para carregar documentos da aba Documentos
function carregarDocumentos(alunoId) {
    if (!alunoId) {
        const list = document.querySelector('#modalAluno #documentos-list');
        if (list) {
            list.innerHTML = `
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="fas fa-file-alt fa-2x mb-3"></i>
                    <p class="mb-0" style="font-size: 0.9rem;">Nenhum aluno selecionado</p>
                </div>
            `;
        }
        return;
    }
    
    // Verificar se a lista existe (aba deve ter estrutura correta)
    const list = document.querySelector('#modalAluno #documentos-list');
    if (!list) {
        logModalAluno('[Documentos] #documentos-list não encontrado dentro do container. Verificar HTML da aba.');
        return;
    }
    
    // Mostrar loading
    list.innerHTML = `
        <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
            <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
            <p class="mb-0" style="font-size: 0.9rem;">Carregando documentos...</p>
        </div>
    `;
    
    fetch(`api/aluno_documentos.php?aluno_id=${alunoId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.documentos && data.documentos.length > 0) {
                list.innerHTML = `
                    <div class="row g-3">
                        ${data.documentos.map(doc => {
                            const tipoLabel = {
                                'rg': 'RG',
                                'cpf': 'CPF',
                                'comprovante_residencia': 'Comprovante de Residência',
                                'foto_3x4': 'Foto 3x4',
                                'outro': 'Outro'
                            }[doc.tipo] || doc.tipo;
                            
                            const tamanhoFormatado = formatarTamanhoArquivo(doc.tamanho_bytes);
                            const dataFormatada = formatarDataDocumento(doc.criado_em);
                            const urlArquivo = construirUrlArquivo(doc.arquivo);
                            
                            return `
                                <div class="col-md-6">
                                    <div class="card border" style="font-size: 0.85rem;">
                                        <div class="card-body p-3">
                                            <div class="d-flex align-items-start mb-2">
                                                <i class="fas fa-file-${doc.mime_type?.includes('pdf') ? 'pdf' : 'image'} fa-2x text-primary me-2"></i>
                                                <div class="flex-grow-1">
                                                    <h6 class="card-title mb-1" style="font-size: 0.9rem; font-weight: 600;">${tipoLabel}</h6>
                                                    <p class="card-text mb-1 text-muted" style="font-size: 0.8rem;">
                                                        <i class="fas fa-file me-1"></i>${doc.nome_original}
                                                    </p>
                                                    <div class="d-flex align-items-center gap-3 text-muted" style="font-size: 0.75rem;">
                                                        <span><i class="fas fa-calendar me-1"></i>${dataFormatada}</span>
                                                        <span><i class="fas fa-weight me-1"></i>${tamanhoFormatado}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2 mt-2">
                                                <a href="${urlArquivo}" target="_blank" class="btn btn-sm btn-outline-primary flex-fill" style="font-size: 0.8rem;">
                                                    <i class="fas fa-eye me-1"></i>Abrir
                                                </a>
                                                <button class="btn btn-sm btn-outline-danger" onclick="excluirDocumento(${doc.id}, ${alunoId})" style="font-size: 0.8rem;">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            `;
                        }).join('')}
                    </div>
                `;
            } else {
                list.innerHTML = `
                    <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                        <i class="fas fa-file-alt fa-2x mb-3"></i>
                        <p class="mb-0" style="font-size: 0.9rem;">Nenhum documento encontrado</p>
                        <small class="text-muted mt-1">Envie o primeiro documento usando o formulário acima</small>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('[Documentos] Erro ao carregar:', error);
            list.innerHTML = `
                <div class="alert alert-danger" role="alert" style="font-size: 0.85rem;">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro ao carregar documentos: ${error.message}
                </div>
            `;
            if (list) {
                list.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Erro ao carregar documentos
                    </div>
                `;
            }
        })
        .finally(() => {
            reforcarEstruturaModalAluno();
        });
}

// Função para enviar documento
function enviarDocumento(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }
    
    console.log('[Documentos] enviarDocumento chamada - upload de documento');
    
    // Descobrir o aluno_id atual de forma confiável (mesma fonte usada para carregar dados/matrícula)
    const alunoIdHidden = document.getElementById('aluno_id_hidden');
    const alunoIdInput = document.getElementById('aluno_id');
    const alunoIdData = document.querySelector('[data-aluno-id]')?.getAttribute('data-aluno-id');
    
    // Tentar múltiplas fontes (prioridade: hidden > input > data-attr > contexto global)
    const alunoId = (alunoIdHidden?.value) 
        || (alunoIdInput?.value)
        || alunoIdData
        || (typeof contextoAlunoAtual !== 'undefined' && contextoAlunoAtual?.alunoId)
        || null;
    
    // Validações simples no frontend
    if (!alunoId) {
        alert('⚠️ Aluno não identificado para envio de documento. Feche e reabra o modal de edição.');
        return;
    }
    
    const tipoSelect = document.getElementById('tipo-documento');
    const arquivoInput = document.getElementById('arquivo-documento');
    const btnEnviar = document.getElementById('btn-enviar-documento');
    
    if (!tipoSelect || !tipoSelect.value) {
        alert('⚠️ Por favor, selecione o tipo de documento.');
        if (tipoSelect) tipoSelect.focus();
        return;
    }
    
    if (!arquivoInput || !arquivoInput.files || !arquivoInput.files[0]) {
        alert('⚠️ Por favor, selecione um arquivo.');
        if (arquivoInput) arquivoInput.focus();
        return;
    }
    
    // Validar tamanho (5MB)
    const arquivo = arquivoInput.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    if (arquivo.size > maxSize) {
        alert('⚠️ Arquivo muito grande. Máximo 5MB.');
        return;
    }
    
    // Preparar FormData próprio (sem depender de <form> HTML)
    const formData = new FormData();
    formData.append('aluno_id', alunoId);
    formData.append('tipo', tipoSelect.value); // Usar 'tipo' conforme documentação da API
    formData.append('arquivo', arquivo);
    
    // Log de debug (limpo)
    console.log('[Documentos] Enviando FormData:', {
        aluno_id: alunoId,
        tipo: tipoSelect.value,
        arquivo_nome: arquivo.name,
        arquivo_tamanho: arquivo.size,
        arquivo_tipo: arquivo.type,
    });
    
    // Desabilitar botão e mostrar loading
    const textoOriginal = btnEnviar ? btnEnviar.innerHTML : null;
    if (btnEnviar) {
        btnEnviar.disabled = true;
        btnEnviar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enviando...';
    }
    
    // URL da API (aluno_id também na query string para compatibilidade)
    const url = `api/aluno_documentos.php?aluno_id=${encodeURIComponent(alunoId)}`;
    
    // Enviar para API
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(async response => {
        const data = await response.json().catch(() => null);
        
        if (!response.ok || !data || !data.success) {
            console.error('[Documentos] Erro ao enviar:', { response, data });
            const msg = (data && (data.error || data.message)) 
                ? (data.error || data.message)
                : 'Erro ao enviar documento. Tente novamente.';
            throw new Error(msg);
        }
        
        return data;
    })
    .then(data => {
        // Sucesso: recarregar a lista de documentos
        console.log('[Documentos] Documento enviado com sucesso:', data);
        
        // Limpar formulário
        if (tipoSelect) tipoSelect.value = '';
        if (arquivoInput) arquivoInput.value = '';
        
        // Recarregar lista de documentos
        if (typeof carregarDocumentos === 'function') {
            carregarDocumentos(alunoId);
        }
        
        // Mostrar mensagem de sucesso (discreta)
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta('Documento enviado com sucesso!', 'success');
        } else {
            alert('✅ Documento enviado com sucesso!');
        }
    })
    .catch(error => {
        console.error('[Documentos] Erro inesperado ao enviar:', error);
        const msg = error.message || 'Erro inesperado ao enviar documento. Verifique sua conexão e tente novamente.';
        if (typeof mostrarAlerta === 'function') {
            mostrarAlerta(msg, 'error');
        } else {
            alert('❌ ' + msg);
        }
    })
    .finally(() => {
        // Restaurar botão
        if (btnEnviar) {
            btnEnviar.disabled = false;
            btnEnviar.innerHTML = textoOriginal || '<i class="fas fa-upload me-1"></i>Enviar';
        }
        // NÃO fechar o modal, não recarregar a página
    });
}

// Função para excluir documento
function excluirDocumento(documentoId, alunoId) {
    if (!confirm('⚠️ Tem certeza que deseja excluir este documento?')) {
        return;
    }
    
    fetch(`api/aluno_documentos.php?id=${documentoId}`, {
        method: 'DELETE'
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(data => {
                throw new Error(data.error || `HTTP ${response.status}`);
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Recarregar lista de documentos
            carregarDocumentos(alunoId);
            
            // Mostrar mensagem de sucesso
            alert('✅ Documento excluído com sucesso!');
        } else {
            throw new Error(data.error || 'Erro desconhecido');
        }
    })
    .catch(error => {
        console.error('[Documentos] Erro ao excluir:', error);
        alert('❌ Erro ao excluir documento: ' + error.message);
    });
}

// Funções auxiliares para documentos
function formatarTamanhoArquivo(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
}

function formatarDataDocumento(dataString) {
    if (!dataString) return '—';
    const data = new Date(dataString);
    return data.toLocaleDateString('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

function construirUrlArquivo(caminhoRelativo) {
    if (!caminhoRelativo) return '#';
    // Construir URL absoluta baseada no caminho atual
    const basePathParts = window.location.pathname.split('/');
    const projectPath = basePathParts.slice(0, -2).join('/');
    return `${window.location.origin}${projectPath}/${caminhoRelativo}`;
}

// Função para carregar contador de documentos no modal Detalhes
function carregarContadorDocumentos(alunoId) {
    if (!alunoId) return;
    
    const contadorElement = document.getElementById(`contador-documentos-${alunoId}`);
    if (!contadorElement) return;
    
    fetch(`api/aluno_documentos.php?aluno_id=${alunoId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.documentos) {
                const quantidade = data.documentos.length;
                contadorElement.innerHTML = `<i class="fas fa-file-alt me-1"></i>${quantidade}`;
                contadorElement.className = quantidade > 0 ? 'badge bg-success' : 'badge bg-secondary';
            } else {
                contadorElement.innerHTML = '<i class="fas fa-file-alt me-1"></i>0';
                contadorElement.className = 'badge bg-secondary';
            }
        })
        .catch(error => {
            console.error('[Documentos] Erro ao carregar contador:', error);
            contadorElement.innerHTML = '<i class="fas fa-exclamation-triangle me-1"></i>—';
            contadorElement.className = 'badge bg-warning';
        });
}

// Função para carregar dados de uma aba específica
function carregarDadosAba(abaId, alunoId) {
    logModalAluno(`📊 Carregando dados da aba: ${abaId} para aluno: ${alunoId}`);
    
    // Carregar documentos se a aba Documentos for aberta
    if (abaId === 'documentos' && alunoId) {
        carregarDocumentos(alunoId);
        return;
    }
    
    // Carregar matrícula principal se necessário (para abas Matrícula e Histórico)
    if ((abaId === 'matricula' || abaId === 'historico') && alunoId) {
        // Verificar se já foi carregada (flag simples para evitar chamadas repetidas desnecessárias)
        const modal = document.getElementById('modalAluno');
        if (modal && !modal.dataset.matriculaCarregada) {
            carregarMatriculaPrincipal(alunoId);
            modal.dataset.matriculaCarregada = 'true';
        }
    }
    
    switch(abaId) {
        case 'matricula':
            carregarMatriculas(alunoId);
            break;
        case 'documentos':
            carregarDocumentos(alunoId);
            break;
        // ABA AGENDA (removida do modal; código mantido como histórico)
        /*
        case 'agenda':
            document.getElementById('aulas-container').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                    <p>Carregando aulas agendadas...</p>
                </div>
            `;
            break;
        */
        // ABA TEÓRICO (removida do modal; código mantido como histórico)
        /*
        case 'teorico':
            document.getElementById('turma-container').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                    <p>Carregando informações da turma...</p>
                </div>
            `;
            break;
        */
        case 'historico':
            carregarHistoricoAluno(alunoId);
            break;
    }
    
    reforcarEstruturaModalAluno();
}
    
/**
 * Carrega a linha do tempo do aluno (eventos da jornada)
 * @param {number} alunoId - ID do aluno
 * @param {Object} options - Opções: { modoVisualizacao: false }
 * 
 * TODO: Adicionar eventos de aulas teóricas/práticas na timeline
 * TODO: Adicionar eventos de exames na timeline
 */
async function carregarHistoricoAluno(alunoId, options = { modoVisualizacao: false }) {
    try {
        const containerId = options.modoVisualizacao 
            ? '#visualizar-historico-container'
            : '#historico-container';
        
        const container = document.querySelector(containerId);
        
        if (!container) {
            logModalAluno('⚠️ Container de histórico não encontrado:', containerId);
            return;
        }
        
        if (!alunoId) {
            logModalAluno('⚠️ alunoId não fornecido para histórico');
            // Mostrar placeholder vazio
            container.innerHTML = `
                <ul class="aluno-timeline-list">
                    <li class="aluno-timeline-empty">
                        <div class="aluno-timeline-empty-icon">
                            <i class="fas fa-history fa-2x text-muted"></i>
                        </div>
                        <p class="text-muted mb-0">
                            Os eventos mais recentes do aluno aparecerão aqui.
                        </p>
                    </li>
                </ul>
            `;
            return;
        }
        
        logModalAluno('📜 Carregando histórico do aluno:', alunoId);
        
        const response = await fetchWithTimeout(`api/historico_aluno.php?aluno_id=${encodeURIComponent(alunoId)}`, {}, 15000);
        
        if (!response.ok) {
            // Não bloquear se houver erro - apenas mostrar placeholder
            container.innerHTML = `
                <ul class="aluno-timeline-list">
                    <li class="aluno-timeline-empty">
                        <div class="aluno-timeline-empty-icon">
                            <i class="fas fa-history fa-2x text-muted"></i>
                        </div>
                        <p class="text-muted mb-0">
                            Os eventos mais recentes do aluno aparecerão aqui.
                        </p>
                    </li>
                </ul>
            `;
            return;
        }
        
        const data = await response.json();
        
        if (!data.success) {
            // Não logar erro se for esperado (não bloquear)
            container.innerHTML = `
                <ul class="aluno-timeline-list">
                    <li class="aluno-timeline-empty">
                        <div class="aluno-timeline-empty-icon">
                            <i class="fas fa-history fa-2x text-muted"></i>
                        </div>
                        <p class="text-muted mb-0">
                            Os eventos mais recentes do aluno aparecerão aqui.
                        </p>
                    </li>
                </ul>
            `;
            return;
        }
        
        const eventos = data.eventos || [];
        
        if (eventos.length === 0) {
            // Mostrar placeholder vazio
            container.innerHTML = `
                <ul class="aluno-timeline-list">
                    <li class="aluno-timeline-empty">
                        <div class="aluno-timeline-empty-icon">
                            <i class="fas fa-history fa-2x text-muted"></i>
                        </div>
                        <p class="text-muted mb-0">
                            Os eventos mais recentes do aluno aparecerão aqui.
                        </p>
                    </li>
                </ul>
            `;
            return;
        }
        
        // Formatar eventos para HTML
        const eventosHTML = eventos.map(evento => {
            // Formatar data para formato brasileiro
            const dataEvento = new Date(evento.data);
            const dataFormatada = dataEvento.toLocaleString('pt-BR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            
            // Determinar tag baseado no tipo
            let tagTexto = '';
            let tagClass = 'bg-light text-muted';
            
            if (evento.tipo === 'aluno_cadastrado') {
                tagTexto = 'Cadastro';
                tagClass = 'bg-primary text-white';
            } else if (evento.tipo.startsWith('matricula_')) {
                tagTexto = 'Matrícula';
                tagClass = 'bg-info text-white';
            } else if (evento.tipo.startsWith('exame_')) {
                tagTexto = 'Exame';
                tagClass = 'bg-info text-white';
            } else if (evento.tipo.startsWith('prova_teorica_') || evento.tipo.startsWith('prova_pratica_')) {
                tagTexto = 'Prova';
                tagClass = 'bg-warning text-dark';
            } else if (evento.tipo.startsWith('aulas_teoricas_') || evento.tipo.startsWith('aulas_praticas_')) {
                tagTexto = 'Aulas';
                tagClass = 'bg-secondary text-white';
            } else if (evento.tipo.startsWith('fatura_')) {
                tagTexto = 'Financeiro';
                if (evento.tipo === 'fatura_vencida') {
                    tagClass = 'bg-danger text-white';
                } else if (evento.tipo === 'fatura_paga') {
                    tagClass = 'bg-success text-white';
                } else {
                    tagClass = 'bg-warning text-dark';
                }
            }
            
            return `
                <li class="aluno-timeline-item">
                    <div class="aluno-timeline-dot"></div>
                    <div class="aluno-timeline-content">
                        <div class="aluno-timeline-date">${dataFormatada}</div>
                        <div class="aluno-timeline-title">${evento.titulo}</div>
                        <div class="aluno-timeline-description">${evento.descricao}</div>
                        ${tagTexto ? `<div class="aluno-timeline-tag badge ${tagClass}">${tagTexto}</div>` : ''}
                    </div>
                </li>
            `;
        }).join('');
        
        // Atualizar container
        container.innerHTML = `<ul class="aluno-timeline-list">${eventosHTML}</ul>`;
        
        logModalAluno(`✅ Histórico carregado: ${eventos.length} evento(s)`);
        
    } catch (error) {
        console.error('❌ Erro ao carregar histórico do aluno:', error);
        // Mostrar placeholder vazio em caso de erro
        const containerId = options.modoVisualizacao 
            ? '#visualizar-historico-container'
            : '#historico-container';
        const container = document.querySelector(containerId);
        if (container) {
            container.innerHTML = `
                <ul class="aluno-timeline-list">
                    <li class="aluno-timeline-empty">
                        <div class="aluno-timeline-empty-icon">
                            <i class="fas fa-history fa-2x text-muted"></i>
                        </div>
                        <p class="text-muted mb-0">
                            Os eventos mais recentes do aluno aparecerão aqui.
                        </p>
                    </li>
                </ul>
            `;
        }
    }
}

// Funções de atalhos da aba Histórico
function abrirAgendaCompleta() {
    const alunoId = document.getElementById('aluno_id_hidden').value;
    if (!alunoId) {
        logModalAluno('[Atalhos] ID do aluno não encontrado');
        return;
    }
    // Redireciona para página de agendamento com filtro do aluno
    window.location.href = `index.php?page=agendar-aula&aluno_id=${alunoId}`;
}

// Função de atalho para financeiro (sobrescreve a função existente quando chamada da aba Histórico)
// Nota: Já existe uma função abrirFinanceiroAluno(id) no código, mas esta versão busca o ID do modal
function abrirFinanceiroAlunoDoHistorico() {
    const alunoId = document.getElementById('aluno_id_hidden').value;
    if (!alunoId) {
        logModalAluno('[Atalhos] ID do aluno não encontrado');
        return;
    }
    // Usa a função existente com o ID do aluno
    if (typeof abrirFinanceiroAluno === 'function') {
        abrirFinanceiroAluno(alunoId);
    } else {
        // Fallback: redireciona diretamente
        window.location.href = `index.php?page=financeiro-faturas&aluno_id=${alunoId}`;
    }
}

function abrirTurmaTeorica() {
    const alunoId = document.getElementById('aluno_id_hidden').value;
    if (!alunoId) {
        logModalAluno('[Atalhos] ID do aluno não encontrado');
        return;
    }
    // Redireciona para página de turmas teóricas (pode precisar ajuste conforme estrutura)
    window.location.href = `index.php?page=turmas-teoricas&aluno_id=${alunoId}`;
}

// Função para adicionar nova matrícula
function adicionarMatricula() {
    const alunoId = document.getElementById('aluno_id_hidden').value;
    if (!alunoId) {
        mostrarAlerta('ID do aluno não encontrado', 'danger');
        return;
    }
    
    mostrarAlerta('Funcionalidade de nova matrícula em desenvolvimento', 'info');
}

// Função para adicionar novo documento
function adicionarDocumento() {
    const alunoId = document.getElementById('aluno_id_hidden').value;
    if (!alunoId) {
        mostrarAlerta('ID do aluno não encontrado', 'danger');
        return;
    }
    
    mostrarAlerta('Funcionalidade de upload de documentos em desenvolvimento', 'info');
}

// =====================================================
// INICIALIZAÇÃO DO MODAL DE ALUNO
// =====================================================
// Event listener para mudança de abas
document.addEventListener('DOMContentLoaded', function() {
    // Ajustar abas por perfil
    ajustarAbasPorPerfil();
    
    // Event listener para mudança de abas - RESETAR SCROLL AO TROCAR DE ABA
    const modalAluno = document.getElementById('modalAluno');
    if (modalAluno) {
        const tabButtons = modalAluno.querySelectorAll('#alunoTabs button[data-bs-toggle="tab"]');
        
        // Garantir que o listener seja registrado apenas uma vez
        if (tabButtons.length > 0 && !modalAluno.dataset.tabListenersAttached) {
            modalAluno.dataset.tabListenersAttached = 'true';
            
            tabButtons.forEach(button => {
                button.addEventListener('shown.bs.tab', function(event) {
                    const targetTab = event.target.getAttribute('data-bs-target').replace('#', '');
                    logModalAluno('[DEBUG aluno-modal] aba trocada para:', targetTab);
                    
                    // Resetar scroll do corpo do modal para o topo
                    const modalBody = modalAluno.querySelector('.aluno-modal-body');
                    if (modalBody) {
                        const scrollAntes = modalBody.scrollTop;
                        modalBody.scrollTop = 0;
                        logModalAluno('[DEBUG aluno-modal] Scroll resetado:', scrollAntes, '→', modalBody.scrollTop);
                        logModalAluno('[DEBUG aluno-modal] Elemento .aluno-modal-body encontrado:', modalBody);
                    } else {
                        console.error('[DEBUG aluno-modal] ERRO: .aluno-modal-body NÃO ENCONTRADO!');
                    }
                    
                    // Carregar dados da aba se necessário
                    const alunoId = document.getElementById('aluno_id_hidden')?.value;
                    
                    if (alunoId) {
                        carregarDadosAba(targetTab, alunoId);
                    }
                    
                    // Reforçar estrutura do modal
                    reforcarEstruturaModalAluno();
                });
            });
            
            logModalAluno('[Modal Aluno] Listeners de abas registrados');
        }
    }
});

// =====================================================
// CONTROLE DE LAYOUT RESPONSIVO PARA ALUNOS
// =====================================================

console.log('🔧 SCRIPT ALUNOS CARREGADO - Verificando dados PHP');
console.log('🔧 Total de alunos:', <?php echo count($alunos ?? []); ?>);
console.log('🔧 Alunos data:', <?php echo json_encode($alunos ?? []); ?>);

// Verificar se há parâmetros na URL que podem causar abertura automática do modal
const urlParams = new URLSearchParams(window.location.search);
console.log('🔧 Parâmetros da URL:', urlParams.toString());
if (urlParams.has('modal') || urlParams.has('novo') || urlParams.has('criar')) {
    console.log('⚠️ Parâmetro encontrado na URL que pode causar abertura automática do modal');
}

// PREVENIR ABERTURA AUTOMÁTICA DO MODAL
console.log('🔧 Verificando se modal deve abrir automaticamente...');
if (typeof forcarFecharModaisIniciais === 'function') {
    forcarFecharModaisIniciais('pre-check');
}
const modal = document.getElementById('modalAluno');
if (modal) {
    // Verificar se há algum CSS que está forçando o modal a aparecer
    const computedStyle = window.getComputedStyle(modal);
    if (computedStyle.display !== 'none' && !modal.hasAttribute('data-opened')) {
        console.log('ℹ️ modalAluno estava visível inadvertidamente - forçando display:none');
        modal.style.setProperty('display', 'none', 'important');
        modal.style.setProperty('visibility', 'hidden', 'important');
        document.body.style.overflow = 'auto';
    } else {
        console.log('✅ Modal está fechado corretamente');
    }
}

function toggleMobileLayoutAlunos() {
    console.log('🔧 toggleMobileLayoutAlunos executado - viewport:', window.innerWidth);
    const viewportWidth = window.innerWidth;
    const isMobile = viewportWidth <= 600;
    const tableContainer = document.querySelector('.table-container');
    const mobileCards = document.querySelector('.mobile-aluno-cards');

    console.log('🔧 isMobile:', isMobile, 'tableContainer:', !!tableContainer, 'mobileCards:', !!mobileCards);

    if (isMobile && mobileCards) {
        if (tableContainer) {
            tableContainer.style.display = 'none';
        }
        mobileCards.style.display = 'block';
        console.log('🔧 Layout mobile ativado');
    } else {
        if (tableContainer) {
            tableContainer.style.display = 'block';
        }
        if (mobileCards) {
            mobileCards.style.display = 'none';
        }
        console.log('🔧 Layout desktop ativado');
    }
}

// Listener para redimensionamento da janela
window.addEventListener('resize', toggleMobileLayoutAlunos);

// Chamada inicial para definir o layout correto
console.log('🔧 Inicializando layout responsivo para alunos');
toggleMobileLayoutAlunos();

// =====================================================
// FUNÇÕES PARA MODAIS RESPONSIVOS
// =====================================================

function ajustarModalResponsivo() {
    const viewportWidth = window.innerWidth;
    const viewportHeight = window.innerHeight;
    
    // Ajustar modal customizado de alunos
    const modalAlunoResponsivo = document.getElementById('modalAluno');
    if (modalAlunoResponsivo && modalAlunoResponsivo.style.display !== 'none') {
        const modalDialog = modalAlunoResponsivo.querySelector('.custom-modal-dialog');
        const modalContent = modalAlunoResponsivo.querySelector('.custom-modal-content');
        
        if (modalDialog && modalContent) {
            // Limpar estilos inline que possam interferir no layout flex
            modalDialog.style.top = '';
            modalDialog.style.left = '';
            modalDialog.style.right = '';
            modalDialog.style.bottom = '';
            modalDialog.style.width = '';
            modalDialog.style.maxHeight = '';
            modalContent.style.maxWidth = '';
            modalContent.style.maxHeight = '';
            // Não forçar height - deixar CSS fazer o trabalho
        }
    }
    
    // Ajustar modais Bootstrap padrão
    const modalsBootstrap = document.querySelectorAll('.modal.show');
    modalsBootstrap.forEach(modal => {
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            if (viewportWidth <= 768) {
                modalDialog.style.maxWidth = '95vw';
                modalDialog.style.margin = '0.5rem auto';
            } else {
                modalDialog.style.maxWidth = '90vw';
                modalDialog.style.margin = '1rem auto';
            }
        }
    });
}

// Listener para redimensionamento da janela
window.addEventListener('resize', ajustarModalResponsivo);
// Ajustar quando modais são abertos
document.addEventListener('shown.bs.modal', ajustarModalResponsivo);

// Ajustar modal customizado quando aberto
// Função removida - usando a versão mais completa acima

// Override das funções existentes para incluir ajuste responsivo
// NOTA: Esta função só será executada se abrirModalAluno já estiver definida
// A função abrirModalAluno já está definida como window.abrirModalAluno acima (linha ~6132)
if (typeof window.abrirModalAluno === 'function') {
    const originalAbrirModalAluno = window.abrirModalAluno;
    window.abrirModalAluno = function() {
        if (originalAbrirModalAluno) {
            originalAbrirModalAluno();
        }
        setTimeout(() => {
            if (typeof ajustarModalResponsivo === 'function') {
                ajustarModalResponsivo();
            }
            if (typeof validarLayoutModalAluno === 'function') {
                validarLayoutModalAluno(); // Validar layout após abrir
            }
        }, 100);
    };
}

// =====================================================
// VALIDAÇÃO DE LAYOUT DO MODAL DE ALUNO
// =====================================================

function validarLayoutModalAluno() {
    const modal = document.getElementById('modalAluno');
    if (!modal || modal.style.display === 'none') return;
    
    const dialog = modal.querySelector('.custom-modal-dialog');
    const content = modal.querySelector('.custom-modal-content');
    const body = modal.querySelector('.aluno-modal-body');
    const footer = modal.querySelector('.aluno-modal-footer');
    
    if (!dialog || !content || !body || !footer) {
        console.warn('⚠️ Estrutura do modal incompleta');
        return;
    }
    
    // Validar altura e posição do dialog
    const dialogRect = dialog.getBoundingClientRect();
    console.log('📐 Dialog:', {
        width: dialogRect.width,
        height: dialogRect.height,
        top: dialogRect.top,
        left: dialogRect.left
    });
    
    // Validar scroll do body
    const bodyScrollHeight = body.scrollHeight;
    const bodyClientHeight = body.clientHeight;
    const temScroll = bodyScrollHeight > bodyClientHeight;
    
    console.log('📜 Body scroll:', {
        scrollHeight: bodyScrollHeight,
        clientHeight: bodyClientHeight,
        temScroll: temScroll,
        scrollTop: body.scrollTop
    });
    
    // Validar footer visível
    const footerRect = footer.getBoundingClientRect();
    const contentRect = content.getBoundingClientRect();
    const footerVisivel = footerRect.top >= contentRect.top && 
                          footerRect.bottom <= contentRect.bottom;
    
    console.log('👣 Footer:', {
        top: footerRect.top,
        bottom: footerRect.bottom,
        contentTop: contentRect.top,
        contentBottom: contentRect.bottom,
        visivel: footerVisivel
    });
    
    // Validar estrutura flex
    const form = modal.querySelector('#formAluno');
    if (form) {
        const formStyle = window.getComputedStyle(form);
        console.log('📋 Form flex:', {
            display: formStyle.display,
            flexDirection: formStyle.flexDirection,
            height: formStyle.height
        });
    }
    
    if (temScroll && footerVisivel) {
        console.log('✅ Layout do modal validado com sucesso!');
    } else {
        console.warn('⚠️ Possíveis problemas no layout do modal');
    }
}

console.log('🔧 Sistema de modais responsivos inicializado');

// =====================================================
// VALIDAÇÃO DE CPF EM TEMPO REAL
// =====================================================

// Função utilitária para aplicar validação em todos os CPF da página
function garantirFeedbackCPF(input) {
    if (!input || !input.parentNode) return null;
    let feedback = input.parentNode.querySelector('.cpf-validation-feedback');
    if (!feedback) {
        feedback = document.createElement('div');
        feedback.className = 'cpf-validation-feedback';
        feedback.style.display = 'none';
        feedback.style.opacity = '0';
        feedback.style.visibility = 'hidden';
        input.parentNode.appendChild(feedback);
    }
    return feedback;
}

function aplicarValidacaoCPFFormulario() {
    const formulariosComCPF = document.querySelectorAll('form input[name="cpf"], #cpf');
    formulariosComCPF.forEach(input => {
        const feedback = garantirFeedbackCPF(input);
        if (!feedback) return;

        if (input.value.length === 14) {
            const cpfLimpo = input.value.replace(/\D/g, '');
            if (validarCPF(cpfLimpo)) {
                mostrarFeedbackCPF(input, true);
            } else {
                mostrarFeedbackCPF(input, false);
            }
        } else {
            ocultarFeedbackCPF(input);
        }
    });
}

function validarCPF(cpf) {
    // Remove caracteres não numéricos
    cpf = cpf.replace(/\D/g, '');
    
    // Verifica se tem 11 dígitos
    if (cpf.length !== 11) return false;
    
    // Verifica se todos os dígitos são iguais
    if (/^(\d)\1{10}$/.test(cpf)) return false;
    
    // Validação do primeiro dígito verificador
    let soma = 0;
    for (let i = 0; i < 9; i++) {
        soma += parseInt(cpf[i]) * (10 - i);
    }
    let resto = soma % 11;
    let digito1 = resto < 2 ? 0 : 11 - resto;
    
    if (parseInt(cpf[9]) !== digito1) return false;
    
    // Validação do segundo dígito verificador
    soma = 0;
    for (let i = 0; i < 10; i++) {
        soma += parseInt(cpf[i]) * (11 - i);
    }
    resto = soma % 11;
    let digito2 = resto < 2 ? 0 : 11 - resto;
    
    return parseInt(cpf[10]) === digito2;
}

function aplicarMascaraCPF(input) {
    let valor = input.value.replace(/\D/g, '');
    let valorFormatado = valor.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
    input.value = valorFormatado;
}

function mostrarFeedbackCPF(input, isValid) {
    if (!input || !input.parentNode) return;

    const feedback = garantirFeedbackCPF(input);
    if (!feedback) return;
    
    if (isValid) {
        input.classList.remove('invalid');
        input.classList.add('valid');
        feedback.textContent = 'CPF válido';
        feedback.className = 'cpf-validation-feedback valid';
        
        // Ocultar mensagem após 1.5 segundos quando válido, mantendo contorno verde
        setTimeout(() => {
            if (feedback.parentNode === input.parentNode) { // Verificar se ainda existe
                feedback.style.opacity = '0';
                feedback.style.visibility = 'hidden';
                // Manter apenas contorno verde do campo
                setTimeout(() => {
                    feedback.style.display = 'none';
                }, 300); // Aguardar transição CSS terminar
            }
        }, 1500);
    } else {
        input.classList.remove('valid');
        input.classList.add('invalid');
        feedback.textContent = 'CPF inválido';
        feedback.className = 'cpf-validation-feedback invalid';


        // Mostrar mensagem de erro por mais tempo
        feedback.style.display = 'block';
        feedback.style.opacity = '1';
        feedback.style.visibility = 'visible';
    }
}

function ocultarFeedbackCPF(input) {
    if (!input || !input.parentNode) return;
    const feedback = garantirFeedbackCPF(input);
    if (!feedback) return;

    input.classList.remove('valid', 'invalid');
    feedback.style.display = 'none';
    feedback.style.opacity = '0';
    feedback.style.visibility = 'hidden';
    feedback.textContent = '';
    feedback.className = 'cpf-validation-feedback';
}

// Aplicar validação de CPF quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    if (!cpfInput) return;
    garantirFeedbackCPF(cpfInput);

    cpfInput.addEventListener('input', function() {
        aplicarMascaraCPF(this);

        if (this.value.length === 14) {
            const cpfLimpo = this.value.replace(/\D/g, '');
            const isValid = validarCPF(cpfLimpo);
            mostrarFeedbackCPF(this, isValid);
        } else if (this.value.length < 14) {
            ocultarFeedbackCPF(this);
        }
    });

    cpfInput.addEventListener('blur', function() {
        if (this.value.length === 14) {
            const cpfLimpo = this.value.replace(/\D/g, '');
            const isValid = validarCPF(cpfLimpo);
            mostrarFeedbackCPF(this, isValid);
        }
    });

    cpfInput.addEventListener('focus', function() {
        if (this.value.length < 14) {
            ocultarFeedbackCPF(this);
        }
    });
});

// Função para validar CPF antes do envio do formulário
function validarCPFAntesEnvio() {
    const cpfInput = document.getElementById('cpf');
    if (cpfInput && cpfInput.value.length === 14) {
        const cpfLimpo = cpfInput.value.replace(/\D/g, '');
        return validarCPF(cpfLimpo);
    }
    return false;
}

// Função para validar CPF automaticamente após carregamento de dados
function validarCampoCPFAutomaticamente() {
    console.log('🔍 Validando CPF automaticamente...');
    const cpfInput = document.getElementById('cpf');
    
    if (cpfInput && cpfInput.value.length === 14) {
        garantirFeedbackCPF(cpfInput);

        const cpfLimpo = cpfInput.value.replace(/\D/g, '');
        const isValid = validarCPF(cpfLimpo);
        
        console.log(`✅ CPF automaticamente validado: ${cpfInput.value} -> ${isValid ? 'VÁLIDO' : 'INVÁLIDO'}`);
        
        if (isValid) {
            // Só mostrar feedback se for válido (ocultará automaticamente após 1.5s)
            mostrarFeedbackCPF(cpfInput, true);
        } else {
            // Se inválido, só marcar visualmente sem texto persistente
            cpfInput.classList.add('invalid');
            cpfInput.classList.remove('valid');
        }
    } else {
        console.log('⚠️ CPF não tem 14 caracteres, não validando automaticamente');
    }
}
console.log('✅ Validação de CPF inicializada');
// =====================================================
// MÁSCARA DE RENACH
// =====================================================

function aplicarMascaraRenach(input) {
    let valor = input.value.replace(/\D/g, '').toUpperCase();
    
    // Se não começar com PE, adiciona automaticamente
    if (!valor.startsWith('PE')) {
        valor = 'PE' + valor;
    }
    
    // Limita a 11 caracteres (PE + 9 dígitos)
    if (valor.length > 11) {
        valor = valor.substring(0, 11);
    }
    
    input.value = valor;
}
// Aplicar máscara de Renach quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    const renachInput = document.getElementById('renach');
    
    if (renachInput) {
        // Aplicar máscara enquanto digita
        renachInput.addEventListener('input', function() {
            aplicarMascaraRenach(this);
        });
        
        // Preencher "PE" automaticamente ao focar se estiver vazio
        renachInput.addEventListener('focus', function() {
            if (this.value === '') {
                this.value = 'PE';
            }
        });
    }
});

console.log('✅ Máscara de Renach inicializada');

// =====================================================
// FUNÇÕES DE GERENCIAMENTO DE FOTO DO ALUNO
// =====================================================

/**
 * Preview da foto selecionada
 */
function previewFotoAluno(input) {
    console.log('📷 Preview da foto do aluno iniciado...');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tipo de arquivo
        if (!file.type.startsWith('image/')) {
            alert('⚠️ Por favor, selecione apenas arquivos de imagem (JPG, PNG, GIF, WebP)');
            input.value = '';
            return;
        }
        
        // Validar tamanho (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            alert('⚠️ O arquivo deve ter no máximo 2MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('foto-preview-aluno');
            const container = document.getElementById('preview-container-aluno');
            const placeholder = document.getElementById('placeholder-foto-aluno');
            
            preview.src = e.target.result;
            container.style.display = 'block';
            placeholder.style.display = 'none';
            
            console.log('✅ Preview da foto do aluno carregado com sucesso');
        };
        reader.readAsDataURL(file);
    }
}
/**
 * Remover foto selecionada
 */
function removerFotoAluno() {
    console.log('🗑️ Removendo foto do aluno...');
    
    const input = document.getElementById('foto');
    const preview = document.getElementById('foto-preview-aluno');
    const container = document.getElementById('preview-container-aluno');
    const placeholder = document.getElementById('placeholder-foto-aluno');
    
    input.value = '';
    preview.src = '';
    container.style.display = 'none';
    placeholder.style.display = 'block';
    
    console.log('✅ Foto do aluno removida com sucesso');
}
/**
 * Carregar foto existente do aluno
 */
function carregarFotoExistenteAluno(caminhoFoto) {
    console.log('📷 Carregando foto existente do aluno:', caminhoFoto);
    
    const preview = document.getElementById('foto-preview-aluno');
    const container = document.getElementById('preview-container-aluno');
    const placeholder = document.getElementById('placeholder-foto-aluno');
    
    // Verificar se os elementos existem
    if (!preview || !container || !placeholder) {
        console.warn('⚠️ Elementos de preview de foto não encontrados no DOM');
        return;
    }
    
    if (caminhoFoto && caminhoFoto.trim() !== '') {
        // Construir URL completa da foto usando o mesmo método do modal de detalhes
        let urlFoto;
        if (caminhoFoto.startsWith('http://') || caminhoFoto.startsWith('https://')) {
            // URL absoluta já completa
            urlFoto = caminhoFoto;
        } else if (caminhoFoto.startsWith('/')) {
            // Caminho absoluto a partir da raiz do servidor
            // Extrair base path do projeto (ex: /cfc-bom-conselho)
            const origin = window.location.origin;
            const projectBase = window.location.pathname.split('/admin/')[0] || '';
            urlFoto = `${origin}${projectBase}${caminhoFoto}`;
        } else {
            // Caminho relativo - construir baseado na estrutura do projeto
            // O caminho salvo no banco é: admin/uploads/alunos/nome_arquivo
            
            // Extrair base path do projeto (ex: /cfc-bom-conselho)
            const origin = window.location.origin;
            const projectBase = window.location.pathname.split('/admin/')[0] || '';
            const baseUrl = `${origin}${projectBase}`;
            
            // Normalizar caminho (remover barras iniciais se houver)
            const normalizedFoto = caminhoFoto.replace(/^\/+/, '');
            
            // Se o caminho já começa com admin/, usar direto
            if (normalizedFoto.startsWith('admin/')) {
                urlFoto = `${baseUrl}/${normalizedFoto}`;
            } else if (normalizedFoto.startsWith('uploads/')) {
                // Se começa com uploads/, adicionar admin/ antes
                urlFoto = `${baseUrl}/admin/${normalizedFoto}`;
            } else {
                // Se não começa com admin/ nem uploads/, assumir que é apenas o nome do arquivo
                // e construir o caminho completo: admin/uploads/alunos/nome_arquivo
                urlFoto = `${baseUrl}/admin/uploads/alunos/${normalizedFoto}`;
            }
        }
        
        // Validar que urlFoto foi construída corretamente (não pode ter ${fotoUrl} literal)
        if (urlFoto && (urlFoto.includes('${fotoUrl}') || urlFoto.includes('${') || urlFoto.trim() === '')) {
            console.error('[FOTO] Erro: URL da foto inválida ou contém template literal não processado:', urlFoto);
            // Usar placeholder se houver erro na construção
            urlFoto = null;
        }
        
        if (urlFoto) {
            console.log('[FOTO] URL da foto do aluno construída:', urlFoto);
            console.log('[FOTO] Base URL usada:', window.location.origin + (window.location.pathname.split('/admin/')[0] || ''));
            
            // Limpar handlers anteriores para evitar múltiplos eventos
            preview.onload = null;
            preview.onerror = null;
            
            // Definir handlers antes de definir src
            preview.onload = function() {
                console.log('[FOTO] Foto existente do aluno carregada com sucesso');
                container.style.display = 'block';
                placeholder.style.display = 'none';
            };
            
            preview.onerror = function() {
                console.warn('[FOTO] Erro ao carregar foto do aluno (404 ou outro erro):', urlFoto);
                // Se der erro, mostrar placeholder e esconder preview
                // Não tentar carregar novamente para evitar loop de 404
                container.style.display = 'none';
                placeholder.style.display = 'block';
                // Limpar src para evitar tentativas repetidas
                preview.src = '';
                preview.onerror = null; // Remover handler para evitar loops
            };
            
            // Definir src por último para disparar o carregamento
            preview.src = urlFoto;
        } else {
            // Sem foto ou erro na construção - mostrar placeholder
            console.log('[FOTO] Sem foto ou erro na construção, mostrando placeholder');
            container.style.display = 'none';
            placeholder.style.display = 'block';
        }
    } else {
        // Caminho vazio ou inválido
        console.log('[FOTO] Caminho da foto vazio ou inválido:', caminhoFoto);
        container.style.display = 'none';
        placeholder.style.display = 'block';
        
        console.log('ℹ️ Nenhuma foto existente do aluno encontrada');
    }
}

console.log('✅ Funções de foto do aluno inicializadas');
</script>
</script>