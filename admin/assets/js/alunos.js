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
    
    // Detectar caminho baseado na URL atual - usar caminho relativo
    if (pathname.includes('/admin/')) {
        // Extrair o diretório base do projeto
        const pathParts = pathname.split('/');
        const projectIndex = pathParts.findIndex(part => part === 'admin');
        if (projectIndex > 0) {
            const basePath = pathParts.slice(0, projectIndex).join('/');
            caminhoAPIAlunosCache = baseUrl + basePath + '/admin/api/alunos.php';
        } else {
            caminhoAPIAlunosCache = baseUrl + '/admin/api/alunos.php';
        }
    } else {
        caminhoAPIAlunosCache = baseUrl + '/admin/api/alunos.php';
    }
    
    console.log('🌐 Caminho da API Alunos detectado:', caminhoAPIAlunosCache);
    console.log('🌐 Base URL:', baseUrl);
    console.log('🌐 Pathname:', pathname);
    return caminhoAPIAlunosCache;
}

// Função para fazer requisições à API
async function fetchAPIAlunos(endpoint = '', options = {}) {
    const baseApiUrl = await detectarCaminhoAPIAlunos();
    const url = baseApiUrl + endpoint;
    
    console.log('📡 Fazendo requisição para:', url);
    console.log('📡 URL completa:', url);
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

// =====================================================
// CONTROLE DE VISIBILIDADE DO MODAL - PADRÃO ÚNICO
// =====================================================

function abrirModalAluno(modo = 'novo', alunoId = null) {
  const modal = document.getElementById('modalAluno');
  if (!modal) {
    console.warn('[modalAluno] Elemento #modalAluno não encontrado.');
    return;
  }

  // visibilidade e centralização (sempre igual, independente do modo)
  modal.dataset.opened = 'true';
  document.body.style.overflow = 'hidden';

  // garante que o conteúdo do modal começa no topo
  const bodyEl = modal.querySelector('.aluno-modal-body');
  if (bodyEl) {
    bodyEl.scrollTop = 0;
  }

  // lógica de modo (apenas título/campos, sem mexer em posição)
  const tituloEl = modal.querySelector('.aluno-modal-title');
  if (tituloEl) {
    if (modo === 'editar') {
      tituloEl.innerHTML = '<i class="fas fa-user-edit me-2"></i>Editar Aluno';
    } else {
      tituloEl.innerHTML = '<i class="fas fa-user-plus me-2"></i>Novo Aluno';
    }
  }

  // Configurar campos hidden se necessário
  const acaoEl = document.getElementById('acaoAluno');
  const alunoIdEl = document.getElementById('aluno_id_hidden');
  if (acaoEl) {
    acaoEl.value = (modo === 'editar') ? 'editar' : 'criar';
  }
  if (alunoIdEl && alunoId) {
    alunoIdEl.value = alunoId;
  }

  // Debug: verificar centralização
  const dialog = modal.querySelector('.custom-modal-dialog');
  if (dialog) {
    const rect = dialog.getBoundingClientRect();
    const viewportWidth = window.innerWidth;
    const leftGap = rect.left;
    const rightGap = viewportWidth - rect.right;
    console.log('[modalAluno]', modo, { viewportWidth, leftGap, rightGap, diff: Math.abs(leftGap - rightGap) });
  }

  console.log('[modalAluno] abrirModalAluno chamado, modo:', modo, 'alunoId:', alunoId, 'data-opened=true');
}

function fecharModalAluno() {
  const modal = document.getElementById('modalAluno');
  if (!modal) {
    console.warn('[modalAluno] Elemento #modalAluno não encontrado (fechar).');
    return;
  }

  modal.dataset.opened = 'false';

  // libera o scroll do fundo
  document.body.style.overflow = '';

  console.log('[modalAluno] fecharModalAluno chamado, data-opened=false');
}

// expõe explicitamente no escopo global
window.abrirModalAluno = abrirModalAluno;
window.fecharModalAluno = fecharModalAluno;

console.log('[modalAluno] funções abrir/fechar registradas no window.');

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
        
        if (!formData.get('tipo_servico')) {
            alert('Tipo de serviço é obrigatório');
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
            naturalidade: (formData.get('naturalidade') || '').trim(),
            nacionalidade: (formData.get('nacionalidade') || 'Brasileira').trim(),
            endereco: (formData.get('logradouro') || '').trim(),
            numero: (formData.get('numero') || '').trim(),
            bairro: (formData.get('bairro') || '').trim(),
            cidade: (formData.get('cidade') || '').trim(),
            estado: (formData.get('uf') || '').trim(),
            cep: (formData.get('cep') || '').trim(),
            telefone: (formData.get('telefone') || '').trim(),
            email: (formData.get('email') || '').trim(),
            tipo_servico: formData.get('tipo_servico'),
            categoria_cnh: formData.get('categoria_cnh'),
            cfc_id: formData.get('cfc_id') || null,
            status: formData.get('status') || 'ativo',
            observacoes: (formData.get('observacoes') || '').trim()
        };
        
        // Debug: verificar dados antes de enviar
        console.log('🔧 Dados do formulário (FormData):');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }
        
        console.log('🔧 Dados preparados para API:');
        console.log(alunoData);
        
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
            
            // Abrir modal usando padrão único (centralização e visibilidade)
            // A função abrirModalAluno já configura título e campos hidden
            abrirModalAluno('editar', id);
            
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
                const naturalidadeField = document.getElementById('naturalidade');
                const nacionalidadeField = document.getElementById('nacionalidade');
                const statusField = document.getElementById('status');
                const emailField = document.getElementById('email');
                const telefoneField = document.getElementById('telefone');
                
                // Campos acadêmicos
                const cfcField = document.getElementById('cfc_id');
                const tipoServicoField = document.getElementById('tipo_servico');
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
                if (naturalidadeField) naturalidadeField.value = aluno.naturalidade || '';
                if (nacionalidadeField) nacionalidadeField.value = aluno.nacionalidade || 'Brasileira';
                if (statusField) statusField.value = aluno.status || 'ativo';
                if (emailField) emailField.value = aluno.email || '';
                if (telefoneField) telefoneField.value = aluno.telefone || '';
                if (cfcField) cfcField.value = aluno.cfc_id || '';
                if (cepField) cepField.value = aluno.cep || '';
                if (logradouroField) logradouroField.value = aluno.endereco || '';
                if (numeroField) numeroField.value = aluno.numero || '';
                if (bairroField) bairroField.value = aluno.bairro || '';
                if (ufField) ufField.value = aluno.estado || '';
                if (cidadeField) cidadeField.value = aluno.cidade || '';
                if (obsField) obsField.value = aluno.observacoes || '';
                
                // Carregar operações existentes
                console.log('🔄 Carregando operações do aluno:', aluno.operacoes);
                console.log('🔄 Tipo de operacoes:', typeof aluno.operacoes);
                
                let operacoesArray = null;
                
                // Verificar se operacoes é string JSON e converter para array
                if (typeof aluno.operacoes === 'string' && aluno.operacoes !== 'null') {
                    try {
                        operacoesArray = JSON.parse(aluno.operacoes);
                        console.log('🔄 Operacoes convertidas de string para array:', operacoesArray);
                    } catch (e) {
                        console.error('❌ Erro ao fazer parse das operações:', e);
                        operacoesArray = null;
                    }
                } else if (Array.isArray(aluno.operacoes)) {
                    operacoesArray = aluno.operacoes;
                    console.log('🔄 Operacoes já é array:', operacoesArray);
                }
                
                console.log('🔄 Operacoes finais:', operacoesArray);
                console.log('🔄 Operacoes é array?', Array.isArray(operacoesArray));
                console.log('🔄 Quantidade de operações:', operacoesArray ? operacoesArray.length : 'undefined');
                
                if (operacoesArray && Array.isArray(operacoesArray) && operacoesArray.length > 0) {
                    console.log('✅ Operações válidas encontradas, chamando carregarOperacoesExistentes');
                    carregarOperacoesExistentes(operacoesArray);
                } else {
                    console.log('⚠️ Nenhuma operação encontrada ou formato inválido');
                    // Limpar operações existentes
                    const container = document.getElementById('operacoes-container');
                    if (container) {
                        container.innerHTML = '';
                        console.log('🧹 Container de operações limpo');
                    }
                }
                
                // Preencher tipo de serviço e categoria CNH
                if (aluno.categoria_cnh) {
                    // Usar o tipo de serviço salvo no banco, ou determinar baseado na categoria
                    let tipoServico = aluno.tipo_servico || '';
                    
                    // Se não tiver tipo_servico salvo, determinar baseado na categoria
                    if (!tipoServico) {
                        if (['A', 'B', 'AB', 'ACC'].includes(aluno.categoria_cnh)) {
                            tipoServico = 'primeira_habilitacao';
                        } else if (['C', 'D', 'E'].includes(aluno.categoria_cnh)) {
                            tipoServico = 'adicao';
                        } else {
                            tipoServico = 'mudanca';
                        }
                    }
                    
                    console.log('🔧 Tipo de serviço para edição:', tipoServico, '(salvo:', aluno.tipo_servico, ', categoria:', aluno.categoria_cnh, ')');
                    
                    // Definir tipo de serviço primeiro
                    console.log('🔧 Definindo tipo de serviço:', tipoServico);
                    if (tipoServicoField) {
                        tipoServicoField.value = tipoServico;
                        console.log('✅ Tipo de serviço definido:', tipoServicoField.value);
                    } else {
                        console.log('❌ Campo tipo_servico não encontrado!');
                    }
                    
                    // Carregar categorias para o tipo selecionado
                    console.log('🔧 Verificando função carregarCategoriasCNH...', typeof carregarCategoriasCNH);
                    
                    if (typeof carregarCategoriasCNH === 'function') {
                        console.log('🔧 Chamando carregarCategoriasCNH()...');
                        carregarCategoriasCNH();
                        
                        // Aguardar um pouco mais para garantir que as opções sejam carregadas
                        setTimeout(() => {
                            console.log('🔧 Definindo categoria CNH:', aluno.categoria_cnh);
                            if (categoriaField) {
                                categoriaField.value = aluno.categoria_cnh || '';
                                console.log('✅ Categoria CNH definida:', categoriaField.value);
                            } else {
                                console.log('❌ Campo categoria_cnh não encontrado!');
                            }
                        }, 500); // Aumentar timeout para 500ms
                    } else {
                        console.log('⚠️ Função carregarCategoriasCNH não encontrada, usando fallback');
                        // Fallback se a função não estiver disponível
                        if (categoriaField) {
                            categoriaField.value = aluno.categoria_cnh || '';
                            console.log('✅ Categoria CNH definida (fallback):', categoriaField.value);
                        }
                    }
                }
                
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
            if (modal && modal.dataset.opened === 'true') {
                fecharModalAluno();
            }
        }
    });
    
    console.log('✅ Sistema de alunos inicializado!');
});

