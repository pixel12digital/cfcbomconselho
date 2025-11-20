# Correção do Cálculo de Aulas Práticas no Histórico do Aluno

## Problema Identificado

Para o aluno ID 167 com categoria AB (A + B):
- ✅ **Total de Horas Necessárias:** 85 (correto)
- ✅ **Aulas Teóricas:** 45h (correto)
- ❌ **Aulas Práticas:** 20 aulas (INCORRETO - deveria ser 40)

**Causa:** O sistema estava usando `horas_praticas_total` de uma categoria individual (B = 20) em vez de somar as práticas de todas as categorias componentes (A = 20 + B = 20 = 40).

## Regra de Negócio

### Para Categorias Combinadas (ex: AB, AC, etc.)

1. **Aulas Teóricas:**
   - ❌ NÃO devem ser duplicadas
   - ✅ Devem usar carga teórica única (ex: 45h, não 90h)

2. **Aulas Práticas:**
   - ✅ Devem ser SOMADAS entre as categorias componentes
   - Exemplo: AB = A (20) + B (20) = 40 aulas práticas

### Para Categorias Simples (ex: B)

- Usar valores diretos da configuração da categoria

## Estrutura de Configuração

### Tabela: `configuracoes_categorias`

**Campos principais:**
- `categoria`: Código da categoria (A, B, AB, etc.)
- `horas_teoricas`: Carga teórica em horas/aulas
- `horas_praticas_total`: Total de aulas práticas da categoria
- `horas_praticas_moto`, `horas_praticas_carro`, etc.: Detalhamento por tipo de veículo

### Identificação de Categoria Combinada

- **Categoria simples:** Apenas uma letra (A, B, C, D, E)
- **Categoria combinada:** Duas ou mais letras (AB, AC, ABC, etc.)

A classe `ConfiguracoesCategorias` possui o método `decomporCategoriaCombinada()` que retorna as categorias individuais:
- `AB` → `['A', 'B']`
- `ABC` → `['A', 'B', 'C']`

## Alterações Realizadas

### 1. Função Centralizada `calcularCargaCategoriaHistorico()`

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~209-270)

**Função criada:**
```php
function calcularCargaCategoriaHistorico($categoriaCodigo, $configManager) {
    // Decompor categoria para verificar se é combinada
    $categoriasIndividuais = $configManager->decomporCategoriaCombinada($categoriaCodigo);
    $ehCombinada = count($categoriasIndividuais) > 1;
    
    if ($ehCombinada) {
        // Categoria combinada: somar práticas, teoria única
        $totalAulasPraticas = 0;
        $primeiraConfig = null;
        
        foreach ($categoriasIndividuais as $cat) {
            $config = $configManager->getConfiguracaoByCategoria($cat);
            if ($config) {
                if ($primeiraConfig === null) {
                    $primeiraConfig = $config;
                }
                // Práticas: somar todas as categorias componentes
                $totalAulasPraticas += (int)($config['horas_praticas_total'] ?? 0);
            }
        }
        
        // Teoria: usar apenas da primeira categoria (não somar)
        $totalHorasTeoricas = $primeiraConfig ? (int)($primeiraConfig['horas_teoricas'] ?? 0) : 0;
        
        return [
            'total_horas_teoricas' => $totalHorasTeoricas,
            'total_aulas_praticas' => $totalAulasPraticas,
            'eh_combinada' => true,
            'categorias_componentes' => $categoriasIndividuais
        ];
    } else {
        // Categoria simples: usar valores diretos
        $config = $configManager->getConfiguracaoByCategoria($categoriaCodigo);
        if ($config) {
            return [
                'total_horas_teoricas' => (int)($config['horas_teoricas'] ?? 0),
                'total_aulas_praticas' => (int)($config['horas_praticas_total'] ?? 0),
                'eh_combinada' => false,
                'categorias_componentes' => [$categoriaCodigo]
            ];
        }
    }
    
    // Fallback
    return [
        'total_horas_teoricas' => 45,
        'total_aulas_praticas' => 20,
        'eh_combinada' => false,
        'categorias_componentes' => []
    ];
}
```

