/**
 * JavaScript para gerenciamento de Instrutores
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPIInstrutoresCache = null;

// Função para detectar o caminho correto da API
async function detectarCaminhoAPIInstrutores() {
    if (caminhoAPIInstrutoresCache) {
        return caminhoAPIInstrutoresCache;
    }
    
    const baseUrl = window.location.origin;
    const pathname = window.location.pathname;
    
    // Detectar caminho baseado na URL atual
    if (pathname.includes('/admin/')) {
        const basePath = pathname.substring(0, pathname.lastIndexOf('/admin/'));
        caminhoAPIInstrutoresCache = baseUrl + basePath + '/admin/api/instrutores.php';
    } else {
        caminhoAPIInstrutoresCache = baseUrl + '/admin/api/instrutores.php';
    }
    
    console.log('🌐 Caminho da API Instrutores detectado:', caminhoAPIInstrutoresCache);
    return caminhoAPIInstrutoresCache;
}

// Função para fazer requisições à API
async function fetchAPIInstrutores(endpoint = '', options = {}) {
    const baseApiUrl = await detectarCaminhoAPIInstrutores();
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

// Função para abrir modal de instrutor
window.abrirModalInstrutor = function() {
    console.log('🚀 Abrindo modal de instrutor...');
    
    const modal = document.getElementById('modalInstrutor');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        alert('Erro: Modal não encontrado na página!');
        return;
    }
    
    // Limpar formulário
    const form = document.getElementById('formInstrutor');
    if (form) {
        form.reset();
        document.getElementById('acaoInstrutor').value = 'criar';
        document.getElementById('instrutor_id').value = '';
        document.getElementById('modalTitle').textContent = 'Novo Instrutor';
    }
    
    // Mostrar modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    console.log('✅ Modal aberto com sucesso!');
};

// Função para fechar modal de instrutor
window.fecharModalInstrutor = function() {
    console.log('🚪 Fechando modal de instrutor...');
    
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log('✅ Modal fechado!');
    }
};

// Função para salvar instrutor
window.salvarInstrutor = async function() {
    console.log('💾 Salvando instrutor...');
    
    try {
        const form = document.getElementById('formInstrutor');
        if (!form) {
            throw new Error('Formulário não encontrado');
        }
        
        const formData = new FormData(form);
        
        // Validações
        if (!formData.get('nome').trim()) {
            alert('Nome do instrutor é obrigatório');
            return;
        }
        
        if (!formData.get('credencial').trim()) {
            alert('Credencial é obrigatória');
            return;
        }
        
        if (!formData.get('categoria_habilitacao')) {
            alert('Categoria de habilitação é obrigatória');
            return;
        }
        
        // Preparar dados
        const instrutorData = {
            nome: formData.get('nome').trim(),
            email: formData.get('email').trim(),
            telefone: formData.get('telefone').trim(),
            credencial: formData.get('credencial').trim(),
            categoria_habilitacao: formData.get('categoria_habilitacao'),
            cfc_id: formData.get('cfc_id') || null,
            ativo: formData.get('ativo') === '1'
        };
        
        const acao = formData.get('acao');
        const instrutor_id = formData.get('instrutor_id');
        
        if (acao === 'editar' && instrutor_id) {
            instrutorData.id = instrutor_id;
        }
        
        // Mostrar loading no botão
        const btnSalvar = document.getElementById('btnSalvarInstrutor');
        if (btnSalvar) {
            const originalText = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
            btnSalvar.disabled = true;
            
            try {
                const method = acao === 'editar' ? 'PUT' : 'POST';
                const endpoint = acao === 'editar' ? `?id=${instrutor_id}` : '';
                
                const response = await fetchAPIInstrutores(endpoint, {
                    method: method,
                    body: JSON.stringify(instrutorData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Instrutor salvo com sucesso!');
                    fecharModalInstrutor();
                    
                    // Recarregar página
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.error || 'Erro ao salvar instrutor');
                }
            } catch (error) {
                console.error('❌ Erro ao salvar:', error);
                alert('Erro ao salvar instrutor: ' + error.message);
            } finally {
                btnSalvar.innerHTML = originalText;
                btnSalvar.disabled = false;
            }
        }
        
    } catch (error) {
        console.error('❌ Erro na função salvarInstrutor:', error);
        alert('Erro interno: ' + error.message);
    }
};

// Função para editar instrutor
window.editarInstrutor = async function(id) {
    console.log('✏️ Editando instrutor ID:', id);
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const instrutor = data.data;
            
            // Preencher formulário
            document.getElementById('nome').value = instrutor.nome || '';
            document.getElementById('email').value = instrutor.email || '';
            document.getElementById('telefone').value = instrutor.telefone || '';
            document.getElementById('credencial').value = instrutor.credencial || '';
            document.getElementById('categoria_habilitacao').value = instrutor.categoria_habilitacao || '';
            document.getElementById('cfc_id').value = instrutor.cfc_id || '';
            document.getElementById('ativo').value = instrutor.ativo ? '1' : '0';
            
            // Configurar modal para edição
            document.getElementById('modalTitle').textContent = 'Editar Instrutor';
            document.getElementById('acaoInstrutor').value = 'editar';
            document.getElementById('instrutor_id').value = id;
            
            // Abrir modal
            abrirModalInstrutor();
        } else {
            alert('Erro ao carregar dados do instrutor: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao editar instrutor:', error);
        alert('Erro ao carregar dados do instrutor: ' + error.message);
    }
};

// Função para excluir instrutor
window.excluirInstrutor = async function(id) {
    console.log('🗑️ Excluindo instrutor ID:', id);
    
    if (!confirm('⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este instrutor?')) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Instrutor excluído com sucesso!');
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erro ao excluir instrutor: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao excluir instrutor:', error);
        alert('Erro ao excluir instrutor: ' + error.message);
    }
};

// Função para ativar/desativar instrutor
window.ativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 1);
};

window.desativarInstrutor = async function(id) {
    await alterarStatusInstrutor(id, 0);
};

async function alterarStatusInstrutor(id, status) {
    const acao = status ? 'ativar' : 'desativar';
    const mensagem = status ? 'Deseja realmente ativar este instrutor?' : 'Deseja realmente desativar este instrutor?';
    
    if (!confirm(mensagem)) {
        return;
    }
    
    try {
        const response = await fetchAPIInstrutores(`?id=${id}`, {
            method: 'PUT',
            body: JSON.stringify({ ativo: status })
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(`Instrutor ${acao}do com sucesso!`);
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert(`Erro ao ${acao} instrutor: ` + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error(`❌ Erro ao ${acao} instrutor:`, error);
        alert(`Erro ao ${acao} instrutor: ` + error.message);
    }
}

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de instrutores...');
    
    // Event listeners para o modal
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        // Fechar modal ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalInstrutor();
            }
        });
    }
    
    // Event listener para o formulário
    const form = document.getElementById('formInstrutor');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarInstrutor();
        });
    }
    
    // Event listener para o botão de salvar
    const btnSalvar = document.getElementById('btnSalvarInstrutor');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', function(e) {
            e.preventDefault();
            salvarInstrutor();
        });
    }
    
    // Event listener para ESC fechar modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalInstrutor');
            if (modal && modal.style.display === 'block') {
                fecharModalInstrutor();
            }
        }
    });
    
    console.log('✅ Sistema de instrutores inicializado!');
});

console.log('📋 Arquivo instrutores.js carregado!');
