<div class="content-header">
    <h1 class="content-title">Dashboard</h1>
    <p class="content-subtitle">Bem-vindo, <?= htmlspecialchars($_SESSION['user_name'] ?? 'Usuário') ?>!</p>
</div>

<div class="card">
    <div class="card-body">
        <p>Bem-vindo ao sistema CFC. Em breve, aqui você terá acesso a todas as funcionalidades.</p>
        <p><strong>Papel atual:</strong> <?= htmlspecialchars($_SESSION['current_role'] ?? 'N/A') ?></p>
    </div>
</div>
