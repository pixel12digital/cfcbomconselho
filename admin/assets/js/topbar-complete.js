/**
 * Topbar Completa - Sistema CFC
 * Logo | Busca Global | Notificações | Perfil
 */

class CompleteTopbar {
    constructor() {
        this.searchInput = null;
        this.searchResults = null;
        this.notificationIcon = null;
        this.notificationDropdown = null;
        this.profileButton = null;
        this.profileDropdown = null;
        
        this.searchTimeout = null;
        this.isLoading = false;
        this.minSearchLength = 3;
        this.searchDelay = 300;
        
        this.init();
    }
    
    init() {
        console.log('🚀 Topbar completa inicializada');
        this.createTopbar();
        this.bindEvents();
        this.loadUserProfile();
        this.loadNotifications();
    }
    
    createTopbar() {
        // Criar topbar completa
        const topbar = document.createElement('div');
        topbar.className = 'topbar';
        topbar.innerHTML = `
            <!-- Logo -->
            <a href="?page=dashboard" class="topbar-logo">
                <div class="topbar-logo-icon">
                    <i class="fas fa-car"></i>
                </div>
                <div class="topbar-logo-text">CFC Bom Conselho</div>
            </a>
            
            <!-- Busca Global -->
            <div class="topbar-search">
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        class="search-input" 
                        placeholder="Pesquisar por nome, CPF, matrícula, telefone..."
                        autocomplete="off"
                        aria-label="Busca global"
                    >
                    <i class="fas fa-search search-icon"></i>
                    <div class="search-results" id="search-results" role="listbox" aria-label="Resultados da pesquisa"></div>
                </div>
            </div>
            
            <!-- Notificações -->
            <div class="topbar-notifications">
                <button class="notification-icon" aria-label="Notificações">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge hidden" id="notification-badge">0</span>
                </button>
                <div class="notification-dropdown" id="notification-dropdown">
                    <div class="notification-header">
                        <h3 class="notification-title">Notificações</h3>
                    </div>
                    <div class="notification-list" id="notification-list">
                        <div class="search-loading">Carregando notificações...</div>
                    </div>
                    <div class="notification-footer">
                        <div class="notification-actions">
                            <button class="notification-btn" id="mark-all-read">Marcar todas como lidas</button>
                            <a href="?page=notifications" class="notification-btn">Ver todas</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Perfil do Usuário -->
            <div class="topbar-profile">
                <button class="profile-button" id="profile-button" aria-label="Perfil do usuário">
                    <div class="profile-avatar" id="profile-avatar">A</div>
                    <div class="profile-info">
                        <div class="profile-name" id="profile-name">Administrador</div>
                        <div class="profile-role" id="profile-role">Sistema</div>
                    </div>
                    <i class="fas fa-chevron-down profile-dropdown-icon"></i>
                </button>
                <div class="profile-dropdown" id="profile-dropdown">
                    <a href="?page=profile" class="profile-dropdown-item">
                        <i class="fas fa-user profile-dropdown-icon-item"></i>
                        Meu Perfil
                    </a>
                    <a href="?page=change-password" class="profile-dropdown-item">
                        <i class="fas fa-key profile-dropdown-icon-item"></i>
                        Trocar senha
                    </a>
                    <a href="logout.php" class="profile-dropdown-item logout">
                        <i class="fas fa-sign-out-alt profile-dropdown-icon-item"></i>
                        Sair
                    </a>
                </div>
            </div>
        `;
        
        // Inserir no início do body
        document.body.insertBefore(topbar, document.body.firstChild);
        
        // Referenciar elementos
        this.searchInput = topbar.querySelector('.search-input');
        this.searchResults = topbar.querySelector('.search-results');
        this.notificationIcon = topbar.querySelector('.notification-icon');
        this.notificationDropdown = topbar.querySelector('.notification-dropdown');
        this.profileButton = topbar.querySelector('.profile-button');
        this.profileDropdown = topbar.querySelector('.profile-dropdown');
        
        console.log('✅ Topbar completa criada');
    }
    
