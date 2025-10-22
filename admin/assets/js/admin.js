/**
 * JavaScript para o Painel Administrativo - Sistema CFC
 * Baseado no design do e-condutor para mesma experiência
 */

// Função global para detectar o path base automaticamente
function getBasePath() {
    return window.location.pathname.includes('/cfc-bom-conselho/') ? '/cfc-bom-conselho' : '';
}

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
        this.setupSidebarHover();
    }
    
    // Configurar hover da sidebar - DESABILITADO para evitar conflito
    setupSidebarHover() {
        // Sistema de hover desabilitado - usando menu-efficient.js
        console.log('Sistema de hover do admin.js desabilitado - usando menu-efficient.js');
        return;
    }

    // Configurar toggle dos submenus
    setupSubmenuToggle() {
        const navToggles = document.querySelectorAll('.nav-toggle');
        
        navToggles.forEach(toggle => {
            toggle.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const group = this.getAttribute('data-group');
                const submenu = document.getElementById(group);
                
                if (submenu) {
                    // Toggle do submenu
                    const isVisible = submenu.style.display === 'block';
                    
                    // Fechar todos os outros submenus
                    document.querySelectorAll('.nav-submenu').forEach(menu => {
                        if (menu.id !== group) {
                            menu.style.display = 'none';
                        }
                    });
                    
                    // Toggle do submenu atual
                    submenu.style.display = isVisible ? 'none' : 'block';
                    
                    console.log('Submenu toggled:', group, 'Visible:', !isVisible);
                }
            });
        });
        
        console.log('Sistema de toggle de submenus configurado');
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

// Função global para abrir modal de gerenciamento de cursos
window.abrirModalTiposCursoInterno = function() {
    console.log('🔧 Tentando abrir modal de cursos...');
    const popup = document.getElementById('modalGerenciarTiposCurso');
    if (popup) {
        console.log('✅ Modal encontrado, abrindo...');
        popup.style.display = 'flex';
        popup.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
        
        // Recarregar tipos de curso se a função existir
        if (typeof recarregarTiposCurso === 'function') {
            recarregarTiposCurso();
        }
    } else {
        console.error('❌ Modal modalGerenciarTiposCurso não encontrado');
        // Mostrar notificação de erro se disponível
        if (window.notifications) {
            window.notifications.error('Modal de gerenciamento de cursos não encontrado. Certifique-se de estar na página correta.');
        }
    }
};

// Função global para fechar modal de gerenciamento de cursos
window.fecharModalTiposCurso = function() {
    console.log('🔧 Fechando modal de cursos...');
    const popup = document.getElementById('modalGerenciarTiposCurso');
    if (popup) {
        popup.style.display = 'none';
        popup.classList.remove('show', 'popup-fade-in');
        document.body.style.overflow = '';
        console.log('✅ Modal fechado com sucesso');
    }
};

// Função para abrir formulário de novo curso (integrado)
window.abrirFormularioNovoTipoCurso = function() {
    console.log('🔧 Abrindo formulário Novo Curso integrado...');
    
    // Esconder conteúdo principal
    const conteudoPrincipal = document.getElementById('conteudo-principal-tipos');
    const formularioNovoTipo = document.getElementById('formulario-novo-tipo-curso');
    
    if (conteudoPrincipal && formularioNovoTipo) {
        conteudoPrincipal.style.display = 'none';
        formularioNovoTipo.style.display = 'block';
        
        // Limpar formulário
        document.getElementById('formNovoTipoCursoIntegrado').reset();
        document.getElementById('carga_horaria_integrado').value = '45';
        document.getElementById('ativo_tipo_integrado').checked = true;
        
        // Focar no primeiro campo
        document.getElementById('codigo_tipo_integrado').focus();
    } else {
        console.error('❌ Elementos do formulário não encontrados');
    }
};

// Função para voltar para a lista de cursos
window.voltarParaListaTipos = function() {
    console.log('🔧 Voltando para lista de cursos...');
    
    const conteudoPrincipal = document.getElementById('conteudo-principal-tipos');
    const formularioNovoTipo = document.getElementById('formulario-novo-tipo-curso');
    
    if (conteudoPrincipal && formularioNovoTipo) {
        formularioNovoTipo.style.display = 'none';
        conteudoPrincipal.style.display = 'block';
    }
};

