// Funções JavaScript da página de instrutores - VERSÃO CORRIGIDA
// Este arquivo é carregado APÓS o config.js, garantindo que API_CONFIG esteja disponível

// =====================================================
// FUNÇÕES DE GERENCIAMENTO DE FOTO
// =====================================================

/**
 * Preview da foto selecionada
 */
function previewFoto(input) {
    console.log('📷 Preview da foto iniciado...');
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        
        // Validar tipo de arquivo
        if (!file.type.startsWith('image/')) {
            alert('⚠️ Por favor, selecione apenas arquivos de imagem (JPG, PNG, GIF)');
            input.value = '';
            return;
        }
        
        // Validar tamanho (2MB máximo)
        if (file.size > 2 * 1024 * 1024) {
            alert('⚠️ O arquivo deve ter no máximo 2MB');
            input.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('foto-preview');
            const container = document.getElementById('preview-container');
            const placeholder = document.getElementById('placeholder-foto');
            
            preview.src = e.target.result;
            container.style.display = 'block';
            placeholder.style.display = 'none';
            
            console.log('✅ Preview da foto carregado com sucesso');
        };
        reader.readAsDataURL(file);
    }
}

/**
 * Remover foto selecionada
 */
function removerFoto() {
    console.log('🗑️ Removendo foto...');
    
    const input = document.getElementById('foto');
    const preview = document.getElementById('foto-preview');
    const container = document.getElementById('preview-container');
    const placeholder = document.getElementById('placeholder-foto');
    
    input.value = '';
    preview.src = '';
    container.style.display = 'none';
    placeholder.style.display = 'block';
    
    console.log('✅ Foto removida com sucesso');
}

/**
 * Carregar foto existente do instrutor
 */
function carregarFotoExistente(caminhoFoto) {
    console.log('📷 Carregando foto existente:', caminhoFoto);
    
    if (caminhoFoto && caminhoFoto.trim() !== '') {
        const preview = document.getElementById('foto-preview');
        const container = document.getElementById('preview-container');
        const placeholder = document.getElementById('placeholder-foto');
        
        // Construir URL completa da foto
        let urlFoto;
        if (caminhoFoto.startsWith('http')) {
            urlFoto = caminhoFoto;
        } else {
            // Construir URL baseada no contexto atual
            const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/');
            urlFoto = `${baseUrl}/${caminhoFoto}`;
        }
        
        console.log('📷 URL da foto construída:', urlFoto);
        
        preview.src = urlFoto;
        container.style.display = 'block';
        placeholder.style.display = 'none';
        
        // Verificar se a imagem carregou
        preview.onload = function() {
            console.log('✅ Foto existente carregada com sucesso');
        };
        
        preview.onerror = function() {
            console.error('❌ Erro ao carregar foto:', urlFoto);
            // Se der erro, mostrar placeholder
            container.style.display = 'none';
            placeholder.style.display = 'block';
        };
    } else {
        // Se não há foto, mostrar placeholder
        const container = document.getElementById('preview-container');
        const placeholder = document.getElementById('placeholder-foto');
        
        container.style.display = 'none';
        placeholder.style.display = 'block';
        
        console.log('ℹ️ Nenhuma foto existente encontrada');
    }
}

// Funções JavaScript com URLs CORRIGIDAS
async function abrirModalInstrutor() {
    console.log('🚀 Abrindo modal de instrutor...');
    
    document.getElementById('modalTitle').textContent = 'Novo Instrutor';
    document.getElementById('acaoInstrutor').value = 'novo';
    document.getElementById('instrutor_id').value = '';
    
    // Limpar campos manualmente para evitar problemas com campos de data
    limparCamposFormulario();
    
    const modal = document.getElementById('modalInstrutor');
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Garantir que o modal seja visível
    setTimeout(async () => {
        modal.scrollTop = 0;
        const modalDialog = modal.querySelector('.custom-modal-dialog');
        if (modalDialog) {
            modalDialog.style.opacity = '1';
            modalDialog.style.transform = 'translateY(0)';
        }
        
        // CARREGAR DADOS APÓS O MODAL ESTAR ABERTO
        console.log('📋 Modal aberto, carregando dados dos selects...');
        
        try {
            // Debug: verificar status dos selects
            verificarStatusSelects();
            
            // Testar APIs primeiro
            await testarAPIs();
            
            // Carregar dados dos selects COM RETRY MAIS ROBUSTO
            await carregarCFCsComRetry();
            await carregarUsuariosComRetry();
            
            // VERIFICAÇÃO FINAL - Se ainda não carregou, tentar novamente
            setTimeout(async () => {
                const cfcSelect = document.getElementById('cfc_id');
                const usuarioSelect = document.getElementById('usuario_id');
                
                if (cfcSelect && cfcSelect.options.length <= 1) {
                    console.log('⚠️ CFCs não carregaram, tentando novamente...');
                    await carregarCFCsComRetry();
                } else if (cfcSelect && cfcSelect.options.length > 1) {
                    console.log('✅ CFCs carregados com sucesso!');
                }
                
                if (usuarioSelect && usuarioSelect.options.length <= 1) {
                    console.log('⚠️ Usuários não carregaram, tentando novamente...');
                    await carregarUsuariosComRetry();
                } else if (usuarioSelect && usuarioSelect.options.length > 1) {
                    console.log('✅ Usuários carregados com sucesso!');
                }
                
                // Debug: verificar status após carregamento
                verificarStatusSelects();
            }, 500);
            
        } catch (error) {
            console.error('❌ Erro ao carregar dados do modal:', error);
        }
    }, 100);
}

function fecharModalInstrutor() {
    console.log('🚪 Fechando modal de instrutor...');
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        // Remover classe show
        modal.classList.remove('show');
        
        // Animar o fechamento
        const modalDialog = modal.querySelector('.custom-modal-dialog');
        if (modalDialog) {
            modalDialog.style.opacity = '0';
            modalDialog.style.transform = 'translateY(-20px)';
        }
        
        // Forçar fechamento após animação
        setTimeout(() => {
            modal.style.setProperty('display', 'none', 'important');
            console.log('✅ Modal de instrutor fechado com sucesso');
        }, 300);
    }
}

// Função para limpar campos do formulário de forma segura
function limparCamposFormulario() {
    // Campos de texto
    const camposTexto = ['nome', 'cpf', 'cnh', 'email', 'credencial', 'telefone', 'endereco', 'cidade'];
    camposTexto.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) elemento.value = '';
    });
    
    // Campos de data - limpar de forma segura
    const camposData = ['data_nascimento', 'validade_credencial'];
    camposData.forEach(campoId => {
        const campo = document.getElementById(campoId);
        if (campo) {
            campo.value = '';
            campo.type = 'text'; // Manter como texto para a solução híbrida
            campo.removeAttribute('min');
            campo.removeAttribute('max');
        }
    });
    
    // Campos de select
    const camposSelect = ['usuario_id', 'cfc_id', 'uf', 'ativo'];
    camposSelect.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            if (campo === 'ativo') {
                elemento.value = '1'; // Manter "Ativo" como padrão
            } else {
                elemento.value = '';
            }
        }
    });
    
    // Limpar checkboxes
    document.querySelectorAll('input[name="categorias[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(cb => cb.checked = false);
    
    // Limpar campos de horário
    const horarioInicio = document.getElementById('horario_inicio');
    const horarioFim = document.getElementById('horario_fim');
    if (horarioInicio) horarioInicio.value = '';
    if (horarioFim) horarioFim.value = '';
    
    // Limpar outros campos se existirem
    const outrosCampos = ['tipo_carga', 'observacoes'];
    outrosCampos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) elemento.value = '';
    });
    
    // Limpar campo de foto
    const fotoInput = document.getElementById('foto');
    if (fotoInput) {
        fotoInput.value = '';
    }
    
    // Resetar preview da foto
    const preview = document.getElementById('foto-preview');
    const container = document.getElementById('preview-container');
    const placeholder = document.getElementById('placeholder-foto');
    
    if (preview) preview.src = '';
    if (container) container.style.display = 'none';
    if (placeholder) placeholder.style.display = 'block';
    
    // Garantir que os campos de data estejam funcionando corretamente
    setTimeout(() => {
        const campoDataNascimento = document.getElementById('data_nascimento');
        const campoValidadeCredencial = document.getElementById('validade_credencial');
        
        if (campoDataNascimento) {
            campoDataNascimento.focus();
            campoDataNascimento.blur();
        }
        if (campoValidadeCredencial) {
            campoValidadeCredencial.focus();
            campoValidadeCredencial.blur();
        }
    }, 100);
}

async function editarInstrutor(id) {
    console.log('🔧 Editando instrutor ID:', id);
    
    try {
        // 1. Abrir modal primeiro
        document.getElementById('modalTitle').textContent = 'Editar Instrutor';
        document.getElementById('acaoInstrutor').value = 'editar';
        document.getElementById('instrutor_id').value = id;
        
        // Abrir modal
        abrirModalInstrutor();
        
        // 2. Aguardar carregamento dos selects
        console.log('📋 Aguardando carregamento dos selects...');
        await carregarCFCsComRetry();
        await carregarUsuariosComRetry();
        
        // 3. Buscar dados do instrutor
        console.log('🔍 Buscando dados do instrutor...');
        const response = await fetch(`${API_CONFIG.getRelativeApiUrl('INSTRUTORES')}?id=${id}`);
        console.log('📡 Resposta da API:', response.status, response.statusText);
        
        const data = await response.json();
        console.log('📊 Dados recebidos:', data);
        
        if (data.success && data.data) {
            console.log('✅ Dados do instrutor carregados, preenchendo formulário...');
            preencherFormularioInstrutor(data.data);
        } else {
            console.error('❌ Erro na API:', data.error);
            mostrarAlerta('Erro ao carregar dados do instrutor: ' + (data.error || 'Dados não encontrados'), 'danger');
        }
    } catch (error) {
        console.error('❌ Erro ao carregar instrutor:', error);
        mostrarAlerta('Erro ao carregar dados do instrutor: ' + error.message, 'danger');
    }
}

