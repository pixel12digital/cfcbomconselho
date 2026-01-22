<div class="page-header">
    <div>
        <h1>Presença</h1>
        <p class="text-muted">
            <?= htmlspecialchars($session['discipline_name']) ?> - 
            <?= date('d/m/Y H:i', strtotime($session['starts_at'])) ?>
        </p>
    </div>
    <a href="<?= base_path("turmas-teoricas/{$classId}") ?>" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path("turmas-teoricas/{$classId}/sessoes/{$session['id']}/presenca/salvar") ?>" id="attendanceForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <?php if (empty($enrollments)): ?>
                <p class="text-muted">Nenhum aluno matriculado nesta turma.</p>
            <?php else: ?>
                <div style="display: flex; flex-direction: column; gap: var(--spacing-md);">
                    <?php foreach ($enrollments as $enrollment): 
                        if ($enrollment['status'] !== 'active') continue;
                        $attendance = $attendanceMap[$enrollment['student_id']] ?? null;
                        $currentStatus = $attendance['status'] ?? 'absent';
                    ?>
                        <div class="attendance-item" style="padding: var(--spacing-md); background: var(--color-bg-light); border-radius: var(--radius-md);">
                            <div style="margin-bottom: var(--spacing-sm);">
                                <strong><?= htmlspecialchars($enrollment['student_name']) ?></strong>
                                <?php if ($enrollment['student_cpf']): ?>
                                <div style="font-size: var(--font-size-sm); color: var(--color-text-muted);">
                                    CPF: <?= htmlspecialchars($enrollment['student_cpf']) ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: var(--spacing-sm); margin-bottom: var(--spacing-sm);">
                                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; padding: var(--spacing-xs); border-radius: var(--radius-sm); <?= $currentStatus === 'present' ? 'background: var(--color-success-light, #d1fae5); border: 1px solid var(--color-success);' : 'border: 1px solid var(--color-border);' ?>">
                                    <input 
                                        type="radio" 
                                        name="attendance[<?= $enrollment['student_id'] ?>][status]" 
                                        value="present"
                                        <?= $currentStatus === 'present' ? 'checked' : '' ?>
                                        onchange="updateStatus(this)"
                                    >
                                    <span style="font-size: var(--font-size-sm);">✓ Presente</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; padding: var(--spacing-xs); border-radius: var(--radius-sm); <?= $currentStatus === 'absent' ? 'background: var(--color-danger-light, #fee2e2); border: 1px solid var(--color-danger);' : 'border: 1px solid var(--color-border);' ?>">
                                    <input 
                                        type="radio" 
                                        name="attendance[<?= $enrollment['student_id'] ?>][status]" 
                                        value="absent"
                                        <?= $currentStatus === 'absent' ? 'checked' : '' ?>
                                        onchange="updateStatus(this)"
                                    >
                                    <span style="font-size: var(--font-size-sm);">✗ Ausente</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; padding: var(--spacing-xs); border-radius: var(--radius-sm); <?= $currentStatus === 'justified' ? 'background: var(--color-warning-light, #fef3c7); border: 1px solid var(--color-warning);' : 'border: 1px solid var(--color-border);' ?>">
                                    <input 
                                        type="radio" 
                                        name="attendance[<?= $enrollment['student_id'] ?>][status]" 
                                        value="justified"
                                        <?= $currentStatus === 'justified' ? 'checked' : '' ?>
                                        onchange="updateStatus(this)"
                                    >
                                    <span style="font-size: var(--font-size-sm);">⚠ Justificado</span>
                                </label>
                                
                                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; padding: var(--spacing-xs); border-radius: var(--radius-sm); <?= $currentStatus === 'makeup' ? 'background: var(--color-info-light, #dbeafe); border: 1px solid var(--color-info);' : 'border: 1px solid var(--color-border);' ?>">
                                    <input 
                                        type="radio" 
                                        name="attendance[<?= $enrollment['student_id'] ?>][status]" 
                                        value="makeup"
                                        <?= $currentStatus === 'makeup' ? 'checked' : '' ?>
                                        onchange="updateStatus(this)"
                                    >
                                    <span style="font-size: var(--font-size-sm);">🔄 Reposição</span>
                                </label>
                            </div>
                            
                            <div class="form-group" style="margin-top: var(--spacing-sm);">
                                <label class="form-label" style="font-size: var(--font-size-sm);">Observações</label>
                                <textarea 
                                    name="attendance[<?= $enrollment['student_id'] ?>][notes]" 
                                    class="form-input" 
                                    rows="2"
                                    placeholder="Observações opcionais..."
                                ><?= htmlspecialchars($attendance['notes'] ?? '') ?></textarea>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="form-actions" style="margin-top: var(--spacing-lg); padding-top: var(--spacing-md); border-top: 1px solid var(--color-border);">
                    <button type="submit" class="btn btn-primary" style="width: 100%;">
                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Salvar Presença
                    </button>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<script>
function updateStatus(radio) {
    const item = radio.closest('.attendance-item');
    const labels = item.querySelectorAll('label');
    labels.forEach(label => {
        label.style.background = '';
        label.style.border = '1px solid var(--color-border)';
    });
    
    const selectedLabel = radio.closest('label');
    const status = radio.value;
    const colors = {
        'present': { bg: 'var(--color-success-light, #d1fae5)', border: 'var(--color-success)' },
        'absent': { bg: 'var(--color-danger-light, #fee2e2)', border: 'var(--color-danger)' },
        'justified': { bg: 'var(--color-warning-light, #fef3c7)', border: 'var(--color-warning)' },
        'makeup': { bg: 'var(--color-info-light, #dbeafe)', border: 'var(--color-info)' }
    };
    
    if (colors[status]) {
        selectedLabel.style.background = colors[status].bg;
        selectedLabel.style.border = `1px solid ${colors[status].border}`;
    }
}

// Inicializar cores ao carregar
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="radio"]:checked').forEach(radio => {
        updateStatus(radio);
    });
});
</script>

<style>
.attendance-item {
    transition: all var(--transition-base);
}

@media (max-width: 768px) {
    .attendance-item {
        padding: var(--spacing-sm) !important;
    }
    
    .form-actions button {
        padding: var(--spacing-md) !important;
        font-size: var(--font-size-base) !important;
    }
}
</style>
