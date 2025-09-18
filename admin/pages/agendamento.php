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
    
    // Buscar aulas existentes para o calendário (últimos 6 meses e próximos 6 meses)
    $aulas = $db->fetchAll("
        SELECT a.*, 
               al.nome as aluno_nome,
               COALESCE(u.nome, i.nome) as instrutor_nome,
               v.placa, v.modelo, v.marca
        FROM aulas a
        JOIN alunos al ON a.aluno_id = al.id
        JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.data_aula >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
          AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
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
    <div class="header-content">
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

<!-- Legenda Visual -->
<div class="legend-section">
    <div class="legend-title">
        <i class="fas fa-info-circle me-2"></i>
        Legenda dos Eventos
    </div>
    <div class="legend-items">
        <div class="legend-item">
            <div class="legend-color teorica"></div>
            <span>📚 Aula Teórica</span>
        </div>
        <div class="legend-item">
            <div class="legend-color pratica"></div>
            <span>🚗 Aula Prática</span>
        </div>
        <div class="legend-item">
            <div class="legend-color agendada"></div>
            <span>⏰ Agendada</span>
        </div>
        <div class="legend-item">
            <div class="legend-color concluida"></div>
            <span>✅ Concluída</span>
        </div>
        <div class="legend-item">
            <div class="legend-color em_andamento"></div>
            <span>🔄 Em Andamento</span>
        </div>
</div>

<!-- Calendário Principal -->
<div class="calendar-section">
    <div class="calendar-header">
        <div class="calendar-navigation">
            <button class="btn btn-outline-secondary" onclick="navegarCalendario('previous')">
                <i class="fas fa-chevron-left"></i>
                Anterior
            </button>
            <h3 id="calendar-title" class="calendar-title">Calendário de Aulas</h3>
            <button class="btn btn-outline-secondary" onclick="navegarCalendario('next')">
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
    
    /* Estilos para o calendário */
    .calendar-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .calendar-title {
        font-size: 1.5rem;
        font-weight: 600;
        color: #2c3e50;
        margin: 0;
    }
    
    /* Estilos para eventos do calendário - Experiência Google Calendar */
    .fc-event {
        border-radius: 6px !important;
        font-size: 0.8rem !important;
        padding: 3px 6px !important;
        margin: 1px 0 !important;
        border: none !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2) !important;
        transition: all 0.2s ease !important;
        font-weight: 500 !important;
        line-height: 1.2 !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
        white-space: nowrap !important;
        cursor: pointer !important;
    }
    
    .fc-event:hover {
        transform: translateY(-1px) !important;
        box-shadow: 0 2px 6px rgba(0,0,0,0.3) !important;
        z-index: 10 !important;
    }
    
    /* Estilos específicos por tipo de aula */
    .event-teorica {
        background: linear-gradient(135deg, #3498db, #2980b9) !important;
        border-left: 4px solid #2980b9 !important;
    }
    
    .event-pratica {
        background: linear-gradient(135deg, #e74c3c, #c0392b) !important;
        border-left: 4px solid #c0392b !important;
    }
    
    /* Estilos específicos por status */
    .event-agendada {
        opacity: 1 !important;
        animation: pulse 2s infinite !important;
    }
    
    .event-concluida {
        opacity: 0.8 !important;
        background: linear-gradient(135deg, #27ae60, #229954) !important;
    }
    
    .event-em_andamento {
        background: linear-gradient(135deg, #f39c12, #e67e22) !important;
        animation: pulse 1.5s infinite !important;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.7; }
        100% { opacity: 1; }
    }
    
    /* Melhorar aparência dos dias do calendário */
    .fc-daygrid-day {
        border-color: #e9ecef !important;
        min-height: 100px !important;
    }
    
    .fc-daygrid-day-number {
        font-weight: 600;
        color: #495057;
        font-size: 0.9rem;
        padding: 4px 6px;
    }
    
    .fc-day-today {
        background-color: #fff3cd !important;
        border: 2px solid #ffc107 !important;
    }
    
    .fc-day-today .fc-daygrid-day-number {
        background-color: #ffc107;
        color: white;
        border-radius: 50%;
        width: 28px;
        height: 28px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 0.85rem;
    }
    
    /* Cabeçalho do calendário mais claro */
    .fc-col-header-cell {
        background-color: #f8f9fa !important;
        font-weight: 700;
        color: #495057;
        border-color: #dee2e6 !important;
        font-size: 0.85rem;
        padding: 8px 4px;
    }
    
    /* Melhorar espaçamento dos eventos */
    .fc-daygrid-event {
        margin: 1px 0 !important;
        border-radius: 4px !important;
    }
    
    /* Indicador de mais eventos */
    .fc-more-link {
        background-color: #6c757d !important;
        color: white !important;
        border-radius: 4px !important;
        font-size: 0.75rem !important;
        padding: 2px 6px !important;
        font-weight: 600 !important;
    }
    
    .fc-more-link:hover {
        background-color: #5a6268 !important;
    }
    
    /* Popover para eventos extras */
    .fc-popover {
        border-radius: 8px !important;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15) !important;
        border: none !important;
    }
    
    .fc-popover-header {
        background-color: #f8f9fa !important;
        border-radius: 8px 8px 0 0 !important;
        font-weight: 600 !important;
        color: #495057 !important;
    }
    
    /* Melhorar responsividade */
    @media (max-width: 768px) {
        .fc-event {
            font-size: 0.7rem !important;
            padding: 2px 4px !important;
        }
        
        .fc-daygrid-day {
            min-height: 80px !important;
        }
        
        .fc-daygrid-day-number {
            font-size: 0.8rem;
        }
    }
    
    /* Estilos para modal de detalhes */
    .modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
    }
    
    .modal-content {
        background: white;
        border-radius: 10px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        max-width: 600px;
        width: 90%;
        max-height: 90vh;
        overflow-y: auto;
    }
    
    .modal-large {
        max-width: 1000px;
    }
    
    .modal-header {
        padding: 20px;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background-color: #f8f9fa;
        border-radius: 10px 10px 0 0;
    }
    
    .modal-header h3 {
        margin: 0;
        color: #2c3e50;
        font-weight: 600;
    }
    
    .modal-close {
        background: none;
        border: none;
        font-size: 1.5rem;
        color: #6c757d;
        cursor: pointer;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: all 0.2s ease;
    }
    
    .modal-close:hover {
        background-color: #e9ecef;
        color: #495057;
    }
    
    .modal-body {
        padding: 20px;
    }
    
    .modal-actions {
        padding: 20px;
        border-top: 1px solid #dee2e6;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
        background-color: #f8f9fa;
        border-radius: 0 0 10px 10px;
    }
    
        /* Estilos para tooltips melhorados */
        .event-tooltip {
            background: rgba(0, 0, 0, 0.95);
            color: white;
            padding: 12px;
            border-radius: 8px;
            font-size: 0.85rem;
            line-height: 1.4;
            max-width: 280px;
            word-wrap: break-word;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }
        
        
        #modalDisponibilidade .form-label {
            font-weight: 600 !important;
            margin-bottom: 0.5rem !important;
        }
        
        #modalDisponibilidade .form-control,
        #modalDisponibilidade .form-select {
            padding: 0.75rem !important;
            font-size: 1rem !important;
            border-radius: 8px !important;
        }
        
        #modalDisponibilidade .btn-primary {
            padding: 0.875rem 1.5rem !important;
            font-size: 1.1rem !important;
            font-weight: 600 !important;
            border-radius: 8px !important;
        }
        
        /* Estilos para os resultados - Acessibilidade melhorada */
        #modalDisponibilidade .card-header {
            font-weight: 600 !important;
            font-size: 1rem !important;
            padding: 1rem 1.25rem !important;
            border-bottom: 2px solid rgba(255,255,255,0.2) !important;
        }
        
        #modalDisponibilidade .card-header.bg-success {
            background-color: #198754 !important;
            color: #ffffff !important;
        }
        
        #modalDisponibilidade .card-header.bg-danger {
            background-color: #dc3545 !important;
            color: #ffffff !important;
        }
        
        #modalDisponibilidade .list-group-item {
            padding: 1rem 1.25rem !important;
            margin-bottom: 0 !important;
            border-radius: 0 !important;
            border: none !important;
            border-bottom: 1px solid #dee2e6 !important;
        }
        
        #modalDisponibilidade .list-group-item:last-child {
            border-bottom: none !important;
        }
        
        #modalDisponibilidade .list-group-item-success {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
        
        #modalDisponibilidade .list-group-item-danger {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
        
        #modalDisponibilidade .list-group-item-warning {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
        
        #modalDisponibilidade .list-group-item-secondary {
            background-color: #f8f9fa !important;
            color: #6c757d !important;
        }
        
        #modalDisponibilidade .badge {
            font-size: 0.75rem !important;
            padding: 0.4rem 0.6rem !important;
            border-radius: 4px !important;
            font-weight: 600 !important;
        }
        
        #modalDisponibilidade .text-success {
            color: #198754 !important;
        }
        
        #modalDisponibilidade .text-danger {
            color: #dc3545 !important;
        }
        
        #modalDisponibilidade .text-warning {
            color: #fd7e14 !important;
        }
        
        /* Responsividade para tablets */
        @media (max-width: 992px) {
            #modalDisponibilidade .modal-dialog {
                max-width: 900px !important;
                width: 90% !important;
            }
            
            #modalDisponibilidade .modal-body {
                padding: 1.5rem !important;
            }
        }
        
        /* Responsividade para mobile */
        @media (max-width: 768px) {
            #modalDisponibilidade .modal-dialog {
                margin: 0.5rem !important;
                width: calc(100% - 1rem) !important;
                max-width: none !important;
            }
            
            #modalDisponibilidade .modal-dialog-centered {
                min-height: calc(100vh - 1rem) !important;
            }
            
            #modalDisponibilidade .modal-body {
                padding: 1rem !important;
            }
            
            #modalDisponibilidade .form-control,
            #modalDisponibilidade .form-select {
                padding: 0.625rem !important;
                font-size: 0.95rem !important;
            }
            
            #modalDisponibilidade .btn-primary {
                padding: 0.75rem 1.25rem !important;
                font-size: 1rem !important;
            }
            
            #modalDisponibilidade .list-group-item {
                padding: 0.75rem !important;
            }
            
            #modalDisponibilidade .card-header {
                padding: 0.75rem 1rem !important;
                font-size: 0.9rem !important;
            }
        }
        
        /* Responsividade para mobile pequeno */
        @media (max-width: 576px) {
            #modalDisponibilidade .modal-body {
                padding: 0.75rem !important;
            }
            
            #modalDisponibilidade .row {
                margin-left: -0.5rem !important;
                margin-right: -0.5rem !important;
            }
            
            #modalDisponibilidade .col-md-6 {
                padding-left: 0.5rem !important;
                padding-right: 0.5rem !important;
            }
        }
    
    .tooltip-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
        padding-bottom: 6px;
        border-bottom: 1px solid rgba(255,255,255,0.2);
    }
    
    .tooltip-type {
        background: rgba(255,255,255,0.2);
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 0.75rem;
    }
    
    .tooltip-content {
        margin-bottom: 8px;
    }
    
    .tooltip-row {
        margin-bottom: 4px;
        display: flex;
        align-items: flex-start;
    }
    
    .tooltip-row strong {
        min-width: 70px;
        margin-right: 8px;
        color: #f8f9fa;
    }
    
    .tooltip-footer {
        padding-top: 6px;
        border-top: 1px solid rgba(255,255,255,0.2);
        text-align: center;
        color: #adb5bd;
    }
    
    .status-agendada {
        color: #ffc107;
        font-weight: 600;
    }
    
    .status-concluida {
        color: #28a745;
        font-weight: 600;
    }
    
    .status-em_andamento {
        color: #17a2b8;
        font-weight: 600;
    }
    
    .status-cancelada {
        color: #dc3545;
        font-weight: 600;
    }
    
    /* Estilos para legenda */
    .legend-section {
        background: white;
        border-radius: 10px;
        padding: 15px 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        border-left: 4px solid #007bff;
    }
    
    .legend-title {
        font-weight: 600;
        color: #495057;
        margin-bottom: 12px;
        font-size: 0.9rem;
    }
    
    .legend-items {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.85rem;
        color: #6c757d;
    }
    
    .legend-color {
        width: 16px;
        height: 16px;
        border-radius: 4px;
        border: 2px solid rgba(0,0,0,0.1);
    }
    
    .legend-color.teorica {
        background: linear-gradient(135deg, #3498db, #2980b9);
    }
    
    .legend-color.pratica {
        background: linear-gradient(135deg, #e74c3c, #c0392b);
    }
    
    .legend-color.agendada {
        background: linear-gradient(135deg, #f39c12, #e67e22);
    }
    
    .legend-color.concluida {
        background: linear-gradient(135deg, #27ae60, #229954);
    }
    
    .legend-color.em_andamento {
        background: linear-gradient(135deg, #f39c12, #e67e22);
    }
    
    /* Melhorar aparência dos filtros */
    .filters-section {
        background: white;
        border-radius: 10px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .filter-group {
        margin-bottom: 15px;
    }
    
    .filter-group label {
        font-weight: 600;
        color: #495057;
        margin-bottom: 5px;
        display: block;
    }
    
    .filter-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 6px;
        background-color: white;
        transition: border-color 0.2s ease;
    }
    
    .filter-group select:focus {
        border-color: #007bff;
        outline: none;
        box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
    }
</style>

<!-- Incluir FullCalendar -->
<link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/locales/pt-br.global.min.js"></script>

<!-- JavaScript do agendamento integrado na página -->

<!-- Scripts específicos do agendamento -->
<script>
const aulasData = <?php echo json_encode($aulas); ?>;
const instrutoresData = <?php echo json_encode($instrutores); ?>;
const veiculosData = <?php echo json_encode($veiculos); ?>;
const alunosData = <?php echo json_encode($alunos); ?>;
const cfcsData = <?php echo json_encode($cfcs); ?>;

// Debug: verificar dados carregados
console.log('=== DEBUG AGENDAMENTO ===');
console.log('Total de aulas carregadas:', aulasData.length);
console.log('Aulas carregadas:', aulasData);
console.log('Total de instrutores:', instrutoresData.length);
console.log('Total de veículos:', veiculosData.length);
console.log('Total de alunos:', alunosData.length);

// Inicializar calendário quando a página carregar
document.addEventListener('DOMContentLoaded', function() {
    preencherFiltros();
    inicializarCalendario();
    atualizarEstatisticas();
});

function preencherFiltros() {
    console.log('Preenchendo filtros...');
    
    // Preencher filtro de instrutores
    const filtroInstrutor = document.getElementById('filter-instrutor');
    if (filtroInstrutor && instrutoresData) {
        filtroInstrutor.innerHTML = '<option value="">Todos os Instrutores</option>';
        instrutoresData.forEach(instrutor => {
            const option = document.createElement('option');
            option.value = instrutor.id;
            option.textContent = instrutor.nome;
            filtroInstrutor.appendChild(option);
        });
    }
    
    // Preencher filtro de CFCs
    const filtroCfc = document.getElementById('filter-cfc');
    if (filtroCfc && cfcsData) {
        filtroCfc.innerHTML = '<option value="">Todos os CFCs</option>';
        cfcsData.forEach(cfc => {
            const option = document.createElement('option');
            option.value = cfc.id;
            option.textContent = cfc.nome;
            filtroCfc.appendChild(option);
        });
    }
    
    console.log('Filtros preenchidos');
}

// Funções do sistema de agendamento
function inicializarCalendario() {
    const calendarEl = document.getElementById('calendar');
    if (!calendarEl) {
        console.error('Elemento calendar não encontrado');
        return;
    }

    console.log('Inicializando calendário com', aulasData.length, 'aulas');

    // Configuração do FullCalendar
    const calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'pt-br',
        headerToolbar: false, // Usamos nossa própria toolbar
        height: 'auto',
        expandRows: true,
        dayMaxEvents: 4, // Máximo de 4 eventos por dia para melhor visualização
        selectable: true,
        selectMirror: true,
        editable: false, // Desabilitar edição por drag & drop por enquanto
        eventTimeFormat: {
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        },
        eventDisplay: 'block',
        eventTextColor: '#ffffff',
        eventBorderColor: 'transparent',
        dayMaxEventRows: 4, // Máximo de 4 linhas de eventos por dia
        moreLinkClick: 'popover', // Mostrar popover para eventos extras
        dayMaxEvents: 4, // Limitar eventos visíveis por dia
        // Configurações de localização específicas
        buttonText: {
            today: 'Hoje',
            month: 'Mês',
            week: 'Semana',
            day: 'Dia',
            list: 'Lista'
        },
        // Traduzir texto "more" para português
        moreLinkText: function(num) {
            return '+ ' + num + ' mais';
        },
        events: function(info, successCallback, failureCallback) {
            console.log('Carregando eventos para período:', info.start, 'até', info.end);
            
            // Filtrar aulas para o período solicitado
            let eventosFiltrados = aulasData.filter(aula => {
                const dataAula = new Date(aula.data_aula);
                return dataAula >= info.start && dataAula <= info.end;
            });
            
            console.log('Eventos filtrados para período:', eventosFiltrados.length);
            
            // Aplicar filtros ativos se existirem
            if (window.filtrosAtivos) {
                const filtros = window.filtrosAtivos;
                
                if (filtros.instrutor) {
                    eventosFiltrados = eventosFiltrados.filter(aula => aula.instrutor_id == filtros.instrutor);
                }
                
                if (filtros.tipo) {
                    eventosFiltrados = eventosFiltrados.filter(aula => aula.tipo_aula === filtros.tipo);
                }
                
                if (filtros.status) {
                    eventosFiltrados = eventosFiltrados.filter(aula => aula.status === filtros.status);
                }
                
                console.log('Eventos após aplicar filtros:', eventosFiltrados.length);
            } else {
                // Se não há filtros ativos, mostrar apenas aulas não canceladas por padrão
                eventosFiltrados = eventosFiltrados.filter(aula => aula.status !== 'cancelada');
                console.log('Eventos filtrados (apenas ativos por padrão):', eventosFiltrados.length);
            }
            
            // Converter para formato do FullCalendar
            const eventos = eventosFiltrados.map(aula => formatarEvento(aula));
            console.log('Eventos formatados:', eventos);
            
            successCallback(eventos);
        },
        select: (info) => {
            console.log('Data selecionada:', info.startStr);
            abrirModalNovaAula(info.startStr);
        },
        eventClick: (info) => {
            console.log('Evento clicado:', info.event.id);
            exibirDetalhesAula(info.event);
        },
        eventDidMount: (info) => {
            // Adicionar tooltip ao evento
            adicionarTooltipEvento(info.event, info.el);
        }
    });

    calendar.render();
    
    // Armazenar referência globalmente para uso nos filtros
    window.calendar = calendar;
    
    // Atualizar título do calendário
    atualizarTituloCalendario();
    
    console.log('Calendário inicializado com sucesso');
}

function formatarEvento(aula) {
    const cores = {
        teorica: '#3498db',
        pratica: '#e74c3c',
        agendada: '#f39c12',
        'em_andamento': '#3498db',
        concluida: '#27ae60',
        cancelada: '#95a5a6'
    };

    // Formatar horário para exibição resumida
    const horaInicio = aula.hora_inicio.substring(0, 5); // HH:MM
    const horaFim = aula.hora_fim.substring(0, 5); // HH:MM
    const tipoAulaTexto = aula.tipo_aula === 'teorica' ? '📚' : '🚗';
    const statusIcon = aula.status === 'agendada' ? '⏰' : 
                      aula.status === 'concluida' ? '✅' : 
                      aula.status === 'em_andamento' ? '🔄' : '❌';
    
    // Título resumido para melhor visualização
    const nomeResumido = aula.aluno_nome.split(' ').slice(0, 2).join(' ');
    const tituloResumido = `${horaInicio}-${horaFim} ${tipoAulaTexto} ${nomeResumido}`;
    
    return {
        id: aula.id,
        title: tituloResumido,
        start: `${aula.data_aula}T${aula.hora_inicio}`,
        end: `${aula.data_aula}T${aula.hora_fim}`,
        backgroundColor: cores[aula.tipo_aula] || cores[aula.status],
        borderColor: cores[aula.tipo_aula] || cores[aula.status],
        textColor: '#ffffff',
        display: 'block',
        classNames: [`event-${aula.tipo_aula}`, `event-${aula.status}`],
        extendedProps: {
            tipo_aula: aula.tipo_aula,
            status: aula.status,
            aluno_id: aula.aluno_id,
            aluno_nome: aula.aluno_nome,
            instrutor_id: aula.instrutor_id,
            instrutor_nome: aula.instrutor_nome,
            veiculo_id: aula.veiculo_id,
            observacoes: aula.observacoes,
            placa: aula.placa,
            modelo: aula.modelo,
            marca: aula.marca,
            disciplina: aula.disciplina,
            hora_inicio: horaInicio,
            status_icon: statusIcon
        }
    };
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
    
    fetch('API_CONFIG.getRelativeApiUrl('AGENDAMENTO')', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Tratar resposta HTTP 409 (Conflict) especificamente
        if (response.status === 409) {
            return response.text().then(text => {
                try {
                    const errorData = JSON.parse(text);
                    throw new Error(`CONFLITO: ${errorData.mensagem || 'Conflito de agendamento detectado'}`);
                } catch (e) {
                    throw new Error('CONFLITO: Veículo ou instrutor já possui aula agendada neste horário');
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
                console.error('❌ Erro ao fazer parse do JSON:', e);
                console.error('📄 Texto que causou erro:', text);
                throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
            }
        });
    })
    .then(data => {
        console.log('Resposta da API:', data);
        
        if (data.success) {
            // Sucesso
            alert(data.mensagem || 'Aula agendada com sucesso!');
            fecharModalNovaAula();
            
            // Recarregar calendário para mostrar dados atualizados
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
            
            // Recarregar página após um pequeno delay para garantir que os dados sejam atualizados
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Erro
            alert('Erro ao agendar aula: ' + (data.mensagem || 'Erro desconhecido'));
            
            // Reativar botão
            btnSubmit.innerHTML = textoOriginal;
            btnSubmit.disabled = false;
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        
        // Verificar se é erro de conflito específico
        if (error.message.startsWith('CONFLITO:')) {
            const mensagemConflito = error.message.replace('CONFLITO: ', '');
            alert(`⚠️ ATENÇÃO: ${mensagemConflito}`);
        } else {
            alert('Erro ao agendar aula. Tente novamente.');
        }
        
        // Reativar botão
        btnSubmit.innerHTML = textoOriginal;
        btnSubmit.disabled = false;
    });
}

function atualizarAula(event) {
    event.preventDefault();
    
    const form = event.target;
    const formData = new FormData(form);
    
    // Mostrar loading
    const btnSubmit = form.querySelector('button[type="submit"]');
    const textoOriginal = btnSubmit.innerHTML;
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Atualizando...';
    btnSubmit.disabled = true;
    
    // Converter FormData para objeto
    const data = {};
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    // Debug: log dos dados que serão enviados
    console.log('Dados do FormData:', data);
    console.log('FormData entries:', Array.from(formData.entries()));
    
    // Mapear campos para o formato esperado pela API
    const mappedData = {
        acao: 'editar',
        aula_id: data.aula_id,
        edit_aluno_id: data.aluno_id,
        edit_instrutor_id: data.instrutor_id,
        edit_veiculo_id: data.veiculo_id,
        edit_data_aula: data.data_aula,
        edit_hora_inicio: data.hora_inicio,
        edit_hora_fim: data.hora_fim,
        edit_tipo_aula: data.tipo_aula,
        edit_observacoes: data.observacoes || ''
    };
    
    console.log('Dados mapeados para API:', mappedData);
    
    fetch('API_CONFIG.getRelativeApiUrl('AGENDAMENTO')', {
        method: 'POST',
        credentials: 'include',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(mappedData)
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('❌ Erro ao fazer parse do JSON:', e);
                console.error('📄 Texto que causou erro:', text);
                throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
            }
        });
    })
    .then(result => {
        if (result.sucesso) {
            alert('Aula atualizada com sucesso!');
            fecharModalEditarAula();
            
            // Recarregar calendário para mostrar dados atualizados
            if (window.calendar) {
                window.calendar.refetchEvents();
            }
            
            // Recarregar página após um pequeno delay para garantir que os dados sejam atualizados
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            alert('Erro ao atualizar aula: ' + (result.mensagem || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao atualizar aula. Tente novamente.');
    })
    .finally(() => {
        // Reativar botão
        btnSubmit.innerHTML = textoOriginal;
        btnSubmit.disabled = false;
    });
}

        function abrirModalDisponibilidade() {
            // Remover modal existente se houver
            const modalExistente = document.getElementById('modalDisponibilidade');
            if (modalExistente) {
                modalExistente.remove();
            }
            
            // Criar modal de verificação de disponibilidade seguindo o padrão do modal de detalhes
            const modalHtml = `
                <div id="modalDisponibilidade" class="modal-overlay" style="display: flex;">
                    <div class="modal-content modal-large">
                        <div class="modal-header">
                            <h3>
                                <i class="fas fa-search me-2"></i>Verificar Disponibilidade
                            </h3>
                            <button class="modal-close" onclick="fecharModalDisponibilidade()">×</button>
                        </div>
                        <div class="modal-body">
                            <!-- Seção de Filtros -->
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-filter me-2"></i>Filtros de Consulta
                                    </h6>
                                    <div class="mb-3">
                                        <label for="disp-data" class="form-label fw-bold">Data da Aula:</label>
                                        <input type="date" class="form-control" id="disp-data" value="${new Date().toISOString().split('T')[0]}">
                                    </div>
                                    <div class="mb-3">
                                        <label for="disp-instrutor" class="form-label fw-bold">Instrutor:</label>
                                        <select class="form-select" id="disp-instrutor">
                                            <option value="">Todos os Instrutores</option>
                                            ${instrutoresData.map(instrutor => 
                                                `<option value="${instrutor.id}">${instrutor.nome}</option>`
                                            ).join('')}
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-cog me-2"></i>Configurações
                                    </h6>
                                    <div class="mb-3">
                                        <label for="disp-tipo" class="form-label fw-bold">Tipo de Aula:</label>
                                        <select class="form-select" id="disp-tipo">
                                            <option value="">Todos os Tipos</option>
                                            <option value="teorica">Teórica</option>
                                            <option value="pratica">Prática</option>
                                        </select>
                                    </div>
                                    <div class="mb-3">
                                        <label for="disp-duracao" class="form-label fw-bold">Duração da Aula:</label>
                                        <select class="form-select" id="disp-duracao">
                                            <option value="50">50 minutos (1 aula)</option>
                                            <option value="100">100 minutos (2 aulas)</option>
                                            <option value="180">180 minutos (3 aulas)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
                                <button class="btn btn-primary btn-lg" onclick="consultarDisponibilidade()">
                                    <i class="fas fa-search me-2"></i>Verificar Disponibilidade
                                </button>
                            </div>
                            
                            <!-- Seção de Resultados -->
                            <div id="resultado-disponibilidade" class="results-section" style="display: none;">
                                <hr class="my-4">
                                <h6 class="text-primary mb-4">
                                    <i class="fas fa-calendar-check me-2"></i>Resultados da Consulta
                                </h6>
                                <div id="conteudo-resultado"></div>
                            </div>
                        </div>
                        <div class="modal-actions">
                            <button class="btn btn-secondary" onclick="fecharModalDisponibilidade()">Fechar</button>
                        </div>
                    </div>
                </div>
            `;
            
            // Adicionar modal ao body
            document.body.insertAdjacentHTML('beforeend', modalHtml);
        }

        function fecharModalDisponibilidade() {
            const modal = document.getElementById('modalDisponibilidade');
            if (modal) {
                modal.remove();
            }
        }

async function consultarDisponibilidade() {
    const data = document.getElementById('disp-data').value;
    const instrutorId = document.getElementById('disp-instrutor').value;
    const tipo = document.getElementById('disp-tipo').value;
    const duracao = document.getElementById('disp-duracao').value;
    
    if (!data) {
        alert('Por favor, selecione uma data');
        return;
    }
    
    // Mostrar loading
    const btnConsultar = document.querySelector('#modalDisponibilidade .btn-primary');
    const textoOriginal = btnConsultar.innerHTML;
    btnConsultar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Consultando...';
    btnConsultar.disabled = true;
    
    try {
        // Se instrutor específico foi selecionado, consultar disponibilidade detalhada
        if (instrutorId) {
            await consultarDisponibilidadeInstrutor(data, instrutorId, tipo, duracao);
        } else {
            await consultarDisponibilidadeGeral(data, tipo);
        }
    } catch (error) {
        console.error('Erro ao consultar disponibilidade:', error);
        alert('Erro ao consultar disponibilidade. Tente novamente.');
    } finally {
        // Restaurar botão
        btnConsultar.innerHTML = textoOriginal;
        btnConsultar.disabled = false;
    }
}

async function consultarDisponibilidadeInstrutor(data, instrutorId, tipo, duracao) {
    const horariosDisponiveis = [
        '08:00', '08:50', '09:40', '10:30', '11:20', '12:10',
        '14:00', '14:50', '15:40', '16:30', '17:20', '18:10'
    ];
    
    const resultados = [];
    
    // Primeiro, buscar aulas existentes do instrutor na data
    const aulasExistentes = aulasData.filter(aula => 
        aula.instrutor_id == instrutorId && 
        aula.data_aula === data && 
        aula.status !== 'cancelada'
    );
    
    console.log('Aulas existentes para instrutor', instrutorId, 'na data', data, ':', aulasExistentes);
    
    // Verificar cada horário disponível
    for (const horario of horariosDisponiveis) {
        try {
            const params = new URLSearchParams({
                data_aula: data,
                hora_inicio: horario,
                duracao: duracao,
                instrutor_id: instrutorId,
                tipo_aula: tipo || 'pratica'
            });
            
            const response = await fetch(API_CONFIG.getRelativeApiUrl('VERIFICAR_DISPONIBILIDADE') + '?' + params);
            
            if (!response.ok) {
                console.warn(`API não disponível para ${horario}, usando verificação manual`);
                // Se a API falhou, verificar manualmente se há conflito
                const conflito = aulasExistentes.find(aula => {
                    const aulaInicio = aula.hora_inicio.substring(0, 5);
                    const aulaFim = aula.hora_fim.substring(0, 5);
                    
                    // Verificar se o horário solicitado conflita com a aula existente
                    // Considerando que cada aula dura 50 minutos
                    const horarioInicioMinutos = converterHoraParaMinutos(horario);
                    const horarioFimMinutos = horarioInicioMinutos + parseInt(duracao);
                    const aulaInicioMinutos = converterHoraParaMinutos(aulaInicio);
                    const aulaFimMinutos = converterHoraParaMinutos(aulaFim);
                    
                    // Verificar sobreposição de horários
                    return (horarioInicioMinutos < aulaFimMinutos && horarioFimMinutos > aulaInicioMinutos);
                });
                
                resultados.push({
                    horario: horario,
                    disponivel: !conflito,
                    detalhes: conflito ? { conflito: conflito } : null,
                    mensagem: conflito ? `Conflito com aula de ${conflito.aluno_nome}` : 'Horário disponível'
                });
            } else {
                const result = await response.json();
                console.log(`Verificação para ${horario}:`, result);
                
                if (result.sucesso) {
                    resultados.push({
                        horario: horario,
                        disponivel: result.disponivel,
                        detalhes: result.detalhes,
                        mensagem: result.mensagem
                    });
                } else {
                    // Se a API retornou erro, usar verificação manual
                    const conflito = aulasExistentes.find(aula => {
                        const aulaInicio = aula.hora_inicio.substring(0, 5);
                        const aulaFim = aula.hora_fim.substring(0, 5);
                        
                        const horarioInicioMinutos = converterHoraParaMinutos(horario);
                        const horarioFimMinutos = horarioInicioMinutos + parseInt(duracao);
                        const aulaInicioMinutos = converterHoraParaMinutos(aulaInicio);
                        const aulaFimMinutos = converterHoraParaMinutos(aulaFim);
                        
                        return (horarioInicioMinutos < aulaFimMinutos && horarioFimMinutos > aulaInicioMinutos);
                    });
                    
                    resultados.push({
                        horario: horario,
                        disponivel: !conflito,
                        detalhes: conflito ? { conflito: conflito } : null,
                        mensagem: conflito ? `Conflito com aula de ${conflito.aluno_nome}` : 'Horário disponível'
                    });
                }
            }
        } catch (error) {
            console.error(`Erro ao verificar horário ${horario}:`, error);
            // Em caso de erro, assumir que está disponível
            resultados.push({
                horario: horario,
                disponivel: true,
                detalhes: null,
                mensagem: 'Erro na verificação - assumindo disponível'
            });
        }
    }
    
    console.log('Resultados finais:', resultados);
    exibirResultadosDisponibilidade(resultados, data, instrutorId);
}

async function consultarDisponibilidadeGeral(data, tipo) {
    // Buscar todas as aulas do dia
    const aulasDoDia = aulasData.filter(aula => aula.data_aula === data);
    
    // Agrupar por instrutor
    const aulasPorInstrutor = {};
    aulasDoDia.forEach(aula => {
        if (!aulasPorInstrutor[aula.instrutor_id]) {
            aulasPorInstrutor[aula.instrutor_id] = {
                instrutor: aula.instrutor_nome,
                aulas: []
            };
        }
        aulasPorInstrutor[aula.instrutor_id].aulas.push(aula);
    });
    
    exibirResultadosGerais(aulasPorInstrutor, data);
}

function exibirResultadosDisponibilidade(resultados, data, instrutorId) {
    const conteudo = document.getElementById('conteudo-resultado');
    const resultadoDiv = document.getElementById('resultado-disponibilidade');
    
    const instrutorNome = instrutoresData.find(i => i.id == instrutorId)?.nome || 'Instrutor';
    
    // Buscar aulas existentes para mostrar informações adicionais
    const aulasExistentes = aulasData.filter(aula => 
        aula.instrutor_id == instrutorId && 
        aula.data_aula === data && 
        aula.status !== 'cancelada'
    );
    
    let html = `
        <div class="alert alert-info mb-4">
            <div class="d-flex align-items-center">
                <i class="fas fa-user-circle fa-2x me-3 text-primary"></i>
                <div>
                    <h6 class="mb-1"><strong>${instrutorNome}</strong></h6>
                    <p class="mb-1">Data: ${new Date(data).toLocaleDateString('pt-BR')}</p>
                    ${aulasExistentes.length > 0 ? `<small class="text-muted"><i class="fas fa-info-circle me-1"></i>${aulasExistentes.length} aula(s) já agendada(s) neste dia</small>` : ''}
                </div>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-6 col-md-12">
                <div class="card h-100 border-success">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0 fw-bold" style="color: #575A5E !important;">
                            <i class="fas fa-check-circle me-2" aria-hidden="true"></i>Horários Disponíveis
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
    `;
    
    const disponiveis = resultados.filter(r => r.disponivel);
    const indisponiveis = resultados.filter(r => !r.disponivel);
    
    if (disponiveis.length > 0) {
        disponiveis.forEach(resultado => {
            html += `
                <div class="list-group-item list-group-item-success border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2 text-success" aria-hidden="true"></i>
                            <span class="fw-medium">${resultado.horario}</span>
                        </div>
                        <span class="badge bg-success" role="status" aria-label="Horário disponível">Disponível</span>
                    </div>
                </div>
            `;
        });
    } else {
        html += `
            <div class="list-group-item list-group-item-secondary border-0 text-center py-4">
                <i class="fas fa-exclamation-triangle fa-2x text-muted mb-2" aria-hidden="true"></i>
                <p class="mb-0 text-muted">Nenhum horário disponível</p>
            </div>
        `;
    }
    
    html += `
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-6 col-md-12">
                <div class="card h-100 border-danger">
                    <div class="card-header bg-danger text-white">
                        <h6 class="mb-0 fw-bold" style="color: #575A5E !important;">
                            <i class="fas fa-times-circle me-2" aria-hidden="true"></i>Horários Ocupados
                        </h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
    `;
    
    if (indisponiveis.length > 0) {
        indisponiveis.forEach(resultado => {
            html += `
                <div class="list-group-item list-group-item-danger border-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2 text-danger" aria-hidden="true"></i>
                            <span class="fw-medium">${resultado.horario}</span>
                        </div>
                        <span class="badge bg-danger" role="status" aria-label="Horário ocupado">Ocupado</span>
                    </div>
                    <small class="text-muted">${resultado.mensagem}</small>
                </div>
            `;
        });
    } else if (aulasExistentes.length > 0) {
        // Se não há horários indisponíveis mas há aulas, mostrar as aulas existentes
        aulasExistentes.forEach(aula => {
            const horaInicio = aula.hora_inicio.substring(0, 5);
            const horaFim = aula.hora_fim.substring(0, 5);
            html += `
                <div class="list-group-item list-group-item-warning border-0">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-clock me-2 text-warning" aria-hidden="true"></i>
                            <span class="fw-medium">${horaInicio} - ${horaFim}</span>
                        </div>
                        <span class="badge bg-warning text-dark" role="status" aria-label="Aula agendada">Agendada</span>
                    </div>
                    <small class="text-muted">
                        <i class="fas fa-user me-1" aria-hidden="true"></i>Aluno: ${aula.aluno_nome}
                    </small>
                </div>
            `;
        });
    } else {
        html += `
            <div class="list-group-item list-group-item-secondary border-0 text-center py-4">
                <i class="fas fa-check-circle fa-2x text-muted mb-2" aria-hidden="true"></i>
                <p class="mb-0 text-muted">Todos os horários estão livres</p>
            </div>
        `;
    }
    
    html += `
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    conteudo.innerHTML = html;
    resultadoDiv.style.display = 'block';
}

function exibirResultadosGerais(aulasPorInstrutor, data) {
    const conteudo = document.getElementById('conteudo-resultado');
    const resultadoDiv = document.getElementById('resultado-disponibilidade');
    
    let html = `
        <div class="alert alert-info">
            <h6><i class="fas fa-calendar me-2"></i>Resumo do Dia</h6>
            <p class="mb-0">Data: ${new Date(data).toLocaleDateString('pt-BR')}</p>
        </div>
        
        <div class="row">
    `;
    
    Object.values(aulasPorInstrutor).forEach(instrutor => {
        const totalAulas = instrutor.aulas.length;
        const aulasConcluidas = instrutor.aulas.filter(a => a.status === 'concluida').length;
        const aulasAgendadas = instrutor.aulas.filter(a => a.status === 'agendada').length;
        
        html += `
            <div class="col-md-6 mb-3">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">
                            <i class="fas fa-user me-2"></i>${instrutor.instrutor}
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-4">
                                <div class="text-primary">
                                    <strong>${totalAulas}</strong>
                                    <br><small>Total</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-success">
                                    <strong>${aulasConcluidas}</strong>
                                    <br><small>Concluídas</small>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="text-warning">
                                    <strong>${aulasAgendadas}</strong>
                                    <br><small>Agendadas</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `</div>`;
    
    conteudo.innerHTML = html;
    resultadoDiv.style.display = 'block';
}

function verificarDisponibilidade() {
    abrirModalDisponibilidade();
}

function verificarDisponibilidadeInstrutor() {
    abrirModalDisponibilidade();
}

function verificarDisponibilidadeVeiculo() {
    abrirModalDisponibilidade();
}

// Função auxiliar para converter hora HH:MM para minutos
function converterHoraParaMinutos(hora) {
    const [horas, minutos] = hora.split(':').map(Number);
    return horas * 60 + minutos;
}

function atualizarEstatisticas() {
    // Implementar atualização das estatísticas
    const hoje = new Date();
    const inicioSemana = new Date(hoje);
    inicioSemana.setDate(hoje.getDate() - hoje.getDay());
    
    // Verificar se há filtro de status ativo
    const filtroStatus = window.filtrosAtivos?.status;
    
    // Contar aulas de hoje
    const aulasHoje = aulasData.filter(aula => {
        const dataAula = new Date(aula.data_aula);
        const hojeMatch = dataAula.toDateString() === hoje.toDateString();
        
        if (filtroStatus) {
            return hojeMatch && aula.status === filtroStatus;
        } else {
            return hojeMatch && aula.status !== 'cancelada';
        }
    }).length;
    
    // Contar aulas da semana
    const aulasSemana = aulasData.filter(aula => {
        const dataAula = new Date(aula.data_aula);
        const semanaMatch = dataAula >= inicioSemana && dataAula <= hoje;
        
        if (filtroStatus) {
            return semanaMatch && aula.status === filtroStatus;
        } else {
            return semanaMatch && aula.status !== 'cancelada';
        }
    }).length;
    
    // Contar aulas pendentes (apenas agendadas)
    const aulasPendentes = aulasData.filter(aula => {
        if (filtroStatus) {
            return aula.status === filtroStatus;
        } else {
            return aula.status === 'agendada';
        }
    }).length;
    
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
    
    // Armazenar filtros globalmente para uso na função de eventos
    window.filtrosAtivos = {
        cfc: cfcId,
        instrutor: instrutorId,
        tipo: tipoAula,
        status: status
    };
    
    // Recarregar calendário para aplicar filtros
    if (window.calendar) {
        window.calendar.refetchEvents();
    }
    
    // Atualizar estatísticas com base nos filtros
    atualizarEstatisticas();
    
    console.log('Filtros aplicados e calendário recarregado');
}

function navegarCalendario(direcao) {
    if (window.calendar) {
        if (direcao === 'previous') {
            window.calendar.prev();
        } else if (direcao === 'next') {
            window.calendar.next();
        }
        // Atualizar título após navegação
        atualizarTituloCalendario();
    }
}

function atualizarTituloCalendario() {
    const titulo = document.getElementById('calendar-title');
    if (titulo && window.calendar) {
        const data = window.calendar.getDate();
        const view = window.calendar.view.type;
        
        let texto = '';
        switch (view) {
            case 'dayGridMonth':
                texto = data.toLocaleDateString('pt-BR', { 
                    month: 'long', 
                    year: 'numeric' 
                });
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

function mudarVisualizacao(view) {
    // Atualizar botões ativos
    document.querySelectorAll('.calendar-views .btn').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');

    // Mudar visualização
    if (window.calendar) {
        window.calendar.changeView(view);
        // Atualizar título após mudança de visualização
        atualizarTituloCalendario();
    }
    
    console.log('Mudando para visualização:', view);
}

function exibirDetalhesAula(evento) {
    const props = evento.extendedProps;
    
    // Criar modal de detalhes
    const modalHtml = `
        <div id="modal-detalhes-aula" class="modal-overlay" style="display: flex;">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Detalhes da Aula</h3>
                    <button class="modal-close" onclick="fecharModalDetalhes()">×</button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-calendar-alt me-2"></i>Informações da Aula
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Data:</label>
                                <p class="mb-0">${formatarData(evento.start)}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Horário:</label>
                                <p class="mb-0">${formatarHorario(evento.start, evento.end)}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Tipo de Aula:</label>
                                <p class="mb-0">
                                    <span class="badge bg-${props.tipo_aula === 'teorica' ? 'info' : 'primary'}">
                                        ${props.tipo_aula.toUpperCase()}
                                    </span>
                                </p>
                            </div>
                            ${props.disciplina ? `
                            <div class="mb-3">
                                <label class="form-label fw-bold">Disciplina:</label>
                                <p class="mb-0">${props.disciplina}</p>
                            </div>
                            ` : ''}
                            <div class="mb-3">
                                <label class="form-label fw-bold">Status:</label>
                                <p class="mb-0">
                                    <span class="badge bg-${getStatusColor(props.status)}">
                                        ${props.status.toUpperCase()}
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-users me-2"></i>Participantes
                            </h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Aluno:</label>
                                <p class="mb-0">${props.aluno_nome}</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-bold">Instrutor:</label>
                                <p class="mb-0">${props.instrutor_nome}</p>
                            </div>
                            ${props.placa ? `
                            <div class="mb-3">
                                <label class="form-label fw-bold">Veículo:</label>
                                <p class="mb-0">${props.placa} - ${props.marca} ${props.modelo}</p>
                            </div>
                            ` : `
                            <div class="mb-3">
                                <label class="form-label fw-bold">Veículo:</label>
                                <p class="mb-0 text-muted">Não aplicável</p>
                            </div>
                            `}
                        </div>
                    </div>
                    ${props.observacoes ? `
                    <hr>
                    <div class="row">
                        <div class="col-12">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-sticky-note me-2"></i>Observações
                            </h6>
                            <div class="alert alert-light">
                                <p class="mb-0">${props.observacoes}</p>
                            </div>
                        </div>
                    </div>
                    ` : ''}
                </div>
                <div class="modal-actions">
                    <button class="btn btn-secondary" onclick="fecharModalDetalhes()">Fechar</button>
                    ${props.status === 'agendada' ? `
                    <button class="btn btn-warning" onclick="editarAula(${evento.id})">
                        <i class="fas fa-edit me-1"></i>Editar
                    </button>
                    <button class="btn btn-danger" onclick="cancelarAula(${evento.id})">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Remover modal anterior se existir
    const modalExistente = document.getElementById('modal-detalhes-aula');
    if (modalExistente) {
        modalExistente.remove();
    }
    
    // Adicionar novo modal
    document.body.insertAdjacentHTML('beforeend', modalHtml);
}

function fecharModalDetalhes() {
    const modal = document.getElementById('modal-detalhes-aula');
    if (modal) {
        modal.remove();
    }
}

function adicionarTooltipEvento(evento, elemento) {
    const props = evento.extendedProps;
    const tooltip = `
        <div class="event-tooltip">
            <div class="tooltip-header">
                <strong>${props.hora_inicio} ${props.status_icon}</strong>
                <span class="tooltip-type">${props.tipo_aula === 'teorica' ? '📚 Teórica' : '🚗 Prática'}</span>
            </div>
            <div class="tooltip-content">
                <div class="tooltip-row">
                    <strong>Aluno:</strong> ${props.aluno_nome}
                </div>
                <div class="tooltip-row">
                    <strong>Instrutor:</strong> ${props.instrutor_nome}
                </div>
                ${props.placa ? `
                <div class="tooltip-row">
                    <strong>Veículo:</strong> ${props.marca} ${props.modelo} (${props.placa})
                </div>
                ` : ''}
                <div class="tooltip-row">
                    <strong>Status:</strong> <span class="status-${props.status}">${props.status.toUpperCase()}</span>
                </div>
                ${props.observacoes ? `
                <div class="tooltip-row">
                    <strong>Obs:</strong> ${props.observacoes}
                </div>
                ` : ''}
            </div>
            <div class="tooltip-footer">
                <small>Clique para ver detalhes completos</small>
            </div>
        </div>
    `;

    // Adicionar tooltip usando title (fallback) e data-tooltip
    elemento.setAttribute('title', tooltip.replace(/<[^>]*>/g, ''));
    elemento.setAttribute('data-tooltip', tooltip);
    
    // Adicionar cursor pointer
    elemento.style.cursor = 'pointer';
}

function formatarData(data) {
    if (!data) return 'N/A';
    return new Date(data).toLocaleDateString('pt-BR');
}

function formatarHorario(inicio, fim) {
    if (!inicio || !fim) return 'N/A';
    const inicioFormatado = new Date(inicio).toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    const fimFormatado = new Date(fim).toLocaleTimeString('pt-BR', { 
        hour: '2-digit', 
        minute: '2-digit' 
    });
    return `${inicioFormatado} - ${fimFormatado}`;
}

function getStatusColor(status) {
    const colors = {
        'agendada': 'warning',
        'concluida': 'success',
        'cancelada': 'danger',
        'em_andamento': 'info'
    };
    return colors[status] || 'secondary';
}

function editarAula(aulaId) {
    console.log('Editando aula:', aulaId);
    fecharModalDetalhes();
    abrirModalEditarAula(aulaId);
}

function cancelarAula(aulaId) {
    console.log('Cancelando aula:', aulaId);
    
    // Confirmar cancelamento
    const confirmacao = confirm('Tem certeza que deseja cancelar esta aula?');
    if (confirmacao) {
        // Chamar API para cancelar aula
        fetch('API_CONFIG.getRelativeApiUrl('AGENDAMENTO')', {
            method: 'POST',
            credentials: 'include',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                acao: 'cancelar',
                aula_id: aulaId
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('❌ Erro ao fazer parse do JSON:', e);
                    console.error('📄 Texto que causou erro:', text);
                    throw new Error('Resposta não é JSON válido: ' + text.substring(0, 100));
                }
            });
        })
        .then(data => {
            if (data.success) {
                alert('Aula cancelada com sucesso!');
                fecharModalDetalhes();
                
                // Recarregar calendário para mostrar dados atualizados
                if (window.calendar) {
                    window.calendar.refetchEvents();
                }
                
                // Recarregar página após um pequeno delay para garantir que os dados sejam atualizados
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                alert('Erro ao cancelar aula: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao cancelar aula. Tente novamente.');
        });
    }
}

function exportarAgenda() {
    // Implementar exportação da agenda
    alert('Exportando agenda...');
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