function preencherFormularioInstrutor(instrutor) {
    console.log('🔄 Preenchendo formulário com dados:', instrutor);
    
    // Verificar se os selects estão carregados antes de preencher
    const cfcSelect = document.getElementById('cfc_id');
    const usuarioSelect = document.getElementById('usuario_id');
    
    if (cfcSelect && cfcSelect.options.length <= 1) {
        console.warn('⚠️ Select CFC ainda não carregado, aguardando...');
        setTimeout(() => preencherFormularioInstrutor(instrutor), 200);
        return;
    }
    
    if (usuarioSelect && usuarioSelect.options.length <= 1) {
        console.warn('⚠️ Select Usuário ainda não carregado, aguardando...');
        setTimeout(() => preencherFormularioInstrutor(instrutor), 200);
        return;
    }
    
    console.log('✅ Selects carregados, preenchendo formulário...');
    
    // Preencher campos do formulário
    const nomeField = document.getElementById('nome');
    if (nomeField) {
        nomeField.value = instrutor.nome || instrutor.nome_usuario || '';
        console.log('✅ Campo nome preenchido:', nomeField.value);
    }
    
    const cpfField = document.getElementById('cpf');
    if (cpfField) {
        cpfField.value = instrutor.cpf || '';
        console.log('✅ Campo cpf preenchido:', cpfField.value);
        
        // Verificar se o valor foi realmente aplicado
        setTimeout(() => {
            if (cpfField.value !== instrutor.cpf) {
                console.warn('⚠️ Valor do CPF não foi aplicado corretamente, tentando novamente...');
                cpfField.value = instrutor.cpf || '';
                cpfField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }, 100);
    }
    
    const cnhField = document.getElementById('cnh');
    if (cnhField) {
        cnhField.value = instrutor.cnh || '';
        console.log('✅ Campo cnh preenchido:', cnhField.value);
    }
    
    // Preencher campo de data de nascimento de forma segura
    const campoDataNascimento = document.getElementById('data_nascimento');
    if (campoDataNascimento) {
        if (instrutor.data_nascimento && isValidDate(instrutor.data_nascimento)) {
            // Converter formato ISO para brasileiro sem problemas de fuso horário
            const partes = instrutor.data_nascimento.split('-');
            const ano = partes[0];
            const mes = partes[1];
            const dia = partes[2];
            campoDataNascimento.value = `${dia}/${mes}/${ano}`;
            console.log('✅ Campo data_nascimento preenchido:', campoDataNascimento.value);
        } else {
            campoDataNascimento.value = '';
            console.log('⚠️ Campo data_nascimento vazio ou inválido');
        }
        campoDataNascimento.type = 'text';
    }
    
    const emailField = document.getElementById('email');
    if (emailField) {
        emailField.value = instrutor.email || '';
        console.log('✅ Campo email preenchido:', emailField.value);
    }
    
    // Preencher selects com verificação de valores
    const usuarioField = document.getElementById('usuario_id');
    if (usuarioField && instrutor.usuario_id) {
        // Converter para número para garantir compatibilidade
        const usuarioId = parseInt(instrutor.usuario_id);
        console.log('🔍 Debug - Tentando preencher usuário ID:', usuarioId);
        console.log('🔍 Debug - Opções disponíveis:', Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
        
        // Verificar se o valor existe nas opções antes de definir
        const usuarioOption = usuarioField.querySelector(`option[value="${usuarioId}"]`);
        if (usuarioOption) {
            console.log('🔍 Debug - Opção encontrada:', usuarioOption.textContent);
            
            // Remover temporariamente o evento onchange para evitar interferência
            const originalOnChange = usuarioField.getAttribute('onchange');
            usuarioField.removeAttribute('onchange');
            
            usuarioField.value = usuarioId;
            console.log('✅ Campo usuario_id preenchido:', usuarioId);
            console.log('🔍 Debug - Valor após preenchimento:', usuarioField.value);
            
            // Forçar reflow visual para garantir que o valor seja exibido
            usuarioField.style.display = 'none';
            usuarioField.offsetHeight; // Força reflow
            usuarioField.style.display = '';
            
            // Restaurar o evento onchange após um delay
            setTimeout(() => {
                if (originalOnChange) {
                    usuarioField.setAttribute('onchange', originalOnChange);
                    console.log('🔍 Debug - Evento onchange restaurado');
                }
            }, 200);
            
            // Verificação adicional após um delay
            setTimeout(() => {
                console.log('🔍 Debug - Verificação após 100ms - Valor atual:', usuarioField.value);
                if (usuarioField.value !== usuarioId.toString()) {
                    console.warn('⚠️ Valor do usuário não foi aplicado, tentando novamente...');
                    usuarioField.value = usuarioId;
                    console.log('🔍 Debug - Valor reaplicado:', usuarioField.value);
                }
            }, 100);
        } else {
            console.warn('⚠️ Opção de usuário não encontrada para ID:', usuarioId);
            console.log('🔍 Opções disponíveis:', Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
        }
    }
    
    const cfcField = document.getElementById('cfc_id');
    if (cfcField && instrutor.cfc_id) {
        // Converter para número para garantir compatibilidade
        const cfcId = parseInt(instrutor.cfc_id);
        console.log('🔍 Debug - Tentando preencher CFC ID:', cfcId);
        console.log('🔍 Debug - Opções disponíveis:', Array.from(cfcField.options).map(opt => ({value: opt.value, text: opt.textContent})));
        
        // Verificar se o valor existe nas opções antes de definir
        const cfcOption = cfcField.querySelector(`option[value="${cfcId}"]`);
        if (cfcOption) {
            console.log('🔍 Debug - Opção encontrada:', cfcOption.textContent);
            
            cfcField.value = cfcId;
            console.log('✅ Campo cfc_id preenchido:', cfcId);
            console.log('🔍 Debug - Valor após preenchimento:', cfcField.value);
            
            // Forçar reflow visual para garantir que o valor seja exibido
            cfcField.style.display = 'none';
            cfcField.offsetHeight; // Força reflow
            cfcField.style.display = '';
            
            // Verificação adicional após um delay
            setTimeout(() => {
                console.log('🔍 Debug - Verificação após 100ms - Valor atual:', cfcField.value);
                if (cfcField.value !== cfcId.toString()) {
                    console.warn('⚠️ Valor do CFC não foi aplicado, tentando novamente...');
                    cfcField.value = cfcId;
                    console.log('🔍 Debug - Valor reaplicado:', cfcField.value);
                }
            }, 100);
        } else {
            console.warn('⚠️ Opção de CFC não encontrada para ID:', cfcId);
            console.log('🔍 Opções disponíveis:', Array.from(cfcField.options).map(opt => ({value: opt.value, text: opt.textContent})));
        }
    }
    
    const credencialField = document.getElementById('credencial');
    if (credencialField) {
        credencialField.value = instrutor.credencial || '';
        console.log('✅ Campo credencial preenchido:', credencialField.value);
    }
    
    const telefoneField = document.getElementById('telefone');
    if (telefoneField) {
        telefoneField.value = instrutor.telefone || '';
        console.log('✅ Campo telefone preenchido:', telefoneField.value);
        
        // Verificar se o valor foi realmente aplicado
        setTimeout(() => {
            if (telefoneField.value !== instrutor.telefone) {
                console.warn('⚠️ Valor do telefone não foi aplicado corretamente, tentando novamente...');
                telefoneField.value = instrutor.telefone || '';
                telefoneField.dispatchEvent(new Event('input', { bubbles: true }));
            }
        }, 100);
    }
    
    const enderecoField = document.getElementById('endereco');
    if (enderecoField) {
        enderecoField.value = instrutor.endereco || '';
        console.log('✅ Campo endereco preenchido:', enderecoField.value);
    }
    
    const cidadeField = document.getElementById('cidade');
    if (cidadeField) {
        cidadeField.value = instrutor.cidade || '';
        console.log('✅ Campo cidade preenchido:', cidadeField.value);
    }
    
    const ufField = document.getElementById('uf');
    if (ufField) {
        ufField.value = instrutor.uf || '';
        console.log('✅ Campo uf preenchido:', ufField.value);
    }
    
    const ativoField = document.getElementById('ativo');
    if (ativoField) {
        ativoField.value = instrutor.ativo ? '1' : '0';
        console.log('✅ Campo ativo preenchido:', ativoField.value);
    }
    
    const tipoCargaField = document.getElementById('tipo_carga');
    if (tipoCargaField) {
        tipoCargaField.value = instrutor.tipo_carga || '';
        console.log('✅ Campo tipo_carga preenchido:', tipoCargaField.value);
    }
    
    // Preencher campo de validade da credencial de forma segura
    const campoValidadeCredencial = document.getElementById('validade_credencial');
    if (campoValidadeCredencial) {
        if (instrutor.validade_credencial && isValidDate(instrutor.validade_credencial)) {
            // Converter formato ISO para brasileiro sem problemas de fuso horário
            const partes = instrutor.validade_credencial.split('-');
            const ano = partes[0];
            const mes = partes[1];
            const dia = partes[2];
            campoValidadeCredencial.value = `${dia}/${mes}/${ano}`;
            console.log('✅ Campo validade_credencial preenchido:', campoValidadeCredencial.value);
        } else {
            campoValidadeCredencial.value = '';
            console.log('⚠️ Campo validade_credencial vazio ou inválido');
        }
        campoValidadeCredencial.type = 'text';
    }
    
    const observacoesField = document.getElementById('observacoes');
    if (observacoesField) {
        observacoesField.value = instrutor.observacoes || '';
        console.log('✅ Campo observacoes preenchido:', observacoesField.value);
    }
    
    // Limpar checkboxes primeiro
    document.querySelectorAll('input[name="categorias[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(cb => cb.checked = false);
    
    // Marcar categorias selecionadas
    if (instrutor.categoria_habilitacao && instrutor.categoria_habilitacao.trim() !== '' && instrutor.categoria_habilitacao !== '[]' && instrutor.categoria_habilitacao !== '""') {
        try {
            // Tentar fazer parse se for JSON
            let categorias;
            if (instrutor.categoria_habilitacao.startsWith('[') && instrutor.categoria_habilitacao.endsWith(']')) {
                categorias = JSON.parse(instrutor.categoria_habilitacao);
            } else {
                // Se não for JSON, tratar como string separada por vírgula
                categorias = instrutor.categoria_habilitacao.split(',');
            }
            
            categorias.forEach(cat => {
                const catTrim = cat.trim().replace(/"/g, ''); // Remover aspas
                if (catTrim && catTrim !== '' && catTrim !== '""') {
                    const checkbox = document.querySelector(`input[name="categorias[]"][value="${catTrim}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        console.log('✅ Categoria marcada:', catTrim);
                    } else {
                        console.warn('⚠️ Checkbox categoria não encontrado:', catTrim);
                    }
                }
            });
        } catch (error) {
            console.warn('⚠️ Erro ao processar categorias:', error);
        }
    } else {
        console.warn('⚠️ Nenhuma categoria encontrada no instrutor ou campo vazio');
    }
    
    // Marcar dias da semana selecionados
    if (instrutor.dias_semana && instrutor.dias_semana.trim() !== '' && instrutor.dias_semana !== '[]' && instrutor.dias_semana !== '""') {
        try {
            // Tentar fazer parse se for JSON
            let dias;
            if (instrutor.dias_semana.startsWith('[') && instrutor.dias_semana.endsWith(']')) {
                dias = JSON.parse(instrutor.dias_semana);
            } else {
                // Se não for JSON, tratar como string separada por vírgula
                dias = instrutor.dias_semana.split(',');
            }
            
            dias.forEach(dia => {
                const diaTrim = dia.trim().replace(/"/g, ''); // Remover aspas
                if (diaTrim && diaTrim !== '' && diaTrim !== '""') {
                    const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${diaTrim}"]`);
                    if (checkbox) {
                        checkbox.checked = true;
                        console.log('✅ Dia da semana marcado:', diaTrim);
                    } else {
                        console.warn('⚠️ Checkbox dia da semana não encontrado:', diaTrim);
                    }
                }
            });
        } catch (error) {
            console.warn('⚠️ Erro ao processar dias da semana:', error);
        }
    } else {
        console.warn('⚠️ Nenhum dia da semana encontrado no instrutor ou campo vazio');
    }
    
    // Preencher horários
    const horarioInicioField = document.getElementById('horario_inicio');
    if (horarioInicioField && instrutor.horario_inicio) {
        // Converter formato HH:MM:SS para HH:MM
        let horarioInicio = instrutor.horario_inicio;
        if (horarioInicio && horarioInicio.includes(':')) {
            const partes = horarioInicio.split(':');
            if (partes.length >= 2) {
                horarioInicio = `${partes[0]}:${partes[1]}`;
            }
        }
        horarioInicioField.value = horarioInicio;
        console.log('✅ Campo horario_inicio preenchido:', horarioInicioField.value);
    }
    
    const horarioFimField = document.getElementById('horario_fim');
    if (horarioFimField && instrutor.horario_fim) {
        // Converter formato HH:MM:SS para HH:MM
        let horarioFim = instrutor.horario_fim;
        if (horarioFim && horarioFim.includes(':')) {
            const partes = horarioFim.split(':');
            if (partes.length >= 2) {
                horarioFim = `${partes[0]}:${partes[1]}`;
            }
        }
        horarioFimField.value = horarioFim;
        console.log('✅ Campo horario_fim preenchido:', horarioFimField.value);
    }
    
    // Carregar foto existente se houver
    if (instrutor.foto && instrutor.foto.trim() !== '') {
        carregarFotoExistente(instrutor.foto);
    } else {
        // Resetar preview da foto
        const preview = document.getElementById('foto-preview');
        const container = document.getElementById('preview-container');
        const placeholder = document.getElementById('placeholder-foto');
        
        if (preview) preview.src = '';
        if (container) container.style.display = 'none';
        if (placeholder) placeholder.style.display = 'block';
    }
    
    console.log('✅ Formulário preenchido com sucesso!');
    
    // Verificação final dos selects após um pequeno delay
    setTimeout(() => {
        verificarVinculacaoSelects(instrutor);
    }, 200);
}

function visualizarInstrutor(id) {
    console.log('👁️ Visualizando instrutor ID:', id);
    console.log('🔍 API_CONFIG:', API_CONFIG);
    console.log('🔍 URL da API:', API_CONFIG.getRelativeApiUrl('INSTRUTORES'));
    
    try {
        // Buscar dados do instrutor
        const url = `${API_CONFIG.getRelativeApiUrl('INSTRUTORES')}?id=${id}`;
        console.log('🌐 Fazendo fetch para:', url);
        
        fetch(url)
            .then(response => {
                console.log('📡 Resposta recebida:', response.status, response.statusText);
                return response.json();
            })
            .then(data => {
                console.log('📊 Dados recebidos:', data);
                if (data.success && data.data) {
                    console.log('✅ Dados válidos, abrindo modal de visualização...');
                    abrirModalVisualizacao(data.data);
                } else {
                    console.error('❌ Dados inválidos:', data);
                    mostrarAlerta('Erro ao carregar dados do instrutor: ' + (data.error || 'Dados não encontrados'), 'danger');
                }
            })
            .catch(error => {
                console.error('❌ Erro ao carregar instrutor:', error);
                mostrarAlerta('Erro ao carregar dados do instrutor: ' + error.message, 'danger');
            });
    } catch (error) {
        console.error('❌ Erro na função visualizarInstrutor:', error);
        mostrarAlerta('Erro interno: ' + error.message, 'danger');
    }
}

function abrirModalVisualizacao(instrutor) {
    console.log('📋 Abrindo modal de visualização para instrutor:', instrutor);
    
    // Fechar apenas o modal de edição, não o de visualização
    const modalInstrutor = document.getElementById('modalInstrutor');
    if (modalInstrutor && modalInstrutor.style.display === 'block') {
        if (typeof fecharModalInstrutor === 'function') {
            fecharModalInstrutor();
        }
    }
    
    // Criar modal se não existir
    let modal = document.getElementById('modalVisualizacaoInstrutor');
    if (!modal) {
        modal = criarModalVisualizacao();
        document.body.appendChild(modal);
    }
    
    // Preencher dados do instrutor
    preencherModalVisualizacao(instrutor);
    
    // FORÇAR exibição do modal no mobile
    modal.style.setProperty('display', 'block', 'important');
    modal.style.setProperty('visibility', 'visible', 'important');
    modal.style.setProperty('opacity', '1', 'important');
    modal.style.setProperty('z-index', '99999', 'important');
    modal.style.setProperty('position', 'fixed', 'important');
    modal.style.setProperty('top', '0', 'important');
    modal.style.setProperty('left', '0', 'important');
    modal.style.setProperty('width', '100vw', 'important');
    modal.style.setProperty('height', '100vh', 'important');
    modal.classList.add('show');
    
    // Garantir que o modal-dialog seja visível
    const modalDialog = modal.querySelector('.custom-modal-dialog');
    if (modalDialog) {
        modalDialog.style.setProperty('z-index', '100000', 'important');
        modalDialog.style.setProperty('position', 'relative', 'important');
        modalDialog.style.setProperty('opacity', '1', 'important');
        modalDialog.style.setProperty('transform', 'translateY(0)', 'important');
        modalDialog.style.setProperty('display', 'block', 'important');
    }
    
    // Animar abertura
    setTimeout(() => {
        if (modalDialog) {
            modalDialog.style.opacity = '1';
            modalDialog.style.transform = 'translateY(0)';
        }
    }, 100);
    
    console.log('✅ Modal de visualização aberto com sucesso');
}

function fecharOutrosModais() {
    // Fechar modal de instrutor se estiver aberto
    const modalInstrutor = document.getElementById('modalInstrutor');
    if (modalInstrutor && modalInstrutor.style.display === 'block') {
        if (typeof fecharModalInstrutor === 'function') {
            fecharModalInstrutor();
        }
    }
    
    // Fechar modal de visualização se estiver aberto
    const modalVisualizacao = document.getElementById('modalVisualizacaoInstrutor');
    if (modalVisualizacao && modalVisualizacao.style.display === 'block') {
        fecharModalVisualizacao();
    }
}

function excluirInstrutor(id) {
    if (confirm('Tem certeza que deseja excluir este instrutor?')) {
        fetch(`${API_CONFIG.getRelativeApiUrl('INSTRUTORES')}?id=${id}`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Instrutor excluído com sucesso!', 'success');
                carregarInstrutores(); // Recarregar tabela
            } else {
                mostrarAlerta(data.error || 'Erro ao excluir instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao excluir instrutor', 'danger');
        });
    }
}

function salvarInstrutor() {
    console.log('💾 Salvando instrutor...');
    
    // Proteção contra múltiplos cliques
    const btnSalvar = document.getElementById('btnSalvarInstrutor');
    if (btnSalvar.disabled) {
        console.log('⚠️ Salvamento já em andamento, ignorando clique...');
        return;
    }
    
    try {
        // Validar formulário usando a nova validação inteligente
        if (!validarFormularioInstrutor()) {
            return;
        }
        
        // Preparar dados usando a nova função
        const formData = prepararDadosFormulario();
        
        console.log('📋 Dados preparados:', Object.fromEntries(formData));
        
        // Preparar dados para envio
        const categoriasSelecionadas = formData.get('categoria_habilitacao') ? formData.get('categoria_habilitacao').split(',') : [];
        const diasSemanaSelecionados = formData.get('dias_semana') ? formData.get('dias_semana').split(',') : [];
        
        console.log('📋 Categorias do FormData:', formData.get('categoria_habilitacao'));
        console.log('📋 Dias da semana do FormData:', formData.get('dias_semana'));
        console.log('📋 Categorias processadas:', categoriasSelecionadas);
        console.log('📋 Dias processados:', diasSemanaSelecionados);
        
        // Converter datas do formato brasileiro para ISO (se existirem)
        const dataNascimento = formData.get('data_nascimento') ? converterDataBrasileiraParaISO(formData.get('data_nascimento')) : '';
        const validadeCredencial = formData.get('validade_credencial') ? converterDataBrasileiraParaISO(formData.get('validade_credencial')) : '';
        
        const instrutorData = {
            nome: formData.get('nome').trim(),
            email: formData.get('email').trim(),
            cpf: formData.get('cpf') || '',
            cnh: formData.get('cnh') || '',
            telefone: formData.get('telefone') || '',
            cfc_id: formData.get('cfc_id'),
            credencial: formData.get('credencial').trim(),
            categoria_habilitacao: categoriasSelecionadas.join(','),
            categorias: categoriasSelecionadas,
            dias_semana: diasSemanaSelecionados,
            ativo: formData.get('ativo') === '1',
            endereco: formData.get('endereco') || '',
            cidade: formData.get('cidade') || '',
            uf: formData.get('uf') || '',
            tipo_carga: formData.get('tipo_carga') || '',
            validade_credencial: validadeCredencial || '',
            observacoes: formData.get('observacoes') || '',
            horario_inicio: formData.get('horario_inicio') || '',
            horario_fim: formData.get('horario_fim') || ''
        };
        
        // Adicionar dados condicionais
        if (formData.get('usuario_id')) {
            instrutorData.usuario_id = formData.get('usuario_id');
        } else {
            // Novo usuário
            instrutorData.senha = formData.get('senha');
            instrutorData.cpf = formData.get('cpf_usuario'); // Usar cpf_usuario para novo usuário
        }
        
        // Garantir que CPF seja enviado (pode vir do campo cpf ou cpf_usuario)
        if (!instrutorData.cpf && formData.get('cpf')) {
            instrutorData.cpf = formData.get('cpf');
        }
        
        // Garantir que CNH seja enviado
        if (formData.get('cnh')) {
            instrutorData.cnh = formData.get('cnh');
        }
        
        // Garantir que data de nascimento seja enviado
        if (dataNascimento) {
            instrutorData.data_nascimento = dataNascimento;
        }
        
        const acao = formData.get('acao');
        const instrutor_id = formData.get('instrutor_id');
        
        console.log('🔍 Debug - Ação detectada:', acao);
        console.log('🔍 Debug - ID do instrutor:', instrutor_id);
        console.log('🔍 Debug - Campo acaoInstrutor.value:', document.getElementById('acaoInstrutor')?.value);
        
        if (acao === 'editar' && instrutor_id) {
            instrutorData.id = instrutor_id;
            console.log('✅ Modo EDITAÇÃO detectado, ID:', instrutor_id);
        } else {
            console.log('⚠️ Modo CRIAÇÃO detectado ou ID não encontrado');
        }
        
        // Mostrar loading
        const btnSalvar = document.getElementById('btnSalvarInstrutor');
        const originalText = btnSalvar.innerHTML;
        btnSalvar.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Salvando...';
        btnSalvar.disabled = true;
        
        // Fazer requisição para a API - URL CORRIGIDA
        const url = API_CONFIG.getRelativeApiUrl('INSTRUTORES');
        const method = acao === 'editar' ? 'PUT' : 'POST';
        
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(instrutorData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta(data.message || 'Instrutor salvo com sucesso!', 'success');
                
                // Fechar modal
                fecharModalInstrutor();
                
                // Limpar formulário
                const form = document.getElementById('formInstrutor');
                if (form) form.reset();
                
                // Recarregar página para mostrar dados atualizados
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            } else {
                mostrarAlerta(data.error || 'Erro ao salvar instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao salvar instrutor:', error);
            mostrarAlerta('Erro ao salvar instrutor: ' + error.message, 'danger');
        })
        .finally(() => {
            // Restaurar botão
            btnSalvar.innerHTML = originalText;
            btnSalvar.disabled = false;
        });
    } catch (error) {
        console.error('❌ Erro na preparação dos dados:', error);
        mostrarAlerta('Erro na preparação dos dados: ' + error.message, 'danger');
        
        // Restaurar botão em caso de erro
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    }
}

function mostrarAlerta(mensagem, tipo) {
    // Criar alerta personalizado
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show position-fixed`;
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alertDiv);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function filtrarInstrutores() {
    const status = document.getElementById('filtroStatus').value;
    const cfc = document.getElementById('filtroCFC').value;
    const categoria = document.getElementById('filtroCategoria').value;
    const busca = document.getElementById('buscaInstrutor').value.toLowerCase();
    
    // Implementar filtros aqui
    console.log('Filtrando:', { status, cfc, categoria, busca });
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaInstrutor').value = '';
    
    // Recarregar todos os instrutores
    carregarInstrutores();
}

function exportarInstrutores() {
    // Implementar exportação para CSV/Excel
    mostrarAlerta('Funcionalidade de exportação será implementada em breve!', 'info');
}

function imprimirInstrutores() {
    // Implementar impressão
    mostrarAlerta('Funcionalidade de impressão será implementada em breve!', 'info');
}

// Inicializar página
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Inicializando página de instrutores...');
    
    // Verificar se há parâmetros na URL que podem causar abertura automática do modal
    const urlParams = new URLSearchParams(window.location.search);
    const modalParam = urlParams.get('modal');
    const novoParam = urlParams.get('novo');
    const criarParam = urlParams.get('criar');
    
    console.log('🔍 Parâmetros da URL:', {
        modal: modalParam,
        novo: novoParam,
        criar: criarParam,
        url: window.location.href
    });
    
    // Garantir que o modal esteja fechado no carregamento
    const modal = document.getElementById('modalInstrutor');
    if (modal) {
        console.log('🔒 Forçando fechamento do modal no carregamento...');
        modal.style.setProperty('display', 'none', 'important');
        modal.classList.remove('show');
        modal.style.setProperty('visibility', 'hidden', 'important');
        modal.style.setProperty('opacity', '0', 'important');
    }
    
    // Carregar dados iniciais
    carregarInstrutores();
    
    // Configurar campos de data para funcionarem corretamente
    configurarCamposData();
    
    // Verificar se estamos no mobile e ajustar layout
    verificarLayoutMobile();
    
    // CORREÇÃO TEMPORÁRIA: Forçar exibição dos elementos após carregamento
    setTimeout(() => {
        console.log('🔧 CORREÇÃO TEMPORÁRIA: Forçando exibição dos elementos...');
        
        const tableContainer = document.querySelector('.table-responsive');
        const mobileCards = document.getElementById('mobileInstrutorCards') || document.querySelector('.mobile-instrutor-cards');
        
        if (tableContainer) {
            tableContainer.style.setProperty('display', 'block', 'important');
            tableContainer.style.setProperty('visibility', 'visible', 'important');
            tableContainer.style.setProperty('opacity', '1', 'important');
            console.log('✅ Tabela forçada a aparecer');
        }
        
        if (mobileCards) {
            mobileCards.style.setProperty('display', 'block', 'important');
            mobileCards.style.setProperty('visibility', 'visible', 'important');
            mobileCards.style.setProperty('opacity', '1', 'important');
            console.log('✅ Cards mobile forçados a aparecer');
        }
        
        // Verificar se há dados na tabela
        const tbody = document.querySelector('#tabelaInstrutores tbody');
        if (tbody && tbody.children.length === 0) {
            console.log('⚠️ Tabela vazia, recarregando dados...');
            carregarInstrutores();
        }
    }, 500);
    
    // Adicionar listener para fechar modal ao clicar fora
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fecharModalInstrutor();
            }
        });
        
        // Adicionar listener para tecla ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.style.display === 'block') {
                fecharModalInstrutor();
            }
        });
    }
    
    // Listener para mudanças de tamanho da tela
    window.addEventListener('resize', verificarLayoutMobile);
    
    console.log('✅ Página de instrutores inicializada com sucesso');
});

// Função para verificar se estamos no mobile e ajustar layout
function verificarLayoutMobile() {
    const isMobile = window.innerWidth <= 768;
    const tableContainer = document.querySelector('.table-responsive');
    let mobileCards = document.getElementById('mobileInstrutorCards');
    
    // Fallback para encontrar o container mobile
    if (!mobileCards) {
        mobileCards = document.querySelector('.mobile-instrutor-cards');
        console.log('🔍 Usando fallback .mobile-instrutor-cards:', !!mobileCards);
    }
    
    console.log('📱 Verificando layout mobile:', {
        isMobile: isMobile,
        windowWidth: window.innerWidth,
        tableContainer: !!tableContainer,
        mobileCards: !!mobileCards,
        mobileCardsChildren: mobileCards ? mobileCards.children.length : 0
    });
    
    if (isMobile) {
        console.log('📱 MODO MOBILE ATIVADO');
        
        // Forçar exibição dos cards mobile
        if (mobileCards) {
            mobileCards.style.setProperty('display', 'block', 'important');
            mobileCards.style.setProperty('visibility', 'visible', 'important');
            mobileCards.style.setProperty('opacity', '1', 'important');
            console.log('✅ Cards mobile forçados a aparecer');
            
            // Verificar se há cards criados
            if (mobileCards.children.length === 0) {
                console.log('⚠️ Nenhum card mobile encontrado, recarregando dados...');
                carregarInstrutores();
            } else {
                console.log('✅ Cards mobile encontrados:', mobileCards.children.length);
            }
        } else {
            console.error('❌ Elemento mobileInstrutorCards não encontrado!');
        }
        
        // Ocultar tabela
        if (tableContainer) {
            tableContainer.style.setProperty('display', 'none', 'important');
            console.log('✅ Tabela oculta no mobile');
        }
    } else {
        console.log('🖥️ MODO DESKTOP ATIVADO');
        
        // Forçar exibição da tabela
        if (tableContainer) {
            tableContainer.style.setProperty('display', 'block', 'important');
            console.log('✅ Tabela exibida no desktop');
        }
        
        // Ocultar cards mobile
        if (mobileCards) {
            mobileCards.style.setProperty('display', 'none', 'important');
            console.log('✅ Cards mobile ocultos no desktop');
        }
    }
}

// Função para configurar campos de data híbridos
function configurarCamposData() {
    const camposData = ['data_nascimento', 'validade_credencial'];
    
    camposData.forEach(campoId => {
        const campo = document.getElementById(campoId);
        
        if (campo) {
            // Configurar campo híbrido (texto + calendário)
            configurarCampoDataHibrido(campoId, campo);
        }
    });
}

// Função para validar se uma data é válida
function isValidDate(dateString) {
    if (!dateString) return false;
    
    // Verificar formato yyyy-MM-dd
    const dateRegex = /^\d{4}-\d{2}-\d{2}$/;
    if (!dateRegex.test(dateString)) return false;
    
    // Extrair partes da data
    const partes = dateString.split('-');
    const ano = parseInt(partes[0]);
    const mes = parseInt(partes[1]);
    const dia = parseInt(partes[2]);
    
    // Validar valores básicos
    if (ano < 1900 || ano > 2100) return false;
    if (mes < 1 || mes > 12) return false;
    if (dia < 1 || dia > 31) return false;
    
    // Verificar se é uma data válida usando Date apenas para validação
    const date = new Date(ano, mes - 1, dia);
    if (date.getDate() !== dia || date.getMonth() !== mes - 1 || date.getFullYear() !== ano) {
        return false;
    }
    
    return true;
}

// Função para configurar campo de data com máscara e calendário discreto
function configurarCampoDataHibrido(campoId, campo) {
    // Garantir que seja do tipo texto
    campo.type = 'text';
    
    // Criar wrapper para o campo com posicionamento relativo
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    wrapper.style.display = 'inline-block';
    wrapper.style.width = '100%';
    
    // Mover o campo para dentro do wrapper
    campo.parentNode.insertBefore(wrapper, campo);
    wrapper.appendChild(campo);
    
    // Criar botão do calendário discreto
    const btnCalendario = document.createElement('button');
    btnCalendario.type = 'button';
    btnCalendario.innerHTML = '📅';
    btnCalendario.style.cssText = `
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: transparent;
        border: none;
        font-size: 14px;
        cursor: pointer;
        padding: 2px 4px;
        border-radius: 3px;
        color: #6c757d;
        z-index: 5;
        opacity: 0.7;
        transition: all 0.2s ease;
    `;
    btnCalendario.title = 'Abrir calendário';
    
    // Adicionar botão ao wrapper
    wrapper.appendChild(btnCalendario);
    
    // Aplicar máscara de data brasileira em tempo real
    campo.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Limitar a 8 dígitos
        if (value.length > 8) {
            value = value.substring(0, 8);
        }
        
        // Aplicar máscara dd/mm/aaaa automaticamente
        if (value.length <= 2) {
            value = value;
        } else if (value.length <= 4) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        } else if (value.length <= 8) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4);
        }
        
        e.target.value = value;
    });
    
    // Validar data quando perder foco
    campo.addEventListener('blur', function() {
        const valorTexto = this.value.trim();
        if (valorTexto) {
            if (!converterDataBrasileiraParaISO(valorTexto)) {
                console.warn(`❌ Formato de data inválido: ${valorTexto}. Use dd/mm/aaaa`);
                this.value = '';
                return;
            }
            
            // Validações específicas por campo
            if (campoId === 'data_nascimento') {
                const data = converterDataBrasileiraParaISO(valorTexto);
                if (data && compararDatas(data, getDataAtual()) > 0) {
                    console.warn('Data de nascimento não pode ser no futuro');
                    this.value = '';
                    return;
                }
            }
            
            if (campoId === 'validade_credencial') {
                const data = converterDataBrasileiraParaISO(valorTexto);
                if (data && compararDatas(data, getDataAtual()) < 0) {
                    console.warn('Validade da credencial deve ser no futuro');
                    this.value = '';
                    return;
                }
            }
            
            console.log(`✅ Data válida definida no campo ${campoId}: ${valorTexto}`);
        }
    });
    
    // Permitir tecla Enter para confirmar
    campo.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') {
            this.blur();
        }
    });
    
    // Funcionalidade do calendário discreto
    btnCalendario.addEventListener('click', function() {
        // Criar campo date temporário para o calendário
        const campoDateTemp = document.createElement('input');
        campoDateTemp.type = 'date';
        campoDateTemp.style.cssText = `
            position: absolute;
            left: -9999px;
            opacity: 0;
        `;
        
        // Definir valor atual se existir
        const valorAtual = campo.value.trim();
        if (valorAtual) {
            const dataConvertida = converterDataBrasileiraParaISO(valorAtual);
            if (dataConvertida) {
                campoDateTemp.value = dataConvertida;
            }
        }
        
        // Adicionar ao DOM temporariamente
        document.body.appendChild(campoDateTemp);
        
        // Focar no campo date para abrir o calendário
        campoDateTemp.focus();
        campoDateTemp.click();
        
        // Listener para quando uma data for selecionada
        campoDateTemp.addEventListener('change', function() {
            if (this.value) {
                // Converter de volta para formato brasileiro sem problemas de fuso horário
                const partes = this.value.split('-');
                const ano = partes[0];
                const mes = partes[1];
                const dia = partes[2];
                const dataBrasileira = `${dia}/${mes}/${ano}`;
                
                // Atualizar o campo de texto
                campo.value = dataBrasileira;
                campo.dispatchEvent(new Event('input'));
                campo.dispatchEvent(new Event('blur'));
                
                console.log(`✅ Data selecionada no calendário: ${dataBrasileira}`);
            }
            
            // Remover campo temporário
            document.body.removeChild(campoDateTemp);
        });
        
        // Listener para quando o campo perder foco sem seleção
        campoDateTemp.addEventListener('blur', function() {
            setTimeout(() => {
                if (document.body.contains(campoDateTemp)) {
                    document.body.removeChild(campoDateTemp);
                }
            }, 100);
        });
    });
    
    // Hover effects para o botão do calendário
    btnCalendario.addEventListener('mouseenter', function() {
        this.style.opacity = '1';
        this.style.backgroundColor = '#f8f9fa';
        this.style.color = '#495057';
    });
    
    btnCalendario.addEventListener('mouseleave', function() {
        this.style.opacity = '0.7';
        this.style.backgroundColor = 'transparent';
        this.style.color = '#6c757d';
    });
    
    // Mostrar botão quando o campo receber foco
    campo.addEventListener('focus', function() {
        btnCalendario.style.opacity = '1';
    });
    
    // Ocultar botão quando o campo perder foco (se não estiver sendo usado)
    campo.addEventListener('blur', function() {
        setTimeout(() => {
            if (!btnCalendario.matches(':hover')) {
                btnCalendario.style.opacity = '0.7';
            }
        }, 200);
    });
}

// Função para comparar datas sem problemas de fuso horário
function compararDatas(data1, data2) {
    // Converter ambas as datas para YYYY-MM-DD se necessário
    const data1ISO = typeof data1 === 'string' ? data1 : data1.toISOString().split('T')[0];
    const data2ISO = typeof data2 === 'string' ? data2 : data2.toISOString().split('T')[0];
    
    return data1ISO.localeCompare(data2ISO);
}

// Função para obter data atual no formato YYYY-MM-DD
function getDataAtual() {
    const hoje = new Date();
    const ano = hoje.getFullYear();
    const mes = String(hoje.getMonth() + 1).padStart(2, '0');
    const dia = String(hoje.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}
function converterDataBrasileiraParaISO(dataBrasileira) {
    if (!dataBrasileira || dataBrasileira.trim() === '') {
        return null; // Retorna null para campos vazios
    }
    
    // Verificar se já está no formato YYYY-MM-DD
    if (/^\d{4}-\d{2}-\d{2}$/.test(dataBrasileira)) {
        return dataBrasileira;
    }
    
    // Verificar formato dd/mm/aaaa
    const regex = /^(\d{2})\/(\d{2})\/(\d{4})$/;
    const match = dataBrasileira.match(regex);
    
    if (!match) return null;
    
    const dia = parseInt(match[1]);
    const mes = parseInt(match[2]);
    const ano = parseInt(match[3]);
    
    // Validar valores
    if (dia < 1 || dia > 31) return null;
    if (mes < 1 || mes > 12) return null;
    if (ano < 1900 || ano > 2100) return null;
    
    // Verificar se a data é válida
    const data = new Date(ano, mes - 1, dia);
    if (data.getDate() !== dia || data.getMonth() !== mes - 1 || data.getFullYear() !== ano) {
        return null;
    }
    
    // Retornar no formato ISO sem conversão de fuso horário
    return `${ano}-${mes.toString().padStart(2, '0')}-${dia.toString().padStart(2, '0')}`;
}



function carregarInstrutores() {
    console.log('🔍 Iniciando carregamento de instrutores...');
    
    // DEBUG: Verificar configuração
    console.log('🔧 API_CONFIG:', API_CONFIG);
    console.log('🔧 typeof API_CONFIG:', typeof API_CONFIG);
    
    const urlInstrutores = API_CONFIG.getRelativeApiUrl('INSTRUTORES');
    console.log('🌐 URL construída para Instrutores:', urlInstrutores);
    
    // Carregar instrutores para a tabela
    fetch(urlInstrutores)
        .then(response => {
            console.log('📡 Resposta da API Instrutores:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados recebidos da API Instrutores:', data);
            if (data.success) {
                console.log('✅ Sucesso ao carregar instrutores:', data.data.length, 'instrutores');
                preencherTabelaInstrutores(data.data);
                atualizarEstatisticas(data.data);
                
                // Forçar verificação do layout mobile após carregamento
                setTimeout(() => {
                    console.log('🔄 Verificando layout mobile após carregamento...');
                    verificarLayoutMobile();
                }, 200);
            } else {
                console.error('❌ Erro na API Instrutores:', data.error);
                mostrarAlerta('Erro ao carregar instrutores: ' + (data.error || 'Erro desconhecido'), 'danger');
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar instrutores:', error);
            mostrarAlerta('Erro ao carregar instrutores: ' + error.message, 'danger');
        });
}

function preencherTabelaInstrutores(instrutores) {
    console.log('🔍 Preenchendo tabela e cards mobile com', instrutores.length, 'instrutores');
    
    const tbody = document.querySelector('#tabelaInstrutores tbody');
    const mobileCards = document.getElementById('mobileInstrutorCards');
    
    console.log('📊 Elementos encontrados:');
    console.log('  - tbody:', tbody);
    console.log('  - mobileCards:', mobileCards);
    
    // Verificar se os elementos existem
    if (!tbody) {
        console.error('❌ Elemento #tabelaInstrutores tbody não encontrado!');
        return;
    }
    
    if (!mobileCards) {
        console.error('❌ Elemento #mobileInstrutorCards não encontrado!');
        console.log('🔍 Tentando criar elemento mobileInstrutorCards...');
        
        // Tentar encontrar o container mobile-instrutor-cards
        const mobileContainer = document.querySelector('.mobile-instrutor-cards');
        if (mobileContainer) {
            console.log('✅ Container .mobile-instrutor-cards encontrado, usando ele');
            mobileContainer.innerHTML = '';
        } else {
            console.error('❌ Container .mobile-instrutor-cards também não encontrado!');
            return;
        }
    }
    
    tbody.innerHTML = '';
    if (mobileCards) {
        mobileCards.innerHTML = '';
    } else {
        const mobileContainer = document.querySelector('.mobile-instrutor-cards');
        if (mobileContainer) {
            mobileContainer.innerHTML = '';
        }
    }
    
    instrutores.forEach((instrutor, index) => {
        console.log(`📝 Processando instrutor ${index + 1}:`, instrutor.nome || instrutor.nome_usuario);
        
        // Usar o nome correto (nome_usuario se nome estiver vazio)
        const nomeExibicao = instrutor.nome || instrutor.nome_usuario || 'N/A';
        const cfcExibicao = instrutor.cfc_nome || 'N/A';
        
        // Criar linha da tabela (desktop)
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 32px; height: 32px;">
                        <span class="text-white fw-bold">${nomeExibicao.charAt(0).toUpperCase()}</span>
                    </div>
                    ${nomeExibicao}
                </div>
            </td>
            <td>${instrutor.email || 'N/A'}</td>
            <td>${cfcExibicao}</td>
            <td>${instrutor.credencial || 'N/A'}</td>
            <td>
                <span class="badge bg-info">${formatarCategorias(instrutor.categorias_json) || 'N/A'}</span>
            </td>
            <td>
                <span class="badge ${instrutor.ativo ? 'bg-success' : 'bg-danger'}">
                    ${instrutor.ativo ? 'ATIVO' : 'INATIVO'}
                </span>
            </td>
            <td>
                <div class="btn-group-vertical btn-group-sm">
                    <button class="btn btn-info btn-sm" onclick="visualizarInstrutor(${instrutor.id})" title="Visualizar">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-primary btn-sm" onclick="editarInstrutor(${instrutor.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-danger btn-sm" onclick="excluirInstrutor(${instrutor.id})" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
        tbody.appendChild(row);
        
        // Criar card mobile
        const card = document.createElement('div');
        card.className = 'mobile-instrutor-card';
        card.innerHTML = `
            <div class="mobile-instrutor-header">
                <div class="mobile-instrutor-avatar">
                    <div class="avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <span class="text-white fw-bold">${nomeExibicao.charAt(0).toUpperCase()}</span>
                    </div>
                </div>
                <div class="mobile-instrutor-info">
                    <div class="mobile-instrutor-title">${nomeExibicao}</div>
                    <div class="mobile-instrutor-email">${instrutor.email || 'N/A'}</div>
                </div>
                <div class="mobile-instrutor-status">
                    <span class="badge ${instrutor.ativo ? 'bg-success' : 'bg-danger'}">
                        ${instrutor.ativo ? 'ATIVO' : 'INATIVO'}
                    </span>
                </div>
            </div>
            
            <div class="mobile-instrutor-body">
                <div class="mobile-instrutor-field">
                    <span class="mobile-instrutor-label">CFC:</span>
                    <span class="mobile-instrutor-value">${cfcExibicao}</span>
                </div>
                <div class="mobile-instrutor-field">
                    <span class="mobile-instrutor-label">Credencial:</span>
                    <span class="mobile-instrutor-value">${instrutor.credencial || 'N/A'}</span>
                </div>
                <div class="mobile-instrutor-field">
                    <span class="mobile-instrutor-label">Categorias:</span>
                    <span class="mobile-instrutor-value">
                        <span class="badge bg-info">${formatarCategorias(instrutor.categorias_json) || 'N/A'}</span>
                    </span>
                </div>
            </div>
            
            <div class="mobile-instrutor-actions">
                <button class="btn btn-info" onclick="visualizarInstrutor(${instrutor.id})" title="Visualizar">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="btn btn-primary" onclick="editarInstrutor(${instrutor.id})" title="Editar">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-danger" onclick="excluirInstrutor(${instrutor.id})" title="Excluir">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        `;
        
        // Adicionar card ao container correto
        if (mobileCards) {
            mobileCards.appendChild(card);
        } else {
            const mobileContainer = document.querySelector('.mobile-instrutor-cards');
            if (mobileContainer) {
                mobileContainer.appendChild(card);
            }
        }
        
        console.log(`✅ Card mobile criado para: ${nomeExibicao}`);
    });
    
    // Log final
    const finalMobileCards = mobileCards || document.querySelector('.mobile-instrutor-cards');
    console.log('📱 Cards mobile criados:', finalMobileCards ? finalMobileCards.children.length : 0);
    console.log('🖥️ Linhas da tabela criadas:', tbody.children.length);
           
           // Forçar exibição dos cards mobile após criação
           setTimeout(() => {
               console.log('🔄 Forçando verificação do layout após criação dos cards...');
               verificarLayoutMobile();
               
               // Verificar se os cards estão visíveis no mobile
               const isMobile = window.innerWidth <= 768;
               if (isMobile && mobileCards) {
                   console.log('📱 Verificando visibilidade dos cards mobile...');
                   console.log('  - mobileCards.style.display:', mobileCards.style.display);
                   console.log('  - mobileCards.offsetHeight:', mobileCards.offsetHeight);
                   console.log('  - mobileCards.children.length:', mobileCards.children.length);
                   
                   if (mobileCards.children.length > 0) {
                       console.log('✅ Cards mobile criados e devem estar visíveis');
                   } else {
                       console.error('❌ Nenhum card mobile foi criado!');
                   }
               }
           }, 100);
}

// Função para formatar categorias de habilitação
function formatarCategorias(categoriasJson) {
    if (!categoriasJson) return '';
    
    try {
        let categorias = [];
        
        // Se for string JSON, fazer parse
        if (typeof categoriasJson === 'string') {
            if (categoriasJson.trim() === '') return '';
            categorias = JSON.parse(categoriasJson);
        } else if (Array.isArray(categoriasJson)) {
            // Se já for array
            categorias = categoriasJson;
        } else {
            return '';
        }
        
        // Retornar categorias formatadas
        return categorias.join(', ');
        
    } catch (error) {
        console.warn('⚠️ Erro ao formatar categorias:', error);
        return '';
    }
}

// Função para formatar categorias do JSON
function formatarCategorias(categoriasJson) {
    if (!categoriasJson) return '';
    
    try {
        let categorias;
        
        // Se já é um array
        if (Array.isArray(categoriasJson)) {
            categorias = categoriasJson;
        }
        // Se é uma string JSON
        else if (typeof categoriasJson === 'string') {
            if (categoriasJson.trim() === '') return '';
            categorias = JSON.parse(categoriasJson);
        }
        // Se é uma string separada por vírgulas
        else if (typeof categoriasJson === 'string' && categoriasJson.includes(',')) {
            categorias = categoriasJson.split(',').map(cat => cat.trim());
        }
        else {
            return categoriasJson.toString();
        }
        
        // Verificar se é um array válido
        if (!Array.isArray(categorias)) {
            return categoriasJson.toString();
        }
        
        // Retornar categorias formatadas
        return categorias.join(', ');
        
    } catch (error) {
        console.warn('⚠️ Erro ao formatar categorias:', error);
        return categoriasJson.toString();
    }
}

function atualizarEstatisticas(instrutores) {
    const total = instrutores.length;
    const ativos = instrutores.filter(i => i.ativo).length;
    
    document.getElementById('totalInstrutores').textContent = total;
    document.getElementById('instrutoresAtivos').textContent = ativos;
}

// Função com retry para carregar CFCs
window.carregarCFCsComRetry = async function() {
    const maxTentativas = 5;
    let tentativa = 0;
    
    while (tentativa < maxTentativas) {
        const select = document.getElementById('cfc_id');
        if (select) {
            console.log('✅ Select CFC encontrado, carregando dados...');
            await carregarCFCs();
            return;
        }
        tentativa++;
        console.log(`⏳ Tentativa ${tentativa}: Aguardando select CFC...`);
        await new Promise(resolve => setTimeout(resolve, 200));
    }
    console.error('❌ Select CFC não encontrado após todas as tentativas');
}

async function carregarCFCs() {
    try {
        const url = API_CONFIG.getRelativeApiUrl('CFCs');
        console.log('📡 Carregando CFCs de:', url);
        
        const response = await fetch(url);
        console.log('📡 Resposta da API CFCs:', response.status, response.statusText);
        
        const data = await response.json();
        console.log('📊 Dados recebidos da API CFCs:', data);
        
        if (data.success && data.data) {
            const selectCFC = document.getElementById('cfc_id');
            const filtroCFC = document.getElementById('filtroCFC');
            
            if (selectCFC) {
                selectCFC.innerHTML = '<option value="">Selecione um CFC</option>';
                
                data.data.forEach(cfc => {
                    const option = document.createElement('option');
                    option.value = cfc.id;
                    option.textContent = cfc.nome;
                    selectCFC.appendChild(option);
                    console.log('✅ CFC adicionado:', cfc.nome);
                });
                
                // FORÇAR ATUALIZAÇÃO VISUAL
                selectCFC.style.display = 'none';
                selectCFC.offsetHeight; // Trigger reflow
                selectCFC.style.display = '';
            }
            
            // Também preencher o filtro
            if (filtroCFC) {
                filtroCFC.innerHTML = '<option value="">Todos</option>';
                data.data.forEach(cfc => {
                    const option = document.createElement('option');
                    option.value = cfc.id;
                    option.textContent = cfc.nome;
                    filtroCFC.appendChild(option);
                });
            }
            
            console.log(`✅ ${data.data.length} CFCs carregados com sucesso!`);
        } else {
            console.error('❌ Erro na API CFCs:', data.error);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar CFCs:', error);
    }
}

// Função com retry para carregar usuários
window.carregarUsuariosComRetry = async function() {
    const maxTentativas = 5;
    let tentativa = 0;
    
    while (tentativa < maxTentativas) {
        const select = document.getElementById('usuario_id');
        if (select) {
            console.log('✅ Select Usuário encontrado, carregando dados...');
            await carregarUsuarios();
            return;
        }
        tentativa++;
        console.log(`⏳ Tentativa ${tentativa}: Aguardando select Usuário...`);
        await new Promise(resolve => setTimeout(resolve, 200));
    }
    console.error('❌ Select Usuário não encontrado após todas as tentativas');
}

window.carregarUsuarios = async function() {
    try {
        const url = API_CONFIG.getRelativeApiUrl('USUARIOS');
        console.log('📡 Carregando usuários de:', url);
        
        const response = await fetch(url);
        console.log('📡 Resposta da API Usuários:', response.status, response.statusText);
        
        const data = await response.json();
        console.log('📊 Dados recebidos da API Usuários:', data);
        
        if (data.success && data.data) {
            const select = document.getElementById('usuario_id');
            if (select) {
                select.innerHTML = '<option value="">Selecione um usuário (opcional)</option>';
                
                data.data.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = `${usuario.nome} (${usuario.email})`;
                    select.appendChild(option);
                    console.log('✅ Usuário adicionado:', usuario.nome);
                });
                
                // FORÇAR ATUALIZAÇÃO VISUAL
                select.style.display = 'none';
                select.offsetHeight; // Trigger reflow
                select.style.display = '';
                
                console.log(`✅ ${data.data.length} usuários carregados com sucesso!`);
            } else {
                console.error('❌ Select de usuário não encontrado!');
            }
        } else {
            console.error('❌ Erro na API Usuários:', data.error);
        }
    } catch (error) {
        console.error('❌ Erro ao carregar usuários:', error);
    }
}

