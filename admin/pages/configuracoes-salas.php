<?php
/**
 * Página de Configuração de Salas
 * 
 * Interface administrativa para configurar as salas de aula do CFC
 */

// Verificar se estamos sendo incluídos pelo sistema de roteamento do admin
if (!defined('ADMIN_ROUTING')) {
    require_once '../../includes/config.php';
    require_once '../../includes/database.php';
    require_once '../../includes/auth.php';
    
    // Verificar se usuário está logado
    if (!isLoggedIn()) {
        header('Location: ../../index.php');
        exit;
    }
}

// Verificar permissões
if (!$isAdmin) {
    echo '<div class="alert alert-danger">Você não tem permissão para acessar esta página.</div>';
    return;
}

$db = Database::getInstance();

// Processar ações
$acao = $_POST['acao'] ?? $_GET['acao'] ?? '';
$mensagem = '';
$tipoMensagem = '';

// Verificar se é uma requisição AJAX
$isAjax = isset($_GET['ajax']) || isset($_POST['acao']) && strpos($_POST['acao'], 'ajax') !== false;

if (($acao === 'criar' || $acao === 'criar_ajax') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome'] ?? '');
    $capacidade = (int)($_POST['capacidade'] ?? 30);
    $equipamentos = $_POST['equipamentos'] ?? [];
    $ativa = isset($_POST['ativa']) ? 1 : 0;
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = 'Nome da sala é obrigatório';
    }
    
    if ($capacidade <= 0) {
        $erros[] = 'Capacidade deve ser maior que zero';
    }
    
    if (empty($erros)) {
        try {
            // Verificar se já existe sala com o mesmo nome
            $salaExistente = $db->fetch(
                "SELECT id FROM salas WHERE nome = ? AND cfc_id = ?",
                [$nome, $user['cfc_id'] ?? 1]
            );
            
            if ($salaExistente) {
                $erros[] = 'Já existe uma sala com este nome';
            } else {
                // Inserir nova sala
                $equipamentosJson = json_encode($equipamentos);
                
                $db->insert('salas', [
                    'nome' => $nome,
                    'capacidade' => $capacidade,
                    'equipamentos' => $equipamentosJson,
                    'ativa' => $ativa,
                    'cfc_id' => $user['cfc_id'] ?? 1
                ]);
                
                $mensagem = 'Sala criada com sucesso!';
                $tipoMensagem = 'success';
                
                // Se é requisição AJAX, retornar JSON
                if ($acao === 'criar_ajax') {
                    header('Content-Type: application/json');
                    echo json_encode([
                        'sucesso' => true,
                        'mensagem' => 'Sala criada com sucesso!',
                        'sala' => [
                            'id' => $db->lastInsertId(),
                            'nome' => $nome,
                            'capacidade' => $capacidade
                        ]
                    ]);
                    exit;
                }
                
                // Se foi aberto via popup, fechar e recarregar página pai
                if (isset($_GET['popup'])) {
                    echo "<script>
                        alert('Sala criada com sucesso!');
                        if (window.opener && !window.opener.closed) {
                            window.opener.location.reload();
                            window.close();
                        } else {
                            // Se não conseguir fechar, redirecionar para a página pai
                            setTimeout(function() {
                                window.location.href = '?page=turmas-teoricas&acao=nova&step=1';
                            }, 2000);
                        }
                    </script>";
                    exit;
                }
            }
        } catch (Exception $e) {
            $mensagem = 'Erro ao criar sala: ' . $e->getMessage();
            $tipoMensagem = 'danger';
            
            // Se é requisição AJAX, retornar JSON com erro
            if ($acao === 'criar_ajax') {
                header('Content-Type: application/json');
                echo json_encode([
                    'sucesso' => false,
                    'mensagem' => $mensagem
                ]);
                exit;
            }
        }
    } else {
        $mensagem = 'Erros encontrados: ' . implode(', ', $erros);
        $tipoMensagem = 'warning';
        
        // Se é requisição AJAX, retornar JSON com erro
        if ($acao === 'criar_ajax') {
            header('Content-Type: application/json');
            echo json_encode([
                'sucesso' => false,
                'mensagem' => $mensagem
            ]);
            exit;
        }
    }
}

