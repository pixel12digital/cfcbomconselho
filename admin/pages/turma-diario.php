<?php
/**
 * Detalhes da Turma - Visualização e Edição
 * Sistema de Turmas Teóricas - CFC Bom Conselho
 * 
 * @author Sistema CFC Bom Conselho
 * @version 2.0
 */

// Este arquivo é incluído pelo index.php, então as dependências já estão carregadas
// Verificar se está sendo incluído corretamente
if (!defined('ADMIN_ROUTING')) {
    die('Acesso negado');
}

// AJUSTE INSTRUTOR - FLUXO CHAMADA/DIARIO - Parâmetros da URL
$turmaId = $_GET['turma_id'] ?? null;
$aulaId = $_GET['aula_id'] ?? null;
$origem = $_GET['origem'] ?? null;

if (!$turmaId) {
    echo '<div class="alert alert-danger">ID da turma não informado.</div>';
    return;
}

// Buscar dados da turma
$turma = $db->fetch("
    SELECT 
        t.*,
        s.nome as sala_nome,
        c.nome as cfc_nome
    FROM turmas_teoricas t
    LEFT JOIN salas s ON t.sala_id = s.id
    LEFT JOIN cfcs c ON t.cfc_id = c.id
    WHERE t.id = ?
", [$turmaId]);

if (!$turma) {
    // Buscar turmas disponíveis para mostrar opções
    $turmasDisponiveis = $db->fetchAll("
        SELECT 
            t.id,
            t.nome,
            t.status,
            t.curso_tipo,
            s.nome as sala_nome,
            c.nome as cfc_nome
        FROM turmas_teoricas t
        LEFT JOIN salas s ON t.sala_id = s.id
        LEFT JOIN cfcs c ON t.cfc_id = c.id
        ORDER BY t.nome ASC
    ");
    
    echo '<div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-triangle"></i> Turma não encontrada</h5>
        <p>Não foi possível encontrar a turma com ID ' . htmlspecialchars($turmaId) . '.</p>';
    
    if (!empty($turmasDisponiveis)) {
        echo '<h6>Turmas disponíveis:</h6>
        <div class="row">';
        
        foreach ($turmasDisponiveis as $turmaDisponivel) {
            echo '<div class="col-md-6 mb-2">
                <div class="card">
                    <div class="card-body p-2">
                        <h6 class="card-title mb-1">
                            <a href="?page=turma-diario&turma_id=' . $turmaDisponivel['id'] . '" class="text-decoration-none">
                                ' . htmlspecialchars($turmaDisponivel['nome']) . '
                            </a>
                        </h6>
                        <small class="text-muted">
                            Curso: ' . htmlspecialchars($turmaDisponivel['curso_tipo'] ?? 'Não definido') . '<br>
                            Sala: ' . htmlspecialchars($turmaDisponivel['sala_nome'] ?? 'Não definida') . '<br>
                            CFC: ' . htmlspecialchars($turmaDisponivel['cfc_nome'] ?? 'Não definido') . '<br>
                            Status: <span class="badge bg-' . ($turmaDisponivel['status'] === 'ativo' ? 'success' : 'info') . '">' . ucfirst($turmaDisponivel['status']) . '</span>
                        </small>
                    </div>
                </div>
            </div>';
        }
        
        echo '</div>';
    } else {
        echo '<p><i class="fas fa-info-circle"></i> Nenhuma turma cadastrada no sistema ainda.</p>';
    }
    
    echo '</div>';
    return;
}

// Verificar se usuário tem permissão para esta turma
$canEdit = ($userType === 'admin' || $userType === 'instrutor');
if ($userType === 'instrutor' && $turma['criado_por'] != $userId) {
    $canEdit = false;
}

// Buscar alunos matriculados na turma
$alunosMatriculados = [];
try {
    // Primeiro, verificar se a tabela turma_matriculas existe
    $tabelaExiste = $db->fetch("SHOW TABLES LIKE 'turma_matriculas'");
    
    if ($tabelaExiste) {
        $alunosMatriculados = $db->fetchAll("
            SELECT 
                a.id,
                a.nome,
                a.cpf,
                a.email,
                a.telefone,
                a.data_nascimento,
                a.foto,
                tm.data_matricula,
                tm.status as status_matricula,
                tm.observacoes
            FROM turma_matriculas tm
            INNER JOIN alunos a ON tm.aluno_id = a.id
            WHERE tm.turma_id = ?
            ORDER BY a.nome ASC
        ", [$turmaId]);
    } else {
        // Se a tabela não existe, mostrar mensagem informativa
        error_log("Tabela turma_matriculas não encontrada no banco de dados");
    }
} catch (Exception $e) {
    error_log("Erro ao buscar alunos matriculados: " . $e->getMessage());
    $alunosMatriculados = [];
}

// Processar formulário de edição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $canEdit) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'editar_turma') {
        try {
            $dados = [
                'nome' => $_POST['nome'] ?? '',
                'observacoes' => $_POST['descricao'] ?? '',
                'data_inicio' => $_POST['data_inicio'] ?? '',
                'data_fim' => $_POST['data_fim'] ?? '',
                'max_alunos' => $_POST['max_alunos'] ?? 0,
                'status' => $_POST['status'] ?? 'agendado'
            ];
            
            $db->update('turmas_teoricas', $dados, 'id = ?', [$turmaId]);
            
            echo '<div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle"></i> Turma atualizada com sucesso!
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
            
            // Recarregar dados da turma
            $turma = $db->fetch("
                SELECT 
                    t.*,
                    s.nome as sala_nome,
                    c.nome as cfc_nome
                FROM turmas_teoricas t
                LEFT JOIN salas s ON t.sala_id = s.id
                LEFT JOIN cfcs c ON t.cfc_id = c.id
                WHERE t.id = ?
            ", [$turmaId]);
            
        } catch (Exception $e) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-triangle"></i> Erro ao atualizar turma: ' . htmlspecialchars($e->getMessage()) . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
        }
    }
}

?>

<!-- Page Header -->
<header class="page-header mb-4" role="banner" aria-labelledby="page-title">
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 id="page-title" class="h2 mb-1">
                <i class="fas fa-book-open text-primary" aria-hidden="true"></i>
                Diário de Turma
            </h1>
            <p class="text-muted mb-0" id="page-description">
                Visualize e edite informações da turma e alunos matriculados
            </p>
        </div>
        <nav aria-label="Navegação da página">
            <?php 
            // AJUSTE INSTRUTOR - FLUXO CHAMADA/DIARIO - Botão Voltar respeitando origem
            if ($origem === 'instrutor') {
                $backUrl = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/instrutor/dashboard.php';
                $backTitle = 'Voltar para Dashboard do Instrutor';
            } else {
                $backUrl = '?page=turmas-teoricas';
                $backTitle = 'Voltar para a lista de turmas';
            }
            ?>
            <a href="<?php echo htmlspecialchars($backUrl); ?>" 
               class="btn btn-outline-secondary" 
               role="button"
               aria-label="<?php echo htmlspecialchars($backTitle); ?>"
               title="<?php echo htmlspecialchars($backTitle); ?>">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> 
                <span>Voltar</span>
            </a>
        </nav>
    </div>
</header>

<!-- Detalhes da Turma -->
<section class="card mb-4" aria-labelledby="turma-detalhes-title">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <?php 
            // AJUSTE INSTRUTOR - FLUXO CHAMADA/DIARIO - Botão Voltar respeitando origem (segundo botão)
            if ($origem === 'instrutor') {
                $backUrl2 = (defined('BASE_PATH') ? rtrim(BASE_PATH, '/') : '') . '/instrutor/dashboard.php';
                $backTitle2 = 'Voltar para Dashboard do Instrutor';
            } else {
                $backUrl2 = '?page=turmas-teoricas';
                $backTitle2 = 'Voltar para gestão de turmas';
            }
            ?>
            <a href="<?php echo htmlspecialchars($backUrl2); ?>" 
               class="btn btn-outline-secondary btn-sm me-3" 
               aria-label="<?php echo htmlspecialchars($backTitle2); ?>"
               title="<?php echo htmlspecialchars($backTitle2); ?>">
                <i class="fas fa-arrow-left" aria-hidden="true"></i> 
                <span>Voltar</span>
            </a>
            <h2 id="turma-detalhes-title" class="h5 mb-0">
                <i class="fas fa-info-circle" aria-hidden="true"></i> 
                Detalhes da Turma
            </h2>
        </div>
        <?php if ($canEdit): ?>
        <button type="button" 
                class="btn btn-primary btn-sm" 
                onclick="abrirModalEditarTurma()"
                aria-label="Editar informações da turma"
                title="Editar informações da turma">
            <i class="fas fa-edit" aria-hidden="true"></i> 
            <span>Editar Turma</span>
        </button>
        <?php endif; ?>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4 fw-bold">Nome da Turma:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($turma['nome']) ?></dd>
                    
                    <dt class="col-sm-4 fw-bold">Sala:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($turma['sala_nome'] ?? 'Não definida') ?></dd>
                    
                    <dt class="col-sm-4 fw-bold">Tipo de Curso:</dt>
                    <dd class="col-sm-8">
                        <?php 
                        $cursoNome = match($turma['curso_tipo'] ?? '') {
                            'formacao_45h' => 'Formação de Condutores - 45h',
                            'formacao_acc_20h' => 'Formação de Condutores - ACC 20h',
                            'reciclagem_infrator' => 'Reciclagem para Condutor Infrator',
                            'atualizacao' => 'Curso de Atualização',
                            default => 'Não definido'
                        };
                        echo htmlspecialchars($cursoNome);
                        ?>
                    </dd>
                    
                    <dt class="col-sm-4 fw-bold">CFC:</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($turma['cfc_nome'] ?? 'Não definido') ?></dd>
                    
                    <dt class="col-sm-4 fw-bold">Status:</dt>
                    <dd class="col-sm-8">
                        <?php 
                        $statusNome = match($turma['status'] ?? '') {
                            'criando' => 'Criando',
                            'agendando' => 'Agendando',
                            'completa' => 'Completa',
                            'ativa' => 'Ativa',
                            'concluida' => 'Concluída',
                            'cancelada' => 'Cancelada',
                            default => 'Não definido'
                        };
                        $statusCor = match($turma['status'] ?? '') {
                            'ativa' => 'success',
                            'completa' => 'info',
                            'concluida' => 'primary',
                            'cancelada' => 'danger',
                            default => 'secondary'
                        };
                        ?>
                        <span class="badge bg-<?= $statusCor ?>" role="status" aria-label="Status da turma: <?= $statusNome ?>"><?= $statusNome ?></span>
                    </dd>
                </dl>
            </div>
            <div class="col-md-6">
                <dl class="row">
                    <dt class="col-sm-4 fw-bold">Data de Início:</dt>
                    <dd class="col-sm-8"><?= $turma['data_inicio'] ? date('d/m/Y', strtotime($turma['data_inicio'])) : 'Não definida' ?></dd>
                    
                    <dt class="col-sm-4 fw-bold">Data de Término:</dt>
                    <dd class="col-sm-8"><?= $turma['data_fim'] ? date('d/m/Y', strtotime($turma['data_fim'])) : 'Não definida' ?></dd>
                    
                    <dt class="col-sm-4 fw-bold">Máx. Alunos:</dt>
                    <dd class="col-sm-8"><?= $turma['max_alunos'] ?? 'Não definido' ?></dd>
                    
                    <dt class="col-sm-4 fw-bold">Alunos Matriculados:</dt>
                    <dd class="col-sm-8">
                        <strong aria-label="<?= count($alunosMatriculados) ?> alunos matriculados"><?= count($alunosMatriculados) ?></strong>
                    </dd>
                </dl>
            </div>
        </div>
        <?php if ($turma['observacoes']): ?>
        <div class="mt-3">
            <h3 class="h6 fw-bold">Observações:</h3>
            <p class="text-muted"><?= nl2br(htmlspecialchars($turma['observacoes'])) ?></p>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Alunos Matriculados -->
<section class="card" aria-labelledby="alunos-title">
    <div class="card-header">
        <h2 id="alunos-title" class="h5 mb-0">
            <i class="fas fa-users" aria-hidden="true"></i> 
            Alunos Matriculados 
            <span class="badge bg-primary" aria-label="<?= count($alunosMatriculados) ?> alunos matriculados"><?= count($alunosMatriculados) ?></span>
        </h2>
    </div>
    <div class="card-body">
        <?php if (empty($alunosMatriculados)): ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle" aria-hidden="true"></i> 
            Nenhum aluno matriculado nesta turma ainda.
            <?php if (isset($tabelaExiste) && !$tabelaExiste): ?>
            <br><small class="text-muted">Sistema de matrículas ainda não foi configurado.</small>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" role="table" aria-label="Lista de alunos matriculados">
                <caption class="visually-hidden">Tabela com informações dos alunos matriculados na turma</caption>
                <thead>
                    <tr role="row">
                        <th scope="col" role="columnheader">Foto</th>
                        <th scope="col" role="columnheader">Nome</th>
                        <th scope="col" role="columnheader">CPF</th>
                        <th scope="col" role="columnheader">E-mail</th>
                        <th scope="col" role="columnheader">Telefone</th>
                        <th scope="col" role="columnheader">Data Matrícula</th>
                        <th scope="col" role="columnheader">Status</th>
                        <?php if ($canEdit): ?>
                        <th scope="col" class="text-center" role="columnheader">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($alunosMatriculados as $aluno): ?>
                    <tr role="row">
                        <td role="cell">
                            <?php if ($aluno['foto']): ?>
                            <img src="../<?= htmlspecialchars($aluno['foto']) ?>" 
                                 alt="Foto do aluno <?= htmlspecialchars($aluno['nome']) ?>" 
                                 class="rounded-circle" 
                                 style="width: 40px; height: 40px; object-fit: cover;">
                            <?php else: ?>
                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" 
                                 style="width: 40px; height: 40px;"
                                 aria-label="Foto não disponível para <?= htmlspecialchars($aluno['nome']) ?>">
                                <i class="fas fa-user text-white" aria-hidden="true"></i>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td role="cell"><?= htmlspecialchars($aluno['nome']) ?></td>
                        <td role="cell"><?= htmlspecialchars($aluno['cpf']) ?></td>
                        <td role="cell">
                            <a href="mailto:<?= htmlspecialchars($aluno['email']) ?>" 
                               aria-label="Enviar e-mail para <?= htmlspecialchars($aluno['nome']) ?>">
                                <?= htmlspecialchars($aluno['email']) ?>
                            </a>
                        </td>
                        <td role="cell">
                            <a href="tel:<?= htmlspecialchars($aluno['telefone']) ?>" 
                               aria-label="Ligar para <?= htmlspecialchars($aluno['nome']) ?>">
                                <?= htmlspecialchars($aluno['telefone']) ?>
                            </a>
                        </td>
                        <td role="cell"><?= date('d/m/Y', strtotime($aluno['data_matricula'])) ?></td>
                        <td role="cell">
                            <span class="badge bg-<?= $aluno['status_matricula'] === 'ativa' ? 'success' : 'secondary' ?>" 
                                  role="status" 
                                  aria-label="Status da matrícula: <?= ucfirst($aluno['status_matricula']) ?>">
                                <?= ucfirst($aluno['status_matricula']) ?>
                            </span>
                        </td>
                        <?php if ($canEdit): ?>
                        <td role="cell" class="text-center">
                            <a href="?page=alunos&action=view&id=<?= $aluno['id'] ?>" 
                               class="btn btn-sm btn-outline-primary" 
                               title="Ver detalhes do aluno <?= htmlspecialchars($aluno['nome']) ?>"
                               aria-label="Ver detalhes do aluno <?= htmlspecialchars($aluno['nome']) ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span class="visually-hidden">Ver detalhes</span>
                            </a>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Modal para Editar Turma - Padrão CFC Bom Conselho -->
<?php if ($canEdit): ?>
<link href="assets/css/popup-reference.css" rel="stylesheet">
<style>
/* Estilos específicos para garantir funcionamento dos botões */
#modalEditarTurma .popup-modal-close,
#modalEditarTurma .popup-secondary-button,
#modalEditarTurma .popup-save-button {
    cursor: pointer !important;
    pointer-events: auto !important;
    z-index: 1000 !important;
    position: relative !important;
}

