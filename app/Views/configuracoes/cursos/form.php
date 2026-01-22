<?php
use App\Helpers\TheoryHelper;
?>
<div class="page-header">
    <div>
        <h1><?= $course ? 'Editar' : 'Novo' ?> Curso Teórico</h1>
        <p class="text-muted"><?= $course ? 'Atualize as informações do curso' : 'Configure o template de curso teórico' ?></p>
    </div>
    <a href="<?= base_path('configuracoes/cursos') ?>" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path($course ? "configuracoes/cursos/{$course['id']}/atualizar" : 'configuracoes/cursos/criar') ?>" id="courseForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="name">Nome do Curso *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?= htmlspecialchars($course['name'] ?? '') ?>" 
                    required
                    placeholder="Ex: 1ª Habilitação – AB (modelo CFC X)"
                >
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="active" 
                        value="1"
                        <?= ($course['active'] ?? 1) ? 'checked' : '' ?>
                    >
                    <span>Curso ativo</span>
                </label>
            </div>

            <hr style="margin: var(--spacing-lg) 0; border: none; border-top: 1px solid var(--color-border);">

            <div class="form-group">
                <label class="form-label">Disciplinas do Curso</label>
                <div id="disciplines-container">
                    <?php 
                    $courseDisciplines = $courseDisciplines ?? [];
                    if (empty($courseDisciplines)): 
                    ?>
                        <div class="discipline-item" data-index="0">
                            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: var(--spacing-md); align-items: end;">
                                <div>
                                    <label class="form-label">Disciplina *</label>
                                    <select name="disciplines[0][discipline_id]" class="form-input" required>
                                        <option value="">Selecione uma disciplina</option>
                                        <?php foreach ($disciplines as $discipline): ?>
                                            <option value="<?= $discipline['id'] ?>" data-default-lessons="<?= htmlspecialchars($discipline['default_lessons_count'] ?? '') ?>" data-default-lesson-minutes="<?= htmlspecialchars($discipline['default_lesson_minutes'] ?? '50') ?>">
                                                <?php
                                                $workload = TheoryHelper::formatTheoryWorkload(
                                                    $discipline['default_minutes'] ?? null,
                                                    $discipline['default_lessons_count'] ?? null,
                                                    $discipline['default_lesson_minutes'] ?? null,
                                                    false
                                                );
                                                echo htmlspecialchars($discipline['name']) . ' - ' . strip_tags($workload);
                                                ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: var(--font-size-sm);">Quantidade de Aulas</label>
                                    <input 
                                        type="number" 
                                        name="disciplines[0][lessons_count]" 
                                        class="form-input discipline-lessons-count" 
                                        min="1"
                                        placeholder="Padrão"
                                        data-index="0"
                                        onchange="calculateDisciplineMinutes(0)"
                                    >
                                </div>
                                <div>
                                    <label class="form-label" style="font-size: var(--font-size-sm);">Minutos/Aula</label>
                                    <input 
                                        type="number" 
                                        name="disciplines[0][lesson_minutes]" 
                                        class="form-input discipline-lesson-minutes" 
                                        min="1"
                                        max="180"
                                        value="50"
                                        placeholder="50"
                                        data-index="0"
                                        onchange="calculateDisciplineMinutes(0)"
                                    >
                                </div>
                                <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); padding: var(--spacing-xs);">
                                    <div class="discipline-total" data-index="0">Total: 0 min</div>
                                    <input type="hidden" name="disciplines[0][minutes]" class="discipline-minutes-total" data-index="0" value="">
                                </div>
                                <div>
                                    <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; margin-top: var(--spacing-md);">
                                        <input type="checkbox" name="disciplines[0][required]" value="1" checked>
                                        <span style="font-size: var(--font-size-sm);">Obrigatória</span>
                                    </label>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline btn-sm" onclick="removeDiscipline(this)" style="display: none;">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($courseDisciplines as $index => $cd): ?>
                            <div class="discipline-item" data-index="<?= $index ?>">
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: var(--spacing-md); align-items: end;">
                                    <div>
                                        <label class="form-label">Disciplina *</label>
                                        <select name="disciplines[<?= $index ?>][discipline_id]" class="form-input" required>
                                            <option value="">Selecione uma disciplina</option>
                                            <?php foreach ($disciplines as $discipline): ?>
                                                <option value="<?= $discipline['id'] ?>" <?= $discipline['id'] == $cd['discipline_id'] ? 'selected' : '' ?> data-default-lessons="<?= htmlspecialchars($discipline['default_lessons_count'] ?? '') ?>" data-default-lesson-minutes="<?= htmlspecialchars($discipline['default_lesson_minutes'] ?? '50') ?>">
                                                    <?php
                                                    $workload = TheoryHelper::formatTheoryWorkload(
                                                        $discipline['default_minutes'] ?? null,
                                                        $discipline['default_lessons_count'] ?? null,
                                                        $discipline['default_lesson_minutes'] ?? null,
                                                        false
                                                    );
                                                    echo htmlspecialchars($discipline['name']) . ' - ' . strip_tags($workload);
                                                    ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: var(--font-size-sm);">Quantidade de Aulas</label>
                                        <input 
                                            type="number" 
                                            name="disciplines[<?= $index ?>][lessons_count]" 
                                            class="form-input discipline-lessons-count" 
                                            value="<?= htmlspecialchars($cd['lessons_count'] ?? '') ?>"
                                            min="1"
                                            placeholder="Padrão"
                                            data-index="<?= $index ?>"
                                            onchange="calculateDisciplineMinutes(<?= $index ?>)"
                                        >
                                    </div>
                                    <div>
                                        <label class="form-label" style="font-size: var(--font-size-sm);">Minutos/Aula</label>
                                        <input 
                                            type="number" 
                                            name="disciplines[<?= $index ?>][lesson_minutes]" 
                                            class="form-input discipline-lesson-minutes" 
                                            value="<?= htmlspecialchars($cd['lesson_minutes'] ?? '50') ?>"
                                            min="1"
                                            max="180"
                                            placeholder="50"
                                            data-index="<?= $index ?>"
                                            onchange="calculateDisciplineMinutes(<?= $index ?>)"
                                        >
                                    </div>
                                    <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); padding: var(--spacing-xs);">
                                        <div class="discipline-total" data-index="<?= $index ?>">
                                            Total: <?= htmlspecialchars($cd['minutes'] ?? 0) ?> min
                                        </div>
                                        <input type="hidden" name="disciplines[<?= $index ?>][minutes]" class="discipline-minutes-total" data-index="<?= $index ?>" value="<?= htmlspecialchars($cd['minutes'] ?? '') ?>">
                                    </div>
                                    <div>
                                        <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; margin-top: var(--spacing-md);">
                                            <input type="checkbox" name="disciplines[<?= $index ?>][required]" value="1" <?= ($cd['required'] ?? 1) ? 'checked' : '' ?>>
                                            <span style="font-size: var(--font-size-sm);">Obrigatória</span>
                                        </label>
                                    </div>
                                    <div>
                                        <button type="button" class="btn btn-outline btn-sm" onclick="removeDiscipline(this)">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" class="btn btn-outline btn-sm" onclick="addDiscipline()" style="margin-top: var(--spacing-md);">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Adicionar Disciplina
                </button>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $course ? 'Atualizar' : 'Criar' ?> Curso
                </button>
                <a href="<?= base_path('configuracoes/cursos') ?>" class="btn btn-outline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
