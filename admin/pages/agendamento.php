<?php
// =====================================================
// SISTEMA DE AGENDAMENTO - SISTEMA CFC
// Baseado no design do econdutor para mesma experiência
// =====================================================

// Verificar se as variáveis estão definidas
if (!isset($aulas)) $aulas = [];
if (!isset($instrutores)) $instrutores = [];
if (!isset($veiculos)) $veiculos = [];
if (!isset($alunos)) $alunos = [];
if (!isset($cfcs)) $cfcs = [];

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
    
    // Buscar aulas existentes para o calendário
    $aulas = $db->fetchAll("
        SELECT a.*, 
               al.nome as aluno_nome,
               COALESCE(u.nome, i.nome) as instrutor_nome,
               v.placa, v.modelo, v.marca
        FROM aulas a
        JOIN alunos al ON a.aluno_id = al.id
        JOIN instrutores i ON a.instrutor_id = i.id
        JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.data_aula >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ORDER BY a.data_aula, a.hora_inicio
    ");
    
} catch (Exception $e) {
    if (LOG_ENABLED) {
        error_log('Erro ao carregar dados de agendamento: ' . $e->getMessage());
    }
    $instrutores = [];
    $veiculos = [];
    $alunos = [];
    $cfcs = [];
    $aulas = [];
}
?>

<!-- Header da Página -->
<div class="page-header">
    <div>
        <h1 class="page-title">Sistema de Agendamento</h1>
        <p class="page-subtitle">Gerencie aulas, instrutores e veículos</p>
    </div>
    <div class="page-actions">
        <button class="btn btn-primary" onclick="abrirModalNovaAula()">
            <i class="fas fa-plus"></i>
            Nova Aula
        </button>
        <button class="btn btn-success" onclick="abrirModalDisponibilidade()">
            <i class="fas fa-calendar-check"></i>
            Verificar Disponibilidade
        </button>
        <button class="btn btn-info" onclick="exportarAgenda()">
            <i class="fas fa-download"></i>
            Exportar Agenda
        </button>
    </div>
</div>

