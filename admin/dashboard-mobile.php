<?php
/**
 * Dashboard do Admin/Secretária - Mobile First + PWA
 * Interface focada em usabilidade móvel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || !in_array($user['tipo'], ['admin', 'secretaria'])) {
    header('Location: /login.php');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Buscar dados do usuário
$usuario = $db->fetch("SELECT * FROM usuarios WHERE id = ?", [$user['id']]);

// Buscar estatísticas do dia
$hoje = date('Y-m-d');
$estatisticasHoje = $db->fetch("
    SELECT 
        COUNT(*) as total_aulas,
        SUM(CASE WHEN status = 'agendada' THEN 1 ELSE 0 END) as aulas_agendadas,
        SUM(CASE WHEN status = 'realizada' THEN 1 ELSE 0 END) as aulas_realizadas,
        SUM(CASE WHEN status = 'cancelada' THEN 1 ELSE 0 END) as aulas_canceladas
    FROM aulas 
    WHERE data_aula = ?
", [$hoje]);

// Buscar próximas aulas (próximos 7 dias)
$proximasAulas = $db->fetchAll("
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           i.nome as instrutor_nome,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    JOIN instrutores i ON a.instrutor_id = i.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.data_aula >= ?
      AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
      AND a.status != 'cancelada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 10
", [$hoje, $hoje]);

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($user['id'], $user['tipo']);

// Buscar solicitações pendentes
$solicitacoesPendentes = $db->fetchAll("
    SELECT sa.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           a.data_aula, a.hora_inicio, a.tipo_aula,
           i.nome as instrutor_nome
    FROM solicitacoes_aluno sa
    JOIN alunos al ON sa.aluno_id = al.id
    JOIN aulas a ON sa.aula_id = a.id
    JOIN instrutores i ON a.instrutor_id = i.id
    WHERE sa.status = 'pendente'
    ORDER BY sa.criado_em DESC
    LIMIT 10
");

// Buscar alunos com pendências
$alunosComPendencias = $db->fetchAll("
    SELECT a.*, 
           COUNT(CASE WHEN f.status = 'pendente' THEN 1 END) as faturas_pendentes,
           COUNT(CASE WHEN e.status = 'pendente' THEN 1 END) as exames_pendentes
    FROM alunos a
    LEFT JOIN faturas f ON a.id = f.aluno_id
    LEFT JOIN exames e ON a.id = e.aluno_id
    GROUP BY a.id
    HAVING faturas_pendentes > 0 OR exames_pendentes > 0
    ORDER BY faturas_pendentes DESC, exames_pendentes DESC
    LIMIT 5
");

// Configurar variáveis para o layout
$pageTitle = 'Dashboard - ' . htmlspecialchars($usuario['nome']);
$homeUrl = '/admin/index.php';

// Incluir layout mobile-first
ob_start();
?>

<!-- Conteúdo do Dashboard -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h2 class="card-title fs-mobile-2">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Olá, <?php echo htmlspecialchars($usuario['nome']); ?>!
                </h2>
                <p class="card-subtitle text-muted mb-0">Visão geral do sistema</p>
            </div>
        </div>
    </div>
</div>

<!-- Notificações -->
<?php if (!empty($notificacoesNaoLidas)): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title fs-mobile-3 mb-0">
                    <i class="fas fa-bell me-2"></i>
                    Notificações
                </h3>
                <span class="badge bg-primary"><?php echo count($notificacoesNaoLidas); ?></span>
            </div>
            <div class="card-body p-0">
                <?php foreach ($notificacoesNaoLidas as $notificacao): ?>
                <div class="border-bottom p-3" data-id="<?php echo $notificacao['id']; ?>">
                    <div class="d-flex justify-content-between align-items-start">
                        <div class="flex-grow-1">
                            <h6 class="mb-1"><?php echo htmlspecialchars($notificacao['titulo']); ?></h6>
                            <p class="text-muted mb-1 fs-mobile-6"><?php echo htmlspecialchars($notificacao['mensagem']); ?></p>
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

<!-- Estatísticas de Hoje -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-calendar-day me-2"></i>
                    Estatísticas de Hoje
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-number text-primary"><?php echo $estatisticasHoje['total_aulas']; ?></div>
                            <div class="stat-label">Total de Aulas</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-number text-success"><?php echo $estatisticasHoje['aulas_realizadas']; ?></div>
                            <div class="stat-label">Realizadas</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-number text-warning"><?php echo $estatisticasHoje['aulas_agendadas']; ?></div>
                            <div class="stat-label">Agendadas</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-card text-center">
                            <div class="stat-number text-danger"><?php echo $estatisticasHoje['aulas_canceladas']; ?></div>
                            <div class="stat-label">Canceladas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Solicitações Pendentes -->
<?php if (!empty($solicitacoesPendentes)): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-clock me-2"></i>
                    Solicitações Pendentes
                </h3>
            </div>
            <div class="card-body">
                <div class="solicitacao-list">
                    <?php foreach ($solicitacoesPendentes as $solicitacao): ?>
                    <div class="card mb-3 solicitacao-item" data-solicitacao-id="<?php echo $solicitacao['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-<?php echo $solicitacao['tipo_solicitacao'] === 'reagendamento' ? 'info' : 'warning'; ?> mb-1">
                                        <?php echo ucfirst($solicitacao['tipo_solicitacao']); ?>
                                    </span>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($solicitacao['aluno_nome']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($solicitacao['data_aula'])); ?> - 
                                        <?php echo date('H:i', strtotime($solicitacao['hora_inicio'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-warning">Pendente</span>
                            </div>
                            
                            <div class="solicitacao-detalhes mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <small><?php echo htmlspecialchars($solicitacao['instrutor_nome']); ?></small>
                                </div>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-book text-muted me-2"></i>
                                    <small><?php echo ucfirst($solicitacao['tipo_aula']); ?></small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-sticky-note text-muted me-2"></i>
                                    <small><?php echo htmlspecialchars($solicitacao['justificativa']); ?></small>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-success btn-mobile aprovar-solicitacao" 
                                        data-solicitacao-id="<?php echo $solicitacao['id']; ?>"
                                        data-tipo="<?php echo $solicitacao['tipo_solicitacao']; ?>">
                                    <i class="fas fa-check me-2"></i>
                                    Aprovar
                                </button>
                                <button class="btn btn-danger btn-mobile rejeitar-solicitacao" 
                                        data-solicitacao-id="<?php echo $solicitacao['id']; ?>">
                                    <i class="fas fa-times me-2"></i>
                                    Rejeitar
                                </button>
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

<!-- Próximas Aulas -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Próximas Aulas
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($proximasAulas)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhuma aula agendada</h5>
                    <p class="text-muted fs-mobile-6">Não há aulas agendadas para os próximos 7 dias.</p>
                </div>
                <?php else: ?>
                <div class="aula-list">
                    <?php foreach ($proximasAulas as $aula): ?>
                    <div class="card mb-3 aula-item" data-aula-id="<?php echo $aula['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'success'; ?> mb-1">
                                        <?php echo ucfirst($aula['tipo_aula']); ?>
                                    </span>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($aula['aluno_nome']); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?> - 
                                        <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-<?php echo $aula['status'] === 'agendada' ? 'warning' : 'success'; ?>">
                                    <?php echo ucfirst($aula['status']); ?>
                                </span>
                            </div>
                            
                            <div class="aula-detalhes mb-3">
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-user text-muted me-2"></i>
                                    <small><?php echo htmlspecialchars($aula['instrutor_nome']); ?></small>
                                </div>
                                <?php if ($aula['veiculo_modelo']): ?>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-car text-muted me-2"></i>
                                    <small><?php echo htmlspecialchars($aula['veiculo_modelo']); ?> - <?php echo htmlspecialchars($aula['veiculo_placa']); ?></small>
                                </div>
                                <?php endif; ?>
                                <?php if ($aula['observacoes']): ?>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-sticky-note text-muted me-2"></i>
                                    <small><?php echo htmlspecialchars($aula['observacoes']); ?></small>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button class="btn btn-primary btn-mobile editar-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>">
                                    <i class="fas fa-edit me-2"></i>
                                    Editar
                                </button>
                                <button class="btn btn-outline-danger btn-mobile cancelar-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-data="<?php echo $aula['data_aula']; ?>"
                                        data-hora="<?php echo $aula['hora_inicio']; ?>">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Alunos com Pendências -->
<?php if (!empty($alunosComPendencias)): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Alunos com Pendências
                </h3>
            </div>
            <div class="card-body">
                <div class="aluno-list">
                    <?php foreach ($alunosComPendencias as $aluno): ?>
                    <div class="card mb-3 aluno-item" data-aluno-id="<?php echo $aluno['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($aluno['nome']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($aluno['telefone']); ?></small>
                                </div>
                                <div class="text-end">
                                    <?php if ($aluno['faturas_pendentes'] > 0): ?>
                                    <span class="badge bg-danger me-1"><?php echo $aluno['faturas_pendentes']; ?> fatura(s)</span>
                                    <?php endif; ?>
                                    <?php if ($aluno['exames_pendentes'] > 0): ?>
                                    <span class="badge bg-warning"><?php echo $aluno['exames_pendentes']; ?> exame(s)</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="/admin/aluno.php?id=<?php echo $aluno['id']; ?>" 
                                   class="btn btn-primary btn-mobile">
                                    <i class="fas fa-user me-2"></i>
                                    Ver Detalhes
                                </a>
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

<!-- Ações Rápidas -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-bolt me-2"></i>
                    Ações Rápidas
                </h3>
            </div>
            <div class="card-body">
                <div class="row g-2">
                    <div class="col-6">
                        <a href="/admin/agendamento-moderno.php" class="btn btn-primary btn-mobile w-100">
                            <i class="fas fa-calendar-plus me-2"></i>
                            Nova Aula
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/admin/alunos.php" class="btn btn-secondary btn-mobile w-100">
                            <i class="fas fa-users me-2"></i>
                            Gerenciar Alunos
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/admin/financeiro.php" class="btn btn-outline-primary btn-mobile w-100">
                            <i class="fas fa-dollar-sign me-2"></i>
                            Financeiro
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/admin/relatorios.php" class="btn btn-outline-secondary btn-mobile w-100">
                            <i class="fas fa-chart-bar me-2"></i>
                            Relatórios
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Aprovação/Rejeição -->
<div class="modal fade" id="modalSolicitacao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Aprovar Solicitação</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formSolicitacao" class="form-mobile">
                    <input type="hidden" id="solicitacaoId" name="solicitacao_id">
                    <input type="hidden" id="tipoAcao" name="tipo_acao">
                    
                    <div class="mb-3" id="novaDataGroup" style="display: none;">
                        <label class="form-label">Nova Data</label>
                        <input type="date" id="novaData" name="nova_data" class="form-control">
                    </div>
                    
                    <div class="mb-3" id="novaHoraGroup" style="display: none;">
                        <label class="form-label">Novo Horário</label>
                        <input type="time" id="novaHora" name="nova_hora" class="form-control">
                    </div>
                    
                    <div class="mb-3" id="motivoRecusaGroup" style="display: none;">
                        <label class="form-label">Motivo da Rejeição</label>
                        <textarea id="motivoRecusa" name="motivo_recusa" class="form-control" 
                                  placeholder="Explique o motivo da rejeição..." rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="processarSolicitacao()">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para o dashboard do admin */
