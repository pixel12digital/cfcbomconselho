<?php
/**
 * Dashboard do Instrutor - Mobile First + PWA
 * Interface focada em usabilidade móvel
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/services/SistemaNotificacoes.php';

// Verificar autenticação
$user = getCurrentUser();
if (!$user || $user['tipo'] !== 'instrutor') {
    header('Location: /login.php');
    exit();
}

$db = db();
$notificacoes = new SistemaNotificacoes();

// Buscar dados do instrutor
$instrutor = $db->fetch("SELECT * FROM instrutores WHERE id = ?", [$user['id']]);

// Buscar aulas do dia
$hoje = date('Y-m-d');
$aulasHoje = $db->fetchAll("
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula = ?
      AND a.status != 'cancelada'
    ORDER BY a.hora_inicio ASC
", [$user['id'], $hoje]);

// Buscar próximas aulas (próximos 7 dias)
$proximasAulas = $db->fetchAll("
    SELECT a.*, 
           al.nome as aluno_nome, al.telefone as aluno_telefone,
           v.modelo as veiculo_modelo, v.placa as veiculo_placa
    FROM aulas a
    JOIN alunos al ON a.aluno_id = al.id
    LEFT JOIN veiculos v ON a.veiculo_id = v.id
    WHERE a.instrutor_id = ? 
      AND a.data_aula > ?
      AND a.data_aula <= DATE_ADD(?, INTERVAL 7 DAY)
      AND a.status != 'cancelada'
    ORDER BY a.data_aula ASC, a.hora_inicio ASC
    LIMIT 10
", [$user['id'], $hoje, $hoje]);

// Buscar notificações não lidas
$notificacoesNaoLidas = $notificacoes->buscarNotificacoesNaoLidas($user['id'], 'instrutor');

// Buscar turmas teóricas do instrutor
$turmasTeoricas = $db->fetchAll("
    SELECT DISTINCT t.*, COUNT(a.id) as total_alunos
    FROM turmas t
    JOIN aulas a ON t.id = a.turma_id
    WHERE t.instrutor_id = ? 
      AND t.tipo = 'teorica'
      AND t.status = 'ativa'
    GROUP BY t.id
    ORDER BY t.nome ASC
", [$user['id']]);

// Configurar variáveis para o layout
$pageTitle = 'Dashboard - ' . htmlspecialchars($instrutor['nome']);
$homeUrl = '/instrutor/dashboard.php';

// Incluir layout mobile-first
ob_start();
?>

<!-- Conteúdo do Dashboard -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h2 class="card-title fs-mobile-2">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Olá, <?php echo htmlspecialchars($instrutor['nome']); ?>!
                </h2>
                <p class="card-subtitle text-muted mb-0">Suas aulas e turmas de hoje</p>
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

<!-- Aulas de Hoje -->
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-calendar-day me-2"></i>
                    Aulas de Hoje
                </h3>
            </div>
            <div class="card-body">
                <?php if (empty($aulasHoje)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Nenhuma aula hoje</h5>
                    <p class="text-muted fs-mobile-6">Você não possui aulas agendadas para hoje.</p>
                </div>
                <?php else: ?>
                <div class="aula-list">
                    <?php foreach ($aulasHoje as $aula): ?>
                    <div class="card mb-3 aula-item" data-aula-id="<?php echo $aula['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <span class="badge bg-<?php echo $aula['tipo_aula'] === 'teorica' ? 'info' : 'success'; ?> mb-1">
                                        <?php echo ucfirst($aula['tipo_aula']); ?>
                                    </span>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($aula['aluno_nome']); ?></h6>
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
                                <?php if ($aula['tipo_aula'] === 'teorica'): ?>
                                <button class="btn btn-primary btn-mobile fazer-chamada" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-turma-id="<?php echo $aula['turma_id']; ?>">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Fazer Chamada
                                </button>
                                <button class="btn btn-outline-primary btn-mobile abrir-diario" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-turma-id="<?php echo $aula['turma_id']; ?>">
                                    <i class="fas fa-book me-2"></i>
                                    Abrir Diário
                                </button>
                                <?php else: ?>
                                <button class="btn btn-primary btn-mobile iniciar-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>">
                                    <i class="fas fa-play me-2"></i>
                                    Iniciar Aula
                                </button>
                                <?php endif; ?>
                                
                                <button class="btn btn-outline-warning btn-mobile cancelar-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-data="<?php echo $aula['data_aula']; ?>"
                                        data-hora="<?php echo $aula['hora_inicio']; ?>">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </button>
                                
                                <button class="btn btn-outline-info btn-mobile transferir-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-data="<?php echo $aula['data_aula']; ?>"
                                        data-hora="<?php echo $aula['hora_inicio']; ?>">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    Transferir
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
                    <p class="text-muted fs-mobile-6">Você não possui aulas agendadas para os próximos 7 dias.</p>
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
                                <button class="btn btn-outline-warning btn-mobile cancelar-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-data="<?php echo $aula['data_aula']; ?>"
                                        data-hora="<?php echo $aula['hora_inicio']; ?>">
                                    <i class="fas fa-times me-2"></i>
                                    Cancelar
                                </button>
                                
                                <button class="btn btn-outline-info btn-mobile transferir-aula" 
                                        data-aula-id="<?php echo $aula['id']; ?>"
                                        data-data="<?php echo $aula['data_aula']; ?>"
                                        data-hora="<?php echo $aula['hora_inicio']; ?>">
                                    <i class="fas fa-exchange-alt me-2"></i>
                                    Transferir
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

<!-- Turmas Teóricas -->
<?php if (!empty($turmasTeoricas)): ?>
<div class="row">
    <div class="col-12">
        <div class="card card-mobile mb-mobile">
            <div class="card-header">
                <h3 class="card-title fs-mobile-3">
                    <i class="fas fa-users-class me-2"></i>
                    Minhas Turmas Teóricas
                </h3>
            </div>
            <div class="card-body">
                <div class="turma-list">
                    <?php foreach ($turmasTeoricas as $turma): ?>
                    <div class="card mb-3 turma-item" data-turma-id="<?php echo $turma['id']; ?>">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($turma['nome']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($turma['descricao']); ?></small>
                                </div>
                                <span class="badge bg-info"><?php echo $turma['total_alunos']; ?> alunos</span>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <a href="/instrutor/turma.php?id=<?php echo $turma['id']; ?>&acao=chamada" 
                                   class="btn btn-primary btn-mobile">
                                    <i class="fas fa-clipboard-list me-2"></i>
                                    Fazer Chamada
                                </a>
                                <a href="/instrutor/turma.php?id=<?php echo $turma['id']; ?>&acao=diario" 
                                   class="btn btn-outline-primary btn-mobile">
                                    <i class="fas fa-book me-2"></i>
                                    Abrir Diário
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
                        <a href="/instrutor/aulas.php" class="btn btn-primary btn-mobile w-100">
                            <i class="fas fa-list me-2"></i>
                            Ver Todas as Aulas
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/instrutor/turmas.php" class="btn btn-secondary btn-mobile w-100">
                            <i class="fas fa-users-class me-2"></i>
                            Minhas Turmas
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/instrutor/ocorrencias.php" class="btn btn-outline-primary btn-mobile w-100">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Registrar Ocorrência
                        </a>
                    </div>
                    <div class="col-6">
                        <a href="/instrutor/contato.php" class="btn btn-outline-secondary btn-mobile w-100">
                            <i class="fas fa-phone me-2"></i>
                            Contatar CFC
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cancelamento/Transferência -->
<div class="modal fade" id="modalAcao" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitulo">Cancelar Aula</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="formAcao" class="form-mobile">
                    <input type="hidden" id="aulaId" name="aula_id">
                    <input type="hidden" id="tipoAcao" name="tipo_acao">
                    
                    <div class="mb-3">
                        <label class="form-label">Data da Aula</label>
                        <input type="text" id="dataAula" class="form-control" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Horário da Aula</label>
                        <input type="text" id="horaAula" class="form-control" readonly>
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
                            <option value="problema_saude">Problema de saúde</option>
                            <option value="imprevisto_pessoal">Imprevisto pessoal</option>
                            <option value="problema_veiculo">Problema com veículo</option>
                            <option value="falta_aluno">Falta do aluno</option>
                            <option value="outro">Outro</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Justificativa *</label>
                        <textarea id="justificativa" name="justificativa" class="form-control" 
                                  placeholder="Descreva o motivo da ação..." required rows="3"></textarea>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Política:</strong> Cancelamentos devem ser feitos com no mínimo 24 horas de antecedência.
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="enviarAcao()">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript específico do dashboard do instrutor
document.addEventListener('DOMContentLoaded', function() {
    // Botões de cancelamento
    document.querySelectorAll('.cancelar-aula').forEach(btn => {
        btn.addEventListener('click', function() {
            const aulaId = this.dataset.aulaId;
            const data = this.dataset.data;
            const hora = this.dataset.hora;
            abrirModal('cancelar', aulaId, data, hora);
        });
    });

    // Botões de transferência
    document.querySelectorAll('.transferir-aula').forEach(btn => {
        btn.addEventListener('click', function() {
            const aulaId = this.dataset.aulaId;
            const data = this.dataset.data;
            const hora = this.dataset.hora;
            abrirModal('transferir', aulaId, data, hora);
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
    document.getElementById('tipoAcao').value = tipo;
    document.getElementById('aulaId').value = aulaId;
    document.getElementById('dataAula').value = data;
    document.getElementById('horaAula').value = hora;
    
    const modal = new bootstrap.Modal(document.getElementById('modalAcao'));
    const titulo = document.getElementById('modalTitulo');
    const novaDataGroup = document.getElementById('novaDataGroup');
    const novaHoraGroup = document.getElementById('novaHoraGroup');
    
    if (tipo === 'transferir') {
        titulo.textContent = 'Transferir Aula';
        novaDataGroup.style.display = 'block';
        novaHoraGroup.style.display = 'block';
    } else {
        titulo.textContent = 'Cancelar Aula';
        novaDataGroup.style.display = 'none';
        novaHoraGroup.style.display = 'none';
    }
    
    modal.show();
}

async function enviarAcao() {
    const form = document.getElementById('formAcao');
    const formData = new FormData(form);
    
    if (!formData.get('justificativa').trim()) {
        showToast('Por favor, preencha a justificativa.', 'error');
        return;
    }

    showLoading('Enviando solicitação...');

    try {
        const response = await fetch('../admin/api/agendamento.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                aula_id: formData.get('aula_id'),
                acao: formData.get('tipo_acao'),
                nova_data: formData.get('nova_data'),
                nova_hora: formData.get('nova_hora'),
                motivo: formData.get('motivo'),
                justificativa: formData.get('justificativa')
            })
        });

        const result = await response.json();

        if (result.success) {
            showToast('Ação realizada com sucesso!', 'success');
            bootstrap.Modal.getInstance(document.getElementById('modalAcao')).hide();
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            showToast(result.message || 'Erro ao realizar ação.', 'error');
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