**Resultado:** Função centralizada que garante teoria única e práticas somadas para categorias combinadas.

### 2. Aplicação da Função no Cálculo Inicial

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~289-293)

**Antes:**
```php
if ($ehCategoriaCombinada) {
    $aulasNecessarias = 0;
    $aulasTeoricasNecessarias = 0;
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        $aulasNecessarias += $config['horas_praticas_total'];
        $aulasTeoricasNecessarias += $config['horas_teoricas']; // ❌ ERRADO: somando teóricas
    }
}
```

**Depois:**
```php
if ($ehCategoriaCombinada) {
    // REGRA: Usar função centralizada para garantir teoria única e práticas somadas
    $aulasNecessarias = $cargaCategoria['total_aulas_praticas']; // Soma das práticas (ex: 20+20=40)
    $aulasTeoricasNecessarias = $cargaCategoria['total_horas_teoricas']; // Teoria única (ex: 45, não 90)
    // ...
}
```

**Resultado:** Práticas somadas corretamente (40) e teoria única (45).

### 3. Correção do Card "Total Aulas Práticas"

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~862-875)

**Antes:**
```php
if ($ehCategoriaCombinada) {
    $primeiraConfig = reset($configuracoesCategorias);
    $totalTeoricasGeral = $primeiraConfig['horas_teoricas'];
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        $totalPraticasGeral += $config['horas_praticas_total']; // ❌ Somando dentro do loop
    }
}
```

**Depois:**
```php
if ($ehCategoriaCombinada) {
    // REGRA: Usar função centralizada para garantir teoria única e práticas somadas
    $totalTeoricasGeral = $cargaCategoria['total_horas_teoricas']; // Teoria única (não somada)
    $totalPraticasGeral = $cargaCategoria['total_aulas_praticas']; // Práticas somadas (ex: 40)
    
    foreach ($configuracoesCategorias as $categoria => $config) {
        // NÃO somar práticas aqui - já foi calculado pela função centralizada
        // ...
    }
}
```

**Resultado:** Card "Total Aulas Práticas" agora mostra 40 em vez de 20.

### 4. Correção para Categoria Simples

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~445-451 e ~876-880)

**Mudança:** Categoria simples também usa a função centralizada para garantir consistência:

```php
// Antes:
$aulasNecessarias = $configuracaoCategoria['horas_praticas_total'];
$aulasTeoricasNecessarias = $configuracaoCategoria['horas_teoricas'];

// Depois:
$aulasNecessarias = $cargaCategoria['total_aulas_praticas'];
$aulasTeoricasNecessarias = $cargaCategoria['total_horas_teoricas'];
```

**Resultado:** Consistência entre categoria simples e combinada.

### 5. Melhoria no Card Individual de Categoria

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1302-1332)

**Mudança:** Card individual de cada categoria (dentro do loop) agora mostra:
- Práticas da categoria específica (ex: "Categoria A: 20 aulas práticas")
- Total combinado quando é categoria combinada (ex: "Total combinado (todas as categorias): 40 aulas práticas")

**Resultado:** Usuário vê tanto o detalhamento por categoria quanto o total combinado.

### 6. Logs de Debug

**Arquivo:** `admin/pages/historico-aluno.php`

**Logs adicionados:**
```php
error_log('[DEBUG HISTORICO] Aluno ' . $alunoId . ' - categoria: ' . $categoriaAluno);
error_log('[DEBUG HISTORICO] Carga calculada: ' . json_encode($cargaCategoria));
error_log('[DEBUG HISTORICO] total_horas_teoricas: ' . $cargaCategoria['total_horas_teoricas']);
error_log('[DEBUG HISTORICO] total_aulas_praticas: ' . $cargaCategoria['total_aulas_praticas']);
error_log('[DEBUG HISTORICO] total_horas_necessarias: ' . ($cargaCategoria['total_horas_teoricas'] + $cargaCategoria['total_aulas_praticas']));
error_log('[DEBUG HISTORICO FINAL] categoria: ' . $categoriaAluno . ', ' . json_encode([
    'totalHorasTeoricas' => $totalTeoricasGeral,
    'totalAulasPraticas' => $totalPraticasGeral,
    'totalHorasNecessarias' => $totalTeoricasGeral + $totalPraticasGeral
]));
```

