# Ajuste do "Progresso Detalhado por Categoria" no Histórico do Aluno

## Problema Identificado

Para alunos com categoria combinada (ex: AB - A + B), o bloco "Progresso Detalhado por Categoria" estava mostrando a carga teórica duplicada em cada categoria:

**ANTES:**
- Categoria A: Motocicletas
  - Aulas Teóricas → Necessário: 45h teóricas
  - Aulas Práticas (A) → Necessário: 20 aulas
- Categoria B: Automóveis
  - Aulas Teóricas → Necessário: 45h teóricas
  - Aulas Práticas (B) → Necessário: 20 aulas

Isso dava a impressão de que o aluno precisava de **90h teóricas** (45h em A + 45h em B), quando na verdade a carga teórica é **única para o curso combinado** (45h no total).

## Regra de Negócio

- **Carga teórica:** É única para o curso combinado (ex: AB) → 45h teóricas no total
- **Carga prática:** É somada por categoria (ex: A = 20 aulas, B = 20 aulas) → Total: 40 aulas práticas
- **Na tela:** O resumo teórico deve ser mostrado em um bloco único, e o detalhamento por categoria mostra apenas as práticas

## Alterações Realizadas

### 1. Criação de Cálculo Prévio das Variáveis

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1271-1310)

**NOVO CÓDIGO ADICIONADO:**
```php
<?php
// Calcular totais gerais ANTES de renderizar o "Progresso Detalhado por Categoria"
// para que possam ser reutilizados no bloco de resumo teórico
// (Essas mesmas variáveis são recalculadas no bloco "Total Geral" abaixo, mas precisamos aqui também)
$totalTeoricasGeralDetalhado = 0;
$totalTeoricasConcluidasGeralDetalhado = 0;
$totalPraticasGeralDetalhado = 0;
$totalPraticasConcluidasGeralDetalhado = 0;

if ($ehCategoriaCombinada) {
    // REGRA: Usar função centralizada para garantir teoria única e práticas somadas
    $totalTeoricasGeralDetalhado = $cargaCategoria['total_horas_teoricas']; // Teoria única (não somada)
    $totalPraticasGeralDetalhado = $cargaCategoria['total_aulas_praticas']; // Práticas somadas (ex: 40)
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        $totalTeoricasConcluidasGeralDetalhado += $progressoDetalhado[$categoria]['teoricas']['concluidas'];
        
        foreach (['praticas_moto', 'praticas_carro', 'praticas_carga', 'praticas_passageiros', 'praticas_combinacao'] as $tipo) {
            $totalPraticasConcluidasGeralDetalhado += $progressoDetalhado[$categoria][$tipo]['concluidas'];
        }
    }
} else {
    // Categoria simples: usar valores da função centralizada ou configuração direta
    if ($configuracaoCategoria) {
        $totalTeoricasGeralDetalhado = $cargaCategoria['total_horas_teoricas'];
        $totalTeoricasConcluidasGeralDetalhado = $progressoDetalhado['teoricas']['concluidas'];
        $totalPraticasGeralDetalhado = $cargaCategoria['total_aulas_praticas'];
        $totalPraticasConcluidasGeralDetalhado = $aulasPraticasConcluidas;
    } else {
        // Fallback para valores padrão
        $totalTeoricasGeralDetalhado = 45;
        $totalTeoricasConcluidasGeralDetalhado = $progressoDetalhado['teoricas']['concluidas'];
        $totalPraticasGeralDetalhado = 25;
        $totalPraticasConcluidasGeralDetalhado = $aulasPraticasConcluidas;
    }
}

$percentualTeoricasGeralDetalhado = $totalTeoricasGeralDetalhado > 0 ? min(100, ($totalTeoricasConcluidasGeralDetalhado / $totalTeoricasGeralDetalhado) * 100) : 0;
?>
```

**Justificativa:** As variáveis `$totalTeoricasGeral`, `$totalTeoricasConcluidasGeral` e `$percentualTeoricasGeral` são calculadas no bloco "Total Geral" que vem DEPOIS do "Progresso Detalhado por Categoria". Para reutilizar essa lógica no novo bloco de resumo teórico, precisamos calcular essas variáveis antes.

