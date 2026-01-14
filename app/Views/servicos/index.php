<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Serviços</h1>
            <p class="text-muted">Gestão de serviços oferecidos</p>
        </div>
        <?php if (\App\Services\PermissionService::check('servicos', 'create')): ?>
        <a href="<?= base_path('servicos/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Serviço
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($services)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhum serviço cadastrado ainda.</p>
            <?php if (\App\Services\PermissionService::check('servicos', 'create')): ?>
            <a href="<?= base_path('servicos/novo') ?>" class="btn btn-primary mt-3">
                Criar primeiro serviço
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
                        <th>Nome</th>
                        <th>Categoria</th>
                        <th>Preço Base</th>
                        <th>Formas de Pagamento</th>
                        <th>Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($services as $service): ?>
                    <tr>
                        <td><?= htmlspecialchars($service['name']) ?></td>
                        <td><?= htmlspecialchars($service['category']) ?></td>
                        <td>R$ <?= number_format($service['base_price'], 2, ',', '.') ?></td>
                        <td>
                            <?php 
                            $methods = json_decode($service['payment_methods_json'] ?? '[]', true);
                            if (!empty($methods)) {
                                echo implode(', ', array_map(function($m) {
                                    return ucfirst($m);
                                }, $methods));
                            } else {
                                echo '<span class="text-muted">-</span>';
                            }
                            ?>
                        </td>
                        <td>
                            <?php if ($service['is_active']): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-secondary">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <?php if (\App\Services\PermissionService::check('servicos', 'update')): ?>
                                <a href="<?= base_path("servicos/{$service['id']}/editar") ?>" class="btn-icon" title="Editar">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                <?php endif; ?>
                                <?php if (\App\Services\PermissionService::check('servicos', 'toggle')): ?>
                                <form method="POST" action="<?= base_path("servicos/{$service['id']}/toggle") ?>" style="display: inline;" onsubmit="return confirm('Deseja realmente alterar o status deste serviço?');">
                                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                    <button type="submit" class="btn-icon" title="<?= $service['is_active'] ? 'Desativar' : 'Ativar' ?>">
                                        <?php if ($service['is_active']): ?>
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        <?php else: ?>
                                            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        <?php endif; ?>
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

.mt-3 {
    margin-top: var(--spacing-md);
}
</style>
