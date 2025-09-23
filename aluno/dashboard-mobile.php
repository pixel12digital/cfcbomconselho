<?php
/**
 * Dashboard do Aluno - Mobile First + PWA
 * Interface focada em usabilidade móvel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'aluno') {
    header('Location: /login.php');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Buscar dados do aluno
$aluno = $db->fetch("SELECT * FROM alunos WHERE id = ?", [$user['id']]);

// Buscar próximas aulas (próximos 14 dias)
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
", [$user['id']]);

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($user['id'], 'aluno');

// Buscar status dos exames
$exames = $db->fetchAll("
    SELECT tipo, status, data_exame
    FROM exames 
    WHERE aluno_id = ? 
    ORDER BY data_exame DESC
", [$user['id']]);

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

// Incluir layout mobile-first
ob_start();
?>

<!-- Conteúdo do Dashboard -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h2 class="card-title fs-mobile-2">
                    <i class="fas fa-user-graduate me-2"></i>
                    Olá, <?php echo htmlspecialchars($aluno['nome']); ?>!
                </h2>
                <p class="card-subtitle text-muted mb-0">Acompanhe suas aulas e progresso</p>
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

<!-- Status do Processo -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-route me-2"></i>
                    Seu Progresso
                </h3>
            </div>
            <div class="card-body">
                <div class="progresso-timeline">
                    <div class="timeline-item <?php echo $guardaExames ? 'completed' : 'pending'; ?>">
                        <div class="timeline-icon">
                            <i class="fas fa-stethoscope"></i>
                        </div>
                        <div class="timeline-content">
                            <h5 class="fs-mobile-4">Exames Médico e Psicológico</h5>
                            <p class="text-muted fs-mobile-6"><?php echo $guardaExames ? 'Aprovados' : 'Pendentes'; ?></p>
                        </div>
                    </div>
                    
                    <div class="timeline-item <?php echo $guardaExames ? 'completed' : 'disabled'; ?>">
                        <div class="timeline-icon">
                            <i class="fas fa-book"></i>
                        </div>
                        <div class="timeline-content">
                            <h5 class="fs-mobile-4">Aulas Teóricas</h5>
                            <p class="text-muted fs-mobile-6"><?php echo $guardaExames ? 'Liberadas' : 'Bloqueadas'; ?></p>
                        </div>
                    </div>
                    
                    <div class="timeline-item disabled">
                        <div class="timeline-icon">
                            <i class="fas fa-car"></i>
                        </div>
                        <div class="timeline-content">
                            <h5 class="fs-mobile-4">Aulas Práticas</h5>
                            <p class="text-muted fs-mobile-6">Após prova teórica</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

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
                    <p class="text-muted fs-mobile-6">Você não possui aulas agendadas para os próximos 14 dias.</p>
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
                                    <h6 class="mb-1"><?php echo date('d/m/Y', strtotime($aula['data_aula'])); ?></h6>
                                    <small class="text-muted">
                                        <?php echo date('H:i', strtotime($aula['hora_inicio'])); ?> - 
                                        <?php echo date('H:i', strtotime($aula['hora_fim'])); ?>
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
                                <button class="btn btn-outline-primary btn-mobile solicitar-reagendamento" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-data="<?php echo $aula['data_aula']; ?>"
                                        data-hora="<?php echo $aula['hora_inicio']; ?>">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    Reagendar
                                </button>
                                <button class="btn btn-outline-danger btn-mobile solicitar-cancelamento" 
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
                        <a href="/aluno/aulas.php" class="btn btn-primary btn-mobile w-100">
                            <i class="fas fa-list me-2"></i>
                            Ver Todas as Aulas
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/aluno/notificacoes.php" class="btn btn-secondary btn-mobile w-100">
                            <i class="fas fa-bell me-2"></i>
                            Central de Avisos
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/aluno/financeiro.php" class="btn btn-outline-primary btn-mobile w-100">
                            <i class="fas fa-credit-card me-2"></i>
                            Financeiro
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/aluno/contato.php" class="btn btn-outline-secondary btn-mobile w-100">
                            <i class="fas fa-phone me-2"></i>
                            Contatar CFC
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Solicitação -->
<div class="modal fade" id="modalSolicitacao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Solicitar Reagendamento</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formSolicitacao" class="form-mobile">
                    <input type="hidden" id="aulaId" name="aula_id">
                    <input type="hidden" id="tipoSolicitacao" name="tipo_solicitacao">
                    
                    <div class="mb-3">
                        <label class="form-label">Data Atual</label>
                        <input type="text" id="dataAtual" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Horário Atual</label>
                        <input type="text" id="horaAtual" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3" id="novaDataGroup" style="display: none;">
                        <label class="form-label">Nova Data</label>
                        <input type="date" id="novaData" name="nova_data" class="form-control">
                    </div>
                    
                    <div class="mb-3" id="novaHoraGroup" style="display: none;">
                        <label class="form-label">Novo Horário</label>
                        <input type="time" id="novaHora" name="nova_hora" class="form-control">
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Motivo</label>
                        <select id="motivo" name="motivo" class="form-select">
                            <option value="">Selecione um motivo</option>
                            <option value="imprevisto_pessoal">Imprevisto pessoal</option>
                            <option value="problema_saude">Problema de saúde</option>
                            <option value="compromisso_trabalho">Compromisso de trabalho</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Justificativa *</label>
                        <textarea id="justificativa" name="justificativa" class="form-control" 
                                  placeholder="Descreva o motivo da solicitação..." required rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Política:</strong> Solicitações devem ser feitas com no mínimo 24 horas de antecedência.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarSolicitacao()">Enviar Solicitação</button>
            </div>
        </div>
    </div>
</div>

<style>
/* Estilos específicos para o dashboard do aluno */
.progresso-timeline {
    position: relative;
}

