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
$origem = $_GET['origem'] ?? '';

// Base da aplicação (raiz do projeto, sem /admin)
$baseApp = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/');
// URL padrão de volta para o dashboard do instrutor
$backUrlInstrutor = $baseApp . '/instrutor/dashboard.php';

if (!$turmaId) {
    if ($origem === 'instrutor') {
        header('Location: ' . $backUrlInstrutor);
    } else {
        header('Location: index.php?page=turmas-teoricas');
    }
    exit();
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
    if ($origem === 'instrutor') {
        header('Location: ' . $backUrlInstrutor);
    } else {
        header('Location: index.php?page=turmas-teoricas');
    }
    exit();
}

// AJUSTE IDENTIDADE INSTRUTOR - Lógica de permissão refinada (mesma de turma-chamada.php)
// Variáveis de controle claras
$modoSomenteLeitura = false;
$mostrarAlertaInstrutor = false;
$canEdit = true; // Valor padrão: pode editar

// Quando origem=instrutor, usar identidade do instrutor para verificar permissão
if ($origem === 'instrutor' || $userType === 'instrutor') {
    // Obter instrutor_id real do usuário logado
    $instrutorAtualId = getCurrentInstrutorId($userId);
    
    // Verificar se o instrutor tem aulas nesta turma
    if ($instrutorAtualId) {
        $temAula = $db->fetch(
            "SELECT COUNT(*) as total FROM turma_aulas_agendadas WHERE turma_id = ? AND instrutor_id = ?",
            [$turmaId, $instrutorAtualId]
        );
        if (!$temAula || $temAula['total'] == 0) {
            $modoSomenteLeitura = true;
            $mostrarAlertaInstrutor = true;
            $canEdit = false;
        } else {
            $modoSomenteLeitura = false;
            $mostrarAlertaInstrutor = false;
            $canEdit = true;
        }
    } else {
        // Não encontrou instrutor_id, modo somente leitura
        $modoSomenteLeitura = true;
        $mostrarAlertaInstrutor = true;
        $canEdit = false;
    }
    
    // Verificar regras adicionais: turma concluída/cancelada
    if ($turma['status'] === 'cancelada') {
        $modoSomenteLeitura = true;
        $mostrarAlertaInstrutor = false;
        $canEdit = false;
    } elseif ($turma['status'] === 'concluida') {
        $modoSomenteLeitura = true;
        $mostrarAlertaInstrutor = false;
        $canEdit = false;
    }
} else {
    // Fluxo admin normal - sempre pode editar (exceto turmas canceladas)
    $modoSomenteLeitura = false;
    $mostrarAlertaInstrutor = false;
    $canEdit = true;
    
    // Verificar regras adicionais: turma cancelada
    if ($turma['status'] === 'cancelada') {
        $modoSomenteLeitura = true;
        $canEdit = false;
    }
}

