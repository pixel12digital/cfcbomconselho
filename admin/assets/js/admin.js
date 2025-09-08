/**
 * JavaScript para o Painel Administrativo - Sistema CFC
 * Baseado no design do e-condutor para mesma experiência
 */

// Classe principal do painel administrativo
class AdminPanel {
    constructor() {
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeComponents();
        this.setupResponsiveBehavior();
    }

    // Configurar event listeners
    setupEventListeners() {
        // Toggle sidebar em dispositivos móveis
        const sidebarToggle = document.querySelector('.sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => this.toggleSidebar());
        }

        // Fechar sidebar ao clicar fora
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 1024) {
                const sidebar = document.querySelector('.admin-sidebar');
                const sidebarToggle = document.querySelector('.sidebar-toggle');
                
                if (sidebar && !sidebar.contains(e.target) && !e.target.closest('.sidebar-toggle')) {
                    sidebar.classList.remove('open');
                }
            }
        });

        // Navegação responsiva
        window.addEventListener('resize', () => this.handleResize());
    }

    // Inicializar componentes
    initializeComponents() {
        this.initializeAnimations();
        this.initializeTooltips();
        this.initializeLoadingStates();
        this.initializeNotifications();
    }

    // Configurar comportamento responsivo
    setupResponsiveBehavior() {
        if (window.innerWidth <= 1024) {
            document.body.classList.add('mobile-view');
        }
        this.setupSubmenuToggle();
    }

    // Configurar toggle dos submenus
    setupSubmenuToggle() {
        const navToggles = document.querySelectorAll('.nav-toggle');
        
        navToggles.forEach(toggle => {
            toggle.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const group = toggle.getAttribute('data-group');
                const submenu = document.getElementById(group);
                const arrow = toggle.querySelector('.nav-arrow i');
                
                if (submenu) {
                    // Fechar outros submenus abertos
                    const allSubmenus = document.querySelectorAll('.nav-submenu');
                    const allArrows = document.querySelectorAll('.nav-arrow i');
                    
                    allSubmenus.forEach(menu => {
                        if (menu !== submenu) {
                            menu.classList.remove('open');
                        }
                    });
                    
                    allArrows.forEach(arr => {
                        if (arr !== arrow) {
                            arr.style.transform = 'rotate(0deg)';
                        }
                    });
                    
                    // Toggle do submenu atual
                    submenu.classList.toggle('open');
                    
                    // Rotacionar seta
                    if (submenu.classList.contains('open')) {
                        arrow.style.transform = 'rotate(180deg)';
                    } else {
                        arrow.style.transform = 'rotate(0deg)';
                    }
                }
            });
        });
    }

    // Toggle sidebar
    toggleSidebar() {
        const sidebar = document.querySelector('.admin-sidebar');
        if (sidebar) {
            sidebar.classList.toggle('open');
        }
    }

    // Lidar com redimensionamento da janela
    handleResize() {
        if (window.innerWidth <= 1024) {
            document.body.classList.add('mobile-view');
        } else {
            document.body.classList.remove('mobile-view');
            const sidebar = document.querySelector('.admin-sidebar');
            if (sidebar) {
                sidebar.classList.remove('open');
            }
        }
    }

    // Inicializar animações
    initializeAnimations() {
        const animateElements = document.querySelectorAll('.stat-card, .card, .chart-section');
        animateElements.forEach((element, index) => {
            element.style.animationDelay = `${index * 0.1}s`;
            element.classList.add('animate-fade-in');
        });
    }

    // Inicializar tooltips
    initializeTooltips() {
        const tooltipElements = document.querySelectorAll('[data-tooltip]');
        tooltipElements.forEach(element => {
            element.classList.add('tooltip');
        });
    }

    // Inicializar estados de carregamento
    initializeLoadingStates() {
        const loadingElements = document.querySelectorAll('.loading');
        loadingElements.forEach(element => {
            element.classList.add('loading-state');
        });
    }

    // Inicializar sistema de notificações
    initializeNotifications() {
        // Criar container de notificações se não existir
        if (!document.querySelector('.notifications-container')) {
            const container = document.createElement('div');
            container.className = 'notifications-container';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
                max-width: 400px;
            `;
            document.body.appendChild(container);
        }
    }
}

// Sistema de notificações - REMOVIDO (definido em components.js)
// class NotificationSystem removida para evitar duplicação

// Sistema de confirmação
class ConfirmationSystem {
    static confirm(message, callback) {
        const modal = this.createModal(message);
        document.body.appendChild(modal);
        
        const confirmBtn = modal.querySelector('.confirm-btn');
        const cancelBtn = modal.querySelector('.cancel-btn');
        
        confirmBtn.addEventListener('click', () => {
            document.body.removeChild(modal);
            if (callback) callback(true);
        });
        
        cancelBtn.addEventListener('click', () => {
            document.body.removeChild(modal);
            if (callback) callback(false);
        });
        
        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                document.body.removeChild(modal);
                if (callback) callback(false);
            }
        });
    }

    static createModal(message) {
        const modal = document.createElement('div');
        modal.className = 'modal-overlay';
        modal.innerHTML = `
            <div class="modal">
                <div class="modal-header">
                    <h3 class="modal-title">Confirmação</h3>
                </div>
                <div class="modal-body">
                    <p>${message}</p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary cancel-btn">Cancelar</button>
                    <button class="btn btn-primary confirm-btn">Confirmar</button>
                </div>
            </div>
        `;
        return modal;
    }
}

// Sistema de loading - REMOVIDO (definido em components.js)
// class LoadingSystem removida para evitar duplicação

// Utilitários
class Utils {
    static formatNumber(number) {
        return new Intl.NumberFormat('pt-BR').format(number);
    }

    static formatDate(date) {
        return new Intl.DateTimeFormat('pt-BR').format(new Date(date));
    }

    static formatCurrency(amount) {
        return new Intl.NumberFormat('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        }).format(amount);
    }

    static formatPhone(phone) {
        // Formatar telefone brasileiro
        const cleaned = phone.replace(/\D/g, '');
        const match = cleaned.match(/^(\d{2})(\d{4,5})(\d{4})$/);
        if (match) {
            return `(${match[1]}) ${match[2]}-${match[3]}`;
        }
        return phone;
    }

    static formatCPF(cpf) {
        const cleaned = cpf.replace(/\D/g, '');
        const match = cleaned.match(/^(\d{3})(\d{3})(\d{3})(\d{2})$/);
        if (match) {
            return `${match[1]}.${match[2]}.${match[3]}-${match[4]}`;
        }
        return cpf;
    }

    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }
}

// Sistema de validação
class ValidationSystem {
    static validateEmail(email) {
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return re.test(email);
    }

    static validateCPF(cpf) {
        const cleaned = cpf.replace(/\D/g, '');
        if (cleaned.length !== 11) return false;
        
        // Verificar dígitos repetidos
        if (/^(\d)\1{10}$/.test(cleaned)) return false;
        
        // Validar primeiro dígito verificador
        let sum = 0;
        for (let i = 0; i < 9; i++) {
            sum += parseInt(cleaned.charAt(i)) * (10 - i);
        }
        let remainder = 11 - (sum % 11);
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cleaned.charAt(9))) return false;
        
        // Validar segundo dígito verificador
        sum = 0;
        for (let i = 0; i < 10; i++) {
            sum += parseInt(cleaned.charAt(i)) * (11 - i);
        }
        remainder = 11 - (sum % 11);
        if (remainder === 10 || remainder === 11) remainder = 0;
        if (remainder !== parseInt(cleaned.charAt(10))) return false;
        
        return true;
    }

    static validatePhone(phone) {
        const cleaned = phone.replace(/\D/g, '');
        return cleaned.length >= 10 && cleaned.length <= 11;
    }

    static validateRequired(value) {
        return value !== null && value !== undefined && value.toString().trim() !== '';
    }

    static validateMinLength(value, min) {
        return value && value.toString().length >= min;
    }

    static validateMaxLength(value, max) {
        return value && value.toString().length <= max;
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar painel administrativo
    window.adminPanel = new AdminPanel();
    
    // Disponibilizar sistemas globalmente
    // window.notifications = NotificationSystem; // REMOVIDO - definido em components.js
    // window.confirm = ConfirmationSystem.confirm;
    // window.loading = LoadingSystem; // REMOVIDO - definido em components.js
    window.utils = Utils;
    window.validation = ValidationSystem;
    
    // Log de inicialização
    console.log('Painel Administrativo inicializado com sucesso!');
    
    // Mostrar notificação de boas-vindas
    setTimeout(() => {
        // NotificationSystem.success('Painel administrativo carregado com sucesso!', 3000); // REMOVIDO - definido em components.js
    }, 1000);
});

// Exportar para uso em módulos (se necessário)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        AdminPanel,
        // NotificationSystem, // REMOVIDO - definido em components.js
        ConfirmationSystem,
        // LoadingSystem, // REMOVIDO - definido em components.js
        Utils,
        ValidationSystem
    };
}
