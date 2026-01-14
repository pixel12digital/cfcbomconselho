<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Instrutores</h1>
            <p class="text-muted">Gestão de instrutores</p>
        </div>
        <a href="<?= base_path('instrutores/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Instrutor
        </a>
    </div>
</div>

<?php if (empty($instructors)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhum instrutor cadastrado ainda.</p>
            <a href="<?= base_path('instrutores/novo') ?>" class="btn btn-primary mt-3">
                Criar primeiro instrutor
            </a>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th style="width: 60px;">Foto</th>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th style="width: 120px;">Credencial</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instructors as $instructor): 
                        $credentialExpired = $instructor['credential_expired'] ?? false;
                        $credentialExpiryDate = $instructor['credential_expiry_date'] ?? null;
                    ?>
                    <tr>
                        <td>
                            <?php if (!empty($instructor['photo_path'])): ?>
                                <img src="<?= base_path("instrutores/{$instructor['id']}/foto") ?>" 
                                     alt="Foto" 
                                     style="width: 40px; height: 40px; object-fit: cover; border-radius: 50%; border: 1px solid var(--color-border, #e0e0e0);">
                            <?php else: ?>
                                <div style="width: 40px; height: 40px; background: var(--color-bg-secondary, #f5f5f5); border-radius: 50%; display: flex; align-items: center; justify-content: center; border: 1px solid var(--color-border, #e0e0e0);">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted, #999);">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="<?= base_path("instrutores/{$instructor['id']}/editar") ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($instructor['name']) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($instructor['cpf'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($instructor['phone'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($instructor['email'] ?: '-') ?></td>
                        <td>
                            <?php if ($credentialExpiryDate): ?>
                                <?php if ($credentialExpired): ?>
                                    <span style="color: #ef4444; font-weight: 600; font-size: 0.875rem;">⚠️ Vencida</span>
                                    <br>
                                    <small style="color: #ef4444;"><?= date('d/m/Y', strtotime($credentialExpiryDate)) ?></small>
                                <?php else: ?>
                                    <span style="color: #10b981; font-weight: 600; font-size: 0.875rem;">✓ Válida</span>
                                    <br>
                                    <small style="color: var(--color-text-muted, #666);">Até <?= date('d/m/Y', strtotime($credentialExpiryDate)) ?></small>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: var(--color-text-muted, #999); font-size: 0.875rem;">Não informada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($instructor['is_active']): ?>
                                <span style="color: #10b981; font-weight: 600;">Ativo</span>
                            <?php else: ?>
                                <span style="color: #ef4444; font-weight: 600;">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= base_path("instrutores/{$instructor['id']}/editar") ?>" class="btn-icon" title="Editar">
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
