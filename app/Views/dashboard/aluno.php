<?php
use App\Models\Student;
$studentModel = new Student();
?>

<div class="content-header">
    <h1 class="content-title">Meu Progresso</h1>
    <p class="content-subtitle">Bem-vindo, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Aluno') ?>!</p>
</div>

<?php if (!$student): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Seu cadastro ainda não está vinculado ao sistema. Entre em contato com a secretaria.</p>
        </div>
    </div>
<?php else: ?>
    <?php
    $fullName = $studentModel->getFullName($student);
    $studentStepsMap = [];
    foreach ($studentSteps ?? [] as $ss) {
        $studentStepsMap[$ss['step_id']] = $ss;
    }
    ?>

    <!-- Status Geral -->
    <div class="card" style="margin-bottom: var(--spacing-md);">
        <div class="card-body">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--spacing-md);">
                <div>
                    <label class="form-label" style="margin-bottom: var(--spacing-xs);">Status do Processo</label>
                    <div style="font-size: var(--font-size-lg); font-weight: var(--font-weight-semibold);">
                        <?= htmlspecialchars($statusGeral) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Próxima Aula -->
    <div class="card" style="margin-bottom: var(--spacing-md);">
        <div class="card-header">
            <h3 style="margin: 0;">Próxima Aula</h3>
        </div>
        <div class="card-body">
            <?php if ($nextLesson): ?>
                <?php
                $lessonDate = new \DateTime("{$nextLesson['scheduled_date']} {$nextLesson['scheduled_time']}");
                $endTime = clone $lessonDate;
                $endTime->modify("+{$nextLesson['duration_minutes']} minutes");
                ?>
                <div style="display: flex; flex-direction: column; gap: var(--spacing-sm);">
                    <div>
                        <strong style="font-size: var(--font-size-lg);">
                            <?= $lessonDate->format('d/m/Y') ?> às <?= $lessonDate->format('H:i') ?>
                        </strong>
                    </div>
                    <div class="text-muted">
                        Instrutor: <?= htmlspecialchars($nextLesson['instructor_name']) ?>
                    </div>
                    <?php if ($nextLesson['vehicle_plate']): ?>
                    <div class="text-muted">
                        Veículo: <?= htmlspecialchars($nextLesson['vehicle_plate']) ?>
                    </div>
                    <?php endif; ?>
                    <div style="margin-top: var(--spacing-sm); display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                        <a href="<?= base_path("agenda/{$nextLesson['id']}?from=dashboard") ?>" class="btn btn-sm btn-outline">
                            Ver detalhes
                        </a>
                        <?php
                        $now = new \DateTime();
                        $isFuture = $lessonDate > $now;
                        $isScheduled = ($nextLesson['status'] ?? '') === 'agendada';
                        $canRequestReschedule = $isFuture && $isScheduled && !($hasPendingRequest ?? false);
                        ?>
                        <?php if ($canRequestReschedule): ?>
                        <button type="button" class="btn btn-sm btn-primary" onclick="showRescheduleModal(<?= $nextLesson['id'] ?>)">
                            Solicitar reagendamento
                        </button>
                        <?php elseif ($hasPendingRequest ?? false): ?>
                        <span class="text-muted" style="font-size: var(--font-size-sm); align-self: center;">
                            Solicitação pendente
                        </span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-muted">Nenhuma aula agendada no momento.</p>
                <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">
                    Aguarde contato da secretaria ou consulte sua agenda.
                </p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Curso Teórico (Detalhes) -->
    <?php if (!empty($theoryClass) && !empty($theoryProgress)): ?>
    <div class="card" style="margin-bottom: var(--spacing-md);">
        <div class="card-header">
            <h3 style="margin: 0;">Curso Teórico</h3>
        </div>
        <div class="card-body">
            <div style="margin-bottom: var(--spacing-md);">
                <strong><?= htmlspecialchars($theoryClass['course_name']) ?></strong>
                <?php if ($theoryClass['name']): ?>
                    <div class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--spacing-xs);">
                        Turma: <?= htmlspecialchars($theoryClass['name']) ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <div style="margin-bottom: var(--spacing-md);">
                <label class="form-label" style="margin-bottom: var(--spacing-xs);">Progresso</label>
                <div style="background: var(--color-bg-light); border-radius: var(--radius-md); padding: var(--spacing-xs);">
                    <div style="background: var(--color-primary); height: 24px; border-radius: var(--radius-sm); width: <?= $theoryProgress['progress_percent'] ?>%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-size: var(--font-size-sm); font-weight: var(--font-weight-semibold);">
                        <?= $theoryProgress['progress_percent'] ?>%
                    </div>
                </div>
                <div style="margin-top: var(--spacing-xs); font-size: var(--font-size-sm); color: var(--color-text-muted);">
                    <?= $theoryProgress['attended_sessions'] ?> de <?= $theoryProgress['total_sessions'] ?> sessões concluídas
                </div>
            </div>
            
            <?php if ($theoryProgress['is_completed']): ?>
            <div style="padding: var(--spacing-sm); background: var(--color-success-light, #d1fae5); border: 1px solid var(--color-success); border-radius: var(--radius-md); color: var(--color-success); font-weight: var(--font-weight-semibold);">
                ✅ Curso Teórico Concluído!
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Progresso (Etapas) -->
    <?php if (!empty($activeEnrollment) && !empty($steps)): ?>
    <div class="card" style="margin-bottom: var(--spacing-md);">
        <div class="card-header">
            <h3 style="margin: 0;">Progresso da CNH</h3>
        </div>
        <div class="card-body">
            <div class="timeline">
                <?php foreach ($steps as $step): ?>
                <?php 
                $studentStep = $studentStepsMap[$step['id']] ?? null;
                $isCompleted = $studentStep && $studentStep['status'] === 'concluida';
                $isTheoryStep = $step['code'] === 'CURSO_TEORICO';
                ?>
                <div class="timeline-item <?= $isCompleted ? 'completed' : '' ?>">
                    <div class="timeline-marker">
                        <?php if ($isCompleted): ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        <?php else: ?>
                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                        <?php endif; ?>
                    </div>
                    <div class="timeline-content">
                        <div>
                            <h4 style="margin: 0 0 var(--spacing-xs) 0; font-size: var(--font-size-base);">
                                <?= htmlspecialchars($step['name']) ?>
                                <?php if ($isTheoryStep && !empty($theoryProgress)): ?>
                                    <span style="font-size: var(--font-size-sm); color: var(--color-text-muted); font-weight: normal;">
                                        (<?= $theoryProgress['progress_percent'] ?>%)
                                    </span>
                                <?php endif; ?>
                            </h4>
                            <?php if ($step['description']): ?>
                            <p class="text-muted" style="margin: 0; font-size: var(--font-size-sm);">
                                <?= htmlspecialchars($step['description']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Situação Financeira -->
    <div class="card">
        <div class="card-header">
            <h3 style="margin: 0;">Situação Financeira</h3>
        </div>
        <div class="card-body">
            <?php if ($hasPending): ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                    <div>
                        <label class="form-label" style="margin-bottom: var(--spacing-xs); font-size: var(--font-size-sm);">Em aberto</label>
                        <div style="color: var(--color-danger); font-weight: var(--font-weight-semibold); font-size: var(--font-size-lg);">
                            R$ <?= number_format($totalDebt, 2, ',', '.') ?>
                        </div>
                    </div>
                    <?php if (!empty($nextDueDate)): ?>
                    <div>
                        <label class="form-label" style="margin-bottom: var(--spacing-xs); font-size: var(--font-size-sm);">Próximo vencimento</label>
                        <div style="font-weight: var(--font-weight-semibold); font-size: var(--font-size-lg);">
                            <?= date('d/m/Y', strtotime($nextDueDate)) ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($overdueCount > 0): ?>
                    <div>
                        <label class="form-label" style="margin-bottom: var(--spacing-xs); font-size: var(--font-size-sm);">Parcelas em atraso</label>
                        <div style="color: var(--color-danger); font-weight: var(--font-weight-semibold); font-size: var(--font-size-lg);">
                            <?= $overdueCount ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <p class="text-muted" style="font-size: var(--font-size-sm); margin-bottom: var(--spacing-md);">
                    Entre em contato com a secretaria para regularizar.
                </p>
            <?php else: ?>
                <div style="color: var(--color-success); font-weight: var(--font-weight-semibold); margin-bottom: var(--spacing-md);">
                    ✅ Sem pendências
                </div>
            <?php endif; ?>
            <div style="display: flex; gap: var(--spacing-sm); flex-wrap: wrap;">
                <a href="<?= base_path('financeiro') ?>" class="btn btn-sm btn-outline">
                    Ver detalhes financeiros
                </a>
            </div>
        </div>
    </div>
<?php endif; ?>

<style>
.timeline {
    position: relative;
    padding-left: var(--spacing-lg);
}

.timeline-item {
    position: relative;
    padding-bottom: var(--spacing-lg);
    padding-left: var(--spacing-lg);
}

.timeline-item:not(:last-child)::before {
    content: '';
    position: absolute;
    left: 9px;
    top: 32px;
    bottom: -var(--spacing-lg);
    width: 2px;
    background: var(--color-border);
}

.timeline-item.completed:not(:last-child)::before {
    background: var(--color-success);
}

.timeline-marker {
    position: absolute;
    left: 0;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: var(--color-bg);
    border: 2px solid var(--color-border);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--color-text-muted);
}

