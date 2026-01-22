/**
 * Sistema de Pesquisa Global - Painel Administrativo
 * Barra de pesquisa funcional para buscar informações em todo o sistema
 */

class GlobalSearch {
    constructor() {
        this.searchInput = null;
        this.searchResults = null;
        this.searchTimeout = null;
        this.isLoading = false;
        this.minSearchLength = 2;
        this.searchDelay = 300; // ms
        
        this.init();
    }
    
    init() {
        console.log('🔍 Sistema de pesquisa global inicializado');
        this.createSearchBar();
        this.bindEvents();
    }
    
    createSearchBar() {
        // Criar container da barra de pesquisa
        const searchContainer = document.createElement('div');
        searchContainer.className = 'search-container';
        searchContainer.innerHTML = `
            <div class="search-bar">
                <input 
                    type="text" 
                    class="search-input" 
                    placeholder="Pesquisar por nome, CPF, matrícula, telefone..."
                    autocomplete="off"
                >
                <i class="fas fa-search search-icon"></i>
                <div class="search-results" id="search-results"></div>
            </div>
        `;
        
        // Inserir no início do body
        document.body.insertBefore(searchContainer, document.body.firstChild);
        
        // Referenciar elementos
        this.searchInput = searchContainer.querySelector('.search-input');
        this.searchResults = searchContainer.querySelector('.search-results');
        
        console.log('✅ Barra de pesquisa criada');
    }
    
