<?php
/**
 * Interface de Chamada - Turmas Teóricas
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * ETAPA 1.3: Interface de Chamada
 * 
 * @author Sistema CFC Bom Conselho
 * @version 1.0
 * @since 2024
 */

// Incluir dependências
require_once __DIR__ . '/../../includes/config.php';
require_once __DIR__ . '/../../includes/database.php';
require_once __DIR__ . '/../../includes/auth.php';

// Verificar autenticação
if (!isLoggedIn()) {
    header('Location: /login.php');
    exit();
}

$db = Database::getInstance();
$userId = $_SESSION['user_id'];
$userType = $_SESSION['user_type'] ?? 'aluno';

// Verificar permissões
$canEdit = ($userType === 'admin' || $userType === 'instrutor');

// Parâmetros da URL
$turmaId = $_GET['turma_id'] ?? null;
$aulaId = $_GET['aula_id'] ?? null;

if (!$turmaId) {
    // Se já houve output (página incluída via router), usar JavaScript para redirecionar
    if (headers_sent()) {
        echo '<div class="alert alert-warning m-3"><p>É necessário selecionar uma turma para acessar a chamada. Redirecionando...</p></div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php?page=turmas-teoricas"; }, 1000);</script>';
        exit();
    } else {
        // Caso contrário, usar header redirect normal
        header('Location: index.php?page=turmas-teoricas');
        exit();
    }
}

