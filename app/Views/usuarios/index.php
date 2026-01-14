<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Gerenciamento de Usuários</h1>
            <p class="text-muted">Central de Acessos - Controle de identidades e credenciais</p>
        </div>
        <?php if (\App\Services\PermissionService::check('usuarios', 'create') || $_SESSION['current_role'] === 'ADMIN'): ?>
        <a href="<?= base_path('usuarios/novo') ?>" class="btn btn-primary">
            <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Criar Acesso Administrativo
        </a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($studentsWithoutAccess) || !empty($instructorsWithoutAccess)): ?>
<div class="card" style="margin-bottom: var(--spacing-md); background-color: #fff3cd; border-color: #ffc107;">
    <div class="card-header" style="background-color: #ffc107; color: #000;">
        <h3 style="margin: 0; font-size: var(--font-size-lg); display: flex; align-items: center; gap: 8px;">
            <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            Pendências de Acesso
        </h3>
    </div>
    <div class="card-body">
        <p style="margin-bottom: var(--spacing-md);">
            <strong>Alunos e instrutores sem acesso ao sistema:</strong> Estes cadastros existem, mas não possuem credenciais de login. 
            Você pode criar acesso vinculado clicando em "Criar Acesso" abaixo.
        </p>
        
        <?php if (!empty($studentsWithoutAccess)): ?>
        <div style="margin-bottom: var(--spacing-md);">
            <h4 style="margin-bottom: var(--spacing-sm);">Alunos sem acesso (<?= count($studentsWithoutAccess) ?>)</h4>
            <table class="table" style="background: white;">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>E-mail</th>
                        <th style="width: 150px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($studentsWithoutAccess as $student): ?>
                    <tr>
                        <td><?= htmlspecialchars($student['full_name'] ?: $student['name']) ?></td>
                        <td><?= htmlspecialchars($student['cpf']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td>
                            <form method="POST" action="<?= base_path('usuarios/criar-acesso-aluno') ?>" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Criar Acesso
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($instructorsWithoutAccess)): ?>
        <div>
            <h4 style="margin-bottom: var(--spacing-sm);">Instrutores sem acesso (<?= count($instructorsWithoutAccess) ?>)</h4>
            <table class="table" style="background: white;">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>CPF</th>
                        <th>E-mail</th>
                        <th style="width: 150px;">Ação</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($instructorsWithoutAccess as $instructor): ?>
                    <tr>
                        <td><?= htmlspecialchars($instructor['name']) ?></td>
                        <td><?= htmlspecialchars($instructor['cpf']) ?></td>
                        <td><?= htmlspecialchars($instructor['email']) ?></td>
                        <td>
                            <form method="POST" action="<?= base_path('usuarios/criar-acesso-instrutor') ?>" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                <input type="hidden" name="instructor_id" value="<?= $instructor['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-primary">
                                    Criar Acesso
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<?php if (empty($users)): ?>
    <div class="card">
        <div class="card-body text-center" style="padding: 60px 20px;">
            <p class="text-muted">Nenhum usuário cadastrado ainda.</p>
            <?php if (\App\Services\PermissionService::check('usuarios', 'create') || $_SESSION['current_role'] === 'ADMIN'): ?>
            <a href="<?= base_path('usuarios/novo') ?>" class="btn btn-primary mt-3">
                Criar primeiro acesso
            </a>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body" style="padding: 0;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Nome</th>
                        <th>E-mail</th>
                        <th>Perfil</th>
                        <th>Vínculo</th>
                        <th>Status</th>
                        <th style="width: 120px;">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars($user['nome']) ?></strong>
                        </td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td>
                            <?php 
                            $roles = $user['roles_array'] ?? [];
                            foreach ($roles as $role): 
                            ?>
                                <span class="badge badge-primary"><?= htmlspecialchars($role) ?></span>
                            <?php endforeach; ?>
                        </td>
                        <td>
                            <?php if ($user['instructor_id']): ?>
                                <span class="text-muted">Instrutor: <?= htmlspecialchars($user['instructor_name']) ?></span>
                            <?php elseif ($user['student_id']): ?>
                                <span class="text-muted">Aluno: <?= htmlspecialchars($user['student_full_name'] ?: $user['student_name']) ?></span>
                            <?php else: ?>
                                <span class="text-muted">Administrativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($user['status'] === 'ativo'): ?>
                                <span class="badge badge-success">Ativo</span>
                            <?php else: ?>
                                <span class="badge badge-danger">Inativo</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (\App\Services\PermissionService::check('usuarios', 'update') || $_SESSION['current_role'] === 'ADMIN'): ?>
                            <a href="<?= base_path("usuarios/{$user['id']}/editar") ?>" class="btn btn-sm btn-outline">
                                Editar
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>