<!-- Filtros de Agendamento -->
<div class="filters-section">
    <div class="filters-grid">
        <div class="filter-group">
            <label for="filter-cfc">CFC:</label>
            <select id="filter-cfc" onchange="filtrarAgenda()">
                <option value="">Todos os CFCs</option>
                <?php foreach ($cfcs ?? [] as $cfc): ?>
                    <option value="<?php echo $cfc['id']; ?>"><?php echo htmlspecialchars($cfc['nome']); ?></option>
                <?php endforeach; ?>
            </select>
            <script>
                console.log('CFCs carregados:', <?php echo json_encode($cfcs ?? []); ?>);
                console.log('Dropdown CFC:', document.getElementById('filter-cfc'));
            </script>
        </div>
        
        <div class="filter-group">
            <label for="filter-instrutor">Instrutor:</label>
            <select id="filter-instrutor" onchange="filtrarAgenda()">
                <option value="">Todos os Instrutores</option>
                <?php foreach ($instrutores as $instrutor): ?>
                    <option value="<?php echo $instrutor['id']; ?>"><?php echo htmlspecialchars($instrutor['nome']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-tipo">Tipo de Aula:</label>
            <select id="filter-tipo" onchange="filtrarAgenda()">
                <option value="">Todas as Aulas</option>
                <option value="teorica">Teórica</option>
                <option value="pratica">Prática</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label for="filter-status">Status:</label>
            <select id="filter-status" onchange="filtrarAgenda()">
                <option value="">Todos os Status</option>
                <option value="agendada">Agendada</option>
                <option value="em_andamento">Em Andamento</option>
                <option value="concluida">Concluída</option>
                <option value="cancelada">Cancelada</option>
            </select>
        </div>
    </div>
</div>

<!-- Calendário Principal -->
<div class="calendar-section">
    <div class="calendar-header">
        <div class="calendar-navigation">
            <button class="btn btn-outline-secondary" onclick="calendario.previous()">
                <i class="fas fa-chevron-left"></i>
                Anterior
            </button>
            <h3 id="calendar-title" class="calendar-title">Calendário de Aulas</h3>
            <button class="btn btn-outline-secondary" onclick="calendario.next()">
                Próximo
                <i class="fas fa-chevron-right"></i>
            </button>
        </div>
        
        <div class="calendar-views">
            <button class="btn btn-sm btn-outline-primary active" onclick="mudarVisualizacao('dayGridMonth')">Mês</button>
            <button class="btn btn-sm btn-outline-primary" onclick="mudarVisualizacao('timeGridWeek')">Semana</button>
            <button class="btn btn-sm btn-outline-primary" onclick="mudarVisualizacao('timeGridDay')">Dia</button>
            <button class="btn btn-sm btn-outline-primary" onclick="mudarVisualizacao('listWeek')">Lista</button>
        </div>
    </div>
    
    <div id="calendar" class="calendar-container"></div>
</div>

<!-- Estatísticas Rápidas -->
<div class="stats-section">
    <div class="stats-grid">
        <div class="stat-card primary">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="aulas-hoje">0</div>
                <div class="stat-label">Aulas Hoje</div>
            </div>
        </div>
        
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-calendar-week"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="aulas-semana">0</div>
                <div class="stat-label">Aulas Esta Semana</div>
            </div>
        </div>
        
        <div class="stat-card warning">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="aulas-pendentes">0</div>
                <div class="stat-label">Aulas Pendentes</div>
            </div>
        </div>
        
        <div class="stat-card info">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <div class="stat-value" id="instrutores-disponiveis">0</div>
                <div class="stat-label">Instrutores Disponíveis</div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Nova Aula -->
<div id="modal-nova-aula" class="modal-overlay" style="display: none;">
    <div class="modal-content modal-large">
        <div class="modal-header">
            <h3>Nova Aula</h3>
            <button class="modal-close" onclick="fecharModalNovaAula()">×</button>
        </div>
        
        <form id="form-nova-aula" class="modal-form" onsubmit="salvarNovaAula(event)">
            <!-- Seleção de Tipo de Agendamento -->
            <div class="form-section">
                <label class="form-label fw-bold">Tipo de Agendamento:</label>
                <div class="d-flex gap-3 mb-3">
                    <div class="form-check custom-radio">
                        <input class="form-check-input" type="radio" name="tipo_agendamento" id="modal_aula_unica" value="unica" checked>
                        <label class="form-check-label" for="modal_aula_unica">
                            <div class="radio-text">
                                <strong>1 Aula</strong>
                                <small>50 minutos</small>
                            </div>
                        </label>
                    </div>
                    <div class="form-check custom-radio">
                        <input class="form-check-input" type="radio" name="tipo_agendamento" id="modal_duas_aulas" value="duas">
                        <label class="form-check-label" for="modal_duas_aulas">
                            <div class="radio-text">
                                <strong>2 Aulas</strong>
                                <small>1h 40min</small>
                            </div>
                        </label>
                    </div>
                    <div class="form-check custom-radio">
                        <input class="form-check-input" type="radio" name="tipo_agendamento" id="modal_tres_aulas" value="tres">
                        <label class="form-check-label" for="modal_tres_aulas">
                            <div class="radio-text">
                                <strong>3 Aulas</strong>
                                <small>2h 30min</small>
                            </div>
                        </label>
                    </div>
                </div>
                
                <!-- Opções para 3 aulas -->
                <div id="modal_opcoesTresAulas" class="mb-3" style="display: none;">
                    <label class="form-label fw-bold">Posição do Intervalo:</label>
                    <div class="d-flex gap-3">
                        <div class="form-check custom-radio">
                            <input class="form-check-input" type="radio" name="posicao_intervalo" id="modal_intervalo_depois" value="depois" checked>
                            <label class="form-check-label" for="modal_intervalo_depois">
                                <div class="radio-text">
                                    <strong>2 consecutivas + intervalo + 1 aula</strong>
                                    <small>Primeiro bloco, depois intervalo</small>
                                </div>
                            </label>
                        </div>
                        <div class="form-check custom-radio">
                            <input class="form-check-input" type="radio" name="posicao_intervalo" id="modal_intervalo_antes" value="antes">
                            <label class="form-check-label" for="modal_intervalo_antes">
                                <div class="radio-text">
                                    <strong>1 aula + intervalo + 2 consecutivas</strong>
                                    <small>Primeira aula, depois intervalo</small>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>
                
                <small class="form-text text-muted">
                    <i class="fas fa-info-circle me-1"></i>
                    <strong>2 aulas:</strong> Consecutivas (1h 40min) | <strong>3 aulas:</strong> Escolha a posição do intervalo de 30min (2h 30min total)
                </small>
            </div>
            
            <!-- Horários Calculados Automaticamente -->
            <div id="modal_horariosCalculados" class="mb-3" style="display: none;">
                <label class="form-label fw-bold">Horários Calculados:</label>
                <div class="row">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title text-primary">1ª Aula</h6>
                                <div id="modal_hora1" class="fw-bold">--:--</div>
                                <small class="text-muted">50 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" id="modal_coluna2" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title text-success">2ª Aula</h6>
                                <div id="modal_hora2" class="fw-bold">--:--</div>
                                <small class="text-muted">50 min</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4" id="modal_coluna3" style="display: none;">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6 class="card-title text-warning">3ª Aula</h6>
                                <div id="modal_hora3" class="fw-bold">--:--</div>
                                <small class="text-muted">50 min</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="modal_intervaloInfo" class="mt-2 text-center" style="display: none;">
                    <span class="badge bg-info">
                        <i class="fas fa-clock me-1"></i>Intervalo de 30 minutos entre blocos de aulas
                    </span>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="aluno_id">Aluno *</label>
                    <select id="aluno_id" name="aluno_id" required>
                        <option value="">Selecione o aluno</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>">
                                <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo $aluno['categoria_cnh']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="instrutor_id">Instrutor *</label>
                    <select id="instrutor_id" name="instrutor_id" required>
                        <option value="">Selecione o instrutor</option>
                        <?php foreach ($instrutores as $instrutor): ?>
                            <option value="<?php echo $instrutor['id']; ?>">
                                <?php echo htmlspecialchars($instrutor['nome']); ?> - <?php echo htmlspecialchars($instrutor['categoria_habilitacao']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="tipo_aula">Tipo de Aula *</label>
                    <select id="tipo_aula" name="tipo_aula" required>
                        <option value="">Selecione o tipo</option>
                        <option value="teorica">Teórica</option>
                        <option value="pratica">Prática</option>
                        <option value="simulador">Simulador</option>
                        <option value="avaliacao">Avaliação</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="veiculo_id">Veículo</label>
                    <select id="veiculo_id" name="veiculo_id">
                        <option value="">Apenas para aulas práticas</option>
                        <?php foreach ($veiculos as $veiculo): ?>
                            <option value="<?php echo $veiculo['id']; ?>" data-categoria="<?php echo $veiculo['categoria_cnh']; ?>">
                                <?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - ' . $veiculo['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <!-- Campo Disciplina - Visível apenas para aulas teóricas -->
            <div id="modal_campo_disciplina" class="form-group" style="display: none;">
                <label for="disciplina">Disciplina *</label>
                <select id="disciplina" name="disciplina">
                    <option value="">Selecione a disciplina...</option>
                    <option value="legislacao_transito">Legislação de Trânsito</option>
                    <option value="direcao_defensiva">Direção Defensiva</option>
                    <option value="primeiros_socorros">Primeiros Socorros</option>
                    <option value="meio_ambiente">Meio Ambiente e Cidadania</option>
                    <option value="mecanica_basica">Mecânica Básica</option>
                    <option value="sinalizacao">Sinalização de Trânsito</option>
                    <option value="etica_profissional">Ética Profissional</option>
                </select>
                <small class="form-text text-muted">Disciplina específica da aula teórica</small>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_aula">Data da Aula *</label>
                    <input type="date" id="data_aula" name="data_aula" required min="<?php echo date('Y-m-d'); ?>" onchange="modalCalcularHorarios()">
                </div>
                
                <div class="form-group">
                    <label for="hora_inicio">Hora de Início *</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required onchange="modalCalcularHorarios()">
                </div>
                
                <div class="form-group">
                    <label for="duracao">Duração da Aula *</label>
                    <div class="form-control-plaintext bg-light border rounded p-2">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        <strong>50 minutos</strong>
                        <small class="text-muted ms-2">(duração fixa)</small>
                    </div>
                    <input type="hidden" id="duracao" name="duracao" value="50">
                </div>
            </div>
            
            <div class="form-group">
                <label for="observacoes">Observações</label>
                <textarea id="observacoes" name="observacoes" rows="3" placeholder="Observações sobre a aula..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModalNovaAula()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Salvar Aula
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar Aula -->
<div id="modal-editar-aula" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Editar Aula</h3>
            <button class="modal-close" onclick="fecharModalEditarAula()">×</button>
        </div>
        
        <form id="form-editar-aula" class="modal-form" onsubmit="atualizarAula(event)">
            <input type="hidden" id="edit_aula_id" name="aula_id">
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_aluno_id">Aluno *</label>
                    <select id="edit_aluno_id" name="aluno_id" required>
                        <option value="">Selecione o aluno</option>
                        <?php foreach ($alunos as $aluno): ?>
                            <option value="<?php echo $aluno['id']; ?>">
                                <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo $aluno['categoria_cnh']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_instrutor_id">Instrutor *</label>
                    <select id="edit_instrutor_id" name="instrutor_id" required>
                        <option value="">Selecione o instrutor</option>
                        <?php foreach ($instrutores as $instrutor): ?>
                            <option value="<?php echo $instrutor['id']; ?>">
                                <?php echo htmlspecialchars($instrutor['nome']); ?> - <?php echo $instrutor['categoria_habilitacao']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_tipo_aula">Tipo de Aula *</label>
                    <select id="edit_tipo_aula" name="tipo_aula" required>
                        <option value="">Selecione o tipo</option>
                        <option value="teorica">Teórica</option>
                        <option value="pratica">Prática</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="edit_veiculo_id">Veículo</label>
                    <select id="edit_veiculo_id" name="veiculo_id">
                        <option value="">Apenas para aulas práticas</option>
                        <?php foreach ($veiculos as $veiculo): ?>
                            <option value="<?php echo $veiculo['id']; ?>">
                                <?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo'] . ' - ' . $veiculo['placa']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="edit_data_aula">Data da Aula *</label>
                    <input type="date" id="edit_data_aula" name="data_aula" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_hora_inicio">Hora de Início *</label>
                    <input type="time" id="edit_hora_inicio" name="hora_inicio" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_hora_fim">Hora de Fim *</label>
                    <input type="time" id="edit_hora_fim" name="hora_fim" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="edit_status">Status</label>
                <select id="edit_status" name="status">
                    <option value="agendada">Agendada</option>
                    <option value="em_andamento">Em Andamento</option>
                    <option value="concluida">Concluída</option>
                    <option value="cancelada">Cancelada</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="edit_observacoes">Observações</label>
                <textarea id="edit_observacoes" name="observacoes" rows="3" placeholder="Observações sobre a aula..."></textarea>
            </div>
            
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="fecharModalEditarAula()">Cancelar</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i>
                    Atualizar Aula
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal de Confirmação -->
<div id="modal-confirmacao" class="modal-overlay" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Confirmação</h3>
            <button class="modal-close" onclick="fecharModalConfirmacao()">×</button>
        </div>
        
        <div class="modal-body">
            <p id="confirmacao-mensagem">Tem certeza que deseja realizar esta ação?</p>
        </div>
        
        <div class="modal-actions">
            <button class="btn btn-secondary" onclick="fecharModalConfirmacao()">Cancelar</button>
            <button id="btn-confirmar" class="btn btn-danger">Confirmar</button>
        </div>
    </div>
</div>

<!-- CSS específico para o modal de agendamento -->
<style>
    /* Modal maior para agendamento */
    .modal-large {
        max-width: 800px;
        width: 90%;
    }
    
    /* Radio buttons personalizados para melhor visibilidade */
    .custom-radio {
        margin-bottom: 0;
    }
    
    .custom-radio .form-check-input {
        width: 20px;
        height: 20px;
        margin-top: 0;
        border: 3px solid #dee2e6;
        background-color: white;
        cursor: pointer;
        transition: all 0.3s ease;
    }
    
    .custom-radio .form-check-input:checked {
        background-color: #007bff;
        border-color: #007bff;
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        transform: scale(1.1);
    }
    
    .custom-radio .form-check-input:focus {
        box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.25);
        border-color: #007bff;
    }
    
    .custom-radio .form-check-label {
        cursor: pointer;
        padding: 8px 0;
        margin-left: 8px;
    }
    
    .radio-text {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    
    .radio-text strong {
        color: #495057;
        font-size: 14px;
        line-height: 1.2;
    }
    
    .radio-text small {
        color: #6c757d;
        font-size: 12px;
        line-height: 1.2;
    }
    
    .custom-radio .form-check-input:checked + .form-check-label .radio-text strong {
        color: #007bff;
        font-weight: 600;
    }
    
    /* Hover effects */
    .custom-radio:hover .form-check-input:not(:checked) {
        border-color: #adb5bd;
        transform: scale(1.05);
    }
    
    /* Form section */
    .form-section {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 20px;
        border-left: 4px solid #007bff;
    }
</style>

<!-- Scripts específicos do agendamento -->
<script>
const aulasData = <?php echo json_encode($aulas); ?>;
const instrutoresData = <?php echo json_encode($instrutores); ?>;
const veiculosData = <?php echo json_encode($veiculos); ?>;
const alunosData = <?php echo json_encode($alunos); ?>;

// Inicializar calendário quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    inicializarCalendario();
    atualizarEstatisticas();
});

