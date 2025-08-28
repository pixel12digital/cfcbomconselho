// Configuração centralizada para URLs das APIs - VERSÃO FINAL CORRIGIDA
const API_CONFIG = {
    // Endpoints das APIs - SEMPRE URLs relativas
    ENDPOINTS: {
        INSTRUTORES: 'admin/api/instrutores.php',
        USUARIOS: 'admin/api/usuarios.php',
        CFCs: 'admin/api/cfcs.php',
        ALUNOS: 'admin/api/alunos.php',
        VEICULOS: 'admin/api/veiculos.php',
        AGENDAMENTO: 'admin/api/agendamento.php',
        HISTORICO: 'admin/api/historico.php'
    },

    // Função para obter URL relativa da API (RECOMENDADA)
    getRelativeApiUrl: function(endpoint) {
        return this.ENDPOINTS[endpoint];
    },

    // Função para obter URL completa da API (para casos específicos)
    getApiUrl: function(endpoint) {
        // SEMPRE usar URL relativa para evitar problemas de contexto
        return this.getRelativeApiUrl(endpoint);
    }
};

// Log da configuração para debug
console.log('🔧 Configuração de API carregada:', API_CONFIG);
console.log('✅ URLs das APIs corrigidas - usando sempre URLs relativas');
console.log('🎯 Exemplo: Instrutores =', API_CONFIG.getRelativeApiUrl('INSTRUTORES'));