// Função para validar formulário de forma inteligente
function validarFormularioInstrutor() {
    const usuarioSelect = document.getElementById('usuario_id');
    const nomeField = document.getElementById('nome');
    const emailField = document.getElementById('email');
    const cfcSelect = document.getElementById('cfc_id');
    const credencialField = document.getElementById('credencial');
    
    let erros = [];
    
    // Validações básicas sempre obrigatórias
    if (!nomeField.value.trim()) {
        erros.push('Nome é obrigatório');
    }
    
    if (!emailField.value.trim()) {
        erros.push('Email é obrigatório');
    }
    
    if (!usuarioSelect.value) {
        erros.push('Usuário é obrigatório');
    }
    
    if (!cfcSelect.value) {
        erros.push('CFC é obrigatório');
    }
    
    if (!credencialField.value.trim()) {
        erros.push('Credencial é obrigatória');
    }
    
    // Validar formato de email
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(emailField.value)) {
        erros.push('Email deve ter formato válido');
    }
    
    // Validar categorias de habilitação
    const categoriasSelecionadas = document.querySelectorAll('input[name="categorias[]"]:checked');
    if (categoriasSelecionadas.length === 0) {
        erros.push('Pelo menos uma categoria de habilitação deve ser selecionada');
    }
    
    if (erros.length > 0) {
        alert('Erros de validação:\n' + erros.join('\n'));
        return false;
    }
    
    return true;
}

