<?php
/**
 * Página de Gestão de Turmas - Template
 * Sistema CFC - Bom Conselho
 */

// Verificar permissões (as variáveis já estão disponíveis do admin/index.php)
if (!$isAdmin && !$isInstrutor) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Incluir dependências específicas
require_once __DIR__ . '/../includes/turma_manager.php';

// Instanciar o gerenciador de turmas
$turmaManager = new TurmaManager();

// Buscar instrutores para o dropdown
try {
    $instrutores = $db->fetchAll("
        SELECT id, nome, email 
        FROM instrutores 
        WHERE ativo = 1 
        ORDER BY nome ASC
    ");
} catch (Exception $e) {
    $instrutores = [];
}

// Buscar estatísticas
try {
    $cfcIdParaStats = $isAdmin ? null : ($user['cfc_id'] ?? 1); // Admin vê estatísticas de todos os CFCs
    $stats = $turmaManager->obterEstatisticas($cfcIdParaStats);
    $estatisticas = $stats['sucesso'] ? $stats['dados'] : [];
} catch (Exception $e) {
    $estatisticas = [];
}

// Processar filtros
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'status' => $_GET['status'] ?? '',
    'instrutor_id' => $_GET['instrutor_id'] ?? '',
    'cfc_id' => $isAdmin ? null : ($user['cfc_id'] ?? 1) // Admin vê todas as turmas
];

// Buscar turmas com filtros
try {
    $resultado = $turmaManager->listarTurmas($filtros);
    $turmas = (is_array($resultado) && isset($resultado['sucesso']) && $resultado['sucesso']) 
              ? $resultado['dados'] 
              : [];
} catch (Exception $e) {
    $turmas = [];
}

// Processar ações
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$turma_id = $_GET['turma_id'] ?? null;

if ($acao === 'excluir' && $turma_id) {
    if ($turmaManager->excluirTurma($turma_id)) {
        echo '<div class="alert alert-success">Turma excluída com sucesso!</div>';
    } else {
        echo '<div class="alert alert-danger">Erro ao excluir turma.</div>';
    }
}

if ($acao === 'criar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $dadosTurma = [
        'nome' => $_POST['nome'] ?? '',
        'instrutor_id' => $_POST['instrutor_id'] ?? '',
        'tipo_aula' => $_POST['tipo_aula'] ?? '',
        'categoria_cnh' => $_POST['categoria_cnh'] ?? null,
        'data_inicio' => $_POST['data_inicio'] ?? '',
        'data_fim' => $_POST['data_fim'] ?? '',
        'status' => $_POST['status'] ?? 'agendado',
        'observacoes' => $_POST['observacoes'] ?? null,
        'cfc_id' => $isAdmin ? 36 : ($user['cfc_id'] ?? 1) // Admin usa CFC 36 por padrão
    ];
    
    $resultado = $turmaManager->criarTurma($dadosTurma);
    
    if ($resultado['sucesso']) {
        echo '<div class="alert alert-success">Turma criada com sucesso!</div>';
        // Redirecionar para evitar reenvio do formulário
        header('Location: ?page=turmas&sucesso=1');
        exit;
    } else {
        echo '<div class="alert alert-danger">Erro ao criar turma: ' . ($resultado['mensagem'] ?? 'Erro desconhecido') . '</div>';
    }
}

if ($acao === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $turmaId = $_POST['turma_id'] ?? null;
    
    if (!$turmaId) {
        echo '<div class="alert alert-danger">ID da turma é obrigatório para edição.</div>';
    } else {
        $dadosTurma = [
            'nome' => $_POST['nome'] ?? '',
            'instrutor_id' => $_POST['instrutor_id'] ?? '',
            'tipo_aula' => $_POST['tipo_aula'] ?? '',
            'categoria_cnh' => $_POST['categoria_cnh'] ?? null,
            'data_inicio' => $_POST['data_inicio'] ?? '',
            'data_fim' => $_POST['data_fim'] ?? '',
            'status' => $_POST['status'] ?? 'ativa',
            'observacoes' => $_POST['observacoes'] ?? null
        ];
        
        $resultado = $turmaManager->atualizarTurma($turmaId, $dadosTurma);
        
        if ($resultado['sucesso']) {
            echo '<div class="alert alert-success">Turma atualizada com sucesso!</div>';
            // Redirecionar para evitar reenvio do formulário
            header('Location: ?page=turmas&sucesso=2');
            exit;
        } else {
            echo '<div class="alert alert-danger">Erro ao atualizar turma: ' . ($resultado['mensagem'] ?? 'Erro desconhecido') . '</div>';
        }
    }
}

// Verificar se houve sucesso na criação ou edição
if (isset($_GET['sucesso'])) {
    if ($_GET['sucesso'] == '1') {
        echo '<div class="alert alert-success">Turma criada com sucesso!</div>';
    } elseif ($_GET['sucesso'] == '2') {
        echo '<div class="alert alert-success">Turma atualizada com sucesso!</div>';
    }
}
?>

<style>
/* CSS para Modal de Visualização */
#modalVisualizarTurma .modal-body {
    max-height: 80vh;
    overflow-y: auto;
    scroll-behavior: smooth;
    padding: 1.5rem;
}

#modalVisualizarTurma .modal-body::-webkit-scrollbar {
    width: 8px;
}

#modalVisualizarTurma .modal-body::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

#modalVisualizarTurma .modal-body::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 4px;
}

