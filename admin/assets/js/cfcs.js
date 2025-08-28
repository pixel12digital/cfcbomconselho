/**
 * JavaScript para gerenciamento de CFCs
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPICache = null;

    // Verificar se já foi carregado para evitar duplicação
    if (window.cfcsSystemLoaded) {
        console.warn('⚠️ Sistema CFC já foi carregado anteriormente. Ignorando carregamento duplicado.');
    } else {
        window.cfcsSystemLoaded = true;
        
        // CRÍTICO: Listener global para remover backdrop
        document.addEventListener('DOMContentLoaded', function() {
            // Observer para detectar quando modais são abertos
            const observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.classList && node.classList.contains('modal-backdrop')) {
                                // Remover backdrop imediatamente
                                node.style.display = 'none';
                                node.style.opacity = '0';
                                node.style.visibility = 'hidden';
                                node.style.pointerEvents = 'none';
                            }
                        });
                    }
                });
            });
            
            // Observar mudanças no DOM
            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
            
            // Remover backdrop existente se houver
            const existingBackdrops = document.querySelectorAll('.modal-backdrop');
            existingBackdrops.forEach(backdrop => {
                backdrop.style.display = 'none';
                backdrop.style.opacity = '0';
                backdrop.style.visibility = 'hidden';
                backdrop.style.pointerEvents = 'none';
            });
        });
    
    // Função para sanitizar dados de entrada
    function sanitizarDados(texto) {
        if (typeof texto !== 'string') {
            return texto;
        }
        
        // Remover tags HTML e scripts
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#x27;')
            .replace(/\//g, '&#x2F;');
    }

    // Função para validar CNPJ
    function validarCNPJ(cnpj) {
        // Remove caracteres não numéricos
        cnpj = cnpj.replace(/\D/g, '');
        
        // Verifica se tem 14 dígitos
        if (cnpj.length !== 14) {
            return false;
        }
        
        // Verifica se todos os dígitos são iguais
        if (/^(\d)\1+$/.test(cnpj)) {
            return false;
        }
        
        // Validação dos dígitos verificadores
        let soma = 0;
        let peso = 2;
        
        // Primeiro dígito verificador
        for (let i = 11; i >= 0; i--) {
            soma += parseInt(cnpj.charAt(i)) * peso;
            peso = peso === 9 ? 2 : peso + 1;
        }
        
        let digito = 11 - (soma % 11);
        if (digito > 9) digito = 0;
        
        if (parseInt(cnpj.charAt(12)) !== digito) {
            return false;
        }
        
        // Segundo dígito verificador
        soma = 0;
        peso = 2;
        
        for (let i = 12; i >= 0; i--) {
            soma += parseInt(cnpj.charAt(i)) * peso;
            peso = peso === 9 ? 2 : peso + 1;
        }
        
        digito = 11 - (soma % 11);
        if (digito > 9) digito = 0;
        
        return parseInt(cnpj.charAt(13)) === digito;
    }

    // Função para detectar o caminho correto da API
    async function detectarCaminhoAPI() {
        if (caminhoAPICache) {
            return caminhoAPICache;
        }
        
        const baseUrl = window.location.origin;
        const pathname = window.location.pathname;
        
        // Detectar caminho baseado na URL atual
        if (pathname.includes('/admin/')) {
            const basePath = pathname.substring(0, pathname.lastIndexOf('/admin/'));
            caminhoAPICache = baseUrl + basePath + '/admin/api/cfcs.php';
        } else {
            caminhoAPICache = baseUrl + '/admin/api/cfcs.php';
        }
        
        return caminhoAPICache;
    }

    // Função para fazer requisições à API
    async function fetchAPI(endpoint = '', options = {}) {
        const baseApiUrl = await detectarCaminhoAPI();
        const url = baseApiUrl + endpoint;
        
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin',
            signal: AbortSignal.timeout(30000) // Timeout de 30 segundos
        };
        
        const mergedOptions = {
            ...defaultOptions,
            ...options,
            headers: {
                ...defaultOptions.headers,
                ...options.headers
            }
        };
        
        try {
            const response = await fetch(url, mergedOptions);
            
            if (!response.ok) {
                let errorMessage = `HTTP ${response.status}: ${response.statusText}`;
                
                // Tentar obter detalhes do erro da resposta
                try {
                    const errorData = await response.json();
                    if (errorData.error) {
                        errorMessage += ` - ${errorData.error}`;
                    }
                } catch (e) {
                    // Se não conseguir ler JSON, usar status text
                }
                
                throw new Error(errorMessage);
            }
            
            return response;
        } catch (error) {
            console.error('❌ Erro na requisição:', error);
            
            // Se for erro de timeout, tentar novamente uma vez
            if (error.name === 'TimeoutError') {
                try {
                    const retryOptions = { ...mergedOptions };
                    delete retryOptions.signal; // Remover timeout para retry
                    
                    const retryResponse = await fetch(url, retryOptions);
                    if (!retryResponse.ok) {
                        throw new Error(`HTTP ${retryResponse.status}: ${retryResponse.statusText}`);
                    }
                    return retryResponse;
                } catch (retryError) {
                    console.error('❌ Erro no retry:', retryError);
                    throw new Error(`Falha após retry: ${retryError.message}`);
                }
            }
            
            throw error;
        }
    }

    // Função para verificar compatibilidade do navegador
    function verificarCompatibilidade() {
        const compatibilidade = {
            fetch: typeof fetch !== 'undefined',
            abortSignal: typeof AbortSignal !== 'undefined' && AbortSignal.timeout,
            bootstrap: typeof bootstrap !== 'undefined' && bootstrap.Modal,
            formData: typeof FormData !== 'undefined'
        };
        
        if (!compatibilidade.fetch) {
            console.error('❌ Fetch API não suportada neste navegador');
            alert('Seu navegador não suporta as funcionalidades necessárias. Atualize para uma versão mais recente.');
            return false;
        }
        
        if (!compatibilidade.formData) {
            console.error('❌ FormData não suportado neste navegador');
            alert('Seu navegador não suporta FormData. Atualize para uma versão mais recente.');
            return false;
        }
        
        return true;
    }

    // Função para abrir modal de CFC
    window.abrirModalCFC = function(modo = 'criar') {
        const modal = document.getElementById('modalCFC');
        if (!modal) {
            console.error('❌ Modal não encontrado!');
            alert('Erro: Modal não encontrado na página!');
            return;
        }
        
        // Limpar formulário apenas se for para criar novo CFC
        if (modo === 'criar') {
            const form = document.getElementById('formCFC');
            if (form) {
                form.reset();
                const acaoField = document.getElementById('acaoCFC');
                const cfcIdField = document.getElementById('cfc_id');
                const modalTitle = document.getElementById('modalTitle');
                
                if (acaoField) acaoField.value = 'criar';
                if (cfcIdField) cfcIdField.value = '';
                if (modalTitle) modalTitle.textContent = 'Novo CFC';
            }
        }
        
        // SOLUÇÃO CRÍTICA: Forçar estilos CSS antes de abrir o modal
        const modalDialog = modal.querySelector('.modal-dialog');
        if (modalDialog) {
            // Aplicar estilos inline para sobrescrever qualquer CSS
            modalDialog.style.setProperty('max-width', '1200px', 'important');
            modalDialog.style.setProperty('width', '1200px', 'important');
            modalDialog.style.setProperty('margin', '2rem auto', 'important');
            modalDialog.style.setProperty('position', 'relative', 'important');
            modalDialog.style.setProperty('z-index', '1056', 'important');
            
            // Aplicar estilos responsivos
            if (window.innerWidth <= 1400) {
                modalDialog.style.setProperty('max-width', '95vw', 'important');
                modalDialog.style.setProperty('width', '95vw', 'important');
                modalDialog.style.setProperty('margin', '1.5rem auto', 'important');
            }
            if (window.innerWidth <= 1200) {
                modalDialog.style.setProperty('max-width', '90vw', 'important');
                modalDialog.style.setProperty('width', '90vw', 'important');
                modalDialog.style.setProperty('margin', '1rem auto', 'important');
            }
            if (window.innerWidth <= 768) {
                modalDialog.style.setProperty('max-width', '95vw', 'important');
                modalDialog.style.setProperty('width', '95vw', 'important');
                modalDialog.style.setProperty('margin', '0.5rem auto', 'important');
            }
            if (window.innerWidth <= 576) {
                modalDialog.style.setProperty('max-width', '98vw', 'important');
                modalDialog.style.setProperty('width', '98vw', 'important');
                modalDialog.style.setProperty('margin', '0.25rem auto', 'important');
            }
        }
        
        // Mostrar modal usando Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bootstrapModal = new bootstrap.Modal(modal, {
                backdrop: false, // CRÍTICO: Desabilitar backdrop
                keyboard: true
            });
            bootstrapModal.show();
            
            // CRÍTICO: Remover backdrop programaticamente
            setTimeout(() => {
                // Remover todos os backdrops que possam ter sido criados
                const backdrops = document.querySelectorAll('.modal-backdrop');
                backdrops.forEach(backdrop => {
                    backdrop.style.display = 'none';
                    backdrop.style.opacity = '0';
                    backdrop.style.visibility = 'hidden';
                    backdrop.style.pointerEvents = 'none';
                });
                
                // Remover classe 'modal-open' do body se existir
                if (document.body.classList.contains('modal-open')) {
                    document.body.classList.remove('modal-open');
                }
                
                // Aplicar estilos finais para garantir funcionamento
                if (modalDialog) {
                    modalDialog.style.setProperty('max-width', '1200px', 'important');
                    modalDialog.style.setProperty('width', '1200px', 'important');
                    modalDialog.style.setProperty('margin', '2rem auto', 'important');
                    modalDialog.style.setProperty('position', 'relative', 'important');
                    modalDialog.style.setProperty('left', 'auto', 'important');
                    modalDialog.style.setProperty('right', 'auto', 'important');
                    modalDialog.style.setProperty('transform', 'none', 'important');
                    
                    // DEBUG: Verificar se os estilos foram aplicados
                    console.log('🔍 DEBUG Modal Width:', {
                        computedWidth: window.getComputedStyle(modalDialog).width,
                        computedMaxWidth: window.getComputedStyle(modalDialog).maxWidth,
                        offsetWidth: modalDialog.offsetWidth,
                        clientWidth: modalDialog.clientWidth,
                        inlineWidth: modalDialog.style.width,
                        inlineMaxWidth: modalDialog.style.maxWidth
                    });
                }

                // NOVA SOLUÇÃO: Observer para garantir que a largura seja mantida
                const widthObserver = new MutationObserver((mutations) => {
                    mutations.forEach((mutation) => {
                        if (mutation.type === 'attributes' && 
                            (mutation.attributeName === 'style' || mutation.attributeName === 'class')) {
                            
                            const currentWidth = window.getComputedStyle(modalDialog).width;
                            const currentMaxWidth = window.getComputedStyle(modalDialog).maxWidth;
                            
                            // Se a largura foi alterada, forçar novamente
                            if (currentWidth !== '1200px' || currentMaxWidth !== '1200px') {
                                console.log('🔄 Forçando largura novamente:', { currentWidth, currentMaxWidth });
                                modalDialog.style.setProperty('max-width', '1200px', 'important');
                                modalDialog.style.setProperty('width', '1200px', 'important');
                                modalDialog.style.setProperty('min-width', '1200px', 'important');
                            }
                        }
                    });
                });

                // Observar mudanças no modal-dialog
                widthObserver.observe(modalDialog, {
                    attributes: true,
                    attributeFilter: ['style', 'class']
                });

                // Aplicar largura a cada 100ms por 2 segundos para garantir
                let attempts = 0;
                const widthInterval = setInterval(() => {
                    if (attempts >= 20) {
                        clearInterval(widthInterval);
                        return;
                    }
                    
                    modalDialog.style.setProperty('max-width', '1200px', 'important');
                    modalDialog.style.setProperty('width', '1200px', 'important');
                    modalDialog.style.setProperty('min-width', '1200px', 'important');
                    
                    attempts++;
                }, 100);

            }, 100);
        } else {
            // Fallback para modal customizado
            modal.style.display = 'block';
            document.body.style.overflow = 'visible'; // Não bloquear scroll
        }
    };

    // Função para fechar modal de CFC
    window.fecharModalCFC = function() {
        const modal = document.getElementById('modalCFC');
        if (modal) {
            // Fechar modal usando Bootstrap
            if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                const bootstrapModal = bootstrap.Modal.getInstance(modal);
                if (bootstrapModal) {
                    bootstrapModal.hide();
                }
                
                // CRÍTICO: Remover backdrop ao fechar
                setTimeout(() => {
                    const backdrops = document.querySelectorAll('.modal-backdrop');
                    backdrops.forEach(backdrop => {
                        backdrop.style.display = 'none';
                        backdrop.style.opacity = '0';
                        backdrop.style.visibility = 'hidden';
                        backdrop.style.pointerEvents = 'none';
                    });
                    
                    // Remover classe 'modal-open' do body
                    if (document.body.classList.contains('modal-open')) {
                        document.body.classList.remove('modal-open');
                    }
                }, 100);
            } else {
                // Fallback para modal customizado
                modal.style.display = 'none';
                document.body.style.overflow = 'visible';
            }
        }
    };

    // Função para salvar CFC
    window.salvarCFC = async function() {
        try {
            const form = document.getElementById('formCFC');
            if (!form) {
                throw new Error('Formulário não encontrado');
            }
            
            const formData = new FormData(form);
            
            // Validações básicas dos campos obrigatórios
            if (!formData.get('nome') || !formData.get('nome').trim()) {
                alert('Nome do CFC é obrigatório');
                return;
            }
            
            if (!formData.get('cnpj') || !formData.get('cnpj').trim()) {
                alert('CNPJ é obrigatório');
                return;
            }
            
            // Validação básica de CNPJ (formato)
            const cnpj = formData.get('cnpj').trim().replace(/\D/g, '');
            if (cnpj.length !== 14) {
                alert('CNPJ deve ter 14 dígitos');
                return;
            }
            
            // Validação completa do CNPJ
            if (!validarCNPJ(formData.get('cnpj').trim())) {
                alert('CNPJ inválido. Verifique os dígitos verificadores.');
                return;
            }
            
            // Validação básica de email se fornecido
            const email = formData.get('email')?.trim();
            if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                alert('E-mail inválido');
                return;
            }
            
            // Preparar dados baseado na estrutura real do banco
            const cfcData = {
                nome: sanitizarDados(formData.get('nome').trim()),
                cnpj: sanitizarDados(formData.get('cnpj').trim()),
                endereco: sanitizarDados(formData.get('endereco')?.trim() || ''),
                telefone: sanitizarDados(formData.get('telefone')?.trim() || ''),
                email: sanitizarDados(email || ''),
                responsavel_id: formData.get('responsavel_id') || null,
                ativo: formData.get('ativo') === '1',
                cidade: sanitizarDados(formData.get('cidade')?.trim() || ''),
                uf: sanitizarDados(formData.get('uf') || ''),
                cep: sanitizarDados(formData.get('cep')?.trim() || ''),
                bairro: sanitizarDados(formData.get('bairro')?.trim() || ''),
                observacoes: sanitizarDados(formData.get('observacoes')?.trim() || '')
            };
            
            const acao = formData.get('acao');
            const cfc_id = formData.get('cfc_id');
            
            if (acao === 'editar' && cfc_id) {
                cfcData.id = cfc_id;
            }
            
            // Mostrar loading no botão
            const btnSalvar = document.getElementById('btnSalvarCFC');
            if (btnSalvar) {
                const originalText = btnSalvar.innerHTML;
                btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
                btnSalvar.disabled = true;
                
                try {
                    const method = acao === 'editar' ? 'PUT' : 'POST';
                    const endpoint = acao === 'editar' ? `?id=${cfc_id}` : '';
                    
                    const response = await fetchAPI(endpoint, {
                        method: method,
                        body: JSON.stringify(cfcData)
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        alert(data.message || 'CFC salvo com sucesso!');
                        fecharModalCFC();
                        
                        // Recarregar página
                        setTimeout(() => {
                            window.location.reload();
                        }, 1000);
                    } else {
                        alert(data.error || 'Erro ao salvar CFC');
                    }
                } catch (error) {
                    console.error('❌ Erro ao salvar:', error);
                    alert('Erro ao salvar CFC: ' + error.message);
                } finally {
                    btnSalvar.innerHTML = originalText;
                    btnSalvar.disabled = false;
                }
            }
            
        } catch (error) {
            console.error('❌ Erro na função salvarCFC:', error);
            alert('Erro interno: ' + error.message);
        }
    };

    // Função para editar CFC
    window.editarCFC = async function(id) {
        try {
            const response = await fetchAPI(`?id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                const cfc = data.data;
                
                // Preencher formulário
                const form = document.getElementById('formCFC');
                if (form) {
                    // Limpar formulário primeiro
                    form.reset();
                    
                    // Declarar todas as variáveis dos campos do formulário
                    const nomeField = document.getElementById('nome');
                    const cnpjField = document.getElementById('cnpj');
                    const razaoSocialField = document.getElementById('razao_social');
                    const emailField = document.getElementById('email');
                    const telefoneField = document.getElementById('telefone');
                    const cepField = document.getElementById('cep');
                    const enderecoField = document.getElementById('endereco');
                    const bairroField = document.getElementById('bairro');
                    const cidadeField = document.getElementById('cidade');
                    const ufField = document.getElementById('uf');
                    const responsavelField = document.getElementById('responsavel_id');
                    const ativoField = document.getElementById('ativo');
                    const observacoesField = document.getElementById('observacoes');
                    
                    // Mapear campos do banco para os campos do formulário
                    if (nomeField) nomeField.value = cfc.nome || '';
                    if (cnpjField) cnpjField.value = cfc.cnpj || '';
                    if (razaoSocialField) razaoSocialField.value = cfc.razao_social || '';
                    if (emailField) emailField.value = cfc.email || '';
                    if (telefoneField) telefoneField.value = cfc.telefone || '';
                    if (cepField) cepField.value = cfc.cep || '';
                    if (enderecoField) enderecoField.value = cfc.endereco || '';
                    if (bairroField) bairroField.value = cfc.bairro || '';
                    if (cidadeField) cidadeField.value = cfc.cidade || '';
                    if (ufField) ufField.value = cfc.uf || '';
                    if (responsavelField) responsavelField.value = cfc.responsavel_id || '';
                    if (ativoField) ativoField.value = cfc.ativo ? '1' : '0';
                    if (observacoesField) observacoesField.value = cfc.observacoes || '';
                    
                    // Configurar modal para edição
                    const modalTitle = document.getElementById('modalTitle');
                    const acaoField = document.getElementById('acaoCFC');
                    const cfcIdField = document.getElementById('cfc_id');
                    
                    if (modalTitle) modalTitle.textContent = 'Editar CFC';
                    if (acaoField) acaoField.value = 'editar';
                    if (cfcIdField) cfcIdField.value = id;
                    
                    // Abrir modal
                    const modal = document.getElementById('modalCFC');
                    if (modal && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        const bootstrapModal = new bootstrap.Modal(modal);
                        bootstrapModal.show();
                    } else {
                        // Fallback para modal customizado
                        modal.style.display = 'block';
                        document.body.style.overflow = 'hidden';
                    }
                } else {
                    throw new Error('Formulário não encontrado');
                }
            } else {
                alert('Erro ao carregar dados do CFC: ' + (data.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('❌ Erro ao editar CFC:', error);
            alert('Erro ao carregar dados do CFC: ' + error.message);
        }
    };

    // Função para excluir CFC
    window.excluirCFC = async function(id) {
        // Usar confirm nativo do navegador em vez de createModal
        if (!confirm('⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este CFC?')) {
            return;
        }
        
        try {
            const response = await fetchAPI(`?id=${id}`, {
                method: 'DELETE'
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(data.message || 'CFC excluído com sucesso!');
                
                // Recarregar página
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Erro ao excluir CFC: ' + (data.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('❌ Erro ao excluir CFC:', error);
            alert('Erro ao excluir CFC: ' + error.message);
        }
    };

    // Função para editar CFC da visualização
    window.editarCFCDaVisualizacao = function() {
        // Fechar modal de visualização
        const modalVisualizacao = document.getElementById('modalVisualizarCFC');
        if (modalVisualizacao && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const modal = bootstrap.Modal.getInstance(modalVisualizacao);
            if (modal) {
                modal.hide();
            }
        }
        
        // Obter ID do CFC do botão (vamos armazenar temporariamente)
        const cfcId = window.cfcVisualizacaoAtual;
        if (cfcId) {
            // Aguardar um pouco para o modal fechar antes de abrir o de edição
            setTimeout(() => {
                editarCFC(cfcId);
            }, 300);
        } else {
            alert('Erro: ID do CFC não encontrado');
        }
    };

    // Função para visualizar CFC
    window.visualizarCFC = async function(id) {
        // Armazenar ID para uso na edição
        window.cfcVisualizacaoAtual = id;
        
        try {
            const response = await fetchAPI(`?id=${id}`);
            const data = await response.json();
            
            if (data.success) {
                const cfc = data.data;
                
                // Criar conteúdo do modal de visualização
                const modalBody = document.getElementById('modalVisualizarCFCBody');
                if (modalBody) {
                    modalBody.innerHTML = `
                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Informações Básicas</h6>
                                <p><strong>Nome:</strong> ${cfc.nome || 'Não informado'}</p>
                                <p><strong>CNPJ:</strong> ${cfc.cnpj || 'Não informado'}</p>
                                <p><strong>Razão Social:</strong> ${cfc.razao_social || 'Não informado'}</p>
                                <p><strong>Status:</strong> 
                                    <span class="badge ${cfc.ativo ? 'bg-success' : 'bg-danger'}">
                                        ${cfc.ativo ? 'ATIVO' : 'INATIVO'}
                                    </span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Contato</h6>
                                <p><strong>E-mail:</strong> ${cfc.email || 'Não informado'}</p>
                                <p><strong>Telefone:</strong> ${cfc.telefone || 'Não informado'}</p>
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Endereço</h6>
                                <p><strong>CEP:</strong> ${cfc.cep || 'Não informado'}</p>
                                <p><strong>Endereço:</strong> ${cfc.endereco || 'Não informado'}</p>
                                <p><strong>Bairro:</strong> ${cfc.bairro || 'Não informado'}</p>
                                <p><strong>Cidade:</strong> ${cfc.cidade || 'Não informado'}</p>
                                <p><strong>UF:</strong> ${cfc.uf || 'Não informado'}</p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="fw-bold text-primary">Informações Adicionais</h6>
                                <p><strong>Responsável:</strong> ${cfc.responsavel_nome || 'Não definido'}</p>
                                <p><strong>Criado em:</strong> ${cfc.criado_em ? new Date(cfc.criado_em).toLocaleDateString('pt-BR') : 'Não informado'}</p>
                                <p><strong>Observações:</strong> ${cfc.observacoes || 'Nenhuma observação'}</p>
                            </div>
                        </div>
                    `;
                    
                    // Abrir modal de visualização usando Bootstrap
                    const modalElement = document.getElementById('modalVisualizarCFC');
                    if (modalElement) {
                        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                            const modal = new bootstrap.Modal(modalElement);
                            modal.show();
                        } else {
                            // Fallback para modal customizado
                            modalElement.style.display = 'block';
                            document.body.style.overflow = 'hidden';
                        }
                    } else {
                        throw new Error('Elemento do modal de visualização não encontrado');
                    }
                } else {
                    throw new Error('Corpo do modal de visualização não encontrado');
                }
            } else {
                alert('Erro ao carregar dados do CFC: ' + (data.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error('❌ Erro ao visualizar CFC:', error);
            alert('Erro ao carregar dados do CFC: ' + error.message);
        }
    };

    // Função para ativar/desativar CFC
    window.ativarCFC = async function(id) {
        await alterarStatusCFC(id, 1);
    };

    window.desativarCFC = async function(id) {
        await alterarStatusCFC(id, 0);
    };

    async function alterarStatusCFC(id, status) {
        const acao = status ? 'ativar' : 'desativar';
        const mensagem = status ? 'Deseja realmente ativar este CFC?' : 'Deseja realmente desativar este CFC?';
        
        if (!confirm(mensagem)) {
            return;
        }
        
        try {
            const response = await fetchAPI(`?id=${id}`, {
                method: 'PUT',
                body: JSON.stringify({ ativo: status })
            });
            
            const data = await response.json();
            
            if (data.success) {
                alert(`CFC ${acao}do com sucesso!`);
                
                // Recarregar página
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert(`Erro ao ${acao} CFC: ` + (data.error || 'Erro desconhecido'));
            }
        } catch (error) {
            console.error(`❌ Erro ao ${acao} CFC:`, error);
            alert(`Erro ao ${acao} CFC: ` + error.message);
        }
    }

    // Função para gerenciar CFC
    window.gerenciarCFC = function(id) {
        window.location.href = `pages/gerenciar-cfc.php?id=${id}`;
    };

    // Inicialização quando o DOM estiver pronto
    document.addEventListener('DOMContentLoaded', function() {
        // Verificar compatibilidade do navegador
        if (!verificarCompatibilidade()) {
            console.error('❌ Sistema não pode ser inicializado devido a incompatibilidade');
            return;
        }
        
        // Verificar elementos essenciais
        const elementosEssenciais = {
            modalCFC: document.getElementById('modalCFC'),
            formCFC: document.getElementById('formCFC'),
            btnSalvarCFC: document.getElementById('btnSalvarCFC')
        };
        
        // Verificar se todos os elementos essenciais estão presentes
        const elementosFaltando = Object.entries(elementosEssenciais)
            .filter(([nome, elemento]) => !elemento)
            .map(([nome]) => nome);
        
        if (elementosFaltando.length > 0) {
            console.error('❌ Elementos essenciais não encontrados:', elementosFaltando);
            console.warn('⚠️ Algumas funcionalidades podem não funcionar corretamente');
        }
        
        // Event listeners para o modal
        if (elementosEssenciais.modalCFC) {
            // Fechar modal ao clicar fora
            elementosEssenciais.modalCFC.addEventListener('click', function(e) {
                if (e.target === elementosEssenciais.modalCFC) {
                    fecharModalCFC();
                }
            });
        }
        
        // Event listener para o formulário
        if (elementosEssenciais.formCFC) {
            elementosEssenciais.formCFC.addEventListener('submit', function(e) {
                e.preventDefault();
                salvarCFC();
            });
        }
        
        // Event listener para o botão de salvar
        if (elementosEssenciais.btnSalvarCFC) {
            elementosEssenciais.btnSalvarCFC.addEventListener('click', function(e) {
                e.preventDefault();
                salvarCFC();
            });
        }
        
        // Event listener para ESC fechar modal
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('modalCFC');
                if (modal && (modal.style.display === 'block' || modal.classList.contains('show'))) {
                    fecharModalCFC();
                }
            }
        });
    });
}

// Função para testar edição de CFC
window.testarEdicaoCFC = async function(id = 35) {
    console.group('🧪 Teste de Edição de CFC');
    console.log('🎯 Testando edição do CFC ID:', id);
    
    try {
        // Simular clique no botão editar
        console.log('📱 Simulando edição...');
        await editarCFC(id);
        
        // Aguardar um pouco e verificar estado do modal
        setTimeout(() => {
            const modal = document.getElementById('modalCFC');
            if (modal) {
                const isVisible = modal.classList.contains('show') || modal.style.display === 'block';
                console.log(`📱 Modal CFC visível: ${isVisible ? 'SIM' : 'NÃO'}`);
                console.log(`📱 Classes do modal:`, modal.className);
                console.log(`📱 Estilo display:`, modal.style.display);
                
                if (isVisible) {
                    console.log('✅ Modal está visível - edição funcionando!');
                } else {
                    console.error('❌ Modal não está visível - problema na edição!');
                }
            }
        }, 1000);
        
    } catch (error) {
        console.error('❌ Erro no teste de edição:', error);
    }
    
    console.groupEnd();
};

// Função para verificar scripts duplicados
window.verificarScriptsDuplicados = function() {
    console.group('🔍 Verificação de Scripts Duplicados');
    
    const scripts = document.querySelectorAll('script[src]');
    const scriptCounts = {};
    
    scripts.forEach(script => {
        const src = script.src;
        if (scriptCounts[src]) {
            scriptCounts[src]++;
        } else {
            scriptCounts[src] = 1;
        }
    });
    
    const duplicados = Object.entries(scriptCounts)
        .filter(([src, count]) => count > 1)
        .map(([src, count]) => ({ src, count }));
    
    if (duplicados.length > 0) {
        console.warn('⚠️ Scripts duplicados encontrados:', duplicados);
        console.warn('💡 Recomendação: Remover scripts duplicados do HTML');
    } else {
        console.log('✅ Nenhum script duplicado encontrado');
    }
    
    console.groupEnd();
    return duplicados;
};

// Verificar scripts duplicados automaticamente
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', verificarScriptsDuplicados);
} else {
    verificarScriptsDuplicados();
}
