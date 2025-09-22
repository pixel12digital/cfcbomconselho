<?php
/**
 * Sistema de Matrículas - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências






$db = Database::getInstance();
$user = getCurrentUser();
$userType = $user['tipo'] ?? 'admin';
$userId = $user['id'] ?? null;

// Verificar permissões
$canView = ($userType === 'admin' || $userType === 'instrutor');
if (!$canView) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Processar ações
if ($_POST) {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'matricular':
            $turma_id = $_POST['turma_id'] ?? null;
            $aluno_id = $_POST['aluno_id'] ?? null;
            
            if ($turma_id && $aluno_id) {
                try {
                    // Verificar se já está matriculado
                    $existente = $db->fetch("
                        SELECT id FROM turma_alunos 
                        WHERE turma_id = ? AND aluno_id = ?
                    ", [$turma_id, $aluno_id]);
                    
                    if (!$existente) {
                        $db->query("
                            INSERT INTO turma_alunos (turma_id, aluno_id, status, data_matricula) 
                            VALUES (?, ?, 'matriculado', NOW())
                        ", [$turma_id, $aluno_id]);
                        
                        // Atualizar contador de alunos na turma
                        $db->query("
                            UPDATE turmas 
                            SET total_alunos = (SELECT COUNT(*) FROM turma_alunos WHERE turma_id = ? AND status = 'matriculado')
                            WHERE id = ?
                        ", [$turma_id, $turma_id]);
                        
                        $mensagem = "Aluno matriculado com sucesso!";
                        $tipoMensagem = "success";
                    } else {
                        $mensagem = "Aluno já está matriculado nesta turma.";
                        $tipoMensagem = "warning";
                    }
                } catch (Exception $e) {
                    $mensagem = "Erro ao matricular aluno: " . $e->getMessage();
                    $tipoMensagem = "danger";
                }
            }
            break;
            
        case 'desmatricular':
            $matricula_id = $_POST['matricula_id'] ?? null;
            
            if ($matricula_id) {
                try {
                    $matricula = $db->fetch("SELECT turma_id FROM turma_alunos WHERE id = ?", [$matricula_id]);
                    
                    $db->query("DELETE FROM turma_alunos WHERE id = ?", [$matricula_id]);
                    
                    // Atualizar contador de alunos na turma
                    if ($matricula) {
                        $db->query("
                            UPDATE turmas 
                            SET total_alunos = (SELECT COUNT(*) FROM turma_alunos WHERE turma_id = ? AND status = 'matriculado')
                            WHERE id = ?
                        ", [$matricula['turma_id'], $matricula['turma_id']]);
                    }
                    
                    $mensagem = "Aluno desmatriculado com sucesso!";
                    $tipoMensagem = "success";
                } catch (Exception $e) {
                    $mensagem = "Erro ao desmatricular aluno: " . $e->getMessage();
                    $tipoMensagem = "danger";
                }
            }
            break;
    }
}

// Buscar matrículas existentes
try {
    $matriculas = $db->fetchAll("
        SELECT ta.*, a.nome as aluno_nome, a.cpf, a.email, a.telefone,
               t.nome as turma_nome, t.data_inicio, t.data_fim, t.capacidade_maxima,
               i.nome as instrutor_nome
        FROM turma_alunos ta
        JOIN alunos a ON ta.aluno_id = a.id
        JOIN turmas t ON ta.turma_id = t.id
        LEFT JOIN instrutores i ON t.instrutor_id = i.id
        WHERE t.tipo_aula = 'teorica'
        ORDER BY ta.data_matricula DESC
    ");
} catch (Exception $e) {
    $matriculas = [];
}
?>





<style>
    /* Estilos para badges de status */
    .badge-status {
        font-size: 0.75rem;
        padding: 0.375rem 0.75rem;
        border-radius: 0.375rem;
        font-weight: 500;
    }
    
    .badge-matriculado {
        background-color: #6c757d !important;
        color: #ffffff !important;
    }
    
    .badge-concluido {
        background-color: #198754 !important;
        color: #ffffff !important;
    }
    
    .badge-cancelado {
        background-color: #dc3545 !important;
        color: #ffffff !important;
    }
    
    .badge-transferido {
        background-color: #fd7e14 !important;
        color: #ffffff !important;
    }
</style>