// Funções do sistema de agendamento
function inicializarCalendario() {
    // Implementação do FullCalendar será adicionada aqui
    console.log('Calendário inicializado');
}

function abrirModalNovaAula() {
    document.getElementById('modal-nova-aula').style.display = 'flex';
    limparFormularioNovaAula();
}

function fecharModalNovaAula() {
    document.getElementById('modal-nova-aula').style.display = 'none';
}

function abrirModalEditarAula(aulaId) {
    // Carregar dados da aula para edição
    const aula = aulasData.find(a => a.id == aulaId);
    if (aula) {
        preencherFormularioEdicao(aula);
        document.getElementById('modal-editar-aula').style.display = 'flex';
    }
}

function fecharModalEditarAula() {
    document.getElementById('modal-editar-aula').style.display = 'none';
}

function abrirModalConfirmacao(mensagem, acao) {
    document.getElementById('confirmacao-mensagem').textContent = mensagem;
    document.getElementById('btn-confirmar').onclick = acao;
    document.getElementById('modal-confirmacao').style.display = 'flex';
}

function fecharModalConfirmacao() {
    document.getElementById('modal-confirmacao').style.display = 'none';
}

function salvarNovaAula(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    
    // Adicionar tipo de agendamento
    const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked').value;
    formData.append('tipo_agendamento', tipoAgendamento);
    
    // Adicionar disciplina se for aula teórica
    const tipoAula = document.getElementById('tipo_aula').value;
    if (tipoAula === 'teorica') {
        const disciplina = document.getElementById('disciplina').value;
        if (disciplina) {
            formData.append('disciplina', disciplina);
        }
    }
    
    // Adicionar posição do intervalo se for 3 aulas
    if (tipoAgendamento === 'tres') {
        const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked').value;
        formData.append('posicao_intervalo', posicaoIntervalo);
    }
    
    // Mostrar loading
    const btnSubmit = event.target.querySelector('button[type="submit"]');
    const textoOriginal = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Agendando...';
    btnSubmit.disabled = true;
    
    fetch('api/agendamento.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Sucesso
    notifications.success('Aula agendada com sucesso!');
    fecharModalNovaAula();
    // Recarregar calendário
            if (typeof recarregarCalendario === 'function') {
                recarregarCalendario();
            }
        } else {
            // Erro
            notifications.error('Erro ao agendar aula: ' + data.mensagem);
            
            // Reativar botão
            btnSubmit.innerHTML = textoOriginal;
            btnSubmit.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        notifications.error('Erro ao agendar aula. Tente novamente.');
        
        // Reativar botão
        btnSubmit.innerHTML = textoOriginal;
        btnSubmit.disabled = false;
    });
}

