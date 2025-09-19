/**
 * JAVASCRIPT ESPECÍFICO - SISTEMA DE AGENDAMENTO
 * Baseado no sistema e-condutor para mesma experiência do usuário
 */

// =====================================================
// SISTEMA DE AGENDAMENTO COMPLETO
// =====================================================

class SistemaAgendamento {
    constructor() {
        this.calendario = null;
        this.aulasData = [];
        this.instrutoresData = [];
        this.veiculosData = [];
        this.alunosData = [];
        this.filtrosAtivos = {};
        this.init();
    }

    init() {
        this.carregarDados();
        this.inicializarCalendario();
        this.configurarEventos();
        this.atualizarEstatisticas();
    }

    async carregarDados() {
        try {
            this.loadingSystem.show('Carregando dados do sistema...');
            
            // Os dados já estão disponíveis no PHP, vamos usá-los diretamente
            this.carregarDadosDoPHP();
            
            // Tentar carregar aulas via API se disponível
            try {
                const aulasRes = await fetch(API_CONFIG.getRelativeApiUrl('AGENDAMENTO'));
                const aulasResponse = await aulasRes.json();
                if (aulasResponse.sucesso) {
                    this.aulasData = aulasResponse.dados;
                }
            } catch (error) {
                console.warn('Não foi possível carregar aulas via API, usando dados do PHP');
                this.aulasData = [];
            }
            
            this.preencherFiltros();
            this.atualizarEstatisticas();
            
        } catch (error) {
            console.error('Erro ao carregar dados:', error);
            this.notificationSystem.error('Erro ao carregar dados do sistema');
            
            // Carregar dados simulados como fallback
            this.carregarDadosSimulados();
        } finally {
            this.loadingSystem.hide();
        }
    }

    carregarDadosDoPHP() {
        // Os dados já estão disponíveis nas variáveis PHP
        // Vamos extraí-los dos elementos HTML já populados
        
        // Extrair instrutores do dropdown de filtro
        const selectInstrutores = document.getElementById('filter-instrutor');
        if (selectInstrutores) {
            this.instrutoresData = [];
            for (let i = 1; i < selectInstrutores.options.length; i++) {
                const option = selectInstrutores.options[i];
                this.instrutoresData.push({
                    id: option.value,
                    nome: option.textContent
                });
            }
        }
        
        // Extrair veículos do dropdown do modal
        const selectVeiculos = document.getElementById('veiculo_id');
        if (selectVeiculos) {
            this.veiculosData = [];
            for (let i = 1; i < selectVeiculos.options.length; i++) {
                const option = selectVeiculos.options[i];
                // Parsear texto do veículo: "MARCA MODELO - PLACA"
                const texto = option.textContent;
                const match = texto.match(/^(.+) - (.+)$/);
                if (match) {
                    const marcaModelo = match[1].trim();
                    const placa = match[2].trim();
                    const partes = marcaModelo.split(' ');
                    const marca = partes[0] || '';
                    const modelo = partes.slice(1).join(' ') || '';
                    
                    this.veiculosData.push({
                        id: option.value,
                        marca: marca,
                        modelo: modelo,
                        placa: placa
                    });
                }
            }
        }
        
        // Extrair alunos do dropdown do modal
        const selectAlunos = document.getElementById('aluno_id');
        if (selectAlunos) {
            this.alunosData = [];
            for (let i = 1; i < selectAlunos.options.length; i++) {
                const option = selectAlunos.options[i];
                this.alunosData.push({
                    id: option.value,
                    nome: option.textContent.split(' - ')[0] // Remove categoria
                });
            }
        }
        
        // Extrair CFCs do dropdown de filtro
        const selectCFCs = document.getElementById('filter-cfc');
        if (selectCFCs) {
            this.cfcsData = [];
            for (let i = 1; i < selectCFCs.options.length; i++) {
                const option = selectCFCs.options[i];
                this.cfcsData.push({
                    id: option.value,
                    nome: option.textContent
                });
            }
        }
        
        console.log('Dados carregados do PHP:', {
            instrutores: this.instrutoresData.length,
            veiculos: this.veiculosData.length,
            alunos: this.alunosData.length,
            cfcs: this.cfcsData.length
        });
    }
    
