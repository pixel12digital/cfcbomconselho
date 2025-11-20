# Correção da Padronização de Categoria - Lista e Histórico

## Problema Identificado

A padronização de categoria e tipo de serviço estava funcionando apenas no modal "Detalhes do Aluno", mas não na:
1. **Lista de Alunos** - Coluna Categoria mostrava "B" (badge cinza) em vez de "AB" (da matrícula ativa)
2. **Página Histórico do Aluno** - Bloco "Informações do Aluno" mostrava "Categoria CNH: B" em vez de "AB"

**Causa:** As queries que montavam os arrays `$alunos` e `$alunoData` não estavam incluindo os campos `categoria_cnh_matricula` e `tipo_servico_matricula` da matrícula ativa.

## Alterações Realizadas

### 1. Backend - Query da Lista de Alunos

**Arquivo:** `admin/index.php` (case 'alunos', linhas ~2139-2143)

**Mudança:** Adicionado LEFT JOIN com subquery para trazer dados da matrícula ativa diretamente no SELECT.

**Antes:**
```php
$sql = "
    SELECT DISTINCT a.id, a.nome, a.cpf, a.rg, a.data_nascimento, a.endereco, 
           a.telefone, a.email, a.cfc_id, a.categoria_cnh, a.status, a.criado_em, a.operacoes
    FROM alunos a
";
```

**Depois:**
```php
// REGRA DE PADRONIZAÇÃO: Sempre priorizar dados da matrícula ativa quando existir
$sql = "
    SELECT DISTINCT 
        a.id, a.nome, a.cpf, a.rg, a.data_nascimento, a.endereco, 
        a.telefone, a.email, a.cfc_id, a.categoria_cnh, a.status, a.criado_em, a.operacoes,
        m_ativa.categoria_cnh AS categoria_cnh_matricula,
        m_ativa.tipo_servico AS tipo_servico_matricula
    FROM alunos a
    LEFT JOIN (
        SELECT 
            m1.aluno_id,
            m1.categoria_cnh,
            m1.tipo_servico
        FROM matriculas m1
        WHERE m1.status = 'ativa'
        GROUP BY m1.aluno_id
    ) AS m_ativa ON m_ativa.aluno_id = a.id
";
```

**Resultado:** O array `$alunos` agora contém `categoria_cnh_matricula` e `tipo_servico_matricula` para cada aluno que possui matrícula ativa.

### 2. Backend - Query do Histórico do Aluno

**Arquivo:** `admin/pages/historico-aluno.php` (linhas ~64-92)

**Mudança:** Adicionado LEFT JOIN com subquery para trazer dados da matrícula ativa e lógica de fallback quando os dados já vêm do sistema de roteamento.

**Antes:**
```php
$alunoData = $db->fetch("
    SELECT a.*, c.nome as cfc_nome, c.cnpj as cfc_cnpj
    FROM alunos a 
    LEFT JOIN cfcs c ON a.cfc_id = c.id 
    WHERE a.id = ?
", [$alunoId]);
```

**Depois:**
```php
// REGRA DE PADRONIZAÇÃO: Sempre priorizar dados da matrícula ativa quando existir
$alunoData = $db->fetch("
    SELECT 
        a.*, 
        c.nome as cfc_nome, 
        c.cnpj as cfc_cnpj,
        m_ativa.categoria_cnh AS categoria_cnh_matricula,
        m_ativa.tipo_servico AS tipo_servico_matricula
    FROM alunos a 
    LEFT JOIN cfcs c ON a.cfc_id = c.id
    LEFT JOIN (
        SELECT 
            m1.aluno_id,
            m1.categoria_cnh,
            m1.tipo_servico
        FROM matriculas m1
        WHERE m1.status = 'ativa'
        GROUP BY m1.aluno_id
    ) AS m_ativa ON m_ativa.aluno_id = a.id
    WHERE a.id = ?
", [$alunoId]);

// Se os dados vierem do sistema de roteamento, buscar matrícula ativa separadamente
if (defined('ADMIN_ROUTING') && isset($aluno) && empty($alunoData['categoria_cnh_matricula'])) {
    $matriculaAtiva = $db->fetch("
        SELECT categoria_cnh, tipo_servico
        FROM matriculas
        WHERE aluno_id = ? AND status = 'ativa'
        ORDER BY data_inicio DESC
        LIMIT 1
    ", [$alunoId]);
    
    if ($matriculaAtiva) {
        $alunoData['categoria_cnh_matricula'] = $matriculaAtiva['categoria_cnh'] ?? null;
        $alunoData['tipo_servico_matricula'] = $matriculaAtiva['tipo_servico'] ?? null;
    }
}
```

**Resultado:** O array `$alunoData` agora contém `categoria_cnh_matricula` e `tipo_servico_matricula` quando há matrícula ativa.

### 3. Frontend - Renderização da Categoria no Histórico

**Arquivo:** `admin/pages/historico-aluno.php`

**Mudanças:** Aplicada a regra de priorização em todos os pontos onde a categoria é exibida:

1. **Bloco "Informações do Aluno" (linha ~475):**
```php
// REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
$categoriaExibicao = !empty($alunoData['categoria_cnh_matricula']) 
    ? $alunoData['categoria_cnh_matricula'] 
    : $alunoData['categoria_cnh'];
$badgeClass = !empty($alunoData['categoria_cnh_matricula']) ? 'bg-primary' : 'bg-secondary';
?>
<p><strong>Categoria CNH:</strong> 
    <span class="badge <?php echo $badgeClass; ?>"><?php echo htmlspecialchars($categoriaExibicao); ?></span>
</p>
```

2. **Configuração de Categoria (linha ~180):**
```php
// REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
$categoriaAluno = !empty($alunoData['categoria_cnh_matricula']) 
    ? $alunoData['categoria_cnh_matricula'] 
    : $alunoData['categoria_cnh'];
```

3. **Badge de Categoria (linha ~667):**
```php
// REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
$categoriaExibicao = !empty($alunoData['categoria_cnh_matricula']) 
    ? $alunoData['categoria_cnh_matricula'] 
    : $alunoData['categoria_cnh'];
?>
<span class="badge bg-warning text-dark fs-6">
    Categoria <?php echo htmlspecialchars($categoriaExibicao); ?>
</span>
```

4. **Mensagem de Configuração Não Encontrada (linha ~713):**
```php
// REGRA DE PADRONIZAÇÃO: Priorizar categoria da matrícula ativa quando existir
$categoriaExibicao = !empty($alunoData['categoria_cnh_matricula']) 
    ? $alunoData['categoria_cnh_matricula'] 
    : $alunoData['categoria_cnh'];
?>
<small class="text-muted">Categoria: <?php echo htmlspecialchars($categoriaExibicao); ?></small>
```

**Resultado:** Todos os pontos de exibição da categoria no histórico agora priorizam a matrícula ativa.

### 4. Frontend - Lista de Alunos (já estava correto)

**Arquivo:** `admin/pages/alunos.php` (linha ~1647)

**Status:** A renderização da coluna Categoria já estava usando a função helper `obterCategoriaExibicao($aluno)` corretamente. Com a correção da query no `index.php`, agora os dados da matrícula ativa estão disponíveis e a função funciona como esperado.

## Lógica de Priorização Aplicada

A mesma regra de priorização foi aplicada em todos os pontos:

1. **Prioridade 1:** `categoria_cnh_matricula` (da matrícula ativa)
2. **Prioridade 2:** `categoria_cnh` (do cadastro do aluno)
3. **Prioridade 3:** Dados extraídos de `operacoes` (JSON) - apenas na lista
4. **Fallback:** "N/A" ou valor padrão

## Arquivos Modificados

1. **`admin/index.php`**
   - Adicionado LEFT JOIN com subquery para matrícula ativa na query da lista de alunos
   - Campos `categoria_cnh_matricula` e `tipo_servico_matricula` incluídos no SELECT

2. **`admin/pages/historico-aluno.php`**
   - Adicionado LEFT JOIN com subquery para matrícula ativa na query do aluno
   - Lógica de fallback quando dados vêm do sistema de roteamento
   - Aplicada regra de priorização em todos os pontos de exibição da categoria (4 locais)

## Testes de Validação

### Teste 1 - Aluno com Matrícula Ativa (ID 167)

**Cenário:** Aluno com matrícula ativa (AB – Primeira Habilitação)

**Validações Esperadas:**
- ✅ **Lista de Alunos:** Coluna Categoria mostra "AB" com badge primário (azul)
- ✅ **Histórico do Aluno:** Bloco "Informações do Aluno" mostra "Categoria CNH: AB" com badge primário
- ✅ **Aba Matrícula:** Continua "AB – A + B" (já estava correto)
- ✅ **Timeline:** Continua "Categoria AB – primeira_habilitacao (status: ativa)" (já estava correto)
- ✅ **Detalhes do Aluno (header):** Continua "Primeira Habilitação A + B" (já estava correto)

### Teste 2 - Aluno sem Matrícula Ativa

**Cenário:** Aluno sem matrícula ou sem matrícula ativa

**Validações Esperadas:**
- ✅ **Lista de Alunos:** Coluna Categoria mostra categoria do aluno com badge secundário (cinza)
- ✅ **Histórico do Aluno:** Bloco "Informações do Aluno" mostra categoria do aluno com badge secundário
- ✅ Nenhum erro é exibido

## Observações Técnicas

- A subquery usa `GROUP BY m1.aluno_id` para garantir que temos no máximo uma matrícula ativa por aluno
- Os aliases `categoria_cnh_matricula` e `tipo_servico_matricula` batem com o que as funções helper esperam
- A lógica de priorização está documentada com comentários `// REGRA DE PADRONIZAÇÃO:` para facilitar manutenção futura
- Nenhum filtro existente foi removido, apenas o JOIN foi incorporado
- A função helper `obterCategoriaExibicao($aluno)` na lista já estava correta, apenas faltavam os dados

## Próximos Passos (Opcional)

- Considerar criar uma função helper global `obterCategoriaExibicao($alunoData)` para evitar duplicação de lógica entre lista e histórico
- Verificar se há outros pontos no sistema onde a categoria é exibida e aplicar a mesma regra de priorização