.stat-card {
    padding: 16px;
    border-radius: 8px;
    background: #f8f9fa;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    margin-bottom: 4px;
}

.stat-label {
    font-size: 0.875rem;
    color: #6c757d;
    font-weight: 500;
}
</style>

<script>
// JavaScript específico do dashboard do admin
document.addEventListener('DOMContentLoaded', function() {
    // Botões de aprovação
    document.querySelectorAll('.aprovar-solicitacao').forEach(btn => {
        btn.addEventListener('click', function() {
            const solicitacaoId = this.dataset.solicitacaoId;
            const tipo = this.dataset.tipo;
            abrirModal('aprovar', solicitacaoId, tipo);
        });
    });

    // Botões de rejeição
    document.querySelectorAll('.rejeitar-solicitacao').forEach(btn => {
        btn.addEventListener('click', function() {
            const solicitacaoId = this.dataset.solicitacaoId;
            abrirModal('rejeitar', solicitacaoId);
        });
    });

    // Botões de marcar notificação como lida
    document.querySelectorAll('.marcar-lida').forEach(btn => {
        btn.addEventListener('click', function() {
            const notificacaoId = this.dataset.id;
            marcarNotificacaoComoLida(notificacaoId);
        });
    });
});

function abrirModal(tipo, solicitacaoId, tipoSolicitacao = null) {
    document.getElementById('tipoAcao').value = tipo;
    document.getElementById('solicitacaoId').value = solicitacaoId;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitacao'));
    const titulo = document.getElementById('modalTitulo');
    const novaDataGroup = document.getElementById('novaDataGroup');
    const novaHoraGroup = document.getElementById('novaHoraGroup');
    const motivoRecusaGroup = document.getElementById('motivoRecusaGroup');
    
    if (tipo === 'aprovar') {
        titulo.textContent = 'Aprovar Solicitação';
        if (tipoSolicitacao === 'reagendamento') {
            novaDataGroup.style.display = 'block';
            novaHoraGroup.style.display = 'block';
        } else {
            novaDataGroup.style.display = 'none';
            novaHoraGroup.style.display = 'none';
        }
        motivoRecusaGroup.style.display = 'none';
    } else {
        titulo.textContent = 'Rejeitar Solicitação';
        novaDataGroup.style.display = 'none';
        novaHoraGroup.style.display = 'none';
        motivoRecusaGroup.style.display = 'block';
    }
    
    modal.show();
}

