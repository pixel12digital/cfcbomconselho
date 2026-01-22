# Investigação: Fluxo de Agendamento de Exames

## 1. Rotas e Páginas Usadas

### Rotas do Menu

**Links do menu lateral:**
- `index.php?page=exames&tipo=medico`
- `index.php?page=exames&tipo=psicotecnico`
- `index.php?page=exames&tipo=teorico`
- `index.php?page=exames&tipo=pratico`

**Arquivo incluído:**
- `admin/pages/exames.php` (linha ~2651 do `admin/index.php`)

**Roteamento:**
```php
// admin/index.php (linha ~2651)
$content_file = "pages/{$page}.php";
// Quando $page = 'exames', inclui: pages/exames.php
```

**JavaScript específico:**
- ❌ **NÃO existe** arquivo separado como `assets/js/admin-exames.js`
- ✅ Todo o JavaScript está **inline** no próprio `admin/pages/exames.php` (a partir da linha ~2022)

---

## 2. Botões "Agendar Exame" no Histórico do Aluno

### Localização
**Arquivo:** `admin/pages/historico-aluno.php`

### Botão "Agendar Exame" - Exame Médico

**Linha:** ~1090-1093

```php
<?php if ($isAdmin || $isSecretaria): ?>
    <a href="index.php?page=exames&tipo=medico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
       class="btn btn-sm btn-primary mt-2">
        <i class="fas fa-plus me-1"></i>Agendar Exame
    </a>
<?php endif; ?>
```

**URL gerada (exemplo para aluno 167):**
```
index.php?page=exames&tipo=medico&aluno_id=167&origem=historico
```

### Botão "Agendar Exame" - Exame Psicotécnico

**Linha:** ~1176-1179

```php
<?php if ($isAdmin || $isSecretaria): ?>
    <a href="index.php?page=exames&tipo=psicotecnico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
       class="btn btn-sm btn-primary mt-2">
        <i class="fas fa-plus me-1"></i>Agendar Exame
    </a>
<?php endif; ?>
```

**URL gerada (exemplo para aluno 167):**
```
index.php?page=exames&tipo=psicotecnico&aluno_id=167&origem=historico
```

### Botão "Agendar Exame" via Menu

**Arquivo:** `admin/index.php`

**Linhas:** ~1604-1607 (desktop) e ~1917-1920 (mobile)

```php
<a href="index.php?page=exames&tipo=medico" class="nav-sublink ...">
    <i class="fas fa-stethoscope"></i>
    <span>Exame Médico</span>
</a>

<a href="index.php?page=exames&tipo=psicotecnico" class="nav-sublink ...">
    <i class="fas fa-brain"></i>
    <span>Exame Psicotécnico</span>
</a>
```

**URL gerada:**
```
index.php?page=exames&tipo=medico
index.php?page=exames&tipo=psicotecnico
```

**Diferença:** Via menu, **NÃO** inclui `aluno_id` nem `origem=historico`.

---

## 3. Diferença entre "Modal Antigo" e "Tela Nova"

### ❌ Modal Antigo (REMOVIDO)

**Status:** ✅ **Já foi removido** do código

**Localização anterior:** `admin/pages/historico-aluno.php`

**Comentário no código (linha ~2121):**
```php
<!-- NOTA: Modais antigos de agendamento (#modalAgendamento) e resultado (#modalResultado) foram removidos.
     O agendamento de exames agora é feito através do módulo dedicado (page=exames).
     Os botões "Agendar Exame" no histórico redirecionam para o novo módulo.
     O lançamento de resultados também deve ser feito no módulo de exames. -->
```

**Funções JavaScript antigas (também removidas):**
- `abrirModalAgendamento(tipo)` - REMOVIDA
- `agendarExame()` (do histórico) - REMOVIDA
- `lancarResultado()` (do histórico) - REMOVIDA

**Mantida apenas:**
- `cancelarExame(exameId)` - Ainda usada para cancelar exames diretamente do histórico

### ✅ Tela Nova (Modal dentro da página de exames)

**Arquivo:** `admin/pages/exames.php`

**Modal de Agendamento:**
- **ID:** `#modalAgendarExame` (linha ~1754)
- **Formulário:** `#formAgendarExame` (linha ~1764)
- **Campos:**
  - `<select name="aluno_id" id="aluno_id">` - Aluno (pré-selecionado se `aluno_id` na URL)
  - `<input type="hidden" name="tipo" id="tipo_exame">` - Tipo (vem da URL `?tipo=medico`)
  - `<input type="text" disabled value="Exame Médico">` - Tipo (apenas visual, travado)
  - `<input type="date" name="data_agendada">` - Data do Exame
  - `<input type="time" name="hora_agendada">` - Horário
  - `<input type="text" name="clinica_nome">` - Clínica / Local
  - `<input type="text" name="protocolo">` - Protocolo
  - `<textarea name="observacoes">` - Observações

