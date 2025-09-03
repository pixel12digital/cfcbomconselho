/**
 * JavaScript para gerenciamento de Alunos
 * Sistema CFC - Bom Conselho
 */

// Cache para o caminho da API
let caminhoAPIAlunosCache = null;

// Função para converter data de DD/MM/YYYY para YYYY-MM-DD (MySQL)
function converterDataParaMySQL(dataString) {
    if (!dataString || dataString.trim() === '') {
        return null; // Retorna null para campos vazios
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataString)) {
        return dataString;
    }
    
    // Converter de DD/MM/YYYY para YYYY-MM-DD
    const match = dataString.match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
    if (match) {
        const [, dia, mes, ano] = match;
        const dataMySQL = `${ano}-${mes.padStart(2, '0')}-${dia.padStart(2, '0')}`;
        console.log(`✅ Data convertida: ${dataString} → ${dataMySQL}`);
        return dataMySQL;
    }
    
    console.warn(`⚠️ Formato de data inválido: ${dataString}`);
    return null;
}

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
    console.log('📡 Método:', options.method || 'GET');
    console.log('📡 Opções:', options);
    
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
        console.log('📡 Iniciando fetch...');
        const response = await fetch(url, mergedOptions);
        
        console.log('📡 Resposta recebida:', response.status, response.statusText);
        
        if (!response.ok) {
            // Tentar ler o corpo da resposta para mais detalhes
            let errorText = '';
            try {
                const errorBody = await response.text();
                errorText = errorBody;
                console.log('📡 Corpo da resposta de erro:', errorText);
            } catch (e) {
                errorText = 'Não foi possível ler o corpo da resposta';
            }
            
            throw new Error(`HTTP ${response.status}: ${response.statusText} - ${errorText}`);
        }
        
        console.log('✅ Requisição bem-sucedida');
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
    
    // Verificar se está em modo de edição
    const acaoAluno = document.getElementById('acaoAluno');
    const isEditing = acaoAluno && acaoAluno.value === 'editar';
    
    console.log('📋 Modo de edição:', isEditing);
    
    // Limpar formulário apenas se não estiver editando
    const form = document.getElementById('formAluno');
    if (form && !isEditing) {
        console.log('🧹 Limpando formulário para novo aluno');
        form.reset();
        document.getElementById('acaoAluno').value = 'criar';
        document.getElementById('aluno_id').value = '';
        document.getElementById('modalTitle').textContent = 'Novo Aluno';
    } else if (isEditing) {
        console.log('✏️ Mantendo dados do formulário para edição');
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
        if (!formData.get('nome') || !formData.get('nome').trim()) {
            alert('Nome do aluno é obrigatório');
            return;
        }
        
        if (!formData.get('cpf') || !formData.get('cpf').trim()) {
            alert('CPF é obrigatório');
            return;
        }
        
        if (!formData.get('categoria_cnh')) {
            alert('Categoria de CNH é obrigatória');
            return;
        }
        
        // Preparar dados
        const alunoData = {
            nome: (formData.get('nome') || '').trim(),
            cpf: (formData.get('cpf') || '').trim(),
            rg: (formData.get('rg') || '').trim(),
            data_nascimento: formData.get('data_nascimento') || null,
            endereco: (formData.get('logradouro') || '').trim(),
            numero: (formData.get('numero') || '').trim(),
            bairro: (formData.get('bairro') || '').trim(),
            cidade: (formData.get('cidade') || '').trim(),
            estado: (formData.get('uf') || '').trim(),
            cep: (formData.get('cep') || '').trim(),
            telefone: (formData.get('telefone') || '').trim(),
            email: (formData.get('email') || '').trim(),
            categoria_cnh: formData.get('categoria_cnh'),
            cfc_id: formData.get('cfc_id') || null,
            status: formData.get('status') || 'ativo',
            observacoes: (formData.get('observacoes') || '').trim()
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
        
        // Debug: verificar se a resposta tem conteúdo
        const responseText = await response.text();
        console.log('📋 Resposta bruta da API:', responseText);
        
        // Tentar fazer parse do JSON
        let data;
        try {
            data = JSON.parse(responseText);
            console.log('📋 JSON parseado com sucesso:', data);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            throw new Error(`Resposta inválida da API: ${responseText}`);
        }
        
        if (data.success) {
            const aluno = data.aluno;
            
            // Debug: verificar estrutura da resposta
            console.log('📋 Resposta da API:', data);
            console.log('📋 Dados do aluno:', aluno);
            
            if (!aluno) {
                throw new Error('Dados do aluno não encontrados na resposta da API');
            }
            
            // Configurar modal para edição ANTES de abrir
            document.getElementById('modalTitle').textContent = 'Editar Aluno';
            document.getElementById('acaoAluno').value = 'editar';
            document.getElementById('aluno_id').value = id;
            
            // Abrir modal SEM limpar formulário
            const modal = document.getElementById('modalAluno');
            if (!modal) {
                throw new Error('Modal não encontrado');
            }
            
            // Mostrar modal
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
            
            console.log('✅ Modal aberto para edição');
            
            // Preencher formulário DEPOIS de abrir o modal
            console.log('📝 Preenchendo campos do formulário...');
            
            // Aguardar um pouco para garantir que o DOM esteja pronto
            setTimeout(() => {
                // Campos básicos
                const nomeField = document.getElementById('nome');
                const cpfField = document.getElementById('cpf');
                const rgField = document.getElementById('rg');
                const dataNascField = document.getElementById('data_nascimento');
                const statusField = document.getElementById('status');
                const emailField = document.getElementById('email');
                const telefoneField = document.getElementById('telefone');
                
                // Campos acadêmicos
                const cfcField = document.getElementById('cfc_id');
                const categoriaField = document.getElementById('categoria_cnh');
                
                // Campos de endereço
                const cepField = document.getElementById('cep');
                const logradouroField = document.getElementById('logradouro');
                const numeroField = document.getElementById('numero');
                const bairroField = document.getElementById('bairro');
                const ufField = document.getElementById('uf');
                const cidadeField = document.getElementById('cidade');
                
                // Campo de observações
                const obsField = document.getElementById('observacoes');
                
                // Verificar se os campos existem antes de preencher
                if (nomeField) nomeField.value = aluno.nome || '';
                if (cpfField) cpfField.value = aluno.cpf || '';
                if (rgField) rgField.value = aluno.rg || '';
                if (dataNascField) dataNascField.value = aluno.data_nascimento || '';
                if (statusField) statusField.value = aluno.status || 'ativo';
                if (emailField) emailField.value = aluno.email || '';
                if (telefoneField) telefoneField.value = aluno.telefone || '';
                if (cfcField) cfcField.value = aluno.cfc_id || '';
                if (categoriaField) categoriaField.value = aluno.categoria_cnh || '';
                if (cepField) cepField.value = aluno.cep || '';
                if (logradouroField) logradouroField.value = aluno.endereco || '';
                if (numeroField) numeroField.value = aluno.numero || '';
                if (bairroField) bairroField.value = aluno.bairro || '';
                if (ufField) ufField.value = aluno.estado || '';
                if (cidadeField) cidadeField.value = aluno.cidade || '';
                if (obsField) obsField.value = aluno.observacoes || '';
                
                console.log('✅ Campos preenchidos com sucesso');
                console.log('📋 Nome preenchido:', nomeField ? nomeField.value : 'campo não encontrado');
                console.log('📋 CPF preenchido:', cpfField ? cpfField.value : 'campo não encontrado');
            }, 100);
            
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