.timeline-item {
    display: flex;
    align-items: center;
    margin-bottom: 20px;
    position: relative;
}

.timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 20px;
    top: 40px;
    width: 2px;
    height: 20px;
    background: #e2e8f0;
}

.timeline-item.completed::after {
    background: #059669;
}

.timeline-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 16px;
    font-size: 16px;
    color: white;
    background: #e2e8f0;
}

.timeline-item.completed .timeline-icon {
    background: #059669;
}

.timeline-item.disabled .timeline-icon {
    background: #f1f5f9;
    color: #94a3b8;
}

.timeline-content h5 {
    margin-bottom: 4px;
    color: #1e293b;
}

.timeline-item.disabled .timeline-content h5 {
    color: #94a3b8;
}

.timeline-content p {
    color: #64748b;
}

.timeline-item.disabled .timeline-content p {
    color: #94a3b8;
}
</style>

<script>
// JavaScript específico do dashboard do aluno
document.addEventListener('DOMContentLoaded', function() {
    // Botões de reagendamento
    document.querySelectorAll('.solicitar-reagendamento').forEach(btn => {
        btn.addEventListener('click', function() {
            const aulaId = this.dataset.aulaId;
            const data = this.dataset.data;
            const hora = this.dataset.hora;
            abrirModal('reagendamento', aulaId, data, hora);
        });
    });

    // Botões de cancelamento
    document.querySelectorAll('.solicitar-cancelamento').forEach(btn => {
        btn.addEventListener('click', function() {
            const aulaId = this.dataset.aulaId;
            const data = this.dataset.data;
            const hora = this.dataset.hora;
            abrirModal('cancelamento', aulaId, data, hora);
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

function abrirModal(tipo, aulaId, data, hora) {
    document.getElementById('tipoSolicitacao').value = tipo;
    document.getElementById('aulaId').value = aulaId;
    document.getElementById('dataAtual').value = data;
    document.getElementById('horaAtual').value = hora;
    
    const modal = new bootstrap.Modal(document.getElementById('modalSolicitacao'));
    const titulo = document.getElementById('modalTitulo');
    const novaDataGroup = document.getElementById('novaDataGroup');
    const novaHoraGroup = document.getElementById('novaHoraGroup');
    
    if (tipo === 'reagendamento') {
        titulo.textContent = 'Solicitar Reagendamento';
        novaDataGroup.style.display = 'block';
        novaHoraGroup.style.display = 'block';
    } else {
        titulo.textContent = 'Solicitar Cancelamento';
        novaDataGroup.style.display = 'none';
        novaHoraGroup.style.display = 'none';
    }
    
    modal.show();
}

async function enviarSolicitacao() {
    const form = document.getElementById('formSolicitacao');
    const formData = new FormData(form);
    
    if (!formData.get('justificativa').trim()) {
        showToast('Por favor, preencha a justificativa.', 'error');
        return;
    }

    showLoading('Enviando solicitação...');

    try {
        const response = await fetch('../admin/api/solicitacoes.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                aula_id: formData.get('aula_id'),
                tipo_solicitacao: formData.get('tipo_solicitacao'),
                nova_data: formData.get('nova_data'),
                nova_hora: formData.get('nova_hora'),
                motivo: formData.get('motivo'),
                justificativa: formData.get('justificativa')
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast('Solicitação enviada com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalSolicitacao')).hide();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(result.message || 'Erro ao enviar solicitação.', 'error');
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
