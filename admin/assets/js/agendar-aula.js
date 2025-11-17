/**
 * Agendar Aula - Sistema de Slots Visuais
 * Gerencia a interface de agendamento por aluno usando slots disponíveis
 */

(function() {
    'use strict';
    
    // Estado da aplicação
    const state = {
        alunoId: null,
        alunoNome: null,
        tipoAgendamento: 'unica',
        posicaoIntervalo: 'depois',
        slots: [],
        slotsPorData: {},
        slotSelecionado: null,
        diasExpandidos: new Set()
    };
    
    // Elementos DOM
    const elements = {
        loadingSlots: null,
        mensagemSemSlots: null,
        containerDias: null,
        resumoAgendamento: null,
        btnConfirmar: null,
        btnCancelar: null,
        mensagensContainer: null
    };
    
    /**
     * Inicialização
     */
    function init(config) {
        state.alunoId = config.alunoId;
        state.alunoNome = config.alunoNome;
        
        // Obter elementos DOM
        elements.loadingSlots = document.getElementById('loading-slots');
        elements.mensagemSemSlots = document.getElementById('mensagem-sem-slots');
        elements.containerDias = document.getElementById('container-dias');
        elements.resumoAgendamento = document.getElementById('resumo-agendamento');
        elements.btnConfirmar = document.getElementById('btn-confirmar-agendamento');
        elements.btnCancelar = document.getElementById('btn-cancelar-agendamento');
        elements.mensagensContainer = document.getElementById('mensagens-container');
        
        // Event listeners para tipo de agendamento
        document.querySelectorAll('input[name="tipo_agendamento"]').forEach(radio => {
            radio.addEventListener('change', function() {
                state.tipoAgendamento = this.value;
                
                // Mostrar/ocultar opções de intervalo para 3 aulas
                const opcoesTresAulas = document.getElementById('opcoesTresAulas');
                if (this.value === 'tres') {
                    opcoesTresAulas.style.display = 'block';
                } else {
                    opcoesTresAulas.style.display = 'none';
                }
                
                // Recarregar slots
                carregarSlotsDisponiveis();
            });
        });
        
        // Event listeners para posição do intervalo
        document.querySelectorAll('input[name="posicao_intervalo"]').forEach(radio => {
            radio.addEventListener('change', function() {
                state.posicaoIntervalo = this.value;
                if (state.tipoAgendamento === 'tres') {
                    carregarSlotsDisponiveis();
                }
            });
        });
        
        // Event listener para botão confirmar
        if (elements.btnConfirmar) {
            elements.btnConfirmar.addEventListener('click', confirmarAgendamento);
        }
        
        // Event listener para botão cancelar
        if (elements.btnCancelar) {
            elements.btnCancelar.addEventListener('click', function() {
                state.slotSelecionado = null;
                elements.resumoAgendamento.style.display = 'none';
                limparSelecao();
            });
        }
        
        // Carregar slots iniciais
        carregarSlotsDisponiveis();
    }
    
    /**
     * Carregar slots disponíveis da API
     */
    function carregarSlotsDisponiveis() {
        if (!state.alunoId) {
            mostrarErro('ID do aluno não informado');
            return;
        }
        
        // Mostrar loading
        if (elements.loadingSlots) {
            elements.loadingSlots.style.display = 'block';
        }
        if (elements.mensagemSemSlots) {
            elements.mensagemSemSlots.style.display = 'none';
        }
        if (elements.containerDias) {
            elements.containerDias.innerHTML = '';
        }
        
        // Montar URL da API
        const params = new URLSearchParams({
            aluno_id: state.alunoId,
            intervalo: state.tipoAgendamento,
            dias: 14,
            limite: 30
        });
        
        // Adicionar posição do intervalo se for 3 aulas
        if (state.tipoAgendamento === 'tres') {
            params.append('posicao', state.posicaoIntervalo);
        }
        
        const url = `api/disponibilidade.php?${params.toString()}`;
        
        // Fazer requisição
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (elements.loadingSlots) {
                    elements.loadingSlots.style.display = 'none';
                }
                
                if (data.success && data.slots && data.slots.length > 0) {
                    state.slots = data.slots;
                    state.slotsPorData = agruparSlotsPorData(data.slots);
                    renderizarDiasComSlots(state.slotsPorData);
                } else {
                    // Não há slots disponíveis
                    if (elements.mensagemSemSlots) {
                        elements.mensagemSemSlots.style.display = 'block';
                    }
                    state.slots = [];
                    state.slotsPorData = {};
                }
            })
            .catch(error => {
                console.error('Erro ao carregar slots:', error);
                if (elements.loadingSlots) {
                    elements.loadingSlots.style.display = 'none';
                }
                mostrarErro('Erro ao buscar horários disponíveis. Tente novamente.');
            });
    }
    
    /**
     * Agrupar slots por data
     */
    function agruparSlotsPorData(slots) {
        const agrupados = {};
        
        slots.forEach(slot => {
            const data = slot.data;
            if (!agrupados[data]) {
                agrupados[data] = [];
            }
            agrupados[data].push(slot);
        });
        
        // Ordenar slots dentro de cada data por hora_inicio
        Object.keys(agrupados).forEach(data => {
            agrupados[data].sort((a, b) => {
                return a.hora_inicio.localeCompare(b.hora_inicio);
            });
        });
        
        return agrupados;
    }
    
    /**
     * Renderizar lista de dias com slots
     */
    function renderizarDiasComSlots(slotsPorData) {
        if (!elements.containerDias) {
            return;
        }
        
        elements.containerDias.innerHTML = '';
        
        // Ordenar datas
        const datas = Object.keys(slotsPorData).sort();
        
        if (datas.length === 0) {
            return;
        }
        
        datas.forEach(data => {
            const slots = slotsPorData[data];
            const diaCard = criarCardDia(data, slots.length);
            elements.containerDias.appendChild(diaCard);
        });
    }
    
    /**
     * Criar card de dia
     */
    function criarCardDia(data, quantidadeSlots) {
        const card = document.createElement('div');
        card.className = 'dia-card';
        card.dataset.data = data;
        
        // Formatar data
        const dataObj = new Date(data + 'T00:00:00');
        const diaSemana = dataObj.toLocaleDateString('pt-BR', { weekday: 'long' });
        const dataFormatada = dataObj.toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit', year: 'numeric' });
        
        card.innerHTML = `
            <div class="dia-card-header">
                <div class="dia-card-info">
                    <i class="fas fa-calendar-day me-2"></i>
                    <span class="dia-data">${dataFormatada}</span>
                    <span class="dia-semana">${diaSemana}</span>
                </div>
                <div class="dia-card-badge">
                    <span class="badge bg-success">${quantidadeSlots} slot${quantidadeSlots !== 1 ? 's' : ''}</span>
                </div>
                <div class="dia-card-arrow">
                    <i class="fas fa-chevron-down"></i>
                </div>
            </div>
            <div class="dia-card-slots" style="display: none;">
                <!-- Slots serão renderizados aqui -->
            </div>
        `;
        
        // Event listener para expandir/recolher
        const header = card.querySelector('.dia-card-header');
        header.addEventListener('click', function() {
            expandirDia(data, card);
        });
        
        return card;
    }
    
    /**
     * Expandir/recolher dia
     */
    function expandirDia(data, cardElement) {
        const slotsContainer = cardElement.querySelector('.dia-card-slots');
        const arrow = cardElement.querySelector('.dia-card-arrow i');
        
        if (slotsContainer.style.display === 'none') {
            // Expandir
            if (slotsContainer.children.length === 0) {
                // Renderizar slots pela primeira vez
                const slots = state.slotsPorData[data] || [];
                renderizarSlotsDoDia(slots, data, slotsContainer);
            }
            
            slotsContainer.style.display = 'block';
            cardElement.classList.add('dia-card--expandido');
            arrow.classList.remove('fa-chevron-down');
            arrow.classList.add('fa-chevron-up');
            state.diasExpandidos.add(data);
        } else {
            // Recolher
            slotsContainer.style.display = 'none';
            cardElement.classList.remove('dia-card--expandido');
            arrow.classList.remove('fa-chevron-up');
            arrow.classList.add('fa-chevron-down');
            state.diasExpandidos.delete(data);
        }
    }
    
    /**
     * Renderizar slots de um dia
     */
    function renderizarSlotsDoDia(slots, data, container) {
        container.innerHTML = '';
        
        slots.forEach((slot, index) => {
            const slotCard = criarCardSlot(slot, data, index);
            container.appendChild(slotCard);
        });
    }
    
    /**
     * Criar card de slot
     */
    function criarCardSlot(slot, data, index) {
        const card = document.createElement('div');
        card.className = 'slot-card';
        card.dataset.data = data;
        card.dataset.index = index;
        
        // Formatar horário
        const horaInicio = slot.hora_inicio.substring(0, 5); // HH:MM
        const horaFim = slot.hora_fim.substring(0, 5); // HH:MM
        
        // Descrição do tipo
        let tipoDesc = '';
        if (slot.total_aulas === 1) {
            tipoDesc = '1 aula (50 min)';
        } else if (slot.total_aulas === 2) {
            tipoDesc = '2 aulas (1h 40min)';
        } else if (slot.total_aulas === 3) {
            tipoDesc = '3 aulas (2h 30min)';
        }
        
        card.innerHTML = `
            <div class="slot-card-header">
                <i class="fas fa-clock me-2"></i>
                <span class="slot-horario">${horaInicio} - ${horaFim}</span>
                <span class="slot-tipo-badge">${tipoDesc}</span>
            </div>
            <div class="slot-card-body">
                <div class="slot-info">
                    <i class="fas fa-user-tie me-2"></i>
                    <span>${slot.instrutor.nome}</span>
                </div>
                <div class="slot-info">
                    <i class="fas fa-car me-2"></i>
                    <span>${slot.veiculo.modelo} - ${slot.veiculo.placa}</span>
                </div>
            </div>
            <div class="slot-card-selected" style="display: none;">
                <i class="fas fa-check-circle"></i>
                <span>Selecionado</span>
            </div>
        `;
        
        // Event listener para selecionar slot
        card.addEventListener('click', function() {
            selecionarSlot(slot, data, index);
        });
        
        return card;
    }
    
    /**
     * Selecionar slot
     */
    function selecionarSlot(slot, data, index) {
        // Limpar seleção anterior
        limparSelecao();
        
        // Marcar slot como selecionado
        const slotCard = document.querySelector(`.slot-card[data-data="${data}"][data-index="${index}"]`);
        if (slotCard) {
            slotCard.classList.add('slot-card--selecionado');
            const selectedBadge = slotCard.querySelector('.slot-card-selected');
            if (selectedBadge) {
                selectedBadge.style.display = 'flex';
            }
        }
        
        // Salvar slot selecionado
        state.slotSelecionado = {
            slot: slot,
            data: data,
            index: index
        };
        
        // Exibir resumo
        exibirResumo(slot, data);
    }
    
    /**
     * Limpar seleção
     */
    function limparSelecao() {
        document.querySelectorAll('.slot-card--selecionado').forEach(card => {
            card.classList.remove('slot-card--selecionado');
            const selectedBadge = card.querySelector('.slot-card-selected');
            if (selectedBadge) {
                selectedBadge.style.display = 'none';
            }
        });
    }
    
    /**
     * Exibir resumo do agendamento
     */
    function exibirResumo(slot, data) {
        if (!elements.resumoAgendamento) {
            return;
        }
        
        // Formatar data
        const dataObj = new Date(data + 'T00:00:00');
        const dataFormatada = dataObj.toLocaleDateString('pt-BR', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric',
            weekday: 'long'
        });
        
        // Formatar horário
        const horaInicio = slot.hora_inicio.substring(0, 5);
        const horaFim = slot.hora_fim.substring(0, 5);
        
        // Tipo de agendamento
        let tipoDesc = '';
        if (slot.total_aulas === 1) {
            tipoDesc = '1 Aula (50 minutos)';
        } else if (slot.total_aulas === 2) {
            tipoDesc = '2 Aulas (1h 40min)';
        } else if (slot.total_aulas === 3) {
            tipoDesc = '3 Aulas (2h 30min)';
        }
        
        // Preencher resumo
        document.getElementById('resumo-aluno').textContent = state.alunoNome;
        document.getElementById('resumo-data').textContent = dataFormatada;
        document.getElementById('resumo-horario').textContent = `${horaInicio} - ${horaFim}`;
        document.getElementById('resumo-instrutor').textContent = slot.instrutor.nome;
        document.getElementById('resumo-veiculo').textContent = `${slot.veiculo.modelo} - ${slot.veiculo.placa}`;
        document.getElementById('resumo-tipo').textContent = tipoDesc;
        
        // Mostrar resumo
        elements.resumoAgendamento.style.display = 'block';
        
        // Scroll suave até o resumo
        elements.resumoAgendamento.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
    
    /**
     * Confirmar agendamento
     */
    function confirmarAgendamento() {
        if (!state.slotSelecionado) {
            mostrarErro('Por favor, selecione um horário disponível.');
            return;
        }
        
        const slot = state.slotSelecionado.slot;
        const data = state.slotSelecionado.data;
        
        // Validar dados
        if (!slot.instrutor || !slot.veiculo || !slot.hora_inicio || !slot.hora_fim) {
            mostrarErro('Dados do slot incompletos. Tente selecionar outro horário.');
            return;
        }
        
        // Desabilitar botão
        if (elements.btnConfirmar) {
            elements.btnConfirmar.disabled = true;
            elements.btnConfirmar.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Agendando...';
        }
        
        // Montar dados para a API
        const formData = new FormData();
        formData.append('aluno_id', state.alunoId);
        formData.append('tipo_aula', 'pratica'); // Sempre prática nesta tela
        formData.append('instrutor_id', slot.instrutor.id);
        formData.append('veiculo_id', slot.veiculo.id);
        formData.append('data_aula', data);
        formData.append('hora_inicio', slot.hora_inicio);
        formData.append('duracao', '50');
        formData.append('tipo_agendamento', state.tipoAgendamento);
        
        // Adicionar posição do intervalo se for 3 aulas
        if (state.tipoAgendamento === 'tres') {
            formData.append('posicao_intervalo', state.posicaoIntervalo);
        }
        
        // Enviar requisição
        fetch('api/agendamento.php', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Tratar resposta HTTP 409 (Conflict)
            if (response.status === 409) {
                return response.text().then(text => {
                    try {
                        const errorData = JSON.parse(text);
                        throw new Error(`CONFLITO: ${errorData.mensagem || 'Conflito de agendamento detectado'}`);
                    } catch (e) {
                        if (e.message && e.message.startsWith('CONFLITO:')) {
                            throw e;
                        }
                        // Tentar extrair mensagem manualmente
                        let mensagemErro = 'Conflito de agendamento detectado';
                        const match = text.match(/"mensagem":"([^"]+)"/);
                        if (match && match[1]) {
                            mensagemErro = match[1];
                        }
                        throw new Error(`CONFLITO: ${mensagemErro}`);
                    }
                });
            }
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON:', e);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            if (data.success) {
                mostrarSucesso('Aula(s) agendada(s) com sucesso!', data.dados || {});
                
                // Opções após sucesso
                setTimeout(() => {
                    const opcoes = criarOpcoesPosAgendamento();
                    elements.mensagensContainer.appendChild(opcoes);
                }, 1000);
            } else {
                mostrarErro('Erro ao agendar aula: ' + (data.mensagem || 'Erro desconhecido'));
                if (elements.btnConfirmar) {
                    elements.btnConfirmar.disabled = false;
                    elements.btnConfirmar.innerHTML = '<i class="fas fa-check-circle me-1"></i>Confirmar Agendamento';
                }
            }
        })
        .catch(error => {
            console.error('Erro ao confirmar agendamento:', error);
            
            let mensagemErro = 'Erro ao agendar aula. Tente novamente.';
            
            if (error.message && error.message.startsWith('CONFLITO:')) {
                mensagemErro = error.message.replace('CONFLITO: ', '');
            }
            
            mostrarErro(mensagemErro);
            
            // Reativar botão
            if (elements.btnConfirmar) {
                elements.btnConfirmar.disabled = false;
                elements.btnConfirmar.innerHTML = '<i class="fas fa-check-circle me-1"></i>Confirmar Agendamento';
            }
        });
    }
    
    /**
     * Criar opções pós-agendamento
     */
    function criarOpcoesPosAgendamento() {
        const div = document.createElement('div');
        div.className = 'alert alert-info mt-3';
        div.innerHTML = `
            <h6><i class="fas fa-check-circle me-2"></i>O que deseja fazer agora?</h6>
            <div class="d-flex gap-2 mt-3">
                <a href="?page=alunos" class="btn btn-sm btn-primary">
                    <i class="fas fa-arrow-left me-1"></i>Voltar para Alunos
                </a>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="location.reload()">
                    <i class="fas fa-plus me-1"></i>Agendar Outra Aula
                </button>
            </div>
        `;
        return div;
    }
    
    /**
     * Mostrar mensagem de sucesso
     */
    function mostrarSucesso(mensagem, dados) {
        if (!elements.mensagensContainer) {
            return;
        }
        
        // Limpar mensagens anteriores
        elements.mensagensContainer.innerHTML = '';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-success alert-dismissible fade show';
        alertDiv.innerHTML = `
            <h5><i class="fas fa-check-circle me-2"></i>${mensagem}</h5>
            ${dados.aluno ? `<p><strong>Aluno:</strong> ${dados.aluno}</p>` : ''}
            ${dados.instrutor ? `<p><strong>Instrutor:</strong> ${dados.instrutor}</p>` : ''}
            ${dados.data ? `<p><strong>Data:</strong> ${dados.data}</p>` : ''}
            ${dados.total_aulas ? `<p><strong>Total de Aulas:</strong> ${dados.total_aulas}</p>` : ''}
            ${dados.tipo ? `<p><strong>Tipo:</strong> ${dados.tipo}</p>` : ''}
            ${dados.aulas_criadas ? `
                <hr>
                <h6><i class="fas fa-clock me-2"></i>Horários das Aulas:</h6>
                ${dados.aulas_criadas.map((aula, index) => `
                    <p class="mb-1"><strong>${index + 1}ª Aula:</strong> ${aula.hora_inicio} - ${aula.hora_fim}</p>
                `).join('')}
            ` : ''}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        elements.mensagensContainer.appendChild(alertDiv);
    }
    
    /**
     * Mostrar mensagem de erro
     */
    function mostrarErro(mensagem) {
        if (!elements.mensagensContainer) {
            return;
        }
        
        // Limpar mensagens anteriores
        elements.mensagensContainer.innerHTML = '';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            <h5><i class="fas fa-exclamation-triangle me-2"></i>Erro</h5>
            <p>${mensagem}</p>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        elements.mensagensContainer.appendChild(alertDiv);
    }
    
    // Expor API pública
    window.agendarAulaApp = {
        init: init,
        carregarSlotsDisponiveis: carregarSlotsDisponiveis
    };
})();
