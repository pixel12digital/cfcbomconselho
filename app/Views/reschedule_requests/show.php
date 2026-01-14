<?php
$currentRole = $_SESSION['current_role'] ?? '';
$isAdmin = ($currentRole === 'ADMIN' || $currentRole === 'SECRETARIA');

$lessonDate = new \DateTime("{$request['scheduled_date']} {$request['scheduled_time']}");
$createdAt = new \DateTime($request['created_at']);
$reasonLabels = [
    'imprevisto' => 'Imprevisto',
    'trabalho' => 'Trabalho',
    'saude' => 'Saúde',
    'outro' => 'Outro'
];
$reasonLabel = $reasonLabels[$request['reason']] ?? $request['reason'];

$statusLabels = [
    'pending' => 'Pendente',
    'approved' => 'Aprovada',
    'rejected' => 'Recusada',
    'cancelled' => 'Cancelada'
];
$statusLabel = $statusLabels[$request['status']] ?? $request['status'];

$statusColors = [
    'pending' => 'var(--color-warning)',
    'approved' => 'var(--color-success)',
    'rejected' => 'var(--color-danger)',
    'cancelled' => 'var(--color-text-muted)'
];
$statusColor = $statusColors[$request['status']] ?? 'var(--color-text-muted)';

$isPending = ($request['status'] === 'pending');
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Solicitação de Reagendamento</h1>
            <p class="text-muted">Detalhes da solicitação</p>
        </div>
        <div>
            <a href="<?= base_path('solicitacoes-reagendamento') ?>" class="btn btn-outline">
                ← Voltar para Lista
            </a>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div style="display: grid; gap: var(--spacing-lg);">
            <!-- Status -->
            <div style="display: flex; justify-content: space-between; align-items: center; padding: var(--spacing-md); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                <div>
                    <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Status:</strong>
                    <div style="font-size: var(--font-size-lg); font-weight: 600; color: <?= $statusColor ?>; margin-top: var(--spacing-xs);">
                        <?= htmlspecialchars($statusLabel) ?>
                    </div>
                </div>
                <?php if (!$isPending && !empty($request['resolved_by_name'])): ?>
                <div style="text-align: right;">
                    <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Resolvida por:</strong>
                    <div style="margin-top: var(--spacing-xs);"><?= htmlspecialchars($request['resolved_by_name']) ?></div>
                    <?php if (!empty($request['resolved_at'])): ?>
                    <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); margin-top: var(--spacing-xs);">
                        <?= date('d/m/Y H:i', strtotime($request['resolved_at'])) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Informações do Aluno -->
            <div>
                <h3 style="margin-bottom: var(--spacing-md); font-size: var(--font-size-lg);">Informações do Aluno</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md);">
                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Nome:</strong>
                        <div style="margin-top: var(--spacing-xs);"><?= htmlspecialchars($request['student_name'] ?? 'Aluno') ?></div>
                    </div>
                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">CPF:</strong>
                        <div style="margin-top: var(--spacing-xs);"><?= htmlspecialchars($request['student_cpf'] ?? '') ?></div>
                    </div>
                </div>
            </div>

            <!-- Detalhes da Aula -->
            <div>
                <h3 style="margin-bottom: var(--spacing-md); font-size: var(--font-size-lg);">Detalhes da Aula</h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); padding: var(--spacing-md); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Data/Hora:</strong>
                        <div style="margin-top: var(--spacing-xs);">
                            <?= $lessonDate->format('d/m/Y') ?> às <?= $lessonDate->format('H:i') ?>
                        </div>
                    </div>
                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Status da Aula:</strong>
                        <div style="margin-top: var(--spacing-xs);">
                            <?php
                            $lessonStatusLabels = [
                                'agendada' => 'Agendada',
                                'em_andamento' => 'Em Andamento',
                                'concluida' => 'Concluída',
                                'cancelada' => 'Cancelada'
                            ];
                            echo htmlspecialchars($lessonStatusLabels[$request['lesson_status']] ?? $request['lesson_status']);
                            ?>
                        </div>
                    </div>
                </div>
                <div style="margin-top: var(--spacing-md);">
                    <a href="<?= base_path("agenda/{$request['lesson_id']}") ?>" class="btn btn-sm btn-outline">
                        Ver Detalhes da Aula
                    </a>
                </div>
            </div>

            <!-- Motivo e Mensagem -->
            <div>
                <h3 style="margin-bottom: var(--spacing-md); font-size: var(--font-size-lg);">Solicitação</h3>
                <div style="display: grid; gap: var(--spacing-md);">
                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Motivo:</strong>
                        <div style="margin-top: var(--spacing-xs);"><?= htmlspecialchars($reasonLabel) ?></div>
                    </div>
                    
                    <?php if (!empty($request['message'])): ?>
                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Observação do Aluno:</strong>
                        <div style="margin-top: var(--spacing-xs); padding: var(--spacing-md); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                            <?= nl2br(htmlspecialchars($request['message'])) ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <div>
                        <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Solicitado em:</strong>
                        <div style="margin-top: var(--spacing-xs);"><?= $createdAt->format('d/m/Y H:i') ?></div>
                    </div>
                </div>
            </div>

            <!-- Resolução (se já foi resolvida) -->
            <?php if (!$isPending && !empty($request['resolution_note'])): ?>
            <div>
                <h3 style="margin-bottom: var(--spacing-md); font-size: var(--font-size-lg);">Resolução</h3>
                <div style="padding: var(--spacing-md); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                    <?= nl2br(htmlspecialchars($request['resolution_note'])) ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Ações (apenas se pendente) -->
            <?php if ($isPending && $isAdmin): ?>
            <div style="padding-top: var(--spacing-md); border-top: 2px solid var(--color-border);">
                <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                    <button type="button" class="btn btn-success" onclick="showApproveModal(<?= $request['id'] ?>, '<?= htmlspecialchars($request['student_name'] ?? 'Aluno', ENT_QUOTES, 'UTF-8') ?>')">
                        Aprovar Solicitação
                    </button>
                    <button type="button" class="btn btn-danger" onclick="showRejectModal(<?= $request['id'] ?>, '<?= htmlspecialchars($request['student_name'] ?? 'Aluno', ENT_QUOTES, 'UTF-8') ?>')">
                        Recusar Solicitação
                    </button>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Aprovação -->
