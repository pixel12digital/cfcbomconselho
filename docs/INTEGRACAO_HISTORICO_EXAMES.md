# Integração do Histórico do Aluno com Módulo de Exames

## Objetivo

Substituir os modais antigos de agendamento de exames no histórico do aluno pelo novo módulo dedicado de exames (`admin/index.php?page=exames`).

## Alterações Realizadas

### 1. Substituição dos Botões "Agendar Exame" no Histórico

**Arquivo:** `admin/pages/historico-aluno.php`

**Antes:**
```php
<button class="btn btn-sm btn-primary mt-2" onclick="abrirModalAgendamento('medico')">
    <i class="fas fa-plus me-1"></i>Agendar Exame
</button>
```

**Depois:**
```php
<a href="index.php?page=exames&tipo=medico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
   class="btn btn-sm btn-primary mt-2">
    <i class="fas fa-plus me-1"></i>Agendar Exame
</a>
```

**Aplicado para:**
- ✅ Botão "Agendar Exame" do Exame Médico (linha ~1089)
- ✅ Botão "Agendar Exame" do Exame Psicotécnico (linha ~1173)

### 2. Substituição dos Botões "Lançar Resultado" no Histórico

**Arquivo:** `admin/pages/historico-aluno.php`

**Antes:**
```php
<button class="btn btn-sm btn-outline-primary" onclick="abrirModalResultado(<?php echo $exameMedico['id']; ?>, 'medico')">
    <i class="fas fa-edit me-1"></i>Lançar Resultado
</button>
```

**Depois:**
```php
<a href="index.php?page=exames&tipo=medico&exame_id=<?php echo (int)$exameMedico['id']; ?>&origem=historico" 
   class="btn btn-sm btn-outline-primary">
    <i class="fas fa-edit me-1"></i>Lançar Resultado
</a>
```

**Aplicado para:**
- ✅ Botão "Lançar Resultado" do Exame Médico (linha ~1076)
- ✅ Botão "Lançar Resultado" do Exame Psicotécnico (linha ~1160)

### 3. Remoção dos Modais Antigos

**Arquivo:** `admin/pages/historico-aluno.php`

**Removidos:**
- ❌ Modal `#modalAgendamento` (linhas ~2232-2276)
- ❌ Modal `#modalResultado` (linhas ~2278-2323)

**Substituídos por comentários explicativos:**
```php
<!-- NOTA: Modais antigos de agendamento (#modalAgendamento) e resultado (#modalResultado) foram removidos.
     O agendamento de exames agora é feito através do módulo dedicado (page=exames).
     Os botões "Agendar Exame" no histórico redirecionam para o novo módulo.
     O lançamento de resultados também deve ser feito no módulo de exames. -->
```

### 4. Remoção das Funções JavaScript Antigas

**Arquivo:** `admin/pages/historico-aluno.php`

**Removidas:**
- ❌ `function abrirModalAgendamento(tipo)` (linhas ~2090-2131)
- ❌ `function abrirModalResultado(exameId, tipo)` (linhas ~2133-2139)
- ❌ `function agendarExame()` (linhas ~2141-2173)
- ❌ `function lancarResultado()` (linhas ~2175-2207)

**Mantida:**
- ✅ `function cancelarExame(exameId)` - Ainda é usada para cancelar exames diretamente do histórico

**Substituídas por comentário explicativo:**
```javascript
// NOTA: Funções abrirModalAgendamento, agendarExame e lancarResultado foram removidas.
// O agendamento de exames agora é feito através do módulo dedicado (page=exames).
// Os botões "Agendar Exame" no histórico redirecionam para o novo módulo.
```

### 5. Pré-seleção do Aluno no Módulo de Exames

**Arquivo:** `admin/pages/exames.php` (linhas ~1768-1780)

**Alteração:**
```php
<?php 
// Pré-selecionar aluno se vier do histórico
$alunoIdPreSelecionado = isset($_GET['aluno_id']) ? (int)$_GET['aluno_id'] : null;
?>
<select class="form-select" name="aluno_id" id="aluno_id" required>
    <option value="">Selecione um aluno</option>
    <?php foreach ($alunos as $aluno): ?>
        <option value="<?php echo $aluno['id']; ?>"
            <?php echo $alunoIdPreSelecionado === (int)$aluno['id'] ? 'selected' : ''; ?>>
            <?php echo htmlspecialchars($aluno['nome'] . ' - ' . $aluno['cpf']); ?>
        </option>
    <?php endforeach; ?>
</select>
```

**Comportamento:**
- Quando `aluno_id` está presente na URL, o aluno correspondente é pré-selecionado no `<select>`
- Facilita o fluxo quando o usuário vem do histórico do aluno

### 6. Abertura Automática do Modal de Agendamento

**Arquivo:** `admin/pages/exames.php` (linhas ~2128-2145)

