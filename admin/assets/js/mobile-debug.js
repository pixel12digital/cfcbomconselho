/* =====================================================
   MOBILE DEBUG SCRIPT - SISTEMA CFC BOM CONSELHO
   Script para debug de problemas CSS em mobile
   ===================================================== */

(function() {
    'use strict';
    
    // Detectar se é mobile
    function isMobile() {
        return window.innerWidth <= 768 || /Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
    }
    
    // Debug CSS loading
    function debugCSSLoading() {
        if (!isMobile()) return;
        
        console.log('🔍 MOBILE DEBUG: Iniciando verificação de CSS...');
        
        // Verificar CSS carregado
        const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
        console.log('📄 CSS encontrados:', stylesheets.length);
        
        stylesheets.forEach((link, index) => {
            console.log(`CSS ${index + 1}:`, {
                href: link.href,
                loaded: link.sheet ? '✅ Carregado' : '❌ Não carregado',
                status: link.sheet ? 'OK' : 'Não carregado'
            });
        });
        
        // Verificar variáveis CSS
        const rootStyles = getComputedStyle(document.documentElement);
        const primaryColor = rootStyles.getPropertyValue('--primary-color');
        console.log('🎨 Variável --primary-color:', primaryColor || '❌ Não definida');
        
        // Verificar elementos principais
        const adminContainer = document.querySelector('.admin-container');
        const adminHeader = document.querySelector('.admin-header');
        const adminSidebar = document.querySelector('.admin-sidebar');
        
        console.log('🏗️ Elementos principais:', {
            adminContainer: adminContainer ? '✅ Encontrado' : '❌ Não encontrado',
            adminHeader: adminHeader ? '✅ Encontrado' : '❌ Não encontrado',
            adminSidebar: adminSidebar ? '✅ Encontrado' : '❌ Não encontrado'
        });
        
        // Verificar CSP
        const metaCSP = document.querySelector('meta[http-equiv="Content-Security-Policy"]');
        if (metaCSP) {
            console.log('🛡️ CSP Meta tag encontrada:', metaCSP.content);
        }
        
        // Verificar erros de console
        const originalError = console.error;
        console.error = function(...args) {
            if (args[0] && typeof args[0] === 'string' && args[0].includes('CSS')) {
                console.log('🚨 ERRO CSS detectado:', args);
            }
            originalError.apply(console, args);
        };
    }
    
    // Adicionar indicador visual de debug
    function addDebugIndicator() {
        if (!isMobile()) return;
        
        const debugDiv = document.createElement('div');
        debugDiv.className = 'mobile-debug';
        debugDiv.innerHTML = `
            <div>📱 Mobile Debug</div>
            <div>Largura: ${window.innerWidth}px</div>
            <div>CSS: ${document.querySelectorAll('link[rel="stylesheet"]').length}</div>
            <div>User Agent: ${navigator.userAgent.includes('Mobile') ? 'Mobile' : 'Desktop'}</div>
        `;
        document.body.appendChild(debugDiv);
        
        // Remover após 10 segundos
        setTimeout(() => {
            if (debugDiv.parentNode) {
                debugDiv.parentNode.removeChild(debugDiv);
            }
        }, 10000);
    }
    
    // Verificar conectividade
    function checkConnectivity() {
        if (!isMobile()) return;
        
        const connection = navigator.connection || navigator.mozConnection || navigator.webkitConnection;
        if (connection) {
            console.log('🌐 Conectividade:', {
                effectiveType: connection.effectiveType,
                downlink: connection.downlink,
                rtt: connection.rtt,
                saveData: connection.saveData
            });
        }
    }
    
    // Forçar reload de CSS em caso de problema
    function forceCSSReload() {
        if (!isMobile()) return;
        
        const stylesheets = document.querySelectorAll('link[rel="stylesheet"]');
        let reloadCount = 0;
        
        stylesheets.forEach(link => {
            try {
                // Apenas verificar se o CSS foi carregado, sem acessar cssRules
                if (!link.sheet) {
                    console.log('🔄 Recarregando CSS não carregado:', link.href);
                    const newLink = link.cloneNode(true);
                    newLink.href = link.href + '?v=' + Date.now();
                    link.parentNode.replaceChild(newLink, link);
                    reloadCount++;
                }
            } catch (e) {
                // Ignorar erros gerais
                console.log('⚠️ Erro ao verificar CSS:', link.href);
            }
        });
        
        if (reloadCount > 0) {
            console.log(`🔄 ${reloadCount} CSS recarregados`);
        }
    }
    
    // Executar debug quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                debugCSSLoading();
                addDebugIndicator();
                checkConnectivity();
                
                // Verificar novamente após 3 segundos
                setTimeout(() => {
                    forceCSSReload();
                }, 3000);
            }, 1000);
        });
    } else {
        setTimeout(() => {
            debugCSSLoading();
            addDebugIndicator();
            checkConnectivity();
            forceCSSReload();
        }, 1000);
    }
    
    // Expor funções globalmente para debug manual
    window.mobileDebug = {
        debugCSS: debugCSSLoading,
        reloadCSS: forceCSSReload,
        checkConnectivity: checkConnectivity,
        isMobile: isMobile
    };
    
})();
