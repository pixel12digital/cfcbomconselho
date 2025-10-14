/**
 * JavaScript Flyout/Hover Menu
 * Sidebar sempre compacta com flyouts que aparecem no hover
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Menu flyout carregado');
    
    // =====================================================
    // CONFIGURAÇÃO DOS FLYOUTS
    // =====================================================
    
    const flyoutConfig = {
        'cadastros': {
            title: 'Cadastros',
            items: [
                { icon: 'fas fa-users', text: 'Usuários', href: '?page=usuarios' },
                { icon: 'fas fa-building', text: 'CFCs', href: '?page=cfcs' },
                { icon: 'fas fa-graduation-cap', text: 'Alunos', href: '?page=alunos' },
                { icon: 'fas fa-chalkboard-teacher', text: 'Instrutores', href: '?page=instrutores' },
                { icon: 'fas fa-car', text: 'Veículos', href: '?page=veiculos' }
            ]
        },
        'operacional': {
            title: 'Operacional',
            items: [
                { icon: 'fas fa-calendar-alt', text: 'Agendamento', href: '?page=agendamento' },
                { icon: 'fas fa-stethoscope', text: 'Exames Médicos', href: '?page=exames' }
            ]
        },
        'turmas': {
            title: 'Turmas Teóricas',
            items: [
                { icon: 'fas fa-plus', text: 'Nova Turma', href: '?page=turmas-teoricas&acao=nova&step=1' },
                { icon: 'fas fa-list', text: 'Todas as Turmas', href: '?page=turmas-teoricas' },
                { icon: 'fas fa-tachometer-alt', text: 'Dashboard', href: '?page=turma-dashboard' },
                { icon: 'fas fa-calendar-alt', text: 'Calendário de Aulas', href: '?page=turma-calendario' },
                { icon: 'fas fa-user-plus', text: 'Matrículas', href: '?page=turma-matriculas' },
                { icon: 'fas fa-chart-bar', text: 'Relatórios', href: '?page=turma-relatorios' },
                { icon: 'fas fa-cogs', text: 'Configurações', href: '?page=turma-configuracoes' },
                { icon: 'fas fa-copy', text: 'Templates', href: '?page=turma-templates' },
                { icon: 'fas fa-calendar-plus', text: 'Gerador de Grade', href: '?page=turma-grade-generator' }
            ]
        },
        'financeiro': {
            title: 'Financeiro',
            items: [
                { icon: 'fas fa-file-invoice', text: 'Faturas (Receitas)', href: '?page=financeiro-faturas' },
                { icon: 'fas fa-receipt', text: 'Despesas (Pagamentos)', href: '?page=financeiro-despesas' },
                { icon: 'fas fa-chart-line', text: 'Relatórios', href: '?page=financeiro-relatorios' }
            ]
        },
        'relatorios': {
            title: 'Relatórios',
            items: [
                { icon: 'fas fa-chart-bar', text: 'Relatórios Gerais', href: '?page=relatorios' },
                { icon: 'fas fa-file-alt', text: 'Relatórios de Aulas', href: '?page=relatorios-aulas' },
                { icon: 'fas fa-chart-line', text: 'Estatísticas', href: '?page=estatisticas' }
            ]
        },
        'configuracoes': {
            title: 'Configurações',
            items: [
                { icon: 'fas fa-layer-group', text: 'Categorias de Habilitação', href: '?page=configuracoes-categorias' },
                { icon: 'fas fa-door-open', text: 'Salas de Aula', href: '?page=configuracoes-salas' },
                { icon: 'fas fa-cog', text: 'Configurações Gerais', href: '?page=configuracoes' },
                { icon: 'fas fa-user-cog', text: 'Perfil', href: '?page=perfil' },
                { icon: 'fas fa-shield-alt', text: 'Segurança', href: '?page=seguranca' }
            ]
        },
        'ferramentas': {
            title: 'Ferramentas',
            items: [
                { icon: 'fas fa-tools', text: 'Ferramentas Gerais', href: '?page=ferramentas' },
                { icon: 'fas fa-download', text: 'Exportar Dados', href: '?page=exportar' },
                { icon: 'fas fa-upload', text: 'Importar Dados', href: '?page=importar' }
            ]
        }
    };
    
    // =====================================================
    // CRIAÇÃO DOS FLYOUTS
    // =====================================================
    
    function createFlyouts() {
        console.log('Criando flyouts...');
        
        // Criar flyouts para grupos com submenus
        Object.keys(flyoutConfig).forEach(groupId => {
            const config = flyoutConfig[groupId];
            const toggle = document.querySelector(`[data-group="${groupId}"]`);
            
            if (toggle) {
                console.log('Criando flyout para:', groupId);
                
                // Criar elemento flyout
                const flyout = document.createElement('div');
                flyout.className = 'nav-flyout';
                flyout.innerHTML = `
                    <div class="flyout-title">${config.title}</div>
                    ${config.items.map(item => `
                        <a href="${item.href}" class="flyout-item">
                            ${item.text}
                        </a>
                    `).join('')}
                `;
                
                // Adicionar flyout ao toggle
                toggle.parentElement.appendChild(flyout);
                console.log('Flyout criado para:', groupId);
            }
        });
        
        // Criar flyout para Dashboard (item único)
        const dashboardLink = document.querySelector('.nav-link[href="?page=dashboard"]');
        if (dashboardLink) {
            console.log('Criando flyout para Dashboard');
            const flyout = document.createElement('div');
            flyout.className = 'nav-flyout';
            flyout.innerHTML = `
                <div class="flyout-title">Dashboard</div>
                <a href="?page=dashboard" class="flyout-item">
                    Visão Geral
                </a>
            `;
            dashboardLink.parentElement.appendChild(flyout);
        }
        
        // Criar flyout para Sair (item único)
        const logoutLink = document.querySelector('.nav-link[href="logout.php"]');
        if (logoutLink) {
            console.log('Criando flyout para Sair');
            const flyout = document.createElement('div');
            flyout.className = 'nav-flyout';
            flyout.innerHTML = `
                <div class="flyout-title">Sair</div>
                <a href="logout.php" class="flyout-item">
                    Logout
                </a>
            `;
            logoutLink.parentElement.appendChild(flyout);
        }
        
        console.log('Total de flyouts criados:', document.querySelectorAll('.nav-flyout').length);
    }
    
    // =====================================================
    // CONTROLE DE HOVER DOS FLYOUTS - MELHORADO
    // =====================================================
    
    function setupFlyoutHover() {
        console.log('Configurando hover dos flyouts...');
        
        // Aguardar um pouco para garantir que os flyouts foram criados
        setTimeout(() => {
            // Configurar hover para todos os itens de navegação
            const navItems = document.querySelectorAll('.nav-item, .nav-group, .nav-link');
            console.log('Encontrados', navItems.length, 'itens de navegação');
            
            navItems.forEach((item, index) => {
                const flyout = item.querySelector('.nav-flyout');
                
                if (flyout) {
                    console.log('Configurando hover para item', index + 1, ':', item);
                    let hoverTimeout;
                    
                    // Calcular posição do flyout
                    function updateFlyoutPosition() {
                        const rect = item.getBoundingClientRect();
                        flyout.style.top = rect.top + 'px';
                        flyout.style.left = (rect.left + rect.width + 8) + 'px';
                    }
                    
                    item.addEventListener('mouseenter', function() {
                        console.log('Mouse entrou no item:', item);
                        clearTimeout(hoverTimeout);
                        updateFlyoutPosition();
                        flyout.style.opacity = '1';
                        flyout.style.visibility = 'visible';
                        flyout.classList.add('show');
                    });
                    
                    item.addEventListener('mouseleave', function() {
                        console.log('Mouse saiu do item:', item);
                        // Pequeno delay para evitar fechamento acidental
                        hoverTimeout = setTimeout(() => {
                            flyout.style.opacity = '0';
                            flyout.style.visibility = 'hidden';
                            flyout.classList.remove('show');
                        }, 150);
                    });
                    
                    // Atualizar posição quando a janela for redimensionada
                    window.addEventListener('resize', updateFlyoutPosition);
                } else {
                    console.log('Flyout não encontrado para item', index + 1, ':', item);
                }
            });
            
            // Configurar hover para flyouts também (para evitar fechamento ao mover mouse)
            const flyouts = document.querySelectorAll('.nav-flyout');
            console.log('Encontrados', flyouts.length, 'flyouts');
            
            flyouts.forEach((flyout, index) => {
                let hoverTimeout;
                
                flyout.addEventListener('mouseenter', function() {
                    console.log('Mouse entrou no flyout', index + 1);
                    clearTimeout(hoverTimeout);
                });
                
                flyout.addEventListener('mouseleave', function() {
                    console.log('Mouse saiu do flyout', index + 1);
                    hoverTimeout = setTimeout(() => {
                        flyout.style.opacity = '0';
                        flyout.style.visibility = 'hidden';
                        flyout.classList.remove('show');
                    }, 150);
                });
            });
        }, 100);
    }
    
    // =====================================================
    // CONTROLE DE RESPONSIVIDADE
    // =====================================================
    
    function handleResize() {
        console.log('Janela redimensionada:', window.innerWidth);
        
        const sidebar = document.querySelector('.admin-sidebar');
        
        if (window.innerWidth <= 1024) {
            // Em mobile, manter sempre expandido
            if (sidebar) {
                sidebar.style.width = '280px';
                sidebar.classList.add('mobile-expanded');
                console.log('Modo mobile ativado');
            }
        } else {
            // Em desktop, comportamento flyout
            if (sidebar) {
                sidebar.style.width = '70px';
                sidebar.classList.remove('mobile-expanded');
                console.log('Modo desktop ativado');
            }
        }
    }
    
    // =====================================================
    // INICIALIZAÇÃO
    // =====================================================
    
    // Criar flyouts
    createFlyouts();
    
    // Configurar hover
    setupFlyoutHover();
    
    // Configurar responsividade
    window.addEventListener('resize', handleResize);
    
    // Executar uma vez para configurar estado inicial
    handleResize();
    
    console.log('Menu flyout configurado com sucesso');
});
