/**
 * PWA Registration Script - Sistema CFC Bom Conselho
 * Registra Service Worker e gerencia atualizações
 */

class PWAManager {
    constructor() {
        this.registration = null;
        this.updateAvailable = false;
        this.deferredPrompt = null;
        
        // Remover qualquer banner existente imediatamente
        this.hideInstallBanner();
        
        this.init();
    }
    
    async init() {
        console.log('[PWA] Inicializando PWA Manager...');
        
        // Remover qualquer banner existente ao inicializar
        this.hideInstallBanner();
        
        // Verificar suporte a Service Worker
        if (!('serviceWorker' in navigator)) {
            console.warn('[PWA] Service Worker não suportado');
            return;
        }
        
        // Verificar se já existe um controller
        if (navigator.serviceWorker.controller) {
            console.log('[PWA] ✅ Service Worker já está controlando:', navigator.serviceWorker.controller.scriptURL);
        } else {
            console.log('[PWA] Service Worker ainda não está controlando. Registrando...');
        }
        
        // Registrar Service Worker
        await this.registerServiceWorker();
        
        // Configurar eventos de instalação (mas não mostrar banner)
        this.setupInstallEvents();
        
        // Configurar eventos de atualização
        this.setupUpdateEvents();
        
        // Configurar notificações
        this.setupNotifications();
        
        // Verificar se já está instalado
        this.checkInstallationStatus();
        
        console.log('[PWA] PWA Manager inicializado com sucesso');
        
        // Verificar controller após um delay
        setTimeout(() => {
            this.checkControllerStatus();
            // Garantir que não há banners após delay
            this.hideInstallBanner();
        }, 2000);
    }
    
    /**
     * Verificar status do controller e fornecer feedback
     */
    checkControllerStatus() {
        if (navigator.serviceWorker.controller) {
            console.log('[PWA] ✅ Service Worker está controlando a página');
            console.log('[PWA] Controller URL:', navigator.serviceWorker.controller.scriptURL);
            console.log('[PWA] Controller State:', navigator.serviceWorker.controller.state);
        } else {
            console.warn('[PWA] ⚠️ Service Worker NÃO está controlando a página');
            console.warn('[PWA] Isso é necessário para instalação PWA');
            console.warn('[PWA] Solução: Recarregue a página (F5 ou Ctrl+R)');
            
            // Verificar registros
            navigator.serviceWorker.getRegistrations().then(regs => {
                if (regs.length > 0) {
                    regs.forEach(reg => {
                        console.log('[PWA] SW registrado:', {
                            scope: reg.scope,
                            active: reg.active?.state,
                            installing: reg.installing?.state,
                            waiting: reg.waiting?.state
                        });
                    });
                } else {
                    console.error('[PWA] Nenhum Service Worker registrado!');
                }
            });
        }
    }
    
