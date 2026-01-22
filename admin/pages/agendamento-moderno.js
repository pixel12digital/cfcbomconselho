// JavaScript para Interface de Agendamento Moderna
// CFC Bom Conselho

// Variáveis globais
let modalAberto = false;
let modalCancelamentoAberto = false;
let aulasCarregadas = [];
let dataAtual = new Date();

// Inicialização
document.addEventListener('DOMContentLoaded', function() {
    // Configurar Flatpickr para período
    flatpickr("#filtroPeriodo", {
        mode: "range",
        dateFormat: "d/m/Y",
        locale: "pt",
        onChange: function(selectedDates, dateStr, instance) {
            if (selectedDates.length === 2) {
                carregarAulas();
            }
        }
    });

    // Event listeners para filtros
    document.getElementById('filtroInstrutor').addEventListener('change', carregarAulas);
    document.getElementById('filtroVeiculo').addEventListener('change', carregarAulas);
    document.getElementById('filtroStatus').addEventListener('change', carregarAulas);
    document.getElementById('filtroTipo').addEventListener('change', carregarAulas);
    document.getElementById('visualizacao').addEventListener('change', mudarVisualizacao);

    // Event listeners para tipo de aula
    document.getElementById('tipoAula').addEventListener('change', function() {
        const tipo = this.value;
        const instrutorGroup = document.getElementById('instrutorGroup');
        const veiculoGroup = document.getElementById('veiculoGroup');
        
        if (tipo === 'exame') {
            instrutorGroup.style.display = 'none';
            veiculoGroup.style.display = 'none';
        } else {
            instrutorGroup.style.display = 'block';
            if (tipo === 'pratica') {
                veiculoGroup.style.display = 'block';
            } else {
                veiculoGroup.style.display = 'none';
            }
        }
    });

    // Carregar aulas iniciais
    carregarAulas();

    // Botões de marcar notificação como lida
    document.querySelectorAll('.marcar-lida').forEach(btn => {
        btn.addEventListener('click', function() {
            const notificacaoId = this.dataset.id;
            marcarNotificacaoComoLida(notificacaoId);
        });
    });
});

// Funções de navegação
function anterior() {
    dataAtual.setMonth(dataAtual.getMonth() - 1);
    atualizarPeriodoAtual();
    carregarAulas();
}

function proximo() {
    dataAtual.setMonth(dataAtual.getMonth() + 1);
    atualizarPeriodoAtual();
    carregarAulas();
}

function atualizarPeriodoAtual() {
    const meses = [
        'Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho',
        'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'
    ];
    document.getElementById('periodoAtual').textContent = 
        `${meses[dataAtual.getMonth()]} ${dataAtual.getFullYear()}`;
}

// Funções de carregamento
async function carregarAulas() {
    const conteudo = document.getElementById('conteudoAulas');
    conteudo.innerHTML = '<div class="loading"><div class="spinner"></div>Carregando aulas...</div>';

    try {
        const params = new URLSearchParams();
        
        // Adicionar filtros
        const filtroInstrutor = document.getElementById('filtroInstrutor').value;
        const filtroVeiculo = document.getElementById('filtroVeiculo').value;
        const filtroStatus = document.getElementById('filtroStatus').value;
        const filtroTipo = document.getElementById('filtroTipo').value;
        const filtroPeriodo = document.getElementById('filtroPeriodo').value;

        if (filtroInstrutor) params.append('instrutor_id', filtroInstrutor);
        if (filtroVeiculo) params.append('veiculo_id', filtroVeiculo);
        if (filtroStatus) params.append('status', filtroStatus);
        if (filtroTipo) params.append('tipo_aula', filtroTipo);
        if (filtroPeriodo) params.append('periodo', filtroPeriodo);

        const response = await fetch(`../admin/api/agendamento.php?${params.toString()}`);
        const result = await response.json();

        if (result.success) {
            aulasCarregadas = result.data;
            renderizarAulas();
        } else {
            conteudo.innerHTML = '<div class="empty-state"><h3>Erro ao carregar aulas</h3><p>' + result.message + '</p></div>';
        }
    } catch (error) {
        console.error('Erro:', error);
        conteudo.innerHTML = '<div class="empty-state"><h3>Erro de conexão</h3><p>Tente novamente mais tarde.</p></div>';
    }
}

