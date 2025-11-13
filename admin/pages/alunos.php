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

// Headers para evitar cache em produção - removidos pois já há saída HTML
// header("Cache-Control: no-cache, no-store, must-revalidate");
// header("Pragma: no-cache");
// header("Expires: 0");

// Debug: Verificar se os dados estão sendo carregados
error_log("DEBUG ALUNOS: Total de alunos carregados: " . count($alunos));
error_log("DEBUG ALUNOS: Primeiro aluno: " . json_encode($alunos[0] ?? 'nenhum'));
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
    transform: scale(1.08);
}

body.visualizar-aluno-open {
    overflow: hidden !important;
}

/* Modal de edição/novo aluno */
#modalAluno {
    z-index: 1060 !important;
    display: none;
    visibility: hidden;
}

#modalAluno.show {
    display: none;
    visibility: hidden;
}

/* Permitir abertura quando definido via JavaScript */
#modalAluno[style*="display: block"],
#modalAluno[style*="display: flex"] {
    display: block !important;
    visibility: visible !important;
}

/* Garantir que modal seja visível quando aberto */
#modalAluno[data-opened="true"] {
    display: block !important;
    visibility: visible !important;
}

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
   ESTILOS PERSONALIZADOS PARA MODAL DE ALUNOS
   Sobrescrevendo Bootstrap com especificidade máxima
   ===================================================== */

/* Modal quase fullscreen com margens pequenas */
.modal#modalAluno .modal-dialog {
    max-width: calc(100vw - 2rem) !important;
    max-height: calc(100vh - 2rem) !important;
    width: calc(100vw - 2rem) !important;
    height: calc(100vh - 2rem) !important;
    margin: 1rem !important;
    padding: 0 !important;
}

/* Para telas muito pequenas, usar margens menores */
@media (max-width: 576px) {
    .modal#modalAluno .modal-dialog {
        max-width: calc(100vw - 0.5rem) !important;
        max-height: calc(100vh - 0.5rem) !important;
        width: calc(100vw - 0.5rem) !important;
        height: calc(100vh - 0.5rem) !important;
        margin: 0.25rem !important;
    }
}

.modal#modalAluno .modal-content {
    height: 100% !important;
    border-radius: 0.5rem !important;
    border: none !important;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

.modal#modalAluno .modal-body {
    max-height: calc(100% - 120px) !important;
    overflow-y: auto !important;
    padding: 1.5rem !important;
    background-color: #f8f9fa !important;
}

/* Responsividade do modal-body */
@media (max-width: 768px) {
    .modal#modalAluno .modal-body {
        padding: 1rem !important;
    }
}

@media (max-width: 576px) {
    .modal#modalAluno .modal-body {
        padding: 0.75rem !important;
    }
}

.modal#modalAluno .modal-header {
    background: linear-gradient(135deg, #0d6efd 0%, #0b5ed7 100%) !important;
    color: white !important;
    border-bottom: none !important;
    padding: 1rem 1.5rem !important;
    border-radius: 0.5rem 0.5rem 0 0 !important;
}

/* Responsividade do modal-header */
@media (max-width: 768px) {
    .modal#modalAluno .modal-header {
        padding: 0.75rem 1rem !important;
    }
}

@media (max-width: 576px) {
    .modal#modalAluno .modal-header {
        padding: 0.5rem 0.75rem !important;
    }
}

.modal#modalAluno .modal-title {
    color: white !important;
    font-weight: 600 !important;
    font-size: 1.5rem !important;
}

/* Responsividade do modal-title */
@media (max-width: 768px) {
    .modal#modalAluno .modal-title {
        font-size: 1.25rem !important;
    }
}

@media (max-width: 576px) {
    .modal#modalAluno .modal-title {
        font-size: 1.1rem !important;
    }
}

.modal#modalAluno .btn-close {
    filter: invert(1) !important;
}

.modal#modalAluno .modal-footer {
    background-color: #f8f9fa !important;
    border-top: 1px solid #dee2e6 !important;
    padding: 1rem 1.5rem !important;
    border-radius: 0 0 0.5rem 0.5rem !important;
}

/* Responsividade do modal-footer */
@media (max-width: 768px) {
    .modal#modalAluno .modal-footer {
        padding: 0.75rem 1rem !important;
    }
}

@media (max-width: 576px) {
    .modal#modalAluno .modal-footer {
        padding: 0.5rem 0.75rem !important;
    }
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

/* =====================================================
   RESPONSIVIDADE PARA ABAS DO MODAL - MOBILE
   ===================================================== */

@media (max-width: 768px) {
    /* Garantir que modal seja visível no mobile */
    #modalAluno[data-opened="true"] {
        z-index: 99999 !important;
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        width: 100vw !important;
        height: 100vh !important;
        background: rgba(0,0,0,0.5) !important;
    }
    
    /* Abas do modal - Mobile */
    .modal#modalAluno .nav-tabs {
        flex-wrap: wrap !important;
        gap: 0.25rem !important;
        padding: 0.5rem !important;
        background-color: #f8f9fa !important;
        border-radius: 0.5rem !important;
        margin-bottom: 1rem !important;
        border: none !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link {
        flex: 1 1 calc(50% - 0.125rem) !important;
        min-width: calc(50% - 0.125rem) !important;
        max-width: calc(50% - 0.125rem) !important;
        padding: 0.5rem 0.25rem !important;
        font-size: 0.75rem !important;
        text-align: center !important;
        border-radius: 0.375rem !important;
        margin: 0 !important;
        border: 1px solid #dee2e6 !important;
        background-color: white !important;
        color: #6c757d !important;
        transition: all 0.2s ease !important;
        display: flex !important;
        flex-direction: column !important;
        align-items: center !important;
        justify-content: center !important;
        min-height: 60px !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link:hover {
        background-color: #e9ecef !important;
        color: #495057 !important;
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link.active {
        background-color: #0d6efd !important;
        color: white !important;
        border-color: #0d6efd !important;
        box-shadow: 0 2px 8px rgba(13, 110, 253, 0.3) !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link i {
        display: block !important;
        font-size: 1.1rem !important;
        margin-bottom: 0.25rem !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link span {
        display: block !important;
        font-weight: 500 !important;
        line-height: 1.2 !important;
        font-size: 0.7rem !important;
    }
    
    /* Conteúdo das abas - Mobile */
    .modal#modalAluno .tab-content {
        padding: 0.5rem !important;
    }
    
    .modal#modalAluno .tab-pane {
        padding: 0.5rem !important;
    }
}

@media (max-width: 480px) {
    /* Abas em uma coluna para telas muito pequenas */
    .modal#modalAluno .nav-tabs .nav-link {
        flex: 1 1 100% !important;
        min-width: 100% !important;
        max-width: 100% !important;
        margin-bottom: 0.25rem !important;
        min-height: 50px !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link:last-child {
        margin-bottom: 0 !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link i {
        font-size: 1rem !important;
    }
    
    .modal#modalAluno .nav-tabs .nav-link span {
        font-size: 0.65rem !important;
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

#modalAluno.custom-modal {
    display: none;
    position: fixed;
    inset: 0;
    min-height: 100vh;
    background: rgba(15, 23, 42, 0.55);
    z-index: 9999;
    padding: clamp(16px, 3vh, 32px);
    align-items: center;
    justify-content: center;
}

#modalAluno.custom-modal[data-opened="true"],
#modalAluno.custom-modal[style*="display: block"],
#modalAluno.custom-modal[style*="display:block"] {
    display: flex !important;
}

#modalAluno .custom-modal-dialog {
    width: min(94vw, 1200px);
    max-height: 88vh;
    display: flex;
    justify-content: center;
    align-items: stretch;
    margin: 0;
}