.timeline-item.completed .timeline-marker {
    background: var(--color-success);
    border-color: var(--color-success);
    color: white;
}

.timeline-content {
    min-height: 40px;
}
</style>

<!-- Modal de Solicitação de Reagendamento -->
<div id="rescheduleModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center; padding: var(--spacing-md);">
    <div class="card" style="max-width: 500px; width: 100%; max-height: 90vh; overflow-y: auto;">
        <div class="card-header">
            <h3 style="margin: 0;">Solicitar Reagendamento</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="" id="rescheduleForm">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <div class="form-group">
                    <label class="form-label">Motivo <span style="color: var(--color-danger);">*</span></label>
                    <select name="reason" class="form-input" required>
                        <option value="">Selecione um motivo</option>
                        <option value="imprevisto">Imprevisto</option>
                        <option value="trabalho">Trabalho</option>
                        <option value="saude">Saúde</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Observação <small style="color: var(--color-text-muted, #666);">(opcional)</small></label>
                    <textarea name="message" class="form-input" rows="4" placeholder="Informe detalhes adicionais, se necessário..."></textarea>
                </div>
                <div style="display: flex; gap: var(--spacing-sm); justify-content: flex-end; margin-top: var(--spacing-md);">
                    <button type="button" class="btn btn-outline" onclick="hideRescheduleModal()">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="submitRescheduleBtn">Enviar Solicitação</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentLessonId = null;

function showRescheduleModal(lessonId) {
    currentLessonId = lessonId;
    const form = document.getElementById('rescheduleForm');
    form.action = '<?= base_path("agenda/") ?>' + lessonId + '/solicitar-reagendamento';
    document.getElementById('rescheduleModal').style.display = 'flex';
}

function hideRescheduleModal() {
    document.getElementById('rescheduleModal').style.display = 'none';
    const form = document.getElementById('rescheduleForm');
    form.reset();
    currentLessonId = null;
}

// Fechar modal ao clicar fora
document.getElementById('rescheduleModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        hideRescheduleModal();
    }
});

// Prevenir duplo submit
document.getElementById('rescheduleForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitRescheduleBtn');
    if (btn.disabled) {
        e.preventDefault();
        return false;
    }
    btn.disabled = true;
    btn.textContent = 'Enviando...';
});
</script>
