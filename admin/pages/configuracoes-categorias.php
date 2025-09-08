<?php
/**
 * Página de Configuração de Categorias
 * 
 * Interface administrativa para configurar as quantidades de aulas
 * para cada categoria de habilitação.
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

// Incluir classe de configurações
require_once 'includes/configuracoes_categorias.php';

// Processar formulário
$mensagem = '';
$tipoMensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'salvar') {
        $dados = [
            'categoria' => $_POST['categoria'] ?? '',
            'nome' => $_POST['nome'] ?? '',
            'tipo' => $_POST['tipo'] ?? '',
            'horas_teoricas' => (int)($_POST['horas_teoricas'] ?? 0),
            'horas_praticas_total' => (int)($_POST['horas_praticas_total'] ?? 0),
            'horas_praticas_moto' => (int)($_POST['horas_praticas_moto'] ?? 0),
            'horas_praticas_carro' => (int)($_POST['horas_praticas_carro'] ?? 0),
            'horas_praticas_carga' => (int)($_POST['horas_praticas_carga'] ?? 0),
            'horas_praticas_passageiros' => (int)($_POST['horas_praticas_passageiros'] ?? 0),
            'horas_praticas_combinacao' => (int)($_POST['horas_praticas_combinacao'] ?? 0),
            'observacoes' => $_POST['observacoes'] ?? ''
        ];
        
        // Validar dados
        $configManager = ConfiguracoesCategorias::getInstance();
        $erros = []; // Validação básica - pode ser expandida
        
        if (empty($dados['categoria'])) {
            $erros[] = 'Categoria é obrigatória';
        }
        
        if (empty($erros)) {
            try {
                if ($configManager->saveConfiguracao($dados)) {
                    $mensagem = 'Configuração salva com sucesso!';
                    $tipoMensagem = 'success';
                } else {
                    $mensagem = 'Erro ao salvar configuração.';
                    $tipoMensagem = 'danger';
                }
            } catch (Exception $e) {
                $mensagem = 'Erro ao salvar configuração: ' . $e->getMessage();
                $tipoMensagem = 'danger';
            }
        } else {
            $mensagem = 'Erros encontrados: ' . implode(', ', $erros);
            $tipoMensagem = 'warning';
        }
    }
    
    if ($acao === 'restaurar') {
        $categoria = $_POST['categoria'] ?? '';
        if (!empty($categoria)) {
            try {
                if ($configManager->restoreDefault($categoria)) {
                    $mensagem = 'Configuração restaurada para valores padrão!';
                    $tipoMensagem = 'info';
                } else {
                    $mensagem = 'Erro ao restaurar configuração.';
                    $tipoMensagem = 'danger';
                }
            } catch (Exception $e) {
                $mensagem = 'Erro ao restaurar configuração: ' . $e->getMessage();
                $tipoMensagem = 'danger';
            }
        }
    }
}

// Obter apenas configurações das categorias base (A, B, C, D, E)
$configManager = ConfiguracoesCategorias::getInstance();
$todasConfiguracoes = $configManager->getAllConfiguracoes();

// Filtrar apenas categorias base
$categoriasBase = ['A', 'B', 'C', 'D', 'E'];
$configuracoes = array_filter($todasConfiguracoes, function($config) use ($categoriasBase) {
    return in_array($config['categoria'], $categoriasBase);
});

// Ordenar por categoria
usort($configuracoes, function($a, $b) {
    return strcmp($a['categoria'], $b['categoria']);
});

// Obter configuração para edição
$configuracaoEdicao = null;
if (isset($_GET['editar'])) {
    $configuracaoEdicao = $configManager->getConfiguracaoByCategoria($_GET['editar']);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações de Categorias Base - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <style>
        .config-card {
            transition: transform 0.2s;
        }
        .config-card:hover {
            transform: translateY(-2px);
        }
        .tipo-badge {
            font-size: 0.8em;
        }
        .horas-info {
            font-size: 0.9em;
        }
        
        /* CORREÇÃO DE CORES - CONFIGURAÇÕES DE CATEGORIAS */
        .card-header.bg-success {
            background-color: #198754 !important;
            color: #ffffff !important;
        }
        
        .card-header.bg-info {
            background-color: #0dcaf0 !important;
            color: #ffffff !important;
        }
        
        .card-header.bg-warning {
            background-color: #ffc107 !important;
            color: #000000 !important;
        }
        
        /* Garantir que os badges tenham cores corretas */
        .badge.bg-success {
            background-color: #198754 !important;
            color: #ffffff !important;
        }
        
        .badge.bg-info {
            background-color: #0dcaf0 !important;
            color: #ffffff !important;
        }
        
        .badge.bg-warning {
            background-color: #ffc107 !important;
            color: #000000 !important;
        }
        
        /* Garantir que os botões tenham cores corretas */
        .btn-success {
            background-color: #198754 !important;
            border-color: #198754 !important;
            color: #ffffff !important;
        }
        
        .btn-info {
            background-color: #0dcaf0 !important;
            border-color: #0dcaf0 !important;
            color: #ffffff !important;
        }
        
        .btn-warning {
            background-color: #ffc107 !important;
            border-color: #ffc107 !important;
            color: #000000 !important;
        }
        
        /* Hover states */
        .btn-success:hover {
            background-color: #157347 !important;
            border-color: #146c43 !important;
        }
        
        .btn-info:hover {
            background-color: #0aa2c0 !important;
            border-color: #0aa2c0 !important;
        }
        
        .btn-warning:hover {
            background-color: #ffca2c !important;
            border-color: #ffc720 !important;
        }
        
        /* Garantir que os botões dentro dos cards tenham estilo */
        .card-footer .btn {
            margin-right: 5px;
        }
        
        .card-footer .btn:last-child {
            margin-right: 0;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row bg-primary text-white p-3 mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-cogs me-2"></i>
                    Configurações de Categorias Base
                </h1>
            </div>
            <div class="col-auto">
                <button type="button" class="btn btn-outline-light" data-bs-toggle="modal" data-bs-target="#modalNovaConfiguracao">
                    <i class="fas fa-plus me-2"></i>Nova Configuração
                </button>
            </div>
        </div>

        <!-- Mensagens -->
        <?php if ($mensagem): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-<?php echo $tipoMensagem; ?> alert-dismissible fade show">
                    <?php echo htmlspecialchars($mensagem); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Explicação do Sistema -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h5 class="alert-heading">
                        <i class="fas fa-info-circle me-2"></i>
                        Como funciona o sistema simplificado
                    </h5>
                    <p class="mb-2">
                        <strong>Configuramos apenas as 5 categorias base:</strong> A, B, C, D, E
                    </p>
                    <p class="mb-2">
                        <strong>As combinações são geradas automaticamente:</strong>
                    </p>
                    <ul class="mb-0">
                        <li><strong>AB</strong> = A + B (Motocicletas + Automóveis)</li>
                        <li><strong>AC</strong> = A + C (Motocicletas + Carga)</li>
                        <li><strong>ABC</strong> = A + B + C (Motocicletas + Automóveis + Carga)</li>
                        <li>E assim por diante...</li>
                    </ul>
                    <hr>
                    <p class="mb-0">
                        <small class="text-muted">
                            <i class="fas fa-lightbulb me-1"></i>
                            <strong>Vantagem:</strong> Mais simples de gerenciar e evita duplicação de configurações.
                        </small>
                    </p>
                </div>
            </div>
        </div>

        <!-- Configurações Existentes -->
        
        <!-- Primeira Habilitação -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-graduation-cap me-2"></i>
                            <?php echo "Primeira Habilitação"; ?>
                        </h5>
                        <small>Configurações para quem está tirando a primeira CNH</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $primeiraHabilitacao = array_filter($configuracoes, function($config) {
                                return $config['tipo'] === 'primeira_habilitacao';
                            });
                            foreach ($primeiraHabilitacao as $config): 
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card config-card h-100 border-success">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <span class="badge bg-success me-2"><?php echo htmlspecialchars($config['categoria']); ?></span>
                                            <?php echo htmlspecialchars($config['nome']); ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="horas-info">
                                            <?php if ($config['horas_teoricas'] > 0): ?>
                                            <p class="mb-1">
                                                <i class="fas fa-book text-info me-2"></i>
                                                <strong>Teóricas:</strong> <?php echo $config['horas_teoricas']; ?>h
                                            </p>
                                            <?php endif; ?>
                                            
                                            <p class="mb-1">
                                                <i class="fas fa-car text-success me-2"></i>
                                                <strong>Práticas:</strong> <?php echo $config['horas_praticas_total']; ?>h
                                            </p>
                                        </div>
                                        
                                        <?php if ($config['observacoes']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?php echo htmlspecialchars($config['observacoes']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-success btn-sm" 
                                                    onclick="editarConfiguracao('<?php echo $config['categoria']; ?>')">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </button>
                                            <button type="button" class="btn btn-outline-info btn-sm" 
                                                    onclick="restaurarConfiguracao('<?php echo $config['categoria']; ?>')">
                                                <i class="fas fa-undo me-1"></i>Restaurar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Categorias Combinadas -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-layer-group me-2"></i>
                            <?php echo "Categorias Combinadas"; ?>
                        </h5>
                        <small>Configurações para quem já tem uma categoria e quer adicionar outra</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $combinadas = array_filter($configuracoes, function($config) {
                                return $config['tipo'] === 'combinada';
                            });
                            foreach ($combinadas as $config): 
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card config-card h-100 border-info">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <span class="badge bg-info me-2"><?php echo htmlspecialchars($config['categoria']); ?></span>
                                            <?php echo htmlspecialchars($config['nome']); ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="horas-info">
                                            <?php if ($config['horas_teoricas'] > 0): ?>
                                            <p class="mb-1">
                                                <i class="fas fa-book text-info me-2"></i>
                                                <strong>Teóricas:</strong> <?php echo $config['horas_teoricas']; ?>h
                                            </p>
                                            <?php endif; ?>
                                            
                                            <p class="mb-1">
                                                <i class="fas fa-car text-info me-2"></i>
                                                <strong>Práticas:</strong> <?php echo $config['horas_praticas_total']; ?>h
                                            </p>
                                        </div>
                                        
                                        <?php if ($config['observacoes']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?php echo htmlspecialchars($config['observacoes']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-info btn-sm" 
                                                    onclick="editarConfiguracao('<?php echo $config['categoria']; ?>')">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    onclick="restaurarConfiguracao('<?php echo $config['categoria']; ?>')">
                                                <i class="fas fa-undo me-1"></i>Restaurar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Adição de Categorias -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-plus-circle me-2"></i>
                            <?php echo "Adição de Categorias"; ?>
                        </h5>
                        <small>Configurações para quem já tem uma categoria e quer adicionar outra</small>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php 
                            $adicao = array_filter($configuracoes, function($config) {
                                return $config['tipo'] === 'adicao';
                            });
                            foreach ($adicao as $config): 
                            ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card config-card h-100 border-warning">
                                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">
                                            <span class="badge bg-warning me-2"><?php echo htmlspecialchars($config['categoria']); ?></span>
                                            <?php echo htmlspecialchars($config['nome']); ?>
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="horas-info">
                                            <?php if ($config['horas_teoricas'] > 0): ?>
                                            <p class="mb-1">
                                                <i class="fas fa-book text-info me-2"></i>
                                                <strong>Teóricas:</strong> <?php echo $config['horas_teoricas']; ?>h
                                            </p>
                                            <?php endif; ?>
                                            
                                            <p class="mb-1">
                                                <i class="fas fa-car text-warning me-2"></i>
                                                <strong>Práticas:</strong> <?php echo $config['horas_praticas_total']; ?>h
                                            </p>
                                        </div>
                                        
                                        <?php if ($config['observacoes']): ?>
                                        <div class="mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-info-circle me-1"></i>
                                                <?php echo htmlspecialchars($config['observacoes']); ?>
                                            </small>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="card-footer">
                                        <div class="btn-group w-100" role="group">
                                            <button type="button" class="btn btn-outline-warning btn-sm" 
                                                    onclick="editarConfiguracao('<?php echo $config['categoria']; ?>')">
                                                <i class="fas fa-edit me-1"></i>Editar
                                            </button>
                                            <button type="button" class="btn btn-outline-secondary btn-sm" 
                                                    onclick="restaurarConfiguracao('<?php echo $config['categoria']; ?>')">
                                                <i class="fas fa-undo me-1"></i>Restaurar
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Instruções -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Como Funciona
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <h6><i class="fas fa-user-plus me-2"></i>Matrícula</h6>
                                <p class="small">Ao cadastrar um aluno com categoria específica, o sistema automaticamente aplica as configurações definidas aqui.</p>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-calendar-check me-2"></i>Agendamento</h6>
                                <p class="small">O sistema impede o agendamento de aulas além do limite configurado para cada categoria.</p>
                            </div>
                            <div class="col-md-4">
                                <h6><i class="fas fa-chart-line me-2"></i>Histórico</h6>
                                <p class="small">O progresso do aluno é calculado baseado nas configurações ativas de cada categoria.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Nova Configuração -->
    <div class="modal fade" id="modalNovaConfiguracao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="salvar">
                    <div class="modal-header">
                        <h5 class="modal-title">Nova Configuração de Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="categoria" class="form-label">Categoria Base *</label>
                                    <select class="form-select" id="categoria" name="categoria" required>
                                        <option value="">Selecione a categoria base</option>
                                        <option value="A">A - Motocicletas</option>
                                        <option value="B">B - Automóveis</option>
                                        <option value="C">C - Veículos de Carga</option>
                                        <option value="D">D - Veículos de Passageiros</option>
                                        <option value="E">E - Combinação de Veículos</option>
                                    </select>
                                    <small class="form-text text-muted">
                                        <i class="fas fa-info-circle me-1"></i>
                                        As combinações (AB, AC, etc.) são geradas automaticamente
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nome" class="form-label">Nome da Categoria *</label>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           placeholder="Ex: Motocicletas" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="tipo" class="form-label">Tipo *</label>
                            <select class="form-select" id="tipo" name="tipo" required>
                                <option value="">Selecione o tipo</option>
                                <option value="primeira_habilitacao">Primeira Habilitação</option>
                                <option value="adicao">Adição de Categoria</option>
                            </select>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="horas_teoricas" class="form-label">Horas Teóricas</label>
                                    <input type="number" class="form-control" id="horas_teoricas" name="horas_teoricas" 
                                           min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="horas_praticas_total" class="form-label">Total Horas Práticas *</label>
                                    <input type="number" class="form-control" id="horas_praticas_total" name="horas_praticas_total" 
                                           min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Configuração</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Editar Configuração -->
    <div class="modal fade" id="modalEditarConfiguracao" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST">
                    <input type="hidden" name="acao" value="salvar">
                    <input type="hidden" id="edit_categoria" name="categoria">
                    <div class="modal-header">
                        <h5 class="modal-title">Editar Configuração de Categoria</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_nome" class="form-label">Nome da Categoria *</label>
                                    <input type="text" class="form-control" id="edit_nome" name="nome" 
                                           placeholder="Ex: Motocicletas" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_tipo" class="form-label">Tipo *</label>
                                    <select class="form-select" id="edit_tipo" name="tipo" required>
                                        <option value="">Selecione o tipo</option>
                                        <option value="primeira_habilitacao">Primeira Habilitação</option>
                                        <option value="adicao">Adição de Categoria</option>
                                        <option value="combinada">Categoria Combinada</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_horas_teoricas" class="form-label">Horas Teóricas</label>
                                    <input type="number" class="form-control" id="edit_horas_teoricas" name="horas_teoricas" 
                                           min="0" value="0">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="edit_horas_praticas_total" class="form-label">Total Horas Práticas *</label>
                                    <input type="number" class="form-control" id="edit_horas_praticas_total" name="horas_praticas_total" 
                                           min="0" required>
                                </div>
                            </div>
                        </div>
                        
                        
                        <div class="mb-3">
                            <label for="edit_observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="edit_observacoes" name="observacoes" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Salvar Alterações</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function editarConfiguracao(categoria) {
        // Buscar dados da configuração via AJAX
        fetch(`api/configuracoes.php?action=get&categoria=${categoria}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const config = data.configuracao;
                    
                    // Verificar se os elementos existem antes de preencher
                    const editCategoria = document.getElementById('edit_categoria');
                    
                    if (!editCategoria) {
                        console.error('Elemento edit_categoria não encontrado');
                        alert('Erro: Elemento do formulário não encontrado');
                        return;
                    }
                    
                    // Preencher formulário com verificações de segurança
                    editCategoria.value = config.categoria || '';
                    
                    // Usar IDs específicos para evitar conflitos
                    const nomeInput = document.getElementById('edit_nome');
                    const tipoSelect = document.getElementById('edit_tipo');
                    const horasTeoricasInput = document.getElementById('edit_horas_teoricas');
                    const horasPraticasTotalInput = document.getElementById('edit_horas_praticas_total');
                    const observacoesTextarea = document.getElementById('edit_observacoes');
                    
                    // Preencher apenas se os elementos existirem
                    if (nomeInput) nomeInput.value = config.nome || '';
                    if (tipoSelect) tipoSelect.value = config.tipo || '';
                    if (horasTeoricasInput) horasTeoricasInput.value = config.horas_teoricas || 0;
                    if (horasPraticasTotalInput) horasPraticasTotalInput.value = config.horas_praticas_total || 0;
                    if (observacoesTextarea) observacoesTextarea.value = config.observacoes || '';
                    
                    const modal = new bootstrap.Modal(document.getElementById('modalEditarConfiguracao'));
                    modal.show();
                } else {
                    alert('Erro ao carregar configuração: ' + (data.message || 'Erro desconhecido'));
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao carregar configuração: ' + error.message);
            });
        }
        
        function restaurarConfiguracao(categoria) {
            if (confirm(`Tem certeza que deseja restaurar a configuração da categoria ${categoria} para os valores padrão?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="acao" value="restaurar">
                    <input type="hidden" name="categoria" value="${categoria}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        // Validação em tempo real
        document.addEventListener('DOMContentLoaded', function() {
            // Validação básica dos campos obrigatórios
            const totalPraticas = document.getElementById('horas_praticas_total');
            if (totalPraticas) {
                totalPraticas.addEventListener('input', function() {
                    if (this.value < 0) {
                        this.value = 0;
                    }
                });
            }
        });
    </script>
</body>
</html>