#modalAluno .custom-modal-content {
    width: 100%;
    max-height: 88vh;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
    background: #ffffff;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;
}

.aluno-modal-form {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}

.aluno-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    background: var(--primary-600, #0d6efd);
    color: #fff;
    padding: 16px 24px;
    min-height: var(--aluno-modal-header-h, 56px);
}

.aluno-modal-title {
    margin: 0;
    font-size: 1.35rem;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 10px;
    color: inherit;
}

.aluno-modal-title i {
    font-size: 1.25rem;
}

.aluno-modal-close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.2);
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    opacity: 1;
    background-image: none;
    color: #ffffff;
}

.aluno-modal-close:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
}

.aluno-modal-close::after {
    content: "\00d7";
    font-size: 1.4rem;
    line-height: 1;
    color: #fff;
    font-weight: 300;
}

.aluno-modal-body {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding: 0;
    background: #f8fafc;
    flex: 1;
    min-height: 0;
}

.aluno-modal-tabs {
    display: flex;
    align-items: center;
    padding: 0 24px 12px;
    background: #ffffff;
    border-bottom: 1px solid #e2e8f0;
    min-height: var(--aluno-modal-tabs-h, 60px);
}

.aluno-tabs {
    border-bottom: none;
    display: flex;
    gap: 12px;
    width: 100%;
    height: 48px;
    align-items: flex-end;
}

.aluno-tabs .nav-item {
    display: flex;
}

.aluno-tabs .nav-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0 16px;
    border: none;
    border-radius: 10px 10px 0 0;
    color: #475569;
    font-weight: 600;
    background: transparent;
    height: 48px;
    position: relative;
    transition: color 0.2s ease, background 0.2s ease;
}

.aluno-tabs .nav-link i {
    font-size: 1.1rem;
}

.aluno-tabs .nav-link:hover {
    color: #0f172a;
    background: rgba(15, 23, 42, 0.05);
}

.aluno-tabs .nav-link.active {
    color: #0f172a;
    background: #ffffff;
}

.aluno-tabs .nav-link.active::after {
    content: "";
    position: absolute;
    left: 12px;
    right: 12px;
    bottom: -4px;
    height: 2px;
    background: var(--primary-600, #0d6efd);
    border-radius: 999px;
}

.aluno-modal-body {
    display: flex;
    flex-direction: column;
    flex: 1;
    min-height: 0;
}

#modalAluno .custom-modal-content {
    --aluno-modal-max-h: 88vh;
    --aluno-modal-header-h: 56px;
    --aluno-modal-tabs-h: 60px;
    --aluno-modal-footer-h: 64px;
}

.aluno-modal-panel {
    flex: 1;
    --aluno-panel-available: calc(var(--aluno-modal-max-h) - (var(--aluno-modal-header-h) + var(--aluno-modal-tabs-h) + var(--aluno-modal-footer-h)));
    min-height: min(600px, max(480px, var(--aluno-panel-available)));
    max-height: max(320px, var(--aluno-panel-available));
    padding: 20px;
    overflow: auto;
    background: #f8fafc;
    scrollbar-width: thin;
}

.aluno-modal-panel::-webkit-scrollbar {
    width: 8px;
}

.aluno-modal-panel::-webkit-scrollbar-track {
    background: #edf2f7;
    border-radius: 4px;
}

.aluno-modal-panel::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 4px;
}

.aluno-modal-panel::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

.aluno-tab-content .tab-pane {
    padding: 0;
}

.aluno-modal-panel .row {
    --bs-gutter-x: 1rem;
    --bs-gutter-y: 1rem;
}

.aluno-modal-panel h6 {
    font-size: 0.88rem !important;
    font-weight: 600 !important;
    color: #1e293b !important;
    display: flex;
    align-items: center;
    gap: 8px;
}

#modalAluno .form-label {
    font-size: 0.8rem !important;
    font-weight: 600 !important;
    color: #475569 !important;
    margin-bottom: 0.35rem !important;
}

#modalAluno .form-control,
#modalAluno .form-select {
    min-height: 40px !important;
    padding: 0 12px !important;
    font-size: 0.94rem !important;
    border-radius: 10px !important;
    border: 1px solid #cbd5e1 !important;
    color: #1f2937 !important;
}

#modalAluno .form-control:focus,
#modalAluno .form-select:focus {
    border-color: var(--primary-600, #0d6efd) !important;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.18) !important;
}

#modalAluno textarea.form-control {
    min-height: 96px;
    padding: 12px !important;
    resize: vertical;
}

.aluno-modal-footer {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 12px;
    padding: 16px 24px;
    background: #ffffff;
    border-top: 1px solid #e2e8f0;
    position: sticky;
    bottom: 0;
    flex-shrink: 0;
    min-height: var(--aluno-modal-footer-h, 64px);
}

.aluno-btn-cancelar,
.aluno-btn-salvar {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 18px;
    min-height: 40px;
    font-weight: 600;
    border-radius: 10px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
}

.aluno-btn-cancelar {
    border: 1px solid transparent;
    background: transparent;
    color: #475569;
}

.aluno-btn-cancelar:hover {
    background: #f1f5f9;
    color: #0f172a;
}
.aluno-btn-salvar {
    padding-inline: 20px;
}
.aluno-btn-salvar:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 20px rgba(13, 110, 253, 0.18);
}
.aluno-modal-footer .btn i {
    font-size: 0.95rem;
}
@media (max-width: 1024px) {
    #modalAluno .custom-modal-dialog {
        width: min(95vw, 1024px);
    }

    .aluno-modal-panel {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    #modalAluno .custom-modal-dialog {
        width: 100%;
        max-height: 90vh;
    }

    #modalAluno .custom-modal-content {
        border-radius: 10px;
        --aluno-modal-max-h: 90vh;
    }

    .aluno-modal-header {
        padding: 14px 20px;
    }

    .aluno-modal-tabs {
        padding: 0 16px;
        overflow-x: auto;
    }

    .aluno-tabs {
        gap: 8px;
    }

    .aluno-tabs .nav-link {
        padding: 0 14px;
        white-space: nowrap;
    }

    .aluno-modal-panel {
        padding: 18px;
    }
}

@media (max-width: 576px) {
    #modalAluno .custom-modal-dialog {
        padding: 0;
    }

    .aluno-modal-header,
    .aluno-modal-footer {
        padding: 14px 16px;
    }

    .aluno-modal-panel {
        padding: 16px;
    }

    .aluno-tabs {
        height: auto;
    }

    .aluno-tabs .nav-link {
        height: 44px;
    }

    .aluno-btn-cancelar,
    .aluno-btn-salvar {
        flex: 1 1 auto;
        justify-content: center;
    }

    .aluno-modal-footer {
        flex-wrap: wrap;
    }
}
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

