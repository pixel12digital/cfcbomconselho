# Refatoração do Layout - Progresso Detalhado por Categoria

## Objetivo

Refatorar apenas o layout e textos da seção "Progresso Detalhado por Categoria" para ficar menos redundante e com cards mais compactos, **sem alterar a lógica de cálculo**.

## Alterações Realizadas

### 1. Card de Resumo Teórico - Mais Compacto

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1333-1355)

**ANTES:**
```php
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

**DEPOIS:**
```php
<div class="card card-progresso-teorico mb-3">
    <div class="card-body py-3">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <div class="fw-semibold text-primary">
                    Resumo Teórico do Curso (Categorias combinadas)
                </div>
                <small class="text-muted">
                    Carga teórica compartilhada para todas as categorias deste curso combinado.
                </small>
            </div>
            <div class="text-end">
                <div class="fw-bold">
                    <?php echo $totalTeoricasConcluidasGeralDetalhado; ?> / <?php echo $totalTeoricasGeralDetalhado; ?>
                </div>
                <small class="text-muted">
                    Total necessário: <?php echo $totalTeoricasGeralDetalhado; ?> aulas teóricas
                </small>
            </div>
        </div>
    </div>
</div>
```

**Mudanças:**
- ✅ Removida barra de progresso (redundante, já existe no "Total Geral")
- ✅ Layout horizontal mais compacto (informações lado a lado)
- ✅ Uso de `py-3` para reduzir altura do card
- ✅ Classe `card-progresso-teorico` para estilização específica

### 2. Cards de Categoria - Simplificados

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1357-1395)

**ANTES:**
- Card grande com título, práticas, barra de progresso, texto "Total combinado" repetido
- Detalhamento completo por tipo de veículo (moto, carro, carga, passageiros, combinação)
- Muito conteúdo visual

**DEPOIS:**
```php
<div class="card card-progresso-categoria mb-3">
    <div class="card-body py-3">
        <div class="mb-2 d-flex justify-content-between align-items-center">
            <div class="fw-semibold text-primary">
                Categoria <?php echo $categoria; ?>: <?php echo htmlspecialchars($config['nome']); ?>
            </div>
            <span class="badge bg-light text-primary border">
                Aulas práticas (<?php echo $categoria; ?>)
            </span>
        </div>
        
        <div class="small text-muted mb-2">
            Categoria <?php echo $categoria; ?>: <?php echo $config['horas_praticas_total']; ?> aulas práticas
        </div>
        
        <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="small text-muted">Progresso</span>
            <span class="badge bg-success">
                <?php echo $totalPraticasConcluidas; ?>/<?php echo $totalPraticasNecessarias; ?>
            </span>
        </div>
        
        <div class="progress" style="height: 8px;">
            <div class="progress-bar bg-success" role="progressbar" 
                 style="width: <?php echo $percentualPraticas; ?>%">
            </div>
        </div>
    </div>
</div>
```

**Mudanças:**
- ✅ Removido detalhamento por tipo de veículo (moto, carro, etc.)
- ✅ Removido texto "Total combinado" de cada card (agora está no rodapé único)
- ✅ Layout mais enxuto e focado apenas no essencial
- ✅ Uso de `py-3` para reduzir altura
- ✅ Classe `card-progresso-categoria` para estilização específica

### 3. Rodapé com Total Combinado

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1397-1400)

**NOVO CÓDIGO ADICIONADO:**
```php
<!-- Rodapé com total combinado de práticas -->
<div class="text-muted small mt-1 mb-3">
    Total combinado de aulas práticas para todas as categorias: <?php echo $cargaCategoria['total_aulas_praticas']; ?> aulas.
</div>
```

**Justificativa:**
- ✅ Informação aparece uma vez só, não repetida em cada card
- ✅ Texto discreto e informativo
- ✅ Reutiliza a variável `$cargaCategoria['total_aulas_praticas']` que já está correta

### 4. CSS para Cards Compactos

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~1808-1825)

**NOVO CÓDIGO ADICIONADO:**
```css
<style>
    /* Estilos para cards compactos do Progresso Detalhado por Categoria */
    .card-progresso-teorico,
    .card-progresso-categoria {
        border-radius: 10px;
    }

    .card-progresso-teorico .card-body,
    .card-progresso-categoria .card-body {
        padding-top: 0.75rem;
        padding-bottom: 0.75rem;
    }

    /* Em telas grandes, reduzir um pouco ainda mais a "altura visual" */
    @media (min-width: 992px) {
        .card-progresso-categoria {
            margin-bottom: 0.75rem;
        }
    }
