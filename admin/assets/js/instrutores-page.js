// Funções JavaScript da página de instrutores - VERSÃO CORRIGIDA
// Este arquivo é carregado APÓS o config.js, garantindo que API_CONFIG esteja disponível

// Funções JavaScript com URLs CORRIGIDAS
function abrirModalInstrutor() {
    document.getElementById('modalTitle').textContent = 'Novo Instrutor';
    document.getElementById('acaoInstrutor').value = 'novo';
    document.getElementById('instrutor_id').value = '';
    
    // Limpar campos manualmente para evitar problemas com campos de data
    limparCamposFormulario();
    
    const modal = document.getElementById('modalInstrutor');
    modal.style.display = 'block';
    modal.classList.add('show');
    
    // Garantir que o modal seja visível
    setTimeout(() => {
        modal.scrollTop = 0;
        const modalDialog = modal.querySelector('.custom-modal-dialog');
        if (modalDialog) {
            modalDialog.style.opacity = '1';
            modalDialog.style.transform = 'translateY(0)';
        }
    }, 100);
}

function fecharModalInstrutor() {
    const modal = document.getElementById('modalInstrutor');
    modal.classList.remove('show');
    
    // Animar o fechamento
    const modalDialog = modal.querySelector('.custom-modal-dialog');
    if (modalDialog) {
        modalDialog.style.opacity = '0';
        modalDialog.style.transform = 'translateY(-20px)';
    }
    
    setTimeout(() => {
        modal.style.display = 'none';
    }, 300);
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
    
    // Garantir que os campos de data estejam funcionando corretamente
    setTimeout(() => {
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

function editarInstrutor(id) {
    // Buscar dados do instrutor
    fetch(`${API_CONFIG.getRelativeApiUrl('INSTRUTORES')}?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherFormularioInstrutor(data.data);
                document.getElementById('modalTitle').textContent = 'Editar Instrutor';
                document.getElementById('acaoInstrutor').value = 'editar';
                document.getElementById('instrutor_id').value = id;
                
                abrirModalInstrutor();
            } else {
                mostrarAlerta('Erro ao carregar dados do instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do instrutor', 'danger');
        });
}

function preencherFormularioInstrutor(instrutor) {
    // Preencher campos do formulário
    document.getElementById('nome').value = instrutor.nome || instrutor.nome_usuario || '';
    document.getElementById('cpf').value = instrutor.cpf || '';
    document.getElementById('cnh').value = instrutor.cnh || '';
    
    // Preencher campo de data de nascimento de forma segura
    const campoDataNascimento = document.getElementById('data_nascimento');
    if (campoDataNascimento) {
        if (instrutor.data_nascimento && isValidDate(instrutor.data_nascimento)) {
            // Converter formato ISO para brasileiro
            const data = new Date(instrutor.data_nascimento);
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            campoDataNascimento.value = `${dia}/${mes}/${ano}`;
        } else {
            campoDataNascimento.value = '';
        }
        campoDataNascimento.type = 'text';
    }
    
    document.getElementById('email').value = instrutor.email || '';
    document.getElementById('usuario_id').value = instrutor.usuario_id || '';
    document.getElementById('cfc_id').value = instrutor.cfc_id || '';
    document.getElementById('credencial').value = instrutor.credencial || '';
    document.getElementById('telefone').value = instrutor.telefone || '';
    document.getElementById('endereco').value = instrutor.endereco || '';
    document.getElementById('cidade').value = instrutor.cidade || '';
    document.getElementById('uf').value = instrutor.uf || '';
    document.getElementById('ativo').value = instrutor.ativo ? '1' : '0';
    document.getElementById('tipo_carga').value = instrutor.tipo_carga || '';
    
    // Preencher campo de validade da credencial de forma segura
    const campoValidadeCredencial = document.getElementById('validade_credencial');
    if (campoValidadeCredencial) {
        if (instrutor.validade_credencial && isValidDate(instrutor.validade_credencial)) {
            // Converter formato ISO para brasileiro
            const data = new Date(instrutor.validade_credencial);
            const dia = String(data.getDate()).padStart(2, '0');
            const mes = String(data.getMonth() + 1).padStart(2, '0');
            const ano = data.getFullYear();
            campoValidadeCredencial.value = `${dia}/${mes}/${ano}`;
        } else {
            campoValidadeCredencial.value = '';
        }
        campoValidadeCredencial.type = 'text';
    }
    
    document.getElementById('observacoes').value = instrutor.observacoes || '';
    
    // Limpar checkboxes primeiro
    document.querySelectorAll('input[name="categorias[]"]').forEach(cb => cb.checked = false);
    document.querySelectorAll('input[name="dias_semana[]"]').forEach(cb => cb.checked = false);
    
    // Marcar categorias selecionadas
    if (instrutor.categoria_habilitacao) {
        const categorias = instrutor.categoria_habilitacao.split(',');
        categorias.forEach(cat => {
            const checkbox = document.querySelector(`input[name="categorias[]"][value="${cat.trim()}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Marcar dias da semana selecionados
    if (instrutor.dias_semana) {
        const dias = instrutor.dias_semana.split(',');
        dias.forEach(dia => {
            const checkbox = document.querySelector(`input[name="dias_semana[]"][value="${dia.trim()}"]`);
            if (checkbox) checkbox.checked = true;
        });
    }
    
    // Preencher horários
    if (instrutor.horario_inicio) {
        document.getElementById('horario_inicio').value = instrutor.horario_inicio;
    }
    if (instrutor.horario_fim) {
        document.getElementById('horario_fim').value = instrutor.horario_fim;
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
    const form = document.getElementById('formInstrutor');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('nome').trim() || !formData.get('cpf').trim() || !formData.get('cnh').trim() || 
        !formData.get('data_nascimento') || !formData.get('email').trim() || !formData.get('usuario_id') || 
        !formData.get('cfc_id') || !formData.get('credencial').trim()) {
        mostrarAlerta('Preencha todos os campos obrigatórios', 'warning');
        return;
    }
    
    // Preparar dados para envio
    const categoriasSelecionadas = formData.getAll('categorias[]');
    if (categoriasSelecionadas.length === 0) {
        mostrarAlerta('Selecione pelo menos uma categoria de habilitação', 'warning');
        return;
    }
    
    // Converter datas do formato brasileiro para ISO
    const dataNascimento = converterDataBrasileiraParaISO(formData.get('data_nascimento'));
    const validadeCredencial = converterDataBrasileiraParaISO(formData.get('validade_credencial'));
    
    if (!dataNascimento) {
        mostrarAlerta('Data de nascimento inválida. Use o formato dd/mm/aaaa', 'warning');
        return;
    }
    
    const instrutorData = {
        nome: formData.get('nome').trim(),
        cpf: formData.get('cpf').trim(),
        cnh: formData.get('cnh').trim(),
        data_nascimento: dataNascimento,
        email: formData.get('email').trim(),
        usuario_id: formData.get('usuario_id'),
        cfc_id: formData.get('cfc_id'),
        credencial: formData.get('credencial').trim(),
        categoria_habilitacao: categoriasSelecionadas.join(','),
        telefone: formData.get('telefone') || '',
        endereco: formData.get('endereco') || '',
        cidade: formData.get('cidade') || '',
        uf: formData.get('uf') || '',
        ativo: formData.get('ativo') === '1',
        tipo_carga: formData.get('tipo_carga') || '',
        validade_credencial: validadeCredencial || '',
        observacoes: formData.get('observacoes') || '',
        dias_semana: formData.getAll('dias_semana[]').join(','),
        horario_inicio: formData.get('horario_inicio') || '',
        horario_fim: formData.get('horario_fim') || ''
    };
    
    const acao = formData.get('acao');
    const instrutor_id = formData.get('instrutor_id');
    
    if (acao === 'editar' && instrutor_id) {
        instrutorData.id = instrutor_id;
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
            form.reset();
            
            // Recarregar página para mostrar dados atualizados
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            mostrarAlerta(data.error || 'Erro ao salvar instrutor', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao salvar instrutor. Tente novamente.', 'danger');
    })
    .finally(() => {
        // Restaurar botão
        btnSalvar.innerHTML = originalText;
        btnSalvar.disabled = false;
    });
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
    // Carregar dados iniciais
    carregarInstrutores();
    carregarCFCs();
    carregarUsuarios();
    
    // Configurar campos de data para funcionarem corretamente
    configurarCamposData();
    
    // Adicionar listener para fechar modal ao clicar fora
    const modal = document.getElementById('modalInstrutor');
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
});

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
    
    // Verificar se é uma data válida
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return false;
    
    // Verificar se a data não é muito antiga (antes de 1900)
    if (date.getFullYear() < 1900) return false;
    
    // Verificar se a data não é no futuro (para data de nascimento)
    // Esta validação será feita na função configurarCamposData
    
    return true;
}

// Função para configurar campo de data híbrido (texto + calendário)
function configurarCampoDataHibrido(campoId, campo) {
    // Garantir que seja do tipo texto
    campo.type = 'text';
    
    // Criar wrapper para o campo
    const wrapper = document.createElement('div');
    wrapper.style.position = 'relative';
    wrapper.style.display = 'flex';
    wrapper.style.alignItems = 'center';
    
    // Mover o campo para dentro do wrapper
    campo.parentNode.insertBefore(wrapper, campo);
    wrapper.appendChild(campo);
    
    // Criar botão do calendário
    const btnCalendario = document.createElement('button');
    btnCalendario.type = 'button';
    btnCalendario.innerHTML = '📅';
    btnCalendario.style.cssText = `
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        background: none;
        border: none;
        font-size: 16px;
        cursor: pointer;
        padding: 4px;
        border-radius: 4px;
        color: #6c757d;
        z-index: 10;
    `;
    btnCalendario.title = 'Abrir calendário';
    
    // Adicionar botão ao wrapper
    wrapper.appendChild(btnCalendario);
    
    // Aplicar máscara de data brasileira
    campo.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        
        // Aplicar máscara dd/mm/aaaa
        if (value.length <= 2) {
            value = value;
        } else if (value.length <= 4) {
            value = value.substring(0, 2) + '/' + value.substring(2);
        } else if (value.length <= 8) {
            value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4);
        } else {
            value = value.substring(0, 2) + '/' + value.substring(2, 4) + '/' + value.substring(4, 8);
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
                if (data && new Date(data) > new Date()) {
                    console.warn('Data de nascimento não pode ser no futuro');
                    this.value = '';
                    return;
                }
            }
            
            if (campoId === 'validade_credencial') {
                const data = converterDataBrasileiraParaISO(valorTexto);
                if (data && new Date(data) < new Date()) {
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
    
    // Funcionalidade do calendário
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
                // Converter de volta para formato brasileiro
                const data = new Date(this.value);
                const dia = String(data.getDate()).padStart(2, '0');
                const mes = String(data.getMonth() + 1).padStart(2, '0');
                const ano = data.getFullYear();
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
        this.style.backgroundColor = '#f8f9fa';
        this.style.color = '#495057';
    });
    
    btnCalendario.addEventListener('mouseleave', function() {
        this.style.backgroundColor = 'transparent';
        this.style.color = '#6c757d';
    });
}

// Função para converter data brasileira (dd/mm/aaaa) para ISO (aaaa-mm-dd)
function converterDataBrasileiraParaISO(dataBrasileira) {
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
    
    // Retornar no formato ISO
    return data.toISOString().split('T')[0];
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
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherTabelaInstrutores(data.data);
                atualizarEstatisticas(data.data);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar instrutores:', error);
        });
}

