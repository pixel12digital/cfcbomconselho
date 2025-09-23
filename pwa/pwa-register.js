/**
 * PWA Registration Script - Sistema CFC Bom Conselho
 * Registra Service Worker e gerencia atualizações
 */

class PWAManager {
    constructor() {
        this.registration = null;
        this.updateAvailable = false;
        this.deferredPrompt = null;
        this.init();
    }
    
    async init() {
        console.log('[PWA] Inicializando PWA Manager...');
        
        // Verificar suporte a Service Worker
        if (!('serviceWorker' in navigator)) {
            console.warn('[PWA] Service Worker não suportado');
            return;
        }
        
        // Registrar Service Worker
        await this.registerServiceWorker();
        
        // Configurar eventos de instalação
        this.setupInstallEvents();
        
        // Configurar eventos de atualização
        this.setupUpdateEvents();
        
        // Verificar se já está instalado
        this.checkInstallationStatus();
        
        console.log('[PWA] PWA Manager inicializado com sucesso');
    }
    
    async registerServiceWorker() {
        try {
            console.log('[PWA] Registrando Service Worker...');
            
            this.registration = await navigator.serviceWorker.register('/cfc-bom-conselho/pwa/sw.js', {
                scope: '/cfc-bom-conselho/pwa/'
            });
            
            console.log('[PWA] Service Worker registrado:', this.registration);
            
            // Verificar atualizações
            this.registration.addEventListener('updatefound', () => {
                console.log('[PWA] Nova versão do Service Worker encontrada');
                this.handleUpdateFound();
            });
            
        } catch (error) {
            console.error('[PWA] Erro ao registrar Service Worker:', error);
        }
    }
    
