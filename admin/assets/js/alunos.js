/**
 * JavaScript para gerenciamento de Alunos
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPIAlunosCache = null;

// Função para detectar o caminho correto da API
async function detectarCaminhoAPIAlunos() {
    if (caminhoAPIAlunosCache) {
        return caminhoAPIAlunosCache;
    }
    
    const baseUrl = window.location.origin;
    const pathname = window.location.pathname;
    
    // Detectar caminho baseado na URL atual
    if (pathname.includes('/admin/')) {
        const basePath = pathname.substring(0, pathname.lastIndexOf('/admin/'));
        caminhoAPIAlunosCache = baseUrl + basePath + '/admin/api/alunos.php';
    } else {
        caminhoAPIAlunosCache = baseUrl + '/admin/api/alunos.php';
    }
    
    console.log('🌐 Caminho da API Alunos detectado:', caminhoAPIAlunosCache);
    return caminhoAPIAlunosCache;
}

// Função para fazer requisições à API
async function fetchAPIAlunos(endpoint = '', options = {}) {
    const baseApiUrl = await detectarCaminhoAPIAlunos();
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

// Função para abrir modal de aluno
window.abrirModalAluno = function() {
    console.log('🚀 Abrindo modal de aluno...');
    
    const modal = document.getElementById('modalAluno');
    if (!modal) {
        console.error('❌ Modal não encontrado!');
        alert('Erro: Modal não encontrado na página!');
        return;
    }
    
    // Limpar formulário
    const form = document.getElementById('formAluno');
    if (form) {
        form.reset();
        document.getElementById('acaoAluno').value = 'criar';
        document.getElementById('aluno_id').value = '';
        document.getElementById('modalTitle').textContent = 'Novo Aluno';
    }
    
    // Mostrar modal
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
    
    console.log('✅ Modal aberto com sucesso!');
};

// Função para fechar modal de aluno
window.fecharModalAluno = function() {
    console.log('🚪 Fechando modal de aluno...');
    
    const modal = document.getElementById('modalAluno');
    if (modal) {
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        console.log('✅ Modal fechado!');
    }
};

// Função para salvar aluno
window.salvarAluno = async function() {
    console.log('💾 Salvando aluno...');
    
    try {
        const form = document.getElementById('formAluno');
        if (!form) {
            throw new Error('Formulário não encontrado');
        }
        
        const formData = new FormData(form);
        
        // Validações
        if (!formData.get('nome').trim()) {
            alert('Nome do aluno é obrigatório');
            return;
        }
        
        if (!formData.get('cpf').trim()) {
            alert('CPF é obrigatório');
            return;
        }
        
        if (!formData.get('categoria_cnh')) {
            alert('Categoria de CNH é obrigatória');
            return;
        }
        
        // Preparar dados
        const alunoData = {
            nome: formData.get('nome').trim(),
            cpf: formData.get('cpf').trim(),
            rg: formData.get('rg').trim(),
            data_nascimento: formData.get('data_nascimento'),
            endereco: formData.get('endereco').trim(),
            telefone: formData.get('telefone').trim(),
            email: formData.get('email').trim(),
            categoria_cnh: formData.get('categoria_cnh'),
            cfc_id: formData.get('cfc_id') || null,
            status: formData.get('status') || 'ativo'
        };
        
        const acao = formData.get('acao');
        const aluno_id = formData.get('aluno_id');
        
        if (acao === 'editar' && aluno_id) {
            alunoData.id = aluno_id;
        }
        
        // Mostrar loading no botão
        const btnSalvar = document.getElementById('btnSalvarAluno');
        if (btnSalvar) {
            const originalText = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Salvando...';
            btnSalvar.disabled = true;
            
            try {
                const method = acao === 'editar' ? 'PUT' : 'POST';
                const endpoint = acao === 'editar' ? `?id=${aluno_id}` : '';
                
                const response = await fetchAPIAlunos(endpoint, {
                    method: method,
                    body: JSON.stringify(alunoData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    alert(data.message || 'Aluno salvo com sucesso!');
                    fecharModalAluno();
                    
                    // Recarregar página
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                } else {
                    alert(data.error || 'Erro ao salvar aluno');
                }
            } catch (error) {
                console.error('❌ Erro ao salvar:', error);
                alert('Erro ao salvar aluno: ' + error.message);
            } finally {
                btnSalvar.innerHTML = originalText;
                btnSalvar.disabled = false;
            }
        }
        
    } catch (error) {
        console.error('❌ Erro na função salvarAluno:', error);
        alert('Erro interno: ' + error.message);
    }
};

// Função para editar aluno
window.editarAluno = async function(id) {
    console.log('✏️ Editando aluno ID:', id);
    
    try {
        const response = await fetchAPIAlunos(`?id=${id}`);
        const data = await response.json();
        
        if (data.success) {
            const aluno = data.data;
            
            // Preencher formulário
            document.getElementById('nome').value = aluno.nome || '';
            document.getElementById('cpf').value = aluno.cpf || '';
            document.getElementById('rg').value = aluno.rg || '';
            document.getElementById('data_nascimento').value = aluno.data_nascimento || '';
            document.getElementById('endereco').value = aluno.endereco || '';
            document.getElementById('telefone').value = aluno.telefone || '';
            document.getElementById('email').value = aluno.email || '';
            document.getElementById('categoria_cnh').value = aluno.categoria_cnh || '';
            document.getElementById('cfc_id').value = aluno.cfc_id || '';
            document.getElementById('status').value = aluno.status || 'ativo';
            
            // Configurar modal para edição
            document.getElementById('modalTitle').textContent = 'Editar Aluno';
            document.getElementById('acaoAluno').value = 'editar';
            document.getElementById('aluno_id').value = id;
            
            // Abrir modal
            abrirModalAluno();
        } else {
            alert('Erro ao carregar dados do aluno: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao editar aluno:', error);
        alert('Erro ao carregar dados do aluno: ' + error.message);
    }
};

// Função para excluir aluno
window.excluirAluno = async function(id) {
    console.log('🗑️ Excluindo aluno ID:', id);
    
    if (!confirm('⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este aluno?')) {
        return;
    }
    
    try {
        const response = await fetchAPIAlunos(`?id=${id}`, {
            method: 'DELETE'
        });
        
        const data = await response.json();
        
        if (data.success) {
            alert(data.message || 'Aluno excluído com sucesso!');
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erro ao excluir aluno: ' + (data.error || 'Erro desconhecido'));
        }
    } catch (error) {
        console.error('❌ Erro ao excluir aluno:', error);
        alert('Erro ao excluir aluno: ' + error.message);
    }
};

// Função para visualizar histórico do aluno
window.visualizarHistoricoAluno = function(id) {
    console.log('📋 Visualizando histórico do aluno ID:', id);
    window.location.href = `index.php?page=historico-aluno&id=${id}`;
};

// Função para agendar aula para o aluno
window.agendarAulaAluno = function(id) {
    console.log('📅 Agendando aula para aluno ID:', id);
    window.location.href = `index.php?page=agendar-aula&aluno_id=${id}`;
};

// Inicialização quando o DOM estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando sistema de alunos...');
    
    // Event listeners para o modal
    const modal = document.getElementById('modalAluno');
    if (modal) {
        // Fechar modal ao clicar fora
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalAluno();
            }
        });
    }
    
    // Event listener para o formulário
    const form = document.getElementById('formAluno');
    if (form) {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            salvarAluno();
        });
    }
    
    // Event listener para o botão de salvar
    const btnSalvar = document.getElementById('btnSalvarAluno');
    if (btnSalvar) {
        btnSalvar.addEventListener('click', function(e) {
            e.preventDefault();
            salvarAluno();
        });
    }
    
    // Event listener para ESC fechar modal
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('modalAluno');
            if (modal && modal.style.display === 'block') {
                fecharModalAluno();
            }
        }
    });
    
    console.log('✅ Sistema de alunos inicializado!');
});

console.log('📋 Arquivo alunos.js carregado!');
