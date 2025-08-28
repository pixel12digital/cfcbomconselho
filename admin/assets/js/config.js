// Configuração centralizada para URLs das APIs
const API_CONFIG = {
    // URLs base das APIs
    BASE_URL: window.location.origin + window.location.pathname.replace(/\/[^\/]*$/, ''),
    
    // Endpoints das APIs
    ENDPOINTS: {
        INSTRUTORES: 'admin/api/instrutores.php',
        USUARIOS: 'admin/api/usuarios.php',
        CFCs: 'admin/api/cfcs.php',
        ALUNOS: 'admin/api/alunos.php',
        VEICULOS: 'admin/api/veiculos.php',
        AGENDAMENTO: 'admin/api/agendamento.php',
        HISTORICO: 'admin/api/historico.php'
    },
    
    // Função para obter URL completa da API
    getApiUrl: function(endpoint) {
        return this.BASE_URL + '/' + this.ENDPOINTS[endpoint];
    },
    
    // Função para obter URL relativa da API
    getRelativeApiUrl: function(endpoint) {
        return this.ENDPOINTS[endpoint];
    }
};

// Log da configuração para debug
console.log('🔧 Configuração de API carregada:', API_CONFIG);
console.log('🌐 Base URL:', API_CONFIG.BASE_URL);
