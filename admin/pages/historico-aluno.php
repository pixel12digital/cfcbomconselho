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
    SELECT a.*, i.credencial, u.nome as instrutor_nome, v.placa, v.modelo, v.marca
    FROM aulas a
    LEFT JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN usuarios u ON i.usuario_id = u.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.aluno_id = ?
    ORDER BY a.data_aula DESC, a.hora_inicio DESC
", [$alunoId]);

// Calcular estatísticas gerais
$totalAulas = count($aulas);
$aulasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida'));
$aulasCanceladas = count(array_filter($aulas, fn($a) => $a['status'] === 'cancelada'));
$aulasAgendadas = count(array_filter($aulas, fn($a) => $a['status'] === 'agendada'));

// Calcular estatísticas por tipo de aula
$aulasTeoricasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'teorica'));
$aulasPraticasConcluidas = count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica'));

// Calcular estatísticas por categoria de veículo (para aulas práticas)
$aulasPraticasPorTipo = [
    'moto' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'moto')),
    'carro' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'carro')),
    'carga' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'carga')),
    'passageiros' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'passageiros')),
    'combinacao' => count(array_filter($aulas, fn($a) => $a['status'] === 'concluida' && $a['tipo_aula'] === 'pratica' && $a['tipo_veiculo'] === 'combinacao'))
];