**Botão que abre o modal:**
- **Linha:** ~1460
- **HTML:**
```php
<button type="button" class="btn btn-primary btn-lg" onclick="abrirModalAgendar()">
    <i class="fas fa-plus me-2"></i>Agendar Exame
</button>
```

**Função JavaScript que abre o modal:**
- **Linha:** ~2022
- **Função:** `abrirModalAgendar()`
- **Comportamento:**
  - Abre o modal `#modalAgendarExame`
  - Define o tipo do exame baseado na URL (`?tipo=medico`)
  - Limpa o formulário
  - Pré-preenche data de hoje se vazio

**Abertura automática quando vem do histórico:**
- **Linha:** ~2129-2145
- **Código:**
```javascript
document.addEventListener('DOMContentLoaded', function() {
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

---

## 4. Lógica de Salvar e Comportamento Após Submit

### Fluxo de Submit

**Arquivo:** `admin/pages/exames.php`

**Função JavaScript:** `agendarExame()` (linha ~2277)

**Fluxo completo:**

1. **Validação Front-end:**
   ```javascript
   // Linha ~2282-2310
   - Verifica se aluno_id foi selecionado
   - Verifica se tipo está presente (pega da URL se não estiver no form)
   - Verifica se data_agendada foi preenchida
   - Valida se data não é retroativa
   - Valida se hora não é retroativa (se data for hoje)
   ```

2. **Envio via AJAX:**
   ```javascript
   // Linha ~2350
   fetch('api/exames_simple.php?t=' + Date.now(), {
       method: 'POST',
       body: formData,
       cache: 'no-cache'
   })
   ```

3. **API que processa:**
   - **Arquivo:** `admin/api/exames_simple.php`
   - **Método:** `POST` com `action=create` (padrão)
   - **Linha:** ~74-100
   - **Validações:**
     - Tipo de exame válido (`medico`, `psicotecnico`, `teorico`, `pratico`)
     - Aluno existe
     - Data não é retroativa
     - Campos obrigatórios preenchidos
   - **Insert no banco:**
     ```php
     // Linha ~100-150 (aproximadamente)
     $db->insert('exames', [
         'aluno_id' => $alunoId,
         'tipo' => $tipo,
         'data_agendada' => $dataAgendada,
         'hora_agendada' => $horaAgendada,
         'clinica_nome' => $clinicaNome,
         'protocolo' => $protocolo,
         'observacoes' => $observacoes,
         'status' => 'agendado',
         'criado_em' => date('Y-m-d H:i:s')
     ]);
     ```

4. **Resposta da API:**
   ```json
   {
       "success": true,
       "message": "Exame agendado com sucesso!"
   }
   ```
   ou
   ```json
   {
       "success": false,
       "error": "Mensagem de erro",
       "codigo": "CODIGO_ERRO"
   }
   ```

5. **Comportamento após salvar (SUCESSO):**
   ```javascript
   // Linha ~2377-2382
   .then(data => {
       if (data.success === true) {
           const mensagem = data.message || 'Exame agendado com sucesso!';
           alert('✅ ' + mensagem);
           location.reload();  // ⚠️ RECARREGA A PÁGINA ATUAL
       }
   })
   ```

### ⚠️ PROBLEMA IDENTIFICADO: Comportamento Após Salvar

**Código atual (linha ~2382):**
```javascript
location.reload();
```

**O que acontece hoje:**

#### Cenário 1: Acesso via Menu (sem `aluno_id` / sem `origem`)
1. Usuário acessa: `index.php?page=exames&tipo=medico`
2. Clica em "Agendar Exame" (abre modal)
3. Preenche formulário e salva
4. **Após salvar:** `location.reload()` recarrega `index.php?page=exames&tipo=medico`
5. **Resultado:** ✅ Permanece na página de exames (comportamento correto)

#### Cenário 2: Acesso via Histórico (com `aluno_id` / `origem=historico`)
1. Usuário acessa: `index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`
2. Modal abre automaticamente (devido ao código em `DOMContentLoaded`)
3. Aluno 167 já está pré-selecionado
4. Usuário preenche e salva
5. **Após salvar:** `location.reload()` recarrega `index.php?page=exames&tipo=medico&aluno_id=167&origem=historico`
6. **Resultado:** ❌ **Permanece na página de exames** (deveria voltar para o histórico)

**Problema:** Não há diferenciação entre os dois cenários. Ambos usam `location.reload()`, mantendo o usuário na mesma página.

---

## 5. Resumo Final

### Arquivo da View Principal de Exames

**Caminho:** `admin/pages/exames.php`

**Trechos relevantes:**
- **Linha ~1754:** Modal `#modalAgendarExame` (formulário de agendamento)
- **Linha ~1764:** Formulário `#formAgendarExame`
- **Linha ~1773:** Select de aluno (com pré-seleção se `aluno_id` na URL)
- **Linha ~1795:** Campo hidden com tipo (`name="tipo"`)
- **Linha ~1460:** Botão "Agendar Exame" que abre o modal
- **Linha ~2022:** Função `abrirModalAgendar()` que abre o modal
- **Linha ~2129:** Código que abre modal automaticamente quando `origem=historico`
- **Linha ~2277:** Função `agendarExame()` que processa o submit
- **Linha ~2382:** `location.reload()` após salvar (PROBLEMA)

