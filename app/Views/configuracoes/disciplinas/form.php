<div class="page-header">
    <div>
        <h1><?= $discipline ? 'Editar' : 'Nova' ?> Disciplina</h1>
        <p class="text-muted"><?= $discipline ? 'Atualize as informações da disciplina' : 'Preencha os dados da nova disciplina' ?></p>
    </div>
    <a href="<?= base_path('configuracoes/disciplinas') ?>" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path($discipline ? "configuracoes/disciplinas/{$discipline['id']}/atualizar" : 'configuracoes/disciplinas/criar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="name">Nome da Disciplina *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?= htmlspecialchars($discipline['name'] ?? '') ?>" 
                    required
                    placeholder="Ex: Legislação de Trânsito"
                >
            </div>

            <div class="form-group">
                <label class="form-label">Carga Horária Padrão</label>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md);">
                    <div>
                        <label class="form-label" for="default_lessons_count" style="font-size: var(--font-size-sm);">Quantidade de Aulas</label>
                        <input 
                            type="number" 
                            id="default_lessons_count" 
                            name="default_lessons_count" 
                            class="form-input" 
                            value="<?= htmlspecialchars($discipline['default_lessons_count'] ?? '') ?>" 
                            min="1"
                            placeholder="Ex: 3"
                            onchange="calculateTotalMinutes()"
                        >
                    </div>
                    <div>
                        <label class="form-label" for="default_lesson_minutes" style="font-size: var(--font-size-sm);">Minutos por Aula</label>
                        <input 
                            type="number" 
                            id="default_lesson_minutes" 
                            name="default_lesson_minutes" 
                            class="form-input" 
                            value="<?= htmlspecialchars($discipline['default_lesson_minutes'] ?? '50') ?>" 
                            min="1"
                            max="180"
                            placeholder="50"
                            onchange="calculateTotalMinutes()"
                        >
                    </div>
                </div>
                <div style="margin-top: var(--spacing-sm); padding: var(--spacing-sm); background: var(--color-bg-light); border-radius: var(--border-radius);">
                    <strong>Total calculado:</strong> 
                    <span id="total_minutes_display"><?= htmlspecialchars(($discipline['default_minutes'] ?? '')) ?: '0' ?> minutos</span>
                    <span id="calculation_details" style="color: var(--color-text-muted); font-size: var(--font-size-sm);"></span>
                </div>
                <input type="hidden" id="default_minutes" name="default_minutes" value="<?= htmlspecialchars($discipline['default_minutes'] ?? '') ?>">
                <small class="form-hint">Deixe em branco se a carga horária variar por curso</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="sort_order">Ordem de Exibição</label>
                <input 
                    type="number" 
                    id="sort_order" 
                    name="sort_order" 
                    class="form-input" 
                    value="<?= htmlspecialchars($discipline['sort_order'] ?? 0) ?>" 
                    min="0"
                >
                <small class="form-hint">Disciplinas são ordenadas por este número (menor = primeiro)</small>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="active" 
                        value="1"
                        <?= ($discipline['active'] ?? 1) ? 'checked' : '' ?>
                    >
                    <span>Disciplina ativa</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $discipline ? 'Atualizar' : 'Criar' ?> Disciplina
                </button>
                <a href="<?= base_path('configuracoes/disciplinas') ?>" class="btn btn-outline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function calculateTotalMinutes() {
    const lessonsCount = parseInt(document.getElementById('default_lessons_count').value) || 0;
    const lessonMinutes = parseInt(document.getElementById('default_lesson_minutes').value) || 50;
    
    const total = lessonsCount * lessonMinutes;
    
    document.getElementById('default_minutes').value = total > 0 ? total : '';
    document.getElementById('total_minutes_display').textContent = total > 0 ? total + ' minutos' : '0 minutos';
    
    if (lessonsCount > 0 && lessonMinutes > 0) {
        document.getElementById('calculation_details').textContent = ` (${lessonsCount} × ${lessonMinutes})`;
    } else {
        document.getElementById('calculation_details').textContent = '';
    }
}

// Calcular ao carregar (para edição)
document.addEventListener('DOMContentLoaded', function() {
    calculateTotalMinutes();
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

.form-hint {
    display: block;
    margin-top: var(--spacing-xs);
    font-size: var(--font-size-sm);
    color: var(--color-text-muted);
}
</style>