// Função para preparar dados do formulário
function prepararDadosFormulario() {
    const formData = new FormData();
    
    // Dados básicos sempre enviados
    formData.append('nome', document.getElementById('nome').value);
    formData.append('email', document.getElementById('email').value);
    formData.append('telefone', document.getElementById('telefone').value);
    formData.append('cpf', document.getElementById('cpf').value);
    formData.append('cnh', document.getElementById('cnh').value);
    formData.append('data_nascimento', document.getElementById('data_nascimento').value);
    formData.append('usuario_id', document.getElementById('usuario_id').value);
    formData.append('cfc_id', document.getElementById('cfc_id').value);
    formData.append('credencial', document.getElementById('credencial').value);
    formData.append('ativo', document.getElementById('ativo').value);
    
    // Categorias de habilitação
    const categorias = [];
    document.querySelectorAll('input[name="categorias[]"]:checked').forEach(cb => {
        categorias.push(cb.value);
    });
    formData.append('categoria_habilitacao', categorias.join(','));
    console.log('📋 Categorias selecionadas:', categorias);
    
    // Dias da semana
    const diasSemana = [];
    document.querySelectorAll('input[name="dias_semana[]"]:checked').forEach(cb => {
        diasSemana.push(cb.value);
    });
    formData.append('dias_semana', diasSemana.join(','));
    console.log('📋 Dias da semana selecionados:', diasSemana);
    
    // Outros campos se existirem
    const outrosCampos = ['endereco', 'cidade', 'uf', 'tipo_carga', 'validade_credencial', 'observacoes', 'horario_inicio', 'horario_fim'];
    outrosCampos.forEach(campo => {
        const elemento = document.getElementById(campo);
        if (elemento) {
            formData.append(campo, elemento.value);
        }
    });
    
    // Adicionar ação e ID se for edição
    const acaoInstrutor = document.getElementById('acaoInstrutor');
    const instrutorId = document.getElementById('instrutor_id');
    
    if (acaoInstrutor) {
        formData.append('acao', acaoInstrutor.value);
    }
    
    if (instrutorId && instrutorId.value) {
        formData.append('instrutor_id', instrutorId.value);
    }
    
    console.log('📋 Dados preparados:', Object.fromEntries(formData));
    
    return formData;
}

