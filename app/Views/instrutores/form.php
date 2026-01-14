<?php
$isEdit = isset($instructor) && $instructor;
$pageTitle = $isEdit ? 'Editar Instrutor' : 'Novo Instrutor';
$instructorModel = new \App\Models\Instructor();
$credentialExpired = $isEdit ? $instructorModel->isCredentialExpired($instructor) : false;
$availability = $availability ?? []; // Garantir que existe
$daysOfWeek = [
    0 => ['name' => 'Domingo', 'short' => 'Dom'],
    1 => ['name' => 'Segunda-feira', 'short' => 'Seg'],
    2 => ['name' => 'Terça-feira', 'short' => 'Ter'],
    3 => ['name' => 'Quarta-feira', 'short' => 'Qua'],
    4 => ['name' => 'Quinta-feira', 'short' => 'Qui'],
    5 => ['name' => 'Sexta-feira', 'short' => 'Sex'],
    6 => ['name' => 'Sábado', 'short' => 'Sáb']
];
?>

<div class="page-header">
    <div class="page-header-content">
        <div>
            <h1><?= $pageTitle ?></h1>
            <p class="text-muted"><?= $isEdit ? 'Atualize as informações do instrutor' : 'Preencha os dados do novo instrutor' ?></p>
        </div>
        <a href="<?= base_path('instrutores') ?>" class="btn btn-outline">
            Voltar
        </a>
    </div>
</div>

