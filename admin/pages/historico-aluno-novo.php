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

// Verificar se ID do aluno foi fornecido
$alunoId = null;
if (defined('ADMIN_ROUTING')) {
    // Se estamos no sistema de roteamento, usar variável global
    $alunoId = $aluno_id ?? null;
} else {
    // Se acessado diretamente, usar GET
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
    // Se estamos no sistema de roteamento e já temos os dados
    $alunoData = $aluno;
    $cfcData = $cfc;
} else {
    // Buscar dados do banco
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

// Buscar histórico de aulas
$aulas = db()->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.modelo, v.marca
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
", [$alunoId]);

// Calcular estatísticas
$totalAulas = count($aulas);
$aulasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida'));
$aulasCanceladas = count(array_filter($aulas, fn($a) => $a['status'] === 'cancelada'));
$aulasAgendadas = count(array_filter($aulas, fn($a) => $a['status'] === 'agendada'));

// Incluir classe de configurações
require_once 'includes/configuracoes_categorias.php';

// Calcular progresso baseado na configuração da categoria
$configManager = ConfiguracoesCategorias::getInstance();
$categoriaAluno = $alunoData['categoria_cnh'];

// Verificar se é uma categoria combinada (ex: AB, AC, etc.)
$configuracoesCategorias = $configManager->getConfiguracoesParaCategoriaCombinada($categoriaAluno);
$ehCategoriaCombinada = count($configuracoesCategorias) > 1;

if ($ehCategoriaCombinada) {
    // Para categorias combinadas, calcular progresso separadamente para cada categoria
    $aulasNecessarias = 0;
    $aulasTeoricasNecessarias = 0;
    $progressoDetalhado = [];
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        $aulasNecessarias += $config['horas_praticas_total'];
        $aulasTeoricasNecessarias += $config['horas_teoricas'];
        
        $progressoDetalhado[$categoria] = [
            'config' => $config,
            'teoricas' => [
                'concluidas' => 0,
                'necessarias' => $config['horas_teoricas'],
                'percentual' => 0
            ],
            'praticas_moto' => [
                'concluidas' => 0,
                'necessarias' => $config['horas_praticas_moto'],
                'percentual' => 0
            ],
            'praticas_carro' => [
                'concluidas' => 0,
                'necessarias' => $config['horas_praticas_carro'],
                'percentual' => 0
            ],
            'praticas_carga' => [
                'concluidas' => 0,
                'necessarias' => $config['horas_praticas_carga'],
                'percentual' => 0
            ],
            'praticas_passageiros' => [
                'concluidas' => 0,
                'necessarias' => $config['horas_praticas_passageiros'],
                'percentual' => 0
            ],
            'praticas_combinacao' => [
                'concluidas' => 0,
                'necessarias' => $config['horas_praticas_combinacao'],
                'percentual' => 0
            ]
        ];
    }
    
    // Contar aulas concluídas por tipo para categorias combinadas
    foreach ($aulas as $aula) {
        if ($aula['status'] === 'concluida') {
            if ($aula['tipo_aula'] === 'teorica') {
                // Para teóricas, distribuir entre todas as categorias
                foreach ($progressoDetalhado as $categoria => $dados) {
                    $progressoDetalhado[$categoria]['teoricas']['concluidas']++;
                }
            } elseif ($aula['tipo_aula'] === 'pratica') {
                $tipoVeiculo = $aula['tipo_veiculo'] ?? 'carro';
                // Mapear tipo de veículo para categoria
                $categoriaVeiculo = '';
                switch ($tipoVeiculo) {
                    case 'moto':
                        $categoriaVeiculo = 'A';
                        break;
                    case 'carro':
                        $categoriaVeiculo = 'B';
                        break;
                    case 'carga':
                        $categoriaVeiculo = 'C';
                        break;
                    case 'passageiros':
                        $categoriaVeiculo = 'D';
                        break;
                    case 'combinacao':
                        $categoriaVeiculo = 'E';
                        break;
                }
                
                // Adicionar à categoria específica se existir
                if (isset($progressoDetalhado[$categoriaVeiculo])) {
                    $campoPraticas = "praticas_{$tipoVeiculo}";
                    if (isset($progressoDetalhado[$categoriaVeiculo][$campoPraticas])) {
                        $progressoDetalhado[$categoriaVeiculo][$campoPraticas]['concluidas']++;
                    }
                }
            }
        }
    }
    
    // Calcular percentuais para cada categoria
    foreach ($progressoDetalhado as $categoria => $dados) {
        foreach ($dados as $tipo => $info) {
            if ($tipo !== 'config' && $info['necessarias'] > 0) {
                $progressoDetalhado[$categoria][$tipo]['percentual'] = min(100, ($info['concluidas'] / $info['necessarias']) * 100);
            }
        }
    }
} else {
    // Para categoria única, usar configuração direta
    $configuracaoCategoria = $configManager->getConfiguracaoByCategoria($categoriaAluno);
    
    if ($configuracaoCategoria) {
        $aulasNecessarias = $configuracaoCategoria['horas_praticas_total'];
        $aulasTeoricasNecessarias = $configuracaoCategoria['horas_teoricas'];
        
        $progressoDetalhado = [
            'teoricas' => [
                'concluidas' => 0,
                'necessarias' => $aulasTeoricasNecessarias,
                'percentual' => 0
            ],
            'praticas_moto' => [
                'concluidas' => 0,
                'necessarias' => $configuracaoCategoria['horas_praticas_moto'],
                'percentual' => 0
            ],
            'praticas_carro' => [
                'concluidas' => 0,
                'necessarias' => $configuracaoCategoria['horas_praticas_carro'],
                'percentual' => 0
            ],
            'praticas_carga' => [
                'concluidas' => 0,
                'necessarias' => $configuracaoCategoria['horas_praticas_carga'],
                'percentual' => 0
            ],
            'praticas_passageiros' => [
                'concluidas' => 0,
                'necessarias' => $configuracaoCategoria['horas_praticas_passageiros'],
                'percentual' => 0
            ],
            'praticas_combinacao' => [
                'concluidas' => 0,
                'necessarias' => $configuracaoCategoria['horas_praticas_combinacao'],
                'percentual' => 0
            ]
        ];
        
        // Contar aulas concluídas por tipo
        foreach ($aulas as $aula) {
            if ($aula['status'] === 'concluida') {
                if ($aula['tipo_aula'] === 'teorica') {
                    $progressoDetalhado['teoricas']['concluidas']++;
                } elseif ($aula['tipo_aula'] === 'pratica') {
                    $tipoVeiculo = $aula['tipo_veiculo'] ?? 'carro';
                    if (isset($progressoDetalhado["praticas_{$tipoVeiculo}"])) {
                        $progressoDetalhado["praticas_{$tipoVeiculo}"]['concluidas']++;
                    }
                }
            }
        }
        
        // Calcular percentuais
        foreach ($progressoDetalhado as $tipo => $dados) {
            if ($dados['necessarias'] > 0) {
                $progressoDetalhado[$tipo]['percentual'] = min(100, ($dados['concluidas'] / $dados['necessarias']) * 100);
            }
        }
    } else {
        // Fallback para valores padrão se não encontrar configuração
        $aulasNecessarias = 25;
        $aulasTeoricasNecessarias = 45;
        $progressoDetalhado = null;
    }
}

