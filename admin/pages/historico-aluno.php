<?php
// Esta página é incluída pelo sistema de roteamento do admin
// As variáveis $user, $isAdmin, $isSecretaria, $isInstrutor já estão definidas no index.php

// Verificar se estamos sendo acessados diretamente (sem template)
if (!defined('ADMIN_ROUTING')) {
    // Redirecionar para o template do admin
    $aluno_id = $_GET['id'] ?? '';
    if ($aluno_id) {
        header("Location: ../index.php?page=historico-aluno&id=$aluno_id");
        exit;
    } else {
        header('Location: ../index.php?page=alunos');
        exit;
    }
}

// Incluir dependências necessárias (caso não estejam disponíveis)
if (!function_exists('db')) {
    require_once '../../includes/database.php';
}

// Verificar se as variáveis estão definidas (fallback para compatibilidade)
if (!isset($user)) {
    $user = [
        'id' => $_SESSION['user_id'] ?? null,
        'nome' => $_SESSION['user_name'] ?? null,
        'tipo' => $_SESSION['user_type'] ?? null
    ];
}
if (!isset($isAdmin)) $isAdmin = ($user['tipo'] ?? '') === 'admin';
if (!isset($isSecretaria)) $isSecretaria = ($user['tipo'] ?? '') === 'secretaria';
if (!isset($isInstrutor)) $isInstrutor = ($user['tipo'] ?? '') === 'instrutor';

