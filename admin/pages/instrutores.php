<?php
// Verificar se as variáveis estão definidas
if (!isset($instrutores)) $instrutores = [];
if (!isset($cfcs)) $cfcs = [];
if (!isset($usuarios)) $usuarios = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-chalkboard-teacher me-2"></i>Gestão de Instrutores
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarInstrutores()">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="imprimirInstrutores()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInstrutor">
            <i class="fas fa-plus me-1"></i>Novo Instrutor
        </button>
    </div>
</div>

<!-- Mensagens de Feedback -->
<?php if (!empty($mensagem)): ?>
<div class="alert alert-<?php echo $tipo_mensagem; ?> alert-dismissible fade show" role="alert">
    <?php echo htmlspecialchars($mensagem); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Filtros e Busca -->
<div class="row mb-4">
    <div class="col-md-4">
        <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="buscaInstrutor" placeholder="Buscar instrutor por nome, credencial ou CFC...">
        </div>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroStatus">
            <option value="">Todos os Status</option>
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroCFC">
            <option value="">Todos os CFCs</option>
            <?php foreach ($cfcs as $cfc): ?>
                <option value="<?php echo $cfc['id']; ?>"><?php echo htmlspecialchars($cfc['nome']); ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroCategoria">
            <option value="">Todas as Categorias</option>
            <option value="A">Categoria A</option>
            <option value="B">Categoria B</option>
            <option value="C">Categoria C</option>
            <option value="D">Categoria D</option>
            <option value="E">Categoria E</option>
            <option value="AB">Categoria AB</option>
            <option value="AC">Categoria AC</option>
            <option value="AD">Categoria AD</option>
            <option value="AE">Categoria AE</option>
        </select>
    </div>
    <div class="col-md-2">
        <button type="button" class="btn btn-outline-info w-100" onclick="limparFiltros()">
            <i class="fas fa-times me-1"></i>Limpar
        </button>
    </div>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-primary shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                            Total de Instrutores
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalInstrutores">
                            <?php echo count($instrutores); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-chalkboard-teacher fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-success shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                            Instrutores Ativos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="instrutoresAtivos">
                            <?php echo count(array_filter($instrutores, function($i) { return $i['ativo']; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-info shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                            Aulas Hoje
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="aulasHoje">
                            <?php echo array_sum(array_column($instrutores, 'aulas_hoje')); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-day fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Total de Alunos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalAlunos">
                            <?php echo array_sum(array_column($instrutores, 'total_alunos')); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-user-graduate fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Instrutores -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Instrutores</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tabelaInstrutores">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Instrutor</th>
                        <th>Credencial</th>
                        <th>CFC</th>
                        <th>Categorias</th>
                        <th>Status</th>
                        <th>Disponibilidade</th>
                        <th>Alunos</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($instrutores)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum instrutor cadastrado ainda.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalInstrutor">
                                <i class="fas fa-plus me-1"></i>Cadastrar Primeiro Instrutor
                            </button>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($instrutores as $instrutor): ?>
                        <tr data-instrutor-id="<?php echo $instrutor['id']; ?>">
                            <td><?php echo $instrutor['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3">
                                        <div class="avatar-title bg-primary rounded-circle">
                                            <?php echo strtoupper(substr($instrutor['nome'], 0, 1)); ?>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($instrutor['nome']); ?></strong>
                                        <?php if ($instrutor['email']): ?>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($instrutor['email']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code><?php echo htmlspecialchars($instrutor['credencial']); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($instrutor['cfc_nome'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <?php 
                                $categorias = explode(',', $instrutor['categoria_habilitacao']);
                                foreach ($categorias as $cat): 
                                    $cat = trim($cat);
                                    if (!empty($cat)):
                                ?>
                                    <span class="badge bg-secondary me-1"><?php echo htmlspecialchars($cat); ?></span>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </td>
                            <td>
                                <?php if ($instrutor['ativo']): ?>
                                    <span class="badge bg-success">Ativo</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inativo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($instrutor['disponivel']): ?>
                                    <span class="badge bg-success">Disponível</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Ocupado</span>
                                <?php endif; ?>
                                <br><small class="text-muted">
                                    <?php echo $instrutor['aulas_hoje']; ?> aulas hoje
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-primary"><?php echo $instrutor['total_alunos'] ?? 0; ?></span>
                            </td>
                            <td>
                                <div class="action-buttons-container">
                                    <!-- Botões principais em linha -->
                                    <div class="action-buttons-primary">
                                        <button type="button" class="btn btn-edit action-btn" 
                                                onclick="editarInstrutor(<?php echo $instrutor['id']; ?>)" 
                                                title="Editar dados do instrutor">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button type="button" class="btn btn-view action-btn" 
                                                onclick="visualizarInstrutor(<?php echo $instrutor['id']; ?>)" 
                                                title="Ver detalhes completos do instrutor">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="agendarAula(<?php echo $instrutor['id']; ?>)" 
                                                title="Agendar nova aula com este instrutor">
                                            <i class="fas fa-calendar-plus me-1"></i>Agendar
                                        </button>
                                    </div>
                                    
                                    <!-- Botões secundários em linha -->
                                    <div class="action-buttons-secondary">
                                        <button type="button" class="btn btn-history action-btn" 
                                                onclick="historicoInstrutor(<?php echo $instrutor['id']; ?>)" 
                                                title="Visualizar histórico de aulas e desempenho">
                                            <i class="fas fa-history me-1"></i>Histórico
                                        </button>
                                        <?php if ($instrutor['ativo']): ?>
                                        <button type="button" class="btn btn-toggle action-btn" 
                                                onclick="desativarInstrutor(<?php echo $instrutor['id']; ?>)" 
                                                title="Desativar instrutor (não poderá dar aulas)">
                                            <i class="fas fa-ban me-1"></i>Desativar
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="ativarInstrutor(<?php echo $instrutor['id']; ?>)" 
                                                title="Reativar instrutor para dar aulas">
                                            <i class="fas fa-check me-1"></i>Ativar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botão de exclusão destacado -->
                                    <div class="action-buttons-danger">
                                        <button type="button" class="btn btn-delete action-btn" 
                                                onclick="excluirInstrutor(<?php echo $instrutor['id']; ?>)" 
                                                title="⚠️ EXCLUIR INSTRUTOR - Esta ação não pode ser desfeita!">
                                            <i class="fas fa-trash me-1"></i>Excluir
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Cadastro/Edição de Instrutor -->
<div class="modal fade" id="modalInstrutor" tabindex="-1" aria-labelledby="modalInstrutorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formInstrutor" method="POST" action="admin/pages/instrutores.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalInstrutorLabel">
                        <i class="fas fa-chalkboard-teacher me-2"></i><span id="modalTitle">Novo Instrutor</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" id="acaoInstrutor" value="criar">
                    <input type="hidden" name="instrutor_id" id="instrutor_id" value="">
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="usuario_id" class="form-label">Usuário *</label>
                                <select class="form-select" id="usuario_id" name="usuario_id" required>
                                    <option value="">Selecione um usuário...</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <?php if ($usuario['tipo'] === 'instrutor' || $usuario['tipo'] === 'admin'): ?>
                                        <option value="<?php echo $usuario['id']; ?>">
                                            <?php echo htmlspecialchars($usuario['nome']); ?> 
                                            (<?php echo ucfirst($usuario['tipo']); ?>)
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cfc_id" class="form-label">CFC *</label>
                                <select class="form-select" id="cfc_id" name="cfc_id" required>
                                    <option value="">Selecione um CFC...</option>
                                    <?php foreach ($cfcs as $cfc): ?>
                                        <?php if ($cfc['ativo']): ?>
                                        <option value="<?php echo $cfc['id']; ?>">
                                            <?php echo htmlspecialchars($cfc['nome']); ?>
                                        </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="credencial" class="form-label">Credencial *</label>
                                <input type="text" class="form-control" id="credencial" name="credencial" required 
                                       placeholder="Número da credencial de instrutor">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_credenciamento" class="form-label">Data de Credenciamento</label>
                                <input type="date" class="form-control" id="data_credenciamento" name="data_credenciamento">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria_habilitacao" class="form-label">Categorias de Habilitação *</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="A" id="catA" name="categorias[]">
                                            <label class="form-check-label" for="catA">A - Motocicletas</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="B" id="catB" name="categorias[]">
                                            <label class="form-check-label" for="catB">B - Automóveis</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="C" id="catC" name="categorias[]">
                                            <label class="form-check-label" for="catC">C - Veículos de carga</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="D" id="catD" name="categorias[]">
                                            <label class="form-check-label" for="catD">D - Veículos de passageiros</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="E" id="catE" name="categorias[]">
                                            <label class="form-check-label" for="catE">E - Veículos com reboque</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="AB" id="catAB" name="categorias[]">
                                            <label class="form-check-label" for="catAB">AB - A + B</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="AC" id="catAC" name="categorias[]">
                                            <label class="form-check-label" for="catAC">AC - A + C</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="AD" id="catAD" name="categorias[]">
                                            <label class="form-check-label" for="catAD">AD - A + D</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="AE" id="catAE" name="categorias[]">
                                            <label class="form-check-label" for="catAE">AE - A + E</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="especializacoes" class="form-label">Especializações</label>
                                <textarea class="form-control" id="especializacoes" name="especializacoes" rows="3" 
                                          placeholder="Especializações, cursos, certificações..."></textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="horario_trabalho" class="form-label">Horário de Trabalho</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="hora_inicio" class="form-label">Início</label>
                                        <input type="time" class="form-control" id="hora_inicio" name="hora_inicio">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="hora_fim" class="form-label">Fim</label>
                                        <input type="time" class="form-control" id="hora_fim" name="hora_fim">
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="dias_trabalho" class="form-label">Dias de Trabalho</label>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="segunda" id="segunda" name="dias_trabalho[]">
                                            <label class="form-check-label" for="segunda">Segunda</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="terca" id="terca" name="dias_trabalho[]">
                                            <label class="form-check-label" for="terca">Terça</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="quarta" id="quarta" name="dias_trabalho[]">
                                            <label class="form-check-label" for="quarta">Quarta</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="quinta" id="quinta" name="dias_trabalho[]">
                                            <label class="form-check-label" for="quinta">Quinta</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="sexta" id="sexta" name="dias_trabalho[]">
                                            <label class="form-check-label" for="sexta">Sexta</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="sabado" id="sabado" name="dias_trabalho[]">
                                            <label class="form-check-label" for="sabado">Sábado</label>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="domingo" id="domingo" name="dias_trabalho[]">
                                            <label class="form-check-label" for="domingo">Domingo</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="ativo" class="form-label">Status</label>
                                <select class="form-select" id="ativo" name="ativo">
                                    <option value="1">Ativo</option>
                                    <option value="0">Inativo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="disponivel" class="form-label">Disponibilidade</label>
                                <select class="form-select" id="disponivel" name="disponivel">
                                    <option value="1">Disponível</option>
                                    <option value="0">Ocupado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                  placeholder="Informações adicionais sobre o instrutor..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarInstrutor">
                        <i class="fas fa-save me-1"></i>Salvar Instrutor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Instrutor -->
<div class="modal fade" id="modalVisualizarInstrutor" tabindex="-1" aria-labelledby="modalVisualizarInstrutorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarInstrutorLabel">
                    <i class="fas fa-eye me-2"></i>Detalhes do Instrutor
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVisualizarInstrutorBody">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="fas fa-edit me-1"></i>Editar Instrutor
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para Instrutores -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar filtros
    inicializarFiltrosInstrutor();
    
    // Inicializar busca
    inicializarBuscaInstrutor();
    
    // Handler para o formulário de instrutor
    document.getElementById('formInstrutor').addEventListener('submit', function(e) {
        e.preventDefault();
        salvarInstrutor();
    });
});

function inicializarFiltrosInstrutor() {
    // Filtro por status
    document.getElementById('filtroStatus').addEventListener('change', filtrarInstrutores);
    
    // Filtro por CFC
    document.getElementById('filtroCFC').addEventListener('change', filtrarInstrutores);
    
    // Filtro por categoria
    document.getElementById('filtroCategoria').addEventListener('change', filtrarInstrutores);
}

function filtrarInstrutores() {
    const status = document.getElementById('filtroStatus').value;
    const cfc = document.getElementById('filtroCFC').value;
    const categoria = document.getElementById('filtroCategoria').value;
    const busca = document.getElementById('buscaInstrutor').value.toLowerCase();
    
    const linhas = document.querySelectorAll('#tabelaInstrutores tbody tr');
    
    linhas.forEach(linha => {
        let mostrar = true;
        
        // Filtro por status
        if (status) {
            const statusLinha = linha.querySelector('td:nth-child(6) .badge').textContent;
            if (status === 'ativo' && statusLinha !== 'Ativo') mostrar = false;
            if (status === 'inativo' && statusLinha !== 'Inativo') mostrar = false;
        }
        
        // Filtro por CFC
        if (cfc) {
            const cfcLinha = linha.querySelector('td:nth-child(4) .badge').textContent;
            if (cfcLinha === 'N/A' || cfcLinha !== cfc) {
                mostrar = false;
            }
        }
        
        // Filtro por categoria
        if (categoria) {
            const categoriasLinha = Array.from(linha.querySelectorAll('td:nth-child(5) .badge'))
                .map(badge => badge.textContent);
            if (!categoriasLinha.includes(categoria)) {
                mostrar = false;
            }
        }
        
        // Filtro por busca
        if (busca) {
            const texto = linha.textContent.toLowerCase();
            if (!texto.includes(busca)) {
                mostrar = false;
            }
        }
        
        linha.style.display = mostrar ? '' : 'none';
    });
    
    // Atualizar estatísticas
    atualizarEstatisticas();
}

function inicializarBuscaInstrutor() {
    document.getElementById('buscaInstrutor').addEventListener('input', filtrarInstrutores);
}

function editarInstrutor(id) {
    // Buscar dados do instrutor
    fetch(`admin/api/instrutores.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherFormularioInstrutor(data.instrutor);
                document.getElementById('modalTitle').textContent = 'Editar Instrutor';
                document.getElementById('acaoInstrutor').value = 'editar';
                document.getElementById('instrutor_id').value = id;
                
                const modal = new bootstrap.Modal(document.getElementById('modalInstrutor'));
                modal.show();
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
    document.getElementById('usuario_id').value = instrutor.usuario_id || '';
    document.getElementById('cfc_id').value = instrutor.cfc_id || '';
    document.getElementById('credencial').value = instrutor.credencial || '';
    document.getElementById('data_credenciamento').value = instrutor.data_credenciamento || '';
    document.getElementById('especializacoes').value = instrutor.especializacoes || '';
    document.getElementById('ativo').value = instrutor.ativo ? '1' : '0';
    document.getElementById('disponivel').value = instrutor.disponivel ? '1' : '0';
    document.getElementById('observacoes').value = instrutor.observacoes || '';
    
    // Categorias
    if (instrutor.categoria_habilitacao) {
        const categorias = instrutor.categoria_habilitacao.split(',').map(cat => cat.trim());
        document.querySelectorAll('input[name="categorias[]"]').forEach(checkbox => {
            checkbox.checked = categorias.includes(checkbox.value);
        });
    }
    
    // Horário de trabalho
    if (instrutor.horario_trabalho) {
        const horario = typeof instrutor.horario_trabalho === 'string' ? JSON.parse(instrutor.horario_trabalho) : instrutor.horario_trabalho;
        document.getElementById('hora_inicio').value = horario.hora_inicio || '';
        document.getElementById('hora_fim').value = horario.hora_fim || '';
    }
    
    // Dias de trabalho
    if (instrutor.dias_trabalho) {
        const dias = typeof instrutor.dias_trabalho === 'string' ? JSON.parse(instrutor.dias_trabalho) : instrutor.dias_trabalho;
        document.querySelectorAll('input[name="dias_trabalho[]"]').forEach(checkbox => {
            checkbox.checked = dias.includes(checkbox.value);
        });
    }
}

function visualizarInstrutor(id) {
    fetch(`admin/api/instrutores.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherModalVisualizacao(data.instrutor);
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarInstrutor'));
                modal.show();
            } else {
                mostrarAlerta('Erro ao carregar dados do instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do instrutor', 'danger');
        });
}

function preencherModalVisualizacao(instrutor) {
    const categorias = instrutor.categoria_habilitacao ? instrutor.categoria_habilitacao.split(',').map(cat => cat.trim()) : [];
    const horario = instrutor.horario_trabalho ? (typeof instrutor.horario_trabalho === 'string' ? JSON.parse(instrutor.horario_trabalho) : instrutor.horario_trabalho) : null;
    const dias = instrutor.dias_trabalho ? (typeof instrutor.dias_trabalho === 'string' ? JSON.parse(instrutor.dias_trabalho) : instrutor.dias_trabalho) : [];
    
    const html = `
        <div class="row">
            <div class="col-md-8">
                <h4>${instrutor.nome}</h4>
                <p class="text-muted">Credencial: ${instrutor.credencial}</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-${instrutor.ativo ? 'success' : 'danger'} fs-6 me-2">
                    ${instrutor.ativo ? 'Ativo' : 'Inativo'}
                </span>
                <span class="badge bg-${instrutor.disponivel ? 'success' : 'warning'} fs-6">
                    ${instrutor.disponivel ? 'Disponível' : 'Ocupado'}
                </span>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle me-2"></i>Informações Profissionais</h6>
                <p><strong>CFC:</strong> ${instrutor.cfc_nome || 'Não informado'}</p>
                <p><strong>Data de Credenciamento:</strong> ${instrutor.data_credenciamento ? new Date(instrutor.data_credenciamento).toLocaleDateString('pt-BR') : 'Não informado'}</p>
                <p><strong>E-mail:</strong> ${instrutor.email || 'Não informado'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-graduation-cap me-2"></i>Categorias de Habilitação</h6>
                ${categorias.map(cat => `<span class="badge bg-secondary me-1">${cat}</span>`).join('')}
                ${categorias.length === 0 ? '<p class="text-muted">Nenhuma categoria definida</p>' : ''}
            </div>
        </div>
        
        ${horario ? `
        <hr>
        <h6><i class="fas fa-clock me-2"></i>Horário de Trabalho</h6>
        <p><strong>Início:</strong> ${horario.hora_inicio || 'Não informado'}</p>
        <p><strong>Fim:</strong> ${horario.hora_fim || 'Não informado'}</p>
        ` : ''}
        
        ${dias.length > 0 ? `
        <hr>
        <h6><i class="fas fa-calendar me-2"></i>Dias de Trabalho</h6>
        <p>${dias.map(dia => ucfirst(dia)).join(', ')}</p>
        ` : ''}
        
        ${instrutor.especializacoes ? `
        <hr>
        <h6><i class="fas fa-certificate me-2"></i>Especializações</h6>
        <p>${instrutor.especializacoes}</p>
        ` : ''}
        
        ${instrutor.observacoes ? `
        <hr>
        <h6><i class="fas fa-sticky-note me-2"></i>Observações</h6>
        <p>${instrutor.observacoes}</p>
        ` : ''}
    `;
    
    document.getElementById('modalVisualizarInstrutorBody').innerHTML = html;
    document.getElementById('btnEditarVisualizacao').onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('modalVisualizarInstrutor')).hide();
        editarInstrutor(instrutor.id);
    };
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function agendarAula(id) {
    // Redirecionar para página de agendamento
    window.location.href = `pages/agendar-aula.php?instrutor_id=${id}`;
}

function historicoInstrutor(id) {
    // Debug: verificar se a função está sendo chamada
    console.log('Função historicoInstrutor chamada com ID:', id);
    
    // Redirecionar para página de histórico usando o sistema de roteamento do admin
    window.location.href = `?page=historico-instrutor&id=${id}`;
}

function ativarInstrutor(id) {
    if (confirm('Deseja realmente ativar este instrutor?')) {
        alterarStatusInstrutor(id, 1);
    }
}

function desativarInstrutor(id) {
    if (confirm('Deseja realmente desativar este instrutor? Esta ação pode afetar o agendamento de aulas.')) {
        alterarStatusInstrutor(id, 0);
    }
}

function excluirInstrutor(id) {
    const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este instrutor?';
    
    if (confirm(mensagem)) {
        fetch(`../api/instrutores.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Instrutor excluído com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
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

function alterarStatusInstrutor(id, status) {
    if (confirm('Deseja realmente alterar o status deste instrutor?')) {
        // Fazer requisição para a API
        fetch(`../api/instrutores.php`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                id: id,
                ativo: status === 1
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Status do instrutor alterado com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarAlerta(data.error || 'Erro ao alterar status do instrutor', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao alterar status do instrutor', 'danger');
        });
    }
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaInstrutor').value = '';
    filtrarInstrutores();
}

function atualizarEstatisticas() {
    const linhasVisiveis = document.querySelectorAll('#tabelaInstrutores tbody tr:not([style*="display: none"])');
    
    document.getElementById('totalInstrutores').textContent = linhasVisiveis.length;
    
    const ativos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Ativo'
    ).length;
    
    document.getElementById('instrutoresAtivos').textContent = ativos;
}

function salvarInstrutor() {
    const form = document.getElementById('formInstrutor');
    const formData = new FormData(form);
    
    // Validações básicas
    if (!formData.get('usuario_id')) {
        mostrarAlerta('Usuário é obrigatório', 'danger');
        return;
    }
    
    if (!formData.get('cfc_id')) {
        mostrarAlerta('CFC é obrigatório', 'danger');
        return;
    }
    
    if (!formData.get('credencial').trim()) {
        mostrarAlerta('Credencial é obrigatória', 'danger');
        return;
    }
    
    // Preparar dados para envio
    const instrutorData = {
        usuario_id: formData.get('usuario_id'),
        cfc_id: formData.get('cfc_id'),
        credencial: formData.get('credencial').trim(),
        categoria: formData.get('categoria_habilitacao') || '',
        telefone: formData.get('telefone') || '',
        endereco: formData.get('endereco') || '',
        cidade: formData.get('cidade') || '',
        uf: formData.get('uf') || '',
        ativo: formData.get('ativo') === '1'
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
    
    // Fazer requisição para a API
    const url = '../api/instrutores.php';
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
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalInstrutor'));
            modal.hide();
            
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

function exportarInstrutores() {
    // Buscar dados reais da API
    fetch('../api/instrutores.php')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Criar CSV
                let csv = 'Nome,Email,CFC,Credencial,Categorias,Status\n';
                data.data.forEach(instrutor => {
                    csv += `"${instrutor.nome_usuario || ''}","${instrutor.email || ''}","${instrutor.nome_cfc || ''}","${instrutor.credencial || ''}","${instrutor.categoria || ''}","${instrutor.ativo ? 'Ativo' : 'Inativo'}"\n`;
                });
                
                // Download do arquivo
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'instrutores.csv';
                link.click();
                
                mostrarAlerta('Exportação concluída!', 'success');
            } else {
                mostrarAlerta(data.error || 'Erro ao exportar instrutores', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao exportar instrutores. Tente novamente.', 'danger');
        });
}

function imprimirInstrutores() {
    window.print();
}

// Função para mostrar alertas
function mostrarAlerta(mensagem, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.d-flex'));
    
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}
</script>
