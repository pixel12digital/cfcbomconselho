<?php
use App\Helpers\TheoryHelper;
?>
<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Disciplinas Teóricas</h1>
            <p class="text-muted">Gerencie as disciplinas do curso teórico</p>
        </div>
        <a href="<?= base_path('configuracoes/disciplinas/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nova Disciplina
        </a>
    </div>
</div>

<?php if (empty($disciplines)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhuma disciplina cadastrada ainda.</p>
            <a href="<?= base_path('configuracoes/disciplinas/novo') ?>" class="btn btn-primary mt-3">
                Criar primeira disciplina
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 50px;">Ordem</th>
                        <th>Nome</th>
                        <th>Carga Horária Padrão</th>
                        <th>Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($disciplines as $discipline): ?>
                    <tr>
                        <td><?= htmlspecialchars($discipline['sort_order']) ?></td>
                        <td><?= htmlspecialchars($discipline['name']) ?></td>
                        <td>
                            <?= TheoryHelper::formatTheoryWorkload(
                                $discipline['default_minutes'] ?? null,
                                $discipline['default_lessons_count'] ?? null,
                                $discipline['default_lesson_minutes'] ?? null,
                                false
                            ) ?>
                        </td>
                        <td>
                            <?php if ($discipline['active']): ?>
                                <span class="badge badge-success">Ativa</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inativa</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= base_path("configuracoes/disciplinas/{$discipline['id']}/editar") ?>" class="btn-icon" title="Editar">
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
