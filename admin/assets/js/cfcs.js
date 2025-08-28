/**
 * JavaScript para gerenciamento de CFCs
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPICache = null;

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
        credentials: 'same-origin'
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
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response;
    } catch (error) {
        console.error('❌ Erro na requisição:', error);
        throw error;
    }
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
        
        // Validações
        if (!formData.get('nome').trim()) {
            alert('Nome do CFC é obrigatório');
            return;
        }
        
        if (!formData.get('cnpj').trim()) {
            alert('CNPJ é obrigatório');
            return;
        }
        
        if (!formData.get('cidade').trim()) {
            alert('Cidade é obrigatória');
            return;
        }
        
        if (!formData.get('uf')) {
            alert('UF é obrigatória');
            return;
        }
        
        // Preparar dados
        const cfcData = {
            nome: formData.get('nome').trim(),
            cnpj: formData.get('cnpj').trim(),
            razao_social: formData.get('razao_social').trim() || formData.get('nome').trim(),
            email: formData.get('email').trim(),
            telefone: formData.get('telefone').trim(),
            cep: formData.get('cep').trim(),
            endereco: formData.get('endereco').trim(),
            bairro: formData.get('bairro').trim(),
            cidade: formData.get('cidade').trim(),
            uf: formData.get('uf'),
            responsavel_id: formData.get('responsavel_id') || null,
            ativo: formData.get('ativo') === '1',
            observacoes: formData.get('observacoes').trim()
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
    console.log('✏️ Editando CFC ID:', id);
    
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
                
                // Preencher campos
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
                abrirModalCFC();
                
                console.log('✅ Formulário preenchido com dados do CFC:', cfc);
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
    
    // Event listeners para o modal
    const modal = document.getElementById('modalCFC');
    if (modal) {
        // Fechar modal ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalCFC();
            }
        });
    }
    
    // Event listener para o formulário
    const form = document.getElementById('formCFC');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarCFC();
        });
    }
    
    // Event listener para o botão de salvar
    const btnSalvar = document.getElementById('btnSalvarCFC');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', function(e) {
            e.preventDefault();
            salvarCFC();
        });
    }
    
    // Event listener para ESC fechar modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalCFC');
            if (modal && modal.style.display === 'block') {
                fecharModalCFC();
            }
        }
    });
    
    console.log('✅ Sistema de CFCs inicializado!');
});

console.log('📋 Arquivo cfcs.js carregado!');
