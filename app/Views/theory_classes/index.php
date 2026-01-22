<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Turmas Teóricas</h1>
            <p class="text-muted">Gerencie as turmas de curso teórico</p>
        </div>
        <?php if (\App\Services\PermissionService::check('turmas_teoricas', 'create')): ?>
        <a href="<?= base_path('turmas-teoricas/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Turma
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($status)): ?>
<div style="margin-bottom: var(--spacing-md);">
    <a href="<?= base_path('turmas-teoricas') ?>" class="btn btn-sm btn-outline">
        Todas as turmas
    </a>
</div>
<?php endif; ?>

<?php if (empty($classes)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhuma turma cadastrada ainda.</p>
            <?php if (\App\Services\PermissionService::check('turmas_teoricas', 'create')): ?>
            <a href="<?= base_path('turmas-teoricas/novo') ?>" class="btn btn-primary mt-3">
                Criar primeira turma
            </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome/Código</th>
                        <th>Curso</th>
                        <th>Instrutor</th>
                        <th>Data Início</th>
                        <th>Alunos</th>
                        <th>Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($classes as $class): ?>
                    <tr>
                        <td><?= htmlspecialchars($class['name'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($class['course_name']) ?></td>
                        <td><?= htmlspecialchars($class['instructor_name']) ?></td>
                        <td>
                            <?php if ($class['start_date']): ?>
                                <?= date('d/m/Y', strtotime($class['start_date'])) ?>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td><?= $class['enrolled_count'] ?? 0 ?></td>
                        <td>
                            <?php
                            $statusLabels = [
                                'scheduled' => ['label' => 'Agendada', 'class' => 'badge-secondary'],
                                'in_progress' => ['label' => 'Em Andamento', 'class' => 'badge-primary'],
                                'completed' => ['label' => 'Concluída', 'class' => 'badge-success'],
                                'cancelled' => ['label' => 'Cancelada', 'class' => 'badge-danger']
                            ];
                            $statusInfo = $statusLabels[$class['status']] ?? ['label' => $class['status'], 'class' => 'badge-secondary'];
                            ?>
                            <span class="badge <?= $statusInfo['class'] ?>"><?= $statusInfo['label'] ?></span>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= base_path("turmas-teoricas/{$class['id']}") ?>" class="btn-icon" title="Ver detalhes">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <?php if (\App\Services\PermissionService::check('turmas_teoricas', 'update')): ?>
                                <a href="<?= base_path("turmas-teoricas/{$class['id']}/editar") ?>" class="btn-icon" title="Editar">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                <?php if (\App\Services\PermissionService::check('turmas_teoricas', 'delete')): ?>
                                <form method="POST" action="<?= base_path("turmas-teoricas/{$class['id']}/excluir") ?>" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir esta turma? Esta ação não pode ser desfeita.');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn-icon btn-icon-danger" title="Excluir">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: var(--spacing-lg);
}

.page-header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

.table-actions {
    display: flex;
    gap: var(--spacing-xs);
}

.btn-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    padding: 0;
    border: 1px solid var(--color-border);
    background: transparent;
    color: var(--color-text);
    border-radius: var(--radius-md);
    cursor: pointer;
    transition: all var(--transition-base);
}

.btn-icon:hover {
    background: var(--color-bg-light);
    border-color: var(--color-primary);
    color: var(--color-primary);
}

.btn-icon-danger {
    color: var(--color-danger, #dc3545);
}

.btn-icon-danger:hover {
    background: var(--color-danger-light, #fee);
    border-color: var(--color-danger, #dc3545);
    color: var(--color-danger, #dc3545);
}

.table-actions form {
    display: inline;
}

.mt-3 {
    margin-top: var(--spacing-md);
}

.btn-sm {
    padding: var(--spacing-xs) var(--spacing-sm);
    font-size: var(--font-size-sm);
}
</style>