<div class="matriculas-container">
        <div class="container-fluid">
            <!-- Header -->
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h1 class="mb-2">
                            <i class="fas fa-user-plus me-3"></i>
                            Sistema de Matrículas
                        </h1>
                        <p class="mb-0 opacity-75">
                            Gerenciamento de matrículas em turmas teóricas
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="btn-group" role="group">
                            <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#novaMatriculaModal">
                                <i class="fas fa-plus"></i> Nova Matrícula
                            </button>
                            <a href="?page=turma-matriculas" class="btn btn-light btn-sm">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensagens -->
            <?php if (isset($mensagem)): ?>
                <div class="alert alert-<?= $tipoMensagem ?> alert-dismissible fade show" role="alert">
                    <i class="fas fa-<?= $tipoMensagem === 'success' ? 'check-circle' : ($tipoMensagem === 'warning' ? 'exclamation-triangle' : 'times-circle') ?> me-2"></i>
                    <?= htmlspecialchars($mensagem) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Estatísticas -->
            <div class="row">
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-1"><?= count($matriculas) ?></h3>
                                <p class="mb-0 text-muted">Total de Matrículas</p>
                            </div>
                            <i class="fas fa-users fa-2x text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-1"><?= count(array_filter($matriculas, fn($m) => $m['status'] === 'matriculado')) ?></h3>
                                <p class="mb-0 text-muted">Ativas</p>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-1"><?= count(array_filter($matriculas, fn($m) => $m['status'] === 'concluido')) ?></h3>
                                <p class="mb-0 text-muted">Concluídas</p>
                            </div>
                            <i class="fas fa-graduation-cap fa-2x text-info"></i>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stats-card">
                        <div class="d-flex align-items-center">
                            <div class="flex-grow-1">
                                <h3 class="mb-1"><?= count(array_filter($matriculas, fn($m) => $m['status'] === 'cancelado')) ?></h3>
                                <p class="mb-0 text-muted">Canceladas</p>
                            </div>
                            <i class="fas fa-times-circle fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lista de Matrículas -->
            <div class="table-container">
                <div class="card-header bg-light border-bottom">
                    <h5 class="mb-0 text-dark">
                        <i class="fas fa-list me-2 text-primary"></i>
                        Lista de Matrículas
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Aluno</th>
                                <th>Turma</th>
                                <th>Instrutor</th>
                                <th>Data Matrícula</th>
                                <th>Status</th>
                                <th>Ações</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($matriculas)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <i class="fas fa-inbox text-muted" style="font-size: 3rem;"></i>
                                        <p class="text-muted mt-2">Nenhuma matrícula encontrada</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($matriculas as $matricula): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($matricula['aluno_nome']) ?></div>
                                            <small class="text-muted"><?= htmlspecialchars($matricula['cpf']) ?></small>
                                        </td>
                                        <td>
                                            <div class="fw-bold"><?= htmlspecialchars($matricula['turma_nome']) ?></div>
                                            <small class="text-muted">
                                                <?= date('d/m/Y', strtotime($matricula['data_inicio'])) ?> - 
                                                <?= date('d/m/Y', strtotime($matricula['data_fim'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($matricula['instrutor_nome'] ?? 'Não definido') ?></div>
                                        </td>
                                        <td>
                                            <?= date('d/m/Y H:i', strtotime($matricula['data_matricula'])) ?>
                                        </td>
                                        <td>
                                            <span class="badge badge-status badge-<?= $matricula['status'] ?>">
                                                <?= ucfirst($matricula['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="?page=turma-matriculas" 
                                                   class="btn btn-sm btn-outline-primary" title="Ver Chamada">
                                                    <i class="fas fa-clipboard-check"></i>
                                                </a>
                                                <?php if ($matricula['status'] === 'matriculado'): ?>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="desmatricular(<?= $matricula['id'] ?>, '<?= htmlspecialchars($matricula['aluno_nome']) ?>')"
                                                            title="Desmatricular">
                                                        <i class="fas fa-user-minus"></i>
                                                    </button>
                                                <?php endif; ?>
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
    </div>

    <!-- Modal Nova Matrícula -->
    <div class="modal fade" id="novaMatriculaModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>
                        Nova Matrícula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="matricular">
                        
                        <div class="row">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Turma *</label>
                                <select class="form-select" name="turma_id" required>
                                    <option value="">Selecione uma turma...</option>
                                    <?php foreach ($turmas as $turma): ?>
                                        <option value="<?= $turma['id'] ?>">
                                            <?= htmlspecialchars($turma['nome']) ?> 
                                            (<?= date('d/m/Y', strtotime($turma['data_inicio'])) ?> - 
                                             <?= date('d/m/Y', strtotime($turma['data_fim'])) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Aluno *</label>
                                <select class="form-select" name="aluno_id" required>
                                    <option value="">Selecione um aluno...</option>
                                    <?php foreach ($alunos as $aluno): ?>
                                        <option value="<?= $aluno['id'] ?>">
                                            <?= htmlspecialchars($aluno['nome']) ?> 
                                            (<?= htmlspecialchars($aluno['cpf']) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Matricular
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Confirmação Desmatrícula -->
    <div class="modal fade" id="confirmarDesmatriculaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirmar Desmatrícula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Tem certeza que deseja desmatricular o aluno <strong id="nomeAluno"></strong>?</p>
                    <p class="text-muted">Esta ação não pode ser desfeita.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="action" value="desmatricular">
                        <input type="hidden" name="matricula_id" id="matriculaId">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-user-minus"></i> Desmatricular
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        function desmatricular(matriculaId, nomeAluno) {
            document.getElementById('matriculaId').value = matriculaId;
            document.getElementById('nomeAluno').textContent = nomeAluno;
            
            const modal = new bootstrap.Modal(document.getElementById('confirmarDesmatriculaModal'));
            modal.show();
        }
    </script>