function atualizarAula(event) {
    event.preventDefault();
    
    // Implementar lógica de atualização
    notifications.success('Aula atualizada com sucesso!');
    fecharModalEditarAula();
    // Recarregar calendário
}

function verificarDisponibilidade() {
    // Implementar verificação de disponibilidade
    console.log('Verificando disponibilidade...');
}

function verificarDisponibilidadeInstrutor() {
    // Implementar verificação de disponibilidade do instrutor
    console.log('Verificando disponibilidade do instrutor...');
}

function verificarDisponibilidadeVeiculo() {
    // Implementar verificação de disponibilidade do veículo
    console.log('Verificando disponibilidade do veículo...');
}

function atualizarEstatisticas() {
    // Implementar atualização das estatísticas
    const hoje = new Date();
    const inicioSemana = new Date(hoje);
    inicioSemana.setDate(hoje.getDate() - hoje.getDay());
    
    // Contar aulas de hoje
    const aulasHoje = aulasData.filter(aula => {
        const dataAula = new Date(aula.data_aula);
        return dataAula.toDateString() === hoje.toDateString();
    }).length;
    
    // Contar aulas da semana
    const aulasSemana = aulasData.filter(aula => {
        const dataAula = new Date(aula.data_aula);
        return dataAula >= inicioSemana && dataAula <= hoje;
    }).length;
    
    // Contar aulas pendentes
    const aulasPendentes = aulasData.filter(aula => aula.status === 'agendada').length;
    
    // Contar instrutores disponíveis
    const instrutoresDisponiveis = instrutoresData.filter(instrutor => instrutor.ativo == 1).length;
    
    // Atualizar elementos na página
    const aulasHojeEl = document.getElementById('aulas-hoje');
    const aulasSemanaEl = document.getElementById('aulas-semana');
    const aulasPendentesEl = document.getElementById('aulas-pendentes');
    const instrutoresDisponiveisEl = document.getElementById('instrutores-disponiveis');
    
    if (aulasHojeEl) aulasHojeEl.textContent = aulasHoje;
    if (aulasSemanaEl) aulasSemanaEl.textContent = aulasSemana;
    if (aulasPendentesEl) aulasPendentesEl.textContent = aulasPendentes;
    if (instrutoresDisponiveisEl) instrutoresDisponiveisEl.textContent = instrutoresDisponiveis;
}

