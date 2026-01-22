<?php
/**
 * Histórico de Aluno - Versão Melhorada
 * 
 * Esta versão implementa uma estrutura completa para todas as categorias
 * de habilitação conforme as normas do CONTRAN/DETRAN
 */

// Incluir a classe de categorias
require_once '../includes/categorias_habilitacao.php';

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

// Verificar se ID do aluno foi fornecido
$alunoId = null;
if (defined('ADMIN_ROUTING')) {
    $alunoId = $aluno_id ?? null;
} else {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: alunos.php');
        exit;
    }
    $alunoId = (int)$_GET['id'];
}

if (!$alunoId) {
    if (defined('ADMIN_ROUTING')) {
        echo '<div class="alert alert-danger">ID do aluno não fornecido.</div>';
        return;
    } else {
        header('Location: alunos.php');
        exit;
    }
}

// Buscar dados do aluno
if (defined('ADMIN_ROUTING') && isset($aluno)) {
    $alunoData = $aluno;
    $cfcData = $cfc;
} else {
    $alunoData = db()->fetch("
        SELECT a.*, c.nome as cfc_nome, c.cnpj as cfc_cnpj
        FROM alunos a 
        LEFT JOIN cfcs c ON a.cfc_id = c.id 
        WHERE a.id = ?
    ", [$alunoId]);
    
    if (!$alunoData) {
        if (defined('ADMIN_ROUTING')) {
            echo '<div class="alert alert-danger">Aluno não encontrado.</div>';
            return;
        } else {
            header('Location: alunos.php');
            exit;
        }
    }
    
    $cfcData = null;
    if ($alunoData['cfc_id']) {
        $cfcData = db()->fetch("SELECT * FROM cfcs WHERE id = ?", [$alunoData['cfc_id']]);
    }
}

if (!$alunoData) {
    if (defined('ADMIN_ROUTING')) {
        echo '<div class="alert alert-danger">Aluno não encontrado.</div>';
        return;
    } else {
        header('Location: alunos.php');
        exit;
    }
}

// Obter informações da categoria
$categoriaInfo = CategoriasHabilitacao::getCategoria($alunoData['categoria_cnh']);
$isPrimeiraHabilitacao = CategoriasHabilitacao::isPrimeiraHabilitacao($alunoData['categoria_cnh']);

// Buscar histórico de aulas separado por tipo
$aulasTeoricas = [];
$aulasPraticas = [];

if ($isPrimeiraHabilitacao) {
    // Para primeira habilitação, buscar aulas teóricas
    $aulasTeoricas = db()->fetchAll("
        SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome
        FROM aulas a
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE a.aluno_id = ? AND a.tipo_aula = 'teorica'
        ORDER BY a.data_aula DESC, a.hora_inicio DESC
    ", [$alunoId]);
}

// Buscar aulas práticas
$aulasPraticas = db()->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.modelo, v.marca, v.tipo_veiculo
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ? AND a.tipo_aula = 'pratica'
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
", [$alunoId]);

// Calcular estatísticas gerais
$totalAulas = count($aulasTeoricas) + count($aulasPraticas);
$totalTeoricas = count($aulasTeoricas);
$totalPraticas = count($aulasPraticas);

$aulasConcluidas = count(array_filter($aulasPraticas, fn($a) => $a['status'] === 'concluida'));
$aulasCanceladas = count(array_filter($aulasPraticas, fn($a) => $a['status'] === 'cancelada'));
$aulasAgendadas = count(array_filter($aulasPraticas, fn($a) => $a['status'] === 'agendada'));

// Calcular progresso por subcategoria (para categorias mudanca_categorias)
$progressoDetalhado = [];
if ($categoriaInfo && isset($categoriaInfo['pratica_detalhada'])) {
    foreach ($categoriaInfo['pratica_detalhada'] as $subcategoria => $horasNecessarias) {
        $aulasSubcategoria = array_filter($aulasPraticas, function($aula) use ($subcategoria) {
            // Aqui você pode implementar lógica para identificar o tipo de veículo
            // Por exemplo, baseado no tipo_veiculo ou outras características
            return $aula['tipo_veiculo'] === $subcategoria || 
                   ($subcategoria === 'A' && strpos($aula['tipo_veiculo'], 'moto') !== false) ||
                   ($subcategoria === 'B' && strpos($aula['tipo_veiculo'], 'carro') !== false);
        });
        
        $concluidasSubcategoria = count(array_filter($aulasSubcategoria, fn($a) => $a['status'] === 'concluida'));
        $progressoDetalhado[$subcategoria] = [
            'necessarias' => $horasNecessarias,
            'concluidas' => $concluidasSubcategoria,
            'progresso' => min(100, ($concluidasSubcategoria / $horasNecessarias) * 100)
        ];
    }
} else {
    // Para categorias simples
    $totalNecessarias = CategoriasHabilitacao::getTotalHorasPraticas($alunoData['categoria_cnh']);
    $progressoDetalhado[$alunoData['categoria_cnh']] = [
        'necessarias' => $totalNecessarias,
        'concluidas' => $aulasConcluidas,
        'progresso' => min(100, ($aulasConcluidas / $totalNecessarias) * 100)
    ];
}

// Calcular progresso geral
$progressoGeral = CategoriasHabilitacao::calcularProgresso($alunoData['categoria_cnh'], $aulasConcluidas);
$statusProgresso = CategoriasHabilitacao::getStatusProgresso($alunoData['categoria_cnh'], $aulasConcluidas);

// Buscar próximas aulas
$proximasAulas = db()->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.tipo_veiculo
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 5
", [$alunoId]);

// Buscar aulas teóricas concluídas (para primeira habilitação)
$teoricasConcluidas = 0;
if ($isPrimeiraHabilitacao) {
    $teoricasConcluidas = count(array_filter($aulasTeoricas, fn($a) => $a['status'] === 'concluida'));
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Aluno - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/admin.css" rel="stylesheet">
    <link href="assets/css/action-buttons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .progress-category {
            margin-bottom: 15px;
        }
        .category-badge {
            font-size: 0.8em;
            margin-right: 5px;
        }
        .status-badge {
            font-size: 0.9em;
        }
        .card-category {
            border-left: 4px solid;
        }
        .card-category.A { border-left-color: #007bff; }
        .card-category.B { border-left-color: #28a745; }
        .card-category.C { border-left-color: #ffc107; }
        .card-category.D { border-left-color: #dc3545; }
        .card-category.E { border-left-color: #6f42c1; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row bg-primary text-white p-3 mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-history me-2"></i>
                    Histórico do Aluno
                </h1>
            </div>
            <div class="col-auto">
                <a href="alunos.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Informações do Aluno -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-graduate me-2"></i>
                            Informações do Aluno
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nome:</strong> <?php echo htmlspecialchars($alunoData['nome']); ?></p>
                                <p><strong>CPF:</strong> <?php echo htmlspecialchars($alunoData['cpf']); ?></p>
                                <p><strong>Categoria CNH:</strong> 
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($alunoData['categoria_cnh']); ?></span>
                                    <?php if ($categoriaInfo): ?>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($categoriaInfo['nome']); ?></small>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>CFC:</strong> <?php echo htmlspecialchars($cfcData['nome'] ?? 'Não informado'); ?></p>
                                <p><strong>Status:</strong> 
                                    <span class="badge bg-<?php echo $alunoData['status'] === 'ativo' ? 'success' : ($alunoData['status'] === 'concluido' ? 'info' : 'secondary'); ?>">
                                        <?php echo ucfirst(htmlspecialchars($alunoData['status'])); ?>
                                    </span>
                                </p>
                                <p><strong>Data de Nascimento:</strong> 
                                    <?php echo $alunoData['data_nascimento'] ? date('d/m/Y', strtotime($alunoData['data_nascimento'])) : 'Não informado'; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($categoriaInfo && isset($categoriaInfo['requisito'])): ?>
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-2"></i>
                                    <strong>Requisito:</strong> <?php echo htmlspecialchars($categoriaInfo['requisito']); ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Progresso Geral
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $progressoGeral; ?>%" 
                                 aria-valuenow="<?php echo $progressoGeral; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($progressoGeral, 1); ?>%
                            </div>
                        </div>
                        <p class="mb-1">
                            <strong><?php echo $aulasConcluidas; ?></strong> de 
                            <strong><?php echo CategoriasHabilitacao::getTotalHorasPraticas($alunoData['categoria_cnh']); ?></strong> aulas práticas
                        </p>
                        <small class="text-muted">
                            Status: 
                            <span class="badge bg-<?php 
                                echo $statusProgresso === 'concluido' ? 'success' : 
                                    ($statusProgresso === 'avancado' ? 'info' : 
                                    ($statusProgresso === 'intermediario' ? 'warning' : 'secondary')); 
                            ?>">
                                <?php echo ucfirst(str_replace('_', ' ', $statusProgresso)); ?>
                            </span>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso por Subcategoria (para categorias mudanca_categorias) -->
        <?php if (count($progressoDetalhado) > 1): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-layer-group me-2"></i>
                            Progresso por Subcategoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($progressoDetalhado as $subcategoria => $dados): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card card-category <?php echo $subcategoria; ?>">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <span class="badge bg-primary category-badge"><?php echo $subcategoria; ?></span>
                                            <?php 
                                            $subcategoriaInfo = CategoriasHabilitacao::getCategoria($subcategoria);
                                            echo $subcategoriaInfo ? $subcategoriaInfo['nome'] : 'Categoria ' . $subcategoria;
                                            ?>
                                        </h6>
                                        <div class="progress progress-category">
                                            <div class="progress-bar" role="progressbar" 
                                                 style="width: <?php echo $dados['progresso']; ?>%" 
                                                 aria-valuenow="<?php echo $dados['progresso']; ?>" 
                                                 aria-valuemin="0" aria-valuemax="100">
                                                <?php echo round($dados['progresso'], 1); ?>%
                                            </div>
                                        </div>
                                        <small class="text-muted">
                                            <?php echo $dados['concluidas']; ?> de <?php echo $dados['necessarias']; ?> aulas concluídas
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <?php if ($isPrimeiraHabilitacao): ?>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-book fa-2x mb-2"></i>
                        <h4><?php echo $teoricasConcluidas; ?></h4>
                        <p class="mb-0">Aulas Teóricas</p>
                        <small><?php echo $categoriaInfo['teorica']; ?>h obrigatórias</small>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="col-md-<?php echo $isPrimeiraHabilitacao ? '3' : '4'; ?>">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h4><?php echo $totalPraticas; ?></h4>
                        <p class="mb-0">Total Aulas Práticas</p>
                        <small><?php echo CategoriasHabilitacao::getTotalHorasPraticas($alunoData['categoria_cnh']); ?>h obrigatórias</small>
                    </div>
                </div>
            </div>
            <div class="col-md-<?php echo $isPrimeiraHabilitacao ? '3' : '4'; ?>">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?php echo $aulasConcluidas; ?></h4>
                        <p class="mb-0">Aulas Concluídas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-<?php echo $isPrimeiraHabilitacao ? '3' : '4'; ?>">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4><?php echo $aulasAgendadas; ?></h4>
                        <p class="mb-0">Aulas Agendadas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Próximas Aulas -->
        <?php if ($proximasAulas): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-warning text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Próximas Aulas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Tipo</th>
                                        <th>Instrutor</th>
                                        <th>Veículo</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($proximasAulas as $aula): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($aula['hora_inicio'])) . ' - ' . date('H:i', strtotime($aula['hora_fim'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($aula['tipo_aula'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($aula['instrutor_nome']); ?></td>
                                        <td><?php echo htmlspecialchars($aula['placa']); ?></td>
                                        <td>
                                            <span class="badge bg-warning">Agendada</span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Histórico Completo -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list me-2"></i>
                            Histórico Completo de Aulas
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($totalAulas > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tabelaHistorico">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Tipo</th>
                                        <th>Instrutor</th>
                                        <th>Veículo</th>
                                        <th>Status</th>
                                        <th>Observações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Combinar aulas teóricas e práticas
                                    $todasAulas = array_merge($aulasTeoricas, $aulasPraticas);
                                    // Ordenar por data
                                    usort($todasAulas, function($a, $b) {
                                        return strtotime($b['data_aula']) - strtotime($a['data_aula']);
                                    });
                                    
                                    foreach ($todasAulas as $aula): 
                                    ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($aula['hora_inicio'])) . ' - ' . date('H:i', strtotime($aula['hora_fim'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($aula['tipo_aula'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['instrutor_nome']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['credencial'] ?? 'N/A'); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($aula['tipo_aula'] === 'pratica' && $aula['veiculo_id']): ?>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['placa']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['marca'] . ' ' . $aula['modelo']); ?></small>
                                            </div>
                                            <?php else: ?>
                                            <span class="text-muted">Não aplicável</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = [
                                                'agendada' => 'warning',
                                                'em_andamento' => 'info',
                                                'concluida' => 'success',
                                                'cancelada' => 'danger'
                                            ];
                                            $statusText = [
                                                'agendada' => 'Agendada',
                                                'em_andamento' => 'Em Andamento',
                                                'concluida' => 'Concluída',
                                                'cancelada' => 'Cancelada'
                                            ];
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass[$aula['status']] ?? 'secondary'; ?>">
                                                <?php echo $statusText[$aula['status']] ?? ucfirst($aula['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($aula['observacoes']): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;" 
                                                  title="<?php echo htmlspecialchars($aula['observacoes']); ?>">
                                                <?php echo htmlspecialchars($aula['observacoes']); ?>
                                            </span>
                                            <?php else: ?>
                                            <span class="text-muted">Sem observações</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        title="Ver detalhes da aula"
                                                        onclick="verDetalhesAula(<?php echo $aula['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($aula['status'] === 'agendada'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-warning" 
                                                        title="Editar aula"
                                                        onclick="editarAula(<?php echo $aula['id']; ?>)">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" 
                                                        title="Cancelar aula"
                                                        onclick="cancelarAula(<?php echo $aula['id']; ?>)">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhuma aula encontrada</h5>
                            <p class="text-muted">Este aluno ainda não possui aulas registradas no sistema.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalhes da Aula -->
    <div class="modal fade" id="modalDetalhesAula" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalhes da Aula</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modalDetalhesBody">
                    <!-- Conteúdo será carregado via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Cancelamento de Aula -->
    <div class="modal fade" id="modalCancelarAula" tabindex="-1" aria-labelledby="modalCancelarAulaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalCancelarAulaLabel">
                        <i class="fas fa-times-circle me-2 text-danger"></i>Cancelar Aula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="aulaIdCancelar">
                    
                    <div class="mb-3">
                        <label for="motivoCancelamento" class="form-label required">Motivo do Cancelamento:</label>
                        <select class="form-control" id="motivoCancelamento" required>
                            <option value="">Selecione um motivo</option>
                            <option value="aluno_ausente">Aluno ausente</option>
                            <option value="instrutor_indisponivel">Instrutor indisponível</option>
                            <option value="veiculo_quebrado">Veículo quebrado</option>
                            <option value="condicoes_climaticas">Condições climáticas</option>
                            <option value="problema_tecnico">Problema técnico</option>
                            <option value="reagendamento">Reagendamento</option>
                            <option value="outros">Outros</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="observacoesCancelamento" class="form-label">Observações:</label>
                        <textarea class="form-control" id="observacoesCancelamento" rows="3" placeholder="Digite observações sobre o cancelamento..."></textarea>
                    </div>
                    
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Atenção:</strong> Esta ação não pode ser desfeita. A aula será marcada como cancelada.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-danger" onclick="confirmarCancelamento()">
                        <i class="fas fa-times me-1"></i>Confirmar Cancelamento
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Funções para ações
        function verDetalhesAula(aulaId) {
            // Buscar dados da aula
            const aula = <?php echo json_encode($aulas); ?>.find(a => a.id == aulaId);
            
            if (!aula) {
                alert('Aula não encontrada!');
                return;
            }
            
            // Montar conteúdo do modal
            const modalBody = document.getElementById('modalDetalhesBody');
            
            modalBody.innerHTML = `
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-calendar-alt me-2"></i>Informações da Aula
                        </h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Data:</label>
                            <p class="mb-0">${formatarData(aula.data_aula)}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Horário:</label>
                            <p class="mb-0">${aula.hora_inicio} - ${aula.hora_fim}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Tipo de Aula:</label>
                            <p class="mb-0">
                                <span class="badge bg-${aula.tipo_aula === 'teorica' ? 'info' : 'primary'}">
                                    ${aula.tipo_aula.toUpperCase()}
                                </span>
                            </p>
                        </div>
                        ${aula.disciplina ? `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Disciplina:</label>
                            <p class="mb-0">${aula.disciplina}</p>
                        </div>
                        ` : ''}
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status:</label>
                            <p class="mb-0">
                                <span class="badge bg-${getStatusColor(aula.status)}">
                                    ${aula.status.toUpperCase()}
                                </span>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-users me-2"></i>Informações dos Participantes
                        </h6>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Aluno:</label>
                            <p class="mb-0">${aula.aluno_nome || 'N/A'}</p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Instrutor:</label>
                            <p class="mb-0">${aula.instrutor_nome || 'N/A'}</p>
                            ${aula.credencial ? `<small class="text-muted">${aula.credencial}</small>` : ''}
                        </div>
                        ${aula.placa ? `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Veículo:</label>
                            <p class="mb-0">${aula.placa} - ${aula.modelo || ''} ${aula.marca || ''}</p>
                        </div>
                        ` : `
                        <div class="mb-3">
                            <label class="form-label fw-bold">Veículo:</label>
                            <p class="mb-0 text-muted">Não aplicável</p>
                        </div>
                        `}
                    </div>
                </div>
                ${aula.observacoes ? `
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-sticky-note me-2"></i>Observações
                        </h6>
                        <div class="alert alert-light">
                            <p class="mb-0">${aula.observacoes}</p>
                        </div>
                    </div>
                </div>
                ` : ''}
                <hr>
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-info-circle me-2"></i>Informações do Sistema
                        </h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Criado em:</strong> ${formatarDataHora(aula.criado_em)}
                                </small>
                            </div>
                            ${aula.atualizado_em ? `
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <strong>Atualizado em:</strong> ${formatarDataHora(aula.atualizado_em)}
                                </small>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `;
            
            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalDetalhesAula'));
            modal.show();
        }
        
        // Funções auxiliares
        function formatarData(data) {
            if (!data) return 'N/A';
            const date = new Date(data);
            return date.toLocaleDateString('pt-BR');
        }
        
        function formatarDataHora(dataHora) {
            if (!dataHora) return 'N/A';
            const date = new Date(dataHora);
            return date.toLocaleString('pt-BR');
        }
        
        function getStatusColor(status) {
            const colors = {
                'agendada': 'warning',
                'concluida': 'success',
                'cancelada': 'danger',
                'em_andamento': 'info'
            };
            return colors[status] || 'secondary';
        }

        function editarAula(aulaId) {
            // Limpar cache e redirecionar com versão forçada
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(7);
            const version = 'v' + Math.floor(Date.now() / 1000); // Versão baseada em timestamp
            window.location.href = `index.php?page=agendar-aula&action=edit&edit=${aulaId}&t=${timestamp}&r=${random}&v=${version}`;
        }

        function cancelarAula(aulaId) {
            if (confirm('Tem certeza que deseja cancelar esta aula?')) {
                // Mostrar modal de cancelamento
                const modal = new bootstrap.Modal(document.getElementById('modalCancelarAula'));
                document.getElementById('aulaIdCancelar').value = aulaId;
                modal.show();
            }
        }
        
        function confirmarCancelamento() {
            const aulaId = document.getElementById('aulaIdCancelar').value;
            const motivo = document.getElementById('motivoCancelamento').value;
            const observacoes = document.getElementById('observacoesCancelamento').value;
            
            if (!motivo) {
                alert('Por favor, selecione um motivo para o cancelamento.');
                return;
            }
            
            // Preparar dados
            const formData = new FormData();
            formData.append('aula_id', aulaId);
            formData.append('motivo_cancelamento', motivo);
            formData.append('observacoes', observacoes);
            
            // Enviar dados
            fetch('api/cancelar-aula.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Aula cancelada com sucesso!');
                    location.reload(); // Recarregar página para atualizar dados
                } else {
                    alert('Erro ao cancelar aula: ' + data.message);
                }
            })
            .catch(error => {
                alert('Erro ao cancelar aula: ' + error.message);
            });
        }

        // Exportar histórico
        function exportarHistorico() {
            const table = document.getElementById('tabelaHistorico');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            
            let csv = 'Data,Horário,Tipo,Instrutor,Veículo,Status,Observações\n';
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const rowData = Array.from(cells).slice(0, 7).map(cell => {
                    let text = cell.textContent.trim();
                    text = text.replace(/[^\w\s\-\.\/]/g, '');
                    return `"${text}"`;
                });
                csv += rowData.join(',') + '\n';
            });
            
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', `historico_aluno_${<?php echo $alunoId; ?>}_${new Date().toISOString().split('T')[0]}.csv`);
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Adicionar botão de exportação
        document.addEventListener('DOMContentLoaded', function() {
            const header = document.querySelector('.card-header.bg-dark');
            if (header) {
                const exportBtn = document.createElement('button');
                exportBtn.className = 'btn btn-outline-light btn-sm float-end';
                exportBtn.innerHTML = '<i class="fas fa-download me-2"></i>Exportar';
                exportBtn.onclick = exportarHistorico;
                header.appendChild(exportBtn);
            }
        });
    </script>
</body>
</html>
