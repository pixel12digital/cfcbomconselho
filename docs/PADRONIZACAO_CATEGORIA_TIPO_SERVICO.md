# Padronização de Categoria e Tipo de Serviço - Alunos

## Objetivo

Padronizar a exibição de categoria e tipo de serviço entre:
- Lista de Alunos (coluna Categoria)
- Modal Detalhes do Aluno (header)
- Aba Matrícula (já estava correto)
- Timeline da matrícula (já estava correto)

**Regra:** Sempre priorizar dados da matrícula ativa quando ela existir, usando dados do aluno como fallback.

## Problema Identificado

1. **Lista de Alunos:** Exibia apenas "B" (categoria antiga do cadastro do aluno)
2. **Modal Detalhes:** Exibia "Primeira Habilitação B – Automóvel" (usando dados antigos do aluno)
3. **Aba Matrícula:** Já estava correto, exibindo "AB – A + B" (da matrícula ativa)
4. **Timeline:** Já estava correto, exibindo "Categoria AB – primeira_habilitacao (status: ativa)"

**Causa:** A lista e o header estavam usando `aluno.categoria_cnh` e `aluno.tipo_servico` (dados antigos do cadastro), enquanto a matrícula ativa tinha `matricula.categoria_cnh = "AB"` e `matricula.tipo_servico = "primeira_habilitacao"`.

## Alterações Realizadas

### 1. Backend - API `admin/api/alunos.php`

**Arquivo:** `admin/api/alunos.php` (função `handleGet`)

**Mudança:** Adicionado `categoria_cnh` e `tipo_servico` no SELECT da matrícula ativa e incluídos no objeto aluno retornado.

**Antes:**
```php
$matriculaAtiva = $db->fetch("
    SELECT 
        renach, 
        status, 
        data_fim,
        ...
    FROM matriculas 
    WHERE aluno_id = ? AND status = 'ativa'
    ...
", [$id]);
```

**Depois:**
```php
$matriculaAtiva = $db->fetch("
    SELECT 
        renach, 
        status, 
        categoria_cnh,
        tipo_servico,
        data_fim,
        ...
    FROM matriculas 
    WHERE aluno_id = ? AND status = 'ativa'
    ...
", [$id]);

// Incluir no objeto aluno
$aluno['categoria_cnh_matricula'] = $matriculaAtiva['categoria_cnh'] ?? null;
$aluno['tipo_servico_matricula'] = $matriculaAtiva['tipo_servico'] ?? null;
```

**Resultado:** A API agora retorna `categoria_cnh_matricula` e `tipo_servico_matricula` quando há matrícula ativa.

### 2. Frontend - Lista de Alunos

**Arquivo:** `admin/pages/alunos.php` (renderização da tabela)

**Mudança:** Criada função helper PHP `obterCategoriaExibicao()` e atualizada a renderização da coluna "Categoria" para usar essa função.

**Função Helper PHP:**
```php
function obterCategoriaExibicao($aluno) {
    // Prioridade 1: Categoria da matrícula ativa
    if (!empty($aluno['categoria_cnh_matricula'])) {
        return $aluno['categoria_cnh_matricula'];
    }
    // Prioridade 2: Categoria do aluno (fallback)
    if (!empty($aluno['categoria_cnh'])) {
        return $aluno['categoria_cnh'];
    }
    // Prioridade 3: Tentar extrair de operações
    if (!empty($aluno['operacoes'])) {
        $operacoes = is_string($aluno['operacoes']) ? json_decode($aluno['operacoes'], true) : $aluno['operacoes'];
        if (is_array($operacoes) && !empty($operacoes)) {
            $primeiraOp = $operacoes[0];
            return $primeiraOp['categoria'] ?? $primeiraOp['categoria_cnh'] ?? 'N/A';
        }
    }
    return 'N/A';
}
```

**Renderização da Coluna:**
```php
<td>
    <?php 
    // Obter categoria priorizando matrícula ativa
    $categoriaExibicao = obterCategoriaExibicao($aluno);
    
    // Se houver matrícula ativa, usar badge primário; caso contrário, secundário
    $badgeClass = !empty($aluno['categoria_cnh_matricula']) ? 'bg-primary' : 'bg-secondary';
    
    echo '<span class="badge ' . $badgeClass . '" title="Categoria CNH">' . 
         htmlspecialchars($categoriaExibicao) . '</span>';
    ?>
</td>
```

**Resultado:** A lista agora exibe "AB" (da matrícula ativa) em vez de "B" (do cadastro do aluno).

### 3. Frontend - Modal Detalhes do Aluno (Header)

**Arquivo:** `admin/pages/alunos.php` (função `preencherModalVisualizacao`)

**Mudança:** Criadas funções helper JavaScript `obterCategoriaExibicao()` e `obterTipoServicoExibicao()` e atualizado o texto do header para usar essas funções.