    carregarDadosSimulados() {
        // Dados simulados como fallback
        this.instrutoresData = [
            { id: 1, nome: 'João Silva', email: 'joao@cfc.com' },
            { id: 2, nome: 'Maria Santos', email: 'maria@cfc.com' },
            { id: 3, nome: 'Pedro Costa', email: 'pedro@cfc.com' }
        ];
        
        this.veiculosData = [
            { id: 1, modelo: 'Gol G6', placa: 'ABC-1234', categoria: 'B' },
            { id: 2, modelo: 'Onix', placa: 'DEF-5678', categoria: 'B' },
            { id: 3, modelo: 'Civic', placa: 'GHI-9012', categoria: 'B' }
        ];
        
        this.alunosData = [
            { id: 1, nome: 'Ana Oliveira', email: 'ana@email.com', categoria: 'B' },
            { id: 2, nome: 'Carlos Lima', email: 'carlos@email.com', categoria: 'B' },
            { id: 3, nome: 'Fernanda Rocha', email: 'fernanda@email.com', categoria: 'B' }
        ];
        
        this.cfcsData = [
            { id: 1, nome: 'CFC Centro', endereco: 'Rua Central, 123' },
            { id: 2, nome: 'CFC Norte', endereco: 'Av. Norte, 456' }
        ];
        
        this.aulasData = [];
        
        // Preencher filtros com dados simulados
        this.preencherFiltros();
    }

    preencherFiltros() {
        console.log('Preenchendo filtros...');
        
        // Os dropdowns já estão populados pelo PHP, não precisamos preenchê-los novamente
        // Apenas verificar se os dados foram carregados corretamente
        console.log('Verificando dados carregados:');
        console.log('- Instrutores:', this.instrutoresData?.length || 0);
        console.log('- Veículos:', this.veiculosData?.length || 0);
        console.log('- Alunos:', this.alunosData?.length || 0);
        console.log('- CFCs:', this.cfcsData?.length || 0);
        
        // Verificar se os dropdowns estão populados
        this.verificarDropdowns();
        
        console.log('Filtros verificados com sucesso');
    }

    verificarDropdowns() {
        const dropdowns = [
            { id: 'filter-instrutor', nome: 'Filtro Instrutores' },
            { id: 'veiculo_id', nome: 'Veículos' },
            { id: 'instrutor_id', nome: 'Instrutores' },
            { id: 'aluno_id', nome: 'Alunos' }
        ];
        
        dropdowns.forEach(dropdown => {
            const element = document.getElementById(dropdown.id);
            if (element) {
                const optionsCount = element.options.length;
                console.log(`${dropdown.nome}: ${optionsCount} opções`);
                
                if (optionsCount <= 1) {
                    console.warn(`⚠️ ${dropdown.nome} tem apenas ${optionsCount} opção(ões)`);
                } else {
                    console.log(`✅ ${dropdown.nome} carregado corretamente`);
                }
            } else {
                console.error(`❌ ${dropdown.nome} não encontrado`);
            }
        });
    }

    preencherDropdown(elementId, dados, campoTexto, campoValor) {
        const select = document.getElementById(elementId);
        if (!select) {
            console.warn(`Elemento ${elementId} não encontrado`);
            return;
        }

        // Limpar opções existentes (exceto a primeira que é o placeholder)
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        // Adicionar opções
        dados.forEach(item => {
            const option = document.createElement('option');
            option.value = item[campoValor];
            
            if (elementId === 'veiculo_id') {
                // Para veículos, criar texto personalizado
                option.textContent = `${item.marca} ${item.modelo} - ${item.placa}`;
            } else {
                option.textContent = item[campoTexto];
            }
            
            select.appendChild(option);
        });

        console.log(`Dropdown ${elementId} preenchido com ${dados.length} itens`);
    }

    inicializarCalendario() {
        const calendarEl = document.getElementById('calendar');
        if (!calendarEl) return;

        // Configuração do FullCalendar
        this.calendario = new FullCalendar.Calendar(calendarEl, {
            initialView: 'dayGridMonth',
            locale: 'pt-br',
            headerToolbar: false, // Usamos nossa própria toolbar
            height: 'auto',
            expandRows: true,
            dayMaxEvents: true,
            selectable: true,
            selectMirror: true,
            editable: true,
            eventTimeFormat: {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            },
            events: this.aulasData.map(aula => this.formatarEvento(aula)),
            select: (info) => this.onSelectDate(info),
            eventClick: (info) => this.onEventClick(info),
            eventDrop: (info) => this.onEventDrop(info),
            eventResize: (info) => this.onEventResize(info),
            eventDidMount: (info) => this.onEventDidMount(info),
            loading: (isLoading) => this.onLoadingChange(isLoading)
        });

        this.calendario.render();
        this.atualizarTituloCalendario();
    }