function calcularHoraFim() {
    const horaInicio = document.getElementById('hora_inicio').value;
    if (horaInicio) {
        // Calcular hora de fim (padrão: 1 hora de aula)
        const hora = new Date(`2000-01-01T${horaInicio}`);
        hora.setHours(hora.getHours() + 1);
        const horaFim = hora.toTimeString().slice(0, 5);
        document.getElementById('hora_fim').value = horaFim;
    }
}

function filtrarAgenda() {
    // Obter valores dos filtros
    const cfcId = document.getElementById('filter-cfc').value;
    const instrutorId = document.getElementById('filter-instrutor').value;
    const tipoAula = document.getElementById('filter-tipo').value;
    const status = document.getElementById('filter-status').value;
    
    console.log('Aplicando filtros:', {
        cfc: cfcId,
        instrutor: instrutorId,
        tipo: tipoAula,
        status: status
    });
    
    // Por enquanto, apenas log dos filtros
    // TODO: Implementar filtragem real do calendário
}

function mudarVisualizacao(view) {
    // Implementar mudança de visualização do calendário
    console.log('Mudando para visualização:', view);
}

function exportarAgenda() {
    // Implementar exportação da agenda
    notifications.info('Exportando agenda...');
}

// Funções específicas para o modal de agendamento avançado
function modalCalcularHorarios() {
    const tipoAgendamento = document.querySelector('input[name="tipo_agendamento"]:checked').value;
    const data = document.getElementById('data_aula').value;
    const horaInicio = document.getElementById('hora_inicio').value;
    
    if (!data || !horaInicio) {
        document.getElementById('modal_horariosCalculados').style.display = 'none';
        return;
    }
    
    // Converter hora de início para minutos
    const [horas, minutos] = horaInicio.split(':').map(Number);
    let inicioMinutos = horas * 60 + minutos;
    
    // Elementos do modal
    const horariosCalculados = document.getElementById('modal_horariosCalculados');
    const coluna2 = document.getElementById('modal_coluna2');
    const coluna3 = document.getElementById('modal_coluna3');
    const intervaloInfo = document.getElementById('modal_intervaloInfo');
    const hora1 = document.getElementById('modal_hora1');
    const hora2 = document.getElementById('modal_hora2');
    const hora3 = document.getElementById('modal_hora3');
    
    // Calcular horários baseados no tipo
    switch (tipoAgendamento) {
        case 'unica':
            // 1 aula: 50 minutos
            const fim1 = inicioMinutos + 50;
            hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor(fim1/60).toString().padStart(2,'0')}:${(fim1%60).toString().padStart(2,'0')}`;
            
            coluna2.style.display = 'none';
            coluna3.style.display = 'none';
            intervaloInfo.style.display = 'none';
            horariosCalculados.style.display = 'block';
            break;
            
        case 'duas':
            // 2 aulas consecutivas: 50 + 50 = 100 minutos
            const fim2 = inicioMinutos + 100;
            hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')}`;
            hora2.textContent = `${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')} - ${Math.floor(fim2/60).toString().padStart(2,'0')}:${(fim2%60).toString().padStart(2,'0')}`;
            
            coluna2.style.display = 'block';
            coluna3.style.display = 'none';
            intervaloInfo.style.display = 'none';
            horariosCalculados.style.display = 'block';
            break;
            
        case 'tres':
            // 3 aulas com intervalo de 30min = 180 minutos total
            const fim3 = inicioMinutos + 180;
            const posicaoIntervalo = document.querySelector('input[name="posicao_intervalo"]:checked').value;
            
            if (posicaoIntervalo === 'depois') {
                // 2 consecutivas + 30min intervalo + 1 aula
                hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')}`;
                hora2.textContent = `${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+100)/60).toString().padStart(2,'0')}:${((inicioMinutos+100)%60).toString().padStart(2,'0')}`;
                hora3.textContent = `${Math.floor((inicioMinutos+130)/60).toString().padStart(2,'0')}:${((inicioMinutos+130)%60).toString().padStart(2,'0')} - ${Math.floor(fim3/60).toString().padStart(2,'0')}:${(fim3%60).toString().padStart(2,'0')}`;
            } else {
                // 1 aula + 30min intervalo + 2 consecutivas
                hora1.textContent = `${Math.floor(inicioMinutos/60).toString().padStart(2,'0')}:${(inicioMinutos%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+50)/60).toString().padStart(2,'0')}:${((inicioMinutos+50)%60).toString().padStart(2,'0')}`;
                hora2.textContent = `${Math.floor((inicioMinutos+80)/60).toString().padStart(2,'0')}:${((inicioMinutos+80)%60).toString().padStart(2,'0')} - ${Math.floor((inicioMinutos+130)/60).toString().padStart(2,'0')}:${((inicioMinutos+130)%60).toString().padStart(2,'0')}`;
                hora3.textContent = `${Math.floor((inicioMinutos+130)/60).toString().padStart(2,'0')}:${((inicioMinutos+130)%60).toString().padStart(2,'0')} - ${Math.floor(fim3/60).toString().padStart(2,'0')}:${(fim3%60).toString().padStart(2,'0')}`;
            }
            
            coluna2.style.display = 'block';
            coluna3.style.display = 'block';
            intervaloInfo.style.display = 'block';
            horariosCalculados.style.display = 'block';
            break;
    }
}

