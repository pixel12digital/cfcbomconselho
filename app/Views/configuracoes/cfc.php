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
                            src="<?= base_path('configuracoes/cfc/logo') ?>" 
                            alt="Logo do CFC" 
                            style="max-width: 150px; max-height: 150px; border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                            onerror="this.style.display='none'; this.parentElement.parentElement.querySelector('.logo-error')?.style.display='block';"
                        >
                        <div class="logo-error" style="display: none; padding: 10px; background: var(--color-warning-light); border-radius: var(--radius-md); color: var(--color-warning);">
                            Erro ao carregar logo
                        </div>
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

        <!-- Preview do logo selecionado (antes do upload) -->
        <div id="logo-preview-container" style="display: none; margin-bottom: var(--spacing-lg); padding: var(--spacing-md); background: var(--color-gray-50); border-radius: var(--radius-md);">
            <div style="display: flex; align-items: center; gap: var(--spacing-md);">
                <div>
                    <img 
                        id="logo-preview" 
                        src="" 
                        alt="Preview do logo" 
                        style="max-width: 150px; max-height: 150px; border-radius: var(--radius-md); box-shadow: 0 2px 8px rgba(0,0,0,0.1);"
                    >
                </div>
                <div style="flex: 1;">
                    <p style="margin: 0 0 var(--spacing-xs) 0; font-weight: 500;">Preview do logo selecionado</p>
                    <p id="logo-preview-name" style="margin: 0; font-size: 0.875rem; color: var(--color-gray-600);"></p>
                </div>
            </div>
        </div>

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
                    <?= !$hasLogo ? 'required' : '' ?>
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

        <script>
        // Preview do logo antes do upload
        const logoInput = document.getElementById('logo');
        logoInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            console.log('[UPLOAD DEBUG] Arquivo selecionado:', {
                name: file?.name,
                size: file?.size,
                type: file?.type,
                lastModified: file?.lastModified
            });
            
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('logo-preview').src = e.target.result;
                    document.getElementById('logo-preview-name').textContent = file.name + ' (' + (file.size / 1024).toFixed(2) + ' KB)';
                    document.getElementById('logo-preview-container').style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                document.getElementById('logo-preview-container').style.display = 'none';
            }
        });

        // Debug do form submit com interceptação AJAX
        const uploadForm = document.querySelector('form[action*="logo/upload"]');
        if (uploadForm) {
            uploadForm.addEventListener('submit', function(e) {
                const formData = new FormData(uploadForm);
                const file = logoInput.files[0];
                
                console.log('[UPLOAD DEBUG] ========================================');
                console.log('[UPLOAD DEBUG] Form submit iniciado');
                console.log('[UPLOAD DEBUG] Action:', uploadForm.action);
                console.log('[UPLOAD DEBUG] Method:', uploadForm.method);
                console.log('[UPLOAD DEBUG] Enctype:', uploadForm.enctype);
                console.log('[UPLOAD DEBUG] Arquivo selecionado:', {
                    hasFile: !!file,
                    fileName: file?.name,
                    fileSize: file?.size,
                    fileSizeMB: file ? (file.size / 1024 / 1024).toFixed(2) + ' MB' : 'N/A',
                    fileType: file?.type
                });
                console.log('[UPLOAD DEBUG] FormData keys:', Array.from(formData.keys()));
                console.log('[UPLOAD DEBUG] CSRF Token:', formData.get('csrf_token') ? 'presente' : 'ausente');
                console.log('[UPLOAD DEBUG] ========================================');

                // Verificar se arquivo foi selecionado
                if (!file) {
                    console.error('[UPLOAD DEBUG] ❌ ERRO: Nenhum arquivo selecionado!');
                    e.preventDefault();
                    alert('Por favor, selecione um arquivo antes de fazer upload.');
                    return false;
                }

                // Verificar tamanho (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    console.error('[UPLOAD DEBUG] ❌ ERRO: Arquivo muito grande!', {
                        size: file.size,
                        sizeMB: (file.size / 1024 / 1024).toFixed(2) + ' MB',
                        maxSize: '5 MB'
                    });
                    e.preventDefault();
                    alert('Arquivo muito grande. Máximo 5MB.');
                    return false;
                }

                console.log('[UPLOAD DEBUG] ✅ Validações passaram, enviando requisição...');
                
                // Interceptar via AJAX para capturar resposta
                e.preventDefault();
                
                const submitButton = uploadForm.querySelector('button[type="submit"]');
                const originalText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.textContent = 'Enviando...';
                
                fetch(uploadForm.action, {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                })
                .then(function(response) {
                    console.log('[UPLOAD DEBUG] ========================================');
                    console.log('[UPLOAD DEBUG] Resposta recebida');
                    console.log('[UPLOAD DEBUG] Status:', response.status, response.statusText);
                    console.log('[UPLOAD DEBUG] Headers:', Object.fromEntries(response.headers.entries()));
                    
                    // Ler headers de debug
                    const debugHeaders = {};
                    response.headers.forEach(function(value, key) {
                        if (key.toLowerCase().startsWith('x-upload-debug')) {
                            debugHeaders[key] = value;
                        }
                    });
                    if (Object.keys(debugHeaders).length > 0) {
                        console.log('[UPLOAD DEBUG] Headers de debug:', debugHeaders);
                    }
                    
                    console.log('[UPLOAD DEBUG] ========================================');
                    
                    // Redirecionar para a página (mesmo comportamento do form submit)
                    if (response.redirected || response.status === 302 || response.status === 301) {
                        console.log('[UPLOAD DEBUG] Redirect detectado, recarregando página...');
                        window.location.href = response.url || window.location.href;
                    } else {
                        // Se não houver redirect, recarregar a página atual
                        window.location.reload();
                    }
                })
                .catch(function(error) {
                    console.error('[UPLOAD DEBUG] ❌ ERRO na requisição:', error);
                    submitButton.disabled = false;
                    submitButton.textContent = originalText;
                    alert('Erro ao fazer upload: ' + error.message);
                });
                
                return false;
            });

        } else {
            console.error('[UPLOAD DEBUG] ❌ Form não encontrado!');
        }
        </script>

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
        
        <form method="POST" action="<?= base_path('configuracoes/cfc/salvar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="nome">Nome do CFC <span class="text-danger">*</span></label>
                <input 
                    type="text" 
                    name="nome" 
                    id="nome"
                    class="form-input" 
                    value="<?= htmlspecialchars($cfc['nome'] ?? '') ?>" 
                    required
                    maxlength="255"
                >
                <small class="form-hint">
                    O nome do CFC é usado no manifest do aplicativo PWA.
                </small>
            </div>

            <?php if (!empty($cfc['cnpj'])): ?>
                <div class="form-group">
                    <label class="form-label" for="cnpj">CNPJ</label>
                    <input 
                        type="text" 
                        name="cnpj"
                        id="cnpj"
                        class="form-input" 
                        value="<?= htmlspecialchars($cfc['cnpj']) ?>" 
                        maxlength="18"
                    >
                </div>
            <?php endif; ?>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    Salvar Informações
                </button>
            </div>
        </form>
    </div>
</div>