    bindEvents() {
        // Eventos da busca
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });
        
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= this.minSearchLength) {
                this.showSearchResults();
            }
        });
        
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleSearchKeydown(e);
        });
        
        // Eventos das notificações
        this.notificationIcon.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleNotificationDropdown();
        });
        
        // Eventos do perfil
        this.profileButton.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggleProfileDropdown();
        });
        
        // Eventos globais
        document.addEventListener('click', (e) => {
            this.handleGlobalClick(e);
        });
        
        // Atalho de teclado "/" para focar busca
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                this.searchInput.focus();
            }
        });
        
        console.log('✅ Eventos da topbar configurados');
    }
    
    // =====================================================
    // FUNCIONALIDADES DE BUSCA
    // =====================================================
    
    handleSearch(query) {
        // Limpar timeout anterior
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Se query muito curta, limpar resultados
        if (query.length < this.minSearchLength) {
            this.hideSearchResults();
            return;
        }
        
        // Debounce da pesquisa
        this.searchTimeout = setTimeout(() => {
            this.performSearch(query);
        }, this.searchDelay);
    }
    
    async performSearch(query) {
        if (this.isLoading) return;
        
        this.isLoading = true;
        this.showSearchLoading();
        
        try {
            console.log('🔍 Pesquisando por:', query);
            
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Erro na pesquisa');
            }
            
            this.displaySearchResults(data.results || []);
            
        } catch (error) {
            console.error('❌ Erro na pesquisa:', error);
            this.showSearchError('Erro ao realizar pesquisa');
        } finally {
            this.isLoading = false;
        }
    }
    
    displaySearchResults(results) {
        if (results.length === 0) {
            this.showSearchEmpty();
            return;
        }
        
        const html = results.slice(0, 8).map((result, index) => `
            <div class="search-result-item" role="option" tabindex="0" data-index="${index}">
                <div class="search-result-icon" style="background-color: ${result.color}">
                    <i class="${result.icon}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-subtitle">${result.subtitle}</div>
                </div>
                <div class="search-result-type">${result.type}</div>
            </div>
        `).join('');
        
        const footerHtml = `
            <div class="search-footer">
                <a href="?page=search&q=${encodeURIComponent(this.searchInput.value)}" class="search-footer-link">
                    Ver todos os resultados
                </a>
            </div>
        `;
        
        this.searchResults.innerHTML = html + footerHtml;
        this.showSearchResults();
        
        // Adicionar eventos aos itens
        this.bindSearchResultEvents();
        
        console.log(`✅ ${results.length} resultados encontrados`);
    }
    
    bindSearchResultEvents() {
        const items = this.searchResults.querySelectorAll('.search-result-item');
        
        items.forEach((item, index) => {
            item.addEventListener('click', () => {
                const url = item.querySelector('a')?.href || item.dataset.url;
                if (url) {
                    window.location.href = url;
                }
            });
            
            item.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    item.click();
                }
            });
        });
    }
    
    handleSearchKeydown(e) {
        const results = this.searchResults.querySelectorAll('.search-result-item');
        
        if (e.key === 'Escape') {
            this.hideSearchResults();
            this.searchInput.blur();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            const active = this.searchResults.querySelector('.search-result-item.active');
            if (active) {
                active.classList.remove('active');
                const next = active.nextElementSibling;
                if (next && next.classList.contains('search-result-item')) {
                    next.classList.add('active');
                }
            } else if (results.length > 0) {
                results[0].classList.add('active');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const active = this.searchResults.querySelector('.search-result-item.active');
            if (active) {
                active.classList.remove('active');
                const prev = active.previousElementSibling;
                if (prev && prev.classList.contains('search-result-item')) {
                    prev.classList.add('active');
                }
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const active = this.searchResults.querySelector('.search-result-item.active');
            if (active) {
                active.click();
            }
        }
    }
    
    showSearchLoading() {
        this.searchResults.innerHTML = `
            <div class="search-loading">
                Pesquisando...
            </div>
        `;
        this.showSearchResults();
    }
    
    showSearchEmpty() {
        this.searchResults.innerHTML = `
            <div class="search-empty">
                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                Nenhum resultado encontrado
            </div>
        `;
        this.showSearchResults();
    }
    
    showSearchError(message) {
        this.searchResults.innerHTML = `
            <div class="search-error">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                ${message}
            </div>
        `;
        this.showSearchResults();
    }
    
    showSearchResults() {
        this.searchResults.classList.add('show');
    }
    
    hideSearchResults() {
        this.searchResults.classList.remove('show');
    }
    
    // =====================================================
    // FUNCIONALIDADES DE NOTIFICAÇÕES
    // =====================================================
    
    async loadNotifications() {
        try {
            const response = await fetch('api/notifications.php?limit=10');
            const data = await response.json();
            
            if (data.success) {
                this.displayNotifications(data.notifications || []);
                this.updateNotificationBadge(data.unread_count || 0);
            }
        } catch (error) {
            console.error('❌ Erro ao carregar notificações:', error);
            this.showNotificationError();
        }
    }
    
    displayNotifications(notifications) {
        const list = this.notificationDropdown.querySelector('.notification-list');
        
        if (notifications.length === 0) {
            list.innerHTML = `
                <div class="search-empty">
                    <i class="fas fa-bell-slash" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                    Nenhuma notificação
                </div>
            `;
            return;
        }
        
        const html = notifications.map(notification => `
            <a href="${notification.url || '#'}" class="notification-item ${notification.unread ? 'unread' : ''}">
                <div class="notification-icon-item" style="background-color: ${notification.color || '#3498db'}">
                    <i class="${notification.icon || 'fas fa-info'}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">${notification.message}</div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
            </a>
        `).join('');
        
        list.innerHTML = html;
    }
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
    
    toggleNotificationDropdown() {
        const isOpen = this.notificationDropdown.classList.contains('show');
        
        // Fechar outros dropdowns
        this.hideProfileDropdown();
        this.hideSearchResults();
        
        if (isOpen) {
            this.hideNotificationDropdown();
        } else {
            this.showNotificationDropdown();
        }
    }
    
    showNotificationDropdown() {
        this.notificationDropdown.classList.add('show');
        this.loadNotifications(); // Recarregar notificações
    }
    
    hideNotificationDropdown() {
        this.notificationDropdown.classList.remove('show');
    }
    
    showNotificationError() {
        const list = this.notificationDropdown.querySelector('.notification-list');
        list.innerHTML = `
            <div class="search-error">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                Erro ao carregar notificações
            </div>
        `;
    }
    
    formatTime(timestamp) {
        const now = new Date();
        const time = new Date(timestamp);
        const diff = now - time;
        
        if (diff < 60000) return 'Agora';
        if (diff < 3600000) return `${Math.floor(diff / 60000)}min atrás`;
        if (diff < 86400000) return `${Math.floor(diff / 3600000)}h atrás`;
        return time.toLocaleDateString('pt-BR');
    }
    
    // =====================================================
    // FUNCIONALIDADES DO PERFIL
    // =====================================================
    
    async loadUserProfile() {
        try {
            // Simular dados do usuário (substituir por API real)
            const userData = {
                name: 'Administrador Sistema',
                role: 'Administrador',
                avatar: 'A',
                initials: 'AS'
            };
            
            this.displayUserProfile(userData);
        } catch (error) {
            console.error('❌ Erro ao carregar perfil:', error);
        }
    }
    
    displayUserProfile(userData) {
        const avatar = document.getElementById('profile-avatar');
        const name = document.getElementById('profile-name');
        const role = document.getElementById('profile-role');
        
        avatar.textContent = userData.initials || userData.name.charAt(0);
        name.textContent = userData.name;
        role.textContent = userData.role;
    }
    
    toggleProfileDropdown() {
        const isOpen = this.profileDropdown.classList.contains('show');
        
        // Fechar outros dropdowns
        this.hideNotificationDropdown();
        this.hideSearchResults();
        
        if (isOpen) {
            this.hideProfileDropdown();
        } else {
            this.showProfileDropdown();
        }
    }
    
    showProfileDropdown() {
        this.profileDropdown.classList.add('show');
        this.profileButton.classList.add('active');
    }
    
    hideProfileDropdown() {
        this.profileDropdown.classList.remove('show');
        this.profileButton.classList.remove('active');
    }
    
    // =====================================================
    // EVENTOS GLOBAIS
    // =====================================================
    
    handleGlobalClick(e) {
        // Fechar dropdowns ao clicar fora
        if (!e.target.closest('.search-input-wrapper')) {
            this.hideSearchResults();
        }
        
        if (!e.target.closest('.topbar-notifications')) {
            this.hideNotificationDropdown();
        }
        
        if (!e.target.closest('.topbar-profile')) {
            this.hideProfileDropdown();
        }
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 DOM carregado, inicializando topbar completa...');
    new CompleteTopbar();
});

// Fallback: inicializar após um pequeno delay se necessário
setTimeout(function() {
    if (!document.querySelector('.topbar')) {
        console.log('⚠️ Topbar não encontrada, criando novamente...');
        new CompleteTopbar();
    }
}, 100);