// Event listeners para o modal
document.addEventListener('DOMContentLoaded', function() {
    // Event listeners para tipo de agendamento
    document.querySelectorAll('input[name="tipo_agendamento"]').forEach(radio => {
        radio.addEventListener('change', function() {
            // Mostrar/ocultar opções de intervalo para 3 aulas
            const opcoesTresAulas = document.getElementById('modal_opcoesTresAulas');
            if (this.value === 'tres') {
                opcoesTresAulas.style.display = 'block';
            } else {
                opcoesTresAulas.style.display = 'none';
            }
            modalCalcularHorarios();
        });
    });
    
    // Event listeners para posição do intervalo
    document.querySelectorAll('input[name="posicao_intervalo"]').forEach(radio => {
        radio.addEventListener('change', modalCalcularHorarios);
    });
    
    // Event listener para tipo de aula
    document.getElementById('tipo_aula').addEventListener('change', function() {
        const campoDisciplina = document.getElementById('modal_campo_disciplina');
        const disciplina = document.getElementById('disciplina');
        const veiculo = document.getElementById('veiculo_id');
        
        if (this.value === 'teorica') {
            // Aula teórica: mostrar disciplina, ocultar veículo
            campoDisciplina.style.display = 'block';
            disciplina.required = true;
            disciplina.disabled = false;
            
            veiculo.required = false;
            veiculo.disabled = true;
            veiculo.value = '';
        } else {
            // Aula prática: ocultar disciplina, mostrar veículo
            campoDisciplina.style.display = 'none';
            disciplina.required = false;
            disciplina.disabled = true;
            disciplina.value = '';
            
            veiculo.required = true;
            veiculo.disabled = false;
        }
    });
});

