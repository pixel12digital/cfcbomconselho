# Validação: Campos de Cidade (UI de Edição)

## Checklist de Aceite ✅

### 1. Abrir aluno já salvo
- [x] UF aparece selecionada no `<select>`
- [x] Cidade aparece preenchida com o nome no input de busca
- [x] Hidden `city_id` já preenchido com o ID correto
- [x] Campo de cidade habilitado (não desabilitado)

### 2. Ao mudar UF
- [x] Limpa cidade (input de busca)
- [x] Limpa hidden `city_id`
- [x] Desabilita campo de cidade se UF for vazia
- [x] Habilita campo de cidade se UF for selecionada

### 3. Cidade de Nascimento
- [x] Mesmo comportamento do campo de endereço
- [x] Carrega corretamente ao editar
- [x] Limpa ao mudar UF de nascimento

## Implementação Atual

### Backend (Controller)
```php
// AlunosController::editar()
$currentCity = null;
if (!empty($student['city_id'])) {
    $cityModel = new City();
    $currentCity = $cityModel->findById($student['city_id']);
}

$currentBirthCity = null;
if (!empty($student['birth_city_id'])) {
    $cityModel = new City();
    $currentBirthCity = $cityModel->findById($student['birth_city_id']);
}
```

### Frontend (JavaScript)
```javascript
// Componente initCityAutocomplete() garante:
// 1. Inicialização com valores existentes
if (currentCityName && currentStateUf) {
    citySearchInput.value = currentCityName;
    cityIdInput.value = currentCityId || '';
}

// 2. Habilitar campo se UF já estiver selecionada
if (currentStateUf && stateUfSelect.value === currentStateUf) {
    citySearchInput.disabled = false;
    // Garantir que cidade está preenchida
    if (currentCityName && currentCityId) {
        citySearchInput.value = currentCityName;
        cityIdInput.value = currentCityId;
    }
}
```

## Status: ✅ IMPLEMENTADO E VALIDADO

Todos os critérios de aceite foram implementados e testados.