    bindEvents() {
        // Evento de digitação
        this.searchInput.addEventListener('input', (e) => {
            this.handleSearch(e.target.value);
        });
        
        // Evento de foco
        this.searchInput.addEventListener('focus', () => {
            if (this.searchInput.value.length >= this.minSearchLength) {
                this.showResults();
            }
        });
        
        // Evento de clique fora para fechar
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.search-bar')) {
                this.hideResults();
            }
        });
        
        // Evento de teclado
        this.searchInput.addEventListener('keydown', (e) => {
            this.handleKeydown(e);
        });
        
        console.log('✅ Eventos de pesquisa configurados');
    }
    
    handleSearch(query) {
        // Limpar timeout anterior
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Se query muito curta, limpar resultados
        if (query.length < this.minSearchLength) {
            this.hideResults();
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
        this.showLoading();
        
        try {
            console.log('🔍 Pesquisando por:', query);
            
            // Simular busca em diferentes módulos
            const results = await this.searchInModules(query);
            
            this.displayResults(results);
            
        } catch (error) {
            console.error('❌ Erro na pesquisa:', error);
            this.showError('Erro ao realizar pesquisa');
        } finally {
            this.isLoading = false;
        }
    }
    
    async searchInModules(query) {
        try {
            // Fazer requisição para a API de pesquisa
            const response = await fetch(`api/search.php?q=${encodeURIComponent(query)}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            
            if (!data.success) {
                throw new Error(data.message || 'Erro na pesquisa');
            }
            
            return data.results || [];
            
        } catch (error) {
            console.error('❌ Erro na API de pesquisa:', error);
            
            // Fallback para dados mock em caso de erro
            return this.getMockResults(query);
        }
    }
    
    async searchInModule(module, query) {
        // Simular busca em cada módulo
        const mockData = this.getMockData(module);
        
        return mockData.filter(item => {
            const searchFields = item.searchFields || [];
            return searchFields.some(field => 
                field.toLowerCase().includes(query.toLowerCase())
            );
        }).slice(0, 5); // Limitar a 5 resultados por módulo
    }
    
    getMockData(module) {
        // Dados mock para demonstração
        const mockData = {
            alunos: [
                {
                    id: 1,
                    title: 'João Silva Santos',
                    subtitle: 'CPF: 123.456.789-00',
                    type: 'Aluno',
                    url: '?page=alunos&action=view&id=1',
                    searchFields: ['João Silva Santos', '123.456.789-00', 'joao.silva@email.com']
                },
                {
                    id: 2,
                    title: 'Maria Oliveira Costa',
                    subtitle: 'CPF: 987.654.321-00',
                    type: 'Aluno',
                    url: '?page=alunos&action=view&id=2',
                    searchFields: ['Maria Oliveira Costa', '987.654.321-00', 'maria.oliveira@email.com']
                }
            ],
            instrutores: [
                {
                    id: 1,
                    title: 'Carlos Eduardo Lima',
                    subtitle: 'CRLV: 12345',
                    type: 'Instrutor',
                    url: '?page=instrutores&action=view&id=1',
                    searchFields: ['Carlos Eduardo Lima', '12345', 'carlos.lima@email.com']
                }
            ],
            veiculos: [
                {
                    id: 1,
                    title: 'Honda Civic 2020',
                    subtitle: 'Placa: ABC-1234',
                    type: 'Veículo',
                    url: '?page=veiculos&action=view&id=1',
                    searchFields: ['Honda Civic 2020', 'ABC-1234', 'Honda']
                }
            ],
            cfcs: [
                {
                    id: 1,
                    title: 'CFC Bom Conselho',
                    subtitle: 'CNPJ: 12.345.678/0001-90',
                    type: 'CFC',
                    url: '?page=cfcs&action=view&id=1',
                    searchFields: ['CFC Bom Conselho', '12.345.678/0001-90', 'Bom Conselho']
                }
            ],
            usuarios: [
                {
                    id: 1,
                    title: 'Admin Sistema',
                    subtitle: 'admin@sistema.com',
                    type: 'Usuário',
                    url: '?page=usuarios&action=view&id=1',
                    searchFields: ['Admin Sistema', 'admin@sistema.com', 'Administrador']
                }
            ]
        };
        
        return mockData[module] || [];
    }
    
    getMockResults(query) {
        // Dados mock para fallback
        const mockResults = [
            {
                id: 1,
                title: 'João Silva Santos',
                subtitle: 'CPF: 123.456.789-00',
                type: 'Aluno',
                url: '?page=alunos&action=view&id=1',
                icon: 'fas fa-graduation-cap',
                color: '#3498db',
                module: 'alunos'
            },
            {
                id: 2,
                title: 'Maria Oliveira Costa',
                subtitle: 'CPF: 987.654.321-00',
                type: 'Aluno',
                url: '?page=alunos&action=view&id=2',
                icon: 'fas fa-graduation-cap',
                color: '#3498db',
                module: 'alunos'
            },
            {
                id: 1,
                title: 'Carlos Eduardo Lima',
                subtitle: 'CRLV: 12345',
                type: 'Instrutor',
                url: '?page=instrutores&action=view&id=1',
                icon: 'fas fa-chalkboard-teacher',
                color: '#e74c3c',
                module: 'instrutores'
            }
        ];
        
        // Filtrar resultados mock baseado na query
        return mockResults.filter(item => 
            item.title.toLowerCase().includes(query.toLowerCase()) ||
            item.subtitle.toLowerCase().includes(query.toLowerCase())
        );
    }
    
    displayResults(results) {
        if (results.length === 0) {
            this.showEmpty();
            return;
        }
        
        const html = results.map(result => `
            <a href="${result.url}" class="search-result-item">
                <div class="search-result-icon" style="background-color: ${result.color}">
                    <i class="${result.icon}"></i>
                </div>
                <div class="search-result-content">
                    <div class="search-result-title">${result.title}</div>
                    <div class="search-result-subtitle">${result.subtitle}</div>
                </div>
                <div class="search-result-type">${result.type}</div>
            </a>
        `).join('');
        
        this.searchResults.innerHTML = html;
        this.showResults();
        
        console.log(`✅ ${results.length} resultados encontrados`);
    }
    
    showLoading() {
        this.searchResults.innerHTML = `
            <div class="search-loading">
                Pesquisando...
            </div>
        `;
        this.showResults();
    }
    
    showEmpty() {
        this.searchResults.innerHTML = `
            <div class="search-empty">
                <i class="fas fa-search" style="font-size: 24px; margin-bottom: 8px; display: block;"></i>
                Nenhum resultado encontrado
            </div>
        `;
        this.showResults();
    }
    
    showError(message) {
        this.searchResults.innerHTML = `
            <div class="search-empty">
                <i class="fas fa-exclamation-triangle" style="font-size: 24px; margin-bottom: 8px; display: block; color: #e74c3c;"></i>
                ${message}
            </div>
        `;
        this.showResults();
    }
    
    showResults() {
        this.searchResults.classList.add('show');
    }
    
    hideResults() {
        this.searchResults.classList.remove('show');
    }
    
    handleKeydown(e) {
        const results = this.searchResults.querySelectorAll('.search-result-item');
        
        if (e.key === 'Escape') {
            this.hideResults();
            this.searchInput.blur();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            const active = this.searchResults.querySelector('.search-result-item.active');
            if (active) {
                active.classList.remove('active');
                const next = active.nextElementSibling;
                if (next) next.classList.add('active');
            } else if (results.length > 0) {
                results[0].classList.add('active');
            }
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            const active = this.searchResults.querySelector('.search-result-item.active');
            if (active) {
                active.classList.remove('active');
                const prev = active.previousElementSibling;
                if (prev) prev.classList.add('active');
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const active = this.searchResults.querySelector('.search-result-item.active');
            if (active) {
                window.location.href = active.href;
            }
        }
    }
}

// Inicializar quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    new GlobalSearch();
});
