/**
 * Dashboard Administrativo - JavaScript
 * Sistema CFC Bom Conselho
 */

// ===== CONFIGURAÇÕES GLOBAIS =====
const ADMIN_CONFIG = {
    apiUrl: '../api/',
    refreshInterval: 30000, // 30 segundos
    maxNotifications: 10,
    chartColors: {
        primary: '#4e73df',
        success: '#1cc88a',
        info: '#36b9cc',
        warning: '#f6c23e',
        danger: '#e74a3b'
    }
};

// ===== INICIALIZAÇÃO =====
document.addEventListener('DOMContentLoaded', function() {
    initializeAdmin();
});

function initializeAdmin() {
    console.log('Inicializando Dashboard Administrativo...');
    
    // Inicializar componentes
    initializeSidebar();
    initializeNotifications();
    initializeDataTables();
    initializeModals();
    initializeTooltips();
    
    // Configurar atualizações automáticas
    setupAutoRefresh();
    
    // Configurar eventos globais
    setupGlobalEvents();
    
    console.log('Dashboard Administrativo inicializado com sucesso!');
}

// ===== SIDEBAR =====
function initializeSidebar() {
    const sidebarToggle = document.querySelector('.sidebar-toggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            document.body.classList.toggle('sidebar-collapsed');
        });
    }
    
    // Marcar item ativo no sidebar
    markActiveSidebarItem();
}

function markActiveSidebarItem() {
    const currentPage = getCurrentPage();
    const sidebarLinks = document.querySelectorAll('.sidebar .nav-link');
    
    sidebarLinks.forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') && link.getAttribute('href').includes(currentPage)) {
            link.classList.add('active');
        }
    });
}

function getCurrentPage() {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get('page') || 'dashboard';
}

// ===== NOTIFICAÇÕES =====
function initializeNotifications() {
    // Carregar notificações iniciais
    loadNotifications();
    
    // Configurar intervalo de atualização
    setInterval(loadNotifications, ADMIN_CONFIG.refreshInterval);
}

function loadNotifications() {
    const container = document.getElementById('notificacoes-container');
    if (!container) return;
    
    // Simular carregamento de notificações da API
    fetch(`${ADMIN_CONFIG.apiUrl}notificacoes.php`)
        .then(response => response.json())
        .then(data => {
            displayNotifications(data.notificacoes || []);
        })
        .catch(error => {
            console.error('Erro ao carregar notificações:', error);
            displayNotifications(getDefaultNotifications());
        });
}