    formatarEvento(aula) {
        const cores = {
            teorica: '#3498db',
            pratica: '#e74c3c',
            agendada: '#f39c12',
            'em_andamento': '#3498db',
            concluida: '#27ae60',
            cancelada: '#95a5a6'
        };

        return {
            id: aula.id,
            title: `${aula.aluno_nome} - ${aula.instrutor_nome}`,
            start: `${aula.data_aula}T${aula.hora_inicio}`,
            end: `${aula.data_aula}T${aula.hora_fim}`,
            backgroundColor: cores[aula.tipo_aula] || cores[aula.status],
            borderColor: cores[aula.tipo_aula] || cores[aula.status],
            textColor: '#ffffff',
            extendedProps: {
                tipo_aula: aula.tipo_aula,
                status: aula.status,
                aluno_id: aula.aluno_id,
                instrutor_id: aula.instrutor_id,
                veiculo_id: aula.veiculo_id,
                observacoes: aula.observacoes,
                placa: aula.placa,
                modelo: aula.modelo,
                marca: aula.marca
            }
        };
    }

    onSelectDate(info) {
        // Abrir modal para nova aula na data selecionada
        this.abrirModalNovaAula(info.startStr);
    }

    onEventClick(info) {
        // Abrir modal para editar aula
        this.abrirModalEditarAula(info.event.id);
    }

    onEventDrop(info) {
        // Atualizar horário da aula via drag & drop
        this.confirmarMudancaHorario(info.event, info.oldEvent);
    }

    onEventResize(info) {
        // Atualizar duração da aula via resize
        this.confirmarMudancaDuracao(info.event, info.oldEvent);
    }

    onEventDidMount(info) {
        // Adicionar tooltips e informações extras
        this.adicionarTooltipEvento(info.event);
    }

    onLoadingChange(isLoading) {
        // Mostrar/ocultar indicador de carregamento
        if (isLoading) {
            loading.showElement(document.getElementById('calendar'), 'Carregando calendário...');
        } else {
            loading.hideElement(document.getElementById('calendar'));
        }
    }

    confirmarMudancaHorario(novoEvento, eventoAntigo) {
        const mensagem = `Deseja alterar o horário da aula de "${novoEvento.title}"?\n\n` +
                        `De: ${this.formatarDataHora(eventoAntigo.start)}\n` +
                        `Para: ${this.formatarDataHora(novoEvento.start)}`;

        modals.confirm(mensagem, () => {
            this.atualizarHorarioAula(novoEvento.id, novoEvento.start, novoEvento.end);
        }, () => {
            // Reverter mudança
            this.calendario.refetchEvents();
        });
    }

    confirmarMudancaDuracao(novoEvento, eventoAntigo) {
        const duracaoAntiga = this.calcularDuracao(eventoAntigo.start, eventoAntigo.end);
        const duracaoNova = this.calcularDuracao(novoEvento.start, novoEvento.end);

        const mensagem = `Deseja alterar a duração da aula de "${novoEvento.title}"?\n\n` +
                        `De: ${duracaoAntiga} minutos\n` +
                        `Para: ${duracaoNova} minutos`;

        modals.confirm(mensagem, () => {
            this.atualizarDuracaoAula(novoEvento.id, novoEvento.start, novoEvento.end);
        }, () => {
            // Reverter mudança
            this.calendario.refetchEvents();
        });
    }

    adicionarTooltipEvento(evento) {
        const props = evento.extendedProps;
        const tooltip = `
            <div class="event-tooltip">
                <strong>${evento.title}</strong><br>
                Tipo: ${props.tipo_aula}<br>
                Status: ${props.status}<br>
                ${props.placa ? `Veículo: ${props.marca} ${props.modelo} (${props.placa})<br>` : ''}
                ${props.observacoes ? `Obs: ${props.observacoes}` : ''}
            </div>
        `;

        // Implementar tooltip personalizado
        this.criarTooltip(evento.el, tooltip);
    }

    criarTooltip(elemento, conteudo) {
        // Implementar sistema de tooltip
        elemento.setAttribute('title', conteudo.replace(/<[^>]*>/g, ''));
    }

    // =====================================================
    // NAVEGAÇÃO DO CALENDÁRIO
    // =====================================================

    anterior() {
        this.calendario.prev();
        this.atualizarTituloCalendario();
    }