**Resultado:** Logs detalhados para facilitar debug e validação.

## Arquivos Modificados

1. **`admin/pages/historico-aluno.php`**
   - Criada função `calcularCargaCategoriaHistorico()` (linhas ~209-270)
   - Aplicada função no cálculo inicial para categoria combinada (linhas ~289-293)
   - Aplicada função no cálculo inicial para categoria simples (linhas ~445-451)
   - Corrigido cálculo do card "Total Aulas Práticas" (linhas ~862-875)
   - Corrigido cálculo para categoria simples no card geral (linhas ~876-880)
   - Melhorado card individual de categoria para mostrar total combinado (linhas ~1302-1332)
   - Adicionados logs de debug

## Validação Esperada

### Teste 1 - Aluno com Categoria Combinada (ID 167, AB)

**Cenário:** Aluno com matrícula ativa AB (A + B)

**Validações:**
- ✅ **Total de Horas Necessárias:** 85 (45 teóricas + 40 práticas)
- ✅ **Aulas Teóricas:** 45h (não 90h)
- ✅ **Aulas Práticas:** 40 aulas (20 de A + 20 de B, não apenas 20)
- ✅ **Card "Total Aulas Práticas":** Mostra "Total necessário: 40h práticas"
- ✅ **Cards individuais:** Mostram "Categoria A: 20 aulas práticas" e "Categoria B: 20 aulas práticas" com nota "Total combinado: 40 aulas práticas"

### Teste 2 - Aluno com Categoria Simples (B)

**Cenário:** Aluno com categoria B apenas

**Validações:**
- ✅ **Total de Horas Necessárias:** 65 (45 teóricas + 20 práticas, exemplo)
- ✅ **Aulas Teóricas:** 45h (ou valor configurado para B)
- ✅ **Aulas Práticas:** 20 aulas (valor configurado para B)
- ✅ **Card "Total Aulas Práticas":** Mostra "Total necessário: 20h práticas"

### Logs Esperados no Console/error_log

Para aluno 167 (AB):
```
[DEBUG HISTORICO] Aluno 167 - categoria: AB
[DEBUG HISTORICO] Carga calculada: {"total_horas_teoricas":45,"total_aulas_praticas":40,"eh_combinada":true,"categorias_componentes":["A","B"]}
[DEBUG HISTORICO] total_horas_teoricas: 45
[DEBUG HISTORICO] total_aulas_praticas: 40
[DEBUG HISTORICO] total_horas_necessarias: 85
[DEBUG HISTORICO FINAL] categoria: AB, {"totalHorasTeoricas":45,"totalAulasPraticas":40,"totalHorasNecessarias":85}
```

## Observações Técnicas

- A função `calcularCargaCategoriaHistorico()` é centralizada e reutilizada em todos os pontos de cálculo
- A regra de negócio está documentada com comentários `// REGRA:` para facilitar manutenção
- A teoria nunca é duplicada para categorias combinadas (usa valor da primeira categoria)
- As práticas são sempre somadas para categorias combinadas
- A função funciona tanto para categorias simples quanto combinadas
- Logs de debug facilitam validação e troubleshooting

## Próximos Passos (Opcional)

- Considerar mover a função `calcularCargaCategoriaHistorico()` para a classe `ConfiguracoesCategorias` para reutilização em outras partes do sistema
- Verificar se há outros pontos no sistema (dashboards, relatórios) que precisam da mesma correção
- Adicionar testes unitários para a função de cálculo