// Função para verificar status dos selects (debug)
function verificarStatusSelects() {
    const cfcSelect = document.getElementById('cfc_id');
    const usuarioSelect = document.getElementById('usuario_id');
    
    console.log('🔍 Status dos Selects:');
    console.log('CFC Select:', cfcSelect ? 'Encontrado' : 'Não encontrado');
    console.log('CFC Options:', cfcSelect ? cfcSelect.options.length : 'N/A');
    console.log('Usuário Select:', usuarioSelect ? 'Encontrado' : 'Não encontrado');
    console.log('Usuário Options:', usuarioSelect ? usuarioSelect.options.length : 'N/A');
    
    // Verificar URLs das APIs
    console.log('🔧 URLs das APIs:');
    console.log('CFCs URL:', API_CONFIG.getRelativeApiUrl('CFCs'));
    console.log('USUARIOS URL:', API_CONFIG.getRelativeApiUrl('USUARIOS'));
}

// Função para testar APIs diretamente
async function testarAPIs() {
    console.log('🧪 Testando APIs...');
    
    try {
        // Testar API de CFCs
        const urlCFCs = API_CONFIG.getRelativeApiUrl('CFCs');
        console.log('📡 Testando CFCs:', urlCFCs);
        const responseCFCs = await fetch(urlCFCs);
        const dataCFCs = await responseCFCs.json();
        console.log('📊 Resposta CFCs:', dataCFCs);
        
        // Testar API de Usuários
        const urlUsuarios = API_CONFIG.getRelativeApiUrl('USUARIOS');
        console.log('📡 Testando Usuários:', urlUsuarios);
        const responseUsuarios = await fetch(urlUsuarios);
        const dataUsuarios = await responseUsuarios.json();
        console.log('📊 Resposta Usuários:', dataUsuarios);
        
    } catch (error) {
        console.error('❌ Erro ao testar APIs:', error);
    }
}