#modalVisualizarTurma .modal-body::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Estilos específicos para turmas - Alinhados com identidade visual */
.stats-card {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1rem;
    border: none;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
.stats-card.success {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}
.stats-card.warning {
    background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%);
    color: #212529;
}
.stats-card.info {
    background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);
}

.status-badge {
    padding: 0.25rem 0.5rem;
    border-radius: 0.375rem;
    font-size: 0.75rem;
    font-weight: 500;
}
.status-ativa { background-color: #d4edda; color: #155724; }
.status-inativa { background-color: #f8d7da; color: #721c24; }
.status-pendente { background-color: #fff3cd; color: #856404; }
.status-indefinido { background-color: #e2e3e5; color: #383d41; }

/* CSS RESPONSIVO PARA MOBILE */
@media (max-width: 768px) {
    /* Ocultar tabela no mobile */
    .table-responsive {
        display: none !important;
    }
    
    /* Mostrar cards mobile */
    .mobile-turma-cards {
        display: block !important;
    }
    
    /* Cards de turmas */
    .mobile-turma-card {
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin-bottom: 1rem;
        padding: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .mobile-turma-header {
        display: flex;
        align-items: center;
        margin-bottom: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 1px solid #e9ecef;
    }
    
    .mobile-turma-avatar {
        width: 50px;
        height: 50px;
        background: #007bff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 0.75rem;
        flex-shrink: 0;
    }
    
    .mobile-turma-avatar i {
        color: white;
        font-size: 1.25rem;
    }
    
    .mobile-turma-info {
        flex: 1;
        min-width: 0;
    }
    
    .mobile-turma-title {
        font-weight: 600;
        font-size: 1rem;
        color: #212529;
        margin-bottom: 0.25rem;
    }
    
    .mobile-turma-subtitle {
        font-size: 0.875rem;
        color: #6c757d;
    }
    
    .mobile-turma-body {
        margin-bottom: 0.75rem;
    }
    
    .mobile-turma-field {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.5rem;
        padding: 0.25rem 0;
    }
    
    .mobile-turma-label {
        font-size: 0.875rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .mobile-turma-value {
        font-size: 0.875rem;
        color: #212529;
        font-weight: 500;
    }
    
    .mobile-turma-value .badge {
        font-size: 0.75rem;
        padding: 0.25rem 0.5rem;
    }
    
    .mobile-turma-actions {
        display: flex;
        gap: 0.25rem;
        flex-wrap: nowrap;
        justify-content: center;
        padding-top: 0.75rem;
        border-top: 1px solid #e9ecef;
    }
    
    .mobile-turma-actions .btn {
        width: 36px;
        height: 36px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 6px;
        font-size: 0.875rem;
    }
    
    .mobile-turma-actions .btn i {
        font-size: 0.875rem;
        margin: 0;
    }
}

@media (min-width: 769px) {
    /* Ocultar cards no desktop */
    .mobile-turma-cards {
        display: none !important;
    }
    
    /* Mostrar tabela no desktop */
    .table-responsive {
        display: block !important;
    }
}

/* Correção para overlay do modal */
.modal-backdrop {
    z-index: 1040 !important;
}

.modal {
    z-index: 1050 !important;
}

/* Garantir que o body não mantenha classes de modal */
body.modal-open {
    overflow: hidden;
}

/* Limpar backdrop quando modal é fechado */
body:not(.modal-open) .modal-backdrop {
    display: none !important;
}
</style>

<!-- Header da página -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="fas fa-chalkboard-teacher me-2"></i>Gestão de Turmas</h2>
        <p class="text-muted mb-0">Gerencie as turmas teóricas do sistema</p>
    </div>
    <button class="btn btn-primary" onclick="novaTurma()">
        <i class="fas fa-plus me-1"></i>Nova Turma
    </button>
</div>

<!-- Cards de Estatísticas -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="stats-card">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Total de Turmas</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['total_turmas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-chalkboard-teacher fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card success">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Turmas Ativas</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['turmas_ativas'] ?? 0; ?></h3>
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
                    <h6 class="mb-0">Alunos Matriculados</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['total_matriculas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-users fa-2x"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stats-card info">
            <div class="d-flex justify-content-between">
                <div>
                    <h6 class="mb-0">Próximas Aulas</h6>
                    <h3 class="mb-0"><?php echo $estatisticas['proximas_aulas'] ?? 0; ?></h3>
                </div>
                <div class="align-self-center">
                    <i class="fas fa-calendar-alt fa-2x"></i>
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
            <input type="hidden" name="page" value="turmas">
            <div class="col-md-3">
                <label for="busca" class="form-label">Buscar</label>
                <input type="text" class="form-control" id="busca" name="busca" 
                       placeholder="Nome da turma..." value="<?php echo htmlspecialchars($filtros['busca']); ?>">
            </div>
            <div class="col-md-2">
                <label for="status" class="form-label">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">Todos</option>
                    <option value="ativa" <?php echo $filtros['status'] === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                    <option value="inativa" <?php echo $filtros['status'] === 'inativa' ? 'selected' : ''; ?>>Inativa</option>
                    <option value="pendente" <?php echo $filtros['status'] === 'pendente' ? 'selected' : ''; ?>>Pendente</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="instrutor_id" class="form-label">Instrutor</label>
                <select class="form-select" id="instrutor_id" name="instrutor_id">
                    <option value="">Todos</option>
                    <?php foreach ($instrutores as $instrutor): ?>
                    <option value="<?php echo $instrutor['id']; ?>" <?php echo $filtros['instrutor_id'] == $instrutor['id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($instrutor['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="data_inicio" class="form-label">Data Início</label>
                <input type="date" class="form-control" id="data_inicio" name="data_inicio" value="<?php echo $filtros['data_inicio']; ?>">
            </div>
            <div class="col-md-2">
                <label for="data_fim" class="form-label">Data Fim</label>
                <input type="date" class="form-control" id="data_fim" name="data_fim" value="<?php echo $filtros['data_fim']; ?>">
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i>
                    </button>
                    <a href="?page=turmas" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Turmas -->
<div class="card">
    <div class="card-header">
        <h5 class="mb-0"><i class="fas fa-list me-2"></i>Lista de Turmas</h5>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome</th>
                        <th>Instrutor</th>
                        <th>Data Início</th>
                        <th>Data Fim</th>
                        <th>Alunos</th>
                        <th>Status</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turmas)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>Nenhuma turma encontrada</p>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($turmas as $turma): ?>
                    <?php if (is_array($turma)): ?>
                    <tr>
                        <td><?php echo $turma['id'] ?? 'N/A'; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($turma['nome'] ?? 'Nome não definido'); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($turma['descricao'] ?? ''); ?></small>
                        </td>
                        <td>
                            <?php if (!empty($turma['instrutor_nome'])): ?>
                            <strong><?php echo htmlspecialchars($turma['instrutor_nome']); ?></strong><br>
                            <small class="text-muted"><?php echo htmlspecialchars($turma['instrutor_email'] ?? ''); ?></small>
                            <?php else: ?>
                            <span class="text-muted">Não definido</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($turma['data_inicio'])): ?>
                                <?php echo date('d/m/Y', strtotime($turma['data_inicio'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Não definida</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($turma['data_fim'])): ?>
                                <?php echo date('d/m/Y', strtotime($turma['data_fim'])); ?>
                            <?php else: ?>
                                <span class="text-muted">Não definida</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="badge bg-info">
                                <?php echo $turma['total_alunos'] ?? 0; ?> alunos
                            </span>
                        </td>
                        <td>
                            <span class="status-badge status-<?php echo $turma['status'] ?? 'indefinido'; ?>">
                                <?php echo ucfirst($turma['status'] ?? 'Indefinido'); ?>
                            </span>
                        </td>
                        <td>
                            <div class="btn-group btn-group-sm">
                                <button class="btn btn-outline-primary" onclick="visualizarTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Visualizar">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="btn btn-outline-success" onclick="editarTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="btn btn-outline-info" onclick="gerenciarAlunos(<?php echo $turma['id'] ?? 0; ?>)" title="Gerenciar Alunos">
                                    <i class="fas fa-users"></i>
                                </button>
                                <button class="btn btn-outline-warning" onclick="calendarioTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Calendário">
                                    <i class="fas fa-calendar"></i>
                                </button>
                                <button class="btn btn-outline-danger" onclick="excluirTurma(<?php echo $turma['id'] ?? 0; ?>)" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Cards Mobile para Turmas -->
<div class="mobile-turma-cards" id="mobileTurmaCards">
    <?php if (!empty($turmas)): ?>
        <?php foreach ($turmas as $turma): ?>
        <div class="mobile-turma-card">
            <div class="mobile-turma-header">
                <div class="mobile-turma-avatar">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div class="mobile-turma-info">
                    <div class="mobile-turma-title">
                        <?php echo htmlspecialchars($turma['nome']); ?>
                    </div>
                    <div class="mobile-turma-subtitle">
                        ID: <?php echo $turma['id']; ?> • <?php echo htmlspecialchars($turma['categoria_cnh'] ?? 'N/A'); ?>
                    </div>
                </div>
            </div>
            
            <div class="mobile-turma-body">
                <div class="mobile-turma-field">
                    <span class="mobile-turma-label">Instrutor:</span>
                    <span class="mobile-turma-value">
                        <?php echo htmlspecialchars($turma['instrutor_nome'] ?? 'N/A'); ?>
                    </span>
                </div>
                
                <div class="mobile-turma-field">
                    <span class="mobile-turma-label">Data Início:</span>
                    <span class="mobile-turma-value">
                        <?php echo $turma['data_inicio'] ? date('d/m/Y', strtotime($turma['data_inicio'])) : 'N/A'; ?>
                    </span>
                </div>
                
                <div class="mobile-turma-field">
                    <span class="mobile-turma-label">Data Fim:</span>
                    <span class="mobile-turma-value">
                        <?php echo $turma['data_fim'] ? date('d/m/Y', strtotime($turma['data_fim'])) : 'N/A'; ?>
                    </span>
                </div>
                
                <div class="mobile-turma-field">
                    <span class="mobile-turma-label">Alunos:</span>
                    <span class="mobile-turma-value">
                        <span class="badge bg-info"><?php echo $turma['total_alunos'] ?? 0; ?></span>
                    </span>
                </div>
                
                <div class="mobile-turma-field">
                    <span class="mobile-turma-label">Status:</span>
                    <span class="mobile-turma-value">
                        <?php
                        $statusClass = [
                            'ativa' => 'success',
                            'inativa' => 'danger',
                            'pendente' => 'warning'
                        ];
                        $statusText = [
                            'ativa' => 'Ativa',
                            'inativa' => 'Inativa',
                            'pendente' => 'Pendente'
                        ];
                        ?>
                        <span class="badge bg-<?php echo $statusClass[$turma['status']] ?? 'secondary'; ?>">
                            <?php echo $statusText[$turma['status']] ?? ucfirst($turma['status']); ?>
                        </span>
                    </span>
                </div>
            </div>
            
            <div class="mobile-turma-actions">
                <button type="button" class="btn btn-sm btn-primary" 
                        onclick="editarTurma(<?php echo $turma['id']; ?>)" 
                        title="Editar turma">
                    <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-info" 
                        onclick="visualizarTurma(<?php echo $turma['id']; ?>)" 
                        title="Ver detalhes da turma">
                    <i class="fas fa-eye"></i>
                </button>
                <button type="button" class="btn btn-sm btn-warning" 
                        onclick="gerenciarAlunos(<?php echo $turma['id']; ?>)" 
                        title="Gerenciar alunos da turma">
                    <i class="fas fa-users"></i>
                </button>
                <?php if ($turma['status'] === 'ativa'): ?>
                <button type="button" class="btn btn-sm btn-secondary" 
                        onclick="desativarTurma(<?php echo $turma['id']; ?>)" 
                        title="Desativar turma">
                    <i class="fas fa-ban"></i>
                </button>
                <?php else: ?>
                <button type="button" class="btn btn-sm btn-success" 
                        onclick="ativarTurma(<?php echo $turma['id']; ?>)" 
                        title="Ativar turma">
                    <i class="fas fa-check"></i>
                </button>
                <?php endif; ?>
                <button type="button" class="btn btn-sm btn-danger" 
                        onclick="excluirTurma(<?php echo $turma['id']; ?>)" 
                        title="Excluir turma">
                    <i class="fas fa-trash"></i>
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="mobile-turma-card text-center">
            <div class="mobile-turma-header">
                <div class="mobile-turma-avatar">
                    <i class="fas fa-inbox"></i>
                </div>
                <div class="mobile-turma-info">
                    <div class="mobile-turma-title">Nenhuma turma cadastrada</div>
                    <div class="mobile-turma-subtitle">Crie a primeira turma para começar</div>
                </div>
            </div>
            <div class="mobile-turma-actions">
                <button class="btn btn-primary" onclick="novaTurma()">
                    <i class="fas fa-plus me-1"></i>Nova Turma
                </button>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Modal Nova Turma -->
<div class="modal fade" id="modalNovaTurma" tabindex="-1" aria-labelledby="modalNovaTurmaLabel" aria-hidden="true" data-bs-backdrop="true" data-bs-keyboard="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaTurmaLabel">
                    <i class="fas fa-plus me-2"></i>Nova Turma
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formNovaTurma" method="POST" action="?page=turmas">
                <input type="hidden" name="acao" value="criar">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="nome" class="form-label">Nome da Turma *</label>
                                <input type="text" class="form-control" id="nome" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="instrutor_id" class="form-label">Instrutor *</label>
                                <select class="form-select" id="instrutor_id" name="instrutor_id" required>
                                    <option value="">Selecione um instrutor</option>
                                    <?php foreach ($instrutores as $instrutor): ?>
                                    <option value="<?php echo $instrutor['id']; ?>">
                                        <?php echo htmlspecialchars($instrutor['nome']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="tipo_aula" class="form-label">Tipo de Aula *</label>
                                <select class="form-select" id="tipo_aula" name="tipo_aula" required onchange="atualizarPreviewDisciplinas()">
                                    <option value="">Selecione o tipo</option>
                                    <option value="teorica">Teórica</option>
                                    <option value="pratica">Prática</option>
                                    <option value="mista">Mista</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="categoria_cnh" class="form-label">Categoria CNH</label>
                                <select class="form-select" id="categoria_cnh" name="categoria_cnh" onchange="atualizarPreviewDisciplinas()">
                                    <option value="">Selecione a categoria</option>
                                    <option value="A">A - Motocicleta</option>
                                    <option value="B">B - Automóvel</option>
                                    <option value="C">C - Caminhão</option>
                                    <option value="D">D - Ônibus</option>
                                    <option value="E">E - Carreta</option>
                                    <option value="AB">AB - A + B</option>
                                    <option value="AC">AC - A + C</option>
                                    <option value="AD">AD - A + D</option>
                                    <option value="AE">AE - A + E</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="status" class="form-label">Status *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="agendado">Agendado</option>
                                    <option value="ativa">Ativa</option>
                                    <option value="concluida">Concluída</option>
                                    <option value="cancelada">Cancelada</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview das Disciplinas (apenas para aulas teóricas) -->
                    <div id="preview_disciplinas" class="mb-3" style="display: none;">
                        <div class="card border-primary">
                            <div class="card-header bg-primary text-white">
                                <h6 class="mb-0">
                                    <i class="fas fa-graduation-cap me-2"></i>
                                    Preview das Disciplinas Teóricas
                                </h6>
                            </div>
                            <div class="card-body">
                                <div id="disciplinas_preview_content">
                                    <div class="text-center text-muted">
                                        <i class="fas fa-spinner fa-spin me-2"></i>
                                        Carregando disciplinas...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_inicio" class="form-label">Data de Início *</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="data_fim" class="form-label">Data de Fim *</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoes" class="form-label">Observações</label>
                        <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Observações sobre a turma..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Turma
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Visualizar Turma -->
<div class="modal fade" id="modalVisualizarTurma" tabindex="-1" aria-labelledby="modalVisualizarTurmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalVisualizarTurmaLabel">
                    <i class="fas fa-eye me-2"></i>Detalhes da Turma
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="conteudoVisualizacaoTurma">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando dados da turma...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Editar Turma -->
<div class="modal fade" id="modalEditarTurma" tabindex="-1" aria-labelledby="modalEditarTurmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalEditarTurmaLabel">
                    <i class="fas fa-edit me-2"></i>Editar Turma
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formEditarTurma" method="POST" action="?page=turmas">
                <input type="hidden" name="acao" value="editar">
                <input type="hidden" name="turma_id" id="editar_turma_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="editar_nome" class="form-label">Nome da Turma *</label>
                            <input type="text" class="form-control" id="editar_nome" name="nome" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editar_instrutor_id" class="form-label">Instrutor *</label>
                            <select class="form-select" id="editar_instrutor_id" name="instrutor_id" required>
                                <option value="">Selecione um instrutor</option>
                                <?php foreach ($instrutores as $instrutor): ?>
                                <option value="<?php echo $instrutor['id']; ?>">
                                    <?php echo htmlspecialchars($instrutor['nome']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editar_tipo_aula" class="form-label">Tipo de Aula *</label>
                            <select class="form-select" id="editar_tipo_aula" name="tipo_aula" required>
                                <option value="">Selecione o tipo</option>
                                <option value="teorica">Teórica</option>
                                <option value="pratica">Prática</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editar_categoria_cnh" class="form-label">Categoria CNH</label>
                            <select class="form-select" id="editar_categoria_cnh" name="categoria_cnh">
                                <option value="">Selecione a categoria</option>
                                <option value="A">A - Motocicleta</option>
                                <option value="B">B - Automóvel</option>
                                <option value="C">C - Caminhão</option>
                                <option value="D">D - Ônibus</option>
                                <option value="E">E - Carreta</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="editar_data_inicio" class="form-label">Data de Início *</label>
                            <input type="date" class="form-control" id="editar_data_inicio" name="data_inicio" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editar_data_fim" class="form-label">Data de Fim *</label>
                            <input type="date" class="form-control" id="editar_data_fim" name="data_fim" required>
                        </div>
                        <div class="col-md-6">
                            <label for="editar_status" class="form-label">Status *</label>
                            <select class="form-select" id="editar_status" name="status" required>
                                <option value="ativa">Ativa</option>
                                <option value="inativa">Inativa</option>
                                <option value="pendente">Pendente</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="editar_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="editar_observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Alterações
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Calendário da Turma -->
<div class="modal fade" id="modalCalendarioTurma" tabindex="-1" aria-labelledby="modalCalendarioTurmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCalendarioTurmaLabel">
                    <i class="fas fa-calendar-alt me-2"></i>Calendário de Aulas
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="conteudoCalendarioTurma">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando calendário...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Fechar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function novaTurma() {
    // Abrir modal para nova turma
    const modalElement = document.getElementById('modalNovaTurma');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    
    // Limpar formulário ao abrir
    document.getElementById('formNovaTurma').reset();
    
    // Mostrar modal
    modal.show();
    
    // Garantir que o backdrop seja removido quando o modal for fechado
    modalElement.addEventListener('hidden.bs.modal', function () {
        // Remover qualquer classe de backdrop que possa ter ficado
        document.body.classList.remove('modal-open');
        const backdrops = document.querySelectorAll('.modal-backdrop');
        backdrops.forEach(backdrop => backdrop.remove());
    });
}

// Validação do formulário
document.getElementById('formNovaTurma').addEventListener('submit', function(e) {
    const dataInicio = document.getElementById('data_inicio').value;
    const dataFim = document.getElementById('data_fim').value;
    
    if (dataInicio && dataFim && new Date(dataInicio) >= new Date(dataFim)) {
        e.preventDefault();
        alert('A data de fim deve ser posterior à data de início.');
        return false;
    }
    
    // Mostrar loading no botão
    const btnSubmit = this.querySelector('button[type="submit"]');
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSubmit.disabled = true;
});

// Função global para limpar backdrop
function limparBackdrop() {
    document.body.classList.remove('modal-open');
    const backdrops = document.querySelectorAll('.modal-backdrop');
    backdrops.forEach(backdrop => backdrop.remove());
    
    // Forçar reflow para garantir limpeza
    document.body.style.overflow = '';
}

// Adicionar event listeners para todos os botões de fechar
document.addEventListener('DOMContentLoaded', function() {
    // Botões de fechar do modal
    const closeButtons = document.querySelectorAll('[data-bs-dismiss="modal"]');
    closeButtons.forEach(button => {
        button.addEventListener('click', limparBackdrop);
    });
    
    // Clicar fora do modal também deve limpar
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('modal-backdrop')) {
            limparBackdrop();
        }
    });
    
    // Tecla ESC também deve limpar
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            limparBackdrop();
        }
    });
});

function visualizarTurma(id) {
    // Carregar dados da turma e abrir modal de visualização
    fetch(`api/turmas.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.dados) {
                preencherModalVisualizacao(data.dados);
                abrirModalVisualizacao();
            } else {
                alert('Erro ao carregar dados da turma: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            alert('Erro ao carregar turma: ' + error.message);
        });
}

function editarTurma(id) {
    // Carregar dados da turma e abrir modal de edição
    fetch(`api/turmas.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.dados) {
                preencherModalEdicao(data.dados);
                abrirModalEdicao();
            } else {
                alert('Erro ao carregar dados da turma: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            alert('Erro ao carregar turma: ' + error.message);
        });
}

function gerenciarAlunos(id) {
    // Redirecionar para página de gerenciamento de alunos da turma
    window.location.href = `?page=turmas-alunos&turma_id=${id}`;
}

function calendarioTurma(id) {
    // Carregar dados da turma e abrir modal de calendário
    fetch(`api/turmas.php?id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.sucesso && data.dados) {
                preencherModalCalendario(data.dados);
                abrirModalCalendario();
            } else {
                alert('Erro ao carregar dados da turma: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            alert('Erro ao carregar turma: ' + error.message);
        });
}

function excluirTurma(id) {
    if (confirm('Deseja realmente excluir esta turma?')) {
        if (confirm('Esta ação não pode ser desfeita. Continuar?')) {
            window.location.href = `?page=turmas&acao=excluir&turma_id=${id}`;
        }
    }
}

function ativarTurma(id) {
    if (confirm('Deseja ativar esta turma?')) {
        // Implementar ativação da turma
        fetch(`api/turmas.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                acao: 'ativar',
                turma_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('Turma ativada com sucesso!');
                location.reload();
            } else {
                alert('Erro ao ativar turma: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            alert('Erro ao ativar turma: ' + error.message);
        });
    }
}

function desativarTurma(id) {
    if (confirm('Deseja desativar esta turma?')) {
        // Implementar desativação da turma
        fetch(`api/turmas.php`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                acao: 'desativar',
                turma_id: id
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                alert('Turma desativada com sucesso!');
                location.reload();
            } else {
                alert('Erro ao desativar turma: ' + (data.mensagem || 'Erro desconhecido'));
            }
        })
        .catch(error => {
            alert('Erro ao desativar turma: ' + error.message);
        });
    }
}

// Funções para gerenciar modais
function abrirModalVisualizacao() {
    const modalElement = document.getElementById('modalVisualizarTurma');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    modal.show();
}

function abrirModalEdicao() {
    const modalElement = document.getElementById('modalEditarTurma');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    modal.show();
}

function abrirModalCalendario() {
    const modalElement = document.getElementById('modalCalendarioTurma');
    const modal = new bootstrap.Modal(modalElement, {
        backdrop: true,
        keyboard: true,
        focus: true
    });
    modal.show();
}

function preencherModalVisualizacao(turma) {
    const conteudo = document.getElementById('conteudoVisualizacaoTurma');
    
    // Calcular progresso da turma
    const dataInicio = turma.data_inicio ? new Date(turma.data_inicio) : null;
    const dataFim = turma.data_fim ? new Date(turma.data_fim) : null;
    const hoje = new Date();
    
    let progresso = 0;
    let statusProgresso = 'Não iniciada';
    let corProgresso = 'secondary';
    
    if (dataInicio && dataFim) {
        const totalDias = Math.ceil((dataFim - dataInicio) / (1000 * 60 * 60 * 24));
        const diasDecorridos = Math.ceil((hoje - dataInicio) / (1000 * 60 * 60 * 24));
        
        if (hoje < dataInicio) {
            statusProgresso = 'Aguardando início';
            corProgresso = 'warning';
        } else if (hoje > dataFim) {
            progresso = 100;
            statusProgresso = 'Concluída';
            corProgresso = 'success';
        } else {
            progresso = Math.min(100, Math.max(0, (diasDecorridos / totalDias) * 100));
            statusProgresso = 'Em andamento';
            corProgresso = 'primary';
        }
    }
    
    conteudo.innerHTML = `
        <div class="row g-4">
            <!-- Cabeçalho da Turma -->
            <div class="col-12">
                <div class="card border-0 bg-light">
                    <div class="card-body text-dark text-center py-4">
                        <h4 class="mb-2 text-dark"><i class="fas fa-chalkboard-teacher me-2 text-secondary"></i>${turma.nome || 'N/A'}</h4>
                        <p class="mb-0 text-muted">Turma #${turma.id || 'N/A'} • ${turma.categoria_cnh || 'N/A'}</p>
                    </div>
                </div>
            </div>
            
            <!-- Progresso da Turma -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 text-dark"><i class="fas fa-chart-line me-2 text-secondary"></i>Progresso da Turma</h6>
                    </div>
                    <div class="card-body py-3">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="fw-bold">${statusProgresso}</span>
                            <span class="badge bg-${corProgresso} fs-6">${Math.round(progresso)}%</span>
                        </div>
                        <div class="progress mb-3" style="height: 10px;">
                            <div class="progress-bar bg-${corProgresso}" role="progressbar" 
                                 style="width: ${progresso}%" aria-valuenow="${progresso}" 
                                 aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="row text-center">
                            <div class="col-4">
                                <small class="text-muted d-block">Início</small>
                                <strong>${turma.data_inicio ? new Date(turma.data_inicio).toLocaleDateString('pt-BR') : 'N/A'}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Hoje</small>
                                <strong>${hoje.toLocaleDateString('pt-BR')}</strong>
                            </div>
                            <div class="col-4">
                                <small class="text-muted d-block">Fim</small>
                                <strong>${turma.data_fim ? new Date(turma.data_fim).toLocaleDateString('pt-BR') : 'N/A'}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Instrutor Responsável -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 text-dark"><i class="fas fa-user-tie me-2 text-secondary"></i>Instrutor Responsável</h6>
                    </div>
                    <div class="card-body text-center py-4">
                        <div class="mb-3">
                            <div class="avatar-lg mx-auto mb-3" style="width: 70px; height: 70px; background: #17a2b8; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                <i class="fas fa-user fa-2x text-white"></i>
                            </div>
                        </div>
                        <h5 class="mb-1">${turma.instrutor_nome || 'N/A'}</h5>
                        <p class="text-muted mb-2 small">${turma.instrutor_email || 'N/A'}</p>
                        <span class="badge bg-info">Instrutor Certificado</span>
                    </div>
                </div>
            </div>
            
            <!-- Estatísticas da Turma -->
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 text-dark"><i class="fas fa-chart-bar me-2 text-secondary"></i>Estatísticas</h6>
                    </div>
                    <div class="card-body p-3">
                        <div class="row g-2">
                            <div class="col-6 text-center">
                                <div class="border rounded p-2 h-100 d-flex flex-column justify-content-center">
                                    <i class="fas fa-users fa-2x text-primary mb-1"></i>
                                    <h5 class="mb-0">${turma.total_alunos || 0}</h5>
                                    <small class="text-muted">Alunos</small>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="border rounded p-2 h-100 d-flex flex-column justify-content-center">
                                    <i class="fas fa-book fa-2x text-warning mb-1"></i>
                                    <h5 class="mb-0">${turma.aulas ? turma.aulas.length : 0}</h5>
                                    <small class="text-muted">Aulas</small>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="border rounded p-2 h-100 d-flex flex-column justify-content-center">
                                    <i class="fas fa-clock fa-2x text-info mb-1"></i>
                                    <h5 class="mb-0">${turma.tipo_aula === 'teorica' ? '45h' : '20h'}</h5>
                                    <small class="text-muted">Carga</small>
                                </div>
                            </div>
                            <div class="col-6 text-center">
                                <div class="border rounded p-2 h-100 d-flex flex-column justify-content-center">
                                    <i class="fas fa-graduation-cap fa-2x text-success mb-1"></i>
                                    <h5 class="mb-0">${turma.categoria_cnh || 'N/A'}</h5>
                                    <small class="text-muted">CNH</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Alunos da Turma -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 text-dark"><i class="fas fa-users me-2 text-secondary"></i>Alunos Matriculados (${turma.total_alunos || 0})</h6>
                    </div>
                    <div class="card-body">
                        ${turma.alunos && turma.alunos.length > 0 ? `
                            <div class="row g-2">
                                ${turma.alunos.slice(0, 6).map(aluno => `
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center p-2 border rounded">
                                            <div class="avatar-sm me-3" style="width: 40px; height: 40px; background: #ffc107; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                                <i class="fas fa-user text-dark"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">${aluno.aluno_nome || 'N/A'}</div>
                                                <small class="text-muted">${aluno.aluno_email || 'N/A'}</small>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            ${turma.alunos.length > 6 ? `
                                <div class="text-center mt-3">
                                    <button class="btn btn-outline-warning btn-sm" onclick="gerenciarAlunos(${turma.id})">
                                        <i class="fas fa-eye me-1"></i>Ver todos os ${turma.alunos.length} alunos
                                    </button>
                                </div>
                            ` : ''}
                        ` : `
                            <div class="text-center py-4">
                                <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Nenhum aluno matriculado</h5>
                                <p class="text-muted">Esta turma ainda não possui alunos matriculados.</p>
                                <button class="btn btn-warning" onclick="gerenciarAlunos(${turma.id})">
                                    <i class="fas fa-user-plus me-1"></i>Matricular Alunos
                                </button>
                            </div>
                        `}
                    </div>
                </div>
            </div>
            
            <!-- Cronograma de Aulas -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 text-dark"><i class="fas fa-calendar-alt me-2 text-secondary"></i>Cronograma de Aulas</h6>
                    </div>
                    <div class="card-body">
                        ${turma.aulas && turma.aulas.length > 0 ? `
                            <div class="row g-2">
                                ${turma.aulas.slice(0, 4).map(aula => `
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center p-3 border rounded">
                                            <div class="me-3">
                                                <i class="fas fa-book fa-2x text-primary"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-bold">${aula.titulo || 'Aula ' + aula.ordem}</div>
                                                <small class="text-muted">${aula.descricao || 'Conteúdo da aula'}</small>
                                            </div>
                                            <div class="text-end">
                                                <span class="badge bg-primary">${aula.duracao || '2h'}</span>
                                            </div>
                                        </div>
                                    </div>
                                `).join('')}
                            </div>
                            ${turma.aulas.length > 4 ? `
                                <div class="text-center mt-3">
                                    <button class="btn btn-outline-primary btn-sm" onclick="calendarioTurma(${turma.id})">
                                        <i class="fas fa-calendar me-1"></i>Ver cronograma completo
                                    </button>
                                </div>
                            ` : ''}
                        ` : `
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Cronograma não definido</h5>
                                <p class="text-muted">Esta turma ainda não possui aulas programadas.</p>
                                <button class="btn btn-primary" onclick="calendarioTurma(${turma.id})">
                                    <i class="fas fa-calendar-plus me-1"></i>Criar Cronograma
                                </button>
                            </div>
                        `}
                    </div>
                </div>
            </div>
            
            <!-- Observações -->
            ${turma.observacoes ? `
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-light border-bottom">
                        <h6 class="mb-0 text-dark"><i class="fas fa-sticky-note me-2 text-secondary"></i>Observações</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0">${turma.observacoes}</p>
                    </div>
                </div>
            </div>
            ` : ''}
        </div>
    `;
}

function preencherModalEdicao(turma) {
    document.getElementById('editar_turma_id').value = turma.id;
    document.getElementById('editar_nome').value = turma.nome || '';
    document.getElementById('editar_instrutor_id').value = turma.instrutor_id || '';
    document.getElementById('editar_tipo_aula').value = turma.tipo_aula || '';
    document.getElementById('editar_categoria_cnh').value = turma.categoria_cnh || '';
    document.getElementById('editar_data_inicio').value = turma.data_inicio || '';
    document.getElementById('editar_data_fim').value = turma.data_fim || '';
    document.getElementById('editar_status').value = turma.status || 'ativa';
    document.getElementById('editar_observacoes').value = turma.observacoes || '';
}

function preencherModalCalendario(turma) {
    const conteudo = document.getElementById('conteudoCalendarioTurma');
    
    conteudo.innerHTML = `
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Calendário de Aulas - ${turma.nome}</h6>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Funcionalidade em desenvolvimento:</strong> O calendário de aulas será implementado em breve.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-calendar-plus fa-3x text-primary mb-3"></i>
                                        <h5>Agendar Aulas</h5>
                                        <p class="text-muted">Agende aulas teóricas e práticas para esta turma</p>
                                        <button class="btn btn-primary" disabled>
                                            <i class="fas fa-plus me-1"></i>Em Breve
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <i class="fas fa-list fa-3x text-success mb-3"></i>
                                        <h5>Listar Aulas</h5>
                                        <p class="text-muted">Visualize todas as aulas agendadas</p>
                                        <button class="btn btn-success" disabled>
                                            <i class="fas fa-list me-1"></i>Em Breve
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
}

// Validação do formulário de edição
document.getElementById('formEditarTurma').addEventListener('submit', function(e) {
    const dataInicio = document.getElementById('editar_data_inicio').value;
    const dataFim = document.getElementById('editar_data_fim').value;
    
    if (dataInicio && dataFim && new Date(dataInicio) >= new Date(dataFim)) {
        e.preventDefault();
        alert('A data de fim deve ser posterior à data de início.');
        return false;
    }
    
    // Mostrar loading no botão
    const btnSubmit = this.querySelector('button[type="submit"]');
    btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Salvando...';
    btnSubmit.disabled = true;
});

// Função para atualizar preview das disciplinas
function atualizarPreviewDisciplinas() {
    const tipoAula = document.getElementById('tipo_aula').value;
    const categoriaCNH = document.getElementById('categoria_cnh').value;
    const previewDiv = document.getElementById('preview_disciplinas');
    const contentDiv = document.getElementById('disciplinas_preview_content');
    
    if (tipoAula === 'teorica' && categoriaCNH) {
        previewDiv.style.display = 'block';
        contentDiv.innerHTML = `
            <div class="text-center text-muted">
                <i class="fas fa-spinner fa-spin me-2"></i>
                Carregando disciplinas para categoria ${categoriaCNH}...
            </div>
        `;
        
        // Buscar preview das disciplinas
        fetch(`api/turma-grade-generator.php?turma_id=0&action=preview&categoria=${categoriaCNH}`)
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    renderizarPreviewDisciplinas(data.data.disciplinas);
                } else {
                    contentDiv.innerHTML = `
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${data.message}
                        </div>
                    `;
                }
            })
            .catch(error => {
                contentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        Erro ao carregar disciplinas: ${error.message}
                    </div>
                `;
            });
    } else {
        previewDiv.style.display = 'none';
    }
}

// Função para renderizar o preview das disciplinas
function renderizarPreviewDisciplinas(disciplinas) {
    const contentDiv = document.getElementById('disciplinas_preview_content');
    
    if (disciplinas.length === 0) {
        contentDiv.innerHTML = `
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Nenhuma disciplina configurada para esta categoria.
            </div>
        `;
        return;
    }
    
    const totalAulas = disciplinas.reduce((total, disc) => total + disc.aulas, 0);
    
    let html = `
        <div class="row mb-3">
            <div class="col-12">
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Total de aulas:</strong> ${totalAulas} aulas teóricas
                </div>
            </div>
        </div>
        <div class="row">
    `;
    
    disciplinas.forEach(disciplina => {
        const corClasses = {
            'primary': 'bg-primary',
            'danger': 'bg-danger', 
            'success': 'bg-success',
            'warning': 'bg-warning',
            'info': 'bg-info'
        };
        
        html += `
            <div class="col-md-6 col-lg-4 mb-2">
                <div class="card border-${disciplina.cor} h-100">
                    <div class="card-body p-2">
                        <div class="d-flex align-items-center">
                            <i class="${disciplina.icone} text-${disciplina.cor} me-2" style="font-size: 1.2em;"></i>
                            <div class="flex-grow-1">
                                <h6 class="card-title mb-0" style="font-size: 0.9em;">${disciplina.nome}</h6>
                            </div>
                            <span class="badge ${corClasses[disciplina.cor]} text-white" style="font-size: 0.8em;">
                                ${disciplina.aulas} aulas
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    });
    
    html += `
        </div>
        <div class="mt-3">
            <small class="text-muted">
                <i class="fas fa-lightbulb me-1"></i>
                A grade será gerada automaticamente com base nesta configuração.
            </small>
        </div>
    `;
    
    contentDiv.innerHTML = html;
}
</script>
