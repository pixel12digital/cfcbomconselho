/**
 * PWA Install Footer Component
 * Componente discreto para instalação e compartilhamento do app
 * Aparece apenas em páginas institucionais e login (não nos dashboards)
 */

class PWAInstallFooter {
    constructor(options = {}) {
        this.deferredPrompt = null;
        this.isInstalled = false;
        this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        this.options = {
            userType: options.userType || this.detectUserType(),
            containerSelector: options.containerSelector || null,
            ...options
        };
        
        this.init();
    }
    
    /**
     * Detectar tipo de usuário pela URL
     */
    detectUserType() {
        const urlParams = new URLSearchParams(window.location.search);
        const type = urlParams.get('type');
        
        if (type === 'aluno') return 'aluno';
        if (type === 'instrutor' || type === 'admin') return 'instrutor';
        
        // Se não tiver tipo na URL, verificar pela rota
        const path = window.location.pathname;
        if (path.includes('/instrutor/') || path.includes('/admin/')) return 'instrutor';
        if (path.includes('/aluno/')) return 'aluno';
        
        return 'institucional'; // Site institucional
    }
    
    /**
     * Obter URL correta baseada no tipo de usuário
     */
    getAppUrl() {
        if (this.options.userType === 'aluno') {
            return 'https://cfcbomconselho.com.br/login.php?type=aluno';
        } else if (this.options.userType === 'instrutor') {
            return 'https://cfcbomconselho.com.br/login.php?type=instrutor';
        } else {
            // Site institucional - retornar URL principal
            return 'https://cfcbomconselho.com.br';
        }
    }
    
    /**
     * Obter texto do botão baseado no tipo
     */
    getButtonText() {
        if (this.options.userType === 'aluno') {
            return 'Instalar App do Aluno';
        } else if (this.options.userType === 'instrutor') {
            return 'Instalar App do Instrutor';
        } else {
            return 'Instalar App';
        }
    }
    
    /**
     * Obter título do app baseado no tipo
     */
    getAppTitle() {
        if (this.options.userType === 'aluno') {
            return 'App do Aluno';
        } else if (this.options.userType === 'instrutor') {
            return 'App do Instrutor';
        } else {
            return 'App do CFC';
        }
    }
    