<div id="approveModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: var(--spacing-md);">
    <div class="card" style="max-width: 500px; width: 100%;">
        <div class="card-header">
            <h3 style="margin: 0;">Aprovar Solicitação</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="approveForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <p>Deseja aprovar a solicitação de reagendamento de <strong id="approveStudentName"></strong>?</p>
                <div class="form-group">
                    <label class="form-label">Observação <small style="color: var(--color-text-muted, #666);">(opcional)</small></label>
                    <textarea name="resolution_note" class="form-input" rows="3" placeholder="Adicione uma observação, se necessário..."></textarea>
                </div>
                <div style="display: flex; gap: var(--spacing-sm); justify-content: flex-end; margin-top: var(--spacing-md);">
                    <button type="button" class="btn btn-outline" onclick="hideApproveModal()">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="submitApproveBtn">Aprovar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Recusa -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: var(--spacing-md);">
    <div class="card" style="max-width: 500px; width: 100%;">
        <div class="card-header">
            <h3 style="margin: 0;">Recusar Solicitação</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="rejectForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <p>Deseja recusar a solicitação de reagendamento de <strong id="rejectStudentName"></strong>?</p>
                <div class="form-group">
                    <label class="form-label">Motivo da recusa <small style="color: var(--color-text-muted, #666);">(opcional)</small></label>
                    <textarea name="resolution_note" class="form-input" rows="3" placeholder="Informe o motivo da recusa..."></textarea>
                </div>
                <div style="display: flex; gap: var(--spacing-sm); justify-content: flex-end; margin-top: var(--spacing-md);">
                    <button type="button" class="btn btn-outline" onclick="hideRejectModal()">Cancelar</button>
                    <button type="submit" class="btn btn-danger" id="submitRejectBtn">Recusar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function showApproveModal(requestId, studentName) {
    document.getElementById('approveStudentName').textContent = studentName;
    const form = document.getElementById('approveForm');
    form.action = '<?= base_path("solicitacoes-reagendamento/") ?>' + requestId + '/aprovar';
    document.getElementById('approveModal').style.display = 'flex';
}

function hideApproveModal() {
    document.getElementById('approveModal').style.display = 'none';
    document.getElementById('approveForm').reset();
}

function showRejectModal(requestId, studentName) {
    document.getElementById('rejectStudentName').textContent = studentName;
    const form = document.getElementById('rejectForm');
    form.action = '<?= base_path("solicitacoes-reagendamento/") ?>' + requestId + '/recusar';
    document.getElementById('rejectModal').style.display = 'flex';
}

function hideRejectModal() {
    document.getElementById('rejectModal').style.display = 'none';
    document.getElementById('rejectForm').reset();
}

// Fechar modais ao clicar fora
document.getElementById('approveModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideApproveModal();
    }
});

document.getElementById('rejectModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideRejectModal();
    }
});

// Prevenir duplo submit
document.getElementById('approveForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitApproveBtn');
    if (btn && btn.disabled) {
        e.preventDefault();
        return false;
    }
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Aprovando...';
    }
});

document.getElementById('rejectForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitRejectBtn');
    if (btn && btn.disabled) {
        e.preventDefault();
        return false;
    }
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Recusando...';
    }
});
</script>