### 2. Remoção do Bloco de Teóricas Dentro de Cada Categoria

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1282-1312)

**ANTES:**
```php
<div class="border rounded p-3 mb-3">
    <h6 class="text-primary mb-3">
        <i class="fas fa-certificate me-2"></i>
        Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
    </h6>
    
    <!-- Teóricas -->
    <?php if ($config['horas_teoricas'] > 0): ?>
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="fw-bold">
                <i class="fas fa-book text-info me-2"></i>
                Aulas Teóricas
            </span>
            <span class="badge bg-info">
                <?php echo $progressoDetalhado[$categoria]['teoricas']['concluidas']; ?>/<?php echo $progressoDetalhado[$categoria]['teoricas']['necessarias']; ?>
            </span>
        </div>
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-info" role="progressbar" 
                 style="width: <?php echo $progressoDetalhado[$categoria]['teoricas']['percentual']; ?>%">
            </div>
        </div>
        <small class="text-muted">
            Necessário: <?php echo $config['horas_teoricas']; ?>h teóricas
        </small>
    </div>
    <?php endif; ?>
    
    <!-- Práticas -->
    ...
</div>
```

**DEPOIS:**
```php
<div class="border rounded p-3 mb-3">
    <h6 class="text-primary mb-3">
        <i class="fas fa-certificate me-2"></i>
        Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
    </h6>
    
    <!-- Práticas -->
    ...
</div>
```

**Justificativa:** Removido o bloco de "Aulas Teóricas" dentro de cada categoria individual para evitar a impressão de duplicação. A teoria agora é mostrada apenas no bloco único de resumo teórico.

### 3. Criação do Bloco Único de Resumo Teórico

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1312-1338)

**NOVO CÓDIGO ADICIONADO:**
```php
<?php
// Regra de negócio:
// - Carga teórica é única para o curso combinado (ex.: AB) -> 45h teóricas no total.
// - Carga prática é por categoria (ex.: A = 20 aulas, B = 20 aulas).
// - Nesta tela, o resumo teórico é mostrado em um bloco único,
//   e o detalhamento por categoria mostra apenas as práticas.

// Reutilizar os valores já calculados corretamente no "Total Geral"
// Esses valores já garantem teoria única e práticas somadas
?>

<!-- Resumo Teórico do Curso (Categorias Combinadas) -->
<div class="border rounded p-3 mb-4 bg-light">
    <h6 class="text-info mb-3">
        <i class="fas fa-book me-2"></i>
        Resumo Teórico do Curso (Categorias combinadas)
    </h6>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="fw-bold">Progresso Geral Teórico</span>
        <span class="badge bg-info fs-6">
            <?php echo $totalTeoricasConcluidasGeralDetalhado; ?>/<?php echo $totalTeoricasGeralDetalhado; ?>
        </span>
    </div>
    <div class="progress" style="height: 10px;">
        <div class="progress-bar bg-info" role="progressbar" 
             style="width: <?php echo $percentualTeoricasGeralDetalhado; ?>%">
        </div>
    </div>
    <small class="text-muted">
        <strong>Total necessário: <?php echo $totalTeoricasGeralDetalhado; ?>h teóricas</strong>
        <br>
        <em>Carga teórica compartilhada para todas as categorias do curso combinado.</em>
    </small>
</div>
```

**Justificativa:** Criado um bloco único que mostra a carga teórica uma vez só para o curso combinado, reutilizando a mesma lógica do bloco "Total Geral" para garantir consistência.

### 4. Melhoria no Texto do Card de Práticas

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1351-1357)

**ANTES:**
```php
<small class="text-muted">
    Categoria <?php echo $categoria; ?>: <?php echo $config['horas_praticas_total']; ?> aulas práticas
    <?php if ($ehCategoriaCombinada): ?>
        <br><strong>Total combinado (todas as categorias): <?php echo $cargaCategoria['total_aulas_praticas']; ?> aulas práticas</strong>
    <?php endif; ?>
</small>
```