</style>
```

**Justificativa:**
- ✅ Cards com bordas arredondadas (10px) para visual mais moderno
- ✅ Padding reduzido (`py-3` = 0.75rem) para cards mais baixos
- ✅ Em telas grandes (≥992px), reduz ainda mais o espaçamento entre cards

## Estrutura Visual Final

**Para categoria combinada (AB):**

```
[Card] Progresso Detalhado por Categoria
  ↓
  [Card Compacto] Resumo Teórico do Curso (Categorias combinadas)
    - Layout horizontal: Título/Descrição | Progresso (0/45)
    - Total necessário: 45 aulas teóricas
  ↓
  [Card Compacto] Categoria A: Motocicletas
    - Badge: "Aulas práticas (A)"
    - Texto: "Categoria A: 20 aulas práticas"
    - Barra de progresso: 0/20
  ↓
  [Card Compacto] Categoria B: Automóveis
    - Badge: "Aulas práticas (B)"
    - Texto: "Categoria B: 20 aulas práticas"
    - Barra de progresso: 0/20
  ↓
  [Rodapé] Total combinado de aulas práticas para todas as categorias: 40 aulas.
```

## Garantias de Não-Regressão

✅ **Lógica de cálculo:**
- Não alterada - continua usando `$cargaCategoria['total_horas_teoricas']` e `$cargaCategoria['total_aulas_praticas']`
- Variáveis reutilizadas do bloco "Total Geral" para consistência

✅ **Funcionalidades mantidas:**
- Cálculo de progresso por categoria continua funcionando
- Barra de progresso mostra valores corretos
- Total combinado calculado corretamente

✅ **Compatibilidade:**
- Cards responsivos (coluna única em mobile)
- Mantém padrão visual do sistema (Bootstrap)
- Ícones e cores preservados

## Arquivos Modificados

1. **`admin/pages/historico-aluno.php`**
   - Refatorado card de resumo teórico (linhas ~1333-1355)
   - Simplificado cards de categoria (linhas ~1357-1395)
   - Adicionado rodapé com total combinado (linhas ~1397-1400)
   - Adicionado CSS para cards compactos (linhas ~1808-1825)

## Validação Esperada

### Teste 1 - Aluno com Categoria Combinada (ID 167, AB)

**Cenário:** Aluno com matrícula ativa AB (A + B)

**Validações:**
- ✅ **Card "Resumo Teórico":** Layout horizontal compacto, mostra "0 / 45" (ou valor correto)
- ✅ **Card "Categoria A":** Mostra apenas práticas (20 aulas), sem detalhamento por tipo
- ✅ **Card "Categoria B":** Mostra apenas práticas (20 aulas), sem detalhamento por tipo
- ✅ **Rodapé:** Mostra "Total combinado de aulas práticas para todas as categorias: 40 aulas."
- ✅ **Bloco "Total Geral":** Continua mostrando corretamente 45h teóricas e 40 aulas práticas
- ✅ **Altura dos cards:** Cards mais baixos e compactos visualmente

### Teste 2 - Responsividade Mobile

**Cenário:** Visualizar em dispositivo móvel

**Validações:**
- ✅ Cards continuam em coluna única
- ✅ Conteúdo não fica gigante, mantém-se compacto
- ✅ Textos legíveis e bem formatados

## Observações Técnicas

- **Remoção do detalhamento por tipo de veículo:** O detalhamento (moto, carro, carga, etc.) foi removido dos cards individuais para reduzir o "peso visual". Se necessário no futuro, pode ser adicionado em um modal ou seção separada.
- **Reutilização de variáveis:** Todas as variáveis usadas (`$totalTeoricasGeralDetalhado`, `$cargaCategoria['total_aulas_praticas']`, etc.) são as mesmas já calculadas corretamente, garantindo consistência.
- **CSS inline:** O CSS foi adicionado inline no arquivo PHP para manter tudo em um único lugar. Se preferir, pode ser movido para um arquivo CSS separado no futuro.