// Buscar dados da turma (CORRIGIDO: usar turmas_teoricas em vez de turmas)
$turma = $db->fetch("
    SELECT 
        tt.*,
        i.nome as instrutor_nome,
        c.nome as cfc_nome
    FROM turmas_teoricas tt
    LEFT JOIN instrutores i ON tt.instrutor_id = i.id
    LEFT JOIN cfcs c ON tt.cfc_id = c.id
    WHERE tt.id = ?
", [$turmaId]);

if (!$turma) {
    // Se já houve output (página incluída via router), usar JavaScript para redirecionar
    if (headers_sent()) {
        echo '<div class="alert alert-danger m-3"><p>Turma não encontrada. Redirecionando...</p></div>';
        echo '<script>setTimeout(function(){ window.location.href = "index.php?page=turmas-teoricas"; }, 1000);</script>';
        exit();
    } else {
        // Caso contrário, usar header redirect normal
        header('Location: index.php?page=turmas-teoricas');
        exit();
    }
}

// Verificar se usuário tem permissão para esta turma
if ($userType === 'instrutor' && $turma['instrutor_id'] != $userId) {
    $canEdit = false;
}

// Verificar regras adicionais: turma concluída/cancelada
if ($turma['status'] === 'cancelada') {
    // Ninguém pode editar turmas canceladas
    $canEdit = false;
} elseif ($turma['status'] === 'concluida' && $userType === 'instrutor') {
    // Instrutor não pode editar turmas concluídas (apenas admin/secretaria)
    $canEdit = false;
}

// Buscar aulas da turma (CORRIGIDO: usar turma_aulas_agendadas e aula_id)
$aulas = $db->fetchAll("
    SELECT 
        taa.*,
        COUNT(tp.id) as presencas_registradas
    FROM turma_aulas_agendadas taa
    LEFT JOIN turma_presencas tp ON taa.id = tp.aula_id
    WHERE taa.turma_id = ?
    GROUP BY taa.id
    ORDER BY taa.ordem_global ASC
", [$turmaId]);

// Se não especificou aula, usar a primeira
if (!$aulaId && !empty($aulas)) {
    $aulaId = $aulas[0]['id'];
}

// Buscar dados da aula atual (CORRIGIDO: usar turma_aulas_agendadas)
$aulaAtual = null;
if ($aulaId) {
    $aulaAtual = $db->fetch("
        SELECT * FROM turma_aulas_agendadas WHERE id = ? AND turma_id = ?
    ", [$aulaId, $turmaId]);
}

// Buscar alunos matriculados na turma (CORRIGIDO: usar turma_matriculas e aula_id)
$alunos = $db->fetchAll("
    SELECT 
        a.*,
        tm.status as status_matricula,
        tm.data_matricula,
        tm.frequencia_percentual,
        tp.presente,
        tp.justificativa as observacao_presenca,
        tp.registrado_em as presenca_registrada_em,
        tp.id as presenca_id
    FROM alunos a
    JOIN turma_matriculas tm ON a.id = tm.aluno_id
    LEFT JOIN turma_presencas tp ON (
        a.id = tp.aluno_id 
        AND tp.turma_id = ? 
        AND tp.aula_id = ?
    )
    WHERE tm.turma_id = ? 
    AND tm.status IN ('matriculado', 'cursando', 'concluido')
    ORDER BY a.nome ASC
", [$turmaId, $aulaId, $turmaId]);

// Calcular estatísticas da turma
$estatisticasTurma = [
    'total_alunos' => count($alunos),
    'presentes' => 0,
    'ausentes' => 0,
    'sem_registro' => 0,
    'frequencia_media' => 0
];

foreach ($alunos as $aluno) {
    if ($aluno['presenca_id']) {
        if ($aluno['presente']) {
            $estatisticasTurma['presentes']++;
        } else {
            $estatisticasTurma['ausentes']++;
        }
    } else {
        $estatisticasTurma['sem_registro']++;
    }
}

if ($estatisticasTurma['total_alunos'] > 0) {
    $totalRegistradas = $estatisticasTurma['presentes'] + $estatisticasTurma['ausentes'];
    if ($totalRegistradas > 0) {
        $estatisticasTurma['frequencia_media'] = round(
            ($estatisticasTurma['presentes'] / $totalRegistradas) * 100, 2
        );
    }
}

// Buscar frequência geral da turma via API
$frequenciaGeral = null;
if ($aulaId) {
    // Simular chamada para API de frequência
    $_GET = ['turma_id' => $turmaId];
    ob_start();
    include __DIR__ . '/../api/turma-frequencia.php';
    $output = ob_get_clean();
    $response = json_decode($output, true);
    
    if ($response && $response['success']) {
        $frequenciaGeral = $response['data'];
    }
}

?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chamada - <?= htmlspecialchars($turma['nome']) ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <style>
        .chamada-container {
            background: #f8f9fa;
            min-height: 100vh;
            padding: 20px 0;
        }
        
        .chamada-header {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            padding: 20px;
        }
        
        .chamada-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .aluno-item {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            padding: 15px;
            transition: all 0.3s ease;
        }
        
        .aluno-item:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .aluno-item.presente {
            border-left: 4px solid #28a745;
            background: #f8fff9;
        }
        
        .aluno-item.ausente {
            border-left: 4px solid #dc3545;
            background: #fff8f8;
        }
        
        .aluno-item.sem-registro {
            border-left: 4px solid #6c757d;
        }
        
        .frequencia-badge {
            font-size: 0.8em;
            padding: 4px 8px;
            border-radius: 12px;
        }
        
        .frequencia-badge.alto {
            background: #d4edda;
            color: #155724;
        }
        
        .frequencia-badge.medio {
            background: #fff3cd;
            color: #856404;
        }
        
        .frequencia-badge.baixo {
            background: #f8d7da;
            color: #721c24;
        }
        
        .btn-presenca {
            min-width: 100px;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1050;
        }
        
        .stats-card {
            text-align: center;
            padding: 15px;
        }
        
        .stats-number {
            font-size: 2em;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .stats-label {
            color: #6c757d;
            font-size: 0.9em;
        }
        
        .aula-selector {
            background: #e9ecef;
            border-radius: 8px;
            padding: 10px;
            margin-bottom: 20px;
        }
        
        .observacao-input {
            font-size: 0.9em;
            resize: vertical;
            min-height: 60px;
        }
        
        .btn-lote {
            margin-bottom: 15px;
        }
        
        .auditoria-info {
            font-size: 0.8em;
            color: #6c757d;
            margin-top: 5px;
        }
        
        .status-turma {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 500;
        }
        
        .status-turma.ativo {
            background: #d4edda;
            color: #155724;
        }
        
        .status-turma.agendado {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .status-turma.encerrado {
            background: #f8d7da;
            color: #721c24;
        }
        
        /* CSS Responsivo para Mobile */
        @media (max-width: 767px) {
            .btn-presenca {
                min-width: 120px;
                padding: 10px 15px;
                font-size: 0.9rem;
            }
            
            .stats-card {
                padding: 10px 5px;
            }
            
            .stats-number {
                font-size: 1.5em;
            }
            
            .stats-label {
                font-size: 0.8em;
            }
            
            .aluno-item {
                padding: 12px;
                margin-bottom: 12px;
            }
            
            .chamada-header {
                padding: 15px;
            }
            
            .chamada-header h2 {
                font-size: 1.3rem;
            }
            
            .toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
            }
            
            .toast {
                max-width: 100%;
            }
            
            .btn-group {
                width: 100%;
            }
            
            .btn-group .btn {
                flex: 1;
            }
            
            .frequencia-badge {
                font-size: 0.75em;
                padding: 3px 6px;
            }
        }
    </style>
</head>
<body>
    <div class="chamada-container">
        <div class="container-fluid">
            <!-- Header da Chamada -->
            <div class="chamada-header">
                <!-- Aviso de turma concluída/cancelada -->
                <?php if (!$canEdit): ?>
                    <?php if ($turma['status'] === 'concluida'): ?>
                    <div class="alert alert-warning mb-3" role="alert">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Turma concluída:</strong> Esta turma está concluída. Apenas administração pode ajustar presenças.
                    </div>
                    <?php elseif ($turma['status'] === 'cancelada'): ?>
                    <div class="alert alert-danger mb-3" role="alert">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Turma cancelada:</strong> Não é possível editar presenças de turmas canceladas.
                    </div>
                    <?php elseif ($userType === 'instrutor' && $turma['instrutor_id'] != $userId): ?>
                    <div class="alert alert-info mb-3" role="alert">
                        <i class="fas fa-lock me-2"></i>
                        <strong>Sem permissão:</strong> Você não é o instrutor desta turma. Apenas visualização.
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                
                <div class="row align-items-center">
                    <div class="col-12 col-md-8">
                        <h2 class="mb-1">
                            <i class="fas fa-clipboard-check text-primary"></i>
                            Chamada - <?= htmlspecialchars($turma['nome']) ?>
                        </h2>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user-tie"></i> <?= htmlspecialchars($turma['instrutor_nome']) ?> |
                            <i class="fas fa-building"></i> <?= htmlspecialchars($turma['cfc_nome']) ?> |
                            <span class="status-turma <?= $turma['status'] ?>"><?= ucfirst($turma['status']) ?></span>
                        </p>
                        <?php if ($aulaAtual): ?>
                        <p class="text-muted mb-0">
                            <i class="fas fa-calendar"></i> <?= date('d/m/Y', strtotime($aulaAtual['data_aula'])) ?> |
                            <i class="fas fa-clock"></i> <?= $aulaAtual['duracao_minutos'] ?? 'N/A' ?> min |
                            <i class="fas fa-book"></i> <?= htmlspecialchars($aulaAtual['nome_aula']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-12 col-md-4 text-end mt-2 mt-md-0">
                        <!-- Links Contextuais -->
                        <div class="btn-group" role="group">
                            <a href="turma-diario.php?turma_id=<?= $turmaId ?>&aula_id=<?= $aulaId ?>" 
                               class="btn btn-outline-info btn-sm" title="Ir para Diário desta aula">
                                <i class="fas fa-book-open"></i> Diário
                            </a>
                            <?php if ($userType === 'admin'): ?>
                                <a href="turma-relatorios.php?turma_id=<?= $turmaId ?>" 
                                   class="btn btn-outline-success btn-sm" title="Relatórios da turma">
                                    <i class="fas fa-chart-bar"></i> Relatórios
                                </a>
                            <?php endif; ?>
                            <a href="turmas.php" class="btn btn-outline-secondary btn-sm" title="Voltar para Gestão de Turmas">
                                <i class="fas fa-arrow-left"></i> Voltar
                            </a>
                        </div>
                        <?php if ($canEdit): ?>
                        <button class="btn btn-primary ms-2" onclick="salvarChamada()">
                            <i class="fas fa-save"></i> Salvar Chamada
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Seletor de Aulas -->
            <?php if (count($aulas) > 1): ?>
            <div class="aula-selector">
                <div class="row align-items-center">
                    <div class="col-md-6">
                        <label class="form-label fw-bold">Aula:</label>
                        <select class="form-select" id="aulaSelector" onchange="trocarAula()">
                            <?php foreach ($aulas as $aula): ?>
                            <option value="<?= $aula['id'] ?>" <?= $aula['id'] == $aulaId ? 'selected' : '' ?>>
                                Aula <?= $aula['ordem'] ?> - <?= htmlspecialchars($aula['nome_aula']) ?>
                                (<?= date('d/m/Y', strtotime($aula['data_aula'])) ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <div class="btn-group" role="group">
                            <button class="btn btn-outline-primary btn-sm" onclick="navegarAula('anterior')">
                                <i class="fas fa-chevron-left"></i> Anterior
                            </button>
                            <button class="btn btn-outline-primary btn-sm" onclick="navegarAula('proxima')">
                                Próxima <i class="fas fa-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Estatísticas da Turma -->
            <div class="row mb-4">
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-primary"><?= $estatisticasTurma['total_alunos'] ?></div>
                        <div class="stats-label">Total de Alunos</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-success"><?= $estatisticasTurma['presentes'] ?></div>
                        <div class="stats-label">Presentes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-danger"><?= $estatisticasTurma['ausentes'] ?></div>
                        <div class="stats-label">Ausentes</div>
                    </div>
                </div>
                <div class="col-6 col-md-3 mb-3 mb-md-0">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-info"><?= $estatisticasTurma['frequencia_media'] ?>%</div>
                        <div class="stats-label">Frequência Média</div>
                    </div>
                </div>
            </div>

            <!-- Ações em Lote -->
            <?php if ($canEdit && !empty($alunos)): ?>
            <div class="chamada-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-tasks"></i> Ações em Lote
                    </h5>
                </div>
                <div class="card-body">
                    <div class="btn-lote">
                        <button class="btn btn-success btn-lote" onclick="marcarTodos('presente')">
                            <i class="fas fa-check-circle"></i> Marcar Todos como Presentes
                        </button>
                        <button class="btn btn-warning btn-lote" onclick="marcarTodos('ausente')">
                            <i class="fas fa-times-circle"></i> Marcar Todos como Ausentes
                        </button>
                        <button class="btn btn-secondary btn-lote" onclick="limparTodos()">
                            <i class="fas fa-eraser"></i> Limpar Todas as Marcações
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Lista de Alunos -->
            <div class="chamada-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-users"></i> Lista de Chamada
                        <?php if (!$canEdit): ?>
                        <span class="badge bg-warning ms-2">Somente Leitura</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($alunos)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Nenhum aluno matriculado</h5>
                        <p class="text-muted">Esta turma não possui alunos matriculados.</p>
                    </div>
                    <?php else: ?>
                    <div id="listaAlunos">
                        <?php foreach ($alunos as $aluno): ?>
                        <div class="aluno-item <?= $aluno['presenca_id'] ? ($aluno['presente'] ? 'presente' : 'ausente') : 'sem-registro' ?>" 
                             data-aluno-id="<?= $aluno['id'] ?>" 
                             data-presenca-id="<?= $aluno['presenca_id'] ?>"
                             data-frequencia-aluno-id="<?= $aluno['id'] ?>">
                            <!-- Layout Mobile-First: Empilhado em mobile, grid em desktop -->
                            <div class="row align-items-center">
                                <!-- Nome e CPF -->
                                <div class="col-12 col-md-4 mb-2 mb-md-0">
                                    <div class="d-flex align-items-center">
                                        <div class="me-2 me-md-3">
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($aluno['nome']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($aluno['cpf']) ?></small>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Status e Frequência (lado a lado em mobile) -->
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <span class="badge bg-<?= in_array($aluno['status_matricula'], ['cursando', 'matriculado']) ? 'success' : 'primary' ?>">
                                        <?= ucfirst($aluno['status_matricula']) ?>
                                    </span>
                                </div>
                                <div class="col-6 col-md-2 mb-2 mb-md-0">
                                    <?php 
                                    // Buscar frequência do aluno (priorizar frequencia_percentual direto do aluno, depois da API)
                                    $percentualFreq = null;
                                    
                                    // Primeiro, tentar usar frequencia_percentual direto do aluno (já vem na query)
                                    if (isset($aluno['frequencia_percentual']) && $aluno['frequencia_percentual'] !== null) {
                                        $percentualFreq = (float)$aluno['frequencia_percentual'];
                                    } 
                                    // Se não tiver, tentar buscar da API de frequência
                                    elseif ($frequenciaGeral && isset($frequenciaGeral['frequencias_alunos'])) {
                                        foreach ($frequenciaGeral['frequencias_alunos'] as $freq) {
                                            if ($freq['aluno']['id'] == $aluno['id']) {
                                                $percentualFreq = (float)$freq['estatisticas']['percentual_frequencia'];
                                                break;
                                            }
                                        }
                                    }
                                    
                                    if ($percentualFreq !== null):
                                        $frequenciaMinima = isset($turma['frequencia_minima']) ? (float)$turma['frequencia_minima'] : 75.0;
                                        $classe = 'baixo';
                                        if ($percentualFreq >= $frequenciaMinima) {
                                            $classe = 'alto';
                                        } elseif ($percentualFreq >= ($frequenciaMinima - 10)) {
                                            $classe = 'medio';
                                        }
                                    ?>
                                        <span class="frequencia-badge <?= $classe ?>" id="freq-badge-<?= $aluno['id'] ?>">
                                            <?= number_format($percentualFreq, 1) ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="frequencia-badge baixo" id="freq-badge-<?= $aluno['id'] ?>">
                                            N/A
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Botões de Presença -->
                                <div class="col-12 col-md-4 mt-2 mt-md-0">
                                    <?php if ($canEdit): ?>
                                    <div class="btn-group w-100 w-md-auto" role="group">
                                        <button class="btn btn-sm btn-outline-success btn-presenca <?= $aluno['presenca_id'] && $aluno['presente'] ? 'active' : '' ?>" 
                                                onclick="marcarPresenca(<?= $aluno['id'] ?>, true)"
                                                <?= !$canEdit ? 'disabled' : '' ?>>
                                            <i class="fas fa-check"></i> <span class="d-none d-md-inline">Presente</span>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-presenca <?= $aluno['presenca_id'] && !$aluno['presente'] ? 'active' : '' ?>" 
                                                onclick="marcarPresenca(<?= $aluno['id'] ?>, false)"
                                                <?= !$canEdit ? 'disabled' : '' ?>>
                                            <i class="fas fa-times"></i> <span class="d-none d-md-inline">Ausente</span>
                                        </button>
                                    </div>
                                    <?php else: ?>
                                    <div class="text-muted">
                                        <?php if ($aluno['presenca_id']): ?>
                                            <?= $aluno['presente'] ? '<i class="fas fa-check text-success"></i> Presente' : '<i class="fas fa-times text-danger"></i> Ausente' ?>
                                        <?php else: ?>
                                            <i class="fas fa-minus text-muted"></i> Sem registro
                                        <?php endif; ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Observação -->
                            <?php if ($aluno['presenca_id'] && $aluno['observacao_presenca']): ?>
                            <div class="row mt-2">
                                <div class="col-12">
                                    <small class="text-muted">
                                        <i class="fas fa-comment"></i> <?= htmlspecialchars($aluno['observacao_presenca']) ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Auditoria -->
                            <?php if ($aluno['presenca_id'] && $aluno['presenca_registrada_em']): ?>
                            <div class="row mt-1">
                                <div class="col-12">
                                    <div class="auditoria-info">
                                        <i class="fas fa-clock"></i> Registrado em <?= date('d/m/Y H:i', strtotime($aluno['presenca_registrada_em'])) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Modal de Observação -->
    <div class="modal fade" id="modalObservacao" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Observação</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <textarea class="form-control observacao-input" id="observacaoInput" 
                              placeholder="Digite uma observação sobre a presença/ausência..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="confirmarPresenca()">Confirmar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript -->
    <script>
        // Variáveis globais
        let turmaId = <?= $turmaId ?>;
        let aulaId = <?= $aulaId ?>;
        let canEdit = <?= $canEdit ? 'true' : 'false' ?>;
        let presencaPendente = null;
        let alteracoesPendentes = false;

        // Função para mostrar toast
        function mostrarToast(mensagem, tipo = 'success') {
            const toastContainer = document.getElementById('toastContainer');
            const toastId = 'toast-' + Date.now();
            
            const toastHtml = `
                <div class="toast" id="${toastId}" role="alert">
                    <div class="toast-header">
                        <i class="fas fa-${tipo === 'success' ? 'check-circle text-success' : 'exclamation-triangle text-warning'} me-2"></i>
                        <strong class="me-auto">Sistema</strong>
                        <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
                    </div>
                    <div class="toast-body">${mensagem}</div>
                </div>
            `;
            
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toast = new bootstrap.Toast(document.getElementById(toastId));
            toast.show();
            
            // Remover toast após 5 segundos
            setTimeout(() => {
                const toastElement = document.getElementById(toastId);
                if (toastElement) {
                    toastElement.remove();
                }
            }, 5000);
        }

        // Função para marcar presença individual
        function marcarPresenca(alunoId, presente) {
            if (!canEdit) {
                mostrarToast('Você não tem permissão para editar presenças', 'error');
                return;
            }

            // Verificar se já existe presença
            const alunoItem = document.querySelector(`[data-aluno-id="${alunoId}"]`);
            const presencaId = alunoItem.dataset.presencaId;
            
            if (presencaId) {
                // Atualizar presença existente
                atualizarPresenca(presencaId, presente);
            } else {
                // Criar nova presença
                criarPresenca(alunoId, presente);
            }
        }

        // Função para criar nova presença
        function criarPresenca(alunoId, presente) {
            const dados = {
                turma_id: turmaId,
                turma_aula_id: aulaId,
                aluno_id: alunoId,
                presente: presente
            };

            fetch('/admin/api/turma-presencas.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarToast('Presença registrada com sucesso!');
                    atualizarInterfaceAluno(alunoId, presente, data.presenca_id);
                    alteracoesPendentes = true;
                } else {
                    mostrarToast('Erro ao registrar presença: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            });
        }

        // Função para atualizar presença existente
        function atualizarPresenca(presencaId, presente) {
            const dados = {
                presente: presente
            };

            fetch(`/admin/api/turma-presencas.php?id=${presencaId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(dados)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    mostrarToast('Presença atualizada com sucesso!');
                    const alunoItem = document.querySelector(`[data-presenca-id="${presencaId}"]`);
                    const alunoId = alunoItem.dataset.alunoId;
                    atualizarInterfaceAluno(alunoId, presente, presencaId);
                    alteracoesPendentes = true;
                    // Atualizar frequência do aluno após atualizar presença
                    atualizarFrequenciaAluno(alunoId);
                } else {
                    mostrarToast('Erro ao atualizar presença: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
            });
        }
        
        // Função para atualizar frequência do aluno após marcar presença
        function atualizarFrequenciaAluno(alunoId) {
            // Buscar frequência atualizada via API
            fetch(`/admin/api/turma-frequencia.php?turma_id=${turmaId}&aluno_id=${alunoId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.estatisticas) {
                        const percentual = data.data.estatisticas.percentual_frequencia;
                        const badgeElement = document.getElementById(`freq-badge-${alunoId}`);
                        
                        if (badgeElement) {
                            // Atualizar valor
                            badgeElement.textContent = percentual.toFixed(1) + '%';
                            
                            // Atualizar classe (alto/médio/baixo)
                            badgeElement.className = 'frequencia-badge ';
                            // Frequência mínima padrão: 75%
                            const frequenciaMinima = 75.0;
                            if (percentual >= frequenciaMinima) {
                                badgeElement.className += 'alto';
                            } else if (percentual >= (frequenciaMinima - 10)) {
                                badgeElement.className += 'medio';
                            } else {
                                badgeElement.className += 'baixo';
                            }
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao atualizar frequência:', error);
                    // Não mostrar erro ao usuário, apenas logar
                });
        }

        // Função para atualizar interface do aluno
        function atualizarInterfaceAluno(alunoId, presente, presencaId) {
            const alunoItem = document.querySelector(`[data-aluno-id="${alunoId}"]`);
            
            // Atualizar classe do item
            alunoItem.className = `aluno-item ${presente ? 'presente' : 'ausente'}`;
            
            // Atualizar botões
            const btnPresente = alunoItem.querySelector('.btn-outline-success');
            const btnAusente = alunoItem.querySelector('.btn-outline-danger');
            
            if (presente) {
                btnPresente.classList.add('active');
                btnAusente.classList.remove('active');
            } else {
                btnPresente.classList.remove('active');
                btnAusente.classList.add('active');
            }
            
            // Atualizar presenca_id
            alunoItem.dataset.presencaId = presencaId;
            
            // Atualizar estatísticas
            atualizarEstatisticas();
        }

        // Função para marcar todos os alunos
        function marcarTodos(tipo) {
            if (!canEdit) {
                mostrarToast('Você não tem permissão para editar presenças', 'error');
                return;
            }

            const presente = tipo === 'presente';
            const alunos = document.querySelectorAll('[data-aluno-id]');
            const presencas = [];

            alunos.forEach(aluno => {
                const alunoId = aluno.dataset.alunoId;
                const presencaId = aluno.dataset.presencaId;
                
                if (presencaId) {
                    // Atualizar presença existente
                    atualizarPresenca(presencaId, presente);
                } else {
                    // Adicionar à lista de novas presenças
                    presencas.push({
                        aluno_id: parseInt(alunoId),
                        presente: presente
                    });
                }
            });

            // Processar novas presenças em lote
            if (presencas.length > 0) {
                const dados = {
                    turma_id: turmaId,
                    aula_id: aulaId, // Usar aula_id (nome correto)
                    presencas: presencas
                };

                fetch('/admin/api/turma-presencas.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(dados)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        mostrarToast(`Presenças processadas: ${data.sucessos} sucessos`);
                        if (data.erros && data.erros.length > 0) {
                            mostrarToast('Alguns erros: ' + data.erros.join(', '), 'error');
                        }
                        // Atualizar frequências de todos os alunos processados
                        presencas.forEach(presenca => {
                            atualizarFrequenciaAluno(presenca.aluno_id);
                        });
                        recarregarPagina();
                    } else {
                        mostrarToast('Erro ao processar presenças: ' + data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Erro:', error);
                    mostrarToast('Erro de conexão. Tente novamente.', 'error');
                });
            }
        }

        // Função para limpar todas as marcações
        function limparTodos() {
            if (!canEdit) {
                mostrarToast('Você não tem permissão para editar presenças', 'error');
                return;
            }

            if (!confirm('Tem certeza que deseja limpar todas as marcações?')) {
                return;
            }

            const alunos = document.querySelectorAll('[data-presenca-id]');
            let promises = [];

            alunos.forEach(aluno => {
                const presencaId = aluno.dataset.presencaId;
                if (presencaId) {
                    promises.push(
                        fetch(`/admin/api/turma-presencas.php?id=${presencaId}`, {
                            method: 'DELETE'
                        })
                    );
                }
            });

            Promise.all(promises)
            .then(responses => {
                const results = responses.map(r => r.json());
                return Promise.all(results);
            })
            .then(data => {
                mostrarToast('Todas as marcações foram removidas!');
                recarregarPagina();
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro ao limpar marcações', 'error');
            });
        }

        // Função para atualizar estatísticas
        function atualizarEstatisticas() {
            const alunos = document.querySelectorAll('[data-aluno-id]');
            let presentes = 0;
            let ausentes = 0;
            let semRegistro = 0;

            alunos.forEach(aluno => {
                const presencaId = aluno.dataset.presencaId;
                if (presencaId) {
                    if (aluno.classList.contains('presente')) {
                        presentes++;
                    } else {
                        ausentes++;
                    }
                } else {
                    semRegistro++;
                }
            });

            // Atualizar números na interface (com verificação de existência)
            const statsPresentes = document.querySelector('.stats-number.text-success');
            const statsAusentes = document.querySelector('.stats-number.text-danger');
            const statsFrequencia = document.querySelector('.stats-number.text-info');
            
            if (statsPresentes) {
                statsPresentes.textContent = presentes;
            }
            if (statsAusentes) {
                statsAusentes.textContent = ausentes;
            }
            
            // Calcular frequência média
            const totalRegistradas = presentes + ausentes;
            let frequenciaMedia = 0;
            if (totalRegistradas > 0) {
                frequenciaMedia = Math.round((presentes / totalRegistradas) * 100);
            }
            if (statsFrequencia) {
                statsFrequencia.textContent = frequenciaMedia + '%';
            }
        }

        // Função para trocar de aula
        function trocarAula() {
            const novoAulaId = document.getElementById('aulaSelector').value;
            if (novoAulaId != aulaId) {
                window.location.href = `?turma_id=${turmaId}&aula_id=${novoAulaId}`;
            }
        }

        // Função para navegar entre aulas
        function navegarAula(direcao) {
            const selector = document.getElementById('aulaSelector');
            const opcoes = Array.from(selector.options);
            const indiceAtual = opcoes.findIndex(opcao => opcao.value == aulaId);
            
            let novoIndice;
            if (direcao === 'anterior') {
                novoIndice = indiceAtual - 1;
            } else {
                novoIndice = indiceAtual + 1;
            }
            
            if (novoIndice >= 0 && novoIndice < opcoes.length) {
                selector.value = opcoes[novoIndice].value;
                trocarAula();
            }
        }

        // Função para salvar chamada (placeholder)
        function salvarChamada() {
            mostrarToast('Chamada salva automaticamente!');
            alteracoesPendentes = false;
        }

        // Função para recarregar página
        function recarregarPagina() {
            setTimeout(() => {
                window.location.reload();
            }, 1000);
        }

        // Avisar sobre alterações não salvas
        window.addEventListener('beforeunload', function(e) {
            if (alteracoesPendentes) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        // Inicialização
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Interface de chamada carregada');
            console.log('Turma ID:', turmaId);
            console.log('Aula ID:', aulaId);
            console.log('Pode editar:', canEdit);
        });
    </script>
</body>
</html>
