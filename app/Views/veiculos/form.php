<?php
$isEdit = isset($vehicle) && $vehicle;
$pageTitle = $isEdit ? 'Editar Veículo' : 'Novo Veículo';
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1><?= $pageTitle ?></h1>
            <p class="text-muted"><?= $isEdit ? 'Atualize as informações do veículo' : 'Preencha os dados do novo veículo' ?></p>
        </div>
        <a href="<?= base_path('veiculos') ?>" class="btn btn-outline">
            Voltar
        </a>
    </div>
</div>

<div class="card" style="max-width: 800px; margin: 0 auto;">
    <div class="card-body">
        <form method="POST" action="<?= base_path($isEdit ? "veiculos/{$vehicle['id']}/atualizar" : 'veiculos/criar') ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Placa *</label>
                    <input type="text" name="plate" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($vehicle['plate']) : '' ?>" 
                           placeholder="ABC-1234" 
                           style="text-transform: uppercase;"
                           required>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Categoria *</label>
                    <select name="category" class="form-input" required>
                        <option value="">Selecione</option>
                        <option value="A" <?= $isEdit && $vehicle['category'] === 'A' ? 'selected' : '' ?>>A - Moto</option>
                        <option value="B" <?= ($isEdit && $vehicle['category'] === 'B') || !$isEdit ? 'selected' : '' ?>>B - Carro</option>
                        <option value="C" <?= $isEdit && $vehicle['category'] === 'C' ? 'selected' : '' ?>>C - Caminhão</option>
                        <option value="D" <?= $isEdit && $vehicle['category'] === 'D' ? 'selected' : '' ?>>D - Ônibus</option>
                        <option value="E" <?= $isEdit && $vehicle['category'] === 'E' ? 'selected' : '' ?>>E - Carreta</option>
                    </select>
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Marca</label>
                    <input type="text" name="brand" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($vehicle['brand'] ?? '') : '' ?>" 
                           placeholder="Ex: Fiat, Volkswagen">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Modelo</label>
                    <input type="text" name="model" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($vehicle['model'] ?? '') : '' ?>" 
                           placeholder="Ex: Uno, Gol">
                </div>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: var(--spacing-md); margin-bottom: var(--spacing-md);">
                <div class="form-group">
                    <label class="form-label">Ano</label>
                    <input type="number" name="year" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($vehicle['year'] ?? '') : '' ?>" 
                           placeholder="2020" 
                           min="1900" 
                           max="<?= date('Y') + 1 ?>">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Cor</label>
                    <input type="text" name="color" class="form-input" 
                           value="<?= $isEdit ? htmlspecialchars($vehicle['color'] ?? '') : '' ?>" 
                           placeholder="Ex: Branco, Prata">
                </div>
            </div>
            
            <div class="form-group" style="margin-bottom: var(--spacing-md);">
                <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                    <input type="checkbox" name="is_active" value="1" 
                           <?= $isEdit && $vehicle['is_active'] ? 'checked' : 'checked' ?>>
                    <span>Veículo ativo</span>
                </label>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; margin-top: var(--spacing-lg);">
                <a href="<?= base_path('veiculos') ?>" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                </button>
            </div>
        </form>
    </div>
</div>