<!-- Indicadores compactos -->
<div class="alunos-kpi-bar" role="group" aria-label="Indicadores de alunos">
    <div class="alunos-kpi-item">
        <i class="fas fa-users alunos-kpi-icon total" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Total</span>
        <span class="alunos-kpi-value" id="totalAlunos">
            <?php echo count($alunos); ?>
        </span>
    </div>
    <div class="alunos-kpi-item">
        <i class="fas fa-check-circle alunos-kpi-icon ativos" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Ativos</span>
        <span class="alunos-kpi-value" id="alunosAtivos">
            <?php echo count(array_filter($alunos, function($a) { return ($a['status'] ?? '') === 'ativo'; })); ?>
        </span>
    </div>
    <div class="alunos-kpi-item">
        <i class="fas fa-clock alunos-kpi-icon formacao" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Em formação</span>
        <span class="alunos-kpi-value" id="emFormacao">
            <?php echo count(array_filter($alunos, function($a) { return ($a['status'] ?? '') === 'ativo'; })); ?>
        </span>
    </div>
    <div class="alunos-kpi-item">
        <i class="fas fa-graduation-cap alunos-kpi-icon concluidos" aria-hidden="true"></i>
        <span class="alunos-kpi-label">Concluídos</span>
        <span class="alunos-kpi-value" id="concluidos">
            <?php echo count(array_filter($alunos, function($a) { return ($a['status'] ?? '') === 'concluido'; })); ?>
        </span>
    </div>
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
                                // Mostrar operações dinâmicas em vez de categoria única
                                $operacoes = $aluno['operacoes'];
                                if (is_string($operacoes)) {
                                    $operacoes = json_decode($operacoes, true);
                                }
                                
                                if (!empty($operacoes) && is_array($operacoes)) {
                                    foreach ($operacoes as $index => $operacao) {
                                        $badgeClass = '';
                                        $tipoText = '';
                                        
                                        $tipo = $operacao['tipo'] ?? 'desconhecido';
                                        $categoria = $operacao['categoria'] ?? $operacao['categoria_cnh'] ?? 'N/A';
                                        
                                        switch ($tipo) {
                                            case 'primeira_habilitacao':
                                                $badgeClass = 'bg-primary';
                                                $tipoText = '';
                                                break;
                                            case 'adicao':
                                                $badgeClass = 'badge-status-ativo';
                                                $tipoText = '';
                                                break;
                                            case 'mudanca':
                                                $badgeClass = 'badge-status-pendente';
                                                $tipoText = '';
                                                break;
                                            case 'aula_avulsa':
                                                $badgeClass = 'bg-info';
                                                $tipoText = '';
                                                break;
                                            default:
                                                $badgeClass = 'badge-status-inativo';
                                                $tipoText = '';
                                        }
                                        
                                        if ($index > 0) echo '<br>';
                                        echo '<span class="badge ' . $badgeClass . ' me-1" title="' . ucfirst(str_replace('_', ' ', $tipo)) . '">' . 
                                             htmlspecialchars($categoria) . '</span>';
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
                                    
                                    <?php if ($isAdmin || $user['tipo'] === 'secretaria'): ?>
                                    <button type="button" class="btn btn-sm btn-primary-action btn-add action-icon-btn" 
                                            onclick="agendarAula(<?php echo $aluno['id']; ?>)" 
                                            title="Agendar nova aula para este aluno" data-tooltip="Agendar nova aula para este aluno"
                                            style="display: inline-flex !important; visibility: visible !important; opacity: 1 !important;">
                                        <i class="fas fa-calendar-plus" style="display: inline-block !important; visibility: visible !important; opacity: 1 !important;"></i>
                                    </button>
                                    <?php endif; ?>
                                    
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
                                if (!empty($aluno['operacoes'])) {
                                    // Verificar se operacoes é string JSON e converter para array
                                    $operacoes = $aluno['operacoes'];
                                    if (is_string($operacoes)) {
                                        $operacoes = json_decode($operacoes, true);
                                    }
                                    
                                    if (is_array($operacoes) && !empty($operacoes)) {
                                        $categorias = array_column($operacoes, 'categoria');
                                        echo implode(', ', array_unique($categorias));
                                    } else {
                                        echo htmlspecialchars($aluno['categoria_cnh'] ?? 'N/A');
                                    }
                                } else {
                                    echo htmlspecialchars($aluno['categoria_cnh'] ?? 'N/A');
                                }
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
                        <button type="button" class="btn btn-sm btn-info" onclick="agendarAula(<?php echo $aluno['id']; ?>)" title="Agendar aula">
                            <i class="fas fa-calendar-plus"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="historicoAluno(<?php echo $aluno['id']; ?>)" title="Histórico de aulas">
                            <i class="fas fa-history"></i>
                        </button>
                        <?php if ($aluno['status'] === 'ativo'): ?>
                        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="desativarAluno(<?php echo $aluno['id']; ?>)" title="Desativar aluno">
                            <i class="fas fa-ban"></i>
                        </button>
                        <?php else: ?>
                        <button type="button" class="btn btn-sm btn-outline-success" onclick="ativarAluno(<?php echo $aluno['id']; ?>)" title="Ativar aluno">
                            <i class="fas fa-check"></i>
                        </button>
                        <?php endif; ?>
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
<!-- Modal Customizado para Cadastro/Edição de Aluno -->
<div id="modalAluno" class="custom-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <form id="formAluno" method="POST" class="aluno-modal-form">
                <div class="aluno-modal-header modal-header">
                    <h2 class="aluno-modal-title" id="modalTitle">
                        <i class="fas fa-user-graduate me-2" aria-hidden="true"></i>
                        <span>Novo Aluno</span>
                    </h2>
                    <button type="button" class="btn-close aluno-modal-close" onclick="fecharModalAluno()" aria-label="Fechar modal"></button>
                </div>
                <input type="hidden" name="acao" id="acaoAluno" value="criar">
                <input type="hidden" name="aluno_id" id="aluno_id_hidden" value="">

                <div class="aluno-modal-tabs">
                    <ul class="nav nav-tabs aluno-tabs" id="alunoTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="dados-tab" data-bs-toggle="tab" data-bs-target="#dados" type="button" role="tab">
                                <i class="fas fa-user"></i>
                                <span>Dados</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="matricula-tab" data-bs-toggle="tab" data-bs-target="#matricula" type="button" role="tab">
                                <i class="fas fa-graduation-cap"></i>
                                <span>Matrícula</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" id="financeiro-tab-container" style="display: none;">
                            <button class="nav-link" id="financeiro-tab" data-bs-toggle="tab" data-bs-target="#financeiro" type="button" role="tab">
                                <i class="fas fa-dollar-sign"></i>
                                <span>Financeiro</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation" id="documentos-tab-container" style="display: none;">
                            <button class="nav-link" id="documentos-tab" data-bs-toggle="tab" data-bs-target="#documentos" type="button" role="tab">
                                <i class="fas fa-file-alt"></i>
                                <span>Documentos</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="agenda-tab" data-bs-toggle="tab" data-bs-target="#agenda" type="button" role="tab">
                                <i class="fas fa-calendar-alt"></i>
                                <span>Agenda</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="teorico-tab" data-bs-toggle="tab" data-bs-target="#teorico" type="button" role="tab">
                                <i class="fas fa-chalkboard-teacher"></i>
                                <span>Teórico</span>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="historico-tab" data-bs-toggle="tab" data-bs-target="#historico" type="button" role="tab">
                                <i class="fas fa-history"></i>
                                <span>Histórico</span>
                            </button>
                        </li>
                    </ul>
                </div>

                <div class="aluno-modal-body modal-body">
                    <div class="aluno-modal-panel">
                    <div class="tab-content aluno-tab-content" id="alunoTabsContent">
                        <!-- Aba Dados -->
                        <div class="tab-pane fade show active" id="dados" role="tabpanel">
                    
                    <div class="container-fluid" style="padding: 0;">
                        <!-- Seção 1: Informações Pessoais -->
                        <div class="row mb-2">
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
                                    <label for="renach" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Renach *</label>
                                    <input type="text" class="form-control" id="renach" name="renach" required 
                                           placeholder="PE000000000" maxlength="11" style="padding: 0.4rem; font-size: 0.85rem;"
                                           data-mask="renach">
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
                                    <label for="naturalidade_estado" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Estado</label>
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
                                    <label for="naturalidade_municipio" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Município</label>
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
                            <div class="col-md-3">
                                <div class="mb-1">
                                    <label for="numero" class="form-label" style="font-size: 0.8rem; margin-bottom: 0.1rem;">Número</label>
                                    <input type="text" class="form-control" id="numero" name="numero" 
                                           placeholder="123" style="padding: 0.4rem; font-size: 0.85rem;">
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
                        
                        <!-- Aba Matrícula/Serviço -->
                        <div class="tab-pane fade" id="matricula" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-1 mb-3">
                                        <i class="fas fa-graduation-cap me-1"></i>Matrículas do Aluno
                                    </h6>
                                    <div id="matriculas-container">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p>Carregando matrículas...</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="adicionarMatricula()">
                                            <i class="fas fa-plus me-1"></i>Nova Matrícula
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Financeiro -->
                        <div class="tab-pane fade" id="financeiro" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-1 mb-3">
                                        <i class="fas fa-dollar-sign me-1"></i>Informações Financeiras
                                    </h6>
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        Sistema financeiro em desenvolvimento. Esta funcionalidade será implementada em breve.
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Documentos -->
                        <div class="tab-pane fade" id="documentos" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-1 mb-3">
                                        <i class="fas fa-file-alt me-1"></i>Documentos do Aluno
                                    </h6>
                                    <div id="documentos-container">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p>Carregando documentos...</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <button type="button" class="btn btn-sm btn-primary" onclick="adicionarDocumento()">
                                            <i class="fas fa-plus me-1"></i>Adicionar Documento
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Agenda/Aulas -->
                        <div class="tab-pane fade" id="agenda" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-1 mb-3">
                                        <i class="fas fa-calendar-alt me-1"></i>Aulas Agendadas
                                    </h6>
                                    <div id="aulas-container">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p>Carregando aulas...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Teórico -->
                        <div class="tab-pane fade" id="teorico" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-1 mb-3">
                                        <i class="fas fa-chalkboard-teacher me-1"></i>Turma Teórica
                                    </h6>
                                    <div id="turma-container">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p>Carregando informações da turma...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Aba Histórico & Auditoria -->
                        <div class="tab-pane fade" id="historico" role="tabpanel">
                            <div class="row">
                                <div class="col-12">
                                    <h6 class="text-primary border-bottom pb-1 mb-3">
                                        <i class="fas fa-history me-1"></i>Histórico Completo
                                    </h6>
                                    <div id="historico-container">
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-spinner fa-spin fa-2x mb-2"></i>
                                            <p>Carregando histórico...</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="aluno-modal-footer modal-footer">
                    <button type="button" class="btn aluno-btn-cancelar" onclick="fecharModalAluno()">
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


<!-- Modal personalizado para Visualização de Aluno -->
<div id="modalVisualizarAluno" class="visualizar-overlay" role="dialog" aria-labelledby="modalVisualizarAlunoLabel" aria-hidden="true">
    <div class="visualizar-dialog">
        <div class="visualizar-header">
            <h5 class="visualizar-title m-0" id="modalVisualizarAlunoLabel">
                <i class="fas fa-eye me-2"></i>Detalhes do Aluno
            </h5>
            <button type="button" class="visualizar-close" onclick="fecharModalVisualizarAluno()" aria-label="Fechar modal">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="visualizar-body" id="modalVisualizarAlunoBody">
            <!-- Conteúdo será carregado via JavaScript -->
        </div>
        <div class="visualizar-footer">
            <button type="button" class="btn btn-light border" onclick="fecharModalVisualizarAluno()">
                <i class="fas fa-times me-1"></i>Fechar
            </button>
            <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                <i class="fas fa-edit me-1"></i>Editar Aluno
            </button>
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
    
    // Adicionar event listener para o formulário
    const formAluno = document.getElementById('formAluno');
    if (formAluno) {
        formAluno.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarAluno();
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

function carregarMunicipios(estado) {
    const municipioSelect = document.getElementById('naturalidade_municipio');
    
    if (!municipioSelect) {
        console.error('❌ Select de município não encontrado');
        return Promise.reject('Select de município não encontrado');
    }
    
    console.log('🔄 Carregando municípios para estado:', estado);
    
    // Se já está no meio de um carregamento para este estado, retornar a promessa existente
    if (carregamentoMunicipios[estado]) {
        console.log('⏭️ Carregamento já em andamento para', estado, '- aguardando...');
        return carregamentoMunicipios[estado];
    }
    
    // Verificar se os municípios já estão carregados para este estado
    const opcoesAtuais = Array.from(municipioSelect.options).map(o => o.value).slice(1);
    const municipiosEsperados = getMunicipiosPorEstado(estado);
    
    // Se não há municípios para este estado, manter placeholder e desabilitar
    if (municipiosEsperados.length === 0) {
        municipioSelect.innerHTML = '<option value="">Estado não configurado</option>';
        municipioSelect.disabled = true;
        console.warn('⚠️ Estado', estado, 'não possui municípios configurados');
        return Promise.resolve();
    }
    
    if (opcoesAtuais.length > 0 && municipiosEsperados.length === opcoesAtuais.length) {
        console.log('✅ Municípios já estão carregados para', estado);
        municipioSelect.disabled = false;
        return Promise.resolve();
    }
    
    // Mostrar indicador de carregamento
    municipioSelect.innerHTML = '<option value="">Carregando municípios...</option>';
    municipioSelect.disabled = true;
    
    // Usar lista estática de municípios (resolvendo problema de CSP)
    const municipios = getMunicipiosPorEstado(estado);
    
    console.log('Municípios encontrados:', municipios); // Debug
    
    // Criar e armazenar a Promise para evitar carregamentos duplicados
    const promiseEmAndamento = new Promise((resolve, reject) => {
        setTimeout(() => {
            try {
                municipioSelect.innerHTML = '<option value="">Selecione o município...</option>';
                
                // Ordenar municípios alfabeticamente
                municipios.sort((a, b) => a.localeCompare(b, 'pt-BR'));
                
                municipios.forEach(municipio => {
                    const option = document.createElement('option');
                    option.value = municipio;
                    option.textContent = municipio;
                    municipioSelect.appendChild(option);
                });
                
                municipioSelect.disabled = false;
                console.log('✅ Municípios carregados no select:', municipioSelect.options.length);
                
                // Limpar a referência ao carregamento
                delete carregamentoMunicipios[estado];
                
                // Disparar evento de mudança para notificar que os municípios foram carregados
                municipioSelect.dispatchEvent(new Event('change'));
                
                resolve();
            } catch (error) {
                console.error('❌ Erro ao carregar municípios', error);
                delete carregamentoMunicipios[estado];
                reject(error);
            }
        }, 100);
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
function getMunicipiosPorEstado(estado) {
    console.log('Buscando municípios para estado:', estado); // Debug
    
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
            'Lontras'
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
        // FORÇAR abertura do modal para edição
        modal.style.setProperty('display', 'block', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.setAttribute('data-opened', 'true'); // Marcar como aberto intencionalmente
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
window.editarAluno = function(id) {
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
    console.log(`📡 URL completa: ${API_CONFIG.getRelativeApiUrl('ALUNOS')}?id=${id}`);
    
    // Buscar dados do aluno (usando nova API funcional)
    const timestamp = new Date().getTime();
    const url = API_CONFIG.getRelativeApiUrl('ALUNOS') + `?id=${id}&t=${timestamp}`;
    console.log(`📡 URL final da requisição: ${url}`);
    
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
            console.log('📄 Dados do aluno:', data.aluno);
            console.log('📄 Naturalidade do aluno:', data.aluno?.naturalidade);
            console.log('📄 Todos os campos do aluno:', Object.keys(data.aluno || {}));
            console.log('📄 Estrutura completa do aluno:', JSON.stringify(data.aluno, null, 2));
            
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
                
                // Função melhorada para garantir que o modal esteja totalmente carregado
                function esperarModalPronto() {
                    return new Promise((resolve) => {
                        const checkModal = () => {
                            const modal = document.getElementById('modalAluno');
                            const form = document.getElementById('formAluno');
                            const estadoSelect = document.getElementById('naturalidade_estado');
                            
                            if (modal && modal.style.display === 'block' && 
                                form && estadoSelect) {
                                console.log('✅ Modal totalmente carregado e pronto');
                                resolve();
                            } else {
                                console.log('⏳ Aguardando modal carregar...', {
                                    modalVisible: modal?.style.display === 'block',
                                    formExists: !!form,
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
                    console.log('🔄 Callando preencherFormularioAluno com dados:', data.aluno);
                    console.log('🔄 Naturalidade disponível:', data.aluno.naturalidade);
                    console.log('🔄 Timestamp:', new Date().toISOString());
                    preencherFormularioAluno(data.aluno);
                    console.log('✅ Formulário preenchido - função executada');
                    
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
    console.log('📝 Preenchendo formulário para aluno:', aluno);
    console.log('📝 Dados específicos do aluno:');
    console.log('  - ID:', aluno.id);
    console.log('  - Nome:', aluno.nome);
    console.log('  - CPF:', aluno.cpf);
    console.log('  - Email:', aluno.email);
    console.log('  - Telefone:', aluno.telefone);
    console.log('  - CFC ID:', aluno.cfc_id);
    console.log('  - Naturalidade:', aluno.naturalidade);
    console.log('  - Nacionalidade:', aluno.nacionalidade);
    
    
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
        'renach': aluno.renach || '',
        'data_nascimento': aluno.data_nascimento || '',
        'naturalidade': aluno.naturalidade || '',
        'naturalidade_estado': extrairEstadoNaturalidade(aluno.naturalidade),
        'naturalidade_municipio': extrairMunicipioNaturalidade(aluno.naturalidade),
        'nacionalidade': aluno.nacionalidade || '',
        'email': aluno.email || '',
        'telefone': aluno.telefone || '',
        'cfc_id': aluno.cfc_id || '',
        'status': aluno.status || 'ativo',
        'atividade_remunerada': aluno.atividade_remunerada || 0
    };
    
    // Carregar foto existente se houver
    if (aluno.foto) {
        carregarFotoExistenteAluno(aluno.foto);
    }
    
    console.log('📝 Campos a serem preenchidos:', campos);
    
    // Preencher cada campo se ele existir (exceto naturalidade que será tratada separadamente)
    console.log('🔍 Verificando elementos do formulário...');
    console.log('🔍 Modal visível?', document.getElementById('modalAluno')?.style.display);
    console.log('🔍 Formulário existe?', document.getElementById('formAluno') ? 'Sim' : 'Não');
    
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
    console.log('🚀 visualizandoAluno chamada com ID:', id);

    // GARANTIR que nenhum outro modal está aberto
    console.log('🔍 Verificando e fechando modais conflitantes...');
    const modalAlunoParaVisualizacao = document.getElementById('modalAluno');
    if (modalAlunoParaVisualizacao && modalAlunoParaVisualizacao.style.display !== 'none') {
        console.log('⚠️ Forçando fechamento do modalAluno conflitante...');
        modalAlunoParaVisualizacao.style.setProperty('display', 'none', 'important');
        modalAlunoParaVisualizacao.style.setProperty('visibility', 'hidden', 'important');
        modalAlunoParaVisualizacao.removeAttribute('data-opened');
    }

    const modalElement = document.getElementById('modalVisualizarAluno');
    const modalBody = document.getElementById('modalVisualizarAlunoBody');

    console.log('🔍 Verificando elementos do DOM:');
    console.log('  modalVisualizarAluno:', modalElement ? '✅ Existe' : '❌ Não existe');
    console.log('  modalVisualizarAlunoBody:', modalBody ? '✅ Existe' : '❌ Não existe');

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

    abrirModalVisualizarAluno();
    aplicarCorrecaoZIconsAction('open');

    const fecharModalVisualizacao = (event) => {
        if (event) {
            event.preventDefault();
            event.stopPropagation();
        }
        fecharModalVisualizarAluno();
    };

    const btnFecharTopo = modalElement.querySelector('.visualizar-close');
    const btnFecharRodape = modalElement.querySelector('.visualizar-footer .btn.btn-light');

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

                const btnFecharTopoAtualizado = modalElement.querySelector('.visualizar-close');
                const btnFecharRodapeAtualizado = modalElement.querySelector('.visualizar-footer .btn.btn-light');

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
                <div class="d-flex align-items-center">
                    ${aluno.foto && aluno.foto !== 'Array' ? `
                        <img src="${aluno.foto.startsWith('http') ? aluno.foto : window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/') + '/' + aluno.foto}" 
                             alt="Foto do aluno" class="rounded-circle me-3" 
                             style="width: 60px; height: 60px; object-fit: cover; border: 2px solid #dee2e6;">
                    ` : `
                        <div class="rounded-circle me-3 d-flex align-items-center justify-content-center bg-light" 
                             style="width: 60px; height: 60px; border: 2px solid #dee2e6;">
                            <i class="fas fa-user text-muted"></i>
                        </div>
                    `}
                    <div>
                        <h4 class="mb-0">${aluno.nome}</h4>
                        <p class="text-muted mb-0">CPF: ${aluno.cpf}</p>
                    </div>
                </div>
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
                <p><strong>Renach:</strong> ${aluno.renach || 'Não informado'}</p>
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
    
    // Configurar botão "Editar Aluno" para usar o modal customizado
    document.getElementById('btnEditarVisualizacao').onclick = () => {
        console.log('✏️ Botão Editar Aluno clicado, fechando modal de visualização...');
        fecharModalVisualizarAluno();
        setTimeout(() => {
            console.log('🪟 Abrindo modal de edição...');
            editarAluno(aluno.id);
        }, 200);
    };
}

function abrirModalVisualizarAluno() {
    const modalElement = document.getElementById('modalVisualizarAluno');
    if (!modalElement) {
        console.error('❌ modalVisualizarAluno não encontrado no DOM.');
        return;
    }

    const dialogElement = modalElement.querySelector('.visualizar-dialog');
    const contentElement = modalElement.querySelector('.visualizar-body');

    modalElement.classList.add('is-open');
    modalElement.setAttribute('data-opened', 'true');
    modalElement.setAttribute('aria-hidden', 'false');
    document.body.classList.add('visualizar-aluno-open');
    document.body.style.overflow = 'hidden';

    modalElement.style.setProperty('display', 'flex', 'important');
    modalElement.style.setProperty('position', 'fixed', 'important');
    modalElement.style.setProperty('inset', '0', 'important');
    modalElement.style.setProperty('padding', '2rem 1rem', 'important');
    modalElement.style.setProperty('background', 'rgba(15, 23, 42, 0.65)', 'important');
    modalElement.style.setProperty('z-index', '1055', 'important');
    modalElement.style.setProperty('align-items', 'flex-start', 'important');
    modalElement.style.setProperty('justify-content', 'center', 'important');
    modalElement.style.setProperty('overflow-y', 'auto', 'important');
    modalElement.style.setProperty('gap', '2rem', 'important');

    if (dialogElement) {
        dialogElement.style.setProperty('position', 'relative', 'important');
        dialogElement.style.setProperty('width', 'min(1080px, 95vw)', 'important');
        dialogElement.style.setProperty('max-width', '1080px', 'important');
        dialogElement.style.setProperty('background', '#ffffff', 'important');
        dialogElement.style.setProperty('border-radius', '18px', 'important');
        dialogElement.style.setProperty('box-shadow', '0 30px 80px rgba(15, 23, 42, 0.35)', 'important');
        dialogElement.style.setProperty('border', '1px solid rgba(148, 163, 184, 0.25)', 'important');
        dialogElement.style.setProperty('overflow', 'hidden', 'important');
        dialogElement.style.setProperty('display', 'flex', 'important');
        dialogElement.style.setProperty('flex-direction', 'column', 'important');
    }

    if (contentElement) {
        contentElement.style.setProperty('display', 'block', 'important');
        contentElement.style.setProperty('opacity', '1', 'important');
        contentElement.style.setProperty('visibility', 'visible', 'important');
    }

    document.querySelectorAll('#backdrop-visualizar-aluno, .modal-backdrop').forEach(el => el.remove());

    const overlayStyles = window.getComputedStyle(modalElement);
    const dialogStyles = dialogElement ? window.getComputedStyle(dialogElement) : null;
    console.log('📊 overlay computed:', JSON.stringify({
        display: overlayStyles?.display,
        position: overlayStyles?.position,
        width: overlayStyles?.width,
        height: overlayStyles?.height,
        pointerEvents: overlayStyles?.pointerEvents
    }));
    console.log('📊 dialog computed:', dialogStyles ? JSON.stringify({
        display: dialogStyles.display,
        width: dialogStyles.width,
        height: dialogStyles.height,
        pointerEvents: dialogStyles.pointerEvents
    }) : '"sem dialog"');

    console.log('🪟 Modal de visualização aberto com estilos forçados.');
}

function fecharModalVisualizarAluno() {
    const modalElement = document.getElementById('modalVisualizarAluno');
    if (!modalElement) {
        return;
    }

    const dialogElement = modalElement.querySelector('.visualizar-dialog');
    const contentElement = modalElement.querySelector('.visualizar-body');

    modalElement.classList.remove('is-open', 'modal-visualizar-fallback');
    modalElement.removeAttribute('data-opened');
    modalElement.setAttribute('aria-hidden', 'true');

    ['display', 'position', 'inset', 'padding', 'background', 'z-index', 'align-items', 'justify-content', 'overflow-y', 'gap'].forEach(prop => {
        modalElement.style.removeProperty(prop);
    });

    if (dialogElement) {
        ['position', 'width', 'max-width', 'background', 'border-radius', 'box-shadow', 'border', 'overflow', 'display', 'flex-direction'].forEach(prop => {
            dialogElement.style.removeProperty(prop);
        });
    }

    if (contentElement) {
        ['display', 'opacity', 'visibility'].forEach(prop => contentElement.style.removeProperty(prop));
    }

    document.querySelectorAll('#backdrop-visualizar-aluno, .modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('visualizar-aluno-open');
    if (!document.body.classList.contains('modal-open')) {
        document.body.style.removeProperty('overflow');
    }

    aplicarCorrecaoZIconsAction('close');
    console.log('✅ Modal de visualização fechado completamente');
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

function abrirFinanceiroAluno(id) {
    // Debug: verificar se a função está sendo chamada
    console.log('Função abrirFinanceiroAluno chamada com ID:', id);
    
    // Redirecionar para página de faturas com filtro por aluno usando roteamento
    window.location.href = `?page=financeiro-faturas&aluno_id=${id}`;
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

function abrirModalAluno() {
    console.log('🚀 Abrindo modal customizado...');
    
    console.log('🔒 Verificando conflitos com modal de visualização...');
    const modalVisualizar = document.getElementById('modalVisualizarAluno');
    if (modalVisualizar && modalVisualizar.classList.contains('is-open')) {
        console.log('⚠️ Fechando modal de visualização antes de abrir modal de edição...');
        fecharModalVisualizarAluno();
    }
    
    const modal = document.getElementById('modalAluno');
    if (modal) {
        // FORÇAR abertura do modal
        modal.style.setProperty('display', 'block', 'important');
        modal.style.setProperty('visibility', 'visible', 'important');
        modal.setAttribute('data-opened', 'true'); // Marcar como aberto intencionalmente
        document.body.style.overflow = 'hidden'; // Prevenir scroll do body
        
        // CORREÇÃO: Diminuir z-index dos ícones de ação quando modal de edição abrir
        aplicarCorrecaoZIconsAction('open');
        
        console.log('✅ Modal de edição aberto com sucesso');
        
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
            console.log('🧹 Seção de operações limpa');
            
            // Adicionar operação padrão automaticamente
            adicionarOperacao();
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
        // FORÇAR fechamento do modal
        modal.style.setProperty('display', 'none', 'important');
        modal.style.setProperty('visibility', 'hidden', 'important');
        modal.removeAttribute('data-opened'); // Remover marcação de aberto
        document.body.style.overflow = 'auto'; // Restaurar scroll do body
        
        // CORREÇÃO: Restaurar z-index dos ícones de ação quando modal de edição fechar
        aplicarCorrecaoZIconsAction('close');
        
        // Resetar campos de naturalidade para evitar problemas
        resetFormulario();
        
        console.log('✅ Modal customizado fechado!');
    }
}
// Função para resetar o formulário de alunos
function resetFormulario() {
    console.log('🔄 Resetando formulário de alunos...');
    
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
    
    console.log('✅ Formulário resetado completamente');
}


// FUNÇÃO PARA CORRIGIR Z-INDEX DOS ÍCONES DE AÇÃO
function aplicarCorrecaoZIconsAction(acao) {
    console.log(`🔧 Aplicando correção de z-index para ícones de ação: ${acao}`);
    const actionButtons = document.querySelectorAll('.action-icon-btn');
    const actionContainers = document.querySelectorAll('.action-buttons-compact');

    if (acao === 'open') {
        actionButtons.forEach(btn => {
            btn.style.setProperty('z-index', '1', 'important');
        });

        actionContainers.forEach(container => {
            container.style.setProperty('z-index', '1', 'important');
        });

        console.log('🔽 z-index dos ícones diminuído para ficar atrás do modal');
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
        console.log(`🛑 ModalAluno estava visível sem permissão (${origem}) - forçando fechamento.`);
        modalAluno.style.setProperty('display', 'none', 'important');
        modalAluno.style.setProperty('visibility', 'hidden', 'important');
        modalAluno.setAttribute('aria-hidden', 'true');
        modalAluno.removeAttribute('aria-modal');
        modalAluno.removeAttribute('data-opened');
    }

    if (modalVisualizarOverlay && modalVisualizarOverlay.classList.contains('is-open') && !modalVisualizarOverlay.hasAttribute('data-opened')) {
        console.log(`🛑 modalVisualizarAluno estava aberto indevidamente (${origem}) - fechando.`);
        modalVisualizarOverlay.classList.remove('is-open', 'modal-visualizar-fallback');
        modalVisualizarOverlay.setAttribute('aria-hidden', 'true');
        ['display', 'position', 'inset', 'width', 'height', 'overflow-y', 'background', 'visibility', 'opacity'].forEach(prop => {
            modalVisualizarOverlay.style.removeProperty(prop);
        });
        const dialogElement = modalVisualizarOverlay.querySelector('.visualizar-dialog');
        if (dialogElement) {
            ['position', 'margin', 'width', 'max-width', 'pointer-events', 'display', 'opacity', 'transform'].forEach(prop => {
                dialogElement.style.removeProperty(prop);
            });
        }
    }

    modaisBootstrap.forEach(modal => {
        console.log(`🛑 Fechando modal residual "${modal.id || modal.className}" (${origem}).`);
        modal.classList.remove('show');
        modal.style.removeProperty('display');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');
    });

    backdrops.forEach(backdrop => {
        backdrop.remove();
    });

    if (haviaResiduos) {
        console.log(`🧼 Resíduos de modal removidos (${origem}) -> modais:${modaisBootstrap.length} backdrops:${backdrops.length}`);
    }

    document.body.classList.remove('modal-open');
    document.body.classList.remove('visualizar-aluno-open');
    document.body.style.overflow = 'auto';
    document.body.style.removeProperty('paddingRight');
}

function limparTodosModais() {
    console.log('🧹 Limpando todos os modais conflitantes...');
    
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
        const dialogElement = modalVisualizar.querySelector('.visualizar-dialog');
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
        console.log('📦 modalVisualizarAluno realocado diretamente no body para garantir z-index correto.');
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
// Função para salvar aluno via AJAX
function salvarAluno() {
    const form = document.getElementById('formAluno');
    const formData = new FormData(form);
    
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
        console.log('📝 Enviando ID do aluno para edição:', alunoIdHidden.value);
    } else {
        console.log('📝 Criando novo aluno (sem ID)');
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
    console.log('📤 Aluno ID:', alunoId);
    
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
    const currentUser = <?php echo json_encode($user ?? []); ?>;
    const userType = currentUser.tipo || 'instrutor';
    
    console.log('👤 Ajustando abas para perfil:', userType);
    
    // Mostrar/ocultar abas conforme perfil
    if (userType === 'instrutor') {
        // Instrutor: apenas Teórico, Agenda/Aulas, Histórico
        document.getElementById('financeiro-tab-container').style.display = 'none';
        document.getElementById('documentos-tab-container').style.display = 'none';
    } else if (userType === 'secretaria') {
        // Secretaria: todas exceto gestão de Usuários (já controlado no menu)
        document.getElementById('financeiro-tab-container').style.display = 'block';
        document.getElementById('documentos-tab-container').style.display = 'block';
    } else if (userType === 'admin') {
        // Admin: todas as abas
        document.getElementById('financeiro-tab-container').style.display = 'block';
        document.getElementById('documentos-tab-container').style.display = 'block';
    }
}
// Função para carregar dados da aba Matrícula
function carregarMatriculas(alunoId) {
    if (!alunoId) return;
    
    fetch(`api/matriculas.php?aluno_id=${alunoId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('matriculas-container');
            if (data.success && data.matriculas.length > 0) {
                container.innerHTML = `
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
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-graduation-cap fa-2x mb-2"></i>
                        <p>Nenhuma matrícula encontrada</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar matrículas:', error);
            document.getElementById('matriculas-container').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro ao carregar matrículas
                </div>
            `;
        });
}
// Função para carregar documentos da aba Documentos
function carregarDocumentos(alunoId) {
    if (!alunoId) return;
    
    fetch(`api/aluno-documentos.php?aluno_id=${alunoId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('documentos-container');
            if (data.success && data.documentos.length > 0) {
                container.innerHTML = `
                    <div class="row">
                        ${data.documentos.map(doc => `
                            <div class="col-md-6 mb-3">
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-title">${doc.tipo_documento}</h6>
                                        <p class="card-text small">${doc.nome_arquivo}</p>
                                        <div class="d-flex justify-content-between">
                                            <span class="badge bg-${doc.status === 'aprovado' ? 'success' : doc.status === 'rejeitado' ? 'danger' : 'warning'}">${doc.status}</span>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary" onclick="visualizarDocumento(${doc.id})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="excluirDocumento(${doc.id})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `).join('')}
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-file-alt fa-2x mb-2"></i>
                        <p>Nenhum documento encontrado</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar documentos:', error);
            document.getElementById('documentos-container').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro ao carregar documentos
                </div>
            `;
        });
}

// Função para carregar dados de uma aba específica
function carregarDadosAba(abaId, alunoId) {
    console.log(`📊 Carregando dados da aba: ${abaId} para aluno: ${alunoId}`);
    
    switch(abaId) {
        case 'matricula':
            carregarMatriculas(alunoId);
            break;
        case 'documentos':
            carregarDocumentos(alunoId);
            break;
        case 'agenda':
            document.getElementById('aulas-container').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-calendar-alt fa-2x mb-2"></i>
                    <p>Carregando aulas agendadas...</p>
                </div>
            `;
            break;
        case 'teorico':
            document.getElementById('turma-container').innerHTML = `
                <div class="text-center text-muted py-4">
                    <i class="fas fa-chalkboard-teacher fa-2x mb-2"></i>
                    <p>Carregando informações da turma...</p>
                </div>
            `;
            break;
        case 'historico':
            carregarHistorico(alunoId);
            break;
    }
}

// Função para carregar histórico
function carregarHistorico(alunoId) {
    if (!alunoId) return;
    
    fetch(`api/historico.php?tipo=aluno&id=${alunoId}`)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('historico-container');
            if (data.success) {
                container.innerHTML = `
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker"></div>
                            <div class="timeline-content">
                                <h6>Cadastro do Aluno</h6>
                                <p class="text-muted small">Aluno cadastrado no sistema</p>
                            </div>
                        </div>
                    </div>
                `;
            } else {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-history fa-2x mb-2"></i>
                        <p>Nenhum histórico encontrado</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro ao carregar histórico:', error);
            document.getElementById('historico-container').innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Erro ao carregar histórico
                </div>
            `;
        });
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

// Event listener para mudança de abas
document.addEventListener('DOMContentLoaded', function() {
    // Ajustar abas por perfil
    ajustarAbasPorPerfil();
    
    // Event listener para mudança de abas
    const tabButtons = document.querySelectorAll('#alunoTabs button[data-bs-toggle="tab"]');
    tabButtons.forEach(button => {
        button.addEventListener('shown.bs.tab', function(event) {
            const targetTab = event.target.getAttribute('data-bs-target').replace('#', '');
            const alunoId = document.getElementById('aluno_id_hidden').value;
            
            if (alunoId) {
                carregarDadosAba(targetTab, alunoId);
            }
        });
    });
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
            modalDialog.style.top = '';
            modalDialog.style.left = '';
            modalDialog.style.right = '';
            modalDialog.style.bottom = '';
            modalDialog.style.width = '';
            modalDialog.style.maxHeight = '';
            modalContent.style.maxWidth = '';
            modalContent.style.maxHeight = '';
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
const originalAbrirModalAluno = window.abrirModalAluno;
if (originalAbrirModalAluno) {
    window.abrirModalAluno = function() {
        originalAbrirModalAluno();
        setTimeout(ajustarModalResponsivo, 100);
    };
}

console.log('🔧 Sistema de modais responsivos inicializado');

// =====================================================
// VALIDAÇÃO DE CPF EM TEMPO REAL
// =====================================================

// Função utilitária para aplicar validação em todos os CPF da página
function aplicarValidacaoCPFFormulario() {
    const formulariosComCPF = document.querySelectorAll('form input[name="cpf"], #cpf');
    
    formulariosComCPF.forEach(input => {
        // Primeiro, limpar qualquer feedback anterior
        const feedbackExistente = input.parentNode.querySelector('.cpf-validation-feedback');
        if (feedbackExistente && feedbackExistente.classList.contains('valid')) {
            feedbackExistente.remove();
        }
        
        if (input.value.length === 14) {
            const cpfLimpo = input.value.replace(/\D/g, '');
            const isValid = validarCPF(cpfLimpo);
            
            if (isValid) {
                // Re-aplicar validação correta com timer de ocultação
                mostrarFeedbackCPF(input, true);
            }
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
    const feedback = input.parentNode.querySelector('.cpf-validation-feedback');
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
    const feedback = input.parentNode.querySelector('.cpf-validation-feedback');
    if (feedback) {
        input.classList.remove('valid', 'invalid');
        feedback.style.display = 'none';
        feedback.className = 'cpf-validation-feedback';
        feedback.textContent = ''; // Limpar conteúdo
    }
}

// Aplicar validação de CPF quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    const cpfInput = document.getElementById('cpf');
    
    if (cpfInput) {
        // Aplicar máscara enquanto digita
        cpfInput.addEventListener('input', function() {
            aplicarMascaraCPF(this);
            
            // Validar CPF quando tiver 14 caracteres (com máscara)
            if (this.value.length === 14) {
                const cpfLimpo = this.value.replace(/\D/g, '');
                const isValid = validarCPF(cpfLimpo);
                mostrarFeedbackCPF(this, isValid);
            } else if (this.value.length < 14) {
                ocultarFeedbackCPF(this);
            }
        });
        
        // Validar ao sair do campo
        cpfInput.addEventListener('blur', function() {
            if (this.value.length === 14) {
                const cpfLimpo = this.value.replace(/\D/g, '');
                const isValid = validarCPF(cpfLimpo);
                mostrarFeedbackCPF(this, isValid);
            }
        });
        
        // Limpar validação ao focar
        cpfInput.addEventListener('focus', function() {
            if (this.value.length < 14) {
                ocultarFeedbackCPF(this);
            }
        });
    }
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
    
    if (caminhoFoto && caminhoFoto.trim() !== '') {
        const preview = document.getElementById('foto-preview-aluno');
        const container = document.getElementById('preview-container-aluno');
        const placeholder = document.getElementById('placeholder-foto-aluno');
        
        // Construir URL completa da foto
        let urlFoto;
        if (caminhoFoto.startsWith('http')) {
            urlFoto = caminhoFoto;
        } else {
            // Construir URL baseada no contexto atual
            const baseUrl = window.location.origin;
            const basePath = window.location.pathname.split('/').slice(0, -2).join('/');
            urlFoto = `${baseUrl}${basePath}/${caminhoFoto}`;
        }
        
        console.log('📷 URL da foto do aluno construída:', urlFoto);
        
        preview.src = urlFoto;
        container.style.display = 'block';
        placeholder.style.display = 'none';
        
        // Verificar se a imagem carregou
        preview.onload = function() {
            console.log('✅ Foto existente do aluno carregada com sucesso');
        };
        
        preview.onerror = function() {
            console.error('❌ Erro ao carregar foto do aluno:', urlFoto);
            // Se der erro, mostrar placeholder
            container.style.display = 'none';
            placeholder.style.display = 'block';
        };
    } else {
        // Se não há foto, mostrar placeholder
        const container = document.getElementById('preview-container-aluno');
        const placeholder = document.getElementById('placeholder-foto-aluno');
        
        container.style.display = 'none';
        placeholder.style.display = 'block';
        
        console.log('ℹ️ Nenhuma foto existente do aluno encontrada');
    }
}

console.log('✅ Funções de foto do aluno inicializadas');
</script>
</script>