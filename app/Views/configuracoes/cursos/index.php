<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Cursos Teóricos</h1>
            <p class="text-muted">Gerencie os templates de cursos teóricos</p>
        </div>
        <a href="<?= base_path('configuracoes/cursos/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Curso
        </a>
    </div>
</div>

<?php if (empty($courses)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhum curso cadastrado ainda.</p>
            <a href="<?= base_path('configuracoes/cursos/novo') ?>" class="btn btn-primary mt-3">
                Criar primeiro curso
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($courses as $course): ?>
                    <tr>
                        <td><?= htmlspecialchars($course['name']) ?></td>
                        <td>
                            <?php if ($course['active']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= base_path("configuracoes/cursos/{$course['id']}/editar") ?>" class="btn-icon" title="Editar">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
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

.mt-3 {
    margin-top: var(--spacing-md);
}
</style>
