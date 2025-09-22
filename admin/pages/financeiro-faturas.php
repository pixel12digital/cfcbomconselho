<?php
/**
 * Página de Faturas (Receitas) - Template
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
        'total_faturas' => $db->count('financeiro_faturas'),
        'faturas_pagas' => $db->count('financeiro_faturas', 'status = ?', ['paga']),
        'faturas_vencidas' => $db->count('financeiro_faturas', 'status = ? AND data_vencimento < ?', ['aberta', date('Y-m-d')]),
        'total_valor' => $db->fetchColumn("SELECT SUM(valor) FROM financeiro_faturas WHERE status = 'aberta'") ?? 0
    ];
} catch (Exception $e) {
    $stats = [
        'total_faturas' => 0,
        'faturas_pagas' => 0,
        'faturas_vencidas' => 0,
        'total_valor' => 0
    ];
}

// Buscar faturas
$filtro_aluno = $_GET['aluno_id'] ?? null;
$filtro_status = $_GET['status'] ?? '';
$filtro_data_inicio = $_GET['data_inicio'] ?? '';
$filtro_data_fim = $_GET['data_fim'] ?? '';

try {
    $where_conditions = [];
    $params = [];
    
    if ($filtro_aluno) {
        $where_conditions[] = "f.aluno_id = ?";
        $params[] = $filtro_aluno;
    }
    
    if ($filtro_status) {
        $where_conditions[] = "f.status = ?";
        $params[] = $filtro_status;
    }
    
    if ($filtro_data_inicio) {
        $where_conditions[] = "f.data_vencimento >= ?";
        $params[] = $filtro_data_inicio;
    }
    
    if ($filtro_data_fim) {
        $where_conditions[] = "f.data_vencimento <= ?";
        $params[] = $filtro_data_fim;
    }
    
    $where_sql = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
    
    $faturas = $db->fetchAll("
        SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf
        FROM financeiro_faturas f
        LEFT JOIN alunos a ON f.aluno_id = a.id
        {$where_sql}
        ORDER BY f.data_vencimento DESC, f.id DESC
        LIMIT 100
    ", $params);
} catch (Exception $e) {
    $faturas = [];
}

// Buscar alunos para filtro
try {
    $alunos = $db->fetchAll("SELECT id, nome, cpf FROM alunos ORDER BY nome");
} catch (Exception $e) {
    $alunos = [];
}
?>

<style>
/* Estilos específicos para faturas */
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
.status-aberta { background-color: #fff3cd; color: #856404; }
.status-paga { background-color: #d4edda; color: #155724; }
.status-vencida { background-color: #f8d7da; color: #721c24; }
.status-parcial { background-color: #d1ecf1; color: #0c5460; }
.status-cancelada { background-color: #e2e3e5; color: #383d41; }
</style>

<!-- Header da página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-file-invoice me-2"></i>Faturas (Receitas)</h2>
        <?php if ($filtro_aluno): ?>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="?page=alunos" class="text-decoration-none">
                        <i class="fas fa-graduation-cap me-1"></i>Alunos
                    </a>
                </li>
                <li class="breadcrumb-item">
                    <a href="?page=historico-aluno&id=<?php echo $filtro_aluno; ?>" class="text-decoration-none">
                        <i class="fas fa-history me-1"></i>Histórico do Aluno
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <i class="fas fa-dollar-sign me-1"></i>Faturas
                </li>
            </ol>
        </nav>
        <?php endif; ?>
    </div>
    <button class="btn btn-primary" onclick="novaFatura()">
        <i class="fas fa-plus me-1"></i>Nova Fatura
    </button>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Total de Faturas</h6>
                    <h3 class="mb-0"><?php echo $stats['total_faturas']; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-file-invoice fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Faturas Pagas</h6>
                    <h3 class="mb-0"><?php echo $stats['faturas_pagas']; ?></h3>
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
                    <h6 class="mb-0">Faturas Vencidas</h6>
                    <h3 class="mb-0"><?php echo $stats['faturas_vencidas']; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card info">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Valor em Aberto</h6>
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
            <input type="hidden" name="page" value="financeiro-faturas">
            <div class="col-md-3">
                <label for="aluno_id" class="form-label">Aluno</label>
                <select class="form-select" id="aluno_id" name="aluno_id">
                    <option value="">Todos os alunos</option>
                    <?php foreach ($alunos as $aluno): ?>
                    <option value="<?php echo $aluno['id']; ?>" <?php echo $filtro_aluno == $aluno['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo $aluno['cpf']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="aberta" <?php echo $filtro_status === 'aberta' ? 'selected' : ''; ?>>Aberta</option>
                    <option value="paga" <?php echo $filtro_status === 'paga' ? 'selected' : ''; ?>>Paga</option>
                    <option value="vencida" <?php echo $filtro_status === 'vencida' ? 'selected' : ''; ?>>Vencida</option>
                    <option value="parcial" <?php echo $filtro_status === 'parcial' ? 'selected' : ''; ?>>Parcial</option>
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
                    <a href="?page=financeiro-faturas" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i>Limpar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Faturas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Faturas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Aluno</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th>Vencimento</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faturas)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhuma fatura encontrada</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($faturas as $fatura): ?>
                    <tr>
                        <td><?php echo $fatura['id']; ?></td>
                        <td>
                            <?php if ($fatura['aluno_nome']): ?>
                            <strong><?php echo htmlspecialchars($fatura['aluno_nome']); ?></strong><br>
                            <small class="text-muted"><?php echo $fatura['aluno_cpf']; ?></small>
                            <?php else: ?>
                            <span class="text-muted">Aluno não encontrado</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($fatura['descricao']); ?></td>
                        <td><strong>R$ <?php echo number_format($fatura['valor'], 2, ',', '.'); ?></strong></td>
                        <td><?php echo date('d/m/Y', strtotime($fatura['data_vencimento'])); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $fatura['status']; ?>">
                                <?php echo ucfirst($fatura['status']); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="visualizarFatura(<?php echo $fatura['id']; ?>)" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="marcarComoPaga(<?php echo $fatura['id']; ?>)" title="Marcar como Paga">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="cancelarFatura(<?php echo $fatura['id']; ?>)" title="Cancelar">
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
function novaFatura() {
    // Implementar modal para nova fatura
    alert('Funcionalidade de nova fatura será implementada em breve.');
}

function visualizarFatura(id) {
    // Implementar visualização da fatura
    alert('Visualização da fatura ' + id + ' será implementada em breve.');
}

function marcarComoPaga(id) {
    if (confirm('Deseja marcar esta fatura como paga?')) {
        // Implementar marcação como paga
        alert('Marcação como paga será implementada em breve.');
    }
}

function cancelarFatura(id) {
    if (confirm('Deseja cancelar esta fatura?')) {
        // Implementar cancelamento
        alert('Cancelamento será implementado em breve.');
    }
}
</script>