**Funções Helper JavaScript:**
```javascript
function obterCategoriaExibicao(aluno) {
    // Prioridade 1: Categoria da matrícula ativa
    if (aluno.categoria_cnh_matricula) {
        return aluno.categoria_cnh_matricula;
    }
    // Prioridade 2: Categoria do aluno (fallback)
    if (aluno.categoria_cnh) {
        return aluno.categoria_cnh;
    }
    // Prioridade 3: Tentar extrair de operações
    if (aluno.operacoes) {
        try {
            const operacoes = typeof aluno.operacoes === 'string' ? JSON.parse(aluno.operacoes) : aluno.operacoes;
            if (Array.isArray(operacoes) && operacoes.length > 0) {
                const primeiraOp = operacoes[0];
                return primeiraOp.categoria || primeiraOp.categoria_cnh || 'N/A';
            }
        } catch (e) {
            // Ignorar erro de parse
        }
    }
    return 'N/A';
}

function obterTipoServicoExibicao(aluno) {
    // Prioridade 1: Tipo de serviço da matrícula ativa
    if (aluno.tipo_servico_matricula) {
        return aluno.tipo_servico_matricula;
    }
    // Prioridade 2: Tipo de serviço do aluno (fallback)
    if (aluno.tipo_servico) {
        return aluno.tipo_servico;
    }
    // Prioridade 3: Tentar extrair de operações
    if (aluno.operacoes) {
        try {
            const operacoes = typeof aluno.operacoes === 'string' ? JSON.parse(aluno.operacoes) : aluno.operacoes;
            if (Array.isArray(operacoes) && operacoes.length > 0) {
                const primeiraOp = operacoes[0];
                return primeiraOp.tipo_servico || primeiraOp.tipo || 'Primeira Habilitação';
            }
        } catch (e) {
            // Ignorar erro de parse
        }
    }
    return 'Primeira Habilitação';
}
```

**Formatação do Texto:**
```javascript
// Obter categoria e tipo de serviço usando as funções helper
const categoriaExibicao = obterCategoriaExibicao(aluno);
const tipoServicoExibicao = obterTipoServicoExibicao(aluno);

// Formatar texto do tipo de serviço
const tipoServicoMap = {
    'primeira_habilitacao': 'Primeira Habilitação',
    'adicao': 'Adição de Categoria',
    'mudanca': 'Mudança de Categoria',
    'renovacao': 'Renovação',
    'reciclagem': 'Reciclagem'
};

const tipoServicoTextoFormatado = tipoServicoMap[tipoServicoExibicao] || tipoServicoExibicao;

// Formatar categoria (ex: AB -> "A + B", B -> "B")
let categoriaFormatada = categoriaExibicao;
if (categoriaExibicao === 'AB') {
    categoriaFormatada = 'A + B';
} else if (categoriaExibicao === 'AC') {
    categoriaFormatada = 'A + C';
} else if (categoriaExibicao === 'AD') {
    categoriaFormatada = 'A + D';
} else if (categoriaExibicao === 'AE') {
    categoriaFormatada = 'A + E';
}

// Montar texto final
let tipoServicoTexto = `${tipoServicoTextoFormatado} ${categoriaFormatada}`;
```

**Resultado:** O header agora exibe "Primeira Habilitação A + B" (da matrícula ativa) em vez de "Primeira Habilitação B – Automóvel" (dados antigos do aluno).

## Lógica de Priorização

A lógica de priorização é aplicada em todos os pontos de exibição:

1. **Prioridade 1:** Dados da matrícula ativa (`categoria_cnh_matricula`, `tipo_servico_matricula`)
2. **Prioridade 2:** Dados do aluno (`categoria_cnh`, `tipo_servico`)
3. **Prioridade 3:** Dados extraídos de `operacoes` (JSON)
4. **Fallback:** Valores padrão ("N/A" para categoria, "Primeira Habilitação" para tipo de serviço)

## Arquivos Modificados

1. **`admin/api/alunos.php`**
   - Adicionado `categoria_cnh` e `tipo_servico` no SELECT da matrícula ativa
   - Incluídos `categoria_cnh_matricula` e `tipo_servico_matricula` no objeto aluno retornado

2. **`admin/pages/alunos.php`**
   - Criada função helper PHP `obterCategoriaExibicao()`
   - Criada função helper PHP `obterTipoServicoExibicao()`
   - Atualizada renderização da coluna "Categoria" na lista de alunos
   - Criadas funções helper JavaScript `obterCategoriaExibicao()` e `obterTipoServicoExibicao()`
   - Atualizado texto do header do modal Detalhes do Aluno

## Testes Recomendados

### Teste 1 - Aluno com Matrícula Ativa (ID 167)

**Cenário:** Aluno com matrícula ativa (AB – Primeira Habilitação)

**Validações:**
- ✅ **Aba Matrícula:** Categoria: AB – A + B, Tipo de serviço: Primeira Habilitação
- ✅ **Timeline:** Continua mostrando "Categoria AB – primeira_habilitacao (status: ativa)"
- ✅ **Lista de Alunos:** Coluna Categoria mostra "AB" (badge primário)
- ✅ **Detalhes do Aluno (header):** Texto principal deve refletir "Primeira Habilitação A + B"

### Teste 2 - Aluno sem Matrícula Ativa

**Cenário:** Aluno sem matrícula ou sem matrícula ativa

**Validações:**
- ✅ **Lista de Alunos:** Coluna Categoria mostra categoria do aluno (badge secundário)
- ✅ **Detalhes do Aluno (header):** Texto principal usa dados do aluno (fallback)
- ✅ Nenhum erro é exibido

## Observações Técnicas

- A timeline e a aba Matrícula já estavam corretas e não foram alteradas
- As funções helper garantem consistência entre todos os pontos de exibição
- O fallback para dados do aluno garante que nada quebre quando não houver matrícula ativa
- A formatação de categoria (AB -> "A + B") é aplicada apenas no header do modal Detalhes para melhor legibilidade

## Próximos Passos (Opcional)

- Se a lista de alunos estiver usando dados diretos do banco (não da API), considerar adicionar JOIN com matrículas ativas na query para incluir `categoria_cnh_matricula` e `tipo_servico_matricula` diretamente no array `$alunos`
- Considerar criar uma função centralizada (helper global) para formatação de categoria e tipo de serviço, evitando duplicação de lógica

