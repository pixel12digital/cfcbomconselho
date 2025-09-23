<?php
// =====================================================
// SISTEMA DE AGENDAMENTO MODERNO - ADMIN/SECRETÁRIA
// Interface responsiva com calendário e filtros
// =====================================================

require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/guards/AgendamentoPermissions.php';

// Verificar permissões
$permissions = new AgendamentoPermissions();
$permCriar = $permissions->podeCriarAgendamento();
if (!$permCriar['permitido']) {
    header('HTTP/1.1 403 Forbidden');
    die('Acesso negado: ' . $permCriar['motivo']);
}

// Obter dados necessários para o agendamento
try {
    $db = db();
    
    // Buscar instrutores ativos
    $instrutores = $db->fetchAll("
        SELECT i.*, 
               COALESCE(u.nome, i.nome) as nome,
               COALESCE(u.email, i.email) as email,
               COALESCE(u.telefone, i.telefone) as telefone,
               CASE 
                   WHEN i.categorias_json IS NOT NULL AND i.categorias_json != '' AND i.categorias_json != '[]' THEN 
                       REPLACE(REPLACE(REPLACE(i.categorias_json, '[', ''), ']', ''), '\"', '')
                   WHEN i.categoria_habilitacao IS NOT NULL AND i.categoria_habilitacao != '' THEN 
                       i.categoria_habilitacao
                   ELSE 'Sem categoria'
               END as categoria_habilitacao
        FROM instrutores i
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY COALESCE(u.nome, i.nome)
    ");
    
    // Buscar veículos disponíveis
    $veiculos = $db->fetchAll("
        SELECT * FROM veiculos 
        WHERE ativo = 1 
        ORDER BY marca, modelo
    ");
    
    // Buscar alunos ativos
    $alunos = $db->fetchAll("
        SELECT * FROM alunos 
        WHERE status = 'ativo' 
        ORDER BY nome
    ");
    
    // Buscar CFCs ativos
    $cfcs = $db->fetchAll("
        SELECT * FROM cfcs 
        WHERE ativo = 1 
        ORDER BY nome
    ");
    
} catch (Exception $e) {
    if (LOG_ENABLED) {
        error_log('Erro ao carregar dados de agendamento: ' . $e->getMessage());
    }
    $instrutores = [];
    $veiculos = [];
    $alunos = [];
    $cfcs = [];
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Agendamento - CFC Bom Conselho</title>
    <link rel="stylesheet" href="assets/css/agendamento-moderno.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body class="agendamento-container">
    <!-- Header da Página -->
    <header class="agendamento-header">
        <div class="agendamento-header-content">
            <div>
                <h1 class="agendamento-title">Sistema de Agendamento</h1>
                <p class="agendamento-subtitle">Gerencie aulas, instrutores e veículos de forma eficiente</p>
            </div>
            <div class="agendamento-actions">
                <button class="btn btn-primary" onclick="abrirModalNovaAula()">
                    <i class="fas fa-plus"></i>
                    Nova Aula
                </button>
                <button class="btn btn-success" onclick="abrirModalDisponibilidade()">
                    <i class="fas fa-calendar-check"></i>
                    Verificar Disponibilidade
                </button>
                <button class="btn btn-secondary" onclick="exportarAgenda()">
                    <i class="fas fa-download"></i>
                    Exportar
                </button>
            </div>
        </div>
    </header>

    <!-- Filtros -->
    <section class="agendamento-filters">
        <div class="filters-grid">
            <div class="filter-group">
                <label class="filter-label" for="filtro-data-inicio">Data Início</label>
                <input type="date" id="filtro-data-inicio" class="filter-input" onchange="aplicarFiltros()">
            </div>
            <div class="filter-group">
                <label class="filter-label" for="filtro-data-fim">Data Fim</label>
                <input type="date" id="filtro-data-fim" class="filter-input" onchange="aplicarFiltros()">
            </div>
            <div class="filter-group">
                <label class="filter-label" for="filtro-instrutor">Instrutor</label>
                <select id="filtro-instrutor" class="filter-select" onchange="aplicarFiltros()">
                    <option value="">Todos os instrutores</option>
                    <?php foreach ($instrutores as $instrutor): ?>
                        <option value="<?= $instrutor['id'] ?>"><?= htmlspecialchars($instrutor['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="filtro-aluno">Aluno</label>
                <select id="filtro-aluno" class="filter-select" onchange="aplicarFiltros()">
                    <option value="">Todos os alunos</option>
                    <?php foreach ($alunos as $aluno): ?>
                        <option value="<?= $aluno['id'] ?>"><?= htmlspecialchars($aluno['nome']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="filtro-tipo">Tipo de Aula</label>
                <select id="filtro-tipo" class="filter-select" onchange="aplicarFiltros()">
                    <option value="">Todos os tipos</option>
                    <option value="teorica">Teórica</option>
                    <option value="pratica">Prática</option>
                </select>
            </div>
            <div class="filter-group">
                <label class="filter-label" for="filtro-status">Status</label>
                <select id="filtro-status" class="filter-select" onchange="aplicarFiltros()">
                    <option value="">Todos os status</option>
                    <option value="agendada">Agendada</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="concluida">Concluída</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            <div class="filter-group">
                <button class="btn btn-outline" onclick="limparFiltros()">
                    <i class="fas fa-times"></i>
                    Limpar Filtros
                </button>
            </div>
        </div>
    </section>

    <!-- Calendário -->
    <main class="agendamento-calendar">
        <div class="calendar-header">
            <h2 class="calendar-title" id="calendar-title">Calendário de Aulas</h2>
            <div class="calendar-nav">
                <button class="calendar-nav-btn" onclick="navegarMes('anterior')">
                    <i class="fas fa-chevron-left"></i>
                </button>
                <button class="calendar-nav-btn" onclick="navegarMes('hoje')">
                    Hoje
                </button>
                <button class="calendar-nav-btn" onclick="navegarMes('proximo')">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            <div class="calendar-view-toggle">
                <button class="view-toggle-btn active" onclick="alternarVista('calendario')">
                    <i class="fas fa-calendar"></i>
                    Calendário
                </button>
                <button class="view-toggle-btn" onclick="alternarVista('lista')">
                    <i class="fas fa-list"></i>
                    Lista
                </button>
            </div>
        </div>

        <!-- Vista de Calendário -->
        <div id="calendar-view" class="calendar-view">
            <div class="calendar-grid" id="calendar-grid">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>

        <!-- Vista de Lista -->
        <div id="list-view" class="list-view" style="display: none;">
            <div class="list-header">
                <h3 class="list-title">Aulas Agendadas</h3>
                <span class="list-count" id="list-count">0 aulas encontradas</span>
            </div>
            <div class="aulas-list" id="aulas-list">
                <!-- Será preenchido via JavaScript -->
            </div>
        </div>
    </main>

    <!-- Modal Nova Aula -->
    <div id="modal-nova-aula" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Nova Aula</h3>
                <button class="modal-close" onclick="fecharModal('modal-nova-aula')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-nova-aula" onsubmit="criarAula(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="tipo-aula">Tipo de Aula *</label>
                            <select id="tipo-aula" class="form-select" required onchange="atualizarCamposTipoAula()">
                                <option value="">Selecione o tipo</option>
                                <option value="teorica">Teórica</option>
                                <option value="pratica">Prática</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="disciplina">Disciplina</label>
                            <select id="disciplina" class="form-select" disabled>
                                <option value="">Selecione a disciplina</option>
                                <option value="legislacao">Legislação de Trânsito</option>
                                <option value="direcao_defensiva">Direção Defensiva</option>
                                <option value="primeiros_socorros">Primeiros Socorros</option>
                                <option value="meio_ambiente">Meio Ambiente</option>
                                <option value="mecanica_basica">Mecânica Básica</option>
                            </select>
                            <div class="form-help">Obrigatório apenas para aulas teóricas</div>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="aluno">Aluno *</label>
                            <select id="aluno" class="form-select" required onchange="verificarGuardsAluno()">
                                <option value="">Selecione o aluno</option>
                                <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?= $aluno['id'] ?>" 
                                            data-exame-medico="<?= $aluno['exame_medico'] ?? '' ?>"
                                            data-exame-psicologico="<?= $aluno['exame_psicologico'] ?? '' ?>"
                                            data-prova-teorica="<?= $aluno['resultado_prova_teorica'] ?? '' ?>">
                                        <?= htmlspecialchars($aluno['nome']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="aluno-guards" class="form-help"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="instrutor">Instrutor *</label>
                            <select id="instrutor" class="form-select" required onchange="filtrarVeiculos()">
                                <option value="">Selecione o instrutor</option>
                                <?php foreach ($instrutores as $instrutor): ?>
                                    <option value="<?= $instrutor['id'] ?>" 
                                            data-categorias="<?= htmlspecialchars($instrutor['categoria_habilitacao']) ?>">
                                        <?= htmlspecialchars($instrutor['nome']) ?> 
                                        (<?= htmlspecialchars($instrutor['categoria_habilitacao']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="veiculo">Veículo</label>
                            <select id="veiculo" class="form-select" disabled>
                                <option value="">Selecione o veículo</option>
                                <?php foreach ($veiculos as $veiculo): ?>
                                    <option value="<?= $veiculo['id'] ?>" 
                                            data-categoria="<?= $veiculo['categoria'] ?? '' ?>">
                                        <?= htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - ' . $veiculo['placa']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-help">Obrigatório apenas para aulas práticas</div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="cfc">CFC *</label>
                            <select id="cfc" class="form-select" required>
                                <option value="">Selecione o CFC</option>
                                <?php foreach ($cfcs as $cfc): ?>
                                    <option value="<?= $cfc['id'] ?>"><?= htmlspecialchars($cfc['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="data-aula">Data da Aula *</label>
                            <input type="date" id="data-aula" class="form-input" required onchange="verificarDisponibilidade()">
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="hora-inicio">Hora de Início *</label>
                            <input type="time" id="hora-inicio" class="form-input" required onchange="verificarDisponibilidade()">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="tipo-agendamento">Tipo de Agendamento</label>
                        <select id="tipo-agendamento" class="form-select" onchange="atualizarTipoAgendamento()">
                            <option value="unica">Aula Única (50 min)</option>
                            <option value="duas">2 Aulas Consecutivas (100 min)</option>
                            <option value="tres">3 Aulas com Intervalo (180 min)</option>
                        </select>
                    </div>

                    <div id="posicao-intervalo-group" class="form-group" style="display: none;">
                        <label class="form-label" for="posicao-intervalo">Posição do Intervalo</label>
                        <select id="posicao-intervalo" class="form-select">
                            <option value="depois">2 consecutivas + 30min + 1 aula</option>
                            <option value="antes">1 aula + 30min + 2 consecutivas</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="observacoes">Observações</label>
                        <textarea id="observacoes" class="form-textarea" rows="3" placeholder="Observações adicionais sobre a aula..."></textarea>
                    </div>

                    <div id="disponibilidade-check" class="form-group" style="display: none;">
                        <div class="form-help">
                            <i class="fas fa-info-circle"></i>
                            <span id="disponibilidade-text">Verificando disponibilidade...</span>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="fecharModal('modal-nova-aula')">
                    Cancelar
                </button>
                <button type="submit" form="form-nova-aula" class="btn btn-primary" id="btn-criar-aula">
                    <i class="fas fa-plus"></i>
                    Criar Aula
                </button>
            </div>
        </div>
    </div>

    <!-- Modal Verificar Disponibilidade -->
    <div id="modal-disponibilidade" class="modal-overlay" style="display: none;">
        <div class="modal">
            <div class="modal-header">
                <h3 class="modal-title">Verificar Disponibilidade</h3>
                <button class="modal-close" onclick="fecharModal('modal-disponibilidade')">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form id="form-disponibilidade" onsubmit="verificarDisponibilidadeCompleta(event)">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="disp-data">Data</label>
                            <input type="date" id="disp-data" class="form-input" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="disp-instrutor">Instrutor</label>
                            <select id="disp-instrutor" class="form-select" required>
                                <option value="">Selecione o instrutor</option>
                                <?php foreach ($instrutores as $instrutor): ?>
                                    <option value="<?= $instrutor['id'] ?>"><?= htmlspecialchars($instrutor['nome']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i>
                            Verificar Disponibilidade
                        </button>
                    </div>
                </form>
                <div id="disponibilidade-resultado" style="display: none;">
                    <!-- Resultado será exibido aqui -->
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="toast-container"></div>

    <script>
        // Variáveis globais
        let aulasData = [];
        let currentDate = new Date();
        let currentView = 'calendario';
        let filtrosAtivos = {};

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            carregarAulas();
            inicializarCalendario();
            definirDatasPadrao();
        });

        // Carregar aulas do servidor
        async function carregarAulas() {
            try {
                const response = await fetch('api/agendamento.php');
                const data = await response.json();
                
                if (data.success) {
                    aulasData = data.dados || [];
                    atualizarVisualizacao();
                } else {
                    mostrarToast('Erro ao carregar aulas: ' + data.mensagem, 'error');
                }
            } catch (error) {
                console.error('Erro ao carregar aulas:', error);
                mostrarToast('Erro ao carregar aulas', 'error');
            }
        }

        // Definir datas padrão dos filtros
        function definirDatasPadrao() {
            const hoje = new Date();
            const proximaSemana = new Date(hoje);
            proximaSemana.setDate(hoje.getDate() + 7);
            
            document.getElementById('filtro-data-inicio').value = hoje.toISOString().split('T')[0];
            document.getElementById('filtro-data-fim').value = proximaSemana.toISOString().split('T')[0];
        }

        // Aplicar filtros
        function aplicarFiltros() {
            filtrosAtivos = {
                dataInicio: document.getElementById('filtro-data-inicio').value,
                dataFim: document.getElementById('filtro-data-fim').value,
                instrutor: document.getElementById('filtro-instrutor').value,
                aluno: document.getElementById('filtro-aluno').value,
                tipo: document.getElementById('filtro-tipo').value,
                status: document.getElementById('filtro-status').value
            };
            
            atualizarVisualizacao();
        }

        // Limpar filtros
        function limparFiltros() {
            document.getElementById('filtro-data-inicio').value = '';
            document.getElementById('filtro-data-fim').value = '';
            document.getElementById('filtro-instrutor').value = '';
            document.getElementById('filtro-aluno').value = '';
            document.getElementById('filtro-tipo').value = '';
            document.getElementById('filtro-status').value = '';
            
            filtrosAtivos = {};
            atualizarVisualizacao();
        }

        // Filtrar aulas
        function filtrarAulas() {
            return aulasData.filter(aula => {
                // Filtro por data
                if (filtrosAtivos.dataInicio && aula.data_aula < filtrosAtivos.dataInicio) return false;
                if (filtrosAtivos.dataFim && aula.data_aula > filtrosAtivos.dataFim) return false;
                
                // Filtro por instrutor
                if (filtrosAtivos.instrutor && aula.instrutor_id != filtrosAtivos.instrutor) return false;
                
                // Filtro por aluno
                if (filtrosAtivos.aluno && aula.aluno_id != filtrosAtivos.aluno) return false;
                
                // Filtro por tipo
                if (filtrosAtivos.tipo && aula.tipo_aula != filtrosAtivos.tipo) return false;
                
                // Filtro por status
                if (filtrosAtivos.status && aula.status != filtrosAtivos.status) return false;
                
                return true;
            });
        }

        // Atualizar visualização
        function atualizarVisualizacao() {
            const aulasFiltradas = filtrarAulas();
            
            if (currentView === 'calendario') {
                atualizarCalendario(aulasFiltradas);
            } else {
                atualizarLista(aulasFiltradas);
            }
        }

        // Alternar vista
        function alternarVista(vista) {
            currentView = vista;
            
            // Atualizar botões
            document.querySelectorAll('.view-toggle-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            // Mostrar/ocultar vistas
            document.getElementById('calendar-view').style.display = vista === 'calendario' ? 'block' : 'none';
            document.getElementById('list-view').style.display = vista === 'lista' ? 'block' : 'none';
            
            atualizarVisualizacao();
        }

        // Navegar mês
        function navegarMes(direcao) {
            if (direcao === 'anterior') {
                currentDate.setMonth(currentDate.getMonth() - 1);
            } else if (direcao === 'proximo') {
                currentDate.setMonth(currentDate.getMonth() + 1);
            } else if (direcao === 'hoje') {
                currentDate = new Date();
            }
            
            atualizarCalendario(filtrarAulas());
        }

        // Inicializar calendário
        function inicializarCalendario() {
            atualizarCalendario(aulasData);
        }

        // Atualizar calendário
        function atualizarCalendario(aulas) {
            const calendarGrid = document.getElementById('calendar-grid');
            const calendarTitle = document.getElementById('calendar-title');
            
            // Atualizar título
            const mesAno = currentDate.toLocaleDateString('pt-BR', { 
                month: 'long', 
                year: 'numeric' 
            });
            calendarTitle.textContent = mesAno.charAt(0).toUpperCase() + mesAno.slice(1);
            
            // Limpar grid
            calendarGrid.innerHTML = '';
            
            // Cabeçalhos dos dias
            const diasSemana = ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'];
            diasSemana.forEach(dia => {
                const header = document.createElement('div');
                header.className = 'calendar-day-header';
                header.textContent = dia;
                calendarGrid.appendChild(header);
            });
            
            // Primeiro dia do mês
            const primeiroDia = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
            const ultimoDia = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
            const primeiroDiaSemana = primeiroDia.getDay();
            const diasNoMes = ultimoDia.getDate();
            
            // Dias do mês anterior
            const mesAnterior = new Date(currentDate.getFullYear(), currentDate.getMonth() - 1, 0);
            for (let i = primeiroDiaSemana - 1; i >= 0; i--) {
                const dia = document.createElement('div');
                dia.className = 'calendar-day other-month';
                dia.textContent = mesAnterior.getDate() - i;
                calendarGrid.appendChild(dia);
            }
            
            // Dias do mês atual
            const hoje = new Date();
            for (let dia = 1; dia <= diasNoMes; dia++) {
                const diaElement = document.createElement('div');
                diaElement.className = 'calendar-day';
                
                // Verificar se é hoje
                if (currentDate.getFullYear() === hoje.getFullYear() && 
                    currentDate.getMonth() === hoje.getMonth() && 
                    dia === hoje.getDate()) {
                    diaElement.classList.add('today');
                }
                
                // Número do dia
                const diaNumber = document.createElement('div');
                diaNumber.className = 'calendar-day-number';
                diaNumber.textContent = dia;
                diaElement.appendChild(diaNumber);
                
                // Aulas do dia
                const dataAtual = `${currentDate.getFullYear()}-${String(currentDate.getMonth() + 1).padStart(2, '0')}-${String(dia).padStart(2, '0')}`;
                const aulasDoDia = aulas.filter(aula => aula.data_aula === dataAtual);
                
                aulasDoDia.forEach(aula => {
                    const evento = document.createElement('div');
                    evento.className = `calendar-event ${aula.tipo_aula} ${aula.status}`;
                    evento.textContent = `${aula.hora_inicio.substring(0, 5)} - ${aula.aluno_nome}`;
                    evento.title = `${aula.tipo_aula.charAt(0).toUpperCase() + aula.tipo_aula.slice(1)} - ${aula.aluno_nome} com ${aula.instrutor_nome}`;
                    evento.onclick = () => abrirDetalhesAula(aula);
                    diaElement.appendChild(evento);
                });
                
                calendarGrid.appendChild(diaElement);
            }
            
            // Dias do próximo mês
            const diasRestantes = 42 - (primeiroDiaSemana + diasNoMes);
            for (let dia = 1; dia <= diasRestantes; dia++) {
                const diaElement = document.createElement('div');
                diaElement.className = 'calendar-day other-month';
                diaElement.textContent = dia;
                calendarGrid.appendChild(diaElement);
            }
        }

        // Atualizar lista
        function atualizarLista(aulas) {
            const aulasList = document.getElementById('aulas-list');
            const listCount = document.getElementById('list-count');
            
            listCount.textContent = `${aulas.length} aula${aulas.length !== 1 ? 's' : ''} encontrada${aulas.length !== 1 ? 's' : ''}`;
            
            aulasList.innerHTML = '';
            
            if (aulas.length === 0) {
                const emptyState = document.createElement('div');
                emptyState.className = 'empty-state';
                emptyState.innerHTML = `
                    <i class="fas fa-calendar-times"></i>
                    <h3>Nenhuma aula encontrada</h3>
                    <p>Não há aulas que correspondam aos filtros aplicados.</p>
                `;
                aulasList.appendChild(emptyState);
                return;
            }
            
            aulas.forEach(aula => {
                const aulaCard = document.createElement('div');
                aulaCard.className = 'aula-card';
                aulaCard.onclick = () => abrirDetalhesAula(aula);
                
                const dataFormatada = new Date(aula.data_aula).toLocaleDateString('pt-BR');
                const horaFormatada = aula.hora_inicio.substring(0, 5);
                
                aulaCard.innerHTML = `
                    <div class="aula-header">
                        <div class="aula-info">
                            <span class="aula-tipo ${aula.tipo_aula}">${aula.tipo_aula.charAt(0).toUpperCase() + aula.tipo_aula.slice(1)}</span>
                            <h4 class="aula-titulo">${aula.aluno_nome}</h4>
                        </div>
                        <span class="status-badge ${aula.status}">${aula.status.charAt(0).toUpperCase() + aula.status.slice(1)}</span>
                    </div>
                    <div class="aula-detalhes">
                        <div class="aula-detalhe">
                            <i class="fas fa-calendar"></i>
                            <span>${dataFormatada}</span>
                        </div>
                        <div class="aula-detalhe">
                            <i class="fas fa-clock"></i>
                            <span>${horaFormatada}</span>
                        </div>
                        <div class="aula-detalhe">
                            <i class="fas fa-user-tie"></i>
                            <span>${aula.instrutor_nome}</span>
                        </div>
                        ${aula.veiculo_id ? `
                        <div class="aula-detalhe">
                            <i class="fas fa-car"></i>
                            <span>${aula.marca} ${aula.modelo} - ${aula.placa}</span>
                        </div>
                        ` : ''}
                    </div>
                    <div class="aula-actions">
                        <button class="btn btn-outline btn-sm" onclick="event.stopPropagation(); editarAula(${aula.id})">
                            <i class="fas fa-edit"></i>
                            Editar
                        </button>
                        <button class="btn btn-danger btn-sm" onclick="event.stopPropagation(); cancelarAula(${aula.id})">
                            <i class="fas fa-times"></i>
                            Cancelar
                        </button>
                    </div>
                `;
                
                aulasList.appendChild(aulaCard);
            });
        }

        // Abrir modal nova aula
        function abrirModalNovaAula() {
            document.getElementById('modal-nova-aula').style.display = 'flex';
            document.getElementById('data-aula').value = new Date().toISOString().split('T')[0];
        }

        // Fechar modal
        function fecharModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Atualizar campos baseado no tipo de aula
        function atualizarCamposTipoAula() {
            const tipoAula = document.getElementById('tipo-aula').value;
            const disciplina = document.getElementById('disciplina');
            const veiculo = document.getElementById('veiculo');
            
            if (tipoAula === 'teorica') {
                disciplina.disabled = false;
                disciplina.required = true;
                veiculo.disabled = true;
                veiculo.required = false;
            } else if (tipoAula === 'pratica') {
                disciplina.disabled = true;
                disciplina.required = false;
                veiculo.disabled = false;
                veiculo.required = true;
            } else {
                disciplina.disabled = true;
                disciplina.required = false;
                veiculo.disabled = true;
                veiculo.required = false;
            }
        }

        // Verificar guards do aluno
        function verificarGuardsAluno() {
            const alunoSelect = document.getElementById('aluno');
            const alunoId = alunoSelect.value;
            const guardsDiv = document.getElementById('aluno-guards');
            
            if (!alunoId) {
                guardsDiv.innerHTML = '';
                return;
            }
            
            const option = alunoSelect.querySelector(`option[value="${alunoId}"]`);
            const exameMedico = option.dataset.exameMedico;
            const examePsicologico = option.dataset.examePsicologico;
            const provaTeorica = option.dataset.provaTeorica;
            
            let guards = [];
            
            if (exameMedico !== 'aprovado' && exameMedico !== 'apto') {
                guards.push('Exame médico não aprovado');
            }
            
            if (examePsicologico !== 'aprovado' && examePsicologico !== 'apto') {
                guards.push('Exame psicológico não aprovado');
            }
            
            if (provaTeorica !== 'aprovado' && provaTeorica !== 'apto') {
                guards.push('Prova teórica não aprovada');
            }
            
            if (guards.length > 0) {
                guardsDiv.innerHTML = `<span style="color: var(--danger-color);"><i class="fas fa-exclamation-triangle"></i> ${guards.join(', ')}</span>`;
            } else {
                guardsDiv.innerHTML = '<span style="color: var(--success-color);"><i class="fas fa-check"></i> Todos os requisitos atendidos</span>';
            }
        }

        // Filtrar veículos baseado no instrutor
        function filtrarVeiculos() {
            const instrutorSelect = document.getElementById('instrutor');
            const veiculoSelect = document.getElementById('veiculo');
            const instrutorId = instrutorSelect.value;
            
            if (!instrutorId) {
                veiculoSelect.innerHTML = '<option value="">Selecione o veículo</option>';
                return;
            }
            
            const option = instrutorSelect.querySelector(`option[value="${instrutorId}"]`);
            const categoriasInstrutor = option.dataset.categorias.toLowerCase();
            
            veiculoSelect.innerHTML = '<option value="">Selecione o veículo</option>';
            
            <?php foreach ($veiculos as $veiculo): ?>
                const categoriaVeiculo = '<?= strtolower($veiculo['categoria'] ?? '') ?>';
                if (categoriaVeiculo === '' || categoriasInstrutor.includes(categoriaVeiculo)) {
                    const option = document.createElement('option');
                    option.value = '<?= $veiculo['id'] ?>';
                    option.textContent = '<?= htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - ' . $veiculo['placa']) ?>';
                    veiculoSelect.appendChild(option);
                }
            <?php endforeach; ?>
        }

        // Atualizar tipo de agendamento
        function atualizarTipoAgendamento() {
            const tipoAgendamento = document.getElementById('tipo-agendamento').value;
            const posicaoIntervaloGroup = document.getElementById('posicao-intervalo-group');
            
            if (tipoAgendamento === 'tres') {
                posicaoIntervaloGroup.style.display = 'block';
            } else {
                posicaoIntervaloGroup.style.display = 'none';
            }
        }

        // Verificar disponibilidade
        function verificarDisponibilidade() {
            const dataAula = document.getElementById('data-aula').value;
            const horaInicio = document.getElementById('hora-inicio').value;
            const instrutorId = document.getElementById('instrutor').value;
            const veiculoId = document.getElementById('veiculo').value;
            
            if (!dataAula || !horaInicio || !instrutorId) {
                return;
            }
            
            const checkDiv = document.getElementById('disponibilidade-check');
            const textDiv = document.getElementById('disponibilidade-text');
            
            checkDiv.style.display = 'block';
            textDiv.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando disponibilidade...';
            
            // Simular verificação (implementar chamada real para API)
            setTimeout(() => {
                textDiv.innerHTML = '<i class="fas fa-check" style="color: var(--success-color);"></i> Horário disponível para agendamento';
            }, 1000);
        }

        // Criar aula
        async function criarAula(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const dados = Object.fromEntries(formData.entries());
            
            // Adicionar campos extras
            dados.acao = 'criar';
            dados.tipo_agendamento = document.getElementById('tipo-agendamento').value;
            dados.posicao_intervalo = document.getElementById('posicao-intervalo').value;
            
            try {
                const btnCriar = document.getElementById('btn-criar-aula');
                btnCriar.disabled = true;
                btnCriar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
                
                const response = await fetch('api/agendamento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarToast(result.mensagem, 'success');
                    fecharModal('modal-nova-aula');
                    event.target.reset();
                    carregarAulas();
                } else {
                    mostrarToast(result.mensagem, 'error');
                }
            } catch (error) {
                console.error('Erro ao criar aula:', error);
                mostrarToast('Erro ao criar aula', 'error');
            } finally {
                const btnCriar = document.getElementById('btn-criar-aula');
                btnCriar.disabled = false;
                btnCriar.innerHTML = '<i class="fas fa-plus"></i> Criar Aula';
            }
        }

        // Abrir detalhes da aula
        function abrirDetalhesAula(aula) {
            // Implementar modal de detalhes
            console.log('Detalhes da aula:', aula);
        }

        // Editar aula
        function editarAula(aulaId) {
            // Implementar edição
            console.log('Editar aula:', aulaId);
        }

        // Cancelar aula
        async function cancelarAula(aulaId) {
            if (!confirm('Tem certeza que deseja cancelar esta aula?')) {
                return;
            }
            
            try {
                const response = await fetch('api/agendamento.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        acao: 'cancelar',
                        aula_id: aulaId,
                        motivo: 'Cancelamento solicitado pelo usuário'
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    mostrarToast(result.mensagem, 'success');
                    carregarAulas();
                } else {
                    mostrarToast(result.mensagem, 'error');
                }
            } catch (error) {
                console.error('Erro ao cancelar aula:', error);
                mostrarToast('Erro ao cancelar aula', 'error');
            }
        }

        // Mostrar toast
        function mostrarToast(mensagem, tipo = 'info') {
            const toastContainer = document.getElementById('toast-container');
            const toast = document.createElement('div');
            toast.className = `toast ${tipo}`;
            
            const icon = tipo === 'success' ? 'check-circle' : 
                       tipo === 'error' ? 'exclamation-circle' : 
                       tipo === 'warning' ? 'exclamation-triangle' : 'info-circle';
            
            toast.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${mensagem}</span>
            `;
            
            toastContainer.appendChild(toast);
            
            // Remover após 5 segundos
            setTimeout(() => {
                toast.remove();
            }, 5000);
        }

        // Exportar agenda
        function exportarAgenda() {
            // Implementar exportação
            console.log('Exportar agenda');
        }

        // Abrir modal disponibilidade
        function abrirModalDisponibilidade() {
            document.getElementById('modal-disponibilidade').style.display = 'flex';
        }

        // Verificar disponibilidade completa
        function verificarDisponibilidadeCompleta(event) {
            event.preventDefault();
            // Implementar verificação completa
            console.log('Verificar disponibilidade completa');
        }
    </script>
</body>
</html>
