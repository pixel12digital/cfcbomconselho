/**
 * Mobile Menu JavaScript - Sistema CFC Bom Conselho
 * Fase 1: Menu hambúrguer e drawer de navegação
 */

class MobileMenu {
    constructor() {
        this.drawer = null;
        this.toggle = null;
        this.closeButton = null;
        this.overlay = null;
        this.isOpen = false;
        this.focusableElements = [];
        this.previousActiveElement = null;
        
        // Elementos da busca mobile
        this.searchContainer = null;
        this.searchInput = null;
        this.searchButton = null;
        this.filtersToggle = null;
        this.filtersPanel = null;
        this.activeFilters = [];
        
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
        console.log('Inicializando Mobile Menu...');
        
        // Elementos principais
        this.drawer = document.getElementById('mobile-drawer');
        this.toggle = document.getElementById('mobile-menu-toggle');
        this.closeButton = document.getElementById('mobile-drawer-close');
        this.overlay = document.getElementById('mobile-drawer-overlay');
        
        // Elementos da busca mobile
        this.searchContainer = document.getElementById('mobile-search-container');
        this.searchInput = document.getElementById('mobile-search-input');
        this.searchButton = document.getElementById('mobile-search-button');
        this.filtersToggle = document.getElementById('mobile-filters-toggle');
        this.filtersPanel = document.getElementById('mobile-filters-panel');
        
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
        
        // Configurar busca mobile
        this.setupMobileSearch();
        
        // Expandir grupo com item ativo
        this.expandActiveGroup();
        
        console.log('Mobile Menu configurado com sucesso');
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
        
        // Ajustar posicionamento da busca mobile
        this.adjustMobileSearchPosition();
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
    
    // Método público para fechar menu (útil para outros scripts)
    close() {
        this.closeMenu();
    }
    
    // Método público para verificar se está aberto
    isMenuOpen() {
        return this.isOpen;
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
    
    // Configurar busca mobile
    setupMobileSearch() {
        if (!this.searchContainer) return;
        
        // Toggle dos filtros
        if (this.filtersToggle && this.filtersPanel) {
            this.filtersToggle.addEventListener('click', (e) => {
                e.preventDefault();
                this.toggleFilters();
            });
        }
        
        // Botão de busca
        if (this.searchButton) {
            this.searchButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.performSearch();
            });
        }
        
        // Enter na busca
        if (this.searchInput) {
            this.searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch();
                }
            });
            
            // Limpar busca com X
            this.searchInput.addEventListener('input', (e) => {
                this.updateSearchButton(e.target.value);
            });
        }
        
        // Aplicar filtros
        const applyBtn = document.getElementById('mobile-apply-filters');
        if (applyBtn) {
            applyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.applyFilters();
            });
        }
        
        // Limpar filtros
        const clearBtn = document.getElementById('mobile-clear-filters');
        if (clearBtn) {
            clearBtn.addEventListener('click', (e) => {
                e.preventDefault();
                this.clearFilters();
            });
        }
    }
    
    // Toggle dos filtros
    toggleFilters() {
        if (!this.filtersPanel || !this.filtersToggle) return;
        
        const isOpen = this.filtersPanel.classList.contains('open');
        
        if (isOpen) {
            this.filtersPanel.classList.remove('open');
            this.filtersToggle.classList.remove('active');
            this.filtersToggle.setAttribute('aria-expanded', 'false');
        } else {
            this.filtersPanel.classList.add('open');
            this.filtersToggle.classList.add('active');
            this.filtersToggle.setAttribute('aria-expanded', 'true');
        }
    }
    
    // Executar busca
    performSearch() {
        if (!this.searchInput) return;
        
        const query = this.searchInput.value.trim();
        if (query) {
            console.log('Executando busca:', query);
            // Aqui você implementaria a lógica de busca
            // Por exemplo, filtrar tabelas, fazer requisições AJAX, etc.
            this.showSearchResults(query);
        }
    }
    
    // Atualizar botão de busca
    updateSearchButton(value) {
        if (!this.searchButton) return;
        
        if (value.trim()) {
            this.searchButton.innerHTML = '<i class="fas fa-search"></i>';
            this.searchButton.setAttribute('aria-label', 'Buscar');
        } else {
            this.searchButton.innerHTML = '<i class="fas fa-times"></i>';
            this.searchButton.setAttribute('aria-label', 'Limpar busca');
        }
    }
    
    // Aplicar filtros
    applyFilters() {
        const statusFilter = document.getElementById('mobile-filter-status');
        const categoriaFilter = document.getElementById('mobile-filter-categoria');
        
        const filters = {
            status: statusFilter ? statusFilter.value : '',
            categoria: categoriaFilter ? categoriaFilter.value : ''
        };
        
        // Filtrar apenas valores não vazios
        const activeFilters = Object.entries(filters)
            .filter(([key, value]) => value)
            .map(([key, value]) => ({ key, value }));
        
        this.activeFilters = activeFilters;
        this.updateActiveFiltersDisplay();
        this.toggleFilters(); // Fechar painel após aplicar
        
        console.log('Filtros aplicados:', activeFilters);
        // Aqui você implementaria a lógica de filtragem
    }
    
    // Limpar filtros
    clearFilters() {
        const statusFilter = document.getElementById('mobile-filter-status');
        const categoriaFilter = document.getElementById('mobile-filter-categoria');
        
        if (statusFilter) statusFilter.value = '';
        if (categoriaFilter) categoriaFilter.value = '';
        
        this.activeFilters = [];
        this.updateActiveFiltersDisplay();
        
        console.log('Filtros limpos');
        // Aqui você implementaria a lógica para remover filtros
    }
    
    // Atualizar exibição dos filtros ativos
    updateActiveFiltersDisplay() {
        const container = document.getElementById('mobile-active-filters');
        if (!container) return;
        
        if (this.activeFilters.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.style.display = 'flex';
        container.innerHTML = this.activeFilters.map(filter => `
            <div class="mobile-filter-chip">
                <span>${this.getFilterLabel(filter.key)}: ${filter.value}</span>
                <span class="remove" data-key="${filter.key}">×</span>
            </div>
        `).join('');
        
        // Adicionar event listeners para remover filtros
        container.querySelectorAll('.remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const key = e.target.getAttribute('data-key');
                this.removeFilter(key);
            });
        });
    }
    
    // Obter label do filtro
    getFilterLabel(key) {
        const labels = {
            status: 'Status',
            categoria: 'Categoria'
        };
        return labels[key] || key;
    }
    
    // Remover filtro específico
    removeFilter(key) {
        this.activeFilters = this.activeFilters.filter(filter => filter.key !== key);
        this.updateActiveFiltersDisplay();
        
        // Atualizar select correspondente
        const select = document.getElementById(`mobile-filter-${key}`);
        if (select) {
            select.value = '';
        }
        
        console.log('Filtro removido:', key);
    }
    
    // Mostrar resultados da busca
    showSearchResults(query) {
        // Implementar lógica de busca aqui
        console.log('Mostrando resultados para:', query);
    }
    
    // Ajustar posicionamento da busca mobile
    adjustMobileSearchPosition() {
        if (!this.searchContainer) return;
        
        const isMobile = window.innerWidth <= 768;
        const mainContent = document.querySelector('.admin-main');
        
        if (isMobile && mainContent) {
            // Em mobile, ajustar margin-top do conteúdo principal
            const searchHeight = this.searchContainer.offsetHeight;
            const topbarHeight = 56; // Altura da topbar mobile
            mainContent.style.marginTop = (topbarHeight + searchHeight) + 'px';
        } else if (mainContent) {
            // Em desktop, restaurar margin-top padrão
            mainContent.style.marginTop = '';
        }
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
        window.mobileMenu = new MobileMenu();
    }
});

// Exportar para uso global se necessário
if (typeof window !== 'undefined') {
    window.MobileMenu = MobileMenu;
}