    setupInstallEvents() {
        // Evento beforeinstallprompt - para instalação no Android
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('[PWA] beforeinstallprompt disparado');
            e.preventDefault();
            this.deferredPrompt = e;
            this.showInstallBanner();
        });
        
        // Evento appinstalled - quando o app é instalado
        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App instalado com sucesso');
            this.hideInstallBanner();
            this.showInstallationSuccess();
        });
    }
    
    setupUpdateEvents() {
        // Verificar se há atualização disponível
        if (this.registration && this.registration.waiting) {
            this.updateAvailable = true;
            this.showUpdateBanner();
        }
        
        // Escutar mensagens do Service Worker
        navigator.serviceWorker.addEventListener('message', (event) => {
            if (event.data && event.data.type === 'UPDATE_AVAILABLE') {
                this.updateAvailable = true;
                this.showUpdateBanner();
            }
        });
    }
    
    handleUpdateFound() {
        const newWorker = this.registration.installing;
        
        newWorker.addEventListener('statechange', () => {
            if (newWorker.state === 'installed') {
                if (navigator.serviceWorker.controller) {
                    // Nova versão disponível
                    this.updateAvailable = true;
                    this.showUpdateBanner();
                } else {
                    // Primeira instalação
                    console.log('[PWA] Service Worker instalado pela primeira vez');
                }
            }
        });
    }
    
    showInstallBanner() {
        // Criar banner de instalação
        const banner = this.createBanner({
            type: 'install',
            title: 'Instalar App',
            message: 'Instale o CFC Bom Conselho para acesso rápido e funcionalidades offline.',
            buttonText: 'Instalar',
            buttonAction: () => this.installApp()
        });
        
        document.body.appendChild(banner);
    }
    
    showUpdateBanner() {
        // Criar banner de atualização
        const banner = this.createBanner({
            type: 'update',
            title: 'Nova Versão Disponível',
            message: 'Uma nova versão do sistema está disponível com melhorias e correções.',
            buttonText: 'Atualizar',
            buttonAction: () => this.updateApp()
        });
        
        document.body.appendChild(banner);
    }
    
    createBanner(options) {
        const banner = document.createElement('div');
        banner.className = `pwa-banner pwa-banner-${options.type}`;
        banner.innerHTML = `
            <div class="pwa-banner-content">
                <div class="pwa-banner-icon">
                    <i class="fas fa-${options.type === 'install' ? 'download' : 'sync-alt'}"></i>
                </div>
                <div class="pwa-banner-text">
                    <h4>${options.title}</h4>
                    <p>${options.message}</p>
                </div>
                <div class="pwa-banner-actions">
                    <button class="pwa-banner-btn pwa-banner-btn-primary" onclick="this.closest('.pwa-banner').remove()">
                        ${options.buttonText}
                    </button>
                    <button class="pwa-banner-btn pwa-banner-btn-secondary" onclick="this.closest('.pwa-banner').remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;
        
        // Adicionar estilos
        this.addBannerStyles();
        
        // Configurar ação do botão
        const button = banner.querySelector('.pwa-banner-btn-primary');
        button.addEventListener('click', options.buttonAction);
        
        return banner;
    }
    
    addBannerStyles() {
        if (document.getElementById('pwa-banner-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'pwa-banner-styles';
        styles.textContent = `
            .pwa-banner {
                position: fixed;
                bottom: 20px;
                left: 20px;
                right: 20px;
                max-width: 400px;
                background: #2c3e50;
                color: white;
                border-radius: 12px;
                box-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
                z-index: 10000;
                animation: slideUp 0.3s ease;
            }
            
            .pwa-banner-content {
                display: flex;
                align-items: center;
                padding: 16px;
                gap: 12px;
            }
            
            .pwa-banner-icon {
                font-size: 24px;
                color: #3498db;
                flex-shrink: 0;
            }
            
            .pwa-banner-text {
                flex: 1;
            }
            
            .pwa-banner-text h4 {
                margin: 0 0 4px 0;
                font-size: 16px;
                font-weight: 600;
            }
            
            .pwa-banner-text p {
                margin: 0;
                font-size: 14px;
                opacity: 0.9;
                line-height: 1.4;
            }
            
            .pwa-banner-actions {
                display: flex;
                gap: 8px;
                flex-shrink: 0;
            }
            
            .pwa-banner-btn {
                padding: 8px 16px;
                border: none;
                border-radius: 6px;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.2s ease;
            }
            
            .pwa-banner-btn-primary {
                background: #3498db;
                color: white;
            }
            
            .pwa-banner-btn-primary:hover {
                background: #2980b9;
            }
            
            .pwa-banner-btn-secondary {
                background: rgba(255, 255, 255, 0.2);
                color: white;
                padding: 8px;
                width: 32px;
                height: 32px;
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .pwa-banner-btn-secondary:hover {
                background: rgba(255, 255, 255, 0.3);
            }
            
            @keyframes slideUp {
                from {
                    transform: translateY(100%);
                    opacity: 0;
                }
                to {
                    transform: translateY(0);
                    opacity: 1;
                }
            }
            
            @media (max-width: 768px) {
                .pwa-banner {
                    left: 10px;
                    right: 10px;
                    bottom: 10px;
                }
                
                .pwa-banner-content {
                    padding: 12px;
                }
                
                .pwa-banner-text h4 {
                    font-size: 14px;
                }
                
                .pwa-banner-text p {
                    font-size: 12px;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    async installApp() {
        if (!this.deferredPrompt) {
            console.warn('[PWA] Deferred prompt não disponível');
            return;
        }
        
        try {
            console.log('[PWA] Iniciando instalação...');
            
            // Mostrar prompt de instalação
            this.deferredPrompt.prompt();
            
            // Aguardar resposta do usuário
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log('[PWA] Resultado da instalação:', outcome);
            
            if (outcome === 'accepted') {
                console.log('[PWA] Usuário aceitou a instalação');
            } else {
                console.log('[PWA] Usuário rejeitou a instalação');
            }
            
            // Limpar deferred prompt
            this.deferredPrompt = null;
            
        } catch (error) {
            console.error('[PWA] Erro durante instalação:', error);
        }
    }
    
    async updateApp() {
        if (!this.registration || !this.registration.waiting) {
            console.warn('[PWA] Nenhuma atualização disponível');
            return;
        }
        
        try {
            console.log('[PWA] Aplicando atualização...');
            
            // Enviar mensagem para o Service Worker
            this.registration.waiting.postMessage({ type: 'SKIP_WAITING' });
            
            // Recarregar a página
            window.location.reload();
            
        } catch (error) {
            console.error('[PWA] Erro durante atualização:', error);
        }
    }
    
    hideInstallBanner() {
        const banners = document.querySelectorAll('.pwa-banner-install');
        banners.forEach(banner => banner.remove());
    }
    
    showInstallationSuccess() {
        // Mostrar notificação de sucesso
        const notification = document.createElement('div');
        notification.className = 'pwa-success-notification';
        notification.innerHTML = `
            <div class="pwa-success-content">
                <i class="fas fa-check-circle"></i>
                <span>App instalado com sucesso!</span>
            </div>
        `;
        
        // Adicionar estilos
        this.addSuccessNotificationStyles();
        
        document.body.appendChild(notification);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notification.remove();
        }, 3000);
    }
    
    addSuccessNotificationStyles() {
        if (document.getElementById('pwa-success-styles')) return;
        
        const styles = document.createElement('style');
        styles.id = 'pwa-success-styles';
        styles.textContent = `
            .pwa-success-notification {
                position: fixed;
                top: 20px;
                right: 20px;
                background: #27ae60;
                color: white;
                padding: 12px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
                z-index: 10001;
                animation: slideInRight 0.3s ease;
            }
            
            .pwa-success-content {
                display: flex;
                align-items: center;
                gap: 10px;
                font-weight: 500;
            }
            
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
        `;
        
        document.head.appendChild(styles);
    }
    
    checkInstallationStatus() {
        // Verificar se está rodando como PWA
        if (window.matchMedia('(display-mode: standalone)').matches) {
            console.log('[PWA] App rodando em modo standalone');
        }
        
        // Verificar se está em iOS
        if (/iPad|iPhone|iPod/.test(navigator.userAgent)) {
            console.log('[PWA] Dispositivo iOS detectado');
        }
    }
    
    // Método público para verificar se há atualização
    hasUpdate() {
        return this.updateAvailable;
    }
    
    // Método público para forçar verificação de atualização
    async checkForUpdates() {
        if (this.registration) {
            await this.registration.update();
        }
    }
}

// Inicializar PWA Manager quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se estamos na área admin
    if (window.location.pathname.includes('/admin/')) {
        window.pwaManager = new PWAManager();
    }
});

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.PWAManager = PWAManager;
}