if ($acao === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $nome = trim($_POST['nome'] ?? '');
    $capacidade = (int)($_POST['capacidade'] ?? 30);
    $equipamentos = $_POST['equipamentos'] ?? [];
    $ativa = isset($_POST['ativa']) ? 1 : 0;
    
    $erros = [];
    
    if (empty($nome)) {
        $erros[] = 'Nome da sala é obrigatório';
    }
    
    if ($capacidade <= 0) {
        $erros[] = 'Capacidade deve ser maior que zero';
    }
    
    if (empty($erros)) {
        try {
            // Verificar se já existe outra sala com o mesmo nome
            $salaExistente = $db->fetch(
                "SELECT id FROM salas WHERE nome = ? AND cfc_id = ? AND id != ?",
                [$nome, $user['cfc_id'] ?? 1, $id]
            );
            
            if ($salaExistente) {
                $erros[] = 'Já existe uma sala com este nome';
            } else {
                // Atualizar sala
                $equipamentosJson = json_encode($equipamentos);
                
                $db->update('salas', [
                    'nome' => $nome,
                    'capacidade' => $capacidade,
                    'equipamentos' => $equipamentosJson,
                    'ativa' => $ativa
                ], ['id' => $id]);
                
                $mensagem = 'Sala atualizada com sucesso!';
                $tipoMensagem = 'success';
            }
        } catch (Exception $e) {
            $mensagem = 'Erro ao atualizar sala: ' . $e->getMessage();
            $tipoMensagem = 'danger';
        }
    } else {
        $mensagem = 'Erros encontrados: ' . implode(', ', $erros);
        $tipoMensagem = 'warning';
    }
}

if ($acao === 'excluir' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    
    try {
        // Verificar se a sala está sendo usada em alguma turma
        $salaEmUso = $db->fetch(
            "SELECT id FROM turmas_teoricas WHERE sala_id = ? AND status NOT IN ('cancelada', 'concluida')",
            [$id]
        );
        
        if ($salaEmUso) {
            $mensagem = 'Não é possível excluir esta sala pois ela está sendo usada em uma turma ativa.';
            $tipoMensagem = 'warning';
        } else {
            $db->delete('salas', ['id' => $id]);
            $mensagem = 'Sala excluída com sucesso!';
            $tipoMensagem = 'success';
        }
    } catch (Exception $e) {
        $mensagem = 'Erro ao excluir sala: ' . $e->getMessage();
        $tipoMensagem = 'danger';
    }
}