// Função para carregar operações existentes (copiada do alunos.php)
function carregarOperacoesExistentes(operacoes) {
    console.log('🔄 Carregando operações existentes:', operacoes);
    console.log('🔄 Tipo de operacoes:', typeof operacoes);
    console.log('🔄 Array?', Array.isArray(operacoes));
    console.log('🔄 Quantidade:', operacoes ? operacoes.length : 'undefined');
    
    // Limpar operações atuais
    const container = document.getElementById('operacoes-container');
    if (!container) {
        console.log('❌ Container operacoes-container não encontrado');
        return;
    }
    
    container.innerHTML = '';
    let contadorOperacoes = 0;
    
    // Verificar se operacoes é um array válido
    if (!Array.isArray(operacoes) || operacoes.length === 0) {
        console.log('⚠️ Nenhuma operação para carregar ou operacoes não é array');
        return;
    }
    
    // Definir categorias por tipo de serviço (GLOBAL)
    const categoriasPorTipo = {
        'primeira_habilitacao': [
            { value: 'A', text: 'A - Motocicletas', desc: 'Primeira habilitação para motocicletas, ciclomotores e triciclos' },
            { value: 'B', text: 'B - Automóveis', desc: 'Primeira habilitação para automóveis, caminhonetes e utilitários' },
            { value: 'AB', text: 'AB - A + B', desc: 'Primeira habilitação completa (motocicletas + automóveis)' }
        ],
        'adicao': [
            { value: 'A', text: 'A - Motocicletas', desc: 'Adicionar categoria A (motocicletas) à habilitação existente' },
            { value: 'B', text: 'B - Automóveis', desc: 'Adicionar categoria B (automóveis) à habilitação existente' }
        ],
        'mudanca': [
            { value: 'C', text: 'C - Veículos de Carga', desc: 'Mudança de B para C (veículos de carga acima de 3.500kg)' },
            { value: 'D', text: 'D - Veículos de Passageiros', desc: 'Mudança de B para D (veículos de transporte de passageiros)' },
            { value: 'E', text: 'E - Combinação de Veículos', desc: 'Mudança de B para E (combinação de veículos - carreta, bitrem)' }
        ]
    };
    
    // Adicionar cada operação existente
    operacoes.forEach((operacao, index) => {
        console.log(`🔄 Processando operação ${index}:`, operacao);
        console.log(`🔄 Operação ${index} - tipo:`, operacao.tipo);
        console.log(`🔄 Operação ${index} - categoria:`, operacao.categoria);
        contadorOperacoes++;
        console.log(`🔄 Contador de operações agora é: ${contadorOperacoes}`);
        
        const operacaoHtml = `
            <div class="operacao-item border rounded p-2 mb-2" data-operacao-id="${contadorOperacoes}">
                <div class="row align-items-center">
                    <div class="col-md-3">
                        <select class="form-select form-select-sm" name="operacao_tipo_${contadorOperacoes}" onchange="carregarCategoriasOperacao(${contadorOperacoes})">
                            <option value="">Tipo de Operação</option>
                            <option value="primeira_habilitacao" ${operacao.tipo === 'primeira_habilitacao' ? 'selected' : ''}>🏍️ Primeira Habilitação</option>
                            <option value="adicao" ${operacao.tipo === 'adicao' ? 'selected' : ''}>➕ Adição de Categoria</option>
                            <option value="mudanca" ${operacao.tipo === 'mudanca' ? 'selected' : ''}>🔄 Mudança de Categoria</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <select class="form-select form-select-sm" name="operacao_categoria_${contadorOperacoes}" disabled>
                            <option value="">Selecione o tipo primeiro</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="removerOperacao(${contadorOperacoes})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        
        container.insertAdjacentHTML('beforeend', operacaoHtml);
        console.log(`✅ HTML inserido para operação ${contadorOperacoes}`);
        
        // Carregar categorias para esta operação
        setTimeout(() => {
            console.log(`⚙️ Carregando categorias para operação ${contadorOperacoes} com categoria: ${operacao.categoria}`);
            carregarCategoriasOperacao(contadorOperacoes, operacao.categoria);
        }, 50);
    });
}

// Função para carregar categorias CNH dinamicamente para uma operação específica
function carregarCategoriasOperacao(operacaoId, categoriaSelecionada = '') {
    console.log(`⚙️ Carregando categorias para operação ${operacaoId}. Categoria Selecionada: ${categoriaSelecionada}`);
    const tipoSelect = document.querySelector(`select[name="operacao_tipo_${operacaoId}"]`);
    const categoriaSelect = document.querySelector(`select[name="operacao_categoria_${operacaoId}"]`);
    
    if (!tipoSelect || !categoriaSelect) {
        console.log('❌ Selects não encontrados para operação', operacaoId);
        return;
    }
    
    const tipoServico = tipoSelect.value;
    
    // Limpar opções anteriores
    categoriaSelect.innerHTML = '<option value="">Selecione a categoria...</option>';
    
    if (!tipoServico) {
        categoriaSelect.disabled = true;
        return;
    }
    
    // Definir categorias por tipo de serviço (mesma lógica da função principal)
    const categoriasPorTipo = {
        'primeira_habilitacao': [
            { value: 'A', text: 'A - Motocicletas', desc: 'Primeira habilitação para motocicletas, ciclomotores e triciclos' },
            { value: 'B', text: 'B - Automóveis', desc: 'Primeira habilitação para automóveis, caminhonetes e utilitários' },
            { value: 'AB', text: 'AB - A + B', desc: 'Primeira habilitação completa (motocicletas + automóveis)' }
        ],
        'adicao': [
            { value: 'A', text: 'A - Motocicletas', desc: 'Adicionar categoria A (motocicletas) à habilitação existente' },
            { value: 'B', text: 'B - Automóveis', desc: 'Adicionar categoria B (automóveis) à habilitação existente' }
        ],
        'mudanca': [
            { value: 'C', text: 'C - Veículos de Carga', desc: 'Mudança de B para C (veículos de carga acima de 3.500kg)' },
            { value: 'D', text: 'D - Veículos de Passageiros', desc: 'Mudança de B para D (veículos de transporte de passageiros)' },
            { value: 'E', text: 'E - Combinação de Veículos', desc: 'Mudança de B para E (combinação de veículos - carreta, bitrem)' }
        ]
    };
    
    // Usar a definição global de categoriasPorTipo
    console.log(`⚙️ Tipo de serviço: ${tipoServico}`);
    console.log(`⚙️ Categorias disponíveis:`, categoriasPorTipo[tipoServico]);
    
    const categorias = categoriasPorTipo[tipoServico] || [];
    
    // Adicionar opções ao select
    categorias.forEach(cat => {
        const option = document.createElement('option');
        option.value = cat.value;
        option.textContent = cat.text;
        if (cat.value === categoriaSelecionada) {
            option.selected = true;
            console.log(`✅ Categoria selecionada: ${cat.value} - ${cat.text}`);
        }
        categoriaSelect.appendChild(option);
    });
    
    // Habilitar select
    categoriaSelect.disabled = false;
    console.log(`⚙️ Select habilitado para operação ${operacaoId}`);
}

// Função para remover operação
function removerOperacao(operacaoId) {
    const operacaoItem = document.querySelector(`[data-operacao-id="${operacaoId}"]`);
    if (operacaoItem) {
        operacaoItem.remove();
    }
}

console.log('📋 Arquivo alunos.js carregado!');