async function processarSolicitacao() {
    const form = document.getElementById('formSolicitacao');
    const formData = new FormData(form);
    
    const tipoAcao = formData.get('tipo_acao');
    if (tipoAcao === 'rejeitar' && !formData.get('motivo_recusa').trim()) {
        showToast('Por favor, preencha o motivo da rejeição.', 'error');
        return;
    }

    showLoading('Processando solicitação...');

    try {
        const response = await fetch('../admin/api/solicitacoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                solicitacao_id: formData.get('solicitacao_id'),
                acao: formData.get('tipo_acao'),
                nova_data: formData.get('nova_data'),
                nova_hora: formData.get('nova_hora'),
                motivo_recusa: formData.get('motivo_recusa')
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast('Solicitação processada com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalSolicitacao')).hide();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(result.message || 'Erro ao processar solicitação.', 'error');
        }
    } catch (error) {
        console.error('Erro:', error);
        showToast('Erro de conexão. Tente novamente.', 'error');
    } finally {
        hideLoading();
    }
}

async function marcarNotificacaoComoLida(notificacaoId) {
    try {
        const response = await fetch('../admin/api/notificacoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                notificacao_id: notificacaoId
            })
        });

        const result = await response.json();

        if (result.success) {
            const notificacaoItem = document.querySelector(`[data-id="${notificacaoId}"]`);
            if (notificacaoItem) {
                notificacaoItem.remove();
            }
            
            const badge = document.querySelector('.badge');
            if (badge) {
                const count = parseInt(badge.textContent) - 1;
                if (count > 0) {
                    badge.textContent = count;
                } else {
                    badge.parentElement.parentElement.remove();
                }
            }
        }
    } catch (error) {
        console.error('Erro:', error);
    }
}
</script>

<?php
// Finalizar buffer e incluir layout
$pageContent = ob_get_clean();
include __DIR__ . '/../includes/layout/mobile-first.php';
?>