function displayNotifications(notificacoes) {
    const container = document.getElementById('notificacoes-container');
    if (!container) return;
    
    if (notificacoes.length === 0) {
        container.innerHTML = `
            <div class="text-center text-muted py-3">
                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                <p>Nenhuma notificação no momento.</p>
            </div>
        `;
        return;
    }
    
    const html = notificacoes.slice(0, ADMIN_CONFIG.maxNotifications).map(notif => {
        const alertClass = getAlertClass(notif.tipo);
        const icon = getNotificationIcon(notif.tipo);
        
        return `
            <div class="alert alert-${alertClass} alert-dismissible fade show" role="alert">
                <i class="${icon} me-2"></i>
                <strong>${notif.titulo}</strong> ${notif.mensagem}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }).join('');
    
    container.innerHTML = html;
}

function getAlertClass(tipo) {
    const classes = {
        'info': 'info',
        'success': 'success',
        'warning': 'warning',
        'error': 'danger',
        'danger': 'danger'
    };
    return classes[tipo] || 'info';
}

function getNotificationIcon(tipo) {
    const icons = {
        'info': 'fas fa-info-circle',
        'success': 'fas fa-check-circle',
        'warning': 'fas fa-exclamation-triangle',
        'error': 'fas fa-times-circle',
        'danger': 'fas fa-exclamation-triangle'
    };
    return icons[tipo] || 'fas fa-bell';
}

function getDefaultNotifications() {
    return [
        {
            tipo: 'info',
            titulo: 'Bem-vindo!',
            mensagem: 'O dashboard foi configurado com sucesso.'
        },
        {
            tipo: 'warning',
            titulo: 'Atenção:',
            mensagem: 'Verifique os cadastros pendentes de aprovação.'
        },
        {
            tipo: 'success',
            titulo: 'Sucesso:',
            mensagem: 'Sistema funcionando perfeitamente.'
        }
    ];
}

// ===== TABELAS DE DADOS =====
function initializeDataTables() {
    // Inicializar DataTables se estiver disponível
    if (typeof $.fn.DataTable !== 'undefined') {
        $('.datatable').DataTable({
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.4/i18n/pt-BR.json'
            },
            responsive: true,
            pageLength: 25,
            order: [[0, 'desc']]
        });
    }
}

// ===== MODAIS =====
function initializeModals() {
    // Configurar modais de confirmação
    setupConfirmationModals();
    
    // Configurar modais de formulário
    setupFormModals();
}

function setupConfirmationModals() {
    const confirmButtons = document.querySelectorAll('[data-confirm]');
    
    confirmButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            const message = this.getAttribute('data-confirm');
            if (!confirm(message)) {
                e.preventDefault();
                return false;
            }
        });
    });
}

function setupFormModals() {
    const formModals = document.querySelectorAll('.modal-form');
    
    formModals.forEach(modal => {
        const form = modal.querySelector('form');
        if (form) {
            form.addEventListener('submit', handleFormSubmit);
        }
    });
}

function handleFormSubmit(e) {
    const form = e.target;
    const submitBtn = form.querySelector('[type="submit"]');
    
    if (submitBtn) {
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Processando...';
    }
    
    // Aqui você pode adicionar validação adicional se necessário
    return true;
}

// ===== TOOLTIPS =====
function initializeTooltips() {
    // Inicializar tooltips do Bootstrap
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// ===== ATUALIZAÇÃO AUTOMÁTICA =====
function setupAutoRefresh() {
    // Atualizar estatísticas periodicamente
    setInterval(updateStatistics, ADMIN_CONFIG.refreshInterval);
    
    // Atualizar gráficos periodicamente
    setInterval(updateCharts, ADMIN_CONFIG.refreshInterval * 2);
}

function updateStatistics() {
    // Atualizar estatísticas do dashboard
    fetch(`${ADMIN_CONFIG.apiUrl}estatisticas.php`)
        .then(response => response.json())
        .then(data => {
            updateStatisticsDisplay(data);
        })
        .catch(error => {
            console.error('Erro ao atualizar estatísticas:', error);
        });
}

function updateStatisticsDisplay(data) {
    // Atualizar valores dos cards de estatísticas
    Object.keys(data).forEach(key => {
        const element = document.querySelector(`[data-stat="${key}"]`);
        if (element) {
            element.textContent = formatNumber(data[key]);
        }
    });
}

function updateCharts() {
    // Atualizar dados dos gráficos
    console.log('Atualizando gráficos...');
    // Implementar atualização dos gráficos conforme necessário
}

// ===== EVENTOS GLOBAIS =====
function setupGlobalEvents() {
    // Configurar logout automático por inatividade
    setupInactivityLogout();
    
    // Configurar tratamento de erros
    setupErrorHandling();
    
    // Configurar shortcuts de teclado
    setupKeyboardShortcuts();
}

function setupInactivityLogout() {
    let inactivityTimer;
    const timeout = 30 * 60 * 1000; // 30 minutos
    
    function resetTimer() {
        clearTimeout(inactivityTimer);
        inactivityTimer = setTimeout(logout, timeout);
    }
    
    // Eventos que resetam o timer
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(event => {
        document.addEventListener(event, resetTimer, true);
    });
    
    resetTimer();
}

function logout() {
    if (confirm('Sua sessão expirou por inatividade. Deseja fazer login novamente?')) {
        window.location.href = '../logout.php';
    }
}

function setupErrorHandling() {
    window.addEventListener('error', function(e) {
        console.error('Erro JavaScript:', e.error);
        showErrorNotification('Erro no sistema', 'Ocorreu um erro inesperado. Tente novamente.');
    });
    
    window.addEventListener('unhandledrejection', function(e) {
        console.error('Promise rejeitada:', e.reason);
        showErrorNotification('Erro na operação', 'Falha na operação solicitada.');
    });
}

function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K - Busca
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            focusSearch();
        }
        
        // Ctrl/Cmd + N - Nova entrada
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            openNewEntry();
        }
        
        // Esc - Fechar modais
        if (e.key === 'Escape') {
            closeAllModals();
        }
    });
}

// ===== FUNÇÕES UTILITÁRIAS =====
function formatNumber(num) {
    if (num === null || num === undefined) return '0';
    return new Intl.NumberFormat('pt-BR').format(num);
}

function formatCurrency(value) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL'
    }).format(value);
}

function formatDate(date) {
    return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
}

function formatDateTime(dateTime) {
    return new Intl.DateTimeFormat('pt-BR', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    }).format(new Date(dateTime));
}

function showSuccessNotification(title, message) {
    showNotification('success', title, message);
}

function showErrorNotification(title, message) {
    showNotification('danger', title, message);
}

function showWarningNotification(title, message) {
    showNotification('warning', title, message);
}

function showInfoNotification(title, message) {
    showNotification('info', title, message);
}

function showNotification(type, title, message) {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            <strong>${title}</strong> ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;
    
    // Inserir no topo da página
    const container = document.querySelector('.container-fluid');
    if (container) {
        container.insertAdjacentHTML('afterbegin', alertHtml);
        
        // Auto-remover após 5 segundos
        setTimeout(() => {
            const alert = container.querySelector('.alert');
            if (alert) {
                alert.remove();
            }
        }, 5000);
    }
}

// ===== FUNÇÕES DE NAVEGAÇÃO =====
function focusSearch() {
    const searchInput = document.querySelector('#search-input, .search-input');
    if (searchInput) {
        searchInput.focus();
    }
}

function openNewEntry() {
    const newEntryBtn = document.querySelector('.btn-new-entry, [href*="action=create"]');
    if (newEntryBtn) {
        newEntryBtn.click();
    }
}

function closeAllModals() {
    const modals = document.querySelectorAll('.modal.show');
    modals.forEach(modal => {
        const modalInstance = bootstrap.Modal.getInstance(modal);
        if (modalInstance) {
            modalInstance.hide();
        }
    });
}

// ===== FUNÇÕES DE EXPORTAÇÃO =====
function exportToCSV(tableId, filename = 'export.csv') {
    const table = document.getElementById(tableId);
    if (!table) return;
    
    let csv = [];
    const rows = table.querySelectorAll('tr');
    
    for (let i = 0; i < rows.length; i++) {
        let row = [], cols = rows[i].querySelectorAll('td, th');
        
        for (let j = 0; j < cols.length; j++) {
            let text = cols[j].innerText.replace(/"/g, '""');
            row.push('"' + text + '"');
        }
        
        csv.push(row.join(','));
    }
    
    downloadCSV(csv.join('\n'), filename);
}

function downloadCSV(csv, filename) {
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const link = document.createElement('a');
    
    if (link.download !== undefined) {
        const url = URL.createObjectURL(blob);
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
}

// ===== FUNÇÕES DE VALIDAÇÃO =====
function validateForm(form) {
    const inputs = form.querySelectorAll('input[required], select[required], textarea[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            markFieldAsInvalid(input, 'Este campo é obrigatório');
            isValid = false;
        } else {
            markFieldAsValid(input);
        }
    });
    
    return isValid;
}

function markFieldAsInvalid(field, message) {
    field.classList.add('is-invalid');
    field.classList.remove('is-valid');
    
    // Remover mensagem de erro existente
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
    
    // Adicionar nova mensagem de erro
    const errorDiv = document.createElement('div');
    errorDiv.className = 'invalid-feedback';
    errorDiv.textContent = message;
    field.parentNode.appendChild(errorDiv);
}

function markFieldAsValid(field) {
    field.classList.remove('is-invalid');
    field.classList.add('is-valid');
    
    // Remover mensagem de erro
    const existingError = field.parentNode.querySelector('.invalid-feedback');
    if (existingError) {
        existingError.remove();
    }
}

// ===== FUNÇÕES DE AJAX =====
function ajaxRequest(url, options = {}) {
    const defaultOptions = {
        method: 'GET',
        headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    };
    
    const finalOptions = { ...defaultOptions, ...options };
    
    return fetch(url, finalOptions)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .catch(error => {
            console.error('Erro na requisição AJAX:', error);
            throw error;
        });
}

// ===== FUNÇÕES DE DEBUG =====
function debugLog(message, data = null) {
    if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
        console.log(`[DEBUG] ${message}`, data);
    }
}

// ===== EXPORTAÇÃO DE FUNÇÕES GLOBAIS =====
window.AdminDashboard = {
    showNotification,
    showSuccessNotification,
    showErrorNotification,
    showWarningNotification,
    showInfoNotification,
    exportToCSV,
    validateForm,
    ajaxRequest,
    debugLog
};