let disciplineIndex = <?= count($courseDisciplines ?? []) ?>;

function addDiscipline() {
    const container = document.getElementById('disciplines-container');
    const item = document.createElement('div');
    item.className = 'discipline-item';
    item.setAttribute('data-index', disciplineIndex);
    
    const disciplines = <?= json_encode($disciplines) ?>;
    let optionsHtml = '<option value="">Selecione uma disciplina</option>';
    disciplines.forEach(d => {
        // Formatar carga horária
        let workload = '';
        if (d.default_minutes) {
            const lessons = d.default_lessons_count || Math.ceil(d.default_minutes / (d.default_lesson_minutes || 50));
            const lessonMins = d.default_lesson_minutes || 50;
            workload = ` - ${lessons} ${lessons === 1 ? 'aula' : 'aulas'} (${d.default_minutes} min)`;
        }
        optionsHtml += `<option value="${d.id}" data-default-lessons="${d.default_lessons_count || ''}" data-default-lesson-minutes="${d.default_lesson_minutes || 50}">${d.name}${workload}</option>`;
    });
    
    item.innerHTML = `
        <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr auto; gap: var(--spacing-md); align-items: end; margin-top: var(--spacing-md);">
            <div>
                <label class="form-label">Disciplina *</label>
                <select name="disciplines[${disciplineIndex}][discipline_id]" class="form-input" required>
                    ${optionsHtml}
                </select>
            </div>
            <div>
                <label class="form-label" style="font-size: var(--font-size-sm);">Quantidade de Aulas</label>
                <input type="number" name="disciplines[${disciplineIndex}][lessons_count]" class="form-input discipline-lessons-count" min="1" placeholder="Padrão" data-index="${disciplineIndex}" onchange="calculateDisciplineMinutes(${disciplineIndex})">
            </div>
            <div>
                <label class="form-label" style="font-size: var(--font-size-sm);">Minutos/Aula</label>
                <input type="number" name="disciplines[${disciplineIndex}][lesson_minutes]" class="form-input discipline-lesson-minutes" min="1" max="180" value="50" placeholder="50" data-index="${disciplineIndex}" onchange="calculateDisciplineMinutes(${disciplineIndex})">
            </div>
            <div style="font-size: var(--font-size-xs); color: var(--color-text-muted); padding: var(--spacing-xs);">
                <div class="discipline-total" data-index="${disciplineIndex}">Total: 0 min</div>
                <input type="hidden" name="disciplines[${disciplineIndex}][minutes]" class="discipline-minutes-total" data-index="${disciplineIndex}" value="">
            </div>
            <div>
                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer; margin-top: var(--spacing-md);">
                    <input type="checkbox" name="disciplines[${disciplineIndex}][required]" value="1" checked>
                    <span style="font-size: var(--font-size-sm);">Obrigatória</span>
                </label>
            </div>
            <div>
                <button type="button" class="btn btn-outline btn-sm" onclick="removeDiscipline(this)">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    `;
    
    container.appendChild(item);
    disciplineIndex++;
    updateRemoveButtons();
}