// Função para verificar e corrigir vinculação dos selects
function verificarVinculacaoSelects(instrutor) {
    console.log('🔍 Verificando vinculação dos selects...');
    
    // Verificar CFC
    const cfcField = document.getElementById('cfc_id');
    if (cfcField && instrutor.cfc_id) {
        const cfcId = parseInt(instrutor.cfc_id);
        if (cfcField.value !== cfcId.toString()) {
            console.warn('⚠️ CFC não vinculado corretamente, tentando novamente...');
            const cfcOption = cfcField.querySelector(`option[value="${cfcId}"]`);
            if (cfcOption) {
                // Remover temporariamente o evento onchange se existir
                const originalOnChange = cfcField.getAttribute('onchange');
                if (originalOnChange) {
                    cfcField.removeAttribute('onchange');
                }
                
                cfcField.value = cfcId;
                console.log('✅ CFC vinculado com sucesso:', cfcId);
                
                // Restaurar o evento onchange após um delay
                setTimeout(() => {
                    if (originalOnChange) {
                        cfcField.setAttribute('onchange', originalOnChange);
                    }
                }, 200);
            } else {
                console.error('❌ Opção de CFC não encontrada para ID:', cfcId);
                console.log('🔍 Opções disponíveis:', Array.from(cfcField.options).map(opt => ({value: opt.value, text: opt.textContent})));
            }
        } else {
            console.log('✅ CFC já vinculado corretamente');
        }
    }
    
    // Verificar Usuário
    const usuarioField = document.getElementById('usuario_id');
    if (usuarioField && instrutor.usuario_id) {
        const usuarioId = parseInt(instrutor.usuario_id);
        if (usuarioField.value !== usuarioId.toString()) {
            console.warn('⚠️ Usuário não vinculado corretamente, tentando novamente...');
            const usuarioOption = usuarioField.querySelector(`option[value="${usuarioId}"]`);
            if (usuarioOption) {
                // Remover temporariamente o evento onchange se existir
                const originalOnChange = usuarioField.getAttribute('onchange');
                if (originalOnChange) {
                    usuarioField.removeAttribute('onchange');
                }
                
                usuarioField.value = usuarioId;
                console.log('✅ Usuário vinculado com sucesso:', usuarioId);
                
                // Restaurar o evento onchange após um delay
                setTimeout(() => {
                    if (originalOnChange) {
                        usuarioField.setAttribute('onchange', originalOnChange);
                    }
                }, 200);
            } else {
                console.error('❌ Opção de usuário não encontrada para ID:', usuarioId);
                console.log('🔍 Opções disponíveis:', Array.from(usuarioField.options).map(opt => ({value: opt.value, text: opt.textContent})));
            }
        } else {
            console.log('✅ Usuário já vinculado corretamente');
        }
    }
}

