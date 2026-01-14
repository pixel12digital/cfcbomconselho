<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Veículos</h1>
            <p class="text-muted">Gestão da frota de veículos</p>
        </div>
        <a href="<?= base_path('veiculos/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Veículo
        </a>
    </div>
</div>

<?php if (empty($vehicles)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhum veículo cadastrado ainda.</p>
            <a href="<?= base_path('veiculos/novo') ?>" class="btn btn-primary mt-3">
                Cadastrar primeiro veículo
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Placa</th>
                        <th>Categoria</th>
                        <th>Marca/Modelo</th>
                        <th>Ano</th>
                        <th>Cor</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($vehicles as $vehicle): ?>
                    <tr>
                        <td>
                            <a href="<?= base_path("veiculos/{$vehicle['id']}/editar") ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 600;">
                                <?= htmlspecialchars($vehicle['plate']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($vehicle['category'] ?: '-') ?></td>
                        <td>
                            <?php if ($vehicle['brand'] || $vehicle['model']): ?>
                                <?= htmlspecialchars(trim(($vehicle['brand'] ?? '') . ' ' . ($vehicle['model'] ?? ''))) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= $vehicle['year'] ? htmlspecialchars($vehicle['year']) : '-' ?></td>
                        <td><?= htmlspecialchars($vehicle['color'] ?: '-') ?></td>
                        <td>
                            <?php if ($vehicle['is_active']): ?>
                                <span style="color: #10b981; font-weight: 600;">Ativo</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: 600;">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= base_path("veiculos/{$vehicle['id']}/editar") ?>" class="btn-icon" title="Editar">
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