function preencherTabelaInstrutores(instrutores) {
    const tbody = document.querySelector('#tabelaInstrutores tbody');
    tbody.innerHTML = '';
    
    instrutores.forEach(instrutor => {
        const row = document.createElement('tr');
        
        // Usar o nome correto (nome_usuario se nome estiver vazio)
        const nomeExibicao = instrutor.nome || instrutor.nome_usuario || 'N/A';
        const cfcExibicao = instrutor.cfc_nome || 'N/A';
        
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
                <span class="badge bg-info">${instrutor.categoria_habilitacao || 'N/A'}</span>
            </td>
            <td>
                <span class="badge ${instrutor.ativo ? 'bg-success' : 'bg-danger'}">
                    ${instrutor.ativo ? 'ATIVO' : 'INATIVO'}
                </span>
            </td>
            <td>
                <div class="btn-group-vertical btn-group-sm">
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
    });
}

function atualizarEstatisticas(instrutores) {
    const total = instrutores.length;
    const ativos = instrutores.filter(i => i.ativo).length;
    
    document.getElementById('totalInstrutores').textContent = total;
    document.getElementById('instrutoresAtivos').textContent = ativos;
}

function carregarCFCs() {
    console.log('🔍 Iniciando carregamento de CFCs...');
    
    // DEBUG: Verificar configuração
    console.log('🔧 API_CONFIG:', API_CONFIG);
    console.log('🔧 typeof API_CONFIG:', typeof API_CONFIG);
    
    const urlCFC = API_CONFIG.getRelativeApiUrl('CFCs');
    console.log('🌐 URL construída para CFCs:', urlCFC);
    
    // Carregar CFCs para o select
    fetch(urlCFC)
        .then(response => {
            console.log('📡 Resposta da API CFCs:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados recebidos da API CFCs:', data);
            
            if (data.success) {
                const selectCFC = document.getElementById('cfc_id');
                const filtroCFC = document.getElementById('filtroCFC');
                
                console.log('🎯 Select CFC encontrado:', selectCFC);
                console.log('🎯 Filtro CFC encontrado:', filtroCFC);
                
                if (selectCFC) {
                    selectCFC.innerHTML = '<option value="">Selecione um CFC</option>';
                    
                    data.data.forEach(cfc => {
                        const option = document.createElement('option');
                        option.value = cfc.id;
                        option.textContent = cfc.nome;
                        selectCFC.appendChild(option);
                        console.log('✅ CFC adicionado:', cfc.nome);
                    });
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
                
                console.log('✅ CFCs carregados com sucesso!');
            } else {
                console.error('❌ Erro na API CFCs:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar CFCs:', error);
        });
}

function carregarUsuarios() {
    console.log('🔍 Iniciando carregamento de usuários...');
    
    // DEBUG: Verificar configuração
    console.log('🔧 API_CONFIG:', API_CONFIG);
    console.log('🔧 typeof API_CONFIG:', typeof API_CONFIG);
    
    const urlUsuarios = API_CONFIG.getRelativeApiUrl('USUARIOS');
    console.log('🌐 URL construída para Usuários:', urlUsuarios);
    
    // Carregar usuários para o select
    fetch(urlUsuarios)
        .then(response => {
            console.log('📡 Resposta da API Usuários:', response.status, response.statusText);
            return response.json();
        })
        .then(data => {
            console.log('📊 Dados recebidos da API Usuários:', data);
            
            if (data.success) {
                const selectUsuario = document.getElementById('usuario_id');
                console.log('🎯 Select Usuário encontrado:', selectUsuario);
                
                if (selectUsuario) {
                    selectUsuario.innerHTML = '<option value="">Selecione um usuário</option>';
                    
                    data.data.forEach(usuario => {
                        const option = document.createElement('option');
                        option.value = usuario.id;
                        option.textContent = `${usuario.nome} (${usuario.email})`;
                        selectUsuario.appendChild(option);
                        console.log('✅ Usuário adicionado:', usuario.nome);
                    });
                    
                    console.log('✅ Usuários carregados com sucesso!');
                } else {
                    console.error('❌ Select de usuário não encontrado!');
                }
            } else {
                console.error('❌ Erro na API Usuários:', data.error);
            }
        })
        .catch(error => {
            console.error('❌ Erro ao carregar usuários:', error);
        });
}

console.log('📋 Arquivo instrutores-page.js carregado com sucesso!');
