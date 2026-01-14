<?php
$currentRole = $_SESSION['current_role'] ?? '';
$isInstrutor = ($currentRole === 'INSTRUTOR');
$kmStart = $lesson['km_start'] ?? null;
?>
<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1>Concluir Aula</h1>
            <p class="text-muted">Registre a quilometragem final e observações</p>
        </div>
        <a href="<?= base_path("agenda/{$lesson['id']}") ?>" class="btn btn-outline">Voltar</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Informações da Aula</h2>
    </div>
    <div class="card-body">
        <div style="display: grid; gap: var(--spacing-sm); margin-bottom: var(--spacing-md);">
            <div>
                <label class="form-label">Aluno</label>
                <div><?= htmlspecialchars($lesson['student_name']) ?></div>
            </div>
            <div>
                <label class="form-label">Data e Hora</label>
                <div><?= date('d/m/Y H:i', strtotime("{$lesson['scheduled_date']} {$lesson['scheduled_time']}")) ?></div>
            </div>
            <div>
                <label class="form-label">Veículo</label>
                <div><?= htmlspecialchars($lesson['vehicle_plate'] ?? 'N/A') ?></div>
            </div>
            <?php if ($kmStart !== null): ?>
            <div>
                <label class="form-label">KM Inicial</label>
                <div style="font-size: 1.25rem; font-weight: 600; color: var(--color-primary);">
                    <?= number_format($kmStart, 0, ',', '.') ?> km
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h2>Dados para Conclusão da Aula</h2>
    </div>
    <div class="card-body">
        <form method="POST" action="<?= base_path("agenda/{$lesson['id']}/concluir") ?>" id="concluirForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <div class="form-group">
                <label class="form-label">
                    Quilometragem Final <span style="color: var(--color-danger);">*</span>
                </label>
                <input type="number" 
                       name="km_end" 
                       class="form-input" 
                       min="<?= $kmStart ?? 0 ?>" 
                       step="1"
                       required 
                       autofocus
                       placeholder="Ex: 58.550"
                       style="font-size: 1.25rem; font-weight: 600; text-align: center;">
                <small class="form-hint" style="display: block; margin-top: var(--spacing-xs); color: var(--color-text-muted, #666);">
                    Informe a quilometragem do veículo ao final da aula
                    <?php if ($kmStart !== null): ?>
                        <br><strong>KM Inicial registrado: <?= number_format($kmStart, 0, ',', '.') ?> km</strong> • O valor final deve ser maior ou igual ao inicial
                    <?php endif; ?>
                </small>
            </div>
            
            <div class="form-group">
                <label class="form-label">
                    Observação da Aula <small style="color: var(--color-text-muted, #666);">(opcional)</small>
                </label>
                <textarea name="instructor_notes" 
                          class="form-input" 
                          rows="4" 
                          placeholder="Observação interna do instrutor (visível apenas para você e o CFC)..."></textarea>
                <small class="form-hint" style="display: block; margin-top: var(--spacing-xs); color: var(--color-text-muted, #666);">
                    <strong>Privacidade:</strong> Esta observação é <strong>privada</strong> e visível apenas para você, administração e secretaria. <strong>Não será exibida ao aluno.</strong>
                </small>
            </div>
            
            <div style="display: flex; gap: var(--spacing-sm); justify-content: flex-end; margin-top: var(--spacing-md);">
                <a href="<?= base_path("agenda/{$lesson['id']}") ?>" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-success" id="submitBtn">
                    Concluir Aula
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Prevenir duplo submit
document.getElementById('concluirForm')?.addEventListener('submit', function(e) {
    const btn = document.getElementById('submitBtn');
    if (btn && btn.disabled) {
        e.preventDefault();
        return false;
    }
    if (btn) {
        btn.disabled = true;
        btn.textContent = 'Concluindo...';
    }
});

// Validação client-side: km final >= km inicial
<?php if ($kmStart !== null): ?>
document.getElementById('concluirForm')?.addEventListener('submit', function(e) {
    const kmEnd = parseInt(document.querySelector('input[name="km_end"]').value);
    const kmStart = <?= $kmStart ?>;
    
    if (kmEnd < kmStart) {
        e.preventDefault();
        alert('A quilometragem final deve ser maior ou igual à quilometragem inicial (' + kmStart.toLocaleString('pt-BR') + ' km).');
        return false;
    }
});
<?php endif; ?>

// Focar no campo de km ao carregar
document.querySelector('input[name="km_end"]')?.focus();
</script>

<style>
@media (max-width: 768px) {
    .card {
        margin-bottom: var(--spacing-md);
    }
    
    .form-input[type="number"] {
        font-size: 1.5rem !important;
        padding: var(--spacing-md);
    }
}
</style>
