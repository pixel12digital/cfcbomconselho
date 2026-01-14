<?php
$currentRole = $_SESSION['current_role'] ?? '';
$isAdmin = ($currentRole === 'ADMIN' || $currentRole === 'SECRETARIA');
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Solicitações de Reagendamento</h1>
            <p class="text-muted">Gerenciar solicitações de reagendamento de aulas</p>
        </div>
    </div>
</div>

<?php if (empty($requests)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Não há solicitações pendentes no momento.</p>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <div style="display: flex; flex-direction: column;">
                <?php foreach ($requests as $req): ?>
                    <?php
                    $lessonDate = new \DateTime("{$req['scheduled_date']} {$req['scheduled_time']}");
                    $createdAt = new \DateTime($req['created_at']);
                    $reasonLabels = [
                        'imprevisto' => 'Imprevisto',
                        'trabalho' => 'Trabalho',
                        'saude' => 'Saúde',
                        'outro' => 'Outro'
                    ];
                    $reasonLabel = $reasonLabels[$req['reason']] ?? $req['reason'];
                    ?>
                    <div style="padding: var(--spacing-md); border-bottom: 1px solid var(--color-border);">
                        <div style="display: grid; gap: var(--spacing-md);">
                            <!-- Cabeçalho -->
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: var(--spacing-md); flex-wrap: wrap;">
                                <div style="flex: 1; min-width: 200px;">
                                    <div style="font-weight: 600; font-size: var(--font-size-lg); margin-bottom: var(--spacing-xs);">
                                        <?= htmlspecialchars($req['student_name'] ?? 'Aluno') ?>
                                    </div>
                                    <div style="color: var(--color-text-muted); font-size: var(--font-size-sm);">
                                        CPF: <?= htmlspecialchars($req['student_cpf'] ?? '') ?>
                                    </div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="font-size: var(--font-size-sm); color: var(--color-text-muted);">
                                        Solicitação de <?= $createdAt->format('d/m/Y H:i') ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Detalhes da Aula -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); padding: var(--spacing-sm); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                                <div>
                                    <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Data/Hora da Aula</strong>
                                    <div><?= $lessonDate->format('d/m/Y') ?> às <?= $lessonDate->format('H:i') ?></div>
                                </div>
                                <div>
                                    <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Status da Aula</strong>
                                    <div>
                                        <?php
                                        $lessonStatusLabels = [
                                            'agendada' => 'Agendada',
                                            'em_andamento' => 'Em Andamento',
                                            'concluida' => 'Concluída',
                                            'cancelada' => 'Cancelada'
                                        ];
                                        echo htmlspecialchars($lessonStatusLabels[$req['lesson_status']] ?? $req['lesson_status']);
                                        ?>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Motivo e Mensagem -->
                            <div>
                                <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Motivo:</strong>
                                <div style="margin-top: var(--spacing-xs);"><?= htmlspecialchars($reasonLabel) ?></div>
                            </div>
                            
                            <?php if (!empty($req['message'])): ?>
                            <div>
                                <strong style="font-size: var(--font-size-sm); color: var(--color-text-muted);">Observação:</strong>
                                <div style="margin-top: var(--spacing-xs); padding: var(--spacing-sm); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                                    <?= nl2br(htmlspecialchars($req['message'])) ?>
                                </div>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Ações -->
                            <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap; padding-top: var(--spacing-sm); border-top: 1px solid var(--color-border);">
                                <a href="<?= base_path("agenda/{$req['lesson_id']}") ?>" class="btn btn-sm btn-outline">
                                    Ver Aula
                                </a>
                                <button type="button" class="btn btn-sm btn-success" onclick="showApproveModal(<?= $req['id'] ?>, '<?= htmlspecialchars($req['student_name'] ?? 'Aluno', ENT_QUOTES, 'UTF-8') ?>')">
                                    Aprovar
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="showRejectModal(<?= $req['id'] ?>, '<?= htmlspecialchars($req['student_name'] ?? 'Aluno', ENT_QUOTES, 'UTF-8') ?>')">
                                    Recusar
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

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
