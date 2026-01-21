<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Configurações do CFC</h1>
            <p class="text-muted">Configure o logo do CFC para o aplicativo PWA</p>
        </div>
    </div>
</div>

<div class="card" style="margin-bottom: var(--spacing-md);">
    <div class="card-body">
        <h2 style="margin-bottom: var(--spacing-md); font-size: 1.25rem;">Logo do CFC</h2>
        <p class="text-muted" style="margin-bottom: var(--spacing-lg);">
            O logo será usado para gerar os ícones do aplicativo PWA (192x192 e 512x512).
            Recomendamos uma imagem quadrada ou com proporção próxima de 1:1.
        </p>

        <?php if ($hasLogo): ?>
            <div style="margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--color-gray-50); border-radius: var(--radius-md);">
                <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                    <div>
                        <img 
                            src="<?= base_path($cfc['logo_path']) ?>" 
                            alt="Logo do CFC" 
                            style="max-width: 150px; max-height: 150px; border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                        >
                    </div>
                    <div style="flex: 1;">
                        <p style="margin: 0 0 var(--spacing-xs) 0; font-weight: 500;">Logo atual</p>
                        <p style="margin: 0; font-size: 0.875rem; color: var(--color-gray-600);">
                            <?= htmlspecialchars(basename($cfc['logo_path'])) ?>
                        </p>
                        <?php if ($iconsExist): ?>
                            <p style="margin: var(--spacing-xs) 0 0 0; font-size: 0.875rem; color: var(--color-success);">
                                ✅ Ícones PWA gerados com sucesso
                            </p>
                        <?php else: ?>
                            <p style="margin: var(--spacing-xs) 0 0 0; font-size: 0.875rem; color: var(--color-warning);">
                                ⚠️ Ícones PWA não encontrados (serão gerados ao fazer upload)
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div style="margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--color-gray-50); border-radius: var(--radius-md); border: 2px dashed var(--color-gray-300); text-align: center;">
                <p style="margin: 0; color: var(--color-gray-600);">
                    Nenhum logo cadastrado. Faça upload de um logo para personalizar os ícones do aplicativo.
                </p>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= base_path('configuracoes/cfc/logo/upload') ?>" enctype="multipart/form-data">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="logo">
                    <?= $hasLogo ? 'Substituir Logo' : 'Fazer Upload do Logo' ?>
                    <span class="text-danger">*</span>
                </label>
                <input 
                    type="file" 
                    name="logo" 
                    id="logo" 
                    class="form-input" 
                    accept="image/jpeg,image/jpg,image/png,image/webp"
                    required
                >
                <small class="form-hint">
                    Formatos aceitos: JPG, PNG, WEBP. Tamanho máximo: 5MB. 
                    Recomendado: imagem quadrada (1:1) com pelo menos 512x512 pixels.
                </small>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $hasLogo ? 'Substituir Logo' : 'Fazer Upload' ?>
                </button>
            </div>
        </form>

        <?php if ($hasLogo): ?>
            <hr style="margin: var(--spacing-lg) 0; border: none; border-top: 1px solid var(--color-gray-200);">
            
            <form method="POST" action="<?= base_path('configuracoes/cfc/logo/remover') ?>" onsubmit="return confirm('Tem certeza que deseja remover o logo? Os ícones PWA também serão removidos.');">
                <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                <button type="submit" class="btn btn-danger">
                    Remover Logo
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <h2 style="margin-bottom: var(--spacing-md); font-size: 1.25rem;">Informações do CFC</h2>
        
        <div class="form-group">
            <label class="form-label">Nome do CFC</label>
            <input 
                type="text" 
                class="form-input" 
                value="<?= htmlspecialchars($cfc['nome'] ?? '') ?>" 
                readonly
                style="background: var(--color-gray-50);"
            >
            <small class="form-hint">
                O nome do CFC é usado no manifest do aplicativo PWA.
            </small>
        </div>

        <?php if (!empty($cfc['cnpj'])): ?>
            <div class="form-group">
                <label class="form-label">CNPJ</label>
                <input 
                    type="text" 
                    class="form-input" 
                    value="<?= htmlspecialchars($cfc['cnpj']) ?>" 
                    readonly
                    style="background: var(--color-gray-50);"
                >
            </div>
        <?php endif; ?>
    </div>
</div>
