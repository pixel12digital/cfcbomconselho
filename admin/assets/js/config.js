// Configuração centralizada para URLs das APIs - VERSÃO FINAL CORRIGIDA PARA PRODUÇÃO
const API_CONFIG = {
    // Detectar ambiente automaticamente
    isProduction: window.location.hostname.includes('hostinger') || window.location.hostname.includes('hstgr.io'),
    
    // Endpoints das APIs - URLs absolutas para produção, relativas para desenvolvimento
    ENDPOINTS: {
        INSTRUTORES: 'admin/api/instrutores.php',
        USUARIOS: 'admin/api/usuarios.php',
        CFCs: 'admin/api/cfcs.php',
        ALUNOS: 'admin/api/alunos.php',
        VEICULOS: 'admin/api/veiculos.php',
        AGENDAMENTO: 'admin/api/agendamento.php',
        HISTORICO: 'admin/api/historico.php'
    },

    // Função para obter URL da API (CORRIGIDA para produção)
    getRelativeApiUrl: function(endpoint) {
        const baseUrl = this.isProduction ? window.location.origin : '';
        return baseUrl + '/' + this.ENDPOINTS[endpoint];
    },

    // Função para obter URL completa da API (para casos específicos)
    getApiUrl: function(endpoint) {
        return this.getRelativeApiUrl(endpoint);
    }
};

// Log da configuração para debug
console.log('🔧 Configuração de API carregada:', API_CONFIG);
console.log('🌍 Ambiente detectado:', API_CONFIG.isProduction ? 'PRODUÇÃO' : 'DESENVOLVIMENTO');
console.log('✅ URLs das APIs corrigidas - usando URLs absolutas em produção');
console.log('🎯 Exemplo: Instrutores =', API_CONFIG.getRelativeApiUrl('INSTRUTORES'));
