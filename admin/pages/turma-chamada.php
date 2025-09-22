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
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Buscar dados da turma
$turma = $db->fetch("
    SELECT 
        t.*,
        i.nome as instrutor_nome,
        c.nome as cfc_nome
    FROM turmas t
    LEFT JOIN instrutores i ON t.instrutor_id = i.id
    LEFT JOIN cfcs c ON t.cfc_id = c.id
    WHERE t.id = ?
", [$turmaId]);

if (!$turma) {
    header('Location: /admin/pages/turmas.php');
    exit();
}

// Verificar se usuário tem permissão para esta turma
if ($userType === 'instrutor' && $turma['instrutor_id'] != $userId) {
    $canEdit = false;
}

// Buscar aulas da turma
$aulas = $db->fetchAll("
    SELECT 
        ta.*,
        COUNT(tp.id) as presencas_registradas
    FROM turma_aulas ta
    LEFT JOIN turma_presencas tp ON ta.id = tp.turma_aula_id
    WHERE ta.turma_id = ?
    GROUP BY ta.id
    ORDER BY ta.ordem ASC
", [$turmaId]);

// Se não especificou aula, usar a primeira
if (!$aulaId && !empty($aulas)) {
    $aulaId = $aulas[0]['id'];
}

// Buscar dados da aula atual
$aulaAtual = null;
if ($aulaId) {
    $aulaAtual = $db->fetch("
        SELECT * FROM turma_aulas WHERE id = ? AND turma_id = ?
    ", [$aulaId, $turmaId]);
}

// Buscar alunos matriculados na turma
$alunos = $db->fetchAll("
    SELECT 
        a.*,
        ta.status as status_matricula,
        ta.data_matricula,
        tp.presente,
        tp.observacao as observacao_presenca,
        tp.registrado_em as presenca_registrada_em,
        tp.id as presenca_id
    FROM alunos a
    JOIN turma_alunos ta ON a.id = ta.aluno_id
    LEFT JOIN turma_presencas tp ON (
        a.id = tp.aluno_id 
        AND tp.turma_id = ? 
        AND tp.turma_aula_id = ?
    )
    WHERE ta.turma_id = ? 
    AND ta.status IN ('matriculado', 'ativo')
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
    </style>
</head>
<body>
    <div class="chamada-container">
        <div class="container-fluid">
            <!-- Header da Chamada -->
            <div class="chamada-header">
                <div class="row align-items-center">
                    <div class="col-md-8">
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
                            <i class="fas fa-clock"></i> <?= $aulaAtual['duracao_minutos'] ?> min |
                            <i class="fas fa-book"></i> <?= htmlspecialchars($aulaAtual['nome_aula']) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-4 text-end">
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
                <div class="col-md-3">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-primary"><?= $estatisticasTurma['total_alunos'] ?></div>
                        <div class="stats-label">Total de Alunos</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-success"><?= $estatisticasTurma['presentes'] ?></div>
                        <div class="stats-label">Presentes</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="chamada-card stats-card">
                        <div class="stats-number text-danger"><?= $estatisticasTurma['ausentes'] ?></div>
                        <div class="stats-label">Ausentes</div>
                    </div>
                </div>
                <div class="col-md-3">
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
                             data-presenca-id="<?= $aluno['presenca_id'] ?>">
                            <div class="row align-items-center">
                                <div class="col-md-4">
                                    <div class="d-flex align-items-center">
                                        <div class="me-3">
                                            <i class="fas fa-user-circle fa-2x text-muted"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= htmlspecialchars($aluno['nome']) ?></h6>
                                            <small class="text-muted"><?= htmlspecialchars($aluno['cpf']) ?></small>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <span class="badge bg-<?= $aluno['status_matricula'] === 'ativo' ? 'success' : 'primary' ?>">
                                        <?= ucfirst($aluno['status_matricula']) ?>
                                    </span>
                                </div>
                                <div class="col-md-2">
                                    <?php if ($frequenciaGeral && isset($frequenciaGeral['frequencias_alunos'])): ?>
                                        <?php 
                                        $freqAluno = null;
                                        foreach ($frequenciaGeral['frequencias_alunos'] as $freq) {
                                            if ($freq['aluno']['id'] == $aluno['id']) {
                                                $freqAluno = $freq;
                                                break;
                                            }
                                        }
                                        ?>
                                        <?php if ($freqAluno): ?>
                                            <?php 
                                            $percentual = $freqAluno['estatisticas']['percentual_frequencia'];
                                            $classe = 'baixo';
                                            if ($percentual >= $turma['frequencia_minima']) {
                                                $classe = 'alto';
                                            } elseif ($percentual >= ($turma['frequencia_minima'] - 10)) {
                                                $classe = 'medio';
                                            }
                                            ?>
                                            <span class="frequencia-badge <?= $classe ?>">
                                                <?= $percentual ?>%
                                            </span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-4">
                                    <?php if ($canEdit): ?>
                                    <div class="btn-group" role="group">
                                        <button class="btn btn-sm btn-outline-success btn-presenca <?= $aluno['presenca_id'] && $aluno['presente'] ? 'active' : '' ?>" 
                                                onclick="marcarPresenca(<?= $aluno['id'] ?>, true)">
                                            <i class="fas fa-check"></i> Presente
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger btn-presenca <?= $aluno['presenca_id'] && !$aluno['presente'] ? 'active' : '' ?>" 
                                                onclick="marcarPresenca(<?= $aluno['id'] ?>, false)">
                                            <i class="fas fa-times"></i> Ausente
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
                } else {
                    mostrarToast('Erro ao atualizar presença: ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                mostrarToast('Erro de conexão. Tente novamente.', 'error');
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
                    turma_aula_id: aulaId,
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

            // Atualizar números na interface
            document.querySelector('.stats-number.text-success').textContent = presentes;
            document.querySelector('.stats-number.text-danger').textContent = ausentes;
            
            // Calcular frequência média
            const totalRegistradas = presentes + ausentes;
            let frequenciaMedia = 0;
            if (totalRegistradas > 0) {
                frequenciaMedia = Math.round((presentes / totalRegistradas) * 100);
            }
            document.querySelector('.stats-number.text-info').textContent = frequenciaMedia + '%';
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
