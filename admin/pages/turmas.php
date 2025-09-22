<?php
/**
 * Página de Gestão de Turmas
 * Baseada na análise do sistema eCondutor
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../includes/turma_manager.php';

// Obter dados do usuário logado e suas permissões
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;

// Instanciar o gerenciador de turmas
$turmaManager = new TurmaManager();

// Buscar instrutores para o dropdown
$db = Database::getInstance();
$instrutores = $db->fetchAll("
    SELECT id, nome, email 
    FROM instrutores 
    WHERE ativo = 1 
    ORDER BY nome ASC
");

// Buscar estatísticas
$stats = $turmaManager->obterEstatisticas($user['cfc_id'] ?? 1);
$estatisticas = $stats['sucesso'] ? $stats['dados'] : [];

// Processar filtros
$filtros = [
    'busca' => $_GET['busca'] ?? '',
    'data_inicio' => $_GET['data_inicio'] ?? '',
    'data_fim' => $_GET['data_fim'] ?? '',
    'status' => $_GET['status'] ?? '',
    'tipo_aula' => $_GET['tipo_aula'] ?? '',
    'cfc_id' => $user['cfc_id'] ?? 1,
    'limite' => (int)($_GET['limite'] ?? 10),
    'pagina' => (int)($_GET['pagina'] ?? 0)
];

$resultado = $turmaManager->listarTurmas($filtros);
$turmas = $resultado['sucesso'] ? $resultado['dados'] : [];
$totalTurmas = $resultado['sucesso'] ? $resultado['total'] : 0;

// Calcular paginação
$totalPaginas = ceil($totalTurmas / $filtros['limite']);
$paginaAtual = $filtros['pagina'] + 1;
?>

<!-- CSS específico para a página de turmas -->
<style>
    .page-header {
        background: linear-gradient(135deg, #00A651 0%, #007A3D 100%);
        color: white;
        padding: 2rem 0;
        margin-bottom: 2rem;
        border-radius: 10px;
    }
    
    .stats-card {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
        border-left: 4px solid #00A651;
    }
    
    .filters-section {
        background: white;
        border-radius: 10px;
        padding: 1.5rem;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 2rem;
    }
    
    .table-container {
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        overflow: hidden;
    }
    
    .btn-primary {
        background: linear-gradient(135deg, #00A651 0%, #007A3D 100%);
        border: none;
        border-radius: 8px;
        padding: 0.75rem 1.5rem;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    
    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,166,81,0.3);
    }
    
    .modal-content {
        border-radius: 15px;
        border: none;
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    
    .modal-header {
        background: linear-gradient(135deg, #00A651 0%, #007A3D 100%);
        color: white;
        border-radius: 15px 15px 0 0;
    }
    
    .form-control:focus {
        border-color: #00A651;
        box-shadow: 0 0 0 0.2rem rgba(0,166,81,0.25);
    }
    
    .badge {
        font-size: 0.8rem;
        padding: 0.5rem 0.75rem;
        border-radius: 20px;
    }
    
    .badge-success {
        background: linear-gradient(135deg, #00A651 0%, #007A3D 100%);
    }
    
    .badge-warning {
        background: linear-gradient(135deg, #FFC107 0%, #FF8F00 100%);
    }
    
    .badge-danger {
        background: linear-gradient(135deg, #DC3545 0%, #C82333 100%);
    }
    
    .badge-info {
        background: linear-gradient(135deg, #17A2B8 0%, #138496 100%);
    }
    
    .pagination-controls {
        background: white;
        padding: 1rem;
        border-radius: 0 0 10px 10px;
        border-top: 1px solid #e9ecef;
    }
    
    .page-link {
        color: #00A651;
        border: 1px solid #dee2e6;
        border-radius: 8px;
        margin: 0 2px;
        transition: all 0.3s ease;
    }
    
    .page-link:hover {
        background: #00A651;
        color: white;
        border-color: #00A651;
    }
    
    .page-item.active .page-link {
        background: #00A651;
        border-color: #00A651;
    }
</style>

<!-- Cabeçalho da Página -->
<div class="page-header">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="mb-0">
                    <i class="fas fa-graduation-cap me-3"></i>
                    Gestão de Turmas
                </h1>
                <p class="mb-0 opacity-75">Gerencie suas turmas teóricas e práticas</p>
            </div>
            <div class="col-md-4 text-end">
                <button class="btn btn-light btn-lg" onclick="abrirModalNovaTurma()">
                    <i class="fas fa-plus me-2"></i>
                    Nova Turma
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Estatísticas -->
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-graduation-cap text-success" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $estatisticas['total_turmas'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Total de Turmas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-play-circle text-info" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $estatisticas['turmas_ativas'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Turmas Ativas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-calendar-check text-warning" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $estatisticas['turmas_agendadas'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Agendadas</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stats-card">
                <div class="d-flex align-items-center">
                    <div class="stats-icon me-3">
                        <i class="fas fa-check-circle text-success" style="font-size: 2rem;"></i>
                    </div>
                    <div>
                        <h3 class="mb-0"><?php echo $estatisticas['turmas_concluidas'] ?? 0; ?></h3>
                        <p class="text-muted mb-0">Concluídas</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filtros -->
<div class="container-fluid">
    <div class="filters-section">
        <h5 class="mb-3">
            <i class="fas fa-filter me-2"></i>
            Filtros de Busca
        </h5>
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label">Buscar</label>
                <input type="text" class="form-control" name="busca" 
                       value="<?php echo htmlspecialchars($filtros['busca']); ?>" 
                       placeholder="Nome da turma, instrutor...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Data Início</label>
                <input type="date" class="form-control" name="data_inicio" 
                       value="<?php echo $filtros['data_inicio']; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Data Fim</label>
                <input type="date" class="form-control" name="data_fim" 
                       value="<?php echo $filtros['data_fim']; ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">Todos</option>
                    <option value="agendado" <?php echo $filtros['status'] === 'agendado' ? 'selected' : ''; ?>>Agendado</option>
                    <option value="ativa" <?php echo $filtros['status'] === 'ativa' ? 'selected' : ''; ?>>Ativa</option>
                    <option value="inativa" <?php echo $filtros['status'] === 'inativa' ? 'selected' : ''; ?>>Inativa</option>
                    <option value="concluida" <?php echo $filtros['status'] === 'concluida' ? 'selected' : ''; ?>>Concluída</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Tipo</label>
                <select class="form-select" name="tipo_aula">
                    <option value="">Todos</option>
                    <option value="teorica" <?php echo $filtros['tipo_aula'] === 'teorica' ? 'selected' : ''; ?>>Teórica</option>
                    <option value="pratica" <?php echo $filtros['tipo_aula'] === 'pratica' ? 'selected' : ''; ?>>Prática</option>
                </select>
            </div>
            <div class="col-md-1">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Turmas -->
<div class="container-fluid">
    <div class="table-container">
        <div class="table-header p-3 border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>
                    Lista de Turmas
                </h5>
                <span class="badge bg-primary">
                    <?php echo $totalTurmas; ?> turma(s) encontrada(s)
                </span>
            </div>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Nome da Turma</th>
                        <th>Instrutor</th>
                        <th>Início</th>
                        <th>Final</th>
                        <th>Alunos</th>
                        <th>Situação</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($turmas)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">Nenhuma turma encontrada</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($turmas as $turma): ?>
                            <tr>
                                <td>
                                    <div class="fw-bold"><?php echo htmlspecialchars($turma['nome']); ?></div>
                                    <small class="text-muted"><?php echo ucfirst($turma['tipo_aula']); ?></small>
                                </td>
                                <td>
                                    <div><?php echo htmlspecialchars($turma['instrutor_nome']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($turma['instrutor_email']); ?></small>
                                </td>
                                <td>
                                    <?php if ($turma['data_inicio']): ?>
                                        <?php echo date('d/m/Y', strtotime($turma['data_inicio'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($turma['data_fim']): ?>
                                        <?php echo date('d/m/Y', strtotime($turma['data_fim'])); ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge bg-info"><?php echo $turma['total_alunos']; ?></span>
                                </td>
                                <td>
                                    <?php
                                    $statusClass = match($turma['status']) {
                                        'agendado' => 'warning',
                                        'ativa' => 'success',
                                        'inativa' => 'secondary',
                                        'concluida' => 'info',
                                        default => 'secondary'
                                    };
                                    ?>
                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($turma['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <?php
                                        // Buscar primeira aula da turma para deep links
                                        $firstAula = $db->fetch("
                                            SELECT id FROM turma_aulas 
                                            WHERE turma_id = ? 
                                            ORDER BY data_aula ASC, hora_inicio ASC 
                                            LIMIT 1
                                        ", [$turma['id']]);
                                        $firstAulaId = $firstAula['id'] ?? null;
                                        ?>
                                        
                                        <!-- Botões de Ação Rápida -->
                                        <?php if ($userType === 'admin' || ($userType === 'instrutor' && $turma['instrutor_id'] == $userId)): ?>
                                            <?php if ($firstAulaId): ?>
                                                <a href="turma-chamada.php?turma_id=<?= $turma['id'] ?>&aula_id=<?= $firstAulaId ?>"
                                                   class="btn btn-sm btn-outline-primary" title="Chamada">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </a>
                                                <a href="turma-diario.php?turma_id=<?= $turma['id'] ?>&aula_id=<?= $firstAulaId ?>"
                                                   class="btn btn-sm btn-outline-info" title="Diário">
                                                    <i class="fas fa-book-open"></i>
                                                </a>
                                            <?php endif; ?>
                                        <?php endif; ?>

                                        <?php if ($userType === 'admin'): ?>
                                            <a href="turma-relatorios.php?turma_id=<?= $turma['id'] ?>"
                                               class="btn btn-sm btn-outline-success" title="Relatórios">
                                                <i class="fas fa-chart-bar"></i>
                                            </a>
                                        <?php endif; ?>

                                        <!-- Botões de Gestão -->
                                        <button class="btn btn-sm btn-outline-secondary" 
                                                onclick="visualizarTurma(<?php echo $turma['id']; ?>)"
                                                title="Visualizar">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-warning" 
                                                onclick="editarTurma(<?php echo $turma['id']; ?>)"
                                                title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger" 
                                                onclick="excluirTurma(<?php echo $turma['id']; ?>)"
                                                title="Excluir">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Paginação -->
        <?php if ($totalPaginas > 1): ?>
            <div class="pagination-controls">
                <nav aria-label="Paginação de turmas">
                    <ul class="pagination justify-content-center mb-0">
                        <?php if ($paginaAtual > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $filtros['pagina'] - 1])); ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $paginaAtual - 2); $i <= min($totalPaginas, $paginaAtual + 2); $i++): ?>
                            <li class="page-item <?php echo $i === $paginaAtual ? 'active' : ''; ?>">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $i - 1])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($paginaAtual < $totalPaginas): ?>
                            <li class="page-item">
                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['pagina' => $filtros['pagina'] + 1])); ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal de Nova Turma -->
<div class="modal fade" id="modalNovaTurma" tabindex="-1" aria-labelledby="modalNovaTurmaLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNovaTurmaLabel">
                    <i class="fas fa-plus me-2"></i>
                    Nova Turma
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="formNovaTurma">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Nome da Turma *</label>
                                <input type="text" class="form-control" name="nome" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Tipo de Aula *</label>
                                <select class="form-select" name="tipo_aula" required>
                                    <option value="">Selecione...</option>
                                    <option value="teorica">Teórica</option>
                                    <option value="pratica">Prática</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Instrutor *</label>
                                <select class="form-select" name="instrutor_id" required>
                                    <option value="">Selecione...</option>
                                    <?php foreach ($instrutores as $instrutor): ?>
                                        <option value="<?php echo $instrutor['id']; ?>">
                                            <?php echo htmlspecialchars($instrutor['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="agendado">Agendado</option>
                                    <option value="ativa">Ativa</option>
                                    <option value="inativa">Inativa</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data de Início</label>
                                <input type="date" class="form-control" name="data_inicio">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Data de Fim</label>
                                <input type="date" class="form-control" name="data_fim">
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Observações</label>
                        <textarea class="form-control" name="observacoes" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-2"></i>
                    Cancelar
                </button>
                <button type="button" class="btn btn-primary" onclick="salvarTurma()">
                    <i class="fas fa-save me-2"></i>
                    Salvar Turma
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript específico para a página -->
<script>
// Função para abrir modal de nova turma
function abrirModalNovaTurma() {
    const modal = new bootstrap.Modal(document.getElementById('modalNovaTurma'));
    modal.show();
}

// Função para salvar turma
function salvarTurma() {
    const form = document.getElementById('formNovaTurma');
    const formData = new FormData(form);
    
    // Adicionar dados extras
    formData.append('acao', 'criar');
    
    fetch('api/turmas.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.sucesso) {
            // Mostrar mensagem de sucesso
            showNotification('Turma criada com sucesso!', 'success');
            
            // Fechar modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalNovaTurma'));
            modal.hide();
            
            // Recarregar página
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        } else {
            showNotification(data.mensagem || 'Erro ao criar turma', 'danger');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showNotification('Erro ao comunicar com o servidor', 'danger');
    });
}

// Função para visualizar turma
function visualizarTurma(id) {
    showNotification('Funcionalidade em desenvolvimento', 'info');
}

// Função para editar turma
function editarTurma(id) {
    showNotification('Funcionalidade em desenvolvimento', 'info');
}

// Função para excluir turma
function excluirTurma(id) {
    if (confirm('Tem certeza que deseja excluir esta turma?')) {
        fetch('api/turmas.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
        .then(response => response.json())
        .then(data => {
            if (data.sucesso) {
                showNotification('Turma excluída com sucesso!', 'success');
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showNotification(data.mensagem || 'Erro ao excluir turma', 'danger');
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            showNotification('Erro ao comunicar com o servidor', 'danger');
        });
    }
}

// Função para mostrar notificações
function showNotification(message, type = 'info') {
    // Usar a função de notificação do sistema principal se disponível
    if (typeof window.showNotification === 'function') {
        window.showNotification(message, type);
    } else {
        // Fallback simples
        alert(message);
    }
}
</script>