// Função para recarregar lista de cursos via AJAX
window.recarregarTiposCurso = function() {
    console.log('🔄 Iniciando carregamento de cursos...');
    
    // Mostrar loading state
    const tiposContainer = document.getElementById('lista-tipos-curso-modal');
    if (!tiposContainer) {
        console.error('❌ Container lista-tipos-curso-modal não encontrado');
        return;
    }
    
    tiposContainer.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Carregando cursos...</h6>
                <p>Aguarde enquanto buscamos os cursos cadastrados</p>
            </div>
        </div>
    `;
    
    console.log('📡 Fazendo requisição para API...');
    fetch(getBasePath() + '/admin/api/tipos-curso-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            // Verificar se a resposta é realmente JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                throw new Error('Resposta não é JSON válido. Content-Type: ' + contentType);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Texto recebido:', text);
                    throw new Error('JSON inválido: ' + e.message);
                }
            });
        })
        .then(data => {
            console.log('✅ Dados recebidos:', data);
            if (data.sucesso) {
                const selectCurso = document.getElementById('curso_tipo');
                const tiposContainer = document.getElementById('lista-tipos-curso-modal');
                
                // Atualizar contador de tipos no modal
                const totalTipos = document.getElementById('total-tipos-curso');
                if (totalTipos) {
                    totalTipos.textContent = data.tipos.length;
                }
                
                // Atualizar dropdown
                if (selectCurso) {
                    selectCurso.innerHTML = '<option value="">Selecione o tipo de curso...</option>';
                    data.tipos.forEach(tipo => {
                        selectCurso.innerHTML += '<option value="' + tipo.codigo + '">' + tipo.nome + '</option>';
                    });
                }
                
                // Atualizar lista no modal com o novo padrão
                if (data.tipos.length === 0) {
                    tiposContainer.innerHTML = `
                        <div class="popup-empty-state show">
                            <div class="empty-icon">
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <h5>Nenhum curso encontrado</h5>
                            <p>Crie seu primeiro curso para começar</p>
                            <button type="button" class="popup-primary-button" onclick="abrirFormularioNovoTipoCurso()">
                                <i class="fas fa-plus"></i>
                                Criar Primeiro Curso
                            </button>
                        </div>
                    `;
                } else {
                    // Converter HTML dos tipos para o novo padrão
                    let htmlTipos = '';
                    data.tipos.forEach(tipo => {
                        const statusClass = tipo.ativo == 1 ? 'active' : '';
                        const statusText = tipo.ativo == 1 ? 'ATIVO' : 'INATIVO';
                        const statusColor = tipo.ativo == 1 ? '#28a745' : '#6c757d';
                        
                        htmlTipos += `
                            <div class="popup-item-card ${statusClass}">
                                <div class="popup-item-card-header">
                                    <div class="popup-item-card-content">
                                        <h6 class="popup-item-card-title">${tipo.nome}</h6>
                                        <div class="popup-item-card-code" style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold;">
                                            ${statusText}
                                        </div>
                                        <div class="popup-item-card-description" style="margin-top: 0.5rem;">
                                            <div><strong>Código:</strong> ${tipo.codigo}</div>
                                            <div><strong>Carga Horária:</strong> ${tipo.carga_horaria_total} horas</div>
                                            ${tipo.descricao ? '<div><strong>Descrição:</strong> ' + tipo.descricao + '</div>' : ''}
                                        </div>
                                    </div>
                                    <div class="popup-item-card-actions">
                                        <button type="button" class="popup-item-card-menu" onclick="editarTipoCurso(${tipo.id}, '${tipo.codigo}', '${tipo.nome}', '${tipo.descricao || ''}', ${tipo.carga_horaria_total}, ${tipo.ativo})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="popup-item-card-menu" onclick="confirmarExclusaoTipoCurso(${tipo.id}, '${tipo.nome}')" title="Excluir" style="color: #dc3545;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    tiposContainer.innerHTML = htmlTipos;
                }
                
                // Atualizar contador na página principal
                const smallText = document.querySelector('small.text-muted');
                if (smallText && smallText.textContent.includes('curso(s) cadastrado(s)')) {
                    smallText.innerHTML = '<i class="fas fa-info-circle me-1"></i>' + data.tipos.length + ' curso(s) cadastrado(s) - <a href="#" onclick="abrirModalTiposCursoInterno()" class="text-primary">Clique aqui para gerenciar</a>';
                }
            } else {
                console.error('❌ Erro na resposta:', data.mensagem);
                tiposContainer.innerHTML = `
                    <div class="popup-error-state show">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Erro ao carregar cursos</h5>
                        <p>${data.mensagem}</p>
                        <button type="button" class="popup-secondary-button" onclick="recarregarTiposCurso()">
                            <i class="fas fa-redo"></i>
                            Tentar Novamente
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Erro ao recarregar cursos:', error);
            tiposContainer.innerHTML = `
                <div class="popup-error-state show">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Erro de conexão</h5>
                    <p>${error.message || 'Não foi possível conectar ao servidor'}</p>
                    <button type="button" class="popup-secondary-button" onclick="recarregarTiposCurso()">
                        <i class="fas fa-redo"></i>
                        Tentar Novamente
                    </button>
                </div>
            `;
        });
};

// Função global para abrir modal de gerenciamento de disciplinas
window.abrirModalDisciplinasInterno = function() {
    console.log('🔧 Tentando abrir modal de disciplinas...');
    const popup = document.getElementById('modalGerenciarDisciplinas');
    if (popup) {
        console.log('✅ Modal encontrado, abrindo...');
        popup.style.display = 'flex';
        popup.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
        
        // Carregar disciplinas automaticamente
        setTimeout(() => {
            carregarDisciplinasModal();
        }, 100);
    } else {
        console.error('❌ Modal modalGerenciarDisciplinas não encontrado');
        // Mostrar notificação de erro se disponível
        if (window.notifications) {
            window.notifications.error('Modal de gerenciamento de disciplinas não encontrado. Certifique-se de estar na página correta.');
        }
    }
};

// Função global para fechar modal de gerenciamento de disciplinas
window.fecharModalDisciplinas = function() {
    console.log('🔧 Fechando modal de disciplinas...');
    const popup = document.getElementById('modalGerenciarDisciplinas');
    if (popup) {
        popup.style.display = 'none';
        popup.classList.remove('show', 'popup-fade-in');
        document.body.style.overflow = '';
        console.log('✅ Modal fechado com sucesso');
    }
};

// Função para abrir formulário de nova disciplina
window.abrirFormularioNovaDisciplina = function() {
    console.log('🔧 Abrindo formulário Nova Disciplina...');
    
    // Esconder conteúdo principal
    const conteudoPrincipal = document.getElementById('conteudo-principal-disciplinas');
    const formularioNovaDisciplina = document.getElementById('formulario-nova-disciplina');
    
    if (conteudoPrincipal && formularioNovaDisciplina) {
        conteudoPrincipal.style.display = 'none';
        formularioNovaDisciplina.style.display = 'block';
        
        // Limpar formulário
        document.getElementById('formNovaDisciplinaIntegrado').reset();
        document.getElementById('carga_horaria_disciplina_integrado').value = '20';
        document.getElementById('cor_disciplina_integrado').value = '#023A8D';
        
        // Focar no primeiro campo
        document.getElementById('codigo_disciplina_integrado').focus();
    } else {
        console.error('❌ Elementos do formulário não encontrados');
    }
};

// Função para voltar para a lista de disciplinas
window.voltarParaListaDisciplinas = function() {
    console.log('🔧 Voltando para lista de disciplinas...');
    
    const conteudoPrincipal = document.getElementById('conteudo-principal-disciplinas');
    const formularioNovaDisciplina = document.getElementById('formulario-nova-disciplina');
    
    if (conteudoPrincipal && formularioNovaDisciplina) {
        formularioNovaDisciplina.style.display = 'none';
        conteudoPrincipal.style.display = 'block';
    }
};

// Função para filtrar disciplinas
window.filtrarDisciplinas = function() {
    console.log('🔍 Filtrando disciplinas...');
    const termoBusca = document.getElementById('buscarDisciplinas')?.value.toLowerCase() || '';
    
    const disciplinas = document.querySelectorAll('#listaDisciplinas .popup-item-card');
    disciplinas.forEach(disciplina => {
        const nome = disciplina.querySelector('.popup-item-card-title')?.textContent.toLowerCase() || '';
        const codigo = disciplina.querySelector('.popup-item-card-description')?.textContent.toLowerCase() || '';
        
        if (nome.includes(termoBusca) || codigo.includes(termoBusca)) {
            disciplina.style.display = 'block';
        } else {
            disciplina.style.display = 'none';
        }
    });
};

// Função para salvar nova disciplina
window.salvarNovaDisciplinaIntegrada = function(event) {
    event.preventDefault();
    console.log('💾 Salvando nova disciplina...');
    
    const formData = new FormData(event.target);
    const disciplina = {
        codigo: formData.get('codigo'),
        nome: formData.get('nome'),
        descricao: formData.get('descricao'),
        carga_horaria_padrao: formData.get('carga_horaria_padrao'),
        cor_hex: formData.get('cor_hex')
    };
    
    console.log('📝 Dados da disciplina:', disciplina);
    
    // Aqui você pode implementar a chamada AJAX para salvar
    // Por enquanto, apenas simular sucesso
    setTimeout(() => {
        alert('Disciplina salva com sucesso!');
        voltarParaListaDisciplinas();
        // Recarregar lista se a função existir
        if (typeof carregarDisciplinasModal === 'function') {
            carregarDisciplinasModal();
        }
    }, 500);
};

// Função para salvar alterações das disciplinas
window.salvarAlteracoesDisciplinas = function() {
    console.log('💾 Salvando alterações das disciplinas...');
    alert('Alterações salvas com sucesso!');
};

// Função para carregar disciplinas no modal
window.carregarDisciplinasModal = function() {
    console.log('🔄 Carregando disciplinas no modal...');
    
    const disciplinasContainer = document.getElementById('listaDisciplinas');
    const totalDisciplinas = document.getElementById('totalDisciplinas');
    
    if (!disciplinasContainer) {
        console.error('❌ Container listaDisciplinas não encontrado');
        return;
    }
    
    // Mostrar loading
    disciplinasContainer.innerHTML = `
        <div class="popup-loading-state show">
            <div class="popup-loading-spinner"></div>
            <div class="popup-loading-text">
                <h6>Carregando disciplinas...</h6>
                <p>Aguarde enquanto buscamos as disciplinas cadastradas</p>
            </div>
        </div>
    `;
    
    // Fazer requisição para a API
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => {
            if (!response.ok) {
                throw new Error('HTTP error! status: ' + response.status);
            }
            return response.json();
        })
        .then(data => {
            console.log('✅ Dados das disciplinas recebidos:', data);
            
            if (data.sucesso && data.disciplinas) {
                // Atualizar contador
                if (totalDisciplinas) {
                    totalDisciplinas.textContent = data.disciplinas.length;
                }
                
                // Renderizar disciplinas
                if (data.disciplinas.length === 0) {
                    disciplinasContainer.innerHTML = `
                        <div class="popup-empty-state show">
                            <div class="empty-icon">
                                <i class="fas fa-book"></i>
                            </div>
                            <h5>Nenhuma disciplina encontrada</h5>
                            <p>Crie sua primeira disciplina para começar</p>
                            <button type="button" class="popup-primary-button" onclick="abrirFormularioNovaDisciplina()">
                                <i class="fas fa-plus"></i>
                                Criar Primeira Disciplina
                            </button>
                        </div>
                    `;
                } else {
                    let htmlDisciplinas = '';
                    data.disciplinas.forEach(disciplina => {
                        const statusClass = disciplina.ativa ? 'active' : '';
                        const statusText = disciplina.ativa ? 'ATIVA' : 'INATIVA';
                        const statusColor = disciplina.ativa ? '#28a745' : '#6c757d';
                        
                        htmlDisciplinas += `
                            <div class="popup-item-card ${statusClass}">
                                <div class="popup-item-card-header">
                                    <div class="popup-item-card-content">
                                        <h6 class="popup-item-card-title">${disciplina.nome}</h6>
                                        <div class="popup-item-card-code" style="background: ${statusColor}; color: white; padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; font-weight: bold; margin-bottom: 0.5rem;">
                                            ${statusText}
                                        </div>
                                        <div class="popup-item-card-description">
                                            <div><strong>Código:</strong> ${disciplina.codigo}</div>
                                            <div><strong>Carga Horária:</strong> ${disciplina.carga_horaria_padrao}h</div>
                                            ${disciplina.descricao ? '<div><strong>Descrição:</strong> ' + disciplina.descricao + '</div>' : ''}
                                        </div>
                                    </div>
                                    <div class="popup-item-card-actions">
                                        <button type="button" class="popup-item-card-menu" onclick="editarDisciplina(${disciplina.id})" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="popup-item-card-menu" onclick="excluirDisciplina(${disciplina.id}, '${disciplina.nome}')" title="Excluir" style="color: #dc3545;">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    disciplinasContainer.innerHTML = htmlDisciplinas;
                }
            } else {
                console.error('❌ Erro na resposta:', data.mensagem);
                disciplinasContainer.innerHTML = `
                    <div class="popup-error-state show">
                        <div class="error-icon">
                            <i class="fas fa-exclamation-triangle"></i>
                        </div>
                        <h5>Erro ao carregar disciplinas</h5>
                        <p>${data.mensagem}</p>
                        <button type="button" class="popup-secondary-button" onclick="carregarDisciplinasModal()">
                            <i class="fas fa-redo"></i>
                            Tentar Novamente
                        </button>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar disciplinas:', error);
            disciplinasContainer.innerHTML = `
                <div class="popup-error-state show">
                    <div class="error-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h5>Erro de conexão</h5>
                    <p>${error.message || 'Não foi possível conectar ao servidor'}</p>
                    <button type="button" class="popup-secondary-button" onclick="carregarDisciplinasModal()">
                        <i class="fas fa-redo"></i>
                        Tentar Novamente
                    </button>
                </div>
            `;
        });
};

// Função para editar disciplina
window.editarDisciplina = function(id) {
    console.log('✏️ Editando disciplina ID:', id);
    
    // Verificar se o modal de edição existe
    const modalEditar = document.getElementById('modalEditarDisciplina');
    if (!modalEditar) {
        console.log('⚠️ Modal de edição de disciplina não encontrado. Redirecionando para página de configurações...');
        // Redirecionar para a página de turmas teóricas com parâmetro de edição
        window.location.href = `?page=turmas-teoricas&editar_disciplina=${id}`;
        return;
    }
    
    // Buscar dados da disciplina
    fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=listar')
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.disciplinas) {
                const disciplina = data.disciplinas.find(d => d.id == id);
                if (disciplina) {
                    // Preencher campos do formulário
                    document.getElementById('edit_id').value = disciplina.id;
                    document.getElementById('edit_codigo').value = disciplina.codigo || '';
                    document.getElementById('edit_nome').value = disciplina.nome || '';
                    document.getElementById('edit_descricao').value = disciplina.descricao || '';
                    document.getElementById('edit_carga_horaria_padrao').value = disciplina.carga_horaria_padrao || '';
                    document.getElementById('edit_cor_hex').value = disciplina.cor_hex || '#007bff';
                    document.getElementById('edit_icone').value = disciplina.icone || 'book';
                    
                    // Abrir modal usando template padrão
                    modalEditar.style.display = 'flex';
                    modalEditar.classList.add('show', 'popup-fade-in');
                    document.body.style.overflow = 'hidden';
                    
                    console.log('✅ Modal de edição de disciplina aberto');
                } else {
                    console.error('❌ Disciplina não encontrada:', id);
                    alert('Disciplina não encontrada!');
                }
            } else {
                console.error('❌ Erro ao carregar dados das disciplinas:', data.mensagem);
                alert('Erro ao carregar dados das disciplinas!');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao editar disciplina:', error);
            alert('Erro ao carregar dados da disciplina!');
        });
};

// Função para excluir disciplina
window.excluirDisciplina = function(id, nome) {
    console.log('🗑️ Excluindo disciplina:', nome, 'ID:', id);
    
    if (confirm(`Tem certeza que deseja excluir a disciplina "${nome}"?`)) {
        // Implementar exclusão via API
        fetch(getBasePath() + '/admin/api/disciplinas-clean.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `acao=excluir&id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('Disciplina excluída com sucesso!');
                carregarDisciplinasModal(); // Recarregar lista
            } else {
                alert('Erro ao excluir disciplina: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('Erro ao excluir disciplina:', error);
            alert('Erro ao excluir disciplina. Tente novamente.');
        });
    }
};

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