    proximo() {
        this.calendario.next();
        this.atualizarTituloCalendario();
    }

    mudarVisualizacao(view) {
        // Atualizar botões ativos
        document.querySelectorAll('.calendar-views .btn').forEach(btn => {
            btn.classList.remove('active');
        });
        event.target.classList.add('active');

        // Mudar visualização
        this.calendario.changeView(view);
        this.atualizarTituloCalendario();
    }

    atualizarTituloCalendario() {
        const titulo = document.getElementById('calendar-title');
        if (titulo && this.calendario) {
            const data = this.calendario.getDate();
            const view = this.calendario.view.type;
            
            let texto = '';
            switch (view) {
                case 'dayGridMonth':
                    texto = data.toLocaleDateString('pt-BR', { month: 'long', year: 'numeric' });
                    break;
                case 'timeGridWeek':
                    const inicio = new Date(data);
                    const fim = new Date(data);
                    fim.setDate(fim.getDate() + 6);
                    texto = `${inicio.toLocaleDateString('pt-BR')} - ${fim.toLocaleDateString('pt-BR')}`;
                    break;
                case 'timeGridDay':
                    texto = data.toLocaleDateString('pt-BR', { 
                        weekday: 'long', 
                        day: 'numeric', 
                        month: 'long', 
                        year: 'numeric' 
                    });
                    break;
                case 'listWeek':
                    texto = `Semana de ${data.toLocaleDateString('pt-BR')}`;
                    break;
            }
            
            titulo.textContent = texto.charAt(0).toUpperCase() + texto.slice(1);
        }
    }

    // =====================================================
    // MODAIS E FORMULÁRIOS
    // =====================================================

    abrirModalNovaAula(dataSelecionada = null) {
        const modal = document.getElementById('modal-nova-aula');
        if (modal) {
            modal.style.display = 'flex';
            this.limparFormularioNovaAula();
            
            if (dataSelecionada) {
                document.getElementById('data_aula').value = dataSelecionada.split('T')[0];
            }
        }
    }