function criarModalVisualizacao() {
    const modal = document.createElement('div');
    modal.id = 'modalVisualizacaoInstrutor';
    modal.className = 'custom-modal modal-visualizacao-responsive';
    
    modal.innerHTML = `
        <div class="custom-modal-dialog modal-dialog-responsive">
            <div class="modal-header modal-header-responsive">
                <h5 class="modal-title modal-title-responsive">
                    <i class="fas fa-eye me-2"></i>Visualizar Instrutor
                </h5>
                <button type="button" class="btn-close btn-close-responsive" onclick="fecharModalVisualizacao()">&times;</button>
            </div>
            <div class="modal-body modal-body-responsive">
                <div id="conteudoVisualizacao">
                    <!-- Conteúdo será preenchido dinamicamente -->
                </div>
            </div>
            <div class="modal-footer modal-footer-responsive">
                <button type="button" class="btn btn-secondary btn-responsive" onclick="fecharModalVisualizacao()">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
                <button type="button" class="btn btn-primary btn-responsive" id="btnEditarInstrutor">
                    <i class="fas fa-edit me-1"></i>Editar
                </button>
            </div>
        </div>
    `;
    
    // Adicionar listener para fechar modal ao clicar fora
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            fecharModalVisualizacao();
        }
    });
    
    // Adicionar listener para tecla ESC
    modal.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fecharModalVisualizacao();
        }
    });
    
    return modal;
}