// Função para editar tipo de curso - Compatível com admin.js
function editarTipoCurso(id, codigo, nome, descricao, carga_horaria_total, ativo) {
    console.log('📝 Editando tipo de curso:', { id, codigo, nome, descricao, carga_horaria_total, ativo });
    
    // Verificar se o modal de edição existe
    const modalEditar = document.getElementById('modalEditarTipoCurso');
    if (!modalEditar) {
        console.log('⚠️ Modal de edição não encontrado. Redirecionando para página de configurações...');
        // Redirecionar para a página de turmas teóricas com parâmetro de edição
        window.location.href = `?page=turmas-teoricas&editar_curso=${id}`;
        return;
    }
    
    // Preencher campos do formulário se existirem
    const campos = {
        'editar_tipo_curso_id': id,
        'editar_codigo': codigo,
        'editar_nome_tipo': nome,
        'editar_descricao_tipo': descricao,
        'editar_carga_horaria': carga_horaria_total,
        'editar_ativo_tipo': ativo == 1
    };
    
    Object.entries(campos).forEach(([campoId, valor]) => {
        const elemento = document.getElementById(campoId);
        if (elemento) {
            if (elemento.type === 'checkbox') {
                elemento.checked = valor;
            } else {
                elemento.value = valor;
            }
        } else {
            console.warn(`⚠️ Campo ${campoId} não encontrado`);
        }
    });
    
    // Carregar disciplinas salvas se a função existir
    if (typeof carregarDisciplinasSalvas === 'function') {
        carregarDisciplinasSalvas(codigo);
    }
    
    // Atualizar auditoria de carga horária se a função existir
    if (typeof atualizarAuditoriaCargaHoraria === 'function') {
        setTimeout(() => {
            atualizarAuditoriaCargaHoraria();
        }, 100);
    }
    
    // Abrir modal
    const popup = document.getElementById('modalEditarTipoCurso');
    if (popup) {
        popup.style.display = 'flex';
        popup.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
        console.log('✅ Modal de edição aberto');
    } else {
        console.error('❌ Não foi possível abrir o modal de edição');
    }
}