**DEPOIS:**
```php
<small class="text-muted">
    <strong>Categoria <?php echo $categoria; ?>: <?php echo $config['horas_praticas_total']; ?> aulas práticas</strong>
    <?php if ($ehCategoriaCombinada): ?>
        <br>Total combinado (todas as categorias): <?php echo $cargaCategoria['total_aulas_praticas']; ?> aulas práticas
    <?php endif; ?>
</small>
```

**Justificativa:** Ajuste visual para destacar a quantidade de práticas da categoria específica e manter o total combinado como informação complementar.

## Estrutura Visual Final

**DEPOIS das alterações, para categoria combinada (AB):**

```
[Card] Progresso Detalhado por Categoria

  [Sub-card] Resumo Teórico do Curso (Categorias combinadas)
    - Total necessário: 45h teóricas
    - Barra de progresso geral (teórico)
    - Texto: "Carga teórica compartilhada para todas as categorias do curso combinado."

  [Sub-card] Categoria A: Motocicletas
    - Aulas Práticas (A)
      - Necessário: 20 aulas práticas
      - Progresso específico
      - Total combinado: 40 aulas práticas

  [Sub-card] Categoria B: Automóveis
    - Aulas Práticas (B)
      - Necessário: 20 aulas práticas
      - Progresso específico
      - Total combinado: 40 aulas práticas
```

## Garantias de Não-Regressão

✅ **Não alterado:**
- O histórico completo de aulas continua sendo montado corretamente
- As barras de progresso geral no bloco "Total Geral – Todas as Categorias" continuam funcionando
- O total de 40 aulas práticas (20 A + 20 B) continua aparecendo corretamente no "Total Geral"
- A lógica de cálculo de progresso por categoria continua funcionando

✅ **Apenas ajustado:**
- Removida a duplicação visual da carga teórica em cada categoria
- Criado bloco único de resumo teórico que reutiliza a mesma lógica do "Total Geral"
- Mantido o foco em práticas dentro de cada categoria individual

## Arquivos Modificados

1. **`admin/pages/historico-aluno.php`**
   - Adicionado cálculo prévio de variáveis (linhas ~1271-1310)
   - Removido bloco de teóricas dentro de cada categoria (linhas ~1291-1312)
   - Criado bloco único de resumo teórico (linhas ~1312-1338)
   - Ajustado texto do card de práticas (linhas ~1351-1357)

## Validação Esperada

### Teste 1 - Aluno com Categoria Combinada (ID 167, AB)

**Cenário:** Aluno com matrícula ativa AB (A + B)

**Validações:**
- ✅ **Bloco "Resumo Teórico do Curso":** Mostra "Total necessário: 45h teóricas" (uma vez só)
- ✅ **Categoria A:** Mostra apenas práticas (20 aulas), sem bloco de teóricas
- ✅ **Categoria B:** Mostra apenas práticas (20 aulas), sem bloco de teóricas
- ✅ **Total combinado:** Aparece em cada categoria mostrando "40 aulas práticas"
- ✅ **Bloco "Total Geral":** Continua mostrando corretamente 45h teóricas e 40 aulas práticas

### Teste 2 - Aluno com Categoria Simples (B)

**Cenário:** Aluno com categoria B apenas

**Validações:**
- ✅ **Categoria B:** Mostra teóricas (45h) e práticas (20 aulas) normalmente
- ✅ **Não há bloco de "Resumo Teórico do Curso"** (apenas para categorias combinadas)

## Observações Técnicas

- As variáveis `$totalTeoricasGeralDetalhado`, `$totalTeoricasConcluidasGeralDetalhado` e `$percentualTeoricasGeralDetalhado` são calculadas especificamente para o bloco "Progresso Detalhado por Categoria"
- As mesmas variáveis são recalculadas no bloco "Total Geral" (com nomes diferentes: `$totalTeoricasGeral`, etc.) para manter a independência dos blocos
- A lógica de cálculo reutiliza a função centralizada `calcularCargaCategoriaHistorico()` para garantir consistência
- Comentários explicativos foram adicionados no código para documentar a regra de negócio

