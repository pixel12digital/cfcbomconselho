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

/**
 * Obtém a descrição curta da fatura para exibição na listagem
 * Se termina com "Entrada" ou "Xª parcela", retorna apenas isso
 * Caso contrário, retorna o título completo
 */
function getDescricaoCurtaFatura(string $titulo): string {
    $tituloTrim = trim($titulo);
    
    // Regex para capturar "Entrada" ou "<n>ª parcela" no final
    if (preg_match('/(Entrada|\d+ª parcela)\s*$/u', $tituloTrim, $matches)) {
        return $matches[1]; // Entrada ou "Xª parcela"
    }
    
    // Caso não seja entrada/parcela, retorna o título inteiro
    return $tituloTrim;
}

/**
 * Formata o status da fatura para exibição visual
 * Retorna array com 'label' (texto) e 'class' (classe CSS)
 */
function formatarStatusFatura(string $status, ?string $dataVencimento = null): array {
    $hoje = new DateTime('today');
    
    // Se status é vencida, sempre mostrar EM ATRASO
    if ($status === 'vencida') {
        return [
            'label' => 'EM ATRASO',
            'class' => 'danger',
            'status_original' => 'vencida'
        ];
    }
    
    // Se status é aberta e está vencida, mostrar EM ATRASO
    if ($status === 'aberta' && $dataVencimento) {
        try {
            $vencimento = new DateTime($dataVencimento);
            if ($vencimento < $hoje) {
                return [
                    'label' => 'EM ATRASO',
                    'class' => 'danger',
                    'status_original' => 'aberta'
                ];
            }
        } catch (Exception $e) {
            // Se houver erro ao parsear data, usar status padrão
        }
    }
    
    // Status padrão
    $statusMap = [
        'aberta' => ['label' => 'ABERTA', 'class' => 'primary'],
        'paga' => ['label' => 'PAGA', 'class' => 'success'],
        'parcial' => ['label' => 'PARCIAL', 'class' => 'warning'],
        'cancelada' => ['label' => 'CANCELADA', 'class' => 'secondary']
    ];
    
    return $statusMap[$status] ?? [
        'label' => strtoupper($status),
        'class' => 'secondary',
        'status_original' => $status
    ];
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

// Parâmetros de paginação
$perPage = max(1, (int)($_GET['f_per_page'] ?? 20));
$currentPage = max(1, (int)($_GET['f_page'] ?? 1));
$offset = ($currentPage - 1) * $perPage;

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
    
    // Ordenação padrão: por vencimento ASC (mais próximo primeiro) - ordenação final é feita no frontend
    $orderBy = 'f.data_vencimento ASC, f.id DESC';
    
    // Contar total de faturas com os mesmos filtros
    $totalFaturas = $db->fetch("
        SELECT COUNT(*) as total
        FROM financeiro_faturas f
        LEFT JOIN alunos a ON f.aluno_id = a.id
        {$where_sql}
    ", $params);
    $totalFaturas = (int)($totalFaturas['total'] ?? 0);
    $totalPages = max(1, ceil($totalFaturas / $perPage));
    
    // Ajustar página atual se necessário
    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
        $offset = ($currentPage - 1) * $perPage;
    }
    
    // Buscar faturas com paginação
    $faturas = $db->fetchAll("
        SELECT f.*, a.nome as aluno_nome, a.cpf as aluno_cpf
        FROM financeiro_faturas f
        LEFT JOIN alunos a ON f.aluno_id = a.id
        {$where_sql}
        ORDER BY $orderBy
        LIMIT ? OFFSET ?
    ", array_merge($params, [$perPage, $offset]));
} catch (Exception $e) {
    $faturas = [];
    $totalFaturas = 0;
    $totalPages = 1;
    $currentPage = 1;
}

// Buscar alunos para filtro
try {
    $alunos = $db->fetchAll("SELECT id, nome, cpf FROM alunos ORDER BY nome");
} catch (Exception $e) {
    $alunos = [];
}

// Incluir função compartilhada
if (!function_exists('buildDescricaoSugestaoFatura')) {
    require_once __DIR__ . '/../includes/financeiro-faturas-functions.php';
}

// Buscar operações/serviços do aluno para sugerir descrição da fatura (quando há aluno_id na URL)
$descricao_sugestao = null;
$aluno_id_get = $_GET['aluno_id'] ?? null;
$matricula_id_get = $_GET['matricula_id'] ?? null;

if ($aluno_id_get || $matricula_id_get) {
    $descricao_sugestao = buildDescricaoSugestaoFatura($db, $aluno_id_get, $matricula_id_get);
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

.status-cancelada { 
    background-color: #f8d7da; 
    color: #842029; 
    border: 1px solid #f5c2c7;
}

.status-parcial { 
    background-color: #fff3cd; 
    color: #856404; 
    border: 1px solid #ffeaa7;
}

/* Classes para novos status formatados */
.status-primary { 
    background-color: var(--blue-100); 
    color: var(--blue-800); 
    border: 1px solid var(--blue-200);
}

.status-success { 
    background-color: #dcfce7; 
    color: #166534; 
    border: 1px solid #bbf7d0;
}

.status-danger { 
    background-color: #fef2f2; 
    color: #991b1b; 
    border: 1px solid #fecaca;
}

.status-secondary { 
    background-color: #f8d7da; 
    color: #842029; 
    border: 1px solid #f5c2c7;
}

.status-warning { 
    background-color: #fff3cd; 
    color: #856404; 
    border: 1px solid #ffeaa7;
}


/* =====================================================
   LAYOUT COMPACTO DA TABELA DE FATURAS
   ===================================================== */

/* Desktop - Layout em linha única */
.col-aluno {
    white-space: nowrap;
}

.col-aluno .aluno-nome {
    font-weight: 600;
}

.col-aluno .aluno-doc {
    font-size: 0.85rem;
    color: #6c757d;
    margin-left: 4px;
}

.col-valor .valor-formatado {
    font-weight: 600;
    white-space: nowrap;
}

.col-descricao span {
    white-space: nowrap;
    text-overflow: ellipsis;
    overflow: hidden;
    display: inline-block;
    max-width: 140px;
}

.col-status {
    white-space: nowrap;
    vertical-align: middle;
    text-align: center;
    padding-left: 12px !important;
    padding-right: 12px !important;
}

.col-acoes {
    white-space: nowrap;
    vertical-align: middle;
    padding-left: 12px !important;
    padding-right: 12px !important;
}

/* Coluna de vencimento: não quebrar e centralizar */
.col-vencimento {
    white-space: nowrap;
    text-align: center;
    padding-left: 12px !important;
    padding-right: 12px !important;
}

/* Cabeçalho de ordenação */
.th-sort-vencimento {
    user-select: none;
    position: relative;
    padding: 12px 12px !important;
    vertical-align: middle;
}

.th-sort-vencimento:hover {
    background-color: rgba(0, 0, 0, 0.05);
}

.th-sort-vencimento .th-vencimento-wrapper {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    white-space: nowrap;
}

.th-sort-vencimento .th-vencimento-label {
    display: inline-block;
}

.th-sort-vencimento .sort-icon {
    font-size: 0.8em;
    line-height: 1;
    color: #6c757d;
    display: inline-block;
    transition: transform 0.2s;
}

/* Coluna de status: compacta e centralizada */
.col-status-header {
    white-space: nowrap;
    text-align: center;
    padding-left: 12px !important;
    padding-right: 12px !important;
}

/* Ajustes para ocupar toda largura do card (igual Lista de Alunos) */
/* Aplicar apenas no card que contém a tabela de faturas */
#tabela-faturas {
    width: 100%;
    margin-bottom: 0;
}

/* Remover padding lateral do card-body que contém a tabela de faturas */
.card-body:has(#tabela-faturas),
.card-body > .table-responsive:has(#tabela-faturas) {
    padding-left: 0 !important;
    padding-right: 0 !important;
}

/* Fallback: usar seletor direto baseado na estrutura */
.card .card-body .table-responsive {
    width: 100%;
    margin: 0;
}

.card .card-body .table-responsive .table {
    width: 100%;
    margin-bottom: 0;
}

/* Remover padding lateral apenas quando a tabela está diretamente no card-body */
.card .card-body > .table-responsive {
    padding-left: 0;
    padding-right: 0;
}

/* Mobile - Permitir quebra de linha */
@media (max-width: 991px) {
    .col-aluno {
        white-space: normal;
    }
    
    .col-aluno .aluno-doc {
        display: block;
        margin-left: 0;
        margin-top: 2px;
    }
    
    .col-aluno .aluno-doc::before {
        content: '';
    }
    
    /* Em mobile, restaurar padding lateral do card-body */
    .card .card-body {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }
}

/* =====================================================
   MODAL NOVA FATURA - PADRÃO CUSTOM-MODAL
   ===================================================== */

/* Dialog específico para modal de faturas */
#modalNovaFatura .custom-modal-dialog,
#modalEditarFatura .custom-modal-dialog {
    width: min(1100px, 96vw);
    max-width: 1100px;
    height: min(90vh, 800px);
    min-height: min(500px, 70vh);
    max-height: 90vh;
}

/* Content - container flex em coluna */
#modalNovaFatura .custom-modal-content,
#modalEditarFatura .custom-modal-content {
    width: 100%;
    height: 100%;
    min-height: 0;
    max-height: 100%;
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 20px 40px rgba(15, 23, 42, 0.16);
    background: #ffffff;
    display: flex;
    flex-direction: column;
    align-items: stretch;
    overflow: hidden;
    position: relative;
}

/* Form também é flex column */
.financeiro-modal-form {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
    height: 100%;
    overflow: hidden;
}