    async registerServiceWorker() {
        try {
            console.log('[PWA] Registrando Service Worker...');
            
            // Verificar se já existe um controller
            if (navigator.serviceWorker.controller) {
                console.log('[PWA] Service Worker já está controlando:', navigator.serviceWorker.controller.scriptURL);
                return;
            }
            
            // Usar SW do root para garantir scope "/"
            this.registration = await navigator.serviceWorker.register('/sw.js', {
                scope: '/'
            });
            
            console.log('[PWA] Service Worker registrado:', this.registration);
            console.log('[PWA] SW State:', this.registration.active?.state || this.registration.installing?.state || this.registration.waiting?.state);
            console.log('[PWA] SW Scope:', this.registration.scope);
            
            // Se já existe um SW ativo, verificar se está controlando
            if (this.registration.active) {
                console.log('[PWA] SW ativo encontrado:', this.registration.active.scriptURL);
            }
            
            // Se está instalando, aguardar ativação
            if (this.registration.installing) {
                console.log('[PWA] SW instalando, aguardando ativação...');
                this.registration.installing.addEventListener('statechange', () => {
                    console.log('[PWA] SW state mudou para:', this.registration.installing.state);
                    if (this.registration.installing.state === 'activated') {
                        console.log('[PWA] SW ativado! Aguardando clients.claim()...');
                        // Aguardar um pouco e verificar se está controlando
                        setTimeout(() => {
                            if (!navigator.serviceWorker.controller) {
                                console.log('[PWA] SW ativado mas não está controlando. Recarregando página...');
                                window.location.reload();
                            }
                        }, 1000);
                    }
                });
            }
            
            // Se está waiting, pode precisar de skipWaiting
            if (this.registration.waiting) {
                console.log('[PWA] SW waiting encontrado. Pode precisar de skipWaiting.');
                // Se há um SW waiting, pode ser que precise recarregar
                if (!navigator.serviceWorker.controller) {
                    console.log('[PWA] SW waiting e sem controller. Recarregando para ativar...');
                    setTimeout(() => {
                        window.location.reload();
                    }, 500);
                }
            }
            
            // Se já está ativo mas não está controlando, recarregar
            if (this.registration.active && !navigator.serviceWorker.controller) {
                console.log('[PWA] SW ativo mas não está controlando. Recarregando página...');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            }
            
            // Verificar atualizações
            this.registration.addEventListener('updatefound', () => {
                console.log('[PWA] Nova versão do Service Worker encontrada');
                this.handleUpdateFound();
            });
            
            // Escutar mensagens do SW
            navigator.serviceWorker.addEventListener('message', (event) => {
                if (event.data && event.data.type === 'SW_ACTIVATED') {
                    console.log('[PWA] ✅ Service Worker ativado! Versão:', event.data.version);
                    // Recarregar para o SW controlar (apenas se ainda não estiver controlando)
                    if (!navigator.serviceWorker.controller) {
                        console.log('[PWA] Recarregando página para SW controlar...');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    }
                }
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
            
            // Desabilitado: banner removido para evitar confusão
            // O footer do login já tem o botão de instalação
            // Só mostrar banner se ainda deve mostrar baseado nas escolhas do usuário
            // if (this.shouldShowInstallPrompt()) {
            //     this.showInstallBanner();
            // } else {
            //     console.log('[PWA] beforeinstallprompt ignorado - usuário já escolheu anteriormente');
            // }
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
        // DESABILITADO COMPLETAMENTE: Banner nunca será exibido
        // O footer do login (install-footer.js) já tem o botão de instalação
        // Não criar banner para manter apenas uma opção de instalação
        console.log('[PWA] showInstallBanner() chamado mas DESABILITADO - use o footer do login');
        
        // Garantir que nenhum banner exista
        this.hideInstallBanner();
        
        // Retornar imediatamente sem criar nada
        return;
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
        // DESABILITADO: Banner removido completamente
        // Não criar banner - apenas retornar null
        console.log('[PWA] createBanner() chamado mas desabilitado - banner não será criado');
        return null;
        
        // Código original comentado:
        // const banner = document.createElement('div');
        // banner.className = `pwa-banner pwa-banner-${options.type}`;
        // ... resto do código comentado
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
        // Remover TODOS os banners PWA que possam existir
        const banners = document.querySelectorAll('.pwa-banner, .pwa-banner-install, .pwa-banner-install');
        banners.forEach(banner => {
            console.log('[PWA] Removendo banner encontrado:', banner);
            banner.remove();
        });
        
        // Também remover estilos do banner se existirem
        const bannerStyles = document.getElementById('pwa-banner-styles');
        if (bannerStyles) {
            bannerStyles.remove();
        }
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
    
    /**
     * Configurar sistema de notificações
     */
    setupNotifications() {
        console.log('[PWA] Configurando notificações...');
        
        // Verificar suporte a notificações
        if (!('Notification' in window)) {
            console.warn('[PWA] Notificações não suportadas');
            return;
        }
        
        // Verificar permissão atual
        if (Notification.permission === 'granted') {
            console.log('[PWA] Notificações já autorizadas');
            this.maybeShowInstallPrompt();
        } else if (Notification.permission === 'default') {
            console.log('[PWA] Solicitando permissão para notificações...');
            this.requestNotificationPermission();
        } else {
            console.log('[PWA] Notificações negadas pelo usuário');
        }
    }
    
    /**
     * Solicitar permissão para notificações
     */
    async requestNotificationPermission() {
        try {
            const permission = await Notification.requestPermission();
            
            if (permission === 'granted') {
                console.log('[PWA] Permissão para notificações concedida');
                this.maybeShowInstallPrompt();
                
                // Enviar notificação de boas-vindas
                this.sendWelcomeNotification();
            } else {
                console.log('[PWA] Permissão para notificações negada');
            }
        } catch (error) {
            console.error('[PWA] Erro ao solicitar permissão:', error);
        }
    }
    
    /**
     * Enviar notificação de boas-vindas
     */
    sendWelcomeNotification() {
        if (Notification.permission === 'granted') {
            const notification = new Notification('CFC Bom Conselho', {
                body: 'Bem-vindo ao sistema administrativo! Você pode instalar este app para acesso rápido.',
                icon: '/pwa/icons/icon-192.png',
                badge: '/pwa/icons/icon-72.png',
                tag: 'cfc-welcome',
                requireInteraction: false,
                silent: false
            });
            
            // Fechar automaticamente após 5 segundos
            setTimeout(() => {
                notification.close();
            }, 5000);
        }
    }
    
    /**
     * Controlar escolha do usuário sobre instalação
     */
    handleInstallChoice(choice) {
        const banner = document.querySelector('.pwa-banner');
        if (banner) {
            banner.remove();
        }
        
        const now = new Date().getTime();
        
        if (choice === 'accept') {
            // Usuário aceitou instalar - executar instalação
            this.installApp();
            // Salvar que foi aceito (não mostrar mais por 90 dias)
            localStorage.setItem('pwa-install-user-choice', 'accepted');
            localStorage.setItem('pwa-install-choice-timestamp', now + (90 * 24 * 60 * 60 * 1000)); // 90 dias
        } else if (choice === 'dismiss') {
            // Usuário dismissou - não mostrar por 30 dias
            localStorage.setItem('pwa-install-user-choice', 'dismissed');
            localStorage.setItem('pwa-install-choice-timestamp', now + (30 * 24 * 60 * 60 * 1000)); // 30 dias
            console.log('[PWA] Usuário dismissou o prompt de instalação por 30 dias');
        }
    }

    /**
     * Verificar se deve mostrar prompt de instalação
     */
    shouldShowInstallPrompt() {
        const userChoice = localStorage.getItem('pwa-install-user-choice');
        const choiceTimestamp = localStorage.getItem('pwa-install-choice-timestamp');
        const now = new Date().getTime();
        
        // Se já foi escolhido e ainda não expirou, não mostrar
        if (userChoice && choiceTimestamp) {
            if (now < parseInt(choiceTimestamp)) {
                return false; // Ainda dentro do período de repouso
            }
        }
        
        // Se foi aceito e não expirou, nunca mais mostrar
        if (userChoice === 'accepted' && now < parseInt(choiceTimestamp)) {
            return false;
        }
        
        // Limpar dados expirados
        if (now >= parseInt(choiceTimestamp)) {
            localStorage.removeItem('pwa-install-user-choice');
            localStorage.removeItem('pwa-install-choice-timestamp');
        }
        
        return true; // Pode mostrar
    }

    /**
     * Verificar se deve mostrar prompt e eventualmente mostrar
     */
    maybeShowInstallPrompt() {
        // Desabilitado: banner removido para evitar confusão
        // O footer do login já tem o botão de instalação
        // Sempre verificar se deve mostrar antes de tentar mostrar
        // if (this.shouldShowInstallPrompt()) {
        //     console.log('[PWA] Condições atendidas, mostrando prompt de instalação');
        //     this.showInstallPrompt();
        // } else {
        //     console.log('[PWA] Condições não atendidas para mostrar prompt de instalação');
        // }
    }

    /**
     * Mostrar prompt de instalação (método interno - use maybeShowInstallPrompt)
     */
    showInstallPrompt() {
        // Verificar se já foi mostrado hoje (limitação adicional)
        const lastShown = localStorage.getItem('pwa-install-prompt-last-shown');
        const today = new Date().toDateString();
        
        if (lastShown === today) {
            console.log('[PWA] Prompt já foi mostrado hoje - pule');
            return;
        }
        
        // Verificar se está instalado como PWA (verificação final)
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            console.log('[PWA] App já está instalado como PWA - pule');
            return;
        }
        
        // Desabilitado: banner removido para evitar confusão
        // O footer do login já tem o botão de instalação
        // Mostrar banner de instalação
        // console.log('[PWA] Mostrando prompt de instalação');
        // this.showInstallBanner();
        
        // Salvar que foi mostrado hoje
        // localStorage.setItem('pwa-install-prompt-last-shown', today);
    }
}

// Função utilitária para reset das escolhas PWA (para debugging/admin)
window.resetPWAChoices = function() {
    localStorage.removeItem('pwa-install-user-choice');
    localStorage.removeItem('pwa-install-choice-timestamp');
    localStorage.removeItem('pwa-install-prompt-last-shown');
    console.log('[PWA] Escolhas de instalação resetadas');
};

// Inicializar PWA Manager quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se estamos na área admin, instrutor ou login
    const path = window.location.pathname;
    const isAdminArea = path.includes('/admin/');
    const isInstrutorArea = path.includes('/instrutor/');
    const isLoginPage = path.includes('/login.php') || path === '/';
    
    // NÃO inicializar na página de login - o install-footer.js já cuida disso
    // Isso evita mostrar dois prompts de instalação
    if (isAdminArea || isInstrutorArea) {
        window.pwaManager = new PWAManager();
        
        // Debug: mostrar estado das escolhas do usuário
        if (localStorage.getItem('pwa-install-user-choice')) {
            const choice = localStorage.getItem('pwa-install-user-choice');
            const timestamp = localStorage.getItem('pwa-install-choice-timestamp');
            const expiry = new Date(parseInt(timestamp));
            console.log(`[PWA] Estado anterior: ${choice}, expira em: ${expiry.toLocaleString()}`);
        }
    } else if (isLoginPage) {
        console.log('[PWA] Página de login detectada - PWAManager não será inicializado (install-footer.js cuida da instalação)');
    }
});

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.PWAManager = PWAManager;
}
