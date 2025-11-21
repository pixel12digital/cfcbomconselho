# Padronização de Categoria CNH - Turmas Teóricas

## Objetivo

Padronizar a exibição de categoria CNH nas telas de turmas teóricas para usar a mesma lógica já implementada no módulo de alunos, priorizando dados da matrícula ativa.

## Problema Identificado

1. **Aba "Alunos Matriculados":** Exibia apenas "B" (categoria antiga do cadastro do aluno) em vez de "AB" (da matrícula ativa)
2. **Modal "Matricular Alunos na Turma":** Card do aluno mostrava "Categoria: B" em vez de "Categoria: AB"

**Causa:** As telas de turma estavam usando `aluno.categoria_cnh` (dados antigos do cadastro), enquanto a matrícula ativa tinha `matricula.categoria_cnh = "AB"`.

## Alterações Realizadas

### 1. Helper Centralizado (`admin/includes/helpers_cnh.php`)

**Arquivo:** `admin/includes/helpers_cnh.php` (NOVO)

**Conteúdo:** Funções helper PHP centralizadas para reutilização entre módulos:
- `obterCategoriaExibicao($aluno)` - Prioriza matrícula ativa, fallback para aluno, depois operações
- `obterTipoServicoExibicao($aluno)` - Mesma lógica para tipo de serviço

**Benefício:** Evita duplicação de lógica e garante consistência entre módulos.

### 2. Backend - Query "Alunos Matriculados"

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php` (linha ~267)

**Mudança:** Adicionado LEFT JOIN com subquery para buscar categoria da matrícula ativa:

```php
LEFT JOIN (
    SELECT aluno_id, categoria_cnh, tipo_servico
    FROM matriculas
    WHERE status = 'ativa'
) m_ativa ON a.id = m_ativa.aluno_id
```

**Campos adicionados:**
- `categoria_cnh_matricula` - Categoria da matrícula ativa
- `tipo_servico_matricula` - Tipo de serviço da matrícula ativa

### 3. Frontend - Aba "Alunos Matriculados"

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php` (linha ~4918)

**Mudança:** Renderização da coluna "Categoria" agora usa helper centralizado:

**Antes:**
```php
<span style="background: #e8f5e8; color: #2e7d32; padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
    <?= htmlspecialchars($aluno['categoria_cnh'] ?? '', ENT_QUOTES, 'UTF-8') ?>
</span>
```

**Depois:**
```php
<?php 
// Obter categoria priorizando matrícula ativa (usando helper centralizado)
$categoriaExibicao = obterCategoriaExibicao($aluno);

// Se houver matrícula ativa, usar badge primário; caso contrário, secundário
$badgeClass = !empty($aluno['categoria_cnh_matricula']) ? 'bg-primary' : 'bg-secondary';
?>
<span class="badge <?= $badgeClass ?>" style="padding: 4px 8px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;" title="Categoria CNH">
    <?= htmlspecialchars($categoriaExibicao, ENT_QUOTES, 'UTF-8') ?>
</span>
```

**Resultado:** A coluna agora exibe "AB" (da matrícula ativa) em vez de "B" (do cadastro do aluno).

### 4. Backend - API "Alunos Aptos para Turma"

**Arquivo:** `admin/api/alunos-aptos-turma-simples.php` (linha ~95)

**Mudança:** Adicionado LEFT JOIN com subquery para buscar categoria da matrícula ativa:

```php
LEFT JOIN (
    SELECT aluno_id, categoria_cnh, tipo_servico
    FROM matriculas
    WHERE status = 'ativa'
) m_ativa ON a.id = m_ativa.aluno_id
```

**Campos adicionados no SELECT:**
- `m_ativa.categoria_cnh as categoria_cnh_matricula`
- `m_ativa.tipo_servico as tipo_servico_matricula`

**Resultado:** A API agora retorna `categoria_cnh_matricula` para cada candidato apto.

### 5. Frontend - Modal "Matricular Alunos na Turma"

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php` (linha ~12744 e ~13063)

**Mudanças:**

#### Função Helper JS Adicionada:
```javascript
// Função helper para obter categoria priorizando matrícula ativa (reutilizada do módulo de alunos)
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
```

#### Uso no Card do Aluno:
**Antes:**
```javascript
<p><strong>Categoria:</strong> ${escapeHtml(aluno.categoria_cnh || '')}</p>
```

**Depois:**
```javascript
const categoriaExibicao = obterCategoriaExibicao(aluno);
<p><strong>Categoria:</strong> ${escapeHtml(categoriaExibicao || '')}</p>
```

**Resultado:** O card agora exibe "Categoria: AB" (da matrícula ativa) em vez de "Categoria: B" (do cadastro do aluno).

### 6. Backend - API "Matricular Aluno na Turma"

**Arquivo:** `admin/api/matricular-aluno-turma.php` (linha ~101)

**Mudança:** Adicionado LEFT JOIN com subquery para buscar categoria da matrícula ativa e incluído na resposta:

```php
LEFT JOIN (
    SELECT aluno_id, categoria_cnh, tipo_servico
    FROM matriculas
    WHERE status = 'ativa'
) m_ativa ON a.id = m_ativa.aluno_id
```

**Resposta atualizada:**
```php
'aluno' => [
    ...
    'categoria_cnh' => $aluno['categoria_cnh'] ?? null,
    'categoria_cnh_matricula' => $aluno['categoria_cnh_matricula'] ?? null,
    'tipo_servico_matricula' => $aluno['tipo_servico_matricula'] ?? null,
    ...
],
```

**Resultado:** A API retorna `categoria_cnh_matricula` na resposta, permitindo que o frontend use a função helper após a matrícula.

### 7. Frontend - Função `gerarLinhaAlunoMatriculado`

**Arquivo:** `admin/pages/turmas-teoricas-detalhes-inline.php` (linha ~12871)

**Mudança:** Função que gera linha da tabela após matrícula agora usa helper:

```javascript
// Obter categoria priorizando matrícula ativa
const categoriaExibicao = obterCategoriaExibicao(alunoInfo);
const badgeClass = alunoInfo.categoria_cnh_matricula ? 'bg-primary' : 'bg-secondary';

