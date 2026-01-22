<div class="page-header">
    <div>
        <h1><?= $class ? 'Editar' : 'Nova' ?> Turma Teórica</h1>
        <p class="text-muted"><?= $class ? 'Atualize as informações da turma' : 'Configure a nova turma teórica' ?></p>
    </div>
    <a href="<?= base_path('turmas-teoricas') ?>" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path($class ? "turmas-teoricas/{$class['id']}/atualizar" : 'turmas-teoricas/criar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="course_id">Curso *</label>
                <select id="course_id" name="course_id" class="form-input" required>
                    <option value="">Selecione um curso</option>
                    <?php foreach ($courses as $course): ?>
                        <option value="<?= $course['id'] ?>" <?= ($class && $class['course_id'] == $course['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($course['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="instructor_id">Instrutor *</label>
                <select id="instructor_id" name="instructor_id" class="form-input" required>
                    <option value="">Selecione um instrutor</option>
                    <?php foreach ($instructors as $instructor): ?>
                        <option value="<?= $instructor['id'] ?>" <?= ($class && $class['instructor_id'] == $instructor['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($instructor['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label" for="name">Nome/Código da Turma</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?= htmlspecialchars($class['name'] ?? '') ?>" 
                    placeholder="Ex: Turma A - Manhã (opcional)"
                >
                <small class="form-hint">Opcional. Se não informado, será usado o nome do curso.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="start_date">Data de Início</label>
                <input 
                    type="date" 
                    id="start_date" 
                    name="start_date" 
                    class="form-input" 
                    value="<?= $class && $class['start_date'] ? date('Y-m-d', strtotime($class['start_date'])) : '' ?>"
                >
            </div>

            <?php if ($class): ?>
            <div class="form-group">
                <label class="form-label" for="status">Status</label>
                <select id="status" name="status" class="form-input">
                    <option value="scheduled" <?= $class['status'] == 'scheduled' ? 'selected' : '' ?>>Agendada</option>
                    <option value="in_progress" <?= $class['status'] == 'in_progress' ? 'selected' : '' ?>>Em Andamento</option>
                    <option value="completed" <?= $class['status'] == 'completed' ? 'selected' : '' ?>>Concluída</option>
                    <option value="cancelled" <?= $class['status'] == 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                </select>
            </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $class ? 'Atualizar' : 'Criar' ?> Turma
                </button>
                <a href="<?= base_path('turmas-teoricas') ?>" class="btn btn-outline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

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
