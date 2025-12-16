/**
 * PWA Install Footer Component
 * Componente discreto para instalação e compartilhamento do app
 * Aparece apenas em páginas institucionais e login (não nos dashboards)
 */

class PWAInstallFooter {
    constructor(options = {}) {
        // Usar window.__deferredPrompt como fonte única (capturado cedo)
        this.deferredPrompt = window.__deferredPrompt || null;
        this.isInstalled = false;
        this.isIOS = /iPad|iPhone|iPod/.test(navigator.userAgent) && !window.MSStream;
        this.options = {
            userType: options.userType || this.detectUserType(),
            containerSelector: options.containerSelector || null,
            ...options
        };
        
        // Se já foi capturado cedo, usar
        if (window.__deferredPrompt) {
            console.log('[PWA Footer] ✅ Usando beforeinstallprompt capturado cedo (timestamp:', window.__bipFiredAt, ')');
        }
        
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
        
        // Verificar se há manifest na página (requisito para PWA)
        const manifestLink = document.querySelector('link[rel="manifest"]');
        if (!manifestLink) {
            console.log('[PWA Footer] Manifest não encontrado na página. Componente não será exibido.');
            return; // Não mostrar se não há manifest
        }
        
        // Verificar se já está instalado - se estiver, não inicializar
        if (this.isAlreadyInstalled()) {
            this.isInstalled = true;
            console.log('[PWA Footer] App já instalado, componente não será inicializado');
            // Ocultar container imediatamente
            this.hide();
            return; // Não continuar inicialização
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
        // Verificar se já foi capturado cedo
        if (window.__deferredPrompt) {
            this.deferredPrompt = window.__deferredPrompt;
            console.log('[PWA Footer] ✅ Usando beforeinstallprompt já capturado cedo');
            this.updateInstallButton();
        }
        
        // Escutar evento customizado (disparado pelo script early)
        window.addEventListener('pwa:beforeinstallprompt', (e) => {
            console.log('[PWA Footer] ✅ Recebido evento customizado pwa:beforeinstallprompt');
            this.deferredPrompt = window.__deferredPrompt;
            this.updateInstallButton();
        });
        
        // Backup: Evento beforeinstallprompt direto (caso o early não tenha capturado)
        window.addEventListener('beforeinstallprompt', (e) => {
            console.log('[PWA Footer] ✅ beforeinstallprompt capturado (backup listener)');
            e.preventDefault();
            window.__deferredPrompt = e;
            window.__bipFiredAt = Date.now();
            this.deferredPrompt = e;
            this.updateInstallButton();
        });
        
        // Log se o evento não disparar após um tempo (apenas para log, não trava botão)
        setTimeout(() => {
            if (!this.deferredPrompt && !window.__deferredPrompt) {
                console.log('[PWA Footer] ℹ️ beforeinstallprompt ainda não foi disparado após 3 segundos');
                console.log('[PWA Footer] Isso pode ser normal - o evento pode demorar mais');
                console.log('[PWA Footer] O botão continuará funcionando e mostrará diagnóstico se necessário');
            } else if (window.__deferredPrompt && !this.deferredPrompt) {
                // Sincronizar se foi capturado cedo mas não sincronizou
                this.deferredPrompt = window.__deferredPrompt;
                this.updateInstallButton();
            }
        }, 3000);
        
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
        
        // Atualizar estado dos botões (aguardar um pouco para deferredPrompt)
        setTimeout(() => {
            this.updateInstallButton();
        }, 500);
        
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
                <div class="pwa-install-footer-title" style="cursor: default;">
                    <span class="pwa-install-icon">⬇️</span>
                    <span>${this.getAppTitle()}</span>
                </div>
                ${!isInstalled ? `
                <div class="pwa-install-footer-subtitle" style="font-size: 12px; color: #666; margin-top: 4px; margin-bottom: 12px;">
                    Instale para abrir como aplicativo no celular e no computador.
                </div>
                ` : ''}
                ${isInstalled ? `
                <div class="pwa-install-footer-status">
                    <i class="fas fa-check-circle"></i>
                    <span>App instalado</span>
                </div>
                ` : ''}
                <div class="pwa-install-footer-actions">
                    <button class="pwa-install-btn pwa-install-btn-primary" id="pwa-install-btn">
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
            
            // Título não é mais clicável (removido para evitar redundância)
            // if (title) {
            //     console.log('[PWA Footer] Título clicado (delegação)');
            //     this.handleTitleClick();
            //     return;
            // }
            
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
            const isAndroid = /Android/i.test(navigator.userAgent);
            const isDesktop = !isAndroid && !this.isIOS;
            
            if (isAndroid) {
                // Android: menu ⋮ → Instalar app
                helpContent = `
                    <div class="pwa-help-note">
                        <i class="fas fa-info-circle"></i>
                        <p>Para instalar o ${appName} no Android:</p>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">1</div>
                        <div class="pwa-help-step-content">
                            <p>Toque no menu <strong>(⋮)</strong> no canto superior direito do Chrome</p>
                        </div>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">2</div>
                        <div class="pwa-help-step-content">
                            <p>Selecione <strong>"Instalar app"</strong> ou <strong>"Adicionar à tela inicial"</strong></p>
                        </div>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">3</div>
                        <div class="pwa-help-step-content">
                            <p>Confirme a instalação</p>
                        </div>
                    </div>
                    <div class="pwa-help-note" style="background: #fff3cd; border-left-color: #ffc107; margin-top: 15px;">
                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                        <p><strong>Nota:</strong> No Android, o ícone de instalação geralmente aparece no menu, não na barra de endereços.</p>
                    </div>
                `;
            } else if (isDesktop) {
                // Desktop: menu ou ícone na barra
                helpContent = `
                    <div class="pwa-help-note">
                        <i class="fas fa-info-circle"></i>
                        <p>Para instalar o ${appName} no Desktop:</p>
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
                            <p><strong>Opção A:</strong> Procure pelo ícone de instalação <i class="fas fa-download"></i> na barra de endereços (canto direito)</p>
                            <p style="margin-top: 8px;"><strong>Opção B:</strong> Clique no menu (⋮) no canto superior direito → <strong>"Instalar app"</strong></p>
                        </div>
                    </div>
                    <div class="pwa-help-step">
                        <div class="pwa-help-step-number">3</div>
                        <div class="pwa-help-step-content">
                            <p>Confirme a instalação na janela que aparecer</p>
                        </div>
                    </div>
                `;
            } else {
                // Fallback genérico
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
        if (!installBtn) {
            console.warn('[PWA Footer] Botão de instalação não encontrado');
            return;
        }
        
        // Se já está instalado, ocultar botão
        if (this.isInstalled) {
            installBtn.style.display = 'none';
            console.log('[PWA Footer] App já instalado, ocultando botão');
            return;
        }
        
        // Botão sempre visível, mas muda estilo se não tiver prompt
        installBtn.style.display = 'inline-flex';
        
        // Verificar window.__deferredPrompt também (fonte única)
        const hasPrompt = this.deferredPrompt || window.__deferredPrompt;
        if (hasPrompt) {
            // Sincronizar se necessário
            if (window.__deferredPrompt && !this.deferredPrompt) {
                this.deferredPrompt = window.__deferredPrompt;
            }
            
            // Tem prompt - botão ativo
            installBtn.classList.remove('pwa-install-btn-disabled');
            installBtn.title = 'Clique para instalar o app';
            const btnText = installBtn.querySelector('span');
            if (btnText) btnText.textContent = this.getButtonText();
            console.log('[PWA Footer] ✅ Botão de instalação ATIVO (prompt disponível)');
        } else {
            // Sem prompt - botão mostra instruções
            installBtn.classList.add('pwa-install-btn-disabled');
            
            // Detectar plataforma para texto específico
            const isAndroid = /Android/i.test(navigator.userAgent);
            const isDesktop = !isAndroid && !this.isIOS;
            const isInApp = /FBAN|FBAV|Instagram|Line|WhatsApp|wv/i.test(navigator.userAgent);
            
            let buttonText = 'Instalar pelo menu do Chrome';
            if (isAndroid && !isInApp) {
                buttonText = 'Instalar pelo menu (⋮)';
            } else if (isDesktop) {
                buttonText = 'Instalar pelo menu ou ícone na barra';
            } else if (isInApp) {
                buttonText = 'Abrir no Chrome para instalar';
            }
            
            const btnText = installBtn.querySelector('span');
            if (btnText) btnText.textContent = buttonText;
            installBtn.title = 'Clique para ver instruções de instalação';
            console.log('[PWA Footer] ⚠️ Botão de instalação (sem prompt - mostrará instruções)');
        }
    }
    
    /**
     * Coletar relatório completo de Installability
     */
    async collectInstallabilityReport() {
        const report = {
            manifest: null,
            manifestUrl: null,
            manifestData: null,
            currentUrl: window.location.href,
            currentPath: window.location.pathname,
            isSecureContext: window.isSecureContext,
            hasServiceWorkerController: !!navigator.serviceWorker.controller,
            serviceWorkerScope: null,
            isStandalone: window.matchMedia('(display-mode: standalone)').matches,
            installedRelatedApps: null,
            currentUrlInScope: false,
            issues: [],
            recommendations: []
        };
        
        // 1. Buscar manifest
        const manifestLink = document.querySelector('link[rel="manifest"]');
        if (manifestLink) {
            report.manifest = manifestLink;
            report.manifestUrl = manifestLink.href;
            
            try {
                const res = await fetch(report.manifestUrl, {cache: 'no-store'});
                if (res.status === 200) {
                    const contentType = res.headers.get('content-type');
                    if (contentType && contentType.includes('json')) {
                        report.manifestData = await res.json();
                        
                        // Verificar se URL atual está no scope
                        const scope = report.manifestData.scope || '/';
                        const currentUrlObj = new URL(report.currentUrl);
                        const scopeUrlObj = new URL(scope, currentUrlObj.origin);
                        report.currentUrlInScope = currentUrlObj.pathname.startsWith(scopeUrlObj.pathname);
                    } else {
                        report.issues.push(`Manifest Content-Type incorreto: ${contentType}`);
                    }
                } else {
                    report.issues.push(`Manifest retornou status ${res.status}`);
                }
            } catch (e) {
                report.issues.push(`Erro ao carregar manifest: ${e.message}`);
            }
        } else {
            report.issues.push('Manifest não encontrado no HTML');
        }
        
        // 2. Verificar Service Worker
        if (report.hasServiceWorkerController) {
            report.serviceWorkerScope = navigator.serviceWorker.controller.scriptURL;
        } else {
            try {
                const regs = await navigator.serviceWorker.getRegistrations();
                if (regs.length > 0) {
                    report.serviceWorkerScope = regs[0].scope;
                    const state = regs[0].active?.state || regs[0].installing?.state || regs[0].waiting?.state || 'unknown';
                    report.issues.push(`Service Worker registrado mas não controlando (estado: ${state})`);
                    report.recommendations.push('Recarregue a página (F5) para o SW assumir controle');
                } else {
                    report.issues.push('Nenhum Service Worker registrado');
                    report.recommendations.push('Acesse login.php?type=aluno ou login.php?type=instrutor para registrar o SW');
                }
            } catch (e) {
                report.issues.push(`Erro ao verificar SW: ${e.message}`);
            }
        }
        
        // 3. Verificar getInstalledRelatedApps
        if ('getInstalledRelatedApps' in navigator) {
            try {
                const apps = await navigator.getInstalledRelatedApps();
                report.installedRelatedApps = apps;
                if (apps && apps.length > 0) {
                    report.issues.push(`App relacionado já instalado: ${JSON.stringify(apps)}`);
                }
            } catch (e) {
                console.warn('[PWA Footer] Erro ao verificar getInstalledRelatedApps:', e);
            }
        }
        
        // 4. Verificar HTTPS
        if (!report.isSecureContext) {
            report.issues.push('Não está em contexto seguro (HTTPS)');
            report.recommendations.push('PWA requer HTTPS');
        }
        
        // 5. Verificar se já está instalado
        if (report.isStandalone) {
            report.issues.push('App já está instalado como PWA');
        }
        
        // 6. Verificar manifest data
        if (report.manifestData) {
            if (!report.manifestData.start_url) {
                report.issues.push('Manifest sem start_url');
            }
            if (!report.manifestData.scope) {
                report.issues.push('Manifest sem scope');
            }
            if (!report.manifestData.id) {
                report.issues.push('Manifest sem id (pode causar conflito)');
            }
            if (!report.manifestData.icons || report.manifestData.icons.length === 0) {
                report.issues.push('Manifest sem ícones');
            } else {
                // Verificar se tem ícones 192 e 512
                const has192 = report.manifestData.icons.some(i => i.sizes === '192x192' || i.sizes.includes('192'));
                const has512 = report.manifestData.icons.some(i => i.sizes === '512x512' || i.sizes.includes('512'));
                if (!has192) report.issues.push('Manifest sem ícone 192x192');
                if (!has512) report.issues.push('Manifest sem ícone 512x512');
            }
        }
        
        // 7. Verificações adicionais para beforeinstallprompt
        report.beforeinstallpromptInfo = {
            listenerRegistered: !!window.__deferredPrompt || window.__bipFiredAt !== undefined,
            timeSincePageLoad: Date.now() - (performance.timing?.navigationStart || Date.now()),
            userAgent: navigator.userAgent,
            platform: navigator.platform,
            cookieEnabled: navigator.cookieEnabled,
            onLine: navigator.onLine
        };
        
        // Verificar se há histórico de rejeição (localStorage)
        try {
            const lastRejection = localStorage.getItem('pwa-install-rejected');
            if (lastRejection) {
                const rejectionTime = parseInt(lastRejection);
                const hoursSinceRejection = (Date.now() - rejectionTime) / (1000 * 60 * 60);
                if (hoursSinceRejection < 24) {
                    report.issues.push(`Prompt foi rejeitado há ${Math.round(hoursSinceRejection)} horas (cooldown do Chrome pode estar ativo)`);
                    report.recommendations.push('Aguarde 24 horas ou limpe os dados do site para resetar o cooldown');
                }
            }
        } catch (e) {
            // Ignorar erro de localStorage
        }
        
        // Verificar se está em modo de desenvolvimento
        if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
            report.recommendations.push('⚠️ Em localhost, o beforeinstallprompt pode não disparar. Teste em produção.');
        }
        
        return report;
    }
    
    /**
     * Diagnosticar por que PWA não está elegível (usando relatório completo)
     */
    async diagnosePWA() {
        const report = await this.collectInstallabilityReport();
        const diagnostics = [];
        const successes = [];
        const solutions = [];
        
        // Adicionar sucessos
        if (report.hasServiceWorkerController) {
            successes.push('Service Worker está controlando a página');
        }
        if (report.isSecureContext) {
            successes.push('HTTPS ativo');
        }
        if (report.manifestData) {
            successes.push(`Manifest válido: ${report.manifestData.name || 'N/A'}`);
        }
        if (report.currentUrlInScope && report.manifestData) {
            successes.push(`URL atual está no scope (${report.manifestData.scope})`);
        }
        
        // Adicionar problemas
        report.issues.forEach(issue => {
            diagnostics.push(issue);
        });
        
        // Adicionar soluções
        report.recommendations.forEach(rec => {
            solutions.push(rec);
        });
        
        // Adicionar informações do manifest
        if (report.manifestData) {
            console.log('[PWA Footer] 📋 Relatório de Installability:');
            console.log('  Manifest URL:', report.manifestUrl);
            console.log('  start_url:', report.manifestData.start_url);
            console.log('  scope:', report.manifestData.scope);
            console.log('  id:', report.manifestData.id);
            console.log('  display:', report.manifestData.display);
            console.log('  icons:', report.manifestData.icons?.map(i => `${i.sizes} (${i.purpose || 'any'})`).join(', ') || 'nenhum');
            console.log('  currentUrlInScope:', report.currentUrlInScope);
            console.log('  isSecureContext:', report.isSecureContext);
            console.log('  hasServiceWorkerController:', report.hasServiceWorkerController);
            console.log('  serviceWorkerScope:', report.serviceWorkerScope);
            console.log('  isStandalone:', report.isStandalone);
            if (report.installedRelatedApps) {
                console.log('  installedRelatedApps:', report.installedRelatedApps);
            }
            if (report.beforeinstallpromptInfo) {
                console.log('  beforeinstallpromptInfo:', report.beforeinstallpromptInfo);
            }
        }
        
        // Diagnóstico específico para beforeinstallprompt não disparar
        if (!window.__deferredPrompt && report.hasServiceWorkerController && report.isSecureContext && report.manifestData) {
            console.log('[PWA Footer] 🔍 Diagnóstico: beforeinstallprompt não disparou apesar de requisitos OK');
            console.log('[PWA Footer] Possíveis causas:');
            console.log('  1. Cooldown do Chrome (usuário rejeitou prompt anteriormente)');
            console.log('  2. App já instalado (mesmo que não apareça em getInstalledRelatedApps)');
            console.log('  3. Requisitos internos do Chrome não atendidos (não visíveis)');
            console.log('  4. Cache do navegador com versão antiga do manifest/SW');
            console.log('[PWA Footer] Soluções:');
            console.log('  - Limpar dados do site (F12 → Application → Clear storage)');
            console.log('  - Desinstalar app PWA se já estiver instalado');
            console.log('  - Aguardar alguns minutos (cooldown pode ser temporário)');
            console.log('  - Testar em janela anônima (Ctrl+Shift+N)');
            
            // Adicionar solução específica
            if (!solutions.some(s => s.includes('cooldown'))) {
                solutions.push('Limpar dados do site (F12 → Application → Clear storage) para resetar cooldown');
                solutions.push('Testar em janela anônima (Ctrl+Shift+N) para evitar cache');
            }
        }
        
        return {
            diagnostics,
            successes,
            solutions,
            report
        };
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
        
        // Verificar window.__deferredPrompt também (fonte única)
        const promptToUse = this.deferredPrompt || window.__deferredPrompt;
        
        if (!promptToUse) {
            console.log('[PWA Footer] Deferred prompt não disponível, coletando relatório de installability...');
            
            // Coletar relatório completo e mostrar diagnóstico
            const diagnosis = await this.diagnosePWA();
            this.showDiagnostics(diagnosis.diagnostics, diagnosis.successes, diagnosis.solutions, diagnosis.report);
            return;
        }
        
        // Sincronizar se necessário
        if (window.__deferredPrompt && !this.deferredPrompt) {
            this.deferredPrompt = window.__deferredPrompt;
        }
        
        try {
            console.log('[PWA Footer] Chamando deferredPrompt.prompt()...');
            
            // Mostrar prompt de instalação
            const promptResult = this.deferredPrompt.prompt();
            console.log('[PWA Footer] prompt() retornou:', promptResult);
            
            // Aguardar resposta do usuário com timeout
            const timeoutPromise = new Promise((_, reject) => {
                setTimeout(() => {
                    reject(new Error('Timeout: prompt não respondeu em 30 segundos'));
                }, 30000);
            });
            
            const userChoicePromise = this.deferredPrompt.userChoice;
            console.log('[PWA Footer] Aguardando userChoice...');
            
            const { outcome } = await Promise.race([userChoicePromise, timeoutPromise]);
            
            console.log('[PWA Footer] Resultado da instalação:', outcome);
            
            if (outcome === 'accepted') {
                this.showSuccessMessage('App instalado com sucesso!');
                console.log('[PWA Footer] ✅ Instalação aceita pelo usuário');
            } else {
                console.log('[PWA Footer] ❌ Usuário rejeitou a instalação');
            }
            
            // Limpar deferred prompt
            this.deferredPrompt = null;
            this.updateInstallButton();
            
        } catch (error) {
            console.error('[PWA Footer] Erro durante instalação:', error);
            console.error('[PWA Footer] Tipo do erro:', error.name);
            console.error('[PWA Footer] Mensagem:', error.message);
            
            // Se for timeout, dar feedback específico
            if (error.message && error.message.includes('Timeout')) {
                this.showErrorMessage('O prompt de instalação não respondeu. Tente fechar o modal e clicar novamente.');
            } else {
                this.showErrorMessage('Erro ao instalar o app. Tente novamente.');
            }
            
            // Limpar deferred prompt mesmo em caso de erro
            this.deferredPrompt = null;
            this.updateInstallButton();
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
     * Mostrar diagnóstico completo de Installability
     */
    showDiagnostics(diagnostics = [], successes = [], solutions = [], report = null) {
        const modal = document.createElement('div');
        modal.className = 'pwa-help-modal';
        
        // Construir seção de informações do manifest
        let manifestInfo = '';
        if (report && report.manifestData) {
            const iconsList = report.manifestData.icons?.map(i => 
                `${i.sizes} (${i.purpose || 'any'}) - ${i.src}`
            ).join('<br>') || 'Nenhum';
            
            manifestInfo = `
                <div class="pwa-help-note" style="background: #e8f4f8; border-left-color: #3498db; margin-top: 20px;">
                    <i class="fas fa-file-code" style="color: #3498db;"></i>
                    <p><strong>Informações do Manifest:</strong></p>
                    <div style="margin-top: 10px; font-size: 13px; line-height: 1.8;">
                        <strong>URL:</strong> ${report.manifestUrl || 'N/A'}<br>
                        <strong>Nome:</strong> ${report.manifestData.name || 'N/A'}<br>
                        <strong>start_url:</strong> ${report.manifestData.start_url || 'N/A'}<br>
                        <strong>scope:</strong> ${report.manifestData.scope || 'N/A'}<br>
                        <strong>id:</strong> ${report.manifestData.id || 'N/A'}<br>
                        <strong>display:</strong> ${report.manifestData.display || 'N/A'}<br>
                        <strong>currentUrlInScope:</strong> ${report.currentUrlInScope ? '✅ true' : '❌ false'}<br>
                        <strong>Ícones:</strong><br>
                        <div style="margin-left: 20px; font-family: monospace; font-size: 11px;">
                            ${iconsList}
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Construir seção de motivos prováveis
        let probableReasons = '';
        if (report) {
            const reasons = [];
            
            if (!report.hasServiceWorkerController) {
                reasons.push('Service Worker não está controlando a página (requisito obrigatório)');
            }
            if (!report.currentUrlInScope && report.manifestData) {
                reasons.push(`URL atual (${report.currentPath}) não está no scope do manifest (${report.manifestData.scope})`);
            }
            if (!report.isSecureContext) {
                reasons.push('Não está em contexto seguro (HTTPS obrigatório)');
            }
            if (report.isStandalone) {
                reasons.push('App já está instalado como PWA');
            }
            if (report.installedRelatedApps && report.installedRelatedApps.length > 0) {
                reasons.push('App relacionado já está instalado');
            }
            if (report.manifestData && !report.manifestData.id) {
                reasons.push('Manifest sem id (pode causar conflito com outras instalações)');
            }
            if (report.manifestData && (!report.manifestData.icons || report.manifestData.icons.length === 0)) {
                reasons.push('Manifest sem ícones válidos');
            }
            
            // Se todos os requisitos estão OK mas beforeinstallprompt não dispara
            if (reasons.length === 0 && !window.__deferredPrompt) {
                reasons.push('Cooldown do Chrome: O prompt foi rejeitado anteriormente (pode durar até 24 horas)');
                reasons.push('App já instalado: Pode estar instalado mesmo que não apareça em getInstalledRelatedApps');
                reasons.push('Requisitos internos do Chrome: Alguns critérios não são visíveis publicamente');
                reasons.push('Cache do navegador: Versão antiga do manifest ou SW pode estar em cache');
            }
            
            if (reasons.length > 0) {
                probableReasons = `
                    <div class="pwa-help-note" style="background: #fff3cd; border-left-color: #ffc107; margin-top: 20px;">
                        <i class="fas fa-search" style="color: #ffc107;"></i>
                        <p><strong>Motivos Prováveis:</strong></p>
                        <ul style="list-style: none; padding: 0; margin: 10px 0 0 0;">
                            ${reasons.map(r => `<li style="padding: 6px 0; color: #856404;">
                                • ${r}
                            </li>`).join('')}
                        </ul>
                    </div>
                `;
            }
        }
        
        modal.innerHTML = `
            <div class="pwa-help-modal-content" style="max-width: 600px;">
                <div class="pwa-help-modal-header">
                    <h4>Diagnóstico PWA - Installability Report</h4>
                    <button class="pwa-help-modal-close" type="button">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="pwa-help-modal-body" style="max-height: 70vh; overflow-y: auto;">
                    ${successes.length > 0 ? `
                    <div class="pwa-help-note" style="background: #d4edda; border-left-color: #28a745;">
                        <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        <p><strong>Verificações OK:</strong></p>
                        <ul style="list-style: none; padding: 0; margin: 10px 0 0 0;">
                            ${successes.map(s => `<li style="padding: 6px 0; color: #28a745;">
                                ✅ ${s}
                            </li>`).join('')}
                        </ul>
                    </div>
                    ` : ''}
                    
                    ${diagnostics.length > 0 ? `
                    <div class="pwa-help-note" style="background: #fff3cd; border-left-color: #ffc107;">
                        <i class="fas fa-exclamation-triangle" style="color: #ffc107;"></i>
                        <p><strong>Problemas Detectados:</strong></p>
                        <ul style="list-style: none; padding: 0; margin: 20px 0;">
                            ${diagnostics.map(d => `<li style="padding: 8px 0; border-bottom: 1px solid #e1e5e9;">
                                <i class="fas fa-times-circle" style="color: #e74c3c; margin-right: 8px;"></i>
                                ${d}
                            </li>`).join('')}
                        </ul>
                    </div>
                    ` : ''}
                    
                    ${probableReasons}
                    
                    ${manifestInfo}
                    
                    ${solutions.length > 0 ? `
                    <div class="pwa-help-note" style="background: #d1ecf1; border-left-color: #0c5460; margin-top: 20px;">
                        <i class="fas fa-lightbulb" style="color: #0c5460;"></i>
                        <p><strong>Soluções Recomendadas:</strong></p>
                        <ul style="list-style: none; padding: 0; margin: 10px 0 0 0;">
                            ${solutions.map(s => `<li style="padding: 6px 0; color: #0c5460;">
                                💡 ${s}
                            </li>`).join('')}
                        </ul>
                    </div>
                    ` : ''}
                    
                    <div class="pwa-help-note" style="margin-top: 20px;">
                        <i class="fas fa-info-circle"></i>
                        <p>Verifique o console do navegador (F12) para mais detalhes técnicos do relatório completo.</p>
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
        console.log('[PWA Footer] Share click');
        
        const url = this.getAppUrl();
        const title = this.options.userType === 'aluno'
            ? 'CFC Bom Conselho • Aluno'
            : this.options.userType === 'instrutor'
            ? 'CFC Bom Conselho • Instrutor'
            : 'CFC Bom Conselho - Sistema';
        const text = this.options.userType === 'aluno' 
            ? 'Acesse o Portal do Aluno do CFC Bom Conselho'
            : this.options.userType === 'instrutor'
            ? 'Acesse o Portal do Instrutor do CFC Bom Conselho'
            : 'Acesse o site do CFC Bom Conselho';
        
        console.log('[PWA Footer] share url:', url);
        console.log('[PWA Footer] navigator.share available:', !!navigator.share);
        
        // Tentar Web Share API primeiro (mobile)
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
                    console.log('[PWA Footer] Erro ao compartilhar via Web Share API:', error);
                } else {
                    console.log('[PWA Footer] Usuário cancelou compartilhamento');
                    return;
                }
            }
        }
        
        // Fallback: mostrar opções (desktop ou sem Web Share API)
        console.log('[PWA Footer] fallback: showing share options');
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
                    <button class="pwa-share-option" data-action="copy" type="button" id="pwa-share-copy-btn">
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
        console.log('[PWA Footer] fallback: whatsapp');
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
        console.log('[PWA Footer] fallback: copy');
        console.log('[PWA Footer] copyToClipboard chamado, URL:', url);
        
        try {
            if (navigator.clipboard && navigator.clipboard.writeText) {
                await navigator.clipboard.writeText(url);
                console.log('[PWA Footer] Link copiado via Clipboard API');
                this.showSuccessMessage('Link copiado!');
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
        // Ocultar footer se existir
        const footer = document.querySelector('.pwa-install-footer');
        if (footer) {
            footer.style.display = 'none';
        }
        
        // Ocultar container também
        const container = document.querySelector(this.options.containerSelector || '.pwa-install-footer-container');
        if (container) {
            container.style.display = 'none';
            // Limpar conteúdo para não ocupar espaço
            container.innerHTML = '';
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
    
    // Verificar se há manifest na página
    const manifestLink = document.querySelector('link[rel="manifest"]');
    if (!manifestLink) {
        console.log('[PWA Footer] Manifest não encontrado. Componente não será inicializado.');
        return;
    }
    
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
