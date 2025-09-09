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
        SELECT i.*, u.nome, u.email, u.telefone
        FROM instrutores i
        JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.ativo = 1
        ORDER BY u.nome
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
    <div class="modal-content">
        <div class="modal-header">
            <h3>Nova Aula</h3>
            <button class="modal-close" onclick="fecharModalNovaAula()">×</button>
        </div>
        
        <form id="form-nova-aula" class="modal-form" onsubmit="salvarNovaAula(event)">
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
                    <select id="instrutor_id" name="instrutor_id" required onchange="verificarDisponibilidadeInstrutor()">
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
                    <label for="tipo_aula">Tipo de Aula *</label>
                    <select id="tipo_aula" name="tipo_aula" required onchange="verificarDisponibilidadeVeiculo()">
                        <option value="">Selecione o tipo</option>
                        <option value="teorica">Teórica</option>
                        <option value="pratica">Prática</option>
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
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data_aula">Data da Aula *</label>
                    <input type="date" id="data_aula" name="data_aula" required min="<?php echo date('Y-m-d'); ?>" onchange="verificarDisponibilidade()">
                </div>
                
                <div class="form-group">
                    <label for="hora_inicio">Hora de Início *</label>
                    <input type="time" id="hora_inicio" name="hora_inicio" required onchange="calcularHoraFim()">
                </div>
                
                <div class="form-group">
                    <label for="hora_fim">Hora de Fim *</label>
                    <input type="time" id="hora_fim" name="hora_fim" required>
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

<!-- Scripts específicos do agendamento -->
<script>
// Dados das aulas para o calendário
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
    
    // Implementar lógica de salvamento
    notifications.success('Aula agendada com sucesso!');
    fecharModalNovaAula();
    // Recarregar calendário
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

function atualizarEstatisticas() {
    // Implementar atualização das estatísticas
    document.getElementById('aulas-hoje').textContent = '0';
    document.getElementById('aulas-semana').textContent = '0';
    document.getElementById('aulas-pendentes').textContent = '0';
    document.getElementById('instrutores-disponiveis').textContent = '0';
}

function limparFormularioNovaAula() {
    document.getElementById('form-nova-aula').reset();
    document.getElementById('veiculo_id').disabled = true;
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