// AJUSTE 2025-12 - Buscar aulas agendadas da turma para exibir no diário
// Enriquecido com informações de presença
$aulasAgendadas = [];
try {
    // Buscar total de alunos matriculados na turma
    $totalAlunosTurma = $db->fetch("
        SELECT COUNT(*) as total
        FROM turma_matriculas
        WHERE turma_id = ? 
        AND status IN ('matriculado', 'cursando', 'concluido')
    ", [$turmaId]);
    $totalAlunos = (int)($totalAlunosTurma['total'] ?? 0);
    
    // Buscar aulas com contagem de presenças
    $aulasAgendadas = $db->fetchAll("
        SELECT 
            taa.id,
            taa.nome_aula,
            taa.disciplina,
            taa.data_aula,
            taa.hora_inicio,
            taa.hora_fim,
            taa.status as aula_status,
            taa.ordem_global,
            i.nome as instrutor_nome,
            COUNT(DISTINCT CASE WHEN tp.presente = 1 THEN tp.id END) as total_presentes,
            COUNT(DISTINCT CASE WHEN tp.presente = 0 THEN tp.id END) as total_ausentes,
            COUNT(DISTINCT tp.id) as total_registrados
        FROM turma_aulas_agendadas taa
        LEFT JOIN instrutores i ON taa.instrutor_id = i.id
        LEFT JOIN turma_presencas tp ON (
            tp.turma_aula_id = taa.id 
            AND tp.turma_id = taa.turma_id
        )
        WHERE taa.turma_id = ?
        GROUP BY taa.id, taa.nome_aula, taa.disciplina, taa.data_aula, 
                 taa.hora_inicio, taa.hora_fim, taa.status, taa.ordem_global, i.nome
        ORDER BY taa.ordem_global ASC, taa.data_aula ASC, taa.hora_inicio ASC
    ", [$turmaId]);
    
    // Adicionar total de alunos e calcular status da chamada para cada aula
    foreach ($aulasAgendadas as &$aula) {
        $aula['total_alunos'] = $totalAlunos;
        $aula['total_presentes'] = (int)($aula['total_presentes'] ?? 0);
        $aula['total_ausentes'] = (int)($aula['total_ausentes'] ?? 0);
        $aula['total_registrados'] = (int)($aula['total_registrados'] ?? 0);
        
        // Determinar status da chamada
        if ($aula['total_registrados'] == 0) {
            $aula['status_chamada'] = 'nao_iniciada';
            $aula['status_chamada_label'] = 'Não iniciada';
        } elseif ($aula['total_registrados'] < $totalAlunos) {
            $aula['status_chamada'] = 'em_andamento';
            $aula['status_chamada_label'] = 'Em andamento';
        } else {
            $aula['status_chamada'] = 'concluida';
            $aula['status_chamada_label'] = 'Concluída';
        }
    }
    unset($aula); // Limpar referência
} catch (Exception $e) {
    error_log("Erro ao buscar aulas agendadas: " . $e->getMessage());
    $aulasAgendadas = [];
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
// CORREÇÃO 2025-01: Apenas admin/secretaria podem editar turma
$podeEditarTurma = ($userType === 'admin' || $userType === 'secretaria') && $canEdit;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $podeEditarTurma) {
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
            // Botão Voltar respeitando origem
            $backUrl   = 'index.php?page=turmas-teoricas&acao=detalhes&turma_id=' . (int)$turmaId;
            $backTitle = 'Voltar para Gestão de Turmas';
            
            if ($origem === 'instrutor') {
                $backUrl   = $backUrlInstrutor;
                $backTitle = 'Voltar para Dashboard do Instrutor';
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

<!-- Aviso de turma concluída/cancelada ou permissão de instrutor -->
<?php if (!$canEdit || $mostrarAlertaInstrutor): ?>
    <?php if ($turma['status'] === 'concluida'): ?>
    <div class="alert alert-warning mb-3" role="alert">
        <i class="fas fa-info-circle me-2"></i>
        <strong>Turma concluída:</strong> Esta turma está concluída. Apenas administração pode ajustar informações.
    </div>
    <?php elseif ($turma['status'] === 'cancelada'): ?>
    <div class="alert alert-danger mb-3" role="alert">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Turma cancelada:</strong> Não é possível editar informações de turmas canceladas.
    </div>
    <?php elseif ($mostrarAlertaInstrutor): ?>
    <div class="alert alert-info mb-3" role="alert">
        <i class="fas fa-lock me-2"></i>
        <strong>Sem permissão:</strong> Você não é o instrutor desta turma. Apenas visualização.
    </div>
    <?php endif; ?>
<?php endif; ?>

<!-- Detalhes da Turma -->
<section class="card mb-4" aria-labelledby="turma-detalhes-title">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <?php 
            // Segundo botão Voltar (reaproveita variáveis do primeiro)
            $backUrl2   = $backUrl;
            $backTitle2 = $backTitle;
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
        <?php 
        // CORREÇÃO 2025-01: Botão "Editar Turma" apenas para admin/secretaria
        // Instrutores não devem editar informações da turma, apenas visualizar e marcar presenças
        $podeEditarTurma = ($userType === 'admin' || $userType === 'secretaria') && $canEdit;
        if ($podeEditarTurma): 
        ?>
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
                        <td role="cell">
                            <?php if ($origem === 'instrutor'): ?>
                                <a href="#" 
                                   onclick="visualizarAlunoInstrutor(<?= $aluno['id'] ?>, <?= $turmaId ?>); return false;" 
                                   class="text-decoration-none text-dark fw-bold"
                                   title="Ver detalhes do aluno">
                                    <?= htmlspecialchars($aluno['nome']) ?>
                                </a>
                            <?php else: ?>
                                <?= htmlspecialchars($aluno['nome']) ?>
                            <?php endif; ?>
                        </td>
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
                        <td role="cell" class="text-center">
                            <?php if ($origem === 'instrutor'): ?>
                                <!-- Instrutor: usar modal AJAX com endpoint restrito -->
                                <button type="button" 
                                        class="btn btn-sm btn-outline-primary" 
                                        onclick="visualizarAlunoInstrutor(<?= $aluno['id'] ?>, <?= $turmaId ?>)"
                                        title="Ver detalhes do aluno <?= htmlspecialchars($aluno['nome']) ?>"
                                        aria-label="Ver detalhes do aluno <?= htmlspecialchars($aluno['nome']) ?>">
                                    <i class="fas fa-user" aria-hidden="true"></i>
                                    <span class="d-none d-md-inline ms-1">Ver Aluno</span>
                                </button>
                            <?php elseif ($canEdit): ?>
                                <!-- AJUSTE 2025-12 - Admin/Secretaria: ir para histórico do aluno (com contexto da turma) -->
                                <a href="?page=historico-aluno&id=<?= $aluno['id'] ?>&turma_id=<?= $turmaId ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Ver histórico do aluno <?= htmlspecialchars($aluno['nome']) ?>"
                                   aria-label="Ver histórico do aluno <?= htmlspecialchars($aluno['nome']) ?>">
                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                    <span class="visually-hidden">Ver histórico</span>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Aulas Agendadas -->
<!-- AJUSTE 2025-12 - Seção para listar aulas e permitir acesso à chamada -->
<section class="card mb-4" aria-labelledby="aulas-title">
    <div class="card-header">
        <h2 id="aulas-title" class="h5 mb-0">
            <i class="fas fa-calendar-check" aria-hidden="true"></i> 
            Aulas Agendadas 
            <span class="badge bg-primary" aria-label="<?= count($aulasAgendadas) ?> aulas agendadas"><?= count($aulasAgendadas) ?></span>
        </h2>
    </div>
    <div class="card-body">
        <?php if (empty($aulasAgendadas)): ?>
        <div class="alert alert-info" role="alert">
            <i class="fas fa-info-circle" aria-hidden="true"></i> 
            Nenhuma aula agendada para esta turma ainda.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover" role="table" aria-label="Lista de aulas agendadas">
                <caption class="visually-hidden">Tabela com informações das aulas agendadas da turma</caption>
                <thead>
                    <tr role="row">
                        <th scope="col" role="columnheader">Data</th>
                        <th scope="col" role="columnheader">Horário</th>
                        <th scope="col" role="columnheader">Aula</th>
                        <th scope="col" role="columnheader">Disciplina</th>
                        <th scope="col" role="columnheader">Instrutor</th>
                        <th scope="col" role="columnheader">Status</th>
                        <!-- AJUSTE 2025-12 - Colunas de presença -->
                        <th scope="col" role="columnheader">Presenças</th>
                        <th scope="col" role="columnheader">Chamada</th>
                        <th scope="col" class="text-center" role="columnheader">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aulasAgendadas as $aula): ?>
                    <tr role="row">
                        <td role="cell"><?= date('d/m/Y', strtotime($aula['data_aula'])) ?></td>
                        <td role="cell">
                            <?= date('H:i', strtotime($aula['hora_inicio'])) ?> - 
                            <?= date('H:i', strtotime($aula['hora_fim'])) ?>
                        </td>
                        <td role="cell"><?= htmlspecialchars($aula['nome_aula'] ?? 'Aula ' . $aula['ordem_global']) ?></td>
                        <td role="cell">
                            <?php
                            $nomesDisciplinas = [
                                'legislacao_transito' => 'Legislação de Trânsito',
                                'direcao_defensiva' => 'Direção Defensiva',
                                'primeiros_socorros' => 'Primeiros Socorros',
                                'meio_ambiente_cidadania' => 'Meio Ambiente e Cidadania',
                                'mecanica_basica' => 'Mecânica Básica'
                            ];
                            $disciplinaNome = $nomesDisciplinas[$aula['disciplina']] ?? ucfirst(str_replace('_', ' ', $aula['disciplina']));
                            echo htmlspecialchars($disciplinaNome);
                            ?>
                        </td>
                        <td role="cell"><?= htmlspecialchars($aula['instrutor_nome'] ?? 'Não definido') ?></td>
                        <td role="cell">
                            <span class="badge bg-<?= $aula['aula_status'] === 'realizada' ? 'success' : ($aula['aula_status'] === 'cancelada' ? 'danger' : 'info') ?>" 
                                  role="status" 
                                  aria-label="Status da aula: <?= ucfirst($aula['aula_status']) ?>">
                                <?= ucfirst($aula['aula_status']) ?>
                            </span>
                        </td>
                        <!-- AJUSTE 2025-12 - Coluna de presenças -->
                        <td role="cell">
                            <?php if ($aula['total_alunos'] > 0): ?>
                                <span class="badge bg-<?= $aula['total_presentes'] > 0 ? 'success' : 'secondary' ?>">
                                    <?= $aula['total_presentes'] ?>/<?= $aula['total_alunos'] ?>
                                </span>
                                <small class="text-muted d-block mt-1">
                                    <?= $aula['total_ausentes'] > 0 ? $aula['total_ausentes'] . ' ausente(s)' : '' ?>
                                </small>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <!-- AJUSTE 2025-12 - Coluna de status da chamada -->
                        <td role="cell">
                            <?php
                            $statusChamadaClass = match($aula['status_chamada']) {
                                'concluida' => 'success',
                                'em_andamento' => 'warning',
                                default => 'secondary'
                            };
                            ?>
                            <span class="badge bg-<?= $statusChamadaClass ?>" 
                                  role="status" 
                                  aria-label="Status da chamada: <?= htmlspecialchars($aula['status_chamada_label']) ?>">
                                <?= htmlspecialchars($aula['status_chamada_label']) ?>
                            </span>
                        </td>
                        <td role="cell" class="text-center">
                            <!-- AJUSTE 2025-12 - Link para Chamada (admin/secretaria sem origem=instrutor) -->
                            <?php if ($origem !== 'instrutor'): ?>
                                <a href="?page=turma-chamada&turma_id=<?= $turmaId ?>&aula_id=<?= $aula['id'] ?>" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Abrir chamada da aula"
                                   aria-label="Abrir chamada da aula de <?= htmlspecialchars($aula['nome_aula'] ?? 'Aula ' . $aula['ordem_global']) ?>">
                                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                                    <span class="d-none d-md-inline ms-1">Chamada</span>
                                </a>
                            <?php else: ?>
                                <!-- Instrutor: usar link com origem=instrutor -->
                                <a href="?page=turma-chamada&turma_id=<?= $turmaId ?>&aula_id=<?= $aula['id'] ?>&origem=instrutor" 
                                   class="btn btn-sm btn-outline-primary" 
                                   title="Abrir chamada da aula"
                                   aria-label="Abrir chamada da aula de <?= htmlspecialchars($aula['nome_aula'] ?? 'Aula ' . $aula['ordem_global']) ?>">
                                    <i class="fas fa-clipboard-check" aria-hidden="true"></i>
                                    <span class="d-none d-md-inline ms-1">Chamada</span>
                                </a>
                            <?php endif; ?>
                        </td>
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

<?php if ($origem === 'instrutor'): ?>
<!-- Modal para Visualizar Aluno (Modo Instrutor) -->
<div class="modal fade" id="modalAlunoInstrutor" tabindex="-1" aria-labelledby="modalAlunoInstrutorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalAlunoInstrutorLabel">
                    <i class="fas fa-user"></i> Detalhes do Aluno
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body" id="modalAlunoInstrutorBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2">Carregando informações do aluno...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<script>
function visualizarAlunoInstrutor(alunoId, turmaId) {
    const modal = new bootstrap.Modal(document.getElementById('modalAlunoInstrutor'));
    const modalBody = document.getElementById('modalAlunoInstrutorBody');
    
    // Mostrar loading
    modalBody.innerHTML = `
        <div class="text-center py-4">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Carregando...</span>
            </div>
            <p class="mt-2">Carregando informações do aluno...</p>
        </div>
    `;
    
    // Abrir modal
    modal.show();
    
    // Buscar dados do aluno via endpoint restrito
    fetch(`../admin/api/aluno-detalhes-instrutor.php?aluno_id=${alunoId}&turma_id=${turmaId}`)
        .then(response => {
            if (!response.ok) {
                if (response.status === 403) {
                    throw new Error('Você não tem permissão para visualizar este aluno');
                }
                throw new Error('Erro ao carregar dados do aluno');
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const aluno = data.aluno;
                const turma = data.turma;
                const matricula = data.matricula;
                const frequencia = data.frequencia;
                
                // Formatar CPF
                function formatarCPF(cpf) {
                    if (!cpf) return 'Não informado';
                    const cpfLimpo = cpf.replace(/\D/g, '');
                    return cpfLimpo.replace(/(\d{3})(\d{3})(\d{3})(\d{2})/, '$1.$2.$3-$4');
                }
                
                // Formatar telefone
                function formatarTelefone(tel) {
                    if (!tel) return 'Não informado';
                    const telLimpo = tel.replace(/\D/g, '');
                    if (telLimpo.length === 11) {
                        return telLimpo.replace(/(\d{2})(\d{5})(\d{4})/, '($1) $2-$3');
                    } else if (telLimpo.length === 10) {
                        return telLimpo.replace(/(\d{2})(\d{4})(\d{4})/, '($1) $2-$3');
                    }
                    return tel;
                }
                
                // Formatar data de nascimento
                const dataNasc = aluno.data_nascimento ? new Date(aluno.data_nascimento).toLocaleDateString('pt-BR') : 'Não informado';
                
                // Categoria CNH
                const categoriaCNH = aluno.categoria_cnh || 'Não informado';
                
                // Formatar frequência
                const freqPercent = frequencia ? frequencia.frequencia_percentual.toFixed(1) : '0.0';
                const freqBadgeClass = freqPercent >= 75 ? 'bg-success' : (freqPercent >= 60 ? 'bg-warning' : 'bg-danger');
                
                // Montar HTML
                let historicoHtml = '';
                if (frequencia && frequencia.historico && frequencia.historico.length > 0) {
                    historicoHtml = `
                        <h6 class="mt-3 mb-2">Últimas Presenças:</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Data</th>
                                        <th>Aula</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${frequencia.historico.map(p => `
                                        <tr>
                                            <td>${new Date(p.data_aula).toLocaleDateString('pt-BR')}</td>
                                            <td>${p.nome_aula || 'N/A'}</td>
                                            <td>
                                                <span class="badge ${p.presente ? 'bg-success' : 'bg-danger'}">
                                                    ${p.presente ? 'PRESENTE' : 'AUSENTE'}
                                                </span>
                                            </td>
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    `;
                } else {
                    historicoHtml = '<p class="text-muted mt-3">Nenhuma presença registrada ainda.</p>';
                }
                
                modalBody.innerHTML = `
                    <div class="text-center mb-3">
                        ${aluno.foto && aluno.foto.trim() !== '' 
                            ? `<img src="../${aluno.foto}" 
                                   alt="Foto do aluno ${aluno.nome}" 
                                   class="rounded-circle" 
                                   style="width: 100px; height: 100px; object-fit: cover; border: 3px solid #dee2e6;"
                                   onerror="this.outerHTML='<div class=\\'rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto\\' style=\\'width:100px;height:100px;border:3px solid #dee2e6;\\'><i class=\\'fas fa-user fa-3x text-white\\'></i></div>'">`
                            : `<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center mx-auto" 
                                   style="width: 100px; height: 100px; border: 3px solid #dee2e6;">
                                    <i class="fas fa-user fa-3x text-white"></i>
                                  </div>`
                        }
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Dados Pessoais</h6>
                            <dl class="row mb-3">
                                <dt class="col-sm-4">Nome:</dt>
                                <dd class="col-sm-8"><strong>${aluno.nome}</strong></dd>
                                
                                <dt class="col-sm-4">CPF:</dt>
                                <dd class="col-sm-8">${formatarCPF(aluno.cpf)}</dd>
                                
                                <dt class="col-sm-4">Data Nascimento:</dt>
                                <dd class="col-sm-8">${dataNasc}</dd>
                                
                                <dt class="col-sm-4">Categoria CNH:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-info">${categoriaCNH}</span>
                                </dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h6>Contato</h6>
                            <dl class="row mb-3">
                                <dt class="col-sm-4">E-mail:</dt>
                                <dd class="col-sm-8">
                                    ${aluno.email ? `<a href="mailto:${aluno.email}">${aluno.email}</a>` : 'Não informado'}
                                </dd>
                                
                                <dt class="col-sm-4">Telefone:</dt>
                                <dd class="col-sm-8">
                                    ${aluno.telefone ? `
                                        <a href="tel:${aluno.telefone.replace(/\D/g, '')}">${formatarTelefone(aluno.telefone)}</a>
                                        <a href="https://wa.me/55${aluno.telefone.replace(/\D/g, '')}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-success ms-2" 
                                           title="Abrir WhatsApp">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    ` : 'Não informado'}
                                </dd>
                            </dl>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6>Matrícula na Turma</h6>
                            <dl class="row mb-3">
                                <dt class="col-sm-4">Turma:</dt>
                                <dd class="col-sm-8">${turma.nome}</dd>
                                
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-primary">${matricula.status}</span>
                                </dd>
                                
                                <dt class="col-sm-4">Data Matrícula:</dt>
                                <dd class="col-sm-8">${new Date(matricula.data_matricula).toLocaleDateString('pt-BR')}</dd>
                                
                                <dt class="col-sm-4">Frequência:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge ${freqBadgeClass}">${freqPercent}%</span>
                                    <small class="text-muted ms-2">
                                        (${frequencia.total_presentes} presentes / ${frequencia.total_aulas} aulas)
                                    </small>
                                </dd>
                            </dl>
                            
                            ${historicoHtml}
                        </div>
                    </div>
                `;
            } else {
                modalBody.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle"></i> ${data.message || 'Erro ao carregar dados do aluno'}
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Erro:', error);
            modalBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle"></i> ${error.message || 'Erro ao carregar dados do aluno'}
                </div>
            `;
        });
}
</script>
<?php endif; ?>
