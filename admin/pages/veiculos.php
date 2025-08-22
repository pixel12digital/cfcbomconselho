<?php
// Verificar se as variáveis estão definidas
if (!isset($veiculos)) $veiculos = [];
if (!isset($cfcs)) $cfcs = [];
if (!isset($mensagem)) $mensagem = '';
if (!isset($tipo_mensagem)) $tipo_mensagem = 'info';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">
        <i class="fas fa-car me-2"></i>Gestão de Veículos
    </h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportarVeiculos()">
                <i class="fas fa-download me-1"></i>Exportar
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="imprimirVeiculos()">
                <i class="fas fa-print me-1"></i>Imprimir
            </button>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVeiculo">
            <i class="fas fa-plus me-1"></i>Novo Veículo
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
            <input type="text" class="form-control" id="buscaVeiculo" placeholder="Buscar veículo por placa, modelo ou CFC...">
        </div>
    </div>
    <div class="col-md-2">
        <select class="form-select" id="filtroStatus">
            <option value="">Todos os Status</option>
            <option value="ativo">Ativo</option>
            <option value="inativo">Inativo</option>
            <option value="manutencao">Em Manutenção</option>
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
                            Total de Veículos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalVeiculos">
                            <?php echo count($veiculos); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-car fa-2x text-gray-300"></i>
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
                            Veículos Ativos
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="veiculosAtivos">
                            <?php echo count(array_filter($veiculos, function($v) { return $v['ativo']; })); ?>
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
        <div class="card border-left-warning shadow h-100 py-2">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                            Em Manutenção
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="emManutencao">
                            <?php echo count(array_filter($veiculos, function($v) { return $v['status'] === 'manutencao'; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-tools fa-2x text-gray-300"></i>
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
                            Disponíveis Hoje
                        </div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800" id="disponiveisHoje">
                            <?php echo count(array_filter($veiculos, function($v) { return $v['ativo'] && $v['disponivel']; })); ?>
                        </div>
                    </div>
                    <div class="col-auto">
                        <i class="fas fa-calendar-check fa-2x text-gray-300"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Veículos -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Veículos</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover" id="tabelaVeiculos">
                <thead class="table-dark">
                    <tr>
                        <th>ID</th>
                        <th>Veículo</th>
                        <th>Placa</th>
                        <th>CFC</th>
                        <th>Categoria</th>
                        <th>Status</th>
                        <th>Disponibilidade</th>
                        <th>Próxima Manutenção</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($veiculos)): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhum veículo cadastrado ainda.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalVeiculo">
                                <i class="fas fa-plus me-1"></i>Cadastrar Primeiro Veículo
                            </button>
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($veiculos as $veiculo): ?>
                        <tr data-veiculo-id="<?php echo $veiculo['id']; ?>">
                            <td><?php echo $veiculo['id']; ?></td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm me-3">
                                        <div class="avatar-title bg-primary rounded-circle">
                                            <i class="fas fa-car"></i>
                                        </div>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($veiculo['marca'] . ' ' . $veiculo['modelo']); ?></strong>
                                        <br><small class="text-muted">Ano: <?php echo $veiculo['ano']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code class="fs-6"><?php echo htmlspecialchars($veiculo['placa']); ?></code>
                            </td>
                            <td>
                                <span class="badge bg-info"><?php echo htmlspecialchars($veiculo['cfc_nome'] ?? 'N/A'); ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary"><?php echo htmlspecialchars($veiculo['categoria_cnh']); ?></span>
                            </td>
                            <td>
                                <?php
                                $statusClass = [
                                    'ativo' => 'success',
                                    'inativo' => 'danger',
                                    'manutencao' => 'warning'
                                ];
                                $statusText = [
                                    'ativo' => 'Ativo',
                                    'inativo' => 'Inativo',
                                    'manutencao' => 'Em Manutenção'
                                ];
                                ?>
                                <span class="badge bg-<?php echo $statusClass[$veiculo['status']] ?? 'secondary'; ?>">
                                    <?php echo $statusText[$veiculo['status']] ?? ucfirst($veiculo['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($veiculo['disponivel']): ?>
                                    <span class="badge bg-success">Disponível</span>
                                <?php else: ?>
                                    <span class="badge bg-warning">Ocupado</span>
                                <?php endif; ?>
                                <br><small class="text-muted">
                                    <?php echo $veiculo['aulas_hoje'] ?? 0; ?> aulas hoje
                                </small>
                            </td>
                            <td>
                                <?php if ($veiculo['proxima_manutencao']): ?>
                                    <small><?php echo date('d/m/Y', strtotime($veiculo['proxima_manutencao'])); ?></small>
                                    <?php 
                                    $dias_manutencao = (strtotime($veiculo['proxima_manutencao']) - time()) / (60 * 60 * 24);
                                    if ($dias_manutencao <= 7): ?>
                                        <br><span class="badge bg-danger">Urgente</span>
                                    <?php elseif ($dias_manutencao <= 30): ?>
                                        <br><span class="badge bg-warning">Próximo</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted">Não agendada</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="action-buttons-container">
                                    <!-- Botões principais em linha -->
                                    <div class="action-buttons-primary">
                                        <button type="button" class="btn btn-edit action-btn" 
                                                onclick="editarVeiculo(<?php echo $veiculo['id']; ?>)" 
                                                title="Editar dados do veículo">
                                            <i class="fas fa-edit me-1"></i>Editar
                                        </button>
                                        <button type="button" class="btn btn-view action-btn" 
                                                onclick="visualizarVeiculo(<?php echo $veiculo['id']; ?>)" 
                                                title="Ver detalhes completos do veículo">
                                            <i class="fas fa-eye me-1"></i>Ver
                                        </button>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="agendarAula(<?php echo $veiculo['id']; ?>)" 
                                                title="Agendar aula usando este veículo">
                                            <i class="fas fa-calendar-plus me-1"></i>Agendar
                                        </button>
                                    </div>
                                    
                                    <!-- Botões secundários em linha -->
                                    <div class="action-buttons-secondary">
                                        <button type="button" class="btn btn-maintenance action-btn" 
                                                onclick="agendarManutencao(<?php echo $veiculo['id']; ?>)" 
                                                title="Agendar manutenção para este veículo">
                                            <i class="fas fa-tools me-1"></i>Manutenção
                                        </button>
                                        <?php if ($veiculo['ativo']): ?>
                                        <button type="button" class="btn btn-toggle action-btn" 
                                                onclick="desativarVeiculo(<?php echo $veiculo['id']; ?>)" 
                                                title="Desativar veículo (não poderá ser usado em aulas)">
                                            <i class="fas fa-ban me-1"></i>Desativar
                                        </button>
                                        <?php else: ?>
                                        <button type="button" class="btn btn-schedule action-btn" 
                                                onclick="ativarVeiculo(<?php echo $veiculo['id']; ?>)" 
                                                title="Reativar veículo para uso em aulas">
                                            <i class="fas fa-check me-1"></i>Ativar
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <!-- Botão de exclusão destacado -->
                                    <div class="action-buttons-danger">
                                        <button type="button" class="btn btn-delete action-btn" 
                                                onclick="excluirVeiculo(<?php echo $veiculo['id']; ?>)" 
                                                title="⚠️ EXCLUIR VEÍCULO - Esta ação não pode ser desfeita!">
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

<!-- Modal para Cadastro/Edição de Veículo -->
<div class="modal fade" id="modalVeiculo" tabindex="-1" aria-labelledby="modalVeiculoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formVeiculo" method="POST" action="admin/pages/veiculos.php">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVeiculoLabel">
                        <i class="fas fa-car me-2"></i><span id="modalTitle">Novo Veículo</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="acao" id="acaoVeiculo" value="criar">
                    <input type="hidden" name="veiculo_id" id="veiculo_id" value="">
                    
                    <div class="row">
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="placa" class="form-label">Placa *</label>
                                <input type="text" class="form-control" id="placa" name="placa" required 
                                       placeholder="ABC-1234" maxlength="8">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="marca" class="form-label">Marca *</label>
                                <input type="text" class="form-control" id="marca" name="marca" required 
                                       placeholder="Ex: Fiat, Volkswagen, Chevrolet...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="modelo" class="form-label">Modelo *</label>
                                <input type="text" class="form-control" id="modelo" name="modelo" required 
                                       placeholder="Ex: Uno, Gol, Onix...">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="ano" class="form-label">Ano *</label>
                                <input type="number" class="form-control" id="ano" name="ano" required 
                                       min="1900" max="<?php echo date('Y') + 1; ?>" 
                                       value="<?php echo date('Y'); ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="categoria_cnh" class="form-label">Categoria CNH *</label>
                                <select class="form-select" id="categoria_cnh" name="categoria_cnh" required>
                                    <option value="">Selecione a categoria...</option>
                                    <option value="A">A - Motocicletas</option>
                                    <option value="B">B - Automóveis</option>
                                    <option value="C">C - Veículos de carga</option>
                                    <option value="D">D - Veículos de passageiros</option>
                                    <option value="E">E - Veículos com reboque</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="cor" class="form-label">Cor</label>
                                <input type="text" class="form-control" id="cor" name="cor" 
                                       placeholder="Ex: Branco, Prata, Preto...">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="chassi" class="form-label">Chassi</label>
                                <input type="text" class="form-control" id="chassi" name="chassi" 
                                       placeholder="Número do chassi">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="renavam" class="form-label">RENAVAM</label>
                                <input type="text" class="form-control" id="renavam" name="renavam" 
                                       placeholder="Número do RENAVAM">
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_aquisicao" class="form-label">Data de Aquisição</label>
                                <input type="date" class="form-control" id="data_aquisicao" name="data_aquisicao">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="valor_aquisicao" class="form-label">Valor de Aquisição</label>
                                <div class="input-group">
                                    <span class="input-group-text">R$</span>
                                    <input type="text" class="form-control" id="valor_aquisicao" name="valor_aquisicao" 
                                           placeholder="0,00">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="quilometragem" class="form-label">Quilometragem Atual</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="quilometragem" name="quilometragem" 
                                           min="0" placeholder="0">
                                    <span class="input-group-text">km</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="combustivel" class="form-label">Tipo de Combustível</label>
                                <select class="form-select" id="combustivel" name="combustivel">
                                    <option value="">Selecione...</option>
                                    <option value="gasolina">Gasolina</option>
                                    <option value="etanol">Etanol</option>
                                    <option value="flex">Flex (Gasolina/Etanol)</option>
                                    <option value="diesel">Diesel</option>
                                    <option value="eletrico">Elétrico</option>
                                    <option value="hibrido">Híbrido</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="ativo">Ativo</option>
                                    <option value="inativo">Inativo</option>
                                    <option value="manutencao">Em Manutenção</option>
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
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="proxima_manutencao" class="form-label">Próxima Manutenção</label>
                                <input type="date" class="form-control" id="proxima_manutencao" name="proxima_manutencao">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="km_manutencao" class="form-label">KM para Manutenção</label>
                                <div class="input-group">
                                    <input type="number" class="form-control" id="km_manutencao" name="km_manutencao" 
                                           min="0" placeholder="0">
                                    <span class="input-group-text">km</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" 
                                  placeholder="Informações adicionais sobre o veículo..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarVeiculo">
                        <i class="fas fa-save me-1"></i>Salvar Veículo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para Visualização de Veículo -->
<div class="modal fade" id="modalVisualizarVeiculo" tabindex="-1" aria-labelledby="modalVisualizarVeiculoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarVeiculoLabel">
                    <i class="fas fa-eye me-2"></i>Detalhes do Veículo
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalVisualizarVeiculoBody">
                <!-- Conteúdo será carregado via JavaScript -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                <button type="button" class="btn btn-primary" id="btnEditarVisualizacao">
                    <i class="fas fa-edit me-1"></i>Editar Veículo
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts específicos para Veículos -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar máscaras
    inicializarMascarasVeiculo();
    
    // Inicializar filtros
    inicializarFiltrosVeiculo();
    
    // Inicializar busca
    inicializarBuscaVeiculo();
});

function inicializarMascarasVeiculo() {
    // Máscara para placa
    if (typeof IMask !== 'undefined') {
        new IMask(document.getElementById('placa'), {
            mask: 'aaa-0000'
        });
        
        // Máscara para valor
        new IMask(document.getElementById('valor_aquisicao'), {
            mask: Number,
            scale: 2,
            thousandsSeparator: '.',
            padFractionalZeros: true,
            radix: ',',
            mapToRadix: ['.']
        });
    }
}

function inicializarFiltrosVeiculo() {
    // Filtro por status
    document.getElementById('filtroStatus').addEventListener('change', filtrarVeiculos);
    
    // Filtro por CFC
    document.getElementById('filtroCFC').addEventListener('change', filtrarVeiculos);
    
    // Filtro por categoria
    document.getElementById('filtroCategoria').addEventListener('change', filtrarVeiculos);
}

function filtrarVeiculos() {
    const status = document.getElementById('filtroStatus').value;
    const cfc = document.getElementById('filtroCFC').value;
    const categoria = document.getElementById('filtroCategoria').value;
    const busca = document.getElementById('buscaVeiculo').value.toLowerCase();
    
    const linhas = document.querySelectorAll('#tabelaVeiculos tbody tr');
    
    linhas.forEach(linha => {
        let mostrar = true;
        
        // Filtro por status
        if (status && linha.querySelector('td:nth-child(6) .badge').textContent !== status) {
            mostrar = false;
        }
        
        // Filtro por CFC
        if (cfc) {
            const cfcLinha = linha.querySelector('td:nth-child(4) .badge').textContent;
            if (cfcLinha === 'N/A' || cfcLinha !== cfc) {
                mostrar = false;
            }
        }
        
        // Filtro por categoria
        if (categoria && linha.querySelector('td:nth-child(5) .badge').textContent !== categoria) {
            mostrar = false;
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

function inicializarBuscaVeiculo() {
    document.getElementById('buscaVeiculo').addEventListener('input', filtrarVeiculos);
}

function editarVeiculo(id) {
    // Buscar dados do veículo
    fetch(`admin/api/veiculos.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherFormularioVeiculo(data.veiculo);
                document.getElementById('modalTitle').textContent = 'Editar Veículo';
                document.getElementById('acaoVeiculo').value = 'editar';
                document.getElementById('veiculo_id').value = id;
                
                const modal = new bootstrap.Modal(document.getElementById('modalVeiculo'));
                modal.show();
            } else {
                mostrarAlerta('Erro ao carregar dados do veículo', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do veículo', 'danger');
        });
}

function preencherFormularioVeiculo(veiculo) {
    document.getElementById('cfc_id').value = veiculo.cfc_id || '';
    document.getElementById('placa').value = veiculo.placa || '';
    document.getElementById('marca').value = veiculo.marca || '';
    document.getElementById('modelo').value = veiculo.modelo || '';
    document.getElementById('ano').value = veiculo.ano || '';
    document.getElementById('categoria_cnh').value = veiculo.categoria_cnh || '';
    document.getElementById('cor').value = veiculo.cor || '';
    document.getElementById('chassi').value = veiculo.chassi || '';
    document.getElementById('renavam').value = veiculo.renavam || '';
    document.getElementById('data_aquisicao').value = veiculo.data_aquisicao || '';
    document.getElementById('valor_aquisicao').value = veiculo.valor_aquisicao || '';
    document.getElementById('quilometragem').value = veiculo.quilometragem || '';
    document.getElementById('combustivel').value = veiculo.combustivel || '';
    document.getElementById('status').value = veiculo.status || 'ativo';
    document.getElementById('disponivel').value = veiculo.disponivel ? '1' : '0';
    document.getElementById('proxima_manutencao').value = veiculo.proxima_manutencao || '';
    document.getElementById('km_manutencao').value = veiculo.km_manutencao || '';
    document.getElementById('observacoes').value = veiculo.observacoes || '';
}

function visualizarVeiculo(id) {
    fetch(`admin/api/veiculos.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                preencherModalVisualizacao(data.veiculo);
                const modal = new bootstrap.Modal(document.getElementById('modalVisualizarVeiculo'));
                modal.show();
            } else {
                mostrarAlerta('Erro ao carregar dados do veículo', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao carregar dados do veículo', 'danger');
        });
}

function preencherModalVisualizacao(veiculo) {
    const html = `
        <div class="row">
            <div class="col-md-8">
                <h4>${veiculo.marca} ${veiculo.modelo}</h4>
                <p class="text-muted">Placa: ${veiculo.placa} | Ano: ${veiculo.ano}</p>
            </div>
            <div class="col-md-4 text-end">
                <span class="badge bg-${veiculo.status === 'ativo' ? 'success' : (veiculo.status === 'manutencao' ? 'warning' : 'danger')} fs-6 me-2">
                    ${veiculo.status === 'ativo' ? 'Ativo' : (veiculo.status === 'manutencao' ? 'Em Manutenção' : 'Inativo')}
                </span>
                <span class="badge bg-${veiculo.disponivel ? 'success' : 'warning'} fs-6">
                    ${veiculo.disponivel ? 'Disponível' : 'Ocupado'}
                </span>
            </div>
        </div>
        
        <hr>
        
        <div class="row">
            <div class="col-md-6">
                <h6><i class="fas fa-info-circle me-2"></i>Informações do Veículo</h6>
                <p><strong>CFC:</strong> ${veiculo.cfc_nome || 'Não informado'}</p>
                <p><strong>Categoria:</strong> <span class="badge bg-secondary">${veiculo.categoria_cnh}</span></p>
                <p><strong>Cor:</strong> ${veiculo.cor || 'Não informado'}</p>
                <p><strong>Combustível:</strong> ${veiculo.combustivel ? ucfirst(veiculo.combustivel) : 'Não informado'}</p>
            </div>
            <div class="col-md-6">
                <h6><i class="fas fa-cogs me-2"></i>Especificações Técnicas</h6>
                <p><strong>Chassi:</strong> ${veiculo.chassi || 'Não informado'}</p>
                <p><strong>RENAVAM:</strong> ${veiculo.renavam || 'Não informado'}</p>
                <p><strong>Quilometragem:</strong> ${veiculo.quilometragem ? veiculo.quilometragem + ' km' : 'Não informado'}</p>
                <p><strong>KM para Manutenção:</strong> ${veiculo.km_manutencao ? veiculo.km_manutencao + ' km' : 'Não informado'}</p>
            </div>
        </div>
        
        ${veiculo.data_aquisicao || veiculo.valor_aquisicao ? `
        <hr>
        <h6><i class="fas fa-dollar-sign me-2"></i>Informações de Aquisição</h6>
        ${veiculo.data_aquisicao ? `<p><strong>Data de Aquisição:</strong> ${new Date(veiculo.data_aquisicao).toLocaleDateString('pt-BR')}</p>` : ''}
        ${veiculo.valor_aquisicao ? `<p><strong>Valor de Aquisição:</strong> R$ ${parseFloat(veiculo.valor_aquisicao).toLocaleString('pt-BR', {minimumFractionDigits: 2})}</p>` : ''}
        ` : ''}
        
        ${veiculo.proxima_manutencao ? `
        <hr>
        <h6><i class="fas fa-tools me-2"></i>Manutenção</h6>
        <p><strong>Próxima Manutenção:</strong> ${new Date(veiculo.proxima_manutencao).toLocaleDateString('pt-BR')}</p>
        ` : ''}
        
        ${veiculo.observacoes ? `
        <hr>
        <h6><i class="fas fa-sticky-note me-2"></i>Observações</h6>
        <p>${veiculo.observacoes}</p>
        ` : ''}
    `;
    
    document.getElementById('modalVisualizarVeiculoBody').innerHTML = html;
    document.getElementById('btnEditarVisualizacao').onclick = () => {
        bootstrap.Modal.getInstance(document.getElementById('modalVisualizarVeiculo')).hide();
        editarVeiculo(veiculo.id);
    };
}

function ucfirst(str) {
    return str.charAt(0).toUpperCase() + str.slice(1);
}

function agendarAula(id) {
    // Redirecionar para página de agendamento
    window.location.href = `pages/agendar-aula.php?veiculo_id=${id}`;
}

function agendarManutencao(id) {
    // Redirecionar para página de agendamento de manutenção
    window.location.href = `admin/pages/agendar-manutencao.php?veiculo_id=${id}`;
}

function ativarVeiculo(id) {
    if (confirm('Deseja realmente ativar este veículo?')) {
        alterarStatusVeiculo(id, 'ativo');
    }
}

function desativarVeiculo(id) {
    if (confirm('Deseja realmente desativar este veículo? Esta ação pode afetar o agendamento de aulas.')) {
        alterarStatusVeiculo(id, 'inativo');
    }
}

function excluirVeiculo(id) {
    const mensagem = '⚠️ ATENÇÃO: Esta ação não pode ser desfeita!\n\nDeseja realmente excluir este veículo?';
    
    if (confirm(mensagem)) {
        fetch(`../api/veiculos.php`, {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarAlerta('Veículo excluído com sucesso!', 'success');
                setTimeout(() => {
                    location.reload();
                }, 1500);
            } else {
                mostrarAlerta(data.error || 'Erro ao excluir veículo', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            mostrarAlerta('Erro ao excluir veículo', 'danger');
        });
    }
}

function alterarStatusVeiculo(id, status) {
    const formData = new FormData();
    formData.append('acao', 'alterar_status');
    formData.append('veiculo_id', id);
    formData.append('status', status);
    
    fetch('admin/pages/veiculos.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        location.reload();
    })
    .catch(error => {
        console.error('Erro:', error);
        mostrarAlerta('Erro ao alterar status do veículo', 'danger');
    });
}

function limparFiltros() {
    document.getElementById('filtroStatus').value = '';
    document.getElementById('filtroCFC').value = '';
    document.getElementById('filtroCategoria').value = '';
    document.getElementById('buscaVeiculo').value = '';
    filtrarVeiculos();
}

function atualizarEstatisticas() {
    const linhasVisiveis = document.querySelectorAll('#tabelaVeiculos tbody tr:not([style*="display: none"])');
    
    document.getElementById('totalVeiculos').textContent = linhasVisiveis.length;
    
    const ativos = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Ativo'
    ).length;
    
    const manutencao = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(6) .badge').textContent === 'Em Manutenção'
    ).length;
    
    const disponiveis = Array.from(linhasVisiveis).filter(linha => 
        linha.querySelector('td:nth-child(7) .badge').textContent === 'Disponível'
    ).length;
    
    document.getElementById('veiculosAtivos').textContent = ativos;
    document.getElementById('emManutencao').textContent = manutencao;
    document.getElementById('disponiveisHoje').textContent = disponiveis;
}

function exportarVeiculos() {
    // Implementar exportação para Excel/CSV
    alert('Funcionalidade de exportação será implementada em breve!');
}

function imprimirVeiculos() {
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