function preencherModalVisualizacao(instrutor) {
    const modal = document.getElementById('modalVisualizacaoInstrutor');
    const conteudo = document.getElementById('conteudoVisualizacao');
    if (!modal || !conteudo) return;
    
    // Usar o nome correto (nome_usuario se nome estiver vazio)
    const nomeExibicao = instrutor.nome || instrutor.nome_usuario || 'N/A';
    const cfcExibicao = instrutor.cfc_nome || 'N/A';
    
    // Formatar categorias
    const categoriasFormatadas = formatarCategorias(instrutor.categorias_json) || 'N/A';
    
    // Formatar dias da semana
    const diasFormatados = formatarDiasSemana(instrutor.dias_semana) || 'N/A';
    
    // Formatar datas
    const dataNascimentoFormatada = instrutor.data_nascimento ? converterDataParaExibicao(instrutor.data_nascimento) : 'N/A';
    const validadeCredencialFormatada = instrutor.validade_credencial ? converterDataParaExibicao(instrutor.validade_credencial) : 'N/A';
    
    // Formatar horários
    const horarioInicioFormatado = instrutor.horario_inicio ? instrutor.horario_inicio.substring(0, 5) : 'N/A';
    const horarioFimFormatado = instrutor.horario_fim ? instrutor.horario_fim.substring(0, 5) : 'N/A';
    
    // Preparar HTML da foto
    let fotoHTML = '';
    if (instrutor.foto && instrutor.foto.trim() !== '') {
        let urlFoto;
        if (instrutor.foto.startsWith('http')) {
            urlFoto = instrutor.foto;
        } else {
            // Construir URL baseada no contexto atual
            const baseUrl = window.location.origin + window.location.pathname.split('/').slice(0, -2).join('/');
            urlFoto = `${baseUrl}/${instrutor.foto}`;
        }
        fotoHTML = `
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-camera me-2"></i>Foto do Instrutor
                    </h6>
                </div>
                <div class="col-12 text-center">
                    <img src="${urlFoto}" alt="Foto do instrutor" 
                         class="img-fluid rounded-circle border shadow-sm"
                         style="max-width: 200px; max-height: 200px; object-fit: cover; border: 3px solid #dee2e6 !important;"
                         onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
                    <div style="display: none; color: #6c757d; font-size: 0.9rem;">
                        <i class="fas fa-user-circle fa-3x"></i><br>
                        Foto não disponível
                    </div>
                </div>
            </div>
        `;
    }

    conteudo.innerHTML = `
        <div class="instrutor-visualizacao-content">
            ${fotoHTML}
            
            <!-- Informações Básicas -->
            <div class="instrutor-section">
                <h6 class="instrutor-section-title">
                    <i class="fas fa-user-tie me-2"></i>Informações Básicas
                </h6>
                <div class="instrutor-fields">
                    <div class="instrutor-field">
                        <strong>Nome Completo:</strong><br>
                        <span class="instrutor-value">${nomeExibicao}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Email:</strong><br>
                        <span class="instrutor-value">${instrutor.email || 'N/A'}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>CPF:</strong><br>
                        <span class="instrutor-value">${instrutor.cpf || 'N/A'}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>CNH:</strong><br>
                        <span class="instrutor-value">${instrutor.cnh || 'N/A'}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Data de Nascimento:</strong><br>
                        <span class="instrutor-value">${dataNascimentoFormatada}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Telefone:</strong><br>
                        <span class="instrutor-value">${instrutor.telefone || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <!-- Dados do Instrutor -->
            <div class="instrutor-section">
                <h6 class="instrutor-section-title">
                    <i class="fas fa-id-card me-2"></i>Dados do Instrutor
                </h6>
                <div class="instrutor-fields">
                    <div class="instrutor-field">
                        <strong>Credencial:</strong><br>
                        <span class="instrutor-value">${instrutor.credencial || 'N/A'}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>CFC:</strong><br>
                        <span class="instrutor-value">${cfcExibicao}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Categorias de Habilitação:</strong><br>
                        <span class="badge bg-info">${categoriasFormatadas}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Status:</strong><br>
                        <span class="badge ${instrutor.ativo ? 'bg-success' : 'bg-danger'}">
                            ${instrutor.ativo ? 'ATIVO' : 'INATIVO'}
                        </span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Validade da Credencial:</strong><br>
                        <span class="instrutor-value">${validadeCredencialFormatada}</span>
                    </div>
                </div>
            </div>
            
            <!-- Horários Disponíveis -->
            <div class="instrutor-section">
                <h6 class="instrutor-section-title">
                    <i class="fas fa-clock me-2"></i>Horários Disponíveis
                </h6>
                <div class="instrutor-fields">
                    <div class="instrutor-field">
                        <strong>Dias da Semana:</strong><br>
                        <span class="instrutor-value">${diasFormatados}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Horário:</strong><br>
                        <span class="instrutor-value">${horarioInicioFormatado} - ${horarioFimFormatado}</span>
                    </div>
                </div>
            </div>
            
            <!-- Endereço -->
            <div class="instrutor-section">
                <h6 class="instrutor-section-title">
                    <i class="fas fa-map-marker-alt me-2"></i>Endereço
                </h6>
                <div class="instrutor-fields">
                    <div class="instrutor-field">
                        <strong>Endereço:</strong><br>
                        <span class="instrutor-value">${instrutor.endereco || 'N/A'}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>Cidade:</strong><br>
                        <span class="instrutor-value">${instrutor.cidade || 'N/A'}</span>
                    </div>
                    <div class="instrutor-field">
                        <strong>UF:</strong><br>
                        <span class="instrutor-value">${instrutor.uf || 'N/A'}</span>
                    </div>
                </div>
            </div>
            
            <!-- Observações -->
            ${instrutor.observacoes ? `
            <div class="instrutor-section">
                <h6 class="instrutor-section-title">
                    <i class="fas fa-sticky-note me-2"></i>Observações
                </h6>
                <div class="instrutor-fields">
                    <div class="instrutor-field">
                        <span class="instrutor-value">${instrutor.observacoes}</span>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;
    
    // FORÇAR CSS INLINE PARA GARANTIR LAYOUT EM COLUNA ÚNICA
    const modalConteudo = modal.querySelector('.modal-body-responsive');
    if (modalConteudo) {
        // Aplicar CSS inline para forçar layout em coluna única
        modalConteudo.style.cssText = `
            display: block !important;
            width: 100% !important;
            padding: 1rem !important;
        `;
        
        // Forçar todos os elementos filhos para coluna única
        const elementos = modalConteudo.querySelectorAll('*');
        elementos.forEach(el => {
            if (el.classList.contains('col-md-6') || el.classList.contains('col-12') || el.classList.contains('row')) {
                el.style.cssText = `
                    display: block !important;
                    width: 100% !important;
                    max-width: 100% !important;
                    flex: none !important;
                    float: none !important;
                    clear: both !important;
                    margin-bottom: 0.5rem !important;
                `;
            }
        });
        
        console.log('🔧 CSS inline aplicado para forçar layout em coluna única');
    }
    
    // Configurar botão de editar
    const btnEditar = document.getElementById('btnEditarInstrutor');
    if (btnEditar) {
        btnEditar.onclick = function() {
            // Fechar modal de visualização primeiro
            fecharModalVisualizacao();
            
            // Aguardar um pouco para garantir que o modal foi fechado antes de abrir o de edição
            setTimeout(() => {
                editarInstrutor(instrutor.id);
            }, 350); // Tempo ligeiramente maior que a animação de fechamento (300ms)
        };
    }
}

function fecharModalVisualizacao() {
    console.log('🚪 Fechando modal de visualização...');
    const modal = document.getElementById('modalVisualizacaoInstrutor');
    if (modal) {
        const modalDialog = modal.querySelector('.custom-modal-dialog');
        if (modalDialog) {
            modalDialog.style.opacity = '0';
            modalDialog.style.transform = 'translateY(-20px)';
        }
        
        // Remover classe show para garantir que não interfira com outros modais
        modal.classList.remove('show');
        
        setTimeout(() => {
            // FORÇAR fechamento do modal
            modal.style.setProperty('display', 'none', 'important');
            modal.style.setProperty('visibility', 'hidden', 'important');
            modal.style.setProperty('opacity', '0', 'important');
            
            // Limpar o conteúdo para evitar conflitos
            const conteudoVisualizacao = document.getElementById('conteudoVisualizacao');
            if (conteudoVisualizacao) {
                conteudoVisualizacao.innerHTML = '';
            }
            
            console.log('✅ Modal de visualização fechado com sucesso');
        }, 300);
    }
}

function formatarDiasSemana(diasSemana) {
    if (!diasSemana) return '';
    
    try {
        let dias = [];
        
        // Se já é um array
        if (Array.isArray(diasSemana)) {
            dias = diasSemana;
        }
        // Se é uma string JSON
        else if (typeof diasSemana === 'string') {
            if (diasSemana.trim() === '') return '';
            try {
                dias = JSON.parse(diasSemana);
            } catch (e) {
                // Se não for JSON, tentar split por vírgula
                dias = diasSemana.split(',').map(dia => dia.trim()).filter(dia => dia !== '');
            }
        }
        
        // Mapear nomes dos dias
        const nomesDias = {
            'segunda': 'Segunda-feira',
            'terca': 'Terça-feira',
            'quarta': 'Quarta-feira',
            'quinta': 'Quinta-feira',
            'sexta': 'Sexta-feira',
            'sabado': 'Sábado',
            'domingo': 'Domingo'
        };
        
        return dias.map(dia => nomesDias[dia] || dia).join(', ');
        
    } catch (error) {
        console.warn('⚠️ Erro ao formatar dias da semana:', error);
        return diasSemana.toString();
    }
}

// Função para converter data de YYYY-MM-DD para DD/MM/YYYY
function converterDataParaExibicao(dataString) {
    if (!dataString || dataString === '0000-00-00' || dataString.trim() === '') {
        return '';
    }
    
    try {
        // Verificar se está no formato YYYY-MM-DD
        const match = dataString.match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (match) {
            const [, ano, mes, dia] = match;
            const dataFormatada = `${dia}/${mes}/${ano}`;
            return dataFormatada;
        }
        
        // Fallback para outras conversões usando Date
        const data = new Date(dataString);
        if (!isNaN(data.getTime())) {
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            const dataFormatada = `${dia}/${mes}/${ano}`;
            return dataFormatada;
        } else {
            console.warn(`⚠️ Data inválida para conversão: ${dataString}`);
            return '';
        }
    } catch (e) {
        console.warn(`⚠️ Erro ao converter data: ${dataString}`, e);
        return '';
    }
}

console.log('📋 Arquivo instrutores-page.js carregado com sucesso!');

// Função de inicialização automática
async function inicializarDadosInstrutores() {
    console.log('🚀 Inicializando dados de instrutores...');
    
    try {
        // Carregar CFCs no filtro
        await carregarCFCsComRetry();
        
        // Carregar usuários no filtro (se existir)
        const filtroCFC = document.getElementById('filtroCFC');
        if (filtroCFC) {
            console.log('✅ Filtro CFC encontrado, populando...');
            const cfcSelect = document.getElementById('cfc_id');
            if (cfcSelect && cfcSelect.options.length > 1) {
                filtroCFC.innerHTML = '<option value="">Todos</option>';
                for (let i = 1; i < cfcSelect.options.length; i++) {
                    const option = cfcSelect.options[i].cloneNode(true);
                    filtroCFC.appendChild(option);
                }
            }
        }
        
        console.log('✅ Inicialização concluída!');
    } catch (error) {
        console.error('❌ Erro na inicialização:', error);
    }
}

// Executar inicialização quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', inicializarDadosInstrutores);
} else {
    inicializarDadosInstrutores();
}