function removeDiscipline(button) {
    const item = button.closest('.discipline-item');
    item.remove();
    updateRemoveButtons();
}

function updateRemoveButtons() {
    const items = document.querySelectorAll('.discipline-item');
    items.forEach((item, index) => {
        const button = item.querySelector('button[onclick*="removeDiscipline"]');
        if (button) {
            button.style.display = items.length > 1 ? 'block' : 'none';
        }
    });
}

// Calcular minutos totais para uma disciplina
function calculateDisciplineMinutes(index) {
    const countInput = document.querySelector(`input.discipline-lessons-count[data-index="${index}"]`);
    const minutesInput = document.querySelector(`input.discipline-lesson-minutes[data-index="${index}"]`);
    const totalDisplay = document.querySelector(`.discipline-total[data-index="${index}"]`);
    const totalHidden = document.querySelector(`input.discipline-minutes-total[data-index="${index}"]`);
    
    if (!countInput || !minutesInput || !totalDisplay || !totalHidden) return;
    
    const count = parseInt(countInput.value) || 0;
    const minutes = parseInt(minutesInput.value) || 50;
    const total = count * minutes;
    
    totalDisplay.textContent = `Total: ${total} min`;
    totalHidden.value = total > 0 ? total : '';
}

// Inicializar cálculos para disciplinas existentes
document.addEventListener('DOMContentLoaded', function() {
    updateRemoveButtons();
    document.querySelectorAll('.discipline-item').forEach((item, index) => {
        calculateDisciplineMinutes(index);
    });
});
</script>

<style>
.form-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-border);
}

.discipline-item {
    margin-bottom: var(--spacing-md);
    padding: var(--spacing-md);
    background: var(--color-bg-light);
    border-radius: var(--radius-md);
}

.btn-sm {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: var(--font-size-sm);
}
</style>
