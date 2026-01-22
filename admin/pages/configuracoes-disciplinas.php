<?php
/**
 * Página de Gerenciamento de Disciplinas
 * Sistema CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Verificar permissões
if (!$isAdmin && !$isInstrutor) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

// Incluir dependências
require_once __DIR__ . '/../../includes/database.php';

// Obter dados do usuário
$user = $_SESSION['user'] ?? null;
$cfcId = $user['cfc_id'] ?? 1;

// Processar ações
$acao = $_GET['acao'] ?? $_POST['acao'] ?? '';
$disciplinaId = $_GET['id'] ?? $_POST['id'] ?? null;

// Verificar se é requisição AJAX
$isAjax = isset($_GET['ajax']) || isset($_POST['acao']) && strpos($_POST['acao'], 'ajax') !== false;

// Processar exclusão
if ($acao === 'excluir' && $disciplinaId && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $db = Database::getInstance();
        
        // Verificar se a disciplina está sendo usada
        $emUso = $db->findWhere('turmas_disciplinas', 'disciplina_id = ?', [$disciplinaId], '*', null, 1);
        if ($emUso) {
            $erro = 'Não é possível excluir disciplina que está sendo usada em turmas';
        } else {
            $excluido = $db->update('disciplinas', ['ativa' => false], 'id = ? AND cfc_id = ?', [$disciplinaId, $cfcId]);
            if ($excluido) {
                $sucesso = 'Disciplina excluída com sucesso';
            } else {
                $erro = 'Erro ao excluir disciplina';
            }
        }
    } catch (Exception $e) {
        $erro = 'Erro interno: ' . $e->getMessage();
    }
}

// Buscar disciplinas
try {
    $db = Database::getInstance();
    $disciplinas = $db->findWhere('disciplinas', 'cfc_id = ? AND ativa = 1', [$cfcId], '*', 'nome ASC');
} catch (Exception $e) {
    $disciplinas = [];
    $erro = 'Erro ao carregar disciplinas: ' . $e->getMessage();
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0">
                        <i class="fas fa-book me-2 text-primary"></i>
                        Gerenciamento de Disciplinas
                    </h1>
                    <p class="text-muted mb-0">Gerencie as disciplinas disponíveis para os cursos</p>
                </div>
                <button type="button" class="btn btn-primary" onclick="abrirModalDisciplina()">
                    <i class="fas fa-plus me-1"></i>Nova Disciplina
                </button>
            </div>

            <!-- Alertas -->
            <?php if (isset($sucesso)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?php echo $sucesso; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($erro)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $erro; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Lista de Disciplinas -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Disciplinas Cadastradas
                        <span class="badge bg-primary ms-2"><?php echo count($disciplinas); ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($disciplinas)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma disciplina cadastrada</h5>
                            <p class="text-muted">Clique em "Nova Disciplina" para começar</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Nome</th>
                                        <th>Carga Horária</th>
                                        <th>Descrição</th>
                                        <th width="120">Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($disciplinas as $disciplina): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($disciplina['nome']); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">
                                                    <?php echo $disciplina['carga_horaria']; ?>h
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($disciplina['descricao'] ?? 'Sem descrição'); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button type="button" class="btn btn-outline-primary" 
                                                            onclick="editarDisciplina(<?php echo $disciplina['id']; ?>)"
                                                            title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger" 
                                                            onclick="excluirDisciplina(<?php echo $disciplina['id']; ?>, '<?php echo htmlspecialchars($disciplina['nome']); ?>')"
                                                            title="Excluir">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nova/Editar Disciplina -->
<div class="modal fade" id="modalDisciplina" tabindex="-1" aria-labelledby="modalDisciplinaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDisciplinaLabel">
                    <i class="fas fa-book me-2"></i>Nova Disciplina
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formDisciplina">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da Disciplina *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required
                               placeholder="Ex: Legislação de Trânsito">
                    </div>
                    
                    <div class="mb-3">
                        <label for="carga_horaria" class="form-label">Carga Horária *</label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="carga_horaria" name="carga_horaria" 
                                   min="1" max="100" required placeholder="18">
                            <span class="input-group-text">horas</span>
                        </div>
                        <small class="text-muted">Número de horas que a disciplina será ministrada</small>
                    </div>
                    
                    <div class="mb-3">
                        <label for="descricao" class="form-label">Descrição</label>
                        <textarea class="form-control" id="descricao" name="descricao" rows="3"
                                  placeholder="Descrição opcional da disciplina..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancelar
                    </button>
                    <button type="submit" class="btn btn-primary" id="btnSalvarDisciplina">
                        <i class="fas fa-save me-1"></i>Salvar Disciplina
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalConfirmarExclusao" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a disciplina <strong id="nomeDisciplinaExcluir"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle me-2"></i>
                    Esta ação não pode ser desfeita. A disciplina será removida permanentemente.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="fas fa-times me-1"></i>Cancelar
                </button>
                <button type="button" class="btn btn-danger" id="btnConfirmarExclusao">
                    <i class="fas fa-trash me-1"></i>Excluir
                </button>
            </div>
        </div>
    </div>
</div>

<style>
.table th {
    border-top: none;
    font-weight: 600;
    color: #495057;
}

.btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.75rem;
}

.modal-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.modal-title {
    color: #495057;
    font-weight: 600;
}
</style>

<script>
let disciplinaEditando = null;

// Abrir modal para nova disciplina
function abrirModalDisciplina() {
    disciplinaEditando = null;
    document.getElementById('modalDisciplinaLabel').innerHTML = '<i class="fas fa-book me-2"></i>Nova Disciplina';
    document.getElementById('formDisciplina').reset();
    document.getElementById('btnSalvarDisciplina').innerHTML = '<i class="fas fa-save me-1"></i>Salvar Disciplina';
    
    const modal = new bootstrap.Modal(document.getElementById('modalDisciplina'));
    modal.show();
}

// Editar disciplina
function editarDisciplina(id) {
    disciplinaEditando = id;
    document.getElementById('modalDisciplinaLabel').innerHTML = '<i class="fas fa-edit me-2"></i>Editar Disciplina';
    document.getElementById('btnSalvarDisciplina').innerHTML = '<i class="fas fa-save me-1"></i>Atualizar Disciplina';
    
    // Buscar dados da disciplina
    fetch(`admin/api/disciplinas.php?action=obter&id=${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const disciplina = data.disciplina;
                document.getElementById('nome').value = disciplina.nome;
                document.getElementById('carga_horaria').value = disciplina.carga_horaria;
                document.getElementById('descricao').value = disciplina.descricao || '';
                
                const modal = new bootstrap.Modal(document.getElementById('modalDisciplina'));
                modal.show();
            } else {
                alert('Erro ao carregar disciplina: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            alert('Erro ao carregar disciplina');
        });
}

// Excluir disciplina
function excluirDisciplina(id, nome) {
    document.getElementById('nomeDisciplinaExcluir').textContent = nome;
    document.getElementById('btnConfirmarExclusao').onclick = () => confirmarExclusao(id);
    
    const modal = new bootstrap.Modal(document.getElementById('modalConfirmarExclusao'));
    modal.show();
}

// Confirmar exclusão
function confirmarExclusao(id) {
    fetch(`admin/api/disciplinas.php?action=excluir&id=${id}`, {
        method: 'DELETE'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao excluir disciplina: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao excluir disciplina');
    });
}

// Salvar disciplina
document.getElementById('formDisciplina').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = {
        nome: formData.get('nome'),
        carga_horaria: formData.get('carga_horaria'),
        descricao: formData.get('descricao')
    };
    
    const url = disciplinaEditando 
        ? `admin/api/disciplinas.php?action=editar&id=${disciplinaEditando}`
        : 'admin/api/disciplinas.php?action=criar';
    
    const method = disciplinaEditando ? 'PUT' : 'POST';
    
    fetch(url, {
        method: method,
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Erro ao salvar disciplina: ' + data.error);
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        alert('Erro ao salvar disciplina');
    });
});
</script>
