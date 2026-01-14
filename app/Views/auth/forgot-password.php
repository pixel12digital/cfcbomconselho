<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperar Senha - Sistema CFC</title>
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
            
            <h2 style="margin-bottom: var(--spacing-md);">Recuperar Senha</h2>
            <p class="text-muted" style="margin-bottom: var(--spacing-lg);">
                Informe seu e-mail e enviaremos um link para redefinir sua senha.
            </p>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger" style="margin-bottom: var(--spacing-md);">
                    <?= htmlspecialchars($_SESSION['error']) ?>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success" style="margin-bottom: var(--spacing-md);">
                    <?= htmlspecialchars($_SESSION['success']) ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form method="POST" action="<?= base_path('/forgot-password') ?>">
                <div class="form-group">
                    <label class="form-label" for="email">E-mail</label>
                    <input 
                        type="email" 
                        name="email" 
                        id="email" 
                        class="form-input" 
                        required
                        autofocus
                        placeholder="seu@email.com"
                    >
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: var(--spacing-md);">
                    Enviar Link de Recuperação
                </button>
            </form>

            <div style="text-align: center;">
                <a href="<?= base_path('/login') ?>" class="text-link">Voltar para login</a>
            </div>
        </div>
    </div>
</body>
</html>
