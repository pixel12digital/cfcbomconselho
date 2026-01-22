<?php
/**
 * Página de Despesas (Pagamentos) - Template
 * Sistema CFC - Bom Conselho
 */

// Verificar se sistema financeiro está habilitado
if (!defined('FINANCEIRO_ENABLED') || !FINANCEIRO_ENABLED) {
    echo '<div class="alert alert-danger">Módulo financeiro não está habilitado.</div>';
    return;
}

// Verificar permissões (as variáveis já estão disponíveis do admin/index.php)
if (!$isAdmin && $user['tipo'] !== 'secretaria') {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Obter estatísticas
try {
    $stats = [
        'total_despesas' => $db->count('financeiro_despesas'),
        'despesas_pagas' => $db->count('financeiro_despesas', 'status = ?', ['paga']),
        'despesas_pendentes' => $db->count('financeiro_despesas', 'status = ?', ['pendente']),
        'total_valor' => $db->fetchColumn("SELECT SUM(valor) FROM financeiro_despesas WHERE status = 'pendente'") ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_despesas' => 0,
        'despesas_pagas' => 0,
        'despesas_pendentes' => 0,
        'total_valor' => 0
    ];
}

// Buscar despesas
$filtro_categoria = $_GET['categoria'] ?? '';
$filtro_status = $_GET['status'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($filtro_categoria) {
        $where_conditions[] = "categoria = ?";
        $params[] = $filtro_categoria;
    }
    
    if ($filtro_status) {
        $where_conditions[] = "status = ?";
        $params[] = $filtro_status;
    }
    
    if ($filtro_data_inicio) {
        $where_conditions[] = "data_vencimento >= ?";
        $params[] = $filtro_data_inicio;
    }
    
    if ($filtro_data_fim) {
        $where_conditions[] = "data_vencimento <= ?";
        $params[] = $filtro_data_fim;
    }
    
    $where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $despesas = $db->fetchAll("
        SELECT *
        FROM financeiro_despesas
        {$where_sql}
        ORDER BY data_vencimento DESC, id DESC
        LIMIT 100
    ", $params);
} catch (Exception $e) {
    $despesas = [];
}
?>

<style>
/* Estilos específicos para despesas */
.stats-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 10px;
    padding: 1.5rem;
    margin-bottom: 1rem;
}
.stats-card.success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
}
.stats-card.warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
}
.stats-card.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
}
.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-pendente { background-color: #fff3cd; color: #856404; }
.status-paga { background-color: #d4edda; color: #155724; }
.status-vencida { background-color: #f8d7da; color: #721c24; }
.status-cancelada { background-color: #e2e3e5; color: #383d41; }
</style>

<!-- Header da página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-receipt me-2"></i>Despesas (Pagamentos)</h2>
        <p class="text-muted mb-0">Gerencie as despesas e pagamentos do sistema</p>
    </div>
    <button class="btn btn-primary" onclick="novaDespesa()">
        <i class="fas fa-plus me-1"></i>Nova Despesa
    </button>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Total de Despesas</h6>
                    <h3 class="mb-0"><?php echo $stats['total_despesas']; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-receipt fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Despesas Pagas</h6>
                    <h3 class="mb-0"><?php echo $stats['despesas_pagas']; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-check-circle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card warning">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Pendentes</h6>
                    <h3 class="mb-0"><?php echo $stats['despesas_pendentes']; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-clock fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card info">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Valor Pendente</h6>
                    <h3 class="mb-0">R$ <?php echo number_format($stats['total_valor'], 2, ',', '.'); ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-money-bill-wave fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="card mb-4">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros</h5>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3">
            <input type="hidden" name="page" value="financeiro-despesas">
            <div class="col-md-3">
                <label for="categoria" class="form-label">Categoria</label>
                <select class="form-select" id="categoria" name="categoria">
                    <option value="">Todas as categorias</option>
                    <option value="combustivel" <?php echo $filtro_categoria === 'combustivel' ? 'selected' : ''; ?>>Combustível</option>
                    <option value="manutencao" <?php echo $filtro_categoria === 'manutencao' ? 'selected' : ''; ?>>Manutenção</option>
                    <option value="salarios" <?php echo $filtro_categoria === 'salarios' ? 'selected' : ''; ?>>Salários</option>
                    <option value="aluguel" <?php echo $filtro_categoria === 'aluguel' ? 'selected' : ''; ?>>Aluguel</option>
                    <option value="outros" <?php echo $filtro_categoria === 'outros' ? 'selected' : ''; ?>>Outros</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="pendente" <?php echo $filtro_status === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                    <option value="paga" <?php echo $filtro_status === 'paga' ? 'selected' : ''; ?>>Paga</option>
                    <option value="vencida" <?php echo $filtro_status === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                    <option value="cancelada" <?php echo $filtro_status === 'cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $filtro_data_inicio; ?>">
            </div>
            <div class="col-md-2">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $filtro_data_fim; ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i>Filtrar
                    </button>
                    <a href="?page=financeiro-despesas" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Despesas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Despesas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Descrição</th>
                        <th>Categoria</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($despesas)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhuma despesa encontrada</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($despesas as $despesa): ?>
                    <tr>
                        <td><?php echo $despesa['id']; ?></td>
                        <td><?php echo htmlspecialchars($despesa['descricao']); ?></td>
                        <td>
                            <span class="badge bg-secondary">
                                <?php echo ucfirst($despesa['categoria']); ?>
                            </span>
                        </td>
                        <td><strong>R$ <?php echo number_format($despesa['valor'], 2, ',', '.'); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($despesa['data_vencimento'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $despesa['status']; ?>">
                                <?php echo ucfirst($despesa['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="visualizarDespesa(<?php echo $despesa['id']; ?>)" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="marcarComoPaga(<?php echo $despesa['id']; ?>)" title="Marcar como Paga">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="cancelarDespesa(<?php echo $despesa['id']; ?>)" title="Cancelar">
                                    <i class="fas fa-times"></i>
                                </button>
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

<script>
function novaDespesa() {
    alert('Funcionalidade de nova despesa será implementada em breve.');
}

function visualizarDespesa(id) {
    alert('Visualização da despesa ' + id + ' será implementada em breve.');
}

function marcarComoPaga(id) {
    if (confirm('Deseja marcar esta despesa como paga?')) {
        alert('Marcação como paga será implementada em breve.');
    }
}

function cancelarDespesa(id) {
    if (confirm('Deseja cancelar esta despesa?')) {
        alert('Cancelamento será implementado em breve.');
    }
}
</script>
