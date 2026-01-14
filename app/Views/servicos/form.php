<div class="page-header">
    <div>
        <h1><?= $service ? 'Editar' : 'Novo' ?> Serviço</h1>
        <p class="text-muted"><?= $service ? 'Atualize as informações do serviço' : 'Preencha os dados do novo serviço' ?></p>
    </div>
    <a href="<?= base_path('servicos') ?>" class="btn btn-outline">
        <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Voltar
    </a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="<?= base_path($service ? "servicos/{$service['id']}/atualizar" : 'servicos/criar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">

            <div class="form-group">
                <label class="form-label" for="name">Nome *</label>
                <input 
                    type="text" 
                    id="name" 
                    name="name" 
                    class="form-input" 
                    value="<?= htmlspecialchars($service['name'] ?? '') ?>" 
                    required
                    placeholder="Ex: 1ª Habilitação - Categoria B"
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="category">Categoria *</label>
                <input 
                    type="text" 
                    id="category" 
                    name="category" 
                    class="form-input" 
                    value="<?= htmlspecialchars($service['category'] ?? '') ?>" 
                    required
                    placeholder="Ex: 1ª habilitação, Renovação, etc."
                >
            </div>

            <div class="form-group">
                <label class="form-label" for="base_price">Preço Base (R$) *</label>
                <input 
                    type="number" 
                    id="base_price" 
                    name="base_price" 
                    class="form-input" 
                    value="<?= htmlspecialchars($service['base_price'] ?? '0.00') ?>" 
                    step="0.01"
                    min="0"
                    required
                >
            </div>

            <div class="form-group">
                <label class="form-label">Formas de Pagamento</label>
                <div style="display: flex; gap: var(--spacing-md); flex-wrap: wrap;">
                    <?php 
                    $methods = json_decode($service['payment_methods_json'] ?? '[]', true);
                    $availableMethods = ['pix', 'boleto', 'cartao'];
                    foreach ($availableMethods as $method): 
                    ?>
                    <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer;">
                        <input 
                            type="checkbox" 
                            name="payment_methods[]" 
                            value="<?= $method ?>"
                            <?= in_array($method, $methods) ? 'checked' : '' ?>
                        >
                        <span><?= ucfirst($method) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label style="display: flex; align-items: center; gap: var(--spacing-xs); cursor: pointer;">
                    <input 
                        type="checkbox" 
                        name="is_active" 
                        value="1"
                        <?= ($service['is_active'] ?? 1) ? 'checked' : '' ?>
                    >
                    <span>Serviço ativo</span>
                </label>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">
                    <?= $service ? 'Atualizar' : 'Criar' ?> Serviço
                </button>
                <a href="<?= base_path('servicos') ?>" class="btn btn-outline">
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
}
</style>
