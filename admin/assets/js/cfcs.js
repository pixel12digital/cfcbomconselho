/**
 * JavaScript para gerenciamento de CFCs
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPICache = null;

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
    
    console.log('🌐 Caminho da API detectado:', caminhoAPICache);
    return caminhoAPICache;
}

// Função para fazer requisições à API
async function fetchAPI(endpoint = '', options = {}) {
    const baseApiUrl = await detectarCaminhoAPI();
    const url = baseApiUrl + endpoint;
    
    console.log('📡 Fazendo requisição para:', url);
    
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
            console.log('⏰ Timeout detectado, tentando novamente...');
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
    
    console.log('🔍 Verificando compatibilidade:', compatibilidade);
    
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
window.abrirModalCFC = function() {
    console.log('🚀 Abrindo modal de CFC...');
    
    const modal = document.getElementById('modalCFC');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        alert('Erro: Modal não encontrado na página!');
        return;
    }
    
    // Limpar formulário
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
    
    // Mostrar modal usando Bootstrap
    if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
        const bootstrapModal = new bootstrap.Modal(modal);
        bootstrapModal.show();
    } else {
        // Fallback para modal customizado
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    }
    
    console.log('✅ Modal aberto com sucesso!');
};

// Função para fechar modal de CFC
window.fecharModalCFC = function() {
    console.log('🚪 Fechando modal de CFC...');
    
    const modal = document.getElementById('modalCFC');
    if (modal) {
        // Fechar modal usando Bootstrap
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const bootstrapModal = bootstrap.Modal.getInstance(modal);
            if (bootstrapModal) {
                bootstrapModal.hide();
            }
        } else {
            // Fallback para modal customizado
            modal.style.display = 'none';
            document.body.style.overflow = 'auto';
        }
        console.log('✅ Modal fechado!');
    }
};

// Função para salvar CFC
window.salvarCFC = async function() {
    console.log('💾 Salvando CFC...');
    
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
        // Campos reais: id, nome, cnpj, endereco, telefone, email, responsavel, status, created_at, updated_at, responsavel_id, ativo
        const cfcData = {
            nome: sanitizarDados(formData.get('nome').trim()),
            cnpj: sanitizarDados(formData.get('cnpj').trim()),
            endereco: sanitizarDados(formData.get('endereco')?.trim() || ''),
            telefone: sanitizarDados(formData.get('telefone')?.trim() || ''),
            email: sanitizarDados(email || ''),
            responsavel_id: formData.get('responsavel_id') || null,
            ativo: formData.get('ativo') === '1'
        };
        
        // Adicionar campos opcionais se existirem no banco
        if (formData.get('cep')) {
            cfcData.cep = sanitizarDados(formData.get('cep').trim());
        }
        
        if (formData.get('bairro')) {
            cfcData.bairro = sanitizarDados(formData.get('bairro').trim());
        }
        
        if (formData.get('cidade')) {
            cfcData.cidade = sanitizarDados(formData.get('cidade').trim());
        }
        
        if (formData.get('uf')) {
            cfcData.uf = sanitizarDados(formData.get('uf'));
        }
        
        if (formData.get('observacoes')) {
            cfcData.observacoes = sanitizarDados(formData.get('observacoes').trim());
        }
        
        console.log('📤 Dados preparados para envio:', cfcData);
        
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

// Função para debug em produção
window.debugCFC = function() {
    console.group('🐛 Debug do Sistema CFC');
    
    // Verificar elementos
    const elementos = {
        modalCFC: document.getElementById('modalCFC'),
        formCFC: document.getElementById('formCFC'),
        btnSalvarCFC: document.getElementById('btnSalvarCFC'),
        modalVisualizarCFC: document.getElementById('modalVisualizarCFC'),
        modalVisualizarCFCBody: document.getElementById('modalVisualizarCFCBody')
    };
    
    console.log('🔍 Elementos encontrados:', elementos);
    
    // Verificar compatibilidade
    const compatibilidade = {
        fetch: typeof fetch !== 'undefined',
        abortSignal: typeof AbortSignal !== 'undefined' && AbortSignal.timeout,
        bootstrap: typeof bootstrap !== 'undefined' && bootstrap.Modal,
        formData: typeof FormData !== 'undefined'
    };
    
    console.log('🔍 Compatibilidade:', compatibilidade);
    
    // Verificar cache da API
    console.log('🌐 Cache da API:', caminhoAPICache);
    
    // Verificar variáveis globais
    console.log('🌍 Variáveis globais:', {
        cfcVisualizacaoAtual: window.cfcVisualizacaoAtual,
        abrirModalCFC: typeof window.abrirModalCFC,
        fecharModalCFC: typeof window.fecharModalCFC,
        salvarCFC: typeof window.salvarCFC,
        editarCFC: typeof window.editarCFC,
        excluirCFC: typeof window.excluirCFC
    });
    
    console.groupEnd();
    
    // Mostrar alerta com informações básicas
    const elementosFaltando = Object.entries(elementos)
        .filter(([nome, elemento]) => !elemento)
        .map(([nome]) => nome);
    
    if (elementosFaltando.length > 0) {
        alert(`⚠️ Elementos faltando: ${elementosFaltando.join(', ')}\n\nVerifique o console para mais detalhes.`);
    } else {
        alert('✅ Todos os elementos encontrados!\n\nVerifique o console para mais detalhes.');
    }
};

// Função para testar a API
window.testarAPICFC = async function() {
    console.log('🧪 Testando API de CFCs...');
    
    try {
        // Testar busca de um CFC específico
        const response = await fetchAPI('?id=34');
        const data = await response.json();
        
        console.log('📊 Resposta da API:', data);
        console.log('📋 Estrutura dos dados:', JSON.stringify(data, null, 2));
        
        if (data.success && data.data) {
            const cfc = data.data;
            console.log('✅ CFC encontrado:', cfc);
            console.log('📝 Campos disponíveis:', Object.keys(cfc));
            
            // Mostrar valores de cada campo
            Object.keys(cfc).forEach(key => {
                console.log(`  ${key}: ${cfc[key]} (tipo: ${typeof cfc[key]})`);
            });
        } else {
            console.error('❌ API não retornou dados válidos');
        }
    } catch (error) {
        console.error('❌ Erro ao testar API:', error);
    }
};

// Função para editar CFC
window.editarCFC = async function(id) {
    console.log('✏️ Editando CFC ID:', id);
    
    try {
        const response = await fetchAPI(`?id=${id}`);
        const data = await response.json();
        
        console.log('📊 Resposta da API:', data);
        
        if (data.success) {
            const cfc = data.data;
            console.log('📋 Dados do CFC recebidos:', cfc);
            
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
                // Baseado na estrutura real do banco (cfcs table)
                // Campos reais: id, nome, cnpj, endereco, telefone, email, responsavel, status, created_at, updated_at, responsavel_id, ativo
                
                if (nomeField) {
                    nomeField.value = cfc.nome || '';
                    console.log('✅ Campo nome preenchido:', cfc.nome);
                }
                
                if (cnpjField) {
                    cnpjField.value = cfc.cnpj || '';
                    console.log('✅ Campo CNPJ preenchido:', cfc.cnpj);
                }
                
                if (razaoSocialField) {
                    // Campo razao_social não existe no banco, usar nome como fallback
                    razaoSocialField.value = cfc.nome || '';
                    console.log('✅ Campo razão social preenchido (fallback para nome):', cfc.nome);
                }
                
                if (emailField) {
                    emailField.value = cfc.email || '';
                    console.log('✅ Campo email preenchido:', cfc.email);
                }
                
                if (telefoneField) {
                    telefoneField.value = cfc.telefone || '';
                    console.log('✅ Campo telefone preenchido:', cfc.telefone);
                }
                
                if (cepField) {
                    // Campo CEP não existe no banco
                    cepField.value = '';
                    console.log('⚠️ Campo CEP não existe no banco, deixando vazio');
                }
                
                if (enderecoField) {
                    enderecoField.value = cfc.endereco || '';
                    console.log('✅ Campo endereço preenchido:', cfc.endereco);
                }
                
                if (bairroField) {
                    // Campo bairro não existe no banco
                    bairroField.value = '';
                    console.log('⚠️ Campo bairro não existe no banco, deixando vazio');
                }
                
                if (cidadeField) {
                    // Campo cidade não existe no banco
                    cidadeField.value = '';
                    console.log('⚠️ Campo cidade não existe no banco, deixando vazio');
                }
                
                if (ufField) {
                    // Campo UF não existe no banco
                    ufField.value = '';
                    console.log('⚠️ Campo UF não existe no banco, deixando vazio');
                }
                
                if (responsavelField) {
                    responsavelField.value = cfc.responsavel_id || '';
                    console.log('✅ Campo responsável preenchido:', cfc.responsavel_id);
                }
                
                if (ativoField) {
                    // Converter para string '1' ou '0' para o select
                    const ativoValue = cfc.ativo ? '1' : '0';
                    ativoField.value = ativoValue;
                    console.log('✅ Campo ativo preenchido:', ativoValue, '(', cfc.ativo, ')');
                }
                
                if (observacoesField) {
                    // Campo observações não existe no banco
                    observacoesField.value = '';
                    console.log('⚠️ Campo observações não existe no banco, deixando vazio');
                }
                
                // Configurar modal para edição
                const modalTitle = document.getElementById('modalTitle');
                const acaoField = document.getElementById('acaoCFC');
                const cfcIdField = document.getElementById('cfc_id');
                
                if (modalTitle) {
                    modalTitle.textContent = 'Editar CFC';
                    console.log('✅ Título do modal alterado para: Editar CFC');
                }
                
                if (acaoField) {
                    acaoField.value = 'editar';
                    console.log('✅ Campo ação definido como: editar');
                }
                
                if (cfcIdField) {
                    cfcIdField.value = id;
                    console.log('✅ Campo ID do CFC definido como:', id);
                }
                
                // Abrir modal
                console.log('🚀 Abrindo modal de edição...');
                abrirModalCFC();
                
                console.log('✅ Formulário preenchido com dados do CFC:', cfc);
            } else {
                throw new Error('Formulário não encontrado');
            }
        } else {
            console.error('❌ API retornou erro:', data.error);
            alert('Erro ao carregar dados do CFC: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao editar CFC:', error);
        alert('Erro ao carregar dados do CFC: ' + error.message);
    }
};

// Função para excluir CFC
window.excluirCFC = async function(id) {
    console.log('🗑️ Excluindo CFC ID:', id);
    
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
    console.log('✏️ Editando CFC da visualização...');
    
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
    console.log('👁️ Visualizando CFC ID:', id);
    
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
                    
                    console.log('✅ Modal de visualização aberto com dados do CFC:', cfc);
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
    console.log('🚀 Inicializando sistema de CFCs...');
    
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
    
    console.log('🔍 Verificando elementos essenciais:', elementosEssenciais);
    
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
    
    console.log('✅ Sistema de CFCs inicializado!');
});

console.log('📋 Arquivo cfcs.js carregado!');