function renderizarAulas() {
    const visualizacao = document.getElementById('visualizacao').value;
    const conteudo = document.getElementById('conteudoAulas');

    if (aulasCarregadas.length === 0) {
        conteudo.innerHTML = '<div class="empty-state"><h3>Nenhuma aula encontrada</h3><p>Não há aulas para os filtros selecionados.</p></div>';
        return;
    }

    switch (visualizacao) {
        case 'calendario':
            renderizarCalendario();
            break;
        case 'lista':
            renderizarLista();
            break;
        case 'dia':
            renderizarDia();
            break;
        case 'semana':
            renderizarSemana();
            break;
    }
}

function renderizarLista() {
    const conteudo = document.getElementById('conteudoAulas');
    let html = '<div class="aula-list">';

    aulasCarregadas.forEach(aula => {
        html += `
            <div class="aula-item" data-aula-id="${aula.id}">
                <div class="aula-item-header">
                    <div>
                        <div class="aula-tipo ${aula.tipo_aula}">${aula.tipo_aula.charAt(0).toUpperCase() + aula.tipo_aula.slice(1)}</div>
                        <div class="aula-data">${formatarData(aula.data_aula)}</div>
                        <div class="aula-hora">${formatarHora(aula.hora_inicio)} - ${formatarHora(aula.hora_fim)}</div>
                    </div>
                    <div class="aula-status ${aula.status}">${aula.status.charAt(0).toUpperCase() + aula.status.slice(1)}</div>
                </div>
                <div class="aula-detalhes">
                    <div class="aula-detalhe">
                        <i class="fas fa-user aula-detalhe-icon"></i>
                        ${aula.aluno_nome}
                    </div>
                    <div class="aula-detalhe">
                        <i class="fas fa-chalkboard-teacher aula-detalhe-icon"></i>
                        ${aula.instrutor_nome}
                    </div>
                    ${aula.veiculo_modelo ? `
                        <div class="aula-detalhe">
                            <i class="fas fa-car aula-detalhe-icon"></i>
                            ${aula.veiculo_modelo} - ${aula.veiculo_placa}
                        </div>
                    ` : ''}
                </div>
                <div class="aula-actions">
                    <button class="btn btn-sm btn-primary" onclick="editarAula(${aula.id})">
                        <i class="fas fa-edit"></i> Editar
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="abrirModalCancelamento(${aula.id})">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </div>
        `;
    });

    html += '</div>';
    conteudo.innerHTML = html;
}

function renderizarCalendario() {
    const conteudo = document.getElementById('conteudoAulas');
    conteudo.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Visualização de calendário em desenvolvimento.</div>';
}

function renderizarDia() {
    const conteudo = document.getElementById('conteudoAulas');
    conteudo.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Visualização por dia em desenvolvimento.</div>';
}

function renderizarSemana() {
    const conteudo = document.getElementById('conteudoAulas');
    conteudo.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Visualização por semana em desenvolvimento.</div>';
}

function mudarVisualizacao() {
    const visualizacao = document.getElementById('visualizacao').value;
    const titulo = document.getElementById('tituloVisualizacao');
    
    switch (visualizacao) {
        case 'calendario':
            titulo.textContent = 'Calendário de Aulas';
            break;
        case 'lista':
            titulo.textContent = 'Lista de Aulas';
            break;
        case 'dia':
            titulo.textContent = 'Aulas do Dia';
            break;
        case 'semana':
            titulo.textContent = 'Aulas da Semana';
            break;
    }
    
    renderizarAulas();
}

// Funções do modal
function abrirModalCriarAula() {
    document.getElementById('modalTitulo').textContent = 'Nova Aula';
    document.getElementById('formAula').reset();
    document.getElementById('aulaId').value = '';
    document.getElementById('modalAula').classList.remove('hidden');
    modalAberto = true;
}

function editarAula(aulaId) {
    const aula = aulasCarregadas.find(a => a.id === aulaId);
    if (!aula) return;

    document.getElementById('modalTitulo').textContent = 'Editar Aula';
    document.getElementById('aulaId').value = aula.id;
    document.getElementById('tipoAula').value = aula.tipo_aula;
    document.getElementById('aluno').value = aula.aluno_id;
    document.getElementById('instrutor').value = aula.instrutor_id;
    document.getElementById('veiculo').value = aula.veiculo_id || '';
    document.getElementById('dataAula').value = aula.data_aula;
    document.getElementById('horaInicio').value = aula.hora_inicio;
    document.getElementById('disciplina').value = aula.disciplina || '';
    document.getElementById('observacoes').value = aula.observacoes || '';

    document.getElementById('modalAula').classList.remove('hidden');
    modalAberto = true;
}

function fecharModal() {
    document.getElementById('modalAula').classList.add('hidden');
    modalAberto = false;
}