/* Header - fixo */
.financeiro-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 16px 24px;
    background-color: var(--cfc-surface, #FFFFFF);
    border-bottom: 1px solid var(--cfc-border-subtle, #E5E7EB);
    border-top-left-radius: 12px;
    border-top-right-radius: 12px;
    color: var(--cfc-primary, #0F1E4A);
    min-height: 56px;
    flex: 0 0 auto;
    flex-shrink: 0;
}

.financeiro-modal-title {
    font-size: 1.25rem;
    font-weight: 600;
    margin: 0;
    display: flex;
    align-items: center;
    gap: 8px;
    color: var(--cfc-primary, #0F1E4A);
}

.financeiro-modal-title i {
    font-size: 1.1rem;
    color: var(--cfc-primary, #0F1E4A);
}

.financeiro-modal-close {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: var(--gray-300, #cbd5e1);
    border: none;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0;
    opacity: 1;
    background-image: none;
    color: var(--gray-700, #334155);
    cursor: pointer;
    transition: all 0.2s ease;
    font-size: 1.4rem;
    line-height: 1;
}

.financeiro-modal-close::after {
    content: "\00d7";
    font-size: 1.4rem;
    line-height: 1;
    color: var(--gray-700, #334155);
    font-weight: 300;
}

.financeiro-modal-close:hover {
    background-color: var(--gray-400, #94a3b8);
    color: var(--gray-800, #1e293b);
}

.financeiro-modal-close:focus-visible {
    outline: none;
    box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.4);
}

/* Body - rolável */
.financeiro-modal-body {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding: 24px 32px;
    background-color: var(--cfc-surface, #FFFFFF);
    flex: 1 1 auto;
    min-height: 0;
    overflow-y: auto;
    overflow-x: hidden;
    scrollbar-width: thin;
    scrollbar-color: #94a3b8 #edf2f7;
    box-sizing: border-box;
}

/* Scrollbar customizada */
.financeiro-modal-body::-webkit-scrollbar {
    width: 8px;
}

.financeiro-modal-body::-webkit-scrollbar-track {
    background: #edf2f7;
    border-radius: 4px;
}

.financeiro-modal-body::-webkit-scrollbar-thumb {
    background: #94a3b8;
    border-radius: 4px;
}

.financeiro-modal-body::-webkit-scrollbar-thumb:hover {
    background: #64748b;
}

/* Footer - fixo */
.financeiro-modal-footer {
    display: flex;
    align-items: center;
    justify-content: flex-end;
    gap: 12px;
    padding: 12px 24px;
    background-color: var(--cfc-surface-muted, #F3F4F6);
    border-top: 1px solid var(--cfc-border-subtle, #E5E7EB);
    border-bottom-left-radius: 12px;
    border-bottom-right-radius: 12px;
    flex: 0 0 auto;
    flex-shrink: 0;
    min-height: auto;
}

.financeiro-modal-footer .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 0 18px;
    min-height: 40px;
    font-weight: 600;
    border-radius: 10px;
    transition: transform 0.15s ease, box-shadow 0.15s ease;
    cursor: pointer;
}

.financeiro-modal-footer .btn-outline-secondary {
    border: 1px solid var(--cfc-border-subtle, #E5E7EB);
    background: var(--cfc-surface, #FFFFFF);
    color: var(--gray-700, #334155);
}

.financeiro-modal-footer .btn-outline-secondary:hover {
    background: var(--gray-100, #f1f5f9);
    color: var(--gray-800, #1e293b);
    border-color: var(--gray-300, #cbd5e1);
}

.financeiro-modal-footer .btn-primary {
    padding-inline: 20px;
    background: var(--primary-color, #1e3a8a);
    color: #ffffff;
    border: none;
    min-height: 40px;
}

.financeiro-modal-footer .btn-primary:hover {
    background: var(--primary-dark, #1e40af);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.3);
}

/* Responsividade */
@media (max-width: 992px) {
    #modalNovaFatura .custom-modal-dialog {
        width: 95vw;
        max-width: 95vw;
        height: 90vh;
        max-height: 90vh;
    }
    
    .financeiro-modal-body {
        padding: 16px 20px;
    }
    
    .financeiro-modal-header {
        padding: 12px 20px;
    }
    
    .financeiro-modal-footer {
        padding: 12px 20px;
    }
}

@media (max-width: 768px) {
    #modalNovaFatura .custom-modal-dialog {
        width: 100vw;
        max-width: 100vw;
        height: 100vh;
        max-height: 100vh;
        border-radius: 0;
    }
    
    #modalNovaFatura .custom-modal-content {
        border-radius: 0;
    }
    
    .financeiro-modal-body {
        padding: 12px 16px;
    }
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
                    <h3 class="mb-0" id="stats-total-faturas"><?php echo $stats['total_faturas']; ?></h3>
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
                    <h3 class="mb-0" id="stats-faturas-pagas"><?php echo $stats['faturas_pagas']; ?></h3>
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
                    <h3 class="mb-0" id="stats-faturas-vencidas"><?php echo $stats['faturas_vencidas']; ?></h3>
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
                    <h3 class="mb-0" id="stats-valor-aberto">R$ <?php echo number_format($stats['total_valor'], 2, ',', '.'); ?></h3>
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
            <table class="table table-hover" id="tabela-faturas">
                <thead>
                    <tr>
                        <th>Aluno</th>
                        <th>Descrição</th>
                        <th>Valor</th>
                        <th id="th-vencimento" class="th-sort-vencimento col-vencimento" data-sort-direction="asc" style="cursor: pointer;">
                            <span class="th-vencimento-wrapper">
                                <span class="th-vencimento-label">Vencimento</span>
                                <span class="sort-icon">▲</span>
                            </span>
                        </th>
                        <th class="col-status-header">Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($faturas)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhuma fatura encontrada</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($faturas as $fatura): ?>
                    <?php
                    // Obter descrição curta usando a nova regra
                    $tituloCompleto = $fatura['descricao'] ?? $fatura['titulo'] ?? '';
                    $descricaoCurta = getDescricaoCurtaFatura($tituloCompleto);
                    
                    // Formatar status considerando se está vencida
                    $statusInfo = formatarStatusFatura($fatura['status'], $fatura['data_vencimento'] ?? null);
                    
                    // Log de debug (apenas em modo desenvolvimento)
                    if (defined('DEBUG') && DEBUG) {
                        error_log(sprintf(
                            "Fatura ID: %d | Status: %s | Vencimento: %s | Título: %s | Descrição curta: %s",
                            $fatura['id'],
                            $fatura['status'],
                            $fatura['data_vencimento'] ?? 'N/A',
                            $tituloCompleto,
                            $descricaoCurta
                        ));
                    }
                    ?>
                    <tr data-fatura-id="<?php echo $fatura['id']; ?>" 
                        data-status="<?php echo htmlspecialchars($fatura['status']); ?>" 
                        data-vencimento="<?php echo htmlspecialchars($fatura['data_vencimento']); ?>"
                        data-titulo="<?php echo htmlspecialchars($tituloCompleto); ?>"
                        data-descricao-curta="<?php echo htmlspecialchars($descricaoCurta); ?>">
                        <td class="col-aluno">
                            <?php if ($fatura['aluno_nome']): ?>
                            <span class="aluno-nome"><?php echo htmlspecialchars($fatura['aluno_nome']); ?></span>
                            <span class="aluno-doc"> • <?php echo $fatura['aluno_cpf']; ?></span>
                            <?php else: ?>
                            <span class="text-muted">Aluno não encontrado</span>
                            <?php endif; ?>
                        </td>
                        <td class="col-descricao">
                            <span title="<?php echo htmlspecialchars($tituloCompleto); ?>">
                                <?php echo htmlspecialchars($descricaoCurta); ?>
                            </span>
                        </td>
                        <td class="col-valor">
                            <span class="valor-formatado">R$ <?php echo number_format($fatura['valor'], 2, ',', '.'); ?></span>
                        </td>
                        <td class="col-vencimento"><?php echo date('d/m/Y', strtotime($fatura['data_vencimento'])); ?></td>
                        <td class="col-status">
                            <span class="status-badge status-<?php echo $statusInfo['class']; ?>" 
                                  data-status-original="<?php echo htmlspecialchars($fatura['status']); ?>">
                                <?php echo htmlspecialchars($statusInfo['label']); ?>
                            </span>
                        </td>
                        <td class="col-acoes">
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-info" data-fatura-id="<?php echo $fatura['id']; ?>" onclick="editarFatura(<?php echo $fatura['id']; ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-primary" data-fatura-id="<?php echo $fatura['id']; ?>" onclick="visualizarFatura(<?php echo $fatura['id']; ?>)" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" data-fatura-id="<?php echo $fatura['id']; ?>" onclick="marcarComoPaga(<?php echo $fatura['id']; ?>)" title="Marcar como Paga">
                                    <i class="fas fa-check"></i>
                                </button>
                                <button class="btn btn-outline-danger" data-fatura-id="<?php echo $fatura['id']; ?>" onclick="cancelarFatura(<?php echo $fatura['id']; ?>)" title="Cancelar">
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
        
        <?php if ($totalPages > 1): ?>
        <!-- Paginação -->
        <nav aria-label="Paginação de faturas" class="mt-3">
            <ul class="pagination justify-content-center mb-0">
                <?php
                // Montar base de parâmetros preservando filtros
                $baseParams = $_GET;
                unset($baseParams['f_page']); // Remover página atual
                
                // Botão Anterior
                if ($currentPage > 1):
                    $prevParams = $baseParams;
                    $prevParams['f_page'] = $currentPage - 1;
                    $prevUrl = '?' . http_build_query($prevParams);
                ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo htmlspecialchars($prevUrl); ?>">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </a>
                </li>
                <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link"><i class="fas fa-chevron-left"></i> Anterior</span>
                </li>
                <?php endif; ?>
                
                <?php
                // Calcular range de páginas para exibir
                $maxPagesToShow = 7;
                $startPage = max(1, $currentPage - floor($maxPagesToShow / 2));
                $endPage = min($totalPages, $startPage + $maxPagesToShow - 1);
                
                // Ajustar início se estiver no final
                if ($endPage - $startPage < $maxPagesToShow - 1) {
                    $startPage = max(1, $endPage - $maxPagesToShow + 1);
                }
                
                // Primeira página
                if ($startPage > 1):
                    $firstParams = $baseParams;
                    $firstParams['f_page'] = 1;
                    $firstUrl = '?' . http_build_query($firstParams);
                ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo htmlspecialchars($firstUrl); ?>">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                <?php endif; ?>
                <?php endif; ?>
                
                <?php
                // Páginas no range
                for ($i = $startPage; $i <= $endPage; $i++):
                    $pageParams = $baseParams;
                    $pageParams['f_page'] = $i;
                    $pageUrl = '?' . http_build_query($pageParams);
                ?>
                <li class="page-item <?php echo $i === $currentPage ? 'active' : ''; ?>">
                    <a class="page-link" href="<?php echo htmlspecialchars($pageUrl); ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
                
                <?php
                // Última página
                if ($endPage < $totalPages):
                    $lastParams = $baseParams;
                    $lastParams['f_page'] = $totalPages;
                    $lastUrl = '?' . http_build_query($lastParams);
                ?>
                <?php if ($endPage < $totalPages - 1): ?>
                <li class="page-item disabled">
                    <span class="page-link">...</span>
                </li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo htmlspecialchars($lastUrl); ?>">
                        <?php echo $totalPages; ?>
                    </a>
                </li>
                <?php endif; ?>
                
                <?php
                // Botão Próxima
                if ($currentPage < $totalPages):
                    $nextParams = $baseParams;
                    $nextParams['f_page'] = $currentPage + 1;
                    $nextUrl = '?' . http_build_query($nextParams);
                ?>
                <li class="page-item">
                    <a class="page-link" href="<?php echo htmlspecialchars($nextUrl); ?>">
                        Próxima <i class="fas fa-chevron-right"></i>
                    </a>
                </li>
                <?php else: ?>
                <li class="page-item disabled">
                    <span class="page-link">Próxima <i class="fas fa-chevron-right"></i></span>
                </li>
                <?php endif; ?>
            </ul>
            
            <div class="text-center mt-2 text-muted small">
                Mostrando <?php echo $offset + 1; ?> a <?php echo min($offset + $perPage, $totalFaturas); ?> 
                de <?php echo $totalFaturas; ?> faturas
            </div>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova Fatura - Padronizado -->
<div id="modalNovaFatura" class="custom-modal">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <form id="formNovaFatura" class="modal-form financeiro-modal-form">
                <div class="modal-form-header financeiro-modal-header">
                    <h2 class="financeiro-modal-title">
                        <i class="fas fa-file-invoice-dollar me-2"></i>Nova Fatura
                    </h2>
                    <button type="button" class="btn-close financeiro-modal-close" onclick="fecharModalNovaFatura()"></button>
                </div>
                
                <div class="modal-form-body financeiro-modal-body">
                    <!-- Seção: Informações Básicas -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-info-circle me-2"></i>Informações Básicas
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="aluno_id_modal" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Aluno <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="aluno_id_modal" name="aluno_id" required style="padding: 0.5rem; font-size: 0.9rem;">
                                    <option value="">Selecione um aluno</option>
                                    <?php foreach ($alunos as $aluno): ?>
                                    <option value="<?php echo $aluno['id']; ?>">
                                        <?php echo htmlspecialchars($aluno['nome']); ?> - <?php echo $aluno['cpf']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="valor_total" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Valor Total <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white fw-bold">
                                        R$
                                    </span>
                                    <input type="text" class="form-control" id="valor_total" name="valor_total" 
                                           required placeholder="0,00" autocomplete="off" 
                                           data-skip-mask="true"
                                           style="padding: 0.5rem; font-size: 0.9rem;">
                                </div>
                                <small class="text-muted" style="font-size: 0.8rem;">Digite o valor total da fatura</small>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4" style="border-color: #e5e7eb;">

                    <!-- Seção: Detalhes da Fatura -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-file-invoice me-2"></i>Detalhes da Fatura
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="data_vencimento" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar me-1"></i>Data de Vencimento <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="data_vencimento" name="data_vencimento" required style="padding: 0.5rem; font-size: 0.9rem;">
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-flag me-1"></i>Status
                                </label>
                                <select class="form-select" id="status" name="status" style="padding: 0.5rem; font-size: 0.9rem;">
                                    <option value="aberta" selected>Aberta</option>
                                    <option value="paga">Paga</option>
                                    <option value="parcial">Parcial</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4" style="border-color: #e5e7eb;">
                    
                    <!-- Seção: Parcelamento -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
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
                                                <input type="text" class="form-control form-control-lg" id="entrada" name="entrada" 
                                                       value="0,00" placeholder="0,00" autocomplete="off"
                                                       data-skip-mask="true">
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
                                            <label for="frequencia_parcelas" class="form-label fw-semibold fs-6">
                                                <i class="fas fa-calendar-alt me-1"></i>Frequência
                                            </label>
                                            <select class="form-select form-select-lg" id="frequencia_parcelas" name="frequencia_parcelas">
                                                <option value="monthly" selected>Mensal (mesmo dia)</option>
                                                <option value="days">A cada X dias</option>
                                            </select>
                                            <small class="text-muted mt-2 d-block">Frequência de vencimento das parcelas</small>
                                        </div>
                                        <div class="col-12" id="container-intervalo-dias" style="display: none;">
                                            <label for="intervalo_parcelas" class="form-label fw-semibold fs-6">
                                                <i class="fas fa-calendar-day me-1"></i>Intervalo (dias)
                                            </label>
                                            <input type="number" class="form-control form-control-lg" id="intervalo_parcelas" name="intervalo_parcelas" 
                                                   value="30" min="1" max="365">
                                            <small class="text-muted mt-2 d-block">Intervalo em dias corridos entre cada parcela</small>
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
                    <hr class="my-4" style="border-color: #e5e7eb;">

                    <!-- Seção: Descrição da Fatura -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-edit me-2"></i>Descrição da Fatura
                        </h6>
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="descricao" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-align-left me-1"></i>Descrição <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="3" required 
                                          placeholder="Ex: Mensalidade curso teórico - Janeiro 2024" style="font-size: 0.9rem;"></textarea>
                                <small class="text-muted" style="font-size: 0.8rem;">Descreva o serviço ou produto da fatura</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-form-footer financeiro-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="fecharModalNovaFatura()">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarFatura">
                        <i class="fas fa-save me-1"></i>Salvar Fatura
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Editar Fatura - Padronizado -->
<div id="modalEditarFatura" class="custom-modal">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <form id="formEditarFatura" class="modal-form financeiro-modal-form">
                <div class="modal-form-header financeiro-modal-header">
                    <h2 class="financeiro-modal-title">
                        <i class="fas fa-edit me-2"></i>Editar Fatura
                    </h2>
                    <button type="button" class="btn-close financeiro-modal-close" onclick="fecharModalEditarFatura()"></button>
                </div>
                
                <div class="modal-form-body financeiro-modal-body">
                    <!-- Hidden para ID da fatura -->
                    <input type="hidden" id="editar_fatura_id" name="fatura_id">
                    
                    <!-- Seção: Informações Básicas -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-info-circle me-2"></i>Informações Básicas
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Aluno
                                </label>
                                <div class="form-control" id="editar_aluno_info" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;" readonly>
                                    <span id="editar_aluno_nome">-</span>
                                    <span id="editar_aluno_cpf" class="text-muted ms-2">-</span>
                                </div>
                                <small class="text-muted" style="font-size: 0.8rem;">Informação somente leitura</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Valor
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white fw-bold">R$</span>
                                    <input type="text" class="form-control" id="editar_valor" 
                                           readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                                </div>
                                <small class="text-muted" style="font-size: 0.8rem;">Valor não pode ser alterado</small>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4" style="border-color: #e5e7eb;">

                    <!-- Seção: Detalhes da Fatura -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-file-invoice me-2"></i>Detalhes da Fatura
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="editar_titulo" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-align-left me-1"></i>Descrição / Título
                                </label>
                                <input type="text" class="form-control" id="editar_titulo" name="titulo" 
                                       style="padding: 0.5rem; font-size: 0.9rem;" 
                                       placeholder="Ex: CNH - 1ª parcela">
                                <small class="text-muted" style="font-size: 0.8rem;">Título ou descrição da fatura</small>
                            </div>
                            <div class="col-md-6">
                                <label for="editar_data_vencimento" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar me-1"></i>Data de Vencimento <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="editar_data_vencimento" name="data_vencimento" 
                                       required style="padding: 0.5rem; font-size: 0.9rem;">
                                <small class="text-muted" style="font-size: 0.8rem;">Data de vencimento da fatura</small>
                            </div>
                            <div class="col-md-6">
                                <label for="editar_forma_pagamento" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-credit-card me-1"></i>Forma de Pagamento
                                </label>
                                <select class="form-select" id="editar_forma_pagamento" name="forma_pagamento" 
                                        style="padding: 0.5rem; font-size: 0.9rem;">
                                    <option value="avista">À Vista</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="pix">PIX</option>
                                    <option value="cartao">Cartão</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="dinheiro">Dinheiro</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4" style="border-color: #e5e7eb;">

                    <!-- Seção: Resumo de Pagamentos -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-money-bill-wave me-2"></i>Resumo de Pagamentos
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Total Pago
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white fw-bold">R$</span>
                                    <input type="text" class="form-control" id="editar_total_pago" 
                                           readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #d1e7dd;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Saldo em Aberto
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-warning text-white fw-bold">R$</span>
                                    <input type="text" class="form-control" id="editar_saldo_aberto" 
                                           readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #fff3cd;">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Último Pagamento
                                </label>
                                <div class="form-control" id="editar_ultimo_pagamento" 
                                     style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                                    <span>-</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-form-footer financeiro-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="fecharModalEditarFatura()">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarEdicao">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Visualização de Fatura -->
<div id="modalVisualizarFatura" class="custom-modal">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <div class="modal-form-header financeiro-modal-header">
                <h2 class="financeiro-modal-title">
                    <i class="fas fa-eye me-2"></i>Visualizar Fatura
                </h2>
                <button type="button" class="btn-close financeiro-modal-close" onclick="fecharModalVisualizarFatura()"></button>
            </div>
            
            <div class="modal-form-body financeiro-modal-body" id="modalVisualizarFaturaBody">
                <!-- Conteúdo será preenchido via JavaScript -->
                <div class="text-center py-5">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-3 text-muted">Carregando dados da fatura...</p>
                </div>
            </div>
            
            <div class="modal-form-footer financeiro-modal-footer">
                <button type="button" class="btn btn-outline-secondary" onclick="fecharModalVisualizarFatura()">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Registrar Pagamento -->
<div id="modalRegistrarPagamento" class="custom-modal">
    <div class="custom-modal-dialog">
        <div class="custom-modal-content">
            <form id="formRegistrarPagamento" class="modal-form financeiro-modal-form">
                <div class="modal-form-header financeiro-modal-header">
                    <h2 class="financeiro-modal-title">
                        <i class="fas fa-money-bill-wave me-2"></i>Registrar Pagamento
                    </h2>
                    <button type="button" class="btn-close financeiro-modal-close" onclick="fecharModalRegistrarPagamento()"></button>
                </div>
                
                <div class="modal-form-body financeiro-modal-body">
                    <!-- Hidden para ID da fatura -->
                    <input type="hidden" id="pagamento_fatura_id" name="fatura_id">
                    
                    <!-- Seção: Informações da Fatura -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-file-invoice me-2"></i>Informações da Fatura
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Aluno
                                </label>
                                <div class="form-control" id="pagamento_aluno_info" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;" readonly>
                                    <span id="pagamento_aluno_nome">-</span>
                                    <span id="pagamento_aluno_cpf" class="text-muted ms-2">-</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Valor da Fatura
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-primary text-white fw-bold">R$</span>
                                    <input type="text" class="form-control" id="pagamento_valor_fatura" 
                                           readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    Saldo em Aberto
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-warning text-white fw-bold">R$</span>
                                    <input type="text" class="form-control" id="pagamento_saldo_aberto" 
                                           readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #fff3cd;">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Separador Visual -->
                    <hr class="my-4" style="border-color: #e5e7eb;">

                    <!-- Seção: Dados do Pagamento -->
                    <div class="mb-4">
                        <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                            <i class="fas fa-money-bill-wave me-2"></i>Dados do Pagamento
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="pagamento_data_pagamento" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-calendar me-1"></i>Data de Pagamento <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control" id="pagamento_data_pagamento" name="data_pagamento" 
                                       required style="padding: 0.5rem; font-size: 0.9rem;">
                                <small class="text-muted" style="font-size: 0.8rem;">Data em que o pagamento foi recebido</small>
                            </div>
                            <div class="col-md-6">
                                <label for="pagamento_valor_pago" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-dollar-sign me-1"></i>Valor Pago <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text bg-success text-white fw-bold">R$</span>
                                    <input type="number" class="form-control" id="pagamento_valor_pago" name="valor_pago" 
                                           step="0.01" min="0.01" required style="padding: 0.5rem; font-size: 0.9rem;"
                                           placeholder="0,00">
                                </div>
                                <small class="text-muted" style="font-size: 0.8rem;">Valor recebido neste pagamento</small>
                            </div>
                            <div class="col-md-6">
                                <label for="pagamento_metodo" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-credit-card me-1"></i>Método de Pagamento <span class="text-danger">*</span>
                                </label>
                                <select class="form-select" id="pagamento_metodo" name="metodo" 
                                        required style="padding: 0.5rem; font-size: 0.9rem;">
                                    <option value="pix">PIX</option>
                                    <option value="boleto">Boleto</option>
                                    <option value="cartao">Cartão</option>
                                    <option value="dinheiro">Dinheiro</option>
                                    <option value="transferencia">Transferência</option>
                                    <option value="outros">Outros</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label for="pagamento_obs" class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                                    <i class="fas fa-sticky-note me-1"></i>Observações
                                </label>
                                <textarea class="form-control" id="pagamento_obs" name="obs" rows="3" 
                                          style="padding: 0.5rem; font-size: 0.9rem;"
                                          placeholder="Observações sobre o pagamento (opcional)"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-form-footer financeiro-modal-footer">
                    <button type="button" class="btn btn-outline-secondary" onclick="fecharModalRegistrarPagamento()">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-success" id="btnSalvarPagamento">
                        <i class="fas fa-check me-1"></i>Registrar Pagamento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Passar descrição sugerida do PHP para JavaScript (se disponível)
// Descrição vem das operações configuradas em "Curso e Serviços" do aluno
<?php if (!empty($descricao_sugestao)): ?>
window.descricaoSugestaoFatura = <?php echo json_encode($descricao_sugestao, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
console.log('📋 Descrição sugerida do PHP:', <?php echo json_encode($descricao_sugestao, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>);
<?php else: ?>
window.descricaoSugestaoFatura = null;
console.log('⚠️ Nenhuma descrição sugerida encontrada. Aluno ID:', <?php echo json_encode($aluno_id_get); ?>);
<?php endif; ?>

// Passar IDs do GET para JavaScript (se disponíveis)
window.alunoIdGet = <?php echo json_encode($aluno_id_get); ?>;
window.matriculaIdGet = <?php echo json_encode($matricula_id_get); ?>;

// Debug: Log dos valores passados
console.log('🔍 Debug - Valores passados do PHP:');
console.log('  - alunoIdGet:', window.alunoIdGet);
console.log('  - matriculaIdGet:', window.matriculaIdGet);
console.log('  - descricaoSugestaoFatura:', window.descricaoSugestaoFatura);
// Helper para parse de valor brasileiro (R$ 1.500,50 -> 1500.50)
function parseValorBrasileiro(valorStr) {
    if (!valorStr) return 0;
    // Remove prefixo R$ e espaços
    valorStr = valorStr.toString().replace(/R\$\s*/g, '').trim();
    // Remove separador de milhar (ponto)
    valorStr = valorStr.replace(/\./g, '');
    // Troca vírgula por ponto para parseFloat
    valorStr = valorStr.replace(',', '.');
    const numero = parseFloat(valorStr);
    return isNaN(numero) ? 0 : numero;
}

// Helper para formatar valor brasileiro (1500.50 -> R$ 1.500,50)
function formatarValorBrasileiro(valor) {
    if (!valor && valor !== 0) return '0,00';
    const numero = typeof valor === 'string' ? parseValorBrasileiro(valor) : parseFloat(valor);
    if (isNaN(numero)) return '0,00';
    return numero.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

// Função para inicializar máscaras de moeda (exposta globalmente)
// Flag para prevenir listeners duplicados
let mascarasMoedaInicializadas = false;
let valorTotalHandlers = null;
let entradaHandlers = null;

function inicializarMascarasMoeda() {
    // Destruir máscaras existentes se houver (se estiverem usando IMask)
    if (window.valorTotalMask && typeof window.valorTotalMask.destroy === 'function') {
        window.valorTotalMask.destroy();
        window.valorTotalMask = null;
    }
    if (window.entradaMask && typeof window.entradaMask.destroy === 'function') {
        window.entradaMask.destroy();
        window.entradaMask = null;
    }
    
    // Remover listeners antigos se existirem
    const valorTotalInput = document.getElementById('valor_total');
    if (valorTotalInput && valorTotalHandlers) {
        valorTotalInput.removeEventListener('keypress', valorTotalHandlers.keypress);
        valorTotalInput.removeEventListener('blur', valorTotalHandlers.blur);
        valorTotalInput.removeEventListener('focus', valorTotalHandlers.focus);
    }
    
    const entradaInput = document.getElementById('entrada');
    if (entradaInput && entradaHandlers) {
        entradaInput.removeEventListener('keypress', entradaHandlers.keypress);
        entradaInput.removeEventListener('blur', entradaHandlers.blur);
        entradaInput.removeEventListener('focus', entradaHandlers.focus);
    }
    
    // Configurar campo Valor Total com formatação manual (sem IMask durante digitação)
    if (valorTotalInput) {
        // Garantir que o campo não fique readonly
        valorTotalInput.removeAttribute('readonly');
        valorTotalInput.removeAttribute('disabled');
        
        // Criar handlers nomeados para poder removê-los depois
        valorTotalHandlers = {
            keypress: function(e) {
                const char = String.fromCharCode(e.which);
                // Permitir números, vírgula, ponto
                if (!/[0-9,.]/.test(char) && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                }
            },
            blur: function() {
                if (this.value && this.value.trim() !== '') {
                    const valorNum = parseValorBrasileiro(this.value);
                    if (valorNum > 0) {
                        // Usar flag para prevenir que a mudança de valor dispare eventos de change
                        const oldValue = this.value;
                        this.value = formatarValorBrasileiro(valorNum);
                        // Se o valor formatado for diferente, não disparar change (já foi formatado)
                        if (oldValue !== this.value) {
                            // Não disparar change event manualmente
                        }
                    } else {
                        this.value = '';
                    }
                }
            },
            focus: function() {
                if (this.value) {
                    const valorNum = parseValorBrasileiro(this.value);
                    if (valorNum > 0) {
                        // Mostrar apenas o número sem formatação durante edição
                        // Se for inteiro, mostrar sem decimais; se tiver decimais, mostrar com vírgula
                        if (valorNum % 1 === 0) {
                            this.value = valorNum.toString();
                        } else {
                            this.value = valorNum.toString().replace('.', ',');
                        }
                    }
                }
            }
        };
        
        valorTotalInput.addEventListener('keypress', valorTotalHandlers.keypress);
        valorTotalInput.addEventListener('blur', valorTotalHandlers.blur);
        valorTotalInput.addEventListener('focus', valorTotalHandlers.focus);
    }
    
    // Configurar campo Entrada com formatação manual (sem IMask durante digitação)
    if (entradaInput) {
        // Garantir que o campo não fique readonly
        entradaInput.removeAttribute('readonly');
        entradaInput.removeAttribute('disabled');
        
        // Criar handlers nomeados para poder removê-los depois
        entradaHandlers = {
            keypress: function(e) {
                const char = String.fromCharCode(e.which);
                // Permitir números, vírgula, ponto
                if (!/[0-9,.]/.test(char) && !e.ctrlKey && !e.metaKey) {
                    e.preventDefault();
                }
            },
            blur: function() {
                if (this.value && this.value.trim() !== '') {
                    const valorNum = parseValorBrasileiro(this.value);
                    if (valorNum > 0) {
                        this.value = formatarValorBrasileiro(valorNum);
                    } else {
                        this.value = '0,00';
                    }
                } else {
                    this.value = '0,00';
                }
            },
            focus: function() {
                if (this.value && this.value !== '0,00') {
                    const valorNum = parseValorBrasileiro(this.value);
                    if (valorNum > 0) {
                        // Mostrar apenas o número sem formatação durante edição
                        // Se for inteiro, mostrar sem decimais; se tiver decimais, mostrar com vírgula
                        if (valorNum % 1 === 0) {
                            this.value = valorNum.toString();
                        } else {
                            this.value = valorNum.toString().replace('.', ',');
                        }
                    } else {
                        this.value = '';
                    }
                } else {
                    this.value = '';
                }
            }
        };
        
        entradaInput.addEventListener('keypress', entradaHandlers.keypress);
        entradaInput.addEventListener('blur', entradaHandlers.blur);
        entradaInput.addEventListener('focus', entradaHandlers.focus);
    }
    
    mascarasMoedaInicializadas = true;
}

// Função utilitária para aplicar descrição sugerida no campo
function aplicarDescricaoSugerida(descricao) {
    const descricaoField = document.getElementById('descricao');
    if (!descricaoField) return;
    
    // Só preenche se ainda estiver vazio (usuário pode querer sobrescrever)
    if (!descricaoField.value.trim() && descricao) {
        descricaoField.value = descricao;
        console.log('[Fatura] Descrição sugerida aplicada:', descricao);
    }
}

// Função para buscar descrição sugerida do backend baseada no aluno selecionado
async function carregarDescricaoSugeridaPorAluno(alunoId, matriculaId = null) {
    if (!alunoId) {
        window.descricaoSugestaoFatura = null;
        return;
    }
    
    try {
        const formData = new FormData();
        formData.append('aluno_id', alunoId);
        if (matriculaId) {
            formData.append('matricula_id', matriculaId);
        }
        
        const response = await fetch('index.php?page=financeiro-faturas&action=descricao_sugerida_fatura', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        if (data.success) {
            window.descricaoSugestaoFatura = data.descricao_sugerida || null;
            aplicarDescricaoSugerida(window.descricaoSugestaoFatura);
        } else {
            console.warn('[Fatura] Falha ao carregar descrição sugerida:', data.message);
            window.descricaoSugestaoFatura = null;
        }
    } catch (err) {
        console.error('[Fatura] Erro ao carregar descrição sugerida:', err);
        window.descricaoSugestaoFatura = null;
    }
}

// Definir data de vencimento padrão (30 dias a partir de hoje)
// Configurar listener para buscar descrição quando aluno for selecionado
document.addEventListener('DOMContentLoaded', function() {
    // Listener no campo de aluno do modal para buscar descrição quando mudar
    const alunoSelect = document.getElementById('aluno_id_modal');
    if (alunoSelect) {
        alunoSelect.addEventListener('change', function() {
            const alunoId = this.value;
            if (alunoId) {
                carregarDescricaoSugeridaPorAluno(alunoId);
            } else {
                window.descricaoSugestaoFatura = null;
                const descricaoField = document.getElementById('descricao');
                if (descricaoField && !descricaoField.value.trim()) {
                    descricaoField.value = '';
                }
            }
        });
    }
    
    // Se houver aluno_id no GET, buscar descrição ao carregar
    if (window.alunoIdGet) {
        carregarDescricaoSugeridaPorAluno(window.alunoIdGet, window.matriculaIdGet);
    } else if (window.descricaoSugestaoFatura) {
        // Se já veio do PHP (quando há aluno_id na URL), aplicar diretamente
        aplicarDescricaoSugerida(window.descricaoSugestaoFatura);
    }
});

// Ordenação por vencimento no frontend (sem recarregar página)
document.addEventListener('DOMContentLoaded', function() {
    const thVencimento = document.getElementById('th-vencimento');
    if (thVencimento) {
        thVencimento.addEventListener('click', function() {
            const table = this.closest('table');
            if (!table) return;
            
            const tbody = table.querySelector('tbody');
            if (!tbody) return;
            
            const rows = Array.from(tbody.querySelectorAll('tr[data-vencimento]'));
            if (rows.length === 0) return;
            
            const currentDirection = this.dataset.sortDirection || 'asc';
            const newDirection = currentDirection === 'asc' ? 'desc' : 'asc';
            const sortIcon = this.querySelector('.sort-icon');
            
            // Ordenar as linhas
            rows.sort((a, b) => {
                const va = a.dataset.vencimento || '';
                const vb = b.dataset.vencimento || '';
                
                if (newDirection === 'asc') {
                    return va.localeCompare(vb);
                } else {
                    return vb.localeCompare(va);
                }
            });
            
            // Remontar o tbody na nova ordem
            rows.forEach(row => tbody.appendChild(row));
            
            // Atualizar direção e ícone
            this.dataset.sortDirection = newDirection;
            if (sortIcon) {
                sortIcon.textContent = newDirection === 'asc' ? '▲' : '▼';
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const dataVencimento = document.getElementById('data_vencimento');
    if (dataVencimento && !dataVencimento.value) {
        const hoje = new Date();
        const vencimento = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate() + 30);
        // Usar formatDateLocal para evitar problemas de fuso horário
        dataVencimento.value = formatDateLocal(vencimento);
    }
    
    // Inicializar máscaras quando DOM estiver pronto
    inicializarMascarasMoeda();
    
    // Event listeners para parcelamento
    setupParcelamentoEvents();
});

// Flag para prevenir loops recursivos
let isCalculandoParcelas = false;
let parcelamentoListenersAttached = false;
let parcelamentoTimeout = null;

// Configurar eventos de parcelamento
function setupParcelamentoEvents() {
    // Prevenir múltiplas chamadas que criariam listeners duplicados
    if (parcelamentoListenersAttached) {
        return;
    }
    parcelamentoListenersAttached = true;
    
    const parcelamentoCheckbox = document.getElementById('parcelamento');
    const configParcelamento = document.getElementById('config-parcelamento');
    const valorTotal = document.getElementById('valor_total');
    const entrada = document.getElementById('entrada');
    const numParcelas = document.getElementById('num_parcelas');
    const intervaloParcelas = document.getElementById('intervalo_parcelas');
    const dataVencimento = document.getElementById('data_vencimento');
    
    if (!parcelamentoCheckbox || !configParcelamento) return;
    
    // Função para agendar recálculo com debounce
    function agendarRecalculoParcelas() {
        // Limpar timeout anterior se existir
        if (parcelamentoTimeout) {
            clearTimeout(parcelamentoTimeout);
        }
        
        // Agendar novo recálculo
        parcelamentoTimeout = setTimeout(() => {
            parcelamentoTimeout = null;
            calcularParcelas();
        }, 200); // Delay de 200ms para debounce
    }
    
    // Toggle configuração de parcelamento
    parcelamentoCheckbox.addEventListener('change', function() {
        if (this.checked) {
            configParcelamento.style.display = 'block';
            dataVencimento.required = false;
            // Usar agendamento com debounce
            agendarRecalculoParcelas();
        } else {
            // Limpar timeout se estiver agendado
            if (parcelamentoTimeout) {
                clearTimeout(parcelamentoTimeout);
                parcelamentoTimeout = null;
            }
            configParcelamento.style.display = 'none';
            document.getElementById('resumo-parcelas').style.display = 'none';
            dataVencimento.required = true;
        }
    });
    
    // Recalcular parcelas quando valores mudarem
    // Usar apenas 'input' para campos de texto (valor_total, entrada) e 'change' para selects
    // Isso evita duplicação de eventos
    // Usar debounce para evitar múltiplas execuções durante digitação
    if (valorTotal) {
        valorTotal.addEventListener('input', agendarRecalculoParcelas);
    }
    if (entrada) {
        entrada.addEventListener('input', agendarRecalculoParcelas);
    }
    if (numParcelas) {
        numParcelas.addEventListener('change', agendarRecalculoParcelas);
    }
    
    // Campo de frequência
    const frequenciaParcelas = document.getElementById('frequencia_parcelas');
    if (frequenciaParcelas) {
        frequenciaParcelas.addEventListener('change', function() {
            const containerIntervaloDias = document.getElementById('container-intervalo-dias');
            if (this.value === 'days') {
                containerIntervaloDias.style.display = 'block';
            } else {
                containerIntervaloDias.style.display = 'none';
            }
            agendarRecalculoParcelas();
        });
    }
    
    // Campo de intervalo em dias (só aparece quando frequência = days)
    if (intervaloParcelas) {
        intervaloParcelas.addEventListener('change', agendarRecalculoParcelas);
        intervaloParcelas.addEventListener('input', agendarRecalculoParcelas);
    }
    
    // Campo data_vencimento - recalcular parcelas quando mudar
    if (dataVencimento) {
        dataVencimento.addEventListener('change', agendarRecalculoParcelas);
    }
}

// Funções auxiliares para trabalhar com datas sem problemas de fuso horário
function parseDateLocal(dateString) {
    // Converte string YYYY-MM-DD para Date no fuso local, sem conversão UTC
    // Evita o problema de "menos 1 dia" causado por fuso horário
    const [ano, mes, dia] = dateString.split('-').map(Number);
    return new Date(ano, mes - 1, dia); // mes - 1 porque Date usa 0-11 para meses
}

function formatDateLocal(date) {
    // Converte Date para string YYYY-MM-DD no fuso local, sem conversão UTC
    // Evita o problema de "menos 1 dia" causado por toISOString()
    const ano = date.getFullYear();
    const mes = String(date.getMonth() + 1).padStart(2, '0'); // +1 porque Date usa 0-11
    const dia = String(date.getDate()).padStart(2, '0');
    return `${ano}-${mes}-${dia}`;
}

// Função para calcular vencimentos das parcelas
function calcularVencimentosParcelas(opcoes) {
    const {
        dataPrimeiraParcela, // Date ou string YYYY-MM-DD
        quantidadeParcelas,
        frequencia,          // 'monthly' | 'days'
        intervaloDias        // number, usado só se frequencia === 'days'
    } = opcoes;

    const vencimentos = [];
    
    // Converter dataPrimeiraParcela para Date no fuso local
    let base;
    if (dataPrimeiraParcela instanceof Date) {
        // Se já é Date, criar nova Date com os componentes locais para evitar problemas
        base = new Date(dataPrimeiraParcela.getFullYear(), dataPrimeiraParcela.getMonth(), dataPrimeiraParcela.getDate());
    } else if (typeof dataPrimeiraParcela === 'string') {
        // Se é string YYYY-MM-DD, usar parseDateLocal
        base = parseDateLocal(dataPrimeiraParcela);
    } else {
        base = new Date(dataPrimeiraParcela);
    }
    
    const diaBase = base.getDate();

    for (let i = 0; i < quantidadeParcelas; i++) {
        let d = new Date(base);

        if (frequencia === 'monthly') {
            // Avança i meses mantendo o dia, caindo no mesmo dia
            // (se o mês não tiver esse dia, usar o último dia do mês)
            const targetMonth = d.getMonth() + i;
            const targetYear = d.getFullYear() + Math.floor(targetMonth / 12);
            const monthIndex = (targetMonth % 12 + 12) % 12;

            // Cria a data tentativamente com o mesmo dia
            d = new Date(targetYear, monthIndex, diaBase);

            // Se o dia "voltou" (ex: pedimos dia 31/02), ajustar para último dia do mês
            if (d.getMonth() !== monthIndex) {
                d = new Date(targetYear, monthIndex + 1, 0); // dia 0 do próximo mês = último dia do mês desejado
            }
        } else if (frequencia === 'days') {
            const dias = intervaloDias || 30;
            d.setDate(d.getDate() + i * dias);
        }

        vencimentos.push(d);
    }

    return vencimentos;
}

// Calcular e exibir parcelas
function calcularParcelas() {
    // Prevenir loops recursivos
    if (isCalculandoParcelas) {
        return;
    }
    
    isCalculandoParcelas = true;
    
    try {
        const parcelamentoCheckbox = document.getElementById('parcelamento');
        // Curto-circuito: se toggle desligado, não calcular
        if (!parcelamentoCheckbox || !parcelamentoCheckbox.checked) {
            isCalculandoParcelas = false;
            return;
        }
    
        // Usar helper para parse de valores brasileiros
        const valorTotalInput = document.getElementById('valor_total');
        const entradaInput = document.getElementById('entrada');
        const numParcelasSelect = document.getElementById('num_parcelas');
        const intervaloParcelasInput = document.getElementById('intervalo_parcelas');
        const frequenciaParcelasSelect = document.getElementById('frequencia_parcelas');
        const dataVencimentoInput = document.getElementById('data_vencimento');
        
        // Validação de elementos
        if (!valorTotalInput || !entradaInput || !numParcelasSelect || !frequenciaParcelasSelect) {
            isCalculandoParcelas = false;
            return;
        }
        
        const valorTotal = parseValorBrasileiro(valorTotalInput.value);
        const entrada = parseValorBrasileiro(entradaInput.value);
        const numParcelas = parseInt(numParcelasSelect.value) || 1;
        const frequencia = frequenciaParcelasSelect.value || 'monthly';
        const intervaloDias = frequencia === 'days' && intervaloParcelasInput ? parseInt(intervaloParcelasInput.value) || 30 : 30;
        const dataVencimento = dataVencimentoInput ? dataVencimentoInput.value.trim() : '';
        
        // Curto-circuito: valores inválidos
        if (isNaN(valorTotal) || valorTotal <= 0) {
            const resumoParcelas = document.getElementById('resumo-parcelas');
            if (resumoParcelas) {
                resumoParcelas.style.display = 'none';
            }
            isCalculandoParcelas = false;
            return;
        }
        
        // VALIDAÇÃO CRÍTICA: data_vencimento é obrigatória para parcelamento
        if (!dataVencimento) {
            const resumoParcelas = document.getElementById('resumo-parcelas');
            if (resumoParcelas) {
                resumoParcelas.style.display = 'none';
            }
            // Exibir mensagem amigável
            if (dataVencimentoInput) {
                dataVencimentoInput.classList.add('is-invalid');
                if (typeof showAlert === 'function') {
                    showAlert('warning', 'Por favor, defina a data de vencimento da fatura para calcular as parcelas.');
                }
            }
            isCalculandoParcelas = false;
            return;
        }
        
        // Validar formato da data (deve ser YYYY-MM-DD)
        if (!/^\d{4}-\d{2}-\d{2}$/.test(dataVencimento)) {
            const resumoParcelas = document.getElementById('resumo-parcelas');
            if (resumoParcelas) {
                resumoParcelas.style.display = 'none';
            }
            if (dataVencimentoInput) {
                dataVencimentoInput.classList.add('is-invalid');
                if (typeof showAlert === 'function') {
                    showAlert('warning', 'Data de vencimento inválida. Por favor, corrija.');
                }
            }
            isCalculandoParcelas = false;
            return;
        }
        
        // Converter data_vencimento para Date no fuso local (sem problemas de UTC)
        // Usar parseDateLocal para evitar o bug de "menos 1 dia"
        const dataBase = parseDateLocal(dataVencimento);
        
        // Validar se a data é válida
        if (isNaN(dataBase.getTime())) {
            const resumoParcelas = document.getElementById('resumo-parcelas');
            if (resumoParcelas) {
                resumoParcelas.style.display = 'none';
            }
            if (dataVencimentoInput) {
                dataVencimentoInput.classList.add('is-invalid');
                if (typeof showAlert === 'function') {
                    showAlert('warning', 'Data de vencimento inválida. Por favor, corrija.');
                }
            }
            isCalculandoParcelas = false;
            return;
        }
        
        // Remover classe de erro se data for válida
        if (dataVencimentoInput) {
            dataVencimentoInput.classList.remove('is-invalid');
        }
        
        // Validação de número de parcelas (limite máximo para segurança)
        const MAX_PARCELAS = 240;
        const numParcelasValido = Math.min(Math.max(1, numParcelas), MAX_PARCELAS);
        
        // Validação de entrada (não pode ser negativa)
        const entradaValida = Math.max(0, entrada);
    
        // Calcular valor das parcelas
        const valorRestante = valorTotal - entradaValida;
        
        // Proteção contra divisão por zero
        if (numParcelasValido <= 0) {
            isCalculandoParcelas = false;
            return;
        }
        
        const valorParcela = valorRestante / numParcelasValido;
        
        // Gerar tabela de parcelas
        const tabelaParcelas = document.getElementById('tabela-parcelas');
        if (!tabelaParcelas) {
            isCalculandoParcelas = false;
            return;
        }
        
        // Limpar tabela de forma eficiente (evitar múltiplas reflows)
        tabelaParcelas.innerHTML = '';
        
        // Data base para cálculo: SEMPRE usar data_vencimento como referência central
        // dataBase já foi criada acima usando parseDateLocal (sem problemas de fuso)
        
        // Se houver entrada, ela usa a mesma data_vencimento
        // As parcelas seguem a partir dessa data base, respeitando a frequência
        if (entradaValida > 0.009) {
            // Entrada sempre usa data_vencimento como vencimento (exatamente a mesma data)
            // Usar formatDateLocal para evitar problemas de fuso horário
            const dataEntradaFormatada = formatDateLocal(dataBase);
            const row = document.createElement('tr');
            row.innerHTML = `
                <td><strong>Entrada</strong></td>
                <td><strong>R$ ${formatarValorBrasileiro(entradaValida)}</strong></td>
                <td>${formatarData(dataBase)}</td>
            `;
            tabelaParcelas.appendChild(row);
        }
        
        // Calcular vencimentos das parcelas
        // IMPORTANTE: Se houver entrada, as parcelas começam a partir da data_vencimento
        // Se não houver entrada, a 1ª parcela é na data_vencimento
        // Em ambos os casos, data_vencimento é a referência central
        const vencimentos = calcularVencimentosParcelas({
            dataPrimeiraParcela: dataBase, // Sempre data_vencimento
            quantidadeParcelas: numParcelasValido,
            frequencia: frequencia,
            intervaloDias: intervaloDias
        });
        
        // Adicionar parcelas com proteção contra loops infinitos
        // Calcular valor base da parcela (arredondado para baixo em centavos)
        const valorParcelaBase = Math.floor((valorRestante * 100) / numParcelasValido) / 100;
        // Calcular diferença total (o que sobra devido ao arredondamento)
        const diferencaTotal = Math.round((valorRestante - (valorParcelaBase * numParcelasValido)) * 100) / 100;
        
        // Adicionar parcelas com limite máximo de segurança
        for (let i = 0; i < numParcelasValido && i < vencimentos.length; i++) {
            // A última parcela recebe o valor base + a diferença para garantir soma exata
            let valorParcelaAtual = (i === numParcelasValido - 1) 
                ? valorParcelaBase + diferencaTotal
                : valorParcelaBase;
            
            // Garantir que não fique negativo ou muito pequeno
            valorParcelaAtual = Math.max(0, Math.round(valorParcelaAtual * 100) / 100);
            
            // Se o valor for muito pequeno (menos de 1 centavo), não adicionar
            if (valorParcelaAtual < 0.01) {
                continue;
            }
            
            const dataParcela = vencimentos[i];
            // Formatar data para input type="date" (YYYY-MM-DD)
            // Usar formatDateLocal para evitar problemas de fuso horário (bug de "menos 1 dia")
            const dataFormatadaInput = formatDateLocal(dataParcela);
            
            const row = document.createElement('tr');
            row.setAttribute('data-parcela-index', i);
            row.innerHTML = `
                <td>${i + 1}ª parcela</td>
                <td>R$ ${formatarValorBrasileiro(valorParcelaAtual)}</td>
                <td>
                    <input type="date" 
                           class="form-control form-control-sm js-parcela-vencimento" 
                           data-index="${i}"
                           data-valor="${valorParcelaAtual.toFixed(2)}"
                           value="${dataFormatadaInput}"
                           style="min-width: 150px;">
                </td>
            `;
            tabelaParcelas.appendChild(row);
        }
    
        // Mostrar resumo
        const resumoParcelas = document.getElementById('resumo-parcelas');
        if (resumoParcelas) {
            resumoParcelas.style.display = 'block';
        }
        
        // Validar se entrada não excede valor total
        if (entradaValida > valorTotal) {
            entradaInput.classList.add('is-invalid');
            if (typeof showAlert === 'function') {
                showAlert('warning', 'O valor da entrada não pode ser maior que o valor total.');
            }
        } else {
            entradaInput.classList.remove('is-invalid');
        }
    } finally {
        // Sempre liberar o flag, mesmo em caso de erro
        isCalculandoParcelas = false;
    }
}

// Formatar data para exibição
function formatarData(data) {
    return data.toLocaleDateString('pt-BR');
}

/**
 * Abre o modal de Nova Fatura
 * @param {number|null} alunoId - ID do aluno para pré-selecionar (opcional)
 * @param {number|null} matriculaId - ID da matrícula para sugerir descrição (opcional)
 */
function novaFatura(alunoId = null, matriculaId = null) {
    // Abrir modal primeiro
    const modal = document.getElementById('modalNovaFatura');
    modal.setAttribute('data-opened', 'true');
    
    // Limpar formulário após modal abrir
    setTimeout(() => {
        document.getElementById('formNovaFatura').reset();
        
        // Se aluno_id foi passado via GET, pré-selecionar
        if (!alunoId && window.alunoIdGet) {
            alunoId = window.alunoIdGet;
        }
        
        // Pré-selecionar aluno se fornecido
        const alunoSelect = document.getElementById('aluno_id_modal');
        if (alunoId && alunoSelect) {
            alunoSelect.value = alunoId;
            // Disparar evento change para carregar dados relacionados (isso já vai buscar a descrição via listener)
            alunoSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (window.alunoIdGet && alunoSelect) {
            // Se houver aluno_id no GET mas não foi passado como parâmetro
            alunoSelect.value = window.alunoIdGet;
            alunoSelect.dispatchEvent(new Event('change', { bubbles: true }));
        } else if (window.descricaoSugestaoFatura) {
            // Se já veio do PHP (quando há aluno_id na URL), aplicar diretamente
            setTimeout(() => aplicarDescricaoSugerida(window.descricaoSugestaoFatura), 300);
        }
        
        // Definir data de vencimento padrão
        const hoje = new Date();
        const vencimento = new Date(hoje.getFullYear(), hoje.getMonth(), hoje.getDate() + 30);
        // Usar formatDateLocal para evitar problemas de fuso horário
        document.getElementById('data_vencimento').value = formatDateLocal(vencimento);
        
        // Resetar configurações de parcelamento
        document.getElementById('parcelamento').checked = false;
        document.getElementById('config-parcelamento').style.display = 'none';
        document.getElementById('resumo-parcelas').style.display = 'none';
        document.getElementById('num_parcelas').value = '4';
        document.getElementById('frequencia_parcelas').value = 'monthly';
        const containerIntervaloDias = document.getElementById('container-intervalo-dias');
        if (containerIntervaloDias) {
            containerIntervaloDias.style.display = 'none';
        }
        const intervaloParcelas = document.getElementById('intervalo_parcelas');
        if (intervaloParcelas) {
            intervaloParcelas.value = '30';
        }
        
        // Reinicializar máscaras após reset do formulário
        if (typeof inicializarMascarasMoeda === 'function') {
            inicializarMascarasMoeda();
        }
        
        // Limpar campos de valor explicitamente
        const entradaInput = document.getElementById('entrada');
        if (entradaInput) {
            entradaInput.value = '';
            if (window.entradaMask) {
                window.entradaMask.updateValue();
            }
        }
        
        const valorTotalInput = document.getElementById('valor_total');
        if (valorTotalInput) {
            valorTotalInput.value = '';
            if (window.valorTotalMask) {
                window.valorTotalMask.updateValue();
            }
        }
    }, 150);
}

function fecharModalNovaFatura() {
    const modal = document.getElementById('modalNovaFatura');
    modal.setAttribute('data-opened', 'false');
}

/**
 * Configura navegação com Enter entre campos do modal Nova Fatura
 * Permite navegar pelos campos usando Enter sem submeter o formulário
 */
function configurarNavegacaoEnter() {
    const modal = document.getElementById('modalNovaFatura');
    if (!modal) return;
    
    // Ordem de foco lógica dos campos (exceto textarea que mantém comportamento padrão)
    const ordemFoco = [
        'aluno_id_modal',      // 1. Aluno
        'valor_total',         // 2. Valor Total
        'data_vencimento',     // 3. Data de Vencimento
        'status',              // 4. Status
        'entrada',             // 5. Entrada (se parcelamento ativado)
        'num_parcelas',        // 6. Número de Parcelas (se parcelamento ativado)
        'intervalo_parcelas',  // 7. Intervalo (se parcelamento ativado e frequência = days)
        // 'descricao' é textarea - não interceptar Enter aqui
    ];
    
    // Adicionar listener apenas dentro do modal
    modal.addEventListener('keydown', function(e) {
        // Apenas processar se for Enter e não for textarea
        if (e.key !== 'Enter' || e.target.tagName === 'TEXTAREA') {
            return;
        }
        
        // Não processar se estiver em botões (deixar comportamento padrão)
        if (e.target.tagName === 'BUTTON' || e.target.type === 'submit') {
            return;
        }
        
        // Não processar se estiver em select (deixar comportamento padrão)
        if (e.target.tagName === 'SELECT') {
            return;
        }
        
        // Prevenir submit do formulário
        e.preventDefault();
        
        // Encontrar índice atual do campo na ordem
        const campoAtual = e.target.id;
        const indiceAtual = ordemFoco.indexOf(campoAtual);
        
        // Encontrar próximo campo visível e habilitado
        let proximoIndice = indiceAtual + 1;
        let proximoCampo = null;
        
        while (proximoIndice < ordemFoco.length && !proximoCampo) {
            const proximoId = ordemFoco[proximoIndice];
            const proximoElemento = document.getElementById(proximoId);
            
            if (proximoElemento && 
                !proximoElemento.disabled && 
                proximoElemento.offsetParent !== null) { // Verifica se está visível
                proximoCampo = proximoElemento;
                break;
            }
            proximoIndice++;
        }
        
        // Se não encontrou próximo campo na ordem, tentar focar descrição ou botão salvar
        if (!proximoCampo) {
            const descricaoField = document.getElementById('descricao');
            if (descricaoField && descricaoField.offsetParent !== null) {
                proximoCampo = descricaoField;
            } else {
                // Último recurso: focar botão salvar
                const btnSalvar = document.querySelector('#formNovaFatura button[type="submit"]');
                if (btnSalvar) {
                    proximoCampo = btnSalvar;
                }
            }
        }
        
        // Focar próximo campo
        if (proximoCampo) {
            proximoCampo.focus();
            // Se for input, selecionar texto para facilitar edição
            if (proximoCampo.tagName === 'INPUT' && proximoCampo.type !== 'date') {
                proximoCampo.select();
            }
        }
    });
}

// Configurar navegação com Enter quando o DOM estiver pronto
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', configurarNavegacaoEnter);
} else {
    configurarNavegacaoEnter();
}

// Submissão do formulário
document.getElementById('formNovaFatura').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    // Verificar se é parcelamento e validar datas editadas
    const parcelamentoCheckbox = document.getElementById('parcelamento');
    if (parcelamentoCheckbox && parcelamentoCheckbox.checked) {
        const inputsVencimento = document.querySelectorAll('.js-parcela-vencimento');
        const vencimentosEditados = [];
        let temErro = false;
        let linhaErro = null;
        
        // Verificar se há entrada e adicionar ao array
        const entradaInput = document.getElementById('entrada');
        const entradaValor = entradaInput ? parseValorBrasileiro(entradaInput.value) : 0;
        const dataVencimentoInput = document.getElementById('data_vencimento');
        const dataVencimentoBase = dataVencimentoInput ? dataVencimentoInput.value : '';
        
        if (entradaValor > 0.009 && dataVencimentoBase) {
            vencimentosEditados.push({
                tipo: 'entrada',
                vencimento: dataVencimentoBase,
                valor: entradaValor.toFixed(2)
            });
        }
        
        // Validar e adicionar parcelas
        inputsVencimento.forEach((input, index) => {
            const dataVencimento = input.value.trim();
            const valorParcela = parseFloat(input.getAttribute('data-valor')) || 0;
            
            // Validar se está preenchido
            if (!dataVencimento) {
                temErro = true;
                linhaErro = index + 1;
                input.classList.add('is-invalid');
                return;
            }
            
            // Validar se é uma data válida
            const dataParsed = new Date(dataVencimento);
            if (isNaN(dataParsed.getTime())) {
                temErro = true;
                linhaErro = index + 1;
                input.classList.add('is-invalid');
                return;
            }
            
            // Data válida
            input.classList.remove('is-invalid');
            vencimentosEditados.push({
                numero: index + 1,
                vencimento: dataVencimento,
                valor: valorParcela.toFixed(2),
                tipo: 'parcela'
            });
        });
        
        // Se houver erro de validação, exibir mensagem e não enviar
        if (temErro) {
            showAlert('danger', `Por favor, corrija o vencimento inválido na ${linhaErro}ª parcela.`);
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Fatura';
            return;
        }
        
        // Adicionar vencimentos editados ao FormData
        if (vencimentosEditados.length > 0) {
            formData.set('parcelas_editadas', JSON.stringify(vencimentosEditados));
        }
    }
    
    // Converter valor_total de formato brasileiro para numérico (ponto decimal)
    const valorTotalStr = formData.get('valor_total');
    if (valorTotalStr) {
        const valorTotalNum = parseValorBrasileiro(valorTotalStr);
        formData.set('valor_total', valorTotalNum.toFixed(2));
    }
    
    // Converter entrada de formato brasileiro para numérico (ponto decimal)
    const entradaStr = formData.get('entrada');
    if (entradaStr) {
        const entradaNum = parseValorBrasileiro(entradaStr);
        formData.set('entrada', entradaNum.toFixed(2));
    }
    
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
    .then(response => {
        // Primeiro obter o texto da resposta para debug
        return response.text().then(text => {
            try {
                // Tentar fazer parse do JSON
                const data = JSON.parse(text);
                return { ok: response.ok, data: data };
            } catch (e) {
                // Se não for JSON válido, logar o erro e o conteúdo
                console.error('Resposta não é JSON válido:', text);
                console.error('Erro de parsing:', e);
                throw new Error('Resposta do servidor não é JSON válido. Verifique o console para detalhes.');
            }
        });
    })
    .then(({ ok, data }) => {
        if (!ok || !data.success) {
            // Erro retornado pelo servidor
            // Exibir debug no console para facilitar diagnóstico
            if (data.debug) {
                console.error('Erro ao criar fatura - Debug:', data.debug);
                console.error('Tipo:', data.debug.type);
                console.error('Mensagem:', data.debug.msg);
                console.error('Arquivo:', data.debug.file, 'Linha:', data.debug.line);
            } else {
                console.error('Erro ao criar fatura:', data);
            }
            
            // Exibir mensagem amigável ao usuário
            showAlert('danger', data.message || 'Erro ao criar fatura');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
            return;
        }
        
        // Sucesso
        showAlert('success', data.message || 'Fatura criada com sucesso!');
        
        // Fechar modal
        fecharModalNovaFatura();
        
        // Recarregar página para mostrar nova fatura
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    })
    .catch(error => {
        console.error('Erro na requisição:', error);
        showAlert('danger', error.message || 'Erro de conexão. Tente novamente.');
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    });
});

// Handler de submit do formulário de edição
// Usa: PUT admin/api/financeiro-faturas.php?id={id}
document.getElementById('formEditarFatura').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const faturaId = document.getElementById('editar_fatura_id').value;
    if (!faturaId) {
        showAlert('danger', 'ID da fatura não encontrado.');
        return;
    }
    
    // Validar data de vencimento (obrigatória)
    const dataVencimento = document.getElementById('editar_data_vencimento').value;
    if (!dataVencimento) {
        showAlert('danger', 'Data de vencimento é obrigatória.');
        document.getElementById('editar_data_vencimento').focus();
        return;
    }
    
    // Validar formato da data (YYYY-MM-DD)
    if (!/^\d{4}-\d{2}-\d{2}$/.test(dataVencimento)) {
        showAlert('danger', 'Formato de data inválido. Use o formato YYYY-MM-DD.');
        return;
    }
    
    // Coletar dados do formulário
    const payload = {
        data_vencimento: dataVencimento
    };
    
    // Adicionar título se foi alterado
    const titulo = document.getElementById('editar_titulo').value.trim();
    if (titulo) {
        payload.titulo = titulo;
    }
    
    // Adicionar forma de pagamento se foi alterada
    const formaPagamento = document.getElementById('editar_forma_pagamento').value;
    if (formaPagamento) {
        payload.forma_pagamento = formaPagamento;
    }
    
    const submitBtn = document.getElementById('btnSalvarEdicao');
    const originalText = submitBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    
    try {
        // Fazer requisição PUT para atualizar fatura
        const apiUrl = getApiFaturasUrl(`id=${faturaId}`);
        console.log('💾 Salvando fatura via:', apiUrl);
        const response = await fetch(apiUrl, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        // Ler texto primeiro para evitar erro de parsing
        const responseText = await response.text();
        const contentType = response.headers.get('content-type');
        let data;
        
        // Verificar se a resposta é JSON
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Resposta não é JSON. Status:', response.status);
            console.error('❌ Content-Type:', contentType);
            console.error('❌ Primeiros 500 caracteres da resposta:', responseText.substring(0, 500));
            
            // Se for 404, dar mensagem mais específica
            if (response.status === 404) {
                throw new Error(`API não encontrada (404). Verifique se o arquivo admin/api/financeiro-faturas.php existe. URL tentada: ${apiUrl}`);
            }
            
            throw new Error(`Erro ao atualizar fatura: Servidor retornou ${response.status} (${response.statusText}). A resposta não é JSON válido.`);
        }
        
        // Tentar fazer parse do JSON
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            console.error('❌ Conteúdo recebido:', responseText.substring(0, 500));
            throw new Error('Resposta do servidor não é JSON válido. Verifique o console para detalhes.');
        }
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || data.message || 'Erro ao atualizar fatura');
        }
        
        // Sucesso
        showAlert('success', data.message || 'Fatura atualizada com sucesso!');
        
        // Fechar modal
        fecharModalEditarFatura();
        
        // Recarregar página mantendo parâmetros da URL (filtros, paginação, etc.)
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
    } catch (error) {
        console.error('Erro ao atualizar fatura:', error);
        showAlert('danger', 'Erro ao atualizar fatura: ' + error.message);
        
        // Reabilitar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

// Handler do formulário de registrar pagamento
document.getElementById('formRegistrarPagamento').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const faturaId = document.getElementById('pagamento_fatura_id').value;
    if (!faturaId) {
        showAlert('danger', 'ID da fatura não encontrado.');
        return;
    }
    
    const submitBtn = document.getElementById('btnSalvarPagamento');
    const originalText = submitBtn.innerHTML;
    
    // Desabilitar botão e mostrar loading
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Registrando...';
    
    try {
        // Montar payload
        const payload = {
            fatura_id: parseInt(faturaId),
            data_pagamento: document.getElementById('pagamento_data_pagamento').value,
            valor_pago: parseFloat(document.getElementById('pagamento_valor_pago').value),
            metodo: document.getElementById('pagamento_metodo').value,
            obs: document.getElementById('pagamento_obs').value || null
        };
        
        // Validar valor pago
        if (payload.valor_pago <= 0) {
            throw new Error('O valor pago deve ser maior que zero.');
        }
        
        console.log('💰 Registrando pagamento:', payload);
        
        // Fazer requisição POST para registrar pagamento
        const apiUrl = getApiPagamentosUrl();
        const response = await fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(payload)
        });
        
        // Ler texto primeiro para evitar erro de parsing
        const responseText = await response.text();
        const contentType = response.headers.get('content-type');
        let data;
        
        // Verificar se a resposta é JSON
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Resposta não é JSON. Status:', response.status);
            console.error('❌ Content-Type:', contentType);
            console.error('❌ Primeiros 500 caracteres da resposta:', responseText.substring(0, 500));
            
            if (response.status === 404) {
                throw new Error(`API não encontrada (404). Verifique se o arquivo admin/api/pagamentos.php existe.`);
            }
            
            throw new Error(`Erro ao registrar pagamento: Servidor retornou ${response.status} (${response.statusText}). A resposta não é JSON válido.`);
        }
        
        // Tentar fazer parse do JSON
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            console.error('❌ Conteúdo recebido:', responseText.substring(0, 500));
            throw new Error('Resposta do servidor não é JSON válido. Verifique o console para detalhes.');
        }
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || data.message || 'Erro ao registrar pagamento');
        }
        
        // Sucesso
        console.log('✅ Pagamento registrado com sucesso:', data);
        showAlert('success', data.message || 'Pagamento registrado com sucesso!');
        
        // Fechar modal
        fecharModalRegistrarPagamento();
        
        // Recarregar página mantendo parâmetros da URL (filtros, paginação, etc.)
        setTimeout(() => {
            window.location.reload();
        }, 1000);
        
    } catch (error) {
        console.error('❌ Erro ao registrar pagamento:', error);
        showAlert('danger', 'Erro ao registrar pagamento: ' + error.message);
        
        // Reabilitar botão
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalText;
    }
});

/**
 * Função auxiliar para construir URL da API de faturas
 * Retorna caminho relativo baseado na URL atual
 * Se estamos em admin/index.php, api/ resolve para admin/api/
 */
function getApiFaturasUrl(params = '') {
    const pathname = window.location.pathname;
    
    // Se estamos em admin/index.php, usar caminho relativo api/
    // O navegador resolve api/ relativo ao diretório da URL atual (admin/)
    if (pathname.includes('/admin/')) {
        const relativeUrl = `api/financeiro-faturas.php${params ? '?' + params : ''}`;
        console.log('📍 Pathname:', pathname);
        console.log('📍 URL da API:', relativeUrl);
        return relativeUrl;
    }
    
    // Fallback: construir caminho absoluto
    const absoluteUrl = `/admin/api/financeiro-faturas.php${params ? '?' + params : ''}`;
    console.log('📍 URL absoluta (fallback):', absoluteUrl);
    return absoluteUrl;
}

/**
 * Função auxiliar para construir URL da API de pagamentos
 */
function getApiPagamentosUrl(params = '') {
    const pathname = window.location.pathname;
    
    if (pathname.includes('/admin/')) {
        const relativeUrl = `api/pagamentos.php${params ? '?' + params : ''}`;
        return relativeUrl;
    }
    
    const absoluteUrl = `/admin/api/pagamentos.php${params ? '?' + params : ''}`;
    return absoluteUrl;
}

/**
 * Abre modal de edição e carrega dados da fatura via API GET
 * Usa: admin/api/financeiro-faturas.php?id={id}
 */
async function editarFatura(id) {
    if (!id) {
        showAlert('danger', 'ID da fatura não fornecido.');
        return;
    }
    
    const modal = document.getElementById('modalEditarFatura');
    if (!modal) {
        showAlert('danger', 'Modal de edição não encontrado.');
        return;
    }
    
    // Mostrar modal com loading (usar padrão data-opened)
    modal.setAttribute('data-opened', 'true');
    
    // Desabilitar formulário enquanto carrega
    const form = document.getElementById('formEditarFatura');
    const submitBtn = document.getElementById('btnSalvarEdicao');
    form.querySelectorAll('input, select, button').forEach(el => el.disabled = true);
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Carregando...';
    
    try {
        // Buscar dados da fatura via API GET
        const apiUrl = getApiFaturasUrl(`id=${id}`);
        console.log('🔍 Carregando fatura via:', apiUrl);
        const response = await fetch(apiUrl);
        
        // Ler texto primeiro para evitar erro de parsing
        const responseText = await response.text();
        const contentType = response.headers.get('content-type');
        let data;
        
        // Verificar se a resposta é JSON
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Resposta não é JSON. Status:', response.status);
            console.error('❌ Content-Type:', contentType);
            console.error('❌ Primeiros 500 caracteres da resposta:', responseText.substring(0, 500));
            
            // Se for 404, dar mensagem mais específica
            if (response.status === 404) {
                throw new Error(`API não encontrada (404). Verifique se o arquivo admin/api/financeiro-faturas.php existe. URL tentada: ${apiUrl}`);
            }
            
            throw new Error(`Erro ao carregar fatura: Servidor retornou ${response.status} (${response.statusText}). A resposta não é JSON válido.`);
        }
        
        // Tentar fazer parse do JSON
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            console.error('❌ Conteúdo recebido:', responseText.substring(0, 500));
            throw new Error('Resposta do servidor não é JSON válido. Verifique o console para detalhes.');
        }
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Erro ao carregar dados da fatura');
        }
        
        const fatura = data.data;
        
        // Buscar pagamentos da fatura para resumo
        console.log('✏️ Buscando pagamentos para resumo na edição da fatura:', fatura.id);
        let totalPago = 0;
        let ultimoPagamento = null;
        
        try {
            const apiUrlPagamentos = getApiPagamentosUrl(`fatura_id=${fatura.id}`);
            const responsePagamentos = await fetch(apiUrlPagamentos);
            const responseTextPagamentos = await responsePagamentos.text();
            const contentTypePagamentos = responsePagamentos.headers.get('content-type');
            
            if (contentTypePagamentos && contentTypePagamentos.includes('application/json')) {
                const dataPagamentos = JSON.parse(responseTextPagamentos);
                if (dataPagamentos.success && dataPagamentos.pagamentos) {
                    totalPago = dataPagamentos.pagamentos.reduce((sum, p) => sum + parseFloat(p.valor_pago || 0), 0);
                    if (dataPagamentos.pagamentos.length > 0) {
                        ultimoPagamento = dataPagamentos.pagamentos[0]; // Já vem ordenado por data_pagamento DESC
                    }
                    console.log('✏️ Resumo de pagamentos:', { totalPago, ultimoPagamento: ultimoPagamento?.data_pagamento });
                }
            }
        } catch (error) {
            console.warn('⚠️ Erro ao buscar pagamentos para resumo (continuando edição):', error);
        }
        
        // Calcular saldo em aberto
        const valorTotal = parseFloat(fatura.valor || fatura.valor_total || 0);
        const saldoAberto = Math.max(0, valorTotal - totalPago);
        
        // Preencher campos do formulário
        document.getElementById('editar_fatura_id').value = fatura.id;
        
        // Aluno (somente leitura)
        document.getElementById('editar_aluno_nome').textContent = fatura.aluno_nome || 'Aluno não encontrado';
        document.getElementById('editar_aluno_cpf').textContent = fatura.cpf ? ` • ${fatura.cpf}` : '';
        
        // Valor (somente leitura)
        document.getElementById('editar_valor').value = valorTotal.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        
        // Título/Descrição (editável)
        document.getElementById('editar_titulo').value = fatura.titulo || '';
        
        // Data de Vencimento (editável) - já vem em YYYY-MM-DD da API
        document.getElementById('editar_data_vencimento').value = fatura.data_vencimento || fatura.vencimento || '';
        
        // Forma de Pagamento (editável)
        const formaPagamento = fatura.forma_pagamento || 'avista';
        document.getElementById('editar_forma_pagamento').value = formaPagamento;
        
        // Preencher resumo de pagamentos
        const totalPagoFormatado = totalPago.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.getElementById('editar_total_pago').value = totalPagoFormatado;
        
        const saldoAbertoFormatado = saldoAberto.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.getElementById('editar_saldo_aberto').value = saldoAbertoFormatado;
        
        // Último pagamento
        let ultimoPagamentoTexto = '-';
        if (ultimoPagamento && ultimoPagamento.data_pagamento) {
            const dataUltimoPag = new Date(ultimoPagamento.data_pagamento);
            if (!isNaN(dataUltimoPag.getTime())) {
                ultimoPagamentoTexto = dataUltimoPag.toLocaleDateString('pt-BR');
            }
        }
        document.getElementById('editar_ultimo_pagamento').querySelector('span').textContent = ultimoPagamentoTexto;
        
        // Reabilitar formulário
        form.querySelectorAll('input, select, button').forEach(el => {
            if (el.id !== 'editar_valor' && el.id !== 'editar_aluno_info' && 
                el.id !== 'editar_total_pago' && el.id !== 'editar_saldo_aberto' && 
                el.id !== 'editar_ultimo_pagamento') {
                el.disabled = false;
            }
        });
        submitBtn.innerHTML = '<i class="fas fa-save me-1"></i>Salvar Alterações';
        
    } catch (error) {
        console.error('Erro ao carregar fatura:', error);
        showAlert('danger', 'Erro ao carregar dados da fatura: ' + error.message);
        
        // Fechar modal em caso de erro
        fecharModalEditarFatura();
    }
}

/**
 * Fecha o modal de edição
 */
function fecharModalEditarFatura() {
    const modal = document.getElementById('modalEditarFatura');
    if (modal) {
        modal.setAttribute('data-opened', 'false');
        
        // Limpar formulário
        const form = document.getElementById('formEditarFatura');
        if (form) {
            form.reset();
        }
    }
}

// Fechar modal de edição ao clicar no backdrop
document.addEventListener('DOMContentLoaded', function() {
    const modalEditar = document.getElementById('modalEditarFatura');
    if (modalEditar) {
        modalEditar.addEventListener('click', function(e) {
            // Se clicou diretamente no backdrop (não no dialog)
            if (e.target === modalEditar) {
                fecharModalEditarFatura();
            }
        });
    }
});

/**
 * Função auxiliar para escapar HTML e prevenir XSS
 */
function escapeHtml(text) {
    if (text === null || text === undefined) return '';
    if (typeof text !== 'string') {
        text = String(text);
    }
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Visualiza uma fatura em modal somente leitura
 */
async function visualizarFatura(id) {
    if (!id) {
        showAlert('danger', 'ID da fatura não fornecido.');
        return;
    }
    
    const modal = document.getElementById('modalVisualizarFatura');
    if (!modal) {
        showAlert('danger', 'Modal de visualização não encontrado.');
        return;
    }
    
    // Mostrar modal com loading
    modal.setAttribute('data-opened', 'true');
    const modalBody = document.getElementById('modalVisualizarFaturaBody');
    
    // Mostrar loading
    modalBody.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-3 text-muted">Carregando dados da fatura...</p>
        </div>
    `;
    
    try {
        // Buscar dados da fatura via API GET
        const apiUrl = getApiFaturasUrl(`id=${id}`);
        console.log('🔍 Visualizando fatura via:', apiUrl);
        const response = await fetch(apiUrl);
        
        // Ler texto primeiro para evitar erro de parsing
        const responseText = await response.text();
        const contentType = response.headers.get('content-type');
        let data;
        
        // Verificar se a resposta é JSON
        if (!contentType || !contentType.includes('application/json')) {
            console.error('❌ Resposta não é JSON. Status:', response.status);
            console.error('❌ Content-Type:', contentType);
            console.error('❌ Primeiros 500 caracteres da resposta:', responseText.substring(0, 500));
            
            if (response.status === 404) {
                throw new Error(`API não encontrada (404). Verifique se o arquivo admin/api/financeiro-faturas.php existe. URL tentada: ${apiUrl}`);
            }
            
            throw new Error(`Erro ao carregar fatura: Servidor retornou ${response.status} (${response.statusText}). A resposta não é JSON válido.`);
        }
        
        // Tentar fazer parse do JSON
        try {
            data = JSON.parse(responseText);
        } catch (parseError) {
            console.error('❌ Erro ao fazer parse do JSON:', parseError);
            console.error('❌ Conteúdo recebido:', responseText.substring(0, 500));
            throw new Error('Resposta do servidor não é JSON válido. Verifique o console para detalhes.');
        }
        
        if (!response.ok || !data.success) {
            throw new Error(data.error || 'Erro ao carregar dados da fatura');
        }
        
        const fatura = data.data;
        
        // Buscar pagamentos da fatura
        console.log('👁️ Buscando pagamentos para visualização da fatura:', fatura.id);
        let pagamentos = [];
        let totalPago = 0;
        let ultimoPagamento = null;
        
        try {
            const apiUrlPagamentos = getApiPagamentosUrl(`fatura_id=${fatura.id}`);
            const responsePagamentos = await fetch(apiUrlPagamentos);
            const responseTextPagamentos = await responsePagamentos.text();
            const contentTypePagamentos = responsePagamentos.headers.get('content-type');
            
            if (contentTypePagamentos && contentTypePagamentos.includes('application/json')) {
                const dataPagamentos = JSON.parse(responseTextPagamentos);
                if (dataPagamentos.success && dataPagamentos.pagamentos) {
                    pagamentos = dataPagamentos.pagamentos;
                    totalPago = pagamentos.reduce((sum, p) => sum + parseFloat(p.valor_pago || 0), 0);
                    if (pagamentos.length > 0) {
                        ultimoPagamento = pagamentos[0]; // Já vem ordenado por data_pagamento DESC
                    }
                    console.log('👁️ Pagamentos encontrados:', pagamentos.length, 'Total pago:', totalPago);
                }
            }
        } catch (error) {
            console.warn('⚠️ Erro ao buscar pagamentos (continuando visualização):', error);
        }
        
        // Calcular saldo em aberto
        const valorTotal = parseFloat(fatura.valor || fatura.valor_total || 0);
        const saldoAberto = Math.max(0, valorTotal - totalPago);
        
        // Escapar valores para prevenir XSS e erros de sintaxe
        const alunoNome = escapeHtml(fatura.aluno_nome || 'Aluno não encontrado');
        const alunoCpf = escapeHtml(fatura.cpf || fatura.aluno_cpf || '');
        const titulo = escapeHtml(fatura.titulo || '-');
        const observacoes = escapeHtml(fatura.observacoes || '');
        
        // Formatar valores
        const valorFormatado = valorTotal.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        const totalPagoFormatado = totalPago.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        const saldoAbertoFormatado = saldoAberto.toLocaleString('pt-BR', {
            style: 'currency',
            currency: 'BRL'
        });
        
        // Formatar data de vencimento
        let dataVencimentoFormatada = '-';
        if (fatura.data_vencimento || fatura.vencimento) {
            const dataVenc = new Date(fatura.data_vencimento || fatura.vencimento);
            if (!isNaN(dataVenc.getTime())) {
                dataVencimentoFormatada = dataVenc.toLocaleDateString('pt-BR');
            }
        }
        
        // Formatar status
        const statusLabels = {
            'aberta': 'Aberta',
            'paga': 'Paga',
            'vencida': 'Vencida',
            'parcial': 'Parcial',
            'cancelada': 'Cancelada'
        };
        const statusLabel = statusLabels[fatura.status] || escapeHtml(fatura.status || 'Aberta');
        const statusClass = {
            'aberta': 'primary',
            'paga': 'success',
            'vencida': 'danger',
            'parcial': 'warning',
            'cancelada': 'secondary'
        }[fatura.status] || 'primary';
        
        // Formatar forma de pagamento
        const formaPagamentoLabels = {
            'avista': 'À Vista',
            'boleto': 'Boleto',
            'pix': 'PIX',
            'cartao': 'Cartão',
            'transferencia': 'Transferência',
            'dinheiro': 'Dinheiro'
        };
        const formaPagamentoLabel = formaPagamentoLabels[fatura.forma_pagamento] || escapeHtml(fatura.forma_pagamento || 'À Vista');
        
        // Formatar método de pagamento
        const metodoLabels = {
            'pix': 'PIX',
            'boleto': 'Boleto',
            'cartao': 'Cartão',
            'dinheiro': 'Dinheiro',
            'transferencia': 'Transferência',
            'outros': 'Outros'
        };
        
        // Montar HTML do conteúdo
        const html = `
            <!-- Seção: Informações Básicas -->
            <div class="mb-4">
                <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                    <i class="fas fa-info-circle me-2"></i>Informações Básicas
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Aluno
                        </label>
                        <div class="form-control" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                            <span>${alunoNome}</span>
                            ${alunoCpf ? `<span class="text-muted ms-2">• ${alunoCpf}</span>` : ''}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Valor Total
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white fw-bold">R$</span>
                            <input type="text" class="form-control" value="${escapeHtml(valorFormatado)}" 
                                   readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Separador Visual -->
            <hr class="my-4" style="border-color: #e5e7eb;">

            <!-- Seção: Resumo Financeiro -->
            <div class="mb-4">
                <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                    <i class="fas fa-calculator me-2"></i>Resumo Financeiro
                </h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Total da Fatura
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-primary text-white fw-bold">R$</span>
                            <input type="text" class="form-control" value="${escapeHtml(valorFormatado)}" 
                                   readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Total Pago
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-success text-white fw-bold">R$</span>
                            <input type="text" class="form-control" value="${escapeHtml(totalPagoFormatado)}" 
                                   readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: #d1e7dd;">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            Saldo em Aberto
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-warning text-white fw-bold">R$</span>
                            <input type="text" class="form-control" value="${escapeHtml(saldoAbertoFormatado)}" 
                                   readonly style="padding: 0.5rem; font-size: 0.9rem; background-color: ${saldoAberto > 0 ? '#fff3cd' : '#d1e7dd'};">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Separador Visual -->
            <hr class="my-4" style="border-color: #e5e7eb;">

            <!-- Seção: Detalhes da Fatura -->
            <div class="mb-4">
                <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                    <i class="fas fa-file-invoice me-2"></i>Detalhes da Fatura
                </h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-align-left me-1"></i>Descrição / Título
                        </label>
                        <div class="form-control" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                            ${titulo}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-calendar me-1"></i>Data de Vencimento
                        </label>
                        <div class="form-control" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                            ${escapeHtml(dataVencimentoFormatada)}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-credit-card me-1"></i>Forma de Pagamento
                        </label>
                        <div class="form-control" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa;">
                            ${formaPagamentoLabel}
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-info-circle me-1"></i>Status
                        </label>
                        <div>
                            <span class="badge bg-${statusClass}">${statusLabel}</span>
                        </div>
                    </div>
                    ${observacoes ? `
                    <div class="col-12">
                        <label class="form-label fw-semibold" style="font-size: 0.9rem; margin-bottom: 0.5rem;">
                            <i class="fas fa-sticky-note me-1"></i>Observações
                        </label>
                        <div class="form-control" style="padding: 0.5rem; font-size: 0.9rem; background-color: #f8f9fa; min-height: 80px;">
                            ${observacoes}
                        </div>
                    </div>
                    ` : ''}
                </div>
            </div>

            <!-- Separador Visual -->
            <hr class="my-4" style="border-color: #e5e7eb;">

            <!-- Seção: Histórico de Pagamentos -->
            <div class="mb-4">
                <h6 class="text-primary mb-3 border-bottom pb-2" style="font-size: 0.95rem; font-weight: 600;">
                    <i class="fas fa-history me-2"></i>Histórico de Pagamentos
                </h6>
                ${pagamentos.length > 0 ? `
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th style="font-size: 0.85rem;">Data</th>
                                <th style="font-size: 0.85rem;">Valor</th>
                                <th style="font-size: 0.85rem;">Método</th>
                                <th style="font-size: 0.85rem;">Observações</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${pagamentos.map(p => {
                                const dataPag = new Date(p.data_pagamento);
                                const dataPagFormatada = !isNaN(dataPag.getTime()) ? dataPag.toLocaleDateString('pt-BR') : '-';
                                const valorPagFormatado = parseFloat(p.valor_pago || 0).toLocaleString('pt-BR', {
                                    style: 'currency',
                                    currency: 'BRL'
                                });
                                const metodoPag = metodoLabels[p.metodo] || escapeHtml(p.metodo || '-');
                                const obsPag = escapeHtml(p.obs || '-');
                                return `
                                    <tr>
                                        <td style="font-size: 0.85rem;">${dataPagFormatada}</td>
                                        <td style="font-size: 0.85rem; font-weight: 600;">${valorPagFormatado}</td>
                                        <td style="font-size: 0.85rem;">${metodoPag}</td>
                                        <td style="font-size: 0.85rem; color: #6c757d;">${obsPag}</td>
                                    </tr>
                                `;
                            }).join('')}
                        </tbody>
                    </table>
                </div>
                ` : `
                <div class="alert alert-info mb-0" style="font-size: 0.9rem;">
                    <i class="fas fa-info-circle me-2"></i>Nenhum pagamento registrado ainda.
                </div>
                `}
            </div>
        `;
        
        // Preencher modal
        modalBody.innerHTML = html;
        
    } catch (error) {
        console.error('Erro ao visualizar fatura:', error);
        showAlert('danger', 'Erro ao carregar dados da fatura: ' + error.message);
        
        // Mostrar mensagem de erro no modal
        modalBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Erro ao carregar fatura:</strong><br>
                ${error.message}
            </div>
        `;
    }
}

/**
 * Fecha o modal de visualização
 */
function fecharModalVisualizarFatura() {
    const modal = document.getElementById('modalVisualizarFatura');
    if (modal) {
        modal.setAttribute('data-opened', 'false');
    }
}

// Fechar modal de visualização ao clicar no backdrop
document.addEventListener('DOMContentLoaded', function() {
    const modalVisualizar = document.getElementById('modalVisualizarFatura');
    if (modalVisualizar) {
        modalVisualizar.addEventListener('click', function(e) {
            // Se clicou diretamente no backdrop (não no dialog)
            if (e.target === modalVisualizar) {
                fecharModalVisualizarFatura();
            }
        });
    }
});

/**
 * Abre modal de registrar pagamento para a fatura
 */
function marcarComoPaga(id) {
    if (!id) {
        showAlert('danger', 'ID da fatura não fornecido.');
        return;
    }
    
    // Verificar se a fatura está cancelada antes de abrir o modal
    const row = document.querySelector(`tr[data-fatura-id="${id}"]`);
    const status = row?.dataset.status || null;
    
    if (status === 'cancelada') {
        showAlert('warning', 'Não é possível registrar pagamentos para uma fatura cancelada.');
        return;
    }
    
    abrirModalRegistrarPagamento(id);
}

/**
 * Abre modal de registrar pagamento e carrega dados da fatura
 */
async function abrirModalRegistrarPagamento(faturaId) {
    if (!faturaId) {
        showAlert('danger', 'ID da fatura não fornecido.');
        return;
    }
    
    const modal = document.getElementById('modalRegistrarPagamento');
    if (!modal) {
        showAlert('danger', 'Modal de registrar pagamento não encontrado.');
        return;
    }
    
    // Mostrar modal com loading
    modal.setAttribute('data-opened', 'true');
    const form = document.getElementById('formRegistrarPagamento');
    form.querySelectorAll('input, select, textarea, button').forEach(el => el.disabled = true);
    
    try {
        console.log('💰 Carregando dados da fatura para pagamento:', faturaId);
        
        // 1. Buscar dados da fatura
        const apiUrlFatura = getApiFaturasUrl(`id=${faturaId}`);
        const responseFatura = await fetch(apiUrlFatura);
        const responseTextFatura = await responseFatura.text();
        const contentTypeFatura = responseFatura.headers.get('content-type');
        
        if (!contentTypeFatura || !contentTypeFatura.includes('application/json')) {
            throw new Error(`Erro ao carregar fatura: Servidor retornou ${responseFatura.status}`);
        }
        
        const dataFatura = JSON.parse(responseTextFatura);
        if (!responseFatura.ok || !dataFatura.success) {
            throw new Error(dataFatura.error || 'Erro ao carregar dados da fatura');
        }
        
        const fatura = dataFatura.data;
        
        // Validar se a fatura não está cancelada (validação adicional no backend)
        if (fatura.status === 'cancelada') {
            showAlert('warning', 'Não é possível registrar pagamentos para uma fatura cancelada.');
            fecharModalRegistrarPagamento();
            return;
        }
        
        // 2. Buscar pagamentos existentes
        console.log('💰 Buscando pagamentos existentes para fatura:', faturaId);
        const apiUrlPagamentos = getApiPagamentosUrl(`fatura_id=${faturaId}`);
        const responsePagamentos = await fetch(apiUrlPagamentos);
        const responseTextPagamentos = await responsePagamentos.text();
        const contentTypePagamentos = responsePagamentos.headers.get('content-type');
        
        let totalPago = 0;
        if (contentTypePagamentos && contentTypePagamentos.includes('application/json')) {
            try {
                const dataPagamentos = JSON.parse(responseTextPagamentos);
                if (dataPagamentos.success && dataPagamentos.pagamentos) {
                    totalPago = dataPagamentos.pagamentos.reduce((sum, p) => sum + parseFloat(p.valor_pago || 0), 0);
                    console.log('💰 Total já pago:', totalPago);
                }
            } catch (e) {
                console.warn('⚠️ Erro ao processar pagamentos (continuando):', e);
            }
        }
        
        // 3. Calcular saldo em aberto
        const valorTotal = parseFloat(fatura.valor_total || fatura.valor || 0);
        const saldoAberto = Math.max(0, valorTotal - totalPago);
        
        console.log('💰 Resumo financeiro:', {
            valorTotal,
            totalPago,
            saldoAberto
        });
        
        // 4. Preencher formulário
        document.getElementById('pagamento_fatura_id').value = faturaId;
        
        // Aluno
        document.getElementById('pagamento_aluno_nome').textContent = fatura.aluno_nome || 'Aluno não encontrado';
        document.getElementById('pagamento_aluno_cpf').textContent = fatura.cpf || fatura.aluno_cpf ? ` • ${fatura.cpf || fatura.aluno_cpf}` : '';
        
        // Valor da fatura
        const valorFormatado = valorTotal.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.getElementById('pagamento_valor_fatura').value = valorFormatado;
        
        // Saldo em aberto
        const saldoFormatado = saldoAberto.toLocaleString('pt-BR', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
        document.getElementById('pagamento_saldo_aberto').value = saldoFormatado;
        
        // Valor pago (pré-preenchido com saldo, mas permitir edição)
        document.getElementById('pagamento_valor_pago').value = saldoAberto > 0 ? saldoAberto.toFixed(2) : '0.00';
        
        // Data de pagamento (hoje)
        const hoje = new Date().toISOString().split('T')[0];
        document.getElementById('pagamento_data_pagamento').value = hoje;
        
        // Reabilitar formulário
        form.querySelectorAll('input, select, textarea, button').forEach(el => {
            if (el.id !== 'pagamento_valor_fatura' && el.id !== 'pagamento_saldo_aberto' && el.id !== 'pagamento_aluno_info') {
                el.disabled = false;
            }
        });
        
    } catch (error) {
        console.error('❌ Erro ao carregar dados para pagamento:', error);
        showAlert('danger', 'Erro ao carregar dados da fatura: ' + error.message);
        fecharModalRegistrarPagamento();
    }
}

/**
 * Fecha o modal de registrar pagamento
 */
function fecharModalRegistrarPagamento() {
    const modal = document.getElementById('modalRegistrarPagamento');
    if (modal) {
        modal.setAttribute('data-opened', 'false');
        
        // Limpar formulário
        const form = document.getElementById('formRegistrarPagamento');
        if (form) {
            form.reset();
        }
    }
}

// Fechar modal de registrar pagamento ao clicar no backdrop
document.addEventListener('DOMContentLoaded', function() {
    const modalRegistrarPagamento = document.getElementById('modalRegistrarPagamento');
    if (modalRegistrarPagamento) {
        modalRegistrarPagamento.addEventListener('click', function(e) {
            if (e.target === modalRegistrarPagamento) {
                fecharModalRegistrarPagamento();
            }
        });
    }
});

/**
 * Cancela uma fatura (muda status para 'cancelada')
 * Apenas permite cancelar faturas em aberto/vencidas sem pagamentos
 */
async function cancelarFatura(id) {
    if (!id) {
        showAlert('danger', 'ID da fatura não fornecido.');
        return;
    }
    
    try {
        // 1. Obter dados da linha e status atual
        const row = document.querySelector(`tr[data-fatura-id="${id}"]`);
        if (!row) {
            showAlert('danger', 'Fatura não encontrada na tabela.');
            return;
        }
        
        const status = row.dataset.status || null;
        
        // 2. Validar se pode cancelar (apenas aberta ou vencida)
        if (status && ['paga', 'parcial', 'cancelada'].includes(status)) {
            let mensagem = 'Somente faturas em aberto podem ser canceladas.';
            if (status === 'cancelada') {
                mensagem = 'Esta fatura já está cancelada.';
            } else if (status === 'paga') {
                mensagem = 'Não é possível cancelar uma fatura já paga.';
            } else if (status === 'parcial') {
                mensagem = 'Não é possível cancelar uma fatura com pagamentos parciais.';
            }
            showAlert('warning', mensagem);
            return;
        }
        
        // 3. Verificar se há pagamentos via API
        console.log('🔍 Verificando pagamentos da fatura:', id);
        const apiUrlPagamentos = getApiPagamentosUrl(`fatura_id=${id}`);
        const responsePagamentos = await fetch(apiUrlPagamentos);
        const responseTextPagamentos = await responsePagamentos.text();
        const contentTypePagamentos = responsePagamentos.headers.get('content-type');
        
        let temPagamentos = false;
        if (contentTypePagamentos && contentTypePagamentos.includes('application/json')) {
            try {
                const dataPagamentos = JSON.parse(responseTextPagamentos);
                if (dataPagamentos.success && dataPagamentos.pagamentos && Array.isArray(dataPagamentos.pagamentos)) {
                    temPagamentos = dataPagamentos.pagamentos.length > 0;
                    console.log('💰 Pagamentos encontrados:', dataPagamentos.pagamentos.length);
                }
            } catch (e) {
                console.warn('⚠️ Erro ao processar resposta de pagamentos:', e);
            }
        }
        
        if (temPagamentos) {
            showAlert('warning', 'Não é possível cancelar fatura que já possui pagamentos registrados.');
            return;
        }
        
        // 4. Buscar dados da fatura para mostrar no diálogo de confirmação
        console.log('📄 Carregando dados da fatura para confirmação:', id);
        const apiUrlFatura = getApiFaturasUrl(`id=${id}`);
        const responseFatura = await fetch(apiUrlFatura);
        const responseTextFatura = await responseFatura.text();
        const contentTypeFatura = responseFatura.headers.get('content-type');
        
        if (!contentTypeFatura || !contentTypeFatura.includes('application/json')) {
            throw new Error(`Erro ao carregar fatura: Servidor retornou ${responseFatura.status}`);
        }
        
        const dataFatura = JSON.parse(responseTextFatura);
        if (!responseFatura.ok || !dataFatura.success) {
            throw new Error(dataFatura.error || 'Erro ao carregar dados da fatura');
        }
        
        const fatura = dataFatura.data;
        
        // Formatar valores para exibição
        const formatarMoeda = (valor) => {
            return new Intl.NumberFormat('pt-BR', {
                style: 'currency',
                currency: 'BRL'
            }).format(valor || 0);
        };
        
        const formatarData = (data) => {
            if (!data) return '-';
            try {
                const d = new Date(data);
                return d.toLocaleDateString('pt-BR');
            } catch (e) {
                return data;
            }
        };
        
        // 5. Confirmar com o usuário
        const mensagemConfirmacao = `
            Confirma o cancelamento desta fatura?<br><br>
            <strong>Aluno:</strong> ${escapeHtml(fatura.aluno_nome || 'Não informado')}<br>
            <strong>Descrição:</strong> ${escapeHtml(fatura.titulo || 'Não informado')}<br>
            <strong>Vencimento:</strong> ${formatarData(fatura.data_vencimento || fatura.vencimento)}<br>
            <strong>Valor:</strong> ${formatarMoeda(fatura.valor_total || fatura.valor || 0)}<br><br>
            <small class="text-muted">Esta ação não pode ser desfeita.</small>
        `;
        
        if (!confirm(mensagemConfirmacao.replace(/<[^>]*>/g, ''))) {
            return;
        }
        
        // 6. Enviar PUT para marcar como cancelada
        console.log('❌ Cancelando fatura:', id);
        const responseCancel = await fetch(apiUrlFatura, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                status: 'cancelada'
            })
        });
        
        const responseTextCancel = await responseCancel.text();
        const contentTypeCancel = responseCancel.headers.get('content-type');
        
        if (!contentTypeCancel || !contentTypeCancel.includes('application/json')) {
            console.error('❌ Resposta não é JSON. Status:', responseCancel.status);
            console.error('❌ Primeiros 500 caracteres:', responseTextCancel.substring(0, 500));
            throw new Error(`Erro ao cancelar fatura: Servidor retornou ${responseCancel.status}`);
        }
        
        const dataCancel = JSON.parse(responseTextCancel);
        
        if (!responseCancel.ok || !dataCancel.success) {
            throw new Error(dataCancel.error || dataCancel.message || 'Erro ao cancelar fatura');
        }
        
        // 7. Sucesso - atualizar linha na tabela sem recarregar
        console.log('✅ Fatura cancelada com sucesso');
        showAlert('success', 'Fatura cancelada com sucesso.');
        
        // Atualizar dataset da linha
        row.dataset.status = 'cancelada';
        
        // Atualizar badge de status
        const statusCell = row.querySelector('.col-status');
        if (statusCell) {
            statusCell.innerHTML = '<span class="status-badge status-cancelada">Cancelada</span>';
        }
        
        // 8. Atualizar cards de resumo sem recarregar a página
        atualizarResumoFinanceiro();
        
    } catch (error) {
        console.error('❌ Erro ao cancelar fatura:', error);
        showAlert('danger', 'Erro ao cancelar fatura: ' + error.message);
    }
}

/**
 * Atualiza os cards de resumo financeiro (estatísticas) sem recarregar a página
 * Recalcula baseado nas linhas da tabela visíveis
 * 
 * NOTA: Esta função recalcula baseado nas faturas visíveis na tabela atual.
 * Se houver filtros aplicados, os cards refletirão apenas as faturas filtradas.
 * Para totais globais exatos, seria necessário uma chamada à API, mas isso
 * foi simplificado para evitar requisições extras.
 */
function atualizarResumoFinanceiro() {
    try {
        // Contar faturas na tabela visível
        const linhas = document.querySelectorAll('#tabela-faturas tbody tr[data-fatura-id]');
        let totalFaturas = linhas.length;
        let faturasPagas = 0;
        let faturasVencidas = 0;
        let valorAberto = 0;
        
        const hoje = new Date();
        hoje.setHours(0, 0, 0, 0);
        
        linhas.forEach(linha => {
            const status = linha.dataset.status || '';
            const vencimento = linha.dataset.vencimento || '';
            
            // Contar pagas
            if (status === 'paga') {
                faturasPagas++;
            }
            
            // Contar vencidas (aberta ou parcial com vencimento < hoje)
            if ((status === 'aberta' || status === 'vencida') && vencimento) {
                try {
                    const dataVenc = new Date(vencimento);
                    dataVenc.setHours(0, 0, 0, 0);
                    if (dataVenc < hoje) {
                        faturasVencidas++;
                    }
                } catch (e) {
                    // Ignorar erro de parsing de data
                }
            }
            
            // Somar valor em aberto (apenas faturas abertas/vencidas/parciais, não canceladas)
            if (status === 'aberta' || status === 'vencida' || status === 'parcial') {
                const valorCell = linha.querySelector('.col-valor .valor-formatado');
                if (valorCell) {
                    const valorText = valorCell.textContent.replace(/[^\d,.-]/g, '').replace(',', '.');
                    const valor = parseFloat(valorText) || 0;
                    valorAberto += valor;
                }
            }
        });
        
        // Atualizar elementos dos cards
        const totalFaturasEl = document.getElementById('stats-total-faturas');
        const faturasPagasEl = document.getElementById('stats-faturas-pagas');
        const faturasVencidasEl = document.getElementById('stats-faturas-vencidas');
        const valorAbertoEl = document.getElementById('stats-valor-aberto');
        
        if (totalFaturasEl) {
            totalFaturasEl.textContent = totalFaturas;
        }
        if (faturasPagasEl) {
            faturasPagasEl.textContent = faturasPagas;
        }
        if (faturasVencidasEl) {
            faturasVencidasEl.textContent = faturasVencidas;
        }
        if (valorAbertoEl) {
            // Formatar valor em R$ brasileiro
            const valorFormatado = 'R$ ' + valorAberto.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            valorAbertoEl.textContent = valorFormatado;
        }
        
        console.log('✅ Cards de resumo atualizados:', {
            totalFaturas,
            faturasPagas,
            faturasVencidas,
            valorAberto
        });
        
    } catch (error) {
        console.error('❌ Erro ao atualizar resumo financeiro:', error);
        // Não bloquear o fluxo - apenas logar o erro
    }
}

// Log de debug para faturas (apenas em modo desenvolvimento)
// Esta função será chamada automaticamente quando a página carregar
(function() {
    // Habilitar debug por padrão (pode ser desabilitado depois)
    if (typeof window.DEBUG === 'undefined') {
        window.DEBUG = true;
    }
    
    // Aguardar DOM estar pronto
    function logFaturasDebug() {
        if (!window.DEBUG) return;
        
        const linhas = document.querySelectorAll('#tabela-faturas tbody tr[data-fatura-id]');
        if (linhas.length === 0) return;
        
        console.log('📋 Debug de Faturas - Total:', linhas.length);
        
        linhas.forEach((linha) => {
            const faturaId = linha.dataset.faturaId;
            const status = linha.dataset.status;
            const vencimento = linha.dataset.vencimento || '';
            const titulo = linha.dataset.titulo || '';
            const descricaoCurta = linha.dataset.descricaoCurta || '';
            
            console.log('📋 Fatura Debug:', {
                id: faturaId,
                status: status,
                data_vencimento: vencimento,
                titulo: titulo,
                descricao_curta_renderizada: descricaoCurta
            });
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', logFaturasDebug);
    } else {
        setTimeout(logFaturasDebug, 500); // Aguardar tabela ser renderizada
    }
})();

// Log de debug para faturas (apenas em modo desenvolvimento)
// Esta função será chamada automaticamente quando a página carregar
(function() {
    // Habilitar debug por padrão (pode ser desabilitado depois)
    if (typeof window.DEBUG === 'undefined') {
        window.DEBUG = true;
    }
    
    // Aguardar DOM estar pronto
    function logFaturasDebug() {
        if (!window.DEBUG) return;
        
        const linhas = document.querySelectorAll('#tabela-faturas tbody tr[data-fatura-id]');
        if (linhas.length === 0) return;
        
        console.log('📋 Debug de Faturas - Total:', linhas.length);
        
        linhas.forEach((linha) => {
            const faturaId = linha.dataset.faturaId;
            const status = linha.dataset.status;
            const vencimento = linha.dataset.vencimento || '';
            const titulo = linha.dataset.titulo || '';
            const descricaoCurta = linha.dataset.descricaoCurta || '';
            
            console.log('📋 Fatura Debug:', {
                id: faturaId,
                status: status,
                data_vencimento: vencimento,
                titulo: titulo,
                descricao_curta_renderizada: descricaoCurta
            });
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', logFaturasDebug);
    } else {
        setTimeout(logFaturasDebug, 500); // Aguardar tabela ser renderizada
    }
})();

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
