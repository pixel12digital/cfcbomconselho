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

    // Função para obter URL da API (CORRIGIDA para ambos os ambientes)
    getRelativeApiUrl: function(endpoint) {
        if (this.isProduction) {
            // Produção: usar URL absoluta
            return window.location.origin + '/' + this.ENDPOINTS[endpoint];
        } else {
            // Desenvolvimento: usar URL relativa ao projeto
            const currentPath = window.location.pathname;
            
            // Extrair o caminho do projeto corretamente
            let projectPath;
            if (currentPath.includes('/admin/')) {
                // Se estamos em uma página admin, pegar o caminho até /admin/
                projectPath = currentPath.split('/admin/')[0];
            } else if (currentPath.includes('/cfc-bom-conselho/')) {
                // Se estamos em uma página do projeto, pegar o caminho do projeto
                projectPath = '/cfc-bom-conselho';
            } else {
                // Fallback: usar o caminho atual sem o arquivo
                projectPath = currentPath.substring(0, currentPath.lastIndexOf('/'));
            }
            
            return projectPath + '/' + this.ENDPOINTS[endpoint];
        }
    },

    // Função para obter URL completa da API (para casos específicos)
    getApiUrl: function(endpoint) {
        return this.getRelativeApiUrl(endpoint);
    }
};

// Log da configuração para debug
console.log('🔧 Configuração de API carregada:', API_CONFIG);
console.log('🌍 Ambiente detectado:', API_CONFIG.isProduction ? 'PRODUÇÃO' : 'DESENVOLVIMENTO');
console.log('✅ URLs das APIs corrigidas - usando URLs apropriadas para cada ambiente');
console.log('🎯 Exemplo: Instrutores =', API_CONFIG.getRelativeApiUrl('INSTRUTORES'));
console.log('🔍 Caminho atual:', window.location.pathname);