// Buscar salas existentes
try {
    $salas = $db->fetchAll("
        SELECT s.*, 
               COUNT(t.id) as turmas_ativas
        FROM salas s 
        LEFT JOIN turmas_teoricas t ON s.id = t.sala_id AND t.status NOT IN ('cancelada', 'concluida')
        WHERE s.cfc_id = ?
        GROUP BY s.id
        ORDER BY s.nome ASC
    ", [$user['cfc_id'] ?? 1]);
} catch (Exception $e) {
    $salas = [];
}

// Se é requisição AJAX para listar salas
if (isset($_GET['ajax']) && $_GET['ajax'] === 'lista_salas') {
    header('Content-Type: application/json');
    
    // Gerar HTML das salas para o modal
    $html = '';
    if (empty($salas)) {
        $html = '<div class="text-center py-3">
            <i class="fas fa-door-open fa-2x text-muted mb-2"></i>
            <p class="text-muted">Nenhuma sala cadastrada</p>
        </div>';
    } else {
        foreach ($salas as $sala) {
            $equipamentos = json_decode($sala['equipamentos'] ?? '{}', true);
            $equipamentosList = '';
            if (!empty($equipamentos)) {
                $equipamentosList = '<div class="equipamentos-list mt-1">';
                foreach ($equipamentos as $equipamento => $disponivel) {
                    if ($disponivel === true || $disponivel === 'true') {
                        $equipamentosList .= '<div><i class="fas fa-check-circle me-1 text-success"></i>' . ucfirst(str_replace('_', ' ', $equipamento)) . '</div>';
                    }
                }
                $equipamentosList .= '</div>';
            }
            
            $statusBadge = $sala['ativa'] ? '<span class="badge bg-success">Ativa</span>' : '<span class="badge bg-secondary">Inativa</span>';
            
            $html .= '<div class="col-md-6 mb-3">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0"><i class="fas fa-door-open me-2"></i>' . htmlspecialchars($sala['nome']) . '</h6>
                        ' . $statusBadge . '
                    </div>
                    <div class="card-body">
                        <div class="mb-2">
                            <strong><i class="fas fa-users me-1"></i>Capacidade:</strong> ' . $sala['capacidade'] . ' alunos
                        </div>
                        ' . ($equipamentosList ? '<div class="mb-2"><strong><i class="fas fa-tools me-1"></i>Equipamentos:</strong>' . $equipamentosList . '</div>' : '') . '
                        <div class="mb-2">
                            <strong><i class="fas fa-chalkboard-teacher me-1"></i>Turmas Ativas:</strong> ' . $sala['turmas_ativas'] . '
                        </div>
                    </div>
                </div>
            </div>';
        }
        $html = '<div class="row">' . $html . '</div>';
    }
    
    echo json_encode([
        'sucesso' => true,
        'salas' => $salas,
        'html' => $html
    ]);
    exit;
}

// Buscar sala para edição
$salaEdicao = null;
if (isset($_GET['editar'])) {
    $salaId = (int)$_GET['editar'];
    $salaEdicao = $db->fetch("SELECT * FROM salas WHERE id = ? AND cfc_id = ?", [$salaId, $user['cfc_id'] ?? 1]);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Salas - Sistema CFC</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .sala-card {
            transition: transform 0.2s ease-in-out;
        }
        .sala-card:hover {
            transform: translateY(-2px);
        }
        .equipamentos-list {
            font-size: 0.9rem;
        }
        .equipamentos-list i {
            color: #28a745;
        }
    </style>
</head>
<body>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-door-open me-2"></i>Configurações de Salas</h2>
            <p class="text-muted mb-0">Gerencie as salas de aula do CFC</p>
            <?php if (isset($_GET['popup'])): ?>
            <div class="alert alert-info alert-sm mt-2">
                <i class="fas fa-info-circle me-1"></i>
                <strong>Modo Rápido:</strong> Esta janela fechará automaticamente após cadastrar uma sala.
            </div>
            <?php endif; ?>
        </div>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalNovaSala">
            <i class="fas fa-plus me-1"></i>Nova Sala
        </button>
    </div>

    <!-- Mensagens -->
    <?php if ($mensagem): ?>
    <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($mensagem); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Lista de Salas -->
    <div class="row">
        <?php foreach ($salas as $sala): ?>
        <?php 
        $equipamentos = json_decode($sala['equipamentos'] ?? '{}', true);
        ?>
        <div class="col-md-6 col-lg-4 mb-3">
            <div class="card sala-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">
                        <i class="fas fa-door-open me-2"></i><?php echo htmlspecialchars($sala['nome']); ?>
                    </h6>
                    <div>
                        <?php if ($sala['ativa']): ?>
                        <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inativa</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <strong><i class="fas fa-users me-1"></i>Capacidade:</strong> 
                        <?php echo $sala['capacidade']; ?> alunos
                    </div>
                    
                    <?php if (!empty($equipamentos)): ?>
                    <div class="mb-2">
                        <strong><i class="fas fa-tools me-1"></i>Equipamentos:</strong>
                        <div class="equipamentos-list mt-1">
                            <?php foreach ($equipamentos as $equipamento => $disponivel): ?>
                            <?php if ($disponivel === true || $disponivel === 'true'): ?>
                            <div><i class="fas fa-check-circle me-1"></i><?php echo ucfirst(str_replace('_', ' ', $equipamento)); ?></div>
                            <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-2">
                        <strong><i class="fas fa-chalkboard-teacher me-1"></i>Turmas Ativas:</strong> 
                        <?php echo $sala['turmas_ativas']; ?>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="btn-group w-100" role="group">
                        <button class="btn btn-outline-primary btn-sm" onclick="editarSala(<?php echo $sala['id']; ?>)">
                            <i class="fas fa-edit me-1"></i>Editar
                        </button>
                        <?php if ($sala['turmas_ativas'] == 0): ?>
                        <button class="btn btn-outline-danger btn-sm" onclick="excluirSala(<?php echo $sala['id']; ?>, '<?php echo htmlspecialchars($sala['nome']); ?>')">
                            <i class="fas fa-trash me-1"></i>Excluir
                        </button>
                        <?php else: ?>
                        <button class="btn btn-outline-secondary btn-sm" disabled title="Sala em uso">
                            <i class="fas fa-lock me-1"></i>Em Uso
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($salas)): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-door-open fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">Nenhuma sala cadastrada</h5>
                <p class="text-muted">Clique em "Nova Sala" para começar</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Nova/Editar Sala -->
<div class="modal fade" id="modalNovaSala" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-door-open me-2"></i>
                    <span id="modalTitulo">Nova Sala</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="formSala" method="POST">
                <input type="hidden" name="acao" value="criar" id="acaoForm">
                <input type="hidden" name="id" id="salaId">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="nome" class="form-label">Nome da Sala *</label>
                        <input type="text" class="form-control" id="nome" name="nome" required 
                               placeholder="Ex: Sala 1, Laboratório, Auditório">
                    </div>
                    
                    <div class="mb-3">
                        <label for="capacidade" class="form-label">Capacidade *</label>
                        <input type="number" class="form-control" id="capacidade" name="capacidade" 
                               min="1" max="100" value="30" required>
                        <div class="form-text">Número máximo de alunos que a sala comporta</div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Equipamentos Disponíveis</label>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[projetor]" id="projetor">
                                    <label class="form-check-label" for="projetor">
                                        <i class="fas fa-tv me-1"></i>Projetor
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[quadro]" id="quadro">
                                    <label class="form-check-label" for="quadro">
                                        <i class="fas fa-chalkboard me-1"></i>Quadro
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[ar_condicionado]" id="ar_condicionado">
                                    <label class="form-check-label" for="ar_condicionado">
                                        <i class="fas fa-snowflake me-1"></i>Ar Condicionado
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[computadores]" id="computadores">
                                    <label class="form-check-label" for="computadores">
                                        <i class="fas fa-desktop me-1"></i>Computadores
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[internet]" id="internet">
                                    <label class="form-check-label" for="internet">
                                        <i class="fas fa-wifi me-1"></i>Internet
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="equipamentos[som]" id="som">
                                    <label class="form-check-label" for="som">
                                        <i class="fas fa-volume-up me-1"></i>Sistema de Som
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="ativa" id="ativa" checked>
                            <label class="form-check-label" for="ativa">
                                Sala ativa (disponível para uso)
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Salvar Sala
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Confirmação de Exclusão -->
<div class="modal fade" id="modalExcluir" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-exclamation-triangle me-2"></i>Confirmar Exclusão
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Tem certeza que deseja excluir a sala <strong id="nomeSalaExclusao"></strong>?</p>
                <p class="text-muted">Esta ação não pode ser desfeita.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="id" id="idSalaExclusao">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Excluir
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Dados da sala para edição
const salaEdicao = <?php echo json_encode($salaEdicao); ?>;

// Função para editar sala
function editarSala(id) {
    const sala = salaEdicao;
    if (sala && sala.id == id) {
        document.getElementById('modalTitulo').textContent = 'Editar Sala';
        document.getElementById('acaoForm').value = 'editar';
        document.getElementById('salaId').value = sala.id;
        document.getElementById('nome').value = sala.nome;
        document.getElementById('capacidade').value = sala.capacidade;
        document.getElementById('ativa').checked = sala.ativa == 1;
        
        // Limpar checkboxes
        const checkboxes = document.querySelectorAll('input[name^="equipamentos"]');
        checkboxes.forEach(cb => cb.checked = false);
        
        // Marcar equipamentos disponíveis
        if (sala.equipamentos) {
            try {
                const equipamentos = JSON.parse(sala.equipamentos);
                Object.keys(equipamentos).forEach(equipamento => {
                    const checkbox = document.querySelector(`input[name="equipamentos[${equipamento}]"]`);
                    if (checkbox && equipamentos[equipamento]) {
                        checkbox.checked = true;
                    }
                });
            } catch (e) {
                console.error('Erro ao parsear equipamentos:', e);
            }
        }
        
        const modal = new bootstrap.Modal(document.getElementById('modalNovaSala'));
        modal.show();
    } else {
        // Redirecionar para editar
        window.location.href = '?page=configuracoes-salas&editar=' + id;
    }
}

// Função para excluir sala
function excluirSala(id, nome) {
    document.getElementById('nomeSalaExclusao').textContent = nome;
    document.getElementById('idSalaExclusao').value = id;
    const modal = new bootstrap.Modal(document.getElementById('modalExcluir'));
    modal.show();
}

// Limpar formulário ao abrir modal para nova sala
document.getElementById('modalNovaSala').addEventListener('show.bs.modal', function() {
    if (!salaEdicao) {
        document.getElementById('modalTitulo').textContent = 'Nova Sala';
        document.getElementById('acaoForm').value = 'criar';
        document.getElementById('salaId').value = '';
        document.getElementById('formSala').reset();
        document.getElementById('capacidade').value = '30';
        document.getElementById('ativa').checked = true;
    }
});
</script>

</body>
</html>
