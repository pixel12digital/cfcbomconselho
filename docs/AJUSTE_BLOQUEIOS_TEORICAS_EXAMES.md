# Ajuste: Bloqueios para Aulas Teóricas - Alinhamento com Lógica de Exames

## Problema Identificado

O bloco "Bloqueios para Aulas Teóricas" estava usando uma lógica diferente da lógica centralizada de exames implementada no bloco "Exames Pendentes", causando inconsistências:

- **Bloco "Exames Pendentes":** Usa `renderizarBadgesExame()` e verifica `tem_resultado` corretamente
- **Bloco "Bloqueios":** Usava `GuardsExames::verificarExamesOK()` que só verifica `status = 'concluido'` e `resultado = 'apto'`, sem considerar valores antigos ('aprovado') nem a lógica de `tem_resultado`

**Resultado:** Mesmo com exames concluídos e aptos, o bloco de bloqueios ainda mostrava "Exames médico e psicotécnico não concluídos".

## Solução Implementada

### Arquivo: `admin/pages/historico-aluno.php`

**Localização:** Linha ~1361-1395

### Alterações Realizadas

#### 1. Reutilização de Variáveis Centralizadas

O bloco de bloqueios agora reutiliza as mesmas variáveis já calculadas para o bloco "Exames Pendentes":

- **`$examesOK`** (linha ~254-267): Indica se ambos os exames estão concluídos e aptos
- **`$temPendencias`** (linha ~1326): Indica se há exames pendentes (calculado no bloco "Exames Pendentes")

#### 2. Filtragem de Motivos de Bloqueio

**Antes:**
```php
<?php foreach ($bloqueioTeorica['motivos_bloqueio'] as $motivo): ?>
    <li><?php echo htmlspecialchars($motivo); ?></li>
<?php endforeach; ?>
```

**Agora:**
```php
// Filtrar motivos de bloqueio: remover motivo de exames se exames estiverem OK
$motivosBloqueioFiltrados = [];
foreach ($bloqueioTeorica['motivos_bloqueio'] as $motivo) {
    if (stripos($motivo, 'Exames médico e psicotécnico') !== false) {
        // Só adicionar se realmente houver pendência de exames
        if (!$examesOK && $temPendencias) {
            $motivosBloqueioFiltrados[] = $motivo;
        }
    } else {
        // Outros motivos (financeiro, documentação, etc.) são mantidos intocados
        $motivosBloqueioFiltrados[] = $motivo;
    }
}
```

#### 3. Condição de Exibição do Bloco

**Antes:**
```php
<?php if (!$bloqueioTeorica['pode_prosseguir']): ?>
```

**Agora:**
```php
<?php if ($mostrarBlocoBloqueios): ?>
// Onde $mostrarBlocoBloqueios = !empty($motivosBloqueioFiltrados)
```

Isso garante que o bloco só aparece se houver motivos válidos após a filtragem.

#### 4. Logs de Debug

Adicionados logs para rastrear a decisão de adicionar ou não o motivo de exames:

```php
error_log("[BLOQUEIOS TEORICAS] Aluno {$alunoId} - examesOK=" . ($examesOK ? 'true' : 'false') . ", temPendencias=" . ($temPendencias ? 'true' : 'false') . " - motivo_exames_adicionado=" . ($adicionarMotivoExames ? 'true' : 'false'));
```

## Lógica de Decisão

### Critério para Adicionar Motivo "Exames médico e psicotécnico não concluídos"

O motivo só é adicionado se:
- `$examesOK === false` **E**
- `$temPendencias === true`

Isso significa:
- Exames não estão OK (não concluídos ou não aptos) **E**
- Há pendências reais de exames (calculadas pela mesma lógica do bloco "Exames Pendentes")

### Garantias

✅ **Consistência:** Usa a mesma lógica do bloco "Exames Pendentes"
✅ **Não duplica regras:** Reutiliza variáveis já calculadas
✅ **Preserva outros motivos:** Financeiro, documentação, etc. não são afetados
✅ **Logs detalhados:** Facilita debug e validação

## Testes Esperados

### ✅ Cenário 1: Sem Exames

**Estado:**
- Nenhum exame agendado
- `$examesOK = false`
- `$temPendencias = true`

**Resultado Esperado:**
- Bloco "Exames Pendentes": Aparece com "Falta agendar exame médico" e "Falta agendar exame psicotécnico"
- Bloco "Bloqueios": Aparece com motivo "Exames médico e psicotécnico não concluídos"

### ✅ Cenário 2: Médico e Psicotécnico Concluídos Aptos

**Estado:**
- Exame médico: concluído, apto
- Exame psicotécnico: concluído, apto
- `$examesOK = true`
- `$temPendencias = false`

**Resultado Esperado:**
- Bloco "Exames Pendentes": Aparece verde "Exames OK"
- Bloco "Bloqueios": **NÃO** inclui motivo "Exames médico e psicotécnico não concluídos"
  - Se houver outros motivos (financeiro, etc.), bloco aparece apenas com esses motivos
  - Se não houver outros motivos, bloco não aparece

### ✅ Cenário 3: Um dos Exames Sem Resultado

**Estado:**
- Exame médico: agendado, sem resultado
- Exame psicotécnico: concluído, apto
- `$examesOK = false`
- `$temPendencias = true`

**Resultado Esperado:**
- Bloco "Exames Pendentes": Aparece com "Falta lançar resultado do exame médico"
- Bloco "Bloqueios": Aparece com motivo "Exames médico e psicotécnico não concluídos"

## Localização das Alterações

### Arquivo: `admin/pages/historico-aluno.php`

**Linha ~1361-1395:** Bloco "Bloqueios para Aulas Teóricas"
- Filtragem de motivos usando `$examesOK` e `$temPendencias`
- Logs de debug
- Condição de exibição ajustada

**Linha ~1326:** Variável `$temPendencias` (comentário adicionado indicando reutilização)

## Compatibilidade

- ✅ Mantém compatibilidade com valores antigos ('aprovado'/'reprovado')
- ✅ Não altera lógica de outros motivos de bloqueio
- ✅ Não altera layout ou estrutura visual
- ✅ Usa mesma "fonte da verdade" do bloco "Exames Pendentes"

