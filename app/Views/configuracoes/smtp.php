<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Configurações SMTP</h1>
            <p class="text-muted">Configure o envio de e-mails do sistema</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: var(--spacing-md);">
    <div class="card-body">
        <form method="POST" action="<?= base_path('configuracoes/smtp/salvar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="host">Servidor SMTP <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        name="host" 
                        id="host" 
                        class="form-input" 
                        value="<?= htmlspecialchars($settings['host'] ?? '') ?>" 
                        required
                        placeholder="smtp.gmail.com"
                    >
                </div>

                <div class="form-group" style="width: 120px;">
                    <label class="form-label" for="port">Porta <span class="text-danger">*</span></label>
                    <input 
                        type="number" 
                        name="port" 
                        id="port" 
                        class="form-input" 
                        value="<?= htmlspecialchars($settings['port'] ?? '587') ?>" 
                        required
                        min="1"
                        max="65535"
                    >
                </div>
            </div>

            <div class="form-group">
                <label class="form-label" for="username">Usuário <span class="text-danger">*</span></label>
                <input 
                    type="text" 
                    name="username" 
                    id="username" 
                    class="form-input" 
                    value="<?= htmlspecialchars($settings['username'] ?? '') ?>" 
                    required
                    placeholder="seu@email.com"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="password">Senha <span class="text-danger">*</span></label>
                <input 
                    type="password" 
                    name="password" 
                    id="password" 
                    class="form-input" 
                    <?= empty($settings) ? 'required' : '' ?>
                    placeholder="<?= empty($settings) ? 'Digite a senha' : 'Deixe em branco para manter a atual' ?>"
                >
                <small class="form-text"><?= empty($settings) ? '' : 'Deixe em branco para manter a senha atual.' ?></small>
            </div>

            <div class="form-group">
                <label class="form-label" for="encryption">Criptografia</label>
                <select name="encryption" id="encryption" class="form-input">
                    <option value="tls" <?= ($settings['encryption'] ?? 'tls') === 'tls' ? 'selected' : '' ?>>TLS</option>
                    <option value="ssl" <?= ($settings['encryption'] ?? '') === 'ssl' ? 'selected' : '' ?>>SSL</option>
                    <option value="none" <?= ($settings['encryption'] ?? '') === 'none' ? 'selected' : '' ?>>Nenhuma</option>
                </select>
            </div>

            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="from_email">E-mail Remetente <span class="text-danger">*</span></label>
                    <input 
                        type="email" 
                        name="from_email" 
                        id="from_email" 
                        class="form-input" 
                        value="<?= htmlspecialchars($settings['from_email'] ?? '') ?>" 
                        required
                        placeholder="noreply@cfc.com"
                    >
                </div>

                <div class="form-group" style="flex: 1;">
                    <label class="form-label" for="from_name">Nome Remetente</label>
                    <input 
                        type="text" 
                        name="from_name" 
                        id="from_name" 
                        class="form-input" 
                        value="<?= htmlspecialchars($settings['from_name'] ?? '') ?>" 
                        placeholder="Sistema CFC"
                    >
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Salvar Configurações
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Testar Configuração</h3>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_path('configuracoes/smtp/testar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label class="form-label" for="test_email">E-mail de Teste</label>
                <div style="display: flex; gap: var(--spacing-md);">
                    <input 
                        type="email" 
                        name="test_email" 
                        id="test_email" 
                        class="form-input" 
                        required
                        placeholder="seu@email.com"
                        style="flex: 1;"
                    >
                    <button type="submit" class="btn btn-outline">
                        Enviar Teste
                    </button>
                </div>
                <small class="form-text">Envie um e-mail de teste para verificar se a configuração está funcionando.</small>
            </div>
        </form>
    </div>
</div>