#modalEditarTurma .popup-modal-close:hover {
    background-color: rgba(255, 255, 255, 0.2) !important;
    transform: scale(1.1) !important;
}

#modalEditarTurma .popup-secondary-button:hover {
    background: #f8f9fa !important;
    border-color: #023A8D !important;
    color: #023A8D !important;
}

#modalEditarTurma .popup-save-button:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15) !important;
    background: linear-gradient(135deg, #1e5bb8 0%, #023A8D 100%) !important;
}

/* Garantir que o modal seja clicável */
#modalEditarTurma {
    pointer-events: auto !important;
    display: none !important;
}

#modalEditarTurma.show {
    display: flex !important;
    pointer-events: auto !important;
}

/* Garantir que o backdrop funcione */
#modalEditarTurma::before {
    content: '' !important;
    position: fixed !important;
    top: 0 !important;
    left: 0 !important;
    width: 100vw !important;
    height: 100vh !important;
    background-color: rgba(0, 0, 0, 0.5) !important;
    z-index: -1 !important;
}
</style>

<div class="popup-modal" id="modalEditarTurma">
    <div class="popup-modal-wrapper">
        
        <!-- HEADER -->
        <div class="popup-modal-header">
            <div class="header-content">
                <div class="header-icon">
                    <i class="fas fa-edit"></i>
                </div>
                <div class="header-text">
                    <h5>Editar Turma</h5>
                    <small>Modifique as informações da turma</small>
                </div>
            </div>
            <button type="button" class="popup-modal-close" onclick="fecharModalEditarTurma()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- CONTEÚDO -->
        <div class="popup-modal-content">
            <form method="POST" id="formEditarTurma">
                <input type="hidden" name="action" value="editar_turma">
                
                <!-- Seção de Informações Básicas -->
                <div class="popup-section-header">
                    <div class="popup-section-title">
                        <h6>Informações Básicas</h6>
                        <small>Dados principais da turma</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label for="nomeTurma" class="form-label">Nome da Turma *</label>
                        <input type="text" 
                               id="nomeTurma"
                               class="form-control" 
                               name="nome" 
                               value="<?= htmlspecialchars($turma['nome']) ?>" 
                               required
                               aria-describedby="nomeTurmaHelp">
                        <div id="nomeTurmaHelp" class="form-text">Nome que identifica a turma</div>
                    </div>
                    
                    <div class="col-md-12 mb-3">
                        <label for="observacoesTurma" class="form-label">Observações</label>
                        <textarea id="observacoesTurma"
                                  class="form-control" 
                                  name="descricao" 
                                  rows="3"
                                  aria-describedby="observacoesTurmaHelp"><?= htmlspecialchars($turma['observacoes'] ?? '') ?></textarea>
                        <div id="observacoesTurmaHelp" class="form-text">Informações adicionais sobre a turma</div>
                    </div>
                </div>

                <!-- Seção de Datas e Configurações -->
                <div class="popup-section-header mt-4">
                    <div class="popup-section-title">
                        <h6>Datas e Configurações</h6>
                        <small>Período e limites da turma</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="dataInicio" class="form-label">Data de Início</label>
                        <input type="date" 
                               id="dataInicio"
                               class="form-control" 
                               name="data_inicio" 
                               value="<?= $turma['data_inicio'] ?>"
                               aria-describedby="dataInicioHelp">
                        <div id="dataInicioHelp" class="form-text">Data de início das aulas</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="dataFim" class="form-label">Data de Término</label>
                        <input type="date" 
                               id="dataFim"
                               class="form-control" 
                               name="data_fim" 
                               value="<?= $turma['data_fim'] ?>"
                               aria-describedby="dataFimHelp">
                        <div id="dataFimHelp" class="form-text">Data de término das aulas</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="maxAlunos" class="form-label">Máx. Alunos</label>
                        <input type="number" 
                               id="maxAlunos"
                               class="form-control" 
                               name="max_alunos" 
                               value="<?= $turma['max_alunos'] ?? 0 ?>" 
                               min="0"
                               aria-describedby="maxAlunosHelp">
                        <div id="maxAlunosHelp" class="form-text">Número máximo de alunos na turma</div>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="statusTurma" class="form-label">Status</label>
                        <select id="statusTurma" 
                                class="form-select" 
                                name="status"
                                aria-describedby="statusTurmaHelp">
                            <option value="criando" <?= $turma['status'] === 'criando' ? 'selected' : '' ?>>Criando</option>
                            <option value="agendando" <?= $turma['status'] === 'agendando' ? 'selected' : '' ?>>Agendando</option>
                            <option value="completa" <?= $turma['status'] === 'completa' ? 'selected' : '' ?>>Completa</option>
                            <option value="ativa" <?= $turma['status'] === 'ativa' ? 'selected' : '' ?>>Ativa</option>
                            <option value="concluida" <?= $turma['status'] === 'concluida' ? 'selected' : '' ?>>Concluída</option>
                            <option value="cancelada" <?= $turma['status'] === 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        </select>
                        <div id="statusTurmaHelp" class="form-text">Status atual da turma</div>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- FOOTER -->
        <div class="popup-modal-footer">
            <div class="popup-footer-info">
                <small>
                    <i class="fas fa-info-circle"></i>
                    As alterações serão salvas permanentemente
                </small>
            </div>
            <div class="popup-footer-actions">
                <button type="button" class="popup-secondary-button" onclick="fecharModalEditarTurma()">
                    <i class="fas fa-times"></i>
                    Cancelar
                </button>
                <button type="button" class="popup-save-button" onclick="salvarEdicaoTurma()">
                    <i class="fas fa-save"></i>
                    Salvar Alterações
                </button>
            </div>
        </div>
        
    </div>
