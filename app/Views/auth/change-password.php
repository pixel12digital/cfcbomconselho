<div class="content-header">
    <h1 class="content-title">Alterar Senha</h1>
    <p class="content-subtitle">
        <?php if (!empty($_SESSION['must_change_password'])): ?>
            ⚠️ Por segurança, você precisa alterar sua senha no primeiro acesso.
        <?php else: ?>
            Atualize sua senha de acesso ao sistema
        <?php endif; ?>
    </p>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path('/change-password') ?>" id="changePasswordForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="current_password">
                    Senha Atual <span style="color: var(--color-danger);">*</span>
                </label>
                <input 
                    type="password" 
                    name="current_password" 
                    id="current_password" 
                    class="form-input" 
                    required
                    autofocus
                    placeholder="Digite sua senha atual"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password">
                    Nova Senha <span style="color: var(--color-danger);">*</span>
                </label>
                <input 
                    type="password" 
                    name="new_password" 
                    id="new_password" 
                    class="form-input" 
                    required
                    minlength="8"
                    placeholder="Mínimo 8 caracteres"
                >
                <small class="form-help">A senha deve ter no mínimo 8 caracteres.</small>
            </div>

            <div class="form-group">
                <label class="form-label" for="new_password_confirm">
                    Confirmar Nova Senha <span style="color: var(--color-danger);">*</span>
                </label>
                <input 
                    type="password" 
                    name="new_password_confirm" 
                    id="new_password_confirm" 
                    class="form-input" 
                    required
                    minlength="8"
                    placeholder="Digite a senha novamente"
                >
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Salvar
                </button>
                <a href="<?= base_path('/dashboard') ?>" class="btn btn-outline">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<style>
.form-actions {
    display: flex;
    gap: var(--spacing-md);
    margin-top: var(--spacing-lg);
    padding-top: var(--spacing-md);
    border-top: 1px solid var(--color-border);
    flex-wrap: wrap;
}

@media (max-width: 375px) {
    .form-actions {
        flex-direction: column;
    }
    
    .form-actions .btn {
        width: 100%;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
    const currentPassword = document.getElementById('current_password');
    const newPassword = document.getElementById('new_password');
    const newPasswordConfirm = document.getElementById('new_password_confirm');
    
    // Validação em tempo real da confirmação de senha
    newPasswordConfirm.addEventListener('input', function() {
        if (this.value && newPassword.value !== this.value) {
            this.setCustomValidity('As senhas não coincidem');
        } else {
            this.setCustomValidity('');
        }
    });
    
    newPassword.addEventListener('input', function() {
        if (newPasswordConfirm.value && this.value !== newPasswordConfirm.value) {
            newPasswordConfirm.setCustomValidity('As senhas não coincidem');
        } else {
            newPasswordConfirm.setCustomValidity('');
        }
    });
    
    // Validação no submit
    form.addEventListener('submit', function(e) {
        // Validação de comprimento mínimo
        if (newPassword.value.length < 8) {
            e.preventDefault();
            alert('A senha deve ter no mínimo 8 caracteres.');
            newPassword.focus();
            return false;
        }
        
        // Validação de confirmação
        if (newPassword.value !== newPasswordConfirm.value) {
            e.preventDefault();
            alert('As senhas não coincidem!');
            newPasswordConfirm.focus();
            return false;
        }
        
        // Validação de senha atual preenchida
        if (!currentPassword.value) {
            e.preventDefault();
            alert('Por favor, informe sua senha atual.');
            currentPassword.focus();
            return false;
        }
    });
});
</script>
