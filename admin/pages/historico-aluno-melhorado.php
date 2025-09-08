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
        SELECT a.*, i.credencial, u.nome as instrutor_nome
        FROM aulas a
        LEFT JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN usuarios u ON i.usuario_id = u.id
        WHERE a.aluno_id = ? AND a.tipo_aula = 'teorica'
        ORDER BY a.data_aula DESC, a.hora_inicio DESC
    ", [$alunoId]);
}

// Buscar aulas práticas
$aulasPraticas = db()->fetchAll("
    SELECT a.*, i.credencial, u.nome as instrutor_nome, v.placa, v.modelo, v.marca, v.tipo_veiculo
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

// Calcular progresso por subcategoria (para categorias combinadas)
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
    SELECT a.*, i.credencial, u.nome as instrutor_nome, v.placa, v.tipo_veiculo
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
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/action-buttons.css" rel="stylesheet">
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

        <!-- Progresso por Subcategoria (para categorias combinadas) -->
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
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['credencial']); ?></small>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Funções para ações
        function verDetalhesAula(aulaId) {
            const modal = new bootstrap.Modal(document.getElementById('modalDetalhesAula'));
            document.getElementById('modalDetalhesBody').innerHTML = `
                <div class="text-center">
                    <div class="spinner-border" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando detalhes da aula...</p>
                </div>
            `;
            modal.show();
            
            // Simular carregamento dos dados
            setTimeout(() => {
                document.getElementById('modalDetalhesBody').innerHTML = `
                    <div class="row">
                        <div class="col-md-6">
                            <p><strong>ID da Aula:</strong> ${aulaId}</p>
                            <p><strong>Status:</strong> <span class="badge bg-success">Concluída</span></p>
                            <p><strong>Tipo:</strong> Aula Prática</p>
                        </div>
                        <div class="col-md-6">
                            <p><strong>Data:</strong> 15/06/2024</p>
                            <p><strong>Horário:</strong> 14:00 - 14:50</p>
                            <p><strong>Duração:</strong> 50 minutos</p>
                        </div>
                    </div>
                    <hr>
                    <p><strong>Observações:</strong></p>
                    <p class="text-muted">Aluno apresentou boa evolução na direção. Necessita mais prática em balizas.</p>
                `;
            }, 1000);
        }

        function editarAula(aulaId) {
            window.location.href = `agendar-aula.php?edit=${aulaId}`;
        }

        function cancelarAula(aulaId) {
            if (confirm('Tem certeza que deseja cancelar esta aula?')) {
                alert('Funcionalidade de cancelamento será implementada em breve!');
            }
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