function limparFormularioNovaAula() {
    document.getElementById('form-nova-aula').reset();
    document.getElementById('modal_opcoesTresAulas').style.display = 'none';
    document.getElementById('modal_horariosCalculados').style.display = 'none';
    document.getElementById('modal_campo_disciplina').style.display = 'none';
    
    // Resetar radio buttons
    document.getElementById('modal_aula_unica').checked = true;
    document.getElementById('modal_intervalo_depois').checked = true;
    
    // Desabilitar veículo por padrão
    document.getElementById('veiculo_id').disabled = true;
    document.getElementById('veiculo_id').required = false;
}

function preencherFormularioEdicao(aula) {
    document.getElementById('edit_aula_id').value = aula.id;
    document.getElementById('edit_aluno_id').value = aula.aluno_id;
    document.getElementById('edit_instrutor_id').value = aula.instrutor_id;
    document.getElementById('edit_tipo_aula').value = aula.tipo_aula;
    document.getElementById('edit_veiculo_id').value = aula.veiculo_id || '';
    document.getElementById('edit_data_aula').value = aula.data_aula;
    document.getElementById('edit_hora_inicio').value = aula.hora_inicio;
    document.getElementById('edit_hora_fim').value = aula.hora_fim;
    document.getElementById('edit_status').value = aula.status;
    document.getElementById('edit_observacoes').value = aula.observacoes || '';
}

// Habilitar/desabilitar campo veículo baseado no tipo de aula
document.getElementById('tipo_aula').addEventListener('change', function() {
    const veiculoField = document.getElementById('veiculo_id');
    if (this.value === 'pratica') {
        veiculoField.disabled = false;
        veiculoField.required = true;
    } else {
        veiculoField.disabled = true;
        veiculoField.required = false;
        veiculoField.value = '';
    }
});
</script>
