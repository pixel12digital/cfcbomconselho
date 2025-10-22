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
        
        /* Estilos para edição inline */
        .sala-edit-mode {
            background-color: #f8f9fa;
            border-radius: 0.375rem;
            padding: 1rem;
            margin: -0.5rem;
        }
        
        .sala-edit-mode .form-label {
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .sala-edit-mode .form-control-sm {
            font-size: 0.875rem;
        }
        
        .sala-edit-mode .form-check-sm {
            font-size: 0.8rem;
        }
        
        .sala-edit-mode .form-check-label {
            font-size: 0.8rem;
        }
        
        .sala-card.editing {
            border: 2px solid #007bff;
            box-shadow: 0 0.5rem 1rem rgba(0, 123, 255, 0.15);
        }
        
        .sala-card.editing .card-header {
            background-color: #e3f2fd;
        }
        
        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10;
        }
        
        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
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
                    <!-- Modo Visualização - Nome -->
                    <h6 class="mb-0 sala-nome-display" id="nome-display-<?php echo $sala['id']; ?>">
                        <i class="fas fa-door-open me-2"></i><?php echo htmlspecialchars($sala['nome']); ?>
                    </h6>
                    
                    <!-- Modo Edição - Nome -->
                    <div class="sala-nome-edit" id="nome-edit-<?php echo $sala['id']; ?>" style="display: none;">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text"><i class="fas fa-door-open"></i></span>
                            <input type="text" class="form-control" id="nome-edit-input-<?php echo $sala['id']; ?>" 
                                   value="<?php echo htmlspecialchars($sala['nome']); ?>" placeholder="Nome da sala">
                        </div>
                    </div>
                    
                    <div>
                        <?php if ($sala['ativa']): ?>
                        <span class="badge bg-success">Ativa</span>
                        <?php else: ?>
                        <span class="badge bg-secondary">Inativa</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Modo Visualização -->
                    <div class="sala-view-mode" id="view-mode-<?php echo $sala['id']; ?>">
                        <div class="mb-2">
                            <strong><i class="fas fa-users me-1"></i>Capacidade:</strong> 
                            <span class="capacidade-display"><?php echo $sala['capacidade']; ?></span> alunos
                        </div>
                        
                        <?php if (!empty($equipamentos)): ?>
                        <div class="mb-2">
                            <strong><i class="fas fa-tools me-1"></i>Equipamentos:</strong>
                            <div class="equipamentos-list mt-1 equipamentos-display">
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

                    <!-- Modo Edição -->
                    <div class="sala-edit-mode" id="edit-mode-<?php echo $sala['id']; ?>" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-users me-1"></i>Capacidade:</label>
                            <input type="number" class="form-control form-control-sm" id="capacidade-edit-<?php echo $sala['id']; ?>" 
                                   value="<?php echo $sala['capacidade']; ?>" min="1" max="100">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label"><i class="fas fa-tools me-1"></i>Equipamentos:</label>
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="equipamentos[projetor]" 
                                               id="projetor-edit-<?php echo $sala['id']; ?>" 
                                               <?php echo isset($equipamentos['projetor']) && $equipamentos['projetor'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="projetor-edit-<?php echo $sala['id']; ?>">
                                            <i class="fas fa-tv me-1"></i>Projetor
                                        </label>
                                    </div>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="equipamentos[quadro]" 
                                               id="quadro-edit-<?php echo $sala['id']; ?>" 
                                               <?php echo isset($equipamentos['quadro']) && $equipamentos['quadro'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="quadro-edit-<?php echo $sala['id']; ?>">
                                            <i class="fas fa-chalkboard me-1"></i>Quadro
                                        </label>
                                    </div>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="equipamentos[ar_condicionado]" 
                                               id="ar-edit-<?php echo $sala['id']; ?>" 
                                               <?php echo isset($equipamentos['ar_condicionado']) && $equipamentos['ar_condicionado'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="ar-edit-<?php echo $sala['id']; ?>">
                                            <i class="fas fa-snowflake me-1"></i>Ar Condicionado
                                        </label>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="equipamentos[computadores]" 
                                               id="computadores-edit-<?php echo $sala['id']; ?>" 
                                               <?php echo isset($equipamentos['computadores']) && $equipamentos['computadores'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="computadores-edit-<?php echo $sala['id']; ?>">
                                            <i class="fas fa-desktop me-1"></i>Computadores
                                        </label>
                                    </div>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="equipamentos[internet]" 
                                               id="internet-edit-<?php echo $sala['id']; ?>" 
                                               <?php echo isset($equipamentos['internet']) && $equipamentos['internet'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="internet-edit-<?php echo $sala['id']; ?>">
                                            <i class="fas fa-wifi me-1"></i>Internet
                                        </label>
                                    </div>
                                    <div class="form-check form-check-sm">
                                        <input class="form-check-input" type="checkbox" name="equipamentos[som]" 
                                               id="som-edit-<?php echo $sala['id']; ?>" 
                                               <?php echo isset($equipamentos['som']) && $equipamentos['som'] ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="som-edit-<?php echo $sala['id']; ?>">
                                            <i class="fas fa-volume-up me-1"></i>Sistema de Som
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="ativa-edit-<?php echo $sala['id']; ?>" 
                                       <?php echo $sala['ativa'] ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="ativa-edit-<?php echo $sala['id']; ?>">
                                    <i class="fas fa-check-circle me-1"></i>Sala ativa
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-2">
                            <strong><i class="fas fa-chalkboard-teacher me-1"></i>Turmas Ativas:</strong> 
                            <?php echo $sala['turmas_ativas']; ?>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <!-- Botões Modo Visualização -->
                    <div class="btn-group w-100 sala-view-buttons" id="view-buttons-<?php echo $sala['id']; ?>">
                        <button class="btn btn-outline-primary btn-sm" onclick="editarSalaInline(<?php echo $sala['id']; ?>)">
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
                    
                    <!-- Botões Modo Edição -->
                    <div class="btn-group w-100 sala-edit-buttons" id="edit-buttons-<?php echo $sala['id']; ?>" style="display: none;">
                        <button class="btn btn-success btn-sm" onclick="salvarSalaInline(<?php echo $sala['id']; ?>)">
                            <i class="fas fa-save me-1"></i>Salvar
                        </button>
                        <button class="btn btn-secondary btn-sm" onclick="cancelarEdicaoInline(<?php echo $sala['id']; ?>)">
                            <i class="fas fa-times me-1"></i>Cancelar
                        </button>
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

// Função para editar sala inline
function editarSalaInline(id) {
    // Mostrar modo de edição
    document.getElementById('view-mode-' + id).style.display = 'none';
    document.getElementById('edit-mode-' + id).style.display = 'block';
    document.getElementById('view-buttons-' + id).style.display = 'none';
    document.getElementById('edit-buttons-' + id).style.display = 'flex';
    
    // Mostrar edição do nome no header
    document.getElementById('nome-display-' + id).style.display = 'none';
    document.getElementById('nome-edit-' + id).style.display = 'block';
    
    // Adicionar classe de edição ao card
    const card = document.querySelector(`[id*="${id}"]`).closest('.sala-card');
    card.classList.add('editing');
}

// Função para cancelar edição inline
function cancelarEdicaoInline(id) {
    // Voltar ao modo de visualização
    document.getElementById('view-mode-' + id).style.display = 'block';
    document.getElementById('edit-mode-' + id).style.display = 'none';
    document.getElementById('view-buttons-' + id).style.display = 'flex';
    document.getElementById('edit-buttons-' + id).style.display = 'none';
    
    // Voltar nome no header
    document.getElementById('nome-display-' + id).style.display = 'block';
    document.getElementById('nome-edit-' + id).style.display = 'none';
    
    // Remover classe de edição do card
    const card = document.querySelector(`[id*="${id}"]`).closest('.sala-card');
    card.classList.remove('editing');
}

// Função para salvar sala inline
function salvarSalaInline(id) {
    const card = document.querySelector(`[id*="${id}"]`).closest('.sala-card');
    
    // Adicionar overlay de loading
    const loadingOverlay = document.createElement('div');
    loadingOverlay.className = 'loading-overlay';
    loadingOverlay.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Carregando...</span></div>';
    card.style.position = 'relative';
    card.appendChild(loadingOverlay);
    
    // Coletar dados do formulário
    const nome = document.getElementById('nome-edit-input-' + id).value.trim();
    const capacidade = document.getElementById('capacidade-edit-' + id).value;
    const ativa = document.getElementById('ativa-edit-' + id).checked;
    
    // Coletar equipamentos
    const equipamentos = {};
    const equipamentosCheckboxes = document.querySelectorAll(`#edit-mode-${id} input[name^="equipamentos"]`);
    equipamentosCheckboxes.forEach(checkbox => {
        const name = checkbox.name.match(/\[(.*?)\]/)[1];
        equipamentos[name] = checkbox.checked;
    });
    
    // Validar dados
    if (!nome) {
        alert('Nome da sala é obrigatório');
        card.removeChild(loadingOverlay);
        return;
    }
    
    if (!capacidade || capacidade <= 0) {
        alert('Capacidade deve ser maior que zero');
        card.removeChild(loadingOverlay);
        return;
    }
    
    // Preparar dados para envio
    const formData = new FormData();
    formData.append('acao', 'editar');
    formData.append('id', id);
    formData.append('nome', nome);
    formData.append('capacidade', capacidade);
    formData.append('ativa', ativa ? '1' : '0');
    formData.append('equipamentos', JSON.stringify(equipamentos));
    
    // Enviar requisição AJAX
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        // Remover overlay de loading
        card.removeChild(loadingOverlay);
        
        // Verificar se houve erro na resposta
        if (data.includes('alert-danger') || data.includes('Erro')) {
            alert('Erro ao salvar sala. Verifique os dados e tente novamente.');
            return;
        }
        
        // Atualizar dados na visualização
        atualizarVisualizacaoSala(id, nome, capacidade, ativa, equipamentos);
        
        // Voltar ao modo de visualização
        cancelarEdicaoInline(id);
        
        // Mostrar mensagem de sucesso
        mostrarMensagem('Sala atualizada com sucesso!', 'success');
    })
    .catch(error => {
        console.error('Erro:', error);
        card.removeChild(loadingOverlay);
        alert('Erro ao salvar sala. Tente novamente.');
    });
}

// Função para atualizar a visualização da sala
function atualizarVisualizacaoSala(id, nome, capacidade, ativa, equipamentos) {
    // Atualizar nome no header
    const nomeDisplay = document.querySelector(`#nome-display-${id}`);
    if (nomeDisplay) {
        nomeDisplay.innerHTML = `<i class="fas fa-door-open me-2"></i>${nome}`;
    }
    
    // Atualizar capacidade
    const capacidadeDisplay = document.querySelector(`#view-mode-${id} .capacidade-display`);
    if (capacidadeDisplay) {
        capacidadeDisplay.textContent = capacidade;
    }
    
    // Atualizar status ativa/inativa no header
    const cardHeader = document.querySelector(`#view-mode-${id}`).closest('.sala-card').querySelector('.card-header');
    const badge = cardHeader.querySelector('.badge');
    if (badge) {
        if (ativa) {
            badge.className = 'badge bg-success';
            badge.textContent = 'Ativa';
        } else {
            badge.className = 'badge bg-secondary';
            badge.textContent = 'Inativa';
        }
    }
    
    // Atualizar equipamentos
    const equipamentosDisplay = document.querySelector(`#view-mode-${id} .equipamentos-display`);
    if (equipamentosDisplay) {
        let equipamentosHtml = '';
        Object.keys(equipamentos).forEach(equipamento => {
            if (equipamentos[equipamento]) {
                equipamentosHtml += `<div><i class="fas fa-check-circle me-1"></i>${equipamento.charAt(0).toUpperCase() + equipamento.slice(1).replace('_', ' ')}</div>`;
            }
        });
        equipamentosDisplay.innerHTML = equipamentosHtml;
    }
}

// Função para mostrar mensagem de feedback
function mostrarMensagem(mensagem, tipo) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${tipo} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${mensagem}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    const container = document.querySelector('.container-fluid');
    const header = container.querySelector('.d-flex.justify-content-between');
    header.insertAdjacentElement('afterend', alertDiv);
    
    // Remover mensagem após 5 segundos
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

// Função para editar sala (modal - mantida para compatibilidade)
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
