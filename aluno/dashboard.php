<?php
/**
 * Dashboard do Aluno - Mobile First + PWA
 * Interface focada em usabilidade móvel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação específica para aluno
if (!isset($_SESSION['aluno_id']) || $_SESSION['user_type'] !== 'aluno') {
    header('Location: /login.php?type=aluno');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Buscar dados do aluno - primeiro na tabela usuarios, depois na tabela alunos
$aluno = $db->fetch("SELECT * FROM usuarios WHERE id = ? AND tipo = 'aluno'", [$_SESSION['aluno_id']]);

if (!$aluno) {
    // Se não encontrar na tabela usuarios, buscar na tabela alunos
    $aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$_SESSION['aluno_id']]);
}

if (!$aluno) {
    header('Location: /login.php?type=aluno');
    exit();
}

// Buscar próximas aulas (próximos 14 dias) - se as tabelas existirem
$proximasAulas = [];
try {
    $proximasAulas = $db->fetchAll("
        SELECT a.*, 
               i.nome as instrutor_nome,
               v.modelo as veiculo_modelo, v.placa as veiculo_placa
        FROM aulas a
        JOIN instrutores i ON a.instrutor_id = i.id
        LEFT JOIN veiculos v ON a.veiculo_id = v.id
        WHERE a.aluno_id = ?
          AND a.data_aula >= CURDATE() 
          AND a.data_aula <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
          AND a.status != 'cancelada'
        ORDER BY a.data_aula ASC, a.hora_inicio ASC
        LIMIT 10
    ", [$_SESSION['aluno_id']]);
} catch (Exception $e) {
    // Tabelas não existem ou erro na query
    error_log("[DASHBOARD ALUNO] Erro ao buscar aulas: " . $e->getMessage());
    $proximasAulas = [];
}

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($_SESSION['aluno_id'], 'aluno');

// Buscar status dos exames (se a tabela existir)
$exames = [];
try {
    $exames = $db->fetchAll("
        SELECT tipo, status, data_exame
        FROM exames 
        WHERE aluno_id = ? 
        ORDER BY data_exame DESC
    ", [$_SESSION['aluno_id']]);
} catch (Exception $e) {
    // Tabela exames não existe ou erro na query
    error_log("[DASHBOARD ALUNO] Erro ao buscar exames: " . $e->getMessage());
    $exames = [];
}

// Verificar guardas de negócio
$guardaExames = true;
$guardaFinanceiro = true;

foreach ($exames as $exame) {
    if (in_array($exame['tipo'], ['medico', 'psicologico']) && $exame['status'] !== 'aprovado') {
        $guardaExames = false;
        break;
    }
}

// Configurar variáveis para o layout
$pageTitle = 'Dashboard - ' . htmlspecialchars($aluno['nome']);
$homeUrl = '/aluno/dashboard.php';

// Dados do usuário para o layout
$user = [
    'nome' => $aluno['nome'],
    'tipo' => 'aluno'
];

// Conteúdo da página
ob_start();
?>
<!-- Conteúdo do Dashboard -->
<div class="container-fluid py-4">
    <!-- Cabeçalho de Boas-vindas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <i class="fas fa-user-graduate text-primary" style="font-size: 3rem;"></i>
                    </div>
                    <h1 class="h3 mb-2 text-primary">
                        Olá, <?php echo htmlspecialchars($aluno['nome']); ?>!
                    </h1>
                    <p class="text-muted mb-0">Acompanhe suas aulas e progresso no curso</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notificações -->
    <?php if (!empty($notificacoesNaoLidas)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Notificações
                        </h5>
                        <span class="badge bg-light text-primary"><?php echo count($notificacoesNaoLidas); ?></span>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php foreach ($notificacoesNaoLidas as $notificacao): ?>
                    <div class="border-bottom p-3" data-id="<?php echo $notificacao['id']; ?>">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1"><?php echo htmlspecialchars($notificacao['titulo']); ?></h6>
                                <p class="text-muted mb-1 small"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
                                <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($notificacao['criado_em'])); ?></small>
                            </div>
                            <button class="btn btn-sm btn-outline-primary marcar-lida" data-id="<?php echo $notificacao['id']; ?>">
                                <i class="fas fa-check"></i>
                            </button>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Status do Processo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-route me-2"></i>
                        Meu Progresso
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Exames Médico e Psicológico -->
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-stethoscope text-success fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Exames Médico e Psicológico</h6>
                                    <span class="badge bg-success">Aprovados</span>
                                </div>
                            </div>
                        </div>

                        <!-- Aulas Teóricas -->
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-book text-success fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Aulas Teóricas</h6>
                                    <span class="badge bg-success">Liberadas</span>
                                </div>
                            </div>
                        </div>

                        <!-- Aulas Práticas -->
                        <div class="col-md-4 mb-3">
                            <div class="d-flex align-items-center p-3 border rounded">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-car text-secondary fs-3"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="mb-1">Aulas Práticas</h6>
                                    <span class="badge bg-secondary">Após prova teórica</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Próximas Aulas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Próximas Aulas
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($proximasAulas)): ?>
                        <?php foreach ($proximasAulas as $aula): ?>
                        <div class="border-bottom pb-3 mb-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1">
                                        <i class="fas fa-car me-2"></i>
                                        <?php echo htmlspecialchars($aula['tipo'] ?? 'Aula Prática'); ?>
                                    </h6>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-calendar me-2"></i>
                                        <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?>
                                        às <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?>
                                    </p>
                                    <p class="text-muted mb-1">
                                        <i class="fas fa-user me-2"></i>
                                        Instrutor: <?php echo htmlspecialchars($aula['instrutor_nome'] ?? 'N/A'); ?>
                                    </p>
                                    <?php if (!empty($aula['veiculo_modelo'])): ?>
                                    <p class="text-muted mb-0">
                                        <i class="fas fa-car me-2"></i>
                                        Veículo: <?php echo htmlspecialchars($aula['veiculo_modelo']); ?>
                                        (<?php echo htmlspecialchars($aula['veiculo_placa']); ?>)
                                    </p>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-primary"><?php echo ucfirst($aula['status']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times text-muted fs-1 mb-3"></i>
                            <h6 class="text-muted">Nenhuma aula agendada</h6>
                            <p class="text-muted small">Você não possui aulas agendadas para os próximos 14 dias.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Ações Rápidas -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-bolt me-2"></i>
                        Ações Rápidas
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 col-6 mb-3">
                            <a href="/aluno/aulas.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-calendar-alt mb-2 d-block"></i>
                                Minhas Aulas
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="/aluno/financeiro.php" class="btn btn-outline-success w-100">
                                <i class="fas fa-credit-card mb-2 d-block"></i>
                                Financeiro
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="/aluno/documentos.php" class="btn btn-outline-info w-100">
                                <i class="fas fa-file-alt mb-2 d-block"></i>
                                Documentos
                            </a>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <a href="/aluno/suporte.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-headset mb-2 d-block"></i>
                                Suporte
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
$pageContent = ob_get_clean();

// Incluir layout mobile-first
include __DIR__ . '/../includes/layout/mobile-first.php';
?>