    fecharModalNovaAula() {
        const modal = document.getElementById('modal-nova-aula');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    abrirModalEditarAula(aulaId) {
        const aula = this.aulasData.find(a => a.id == aulaId);
        if (aula) {
            this.preencherFormularioEdicao(aula);
            const modal = document.getElementById('modal-editar-aula');
            if (modal) {
                modal.style.display = 'flex';
            }
        }
    }

    fecharModalEditarAula() {
        const modal = document.getElementById('modal-editar-aula');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // =====================================================
    // OPERAÇÕES CRUD
    // =====================================================

    async salvarNovaAula(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const dados = Object.fromEntries(formData.entries());
        
        try {
            loading.showButton(event.target.querySelector('button[type="submit"]'), 'Salvando...');
            
            // Validar dados
            if (!this.validarDadosAula(dados)) {
                return;
            }

            // Verificar disponibilidade
            if (!await this.verificarDisponibilidade(dados)) {
                return;
            }

            // Enviar para API
            const response = await this.enviarAula(dados);
            
            if (response.success) {
                notifications.success('Aula agendada com sucesso!');
                this.fecharModalNovaAula();
                this.recarregarCalendario();
                this.atualizarEstatisticas();
            } else {
                notifications.error(response.message || 'Erro ao agendar aula');
            }
            
        } catch (error) {
            console.error('Erro ao salvar aula:', error);
            notifications.error('Erro interno ao salvar aula');
        } finally {
            loading.hideButton(event.target.querySelector('button[type="submit"]'));
        }
    }

    async atualizarAula(event) {
        event.preventDefault();
        
        const formData = new FormData(event.target);
        const dados = Object.fromEntries(formData.entries());
        
        try {
            loading.showButton(event.target.querySelector('button[type="submit"]'), 'Atualizando...');
            
            // Validar dados
            if (!this.validarDadosAula(dados)) {
                return;
            }

            // Verificar disponibilidade (excluindo a própria aula)
            if (!await this.verificarDisponibilidade(dados, dados.aula_id)) {
                return;
            }

            // Enviar para API
            const response = await this.atualizarAulaAPI(dados);
            
            if (response.success) {
                notifications.success('Aula atualizada com sucesso!');
                this.fecharModalEditarAula();
                this.recarregarCalendario();
                this.atualizarEstatisticas();
            } else {
                notifications.error(response.message || 'Erro ao atualizar aula');
            }
            
        } catch (error) {
            console.error('Erro ao atualizar aula:', error);
            notifications.error('Erro interno ao atualizar aula');
        } finally {
            loading.hideButton(event.target.querySelector('button[type="submit"]'));
        }
    }

    async excluirAula(aulaId) {
        const mensagem = 'Tem certeza que deseja excluir esta aula? Esta ação não pode ser desfeita.';
        
        modals.confirm(mensagem, async () => {
            try {
                loading.showGlobal('Excluindo aula...');
                
                const response = await this.excluirAulaAPI(aulaId);
                
                if (response.success) {
                    notifications.success('Aula excluída com sucesso!');
                    this.recarregarCalendario();
                    this.atualizarEstatisticas();
                } else {
                    notifications.error(response.message || 'Erro ao excluir aula');
                }
                
            } catch (error) {
                console.error('Erro ao excluir aula:', error);
                notifications.error('Erro interno ao excluir aula');
            } finally {
                loading.hideGlobal();
            }
        });
    }

    // =====================================================
    // VALIDAÇÕES E VERIFICAÇÕES
    // =====================================================

    validarDadosAula(dados) {
        const erros = [];

        if (!dados.aluno_id) erros.push('Aluno é obrigatório');
        if (!dados.instrutor_id) erros.push('Instrutor é obrigatório');
        if (!dados.tipo_aula) erros.push('Tipo de aula é obrigatório');
        if (!dados.data_aula) erros.push('Data da aula é obrigatória');
        if (!dados.hora_inicio) erros.push('Hora de início é obrigatória');
        if (!dados.hora_fim) erros.push('Hora de fim é obrigatória');

        if (dados.tipo_aula === 'pratica' && !dados.veiculo_id) {
            erros.push('Veículo é obrigatório para aulas práticas');
        }

        if (dados.hora_inicio >= dados.hora_fim) {
            erros.push('Hora de fim deve ser posterior à hora de início');
        }

        if (erros.length > 0) {
            notifications.error(erros.join('\n'));
            return false;
        }

        return true;
    }

    async verificarDisponibilidade(dados, aulaIdExcluir = null) {
        try {
            const params = new URLSearchParams({
                instrutor_id: dados.instrutor_id,
                data_aula: dados.data_aula,
                hora_inicio: dados.hora_inicio,
                hora_fim: dados.hora_fim,
                aula_id_excluir: aulaIdExcluir || ''
            });

            const response = await fetch(API_CONFIG.getRelativeApiUrl('VERIFICAR_DISPONIBILIDADE') + `?${params}`);
            const result = await response.json();

            if (!result.disponivel) {
                notifications.error(`Conflito de horário: ${result.mensagem}`);
                return false;
            }

            return true;
        } catch (error) {
            console.error('Erro ao verificar disponibilidade:', error);
            notifications.warning('Não foi possível verificar disponibilidade. Continuando...');
            return true;
        }
    }

    // =====================================================
    // APIS E COMUNICAÇÃO
    // =====================================================

    async enviarAula(dados) {
        try {
            const response = await fetch(API_CONFIG.getRelativeApiUrl('AGENDAMENTO') + '/aula', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            });
            
            // Tratar resposta HTTP 409 (Conflict) especificamente
            if (response.status === 409) {
                const errorData = await response.text();
                console.log('Resposta de erro 409:', errorData);
                try {
                    const parsedError = JSON.parse(errorData);
                    console.log('Dados de erro parseados:', parsedError);
                    throw new Error(`CONFLITO: ${parsedError.mensagem || 'Conflito de agendamento detectado'}`);
                } catch (e) {
                    console.error('Erro ao fazer parse do JSON de erro:', e);
                    console.error('Texto da resposta:', errorData);
                    // Se não conseguir fazer parse, extrair a mensagem do JSON manualmente
                    let mensagemErro = 'Veículo ou instrutor já possui aula agendada neste horário';
                    
                    // Tentar extrair a mensagem do JSON manualmente
                    const match = errorData.match(/"mensagem":"([^"]+)"/);
                    if (match && match[1]) {
                        mensagemErro = match[1];
                    } else if (errorData.includes('INSTRUTOR INDISPONÍVEL')) {
                        mensagemErro = errorData.replace(/.*INSTRUTOR INDISPONÍVEL: /, '👨‍🏫 INSTRUTOR INDISPONÍVEL: ').replace(/".*/, '');
                    } else if (errorData.includes('VEÍCULO INDISPONÍVEL')) {
                        mensagemErro = errorData.replace(/.*VEÍCULO INDISPONÍVEL: /, '🚗 VEÍCULO INDISPONÍVEL: ').replace(/".*/, '');
                    } else if (errorData.includes('LIMITE DE AULAS EXCEDIDO')) {
                        mensagemErro = errorData.replace(/.*LIMITE DE AULAS EXCEDIDO: /, '🚫 LIMITE DE AULAS EXCEDIDO: ').replace(/".*/, '');
                    }
                    
                    throw new Error(`CONFLITO: ${mensagemErro}`);
                }
            }
            
            const resultado = await response.json();
            
            if (!response.ok) {
                throw new Error(resultado.mensagem || 'Erro na requisição');
            }
            
            return resultado;
            
        } catch (error) {
            console.error('Erro ao enviar aula:', error);
            
            // Verificar se é erro de conflito específico
            if (error.message.startsWith('CONFLITO:')) {
                const mensagemConflito = error.message.replace('CONFLITO: ', '');
                return {
                    sucesso: false,
                    mensagem: `⚠️ ${mensagemConflito}`,
                    tipo: 'warning'
                };
            }
            
            return {
                sucesso: false,
                mensagem: error.message || 'Erro ao conectar com o servidor',
                tipo: 'erro'
            };
        }
    }

    async atualizarAulaAPI(dados) {
        try {
            const response = await fetch(API_CONFIG.getRelativeApiUrl('AGENDAMENTO') + '/aula', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            });
            
            const resultado = await response.json();
            
            if (!response.ok) {
                throw new Error(resultado.mensagem || 'Erro na requisição');
            }
            
            return resultado;
            
        } catch (error) {
            console.error('Erro ao atualizar aula:', error);
            return {
                sucesso: false,
                mensagem: error.message || 'Erro ao conectar com o servidor',
                tipo: 'erro'
            };
        }
    }

    async excluirAulaAPI(aulaId) {
        try {
            const response = await fetch(API_CONFIG.getRelativeApiUrl('AGENDAMENTO') + `/aula?id=${aulaId}`, {
                method: 'DELETE'
            });
            
            const resultado = await response.json();
            
            if (!response.ok) {
                throw new Error(resultado.mensagem || 'Erro na requisição');
                return;
            }
            
            return resultado;
            
        } catch (error) {
            console.error('Erro ao excluir aula:', error);
            return {
                sucesso: false,
                mensagem: error.message || 'Erro ao conectar com o servidor',
                tipo: 'erro'
            };
        }
    }

    // =====================================================
    // FILTROS E BUSCA
    // =====================================================

    aplicarFiltros() {
        const filtros = {
            cfc: document.getElementById('filter-cfc')?.value || '',
            instrutor: document.getElementById('filter-instrutor')?.value || '',
            tipo: document.getElementById('filter-tipo')?.value || '',
            status: document.getElementById('filter-status')?.value || ''
        };

        this.filtrosAtivos = filtros;
        this.filtrarEventos();
    }

    filtrarEventos() {
        if (!this.calendario) return;

        let eventosFiltrados = this.aulasData;

        // Aplicar filtros
        if (this.filtrosAtivos.instrutor) {
            eventosFiltrados = eventosFiltrados.filter(aula => 
                aula.instrutor_id == this.filtrosAtivos.instrutor
            );
        }

        if (this.filtrosAtivos.tipo) {
            eventosFiltrados = eventosFiltrados.filter(aula => 
                aula.tipo_aula === this.filtrosAtivos.tipo
            );
        }

        if (this.filtrosAtivos.status) {
            eventosFiltrados = eventosFiltrados.filter(aula => 
                aula.status === this.filtrosAtivos.status
            );
        }

        // Atualizar calendário
        this.calendario.removeAllEvents();
        this.calendario.addEventSource(eventosFiltrados.map(aula => this.formatarEvento(aula)));
    }

    // =====================================================
    // ESTATÍSTICAS E RELATÓRIOS
    // =====================================================

    atualizarEstatisticas() {
        const hoje = new Date().toISOString().split('T')[0];
        const inicioSemana = this.getInicioSemana();
        const fimSemana = this.getFimSemana();

        const aulasHoje = this.aulasData.filter(aula => 
            aula.data_aula === hoje && aula.status !== 'cancelada'
        ).length;

        const aulasSemana = this.aulasData.filter(aula => 
            aula.data_aula >= inicioSemana && 
            aula.data_aula <= fimSemana && 
            aula.status !== 'cancelada'
        ).length;

        const aulasPendentes = this.aulasData.filter(aula => 
            aula.status === 'agendada'
        ).length;

        const instrutoresDisponiveis = this.instrutoresData.filter(instrutor => 
            instrutor.ativo
        ).length;

        // Atualizar elementos na tela
        this.atualizarElementoEstatistica('aulas-hoje', aulasHoje);
        this.atualizarElementoEstatistica('aulas-semana', aulasSemana);
        this.atualizarElementoEstatistica('aulas-pendentes', aulasPendentes);
        this.atualizarElementoEstatistica('instrutores-disponiveis', instrutoresDisponiveis);
    }

    atualizarElementoEstatistica(id, valor) {
        const elemento = document.getElementById(id);
        if (elemento) {
            elemento.textContent = valor.toLocaleString('pt-BR');
        }
    }

    getInicioSemana() {
        const hoje = new Date();
        const diaSemana = hoje.getDay();
        const diasParaSegunda = diaSemana === 0 ? 6 : diaSemana - 1;
        const segunda = new Date(hoje);
        segunda.setDate(hoje.getDate() - diasParaSegunda);
        return segunda.toISOString().split('T')[0];
    }

    getFimSemana() {
        const segunda = new Date(this.getInicioSemana());
        const domingo = new Date(segunda);
        domingo.setDate(segunda.getDate() + 6);
        return domingo.toISOString().split('T')[0];
    }

    // =====================================================
    // UTILITÁRIOS
    // =====================================================

    formatarDataHora(data) {
        if (!data) return '';
        return new Date(data).toLocaleString('pt-BR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    calcularDuracao(inicio, fim) {
        if (!inicio || !fim) return 0;
        const diff = new Date(fim) - new Date(inicio);
        return Math.round(diff / (1000 * 60)); // em minutos
    }

    recarregarCalendario() {
        if (this.calendario) {
            this.calendario.refetchEvents();
        }
    }

    limparFormularioNovaAula() {
        const form = document.getElementById('form-nova-aula');
        if (form) {
            form.reset();
            const veiculoField = document.getElementById('veiculo_id');
            if (veiculoField) {
                veiculoField.disabled = true;
                veiculoField.required = false;
            }
        }
    }

    preencherFormularioEdicao(aula) {
        const campos = {
            'edit_aula_id': aula.id,
            'edit_aluno_id': aula.aluno_id,
            'edit_instrutor_id': aula.instrutor_id,
            'edit_tipo_aula': aula.tipo_aula,
            'edit_veiculo_id': aula.veiculo_id || '',
            'edit_data_aula': aula.data_aula,
            'edit_hora_inicio': aula.hora_inicio,
            'edit_hora_fim': aula.hora_fim,
            'edit_status': aula.status,
            'edit_observacoes': aula.observacoes || ''
        };

        Object.entries(campos).forEach(([id, valor]) => {
            const elemento = document.getElementById(id);
            if (elemento) {
                elemento.value = valor;
            }
        });
    }

    // =====================================================
    // CONFIGURAÇÃO DE EVENTOS
    // =====================================================

    configurarEventos() {
        // Eventos dos filtros
        document.getElementById('filter-cfc')?.addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filter-instrutor')?.addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filter-tipo')?.addEventListener('change', () => this.aplicarFiltros());
        document.getElementById('filter-status')?.addEventListener('change', () => this.aplicarFiltros());

        // Eventos dos formulários
        document.getElementById('form-nova-aula')?.addEventListener('submit', (e) => this.salvarNovaAula(e));
        document.getElementById('form-editar-aula')?.addEventListener('submit', (e) => this.atualizarAula(e));

        // Eventos dos modais
        this.configurarEventosModais();

        // Eventos específicos
        this.configurarEventosEspecificos();
    }

    configurarEventosModais() {
        // Fechar modais ao clicar fora
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Fechar modais com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay').forEach(modal => {
                    if (modal.style.display === 'flex') {
                        modal.style.display = 'none';
                    }
                });
            }
        });
    }

    configurarEventosEspecificos() {
        // Habilitar/desabilitar campo veículo baseado no tipo de aula
        document.getElementById('tipo_aula')?.addEventListener('change', (e) => {
            const veiculoField = document.getElementById('veiculo_id');
            if (veiculoField) {
                if (e.target.value === 'pratica') {
                    veiculoField.disabled = false;
                    veiculoField.required = true;
                } else {
                    veiculoField.disabled = true;
                    veiculoField.required = false;
                    veiculoField.value = '';
                }
            }
        });

        // Calcular hora de fim automaticamente
        document.getElementById('hora_inicio')?.addEventListener('change', (e) => {
            this.calcularHoraFim(e.target.value);
        });
    }

    calcularHoraFim(horaInicio) {
        if (horaInicio) {
            const hora = new Date(`2000-01-01T${horaInicio}`);
            hora.setHours(hora.getHours() + 1); // Padrão: 1 hora de aula
            const horaFim = hora.toTimeString().slice(0, 5);
            
            const horaFimField = document.getElementById('hora_fim');
            if (horaFimField) {
                horaFimField.value = horaFim;
            }
        }
    }
}