function abrirModalCancelamento(aulaId) {
    document.getElementById('cancelarAulaId').value = aulaId;
    document.getElementById('modalCancelamento').classList.remove('hidden');
    modalCancelamentoAberto = true;
}

function fecharModalCancelamento() {
    document.getElementById('modalCancelamento').classList.add('hidden');
    modalCancelamentoAberto = false;
}

// Funções de validação e salvamento
async function validarEAguardar() {
    const form = document.getElementById('formAula');
    const formData = new FormData(form);
    
    // Validação básica
    if (!formData.get('tipo_aula') || !formData.get('aluno_id') || !formData.get('data_aula') || !formData.get('hora_inicio')) {
        mostrarToast('Por favor, preencha todos os campos obrigatórios.', 'error');
        return;
    }

    // Mostrar loading
    const btnSalvar = document.querySelector('#modalAula .btn-primary');
    const textoOriginal = btnSalvar.textContent;
    btnSalvar.innerHTML = '<div class="spinner"></div> Salvando...';
    btnSalvar.disabled = true;

    try {
        const dados = Object.fromEntries(formData);
        const response = await fetch('../admin/api/agendamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(dados)
        });

        const result = await response.json();

        if (result.success) {
            mostrarToast('Aula salva com sucesso!', 'success');
            fecharModal();
            carregarAulas();
        } else {
            mostrarToast(result.message || 'Erro ao salvar aula.', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro de conexão. Tente novamente.', 'error');
    } finally {
        btnSalvar.textContent = textoOriginal;
        btnSalvar.disabled = false;
    }
}

async function confirmarCancelamento() {
    const form = document.getElementById('formCancelamento');
    const formData = new FormData(form);
    
    if (!formData.get('motivo')) {
        mostrarToast('Por favor, selecione um motivo para o cancelamento.', 'error');
        return;
    }

    try {
        const response = await fetch('../admin/api/agendamento.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                aula_id: formData.get('aula_id'),
                motivo: formData.get('motivo'),
                observacoes: formData.get('observacoes')
            })
        });

        const result = await response.json();

        if (result.success) {
            mostrarToast('Aula cancelada com sucesso!', 'success');
            fecharModalCancelamento();
            carregarAulas();
        } else {
            mostrarToast(result.message || 'Erro ao cancelar aula.', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        mostrarToast('Erro de conexão. Tente novamente.', 'error');
    }
}

// Funções auxiliares
function formatarData(data) {
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarHora(hora) {
    return hora.substring(0, 5);
}

function mostrarToast(mensagem, tipo = 'info') {
    const toastContainer = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast toast-${tipo}`;
    
    toast.innerHTML = `
        <div class="toast-header">
            <i class="fas fa-${tipo === 'success' ? 'check-circle' : tipo === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
            <span class="toast-title">${tipo === 'success' ? 'Sucesso' : tipo === 'error' ? 'Erro' : 'Informação'}</span>
        </div>
        <div class="toast-message">${mensagem}</div>
    `;
    
    toastContainer.appendChild(toast);
    
    setTimeout(() => {
        toast.remove();
    }, 5000);
}

async function marcarNotificacaoComoLida(notificacaoId) {
    try {
        const response = await fetch('../admin/api/notificacoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notificacao_id: notificacaoId
            })
        });

        const result = await response.json();

        if (result.success) {
            const notificacaoItem = document.querySelector(`[data-id="${notificacaoId}"]`);
            if (notificacaoItem) {
                notificacaoItem.remove();
            }
            
            const badge = document.querySelector('.badge');
            if (badge) {
                const count = parseInt(badge.textContent) - 1;
                if (count > 0) {
                    badge.textContent = count;
                } else {
                    badge.parentElement.parentElement.remove();
                }
            }
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}

// Funções de ação
function exportarAgenda() {
    mostrarToast('Funcionalidade de exportação em desenvolvimento.', 'info');
}

function verConflitos() {
    mostrarToast('Funcionalidade de conflitos em desenvolvimento.', 'info');
}

function verSolicitacoes() {
    window.location.href = 'solicitacoes.php';
}

// Prevenir envio dos formulários
document.addEventListener('DOMContentLoaded', function() {
    const formAula = document.getElementById('formAula');
    const formCancelamento = document.getElementById('formCancelamento');
    
    if (formAula) {
        formAula.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }
    
    if (formCancelamento) {
        formCancelamento.addEventListener('submit', function(e) {
            e.preventDefault();
        });
    }
});