</div>

<script>
// Sistema de Modal Isolado - CFC Bom Conselho
(function() {
    'use strict';
    
    console.log('🔧 Inicializando sistema de modal isolado...');
    
    // Variáveis globais do modal
    let modalInstance = null;
    let isInitialized = false;
    
    // Função para inicializar o modal
    function initializeModal() {
        if (isInitialized) return;
        
        console.log('🔧 Inicializando modal de edição de turma...');
        
        // Verificar se o modal existe
        const modal = document.getElementById('modalEditarTurma');
        if (!modal) {
            console.error('❌ Modal não encontrado no DOM');
            return;
        }
        
        // Configurar event listeners
        setupEventListeners();
        
        // Marcar como inicializado
        isInitialized = true;
        console.log('✅ Modal inicializado com sucesso');
    }
    
    // Configurar event listeners
    function setupEventListeners() {
        // Event listener para ESC
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modalInstance && modalInstance.isOpen) {
                console.log('⌨️ Fechando modal com ESC');
                closeModal();
            }
        });
        
        // Event listener para clique no backdrop
        document.addEventListener('click', function(e) {
            if (e.target && e.target.classList.contains('popup-modal') && modalInstance && modalInstance.isOpen) {
                console.log('🖱️ Fechando modal clicando no backdrop');
                closeModal();
            }
        });
    }
    
    // Função para abrir modal
    function openModal() {
        console.log('📂 Abrindo modal de edição de turma...');
        
        const modal = document.getElementById('modalEditarTurma');
        if (!modal) {
            console.error('❌ Modal não encontrado');
            return;
        }
        
        // Criar instância do modal
        modalInstance = {
            element: modal,
            isOpen: true
        };
        
        // Mostrar modal
        modal.style.display = 'flex';
        modal.classList.add('show', 'popup-fade-in');
        document.body.style.overflow = 'hidden';
        
        console.log('✅ Modal aberto com sucesso');
    }
    
    // Função para fechar modal
    function closeModal() {
        console.log('❌ Fechando modal de edição de turma...');
        
        if (!modalInstance) {
            console.error('❌ Instância do modal não encontrada');
            return;
        }
        
        const modal = modalInstance.element;
        
        // Esconder modal
        modal.classList.remove('show', 'popup-fade-in');
        modal.style.display = 'none';
        document.body.style.overflow = 'auto';
        
        // Limpar instância
        modalInstance.isOpen = false;
        modalInstance = null;
        
        console.log('✅ Modal fechado com sucesso');
    }
    
    // Função para salvar
    function saveModal() {
        console.log('💾 Salvando edição da turma...');
        
        const form = document.getElementById('formEditarTurma');
        if (!form) {
            console.error('❌ Formulário não encontrado');
            alert('Erro: Formulário não encontrado.');
            return;
        }
        
        // Validar campos obrigatórios
        const nomeTurma = document.getElementById('nomeTurma');
        if (nomeTurma && !nomeTurma.value.trim()) {
            alert('Por favor, preencha o nome da turma.');
            nomeTurma.focus();
            return;
        }
        
        console.log('✅ Submetendo formulário...');
        form.submit();
    }
    
    // Expor funções globalmente
    window.abrirModalEditarTurma = openModal;
    window.fecharModalEditarTurma = closeModal;
    window.salvarEdicaoTurma = saveModal;
    
    // Inicializar quando DOM estiver pronto
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeModal);
    } else {
        initializeModal();
    }
    
    // Funções de compatibilidade
    function abrirModalEditarTurma() {
        openModal();
    }
    
    function fecharModalEditarTurma() {
        closeModal();
    }
    
    function salvarEdicaoTurma() {
        saveModal();
    }
    
    // Expor funções de compatibilidade
    window.abrirModalEditarTurma = abrirModalEditarTurma;
    window.fecharModalEditarTurma = fecharModalEditarTurma;
    window.salvarEdicaoTurma = salvarEdicaoTurma;
    
})();
</script>
<?php endif; ?>