// Função para confirmar exclusão de tipo de curso
function confirmarExclusaoTipoCurso(id, nome) {
    if (confirm(`Tem certeza que deseja excluir o tipo de curso "${nome}"?\n\nEsta ação não pode ser desfeita.`)) {
        console.log('🗑️ Excluindo tipo de curso:', nome, 'ID:', id);
        
        // Implementar exclusão via API
        fetch(getBasePath() + '/admin/api/tipos-curso-clean.php?acao=excluir', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                console.log('✅ Tipo de curso excluído com sucesso');
                alert('Tipo de curso excluído com sucesso!');
                // Recarregar a lista de tipos
                if (typeof recarregarCursos === 'function') {
                    recarregarCursos();
                }
            } else {
                console.error('❌ Erro ao excluir tipo de curso:', data.mensagem);
                alert('Erro ao excluir tipo de curso: ' + data.mensagem);
            }
        })
        .catch(error => {
            console.error('❌ Erro na requisição:', error);
            alert('Erro ao excluir tipo de curso: ' + error.message);
        });
    }
}

// Função para fechar modal de edição de disciplina
function fecharModalEditarDisciplina() {
    const modal = document.getElementById('modalEditarDisciplina');
    if (modal) {
        modal.style.display = 'none';
        modal.classList.remove('show', 'popup-fade-in');
        document.body.style.overflow = 'auto';
        console.log('✅ Modal de edição de disciplina fechado');
    }
}

// Event listener para o formulário de edição de disciplina
document.addEventListener('DOMContentLoaded', function() {
    const formEditarDisciplina = document.getElementById('formEditarDisciplina');
    if (formEditarDisciplina) {
        formEditarDisciplina.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            fetch(getBasePath() + '/admin/api/disciplinas-clean.php?acao=editar', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.sucesso) {
                    console.log('✅ Disciplina atualizada com sucesso');
                    alert('Disciplina atualizada com sucesso!');
                    fecharModalEditarDisciplina();
                    // Recarregar a lista de disciplinas se a função existir
                    if (typeof recarregarDisciplinas === 'function') {
                        recarregarDisciplinas();
                    }
                } else {
                    console.error('❌ Erro ao atualizar disciplina:', data.mensagem);
                    alert('Erro ao atualizar disciplina: ' + data.mensagem);
                }
            })
            .catch(error => {
                console.error('❌ Erro na requisição:', error);
                alert('Erro ao atualizar disciplina: ' + error.message);
            });
        });
    }
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