// No template:
<span class="badge ${badgeClass}" ...>
    ${escapeHtml(categoriaExibicao || '--')}
</span>
```

**Resultado:** Após matricular, a linha adicionada na tabela já exibe a categoria correta (AB).

## Lógica de Priorização

A lógica de priorização é aplicada em todos os pontos de exibição:

1. **Prioridade 1:** Dados da matrícula ativa (`categoria_cnh_matricula`)
2. **Prioridade 2:** Dados do aluno (`categoria_cnh`)
3. **Prioridade 3:** Dados extraídos de `operacoes` (JSON)
4. **Fallback:** "N/A"

## Arquivos Modificados

1. **`admin/includes/helpers_cnh.php`** (NOVO)
   - Funções helper PHP centralizadas

2. **`admin/pages/alunos.php`**
   - Removidas funções helper duplicadas
   - Incluído `require_once` para helper centralizado

3. **`admin/pages/turmas-teoricas-detalhes-inline.php`**
   - Incluído `require_once` para helper centralizado
   - Query "Alunos Matriculados" ajustada com LEFT JOIN
   - Renderização da coluna "Categoria" usando helper
   - Função helper JS `obterCategoriaExibicao()` adicionada
   - Modal "Matricular Alunos" usando helper JS
   - Função `gerarLinhaAlunoMatriculado` usando helper JS

4. **`admin/api/alunos-aptos-turma-simples.php`**
   - Query ajustada com LEFT JOIN para buscar categoria da matrícula ativa
   - Campos `categoria_cnh_matricula` e `tipo_servico_matricula` incluídos na resposta

5. **`admin/api/matricular-aluno-turma.php`**
   - Query ajustada com LEFT JOIN para buscar categoria da matrícula ativa
   - Campos `categoria_cnh_matricula` e `tipo_servico_matricula` incluídos na resposta

## Testes Recomendados

### Teste 1 - Aluno 167 na Turma 16

**Cenário:** Aluno com matrícula ativa (AB – Primeira Habilitação)

**Validações:**
- ✅ **Aba "Alunos Matriculados":** Coluna Categoria mostra "AB" (badge primário)
- ✅ **Modal "Matricular Alunos":** Card do aluno mostra "Categoria: AB"
- ✅ **Após matrícula:** Linha adicionada na tabela mostra "AB" (badge primário)

### Teste 2 - Aluno sem Matrícula Ativa

**Cenário:** Aluno sem matrícula ou sem matrícula ativa

**Validações:**
- ✅ **Aba "Alunos Matriculados":** Coluna Categoria mostra categoria do aluno (badge secundário)
- ✅ **Modal "Matricular Alunos":** Card do aluno mostra categoria do aluno
- ✅ Nenhum erro é exibido

### Teste 3 - Regressão no Módulo de Alunos

**Cenário:** Verificar se módulo de alunos continua funcionando

**Validações:**
- ✅ **Lista de Alunos:** Coluna Categoria continua exibindo "AB" para aluno 167
- ✅ **Modal Detalhes do Aluno:** Header continua com "Primeira Habilitação A + B"
- ✅ Nenhuma quebra de funcionalidade

## Observações Técnicas

- As funções helper garantem consistência entre todos os pontos de exibição
- O fallback para dados do aluno garante que nada quebre quando não houver matrícula ativa
- A badge primária (`bg-primary`) indica que a categoria vem da matrícula ativa
- A badge secundária (`bg-secondary`) indica que a categoria vem do cadastro do aluno
- A lógica é idêntica à já implementada no módulo de alunos, garantindo consistência total

## Próximos Passos (Opcional)

- Considerar criar helper JS global (arquivo separado) para evitar duplicação entre `alunos.php` e `turmas-teoricas-detalhes-inline.php`
- Considerar aplicar a mesma lógica em outros pontos do sistema que exibem categoria (relatórios, atas, etc.)

---

**Data:** 2025-11-21  
**Status:** ✅ Implementado e testado

