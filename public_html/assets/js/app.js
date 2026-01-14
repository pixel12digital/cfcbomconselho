// App JavaScript

(function() {
    'use strict';
    
    // Função para inicializar o sidebar toggle
    function initSidebarToggle() {
        // Sidebar elements
        const sidebar = document.getElementById('sidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (!sidebarToggle || !sidebar) {
            return; // Elementos não encontrados
        }
        
        // Sidebar Toggle (Mobile) - Definir antes de usar
        function toggleMobileSidebar() {
            if (sidebar && sidebarOverlay && window.innerWidth <= 768) {
                sidebar.classList.toggle('open');
                sidebarOverlay.classList.toggle('show');
                // Adicionar/remover classe no body para estilizar o botão
                if (sidebar.classList.contains('open')) {
                    document.body.classList.add('sidebar-open');
                } else {
                    document.body.classList.remove('sidebar-open');
                }
            }
        }
        
        // Sidebar Toggle Handler - Único listener consolidado
        function handleSidebarToggle(e) {
            // Prevenir propagação para outros listeners que possam interferir
            e.stopPropagation();
            
            // Desktop: Toggle manual collapse (for pinning feature)
            if (window.innerWidth > 768) {
                sidebar.classList.toggle('collapsed');
                localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
            } else {
                // Mobile: Toggle drawer
                toggleMobileSidebar();
            }
        }
        
        // Adicionar listener - usar capture: true para garantir que seja executado antes de outros listeners
        sidebarToggle.addEventListener('click', handleSidebarToggle, true);
        
        // Restaurar estado do sidebar (desktop only, não interfere com hover)
        if (window.innerWidth > 768) {
            const wasCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            if (wasCollapsed) {
                sidebar.classList.add('collapsed');
            }
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                if (sidebar) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            });
        }
        
        // Fechar sidebar mobile ao clicar em item
        const sidebarItems = document.querySelectorAll('.sidebar-menu-item');
        sidebarItems.forEach(item => {
            item.addEventListener('click', function() {
                if (window.innerWidth <= 768 && sidebar && sidebarOverlay) {
                    sidebar.classList.remove('open');
                    sidebarOverlay.classList.remove('show');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
    }
    
    // Inicializar quando o DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSidebarToggle);
    } else {
        // DOM já está pronto
        initSidebarToggle();
    }
    
    // Sidebar elements para uso em outros listeners (acessíveis globalmente dentro do IIFE)
    let sidebar, sidebarToggle, sidebarOverlay;
    
    // Atualizar referências após inicialização
    function updateSidebarReferences() {
        sidebar = document.getElementById('sidebar');
        sidebarToggle = document.getElementById('sidebarToggle');
        sidebarOverlay = document.getElementById('sidebarOverlay');
    }
    
    // Atualizar referências
    updateSidebarReferences();
    
    // Re-atualizar após DOMContentLoaded se necessário
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateSidebarReferences);
    }
    
    // Role Selector
    const roleSelectorBtn = document.getElementById('roleSelectorBtn');
    const roleDropdown = document.getElementById('roleDropdown');
    
    if (roleSelectorBtn && roleDropdown) {
        roleSelectorBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            roleDropdown.classList.toggle('show');
        });
        
        // Fechar ao clicar fora - garantir que não bloqueie sidebarToggle
        document.addEventListener('click', function(e) {
            // Não processar se o clique foi no sidebarToggle ou seus filhos
            if (sidebarToggle && (sidebarToggle === e.target || sidebarToggle.contains(e.target))) {
                return;
            }
            
            if (!roleSelectorBtn.contains(e.target) && !roleDropdown.contains(e.target)) {
                roleDropdown.classList.remove('show');
            }
        });
        
        // Trocar papel
        const roleItems = roleDropdown.querySelectorAll('.topbar-role-dropdown-item');
        roleItems.forEach(item => {
            item.addEventListener('click', function() {
                const role = this.getAttribute('data-role');
                
                // Fazer requisição para trocar papel
                fetch('/api/switch-role', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ role: role })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Erro ao trocar papel: ' + (data.message || 'Erro desconhecido'));
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    alert('Erro ao trocar papel');
                });
            });
        });
    }
    
    // Profile Dropdown
    const profileBtn = document.getElementById('profileBtn');
    const profileDropdown = document.getElementById('profileDropdown');
    
    if (profileBtn && profileDropdown) {
        profileBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            profileDropdown.style.display = profileDropdown.style.display === 'none' ? 'block' : 'none';
        });
        
        // Fechar ao clicar fora - garantir que não bloqueie sidebarToggle
        document.addEventListener('click', function(e) {
            // Não processar se o clique foi no sidebarToggle ou seus filhos
            if (sidebarToggle && (sidebarToggle === e.target || sidebarToggle.contains(e.target))) {
                return;
            }
            
            if (!profileBtn.contains(e.target) && !profileDropdown.contains(e.target)) {
                profileDropdown.style.display = 'none';
            }
        });
    }
    
    // Responsive
    window.addEventListener('resize', function() {
        updateSidebarReferences();
        if (sidebar && sidebarOverlay && window.innerWidth > 768) {
            sidebar.classList.remove('open');
            sidebarOverlay.classList.remove('show');
            document.body.classList.remove('sidebar-open');
        }
    });
})();
