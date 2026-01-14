<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Alunos</h1>
            <p class="text-muted">Gestão de alunos do CFC</p>
        </div>
        <?php if (\App\Services\PermissionService::check('alunos', 'create')): ?>
        <a href="<?= base_path('alunos/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Novo Aluno
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card" style="margin-bottom: var(--spacing-md);">
    <div class="card-body">
        <form method="GET" action="<?= base_path('alunos') ?>" style="display: flex; gap: var(--spacing-md); align-items: flex-end;">
            <div class="form-group" style="flex: 1; margin-bottom: 0;">
                <label class="form-label" for="q">Buscar</label>
                <input 
                    type="search" 
                    id="q" 
                    name="q" 
                    class="form-input" 
                    value="<?= htmlspecialchars($search) ?>" 
                    placeholder="Nome, CPF ou telefone..."
                >
            </div>
            <button type="submit" class="btn btn-primary">Buscar</button>
            <?php if ($search): ?>
            <a href="<?= base_path('alunos') ?>" class="btn btn-outline">Limpar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if (empty($students)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted"><?= $search ? 'Nenhum aluno encontrado.' : 'Nenhum aluno cadastrado ainda.' ?></p>
            <?php if (!$search && \App\Services\PermissionService::check('alunos', 'create')): ?>
            <a href="<?= base_path('alunos/novo') ?>" class="btn btn-primary mt-3">
                Criar primeiro aluno
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
                        <th>CPF</th>
                        <th>Telefone</th>
                        <th>Email</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $studentModel = new \App\Models\Student();
                    foreach ($students as $student): 
                        $displayName = $studentModel->getFullName($student);
                    ?>
                    <tr>
                        <td>
                            <a href="<?= base_path("alunos/{$student['id']}") ?>" style="color: var(--color-primary); text-decoration: none; font-weight: 500;">
                                <?= htmlspecialchars($displayName) ?>
                            </a>
                        </td>
                        <td><?= htmlspecialchars($student['cpf']) ?></td>
                        <td><?= htmlspecialchars($student['phone'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($student['email'] ?: '-') ?></td>
                        <td>
                            <div class="table-actions">
                                <a href="<?= base_path("alunos/{$student['id']}") ?>" class="btn-icon" title="Ver detalhes">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <?php if (\App\Services\PermissionService::check('alunos', 'update')): ?>
                                <a href="<?= base_path("alunos/{$student['id']}/editar") ?>" class="btn-icon" title="Editar">
                                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
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