// Garantir que o banco de dados está disponível
if (!isset($db)) {
    $db = db(); // Usar função global
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
    $alunoData = $db->fetch("
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
        $cfcData = $db->fetch("SELECT * FROM cfcs WHERE id = ?", [$alunoData['cfc_id']]);
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
$aulas = $db->fetchAll("
    SELECT a.*, i.credencial, COALESCE(u.nome, i.nome) as instrutor_nome, v.placa, v.modelo, v.marca
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

// Buscar exames do aluno
$exames = $db->fetchAll("
    SELECT * FROM exames 
    WHERE aluno_id = ? 
    ORDER BY tipo, data_agendada DESC
", [$alunoId]);

// Separar exames por tipo
$exameMedico = null;
$examePsicotecnico = null;

foreach ($exames as $exame) {
    if ($exame['tipo'] === 'medico') {
        $exameMedico = $exame;
    } elseif ($exame['tipo'] === 'psicotecnico') {
        $examePsicotecnico = $exame;
    }
}

// Calcular se exames estão OK
$examesOK = false;
if ($exameMedico && $exameMedico['status'] === 'concluido' && $exameMedico['resultado'] === 'apto' &&
    $examePsicotecnico && $examePsicotecnico['status'] === 'concluido' && $examePsicotecnico['resultado'] === 'apto') {
    $examesOK = true;
}

// Verificar guards de bloqueio
require_once __DIR__ . '/../includes/guards_exames.php';
$bloqueioTeorica = GuardsExames::verificarBloqueioTeorica($alunoId);

// Calcular estatísticas por tipo de aula
// Para teóricas, contar apenas disciplinas únicas para evitar duplicação
$disciplinasTeoricasUnicasGerais = [];
$aulasTeoricasConcluidas = 0;
foreach ($aulas as $aula) {
    if ($aula['status'] === 'concluida' && $aula['tipo_aula'] === 'teorica') {
        $disciplina = $aula['disciplina'] ?? 'geral';
        if (!isset($disciplinasTeoricasUnicasGerais[$disciplina])) {
            $disciplinasTeoricasUnicasGerais[$disciplina] = true;
            $aulasTeoricasConcluidas++;
        }
    }
}
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

// Verificar se é uma categoria mudanca_categoria (ex: AB, AC, etc.)
$configuracoesCategorias = $configManager->getConfiguracoesParaCategoriaCombinada($categoriaAluno);
$ehCategoriaCombinada = count($configuracoesCategorias) > 1;

if ($ehCategoriaCombinada) {
    // Para categorias mudanca_categorias, calcular progresso separadamente para cada categoria
    $aulasNecessarias = 0;
    $aulasTeoricasNecessarias = 0;
    $progressoDetalhado = [];
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        $aulasNecessarias += $config['horas_praticas_total'];
        $aulasTeoricasNecessarias += $config['horas_teoricas'];
        
        // Calcular aulas concluídas por tipo para esta categoria específica
        // Para teóricas, contar apenas disciplinas únicas para evitar duplicação
        $disciplinasTeoricasUnicas = [];
        $teoricasConcluidas = 0;
        foreach ($aulas as $aula) {
            if ($aula['status'] === 'concluida' && 
                $aula['tipo_aula'] === 'teorica' && 
                $aula['categoria_veiculo'] === $categoria) {
                $disciplina = $aula['disciplina'] ?? 'geral';
                if (!isset($disciplinasTeoricasUnicas[$disciplina])) {
                    $disciplinasTeoricasUnicas[$disciplina] = true;
                    $teoricasConcluidas++;
                }
            }
        }
        
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
    
    // Contar aulas concluídas por tipo para categorias mudanca_categorias
    $aulasTeoricasContadas = []; // Para evitar duplicação de aulas teóricas
    
    foreach ($aulas as $aula) {
        if ($aula['status'] === 'concluida') {
            if ($aula['tipo_aula'] === 'teorica') {
                // Para teóricas, contar apenas uma vez para categorias mudanca_categorias
                // Usar disciplina como identificador único para evitar duplicação
                $disciplina = $aula['disciplina'] ?? 'geral';
                if (!isset($aulasTeoricasContadas[$disciplina])) {
                    $aulasTeoricasContadas[$disciplina] = true;
                    
                    // Distribuir entre todas as categorias apenas uma vez
                    foreach ($progressoDetalhado as $categoria => $dados) {
                        if (isset($progressoDetalhado[$categoria]['teoricas'])) {
                            $progressoDetalhado[$categoria]['teoricas']['concluidas']++;
                        }
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
$proximasAulas = $db->fetchAll("
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

<!-- Conteúdo da página de histórico do aluno -->

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
                        <h5 class="card-title mb-0" style="color: #6c757d !important;">
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
                        <!-- Exibição para categorias mudanca_categorias -->
                        <div class="text-center mb-3">
                            <h6 class="text-warning mb-2">
                                <i class="fas fa-layer-group me-2"></i>
                                Categoria Combinada: <?php echo htmlspecialchars($categoriaAluno); ?>
                            </h6>
                            <span class="badge bg-warning text-dark fs-6">
                                <?php echo htmlspecialchars($categoriaAluno); ?>
                            </span>
                        </div>
                        
                        <!-- Disciplinas Teóricas Compartilhadas (exibidas apenas uma vez para categorias mudanca_categorias) -->
                        <?php 
                        // Pegar a primeira configuração para obter as disciplinas teóricas
                        $primeiraConfig = reset($configuracoesCategorias);
                        if ($primeiraConfig['horas_teoricas'] > 0): 
                        ?>
                        <div class="border rounded p-3 mb-3 bg-light">
                            <h6 class="text-info mb-2">
                                <i class="fas fa-list me-1"></i>
                                Disciplinas Teóricas (Compartilhadas)
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
                                    $aulasDisciplina = $primeiraConfig[$campo] ?? 0;
                                    if ($aulasDisciplina > 0):
                                ?>
                                <div class="col-12 mb-1">
                                    <div class="d-flex justify-content-between align-items-center p-1 border rounded bg-white">
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

                        <?php foreach ($configuracoesCategorias as $categoria => $config): ?>
                        <div class="border rounded p-3 mb-3">
                            <h6 class="text-primary mb-2">
                                <i class="fas fa-certificate me-1"></i>
                                Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
                            </h6>
                            
                            <div class="row text-center">
                                <div class="col-12">
                                    <h5 class="text-success mb-1">
                                        <i class="fas fa-car me-1"></i>
                                        <?php echo $config['horas_praticas_total']; ?> aulas
                                    </h5>
                                    <small class="text-muted">Práticas</small>
                                </div>
                            </div>
                            
                            <!-- Detalhamento Prático -->
                            <div class="mt-2">
                                <h6 class="text-success mb-2">
                                    <i class="fas fa-car me-1"></i>
                                    Detalhamento Prático
                                </h6>
                                <div class="row">
                                    <?php 
                                    $tiposVeiculo = [
                                        'moto' => ['nome' => 'Motocicleta', 'icone' => 'fas fa-motorcycle', 'cor' => 'warning'],
                                        'carro' => ['nome' => 'Automóvel', 'icone' => 'fas fa-car', 'cor' => 'primary'],
                                        'carga' => ['nome' => 'Caminhão', 'icone' => 'fas fa-truck', 'cor' => 'info'],
                                        'passageiros' => ['nome' => 'Ônibus', 'icone' => 'fas fa-bus', 'cor' => 'success'],
                                        'combinacao' => ['nome' => 'Carreta', 'icone' => 'fas fa-truck-moving', 'cor' => 'secondary']
                                    ];
                                    
                                    foreach ($tiposVeiculo as $tipo => $info):
                                        $campoAulas = "horas_praticas_{$tipo}";
                                        $aulasTipo = $config[$campoAulas] ?? 0;
                                        if ($aulasTipo > 0):
                                    ?>
                                    <div class="col-12 mb-1">
                                        <div class="d-flex justify-content-between align-items-center p-1 border rounded bg-light">
                                            <div class="d-flex align-items-center">
                                                <i class="<?php echo $info['icone']; ?> text-<?php echo $info['cor']; ?> me-2" style="font-size: 0.8em;"></i>
                                                <span class="fw-medium" style="font-size: 0.9em;"><?php echo $info['nome']; ?></span>
                                            </div>
                                            <span class="badge bg-<?php echo $info['cor']; ?>" style="font-size: 0.7em;">
                                                <?php echo $aulasTipo; ?> aulas
                                            </span>
                                        </div>
                                    </div>
                                    <?php 
                                        endif;
                                    endforeach; 
                                    ?>
                                </div>
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
                            // Para categorias mudanca_categorias, contar teóricas apenas uma vez
                            $primeiraConfig = reset($configuracoesCategorias);
                            $totalTeoricasGeral = $primeiraConfig['horas_teoricas'];
                            
                            foreach ($configuracoesCategorias as $categoria => $config) {
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

        <!-- Exames DETRAN -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="card-title mb-0" style="color: #6c757d !important;">
                            <i class="fas fa-stethoscope me-2"></i>
                            Exames (DETRAN)
                            <?php if ($examesOK): ?>
                                <span class="badge bg-success ms-2">
                                    <i class="fas fa-check-circle me-1"></i>OK
                                </span>
                            <?php else: ?>
                                <span class="badge bg-warning ms-2">
                                    <i class="fas fa-clock me-1"></i>Pendente
                                </span>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Exame Médico -->
                            <div class="col-md-6">
                                <div class="card border-<?php echo $exameMedico && $exameMedico['resultado'] === 'apto' ? 'success' : ($exameMedico && $exameMedico['resultado'] === 'inapto' ? 'danger' : 'warning'); ?>">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-user-md me-2"></i>
                                            Exame Médico
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($exameMedico): ?>
                                            <!-- Status Badge -->
                                            <div class="mb-2">
                                                <?php if ($exameMedico['status'] === 'agendado'): ?>
                                                    <span class="badge bg-primary">Agendado</span>
                                                <?php elseif ($exameMedico['status'] === 'concluido'): ?>
                                                    <span class="badge bg-success">Concluído</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Cancelado</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Resultado Badge -->
                                            <div class="mb-2">
                                                <?php if ($exameMedico['resultado'] === 'apto'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Apto
                                                    </span>
                                                <?php elseif ($exameMedico['resultado'] === 'inapto'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Inapto
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Pendente
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Informações -->
                                            <div class="small text-muted">
                                                <?php if ($exameMedico['data_agendada']): ?>
                                                    <p><strong>Agendado:</strong> <?php echo date('d/m/Y H:i', strtotime($exameMedico['data_agendada'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['clinica_nome']): ?>
                                                    <p><strong>Clínica:</strong> <?php echo htmlspecialchars($exameMedico['clinica_nome']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['protocolo']): ?>
                                                    <p><strong>Protocolo:</strong> <?php echo htmlspecialchars($exameMedico['protocolo']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['data_resultado']): ?>
                                                    <p><strong>Resultado em:</strong> <?php echo date('d/m/Y', strtotime($exameMedico['data_resultado'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($exameMedico['observacoes']): ?>
                                                    <p><strong>Observações:</strong> <?php echo htmlspecialchars($exameMedico['observacoes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Ações -->
                                            <?php if ($exameMedico['status'] === 'agendado' && ($isAdmin || $isSecretaria)): ?>
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="abrirModalResultado(<?php echo $exameMedico['id']; ?>, 'medico')">
                                                        <i class="fas fa-edit me-1"></i>Lançar Resultado
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="cancelarExame(<?php echo $exameMedico['id']; ?>)">
                                                        <i class="fas fa-times me-1"></i>Cancelar
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-plus-circle fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Nenhum exame agendado</p>
                                                <?php if ($isAdmin || $isSecretaria): ?>
                                                    <button class="btn btn-sm btn-primary mt-2" onclick="abrirModalAgendamento('medico')">
                                                        <i class="fas fa-plus me-1"></i>Agendar Exame
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Exame Psicotécnico -->
                            <div class="col-md-6">
                                <div class="card border-<?php echo $examePsicotecnico && $examePsicotecnico['resultado'] === 'apto' ? 'success' : ($examePsicotecnico && $examePsicotecnico['resultado'] === 'inapto' ? 'danger' : 'warning'); ?>">
                                    <div class="card-header bg-light">
                                        <h6 class="card-title mb-0">
                                            <i class="fas fa-brain me-2"></i>
                                            Exame Psicotécnico
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <?php if ($examePsicotecnico): ?>
                                            <!-- Status Badge -->
                                            <div class="mb-2">
                                                <?php if ($examePsicotecnico['status'] === 'agendado'): ?>
                                                    <span class="badge bg-primary">Agendado</span>
                                                <?php elseif ($examePsicotecnico['status'] === 'concluido'): ?>
                                                    <span class="badge bg-success">Concluído</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Cancelado</span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Resultado Badge -->
                                            <div class="mb-2">
                                                <?php if ($examePsicotecnico['resultado'] === 'apto'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check me-1"></i>Apto
                                                    </span>
                                                <?php elseif ($examePsicotecnico['resultado'] === 'inapto'): ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times me-1"></i>Inapto
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Pendente
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Informações -->
                                            <div class="small text-muted">
                                                <?php if ($examePsicotecnico['data_agendada']): ?>
                                                    <p><strong>Agendado:</strong> <?php echo date('d/m/Y H:i', strtotime($examePsicotecnico['data_agendada'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['clinica_nome']): ?>
                                                    <p><strong>Clínica:</strong> <?php echo htmlspecialchars($examePsicotecnico['clinica_nome']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['protocolo']): ?>
                                                    <p><strong>Protocolo:</strong> <?php echo htmlspecialchars($examePsicotecnico['protocolo']); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['data_resultado']): ?>
                                                    <p><strong>Resultado em:</strong> <?php echo date('d/m/Y', strtotime($examePsicotecnico['data_resultado'])); ?></p>
                                                <?php endif; ?>
                                                <?php if ($examePsicotecnico['observacoes']): ?>
                                                    <p><strong>Observações:</strong> <?php echo htmlspecialchars($examePsicotecnico['observacoes']); ?></p>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <!-- Ações -->
                                            <?php if ($examePsicotecnico['status'] === 'agendado' && ($isAdmin || $isSecretaria)): ?>
                                                <div class="mt-3">
                                                    <button class="btn btn-sm btn-outline-primary" onclick="abrirModalResultado(<?php echo $examePsicotecnico['id']; ?>, 'psicotecnico')">
                                                        <i class="fas fa-edit me-1"></i>Lançar Resultado
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="cancelarExame(<?php echo $examePsicotecnico['id']; ?>)">
                                                        <i class="fas fa-times me-1"></i>Cancelar
                                                    </button>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="text-center py-3">
                                                <i class="fas fa-plus-circle fa-2x text-muted mb-2"></i>
                                                <p class="text-muted mb-0">Nenhum exame agendado</p>
                                                <?php if ($isAdmin || $isSecretaria): ?>
                                                    <button class="btn btn-sm btn-primary mt-2" onclick="abrirModalAgendamento('psicotecnico')">
                                                        <i class="fas fa-plus me-1"></i>Agendar Exame
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Status Geral dos Exames -->
                        <div class="mt-4 p-3 border rounded <?php echo $examesOK ? 'bg-success bg-opacity-10 border-success' : 'bg-warning bg-opacity-10 border-warning'; ?>">
                            <div class="d-flex align-items-center">
                                <?php if ($examesOK): ?>
                                    <i class="fas fa-check-circle fa-2x text-success me-3"></i>
                                    <div>
                                        <h6 class="mb-1 text-success">Exames OK</h6>
                                        <small class="text-muted">Aluno apto para prosseguir com aulas teóricas</small>
                                    </div>
                                <?php else: ?>
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning me-3"></i>
                                    <div>
                                        <h6 class="mb-1 text-warning">Exames Pendentes</h6>
                                        <small class="text-muted">
                                            <?php if (!$exameMedico): ?>
                                                • Falta agendar exame médico<br>
                                            <?php elseif ($exameMedico['resultado'] !== 'apto'): ?>
                                                • Falta lançar resultado do exame médico<br>
                                            <?php endif; ?>
                                            <?php if (!$examePsicotecnico): ?>
                                                • Falta agendar exame psicotécnico<br>
                                            <?php elseif ($examePsicotecnico['resultado'] !== 'apto'): ?>
                                                • Falta lançar resultado do exame psicotécnico<br>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status de Bloqueios -->
        <?php if (!$bloqueioTeorica['pode_prosseguir']): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Bloqueios para Aulas Teóricas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-lock me-2"></i>
                                Aulas teóricas bloqueadas
                            </h6>
                            <p class="mb-2">O aluno não pode prosseguir com aulas teóricas pelos seguintes motivos:</p>
                            <ul class="mb-0">
                                <?php foreach ($bloqueioTeorica['motivos_bloqueio'] as $motivo): ?>
                                    <li><?php echo htmlspecialchars($motivo); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Liberado para Aulas Teóricas
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading">
                                <i class="fas fa-unlock me-2"></i>
                                Tudo em ordem
                            </h6>
                            <p class="mb-0">O aluno está liberado para prosseguir com aulas teóricas. Exames OK e situação financeira regularizada.</p>
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
                        <!-- Progresso para categorias mudanca_categorias -->
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
                        <h5 class="card-title mb-0" style="color: #6c757d !important; font-weight: 700 !important;">
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
                                                <small class="text-muted"><?php echo htmlspecialchars($aula['credencial'] ?? 'N/A'); ?></small>
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

    <!-- Modal de Detalhes da Aula -->
    <div class="modal fade" id="modalDetalhesAula" tabindex="-1" aria-labelledby="modalDetalhesAulaLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDetalhesAulaLabel">
                        <i class="fas fa-info-circle me-2"></i>Detalhes da Aula
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modalDetalhesBody">
                    <!-- Conteúdo será carregado via JavaScript -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                    <button type="button" class="btn btn-primary" id="btnEditarAula" style="display: none;">
                        <i class="fas fa-edit me-1"></i>Editar Aula
                    </button>
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
            const btnEditar = document.getElementById('btnEditarAula');
            
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
            
            // Mostrar botão de editar apenas para aulas agendadas
            if (aula.status === 'agendada') {
                btnEditar.style.display = 'inline-block';
                btnEditar.onclick = () => {
                    window.location.href = `/cfc-bom-conselho/admin/index.php?page=agendar-aula&action=edit&edit=${aulaId}&t=${Date.now()}`;
                };
            } else {
                btnEditar.style.display = 'none';
            }
            
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
            console.log('=== DEBUG EDIÇÃO ===');
            console.log('aulaId recebido:', aulaId);
            console.log('Tipo do aulaId:', typeof aulaId);
            
            // Verificar se o Bootstrap está carregado
            if (typeof bootstrap === 'undefined') {
                console.error('❌ Bootstrap não está carregado!');
                alert('Erro: Bootstrap não está carregado. Recarregue a página.');
                return;
            }
            
            // Verificar se a função está sendo chamada
            console.log('✅ Função editarAula chamada com sucesso');
            
            // Limpar cache e redirecionar com versão forçada
            const timestamp = Date.now();
            const random = Math.random().toString(36).substring(7);
            const version = 'v' + Math.floor(Date.now() / 1000);
            
            const url = `index.php?page=agendar-aula&action=edit&edit=${aulaId}&t=${timestamp}&r=${random}&v=${version}`;
            
            console.log('URL gerada:', url);
            console.log('Redirecionando em 1 segundo...');
            
            // Adicionar delay para ver o log
            setTimeout(() => {
                console.log('Executando redirecionamento...');
                window.location.href = url;
            }, 1000);
        }

        function cancelarAula(aulaId) {
            console.log('=== DEBUG CANCELAMENTO ===');
            console.log('aulaId recebido:', aulaId);
            console.log('Tipo do aulaId:', typeof aulaId);
            
            // Verificar se o Bootstrap está carregado
            if (typeof bootstrap === 'undefined') {
                console.error('❌ Bootstrap não está carregado!');
                alert('Erro: Bootstrap não está carregado. Recarregue a página.');
                return;
            }
            
            // Verificar se o modal existe
            const modalElement = document.getElementById('modalCancelarAula');
            if (!modalElement) {
                console.error('❌ Modal modalCancelarAula não encontrado!');
                alert('Erro: Modal de cancelamento não encontrado. Recarregue a página.');
                return;
            }
            
            console.log('✅ Modal encontrado:', modalElement);
            
            if (confirm('Tem certeza que deseja cancelar esta aula?')) {
                console.log('✅ Usuário confirmou cancelamento');
                
                // Mostrar modal de cancelamento
                const modal = new bootstrap.Modal(modalElement);
                document.getElementById('aulaIdCancelar').value = aulaId;
                modal.show();
                
                console.log('✅ Modal de cancelamento exibido');
            } else {
                console.log('❌ Usuário cancelou a operação');
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

        // =====================================================
        // FUNÇÕES PARA EXAMES
        // =====================================================

        function abrirModalAgendamento(tipo) {
            console.log('🔍 Função abrirModalAgendamento chamada com tipo:', tipo);
            
            // Verificar se o Bootstrap está carregado
            if (typeof bootstrap === 'undefined') {
                console.error('❌ Bootstrap não está carregado!');
                alert('Erro: Bootstrap não está carregado. Recarregue a página.');
                return;
            }
            
            // Verificar se o modal existe
            const modalElement = document.getElementById('modalAgendamento');
            if (!modalElement) {
                console.error('❌ Modal modalAgendamento não encontrado!');
                alert('Erro: Modal não encontrado. Recarregue a página.');
                return;
            }
            
            console.log('✅ Modal encontrado, criando instância Bootstrap...');
            const modal = new bootstrap.Modal(modalElement);
            
            // Verificar se os elementos existem
            const tipoExameElement = document.getElementById('tipoExame');
            const tipoExameLabelElement = document.getElementById('tipoExameLabel');
            
            if (!tipoExameElement) {
                console.error('❌ Elemento tipoExame não encontrado!');
                return;
            }
            
            if (!tipoExameLabelElement) {
                console.error('❌ Elemento tipoExameLabel não encontrado!');
                return;
            }
            
            console.log('✅ Elementos encontrados, configurando valores...');
            tipoExameElement.value = tipo;
            tipoExameLabelElement.textContent = tipo === 'medico' ? 'Médico' : 'Psicotécnico';
            
            console.log('✅ Mostrando modal...');
            modal.show();
        }

        function abrirModalResultado(exameId, tipo) {
            const modal = new bootstrap.Modal(document.getElementById('modalResultado'));
            document.getElementById('exameIdResultado').value = exameId;
            document.getElementById('tipoExameResultado').value = tipo;
            document.getElementById('tipoExameResultadoLabel').textContent = tipo === 'medico' ? 'Médico' : 'Psicotécnico';
            modal.show();
        }

        function agendarExame() {
            const form = document.getElementById('formAgendamento');
            const formData = new FormData(form);
            
            // Mostrar loading
            const btnSalvar = document.getElementById('btnSalvarAgendamento');
            const loadingHtml = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
            btnSalvar.disabled = true;

            fetch('api/exames.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Exame agendado com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('modalAgendamento')).hide();
                    location.reload(); // Recarregar para mostrar o novo exame
                } else {
                    showToast('error', data.error || 'Erro ao agendar exame');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao agendar exame');
            })
            .finally(() => {
                btnSalvar.innerHTML = loadingHtml;
                btnSalvar.disabled = false;
            });
        }

        function lancarResultado() {
            const form = document.getElementById('formResultado');
            const formData = new FormData(form);
            
            // Mostrar loading
            const btnSalvar = document.getElementById('btnSalvarResultado');
            const loadingHtml = btnSalvar.innerHTML;
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Salvando...';
            btnSalvar.disabled = true;

            fetch(`api/exames.php?id=${document.getElementById('exameIdResultado').value}`, {
                method: 'PUT',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Resultado lançado com sucesso!');
                    bootstrap.Modal.getInstance(document.getElementById('modalResultado')).hide();
                    location.reload(); // Recarregar para mostrar o resultado
                } else {
                    showToast('error', data.error || 'Erro ao lançar resultado');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao lançar resultado');
            })
            .finally(() => {
                btnSalvar.innerHTML = loadingHtml;
                btnSalvar.disabled = false;
            });
        }

        function cancelarExame(exameId) {
            if (!confirm('Tem certeza que deseja cancelar este exame?')) {
                return;
            }

            fetch(`api/exames.php?id=${exameId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast('success', 'Exame cancelado com sucesso!');
                    location.reload();
                } else {
                    showToast('error', data.error || 'Erro ao cancelar exame');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                showToast('error', 'Erro ao cancelar exame');
            });
        }
    </script>

    <!-- Modal Agendamento de Exame -->
    <div class="modal fade" id="modalAgendamento" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-calendar-plus me-2"></i>
                        Agendar Exame <span id="tipoExameLabel"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formAgendamento">
                        <input type="hidden" id="tipoExame" name="tipo">
                        <input type="hidden" name="aluno_id" value="<?php echo $alunoId; ?>">
                        
                        <div class="mb-3">
                            <label for="data_agendada" class="form-label">Data e Hora do Exame</label>
                            <input type="datetime-local" class="form-control" id="data_agendada" name="data_agendada" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="clinica_nome" class="form-label">Nome da Clínica</label>
                            <input type="text" class="form-control" id="clinica_nome" name="clinica_nome" placeholder="Ex: Clínica São Paulo">
                        </div>
                        
                        <div class="mb-3">
                            <label for="protocolo" class="form-label">Protocolo/Guia</label>
                            <input type="text" class="form-control" id="protocolo" name="protocolo" placeholder="Ex: PROT-001">
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes" name="observacoes" rows="3" placeholder="Observações sobre o agendamento..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarAgendamento" onclick="agendarExame()">
                        <i class="fas fa-save me-2"></i>Agendar Exame
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Lançar Resultado -->
    <div class="modal fade" id="modalResultado" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Lançar Resultado - Exame <span id="tipoExameResultadoLabel"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formResultado">
                        <input type="hidden" id="exameIdResultado" name="exame_id">
                        <input type="hidden" id="tipoExameResultado" name="tipo">
                        
                        <div class="mb-3">
                            <label for="resultado" class="form-label">Resultado</label>
                            <select class="form-control" id="resultado" name="resultado" required>
                                <option value="">Selecione o resultado</option>
                                <option value="apto">Apto</option>
                                <option value="inapto">Inapto</option>
                                <option value="pendente">Pendente</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="data_resultado" class="form-label">Data do Resultado</label>
                            <input type="date" class="form-control" id="data_resultado" name="data_resultado" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="observacoes_resultado" class="form-label">Observações</label>
                            <textarea class="form-control" id="observacoes_resultado" name="observacoes" rows="3" placeholder="Observações sobre o resultado..."></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" id="btnSalvarResultado" onclick="lancarResultado()">
                        <i class="fas fa-save me-2"></i>Lançar Resultado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed bottom-0 end-0 p-3">
        <div id="toast" class="toast" role="alert">
            <div class="toast-header">
                <i id="toastIcon" class="fas fa-info-circle text-primary me-2"></i>
                <strong id="toastTitle" class="me-auto">Notificação</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div id="toastBody" class="toast-body"></div>
        </div>
    </div>

    <script>
        // Função para mostrar toast
        function showToast(type, message) {
            const toast = document.getElementById('toast');
            const toastIcon = document.getElementById('toastIcon');
            const toastTitle = document.getElementById('toastTitle');
            const toastBody = document.getElementById('toastBody');
            
            // Configurar ícone e título baseado no tipo
            switch(type) {
                case 'success':
                    toastIcon.className = 'fas fa-check-circle text-success me-2';
                    toastTitle.textContent = 'Sucesso';
                    toast.className = 'toast border-success';
                    break;
                case 'error':
                    toastIcon.className = 'fas fa-exclamation-circle text-danger me-2';
                    toastTitle.textContent = 'Erro';
                    toast.className = 'toast border-danger';
                    break;
                default:
                    toastIcon.className = 'fas fa-info-circle text-primary me-2';
                    toastTitle.textContent = 'Informação';
                    toast.className = 'toast border-primary';
            }
            
            toastBody.textContent = message;
            
            // Mostrar toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
        }
    </script>