    /**
     * Inicializar componente
     */
    async init() {
        // Verificar se estamos em dashboard (não mostrar)
        if (this.isDashboardPage()) {
            return; // Não mostrar em dashboards
        }
        
        // Verificar se já está instalado (mas ainda mostrar o componente)
        if (this.isAlreadyInstalled()) {
            this.isInstalled = true;
        }
        
        // Configurar eventos
        this.setupInstallEvents();
        
        // Aguardar DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.render());
        } else {
            this.render();
        }
    }
    
    /**
     * Verificar se já está instalado
     */
    isAlreadyInstalled() {
        // Verificar display mode
        if (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) {
            return true;
        }
        
        // Verificar se está em modo standalone (navigator.standalone para iOS)
        if (window.navigator.standalone === true) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Verificar se estamos em página de dashboard
     */
    isDashboardPage() {
        const path = window.location.pathname;
        return path.includes('/instrutor/dashboard') || 
               path.includes('/aluno/dashboard') ||
               path.includes('/admin/');
    }
    
    /**
     * Configurar eventos de instalação
     */
    setupInstallEvents() {
        // Evento beforeinstallprompt (Android/Desktop)
        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            this.deferredPrompt = e;
            this.updateInstallButton();
        });
        
        // Evento appinstalled (quando instala)
        window.addEventListener('appinstalled', () => {
            this.isInstalled = true;
            this.deferredPrompt = null;
            this.hide();
        });
    }
    
    /**
     * Renderizar componente no footer
     */
    render() {
        console.log('[PWA Footer] Iniciando renderização...');
        
        // Encontrar container
        const container = this.findContainer();
        if (!container) {
            console.warn('[PWA Footer] Container não encontrado');
            return;
        }
        
        console.log('[PWA Footer] Container encontrado:', container);
        
        // Verificar se já existe um bloco (evitar duplicação)
        const existingBlock = container.querySelector('.pwa-install-footer');
        if (existingBlock) {
            console.log('[PWA Footer] Bloco já existe, removendo...');
            existingBlock.remove();
        }
        
        // Remover listeners antigos se existirem
        if (this.containerListener) {
            container.removeEventListener('click', this.containerListener);
        }
        
        // Criar elemento
        const footerBlock = this.createFooterBlock();
        container.appendChild(footerBlock);
        
        console.log('[PWA Footer] Bloco inserido no DOM');
        
        // Usar delegação de eventos no container
        this.setupEventDelegation(container);
        
        // Atualizar estado dos botões
        this.updateInstallButton();
        
        // Verificar se os botões foram criados
        const shareBtn = footerBlock.querySelector('#pwa-share-btn');
        console.log('[PWA Footer] Botão compartilhar encontrado:', !!shareBtn);
    }
    
    /**
     * Encontrar container do footer
     */
    findContainer() {
        // Se foi especificado um seletor, usar ele
        if (this.options.containerSelector) {
            const container = document.querySelector(this.options.containerSelector);
            if (container) return container;
        }
        
        // Tentar encontrar container existente primeiro
        let container = document.querySelector('.pwa-install-footer-container');
        if (container) return container;
        
        // Tentar encontrar footer padrão
        const footer = document.querySelector('footer');
        if (!footer) {
            console.warn('[PWA Footer] Footer não encontrado, tentando criar container no body');
            // Criar container no final do body como fallback
            container = document.createElement('div');
            container.className = 'pwa-install-footer-container';
            document.body.appendChild(container);
            return container;
        }
        
        // Tentar encontrar .login-footer primeiro (login.php)
        let loginFooter = footer.querySelector('.login-footer');
        if (!loginFooter) {
            // Se não encontrar .login-footer, usar o footer diretamente
            loginFooter = footer;
        }
        
        // Criar container se não existir
        container = document.createElement('div');
        container.className = 'pwa-install-footer-container';
        
        // Inserir antes do último elemento do login-footer ou no final do footer
        if (loginFooter.lastChild) {
            loginFooter.insertBefore(container, loginFooter.lastChild.nextSibling);
        } else {
            loginFooter.appendChild(container);
        }
        
        return container;
    }
    
    /**
     * Criar bloco do footer
     */
    createFooterBlock() {
        const block = document.createElement('div');
        block.className = 'pwa-install-footer';
        
        // Verificar se está instalado
        const isInstalled = this.isAlreadyInstalled();
        
        // Verificar se navegador suporta PWA
        const supportsPWA = 'serviceWorker' in navigator;
        const isChrome = /Chrome/.test(navigator.userAgent);
        const isIncognito = !window.chrome || !window.chrome.runtime;
        const isInApp = /FBAN|FBAV|Instagram|Line|WhatsApp|wv/i.test(navigator.userAgent);
        
        // Mostrar hint apenas em navegadores in-app ou quando realmente não suporta
        const showInstallHint = (isInApp || (!isChrome && !this.isIOS)) && !isInstalled;
        
        // Armazenar para uso no template
        this._isInApp = isInApp;
        
        block.innerHTML = `
            <div class="pwa-install-footer-content">
                <div class="pwa-install-footer-title" style="cursor: pointer;">
                    <span class="pwa-install-icon">⬇️</span>
                    <span>${this.getAppTitle()}</span>
                </div>
                ${isInstalled ? `
                <div class="pwa-install-footer-status">
                    <i class="fas fa-check-circle"></i>
                    <span>App instalado</span>
                </div>
                ` : ''}
                <div class="pwa-install-footer-actions">
                    <button class="pwa-install-btn pwa-install-btn-primary" id="pwa-install-btn" style="display: none;">
                        <i class="fas fa-download"></i>
                        <span>${this.getButtonText()}</span>
                    </button>
                    <button class="pwa-install-btn pwa-install-btn-secondary" id="pwa-share-btn">
                        <i class="fas fa-share-alt"></i>
                        <span>Compartilhar</span>
                    </button>
                    ${isInApp ? `
                    <button class="pwa-install-btn pwa-install-btn-chrome" id="pwa-open-chrome-btn">
                        <i class="fab fa-chrome"></i>
                        <span>Abrir no Chrome</span>
                    </button>
                    ` : ''}
                    ${this.isIOS ? `
                    <button class="pwa-install-btn pwa-install-btn-ios" id="pwa-ios-install-btn">
                        <i class="fas fa-mobile-alt"></i>
                        <span>Como instalar no iPhone</span>
                    </button>
                    ` : ''}
                    ${showInstallHint ? `
                    <div class="pwa-install-hint" style="cursor: pointer;">
                        <i class="fas fa-info-circle"></i>
                        <span>${this._isInApp ? 'Abra no Chrome para instalar' : 'Como instalar o app'}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
        
        // Não precisa mais anexar listeners aqui - delegação será usada
        
        return block;
    }
    
    /**
     * Configurar delegação de eventos (robusto - não perde listeners)
     */
    setupEventDelegation(container) {
        console.log('[PWA Footer] Configurando delegação de eventos...');
        
        // Remover listener anterior se existir
        if (this.containerListener) {
            container.removeEventListener('click', this.containerListener);
        }
        
        // Criar listener único para o container
        this.containerListener = (e) => {
            const target = e.target;
            const button = target.closest('button');
            const hint = target.closest('.pwa-install-hint');
            const title = target.closest('.pwa-install-footer-title');
            
            // Prevenir comportamento padrão
            e.preventDefault();
            e.stopPropagation();
            
            // Botão de instalação
            if (button && button.id === 'pwa-install-btn') {
                console.log('[PWA Footer] Botão instalar clicado (delegação)');
                this.handleInstall();
                return;
            }
            
            // Botão de compartilhar
            if (button && button.id === 'pwa-share-btn') {
                console.log('[PWA Footer] Botão compartilhar clicado (delegação)');
                this.handleShare();
                return;
            }
            
            // Botão iOS
            if (button && button.id === 'pwa-ios-install-btn') {
                console.log('[PWA Footer] Botão iOS clicado (delegação)');
                this.showIOSInstructions();
                return;
            }
            
            // Botão "Abrir no Chrome"
            if (button && button.id === 'pwa-open-chrome-btn') {
                console.log('[PWA Footer] Botão "Abrir no Chrome" clicado (delegação)');
                this.openInChrome();
                return;
            }
            
            // Clique no título
            if (title) {
                console.log('[PWA Footer] Título clicado (delegação)');
                this.handleTitleClick();
                return;
            }
            
            // Clique no aviso/hint
            if (hint) {
                console.log('[PWA Footer] Aviso "Abra no Chrome" clicado (delegação)');
                this.showInstallHelp();
                return;
            }
        };
        
        // Anexar listener ao container
        container.addEventListener('click', this.containerListener, true); // useCapture = true para capturar antes
        console.log('[PWA Footer] Delegação de eventos configurada');
    }
    
    /**
     * Lidar com clique no título "App do CFC"
     */
    handleTitleClick() {
        console.log('[PWA Footer] handleTitleClick chamado');
        
        // Se tiver deferredPrompt, instalar
        if (this.deferredPrompt) {
            console.log('[PWA Footer] Instalando via clique no título...');
            this.handleInstall();
            return;
        }
        
        // Se não, mostrar ajuda
        this.showInstallHelp();
    }
    
    /**
     * Mostrar modal de ajuda de instalação
     */
    showInstallHelp() {
        console.log('[PWA Footer] showInstallHelp chamado');
        
        // Remover modal existente se houver
        const existingModal = document.querySelector('.pwa-help-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const isChrome = /Chrome/.test(navigator.userAgent);
        const isIncognito = window.navigator.connection === undefined || 
                           (window.chrome && window.chrome.runtime && window.chrome.runtime.onConnect === undefined);
        const isInApp = /FBAN|FBAV|Instagram|Line|WhatsApp|wv/i.test(navigator.userAgent);
        
        let helpContent = '';
        
        if (this.isIOS) {
            helpContent = `
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">1</div>
                    <div class="pwa-help-step-content">
                        <p>Toque no botão <strong>Compartilhar</strong> <i class="fas fa-share"></i> na barra inferior do Safari</p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">2</div>
                    <div class="pwa-help-step-content">
                        <p>Role a lista e toque em <strong>Adicionar à Tela de Início</strong></p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">3</div>
                    <div class="pwa-help-step-content">
                        <p>Confirme e o app será adicionado à sua tela inicial</p>
                    </div>
                </div>
            `;
        } else if (isInApp) {
            helpContent = `
                <div class="pwa-help-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Você está em um navegador in-app. Para instalar o app:</p>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">1</div>
                    <div class="pwa-help-step-content">
                        <p>Toque nos <strong>3 pontos</strong> (menu) no canto superior direito</p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">2</div>
                    <div class="pwa-help-step-content">
                        <p>Selecione <strong>Abrir no Chrome</strong> ou <strong>Abrir no Safari</strong></p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">3</div>
                    <div class="pwa-help-step-content">
                        <p>No navegador, procure pelo ícone de instalação na barra de endereços</p>
                    </div>
                </div>
            `;
        } else if (isChrome && isIncognito) {
            helpContent = `
                <div class="pwa-help-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Você está em uma janela anônima. Para instalar o app:</p>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">1</div>
                    <div class="pwa-help-step-content">
                        <p>Abra uma <strong>janela normal</strong> do Chrome (não anônima)</p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">2</div>
                    <div class="pwa-help-step-content">
                        <p>Acesse o mesmo site nesta janela normal</p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">3</div>
                    <div class="pwa-help-step-content">
                        <p>O botão "Instalar App" aparecerá automaticamente</p>
                    </div>
                </div>
            `;
        } else {
            const appName = this.getAppTitle();
            helpContent = `
                <div class="pwa-help-note">
                    <i class="fas fa-info-circle"></i>
                    <p>Para instalar o ${appName}:</p>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">1</div>
                    <div class="pwa-help-step-content">
                        <p>Use o navegador <strong>Chrome</strong> ou <strong>Edge</strong></p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">2</div>
                    <div class="pwa-help-step-content">
                        <p>Toque no menu (⋮) no canto superior direito</p>
                    </div>
                </div>
                <div class="pwa-help-step">
                    <div class="pwa-help-step-number">3</div>
                    <div class="pwa-help-step-content">
                        <p>Selecione <strong>Instalar app</strong> ou procure pelo ícone de instalação na barra de endereços</p>
                    </div>
                </div>
            `;
        }
        
        const modal = document.createElement('div');
        modal.className = 'pwa-help-modal';
        modal.innerHTML = `
            <div class="pwa-help-modal-content">
                <div class="pwa-help-modal-header">
                    <h4>Como Instalar o App</h4>
                    <button class="pwa-help-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pwa-help-modal-body">
                    ${helpContent}
                </div>
            </div>
        `;
        
        // Event listener para fechar
        const closeBtn = modal.querySelector('.pwa-help-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                modal.remove();
            });
        }
        
        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        document.body.appendChild(modal);
        console.log('[PWA Footer] Modal de ajuda criado');
    }
    
    /**
     * Atualizar visibilidade do botão de instalação
     */
    updateInstallButton() {
        const installBtn = document.getElementById('pwa-install-btn');
        if (!installBtn) return;
        
        if (this.deferredPrompt && !this.isInstalled) {
            installBtn.style.display = 'inline-flex';
        } else {
            installBtn.style.display = 'none';
        }
    }
    
    /**
     * Diagnosticar por que PWA não está elegível
     */
    async diagnosePWA() {
        const diagnostics = [];
        
        // 1. Verificar Service Worker
        const hasController = !!navigator.serviceWorker.controller;
        if (!hasController) {
            diagnostics.push('Service Worker não está controlando esta página');
            
            // Verificar se está registrado
            try {
                const regs = await navigator.serviceWorker.getRegistrations();
                if (regs.length === 0) {
                    diagnostics.push('Nenhum Service Worker registrado');
                } else {
                    diagnostics.push(`Service Worker registrado mas não ativo (${regs.length} registro(s))`);
                }
            } catch (e) {
                diagnostics.push('Erro ao verificar registros do Service Worker');
            }
        }
        
        // 2. Verificar manifest
        const manifestLink = document.querySelector('link[rel="manifest"]');
        if (!manifestLink) {
            diagnostics.push('Manifest não encontrado no HTML');
        } else {
            try {
                const manifestUrl = manifestLink.href;
                const res = await fetch(manifestUrl, {cache: 'no-store'});
                if (res.status !== 200) {
                    diagnostics.push(`Manifest retornou status ${res.status}`);
                } else {
                    const contentType = res.headers.get('content-type');
                    if (!contentType || !contentType.includes('json')) {
                        diagnostics.push(`Manifest com Content-Type incorreto: ${contentType}`);
                    } else {
                        const json = await res.json();
                        if (!json.name || !json.start_url) {
                            diagnostics.push('Manifest JSON inválido ou incompleto');
                        }
                    }
                }
            } catch (e) {
                diagnostics.push(`Erro ao carregar manifest: ${e.message}`);
            }
        }
        
        // 3. Verificar HTTPS
        if (!window.isSecureContext) {
            diagnostics.push('Não está em contexto seguro (HTTPS)');
        }
        
        // 4. Verificar se já está instalado
        if (window.matchMedia('(display-mode: standalone)').matches) {
            diagnostics.push('App já está instalado');
        }
        
        return diagnostics;
    }
    
    /**
     * Lidar com instalação
     */
    async handleInstall() {
        const userType = this.options.userType || this.detectUserType();
        const hasPrompt = !!this.deferredPrompt;
        
        console.log('[PWA Footer] Clique em "Instalar App" detectado');
        console.log('[PWA Footer] Tipo detectado:', userType);
        console.log('[PWA Footer] beforeinstallprompt disponível:', hasPrompt);
        
        if (!this.deferredPrompt) {
            console.warn('[PWA Footer] Deferred prompt não disponível, diagnosticando...');
            
            // Diagnosticar problema
            const diagnostics = await this.diagnosePWA();
            if (diagnostics.length > 0) {
                this.showDiagnostics(diagnostics);
            } else {
                this.showInstallHelp();
            }
            return;
        }
        
        try {
            // Mostrar prompt de instalação
            this.deferredPrompt.prompt();
            
            // Aguardar resposta do usuário
            const { outcome } = await this.deferredPrompt.userChoice;
            
            console.log('[PWA Footer] Resultado da instalação:', outcome);
            
            if (outcome === 'accepted') {
                this.showSuccessMessage('App instalado com sucesso!');
            } else {
                console.log('[PWA Footer] Usuário rejeitou a instalação');
            }
            
            // Limpar deferred prompt
            this.deferredPrompt = null;
            this.updateInstallButton();
            
        } catch (error) {
            console.error('[PWA Footer] Erro durante instalação:', error);
            this.showErrorMessage('Erro ao instalar o app. Tente novamente.');
        }
    }
    
    /**
     * Abrir no Chrome (para in-app browsers)
     */
    openInChrome() {
        const currentUrl = window.location.href;
        console.log('[PWA Footer] Tentando abrir no Chrome:', currentUrl);
        
        // Extrair apenas o path e query string (sem o protocolo e domínio)
        const urlParts = new URL(currentUrl);
        const pathAndQuery = urlParts.pathname + urlParts.search + urlParts.hash;
        
        // Tentar usar intent do Android primeiro
        const chromeIntent = `intent://${urlParts.host}${pathAndQuery}#Intent;scheme=https;package=com.android.chrome;end`;
        
        // Criar um link temporário para tentar abrir
        const link = document.createElement('a');
        link.href = chromeIntent;
        link.style.display = 'none';
        document.body.appendChild(link);
        
        try {
            link.click();
            
            // Se não funcionar após um tempo, tentar fallback
            setTimeout(() => {
                // Fallback 1: Tentar com googlechrome://
                const chromeUrl = `googlechrome://${urlParts.host}${pathAndQuery}`;
                link.href = chromeUrl;
                link.click();
                
                // Fallback 2: Se ainda não funcionar, mostrar instruções
                setTimeout(() => {
                    this.showChromeInstructions();
                }, 1000);
            }, 500);
            
            // Remover link após tentativas
            setTimeout(() => {
                if (link.parentNode) {
                    link.parentNode.removeChild(link);
                }
            }, 2000);
        } catch (error) {
            console.error('[PWA Footer] Erro ao abrir no Chrome:', error);
            if (link.parentNode) {
                link.parentNode.removeChild(link);
            }
            this.showChromeInstructions();
        }
    }
    
    /**
     * Mostrar diagnóstico real do PWA
     */
    showDiagnostics(diagnostics) {
        const modal = document.createElement('div');
        modal.className = 'pwa-help-modal';
        modal.innerHTML = `
            <div class="pwa-help-modal-content">
                <div class="pwa-help-modal-header">
                    <h4>Diagnóstico PWA</h4>
                    <button class="pwa-help-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pwa-help-modal-body">
                    <div class="pwa-help-note" style="background: #fff3cd; border-left-color: #ffc107;">
                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                        <p><strong>O app não está elegível para instalação:</strong></p>
                    </div>
                    <ul style="list-style: none; padding: 0; margin: 20px 0;">
                        ${diagnostics.map(d => `<li style="padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                            <i class="fas fa-times-circle" style="color: #e74c3c; margin-right: 8px;"></i>
                            ${d}
                        </li>`).join('')}
                    </ul>
                    <div class="pwa-help-note">
                        <i class="fas fa-info-circle"></i>
                        <p>Verifique o console do navegador (F12) para mais detalhes técnicos.</p>
                    </div>
                </div>
            </div>
        `;
        
        const closeBtn = modal.querySelector('.pwa-help-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                modal.remove();
            });
        }
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        document.body.appendChild(modal);
    }
    
    /**
     * Mostrar instruções para abrir no Chrome
     */
    showChromeInstructions() {
        const modal = document.createElement('div');
        modal.className = 'pwa-help-modal';
        modal.innerHTML = `
            <div class="pwa-help-modal-content">
                <div class="pwa-help-modal-header">
                    <h4>Abrir no Chrome</h4>
                    <button class="pwa-help-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pwa-help-modal-body">
                    <div class="pwa-help-note">
                        <i class="fas fa-info-circle"></i>
                        <p>Para instalar o app, você precisa abrir esta página no Chrome:</p>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">1</div>
                        <div class="pwa-help-step-content">
                            <p>Toque nos <strong>3 pontos</strong> (⋮) no canto superior direito</p>
                        </div>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">2</div>
                        <div class="pwa-help-step-content">
                            <p>Selecione <strong>Abrir no Chrome</strong> ou <strong>Abrir no navegador</strong></p>
                        </div>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">3</div>
                        <div class="pwa-help-step-content">
                            <p>No Chrome, procure pelo ícone de instalação na barra de endereços</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        const closeBtn = modal.querySelector('.pwa-help-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                modal.remove();
            });
        }
        
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.remove();
            }
        });
        
        document.body.appendChild(modal);
    }
    
    /**
     * Lidar com compartilhamento
     */
    async handleShare() {
        console.log('[PWA Footer] handleShare chamado');
        
        const url = this.getAppUrl();
        const title = this.options.userType === 'aluno'
            ? 'CFC Bom Conselho - App do Aluno'
            : this.options.userType === 'instrutor'
            ? 'CFC Bom Conselho - App do Instrutor'
            : 'CFC Bom Conselho - Sistema';
        const text = this.options.userType === 'aluno' 
            ? 'Acesse o Portal do Aluno do CFC Bom Conselho'
            : this.options.userType === 'instrutor'
            ? 'Acesse o Portal do Instrutor do CFC Bom Conselho'
            : 'Acesse o site do CFC Bom Conselho';
        
        console.log('[PWA Footer] URL:', url);
        console.log('[PWA Footer] Navigator.share disponível:', !!navigator.share);
        
        // Tentar Web Share API primeiro
        if (navigator.share) {
            try {
                console.log('[PWA Footer] Tentando Web Share API...');
                await navigator.share({
                    title: title,
                    text: text,
                    url: url
                });
                console.log('[PWA Footer] Compartilhamento via Web Share API concluído');
                return;
            } catch (error) {
                // Usuário cancelou ou erro - continuar para fallback
                if (error.name !== 'AbortError') {
                    console.log('[PWA Footer] Erro ao compartilhar:', error);
                } else {
                    console.log('[PWA Footer] Usuário cancelou compartilhamento');
                    return;
                }
            }
        }
        
        // Fallback: mostrar opções
        console.log('[PWA Footer] Mostrando opções de compartilhamento (fallback)');
        this.showShareOptions(url, text);
    }
    
    /**
     * Mostrar opções de compartilhamento (fallback)
     */
    showShareOptions(url, text) {
        console.log('[PWA Footer] showShareOptions chamado');
        
        // Remover modal existente se houver
        const existingModal = document.querySelector('.pwa-share-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modal = document.createElement('div');
        modal.className = 'pwa-share-modal';
        modal.innerHTML = `
            <div class="pwa-share-modal-content">
                <div class="pwa-share-modal-header">
                    <h4>Compartilhar</h4>
                    <button class="pwa-share-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pwa-share-modal-options">
                    <button class="pwa-share-option" data-action="whatsapp" type="button">
                        <i class="fab fa-whatsapp"></i>
                        <span>Enviar no WhatsApp</span>
                    </button>
                    <button class="pwa-share-option" data-action="copy" type="button">
                        <i class="fas fa-copy"></i>
                        <span>Copiar link</span>
                    </button>
                </div>
            </div>
        `;
        
        // Event listeners - usar arrow functions para manter contexto
        const whatsappBtn = modal.querySelector('[data-action="whatsapp"]');
        if (whatsappBtn) {
            whatsappBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('[PWA Footer] WhatsApp clicado');
                this.shareViaWhatsApp(url, text);
                modal.remove();
            });
        }
        
        const copyBtn = modal.querySelector('[data-action="copy"]');
        if (copyBtn) {
            copyBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('[PWA Footer] Copiar link clicado');
                this.copyToClipboard(url);
                modal.remove();
            });
        }
        
        const closeBtn = modal.querySelector('.pwa-share-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('[PWA Footer] Fechar modal clicado');
                modal.remove();
            });
        }
        
        // Fechar ao clicar fora do modal
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                console.log('[PWA Footer] Clicou fora do modal');
                modal.remove();
            }
        });
        
        document.body.appendChild(modal);
        console.log('[PWA Footer] Modal de compartilhamento criado e inserido');
    }
    
    /**
     * Compartilhar via WhatsApp
     */
    shareViaWhatsApp(url, text) {
        console.log('[PWA Footer] shareViaWhatsApp chamado');
        const message = encodeURIComponent(`${text}\n\n${url}`);
        const whatsappUrl = `https://wa.me/?text=${message}`;
        console.log('[PWA Footer] Abrindo WhatsApp:', whatsappUrl);
        
        // Tentar abrir em nova aba (pode ser bloqueado em anônimo)
        try {
            const newWindow = window.open(whatsappUrl, '_blank', 'noopener,noreferrer');
            if (!newWindow || newWindow.closed || typeof newWindow.closed === 'undefined') {
                // Popup bloqueado, tentar navegação direta
                console.log('[PWA Footer] Popup bloqueado, tentando navegação direta');
                window.location.href = whatsappUrl;
            }
        } catch (error) {
            console.error('[PWA Footer] Erro ao abrir WhatsApp:', error);
            // Fallback: navegação direta
            window.location.href = whatsappUrl;
        }
    }
    
    /**
     * Copiar para área de transferência
     */
    async copyToClipboard(url) {
        console.log('[PWA Footer] copyToClipboard chamado, URL:', url);
        
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(url);
                console.log('[PWA Footer] Link copiado via Clipboard API');
                this.showSuccessMessage('Link copiado para a área de transferência!');
            } else {
                throw new Error('Clipboard API não disponível');
            }
        } catch (error) {
            console.warn('[PWA Footer] Erro ao copiar via Clipboard API, tentando fallback:', error);
            // Fallback para navegadores antigos
            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';
            textarea.style.left = '-9999px';
            textarea.style.top = '0';
            textarea.setAttribute('readonly', '');
            document.body.appendChild(textarea);
            
            try {
                textarea.select();
                textarea.setSelectionRange(0, url.length);
                const successful = document.execCommand('copy');
                if (successful) {
                    console.log('[PWA Footer] Link copiado via execCommand');
                    this.showSuccessMessage('Link copiado para a área de transferência!');
                } else {
                    throw new Error('execCommand falhou');
                }
            } catch (err) {
                console.error('[PWA Footer] Erro ao copiar via fallback:', err);
                this.showErrorMessage('Erro ao copiar link. Tente selecionar e copiar manualmente.');
            } finally {
                document.body.removeChild(textarea);
            }
        }
    }
    
    /**
     * Mostrar instruções iOS
     */
    showIOSInstructions() {
        console.log('[PWA Footer] showIOSInstructions chamado');
        
        // Remover modal existente se houver
        const existingModal = document.querySelector('.pwa-ios-modal');
        if (existingModal) {
            existingModal.remove();
        }
        
        const modal = document.createElement('div');
        modal.className = 'pwa-ios-modal';
        modal.innerHTML = `
            <div class="pwa-ios-modal-content">
                <div class="pwa-ios-modal-header">
                    <h4>Instalar no iPhone</h4>
                    <button class="pwa-ios-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pwa-ios-modal-body">
                    <div class="pwa-ios-steps">
                        <div class="pwa-ios-step">
                            <div class="pwa-ios-step-number">1</div>
                            <div class="pwa-ios-step-content">
                                <p>Toque no botão <strong>Compartilhar</strong> <i class="fas fa-share"></i> na barra inferior do Safari</p>
                            </div>
                        </div>
                        <div class="pwa-ios-step">
                            <div class="pwa-ios-step-number">2</div>
                            <div class="pwa-ios-step-content">
                                <p>Role a lista e toque em <strong>Adicionar à Tela de Início</strong></p>
                            </div>
                        </div>
                        <div class="pwa-ios-step">
                            <div class="pwa-ios-step-number">3</div>
                            <div class="pwa-ios-step-content">
                                <p>Confirme e o app será adicionado à sua tela inicial</p>
                            </div>
                        </div>
                    </div>
                    <div class="pwa-ios-note">
                        <i class="fas fa-info-circle"></i>
                        <p>O app funcionará como um aplicativo nativo após a instalação</p>
                    </div>
                </div>
            </div>
        `;
        
        // Event listener para fechar
        const closeBtn = modal.querySelector('.pwa-ios-modal-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                console.log('[PWA Footer] Fechar modal iOS clicado');
                modal.remove();
            });
        }
        
        document.body.appendChild(modal);
        console.log('[PWA Footer] Modal iOS criado e inserido');
        
        // Fechar ao clicar fora
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                console.log('[PWA Footer] Clicou fora do modal iOS');
                modal.remove();
            }
        });
    }
    
    /**
     * Mostrar mensagem de sucesso
     */
    showSuccessMessage(message) {
        this.showToast(message, 'success');
    }
    
    /**
     * Mostrar mensagem de erro
     */
    showErrorMessage(message) {
        this.showToast(message, 'error');
    }
    
    /**
     * Mostrar toast
     */
    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `pwa-toast pwa-toast-${type}`;
        toast.textContent = message;
        
        document.body.appendChild(toast);
        
        // Animar entrada
        setTimeout(() => toast.classList.add('pwa-toast-show'), 10);
        
        // Remover após 3 segundos
        setTimeout(() => {
            toast.classList.remove('pwa-toast-show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Ocultar componente
     */
    hide() {
        const footer = document.querySelector('.pwa-install-footer');
        if (footer) {
            footer.style.display = 'none';
        }
    }
}

// Função para detectar base path dinamicamente
function getPWABasePath() {
    // Se já foi definido globalmente, usar
    if (typeof window.PWA_BASE_PATH !== 'undefined') {
        return window.PWA_BASE_PATH;
    }
    
    // Detectar automaticamente baseado na URL
    const path = window.location.pathname;
    
    // Se estiver em subpasta (ex: /cfc-bom-conselho/)
    if (path.includes('/cfc-bom-conselho/')) {
        return '/cfc-bom-conselho';
    }
    
    // Se estiver em raiz, retornar vazio
    return '';
}

// Inicializar automaticamente quando DOM estiver pronto
function initPWAInstallFooter() {
    console.log('[PWA Footer] initPWAInstallFooter chamado');
    
    // Verificar se não estamos em dashboard
    const path = window.location.pathname;
    const isDashboard = path.includes('/instrutor/dashboard') || 
                       path.includes('/aluno/dashboard') ||
                       path.includes('/admin/');
    
    console.log('[PWA Footer] Path:', path);
    console.log('[PWA Footer] É dashboard?', isDashboard);
    
    if (!isDashboard) {
        // Definir base path antes de inicializar
        window.PWA_BASE_PATH = getPWABasePath();
        console.log('[PWA Footer] Base path:', window.PWA_BASE_PATH);
        
        try {
            window.pwaInstallFooter = new PWAInstallFooter();
            console.log('[PWA Footer] Componente inicializado com sucesso');
        } catch (error) {
            console.error('[PWA Footer] Erro ao inicializar componente:', error);
        }
    } else {
        console.log('[PWA Footer] Dashboard detectado, componente não será inicializado');
    }
}

if (document.readyState === 'loading') {
    console.log('[PWA Footer] DOM ainda carregando, aguardando DOMContentLoaded');
    document.addEventListener('DOMContentLoaded', () => {
        console.log('[PWA Footer] DOMContentLoaded disparado');
        initPWAInstallFooter();
    });
} else {
    console.log('[PWA Footer] DOM já carregado, inicializando imediatamente');
    initPWAInstallFooter();
}

// Exportar para uso global
if (typeof window !== 'undefined') {
    window.PWAInstallFooter = PWAInstallFooter;
}
