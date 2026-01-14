<?php
$isEdit = isset($lesson) && $lesson;
$pageTitle = $isEdit ? 'Remarcar Aula' : 'Nova Aula';
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1><?= $pageTitle ?></h1>
            <p class="text-muted"><?= $isEdit ? 'Altere os dados da aula' : 'Agende uma nova aula' ?></p>
        </div>
        <a href="<?= base_path('agenda') ?>" class="btn btn-outline">
            Voltar
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-body">
        <form method="POST" action="<?= base_path($isEdit ? "agenda/{$lesson['id']}/atualizar" : 'agenda/criar') ?>" id="instructorForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <?php if ($isEdit): ?>
            <input type="hidden" name="lesson_id" value="<?= $lesson['id'] ?>">
            <?php endif; ?>
            
            <!-- Aluno e Matrícula -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Aluno *</label>
                    <?php if ($isEdit): ?>
                        <input type="text" class="form-input" value="<?= htmlspecialchars($lesson['student_name'] ?? '') ?>" disabled>
                        <input type="hidden" name="student_id" value="<?= $lesson['student_id'] ?>">
                    <?php else: ?>
                        <?php if ($student): ?>
                            <input type="text" class="form-input" value="<?= htmlspecialchars($student['name'] ?? $student['full_name'] ?? '') ?>" disabled>
                            <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                        <?php else: ?>
                            <select name="student_id" id="student_id" class="form-input" required onchange="loadEnrollments(this.value)">
                                <option value="">Selecione um aluno</option>
                                <?php if (!empty($students)): ?>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?= $s['id'] ?>" <?= ($studentId ?? '') == $s['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($s['full_name'] ?? $s['name']) ?> 
                                            <?= !empty($s['cpf']) ? ' - ' . htmlspecialchars($s['cpf']) : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Nenhum aluno cadastrado</option>
                                <?php endif; ?>
                            </select>
                            <?php if (empty($students)): ?>
                                <small class="form-hint" style="color: #ef4444;">
                                    ⚠️ Nenhum aluno cadastrado. <a href="<?= base_path('alunos/novo') ?>">Cadastre um aluno primeiro</a>.
                                </small>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Matrícula *</label>
                    <?php if ($isEdit): ?>
                        <input type="text" class="form-input" value="Matrícula #<?= $lesson['enrollment_id'] ?>" disabled>
                        <input type="hidden" name="enrollment_id" value="<?= $lesson['enrollment_id'] ?>">
                    <?php else: ?>
                        <select name="enrollment_id" id="enrollment_id" class="form-input" required>
                            <option value="">Selecione uma matrícula</option>
                            <?php if (!empty($enrollments)): ?>
                                <?php foreach ($enrollments as $enr): ?>
                                <option value="<?= $enr['id'] ?>" <?= ($enrollment && $enrollment['id'] == $enr['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($enr['service_name'] ?? 'Matrícula') ?> - 
                                    <?= $enr['financial_status'] === 'bloqueado' ? '⚠️ BLOQUEADO' : '✅ Ativa' ?>
                                </option>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <option value="" disabled>Selecione um aluno primeiro</option>
                            <?php endif; ?>
                        </select>
                        <?php if (empty($enrollments) && !$student): ?>
                            <small class="form-hint">
                                Selecione um aluno para carregar as matrículas
                            </small>
                        <?php elseif (empty($enrollments) && $student): ?>
                            <small class="form-hint" style="color: #ef4444;">
                                ⚠️ Este aluno não possui matrículas. <a href="<?= base_path('matriculas/novo?student_id=' . $student['id']) ?>">Criar matrícula</a>.
                            </small>
                        <?php endif; ?>
                        <?php if (!empty($enrollments)): ?>
                        <small class="form-hint">
                            <?php 
                            $blocked = array_filter($enrollments, fn($e) => $e['financial_status'] === 'bloqueado');
                            if (!empty($blocked)): 
                            ?>
                            ⚠️ Algumas matrículas estão bloqueadas financeiramente
                            <?php endif; ?>
                        </small>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Instrutor -->
            <div class="form-group" style="margin-bottom: var(--spacing-md);">
                <label class="form-label">Instrutor *</label>
                <select name="instructor_id" class="form-input" required>
                    <option value="">Selecione um instrutor</option>
                    <?php foreach ($instructors as $instructor): ?>
                    <option value="<?= $instructor['id'] ?>" <?= ($isEdit && $lesson['instructor_id'] == $instructor['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($instructor['name']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Veículo -->
            <div class="form-group" style="margin-bottom: var(--spacing-md);">
                <label class="form-label">Veículo *</label>
                <select name="vehicle_id" class="form-input" required>
                    <option value="">Selecione um veículo</option>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <option value="<?= $vehicle['id'] ?>" <?= ($isEdit && $lesson['vehicle_id'] == $vehicle['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($vehicle['plate']) ?> - <?= htmlspecialchars($vehicle['model'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Data e Hora -->
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Data *</label>
                    <input type="date" name="scheduled_date" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($lesson['scheduled_date']) : date('Y-m-d') ?>" 
                           min="<?= date('Y-m-d') ?>" required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Hora *</label>
                    <input type="time" name="scheduled_time" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($lesson['scheduled_time']) : '08:00' ?>" required>
                </div>
                
                <?php if (!$isEdit): ?>
                <div class="form-group">
                    <label class="form-label">Quantidade *</label>
                    <div style="display: flex; align-items: center; gap: var(--spacing-sm);">
                        <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; flex: 1;">
                            <input type="radio" name="lesson_count" value="1" checked onchange="updateLessonCount()">
                            <span>1</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; flex: 1;">
                            <input type="radio" name="lesson_count" value="2" onchange="updateLessonCount()">
                            <span>2</span>
                        </label>
                    </div>
                    <small class="form-hint" id="lesson_count_hint" style="margin-top: var(--spacing-xs); display: block;">1 aula de 50 min</small>
                    <input type="hidden" name="duration_minutes" id="duration_minutes" value="<?= \App\Config\Constants::DURACAO_AULA_PADRAO ?>">
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label class="form-label">Duração (minutos)</label>
                    <input type="number" name="duration_minutes" class="form-input" 
                           value="<?= htmlspecialchars($lesson['duration_minutes']) ?>" 
                           min="30" max="120" step="10" required>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Observações -->
            <div class="form-group" style="margin-bottom: var(--spacing-md);">
                <label class="form-label">Observações</label>
                <textarea name="notes" class="form-input" rows="3" placeholder="Observações sobre a aula..."><?= $isEdit ? htmlspecialchars($lesson['notes'] ?? '') : '' ?></textarea>
            </div>
            
            <?php if ($isEdit && in_array($lesson['status'], ['concluida', 'cancelada'])): ?>
            <div class="alert alert-warning">
                Esta aula já foi <?= $lesson['status'] === 'concluida' ? 'concluída' : 'cancelada' ?>. Não é possível editá-la.
            </div>
            <?php endif; ?>
            
            <!-- Botões -->
            <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; margin-top: var(--spacing-lg);">
                <a href="<?= base_path('agenda') ?>" class="btn btn-outline">Cancelar</a>
                <button type="submit" id="submitBtn" class="btn btn-primary" <?= $isEdit && in_array($lesson['status'], ['concluida', 'cancelada']) ? 'disabled' : '' ?>>
                    <?= $isEdit ? 'Remarcar Aula' : 'Agendar Aula' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Prevenir duplo submit
const form = document.getElementById('instructorForm');
if (form) {
    form.addEventListener('submit', function(e) {
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn && !submitBtn.disabled) {
            submitBtn.disabled = true;
            submitBtn.textContent = '<?= $isEdit ? 'Remarcando...' : 'Agendando...' ?>';
        }
    });
}

// Atualizar display baseado na quantidade de aulas
function updateLessonCount() {
    const lessonCountInputs = document.querySelectorAll('input[name="lesson_count"]');
    const durationMinutes = document.getElementById('duration_minutes');
    const hint = document.getElementById('lesson_count_hint');
    
    if (!durationMinutes) return;
    
    const lessonCount = parseInt(document.querySelector('input[name="lesson_count"]:checked')?.value || '1');
    const durationPerLesson = 50; // minutos
    
    durationMinutes.value = durationPerLesson.toString();
    
    if (hint) {
        if (lessonCount === 2) {
            hint.textContent = '2 aulas de 50 min consecutivas';
        } else {
            hint.textContent = '1 aula de 50 min';
        }
    }
}

// Inicializar
<?php if (!$isEdit): ?>
document.addEventListener('DOMContentLoaded', function() {
    updateLessonCount();
    const lessonCountInputs = document.querySelectorAll('input[name="lesson_count"]');
    lessonCountInputs.forEach(input => {
        input.addEventListener('change', updateLessonCount);
    });
});
<?php endif; ?>

// Carregar matrículas quando aluno for selecionado
function loadEnrollments(studentId) {
    const enrollmentSelect = document.getElementById('enrollment_id');
    
    if (!studentId) {
        enrollmentSelect.innerHTML = '<option value="">Selecione um aluno primeiro</option>';
        enrollmentSelect.disabled = true;
        return;
    }
    
    enrollmentSelect.innerHTML = '<option value="">Carregando...</option>';
    enrollmentSelect.disabled = true;
    
    // Buscar matrículas do aluno via AJAX
    fetch('<?= base_path("api/students") ?>/' + studentId + '/enrollments')
        .then(response => response.json())
        .then(data => {
            enrollmentSelect.innerHTML = '<option value="">Selecione uma matrícula</option>';
            
            if (data.success && data.enrollments && data.enrollments.length > 0) {
                data.enrollments.forEach(function(enr) {
                    const option = document.createElement('option');
                    option.value = enr.id;
                    const status = enr.financial_status === 'bloqueado' ? '⚠️ BLOQUEADO' : '✅ Ativa';
                    option.textContent = (enr.service_name || 'Matrícula') + ' - ' + status;
                    enrollmentSelect.appendChild(option);
                });
                enrollmentSelect.disabled = false;
            } else {
                enrollmentSelect.innerHTML = '<option value="" disabled>Nenhuma matrícula encontrada</option>';
                enrollmentSelect.disabled = true;
                
                // Mostrar mensagem
                const hint = document.createElement('small');
                hint.className = 'form-hint';
                hint.style.color = '#ef4444';
                hint.innerHTML = '⚠️ Este aluno não possui matrículas. <a href="<?= base_path("matriculas/novo") ?>?student_id=' + studentId + '">Criar matrícula</a>.';
                
                // Remover hint anterior se existir
                const existingHint = enrollmentSelect.parentElement.querySelector('.form-hint');
                if (existingHint) {
                    existingHint.remove();
                }
                
                enrollmentSelect.parentElement.appendChild(hint);
            }
        })
        .catch(error => {
            console.error('Erro ao carregar matrículas:', error);
            enrollmentSelect.innerHTML = '<option value="">Erro ao carregar matrículas</option>';
            enrollmentSelect.disabled = true;
        });
}

// Inicializar estado do select de matrículas
<?php if (!$isEdit && !$student): ?>
document.getElementById('enrollment_id').disabled = true;
<?php endif; ?>
</script>