**Código adicionado:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
    // Verificar se veio do histórico e abrir modal automaticamente
    const urlParams = new URLSearchParams(window.location.search);
    const origem = urlParams.get('origem');
    const alunoId = urlParams.get('aluno_id');
    const exameId = urlParams.get('exame_id');
    
    if (origem === 'historico') {
        if (alunoId) {
            // Abrir modal de agendamento
            setTimeout(function() {
                const btnAgendar = document.querySelector('button[onclick="abrirModalAgendar()"]');
                if (btnAgendar) {
                    btnAgendar.click();
                }
            }, 300);
        } else if (exameId) {
            // Abrir modal de resultado
            setTimeout(function() {
                abrirModalResultado(exameId);
            }, 300);
        }
    }
    // ... resto do código
});
```

**Comportamento:**
- Quando `origem=historico` e `aluno_id` estão presentes, o modal de agendamento abre automaticamente após 300ms
- Quando `origem=historico` e `exame_id` está presente, o modal de resultado abre automaticamente após 300ms
- O delay de 300ms garante que o DOM está completamente carregado

## Fluxo Completo

### Cenário 1: Agendar Exame Médico a partir do Histórico

1. **Usuário no histórico:** `admin/index.php?page=historico-aluno&id=167`
2. **Clica em "Agendar Exame"** no card "Exame Médico"
3. **Redirecionamento:** `admin/index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`
4. **Comportamento:**
   - Aluno 167 é pré-selecionado no campo "Aluno"
   - Tipo de exame está travado como "Exame Médico"
   - Modal "Agendar Novo Exame" abre automaticamente
   - Usuário preenche data, horário, clínica, protocolo, observações
   - Clica em "Agendar Exame"
   - Exame é salvo e usuário retorna à listagem

### Cenário 2: Lançar Resultado a partir do Histórico

1. **Usuário no histórico:** `admin/index.php?page=historico-aluno&id=167`
2. **Clica em "Lançar Resultado"** no card "Exame Médico" (quando há exame agendado)
3. **Redirecionamento:** `admin/index.php?page=exames&tipo=medico&exame_id=123&origem=historico`
4. **Comportamento:**
   - Modal "Lançar Resultado do Exame" abre automaticamente
   - ID do exame (123) é preenchido automaticamente
   - Usuário seleciona resultado (Apto/Inapto/Pendente), data e observações
   - Clica em "Salvar Resultado"
   - Resultado é salvo e usuário retorna à listagem

## Arquivos Modificados

1. **`admin/pages/historico-aluno.php`**
   - Substituição de botões "Agendar Exame" por links
   - Substituição de botões "Lançar Resultado" por links
   - Remoção de modais antigos (`#modalAgendamento`, `#modalResultado`)
   - Remoção de funções JavaScript antigas (`abrirModalAgendamento`, `agendarExame`, `lancarResultado`)
   - Manutenção de `cancelarExame()` (ainda usada)

2. **`admin/pages/exames.php`**
   - Pré-seleção do aluno quando `aluno_id` está na URL
   - Abertura automática do modal de agendamento quando `origem=historico` e `aluno_id` presente
   - Abertura automática do modal de resultado quando `origem=historico` e `exame_id` presente

## Validação

### Testes Manuais

1. ✅ **Abrir histórico do aluno 167:** `admin/index.php?page=historico-aluno&id=167`
2. ✅ **Clicar em "Agendar Exame" (Médico):**
   - Deve redirecionar para `page=exames&tipo=medico&aluno_id=167&origem=historico`
   - Aluno 167 deve estar pré-selecionado
   - Modal deve abrir automaticamente
3. ✅ **Clicar em "Agendar Exame" (Psicotécnico):**
   - Deve redirecionar para `page=exames&tipo=psicotecnico&aluno_id=167&origem=historico`
   - Aluno 167 deve estar pré-selecionado
   - Modal deve abrir automaticamente
4. ✅ **Clicar em "Lançar Resultado" (quando há exame agendado):**
   - Deve redirecionar para `page=exames&tipo=medico&exame_id={id}&origem=historico`
   - Modal de resultado deve abrir automaticamente
5. ✅ **Verificar console do navegador:**
   - Não deve haver erros relacionados a `modalAgendamento` ou `modalResultado`
   - Não deve haver erros de funções não definidas (`abrirModalAgendamento`, etc.)

## Benefícios

1. ✅ **Código mais limpo:** Remoção de modais e funções duplicadas
2. ✅ **Experiência unificada:** Todo agendamento de exames passa pelo mesmo módulo
3. ✅ **Manutenção facilitada:** Lógica centralizada no módulo de exames
4. ✅ **Fluxo intuitivo:** Redirecionamento direto com pré-seleção e abertura automática do modal
5. ✅ **Consistência:** Mesma interface para agendamento, independente da origem

## Observações

- A função `cancelarExame()` foi mantida no histórico, pois ainda é usada para cancelar exames diretamente do histórico sem precisar ir ao módulo de exames
- Os botões "Lançar Resultado" também foram convertidos para links, redirecionando para o módulo de exames com o `exame_id` na URL
- O parâmetro `origem=historico` permite identificar a origem e ajustar comportamentos futuros se necessário