// =====================================================
// INICIALIZAÇÃO E EXPOSIÇÃO GLOBAL
// =====================================================

// Instanciar sistema quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Verificar se FullCalendar está disponível
    if (typeof FullCalendar === 'undefined') {
        console.error('FullCalendar não está carregado!');
        notifications.error('Erro: Biblioteca FullCalendar não encontrada');
        return;
    }

    // Inicializar sistema de agendamento
    window.sistemaAgendamento = new SistemaAgendamento();
    
    // Expor funções globais para compatibilidade
    window.calendario = {
        previous: () => window.sistemaAgendamento?.anterior(),
        next: () => window.sistemaAgendamento?.proximo()
    };

    // Funções globais para compatibilidade com HTML
    window.abrirModalNovaAula = () => window.sistemaAgendamento?.abrirModalNovaAula();
    window.fecharModalNovaAula = () => window.sistemaAgendamento?.fecharModalNovaAula();
    window.abrirModalEditarAula = (id) => window.sistemaAgendamento?.abrirModalEditarAula(id);
    window.fecharModalEditarAula = () => window.sistemaAgendamento?.fecharModalEditarAula();
    window.filtrarAgenda = () => window.sistemaAgendamento?.aplicarFiltros();
    window.mudarVisualizacao = (view) => window.sistemaAgendamento?.mudarVisualizacao(view);
    window.exportarAgenda = () => window.sistemaAgendamento?.exportarAgenda();
    window.verificarDisponibilidade = () => window.sistemaAgendamento?.verificarDisponibilidade();
    window.verificarDisponibilidadeInstrutor = () => window.sistemaAgendamento?.verificarDisponibilidadeInstrutor();
    window.verificarDisponibilidadeVeiculo = () => window.sistemaAgendamento?.verificarDisponibilidadeVeiculo();
    window.calcularHoraFim = () => window.sistemaAgendamento?.calcularHoraFim();
    window.salvarNovaAula = (e) => window.sistemaAgendamento?.salvarNovaAula(e);
    window.atualizarAula = (e) => window.sistemaAgendamento?.atualizarAula(e);

    console.log('✅ Sistema de Agendamento inicializado com sucesso!');
});