### Arquivo(s) que Ainda Possuem Modal Antigo

**Status:** ✅ **NENHUM** - Modais antigos já foram removidos

**Comentário no código:**
- `admin/pages/historico-aluno.php` (linha ~2121): Comentário explicando que modais foram removidos

### Como Cada Botão "Agendar Exame" Está Configurado

#### No Histórico do Aluno

**Exame Médico:**
```php
<a href="index.php?page=exames&tipo=medico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
   class="btn btn-sm btn-primary mt-2">
    <i class="fas fa-plus me-1"></i>Agendar Exame
</a>
```

**Exame Psicotécnico:**
```php
<a href="index.php?page=exames&tipo=psicotecnico&aluno_id=<?php echo (int)$alunoId; ?>&origem=historico" 
   class="btn btn-sm btn-primary mt-2">
    <i class="fas fa-plus me-1"></i>Agendar Exame
</a>
```

#### Via Menu

**Exame Médico:**
```php
<a href="index.php?page=exames&tipo=medico" class="nav-sublink ...">
    <i class="fas fa-stethoscope"></i>
    <span>Exame Médico</span>
</a>
```

**Exame Psicotécnico:**
```php
<a href="index.php?page=exames&tipo=psicotecnico" class="nav-sublink ...">
    <i class="fas fa-brain"></i>
    <span>Exame Psicotécnico</span>
</a>
```

### Arquivo/Lógica que Processa o Submit

**Arquivo:** `admin/pages/exames.php`

**Função:** `agendarExame()` (linha ~2277)

**Fluxo atual:**

```javascript
function agendarExame() {
    // 1. Validações front-end
    // 2. Monta FormData
    // 3. Envia via fetch para api/exames_simple.php
    // 4. Em caso de sucesso:
    if (data.success === true) {
        alert('✅ ' + mensagem);
        location.reload();  // ⚠️ PROBLEMA: Sempre recarrega a página atual
    }
}
```

**API:** `admin/api/exames_simple.php`
- **Método:** `POST` com `action=create`
- **Validações:** Tipo, aluno, data, campos obrigatórios
- **Insert:** Tabela `exames`
- **Resposta:** JSON com `success: true/false`

**Comportamento após salvar:**
- ✅ **Via menu:** Permanece na página de exames (correto)
- ❌ **Via histórico:** Permanece na página de exames (deveria voltar para histórico)

---

## 6. Recomendações para Correção

### Alteração Necessária

**Arquivo:** `admin/pages/exames.php`

**Função:** `agendarExame()` (linha ~2377-2382)

**Código atual:**
```javascript
.then(data => {
    if (data.success === true) {
        const mensagem = data.message || 'Exame agendado com sucesso!';
        alert('✅ ' + mensagem);
        location.reload();  // ⚠️ Sempre recarrega
    }
})
```

**Código sugerido:**
```javascript
.then(data => {
    if (data.success === true) {
        const mensagem = data.message || 'Exame agendado com sucesso!';
        alert('✅ ' + mensagem);
        
        // Verificar se veio do histórico
        const urlParams = new URLSearchParams(window.location.search);
        const origem = urlParams.get('origem');
        const alunoId = urlParams.get('aluno_id');
        
        if (origem === 'historico' && alunoId) {
            // Voltar para o histórico do aluno
            window.location.href = `index.php?page=historico-aluno&id=${alunoId}`;
        } else {
            // Permanecer na página de exames
            location.reload();
        }
    }
})
```

**Testes esperados:**
1. ✅ Via menu: Salvar → Permanece em `page=exames&tipo=medico`
2. ✅ Via histórico: Salvar → Redireciona para `page=historico-aluno&id=167`