// Incluir classe de configurações
require_once __DIR__ . '/../includes/configuracoes_categorias.php';

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
        
        // Calcular aulas concluídas por tipo para esta categoria específica
        $teoricasConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'teorica' && 
                   $a['categoria_veiculo'] === $categoria;
        }));
        
        $praticasMotoConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'moto';
        }));
        
        $praticasCarroConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'carro';
        }));
        
        $praticasCargaConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'carga';
        }));
        
        $praticasPassageirosConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'passageiros';
        }));
        
        $praticasCombinacaoConcluidas = count(array_filter($aulas, function($a) use ($categoria) {
            return $a['status'] === 'concluida' && 
                   $a['tipo_aula'] === 'pratica' && 
                   $a['categoria_veiculo'] === $categoria &&
                   $a['tipo_veiculo'] === 'combinacao';
        }));

        $progressoDetalhado[$categoria] = [
            'config' => $config,
            'teoricas' => [
                'concluidas' => $teoricasConcluidas,
                'necessarias' => $config['horas_teoricas'],
                'percentual' => $config['horas_teoricas'] > 0 ? min(100, ($teoricasConcluidas / $config['horas_teoricas']) * 100) : 0
            ],
            'praticas_moto' => [
                'concluidas' => $praticasMotoConcluidas,
                'necessarias' => $config['horas_praticas_moto'],
                'percentual' => $config['horas_praticas_moto'] > 0 ? min(100, ($praticasMotoConcluidas / $config['horas_praticas_moto']) * 100) : 0
            ],
            'praticas_carro' => [
                'concluidas' => $praticasCarroConcluidas,
                'necessarias' => $config['horas_praticas_carro'],
                'percentual' => $config['horas_praticas_carro'] > 0 ? min(100, ($praticasCarroConcluidas / $config['horas_praticas_carro']) * 100) : 0
            ],
            'praticas_carga' => [
                'concluidas' => $praticasCargaConcluidas,
                'necessarias' => $config['horas_praticas_carga'],
                'percentual' => $config['horas_praticas_carga'] > 0 ? min(100, ($praticasCargaConcluidas / $config['horas_praticas_carga']) * 100) : 0
            ],
            'praticas_passageiros' => [
                'concluidas' => $praticasPassageirosConcluidas,
                'necessarias' => $config['horas_praticas_passageiros'],
                'percentual' => $config['horas_praticas_passageiros'] > 0 ? min(100, ($praticasPassageirosConcluidas / $config['horas_praticas_passageiros']) * 100) : 0
            ],
            'praticas_combinacao' => [
                'concluidas' => $praticasCombinacaoConcluidas,
                'necessarias' => $config['horas_praticas_combinacao'],
                'percentual' => $config['horas_praticas_combinacao'] > 0 ? min(100, ($praticasCombinacaoConcluidas / $config['horas_praticas_combinacao']) * 100) : 0
            ]
        ];
    }
    
    // Contar aulas concluídas por tipo para categorias combinadas
    foreach ($aulas as $aula) {
        if ($aula['status'] === 'concluida') {
            if ($aula['tipo_aula'] === 'teorica') {
                // Para teóricas, distribuir entre todas as categorias
                foreach ($progressoDetalhado as $categoria => $dados) {
                    if (isset($progressoDetalhado[$categoria]['teoricas'])) {
                        $progressoDetalhado[$categoria]['teoricas']['concluidas']++;
                    }
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
                'concluidas' => $aulasTeoricasConcluidas,
                'necessarias' => $aulasTeoricasNecessarias,
                'percentual' => $aulasTeoricasNecessarias > 0 ? min(100, ($aulasTeoricasConcluidas / $aulasTeoricasNecessarias) * 100) : 0
            ],
            'praticas_moto' => [
                'concluidas' => $aulasPraticasPorTipo['moto'],
                'necessarias' => $configuracaoCategoria['horas_praticas_moto'],
                'percentual' => $configuracaoCategoria['horas_praticas_moto'] > 0 ? min(100, ($aulasPraticasPorTipo['moto'] / $configuracaoCategoria['horas_praticas_moto']) * 100) : 0
            ],
            'praticas_carro' => [
                'concluidas' => $aulasPraticasPorTipo['carro'],
                'necessarias' => $configuracaoCategoria['horas_praticas_carro'],
                'percentual' => $configuracaoCategoria['horas_praticas_carro'] > 0 ? min(100, ($aulasPraticasPorTipo['carro'] / $configuracaoCategoria['horas_praticas_carro']) * 100) : 0
            ],
            'praticas_carga' => [
                'concluidas' => $aulasPraticasPorTipo['carga'],
                'necessarias' => $configuracaoCategoria['horas_praticas_carga'],
                'percentual' => $configuracaoCategoria['horas_praticas_carga'] > 0 ? min(100, ($aulasPraticasPorTipo['carga'] / $configuracaoCategoria['horas_praticas_carga']) * 100) : 0
            ],
            'praticas_passageiros' => [
                'concluidas' => $aulasPraticasPorTipo['passageiros'],
                'necessarias' => $configuracaoCategoria['horas_praticas_passageiros'],
                'percentual' => $configuracaoCategoria['horas_praticas_passageiros'] > 0 ? min(100, ($aulasPraticasPorTipo['passageiros'] / $configuracaoCategoria['horas_praticas_passageiros']) * 100) : 0
            ],
            'praticas_combinacao' => [
                'concluidas' => $aulasPraticasPorTipo['combinacao'],
                'necessarias' => $configuracaoCategoria['horas_praticas_combinacao'],
                'percentual' => $configuracaoCategoria['horas_praticas_combinacao'] > 0 ? min(100, ($aulasPraticasPorTipo['combinacao'] / $configuracaoCategoria['horas_praticas_combinacao']) * 100) : 0
            ]
        ];
    } else {
        // Fallback para valores padrão se não encontrar configuração
        $aulasNecessarias = 25;
        $aulasTeoricasNecessarias = 45;
        $progressoDetalhado = [
            'teoricas' => [
                'concluidas' => $aulasTeoricasConcluidas,
                'necessarias' => $aulasTeoricasNecessarias,
                'percentual' => $aulasTeoricasNecessarias > 0 ? min(100, ($aulasTeoricasConcluidas / $aulasTeoricasNecessarias) * 100) : 0
            ],
            'praticas_moto' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ],
            'praticas_carro' => [
                'concluidas' => $aulasPraticasConcluidas,
                'necessarias' => $aulasNecessarias,
                'percentual' => $aulasNecessarias > 0 ? min(100, ($aulasPraticasConcluidas / $aulasNecessarias) * 100) : 0
            ],
            'praticas_carga' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ],
            'praticas_passageiros' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ],
            'praticas_combinacao' => [
                'concluidas' => 0,
                'necessarias' => 0,
                'percentual' => 0
            ]
        ];
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
    SELECT a.*, i.credencial, u.nome as instrutor_nome, v.placa
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
                    <i class="fas fa-history me-2"></i>
                    Histórico do Aluno
                </h1>
            </div>
            <div class="col-auto">
                <a href="index.php?page=alunos" class="btn btn-outline-light">
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
                                            <?php echo $config['horas_teoricas']; ?> aulas
                                        </h5>
                                        <small class="text-muted">Teóricas</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-success mb-1">
                                        <i class="fas fa-car me-1"></i>
                                        <?php echo $config['horas_praticas_total']; ?> aulas
                                    </h5>
                                    <small class="text-muted">Práticas</small>
                                </div>
                            </div>
                            
                            <!-- Disciplinas Teóricas para Categorias Combinadas -->
                            <?php if ($config['horas_teoricas'] > 0): ?>
                            <div class="mt-2">
                                <h6 class="text-info mb-2">
                                    <i class="fas fa-list me-1"></i>
                                    Disciplinas Teóricas
                                </h6>
                                <div class="row">
                                    <?php 
                                    $disciplinas = [
                                        'legislacao_transito_aulas' => ['nome' => 'Legislação de Trânsito', 'icone' => 'fas fa-gavel', 'cor' => 'primary'],
                                        'primeiros_socorros_aulas' => ['nome' => 'Primeiros Socorros', 'icone' => 'fas fa-first-aid', 'cor' => 'danger'],
                                        'meio_ambiente_cidadania_aulas' => ['nome' => 'Meio Ambiente e Cidadania', 'icone' => 'fas fa-leaf', 'cor' => 'success'],
                                        'direcao_defensiva_aulas' => ['nome' => 'Direção Defensiva', 'icone' => 'fas fa-shield-alt', 'cor' => 'warning'],
                                        'mecanica_basica_aulas' => ['nome' => 'Mecânica Básica', 'icone' => 'fas fa-tools', 'cor' => 'info']
                                    ];
                                    
                                    foreach ($disciplinas as $campo => $info):
                                        $aulasDisciplina = $config[$campo] ?? 0;
                                        if ($aulasDisciplina > 0):
                                    ?>
                                    <div class="col-12 mb-1">
                                        <div class="d-flex justify-content-between align-items-center p-1 border rounded bg-light">
                                            <div class="d-flex align-items-center">
                                                <i class="<?php echo $info['icone']; ?> text-<?php echo $info['cor']; ?> me-2" style="font-size: 0.8em;"></i>
                                                <span class="fw-medium" style="font-size: 0.9em;"><?php echo $info['nome']; ?></span>
                                            </div>
                                            <span class="badge bg-<?php echo $info['cor']; ?>" style="font-size: 0.7em;">
                                                <?php echo $aulasDisciplina; ?> aulas
                                            </span>
                                        </div>
                                    </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <div class="mt-2">
                                <small class="text-muted">
                                    <strong>Detalhamento Prático:</strong><br>
                                    <?php if ($config['horas_praticas_moto'] > 0): ?>
                                        🏍️ Motocicletas: <?php echo $config['horas_praticas_moto']; ?> aulas<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_carro'] > 0): ?>
                                        🚗 Automóveis: <?php echo $config['horas_praticas_carro']; ?> aulas<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_carga'] > 0): ?>
                                        🚛 Carga: <?php echo $config['horas_praticas_carga']; ?> aulas<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_passageiros'] > 0): ?>
                                        🚌 Passageiros: <?php echo $config['horas_praticas_passageiros']; ?> aulas<br>
                                    <?php endif; ?>
                                    <?php if ($config['horas_praticas_combinacao'] > 0): ?>
                                        🚛+🚗 Combinação: <?php echo $config['horas_praticas_combinacao']; ?> aulas
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
                                        <?php echo $configuracaoCategoria['horas_teoricas']; ?> aulas
                                    </h5>
                                    <small class="text-muted">Teóricas</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <h5 class="text-success mb-1">
                                    <i class="fas fa-car me-1"></i>
                                    <?php echo $configuracaoCategoria['horas_praticas_total']; ?> aulas
                                </h5>
                                <small class="text-muted">Práticas</small>
                            </div>
                        </div>
                        
                        <!-- Detalhamento das Disciplinas Teóricas -->
                        <?php if ($configuracaoCategoria['horas_teoricas'] > 0): ?>
                        <div class="mt-3">
                            <h6 class="text-info mb-2">
                                <i class="fas fa-list me-1"></i>
                                Disciplinas Teóricas
                            </h6>
                            <div class="row">
                                <?php 
                                $disciplinas = [
                                    'legislacao_transito_aulas' => ['nome' => 'Legislação de Trânsito', 'icone' => 'fas fa-gavel', 'cor' => 'primary'],
                                    'primeiros_socorros_aulas' => ['nome' => 'Primeiros Socorros', 'icone' => 'fas fa-first-aid', 'cor' => 'danger'],
                                    'meio_ambiente_cidadania_aulas' => ['nome' => 'Meio Ambiente e Cidadania', 'icone' => 'fas fa-leaf', 'cor' => 'success'],
                                    'direcao_defensiva_aulas' => ['nome' => 'Direção Defensiva', 'icone' => 'fas fa-shield-alt', 'cor' => 'warning'],
                                    'mecanica_basica_aulas' => ['nome' => 'Mecânica Básica', 'icone' => 'fas fa-tools', 'cor' => 'info']
                                ];
                                
                                foreach ($disciplinas as $campo => $info):
                                    $aulasDisciplina = $configuracaoCategoria[$campo] ?? 0;
                                    if ($aulasDisciplina > 0):
                                ?>
                                <div class="col-12 mb-2">
                                    <div class="d-flex justify-content-between align-items-center p-2 border rounded bg-light">
                                        <div class="d-flex align-items-center">
                                            <i class="<?php echo $info['icone']; ?> text-<?php echo $info['cor']; ?> me-2"></i>
                                            <span class="fw-medium"><?php echo $info['nome']; ?></span>
                                        </div>
                                        <span class="badge bg-<?php echo $info['cor']; ?>">
                                            <?php echo $aulasDisciplina; ?> aulas
                                        </span>
                                    </div>
                                </div>
                                <?php 
                                    endif;
                                endforeach; 
                                ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Detalhamento Prático:</strong><br>
                                <?php if ($configuracaoCategoria['horas_praticas_moto'] > 0): ?>
                                    🏍️ Motocicletas: <?php echo $configuracaoCategoria['horas_praticas_moto']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_carro'] > 0): ?>
                                    🚗 Automóveis: <?php echo $configuracaoCategoria['horas_praticas_carro']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_carga'] > 0): ?>
                                    🚛 Carga: <?php echo $configuracaoCategoria['horas_praticas_carga']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_passageiros'] > 0): ?>
                                    🚌 Passageiros: <?php echo $configuracaoCategoria['horas_praticas_passageiros']; ?> aulas<br>
                                <?php endif; ?>
                                <?php if ($configuracaoCategoria['horas_praticas_combinacao'] > 0): ?>
                                    🚛+🚗 Combinação: <?php echo $configuracaoCategoria['horas_praticas_combinacao']; ?> aulas
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

        <!-- Total Geral -->
        <?php if ($progressoDetalhado): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0" style="color: #000000 !important; font-weight: 700 !important;">
                            <i class="fas fa-calculator me-2"></i>
                            Total Geral - Todas as Categorias
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Calcular totais gerais
                        $totalTeoricasGeral = 0;
                        $totalTeoricasConcluidasGeral = 0;
                        $totalPraticasGeral = 0;
                        $totalPraticasConcluidasGeral = 0;
                        
                        if ($ehCategoriaCombinada) {
                            foreach ($configuracoesCategorias as $categoria => $config) {
                                $totalTeoricasGeral += $config['horas_teoricas'];
                                $totalTeoricasConcluidasGeral += $progressoDetalhado[$categoria]['teoricas']['concluidas'];
                                $totalPraticasGeral += $config['horas_praticas_total']; // Somar as práticas das categorias
                                
                                foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
                                    $totalPraticasConcluidasGeral += $progressoDetalhado[$categoria][$tipo]['concluidas'];
                                }
                            }
                        } else {
                            if ($configuracaoCategoria) {
                                $totalTeoricasGeral = $configuracaoCategoria['horas_teoricas'];
                                $totalTeoricasConcluidasGeral = $progressoDetalhado['teoricas']['concluidas'];
                                $totalPraticasGeral = $configuracaoCategoria['horas_praticas_total'];
                                $totalPraticasConcluidasGeral = $aulasPraticasConcluidas;
                            } else {
                                // Fallback para valores padrão
                                $totalTeoricasGeral = 45;
                                $totalTeoricasConcluidasGeral = $progressoDetalhado['teoricas']['concluidas'];
                                $totalPraticasGeral = 25;
                                $totalPraticasConcluidasGeral = $aulasPraticasConcluidas;
                            }
                        }
                        
                        $percentualTeoricasGeral = $totalTeoricasGeral > 0 ? min(100, ($totalTeoricasConcluidasGeral / $totalTeoricasGeral) * 100) : 0;
                        $percentualPraticasGeral = $totalPraticasGeral > 0 ? min(100, ($totalPraticasConcluidasGeral / $totalPraticasGeral) * 100) : 0;
                        ?>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="text-info mb-3">
                                        <i class="fas fa-book me-2"></i>
                                        Total Aulas Teóricas
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">Progresso Geral</span>
                                        <span class="badge bg-info fs-6">
                                            <?php echo $totalTeoricasConcluidasGeral; ?>/<?php echo $totalTeoricasGeral; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-info" role="progressbar" 
                                             style="width: <?php echo $percentualTeoricasGeral; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Total necessário: <?php echo $totalTeoricasGeral; ?> aulas teóricas
                                    </small>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="border rounded p-3 mb-3">
                                    <h6 class="text-success mb-3">
                                        <i class="fas fa-car me-2"></i>
                                        Total Aulas Práticas
                                    </h6>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-bold">Progresso Geral</span>
                                        <span class="badge bg-success fs-6">
                                            <?php echo $totalPraticasConcluidasGeral; ?>/<?php echo $totalPraticasGeral; ?>
                                        </span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" 
                                             style="width: <?php echo $percentualPraticasGeral; ?>%">
                                        </div>
                                    </div>
                                    <small class="text-muted">
                                        Total necessário: <?php echo $totalPraticasGeral; ?>h práticas
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="border rounded p-3 bg-light">
                                <h6 class="text-primary mb-3">
                                    <i class="fas fa-chart-pie me-2"></i>
                                    Resumo Geral
                                </h6>
                                <div class="row text-center">
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-primary mb-1"><?php echo $totalTeoricasGeral + $totalPraticasGeral; ?></h4>
                                            <small class="text-muted">Total de Horas Necessárias</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-success mb-1"><?php echo $totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral; ?></h4>
                                            <small class="text-muted">Horas Concluídas</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-warning mb-1"><?php echo ($totalTeoricasGeral + $totalPraticasGeral) - ($totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral); ?></h4>
                                            <small class="text-muted">Horas Restantes</small>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="p-2">
                                            <h4 class="text-info mb-1"><?php echo number_format((($totalTeoricasConcluidasGeral + $totalPraticasConcluidasGeral) / ($totalTeoricasGeral + $totalPraticasGeral)) * 100, 1); ?>%</h4>
                                            <small class="text-muted">Progresso Geral</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Progresso Detalhado por Categoria -->
        <?php if ($progressoDetalhado): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0" style="color: #000000 !important; font-weight: 700 !important;">
                            <i class="fas fa-chart-line me-2"></i>
                            Progresso Detalhado por Categoria
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($ehCategoriaCombinada): ?>
                        <!-- Progresso para categorias combinadas -->
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
                                        $totalPraticasNecessarias = $config['horas_praticas_total']; // Usar sempre o total da configuração
                                        foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
                                            $totalPraticasConcluidas += $progressoDetalhado[$categoria][$tipo]['concluidas'];
                                        }
                                        echo $totalPraticasConcluidas . '/' . $totalPraticasNecessarias;
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php 
                                         $totalPraticasNecessarias = $config['horas_praticas_total']; // Usar sempre o total da configuração
                                         echo $totalPraticasNecessarias > 0 ? min(100, ($totalPraticasConcluidas / $totalPraticasNecessarias) * 100) : 0; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Total necessário: <?php echo $config['horas_praticas_total']; ?> aulas práticas
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
                        <?php if ($configuracaoCategoria): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary mb-3">
                                <i class="fas fa-certificate me-2"></i>
                                <?php echo htmlspecialchars($configuracaoCategoria['nome']); ?>
                            </h6>
                            
                            <!-- Teóricas -->
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
                            
                            <!-- Práticas -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="fw-bold">
                                        <i class="fas fa-car text-success me-2"></i>
                                        Aulas Práticas
                                    </span>
                                    <span class="badge bg-success">
                                        <?php 
                                        // Se não há aulas necessárias calculadas, usar o total da configuração
                                        $totalNecessarias = $aulasNecessarias > 0 ? $aulasNecessarias : $configuracaoCategoria['horas_praticas_total'];
                                        echo $aulasPraticasConcluidas . '/' . $totalNecessarias;
                                        ?>
                                    </span>
                                </div>
                                <div class="progress" style="height: 8px;">
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php 
                                         $totalNecessarias = $aulasNecessarias > 0 ? $aulasNecessarias : $configuracaoCategoria['horas_praticas_total'];
                                         $percentualCorrigido = $totalNecessarias > 0 ? min(100, ($aulasPraticasConcluidas / $totalNecessarias) * 100) : 0;
                                         echo $percentualCorrigido; ?>%">
                                    </div>
                                </div>
                                <small class="text-muted">
                                    Total necessário: <?php echo $configuracaoCategoria['horas_praticas_total']; ?> aulas práticas
                                </small>
                            </div>
                            
                            <!-- Detalhamento por tipo de veículo -->
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
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Estatísticas Gerais -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h4><?php echo $totalTeoricasGeral + $totalPraticasGeral; ?></h4>
                        <p class="mb-0">Total de Horas Necessárias</p>
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

        <!-- Histórico Completo -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="card-title mb-0" style="color: #ffffff !important; font-weight: 700 !important;">
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
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/admin.js"></script>
    <script>
        // Funções para ações
        function verDetalhesAula(aulaId) {
            alert('Funcionalidade de detalhes será implementada em breve!');
        }

        function editarAula(aulaId) {
            window.location.href = `agendar-aula.php?edit=${aulaId}`;
        }

        function cancelarAula(aulaId) {
            if (confirm('Tem certeza que deseja cancelar esta aula?')) {
                alert('Funcionalidade de cancelamento será implementada em breve!');
            }
        }
    </script>
</body>
</html>