// =====================================================
// FUNÇÕES DE EXPORTAÇÃO E RELATÓRIOS
// =====================================================

window.exportarAgenda = function() {
    if (!window.sistemaAgendamento) return;
    
    try {
        const dados = window.sistemaAgendamento.aulasData;
        const csv = window.sistemaAgendamento.converterParaCSV(dados);
        
        // Criar e baixar arquivo
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', `agenda_${new Date().toISOString().split('T')[0]}.csv`);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        notifications.success('Agenda exportada com sucesso!');
    } catch (error) {
        console.error('Erro ao exportar agenda:', error);
        notifications.error('Erro ao exportar agenda');
    }
};

// =====================================================
// FUNÇÕES AUXILIARES
// =====================================================

window.abrirModalDisponibilidade = function() {
    notifications.info('Funcionalidade de verificação de disponibilidade será implementada em breve!');
};

// Função para converter dados para CSV
SistemaAgendamento.prototype.converterParaCSV = function(dados) {
    const headers = ['ID', 'Aluno', 'Instrutor', 'Tipo', 'Data', 'Início', 'Fim', 'Status', 'Veículo', 'Observações'];
    const rows = dados.map(aula => [
        aula.id,
        aula.aluno_nome,
        aula.instrutor_nome,
        aula.tipo_aula,
        aula.data_aula,
        aula.hora_inicio,
        aula.hora_fim,
        aula.status,
        aula.placa || 'N/A',
        aula.observacoes || ''
    ]);
    
    return [headers, ...rows]
        .map(row => row.map(cell => `"${cell}"`).join(','))
        .join('\n');
};