<div class="card" style="max-width: 1000px; margin: 0 auto;">
    <div class="card-body">
        <form method="POST" action="<?= base_path($isEdit ? "instrutores/{$instructor['id']}/atualizar" : 'instrutores/criar') ?>" enctype="multipart/form-data" id="instructorForm">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            
            <!-- Foto -->
            <div class="form-section">
                <h3 class="form-section-title">Foto</h3>
                <div style="display: flex; gap: var(--spacing-md); align-items: flex-start;">
                    <div>
                        <?php if ($isEdit && !empty($instructor['photo_path'])): ?>
                            <img src="<?= base_path("instrutores/{$instructor['id']}/foto") ?>" 
                                 alt="Foto do instrutor" 
                                 style="width: 120px; height: 120px; object-fit: cover; border-radius: 8px; border: 2px solid var(--color-border, #e0e0e0);">
                        <?php else: ?>
                            <div style="width: 120px; height: 120px; background: var(--color-bg-secondary, #f5f5f5); border-radius: 8px; display: flex; align-items: center; justify-content: center; border: 2px solid var(--color-border, #e0e0e0);">
                                <svg width="48" height="48" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="color: var(--color-text-muted, #999);">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div style="flex: 1;">
                        <div class="form-group">
                            <label class="form-label">Foto do Instrutor</label>
                            <input type="file" name="photo" accept="image/jpeg,image/jpg,image/png,image/webp" class="form-input">
                            <small class="form-hint">Formatos aceitos: JPG, PNG, WEBP. Máximo 2MB.</small>
                        </div>
                        <?php if ($isEdit && !empty($instructor['photo_path'])): ?>
                        <form method="POST" action="<?= base_path("instrutores/{$instructor['id']}/foto/remover") ?>" style="margin-top: var(--spacing-sm);">
                            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                            <button type="submit" class="btn btn-sm btn-outline btn-danger" onclick="return confirm('Deseja realmente remover a foto?')">
                                Remover Foto
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Informações Básicas -->
            <div class="form-section">
                <h3 class="form-section-title">Informações Básicas</h3>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Nome Completo *</label>
                        <input type="text" name="name" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['name']) : '' ?>" 
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label">CPF *</label>
                        <input type="text" name="cpf" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['cpf'] ?? '') : '' ?>" 
                               placeholder="000.000.000-00"
                               required>
                    </div>
                    
                    <div class="form-group form-col-2">
                        <label class="form-label">Data de Nascimento *</label>
                        <input type="date" name="birth_date" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['birth_date'] ?? '') : '' ?>" 
                               max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                               min="<?= date('Y-m-d', strtotime('-100 years')) ?>"
                               required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label">Telefone *</label>
                        <input type="text" name="phone" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['phone'] ?? '') : '' ?>" 
                               placeholder="(00) 00000-0000"
                               required>
                    </div>
                    
                    <div class="form-group form-col-2">
                        <label class="form-label">E-mail *</label>
                        <input type="email" name="email" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['email'] ?? '') : '' ?>" 
                               placeholder="email@exemplo.com"
                               required>
                    </div>
                </div>
            </div>
            
            <!-- Dados Profissionais -->
            <div class="form-section">
                <h3 class="form-section-title">Dados Profissionais</h3>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label">Número da CNH</label>
                        <input type="text" name="license_number" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['license_number'] ?? '') : '' ?>" 
                               placeholder="00000000000">
                    </div>
                    
                    <div class="form-group form-col-2">
                        <label class="form-label">Categoria da CNH</label>
                        <input type="text" name="license_category" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['license_category'] ?? '') : '' ?>" 
                               placeholder="Ex: B">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label">Categorias de Habilitação</label>
                        <input type="text" name="license_categories" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['license_categories'] ?? '') : '' ?>" 
                               placeholder="Ex: AB, BCD">
                        <small class="form-hint">Separe múltiplas categorias com vírgula</small>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label">Número da Credencial *</label>
                        <input type="text" name="credential_number" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['credential_number'] ?? '') : '' ?>" 
                               placeholder="Número da credencial do instrutor"
                               required>
                    </div>
                    
                    <div class="form-group form-col-2">
                        <label class="form-label">Validade da Credencial *</label>
                        <input type="date" name="credential_expiry_date" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['credential_expiry_date'] ?? '') : '' ?>" 
                               min="<?= date('Y-m-d') ?>"
                               required>
                        <?php if ($credentialExpired): ?>
                        <div style="color: #ef4444; font-weight: 600; margin-top: 4px; font-size: 0.875rem;">
                            ⚠️ Credencial vencida! Instrutor não poderá ser selecionado para novas aulas.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                            <input type="checkbox" name="is_active" value="1" 
                                   <?= $isEdit && $instructor['is_active'] ? 'checked' : 'checked' ?>>
                            <span>Instrutor ativo</span>
                        </label>
                    </div>
                </div>
            </div>
            
            <!-- Disponibilidade de Horários -->
            <div class="form-section">
                <h3 class="form-section-title">Disponibilidade de Horários</h3>
                <p class="text-muted" style="margin-bottom: var(--spacing-md);">Configure os horários em que o instrutor está disponível para ministrar aulas.</p>
                
                <div style="display: grid; gap: var(--spacing-sm);">
                    <?php foreach ($daysOfWeek as $dayNum => $dayInfo): 
                        $av = $availability[$dayNum] ?? null;
                        $isAvailable = $av ? $av['is_available'] : false;
                        $startTime = $av ? $av['start_time'] : '08:00';
                        $endTime = $av ? $av['end_time'] : '18:00';
                    ?>
                    <div style="display: grid; grid-template-columns: 150px 1fr 1fr 1fr; gap: var(--spacing-sm); align-items: center; padding: var(--spacing-sm); background: var(--color-bg-secondary, #f5f5f5); border-radius: var(--radius-sm, 4px);">
                        <div>
                            <label style="display: flex; align-items: center; gap: var(--spacing-sm); cursor: pointer;">
                                <input type="checkbox" 
                                       name="availability_day_<?= $dayNum ?>" 
                                       value="1" 
                                       <?= $isAvailable ? 'checked' : '' ?>
                                       onchange="toggleDayAvailability(<?= $dayNum ?>, this.checked)">
                                <span style="font-weight: 600;"><?= $dayInfo['name'] ?></span>
                            </label>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.875rem;">Início</label>
                            <input type="time" 
                                   name="availability_start_<?= $dayNum ?>" 
                                   class="form-input" 
                                   value="<?= $startTime ?>"
                                   id="availability_start_<?= $dayNum ?>"
                                   <?= !$isAvailable ? 'disabled' : '' ?>>
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label class="form-label" style="font-size: 0.875rem;">Fim</label>
                            <input type="time" 
                                   name="availability_end_<?= $dayNum ?>" 
                                   class="form-input" 
                                   value="<?= $endTime ?>"
                                   id="availability_end_<?= $dayNum ?>"
                                   <?= !$isAvailable ? 'disabled' : '' ?>>
                        </div>
                        <div style="text-align: right;">
                            <?php if ($isAvailable): ?>
                                <span style="color: #10b981; font-weight: 600;">✓ Disponível</span>
                            <?php else: ?>
                                <span style="color: #6b7280;">Indisponível</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Endereço -->
            <div class="form-section">
                <h3 class="form-section-title">Endereço</h3>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label" for="cep">CEP</label>
                        <div class="cep-input-wrapper">
                            <input type="text" id="cep" name="cep" class="form-input" 
                                   value="<?= $isEdit ? htmlspecialchars($instructor['cep'] ?? '') : '' ?>"
                                   placeholder="00000-000"
                                   maxlength="9">
                            <span id="cep-loading-text" class="cep-loading-text" style="display: none;">Buscando CEP...</span>
                        </div>
                    </div>
                    <div class="form-group form-col-2">
                        <label class="form-label" for="address_street">Logradouro</label>
                        <input type="text" id="address_street" name="address_street" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['address_street'] ?? '') : '' ?>"
                               placeholder="Rua, Avenida, etc">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-col-3">
                        <label class="form-label" for="address_number">Número</label>
                        <input type="text" id="address_number" name="address_number" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['address_number'] ?? '') : '' ?>">
                    </div>
                    <div class="form-group form-col-3">
                        <label class="form-label" for="address_complement">Complemento</label>
                        <input type="text" id="address_complement" name="address_complement" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['address_complement'] ?? '') : '' ?>"
                               placeholder="Apto, Bloco, etc">
                    </div>
                    <div class="form-group form-col-3">
                        <label class="form-label" for="address_neighborhood">Bairro</label>
                        <input type="text" id="address_neighborhood" name="address_neighborhood" class="form-input" 
                               value="<?= $isEdit ? htmlspecialchars($instructor['address_neighborhood'] ?? '') : '' ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group form-col-2">
                        <label class="form-label" for="address_state_id">UF</label>
                        <select id="address_state_id" name="address_state_id" class="form-input">
                            <option value="">Selecione</option>
                            <?php foreach ($states as $state): ?>
                            <option value="<?= $state['id'] ?>" <?= ($isEdit && $instructor['address_state_id'] == $state['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($state['uf']) ?> - <?= htmlspecialchars($state['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group form-col-2">
                        <label class="form-label" for="address_city_search">Cidade</label>
                        <div class="city-autocomplete-wrapper">
                            <input type="text" id="address_city_search" class="form-input city-search-input" 
                                   placeholder="Digite para buscar cidade..."
                                   autocomplete="off"
                                   value="<?= $isEdit && $currentCity ? htmlspecialchars($currentCity['name']) : '' ?>"
                                   disabled>
                            <input type="hidden" id="address_city_id" name="address_city_id" 
                                   value="<?= $isEdit && $currentCity ? $currentCity['id'] : '' ?>">
                            <div id="address_city_dropdown" class="city-dropdown" style="display: none;"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Observações -->
            <div class="form-section">
                <h3 class="form-section-title">Observações</h3>
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Observações</label>
                        <textarea name="notes" class="form-input" rows="4" 
                                  placeholder="Observações administrativas sobre o instrutor..."><?= $isEdit ? htmlspecialchars($instructor['notes'] ?? '') : '' ?></textarea>
                    </div>
                </div>
            </div>
            
            <div style="display: flex; gap: var(--spacing-md); justify-content: flex-end; margin-top: var(--spacing-lg);">
                <a href="<?= base_path('instrutores') ?>" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                    <?= $isEdit ? 'Atualizar' : 'Cadastrar' ?>
                </button>
            </div>
        </form>
    </div>
</div>

<style>
.form-section {
    margin-bottom: var(--spacing-xl);
    padding-bottom: var(--spacing-lg);
    border-bottom: 1px solid var(--color-border, #e0e0e0);
}

.form-section:last-of-type {
    border-bottom: none;
}

.form-section-title {
    font-size: 1.125rem;
    font-weight: 600;
    margin-bottom: var(--spacing-md);
    color: var(--color-text, #333);
}

.form-row {
    display: grid;
    grid-template-columns: 1fr;
    gap: var(--spacing-md);
    margin-bottom: var(--spacing-md);
}

.form-col-2 {
    grid-template-columns: 1fr 1fr;
}

.form-col-3 {
    grid-template-columns: 1fr 1fr 1fr;
}

.cep-input-wrapper {
    position: relative;
}

.cep-loading-text {
    position: absolute;
    right: var(--spacing-sm);
    top: 50%;
    transform: translateY(-50%);
    font-size: 0.875rem;
    color: var(--color-text-muted, #666);
}

.city-autocomplete-wrapper {
    position: relative;
}

.city-search-input:disabled {
    background: var(--color-bg-secondary, #f5f5f5);
    cursor: not-allowed;
}

.city-dropdown {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid var(--color-border, #e0e0e0);
    border-radius: var(--radius-sm, 4px);
    max-height: 200px;
    overflow-y: auto;
    z-index: 100;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
}

.city-dropdown-item {
    padding: var(--spacing-sm) var(--spacing-md);
    cursor: pointer;
    border-bottom: 1px solid var(--color-border, #f0f0f0);
}

.city-dropdown-item:hover {
    background: var(--color-bg-secondary, #f5f5f5);
}

.city-dropdown-item:last-child {
    border-bottom: none;
}
</style>

<script>
// CEP Autocomplete
document.getElementById('cep')?.addEventListener('blur', function() {
    const cep = this.value.replace(/\D/g, '');
    if (cep.length === 8) {
        document.getElementById('cep-loading-text').style.display = 'inline';
        
        fetch('<?= base_path("api/geo/cep") ?>?cep=' + cep)
            .then(response => response.json())
            .then(data => {
                document.getElementById('cep-loading-text').style.display = 'none';
                
                if (data.success && !data.erro) {
                    document.getElementById('address_street').value = data.logradouro || '';
                    document.getElementById('address_neighborhood').value = data.bairro || '';
                    document.getElementById('address_complement').value = data.complemento || '';
                    
                    // Selecionar UF
                    const stateSelect = document.getElementById('address_state_id');
                    if (stateSelect && data.uf) {
                        Array.from(stateSelect.options).forEach(option => {
                            if (option.text.startsWith(data.uf + ' -')) {
                                option.selected = true;
                                stateSelect.dispatchEvent(new Event('change'));
                            }
                        });
                    }
                }
            })
            .catch(error => {
                document.getElementById('cep-loading-text').style.display = 'none';
                console.error('Erro ao buscar CEP:', error);
            });
    }
});

// Cidade Autocomplete
const stateSelect = document.getElementById('address_state_id');
const citySearch = document.getElementById('address_city_search');
const cityIdInput = document.getElementById('address_city_id');
const cityDropdown = document.getElementById('address_city_dropdown');

let citySearchTimeout;

// Buscar UF do estado selecionado para usar na API
function getStateUf(stateId) {
    const option = stateSelect?.options[stateSelect.selectedIndex];
    if (option && option.text) {
        const match = option.text.match(/^([A-Z]{2}) -/);
        return match ? match[1] : null;
    }
    return null;
}

stateSelect?.addEventListener('change', function() {
    if (this.value) {
        citySearch.disabled = false;
        citySearch.placeholder = 'Digite para buscar cidade...';
    } else {
        citySearch.disabled = true;
        citySearch.value = '';
        cityIdInput.value = '';
    }
});

citySearch?.addEventListener('input', function() {
    const stateId = stateSelect?.value;
    const uf = getStateUf(stateId);
    const query = this.value.trim();
    
    if (!uf || query.length < 2) {
        cityDropdown.style.display = 'none';
        return;
    }
    
    clearTimeout(citySearchTimeout);
    citySearchTimeout = setTimeout(() => {
        fetch(`<?= base_path("api/geo/cidades") ?>?uf=${uf}&q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(cities => {
                cityDropdown.innerHTML = '';
                
                if (cities.length === 0) {
                    cityDropdown.style.display = 'none';
                    return;
                }
                
                cities.forEach(city => {
                    const item = document.createElement('div');
                    item.className = 'city-dropdown-item';
                    item.textContent = city.name;
                    item.onclick = () => {
                        citySearch.value = city.name;
                        cityIdInput.value = city.id;
                        cityDropdown.style.display = 'none';
                    };
                    cityDropdown.appendChild(item);
                });
                
                cityDropdown.style.display = 'block';
            })
            .catch(error => {
                console.error('Erro ao buscar cidades:', error);
            });
    }, 300);
});

// Fechar dropdown ao clicar fora
document.addEventListener('click', function(e) {
    if (citySearch && cityDropdown && !citySearch.contains(e.target) && !cityDropdown.contains(e.target)) {
        cityDropdown.style.display = 'none';
    }
});

// Toggle disponibilidade do dia
function toggleDayAvailability(dayNum, isAvailable) {
    document.getElementById('availability_start_' + dayNum).disabled = !isAvailable;
    document.getElementById('availability_end_' + dayNum).disabled = !isAvailable;
}

// Inicializar estado dos campos de disponibilidade
<?php foreach ($daysOfWeek as $dayNum => $dayInfo): 
    $av = $availability[$dayNum] ?? null;
    $isAvailable = $av ? $av['is_available'] : false;
?>
toggleDayAvailability(<?= $dayNum ?>, <?= $isAvailable ? 'true' : 'false' ?>);
<?php endforeach; ?>

// Habilitar campo cidade se já tiver estado selecionado
<?php if ($isEdit && !empty($instructor['address_state_id'])): ?>
if (citySearch) {
    citySearch.disabled = false;
}
<?php endif; ?>

// Inicializar após carregamento
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($isEdit && !empty($instructor['address_state_id']) && $currentCity): ?>
    if (citySearch && cityIdInput) {
        citySearch.value = '<?= htmlspecialchars($currentCity['name']) ?>';
        cityIdInput.value = '<?= $currentCity['id'] ?>';
    }
    <?php endif; ?>
});
</script>
