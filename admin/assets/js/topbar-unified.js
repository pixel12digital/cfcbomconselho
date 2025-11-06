/**
 * Topbar Unificada - Sistema CFC Bom Conselho
 * Layout: Logo | Busca Global | Notificações | Perfil
 * Funcionalidades: Busca com debounce, notificações, perfil
 */

class TopbarUnified {
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
        console.log('🚀 Topbar unificada inicializando...');
        
        // Aguardar um pouco para garantir que o DOM esteja pronto
        setTimeout(() => {
            this.ensureTopbarExists();
            this.bindEvents();
            this.loadUserProfile();
            this.loadNotifications();
            this.ensureStickyBehavior();
            console.log('✅ Topbar unificada inicializada com sucesso');
        }, 100);
    }
    
    ensureTopbarExists() {
        // Verificar se a topbar já existe no HTML
        let topbar = document.querySelector('.topbar');
        
        if (!topbar) {
            console.log('⚠️ Topbar não encontrada no HTML, criando...');
            this.createTopbar();
        } else {
            console.log('✅ Topbar encontrada no HTML');
            this.bindElements();
            
            // Garantir que a topbar seja sempre sticky
            this.ensureStickyBehavior();
        }
    }
    
    ensureStickyBehavior() {
        const topbar = document.querySelector('.topbar');
        if (topbar) {
            // Forçar comportamento sticky
            topbar.style.position = 'fixed';
            topbar.style.top = '0';
            topbar.style.left = '0';
            topbar.style.right = '0';
            topbar.style.zIndex = '1000';
            
            // CRÍTICO: Usar variável CSS em vez de inline style para evitar duplicação
            // Medir altura real da navbar e aplicar via CSS variable
            const navbarHeight = topbar.offsetHeight || 64;
            document.documentElement.style.setProperty('--navbar-h', `${navbarHeight}px`);
            
            // Remover qualquer padding-top inline do body (se existir)
            document.body.style.paddingTop = '';
            
            // Remover qualquer margin-top inline do .admin-main (se existir)
            const adminMain = document.querySelector('.admin-main, .admin-container');
            if (adminMain) {
                adminMain.style.marginTop = '';
            }
            
            // Monitorar scroll para garantir que a topbar permaneça no topo
            this.monitorScroll();
            
            console.log('✅ Comportamento sticky garantido');
        }
    }
    
    monitorScroll() {
        let lastScrollTop = 0;
        
        window.addEventListener('scroll', () => {
            const topbar = document.querySelector('.topbar');
            if (topbar) {
                // Garantir que a topbar sempre fique no topo
                topbar.style.position = 'fixed';
                topbar.style.top = '0';
                topbar.style.left = '0';
                topbar.style.right = '0';
                topbar.style.zIndex = '1000';
                
                // CRÍTICO: Atualizar variável CSS se a altura mudar, mas NÃO aplicar inline style
                const navbarHeight = topbar.offsetHeight || 64;
                document.documentElement.style.setProperty('--navbar-h', `${navbarHeight}px`);
                
                // Garantir que não há padding-top inline no body
                if (document.body.style.paddingTop) {
                    document.body.style.paddingTop = '';
                }
            }
        }, { passive: true });
        
        console.log('✅ Monitor de scroll ativado');
    }
    
    createTopbar() {
        const topbar = document.createElement('div');
        topbar.className = 'topbar';
        topbar.innerHTML = this.getTopbarHTML();
        
        // Inserir no início do body
        document.body.insertBefore(topbar, document.body.firstChild);
        
        this.bindElements();
        console.log('✅ Topbar criada dinamicamente');
    }
    
    getTopbarHTML() {
        return `
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
            
            <!-- Notificações e Perfil (Direita) -->
            <div class="topbar-right">
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
            </div>
        `;
    }
    
    bindElements() {
        // Referenciar elementos
        this.searchInput = document.querySelector('.search-input');
        this.searchResults = document.querySelector('.search-results');
        this.notificationIcon = document.querySelector('.notification-icon');
        this.notificationDropdown = document.querySelector('.notification-dropdown');
        this.profileButton = document.querySelector('.profile-button');
        this.profileDropdown = document.querySelector('.profile-dropdown');
        
        console.log('🔍 Elementos da topbar:', {
            searchInput: !!this.searchInput,
            searchResults: !!this.searchResults,
            notificationIcon: !!this.notificationIcon,
            notificationDropdown: !!this.notificationDropdown,
            profileButton: !!this.profileButton,
            profileDropdown: !!this.profileDropdown
        });
    }
    
    bindEvents() {
        // Eventos da busca
        if (this.searchInput) {
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
        }
        
        // Eventos das notificações
        if (this.notificationIcon) {
            this.notificationIcon.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleNotificationDropdown();
            });
        }
        
        // Eventos dos botões de ação das notificações
        document.addEventListener('click', (e) => {
            if (e.target.id === 'mark-all-read') {
                e.preventDefault();
                e.stopPropagation();
                this.markAllAsRead();
            }
        });
        
        // Eventos do perfil
        if (this.profileButton) {
            this.profileButton.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleProfileDropdown();
            });
        }
        
        // Eventos globais
        document.addEventListener('click', (e) => {
            this.handleGlobalClick(e);
        });
        
        // Atalho de teclado "/" para focar busca
        document.addEventListener('keydown', (e) => {
            if (e.key === '/' && !e.target.matches('input, textarea')) {
                e.preventDefault();
                if (this.searchInput) {
                    this.searchInput.focus();
                }
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
            <div class="search-result-item" role="option" tabindex="0" data-index="${index}" data-url="${result.url || '#'}">
                <div class="search-result-icon" style="background-color: ${result.color || '#3498db'}">
                    <i class="${result.icon || 'fas fa-search'}"></i>
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
                const url = item.dataset.url;
                if (url && url !== '#') {
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
            console.log('🔔 Carregando notificações...');
            const response = await fetch('api/notifications.php?limit=10');
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            console.log('📨 Dados das notificações:', data);
            
            if (data.success) {
                this.displayNotifications(data.notifications || []);
                this.updateNotificationBadge(data.unread_count || 0);
            } else {
                throw new Error(data.message || 'Erro ao carregar notificações');
            }
        } catch (error) {
            console.error('❌ Erro ao carregar notificações:', error);
            this.showNotificationError();
            // Usar dados mock para demonstração
            this.loadMockNotifications();
        }
    }
    
    loadMockNotifications() {
        console.log('🔄 Carregando notificações mock...');
        const mockNotifications = [
            {
                id: 1,
                title: 'Novo aluno cadastrado',
                message: 'João Silva foi cadastrado no sistema',
                type: 'user',
                url: '?page=alunos&action=view&id=1',
                unread: true,
                created_at: new Date().toISOString(),
                icon: 'fas fa-user',
                color: '#9b59b6'
            },
            {
                id: 2,
                title: 'Aula agendada',
                message: 'Aula prática agendada para amanhã às 14:00',
                type: 'schedule',
                url: '?page=aulas&action=view&id=1',
                unread: true,
                created_at: new Date(Date.now() - 3600000).toISOString(),
                icon: 'fas fa-calendar',
                color: '#e67e22'
            },
            {
                id: 3,
                title: 'Pagamento recebido',
                message: 'Pagamento de R$ 150,00 recebido de Maria Santos',
                type: 'payment',
                url: '?page=financeiro&action=view&id=1',
                unread: false,
                created_at: new Date(Date.now() - 7200000).toISOString(),
                icon: 'fas fa-credit-card',
                color: '#27ae60'
            },
            {
                id: 4,
                title: 'Sistema atualizado',
                message: 'Nova versão do sistema disponível',
                type: 'system',
                url: '?page=atualizacoes',
                unread: true,
                created_at: new Date(Date.now() - 86400000).toISOString(),
                icon: 'fas fa-cog',
                color: '#34495e'
            },
            {
                id: 5,
                title: 'Documento vencido',
                message: 'CNH do instrutor Carlos expira em 30 dias',
                type: 'warning',
                url: '?page=instrutores&action=view&id=1',
                unread: true,
                created_at: new Date(Date.now() - 172800000).toISOString(),
                icon: 'fas fa-exclamation-triangle',
                color: '#f39c12'
            }
        ];
        
        this.displayNotifications(mockNotifications);
        this.updateNotificationBadge(mockNotifications.filter(n => n.unread).length);
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
            <a href="${notification.url || '#'}" class="notification-item ${notification.unread ? 'unread' : ''}" data-id="${notification.id}">
                <div class="notification-icon-item" style="background-color: ${notification.color || '#3498db'}">
                    <i class="${notification.icon || 'fas fa-info'}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-message">
                        <strong>${notification.title}</strong><br>
                        ${notification.message}
                    </div>
                    <div class="notification-time">${this.formatTime(notification.created_at)}</div>
                </div>
                ${notification.unread ? '<div class="notification-unread-indicator"></div>' : ''}
            </a>
        `).join('');
        
        list.innerHTML = html;
        
        // Adicionar eventos aos itens
        this.bindNotificationEvents();
        
        console.log(`✅ ${notifications.length} notificações exibidas`);
    }
    
    bindNotificationEvents() {
        const items = this.notificationDropdown.querySelectorAll('.notification-item');
        
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                const notificationId = item.dataset.id;
                if (notificationId) {
                    this.markAsRead(notificationId);
                }
            });
        });
    }
    
    async markAsRead(notificationId) {
        try {
            console.log('📖 Marcando notificação como lida:', notificationId);
            // Aqui você pode implementar a chamada para a API
            // await fetch(`api/notifications.php`, {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ action: 'mark_read', id: notificationId })
            // });
        } catch (error) {
            console.error('❌ Erro ao marcar como lida:', error);
        }
    }
    
    updateNotificationBadge(count) {
        const badge = document.getElementById('notification-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count > 99 ? '99+' : count;
                badge.classList.remove('hidden');
                console.log(`🔔 Badge atualizado: ${count} notificações não lidas`);
            } else {
                badge.classList.add('hidden');
                console.log('🔔 Badge ocultado: nenhuma notificação não lida');
            }
        }
    }
    
    async markAllAsRead() {
        try {
            console.log('📖 Marcando todas as notificações como lidas...');
            
            // Simular chamada para API
            // const response = await fetch('api/notifications.php', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify({ action: 'mark_all_read' })
            // });
            
            // Atualizar badge para 0
            this.updateNotificationBadge(0);
            
            // Atualizar visual das notificações
            const items = this.notificationDropdown.querySelectorAll('.notification-item');
            items.forEach(item => {
                item.classList.remove('unread');
                const indicator = item.querySelector('.notification-unread-indicator');
                if (indicator) {
                    indicator.remove();
                }
            });
            
            console.log('✅ Todas as notificações marcadas como lidas');
        } catch (error) {
            console.error('❌ Erro ao marcar todas como lidas:', error);
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
        
        if (avatar) avatar.textContent = userData.initials || userData.name.charAt(0);
        if (name) name.textContent = userData.name;
        if (role) role.textContent = userData.role;
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
    console.log('🚀 DOM carregado, inicializando topbar unificada...');
    new TopbarUnified();
});

// Fallback: inicializar após um pequeno delay se necessário
setTimeout(function() {
    if (!document.querySelector('.topbar')) {
        console.log('⚠️ Topbar não encontrada, criando novamente...');
        new TopbarUnified();
    }
}, 500);
