/**
 * Mobile Menu Clean JavaScript - Sistema CFC Bom Conselho
 * Estado base estável: Topbar + Sidebar + MobileDrawer
 */

class MobileMenuClean {
    constructor() {
        this.drawer = null;
        this.toggle = null;
        this.closeButton = null;
        this.overlay = null;
        this.isOpen = false;
        this.focusableElements = [];
        this.previousActiveElement = null;
        
        this.init();
    }
    
    init() {
        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setup());
        } else {
            this.setup();
        }
    }
    
    setup() {
        console.log('Inicializando Mobile Menu Clean...');
        
        // Elementos principais
        this.drawer = document.getElementById('mobile-drawer');
        this.toggle = document.getElementById('mobile-menu-toggle');
        this.closeButton = document.getElementById('mobile-drawer-close');
        this.overlay = document.getElementById('mobile-drawer-overlay');
        
        // Verificar se elementos existem
        if (!this.drawer || !this.toggle) {
            console.warn('Elementos do mobile menu não encontrados');
            return;
        }
        
        // Configurar event listeners
        this.setupEventListeners();
        
        // Configurar grupos expansíveis
        this.setupExpandableGroups();
        
        // Configurar responsividade
        this.setupResponsive();
        
        // Expandir grupo com item ativo
        this.expandActiveGroup();
        
        console.log('Mobile Menu Clean configurado com sucesso');
    }
    
    setupEventListeners() {
        // Toggle do menu
        this.toggle.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.toggleMenu();
        });
        
        // Botão de fechar
        if (this.closeButton) {
            this.closeButton.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMenu();
            });
        }
        
        // Overlay (clique fora)
        if (this.overlay) {
            this.overlay.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.closeMenu();
            });
        }
        
        // Tecla ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                e.preventDefault();
                this.closeMenu();
            }
        });
        
        // Links de navegação - fechar menu ao clicar
        const navLinks = this.drawer.querySelectorAll('.mobile-nav-link, .mobile-nav-sublink');
        navLinks.forEach(link => {
            link.addEventListener('click', (e) => {
                // Se for um link externo ou logout, fechar imediatamente
                if (link.href.includes('logout.php') || link.target === '_blank') {
                    this.closeMenu();
                    return;
                }
                
                // Para links internos, fechar após pequeno delay
                setTimeout(() => this.closeMenu(), 150);
            });
        });
        
        // Prevenir fechamento ao clicar dentro do drawer
        this.drawer.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    setupExpandableGroups() {
        const groupHeaders = this.drawer.querySelectorAll('.mobile-nav-group-header');
        
        groupHeaders.forEach(header => {
            header.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                const group = header.closest('.mobile-nav-group');
                const submenu = group.querySelector('.mobile-nav-submenu');
                const arrow = header.querySelector('.mobile-nav-arrow');
                
                if (group.classList.contains('open')) {
                    // Fechar grupo
                    group.classList.remove('open');
                    submenu.style.maxHeight = '0';
                    if (arrow) arrow.style.transform = 'rotate(0deg)';
                } else {
                    // Fechar outros grupos abertos
                    const openGroups = this.drawer.querySelectorAll('.mobile-nav-group.open');
                    openGroups.forEach(openGroup => {
                        if (openGroup !== group) {
                            openGroup.classList.remove('open');
                            const openSubmenu = openGroup.querySelector('.mobile-nav-submenu');
                            const openArrow = openGroup.querySelector('.mobile-nav-arrow');
                            if (openSubmenu) openSubmenu.style.maxHeight = '0';
                            if (openArrow) openArrow.style.transform = 'rotate(0deg)';
                        }
                    });
                    
                    // Abrir grupo atual
                    group.classList.add('open');
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                    if (arrow) arrow.style.transform = 'rotate(180deg)';
                }
            });
        });
    }
    
    setupResponsive() {
        // Verificar tamanho da tela
        const checkScreenSize = () => {
            const isMobile = window.innerWidth <= 768;
            
            if (isMobile) {
                // Em mobile, garantir que sidebar esteja oculta
                const sidebar = document.querySelector('.admin-sidebar');
                if (sidebar) {
                    sidebar.style.transform = 'translateX(-100%)';
                }
            } else {
                // Em desktop, fechar drawer se estiver aberto
                if (this.isOpen) {
                    this.closeMenu();
                }
                
                // Restaurar sidebar
                const sidebar = document.querySelector('.admin-sidebar');
                if (sidebar) {
                    sidebar.style.transform = '';
                }
            }
        };
        
        // Verificar no carregamento
        checkScreenSize();
        
        // Verificar no redimensionamento
        window.addEventListener('resize', debounce(checkScreenSize, 250));
    }
    
    toggleMenu() {
        if (this.isOpen) {
            this.closeMenu();
        } else {
            this.openMenu();
        }
    }
    
    openMenu() {
        if (this.isOpen) return;
        
        console.log('Abrindo menu mobile...');
        
        // Salvar elemento ativo anterior
        this.previousActiveElement = document.activeElement;
        
        // Abrir drawer
        this.drawer.classList.add('open');
        this.drawer.setAttribute('aria-hidden', 'false');
        
        // Atualizar toggle
        this.toggle.setAttribute('aria-expanded', 'true');
        this.toggle.setAttribute('aria-label', 'Fechar menu de navegação');
        
        // Bloquear scroll do body
        document.body.classList.add('drawer-open');
        
        // Atualizar estado
        this.isOpen = true;
        
        // Configurar focus trap
        this.setupFocusTrap();
        
        // Focar no primeiro elemento
        setTimeout(() => {
            const firstFocusable = this.getFocusableElements()[0];
            if (firstFocusable) {
                firstFocusable.focus();
            }
        }, 100);
        
        // Ocultar elementos flutuantes
        this.hideFloatingElements();
    }
    
    closeMenu() {
        if (!this.isOpen) return;
        
        console.log('Fechando menu mobile...');
        
        // Fechar drawer
        this.drawer.classList.remove('open');
        this.drawer.setAttribute('aria-hidden', 'true');
        
        // Atualizar toggle
        this.toggle.setAttribute('aria-expanded', 'false');
        this.toggle.setAttribute('aria-label', 'Abrir menu de navegação');
        
        // Restaurar scroll do body
        document.body.classList.remove('drawer-open');
        
        // Atualizar estado
        this.isOpen = false;
        
        // Remover focus trap
        this.removeFocusTrap();
        
        // Retornar foco ao elemento anterior
        if (this.previousActiveElement) {
            this.previousActiveElement.focus();
        }
        
        // Restaurar elementos flutuantes
        this.showFloatingElements();
    }
    
    setupFocusTrap() {
        this.focusableElements = this.getFocusableElements();
        
        if (this.focusableElements.length === 0) return;
        
        // Configurar navegação por teclado
        this.drawer.addEventListener('keydown', this.handleFocusTrap.bind(this));
    }
    
    removeFocusTrap() {
        this.drawer.removeEventListener('keydown', this.handleFocusTrap.bind(this));
        this.focusableElements = [];
    }
    
    handleFocusTrap(e) {
        if (e.key !== 'Tab') return;
        
        const firstElement = this.focusableElements[0];
        const lastElement = this.focusableElements[this.focusableElements.length - 1];
        
        if (e.shiftKey) {
            // Shift + Tab
            if (document.activeElement === firstElement) {
                e.preventDefault();
                lastElement.focus();
            }
        } else {
            // Tab
            if (document.activeElement === lastElement) {
                e.preventDefault();
                firstElement.focus();
            }
        }
    }
    
    getFocusableElements() {
        const focusableSelectors = [
            'button:not([disabled])',
            'a[href]',
            'input:not([disabled])',
            'select:not([disabled])',
            'textarea:not([disabled])',
            '[tabindex]:not([tabindex="-1"])'
        ];
        
        return Array.from(this.drawer.querySelectorAll(focusableSelectors.join(', ')))
            .filter(element => {
                // Verificar se elemento está visível
                const style = window.getComputedStyle(element);
                return style.display !== 'none' && 
                       style.visibility !== 'hidden' && 
                       style.opacity !== '0';
            });
    }
    
    hideFloatingElements() {
        // Ocultar elementos flutuantes que podem interferir
        const floatingElements = document.querySelectorAll(
            '.floating-whatsapp, .floating-cta, .floating-button, .whatsapp-float'
        );
        
        floatingElements.forEach(element => {
            element.style.display = 'none';
        });
    }
    
    showFloatingElements() {
        // Restaurar elementos flutuantes
        const floatingElements = document.querySelectorAll(
            '.floating-whatsapp, .floating-cta, .floating-button, .whatsapp-float'
        );
        
        floatingElements.forEach(element => {
            element.style.display = '';
        });
    }
    
    // Expandir grupo que contém o item ativo
    expandActiveGroup() {
        const activeLink = this.drawer.querySelector('.mobile-nav-link.active, .mobile-nav-sublink.active');
        if (activeLink) {
            const group = activeLink.closest('.mobile-nav-group');
            if (group) {
                const submenu = group.querySelector('.mobile-nav-submenu');
                const arrow = group.querySelector('.mobile-nav-arrow');
                
                group.classList.add('open');
                if (submenu) {
                    submenu.style.maxHeight = submenu.scrollHeight + 'px';
                }
                if (arrow) {
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
        }
    }
    
    // Método público para fechar menu (útil para outros scripts)
    close() {
        this.closeMenu();
    }
    
    // Método público para verificar se está aberto
    isMenuOpen() {
        return this.isOpen;
    }
}

// Função utilitária para debounce
function debounce(func, wait) {
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

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se estamos em uma página que tem o mobile menu
    if (document.getElementById('mobile-drawer')) {
        window.mobileMenu = new MobileMenuClean();
    }
});

// Exportar para uso global se necessário
if (typeof window !== 'undefined') {
    window.MobileMenuClean = MobileMenuClean;
}