$progressoPercentual = min(100, ($aulasConcluidas / $aulasNecessarias) * 100);

// Buscar última aula
$ultimaAula = null;
if ($aulas) {
    $ultimaAula = $aulas[0];
}

// Buscar próximas aulas
$proximasAulas = db()->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ? AND a.data_aula >= CURDATE() AND a.status = 'agendada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 5
", [$alunoId]);
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
            <div class="col-md-6">
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
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            Configuração da Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($ehCategoriaCombinada): ?>
                        <!-- Exibição para categorias combinadas -->
                        <div class="text-center mb-3">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-layer-group me-2"></i>
                                Categoria Combinada: <?php echo htmlspecialchars($categoriaAluno); ?>
                            </h6>
                            <span class="badge bg-warning text-dark fs-6">
                                <?php echo htmlspecialchars($categoriaAluno); ?>
                            </span>
                        </div>
                        
                        <?php foreach ($configuracoesCategorias as $categoria => $config): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-certificate me-1"></i>
                                Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
                            </h6>
                            
                            <div class="row text-center">
                                <div class="col-6">
                                    <div class="border-end">
                                        <h5 class="text-info mb-1">
                                            <i class="fas fa-book me-1"></i>
                                            <?php echo $config['horas_teoricas']; ?>h
                                        </h5>
                                        <small class="text-muted">Teóricas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-success mb-1">
                                        <i class="fas fa-car me-1"></i>
                                        <?php echo $config['horas_praticas_total']; ?>h
                                    </h5>
                                    <small class="text-muted">Práticas</small>
                                </div>
                            </div>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>Detalhamento:</strong><br>
                                    <?php if ($config['horas_praticas_moto'] > 0): ?>
                                        🏍️ Motocicletas: <?php echo $config['horas_praticas_moto']; ?>h<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_carro'] > 0): ?>
                                        🚗 Automóveis: <?php echo $config['horas_praticas_carro']; ?>h<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_carga'] > 0): ?>
                                        🚛 Carga: <?php echo $config['horas_praticas_carga']; ?>h<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_passageiros'] > 0): ?>
                                        🚌 Passageiros: <?php echo $config['horas_praticas_passageiros']; ?>h<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_combinacao'] > 0): ?>
                                        🚛+🚗 Combinação: <?php echo $config['horas_praticas_combinacao']; ?>h
                                    <?php endif; ?>
                                </small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php elseif ($configuracaoCategoria): ?>
                        <!-- Exibição para categoria única -->
                        <div class="text-center mb-3">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-layer-group me-2"></i>
                                <?php echo htmlspecialchars($configuracaoCategoria['nome']); ?>
                            </h6>
                            <span class="badge bg-warning text-dark fs-6">
                                Categoria <?php echo htmlspecialchars($alunoData['categoria_cnh']); ?>
                            </span>
                        </div>
                        
                        <div class="row text-center">
                            <div class="col-6">
                                <div class="border-end">
                                    <h5 class="text-info mb-1">
                                        <i class="fas fa-book me-1"></i>
                                        <?php echo $configuracaoCategoria['horas_teoricas']; ?>h
                                    </h5>
                                    <small class="text-muted">Teóricas</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success mb-1">
                                    <i class="fas fa-car me-1"></i>
                                    <?php echo $configuracaoCategoria['horas_praticas_total']; ?>h
                                </h5>
                                <small class="text-muted">Práticas</small>
                            </div>
                        </div>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Detalhamento Prático:</strong><br>
                                <?php if ($configuracaoCategoria['horas_praticas_moto'] > 0): ?>
                                    🏍️ Motocicletas: <?php echo $configuracaoCategoria['horas_praticas_moto']; ?>h<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_carro'] > 0): ?>
                                    🚗 Automóveis: <?php echo $configuracaoCategoria['horas_praticas_carro']; ?>h<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_carga'] > 0): ?>
                                    🚛 Carga: <?php echo $configuracaoCategoria['horas_praticas_carga']; ?>h<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_passageiros'] > 0): ?>
                                    🚌 Passageiros: <?php echo $configuracaoCategoria['horas_praticas_passageiros']; ?>h<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_combinacao'] > 0): ?>
                                    🚛+🚗 Combinação: <?php echo $configuracaoCategoria['horas_praticas_combinacao']; ?>h
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php else: ?>
                        <div class="text-center">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                            <p class="text-muted mb-0">Configuração não encontrada</p>
                            <small class="text-muted">Categoria: <?php echo htmlspecialchars($alunoData['categoria_cnh']); ?></small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progresso Detalhado -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-line me-2"></i>
                            Progresso Detalhado - <?php echo $ehCategoriaCombinada ? $categoriaAluno : ($configuracaoCategoria['nome'] ?? $alunoData['categoria_cnh']); ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($ehCategoriaCombinada): ?>
                        <!-- Progresso para categorias combinadas -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $progressoPercentual; ?>%" 
                                         aria-valuenow="<?php echo $progressoPercentual; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($progressoPercentual, 1); ?>%
                                    </div>
                                </div>
                                <p class="mb-1"><strong><?php echo $aulasConcluidas; ?></strong> de <strong><?php echo $aulasNecessarias; ?></strong> aulas práticas</p>
                                <small class="text-muted">Categoria Combinada: <?php echo $categoriaAluno; ?></small>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="text-success mb-0"><?php echo round($progressoPercentual, 1); ?>%</h4>
                                <small class="text-muted">Concluído</small>
                            </div>
                        </div>
                        
                        <!-- Progresso separado por categoria -->
                        <?php foreach ($configuracoesCategorias as $categoria => $config): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-certificate me-2"></i>
                                Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
                            </h6>
                            
                            <!-- Teóricas -->
                            <?php if ($config['horas_teoricas'] > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-book text-info me-2"></i>
                                        Aulas Teóricas
                                    </span>
                                    <span class="badge bg-info">
                                        <?php echo $progressoDetalhado[$categoria]['teoricas']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['teoricas']['necessarias']; ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo $progressoDetalhado[$categoria]['teoricas']['percentual']; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Necessário: <?php echo $config['horas_teoricas']; ?>h teóricas
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Práticas -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-car text-success me-2"></i>
                                        Aulas Práticas
                                    </span>
                                    <span class="badge bg-success">
                                        <?php 
                                        $totalPraticasConcluidas = 0;
                                        $totalPraticasNecessarias = 0;
                                        foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
                                            $totalPraticasConcluidas += $progressoDetalhado[$categoria][$tipo]['concluidas'];
                                            $totalPraticasNecessarias += $progressoDetalhado[$categoria][$tipo]['necessarias'];
                                        }
                                        echo $totalPraticasConcluidas . '/' . $totalPraticasNecessarias;
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $totalPraticasNecessarias > 0 ? min(100, ($totalPraticasConcluidas / $totalPraticasNecessarias) * 100) : 0; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Total necessário: <?php echo $config['horas_praticas_total']; ?>h práticas
                                </small>
                            </div>
                            
                            <!-- Detalhamento por tipo de veículo -->
                            <div class="row">
                                <?php if ($config['horas_praticas_moto'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-motorcycle text-warning me-2"></i>
                                            <strong>Motocicletas</strong>
                                        </span>
                                        <span class="badge bg-warning">
                                            <?php echo $progressoDetalhado[$categoria]['praticas_moto']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['praticas_moto']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado[$categoria]['praticas_moto']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $config['horas_praticas_moto']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($config['horas_praticas_carro'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-car text-primary me-2"></i>
                                            <strong>Automóveis</strong>
                                        </span>
                                        <span class="badge bg-primary">
                                            <?php echo $progressoDetalhado[$categoria]['praticas_carro']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['praticas_carro']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-primary" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado[$categoria]['praticas_carro']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $config['horas_praticas_carro']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($config['horas_praticas_carga'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-truck text-secondary me-2"></i>
                                            <strong>Carga</strong>
                                        </span>
                                        <span class="badge bg-secondary">
                                            <?php echo $progressoDetalhado[$categoria]['praticas_carga']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['praticas_carga']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-secondary" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado[$categoria]['praticas_carga']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $config['horas_praticas_carga']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($config['horas_praticas_passageiros'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-bus text-info me-2"></i>
                                            <strong>Passageiros</strong>
                                        </span>
                                        <span class="badge bg-info">
                                            <?php echo $progressoDetalhado[$categoria]['praticas_passageiros']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['praticas_passageiros']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado[$categoria]['praticas_passageiros']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $config['horas_praticas_passageiros']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($config['horas_praticas_combinacao'] > 0): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-trailer text-dark me-2"></i>
                                            <strong>Combinação</strong>
                                        </span>
                                        <span class="badge bg-dark">
                                            <?php echo $progressoDetalhado[$categoria]['praticas_combinacao']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['praticas_combinacao']['necessarias']; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 6px;">
                                        <div class="progress-bar bg-dark" role="progressbar" 
                                             style="width: <?php echo $progressoDetalhado[$categoria]['praticas_combinacao']['percentual']; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted"><?php echo $config['horas_praticas_combinacao']; ?>h necessárias</small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                        
                        <?php else: ?>
                        <!-- Progresso para categoria única -->
                        <div class="row">
                            <div class="col-md-8">
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $progressoPercentual; ?>%" 
                                         aria-valuenow="<?php echo $progressoPercentual; ?>" 
                                         aria-valuemin="0" aria-valuemax="100">
                                        <?php echo round($progressoPercentual, 1); ?>%
                                    </div>
                                </div>
                                <p class="mb-1"><strong><?php echo $aulasConcluidas; ?></strong> de <strong><?php echo $aulasNecessarias; ?></strong> aulas práticas</p>
                                <small class="text-muted">Configurado: <?php echo $configuracaoCategoria['nome'] ?? 'Padrão'; ?></small>
                            </div>
                            <div class="col-md-4 text-center">
                                <h4 class="text-success mb-0"><?php echo round($progressoPercentual, 1); ?>%</h4>
                                <small class="text-muted">Concluído</small>
                            </div>
                        </div>
                        
                        <?php if ($configuracaoCategoria): ?>
                        <div class="mt-3">
                            <h6 class="text-success mb-3">
                                <i class="fas fa-cogs me-2"></i>
                                Configuração da Categoria: <?php echo htmlspecialchars($configuracaoCategoria['nome']); ?>
                            </h6>
                            
                            <!-- Configuração Teórica -->
                            <?php if ($configuracaoCategoria['horas_teoricas'] > 0): ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-book text-info me-2"></i>
                                        Aulas Teóricas
                                    </span>
                                    <span class="badge bg-info">
                                        <?php echo $progressoDetalhado['teoricas']['concluidas']; ?>/<?php echo $progressoDetalhado['teoricas']['necessarias']; ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-info" role="progressbar" 
                                         style="width: <?php echo $progressoDetalhado['teoricas']['percentual']; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Necessário: <?php echo $configuracaoCategoria['horas_teoricas']; ?>h teóricas
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Configuração Prática -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-car text-success me-2"></i>
                                        Aulas Práticas
                                    </span>
                                    <span class="badge bg-success">
                                        <?php echo $aulasConcluidas; ?>/<?php echo $aulasNecessarias; ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $progressoPercentual; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Total necessário: <?php echo $configuracaoCategoria['horas_praticas_total']; ?>h práticas
                                </small>
                            </div>
                            
                            <!-- Detalhamento por Tipo de Veículo -->
                            <div class="mt-3">
                                <h6 class="text-dark mb-2">
                                    <i class="fas fa-list me-2"></i>
                                    Detalhamento por Tipo de Veículo
                                </h6>
                                
                                <div class="row">
                                    <?php if ($configuracaoCategoria['horas_praticas_moto'] > 0): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-motorcycle text-warning me-2"></i>
                                                <strong>Motocicletas</strong>
                                            </span>
                                            <span class="badge bg-warning">
                                                <?php echo $progressoDetalhado['praticas_moto']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_moto']['necessarias']; ?>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-warning" role="progressbar" 
                                                 style="width: <?php echo $progressoDetalhado['praticas_moto']['percentual']; ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_moto']; ?>h necessárias</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($configuracaoCategoria['horas_praticas_carro'] > 0): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-car text-primary me-2"></i>
                                                <strong>Automóveis</strong>
                                            </span>
                                            <span class="badge bg-primary">
                                                <?php echo $progressoDetalhado['praticas_carro']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_carro']['necessarias']; ?>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-primary" role="progressbar" 
                                                 style="width: <?php echo $progressoDetalhado['praticas_carro']['percentual']; ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_carro']; ?>h necessárias</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($configuracaoCategoria['horas_praticas_carga'] > 0): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-truck text-secondary me-2"></i>
                                                <strong>Veículos de Carga</strong>
                                            </span>
                                            <span class="badge bg-secondary">
                                                <?php echo $progressoDetalhado['praticas_carga']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_carga']['necessarias']; ?>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-secondary" role="progressbar" 
                                                 style="width: <?php echo $progressoDetalhado['praticas_carga']['percentual']; ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_carga']; ?>h necessárias</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($configuracaoCategoria['horas_praticas_passageiros'] > 0): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-bus text-info me-2"></i>
                                                <strong>Veículos de Passageiros</strong>
                                            </span>
                                            <span class="badge bg-info">
                                                <?php echo $progressoDetalhado['praticas_passageiros']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_passageiros']['necessarias']; ?>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-info" role="progressbar" 
                                                 style="width: <?php echo $progressoDetalhado['praticas_passageiros']['percentual']; ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_passageiros']; ?>h necessárias</small>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($configuracaoCategoria['horas_praticas_combinacao'] > 0): ?>
                                    <div class="col-md-6 mb-2">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span>
                                                <i class="fas fa-trailer text-dark me-2"></i>
                                                <strong>Combinação de Veículos</strong>
                                            </span>
                                            <span class="badge bg-dark">
                                                <?php echo $progressoDetalhado['praticas_combinacao']['concluidas']; ?>/<?php echo $progressoDetalhado['praticas_combinacao']['necessarias']; ?>
                                            </span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-dark" role="progressbar" 
                                                 style="width: <?php echo $progressoDetalhado['praticas_combinacao']['percentual']; ?>%">
                                            </div>
                                        </div>
                                        <small class="text-muted"><?php echo $configuracaoCategoria['horas_praticas_combinacao']; ?>h necessárias</small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Observações da Configuração -->
                            <?php if ($configuracaoCategoria['observacoes']): ?>
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Observações:</strong> <?php echo htmlspecialchars($configuracaoCategoria['observacoes']); ?>
                                </small>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="mt-3">
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Configuração não encontrada</strong><br>
                                <small>Não foi possível encontrar a configuração para a categoria <?php echo htmlspecialchars($alunoData['categoria_cnh']); ?>. 
                                Usando valores padrão.</small>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Estatísticas -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h4><?php echo $totalAulas; ?></h4>
                        <p class="mb-0">Total de Aulas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?php echo $aulasConcluidas; ?></h4>
                        <p class="mb-0">Aulas Concluídas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4><?php echo $aulasAgendadas; ?></h4>
                        <p class="mb-0">Aulas Agendadas</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-danger text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-times-circle fa-2x mb-2"></i>
                        <h4><?php echo $aulasCanceladas; ?></h4>
                        <p class="mb-0">Aulas Canceladas</p>
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
                        <?php if ($aulas): ?>
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
                                    <?php foreach ($aulas as $aula): ?>
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
                            <p class="text-muted">Este aluno ainda não possui aulas registradas no sistema.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Progresso -->
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
                            Progresso Mensal
                        </h5>
                    </div>
                    <div class="card-body">
                        <canvas id="chartProgresso" width="400" height="200"></canvas>
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

        const dadosProgresso = {
            labels: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho'],
            datasets: [{
                label: 'Aulas Concluídas',
                data: [5, 8, 12, 15, 18, <?php echo $aulasConcluidas; ?>],
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

            // Gráfico de linha - Progresso mensal
            const ctxProgresso = document.getElementById('chartProgresso').getContext('2d');
            new Chart(ctxProgresso, {
                type: 'line',
                data: dadosProgresso,
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 5
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
            // Implementar modal com detalhes da aula
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
            // Redirecionar para página de edição
            window.location.href = `index.php?page=agendar-aula&action=edit&edit=${aulaId}`;
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
                    // Remover badges e ícones
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
</body>
</html>
