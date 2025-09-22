<?php
/**
 * Calendário de Aulas - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências






$db = Database::getInstance();
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;

// Verificar permissões
$canView = ($userType === 'admin' || $userType === 'instrutor');
if (!$canView) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Buscar aulas para o calendário
try {
    $aulas_calendario = $db->fetchAll("
        SELECT ta.*, t.nome as turma_nome, t.status as turma_status,
               i.nome as instrutor_nome, c.nome as cfc_nome
        FROM turma_aulas ta
        JOIN turmas t ON ta.turma_id = t.id
        LEFT JOIN instrutores i ON t.instrutor_id = i.id
        LEFT JOIN cfcs c ON t.cfc_id = c.id
        WHERE t.tipo_aula = 'teorica'
        ORDER BY ta.data_aula, ta.hora_inicio
    ");
} catch (Exception $e) {
    $aulas_calendario = [];
}
?>





    <div class="calendar-container">
        <div class="container-fluid">
            <!-- Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-calendar-alt me-3"></i>
                            Calendário de Aulas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Visualização das aulas teóricas agendadas
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            <a href="?page=turma-calendario" class="btn btn-light btn-sm">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                            <a href="?page=turma-calendario" class="btn btn-light btn-sm">
                                <i class="fas fa-list"></i> Lista de Turmas
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtros -->
            <div class="filters-card">
                <div class="row align-items-end">
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Turma:</label>
                        <select class="form-select" id="filtroTurma">
                            <option value="">Todas as turmas</option>
                            <?php foreach ($turmas as $turma): ?>
                                <option value="<?= $turma['id'] ?>">
                                    <?= htmlspecialchars($turma['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Instrutor:</label>
                        <select class="form-select" id="filtroInstrutor">
                            <option value="">Todos os instrutores</option>
                            <?php foreach ($instrutores as $instrutor): ?>
                                <option value="<?= $instrutor['id'] ?>">
                                    <?= htmlspecialchars($instrutor['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold">Status:</label>
                        <select class="form-select" id="filtroStatus">
                            <option value="">Todos os status</option>
                            <option value="agendada">Agendada</option>
                            <option value="realizada">Realizada</option>
                            <option value="cancelada">Cancelada</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button class="btn btn-primary w-100" onclick="aplicarFiltros()">
                            <i class="fas fa-filter"></i> Aplicar Filtros
                        </button>
                    </div>
                </div>
            </div>

            <!-- Legenda -->
            <div class="calendar-card">
                <h6 class="mb-3">
                    <i class="fas fa-info-circle text-primary"></i>
                    Legenda
                </h6>
                <div class="legend-item">
                    <div class="legend-color legend-ativa"></div>
                    <span>Turma Ativa</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color legend-agendada"></div>
                    <span>Turma Agendada</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color legend-concluida"></div>
                    <span>Turma Concluída</span>
                </div>
            </div>

            <!-- Calendário -->
            <div class="calendar-card">
                <div id="calendar"></div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- FullCalendar JS -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
    <!-- FullCalendar Locale -->
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/pt-br.global.min.js"></script>
    
    <script>
        let calendar;
        let eventos = <?= json_encode($aulas_calendario) ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'pt-br',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventos.map(evento => ({
                    id: evento.id,
                    title: `${evento.nome_aula} - ${evento.turma_nome}`,
                    start: `${evento.data_aula}T${evento.hora_inicio}`,
                    end: `${evento.data_aula}T${evento.hora_fim}`,
                    className: `event-turma-${evento.turma_status}`,
                    extendedProps: {
                        turma: evento.turma_nome,
                        instrutor: evento.instrutor_nome,
                        cfc: evento.cfc_nome,
                        duracao: evento.duracao_minutos,
                        status: evento.status
                    }
                })),
                eventClick: function(info) {
                    mostrarDetalhesEvento(info.event);
                },
                eventDidMount: function(info) {
                    // Adicionar tooltip
                    info.el.title = `${info.event.title}\nInstrutor: ${info.event.extendedProps.instrutor}\nDuração: ${info.event.extendedProps.duracao} min`;
                }
            });
            
            calendar.render();
        });
        
        function aplicarFiltros() {
            const filtroTurma = document.getElementById('filtroTurma').value;
            const filtroInstrutor = document.getElementById('filtroInstrutor').value;
            const filtroStatus = document.getElementById('filtroStatus').value;
            
            let eventosFiltrados = eventos;
            
            if (filtroTurma) {
                eventosFiltrados = eventosFiltrados.filter(e => e.turma_id == filtroTurma);
            }
            
            if (filtroInstrutor) {
                eventosFiltrados = eventosFiltrados.filter(e => e.instrutor_id == filtroInstrutor);
            }
            
            if (filtroStatus) {
                eventosFiltrados = eventosFiltrados.filter(e => e.status === filtroStatus);
            }
            
            calendar.removeAllEvents();
            calendar.addEventSource(eventosFiltrados.map(evento => ({
                id: evento.id,
                title: `${evento.nome_aula} - ${evento.turma_nome}`,
                start: `${evento.data_aula}T${evento.hora_inicio}`,
                end: `${evento.data_aula}T${evento.hora_fim}`,
                className: `event-turma-${evento.turma_status}`,
                extendedProps: {
                    turma: evento.turma_nome,
                    instrutor: evento.instrutor_nome,
                    cfc: evento.cfc_nome,
                    duracao: evento.duracao_minutos,
                    status: evento.status
                }
            })));
        }
        
        function mostrarDetalhesEvento(evento) {
            const props = evento.extendedProps;
            const modal = `
                <div class="modal fade" id="eventoModal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Detalhes da Aula</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Aula:</strong><br>
                                        ${evento.title}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Data/Hora:</strong><br>
                                        ${evento.start.toLocaleDateString('pt-BR')} - ${evento.start.toLocaleTimeString('pt-BR', {hour: '2-digit', minute: '2-digit'})}
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Instrutor:</strong><br>
                                        ${props.instrutor || 'Não definido'}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Duração:</strong><br>
                                        ${props.duracao} minutos
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>CFC:</strong><br>
                                        ${props.cfc || 'Não definido'}
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Status:</strong><br>
                                        <span class="badge bg-${props.status === 'realizada' ? 'success' : (props.status === 'cancelada' ? 'danger' : 'warning')}">
                                            ${props.status === 'realizada' ? 'Realizada' : (props.status === 'cancelada' ? 'Cancelada' : 'Agendada')}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                                <a href="?page=turma-calendario" class="btn btn-primary">
                                    <i class="fas fa-clipboard-check"></i> Chamada
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Remover modal anterior se existir
            const modalAnterior = document.getElementById('eventoModal');
            if (modalAnterior) {
                modalAnterior.remove();
            }
            
            // Adicionar novo modal
            document.body.insertAdjacentHTML('beforeend', modal);
            
            // Mostrar modal
            const modalElement = new bootstrap.Modal(document.getElementById('eventoModal'));
            modalElement.show();
        }
    </script>


