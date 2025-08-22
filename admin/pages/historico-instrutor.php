<?php
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

// Verificar se ID do instrutor foi fornecido
$instrutorId = null;
if (defined('ADMIN_ROUTING')) {
    // Se estamos no sistema de roteamento, usar variável global
    $instrutorId = $instrutor_id ?? null;
} else {
    // Se acessado diretamente, usar GET
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        header('Location: instrutores.php');
        exit;
    }
    $instrutorId = (int)$_GET['id'];
}

if (!$instrutorId) {
    if (defined('ADMIN_ROUTING')) {
        echo '<div class="alert alert-danger">ID do instrutor não fornecido.</div>';
        return;
    } else {
        header('Location: instrutores.php');
        exit;
    }
}

// Buscar dados do instrutor
if (defined('ADMIN_ROUTING') && isset($instrutor)) {
    // Se estamos no sistema de roteamento e já temos os dados
    $instrutorData = $instrutor;
    $cfcData = $cfc;
} else {
    // Buscar dados do banco
    $instrutorData = db()->fetch("
        SELECT i.*, u.nome, u.email, u.telefone, c.nome as cfc_nome
        FROM instrutores i 
        LEFT JOIN usuarios u ON i.usuario_id = u.id 
        LEFT JOIN cfcs c ON i.cfc_id = c.id 
        WHERE i.id = ?
    ", [$instrutorId]);
    
    if (!$instrutorData) {
        if (defined('ADMIN_ROUTING')) {
            echo '<div class="alert alert-danger">Instrutor não encontrado.</div>';
            return;
        } else {
            header('Location: instrutores.php');
            exit;
        }
    }
    
    $cfcData = null;
    if ($instrutorData['cfc_id']) {
        $cfcData = db()->fetch("SELECT * FROM cfcs WHERE id = ?", [$instrutorData['cfc_id']]);
    }
}

if (!$instrutorData) {
    if (defined('ADMIN_ROUTING')) {
        echo '<div class="alert alert-danger">Instrutor não encontrado.</div>';
        exit;
    } else {
        header('Location: instrutores.php');
        exit;
    }
}

// Buscar histórico de aulas do instrutor
$aulas = db()->fetchAll("
    SELECT a.*, al.nome as aluno_nome, al.cpf as aluno_cpf, v.placa, v.modelo, v.marca
    FROM aulas a
    LEFT JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ?
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
", [$instrutorId]);

// Calcular estatísticas
$totalAulas = count($aulas);
$aulasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida'));
$aulasCanceladas = count(array_filter($aulas, fn($a) => $a['status'] === 'cancelada'));
$aulasAgendadas = count(array_filter($aulas, fn($a) => $a['status'] === 'agendada'));
$aulasTeoricas = count(array_filter($aulas, fn($a) => $a['tipo_aula'] === 'teorica'));
$aulasPraticas = count(array_filter($aulas, fn($a) => $a['tipo_aula'] === 'pratica'));

// Calcular taxa de conclusão
$taxaConclusao = $totalAulas > 0 ? ($aulasConcluidas / $totalAulas) * 100 : 0;

// Buscar estatísticas por mês (últimos 6 meses)
$estatisticasMensais = [];
for ($i = 5; $i >= 0; $i--) {
    $mes = date('Y-m', strtotime("-$i months"));
    $aulasMes = db()->fetchColumn("
        SELECT COUNT(*) FROM aulas 
        WHERE instrutor_id = ? AND DATE_FORMAT(data_aula, '%Y-%m') = ? AND status = 'concluida'
    ", [$instrutorId, $mes]);
    
    $estatisticasMensais[] = [
        'mes' => date('M/Y', strtotime("-$i months")),
        'aulas' => $aulasMes
    ];
}

// Buscar alunos únicos atendidos
$alunosUnicos = db()->fetchAll("
    SELECT DISTINCT al.id, al.nome, al.cpf, al.categoria_cnh
    FROM aulas a
    LEFT JOIN alunos al ON a.aluno_id = al.id
    WHERE a.instrutor_id = ?
    ORDER BY al.nome
", [$instrutorId]);

$totalAlunosUnicos = count($alunosUnicos);

// Buscar próximas aulas
$proximasAulas = db()->fetchAll("
    SELECT a.*, al.nome as aluno_nome, al.cpf as aluno_cpf, v.placa
    FROM aulas a
    LEFT JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 10
", [$instrutorId]);

// Buscar veículos utilizados
$veiculosUtilizados = db()->fetchAll("
    SELECT DISTINCT v.id, v.placa, v.modelo, v.marca, COUNT(a.id) as total_aulas
    FROM aulas a
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? AND v.id IS NOT NULL
    GROUP BY v.id
    ORDER BY total_aulas DESC
", [$instrutorId]);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico do Instrutor - Sistema CFC</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <link href="../assets/css/action-buttons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="container-fluid">
        <!-- Header -->
        <div class="row bg-primary text-white p-3 mb-4">
            <div class="col">
                <h1 class="h3 mb-0">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Histórico do Instrutor
                </h1>
            </div>
            <div class="col-auto">
                <a href="instrutores.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Voltar
                </a>
            </div>
        </div>

        <!-- Informações do Instrutor -->
        <div class="row mb-4">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-user-tie me-2"></i>
                            Informações do Instrutor
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Nome:</strong> <?php echo htmlspecialchars($instrutorData['nome']); ?></p>
                                <p><strong>Credencial:</strong> <?php echo htmlspecialchars($instrutorData['credencial']); ?></p>
                                <p><strong>Categoria de Habilitação:</strong> 
                                    <span class="badge bg-primary"><?php echo htmlspecialchars($instrutorData['categoria_habilitacao']); ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>CFC:</strong> <?php echo htmlspecialchars($cfcData['nome'] ?? 'Não informado'); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($instrutorData['email']); ?></p>
                                <p><strong>Telefone:</strong> <?php echo htmlspecialchars($instrutorData['telefone']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Taxa de Conclusão
                        </h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="progress mb-3" style="height: 25px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $taxaConclusao; ?>%" 
                                 aria-valuenow="<?php echo $taxaConclusao; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($taxaConclusao, 1); ?>%
                            </div>
                        </div>
                        <p class="mb-1"><strong><?php echo $aulasConcluidas; ?></strong> de <strong><?php echo $totalAulas; ?></strong> aulas</p>
                        <small class="text-muted">Taxa de conclusão geral</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas Principais -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h4><?php echo $totalAulas; ?></h4>
                        <p class="mb-0">Total de Aulas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?php echo $aulasConcluidas; ?></h4>
                        <p class="mb-0">Concluídas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4><?php echo $aulasAgendadas; ?></h4>
                        <p class="mb-0">Agendadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h4><?php echo $aulasCanceladas; ?></h4>
                        <p class="mb-0">Canceladas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-book fa-2x mb-2"></i>
                        <h4><?php echo $aulasTeoricas; ?></h4>
                        <p class="mb-0">Teóricas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-car fa-2x mb-2"></i>
                        <h4><?php echo $aulasPraticas; ?></h4>
                        <p class="mb-0">Práticas</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas Adicionais -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x text-primary mb-2"></i>
                        <h4 class="text-primary"><?php echo $totalAlunosUnicos; ?></h4>
                        <p class="mb-0">Alunos Atendidos</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="fas fa-car-side fa-2x text-success mb-2"></i>
                        <h4 class="text-success"><?php echo count($veiculosUtilizados); ?></h4>
                        <p class="mb-0">Veículos Utilizados</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="fas fa-star fa-2x text-warning mb-2"></i>
                        <h4 class="text-warning"><?php echo $totalAulas > 0 ? round($totalAulas / 30, 1) : 0; ?></h4>
                        <p class="mb-0">Aulas/Mês</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-light">
                    <div class="card-body text-center">
                        <i class="fas fa-trophy fa-2x text-danger mb-2"></i>
                        <h4 class="text-danger"><?php echo $aulasConcluidas > 0 ? round(($aulasConcluidas / $totalAulas) * 100, 1) : 0; ?>%</h4>
                        <p class="mb-0">Taxa de Sucesso</p>
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
                            Próximas Aulas (Próximos 10 dias)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Aluno</th>
                                        <th>Tipo</th>
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
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['aluno_nome']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['aluno_cpf']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($aula['tipo_aula'])); ?>
                                            </span>
                                        </td>
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

        <!-- Alunos Atendidos -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-users me-2"></i>
                            Alunos Atendidos
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($alunosUnicos): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Nome</th>
                                        <th>CPF</th>
                                        <th>Categoria CNH</th>
                                        <th>Total de Aulas</th>
                                        <th>Última Aula</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alunosUnicos as $aluno): ?>
                                    <?php
                                    // Buscar estatísticas do aluno com este instrutor
                                    $statsAluno = db()->fetch("
                                        SELECT COUNT(*) as total_aulas, 
                                               MAX(data_aula) as ultima_aula,
                                               COUNT(CASE WHEN status = 'concluida' THEN 1 END) as aulas_concluidas
                                        FROM aulas 
                                        WHERE instrutor_id = ? AND aluno_id = ?
                                    ", [$instrutorId, $aluno['id']]);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($aluno['nome']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($aluno['cpf']); ?></td>
                                        <td>
                                            <span class="badge bg-primary"><?php echo htmlspecialchars($aluno['categoria_cnh']); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $statsAluno['total_aulas']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($statsAluno['ultima_aula']): ?>
                                            <?php echo date('d/m/Y', strtotime($statsAluno['ultima_aula'])); ?>
                                            <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $progresso = $statsAluno['total_aulas'] > 0 ? ($statsAluno['aulas_concluidas'] / $statsAluno['total_aulas']) * 100 : 0;
                                            $statusClass = $progresso >= 80 ? 'success' : ($progresso >= 50 ? 'warning' : 'danger');
                                            ?>
                                            <span class="badge bg-<?php echo $statusClass; ?>">
                                                <?php echo round($progresso, 1); ?>%
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Nenhum aluno encontrado</h5>
                            <p class="text-muted">Este instrutor ainda não possui alunos registrados.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Veículos Utilizados -->
        <?php if ($veiculosUtilizados): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-car me-2"></i>
                            Veículos Utilizados
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Placa</th>
                                        <th>Modelo</th>
                                        <th>Marca</th>
                                        <th>Total de Aulas</th>
                                        <th>Última Utilização</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($veiculosUtilizados as $veiculo): ?>
                                    <?php
                                    $ultimaUtilizacao = db()->fetchColumn("
                                        SELECT MAX(data_aula) FROM aulas 
                                        WHERE instrutor_id = ? AND veiculo_id = ?
                                    ", [$instrutorId, $veiculo['id']]);
                                    ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($veiculo['placa']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($veiculo['modelo']); ?></td>
                                        <td><?php echo htmlspecialchars($veiculo['marca']); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $veiculo['total_aulas']; ?></span>
                                        </td>
                                        <td>
                                            <?php if ($ultimaUtilizacao): ?>
                                            <?php echo date('d/m/Y', strtotime($ultimaUtilizacao)); ?>
                                            <?php else: ?>
                                            <span class="text-muted">Nunca</span>
                                            <?php endif; ?>
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
                        <?php if ($aulas): ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tabelaHistorico">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Horário</th>
                                        <th>Aluno</th>
                                        <th>Tipo</th>
                                        <th>Veículo</th>
                                        <th>Status</th>
                                        <th>Observações</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($aulas as $aula): ?>
                                    <tr>
                                        <td><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></td>
                                        <td><?php echo date('H:i', strtotime($aula['hora_inicio'])) . ' - ' . date('H:i', strtotime($aula['hora_fim'])); ?></td>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($aula['aluno_nome']); ?></strong>
                                                <br>
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['aluno_cpf']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'primary'; ?>">
                                                <?php echo ucfirst(htmlspecialchars($aula['tipo_aula'])); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($aula['veiculo_id']): ?>
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
                            <p class="text-muted">Este instrutor ainda não possui aulas registradas no sistema.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Distribuição de Aulas por Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartStatus" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-bar me-2"></i>
                            Aulas por Mês (Últimos 6 meses)
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartMensal" width="400" height="200"></canvas>
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
        // Dados para os gráficos
        const dadosStatus = {
            labels: ['Concluídas', 'Agendadas', 'Canceladas'],
            datasets: [{
                data: [<?php echo $aulasConcluidas; ?>, <?php echo $aulasAgendadas; ?>, <?php echo $aulasCanceladas; ?>],
                backgroundColor: ['#28a745', '#ffc107', '#dc3545'],
                borderWidth: 2,
                borderColor: '#fff'
            }]
        };

        const dadosMensais = {
            labels: <?php echo json_encode(array_column($estatisticasMensais, 'mes')); ?>,
            datasets: [{
                label: 'Aulas Concluídas',
                data: <?php echo json_encode(array_column($estatisticasMensais, 'aulas')); ?>,
                backgroundColor: 'rgba(40, 167, 69, 0.2)',
                borderColor: '#28a745',
                borderWidth: 2,
                fill: true
            }]
        };

        // Inicializar gráficos
        document.addEventListener('DOMContentLoaded', function() {
            // Gráfico de pizza - Status das aulas
            const ctxStatus = document.getElementById('chartStatus').getContext('2d');
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: dadosStatus,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    }
                }
            });

            // Gráfico de linha - Aulas por mês
            const ctxMensal = document.getElementById('chartMensal').getContext('2d');
            new Chart(ctxMensal, {
                type: 'line',
                data: dadosMensais,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    },
                    plugins: {
                        legend: {
                            display: false
                        }
                    }
                }
            });
        });

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
                    <p class="text-muted">Aula realizada com sucesso. Aluno apresentou boa evolução.</p>
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
            
            let csv = 'Data,Horário,Aluno,Tipo,Veículo,Status,Observações\n';
            
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
            link.setAttribute('download', `historico_instrutor_${<?php echo $instrutorId; ?>}_${new Date().toISOString().split('T')[0]}.csv`);
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
