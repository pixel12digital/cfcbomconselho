<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha - Sistema CFC</title>
    <link rel="stylesheet" href="<?= asset_url('css/tokens.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/components.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/layout.css') ?>">
    <link rel="stylesheet" href="<?= asset_url('css/utilities.css') ?>">
    <style>
        body {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            background: var(--color-gray-50);
        }
        .auth-container {
            width: 100%;
            max-width: 400px;
            padding: var(--spacing-lg);
        }
        .auth-card {
            background: white;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--spacing-xl);
        }
        .auth-logo {
            text-align: center;
            margin-bottom: var(--spacing-xl);
            font-size: 24px;
            font-weight: bold;
            color: var(--color-primary);
        }
    </style>
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-logo">CFC Sistema</div>
            
            <h2 style="margin-bottom: var(--spacing-md);">Redefinir Senha</h2>
            <p class="text-muted" style="margin-bottom: var(--spacing-lg);">
                Olá, <strong><?= htmlspecialchars($user['nome'] ?? '') ?></strong>. Defina sua nova senha abaixo.
            </p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" style="margin-bottom: var(--spacing-md);">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <form method="POST" action="<?= base_path('/reset-password') ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

                <div class="form-group">
                    <label class="form-label" for="password">Nova Senha</label>
                    <input 
                        type="password" 
                        name="password" 
                        id="password" 
                        class="form-input" 
                        required
                        minlength="8"
                        autofocus
                        placeholder="Mínimo 8 caracteres"
                    >
                    <small class="form-text">A senha deve ter no mínimo 8 caracteres.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="password_confirm">Confirmar Nova Senha</label>
                    <input 
                        type="password" 
                        name="password_confirm" 
                        id="password_confirm" 
                        class="form-input" 
                        required
                        minlength="8"
                        placeholder="Digite a senha novamente"
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: var(--spacing-md);">
                    Redefinir Senha
                </button>
            </form>

            <div style="text-align: center;">
                <a href="<?= base_path('/login') ?>" class="text-link">Voltar para login</a>
            </div>
        </div>
    </div>
</body>
</html>
