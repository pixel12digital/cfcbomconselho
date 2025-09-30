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
/* Estilos específicos para faturas - Alinhado ao template do projeto */
.stats-card {
    background: var(--white);
    color: var(--gray-700);
    border: 1px solid var(--gray-200);
    border-radius: var(--border-radius);
    padding: 1.5rem;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
    transition: var(--transition-normal);
    position: relative;
    overflow: hidden;
}

.stats-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 4px;
    height: 100%;
    background: var(--primary-color);
}

.stats-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}

.stats-card.success::before {
    background: var(--success-color);
}

.stats-card.warning::before {
    background: var(--warning-color);
}

.stats-card.info::before {
    background: var(--info-color);
}

.stats-card h6 {
    color: var(--gray-500);
    font-size: var(--font-size-sm);
    font-weight: var(--font-weight-medium);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.stats-card h3 {
    color: var(--gray-800);
    font-size: var(--font-size-2xl);
    font-weight: var(--font-weight-bold);
    margin: 0;
}

.stats-card .fas {
    color: var(--gray-400);
    opacity: 0.7;
}

.stats-card.success .fas {
    color: var(--success-color);
}

.stats-card.warning .fas {
    color: var(--warning-color);
}

.stats-card.info .fas {
    color: var(--info-color);
}

.status-badge {
    padding: 0.25rem 0.75rem;
    border-radius: var(--border-radius);
    font-size: var(--font-size-xs);
    font-weight: var(--font-weight-medium);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-aberta { 
    background-color: var(--blue-100); 
    color: var(--blue-800); 
    border: 1px solid var(--blue-200);
}

.status-paga { 
    background-color: #dcfce7; 
    color: #166534; 
    border: 1px solid #bbf7d0;
}

.status-vencida { 
    background-color: #fef2f2; 
    color: #991b1b; 
    border: 1px solid #fecaca;
}

.status-parcial { 
    background-color: var(--blue-50); 
    color: var(--blue-700); 
    border: 1px solid var(--blue-200);
}

.status-cancelada { 
    background-color: var(--gray-100); 
    color: var(--gray-600); 
    border: 1px solid var(--gray-200);
}
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

<!-- Modal Nova Fatura -->
<div class="modal fade" id="modalNovaFatura" tabindex="-1" aria-labelledby="modalNovaFaturaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaFaturaLabel">
                    <i class="fas fa-plus me-2"></i>Nova Fatura
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovaFatura">
                <div class="modal-body">
                    <!-- Seção: Informações Básicas -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Informações Básicas
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="aluno_id_modal" class="form-label fw-semibold">
                                    Aluno <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg" id="aluno_id_modal" name="aluno_id" required>
                                    <option value="">Selecione um aluno</option>
                                    <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo $aluno['cpf']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="valor_total" class="form-label fw-semibold">
                                    Valor Total <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <span class="input-group-text bg-primary text-white">
                                        <i class="fas fa-dollar-sign"></i>
                                    </span>
                                    <input type="number" class="form-control" id="valor_total" name="valor_total" 
                                           step="0.01" min="0" required placeholder="0,00">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4">

                    <!-- Seção: Parcelamento -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-calculator me-2"></i>Parcelamento
                        </h6>
                        
                        <!-- Checkbox de Parcelamento -->
                        <div class="row mb-3">
                            <div class="col-12">
                                <div class="form-check form-switch form-check-lg">
                                    <input class="form-check-input" type="checkbox" id="parcelamento" name="parcelamento">
                                    <label class="form-check-label fw-semibold" for="parcelamento">
                                        <i class="fas fa-credit-card me-2"></i>Parcelar esta fatura
                                    </label>
                                </div>
                                <small class="text-muted">Marque para dividir o valor em múltiplas faturas</small>
                            </div>
                        </div>
                        
                        <!-- Configurações de Parcelamento -->
                        <div id="config-parcelamento" style="display: none;">
                            <div class="card border-primary">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-primary">
                                        <i class="fas fa-cog me-2"></i>Configuração de Parcelas
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-4">
                                        <div class="col-12">
                                            <label for="entrada" class="form-label fw-semibold fs-6">
                                                <i class="fas fa-hand-holding-usd me-1"></i>Entrada
                                            </label>
                                            <div class="input-group input-group-lg">
                                                <span class="input-group-text bg-success text-white fw-bold">R$</span>
                                                <input type="number" class="form-control form-control-lg" id="entrada" name="entrada" 
                                                       step="0.01" min="0" value="0" placeholder="0,00">
                                            </div>
                                            <small class="text-muted mt-2 d-block">Valor pago antecipadamente (opcional)</small>
                                        </div>
                                        <div class="col-12">
                                            <label for="num_parcelas" class="form-label fw-semibold fs-6">
                                                <i class="fas fa-list-ol me-1"></i>Número de Parcelas
                                            </label>
                                            <select class="form-select form-select-lg" id="num_parcelas" name="num_parcelas">
                                                <option value="2">2 parcelas</option>
                                                <option value="3">3 parcelas</option>
                                                <option value="4" selected>4 parcelas</option>
                                                <option value="5">5 parcelas</option>
                                                <option value="6">6 parcelas</option>
                                                <option value="8">8 parcelas</option>
                                                <option value="10">10 parcelas</option>
                                                <option value="12">12 parcelas</option>
                                            </select>
                                            <small class="text-muted mt-2 d-block">Quantidade de parcelas para dividir o valor</small>
                                        </div>
                                        <div class="col-12">
                                            <label for="intervalo_parcelas" class="form-label fw-semibold fs-6">
                                                <i class="fas fa-calendar-alt me-1"></i>Intervalo (dias)
                                            </label>
                                            <select class="form-select form-select-lg" id="intervalo_parcelas" name="intervalo_parcelas">
                                                <option value="30" selected>30 dias</option>
                                                <option value="15">15 dias</option>
                                                <option value="45">45 dias</option>
                                                <option value="60">60 dias</option>
                                            </select>
                                            <small class="text-muted mt-2 d-block">Intervalo entre cada parcela</small>
                                        </div>
                                    </div>
                                    
                                    <!-- Resumo das Parcelas -->
                                    <div id="resumo-parcelas" class="mt-4" style="display: none;">
                                        <h6 class="text-success mb-3">
                                            <i class="fas fa-table me-2"></i>Resumo das Parcelas
                                        </h6>
                                        <div class="table-responsive">
                                            <table class="table table-hover table-bordered">
                                                <thead class="table-success">
                                                    <tr>
                                                        <th class="text-center">Parcela</th>
                                                        <th class="text-center">Valor</th>
                                                        <th class="text-center">Vencimento</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="tabela-parcelas" class="table-light">
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4">

                    <!-- Seção: Detalhes da Fatura -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-file-invoice me-2"></i>Detalhes da Fatura
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="data_vencimento" class="form-label fw-semibold">
                                    <i class="fas fa-calendar me-1"></i>Data de Vencimento <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg" id="data_vencimento" name="data_vencimento" required>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold">
                                    <i class="fas fa-flag me-1"></i>Status
                                </label>
                                <select class="form-select form-select-lg" id="status" name="status">
                                    <option value="aberta" selected>Aberta</option>
                                    <option value="paga">Paga</option>
                                    <option value="parcial">Parcial</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4">

                    <!-- Seção: Descrição e Observações -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-edit me-2"></i>Descrição e Observações
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="descricao" class="form-label fw-semibold">
                                    <i class="fas fa-align-left me-1"></i>Descrição <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3" required 
                                          placeholder="Ex: Mensalidade curso teórico - Janeiro 2024"></textarea>
                                <small class="text-muted">Descreva o serviço ou produto da fatura</small>
                            </div>
                            <div class="col-12">
                                <label for="observacoes" class="form-label fw-semibold">
                                    <i class="fas fa-sticky-note me-1"></i>Observações
                                </label>
                                <textarea class="form-control" id="observacoes" name="observacoes" rows="2" 
                                          placeholder="Observações adicionais (opcional)"></textarea>
                                <small class="text-muted">Informações complementares sobre a fatura</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Fatura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Definir data de vencimento padrão (30 dias a partir de hoje)
document.addEventListener('DOMContentLoaded', function() {
    const dataVencimento = document.getElementById('data_vencimento');
    if (dataVencimento && !dataVencimento.value) {
        const hoje = new Date();
        const vencimento = new Date(hoje.getTime() + (30 * 24 * 60 * 60 * 1000));
        dataVencimento.value = vencimento.toISOString().split('T')[0];
    }
    
    // Event listeners para parcelamento
    setupParcelamentoEvents();
});

// Configurar eventos de parcelamento
function setupParcelamentoEvents() {
    const parcelamentoCheckbox = document.getElementById('parcelamento');
    const configParcelamento = document.getElementById('config-parcelamento');
    const valorTotal = document.getElementById('valor_total');
    const entrada = document.getElementById('entrada');
    const numParcelas = document.getElementById('num_parcelas');
    const intervaloParcelas = document.getElementById('intervalo_parcelas');
    const dataVencimento = document.getElementById('data_vencimento');
    
    // Toggle configuração de parcelamento
    parcelamentoCheckbox.addEventListener('change', function() {
        if (this.checked) {
            configParcelamento.style.display = 'block';
            dataVencimento.required = false;
            calcularParcelas();
        } else {
            configParcelamento.style.display = 'none';
            document.getElementById('resumo-parcelas').style.display = 'none';
            dataVencimento.required = true;
        }
    });
    
    // Recalcular parcelas quando valores mudarem
    [valorTotal, entrada, numParcelas, intervaloParcelas].forEach(element => {
        element.addEventListener('input', calcularParcelas);
        element.addEventListener('change', calcularParcelas);
    });
}

// Calcular e exibir parcelas
function calcularParcelas() {
    const parcelamentoCheckbox = document.getElementById('parcelamento');
    if (!parcelamentoCheckbox.checked) return;
    
    const valorTotal = parseFloat(document.getElementById('valor_total').value) || 0;
    const entrada = parseFloat(document.getElementById('entrada').value) || 0;
    const numParcelas = parseInt(document.getElementById('num_parcelas').value) || 1;
    const intervaloDias = parseInt(document.getElementById('intervalo_parcelas').value) || 30;
    const dataVencimento = document.getElementById('data_vencimento').value;
    
    if (valorTotal <= 0) {
        document.getElementById('resumo-parcelas').style.display = 'none';
        return;
    }
    
    // Calcular valor das parcelas
    const valorRestante = valorTotal - entrada;
    const valorParcela = valorRestante / numParcelas;
    
    // Gerar tabela de parcelas
    const tabelaParcelas = document.getElementById('tabela-parcelas');
    tabelaParcelas.innerHTML = '';
    
    // Data base para cálculo
    let dataBase = new Date();
    if (dataVencimento) {
        dataBase = new Date(dataVencimento);
    }
    
    // Adicionar entrada se houver
    if (entrada > 0) {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td><strong>Entrada</strong></td>
            <td><strong>R$ ${entrada.toFixed(2).replace('.', ',')}</strong></td>
            <td>${formatarData(dataBase)}</td>
        `;
        tabelaParcelas.appendChild(row);
    }
    
    // Adicionar parcelas
    for (let i = 1; i <= numParcelas; i++) {
        const dataParcela = new Date(dataBase);
        dataParcela.setDate(dataBase.getDate() + (i * intervaloDias));
        
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${i}ª parcela</td>
            <td>R$ ${valorParcela.toFixed(2).replace('.', ',')}</td>
            <td>${formatarData(dataParcela)}</td>
        `;
        tabelaParcelas.appendChild(row);
    }
    
    // Mostrar resumo
    document.getElementById('resumo-parcelas').style.display = 'block';
    
    // Validar se entrada não excede valor total
    if (entrada > valorTotal) {
        document.getElementById('entrada').classList.add('is-invalid');
        showAlert('warning', 'O valor da entrada não pode ser maior que o valor total.');
    } else {
        document.getElementById('entrada').classList.remove('is-invalid');
    }
}

// Formatar data para exibição
function formatarData(data) {
    return data.toLocaleDateString('pt-BR');
}

function novaFatura() {
    // Limpar formulário
    document.getElementById('formNovaFatura').reset();
    
    // Definir data de vencimento padrão
    const hoje = new Date();
    const vencimento = new Date(hoje.getTime() + (30 * 24 * 60 * 60 * 1000));
    document.getElementById('data_vencimento').value = vencimento.toISOString().split('T')[0];
    
    // Resetar configurações de parcelamento
    document.getElementById('parcelamento').checked = false;
    document.getElementById('config-parcelamento').style.display = 'none';
    document.getElementById('resumo-parcelas').style.display = 'none';
    document.getElementById('entrada').value = '0';
    document.getElementById('num_parcelas').value = '4';
    document.getElementById('intervalo_parcelas').value = '30';
    
    // Mostrar modal
    const modal = new bootstrap.Modal(document.getElementById('modalNovaFatura'));
    modal.show();
}

// Submissão do formulário
document.getElementById('formNovaFatura').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const submitBtn = this.querySelector('button[type="submit"]');
    const originalText = submitBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    
    // Enviar via AJAX
    fetch('?page=financeiro-faturas&action=create', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Sucesso
            showAlert('success', 'Fatura criada com sucesso!');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaFatura'));
            modal.hide();
            
            // Recarregar página para mostrar nova fatura
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            // Erro
            showAlert('danger', data.message || 'Erro ao criar fatura');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('danger', 'Erro de conexão. Tente novamente.');
    })
    .finally(() => {
        // Reabilitar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

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

// Função para mostrar alertas
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    
    // Inserir no topo da página
    const container = document.querySelector('.container-fluid, .container, main');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // Remover após 5 segundos
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}
</script>
